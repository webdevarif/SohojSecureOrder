<?php
/**
 * Settings Handler
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Handler Class
 */
class Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize settings
     */
    private function init() {
        // Add settings page
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add AJAX handlers
        add_action('wp_ajax_sohoj_save_settings', array($this, 'save_settings'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings group
        register_setting('sohoj_settings_group', 'sohoj_example_setting');
    }
    
    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        check_ajax_referer('sohoj_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = array(
            'sohoj_example_setting' => sanitize_text_field($_POST['example_setting'])
        );
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
        
        wp_send_json_success('Settings saved successfully!');
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = '') {
        return get_option($key, $default);
    }
    
    /**
     * Update setting value
     */
    public static function update_setting($key, $value) {
        return update_option($key, $value);
    }
} 