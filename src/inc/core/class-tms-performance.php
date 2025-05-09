<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsPerformance extends TMSReportsHelper {
	public $table_main = 'reports_performance';
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table_performance' ) );
		
		$this->ajax_actions();
	}
	
	public function ajax_actions() {
		add_action( 'wp_ajax_update_performance', array( $this, 'update_performance' ) );
	}
	
	public function update_performance() {
		// Ensure this is an AJAX request
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
			
			return;
		}
		
		// Sanitize and validate input data
		$MY_INPUT = filter_var_array( $_POST, [
			'user_id'         => FILTER_VALIDATE_INT,
			'date'            => FILTER_SANITIZE_STRING,
			'monday_calls'    => FILTER_VALIDATE_INT,
			'tuesday_calls'   => FILTER_VALIDATE_INT,
			'wednesday_calls' => FILTER_VALIDATE_INT,
			'thursday_calls'  => FILTER_VALIDATE_INT,
			'friday_calls'    => FILTER_VALIDATE_INT,
			'saturday_calls'  => FILTER_VALIDATE_INT,
			'sunday_calls'    => FILTER_VALIDATE_INT,
			'bonus'           => FILTER_VALIDATE_FLOAT,
		] );
		
		// Check required fields
		if ( empty( $MY_INPUT[ 'user_id' ] ) || empty( $MY_INPUT[ 'date' ] ) ) {
			wp_send_json_error( [ 'message' => 'Invalid or missing user_id or date' ] );
			
			return;
		}
		
		$current_user_id = get_current_user_id();
		
		// Replace null values with defaults
		$MY_INPUT[ 'monday_calls' ]      = $MY_INPUT[ 'monday_calls' ] ?? 0;
		$MY_INPUT[ 'tuesday_calls' ]     = $MY_INPUT[ 'tuesday_calls' ] ?? 0;
		$MY_INPUT[ 'wednesday_calls' ]   = $MY_INPUT[ 'wednesday_calls' ] ?? 0;
		$MY_INPUT[ 'thursday_calls' ]    = $MY_INPUT[ 'thursday_calls' ] ?? 0;
		$MY_INPUT[ 'friday_calls' ]      = $MY_INPUT[ 'friday_calls' ] ?? 0;
		$MY_INPUT[ 'saturday_calls' ]    = $MY_INPUT[ 'saturday_calls' ] ?? 0;
		$MY_INPUT[ 'sunday_calls' ]      = $MY_INPUT[ 'sunday_calls' ] ?? 0;
		$MY_INPUT[ 'bonus' ]             = $MY_INPUT[ 'bonus' ] ?? 0.00;
		$MY_INPUT[ 'user_last_updated' ] = $current_user_id;
		
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_main;
		// Check if the record exists
		$existing_record = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_name WHERE user_id = %d AND date = %s", $MY_INPUT[ 'user_id' ], $MY_INPUT[ 'date' ] ), ARRAY_A );
		
		if ( ! $existing_record ) {
			wp_send_json_error( [ 'message' => 'Record not found for the given user_id and date' ] );
			
			return;
		}
		
		// Update the record
		$updated = $wpdb->update( $table_name, [
			'monday_calls'      => $MY_INPUT[ 'monday_calls' ],
			'tuesday_calls'     => $MY_INPUT[ 'tuesday_calls' ],
			'wednesday_calls'   => $MY_INPUT[ 'wednesday_calls' ],
			'thursday_calls'    => $MY_INPUT[ 'thursday_calls' ],
			'friday_calls'      => $MY_INPUT[ 'friday_calls' ],
			'saturday_calls'    => $MY_INPUT[ 'saturday_calls' ],
			'sunday_calls'      => $MY_INPUT[ 'sunday_calls' ],
			'bonus'             => $MY_INPUT[ 'bonus' ],
			'user_last_updated' => $current_user_id,
		], [ 'id' => $existing_record[ 'id' ] ], [
			'%d',
			'%d',
			'%d',
			'%d',
			'%d',
			'%d',
			'%d',
			'%f',
			'%d'
		], [ '%d' ] );
		
		if ( $updated === false ) {
			wp_send_json_error( [ 'message' => 'Failed to update the record' ] );
			
			return;
		}
		
		// Return success response with the updated data
		wp_send_json_success( [ 'message' => 'Record successfully updated', 'data' => $MY_INPUT ] );
	}
	
	
	public function create_table_performance() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        user_last_updated mediumint(9),
        date DATE NOT NULL,
        monday_calls mediumint(9) DEFAULT 0,
        tuesday_calls mediumint(9) DEFAULT 0,
        wednesday_calls mediumint(9) DEFAULT 0,
        thursday_calls mediumint(9) DEFAULT 0,
        friday_calls mediumint(9) DEFAULT 0,
        saturday_calls mediumint(9) DEFAULT 0,
        sunday_calls mediumint(9) DEFAULT 0,
        bonus decimal(10,2) DEFAULT 0.00,
        PRIMARY KEY (id),
        UNIQUE KEY user_date (user_id, date),
        INDEX idx_user_id (user_id),
        INDEX idx_date (date)
    ) $charset_collate;";
		
		dbDelta( $sql );
	}
	
	public function get_or_create_performance_record( $user_id, $date ) {
		global $wpdb;
		if ( ! $this->is_valid_date( $date ) ) {
			return false; // Invalid date
		}
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$existing_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d AND date = %s", $user_id, $date ), ARRAY_A );
		
		if ( $existing_record ) {
			return $existing_record;
		}
		
		$inserted = $wpdb->insert( $table_name, [
			'user_id'         => $user_id,
			'date'            => $date,
			'monday_calls'    => 0,
			'tuesday_calls'   => 0,
			'wednesday_calls' => 0,
			'thursday_calls'  => 0,
			'friday_calls'    => 0,
			'saturday_calls'  => 0,
			'sunday_calls'    => 0,
			'bonus'           => 0.00
		] );
		
		if ( $inserted ) {
			return $wpdb->insert_id;
		}
		
		return false;
	}
	
	public function get_dispatcher_weekly_report( $start_date, $dispatcher_id ) {
		global $wpdb;
		
		// Преобразование даты понедельника
		$start_date = date( 'Y-m-d', strtotime( $start_date ) );
		
		// Формирование списка дат недели
		$week_dates = [];
		for ( $i = 0; $i < 7; $i ++ ) {
			$week_dates[] = date( 'Y-m-d', strtotime( "+$i days", strtotime( $start_date ) ) );
		}
		
		// Таблицы
		$report_tables = [
			$wpdb->prefix . 'reports_martlet',
			$wpdb->prefix . 'reports_odysseia',
			$wpdb->prefix . 'reports_endurance'
		];
		$meta_tables   = [
			$wpdb->prefix . 'reportsmeta_martlet',
			$wpdb->prefix . 'reportsmeta_odysseia',
			$wpdb->prefix . 'reportsmeta_endurance'
		];
		
		$weekly_report = [];
		
		foreach ( $week_dates as $date ) {
			$day_report = [
				'date'       => $date,
				'post_count' => 0,
				'profit'     => 0.00,
			];
			
			foreach ( $report_tables as $index => $report_table ) {
				// Определяем таблицы
				$table_reports = $report_table;
				$table_meta    = $meta_tables[ $index ];
				
				// SQL-запрос с явным указанием связей
				$query = $wpdb->prepare( "SELECT
                    COUNT(DISTINCT reports.id) AS post_count,
                    SUM(CAST(profit_meta.meta_value AS DECIMAL(10, 2))) AS total_profit
                 FROM $table_reports reports
                 INNER JOIN $table_meta dispatcher_meta
                    ON reports.id = dispatcher_meta.post_id
                    AND dispatcher_meta.meta_key = 'dispatcher_initials'
                 INNER JOIN $table_meta profit_meta
                    ON reports.id = profit_meta.post_id
                    AND profit_meta.meta_key = 'profit'
                 INNER JOIN $table_meta AS load_status
			        ON reports.id = load_status.post_id
			        AND load_status.meta_key = 'load_status'
                 WHERE DATE(reports.date_booked) = %s
                   AND dispatcher_meta.meta_value = %d
                   AND load_status.meta_value NOT IN ('waiting-on-rc', 'cancelled')
                   AND reports.status_post = 'publish'", $date, $dispatcher_id );
				
				$result = $wpdb->get_row( $query );
				
				if ( $result ) {
					$day_report[ 'post_count' ] += intval( $result->post_count );
					$day_report[ 'profit' ]     += floatval( $result->total_profit );
				}
			}
			
			$weekly_report[] = $day_report;
		}
		
		return $weekly_report;
	}
	
	function calculate_performance( $calls, $loads, $profit ) {
		$calls_performance  = $calls * 0.01;                // 1% от звонков
		$loads_performance  = $loads * 0.20;                // 20% от загрузок
		$profit_performance = floor( $profit / 10 ) * 0.01; // 1% от каждого $10
		
		$performance = $calls_performance + $loads_performance + $profit_performance;
		
		return $performance * 100;
	}
}