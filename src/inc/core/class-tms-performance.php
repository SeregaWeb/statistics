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
	
	/**
	 * Optimize performance tables for large datasets (500k+ records)
	 * Safe to run on existing data - no data loss
	 * @return array
	 */
	public function optimize_performance_tables_for_performance() {
		global $wpdb;
		
		$results = array();
		$table_name = $wpdb->prefix . $this->table_main;
		
		$table_results = array(
			'table' => $table_name,
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
			MODIFY COLUMN user_id INT UNSIGNED NOT NULL,
			MODIFY COLUMN user_last_updated INT UNSIGNED NULL
		" );
		if ( $result !== false ) {
			$table_results['changes'][] = 'Changed user_id fields to INT UNSIGNED';
		}
		
		// 3. Изменяем типы звонков на INT UNSIGNED для лучшей производительности
		$result = $wpdb->query( "
			ALTER TABLE $table_name 
			MODIFY COLUMN monday_calls INT UNSIGNED DEFAULT 0,
			MODIFY COLUMN tuesday_calls INT UNSIGNED DEFAULT 0,
			MODIFY COLUMN wednesday_calls INT UNSIGNED DEFAULT 0,
			MODIFY COLUMN thursday_calls INT UNSIGNED DEFAULT 0,
			MODIFY COLUMN friday_calls INT UNSIGNED DEFAULT 0,
			MODIFY COLUMN saturday_calls INT UNSIGNED DEFAULT 0,
			MODIFY COLUMN sunday_calls INT UNSIGNED DEFAULT 0
		" );
		if ( $result !== false ) {
			$table_results['changes'][] = 'Changed calls fields to INT UNSIGNED';
		}
		
		// 4. Оптимизируем тип bonus
		$result = $wpdb->query( "
			ALTER TABLE $table_name 
			MODIFY COLUMN bonus DECIMAL(10,2) DEFAULT 0.00
		" );
		if ( $result !== false ) {
			$table_results['changes'][] = 'Optimized bonus field';
		}
		
		// 5. Добавляем составные индексы для частых запросов
		$indexes_to_add = array(
			'idx_user_date_range' => '(user_id, date)',
			'idx_date_user' => '(date, user_id)',
			'idx_user_updated' => '(user_id, user_last_updated)',
			'idx_date_range' => '(date)',
			'idx_user_performance' => '(user_id, monday_calls, tuesday_calls, wednesday_calls, thursday_calls, friday_calls, saturday_calls, sunday_calls)',
			'idx_bonus_range' => '(bonus)'
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
		
		// 6. Оптимизируем таблицу
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$wpdb->query( "ANALYZE TABLE $table_name" );
		
		$table_results['changes'][] = 'Optimized and analyzed table';
		$results[] = $table_results;
		
		return $results;
	}
	
	/**
	 * Add performance indexes to existing performance tables (safe operation)
	 * @return array
	 */
	public function add_performance_indexes_safe() {
		global $wpdb;
		
		$results = array();
		$table_name = $wpdb->prefix . $this->table_main;
		
		$table_results = array(
			'table' => $table_name,
			'indexes_added' => array()
		);
		
		// Добавляем только недостающие индексы
		$main_indexes = array(
			'idx_user_date_range' => '(user_id, date)',
			'idx_date_user' => '(date, user_id)',
			'idx_user_updated' => '(user_id, user_last_updated)',
			'idx_date_range' => '(date)',
			'idx_user_performance' => '(user_id, monday_calls, tuesday_calls, wednesday_calls, thursday_calls, friday_calls, saturday_calls, sunday_calls)',
			'idx_bonus_range' => '(bonus)'
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
		
		$results[] = $table_results;
		
		return $results;
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