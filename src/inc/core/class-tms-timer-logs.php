<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSTimerLogs extends TMSLogs {
	
	public $table_timer_logs = 'timer_logs';
	
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * Log timer action
	 * 
	 * @param int $id_load Load ID
	 * @param string $action Action type (start, stop, pause, resume)
	 * @param string $comment Comment
	 * @param string $project Project name
	 * @param bool $flt Is FLT load
	 * @return bool Success status
	 */
	public function log_timer_action( $id_load, $action, $comment = '', $project = '', $flt = false ) {
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		
		// Get dispatcher_id from the load
		$dispatcher_id = $this->get_dispatcher_id_from_load( $id_load, $project, $flt );
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		
		$result = $wpdb->insert(
			$table_logs,
			array(
				'id_load' => $id_load,
				'id_user' => $user_id,
				'dispatcher_id' => $dispatcher_id,
				'action' => $action,
				'comment' => $comment,
				'project' => $project,
				'flt' => $flt ? 1 : 0,
				'created_at' => $this->get_new_york_time()
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
		
		return $result !== false;
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
		}
		
		return $dispatcher_id ? intval( $dispatcher_id ) : null;
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
	 * Get timer logs for a load
	 * 
	 * @param int $id_load Load ID
	 * @param int $user_id User ID (optional)
	 * @param int $limit Limit results (optional)
	 * @return array Array of logs
	 */
	public function get_timer_logs( $id_load, $user_id = null, $limit = null ) {
		global $wpdb;
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		$table_users = $wpdb->prefix . 'users';
		
		$where_clause = "WHERE tl.id_load = %d";
		$params = array( $id_load );
		
		if ( $user_id ) {
			$where_clause .= " AND tl.id_user = %d";
			$params[] = $user_id;
		}
		
		$limit_clause = '';
		if ( $limit ) {
			$limit_clause = " LIMIT %d";
			$params[] = $limit;
		}
		
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tl.*, 
					u.display_name as user_name,
					d.display_name as dispatcher_name
				FROM $table_logs tl
				LEFT JOIN $table_users u ON tl.id_user = u.ID
				LEFT JOIN $table_users d ON tl.dispatcher_id = d.ID
				$where_clause 
				ORDER BY tl.created_at DESC$limit_clause",
				$params
			),
			ARRAY_A
		);
		
		return $logs ?: array();
	}
	
	/**
	 * Get timer logs for a user
	 * 
	 * @param int $user_id User ID
	 * @param string $project Project name (optional)
	 * @param bool $flt Is FLT (optional)
	 * @param int $limit Limit results (optional)
	 * @return array Array of logs
	 */
	public function get_user_timer_logs( $user_id, $project = '', $flt = null, $limit = null ) {
		global $wpdb;
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		
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
		
		$limit_clause = '';
		if ( $limit ) {
			$limit_clause = " LIMIT %d";
			$params[] = $limit;
		}
		
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_logs $where_clause ORDER BY created_at DESC$limit_clause",
				$params
			),
			ARRAY_A
		);
		
		return $logs ?: array();
	}
	
	/**
	 * Get timer logs statistics
	 * 
	 * @param int $user_id User ID (optional)
	 * @param string $project Project name (optional)
	 * @param bool $flt Is FLT (optional)
	 * @param string $date_from Date from (optional)
	 * @param string $date_to Date to (optional)
	 * @return array Statistics
	 */
	public function get_timer_logs_stats( $user_id = null, $project = '', $flt = null, $date_from = '', $date_to = '' ) {
		global $wpdb;
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		
		$where_conditions = array();
		$params = array();
		
		if ( $user_id ) {
			$where_conditions[] = "id_user = %d";
			$params[] = $user_id;
		}
		
		if ( $project ) {
			$where_conditions[] = "project = %s";
			$params[] = $project;
		}
		
		if ( $flt !== null ) {
			$where_conditions[] = "flt = %d";
			$params[] = $flt ? 1 : 0;
		}
		
		if ( $date_from ) {
			$where_conditions[] = "created_at >= %s";
			$params[] = $date_from;
		}
		
		if ( $date_to ) {
			$where_conditions[] = "created_at <= %s";
			$params[] = $date_to;
		}
		
		$where_clause = '';
		if ( $where_conditions ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}
		
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total_logs,
					SUM(CASE WHEN action = 'start' THEN 1 ELSE 0 END) as start_actions,
					SUM(CASE WHEN action = 'stop' THEN 1 ELSE 0 END) as stop_actions,
					SUM(CASE WHEN action = 'pause' THEN 1 ELSE 0 END) as pause_actions,
					SUM(CASE WHEN action = 'resume' THEN 1 ELSE 0 END) as resume_actions,
					COUNT(DISTINCT id_load) as unique_loads,
					COUNT(DISTINCT id_user) as unique_users
				FROM $table_logs $where_clause",
				$params
			),
			ARRAY_A
		);
		
		return $stats ?: array(
			'total_logs' => 0,
			'start_actions' => 0,
			'stop_actions' => 0,
			'pause_actions' => 0,
			'resume_actions' => 0,
			'unique_loads' => 0,
			'unique_users' => 0
		);
	}
	
	/**
	 * Clean old timer logs (older than specified days)
	 * 
	 * @param int $days_old Days old (default: 365)
	 * @return int Number of deleted records
	 */
	public function clean_old_timer_logs( $days_old = 365 ) {
		global $wpdb;
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-$days_old days" ) );
		
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_logs WHERE created_at < %s",
				$cutoff_date
			)
		);
		
		return $deleted ?: 0;
	}
	
	/**
	 * Export timer logs to CSV
	 * 
	 * @param int $user_id User ID (optional)
	 * @param string $project Project name (optional)
	 * @param bool $flt Is FLT (optional)
	 * @param string $date_from Date from (optional)
	 * @param string $date_to Date to (optional)
	 * @return string CSV content
	 */
	public function export_timer_logs_csv( $user_id = null, $project = '', $flt = null, $date_from = '', $date_to = '' ) {
		global $wpdb;
		
		$table_logs = $wpdb->prefix . $this->table_timer_logs;
		
		$where_conditions = array();
		$params = array();
		
		if ( $user_id ) {
			$where_conditions[] = "id_user = %d";
			$params[] = $user_id;
		}
		
		if ( $project ) {
			$where_conditions[] = "project = %s";
			$params[] = $project;
		}
		
		if ( $flt !== null ) {
			$where_conditions[] = "flt = %d";
			$params[] = $flt ? 1 : 0;
		}
		
		if ( $date_from ) {
			$where_conditions[] = "created_at >= %s";
			$params[] = $date_from;
		}
		
		if ( $date_to ) {
			$where_conditions[] = "created_at <= %s";
			$params[] = $date_to;
		}
		
		$where_clause = '';
		if ( $where_conditions ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}
		
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_logs $where_clause ORDER BY created_at DESC",
				$params
			),
			ARRAY_A
		);
		
		// Generate CSV
		$csv_content = "ID,Load ID,User ID,Action,Comment,Project,FLT,Created At\n";
		
		foreach ( $logs as $log ) {
			$csv_content .= sprintf(
				"%d,%d,%d,%s,\"%s\",%s,%d,%s\n",
				$log['id'],
				$log['id_load'],
				$log['id_user'],
				$log['action'],
				str_replace( '"', '""', $log['comment'] ),
				$log['project'],
				$log['flt'],
				$log['created_at']
			);
		}
		
		return $csv_content;
	}

}
