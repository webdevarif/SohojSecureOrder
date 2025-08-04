<?php
/**
 * IP Blocker
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

use SohojSecureOrder\Core\Phone_Validator;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * IP Blocker Class
 */
class IP_Blocker {
    
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
        

        

        // Add checkout validation
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout'));

        // Add bulk action to orders list
        if (get_option('sohoj_ip_blocking_enabled', 0) == 1) {
            add_filter('bulk_actions-edit-shop_order', array($this, 'add_block_user_bulk_action'));
            add_filter('handle_bulk_actions-edit-shop_order', array($this, 'handle_block_user_bulk_action'), 10, 3);
            add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'add_block_user_bulk_action'));
            add_filter('handle_bulk_actions-woocommerce_page_wc-orders', array($this, 'handle_block_user_bulk_action'), 10, 3);
            add_action('admin_notices', array($this, 'block_user_admin_notice'));
        }
    }

    /**
     * Create the database table
     */
    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sohoj_blocked_users';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            ip_address varchar(100) NOT NULL,
            phone_number varchar(20) NOT NULL,
            blocked_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
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
    }

    /**
     * Blocked users page
     */
    public function blocked_users_page() {
        $message = '';
        $message_type = '';

        if (isset($_POST['sohoj_block_user_nonce']) && wp_verify_nonce($_POST['sohoj_block_user_nonce'], 'sohoj_block_user_action')) {
            if (isset($_POST['unblock_submit']) && !empty($_POST['unblock'])) {
                $this->unblock_users($_POST['unblock']);
                $message = 'Selected users have been unblocked.';
                $message_type = 'success';
            } elseif (isset($_POST['block_submit']) && !empty($_POST['block_phone'])) {
                $phone_to_block = sanitize_text_field($_POST['block_phone']);
                $name_to_block = sanitize_text_field($_POST['block_name']);
                if ($this->block_user('', $phone_to_block, $name_to_block)) {
                    $message = 'User has been blocked successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Could not block user. They may already be blocked or the phone number is invalid.';
                    $message_type = 'error';
                }
            }
        }

        if ($message) {
            echo '<div class="notice notice-' . $message_type . ' is-dismissible"><p>' . $message . '</p></div>';
        }

        $blocked_users = $this->get_blocked_users();
        ?>
        <div class="wrap">
            <h1>Blocked Users Management</h1>
            <p>Manage users who are blocked from placing orders. You can block them by IP address or phone number.</p>

            <div id="col-container" class="wp-clearfix">
                <div id="col-left">
                    <div class="col-wrap">
                        <h2>Block a New User</h2>
                        <form method="post" class="sohoj-form">
                            <?php wp_nonce_field('sohoj_block_user_action', 'sohoj_block_user_nonce'); ?>
                            <div class="form-field">
                                <label for="block_name">Name</label>
                                <input type="text" name="block_name" id="block_name" placeholder="e.g., John Doe" style="width: 100%;">
                            </div>
                            <div class="form-field">
                                <label for="block_phone">Phone Number</label>
                                <input type="text" name="block_phone" id="block_phone" placeholder="e.g., 01712345678" style="width: 100%;">
                                <p>Enter a phone number to block. The user with this phone number will not be able to place an order.</p>
                            </div>
                            <p class="submit">
                                <input type="submit" name="block_submit" class="button button-primary" value="Block User">
                            </p>
                        </form>
                    </div>
                </div>

                <div id="col-right">
                    <div class="col-wrap">
                        <h2>Currently Blocked Users</h2>
                        <form method="post">
                            <?php wp_nonce_field('sohoj_block_user_action', 'sohoj_block_user_nonce'); ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <td id="cb" class="manage-column column-cb check-column">
                                            <input type="checkbox">
                                        </td>
                                        <th scope="col" class="manage-column">Name</th>
                                        <th scope="col" class="manage-column">IP Address</th>
                                        <th scope="col" class="manage-column">Phone Number</th>
                                        <th scope="col" class="manage-column">Blocked Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($blocked_users): ?>
                                        <?php foreach ($blocked_users as $user): ?>
                                            <tr>
                                                <th scope="row" class="check-column">
                                                    <input type="checkbox" name="unblock[]" value="<?php echo esc_attr($user->id); ?>">
                                                </th>
                                                <td><?php echo esc_html($user->name); ?></td>
                                                <td><?php echo esc_html($user->ip_address ? $user->ip_address : 'N/A'); ?></td>
                                                <td><?php echo esc_html($user->phone_number); ?></td>
                                                <td><?php echo esc_html($user->blocked_at); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 20px;">No blocked users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot >
                                    <tr>
                                        <td class="manage-column column-cb check-column">
                                            <input type="checkbox">
                                        </td>
                                        <th scope="col" class="manage-column">Name</th>
                                        <th scope="col" class="manage-column">IP Address</th>
                                        <th scope="col" class="manage-column">Phone Number</th>
                                        <th scope="col" class="manage-column">Blocked Date</th>
                                    </tr>
                                </tfoot>
                            </table>
                            <div class="tablenav bottom">
                                <div class="alignleft actions bulkactions">
                                    <input type="submit" name="unblock_submit" class="button action" value="Unblock Selected">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get blocked users
     */
    private function get_blocked_users() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sohoj_blocked_users';
        return $wpdb->get_results("SELECT * FROM $table_name");
    }

    /**
     * Block a user
     */
    public function block_user($ip, $phone, $name = '') {
        $ip = sanitize_text_field($ip);
        $phone = sanitize_text_field($phone);
        $name = sanitize_text_field($name);

        if (empty($ip) && empty($phone)) {
            return false;
        }

        $normalized_phone = Phone_Validator::normalize_bangladeshi_phone($phone);

        if ($this->is_blocked($ip, $normalized_phone)) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sohoj_blocked_users';

        $result = $wpdb->insert(
            $table_name,
            array(
                'name'         => $name,
                'ip_address'   => $ip,
                'phone_number' => $normalized_phone,
                'blocked_at'   => current_time('mysql', 1),
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        return $result !== false;
    }

    /**
     * Unblock users
     */
    private function unblock_users($ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sohoj_blocked_users';
        $ids = implode(',', array_map('absint', $ids));
        $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids)");
    }

    /**
     * Validate checkout
     */
    public function validate_checkout() {
        error_log('[SohojSecureOrder Debug] Running validate_checkout.');

        if (get_option('sohoj_ip_blocking_enabled', 0) != 1) {
            error_log('[SohojSecureOrder Debug] IP blocking is disabled. Skipping check.');
            return;
        }

        $ip = $this->get_user_ip();
        $phone = isset($_POST['billing_phone']) ? $_POST['billing_phone'] : '';

        error_log('[SohojSecureOrder Debug] Checkout validation for IP: ' . $ip . ' | Phone: ' . $phone);

        if (empty($phone)) {
            error_log('[SohojSecureOrder Debug] No phone number provided in checkout. Skipping check.');
            return;
        }

        if ($this->is_blocked($ip, $phone)) {
            error_log('[SohojSecureOrder Debug] BLOCKING checkout for user. IP: ' . $ip . ' | Phone: ' . $phone);
            wc_add_notice('You are currently blocked from placing new orders. Please contact us for assistance.', 'error');
        } else {
            error_log('[SohojSecureOrder Debug] User is not blocked. Allowing checkout.');
        }
    }

    /**
     * Check if a user is blocked
     */
    private function is_blocked($ip, $phone) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sohoj_blocked_users';
        $normalized_phone = Phone_Validator::normalize_bangladeshi_phone($phone);

        $blocked_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ip_address = %s OR phone_number = %s", $ip, $normalized_phone));
        return $blocked_user !== null;
    }

    /**
     * Get user IP
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Add block user bulk action
     */
    public function add_block_user_bulk_action($bulk_actions) {
        $bulk_actions['block_user'] = __('Block User', 'sohoj-secure-order');
        return $bulk_actions;
    }

    /**
     * Handle block user bulk action
     */
    public function handle_block_user_bulk_action($redirect_to, $action, $order_ids) {
        if ($action !== 'block_user') {
            return $redirect_to;
        }

        $blocked_count = 0;
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $ip = $order->get_customer_ip_address();
                $phone = $order->get_billing_phone();
                $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                
                if ($this->block_user($ip, $phone, $name)) {
                    $blocked_count++;
                }
            }
        }

        $redirect_to = add_query_arg(
            array(
                'bulk_action' => 'block_user',
                'changed' => $blocked_count,
            ),
            $redirect_to
        );

        return $redirect_to;
    }

    /**
     * Admin notice for block user bulk action
     */
    public function block_user_admin_notice() {
        if (empty($_REQUEST['bulk_action']) || $_REQUEST['bulk_action'] !== 'block_user') {
            return;
        }

        if (empty($_REQUEST['changed'])) {
            return;
        }

        $count = intval($_REQUEST['changed']);

        printf('<div id="message" class="updated notice is-dismissible"><p>' .
            _n('%s user blocked.', '%s users blocked.', $count, 'sohoj-secure-order')
            . '</p></div>', $count);
    }
}