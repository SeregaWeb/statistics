<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsTimer extends TMSReports {
	
	public $table_timers = 'timers';
	public $table_timer_logs = 'timer_logs';
	
	public $per_page_timers = 50;
	public $timer_logs = false;
	
	// Properties for pause tracking
	private $pause_start_time = null;
	private $current_pause_duration = 0;

	public function __construct() {
		// Initialize timer logs controller
		$this->timer_logs = new TMSTimerLogs();
		
		
	}

    public function init() {
          // Initialize tables
          $this->create_tables();
          
          $this->ajax_actions();
    }

    public function ajax_actions() {
        add_action( 'wp_ajax_start_timer', array( $this, 'ajax_start_timer' ) );
        add_action( 'wp_ajax_pause_timer', array( $this, 'ajax_pause_timer' ) );
        add_action( 'wp_ajax_resume_timer', array( $this, 'ajax_resume_timer' ) );
        add_action( 'wp_ajax_stop_timer', array( $this, 'ajax_stop_timer' ) );
        add_action( 'wp_ajax_update_timer', array( $this, 'ajax_update_timer' ) );
        add_action( 'wp_ajax_get_timer_status', array( $this, 'ajax_get_timer_status' ) );
        add_action( 'wp_ajax_get_timer_logs', array( $this, 'ajax_get_timer_logs' ) );
        add_action( 'wp_ajax_get_timer_logs_analytics', array( $this, 'ajax_get_timer_logs_analytics' ) );
        add_action( 'wp_ajax_get_timer_analytics', array( $this, 'ajax_get_timer_analytics' ) );
        add_action( 'wp_ajax_get_smart_analytics', array( $this, 'ajax_get_smart_analytics' ) );
        add_action( 'wp_ajax_export_timer_analytics', array( $this, 'ajax_export_timer_analytics' ) );
        add_action( 'wp_ajax_update_timers_dispatcher_id', array( $this, 'ajax_update_timers_dispatcher_id' ) );
    }
	
	/**
	 * Create database tables for timers system
	 */
	public function create_tables() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		// Table: timers
		$table_timers = $wpdb->prefix . $this->table_timers;
		$sql_timers = "CREATE TABLE $table_timers (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			id_load bigint(20) NOT NULL,
			id_user bigint(20) NOT NULL,
			dispatcher_id bigint(20) DEFAULT NULL COMMENT 'ID of dispatcher who owns the load',
			create_timer datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			stop_time datetime DEFAULT NULL,
			duration int(11) DEFAULT NULL COMMENT 'Duration in minutes',
			pause_duration int(11) DEFAULT 0 COMMENT 'Total pause time in minutes',
			project varchar(50) NOT NULL DEFAULT '',
			flt tinyint(1) NOT NULL DEFAULT 0,
			status enum('active', 'paused', 'stopped') NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_id_load (id_load),
			KEY idx_id_user (id_user),
			KEY idx_dispatcher_id (dispatcher_id),
			KEY idx_project (project),
			KEY idx_flt (flt),
			KEY idx_status (status),
			KEY idx_create_timer (create_timer),
			KEY idx_stop_time (stop_time),
			KEY idx_duration (duration),
			KEY idx_pause_duration (pause_duration),
			KEY idx_created_at (created_at),
			KEY idx_load_user (id_load, id_user),
			KEY idx_user_project (id_user, project),
			KEY idx_load_project (id_load, project),
			KEY idx_dispatcher_project (dispatcher_id, project)
		) $charset_collate;";
		
		// Table: timer_logs
		$table_timer_logs = $wpdb->prefix . $this->table_timer_logs;
		$sql_timer_logs = "CREATE TABLE $table_timer_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			id_load bigint(20) NOT NULL,
			id_user bigint(20) NOT NULL,
			dispatcher_id bigint(20) DEFAULT NULL COMMENT 'ID of dispatcher who owns the load',
			action varchar(100) NOT NULL,
			comment text DEFAULT NULL,
			project varchar(50) NOT NULL DEFAULT '',
			flt tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_id_load (id_load),
			KEY idx_id_user (id_user),
			KEY idx_dispatcher_id (dispatcher_id),
			KEY idx_action (action),
			KEY idx_project (project),
			KEY idx_flt (flt),
			KEY idx_created_at (created_at),
			KEY idx_load_user (id_load, id_user),
			KEY idx_user_project (id_user, project),
			KEY idx_load_project (id_load, project),
			KEY idx_user_action (id_user, action),
			KEY idx_load_action (id_load, action),
			KEY idx_dispatcher_project (dispatcher_id, project)
		) $charset_collate;";
		
		// Table: timer_analytics
		$table_timer_analytics = $wpdb->prefix . 'timer_analytics';
		$sql_timer_analytics = "CREATE TABLE $table_timer_analytics (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			id_user bigint(20) NOT NULL,
			date_analytics date NOT NULL,
			project varchar(50) NOT NULL DEFAULT '',
			flt tinyint(1) NOT NULL DEFAULT 0,
			total_timers_started int(11) DEFAULT 0 COMMENT 'Total timers started today',
			total_updates int(11) DEFAULT 0 COMMENT 'Total updates made today',
			yellow_zone_loads text DEFAULT NULL COMMENT 'JSON array of load IDs in yellow zone (1-2h)',
			red_zone_loads text DEFAULT NULL COMMENT 'JSON array of load IDs in red zone (2-4h)',
			black_zone_loads text DEFAULT NULL COMMENT 'JSON array of load IDs in black zone (4h+)',
			shift_type enum('morning','day','night') DEFAULT 'day',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_user_date_project_flt (id_user, date_analytics, project, flt),
			KEY idx_id_user (id_user),
			KEY idx_date_analytics (date_analytics),
			KEY idx_project (project),
			KEY idx_flt (flt),
			KEY idx_shift_type (shift_type),
			KEY idx_user_date (id_user, date_analytics),
			KEY idx_project_date (project, date_analytics)
		) $charset_collate;";
		
		// Execute table creation
		dbDelta( $sql_timers );
		dbDelta( $sql_timer_logs );
		dbDelta( $sql_timer_analytics );
		
		// Add dispatcher_id column to existing timers table if it doesn't exist
		$this->add_dispatcher_id_column();
		
		// Add dispatcher_id column to existing timer_logs table if it doesn't exist
		$this->add_timer_logs_dispatcher_id_column();
		
		// Update existing timers with dispatcher_id (only run once to avoid spam)
		if ( ! get_option( 'tms_timer_dispatcher_id_updated' ) ) {
			$this->update_existing_timers_dispatcher_id();
			update_option( 'tms_timer_dispatcher_id_updated', true );
		}
	}
	
	/**
	 * Add dispatcher_id column to existing timers table
	 */
	private function add_dispatcher_id_column() {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Check if dispatcher_id column exists
		$column_exists = $wpdb->get_results( $wpdb->prepare( "
			SELECT COLUMN_NAME 
			FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s 
			AND TABLE_NAME = %s 
			AND COLUMN_NAME = 'dispatcher_id'
		", DB_NAME, $table_timers ) );
		
		if ( empty( $column_exists ) ) {
			// Add the column
			$wpdb->query( "ALTER TABLE $table_timers ADD COLUMN dispatcher_id bigint(20) DEFAULT NULL COMMENT 'ID of dispatcher who owns the load' AFTER id_user" );
			
			// Add index for the new column
			$wpdb->query( "ALTER TABLE $table_timers ADD KEY idx_dispatcher_id (dispatcher_id)" );
			$wpdb->query( "ALTER TABLE $table_timers ADD KEY idx_dispatcher_project (dispatcher_id, project)" );
			
		}
	}
	
	/**
	 * Add dispatcher_id column to existing timer_logs table
	 */
	private function add_timer_logs_dispatcher_id_column() {
		global $wpdb;
		
		$table_timer_logs = $wpdb->prefix . $this->table_timer_logs;
		
		// Check if dispatcher_id column exists
		$column_exists = $wpdb->get_results( $wpdb->prepare( "
			SELECT COLUMN_NAME 
			FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s 
			AND TABLE_NAME = %s 
			AND COLUMN_NAME = 'dispatcher_id'
		", DB_NAME, $table_timer_logs ) );
		
		if ( empty( $column_exists ) ) {
			// Add the column
			$wpdb->query( "ALTER TABLE $table_timer_logs ADD COLUMN dispatcher_id bigint(20) DEFAULT NULL COMMENT 'ID of dispatcher who owns the load' AFTER id_user" );
			
			// Add index for the new column
			$wpdb->query( "ALTER TABLE $table_timer_logs ADD KEY idx_dispatcher_id (dispatcher_id)" );
			$wpdb->query( "ALTER TABLE $table_timer_logs ADD KEY idx_dispatcher_project (dispatcher_id, project)" );
			
		}
	}
	
	/**
	 * Update existing timers with dispatcher_id
	 */
	private function update_existing_timers_dispatcher_id() {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get all timers that don't have dispatcher_id set
		$timers_without_dispatcher = $wpdb->get_results( "
			SELECT id, id_load, project, flt 
			FROM $table_timers 
			WHERE dispatcher_id IS NULL
		" );
		
		$updated_count = 0;
		
		foreach ( $timers_without_dispatcher as $timer ) {
			// Use the same logic as get_dispatcher_id_from_load
			$dispatcher_id = $this->get_dispatcher_id_from_load( $timer->id_load, $timer->project, $timer->flt );
			
			if ( $dispatcher_id ) {
				$wpdb->update(
					$table_timers,
					array( 'dispatcher_id' => intval( $dispatcher_id ) ),
					array( 'id' => $timer->id ),
					array( '%d' ),
					array( '%d' )
				);
				$updated_count++;
			}
		}
		
		if ( $updated_count > 0 ) {
			error_log( "TMS Timer: Updated $updated_count existing timers with dispatcher_id" );
		}
	}
	
	/**
	 * Get dispatcher ID from load data
	 * 
	 * @param int $id_load Load ID
	 * @param string $project Project name
	 * @param bool $flt Is FLT load
	 * @return int|null Dispatcher ID or null if not found
	 */
	private function get_dispatcher_id_from_load( $id_load, $project = '', $flt = false ) {
		global $wpdb;
		
		$dispatcher_id = null;
		
		// Determine the correct meta table name based on project and flt
		if ( $flt ) {
			// FLT meta tables: wp_reportsmeta_flt_endurance, wp_reportsmeta_flt_martlet, wp_reportsmeta_flt_odysseia
			if ( ! empty( $project ) && $project !== 'flt' ) {
				$meta_table_name = $wpdb->prefix . 'reportsmeta_flt_' . strtolower( $project );
			} else {
				// Fallback to general FLT meta table if no specific project
				$meta_table_name = $wpdb->prefix . 'reportsmeta_flt';
			}
		} else {
			// Regular meta tables: wp_reportsmeta_endurance, wp_reportsmeta_martlet, wp_reportsmeta_odysseia
			if ( ! empty( $project ) ) {
				$meta_table_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $project );
			} else {
				// Fallback to general reports meta table if no specific project
				$meta_table_name = $wpdb->prefix . 'reportsmeta';
			}
		}
		
		// Check if meta table exists before querying
		$table_exists = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) 
			FROM information_schema.tables 
			WHERE table_schema = %s 
			AND table_name = %s
		", DB_NAME, $meta_table_name ) );
		
		if ( $table_exists ) {
			$dispatcher_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT meta_value FROM $meta_table_name 
				WHERE post_id = %d AND meta_key = 'dispatcher_initials'
			", $id_load ) );
			
		} else {
			// Only log once per unique table name to avoid spam
			static $logged_tables = array();
			if ( ! isset( $logged_tables[ $meta_table_name ] ) ) {
				$logged_tables[ $meta_table_name ] = true;
			}
		}
		
		return $dispatcher_id ? intval( $dispatcher_id ) : null;
	}
	
	/**
	 * Start a new timer for a load
	 * 
	 * @param int $id_load Load ID
	 * @param string $project Project name
	 * @param bool $flt Is FLT load
	 * @return int|false Timer ID on success, false on failure
	 */
	public function start_timer( $id_load, $project = '', $flt = false ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		// Check if there's already an active timer for this load and user
		$existing_timer = $this->get_active_timer_for_load( $id_load );
		if ( $existing_timer ) {
			// Stop the existing timer before starting a new one
			$this->stop_timer( $id_load, 'Replaced by new timer' );
		}
		
		// Also check for paused timers and stop them
		$paused_timer = $this->get_paused_timer_for_load( $id_load );
		if ( $paused_timer ) {
			$this->stop_timer( $id_load, 'Replaced by new timer' );
		}
		
		// Get dispatcher_id from the load
		$dispatcher_id = $this->get_dispatcher_id_from_load( $id_load, $project, $flt );
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$result = $wpdb->insert(
			$table_timers,
			array(
				'id_load' => $id_load,
				'id_user' => $user_id,
				'dispatcher_id' => $dispatcher_id,
				'create_timer' => $this->get_new_york_time(),
				'project' => $project,
				'flt' => $flt ? 1 : 0,
				'status' => 'active'
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%s' )
		);
		
		if ( $result ) {
			$timer_id = $wpdb->insert_id;
			
			// Log the action
			$this->timer_logs->log_timer_action( $id_load, 'start', 'Timer started', $project, $flt );
			
			// Update analytics
			$this->update_analytics( $id_load, 'start', $project, $flt );
			
			return $timer_id;
		}
		
		return false;
	}
	
	/**
	 * Stop a timer
	 * 
	 * @param int $id_load Load ID
	 * @param string $comment Optional comment
	 * @return bool Success status
	 */
	public function stop_timer( $id_load, $comment = '' ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get current timer (active or paused) - any user
		$timer = $this->get_active_timer_for_load( $id_load );
		if ( ! $timer ) {
			$timer = $this->get_paused_timer_for_load( $id_load );
		}
		
		if ( ! $timer ) {
			return false;
		}
		
		$stop_time = $this->get_new_york_time();
		
		// Calculate total duration in minutes
		$start_time = new DateTime( $timer['create_timer'] );
		$end_time = new DateTime( $stop_time );
		$total_seconds = $end_time->getTimestamp() - $start_time->getTimestamp();
		$total_minutes = floor( $total_seconds / 60 );
		
		// Calculate pause duration if timer was paused
		$pause_duration = $timer['pause_duration'] ?: 0;
		if ( $timer['status'] === 'paused' && $this->pause_start_time ) {
			$pause_start = new DateTime( $this->pause_start_time );
			$pause_end = new DateTime( $stop_time );
			$current_pause = floor( ( $pause_end->getTimestamp() - $pause_start->getTimestamp() ) / 60 );
			$pause_duration += $current_pause;
		}
		
		// Calculate actual work duration (total - pause)
		$work_duration = $total_minutes - $pause_duration;
		
		// Update timer
		$result = $wpdb->update(
			$table_timers,
			array(
				'stop_time' => $stop_time,
				'duration' => $work_duration,
				'pause_duration' => $pause_duration,
				'status' => 'stopped'
			),
			array(
				'id' => $timer['id']
			),
			array( '%s', '%d', '%d', '%s' ),
			array( '%d' )
		);
		
		if ( $result !== false ) {
			// Reset pause tracking
			$this->pause_start_time = null;
			$this->current_pause_duration = 0;
			
			// Log the action
			$this->timer_logs->log_timer_action( $id_load, 'stop', $comment ?: 'Timer stopped', $timer['project'], $timer['flt'] );
			
			// Update analytics
			$this->update_analytics( $id_load, 'stop', $timer['project'], $timer['flt'] );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get active timer for a load and user
	 * 
	 * @param int $id_load Load ID
	 * @param int $user_id User ID
	 * @return array|false Timer data or false
	 */
	public function get_active_timer( $id_load, $user_id = null ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$timer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_timers 
				WHERE id_load = %d AND id_user = %d AND status = 'active' 
				ORDER BY create_timer DESC LIMIT 1",
				$id_load,
				$user_id
			),
			ARRAY_A
		);
		
		return $timer ?: false;
	}
	
	/**
	 * Get active timer for a load (any user)
	 * 
	 * @param int $id_load Load ID
	 * @return array|false Timer data or false if not found
	 */
	public function get_active_timer_for_load( $id_load ) {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$timer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_timers 
				WHERE id_load = %d AND status = 'active' 
				ORDER BY create_timer DESC LIMIT 1",
				$id_load
			),
			ARRAY_A
		);
		
		return $timer ?: false;
	}
	
	
	/**
	 * Pause a timer
	 * 
	 * @param int $id_load Load ID
	 * @param string $comment Optional comment
	 * @return bool Success status
	 */
	public function pause_timer( $id_load, $comment = '' ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get current timer (any user)
		$timer = $this->get_active_timer_for_load( $id_load );
		if ( ! $timer ) {
			return false;
		}
		
		// Record pause start time
		$this->pause_start_time = $this->get_new_york_time();
		
		// Update timer status
		$result = $wpdb->update(
			$table_timers,
			array( 'status' => 'paused' ),
			array( 'id' => $timer['id'] ),
			array( '%s' ),
			array( '%d' )
		);
		
		if ( $result !== false ) {
			// Log the action
			$this->timer_logs->log_timer_action( $id_load, 'pause', $comment ?: 'Timer paused', $timer['project'], $timer['flt'] );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Resume a paused timer
	 * 
	 * @param int $id_load Load ID
	 * @param string $comment Optional comment
	 * @return bool Success status
	 */
	public function resume_timer( $id_load, $comment = '' ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get paused timer (any user)
		$timer = $this->get_paused_timer_for_load( $id_load );
		if ( ! $timer ) {
			return false;
		}
		
		// Calculate pause duration and add to total
		$current_pause_duration = 0;
		if ( $this->pause_start_time ) {
			$pause_start = new DateTime( $this->pause_start_time );
			$pause_end = new DateTime( $this->get_new_york_time() );
			$current_pause_duration = floor( ( $pause_end->getTimestamp() - $pause_start->getTimestamp() ) / 60 );
		}
		
		$total_pause_duration = ( $timer['pause_duration'] ?: 0 ) + $current_pause_duration;
		
		// Update timer status and pause duration
		$result = $wpdb->update(
			$table_timers,
			array( 
				'status' => 'active',
				'pause_duration' => $total_pause_duration
			),
			array( 'id' => $timer['id'] ),
			array( '%s', '%d' ),
			array( '%d' )
		);
		
		if ( $result !== false ) {
			// Reset pause tracking
			$this->pause_start_time = null;
			$this->current_pause_duration = 0;
			
			// Log the action
			$this->timer_logs->log_timer_action( $id_load, 'resume', $comment ?: 'Timer resumed', $timer['project'], $timer['flt'] );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Update/restart timer for load refresh
	 * When updating someone else's timer, ownership transfers to the current user
	 *
	 * @param int $id_load Load ID
	 * @param string $comment Comment for the update
	 * @param string $project Project name
	 * @param bool $flt Is FLT
	 * @return bool Success status
	 */
	public function update_timer( $id_load, $comment = '', $project = '', $flt = false ) {
		
		$user_id = get_current_user_id();
		
		// Get previous timer owner before stopping
		$previous_owner_id = null;
		$existing_timer = $this->get_active_timer_for_load( $id_load );
		if ( $existing_timer ) {
			$previous_owner_id = $existing_timer['id_user'];
			$this->stop_timer_silent( $id_load );
		} else {
			$paused_timer = $this->get_paused_timer_for_load( $id_load );
			if ( $paused_timer ) {
				$previous_owner_id = $paused_timer['id_user'];
				$this->stop_timer_silent( $id_load );
			}
		}
		
		// Start a new timer after stopping the old one - without logging
		$new_timer_id = $this->start_timer_silent( $id_load, $project, $flt );
		
		if ( $new_timer_id ) {
			// Determine if ownership was transferred
			$ownership_transferred = ( $previous_owner_id && $previous_owner_id != $user_id );
			
			// Create appropriate log message
			$log_message = $comment ?: 'Load updated - timer restarted';
			if ( $ownership_transferred ) {
				$log_message .= ' and ownership transferred';
			}
			
			// Log the update action
			$this->timer_logs->log_timer_action( $id_load, 'update', $log_message, $project, $flt );
			
			// Update analytics (increment updates counter)
			
			$this->update_analytics( $id_load, 'update', $project, $flt );
			
			// Invalidate analytics cache since ownership might have changed
			$this->invalidate_analytics_cache( $user_id );
			
			return $new_timer_id;
		}
		
		return false;
	}

	/**
	 * Stop timer silently (without logging) - used internally for update operations
	 * 
	 * @param int $id_load Load ID
	 * @return bool Success status
	 */
	private function stop_timer_silent( $id_load ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get current timer (active or paused) - any user
		$timer = $this->get_active_timer_for_load( $id_load );
		if ( ! $timer ) {
			$timer = $this->get_paused_timer_for_load( $id_load );
		}
		
		if ( ! $timer ) {
			return false;
		}
		
		$stop_time = $this->get_new_york_time();
		
		// Calculate total duration in minutes
		$start_time = new DateTime( $timer['create_timer'] );
		$end_time = new DateTime( $stop_time );
		$total_seconds = $end_time->getTimestamp() - $start_time->getTimestamp();
		$total_minutes = floor( $total_seconds / 60 );
		
		// Calculate pause duration if timer was paused
		$pause_duration = $timer['pause_duration'] ?: 0;
		if ( $timer['status'] === 'paused' && $this->pause_start_time ) {
			$pause_start = new DateTime( $this->pause_start_time );
			$pause_end = new DateTime( $stop_time );
			$pause_seconds = $pause_end->getTimestamp() - $pause_start->getTimestamp();
			$pause_duration += floor( $pause_seconds / 60 );
		}
		
		// Update timer record
		$result = $wpdb->update(
			$table_timers,
			array(
				'status' => 'stopped',
				'stop_time' => $stop_time,
				'duration' => $total_minutes,
				'pause_duration' => $pause_duration
			),
			array( 'id' => $timer['id'] ),
			array( '%s', '%s', '%d', '%d' ),
			array( '%d' )
		);
		
		// Reset pause start time
		$this->pause_start_time = null;
		
		return $result !== false;
	}

	/**
	 * Start timer silently (without logging) - used internally for update operations
	 * When updating someone else's timer, the ownership transfers to the current user
	 * 
	 * @param int $id_load Load ID
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @return int|false Timer ID or false on failure
	 */
	private function start_timer_silent( $id_load, $project = '', $flt = false ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		// Get dispatcher_id from the load
		$dispatcher_id = $this->get_dispatcher_id_from_load( $id_load, $project, $flt );
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$result = $wpdb->insert(
			$table_timers,
			array(
				'id_load' => $id_load,
				'id_user' => $user_id, // Ownership transfers to current user
				'dispatcher_id' => $dispatcher_id,
				'create_timer' => $this->get_new_york_time(),
				'project' => $project,
				'flt' => $flt ? 1 : 0,
				'status' => 'active'
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%s' )
		);
		
		if ( $result ) {
			$timer_id = $wpdb->insert_id;
			
			// Note: Analytics are updated by the calling function (update_timer)
			// to avoid double counting when updating timers
			
			return $timer_id;
		}
		
		return false;
	}

	/**
	 * Recalculate all zones for a user based on their stopped timers
	 *
	 * @param int $user_id User ID
	 * @param string $project Project name
	 * @param bool $flt Is FLT load
	 * @return array Zone data (yellow_zone_loads, red_zone_loads, black_zone_loads)
	 */
	private function recalculate_user_zones( $user_id, $project = '', $flt = false ) {
		global $wpdb;
		
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get all timers (active, paused, and stopped) for the user today
		// Note: We search by current date, not by create_timer date, to include manually modified timers
		$today = date( 'Y-m-d' );
		$timers = $wpdb->get_results( $wpdb->prepare( "
			SELECT id_load, duration, status, create_timer, stop_time, pause_duration
			FROM $table_timers 
			WHERE id_user = %d 
			AND (DATE(create_timer) = %s OR DATE(updated_at) = %s)
			AND project = %s 
			AND flt = %d
		", $user_id, $today, $today, $project, $flt ? 1 : 0 ), ARRAY_A );
		
		$yellow_zone_loads = array();
		$red_zone_loads = array();
		$black_zone_loads = array();
		
		foreach ( $timers as $timer ) {
			$id_load = $timer['id_load'];
			$duration_hours = 0;
			
			// Calculate duration based on timer status
			if ( $timer['status'] === 'stopped' && $timer['duration'] > 0 ) {
				// For stopped timers, use the stored duration
				$duration_hours = $timer['duration'] / 60;
			} elseif ( $timer['status'] === 'active' || $timer['status'] === 'paused' ) {
				// For active/paused timers, calculate current duration
				$start_time = new DateTime( $timer['create_timer'] );
				$current_time = new DateTime( $this->get_new_york_time() );
				$elapsed_seconds = $current_time->getTimestamp() - $start_time->getTimestamp();
				$elapsed_minutes = floor( $elapsed_seconds / 60 );
				
				// Subtract pause time if timer was paused
				$pause_duration = $timer['pause_duration'] ?: 0;
				$active_minutes = $elapsed_minutes - $pause_duration;
				
				$duration_hours = max( 0, $active_minutes ) / 60;
			}
			
			// Only include timers with meaningful duration
			if ( $duration_hours > 0 ) {
				if ( $duration_hours >= 1 && $duration_hours < 2 ) {
					$yellow_zone_loads[] = $id_load;
				} elseif ( $duration_hours >= 2 && $duration_hours < 4 ) {
					$red_zone_loads[] = $id_load;
				} elseif ( $duration_hours >= 4 ) {
					$black_zone_loads[] = $id_load;
				}
			}
		}
		
		// Remove duplicates
		$yellow_zone_loads = array_unique( $yellow_zone_loads );
		$red_zone_loads = array_unique( $red_zone_loads );
		$black_zone_loads = array_unique( $black_zone_loads );
		
		// Debug: Log each timer's details
		foreach ( $timers as $timer ) {
			$duration_hours = 0;
			if ( $timer['status'] === 'stopped' && $timer['duration'] > 0 ) {
				$duration_hours = $timer['duration'] / 60;
			} elseif ( $timer['status'] === 'active' || $timer['status'] === 'paused' ) {
				$start_time = new DateTime( $timer['create_timer'] );
				$current_time = new DateTime( $this->get_new_york_time() );
				$elapsed_seconds = $current_time->getTimestamp() - $start_time->getTimestamp();
				$elapsed_minutes = floor( $elapsed_seconds / 60 );
				$pause_duration = $timer['pause_duration'] ?: 0;
				$active_minutes = $elapsed_minutes - $pause_duration;
				$duration_hours = max( 0, $active_minutes ) / 60;
			}
			
			error_log( '  - Timer ' . $timer['id_load'] . ': status=' . $timer['status'] . ', duration_hours=' . round( $duration_hours, 2 ) );
		}
		
		// Update analytics records in database with new zone data
		$table_analytics = $wpdb->prefix . 'timer_analytics';
		$today = date( 'Y-m-d' );
		
		// Find existing analytics records for this user/date/project/flt
		$existing_records = $wpdb->get_results( $wpdb->prepare( "
			SELECT id FROM $table_analytics 
			WHERE id_user = %d AND date_analytics = %s AND project = %s AND flt = %d
		", $user_id, $today, $project, $flt ? 1 : 0 ) );
		
		
		// Update each record with new zone data
		foreach ( $existing_records as $record ) {
			$update_result = $wpdb->update(
				$table_analytics,
				array(
					'yellow_zone_loads' => json_encode( $yellow_zone_loads ),
					'red_zone_loads' => json_encode( $red_zone_loads ),
					'black_zone_loads' => json_encode( $black_zone_loads )
				),
				array( 'id' => $record->id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			
		}
		
		return array(
			'yellow_zone_loads' => $yellow_zone_loads,
			'red_zone_loads' => $red_zone_loads,
			'black_zone_loads' => $black_zone_loads
		);
	}

	/**
	 * Update analytics data for a load
	 *
	 * @param int $id_load Load ID
	 * @param string $action Timer action (start, stop, update, pause, resume)
	 * @param string $project Project name
	 * @param bool $flt Is FLT load
	 * @return void
	 */
	public function update_analytics( $id_load, $action, $project = '', $flt = false ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		
		// If project is empty, try to get current user's project
		if ( empty( $project ) ) {
			$project = $this->get_current_user_project();
		}
		
		$table_analytics = $wpdb->prefix . 'timer_analytics';
		$date_analytics = date( 'Y-m-d' );
		$shift_type = $this->get_shift_type();
		
		
		// Check if analytics record exists for today (one record per user/date/project/flt)
		$existing = $wpdb->get_row( $wpdb->prepare( "
			SELECT * FROM $table_analytics 
			WHERE id_user = %d AND date_analytics = %s AND project = %s AND flt = %d
		", $user_id, $date_analytics, $project, $flt ? 1 : 0 ) );
		
		
		if ( $existing ) {
			// Update existing record
			$update_data = array();
			
			
			if ( $action === 'start' ) {
				$update_data['total_timers_started'] = $existing->total_timers_started + 1;
			} elseif ( $action === 'update' ) {
				$update_data['total_updates'] = $existing->total_updates + 1;
			} elseif ( $action === 'stop' ) {
				
				// Get timer duration from the stopped timer
				$timer = $this->get_stopped_timer( $id_load, $user_id );
				
				if ( $timer && $timer['duration'] > 0 ) {
					// Determine zone based on duration
					$duration_hours = $timer['duration'] / 60;
					$zone_field = $this->get_zone_field( $duration_hours );
					
					if ( $zone_field ) {
						// Get existing loads in this zone
						$current_loads = json_decode( $existing->$zone_field, true ) ?: array();
						
						// Add new load if not already present (avoid duplicates)
						if ( ! in_array( $id_load, $current_loads ) ) {
							$current_loads[] = $id_load;
							$update_data[ $zone_field ] = json_encode( $current_loads );
						} else {
						}
					}
				} else {
					error_log( 'Timer Analytics Debug - STOP action: no timer found or duration is 0' );
				}
			}
			
			if ( ! empty( $update_data ) ) {
				
				$result = $wpdb->update(
					$table_analytics,
					$update_data,
					array( 'id' => $existing->id ),
					array_fill( 0, count( $update_data ), '%s' ),
					array( '%d' )
				);
				
				if ( $result === false ) {
					error_log( 'Timer Analytics Debug - Update error: ' . $wpdb->last_error );
				}
			} else {
				error_log( 'Timer Analytics Debug - No update data to save' );
			}
		} else {
			// Create new record with empty zones initially
			$insert_data = array(
				'id_user' => $user_id,
				'date_analytics' => $date_analytics,
				'project' => $project,
				'flt' => $flt ? 1 : 0,
				'shift_type' => $shift_type,
				'total_timers_started' => ( $action === 'start' ) ? 1 : 0,
				'total_updates' => ( $action === 'update' ) ? 1 : 0,
				'yellow_zone_loads' => json_encode( array() ),
				'red_zone_loads' => json_encode( array() ),
				'black_zone_loads' => json_encode( array() )
			);
			
			// If it's a stop action, add the load to appropriate zone
			if ( $action === 'stop' ) {
				$timer = $this->get_stopped_timer( $id_load, $user_id );
				if ( $timer && $timer['duration'] > 0 ) {
					$duration_hours = $timer['duration'] / 60;
					$zone_field = $this->get_zone_field( $duration_hours );
					if ( $zone_field ) {
						$insert_data[ $zone_field ] = json_encode( array( $id_load ) );
					}
				}
			}
			
			
			$result = $wpdb->insert( $table_analytics, $insert_data );
			
			if ( $result === false ) {
				error_log( 'Timer Analytics Debug - Insert error: ' . $wpdb->last_error );
			}
		}
		
		// Recalculate analytics to update zones for all user timers
		$this->recalculate_analytics( $id_load, $user_id, $date_analytics, $project, $flt );
		
	}

	/**
	 * Get shift type based on user roles
	 *
	 * @return string
	 */
	private function get_shift_type() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return 'day'; // Default fallback
		}
		
		// Check user roles to determine shift
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return 'day';
		}
		
		$user_roles = $user->roles;
		
		// Check for specific shift roles first
		if ( in_array( 'morning_tracking', $user_roles ) ) {
			return 'morning';
		} elseif ( in_array( 'nightshift_tracking', $user_roles ) ) {
			return 'night';
		} elseif ( in_array( 'tracking', $user_roles ) || in_array( 'tracking-tl', $user_roles ) ) {
			return 'day';
		}
		
		// Default fallback
		return 'day';
	}

	/**
	 * Get zone field name based on duration (simplified - no green zone)
	 *
	 * @param float $duration_hours
	 * @return string
	 */
	private function get_zone_field( $duration_hours ) {
		if ( $duration_hours <= 2 ) {
			return 'yellow_zone_loads';
		} elseif ( $duration_hours <= 4 ) {
			return 'red_zone_loads';
		} else {
			return 'black_zone_loads';
		}
	}

	/**
	 * Get current user's project
	 *
	 * @return string
	 */
	private function get_current_user_project() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return '';
		}
		
		// Try to get project from ACF field
		$project = get_field( 'current_select', 'user_' . $user_id );
		if ( ! empty( $project ) ) {
			return $project;
		}
		
		// Fallback to default project
		return 'Odysseia';
	}


	/**
	 * Get stopped timer for analytics
	 *
	 * @param int $id_load Load ID
	 * @param int $user_id User ID
	 * @return array|false Timer data or false
	 */
	private function get_stopped_timer( $id_load, $user_id ) {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		
		// Get the most recent stopped timer for this load and user
		$timer = $wpdb->get_row( $wpdb->prepare( "
			SELECT * FROM $table_timers 
			WHERE id_load = %d AND id_user = %d AND status = 'stopped' AND duration IS NOT NULL
			ORDER BY stop_time DESC 
			LIMIT 1
		", $id_load, $user_id ), ARRAY_A );
		
		
		return $timer;
	}


	/**
	 * Recalculate analytics based on actual timer data
	 *
	 * @param int $id_load Load ID
	 * @param int $user_id User ID
	 * @param string $date_analytics Date
	 * @param string $project Project name
	 * @param bool $flt Is FLT load
	 * @return void
	 */
	private function recalculate_analytics( $id_load, $user_id, $date_analytics, $project, $flt ) {
		global $wpdb;
		
		$table_analytics = $wpdb->prefix . 'timer_analytics';
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		// Get all stopped timers for this load/user/date/project/flt
		$timers = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM $table_timers 
			WHERE id_load = %d AND id_user = %d AND project = %s AND flt = %d 
			AND status = 'stopped' AND duration IS NOT NULL AND duration > 0
			AND DATE(create_timer) = %s
		", $id_load, $user_id, $project, $flt, $date_analytics ) );
		
		// Calculate totals
		$total_duration = 0;
		$green_zone_count = 0;
		$yellow_zone_count = 0;
		$red_zone_count = 0;
		$black_zone_count = 0;
		$timer_used_count = count( $timers );
		
		foreach ( $timers as $timer ) {
			$total_duration += $timer->duration;
			$duration_hours = $timer->duration / 60;
			
			if ( $duration_hours < 1 ) {
				$green_zone_count++;
			} elseif ( $duration_hours < 2 ) {
				$yellow_zone_count++;
			} elseif ( $duration_hours < 4 ) {
				$red_zone_count++;
			} else {
				$black_zone_count++;
			}
		}
		
		$avg_duration = $timer_used_count > 0 ? $total_duration / $timer_used_count : 0;
		
		// Update analytics record
		$wpdb->update(
			$table_analytics,
			array(
				'timer_used_count' => $timer_used_count,
				'total_duration' => $total_duration,
				'avg_duration' => $avg_duration,
				'green_zone_count' => $green_zone_count,
				'yellow_zone_count' => $yellow_zone_count,
				'red_zone_count' => $red_zone_count,
				'black_zone_count' => $black_zone_count
			),
			array(
				'id_user' => $user_id,
				'date_analytics' => $date_analytics,
				'project' => $project,
				'flt' => $flt
			),
			array( '%d', '%d', '%f', '%d', '%d', '%d', '%d' ),
			array( '%d', '%s', '%s', '%d' )
		);
		
		// After updating individual load analytics, recalculate zones for all user timers
		// This ensures zones reflect all timers, not just the current one
		$this->recalculate_user_zones( $user_id, $project, $flt );
	}
	
	/**
	 * Get paused timer for a load and user
	 * 
	 * @param int $id_load Load ID
	 * @param int $user_id User ID
	 * @return array|false Timer data or false
	 */
	public function get_paused_timer( $id_load, $user_id = null ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$timer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_timers 
				WHERE id_load = %d AND id_user = %d AND status = 'paused' 
				ORDER BY create_timer DESC LIMIT 1",
				$id_load,
				$user_id
			),
			ARRAY_A
		);
		
		return $timer ?: false;
	}
	
	/**
	 * Get paused timer for a load (any user)
	 * 
	 * @param int $id_load Load ID
	 * @return array|false Timer data or false if not found
	 */
	public function get_paused_timer_for_load( $id_load ) {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$timer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_timers 
				WHERE id_load = %d AND status = 'paused' 
				ORDER BY create_timer DESC LIMIT 1",
				$id_load
			),
			ARRAY_A
		);
		
		return $timer ?: false;
	}
	
	/**
	 * Get all timers for a load
	 * 
	 * @param int $id_load Load ID
	 * @param int $user_id User ID (optional)
	 * @return array Array of timers
	 */
	public function get_load_timers( $id_load, $user_id = null ) {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$where_clause = "WHERE id_load = %d";
		$params = array( $id_load );
		
		if ( $user_id ) {
			$where_clause .= " AND id_user = %d";
			$params[] = $user_id;
		}
		
		$timers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_timers $where_clause ORDER BY create_timer DESC",
				$params
			),
			ARRAY_A
		);
		
		return $timers ?: array();
	}
	
	/**
	 * Get timer logs for a load
	 * 
	 * @param int $id_load Load ID
	 * @param int $user_id User ID (optional)
	 * @param int $limit Limit results (optional)
	 * @return array Array of logs
	 */
	public function get_timer_logs( $id_load, $user_id = null, $limit = null ) {
		return $this->timer_logs->get_timer_logs( $id_load, $user_id, $limit );
	}

	/**
	 * Get timer logs with filters for analytics
	 * 
	 * @param array $filters Filters array
	 * @return array Timer logs with user info
	 */
	public function get_timer_logs_analytics( $filters = array() ) {
		global $wpdb;
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		$table_users = $wpdb->prefix . 'users';
		
		// Default filters
		$defaults = array(
			'period' => 'day',
			'date' => date( 'Y-m-d' ),
			'user_id' => '',
			'project' => '',
			'flt' => '',
			'action' => '',
			'limit' => 20,
			'offset' => 0
		);
		
		$filters = wp_parse_args( $filters, $defaults );
		
		// Build date range
		$date_range = $this->get_date_range( $filters['period'], $filters['date'] );
		
		// Build WHERE conditions
		$where_conditions = array();
		$where_values = array();
		
		// Date range
		$where_conditions[] = "DATE(logs.created_at) BETWEEN %s AND %s";
		$where_values[] = $date_range['start'];
		$where_values[] = $date_range['end'];
		
		// User filter
		if ( ! empty( $filters['user_id'] ) ) {
			$where_conditions[] = "logs.id_user = %d";
			$where_values[] = intval( $filters['user_id'] );
		}
		
		// Project filter
		if ( ! empty( $filters['project'] ) ) {
			$where_conditions[] = "logs.project = %s";
			$where_values[] = $filters['project'];
		}
		
		// FLT filter
		if ( $filters['flt'] !== '' ) {
			$where_conditions[] = "logs.flt = %d";
			$where_values[] = intval( $filters['flt'] );
		}
		
		// Action filter
		if ( ! empty( $filters['action'] ) ) {
			$where_conditions[] = "logs.action = %s";
			$where_values[] = $filters['action'];
		}
		
		$where_clause = implode( ' AND ', $where_conditions );
		
		// Get total count first
		$count_query = $wpdb->prepare( "
			SELECT COUNT(*)
			FROM $table_logs as logs
			LEFT JOIN $table_users as users ON logs.id_user = users.ID
			WHERE $where_clause
		", $where_values );
		
		$total_logs = $wpdb->get_var( $count_query );
		
		// Build main query with pagination
		$query = $wpdb->prepare( "
			SELECT 
				logs.*,
				users.display_name as user_name,
				users.user_email as user_email
			FROM $table_logs as logs
			LEFT JOIN $table_users as users ON logs.id_user = users.ID
			WHERE $where_clause
			ORDER BY logs.created_at DESC
			LIMIT %d OFFSET %d
		", array_merge( $where_values, array( intval( $filters['limit'] ), intval( $filters['offset'] ) ) ) );
		
		
		$logs = $wpdb->get_results( $query, ARRAY_A );
		
		// Format logs for display
		$formatted_logs = array();
		foreach ( $logs as $log ) {
			$formatted_logs[] = array(
				'id' => $log['id'],
				'load_id' => $log['id_load'],
				'user_id' => $log['id_user'],
				'user_name' => $log['user_name'] ?: 'Unknown User',
				'user_email' => $log['user_email'] ?: '',
				'action' => $log['action'],
				'comment' => $log['comment'] ?: '',
				'project' => $log['project'],
				'flt' => intval( $log['flt'] ), // Ensure it's an integer
				'dispatcher_id' => $log['dispatcher_id'],
				'created_at' => $log['created_at'],
				'formatted_time' => $this->format_datetime_for_display( $log['created_at'] )
			);
		}
		
		return array(
			'logs' => $formatted_logs,
			'total_logs' => $total_logs
		);
	}

	/**
	 * Get timer status indicator for display in table
	 *
	 * @param int $id_load Load ID
	 * @return string HTML status indicator
	 */
	public function get_timer_status( $id_load ) {
		$active_timer = $this->get_active_timer_for_load( $id_load );
		$paused_timer = $this->get_paused_timer_for_load( $id_load );
		
		// Check if timer is paused
		if ( $paused_timer ) {
			$start_time = new DateTime( $paused_timer['create_timer'], new DateTimeZone( 'America/New_York' ) );
			$pause_time = new DateTime( $paused_timer['pause_start_time'], new DateTimeZone( 'America/New_York' ) );
			
			// Calculate total duration in minutes including days
			$diff = $pause_time->diff( $start_time );
			$duration_minutes = $diff->i + ( $diff->h * 60 ) + ( $diff->days * 24 * 60 );
			$duration_hours = $duration_minutes / 60;

			// Special color for paused timer - always use info (blue) to distinguish from active
			$color_class = 'bg-info'; // Blue for paused
			$status_text = 'Paused';

			$duration_display = $this->format_minutes_to_hours( $duration_minutes );
			
			return sprintf(
				'<div class="d-flex flex-column align-items-center mt-1">
					<span class="badge %s" title="Duration: %s (Paused)">%s</span>
					<small class="text-muted">%s</small>
				</div>',
				$color_class,
				$duration_display,
				$status_text,
				$duration_display
			);
		}
		
		// Check if timer is active
		if ( $active_timer ) {
			$start_time = new DateTime( $active_timer['create_timer'], new DateTimeZone( 'America/New_York' ) );
			$current_time = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
			
			// Calculate total duration in minutes including days
			$diff = $current_time->diff( $start_time );
			$duration_minutes = $diff->i + ( $diff->h * 60 ) + ( $diff->days * 24 * 60 );
			$duration_hours = $duration_minutes / 60;

			// Determine color based on duration
			$color_class = '';
			$status_text = 'Active';
			
			if ( $duration_hours < 1 ) {
				$color_class = 'bg-success'; // Green
			} elseif ( $duration_hours < 2 ) {
				$color_class = 'bg-warning'; // Yellow
			} elseif ( $duration_hours < 4 ) {
				$color_class = 'bg-danger'; // Red
			} else {
				$color_class = 'bg-dark'; // Black
			}

			$duration_display = $this->format_minutes_to_hours( $duration_minutes );
			
			return sprintf(
				'<div class="d-flex flex-column align-items-center mt-1">
					<span class="badge %s" title="Duration: %s">%s</span>
					<small class="text-muted" style="white-space: nowrap;">%s</small>
				</div>',
				$color_class,
				$duration_display,
				$status_text,
				$duration_display
			);
		}
		
		// No active or paused timer
		return '';
	}
	
	/**
	 * Get timer statistics for a user
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name (optional)
	 * @param bool $flt Is FLT (optional)
	 * @return array Statistics
	 */
	public function get_user_timer_stats( $user_id, $project = '', $flt = null ) {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$where_clause = "WHERE id_user = %d";
		$params = array( $user_id );
		
		if ( $project ) {
			$where_clause .= " AND project = %s";
			$params[] = $project;
		}
		
		if ( $flt !== null ) {
			$where_clause .= " AND flt = %d";
			$params[] = $flt ? 1 : 0;
		}
		
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total_timers,
					SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_timers,
					SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused_timers,
					SUM(CASE WHEN status = 'stopped' THEN 1 ELSE 0 END) as stopped_timers
				FROM $table_timers $where_clause",
				$params
			),
			ARRAY_A
		);
		
		return $stats ?: array(
			'total_timers' => 0,
			'active_timers' => 0,
			'paused_timers' => 0,
			'stopped_timers' => 0
		);
	}
	
	/**
	 * Get timer statistics with average duration
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name (optional)
	 * @param bool $flt Is FLT (optional)
	 * @param string $date_from Date from (optional)
	 * @param string $date_to Date to (optional)
	 * @return array Statistics with averages
	 */
	public function get_timer_stats_with_averages( $user_id, $project = '', $flt = null, $date_from = '', $date_to = '' ) {
		global $wpdb;
		
		$table_timers = $wpdb->prefix . $this->table_timers;
		
		$where_conditions = array( 'id_user = %d' );
		$params = array( $user_id );
		
		if ( $project ) {
			$where_conditions[] = 'project = %s';
			$params[] = $project;
		}
		
		if ( $flt !== null ) {
			$where_conditions[] = 'flt = %d';
			$params[] = $flt ? 1 : 0;
		}
		
		if ( $date_from ) {
			$where_conditions[] = 'create_timer >= %s';
			$params[] = $date_from;
		}
		
		if ( $date_to ) {
			$where_conditions[] = 'create_timer <= %s';
			$params[] = $date_to;
		}
		
		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total_timers,
					SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_timers,
					SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused_timers,
					SUM(CASE WHEN status = 'stopped' THEN 1 ELSE 0 END) as stopped_timers,
					AVG(CASE WHEN status = 'stopped' AND duration IS NOT NULL THEN duration ELSE NULL END) as avg_duration_minutes,
					AVG(CASE WHEN status = 'stopped' AND pause_duration IS NOT NULL THEN pause_duration ELSE NULL END) as avg_pause_minutes,
					SUM(CASE WHEN status = 'stopped' AND duration IS NOT NULL THEN duration ELSE 0 END) as total_work_minutes,
					SUM(CASE WHEN status = 'stopped' AND pause_duration IS NOT NULL THEN pause_duration ELSE 0 END) as total_pause_minutes
				FROM $table_timers $where_clause",
				$params
			),
			ARRAY_A
		);
		
		// Format averages
		$stats['avg_duration_minutes'] = $stats['avg_duration_minutes'] ? round( $stats['avg_duration_minutes'], 2 ) : 0;
		$stats['avg_pause_minutes'] = $stats['avg_pause_minutes'] ? round( $stats['avg_pause_minutes'], 2 ) : 0;
		
		// Convert to hours and minutes for display
		$stats['avg_duration_formatted'] = $this->format_minutes_to_hours( $stats['avg_duration_minutes'] );
		$stats['avg_pause_formatted'] = $this->format_minutes_to_hours( $stats['avg_pause_minutes'] );
		$stats['total_work_formatted'] = $this->format_minutes_to_hours( $stats['total_work_minutes'] );
		$stats['total_pause_formatted'] = $this->format_minutes_to_hours( $stats['total_pause_minutes'] );
		
		return $stats ?: array(
			'total_timers' => 0,
			'active_timers' => 0,
			'paused_timers' => 0,
			'stopped_timers' => 0,
			'avg_duration_minutes' => 0,
			'avg_pause_minutes' => 0,
			'total_work_minutes' => 0,
			'total_pause_minutes' => 0,
			'avg_duration_formatted' => '0h 0m',
			'avg_pause_formatted' => '0h 0m',
			'total_work_formatted' => '0h 0m',
			'total_pause_formatted' => '0h 0m'
		);
	}
	
	/**
	 * Format minutes to hours and minutes
	 * 
	 * @param int $minutes Minutes
	 * @return string Formatted string (e.g., "2h 30m")
	 */
	private function format_minutes_to_hours( $minutes ) {
		if ( ! $minutes || $minutes <= 0 ) {
			return '0h 0m';
		}
		
		$hours = floor( $minutes / 60 );
		$mins = $minutes % 60;
		
		return sprintf( '%dh %dm', $hours, $mins );
	}
	
	/**
	 * AJAX: Start timer
	 */
	public function ajax_start_timer() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		$comment = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
		$project = sanitize_text_field( $_POST['project'] ?? '' );
		$flt = filter_var( $_POST['flt'] ?? false, FILTER_VALIDATE_BOOLEAN );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		$result = $this->start_timer( $load_id, $project, $flt );
		
		if ( $result ) {
			wp_send_json_success( array( 
				'message' => 'Timer started successfully',
				'timer_id' => $result
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to start timer or timer already exists' ) );
		}
	}
	
	/**
	 * AJAX: Pause timer
	 */
	public function ajax_pause_timer() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		$comment = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		if ( ! $comment ) {
			wp_send_json_error( array( 'message' => 'Comment is required when pausing timer' ) );
		}
		
		$result = $this->pause_timer( $load_id, $comment );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Timer paused successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to pause timer' ) );
		}
	}
	
	/**
	 * AJAX: Resume timer
	 */
	public function ajax_resume_timer() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		$comment = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		$result = $this->resume_timer( $load_id, $comment );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Timer resumed successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to resume timer' ) );
		}
	}
	
	/**
	 * AJAX: Stop timer
	 */
	public function ajax_stop_timer() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		$comment = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		$result = $this->stop_timer( $load_id, $comment );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Timer stopped successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to stop timer' ) );
		}
	}
	
	/**
	 * AJAX: Get timer status
	 */
	public function ajax_get_timer_status() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		$active_timer = $this->get_active_timer_for_load( $load_id );
		$paused_timer = $this->get_paused_timer_for_load( $load_id );
		
		$timer = $active_timer ?: $paused_timer;
		
		// Render HTML status badge for table cell
		$status_html = $this->get_timer_status( $load_id );
		
		// Add debug info
		if ( $timer ) {
			$timer['debug'] = array(
				'create_timer_raw' => $timer['create_timer'],
				'current_ny_time' => $this->get_new_york_time(),
				'server_time' => current_time( 'mysql' )
			);
		}
		
		wp_send_json_success( array(
			'timer'       => $timer,
			'status_html' => $status_html,
		) );
	}
	
	/**
	 * AJAX: Get timer logs
	 */
	public function ajax_get_timer_logs() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		$logs = $this->get_timer_logs( $load_id, null, 10 ); // Last 10 logs
		
		wp_send_json_success( array( 'logs' => $logs ) );
	}

	/**
	 * AJAX action: Update timer
	 */
	public function ajax_update_timer() {
		$load_id = intval( $_POST['load_id'] ?? 0 );
		$comment = sanitize_text_field( $_POST['comment'] ?? '' );
		$project = sanitize_text_field( $_POST['project'] ?? '' );
		$flt = filter_var( $_POST['flt'] ?? false, FILTER_VALIDATE_BOOLEAN );
		
		if ( ! $load_id ) {
			wp_send_json_error( array( 'message' => 'Invalid load ID' ) );
		}
		
		$result = $this->update_timer( $load_id, $comment, $project, $flt );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Timer updated successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to update timer' ) );
		}
	}
	
	/**
	 * Get current time in New York timezone
	 * 
	 * @return string MySQL formatted datetime
	 */
	private function get_new_york_time() {
		$ny_timezone = new DateTimeZone( 'America/New_York' );
		$ny_time = new DateTime( 'now', $ny_timezone );
		return $ny_time->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Format datetime for display
	 * 
	 * @param string $datetime MySQL datetime string
	 * @return string Formatted datetime for display
	 */
	private function format_datetime_for_display( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}
		
		try {
			$date = new DateTime( $datetime );
			return $date->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return $datetime; // Return original if formatting fails
		}
	}
	
	/**
	 * Check if current context is FLT
	 */
	private function is_flt() {
		// This should be determined based on your project structure
		// You might need to pass this from the frontend or determine it another way
		return false; // Default to false, adjust as needed
	}

	/**
	 * Get timer analytics for a period
	 *
	 * @param string $period Period (day, week, month)
	 * @param string $date Date in Y-m-d format
	 * @param array $filters Filters (user_id, project, flt)
	 * @return array Analytics data
	 */
	public function get_timer_analytics( $period = 'day', $date = null, $filters = array() ) {
		global $wpdb;
		
		$table_analytics = $wpdb->prefix . 'timer_analytics';
		$date = $date ?: date( 'Y-m-d' );
		
		// Build date range based on period
		$date_range = $this->get_date_range( $period, $date );
		
		// Build WHERE clause
		$where_conditions = array( "date_analytics BETWEEN %s AND %s" );
		$where_values = array( $date_range['start'], $date_range['end'] );
		
		if ( ! empty( $filters['user_id'] ) ) {
			$where_conditions[] = "id_user = %d";
			$where_values[] = $filters['user_id'];
		}
		
		if ( ! empty( $filters['project'] ) ) {
			$where_conditions[] = "project = %s";
			$where_values[] = $filters['project'];
		}
		
		if ( isset( $filters['flt'] ) ) {
			$where_conditions[] = "flt = %d";
			$where_values[] = $filters['flt'] ? 1 : 0;
		}
		
		$where_clause = implode( ' AND ', $where_conditions );
		
		// Get analytics data (simplified)
		$analytics_query = $wpdb->prepare( "
			SELECT 
				COUNT(*) as total_analytics_records,
				SUM(total_timers_started) as total_timer_uses,
				SUM(total_updates) as total_updates,
				SUM(JSON_LENGTH(yellow_zone_loads)) as yellow_zone_count,
				SUM(JSON_LENGTH(red_zone_loads)) as red_zone_count,
				SUM(JSON_LENGTH(black_zone_loads)) as black_zone_count
			FROM $table_analytics 
			WHERE $where_clause
		", $where_values );
		
		$analytics = $wpdb->get_results( $analytics_query );
		$data = $analytics[0] ?? array();
		
		
		// Get total loads count (excluding final statuses)
		$total_loads = $this->get_total_loads_count( $period, $date, $filters );
		$data->total_loads = $total_loads;
		
		
		// Calculate loads without timer (simplified)
		$data->loads_without_timer = max(0, $total_loads - $data->total_timer_uses);
		
		// Calculate percentages based on total loads
		if ( $total_loads > 0 ) {
			$data->timer_usage_percentage = round( $data->total_timer_uses / $total_loads * 100, 2 );
		} else {
			$data->timer_usage_percentage = 0;
		}
		
		// Calculate zone percentages based on total timer uses (not zone loads)
		// This gives more accurate representation of timer distribution
		if ( $data->total_timer_uses > 0 ) {
			$data->yellow_zone_percentage = round( $data->yellow_zone_count / $data->total_timer_uses * 100, 2 );
			$data->red_zone_percentage = round( $data->red_zone_count / $data->total_timer_uses * 100, 2 );
			$data->black_zone_percentage = round( $data->black_zone_count / $data->total_timer_uses * 100, 2 );
			
			
		} else {
			$data->yellow_zone_percentage = 0;
			$data->red_zone_percentage = 0;
			$data->black_zone_percentage = 0;
		}
		
		// Force recalculate zones for current user to ensure active timers are included
		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$project = isset( $filters['project'] ) ? $filters['project'] : '';
			$flt = isset( $filters['flt'] ) ? $filters['flt'] : false;
			
			$this->recalculate_user_zones( $current_user_id, $project, $flt );
		}
		
		return $data;
	}

	/**
	 * Get total loads count (excluding final statuses)
	 *
	 * @param string $period Period (day, week, month)
	 * @param string $date Date in Y-m-d format
	 * @param array $filters Filters (user_id, project, flt)
	 * @return int Total loads count
	 */
	private function get_total_loads_count( $period = 'day', $date = null, $filters = array() ) {
		global $wpdb;
		
		$date = $date ?: date( 'Y-m-d' );
		
		// Build date range based on period
		$date_range = $this->get_date_range( $period, $date );
		
		// If no specific project is selected, get all projects
		if ( empty( $filters['project'] ) ) {
			return $this->get_total_loads_count_all_projects( $period, $date, $filters );
		}
		
		$project = $filters['project'];
		$project_lower = strtolower( $project );
		
		// Determine which table to use based on FLT filter
		$is_flt = isset( $filters['flt'] ) && $filters['flt'];
		
		if ( $is_flt ) {
			$table_main = $wpdb->prefix . 'reports_flt';
			$table_meta = $wpdb->prefix . 'reportsmeta_flt';
		} else {
			$table_main = $wpdb->prefix . 'reports_' . $project_lower;
			$table_meta = $wpdb->prefix . 'reportsmeta_' . $project_lower;
		}
		
		// Check if table exists
		if ( ! $this->table_exists( $table_main ) ) {
			return 0;
		}
		
		// Build WHERE clause
		$where_conditions = array();
		$where_values = array();
		
		// Date range - only for analytics, not for active loads count
		// For active loads, we want all loads regardless of creation date
		if ( ! empty( $filters['count_active_loads_only'] ) ) {
			// Skip date filter for active loads count
		} else {
			$where_conditions[] = "DATE(main.date_booked) BETWEEN %s AND %s";
			$where_values[] = $date_range['start'];
			$where_values[] = $date_range['end'];
		}
		
		// Exclude final statuses
		$exclude_status = array( 'delivered', 'tonu', 'cancelled', 'waiting-on-rc' );
		$where_conditions[] = "load_status.meta_value NOT IN ('" . implode( "','", $exclude_status ) . "')";
		
		// Only published posts
		$where_conditions[] = "main.status_post = 'publish'";
		
		// User filter (if specified)
		// Filter by user if specified
		// Note: dispatcher_initials field actually stores user IDs, not initials
		if ( ! empty( $filters['user_id'] ) ) {
			$where_conditions[] = "dispatcher.meta_value = %d";
			$where_values[] = $filters['user_id'];
		}
		
		$where_clause = implode( ' AND ', $where_conditions );
		
		// Get total count
		$count_query = $wpdb->prepare( "
			SELECT COUNT(DISTINCT main.id)
			FROM $table_main AS main
			LEFT JOIN $table_meta AS dispatcher ON main.id = dispatcher.post_id AND dispatcher.meta_key = 'dispatcher_initials'
			LEFT JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
			WHERE $where_clause
		", $where_values );
		
		
		$count = $wpdb->get_var( $count_query );
		
		
		return (int) $count;
	}

	/**
	 * Get total loads count for all projects
	 *
	 * @param string $period Period (day, week, month)
	 * @param string $date Date in Y-m-d format
	 * @param array $filters Filters (user_id, flt)
	 * @return int Total loads count
	 */
	private function get_total_loads_count_all_projects( $period = 'day', $date = null, $filters = array() ) {
		global $wpdb;
		
		$date = $date ?: date( 'Y-m-d' );
		$date_range = $this->get_date_range( $period, $date );
		
		// Get all available projects
		$helper = new TMSReportsHelper();
		$projects = array_keys( $helper->tms_tables_with_label );
		
		
		$total_count = 0;
		$is_flt = isset( $filters['flt'] ) && $filters['flt'];
		
		foreach ( $projects as $project ) {
			// Use the same logic as get_table_items_tracking
			$args = array(
				'status_post'    => 'publish',
				'exclude_status' => array( 'delivered', 'tonu', 'cancelled', 'waiting-on-rc' ),
			);
			
			// Add date filter
			$args['date_from'] = $date_range['start'];
			$args['date_to'] = $date_range['end'];
			
			// Add user filter if specified
			if ( ! empty( $filters['user_id'] ) ) {
				// Get user's team members from ACF field
				$my_team = get_field( 'my_team', 'user_' . $filters['user_id'] );
				if ( ! is_array( $my_team ) || empty( $my_team ) ) {
					$my_team = array( $filters['user_id'] );
				}
				$args['my_team'] = $my_team;
			}
			
			// Create appropriate reports class instance
			if ( $is_flt ) {
				$reports = new TMSReportsFlt();
			} else {
				$reports = new TMSReports();
				// Set the project for regular reports
				$reports->table_main = 'reports_' . strtolower( $project );
				$reports->table_meta = 'reportsmeta_' . strtolower( $project );
			}
			
			// Get loads using the same method as the tracking page
			$args = $reports->set_filter_params( $args );
			$items = $reports->get_table_items_tracking( $args );
			
			$count = 0;
			if ( isset( $items['total_posts'] ) ) {
				$count = intval( $items['total_posts'] );
			} elseif ( isset( $items['total_items'] ) ) {
				$count = intval( $items['total_items'] );
			}
			
			
			$total_count += $count;
		}
		
		
		return $total_count;
	}


	/**
	 * Check if table exists
	 *
	 * @param string $table_name Table name
	 * @return bool
	 */
	private function table_exists( $table_name ) {
		global $wpdb;
		
		$result = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) 
			FROM information_schema.tables 
			WHERE table_schema = %s 
			AND table_name = %s
		", DB_NAME, $table_name ) );
		
		return (int) $result > 0;
	}

	/**
	 * Get user initials by user ID
	 *
	 * @param int $user_id User ID
	 * @return string User initials
	 */
	private function get_user_initials( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return '';
		}
		
		// Try to get initials from ACF field
		$initials = get_field( 'initials', 'user_' . $user_id );
		if ( ! empty( $initials ) ) {
			return $initials;
		}
		
		// Fallback to first name + last name initials
		$first_name = $user->first_name;
		$last_name = $user->last_name;
		
		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			return strtoupper( substr( $first_name, 0, 1 ) . substr( $last_name, 0, 1 ) );
		}
		
		// Last fallback to display name
		return $user->display_name;
	}

	/**
	 * Get detailed analytics by user
	 *
	 * @param string $period Period (day, week, month)
	 * @param string $date Date in Y-m-d format
	 * @param array $filters Filters (project, flt)
	 * @return array User analytics data
	 */
	public function get_user_analytics( $period = 'day', $date = null, $filters = array() ) {
		global $wpdb;
		
		$table_analytics = $wpdb->prefix . 'timer_analytics';
		$date = $date ?: date( 'Y-m-d' );
		
		// Build date range based on period
		$date_range = $this->get_date_range( $period, $date );
		
		// Build WHERE clause
		$where_conditions = array( "date_analytics BETWEEN %s AND %s" );
		$where_values = array( $date_range['start'], $date_range['end'] );
		
		if ( ! empty( $filters['user_id'] ) ) {
			$where_conditions[] = "id_user = %d";
			$where_values[] = $filters['user_id'];
		}
		
		if ( ! empty( $filters['project'] ) ) {
			$where_conditions[] = "project = %s";
			$where_values[] = $filters['project'];
		}
		
		if ( isset( $filters['flt'] ) ) {
			$where_conditions[] = "flt = %d";
			$where_values[] = $filters['flt'] ? 1 : 0;
		}
		
		$where_clause = implode( ' AND ', $where_conditions );
		
		// Get user analytics data (simplified)
		$analytics = $wpdb->get_results( $wpdb->prepare( "
			SELECT 
				id_user,
				COUNT(*) as total_records,
				SUM(total_timers_started) as total_timer_uses,
				SUM(total_updates) as total_updates,
				SUM(JSON_LENGTH(yellow_zone_loads)) as yellow_zone_count,
				SUM(JSON_LENGTH(red_zone_loads)) as red_zone_count,
				SUM(JSON_LENGTH(black_zone_loads)) as black_zone_count
			FROM $table_analytics 
			WHERE $where_clause
			GROUP BY id_user
			ORDER BY total_timer_uses DESC
		", $where_values ) );
		
		return $analytics;
	}

	/**
	 * Get date range based on period
	 *
	 * @param string $period Period (day, week, month)
	 * @param string $date Date in Y-m-d format
	 * @return array Date range
	 */
	private function get_date_range( $period, $date ) {
		$start_date = new DateTime( $date );
		$end_date = new DateTime( $date );
		
		switch ( $period ) {
			case 'week':
				$start_date->modify( 'monday this week' );
				$end_date->modify( 'sunday this week' );
				break;
			case 'month':
				// For month, use the month of the selected date, not current month
				$start_date->modify( 'first day of this month' );
				$end_date->modify( 'last day of this month' );
				break;
			default: // day
				// Already set to the same date
				break;
		}
		
		$date_range = array(
			'start' => $start_date->format( 'Y-m-d' ),
			'end' => $end_date->format( 'Y-m-d' )
		);
		
		
		return $date_range;
	}

	/**
	 * Add user names to analytics data
	 *
	 * @param array $user_analytics
	 * @return array
	 */
	private function add_user_names_to_analytics( $user_analytics ) {
		if ( ! is_array( $user_analytics ) ) {
			return $user_analytics;
		}

		foreach ( $user_analytics as &$user_data ) {
			$user = get_userdata( $user_data->id_user );
			if ( $user ) {
				$user_data->user_name = $user->display_name;
				$user_data->user_login = $user->user_login;
			} else {
				$user_data->user_name = 'Unknown User';
				$user_data->user_login = 'unknown';
			}
		}

		return $user_analytics;
	}

	/**
	 * AJAX: Get timer analytics
	 */
	public function ajax_get_timer_analytics() {
		// Check user permissions (managers, tracking-tl, administrator)
		$TMSUser = new TMSUsers();
		if ( ! $TMSUser->check_user_role_access( array( 'managers', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking', 'administrator' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}
		
		$period = sanitize_text_field( $_POST['period'] ?? 'day' );
		$date = sanitize_text_field( $_POST['date'] ?? date( 'Y-m-d' ) );
		$filters = array();
		
		if ( ! empty( $_POST['user_id'] ) ) {
			$filters['user_id'] = intval( $_POST['user_id'] );
		}
		
		if ( ! empty( $_POST['project'] ) ) {
			$filters['project'] = sanitize_text_field( $_POST['project'] );
		}
		
		if ( isset( $_POST['flt'] ) ) {
			$filters['flt'] = filter_var( $_POST['flt'], FILTER_VALIDATE_BOOLEAN );
		}
		
		
		$analytics = $this->get_timer_analytics( $period, $date, $filters );
		$user_analytics = $this->get_user_analytics( $period, $date, $filters );
		
		// Add user names to user analytics	
		$user_analytics = $this->add_user_names_to_analytics( $user_analytics );
		
		wp_send_json_success( array(
			'analytics' => $analytics,
			'user_analytics' => $user_analytics,
			'period' => $period,
			'date' => $date,
			'filters' => $filters,
			'debug' => array(
				'total_loads' => $analytics->total_loads ?? 'null',
				'timer_usage_percentage' => $analytics->timer_usage_percentage ?? 'null',
				'green_zone_count' => $analytics->green_zone_count ?? 'null'
			)
		) );
	}

	/**
	 * AJAX handler for getting timer logs for analytics
	 */
	public function ajax_get_timer_logs_analytics() {
		// Check user permissions (managers, tracking-tl, administrator)
		$TMSUser = new TMSUsers();

		if ( ! $TMSUser->check_user_role_access( array( 'managers', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking', 'administrator' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}
		
		// Get filters from POST data
		$filters = array(
			'period' => sanitize_text_field( $_POST['period'] ?? 'day' ),
			'date' => sanitize_text_field( $_POST['date'] ?? date( 'Y-m-d' ) ),
			'user_id' => sanitize_text_field( $_POST['user_id'] ?? '' ),
			'project' => sanitize_text_field( $_POST['project'] ?? '' ),
			'flt' => sanitize_text_field( $_POST['flt'] ?? '' ),
			'action' => sanitize_text_field( $_POST['timer_action'] ?? '' ), // This is the timer action filter
			'limit' => intval( $_POST['limit'] ?? 20 ),
			'offset' => intval( $_POST['offset'] ?? 0 )
		);
		
		// Get timer logs
		$result = $this->get_timer_logs_analytics( $filters );
		
		wp_send_json_success( array(
			'logs' => $result['logs'],
			'filters' => $filters,
			'total_logs' => $result['total_logs'],
			'loaded_count' => count( $result['logs'] )
		) );
	}

	/**
	 * AJAX: Export timer analytics to Excel
	 */
	public function ajax_export_timer_analytics() {
		// Check user permissions (managers, tracking-tl, administrator)
		$TMSUser = new TMSUsers();
		if ( ! $TMSUser->check_user_role_access( array( 'managers', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking', 'administrator' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Access denied' ) );
		}
		
		$period = sanitize_text_field( $_POST['period'] ?? 'day' );
		$date = sanitize_text_field( $_POST['date'] ?? date( 'Y-m-d' ) );
		$filters = array();
		
		if ( ! empty( $_POST['user_id'] ) ) {
			$filters['user_id'] = intval( $_POST['user_id'] );
		}
		
		if ( ! empty( $_POST['project'] ) ) {
			$filters['project'] = sanitize_text_field( $_POST['project'] );
		}
		
		if ( isset( $_POST['flt'] ) ) {
			$filters['flt'] = filter_var( $_POST['flt'], FILTER_VALIDATE_BOOLEAN );
		}
		
		// Generate Excel file
		try {
			$file_url = $this->generate_analytics_excel( $period, $date, $filters );
			
			if ( $file_url ) {
				wp_send_json_success( array( 'file_url' => $file_url ) );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to generate Excel file' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Export failed: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Generate Excel file for analytics
	 *
	 * @param string $period
	 * @param string $date
	 * @param array $filters
	 * @return string|false Filename or false on error
	 */
	private function generate_analytics_excel( $period, $date, $filters ) {
		// Get analytics data
		$analytics = $this->get_timer_analytics( $period, $date, $filters );
		$user_analytics = $this->get_user_analytics( $period, $date, $filters );
		
		// Add user names to user analytics
		$user_analytics = $this->add_user_names_to_analytics( $user_analytics );
		
		// Convert stdClass objects to arrays if needed
		if ( is_object( $analytics ) ) {
			$analytics = (array) $analytics;
		}
		if ( is_object( $user_analytics ) ) {
			$user_analytics = (array) $user_analytics;
		}
		
		// Get ALL timer logs (without pagination)
		$all_logs_filters = array_merge( $filters, array(
			'period' => $period,
			'date' => $date,
			'limit' => 999999, // Get all logs
			'offset' => 0
		) );
		$all_logs_result = $this->get_timer_logs_analytics( $all_logs_filters );
		$all_logs = $all_logs_result['logs'] ?? array();
		
		
		// Create filename with timestamp
		$timestamp = date( 'Y-m-d_H-i-s' );
		$filename = "timer_analytics_{$period}_{$date}_{$timestamp}.xlsx";
		
		// Create exports directory in theme if it doesn't exist (following TMSGenerateDocument pattern)
		$export_dir = get_theme_file_path() . '/exports/';
		if ( ! is_dir( $export_dir ) ) {
			mkdir( $export_dir, 0777, true );
		}
		
		$filepath = $export_dir . $filename;
		
		// Create Excel file using simple CSV format (Excel can open CSV)
		$csv_content = $this->generate_csv_content( $analytics, $user_analytics, $all_logs, $period, $date );
		
		// Write file to theme exports directory
		if ( file_put_contents( $filepath, $csv_content ) === false ) {
			return false;
		}
		
		// Get file URL for download (following TMSGenerateDocument pattern)
		$file_url = get_theme_file_uri() . '/exports/' . $filename;
		
		
		return $file_url;
	}

	/**
	 * Clean string for CSV export
	 *
	 * @param string $string
	 * @return string Cleaned string
	 */
	private function clean_csv_string( $string ) {
		// Convert to string if not already
		$string = (string) $string;
		
		
		// Remove all types of newlines, tabs, and control characters
		$string = str_replace( array( 
			"\n", "\r", "\t", "\0", 
			"\\n", "\\r", "\\t", "\\0",
			"\x0A", "\x0D", "\x09", "\x00"
		), ' ', $string );
		
		// Remove any remaining backslashes followed by n, r, t
		$string = preg_replace( '/\\\\[nrt]/', ' ', $string );
		
		// Remove multiple spaces
		$string = preg_replace( '/\s+/', ' ', $string );
		
		// Remove any non-printable characters except spaces
		$string = preg_replace( '/[^\x20-\x7E]/', ' ', $string );
		
		// Trim
		$string = trim( $string );
		
		
		return $string;
	}

	/**
	 * Generate CSV content for export
	 *
	 * @param array $analytics
	 * @param array $user_analytics
	 * @param array $all_logs
	 * @param string $period
	 * @param string $date
	 * @return string CSV content
	 */
	private function generate_csv_content( $analytics, $user_analytics, $all_logs, $period, $date ) {
		$csv_content = '';
		
		// Ensure all_logs is an array
		if ( ! is_array( $all_logs ) ) {
			$all_logs = array();
		}
		
		// Add UTF-8 BOM for proper Excel encoding
		$csv_content .= "\xEF\xBB\xBF";
		
		// Header - simple format to avoid Excel errors
		$csv_content .= "Timer Analytics Export\n";
		$csv_content .= "Period: {$period}\n";
		$csv_content .= "Date: {$date}\n";
		$csv_content .= "Generated: " . date( 'Y-m-d H:i:s' ) . "\n\n";
		
		// Overall Statistics
		$csv_content .= "\nOVERALL STATISTICS\n";
		$csv_content .= "Metric,Value\n";
		$csv_content .= "Total Timer Uses," . ( $analytics['total_timer_uses'] ?? 0 ) . "\n";
		$csv_content .= "Total Updates," . ( $analytics['total_updates'] ?? 0 ) . "\n";
		$csv_content .= "Yellow Zone Count," . ( $analytics['yellow_zone_count'] ?? 0 ) . "\n";
		$csv_content .= "Yellow Zone Percentage," . ( $analytics['yellow_zone_percentage'] ?? 0 ) . "%\n";
		$csv_content .= "Red Zone Count," . ( $analytics['red_zone_count'] ?? 0 ) . "\n";
		$csv_content .= "Red Zone Percentage," . ( $analytics['red_zone_percentage'] ?? 0 ) . "%\n";
		$csv_content .= "Black Zone Count," . ( $analytics['black_zone_count'] ?? 0 ) . "\n";
		$csv_content .= "Black Zone Percentage," . ( $analytics['black_zone_percentage'] ?? 0 ) . "%\n";
		
		// User Performance
		$csv_content .= "\nUSER PERFORMANCE\n";
		$csv_content .= "User Name,Timer Uses,Updates,Yellow Zone,Red Zone,Black Zone\n";
		
		if ( is_array( $user_analytics ) && ! empty( $user_analytics ) ) {
			foreach ( $user_analytics as $user ) {
				// Convert user object to array if needed
				if ( is_object( $user ) ) {
					$user = (array) $user;
				}
				
				// Clean user data
				$user_name = $user['user_name'] ?? 'Unknown User';
				
				// Clean data for CSV
				$user_name = $this->clean_csv_string( $user_name ) ?: 'Unknown User';
				
				// Create CSV row manually to avoid sprintf issues
				$csv_row = array(
					'"' . str_replace( '"', '""', $user_name ) . '"',
					$user['total_timer_uses'] ?? 0,
					$user['total_updates'] ?? 0,
					$user['yellow_zone_count'] ?? 0,
					$user['red_zone_count'] ?? 0,
					$user['black_zone_count'] ?? 0
				);
				
				$csv_content .= implode( ',', $csv_row ) . "\n";
			}
		}
		
		// Timer Logs
		$csv_content .= "\nTIMER LOGS\n";
		$csv_content .= "Load ID,User Name,Action,Comment,Project,FLT,Dispatcher ID,Created At\n";
		
		if ( is_array( $all_logs ) && ! empty( $all_logs ) ) {
			foreach ( $all_logs as $log ) {
				// Convert log object to array if needed
				if ( is_object( $log ) ) {
					$log = (array) $log;
				}
				
				// Clean log data - check both possible keys
				$load_id = $log['id_load'] ?? $log['load_id'] ?? 0;
				$user_name = $log['user_name'] ?? 'Unknown User';
				$action = $log['action'] ?? 'Unknown';
				$comment = $log['comment'] ?? '';
				$project = $log['project'] ?? '';
				$flt = $log['flt'] ? 'Yes' : 'No';
				$dispatcher_id = $log['dispatcher_id'] ?? '';
				$created_at = $log['created_at'] ?? '';
				
				// Clean data for CSV
				$user_name = $this->clean_csv_string( $user_name ) ?: 'Unknown User';
				$action = $this->clean_csv_string( $action ) ?: 'Unknown';
				$comment = $this->clean_csv_string( $comment ) ?: 'No Comment';
				$project = $this->clean_csv_string( $project ) ?: 'Unknown Project';
				$dispatcher_id = $this->clean_csv_string( $dispatcher_id ) ?: 'N/A';
				$created_at = $this->clean_csv_string( $created_at ) ?: 'Unknown Date';
				
				// Create CSV row manually to avoid sprintf issues
				$csv_row = array(
					$load_id,
					'"' . str_replace( '"', '""', $user_name ) . '"',
					'"' . str_replace( '"', '""', $action ) . '"',
					'"' . str_replace( '"', '""', $comment ) . '"',
					'"' . str_replace( '"', '""', $project ) . '"',
					'"' . $flt . '"',
					'"' . $dispatcher_id . '"',
					'"' . $created_at . '"'
				);
				
				$csv_content .= implode( ',', $csv_row ) . "\n";
			}
		}
		
		return $csv_content;
	}

	/**
	 * AJAX: Update existing timers with dispatcher_id
	 */
	public function ajax_update_timers_dispatcher_id() {
		// Check if user has permission
		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		
		// Run the update function
		$this->update_existing_timers_dispatcher_id();
		
		wp_send_json_success( array(
			'message' => 'Timers updated with dispatcher_id successfully'
		) );
	}

	/**
	 * Check if analytics should be updated (every 15 minutes)
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @return bool True if analytics should be updated
	 */
	public function should_update_analytics( $user_id, $project = '', $flt = false ) {
		$cache_key = $this->get_analytics_cache_key( $user_id, $project, $flt );
		$cached_data = get_transient( $cache_key );
		
		$should_update = ( $cached_data === false );
		
		
		
		return $should_update;
	}

	/**
	 * Generate cache key for analytics data
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @return string Cache key
	 */
	private function get_analytics_cache_key( $user_id, $project = '', $flt = false ) {
		$key_parts = array(
			'timer_analytics',
			'user_' . $user_id,
			'project_' . ( $project ?: 'default' ),
			'flt_' . ( $flt ? '1' : '0' )
		);
		
		return implode( '_', $key_parts );
	}

	/**
	 * Update analytics transient data
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @param array $analytics_data Analytics data to store
	 */
	public function update_analytics_transient( $user_id, $project, $flt, $analytics_data ) {
		$cache_key = $this->get_analytics_cache_key( $user_id, $project, $flt );
		$cache_duration = 15 * MINUTE_IN_SECONDS; // 15 minutes
		
		set_transient( $cache_key, $analytics_data, $cache_duration );
		
	}

	/**
	 * Get cached analytics data from transient
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @return array|null Cached analytics data or null if not available
	 */
	public function get_cached_analytics( $user_id, $project = '', $flt = false ) {
		$cache_key = $this->get_analytics_cache_key( $user_id, $project, $flt );
		$cached_data = get_transient( $cache_key );
		
		
		
		return $cached_data !== false ? $cached_data : null;
	}


	/**
	 * Invalidate analytics cache (force next request to get fresh data)
	 * 
	 * @param int $user_id User ID (optional, if not provided, invalidates all user caches)
	 * @param string $project Project name (optional)
	 * @param bool $flt FLT flag (optional)
	 */
	public function invalidate_analytics_cache( $user_id = null, $project = '', $flt = false ) {
		if ( $user_id ) {
			// Invalidate specific user's cache
			$cache_key = $this->get_analytics_cache_key( $user_id, $project, $flt );
			delete_transient( $cache_key );
		} else {
			// Invalidate all timer analytics caches (for all users)
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timer_analytics_%'" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_timer_analytics_%'" );
		}
	}

	/**
	 * Update analytics for current user with smart caching
	 * 
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @return array Updated analytics data
	 */
	public function get_smart_analytics( $project = '', $flt = false ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}


		// Check if we should update analytics
		if ( $this->should_update_analytics( $user_id, $project, $flt ) ) {
			// Get fresh analytics data
			$analytics_data = $this->get_timer_analytics( array(
				'user_id' => $user_id,
				'project' => $project,
				'flt' => $flt
			) );

			// Convert stdClass to array if needed
			if ( is_object( $analytics_data ) ) {
				$analytics_data = (array) $analytics_data;
			}

			// Cache the data using transients
			$this->update_analytics_transient( $user_id, $project, $flt, $analytics_data );

		} else {
			// Use cached data
			$analytics_data = $this->get_cached_analytics( $user_id, $project, $flt );
			if ( ! $analytics_data ) {
				// Fallback to fresh data if cache is empty
				$analytics_data = $this->get_timer_analytics( array(
					'user_id' => $user_id,
					'project' => $project,
					'flt' => $flt
				) );
				
				// Convert stdClass to array if needed
				if ( is_object( $analytics_data ) ) {
					$analytics_data = (array) $analytics_data;
				}
				
				// Cache the data using transients
				$this->update_analytics_transient( $user_id, $project, $flt, $analytics_data );
			}

		}

		return $analytics_data;
	}

	/**
	 * Force update analytics (bypass cache)
	 * 
	 * @param string $project Project name
	 * @param bool $flt FLT flag
	 * @return array Fresh analytics data
	 */
	public function force_update_analytics( $project = '', $flt = false ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}

		// Get fresh analytics data
		$analytics_data = $this->get_timer_analytics( array(
			'user_id' => $user_id,
			'project' => $project,
			'flt' => $flt
		) );

		// Convert stdClass to array if needed
		if ( is_object( $analytics_data ) ) {
			$analytics_data = (array) $analytics_data;
		}


		// Cache the data using transients
		$this->update_analytics_transient( $user_id, $project, $flt, $analytics_data );

		return $analytics_data;
	}

	/**
	 * AJAX: Get smart analytics (with caching)
	 */
	public function ajax_get_smart_analytics() {
		$project = sanitize_text_field( $_POST['project'] ?? '' );
		$flt = filter_var( $_POST['flt'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$force_update = filter_var( $_POST['force_update'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( $force_update ) {
			$analytics_data = $this->force_update_analytics( $project, $flt );
		} else {
			$analytics_data = $this->get_smart_analytics( $project, $flt );
		}

		wp_send_json_success( array(
			'analytics' => $analytics_data,
			'cached' => ! $force_update && ! $this->should_update_analytics()
		) );
	}

}