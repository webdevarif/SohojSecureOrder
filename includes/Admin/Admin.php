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
            $show_phone_info = isset($_POST['show_phone_info']) ? 1 : 0;
            error_log('Sohoj Settings Debug - show_phone_info value: ' . $show_phone_info);
            update_option('sohoj_show_phone_info', $show_phone_info);
            update_option('sohoj_error_icon', sanitize_text_field($_POST['error_icon']));
            update_option('sohoj_error_heading', sanitize_text_field($_POST['error_heading']));
            update_option('sohoj_error_message', sanitize_textarea_field($_POST['error_message']));
            update_option('sohoj_info_icon', sanitize_text_field($_POST['info_icon']));
            update_option('sohoj_info_heading', sanitize_text_field($_POST['info_heading']));
            update_option('sohoj_info_message', sanitize_textarea_field($_POST['info_message']));
            
            // Order Limit settings
            $order_limit = isset($_POST['order_limit']) ? 1 : 0;
            update_option('sohoj_order_limit_enabled', $order_limit);
            update_option('sohoj_order_limit_time_value', absint($_POST['order_limit_time_value']));
            update_option('sohoj_order_limit_time_unit', sanitize_text_field($_POST['order_limit_time_unit']));
            update_option('sohoj_order_limit_count', absint($_POST['order_limit_count']));
            
            // Order Limit notification settings
            $show_order_limit_info = isset($_POST['show_order_limit_info']) ? 1 : 0;
            error_log('Sohoj Settings Debug - show_order_limit_info value: ' . $show_order_limit_info);
            update_option('sohoj_show_order_limit_info', $show_order_limit_info);
            update_option('sohoj_order_limit_error_icon', sanitize_text_field($_POST['order_limit_error_icon']));
            update_option('sohoj_order_limit_error_heading', sanitize_text_field($_POST['order_limit_error_heading']));
            update_option('sohoj_order_limit_error_message', sanitize_textarea_field($_POST['order_limit_error_message']));
            update_option('sohoj_order_limit_info_icon', sanitize_text_field($_POST['order_limit_info_icon']));
            update_option('sohoj_order_limit_info_heading', sanitize_text_field($_POST['order_limit_info_heading']));
            update_option('sohoj_order_limit_info_message', sanitize_textarea_field($_POST['order_limit_info_message']));
            
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
        $order_limit_error_icon = get_option('sohoj_order_limit_error_icon', '🛑');
        $order_limit_error_heading = get_option('sohoj_order_limit_error_heading', 'Order Limit Reached');
        $order_limit_error_message = get_option('sohoj_order_limit_error_message', 'You have already placed {{ count }} orders in the last {{ period }} {{ unit }}. Please wait {{ remaining }} {{ unit }} before placing another order.');
        $show_order_limit_info = get_option('sohoj_show_order_limit_info', 0); // Default to disabled
        $order_limit_info_icon = get_option('sohoj_order_limit_info_icon', '⏰');
        $order_limit_info_heading = get_option('sohoj_order_limit_info_heading', 'Order Limit Policy');
        $order_limit_info_message = get_option('sohoj_order_limit_info_message', 'To ensure fair access, customers are limited to {{ limit }} orders per {{ period }} {{ unit }}.');
        
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
        
        $order_limit_config_fields = '
        <div class="sohoj-conditional-fields" id="order-limit-configuration" style="display: ' . ($order_limit_enabled ? 'block' : 'none') . ';">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
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
                <input type="hidden" name="show_order_limit_info" id="show_order_limit_info" value="' . esc_attr($show_order_limit_info) . '">
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
                <input type="hidden" name="show_phone_info" id="show_phone_info" value="' . esc_attr($show_phone_info) . '">
            </div>';
        }
        
        // Create separate sections for each feature
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
        
        $all_sections = $order_limit_section . $phone_validation_section;
        
        $save_button = \SohojSecureOrder\Admin\Form_Components::render_button(array(
            'text' => 'Save Settings',
            'type' => 'submit',
            'name' => 'submit',
            'class' => 'primary',
            'size' => 'normal'
        ));
        
        $form_content = '
            <form method="post" action="">
                ' . wp_nonce_field('sohoj_save_settings', 'sohoj_settings_nonce', true, false) . '
                ' . $all_sections . '
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
        <?php
    }
    
    /**
     * License page
     */
    public function license_page() {
        ?>
        <div class="wrap">
            <h1>License Management</h1>
            <div class="sohoj-license">
                <div class="sohoj-card">
                    <h2>Plugin License</h2>
                    <p>Manage your plugin license.</p>
                    <p><em>License functionality coming soon...</em></p>
                </div>
            </div>
        </div>
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