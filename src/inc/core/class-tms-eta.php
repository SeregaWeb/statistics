<?php

class TMSEta extends TMSReportsHelper {
    
    public $table_name = 'eta_records';
    public $helper = false;
    
    public function __construct() {
        // TMSReportsHelper doesn't have a constructor, so we don't need to call parent
        $this->helper = new TMSCommonHelper();
    }
    
    public function init() {
        $this->create_table();
        $this->ajax_actions();
        
        // Schedule cron after WordPress is fully loaded
        add_action('wp_loaded', [$this, 'init_cron']);
    }
    
    /**
     * Create ETA records table
     */
    public function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        // Check if table already exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                load_number varchar(50) NOT NULL,
                eta_datetime datetime NOT NULL,
                timezone varchar(20) NOT NULL,
                status enum('active', 'sended') DEFAULT 'active',
                user_id int(11) NOT NULL,
                is_flt tinyint(1) DEFAULT 0,
                project varchar(50) NOT NULL DEFAULT '',
                eta_type enum('pickup', 'delivery') NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_load_type_user_project (load_number, eta_type, is_flt, user_id, project),
                KEY idx_load_number (load_number),
                KEY idx_status (status),
                KEY idx_user_id (user_id),
                KEY idx_project (project)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Table exists, check if we need to update the unique key
            $this->update_table_structure_if_needed();
        }
    }
    
    /**
     * Update table structure if needed (for existing tables)
     */
    private function update_table_structure_if_needed() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        // Check if project column exists
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
        $has_project_column = in_array('project', $columns);
        
        if (!$has_project_column) {
            // Add project column
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN project varchar(50) NOT NULL DEFAULT '' AFTER is_flt");
            // Add index for project
            $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_project (project)");
        }
        
        // Check current indexes
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        
        $new_key_exists = false;
        $old_key_exists = false;
        
        foreach ($indexes as $index) {
            if ($index->Key_name === 'unique_load_type_user_project') {
                $new_key_exists = true;
            }
            if ($index->Key_name === 'unique_load_type_user') {
                $old_key_exists = true;
            }
        }
        
        // If we have the old key but not the new one, update the structure
        if ($old_key_exists && !$new_key_exists) {
            // Drop the old unique key
            $wpdb->query("ALTER TABLE $table_name DROP INDEX unique_load_type_user");
            
            // Add the new unique key with project
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY unique_load_type_user_project (load_number, eta_type, is_flt, user_id, project)");
        }
    }
    
    /**
     * Register AJAX actions
     */
    public function ajax_actions() {
        $actions = array(
            'save_eta_record' => 'save_eta_record',
            'get_eta_record' => 'get_eta_record',
            'get_eta_record_for_display' => 'get_eta_record_for_display_ajax',
            'test_eta_notifications' => 'test_eta_notifications',
            'create_test_eta_records' => 'create_test_eta_records',
            'debug_reference_number' => 'debug_reference_number',
        );
        
        foreach ($actions as $ajax_action => $method) {
            add_action("wp_ajax_{$ajax_action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$ajax_action}", [$this->helper, 'need_login']);
        }
    }

    /**
     * Debug helper: return reference_number by load id (supports FLT)
     */
    public function debug_reference_number() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            wp_send_json_error( [ 'message' => 'Invalid request' ] );
        }

        $MY_INPUT = filter_var_array( $_POST, [
            'load_id' => FILTER_SANITIZE_NUMBER_INT,
            'is_flt'  => FILTER_SANITIZE_NUMBER_INT,
        ] );

        $load_id = (int) ( $MY_INPUT['load_id'] ?? 0 );
        $is_flt  = (bool) ( $MY_INPUT['is_flt'] ?? 0 );

        if ( ! $load_id ) {
            wp_send_json_error( [ 'message' => 'Missing load_id' ] );
        }

        // Resolve table set and fetch reference
        if ( $is_flt ) {
            $reports = new TMSReportsFlt();
        } else {
            $reports = new TMSReports();
        }

        $project = $reports->project ?: '';
        $reference_number = $this->get_reference_number_by_load( $load_id, $is_flt, $project );

        wp_send_json_success( [
            'load_id'           => $load_id,
            'is_flt'            => $is_flt,
            'project'           => $project,
            'meta_table'        => $reports->table_meta,
            'reference_number'  => $reference_number,
        ] );
    }
    
    /**
     * Save or update ETA record
     */
    public function save_eta_record() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request']);
        }
        
        global $wpdb;
        
        $MY_INPUT = filter_var_array($_POST, [
            'load_id' => FILTER_SANITIZE_NUMBER_INT,
            'date' => FILTER_SANITIZE_STRING,
            'time' => FILTER_SANITIZE_STRING,
            'timezone' => FILTER_SANITIZE_STRING,
            'state' => FILTER_SANITIZE_STRING,
            'eta_type' => FILTER_SANITIZE_STRING,
            'is_flt' => FILTER_SANITIZE_NUMBER_INT,
        ]);
        
        if (!$MY_INPUT['load_id'] || !$MY_INPUT['date'] || !$MY_INPUT['time'] || !$MY_INPUT['eta_type']) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        $load_id = $MY_INPUT['load_id'];
        $date = $MY_INPUT['date'];
        $time = $MY_INPUT['time'];
        $timezone = $MY_INPUT['timezone'] ?: '';
        $state = $MY_INPUT['state'] ?: '';
        $eta_type = $MY_INPUT['eta_type']; // 'pickup' or 'delivery'
        $is_flt = (bool) $MY_INPUT['is_flt'];
        $user_id = get_current_user_id();
        
        // Get current user's project
        $reports = $is_flt ? new TMSReportsFlt() : new TMSReports();
        $project = $reports->project ?: '';
        
        // Use timezone from form if provided (it's already correct, calculated with coordinates)
        // Only fallback to state-based calculation if timezone is not provided
        if (empty($timezone) && $state) {
            // Fallback to state-based calculation only if no timezone provided
            $helper = new TMSReportsHelper();
            $timezone = $helper->get_timezone_by_state($state, $date);
        }
        
        // Combine date and time
        $eta_datetime = $date . ' ' . $time . ':00';
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        // Check if record already exists for this user and project
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $table_name WHERE load_number = %s AND eta_type = %s AND is_flt = %d AND user_id = %d AND project = %s",
            $load_id, $eta_type, $is_flt, $user_id, $project
        ));
        
        if ($existing_record) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                [
                    'eta_datetime' => $eta_datetime,
                    'timezone' => $timezone,
                    'status' => 'active',
                    'user_id' => $user_id,
                    'project' => $project,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $existing_record->id],
                ['%s', '%s', '%s', '%d', '%s', '%s'],
                ['%d']
            );
            
            if ($result !== false) {
                wp_send_json_success([
                    'message' => 'ETA record updated successfully',
                    'action' => 'updated',
                    'record_id' => $existing_record->id
                ]);
            }
        } else {
            // Create new record
            $result = $wpdb->insert(
                $table_name,
                [
                    'load_number' => $load_id,
                    'eta_datetime' => $eta_datetime,
                    'timezone' => $timezone,
                    'status' => 'active',
                    'user_id' => $user_id,
                    'is_flt' => $is_flt,
                    'project' => $project,
                    'eta_type' => $eta_type
                ],
                ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
            );
            
            if ($result !== false) {
                wp_send_json_success([
                    'message' => 'ETA record created successfully',
                    'action' => 'created',
                    'record_id' => $wpdb->insert_id
                ]);
            }
        }
        
        wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
    }

    /**
     * Create or update ETA record from server-side (e.g. when load status changes to loaded-enroute).
     * Uses current user and project if not provided.
     *
     * @param int    $load_id     Load post ID.
     * @param string $eta_type    'pickup' or 'delivery'.
     * @param string $date        Date Y-m-d.
     * @param string $time        Time H:i (seconds optional).
     * @param string $timezone    Timezone string.
     * @param bool   $is_flt      Is FLT load.
     * @param int|null    $user_id   User ID (default current user).
     * @param string|null $project   Project (default from Reports).
     * @param bool   $create_only If true, only insert when no record exists; never update existing.
     * @return bool True on success.
     */
    public function create_or_update_eta_record( $load_id, $eta_type, $date, $time, $timezone, $is_flt, $user_id = null, $project = null, $create_only = false ) {
        global $wpdb;

        TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: called load_id=' . $load_id . ', eta_type=' . $eta_type . ', date=' . $date . ', time=' . $time . ', is_flt=' . ( $is_flt ? '1' : '0' ), 'eta-auto' );

        if ( ! $load_id || ! $date || ! $time || ! $eta_type ) {
            TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: validation fail (missing load_id/date/time/eta_type)', 'eta-auto' );
            return false;
        }

        if ( $user_id === null ) {
            $user_id = get_current_user_id();
        }
        if ( $project === null ) {
            $reports = $is_flt ? new TMSReportsFlt() : new TMSReports();
            $project = $reports->project ?: '';
        }

        TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: user_id=' . $user_id . ', project=' . $project, 'eta-auto' );

        $timezone = $timezone ?: '';
        if ( substr_count( trim( $time ), ':' ) === 1 ) {
            $time = trim( $time ) . ':00';
        }
        $eta_datetime = $date . ' ' . trim( $time );
        $table_name   = $wpdb->prefix . $this->table_name;

        $existing_record = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, status FROM $table_name WHERE load_number = %s AND eta_type = %s AND is_flt = %d AND user_id = %d AND project = %s",
            $load_id,
            $eta_type,
            $is_flt ? 1 : 0,
            $user_id,
            $project
        ) );

        if ( $existing_record ) {
            if ( $create_only ) {
                TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: record already exists (create_only), skip update id=' . $existing_record->id, 'eta-auto' );
                return true;
            }
            TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: updating existing record id=' . $existing_record->id, 'eta-auto' );
            $result = $wpdb->update(
                $table_name,
                [
                    'eta_datetime' => $eta_datetime,
                    'timezone'     => $timezone,
                    'status'       => 'active',
                    'user_id'      => $user_id,
                    'project'      => $project,
                    'updated_at'   => current_time( 'mysql' ),
                ],
                [ 'id' => $existing_record->id ],
                [ '%s', '%s', '%s', '%d', '%s', '%s' ],
                [ '%d' ]
            );
            if ( $result === false ) {
                TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: update failed, last_error=' . $wpdb->last_error, 'eta-auto' );
            }
            return $result !== false;
        }

        TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: inserting new record', 'eta-auto' );
        $result = $wpdb->insert(
            $table_name,
            [
                'load_number'   => $load_id,
                'eta_datetime'  => $eta_datetime,
                'timezone'      => $timezone,
                'status'        => 'active',
                'user_id'       => $user_id,
                'is_flt'        => $is_flt ? 1 : 0,
                'project'       => $project,
                'eta_type'      => $eta_type,
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
        );
        if ( $result === false ) {
            TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: insert failed, last_error=' . $wpdb->last_error, 'eta-auto' );
        } else {
            TMSLogger::log_to_file( '[ETA-auto] create_or_update_eta_record: insert ok, id=' . $wpdb->insert_id, 'eta-auto' );
        }
        return $result !== false;
    }
    
    /**
     * Get ETA record for specific load and type
     */
    public function get_eta_record() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request']);
        }
        
        global $wpdb;
        
        $MY_INPUT = filter_var_array($_POST, [
            'load_id' => FILTER_SANITIZE_NUMBER_INT,
            'eta_type' => FILTER_SANITIZE_STRING,
            'is_flt' => FILTER_SANITIZE_NUMBER_INT,
        ]);
        
        if (!$MY_INPUT['load_id'] || !$MY_INPUT['eta_type']) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        $load_id = $MY_INPUT['load_id'];
        $eta_type = $MY_INPUT['eta_type'];
        $is_flt = (bool) $MY_INPUT['is_flt'];
        $user_id = get_current_user_id();
        
        // Get current user's project
        $reports = $is_flt ? new TMSReportsFlt() : new TMSReports();
        $project = $reports->project ?: '';
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE load_number = %s AND eta_type = %s AND is_flt = %d AND user_id = %d AND project = %s",
            $load_id, $eta_type, $is_flt, $user_id, $project
        ));
        
        if ($record) {
            // Parse datetime
            $datetime = new DateTime($record->eta_datetime);
            $date = $datetime->format('Y-m-d');
            $time = $datetime->format('H:i');
            
            wp_send_json_success([
                'exists' => true,
                'date' => $date,
                'time' => $time,
                'timezone' => $record->timezone,
                'status' => $record->status,
                'record_id' => $record->id
            ]);
        } else {
            wp_send_json_success([
                'exists' => false
            ]);
        }
    }
    
    /**
     * Check if ETA record exists for load and type
     */
    public function eta_record_exists($load_id, $eta_type, $is_flt = false, $user_id = null, $project = null) {
        global $wpdb;

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($project === null) {
            $reports = $is_flt ? new TMSReportsFlt() : new TMSReports();
            $project = $reports->project ?: '';
        }
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        $record = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE load_number = %s AND eta_type = %s AND is_flt = %d AND user_id = %d AND project = %s",
            $load_id, $eta_type, $is_flt, $user_id, $project
        ));
        
        return !empty($record);
    }
    
    /**
     * Get ETA record data for display
     */
    public function get_eta_record_data($load_id, $eta_type, $is_flt = false, $user_id = null, $project = null) {
        global $wpdb;
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($project === null) {
            $reports = $is_flt ? new TMSReportsFlt() : new TMSReports();
            $project = $reports->project ?: '';
        }
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE load_number = %s AND eta_type = %s AND is_flt = %d AND user_id = %d AND project = %s",
            $load_id, $eta_type, $is_flt, $user_id, $project
        ));
        
        if ($record) {
            $datetime = new DateTime($record->eta_datetime);
            return [
                'exists' => true,
                'date' => $datetime->format('Y-m-d'),
                'time' => $datetime->format('H:i'),
                'timezone' => $record->timezone,
                'status' => $record->status,
                'record_id' => $record->id
            ];
        }
        
        return ['exists' => false];
    }
    
    /**
     * Get ETA record for any user (for display to all users with access)
     * Returns the most recent active ETA record for the load
     */
    public function get_eta_record_for_display($load_id, $eta_type, $is_flt = false, $project = null) {
        global $wpdb;
        
        if ($project === null) {
            $reports = $is_flt ? new TMSReportsFlt() : new TMSReports();
            $project = $reports->project ?: '';
        }
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        // Get the most recent active ETA record for this load (any user)
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE load_number = %s AND eta_type = %s AND is_flt = %d AND project = %s AND status = 'active'
             ORDER BY updated_at DESC, created_at DESC
             LIMIT 1",
            $load_id, $eta_type, $is_flt, $project
        ));
        
        if ($record) {
            $datetime = new DateTime($record->eta_datetime);
            return [
                'exists' => true,
                'eta_datetime' => $record->eta_datetime,
                'date' => $datetime->format('Y-m-d'),
                'time' => $datetime->format('H:i'),
                'timezone' => $record->timezone,
                'status' => $record->status,
                'record_id' => $record->id
            ];
        }
        
        return ['exists' => false];
    }
    
    /**
     * AJAX handler to get ETA record for display (for all users)
     */
    public function get_eta_record_for_display_ajax() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request']);
        }
        
        $MY_INPUT = filter_var_array($_POST, [
            'load_id' => FILTER_SANITIZE_NUMBER_INT,
            'eta_type' => FILTER_SANITIZE_STRING,
            'is_flt' => FILTER_SANITIZE_NUMBER_INT,
        ]);
        
        if (!$MY_INPUT['load_id'] || !$MY_INPUT['eta_type']) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        $load_id = $MY_INPUT['load_id'];
        $eta_type = $MY_INPUT['eta_type'];
        $is_flt = (bool) $MY_INPUT['is_flt'];
        
        $result = $this->get_eta_record_for_display($load_id, $eta_type, $is_flt);
        
        if ($result['exists']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_success(['exists' => false]);
        }
    }
    
    /**
     * Initialize cron job for ETA notifications
     */
    public function init_cron() {
        // Check if Action Scheduler is available
        if (!function_exists('as_schedule_recurring_action')) {
            error_log('Action Scheduler is not available. ETA notifications will not work.');
            return;
        }
        
        // Hook the action first (only once)
        if (!has_action('eta_notifications_check', [$this, 'check_eta_notifications'])) {
            add_action('eta_notifications_check', [$this, 'check_eta_notifications']);
        }
        
        // Schedule recurring action every 5 minutes
        if (!as_next_scheduled_action('eta_notifications_check')) {
            $result = as_schedule_recurring_action(
                time(), // Start time
                300,    // Interval: 5 minutes in seconds
                'eta_notifications_check', // Action hook
                [],      // Arguments
                'eta-notifications' // Group
            );
            
            if ($result) {
                error_log('ETA notifications cron scheduled successfully. Action ID: ' . $result);
            } else {
                error_log('Failed to schedule ETA notifications cron');
            }
        } 
    }
    
    /**
     * Get Action Scheduler status for ETA notifications
     */
    public function get_cron_status() {
        if (!function_exists('as_next_scheduled_action')) {
            return [
                'status' => 'error',
                'message' => 'Action Scheduler is not available'
            ];
        }
        
        $next_run = as_next_scheduled_action('eta_notifications_check');
        
        if ($next_run) {
            return [
                'status' => 'active',
                'next_run' => $next_run,
                'next_run_formatted' => date('Y-m-d H:i:s', $next_run),
                'message' => 'ETA notifications are scheduled'
            ];
        } else {
            return [
                'status' => 'inactive',
                'message' => 'ETA notifications are not scheduled'
            ];
        }
    }
    
    /**
     * Cancel ETA notifications cron job
     */
    public function cancel_cron() {
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('eta_notifications_check', [], 'eta-notifications');
            return true;
        }
        return false;
    }
    
    /**
     * Check ETA notifications and send emails
     */
    public function check_eta_notifications() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        // Get current New York time
        $ny_timezone = new DateTimeZone('America/New_York');
        $ny_now = new DateTime('now', $ny_timezone);
        
        // Get all active ETA records for today
        $today = $ny_now->format('Y-m-d');
        
        
        $eta_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE status = 'active' 
             AND DATE(eta_datetime) = %s",
            $today
        ));
        
        foreach ($eta_records as $record) {
            $this->process_eta_notification($record, $ny_now);
        }
    }
    
    /**
     * Process individual ETA notification
     */
    private function process_eta_notification($record, $ny_now) {
        // Parse the timezone from the record (e.g., "PDT (UTC-7)")
        $timezone_info = $this->parse_timezone_info($record->timezone);
        
        if (!$timezone_info) {
            return; // Skip if timezone parsing failed
        }
        
        // Create DateTime object for the ETA in the original timezone
        $eta_datetime = new DateTime($record->eta_datetime);
        
        // Convert ETA to New York time
        $eta_ny_time = $this->convert_to_ny_time($eta_datetime, $timezone_info);
        
        // Calculate notification time (30 minutes before ETA in NY time)
        $notification_time = clone $eta_ny_time;
        $notification_time->modify('-30 minutes');
        
        // Check if it's time to send notification (within 5-minute window)
        $time_diff = $ny_now->diff($notification_time);
        $minutes_diff = ($time_diff->h * 60) + $time_diff->i;
        
        // Email notifications disabled - all updates are shown in TMS interface
        // if (abs($minutes_diff) <= 5 && $ny_now >= $notification_time) {
        //     $this->send_eta_notification($record, $eta_ny_time);
        //     $this->mark_eta_as_sent($record->id);
        // }
    }
    
    /**
     * Parse timezone information from stored string
     */
    private function parse_timezone_info($timezone_string) {
        // Parse strings like "PDT (UTC-7)", "EDT (UTC-4)", etc.
        if (preg_match('/\(UTC([+-]\d+)\)/', $timezone_string, $matches)) {
            $utc_offset = intval($matches[1]);
            
            // Map UTC offsets to timezone names
            $timezone_map = [
                -8 => 'America/Los_Angeles',  // PST (Pacific Standard Time)
                -7 => 'America/Los_Angeles',  // PDT (Pacific Daylight Time)
                -6 => 'America/Denver',       // MDT (Mountain Daylight Time)
                -5 => 'America/Chicago',      // CDT (Central Daylight Time)
                -4 => 'America/New_York',     // EDT (Eastern Daylight Time)
            ];
            
            if (isset($timezone_map[$utc_offset])) {
                return [
                    'timezone' => $timezone_map[$utc_offset],
                    'offset' => $utc_offset
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Convert ETA datetime to New York time
     */
    private function convert_to_ny_time($eta_datetime, $timezone_info) {
        $original_timezone = new DateTimeZone($timezone_info['timezone']);
        $ny_timezone = new DateTimeZone('America/New_York');
        
        // Create a new DateTime object with the original timezone
        $eta_with_tz = new DateTime($eta_datetime->format('Y-m-d H:i:s'), $original_timezone);
        
        // Convert to New York time
        $eta_with_tz->setTimezone($ny_timezone);
        
        return $eta_with_tz;
    }
    
    /**
     * Send ETA notification email
     */
    private function send_eta_notification($record, $eta_ny_time) {
        global $global_options;
        
        // Get user who created the ETA
        $user = get_user_by('id', $record->user_id);
        if (!$user) {
            return;
        }
        
        // Get load information
        $load_info = $this->get_load_info($record->load_number);
        
        // Prepare email content
        $eta_type_text = $record->eta_type === 'pickup' ? 'Pickup' : 'Delivery';
        $eta_time_formatted = $eta_ny_time->format('g:i A T');
        
        // Resolve reference number from reports meta by load id, dataset (FLT or not), and project
        $project = isset($record->project) ? $record->project : null;
        $reference_number = $this->get_reference_number_by_load((int)$record->load_number, (bool)$record->is_flt, $project);
        $reference_display = $reference_number ?: $record->load_number;
        $subject = "ETA Reminder: {$eta_type_text} ETA in 30 minutes - Reference {$reference_display}";
        
        $message = "
        <h3>ETA Reminder</h3>
        <p>This is a reminder that your {$eta_type_text} ETA is approaching.</p>
        
        <p><strong>Load Number:</strong> {$reference_display}</p>
        <p><strong>ETA Type:</strong> {$eta_type_text}</p>
        <p><strong>ETA Time (New York):</strong> {$eta_time_formatted}</p>
        <p><strong>Original Timezone:</strong> {$record->timezone}</p>
        
        {$load_info}
        
        <p>Please ensure you're prepared for the scheduled {$eta_type_text}.</p>
        ";
        
        // Send email to the user who set the ETA
        $email_helper = new TMSEmails();
        $email_helper->send_custom_email($user->user_email, [
            'subject' => $subject,
            'project_name' => 'TMS',
            'subtitle' => 'ETA Reminder',
            'message' => $message,
        ]);
    }
    
    /**
     * Get load information for email
     */
    private function get_load_info($load_number) {
        // This would need to be implemented based on your load data structure
        // For now, return basic info
        return "<p><strong>Load Details:</strong> Please check the TMS system for complete load information.</p>";
    }

    /**
     * Get reference_number meta by load id for regular or FLT datasets
     */
    private function get_reference_number_by_load($load_id, $is_flt = false, $project = null) {
        global $wpdb;

        if (!$load_id) {
            return '';
        }

        // If project is provided, use it; otherwise get from current user
        if ($project === null) {
            // Determine meta table by dataset (current user's project is used by report classes)
            if ($is_flt) {
                $reports = new TMSReportsFlt();
            } else {
                $reports = new TMSReports();
            }
            $project = $reports->project;
        }

        // Build table meta name based on project and FLT flag
        if ($is_flt) {
            $table_meta = $wpdb->prefix . 'reportsmeta_flt_' . strtolower($project);
        } else {
            $table_meta = $wpdb->prefix . 'reportsmeta_' . strtolower($project);
        }

        // Fetch reference_number from meta
        $sql = $wpdb->prepare(
            "SELECT meta_value FROM {$table_meta} WHERE post_id = %d AND meta_key = 'reference_number' LIMIT 1",
            $load_id
        );
        $ref = (string) $wpdb->get_var($sql);

        return trim($ref);
    }
    
    /**
     * Mark ETA record as sent
     */
    private function mark_eta_as_sent($record_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->table_name;
        
        $wpdb->update(
            $table_name,
            ['status' => 'sended'],
            ['id' => $record_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Test ETA notifications (for debugging)
     */
    public function test_eta_notifications() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request']);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        // Manually trigger the notification check
        $this->check_eta_notifications();
        
        wp_send_json_success(['message' => 'ETA notifications check completed']);
    }
    
    /**
     * Create test ETA records for today with different timezones
     */
    public function create_test_eta_records() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request']);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;
        
        // Get current New York time
        $ny_timezone = new DateTimeZone('America/New_York');
        $ny_now = new DateTime('now', $ny_timezone);
        $today = $ny_now->format('Y-m-d');
        
        // Create test records for different timezones
        $test_records = [
            [
                'load_number' => 'TEST001',
                'eta_datetime' => $today . ' 12:00:00', // 12:00 PM in original timezone
                'timezone' => 'PDT (UTC-7)',
                'eta_type' => 'pickup',
                'is_flt' => 0
            ],
            [
                'load_number' => 'TEST002', 
                'eta_datetime' => $today . ' 14:30:00', // 2:30 PM in original timezone
                'timezone' => 'CDT (UTC-5)',
                'eta_type' => 'delivery',
                'is_flt' => 0
            ],
            [
                'load_number' => 'TEST003',
                'eta_datetime' => $today . ' 16:45:00', // 4:45 PM in original timezone
                'timezone' => 'EDT (UTC-4)',
                'eta_type' => 'pickup',
                'is_flt' => 1
            ],
            [
                'load_number' => 'TEST004',
                'eta_datetime' => $today . ' 10:15:00', // 10:15 AM in original timezone
                'timezone' => 'MDT (UTC-6)',
                'eta_type' => 'delivery',
                'is_flt' => 0
            ]
        ];
        
        $created_count = 0;
        
        foreach ($test_records as $record) {
            // Check if record already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE load_number = %s AND eta_type = %s AND is_flt = %d",
                $record['load_number'], $record['eta_type'], $record['is_flt']
            ));
            
            if (!$existing) {
                $result = $wpdb->insert(
                    $table_name,
                    [
                        'load_number' => $record['load_number'],
                        'eta_datetime' => $record['eta_datetime'],
                        'timezone' => $record['timezone'],
                        'status' => 'active',
                        'user_id' => 1,
                        'is_flt' => $record['is_flt'],
                        'eta_type' => $record['eta_type']
                    ],
                    ['%s', '%s', '%s', '%s', '%d', '%d', '%s']
                );
                
                if ($result !== false) {
                    $created_count++;
                }
            }
        }
        
        wp_send_json_success([
            'message' => "Created {$created_count} test ETA records for today",
            'records_created' => $created_count
        ]);
    }
}
