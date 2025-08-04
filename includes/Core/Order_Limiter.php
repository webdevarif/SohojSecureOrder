<?php
/**
 * Order Limiter
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

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
        // Hook into WooCommerce checkout if enabled
        if ($this->is_order_limit_enabled()) {
            add_action('woocommerce_checkout_process', array($this, 'validate_order_limit'));
        }
    }
    
    /**
     * Check if order limiting is enabled
     * 
     * @return bool
     */
    private function is_order_limit_enabled() {
        return get_option('sohoj_order_limit_enabled', 0) == 1;
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
            'time_unit' => get_option('sohoj_order_limit_time_unit', 'minutes')
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
     * Get customer orders within time period
     * 
     * @param string $customer_email Customer email
     * @param int $time_period_seconds Time period in seconds
     * @return array Order objects
     */
    private function get_customer_orders($customer_email, $time_period_seconds) {
        $time_ago = time() - $time_period_seconds;
        
        $orders = wc_get_orders(array(
            'billing_email' => $customer_email,
            'date_created' => '>=' . $time_ago,
            'status' => array('processing', 'completed', 'on-hold', 'pending'),
            'limit' => -1
        ));
        
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
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Get customer email
        $customer_email = sanitize_email($_POST['billing_email'] ?? '');
        if (empty($customer_email)) {
            return;
        }
        
        $settings = $this->get_order_limit_settings();
        $time_period_seconds = $this->convert_to_seconds($settings['time_value'], $settings['time_unit']);
        
        // Get customer orders within time period
        $orders = $this->get_customer_orders($customer_email, $time_period_seconds);
        $order_count = count($orders);
        
        // Check if limit is exceeded
        if ($order_count >= $settings['count']) {
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
            wc_add_notice($processed_message, 'error');
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