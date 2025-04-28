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
	
	public function countWeekdays( $monthName, $year ) {
		// Преобразуем название месяца в номер (January -> 1, February -> 2 и т.д.)
		$month = date( 'n', strtotime( $monthName ) );
		
		// Начало и конец месяца
		$startDate = new DateTime( "$year-$month-01" );
		$endDate   = ( clone $startDate )->modify( 'last day of this month' );
		
		$weekdayCount = 0;
		
		// Пробегаемся по всем дням месяца
		while ( $startDate <= $endDate ) {
			// Проверяем, является ли день будним (1 - понедельник, 5 - пятница)
			if ( $startDate->format( 'N' ) >= 1 && $startDate->format( 'N' ) <= 5 ) {
				$weekdayCount ++;
			}
			// Переход к следующему дню
			$startDate->modify( '+1 day' );
		}
		
		return $weekdayCount;
	}
	
	public function get_sources_statistics( $office_dispatcher = 'all' ) {
		global $wpdb;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Результирующий массив
		$statistics = [];
		
		foreach ( $this->sources as $key => $label ) {
			// Базовый SQL-запрос
			$sql = "
            SELECT COUNT(DISTINCT source_meta.post_id) as count,
                   SUM(CASE WHEN profit_meta.meta_key = 'profit' THEN profit_meta.meta_value ELSE 0 END) as profit
            FROM $table_meta as source_meta
            INNER JOIN $table_meta as profit_meta
                ON source_meta.post_id = profit_meta.post_id
                AND profit_meta.meta_key = 'profit'
            LEFT JOIN $table_meta as office_dispatcher
                ON source_meta.post_id = office_dispatcher.post_id
                AND office_dispatcher.meta_key = 'office_dispatcher'
            LEFT JOIN $table_meta as load_status
                ON source_meta.post_id = load_status.post_id
                AND load_status.meta_key = 'load_status'
            WHERE source_meta.meta_key = 'source'
                AND load_status.meta_value != 'cancelled'
                AND source_meta.meta_value = %s
        ";
			
			// Добавляем фильтр по офису, если он задан
			if ( $office_dispatcher !== 'all' ) {
				$sql   .= " AND office_dispatcher.meta_value = %s";
				$query = $wpdb->prepare( $sql, $key, $office_dispatcher );
			} else {
				$query = $wpdb->prepare( $sql, $key );
			}
			
			// Выполняем запрос
			$result = $wpdb->get_row( $query );
			
			$statistics[ $key ] = [
				'label'        => $label,
				'post_count'   => $result->count ?? 0,
				'total_profit' => isset( $result->profit ) ? number_format( $result->profit, 2 ) : '0.00',
			];
		}
		
		return json_encode( $statistics );
	}
	
	
	public function get_dispatcher_statistics( $office_dispatcher_selected ) {
		global $wpdb;
		
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$sql = "
		    SELECT
		        dispatcher_meta.meta_value AS dispatcher_initials,
		        COUNT(reports.id) AS post_count,
		        SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit,
		        AVG(CASE WHEN load_status.meta_value != 'tonu' THEN CAST(profit_meta.meta_value AS DECIMAL(10,2)) ELSE 0 END) AS average_profit
		    FROM $table_reports AS reports
		    INNER JOIN $table_meta AS dispatcher_meta
		        ON reports.id = dispatcher_meta.post_id
		        AND dispatcher_meta.meta_key = 'dispatcher_initials'
		    INNER JOIN $table_meta AS profit_meta
		        ON reports.id = profit_meta.post_id
		        AND profit_meta.meta_key = 'profit'
		    INNER JOIN $table_meta AS load_status
		        ON reports.id = load_status.post_id
		        AND load_status.meta_key = 'load_status'
		    WHERE
		       load_status.meta_value != 'cancelled'
		      AND reports.status_post = 'publish'
		    GROUP BY dispatcher_meta.meta_value
		";
		
		// Выполняем запрос и получаем результаты
		$dispatcher_stats = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $dispatcher_stats ) ) {
			foreach ( $dispatcher_stats as $key => $disp ) {
				$names             = $this->get_user_full_name_by_id( $disp[ 'dispatcher_initials' ] );
				$office_dispatcher = get_field( 'work_location', 'user_' . $disp[ 'dispatcher_initials' ] );
				if ( $office_dispatcher_selected !== 'all' && $office_dispatcher !== $office_dispatcher_selected ) {
					unset( $dispatcher_stats[ $key ] );
				} else {
					if ( $names ) {
						$dispatcher_stats[ $key ][ 'dispatcher_initials' ] = $names[ 'full_name' ];
					}
				}
			}
		}
		$dispatcher_stats = array_values( $dispatcher_stats );
		
		return json_encode( $dispatcher_stats );
	}
	
	public function get_table_top_3_loads() {
		global $wpdb;
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$query = "
			SELECT dispatcher_meta.meta_value AS dispatcher_initials,
			profit_meta.meta_value AS profit, reference_number.meta_value AS reference_number
			FROM $table_reports reports
			INNER JOIN $table_meta dispatcher_meta
			    ON reports.id = dispatcher_meta.post_id
			    AND dispatcher_meta.meta_key = 'dispatcher_initials'
		    INNER JOIN $table_meta reference_number
			    ON reports.id = reference_number.post_id
			    AND reference_number.meta_key = 'reference_number'
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
		        AVG(CASE WHEN load_status.meta_value != 'tonu' THEN CAST(profit.meta_value AS DECIMAL(10,2)) ELSE 0 END) AS average_profit
		    FROM $table_reports reports
		    INNER JOIN $table_meta meta
		        ON reports.id = meta.post_id
		        AND meta.meta_key = 'dispatcher_initials'
		    INNER JOIN $table_meta profit
		        ON reports.id = profit.post_id
		        AND profit.meta_key = 'profit'
		    INNER JOIN $table_meta load_status
		        ON reports.id = load_status.post_id
		        AND load_status.meta_key = 'load_status'
		    WHERE meta.meta_value = %s
		      AND load_status.meta_value != 'cancelled'
		      AND YEAR(reports.date_booked) = %d
		      AND reports.status_post = 'publish'
		    GROUP BY month
		    ORDER BY month
		", $dispatcher_initials, $year );
		
		
		// Execute the query
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Create a mapping of month numbers to names
		$months = $this->get_months();
		
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
	
	public function get_dispatcher_statistics_current_month( $user_id = null ) {
		global $wpdb;
		
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		// Получаем текущий год и месяц
		$current_year  = date( 'Y' );
		$current_month = date( 'm' );
		
		// Основная часть запроса
		$query = "
        SELECT
            dispatcher_meta.meta_value AS dispatcher_initials,
            COUNT(reports.id) AS post_count,
            SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit,
            AVG(CASE WHEN load_status.meta_value != 'tonu' THEN CAST(profit_meta.meta_value AS DECIMAL(10,2)) ELSE 0 END) AS average_profit
        FROM {$table_reports} reports
        INNER JOIN {$table_meta} dispatcher_meta
            ON reports.id = dispatcher_meta.post_id
		    AND dispatcher_meta.meta_key = 'dispatcher_initials'
        INNER JOIN {$table_meta} profit_meta
            ON reports.id = profit_meta.post_id
        INNER JOIN {$table_meta} load_status
            ON reports.id = load_status.post_id
        WHERE profit_meta.meta_key = 'profit'
        AND reports.status_post = 'publish'
                  AND load_status.meta_key = 'load_status'
	          AND load_status.meta_value != 'cancelled'
        AND YEAR(reports.date_booked) = %d
        AND MONTH(reports.date_booked) = %d
    ";
		
		// Если указан user_id, добавляем условие фильтрации
		if ( $user_id ) {
			if ( is_array( $user_id ) ) {
				// Создаем плейсхолдеры для массива
				$placeholders = implode( ',', array_fill( 0, count( $user_id ), '%s' ) );
				// Добавляем условие IN с плейсхолдерами
				$query .= " AND dispatcher_meta.meta_value IN ($placeholders)";
				
				// Объединяем массив значений для $user_id с другими параметрами
				$query = $wpdb->prepare( $query, array_merge( [ $current_year, $current_month ], $user_id ) );
			} else {
				$query .= " AND dispatcher_meta.meta_value = %s";
				$query = $wpdb->prepare( $query, $current_year, $current_month, $user_id );
			}
		} else {
			$query = $wpdb->prepare( $query, $current_year, $current_month );
		}
		
		// Группировка по диспетчерам
		$query .= " GROUP BY dispatcher_meta.meta_value";
		
		// Выполняем запрос и получаем результаты
		$dispatcher_stats = $wpdb->get_results( $query, ARRAY_A );
		if ( is_array( $dispatcher_stats ) && ! empty( $dispatcher_stats ) ) {
			foreach ( $dispatcher_stats as $key => $disp ) {
				$names = $this->get_user_full_name_by_id( $disp[ 'dispatcher_initials' ] );
				$goal  = get_field( 'monthly_goal', 'user_' . $disp[ 'dispatcher_initials' ] );
				
				if ( $names ) {
					$dispatcher_stats[ $key ][ 'dispatcher_initials' ] = $names[ 'full_name' ];
				}
				
				if ( ! $goal ) {
					$goal = 0;
				}
				
				$dispatcher_stats[ $key ][ 'goal' ] = $goal;
			}
		}
		
		return $dispatcher_stats;
	}
	
	public function get_all_users_statistics() {
		global $wpdb;
		
		$exclude_users = get_field( 'exclude_users', get_the_ID() );
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		// Основная часть запроса
		$query = "
			SELECT
				dispatcher_meta.meta_value AS dispatcher_initials,
				COUNT(reports.id) AS post_count,
				SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit
			FROM {$table_reports} reports
			INNER JOIN {$table_meta} dispatcher_meta
				ON reports.id = dispatcher_meta.post_id
		    INNER JOIN {$table_meta} load_status
				ON reports.id = load_status.post_id
			INNER JOIN {$table_meta} profit_meta
				ON reports.id = profit_meta.post_id
			WHERE dispatcher_meta.meta_key = 'dispatcher_initials'
			  AND load_status.meta_key = 'load_status'
			  AND load_status.meta_value != 'cancelled'
			AND profit_meta.meta_key = 'profit'
			AND reports.status_post = 'publish'
		";
		
		// Исключение пользователей из exclude_users
		if ( ! empty( $exclude_users ) && is_array( $exclude_users ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude_users ), '%s' ) );
			$query        .= " AND dispatcher_meta.meta_value NOT IN ($placeholders)";
		}
		
		$query .= " GROUP BY dispatcher_meta.meta_value
			ORDER BY total_profit DESC";
		
		// Подготовка запроса с исключенными пользователями
		$query = $wpdb->prepare( $query, ...$exclude_users );
		
		// Выполняем запрос и получаем результат
		$dispatcher_stats = $wpdb->get_results( $query, ARRAY_A );
		
		// Если есть результаты, обработаем их
		if ( is_array( $dispatcher_stats ) && ! empty( $dispatcher_stats ) ) {
			$result = [];
			
			foreach ( $dispatcher_stats as $key => $disp ) {
				// Получаем полное имя диспетчера по dispatcher_initials
				$names          = $this->get_user_full_name_by_id( $disp[ 'dispatcher_initials' ] );
				$color_initials = $disp[ 'dispatcher_initials' ]
					? get_field( 'initials_color', 'user_' . $disp[ 'dispatcher_initials' ] ) : '#030303';
				
				$result[] = [
					'color'        => $color_initials,
					'id'           => $disp[ 'dispatcher_initials' ],
					'initials'     => $names[ 'initials' ],
					'name'         => $names[ 'full_name' ],
					'post_count'   => $disp[ 'post_count' ],
					'total_profit' => $disp[ 'total_profit' ],
				];
			}
			
			return $result;
		}
		
		return []; // Возвращаем пустой массив, если данных нет
	}
	
	
	public function get_dispatcher_statistics_with_status( $user_id = null, $load_status = 'cancelled' ) {
		global $wpdb;
		
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		// Основная часть запроса
		$query = "
        SELECT
            dispatcher_meta.meta_value AS dispatcher_initials,
            COUNT(DISTINCT reports.id) AS post_count
        FROM {$table_reports} reports
        INNER JOIN {$table_meta} dispatcher_meta
            ON reports.id = dispatcher_meta.post_id
        INNER JOIN {$table_meta} load_status_meta
            ON reports.id = load_status_meta.post_id
        WHERE dispatcher_meta.meta_key = 'dispatcher_initials'
        AND load_status_meta.meta_key = 'load_status'
        AND load_status_meta.meta_value = %s
        AND reports.status_post = 'publish'
    ";
		
		// Если указан user_id, добавляем условие фильтрации
		if ( $user_id ) {
			if ( is_array( $user_id ) ) {
				// Создаем плейсхолдеры для массива
				$placeholders = implode( ',', array_fill( 0, count( $user_id ), '%s' ) );
				// Добавляем условие IN с плейсхолдерами
				$query .= " AND dispatcher_meta.meta_value IN ($placeholders)";
				
				// Объединяем массив значений для $user_id с другими параметрами
				$query = $wpdb->prepare( $query, array_merge( [ $load_status ], $user_id ) );
			} else {
				$query .= " AND dispatcher_meta.meta_value = %s";
				$query = $wpdb->prepare( $query, $load_status, $user_id );
			}
		} else {
			$query = $wpdb->prepare( $query, $load_status );
		}
		
		// Группировка по диспетчерам
		$query .= " GROUP BY dispatcher_meta.meta_value";
		
		// Выполняем запрос и возвращаем результаты
		return $wpdb->get_results( $query, ARRAY_A );
	}
	
	
	public function get_monthly_fuctoring_stats( $year, $month, $office = 'all' ) {
		global $wpdb;
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$sql = "
		    SELECT
		        COUNT(DISTINCT reports.id) AS post_count,
		        SUM(CAST(IFNULL(booked_rate.meta_value, 0) AS DECIMAL(10,2))) AS total_booked_rate,
		        SUM(CAST(IFNULL(profit.meta_value, 0) AS DECIMAL(10,2))) AS total_profit,
		        SUM(CAST(IFNULL(driver_rate.meta_value, 0) AS DECIMAL(10,2))) AS total_driver_rate,
		        SUM(CAST(IFNULL(second_driver_rate.meta_value, 0) AS DECIMAL(10,2))) AS total_second_driver_rate,
		        SUM(CAST(IFNULL(true_profit.meta_value, 0) AS DECIMAL(10,2))) AS total_true_profit,
		        SUM(CAST(IFNULL(processing_fees.meta_value, 0) AS DECIMAL(10,2))) AS total_processing_fees,
		        SUM(CAST(IFNULL(percent_quick_pay_value.meta_value, 0) AS DECIMAL(10,2))) AS total_percent_quick_pay_value,
		        SUM(CAST(IFNULL(quick_pay_driver_amount.meta_value, 0) AS DECIMAL(10,2))) AS total_quick_pay_driver_amount,
		        SUM(CAST(IFNULL(booked_rate_modify.meta_value, 0) AS DECIMAL(10,2))) AS total_booked_rate_modify
		    FROM $table_reports reports
		    LEFT JOIN $table_meta profit ON reports.id = profit.post_id AND profit.meta_key = 'profit'
		    LEFT JOIN $table_meta booked_rate ON reports.id = booked_rate.post_id AND booked_rate.meta_key = 'booked_rate'
		    LEFT JOIN $table_meta driver_rate ON reports.id = driver_rate.post_id AND driver_rate.meta_key = 'driver_rate'
		    LEFT JOIN $table_meta second_driver_rate ON reports.id = second_driver_rate.post_id AND second_driver_rate.meta_key = 'second_driver_rate'
		    LEFT JOIN $table_meta quick_pay_driver_amount ON reports.id = quick_pay_driver_amount.post_id AND quick_pay_driver_amount.meta_key = 'quick_pay_driver_amount'
		    LEFT JOIN $table_meta percent_quick_pay_value ON reports.id = percent_quick_pay_value.post_id AND percent_quick_pay_value.meta_key = 'percent_quick_pay_value'
		    LEFT JOIN $table_meta processing_fees ON reports.id = processing_fees.post_id AND processing_fees.meta_key = 'processing_fees'
		    LEFT JOIN $table_meta true_profit ON reports.id = true_profit.post_id AND true_profit.meta_key = 'true_profit'
		    LEFT JOIN $table_meta booked_rate_modify ON reports.id = booked_rate_modify.post_id AND booked_rate_modify.meta_key = 'booked_rate_modify'
		     LEFT JOIN $table_meta office_dispatcher ON reports.id = office_dispatcher.post_id AND office_dispatcher.meta_key = 'office_dispatcher'
		";
		
		
		if ( $year === 'all' || $month === 'all' ) {
			$sql .= "WHERE reports.status_post = 'publish'";
		} else {
			$sql .= "WHERE YEAR(reports.date_booked) = %d
		      AND MONTH(reports.date_booked) = %d
		      AND reports.status_post = 'publish'";
		}
		
		if ( $office !== 'all' ) {
			$sql .= " AND office_dispatcher.meta_value = %s";
		}
		
		// Prepare query with necessary joins for each meta field
		$query = $wpdb->prepare( $sql, $year, $month, $office );
		// Execute the query
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Initialize the result array
		$monthly_stats = [
			'month'                         => $month,
			'post_count'                    => 0,
			'total_booked_rate'             => 0.00,
			'total_profit'                  => 0.00,
			'total_driver_rate'             => 0.00,
			'total_second_driver_rate'      => 0.00,
			'total_true_profit'             => 0.00,
			'total_booked_rate_modify'      => 0.00,
			'total_processing_fees'         => 0.00,
			'percent_quick_pay_value'       => 0.00,
			'total_quick_pay_driver_amount' => 0.00,
		];
		
		// Populate the result with actual data if available
		if ( ! empty( $results ) && isset( $results[ 0 ] ) ) {
			$monthly_stats[ 'post_count' ]                    = $results[ 0 ][ 'post_count' ] ?? 0;
			$monthly_stats[ 'total_booked_rate' ]             = $results[ 0 ][ 'total_booked_rate' ] ?? 0.00;
			$monthly_stats[ 'total_profit' ]                  = $results[ 0 ][ 'total_profit' ] ?? 0.00;
			$monthly_stats[ 'total_driver_rate' ]             = $results[ 0 ][ 'total_driver_rate' ] ?? 0.00;
			$monthly_stats[ 'total_second_driver_rate' ]      = $results[ 0 ][ 'total_second_driver_rate' ] ?? 0.00;
			$monthly_stats[ 'total_true_profit' ]             = $results[ 0 ][ 'total_true_profit' ] ?? 0.00;
			$monthly_stats[ 'total_booked_rate_modify' ]      = $results[ 0 ][ 'total_booked_rate_modify' ] ?? 0.00;
			$monthly_stats[ 'total_processing_fees' ]         = $results[ 0 ][ 'total_processing_fees' ] ?? 0.00;
			$monthly_stats[ 'percent_quick_pay_value' ]       = $results[ 0 ][ 'percent_quick_pay_value' ] ?? 0.00;
			$monthly_stats[ 'total_quick_pay_driver_amount' ] = $results[ 0 ][ 'total_quick_pay_driver_amount' ] ?? 0.00;
		}
		
		// Return the monthly statistics
		return $monthly_stats;
	}
	
}