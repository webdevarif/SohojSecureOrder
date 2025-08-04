<?php
/**
 * Order Limiter
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

use SohojSecureOrder\Core\Phone_Validator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order Limiter Class
 */
class Order_Limiter {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize order limiting functionality
     */
    private function init() {
        // Always hook, check if enabled inside the method
        add_action('woocommerce_checkout_process', array($this, 'validate_order_limit'), 5);
        error_log('Order Limiter: Hook registered for woocommerce_checkout_process');
    }
    
    /**
     * Check if order limiting is enabled
     * 
     * @return bool
     */
    private function is_order_limit_enabled() {
        $enabled = get_option('sohoj_order_limit_enabled', 0);
        error_log('Order Limiter: is_order_limit_enabled check - option value: ' . $enabled . ', result: ' . ($enabled == 1 ? 'true' : 'false'));
        return $enabled == 1;
    }
    
    /**
     * Get order limit settings
     * 
     * @return array
     */
    private function get_order_limit_settings() {
        return array(
            'count' => absint(get_option('sohoj_order_limit_count', 5)),
            'time_value' => absint(get_option('sohoj_order_limit_time_value', 60)),
            'time_unit' => get_option('sohoj_order_limit_time_unit', 'minutes'),
            'method' => get_option('sohoj_order_limit_method', 'billing_phone')
        );
    }
    
    /**
     * Convert time unit to seconds
     * 
     * @param int $value Time value
     * @param string $unit Time unit (minutes/hours)
     * @return int Seconds
     */
    private function convert_to_seconds($value, $unit) {
        switch ($unit) {
            case 'hours':
                return $value * 3600;
            case 'minutes':
            default:
                return $value * 60;
        }
    }
    
    /**
     * Normalize phone number for consistent comparison - just digits
     * 
     * @param string $phone Raw phone number
     * @return string Normalized phone number (digits only)
     */
    private function normalize_phone($phone) {
        return Phone_Validator::normalize_bangladeshi_phone($phone);
    }
    
    /**
     * Get customer orders within time period using simple approach
     * 
     * @param string $customer_identifier Customer email or phone
     * @param int $time_period_seconds Time period in seconds
     * @param string $method Limiting method (billing_phone or billing_email)
     * @return array Order objects
     */
    private function get_customer_orders($customer_identifier, $time_period_seconds, $method = 'billing_email') {
        error_log('[SohojSecureOrder Debug] Order Limiter: get_customer_orders called with identifier: ' . $customer_identifier . ', method: ' . $method);
        error_log('[SohojSecureOrder Debug] Order Limiter: Time period: ' . $time_period_seconds . ' seconds');
        
        $args = array(
            'limit' => -1, // Get all matching orders
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'status' => array('processing', 'completed', 'on-hold', 'pending'),
            'date_created' => '>=' . (time() - $time_period_seconds),
        );

        if ($method === 'billing_phone') {
            $normalized_phone = $this->normalize_phone($customer_identifier);
            error_log('[SohojSecureOrder Debug] Order Limiter: Searching for normalized phone: ' . $normalized_phone);
            $args['meta_query'] = array(
                array(
                    'key' => '_billing_phone',
                    'value' => $normalized_phone,
                    'compare' => '=',
                ),
            );
        } else {
            $args['billing_email'] = $customer_identifier;
        }
        
        error_log('[SohojSecureOrder Debug] Order Limiter: Query arguments: ' . print_r($args, true));

        $orders = wc_get_orders($args);
        
        error_log('[SohojSecureOrder Debug] Order Limiter: Found ' . count($orders) . ' orders for identifier: ' . $customer_identifier);
        
        if (!empty($orders)) {
            foreach ($orders as $order) {
                error_log('[SohojSecureOrder Debug] Order Limiter: Retrieved Order ID: ' . $order->get_id() . ' | Billing Phone: ' . $order->get_billing_phone());
            }
        }

        return $orders;
    }
    
    /**
     * Calculate remaining time until next order allowed
     * 
     * @param array $orders Customer orders
     * @param int $time_period_seconds Time period in seconds
     * @return int Remaining seconds
     */
    private function calculate_remaining_time($orders, $time_period_seconds) {
        if (empty($orders)) {
            return 0;
        }
        
        // Find the oldest order that's still within the time period
        $oldest_order_time = null;
        foreach ($orders as $order) {
            $order_time = $order->get_date_created()->getTimestamp();
            if ($oldest_order_time === null || $order_time < $oldest_order_time) {
                $oldest_order_time = $order_time;
            }
        }
        
        if ($oldest_order_time === null) {
            return 0;
        }
        
        $time_when_oldest_expires = $oldest_order_time + $time_period_seconds;
        $remaining = $time_when_oldest_expires - time();
        
        return max(0, $remaining);
    }
    
