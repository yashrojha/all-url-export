<?php
/**
 * Admin Page Class
 *
 * @package All_URL_Export
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the admin page UI and settings
 */
class AUE_Admin_Page {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            __('All URL Export', 'all-url-export'),
            __('All URL Export', 'all-url-export'),
            'manage_options',
            'all-url-export',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $post_types = $this->get_available_post_types();
        $wpml_active = $this->is_wpml_active();
        $wpml_languages = $wpml_active ? $this->get_wpml_languages() : array();
        ?>
        <div class="wrap aue-wrap">
            <h1 class="aue-title">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('All URL Export', 'all-url-export'); ?>
            </h1>
            
            <div class="aue-container">
                <div class="aue-settings-panel">
                    <form id="aue-export-form" method="post">
                        
                        <!-- Post Types Selection -->
                        <div class="aue-section">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-admin-post"></span>
                                <?php esc_html_e('Post Types', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-section-desc"><?php esc_html_e('Select which post types to include in the export.', 'all-url-export'); ?></p>
                            
                            <div class="aue-checkbox-grid">
                                <?php foreach ($post_types as $post_type): ?>
                                    <label class="aue-checkbox-label">
                                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>" 
                                            <?php checked(in_array($post_type->name, array('post', 'page'))); ?>>
                                        <span class="aue-checkbox-text"><?php echo esc_html($post_type->labels->name); ?></span>
                                        <span class="aue-post-count">(<?php echo esc_html($this->get_post_count($post_type->name)); ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Post Status -->
                        <div class="aue-section">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e('Post Status', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-section-desc"><?php esc_html_e('Select which post statuses to include.', 'all-url-export'); ?></p>
                            
                            <div class="aue-checkbox-grid">
                                <?php 
                                $statuses = array(
                                    'publish' => __('Published', 'all-url-export'),
                                    'draft' => __('Draft', 'all-url-export'),
                                    'pending' => __('Pending Review', 'all-url-export'),
                                    'private' => __('Private', 'all-url-export'),
                                    'future' => __('Scheduled', 'all-url-export'),
                                );
                                foreach ($statuses as $status => $label): 
                                ?>
                                    <label class="aue-checkbox-label">
                                        <input type="checkbox" name="post_status[]" value="<?php echo esc_attr($status); ?>"
                                            <?php checked($status === 'publish'); ?>>
                                        <span class="aue-checkbox-text"><?php echo esc_html($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Fields to Export -->
                        <div class="aue-section">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-editor-table"></span>
                                <?php esc_html_e('Fields to Export', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-section-desc"><?php esc_html_e('Select which fields to include in the export.', 'all-url-export'); ?></p>
                            
                            <div class="aue-checkbox-grid">
                                <?php 
                                $fields = array(
                                    'id' => __('Post ID', 'all-url-export'),
                                    'title' => __('Title', 'all-url-export'),
                                    'url' => __('URL/Permalink', 'all-url-export'),
                                    'slug' => __('Slug', 'all-url-export'),
                                    'content' => __('Content', 'all-url-export'),
                                    'excerpt' => __('Excerpt', 'all-url-export'),
                                    'author' => __('Author', 'all-url-export'),
                                    'published_date' => __('Published Date', 'all-url-export'),
                                    'modified_date' => __('Last Modified Date', 'all-url-export'),
                                    'post_type' => __('Post Type', 'all-url-export'),
                                    'status' => __('Status', 'all-url-export'),
                                    'editor_used' => __('Editor Used', 'all-url-export'),
                                    'featured_image' => __('Featured Image URL', 'all-url-export'),
                                    'categories' => __('Categories', 'all-url-export'),
                                    'tags' => __('Tags', 'all-url-export'),
                                    'meta_title' => __('SEO Meta Title', 'all-url-export'),
                                    'meta_description' => __('SEO Meta Description', 'all-url-export'),
                                );
                                foreach ($fields as $field => $label): 
                                ?>
                                    <label class="aue-checkbox-label">
                                        <input type="checkbox" name="fields[]" value="<?php echo esc_attr($field); ?>"
                                            <?php checked(in_array($field, array('title', 'url', 'published_date', 'modified_date', 'editor_used'))); ?>>
                                        <span class="aue-checkbox-text"><?php echo esc_html($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Title Source Options -->
                        <div class="aue-section">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-heading"></span>
                                <?php esc_html_e('Title Source', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-section-desc"><?php esc_html_e('Choose where to fetch the title from. Some pages may have H1 in content different from post title.', 'all-url-export'); ?></p>
                            
                            <div class="aue-radio-group">
                                <label class="aue-radio-label">
                                    <input type="radio" name="title_source" value="title" checked>
                                    <span class="aue-radio-text"><?php esc_html_e('Post Title (Default)', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Use the WordPress post title', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="title_source" value="h1_fallback">
                                    <span class="aue-radio-text"><?php esc_html_e('H1 First, Then Post Title', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Use H1 from content if available, otherwise fall back to post title (ensures title column is never empty)', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="title_source" value="h1">
                                    <span class="aue-radio-text"><?php esc_html_e('H1 from Content Only', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Extract the first H1 tag from post content (may be empty if no H1 found)', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="title_source" value="both">
                                    <span class="aue-radio-text"><?php esc_html_e('Both (Separate Columns)', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Export both post title and H1 in separate columns', 'all-url-export'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Editor Type for H1 Parsing -->
                        <div class="aue-section">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-edit"></span>
                                <?php esc_html_e('Content Parsing Mode', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-section-desc"><?php esc_html_e('Select how to parse content for H1 extraction. Auto-detect is recommended.', 'all-url-export'); ?></p>
                            
                            <div class="aue-radio-group">
                                <label class="aue-radio-label">
                                    <input type="radio" name="editor_type" value="auto" checked>
                                    <span class="aue-radio-text"><?php esc_html_e('Auto Detect', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Automatically detect editor per post', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="editor_type" value="gutenberg">
                                    <span class="aue-radio-text"><?php esc_html_e('Gutenberg (Block Editor)', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('WordPress default block editor', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="editor_type" value="classic">
                                    <span class="aue-radio-text"><?php esc_html_e('Classic Editor', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Traditional WordPress editor', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="editor_type" value="elementor">
                                    <span class="aue-radio-text"><?php esc_html_e('Elementor', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Elementor page builder', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="editor_type" value="divi">
                                    <span class="aue-radio-text"><?php esc_html_e('Divi Builder', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('Divi theme builder', 'all-url-export'); ?></span>
                                </label>
                                <label class="aue-radio-label">
                                    <input type="radio" name="editor_type" value="wpbakery">
                                    <span class="aue-radio-text"><?php esc_html_e('WPBakery', 'all-url-export'); ?></span>
                                    <span class="aue-radio-desc"><?php esc_html_e('WPBakery page builder', 'all-url-export'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- WPML Options -->
                        <?php if ($wpml_active): ?>
                        <div class="aue-section aue-wpml-section">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-translation"></span>
                                <?php esc_html_e('WPML Translations', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-section-desc"><?php esc_html_e('WPML detected! Include translations alongside original posts.', 'all-url-export'); ?></p>
                            
                            <div class="aue-wpml-options">
                                <label class="aue-checkbox-label aue-checkbox-large">
                                    <input type="checkbox" name="include_wpml" value="yes" checked>
                                    <span class="aue-checkbox-text"><?php esc_html_e('Include WPML Translations', 'all-url-export'); ?></span>
                                </label>
                                
                                <div class="aue-wpml-languages">
                                    <p class="aue-languages-label"><?php esc_html_e('Available Languages:', 'all-url-export'); ?></p>
                                    <div class="aue-language-tags">
                                        <?php foreach ($wpml_languages as $code => $lang): ?>
                                            <span class="aue-language-tag <?php echo $lang['default'] ? 'aue-default-lang' : ''; ?>">
                                                <?php if (!empty($lang['flag_url'])): ?>
                                                    <img src="<?php echo esc_url($lang['flag_url']); ?>" alt="<?php echo esc_attr($lang['native_name']); ?>" class="aue-flag">
                                                <?php endif; ?>
                                                <?php echo esc_html($lang['native_name']); ?>
                                                <?php if ($lang['default']): ?>
                                                    <span class="aue-default-badge"><?php esc_html_e('Default', 'all-url-export'); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="aue-section aue-wpml-inactive">
                            <h2 class="aue-section-title">
                                <span class="dashicons dashicons-translation"></span>
                                <?php esc_html_e('WPML Translations', 'all-url-export'); ?>
                            </h2>
                            <p class="aue-notice-info">
                                <span class="dashicons dashicons-info"></span>
                                <?php esc_html_e('WPML is not active. Translation export features will be available when WPML is installed and activated.', 'all-url-export'); ?>
                            </p>
                            <input type="hidden" name="include_wpml" value="no">
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="aue-actions">
                            <button type="button" id="aue-preview-btn" class="button button-secondary button-large">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e('Preview Export', 'all-url-export'); ?>
                            </button>
                            <button type="button" id="aue-export-btn" class="button button-primary button-large">
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e('Generate & Download CSV', 'all-url-export'); ?>
                            </button>
                        </div>
                        
                    </form>
                </div>
                
                <!-- Preview Panel -->
                <div class="aue-preview-panel" id="aue-preview-panel">
                    <div class="aue-preview-header">
                        <h2>
                            <span class="dashicons dashicons-editor-table"></span>
                            <?php esc_html_e('Export Preview', 'all-url-export'); ?>
                        </h2>
                        <div class="aue-preview-actions">
                            <span id="aue-preview-count" class="aue-count-badge"></span>
                            <button type="button" id="aue-download-preview" class="button button-small" disabled>
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e('Download', 'all-url-export'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="aue-preview-content" id="aue-preview-content">
                        <div class="aue-preview-placeholder">
                            <span class="dashicons dashicons-format-aside"></span>
                            <p><?php esc_html_e('Click "Preview Export" to see your data here.', 'all-url-export'); ?></p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Loading Overlay -->
            <div class="aue-loading-overlay" id="aue-loading">
                <div class="aue-spinner">
                    <div class="aue-spinner-inner"></div>
                </div>
                <p><?php esc_html_e('Generating export...', 'all-url-export'); ?></p>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Get available post types
     */
    private function get_available_post_types() {
        $args = array(
            'public' => true,
        );
        
        $post_types = get_post_types($args, 'objects');
        
        // Remove attachment
        unset($post_types['attachment']);
        
        return $post_types;
    }
    
    /**
     * Get post count for post type
     */
    private function get_post_count($post_type) {
        $count = wp_count_posts($post_type);
        return isset($count->publish) ? $count->publish : 0;
    }
    
    /**
     * Check if WPML is active
     */
    private function is_wpml_active() {
        return defined('ICL_SITEPRESS_VERSION') && function_exists('icl_get_languages');
    }
    
    /**
     * Get WPML languages
     */
    private function get_wpml_languages() {
        if (!$this->is_wpml_active()) {
            return array();
        }
        
        $languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
        
        if (!is_array($languages)) {
            return array();
        }
        
        $result = array();
        foreach ($languages as $code => $lang) {
            $result[$code] = array(
                'native_name' => $lang['native_name'],
                'translated_name' => $lang['translated_name'],
                'flag_url' => isset($lang['country_flag_url']) ? $lang['country_flag_url'] : '',
                'default' => isset($lang['active']) && $lang['active'] == 1,
            );
        }
        
        return $result;
    }
}
