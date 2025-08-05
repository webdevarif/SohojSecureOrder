<?php
/**
 * Public Frontend Handler
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Public;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public Frontend Handler Class
 */
class Public_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize public functionality
     */
    private function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add shortcodes
        add_shortcode('sohoj_example_form', array($this, 'example_form_shortcode'));
        
        // Add AJAX handlers
        add_action('wp_ajax_sohoj_example_action', array($this, 'example_action'));
        add_action('wp_ajax_nopriv_sohoj_example_action', array($this, 'example_action'));
        add_action('wp_ajax_sohoj_refresh_fraud_check', array($this, 'refresh_fraud_check'));
        
        // WooCommerce integration
        add_action('woocommerce_order_details_after_customer_details', array($this, 'display_fraud_check_info'), 10, 1);
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_admin_fraud_check_info'), 10, 1);
        
        // Add fraud check data to order meta
        add_action('woocommerce_checkout_create_order', array($this, 'add_fraud_check_data_to_order'), 10, 2);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('sohoj-public', SOHOJ_PLUGIN_URL . 'assets/js/public.js', array('jquery'), SOHOJ_PLUGIN_VERSION, true);
        wp_enqueue_style('sohoj-public', SOHOJ_PLUGIN_URL . 'assets/css/public.css', array(), SOHOJ_PLUGIN_VERSION);
        
        wp_localize_script('sohoj-public', 'sohoj_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sohoj_example_nonce')
        ));
    }
    
    /**
     * Display fraud check information on order details page
     */
    public function display_fraud_check_info($order) {
        if (!$order) return;
        
        $fraud_enabled = get_option('sohoj_fraud_check_enabled', 'disabled');
        if ($fraud_enabled !== 'enabled') return;
        
        $fraud_data = $this->get_fraud_check_data($order);
        if (!$fraud_data) return;
        
        ?>
        <div class="sohoj-fraud-check-section" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">
                🛡️ Fraud Check Information
            </h3>
            
            <div class="fraud-check-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                
                <!-- Risk Level -->
                <div class="fraud-risk-level" style="padding: 12px; border-radius: 6px; text-align: center; font-weight: bold;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Risk Level</div>
                    <div class="risk-badge risk-<?php echo esc_attr($fraud_data['risk_level']); ?>" 
                         style="padding: 6px 12px; border-radius: 20px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <?php echo esc_html(ucfirst($fraud_data['risk_level'])); ?>
                    </div>
                </div>
                
                <!-- Phone Validation -->
                <div class="fraud-phone-validation" style="padding: 12px; background: white; border-radius: 6px; border: 1px solid #e9ecef;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Phone Validation</div>
                    <div style="font-size: 14px; font-weight: 500; color: #333;">
                        <?php echo $fraud_data['phone_valid'] ? '✅ Valid' : '❌ Invalid'; ?>
                    </div>
                </div>
                
                <!-- Previous Orders -->
                <div class="fraud-previous-orders" style="padding: 12px; background: white; border-radius: 6px; border: 1px solid #e9ecef;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Previous Orders</div>
                    <div style="font-size: 14px; font-weight: 500; color: #333;">
                        <?php echo esc_html($fraud_data['previous_orders']); ?> orders
                    </div>
                </div>
                
                <!-- IP Location -->
                <div class="fraud-ip-location" style="padding: 12px; background: white; border-radius: 6px; border: 1px solid #e9ecef;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">IP Location</div>
                    <div style="font-size: 14px; font-weight: 500; color: #333;">
                        <?php echo esc_html($fraud_data['ip_location']); ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Detailed Information -->
            <div class="fraud-details" style="margin-top: 15px; padding: 15px; background: white; border-radius: 6px; border: 1px solid #e9ecef;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Risk Analysis</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #666;">
                    <?php foreach ($fraud_data['risk_factors'] as $factor): ?>
                        <li style="margin-bottom: 5px;"><?php echo esc_html($factor); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Checked At -->
            <div style="margin-top: 10px; text-align: right; font-size: 11px; color: #999;">
                Checked at: <?php echo esc_html($fraud_data['checked_at']); ?>
            </div>
        </div>
        
        <style>
            .risk-low { background: #d4edda; color: #155724; }
            .risk-medium { background: #fff3cd; color: #856404; }
            .risk-high { background: #f8d7da; color: #721c24; }
        </style>
        <?php
    }
    
    /**
     * Display fraud check information in admin order details
     */
    public function display_admin_fraud_check_info($order) {
        // Debug logging
        error_log('Sohoj Fraud Check: display_admin_fraud_check_info called');
        
        if (!$order) {
            error_log('Sohoj Fraud Check: No order object provided');
            return;
        }
        
        error_log('Sohoj Fraud Check: Order ID = ' . $order->get_id());
        error_log('Sohoj Fraud Check: Billing Phone = ' . $order->get_billing_phone());
        error_log('Sohoj Fraud Check: Billing Email = ' . $order->get_billing_email());
        
        $fraud_enabled = get_option('sohoj_fraud_check_enabled', 0);
        error_log('Sohoj Fraud Check: fraud_enabled = ' . $fraud_enabled);
        
        if ($fraud_enabled != 1) {
            error_log('Sohoj Fraud Check: Fraud check is disabled');
            return;
        }
        
        $fraud_data = $this->get_fraud_check_data($order);
        error_log('Sohoj Fraud Check: fraud_data = ' . print_r($fraud_data, true));
        
        if (!$fraud_data) {
            error_log('Sohoj Fraud Check: No fraud data available');
            return;
        }
        
        ?>
        <div class="sohoj-admin-fraud-check" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="margin: 0; color: #333; font-size: 14px;">
                    🛡️ Fraud Check Results
                </h4>
                <button type="button" 
                        class="button button-small" 
                        onclick="sohoj_refresh_fraud_check(<?php echo $order->get_id(); ?>)"
                        style="padding: 4px 8px; font-size: 11px; height: auto; line-height: 1.2;">
                    🔄 Refresh
                </button>
            </div>
            
            <table class="widefat" style="margin-top: 10px;">
                <tr>
                    <td style="width: 120px; font-weight: 500;">Risk Level:</td>
                    <td>
                        <span class="risk-badge risk-<?php echo esc_attr($fraud_data['risk_level']); ?>" 
                              style="padding: 4px 8px; border-radius: 12px; font-size: 11px; text-transform: uppercase;">
                            <?php echo esc_html(ucfirst($fraud_data['risk_level'])); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 500;">Phone Valid:</td>
                    <td><?php echo $fraud_data['phone_valid'] ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 500;">Previous Orders:</td>
                    <td><?php echo esc_html($fraud_data['previous_orders']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 500;">IP Location:</td>
                    <td><?php echo esc_html($fraud_data['ip_location']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 500;">Checked At:</td>
                    <td><?php echo esc_html($fraud_data['checked_at']); ?></td>
                </tr>
            </table>
            
            <!-- Risk Factors -->
            <?php if (!empty($fraud_data['risk_factors'])): ?>
            <div style="margin-top: 15px;">
                <h5 style="margin: 0 0 8px 0; color: #333; font-size: 12px;">Risk Factors:</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 11px; color: #666;">
                    <?php foreach ($fraud_data['risk_factors'] as $factor): ?>
                        <li style="margin-bottom: 3px;"><?php echo esc_html($factor); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .risk-low { background: #d4edda; color: #155724; }
            .risk-medium { background: #fff3cd; color: #856404; }
            .risk-high { background: #f8d7da; color: #721c24; }
        </style>
        
        <script>
        function sohoj_refresh_fraud_check(orderId) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '⏳ Loading...';
            button.disabled = true;
            
            // Make AJAX request
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sohoj_refresh_fraud_check',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('sohoj_refresh_fraud_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        alert('Error refreshing fraud check: ' + response.data);
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                },
                error: function() {
                    alert('Error refreshing fraud check. Please try again.');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Add JavaScript for fraud check refresh
     */
    public function add_fraud_check_scripts() {
        ?>
        <script>
        function sohoj_refresh_fraud_check(orderId) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '⏳ Loading...';
            button.disabled = true;
            
            // Make AJAX request
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sohoj_refresh_fraud_check',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('sohoj_refresh_fraud_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        alert('Error refreshing fraud check: ' + response.data);
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                },
                error: function() {
                    alert('Error refreshing fraud check. Please try again.');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Add fraud check data to order during checkout
     */
    public function add_fraud_check_data_to_order($order, $data) {
        $fraud_enabled = get_option('sohoj_fraud_check_enabled', 0);
        if ($fraud_enabled != 1) return;
        
        $fraud_data = $this->perform_fraud_check($data);
        if ($fraud_data) {
            $order->update_meta_data('_sohoj_fraud_check_data', $fraud_data);
        }
    }
    
    /**
     * Get fraud check data for an order
     */
    private function get_fraud_check_data($order) {
        error_log('Sohoj Fraud Check: get_fraud_check_data called for order ' . $order->get_id());
        
        $fraud_data = $order->get_meta('_sohoj_fraud_check_data');
        error_log('Sohoj Fraud Check: Existing fraud data = ' . print_r($fraud_data, true));
        
        if (!$fraud_data) {
            error_log('Sohoj Fraud Check: No existing fraud data, performing new check');
            // Perform fraud check if not already done
            $billing_phone = $order->get_billing_phone();
            $billing_email = $order->get_billing_email();
            
            error_log('Sohoj Fraud Check: Performing fraud check with phone = ' . $billing_phone . ', email = ' . $billing_email);
            
            $fraud_data = $this->perform_fraud_check(array(
                'billing_phone' => $billing_phone,
                'billing_email' => $billing_email
            ));
            
            error_log('Sohoj Fraud Check: New fraud data = ' . print_r($fraud_data, true));
            
            if ($fraud_data) {
                $order->update_meta_data('_sohoj_fraud_check_data', $fraud_data);
                $order->save();
                error_log('Sohoj Fraud Check: Fraud data saved to order');
            } else {
                error_log('Sohoj Fraud Check: Failed to generate fraud data');
            }
        }
        
        return $fraud_data;
    }
    
    /**
     * Perform fraud check on customer data
     */
    private function perform_fraud_check($data) {
        error_log('Sohoj Fraud Check: perform_fraud_check called with data = ' . print_r($data, true));
        
        $phone = isset($data['billing_phone']) ? $data['billing_phone'] : '';
        $email = isset($data['billing_email']) ? $data['billing_email'] : '';
        
        error_log('Sohoj Fraud Check: Raw phone = ' . $phone . ', email = ' . $email);
        
        if (empty($phone)) {
            error_log('Sohoj Fraud Check: Phone is empty, returning false');
            return false;
        }
        
        // Clean phone number
        $phone = $this->clean_phone_number($phone);
        error_log('Sohoj Fraud Check: Cleaned phone = ' . $phone);
        
        // Get customer IP
        $ip = $this->get_client_ip();
        error_log('Sohoj Fraud Check: Client IP = ' . $ip);
        
        // Perform various checks
        $phone_valid = $this->validate_phone($phone);
        error_log('Sohoj Fraud Check: Phone valid = ' . ($phone_valid ? 'true' : 'false'));
        
        $previous_orders = $this->get_previous_orders_count($phone);
        error_log('Sohoj Fraud Check: Previous orders = ' . $previous_orders);
        
        $ip_location = $this->get_ip_location($ip);
        error_log('Sohoj Fraud Check: IP location = ' . $ip_location);
        
        $risk_factors = $this->analyze_risk_factors($phone, $email, $ip);
        error_log('Sohoj Fraud Check: Risk factors = ' . print_r($risk_factors, true));
        
        $risk_level = $this->calculate_risk_level($risk_factors);
        error_log('Sohoj Fraud Check: Risk level = ' . $risk_level);
        
        $fraud_data = array(
            'phone' => $phone,
            'phone_valid' => $phone_valid,
            'previous_orders' => $previous_orders,
            'ip' => $ip,
            'ip_location' => $ip_location,
            'risk_factors' => $risk_factors,
            'risk_level' => $risk_level,
            'checked_at' => current_time('Y-m-d H:i:s')
        );
        
        error_log('Sohoj Fraud Check: Final fraud data = ' . print_r($fraud_data, true));
        return $fraud_data;
    }
    
    /**
     * Clean phone number
     */
    private function clean_phone_number($phone) {
        error_log('Sohoj Phone Clean: Original phone: ' . $phone);
        
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        error_log('Sohoj Phone Clean: After cleaning: ' . $phone);
        
        // Handle Bangladeshi numbers
        if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
            error_log('Sohoj Phone Clean: Valid 11-digit format, returning: ' . $phone);
            return $phone;
        }
        
        if (strlen($phone) === 13 && substr($phone, 0, 4) === '8801') {
            $cleaned = substr($phone, 2); // Remove 880 prefix
            error_log('Sohoj Phone Clean: 13-digit format, removing 880 prefix: ' . $cleaned);
            return $cleaned;
        }
        
        error_log('Sohoj Phone Clean: No valid format found, returning original: ' . $phone);
        return $phone;
    }
    
    /**
     * Validate phone number
     */
    private function validate_phone($phone) {
        // Debug logging
        error_log('Sohoj Phone Validation: Checking phone: ' . $phone);
        error_log('Sohoj Phone Validation: Length: ' . strlen($phone));
        
        // Check if it's a valid Bangladeshi mobile number
        if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
            $operator_codes = array('11', '13', '14', '15', '16', '17', '18', '19');
            $operator = substr($phone, 2, 2);
            error_log('Sohoj Phone Validation: Operator code: ' . $operator);
            error_log('Sohoj Phone Validation: Valid operators: ' . implode(', ', $operator_codes));
            $is_valid = in_array($operator, $operator_codes);
            error_log('Sohoj Phone Validation: Is valid: ' . ($is_valid ? 'true' : 'false'));
            return $is_valid;
        }
        
        error_log('Sohoj Phone Validation: Failed length or prefix check');
        return false;
    }
    
    /**
     * Get previous orders count for phone number
     */
    private function get_previous_orders_count($phone) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_billing_phone' 
             AND pm.meta_value = %s 
             AND p.post_type = 'shop_order' 
             AND p.post_status IN ('wc-completed', 'wc-processing')",
            $phone
        ));
        
        return intval($count);
    }
    
    /**
     * Get IP location
     */
    private function get_ip_location($ip) {
        // Simple IP location detection (you can integrate with a real IP geolocation service)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'International';
        } else {
            return 'Local';
        }
    }
    
    /**
     * Analyze risk factors
     */
    private function analyze_risk_factors($phone, $email, $ip) {
        $factors = array();
        
        // Phone validation
        if (!$this->validate_phone($phone)) {
            $factors[] = 'Invalid phone number format';
        }
        
        // Previous orders
        $previous_orders = $this->get_previous_orders_count($phone);
        if ($previous_orders === 0) {
            $factors[] = 'First-time customer';
        } elseif ($previous_orders > 5) {
            $factors[] = 'Frequent customer (' . $previous_orders . ' previous orders)';
        }
        
        // IP location
        $ip_location = $this->get_ip_location($ip);
        if ($ip_location === 'International') {
            $factors[] = 'International IP address';
        }
        
        // Email domain check
        if (!empty($email)) {
            $domain = substr(strrchr($email, '@'), 1);
            if (in_array($domain, array('tempmail.com', '10minutemail.com', 'guerrillamail.com'))) {
                $factors[] = 'Temporary email address detected';
            }
        }
        
        return $factors;
    }
    
    /**
     * Calculate risk level
     */
    private function calculate_risk_level($risk_factors) {
        $high_risk_indicators = array(
            'Invalid phone number format',
            'Temporary email address detected'
        );
        
        $medium_risk_indicators = array(
            'First-time customer',
            'International IP address'
        );
        
        $high_risk_count = 0;
        $medium_risk_count = 0;
        
        foreach ($risk_factors as $factor) {
            if (in_array($factor, $high_risk_indicators)) {
                $high_risk_count++;
            } elseif (in_array($factor, $medium_risk_indicators)) {
                $medium_risk_count++;
            }
        }
        
        if ($high_risk_count > 0) {
            return 'high';
        } elseif ($medium_risk_count > 0) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
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
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Example form shortcode
     */
    public function example_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Example Form'
        ), $atts);
        
        ob_start();
        ?>
        <div class="sohoj-form">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form id="sohoj-example-form">
                <?php wp_nonce_field('sohoj_example_nonce', 'sohoj_nonce'); ?>
                <p>
                    <label for="example_field">Example Field:</label>
                    <input type="text" id="example_field" name="example_field" required />
                </p>
                <button type="submit" class="button button-primary">Submit</button>
            </form>
            <div id="sohoj-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Refresh fraud check AJAX action
     */
    public function refresh_fraud_check() {
        check_ajax_referer('sohoj_refresh_fraud_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error('Order not found');
        }
        
        // Clear existing fraud check data
        $order->delete_meta_data('_sohoj_fraud_check_data');
        
        // Perform new fraud check
        $billing_phone = $order->get_billing_phone();
        $billing_email = $order->get_billing_email();
        
        $fraud_data = $this->perform_fraud_check(array(
            'billing_phone' => $billing_phone,
            'billing_email' => $billing_email
        ));
        
        if ($fraud_data) {
            $order->update_meta_data('_sohoj_fraud_check_data', $fraud_data);
            $order->save();
            wp_send_json_success('Fraud check refreshed successfully');
        } else {
            wp_send_json_error('Failed to perform fraud check');
        }
    }
    
    /**
     * Example AJAX action
     */
    public function example_action() {
        check_ajax_referer('sohoj_example_nonce', 'nonce');
        
        $example_field = sanitize_text_field($_POST['example_field']);
        
        // Basic validation
        if (empty($example_field)) {
            wp_send_json_error('Field is required');
        }
        
        // Process the data
        $result = $this->process_example_data($example_field);
        
        wp_send_json_success('Data processed successfully!');
    }
    
    /**
     * Process example data
     */
    private function process_example_data($data) {
        // Process the example data
        return 'Processed: ' . sanitize_text_field($data);
    }
} 