<?php

class  TMSStatistics extends TMSReportsHelper {
	public $table_main = '';
	public $table_meta = '';
	
	public function __construct() {
		$user_id       = get_current_user_id();
		$curent_tables = get_field( 'current_select', 'user_' . $user_id );
		if ( $curent_tables ) {
			$this->table_main = 'reports_' . strtolower( $curent_tables );
			$this->table_meta = 'reportsmeta_' . strtolower( $curent_tables );
		}
	}
	
	public function get_dispatcher_statistics() {
		global $wpdb;
		
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$query = "
	        SELECT
	            dispatcher_meta.meta_value AS dispatcher_initials,
	            COUNT(reports.id) AS post_count,
	            SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit,
	            AVG(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS average_profit
	        FROM {$table_reports} reports
	        INNER JOIN {$table_meta} dispatcher_meta
	            ON reports.id = dispatcher_meta.post_id
	        INNER JOIN {$table_meta} profit_meta
	            ON reports.id = profit_meta.post_id
	        WHERE dispatcher_meta.meta_key = 'dispatcher_initials'
	        AND profit_meta.meta_key = 'profit'
	        AND reports.status_post = 'publish'
	        GROUP BY dispatcher_meta.meta_value
	    ";
		
		// Выполняем запрос и получаем результаты
		$dispatcher_stats = $wpdb->get_results( $query, ARRAY_A );
		
		if ( is_array( $dispatcher_stats ) ) {
			foreach ( $dispatcher_stats as $key => $disp ) {
				$names = $this->get_user_full_name_by_id( $disp[ 'dispatcher_initials' ] );
				
				if ( $names ) {
					$dispatcher_stats[ $key ][ 'dispatcher_initials' ] = $names[ 'full_name' ];
				}
			}
		}
		
		return json_encode( $dispatcher_stats );
	}
	
	public function get_table_top_3_loads() {
		global $wpdb;
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$query = "
			SELECT dispatcher_meta.meta_value AS dispatcher_initials,
			profit_meta.meta_value AS profit
			FROM $table_reports reports
			INNER JOIN $table_meta dispatcher_meta
			    ON reports.id = dispatcher_meta.post_id
			    AND dispatcher_meta.meta_key = 'dispatcher_initials'
			INNER JOIN $table_meta profit_meta
			    ON reports.id = profit_meta.post_id
			    AND profit_meta.meta_key = 'profit'
			WHERE reports.status_post = 'publish'
			ORDER BY CAST(profit_meta.meta_value AS DECIMAL(10,2)) DESC
			LIMIT 3
		";
		
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		return $results;
	}
	
	public function get_monthly_dispatcher_stats( $dispatcher_initials, $year ) {
		
		global $wpdb;
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		// Adjusting the query to use %s for string comparison
		$query = $wpdb->prepare( "
			SELECT
			    MONTH(reports.date_booked) AS month,
			    COUNT(reports.id) AS post_count,
			    SUM(CAST(profit.meta_value AS DECIMAL(10,2))) AS total_profit,
			    AVG(CAST(profit.meta_value AS DECIMAL(10,2))) AS average_profit
			FROM wp_reports_odysseia reports
			INNER JOIN wp_reportsmeta_odysseia meta ON reports.id = meta.post_id
			INNER JOIN wp_reportsmeta_odysseia profit ON reports.id = profit.post_id
			WHERE meta.meta_key = 'dispatcher_initials'
			  AND meta.meta_value = %d  -- Change as needed
			  AND profit.meta_key = 'profit'
			  AND YEAR(reports.date_booked) = %d
			  AND reports.status_post = 'publish'
			GROUP BY month
			ORDER BY month
			", $dispatcher_initials, $year );
		
		// Execute the query
		$results = $wpdb->get_results( $query, ARRAY_A );

		// Create a mapping of month numbers to names
		$months = [
			1  => 'January',
			2  => 'February',
			3  => 'March',
			4  => 'April',
			5  => 'May',
			6  => 'June',
			7  => 'July',
			8  => 'August',
			9  => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December'
		];

		// Initialize the result array with month names and 0 values
		$monthly_stats = [];
		foreach ( $months as $month_num => $month_name ) {
			$monthly_stats[ $month_num ] = [
				'month'          => $month_name,
				'post_count'     => 0,
				'total_profit'   => 0.00,
				'average_profit' => 0.00
			];
		}

		// Populate the result with actual data
		foreach ( $results as $row ) {
			$month_key = (int) $row[ 'month' ]; // Ensure we use an integer for the month key
			
			if ( isset( $monthly_stats[ $month_key ] ) ) {
				$monthly_stats[ $month_key ] = [
					'month'          => $months[ $month_key ],
					'post_count'     => $row[ 'post_count' ] ?? 0,
					'total_profit'   => $row[ 'total_profit' ] ?? 0.00,
					'average_profit' => $row[ 'average_profit' ] ?? 0.00
				];
			}
		}

		// Return the monthly statistics
		return $monthly_stats;
	}
}