<?php

/**
 * User Sync Admin Page
 * 
 * Admin interface for testing user synchronization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for user sync
 */
function tms_add_user_sync_admin_menu() {
    add_management_page(
        'User Sync Test',
        'User Sync Test',
        'manage_options',
        'tms-user-sync',
        'tms_user_sync_admin_page'
    );
}
add_action('admin_menu', 'tms_add_user_sync_admin_menu');

/**
 * Admin page content
 */
function tms_user_sync_admin_page() {
    $sync_api = new TMSUserSyncAPI();
    $message = '';
    $message_type = '';
    
    // Handle form submissions
    if (isset($_POST['action'])) {
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'test_connection':
                $result = $sync_api->test_connection();
                if ($result['success']) {
                    $message = 'Connection test successful!';
                    $message_type = 'success';
                } else {
                    $message = 'Connection test failed: ' . $result['error'];
                    if (isset($result['details'])) {
                        $message .= ' (' . $result['details'] . ')';
                    }
                    $message_type = 'error';
                }
                break;
                
            case 'test_server':
                $result = $sync_api->test_server_availability();
                if ($result['success']) {
                    $message = 'Server test successful! Response code: ' . $result['response_code'];
                    $message_type = 'success';
                } else {
                    $message = 'Server test failed: ' . $result['error'];
                    if (isset($result['details'])) {
                        $message .= ' (' . $result['details'] . ')';
                    }
                    $message_type = 'error';
                }
                break;
                
            case 'sync_user':
                $user_id = intval($_POST['user_id']);
                $sync_type = sanitize_text_field($_POST['sync_type']);
                $result = tms_manual_sync_user($user_id, $sync_type);
                if ($result['success']) {
                    $message = "User sync successful! Type: {$sync_type}";
                    $message_type = 'success';
                } else {
                    $message = 'User sync failed: ' . $result['error'];
                    $message_type = 'error';
                }
                break;
                
            case 'update_webhook_url':
                $new_url = sanitize_url($_POST['webhook_url']);
                $sync_api->set_webhook_url($new_url);
                $message = 'Webhook URL updated successfully!';
                $message_type = 'success';
                break;
                
            case 'update_api_key':
                $api_key = sanitize_text_field($_POST['api_key']);
                update_option('tms_sync_api_key', $api_key);
                $message = 'API Key updated successfully!';
                $message_type = 'success';
                break;
                
            case 'toggle_sync':
                $sync_enabled = isset($_POST['sync_enabled']) ? 1 : 0;
                update_option('tms_sync_enabled', $sync_enabled);
                $message = $sync_enabled ? 'Sync enabled successfully!' : 'Sync disabled successfully!';
                $message_type = 'success';
                break;
        }
    }
    
    // Get all users for testing
    $users = get_users(array('number' => 50));
    
    ?>
    <div class="wrap">
        <h1>User Sync Test</h1>
        
        <?php if ($message): ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Current Settings</h2>
            <p><strong>Webhook URL:</strong> <?php echo esc_html($sync_api->get_webhook_url()); ?></p>
            <p><strong>API Key:</strong> <?php 
                $api_key = get_option('tms_sync_api_key', '');
                echo $api_key ? esc_html(substr($api_key, 0, 8) . '...') : 'Not set';
            ?></p>
            <p><strong>Sync Status:</strong> <?php 
                $sync_enabled = get_option('tms_sync_enabled', 1);
                echo $sync_enabled ? '<span style="color: green;">Enabled</span>' : '<span style="color: red;">Disabled</span>';
            ?></p>
        </div>
        
        <div class="card">
            <h2>Update Webhook URL</h2>
            <form method="post">
                <input type="hidden" name="action" value="update_webhook_url">
                <table class="form-table">
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <input type="url" name="webhook_url" value="<?php echo esc_attr($sync_api->get_webhook_url()); ?>" class="regular-text" required>
                            <p class="description">Current: <?php echo esc_html($sync_api->get_webhook_url()); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update Webhook URL'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Update API Key</h2>
            <form method="post">
                <input type="hidden" name="action" value="update_api_key">
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="api_key" value="<?php echo esc_attr(get_option('tms_sync_api_key', '')); ?>" class="regular-text" required>
                            <p class="description">Enter the API key for webhook authentication</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update API Key'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Sync Control</h2>
            <form method="post">
                <input type="hidden" name="action" value="toggle_sync">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="sync_enabled" value="1" <?php checked(get_option('tms_sync_enabled', 1), 1); ?>>
                                Enable automatic synchronization
                            </label>
                            <p class="description">When disabled, sync requests will be logged but not sent</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update Sync Settings'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Test Server</h2>
            <p>Test if the server is reachable (without API key).</p>
            <form method="post">
                <input type="hidden" name="action" value="test_server">
                <?php submit_button('Test Server', 'secondary'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Test Connection</h2>
            <p>Test the full connection to the webhook URL with API key.</p>
            <form method="post">
                <input type="hidden" name="action" value="test_connection">
                <?php submit_button('Test Connection', 'secondary'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Manual User Sync</h2>
            <p>Manually sync a user to test the functionality.</p>
            <form method="post">
                <input type="hidden" name="action" value="sync_user">
                <table class="form-table">
                    <tr>
                        <th scope="row">Select User</th>
                        <td>
                            <select name="user_id" required>
                                <option value="">Choose a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->ID; ?>">
                                        <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sync Type</th>
                        <td>
                            <select name="sync_type" required>
                                <option value="add">Add</option>
                                <option value="update" selected>Update</option>
                                <option value="delete">Delete</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Sync User', 'primary'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Recent Sync Logs</h2>
            <p>Recent synchronization activity from WordPress error log:</p>
            <?php
            // Get recent logs
            $recent_logs = tms_get_recent_sync_logs();
            if (!empty($recent_logs)) {
                echo '<div class="sync-logs">';
                foreach ($recent_logs as $log) {
                    $log_class = 'log-info';
                    if (strpos($log, 'ERROR') !== false) {
                        $log_class = 'log-error';
                    } elseif (strpos($log, 'SUCCESS') !== false) {
                        $log_class = 'log-success';
                    }
                    echo '<div class="log-entry ' . $log_class . '">' . esc_html($log) . '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>No recent sync logs found. Try creating, updating, or deleting a user to see logs.</p>';
            }
            ?>
            <p><strong>Manual log check:</strong> <code>tail -f /path/to/wordpress/wp-content/debug.log | grep "TMS User Sync"</code></p>
        </div>
    </div>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin: 20px 0;
        padding: 20px;
    }
    .card h2 {
        margin-top: 0;
    }
    .sync-logs {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
        background: #f9f9f9;
        margin: 10px 0;
    }
    .log-entry {
        padding: 5px;
        margin: 2px 0;
        font-family: monospace;
        font-size: 12px;
        border-left: 3px solid #ccc;
        padding-left: 10px;
    }
    .log-entry.log-success {
        border-left-color: #46b450;
        background: #f0f8f0;
    }
    .log-entry.log-error {
        border-left-color: #dc3232;
        background: #fdf0f0;
    }
    .log-entry.log-info {
        border-left-color: #00a0d2;
        background: #f0f8ff;
    }
    </style>
    <?php
}

/**
 * Get recent sync logs from WordPress error log
 * 
 * @param int $limit Number of recent logs to retrieve
 * @return array Array of log entries
 */
function tms_get_recent_sync_logs($limit = 20) {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    $logs = array();
    
    if (!file_exists($log_file)) {
        // Try to create the log file if it doesn't exist
        if (is_writable(WP_CONTENT_DIR)) {
            touch($log_file);
            chmod($log_file, 0644);
        } else {
            return array('Log file does not exist and cannot be created. Check file permissions.');
        }
    }
    
    // Check if file is readable
    if (!is_readable($log_file)) {
        return array('Log file exists but is not readable. Check file permissions.');
    }
    
    // Read the last part of the log file
    $file_size = filesize($log_file);
    if ($file_size === 0) {
        return array('Log file is empty. Try creating, updating, or deleting a user to generate logs.');
    }
    
    $read_size = min($file_size, 50000); // Read last 50KB
    
    $handle = fopen($log_file, 'r');
    if (!$handle) {
        return array('Cannot open log file for reading.');
    }
    
    // Seek to the end minus read_size
    fseek($handle, max(0, $file_size - $read_size));
    
    // Skip the first line as it might be incomplete
    fgets($handle);
    
    // Read lines and filter for TMS User Sync
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, 'TMS User Sync') !== false) {
            $logs[] = trim($line);
        }
    }
    
    fclose($handle);
    
    // If no TMS User Sync logs found, return a helpful message
    if (empty($logs)) {
        return array('No TMS User Sync logs found. Make sure WP_DEBUG_LOG is enabled in wp-config.php and try creating/updating/deleting a user.');
    }
    
    // Return the most recent logs (reverse order)
    return array_slice(array_reverse($logs), 0, $limit);
}
