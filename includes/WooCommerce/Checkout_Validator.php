<?php
/**
 * WooCommerce Checkout Validator
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\WooCommerce;

use SohojSecureOrder\Core\Phone_Validator;
use SohojSecureOrder\Core\Order_Limiter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Checkout Validation Handler
 */
class Checkout_Validator {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize checkout validation
     */
    private function init() {
        error_log('Sohoj Checkout: Initializing Checkout_Validator');
        
        // Hook into checkout validation (always hook, check settings inside)
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_phone'));
        error_log('Sohoj Checkout: Added woocommerce_checkout_process hook');
        
        // Add custom validation message styling
        add_action('wp_head', array($this, 'add_validation_styles'));
        
        // Add validation info to checkout page
        add_action('woocommerce_before_checkout_billing_form', array($this, 'add_phone_validation_info'));
        
        // Add order limit info to checkout page (if enabled in settings)
        add_action('woocommerce_before_checkout_billing_form', array($this, 'add_order_limit_info'));
        
        // Add debug logging
        add_action('init', array($this, 'debug_log_settings'));
        
        error_log('Sohoj Checkout: All hooks registered successfully');
    }
    
    /**
     * Check if WooCommerce is active
     * 
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * Check if phone validation is enabled in settings
     * 
     * @return bool
     */
    private function is_phone_validation_enabled() {
        return get_option('sohoj_phone_validation_enabled', 0) == 1;
    }
    
    /**
     * Validate phone number during checkout
     */
    public function validate_checkout_phone() {
        error_log('Sohoj Checkout: validate_checkout_phone() called');
        
        // Check if validation is enabled
        $wc_active = $this->is_woocommerce_active();
        $validation_enabled = $this->is_phone_validation_enabled();
        
        error_log('Sohoj Checkout: WC Active: ' . ($wc_active ? 'Yes' : 'No') . ', Validation Enabled: ' . ($validation_enabled ? 'Yes' : 'No'));
        
        if (!$wc_active || !$validation_enabled) {
            error_log('Sohoj Checkout: Validation skipped - WC or validation not enabled');
            return;
        }
        
        $this->validate_phone_checkout();
    }
    
    /**
     * Validate phone number during checkout process
     */
    public function validate_phone_checkout() {
        error_log('Sohoj Checkout: validate_phone_checkout() called');
        error_log('Sohoj Checkout: $_POST data: ' . print_r($_POST, true));
        
        if (empty($_POST['billing_phone'])) {
            error_log('Sohoj Checkout: billing_phone is empty in $_POST');
            return;
        }
        
        $phone = sanitize_text_field($_POST['billing_phone']);
        error_log('Sohoj Checkout: Validating phone: ' . $phone);
        
        $validation = Phone_Validator::validate_phone_for_checkout($phone);
        error_log('Sohoj Checkout: Validation result: ' . print_r($validation, true));
        
        if (!$validation['valid']) {
            error_log('Sohoj Checkout: Adding error notice: ' . $validation['message']);
            wc_add_notice($validation['message'], 'error');
        } else {
            error_log('Sohoj Checkout: Phone validation passed');
        }
    }
    
