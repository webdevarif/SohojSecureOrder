<?php
/**
 * License Manager
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * License Management Handler
 */
class License_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize license manager
     */
    private function init() {
        // Add AJAX handlers
        add_action('wp_ajax_sohoj_activate_license', array($this, 'activate_license'));
        add_action('wp_ajax_sohoj_deactivate_license', array($this, 'deactivate_license'));
    }
    
    /**
     * Activate license
     */
    public function activate_license() {
        check_ajax_referer('sohoj_license_nonce', 'nonce');
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        if (empty($license_key)) {
            wp_send_json_error('License key is required');
        }
        
        // Simulate license validation (replace with actual API call)
        $is_valid = $this->validate_license($license_key);
        
        if ($is_valid) {
            update_option('sohoj_license_key', $license_key);
            update_option('sohoj_license_status', 'active');
            wp_send_json_success('License activated successfully!');
        } else {
            wp_send_json_error('Invalid license key');
        }
    }
    
    /**
     * Deactivate license
     */
    public function deactivate_license() {
        check_ajax_referer('sohoj_license_nonce', 'nonce');
        
        update_option('sohoj_license_key', '');
        update_option('sohoj_license_status', 'inactive');
        
        wp_send_json_success('License deactivated successfully!');
    }
    
    /**
     * Validate license key
     */
    private function validate_license($license_key) {
        // Simulate API call to license server
        // In real implementation, make HTTP request to your license server
        
        // For demo purposes, accept any non-empty key
        return !empty($license_key);
    }
    
    /**
     * Check if license is active
     */
    public static function is_license_active() {
        $status = get_option('sohoj_license_status', 'inactive');
        return $status === 'active';
    }
    
    /**
     * Get license key
     */
    public static function get_license_key() {
        return get_option('sohoj_license_key', '');
    }
} 