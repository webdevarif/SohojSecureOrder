<?php
/**
 * Incomplete Orders Tracker
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Incomplete Orders Class
 */
class Incomplete_Orders {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sohoj_incomplete_orders';
        $this->init();
    }
    
    /**
     * Initialize incomplete orders functionality
     */
    private function init() {
        // Create database table
        $this->create_table();
        
        // Hook into checkout events  
        add_action('woocommerce_checkout_order_processed', array($this, 'mark_as_completed'));
        
        // Add frontend scripts for form observation
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX handlers for admin actions
        add_action('wp_ajax_sohoj_convert_incomplete_order', array($this, 'convert_to_order'));
        add_action('wp_ajax_sohoj_reject_incomplete_order', array($this, 'reject_incomplete_order'));
        add_action('wp_ajax_sohoj_mark_called', array($this, 'mark_as_called'));
        add_action('wp_ajax_sohoj_get_incomplete_order_details', array($this, 'get_order_details'));
        
        // AJAX handler for capturing incomplete checkout data from frontend
        add_action('wp_ajax_sohoj_capture_incomplete_data', array($this, 'ajax_capture_incomplete_data'));
        add_action('wp_ajax_nopriv_sohoj_capture_incomplete_data', array($this, 'ajax_capture_incomplete_data'));
        
        // AJAX handler for phone history
        add_action('wp_ajax_sohoj_get_phone_history', array($this, 'get_phone_history_ajax'));
        
        error_log('Incomplete Orders: System initialized');
    }
    
    /**
     * Create database table for incomplete orders
     */
    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            billing_first_name varchar(50) NOT NULL,
            billing_last_name varchar(50) NOT NULL,
            billing_address_1 text NOT NULL,
            billing_city varchar(50) NOT NULL,
            billing_state varchar(50) NOT NULL,
            billing_postcode varchar(20) NOT NULL,
            shipping_first_name varchar(50) DEFAULT '',
            shipping_last_name varchar(50) DEFAULT '',
            shipping_address_1 text DEFAULT '',
            shipping_city varchar(50) DEFAULT '',
            shipping_state varchar(50) DEFAULT '',
            shipping_postcode varchar(20) DEFAULT '',
            cart_data longtext NOT NULL,
            cart_total decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50) DEFAULT '',
            order_notes text DEFAULT '',
            status varchar(20) DEFAULT 'incomplete',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            called_at datetime DEFAULT NULL,
            converted_order_id int(11) DEFAULT NULL,
            converted_at datetime DEFAULT NULL,
            rejected_at datetime DEFAULT NULL,
            rejection_reason text DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY customer_email (customer_email),
            KEY customer_phone (customer_phone),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('Incomplete Orders: Database table created/updated');
    }
    
    /**
     * Enqueue frontend scripts for form observation
     */
    public function enqueue_frontend_scripts() {
        // Only load on checkout page
        if (!is_wc_endpoint_url('checkout') && !is_checkout()) {
            return;
        }
        
        // Check if incomplete orders feature is enabled
        if (get_option('sohoj_incomplete_orders_enabled', 0) != 1) {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Add inline script for form observation
        $tracking_fields = get_option('sohoj_incomplete_orders_tracking_fields', array());
        if (!empty($tracking_fields)) {
            $script = $this->get_form_observer_script($tracking_fields);
            wp_add_inline_script('jquery', $script);
        }
    }
    
    /**
     * Get form observer JavaScript
     */
    private function get_form_observer_script($tracking_fields) {
        // Map admin field names to actual form field names
        $field_mapping = array(
            'billing_first_name' => '#billing_first_name',
            'billing_last_name' => '#billing_last_name', 
            'billing_email' => '#billing_email',
            'billing_phone' => '#billing_phone',
            'billing_address_1' => '#billing_address_1'
        );
        
        $selectors = array();
        foreach ($tracking_fields as $field) {
            if (isset($field_mapping[$field])) {
                $selectors[] = $field_mapping[$field];
            }
        }
        
        $selectors_js = json_encode($selectors);
        
        return "
        jQuery(document).ready(function($) {
            var trackingFields = {$selectors_js};
            var debounceTimer = null;
            var lastCapturedData = '';
            
            // Function to capture form data
            function captureIncompleteData() {
                var formData = {};
                var hasData = false;
                
                // Get all checkout form data
                $('form.checkout input, form.checkout select, form.checkout textarea').each(function() {
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    if (name && value) {
                        formData[name] = value;
                        hasData = true;
                    }
                });
                
                if (!hasData) {
                    return;
                }
                
                // Check if any tracking fields have data
                var hasTrackingData = false;
                for (var i = 0; i < trackingFields.length; i++) {
                    var selector = trackingFields[i];
                    var fieldName = selector.replace('#', '');
                    if (formData[fieldName] && formData[fieldName].length > 0) {
                        hasTrackingData = true;
                        break;
                    }
                }
                
                if (!hasTrackingData) {
                    return;
                }
                
                // Don't send duplicate data
                var currentData = JSON.stringify(formData);
                if (currentData === lastCapturedData) {
                    return;
                }
                lastCapturedData = currentData;
                
                // Send AJAX request
                $.ajax({
                    url: '" . admin_url('admin-ajax.php') . "',
                    type: 'POST',
                    data: {
                        action: 'sohoj_capture_incomplete_data',
                        form_data: formData,
                        nonce: '" . wp_create_nonce('sohoj_incomplete_nonce') . "'
                    }
                });
            }
            
            // Observe form field changes with debounce
            function setupFieldObservers() {
                $(document).on('input blur change', trackingFields.join(', '), function() {
                    // Clear existing timer
                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                    }
                    
                    // Set new timer - capture data after 1 second of inactivity
                    debounceTimer = setTimeout(function() {
                        captureIncompleteData();
                    }, 1000);
                });
            }
            
            // Initialize observers
            setupFieldObservers();
            
            // Also observe when new elements are added (for dynamic checkout forms)
            var observer = new MutationObserver(function(mutations) {
                setupFieldObservers();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
        ";
    }
    
    /**
     * AJAX handler to capture incomplete checkout data
     */
    public function ajax_capture_incomplete_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sohoj_incomplete_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if incomplete orders feature is enabled
        if (get_option('sohoj_incomplete_orders_enabled', 0) != 1) {
            wp_send_json_error('Feature not enabled');
        }
        
        $form_data = $_POST['form_data'];
        if (empty($form_data)) {
            wp_send_json_error('No form data provided');
        }
        
        // Get configured tracking fields
        $tracking_fields = get_option('sohoj_incomplete_orders_tracking_fields', array());
        if (empty($tracking_fields)) {
            wp_send_json_error('No tracking fields configured');
        }
        
        // Check if any of the configured trigger fields have data
        $has_trigger_data = false;
        foreach ($tracking_fields as $field) {
            if (!empty($form_data[$field])) {
                $has_trigger_data = true;
                break;
            }
        }
        
        if (!$has_trigger_data) {
            wp_send_json_error('No trigger fields have data');
        }
        
        // Generate or get session ID
        $session_id = '';
        if (WC()->session) {
            $session_id = WC()->session->get_customer_id();
        }
        if (empty($session_id)) {
            $session_id = 'guest_' . uniqid();
            if (WC()->session) {
                WC()->session->set('sohoj_session_id', $session_id);
            }
        }
        
        // Get cart data
        $cart_data = array();
        $cart_total = 0;
        
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $cart_data[] = array(
                    'product_id' => $cart_item['product_id'],
                    'variation_id' => $cart_item['variation_id'],
                    'quantity' => $cart_item['quantity'],
                    'product_name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'line_total' => $cart_item['line_total']
                );
            }
            $cart_total = WC()->cart->get_total('raw');
        }
        
        // Save incomplete order
        $result = $this->save_incomplete_order($session_id, $form_data, $cart_data, $cart_total);
        
        if ($result) {
            wp_send_json_success('Incomplete order data captured');
        } else {
            wp_send_json_error('Failed to save incomplete order data');
        }
    }
    
    /**
     * Save incomplete order to database
     */
    private function save_incomplete_order($session_id, $checkout_data, $cart_data, $cart_total) {
        global $wpdb;
        
        $data = array(
            'session_id' => $session_id,
            'customer_email' => sanitize_email($checkout_data['billing_email'] ?? ''),
            'customer_phone' => sanitize_text_field($checkout_data['billing_phone'] ?? ''),
            'billing_first_name' => sanitize_text_field($checkout_data['billing_first_name'] ?? ''),
            'billing_last_name' => sanitize_text_field($checkout_data['billing_last_name'] ?? ''),
            'billing_address_1' => sanitize_textarea_field($checkout_data['billing_address_1'] ?? ''),
            'billing_city' => sanitize_text_field($checkout_data['billing_city'] ?? ''),
            'billing_state' => sanitize_text_field($checkout_data['billing_state'] ?? ''),
            'billing_postcode' => sanitize_text_field($checkout_data['billing_postcode'] ?? ''),
            'shipping_first_name' => sanitize_text_field($checkout_data['shipping_first_name'] ?? ''),
            'shipping_last_name' => sanitize_text_field($checkout_data['shipping_last_name'] ?? ''),
            'shipping_address_1' => sanitize_textarea_field($checkout_data['shipping_address_1'] ?? ''),
            'shipping_city' => sanitize_text_field($checkout_data['shipping_city'] ?? ''),
            'shipping_state' => sanitize_text_field($checkout_data['shipping_state'] ?? ''),
            'shipping_postcode' => sanitize_text_field($checkout_data['shipping_postcode'] ?? ''),
            'cart_data' => wp_json_encode($cart_data),
            'cart_total' => floatval($cart_total),
            'payment_method' => sanitize_text_field($checkout_data['payment_method'] ?? ''),
            'order_notes' => sanitize_textarea_field($checkout_data['order_comments'] ?? ''),
            'status' => 'incomplete'
        );
        
        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE session_id = %s",
            $session_id
        ));
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $this->table_name,
                $data,
                array('id' => $existing->id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $this->table_name,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Mark incomplete order as completed when order is placed
     */
    public function mark_as_completed($order_id) {
        if (!WC()->session) {
            return;
        }
        
        $session_id = WC()->session->get('sohoj_session_id');
        if (empty($session_id)) {
            $session_id = WC()->session->get_customer_id();
        }
        
        if (!empty($session_id)) {
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array(
                    'status' => 'completed',
                    'converted_order_id' => $order_id,
                    'converted_at' => current_time('mysql')
                ),
                array('session_id' => $session_id),
                array('%s', '%d', '%s'),
                array('%s')
            );
            
            error_log('Incomplete Orders: Marked as completed for session: ' . $session_id . ', Order ID: ' . $order_id);
        }
    }
    
    /**
     * Get incomplete orders with pagination and filtering
     */
    public function get_incomplete_orders($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'incomplete',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'date_filter' => '',
            'start_date' => '',
            'end_date' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($args['status'])) {
            $where_conditions[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where_conditions[] = "(customer_email LIKE %s OR customer_phone LIKE %s OR billing_first_name LIKE %s OR billing_last_name LIKE %s)";
            $search_term = '%' . $args['search'] . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Add date filtering
        if (!empty($args['date_filter'])) {
            if ($args['date_filter'] === 'custom' && !empty($args['start_date']) && !empty($args['end_date'])) {
                $where_conditions[] = "created_at BETWEEN %s AND %s";
                $where_values[] = $args['start_date'] . ' 00:00:00';
                $where_values[] = $args['end_date'] . ' 23:59:59';
            } else {
                $date_conditions = $this->get_date_conditions($args['date_filter']);
                $where_conditions[] = "created_at >= %s";
                $where_values[] = $date_conditions['start'];
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Get incomplete orders count
     */
    public function get_incomplete_orders_count($status = '', $search = '', $date_filter = '', $start_date = '', $end_date = '') {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($status)) {
            $where_conditions[] = "status = %s";
            $where_values[] = $status;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(customer_email LIKE %s OR customer_phone LIKE %s OR billing_first_name LIKE %s OR billing_last_name LIKE %s)";
            $search_term = '%' . $search . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Add date filtering
        if (!empty($date_filter)) {
            if ($date_filter === 'custom' && !empty($start_date) && !empty($end_date)) {
                $where_conditions[] = "created_at BETWEEN %s AND %s";
                $where_values[] = $start_date . ' 00:00:00';
                $where_values[] = $end_date . ' 23:59:59';
            } else {
                $date_conditions = $this->get_date_conditions($date_filter);
                $where_conditions[] = "created_at >= %s";
                $where_values[] = $date_conditions['start'];
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        
        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_var($sql);
        }
    }
    
    /**
     * Get statistics
     */
    public function get_statistics($period = 'today') {
        global $wpdb;
        
        $date_conditions = $this->get_date_conditions($period);
        
        $stats = array();
        
        // Incomplete orders count
        $stats['incomplete'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'incomplete' AND created_at >= %s",
            $date_conditions['start']
        ));
        
        // Converted orders count
        $stats['converted'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed' AND converted_at >= %s",
            $date_conditions['start']
        ));
        
        // Total converted value
        $stats['converted_value'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cart_total) FROM {$this->table_name} WHERE status = 'completed' AND converted_at >= %s",
            $date_conditions['start']
        )) ?: 0;
        
        // Rejection count
        $stats['rejected'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'rejected' AND rejected_at >= %s",
            $date_conditions['start']
        ));
        
        // Called count
        $stats['called'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE called_at IS NOT NULL AND called_at >= %s",
            $date_conditions['start']
        ));
        
        // Calculate conversion rate
        $total_processed = $stats['converted'] + $stats['rejected'];
        $stats['conversion_rate'] = $total_processed > 0 ? round(($stats['converted'] / $total_processed) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * Get date conditions for statistics
     */
    private function get_date_conditions($period) {
        $now = current_time('mysql');
        
        switch ($period) {
            case 'today':
                $start = date('Y-m-d 00:00:00');
                break;
            case 'week':
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'month':
                $start = date('Y-m-01 00:00:00');
                break;
            case 'year':
                $start = date('Y-01-01 00:00:00');
                break;
            case 'maximum':
                $start = '1970-01-01 00:00:00'; // Get all records
                break;
            default:
                $start = date('Y-m-d 00:00:00');
        }
        
        return array(
            'start' => $start,
            'end' => $now
        );
    }
    
    /**
     * Get custom statistics for date range
     */
    public function get_custom_statistics($start_date, $end_date) {
        global $wpdb;
        
        $start = $start_date . ' 00:00:00';
        $end = $end_date . ' 23:59:59';
        
        $stats = array();
        
        // Incomplete orders count
        $stats['incomplete'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'incomplete' AND created_at BETWEEN %s AND %s",
            $start, $end
        ));
        
        // Converted orders count
        $stats['converted'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed' AND converted_at BETWEEN %s AND %s",
            $start, $end
        ));
        
        // Total converted value
        $stats['converted_value'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cart_total) FROM {$this->table_name} WHERE status = 'completed' AND converted_at BETWEEN %s AND %s",
            $start, $end
        )) ?: 0;
        
        // Rejection count
        $stats['rejected'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'rejected' AND rejected_at BETWEEN %s AND %s",
            $start, $end
        ));
        
        // Called count
        $stats['called'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE called_at IS NOT NULL AND called_at BETWEEN %s AND %s",
            $start, $end
        ));
        
        // Calculate conversion rate
        $total_processed = $stats['converted'] + $stats['rejected'];
        $stats['conversion_rate'] = $total_processed > 0 ? round(($stats['converted'] / $total_processed) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * Convert incomplete order to WooCommerce order
     */
    public function convert_to_order() {
        check_ajax_referer('sohoj_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $incomplete_id = intval($_POST['id']);
        
        global $wpdb;
        $incomplete_order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $incomplete_id
        ));
        
        if (!$incomplete_order) {
            wp_send_json_error('Incomplete order not found');
        }
        
        // Create WooCommerce order
        $order = wc_create_order();
        
        // Set billing details
        $order->set_billing_first_name($incomplete_order->billing_first_name);
        $order->set_billing_last_name($incomplete_order->billing_last_name);
        $order->set_billing_email($incomplete_order->customer_email);
        $order->set_billing_phone($incomplete_order->customer_phone);
        $order->set_billing_address_1($incomplete_order->billing_address_1);
        $order->set_billing_city($incomplete_order->billing_city);
        $order->set_billing_state($incomplete_order->billing_state);
        $order->set_billing_postcode($incomplete_order->billing_postcode);
        
        // Set shipping details if available
        if (!empty($incomplete_order->shipping_first_name)) {
            $order->set_shipping_first_name($incomplete_order->shipping_first_name);
            $order->set_shipping_last_name($incomplete_order->shipping_last_name);
            $order->set_shipping_address_1($incomplete_order->shipping_address_1);
            $order->set_shipping_city($incomplete_order->shipping_city);
            $order->set_shipping_state($incomplete_order->shipping_state);
            $order->set_shipping_postcode($incomplete_order->shipping_postcode);
        }
        
        // Add products
        $cart_data = json_decode($incomplete_order->cart_data, true);
        if (!empty($cart_data)) {
            foreach ($cart_data as $item) {
                $product = wc_get_product($item['product_id']);
                if ($product) {
                    $order->add_product($product, $item['quantity']);
                }
            }
        }
        
        // Set payment method
        if (!empty($incomplete_order->payment_method)) {
            $order->set_payment_method($incomplete_order->payment_method);
        }
        
        // Add notes
        if (!empty($incomplete_order->order_notes)) {
            $order->set_customer_note($incomplete_order->order_notes);
        }
        
        // Calculate totals
        $order->calculate_totals();
        
        // Set status to pending
        $order->set_status('pending');
        
        // Save order
        $order->save();
        
        // Update incomplete order record
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'completed',
                'converted_order_id' => $order->get_id(),
                'converted_at' => current_time('mysql')
            ),
            array('id' => $incomplete_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => 'Order converted successfully',
            'order_id' => $order->get_id(),
            'order_url' => admin_url('post.php?post=' . $order->get_id() . '&action=edit')
        ));
    }
    
    /**
     * Reject incomplete order
     */
    public function reject_incomplete_order() {
        check_ajax_referer('sohoj_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $incomplete_id = intval($_POST['id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => 'rejected',
                'rejected_at' => current_time('mysql'),
                'rejection_reason' => $reason
            ),
            array('id' => $incomplete_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Order rejected successfully');
        } else {
            wp_send_json_error('Failed to reject order');
        }
    }
    
    /**
     * Mark as called
     */
    public function mark_as_called() {
        check_ajax_referer('sohoj_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $incomplete_id = intval($_POST['id']);
        
        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            array('called_at' => current_time('mysql')),
            array('id' => $incomplete_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Marked as called successfully');
        } else {
            wp_send_json_error('Failed to update record');
        }
    }
    
    /**
     * Get order details for modal
     */
    public function get_order_details() {
        check_ajax_referer('sohoj_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $incomplete_id = intval($_POST['id']);
        
        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $incomplete_id
        ));
        
        if (!$order) {
            wp_send_json_error('Order not found');
        }
        
        $cart_data = json_decode($order->cart_data, true);
        
        ob_start();
        ?>
        <div class="sohoj-order-details">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0 0 10px 0; color: #111827;">Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo esc_html($order->billing_first_name . ' ' . $order->billing_last_name); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($order->customer_email); ?></p>
                    <p><strong>Phone:</strong> <?php echo esc_html($order->customer_phone); ?></p>
                </div>
                
                <div>
                    <h3 style="margin: 0 0 10px 0; color: #111827;">Order Information</h3>
                    <p><strong>Total:</strong> $<?php echo number_format($order->cart_total, 2); ?></p>
                    <p><strong>Status:</strong> <span style="text-transform: capitalize;"><?php echo esc_html($order->status); ?></span></p>
                    <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($order->created_at)); ?></p>
                    <?php if ($order->called_at): ?>
                        <p><strong>Called:</strong> <?php echo date('M j, Y g:i A', strtotime($order->called_at)); ?></p>
                    <?php endif; ?>
                    <?php if ($order->converted_order_id): ?>
                        <p><strong>Converted Order:</strong> <a href="<?php echo admin_url('post.php?post=' . $order->converted_order_id . '&action=edit'); ?>" target="_blank">#<?php echo $order->converted_order_id; ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #111827;">Billing Address</h3>
                <p><?php echo esc_html($order->billing_address_1); ?><br>
                <?php echo esc_html($order->billing_city . ', ' . $order->billing_state . ' ' . $order->billing_postcode); ?></p>
            </div>
            
            <?php if (!empty($order->shipping_address_1)): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #111827;">Shipping Address</h3>
                <p><?php echo esc_html($order->shipping_first_name . ' ' . $order->shipping_last_name); ?><br>
                <?php echo esc_html($order->shipping_address_1); ?><br>
                <?php echo esc_html($order->shipping_city . ', ' . $order->shipping_state . ' ' . $order->shipping_postcode); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($cart_data)): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #111827;">Cart Items</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 8px; text-align: left; border: 1px solid #e5e7eb;">Product</th>
                            <th style="padding: 8px; text-align: center; border: 1px solid #e5e7eb; width: 80px;">Qty</th>
                            <th style="padding: 8px; text-align: right; border: 1px solid #e5e7eb; width: 100px;">Price</th>
                            <th style="padding: 8px; text-align: right; border: 1px solid #e5e7eb; width: 100px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_data as $item): ?>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #e5e7eb;">
                                <?php echo esc_html($item['product_name']); ?>
                                <?php if ($item['variation_id']): ?>
                                    <small>(Variation ID: <?php echo $item['variation_id']; ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px; text-align: center; border: 1px solid #e5e7eb;"><?php echo $item['quantity']; ?></td>
                            <td style="padding: 8px; text-align: right; border: 1px solid #e5e7eb;">$<?php echo number_format($item['price'], 2); ?></td>
                            <td style="padding: 8px; text-align: right; border: 1px solid #e5e7eb;">$<?php echo number_format($item['line_total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f9fafb; font-weight: bold;">
                            <td colspan="3" style="padding: 8px; border: 1px solid #e5e7eb; text-align: right;">Total:</td>
                            <td style="padding: 8px; text-align: right; border: 1px solid #e5e7eb;">$<?php echo number_format($order->cart_total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order->payment_method)): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #111827;">Payment Method</h3>
                <p><?php echo esc_html($order->payment_method); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order->order_notes)): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #111827;">Order Notes</h3>
                <p><?php echo esc_html($order->order_notes); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($order->status === 'rejected' && !empty($order->rejection_reason)): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #dc2626;">Rejection Reason</h3>
                <p><?php echo esc_html($order->rejection_reason); ?></p>
                <p><small>Rejected on: <?php echo date('M j, Y g:i A', strtotime($order->rejected_at)); ?></small></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        wp_send_json_success(ob_get_clean());
    }
    
    /**
     * Get phone history details for modal
     */
    public function get_phone_history_ajax() {
        check_ajax_referer('sohoj_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $phone = sanitize_text_field($_POST['phone']);
        
        if (empty($phone)) {
            wp_send_json_error('Phone number required');
        }
        
        global $wpdb;
        
        // Get all WooCommerce orders for this phone number
        $orders = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_status, p.post_date, pm1.meta_value as total, pm2.meta_value as currency,
                   pm3.meta_value as first_name, pm4.meta_value as last_name
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_order_total'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_order_currency'
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_billing_first_name'
            LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_billing_last_name'
            WHERE pm.meta_key = '_billing_phone' 
            AND pm.meta_value = %s
            AND p.post_type = 'shop_order'
            ORDER BY p.post_date DESC
        ", $phone));
        
        ob_start();
        ?>
        <div class="sohoj-phone-history">
            <h3 style="margin: 0 0 15px 0; color: #111827;">Order History for <?php echo esc_html($phone); ?></h3>
            
            <?php if ($orders): ?>
                <div style="overflow-x: auto;">
                    <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Order ID</th>
                                <th style="width: 120px;">Customer</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 100px;">Total</th>
                                <th style="width: 150px;">Date</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $status_colors = array(
                                    'wc-pending' => '#f59e0b',
                                    'wc-processing' => '#3b82f6',
                                    'wc-on-hold' => '#6b7280',
                                    'wc-completed' => '#10b981',
                                    'wc-cancelled' => '#ef4444',
                                    'wc-refunded' => '#8b5cf6',
                                    'wc-failed' => '#ef4444'
                                );
                                $status_color = isset($status_colors[$order->post_status]) ? $status_colors[$order->post_status] : '#6b7280';
                                $status_text = ucfirst(str_replace('wc-', '', $order->post_status));
                                ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($order->ID); ?></strong></td>
                                    <td><?php echo esc_html(trim($order->first_name . ' ' . $order->last_name)); ?></td>
                                    <td>
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; color: white; background: <?php echo $status_color; ?>;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order->total): ?>
                                            <?php echo ($order->currency ? $order->currency : '$') . number_format($order->total, 2); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order->post_date)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('post.php?post=' . $order->ID . '&action=edit'); ?>" 
                                           target="_blank" 
                                           class="button button-small"
                                           style="font-size: 11px; padding: 2px 6px;">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background: #f3f4f6; border-radius: 5px; font-size: 13px;">
                    <strong>Summary:</strong> 
                    <?php
                    $total_orders = count($orders);
                    $completed = count(array_filter($orders, function($o) { return $o->post_status === 'wc-completed'; }));
                    $pending = count(array_filter($orders, function($o) { return in_array($o->post_status, ['wc-pending', 'wc-processing', 'wc-on-hold']); }));
                    $cancelled = count(array_filter($orders, function($o) { return in_array($o->post_status, ['wc-cancelled', 'wc-failed', 'wc-refunded']); }));
                    ?>
                    Total: <?php echo $total_orders; ?> orders | 
                    Completed: <?php echo $completed; ?> | 
                    Pending: <?php echo $pending; ?> | 
                    Cancelled/Failed: <?php echo $cancelled; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; color: #6b7280;">
                    <p style="margin: 0; font-size: 14px;">No order history found for this phone number.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        wp_send_json_success(ob_get_clean());
    }
}