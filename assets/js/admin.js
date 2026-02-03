/**
 * All URL Export - Admin JavaScript
 *
 * @package All_URL_Export
 */

(function($) {
    'use strict';

    var AUE = {
        
        /**
         * Current export key for download
         */
        currentExportKey: null,
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            $('#aue-preview-btn').on('click', this.handlePreview.bind(this));
            $('#aue-export-btn').on('click', this.handleExport.bind(this));
            $('#aue-download-preview').on('click', this.handleDownload.bind(this));
            
            // Select all/none helpers
            this.addSelectAllToggle();
        },
        
        /**
         * Add select all toggle to checkbox groups
         */
        addSelectAllToggle: function() {
            $('.aue-section').each(function() {
                var $section = $(this);
                var $checkboxes = $section.find('input[type="checkbox"]');
                
                if ($checkboxes.length > 3) {
                    var $title = $section.find('.aue-section-title');
                    var $toggles = $('<span class="aue-toggle-links">' +
                        '<a href="#" class="aue-select-all">' + 'Select All' + '</a> | ' +
                        '<a href="#" class="aue-select-none">' + 'Select None' + '</a>' +
                        '</span>');
                    
                    $title.append($toggles);
                    
                    $toggles.find('.aue-select-all').on('click', function(e) {
                        e.preventDefault();
                        $checkboxes.prop('checked', true);
                    });
                    
                    $toggles.find('.aue-select-none').on('click', function(e) {
                        e.preventDefault();
                        $checkboxes.prop('checked', false);
                    });
                }
            });
        },
        
        /**
         * Handle preview button click
         */
        handlePreview: function(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }
            
            this.showLoading();
            
            var formData = this.getFormData();
            formData.action = 'aue_generate_export';
            formData.nonce = aue_ajax.nonce;
            
            $.ajax({
                url: aue_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: this.handlePreviewSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: this.hideLoading.bind(this)
            });
        },
        
        /**
         * Handle export button click
         */
        handleExport: function(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }
            
            this.showLoading();
            
            var formData = this.getFormData();
            formData.action = 'aue_generate_export';
            formData.nonce = aue_ajax.nonce;
            
            $.ajax({
                url: aue_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: this.handleExportSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: this.hideLoading.bind(this)
            });
        },
        
        /**
         * Handle download button click
         */
        handleDownload: function(e) {
            e.preventDefault();
            
            if (!this.currentExportKey) {
                alert(aue_ajax.strings.error);
                return;
            }
            
            var downloadUrl = aue_ajax.ajax_url + 
                '?action=aue_download_csv' +
                '&export_key=' + encodeURIComponent(this.currentExportKey) +
                '&nonce=' + encodeURIComponent(aue_ajax.nonce);
            
            window.location.href = downloadUrl;
        },
        
        /**
         * Handle preview success
         */
        handlePreviewSuccess: function(response) {
            if (response.success) {
                this.currentExportKey = response.data.export_key;
                this.renderPreview(response.data.data);
                $('#aue-preview-count').text(response.data.count + ' items');
                $('#aue-download-preview').prop('disabled', false);
            } else {
                this.showError(response.data.message);
            }
        },
        
        /**
         * Handle export success
         */
        handleExportSuccess: function(response) {
            if (response.success) {
                this.currentExportKey = response.data.export_key;
                this.renderPreview(response.data.data);
                $('#aue-preview-count').text(response.data.count + ' items');
                $('#aue-download-preview').prop('disabled', false);
                
                // Trigger download
                this.handleDownload({ preventDefault: function() {} });
            } else {
                this.showError(response.data.message);
            }
        },
        
        /**
         * Handle AJAX error
         */
        handleError: function(xhr, status, error) {
            this.showError(aue_ajax.strings.error + ' ' + error);
        },
        
        /**
         * Validate form
         */
        validateForm: function() {
            var postTypes = $('input[name="post_types[]"]:checked').length;
            var fields = $('input[name="fields[]"]:checked').length;
            
            if (postTypes === 0) {
                this.showError(aue_ajax.strings.select_post_type);
                return false;
            }
            
            if (fields === 0) {
                this.showError(aue_ajax.strings.select_field);
                return false;
            }
            
            return true;
        },
        
        /**
         * Get form data
         */
        getFormData: function() {
            var formData = {};
            
            // Post types
            formData.post_types = [];
            $('input[name="post_types[]"]:checked').each(function() {
                formData.post_types.push($(this).val());
            });
            
            // Post status
            formData.post_status = [];
            $('input[name="post_status[]"]:checked').each(function() {
                formData.post_status.push($(this).val());
            });
            
            // Fields
            formData.fields = [];
            $('input[name="fields[]"]:checked').each(function() {
                formData.fields.push($(this).val());
            });
            
            // Title source
            formData.title_source = $('input[name="title_source"]:checked').val();
            
            // Editor type
            formData.editor_type = $('input[name="editor_type"]:checked').val();
            
            // WPML
            formData.include_wpml = $('input[name="include_wpml"]:checked').val() || 'no';
            
            return formData;
        },
        
        /**
         * Render preview table
         */
        renderPreview: function(data) {
            if (!data || data.length === 0) {
                this.showError(aue_ajax.strings.no_posts);
                return;
            }
            
            var headers = Object.keys(data[0]);
            var $container = $('#aue-preview-content');
            
            // Build table
            var html = '<div class="aue-table-wrapper"><table class="aue-preview-table">';
            
            // Headers
            html += '<thead><tr>';
            headers.forEach(function(header) {
                html += '<th>' + this.escapeHtml(header) + '</th>';
            }, this);
            html += '</tr></thead>';
            
            // Body
            html += '<tbody>';
            var maxRows = Math.min(data.length, 50);
            
            for (var i = 0; i < maxRows; i++) {
                html += '<tr>';
                headers.forEach(function(header) {
                    var value = data[i][header] || '';
                    // Truncate long values
                    if (value.length > 150) {
                        value = value.substring(0, 150) + '...';
                    }
                    html += '<td>' + this.escapeHtml(value) + '</td>';
                }, this);
                html += '</tr>';
            }
            
            html += '</tbody></table></div>';
            
            // Add note if truncated
            if (data.length > 50) {
                html += '<p class="aue-preview-note">Showing 50 of ' + data.length + ' items. Download CSV to see all data.</p>';
            }
            
            $container.html(html);
        },
        
        /**
         * Show loading overlay
         */
        showLoading: function() {
            $('#aue-loading').addClass('active');
        },
        
        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#aue-loading').removeClass('active');
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            var $container = $('#aue-preview-content');
            $container.html(
                '<div class="aue-error-message">' +
                '<span class="dashicons dashicons-warning"></span>' +
                '<p>' + this.escapeHtml(message) + '</p>' +
                '</div>'
            );
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        AUE.init();
    });
    
})(jQuery);
