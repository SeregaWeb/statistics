<?php

/**
 * Chat Sync Admin Page
 * 
 * Admin interface for testing chat synchronization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for chat sync
 */
function tms_add_chat_sync_admin_menu() {
    add_management_page(
        'Chat Sync Test',
        'Chat Sync Test',
        'manage_options',
        'tms-chat-sync',
        'tms_chat_sync_admin_page'
    );
}
add_action('admin_menu', 'tms_add_chat_sync_admin_menu');

/**
 * Admin page content
 */
function tms_chat_sync_admin_page() {
    $message = '';
    $message_type = '';
    
    // Default URLs
    $default_create_url = 'https://odyssea-beckend.onrender.com/v1/create_load_chat';
    $default_update_url = 'https://odyssea-beckend.onrender.com/v1/update_load_chat';
    
    // Get saved URLs from options
    $create_chat_url = get_option('tms_chat_create_url', $default_create_url);
    $update_chat_url = get_option('tms_chat_update_url', $default_update_url);
    $chat_api_key = get_option('tms_chat_api_key', '');
    
    // Handle form submissions
    if (isset($_POST['action'])) {
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'update_create_url':
                $new_url = sanitize_url($_POST['create_chat_url']);
                update_option('tms_chat_create_url', $new_url);
                $create_chat_url = $new_url;
                $message = 'Create Chat URL updated successfully!';
                $message_type = 'success';
                break;
                
            case 'update_update_url':
                $new_url = sanitize_url($_POST['update_chat_url']);
                update_option('tms_chat_update_url', $new_url);
                $update_chat_url = $new_url;
                $message = 'Update Chat URL updated successfully!';
                $message_type = 'success';
                break;
                
            case 'update_chat_api_key':
                $api_key = sanitize_text_field($_POST['chat_api_key']);
                update_option('tms_chat_api_key', $api_key);
                $chat_api_key = $api_key;
                $message = 'Chat API Key updated successfully!';
                $message_type = 'success';
                break;
                
            case 'test_create_chat':
                $load_id = sanitize_text_field($_POST['load_id']);
                $title = sanitize_text_field($_POST['chat_title']);
				$company = isset($_POST['company']) ? sanitize_text_field($_POST['company']) : 'Odysseia';
                $participants_json = stripslashes($_POST['participants_json']);
                
                $participants = json_decode($participants_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
					$message = 'Invalid JSON format for participants: ' . json_last_error_msg();
                    $message_type = 'error';
                } else {
					$result = tms_test_create_chat($load_id, $title, $company, $participants, $create_chat_url, $chat_api_key);
                    if ($result['success']) {
                        $message = 'Create chat test successful! Response: ' . $result['response_body'];
                        $message_type = 'success';
                    } else {
                        $message = 'Create chat test failed: ' . $result['error'];
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'test_update_chat':
                $load_id = sanitize_text_field($_POST['load_id']);
                $participants_json = stripslashes($_POST['participants_json']);
                
                $participants = json_decode($participants_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $message = 'Invalid JSON format for participants: ' . json_last_error_msg();
                    $message_type = 'error';
                } else {
                    $result = tms_test_update_chat($load_id, $participants, $update_chat_url, $chat_api_key);
                    if ($result['success']) {
                        $message = 'Update chat test successful! Response: ' . $result['response_body'];
                        $message_type = 'success';
                    } else {
                        $message = 'Update chat test failed: ' . $result['error'];
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Chat Sync Test</h1>
        
        <?php if ($message): ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Current Settings</h2>
            <p><strong>Create Chat URL:</strong> <?php echo esc_html($create_chat_url); ?></p>
            <p><strong>Update Chat URL:</strong> <?php echo esc_html($update_chat_url); ?></p>
            <p><strong>API Key:</strong> <?php 
                echo $chat_api_key ? esc_html(substr($chat_api_key, 0, 8) . '...') : 'Not set';
            ?></p>
        </div>
        
        <div class="card">
            <h2>Update Create Chat URL</h2>
            <form method="post">
                <input type="hidden" name="action" value="update_create_url">
                <table class="form-table">
                    <tr>
                        <th scope="row">Create Chat URL</th>
                        <td>
                            <input type="url" name="create_chat_url" value="<?php echo esc_attr($create_chat_url); ?>" class="regular-text" required>
                            <p class="description">URL for creating load chat (POST request)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update Create Chat URL'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Update Update Chat URL</h2>
            <form method="post">
                <input type="hidden" name="action" value="update_update_url">
                <table class="form-table">
                    <tr>
                        <th scope="row">Update Chat URL</th>
                        <td>
                            <input type="url" name="update_chat_url" value="<?php echo esc_attr($update_chat_url); ?>" class="regular-text" required>
                            <p class="description">URL for updating load chat (POST request)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update Update Chat URL'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Update Chat API Key</h2>
            <form method="post">
                <input type="hidden" name="action" value="update_chat_api_key">
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="chat_api_key" value="<?php echo esc_attr($chat_api_key); ?>" class="regular-text">
                            <p class="description">Enter the API key for chat webhook authentication (optional)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update API Key'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Test Create Chat</h2>
            <p>Test creating a new load chat.</p>
            <form method="post">
                <input type="hidden" name="action" value="test_create_chat">
                <table class="form-table">
                    <tr>
                        <th scope="row">Load ID</th>
                        <td>
                            <input type="text" name="load_id" value="1" class="regular-text" required>
                            <p class="description">Load ID for the chat</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chat Title</th>
                        <td>
                            <input type="text" name="chat_title" value="test load chat" class="regular-text" required>
                            <p class="description">Title for the chat</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Company</th>
						<td>
							<input type="text" name="company" value="Odysseia" class="regular-text" required>
							<p class="description">Company name (for example: Odysseia)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Participants (JSON)</th>
                        <td>
                            <textarea name="participants_json" rows="8" class="large-text code" required>[
  {"id": "3343", "role": "driver"},
  {"id": "84", "role": "recruiter"},
  {"id": "14", "role": "moderator"},
  {"id": "83", "role": "subscriber"}
]</textarea>
                            <p class="description">JSON array of participants with id and role</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Test Create Chat', 'primary'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Test Update Chat</h2>
            <p>Test updating an existing load chat participants.</p>
            <form method="post">
                <input type="hidden" name="action" value="test_update_chat">
                <table class="form-table">
                    <tr>
                        <th scope="row">Load ID</th>
                        <td>
                            <input type="text" name="load_id" value="1" class="regular-text" required>
                            <p class="description">Load ID for the chat</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Participants (JSON)</th>
                        <td>
                            <textarea name="participants_json" rows="8" class="large-text code" required>[
  {"id": "3343", "role": "driver"},
  {"id": "84", "role": "recruiter"},
  {"id": "14", "role": "moderator"},
  {"id": "83", "role": "subscriber"}
]</textarea>
                            <p class="description">JSON array of participants with id and role</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Test Update Chat', 'primary'); ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Recent Chat Sync Logs</h2>
            <p>Recent chat synchronization activity:</p>
            <?php
            // Get recent logs from TMSLogger
            $log_file = WP_CONTENT_DIR . '/tms-logs/chat-sync.log';
            if (file_exists($log_file)) {
                $logs = file($log_file);
                if ($logs) {
                    $recent_logs = array_slice(array_reverse($logs), 0, 20);
                    echo '<div class="sync-logs">';
                    foreach ($recent_logs as $log) {
                        $log_class = 'log-info';
                        if (strpos($log, 'ERROR') !== false || strpos($log, 'FAILED') !== false) {
                            $log_class = 'log-error';
                        } elseif (strpos($log, 'SUCCESS') !== false) {
                            $log_class = 'log-success';
                        }
                        echo '<div class="log-entry ' . $log_class . '">' . esc_html(trim($log)) . '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>Log file is empty.</p>';
                }
            } else {
                echo '<p>No chat sync logs found yet. Try creating or updating a chat to generate logs.</p>';
            }
            ?>
            <p><strong>Log file location:</strong> <code><?php echo esc_html($log_file); ?></code></p>
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
 * Test create chat request
 * 
 * @param string $load_id Load ID
 * @param string $title Chat title
 * @param string $company Company name
 * @param array $participants Participants array
 * @param string $url Webhook URL
 * @param string $api_key API key
 * @return array Result
 */
function tms_test_create_chat($load_id, $title, $company, $participants, $url, $api_key = '') {
    if (class_exists('TMSLogger')) {
		TMSLogger::log_to_file('[TEST] Testing create chat - Load ID: ' . $load_id . ', Title: ' . $title . ', Company: ' . $company, 'chat-sync');
    }
    
    $payload = array(
        'load_id' => $load_id,
        'title' => $title,
		'company' => $company,
        'participants' => $participants
    );
    
    $headers = array(
        'Content-Type' => 'application/json',
        'User-Agent' => 'TMS-Statistics/1.0'
    );
    
    if (!empty($api_key)) {
        $headers['x-api-key'] = $api_key;
    }
    
    $args = array(
        'method' => 'POST',
        'timeout' => 30,
        'headers' => $headers,
        'body' => json_encode($payload)
    );
    
    if (class_exists('TMSLogger')) {
        TMSLogger::log_to_file('[TEST] Request URL: ' . $url, 'chat-sync');
        TMSLogger::log_to_file('[TEST] Request Payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE), 'chat-sync');
    }
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        $error_msg = 'Webhook request failed: ' . $response->get_error_message();
        if (class_exists('TMSLogger')) {
            TMSLogger::log_to_file('[ERROR] ' . $error_msg, 'chat-sync');
        }
        return array(
            'success' => false,
            'error' => $error_msg
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code >= 200 && $response_code < 300) {
        if (class_exists('TMSLogger')) {
            TMSLogger::log_to_file('[SUCCESS] Create chat successful - Response Code: ' . $response_code . ', Body: ' . $response_body, 'chat-sync');
        }
        return array(
            'success' => true,
            'response_code' => $response_code,
            'response_body' => $response_body
        );
    } else {
        $error_msg = 'Webhook returned error code: ' . $response_code;
        if (class_exists('TMSLogger')) {
            TMSLogger::log_to_file('[ERROR] ' . $error_msg . ' - Body: ' . $response_body, 'chat-sync');
        }
        return array(
            'success' => false,
            'error' => $error_msg,
            'response_body' => $response_body
        );
    }
}

/**
 * Test update chat request
 * 
 * @param string $load_id Load ID
 * @param array $participants Participants array
 * @param string $url Webhook URL
 * @param string $api_key API key
 * @return array Result
 */
function tms_test_update_chat($load_id, $participants, $url, $api_key = '') {
    if (class_exists('TMSLogger')) {
        TMSLogger::log_to_file('[TEST] Testing update chat - Load ID: ' . $load_id, 'chat-sync');
    }
    
    $payload = array(
        'load_id' => $load_id,
        'participants' => $participants
    );
    
    $headers = array(
        'Content-Type' => 'application/json',
        'User-Agent' => 'TMS-Statistics/1.0'
    );
    
    if (!empty($api_key)) {
        $headers['x-api-key'] = $api_key;
    }
    
    $args = array(
        'method' => 'POST',
        'timeout' => 30,
        'headers' => $headers,
        'body' => json_encode($payload)
    );
    
    if (class_exists('TMSLogger')) {
        TMSLogger::log_to_file('[TEST] Request URL: ' . $url, 'chat-sync');
        TMSLogger::log_to_file('[TEST] Request Payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE), 'chat-sync');
    }
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        $error_msg = 'Webhook request failed: ' . $response->get_error_message();
        if (class_exists('TMSLogger')) {
            TMSLogger::log_to_file('[ERROR] ' . $error_msg, 'chat-sync');
        }
        return array(
            'success' => false,
            'error' => $error_msg
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code >= 200 && $response_code < 300) {
        if (class_exists('TMSLogger')) {
            TMSLogger::log_to_file('[SUCCESS] Update chat successful - Response Code: ' . $response_code . ', Body: ' . $response_body, 'chat-sync');
        }
        return array(
            'success' => true,
            'response_code' => $response_code,
            'response_body' => $response_body
        );
    } else {
        $error_msg = 'Webhook returned error code: ' . $response_code;
        if (class_exists('TMSLogger')) {
            TMSLogger::log_to_file('[ERROR] ' . $error_msg . ' - Body: ' . $response_body, 'chat-sync');
        }
        return array(
            'success' => false,
            'error' => $error_msg,
            'response_body' => $response_body
        );
    }
}
