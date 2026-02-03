<?php
/**
 * Export Handler Class
 *
 * @package All_URL_Export
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the data fetching and processing for export
 */
class AUE_Export_Handler {
    
    /**
     * Export options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct($options) {
        $this->options = $options;
    }
    
    /**
     * Get export data
     */
    public function get_export_data() {
        $posts = $this->fetch_posts();
        $data = array();
        
        foreach ($posts as $post) {
            $row = $this->process_post($post);
            
            // If WPML is enabled and we want translations
            if ($this->options['include_wpml'] && $this->is_wpml_active()) {
                $translations = $this->get_wpml_translations($post->ID);
                
                if (!empty($translations)) {
                    // Add translation columns to the row
                    foreach ($translations as $lang_code => $translation) {
                        $row = array_merge($row, $this->get_translation_columns($translation, $lang_code));
                    }
                }
            }
            
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Fetch posts based on options
     */
    private function fetch_posts() {
        $args = array(
            'post_type' => $this->options['post_types'],
            'post_status' => $this->options['post_status'],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
        );
        
        // If WPML is active, get only default language posts
        if ($this->options['include_wpml'] && $this->is_wpml_active()) {
            $default_lang = apply_filters('wpml_default_language', null);
            $args['lang'] = $default_lang;
        }
        
        return get_posts($args);
    }
    
    /**
     * Process a single post
     */
    private function process_post($post) {
        $row = array();
        $fields = $this->options['fields'];
        
        foreach ($fields as $field) {
            switch ($field) {
                case 'id':
                    $row['ID'] = $post->ID;
                    break;
                    
                case 'title':
                    $row['Title'] = $this->get_title($post);
                    break;
                    
                case 'url':
                    $row['URL'] = get_permalink($post->ID);
                    break;
                    
                case 'slug':
                    $row['Slug'] = $post->post_name;
                    break;
                    
                case 'content':
                    $row['Content'] = $this->get_content($post);
                    break;
                    
                case 'excerpt':
                    $row['Excerpt'] = $this->get_excerpt($post);
                    break;
                    
                case 'author':
                    $author = get_userdata($post->post_author);
                    $row['Author'] = $author ? $author->display_name : '';
                    break;
                    
                case 'published_date':
                    $row['Published Date'] = get_the_date('Y-m-d H:i:s', $post->ID);
                    break;
                    
                case 'modified_date':
                    $row['Last Modified'] = get_the_modified_date('Y-m-d H:i:s', $post->ID);
                    break;
                    
                case 'post_type':
                    $post_type_obj = get_post_type_object($post->post_type);
                    $row['Post Type'] = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
                    break;
                    
                case 'status':
                    $row['Status'] = ucfirst($post->post_status);
                    break;
                    
                case 'editor_used':
                    $row['Editor Used'] = $this->get_editor_used($post);
                    break;
                    
                case 'featured_image':
                    $row['Featured Image'] = get_the_post_thumbnail_url($post->ID, 'full') ?: '';
                    break;
                    
                case 'categories':
                    $row['Categories'] = $this->get_taxonomy_terms($post->ID, 'category');
                    break;
                    
                case 'tags':
                    $row['Tags'] = $this->get_taxonomy_terms($post->ID, 'post_tag');
                    break;
                    
                case 'meta_title':
                    $row['SEO Title'] = $this->get_seo_title($post->ID);
                    break;
                    
                case 'meta_description':
                    $row['SEO Description'] = $this->get_seo_description($post->ID);
                    break;
            }
        }
        
        // Add H1 column if title source includes it
        if (in_array('title', $fields) && in_array($this->options['title_source'], array('h1', 'both'))) {
            if ($this->options['title_source'] === 'both') {
                $row['H1 Tag'] = $this->extract_h1($post);
            }
        }
        
        return $row;
    }
    
    /**
     * Get the editor used to create/edit the post
     */
    private function get_editor_used($post) {
        $content = $post->post_content;
        
        // Check for Elementor
        $elementor_mode = get_post_meta($post->ID, '_elementor_edit_mode', true);
        if ($elementor_mode === 'builder') {
            return 'Elementor';
        }
        
        // Check for Divi Builder
        $divi_builder = get_post_meta($post->ID, '_et_pb_use_builder', true);
        if ($divi_builder === 'on') {
            return 'Divi Builder';
        }
        
        // Check for Beaver Builder
        $beaver_builder = get_post_meta($post->ID, '_fl_builder_enabled', true);
        if ($beaver_builder) {
            return 'Beaver Builder';
        }
        
        // Check for WPBakery (Visual Composer)
        if (strpos($content, '[vc_row') !== false || strpos($content, '[vc_column') !== false) {
            return 'WPBakery';
        }
        
        // Check for Oxygen Builder
        $oxygen = get_post_meta($post->ID, 'ct_builder_shortcodes', true);
        if (!empty($oxygen)) {
            return 'Oxygen Builder';
        }
        
        // Check for Brizy
        $brizy = get_post_meta($post->ID, 'brizy_post_uid', true);
        if (!empty($brizy)) {
            return 'Brizy';
        }
        
        // Check for Thrive Architect
        $thrive = get_post_meta($post->ID, 'tve_updated_post', true);
        if (!empty($thrive)) {
            return 'Thrive Architect';
        }
        
        // Check for Gutenberg (Block Editor) - has block comments
        if (strpos($content, '<!-- wp:') !== false) {
            return 'Block Editor (Gutenberg)';
        }
        
        // Check for Classic Editor - no blocks, but has content
        if (!empty($content)) {
            return 'Classic Editor';
        }
        
        // Empty or unknown
        return 'Unknown';
    }
    
    /**
     * Get title based on title source option
     */
    private function get_title($post) {
        switch ($this->options['title_source']) {
            case 'h1_fallback':
                // Try H1 first, fall back to post title if H1 not found
                $h1 = $this->extract_h1($post);
                return !empty($h1) ? $h1 : $post->post_title;
                
            case 'h1':
                $h1 = $this->extract_h1($post);
                return $h1 ?: $post->post_title;
                
            case 'title':
            case 'both':
            default:
                return $post->post_title;
        }
    }
    
    /**
     * Extract H1 from post content
     */
    private function extract_h1($post) {
        $content = $post->post_content;
        $editor_type = $this->detect_editor_type($post);
        
        // Parse content based on editor type
        $content = $this->parse_content_for_editor($content, $editor_type, $post->ID);
        
        // Look for H1 tag
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
            return wp_strip_all_tags($matches[1]);
        }
        
        // Check for Gutenberg heading block with level 1
        if (preg_match('/<!-- wp:heading {"level":1} -->\s*<h1[^>]*>(.*?)<\/h1>/is', $post->post_content, $matches)) {
            return wp_strip_all_tags($matches[1]);
        }
        
        return '';
    }
    
    /**
     * Detect editor type used for a post
     */
    private function detect_editor_type($post) {
        if ($this->options['editor_type'] !== 'auto') {
            return $this->options['editor_type'];
        }
        
        $content = $post->post_content;
        
        // Check for Elementor
        if (get_post_meta($post->ID, '_elementor_edit_mode', true) === 'builder') {
            return 'elementor';
        }
        
        // Check for Divi
        if (get_post_meta($post->ID, '_et_pb_use_builder', true) === 'on') {
            return 'divi';
        }
        
        // Check for WPBakery
        if (strpos($content, '[vc_') !== false || strpos($content, '[/vc_') !== false) {
            return 'wpbakery';
        }
        
        // Check for Gutenberg blocks
        if (strpos($content, '<!-- wp:') !== false) {
            return 'gutenberg';
        }
        
        return 'classic';
    }
    
    /**
     * Parse content based on editor type
     */
    private function parse_content_for_editor($content, $editor_type, $post_id = 0) {
        switch ($editor_type) {
            case 'gutenberg':
                // Strip Gutenberg comments but keep HTML
                return preg_replace('/<!--(.|\s)*?-->/', '', $content);
                
            case 'elementor':
                // For Elementor, try to get rendered content
                if (class_exists('\Elementor\Plugin') && $post_id) {
                    $document = \Elementor\Plugin::$instance->documents->get($post_id);
                    if ($document) {
                        return $document->get_content();
                    }
                }
                return $content;
                
            case 'divi':
                // Strip Divi shortcodes
                return preg_replace('/\[et_pb_[^\]]*\]|\[\/et_pb_[^\]]*\]/', '', $content);
                
            case 'wpbakery':
                // Strip WPBakery shortcodes
                return preg_replace('/\[vc_[^\]]*\]|\[\/vc_[^\]]*\]/', '', $content);
                
            case 'classic':
            default:
                return $content;
        }
    }
    
    /**
     * Get processed content
     */
    private function get_content($post) {
        $content = $post->post_content;
        $editor_type = $this->detect_editor_type($post);
        
        // Parse content based on editor
        $content = $this->parse_content_for_editor($content, $editor_type, $post->ID);
        
        // Apply content filters
        $content = apply_filters('the_content', $content);
        
        // Strip HTML tags
        $content = wp_strip_all_tags($content);
        
        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Get excerpt
     */
    private function get_excerpt($post) {
        if (!empty($post->post_excerpt)) {
            return $post->post_excerpt;
        }
        
        // Generate excerpt from content
        $content = $this->get_content($post);
        return wp_trim_words($content, 55, '...');
    }
    
    /**
     * Get taxonomy terms
     */
    private function get_taxonomy_terms($post_id, $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        
        if (!$terms || is_wp_error($terms)) {
            return '';
        }
        
        $term_names = wp_list_pluck($terms, 'name');
        return implode(', ', $term_names);
    }
    
    /**
     * Get SEO title (supports Yoast, Rank Math, All in One SEO)
     */
    private function get_seo_title($post_id) {
        // Yoast SEO
        $title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (!empty($title)) {
            return $title;
        }
        
        // Rank Math
        $title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($title)) {
            return $title;
        }
        
        // All in One SEO
        $title = get_post_meta($post_id, '_aioseo_title', true);
        if (!empty($title)) {
            return $title;
        }
        
        return '';
    }
    
    /**
     * Get SEO description (supports Yoast, Rank Math, All in One SEO)
     */
    private function get_seo_description($post_id) {
        // Yoast SEO
        $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (!empty($desc)) {
            return $desc;
        }
        
        // Rank Math
        $desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($desc)) {
            return $desc;
        }
        
        // All in One SEO
        $desc = get_post_meta($post_id, '_aioseo_description', true);
        if (!empty($desc)) {
            return $desc;
        }
        
        return '';
    }
    