    /**
     * Format seconds to human readable time
     * 
     * @param int $seconds Seconds
     * @param string $preferred_unit Preferred unit (minutes/hours)
     * @return array Array with 'value' and 'unit'
     */
    private function format_time($seconds, $preferred_unit = 'minutes') {
        if ($seconds <= 0) {
            return array('value' => 0, 'unit' => $preferred_unit);
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0 && ($preferred_unit === 'hours' || $hours >= 2)) {
            return array(
                'value' => $hours,
                'unit' => $hours === 1 ? 'hour' : 'hours'
            );
        } else {
            $minutes = max(1, $minutes); // Show at least 1 minute
            return array(
                'value' => $minutes,
                'unit' => $minutes === 1 ? 'minute' : 'minutes'
            );
        }
    }
    
    /**
     * Replace placeholders in message
     * 
     * @param string $message Message with placeholders
     * @param array $data Replacement data
     * @return string Processed message
     */
    private function replace_placeholders($message, $data) {
        $placeholders = array(
            '{{ count }}' => $data['count'],
            '{{ limit }}' => $data['limit'],
            '{{ period }}' => $data['period'],
            '{{ unit }}' => $data['unit'],
            '{{ remaining }}' => $data['remaining_value'],
            '{{ remaining_unit }}' => $data['remaining_unit']
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $message);
    }
    
    /**
     * Validate order limit during checkout
     */
    public function validate_order_limit() {
        error_log('Order Limiter: validate_order_limit() called');
        
        if (!class_exists('WooCommerce')) {
            error_log('Order Limiter: WooCommerce not active');
            return;
        }
        
        // Check if order limiting is enabled
        if (!$this->is_order_limit_enabled()) {
            error_log('Order Limiter: Order limiting is disabled');
            return;
        }
        
        $settings = $this->get_order_limit_settings();
        $method = $settings['method'];
        
        error_log('Order Limiter: Settings - ' . print_r($settings, true));
        error_log('Order Limiter: Method - ' . $method);
        error_log('Order Limiter: POST data - ' . print_r($_POST, true));
        
        // Get customer identifier based on method
        if ($method === 'billing_phone') {
            $customer_identifier = sanitize_text_field($_POST['billing_phone'] ?? '');
            error_log('Order Limiter: Phone identifier - ' . $customer_identifier);
            if (empty($customer_identifier)) {
                error_log('Order Limiter: Phone identifier is empty');
                return;
            }
            // Normalize phone for consistent searching
            $customer_identifier = $this->normalize_phone($customer_identifier);
            error_log('Order Limiter: Normalized phone identifier - ' . $customer_identifier);
        } else {
            // Default to email
            $customer_identifier = sanitize_email($_POST['billing_email'] ?? '');
            error_log('Order Limiter: Email identifier - ' . $customer_identifier);
            if (empty($customer_identifier)) {
                error_log('Order Limiter: Email identifier is empty');
                return;
            }
        }
        
        $time_period_seconds = $this->convert_to_seconds($settings['time_value'], $settings['time_unit']);
        error_log('Order Limiter: Time period seconds - ' . $time_period_seconds);
        
        // Get customer orders within time period
        $orders = $this->get_customer_orders($customer_identifier, $time_period_seconds, $method);
        $order_count = count($orders);
        
        error_log('Order Limiter: Found ' . $order_count . ' orders for identifier: ' . $customer_identifier);
        error_log('Order Limiter: Order limit is ' . $settings['count']);
        
        // Check if limit is exceeded
        if ($order_count >= $settings['count']) {
            error_log('Order Limiter: Limit exceeded! Adding error notice.');
            $remaining_seconds = $this->calculate_remaining_time($orders, $time_period_seconds);
            $remaining_time = $this->format_time($remaining_seconds, $settings['time_unit']);
            
            // Get custom error message
            $error_message = get_option('sohoj_order_limit_error_message', 'You have already placed {{ count }} orders in the last {{ period }} {{ unit }}. Please wait {{ remaining }} {{ remaining_unit }} before placing another order.');
            
            // Prepare replacement data
            $replacement_data = array(
                'count' => $order_count,
                'limit' => $settings['count'],
                'period' => $settings['time_value'],
                'unit' => $settings['time_unit'],
                'remaining_value' => $remaining_time['value'],
                'remaining_unit' => $remaining_time['unit']
            );
            
            // Replace placeholders and add error
            $processed_message = $this->replace_placeholders($error_message, $replacement_data);
            error_log('Order Limiter: Error message - ' . $processed_message);
            wc_add_notice($processed_message, 'error');
        } else {
            error_log('Order Limiter: Limit not exceeded, allowing order.');
        }
    }
    
    /**
     * Get formatted info message for checkout page
     * 
     * @return string|null Formatted info message or null if not needed
     */
    public function get_info_message() {
        if (!$this->is_order_limit_enabled()) {
            return null;
        }
        
        $settings = $this->get_order_limit_settings();
        $info_message = get_option('sohoj_order_limit_info_message', 'To ensure fair access, customers are limited to {{ limit }} orders per {{ period }} {{ unit }}.');
        
        $replacement_data = array(
            'count' => 0,
            'limit' => $settings['count'],
            'period' => $settings['time_value'],
            'unit' => $settings['time_unit'],
            'remaining_value' => 0,
            'remaining_unit' => $settings['time_unit']
        );
        
        return $this->replace_placeholders($info_message, $replacement_data);
    }
}