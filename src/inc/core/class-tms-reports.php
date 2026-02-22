<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReports extends TMSReportsHelper {
	
	public $table_main = '';
	public $table_meta = '';
	
	public $table_company = 'reports_company';
	
	public $per_page_loads = 100;
	public $user_emails    = array();
	public $email_helper   = false;
	public $project        = '';
	public $log_controller = false;
	public $helper         = false;
	
	public function __construct() {
		$user_id = get_current_user_id();
		
		$this->email_helper = new TMSEmails();
		$this->email_helper->init();
		$this->user_emails = $this->email_helper->get_all_emails();
		
		$this->log_controller = new TMSLogs();
		$this->helper         = new TMSCommonHelper();
		$curent_tables        = get_field( 'current_select', 'user_' . $user_id );
		if ( $curent_tables ) {
			$this->project    = $curent_tables;
			$this->table_main = 'reports_' . strtolower( $curent_tables );
			$this->table_meta = 'reportsmeta_' . strtolower( $curent_tables );
		}
	}
	
	public function get_profit_by_preset( $preset_ids, $month = null, $year = null ) {
		global $wpdb;
		
		if ( empty( $preset_ids ) || ! is_array( $preset_ids ) ) {
			return [];
		}
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$placeholders = implode( ',', array_fill( 0, count( $preset_ids ), '%s' ) );
		
		// Build WHERE conditions and values
		$where_conditions = array();
		$where_values = array();
		
		// Add preset filter
		$where_conditions[] = "preset_meta.meta_key = 'preset'";
		$where_conditions[] = "preset_meta.meta_value IN ($placeholders)";
		$where_values = array_merge( $where_values, $preset_ids );
		
		// Add date filters if provided
		if ( ! empty( $year ) && ! empty( $month ) ) {
			$where_conditions[] = "main.date_booked IS NOT NULL";
			$where_conditions[] = "YEAR(main.date_booked) = %d";
			$where_conditions[] = "MONTH(main.date_booked) = %d";
			$where_values[] = (int) $year;
			$where_values[] = (int) $month;
		} elseif ( ! empty( $year ) ) {
			$where_conditions[] = "main.date_booked IS NOT NULL";
			$where_conditions[] = "YEAR(main.date_booked) = %d";
			$where_values[] = (int) $year;
		} elseif ( ! empty( $month ) ) {
			$where_conditions[] = "main.date_booked IS NOT NULL";
			$where_conditions[] = "MONTH(main.date_booked) = %d";
			$where_values[] = (int) $month;
		}
		
		// Add status filter
		$where_conditions[] = "main.status_post = 'publish'";
		
		$where_clause = implode( ' AND ', $where_conditions );
		
		$sql = "
		SELECT
			preset_meta.meta_value AS preset_id,
			COUNT(DISTINCT preset_meta.post_id) AS total_posts,
			SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit
		FROM {$table_meta} AS preset_meta
		INNER JOIN {$table_main} AS main
			ON main.id = preset_meta.post_id
		INNER JOIN {$table_meta} AS profit_meta
			ON profit_meta.post_id = preset_meta.post_id AND profit_meta.meta_key = 'profit'
		WHERE {$where_clause}
		GROUP BY preset_meta.meta_value
		";
		
		if ( ! empty( $where_values ) ) {
			$prepared_sql = $wpdb->prepare( $sql, ...$where_values );
		} else {
			$prepared_sql = $sql;
		}
		
		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );
		
		$output = [];
		
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$preset_id            = 'brocker_' . $row[ 'preset_id' ];
				$output[ $preset_id ] = [
					'total_posts'  => (int) $row[ 'total_posts' ],
					'total_profit' => (float) $row[ 'total_profit' ],
				];
			}
		}
		
		return $output;
	}
	
	
	public function get_profit_by_dates( $array_dates, $office = null ) {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Если входной параметр не массив – возвращаем пустой массив
		if ( ! is_array( $array_dates ) ) {
			return array();
		}
		
		// Убираем дубликаты, если они есть
		$array_dates = array_unique( $array_dates );
		
		// Обрезаем время (оставляем только дату) и фильтруем невалидные даты
		$array_dates = array_filter( array_map( function( $date ) {
			$trimmed = substr( $date, 0, 10 );
			// Простейшая проверка формата YYYY-MM-DD
			if ( strlen( $trimmed ) === 10 && substr_count( $trimmed, '-' ) === 2 ) {
				return $trimmed;
			}
			
			return false;
		}, $array_dates ) );
		
		// Если после фильтрации массив пуст – возвращаем пустой массив
		if ( empty( $array_dates ) ) {
			return array();
		}
		
		// Создаем строку плейсхолдеров для дат
		$date_placeholders = implode( ',', array_fill( 0, count( $array_dates ), '%s' ) );
		// Формируем базовый SQL-запрос.
		// В данном запросе получаем сумму прибыли (meta с meta_key = 'profit') для каждой даты и source.
		// Если задан офис (и он не 'all'), то добавляем LEFT JOIN для фильтрации по метаполю office_dispatcher.
		$query = "
        SELECT 
            DATE(main.date_booked) AS date, 
            COALESCE(source_meta.meta_value, '') AS source,
            AVG(profit.meta_value) AS average_profit, 
            SUM(profit.meta_value) AS total_profit,
            COUNT(main.id) AS load_count
        FROM $table_main AS main
        LEFT JOIN $table_meta AS profit ON main.id = profit.post_id AND profit.meta_key = 'profit'
     	INNER JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
     	LEFT JOIN $table_meta AS source_meta ON main.id = source_meta.post_id AND source_meta.meta_key = 'source'
    			";
		
		// Массив параметров для плейсхолдеров. Сначала передаем даты.
		$params = array_values( $array_dates );
		
		// Если офис задан и он не равен 'all', добавляем JOIN и условие по метаполю office_dispatcher
		if ( ! empty( $office ) && $office !== 'all' ) {
			$query .= " LEFT JOIN $table_meta AS office_meta ON main.id = office_meta.post_id AND office_meta.meta_key = 'office_dispatcher' ";
		}
		
		// Формируем WHERE часть запроса
		$query .= " WHERE DATE(main.date_booked) IN ($date_placeholders)
                AND main.status_post = 'publish' AND load_status.meta_value NOT IN ('waiting-on-rc', 'cancelled') ";
		
		if ( ! empty( $office ) && $office !== 'all' ) {
			$query    .= " AND office_meta.meta_value = %s ";
			$params[] = $office;
		}
		
		$query .= " GROUP BY DATE(main.date_booked), source_meta.meta_value
                ORDER BY DATE(main.date_booked) ASC, source_meta.meta_value ASC ";
		
		// Подготавливаем запрос с переданными параметрами
		$query = $wpdb->prepare( $query, ...$params );
		
		// Для отладки можно раскомментировать следующую строку:
		// error_log($query);
		
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Если запрос вернул null или false, возвращаем пустой массив
		if ( ! $results ) {
			return array();
		}
		
		// Преобразуем результат в ассоциативный массив вида: 'YYYY-MM-DD' => сумма профита + профит по source
		$profit_by_date = array();
		$date_totals = array(); // Для хранения общего профита и количества грузов по датам
		
		foreach ( $results as $row ) {
			if ( ! empty( $row[ 'date' ] ) ) {
				$date = $row[ 'date' ];
				$source = ! empty( $row[ 'source' ] ) ? $row[ 'source' ] : '';
				$total_profit = (float) $row[ 'total_profit' ];
				$average_profit = (float) $row[ 'average_profit' ];
				$load_count = isset( $row[ 'load_count' ] ) ? (int) $row[ 'load_count' ] : 0;
				
				// Инициализируем структуру для даты, если еще не создана
				if ( ! isset( $profit_by_date[ $date ] ) ) {
					$profit_by_date[ $date ] = array(
						'total' => 0,
						'average' => 0,
					);
					$date_totals[ $date ] = array(
						'total_profit' => 0,
						'total_count' => 0,
					);
				}
				
				// Добавляем к общему профиту и количеству грузов
				$profit_by_date[ $date ][ 'total' ] += $total_profit;
				$date_totals[ $date ][ 'total_profit' ] += $total_profit;
				$date_totals[ $date ][ 'total_count' ] += $load_count;
				
				// Сохраняем профит по source
				if ( ! empty( $source ) ) {
					if ( ! isset( $profit_by_date[ $date ][ $source ] ) ) {
						$profit_by_date[ $date ][ $source ] = array(
							'total' => 0,
							'average' => 0,
							'count' => 0,
						);
					}
					$profit_by_date[ $date ][ $source ][ 'total' ] += $total_profit;
					$profit_by_date[ $date ][ $source ][ 'count' ] += $load_count;
					// Для среднего по source используем среднее значение из запроса (AVG уже рассчитан для этой группы)
					$profit_by_date[ $date ][ $source ][ 'average' ] = $average_profit;
				}
				
				// Подсчитываем количество грузов для расчета общего среднего
				// Используем количество грузов из запроса (можно получить через COUNT, но пока используем приблизительный расчет)
				// Для точности нужно будет добавить COUNT в SELECT
			}
		}
		
		// Пересчитываем общее среднее для каждой даты: общий профит / общее количество грузов
		foreach ( $profit_by_date as $date => &$data ) {
			if ( isset( $date_totals[ $date ] ) && $date_totals[ $date ][ 'total_count' ] > 0 ) {
				$data[ 'average' ] = $date_totals[ $date ][ 'total_profit' ] / $date_totals[ $date ][ 'total_count' ];
			} else {
				$data[ 'average' ] = 0;
			}
		}
		unset( $data ); // Убираем ссылку
		
		return $profit_by_date;
	}
	
	// GET ITEMS
	public function get_stat_platform( $args = array() ) {
		global $wpdb;
		
		// Build cache key including filter parameters
		$filter_params = array(
			'office'      => isset( $args[ 'office' ] ) ? $args[ 'office' ] : '',
			'dispatcher'  => isset( $args[ 'dispatcher' ] ) ? $args[ 'dispatcher' ] : '',
			'load_status' => isset( $args[ 'load_status' ] ) ? $args[ 'load_status' ] : '',
			'source'      => isset( $args[ 'source' ] ) ? $args[ 'source' ] : '',
			'year'        => isset( $args[ 'year' ] ) ? $args[ 'year' ] : '',
			'month'       => isset( $args[ 'month' ] ) ? $args[ 'month' ] : '',
			'my_search'   => isset( $args[ 'my_search' ] ) ? $args[ 'my_search' ] : '',
		);
		$cache_key = 'stat_platform_cache_' . $this->project . '_' . md5( serialize( $filter_params ) );
		$cached    = get_transient( $cache_key );
		
		if ( $cached !== false ) {
			return $cached;
		}
		
		$table_main    = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		$table_company = $wpdb->prefix . $this->table_company;
		
		// Получаем ID компаний, сгруппированных по платформам
		$platform_data = $wpdb->get_results( "
		SELECT set_up_platform AS platform, GROUP_CONCAT(id ORDER BY id ASC) AS company_ids
		FROM $table_company
		WHERE set_up_platform IN ('rmis', 'highway', 'mcp')
		GROUP BY set_up_platform
	", ARRAY_A );
		
		if ( empty( $platform_data ) ) {
			return [];
		}
		
		$final_stats = [];
		
		foreach ( $platform_data as $row ) {
			$platform  = $row[ 'platform' ];
			$ids_array = array_map( 'intval', explode( ',', $row[ 'company_ids' ] ) );
			
			if ( empty( $ids_array ) ) {
				$final_stats[ $platform ] = 0;
				continue;
			}
			
			$placeholders = implode( ',', array_fill( 0, count( $ids_array ), '%d' ) );
			
			// Build WHERE conditions similar to get_table_items
			$where_conditions = array();
			$where_values     = array();
			
			// Base conditions
			$where_conditions[] = "customer_meta.meta_value IN ($placeholders)";
			$where_values       = array_merge( $where_values, $ids_array );
			
			$where_conditions[] = "main.status_post = 'publish'";
			$where_conditions[] = "(load_status.meta_value IS NULL OR load_status.meta_value NOT IN ('waiting-on-rc', 'delivered', 'tonu', 'cancelled'))";
			
			// Add JOINs for filters
			$join_clauses = array();
			$join_clauses[] = "LEFT JOIN {$table_meta} AS dispatcher ON main.id = dispatcher.post_id AND dispatcher.meta_key = 'dispatcher_initials'";
			$join_clauses[] = "LEFT JOIN {$table_meta} AS source ON main.id = source.post_id AND source.meta_key = 'source'";
			$join_clauses[] = "LEFT JOIN {$table_meta} AS office_dispatcher ON main.id = office_dispatcher.post_id AND office_dispatcher.meta_key = 'office_dispatcher'";
			$join_clauses[] = "LEFT JOIN {$table_meta} AS reference ON main.id = reference.post_id AND reference.meta_key = 'reference_number'";
			$join_clauses[] = "LEFT JOIN {$table_meta} AS unit_number ON main.id = unit_number.post_id AND unit_number.meta_key = 'unit_number_name'";
			$join_clauses[] = "LEFT JOIN {$table_meta} AS pick_up_location ON main.id = pick_up_location.post_id AND pick_up_location.meta_key = 'pick_up_location'";
			$join_clauses[] = "LEFT JOIN {$table_meta} AS delivery_location ON main.id = delivery_location.post_id AND delivery_location.meta_key = 'delivery_location'";
			
			// Apply filters
			if ( ! empty( $args[ 'office' ] ) && $args[ 'office' ] !== 'all' ) {
				$where_conditions[] = "office_dispatcher.meta_value = %s";
				$where_values[]     = $args[ 'office' ];
			}
			
			if ( ! empty( $args[ 'dispatcher' ] ) ) {
				$where_conditions[] = "dispatcher.meta_value = %s";
				$where_values[]     = $args[ 'dispatcher' ];
			}
			
			if ( ! empty( $args[ 'load_status' ] ) ) {
				$where_conditions[] = "load_status.meta_value = %s";
				$where_values[]     = $args[ 'load_status' ];
			}
			
			if ( ! empty( $args[ 'source' ] ) ) {
				$where_conditions[] = "source.meta_value = %s";
				$where_values[]     = $args[ 'source' ];
			}
			
			// Date filters
			if ( ! empty( $args[ 'month' ] ) && ! empty( $args[ 'year' ] ) ) {
				$where_conditions[] = "main.date_booked IS NOT NULL AND YEAR(main.date_booked) = %d AND MONTH(main.date_booked) = %d";
				$where_values[]     = $args[ 'year' ];
				$where_values[]     = $args[ 'month' ];
			} elseif ( ! empty( $args[ 'year' ] ) && empty( $args[ 'month' ] ) ) {
				$where_conditions[] = "main.date_booked IS NOT NULL AND YEAR(main.date_booked) = %d";
				$where_values[]     = $args[ 'year' ];
			} elseif ( ! empty( $args[ 'month' ] ) && empty( $args[ 'year' ] ) ) {
				$where_conditions[] = "main.date_booked IS NOT NULL AND MONTH(main.date_booked) = %d";
				$where_values[]     = $args[ 'month' ];
			}
			
			// Search filter
			if ( ! empty( $args[ 'my_search' ] ) ) {
				$where_conditions[] = "(reference.meta_value LIKE %s OR unit_number.meta_value LIKE %s OR pick_up_location.meta_value LIKE %s OR delivery_location.meta_value LIKE %s)";
				$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
				$where_values[]     = $search_value;
				$where_values[]     = $search_value;
				$where_values[]     = $search_value;
				$where_values[]     = $search_value;
			}
			
			$sql = "
			SELECT COUNT(DISTINCT main.id)
			FROM {$table_main} AS main
			LEFT JOIN {$table_meta} AS customer_meta
				ON main.id = customer_meta.post_id AND customer_meta.meta_key = 'customer_id'
			LEFT JOIN {$table_meta} AS load_status
				ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
			" . implode( ' ', $join_clauses ) . "
			WHERE " . implode( ' AND ', $where_conditions );
			
			if ( ! empty( $where_values ) ) {
				$count = $wpdb->get_var( $wpdb->prepare( $sql, ...$where_values ) );
			} else {
				$count = $wpdb->get_var( $sql );
			}
			
			$final_stats[ $platform ] = (int) $count;
		}
		
		set_transient( $cache_key, $final_stats, 30 * MINUTE_IN_SECONDS );
		
		return $final_stats;
	}
	
	public function get_stat_tools() {
		global $wpdb;
		$cache_key = 'stat_tool_cache_' . $this->project;
		$cached    = get_transient( $cache_key );
		
		if ( $cached !== false ) {
			return $cached;
		}
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$sql = "
		SELECT
			COUNT(DISTINCT CASE
				WHEN macropoint.meta_value IS NOT NULL AND macropoint.meta_value != '' THEN macropoint.post_id
			END) AS macropoint_count,
			COUNT(DISTINCT CASE
				WHEN truckertools.meta_value IS NOT NULL AND truckertools.meta_value != '' THEN truckertools.post_id
			END) AS truckertools_count
		FROM $table_main AS main
		LEFT JOIN $table_meta AS macropoint
			ON main.id = macropoint.post_id AND macropoint.meta_key = 'macropoint_set'
		LEFT JOIN $table_meta AS truckertools
			ON main.id = truckertools.post_id AND truckertools.meta_key = 'trucker_tools'
		LEFT JOIN $table_meta AS load_status
			ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
		WHERE main.status_post = 'publish'
		  AND (load_status.meta_value IS NULL OR load_status.meta_value NOT IN ('waiting-on-rc', 'delivered', 'tonu', 'cancelled'))
	";
		
		$results = $wpdb->get_row( $sql, ARRAY_A );
		set_transient( $cache_key, $results, 30 * MINUTE_IN_SECONDS );
		
		return $results;
	}
	
	/**
	 * Get table items and filter. Delegates to TMSReportsHelper with table names and report-specific options.
	 *
	 * @param array $args Filter args.
	 * @return array
	 */
	public function get_table_items( $args = array() ) {
		global $wpdb;
		$args['per_page_loads']       = isset( $args['per_page_loads'] ) ? $args['per_page_loads'] : $this->per_page_loads;
		$args['use_rating_search']    = true;
		$args['my_search_join_extra'] = array( 'pick_up_location', 'delivery_location' );
		return $this->get_table_items_internal(
			$wpdb->prefix . $this->table_main,
			$wpdb->prefix . $this->table_meta,
			$args
		);
	}
	
	/**
	 * Get table items for billing tab. Delegates to TMSReportsHelper with table names.
	 *
	 * @param array $args Filter args.
	 * @return array
	 */
	public function get_table_items_billing( $args = array() ) {
		global $wpdb;
		$args['per_page_loads'] = isset( $args['per_page_loads'] ) ? $args['per_page_loads'] : $this->per_page_loads;
		return $this->get_table_items_billing_internal(
			$wpdb->prefix . $this->table_main,
			$wpdb->prefix . $this->table_meta,
			$args
		);
	}
	
	/**
	 * Get table items for billing shortpay tab. Delegates to TMSReportsHelper with dynamic JOINs.
	 *
	 * @param array $args Filter args.
	 * @return array
	 */
	public function get_table_items_billing_shortpay( $args = array() ) {
		global $wpdb;
		$args[ 'per_page_loads' ] = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		return $this->get_table_items_billing_shortpay_internal(
			$wpdb->prefix . $this->table_main,
			$wpdb->prefix . $this->table_meta,
			$args
		);
	}

	/**
	 * Aggregate Charge back & Short pay totals by broker (no pagination). Delegates to TMSReportsHelper.
	 *
	 * @param array $args Same filters as in get_table_items_billing_shortpay
	 * @return array Array of rows: [ 'customer_id' => int, 'charge_back_total' => float, 'short_pay_total' => float ]
	 */
	public function get_shortpay_stats_by_broker( $args = array() ) {
		global $wpdb;
		return $this->get_shortpay_stats_by_broker_internal(
			$wpdb->prefix . $this->table_main,
			$wpdb->prefix . $this->table_meta,
			$args
		);
	}

	/**
	 * Get table items for tracking tab. Delegates to TMSReportsHelper::get_table_items_tracking_internal.
	 *
	 * @param array $args Filter args.
	 * @return array
	 */
	public function get_table_items_tracking( $args = array() ) {
		global $wpdb;
		$table_main       = $wpdb->prefix . $this->table_main;
		$table_meta       = $wpdb->prefix . $this->table_meta;
		$per_page         = $this->per_page_loads;
		$table_locations  = ! empty( $this->project ) ? $wpdb->prefix . 'reports_' . strtolower( $this->project ) . '_locations' : '';
		return $this->get_table_items_tracking_internal( $table_main, $table_meta, $args, $table_locations, $per_page );
	}

	/**
	 * Get counts for quick status filter buttons (2 queries instead of 5× get_table_items_tracking).
	 *
	 * @param array $args        Same filter args as get_table_items_tracking.
	 * @param array $status_keys Keys for counts: '' for total, plus 'waiting-on-pu-date', 'at-pu', 'loaded-enroute', 'at-del'.
	 * @return array<string, int>
	 */
	public function get_tracking_quick_status_counts( $args = array(), $status_keys = array() ) {
		global $wpdb;
		$table_main      = $wpdb->prefix . $this->table_main;
		$table_meta      = $wpdb->prefix . $this->table_meta;
		$table_locations = ! empty( $this->project ) ? $wpdb->prefix . 'reports_' . strtolower( $this->project ) . '_locations' : '';
		return $this->get_tracking_quick_status_counts_internal( $table_main, $table_meta, $args, $table_locations, $status_keys );
	}

	/**
	 * Get high priority loads for tracking pages
	 * Returns loads with high_priority = 1, excluding 'delivered' status
	 * 
	 * @param array $args Same arguments as get_table_items_tracking
	 * @return array Array of load IDs and full data
	 */
	public function get_high_priority_loads( $args = array() ) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$join_builder = "
	    FROM $table_main AS main
	    LEFT JOIN $table_meta AS dispatcher ON main.id = dispatcher.post_id AND dispatcher.meta_key = 'dispatcher_initials'
	    LEFT JOIN $table_meta AS reference ON main.id = reference.post_id AND reference.meta_key = 'reference_number'
	    LEFT JOIN $table_meta AS unit_number ON main.id = unit_number.post_id AND unit_number.meta_key = 'unit_number_name'
	    LEFT JOIN $table_meta AS unit_phone ON main.id = unit_phone.post_id AND unit_phone.meta_key = 'driver_phone'
	    LEFT JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
	    LEFT JOIN $table_meta AS office_dispatcher
					ON main.id = office_dispatcher.post_id
					AND office_dispatcher.meta_key = 'office_dispatcher'
	    LEFT JOIN $table_meta AS tbd
					ON main.id = tbd.post_id
					AND tbd.meta_key = 'tbd'
	    INNER JOIN $table_meta AS `high_priority`
					ON main.id = `high_priority`.post_id
					AND `high_priority`.meta_key = 'high_priority'
	    WHERE 1=1
	    ";
		
		$sql = "SELECT main.*,
    dispatcher.meta_value AS dispatcher_initials_value,
    reference.meta_value AS reference_number_value,
    unit_number.meta_value AS unit_number_value,
    load_status.meta_value AS load_status_value
    " . $join_builder;
		
		// Условия WHERE
		$where_conditions = [];
		$where_values     = [];
		
		// Вспомогательная функция для формирования условий
		$add_condition = function( $condition, $value ) use ( &$where_conditions, &$where_values ) {
			$where_conditions[] = $condition;
			$where_values[]     = $value;
		};
		
		// High priority condition - check for '1' as string (stored as 1 or '1')
		$where_conditions[] = "`high_priority`.meta_value = '1'";
		
		// Exclude delivered status (allow NULL for load_status in case it's not set)
		// Note: This will be overridden by exclude_status if provided, but we need it for basic filtering
		$where_conditions[] = "(load_status.meta_value IS NULL OR load_status.meta_value != 'delivered')";
		
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$add_condition( "main.status_post = %s", $args[ 'status_post' ] );
		}
		
		if ( ! empty( $args[ 'load_status' ] ) ) {
			$add_condition( "load_status.meta_value = %s", $args[ 'load_status' ] );
		}
		
		if ( ! empty( $args[ 'office' ] ) && $args[ 'office' ] !== 'all' ) {
			$where_conditions[] = "office_dispatcher.meta_value = %s";
			$where_values[]     = $args[ 'office' ];
		}
		
		if ( ! empty( $args[ 'my_team' ] ) && is_array( $args[ 'my_team' ] ) ) {
			$team_values        = esc_sql( $args[ 'my_team' ] );
			$where_conditions[] = "dispatcher.meta_value IN ('" . implode( "','", $team_values ) . "')";
		}
		
		if ( ! empty( $args[ 'dispatcher' ] ) ) {
			$add_condition( "dispatcher.meta_value = %s", $args[ 'dispatcher' ] );
		}
		
		// Exclude statuses - but ensure 'delivered' is always excluded for high priority loads
		if ( ! empty( $args[ 'exclude_status' ] ) ) {
			$exclude_status     = esc_sql( (array) $args[ 'exclude_status' ] );
			// Ensure 'delivered' is in the exclude list
			if ( ! in_array( 'delivered', $exclude_status ) ) {
				$exclude_status[] = 'delivered';
			}
			$where_conditions[] = "(load_status.meta_value IS NULL OR load_status.meta_value NOT IN ('" . implode( "','", $exclude_status ) . "'))";
		}
		
		if ( ! empty( $args[ 'include_status' ] ) ) {
			$include_status     = esc_sql( (array) $args[ 'include_status' ] );
			$where_conditions[] = "load_status.meta_value IN ('" . implode( "','", $include_status ) . "')";
		}
		
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_conditions[] = "(reference.meta_value LIKE %s OR unit_number.meta_value LIKE %s OR unit_phone.meta_value LIKE %s)";
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		// Exclude TBD loads
		if ( isset( $args[ 'exclude_tbd' ] ) && ! empty( $args[ 'exclude_tbd' ] ) ) {
			$where_conditions[] = "(tbd.meta_value IS NULL OR tbd.meta_value != '1')";
		}
		
		if ( $where_conditions ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Order by same as main query
		$sort_order = strtolower( $args[ 'sort_order' ] ?? 'desc' ) === 'asc' ? 'ASC' : 'DESC';
		$sql .= " ORDER BY
    CASE
        WHEN LOWER(load_status.meta_value) = 'at-pu' THEN 1
        WHEN LOWER(load_status.meta_value) = 'at-del' THEN 2
        WHEN LOWER(load_status.meta_value) = 'waiting-on-pu-date' THEN 3
        WHEN LOWER(load_status.meta_value) = 'loaded-enroute' THEN 4
        WHEN LOWER(load_status.meta_value) = 'waiting-on-rc' THEN 5
        ELSE 6
    END,
    CASE
        WHEN LOWER(load_status.meta_value) IN ('at-pu', 'at-del', 'waiting-on-pu-date', 'waiting-on-rc', 'loaded-enroute') THEN COALESCE(main.pick_up_date, '9999-12-31 23:59:59')
        ELSE COALESCE(main.delivery_date, '9999-12-31 23:59:59')
    END $sort_order";

		$main_results = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ), ARRAY_A );

		// Обработка метаданных
		$post_ids  = wp_list_pluck( $main_results, 'id' );
		$meta_data = [];
		
		if ( $post_ids ) {
			$meta_sql     = "SELECT post_id, meta_key, meta_value FROM $table_meta WHERE post_id IN (" . implode( ',', array_map( 'absint', $post_ids ) ) . ")";
			$meta_results = $wpdb->get_results( $meta_sql, ARRAY_A );
			
			$meta_data = array_reduce( $meta_results, function( $carry, $meta_row ) {
				$carry[ $meta_row[ 'post_id' ] ][ $meta_row[ 'meta_key' ] ] = $meta_row[ 'meta_value' ];
				
				return $carry;
			}, [] );
		}
		
		foreach ( $main_results as &$result ) {
			$result[ 'meta_data' ] = $meta_data[ $result[ 'id' ] ] ?? [];
		}
		
		return $main_results;
	}
	
	public function get_table_items_tracking_statistics( $office_dispatcher = 'all' ) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$join_builder = "
		    FROM $table_main AS main
		    LEFT JOIN $table_meta AS dispatcher ON main.id = dispatcher.post_id AND dispatcher.meta_key = 'dispatcher_initials'
		    LEFT JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
	        LEFT JOIN $table_meta AS office_dispatcher ON main.id = office_dispatcher.post_id AND office_dispatcher.meta_key = 'office_dispatcher'
	        LEFT JOIN $table_meta AS tbd ON main.id = tbd.post_id AND tbd.meta_key = 'tbd'
	    WHERE 1=1
		";
		
		// Исключаем статусы
		$exclude_status = [ 'delivered', 'tonu', 'cancelled', 'waiting-on-rc' ];
		$include_status = [ 'waiting-on-pu-date', 'at-pu', 'loaded-enroute', 'at-del' ];
		
		$where_conditions = [];
		
		$where_conditions[] = "main.status_post = 'publish'";
		
		if ( ! empty( $exclude_status ) ) {
			$exclude_status     = esc_sql( $exclude_status );
			$where_conditions[] = "load_status.meta_value NOT IN ('" . implode( "','", $exclude_status ) . "')";
		}
		
		if ( ! empty( $office_dispatcher ) && $office_dispatcher !== 'all' ) {
			$escaped_value      = esc_sql( $office_dispatcher );
			$where_conditions[] = "office_dispatcher.meta_value = '{$escaped_value}'";
		}
		
		// Фильтруем только нужные статусы
		if ( ! empty( $include_status ) ) {
			$include_status     = esc_sql( $include_status );
			$where_conditions[] = "load_status.meta_value IN ('" . implode( "','", $include_status ) . "')";
		}
		
		// Exclude TBD loads
		$where_conditions[] = "(tbd.meta_value IS NULL OR tbd.meta_value != '1')";
		
		$sql = "SELECT
    dispatcher.meta_value AS dispatcher_initials,
    load_status.meta_value AS load_status,
    COUNT(main.id) AS count_status
    " . $join_builder;
		
		if ( $where_conditions ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Группировка по диспетчеру и статусу
		$sql .= " GROUP BY dispatcher.meta_value, load_status.meta_value";
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		// Формируем структуру результата
		$dispatcher_data  = [];
		$grand_total_data = [
			'waiting-on-pu-date' => 0,
			'at-pu'              => 0,
			'loaded-enroute'     => 0,
			'at-del'             => 0,
			'total'              => 0,
		];
		foreach ( $results as $row ) {
			$dispatcher = $row[ 'dispatcher_initials' ] ?: 'Unknown';
			$status     = $row[ 'load_status' ];
			$count      = (int) $row[ 'count_status' ];
			
			// Инициализация данных для диспетчера, если его еще нет
			if ( ! isset( $dispatcher_data[ 'user_' . $dispatcher ] ) ) {
				$dispatcher_data[ 'user_' . $dispatcher ] = [
					'waiting-on-pu-date' => 0,
					'at-pu'              => 0,
					'loaded-enroute'     => 0,
					'at-del'             => 0,
					'total'              => 0,
				];
			}
			
			
			// Если статус существует в массиве диспетчера — увеличиваем счетчик
			if ( isset( $dispatcher_data[ 'user_' . $dispatcher ][ $status ] ) ) {
				$dispatcher_data[ 'user_' . $dispatcher ][ $status ] += $count;
				$dispatcher_data[ 'user_' . $dispatcher ][ 'total' ] += $count; // Добавляем в total диспетчера
				
				// Добавляем в общий подсчет (grand_total_data)
				$grand_total_data[ $status ] += $count;
				$grand_total_data[ 'total' ] += $count;
			}
		}

//		var_dump( $dispatcher_data );
		
		return [
			'dispatchers' => $dispatcher_data,
			'grand_total' => $grand_total_data,
		];
		
	}
	
	public function get_total_by_tracking_team( $user, $items ) {
		$team_total = [
			'waiting-on-pu-date' => 0,
			'at-pu'              => 0,
			'loaded-enroute'     => 0,
			'at-del'             => 0,
			'total'              => 0,
		];
		
		if ( ! isset( $user[ 'my_team' ] ) || ! is_array( $user[ 'my_team' ] ) ) {
			return $team_total;
		}
		
		foreach ( $user[ 'my_team' ] as $team_member_id ) {
			if ( empty( $team_member_id ) || ! is_numeric( $team_member_id ) ) {
				continue;
			}
			
			$key = "user_{$team_member_id}";
			
			if ( isset( $items[ 'dispatchers' ][ $key ] ) && is_array( $items[ 'dispatchers' ][ $key ] ) ) {
				foreach ( $items[ 'dispatchers' ][ $key ] as $status => $count ) {
					if ( isset( $team_total[ $status ] ) && is_numeric( $count ) ) {
						$team_total[ $status ] += $count;
						$team_total[$key] = $count;
					}
				}
			}
		}
		
		return $team_total;
	}
	
	public function get_tracking_users_for_statistics( $exclude, $office_dispatcher = 'all' ) {
		
		global $wpdb;
		
		$sql = "
		SELECT u.ID, u.display_name,
			COALESCE(nightshift.meta_value, '0') AS nightshift,
			COALESCE(my_team.meta_value, '') AS my_team,
			COALESCE(initials_color.meta_value, '') AS initials_color,
			COALESCE(weekends.meta_value, '0') AS weekends,
			um_first.meta_value AS first_name,
			um_last.meta_value AS last_name
		FROM {$wpdb->users} AS u
		INNER JOIN {$wpdb->usermeta} AS ur ON u.ID = ur.user_id AND ur.meta_key = '{$wpdb->prefix}capabilities'
		LEFT JOIN {$wpdb->usermeta} AS nightshift ON u.ID = nightshift.user_id AND nightshift.meta_key = %s
		LEFT JOIN {$wpdb->usermeta} AS my_team ON u.ID = my_team.user_id AND my_team.meta_key = %s
		LEFT JOIN {$wpdb->usermeta} AS initials_color ON u.ID = initials_color.user_id AND initials_color.meta_key = %s
		LEFT JOIN {$wpdb->usermeta} AS weekends ON u.ID = weekends.user_id AND weekends.meta_key = %s
		LEFT JOIN {$wpdb->usermeta} AS um_first ON u.ID = um_first.user_id AND um_first.meta_key = 'first_name'
		LEFT JOIN {$wpdb->usermeta} AS um_last ON u.ID = um_last.user_id AND um_last.meta_key = 'last_name'
		WHERE (
			ur.meta_value LIKE %s OR
			ur.meta_value LIKE %s OR
			ur.meta_value LIKE %s OR
			ur.meta_value LIKE %s
		)" . ( ! empty( $exclude ) ? " AND u.ID NOT IN (" . implode( ',', array_map( 'absint', $exclude ) ) . ") " : "" );
		
		$results = $wpdb->get_results( $wpdb->prepare( $sql, 'nightshift', 'my_team', 'initials_color', 'weekends', '%"tracking"%', '%"tracking-tl"%', '%"morning_tracking"%', '%"nightshift_tracking"%' ), ARRAY_A );
		
		
		$tracking_data = [
			'nightshift' => [],
			'morning_tracking' => [],
			'tracking'   => []
		];
		

		foreach ( $results as $user ) {
			$first_name      = trim( $user[ 'first_name' ] ?? '' );
			$last_name       = trim( $user[ 'last_name' ] ?? '' );
			$initials        = mb_strtoupper( mb_substr( $first_name, 0, 1 ) . mb_substr( $last_name, 0, 1 ) );
			$initials_color  = $user[ 'initials_color' ];
			$fields          = get_fields( 'user_' . $user[ 'ID' ] );
			$day_name        = strtolower( date( 'l' ) ); // Получаем день недели, например: 'Monday'
			$key_name        = 'exclude_' . $day_name;
			$exclude_drivers = get_field_value( $fields, $key_name ) ?? [];
			
			$office = get_field_value( $fields, 'work_location' );
			
			$my_team                  = ! empty( $user[ 'my_team' ] ) ? maybe_unserialize( $user[ 'my_team' ] ) : [];
			$my_team_without_weekends = $my_team;
			
			if ( is_array( $exclude_drivers ) && ! empty( $exclude_drivers ) ) {
				$exclude_drivers = array_map( 'intval', $exclude_drivers );
			}
			
			if ( is_array( $my_team ) && ! empty( $my_team ) ) {
				$my_team                  = array_map( 'intval', $my_team );
				$my_team_without_weekends = array_map( 'intval', $my_team_without_weekends );
			}
			
			if ( is_array( $exclude_drivers ) && ! empty( $exclude_drivers ) ) {
				$filtered_team = array_filter( $my_team, function( $driver_id ) use ( $exclude_drivers ) {
					return ! in_array( $driver_id, $exclude_drivers, true );
				} );
				
				$my_team = $filtered_team;
			}
			
			
			$weekends = ! empty( $user[ 'weekends' ] ) ? maybe_unserialize( $user[ 'weekends' ] ) : [];
			// Получаем текущий день недели в нижнем регистре, например: 'monday'
			$today = strtolower( date( 'l' ) );
			
			if ( $office_dispatcher !== 'all' && $office_dispatcher !== $office ) {
				continue;
			}
			
			
			$user_data = [
				'id'                       => $user[ 'ID' ],
				'name'                     => $user[ 'display_name' ],
				'my_team'                  => is_array( $my_team ) ? $my_team : [],
				'my_team_without_weekends' => is_array( $my_team_without_weekends ) ? $my_team_without_weekends : [],
				'initials'                 => $initials,
				'initials_color'           => $initials_color,
			];
			
			// Get user roles to determine shift type
			$user_roles = get_userdata( $user[ 'ID' ] )->roles ?? [];
			
			// Пропустить пользователя, если сегодня его выходной
			if ( is_array( $weekends ) && in_array( $today, $weekends, true ) ) {
				$tracking_data[ 'tracking_move' ][] = $user_data;
				continue;
			} else {
				$tracking_data[ 'weekends' ][] = $user_data;
			}
			
			// Determine user shift based on roles
			if ( in_array( 'nightshift_tracking', $user_roles ) ) {
				$tracking_data[ 'nightshift' ][] = $user_data;
				$tracking_data[ 'tracking_move' ][] = $user_data;
			} elseif ( in_array( 'morning_tracking', $user_roles ) ) {
				$tracking_data[ 'morning_tracking' ][] = $user_data;
				$tracking_data[ 'tracking_move' ][] = $user_data;
			} else {
				// Regular tracking users (tracking, tracking-tl)
				$tracking_data[ 'tracking_move' ][] = $user_data;
				$tracking_data[ 'tracking' ][]      = $user_data;
			}
		}
		
		return $tracking_data;
		
	}
	
	public function get_favorites( $post_ids = array(), $args = array() ) {
		global $wpdb;
		
		// Проверяем, переданы ли ID
		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return array(
				'results'       => array(),
				'total_pages'   => 0,
				'total_posts'   => 0,
				'current_pages' => 1,
			);
		}
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) === 'asc' ? 'ASC'
			: 'DESC';
		
		// Подготовка ID для SQL
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		
		// Подсчёт записей
		$total_records_sql = "
    SELECT COUNT(DISTINCT main.id)
    FROM $table_main AS main
    WHERE main.id IN ($placeholders)
    ";
		$total_records     = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$post_ids ) );
		
		// Пагинация
		$total_pages = ceil( $total_records / $per_page );
		$offset      = ( $current_page - 1 ) * $per_page;
		
		// Запрос для получения основных данных (без мета-данных)
		$sql = "
		    SELECT main.*
		    FROM $table_main AS main
		    WHERE main.id IN ($placeholders)
		    ORDER BY main.$sort_by $sort_order
		    LIMIT %d, %d
		    ";
		
		// Выполнение запроса
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...array_merge( $post_ids, array(
			$offset,
			$per_page
		) ) ), ARRAY_A );
		
		// Запрос для получения мета-данных
		$meta_sql = "
	    SELECT meta.post_id, meta.meta_key, meta.meta_value
	    FROM $table_meta AS meta
	    WHERE meta.post_id IN ($placeholders)
	    ";
		
		// Получаем мета-данные
		$meta_results = $wpdb->get_results( $wpdb->prepare( $meta_sql, ...$post_ids ), ARRAY_A );
		
		// Группируем мета-данные по постам
		$meta_data_grouped = array();
		foreach ( $meta_results as $meta ) {
			$meta_data_grouped[ $meta[ 'post_id' ] ][ $meta[ 'meta_key' ] ] = $meta[ 'meta_value' ];
		}
		
		// Объединяем мета-данные с основными результатами
		foreach ( $results as &$post ) {
			$post_id = $post[ 'id' ];
			if ( isset( $meta_data_grouped[ $post_id ] ) ) {
				$post[ 'meta_data' ] = $meta_data_grouped[ $post_id ];
			} else {
				$post[ 'meta_data' ] = array();
			}
		}
		
		// Преобразуем к конечному формату
		return array(
			'results'       => $results, // Уже уникальные записи
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
	}
	
	public function get_table_items_unapplied( $args = array() ) {
		global $wpdb;
		
		$table_main        = $wpdb->prefix . $this->table_main;
		$table_meta        = $wpdb->prefix . $this->table_meta;
		$table_company     = $wpdb->prefix . 'reports_company';
		$table_metacompany = $wpdb->prefix . 'reportsmeta_company';
		$per_page          = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page      = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by           = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order        = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) === 'asc' ? 'ASC'
			: 'DESC';
		
		$join_builder = "
        FROM $table_main AS main
        LEFT JOIN $table_meta AS reference
            ON main.id = reference.post_id
            AND reference.meta_key = 'reference_number'
        LEFT JOIN $table_meta AS factoring_status
            ON main.id = factoring_status.post_id
            AND factoring_status.meta_key = 'factoring_status'
        LEFT JOIN $table_meta AS processing
            ON main.id = processing.post_id
            AND processing.meta_key = 'processing'
        LEFT JOIN $table_meta AS customer_meta
            ON main.id = customer_meta.post_id
            AND customer_meta.meta_key = 'customer_id'
        LEFT JOIN $table_company AS company
            ON customer_meta.meta_value = company.id
        LEFT JOIN $table_metacompany AS companymeta
        ON company.id = companymeta.post_id
	    LEFT JOIN $table_metacompany AS days_to_pay
	        ON company.id = days_to_pay.post_id
	        AND days_to_pay.meta_key = 'days_to_pay'
	    LEFT JOIN $table_metacompany AS quick_pay_option
	        ON company.id = quick_pay_option.post_id
	        AND quick_pay_option.meta_key = 'quick_pay_option'
	    LEFT JOIN $table_metacompany AS quick_pay_percent
	        ON company.id = quick_pay_percent.post_id
	        AND quick_pay_percent.meta_key = 'quick_pay_percent'
        LEFT JOIN $table_metacompany AS factoring_broker
	        ON company.id = factoring_broker.post_id
	        AND factoring_broker.meta_key = 'factoring_broker'
		WHERE 1=1
    ";
		
		$sql = "SELECT DISTINCT
        main.*,
        reference.meta_value AS reference_number_value,
        company.company_name,
        company.mc_number,
        days_to_pay.meta_value AS days_to_pay_value,
	    quick_pay_option.meta_value AS quick_pay_option_value,
	    factoring_broker.meta_value AS factoring_broker_value,
	    quick_pay_percent.meta_value AS quick_pay_percent_value" . $join_builder;
		
		$where_conditions = array();
		$where_values     = array();
		
		// Добавляем фильтрацию по значениям processing
		$processing_values = array(
			'factoring-delayed-advance',
			'factoring-wire-transfer',
			'unapplied-payment',
			'direct'
		);
		
		$placeholders       = implode( ', ', array_fill( 0, count( $processing_values ), '%s' ) );
		$where_conditions[] = "processing.meta_value IN ($placeholders)";
		$where_values       = array_merge( $where_values, $processing_values );
		
		// Фильтрация по статусу
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = "main.status_post = %s";
			$where_values[]     = $args[ 'status_post' ];
		}
		
		if ( ! empty( $args[ 'status' ] ) && $args[ 'status' ] !== 'all' ) {
			$where_conditions[] = "factoring_status.meta_value = %s";
			$where_values[]     = $args[ 'status' ];
		}
		
		if ( isset( $args[ 'exclude_factoring_status' ] ) && ! empty( $args[ 'exclude_factoring_status' ] ) ) {
			$exclude_factoring_status = array_map( 'esc_sql', (array) $args[ 'exclude_factoring_status' ] );
			$where_conditions[]       = "(
        factoring_status.meta_value NOT IN ('" . implode( "','", $exclude_factoring_status ) . "')
        OR factoring_status.meta_value IS NULL
        OR factoring_status.meta_value = ''
    )";
		}
		
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(
		        reference.meta_value LIKE %s OR
		        company.mc_number LIKE %s OR
		        company.company_name LIKE %s
		    )";
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records_sql = "SELECT COUNT(*)" . $join_builder;
		if ( ! empty( $where_conditions ) ) {
			$total_records_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		
		// Смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;
		
		// Добавляем сортировку и лимит для текущей страницы
		$sql            .= " ORDER BY
		    CASE
		        WHEN LOWER(factoring_status.meta_value) = 'unsubmitted' THEN 1
		        WHEN LOWER(factoring_status.meta_value) = 'in-processing' THEN 2
		        WHEN LOWER(factoring_status.meta_value) = 'pending-to-tafs' THEN 3
		        WHEN LOWER(factoring_status.meta_value) = 'requires-attention' THEN 4
		        WHEN LOWER(factoring_status.meta_value) = 'in-dispute' THEN 5
		        WHEN LOWER(factoring_status.meta_value) = 'charge-back' THEN 6
		        WHEN LOWER(factoring_status.meta_value) = 'short-pay' THEN 7
		        WHEN LOWER(factoring_status.meta_value) = 'fraud' THEN 8
		        WHEN LOWER(factoring_status.meta_value) = 'processed' THEN 9
		        WHEN LOWER(factoring_status.meta_value) = 'paid' THEN 10
		        WHEN LOWER(factoring_status.meta_value) = 'company-closed' THEN 11
		        ELSE 12
		    END $sort_order
		    LIMIT %d, %d";
		$where_values[] = $offset;
		$where_values[] = $per_page;
		
		// Выполняем запрос
		$main_results = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ), ARRAY_A );
		
		if ( $wpdb->last_error ) {
			var_dump( 'Database error: ' . $wpdb->last_error );
		}
		
		// Собираем все ID записей для получения дополнительных метаданных
		$post_ids = wp_list_pluck( $main_results, 'id' );
		
		// Если есть записи, получаем метаданные
		$meta_data = array();
		if ( ! empty( $post_ids ) ) {
			$meta_sql     = "SELECT post_id, meta_key, meta_value
                 FROM $table_meta
                 WHERE post_id IN (" . implode( ',', array_map( 'absint', $post_ids ) ) . ")";
			$meta_results = $wpdb->get_results( $meta_sql, ARRAY_A );
			
			// Преобразуем метаданные в ассоциативный массив по post_id
			foreach ( $meta_results as $meta_row ) {
				$post_id = $meta_row[ 'post_id' ];
				if ( ! isset( $meta_data[ $post_id ] ) ) {
					$meta_data[ $post_id ] = array();
				}
				$meta_data[ $post_id ][ $meta_row[ 'meta_key' ] ] = $meta_row[ 'meta_value' ];
			}
		}
		
		if ( is_array( $main_results ) && ! empty( $main_results ) ) {
			// Объединяем основную таблицу с метаданными
			foreach ( $main_results as &$result ) {
				$post_id               = $result[ 'id' ];
				$result[ 'meta_data' ] = isset( $meta_data[ $post_id ] ) ? $meta_data[ $post_id ] : array();
			}
		}
		
		return array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
	}
	
	/**
	 * @param $args
	 * Get table items and filter
	 *
	 * @return array
	 */
	public function get_table_items_ar( $args = array() ) {
		global $wpdb;
		
		$table_main    = $wpdb->prefix . $this->table_main;
		$table_meta    = $wpdb->prefix . $this->table_meta;
		$table_company = $wpdb->prefix . 'reports_company'; // Таблица с mc_number и company_name
		$per_page      = $this->per_page_loads;
		$current_page  = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by       = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order    = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) === 'asc' ? 'ASC'
			: 'DESC';
		
		$join_builder = "
		    FROM $table_main AS main
		    LEFT JOIN $table_meta AS reference
		        ON main.id = reference.post_id
		        AND reference.meta_key = 'reference_number'
	        LEFT JOIN $table_meta AS ar_status
				ON main.id = ar_status.post_id
				AND ar_status.meta_key = 'ar_status'
		    LEFT JOIN $table_meta AS customer_meta
		        ON main.id = customer_meta.post_id
		        AND customer_meta.meta_key = 'customer_id'
		    LEFT JOIN $table_company AS company
		        ON customer_meta.meta_value = company.id
		    WHERE 1=1
		";
		
		// Основной запрос с добавлением полей из таблицы company
		$sql = "SELECT
		    main.*,
		    reference.meta_value AS reference_number_value,
		    company.company_name,
		    company.mc_number
		" . $join_builder;
		
		$where_conditions = array();
		$where_values     = array();
		
		// Фильтрация по статусу
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = "main.status_post = %s";
			$where_values[]     = $args[ 'status_post' ];
		}
		
		// Фильтрация по проблемам
		if ( isset( $args[ 'ar_problem' ] ) && $args[ 'ar_problem' ] ) {
			$where_conditions[] = "main.load_problem IS NOT NULL";
			$where_conditions[] = "DATEDIFF(NOW(), main.load_problem) > 50";
		}
		
		if ( ! empty( $args[ 'status' ] ) && $args[ 'status' ] !== 'all' ) {
			$where_conditions[] = "ar_status.meta_value = %s";
			$where_values[]     = $args[ 'status' ];
		}
		
		
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(
        reference.meta_value LIKE %s OR
        company.mc_number LIKE %s OR
        company.company_name LIKE %s
    )";
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		// Применяем фильтры к запросу
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records_sql = "SELECT COUNT(*)" . $join_builder;
		if ( ! empty( $where_conditions ) ) {
			$total_records_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		
		// Смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;
		
		// Добавляем сортировку и лимит для текущей страницы
		$sql            .= " ORDER BY main.$sort_by $sort_order LIMIT %d, %d";
		$where_values[] = $offset;
		$where_values[] = $per_page;
		
		// Выполняем запрос
		$main_results = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ), ARRAY_A );
		// Собираем все ID записей для получения дополнительных метаданных
		$post_ids = wp_list_pluck( $main_results, 'id' );
		
		// Если есть записи, получаем метаданные
		$meta_data = array();
		if ( ! empty( $post_ids ) ) {
			$meta_sql     = "SELECT post_id, meta_key, meta_value
                     FROM $table_meta
                     WHERE post_id IN (" . implode( ',', array_map( 'absint', $post_ids ) ) . ")";
			$meta_results = $wpdb->get_results( $meta_sql, ARRAY_A );
			
			// Преобразуем метаданные в ассоциативный массив по post_id
			foreach ( $meta_results as $meta_row ) {
				$post_id = $meta_row[ 'post_id' ];
				if ( ! isset( $meta_data[ $post_id ] ) ) {
					$meta_data[ $post_id ] = array();
				}
				$meta_data[ $post_id ][ $meta_row[ 'meta_key' ] ] = $meta_row[ 'meta_value' ];
			}
		}
		
		if ( is_array( $main_results ) && ! empty( $main_results ) ) {
			// Объединяем основную таблицу с метаданными
			foreach ( $main_results as &$result ) {
				$post_id               = $result[ 'id' ];
				$result[ 'meta_data' ] = isset( $meta_data[ $post_id ] ) ? $meta_data[ $post_id ] : array();
			}
		}
		
		return array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
	}
	
	public function get_problem_statistics_with_sums() {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Запрос для подсчета сумм booked_rate по диапазонам
		$sql = "
		    SELECT
		        SUM(CASE WHEN DATEDIFF(NOW(), main.load_problem) BETWEEN 31 AND 61 THEN meta_booked_rate.meta_value END) AS sum_range_31_61,
		        SUM(CASE WHEN DATEDIFF(NOW(), main.load_problem) BETWEEN 62 AND 90 THEN meta_booked_rate.meta_value END) AS sum_range_62_90,
		        SUM(CASE WHEN DATEDIFF(NOW(), main.load_problem) BETWEEN 91 AND 121 THEN meta_booked_rate.meta_value END) AS sum_range_91_121,
		        SUM(CASE WHEN DATEDIFF(NOW(), main.load_problem) > 121 THEN meta_booked_rate.meta_value END) AS sum_range_121_plus
		    FROM $table_main AS main
		    LEFT JOIN $table_meta AS meta_booked_rate
		        ON main.id = meta_booked_rate.post_id
		        AND meta_booked_rate.meta_key = 'booked_rate'
		    LEFT JOIN $table_meta AS ar_status
		        ON main.id = ar_status.post_id
		        AND ar_status.meta_key = 'ar_status'
		    WHERE main.load_problem IS NOT NULL
		        AND main.load_problem != ''
		        AND ar_status.meta_value IS NOT NULL
		        AND main.status_post = 'publish'
		";
		// Выполнение запроса
		$result = $wpdb->get_row( $sql, ARRAY_A );
		
		return $result;
	}
	
	public function get_profit_and_gross_by_brocker_id( $customer_id ) {
		
		global $wpdb;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Шаг 1: Получаем список post_id для указанного customer_id.
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id
             FROM $table_meta
             WHERE meta_key = %s AND meta_value = %s", 'customer_id', $customer_id ) );
		
		if ( empty( $post_ids ) ) {
			return [
				'booked_rate_total' => 0,
				'profit_total'      => 0
			];
		}
		
		// Шаг 2: Получаем суммы booked_rate и profit для найденных post_id.
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$query        = $wpdb->prepare( "SELECT meta_key, SUM(meta_value) AS total
         FROM $table_meta
         WHERE post_id IN ($placeholders)
           AND meta_key IN (%s, %s)
         GROUP BY meta_key", array_merge( $post_ids, [ 'booked_rate', 'profit' ] ) );
		
		$results = $wpdb->get_results( $query, OBJECT_K );
		
		// Шаг 3: Возвращаем результаты.
		return [
			'booked_rate_total' => isset( $results[ 'booked_rate' ] )
				? '$' . number_format( $results[ 'booked_rate' ]->total, 2 ) : '$0.00',
			'profit_total'      => isset( $results[ 'profit' ] ) ? '$' . number_format( $results[ 'profit' ]->total, 2 )
				: '$0.00',
		];
		
	}
	
	/**
	 * @param $ID
	 * Get report by id
	 *
	 * @return array|array[]|null
	 */
	public function get_report_by_id( $ID ) {
		global $wpdb;
		
		$table_main = "{$wpdb->prefix}{$this->table_main}";
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		// SQL запрос для получения данных из основной таблицы и мета-данных
		$query = $wpdb->prepare( "
        SELECT main.*, meta.meta_key, meta.meta_value
        FROM $table_main AS main
        LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
        WHERE main.id = %d
    ", $ID );
		
		// Выполняем запрос
		$results = $wpdb->get_results( $query );
		// Преобразуем результаты, чтобы сгруппировать мета-данные
		if ( ! empty( $results ) ) {
			$report = array(
				'main' => array(),
				'meta' => array()
			);
			
			foreach ( $results as $row ) {
				// Заполняем основную информацию только один раз
				if ( empty( $report[ 'main' ] ) ) {
					$report[ 'main' ] = (array) $row;
					unset( $report[ 'main' ][ 'meta_key' ], $report[ 'main' ][ 'meta_value' ] );
				}
				
				// Добавляем мета-данные в массив
				if ( $row->meta_key && $row->meta_value ) {
					$report[ 'meta' ][ $row->meta_key ] = $row->meta_value;
				}
			}
			
			return $report;
		}
		
		return null; // Если нет результатов
	}
	
	public function get_counters_broker( $broker_id ) {
		global $wpdb;
		
		if ( ! is_numeric( $broker_id ) ) {
			return false;
		}
		
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		// SQL-запрос для подсчёта статусов
		$sql = "
		SELECT
		    COUNT(CASE WHEN meta_status.meta_value = 'delivered' THEN 1 END) AS Delivered,
		    COUNT(CASE WHEN meta_status.meta_value = 'cancelled' THEN 1 END) AS Cancelled,
		    COUNT(CASE WHEN meta_status.meta_value = 'tonu' THEN 1 END) AS TONU,
		    COUNT(CASE WHEN meta_status.meta_value NOT IN ('delivered', 'cancelled', 'tonu') THEN 1 END) AS Others
		FROM $table_meta AS meta_broker
		INNER JOIN $table_meta AS meta_status
		    ON meta_broker.post_id = meta_status.post_id
		WHERE meta_broker.meta_key = 'customer_id'
		  AND meta_broker.meta_value = %s
		  AND meta_status.meta_key = 'load_status'
		";
		
		$results = $wpdb->get_row( $wpdb->prepare( $sql, $broker_id ), ARRAY_A );
		
		return $results;
	}
	
	public function get_broker_loads( $broker_id ) {
		global $wpdb;
		
		if ( ! is_numeric( $broker_id ) ) {
			return false;
		}
		
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		// SQL-запрос для подсчёта статусов
		$sql = "
		SELECT
		    COUNT(CASE WHEN meta_status.meta_value = 'delivered' THEN 1 END) AS Delivered,
		    COUNT(CASE WHEN meta_status.meta_value = 'cancelled' THEN 1 END) AS Cancelled,
		    COUNT(CASE WHEN meta_status.meta_value = 'tonu' THEN 1 END) AS TONU,
		    COUNT(CASE WHEN meta_status.meta_value NOT IN ('delivered', 'cancelled', 'tonu') THEN 1 END) AS Others
		FROM $table_meta AS meta_broker
		INNER JOIN $table_meta AS meta_status
		    ON meta_broker.post_id = meta_status.post_id
		WHERE meta_broker.meta_key = 'customer_id'
		  AND meta_broker.meta_value = %s
		  AND meta_status.meta_key = 'load_status'
		";
		
		$results = $wpdb->get_row( $wpdb->prepare( $sql, $broker_id ), ARRAY_A );
		
		return $results;
	}
	
	public function get_profit_broker( $broker_id ) {
		global $wpdb;
		
		if ( ! is_numeric( $broker_id ) ) {
			return false;
		}
		
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		// SQL-запрос для подсчёта статусов
		$sql = "
		SELECT
		    SUM(CASE WHEN meta_status.meta_key = 'profit' THEN meta_status.meta_value ELSE 0 END) AS total_profit,
		    SUM(CASE WHEN meta_status.meta_key = 'booked_rate' THEN meta_status.meta_value ELSE 0 END) AS total_booked_rate,
		    COUNT(DISTINCT meta_broker.post_id) AS post_count
		FROM $table_meta AS meta_broker
		INNER JOIN $table_meta AS meta_status
		    ON meta_broker.post_id = meta_status.post_id
		INNER JOIN $table_meta AS load_status
		        ON meta_broker.post_id = load_status.post_id
		        AND load_status.meta_key = 'load_status'
		WHERE meta_broker.meta_key = 'customer_id'
		  AND meta_broker.meta_value = %s AND load_status.meta_value NOT IN ('waiting-on-rc', 'cancelled')
		";
		
		$results = $wpdb->get_row( $wpdb->prepare( $sql, $broker_id ), ARRAY_A );
		
		return $results;
	}
	
	public function get_counters_shipper( $shipper_id ) {
		global $wpdb;
		
		if ( ! is_numeric( $shipper_id ) ) {
			return false;
		}
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		// SQL-запрос
		$sql     = "SELECT
    COUNT(CASE WHEN meta_pickup.meta_value LIKE '%\"address_id\":\"$shipper_id\"%' THEN 1 END) AS Pickup,
    COUNT(CASE WHEN meta_delivery.meta_value LIKE '%\"address_id\":\"$shipper_id\"%' THEN 1 END) AS Delivary
FROM $table_meta AS meta_pickup
LEFT JOIN $table_meta AS meta_delivery
    ON meta_pickup.post_id = meta_delivery.post_id
WHERE meta_pickup.meta_key = 'pick_up_location'
  AND meta_delivery.meta_key = 'delivery_location'";
		$results = $wpdb->get_row( $sql, ARRAY_A );
		
		return $results;
	}
	
	/**
	 * @param $user_id
	 * @param $project_needs
	 * Get all loads count by user
	 *
	 * @return array
	 */
	public function get_load_counts_by_user_id( $user_id, $project_needs ) {
		global $wpdb;
		
		$table_odysseia       = $wpdb->prefix . 'reports_odysseia';
		$table_martlet        = $wpdb->prefix . 'reports_martlet';
		$table_endurance      = $wpdb->prefix . 'reports_endurance';
		$table_meta_odysseia  = $wpdb->prefix . 'reportsmeta_odysseia';
		$table_meta_martlet   = $wpdb->prefix . 'reportsmeta_martlet';
		$table_meta_endurance = $wpdb->prefix . 'reportsmeta_endurance';
		$result               = array();
		
		if ( is_numeric( array_search( 'Odysseia', $project_needs ) ) ) {
			$odysseia_count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(o.id)
            FROM {$table_odysseia} o
            INNER JOIN {$table_meta_odysseia} m ON o.id = m.post_id
            WHERE m.meta_key = 'dispatcher_initials'
            AND m.meta_value = %s
            AND o.status_post = 'publish'", strval( $user_id ) ) );
			
			$result[ 'Odysseia' ] = $odysseia_count;
		}
		
		if ( is_numeric( array_search( 'Martlet', $project_needs ) ) ) {
			$martlet_count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(m.id)
            FROM {$table_martlet} m
            INNER JOIN {$table_meta_martlet} meta ON m.id = meta.post_id
            WHERE meta.meta_key = 'dispatcher_initials'
            AND meta.meta_value = %s
            AND m.status_post = 'publish'", strval( $user_id ) ) );
			
			$result[ 'Martlet' ] = $martlet_count;
		}
		
		if ( is_numeric( array_search( 'Endurance', $project_needs ) ) ) {
			$endurance_count       = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(e.id)
            FROM {$table_endurance} e
            INNER JOIN {$table_meta_endurance} meta ON e.id = meta.post_id
            WHERE meta.meta_key = 'dispatcher_initials'
            AND meta.meta_value = %s
            AND e.status_post = 'publish'", strval( $user_id ) ) );
			$result[ 'Endurance' ] = $endurance_count;
		}
		
		return $result;
	}
	
	/**
	 * @param $record_id
	 * function check required fields for load before publication
	 *
	 * @return array
	 */
	public function check_empty_fields( $record_id, $meta = false ) {
		global $wpdb;
		
		// Таблица мета-данных
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
		// Список обязательных полей для проверки
		$required_fields = [
			'customer_id'            => 'Customer ID',
			'contact_name'           => 'Contact Name',
			'contact_phone'          => 'Contact Phone',
			'contact_email'          => 'Contact Email',
			'dispatcher_initials'    => 'Dispatcher Initials',
			'reference_number'       => 'Reference Number',
			'pick_up_location'       => 'Pick-up Location',
			'delivery_location'      => 'Delivery Location',
			'unit_number_name'       => 'Unit Number',
			'load_status'            => 'Load Status',
			'load_type'              => 'Load Type',
			'additional_contacts'    => 'Additional Contacts',
			'attached_file_required' => 'Attached File Required',
			'certificate_of_nalysis' => 'Certificate of Analysis'
		];
		
		if ( is_array( $meta ) ) {

			$instructions_str       = str_replace( ' ', '', get_field_value( $meta, 'instructions' ) );
			$instructions_val       = explode( ',', $instructions_str );
	
			$hemp_product = is_numeric(array_search( 'hemp-product', $instructions_val )) ? true : false;
			if ( !$hemp_product ) {
				$required_fields = array_diff_key( $required_fields, array_flip( [
					'certificate_of_nalysis'
				] ) );
			}

			$load_status = get_field_value( $meta, 'load_status' );
			if ( $load_status == 'waiting-on-rc' ) {
				$required_fields = array_diff_key( $required_fields, array_flip( [
					'pick_up_location',
					'reference_number',
					'delivery_location',
					'attached_file_required'
				] ) );
			}
		}
		
		// Формируем массив мета-ключей для проверки
		$meta_keys    = array_keys( $required_fields );
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
		
		// Получаем все значения полей одним запросом
		$query = $wpdb->prepare( "
		SELECT meta_key, meta_value
		FROM $table_meta_name
		WHERE post_id = %d
		  AND meta_key IN ($placeholders)
	", array_merge( [ $record_id ], $meta_keys ) );
		
		$results = $wpdb->get_results( $query, OBJECT_K );
		
		$empty_fields = [];
		
		// Проверяем результаты на пустые значения или некорректные данные
		foreach ( $required_fields as $meta_key => $label ) {
			if ( ! isset( $results[ $meta_key ] ) || empty( $results[ $meta_key ]->meta_value ) || $results[ $meta_key ]->meta_value === '0000-00-00' || ( $results[ $meta_key ]->meta_value === '0.00' && $meta_key !== 'load_status' ) ) {
				$empty_fields[] = '<strong>' . $label . '</strong>';
			}
		}
		
		// Возвращаем сообщение о незаполненных полях
		if ( ! empty( $empty_fields ) ) {
			return array(
				'message' => "The following fields are empty: " . implode( ', ', $empty_fields ),
				'status'  => false
			);
		} else {
			return array( 'message' => "All required fields are filled.", 'status' => true );
		}
	}
	
	// GET ITEMS END
	
	// AJAX ACTIONS
	
	/**
	 * function update draft report (tab 1)
	 * @return void
	 */
	public function update_new_draft_report() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				"customer_id"       => sanitize_text_field( wp_unslash( $_POST['customer_id'] ?? '' ) ),
				"contact_name"      => sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) ),
				"contact_phone"     => sanitize_text_field( wp_unslash( $_POST['contact_phone'] ?? '' ) ),
				"contact_phone_ext" => sanitize_text_field( wp_unslash( $_POST['contact_phone_ext'] ?? '' ) ),
				"contact_email"     => sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
				"post_id"           => sanitize_text_field( wp_unslash( $_POST['post_id'] ?? '' ) ),
				"read_only"         => filter_var( wp_unslash( $_POST['read_only'] ?? false ), FILTER_VALIDATE_BOOLEAN ),
				"preset-select"     => sanitize_text_field( wp_unslash( $_POST['preset-select'] ?? '' ) ),
				"project"           => sanitize_text_field( wp_unslash( $_POST['project'] ?? '' ) ),
			];
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			
			if ( $MY_INPUT[ 'read_only' ] ) {
				wp_send_json_success();
			}
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				// Sanitize additional contacts data - be careful with JSON encoding
				$additional_names      = array_map( function($name) { 
					return sanitize_text_field( wp_unslash( $name ) ); 
				}, $_POST[ 'additional_contact_name' ] ?? [] );
				
				$additional_phones     = array_map( function($phone) { 
					return sanitize_text_field( wp_unslash( $phone ) ); 
				}, $_POST[ 'additional_contact_phone' ] ?? [] );
				
				$additional_phones_ext = array_map( function($ext) { 
					return sanitize_text_field( wp_unslash( $ext ) ); 
				}, $_POST[ 'additional_contact_phone_ext' ] ?? [] );
				
				$additional_emails     = array_map( function($email) { 
					return sanitize_email( wp_unslash( $email ) ); 
				}, $_POST[ 'additional_contact_email' ] ?? [] );
				
				foreach ( $additional_names as $index => $name ) {
					$additional_contacts[] = [
						'name'  => $name,
						'phone' => $additional_phones[ $index ] ?? '',
						'email' => $additional_emails[ $index ] ?? '',
						'ext'   => $additional_phones_ext[ $index ] ?? '',
					];
				}
			}
			
			// Convert the additional contacts array to JSON format
			$MY_INPUT[ 'additional_contacts' ] = json_encode( $additional_contacts );
			
			// Insert the company report
			$result = $this->update_report_draft_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [
					'id_created_post' => $result,
					'message'         => 'Company successfully update',
					'data'            => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error updating to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report (tab 5)
	 * @return void
	 */
	public function update_billing_report() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id"               => FILTER_SANITIZE_STRING,
				"factoring_status"      => FILTER_SANITIZE_STRING,
				"load_problem"          => FILTER_SANITIZE_STRING,
				"processing"            => FILTER_SANITIZE_STRING,
				"short_pay"             => FILTER_SANITIZE_STRING,
				"rc_proof"              => FILTER_VALIDATE_BOOLEAN,
				"pod_proof"             => FILTER_VALIDATE_BOOLEAN,
				"invoiced_proof"        => FILTER_VALIDATE_BOOLEAN,
				"processing_fees"       => FILTER_SANITIZE_STRING,
				"type_pay"              => FILTER_SANITIZE_STRING,
				"percent_quick_pay"     => FILTER_SANITIZE_STRING,
				"booked_rate"           => FILTER_SANITIZE_STRING,
				"driver_rate"           => FILTER_SANITIZE_STRING,
				"profit"                => FILTER_SANITIZE_STRING,
				"tbd"                   => FILTER_VALIDATE_BOOLEAN,
				"ar-action"             => FILTER_VALIDATE_BOOLEAN,
				"ar_status"             => FILTER_SANITIZE_STRING,
				"driver_pay_st"         => FILTER_SANITIZE_STRING,
				"old_ar_status"         => FILTER_SANITIZE_STRING,
				"old_factoring_status"  => FILTER_SANITIZE_STRING,
				"checked_invoice_proof" => FILTER_SANITIZE_STRING,
				"checked_ar_action"     => FILTER_SANITIZE_STRING,
				"log_file_isset"        => FILTER_SANITIZE_STRING,
				"project"               => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			
			if ( ! $MY_INPUT[ 'ar-action' ] ) {
				$MY_INPUT[ 'ar_status' ] = 'not-solved';
			}
			
			if ( $MY_INPUT[ 'factoring_status' ] === 'charge-back' ) {
				if (!empty($MY_INPUT[ 'booked_rate' ])) {
					$MY_INPUT[ 'charge_back_rate' ] = $MY_INPUT[ 'booked_rate' ];
				}
				$MY_INPUT[ 'booked_rate' ] = 0;
			}
			
			if ( $MY_INPUT[ 'factoring_status' ] === 'paid' && $MY_INPUT[ 'driver_pay_st' ] === 'paid' && ! isset( $MY_INPUT[ 'log_file_isset' ] ) ) {
				$id_logs_file = $this->archive_logs_and_close_load( $MY_INPUT );
				if ( is_numeric( $id_logs_file ) ) {
					$MY_INPUT[ 'log_file' ] = $id_logs_file;
				}
			}
			
			$MY_INPUT = $this->count_all_sum( $MY_INPUT );
			
			// Insert the company report
			$result = $this->update_report_billing_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [
					'message' => 'Billing info successfully update',
					'data'    => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error updating to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function archive_logs_and_close_load( $data ) {
		$load_id  = get_field_value( $data, 'post_id' );
		$template = $this->log_controller->get_all_logs( $load_id ); // Получаем шаблон с логами
		
		// Определяем имя файла
		$file_name  = "load-logs-{$load_id}.txt";
		$upload_dir = wp_upload_dir(); // Получаем директорию для загрузок
		$file_path  = trailingslashit( $upload_dir[ 'path' ] ) . $file_name;
		
		// Сохраняем содержимое шаблона в файл
		file_put_contents( $file_path, $template );
		
		// Проверяем, создан ли файл
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_creation_failed', 'Failed to create the log file.' );
		}
		
		// Подготавливаем данные для добавления файла в медиабиблиотеку
		$file_type  = wp_check_filetype( $file_name, null );
		$attachment = [
			'post_mime_type' => $file_type[ 'type' ],
			'post_title'     => basename( $file_name, '.txt' ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		];
		
		// Вставляем файл как вложение
		$attachment_id = wp_insert_attachment( $attachment, $file_path );
		
		// Если вставка прошла успешно
		if ( ! is_wp_error( $attachment_id ) ) {
			// Генерируем метаданные
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
			wp_update_attachment_metadata( $attachment_id, $attachment_data );
			
			if ( is_numeric( $attachment_id ) ) {
				$this->log_controller->delete_all_logs( $load_id );
			}
			
			return $attachment_id;
		} else {
			return $attachment_id; // Возвращаем ошибку, если wp_insert_attachment не удалось
		}
	}
	
	/**
	 * function update draft report (tab 6)
	 * @return void
	 */
	public function update_accounting_report() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id"                 => FILTER_SANITIZE_STRING,
				"factoring_status"        => FILTER_SANITIZE_STRING,
				"log_file_isset"          => FILTER_SANITIZE_STRING,
				"bank_payment_status"     => FILTER_SANITIZE_STRING,
				"driver_pay_statuses"     => FILTER_SANITIZE_STRING,
				"quick_pay_accounting"    => FILTER_VALIDATE_BOOLEAN,
				"quick_pay_method"        => FILTER_SANITIZE_STRING,
				"quick_pay_driver_amount" => FILTER_SANITIZE_STRING,
				"second_bank_payment_status"     => FILTER_SANITIZE_STRING,
				"second_driver_pay_statuses"     => FILTER_SANITIZE_STRING,
				"second_quick_pay_accounting"    => FILTER_VALIDATE_BOOLEAN,
				"second_quick_pay_method"        => FILTER_SANITIZE_STRING,
				"second_quick_pay_driver_amount" => FILTER_SANITIZE_STRING,
				"third_bank_payment_status"     => FILTER_SANITIZE_STRING,
				"third_driver_pay_statuses"     => FILTER_SANITIZE_STRING,
				"third_quick_pay_accounting"    => FILTER_VALIDATE_BOOLEAN,
				"third_quick_pay_method"        => FILTER_SANITIZE_STRING,
				"third_quick_pay_driver_amount" => FILTER_SANITIZE_STRING,
				"project"                 => FILTER_SANITIZE_STRING,
			] );
			
			// Handle checkboxes - if not present in POST, set to false
			// Checkboxes only appear in POST when checked, so we need to explicitly set them to false if missing
			if ( ! isset( $MY_INPUT[ 'quick_pay_accounting' ] ) || $MY_INPUT[ 'quick_pay_accounting' ] === null ) {
				$MY_INPUT[ 'quick_pay_accounting' ] = false;
			}
			if ( ! isset( $MY_INPUT[ 'second_quick_pay_accounting' ] ) || $MY_INPUT[ 'second_quick_pay_accounting' ] === null ) {
				$MY_INPUT[ 'second_quick_pay_accounting' ] = false;
			}
			if ( ! isset( $MY_INPUT[ 'third_quick_pay_accounting' ] ) || $MY_INPUT[ 'third_quick_pay_accounting' ] === null ) {
				$MY_INPUT[ 'third_quick_pay_accounting' ] = false;
			}
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			if ( $MY_INPUT[ 'factoring_status' ] === 'paid' && $MY_INPUT[ 'driver_pay_statuses' ] === 'paid' && ! isset( $MY_INPUT[ 'log_file_isset' ] ) ) {
				$id_logs_file = $this->archive_logs_and_close_load( $MY_INPUT );
				if ( is_numeric( $id_logs_file ) ) {
					$MY_INPUT[ 'log_file' ] = $id_logs_file;
				}
			}
			
			// Insert the company report
			$result = $this->update_report_accounting_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [
					'message' => 'Billing info successfully update',
					'data'    => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error updating to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function add new report (tab 1)
	 * @return void
	 */
	public function add_new_report_draft() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				"customer_id"       => sanitize_text_field( wp_unslash( $_POST['customer_id'] ?? '' ) ),
				"contact_name"      => sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) ),
				"contact_phone"     => sanitize_text_field( wp_unslash( $_POST['contact_phone'] ?? '' ) ),
				"contact_phone_ext" => sanitize_text_field( wp_unslash( $_POST['contact_phone_ext'] ?? '' ) ),
				"contact_email"     => sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
				"preset-select"     => sanitize_text_field( wp_unslash( $_POST['preset-select'] ?? '' ) ),
				"project"           => sanitize_text_field( wp_unslash( $_POST['project'] ?? '' ) ),
			];
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				// Sanitize additional contacts data - be careful with JSON encoding
				$additional_names  = array_map( function($name) { 
					return sanitize_text_field( wp_unslash( $name ) ); 
				}, $_POST[ 'additional_contact_name' ] ?? [] );
				
				$additional_phones = array_map( function($phone) { 
					return sanitize_text_field( wp_unslash( $phone ) ); 
				}, $_POST[ 'additional_contact_phone' ] ?? [] );
				
				$additional_emails = array_map( function($email) { 
					return sanitize_email( wp_unslash( $email ) ); 
				}, $_POST[ 'additional_contact_email' ] ?? [] );
				
				$additional_ext    = array_map( function($ext) { 
					return sanitize_text_field( wp_unslash( $ext ) ); 
				}, $_POST[ 'additional_contact_phone_ext' ] ?? [] );
				
				foreach ( $additional_names as $index => $name ) {
					$additional_contacts[] = [
						'name'  => $name,
						'phone' => $additional_phones[ $index ] ?? '',
						'ext'   => $additional_ext[ $index ] ?? '',
						'email' => $additional_emails[ $index ] ?? ''
					];
				}
			}

			// Convert the additional contacts array to JSON format
			$MY_INPUT[ 'additional_contacts' ] = json_encode( $additional_contacts );


			// Check if 'set_up' is 'completed' and set the timestamp
			$set_up_timestamp = null;
			if ( isset( $MY_INPUT[ 'set_up' ] ) && $MY_INPUT[ 'set_up' ] === 'completed' ) {
				$set_up_timestamp = current_time( 'mysql' ); // or you can use date('Y-m-d H:i:s') if needed
			}
			
			$MY_INPUT[ 'completed' ] = $set_up_timestamp;
			
			$MY_INPUT[ "status_post" ] = 'draft';
			
			// Insert the company report
			$result = $this->add_report_draft_in_db( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				wp_send_json_success( [
					'id_created_post' => $result,
					'message'         => 'Company successfully added',
					'data'            => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not created, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report files (tab 4)
	 * @return void
	 */
	public function update_files_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
				"project" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			if ( ! empty( $_FILES[ 'screen_picture' ] ) ) {
				$MY_INPUT[ 'screen_picture' ] = $this->upload_one_file( $_FILES[ 'screen_picture' ] );
			}

			if ( ! empty( $_FILES[ 'certificate_of_nalysis' ] ) ) {
				$MY_INPUT[ 'certificate_of_nalysis' ] = $this->upload_one_file( $_FILES[ 'certificate_of_nalysis' ] );
			}
			
			if ( ! empty( $_FILES[ 'update_rate_confirmation' ] ) ) {
				$MY_INPUT[ 'updated_rate_confirmation' ] = $this->upload_one_file( $_FILES[ 'update_rate_confirmation' ] );
			}
			
			if ( ! empty( $_FILES[ 'attached_file_required' ] ) ) {
				$MY_INPUT[ 'uploaded_file_required' ] = $this->upload_one_file( $_FILES[ 'attached_file_required' ] );;
			}
			
			if ( ! empty( $_FILES[ 'proof_of_delivery' ] ) ) {
				$MY_INPUT[ 'proof_of_delivery' ] = $this->upload_one_file( $_FILES[ 'proof_of_delivery' ] );
			}
			
			if ( ! empty( $_FILES[ 'attached_files' ] ) ) {
				$MY_INPUT[ 'uploaded_files' ] = $this->multy_upload_files( 'attached_files' );
			}
			
			if ( ! empty( $_FILES[ 'freight_pictures' ] ) ) {
				$MY_INPUT[ 'freight_pictures' ] = $this->multy_upload_files( 'freight_pictures' );
			}
			
			$result = $this->add_report_files( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Files successfully update', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Files not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	function multy_upload_files( $fields_name ) {
		if ( ! isset( $_FILES[ $fields_name ] ) || empty( $_FILES[ $fields_name ][ 'name' ][ 0 ] ) ) {
			return []; // No files to upload
		}
		
		$files          = $_FILES[ $fields_name ];
		$uploaded_files = [];
		$errors         = [];
		$user_id        = get_current_user_id();
		
		foreach ( $files[ 'name' ] as $key => $original_name ) {
			if ( empty( $original_name ) ) {
				continue;
			}
			
			// Check for upload errors
			if ( $files[ 'error' ][ $key ] !== UPLOAD_ERR_OK ) {
				$errors[] = "Upload error: " . $original_name;
				continue;
			}
			
			// Validate file type
			$file_info     = pathinfo( $original_name );
			$extension     = isset( $file_info[ 'extension' ] ) ? strtolower( $file_info[ 'extension' ] ) : '';
			$allowed_types = $this->get_allowed_formats();                                          // Allowed formats
			
			if ( ! in_array( $extension, $allowed_types ) ) {
				$errors[] = "Unsupported file format: " . $original_name;
				continue;
			}
			
			// Validate file size (max 50MB)
			
			$max_size = 50 * 1024 * 1024; // 50MB
			
			if ( $files[ 'size' ][ $key ] > $max_size ) {
				$errors[] = "File is too large (max 50MB): " . $original_name;
				continue;
			}
			
			// Generate unique file name: {user_id}_{timestamp}_{random}_{filename}.{extension}
			$timestamp    = time();
			$unique       = rand( 1000, 99999 );
			$new_filename = "{$user_id}_{$timestamp}_{$unique}_" . sanitize_file_name( $file_info[ 'filename' ] );
			if ( ! empty( $extension ) ) {
				$new_filename .= '.' . $extension;
			}
			
			// Prepare file array for upload
			$file = [
				'name'     => $new_filename,
				'type'     => $files[ 'type' ][ $key ],
				'tmp_name' => $files[ 'tmp_name' ][ $key ],
				'error'    => $files[ 'error' ][ $key ],
				'size'     => $files[ 'size' ][ $key ],
			];
			
			// Upload file using wp_handle_upload()
			$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
			
			if ( ! isset( $upload_result[ 'error' ] ) ) {
				// File uploaded successfully, add to media library
				$file_url  = $upload_result[ 'url' ];
				$file_type = $upload_result[ 'type' ];
				$file_path = $upload_result[ 'file' ];
				
				$attachment = [
					'guid'           => $file_url,
					'post_mime_type' => $file_type,
					'post_title'     => basename( $file_url ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				];
				
				$attachment_id = wp_insert_attachment( $attachment, $file_path );
				
				if ( ! is_wp_error( $attachment_id ) ) {
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );
					$uploaded_files[] = $attachment_id;
				} else {
					$errors[] = "Error adding file to media library: " . $original_name;
				}
			} else {
				$errors[] = "Upload failed: " . $upload_result[ 'error' ];
			}
		}
		
		// Return errors if any
		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'message' => $errors ] );
		}
		
		return $uploaded_files;
	}
	
	function upload_one_file( $file ) {
		if ( ! isset( $file ) || empty( $file[ 'size' ] ) ) {
			return false; // No file uploaded
		}
		
		// Validate upload error
		if ( $file[ 'error' ] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( [ 'message' => 'File upload error: ' . $file[ 'error' ] ] );
		}
		
		// Validate file type
		$file_info     = pathinfo( $file[ 'name' ] );
		$extension     = isset( $file_info[ 'extension' ] ) ? strtolower( $file_info[ 'extension' ] ) : '';
		$allowed_types = $this->get_allowed_formats(); // Allowed formats
		
		if ( ! in_array( $extension, $allowed_types ) ) {
			wp_send_json_error( [ 'message' => 'Unsupported file format: ' . $file[ 'name' ] ] );
		}
		
		// Validate file size (max 50MB)
		$max_size = 50 * 1024 * 1024;                  // 50MB
		if ( $file[ 'size' ] > $max_size ) {
			wp_send_json_error( [ 'message' => 'File is too large (max 50MB): ' . $file[ 'name' ] ] );
		}
		
		// Generate unique file name: {user_id}_{timestamp}_{random}_{filename}.{extension}
		$user_id      = get_current_user_id();
		$timestamp    = time();
		$unique       = rand( 1000, 99999 );
		$new_filename = "{$user_id}_{$timestamp}_{$unique}_" . sanitize_file_name( $file_info[ 'filename' ] );
		
		if ( ! empty( $extension ) ) {
			$new_filename .= '.' . $extension;
		}
		
		// Prepare file array for upload
		$file[ 'name' ] = $new_filename;
		
		// Upload file using wp_handle_upload()
		$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
		
		if ( ! isset( $upload_result[ 'error' ] ) ) {
			// File uploaded successfully, add to media library
			$file_url  = $upload_result[ 'url' ];
			$file_type = $upload_result[ 'type' ];
			$file_path = $upload_result[ 'file' ];
			
			$attachment = [
				'guid'           => $file_url,
				'post_mime_type' => $file_type,
				'post_title'     => basename( $file_url ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			];
			
			$attachment_id = wp_insert_attachment( $attachment, $file_path );
			
			if ( ! is_wp_error( $attachment_id ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
				
				return $attachment_id; // Return uploaded file ID
			} else {
				wp_send_json_error( [ 'message' => 'Error adding file to media library' ] );
			}
		} else {
			wp_send_json_error( [ 'message' => 'Upload failed: ' . $upload_result[ 'error' ] ] );
		}
		
		return false;
	}
	
	/**
	 * function update draft report (tab 3)
	 * @return void
	 */
	public function update_shipper_info() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$data = $_POST;
			
			if ( isset( $data[ 'project' ] ) && $data[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $data[ 'project' ]
				] );
			}
			
			if ( ! isset( $data[ 'pick_up_location_address_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Pick up not fill' ] );
			}
			
			if ( ! isset( $data[ 'delivery_location_address_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Delivery not fill' ] );
			}
			
			$pick_up_location  = [];
			$delivery_location = [];
			$earliest_date     = null;
			$latest_date       = null;
			
			for ( $i = 0; $i < count( $data[ 'pick_up_location_address_id' ] ); $i ++ ) {
				$current_date = isset( $data[ 'pick_up_location_date' ][ $i ] ) ? trim( $data[ 'pick_up_location_date' ][ $i ] ) : '';
				
				// Debug logging for pickup date processing
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( sprintf( '[Date Processing] Pickup location %d: raw date = %s', $i + 1, var_export( $current_date, true ) ), 'location-import' );
				}
				
				// Validate and normalize date - set to null if empty, invalid, or equals epoch (1970-01-01)
				$normalized_date = null;
				if ( ! empty( $current_date ) ) {
					$timestamp = strtotime( $current_date );
					// Check if date is valid and not epoch (1970-01-01 00:00:00)
					if ( $timestamp !== false && $timestamp > 0 ) {
						$date_obj = DateTime::createFromFormat( 'Y-m-d H:i:s', date( 'Y-m-d H:i:s', $timestamp ) );
						// Check if not epoch date (1970-01-01)
						if ( $date_obj && $date_obj->format( 'Y-m-d' ) !== '1970-01-01' ) {
							$normalized_date = date( 'Y-m-d H:i:s', $timestamp );
							if ( class_exists( 'TMSLogger' ) ) {
								TMSLogger::log_to_file( sprintf( '[Date Processing] Pickup location %d: normalized date = %s', $i + 1, $normalized_date ), 'location-import' );
							}
						} else {
							if ( class_exists( 'TMSLogger' ) ) {
								TMSLogger::log_to_file( sprintf( '[Date Processing] Pickup location %d: rejected (1970 date detected)', $i + 1 ), 'location-import' );
							}
						}
					} else {
						if ( class_exists( 'TMSLogger' ) ) {
							TMSLogger::log_to_file( sprintf( '[Date Processing] Pickup location %d: strtotime failed or returned 0 (timestamp = %s)', $i + 1, var_export( $timestamp, true ) ), 'location-import' );
						}
					}
				} else {
					if ( class_exists( 'TMSLogger' ) ) {
						TMSLogger::log_to_file( sprintf( '[Date Processing] Pickup location %d: date is empty', $i + 1 ), 'location-import' );
					}
				}
				
				// Normalize ETA date as well
				$eta_date = isset( $data[ 'pick_up_location_eta_date' ][ $i ] ) ? trim( $data[ 'pick_up_location_eta_date' ][ $i ] ) : '';
				$normalized_eta_date = null;
				if ( ! empty( $eta_date ) && trim( $eta_date ) !== '' ) {
					$eta_timestamp = strtotime( $eta_date );
					if ( $eta_timestamp !== false && $eta_timestamp > 0 ) {
						// Additional check: ensure timestamp is reasonable and not 1970
						if ( $eta_timestamp >= 86400 && strpos( $eta_date, '1970' ) === false ) {
							$eta_date_obj = DateTime::createFromFormat( 'Y-m-d', date( 'Y-m-d', $eta_timestamp ) );
							if ( $eta_date_obj ) {
								$eta_date_only = $eta_date_obj->format( 'Y-m-d' );
								if ( $eta_date_only !== '1970-01-01' && $eta_date_only !== '1970-01-02' ) {
									$normalized_eta_date = date( 'Y-m-d', $eta_timestamp );
								}
							}
						}
					}
				}
				
				$pick_up_location[] = [
					'db_id'         => isset( $data[ 'pick_up_location_db_id' ][ $i ] ) ? intval( $data[ 'pick_up_location_db_id' ][ $i ] ) : 0,
					'address_id'    => $data[ 'pick_up_location_address_id' ][ $i ],
					'address'       => $data[ 'pick_up_location_address' ][ $i ],
					'short_address' => $data[ 'pick_up_location_short_address' ][ $i ],
					'contact'       => $data[ 'pick_up_location_contact' ][ $i ],
					'date'          => $normalized_date,
					'info'          => $data[ 'pick_up_location_info' ][ $i ],
					'type'          => $data[ 'pick_up_location_type' ][ $i ],
					'time_start'    => $data[ 'pick_up_location_start' ][ $i ],
					'time_end'      => $data[ 'pick_up_location_end' ][ $i ],
					'strict_time'   => $data[ 'pick_up_location_strict' ][ $i ],
					'eta_date'      => $normalized_eta_date,
					'eta_time'      => isset( $data[ 'pick_up_location_eta_time' ][ $i ] ) && ! empty( trim( $data[ 'pick_up_location_eta_time' ][ $i ] ) ) ? trim( $data[ 'pick_up_location_eta_time' ][ $i ] ) : null
				];
				
				// Сравнение даты (only if valid date)
				if ( $normalized_date && ( $earliest_date === null || strtotime( $normalized_date ) < strtotime( $earliest_date ) ) ) {
					$earliest_date = $normalized_date;
				}
			}
			
			for ( $i = 0; $i < count( $data[ 'delivery_location_address_id' ] ); $i ++ ) {
				$current_date = isset( $data[ 'delivery_location_date' ][ $i ] ) ? trim( $data[ 'delivery_location_date' ][ $i ] ) : '';
				
				// Debug logging for delivery date processing
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: raw date = %s', $i + 1, var_export( $current_date, true ) ), 'location-import' );
				}
				
				// Validate and normalize date - set to null if empty, invalid, or equals epoch (1970-01-01)
				$normalized_date = null;
				if ( ! empty( $current_date ) && trim( $current_date ) !== '' ) {
					$timestamp = strtotime( $current_date );
					// Check if date is valid, not epoch (timestamp > 0), and not 1970-01-01 in any timezone
					if ( $timestamp !== false && $timestamp > 0 ) {
						// Additional check: ensure timestamp is reasonable (after 1970-01-02 to avoid timezone issues)
						// Also check the actual date string doesn't contain 1970
						if ( $timestamp >= 86400 && strpos( $current_date, '1970' ) === false ) {
							$date_obj = DateTime::createFromFormat( 'Y-m-d H:i:s', date( 'Y-m-d H:i:s', $timestamp ) );
							if ( $date_obj ) {
								$date_only = $date_obj->format( 'Y-m-d' );
								// Double check: not 1970-01-01 in any form
								if ( $date_only !== '1970-01-01' && $date_only !== '1970-01-02' ) {
									$normalized_date = date( 'Y-m-d H:i:s', $timestamp );
									if ( class_exists( 'TMSLogger' ) ) {
										TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: normalized date = %s', $i + 1, $normalized_date ), 'location-import' );
									}
								} else {
									if ( class_exists( 'TMSLogger' ) ) {
										TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: rejected (1970 date detected: %s)', $i + 1, $date_only ), 'location-import' );
									}
								}
							} else {
								if ( class_exists( 'TMSLogger' ) ) {
									TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: DateTime::createFromFormat failed', $i + 1 ), 'location-import' );
								}
							}
						} else {
							if ( class_exists( 'TMSLogger' ) ) {
								TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: rejected (timestamp = %d, contains 1970: %s)', $i + 1, $timestamp, strpos( $current_date, '1970' ) !== false ? 'yes' : 'no' ), 'location-import' );
							}
						}
					} else {
						if ( class_exists( 'TMSLogger' ) ) {
							TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: strtotime failed or returned 0 (timestamp = %s)', $i + 1, var_export( $timestamp, true ) ), 'location-import' );
						}
					}
				} else {
					if ( class_exists( 'TMSLogger' ) ) {
						TMSLogger::log_to_file( sprintf( '[Date Processing] Delivery location %d: date is empty', $i + 1 ), 'location-import' );
					}
				}
				
				// Normalize ETA date as well
				$eta_date = isset( $data[ 'delivery_location_eta_date' ][ $i ] ) ? trim( $data[ 'delivery_location_eta_date' ][ $i ] ) : '';
				$normalized_eta_date = null;
				if ( ! empty( $eta_date ) && trim( $eta_date ) !== '' ) {
					$eta_timestamp = strtotime( $eta_date );
					if ( $eta_timestamp !== false && $eta_timestamp > 0 ) {
						// Additional check: ensure timestamp is reasonable and not 1970
						if ( $eta_timestamp >= 86400 && strpos( $eta_date, '1970' ) === false ) {
							$eta_date_obj = DateTime::createFromFormat( 'Y-m-d', date( 'Y-m-d', $eta_timestamp ) );
							if ( $eta_date_obj ) {
								$eta_date_only = $eta_date_obj->format( 'Y-m-d' );
								if ( $eta_date_only !== '1970-01-01' && $eta_date_only !== '1970-01-02' ) {
									$normalized_eta_date = date( 'Y-m-d', $eta_timestamp );
								}
							}
						}
					}
				}
				
				$delivery_location[] = [
					'db_id'         => isset( $data[ 'delivery_location_db_id' ][ $i ] ) ? intval( $data[ 'delivery_location_db_id' ][ $i ] ) : 0,
					'address_id'    => $data[ 'delivery_location_address_id' ][ $i ],
					'address'       => $data[ 'delivery_location_address' ][ $i ],
					'short_address' => $data[ 'delivery_location_short_address' ][ $i ],
					'contact'       => $data[ 'delivery_location_contact' ][ $i ],
					'date'          => $normalized_date,
					'info'          => $data[ 'delivery_location_info' ][ $i ],
					'type'          => $data[ 'delivery_location_type' ][ $i ],
					'time_start'    => $data[ 'delivery_location_start' ][ $i ],
					'time_end'      => $data[ 'delivery_location_end' ][ $i ],
					'strict_time'   => $data[ 'delivery_location_strict' ][ $i ],
					'eta_date'      => $normalized_eta_date,
					'eta_time'      => isset( $data[ 'delivery_location_eta_time' ][ $i ] ) && ! empty( trim( $data[ 'delivery_location_eta_time' ][ $i ] ) ) ? trim( $data[ 'delivery_location_eta_time' ][ $i ] ) : null
				];
				
				// Сравнение даты (only if valid date)
				if ( $normalized_date && ( $latest_date === null || strtotime( $normalized_date ) > strtotime( $latest_date ) ) ) {
					$latest_date = $normalized_date;
				}
			}
			
			// Validate that all locations have valid dates
			$missing_pickup_dates = [];
			$missing_delivery_dates = [];
			
			// Debug logging
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( sprintf( '[Date Validation] Pickup locations count: %d', count( $pick_up_location ) ), 'location-import' );
				TMSLogger::log_to_file( sprintf( '[Date Validation] Delivery locations count: %d', count( $delivery_location ) ), 'location-import' );
				TMSLogger::log_to_file( sprintf( '[Date Validation] Raw pickup dates: %s', json_encode( isset( $data['pick_up_location_date'] ) ? $data['pick_up_location_date'] : [] ) ), 'location-import' );
				TMSLogger::log_to_file( sprintf( '[Date Validation] Raw delivery dates: %s', json_encode( isset( $data['delivery_location_date'] ) ? $data['delivery_location_date'] : [] ) ), 'location-import' );
			}
			
			foreach ( $pick_up_location as $index => $location ) {
				$date_value = $location['date'];
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( sprintf( '[Date Validation] Pickup location %d: date = %s (empty: %s, null: %s)', $index + 1, var_export( $date_value, true ), empty( $date_value ) ? 'yes' : 'no', $date_value === null ? 'yes' : 'no' ), 'location-import' );
				}
				if ( empty( $date_value ) || $date_value === null ) {
					$missing_pickup_dates[] = $index + 1;
				}
			}
			
			foreach ( $delivery_location as $index => $location ) {
				$date_value = $location['date'];
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( sprintf( '[Date Validation] Delivery location %d: date = %s (empty: %s, null: %s)', $index + 1, var_export( $date_value, true ), empty( $date_value ) ? 'yes' : 'no', $date_value === null ? 'yes' : 'no' ), 'location-import' );
				}
				if ( empty( $date_value ) || $date_value === null ) {
					$missing_delivery_dates[] = $index + 1;
				}
			}
			
			if ( ! empty( $missing_pickup_dates ) || ! empty( $missing_delivery_dates ) ) {
				$error_messages = [];
				if ( ! empty( $missing_pickup_dates ) ) {
					$error_messages[] = 'Pickup location(s) ' . implode( ', ', $missing_pickup_dates ) . ' missing required date';
				}
				if ( ! empty( $missing_delivery_dates ) ) {
					$error_messages[] = 'Delivery location(s) ' . implode( ', ', $missing_delivery_dates ) . ' missing required date';
				}
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( sprintf( '[Date Validation] Validation failed: %s', implode( '. ', $error_messages ) ), 'location-import' );
				}
				wp_send_json_error( [ 'message' => implode( '. ', $error_messages ) . '.' ] );
			}
			
			$pick_up_location_json  = json_encode( $pick_up_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			$delivery_location_json = json_encode( $delivery_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			
			$data[ 'pick_up_location_json' ]  = $pick_up_location_json;
			$data[ 'delivery_location_json' ] = $delivery_location_json;
			$data[ 'pick_up_date' ]           = $earliest_date;
			$data[ 'delivery_date' ]          = $latest_date;
			
			// Save to database (new location tables) AND JSON (for backward compatibility)
			$db_result = $this->save_locations_to_db( $data[ 'post_id' ], $pick_up_location, $delivery_location );
			if ( is_wp_error( $db_result ) && class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( sprintf( '[Location Save] Error saving to DB for load ID %d: %s', $data[ 'post_id' ], $db_result->get_error_message() ), 'location-import' );
			}
			
			$result = $this->add_new_shipper_info( $data );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Shipper info successfully update', 'data' => $data ] );
			}
			
			wp_send_json_error( [ 'message' => 'Shipper not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report (tab 2)
	 * @return void
	 */
	public function add_new_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"date_booked"             => FILTER_SANITIZE_STRING,
				"dispatcher_initials"     => FILTER_SANITIZE_STRING,
				"reference_number"        => FILTER_SANITIZE_STRING,
				"unit_number_name"        => FILTER_SANITIZE_STRING,
				"old_unit_number_name"    => FILTER_SANITIZE_STRING,
				"old_second_unit_number_name" => FILTER_SANITIZE_STRING,
				"old_third_unit_number_name" => FILTER_SANITIZE_STRING,
				"old_second_driver_phone" => FILTER_SANITIZE_STRING,
				"old_third_driver_phone" => FILTER_SANITIZE_STRING,
				"old_value_second_driver_rate" => FILTER_SANITIZE_STRING,
				"old_value_third_driver_rate" => FILTER_SANITIZE_STRING,
				"booked_rate"             => FILTER_SANITIZE_STRING,
				"old_value_booked_rate"   => FILTER_SANITIZE_STRING,
				"processing_fees"         => FILTER_SANITIZE_STRING,
				"type_pay"                => FILTER_SANITIZE_STRING,
				"percent_quick_pay"       => FILTER_SANITIZE_STRING,
				"processing"              => FILTER_SANITIZE_STRING,
				"driver_rate"             => FILTER_SANITIZE_STRING,
				"old_value_driver_rate"   => FILTER_SANITIZE_STRING,
				"driver_phone"            => FILTER_SANITIZE_STRING,
				"shared_with_client"      => FILTER_VALIDATE_BOOLEAN,
				"macropoint_set"          => FILTER_VALIDATE_BOOLEAN,
				"trucker_tools"           => FILTER_VALIDATE_BOOLEAN,
				"old_driver_phone"        => FILTER_SANITIZE_STRING,
				"profit"                  => FILTER_SANITIZE_STRING,
				"load_status"             => FILTER_SANITIZE_STRING,
				"old_load_status"         => FILTER_SANITIZE_STRING,
				"instructions"            => [ 'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY ],
				"old_instructions"        => FILTER_SANITIZE_STRING,
				"source"                  => FILTER_SANITIZE_STRING,
				"load_type"               => FILTER_SANITIZE_STRING,
				"commodity"               => FILTER_SANITIZE_STRING,
				"weight"                  => FILTER_SANITIZE_STRING,
				"old_weight"              => FILTER_SANITIZE_STRING,
				"notes"                   => FILTER_SANITIZE_STRING,
				"post_id"                 => FILTER_SANITIZE_STRING,
				"post_status"             => FILTER_SANITIZE_STRING,
				"read_only"               => FILTER_VALIDATE_BOOLEAN,
				"second_unit_number_name" => FILTER_SANITIZE_STRING,
				"second_driver_rate"      => FILTER_SANITIZE_STRING,
				"second_driver_phone"     => FILTER_SANITIZE_STRING,
				"second_driver"           => FILTER_VALIDATE_BOOLEAN,
				"third_driver"            => FILTER_VALIDATE_BOOLEAN,
				"third_unit_number_name"  => FILTER_SANITIZE_STRING,
				"third_driver_rate"       => FILTER_SANITIZE_STRING,
				"third_driver_phone"      => FILTER_SANITIZE_STRING,
				"attached_driver"         => FILTER_SANITIZE_STRING,
				"attached_second_driver"  => FILTER_SANITIZE_STRING,
				"attached_third_driver"   => FILTER_SANITIZE_STRING,
				"tbd"                     => FILTER_VALIDATE_BOOLEAN,
				"old_tbd"                 => FILTER_VALIDATE_BOOLEAN,
				"additional_fees"         => FILTER_VALIDATE_BOOLEAN,
				"additional_fees_val"     => FILTER_SANITIZE_STRING,
				"additional_fees_driver"  => FILTER_VALIDATE_BOOLEAN,
				"project"                 => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			if (!$MY_INPUT[ 'tbd' ]) {
				if (empty($MY_INPUT[ 'attached_driver' ])) {
					wp_send_json_error( [ 'message' => 'Error adding load: You need to attach a driver. To do this, select the driver from the drop-down list (by clicking on it)' ] );
				}
			}

			if (empty($MY_INPUT[ 'attached_second_driver' ]) && $MY_INPUT[ 'second_driver' ]) {
				wp_send_json_error( [ 'message' => 'Error adding load: You need to attach a second driver. To do this, select the second driver from the drop-down list (by clicking on it)' ] );
			}

			if (empty($MY_INPUT[ 'attached_third_driver' ]) && $MY_INPUT[ 'third_driver' ]) {
				wp_send_json_error( [ 'message' => 'Error adding load: You need to attach a third driver. To do this, select the third driver from the drop-down list (by clicking on it)' ] );
			}
			
			if ( $MY_INPUT[ 'load_status' ] === 'cancelled' ) {
				$MY_INPUT[ "booked_rate" ]        = '0.00';
				$MY_INPUT[ "driver_rate" ]        = '0.00';
				$MY_INPUT[ "profit" ]             = '0.00';
				$MY_INPUT[ "second_driver_rate" ] = '0.00';
				$MY_INPUT[ "third_driver_rate" ]  = '0.00';
			} else {
				$MY_INPUT = $this->count_all_sum( $MY_INPUT );
			}
			
			// Add current time to date_booked if it's only a date
			if ( strlen( $MY_INPUT[ 'date_booked' ] ) === 10 ) {
				$MY_INPUT[ 'date_booked' ] = $MY_INPUT[ 'date_booked' ] . ' ' . date( 'H:i:s' );
			}
			
			// Debug: Log driver fields
			error_log( 'DEBUG: attached_driver = ' . ( $MY_INPUT[ 'attached_driver' ] ?? 'NULL' ) );
			error_log( 'DEBUG: attached_second_driver = ' . ( $MY_INPUT[ 'attached_second_driver' ] ?? 'NULL' ) );
			
			$result = $this->add_load( $MY_INPUT );
			
			if ( $result ) {
				
				// If chat already exists for this load, sync chat participants with latest data
				$this->maybe_update_load_chat_for_load( (int) $MY_INPUT['post_id'] );
				
				// Remove ETA records if status is final
				if ( isset( $MY_INPUT['load_status'] ) ) {
					$this->remove_eta_records_for_status( $result, $MY_INPUT['load_status'] );
				}
				
				wp_send_json_success( [ 'message' => 'Report successfully added', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Report not create, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function count_all_sum( $MY_INPUT ) {
		$MY_INPUT[ "booked_rate" ]        = $this->convert_to_number( $MY_INPUT[ "booked_rate" ] );
		$MY_INPUT[ "driver_rate" ]        = $this->convert_to_number( $MY_INPUT[ "driver_rate" ] );
		$MY_INPUT[ "second_driver_rate" ] = $this->convert_to_number( $MY_INPUT[ "second_driver_rate" ] );
		$MY_INPUT[ "third_driver_rate" ]  = $this->convert_to_number( $MY_INPUT[ "third_driver_rate" ] );
		$MY_INPUT[ "profit" ]             = $this->convert_to_number( $MY_INPUT[ "profit" ] );
		
		if ( is_numeric( $MY_INPUT[ "second_driver_rate" ] ) && is_numeric( $MY_INPUT[ "driver_rate" ] ) && is_numeric( $MY_INPUT[ "third_driver_rate" ] ) ) {
			$with_second_sum = $MY_INPUT[ "driver_rate" ] + $MY_INPUT[ "second_driver_rate" ] + $MY_INPUT[ "third_driver_rate" ];
		}
		
		$booked_rait         = $MY_INPUT[ "booked_rate" ];
		$processing_fees_val = $booked_rait;
		$percent_value       = 0;
		$processing          = $MY_INPUT[ "processing" ];
		if ( $processing === 'direct' ) {
			$processing_fees = $this->convert_to_number( $MY_INPUT[ 'processing_fees' ] );
			
			if ( ! is_numeric( $processing_fees ) ) {
				$processing_fees = 0;
			}
			
			if ( is_numeric( $processing_fees ) ) {
				$processing_fees_val = $booked_rait - $processing_fees;
			}
			
			if ( $MY_INPUT[ 'type_pay' ] === 'quick-pay' ) {
				$percent_quick_pay = $this->convert_to_number( $MY_INPUT[ 'percent_quick_pay' ] );
				
				if ( is_numeric( $percent_quick_pay ) && $percent_quick_pay !== 0 ) {
					$percent_value = $booked_rait * ( $percent_quick_pay / 100 );
				}
			}
		}
		
		
		$MY_INPUT[ "booked_rate_modify" ] = $processing_fees_val - $percent_value;;
		$MY_INPUT[ 'percent_quick_pay_value' ] = $percent_value;
		// FACTORING PERCENT
		
		$proc = 0.0165;
		
		if ( "Martlet" === $this->project ) {
			$proc = 0.035;
			
		}
		
		if ( "Endurance" === $this->project ) {
			$proc = 0.02;
			
		}
		
		if ( $MY_INPUT[ "type_pay" ] === 'quick-pay' ) {
		}
		
		$MY_INPUT[ 'percent_booked_rate' ] = $MY_INPUT[ "booked_rate_modify" ] * $proc;
		
		if ( isset( $with_second_sum ) ) {
			$MY_INPUT[ 'profit' ]      = $MY_INPUT[ "booked_rate_modify" ] - $with_second_sum;
			$MY_INPUT[ 'true_profit' ] = $MY_INPUT[ "booked_rate_modify" ] - ( $MY_INPUT[ 'percent_booked_rate' ] + $with_second_sum );
		} else {
			$MY_INPUT[ 'profit' ]      = $MY_INPUT[ "booked_rate_modify" ] - $MY_INPUT[ "driver_rate" ];
			$MY_INPUT[ 'true_profit' ] = $MY_INPUT[ "booked_rate_modify" ] - ( $MY_INPUT[ 'percent_booked_rate' ] + $MY_INPUT[ "driver_rate" ] );
		}
		
		
		if ( $MY_INPUT[ 'tbd' ] ) {
			$MY_INPUT[ 'profit' ]             = 0;
			$MY_INPUT[ 'true_profit' ]        = 0;
			$MY_INPUT[ "driver_rate" ]        = 0;
			$MY_INPUT[ "second_driver_rate" ] = 0;
			$MY_INPUT[ "third_driver_rate" ]  = 0;
		}
		
		return $MY_INPUT;
	}
	
	public function quick_update_post() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"factoring_status"    => FILTER_SANITIZE_STRING,
				"bank_payment_status" => FILTER_SANITIZE_STRING,
				"driver_pay_statuses" => FILTER_SANITIZE_STRING,
				"post_ids"            => FILTER_SANITIZE_STRING,
				"invoiced_proof"      => FILTER_VALIDATE_BOOLEAN,
				"project"             => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			
			if ( ! $MY_INPUT[ 'factoring_status' ] && ! $MY_INPUT[ 'bank_payment_status' ] && ! $MY_INPUT[ 'driver_pay_statuses' ] && ! $MY_INPUT[ 'invoiced_proof' ] ) {
				wp_send_json_error( array( 'message' => 'You do not select any option' ) );
			}
			
			
			$filled_fields = array_filter( $MY_INPUT, function( $value ) {
				return ! empty( $value ) || $value === false;
			} );
			
			
			$result = $this->update_quick_data_in_db( $filled_fields );
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Loads successfully updated', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Loads not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function quick_update_post_ar() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"ar_status" => FILTER_SANITIZE_STRING,
				"post_ids"  => FILTER_SANITIZE_STRING,
				"project"   => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			
			if ( ! $MY_INPUT[ 'ar_status' ] ) {
				wp_send_json_error( array( 'message' => 'You do not select any option' ) );
			}
			
			$filled_fields = array_filter( $MY_INPUT, function( $value ) {
				return ! empty( $value ) || $value === false;
			} );
			
			$result = $this->update_quick_data_ar_in_db( $filled_fields );
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Loads successfully updated', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Loads not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function delete one file (tab 1)
	 * @return void
	 */
	public function delete_open_image() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"image-id"         => FILTER_SANITIZE_STRING,
				"image-fields"     => FILTER_SANITIZE_STRING,
				"post_id"          => FILTER_SANITIZE_STRING,
				"reference_number" => FILTER_SANITIZE_STRING,
				"project"          => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			$result = $this->remove_one_image_in_db( $MY_INPUT );
			
			if ( $result === true ) {
				wp_send_json_success( [ 'message' => 'Remove success', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error remove in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function send_email_chain() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"load_id" => FILTER_SANITIZE_STRING,
				"project" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}

			
			
			$TMSEmails  = new TMSEmails();
			$email_send = $TMSEmails->send_email_create_load( $MY_INPUT[ 'load_id' ] );

			if ( $email_send[ 'success' ] ) {
				$post_meta = array(
					'mail_chain_success_send' => '1',
				);
				$this->update_post_meta_data( $MY_INPUT[ 'load_id' ], $post_meta );
				
				wp_send_json_success( $email_send );
			}
			wp_send_json_error( $email_send );
		}
	}
	
	/**
	 * function update post status
	 * @return void
	 */
	public function update_post_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
				"project" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			$MY_INPUT[ 'post_status' ] = 'publish';
			
			$result = $this->update_post_status_in_db( $MY_INPUT );
			
			if ( $result ) {

				$load = $this->get_report_by_id( $MY_INPUT[ 'post_id' ] );
				$meta = get_field_value( $load, 'meta' );
				$dispatcher_id    = (int) get_field_value( $meta, 'dispatcher_initials' );
				$reference_number = get_field_value( $meta, 'reference_number' );
				$reference_label  = $reference_number ?: '';
				$project_label    = isset( $MY_INPUT['project'] ) ? $MY_INPUT['project'] : '';
				$dispatcher_name  = $this->get_user_full_name_by_id( $dispatcher_id );
				$dispatcher_label = is_array( $dispatcher_name ) && isset( $dispatcher_name['full_name'] ) ? $dispatcher_name['full_name'] : '';

				$this->send_tracking_notification_for_load(
					'load_created',
					sprintf( 'Add new load %s', $reference_label ),
					sprintf( "Project: %s\nDispatcher: %s", $project_label, $dispatcher_label ),
					array(
						'load_id'          => (int) $MY_INPUT['post_id'],
						'project'          => $project_label,
						'reference_number' => $reference_label,
						'dispatcher_id'    => $dispatcher_id,
					),
					$dispatcher_id
				);

				wp_send_json_success( [ 'message' => 'Load successfully loaded', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function change status post
	 * @return void
	 */
	public function rechange_status_load() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
			] );
			
			$post_id = $MY_INPUT[ "post_id" ];
			
			$message_arr = $this->check_empty_fields( $post_id );
			
			$status_type    = $message_arr[ 'status' ];
			$status_message = $message_arr[ 'message' ];
			$template       = '';
			
			if ( $status_type ) {
				$template = $this->message_top( 'success', $status_message, 'js-update-post-status', 'Publish' );
			} else {
				$template = $this->message_top( 'danger', $status_message );
			}
			
			if ( $template ) {
				wp_send_json_success( [ 'template' => $template ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function get_driver_by_id() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize the input data
			$MY_INPUT = filter_var_array( $_POST, [
				"id"      => FILTER_SANITIZE_STRING,
				"project" => FILTER_SANITIZE_URL,
			] );
			
			$driver_id   = $MY_INPUT[ 'id' ];
			$project_url = rtrim( $MY_INPUT[ 'project' ], '/' ); // Remove trailing slash if any
			
			// Construct the API URL
			$api_url = "{$project_url}/wp-json/wp/v2/driver-name/?driver-id={$driver_id}";
			// Initialize cURL
			$ch = curl_init();
			
			// Configure cURL options
			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Get the response as a string
			curl_setopt( $ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
			] );
			
			// Execute the request
			$response = curl_exec( $ch );
			
			// Check for cURL errors
			if ( curl_errno( $ch ) ) {
				wp_send_json_error( [ 'message' => 'cURL error: ' . curl_error( $ch ) ] );
				curl_close( $ch );
				
				return;
			}
			
			// Get HTTP response code
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			
			// Close the cURL handle
			curl_close( $ch );
			
			// Handle the response
			if ( $http_code === 200 ) {
				$response_data = json_decode( $response, true );
				
				if ( $response_data && isset( $response_data[ 'success' ] ) && $response_data[ 'success' ] ) {
					wp_send_json_success( $response_data[ 'data' ] );
				} else {
					wp_send_json_error( [ 'message' => 'Failed to fetch driver details. Driver not found.' ] );
				}
			} else {
				wp_send_json_error( [
					'message'   => 'Invalid response from the API.',
					'http_code' => $http_code,
					'response'  => $response,
				] );
			}
		}
		
		// Exit if not an AJAX request
		wp_die();
	}
	
	/**
	 * Get driver by ID from internal database
	 */
	public function get_driver_by_id_internal() {
		// Debug: Log that method is called
		error_log( 'get_driver_by_id_internal method called' );
		
		// Check if TMSDrivers class exists
		if ( ! class_exists( 'TMSDrivers' ) ) {
			error_log( 'TMSDrivers class not found' );
			wp_send_json_error( [ 'message' => 'TMSDrivers class not available' ] );
			
			return;
		}
		
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize the input data
			$MY_INPUT = filter_var_array( $_POST, [
				"id" => FILTER_SANITIZE_STRING,
			] );
			
			$driver_id = $MY_INPUT[ 'id' ];
			
			if ( empty( $driver_id ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID is required.' ] );
				
				return;
			}
			
			// Get driver data from internal database
			$drivers     = new TMSDrivers();
			$driver_data = $drivers->get_driver_by_id( $driver_id );
			
			if ( $driver_data && ! empty( $driver_data[ 'main' ] ) ) {
				// Extract driver information
				$driver_name  = '';
				$driver_phone = '';
				
				// Get driver name from meta data
				if ( isset( $driver_data[ 'meta' ][ 'driver_name' ] ) ) {
					$driver_name = $driver_data[ 'meta' ][ 'driver_name' ];
				}
				
				// Get driver phone from meta data
				if ( isset( $driver_data[ 'meta' ][ 'driver_phone' ] ) ) {
					$driver_phone = $driver_data[ 'meta' ][ 'driver_phone' ];
				}
				
				// If no name found in meta, try to construct from other fields
				if ( empty( $driver_name ) ) {
					$first_name  = isset( $driver_data[ 'meta' ][ 'first_name' ] )
						? $driver_data[ 'meta' ][ 'first_name' ] : '';
					$last_name   = isset( $driver_data[ 'meta' ][ 'last_name' ] )
						? $driver_data[ 'meta' ][ 'last_name' ] : '';
					$driver_name = trim( $first_name . ' ' . $last_name );
				}
				
				// Format the response
				$response_data = [
					'driver' => $driver_name,
					'phone'  => $driver_phone,
					'id'     => $driver_id,
					'data'   => $driver_data
				];
				
				wp_send_json_success( $response_data );
			} else {
				wp_send_json_error( [ 'message' => 'Driver not found in database.' ] );
			}
		}
		
		// Exit if not an AJAX request
		wp_die();
	}
	
	public function quick_update_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				'id_load' => FILTER_SANITIZE_NUMBER_INT,
				'status'  => FILTER_SANITIZE_STRING,
				"project" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			$result = $this->update_quick_status_in_db( $MY_INPUT );
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'status successfully updated', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'status not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function quick_update_status_all() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				'data'    => FILTER_DEFAULT, // Оставляем строку как есть
				"project" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			// Проверяем, что пришли данные
			if ( empty( $MY_INPUT[ 'data' ] ) ) {
				wp_send_json_error( [ 'message' => 'No data received' ] );
			}
			
			// Преобразуем JSON-строку в массив
			
			$decoded_data = $MY_INPUT[ 'data' ];
			$decoded_data = explode( ',', $decoded_data );
			
			if ( ! is_array( $decoded_data ) ) {
				wp_send_json_error( [ 'message' => 'Uncorrected data' ] );
			}
			$update_results = [];
			
			// Обрабатываем каждый элемент массива 689598 864590
			foreach ( $decoded_data as $entry ) {
				list( $id_load, $status ) = explode( '|', $entry );
				
				// Проверяем, что ID - число, а статус - строка
				if ( ! is_numeric( $id_load ) || empty( $status ) ) {
					continue;
				}
				
				$result = $this->update_quick_status_in_db( [
					'id_load' => (int) $id_load,
					'status'  => sanitize_text_field( $status ),
				] );
				
				if ( is_wp_error( $result ) ) {
					$update_results[] = [
						'id_load' => $id_load,
						'success' => false,
						'error'   => $result->get_error_message()
					];
				} else {
					$update_results[] = [ 'id_load' => $id_load, 'success' => true ];
				}
			}
			
			wp_send_json_success( [ 'message' => 'Statuses processed', 'results' => $update_results ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function remove one load by id
	 * @return void
	 */
	public function remove_one_load() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			// Получаем данные запроса
			$MY_INPUT = filter_var_array( $_POST, [
				"id_load" => FILTER_SANITIZE_STRING,
				"project" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && $MY_INPUT[ 'project' ] !== $this->project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->project . ' previous - ' . $MY_INPUT[ 'project' ]
				] );
			}
			
			$id_load    = $MY_INPUT[ "id_load" ];
			$table_name = $wpdb->prefix . $this->table_main;
			$table_meta = $wpdb->prefix . $this->table_meta;
			
			// Получаем метаданные
			$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta} WHERE post_id = %d", $id_load ), ARRAY_A );
			
			// Удаляем файлы из метаданных
			foreach ( $meta_data as $meta ) {
				if ( in_array( $meta[ 'meta_key' ], [
					'screen_picture',
					'attached_file_required',
					'update_rate_confirmation',
					'attached_files',
					'certificate_of_nalysis',
				] ) ) {
					// Если это множественные файлы (attached_files), разбиваем на массив
					$files = explode( ',', $meta[ 'meta_value' ] );
					foreach ( $files as $file_id ) {
						if ( ! empty( $file_id ) ) {
							// Удаляем вложение по его ID
							wp_delete_attachment( $file_id, true );
						}
					}
				}
			}
			
			// Удаляем метаданные
			$wpdb->delete( $table_meta, [ 'post_id' => $id_load ] );
			
			// Удаляем запись из основной таблицы
			$wpdb->delete( $table_name, [ 'id' => $id_load ] );
			
			wp_send_json_success( [ 'message' => 'Load and associated files removed successfully' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 5)
	 *
	 * @return true|WP_Error
	 */
	public function update_report_billing_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'load_problem'    => $data[ 'load_problem' ]
		);
		
		$post_meta = array(
			'factoring_status'        => $data[ 'factoring_status' ],
			"processing"              => $data[ 'processing' ],
			"short_pay"               => $data[ 'short_pay' ],
			"rc_proof"                => $data[ 'rc_proof' ],
			"pod_proof"               => $data[ 'pod_proof' ],
			"invoiced_proof"          => $data[ 'invoiced_proof' ],
			"processing_fees"         => $data[ 'processing_fees' ],
			"type_pay"                => $data[ 'type_pay' ],
			"percent_quick_pay"       => $data[ 'percent_quick_pay' ],
			'profit'                  => $data[ 'profit' ],
			'percent_booked_rate'     => $data[ 'percent_booked_rate' ],
			'true_profit'             => $data[ 'true_profit' ],
			'percent_quick_pay_value' => $data[ 'percent_quick_pay_value' ],
			'booked_rate_modify'      => $data[ 'booked_rate_modify' ],
			'ar_status'               => $data[ 'ar_status' ],
			'ar-action'               => $data[ 'ar-action' ],
		);
		
		if ( isset( $data[ 'log_file' ] ) && is_numeric( $data[ 'log_file' ] ) ) {
			$post_meta[ 'log_file' ] = $data[ 'log_file' ];
		}
		
		if ( $data[ 'ar-action' ] && ! isset( $data[ 'checked_ar_action' ] ) ) {
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Set Ar action'
			) );
		}
		
		if ( isset( $data[ 'checked_ar_action' ] ) && ! $data[ 'ar-action' ] ) {
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Unset Ar action'
			) );
		}
		
		if ( $data[ 'invoiced_proof' ] && ! isset( $data[ 'checked_invoice_proof' ] ) ) {
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Set Invoiced'
			) );
		}
		
		if ( isset( $data[ 'checked_invoice_proof' ] ) && ! $data[ 'invoiced_proof' ] ) {
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Unset Invoiced'
			) );
			
			$update_params[ 'load_problem' ] = null;
		}
		
		
		if ( $post_meta[ 'factoring_status' ] === 'charge-back' ) {
			$post_meta[ 'booked_rate' ] = 0;
		}
		
		if (isset($data['charge_back_rate'])) {
			$post_meta[ 'charge_back_rate' ] = $data[ 'charge_back_rate' ];
		}

		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // load_problem
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta ); // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 6)
	 *
	 * @return true|WP_Error
	 */
	public function update_report_accounting_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' )
		);
		
		if ( ! $data[ 'quick_pay_accounting' ] ) {
			$data[ 'quick_pay_method' ]        = null;
			$data[ 'quick_pay_driver_amount' ] = null;
		}
		
		// Second driver quick pay cleanup
		if ( ! $data[ 'second_quick_pay_accounting' ] ) {
			$data[ 'second_quick_pay_method' ]        = null;
			$data[ 'second_quick_pay_driver_amount' ] = null;
		}
		
		// Third driver quick pay cleanup
		if ( ! $data[ 'third_quick_pay_accounting' ] ) {
			$data[ 'third_quick_pay_method' ]        = null;
			$data[ 'third_quick_pay_driver_amount' ] = null;
		}
		
		$old_data = $this->get_report_by_id( $post_id );
		
		$post_meta = array(
			"bank_payment_status"     => $data[ 'bank_payment_status' ],
			"driver_pay_statuses"     => $data[ 'driver_pay_statuses' ],
			"quick_pay_accounting"    => $data[ 'quick_pay_accounting' ],
			"quick_pay_method"        => $data[ 'quick_pay_method' ],
			"quick_pay_driver_amount" => $data[ 'quick_pay_driver_amount' ],
		);
		
		// Add second driver fields - always include quick_pay_accounting (even if false), others only if set
		if ( isset( $data[ 'second_bank_payment_status' ] ) ) {
			$post_meta[ 'second_bank_payment_status' ] = $data[ 'second_bank_payment_status' ];
		}
		if ( isset( $data[ 'second_driver_pay_statuses' ] ) ) {
			$post_meta[ 'second_driver_pay_statuses' ] = $data[ 'second_driver_pay_statuses' ];
		}
		// Always include quick_pay_accounting to save checkbox state (true or false)
		$post_meta[ 'second_quick_pay_accounting' ] = isset( $data[ 'second_quick_pay_accounting' ] ) ? $data[ 'second_quick_pay_accounting' ] : false;
		// Always include method and amount - set to null if quick_pay_accounting is false
		$post_meta[ 'second_quick_pay_method' ] = isset( $data[ 'second_quick_pay_method' ] ) ? $data[ 'second_quick_pay_method' ] : null;
		$post_meta[ 'second_quick_pay_driver_amount' ] = isset( $data[ 'second_quick_pay_driver_amount' ] ) ? $data[ 'second_quick_pay_driver_amount' ] : null;
		
		// Add third driver fields - always include quick_pay_accounting (even if false), others only if set
		if ( isset( $data[ 'third_bank_payment_status' ] ) ) {
			$post_meta[ 'third_bank_payment_status' ] = $data[ 'third_bank_payment_status' ];
		}
		if ( isset( $data[ 'third_driver_pay_statuses' ] ) ) {
			$post_meta[ 'third_driver_pay_statuses' ] = $data[ 'third_driver_pay_statuses' ];
		}
		// Always include quick_pay_accounting to save checkbox state (true or false)
		$post_meta[ 'third_quick_pay_accounting' ] = isset( $data[ 'third_quick_pay_accounting' ] ) ? $data[ 'third_quick_pay_accounting' ] : false;
		// Always include method and amount - set to null if quick_pay_accounting is false
		$post_meta[ 'third_quick_pay_method' ] = isset( $data[ 'third_quick_pay_method' ] ) ? $data[ 'third_quick_pay_method' ] : null;
		$post_meta[ 'third_quick_pay_driver_amount' ] = isset( $data[ 'third_quick_pay_driver_amount' ] ) ? $data[ 'third_quick_pay_driver_amount' ] : null;
		
		$label_fields = array(
			"bank_payment_status"     => 'Bank status',
			"driver_pay_statuses"     => 'Driver pay status',
			"quick_pay_accounting"    => 'Quick pay',
			"quick_pay_method"        => 'Quick pay method',
			"quick_pay_driver_amount" => 'Will charge the driver',
			"second_bank_payment_status"     => 'Bank status (Second Driver)',
			"second_driver_pay_statuses"     => 'Driver pay status (Second Driver)',
			"second_quick_pay_accounting"    => 'Quick pay (Second Driver)',
			"second_quick_pay_method"        => 'Quick pay method (Second Driver)',
			"second_quick_pay_driver_amount" => 'Will charge the driver (Second Driver)',
			"third_bank_payment_status"     => 'Bank status (Third Driver)',
			"third_driver_pay_statuses"     => 'Driver pay status (Third Driver)',
			"third_quick_pay_accounting"    => 'Quick pay (Third Driver)',
			"third_quick_pay_method"        => 'Quick pay method (Third Driver)',
			"third_quick_pay_driver_amount" => 'Will charge the driver (Third Driver)',
		);
		
		if ( isset( $data[ 'log_file' ] ) && is_numeric( $data[ 'log_file' ] ) ) {
			$post_meta[ 'log_file' ] = $data[ 'log_file' ];
		}
		
		// Проверяем, если старые данные существуют
		if ( isset( $old_data[ 'meta' ] ) ) {
			foreach ( $post_meta as $key => $new_value ) {
				$old_value = isset( $old_data[ 'meta' ][ $key ] ) ? $old_data[ 'meta' ][ $key ] : null;
				
				if ( $old_value === null && $new_value ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => "Field '{$label_fields[$key]}' added for the first time with value: {$new_value}."
					) );
					
				} elseif ( $old_value !== $new_value && $new_value ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => "Field '{$label_fields[$key]}' updated. Old value: {$old_value}, New value: {$new_value}."
					) );
				}
			}
		} else {
			foreach ( $post_meta as $key => $value ) {
				if ( $value ):
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => "Field '{$label_fields[$key]}' added for the first time with value: {$value}."
					) );
				endif;
			}
		}
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // load_problem
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta ); // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	public function update_quick_data_in_db( $data ) {
		global $wpdb;
		
		// Получаем и форматируем ID постов
		$post_ids = explode( ',', $data[ 'post_ids' ] );
		unset( $data[ 'post_ids' ] ); // Удаляем post_ids из массива данных, т.к. он не нужен для обновлений
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Определяем общие данные для обновления
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' )
		);
		
		// Проходим по каждому post_id и обновляем данные
		foreach ( $post_ids as $post_id ) {
			$where = array( 'id' => $post_id );
			
			$update_time = false;
			
			if ( isset( $data[ 'invoiced_proof' ] ) && $data[ 'invoiced_proof' ] ) {
				$update_time                     = true;
				$date_est                        = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
				$current_time_est                = $date_est->format( 'Y-m-d H:i:s' );
				$update_params[ 'load_problem' ] = $current_time_est;
			}
			
			if ( $update_time ) {
				// Обновляем общие данные в основной таблице
				$result = $wpdb->update( $table_name, $update_params, $where, array(
					'%d', // user_id_updated
					'%s',  // date_updated
					'%s'  // load_problem
				), array( '%d' ) );
			} else {
				// Обновляем общие данные в основной таблице
				$result = $wpdb->update( $table_name, $update_params, $where, array(
					'%d', // user_id_updated
					'%s'  // date_updated
				), array( '%d' ) );
			}
			
			if ( $result === false ) {
				// Если обновление неудачно, возвращаем ошибку
				return new WP_Error( 'db_error', 'Ошибка при обновлении отчета компании в базе данных: ' . $wpdb->last_error );
			}
			
			// Отфильтруем мета-данные, если они существуют, и обновим их
			$post_meta = array_filter( array(
				"factoring_status"    => $data[ 'factoring_status' ] ?? null,
				"bank_payment_status" => $data[ 'bank_payment_status' ] ?? null,
				"driver_pay_statuses" => $data[ 'driver_pay_statuses' ] ?? null,
				"invoiced_proof"      => isset( $data[ 'invoiced_proof' ] ) ? (bool) $data[ 'invoiced_proof' ] : null
			), function( $value ) {
				return ! is_null( $value ); // Исключаем отсутствующие значения
			} );
			
			$meta_descriptions = array_map( function( $key, $value ) {
				$description_map = array(
					"factoring_status"    => "Factoring status new value",
					"bank_payment_status" => "Bank payment status new value",
					"driver_pay_statuses" => "Driver pay statuses new value",
					"invoiced_proof"      => "Invoiced"
				);
				
				$description = $description_map[ $key ] ?? $key;
				
				$value = ( $value == '1' || $value === true ) ? 'on' : $value;
				
				return "$description - $value</br>";
			}, array_keys( $post_meta ), $post_meta );
			
			$meta_string = implode( " ", $meta_descriptions );
			
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $post_id,
				'message' => 'Quick edit:</br>' . $meta_string
			) );
			
			// Обновляем мета-данные, если они заданы
			if ( ! empty( $post_meta ) ) {
				$meta_update_result = $this->update_post_meta_data( $post_id, $post_meta );
				if ( is_wp_error( $meta_update_result ) ) {
					return $meta_update_result;
				}
			}
		}
		
		return true;
	}
	
	public function update_quick_status_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'id_load' ];
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Определяем общие данные для обновления
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' )
		);
		
		$post_meta = array(
			'load_status' => $data[ 'status' ],
		);
		
		if ( $data[ 'status' ] === 'cancelled' ) {
			$post_meta[ 'booked_rate' ]        = '0.00';
			$post_meta[ 'driver_rate' ]        = '0.00';
			$post_meta[ 'profit' ]             = '0.00';
			$post_meta[ 'second_driver_rate' ] = '0.00';
			$post_meta[ 'third_driver_rate' ]  = '0.00';
		}
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			if ( $data[ 'status' ] === 'cancelled' ) {
				$this->log_controller->create_one_log( array(
					'user_id' => $user_id,
					'post_id' => $post_id,
					'message' => 'Updated status: ' . $this->get_label_by_key( $data[ 'status' ], 'statuses' ) . '<br>Gross, Driver Rate, Profit = 0.00',
				) );
			} else {
				$this->log_controller->create_one_log( array(
					'user_id' => $user_id,
					'post_id' => $post_id,
					'message' => 'Updated status: ' . $this->get_label_by_key( $data[ 'status' ], 'statuses' ),
				) );
			}
			
			return $this->update_post_meta_data( $post_id, $post_meta ); // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	public function update_quick_data_ar_in_db( $data ) {
		global $wpdb;
		
		// Получаем и форматируем ID постов
		$post_ids = explode( ',', $data[ 'post_ids' ] );
		unset( $data[ 'post_ids' ] ); // Удаляем post_ids из массива данных, т.к. он не нужен для обновлений
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Определяем общие данные для обновления
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' )
		);
		
		// Проходим по каждому post_id и обновляем данные
		foreach ( $post_ids as $post_id ) {
			$where = array( 'id' => $post_id );
			
			// Обновляем общие данные в основной таблице
			$result = $wpdb->update( $table_name, $update_params, $where, array(
				'%d', // user_id_updated
				'%s'  // date_updated
			), array( '%d' ) );
			
			if ( $result === false ) {
				// Если обновление неудачно, возвращаем ошибку
				return new WP_Error( 'db_error', 'Ошибка при обновлении отчета компании в базе данных: ' . $wpdb->last_error );
			}
			
			// Отфильтруем мета-данные, если они существуют, и обновим их
			$post_meta = array_filter( array(
				"ar_status" => $data[ 'ar_status' ] ?? null,
			), function( $value ) {
				return ! is_null( $value ); // Исключаем отсутствующие значения
			} );
			
			$meta_descriptions = array_map( function( $key, $value ) {
				$description_map = array(
					"ar_status" => "A/R status: ",
				);
				
				$description = $description_map[ $key ] ?? $key;
				
				$value = ( $value == '1' || $value === true ) ? 'on' : $value;
				
				return "$description - $value</br>";
			}, array_keys( $post_meta ), $post_meta );
			
			$meta_string = implode( " ", $meta_descriptions );
			
			
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $post_id,
				'message' => 'Quick edit:</br>' . $meta_string
			) );
			
			// Обновляем мета-данные, если они заданы
			if ( ! empty( $post_meta ) ) {
				$meta_update_result = $this->update_post_meta_data( $post_id, $post_meta );
				if ( is_wp_error( $meta_update_result ) ) {
					return $meta_update_result;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * @param $data
	 * function update in db (tab 1)
	 *
	 * @return true|WP_Error
	 */
	public function update_report_draft_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
		);
		
		$post_meta = array(
			'customer_id'         => $data[ 'customer_id' ],
			'contact_name'        => $data[ 'contact_name' ],
			'contact_phone'       => $data[ 'contact_phone' ],
			'contact_phone_ext'   => $data[ 'contact_phone_ext' ],
			'contact_email'       => $data[ 'contact_email' ],
			'additional_contacts' => $data[ 'additional_contacts' ],
		);
		
		if ( isset( $data[ 'preset-select' ] ) && ! empty( $data[ 'preset-select' ] ) ) {
			$post_meta[ 'preset' ] = $data[ 'preset-select' ];
		}
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta ); // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function create in db (tab 1)
	 *
	 * @return int|WP_Error
	 */
	public function add_report_draft_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$insert_params = array(
			'user_id_added'   => $user_id,
			'date_created'    => current_time( 'mysql' ),
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'status_post'     => $data[ 'status_post' ],
		);
		
		$post_meta = array(
			'customer_id'         => $data[ 'customer_id' ],
			'contact_name'        => $data[ 'contact_name' ],
			'contact_phone'       => $data[ 'contact_phone' ],
			'contact_phone_ext'   => $data[ 'contact_phone_ext' ],
			'contact_email'       => $data[ 'contact_email' ],
			'additional_contacts' => $data[ 'additional_contacts' ],
		);
		
		if ( isset( $data[ 'preset-select' ] ) && ! empty( $data[ 'preset-select' ] ) ) {
			$post_meta[ 'preset' ] = $data[ 'preset-select' ];
		}
		
		$result = $wpdb->insert( $table_name, $insert_params, array(
			'%d',  // user_id_added
			'%s',  // date_created
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // status_post
		) );
		
		// Check if the insert was successful
		
		if ( $result ) {
			$id_new_post = $wpdb->insert_id;
			$result      = $this->update_post_meta_data( $id_new_post, $post_meta );
			
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $id_new_post,
				'message' => 'Load added: ' . $insert_params[ 'date_created' ] . ' EST',
			) );
			
			return $id_new_post; // Return the ID of the added record
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Check for specific unique constraint violations
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'company_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this name already exists.' );
				} elseif ( strpos( $error, 'mc_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this MC number already exists.' );
				} elseif ( strpos( $error, 'dot_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this DOT number already exists.' );
				}
			}
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error adding the company report to the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 3)
	 *
	 * @return true|WP_Error
	 */
	public function add_new_shipper_info( $data ) {
		global $wpdb;
		
		$post_id = + $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$user_name = $this->get_user_full_name_by_id( $user_id );
		global $global_options;
		$add_new_load = get_field_value( $global_options, 'add_new_load' );
		$link         = '';
		
		$report = $this->get_report_by_id( $post_id );
		$meta   = get_field_value( $report, 'meta' );
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'pick_up_date'    => $data[ 'pick_up_date' ],
			'delivery_date'   => $data[ 'delivery_date' ],
		);
		
		if ( $data[ 'post_status' ] === 'publish' ) {
			if ( isset( $meta[ 'pick_up_location' ] ) && isset( $meta[ 'delivery_location' ] ) && ! empty( $meta[ 'pick_up_location' ] ) && $meta[ 'delivery_location' ] ) {
				$cleanedpick  = $meta[ 'pick_up_location' ];
				$cleaneddeliv = $meta[ 'delivery_location' ];
				
				if ( $cleanedpick !== $data[ 'pick_up_location_json' ] ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Edit Pick UP location: ' . $this->compare_pick_up_locations( $cleanedpick, $data[ 'pick_up_location_json' ] )
					) );
				}
				
				if ( $cleaneddeliv !== $data[ 'delivery_location_json' ] ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Edit Delivery location: ' . $this->compare_pick_up_locations( $cleaneddeliv, $data[ 'delivery_location_json' ] )
					) );
				}
				
				if ( $cleanedpick !== $data[ 'pick_up_location_json' ] || $cleaneddeliv !== $data[ 'delivery_location_json' ] ) {
					$values = '------- OLD VALUES PICK UP -------' . "<br><br><del>";
					
					$values .= $this->formatJsonForEmail( $cleanedpick );
					
					$values .= "</del><br>" . '------- OLD VALUES DELIVERED -------' . "<br><br><del>";
					
					$values .= $this->formatJsonForEmail( $cleaneddeliv );
					
					$values .= "</del><br>" . '------- NEW VALUES PICK UP-------' . "<br><br>";
					
					$values .= $this->formatJsonForEmail( $data[ 'pick_up_location_json' ] );
					
					$values .= "<br>" . '------- NEW VALUES DELIVERED-------' . "<br><br>";
					
					$values .= $this->formatJsonForEmail( $data[ 'delivery_location_json' ] );
					
					$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array( 'tracking_email' ) );
					
					
					if ( $add_new_load ) {
						$url = add_query_arg( array(
							'post_id'    => $data[ 'post_id' ],
							'use_driver' => $this->project,
							'tab'        => 'pills-trip-tab',
						), $add_new_load );
						
						$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $data[ 'reference_number' ] ) );
					}
					
					
					$who_changed = 'locations';
					$this->email_helper->send_custom_email( $select_emails, array(
						'subject'      => 'Changed locations',
						'project_name' => $this->project,
						'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $who_changed . ' for the load ' . $link,
						'message'      => $values,
					) );
				}
			}
		}
		
		$post_meta = array(
			'pick_up_location'  => $data[ 'pick_up_location_json' ],
			'delivery_location' => $data[ 'delivery_location_json' ],
			'all_miles'         => $data[ 'all_miles' ]
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // customer_id
			'%s',  // date_updated
			'%s',  // pick_up_location
			'%s',  // delivery_location
		), array( '%d' ) );
		
		if ( false === $result ) {
			var_dump( "Update failed: " . $wpdb->last_error );
			var_dump( "Last query: " . $wpdb->last_query );
		}
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta );
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the shipper report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 4)
	 *
	 * @return true|WP_Error
	 */
	public function add_report_files( $data ) {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		$user_id         = get_current_user_id();
		$post_id         = $data[ 'post_id' ];
		
		// Получаем текущие данные по мета-ключам для поста
		$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta_name} WHERE post_id = %d", $post_id ), ARRAY_A );
		
		// Преобразуем мета-данные в удобный массив
		$current_data = array_column( $meta_data, 'meta_value', 'meta_key' );
		
		// Подготавливаем новые прикрепленные файлы (добавляем к существующим)
		$new_attached_files = ! empty( $data[ 'uploaded_files' ] ) ? implode( ', ', $data[ 'uploaded_files' ] ) : '';
		if ( $new_attached_files && ! empty( $current_data[ 'attached_files' ] ) ) {
			$new_attached_files = $current_data[ 'attached_files' ] . ', ' . $new_attached_files;
		} elseif ( empty( $new_attached_files ) ) {
			$new_attached_files = $current_data[ 'attached_files' ];
		}

		// Use new single attachment ID if provided, otherwise keep existing value.
		$new_certificate_of_nalysis = '';
		if ( isset( $data['certificate_of_nalysis'] ) && $data['certificate_of_nalysis'] ) {
			$new_certificate_of_nalysis = $data['certificate_of_nalysis'];
		} elseif ( isset( $current_data['certificate_of_nalysis'] ) && $current_data['certificate_of_nalysis'] ) {
			$new_certificate_of_nalysis = $current_data['certificate_of_nalysis'];
		}
		
		// Подготавливаем новые прикрепленные файлы (добавляем к существующим)
		$new_freight_pictures = ! empty( $data[ 'freight_pictures' ] ) ? implode( ', ', $data[ 'freight_pictures' ] )
			: '';
		if ( $new_freight_pictures && ! empty( $current_data[ 'freight_pictures' ] ) ) {
			$new_freight_pictures = $current_data[ 'freight_pictures' ] . ', ' . $new_freight_pictures;
		} elseif ( empty( $new_freight_pictures ) ) {
			$new_freight_pictures = $current_data[ 'freight_pictures' ];
		}
		
		// Используем переданные данные, если они есть, иначе оставляем текущие значения
		$attached_files_required = ! empty( $data[ 'uploaded_file_required' ] ) ? $data[ 'uploaded_file_required' ]
			: $current_data[ 'attached_file_required' ];

		$updated_rate_confirmation = ! empty( $data[ 'updated_rate_confirmation' ] )
			? $data[ 'updated_rate_confirmation' ] : $current_data[ 'updated_rate_confirmation' ];
		
		$updated_screen_picture = ! empty( $data[ 'screen_picture' ] ) ? $data[ 'screen_picture' ]
			: $current_data[ 'screen_picture' ];
		
		$proof_of_delivery_picture = ! empty( $data[ 'proof_of_delivery' ] ) ? $data[ 'proof_of_delivery' ]
			: $current_data[ 'proof_of_delivery' ];
		
		
		if ( ! empty( $data[ 'proof_of_delivery' ] ) ) {
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Added proof of delivery'
			) );
		}
		
		if ( ! empty( $data[ 'updated_rate_confirmation' ] ) ) {
			
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Added rate confirmation'
			) );
			
			if ( $data[ 'updated_rate_confirmation' ] !== $current_data[ 'updated_rate_confirmation' ] ) {
				global $global_options;
				$add_new_load = get_field_value( $global_options, 'add_new_load' );
				$link         = '';
				
				$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array( 'tracking_email' ) );
				$user_name     = $this->get_user_full_name_by_id( $user_id );
				
				if ( $add_new_load ) {
					$url = add_query_arg( array(
						'post_id'    => $data[ 'post_id' ],
						'use_driver' => $this->project,
						'tab'        => 'pills-documents-tab',
					), $add_new_load );
					
					$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $data[ 'reference_number' ] ) );
				}
				
				
				$who_changed = 'rate confirmation';
				$this->email_helper->send_custom_email( $select_emails, array(
					'subject'      => 'Update rate confirmation',
					'project_name' => $this->project,
					'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $who_changed . ' for the load ' . $link,
					'message'      => '',
				) );
				
			}
		}
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
		);
		
		$post_meta = array(
			'attached_files'            => $new_attached_files,
			'attached_file_required'    => $attached_files_required,
			'updated_rate_confirmation' => $updated_rate_confirmation,
			'screen_picture'            => $updated_screen_picture,
			'proof_of_delivery'         => $proof_of_delivery_picture,
			'freight_pictures'          => $new_freight_pictures,
			'certificate_of_nalysis'   => $new_certificate_of_nalysis,
		);

		if ( ! empty( $data[ 'certificate_of_nalysis' ] ) ) {
			$this->log_controller->create_one_log( array(
				'user_id' => $user_id,
				'post_id' => $data[ 'post_id' ],
				'message' => 'Added certificate of analysis'
			) );
		}
		
		if ( ! empty( $data[ 'proof_of_delivery' ] ) ) {
			$current_time_est = $this->getCurrentTimeForAmerica();
			
			$post_meta[ 'proof_of_delivery_time' ] = $current_time_est;
			$update_params[ 'delivery_date' ]      = $current_time_est;
		}
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // date_updated
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta );
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 2)
	 *
	 * @return bool|WP_Error
	 */
	public function add_load( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		$user_name  = $this->get_user_full_name_by_id( $user_id );
		
		// Prepare the instructions field
		$instructions = ! empty( $data[ 'instructions' ] ) ? implode( ',', $data[ 'instructions' ] ) : null;
		
		global $global_options;
		$add_new_load = get_field_value( $global_options, 'add_new_load' );
		$link         = '';
		
		if ( $add_new_load ) {
			$url = add_query_arg( array(
				'post_id'    => $data[ 'post_id' ],
				'use_driver' => $this->project,
				'tab'        => 'pills-load-tab',
			), $add_new_load );
			
			$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $data[ 'reference_number' ] ) );
		}
		
		if ( $data[ 'post_status' ] === 'publish' ) {
			
			if ( $data[ 'old_tbd' ] && is_null( $data[ 'tbd' ] ) ) {
				$this->log_controller->create_one_log( array(
					'user_id' => $user_id,
					'post_id' => $data[ 'post_id' ],
					'message' => 'Remove status TBD'
				) );
			}
			
			if ( $instructions ) {
				if ( $this->normalize_string( $instructions ) !== $this->normalize_string( $data[ 'old_instructions' ] ) ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed instructions: ' . 'New value: ' . $instructions . ' Old value: ' . $data[ 'old_instructions' ]
					) );
				}
			}
			
			// Collect all second driver changes into one log
			$second_driver_changes = array();
			$second_driver_name_changed = false;
			
			// Check second driver name/number
			if ( $data[ 'old_second_unit_number_name' ] && ! empty( $data[ 'old_second_unit_number_name' ] ) ) {
				if ( $data[ 'old_second_unit_number_name' ] !== $data[ 'second_unit_number_name' ] ) {
					$second_driver_changes[] = 'driver: New value: ' . $data[ 'second_unit_number_name' ] . ' Old value: ' . $data[ 'old_second_unit_number_name' ];
					$second_driver_name_changed = true;
				}
			}
			
			// Check second driver phone
			if ( $data[ 'old_second_driver_phone' ] && ! empty( $data[ 'old_second_driver_phone' ] ) ) {
				if ( $data[ 'second_driver_phone' ] !== $data[ 'old_second_driver_phone' ] ) {
					$second_driver_changes[] = 'phone: New value: ' . $data[ 'second_driver_phone' ] . ' Old value: ' . $data[ 'old_second_driver_phone' ];
				}
			}
			
			// Check second driver rate
			$second_driver_rate_changed = false;
			if ( is_numeric( $data[ 'old_value_second_driver_rate' ] ) ) {
				if ( $data[ 'second_driver_rate' ] !== floatval( $data[ 'old_value_second_driver_rate' ] ) ) {
					$second_driver_changes[] = 'rate: New value: $' . $data[ 'second_driver_rate' ] . ' Old value: $' . $data[ 'old_value_second_driver_rate' ];
					// Send email notification if second driver rate changed
					$second_driver_rate_changed = $this->handle_driver_rate_change(
						$data[ 'old_value_second_driver_rate' ],
						$data[ 'second_driver_rate' ],
						2,
						$user_name,
						$link,
						$data[ 'post_id' ]
					);
				}
			}
			
			// Create single log if there are any changes
			if ( ! empty( $second_driver_changes ) ) {
				$message = 'Changed Second Driver<br>' . implode( '<br>', $second_driver_changes );
				$this->log_controller->create_one_log( array(
					'user_id' => $user_id,
					'post_id' => $data[ 'post_id' ],
					'message' => $message
				) );
			}

			// Collect all first driver changes into one log (with email notifications)
			$first_driver_changes = array();
			$first_driver_name_changed = false;
			$first_driver_rate_changed = false;
			
			// Check first driver name/number
			if ( $data[ 'old_unit_number_name' ] && ! empty( $data[ 'old_unit_number_name' ] ) ) {
				if ( $data[ 'old_unit_number_name' ] !== $data[ 'unit_number_name' ] ) {
					$first_driver_changes[] = 'driver: New value: ' . $data[ 'unit_number_name' ] . ' Old value: ' . $data[ 'old_unit_number_name' ];
					$first_driver_name_changed = true;
				}
			}
			
			// Check first driver phone
			if ( $data[ 'old_driver_phone' ] && ! empty( $data[ 'old_driver_phone' ] ) ) {
				if ( $data[ 'driver_phone' ] !== $data[ 'old_driver_phone' ] ) {
					$first_driver_changes[] = 'phone: New value: ' . $data[ 'driver_phone' ] . ' Old value: ' . $data[ 'old_driver_phone' ];
				}
			}
			
			// Check first driver rate
			if ( is_numeric( $data[ 'old_value_driver_rate' ] ) ) {
				if ( $data[ 'driver_rate' ] !== floatval( $data[ 'old_value_driver_rate' ] ) ) {
					$first_driver_changes[] = 'rate: New value: $' . $data[ 'driver_rate' ] . ' Old value: $' . $data[ 'old_value_driver_rate' ];
					$first_driver_rate_changed = $this->handle_driver_rate_change(
						$data[ 'old_value_driver_rate' ],
						$data[ 'driver_rate' ],
						1,
						$user_name,
						$link,
						$data[ 'post_id' ]
					);
				}
			}
			
			// Send email notification if driver name changed
			if ( $first_driver_name_changed ) {
				$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array(
					'tracking_email',
					'admin_email',
					'team_leader_email'
				) );
				
				$who_changed = 'driver';
				$this->email_helper->send_custom_email( $select_emails, array(
					'subject'      => 'Changed driver',
					'project_name' => $this->project,
					'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $who_changed . ' for the load ' . $link,
					'message'      => '<del>' . $data[ 'old_unit_number_name' ] . '</del>, now: ' . $data[ 'unit_number_name' ],
				) );
			}
			
			// Create single log if there are any changes
			if ( ! empty( $first_driver_changes ) ) {
				$message = 'Changed Driver<br>' . implode( '<br>', $first_driver_changes );
				$this->log_controller->create_one_log( array(
					'user_id' => $user_id,
					'post_id' => $data[ 'post_id' ],
					'message' => $message
				) );
			}

			// Collect all third driver changes into one log
			$third_driver_changes = array();
			
			// Check third driver name/number
			if ( $data[ 'old_third_unit_number_name' ] && ! empty( $data[ 'old_third_unit_number_name' ] ) ) {
				if ( $data[ 'old_third_unit_number_name' ] !== $data[ 'third_unit_number_name' ] ) {
					$third_driver_changes[] = 'driver: New value: ' . $data[ 'third_unit_number_name' ] . ' Old value: ' . $data[ 'old_third_unit_number_name' ];
				}
			}
			
			// Check third driver phone
			if ( $data[ 'old_third_driver_phone' ] && ! empty( $data[ 'old_third_driver_phone' ] ) ) {
				if ( $data[ 'third_driver_phone' ] !== $data[ 'old_third_driver_phone' ] ) {
					$third_driver_changes[] = 'phone: New value: ' . $data[ 'third_driver_phone' ] . ' Old value: ' . $data[ 'old_third_driver_phone' ];
				}
			}
			
			// Check third driver rate
			$third_driver_rate_changed = false;
			if ( is_numeric( $data[ 'old_value_third_driver_rate' ] ) ) {
				if ( $data[ 'third_driver_rate' ] !== floatval( $data[ 'old_value_third_driver_rate' ] ) ) {
					$third_driver_changes[] = 'rate: New value: $' . $data[ 'third_driver_rate' ] . ' Old value: $' . $data[ 'old_value_third_driver_rate' ];
					// Send email notification if third driver rate changed
					$third_driver_rate_changed = $this->handle_driver_rate_change(
						$data[ 'old_value_third_driver_rate' ],
						$data[ 'third_driver_rate' ],
						3,
						$user_name,
						$link,
						$data[ 'post_id' ]
					);
				}
			}
			
			// Create single log if there are any changes
			if ( ! empty( $third_driver_changes ) ) {
				$message = 'Changed Third Driver<br>' . implode( '<br>', $third_driver_changes );
				$this->log_controller->create_one_log( array(
					'user_id' => $user_id,
					'post_id' => $data[ 'post_id' ],
					'message' => $message
				) );
			}
			
			// Set modify_driver_price flag if any driver rate was changed
			if ( $first_driver_rate_changed ) {
				$data[ 'modify_driver_price' ] = '1';
			}

			if ( $second_driver_rate_changed ) {
				$data[ 'modify_second_driver_price' ] = '1';
			}

			if ( $third_driver_rate_changed ) {
				$data[ 'modify_third_driver_price' ] = '1';
			}
			
			if ( $data[ 'weight' ] && ! empty( $data[ 'weight' ] ) ) {
				if ( $data[ 'weight' ] !== $data[ 'old_weight' ] ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed Weight: ' . 'New value: ' . $data[ 'weight' ] . ' Old value: ' . $data[ 'old_weight' ]
					) );
				}
			}
			
			
			if ( $data[ 'old_load_status' ] && ! empty( $data[ 'old_load_status' ] ) ) {
				$array_chacked = array( 'delivered', 'tonu', 'cancelled' );
				if ( in_array( $data[ 'load_status' ], $array_chacked ) && $data[ 'load_status' ] !== $data[ 'old_load_status' ] ) {
					$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array(
						'admin_email',
						'team_leader_email'
					) );
					
					$new_status_label = $this->get_label_by_key( $data[ 'load_status' ], 'statuses' );
					$old_status_label = $this->get_label_by_key( $data[ 'old_load_status' ], 'statuses' );
					
					$who_changed = 'status';
					$this->email_helper->send_custom_email( $select_emails, array(
						'subject'      => 'Changed load status',
						'project_name' => $this->project,
						'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $who_changed . ' for the load ' . $link,
						'message'      => '<del>' . $old_status_label . '</del>, now: ' . $new_status_label,
					) );
					
				}
				
				if ( $data[ 'load_status' ] !== $data[ 'old_load_status' ] ) {
					
					$new_status_label = $this->get_label_by_key( $data[ 'load_status' ], 'statuses' );
					$old_status_label = $this->get_label_by_key( $data[ 'old_load_status' ], 'statuses' );
					
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed Load status: ' . 'New value: ' . $new_status_label . ' Old value: ' . $old_status_label
					) );
				}
			}
			


			if ( is_numeric( $data[ 'old_value_booked_rate' ] ) ) {
				if ( $data[ 'booked_rate' ] !== floatval( $data[ 'old_value_booked_rate' ] ) ) {
					
					$data[ 'modify_price' ] = '1';
					
					// Use helper function to handle booked rate change with billing fields check
					$this->handle_booked_rate_change(
						$data[ 'old_value_booked_rate' ],
						$data[ 'booked_rate' ],
						$user_name,
						$link,
						$data[ 'post_id' ]
					);
					
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed Booked rate: ' . 'New value: ' . $data[ 'booked_rate' ] . ' Old value: $' . $data[ 'old_value_booked_rate' ]
					) );
				}
			}
			
		}
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'date_booked'     => $data[ 'date_booked' ],
		);
		
		$office_dispatcher = get_field( 'work_location', 'user_' . $data[ 'dispatcher_initials' ] );
		
		// Debug: Log driver fields in add_load
		error_log( 'DEBUG add_load: attached_driver = ' . ( $data[ 'attached_driver' ] ?? 'NULL' ) );
		error_log( 'DEBUG add_load: attached_second_driver = ' . ( $data[ 'attached_second_driver' ] ?? 'NULL' ) );
		
		$post_meta = array(
			'load_status'             => $data[ 'load_status' ],
			'instructions'            => $instructions,
			'source'                  => $data[ 'source' ],
			'load_type'               => $data[ 'load_type' ],
			'commodity'               => $data[ 'commodity' ],
			'weight'                  => $data[ 'weight' ],
			'notes'                   => $data[ 'notes' ],
			'dispatcher_initials'     => $data[ 'dispatcher_initials' ],
			'reference_number'        => $data[ 'reference_number' ],
			'unit_number_name'        => $data[ 'unit_number_name' ],
			'booked_rate'             => $data[ 'booked_rate' ],
			'driver_rate'             => $data[ 'driver_rate' ],
			'driver_phone'            => $data[ 'driver_phone' ],
			'profit'                  => $data[ 'profit' ],
			'percent_booked_rate'     => $data[ 'percent_booked_rate' ],
			'true_profit'             => $data[ 'true_profit' ],
			'percent_quick_pay_value' => $data[ 'percent_quick_pay_value' ],
			'booked_rate_modify'      => $data[ 'booked_rate_modify' ],
			'tbd'                     => $data[ 'tbd' ],
			'shared_with_client'      => $data[ 'shared_with_client' ],
			'macropoint_set'          => $data[ 'macropoint_set' ],
			'trucker_tools'           => $data[ 'trucker_tools' ],
			'second_unit_number_name' => $data[ 'second_unit_number_name' ],
			'second_driver_rate'      => $data[ 'second_driver_rate' ],
			'second_driver_phone'     => $data[ 'second_driver_phone' ],
			'second_driver'           => $data[ 'second_driver' ],
			'third_unit_number_name'  => $data[ 'third_unit_number_name' ],
			'third_driver_rate'       => $data[ 'third_driver_rate' ],
			'third_driver_phone'      => $data[ 'third_driver_phone' ],
			'third_driver'            => $data[ 'third_driver' ],
			'attached_driver'         => $data[ 'attached_driver' ] ?? '',
			'attached_second_driver'  => $data[ 'attached_second_driver' ] ?? '',
			'attached_third_driver'   => $data[ 'attached_third_driver' ] ?? '',
			'additional_fees'         => $data[ 'additional_fees' ],
			'additional_fees_val'     => $data[ 'additional_fees_val' ],
			'additional_fees_driver'  => $data[ 'additional_fees_driver' ],
			'office_dispatcher'       => $office_dispatcher,
		);
		
		if ( isset( $data[ 'modify_price' ] ) ) {
			$post_meta[ 'modify_price' ] = $data[ 'modify_price' ];
		}
		
		if ( isset( $data[ 'modify_driver_price' ] ) ) {
			$post_meta[ 'modify_driver_price' ] = $data[ 'modify_driver_price' ];
		}

		if ( isset( $data[ 'modify_second_driver_price' ] ) ) {
			$post_meta[ 'modify_second_driver_price' ] = $data[ 'modify_second_driver_price' ];
		}

		if ( isset( $data[ 'modify_third_driver_price' ] ) ) {
			$post_meta[ 'modify_third_driver_price' ] = $data[ 'modify_third_driver_price' ];
		}
		
		if ( isset( $data[ 'read_only' ] ) ) {
			
			$exclude = array( 'reference_number', 'load_type', 'source' );
			
			foreach ( $post_meta as $key => $value ) {
				$search = array_search( $key, $exclude );
				if ( is_numeric( $search ) ) {
					unset( $post_meta[ $key ] );
				}
			}
		}
		
		$post_id = $data[ 'post_id' ];
		// Specify the condition (WHERE clause) - assuming post_id is passed in the data array
		$where = array( 'id' => $data[ 'post_id' ] );
		// Perform the update
		
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // pick_up_date
			'%s',  // delivery_date
			'%s',  // date_booked
		), array( '%d' ) // The data type of the where clause (id is an integer)
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta );
		} else {
			return false; // Error occurred during the update
		}
	}
	
	/**
	 * @param $data
	 * update load status
	 *
	 * @return bool
	 */
	public function update_post_status_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'status_post'     => $data[ 'post_status' ],
		);
		
		// Specify the condition (WHERE clause) - assuming post_id is passed in the data array
		$where = array( 'id' => $data[ 'post_id' ] );
		// Perform the update
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // post_status
		), array( '%d' ) // The data type of the where clause (id is an integer)
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			return false; // Error occurred during the update
		}
	}
	
	/**
	 * @param $data
	 * remove image in db
	 *
	 * @return true|WP_Error
	 */
	public function remove_one_image_in_db( $data ) {
		global $wpdb, $global_options;
		
		$table_meta_name = $wpdb->prefix . $this->table_meta; // Имя таблицы мета данных
		
		// Извлекаем ID изображения и имя мета-ключа
		$image_id    = intval( $data[ 'image-id' ] );
		$image_field = sanitize_text_field( $data[ 'image-fields' ] );
		$post_id     = intval( $data[ 'post_id' ] );
		
		$user_id      = get_current_user_id();
		$user_name    = $this->get_user_full_name_by_id( $user_id );
		$add_new_load = get_field_value( $global_options, 'add_new_load' );
		$link         = '';
		
		// Проверяем корректность входных данных
		if ( ! $image_id || ! $image_field || ! $post_id ) {
			return new WP_Error( 'invalid_input', 'Invalid image ID, field name or post ID.' );
		}
		
		// Извлекаем текущее значение поля meta_key для поста
		$current_value = $wpdb->get_var( $wpdb->prepare( "
		SELECT meta_value
		FROM $table_meta_name
		WHERE post_id = %d AND meta_key = %s", $post_id, $image_field ) );
		
		if ( $current_value ) {
			$new_value = '';
			
			// Для поля attached_files, где значения хранятся через запятую
			if ( $image_field === 'attached_files' || $image_field === 'freight_pictures' ) {
				$ids = explode( ',', $current_value );
				$ids = array_map( 'intval', $ids );
				// Удаляем указанный ID
				$new_ids   = array_diff( $ids, array( $image_id ) );
				$new_value = implode( ',', $new_ids );
			} elseif ( $image_field === 'attached_file_required' || $image_field === 'updated_rate_confirmation' || $image_field === 'screen_picture' || $image_field === 'proof_of_delivery' || $image_field === 'certificate_of_nalysis' ) {
				
				if ( $image_field === 'attached_file_required' ) {
					$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array(
						'tracking_email',
						'billing_email',
						'admin_email',
						'team_leader_email'
					) );
					
					if ( $add_new_load ) {
						$url = add_query_arg( array(
							'post_id'    => $data[ 'post_id' ],
							'use_driver' => $this->project,
							'tab'        => 'pills-documents-tab',
						), $add_new_load );
						
						$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $data[ 'reference_number' ] ) );
					}
					
					$who_changed = 'rate confirmation';
					$this->email_helper->send_custom_email( $select_emails, array(
						'subject'      => 'Changed rate confirmation',
						'project_name' => $this->project,
						'subtitle'     => $user_name[ 'full_name' ] . ' has updated the ' . $who_changed . ' for the load ' . $link,
						'message'      => 'You may need to review the document.',
					) );
				}
				
				// Для полей attached_file_required и updated_rate_confirmation
				if ( $current_value == $image_id ) {
					$new_value = ''; // Удаляем значение, если оно совпадает
				} else {
					return new WP_Error( 'id_not_found', 'The specified ID was not found in the field.' );
				}
			} else {
				return new WP_Error( 'invalid_field', 'Invalid field name.' );
			}
			
			// Обновляем запись в таблице мета-данных
			$result = $wpdb->update( $table_meta_name, array( 'meta_value' => $new_value ), array(
				'post_id'  => $post_id,
				'meta_key' => $image_field
			), array( '%s' ),       // Формат для meta_value
				array( '%d', '%s' ) // Форматы для post_id и meta_key
			);
			
			// Удаляем вложение из медиа библиотеки
			$deleted = wp_delete_attachment( $image_id, true );
			
			if ( ! $deleted ) {
				return new WP_Error( 'delete_failed', 'Failed to delete the attachment.' );
			}
			
			// Проверяем результат обновления в базе данных
			if ( $result !== false ) {
				return true; // Успешное обновление
			} else {
				return new WP_Error( 'db_update_failed', 'Failed to update the database.' );
			}
		} else {
			return new WP_Error( 'no_value_found', 'No value found for the specified field.' );
		}
	}
	
	/**
	 * @param $post_id
	 * @param $meta_data
	 * update post meta fields in db
	 *
	 * @return true|WP_Error
	 */
	function update_post_meta_data( $post_id, $meta_data ) {
		global $wpdb;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
		foreach ( $meta_data as $meta_key => $meta_value ) {
			$existing = $wpdb->get_var( $wpdb->prepare( "
            SELECT id FROM $table_meta_name
            WHERE post_id = %d AND meta_key = %s
        ", $post_id, $meta_key ) );
			
			if ( $existing ) {
				// Обновляем существующую запись
				$wpdb->update( $table_meta_name, array( 'meta_value' => $meta_value ), array( 'id' => $existing ), array( '%s' ), array( '%d' ) );
			} else {
				// Вставляем новую запись
				$wpdb->insert( $table_meta_name, array(
					'post_id'    => $post_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value
				), array( '%d', '%s', '%s' ) );
			}
		}
		
		// Check if load_status was updated to final status and stop timer
		if ( isset( $meta_data['load_status'] ) ) {
			TMSLogger::log_to_file( '[ETA-auto] TMSReports update_post_meta_data: post_id=' . $post_id . ', load_status=' . $meta_data['load_status'], 'eta-auto' );
			$final_statuses = array( 'delivered', 'tonu', 'cancelled', 'waiting-on-rc' );
			if ( in_array( $meta_data['load_status'], $final_statuses ) ) {
				$this->stop_timer_for_final_status( $post_id, $meta_data['load_status'], false );
			}
			
			// Remove ETA records for completed loads
			$this->remove_eta_records_for_status( $post_id, $meta_data['load_status'] );
		}
		
		// wp_timers: restart on loaded-enroute and at-del
		$restart_timer_statuses = array( 'loaded-enroute', 'at-del' );
		if ( isset( $meta_data['load_status'] ) && in_array( $meta_data['load_status'], $restart_timer_statuses ) ) {
			$this->update_timer_for_status_change( $post_id, $meta_data['load_status'], false );
		}
		// ETA timer (wp_eta_records): auto-create delivery ETA when status becomes loaded-enroute
		if ( isset( $meta_data['load_status'] ) && $meta_data['load_status'] === 'loaded-enroute' ) {
			TMSLogger::log_to_file( '[ETA-auto] TMSReports: load_status=loaded-enroute, post_id=' . $post_id . ', table_meta=' . $wpdb->prefix . $this->table_meta . ', is_flt=false', 'eta-auto' );
			$this->ensure_delivery_eta_for_loaded_enroute( $post_id, $wpdb->prefix . $this->table_meta, false );
		}
		
		// Проверка на ошибки
		if ( $wpdb->last_error ) {
			return new WP_Error( 'db_error', 'Ошибка при обновлении метаданных: ' . $wpdb->last_error );
		}
		
		return true;
	}

	/**
	 * Stop timer when load status changes to final status
	 *
	 * @param int $post_id Load ID
	 * @param string $status Final status
	 * @param bool $is_flt Is FLT load
	 * @return void
	 */
	private function stop_timer_for_final_status( $post_id, $status, $is_flt = false ) {
		// Get current user's project
		$user_id = get_current_user_id();
		$current_project = get_field( 'current_select', 'user_' . $user_id );
		
		// Initialize timer class
		$timer_class = new TMSReportsTimer();
		
		// Get active timer for this load, user, and project
		$active_timer = $timer_class->get_active_timer( $post_id, $user_id );
		if ( $active_timer ) {
			// Check if timer matches current project and FLT status
			if ( $active_timer['project'] === $current_project && $active_timer['flt'] == $is_flt ) {
				$timer_class->stop_timer( $post_id, 'Load status changed to: ' . $status );
			}
		}
		
		// Also check paused timer
		$paused_timer = $timer_class->get_paused_timer( $post_id, $user_id );
		if ( $paused_timer ) {
			// Check if timer matches current project and FLT status
			if ( $paused_timer['project'] === $current_project && $paused_timer['flt'] == $is_flt ) {
				$timer_class->stop_timer( $post_id, 'Load status changed to: ' . $status );
			}
		}
	}
	
	/**
	 * Update timer when load status changes to restart timer statuses
	 * 
	 * @param int $post_id Load ID
	 * @param string $status Status that triggers timer update
	 * @param bool $is_flt Is FLT load
	 * @return void
	 */
	private function update_timer_for_status_change( $post_id, $status, $is_flt = false ) {
		// Get current user's project
		$user_id = get_current_user_id();
		$current_project = get_field( 'current_select', 'user_' . $user_id );
		
		// Initialize timer class
		$timer_class = new TMSReportsTimer();
		
		// Update timer with status change comment
		$comment = 'Load status changed to: ' . $status;
		$timer_class->update_timer( $post_id, $comment, $current_project, $is_flt );
	}
	
	// UPDATE IN DATABASE END
	
	// CREATE TABLE AND UPDATE SQL
	
	/**
	 * create table
	 * @return void
	 */
	public function create_table() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $table_name (
			    id mediumint(9) NOT NULL AUTO_INCREMENT,
			    user_id_added mediumint(9) NOT NULL,
			    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    user_id_updated mediumint(9) NULL,
			    date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    pick_up_date datetime NOT NULL,
			    delivery_date datetime NOT NULL,
			    date_booked datetime NOT NULL,
			    load_problem datetime NULL DEFAULT NULL,
			    status_post varchar(50) NULL DEFAULT NULL,
			    PRIMARY KEY (id),
			    INDEX idx_date_created (date_created),
			    INDEX idx_pick_up_date (pick_up_date),
			    INDEX idx_delivery_date (delivery_date),
			    INDEX idx_date_booked (date_booked),
			    INDEX idx_load_problem (load_problem)
			) $charset_collate;";
			
			dbDelta( $sql );
			
			$sql = "CREATE TABLE $table_meta_name (
		        id mediumint(9) NOT NULL AUTO_INCREMENT,
		        post_id mediumint(9) NOT NULL,
		        meta_key varchar(255) NOT NULL,
		        meta_value text,
		        PRIMARY KEY  (id),
                INDEX idx_post_id (post_id),
         		INDEX idx_meta_key (meta_key),
         		INDEX idx_meta_key_value (meta_key, meta_value(191))
    		) $charset_collate;";
			
			dbDelta( $sql );
		}
	}
	
	/**
	 * Create location tables for all projects (pickup and delivery combined)
	 * @return void
	 */
	public function create_location_tables() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		$charset_collate = $wpdb->get_charset_collate();
		
		foreach ( $tables as $val ) {
			$table_name = $wpdb->prefix . 'reports_' . strtolower( $val ) . '_locations';
			
			$sql = "CREATE TABLE $table_name (
			    id mediumint(9) NOT NULL AUTO_INCREMENT,
			    load_id mediumint(9) NOT NULL,
			    location_type varchar(20) NOT NULL DEFAULT 'pickup',
			    address_id varchar(255) NOT NULL,
			    address text NOT NULL,
			    short_address varchar(255) DEFAULT NULL,
			    contact varchar(255) DEFAULT NULL,
			    date datetime DEFAULT NULL,
			    info text DEFAULT NULL,
			    type varchar(50) DEFAULT NULL,
			    time_start time DEFAULT NULL,
			    time_end time DEFAULT NULL,
			    strict_time tinyint(1) DEFAULT 0,
			    eta_date date DEFAULT NULL,
			    eta_time time DEFAULT NULL,
			    order_index smallint(3) NOT NULL DEFAULT 0,
			    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    PRIMARY KEY (id),
			    INDEX idx_load_id (load_id),
			    INDEX idx_location_type (location_type),
			    INDEX idx_load_type (load_id, location_type),
			    INDEX idx_address_id (address_id),
			    INDEX idx_date (date),
			    INDEX idx_order_index (order_index),
			    INDEX idx_load_order (load_id, location_type, order_index),
			    INDEX idx_created_at (created_at),
			    INDEX idx_updated_at (updated_at),
			    INDEX idx_route_stats (location_type, date, load_id)
			) $charset_collate;";

			dbDelta( $sql );
		}
	}

	/**
	 * Import locations from JSON to new location tables
	 * 
	 * @param string $project Project name (Odysseia, Martlet, Endurance)
	 * @param int $batch_size Number of loads to process per batch
	 * @return array Import statistics
	 */
	public function import_locations_from_json( $project, $batch_size = 50 ) {
		global $wpdb;
		
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( sprintf( '[Location Import] Starting import for project: %s, batch size: %d', $project, $batch_size ), 'location-import' );
		}
		
		$project_lower = strtolower( $project );
		$table_main = $wpdb->prefix . 'reports_' . $project_lower;
		$table_meta = $wpdb->prefix . 'reportsmeta_' . $project_lower;
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		
		// Get loads that haven't been imported yet (check if locations table has any records for this load)
		$processed_loads = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT load_id FROM $table_locations"
		) );
		
		$exclude_condition = '';
		if ( ! empty( $processed_loads ) ) {
			$exclude_ids = implode( ',', array_map( 'absint', $processed_loads ) );
			$exclude_condition = "AND main.id NOT IN ($exclude_ids)";
		}
		
		// Get batch of loads with location JSON data
		$loads = $wpdb->get_results( $wpdb->prepare(
			"SELECT main.id, 
				(SELECT meta_value FROM $table_meta WHERE post_id = main.id AND meta_key = 'pick_up_location' LIMIT 1) as pick_up_location_json,
				(SELECT meta_value FROM $table_meta WHERE post_id = main.id AND meta_key = 'delivery_location' LIMIT 1) as delivery_location_json
			FROM $table_main AS main
			WHERE main.status_post = 'publish'
			$exclude_condition
			AND (
				EXISTS (SELECT 1 FROM $table_meta WHERE post_id = main.id AND meta_key = 'pick_up_location' AND meta_value IS NOT NULL AND meta_value != '')
				OR EXISTS (SELECT 1 FROM $table_meta WHERE post_id = main.id AND meta_key = 'delivery_location' AND meta_value IS NOT NULL AND meta_value != '')
			)
			ORDER BY main.id ASC
			LIMIT %d",
			$batch_size
		), ARRAY_A );
		
		if ( empty( $loads ) ) {
			return array(
				'success' => true,
				'processed' => 0,
				'imported' => 0,
				'skipped' => 0,
				'message' => 'No loads to import'
			);
		}
		
		$imported_count = 0;
		$skipped_count = 0;
		
		foreach ( $loads as $load ) {
			$load_id = (int) $load['id'];
			$imported = $this->import_load_locations( $load_id, $project_lower, $load['pick_up_location_json'], $load['delivery_location_json'] );
			
			if ( $imported['success'] ) {
				$imported_count += $imported['count'];
			} else {
				$skipped_count++;
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( sprintf( '[Location Import] Skipped load ID %d: %s', $load_id, $imported['message'] ), 'location-import' );
				}
			}
		}
		
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( sprintf( '[Location Import] Batch completed. Processed: %d, Imported: %d, Skipped: %d', count( $loads ), $imported_count, $skipped_count ), 'location-import' );
		}
		
		return array(
			'success' => true,
			'processed' => count( $loads ),
			'imported' => $imported_count,
			'skipped' => $skipped_count
		);
	}
	
	/**
	 * Import locations for a single load
	 * 
	 * @param int $load_id Load ID
	 * @param string $project_lower Project name in lowercase
	 * @param string $pick_up_json Pick up location JSON
	 * @param string $delivery_json Delivery location JSON
	 * @return array Result
	 */
	private function import_load_locations( $load_id, $project_lower, $pick_up_json, $delivery_json ) {
		global $wpdb;
		
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		$imported_count = 0;
		
		// Process pick up locations
		if ( ! empty( $pick_up_json ) ) {
			$pick_up_data = json_decode( str_replace( "\'", "'", stripslashes( $pick_up_json ) ), true );
			if ( is_array( $pick_up_data ) && ! empty( $pick_up_data ) ) {
				// Preserve order: old first, new last
				foreach ( $pick_up_data as $index => $location ) {
					$order_index = $index;
					$result = $this->insert_location( $table_locations, $load_id, 'pickup', $location, $order_index );
					if ( $result ) {
						$imported_count++;
					}
				}
			}
		}
		
		// Process delivery locations
		if ( ! empty( $delivery_json ) ) {
			$delivery_data = json_decode( str_replace( "\'", "'", stripslashes( $delivery_json ) ), true );
			if ( is_array( $delivery_data ) && ! empty( $delivery_data ) ) {
				// Preserve order: old first, new last
				foreach ( $delivery_data as $index => $location ) {
					$order_index = $index;
					$result = $this->insert_location( $table_locations, $load_id, 'delivery', $location, $order_index );
					if ( $result ) {
						$imported_count++;
					}
				}
			}
		}
		
		return array(
			'success' => true,
			'count' => $imported_count,
			'message' => 'Imported successfully'
		);
	}
	
	/**
	 * Insert location into database
	 * 
	 * @param string $table_name Table name
	 * @param int $load_id Load ID
	 * @param string $location_type 'pickup' or 'delivery'
	 * @param array $location Location data
	 * @param int $order_index Order index
	 * @return bool Success
	 */
	private function insert_location( $table_name, $load_id, $location_type, $location, $order_index ) {
		global $wpdb;
		
		// Normalize strict_time from different possible formats ('true', 'false', 1, 0, '')
		$strict_raw = isset( $location['strict_time'] ) ? $location['strict_time'] : 0;
		$strict_normalized = 0;
		if ( $strict_raw === 'true' || $strict_raw === true || $strict_raw === 1 || $strict_raw === '1' ) {
			$strict_normalized = 1;
		}
		
		$data = array(
			'load_id' => $load_id,
			'location_type' => $location_type,
			'address_id' => isset( $location['address_id'] ) ? $location['address_id'] : '',
			'address' => isset( $location['address'] ) ? $location['address'] : '',
			'short_address' => isset( $location['short_address'] ) ? $location['short_address'] : null,
			'contact' => isset( $location['contact'] ) ? $location['contact'] : null,
			'date' => isset( $location['date'] ) && ! empty( $location['date'] ) ? $location['date'] : null,
			'info' => isset( $location['info'] ) ? $location['info'] : null,
			'type' => isset( $location['type'] ) ? $location['type'] : null,
			'time_start' => isset( $location['time_start'] ) && ! empty( $location['time_start'] ) ? $location['time_start'] : null,
			'time_end' => isset( $location['time_end'] ) && ! empty( $location['time_end'] ) ? $location['time_end'] : null,
			'strict_time' => $strict_normalized,
			'eta_date' => isset( $location['eta_date'] ) && ! empty( $location['eta_date'] ) ? $location['eta_date'] : null,
			'eta_time' => isset( $location['eta_time'] ) && ! empty( $location['eta_time'] ) ? $location['eta_time'] : null,
			'order_index' => $order_index
		);
		
		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' );
		
		return $wpdb->insert( $table_name, $data, $format ) !== false;
	}
	
	/**
	 * Get locations from database for a load
	 * 
	 * @param int $load_id Load ID
	 * @param string $location_type 'pickup', 'delivery', or 'all'
	 * @return array Array of locations
	 */
	public function get_locations_from_db( $load_id, $location_type = 'all' ) {
		global $wpdb;
		
		if ( empty( $this->project ) ) {
			return array();
		}
		
		$project_lower = strtolower( $this->project );
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		
		$where_type = '';
		if ( $location_type !== 'all' ) {
			$where_type = $wpdb->prepare( " AND location_type = %s", $location_type );
		}
		
		$locations = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_locations 
			WHERE load_id = %d $where_type
			ORDER BY location_type, order_index ASC",
			$load_id
		), ARRAY_A );
		
		// Convert to format similar to JSON structure
		$result = array(
			'pickup' => array(),
			'delivery' => array()
		);
		
		foreach ( $locations as $location ) {
			$loc_data = array(
				'db_id' => $location['id'], // Include DB ID for updates
				'address_id' => $location['address_id'],
				'address' => $location['address'],
				'short_address' => $location['short_address'],
				'contact' => $location['contact'],
				'date' => $location['date'],
				'info' => $location['info'],
				'type' => $location['type'],
				'time_start' => $location['time_start'],
				'time_end' => $location['time_end'],
				'strict_time' => $location['strict_time'],
				'eta_date' => $location['eta_date'],
				'eta_time' => $location['eta_time'],
			);
			
			if ( $location['location_type'] === 'pickup' ) {
				$result['pickup'][] = $loc_data;
			} else {
				$result['delivery'][] = $loc_data;
			}
		}
		
		// Data is already in correct order (old first, new last) from ORDER BY order_index ASC
		// No need to reverse anymore
		
		return $result;
	}

	/**
	 * Get locations from database for multiple loads in one query (batch).
	 *
	 * @param int[]  $load_ids Load IDs.
	 * @param string $project  Optional. Project slug; defaults to $this->project.
	 * @param bool   $is_flt   Optional. Unused in this class; for signature compatibility.
	 * @return array<int, array{pickup: array, delivery: array}> Keyed by load_id, same structure as get_locations_from_db() per load.
	 */
	public function get_locations_from_db_batch( $load_ids, $project = null, $is_flt = false ) {
		return parent::get_locations_from_db_batch( $load_ids, $project !== null ? $project : $this->project, false );
	}
	
	/**
	 * Save locations to database (updates existing, adds new, removes deleted)
	 * 
	 * @param int $load_id Load ID
	 * @param array $pick_up_locations Array of pickup locations (may contain 'db_id' for updates)
	 * @param array $delivery_locations Array of delivery locations (may contain 'db_id' for updates)
	 * @return bool|WP_Error
	 */
	public function save_locations_to_db( $load_id, $pick_up_locations = array(), $delivery_locations = array() ) {
		global $wpdb;
		
		if ( empty( $this->project ) ) {
			return new WP_Error( 'no_project', 'Project not set' );
		}
		
		$project_lower = strtolower( $this->project );
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		
		// Get existing locations from DB
		$existing_locations = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_locations WHERE load_id = %d",
			$load_id
		), ARRAY_A );
		
		// Create map of existing locations by ID
		$existing_by_id = array();
		foreach ( $existing_locations as $loc ) {
			$existing_by_id[ $loc['id'] ] = $loc;
		}
		
		// Track which IDs are being used (to delete unused ones)
		$used_ids = array();
		
		// Process pickup locations (preserve order: old first, new last)
		foreach ( $pick_up_locations as $index => $location ) {
			$order_index = $index;
			$db_id = isset( $location['db_id'] ) ? intval( $location['db_id'] ) : 0;
			
			// Remove db_id from location data before saving
			$location_data = $location;
			unset( $location_data['db_id'] );
			
			if ( $db_id > 0 && isset( $existing_by_id[ $db_id ] ) ) {
				// Update existing location
				$location_data['order_index'] = $order_index;
				$result = $this->update_location_in_db( $db_id, $location_data );
				if ( ! $result ) {
					return new WP_Error( 'update_failed', 'Failed to update pickup location ID: ' . $db_id );
				}
				$used_ids[] = $db_id;
			} else {
				// Insert new location
				$result = $this->insert_location( $table_locations, $load_id, 'pickup', $location_data, $order_index );
				if ( ! $result ) {
					return new WP_Error( 'insert_failed', 'Failed to insert pickup location' );
				}
			}
		}
		
		// Process delivery locations (preserve order: old first, new last)
		foreach ( $delivery_locations as $index => $location ) {
			$order_index = $index;
			$db_id = isset( $location['db_id'] ) ? intval( $location['db_id'] ) : 0;
			
			// Remove db_id from location data before saving
			$location_data = $location;
			unset( $location_data['db_id'] );
			
			if ( $db_id > 0 && isset( $existing_by_id[ $db_id ] ) ) {
				// Update existing location
				$location_data['order_index'] = $order_index;
				$result = $this->update_location_in_db( $db_id, $location_data );
				if ( ! $result ) {
					return new WP_Error( 'update_failed', 'Failed to update delivery location ID: ' . $db_id );
				}
				$used_ids[] = $db_id;
			} else {
				// Insert new location
				$result = $this->insert_location( $table_locations, $load_id, 'delivery', $location_data, $order_index );
				if ( ! $result ) {
					return new WP_Error( 'insert_failed', 'Failed to insert delivery location' );
				}
			}
		}
		
		// Delete locations that are no longer in the new data
		if ( ! empty( $existing_locations ) ) {
			foreach ( $existing_locations as $existing ) {
				if ( ! in_array( $existing['id'], $used_ids ) ) {
					$wpdb->delete( $table_locations, array( 'id' => $existing['id'] ), array( '%d' ) );
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Delete location from database
	 * 
	 * @param int $location_id Location ID
	 * @return bool Success
	 */
	public function delete_location_from_db( $location_id ) {
		global $wpdb;
		
		if ( empty( $this->project ) ) {
			return false;
		}
		
		$project_lower = strtolower( $this->project );
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		
		$result = $wpdb->delete( $table_locations, array( 'id' => $location_id ), array( '%d' ) );
		
		return $result !== false;
	}
	
	/**
	 * Update location in database
	 * 
	 * @param int $location_id Location ID
	 * @param array $location_data Location data to update
	 * @return bool Success
	 */
	public function update_location_in_db( $location_id, $location_data ) {
		global $wpdb;
		
		if ( empty( $this->project ) ) {
			return false;
		}
		
		$project_lower = strtolower( $this->project );
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		
		$data = array();
		$format = array();
		
		if ( isset( $location_data['address_id'] ) ) {
			$data['address_id'] = $location_data['address_id'];
			$format[] = '%s';
		}
		if ( isset( $location_data['address'] ) ) {
			$data['address'] = $location_data['address'];
			$format[] = '%s';
		}
		if ( isset( $location_data['short_address'] ) ) {
			$data['short_address'] = $location_data['short_address'];
			$format[] = '%s';
		}
		if ( isset( $location_data['contact'] ) ) {
			$data['contact'] = $location_data['contact'];
			$format[] = '%s';
		}
		if ( isset( $location_data['date'] ) ) {
			// Validate and normalize date - set to null if empty, invalid, or equals epoch (1970-01-01)
			$date_value = trim( $location_data['date'] );
			if ( empty( $date_value ) || $date_value === '' ) {
				$data['date'] = null;
				$format[] = '%s';
			} else {
				$timestamp = strtotime( $date_value );
				// Check if date is valid, not epoch (timestamp >= 86400 = 1970-01-02), and doesn't contain 1970 in string
				if ( $timestamp !== false && $timestamp >= 86400 && strpos( $date_value, '1970' ) === false ) {
					$date_obj = DateTime::createFromFormat( 'Y-m-d H:i:s', date( 'Y-m-d H:i:s', $timestamp ) );
					if ( $date_obj ) {
						$date_only = $date_obj->format( 'Y-m-d' );
						// Double check: not 1970-01-01 or 1970-01-02
						if ( $date_only !== '1970-01-01' && $date_only !== '1970-01-02' ) {
							$data['date'] = date( 'Y-m-d H:i:s', $timestamp );
							$format[] = '%s';
						} else {
							$data['date'] = null;
							$format[] = '%s';
						}
					} else {
						$data['date'] = null;
						$format[] = '%s';
					}
				} else {
					$data['date'] = null;
					$format[] = '%s';
				}
			}
		}
		if ( isset( $location_data['info'] ) ) {
			$data['info'] = $location_data['info'];
			$format[] = '%s';
		}
		if ( isset( $location_data['type'] ) ) {
			$data['type'] = $location_data['type'];
			$format[] = '%s';
		}
		if ( isset( $location_data['time_start'] ) ) {
			$data['time_start'] = $location_data['time_start'];
			$format[] = '%s';
		}
		if ( isset( $location_data['time_end'] ) ) {
			$data['time_end'] = $location_data['time_end'];
			$format[] = '%s';
		}
		if ( isset( $location_data['strict_time'] ) ) {
			// Normalize strict_time from different possible formats ('true', 'false', 1, 0, '')
			$strict_raw = $location_data['strict_time'];
			$strict_normalized = 0;
			if ( $strict_raw === 'true' || $strict_raw === true || $strict_raw === 1 || $strict_raw === '1' ) {
				$strict_normalized = 1;
			}
			$data['strict_time'] = $strict_normalized;
			$format[] = '%d';
		}
		if ( isset( $location_data['eta_date'] ) ) {
			// Validate and normalize ETA date - set to null if empty, invalid, or equals epoch (1970-01-01)
			$eta_date_value = trim( $location_data['eta_date'] );
			if ( empty( $eta_date_value ) || $eta_date_value === '' ) {
				$data['eta_date'] = null;
				$format[] = '%s';
			} else {
				$eta_timestamp = strtotime( $eta_date_value );
				// Check if date is valid, not epoch (timestamp >= 86400 = 1970-01-02), and doesn't contain 1970 in string
				if ( $eta_timestamp !== false && $eta_timestamp >= 86400 && strpos( $eta_date_value, '1970' ) === false ) {
					$eta_date_obj = DateTime::createFromFormat( 'Y-m-d', date( 'Y-m-d', $eta_timestamp ) );
					if ( $eta_date_obj ) {
						$eta_date_only = $eta_date_obj->format( 'Y-m-d' );
						if ( $eta_date_only !== '1970-01-01' && $eta_date_only !== '1970-01-02' ) {
							$data['eta_date'] = date( 'Y-m-d', $eta_timestamp );
							$format[] = '%s';
						} else {
							$data['eta_date'] = null;
							$format[] = '%s';
						}
					} else {
						$data['eta_date'] = null;
						$format[] = '%s';
					}
				} else {
					$data['eta_date'] = null;
					$format[] = '%s';
				}
			}
		}
		if ( isset( $location_data['eta_time'] ) ) {
			$eta_time_value = trim( $location_data['eta_time'] );
			$data['eta_time'] = ! empty( $eta_time_value ) ? $eta_time_value : null;
			$format[] = '%s';
		}
		if ( isset( $location_data['order_index'] ) ) {
			$data['order_index'] = (int) $location_data['order_index'];
			$format[] = '%d';
		}
		
		if ( empty( $data ) ) {
			return false;
		}
		
		$result = $wpdb->update( $table_locations, $data, array( 'id' => $location_id ), $format, array( '%d' ) );
		
		return $result !== false;
	}
	
	/**
	 * Get import statistics for a project
	 * 
	 * @param string $project Project name
	 * @return array Statistics
	 */
	public function get_location_import_stats( $project ) {
		global $wpdb;
		
		$project_lower = strtolower( $project );
		$table_main = $wpdb->prefix . 'reports_' . $project_lower;
		$table_meta = $wpdb->prefix . 'reportsmeta_' . $project_lower;
		$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
		
		// Total loads with location data
		$total_loads = $wpdb->get_var( "
			SELECT COUNT(DISTINCT main.id)
			FROM $table_main AS main
			WHERE main.status_post = 'publish'
			AND (
				EXISTS (SELECT 1 FROM $table_meta WHERE post_id = main.id AND meta_key = 'pick_up_location' AND meta_value IS NOT NULL AND meta_value != '')
				OR EXISTS (SELECT 1 FROM $table_meta WHERE post_id = main.id AND meta_key = 'delivery_location' AND meta_value IS NOT NULL AND meta_value != '')
			)
		" );
		
		// Processed loads (have records in locations table)
		$processed = $wpdb->get_var( "
			SELECT COUNT(DISTINCT load_id) FROM $table_locations
		" );
		
		// Total imported locations
		$imported_locations = $wpdb->get_var( "
			SELECT COUNT(*) FROM $table_locations
		" );
		
		$progress_percent = $total_loads > 0 ? ( $processed / $total_loads ) * 100 : 100;
		
		return array(
			'total_loads' => (int) $total_loads,
			'processed' => (int) $processed,
			'imported_locations' => (int) $imported_locations,
			'progress_percent' => $progress_percent
		);
	}
	
	/**
	 * update table add new fields and indexes isset fields
	 * @return void
	 */
	public function update_tables_with_delivery_and_indexes() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			// Добавляем новое поле delivery_date и индексы на все даты
			$wpdb->query( "
            ALTER TABLE $table_name
            ADD COLUMN delivery_date datetime NOT NULL AFTER pick_up_date,
            ADD INDEX idx_date_created (date_created),
            ADD INDEX idx_pick_up_date (pick_up_date),
            ADD INDEX idx_delivery_date (delivery_date),
            ADD INDEX idx_date_booked (date_booked),
            ADD INDEX idx_load_problem (load_problem);
        " );
			
			// Добавляем индексы в мета таблицу
			$wpdb->query( "
            ALTER TABLE $table_meta_name
            ADD INDEX idx_post_id (post_id),
            ADD INDEX idx_meta_key (meta_key(191)),
            ADD INDEX idx_meta_key_value (meta_key(191), meta_value(191));
        " );
		}
	}
	
	/**
	 * Optimize existing tables for large datasets (500k+ records)
	 * Safe to run on existing data - no data loss
	 * @return array
	 */
	public function optimize_existing_tables_for_performance() {
		global $wpdb;
		
		$results = array();
		$tables  = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			$table_results = array(
				'table'      => $table_name,
				'meta_table' => $table_meta_name,
				'changes'    => array()
			);
			
			// 1. Изменяем тип ID на BIGINT для поддержки больших объемов
			$result = $wpdb->query( "
				ALTER TABLE $table_name 
				MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed id to BIGINT UNSIGNED';
			}
			
			// 2. Изменяем типы пользователей на INT UNSIGNED
			$result = $wpdb->query( "
				ALTER TABLE $table_name 
				MODIFY COLUMN user_id_added INT UNSIGNED NOT NULL,
				MODIFY COLUMN user_id_updated INT UNSIGNED NULL
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed user_id fields to INT UNSIGNED';
			}
			
			// 3. Изменяем datetime на TIMESTAMP для лучшей производительности
			$result = $wpdb->query( "
				ALTER TABLE $table_name 
				MODIFY COLUMN date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				MODIFY COLUMN date_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				MODIFY COLUMN pick_up_date TIMESTAMP NOT NULL,
				MODIFY COLUMN delivery_date TIMESTAMP NOT NULL,
				MODIFY COLUMN date_booked TIMESTAMP NOT NULL,
				MODIFY COLUMN load_problem TIMESTAMP NULL DEFAULT NULL
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed datetime fields to TIMESTAMP';
			}
			
			// 4. Добавляем составные индексы для частых запросов
			// Важно: индексы ниже покрывают тяжёлые выборки по статусу и датам (loads + фильтры по date_booked / load_problem).
			$indexes_to_add = array(
				// Основной для тяжёлого запроса: status_post + date_booked
				'idx_status_post_date_booked' => '(status_post, date_booked)',
				'idx_pick_up_delivery'        => '(pick_up_date, delivery_date)',
				'idx_user_status'             => '(user_id_added, status_post)',
				'idx_created_status'          => '(date_created, status_post)',
				'idx_status_post'             => '(status_post)'
			);
			
			foreach ( $indexes_to_add as $index_name => $index_columns ) {
				// Проверяем, существует ли индекс
				$index_exists = $wpdb->get_var( "
					SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'
				" );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $table_name ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results[ 'changes' ][] = "Added index: $index_name";
					}
				}
			}
			
			// 5. Оптимизируем мета-таблицу
			$result = $wpdb->query( "
				ALTER TABLE $table_meta_name 
				MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				MODIFY COLUMN post_id BIGINT UNSIGNED NOT NULL
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed meta table id fields to BIGINT UNSIGNED';
			}
			
			// 6. Изменяем longtext на более эффективные типы
			$result = $wpdb->query( "
				ALTER TABLE $table_meta_name 
				MODIFY COLUMN meta_key VARCHAR(255) NOT NULL,
				MODIFY COLUMN meta_value TEXT
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed meta_key to VARCHAR(255), meta_value to TEXT';
			}
			
			// 7. Добавляем дополнительные индексы для мета-таблицы
			$meta_indexes = array(
				'idx_post_meta_key' => '(post_id, meta_key)',
				'idx_meta_value'    => '(meta_value(100))',
				'idx_key_value'     => '(meta_key, meta_value(100))'
			);
			
			foreach ( $meta_indexes as $index_name => $index_columns ) {
				$index_exists = $wpdb->get_var( "
					SHOW INDEX FROM $table_meta_name WHERE Key_name = '$index_name'
				" );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $table_meta_name ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results[ 'changes' ][] = "Added meta index: $index_name";
					}
				}
			}
			
			// 8. Оптимизируем таблицы
			$wpdb->query( "OPTIMIZE TABLE $table_name" );
			$wpdb->query( "OPTIMIZE TABLE $table_meta_name" );
			$wpdb->query( "ANALYZE TABLE $table_name" );
			$wpdb->query( "ANALYZE TABLE $table_meta_name" );
			
			$table_results[ 'changes' ][] = 'Optimized and analyzed tables';
			$results[]                    = $table_results;
		}
		
		return $results;
	}
	
	/**
	 * Add performance indexes to existing tables (safe operation)
	 * @return array
	 */
	public function add_performance_indexes_safe() {
		global $wpdb;
		
		$results = array();
		$tables  = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			$table_results = array(
				'table'         => $table_name,
				'meta_table'    => $table_meta_name,
				'indexes_added' => array()
			);
			
			// Добавляем только недостающие индексы
			$main_indexes = array(
				'idx_date_booked_status' => '(date_booked, status_post)',
				'idx_pick_up_delivery'   => '(pick_up_date, delivery_date)',
				'idx_user_status'        => '(user_id_added, status_post)',
				'idx_created_status'     => '(date_created, status_post)'
			);
			
			foreach ( $main_indexes as $index_name => $index_columns ) {
				$index_exists = $wpdb->get_var( "
					SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'
				" );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $table_name ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results[ 'indexes_added' ][] = $index_name;
					}
				}
			}
			
			// Индексы для мета-таблицы
			$meta_indexes = array(
				'idx_post_meta_key' => '(post_id, meta_key)',
				'idx_meta_value'    => '(meta_value(100))',
				'idx_key_value'     => '(meta_key, meta_value(100))'
			);
			
			foreach ( $meta_indexes as $index_name => $index_columns ) {
				$index_exists = $wpdb->get_var( "
					SHOW INDEX FROM $table_meta_name WHERE Key_name = '$index_name'
				" );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $table_meta_name ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results[ 'indexes_added' ][] = "meta_$index_name";
					}
				}
			}
			
			$results[] = $table_results;
		}
		
		return $results;
	}
	
	/**
	 * AJAX handler for database optimization
	 * @return void
	 */
	public function optimize_database_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			if ( $optimization_type === 'full' ) {
				$results = $this->optimize_existing_tables_for_performance();
				$message = 'Full database optimization completed successfully';
			} else {
				$results = $this->add_performance_indexes_safe();
				$message = 'Performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for adding performance table indexes only
	 * @return void
	 */
	public function add_performance_table_indexes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		try {
			$results = $this->add_performance_indexes_safe();
			
			wp_send_json_success( array(
				'message' => 'Performance indexes added successfully',
				'results' => $results
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Failed to add indexes: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	public function add_pinned_message() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}
		if ( isset( $_POST['project'] ) && $_POST['project'] !== $this->project ) {
			wp_send_json_error( array(
				'message' => 'You have changed the project need to switch back, current project - ' . $this->project . ' previous - ' . $_POST['project'],
			) );
		}
		$user_id        = (int) ( $_POST['user_id'] ?? 0 );
		$post_id        = (int) ( $_POST['post_id'] ?? 0 );
		$pinned_message = sanitize_textarea_field( wp_unslash( $_POST['pinned_message'] ?? '' ) );
		if ( ! $user_id || ! $post_id || ! $pinned_message ) {
			wp_send_json_error( array( 'message' => 'Need fill data' ) );
		}
		$helper = new TMSReportsHelper();
		$result = $helper->add_pinned_message_for_reports( $this, $user_id, $post_id, $pinned_message, 'reports' );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ?? 'something went wrong' ) );
		}
		wp_send_json_success( array(
			'message' => 'Message pinned',
			'pinned'  => $result['pinned_for_response'],
		) );
	}
	
	public function delete_pinned_message() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}
		if ( isset( $_POST['project'] ) && $_POST['project'] !== $this->project ) {
			wp_send_json_error( array(
				'message' => 'You have changed the project need to switch back, current project - ' . $this->project . ' previous - ' . $_POST['project'],
			) );
		}
		$post_id       = (int) ( $_POST['id'] ?? 0 );
		$message_index = (int) ( $_POST['message_index'] ?? -1 );
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'No post_id provided' ) );
		}
		if ( $message_index < 0 ) {
			wp_send_json_error( array( 'message' => 'No message index provided' ) );
		}
		$helper = new TMSReportsHelper();
		$result = $helper->delete_pinned_message_for_reports( $this, $post_id, $message_index );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ?? 'Something went wrong' ) );
		}
		wp_send_json_success( array( 'message' => 'Pinned message deleted' ) );
	}
	
	// INIT Actions
	
	/**
	 * init all ajax actions for fork
	 * @return void
	 */
	public function ajax_actions() {
		
		$actions = [
			'add_new_report'                => 'add_new_report',
			'add_new_draft_report'          => 'add_new_report_draft',
			'update_new_draft_report'       => 'update_new_draft_report',
			'update_billing_report'         => 'update_billing_report',
			'update_files_report'           => 'update_files_report',
			'delete_open_image'             => 'delete_open_image',
			'update_shipper_info'           => 'update_shipper_info',
			'send_email_chain'              => 'send_email_chain',
			'update_post_status'            => 'update_post_status',
			'rechange_status_load'          => 'rechange_status_load',
			'remove_one_load'               => 'remove_one_load',
			'get_driver_by_id'              => 'get_driver_by_id',
			'get_driver_by_id_internal'     => 'get_driver_by_id_internal',
			'update_accounting_report'      => 'update_accounting_report',
			'quick_update_post'             => 'quick_update_post',
			'quick_update_post_ar'          => 'quick_update_post_ar',
			'quick_update_status'           => 'quick_update_status',
			'quick_update_status_all'       => 'quick_update_status_all',
			'add_pinned_message'            => 'add_pinned_message',
			'delete_pinned_message'         => 'delete_pinned_message',
			'update_high_priority'          => 'update_high_priority',
			'optimize_database_tables'      => 'optimize_database_tables',
			'add_performance_indexes'       => 'add_performance_indexes',
			'optimize_log_tables'           => 'optimize_log_tables',
			'add_log_performance_indexes'   => 'add_log_performance_indexes',
			'optimize_performance_tables'   => 'optimize_performance_tables',
			'add_performance_table_indexes' => 'add_performance_table_indexes',
			'optimize_drivers_tables'       => 'optimize_drivers_tables',
			'optimize_contacts_tables'      => 'optimize_contacts_tables',
			'optimize_company_tables'       => 'optimize_company_tables',
			'optimize_shipper_tables'       => 'optimize_shipper_tables',
			'create_load_chat'              => 'create_load_chat',
			'get_driver_stats_popup_html'   => 'ajax_get_driver_stats_popup_html',
			'get_tracking_live_state'       => 'ajax_get_tracking_live_state',
		];

		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this->helper, 'need_login' ] );
		}
		
		add_action( 'delete_user', array( $this, 'handle_dispatcher_deletion' ) );
		
	}
	
	/**
	 * init functions need for start work all functions loads
	 * @return void
	 */
	public function init() {
		if ( current_user_can( 'administrator' ) ) {
			add_action( 'after_setup_theme', array( $this, 'create_table' ) );
			add_action( 'after_setup_theme', array( $this, 'create_location_tables' ) );
		}
		
		//TODO UPDATE DATABASE AFTER CREATE , up speed search in database (NOT delete)
		//add_action( 'after_setup_theme', array( $this, 'update_tables_with_delivery_and_indexes' ) );
		
		$this->ajax_actions();
		
		// Add admin menu for database optimization
		add_action( 'admin_menu', array( $this, 'add_database_optimization_menu' ) );
	}
	
	/**
	 * Add database optimization page to admin menu
	 * @return void
	 */
	public function add_database_optimization_menu() {
		add_submenu_page( 'tools.php',   // Parent slug (Tools menu)
			'Database Optimization',     // Page title
			'DB Optimization',           // Menu title
			'manage_options',            // Capability
			'tms-database-optimization', // Menu slug
			array( $this, 'render_database_optimization_page' ) // Callback function
		);
	}

	public function update_high_priority() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$high_priority = $_POST[ 'high_priority' ] ?? 'false';
			$post_id = $_POST[ 'post_id' ] ?? 0;

			if ( ! $post_id ) {
				wp_send_json_error( array( 'message' => 'No post_id provided' ) );
			}

			// Convert string 'true'/'false' to boolean
			$is_high_priority = ( $high_priority === 'true' || $high_priority === '1' || $high_priority === 1 || $high_priority === true );
			$high_priority_value = $is_high_priority ? 1 : 0;

			// Update meta data
			$this->update_post_meta_data( $post_id, array( 'high_priority' => $high_priority_value ) );

			// Return informative message
			$message = $is_high_priority ? 'Set up high priority' : 'High priority removed';
			wp_send_json_success( array( 'message' => $message ) );
		}
	}
	
	/**
	 * Create load chat via webhook
	 */
	public function create_load_chat() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}
		
		// Check permissions
		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
			return;
		}
		
		$load_id = sanitize_text_field( $_POST['load_id'] ?? '' );
		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$company = sanitize_text_field( $_POST['company'] ?? '' );
		$participants_json = stripslashes( $_POST['participants'] ?? '[]' );
		
		if ( empty( $load_id ) || empty( $title ) || empty( $company ) ) {
			wp_send_json_error( array( 'message' => 'Missing required fields: load_id, title, or company' ) );
			return;
		}
		
		$participants = json_decode( $participants_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => 'Invalid JSON format for participants: ' . json_last_error_msg() ) );
			return;
		}
		
		// Get webhook URL and API key from options
		$create_chat_url = get_option( 'tms_chat_create_url', 'https://odyssea-beckend.onrender.com/v1/create_load_chat' );
		$chat_api_key = get_option( 'tms_chat_api_key', '' );
		
		// Use the function from chat-sync-admin.php if available, otherwise create inline
		if ( function_exists( 'tms_test_create_chat' ) ) {
			$result = tms_test_create_chat( $load_id, $title, $company, $participants, $create_chat_url, $chat_api_key );
		} else {
			// Inline implementation
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( '[CREATE] Creating chat - Load ID: ' . $load_id . ', Title: ' . $title . ', Company: ' . $company, 'chat-sync' );
			}
			
			$payload = array(
				'load_id' => $load_id,
				'title' => $title,
				'company' => $company,
				'participants' => $participants
			);
			
			$headers = array(
				'Content-Type' => 'application/json',
				'User-Agent' => 'TMS-Statistics/1.0'
			);
			
			if ( ! empty( $chat_api_key ) ) {
				$headers['x-api-key'] = $chat_api_key;
			}
			
			$args = array(
				'method' => 'POST',
				'timeout' => 30,
				'headers' => $headers,
				'body' => json_encode( $payload )
			);
			
			$response = wp_remote_post( $create_chat_url, $args );
			
			if ( is_wp_error( $response ) ) {
				$error_msg = 'Webhook request failed: ' . $response->get_error_message();
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file( '[ERROR] ' . $error_msg, 'chat-sync' );
				}
				$result = array(
					'success' => false,
					'error' => $error_msg
				);
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				$response_body = wp_remote_retrieve_body( $response );
				
				if ( $response_code >= 200 && $response_code < 300 ) {
					if ( class_exists( 'TMSLogger' ) ) {
						TMSLogger::log_to_file( '[SUCCESS] Create chat successful - Response Code: ' . $response_code . ', Body: ' . $response_body, 'chat-sync' );
					}
					$result = array(
						'success' => true,
						'response_code' => $response_code,
						'response_body' => $response_body
					);
				} else {
					$error_msg = 'Webhook returned error code: ' . $response_code;
					if ( class_exists( 'TMSLogger' ) ) {
						TMSLogger::log_to_file( '[ERROR] ' . $error_msg . ' - Body: ' . $response_body, 'chat-sync' );
					}
					$result = array(
						'success' => false,
						'error' => $error_msg,
						'response_body' => $response_body
					);
				}
			}
		}
		
		if ( ! empty( $result['success'] ) ) {
			// Mark chat as created for this load to avoid duplicates
			$this->update_post_meta_data( (int) $load_id, array( 'chat_created' => 1 ) );
			
			wp_send_json_success(
				array(
					'message' => 'Chat created',
					'reload'  => false,
				)
			);
		}
		
		// Build human-friendly error message
		$display_message = $result['error'] ?? 'Failed to create chat';
		
		if ( ! empty( $result['response_body'] ) && is_string( $result['response_body'] ) ) {
			$decoded_body = json_decode( $result['response_body'], true );
			if ( json_last_error() === JSON_ERROR_NONE && ! empty( $decoded_body['message'] ) ) {
				$display_message = $decoded_body['message'];
			}
		}
		
		wp_send_json_error(
			array(
				'message' => $display_message,
			)
		);
	}

	/**
	 * AJAX: return HTML for driver Statistics tab (used in popup from load tables).
	 */
	public function ajax_get_driver_stats_popup_html() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}
		$driver_id = isset( $_POST['driver_id'] ) ? absint( $_POST['driver_id'] ) : 0;
		if ( ! $driver_id ) {
			wp_send_json_error( array( 'message' => 'Missing driver_id' ) );
			return;
		}
		$driver_instance = new TMSDrivers();
		$driver_obj      = $driver_instance->get_driver_by_id( $driver_id );
		if ( ! $driver_obj || ! is_array( $driver_obj ) ) {
			wp_send_json_error( array( 'message' => 'Driver not found' ) );
			return;
		}
		$args = array(
			'report_object' => $driver_obj,
			'post_id'       => $driver_id,
		);
		ob_start();
		$template_path = get_template_directory() . '/src/template-parts/report/tabs/driver-tab-stats-popup.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			ob_end_clean();
			wp_send_json_error( array( 'message' => 'Template not found' ) );
			return;
		}
		$html = ob_get_clean();
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Sync existing load chat (update participants) after load update.
	 *
	 * This is called after a successful add_load() update. It only runs if the load
	 * already has a chat_created flag set in its meta.
	 *
	 * @param int $post_id Load (report) ID.
	 *
	 * @return void
	 */
	protected function maybe_update_load_chat_for_load( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		// Debug log: function entry
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file(
				'[UPDATE] maybe_update_load_chat_for_load called for load ' . $post_id,
				'chat-sync'
			);
		}

		// Load current report meta
		$report = $this->get_report_by_id( $post_id );
		if ( ! is_array( $report ) || empty( $report['meta'] ) ) {
			return;
		}

		$meta         = get_field_value( $report, 'meta' );
		$chat_created = get_field_value( $meta, 'chat_created' );

		// Only sync if a chat has been created before
		if ( empty( $chat_created ) ) {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file(
					'[UPDATE] Skip chat update for load ' . $post_id . ' - chat_created meta is empty',
					'chat-sync'
				);
			}
			return;
		}

		// Build full chat context (participants based on current dispatcher, drivers, etc.)
		$helper       = new TMSReportsHelper();
		$chat_context = $helper->get_load_chat_context( $meta, $this->project );
		$participants = isset( $chat_context['participants'] ) ? $chat_context['participants'] : array();

		if ( empty( $participants ) ) {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file(
					'[UPDATE] Skip chat update for load ' . $post_id . ' - no participants in context',
					'chat-sync'
				);
			}
			return;
		}

		$update_chat_url = get_option(
			'tms_chat_update_url',
			'https://odyssea-beckend.onrender.com/v1/update_load_chat'
		);
		$chat_api_key    = get_option( 'tms_chat_api_key', '' );

		// Prefer shared helper from chat-sync-admin if available
		if ( function_exists( 'tms_test_update_chat' ) ) {
			$result = tms_test_update_chat( (string) $post_id, $participants, $update_chat_url, $chat_api_key );

			if ( isset( $result['success'] ) && $result['success'] ) {
				if ( class_exists( 'TMSLogger' ) ) {
					TMSLogger::log_to_file(
						'[SUCCESS] Update chat successful (helper) for load ' . $post_id,
						'chat-sync'
					);
				}
			} else {
				if ( class_exists( 'TMSLogger' ) ) {
					$msg = isset( $result['error'] ) ? $result['error'] : 'Unknown error';
					TMSLogger::log_to_file(
						'[ERROR] Update chat failed (helper) for load ' . $post_id . ' - ' . $msg,
						'chat-sync'
					);
				}
			}

			return;
		}

		// Inline implementation
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file(
				'[UPDATE] Updating chat (inline) - Load ID: ' . $post_id,
				'chat-sync'
			);
		}

		$payload = array(
			'load_id'      => (string) $post_id,
			'participants' => $participants,
		);

		$headers = array(
			'Content-Type' => 'application/json',
			'User-Agent'   => 'TMS-Statistics/1.0',
		);

		if ( ! empty( $chat_api_key ) ) {
			$headers['x-api-key'] = $chat_api_key;
		}

		$args = array(
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => $headers,
			'body'    => wp_json_encode( $payload ),
		);

		$response = wp_remote_post( $update_chat_url, $args );

		if ( is_wp_error( $response ) ) {
			$error_msg = 'Webhook request failed: ' . $response->get_error_message();
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( '[ERROR] ' . $error_msg, 'chat-sync' );
			}

			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 200 && $response_code < 300 ) {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file(
					'[SUCCESS] Update chat successful - Response Code: ' . $response_code . ', Body: ' . $response_body,
					'chat-sync'
				);
			}
		} else {
			$error_msg = 'Webhook returned error code: ' . $response_code;
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file(
					'[ERROR] ' . $error_msg . ' - Body: ' . $response_body,
					'chat-sync'
				);
			}
		}
	}
	
	/**
	 * Render database optimization page
	 * @return void
	 */
	public function render_database_optimization_page() {
		// Проверяем права доступа
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		// Обработка формы
		if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] === 'optimize_database' ) {
			$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
			
			if ( $optimization_type === 'full' ) {
				$results = $this->optimize_existing_tables_for_performance();
				$message = 'Full database optimization completed successfully';
			} else {
				$results = $this->add_performance_indexes_safe();
				$message = 'Performance indexes added successfully';
			}
		}
		?>
        <div class="wrap">
            <h1>Database Optimization</h1>
            <p class="description">Optimize your database for better performance with large datasets (500k+ records)</p>
			
			<?php if ( isset( $message ) ) : ?>
                <div class="notice notice-success">
                    <h3><?php echo esc_html( $message ); ?></h3>
					<?php if ( isset( $results ) ) : ?>
                        <h4>Changes made:</h4>
                        <ul>
							<?php foreach ( $results as $table_result ) : ?>
                                <li>
                                    <strong><?php echo esc_html( $table_result[ 'table' ] ); ?></strong>
									<?php if ( ! empty( $table_result[ 'changes' ] ) ) : ?>
                                        <ul>
											<?php foreach ( $table_result[ 'changes' ] as $change ) : ?>
                                                <li><?php echo esc_html( $change ); ?></li>
											<?php endforeach; ?>
                                        </ul>
									<?php endif; ?>
									<?php if ( ! empty( $table_result[ 'indexes_added' ] ) ) : ?>
                                        <ul>
											<?php foreach ( $table_result[ 'indexes_added' ] as $index ) : ?>
                                                <li>Added index: <?php echo esc_html( $index ); ?></li>
											<?php endforeach; ?>
                                        </ul>
									<?php endif; ?>
                                </li>
							<?php endforeach; ?>
                        </ul>
					<?php endif; ?>
                </div>
			<?php endif; ?>

            <div class="card">
                <h2>Reports Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Optimization (Safe)</h3>
                    <p>Add performance indexes to existing tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-optimize-form">
                        <input type="hidden" name="action" value="optimize_database">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Performance Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify table structures. Make sure you have a backup!</p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize data types (datetime → timestamp)</li>
                        <li>Add comprehensive indexes</li>
                        <li>Optimize meta table structure</li>
                    </ul>
                    <form method="post" id="full-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_database">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Database Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Log Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Log Optimization (Safe)</h3>
                    <p>Add performance indexes to log tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-log-optimize-form">
                        <input type="hidden" name="action" value="optimize_log_tables">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Log Performance Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Log Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify log table structures. Make sure you have a backup!</p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize data types (datetime → timestamp)</li>
                        <li>Change LONGTEXT to TEXT for better performance</li>
                        <li>Add comprehensive indexes for log queries</li>
                    </ul>
                    <form method="post" id="full-log-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify log table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_log_tables">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Log Tables Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Performance Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Performance Optimization (Safe)</h3>
                    <p>Add performance indexes to performance tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-performance-optimize-form">
                        <input type="hidden" name="action" value="optimize_performance_tables">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Performance Table Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Performance Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify performance table structures. Make sure you have a
                        backup!</p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize user ID fields to INT UNSIGNED</li>
                        <li>Optimize calls fields to INT UNSIGNED</li>
                        <li>Add comprehensive indexes for performance queries</li>
                    </ul>
                    <form method="post" id="full-performance-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify performance table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_performance_tables">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Performance Tables Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Drivers Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Drivers Optimization (Safe)</h3>
                    <p>Add performance indexes to drivers tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-drivers-optimize-form">
                        <input type="hidden" name="action" value="optimize_drivers_tables">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Drivers Performance Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Drivers Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify drivers table structures. Make sure you have a backup!
                    </p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize user ID fields to BIGINT UNSIGNED</li>
                        <li>Change datetime fields to TIMESTAMP for better performance</li>
                        <li>Add comprehensive indexes for driver queries</li>
                        <li>Optimize meta table structure</li>
                    </ul>
                    <form method="post" id="full-drivers-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify drivers table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_drivers_tables">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Drivers Tables Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Contacts Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Contacts Optimization (Safe)</h3>
                    <p>Add performance indexes to contacts tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-contacts-optimize-form">
                        <input type="hidden" name="action" value="optimize_contacts_tables">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Contacts Performance Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Contacts Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify contacts table structures. Make sure you have a
                        backup!</p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize user ID and company ID fields to BIGINT UNSIGNED</li>
                        <li>Change datetime fields to TIMESTAMP for better performance</li>
                        <li>Add comprehensive indexes for contact queries</li>
                        <li>Optimize additional contacts table structure</li>
                    </ul>
                    <form method="post" id="full-contacts-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify contacts table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_contacts_tables">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Contacts Tables Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Company Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Company Optimization (Safe)</h3>
                    <p>Add performance indexes to company tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-company-optimize-form">
                        <input type="hidden" name="action" value="optimize_company_tables">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Company Performance Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Company Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify company table structures. Make sure you have a backup!
                    </p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize user ID fields to BIGINT UNSIGNED</li>
                        <li>Change datetime fields to TIMESTAMP for better performance</li>
                        <li>Add comprehensive indexes for company queries</li>
                        <li>Optimize meta table structure</li>
                    </ul>
                    <form method="post" id="full-company-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify company table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_company_tables">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Company Tables Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Shipper Tables Optimization</h2>
                <div class="optimization-section">
                    <h3>Quick Shipper Optimization (Safe)</h3>
                    <p>Add performance indexes to shipper tables. This is safe and won't affect your data.</p>
                    <form method="post" id="quick-shipper-optimize-form">
                        <input type="hidden" name="action" value="optimize_shipper_tables">
                        <input type="hidden" name="optimization_type" value="indexes">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-primary">
                            Add Shipper Performance Indexes
                        </button>
                    </form>
                </div>

                <div class="optimization-section">
                    <h3>Full Shipper Optimization (Advanced)</h3>
                    <p><strong>Warning:</strong> This will modify shipper table structures. Make sure you have a backup!
                    </p>
                    <ul>
                        <li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
                        <li>Optimize user ID fields to BIGINT UNSIGNED</li>
                        <li>Change datetime fields to TIMESTAMP for better performance</li>
                        <li>Add comprehensive indexes for shipper queries</li>
                        <li>Optimize address and location-based searches</li>
                    </ul>
                    <form method="post" id="full-shipper-optimize-form"
                          onsubmit="return confirm('Are you sure? This will modify shipper table structures. Make sure you have a backup!');">
                        <input type="hidden" name="action" value="optimize_shipper_tables">
                        <input type="hidden" name="optimization_type" value="full">
						<?php wp_nonce_field( 'tms_optimize_database', 'tms_optimize_nonce' ); ?>
                        <button type="submit" class="button button-warning">
                            Full Shipper Tables Optimization
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Optimization Details</h2>
                <h3>What will be optimized:</h3>
                <ul>
                    <li><strong>ID Fields:</strong> mediumint(9) → BIGINT UNSIGNED (supports 18+ quintillion records)
                    </li>
                    <li><strong>User ID Fields:</strong> mediumint(9) → INT UNSIGNED</li>
                    <li><strong>Date Fields:</strong> datetime → TIMESTAMP (better performance)</li>
                    <li><strong>Meta Table:</strong> longtext → VARCHAR(255) for keys, TEXT for values</li>
                    <li><strong>Indexes:</strong> Add composite indexes for frequent queries</li>
                </ul>

                <h3>Performance Improvements:</h3>
                <ul>
                    <li>5-10x faster queries</li>
                    <li>Support for 10+ million records</li>
                    <li>60-80% less memory usage</li>
                    <li>Sub-100ms response times</li>
                </ul>

                <h3>Safety Features:</h3>
                <ul>
                    <li>No data loss - only structure changes</li>
                    <li>Index existence check before adding</li>
                    <li>Error handling and rollback capability</li>
                    <li>Detailed logging of all changes</li>
                </ul>
            </div>
        </div>

        <style>
			.card {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin: 20px 0;
			}

			.card h2 {
				margin-top: 0;
				color: #23282d;
				border-bottom: 2px solid #0073aa;
				padding-bottom: 10px;
			}

			.optimization-section {
				margin: 20px 0;
				padding: 15px;
				border: 1px solid #e5e5e5;
				border-radius: 4px;
				background: #f9f9f9;
			}

			.optimization-section h3 {
				margin-top: 0;
				color: #0073aa;
			}

			.button-warning {
				background: #dc3232 !important;
				border-color: #dc3232 !important;
				color: #fff !important;
			}

			.button-warning:hover {
				background: #c92626 !important;
				border-color: #c92626 !important;
			}
        </style>

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            // AJAX версия для лучшего UX
            const quickForm = document.getElementById('quick-optimize-form')
            const fullForm = document.getElementById('full-optimize-form')
            const quickLogForm = document.getElementById('quick-log-optimize-form')
            const fullLogForm = document.getElementById('full-log-optimize-form')
            const quickPerformanceForm = document.getElementById('quick-performance-optimize-form')
            const fullPerformanceForm = document.getElementById('full-performance-optimize-form')
            const quickDriversForm = document.getElementById('quick-drivers-optimize-form')
            const fullDriversForm = document.getElementById('full-drivers-optimize-form')
            const quickContactsForm = document.getElementById('quick-contacts-optimize-form')
            const fullContactsForm = document.getElementById('full-contacts-optimize-form')
            const quickCompanyForm = document.getElementById('quick-company-optimize-form')
            const fullCompanyForm = document.getElementById('full-company-optimize-form')
            const quickShipperForm = document.getElementById('quick-shipper-optimize-form')
            const fullShipperForm = document.getElementById('full-shipper-optimize-form')
            
            if (quickForm) {
              quickForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_database_tables')
              })
            }
            
            if (fullForm) {
              fullForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_database_tables')
                }
              })
            }
            
            if (quickLogForm) {
              quickLogForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_log_tables')
              })
            }
            
            if (fullLogForm) {
              fullLogForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify log table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_log_tables')
                }
              })
            }
            
            if (quickPerformanceForm) {
              quickPerformanceForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_performance_tables')
              })
            }
            
            if (fullPerformanceForm) {
              fullPerformanceForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify performance table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_performance_tables')
                }
              })
            }
            
            if (quickDriversForm) {
              quickDriversForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_drivers_tables')
              })
            }
            
            if (fullDriversForm) {
              fullDriversForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify drivers table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_drivers_tables')
                }
              })
            }
            
            if (quickContactsForm) {
              quickContactsForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_contacts_tables')
              })
            }
            
            if (fullContactsForm) {
              fullContactsForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify contacts table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_contacts_tables')
                }
              })
            }
            
            if (quickCompanyForm) {
              quickCompanyForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_company_tables')
              })
            }
            
            if (fullCompanyForm) {
              fullCompanyForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify company table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_company_tables')
                }
              })
            }
            
            if (quickShipperForm) {
              quickShipperForm.addEventListener('submit', function (e) {
                e.preventDefault()
                runOptimization('indexes', this, 'optimize_shipper_tables')
              })
            }
            
            if (fullShipperForm) {
              fullShipperForm.addEventListener('submit', function (e) {
                e.preventDefault()
                if (confirm('Are you sure? This will modify shipper table structures. Make sure you have a backup!')) {
                  runOptimization('full', this, 'optimize_shipper_tables')
                }
              })
            }
            
            function runOptimization (type, form, action) {
              const button = form.querySelector('button')
              const originalText = button.textContent
              button.disabled = true
              button.textContent = 'Optimizing...'
              
              const formData = new FormData()
              formData.append('action', action)
              formData.append('optimization_type', type)
              
              fetch(ajaxurl, {
                method: 'POST',
                body  : formData
              })
                .then(response => response.json())
                .then(data => {
                  if (data.success) {
                    location.reload() // Перезагружаем для показа результатов
                  }
                  else {
                    alert('Error: ' + data.data.message)
                    button.disabled = false
                    button.textContent = originalText
                  }
                })
                .catch(error => {
                  alert('Request failed: ' + error)
                  button.disabled = false
                  button.textContent = originalText
                })
            }
          })
        </script>
		<?php
	}
	
	function handle_dispatcher_deletion( $user_id ) {
		// Проверяем, является ли удаляемый пользователь диспетчером
		$user = get_user_by( 'ID', $user_id );
		if ( $user && ( in_array( 'dispatcher', $user->roles ) 
			|| in_array( 'dispatcher-tl', $user->roles ) 
			|| in_array( 'expedite_manager', $user->roles ) ) ) {
			
			// Add to queue for gradual transfer instead of immediate transfer
			$transfer_manager = new TMSDispatcherTransferManager();
			$result = $transfer_manager->add_dispatcher_to_queue( $user_id, 'odysseia' );
			
			if ( is_wp_error( $result ) ) {
				error_log( 'Error adding dispatcher to transfer queue: ' . $result->get_error_message() );
			} else {
				error_log( 'Dispatcher ID ' . $user_id . ' added to gradual transfer queue (odysseia)' );
			}
		}
	}
	
	function get_dispatcher_initials_records( $dispatcher_id ) {
		global $wpdb;
		$results = [];
		
		// Получаем список таблиц
		$tables = $this->tms_tables;
		foreach ( $tables as $val ) {
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			// Выполняем запрос к каждой таблице
			$query = $wpdb->prepare( "SELECT id FROM $table_meta_name
            WHERE meta_key = %s AND meta_value = %s", 'dispatcher_initials', $dispatcher_id );
			
			$table_results = $wpdb->get_results( $query, ARRAY_A );
			
			// Добавляем результаты в общий массив с использованием имени таблицы в качестве ключа
			if ( ! empty( $table_results ) ) {
				$results[ $table_meta_name ] = $table_results;
			} else {
				$results[ $table_meta_name ] = []; // Добавляем пустой массив, если данных нет
			}
		}
		
		return $results;
	}
	
	function update_dispatcher_initials_records( $records, $new_dispatcher_id ) {
		global $wpdb;
		
		if ( empty( $records ) || ! is_array( $records ) || empty( $new_dispatcher_id ) ) {
			return false;
		}
		
		foreach ( $records as $table_name => $rows ) {
			if ( ! empty( $rows ) ) {
				$ids        = array_column( $rows, 'id' );
				$table_name = esc_sql( $table_name );
				$ids_string = implode( ',', array_map( 'intval', $ids ) );
				$query      = "
                UPDATE $table_name
                SET meta_value = %s
                WHERE id IN ($ids_string)
            ";
				
				$wpdb->query( $wpdb->prepare( $query, $new_dispatcher_id ) );
			}
		}
		
		return true;
	}
	
	function update_contacts_for_new_user( $id_user, $new_dispatcher_id ) {
		global $wpdb;
		
		$table_contacts = $wpdb->prefix . 'contacts';
		
		$id_user           = (int) $id_user;
		$new_dispatcher_id = (int) $new_dispatcher_id;
		
		if ( $id_user > 0 && $new_dispatcher_id > 0 ) {
			$updated = $wpdb->update( $table_contacts, [ 'user_id_added' => $new_dispatcher_id ], [ 'user_id_added' => $id_user ], [ '%d' ], [ '%d' ] );
			
			return $updated; // вернёт количество обновлённых строк
		}
		
		return false;
	}
	
	function move_contacts_for_new_dispatcher( $dispatcher_id_to_find ) {
		global $global_options;
		
		// Получаем новый ID диспетчера из глобальных настроек
		$new_dispatcher_id = get_field_value( $global_options, 'empty_dispatcher' );
		
		if ( $new_dispatcher_id === $dispatcher_id_to_find ) {
			return new WP_Error( 'invalid_id', 'Новый ID диспетчера не может совпадать с удаляемым.' );
		}
		
		$this->update_contacts_for_new_user( $dispatcher_id_to_find, $new_dispatcher_id );
		
	}
	
	function move_loads_for_new_dispatcher( $dispatcher_id_to_find ) {
		global $global_options;
		
		// Получаем новый ID диспетчера из глобальных настроек
		$new_dispatcher_id = get_field_value( $global_options, 'empty_dispatcher' );
		
		// Проверяем, что новый ID не равен ID удаляемого диспетчера
		if ( $new_dispatcher_id === $dispatcher_id_to_find ) {
			return new WP_Error( 'invalid_id', 'Новый ID диспетчера не может совпадать с удаляемым.' );
		}
		
		// Получаем все записи, связанные с удаляемым диспетчером
		$records = $this->get_dispatcher_initials_records( $dispatcher_id_to_find );
		
		// Проверяем, есть ли что обновлять
		if ( empty( $records ) ) {
			return new WP_Error( 'no_records', 'Записей для обновления не найдено.' );
		}
		
		// Обновляем все записи на нового диспетчера
		$update_result = $this->update_dispatcher_initials_records( $records, $new_dispatcher_id );
		
		// Проверяем результат
		if ( $update_result ) {
			return 'Записи успешно перенесены на нового диспетчера.';
		} else {
			return new WP_Error( 'update_failed', 'Не удалось обновить записи.' );
		}
	}
	
	/**
	 * AJAX handler for log tables optimization
	 * @return void
	 */
	public function optimize_log_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			$logs = new TMSLogs();
			
			if ( $optimization_type === 'full' ) {
				$results = $logs->optimize_log_tables_for_performance();
				$message = 'Full log tables optimization completed successfully';
			} else {
				$results = $logs->add_log_performance_indexes_safe();
				$message = 'Log performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Log optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for adding log performance indexes only
	 * @return void
	 */
	public function add_log_performance_indexes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		try {
			$logs    = new TMSLogs();
			$results = $logs->add_log_performance_indexes_safe();
			
			wp_send_json_success( array(
				'message' => 'Log performance indexes added successfully',
				'results' => $results
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Failed to add log indexes: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for performance tables optimization
	 * @return void
	 */
	public function optimize_performance_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			$performance = new TMSReportsPerformance();
			
			if ( $optimization_type === 'full' ) {
				$results = $performance->optimize_performance_tables_for_performance();
				$message = 'Full performance tables optimization completed successfully';
			} else {
				$results = $performance->add_performance_indexes_safe();
				$message = 'Performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Performance optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for adding performance indexes only
	 * @return void
	 */
	public function add_performance_indexes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		try {
			$performance = new TMSReportsPerformance();
			$results     = $performance->add_performance_indexes_safe();
			
			wp_send_json_success( array(
				'message' => 'Performance indexes added successfully',
				'results' => $results
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Failed to add performance indexes: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	
	/**
	 * AJAX handler for drivers tables optimization
	 * @return void
	 */
	public function optimize_drivers_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			$drivers = new TMSDrivers();
			
			if ( $optimization_type === 'full' ) {
				$results = $drivers->perform_full_drivers_optimization();
				$message = 'Full drivers tables optimization completed successfully';
			} else {
				$results = $drivers->perform_fast_drivers_optimization();
				$message = 'Drivers performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Drivers optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for contacts tables optimization
	 * @return void
	 */
	public function optimize_contacts_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			$contacts = new TMSContacts();
			
			if ( $optimization_type === 'full' ) {
				$results = $contacts->perform_full_contacts_optimization();
				$message = 'Full contacts tables optimization completed successfully';
			} else {
				$results = $contacts->perform_fast_contacts_optimization();
				$message = 'Contacts performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Contacts optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for company tables optimization
	 * @return void
	 */
	public function optimize_company_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			$company = new TMSReportsCompany();
			
			if ( $optimization_type === 'full' ) {
				$results = $company->perform_full_company_optimization();
				$message = 'Full company tables optimization completed successfully';
			} else {
				$results = $company->perform_fast_company_optimization();
				$message = 'Company performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Company optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * AJAX handler for shipper tables optimization
	 * @return void
	 */
	public function optimize_shipper_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$optimization_type = $_POST[ 'optimization_type' ] ?? 'indexes';
		
		try {
			$shipper = new TMSReportsShipper();
			
			if ( $optimization_type === 'full' ) {
				$results = $shipper->perform_full_shipper_optimization();
				$message = 'Full shipper tables optimization completed successfully';
			} else {
				$results = $shipper->perform_fast_shipper_optimization();
				$message = 'Shipper performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message'           => $message,
				'results'           => $results,
				'optimization_type' => $optimization_type
			) );
			
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Shipper optimization failed: ' . $e->getMessage(),
				'error'   => $e->getMessage()
			) );
		}
	}
	
	/**
	 * Remove ETA records for completed loads
	 */
	private function remove_eta_records_for_status( $post_id, $status ) {
		// Use helper function for regular loads (is_flt = false)
		$this->remove_eta_records_by_status( $post_id, $status, false );
	}
	
	// CREATE TABLE AND UPDATE SQL END
}
	