<?php
/**
 * Phone History for WooCommerce Orders
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phone History Class
 */
class Phone_History {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize
     */
    private function init() {
        $phone_history_enabled = get_option('sohoj_phone_history_enabled', 0);
        error_log('Phone_History: Initializing - setting value: ' . $phone_history_enabled);
        
        // Force enable for testing - remove this line later
        $phone_history_enabled = 1;
        
        // Only initialize if Phone History is enabled
        if ($phone_history_enabled == 1) {
            error_log('Phone_History: Registering hooks for WooCommerce orders table');
            
            // Add History column to WooCommerce orders table
            add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_history_column'));
            add_filter('manage_edit-shop_order_columns', array($this, 'add_history_column'));
            
            // Render History column content
            add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_history_column'), 10, 2);
            add_action('manage_shop_order_posts_custom_column', array($this, 'render_history_column_legacy'), 10, 2);
            
            // AJAX handler for phone history modal
            add_action('wp_ajax_sohoj_get_phone_history', array($this, 'get_phone_history_ajax'));
            
            // Enqueue scripts for orders page
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        } else {
            error_log('Phone_History: Not enabled - setting value: ' . $phone_history_enabled);
        }
    }
    
    /**
     * Add History column to orders table
     */
    public function add_history_column($columns) {
        error_log('Phone_History: add_history_column called with columns: ' . print_r(array_keys($columns), true));
        
        $new_columns = array();
        $inserted = false;
        
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            // Insert after the 'order_status' column
            if ($key === 'order_status') {
                $new_columns['phone_history'] = __('Phone History', 'sohoj-secure-order');
                $inserted = true;
                error_log('Phone_History: Inserted after order_status column');
            }
        }
        
        // If order_status column doesn't exist, add at the end
        if (!$inserted) {
            $new_columns['phone_history'] = __('Phone History', 'sohoj-secure-order');
            error_log('Phone_History: Added at the end - order_status not found');
        }
        