    /**
     * Check if WPML is active
     */
    private function is_wpml_active() {
        return defined('ICL_SITEPRESS_VERSION') && function_exists('icl_get_languages');
    }
    
    /**
     * Get translated URL for a post in specific language
     */
    private function get_translated_url($post_id, $lang_code) {
        if (!$this->is_wpml_active()) {
            return get_permalink($post_id);
        }
        
        global $sitepress;
        
        if ($sitepress) {
            // Save current language
            $current_lang = $sitepress->get_current_language();
            
            // Switch to target language
            $sitepress->switch_lang($lang_code);
            
            // Get permalink in the target language context
            $url = get_permalink($post_id);
            
            // Restore original language
            $sitepress->switch_lang($current_lang);
            
            return $url;
        }
        
        // Fallback if WPML is active but $sitepress is not available
        return get_permalink($post_id);
    }
    
    /**
     * Get WPML translations for a post
     */
    private function get_wpml_translations($post_id) {
        if (!$this->is_wpml_active()) {
            return array();
        }
        
        $post_type = get_post_type($post_id);
        $trid = apply_filters('wpml_element_trid', null, $post_id, 'post_' . $post_type);
        
        if (!$trid) {
            return array();
        }
        
        $translations = apply_filters('wpml_get_element_translations', null, $trid, 'post_' . $post_type);
        
        if (!is_array($translations)) {
            return array();
        }
        
        $default_lang = apply_filters('wpml_default_language', null);
        $result = array();
        
        foreach ($translations as $lang_code => $translation) {
            // Skip default language (already processed as main post)
            if ($lang_code === $default_lang) {
                continue;
            }
            
            if (isset($translation->element_id)) {
                $translated_post = get_post($translation->element_id);
                if ($translated_post) {
                    $result[$lang_code] = $translated_post;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Get translation columns for a translated post
     */
    private function get_translation_columns($post, $lang_code) {
        $columns = array();
        $lang_code_upper = strtoupper($lang_code);
        $fields = $this->options['fields'];
        
        foreach ($fields as $field) {
            switch ($field) {
                case 'id':
                    $columns["ID ({$lang_code_upper})"] = $post->ID;
                    break;
                    
                case 'title':
                    $columns["Title ({$lang_code_upper})"] = $this->get_title($post);
                    break;
                    
                case 'url':
                    $columns["URL ({$lang_code_upper})"] = $this->get_translated_url($post->ID, $lang_code);
                    break;
                    
                case 'slug':
                    $columns["Slug ({$lang_code_upper})"] = $post->post_name;
                    break;
                    
                case 'content':
                    $columns["Content ({$lang_code_upper})"] = $this->get_content($post);
                    break;
                    
                case 'excerpt':
                    $columns["Excerpt ({$lang_code_upper})"] = $this->get_excerpt($post);
                    break;
                    
                case 'author':
                    $author = get_userdata($post->post_author);
                    $columns["Author ({$lang_code_upper})"] = $author ? $author->display_name : '';
                    break;
                    
                case 'published_date':
                    $columns["Published Date ({$lang_code_upper})"] = get_the_date('Y-m-d H:i:s', $post->ID);
                    break;
                    
                case 'modified_date':
                    $columns["Last Modified ({$lang_code_upper})"] = get_the_modified_date('Y-m-d H:i:s', $post->ID);
                    break;
                    
                case 'status':
                    $columns["Status ({$lang_code_upper})"] = ucfirst($post->post_status);
                    break;
                    
                case 'editor_used':
                    $columns["Editor Used ({$lang_code_upper})"] = $this->get_editor_used($post);
                    break;
                    
                case 'featured_image':
                    $columns["Featured Image ({$lang_code_upper})"] = get_the_post_thumbnail_url($post->ID, 'full') ?: '';
                    break;
                    
                case 'meta_title':
                    $columns["SEO Title ({$lang_code_upper})"] = $this->get_seo_title($post->ID);
                    break;
                    
                case 'meta_description':
                    $columns["SEO Description ({$lang_code_upper})"] = $this->get_seo_description($post->ID);
                    break;
            }
        }
        
        // Add H1 for translations if needed
        if (in_array('title', $fields) && $this->options['title_source'] === 'both') {
            $columns["H1 Tag ({$lang_code_upper})"] = $this->extract_h1($post);
        }
        
        return $columns;
    }
}
