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
        
        // Try multiple WooCommerce hooks for product details
        add_action('woocommerce_after_checkout_billing_form', array($this, 'add_single_product_details'), 10);
        add_action('woocommerce_checkout_before_order_review', array($this, 'add_single_product_details'), 5);
        add_action('woocommerce_checkout_after_customer_details', array($this, 'add_single_product_details'), 10);
        add_action('woocommerce_review_order_before_cart_contents', array($this, 'add_single_product_details'), 5);
        
        // Add JavaScript injection as fallback
        add_action('wp_footer', array($this, 'inject_product_details_js'));
        
        // Add simple test hook to verify WooCommerce hooks are working
        add_action('woocommerce_before_checkout_form', array($this, 'test_checkout_hook'));
        error_log('Sohoj Checkout: Registered multiple product details hooks');
        
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
     * Add single product details below billing address
     */
    public function add_single_product_details() {
        static $displayed = false;
        
        if ($displayed) {
            return; // Prevent multiple displays
        }
        
        error_log('Sohoj Checkout: add_single_product_details() called');
        
        if (!$this->is_woocommerce_active()) {
            error_log('Sohoj Checkout: WooCommerce not active');
            return;
        }
        
        // Get cart contents
        $cart = WC()->cart;
        if (!$cart) {
            error_log('Sohoj Checkout: No cart object');
            return;
        }
        
        if ($cart->is_empty()) {
            error_log('Sohoj Checkout: Cart is empty');
            return;
        }
        
        $cart_items = $cart->get_cart();
        $cart_count = count($cart_items);
        
        error_log('Sohoj Checkout: Cart has ' . $cart_count . ' items');
        
        // Only show for single product orders
        if ($cart_count !== 1) {
            if ($cart_count > 1) {
                error_log('Sohoj Checkout: Displaying multiple products summary');
                $this->display_multiple_products_summary($cart_items);
                $displayed = true;
            } else {
                error_log('Sohoj Checkout: No items to display');
            }
            return;
        }
        
        // Get the single product
        $cart_item = reset($cart_items);
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];
        
        if (!$product) {
            error_log('Sohoj Checkout: No product found in cart item');
            return;
        }
        
        error_log('Sohoj Checkout: Displaying single product details for: ' . $product->get_name());
        
        // Add a simple test output first
        echo '<div style="background: red; color: white; padding: 10px; margin: 10px 0;">SOHOJ DEBUG: Product details should appear here for: ' . esc_html($product->get_name()) . '</div>';
        
        $this->display_single_product_details($product, $cart_item, $quantity);
        $displayed = true;
    }
    
    /**
     * Display single product details
     */
    private function display_single_product_details($product, $cart_item, $quantity) {
        error_log('Sohoj Checkout: display_single_product_details() called for: ' . $product->get_name());
        $product_name = $product->get_name();
        $product_price = $product->get_price();
        $product_image = $product->get_image('thumbnail');
        $product_permalink = $product->get_permalink();
        $product_sku = $product->get_sku();
        $product_weight = $product->get_weight();
        $product_dimensions = $product->get_dimensions(false);
        $line_total = $cart_item['line_total'];
        $line_subtotal = $cart_item['line_subtotal'];
        
        // Get product attributes/variations if applicable
        $variation_data = '';
        if ($product->is_type('variation')) {
            $attributes = $product->get_variation_attributes();
            if (!empty($attributes)) {
                $variation_parts = array();
                foreach ($attributes as $name => $value) {
                    $variation_parts[] = ucfirst(str_replace('attribute_', '', $name)) . ': ' . $value;
                }
                $variation_data = implode(', ', $variation_parts);
            }
        }
        
        ?>
        <div class="sohoj-single-product-details" style="
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        ">
            <h3 style="
                margin: 0 0 16px 0;
                color: #1e293b;
                font-size: 18px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
            ">
                <span style="font-size: 20px;">📦</span>
                Order Summary
            </h3>
            
            <div style="display: flex; gap: 16px; align-items: start;">
                <!-- Product Image -->
                <div style="flex-shrink: 0;">
                    <a href="<?php echo esc_url($product_permalink); ?>" target="_blank" style="display: block; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <?php echo $product_image; ?>
                    </a>
                </div>
                
                <!-- Product Details -->
                <div style="flex-grow: 1; min-width: 0;">
                    <div style="margin-bottom: 12px;">
                        <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600; color: #1e293b;">
                            <a href="<?php echo esc_url($product_permalink); ?>" target="_blank" style="color: #3b82f6; text-decoration: none;">
                                <?php echo esc_html($product_name); ?>
                            </a>
                        </h4>
                        
                        <?php if ($variation_data): ?>
                            <p style="margin: 0; font-size: 13px; color: #64748b;">
                                <?php echo esc_html($variation_data); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Info Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 12px;">
                        <div style="background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Quantity</span>
                            <span style="font-weight: 600; color: #1e293b;"><?php echo esc_html($quantity); ?></span>
                        </div>
                        
                        <div style="background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Unit Price</span>
                            <span style="font-weight: 600; color: #1e293b;"><?php echo wc_price($product_price); ?></span>
                        </div>
                        
                        <?php if ($product_sku): ?>
                        <div style="background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">SKU</span>
                            <span style="font-weight: 600; color: #1e293b;"><?php echo esc_html($product_sku); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product_weight): ?>
                        <div style="background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Weight</span>
                            <span style="font-weight: 600; color: #1e293b;"><?php echo esc_html($product_weight . ' ' . get_option('woocommerce_weight_unit')); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Total Price -->
                    <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #3b82f6; border-radius: 8px; padding: 12px; text-align: center;">
                        <span style="display: block; font-size: 12px; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Total Amount</span>
                        <span style="font-size: 20px; font-weight: 700; color: #1e40af;"><?php echo wc_price($line_total); ?></span>
                        <?php if ($line_subtotal != $line_total): ?>
                            <span style="display: block; font-size: 12px; color: #64748b; text-decoration: line-through; margin-top: 2px;">
                                Original: <?php echo wc_price($line_subtotal); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .sohoj-single-product-details img {
            max-width: 80px;
            height: auto;
            border-radius: 6px;
        }
        
        .sohoj-single-product-details a:hover {
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        
        @media (max-width: 768px) {
            .sohoj-single-product-details {
                padding: 16px !important;
            }
            
            .sohoj-single-product-details > div:first-of-type {
                flex-direction: column !important;
                gap: 12px !important;
            }
            
            .sohoj-single-product-details img {
                max-width: 60px !important;
            }
            
            .sohoj-single-product-details h3 {
                font-size: 16px !important;
            }
            
            .sohoj-single-product-details h4 {
                font-size: 14px !important;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Display multiple products summary
     */
    private function display_multiple_products_summary($cart_items) {
        $total_items = 0;
        $total_amount = 0;
        
        foreach ($cart_items as $cart_item) {
            $total_items += $cart_item['quantity'];
            $total_amount += $cart_item['line_total'];
        }
        
        ?>
        <div class="sohoj-multiple-products-summary" style="
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        ">
            <h3 style="
                margin: 0 0 12px 0;
                color: #92400e;
                font-size: 16px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
            ">
                <span style="font-size: 18px;">🛒</span>
                Order Summary (<?php echo count($cart_items); ?> Products)
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px;">
                <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 6px; text-align: center;">
                    <span style="display: block; font-size: 20px; font-weight: 700; color: #92400e;"><?php echo $total_items; ?></span>
                    <span style="font-size: 11px; color: #92400e; text-transform: uppercase;">Total Items</span>
                </div>
                
                <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 6px; text-align: center;">
                    <span style="display: block; font-size: 20px; font-weight: 700; color: #92400e;"><?php echo count($cart_items); ?></span>
                    <span style="font-size: 11px; color: #92400e; text-transform: uppercase;">Products</span>
                </div>
                
                <div style="background: rgba(255,255,255,0.7); padding: 12px; border-radius: 6px; text-align: center;">
                    <span style="display: block; font-size: 20px; font-weight: 700; color: #92400e;"><?php echo wc_price($total_amount); ?></span>
                    <span style="font-size: 11px; color: #92400e; text-transform: uppercase;">Subtotal</span>
                </div>
            </div>
            
            <p style="margin: 12px 0 0 0; font-size: 12px; color: #92400e; text-align: center; opacity: 0.8;">
                View full order details in the summary section →
            </p>
        </div>
        <?php
    }
    
    /**
     * Test checkout hook to verify WooCommerce integration
     */
    public function test_checkout_hook() {
        error_log('Sohoj Checkout: test_checkout_hook() called - WooCommerce hooks are working!');
        echo '<div style="background: green; color: white; padding: 10px; margin: 10px 0; border-radius: 5px;">✅ SOHOJ DEBUG: WooCommerce checkout hooks are working!</div>';
    }
    
    /**
     * Inject product details via JavaScript as fallback
     */
    public function inject_product_details_js() {
        if (!is_checkout() || !$this->is_woocommerce_active()) {
            return;
        }
        
        // Get cart contents
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return;
        }
        
        $cart_items = $cart->get_cart();
        $cart_count = count($cart_items);
        
        if ($cart_count !== 1) {
            return; // Only for single product orders
        }
        
        // Get the single product
        $cart_item = reset($cart_items);
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];
        
        if (!$product) {
            return;
        }
        
        // Generate the HTML
        ob_start();
        $this->display_single_product_details($product, $cart_item, $quantity);
        $product_html = ob_get_clean();
        
        // Escape the HTML for JavaScript
        $escaped_html = json_encode($product_html);
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Sohoj: Attempting to inject product details via JavaScript');
            
            var productHtml = <?php echo $escaped_html; ?>;
            var injected = false;
            
            // Try multiple selectors to find the right place to inject
            var selectors = [
                '#billing_address_1_field',
                '.woocommerce-billing-fields',
                '.woocommerce-billing-fields__field-wrapper',
                '[id*="billing"]',
                '.col-1',
                '.woocommerce-checkout-review-order',
                '.checkout_coupon'
            ];
            
            for (var i = 0; i < selectors.length; i++) {
                var $target = $(selectors[i]).last();
                if ($target.length > 0 && !injected) {
                    console.log('Sohoj: Found target element:', selectors[i]);
                    $target.after('<div id="sohoj-product-details-js">' + productHtml + '</div>');
                    injected = true;
                    break;
                }
            }
            
            if (!injected) {
                console.log('Sohoj: Could not find target element, appending to checkout form');
                $('.woocommerce-checkout').prepend('<div id="sohoj-product-details-js">' + productHtml + '</div>');
            }
            
            console.log('Sohoj: Product details injection completed');
        });
        </script>
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