        error_log('Phone_History: Final columns: ' . print_r(array_keys($new_columns), true));
        return $new_columns;
    }
    
    /**
     * Render History column content for HPOS orders
     */
    public function render_history_column($column, $order) {
        if ($column === 'phone_history') {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
            }
            
            if ($order) {
                $phone = $order->get_billing_phone();
                if ($phone) {
                    $phone_stats = $this->get_phone_order_stats($phone);
                    echo '<button class="button button-small sohoj-history-btn" data-phone="' . esc_attr($phone) . '" style="font-size: 11px;">' .
                         $phone_stats['total'] . ' Orders' .
                         '</button>';
                } else {
                    echo '<span style="color: #999;">No Phone</span>';
                }
            }
        }
    }
    
    /**
     * Render History column content for legacy orders (post-based)
     */
    public function render_history_column_legacy($column, $post_id) {
        if ($column === 'phone_history') {
            $order = wc_get_order($post_id);
            if ($order) {
                $phone = $order->get_billing_phone();
                if ($phone) {
                    $phone_stats = $this->get_phone_order_stats($phone);
                    echo '<button class="button button-small sohoj-history-btn" data-phone="' . esc_attr($phone) . '" style="font-size: 11px;">' .
                         $phone_stats['total'] . ' Orders' .
                         '</button>';
                } else {
                    echo '<span style="color: #999;">No Phone</span>';
                }
            }
        }
    }
    
    /**
     * Generate phone number variations for matching
     */
    private function get_phone_variations($phone) {
        // Normalize phone number - remove all non-digits
        $phone_digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Try multiple phone number variations
        $phone_variations = array(
            $phone, // Original format
            $phone_digits, // Only digits
            '+' . $phone_digits, // With + prefix
        );
        
        // Bangladesh phone number variations
        if (strlen($phone_digits) >= 10) {
            // For 01711132721 -> try 1711132721 (without leading 0)
            if (substr($phone_digits, 0, 1) === '0') {
                $without_zero = substr($phone_digits, 1);
                $phone_variations[] = $without_zero;
                $phone_variations[] = '+880' . $without_zero;
                $phone_variations[] = '880' . $without_zero;
            }
            
            // For 1711132721 -> try 01711132721 (with leading 0)
            if (substr($phone_digits, 0, 1) !== '0' && strlen($phone_digits) == 10) {
                $phone_variations[] = '0' . $phone_digits;
            }
            
            // Try standard Bangladesh formats
            $last_10_digits = substr($phone_digits, -10);
            if (strlen($last_10_digits) == 10) {
                $phone_variations[] = '+8801' . substr($last_10_digits, 1); // +8801XXXXXXXXX
                $phone_variations[] = '8801' . substr($last_10_digits, 1); // 8801XXXXXXXXX
            }
        }
        
        // Remove duplicates and empty values
        $phone_variations = array_unique(array_filter($phone_variations));
        
        return $phone_variations;
    }

    /**
     * Get order statistics for a phone number
     */
    private function get_phone_order_stats($phone) {
        global $wpdb;
        
        // Get phone variations
        $phone_variations = $this->get_phone_variations($phone);
        
        // Debug logging
        error_log('Phone_History: get_phone_order_stats - Searching for phone: ' . $phone);
        error_log('Phone_History: get_phone_order_stats - Phone variations: ' . print_r($phone_variations, true));
        
        // Create SQL IN clause for phone variations
        $placeholders = implode(',', array_fill(0, count($phone_variations), '%s'));
        
        // Get WooCommerce orders by phone variations
        $query = "
            SELECT DISTINCT pm.meta_value as phone, p.post_status as status
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_billing_phone' 
            AND pm.meta_value IN ($placeholders)
            AND p.post_type = 'shop_order'
        ";
        
        $orders = $wpdb->get_results($wpdb->prepare($query, $phone_variations));
        
        $completed = 0;
        $cancelled = 0;
        
        foreach ($orders as $order) {
            switch ($order->status) {
                case 'wc-completed':
                    $completed++;
                    break;
                case 'wc-cancelled':
                case 'wc-failed':
                case 'wc-refunded':
                    $cancelled++;
                    break;
            }
        }
        
        return array(
            'completed' => $completed,
            'cancelled' => $cancelled,
            'total' => $completed + $cancelled
        );
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
        
        // Get phone variations
        $phone_variations = $this->get_phone_variations($phone);
        
        // Debug logging
        error_log('Phone_History: get_phone_history_ajax - Searching for phone: ' . $phone);
        error_log('Phone_History: get_phone_history_ajax - Phone variations: ' . print_r($phone_variations, true));
        
        // Create SQL IN clause for phone variations
        $placeholders = implode(',', array_fill(0, count($phone_variations), '%s'));
        
        // Get all WooCommerce orders for this phone number
        $query = "
            SELECT DISTINCT p.ID, p.post_status, p.post_date, pm1.meta_value as total, pm2.meta_value as currency,
                   pm3.meta_value as first_name, pm4.meta_value as last_name, pm.meta_value as matched_phone
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_order_total'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_order_currency'
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_billing_first_name'
            LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_billing_last_name'
            WHERE pm.meta_key = '_billing_phone' 
            AND pm.meta_value IN ($placeholders)
            AND p.post_type = 'shop_order'
            ORDER BY p.post_date DESC
        ";
        
        $orders = $wpdb->get_results($wpdb->prepare($query, $phone_variations));
        
        // Debug results
        error_log('Phone_History: Found ' . count($orders) . ' orders for phone variations');
        if (!empty($orders)) {
            error_log('Phone_History: Sample order - ID: ' . $orders[0]->ID . ', Status: ' . $orders[0]->post_status . ', Phone: ' . $orders[0]->matched_phone);
        }
        
        // Additional debug: Let's see all phone numbers in the database
        $all_phones = $wpdb->get_results("
            SELECT DISTINCT pm.meta_value as phone_number, COUNT(*) as count
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_billing_phone' 
            AND p.post_type = 'shop_order'
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
            ORDER BY count DESC
            LIMIT 10
        ");
        
        error_log('Phone_History: Sample phone numbers in database:');
        foreach($all_phones as $phone_record) {
            error_log('Phone_History: Phone: "' . $phone_record->phone_number . '" (Count: ' . $phone_record->count . ')');
        }
        
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
                    $history_total = $completed + $cancelled;
                    ?>
                    Total: <?php echo $total_orders; ?> orders | 
                    <strong>History Count: <?php echo $history_total; ?></strong> (<?php echo $completed; ?> completed + <?php echo $cancelled; ?> cancelled) | 
                    Pending: <?php echo $pending; ?>
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
    
    /**
     * Enqueue scripts for orders page
     */
    public function enqueue_scripts($hook) {
        // Only load on WooCommerce orders pages
        if (strpos($hook, 'woocommerce_page_wc-orders') !== false || $hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
            wp_enqueue_script('sohoj-phone-history', SOHOJ_PLUGIN_URL . 'assets/js/phone-history.js', array('jquery'), '1.0.0', true);
            wp_localize_script('sohoj-phone-history', 'sohoj_phone_history_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sohoj_admin_nonce')
            ));
        }
    }
}