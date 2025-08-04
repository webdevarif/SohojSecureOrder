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
        $this->init();
    }
    
    /**
     * Initialize update checker
     */
    private function init() {
        // Check for updates on admin init
        add_action('admin_init', array($this, 'check_for_updates'));
        
        // Add admin notice for updates
        add_action('admin_notices', array($this, 'show_update_notice'));
    }
    
    /**
     * Check for updates
     */
    public function check_for_updates() {
        $current_version = get_option('sohoj_plugin_version', SOHOJ_PLUGIN_VERSION);
        $latest_version = $this->get_latest_version();
        
        if ($latest_version && version_compare($latest_version, $current_version, '>')) {
            update_option('sohoj_latest_version', $latest_version);
        }
    }
    
    /**
     * Get latest version from GitHub
     */
    private function get_latest_version() {
        $github_url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';
        
        $response = wp_remote_get($github_url, array(
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
        
        if ($data && isset($data['tag_name'])) {
            return ltrim($data['tag_name'], 'v');
        }
        
        return false;
    }
    
    /**
     * Show update notice
     */
    public function show_update_notice() {
        $latest_version = get_option('sohoj_latest_version');
        $current_version = get_option('sohoj_plugin_version', SOHOJ_PLUGIN_VERSION);
        
        if ($latest_version && version_compare($latest_version, $current_version, '>')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Sohoj Secure Order:</strong> 
                    A new version (<?php echo esc_html($latest_version); ?>) is available. 
                    <a href="https://github.com/<?php echo esc_html($this->github_repo); ?>/releases" target="_blank">
                        Download now
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Get download URL
     */
    public function get_download_url() {
        return 'https://github.com/' . $this->github_repo . '/archive/refs/heads/main.zip';
    }
} 