<?php

class  TMSStatistics extends TMSReportsHelper {
	public $table_main     = '';
	public $table_meta     = '';
	public $table_main_flt = '';
	public $table_meta_flt = '';
	
	public function __construct() {
		$user_id       = get_current_user_id();
		$curent_tables = get_field( 'current_select', 'user_' . $user_id );
		if ( $curent_tables ) {
			$this->table_main     = 'reports_' . strtolower( $curent_tables );
			$this->table_meta     = 'reportsmeta_' . strtolower( $curent_tables );
			$this->table_main_flt = 'reports_flt_' . strtolower( $curent_tables );
			$this->table_meta_flt = 'reportsmeta_flt_' . strtolower( $curent_tables );
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
	
	public function get_sources_statistics(
		$year_param, $month_param, $office_dispatcher = 'all', $dispatcher_initials = 'all'
	) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$statistics = [];
		
		foreach ( $this->sources as $key => $label ) {
			$sql = "
			SELECT
				COUNT(DISTINCT reports.id) as count,
				SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) as profit
			FROM {$table_meta} source_meta
			INNER JOIN {$table_main} reports
				ON source_meta.post_id = reports.id
			INNER JOIN {$table_meta} profit_meta
				ON reports.id = profit_meta.post_id AND profit_meta.meta_key = 'profit'
			LEFT JOIN {$table_meta} office_dispatcher
				ON reports.id = office_dispatcher.post_id AND office_dispatcher.meta_key = 'office_dispatcher'
			LEFT JOIN {$table_meta} dispatcher_initials
				ON reports.id = dispatcher_initials.post_id AND dispatcher_initials.meta_key = 'dispatcher_initials'
			LEFT JOIN {$table_meta} load_status
				ON reports.id = load_status.post_id AND load_status.meta_key = 'load_status'
			LEFT JOIN {$table_meta} tbd
				ON reports.id = tbd.post_id AND tbd.meta_key = 'tbd'
			WHERE source_meta.meta_key = 'source'
				AND source_meta.meta_value = %s
				AND reports.status_post = 'publish'
				AND (tbd.meta_value IS NULL OR tbd.meta_value = '')
				AND load_status.meta_value NOT IN ('cancelled', 'waiting-on-rc')
		";
			
			$params = [ $key ];
			
			if ( $dispatcher_initials !== 'all' ) {
				$sql      .= " AND dispatcher_initials.meta_value = %s";
				$params[] = $dispatcher_initials;
			}
			
			if ( $office_dispatcher !== 'all' ) {
				$sql      .= " AND office_dispatcher.meta_value = %s";
				$params[] = $office_dispatcher;
			}
			
			if ( $year_param !== 'all' && $month_param !== 'all' ) {
				$sql      .= " AND YEAR(reports.date_booked) = %d AND MONTH(reports.date_booked) = %d";
				$params[] = (int) $year_param;
				$params[] = (int) $month_param;
			}
			
			$query  = $wpdb->prepare( $sql, ...$params );
			$result = $wpdb->get_row( $query );
			
			$statistics[ $key ] = [
				'dispatcher'   => $dispatcher_initials ?? '',
				'label'        => $label,
				'post_count'   => (int) ( $result->count ?? 0 ),
				'total_profit' => isset( $result->profit ) ? number_format( $result->profit, 2 ) : '0.00',
			];
		}
		
		return json_encode( $statistics );
	}
	
	
	public function get_dispatcher_statistics( $office_dispatcher_selected, $project ) {
		global $wpdb;
		$table_reports = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		
		$sql = "
		    SELECT
		        dispatcher_meta.meta_value AS dispatcher_initials,
		        COUNT(reports.id) AS post_count,
		        SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit,
		        AVG(CASE WHEN load_status.meta_value != 'tonu' THEN CAST(profit_meta.meta_value AS DECIMAL(10,2)) END) AS average_profit
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
			LEFT JOIN $table_meta AS tbd
				ON reports.id = tbd.post_id
				AND tbd.meta_key = 'tbd'
		    WHERE
		       load_status.meta_value NOT IN ( 'cancelled', 'waiting-on-rc' ) AND (tbd.meta_value IS NULL OR tbd.meta_value = '')
		      AND reports.status_post = 'publish'
		    GROUP BY dispatcher_meta.meta_value
		";
		
		// Выполняем запрос и получаем результаты
		$dispatcher_stats = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $dispatcher_stats ) ) {
			foreach ( $dispatcher_stats as $key => $disp ) {
				$names             = $this->get_user_full_name_by_id( $disp[ 'dispatcher_initials' ] );
				$office_dispatcher = get_field( 'work_location', 'user_' . $disp[ 'dispatcher_initials' ] );
				$access            = get_field( 'permission_view', 'user_' . $disp[ 'dispatcher_initials' ] );
				
				if ( ! in_array( $project, $access ) ) {
					unset( $dispatcher_stats[ $key ] );
				}
				
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
	
	public function get_profit_by_office_stats( $office ) {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$params = array();
		
		$query = "
		SELECT
			DATE(main.date_booked) AS date,
			SUM(CAST(profit.meta_value AS DECIMAL(10,2))) AS total_profit,
			MAX(CAST(profit.meta_value AS DECIMAL(10,2))) AS max_profit
		FROM $table_main AS main
		LEFT JOIN $table_meta AS profit ON main.id = profit.post_id AND profit.meta_key = 'profit'
		LEFT JOIN $table_meta AS office_meta ON main.id = office_meta.post_id AND office_meta.meta_key = 'office_dispatcher'
		INNER JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
		WHERE main.status_post = 'publish'
			AND load_status.meta_value NOT IN ('waiting-on-rc', 'cancelled')
	";
		
		if ( ! empty( $office ) && $office !== 'all' ) {
			$query    .= " AND office_meta.meta_value = %s ";
			$params[] = $office;
		}
		
		$query .= "
		GROUP BY DATE(main.date_booked)
		ORDER BY total_profit DESC
		LIMIT 1
	";
		
		$query  = $wpdb->prepare( $query, ...$params );
		$result = $wpdb->get_row( $query, ARRAY_A );
		
		if ( ! $result ) {
			return array(
				'date'    => null,
				'date_us' => null,
				'total'   => 0,
				'max'     => 0,
			);
		}
		
		return array(
			'date'    => $result[ 'date' ], // исходная дата
			'date_us' => date( 'm/d/Y', strtotime( $result[ 'date' ] ) ), // форматированная
			'total'   => (float) $result[ 'total_profit' ],
			'max'     => (float) $result[ 'max_profit' ],
		);
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
	     	INNER JOIN $table_meta AS load_status
		        ON reports.id = load_status.post_id
		        AND load_status.meta_key = 'load_status'
			LEFT JOIN $table_meta AS tbd
				ON reports.id = tbd.post_id
				AND tbd.meta_key = 'tbd'
			WHERE load_status.meta_value NOT IN ( 'cancelled', 'waiting-on-rc' ) AND (tbd.meta_value IS NULL OR tbd.meta_value = '') AND reports.status_post = 'publish'
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
		        AVG(CASE WHEN load_status.meta_value != 'tonu' THEN CAST(profit.meta_value AS DECIMAL(10,2)) END) AS average_profit
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
	        LEFT JOIN $table_meta AS tbd
				ON reports.id = tbd.post_id
				AND tbd.meta_key = 'tbd'
		    WHERE meta.meta_value = %s
		      AND load_status.meta_value NOT IN ( 'cancelled', 'waiting-on-rc' ) AND (tbd.meta_value IS NULL OR tbd.meta_value = '')
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

		
		// if (current_user_can('administrator')) {
		// 	$current_month = '9';
		// }

		// Основная часть запроса
		$query = "
        SELECT
            dispatcher_meta.meta_value AS dispatcher_initials,
            COUNT(reports.id) AS post_count,
            SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit,
            AVG(CASE WHEN load_status.meta_value != 'tonu' THEN CAST(profit_meta.meta_value AS DECIMAL(10,2)) END) AS average_profit
        FROM {$table_reports} reports
        INNER JOIN {$table_meta} dispatcher_meta
            ON reports.id = dispatcher_meta.post_id
		    AND dispatcher_meta.meta_key = 'dispatcher_initials'
        INNER JOIN {$table_meta} profit_meta
            ON reports.id = profit_meta.post_id
        INNER JOIN {$table_meta} load_status
            ON reports.id = load_status.post_id
        LEFT JOIN $table_meta AS tbd
				ON reports.id = tbd.post_id
				AND tbd.meta_key = 'tbd'
        WHERE profit_meta.meta_key = 'profit'
        AND reports.status_post = 'publish'
                  AND load_status.meta_key = 'load_status'
	          AND load_status.meta_value NOT IN ( 'cancelled', 'waiting-on-rc' ) AND (tbd.meta_value IS NULL OR tbd.meta_value = '')
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
				
				// Convert string values to numbers for proper calculations
				$dispatcher_stats[ $key ][ 'post_count' ] = (int) $disp[ 'post_count' ];
				$dispatcher_stats[ $key ][ 'total_profit' ] = (float) $disp[ 'total_profit' ];
				$dispatcher_stats[ $key ][ 'average_profit' ] = (float) $disp[ 'average_profit' ];
				$dispatcher_stats[ $key ][ 'goal' ] = (float) $goal;
			}
		}
		
		// Add team members who haven't made any loads yet but have goals set
		if ( ! empty( $user_id ) && is_array( $user_id ) ) {
			// Get all team members from the user's team
			$my_team = get_field( 'my_team', 'user_' . $user_id[0] );
			if ( is_array( $my_team ) ) {
				// Get existing dispatcher IDs from stats
				$existing_dispatcher_ids = array();
				foreach ( $dispatcher_stats as $disp ) {
					$existing_dispatcher_ids[] = $disp[ 'dispatcher_initials' ];
				}
				
				// Add missing team members
				foreach ( $my_team as $team_member_id ) {
					$names = $this->get_user_full_name_by_id( $team_member_id );
					if ( $names && ! in_array( $names[ 'full_name' ], $existing_dispatcher_ids ) ) {
						$goal = get_field( 'monthly_goal', 'user_' . $team_member_id );
						if ( $goal && $goal > 0 ) {
							$dispatcher_stats[] = array(
								'dispatcher_initials' => $names[ 'full_name' ],
								'post_count' => (int) 0,
								'total_profit' => (float) 0,
								'average_profit' => (float) 0,
								'goal' => (float) $goal
							);
						}
					}
				}
			}
		}

		// if (current_user_can('administrator')) {
		// 	// Get all report IDs that match the query conditions
		// 	$report_ids_query = "
		// 		SELECT DISTINCT reports.id
		// 		FROM {$table_reports} reports
		// 		INNER JOIN {$table_meta} dispatcher_meta
		// 			ON reports.id = dispatcher_meta.post_id
		// 			AND dispatcher_meta.meta_key = 'dispatcher_initials'
		// 		INNER JOIN {$table_meta} profit_meta
		// 			ON reports.id = profit_meta.post_id
		// 		INNER JOIN {$table_meta} load_status
		// 			ON reports.id = load_status.post_id
		// 		LEFT JOIN $table_meta AS tbd
		// 			ON reports.id = tbd.post_id
		// 			AND tbd.meta_key = 'tbd'
		// 		WHERE profit_meta.meta_key = 'profit'
		// 		AND reports.status_post = 'publish'
		// 		AND load_status.meta_key = 'load_status'
		// 		AND load_status.meta_value NOT IN ( 'cancelled', 'waiting-on-rc' ) 
		// 		AND (tbd.meta_value IS NULL OR tbd.meta_value = '')
		// 		AND YEAR(reports.date_booked) = %d
		// 		AND MONTH(reports.date_booked) = %d
		// 	";
			
		// 	// Add user filter if specified
		// 	if ( $user_id ) {
		// 		if ( is_array( $user_id ) ) {
		// 			$placeholders = implode( ',', array_fill( 0, count( $user_id ), '%s' ) );
		// 			$report_ids_query .= " AND dispatcher_meta.meta_value IN ($placeholders)";
		// 			$report_ids_query = $wpdb->prepare( $report_ids_query, array_merge( [ $current_year, $current_month ], $user_id ) );
		// 		} else {
		// 			$report_ids_query .= " AND dispatcher_meta.meta_value = %s";
		// 			$report_ids_query = $wpdb->prepare( $report_ids_query, $current_year, $current_month, $user_id );
		// 		}
		// 	} else {
		// 		$report_ids_query = $wpdb->prepare( $report_ids_query, $current_year, $current_month );
		// 	}
			
		// 	$report_ids = $wpdb->get_col( $report_ids_query );
			
		// 	echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
		// 	echo '<strong>Report IDs in this query:</strong><br>';
		// 	echo implode(', ', $report_ids);
		// 	echo '<br><strong>Total count:</strong> ' . count($report_ids);
		// 	echo '</div>';
			
		// }
		
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
			LEFT JOIN $table_meta AS tbd
				ON reports.id = tbd.post_id
				AND tbd.meta_key = 'tbd'
			WHERE dispatcher_meta.meta_key = 'dispatcher_initials'
			  AND load_status.meta_key = 'load_status'
			  AND load_status.meta_value NOT IN ( 'cancelled', 'waiting-on-rc' ) AND (tbd.meta_value IS NULL OR tbd.meta_value = '')
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
	
	public function get_top_10_customers( $year, $month, $office = 'all', $is_flt = false ) {
		global $wpdb;
		$table_reports = $wpdb->prefix . ( $is_flt ? $this->table_main_flt : $this->table_main );
		$table_meta    = $wpdb->prefix . ( $is_flt ? $this->table_meta_flt : $this->table_meta );
		
		// Базовый SQL
		$sql = "
		    SELECT
		        customer.meta_value AS customer_id,
		        SUM(CAST(profit.meta_value AS DECIMAL(10,2))) AS total_profit,
		        COUNT(DISTINCT reports.id) AS post_count
		    FROM {$table_reports} AS reports
		    LEFT JOIN {$table_meta} AS profit
		        ON profit.post_id = reports.id AND profit.meta_key = 'profit'
		    LEFT JOIN {$table_meta} AS customer
		        ON customer.post_id = reports.id AND customer.meta_key = 'customer_id'
		    LEFT JOIN $table_meta AS office_dispatcher
  				ON office_dispatcher.post_id = reports.id
	  			AND office_dispatcher.meta_key = 'office_dispatcher'
		";
		
		
		// Собираем условия
		$where = [ "reports.status_post = 'publish'" ];
		
		if ( $year !== 'all' && $month !== 'all' ) {
			$where[] = $wpdb->prepare( "YEAR(reports.date_booked) = %d", $year );
			$where[] = $wpdb->prepare( "MONTH(reports.date_booked) = %d", $month );
		}
		
		if ( $office !== 'all' ) {
			$where[] = $wpdb->prepare( "office_dispatcher.meta_value = %s", $office );
		}
		
		// Добавляем WHERE в запрос
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}
		
		$sql .= "
		    GROUP BY customer.meta_value
		    ORDER BY total_profit DESC
		    LIMIT 10
		";
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		return $results;
	}
	
	public function get_monthly_fuctoring_stats( $year, $month, $office = 'all', $is_flt = false ) {
		global $wpdb;
		$table_reports = $wpdb->prefix . ( $is_flt ? $this->table_main_flt : $this->table_main );
		$table_meta    = $wpdb->prefix . ( $is_flt ? $this->table_meta_flt : $this->table_meta );
		
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
		        SUM(CAST(IFNULL(booked_rate_modify.meta_value, 0) AS DECIMAL(10,2))) AS total_booked_rate_modify,
		        SUM(CAST(IFNULL(percent_booked_rate.meta_value, 0) AS DECIMAL(10,2))) AS total_percent_booked_rate,
		        SUM(CASE WHEN factoring_status.meta_value IN ('processed', 'paid') THEN CAST(IFNULL(booked_rate.meta_value, 0) AS DECIMAL(10,2)) ELSE 0 END) AS total_processed_invoices,
		        SUM(CASE WHEN driver_pay_statuses.meta_value = 'paid' THEN CAST(IFNULL(driver_rate.meta_value, 0) AS DECIMAL(10,2)) ELSE 0 END) AS total_paid_loads
		    FROM $table_reports reports
		    LEFT JOIN $table_meta profit ON reports.id = profit.post_id AND profit.meta_key = 'profit'
		    LEFT JOIN $table_meta percent_booked_rate ON reports.id = percent_booked_rate.post_id AND percent_booked_rate.meta_key = 'percent_booked_rate'
		    LEFT JOIN $table_meta booked_rate ON reports.id = booked_rate.post_id AND booked_rate.meta_key = 'booked_rate'
		    LEFT JOIN $table_meta driver_rate ON reports.id = driver_rate.post_id AND driver_rate.meta_key = 'driver_rate'
		    LEFT JOIN $table_meta second_driver_rate ON reports.id = second_driver_rate.post_id AND second_driver_rate.meta_key = 'second_driver_rate'
		    LEFT JOIN $table_meta quick_pay_driver_amount ON reports.id = quick_pay_driver_amount.post_id AND quick_pay_driver_amount.meta_key = 'quick_pay_driver_amount'
		    LEFT JOIN $table_meta percent_quick_pay_value ON reports.id = percent_quick_pay_value.post_id AND percent_quick_pay_value.meta_key = 'percent_quick_pay_value'
		    LEFT JOIN $table_meta processing_fees ON reports.id = processing_fees.post_id AND processing_fees.meta_key = 'processing_fees'
		    LEFT JOIN $table_meta true_profit ON reports.id = true_profit.post_id AND true_profit.meta_key = 'true_profit'
		    LEFT JOIN $table_meta booked_rate_modify ON reports.id = booked_rate_modify.post_id AND booked_rate_modify.meta_key = 'booked_rate_modify'
		    LEFT JOIN $table_meta office_dispatcher ON reports.id = office_dispatcher.post_id AND office_dispatcher.meta_key = 'office_dispatcher'
		    LEFT JOIN $table_meta factoring_status ON reports.id = factoring_status.post_id AND factoring_status.meta_key = 'factoring_status'
		    LEFT JOIN $table_meta driver_pay_statuses ON reports.id = driver_pay_statuses.post_id AND driver_pay_statuses.meta_key = 'driver_pay_statuses'
		    LEFT JOIN $table_meta load_status ON reports.id = load_status.post_id AND load_status.meta_key = 'load_status'
		    LEFT JOIN $table_meta tbd ON reports.id = tbd.post_id AND tbd.meta_key = 'tbd'
		";
		
		
		if ( $year === 'all' || $month === 'all' ) {
			$sql .= "WHERE reports.status_post = 'publish'";
		} else {
			$sql .= "WHERE YEAR(reports.date_booked) = %d
		      AND MONTH(reports.date_booked) = %d
		      AND reports.status_post = 'publish'";
		}
		
		// Exclude cancelled and waiting-on-rc statuses
		$sql .= " AND (load_status.meta_value IS NULL OR load_status.meta_value NOT IN ('cancelled', 'waiting-on-rc'))";
		
		// Exclude loads with TBD (To Be Determined) field filled
		$sql .= " AND (tbd.meta_value IS NULL OR tbd.meta_value = '')";
		
		if ( $office !== 'all' ) {
			$sql .= " AND office_dispatcher.meta_value = %s";
		}
		
		// Prepare query with necessary joins for each meta field
		$query = $wpdb->prepare( $sql, $year, $month, $office );
		// Execute the query
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Show report IDs for administrators
		// if (current_user_can('administrator')) {

		// 	$exclude_ids = array(9053, 9056, 9057, 9130, 9132, 9133, 9135, 9137, 9138, 9139, 9141, 9142, 9143, 9144, 9145, 9146, 9148, 9149, 9150, 9151, 9152, 9154, 9157, 9158, 9159, 9160, 9161, 9162, 9163, 9165, 9167, 9169, 9172, 9174, 9175, 9177, 9178, 9180, 9181, 9182, 9186, 9187, 9188, 9190, 9191, 9192, 9196, 9199, 9200, 9201, 9202, 9203, 9204, 9205, 9206, 9207, 9208, 9209, 9211, 9214, 9215, 9218, 9219, 9220, 9221, 9223, 9226, 9229, 9231, 9232, 9233, 9234, 9235, 9236, 9237, 9238, 9239, 9241, 9242, 9244, 9245, 9246, 9247, 9249, 9251, 9253, 9254, 9256, 9257, 9258, 9259, 9260, 9261, 9262, 9263, 9264, 9265, 9266, 9267, 9268, 9269, 9271, 9272, 9273, 9277, 9278, 9279, 9280, 9281, 9283, 9284, 9285, 9287, 9288, 9289, 9290, 9291, 9299, 9300, 9301, 9302, 9303, 9304, 9305, 9307, 9308, 9309, 9312, 9315, 9316, 9317, 9318, 9319, 9320, 9322, 9324, 9326, 9329, 9330, 9331, 9336, 9337, 9339, 9341, 9342, 9343, 9344, 9346, 9347, 9348, 9349, 9350, 9355, 9356, 9357, 9358, 9359, 9360, 9361, 9363, 9364, 9368, 9369, 9370, 9371, 9372, 9373, 9374, 9375, 9376, 9378, 9379, 9380, 9381, 9382, 9383, 9384, 9385, 9386, 9391, 9394, 9395, 9397, 9399, 9400, 9403, 9404, 9405, 9409, 9410, 9413, 9415, 9416, 9417, 9420, 9421, 9422, 9423, 9426, 9428, 9429, 9431, 9432, 9435, 9436, 9438, 9439, 9440, 9442, 9444, 9445, 9446, 9448, 9449, 9450, 9451, 9453, 9454, 9457, 9458, 9459, 9460, 9461, 9463, 9464, 9467, 9468, 9469, 9470, 9471, 9472, 9473, 9476, 9477, 9478, 9481, 9482, 9483, 9485, 9486, 9488, 9490, 9491, 9492, 9493, 9496, 9497, 9501, 9502, 9506, 9509, 9510, 9511, 9512, 9513, 9514, 9515, 9517, 9521, 9524, 9526, 9527, 9528, 9529, 9530, 9532, 9534, 9536, 9537, 9540, 9541, 9542, 9544, 9545, 9546, 9547, 9548, 9549, 9550, 9552, 9556, 9557, 9559, 9562, 9563, 9564, 9567, 9568, 9569, 9572, 9573, 9575, 9576, 9577, 9578, 9579, 9580, 9581, 9582, 9583, 9585, 9586, 9589, 9590, 9592, 9594, 9596, 9597, 9599, 9605, 9606, 9607, 9609, 9610, 9612, 9613, 9614, 9615, 9617, 9618, 9619, 9620, 9621, 9623, 9625, 9626, 9627, 9628, 9629, 9631, 9632, 9633, 9634, 9635, 9636, 9637, 9638, 9639, 9641, 9642, 9644, 9646, 9647, 9648, 9649, 9650, 9651, 9655, 9659, 9660, 9661, 9662, 9663, 9664, 9665, 9666, 9667, 9669, 9675, 9676, 9677, 9678, 9680, 9682, 9683, 9684, 9685, 9688, 9690, 9691, 9692, 9693, 9694, 9697, 9699, 9702, 9703, 9707, 9710, 9711, 9712, 9713, 9716, 9719, 9720, 9721, 9729, 9731, 9732, 9734, 9735, 9736, 9737, 9739, 9740, 9741, 9743, 9744, 9745, 9747, 9749, 9750, 9751, 9753, 9754, 9756, 9758, 9759, 9760, 9761, 9762, 9763, 9764, 9765, 9766, 9767, 9771, 9773, 9774, 9775, 9776, 9777, 9778, 9779, 9781, 9782, 9783, 9784, 9785, 9786, 9788, 9789, 9790, 9792, 9793, 9794, 9795, 9797, 9798, 9799, 9800, 9801, 9802, 9806, 9807, 9809, 9810, 9811, 9812, 9813, 9814, 9816, 9817, 9818, 9819, 9822, 9823, 9824, 9825, 9826, 9827, 9828, 9830, 9831, 9832, 9833, 9834, 9836, 9837, 9838, 9840, 9842, 9844, 9845, 9846, 9847, 9848, 9849, 9851, 9854, 9855, 9856, 9857, 9858, 9859, 9868, 9869, 9870, 9871, 9876, 9878, 9880, 9881, 9882, 9883, 9884, 9885, 9887, 9888, 9889, 9892, 9893, 9894, 9896, 9898, 9899, 9900, 9906, 9907, 9908, 9910, 9912, 9913, 9914, 9916, 9919, 9922, 9924, 9925, 9926, 9928, 9929, 9931, 9932, 9933, 9934, 9935, 9936, 9937, 9938, 9939, 9941, 9943, 9944, 9945, 9946, 9947, 9954, 9955, 9957, 9958, 9962, 9964, 9965, 9966, 9968, 9970, 9972, 9973, 9974, 9975, 9976, 9977, 9979, 9981, 9982, 9983, 9984, 9986, 9988, 9991, 9997, 9999, 10000, 10001, 10003, 10007, 10008, 10010, 10011, 10012, 10013, 10014, 10015, 10019, 10020, 10021, 10024, 10025, 10026, 10027, 10028, 10029, 10030, 10031, 10035, 10041, 10043, 10045, 10046, 10047, 10050, 10053, 10054, 10060, 10061, 10062, 10063, 10064, 10066, 10071, 10073, 10074, 10075, 10076, 10077, 10078, 10079, 10080, 10081, 10082, 10083, 10087, 10088, 10090, 10091, 10093, 10095, 10096, 10097, 10098, 10099, 10103, 10104, 10105, 10106, 10108, 10110, 10111, 10112, 10113, 10114, 10116, 10117, 10118, 10119, 10120, 10121, 10122, 10123, 10124, 10126, 10127, 10129, 10132, 10133, 10134, 10135, 10137, 10138, 10139, 10140, 10142, 10143, 10145, 10146, 10147, 10151, 10153, 10154, 10155, 10157, 10159, 10163, 10168, 10169, 10170, 10172, 10175, 10177, 10178, 10181, 10183, 10184, 10185, 10186, 10187, 10188, 10189, 10190, 10191, 10192, 10194, 10196, 10197, 10198, 10200, 10201, 10203, 10209, 10211, 10212, 10213, 10216, 10217, 10218, 10219, 10221, 10222, 10224, 10229, 10230, 10231, 10232, 10233, 10237, 10238, 10240, 10245, 10247, 10249, 10250, 10252, 10253, 10254, 10255, 10257, 10258, 10260, 10263, 10264, 10265, 10266, 10267, 10269, 10270, 10272, 10273, 10274, 10275, 10276, 10277, 10278, 10279, 10280, 10281, 10283, 10286, 10289, 10293, 10295, 10297, 10298, 10299, 10300, 10301, 10302, 10303, 10310, 10315, 10316, 10317, 10318, 10319, 10320, 10322, 10324, 10325, 10326, 10329, 10331, 10333, 10334, 10335, 10341, 10342, 10343, 10344, 10345, 10346, 10347, 10349, 10350, 10351, 10352, 10353, 10355, 10356, 10357, 10358, 10359, 10360, 10361, 10362, 10363, 10365, 10368, 10369, 10370, 10371, 10373, 10376, 10377, 10378, 10380, 10386, 10387, 10391, 10392, 10393, 10396, 10397, 10398, 10400, 10401, 10403, 10404, 10406, 10409, 10412, 10413, 10414, 10416, 10418, 10419, 10420, 10421, 10422, 10424, 10426, 10428, 10433, 10434, 10435, 10441, 10442, 10443, 10444, 10445, 10446, 10447, 10448, 10451, 10454, 10455, 10456, 10460, 10462, 10463, 10464, 10465, 10466, 10467, 10468, 10469, 10470, 10471, 10472, 10474, 10475, 10476, 10477, 10478, 10479, 10483, 10484, 10485, 10486, 10487, 10489, 10490, 10491, 10496, 10498, 10500, 10501, 10502, 10503, 10504, 10505, 10506, 10507, 10508, 10509, 10510, 10512, 10514, 10515, 10516, 10517, 10518, 10519, 10522, 10523, 10524, 10525, 10527, 10529, 10530, 10531, 10533, 10534, 10535, 10536, 10538, 10540, 10542, 10546, 10548, 10550, 10551, 10552, 10553, 10561, 10562, 10563);

		// 	// Get all report IDs that match the query conditions
		// 	$report_ids_sql = "
		// 		SELECT DISTINCT reports.id
		// 		FROM $table_reports reports
		// 		LEFT JOIN $table_meta profit ON reports.id = profit.post_id AND profit.meta_key = 'profit'
		// 		LEFT JOIN $table_meta percent_booked_rate ON reports.id = percent_booked_rate.post_id AND percent_booked_rate.meta_key = 'percent_booked_rate'
		// 		LEFT JOIN $table_meta booked_rate ON reports.id = booked_rate.post_id AND booked_rate.meta_key = 'booked_rate'
		// 		LEFT JOIN $table_meta driver_rate ON reports.id = driver_rate.post_id AND driver_rate.meta_key = 'driver_rate'
		// 		LEFT JOIN $table_meta second_driver_rate ON reports.id = second_driver_rate.post_id AND second_driver_rate.meta_key = 'second_driver_rate'
		// 		LEFT JOIN $table_meta quick_pay_driver_amount ON reports.id = quick_pay_driver_amount.post_id AND quick_pay_driver_amount.meta_key = 'quick_pay_driver_amount'
		// 		LEFT JOIN $table_meta percent_quick_pay_value ON reports.id = percent_quick_pay_value.post_id AND percent_quick_pay_value.meta_key = 'percent_quick_pay_value'
		// 		LEFT JOIN $table_meta processing_fees ON reports.id = processing_fees.post_id AND processing_fees.meta_key = 'processing_fees'
		// 		LEFT JOIN $table_meta true_profit ON reports.id = true_profit.post_id AND true_profit.meta_key = 'true_profit'
		// 		LEFT JOIN $table_meta booked_rate_modify ON reports.id = booked_rate_modify.post_id AND booked_rate_modify.meta_key = 'booked_rate_modify'
		// 		LEFT JOIN $table_meta office_dispatcher ON reports.id = office_dispatcher.post_id AND office_dispatcher.meta_key = 'office_dispatcher'
		// 		LEFT JOIN $table_meta factoring_status ON reports.id = factoring_status.post_id AND factoring_status.meta_key = 'factoring_status'
		// 		LEFT JOIN $table_meta driver_pay_statuses ON reports.id = driver_pay_statuses.post_id AND driver_pay_statuses.meta_key = 'driver_pay_statuses'
		// 		LEFT JOIN $table_meta load_status ON reports.id = load_status.post_id AND load_status.meta_key = 'load_status'
		// 		LEFT JOIN $table_meta tbd ON reports.id = tbd.post_id AND tbd.meta_key = 'tbd'
		// 	";
			
		// 	if ( $year === 'all' || $month === 'all' ) {
		// 		$report_ids_sql .= "WHERE reports.status_post = 'publish'";
		// 	} else {
		// 		$report_ids_sql .= "WHERE YEAR(reports.date_booked) = %d
		// 		      AND MONTH(reports.date_booked) = %d
		// 		      AND reports.status_post = 'publish'";
		// 	}
			
		// 	// Exclude cancelled and waiting-on-rc statuses
		// 	$report_ids_sql .= " AND (load_status.meta_value IS NULL OR load_status.meta_value NOT IN ('cancelled', 'waiting-on-rc'))";
			
		// 	// Exclude loads with TBD (To Be Determined) field filled
		// 	$report_ids_sql .= " AND (tbd.meta_value IS NULL OR tbd.meta_value = '')";
			
		// 	if ( $office !== 'all' ) {
		// 		$report_ids_sql .= " AND office_dispatcher.meta_value = %s";
		// 	}
			
		// 	// Add exclusion of specified IDs for administrators
		// 	if ( ! empty( $exclude_ids ) ) {
		// 		$exclude_placeholders = implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) );
		// 		$report_ids_sql .= " AND reports.id NOT IN ($exclude_placeholders)";
		// 	}
			
		// 	// Prepare query with exclude IDs
		// 	if ( ! empty( $exclude_ids ) ) {
		// 		$report_ids_query = $wpdb->prepare( $report_ids_sql, array_merge( [ $year, $month, $office ], $exclude_ids ) );
		// 	} else {
		// 		$report_ids_query = $wpdb->prepare( $report_ids_sql, $year, $month, $office );
		// 	}
			
		// 	$report_ids = $wpdb->get_col( $report_ids_query );
			
		// 	echo '<div style="background: #e8f4fd; padding: 10px; margin: 10px 0; border: 1px solid #2196F3;">';
		// 	echo '<strong>Monthly Factoring Stats - Report IDs (Excluding ' . count($exclude_ids) . ' IDs):</strong><br>';
		// 	echo implode(', ', $report_ids);
		// 	echo '<br><strong>Total count (after exclusion):</strong> ' . count($report_ids);
		// 	echo '<br><strong>Excluded IDs count:</strong> ' . count($exclude_ids);
		// 	echo '<br><strong>Year:</strong> ' . $year . ', <strong>Month:</strong> ' . $month . ', <strong>Office:</strong> ' . $office;
		// 	echo '</div>';
		// }
		
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
			'total_percent_booked_rate'     => 0.00,
			'total_processed_invoices'      => 0.00,
			'total_paid_loads'              => 0.00,
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
			$monthly_stats[ 'total_percent_booked_rate' ]     = $results[ 0 ][ 'total_percent_booked_rate' ] ?? 0.00;
			$monthly_stats[ 'total_processed_invoices' ]      = $results[ 0 ][ 'total_processed_invoices' ] ?? 0.00;
			$monthly_stats[ 'total_paid_loads' ]              = $results[ 0 ][ 'total_paid_loads' ] ?? 0.00;
		}
		
		// Return the monthly statistics
		return $monthly_stats;
	}
	
	/**
	 * Get dispatchers with FLT access filtering
	 *
	 * @param string|null $office_user - office filter
	 * @param bool $is_flt - whether to filter by FLT access
	 *
	 * @return array
	 */
	public function get_dispatchers( $office_user = null, $is_flt = false , $include_expedite_manager = false ) {
		// Аргументы для получения пользователей с ролью 'dispatcher'
		
		$report  = new TMSReports();
		$project = $report->project;

		

		$args = array(
			'role__in' => array( 'dispatcher', 'dispatcher-tl'),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);

		if ( $include_expedite_manager ) {
			$args['role__in'][] = 'expedite_manager';
		}
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$office     = get_field( 'work_location', "user_" . $user->ID );
			$access     = get_field( 'permission_view', 'user_' . $user->ID );
			$flt_access = get_field( 'flt', 'user_' . $user->ID );
			
			// Проверяем доступ к проекту
			if ( ! in_array( $project, $access ) ) {
				continue;
			}
			
			// Фильтруем по FLT доступу
			if ( $is_flt && ! $flt_access ) {
				continue;
			}
			
			// Если не FLT режим, исключаем пользователей с FLT доступом
			if ( ! $is_flt && $flt_access ) {
				continue;
			}
			
			// Фильтруем по офису
			if ( is_null( $office_user ) ) {
				$dispatchers[] = array(
					'id'       => $user->ID,
					'fullname' => trim( $first_name . ' ' . $last_name ),
					'office'   => $office,
				);
			} else {
				if ( $office_user === $office || $office_user === 'all' ) {
					$dispatchers[] = array(
						'id'       => $user->ID,
						'fullname' => trim( $first_name . ' ' . $last_name ),
						'office'   => $office,
					);
				}
			}
		}
		
		
		return $dispatchers;
	}
	
	/**
	 * Get dispatchers TL with FLT access filtering
	 *
	 * @param string|null $office_user - office filter
	 * @param bool $is_flt - whether to filter by FLT access
	 *
	 * @return array
	 */
	public function get_dispatchers_tl( $office_user = null, $is_flt = false ) {
		// Аргументы для получения пользователей с ролью 'dispatcher-tl'
		$args = array(
			'role__in' => array( 'dispatcher-tl' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$office     = get_field( 'work_location', "user_" . $user->ID );
			$flt_access = get_field( 'flt', 'user_' . $user->ID );
			
			// Фильтруем по FLT доступу
			if ( $is_flt && ! $flt_access ) {
				continue;
			}
			
			// Если не FLT режим, исключаем пользователей с FLT доступом
			if ( ! $is_flt && $flt_access ) {
				continue;
			}
			
			// Фильтруем по офису
			if ( is_null( $office_user ) ) {
				$dispatchers[] = array(
					'id'       => $user->ID,
					'fullname' => trim( $first_name . ' ' . $last_name ),
					'office'   => $office,
				);
			} else {
				if ( $office_user === $office ) {
					$dispatchers[] = array(
						'id'       => $user->ID,
						'fullname' => trim( $first_name . ' ' . $last_name ),
						'office'   => $office,
					);
				}
			}
		}
		
		return $dispatchers;
	}

	/**
	 * Get expedite managers with FLT access filtering
	 *
	 * @param string|null $office_user - office filter
	 * @param bool $is_flt - whether to filter by FLT access
	 *
	 * @return array
	 */
	public function get_expedite_managers( $office_user = null, $is_flt = false ) {
		// Аргументы для получения пользователей с ролью 'dispatcher-tl'
		$args = array(
			'role__in' => array( 'expedite_manager' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$office     = get_field( 'work_location', "user_" . $user->ID );
			$flt_access = get_field( 'flt', 'user_' . $user->ID );
			
			// Фильтруем по FLT доступу
			if ( $is_flt && ! $flt_access ) {
				continue;
			}
			
			// Если не FLT режим, исключаем пользователей с FLT доступом
			if ( ! $is_flt && $flt_access ) {
				continue;
			}
			
			// Фильтруем по офису
			if ( is_null( $office_user ) ) {
				$dispatchers[] = array(
					'id'       => $user->ID,
					'fullname' => trim( $first_name . ' ' . $last_name ),
					'office'   => $office,
				);
			} else {
				if ( $office_user === $office ) {
					$dispatchers[] = array(
						'id'       => $user->ID,
						'fullname' => trim( $first_name . ' ' . $last_name ),
						'office'   => $office,
					);
				}
			}
		}
		
		return $dispatchers;
	}
	
}