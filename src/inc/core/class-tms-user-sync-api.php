<?php

/**
 * TMS User Sync API Class
 * 
 * Handles synchronization of WordPress users with external services
 * via webhook calls for add/update/delete operations
 */
class TMSUserSyncAPI {
    
    /**
     * Webhook URL for synchronization
     */
    private $webhook_url;
    
    /**
     * API timeout in seconds
     */
    private $timeout = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load webhook URL from WordPress options, fallback to default
        $this->webhook_url = get_option('tms_sync_webhook_url', 'https://odyssea-backend-nestjs.onrender.com/v1/sync-db');
    }
    
    /**
     * Send user data to external service
     * 
     * @param string $type Operation type: 'add', 'update', 'delete'
     * @param WP_User|int $user WordPress user object or user ID
     * @param string $role User role: 'driver' or 'employee'
     * @return array Response data
     */
    public function sync_user($type, $user, $role = 'employee') {
        // Validate operation type
        if (!in_array($type, ['add', 'update', 'delete'])) {
            return array(
                'success' => false,
                'error' => 'Invalid operation type. Must be: add, update, or delete'
            );
        }
        
        // Get user object if ID was passed
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
            if (!$user) {
                return array(
                    'success' => false,
                    'error' => 'User not found'
                );
            }
        }
        
        // Prepare payload data
        $payload = $this->prepare_payload($type, $user, $role);
        
        // Send webhook request
        return $this->send_webhook($payload);
    }
    
    /**
     * Prepare payload data for webhook
     * 
     * @param string $type Operation type
     * @param WP_User|array $user WordPress user object or driver data array
     * @param string $role User role
     * @return array Payload data
     */
    private function prepare_payload($type, $user, $role) {
        $payload = array(
            'type' => $type,
            'role' => $role,
            'timestamp' => current_time('mysql'),
            'source' => 'tms-statistics'
        );
        
        // For delete operations, only send user/driver ID
        if ($type === 'delete') {
            if (is_object($user) && isset($user->ID)) {
                // WordPress user object
                $payload['user_id'] = $user->ID;
            } elseif (is_array($user) && isset($user['driver_id'])) {
                // Driver data array
                $payload['driver_id'] = $user['driver_id'];
            }
            return $payload;
        }
        
        // For add/update operations, send full user/driver data
        if (is_object($user) && isset($user->ID)) {
            // WordPress user object
            $user_roles = is_array($user->roles) ? $user->roles : array();
            $payload['user_data'] = array(
                'id' => $user->ID,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $user_roles,
                'user_registered' => $user->user_registered,
                'acf_fields' => array(
                    'permission_view' => function_exists('get_field') ? get_field('permission_view', 'user_' . $user->ID) : '',
                    'initials_color' => function_exists('get_field') ? get_field('initials_color', 'user_' . $user->ID) : '',
                    'work_location' => function_exists('get_field') ? get_field('work_location', 'user_' . $user->ID) : '',
                    'phone_number' => function_exists('get_field') ? get_field('phone_number', 'user_' . $user->ID) : '',
                    'flt' => function_exists('get_field') ? get_field('flt', 'user_' . $user->ID) : '',
                    'deactivate_account' => function_exists('get_field') ? get_field('deactivate_account', 'user_' . $user->ID) : ''
                )
            );
        } elseif (is_array($user) && isset($user['driver_id'])) {
            // Driver data array
            $payload['driver_data'] = array(
                'driver_id' => $user['driver_id'],
                'driver_name' => $user['driver_name'],
                'driver_email' => $user['driver_email'],
                'driver_phone' => $user['driver_phone'],
                'home_location' => $user['home_location'],
                'vehicle_type' => $user['vehicle_type'],
                'vin' => $user['vin'],
                'driver_status' => $user['driver_status'],
                'latitude' => $user['latitude'],
                'longitude' => $user['longitude'],
                'status_date' => $user['status_date'],
                'current_location' => $user['current_location'],
                'current_city' => $user['current_city'],
                'current_zipcode' => $user['current_zipcode'],
                'current_country' => $user['current_country']
            );
        }
        
        return $payload;
    }
    
    /**
     * Send webhook request to external service
     * 
     * @param array $payload Data to send
     * @return array Response data
     */
    private function send_webhook($payload) {
        // Log the attempt
        $this->log_sync_attempt($payload);
        
        // Check if sync is enabled
        $sync_enabled = get_option('tms_sync_enabled', 1);
        if (!$sync_enabled) {
            $this->log_sync_success($payload, 200, 'Sync disabled - request logged only');
            return array(
                'success' => true,
                'message' => 'Sync is disabled. Request logged but not sent.',
                'payload' => $payload
            );
        }
        
        // Get API key
        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            $this->log_sync_error($payload, 'API key not configured');
            return array(
                'success' => false,
                'error' => 'API key not configured. Please set it in admin settings.'
            );
        }
        
        // Prepare headers with API key
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'TMS-Statistics/1.0',
            'x-api-key' => $api_key
        );
        
        $args = array(
            'method' => 'POST',
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => json_encode($payload)
        );
        
        // Log the request details for debugging
        error_log('TMS User Sync API - Request URL: ' . $this->webhook_url);
        error_log('TMS User Sync API - Request Headers: ' . json_encode($headers));
        error_log('TMS User Sync API - Request Body: ' . json_encode($payload));
        
        $response = wp_remote_post($this->webhook_url, $args);
        
        if (is_wp_error($response)) {
            $this->log_sync_error($payload, 'Webhook request failed: ' . $response->get_error_message());
            return array(
                'success' => false,
                'error' => 'Webhook request failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            $this->log_sync_success($payload, $response_code, $response_body);
            return array(
                'success' => true,
                'response_code' => $response_code,
                'response_body' => $response_body
            );
        } else {
            $this->log_sync_error($payload, 'Webhook returned error code: ' . $response_code, $response_body);
            return array(
                'success' => false,
                'error' => 'Webhook returned error code: ' . $response_code,
                'response_body' => $response_body
            );
        }
    }
    
    /**
     * Set webhook URL
     * 
     * @param string $url Webhook URL
     */
    public function set_webhook_url($url) {
        $this->webhook_url = $url;
        // Save to WordPress options for persistence
        update_option('tms_sync_webhook_url', $url);
    }
    
    /**
     * Get current webhook URL
     * 
     * @return string Current webhook URL
     */
    public function get_webhook_url() {
        return $this->webhook_url;
    }
    
    /**
     * Get API key from WordPress options
     * 
     * @return string API key
     */
    private function get_api_key() {
        return get_option('tms_sync_api_key', '');
    }
    
    /**
     * Test webhook connection
     * 
     * @return array Test result
     */
    public function test_connection() {
        // First, test if the server is reachable
        $server_test = $this->test_server_availability();
        if (!$server_test['success']) {
            return $server_test;
        }
        
        $test_payload = array(
            'type' => 'add',
            'role' => 'driver',
            'timestamp' => current_time('mysql'),
            'source' => 'tms-statistics',
            'driver_data' => array(
                'driver_id' => 'test-connection-' . time(),
                'driver_name' => 'Test Connection',
                'driver_email' => 'test@example.com'
            )
        );
        
        return $this->send_webhook($test_payload);
    }
    
    /**
     * Test if the server is reachable
     * 
     * @return array Test result
     */
    public function test_server_availability() {
        $parsed_url = parse_url($this->webhook_url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        // Try to reach the base URL first
        $response = wp_remote_get($base_url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Server not reachable: ' . $response->get_error_message(),
                'details' => 'Base URL: ' . $base_url
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        return array(
            'success' => true,
            'message' => 'Server is reachable',
            'response_code' => $response_code,
            'base_url' => $base_url
        );
    }
    
    /**
     * Log sync attempt
     * 
     * @param array $payload Payload data
     */
    private function log_sync_attempt($payload) {
        $entity_id = 'N/A';
        if (isset($payload['user_data']['id'])) {
            $entity_id = $payload['user_data']['id'];
        } elseif (isset($payload['user_id'])) {
            $entity_id = $payload['user_id'];
        } elseif (isset($payload['driver_data']['driver_id'])) {
            $entity_id = $payload['driver_data']['driver_id'];
        } elseif (isset($payload['driver_id'])) {
            $entity_id = $payload['driver_id'];
        }
        
        $log_message = sprintf(
            "[TMS USER SYNC] ATTEMPT - Type: %s, Role: %s, Entity ID: %s, Timestamp: %s",
            $payload['type'],
            isset($payload['role']) ? $payload['role'] : 'N/A',
            $entity_id,
            $payload['timestamp']
        );
        
        error_log($log_message);
    }
    
    /**
     * Log successful sync
     * 
     * @param array $payload Payload data
     * @param int $response_code HTTP response code
     * @param string $response_body Response body
     */
    private function log_sync_success($payload, $response_code, $response_body) {
        $entity_id = 'N/A';
        if (isset($payload['user_data']['id'])) {
            $entity_id = $payload['user_data']['id'];
        } elseif (isset($payload['user_id'])) {
            $entity_id = $payload['user_id'];
        } elseif (isset($payload['driver_data']['driver_id'])) {
            $entity_id = $payload['driver_data']['driver_id'];
        } elseif (isset($payload['driver_id'])) {
            $entity_id = $payload['driver_id'];
        }
        
        $log_message = sprintf(
            "[TMS USER SYNC] SUCCESS - Type: %s, Role: %s, Entity ID: %s, Response Code: %d, Timestamp: %s",
            $payload['type'],
            $payload['role'],
            $entity_id,
            $response_code,
            $payload['timestamp']
        );
        
        error_log($log_message);
        error_log("[TMS USER SYNC] SUCCESS - Response Body: " . $response_body);
    }
    
    /**
     * Log sync error
     * 
     * @param array $payload Payload data
     * @param string $error_message Error message
     * @param string $response_body Response body (optional)
     */
    private function log_sync_error($payload, $error_message, $response_body = '') {
        $entity_id = 'N/A';
        if (isset($payload['user_data']['id'])) {
            $entity_id = $payload['user_data']['id'];
        } elseif (isset($payload['user_id'])) {
            $entity_id = $payload['user_id'];
        } elseif (isset($payload['driver_data']['driver_id'])) {
            $entity_id = $payload['driver_data']['driver_id'];
        } elseif (isset($payload['driver_id'])) {
            $entity_id = $payload['driver_id'];
        }
        
        $log_message = sprintf(
            "[TMS USER SYNC] ERROR - Type: %s, Role: %s, Entity ID: %s, Error: %s, Timestamp: %s",
            $payload['type'],
            isset($payload['role']) ? $payload['role'] : 'N/A',
            $entity_id,
            $error_message,
            $payload['timestamp']
        );
        
        error_log($log_message);
        if (!empty($response_body)) {
            error_log("[TMS USER SYNC] ERROR - Response Body: " . $response_body);
        }
    }
}
