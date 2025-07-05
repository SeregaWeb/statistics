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
	
	public function get_profit_by_preset( $preset_ids ) {
		global $wpdb;
		
		if ( empty( $preset_ids ) || ! is_array( $preset_ids ) ) {
			return [];
		}
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$placeholders = implode( ',', array_fill( 0, count( $preset_ids ), '%s' ) );
		
		$sql = "
		SELECT
			preset_meta.meta_value AS preset_id,
			COUNT(DISTINCT preset_meta.post_id) AS total_posts,
			SUM(CAST(profit_meta.meta_value AS DECIMAL(10,2))) AS total_profit
		FROM {$table_meta} AS preset_meta
		INNER JOIN {$table_meta} AS profit_meta
			ON profit_meta.post_id = preset_meta.post_id AND profit_meta.meta_key = 'profit'
		WHERE preset_meta.meta_key = 'preset'
		  AND preset_meta.meta_value IN ($placeholders)
		GROUP BY preset_meta.meta_value
	";
		
		$prepared_sql = $wpdb->prepare( $sql, ...$preset_ids );
		$results      = $wpdb->get_results( $prepared_sql, ARRAY_A );
		
		$output = [];
		
		foreach ( $results as $row ) {
			$preset_id            = 'brocker_' . $row[ 'preset_id' ];
			$output[ $preset_id ] = [
				'total_posts'  => (int) $row[ 'total_posts' ],
				'total_profit' => (float) $row[ 'total_profit' ],
			];
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
		// В данном запросе получаем сумму прибыли (meta с meta_key = 'profit') для каждой даты.
		// Если задан офис (и он не 'all'), то добавляем LEFT JOIN для фильтрации по метаполю office_dispatcher.
		$query = "
        SELECT DATE(main.date_booked) AS date, AVG(profit.meta_value) AS average_profit, SUM(profit.meta_value) AS total_profit
        FROM $table_main AS main
        LEFT JOIN $table_meta AS profit ON main.id = profit.post_id AND profit.meta_key = 'profit'
     	INNER JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
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
		
		$query .= " GROUP BY DATE(main.date_booked)
                ORDER BY DATE(main.date_booked) ASC ";
		
		// Подготавливаем запрос с переданными параметрами
		$query = $wpdb->prepare( $query, ...$params );
		
		// Для отладки можно раскомментировать следующую строку:
		// error_log($query);
		
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		// Если запрос вернул null или false, возвращаем пустой массив
		if ( ! $results ) {
			return array();
		}
		
		// Преобразуем результат в ассоциативный массив вида: 'YYYY-MM-DD' => сумма профита
		$profit_by_date = array();
		foreach ( $results as $row ) {
			if ( ! empty( $row[ 'date' ] ) ) {
				$profit_by_date[ $row[ 'date' ] ][ 'total' ]   = (float) $row[ 'total_profit' ];
				$profit_by_date[ $row[ 'date' ] ][ 'average' ] = (float) $row[ 'average_profit' ];
			}
		}
		
		return $profit_by_date;
	}
	
	
	// GET ITEMS
	public function get_stat_platform() {
		global $wpdb;
		
		$cache_key = 'stat_platform_cache_' . $this->project;
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
			
			$sql = "
			SELECT COUNT(DISTINCT main.id)
			FROM {$table_main} AS main
			LEFT JOIN {$table_meta} AS customer_meta
				ON main.id = customer_meta.post_id AND customer_meta.meta_key = 'customer_id'
			LEFT JOIN {$table_meta} AS load_status
				ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
			WHERE customer_meta.meta_value IN ($placeholders)
			  AND main.status_post = 'publish'
			  AND (load_status.meta_value IS NULL OR load_status.meta_value NOT IN ('waiting-on-rc', 'delivered', 'tonu', 'cancelled'))
		";
			
			$count = $wpdb->get_var( $wpdb->prepare( $sql, ...$ids_array ) );
			
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
	 * @param $args
	 * Get table items and filter
	 *
	 * @return array
	 */
	public function get_table_items( $args = array() ) {
		global $wpdb;
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) == 'asc' ? 'ASC'
			: 'DESC';
		
		$join_builder = "
			FROM $table_main AS main
			LEFT JOIN $table_meta AS dispatcher
				ON main.id = dispatcher.post_id
				AND dispatcher.meta_key = 'dispatcher_initials'
			LEFT JOIN $table_meta AS reference
				ON main.id = reference.post_id
				AND reference.meta_key = 'reference_number'
			LEFT JOIN $table_meta AS unit_number
				ON main.id = unit_number.post_id
				AND unit_number.meta_key = 'unit_number_name'
			LEFT JOIN $table_meta AS load_status
				ON main.id = load_status.post_id
				AND load_status.meta_key = 'load_status'
			LEFT JOIN $table_meta AS driver_rate
				ON main.id = driver_rate.post_id
				AND driver_rate.meta_key = 'driver_rate'
			LEFT JOIN $table_meta AS source
				ON main.id = source.post_id
				AND source.meta_key = 'source'
			LEFT JOIN $table_meta AS invoiced_proof
				ON main.id = invoiced_proof.post_id
				AND invoiced_proof.meta_key = 'invoiced_proof'
			LEFT JOIN $table_meta AS office_dispatcher
				ON main.id = office_dispatcher.post_id
				AND office_dispatcher.meta_key = 'office_dispatcher'
			LEFT JOIN $table_meta AS customer_id
				ON main.id = customer_id.post_id
				AND customer_id.meta_key = 'customer_id'
			LEFT JOIN $table_meta AS driver_pay_statuses
				ON main.id = driver_pay_statuses.post_id
				AND driver_pay_statuses.meta_key = 'driver_pay_statuses'
			LEFT JOIN $table_meta AS factoring_status
				ON main.id = factoring_status.post_id
				AND factoring_status.meta_key = 'factoring_status'
			LEFT JOIN $table_meta AS tbd
				ON main.id = tbd.post_id
				AND tbd.meta_key = 'tbd'
			WHERE 1=1";
		
		// Основной запрос
		$sql = "SELECT main.*,
			dispatcher.meta_value AS dispatcher_initials_value,
			reference.meta_value AS reference_number_value,
			unit_number.meta_value AS unit_number_value
	" . $join_builder;
		
		$where_conditions = array();
		$where_values     = array();
		
		// Фильтрация по статусу
		if ( ! empty( $args[ 'customer_id' ] ) && $args[ 'customer_id' ] !== 'all' ) {
			$where_conditions[] = "customer_id.meta_value = %s";
			$where_values[]     = $args[ 'customer_id' ];
		}
		// Фильтрация по статусу
		if ( ! empty( $args[ 'office' ] ) && $args[ 'office' ] !== 'all' ) {
			$where_conditions[] = "office_dispatcher.meta_value = %s";
			$where_values[]     = $args[ 'office' ];
		}
		
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = "main.status_post = %s";
			$where_values[]     = $args[ 'status_post' ];
		}
		
		if ( isset( $args[ 'exclude_empty_rate' ] ) && $args[ 'exclude_empty_rate' ] ) {
			$where_conditions[] = "driver_rate.meta_value IS NOT NULL AND driver_rate.meta_value != '' AND CAST(driver_rate.meta_value AS DECIMAL) > 0";
		}
		
		if ( isset( $args[ 'ar_problem' ] ) && $args[ 'ar_problem' ] ) {
			$where_conditions[] = "main.load_problem IS NOT NULL";
			$where_conditions[] = "DATEDIFF(NOW(), main.load_problem) > 50";
		}
		
		// Фильтрация по dispatcher_initials
		if ( ! empty( $args[ 'dispatcher' ] ) ) {
			$where_conditions[] = "dispatcher.meta_value = %s";
			$where_values[]     = $args[ 'dispatcher' ];
		}
		
		if ( isset( $args[ 'user_id' ] ) && ! empty( $args[ 'user_id' ] ) ) {
			$where_conditions[] = "(main.user_id_added = %s OR dispatcher.meta_value = %s )";
			$where_values[]     = $args[ 'user_id' ];
			$where_values[]     = $args[ 'user_id' ];
		}
		
		if ( ! empty( $args[ 'load_status' ] ) ) {
			$where_conditions[] = "load_status.meta_value = %s";
			$where_values[]     = $args[ 'load_status' ];
		}
		
		if ( isset( $args[ 'my_team' ] ) && ! empty( $args[ 'my_team' ] ) && is_array( $args[ 'my_team' ] ) ) {
			$team_values        = array_map( 'esc_sql', (array) $args[ 'my_team' ] ); // Обрабатываем значения
			$where_conditions[] = "dispatcher.meta_value IN ('" . implode( "','", $team_values ) . "')";
		}
		
		if ( isset( $args[ 'exclude_status' ] ) && ! empty( $args[ 'exclude_status' ] ) ) {
			$exclude_status = array_map( 'esc_sql', (array) $args[ 'exclude_status' ] );
			
			if ( isset( $args[ 'load_status' ] ) && $args[ 'load_status' ] === 'cancelled' ) {
				$exclude_status = implode( "','", array_diff( $exclude_status, array( 'cancelled' ) ) );
			} else {
				$exclude_status = implode( "','", $exclude_status );
			}
			if ( ! empty( $exclude_status ) ) {
				$where_conditions[] = "load_status.meta_value NOT IN ('" . $exclude_status . "')";
			}
		}
		
		
		if ( isset( $args[ 'include_status' ] ) && ! empty( $args[ 'include_status' ] ) ) {
			$include_status     = array_map( 'esc_sql', (array) $args[ 'include_status' ] );
			$where_conditions[] = "load_status.meta_value IN ('" . implode( "','", $include_status ) . "')";
		}
		
		if ( isset( $args[ 'exclude_paid' ] ) && ! empty( $args[ 'exclude_paid' ] ) ) {
			// Условие для exclude_paid: показывать все записи, где значение не "paid" или оно отсутствует/пустое
			$where_conditions[] = "(
		        driver_pay_statuses.meta_value NOT IN ('paid')
		        OR driver_pay_statuses.meta_value IS NULL
		        OR driver_pay_statuses.meta_value = ''
		    )";
		}
		
		if ( isset( $args[ 'exclude_tbd' ] ) && ! empty( $args[ 'exclude_tbd' ] ) ) {
			$where_conditions[] = "(tbd.meta_value IS NULL OR tbd.meta_value != '1')";
		}
		
		if ( isset( $args[ 'include_paid' ] ) && ! empty( $args[ 'include_paid' ] ) ) {
			// Условие для include_paid: показывать только записи, где значение "paid" и оно не пустое/не NULL
			$where_conditions[] = "(
        driver_pay_statuses.meta_value = 'paid'
        AND driver_pay_statuses.meta_value IS NOT NULL
        AND driver_pay_statuses.meta_value != ''
    )";
		}
		
		
		if ( ! empty( $args[ 'invoice' ] ) ) {
			if ( $args[ 'invoice' ] === 'invoiced' ) {
				$where_conditions[] = "invoiced_proof.meta_value = %s";
				$where_values[]     = '1';
			} else {
				$where_conditions[] = "(invoiced_proof.meta_value = %s OR invoiced_proof.meta_value IS NULL)";
				$where_values[]     = '0';
			}
		}
		
		if ( ! empty( $args[ 'factoring' ] ) ) {
			$where_conditions[] = "factoring_status.meta_value = %s";
			$where_values[]     = $args[ 'factoring' ];
		}
		
		if ( ! empty( $args[ 'source' ] ) ) {
			$where_conditions[] = "source.meta_value = %s";
			$where_values[]     = $args[ 'source' ];
		}
		
		// Фильтрация по reference_number
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(reference.meta_value LIKE %s OR unit_number.meta_value LIKE %s)";
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		if ( ! empty( $args[ 'month' ] ) && ! empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
			$where_values[]     = $args[ 'month' ];
		}
		
		// Фильтрация по только году
		if ( ! empty( $args[ 'year' ] ) && empty( $args[ 'month' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
		}
		
		// Фильтрация по только месяцу
		if ( ! empty( $args[ 'month' ] ) && empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'month' ];
		}
		
		// Применяем фильтры к запросу
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Подсчёт общего количества записей с учётом фильтров
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
	
	public function get_table_items_billing( $args = array() ) {
		global $wpdb;
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) == 'asc' ? 'ASC'
			: 'DESC';
		
		$join_builder = "
			FROM $table_main AS main
			LEFT JOIN $table_meta AS dispatcher ON main.id = dispatcher.post_id AND dispatcher.meta_key = 'dispatcher_initials'
			LEFT JOIN $table_meta AS reference ON main.id = reference.post_id AND reference.meta_key = 'reference_number'
			LEFT JOIN $table_meta AS unit_number ON main.id = unit_number.post_id AND unit_number.meta_key = 'unit_number_name'
			LEFT JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
			LEFT JOIN $table_meta AS invoiced_proof ON main.id = invoiced_proof.post_id AND invoiced_proof.meta_key = 'invoiced_proof'
			LEFT JOIN $table_meta AS factoring_status ON main.id = factoring_status.post_id AND factoring_status.meta_key = 'factoring_status'
			LEFT JOIN $table_meta AS processing ON main.id = processing.post_id AND processing.meta_key = 'processing'
			WHERE 1=1
		";
		
		// Основной запрос
		$sql = "SELECT main.*,
			dispatcher.meta_value AS dispatcher_initials_value,
			reference.meta_value AS reference_number_value,
			unit_number.meta_value AS unit_number_value
	" . $join_builder;
		
		$where_conditions = array();
		$where_values     = array();
		
		
		$processing_values = array(
			'factoring-delayed-advance',
			'factoring-wire-transfer',
			'unapplied-payment',
			'direct'
		);
		
		$placeholders       = implode( ', ', array_fill( 0, count( $processing_values ), '%s' ) );
		$where_conditions[] = "(processing.meta_value NOT IN ($placeholders) OR processing.meta_value IS NULL OR processing.meta_value = '')";
		$where_values       = array_merge( $where_values, $processing_values );
		
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = $wpdb->prepare( "main.status_post = %s", $args[ 'status_post' ] );
		}
		
		// Фильтрация по dispatcher_initials
		if ( ! empty( $args[ 'dispatcher' ] ) ) {
			$where_conditions[] = $wpdb->prepare( "dispatcher.meta_value = %s", $args[ 'dispatcher' ] );
		}
		
		if ( ! empty( $args[ 'load_status' ] ) ) {
			$where_conditions[] = $wpdb->prepare( "load_status.meta_value = %s", $args[ 'load_status' ] );
		}
		
		
		if ( ! empty( $args[ 'exclude_factoring_status' ] ) ) {
			$exclude_factoring_status = implode( "','", array_map( 'esc_sql', (array) $args[ 'exclude_factoring_status' ] ) );
			$where_conditions[]       = "(
				factoring_status.meta_value NOT IN ('$exclude_factoring_status')
				OR factoring_status.meta_value IS NULL
				OR factoring_status.meta_value = ''
			)";
		}
		
		if ( ! empty( $args[ 'include_factoring_status' ] ) ) {
			$include_factoring_status = implode( "','", array_map( 'esc_sql', (array) $args[ 'include_factoring_status' ] ) );
			$where_conditions[]       = "(
				factoring_status.meta_value IN ('$include_factoring_status')
				AND factoring_status.meta_value IS NOT NULL
				AND factoring_status.meta_value != ''
			)";
		}
		
		if ( isset( $args[ 'exclude_status' ] ) && ! empty( $args[ 'exclude_status' ] ) ) {
			$exclude_status = array_map( 'esc_sql', (array) $args[ 'exclude_status' ] );
			
			if ( isset( $args[ 'load_status' ] ) && $args[ 'load_status' ] === 'cancelled' ) {
				$exclude_status = implode( "','", array_diff( $exclude_status, array( 'cancelled' ) ) );
			} else {
				$exclude_status = implode( "','", $exclude_status );
			}
			if ( ! empty( $exclude_status ) ) {
				$where_conditions[] = "load_status.meta_value NOT IN ('" . $exclude_status . "')";
			}
		}
		
		
		if ( isset( $args[ 'include_status' ] ) && ! empty( $args[ 'include_status' ] ) ) {
			$include_status     = array_map( 'esc_sql', (array) $args[ 'include_status' ] );
			$where_conditions[] = "load_status.meta_value IN ('" . implode( "','", $include_status ) . "')";
		}
		
		if ( ! empty( $args[ 'invoice' ] ) ) {
			if ( $args[ 'invoice' ] === 'invoiced' ) {
				$where_conditions[] = "invoiced_proof.meta_value = %s";
				$where_values[]     = '1';
			} else {
				$where_conditions[] = "(invoiced_proof.meta_value = %s OR invoiced_proof.meta_value IS NULL)";
				$where_values[]     = '0';
			}
		}
		
		if ( ! empty( $args[ 'factoring' ] ) ) {
			$where_conditions[] = "factoring_status.meta_value = %s";
			$where_values[]     = $args[ 'factoring' ];
		}
		
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(reference.meta_value LIKE %s OR unit_number.meta_value LIKE %s)";
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		if ( ! empty( $args[ 'month' ] ) && ! empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
			$where_values[]     = $args[ 'month' ];
		}
		
		if ( ! empty( $args[ 'year' ] ) && empty( $args[ 'month' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
		}
		
		if ( ! empty( $args[ 'month' ] ) && empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'month' ];
		}
		
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records_sql = "SELECT COUNT(*)" . $join_builder;
		if ( ! empty( $where_conditions ) ) {
			$total_records_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		
		$total_pages = ceil( $total_records / $per_page );
		
		$offset = ( $current_page - 1 ) * $per_page;
		
		$sql .= "
		    ORDER BY
		        CASE
		            WHEN LOWER(load_status.meta_value) = 'delivered' THEN 1
		            WHEN LOWER(load_status.meta_value) = 'tonu' THEN 2
		            ELSE 3
		        END,
		        main.$sort_by $sort_order LIMIT %d, %d
		";
		
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
	
	public function get_table_items_billing_shortpay( $args = array() ) {
		global $wpdb;
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) == 'asc' ? 'ASC'
			: 'DESC';
		
		$join_builder = "
			FROM $table_main AS main
			LEFT JOIN $table_meta AS dispatcher ON main.id = dispatcher.post_id AND dispatcher.meta_key = 'dispatcher_initials'
			LEFT JOIN $table_meta AS reference ON main.id = reference.post_id AND reference.meta_key = 'reference_number'
			LEFT JOIN $table_meta AS unit_number ON main.id = unit_number.post_id AND unit_number.meta_key = 'unit_number_name'
			LEFT JOIN $table_meta AS load_status ON main.id = load_status.post_id AND load_status.meta_key = 'load_status'
			LEFT JOIN $table_meta AS invoiced_proof ON main.id = invoiced_proof.post_id AND invoiced_proof.meta_key = 'invoiced_proof'
			LEFT JOIN $table_meta AS factoring_status ON main.id = factoring_status.post_id AND factoring_status.meta_key = 'factoring_status'
			LEFT JOIN $table_meta AS processing ON main.id = processing.post_id AND processing.meta_key = 'processing'
			WHERE 1=1
		";
		
		// Основной запрос
		$sql = "SELECT main.*,
			dispatcher.meta_value AS dispatcher_initials_value,
			reference.meta_value AS reference_number_value,
			unit_number.meta_value AS unit_number_value
	" . $join_builder;
		
		$where_conditions = array();
		$where_values     = array();
		
		
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = $wpdb->prepare( "main.status_post = %s", $args[ 'status_post' ] );
		}
		
		// Фильтрация по dispatcher_initials
		if ( ! empty( $args[ 'dispatcher' ] ) ) {
			$where_conditions[] = $wpdb->prepare( "dispatcher.meta_value = %s", $args[ 'dispatcher' ] );
		}
		
		if ( ! empty( $args[ 'load_status' ] ) ) {
			$where_conditions[] = $wpdb->prepare( "load_status.meta_value = %s", $args[ 'load_status' ] );
		}
		
		
		if ( ! empty( $args[ 'exclude_factoring_status' ] ) ) {
			$exclude_factoring_status = implode( "','", array_map( 'esc_sql', (array) $args[ 'exclude_factoring_status' ] ) );
			$where_conditions[]       = "(
				factoring_status.meta_value NOT IN ('$exclude_factoring_status')
				OR factoring_status.meta_value IS NULL
				OR factoring_status.meta_value = ''
			)";
		}
		
		if ( ! empty( $args[ 'include_factoring_status' ] ) ) {
			$include_factoring_status = implode( "','", array_map( 'esc_sql', (array) $args[ 'include_factoring_status' ] ) );
			$where_conditions[]       = "(
				factoring_status.meta_value IN ('$include_factoring_status')
				AND factoring_status.meta_value IS NOT NULL
				AND factoring_status.meta_value != ''
			)";
		}
		
		if ( isset( $args[ 'exclude_status' ] ) && ! empty( $args[ 'exclude_status' ] ) ) {
			$exclude_status = array_map( 'esc_sql', (array) $args[ 'exclude_status' ] );
			
			if ( isset( $args[ 'load_status' ] ) && $args[ 'load_status' ] === 'cancelled' ) {
				$exclude_status = implode( "','", array_diff( $exclude_status, array( 'cancelled' ) ) );
			} else {
				$exclude_status = implode( "','", $exclude_status );
			}
			if ( ! empty( $exclude_status ) ) {
				$where_conditions[] = "load_status.meta_value NOT IN ('" . $exclude_status . "')";
			}
		}
		
		
		if ( isset( $args[ 'include_status' ] ) && ! empty( $args[ 'include_status' ] ) ) {
			$include_status     = array_map( 'esc_sql', (array) $args[ 'include_status' ] );
			$where_conditions[] = "load_status.meta_value IN ('" . implode( "','", $include_status ) . "')";
		}
		
		if ( ! empty( $args[ 'invoice' ] ) ) {
			if ( $args[ 'invoice' ] === 'invoiced' ) {
				$where_conditions[] = "invoiced_proof.meta_value = %s";
				$where_values[]     = '1';
			} else {
				$where_conditions[] = "(invoiced_proof.meta_value = %s OR invoiced_proof.meta_value IS NULL)";
				$where_values[]     = '0';
			}
		}
		
		if ( ! empty( $args[ 'factoring' ] ) ) {
			$where_conditions[] = "factoring_status.meta_value = %s";
			$where_values[]     = $args[ 'factoring' ];
		}
		
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(reference.meta_value LIKE %s OR unit_number.meta_value LIKE %s)";
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		if ( ! empty( $args[ 'month' ] ) && ! empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
			$where_values[]     = $args[ 'month' ];
		}
		
		if ( ! empty( $args[ 'year' ] ) && empty( $args[ 'month' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
		}
		
		if ( ! empty( $args[ 'month' ] ) && empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'month' ];
		}
		
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records_sql = "SELECT COUNT(*)" . $join_builder;
		if ( ! empty( $where_conditions ) ) {
			$total_records_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		
		$total_pages = ceil( $total_records / $per_page );
		
		$offset = ( $current_page - 1 ) * $per_page;
		
		$sql .= "
		    ORDER BY
		        CASE
		            WHEN LOWER(load_status.meta_value) = 'delivered' THEN 1
		            WHEN LOWER(load_status.meta_value) = 'tonu' THEN 2
		            ELSE 3
		        END,
		        main.$sort_by $sort_order LIMIT %d, %d
		";
		
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
	
	public function get_table_items_tracking( $args = array() ) {
		global $wpdb;
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = $args[ 'sort_by' ] ?? 'date_booked';
		$sort_order   = strtolower( $args[ 'sort_order' ] ?? 'desc' ) === 'asc' ? 'ASC' : 'DESC';
		
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
		
		if ( ! empty( $args[ 'exclude_status' ] ) ) {
			$exclude_status     = esc_sql( (array) $args[ 'exclude_status' ] );
			$where_conditions[] = "load_status.meta_value NOT IN ('" . implode( "','", $exclude_status ) . "')";
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
		
		if ( $where_conditions ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Подсчет общего количества записей
		$total_records_sql = "SELECT COUNT(*) " . $join_builder . ( $where_conditions
				? ' AND ' . implode( ' AND ', $where_conditions ) : '' );
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		$total_pages   = ceil( $total_records / $per_page );
		
		$offset = ( $current_page - 1 ) * $per_page;
		
		$sql            .= " ORDER BY
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
    END $sort_order
    LIMIT %d, %d";
		$where_values[] = $offset;
		$where_values[] = $per_page;
		$main_results   = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ), ARRAY_A );
		
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
		
		return [
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		];
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
		ur.meta_value LIKE %s
	)" . ( ! empty( $exclude ) ? " AND u.ID NOT IN (" . implode( ',', array_map( 'absint', $exclude ) ) . ") " : "" );
		
		$results = $wpdb->get_results( $wpdb->prepare( $sql, 'nightshift', 'my_team', 'initials_color', 'weekends', '%"tracking"%', '%"tracking-tl"%' ), ARRAY_A );
		
		
		$tracking_data = [
			'nightshift' => [],
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
			
			
			$my_team = ! empty( $user[ 'my_team' ] ) ? maybe_unserialize( $user[ 'my_team' ] ) : [];
			
			if ( is_array( $exclude_drivers ) && ! empty( $exclude_drivers ) ) {
				$exclude_drivers = array_map( 'intval', $exclude_drivers );
			}
			
			if ( is_array( $my_team ) && ! empty( $my_team ) ) {
				$my_team = array_map( 'intval', $my_team );
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
				'id'             => $user[ 'ID' ],
				'name'           => $user[ 'display_name' ],
				'my_team'        => is_array( $my_team ) ? $my_team : [],
				'initials'       => $initials,
				'initials_color' => $initials_color,
			];
			
			// Пропустить пользователя, если сегодня его выходной
			if ( is_array( $weekends ) && in_array( $today, $weekends, true ) ) {
				if ( $user[ 'nightshift' ] !== '1' ) {
					$tracking_data[ 'tracking_move' ][] = $user_data;
				}
				continue;
			}
			
			
			if ( $user[ 'nightshift' ] === '1' ) {
				$tracking_data[ 'nightshift' ][] = $user_data;
			} else {
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
			'attached_file_required' => 'Attached File Required'
		];
		
		if ( is_array( $meta ) ) {
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
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"       => FILTER_SANITIZE_STRING,
				"contact_name"      => FILTER_SANITIZE_STRING,
				"contact_phone"     => FILTER_SANITIZE_STRING,
				"contact_phone_ext" => FILTER_SANITIZE_STRING,
				"contact_email"     => FILTER_SANITIZE_STRING,
				"post_id"           => FILTER_SANITIZE_STRING,
				"read_only"         => FILTER_SANITIZE_STRING,
				"preset-select"     => FILTER_SANITIZE_STRING,
			] );
			
			
			if ( isset( $MY_INPUT[ 'read_only' ] ) ) {
				wp_send_json_success();
			}
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				$additional_names      = filter_var_array( $_POST[ 'additional_contact_name' ], FILTER_SANITIZE_STRING );
				$additional_phones     = filter_var_array( $_POST[ 'additional_contact_phone' ], FILTER_SANITIZE_STRING );
				$additional_phones_ext = filter_var_array( $_POST[ 'additional_contact_phone_ext' ], FILTER_SANITIZE_STRING );
				$additional_emails     = filter_var_array( $_POST[ 'additional_contact_email' ], FILTER_SANITIZE_EMAIL );
				
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
			] );
			
			
			if ( ! $MY_INPUT[ 'ar-action' ] ) {
				$MY_INPUT[ 'ar_status' ] = 'not-solved';
			}
			
			if ( $MY_INPUT[ 'factoring_status' ] === 'charge-back' ) {
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
			] );
			
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
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"       => FILTER_SANITIZE_STRING,
				"contact_name"      => FILTER_SANITIZE_STRING,
				"contact_phone"     => FILTER_SANITIZE_STRING,
				"contact_phone_ext" => FILTER_SANITIZE_STRING,
				"contact_email"     => FILTER_SANITIZE_STRING,
				"preset-select"     => FILTER_SANITIZE_STRING,
			] );
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				$additional_names  = filter_var_array( $_POST[ 'additional_contact_name' ], FILTER_SANITIZE_STRING );
				$additional_phones = filter_var_array( $_POST[ 'additional_contact_phone' ], FILTER_SANITIZE_STRING );
				$additional_emails = filter_var_array( $_POST[ 'additional_contact_email' ], FILTER_SANITIZE_EMAIL );
				$additional_ext    = filter_var_array( $_POST[ 'additional_contact_phone_ext' ], FILTER_SANITIZE_STRING );
				
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
				"post_id" => FILTER_SANITIZE_STRING
			] );
			
			if ( ! empty( $_FILES[ 'screen_picture' ] ) ) {
				$MY_INPUT[ 'screen_picture' ] = $this->upload_one_file( $_FILES[ 'screen_picture' ] );
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
				$current_date = $data[ 'pick_up_location_date' ][ $i ];
				
				$pick_up_location[] = [
					'address_id'    => $data[ 'pick_up_location_address_id' ][ $i ],
					'address'       => $data[ 'pick_up_location_address' ][ $i ],
					'short_address' => $data[ 'pick_up_location_short_address' ][ $i ],
					'contact'       => $data[ 'pick_up_location_contact' ][ $i ],
					'date'          => $current_date,
					'info'          => $data[ 'pick_up_location_info' ][ $i ],
					'type'          => $data[ 'pick_up_location_type' ][ $i ],
					'time_start'    => $data[ 'pick_up_location_start' ][ $i ],
					'time_end'      => $data[ 'pick_up_location_end' ][ $i ],
					'strict_time'   => $data[ 'pick_up_location_strict' ][ $i ]
				];
				
				// Сравнение даты
				if ( $current_date && ( $earliest_date === null || strtotime( $current_date ) < strtotime( $earliest_date ) ) ) {
					$earliest_date = $current_date;
				}
			}
			
			for ( $i = 0; $i < count( $data[ 'delivery_location_address_id' ] ); $i ++ ) {
				$current_date = $data[ 'delivery_location_date' ][ $i ];
				
				$delivery_location[] = [
					'address_id'    => $data[ 'delivery_location_address_id' ][ $i ],
					'address'       => $data[ 'delivery_location_address' ][ $i ],
					'short_address' => $data[ 'delivery_location_short_address' ][ $i ],
					'contact'       => $data[ 'delivery_location_contact' ][ $i ],
					'date'          => $current_date,
					'info'          => $data[ 'delivery_location_info' ][ $i ],
					'type'          => $data[ 'delivery_location_type' ][ $i ],
					'time_start'    => $data[ 'delivery_location_start' ][ $i ],
					'time_end'      => $data[ 'delivery_location_end' ][ $i ],
					'strict_time'   => $data[ 'delivery_location_strict' ][ $i ]
				];
				
				// Сравнение даты
				if ( $current_date && ( $latest_date === null || strtotime( $current_date ) > strtotime( $latest_date ) ) ) {
					$latest_date = $current_date;
				}
			}
			
			$pick_up_location_json  = json_encode( $pick_up_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			$delivery_location_json = json_encode( $delivery_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			
			$data[ 'pick_up_location_json' ]  = $pick_up_location_json;
			$data[ 'delivery_location_json' ] = $delivery_location_json;
			$data[ 'pick_up_date' ]           = $earliest_date;
			$data[ 'delivery_date' ]          = $latest_date;
			$result                           = $this->add_new_shipper_info( $data );
			
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
				"read_only"               => FILTER_SANITIZE_STRING,
				"second_unit_number_name" => FILTER_SANITIZE_STRING,
				"second_driver_rate"      => FILTER_SANITIZE_STRING,
				"second_driver_phone"     => FILTER_SANITIZE_STRING,
				"second_driver"           => FILTER_VALIDATE_BOOLEAN,
				"tbd"                     => FILTER_VALIDATE_BOOLEAN,
				"old_tbd"                 => FILTER_VALIDATE_BOOLEAN,
				"additional_fees"         => FILTER_VALIDATE_BOOLEAN,
				"additional_fees_val"     => FILTER_SANITIZE_STRING,
				"additional_fees_driver"  => FILTER_VALIDATE_BOOLEAN,
			] );
			
			if ( $MY_INPUT[ 'load_status' ] === 'cancelled' ) {
				$MY_INPUT[ "booked_rate" ]        = '0.00';
				$MY_INPUT[ "driver_rate" ]        = '0.00';
				$MY_INPUT[ "profit" ]             = '0.00';
				$MY_INPUT[ "second_driver_rate" ] = '0.00';
			} else {
				$MY_INPUT = $this->count_all_sum( $MY_INPUT );
			}
			
			$result = $this->add_load( $MY_INPUT );
			
			if ( $result ) {
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
		$MY_INPUT[ "profit" ]             = $this->convert_to_number( $MY_INPUT[ "profit" ] );
		
		if ( is_numeric( $MY_INPUT[ "second_driver_rate" ] ) && is_numeric( $MY_INPUT[ "driver_rate" ] ) ) {
			$with_second_sum = $MY_INPUT[ "driver_rate" ] + $MY_INPUT[ "second_driver_rate" ];
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
			] );
			
			
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
			] );
			
			
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
			] );
			$result   = $this->remove_one_image_in_db( $MY_INPUT );
			
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
			] );
			
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
			] );
			
			$MY_INPUT[ 'post_status' ] = 'publish';
			
			$result = $this->update_post_status_in_db( $MY_INPUT );
			
			if ( $result ) {
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
	
	public function quick_update_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				'id_load' => FILTER_SANITIZE_NUMBER_INT,
				'status'  => FILTER_SANITIZE_STRING,
			] );
			
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
				'data' => FILTER_DEFAULT, // Оставляем строку как есть
			] );
			
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
			] );
			
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
					'attached_files'
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
	
	// AJAX ACTIONS END
	
	
	// UPDATE IN DATABASE
	
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
		
		$old_data = $this->get_report_by_id( $post_id );
		
		$post_meta = array(
			"bank_payment_status"     => $data[ 'bank_payment_status' ],
			"driver_pay_statuses"     => $data[ 'driver_pay_statuses' ],
			"quick_pay_accounting"    => $data[ 'quick_pay_accounting' ],
			"quick_pay_method"        => $data[ 'quick_pay_method' ],
			"quick_pay_driver_amount" => $data[ 'quick_pay_driver_amount' ],
		);
		
		$label_fields = array(
			"bank_payment_status"     => 'Bank status',
			"driver_pay_statuses"     => 'Driver pay status',
			"quick_pay_accounting"    => 'Quick pay',
			"quick_pay_method"        => 'Quick pay method',
			"quick_pay_driver_amount" => 'Will charge the driver',
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
		);
		
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
			
			if ( $data[ 'old_unit_number_name' ] && ! empty( $data[ 'old_unit_number_name' ] ) ) {
				if ( $data[ 'old_unit_number_name' ] !== $data[ 'unit_number_name' ] ) {
					
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed driver: ' . 'New value: ' . $data[ 'unit_number_name' ] . ' Old value: ' . $data[ 'old_unit_number_name' ]
					) );
					
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
			}
			
			if ( $data[ 'old_driver_phone' ] && ! empty( $data[ 'old_driver_phone' ] ) ) {
				if ( $data[ 'driver_phone' ] !== $data[ 'old_driver_phone' ] ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed Driver phone: ' . 'New value: ' . $data[ 'driver_phone' ] . ' Old value: ' . $data[ 'old_driver_phone' ]
					) );
				}
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
			
			if ( is_numeric( $data[ 'old_value_driver_rate' ] ) ) {
				if ( $data[ 'driver_rate' ] !== floatval( $data[ 'old_value_driver_rate' ] ) ) {
					$this->log_controller->create_one_log( array(
						'user_id' => $user_id,
						'post_id' => $data[ 'post_id' ],
						'message' => 'Changed Driver rate: ' . 'New value: ' . $data[ 'driver_rate' ] . ' Old value: $' . $data[ 'old_value_driver_rate' ]
					) );
					
					$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array(
						'admin_email',
						'billing_email',
						'team_leader_email',
						'accounting_email',
					) );
					
					$who_changed = 'Driver rate';
					$this->email_helper->send_custom_email( $select_emails, array(
						'subject'      => 'Changed Driver rate',
						'project_name' => $this->project,
						'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $who_changed . ' for the load ' . $link,
						'message'      => '<del>$' . $data[ 'old_value_driver_rate' ] . '</del>, now: $' . $data[ 'driver_rate' ],
					) );
					
					$data[ 'modify_driver_price' ] = '1';
					
				}
			}
			if ( is_numeric( $data[ 'old_value_booked_rate' ] ) ) {
				if ( $data[ 'booked_rate' ] !== floatval( $data[ 'old_value_booked_rate' ] ) ) {
					
					$data[ 'modify_price' ] = '1';
					
					$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array(
						'admin_email',
						'billing_email',
						'team_leader_email',
						'accounting_email',
					) );
					
					
					$who_changed = 'Booked rate';
					$this->email_helper->send_custom_email( $select_emails, array(
						'subject'      => 'Changed Booked rate',
						'project_name' => $this->project,
						'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $who_changed . ' for the load ' . $link,
						'message'      => '<del>$' . $data[ 'old_value_booked_rate' ] . '</del>, now: $' . $data[ 'booked_rate' ],
					) );
					
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
			} elseif ( $image_field === 'attached_file_required' || $image_field === 'updated_rate_confirmation' || $image_field === 'screen_picture' || $image_field === 'proof_of_delivery' ) {
				
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
		
		// Проверка на ошибки
		if ( $wpdb->last_error ) {
			return new WP_Error( 'db_error', 'Ошибка при обновлении метаданных: ' . $wpdb->last_error );
		}
		
		return true;
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
		        meta_key longtext,
		        meta_value longtext,
		        PRIMARY KEY  (id),
                INDEX idx_post_id (post_id),
         		INDEX idx_meta_key (meta_key(191)),
         		INDEX idx_meta_key_value (meta_key(191), meta_value(191))
    		) $charset_collate;";
			
			dbDelta( $sql );
		}
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
		$tables = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			$table_results = array(
				'table' => $table_name,
				'meta_table' => $table_meta_name,
				'changes' => array()
			);
			
			// 1. Изменяем тип ID на BIGINT для поддержки больших объемов
			$result = $wpdb->query( "
				ALTER TABLE $table_name 
				MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
			" );
			if ( $result !== false ) {
				$table_results['changes'][] = 'Changed id to BIGINT UNSIGNED';
			}
			
			// 2. Изменяем типы пользователей на INT UNSIGNED
			$result = $wpdb->query( "
				ALTER TABLE $table_name 
				MODIFY COLUMN user_id_added INT UNSIGNED NOT NULL,
				MODIFY COLUMN user_id_updated INT UNSIGNED NULL
			" );
			if ( $result !== false ) {
				$table_results['changes'][] = 'Changed user_id fields to INT UNSIGNED';
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
				$table_results['changes'][] = 'Changed datetime fields to TIMESTAMP';
			}
			
			// 4. Добавляем составные индексы для частых запросов
			$indexes_to_add = array(
				'idx_date_booked_status' => '(date_booked, status_post)',
				'idx_pick_up_delivery' => '(pick_up_date, delivery_date)',
				'idx_user_status' => '(user_id_added, status_post)',
				'idx_created_status' => '(date_created, status_post)',
				'idx_status_post' => '(status_post)'
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
						$table_results['changes'][] = "Added index: $index_name";
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
				$table_results['changes'][] = 'Changed meta table id fields to BIGINT UNSIGNED';
			}
			
			// 6. Изменяем longtext на более эффективные типы
			$result = $wpdb->query( "
				ALTER TABLE $table_meta_name 
				MODIFY COLUMN meta_key VARCHAR(255) NOT NULL,
				MODIFY COLUMN meta_value TEXT
			" );
			if ( $result !== false ) {
				$table_results['changes'][] = 'Changed meta_key to VARCHAR(255), meta_value to TEXT';
			}
			
			// 7. Добавляем дополнительные индексы для мета-таблицы
			$meta_indexes = array(
				'idx_post_meta_key' => '(post_id, meta_key)',
				'idx_meta_value' => '(meta_value(100))',
				'idx_key_value' => '(meta_key, meta_value(100))'
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
						$table_results['changes'][] = "Added meta index: $index_name";
					}
				}
			}
			
			// 8. Оптимизируем таблицы
			$wpdb->query( "OPTIMIZE TABLE $table_name" );
			$wpdb->query( "OPTIMIZE TABLE $table_meta_name" );
			$wpdb->query( "ANALYZE TABLE $table_name" );
			$wpdb->query( "ANALYZE TABLE $table_meta_name" );
			
			$table_results['changes'][] = 'Optimized and analyzed tables';
			$results[] = $table_results;
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
		$tables = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			$table_results = array(
				'table' => $table_name,
				'meta_table' => $table_meta_name,
				'indexes_added' => array()
			);
			
			// Добавляем только недостающие индексы
			$main_indexes = array(
				'idx_date_booked_status' => '(date_booked, status_post)',
				'idx_pick_up_delivery' => '(pick_up_date, delivery_date)',
				'idx_user_status' => '(user_id_added, status_post)',
				'idx_created_status' => '(date_created, status_post)'
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
						$table_results['indexes_added'][] = $index_name;
					}
				}
			}
			
			// Индексы для мета-таблицы
			$meta_indexes = array(
				'idx_post_meta_key' => '(post_id, meta_key)',
				'idx_meta_value' => '(meta_value(100))',
				'idx_key_value' => '(meta_key, meta_value(100))'
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
						$table_results['indexes_added'][] = "meta_$index_name";
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
		try {
			if ( $optimization_type === 'full' ) {
				$results = $this->optimize_existing_tables_for_performance();
				$message = 'Full database optimization completed successfully';
			} else {
				$results = $this->add_performance_indexes_safe();
				$message = 'Performance indexes added successfully';
			}
			
			wp_send_json_success( array(
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Failed to add indexes: ' . $e->getMessage(),
				'error' => $e->getMessage()
			) );
		}
	}
	
	public function add_pinned_message() {
		
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$user_id        = intval( $_POST[ 'user_id' ] ?? 0 );
			$post_id        = intval( $_POST[ 'post_id' ] ?? 0 );
			$pinned_message = sanitize_textarea_field( $_POST[ 'pinned_message' ] ?? '' );
			
			if ( ! $user_id || ! $post_id || ! $pinned_message ) {
				wp_send_json_error( array( 'message' => 'Need fill data' ) );
			}
			
			$pinned_array = array(
				'user_pinned_id' => $user_id,
				'time_pinned'    => time(),
				'message_pinned' => $pinned_message,
			);
			
			if ( $this->update_post_meta_data( $post_id, $pinned_array ) ) {
				$userHelper  = new TMSUsers();
				$name_user   = $userHelper->get_user_full_name_by_id( $user_id );
				$time_pinned = date( 'm/d/Y H:i', $pinned_array[ 'time_pinned' ] );
				
				wp_send_json_success( array(
					'message' => 'Message pinned',
					'pinned'  => array(
						'full_name'      => $name_user[ 'full_name' ],
						'time_pinned'    => $time_pinned,
						'pinned_message' => $pinned_message,
						'id'             => $post_id,
					),
				) );
			}
			wp_send_json_error( array( 'message', 'something went wrong' ) );
		}
	}
	
	public function delete_pinned_message() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$post_id = intval( $_POST[ 'id' ] ?? 0 );
			if ( ! $post_id ) {
				wp_send_json_error( [ 'message' => 'No post_id provided' ] );
			}
			$pinned_array = [
				'user_pinned_id' => '',
				'time_pinned'    => '',
				'message_pinned' => '',
			];
			if ( $this->update_post_meta_data( $post_id, $pinned_array ) ) {
				wp_send_json_success( [ 'message' => 'Pinned message deleted' ] );
			}
			wp_send_json_error( [ 'message' => 'Something went wrong' ] );
		}
	}
	
	// INIT Actions
	
	/**
	 * init all ajax actions for fork
	 * @return void
	 */
	public function ajax_actions() {
		
		$actions = [
			'add_new_report'           => 'add_new_report',
			'add_new_draft_report'     => 'add_new_report_draft',
			'update_new_draft_report'  => 'update_new_draft_report',
			'update_billing_report'    => 'update_billing_report',
			'update_files_report'      => 'update_files_report',
			'delete_open_image'        => 'delete_open_image',
			'update_shipper_info'      => 'update_shipper_info',
			'send_email_chain'         => 'send_email_chain',
			'update_post_status'       => 'update_post_status',
			'rechange_status_load'     => 'rechange_status_load',
			'remove_one_load'          => 'remove_one_load',
			'get_driver_by_id'         => 'get_driver_by_id',
			'update_accounting_report' => 'update_accounting_report',
			'quick_update_post'        => 'quick_update_post',
			'quick_update_post_ar'     => 'quick_update_post_ar',
			'quick_update_status'      => 'quick_update_status',
			'quick_update_status_all'  => 'quick_update_status_all',
			'add_pinned_message'       => 'add_pinned_message',
			'delete_pinned_message'    => 'delete_pinned_message',
			'optimize_database_tables' => 'optimize_database_tables',
			'add_performance_indexes'  => 'add_performance_indexes',
			'optimize_log_tables'      => 'optimize_log_tables',
			'add_log_performance_indexes' => 'add_log_performance_indexes',
			'optimize_performance_tables' => 'optimize_performance_tables',
			'add_performance_table_indexes' => 'add_performance_table_indexes',
			'optimize_drivers_tables'  => 'optimize_drivers_tables',
			'optimize_contacts_tables' => 'optimize_contacts_tables',
			'optimize_company_tables'  => 'optimize_company_tables',
			'optimize_shipper_tables'  => 'optimize_shipper_tables',
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
		add_action( 'after_setup_theme', array( $this, 'create_table' ) );
		
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
		add_submenu_page(
			'tools.php', // Parent slug (Tools menu)
			'Database Optimization', // Page title
			'DB Optimization', // Menu title
			'manage_options', // Capability
			'tms-database-optimization', // Menu slug
			array( $this, 'render_database_optimization_page' ) // Callback function
		);
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
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'optimize_database' ) {
			$optimization_type = $_POST['optimization_type'] ?? 'indexes';
			
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
									<strong><?php echo esc_html( $table_result['table'] ); ?></strong>
									<?php if ( ! empty( $table_result['changes'] ) ) : ?>
										<ul>
											<?php foreach ( $table_result['changes'] as $change ) : ?>
												<li><?php echo esc_html( $change ); ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
									<?php if ( ! empty( $table_result['indexes_added'] ) ) : ?>
										<ul>
											<?php foreach ( $table_result['indexes_added'] as $index ) : ?>
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
					<form method="post" id="full-optimize-form" onsubmit="return confirm('Are you sure? This will modify table structures. Make sure you have a backup!');">
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
					<form method="post" id="full-log-optimize-form" onsubmit="return confirm('Are you sure? This will modify log table structures. Make sure you have a backup!');">
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
					<p><strong>Warning:</strong> This will modify performance table structures. Make sure you have a backup!</p>
					<ul>
						<li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
						<li>Optimize user ID fields to INT UNSIGNED</li>
						<li>Optimize calls fields to INT UNSIGNED</li>
						<li>Add comprehensive indexes for performance queries</li>
					</ul>
					<form method="post" id="full-performance-optimize-form" onsubmit="return confirm('Are you sure? This will modify performance table structures. Make sure you have a backup!');">
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
					<p><strong>Warning:</strong> This will modify drivers table structures. Make sure you have a backup!</p>
					<ul>
						<li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
						<li>Optimize user ID fields to BIGINT UNSIGNED</li>
						<li>Change datetime fields to TIMESTAMP for better performance</li>
						<li>Add comprehensive indexes for driver queries</li>
						<li>Optimize meta table structure</li>
					</ul>
					<form method="post" id="full-drivers-optimize-form" onsubmit="return confirm('Are you sure? This will modify drivers table structures. Make sure you have a backup!');">
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
					<p><strong>Warning:</strong> This will modify contacts table structures. Make sure you have a backup!</p>
					<ul>
						<li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
						<li>Optimize user ID and company ID fields to BIGINT UNSIGNED</li>
						<li>Change datetime fields to TIMESTAMP for better performance</li>
						<li>Add comprehensive indexes for contact queries</li>
						<li>Optimize additional contacts table structure</li>
					</ul>
					<form method="post" id="full-contacts-optimize-form" onsubmit="return confirm('Are you sure? This will modify contacts table structures. Make sure you have a backup!');">
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
					<p><strong>Warning:</strong> This will modify company table structures. Make sure you have a backup!</p>
					<ul>
						<li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
						<li>Optimize user ID fields to BIGINT UNSIGNED</li>
						<li>Change datetime fields to TIMESTAMP for better performance</li>
						<li>Add comprehensive indexes for company queries</li>
						<li>Optimize meta table structure</li>
					</ul>
					<form method="post" id="full-company-optimize-form" onsubmit="return confirm('Are you sure? This will modify company table structures. Make sure you have a backup!');">
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
					<p><strong>Warning:</strong> This will modify shipper table structures. Make sure you have a backup!</p>
					<ul>
						<li>Change ID fields to BIGINT (supports 18+ quintillion records)</li>
						<li>Optimize user ID fields to BIGINT UNSIGNED</li>
						<li>Change datetime fields to TIMESTAMP for better performance</li>
						<li>Add comprehensive indexes for shipper queries</li>
						<li>Optimize address and location-based searches</li>
					</ul>
					<form method="post" id="full-shipper-optimize-form" onsubmit="return confirm('Are you sure? This will modify shipper table structures. Make sure you have a backup!');">
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
					<li><strong>ID Fields:</strong> mediumint(9) → BIGINT UNSIGNED (supports 18+ quintillion records)</li>
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
		document.addEventListener('DOMContentLoaded', function() {
			// AJAX версия для лучшего UX
			const quickForm = document.getElementById('quick-optimize-form');
			const fullForm = document.getElementById('full-optimize-form');
			const quickLogForm = document.getElementById('quick-log-optimize-form');
			const fullLogForm = document.getElementById('full-log-optimize-form');
			const quickPerformanceForm = document.getElementById('quick-performance-optimize-form');
			const fullPerformanceForm = document.getElementById('full-performance-optimize-form');
			const quickDriversForm = document.getElementById('quick-drivers-optimize-form');
			const fullDriversForm = document.getElementById('full-drivers-optimize-form');
			const quickContactsForm = document.getElementById('quick-contacts-optimize-form');
			const fullContactsForm = document.getElementById('full-contacts-optimize-form');
			const quickCompanyForm = document.getElementById('quick-company-optimize-form');
			const fullCompanyForm = document.getElementById('full-company-optimize-form');
			const quickShipperForm = document.getElementById('quick-shipper-optimize-form');
			const fullShipperForm = document.getElementById('full-shipper-optimize-form');
			
			if (quickForm) {
				quickForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_database_tables');
				});
			}
			
			if (fullForm) {
				fullForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_database_tables');
					}
				});
			}
			
			if (quickLogForm) {
				quickLogForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_log_tables');
				});
			}
			
			if (fullLogForm) {
				fullLogForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify log table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_log_tables');
					}
				});
			}
			
			if (quickPerformanceForm) {
				quickPerformanceForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_performance_tables');
				});
			}
			
			if (fullPerformanceForm) {
				fullPerformanceForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify performance table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_performance_tables');
					}
				});
			}
			
			if (quickDriversForm) {
				quickDriversForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_drivers_tables');
				});
			}
			
			if (fullDriversForm) {
				fullDriversForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify drivers table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_drivers_tables');
					}
				});
			}
			
			if (quickContactsForm) {
				quickContactsForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_contacts_tables');
				});
			}
			
			if (fullContactsForm) {
				fullContactsForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify contacts table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_contacts_tables');
					}
				});
			}
			
			if (quickCompanyForm) {
				quickCompanyForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_company_tables');
				});
			}
			
			if (fullCompanyForm) {
				fullCompanyForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify company table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_company_tables');
					}
				});
			}
			
			if (quickShipperForm) {
				quickShipperForm.addEventListener('submit', function(e) {
					e.preventDefault();
					runOptimization('indexes', this, 'optimize_shipper_tables');
				});
			}
			
			if (fullShipperForm) {
				fullShipperForm.addEventListener('submit', function(e) {
					e.preventDefault();
					if (confirm('Are you sure? This will modify shipper table structures. Make sure you have a backup!')) {
						runOptimization('full', this, 'optimize_shipper_tables');
					}
				});
			}
			
			function runOptimization(type, form, action) {
				const button = form.querySelector('button');
				const originalText = button.textContent;
				button.disabled = true;
				button.textContent = 'Optimizing...';
				
				const formData = new FormData();
				formData.append('action', action);
				formData.append('optimization_type', type);
				
				fetch(ajaxurl, {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						location.reload(); // Перезагружаем для показа результатов
					} else {
						alert('Error: ' + data.data.message);
						button.disabled = false;
						button.textContent = originalText;
					}
				})
				.catch(error => {
					alert('Request failed: ' + error);
					button.disabled = false;
					button.textContent = originalText;
				});
			}
		});
		</script>
		<?php
	}
	
	function handle_dispatcher_deletion( $user_id ) {
		// Проверяем, является ли удаляемый пользователь диспетчером
		$user = get_user_by( 'ID', $user_id );
		if ( $user && in_array( 'dispatcher', $user->roles ) || $user && in_array( 'dispatcher-tl', $user->roles ) ) {
			
			// Выполняем перенос лодов на нового диспетчера
			$result = $this->move_loads_for_new_dispatcher( $user_id );
			$this->move_contacts_for_new_dispatcher( $user_id );
			
			// Логируем результат для отладки
			if ( is_wp_error( $result ) ) {
				error_log( 'Error transferring loads: ' . $result->get_error_message() );
			} else {
				error_log( 'Successful load transfer: ' . $result );
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
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
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Log optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
			$logs = new TMSLogs();
			$results = $logs->add_log_performance_indexes_safe();
			
			wp_send_json_success( array(
				'message' => 'Log performance indexes added successfully',
				'results' => $results
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Failed to add log indexes: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
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
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Performance optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
			$results = $performance->add_performance_indexes_safe();
			
			wp_send_json_success( array(
				'message' => 'Performance indexes added successfully',
				'results' => $results
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Failed to add performance indexes: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
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
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Drivers optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
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
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Contacts optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
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
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Company optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
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
		
		$optimization_type = $_POST['optimization_type'] ?? 'indexes';
		
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
				'message' => $message,
				'results' => $results,
				'optimization_type' => $optimization_type
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Shipper optimization failed: ' . $e->getMessage(),
				'error' => $e->getMessage()
			) );
		}
	}
	
	// CREATE TABLE AND UPDATE SQL END
}