    /**
     * Add custom styles for validation messages
     */
    public function add_validation_styles() {
        if (!is_checkout()) {
            return;
        }
        ?>
        <style type="text/css">
        /* WooCommerce Error Messages - Professional Design */
        .woocommerce-error {
            background-color: #fff2f2 !important;
            border: 1px solid #fecaca !important;
            border-left: 4px solid #dc2626 !important;
            color: #7f1d1d !important;
            padding: 16px 20px !important;
            margin-bottom: 20px !important;
            border-radius: 6px !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1) !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        
        .woocommerce-error::before {
            content: "<?php echo esc_js(get_option('sohoj_error_icon', '⚠️')); ?>" !important;
            font-size: 18px !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
            display: block !important;
        }
        
        .woocommerce-error li {
            margin: 0 !important;
        }
        
        .woocommerce-error ul {
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
        }
        
        /* Success Messages */
        .woocommerce-message {
            background-color: #f0fdf4 !important;
            border: 1px solid #bbf7d0 !important;
            border-left: 4px solid #16a34a !important;
            color: #14532d !important;
            padding: 16px 20px !important;
            margin-bottom: 20px !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        
        .woocommerce-message::before {
            content: "✅" !important;
            font-size: 18px !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
        }
        
        /* Info Messages */
        .woocommerce-info {
            background-color: #eff6ff !important;
            border: 1px solid #bfdbfe !important;
            border-left: 4px solid #2563eb !important;
            color: #1e3a8a !important;
            padding: 16px 20px !important;
            margin-bottom: 20px !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        
        .woocommerce-info::before {
            content: "ℹ️" !important;
            font-size: 18px !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
        }
        
        /* Input field error styling */
        .woocommerce .form-field input.input-error,
        .woocommerce .form-field select.input-error,
        .woocommerce .form-field textarea.input-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }
        
        /* Focus state for error inputs */
        .woocommerce .form-field input.input-error:focus,
        .woocommerce .form-field select.input-error:focus,
        .woocommerce .form-field textarea.input-error:focus {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2) !important;
            outline: none !important;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .woocommerce-error,
            .woocommerce-message,
            .woocommerce-info {
                padding: 12px 16px !important;
                font-size: 13px !important;
                gap: 8px !important;
            }
            
            .woocommerce-error::before,
            .woocommerce-message::before,
            .woocommerce-info::before {
                font-size: 16px !important;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Add phone validation info to checkout page
     */
    public function add_phone_validation_info() {
        if (!$this->is_woocommerce_active() || !$this->is_phone_validation_enabled()) {
            return;
        }
        
        // Check if showing phone info is enabled
        $show_phone_info = get_option('sohoj_show_phone_info', 1) == 1;
        if (!$show_phone_info) {
            return;
        }
        
        // Get custom info settings
        $info_icon = get_option('sohoj_info_icon', '📱');
        $info_heading = get_option('sohoj_info_heading', 'Phone Number Format');
        $info_message = get_option('sohoj_info_message', 'Please enter a valid Bangladeshi phone number (e.g., +880 1712345678 or 01712345678)');
        ?>
        <div class="sohoj-phone-info" style="
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #2563eb;
            color: #1e3a8a;
            padding: 16px 20px;
            margin: 16px 0;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
        ">
            <span style="font-size: 18px; line-height: 1; flex-shrink: 0;"><?php echo wp_kses_post($info_icon); ?></span>
            <div>
                <strong style="display: block; margin-bottom: 4px;"><?php echo esc_html($info_heading); ?>:</strong>
                <span><?php echo esc_html($info_message); ?></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add order limit info to checkout page (if enabled in settings)
     */
    public function add_order_limit_info() {
        if (!$this->is_woocommerce_active()) {
            return;
        }
        
        // Check if order limit is enabled
        $order_limit_enabled = get_option('sohoj_order_limit_enabled', 0) == 1;
        if (!$order_limit_enabled) {
            return;
        }
        
        // Check if showing order limit info is enabled
        $show_order_limit_info = get_option('sohoj_show_order_limit_info', 0) == 1;
        if (!$show_order_limit_info) {
            return;
        }
        
        // Get order limiter instance and info message
        $order_limiter = new Order_Limiter();
        $info_message = $order_limiter->get_info_message();
        
        if (empty($info_message)) {
            return;
        }
        
        // Get custom info settings
        $info_icon = get_option('sohoj_order_limit_info_icon', '⏰');
        $info_heading = get_option('sohoj_order_limit_info_heading', 'Order Limit Policy');
        ?>
        <div class="sohoj-order-limit-info" style="
            background-color: #fffbeb;
            border: 1px solid #fed7aa;
            border-left: 4px solid #f59e0b;
            color: #b45309;
            padding: 16px 20px;
            margin: 16px 0;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.1);
        ">
            <span style="font-size: 18px; line-height: 1; flex-shrink: 0;"><?php echo wp_kses_post($info_icon); ?></span>
            <div>
                <strong style="display: block; margin-bottom: 4px;"><?php echo esc_html($info_heading); ?>:</strong>
                <span><?php echo esc_html($info_message); ?></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Debug logging for settings
     */
    public function debug_log_settings() {
        if (is_admin()) {
            $wc_active = $this->is_woocommerce_active();
            $validation_enabled = $this->is_phone_validation_enabled();
            error_log('Sohoj Phone Validation Debug: WooCommerce active: ' . ($wc_active ? 'Yes' : 'No') . ', Validation enabled: ' . ($validation_enabled ? 'Yes' : 'No'));
        }
    }
}