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
	
	public function get_dispatcher_statistics_current_month($user_id = null) {
		global $wpdb;
		
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		// Получаем текущий год и месяц
		$current_year = date('Y');
		$current_month = date('m');
		
		// Основная часть запроса
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
        AND YEAR(reports.date_booked) = %d
        AND MONTH(reports.date_booked) = %d
    ";
		
		// Если указан user_id, добавляем условие фильтрации
		if ($user_id) {
			$query .= " AND dispatcher_meta.meta_value = %s";
			$query = $wpdb->prepare($query, $current_year, $current_month, $user_id);
		} else {
			$query = $wpdb->prepare($query, $current_year, $current_month);
		}
		
		// Группировка по диспетчерам
		$query .= " GROUP BY dispatcher_meta.meta_value";
		
		// Выполняем запрос и получаем результаты
		$dispatcher_stats = $wpdb->get_results($query, ARRAY_A);
		if (is_array($dispatcher_stats) && !empty($dispatcher_stats)) {
			foreach ($dispatcher_stats as $key => $disp) {
				$names = $this->get_user_full_name_by_id($disp['dispatcher_initials']);
				$goal = get_field('monthly_goal', 'user_'.$disp['dispatcher_initials']);
				
				if ($names) {
					$dispatcher_stats[$key]['dispatcher_initials'] = $names['full_name'];
				}
				
				if (!$goal) {
					$goal = 0;
				}
				
				$dispatcher_stats[$key]['goal'] = $goal;
			}
		} else {
			if ($user_id) {
				$names = $this->get_user_full_name_by_id($user_id);
				$goal = get_field('monthly_goal', 'user_'.$user_id);
				
				if ($names) {
					$dispatcher_stats[0]['dispatcher_initials'] = $names['full_name'];
				}
				
				if (!$goal) {
					$goal = 0;
				}
				
				$dispatcher_stats[0]['goal'] = $goal;
				$dispatcher_stats[0]['post_count'] = 0;
				$dispatcher_stats[0]['total_profit'] = 0;
				$dispatcher_stats[0]['average_profit'] = 0;
			}
		}
		
		return $dispatcher_stats;
	}
	
	
	public function get_monthly_fuctoring_stats( $year, $month ) {
		global $wpdb;
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$sql = "
		    SELECT
		        COUNT(DISTINCT reports.id) AS post_count,
		        SUM(CAST(IFNULL(booked_rate.meta_value, 0) AS DECIMAL(10,2))) AS total_booked_rate,
		        SUM(CAST(IFNULL(profit.meta_value, 0) AS DECIMAL(10,2))) AS total_profit,
		        SUM(CAST(IFNULL(driver_rate.meta_value, 0) AS DECIMAL(10,2))) AS total_driver_rate,
		        SUM(CAST(IFNULL(true_profit.meta_value, 0) AS DECIMAL(10,2))) AS total_true_profit,
		        SUM(CAST(IFNULL(processing_fees.meta_value, 0) AS DECIMAL(10,2))) AS total_processing_fees,
		        SUM(CAST(IFNULL(percent_quick_pay_value.meta_value, 0) AS DECIMAL(10,2))) AS total_percent_quick_pay_value,
		        SUM(CAST(IFNULL(quick_pay_driver_amount.meta_value, 0) AS DECIMAL(10,2))) AS total_quick_pay_driver_amount,
		        SUM(CAST(IFNULL(booked_rate_modify.meta_value, 0) AS DECIMAL(10,2))) AS total_booked_rate_modify
		    FROM $table_reports reports
		    LEFT JOIN $table_meta profit ON reports.id = profit.post_id AND profit.meta_key = 'profit'
		    LEFT JOIN $table_meta booked_rate ON reports.id = booked_rate.post_id AND booked_rate.meta_key = 'booked_rate'
		    LEFT JOIN $table_meta driver_rate ON reports.id = driver_rate.post_id AND driver_rate.meta_key = 'driver_rate'
		    LEFT JOIN $table_meta quick_pay_driver_amount ON reports.id = quick_pay_driver_amount.post_id AND quick_pay_driver_amount.meta_key = 'quick_pay_driver_amount'
		    LEFT JOIN $table_meta percent_quick_pay_value ON reports.id = percent_quick_pay_value.post_id AND percent_quick_pay_value.meta_key = 'percent_quick_pay_value'
		    LEFT JOIN $table_meta processing_fees ON reports.id = processing_fees.post_id AND processing_fees.meta_key = 'processing_fees'
		    LEFT JOIN $table_meta true_profit ON reports.id = true_profit.post_id AND true_profit.meta_key = 'true_profit'
		    LEFT JOIN $table_meta booked_rate_modify ON reports.id = booked_rate_modify.post_id AND booked_rate_modify.meta_key = 'booked_rate_modify'
		";
		
		if ($year === 'all' || $month === 'all') {
			$sql .= "WHERE reports.status_post = 'publish'";
		} else {
			$sql .= "WHERE YEAR(reports.date_booked) = %d
		      AND MONTH(reports.date_booked) = %d
		      AND reports.status_post = 'publish'";
		}
		// Prepare query with necessary joins for each meta field
		$query = $wpdb->prepare( $sql, $year, $month );
		// Execute the query
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Initialize the result array
		$monthly_stats = [
			'month'          => $month,
			'post_count'     => 0,
			'total_booked_rate' => 0.00,
			'total_profit'   => 0.00,
			'total_driver_rate' => 0.00,
			'total_true_profit' => 0.00,
			'total_booked_rate_modify' => 0.00,
			'total_processing_fees' => 0.00,
			'percent_quick_pay_value' => 0.00,
			'total_quick_pay_driver_amount' => 0.00,
		];
		
		// Populate the result with actual data if available
		if (!empty($results) && isset($results[0])) {
			$monthly_stats['post_count'] = $results[0]['post_count'] ?? 0;
			$monthly_stats['total_booked_rate'] = $results[0]['total_booked_rate'] ?? 0.00;
			$monthly_stats['total_profit'] = $results[0]['total_profit'] ?? 0.00;
			$monthly_stats['total_driver_rate'] = $results[0]['total_driver_rate'] ?? 0.00;
			$monthly_stats['total_true_profit'] = $results[0]['total_true_profit'] ?? 0.00;
			$monthly_stats['total_booked_rate_modify'] = $results[0]['total_booked_rate_modify'] ?? 0.00;
			$monthly_stats['total_processing_fees'] = $results[0]['total_processing_fees'] ?? 0.00;
			$monthly_stats['percent_quick_pay_value'] = $results[0]['percent_quick_pay_value'] ?? 0.00;
			$monthly_stats['total_quick_pay_driver_amount'] = $results[0]['total_quick_pay_driver_amount'] ?? 0.00;
		}
		
		// Return the monthly statistics
		return $monthly_stats;
	}
	
}