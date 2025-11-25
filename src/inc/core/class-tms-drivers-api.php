<?php

/**
 * TMS Drivers API Class
 * 
 * Provides API endpoints for driver data access
 * 
 * @package WP-rock
 * @since 1.0.0
 */

class TMSDriversAPI {
    
    /**
     * API namespace
     */
    const API_NAMESPACE = 'tms/v1';
    
    /**
     * API endpoints
     */
    const ENDPOINT_DRIVER = 'driver';
    const ENDPOINT_DRIVER_UPDATE = 'driver/update';
    const ENDPOINT_DRIVER_LOCATION_UPDATE = 'driver/location/update';
    const ENDPOINT_DRIVERS = 'drivers';
    const ENDPOINT_DRIVER_LOADS = 'driver/loads';
    const ENDPOINT_LOAD_DETAIL = 'load';
    const ENDPOINT_USERS = 'users';

    private $drivers;
    private $loads;
    
    /**
     * Valid API keys (in production, store these in database or config)
     */
    private $valid_api_keys = array(
        'tms_api_key_2024_driver_access' => array(
            'name' => 'Main Driver API Key',
            'permissions' => array('read_driver_data'),
            'created' => '2024-01-01'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize API
     */
    public function init() {
        // Add any initialization code here
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Universal driver endpoint - accepts either email or id
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_DRIVER, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_driver'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'email' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return empty($param) || filter_var($param, FILTER_VALIDATE_EMAIL) !== false;
                    }
                ),
                'id' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return empty($param) || (is_numeric($param) && $param > 0);
                    }
                )
            )
        ));
        
        // Driver update endpoint
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_DRIVER_UPDATE, array(
            'methods' => 'POST',
            'callback' => array($this, 'update_driver'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'driver_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'user_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                )
            )
        ));
        
        // Driver location update endpoint
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_DRIVER_LOCATION_UPDATE, array(
            'methods' => 'POST',
            'callback' => array($this, 'update_driver_location'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'driver_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'user_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                )
            )
        ));
        
        // Drivers list endpoint with pagination
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_DRIVERS, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_drivers_list'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 20,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ),
                'status' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return empty($param) || is_string($param);
                    }
                ),
                'search' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return empty($param) || is_string($param);
                    }
                )
            )
        ));
        
        // Driver loads endpoint
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_DRIVER_LOADS, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_driver_loads'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'driver_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'project' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, array('odysseia', 'martlet', 'endurance'));
                    }
                ),
                'is_flt' => array(
                    'required' => false,
                    'default' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, array('true', 'false', '1', '0', true, false));
                    }
                ),
                'page' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 20,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                )
            )
        ));
        
        // Load detail endpoint
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_LOAD_DETAIL . '/(?P<load_id>[0-9]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_load_detail'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'load_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'project' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, array('odysseia', 'martlet', 'endurance'));
                    }
                ),
                'is_flt' => array(
                    'required' => false,
                    'default' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, array('true', 'false', '1', '0', true, false));
                    }
                )
            )
        ));
        
        // Users endpoint
        register_rest_route(self::API_NAMESPACE, '/' . self::ENDPOINT_USERS, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_users'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 20,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ),
                'search' => array(
                    'required' => false,
                    'default' => '',
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                )
            )
        ));
    }
    
    /**
     * Check API permission
     * 
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_api_permission($request) {
        $api_key = $request->get_header('X-API-Key');
        
        if (empty($api_key)) {
            return new WP_Error(
                'missing_api_key',
                'API key is required. Please provide X-API-Key header.',
                array('status' => 401)
            );
        }
        
        if (!$this->is_valid_api_key($api_key)) {
            return new WP_Error(
                'invalid_api_key',
                'Invalid API key provided.',
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Check if API key is valid
     * 
     * @param string $api_key
     * @return bool
     */
    private function is_valid_api_key($api_key) {
        return array_key_exists($api_key, $this->valid_api_keys);
    }
    
    /**
     * Universal driver endpoint - accepts either email or id
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_driver($request) {
        $email = $request->get_param('email');
        $driver_id = $request->get_param('id');
        
        // Check if both parameters are provided
        if (!empty($email) && !empty($driver_id)) {
            return new WP_Error(
                'too_many_parameters',
                'Please provide either email OR id parameter, not both.',
                array('status' => 400)
            );
        }
        
        // Check if no parameters are provided
        if (empty($email) && empty($driver_id)) {
            return new WP_Error(
                'missing_parameters',
                'Please provide either email or id parameter.',
                array('status' => 400)
            );
        }
        
        // Use email if provided
        if (!empty($email)) {
            return $this->fetch_driver_data($email, 'email');
        }
        
        // Use ID if provided
        if (!empty($driver_id)) {
            return $this->fetch_driver_data($driver_id, 'id');
        }
    }
    
    /**
     * Fetch driver data by email or ID
     * 
     * @param string|int $identifier Email or ID
     * @param string $type 'email' or 'id'
     * @return WP_REST_Response|WP_Error
     */
    private function fetch_driver_data($identifier, $type = 'email') {
        global $wpdb;
        
        try {
            // Initialize drivers class if not already done
            $drivers = $this->get_drivers();
            
            if ($type === 'email') {
                // Decode URL-encoded email
                $email = urldecode($identifier);
                
                // Additional email validation
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return new WP_Error(
                        'invalid_email',
                        'Invalid email format provided: ' . $email,
                        array('status' => 400)
                    );
                }
                
                // Find driver by email
                $driver_query = "
                    SELECT main.id 
                    FROM {$wpdb->prefix}drivers AS main
                    LEFT JOIN {$wpdb->prefix}drivers_meta AS email_meta 
                        ON main.id = email_meta.post_id 
                        AND email_meta.meta_key = 'driver_email'
                    WHERE main.status_post = 'publish' 
                        AND email_meta.meta_value = %s
                    LIMIT 1
                ";
                
                $driver_id = $wpdb->get_var($wpdb->prepare($driver_query, $email));
                
                if (!$driver_id) {
                    return new WP_Error(
                        'driver_not_found',
                        'Driver not found with email: ' . $email,
                        array('status' => 404)
                    );
                }
            } else {
                // Use ID directly
                $driver_id = intval($identifier);
                
                // Verify driver exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}drivers WHERE id = %d AND status_post = 'publish'",
                    $driver_id
                ));
                
                if (!$exists) {
                    return new WP_Error(
                        'driver_not_found',
                        'Driver not found with ID: ' . $driver_id,
                        array('status' => 404)
                    );
                }
            }
            
            // Get driver data using existing method
            $driver_data = $drivers->get_driver_by_id($driver_id);
            
            if (!$driver_data) {
                return new WP_Error(
                    'driver_data_error',
                    'Failed to retrieve driver data for ID: ' . $driver_id,
                    array('status' => 500)
                );
            }
            
            // Extract main and meta data
            $main_result = $driver_data['main'];
            $meta_data = $driver_data['meta'];
            
            // Get driver ratings and notices
            $ratings = $this->get_driver_ratings($driver_id);
            $notices = $this->get_driver_notices($driver_id);
            
            // Organize data by tabs
            $organized_data = $this->organize_driver_data_by_tabs($main_result, $meta_data);
            
            // Combine all data
            $final_data = array(
                'id' => $main_result['id'],
                'date_created' => $main_result['date_created'],
                'date_updated' => $main_result['date_updated'],
                'user_id_added' => $main_result['user_id_added'],
                'updated_zipcode' => $main_result['updated_zipcode'],
                'status_post' => $main_result['status_post'],
                'organized_data' => $organized_data,
                'ratings' => $ratings,
                'notices' => $notices
            );
            
            return new WP_REST_Response($final_data, 200);
            
        } catch (Exception $e) {
            return new WP_Error(
                'api_error',
                'An error occurred while fetching driver data: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Update driver data
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_driver($request) {
        try {
            $driver_id = $request->get_param('driver_id');
            $user_id = $request->get_param('user_id');
            $json_params = $request->get_json_params();
            

            // TODO: Remove this after testing
            if ($driver_id !== '3343') {
                return array(
                    'success' => false,
                    'message' => 'Driver not updated use onlt 3343 driver for testing',
                    'changed_fields' => array(),
                    'log_created' => false
                );
            }

            if (empty($driver_id)) {
                return new WP_Error(
                    'missing_driver_id',
                    'Driver ID parameter is required.',
                    array('status' => 400)
                );
            }
            
            if (empty($user_id)) {
                return new WP_Error(
                    'missing_user_id',
                    'User ID parameter is required for logging.',
                    array('status' => 400)
                );
            }
            
            if (empty($json_params)) {
                return new WP_Error(
                    'missing_data',
                    'Request body is required.',
                    array('status' => 400)
                );
            }
            
            // Validate user exists
            $user_info = get_userdata($user_id);
            if (!$user_info) {
                return new WP_Error(
                    'user_not_found',
                    'User not found.',
                    array('status' => 404)
                );
            }
            
            // Validate driver exists
            $drivers = $this->get_drivers();
            $driver_data = $drivers->get_driver_by_id($driver_id);
            
            if (!$driver_data) {
                return new WP_Error(
                    'driver_not_found',
                    'Driver not found.',
                    array('status' => 404)
                );
            }
            
            // Update driver data
            $update_result = $this->process_driver_update($driver_id, $json_params, $user_id);
            
            if ($update_result['success']) {
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Driver updated successfully',
                    'changed_fields' => $update_result['changed_fields'],
                    'log_created' => $update_result['log_created'],
                    'timestamp' => current_time('mysql'),
                    'api_version' => '1.0'
                ), 200);
            } else {
                return new WP_Error(
                    'update_failed',
                    $update_result['message'],
                    array('status' => 500)
                );
            }
            
        } catch (Exception $e) {
            return new WP_Error(
                'api_error',
                'An error occurred while updating driver: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Update driver location
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_driver_location($request) {
        try {
            $driver_id = $request->get_param('driver_id');
            $user_id = $request->get_param('user_id');
            $json_params = $request->get_json_params();
            
            if (empty($driver_id)) {
                return new WP_Error(
                    'missing_driver_id',
                    'Driver ID parameter is required.',
                    array('status' => 400)
                );
            }
            
            if (empty($user_id)) {
                return new WP_Error(
                    'missing_user_id',
                    'User ID parameter is required for logging.',
                    array('status' => 400)
                );
            }
            
            if (empty($json_params)) {
                return new WP_Error(
                    'missing_data',
                    'Request body is required.',
                    array('status' => 400)
                );
            }
            
            // Validate user exists
            $user_info = get_userdata($user_id);
            if (!$user_info) {
                return new WP_Error(
                    'user_not_found',
                    'User not found.',
                    array('status' => 404)
                );
            }
            
            // Validate driver exists
            $drivers = $this->get_drivers();
            $driver_data = $drivers->get_driver_by_id($driver_id);
            
            if (!$driver_data) {
                return new WP_Error(
                    'driver_not_found',
                    'Driver not found.',
                    array('status' => 404)
                );
            }
            
            // Validate required location fields
            if (empty($json_params['latitude']) || empty($json_params['longitude'])) {
                return new WP_Error(
                    'missing_coordinates',
                    'Latitude and longitude are required.',
                    array('status' => 400)
                );
            }
            
            // Get current driver meta values for comparison
            $current_meta = $this->get_all_driver_meta_values($driver_id);
            
            // Prepare update data
            $update_data = array(
                'driver_id' => intval($driver_id),
                'driver_status' => isset($json_params['driver_status']) ? sanitize_text_field($json_params['driver_status']) : '',
                'status_date' => isset($json_params['status_date']) ? sanitize_text_field($json_params['status_date']) : '',
                'current_location' => isset($json_params['current_location']) ? sanitize_text_field($json_params['current_location']) : '',
                'current_city' => isset($json_params['current_city']) ? sanitize_text_field($json_params['current_city']) : '',
                'current_zipcode' => isset($json_params['current_zipcode']) ? sanitize_text_field($json_params['current_zipcode']) : '',
                'latitude' => sanitize_text_field($json_params['latitude']),
                'longitude' => sanitize_text_field($json_params['longitude']),
                'country' => isset($json_params['country']) ? sanitize_text_field($json_params['country']) : '',
                'current_country' => isset($json_params['current_country']) ? sanitize_text_field($json_params['current_country']) : '',
            );
            
            // Add notes if provided
            if (isset($json_params['notes'])) {
                $update_data['notes'] = sanitize_textarea_field($json_params['notes']);
            }
            
            // Get user full name for logging
            $name_user = $drivers->get_user_full_name_by_id($user_id);
            
            // Get current time in New York timezone and format it properly
            $ny_timezone = new DateTimeZone('America/New_York');
            $ny_time = new DateTime('now', $ny_timezone);
            $formatted_time = $ny_time->format('m/d/Y g:i a');
            
            $update_data['last_user_update'] = 'Last update: ' . $name_user['full_name'] . ' - ' . $formatted_time;
            
            // Compare old and new values to track changes
            $changed_fields = array();
            $location_fields = array(
                'driver_status' => 'driver_status',
                'status_date' => 'status_date',
                'current_location' => 'current_location',
                'current_city' => 'current_city',
                'current_zipcode' => 'current_zipcode',
                'latitude' => 'latitude',
                'longitude' => 'longitude',
                'country' => 'country',
                'current_country' => 'current_country',
            );
            
            foreach ($location_fields as $field_key => $meta_key) {
                if (isset($update_data[$field_key]) && $update_data[$field_key] !== '') {
                    $old_value = $current_meta[$meta_key] ?? '';
                    $new_value = $update_data[$field_key];
                    
                    if ($old_value != $new_value) {
                        $changed_fields[] = array(
                            'field' => $meta_key,
                            'old_value' => $old_value ?: '(empty)',
                            'new_value' => $new_value
                        );
                    }
                }
            }
            
            // Fields that should not be saved as meta (they are main table fields or special fields)
            // Mark them in update_data so update_driver_in_db can filter them
            $update_data['_exclude_from_meta'] = array(
                'driver_id',
                'recruiter_add',
                'status_date', // status_date is converted to date_available in main table
                'user_id_updated',
                'date_updated',
                'user_id_added',
                'date_created',
            );
            
            // Update driver location in database
            $result = $drivers->update_driver_in_db($update_data);
            
            if ($result) {
                // Create log entry if there were changes
                $log_created = false;
                if (!empty($changed_fields)) {
                    $log_result = $this->create_driver_location_update_log($driver_id, $user_id, $changed_fields);
                    $log_created = $log_result['success'];
                }
                
                // Get updated driver data using public method
                $driver_data = $drivers->get_driver_by_id($driver_id);
                $updated_driver_data = null;
                
                if ($driver_data) {
                    // Extract basic info for response
                    $main_result = $driver_data['main'];
                    $meta_data = $driver_data['meta'];
                    
                    $updated_driver_data = array(
                        'id' => $main_result['id'],
                        'driver_name' => $meta_data['driver_name'] ?? null,
                        'driver_status' => $meta_data['driver_status'] ?? null,
                        'current_location' => $meta_data['current_location'] ?? null,
                        'current_city' => $meta_data['current_city'] ?? null,
                        'current_zipcode' => $meta_data['current_zipcode'] ?? null,
                        'latitude' => $meta_data['latitude'] ?? null,
                        'longitude' => $meta_data['longitude'] ?? null,
                        'date_updated' => $main_result['date_updated'] ?? null
                    );
                }
                
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Driver location updated successfully',
                    'driver_id' => $driver_id,
                    'data' => $update_data,
                    'changed_fields' => $changed_fields,
                    'log_created' => $log_created,
                    'updated_driver' => $updated_driver_data,
                    'timestamp' => current_time('mysql'),
                    'api_version' => '1.0'
                ), 200);
            } else {
                return new WP_Error(
                    'update_failed',
                    'Failed to update driver location',
                    array('status' => 500)
                );
            }
            
        } catch (Exception $e) {
            return new WP_Error(
                'api_error',
                'An error occurred while updating driver location: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Get drivers list with pagination
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_drivers_list($request) {
        try {
            // Get parameters
            $page = intval($request->get_param('page')) ?: 1;
            $per_page = intval($request->get_param('per_page')) ?: 20;
            $status = $request->get_param('status');
            $search = $request->get_param('search');
            
            // Initialize drivers class
            $drivers = $this->get_drivers();
            
            // Calculate offset
            $offset = ($page - 1) * $per_page;
            
            // Get drivers data
            $drivers_data = $this->fetch_drivers_list($offset, $per_page, $status, $search);
            
            if ($drivers_data === false) {
                return new WP_Error(
                    'drivers_fetch_error',
                    'Failed to retrieve drivers list',
                    array('status' => 500)
                );
            }
            
            // Get total count for pagination
            $total_count = $this->get_drivers_total_count($status, $search);
            $total_pages = ceil($total_count / $per_page);
            
            // Prepare response data
            $response_data = array(
                'success' => true,
                'data' => $drivers_data,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_count' => $total_count,
                    'total_pages' => $total_pages,
                    'has_next_page' => $page < $total_pages,
                    'has_prev_page' => $page > 1
                ),
                'filters' => array(
                    'status' => $status,
                    'search' => $search
                ),
                'timestamp' => current_time('mysql'),
                'api_version' => '1.0'
            );
            
            return new WP_REST_Response($response_data, 200);
            
        } catch (Exception $e) {
            return new WP_Error(
                'api_error',
                'An error occurred while fetching drivers list: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Get driver loads
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_driver_loads($request) {
        $driver_id = $request->get_param('driver_id');
        $project = $request->get_param('project');
        $is_flt = $request->get_param('is_flt');
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        
        if (empty($driver_id)) {
            return new WP_Error(
                'missing_driver_id',
                'Driver ID parameter is required.',
                array('status' => 400)
            );
        }
        
        if (empty($project)) {
            return new WP_Error(
                'missing_project',
                'Project parameter is required. Valid values: odiseia, martlet, endurance',
                array('status' => 400)
            );
        }
        
        // Convert is_flt to boolean
        $is_flt = filter_var($is_flt, FILTER_VALIDATE_BOOLEAN);
        
        // Validate driver ID
        if (!is_numeric($driver_id) || $driver_id <= 0) {
            return new WP_Error(
                'invalid_driver_id',
                'Invalid driver ID provided.',
                array('status' => 400)
            );
        }
        
        // Get loads for this driver
        $loads_data = $this->fetch_driver_loads($driver_id, $project, $is_flt, $page, $per_page);
        
        // Format response
        $response_data = array(
            'success' => true,
            'data' => $loads_data,
            'pagination' => array(
                'current_page' => (int)$page,
                'per_page' => (int)$per_page,
                'total_pages' => $loads_data['total_pages'],
                'total_items' => $loads_data['total_items']
            ),
            'timestamp' => current_time('mysql'),
            'api_version' => '1.0'
        );
        
        return new WP_REST_Response($response_data, 200);
    }
    
    /**
     * Get load detail
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_load_detail($request) {
        $load_id = $request->get_param('load_id');
        $project = $request->get_param('project');
        $is_flt = $request->get_param('is_flt');
        
        if (empty($load_id)) {
            return new WP_Error(
                'missing_load_id',
                'Load ID parameter is required.',
                array('status' => 400)
            );
        }
        
        if (empty($project)) {
            return new WP_Error(
                'missing_project',
                'Project parameter is required. Valid values: odiseia, martlet, endurance',
                array('status' => 400)
            );
        }
        
        // Convert is_flt to boolean
        $is_flt = filter_var($is_flt, FILTER_VALIDATE_BOOLEAN);
        
        // Get load data
        $load_data = $this->fetch_load_detail($load_id, $project, $is_flt);
        
        if (empty($load_data)) {
            return new WP_Error(
                'load_not_found',
                'Load not found with the provided ID.',
                array('status' => 404)
            );
        }
        
        // Format response
        $response_data = array(
            'success' => true,
            'data' => $load_data,
            'timestamp' => current_time('mysql'),
            'api_version' => '1.0'
        );
        
        return new WP_REST_Response($response_data, 200);
    }
    
    /**
     * Fetch driver data by email
     * 
     * @param string $email
     * @return array|null
     */
    private function fetch_driver_by_email($email) {
        global $wpdb;
        
        $table_main = $wpdb->prefix . 'drivers';
        $table_meta = $wpdb->prefix . 'drivers_meta';
        
        // Get main driver record
        $main_query = "
            SELECT main.*
            FROM $table_main main
            LEFT JOIN $table_meta email_meta 
                ON main.id = email_meta.post_id 
                AND email_meta.meta_key = 'driver_email'
            WHERE email_meta.meta_value = %s
            AND main.status_post = 'publish'
            LIMIT 1
        ";
        
        $main_result = $wpdb->get_row($wpdb->prepare($main_query, $email), ARRAY_A);
        
        if (empty($main_result)) {
            return null;
        }
        
        $driver_id = $main_result['id'];
        
        // Get all meta data for this driver
        $meta_query = "
            SELECT meta_key, meta_value
            FROM $table_meta
            WHERE post_id = %d
        ";
        
        $meta_results = $wpdb->get_results($wpdb->prepare($meta_query, $driver_id), ARRAY_A);
        
        // Organize meta data
        $meta_data = array();
        foreach ($meta_results as $meta_row) {
            $meta_data[$meta_row['meta_key']] = $meta_row['meta_value'];
        }
        
        // Get driver ratings if any
        $ratings = $this->get_driver_ratings($driver_id);
        
        // Get driver notices if any
        $notices = $this->get_driver_notices($driver_id);
        
        // Organize data by tabs
        $organized_data = $this->organize_driver_data_by_tabs($main_result, $meta_data);
        
        // Combine all data
        $driver_data = array(
            'id' => $main_result['id'],
            'role' => 'driver',
            'date_created' => $main_result['date_created'],
            'date_updated' => $main_result['date_updated'],
            'user_id_added' => $main_result['user_id_added'],
            'updated_zipcode' => $main_result['updated_zipcode'],
            'status_post' => $main_result['status_post'],
            'organized_data' => $organized_data,
            'ratings' => $ratings,
            'notices' => $notices,

        );
        
        return $driver_data;
    }
    
    /**
     * Get drivers instance (lazy initialization)
     * 
     * @return TMSDrivers
     */
    private function get_drivers() {
        if (!$this->drivers) {
            $this->drivers = new TMSDrivers();
        }
        return $this->drivers;
    }
    
    /**
     * Get loads instance (lazy initialization)
     * 
     * @return TMSReports
     */
    private function get_loads() {
        if (!$this->loads) {
            $this->loads = new TMSReports();
        }
        return $this->loads;
    }
    
    /**
     * Get human-readable labels for multiple values
     * 
     * @param string $values Comma-separated values
     * @param array $labels_array Array of labels
     * @return array
     */
    private function get_multiple_labels($values, $labels_array) {
        if (empty($values)) {
            return array('value' => null, 'label' => null);
        }
        
        $value_array = array_map('trim', explode(',', $values));
        $label_array = array();
        
        foreach ($value_array as $value) {
            $label_array[] = $labels_array[$value] ?? $value;
        }
        
        return array(
            'value' => $values,
            'label' => implode(', ', $label_array)
        );
    }
    
    /**
     * Get human-readable label for single value
     * 
     * @param string $value
     * @param array $labels_array Array of labels
     * @return array
     */
    private function get_single_label($value, $labels_array) {
        if (empty($value)) {
            return array('value' => null, 'label' => null);
        }
        
        return array(
            'value' => $value,
            'label' => $labels_array[$value] ?? $value
        );
    }
    
    /**
     * Clean empty values (like "/ /", "//", etc.)
     * 
     * @param string $value
     * @return string|null
     */
    private function clean_empty_value($value) {
        if (empty($value)) {
            return null;
        }
        
        // Remove common empty patterns
        $cleaned = trim($value);
        if (empty($cleaned) || 
            $cleaned === '/' || 
            $cleaned === '//' || 
            $cleaned === '/ /' || 
            $cleaned === '//' || 
            $cleaned === ' / ' || 
            $cleaned === '/ / ' ||
            $cleaned === ' / /' ||
            preg_match('/^[\s\/\-]+$/', $cleaned)) {
            return null;
        }
        
        return $cleaned;
    }
    
    /**
     * Get file URL by attachment ID
     * 
     * @param string|int $attachment_id
     * @return string|null
     */
    private function get_file_url($attachment_id) {
        if (empty($attachment_id)) {
            return null;
        }
        
        $url = wp_get_attachment_url($attachment_id);
        return $url ? $url : null;
    }
    
    /**
     * Organize driver data by tabs
     * 
     * @param array $main_result
     * @param array $meta_data
     * @return array
     */
    private function organize_driver_data_by_tabs($main_result, $meta_data) {
        return array(
            'current_location' => $this->get_current_location_data($main_result, $meta_data),
            'contact' => $this->get_contact_data($meta_data),
            'vehicle' => $this->get_vehicle_data($meta_data),
            'documents' => $this->get_documents_data($meta_data),
            'statistics' => $this->get_statistics_data($main_result, $meta_data)
        );
    }
    
    /**
     * Get current location data
     * 
     * @param array $main_result
     * @param array $meta_data
     * @return array
     */
    private function get_current_location_data($main_result, $meta_data) {
        return array(
            'status' => $meta_data['driver_status'] ?? null,
            'available_date' => $main_result['date_available'] ?? null,
            'zipcode' => $meta_data['current_zipcode'] ?? $meta_data['updated_zipcode'] ?? null,
            'city' => $meta_data['current_city'] ?? null,
            'state' => $meta_data['current_location'] ?? null,
            'coordinates' => array(
                'lat' => $meta_data['latitude'] ?? null,
                'lng' => $meta_data['longitude'] ?? null
            )
        );
    }
    
    /**
     * Get contact data
     * 
     * @param array $meta_data
     * @return array
     */
    private function get_contact_data($meta_data) {


        // Driver Phone
        // Driver email
        // Home state, city
        // Date of Birth
        // Language (набор множества языков если водитель говорит на нескольких)
        // Team Driver (Optional)
        // Preferred distance
        // Emergency Contact
        // Emergency Contact Name, Emergency Phone, Relation


        // Get human-readable labels
        // $preferred_distance = $this->get_multiple_labels(
        //     $meta_data['preferred_distance'] ?? null, 
        //     $this->get_drivers()->labels_distance
        // );
        
        // $languages = $this->get_multiple_labels(
        //     $meta_data['languages'] ?? null, 
        //     $this->get_drivers()->languages
        // );
        
        // $emergency_relation = $this->get_single_label(
        //     $meta_data['emergency_contact_relation'] ?? null, 
        //     $this->get_drivers()->relation_options
        // );

        return array(
            'driver_name' => $this->clean_empty_value($meta_data['driver_name'] ?? null),
            'driver_phone' => $this->clean_empty_value($meta_data['driver_phone'] ?? null),
            'driver_email' => $this->clean_empty_value($meta_data['driver_email'] ?? null),
            'home_location' => $this->clean_empty_value($meta_data['home_location'] ?? null),
            'city' => $this->clean_empty_value($meta_data['city'] ?? null),
            'city_state_zip' => $this->clean_empty_value($meta_data['city_state_zip'] ?? null),
            'date_of_birth' => $this->clean_empty_value($meta_data['dob'] ?? null),
            'languages' => $this->clean_empty_value($meta_data['languages'] ?? null),
            'team_driver' => array(
                'enabled' => !empty($meta_data['team_driver_enabled']),
                'name' => $this->clean_empty_value($meta_data['team_driver_name'] ?? null),
                'phone' => $this->clean_empty_value($meta_data['team_driver_phone'] ?? null),
                'email' => $this->clean_empty_value($meta_data['team_driver_email'] ?? null),
                'date_of_birth' => $this->clean_empty_value($meta_data['team_driver_dob'] ?? null)
            ),
            'preferred_distance' => $this->clean_empty_value($meta_data['preferred_distance'] ?? null),
            'emergency_contact' => array(
                'name' => $this->clean_empty_value($meta_data['emergency_contact_name'] ?? null),
                'phone' => $this->clean_empty_value($meta_data['emergency_contact_phone'] ?? null),
                'relation' => $this->clean_empty_value($meta_data['emergency_contact_relation'] ?? null)
            ),
            'recruiter_add' => $this->clean_empty_value($meta_data['recruiter_add'] ?? null)
        );
    }
    
    /**
     * Get vehicle data
     * 
     * @param array $meta_data
     * @return array
     */
    private function get_vehicle_data($meta_data) {
        // Get human-readable vehicle type label
        $vehicle_type = $this->get_single_label(
            $meta_data['vehicle_type'] ?? null, 
            $this->get_drivers()->vehicle
        );

        return array(
            'type' => $vehicle_type,
            'make' => $this->clean_empty_value($meta_data['vehicle_make'] ?? null),
            'model' => $this->clean_empty_value($meta_data['vehicle_model'] ?? null),
            'year' => $this->clean_empty_value($meta_data['vehicle_year'] ?? null),
            'payload' => $this->clean_empty_value($meta_data['payload'] ?? null),
            'cargo_space_dimensions' => $this->clean_empty_value($meta_data['dimensions'] ?? null),
            'overall_dimensions' => $this->clean_empty_value($meta_data['overall_dimensions'] ?? null),
            'vin' => $meta_data['vin'] ?? null,
            'equipment' => array(
                'side_door' => !empty($meta_data['side_door']),
                'load_bars' => !empty($meta_data['load_bars']),
                'printer' => !empty($meta_data['printer']),
                'sleeper' => !empty($meta_data['sleeper']),
                'ppe' => !empty($meta_data['ppe']),
                'e_tracks' => !empty($meta_data['e_tracks']),
                'pallet_jack' => !empty($meta_data['pallet_jack']),
                'lift_gate' => !empty($meta_data['lift_gate']),
                'dolly' => !empty($meta_data['dolly']),
                'ramp' => !empty($meta_data['ramp'])
            )
        );
    }
    
    /**
     * Get documents data
     * 
     * @param array $meta_data
     * @return array
     */
    private function get_documents_data($meta_data) {
        // Get file URLs for certificates
        $hazmat_certificate_file_id = $this->clean_empty_value($meta_data['hazmat_certificate_file'] ?? null);
        $twic_file_id = $this->clean_empty_value($meta_data['twic_file'] ?? null);
        $tsa_file_id = $this->clean_empty_value($meta_data['tsa_file'] ?? null);
        $background_file_id = $this->clean_empty_value($meta_data['background_file'] ?? null);
        $change_9_file_id = $this->clean_empty_value($meta_data['change_9_file'] ?? null);
        
        return array(
            'driver_licence_type' => $this->clean_empty_value($meta_data['driver_licence_type'] ?? null),
            'real_id' => !empty($meta_data['real_id']),
            'hazmat_certificate' => array(
                'has_certificate' => !empty($meta_data['hazmat_certificate']),
                'file_url' => $this->get_file_url($hazmat_certificate_file_id)
            ),
            'twic' => array(
                'has_certificate' => !empty($meta_data['twic']),
                'file_url' => $this->get_file_url($twic_file_id)
            ),
            'tsa_approved' => array(
                'has_certificate' => !empty($meta_data['tsa_approved']),
                'file_url' => $this->get_file_url($tsa_file_id)
            ),
            'background_check' => array(
                'has_certificate' => !empty($meta_data['background_check']),
                'file_url' => $this->get_file_url($background_file_id)
            ),
            'change_9_training' => array(
                'has_certificate' => !empty($meta_data['change_9_training']),
                'file_url' => $this->get_file_url($change_9_file_id)
            )
        );
    }
    
    /**
     * Get statistics data
     * 
     * @param array $main_result
     * @param array $meta_data
     * @return array
     */
    private function get_statistics_data($main_result, $meta_data) {
        // Get driver ID for statistics
        $driver_id = $main_result['id'] ?? null;
        
        // Get driver statistics (rating and notices)
        $driver_statistics = array();
        if ($driver_id) {
            $driver_statistics = $this->get_drivers()->get_driver_statistics($driver_id, true);
        }
    

        return array(
            'rating' => array(
                'average_rating' => $driver_statistics['rating']['avg_rating'] ?? 0,
                'total_ratings' => $driver_statistics['rating']['count'] ?? 0,
                'all_ratings' => $driver_statistics['rating']['data'] ?? array()
            ),
            'notifications' => array(
                'total_count' => $driver_statistics['notice']['count'] ?? 0,
                'all_notifications' => $driver_statistics['notice']['data'] ?? array()
            )
        );
    }
    
    /**
     * Get driver ID by email
     * 
     * @param string $email
     * @return int|null
     */
    private function get_driver_id_by_email($email) {
        global $wpdb;
        
        $table_main = $wpdb->prefix . 'drivers';
        $table_meta = $wpdb->prefix . 'drivers_meta';
        
        $query = "
            SELECT main.id
            FROM $table_main main
            LEFT JOIN $table_meta email_meta 
                ON main.id = email_meta.post_id 
                AND email_meta.meta_key = 'driver_email'
            WHERE email_meta.meta_value = %s
            AND main.status_post = 'publish'
            LIMIT 1
        ";
        
        return $wpdb->get_var($wpdb->prepare($query, $email));
    }
    
    /**
     * Fetch driver loads
     * 
     * @param int $driver_id
     * @param string $project
     * @param bool $is_flt
     * @param int $page
     * @param int $per_page
     * @return array
     */
    private function fetch_driver_loads($driver_id, $project, $is_flt = false, $page = 1, $per_page = 20) {
        global $wpdb;
        
        // Build table names based on project and FLT flag
        $table_prefix = $is_flt ? 'reports_flt_' : 'reports_';
        $meta_prefix = $is_flt ? 'reportsmeta_flt_' : 'reportsmeta_';
        
        $table_main = $wpdb->prefix . $table_prefix . strtolower($project);
        $table_meta = $wpdb->prefix . $meta_prefix . strtolower($project);
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Get total count first
        $count_query = "
            SELECT COUNT(DISTINCT main.id)
            FROM $table_main main
            LEFT JOIN $table_meta driver_meta 
                ON main.id = driver_meta.post_id 
                AND driver_meta.meta_key = 'attached_driver'
            LEFT JOIN $table_meta second_driver_meta 
                ON main.id = second_driver_meta.post_id 
                AND second_driver_meta.meta_key = 'attached_second_driver'
            WHERE main.status_post = 'publish'
            AND (driver_meta.meta_value = %s OR second_driver_meta.meta_value = %s)
        ";
        
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $driver_id, $driver_id));
        $total_pages = ceil($total_items / $per_page);
        
        // Get loads with pagination
        $loads_query = "
            SELECT DISTINCT main.id, main.date_created, main.date_updated
            FROM $table_main main
            LEFT JOIN $table_meta driver_meta 
                ON main.id = driver_meta.post_id 
                AND driver_meta.meta_key = 'attached_driver'
            LEFT JOIN $table_meta second_driver_meta 
                ON main.id = second_driver_meta.post_id 
                AND second_driver_meta.meta_key = 'attached_second_driver'
            WHERE main.status_post = 'publish'
            AND (driver_meta.meta_value = %s OR second_driver_meta.meta_value = %s)
            ORDER BY main.date_created DESC
            LIMIT %d, %d
        ";
        
        $loads = $wpdb->get_results($wpdb->prepare($loads_query, $driver_id, $driver_id, $offset, $per_page), ARRAY_A);
        
        // Get meta data for each load
        $loads_data = array();
        foreach ($loads as $load) {
            $load_id = $load['id'];
            
            // Get all meta data for this load
            $meta_query = "
                SELECT meta_key, meta_value
                FROM $table_meta
                WHERE post_id = %d
            ";
            
            $meta_results = $wpdb->get_results($wpdb->prepare($meta_query, $load_id), ARRAY_A);
            
            // Organize meta data
            $meta_data = array();
            foreach ($meta_results as $meta_row) {
                $meta_data[$meta_row['meta_key']] = $meta_row['meta_value'];
            }
            
            $loads_data[] = array(
                'id' => $load_id,
                'date_created' => $load['date_created'],
                'date_updated' => $load['date_updated'],
                'meta_data' => $meta_data
            );
        }
        
        return array(
            'loads' => $loads_data,
            'total_items' => (int)$total_items,
            'total_pages' => (int)$total_pages
        );
    }
    
    /**
     * Fetch load detail
     * 
     * @param int $load_id
     * @param string $project
     * @param bool $is_flt
     * @return array|null
     */
    private function fetch_load_detail($load_id, $project, $is_flt = false) {
        global $wpdb;
        
        // Build table names based on project and FLT flag
        $table_prefix = $is_flt ? 'reports_flt_' : 'reports_';
        $meta_prefix = $is_flt ? 'reportsmeta_flt_' : 'reportsmeta_';
        
        $table_main = $wpdb->prefix . $table_prefix . strtolower($project);
        $table_meta = $wpdb->prefix . $meta_prefix . strtolower($project);
        
        // Get main load record
        $main_query = "
            SELECT *
            FROM $table_main
            WHERE id = %d
            AND status_post = 'publish'
            LIMIT 1
        ";
        
        $main_result = $wpdb->get_row($wpdb->prepare($main_query, $load_id), ARRAY_A);
        
        if (empty($main_result)) {
            return null;
        }
        
        // Get all meta data for this load
        $meta_query = "
            SELECT meta_key, meta_value
            FROM $table_meta
            WHERE post_id = %d
        ";
        
        $meta_results = $wpdb->get_results($wpdb->prepare($meta_query, $load_id), ARRAY_A);
        
        // Organize meta data
        $meta_data = array();
        foreach ($meta_results as $meta_row) {
            $meta_data[$meta_row['meta_key']] = $meta_row['meta_value'];
        }
        
        return array(
            'id' => $main_result['id'],
            'date_created' => $main_result['date_created'],
            'date_updated' => $main_result['date_updated'],
            'user_id_added' => $main_result['user_id_added'],
            'status_post' => $main_result['status_post'],
            'meta_data' => $meta_data
        );
    }
    
    /**
     * Get driver ratings
     * 
     * @param int $driver_id
     * @return array
     */
    private function get_driver_ratings($driver_id) {
        global $wpdb;
        
        $table_ratings = $wpdb->prefix . 'drivers_raiting';
        
        $query = "
            SELECT *
            FROM $table_ratings
            WHERE post_id = %d
            ORDER BY date_created DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $driver_id), ARRAY_A);
    }
    
    /**
     * Get driver notices
     * 
     * @param int $driver_id
     * @return array
     */
    private function get_driver_notices($driver_id) {
        global $wpdb;
        
        $table_notices = $wpdb->prefix . 'drivers_notice';
        
        $query = "
            SELECT *
            FROM $table_notices
            WHERE post_id = %d
            ORDER BY date_created DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $driver_id), ARRAY_A);
    }
    
    /**
     * Generate new API key (for admin use)
     * 
     * @param string $name
     * @param array $permissions
     * @return string
     */
    public function generate_api_key($name = 'Generated Key', $permissions = array('read_driver_data')) {
        $api_key = 'tms_api_' . wp_generate_password(32, false);
        
        // In production, you would save this to database
        // For now, we'll just return the key
        return $api_key;
    }
    
    /**
     * Get API usage statistics (for monitoring)
     * 
     * @return array
     */
    public function get_api_stats() {
        // This would typically query a logs table
        // For now, return basic info
        return array(
            'total_keys' => count($this->valid_api_keys),
            'endpoints' => array(
                'driver_by_email' => self::API_NAMESPACE . '/' . self::ENDPOINT_DRIVER
            ),
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Fetch drivers list with pagination and filters
     * 
     * @param int $offset
     * @param int $per_page
     * @param string $status
     * @param string $search
     * @return array|false
     */
    private function fetch_drivers_list($offset, $per_page, $status = null, $search = null) {
        global $wpdb;
        
        try {
            $drivers = $this->get_drivers();
            
            // Build WHERE conditions
            $where_conditions = array("main.status_post = 'publish'");
            $where_values = array();
            
            // Add status filter if provided
            if (!empty($status)) {
                $where_conditions[] = "driver_status.meta_value = %s";
                $where_values[] = $status;
            }
            
            // Add search filter if provided
            if (!empty($search)) {
                $where_conditions[] = "(driver_name.meta_value LIKE %s OR driver_phone.meta_value LIKE %s OR driver_email.meta_value LIKE %s)";
                $search_term = '%' . $wpdb->esc_like($search) . '%';
                $where_values[] = $search_term;
                $where_values[] = $search_term;
                $where_values[] = $search_term;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Build the main query with only required fields
            $query = "
                SELECT DISTINCT main.id,
                       driver_name.meta_value as driver_name,
                       driver_phone.meta_value as driver_phone,
                       driver_email.meta_value as driver_email,
                       home_location.meta_value as home_location,
                       vehicle_type.meta_value as vehicle_type,
                       vin.meta_value as vin
                FROM {$wpdb->prefix}drivers AS main
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_name 
                    ON main.id = driver_name.post_id AND driver_name.meta_key = 'driver_name'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_phone 
                    ON main.id = driver_phone.post_id AND driver_phone.meta_key = 'driver_phone'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_email 
                    ON main.id = driver_email.post_id AND driver_email.meta_key = 'driver_email'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_status 
                    ON main.id = driver_status.post_id AND driver_status.meta_key = 'driver_status'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS home_location 
                    ON main.id = home_location.post_id AND home_location.meta_key = 'home_location'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS vehicle_type 
                    ON main.id = vehicle_type.post_id AND vehicle_type.meta_key = 'vehicle_type'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS vin 
                    ON main.id = vin.post_id AND vin.meta_key = 'vin'
                WHERE $where_clause
                ORDER BY main.id DESC
                LIMIT %d OFFSET %d
            ";
            
            // Add limit and offset to values
            $where_values[] = $per_page;
            $where_values[] = $offset;
            
            // Prepare and execute query
            $results = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
            
            if ($results === false) {
                return false;
            }
            
            // Format results with simple fields only
            $formatted_results = array();
            foreach ($results as $driver) {
                $formatted_results[] = array(
                    'id' => intval($driver['id']),
                    'role' => 'driver', // Fixed role as requested
                    'driver_name' => $this->clean_empty_value($driver['driver_name']),
                    'driver_email' => $this->clean_empty_value($driver['driver_email']),
                    'driver_phone' => $this->clean_empty_value($driver['driver_phone']),
                    'home_location' => $this->clean_empty_value($driver['home_location']),
                    'type' => $this->clean_empty_value($driver['vehicle_type']),
                    'vin' => $this->clean_empty_value($driver['vin'])
                );
            }
            
            return $formatted_results;
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI fetch_drivers_list error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get full driver data for a single driver (same structure as single driver endpoint)
     * 
     * @param int $driver_id
     * @return array|null
     */
    private function get_full_driver_data($driver_id) {
        try {
            $drivers = $this->get_drivers();
            
            // Get driver data using existing method
            $driver_data = $drivers->get_driver_by_id($driver_id);
            
            if (!$driver_data) {
                return null;
            }
            
            // Extract main and meta data
            $main_result = $driver_data['main'];
            $meta_data = $driver_data['meta'];
            
            // Get driver ratings and notices
            $ratings = $this->get_driver_ratings($driver_id);
            $notices = $this->get_driver_notices($driver_id);
            
            // Organize data by tabs
            $organized_data = $this->organize_driver_data_by_tabs($main_result, $meta_data);
            
            // Combine all data (same structure as single driver endpoint)
            $final_data = array(
                'id' => $main_result['id'],
                'date_created' => $main_result['date_created'],
                'date_updated' => $main_result['date_updated'],
                'user_id_added' => $main_result['user_id_added'],
                'updated_zipcode' => $main_result['updated_zipcode'],
                'status_post' => $main_result['status_post'],
                'organized_data' => $organized_data,
                'ratings' => $ratings,
                'notices' => $notices
            );
            
            return $final_data;
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI get_full_driver_data error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get total count of drivers for pagination
     * 
     * @param string $status
     * @param string $search
     * @return int
     */
    private function get_drivers_total_count($status = null, $search = null) {
        global $wpdb;
        
        try {
            // Build WHERE conditions
            $where_conditions = array("main.status_post = 'publish'");
            $where_values = array();
            
            // Add status filter if provided
            if (!empty($status)) {
                $where_conditions[] = "driver_status.meta_value = %s";
                $where_values[] = $status;
            }
            
            // Add search filter if provided
            if (!empty($search)) {
                $where_conditions[] = "(driver_name.meta_value LIKE %s OR driver_phone.meta_value LIKE %s OR driver_email.meta_value LIKE %s)";
                $search_term = '%' . $wpdb->esc_like($search) . '%';
                $where_values[] = $search_term;
                $where_values[] = $search_term;
                $where_values[] = $search_term;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Build count query
            $count_query = "
                SELECT COUNT(DISTINCT main.id)
                FROM {$wpdb->prefix}drivers AS main
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_name 
                    ON main.id = driver_name.post_id AND driver_name.meta_key = 'driver_name'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_phone 
                    ON main.id = driver_phone.post_id AND driver_phone.meta_key = 'driver_phone'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_email 
                    ON main.id = driver_email.post_id AND driver_email.meta_key = 'driver_email'
                LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_status 
                    ON main.id = driver_status.post_id AND driver_status.meta_key = 'driver_status'
                WHERE $where_clause
            ";
            
            $count = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
            
            return intval($count);
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI get_drivers_total_count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Process driver update data
     * 
     * @param int $driver_id
     * @param array $data
     * @return array
     */
    private function process_driver_update($driver_id, $data, $user_id) {
        global $wpdb;
        
        try {
            // Get current driver data for comparison
            $drivers = $this->get_drivers();
            $current_driver = $drivers->get_driver_by_id($driver_id);
            
            if (!$current_driver) {
                return array(
                    'success' => false,
                    'message' => 'Driver not found',
                    'changed_fields' => array(),
                    'log_created' => false
                );
            }
            
            // Get all current meta values from database for accurate comparison
            $current_meta = $this->get_all_driver_meta_values($driver_id);
            
            $changed_fields = array();
            $errors = array();
            $log_created = false;
            
            // Process current_location data
            if (isset($data['current_location'])) {
                $location_result = $this->update_current_location_with_comparison($driver_id, $data['current_location'], $current_meta);
                if ($location_result['success']) {
                    $changed_fields = array_merge($changed_fields, $location_result['changed_fields']);
                } else {
                    $errors = array_merge($errors, $location_result['errors']);
                }
            }
            
            // Process contact data
            if (isset($data['contact'])) {
                $contact_result = $this->update_contact_data_with_comparison($driver_id, $data['contact'], $current_meta);
                if ($contact_result['success']) {
                    $changed_fields = array_merge($changed_fields, $contact_result['changed_fields']);
                } else {
                    $errors = array_merge($errors, $contact_result['errors']);
                }
            }
            
            // Process vehicle data
            if (isset($data['vehicle'])) {
                $vehicle_result = $this->update_vehicle_data_with_comparison($driver_id, $data['vehicle'], $current_meta);
                if ($vehicle_result['success']) {
                    $changed_fields = array_merge($changed_fields, $vehicle_result['changed_fields']);
                } else {
                    $errors = array_merge($errors, $vehicle_result['errors']);
                }
            }
            
            // Create log entry if there were changes
            if (!empty($changed_fields)) {
                $log_result = $this->create_driver_update_log($driver_id, $user_id, $changed_fields);
                $log_created = $log_result['success'];
            }
            
            if (!empty($errors)) {
                return array(
                    'success' => false,
                    'message' => 'Some fields failed to update: ' . implode(', ', $errors),
                    'changed_fields' => $changed_fields,
                    'log_created' => $log_created
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Driver updated successfully',
                'changed_fields' => $changed_fields,
                'log_created' => $log_created
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI process_driver_update error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
                'changed_fields' => array(),
                'log_created' => false
            );
        }
    }
    
    /**
     * Get all driver meta values from database
     * 
     * @param int $driver_id
     * @return array
     */
    private function get_all_driver_meta_values($driver_id) {
        global $wpdb;
        
        $table_meta = $wpdb->prefix . 'drivers_meta';
        
        $query = $wpdb->prepare("
            SELECT meta_key, meta_value 
            FROM $table_meta 
            WHERE post_id = %d
        ", $driver_id);
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $meta_values = array();
        foreach ($results as $row) {
            $meta_values[$row['meta_key']] = $row['meta_value'];
        }
        
        // Debug: Log all available fields for this driver
        error_log('TMSDriversAPI get_all_driver_meta_values - Available fields: ' . implode(', ', array_keys($meta_values)));
        
        return $meta_values;
    }
    
    /**
     * Update current location data
     * 
     * @param int $driver_id
     * @param array $location_data
     * @return array
     */
    private function update_current_location($driver_id, $location_data) {
        global $wpdb;
        
        $updated_fields = array();
        $errors = array();
        
        try {
            // Update zipcode
            if (isset($location_data['zipcode'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $location_data['zipcode']),
                    array('post_id' => $driver_id, 'meta_key' => 'current_zipcode'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'current_zipcode';
                } else {
                    $errors[] = 'current_zipcode';
                }
            }
            
            // Update city
            if (isset($location_data['city'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $location_data['city']),
                    array('post_id' => $driver_id, 'meta_key' => 'current_city'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'current_city';
                } else {
                    $errors[] = 'current_city';
                }
            }
            
            // Update state
            if (isset($location_data['state'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $location_data['state']),
                    array('post_id' => $driver_id, 'meta_key' => 'current_location'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'current_location';
                } else {
                    $errors[] = 'current_location';
                }
            }
            
            // Update coordinates
            if (isset($location_data['coordinates'])) {
                if (isset($location_data['coordinates']['lat'])) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'drivers_meta',
                        array('meta_value' => $location_data['coordinates']['lat']),
                        array('post_id' => $driver_id, 'meta_key' => 'latitude'),
                        array('%s'),
                        array('%d', '%s')
                    );
                    if ($result !== false) {
                        $updated_fields[] = 'latitude';
                    } else {
                        $errors[] = 'latitude';
                    }
                }
                
                if (isset($location_data['coordinates']['lng'])) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'drivers_meta',
                        array('meta_value' => $location_data['coordinates']['lng']),
                        array('post_id' => $driver_id, 'meta_key' => 'longitude'),
                        array('%s'),
                        array('%d', '%s')
                    );
                    if ($result !== false) {
                        $updated_fields[] = 'longitude';
                    } else {
                        $errors[] = 'longitude';
                    }
                }
            }
            
            // Update last_updated
            if (isset($location_data['last_updated'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $location_data['last_updated']),
                    array('post_id' => $driver_id, 'meta_key' => 'status_date'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'status_date';
                } else {
                    $errors[] = 'status_date';
                }
            }
            
            return array(
                'success' => empty($errors),
                'updated_fields' => $updated_fields,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI update_current_location error: ' . $e->getMessage());
            return array(
                'success' => false,
                'updated_fields' => $updated_fields,
                'errors' => array('location_update_failed')
            );
        }
    }
    
    /**
     * Update contact data
     * 
     * @param int $driver_id
     * @param array $contact_data
     * @return array
     */
    private function update_contact_data($driver_id, $contact_data) {
        global $wpdb;
        
        $updated_fields = array();
        $errors = array();
        
        try {
            // Update driver_name
            if (isset($contact_data['driver_name'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['driver_name']),
                    array('post_id' => $driver_id, 'meta_key' => 'driver_name'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'driver_name';
                } else {
                    $errors[] = 'driver_name';
                }
            }
            
            // Update driver_phone
            if (isset($contact_data['driver_phone'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['driver_phone']),
                    array('post_id' => $driver_id, 'meta_key' => 'driver_phone'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'driver_phone';
                } else {
                    $errors[] = 'driver_phone';
                }
            }
            
            // Update driver_email
            if (isset($contact_data['driver_email'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['driver_email']),
                    array('post_id' => $driver_id, 'meta_key' => 'driver_email'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'driver_email';
                } else {
                    $errors[] = 'driver_email';
                }
            }
            
            // Update home_location
            if (isset($contact_data['home_location'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['home_location']),
                    array('post_id' => $driver_id, 'meta_key' => 'home_location'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'home_location';
                } else {
                    $errors[] = 'home_location';
                }
            }
            
            // Update city
            if (isset($contact_data['city'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['city']),
                    array('post_id' => $driver_id, 'meta_key' => 'city'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'city';
                } else {
                    $errors[] = 'city';
                }
            }
            
            // Update city_state_zip
            if (isset($contact_data['city_state_zip'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['city_state_zip']),
                    array('post_id' => $driver_id, 'meta_key' => 'city_state_zip'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'city_state_zip';
                } else {
                    $errors[] = 'city_state_zip';
                }
            }
            
            // Update date_of_birth
            if (isset($contact_data['date_of_birth'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['date_of_birth']),
                    array('post_id' => $driver_id, 'meta_key' => 'date_of_birth'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'date_of_birth';
                } else {
                    $errors[] = 'date_of_birth';
                }
            }
            
            // Update languages
            if (isset($contact_data['languages'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['languages']),
                    array('post_id' => $driver_id, 'meta_key' => 'languages'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'languages';
                } else {
                    $errors[] = 'languages';
                }
            }
            
            // Update team_driver data
            if (isset($contact_data['team_driver'])) {
                $team_result = $this->update_team_driver_data($driver_id, $contact_data['team_driver']);
                if ($team_result['success']) {
                    $updated_fields = array_merge($updated_fields, $team_result['updated_fields']);
                } else {
                    $errors = array_merge($errors, $team_result['errors']);
                }
            }
            
            // Update preferred_distance
            if (isset($contact_data['preferred_distance'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $contact_data['preferred_distance']),
                    array('post_id' => $driver_id, 'meta_key' => 'preferred_distance'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'preferred_distance';
                } else {
                    $errors[] = 'preferred_distance';
                }
            }
            
            // Update emergency_contact data
            if (isset($contact_data['emergency_contact'])) {
                $emergency_result = $this->update_emergency_contact_data($driver_id, $contact_data['emergency_contact']);
                if ($emergency_result['success']) {
                    $updated_fields = array_merge($updated_fields, $emergency_result['updated_fields']);
                } else {
                    $errors = array_merge($errors, $emergency_result['errors']);
                }
            }
            
            return array(
                'success' => empty($errors),
                'updated_fields' => $updated_fields,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI update_contact_data error: ' . $e->getMessage());
            return array(
                'success' => false,
                'updated_fields' => $updated_fields,
                'errors' => array('contact_update_failed')
            );
        }
    }
    
    /**
     * Update vehicle data
     * 
     * @param int $driver_id
     * @param array $vehicle_data
     * @return array
     */
    private function update_vehicle_data($driver_id, $vehicle_data) {
        global $wpdb;
        
        $updated_fields = array();
        $errors = array();
        
        try {
            // Update vehicle type
            if (isset($vehicle_data['type']['value'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['type']['value']),
                    array('post_id' => $driver_id, 'meta_key' => 'vehicle_type'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'vehicle_type';
                } else {
                    $errors[] = 'vehicle_type';
                }
            }
            
            // Update make
            if (isset($vehicle_data['make'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['make']),
                    array('post_id' => $driver_id, 'meta_key' => 'make'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'make';
                } else {
                    $errors[] = 'make';
                }
            }
            
            // Update model
            if (isset($vehicle_data['model'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['model']),
                    array('post_id' => $driver_id, 'meta_key' => 'model'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'model';
                } else {
                    $errors[] = 'model';
                }
            }
            
            // Update year
            if (isset($vehicle_data['year'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['year']),
                    array('post_id' => $driver_id, 'meta_key' => 'year'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'year';
                } else {
                    $errors[] = 'year';
                }
            }
            
            // Update payload
            if (isset($vehicle_data['payload'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['payload']),
                    array('post_id' => $driver_id, 'meta_key' => 'payload'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'payload';
                } else {
                    $errors[] = 'payload';
                }
            }
            
            // Update cargo_space_dimensions
            if (isset($vehicle_data['cargo_space_dimensions'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['cargo_space_dimensions']),
                    array('post_id' => $driver_id, 'meta_key' => 'dimensions'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'cargo_space_dimensions';
                } else {
                    $errors[] = 'cargo_space_dimensions';
                }
            }
            
            // Update overall_dimensions
            if (isset($vehicle_data['overall_dimensions'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['overall_dimensions']),
                    array('post_id' => $driver_id, 'meta_key' => 'overall_dimensions'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'overall_dimensions';
                } else {
                    $errors[] = 'overall_dimensions';
                }
            }
            
            // Update vin
            if (isset($vehicle_data['vin'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $vehicle_data['vin']),
                    array('post_id' => $driver_id, 'meta_key' => 'vin'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'vin';
                } else {
                    $errors[] = 'vin';
                }
            }
            
            // Update equipment data
            if (isset($vehicle_data['equipment'])) {
                $equipment_result = $this->update_equipment_data($driver_id, $vehicle_data['equipment']);
                if ($equipment_result['success']) {
                    $updated_fields = array_merge($updated_fields, $equipment_result['updated_fields']);
                } else {
                    $errors = array_merge($errors, $equipment_result['errors']);
                }
            }
            
            return array(
                'success' => empty($errors),
                'updated_fields' => $updated_fields,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI update_vehicle_data error: ' . $e->getMessage());
            return array(
                'success' => false,
                'updated_fields' => $updated_fields,
                'errors' => array('vehicle_update_failed')
            );
        }
    }
    
    /**
     * Update team driver data
     * 
     * @param int $driver_id
     * @param array $team_data
     * @return array
     */
    private function update_team_driver_data($driver_id, $team_data) {
        global $wpdb;
        
        $updated_fields = array();
        $errors = array();
        
        try {
            // Update team_driver_enabled
            if (isset($team_data['enabled'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $team_data['enabled'] ? '1' : '0'),
                    array('post_id' => $driver_id, 'meta_key' => 'team_driver'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'team_driver';
                } else {
                    $errors[] = 'team_driver';
                }
            }
            
            // Update team_driver_name
            if (isset($team_data['name'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $team_data['name']),
                    array('post_id' => $driver_id, 'meta_key' => 'team_driver_name'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'team_driver_name';
                } else {
                    $errors[] = 'team_driver_name';
                }
            }
            
            // Update team_driver_phone
            if (isset($team_data['phone'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $team_data['phone']),
                    array('post_id' => $driver_id, 'meta_key' => 'team_driver_phone'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'team_driver_phone';
                } else {
                    $errors[] = 'team_driver_phone';
                }
            }
            
            // Update team_driver_email
            if (isset($team_data['email'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $team_data['email']),
                    array('post_id' => $driver_id, 'meta_key' => 'team_driver_email'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'team_driver_email';
                } else {
                    $errors[] = 'team_driver_email';
                }
            }
            
            // Update team_driver_date_of_birth
            if (isset($team_data['date_of_birth'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $team_data['date_of_birth']),
                    array('post_id' => $driver_id, 'meta_key' => 'team_driver_date_of_birth'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'team_driver_date_of_birth';
                } else {
                    $errors[] = 'team_driver_date_of_birth';
                }
            }
            
            return array(
                'success' => empty($errors),
                'updated_fields' => $updated_fields,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI update_team_driver_data error: ' . $e->getMessage());
            return array(
                'success' => false,
                'updated_fields' => $updated_fields,
                'errors' => array('team_driver_update_failed')
            );
        }
    }
    
    /**
     * Update emergency contact data
     * 
     * @param int $driver_id
     * @param array $emergency_data
     * @return array
     */
    private function update_emergency_contact_data($driver_id, $emergency_data) {
        global $wpdb;
        
        $updated_fields = array();
        $errors = array();
        
        try {
            // Update emergency_contact_name
            if (isset($emergency_data['name'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $emergency_data['name']),
                    array('post_id' => $driver_id, 'meta_key' => 'emergency_contact_name'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'emergency_contact_name';
                } else {
                    $errors[] = 'emergency_contact_name';
                }
            }
            
            // Update emergency_contact_phone
            if (isset($emergency_data['phone'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $emergency_data['phone']),
                    array('post_id' => $driver_id, 'meta_key' => 'emergency_contact_phone'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'emergency_contact_phone';
                } else {
                    $errors[] = 'emergency_contact_phone';
                }
            }
            
            // Update emergency_contact_relation
            if (isset($emergency_data['relation'])) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $emergency_data['relation']),
                    array('post_id' => $driver_id, 'meta_key' => 'emergency_contact_relation'),
                    array('%s'),
                    array('%d', '%s')
                );
                if ($result !== false) {
                    $updated_fields[] = 'emergency_contact_relation';
                } else {
                    $errors[] = 'emergency_contact_relation';
                }
            }
            
            return array(
                'success' => empty($errors),
                'updated_fields' => $updated_fields,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI update_emergency_contact_data error: ' . $e->getMessage());
            return array(
                'success' => false,
                'updated_fields' => $updated_fields,
                'errors' => array('emergency_contact_update_failed')
            );
        }
    }
    
    /**
     * Update equipment data
     * 
     * @param int $driver_id
     * @param array $equipment_data
     * @return array
     */
    private function update_equipment_data($driver_id, $equipment_data) {
        global $wpdb;
        
        $updated_fields = array();
        $errors = array();
        
        try {
            $equipment_fields = array(
                'side_door' => 'side_door',
                'load_bars' => 'load_bars',
                'printer' => 'printer',
                'sleeper' => 'sleeper',
                'ppe' => 'ppe',
                'e_tracks' => 'e_tracks',
                'pallet_jack' => 'pallet_jack',
                'lift_gate' => 'lift_gate',
                'dolly' => 'dolly',
                'ramp' => 'ramp'
            );
            
            foreach ($equipment_fields as $api_field => $db_field) {
                if (isset($equipment_data[$api_field])) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'drivers_meta',
                        array('meta_value' => $equipment_data[$api_field] ? '1' : '0'),
                        array('post_id' => $driver_id, 'meta_key' => $db_field),
                        array('%s'),
                        array('%d', '%s')
                    );
                    if ($result !== false) {
                        $updated_fields[] = $db_field;
                    } else {
                        $errors[] = $db_field;
                    }
                }
            }
            
            return array(
                'success' => empty($errors),
                'updated_fields' => $updated_fields,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI update_equipment_data error: ' . $e->getMessage());
            return array(
                'success' => false,
                'updated_fields' => $updated_fields,
                'errors' => array('equipment_update_failed')
            );
        }
    }
    
    /**
     * Update current location data with comparison
     * 
     * @param int $driver_id
     * @param array $location_data
     * @param array $current_meta
     * @return array
     */
    private function update_current_location_with_comparison($driver_id, $location_data, $current_meta) {
        global $wpdb;
        
        $changed_fields = array();
        $errors = array();
        
        try {
            $location_fields = array(
                'zipcode' => 'current_zipcode',
                'city' => 'current_city',
                'state' => 'current_location',
                'coordinates' => array('lat' => 'latitude', 'lng' => 'longitude'),
                'status' => 'driver_status'
            );
            
            foreach ($location_fields as $api_field => $db_field) {
                if (isset($location_data[$api_field])) {
                    if (is_array($db_field)) {
                        // Handle coordinates
                        foreach ($db_field as $coord_key => $meta_key) {
                            if (isset($location_data[$api_field][$coord_key])) {
                                $new_value = $location_data[$api_field][$coord_key];
                                $old_value = $current_meta[$meta_key] ?? '';
                                
                                if ($old_value != $new_value) {
                                    $result = $wpdb->update(
                                        $wpdb->prefix . 'drivers_meta',
                                        array('meta_value' => $new_value),
                                        array('post_id' => $driver_id, 'meta_key' => $meta_key),
                                        array('%s'),
                                        array('%d', '%s')
                                    );
                                    
                                    if ($result !== false) {
                                        $changed_fields[] = array(
                                            'field' => $meta_key,
                                            'old_value' => $old_value,
                                            'new_value' => $new_value
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        // Handle single field
                        $new_value = $location_data[$api_field];
                        $old_value = $current_meta[$db_field] ?? '';
                        
                        if ($old_value != $new_value) {
                            $result = $wpdb->update(
                                $wpdb->prefix . 'drivers_meta',
                                array('meta_value' => $new_value),
                                array('post_id' => $driver_id, 'meta_key' => $db_field),
                                array('%s'),
                                array('%d', '%s')
                            );
                            
                            if ($result !== false) {
                                $changed_fields[] = array(
                                    'field' => $db_field,
                                    'old_value' => $old_value,
                                    'new_value' => $new_value
                                );
                            }
                        }
                    }
                }
            }
            
            // Handle available_date separately (update main table, not meta table)
            if (isset($location_data['available_date'])) {
                $new_available_date = $location_data['available_date'];
                
                // Get current value from main table
                $main_table = $wpdb->prefix . 'drivers';
                $current_available_date = $wpdb->get_var($wpdb->prepare(
                    "SELECT date_available FROM $main_table WHERE id = %d",
                    $driver_id
                ));
                
                if ($current_available_date != $new_available_date) {
                    $result = $wpdb->update(
                        $main_table,
                        array('date_available' => $new_available_date),
                        array('id' => $driver_id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($result !== false) {
                        $changed_fields[] = array(
                            'field' => 'date_available',
                            'old_value' => $current_available_date ?: '',
                            'new_value' => $new_available_date
                        );
                    }
                }
            }
            
            // Auto-update status_date if zipcode or coordinates were changed
            $location_changed = false;
            foreach ($changed_fields as $change) {
                if (in_array($change['field'], ['current_zipcode', 'latitude', 'longitude'])) {
                    $location_changed = true;
                    break;
                }
            }
            
            if ($location_changed) {
                // Get current time in New York timezone
                $ny_timezone = new DateTimeZone('America/New_York');
                $ny_time = new DateTime('now', $ny_timezone);
                $current_time = $ny_time->format('Y-m-d H:i:s');
                
                $wpdb->update(
                    $wpdb->prefix . 'drivers_meta',
                    array('meta_value' => $current_time),
                    array('post_id' => $driver_id, 'meta_key' => 'status_date'),
                    array('%s'),
                    array('%d', '%s')
                );
            }
            
            return array(
                'success' => true,
                'changed_fields' => $changed_fields,
                'errors' => array()
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'changed_fields' => array(),
                'errors' => array('location_update_failed')
            );
        }
    }
    
    /**
     * Update contact data with comparison
     * 
     * @param int $driver_id
     * @param array $contact_data
     * @param array $current_meta
     * @return array
     */
    private function update_contact_data_with_comparison($driver_id, $contact_data, $current_meta) {
        global $wpdb;
        
        $changed_fields = array();
        $errors = array();
        
        try {
            $contact_fields = array(
                'driver_name' => 'driver_name',
                'driver_phone' => 'driver_phone',
                'driver_email' => 'driver_email',
                'home_location' => 'home_location',
                'city' => 'city',
                'city_state_zip' => 'city_state_zip',
                'date_of_birth' => 'dob',  // ← Исправлено
                'languages' => 'languages',
                'preferred_distance' => 'preferred_distance'
            );
            
            foreach ($contact_fields as $api_field => $db_field) {
                if (isset($contact_data[$api_field])) {
                    $new_value = $contact_data[$api_field];
                    $old_value = $current_meta[$db_field] ?? '';
                    
                    
                    if ($old_value != $new_value) {
                        $result = $wpdb->update(
                            $wpdb->prefix . 'drivers_meta',
                            array('meta_value' => $new_value),
                            array('post_id' => $driver_id, 'meta_key' => $db_field),
                            array('%s'),
                            array('%d', '%s')
                        );
                        
                        if ($result !== false) {
                            $changed_fields[] = array(
                                'field' => $db_field,
                                'old_value' => $old_value,
                                'new_value' => $new_value
                            );
                        }
                    }
                }
            }
            
            // Handle team driver data
            if (isset($contact_data['team_driver'])) {
                $team_result = $this->update_team_driver_data_with_comparison($driver_id, $contact_data['team_driver'], $current_meta);
                if ($team_result['success']) {
                    $changed_fields = array_merge($changed_fields, $team_result['changed_fields']);
                }
            }
            
            // Handle emergency contact data
            if (isset($contact_data['emergency_contact'])) {
                $emergency_result = $this->update_emergency_contact_data_with_comparison($driver_id, $contact_data['emergency_contact'], $current_meta);
                if ($emergency_result['success']) {
                    $changed_fields = array_merge($changed_fields, $emergency_result['changed_fields']);
                }
            }
            
            return array(
                'success' => true,
                'changed_fields' => $changed_fields,
                'errors' => array()
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'changed_fields' => array(),
                'errors' => array('contact_update_failed')
            );
        }
    }
    
    /**
     * Update vehicle data with comparison
     * 
     * @param int $driver_id
     * @param array $vehicle_data
     * @param array $current_meta
     * @return array
     */
    private function update_vehicle_data_with_comparison($driver_id, $vehicle_data, $current_meta) {
        global $wpdb;
        
        $changed_fields = array();
        $errors = array();
        
        try {
            $vehicle_fields = array(
                'make' => 'vehicle_make',  // ← Исправлено
                'model' => 'vehicle_model',  // ← Исправлено
                'year' => 'vehicle_year',  // ← Исправлено
                'payload' => 'payload',
                'cargo_space_dimensions' => 'dimensions',
                'overall_dimensions' => 'overall_dimensions',
                'vin' => 'vin'
            );
            
            foreach ($vehicle_fields as $api_field => $db_field) {
                if (isset($vehicle_data[$api_field])) {
                    $new_value = $vehicle_data[$api_field];
                    $old_value = $current_meta[$db_field] ?? '';
                    
                    // Debug: Log comparison for dimensions only
                    if ($db_field === 'dimensions') {
                        error_log('TMSDriversAPI vehicle comparison - Field: ' . $db_field);
                        error_log('TMSDriversAPI vehicle comparison - Old value: "' . $old_value . '"');
                        error_log('TMSDriversAPI vehicle comparison - New value: "' . $new_value . '"');
                        error_log('TMSDriversAPI vehicle comparison - Are equal: ' . ($old_value == $new_value ? 'YES' : 'NO'));
                    }
                    
                    if ($old_value != $new_value) {
                        $result = $wpdb->update(
                            $wpdb->prefix . 'drivers_meta',
                            array('meta_value' => $new_value),
                            array('post_id' => $driver_id, 'meta_key' => $db_field),
                            array('%s'),
                            array('%d', '%s')
                        );
                        
                        if ($result !== false) {
                            $changed_fields[] = array(
                                'field' => $db_field,
                                'old_value' => $old_value,
                                'new_value' => $new_value
                            );
                        }
                    }
                }
            }
            
            // Handle vehicle type
            if (isset($vehicle_data['type'])) {
                $new_value = is_array($vehicle_data['type']) ? $vehicle_data['type']['value'] : $vehicle_data['type'];
                $old_value = $current_meta['vehicle_type'] ?? '';
                
                if ($old_value != $new_value) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'drivers_meta',
                        array('meta_value' => $new_value),
                        array('post_id' => $driver_id, 'meta_key' => 'vehicle_type'),
                        array('%s'),
                        array('%d', '%s')
                    );
                    
                    if ($result !== false) {
                        $changed_fields[] = array(
                            'field' => 'vehicle_type',
                            'old_value' => $old_value,
                            'new_value' => $new_value
                        );
                    }
                }
            }
            
            // Handle equipment data
            if (isset($vehicle_data['equipment'])) {
                $equipment_result = $this->update_equipment_data_with_comparison($driver_id, $vehicle_data['equipment'], $current_meta);
                if ($equipment_result['success']) {
                    $changed_fields = array_merge($changed_fields, $equipment_result['changed_fields']);
                }
            }
            
            return array(
                'success' => true,
                'changed_fields' => $changed_fields,
                'errors' => array()
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'changed_fields' => array(),
                'errors' => array('vehicle_update_failed')
            );
        }
    }
    
    /**
     * Update team driver data with comparison
     * 
     * @param int $driver_id
     * @param array $team_data
     * @param array $current_meta
     * @return array
     */
    private function update_team_driver_data_with_comparison($driver_id, $team_data, $current_meta) {
        global $wpdb;
        
        $changed_fields = array();
        
        try {
            $team_fields = array(
                'enabled' => 'team_driver_enabled',  // ← Исправлено
                'name' => 'team_driver_name',
                'phone' => 'team_driver_phone',
                'email' => 'team_driver_email',
                'date_of_birth' => 'team_driver_dob'  // ← Исправлено
            );
            
            foreach ($team_fields as $api_field => $db_field) {
                if (isset($team_data[$api_field])) {
                    $new_value = $team_data[$api_field];
                    $old_value = $current_meta[$db_field] ?? '';
                    
                    if ($old_value != $new_value) {
                        $result = $wpdb->update(
                            $wpdb->prefix . 'drivers_meta',
                            array('meta_value' => $new_value),
                            array('post_id' => $driver_id, 'meta_key' => $db_field),
                            array('%s'),
                            array('%d', '%s')
                        );
                        
                        if ($result !== false) {
                            $changed_fields[] = array(
                                'field' => $db_field,
                                'old_value' => $old_value,
                                'new_value' => $new_value
                            );
                        }
                    }
                }
            }
            
            return array(
                'success' => true,
                'changed_fields' => $changed_fields
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'changed_fields' => array()
            );
        }
    }
    
    /**
     * Update emergency contact data with comparison
     * 
     * @param int $driver_id
     * @param array $emergency_data
     * @param array $current_meta
     * @return array
     */
    private function update_emergency_contact_data_with_comparison($driver_id, $emergency_data, $current_meta) {
        global $wpdb;
        
        $changed_fields = array();
        
        try {
            $emergency_fields = array(
                'name' => 'emergency_contact_name',
                'phone' => 'emergency_contact_phone',
                'relation' => 'emergency_contact_relation'
            );
            
            foreach ($emergency_fields as $api_field => $db_field) {
                if (isset($emergency_data[$api_field])) {
                    $new_value = $emergency_data[$api_field];
                    $old_value = $current_meta[$db_field] ?? '';
                    
                    if ($old_value != $new_value) {
                        $result = $wpdb->update(
                            $wpdb->prefix . 'drivers_meta',
                            array('meta_value' => $new_value),
                            array('post_id' => $driver_id, 'meta_key' => $db_field),
                            array('%s'),
                            array('%d', '%s')
                        );
                        
                        if ($result !== false) {
                            $changed_fields[] = array(
                                'field' => $db_field,
                                'old_value' => $old_value,
                                'new_value' => $new_value
                            );
                        }
                    }
                }
            }
            
            return array(
                'success' => true,
                'changed_fields' => $changed_fields
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'changed_fields' => array()
            );
        }
    }
    
    /**
     * Update equipment data with comparison
     * 
     * @param int $driver_id
     * @param array $equipment_data
     * @param array $current_meta
     * @return array
     */
    private function update_equipment_data_with_comparison($driver_id, $equipment_data, $current_meta) {
        global $wpdb;
        
        $changed_fields = array();
        
        try {
            $equipment_fields = array(
                'side_door' => 'side_door',
                'load_bars' => 'load_bars',
                'printer' => 'printer',
                'sleeper' => 'sleeper',
                'ppe' => 'ppe',
                'e_tracks' => 'e_tracks',
                'pallet_jack' => 'pallet_jack',
                'lift_gate' => 'lift_gate',
                'dolly' => 'dolly',
                'ramp' => 'ramp'
            );
            
            foreach ($equipment_fields as $api_field => $db_field) {
                if (isset($equipment_data[$api_field])) {
                    $new_value = $equipment_data[$api_field] ? '1' : '0';
                    $old_value = $current_meta[$db_field] ?? '0';
                    
                    if ($old_value != $new_value) {
                        $result = $wpdb->update(
                            $wpdb->prefix . 'drivers_meta',
                            array('meta_value' => $new_value),
                            array('post_id' => $driver_id, 'meta_key' => $db_field),
                            array('%s'),
                            array('%d', '%s')
                        );
                        
                        if ($result !== false) {
                            $changed_fields[] = array(
                                'field' => $db_field,
                                'old_value' => $old_value,
                                'new_value' => $new_value
                            );
                        }
                    }
                }
            }
            
            return array(
                'success' => true,
                'changed_fields' => $changed_fields
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'changed_fields' => array()
            );
        }
    }
    
    /**
     * Create driver update log entry
     * 
     * @param int $driver_id
     * @param int $user_id
     * @param array $changed_fields
     * @return array
     */
    private function create_driver_update_log($driver_id, $user_id, $changed_fields) {
        try {
            // Create log message
            $log_message = '<strong>Data updated from external application</strong><br><br>';
            
            foreach ($changed_fields as $change) {
                $field_name = $this->format_field_name($change['field']);
                $log_message .= '<strong>' . $field_name . '</strong> - Value changed<br>';
                $log_message .= '<strong>New meaning</strong>: <span style="color: green">' . $change['new_value'] . '</span><br>';
                $log_message .= '<strong>Old meaning</strong>: <span style="color: red">' . $change['old_value'] . '</span><br><br>';
            }
            
            // Create log entry using TMSLogs
            $log_controller = new TMSLogs();
            $log_result = $log_controller->create_one_log(array(
                'user_id' => $user_id,
                'post_id' => $driver_id,
                'message' => $log_message,
                'post_type' => 'driver'
            ));
            
            return array(
                'success' => $log_result['insert'] !== false,
                'log_id' => $log_result['insert'] ?? null
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI create_driver_update_log error: ' . $e->getMessage());
            return array(
                'success' => false,
                'log_id' => null
            );
        }
    }
    
    /**
     * Create driver location update log entry
     * 
     * @param int $driver_id
     * @param int $user_id
     * @param array $changed_fields
     * @return array
     */
    private function create_driver_location_update_log($driver_id, $user_id, $changed_fields) {
        try {
            // Create log message
            $log_message = '<strong>Driver location updated via API</strong><br><br>';
            
            foreach ($changed_fields as $change) {
                $field_name = $this->format_field_name($change['field']);
                $log_message .= '<strong>' . $field_name . '</strong> - Value changed<br>';
                $log_message .= '<strong>Old value</strong>: <span style="color: red">' . esc_html($change['old_value']) . '</span><br>';
                $log_message .= '<strong>New value</strong>: <span style="color: green">' . esc_html($change['new_value']) . '</span><br><br>';
            }
            
            // Create log entry using TMSLogs
            $log_controller = new TMSLogs();
            $log_result = $log_controller->create_one_log(array(
                'user_id' => $user_id,
                'post_id' => $driver_id,
                'message' => $log_message,
                'post_type' => 'driver'
            ));
            
            return array(
                'success' => $log_result['insert'] !== false,
                'log_id' => $log_result['insert'] ?? null
            );
            
        } catch (Exception $e) {
            error_log('TMSDriversAPI create_driver_location_update_log error: ' . $e->getMessage());
            return array(
                'success' => false,
                'log_id' => null
            );
        }
    }
    
    /**
     * Format field name for display
     * 
     * @param string $field
     * @return string
     */
    private function format_field_name($field) {
        return ucwords(str_replace('_', ' ', $field));
    }
    
    /**
     * Get all users with their basic info and ACF fields
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_users($request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $search = $request->get_param('search');
            
            // Calculate offset
            $offset = ($page - 1) * $per_page;
            
            // Build query args
            $args = array(
                'number' => $per_page,
                'offset' => $offset,
                'orderby' => 'display_name',
                'order' => 'ASC'
            );
            
            // Add search if provided
            if (!empty($search)) {
                $args['search'] = '*' . $search . '*';
                $args['search_columns'] = array('user_login', 'user_email', 'display_name', 'first_name', 'last_name');
            }
            
            // Get users
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            
            // Get total count
            $total_args = $args;
            unset($total_args['number'], $total_args['offset']);
            $total_query = new WP_User_Query($total_args);
            $total_users = $total_query->get_total();
            
            // Format user data
            $formatted_users = array();
            foreach ($users as $user) {
                $user_data = array(
                    'id' => $user->ID,
                    'user_email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'roles' => $user->roles,
                    'user_registered' => $user->user_registered,
                    'acf_fields' => array(
                        'permission_view' => get_field('permission_view', 'user_' . $user->ID),
                        'initials_color' => get_field('initials_color', 'user_' . $user->ID),
                        'work_location' => get_field('work_location', 'user_' . $user->ID),
                        'phone_number' => get_field('phone_number', 'user_' . $user->ID),
                        'flt' => get_field('flt', 'user_' . $user->ID)
                    )
                );
                
                $formatted_users[] = $user_data;
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $formatted_users,
                'pagination' => array(
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_users' => $total_users,
                    'total_pages' => ceil($total_users / $per_page)
                ),
                'timestamp' => current_time('mysql'),
                'api_version' => '1.0'
            ), 200);
            
        } catch (Exception $e) {
            return new WP_Error(
                'api_error',
                'An error occurred while fetching users: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
}

// Initialize the API
new TMSDriversAPI();
