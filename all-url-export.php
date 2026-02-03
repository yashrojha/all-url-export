<?php
/**
 * Plugin Name: All URL Export
 * Plugin URI: http://ifoxsolutions.com/
 * Description: Export all post types with customizable fields including title, H1, content, dates, author, and WPML translations.
 * Version: 1.0.1
 * Author: Yash Ojha
 * Author URI: https://ifoxsolutions.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: all-url-export
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AUE_VERSION', '1.0.0');
define('AUE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class All_URL_Export {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once AUE_PLUGIN_DIR . 'includes/class-export-handler.php';
        require_once AUE_PLUGIN_DIR . 'includes/class-csv-generator.php';
        require_once AUE_PLUGIN_DIR . 'includes/class-admin-page.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Initialize admin page
        if (is_admin()) {
            AUE_Admin_Page::get_instance();
        }
        
        // AJAX handlers
        add_action('wp_ajax_aue_generate_export', array($this, 'ajax_generate_export'));
        add_action('wp_ajax_aue_download_csv', array($this, 'ajax_download_csv'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('all-url-export', false, dirname(AUE_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('tools_page_all-url-export' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'aue-admin-css',
            AUE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AUE_VERSION
        );
        
        wp_enqueue_script(
            'aue-admin-js',
            AUE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AUE_VERSION,
            true
        );
        
        wp_localize_script('aue-admin-js', 'aue_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aue_export_nonce'),
            'strings' => array(
                'generating' => __('Generating export...', 'all-url-export'),
                'error' => __('An error occurred. Please try again.', 'all-url-export'),
                'no_posts' => __('No posts found with the selected criteria.', 'all-url-export'),
                'select_field' => __('Please select at least one field to export.', 'all-url-export'),
                'select_post_type' => __('Please select at least one post type.', 'all-url-export'),
            )
        ));
    }
    
    /**
     * AJAX handler for generating export
     */
    public function ajax_generate_export() {
        check_ajax_referer('aue_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'all-url-export')));
        }
        
        $options = $this->sanitize_export_options($_POST);
        
        if (empty($options['post_types'])) {
            wp_send_json_error(array('message' => __('Please select at least one post type.', 'all-url-export')));
        }
        
        if (empty($options['fields'])) {
            wp_send_json_error(array('message' => __('Please select at least one field to export.', 'all-url-export')));
        }
        
        $handler = new AUE_Export_Handler($options);
        $data = $handler->get_export_data();
        
        if (empty($data)) {
            wp_send_json_error(array('message' => __('No posts found with the selected criteria.', 'all-url-export')));
        }
        
        // Store in transient for download
        $export_key = 'aue_export_' . get_current_user_id() . '_' . time();
        set_transient($export_key, array(
            'data' => $data,
            'options' => $options
        ), HOUR_IN_SECONDS);
        
        wp_send_json_success(array(
            'data' => $data,
            'export_key' => $export_key,
            'count' => count($data)
        ));
    }
    
    /**
     * AJAX handler for CSV download
     */
    public function ajax_download_csv() {
        check_ajax_referer('aue_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'all-url-export'));
        }
        
        $export_key = isset($_GET['export_key']) ? sanitize_text_field($_GET['export_key']) : '';
        $export_data = get_transient($export_key);
        
        if (!$export_data) {
            wp_die(__('Export data expired. Please generate a new export.', 'all-url-export'));
        }
        
        $csv_generator = new AUE_CSV_Generator($export_data['data'], $export_data['options']);
        $csv_generator->download();
        exit;
    }
    
    /**
     * Sanitize export options
     */
    private function sanitize_export_options($data) {
        $options = array();
        
        // Post types
        $options['post_types'] = isset($data['post_types']) && is_array($data['post_types']) 
            ? array_map('sanitize_text_field', $data['post_types']) 
            : array();
        
        // Fields to export
        $options['fields'] = isset($data['fields']) && is_array($data['fields']) 
            ? array_map('sanitize_text_field', $data['fields']) 
            : array();
        
        // Title source (title or h1)
        $options['title_source'] = isset($data['title_source']) 
            ? sanitize_text_field($data['title_source']) 
            : 'title';
        
        // Editor type
        $options['editor_type'] = isset($data['editor_type']) 
            ? sanitize_text_field($data['editor_type']) 
            : 'auto';
        
        // Include WPML translations
        $options['include_wpml'] = isset($data['include_wpml']) && $data['include_wpml'] === 'yes';
        
        // Post status
        $options['post_status'] = isset($data['post_status']) && is_array($data['post_status'])
            ? array_map('sanitize_text_field', $data['post_status'])
            : array('publish');
        
        return $options;
    }
}

// Initialize the plugin
function aue_init() {
    return All_URL_Export::get_instance();
}
add_action('plugins_loaded', 'aue_init', 5);

// Activation hook
register_activation_hook(__FILE__, function() {
    // Activation tasks if needed
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup tasks if needed
    flush_rewrite_rules();
});