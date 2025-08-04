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
 * GitHub Update Checker
 */
class Update_Checker {
    
    /**
     * GitHub repository
     */
    private $github_repo = 'webdevarif/SohojSecureOrder';
    
    /**
     * Constructor
     */
    public function __construct() {
        error_log('Sohoj Update Checker: Constructor called');
        $this->init();
    }
    
    /**
     * Initialize update checker
     */
    private function init() {
        error_log('Sohoj Update Checker: Initializing update checker');
        
        // Check for updates on admin init
        add_action('admin_init', array($this, 'check_for_updates'));
        
        // Add admin notice for updates
        add_action('admin_notices', array($this, 'show_update_notice'));
        
        // Add update row to plugins page
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        
        // Add update action to plugins page
        add_action('admin_action_sohoj_update_plugin', array($this, 'update_plugin'));
        
        // Add update link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(SOHOJ_PLUGIN_FILE), array($this, 'add_update_link'));
        
        // Add AJAX handler for checking updates
        add_action('wp_ajax_sohoj_check_updates_ajax', array($this, 'check_updates_ajax'));
        
        // Handle manual check for updates from plugins page
        if (isset($_GET['sohoj_check_updates'])) {
            add_action('admin_init', array($this, 'handle_manual_check'));
        }
        
        error_log('Sohoj Update Checker: Hooks registered successfully');
    }
    
    
    /**
     * Check for updates
     */
    public function check_for_updates() {
        $current_version = get_option('sohoj_plugin_version', SOHOJ_PLUGIN_VERSION);
        $latest_version = $this->get_latest_version();
        
        error_log('Sohoj Update Checker: Current version: ' . $current_version);
        error_log('Sohoj Update Checker: Latest version: ' . ($latest_version ? $latest_version : 'false'));
        
        
        if ($latest_version && version_compare($latest_version, $current_version, '>')) {
            update_option('sohoj_latest_version', $latest_version);
            update_option('sohoj_update_available', true);
            error_log('Sohoj Update Checker: Update available - ' . $latest_version);
        } else {
            update_option('sohoj_update_available', false);
            error_log('Sohoj Update Checker: No update available');
        }
    }
    
    /**
     * AJAX handler for checking updates
     */
    public function check_updates_ajax() {
        check_ajax_referer('sohoj_admin_nonce', 'nonce');
        
        $this->check_for_updates();
        
        $update_available = get_option('sohoj_update_available', false);
        $latest_version = get_option('sohoj_latest_version');
        
        wp_send_json_success(array(
            'update_available' => $update_available,
            'latest_version' => $latest_version
        ));
    }
    
    /**
     * Handle manual check for updates from plugins page
     */
    public function handle_manual_check() {
        // Check if user has proper permissions
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        $this->check_for_updates();
        
        // Redirect back to plugins page to show results
        wp_redirect(admin_url('plugins.php'));
        exit;
    }
    
    /**
     * Get latest version from GitHub
     */
    private function get_latest_version() {
        // First try to get the latest release
        $github_url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';
        
        error_log('Sohoj Update Checker: Checking GitHub URL: ' . $github_url);
        
        $response = wp_remote_get($github_url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'SohojSecureOrder/' . SOHOJ_PLUGIN_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Sohoj Update Checker: GitHub API error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        error_log('Sohoj Update Checker: GitHub response: ' . substr($body, 0, 200) . '...');
        
        if ($data && isset($data['tag_name'])) {
            $version = ltrim($data['tag_name'], 'v');
            error_log('Sohoj Update Checker: Found version: ' . $version);
            return $version;
        }
        
        // If no releases found, check the main branch
        error_log('Sohoj Update Checker: No releases found, checking main branch');
        $main_url = 'https://api.github.com/repos/' . $this->github_repo . '/branches/main';
        
        $main_response = wp_remote_get($main_url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'SohojSecureOrder/' . SOHOJ_PLUGIN_VERSION
            )
        ));
        
        if (!is_wp_error($main_response)) {
            $main_body = wp_remote_retrieve_body($main_response);
            $main_data = json_decode($main_body, true);
            
            if ($main_data && isset($main_data['commit']['sha'])) {
                // Use a version based on the commit SHA
                $commit_sha = substr($main_data['commit']['sha'], 0, 7);
                $version = '1.0.1-' . $commit_sha;
                error_log('Sohoj Update Checker: Using main branch version: ' . $version);
                return $version;
            }
        }
        
        error_log('Sohoj Update Checker: No version found in response');
        return false;
    }
    
    /**
     * Show update notice
     */
    public function show_update_notice() {
        $update_available = get_option('sohoj_update_available', false);
        $latest_version = get_option('sohoj_latest_version');
        
        error_log('Sohoj Update Checker: Show notice - Update available: ' . ($update_available ? 'true' : 'false') . ', Latest version: ' . ($latest_version ? $latest_version : 'false'));
        
        if ($update_available && $latest_version) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Sohoj Secure Order:</strong> 
                    Version <?php echo esc_html($latest_version); ?> is available. 
                    <a href="<?php echo esc_url(admin_url('admin.php?action=sohoj_update_plugin&_wpnonce=' . wp_create_nonce('sohoj_update_nonce'))); ?>" class="button button-secondary" style="margin-left: 10px;">
                        Update Now
                    </a>
                    <a href="https://github.com/<?php echo esc_html($this->github_repo); ?>/releases" target="_blank" class="button button-link">
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
            $update_available = get_option('sohoj_update_available', false);
            $latest_version = get_option('sohoj_latest_version');
            
            error_log('Sohoj Update Checker: Plugin row meta - File: ' . $file . ', Update available: ' . ($update_available ? 'true' : 'false') . ', Latest version: ' . ($latest_version ? $latest_version : 'false'));
            
            // Always show GitHub link
            $links[] = '<a href="https://github.com/' . esc_html($this->github_repo) . '" target="_blank">GitHub</a>';
            
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
        
        error_log('Sohoj Update Checker: Add update link - Update available: ' . ($update_available ? 'true' : 'false') . ', Latest version: ' . ($latest_version ? $latest_version : 'false'));
        
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
        $download_url = $this->get_download_url();
        
        if (!$download_url) {
            wp_die('Could not get download URL');
        }
        
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
    
    /**
     * Get download URL
     */
    public function get_download_url() {
        return 'https://github.com/' . $this->github_repo . '/archive/refs/heads/main.zip';
    }

} 