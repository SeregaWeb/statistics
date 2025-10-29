<?php
/**
 * Dark Mode Management Class
 * Handles AJAX requests for dark mode toggle and cookie management
 */

if (!defined('ABSPATH')) {
    exit;
}

class DarkMode {
    
    private $cookie_name = 'dark_mode_enabled';
    private $cookie_expiry = 30 * DAY_IN_SECONDS; // 30 days
    
    public function __construct() {
        add_action('wp_ajax_toggle_dark_mode', [$this, 'ajax_toggle_dark_mode']);
        add_action('wp_ajax_nopriv_toggle_dark_mode', [$this, 'ajax_toggle_dark_mode']);
    }
    
    /**
     * Handle AJAX request to toggle dark mode
     */
    public function ajax_toggle_dark_mode() {
        $is_enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';
        
        // Set cookie
        $this->set_dark_mode_cookie($is_enabled);
        
        // Return success response
        wp_send_json_success([
            'enabled' => $is_enabled,
            'message' => $is_enabled ? 'Dark mode enabled' : 'Dark mode disabled'
        ]);
    }
    
    /**
     * Set dark mode cookie
     */
    private function set_dark_mode_cookie($enabled) {
        $value = $enabled ? '1' : '0';
        setcookie($this->cookie_name, $value, time() + $this->cookie_expiry, COOKIEPATH, COOKIE_DOMAIN);
    }
    
    /**
     * Check if dark mode is enabled via cookie
     */
    public function is_dark_mode_enabled() {
        return isset($_COOKIE[$this->cookie_name]) && $_COOKIE[$this->cookie_name] === '1';
    }
    
    /**
     * Get body class for dark mode
     */
    public function get_body_class() {
        return $this->is_dark_mode_enabled() ? 'dark-mode' : '';
    }
}

// Initialize the class
new DarkMode();
