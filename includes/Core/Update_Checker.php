<?php
/**
 * Update Checker
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Update Checker Class
 */
class Update_Checker {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize update checker
     */
    private function init() {
        // Add admin notices
        add_action('admin_notices', array($this, 'show_update_notices'));
        
        // Add plugin row meta
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        
        // Add update action to plugins page
        add_action('admin_action_sohoj_update_plugin', array($this, 'update_plugin'));
        
        // Add update link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(SOHOJ_PLUGIN_FILE), array($this, 'add_update_link'));
    }
    
    /**
     * Check for updates
     */
    private function check_for_updates() {
        $current_version = SOHOJ_PLUGIN_VERSION;
        $latest_version = $this->get_latest_version();
        
        if ($latest_version && version_compare($latest_version, $current_version, '>')) {
            update_option('sohoj_latest_version', $latest_version);
            update_option('sohoj_update_available', true);
            return true;
        } else {
            update_option('sohoj_update_available', false);
            return false;
        }
    }
    
    /**
     * Get latest version from JSON endpoint
     */
    private function get_latest_version() {
        $json_url = 'https://raw.githubusercontent.com/webdevarif/SohojSecureOrder/main/update.json';
        
        $response = wp_remote_get($json_url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'SohojSecureOrder/' . SOHOJ_PLUGIN_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data && isset($data['version'])) {
            return $data['version'];
        }
        
        return false;
    }
    
    /**
     * Show update notices
     */
    public function show_update_notices() {
        $update_available = $this->check_for_updates();
        $latest_version = get_option('sohoj_latest_version');
        
        if ($update_available && $latest_version) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Sohoj Secure Order:</strong> 
                    A new version (<?php echo esc_html($latest_version); ?>) is available. 
                    <a href="<?php echo esc_url(admin_url('admin.php?action=sohoj_update_plugin&_wpnonce=' . wp_create_nonce('sohoj_update_nonce'))); ?>" class="button button-secondary" style="margin-left: 10px;">
                        Update Now
                    </a>
                    <a href="https://github.com/webdevarif/SohojSecureOrder/releases" target="_blank" class="button button-link">
                        View Release Notes
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Add plugin row meta
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(SOHOJ_PLUGIN_FILE) === $file) {
            $links[] = '<a href="https://github.com/webdevarif/SohojSecureOrder" target="_blank">View on GitHub</a>';
            
            $update_available = get_option('sohoj_update_available', false);
            $latest_version = get_option('sohoj_latest_version');
            
            if ($update_available && $latest_version) {
                $links[] = '<strong style="color: #d63638;">Update available (v' . esc_html($latest_version) . ')</strong>';
            }
        }
        return $links;
    }
    
    /**
     * Add update link to plugins page
     */
    public function add_update_link($links) {
        $update_available = get_option('sohoj_update_available', false);
        $latest_version = get_option('sohoj_latest_version');
        
        if ($update_available && $latest_version) {
            $update_link = '<a href="' . esc_url(admin_url('admin.php?action=sohoj_update_plugin&_wpnonce=' . wp_create_nonce('sohoj_update_nonce'))) . '" style="color: #2271b1; font-weight: 600;">Update to v' . esc_html($latest_version) . '</a>';
            array_unshift($links, $update_link);
        } else {
            $check_link = '<a href="' . esc_url(admin_url('plugins.php?sohoj_check_updates=1')) . '" style="color: #646970;">Check for Updates</a>';
            array_unshift($links, $check_link);
        }
        
        return $links;
    }
    
    /**
     * Update plugin
     */
    public function update_plugin() {
        // Check nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'sohoj_update_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('update_plugins')) {
            wp_die('Insufficient permissions');
        }
        
        $latest_version = get_option('sohoj_latest_version');
        
        if (!$latest_version) {
            wp_die('No update available');
        }
        
        // Get download URL
        $download_url = 'https://github.com/webdevarif/SohojSecureOrder/archive/refs/heads/main.zip';
        
        // Download and update
        $result = $this->download_and_update($download_url);
        
        if ($result) {
            // Update version in database
            update_option('sohoj_plugin_version', $latest_version);
            update_option('sohoj_update_available', false);
            
            // Redirect back to plugins page
            wp_redirect(admin_url('plugins.php?updated=true'));
            exit;
        } else {
            wp_die('Update failed. Please download and install manually.');
        }
    }
    
    /**
     * Download and update plugin
     */
    private function download_and_update($download_url) {
        // Get WordPress filesystem
        global $wp_filesystem;
        
        if (!$wp_filesystem) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }
        
        // Download the file
        $temp_file = download_url($download_url);
        
        if (is_wp_error($temp_file)) {
            return false;
        }
        
        // Extract the zip file
        $unzipped = unzip_file($temp_file, WP_PLUGIN_DIR);
        
        // Clean up temp file
        unlink($temp_file);
        
        if (is_wp_error($unzipped)) {
            return false;
        }
        
        return true;
    }
} 