<?php
/**
 * Admin Handler
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Handler Class
 */
class Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize admin functionality
     */
    private function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Initialize settings
        new Settings();
        
        // Initialize IP blocker admin menu
        $ip_blocker = new \SohojSecureOrder\Core\IP_Blocker();
        add_action('admin_menu', array($ip_blocker, 'add_admin_menu'), 20);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Sohoj Secure Order',
            'Sohoj Secure',
            'manage_options',
            'sohoj-secure-order',
            array($this, 'dashboard_page'),
            'dashicons-shield',
            30
        );
        
        // Sub menus
        add_submenu_page(
            'sohoj-secure-order',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sohoj-secure-order',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'sohoj-secure-order',
            'Settings',
            'Settings',
            'manage_options',
            'sohoj-settings',
            array($this, 'settings_page')
        );
        
        // Only add Incomplete Orders submenu if it's enabled
        if (get_option('sohoj_incomplete_orders_enabled', 0) == 1) {
            add_submenu_page(
                'sohoj-secure-order',
                'Incomplete Orders',
                'Incomplete Orders',
                'manage_options',
                'sohoj-incomplete-orders',
                array($this, 'incomplete_orders_page')
            );
        }

        if (get_option('sohoj_ip_blocking_enabled', 0) == 1) {
            add_submenu_page(
                'sohoj-secure-order',
                'Blocked Users',
                'Blocked Users',
                'manage_options',
                'sohoj-blocked-users',
                array($this, 'blocked_users_page')
            );
        }
        
        if (get_option('sohoj_fraud_check_enabled', 0) == 1) {
            add_submenu_page(
                'sohoj-secure-order',
                'Fraud Check',
                'Fraud Check',
                'manage_options',
                'sohoj-fraud-check',
                array($this, 'fraud_check_page')
            );
        }
        
        add_submenu_page(
            'sohoj-secure-order',
            'License',
            'License',
            'manage_options',
            'sohoj-license',
            array($this, 'license_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>Sohoj Secure Order - Dashboard</h1>
            <div class="sohoj-dashboard">
                <div class="sohoj-card">
                    <h2>Welcome to Your Plugin</h2>
                    <p>This is a clean starter plugin template.</p>
                    <p>Features:</p>
                    <ul>
                        <li>Professional OOP structure</li>
                        <li>Admin menu system</li>
                        <li>Settings management</li>
                        <li>License system</li>
                        <li>GitHub updates</li>
                        <li>Frontend shortcodes</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Handle form submission
        $success_message = '';
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['sohoj_settings_nonce'], 'sohoj_save_settings')) {
            // Debug: Log the POST data
            error_log('Sohoj Settings Debug - POST data: ' . print_r($_POST, true));
            
            // Main validation setting
            $phone_validation = isset($_POST['phone_validation']) ? 1 : 0;
            update_option('sohoj_phone_validation_enabled', $phone_validation);
            
            // Phone validation notification settings
            // Handle show_phone_info from hidden field (set by JavaScript from modal)
            $show_phone_info = isset($_POST['show_phone_info']) ? absint($_POST['show_phone_info']) : 0;
            error_log('Sohoj Settings Debug - show_phone_info value: ' . $show_phone_info);
            error_log('Sohoj Settings Debug - show_phone_info POST value: ' . (isset($_POST['show_phone_info']) ? $_POST['show_phone_info'] : 'NOT SET'));
            error_log('Sohoj Settings Debug - show_phone_info raw POST: ' . (isset($_POST['show_phone_info']) ? var_export($_POST['show_phone_info'], true) : 'NOT SET'));
            update_option('sohoj_show_phone_info', $show_phone_info);
            update_option('sohoj_error_icon', isset($_POST['error_icon']) ? sanitize_text_field($_POST['error_icon']) : '');
            update_option('sohoj_error_heading', isset($_POST['error_heading']) ? sanitize_text_field($_POST['error_heading']) : '');
            update_option('sohoj_error_message', isset($_POST['error_message']) ? sanitize_textarea_field($_POST['error_message']) : '');
            update_option('sohoj_info_icon', isset($_POST['info_icon']) ? sanitize_text_field($_POST['info_icon']) : '');
            update_option('sohoj_info_heading', isset($_POST['info_heading']) ? sanitize_text_field($_POST['info_heading']) : '');
            update_option('sohoj_info_message', isset($_POST['info_message']) ? sanitize_textarea_field($_POST['info_message']) : '');
            
            // Order Limit settings
            $order_limit = isset($_POST['order_limit']) ? 1 : 0;
            update_option('sohoj_order_limit_enabled', $order_limit);
            update_option('sohoj_order_limit_time_value', absint($_POST['order_limit_time_value']));
            update_option('sohoj_order_limit_time_unit', sanitize_text_field($_POST['order_limit_time_unit']));
            update_option('sohoj_order_limit_count', absint($_POST['order_limit_count']));
            update_option('sohoj_order_limit_method', sanitize_text_field($_POST['order_limit_method']));
            
            // Order Limit notification settings
            // Handle show_order_limit_info from hidden field (set by JavaScript from modal)
            $show_order_limit_info = isset($_POST['show_order_limit_info']) ? absint($_POST['show_order_limit_info']) : 0;
            error_log('Sohoj Settings Debug - show_order_limit_info value: ' . $show_order_limit_info);
            error_log('Sohoj Settings Debug - show_order_limit_info POST value: ' . (isset($_POST['show_order_limit_info']) ? $_POST['show_order_limit_info'] : 'NOT SET'));
            error_log('Sohoj Settings Debug - show_order_limit_info raw POST: ' . (isset($_POST['show_order_limit_info']) ? var_export($_POST['show_order_limit_info'], true) : 'NOT SET'));
            update_option('sohoj_show_order_limit_info', $show_order_limit_info);
            update_option('sohoj_order_limit_error_icon', isset($_POST['order_limit_error_icon']) ? sanitize_text_field($_POST['order_limit_error_icon']) : '');
            update_option('sohoj_order_limit_error_heading', isset($_POST['order_limit_error_heading']) ? sanitize_text_field($_POST['order_limit_error_heading']) : '');
            update_option('sohoj_order_limit_error_message', isset($_POST['order_limit_error_message']) ? sanitize_textarea_field($_POST['order_limit_error_message']) : '');
            update_option('sohoj_order_limit_info_icon', isset($_POST['order_limit_info_icon']) ? sanitize_text_field($_POST['order_limit_info_icon']) : '');
            update_option('sohoj_order_limit_info_heading', isset($_POST['order_limit_info_heading']) ? sanitize_text_field($_POST['order_limit_info_heading']) : '');
            update_option('sohoj_order_limit_info_message', isset($_POST['order_limit_info_message']) ? sanitize_textarea_field($_POST['order_limit_info_message']) : '');
            
            // Incomplete Orders settings
            $incomplete_orders_enabled = isset($_POST['incomplete_orders_enabled']) ? 1 : 0;
            update_option('sohoj_incomplete_orders_enabled', $incomplete_orders_enabled);

            $ip_blocking_enabled = isset($_POST['ip_blocking_enabled']) ? 1 : 0;
            update_option('sohoj_ip_blocking_enabled', $ip_blocking_enabled);
            
            $phone_history_enabled = isset($_POST['phone_history_enabled']) ? 1 : 0;
            error_log('Debug: Saving phone_history_enabled = ' . $phone_history_enabled);
            update_option('sohoj_phone_history_enabled', $phone_history_enabled);
            error_log('Debug: After save, option value = ' . get_option('sohoj_phone_history_enabled', 'NOT_SET'));
            
            $fraud_check_enabled = isset($_POST['fraud_check_enabled']) ? 1 : 0;
            update_option('sohoj_fraud_check_enabled', $fraud_check_enabled);
            
            $fraud_check_use_ai = isset($_POST['fraud_check_use_ai']) ? 1 : 0;
            update_option('sohoj_fraud_check_use_ai', $fraud_check_use_ai);
            
            // Tracking fields
            $tracking_fields = array();
            if (isset($_POST['track_billing_first_name'])) $tracking_fields[] = 'billing_first_name';
            if (isset($_POST['track_billing_last_name'])) $tracking_fields[] = 'billing_last_name';
            if (isset($_POST['track_billing_email'])) $tracking_fields[] = 'billing_email';
            if (isset($_POST['track_billing_phone'])) $tracking_fields[] = 'billing_phone';
            if (isset($_POST['track_billing_address'])) $tracking_fields[] = 'billing_address_1';
            update_option('sohoj_incomplete_orders_tracking_fields', $tracking_fields);
            
            $success_message = 'Settings saved successfully!';
        }
        
        // Get current settings
        $phone_validation_enabled = get_option('sohoj_phone_validation_enabled', 0);
        $show_phone_info = get_option('sohoj_show_phone_info', 1); // Default to enabled
        $error_icon = get_option('sohoj_error_icon', '⚠️');
        $error_heading = get_option('sohoj_error_heading', 'Invalid Phone Number');
        $error_message = get_option('sohoj_error_message', 'Please enter a valid Bangladeshi mobile number (e.g., 01712345678, +8801712345678)');
        $info_icon = get_option('sohoj_info_icon', '📱');
        $info_heading = get_option('sohoj_info_heading', 'Phone Number Format');
        $info_message = get_option('sohoj_info_message', 'Please enter a valid Bangladeshi phone number (e.g., +880 1712345678 or 01712345678)');
        
        // Order Limit settings
        $order_limit_enabled = get_option('sohoj_order_limit_enabled', 0);
        $order_limit_time_value = get_option('sohoj_order_limit_time_value', 60);
        $order_limit_time_unit = get_option('sohoj_order_limit_time_unit', 'minutes');
        $order_limit_count = get_option('sohoj_order_limit_count', 5);
        $order_limit_method = get_option('sohoj_order_limit_method', 'billing_phone');
        $order_limit_error_icon = get_option('sohoj_order_limit_error_icon', '🛑');
        $order_limit_error_heading = get_option('sohoj_order_limit_error_heading', 'Order Limit Reached');
        $order_limit_error_message = get_option('sohoj_order_limit_error_message', 'You have already placed {{ count }} orders in the last {{ period }} {{ unit }}. Please wait {{ remaining }} {{ unit }} before placing another order.');
        $show_order_limit_info = get_option('sohoj_show_order_limit_info', 0); // Default to disabled
        $order_limit_info_icon = get_option('sohoj_order_limit_info_icon', '⏰');
        $order_limit_info_heading = get_option('sohoj_order_limit_info_heading', 'Order Limit Policy');
        $order_limit_info_message = get_option('sohoj_order_limit_info_message', 'To ensure fair access, customers are limited to {{ limit }} orders per {{ period }} {{ unit }}.');
        
        // Incomplete Orders settings
        $incomplete_orders_enabled = get_option('sohoj_incomplete_orders_enabled', 0);
        $ip_blocking_enabled = get_option('sohoj_ip_blocking_enabled', 0);
        $phone_history_enabled = get_option('sohoj_phone_history_enabled', 0);
        $fraud_check_enabled = get_option('sohoj_fraud_check_enabled', 0);
        $fraud_check_use_ai = get_option('sohoj_fraud_check_use_ai', 0);
        error_log('Debug: Loading settings page - phone_history_enabled = ' . $phone_history_enabled);
        $tracking_fields = get_option('sohoj_incomplete_orders_tracking_fields', array('billing_email', 'billing_phone'));
        
        // Build form content using reusable components
        
        // Order Limit Section
        $order_limit_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'order_limit',
            'name' => 'order_limit',
            'value' => 1,
            'checked' => $order_limit_enabled == 1,
            'label' => 'Enable Order Limit',
            'description' => 'When enabled, customers will be limited to a specific number of orders within a time period. This helps prevent order flooding and ensures fair access for all customers.'
        ));
        
        // Order Limit configuration options (always render, show/hide with JS)
        $time_unit_options = array(
            'minutes' => 'Minutes',
            'hours' => 'Hours'
        );
        
        $limit_method_options = array(
            'billing_phone' => 'Phone Number',
            'billing_email' => 'Email Address'
        );
        
        $order_limit_config_fields = '
        <div class="sohoj-conditional-fields" id="order-limit-configuration" style="display: ' . ($order_limit_enabled ? 'block' : 'none') . ';">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                ' . \SohojSecureOrder\Admin\Form_Components::render_text_input(array(
                    'id' => 'order_limit_count',
                    'name' => 'order_limit_count',
                    'value' => $order_limit_count,
                    'label' => 'Order Limit',
                    'icon' => '🔢',
                    'placeholder' => '5',
                    'info' => 'Maximum number of orders allowed',
                    'type' => 'number',
                    'required' => true
                )) . '
                ' . \SohojSecureOrder\Admin\Form_Components::render_text_input(array(
                    'id' => 'order_limit_time_value',
                    'name' => 'order_limit_time_value',
                    'value' => $order_limit_time_value,
                    'label' => 'Time Period',
                    'icon' => '⏱️',
                    'placeholder' => '60',
                    'info' => 'Time period for the limit',
                    'type' => 'number',
                    'required' => true
                )) . '
                ' . \SohojSecureOrder\Admin\Form_Components::render_select(array(
                    'id' => 'order_limit_time_unit',
                    'name' => 'order_limit_time_unit',
                    'value' => $order_limit_time_unit,
                    'label' => 'Time Unit',
                    'icon' => '🕐',
                    'options' => $time_unit_options,
                    'info' => 'Unit for the time period',
                    'required' => true
                )) . '
                ' . \SohojSecureOrder\Admin\Form_Components::render_select(array(
                    'id' => 'order_limit_method',
                    'name' => 'order_limit_method',
                    'value' => $order_limit_method,
                    'label' => 'Limit Method',
                    'icon' => '🎯',
                    'options' => $limit_method_options,
                    'info' => 'Check orders by phone or email',
                    'required' => true
                )) . '
            </div>
            
            <div class="sohoj-notification-cards" id="order-limit-notification-customization">
                <h3 style="margin: 0 0 16px 0; color: #111827; font-size: 16px; font-weight: 600;">🎨 Customize Order Limit Notifications</h3>
                
                <div class="sohoj-card-grid">
                    <div class="sohoj-notification-card error-card" data-type="order_limit_error">
                        <div class="card-header">
                            <span class="card-icon">🛑</span>
                            <span class="card-title">Error Message</span>
                            <span class="edit-btn">✏️</span>
                        </div>
                        <div class="card-preview">
                            <strong>' . esc_html($order_limit_error_heading) . '</strong>
                            <p>' . esc_html(wp_trim_words($order_limit_error_message, 8)) . '</p>
                        </div>
                    </div>
                    
                    <div class="sohoj-notification-card info-card" data-type="order_limit_info">
                        <div class="card-header">
                            <span class="card-icon">⏰</span>
                            <span class="card-title">Info Message</span>
                            <span class="edit-btn">✏️</span>
                        </div>
                        <div class="card-preview">
                            <strong>' . esc_html($order_limit_info_heading) . '</strong>
                            <p>' . esc_html(wp_trim_words($order_limit_info_message, 8)) . '</p>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden fields for order limit form submission -->
                <input type="hidden" name="order_limit_error_icon" id="order_limit_error_icon" value="' . esc_attr($order_limit_error_icon) . '">
                <input type="hidden" name="order_limit_error_heading" id="order_limit_error_heading" value="' . esc_attr($order_limit_error_heading) . '">
                <input type="hidden" name="order_limit_error_message" id="order_limit_error_message" value="' . esc_attr($order_limit_error_message) . '">
                <input type="hidden" name="order_limit_info_icon" id="order_limit_info_icon" value="' . esc_attr($order_limit_info_icon) . '">
                <input type="hidden" name="order_limit_info_heading" id="order_limit_info_heading" value="' . esc_attr($order_limit_info_heading) . '">
                <input type="hidden" name="order_limit_info_message" id="order_limit_info_message" value="' . esc_attr($order_limit_info_message) . '">
            </div>
        </div>';
        
        // Phone Validation Section  
        $phone_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'phone_validation',
            'name' => 'phone_validation',
            'value' => 1,
            'checked' => $phone_validation_enabled == 1,
            'label' => 'Enable Bangladeshi Phone Number Validation',
            'description' => 'When enabled, customers must enter a valid Bangladeshi phone number (with or without country code) in the billing phone field during WooCommerce checkout. Invalid phone numbers will prevent order completion.'
        ));
        
        
        // Notification customization options (only show when validation is enabled)
        $customization_fields = '';
        if ($phone_validation_enabled) {
            $customization_fields = '
            <div class="sohoj-notification-cards" id="notification-customization">
                <h3 style="margin: 0 0 16px 0; color: #111827; font-size: 16px; font-weight: 600;">🎨 Customize Notifications</h3>
                
                <div class="sohoj-card-grid">
                    <div class="sohoj-notification-card error-card" data-type="error">
                        <div class="card-header">
                            <span class="card-icon">⚠️</span>
                            <span class="card-title">Error Message</span>
                            <span class="edit-btn">✏️</span>
                        </div>
                        <div class="card-preview">
                            <strong>' . esc_html($error_heading) . '</strong>
                            <p>' . esc_html(wp_trim_words($error_message, 8)) . '</p>
                        </div>
                    </div>
                    
                    <div class="sohoj-notification-card info-card" data-type="info">
                        <div class="card-header">
                            <span class="card-icon">📱</span>
                            <span class="card-title">Info Message</span>
                            <span class="edit-btn">✏️</span>
                        </div>
                        <div class="card-preview">
                            <strong>' . esc_html($info_heading) . '</strong>
                            <p>' . esc_html(wp_trim_words($info_message, 8)) . '</p>
                        </div>
                    </div>
                </div>
                
                <div class="sohoj-edit-modal" id="edit-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 id="modal-title">Edit Notification</h4>
                            <span class="close-btn">&times;</span>
                        </div>
                        <div class="modal-body">
                            ' . \SohojSecureOrder\Admin\Form_Components::render_text_input(array(
                                'id' => 'edit-icon',
                                'label' => 'Icon',
                                'icon' => '🎨',
                                'placeholder' => '⚠️ or <svg>...</svg>',
                                'info' => 'Use emoji, SVG, or HTML image tags',
                                'class' => 'modal-input'
                            )) . '
                            ' . \SohojSecureOrder\Admin\Form_Components::render_text_input(array(
                                'id' => 'edit-heading',
                                'label' => 'Heading',
                                'icon' => '📝',
                                'placeholder' => 'Notification title',
                                'info' => 'Short, descriptive heading',
                                'class' => 'modal-input'
                            )) . '
                            ' . \SohojSecureOrder\Admin\Form_Components::render_textarea(array(
                                'id' => 'edit-message',
                                'label' => 'Message',
                                'icon' => '💬',
                                'placeholder' => 'You have placed {{ count }} orders...',
                                'info' => 'Use placeholders: {{ count }}, {{ limit }}, {{ period }}, {{ unit }}, {{ remaining }}',
                                'rows' => 3,
                                'class' => 'modal-textarea'
                            )) . '
                            
                            <div id="info-message-options" style="display: none; margin-top: 12px;">
                                <div class="sohoj-form-field">
                                    <div class="sohoj-switch-container" style="gap: 8px;">
                                        <label class="sohoj-switch sohoj-switch--small">
                                            <input type="checkbox" id="edit-show-on-checkout" class="sohoj-switch-input" value="1" />
                                            <span class="sohoj-switch-slider sohoj-switch-slider--small"></span>
                                        </label>
                                        <label for="edit-show-on-checkout" class="sohoj-switch-label" style="font-size: 13px; margin: 0;">
                                            Show on Checkout Page
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="order-limit-info-options" style="display: none; margin-top: 12px;">
                                <div class="sohoj-form-field">
                                    <div class="sohoj-switch-container" style="gap: 8px;">
                                        <label class="sohoj-switch sohoj-switch--small">
                                            <input type="checkbox" id="edit-show-order-limit-on-checkout" class="sohoj-switch-input" value="1" />
                                            <span class="sohoj-switch-slider sohoj-switch-slider--small"></span>
                                        </label>
                                        <label for="edit-show-order-limit-on-checkout" class="sohoj-switch-label" style="font-size: 13px; margin: 0;">
                                            Show on Checkout Page
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="sohoj-btn-save">Save</button>
                            <button type="button" class="sohoj-btn-cancel">Cancel</button>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden fields for form submission -->
                <input type="hidden" name="error_icon" id="error_icon" value="' . esc_attr($error_icon) . '">
                <input type="hidden" name="error_heading" id="error_heading" value="' . esc_attr($error_heading) . '">
                <input type="hidden" name="error_message" id="error_message" value="' . esc_attr($error_message) . '">
                <input type="hidden" name="info_icon" id="info_icon" value="' . esc_attr($info_icon) . '">
                <input type="hidden" name="info_heading" id="info_heading" value="' . esc_attr($info_heading) . '">
                <input type="hidden" name="info_message" id="info_message" value="' . esc_attr($info_message) . '">
            </div>';
        }
        
        // Incomplete Orders Section
        $incomplete_orders_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'incomplete_orders_enabled',
            'name' => 'incomplete_orders_enabled',
            'value' => 1,
            'checked' => $incomplete_orders_enabled == 1,
            'label' => 'Enable Incomplete Orders Tracking',
            'description' => 'Track customers who fill checkout forms but don\'t complete their purchase. Helps recover abandoned orders through follow-up calls and conversions.'
        ));
        
        // Tracking fields configuration
        $tracking_fields_config = '
        <div class="sohoj-conditional-fields" id="incomplete-orders-configuration" style="display: ' . ($incomplete_orders_enabled ? 'block' : 'none') . ';">
            <h4 style="margin: 0 0 16px 0; color: #111827; font-size: 16px; font-weight: 600;">📋 Tracking Trigger Fields</h4>
            <p style="margin: 0 0 16px 0; color: #6b7280; font-size: 14px;">Select which fields, when filled by the customer, will trigger incomplete order tracking:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="sohoj-field-checkbox">
                    <input type="checkbox" name="track_billing_first_name" value="1" ' . (in_array('billing_first_name', $tracking_fields) ? 'checked' : '') . ' style="margin: 0;">
                    <span>👤 First Name</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="sohoj-field-checkbox">
                    <input type="checkbox" name="track_billing_last_name" value="1" ' . (in_array('billing_last_name', $tracking_fields) ? 'checked' : '') . ' style="margin: 0;">
                    <span>👤 Last Name</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="sohoj-field-checkbox">
                    <input type="checkbox" name="track_billing_email" value="1" ' . (in_array('billing_email', $tracking_fields) ? 'checked' : '') . ' style="margin: 0;">
                    <span>📧 Email Address</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="sohoj-field-checkbox">
                    <input type="checkbox" name="track_billing_phone" value="1" ' . (in_array('billing_phone', $tracking_fields) ? 'checked' : '') . ' style="margin: 0;">
                    <span>📱 Phone Number</span>
                </label>
                
                <label style="display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="sohoj-field-checkbox">
                    <input type="checkbox" name="track_billing_address" value="1" ' . (in_array('billing_address_1', $tracking_fields) ? 'checked' : '') . ' style="margin: 0;">
                    <span>🏠 Address</span>
                </label>
            </div>
            
            <p style="margin: 16px 0 0 0; color: #6b7280; font-size: 13px; font-style: italic;">
                💡 <strong>Tip:</strong> Select multiple fields for better tracking accuracy. When ANY of the selected fields are filled, the incomplete order will be saved.
            </p>
        </div>';
        
        // Create separate sections for each feature
        $incomplete_orders_section_content = $incomplete_orders_switch . $tracking_fields_config;
        $order_limit_section_content = $order_limit_switch . $order_limit_config_fields;
        $phone_validation_section_content = $phone_switch . $customization_fields;
        
        $order_limit_section = \SohojSecureOrder\Admin\Form_Components::render_section(array(
            'title' => 'Order Limiting',
            'description' => 'Control customer order frequency to prevent abuse and ensure fair access.',
            'content' => $order_limit_section_content
        ));
        
        $phone_validation_section = \SohojSecureOrder\Admin\Form_Components::render_section(array(
            'title' => 'Phone Number Validation',
            'description' => 'Validate Bangladeshi phone numbers during WooCommerce checkout.',
            'content' => $phone_validation_section_content
        ));
        
        $incomplete_orders_section = \SohojSecureOrder\Admin\Form_Components::render_section(array(
            'title' => 'Incomplete Orders Tracking',
            'description' => 'Track and recover abandoned checkout attempts for better conversion rates.',
            'content' => $incomplete_orders_section_content
        ));
        
        $ip_blocking_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'ip_blocking_enabled',
            'name' => 'ip_blocking_enabled',
            'value' => 1,
            'checked' => $ip_blocking_enabled == 1,
            'label' => 'Enable IP Blocking',
            'description' => 'When enabled, you can block users by IP address and phone number.'
        ));

        $ip_blocking_section = \SohojSecureOrder\Admin\Form_Components::render_section(array(
            'title' => 'IP Blocking',
            'description' => 'Block users by IP and Phone Number.',
            'content' => $ip_blocking_switch
        ));
        
        $phone_history_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'phone_history_enabled',
            'name' => 'phone_history_enabled',
            'value' => 1,
            'checked' => $phone_history_enabled == 1,
            'label' => 'Enable Phone History',
            'description' => 'When enabled, shows order history column in Incomplete Orders table with completed/pending/cancelled order counts for each phone number.'
        ));

        $phone_history_section = \SohojSecureOrder\Admin\Form_Components::render_section(array(
            'title' => 'Phone History',
            'description' => 'Show order history for phone numbers in incomplete orders table.',
            'content' => $phone_history_switch
        ));
        
        $fraud_check_main_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'fraud_check_enabled',
            'name' => 'fraud_check_enabled',
            'value' => 1,
            'checked' => $fraud_check_enabled == 1,
            'label' => 'Enable Fraud Check',
            'description' => 'When enabled, provides a fraud check tool to analyze phone numbers for delivery risk assessment using CurtCommerz API.'
        ));

        $fraud_check_ai_switch = \SohojSecureOrder\Admin\Form_Components::render_switch(array(
            'id' => 'fraud_check_use_ai',
            'name' => 'fraud_check_use_ai',
            'value' => 1,
            'checked' => $fraud_check_use_ai == 1,
            'label' => 'Include AI Analysis',
            'description' => 'Include AI-powered risk assessment and recommendations (may increase processing time)'
        ));

        // Create nested fraud check options
        $fraud_check_content = $fraud_check_main_switch . '
        <div id="fraud-check-ai-option" style="margin-left: 25px; margin-top: 15px; padding-left: 15px; border-left: 3px solid #e5e7eb; ' . ($fraud_check_enabled == 1 ? '' : 'display: none;') . '">
            ' . $fraud_check_ai_switch . '
        </div>';

        $fraud_check_section = \SohojSecureOrder\Admin\Form_Components::render_section(array(
            'title' => 'Fraud Check',
            'description' => 'Analyze phone numbers for delivery risks and fraud patterns.',
            'content' => $fraud_check_content
        ));

        $all_sections = $order_limit_section . $phone_validation_section . $incomplete_orders_section . $ip_blocking_section . $phone_history_section . $fraud_check_section;
        
        $save_button = \SohojSecureOrder\Admin\Form_Components::render_button(array(
            'text' => 'Save Settings',
            'type' => 'submit',
            'name' => 'submit',
            'class' => 'primary',
            'size' => 'normal'
        ));
        
        // Always include hidden fields for checkout page settings, regardless of feature status
        $hidden_fields = '
        <!-- Always present hidden fields for checkout page settings -->
        <input type="hidden" name="show_phone_info" id="show_phone_info" value="' . esc_attr($show_phone_info) . '">
        <input type="hidden" name="show_order_limit_info" id="show_order_limit_info" value="' . esc_attr($show_order_limit_info) . '">
        ';
        
        $form_content = '
            <form method="post" action="">
                ' . wp_nonce_field('sohoj_save_settings', 'sohoj_settings_nonce', true, false) . '
                ' . $all_sections . '
                ' . $hidden_fields . '
                <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                    ' . $save_button . '
                </div>
            </form>
        ';
        
        $form_container = \SohojSecureOrder\Admin\Form_Components::render_form_container($form_content, array(
            'title' => 'Security Settings',
            'description' => 'Configure security features for your WooCommerce store'
        ));
        
        ?>
        <div class="wrap">
            <div class="sohoj-settings">
                <?php if ($success_message): ?>
                    <?php echo \SohojSecureOrder\Admin\Form_Components::render_info_box(array(
                        'type' => 'success',
                        'content' => $success_message
                    )); ?>
                <?php endif; ?>
                
                <?php echo $form_container; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle fraud check toggle
            $('#fraud_check_enabled').change(function() {
                if ($(this).is(':checked')) {
                    $('#fraud-check-ai-option').fadeIn();
                } else {
                    $('#fraud-check-ai-option').fadeOut();
                }
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * Incomplete Orders page
     */
    public function incomplete_orders_page() {
        $incomplete_orders = new \SohojSecureOrder\Core\Incomplete_Orders();
        
        // Handle pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Handle search
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Handle status filter - show all orders by default
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        // Get orders
        $orders = $incomplete_orders->get_incomplete_orders(array(
            'status' => $status,
            'limit' => $per_page,
            'offset' => $offset,
            'search' => $search,
            'date_filter' => $analytics_period,
            'start_date' => $custom_start_date,
            'end_date' => $custom_end_date
        ));
        
        // Get total count
        $total_items = $incomplete_orders->get_incomplete_orders_count($status, $search, $analytics_period, $custom_start_date, $custom_end_date);
        $total_pages = ceil($total_items / $per_page);
        
        // Handle analytics period selection
        $analytics_period = isset($_GET['analytics_period']) ? sanitize_text_field($_GET['analytics_period']) : 'today';
        $custom_start_date = isset($_GET['custom_start_date']) ? sanitize_text_field($_GET['custom_start_date']) : '';
        $custom_end_date = isset($_GET['custom_end_date']) ? sanitize_text_field($_GET['custom_end_date']) : '';
        
        // Get statistics based on selected period
        if ($analytics_period === 'custom' && !empty($custom_start_date) && !empty($custom_end_date)) {
            $stats = $incomplete_orders->get_custom_statistics($custom_start_date, $custom_end_date);
            $period_label = date('M j, Y', strtotime($custom_start_date)) . ' - ' . date('M j, Y', strtotime($custom_end_date));
        } else {
            $stats = $incomplete_orders->get_statistics($analytics_period);
            $period_labels = array(
                'today' => 'Today',
                'week' => 'This Week', 
                'month' => 'This Month',
                'year' => 'This Year',
                'maximum' => 'All Time'
            );
            $period_label = $period_labels[$analytics_period] ?? 'Today';
        }
        
        ?>
        <div class="wrap">
            <h1>Incomplete Orders Management</h1>
            
            <!-- Analytics Dashboard -->
            <div style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin: 16px 0; border: 1px solid #e5e7eb;">
                <div style="margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #111827; font-size: 16px;">📊 Analytics</h3>
                </div>
                
                <!-- Analytics 2 Column Layout -->
                <div style="display: grid; grid-template-columns: 300px 1fr; gap: 20px;">
                    <!-- Left Column - Statistics List -->
                    <div>
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #374151;"><?php echo esc_html($period_label); ?> Statistics</h4>
                        <ul style="list-style: none; margin: 0; padding: 0;">
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                                <span style="font-size: 14px; color: #374151;">Incomplete Orders:</span>
                                <strong style="font-size: 14px; color: #111827;"><?php echo $stats['incomplete']; ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                                <span style="font-size: 14px; color: #374151;">Converted Orders:</span>
                                <strong style="font-size: 14px; color: #111827;"><?php echo $stats['converted']; ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                                <span style="font-size: 14px; color: #374151;">Called Orders:</span>
                                <strong style="font-size: 14px; color: #111827;"><?php echo $stats['called']; ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                                <span style="font-size: 14px; color: #374151;">Rejected Orders:</span>
                                <strong style="font-size: 14px; color: #111827;"><?php echo $stats['rejected']; ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                                <span style="font-size: 14px; color: #374151;">Conversion Rate:</span>
                                <strong style="font-size: 14px; color: #111827;"><?php echo $stats['conversion_rate']; ?>%</strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                                <span style="font-size: 14px; color: #374151;">Converted Value:</span>
                                <strong style="font-size: 14px; color: #111827;">$<?php echo number_format($stats['converted_value'], 2); ?></strong>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Right Column - Full Width Chart -->
                    <div>
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">Visual Overview</h4>
                        <canvas id="incomplete-orders-chart" 
                                data-incomplete="<?php echo $stats['incomplete']; ?>"
                                data-converted="<?php echo $stats['converted']; ?>"
                                data-called="<?php echo $stats['called']; ?>"
                                data-rejected="<?php echo $stats['rejected']; ?>"
                                width="600" height="250" style="max-width: 100%; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="tablenav top" style="margin-bottom: 16px;">
                <div class="alignleft actions">
                    <form method="get" style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="page" value="sohoj-incomplete-orders">
                        
                        <select name="status" style="height: 32px;">
                            <option value="" <?php selected($status, ''); ?>>All Orders</option>
                            <option value="incomplete" <?php selected($status, 'incomplete'); ?>>Incomplete Only</option>
                            <option value="completed" <?php selected($status, 'completed'); ?>>Converted Only</option>
                            <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected Only</option>
                        </select>
                        
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search by email, phone, name..." style="width: 250px; height: 32px;">
                        
                        <!-- Date Range Filter -->
                        <select name="analytics_period" id="filter-period" style="height: 32px;">
                            <option value="" <?php selected($analytics_period, ''); ?>>All Dates</option>
                            <option value="today" <?php selected($analytics_period, 'today'); ?>>Today</option>
                            <option value="week" <?php selected($analytics_period, 'week'); ?>>This Week</option>
                            <option value="month" <?php selected($analytics_period, 'month'); ?>>This Month</option>
                            <option value="year" <?php selected($analytics_period, 'year'); ?>>This Year</option>
                            <option value="custom" <?php selected($analytics_period, 'custom'); ?>>Custom Range</option>
                        </select>
                        
                        <div id="filter-custom-dates" style="display: <?php echo $analytics_period === 'custom' ? 'flex' : 'none'; ?>; align-items: center; gap: 4px;">
                            <input type="date" name="custom_start_date" value="<?php echo esc_attr($custom_start_date); ?>" style="height: 32px; font-size: 12px;">
                            <span style="font-size: 12px;">to</span>
                            <input type="date" name="custom_end_date" value="<?php echo esc_attr($custom_end_date); ?>" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <input type="submit" class="button" value="Filter">
                        
                        <?php if (!empty($search) || !empty($status) || !empty($analytics_period)): ?>
                            <a href="<?php echo admin_url('admin.php?page=sohoj-incomplete-orders'); ?>" class="button">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Orders Table -->
            <!-- DEBUG: Incomplete Orders Enabled = <?php echo get_option('sohoj_incomplete_orders_enabled', 'NOT_SET'); ?> -->
            <!-- DEBUG: Phone History Setting = <?php echo get_option('sohoj_phone_history_enabled', 'NOT_SET'); ?> -->
            <div class="sohoj-incomplete-orders-table">
                <table class="wp-list-table widefat fixed striped" style="background: #fff;">
                    <thead>
                        <tr>
                            <?php 
                            $columns = $this->get_incomplete_orders_columns();
                            foreach ($columns as $key => $column):
                            ?>
                                <th style="<?php echo isset($column['style']) ? esc_attr($column['style']) : ''; ?>"><?php echo esc_html($column['label']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <?php 
                                    $columns = $this->get_incomplete_orders_columns();
                                    foreach ($columns as $key => $column):
                                    ?>
                                        <td><?php echo $this->render_column_content($key, $order); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo count($this->get_incomplete_orders_columns()); ?>" style="text-align: center; padding: 40px;">
                                    <strong>No orders found.</strong><br>
                                    <small>Orders will appear here when customers fill the checkout form but don't complete the purchase.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom" style="margin-top: 16px;">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $total_items; ?> items</span>
                        <?php
                        $base_url = admin_url('admin.php?page=sohoj-incomplete-orders');
                        if (!empty($search)) $base_url .= '&s=' . urlencode($search);
                        if ($status !== 'incomplete') $base_url .= '&status=' . urlencode($status);
                        
                        if ($page > 1): ?>
                            <a class="button" href="<?php echo $base_url . '&paged=' . ($page - 1); ?>">‹ Previous</a>
                        <?php endif; ?>
                        
                        <span class="paging-input">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a class="button" href="<?php echo $base_url . '&paged=' . ($page + 1); ?>">Next ›</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Modals -->
        <div id="sohoj-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0;">Order Details</h2>
                    <button id="sohoj-close-details" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <div id="sohoj-details-content" style="padding: 20px;">
                    Loading...
                </div>
            </div>
        </div>
        
        <div id="sohoj-reject-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; max-width: 400px; width: 90%;">
                <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0;">Reject Order</h2>
                    <button id="sohoj-close-reject" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px;">
                    <label for="rejection-reason" style="display: block; margin-bottom: 8px; font-weight: 600;">Reason for rejection:</label>
                    <textarea id="rejection-reason" style="width: 100%; height: 80px; margin-bottom: 16px;" placeholder="Optional reason..."></textarea>
                    <div style="text-align: right;">
                        <button id="sohoj-confirm-reject" class="button button-primary" style="background: #ef4444; border-color: #ef4444;">Reject Order</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="sohoj-phone-history-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; max-width: 800px; width: 95%; max-height: 80vh; overflow-y: auto;">
                <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0;">Phone Order History</h2>
                    <button id="sohoj-close-phone-history" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <div id="sohoj-phone-history-content" style="padding: 20px;">
                    Loading...
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get incomplete orders table columns
     */
    private function get_incomplete_orders_columns() {
        $columns = array(
            'id' => array(
                'label' => 'ID',
                'style' => 'width: 80px;'
            ),
            'customer' => array(
                'label' => 'Customer',
                'style' => ''
            ),
            'phone' => array(
                'label' => 'Phone',
                'style' => ''
            ),
            'total' => array(
                'label' => 'Total',
                'style' => 'width: 100px;'
            ),
            'date' => array(
                'label' => 'Date',
                'style' => 'width: 120px;'
            ),
            'status' => array(
                'label' => 'Status',
                'style' => 'width: 80px;'
            )
        );
        
        // Add History column - FORCE ENABLE for testing
        $columns['history'] = array(
            'label' => 'History',
            'style' => 'width: 120px;'
        );
        
        $columns['actions'] = array(
            'label' => 'Actions',
            'style' => 'width: 200px;'
        );
        
        return $columns;
    }
    
    /**
     * Render column content for incomplete orders table
     */
    private function render_column_content($column_key, $order) {
        switch ($column_key) {
            case 'id':
                return esc_html($order->id);
                
            case 'customer':
                return '<strong>' . esc_html($order->billing_first_name . ' ' . $order->billing_last_name) . '</strong><br>' .
                       '<small>' . esc_html($order->customer_email) . '</small>';
                       
            case 'phone':
                return esc_html($order->customer_phone);
                
            case 'total':
                return '$' . number_format($order->cart_total, 2);
                
            case 'date':
                return date('M j, Y', strtotime($order->created_at));
                
            case 'status':
                $status_html = '<span class="sohoj-status-badge sohoj-status-' . $order->status . '" style="
                    padding: 4px 8px; 
                    border-radius: 4px; 
                    font-size: 11px; 
                    font-weight: 600;';
                    
                if ($order->status === 'incomplete') $status_html .= 'background: #fef3c7; color: #92400e;';
                elseif ($order->status === 'completed') $status_html .= 'background: #d1fae5; color: #065f46;';
                elseif ($order->status === 'rejected') $status_html .= 'background: #fee2e2; color: #991b1b;';
                
                $status_html .= '">' . ucfirst($order->status) . '</span>';
                
                if ($order->called_at) {
                    $status_html .= '<br><small style="color: #6b7280;">Called: ' . date('M j', strtotime($order->called_at)) . '</small>';
                }
                
                if ($order->converted_order_id) {
                    $status_html .= '<br><small style="color: #059669;">Order #' . $order->converted_order_id . '</small>';
                }
                
                return $status_html;
                
            case 'history':
                $phone_stats = $this->get_phone_order_stats($order->customer_phone);
                return '<button class="button button-small sohoj-history-btn" data-phone="' . esc_attr($order->customer_phone) . '" style="font-size: 11px;">' .
                       $phone_stats['completed'] . 'C / ' . $phone_stats['pending'] . 'P / ' . $phone_stats['cancelled'] . 'X' .
                       '</button>';
                
            case 'actions':
                $actions = '';
                if ($order->status === 'incomplete') {
                    $actions .= '<button class="button button-small sohoj-call-btn" data-id="' . $order->id . '" style="background: #3b82f6; color: white; border: none; margin-right: 4px;">📞 Call</button>';
                    $actions .= '<button class="button button-small button-primary sohoj-convert-btn" data-id="' . $order->id . '" style="margin-right: 4px;">✅ Convert</button>';
                    $actions .= '<button class="button button-small sohoj-reject-btn" data-id="' . $order->id . '" style="background: #ef4444; color: white; border: none;">❌ Reject</button>';
                } elseif ($order->status === 'completed' && $order->converted_order_id) {
                    $actions .= '<a href="' . admin_url('post.php?post=' . $order->converted_order_id . '&action=edit') . '" class="button button-small">View Order</a>';
                }
                $actions .= '<button class="button button-small sohoj-view-details-btn" data-id="' . $order->id . '" style="margin-top: 4px;">👁️ Details</button>';
                return $actions;
                
            default:
                return '';
        }
    }

    /**
     * Get order statistics for a phone number
     */
    private function get_phone_order_stats($phone) {
        global $wpdb;
        
        // Get WooCommerce orders by phone
        $orders = $wpdb->get_results($wpdb->prepare("
            SELECT pm.meta_value as phone, p.post_status as status
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_billing_phone' 
            AND pm.meta_value = %s
            AND p.post_type = 'shop_order'
        ", $phone));
        
        $stats = array(
            'completed' => 0,
            'pending' => 0,
            'cancelled' => 0
        );
        
        foreach ($orders as $order) {
            switch ($order->status) {
                case 'wc-completed':
                    $stats['completed']++;
                    break;
                case 'wc-processing':
                case 'wc-pending':
                case 'wc-on-hold':
                    $stats['pending']++;
                    break;
                case 'wc-cancelled':
                case 'wc-failed':
                case 'wc-refunded':
                    $stats['cancelled']++;
                    break;
            }
        }
        
        return $stats;
    }
    
    /**
     * Blocked users page
     */
    public function blocked_users_page() {
        $ip_blocker = new \SohojSecureOrder\Core\IP_Blocker();
        $ip_blocker->blocked_users_page();
    }
    
    /**
     * License page
     */
    public function license_page() {
        $license_manager = new \SohojSecureOrder\Core\License_Manager();
        $is_active = \SohojSecureOrder\Core\License_Manager::is_license_active();
        $api_key = \SohojSecureOrder\Core\License_Manager::get_api_key();
        $license_data = \SohojSecureOrder\Core\License_Manager::get_license_data();
        
        // Build license status display
        $status_badge = $is_active 
            ? '<span class="sohoj-badge-success">Active</span>'
            : '<span class="sohoj-badge-error">Inactive</span>';
        
        // Build stats for active license
        $stats_content = '';
        if ($is_active && !empty($license_data)) {
            $plan_name = \SohojSecureOrder\Core\License_Manager::get_plan_name();
            $fraud_checks = \SohojSecureOrder\Core\License_Manager::get_remaining_fraud_checks();
            $sms_balance = number_format(\SohojSecureOrder\Core\License_Manager::get_sms_balance(), 2);
            $status = isset($license_data['status']) ? ucfirst($license_data['status']) : 'Unknown';
            
            $stats_content = '
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0;">
                <div class="sohoj-stat-item">
                    <div class="sohoj-stat-icon">📦</div>
                    <div class="sohoj-stat-content">
                        <h4>Plan</h4>
                        <p>' . esc_html($plan_name) . '</p>
                    </div>
                </div>
                <div class="sohoj-stat-item">
                    <div class="sohoj-stat-icon">🔍</div>
                    <div class="sohoj-stat-content">
                        <h4>Fraud Checks</h4>
                        <p>' . $fraud_checks . ' remaining</p>
                    </div>
                </div>
                <div class="sohoj-stat-item">
                    <div class="sohoj-stat-icon">💬</div>
                    <div class="sohoj-stat-content">
                        <h4>SMS Balance</h4>
                        <p>৳' . $sms_balance . '</p>
                    </div>
                </div>
                <div class="sohoj-stat-item">
                    <div class="sohoj-stat-icon">✅</div>
                    <div class="sohoj-stat-content">
                        <h4>Status</h4>
                        <p>' . $status . '</p>
                    </div>
                </div>
            </div>';
            
            // Add expiration warning if needed
            if (isset($license_data['end_date'])) {
                $expiry_date = date('F j, Y', strtotime($license_data['end_date']));
                $expiry_warning = \SohojSecureOrder\Core\License_Manager::is_license_expiring_soon() 
                    ? '<strong style="color: #dc2626;">(Expires Soon!)</strong>' 
                    : '';
                    
                $stats_content .= \SohojSecureOrder\Admin\Form_Components::render_info_box(array(
                    'type' => \SohojSecureOrder\Core\License_Manager::is_license_expiring_soon() ? 'warning' : 'info',
                    'content' => '<strong>License Expires:</strong> ' . $expiry_date . ' ' . $expiry_warning
                ));
            }
        }
        
        // Build main form content
        if (!$is_active) {
            // Activation form
            $api_key_field = \SohojSecureOrder\Admin\Form_Components::render_input(array(
                'id' => 'curtcommerz-api-key',
                'name' => 'curtcommerz_api_key',
                'label' => 'CurtCommerz API Key',
                'placeholder' => 'fc_abc123...',
                'description' => 'Get your API key from your CurtCommerz subscription dashboard.',
                'style' => 'font-family: monospace; width: 100%; max-width: 500px;'
            ));
            
            $activate_button = \SohojSecureOrder\Admin\Form_Components::render_button(array(
                'id' => 'activate-license-btn',
                'text' => '🚀 Activate License',
                'type' => 'button',
                'class' => 'primary',
                'size' => 'large'
            ));
            
            $form_content = '
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 8px 0;">Activate Your CurtCommerz License</h2>
                    <p style="color: #6b7280; margin: 0;">Enter your subscription API key to unlock premium features</p>
                </div>
                
                ' . $api_key_field . '
                
                <div style="margin-top: 24px; text-align: center;">
                    ' . $activate_button . '
                </div>
                
                <div id="license-message" style="margin-top: 20px; display: none;"></div>
            ';
        } else {
            // Management form for active license
            $current_key_display = '<code style="background: #f3f4f6; padding: 8px 12px; border-radius: 4px; font-size: 12px; color: #374151;">' 
                . esc_html(substr($api_key, 0, 20) . '...') . '</code>';
                
            $check_button = \SohojSecureOrder\Admin\Form_Components::render_button(array(
                'id' => 'check-license-btn',
                'text' => '🔄 Check Status',
                'type' => 'button',
                'class' => 'secondary',
                'size' => 'normal'
            ));
            
            $deactivate_button = \SohojSecureOrder\Admin\Form_Components::render_button(array(
                'id' => 'deactivate-license-btn',
                'text' => '🔓 Deactivate License',
                'type' => 'button',
                'class' => 'danger',
                'size' => 'normal'
            ));
            
            $form_content = '
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 8px 0;">License Active</h2>
                    <p style="color: #6b7280; margin: 0;">Your CurtCommerz license is active and ready to use</p>
                </div>
                
                ' . $stats_content . '
                
                <div style="margin: 24px 0;">
                    <h4 style="margin: 0 0 8px 0;">Current API Key</h4>
                    ' . $current_key_display . '
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    ' . $check_button . '
                    ' . $deactivate_button . '
                </div>
                
                <div id="license-message" style="margin-top: 20px; display: none;"></div>
            ';
        }
        
        // Features info
        $features_info = \SohojSecureOrder\Admin\Form_Components::render_info_box(array(
            'type' => 'info',
            'content' => '
                <h4 style="margin: 0 0 12px 0;">🌟 Premium Features</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Courier Services:</strong> Steadfast, Pathao, RedX integration</li>
                    <li><strong>SMS Services:</strong> OTP verification, bulk SMS, notifications</li>
                    <li><strong>Fraud Detection:</strong> Phone number risk assessment</li>
                    <li><strong>24/7 Support:</strong> Priority customer support</li>
                </ul>
            '
        ));
        
        $main_form = \SohojSecureOrder\Admin\Form_Components::render_form_container(
            $form_content . $features_info,
            array(
                'title' => 'CurtCommerz License Management',
                'description' => '',
                'style' => 'max-width: 700px; margin: 0 auto;'
            )
        );
        
        ?>
        <div class="wrap">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="display: inline-flex; align-items: center; gap: 12px; margin: 0; color: #111827;">
                    CurtCommerz License Management
                    <?php echo $status_badge; ?>
                </h1>
            </div>
            
            <style>
                .sohoj-badge-success {
                    background: #d1fae5;
                    color: #065f46;
                    padding: 6px 16px;
                    border-radius: 20px;
                    font-weight: bold;
                    font-size: 14px;
                }
                .sohoj-badge-error {
                    background: #fee2e2;  
                    color: #991b1b;
                    padding: 6px 16px;
                    border-radius: 20px;
                    font-weight: bold;
                    font-size: 14px;
                }
                .sohoj-stat-item {
                    display: flex;
                    align-items: center;
                    padding: 16px;
                    background: #f9fafb;
                    border-radius: 8px;
                    border: 1px solid #e5e7eb;
                }
                .sohoj-stat-icon {
                    font-size: 24px;
                    margin-right: 12px;
                }
                .sohoj-stat-content h4 {
                    margin: 0 0 4px 0;
                    font-size: 14px;
                    color: #6b7280;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .sohoj-stat-content p {
                    margin: 0;
                    font-size: 16px;
                    font-weight: 600;
                    color: #111827;
                }
                .sohoj-btn-danger {
                    background: #dc2626 !important;
                    border-color: #dc2626 !important;
                    color: white !important;
                }
                .sohoj-btn-danger:hover {
                    background: #b91c1c !important;
                    border-color: #b91c1c !important;
                }
            </style>
            
            <?php echo $main_form; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var licenseNonce = '<?php echo wp_create_nonce('sohoj_license_nonce'); ?>';
            
            function showMessage(message, type) {
                var $messageDiv = $('#license-message');
                var messageBox = '';
                
                if (type === 'success') {
                    messageBox = '<div class="sohoj-info-box sohoj-info-success" style="background: #d1fae5; color: #065f46; border: 1px solid #10b981; padding: 12px; border-radius: 6px;"><strong>✅ Success:</strong> ' + message + '</div>';
                } else {
                    messageBox = '<div class="sohoj-info-box sohoj-info-error" style="background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; padding: 12px; border-radius: 6px;"><strong>❌ Error:</strong> ' + message + '</div>';
                }
                
                $messageDiv.html(messageBox).show();
                
                // Reload page after successful activation/deactivation
                if (type === 'success') {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            }
            
            $('#activate-license-btn').click(function() {
                var $btn = $(this);
                var apiKey = $('#curtcommerz-api-key').val().trim();
                
                if (!apiKey) {
                    showMessage('Please enter your API key', 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('🔄 Activating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sohoj_activate_license',
                        api_key: apiKey,
                        nonce: licenseNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                        } else {
                            showMessage(response.data, 'error');
                            $btn.prop('disabled', false).text('🚀 Activate License');
                        }
                    },
                    error: function() {
                        showMessage('Connection error. Please try again.', 'error');
                        $btn.prop('disabled', false).text('🚀 Activate License');
                    }
                });
            });
            
            $('#check-license-btn').click(function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('⏳ Checking...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sohoj_check_license',
                        nonce: licenseNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('License status updated successfully', 'success');
                        } else {
                            showMessage(response.data, 'error');
                        }
                        $btn.prop('disabled', false).text('🔄 Check Status');
                    },
                    error: function() {
                        showMessage('Connection error. Please try again.', 'error');
                        $btn.prop('disabled', false).text('🔄 Check Status');
                    }
                });
            });
            
            $('#deactivate-license-btn').click(function() {
                if (!confirm('⚠️ Are you sure you want to deactivate your license?\n\nThis will disable all premium features including:\n• Courier services\n• SMS services\n• Fraud detection\n• Priority support')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('⏳ Deactivating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sohoj_deactivate_license',
                        nonce: licenseNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data, 'success');
                        } else {
                            showMessage(response.data, 'error');
                            $btn.prop('disabled', false).text('🔓 Deactivate License');
                        }
                    },
                    error: function() {
                        showMessage('Connection error. Please try again.', 'error');
                        $btn.prop('disabled', false).text('🔓 Deactivate License');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Fraud Check page
     */
    public function fraud_check_page() {
        $license_manager = new \SohojSecureOrder\Core\License_Manager();
        $is_active = \SohojSecureOrder\Core\License_Manager::is_license_active();
        
        if (!$is_active) {
            ?>
            <div class="wrap">
                <h1>Fraud Check</h1>
                <?php echo \SohojSecureOrder\Admin\Form_Components::render_info_box(array(
                    'type' => 'warning',
                    'content' => '<strong>License Required:</strong> Please activate your CurtCommerz license to use the fraud check feature. <a href="' . admin_url('admin.php?page=sohoj-license') . '">Activate License</a>'
                )); ?>
            </div>
            <?php
            return;
        }
        
        // Build the search form
        $phone_input = \SohojSecureOrder\Admin\Form_Components::render_text_input(array(
            'id' => 'fraud-check-phone',
            'name' => 'phone',
            'label' => 'Phone Number',
            'placeholder' => '01712345678 or +8801712345678',
            'description' => 'Enter a Bangladeshi phone number to check for fraud patterns',
            'style' => 'font-family: monospace; font-size: 16px;'
        ));
        
        $check_button = \SohojSecureOrder\Admin\Form_Components::render_button(array(
            'id' => 'fraud-check-btn',
            'text' => '🔍 Check Phone Number',
            'type' => 'button',
            'class' => 'primary',
            'size' => 'large'
        ));
        
        $form_content = '
            <div style="margin-bottom: 24px;">
                ' . $phone_input . '
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                ' . $check_button . '
            </div>
            
            <div id="fraud-results" style="display: none; margin-top: 30px;">
                <!-- Results will be loaded here -->
            </div>
        ';
        
        $main_form = \SohojSecureOrder\Admin\Form_Components::render_form_container(
            $form_content,
            array(
                'title' => 'Phone Number Fraud Check',
                'description' => 'Analyze phone numbers for delivery risk patterns and fraud indicators using CurtCommerz intelligence.',
                'style' => 'max-width: 800px; margin: 0 auto;'
            )
        );
        
        ?>
        <div class="wrap">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="margin: 0; color: #111827;">🛡️ Fraud Check Tool</h1>
                <p style="color: #6b7280; margin: 8px 0 0 0;">Intelligent fraud detection powered by CurtCommerz</p>
            </div>
            
            <?php echo $main_form; ?>
            
            <style>
                .fraud-risk-low {
                    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                    border: 2px solid #10b981;
                    color: #065f46;
                }
                .fraud-risk-medium {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    border: 2px solid #f59e0b;
                    color: #92400e;
                }
                .fraud-risk-high {
                    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                    border: 2px solid #ef4444;
                    color: #991b1b;
                }
                .fraud-result-card {
                    border-radius: 12px;
                    padding: 24px;
                    margin: 16px 0;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                }
                .fraud-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 16px;
                    margin: 20px 0;
                }
                .fraud-stat-item {
                    background: rgba(255,255,255,0.7);
                    padding: 16px;
                    border-radius: 8px;
                    text-align: center;
                    border: 1px solid rgba(0,0,0,0.1);
                }
                .fraud-stat-number {
                    font-size: 24px;
                    font-weight: bold;
                    display: block;
                    margin-bottom: 4px;
                }
                .fraud-stat-label {
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    opacity: 0.8;
                }
                .ai-suggestion {
                    background: rgba(255,255,255,0.9);
                    border-radius: 8px;
                    padding: 16px;
                    margin-top: 20px;
                    border: 1px solid rgba(0,0,0,0.1);
                    font-style: italic;
                }
                .loading-spinner {
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    border: 3px solid rgba(255,255,255,.3);
                    border-radius: 50%;
                    border-top-color: #fff;
                    animation: spin 1s ease-in-out infinite;
                }
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var fraudNonce = '<?php echo wp_create_nonce('sohoj_fraud_check_nonce'); ?>';
            
            $('#fraud-check-btn').click(function() {
                var $btn = $(this);
                var phone = $('#fraud-check-phone').val().trim();
                
                if (!phone) {
                    alert('Please enter a phone number');
                    return;
                }
                
                $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Checking...');
                $('#fraud-results').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sohoj_fraud_check',
                        phone: phone,
                        nonce: fraudNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#fraud-results').html(response.data).fadeIn();
                        } else {
                            alert('Error: ' + response.data);
                        }
                        $btn.prop('disabled', false).html('🔍 Check Phone Number');
                    },
                    error: function() {
                        alert('Connection error. Please try again.');
                        $btn.prop('disabled', false).html('🔍 Check Phone Number');
                    }
                });
            });
            
            // Allow Enter key to trigger search
            $('#fraud-check-phone').keypress(function(e) {
                if (e.which == 13) {
                    $('#fraud-check-btn').click();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages or plugins page
        $sohoj_pages = array(
            'toplevel_page_sohoj-secure-order',
            'sohoj-secure_page_sohoj-settings',
            'sohoj-secure_page_sohoj-incomplete-orders',
            'sohoj-secure_page_sohoj-license',
            'plugins.php'
        );
        
        if (!in_array($hook, $sohoj_pages) && strpos($hook, 'sohoj') === false) {
            return;
        }
        
        wp_enqueue_style('sohoj-admin-css', SOHOJ_PLUGIN_URL . 'assets/css/admin.css', array(), SOHOJ_PLUGIN_VERSION);
        wp_enqueue_script('sohoj-admin-js', SOHOJ_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SOHOJ_PLUGIN_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('sohoj-admin-js', 'sohoj_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sohoj_admin_nonce')
        ));
    }
}