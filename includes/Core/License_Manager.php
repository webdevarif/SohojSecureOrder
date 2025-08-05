<?php
/**
 * CurtCommerz License Manager
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CurtCommerz License Management Handler
 */
class License_Manager {
    
    /**
     * API Base URL
     */
    const API_BASE_URL = 'http://127.0.0.1:8000/api/';
    
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
        add_action('wp_ajax_sohoj_activate_license', array($this, 'activate_license_ajax'));
        add_action('wp_ajax_sohoj_deactivate_license', array($this, 'deactivate_license_ajax'));
        add_action('wp_ajax_sohoj_check_license', array($this, 'check_license_ajax'));
        
        // Schedule daily license check
        if (!wp_next_scheduled('sohoj_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'sohoj_daily_license_check');
        }
        add_action('sohoj_daily_license_check', array($this, 'check_license_status'));
    }
    
    /**
     * AJAX handler for license activation
     */
    public function activate_license_ajax() {
        check_ajax_referer('sohoj_license_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }
        
        $result = $this->activate_device($api_key);
        
        if ($result['success']) {
            // Store license data
            update_option('sohoj_api_key', $api_key);
            update_option('sohoj_license_status', 'active');
            update_option('sohoj_license_data', $result['data']);
            update_option('sohoj_license_activated_at', current_time('mysql'));
            
            wp_send_json_success([
                'message' => 'License activated successfully!',
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license deactivation
     */
    public function deactivate_license_ajax() {
        check_ajax_referer('sohoj_license_nonce', 'nonce');
        
        $result = $this->deactivate_device();
        
        if ($result['success']) {
            // Clear license data
            update_option('sohoj_api_key', '');
            update_option('sohoj_license_status', 'inactive');
            update_option('sohoj_license_data', '');
            update_option('sohoj_license_activated_at', '');
            
            wp_send_json_success('License deactivated successfully!');
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license status check
     */
    public function check_license_ajax() {
        check_ajax_referer('sohoj_license_nonce', 'nonce');
        
        $result = $this->check_license_status();
        
        if ($result['success']) {
            // Update stored license data
            update_option('sohoj_license_data', $result['data']);
            update_option('sohoj_license_last_check', current_time('mysql'));
            
            wp_send_json_success([
                'message' => 'License status updated',
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Activate device with CurtCommerz API
     */
    public function activate_device($api_key) {
        $url = self::API_BASE_URL . 'subscription/subscriptions/activate-device/';
        
        $body = [
            'device_id' => $this->get_device_id(),
            'ip_address' => $this->get_client_ip(),
            'store_url' => get_site_url(),
            'subscription_api_key' => $api_key
        ];
        
        // Log the request for debugging
        error_log('CurtCommerz License Activation Request:');
        error_log('URL: ' . $url);
        error_log('Body: ' . json_encode($body));
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 30
        ]);
        
        // Log the response for debugging
        if (is_wp_error($response)) {
            error_log('CurtCommerz License Activation WP Error: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        error_log('CurtCommerz License Activation Response:');
        error_log('Status Code: ' . $status_code);
        error_log('Response Body: ' . $response_body);
        
        if ($status_code === 200 && isset($data['success']) && $data['success']) {
            error_log('CurtCommerz License Activation Successful');
            return [
                'success' => true,
                'message' => $data['message'],
                'data' => $data['subscription']
            ];
        } else {
            $error_message = 'Activation failed';
            if (isset($data['message'])) {
                $error_message = $data['message'];
            } elseif (isset($data['error'])) {
                $error_message = $data['error'];
            } elseif (isset($data['detail'])) {
                $error_message = $data['detail'];
            } elseif (isset($data['errors'])) {
                $error_message = is_array($data['errors']) ? implode(', ', $data['errors']) : $data['errors'];
            }
            
            error_log('CurtCommerz License Activation Failed: ' . $error_message);
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
    }
    
    /**
     * Deactivate device with CurtCommerz API
     */
    public function deactivate_device() {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'No active license found'
            ];
        }
        
        $url = self::API_BASE_URL . 'subscription/subscriptions/deactivate-device/';
        
        // Log the request for debugging
        error_log('CurtCommerz License Deactivation Request:');
        error_log('URL: ' . $url);
        error_log('API Key: ' . substr($api_key, 0, 10) . '...');
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $api_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('CurtCommerz License Deactivation WP Error: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        error_log('CurtCommerz License Deactivation Response:');
        error_log('Status Code: ' . $status_code);
        error_log('Response Body: ' . $response_body);
        
        if ($status_code === 200) {
            error_log('CurtCommerz License Deactivation Successful');
            return [
                'success' => true,
                'message' => 'Device deactivated successfully'
            ];
        } else {
            $error_message = 'Deactivation failed';
            if (isset($data['message'])) {
                $error_message = $data['message'];
            } elseif (isset($data['error'])) {
                $error_message = $data['error'];
            } elseif (isset($data['detail'])) {
                $error_message = $data['detail'];
            }
            
            error_log('CurtCommerz License Deactivation Failed: ' . $error_message);
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
    }
    
    /**
     * Check license status with CurtCommerz API
     */
    public function check_license_status() {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'No API key found'
            ];
        }
        
        $url = self::API_BASE_URL . 'subscription/subscriptions/check-license/';
        
        $response = wp_remote_get($url, [
            'headers' => [
                'X-API-Key' => $api_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200 && isset($data['success']) && $data['success']) {
            // Update license status
            if (isset($data['subscription']['is_active'])) {
                $status = $data['subscription']['is_active'] ? 'active' : 'inactive';
                update_option('sohoj_license_status', $status);
            }
            
            return [
                'success' => true,
                'message' => 'License status retrieved',
                'data' => $data['subscription']
            ];
        } else {
            return [
                'success' => false,
                'message' => isset($data['message']) ? $data['message'] : 'License check failed'
            ];
        }
    }
    
    /**
     * Generate unique device ID
     */
    private function get_device_id() {
        $device_id = get_option('sohoj_device_id');
        
        if (empty($device_id)) {
            $device_id = 'wp_' . wp_generate_uuid4();
            update_option('sohoj_device_id', $device_id);
        }
        
        return $device_id;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }
    
    /**
     * Check if license is active
     */
    public static function is_license_active() {
        $status = get_option('sohoj_license_status', 'inactive');
        return $status === 'active';
    }
    
    /**
     * Get API key
     */
    public static function get_api_key() {
        return get_option('sohoj_api_key', '');
    }
    
    /**
     * Get license data
     */
    public static function get_license_data() {
        return get_option('sohoj_license_data', []);
    }
    
    /**
     * Get subscription plan name
     */
    public static function get_plan_name() {
        $license_data = self::get_license_data();
        return isset($license_data['package']['display_name']) ? $license_data['package']['display_name'] : 'Unknown';
    }
    
    /**
     * Get remaining fraud checks
     */
    public static function get_remaining_fraud_checks() {
        $license_data = self::get_license_data();
        return isset($license_data['remaining_fraud_checks']) ? intval($license_data['remaining_fraud_checks']) : 0;
    }
    
    /**
     * Get SMS balance
     */
    public static function get_sms_balance() {
        $license_data = self::get_license_data();
        return isset($license_data['subscription_sms_balance']) ? floatval($license_data['subscription_sms_balance']) : 0.0;
    }
    
    /**
     * Check if license will expire soon (within 7 days)
     */
    public static function is_license_expiring_soon() {
        $license_data = self::get_license_data();
        
        if (!isset($license_data['end_date'])) {
            return false;
        }
        
        $end_date = strtotime($license_data['end_date']);
        $seven_days_from_now = strtotime('+7 days');
        
        return $end_date <= $seven_days_from_now;
    }
} 