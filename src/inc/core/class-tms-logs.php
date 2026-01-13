<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSLogs extends TMSReports {
	public $use_project = '';
	
	public function __construct() {
		
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$curent_tables = get_field( 'current_select', 'user_' . $user_id );
			if ( $curent_tables ) {
				$this->use_project = strtolower( $curent_tables );
			}
		}
	}
	
	public function init() {
		$this->create_log_tables();
		$this->ajax_actions();
	}
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_user_log', array( $this, 'add_user_log' ) );
	}
	
	/**
	 * Optimize log tables for large datasets (500k+ records)
	 * Safe to run on existing data - no data loss
	 * @return array
	 */
	public function optimize_log_tables_for_performance() {
		global $wpdb;
		
		$results = array();
		$tables  = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$log_table_name = $wpdb->prefix . 'reports_logs_' . strtolower( $val );
			
			$table_results = array(
				'table'   => $log_table_name,
				'changes' => array()
			);
			
			// 1. Изменяем тип ID на BIGINT для поддержки больших объемов
			$result = $wpdb->query( "
				ALTER TABLE $log_table_name 
				MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed id to BIGINT UNSIGNED';
			}
			
			// 2. Изменяем типы ID загрузки и пользователя на INT UNSIGNED
			$result = $wpdb->query( "
				ALTER TABLE $log_table_name 
				MODIFY COLUMN id_load INT UNSIGNED NOT NULL,
				MODIFY COLUMN id_user INT UNSIGNED NOT NULL
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed id_load and id_user to INT UNSIGNED';
			}
			
			// 3. Изменяем datetime на TIMESTAMP для лучшей производительности
			$result = $wpdb->query( "
				ALTER TABLE $log_table_name 
				MODIFY COLUMN log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed log_date to TIMESTAMP';
			}
			
			// 4. Изменяем longtext на TEXT для лучшей производительности
			$result = $wpdb->query( "
				ALTER TABLE $log_table_name 
				MODIFY COLUMN log_text TEXT NOT NULL
			" );
			if ( $result !== false ) {
				$table_results[ 'changes' ][] = 'Changed log_text from LONGTEXT to TEXT';
			}
			
			// 5. Добавляем составные индексы для частых запросов
			$indexes_to_add = array(
				'idx_load_date'     => '(id_load, log_date)',
				'idx_user_date'     => '(id_user, log_date)',
				'idx_priority_date' => '(log_priority, log_date)',
				'idx_date_priority' => '(log_date, log_priority)',
				'idx_load_user'     => '(id_load, id_user)',
				'idx_role_date'     => '(user_role, log_date)'
			);
			
			foreach ( $indexes_to_add as $index_name => $index_columns ) {
				// Проверяем, существует ли индекс
				$index_exists = $wpdb->get_var( "
					SHOW INDEX FROM $log_table_name WHERE Key_name = '$index_name'
				" );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $log_table_name ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results[ 'changes' ][] = "Added index: $index_name";
					}
				}
			}
			
			// 6. Оптимизируем таблицу
			$wpdb->query( "OPTIMIZE TABLE $log_table_name" );
			$wpdb->query( "ANALYZE TABLE $log_table_name" );
			
			$table_results[ 'changes' ][] = 'Optimized and analyzed table';
			$results[]                    = $table_results;
		}
		
		// Оптимизируем таблицу drivers_logs
		$drivers_log_table = $wpdb->prefix . 'drivers_logs';
		
		$drivers_results = array(
			'table'   => $drivers_log_table,
			'changes' => array()
		);
		
		// 1. Изменяем тип ID на BIGINT
		$result = $wpdb->query( "
			ALTER TABLE $drivers_log_table 
			MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
		" );
		if ( $result !== false ) {
			$drivers_results[ 'changes' ][] = 'Changed id to BIGINT UNSIGNED';
		}
		
		// 2. Изменяем типы ID загрузки и пользователя
		$result = $wpdb->query( "
			ALTER TABLE $drivers_log_table 
			MODIFY COLUMN id_load INT UNSIGNED NOT NULL,
			MODIFY COLUMN id_user INT UNSIGNED NOT NULL
		" );
		if ( $result !== false ) {
			$drivers_results[ 'changes' ][] = 'Changed id_load and id_user to INT UNSIGNED';
		}
		
		// 3. Изменяем datetime на TIMESTAMP
		$result = $wpdb->query( "
			ALTER TABLE $drivers_log_table 
			MODIFY COLUMN log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		" );
		if ( $result !== false ) {
			$drivers_results[ 'changes' ][] = 'Changed log_date to TIMESTAMP';
		}
		
		// 4. Изменяем longtext на TEXT
		$result = $wpdb->query( "
			ALTER TABLE $drivers_log_table 
			MODIFY COLUMN log_text TEXT NOT NULL
		" );
		if ( $result !== false ) {
			$drivers_results[ 'changes' ][] = 'Changed log_text from LONGTEXT to TEXT';
		}
		
		// 5. Добавляем составные индексы для drivers_logs
		$drivers_indexes = array(
			'idx_load_date'     => '(id_load, log_date)',
			'idx_user_date'     => '(id_user, log_date)',
			'idx_priority_date' => '(log_priority, log_date)',
			'idx_date_priority' => '(log_date, log_priority)',
			'idx_load_user'     => '(id_load, id_user)',
			'idx_role_date'     => '(user_role, log_date)'
		);
		
		foreach ( $drivers_indexes as $index_name => $index_columns ) {
			$index_exists = $wpdb->get_var( "
				SHOW INDEX FROM $drivers_log_table WHERE Key_name = '$index_name'
			" );
			
			if ( ! $index_exists ) {
				$result = $wpdb->query( "
					ALTER TABLE $drivers_log_table ADD INDEX $index_name $index_columns
				" );
				if ( $result !== false ) {
					$drivers_results[ 'changes' ][] = "Added index: $index_name";
				}
			}
		}
		
		// 6. Оптимизируем drivers_logs таблицу
		$wpdb->query( "OPTIMIZE TABLE $drivers_log_table" );
		$wpdb->query( "ANALYZE TABLE $drivers_log_table" );
		
		$drivers_results[ 'changes' ][] = 'Optimized and analyzed table';
		$results[]                      = $drivers_results;
		
		return $results;
	}
	
	/**
	 * Add performance indexes to existing log tables (safe operation)
	 * @return array
	 */
	public function add_log_performance_indexes_safe() {
		global $wpdb;
		
		$results = array();
		$tables  = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$log_table_name = $wpdb->prefix . 'reports_logs_' . strtolower( $val );
			
			$table_results = array(
				'table'         => $log_table_name,
				'indexes_added' => array()
			);
			
			// Добавляем только недостающие индексы
			$main_indexes = array(
				'idx_load_date'     => '(id_load, log_date)',
				'idx_user_date'     => '(id_user, log_date)',
				'idx_priority_date' => '(log_priority, log_date)',
				'idx_date_priority' => '(log_date, log_priority)',
				'idx_load_user'     => '(id_load, id_user)',
				'idx_role_date'     => '(user_role, log_date)'
			);
			
			foreach ( $main_indexes as $index_name => $index_columns ) {
				$index_exists = $wpdb->get_var( "
					SHOW INDEX FROM $log_table_name WHERE Key_name = '$index_name'
				" );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $log_table_name ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results[ 'indexes_added' ][] = $index_name;
					}
				}
			}
			
			$results[] = $table_results;
		}
		
		// Добавляем индексы для drivers_logs
		$drivers_log_table = $wpdb->prefix . 'drivers_logs';
		
		$drivers_results = array(
			'table'         => $drivers_log_table,
			'indexes_added' => array()
		);
		
		$drivers_indexes = array(
			'idx_load_date'     => '(id_load, log_date)',
			'idx_user_date'     => '(id_user, log_date)',
			'idx_priority_date' => '(log_priority, log_date)',
			'idx_date_priority' => '(log_date, log_priority)',
			'idx_load_user'     => '(id_load, id_user)',
			'idx_role_date'     => '(user_role, log_date)'
		);
		
		foreach ( $drivers_indexes as $index_name => $index_columns ) {
			$index_exists = $wpdb->get_var( "
				SHOW INDEX FROM $drivers_log_table WHERE Key_name = '$index_name'
			" );
			
			if ( ! $index_exists ) {
				$result = $wpdb->query( "
					ALTER TABLE $drivers_log_table ADD INDEX $index_name $index_columns
				" );
				if ( $result !== false ) {
					$drivers_results[ 'indexes_added' ][] = $index_name;
				}
			}
		}
		
		$results[] = $drivers_results;
		
		return $results;
	}
	
	
	public function get_last_log_by_post( $post_id, $post_type = 'report' ) {
		global $wpdb;
		
		// Определяем таблицу логов для текущего проекта
		$logs_table = $this->get_table_by_post_type( $post_type );
		
		// Проверяем, существует ли таблица
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) !== $logs_table ) {
			return 'Logs table does not exist for the current project.';
		}
		
		// SQL-запрос для получения последнего добавленного лога
		$query = $wpdb->prepare( "
	        SELECT id, id_user, user_name, user_role, log_priority, log_date, log_text
	        FROM $logs_table
	        WHERE id_load = %d
	        ORDER BY log_date DESC
	        LIMIT 1
	    ", $post_id );
		
		$log = $wpdb->get_row( $query );
		
		// Проверяем, найден ли лог
		if ( ! $log ) {
			return '';
		}
		
		// Генерируем карточку лога с помощью вашей функции
		return $this->generate_log_card( [
			'role'    => $log->user_role,
			'name'    => $log->user_name,
			'date'    => $log->log_date,
			'message' => $log->log_text,
		] );
	}
	
	
	public function add_user_log() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			// Получение данных из POST
			$MY_INPUT = filter_var_array( $_POST, [
				"user_id"   => FILTER_SANITIZE_NUMBER_INT,
				"post_id"   => FILTER_SANITIZE_NUMBER_INT,
				"post_type" => FILTER_SANITIZE_STRING,
				"message"   => FILTER_UNSAFE_RAW, // Don't sanitize message to preserve quotes
				"priority"  => FILTER_SANITIZE_NUMBER_INT,
				"project"   => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'project' ] ) && strtolower( $MY_INPUT[ 'project' ] ) !== $this->use_project ) {
				wp_send_json_error( [
					'message' => 'You have changed the project
					need to switch back, current project - ' . $this->use_project . ' previous - ' . strtolower( $MY_INPUT[ 'project' ] )
				] );
			}
			
			// Проверка необходимых данных
			if ( ! $MY_INPUT[ 'user_id' ] || ! $MY_INPUT[ 'post_id' ] || ! $MY_INPUT[ 'post_type' ] || ! $MY_INPUT[ 'message' ] ) {
				wp_send_json_error( [ 'message' => 'Missing data' ] );
			}
			
			// Clean message from any escaped characters
			$MY_INPUT[ 'message' ] = stripslashes( $MY_INPUT[ 'message' ] );
			
			$result = $this->create_one_log( $MY_INPUT );
			
			if ( $result[ 'insert' ] === false ) {
				wp_send_json_error( [ 'message' => 'Error adding record to log.' ] );
			}
			
			// Генерация HTML карточки
			$html = $this->generate_log_card( [
				'role'    => $result[ 'user_role' ],
				'name'    => $result[ 'user_name' ],
				'date'    => current_time( 'mysql' ),
				'message' => $MY_INPUT[ 'message' ],
			] );
			
			wp_send_json_success( [ 'template' => $html ] );
		}
		
					wp_send_json_error( [ 'message' => 'Invalid request.' ] );
	}
	
	function create_one_log( $array_data ) {
		
		global $wpdb;
		
		$user_info = get_userdata( $array_data[ 'user_id' ] );
		if ( ! $user_info ) {
			wp_send_json_error( [ 'message' => 'User not found.' ] );
		}
		
		if ( ! isset( $array_data[ 'post_type' ] ) ) {
			$array_data[ 'post_type' ] = 'report';
		}
		
		$user_name    = $user_info->display_name;
		$user_role    = implode( ', ', $user_info->roles );
		$log_priority = $array_data[ 'priority' ] ?? 0;
		
		// Имя таблицы
		$table_name       = $this->get_table_by_post_type( $array_data[ 'post_type' ] );
		$date_est         = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ); // Указываем временную зону EST
		$current_time_est = $date_est->format( 'Y-m-d H:i:s' );
		
		// Debug: Log table name for debugging
		error_log( 'TMS Logs: Saving to table: ' . $table_name . ' for post_type: ' . $array_data[ 'post_type' ] );
		
		// Добавление записи в таблицу
		$result = $wpdb->insert( $table_name, [
			'id_load'      => $array_data[ 'post_id' ],
			'id_user'      => $array_data[ 'user_id' ],
			'user_name'    => $user_name,
			'user_role'    => $user_role,
			'log_priority' => $log_priority,
			'log_date'     => $current_time_est,
			'log_text'     => $array_data[ 'message' ],
		], [ '%d', '%d', '%s', '%s', '%d', '%s', '%s' ] );
		
		return array(
			'insert'    => $result,
			'user_name' => $user_name,
			'user_role' => $user_role
		);
	}
	
	public function generate_log_card( $log_data ) {
		$role    = esc_html( $log_data[ 'role' ] );
		$name    = esc_html( $log_data[ 'name' ] );
		$date    = $this->formatDate( $log_data[ 'date' ] );
		$message = $this->clean_message_for_display( $log_data[ 'message' ] );
		return "
		<div class='log-card {$role}'>
			<span class='log-card__role'>{$role}</span>
			<div class='log-card__top'>
				<p class='log-card__name'>{$name}</p>
				<p class='log-card__time'>{$date}</p>
			</div>
			<div class='log-card__message'>
				{$message}
			</div>
		</div>";
	}
	
	/**
	 * Clean message for display - remove escaped quotes and other unwanted characters
	 * 
	 * @param string $message Raw message
	 * @return string Cleaned message
	 */
	private function clean_message_for_display( $message ) {
		// Remove escaped quotes
		$message = str_replace( array( "\\'", '\\"' ), array( "'", '"' ), $message );
		
		// Remove other common escaped characters
		$message = str_replace( array( '\\\\', '\\n', '\\r', '\\t' ), array( '\\', "\n", "\r", "\t" ), $message );
		
		// Convert line breaks to HTML (but don't escape HTML tags)
		$message = nl2br( $message );
		
		return $message;
	}
	
	public function delete_all_logs( $post_id, $post_type = 'report' ) {
		global $wpdb;
		
		$logs_table = $this->get_table_by_post_type( $post_type );
		
		// Проверяем существование таблицы
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) !== $logs_table ) {
			return new WP_Error( 'table_not_found', 'Logs table does not exist.' );
		}
		
		// Удаляем все записи по id_load
		$query  = $wpdb->prepare( "DELETE FROM $logs_table WHERE id_load = %d", $post_id );
		$result = $wpdb->query( $query );
		
		// Проверяем, были ли удалены записи
		if ( $result === false ) {
			return new WP_Error( 'delete_failed', 'Failed to delete logs.' );
		}
		
		return $result; // Возвращаем количество удаленных записей
	}
	
	public function get_all_logs( $post_id, $post_type = 'report' ) {
		global $wpdb;
		
		$logs_table = $this->get_table_by_post_type( $post_type );
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) !== $logs_table ) {
			return false;
		}
		
		$query = $wpdb->prepare( "
        SELECT id, id_user, user_name, user_role, log_priority, log_date, log_text
        FROM $logs_table
        WHERE id_load = %d
        ORDER BY log_date DESC
    ", $post_id );
		
		$logs = $wpdb->get_results( $query );
		
		// Формируем HTML для логов
		if ( empty( $logs ) ) {
			return false;
		}
		
		$output = "";
		
		foreach ( $logs as $log ) {
			$log_text = str_replace( '<br>', "\n", $log->log_text ); // Заменяем <br> на \n
			
			$output .= "User: {$log->user_name}\n";
			$output .= "Role: {$log->user_role}\n";
			$output .= "Date: {$log->log_date}\n";
			$output .= "Message: {$log_text}\n";
			$output .= "\n"; // Empty line to separate logs
		}
		
		$output .= "--- End of Logs ---\n";
		
		return $output;
	}
	
	public function get_table_by_post_type( $post_type = 'report' ) {
		global $wpdb;
		
		// Initialize with default table
		$logs_table = $wpdb->prefix . 'reports_logs_' . strtolower( $this->use_project );
		
		if ( $post_type === 'report' ) {
			$logs_table = $wpdb->prefix . 'reports_logs_' . strtolower( $this->use_project );
		}
		
		if ( $post_type === 'reports_flt' ) {
			$logs_table = $wpdb->prefix . 'reports_logs_flt_' . strtolower( $this->use_project );
		}
		
		if ( $post_type === 'tracking' ) {
			// Check if FLT parameter is present to determine the correct table
			$flt = isset( $_POST['flt'] ) ? $_POST['flt'] : '';
			if ( $flt ) {
				$logs_table = $wpdb->prefix . 'reports_logs_flt_' . strtolower( $this->use_project );
			} else {
				$logs_table = $wpdb->prefix . 'reports_logs_' . strtolower( $this->use_project );
			}
		}
		
		if ( $post_type === 'driver' ) {
			$logs_table = $wpdb->prefix . 'drivers_logs';
		}
		
		return $logs_table;
	}
	
	public function get_user_logs_by_post( $post_id, $user_id, $post_type = 'report' ) {
		global $wpdb;
		
		// Определяем таблицу логов для текущего проекта
		$logs_table = $this->get_table_by_post_type( $post_type );
		
		// Проверяем, существует ли таблица
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) !== $logs_table ) {
			return 'Logs table does not exist for the current project.';
		}
		
		// Получаем роль пользователя
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return 'User not found.';
		}
		$user_role = reset( $user->roles );
		
		// Роли, которые не должны видеть сообщения от роли "billing"
		$restricted_roles = [ 'tracking', 'dispatcher', 'recruiter', 'morning_tracking', 'nightshift_tracking' ];
		
		// SQL-запрос с фильтрацией по роли, посту и сортировкой по дате
		$query = $wpdb->prepare( "
        SELECT id, id_user, user_name, user_role, log_priority, log_date, log_text
        FROM $logs_table
        WHERE id_load = %d
          AND (user_role != 'billing' OR %s NOT IN ('tracking', 'dispatcher', 'recruiter', 'morning_tracking', 'nightshift_tracking'))
        ORDER BY log_date DESC
    ", $post_id, $user_role );
		
		$logs = $wpdb->get_results( $query );
		
		// Формируем HTML для логов
		if ( empty( $logs ) ) {
			return '<p>No logs available for this post.</p>';
		}
		
		
		$html_output = '';
		foreach ( $logs as $log ) {
			// Используем вашу функцию generate_log_card
			$html_output .= $this->generate_log_card( [
				'role'    => $log->user_role,
				'name'    => $log->user_name,
				'date'    => $log->log_date,
				'message' => $log->log_text,
			] );
		}
		
		return $html_output;
	}
	
	
	public function create_log_tables() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		// Создаем оптимизированные обычные таблицы логов
		foreach ( $tables as $val ) {
			$log_table_name  = $wpdb->prefix . 'reports_logs_' . strtolower( $val );
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $log_table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_load INT UNSIGNED NOT NULL,
            id_user INT UNSIGNED NOT NULL,
            user_name varchar(255) NOT NULL,
            user_role varchar(100) NOT NULL,
            log_priority smallint(5) NOT NULL DEFAULT 0,
            log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            log_text TEXT NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_id_load (id_load),
            INDEX idx_id_user (id_user),
            INDEX idx_log_priority (log_priority),
            INDEX idx_log_date (log_date),
            INDEX idx_load_date (id_load, log_date),
            INDEX idx_user_date (id_user, log_date),
            INDEX idx_priority_date (log_priority, log_date),
            INDEX idx_date_priority (log_date, log_priority),
            INDEX idx_load_user (id_load, id_user),
            INDEX idx_role_date (user_role, log_date)
        ) $charset_collate;";
			
			dbDelta( $sql );
		}
		
		// Создаем оптимизированные FLT таблицы логов
		foreach ( $tables as $val ) {
			$log_table_name  = $wpdb->prefix . 'reports_logs_flt_' . strtolower( $val );
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $log_table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_load INT UNSIGNED NOT NULL,
            id_user INT UNSIGNED NOT NULL,
            user_name varchar(255) NOT NULL,
            user_role varchar(100) NOT NULL,
            log_priority smallint(5) NOT NULL DEFAULT 0,
            log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            log_text TEXT NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_id_load (id_load),
            INDEX idx_id_user (id_user),
            INDEX idx_log_priority (log_priority),
            INDEX idx_log_date (log_date),
            INDEX idx_load_date (id_load, log_date),
            INDEX idx_user_date (id_user, log_date),
            INDEX idx_priority_date (log_priority, log_date),
            INDEX idx_date_priority (log_date, log_priority),
            INDEX idx_load_user (id_load, id_user),
            INDEX idx_role_date (user_role, log_date)
        ) $charset_collate;";
			
			dbDelta( $sql );
		}
		
		// Создаем оптимизированную таблицу логов водителей
		$log_table_name  = $wpdb->prefix . 'drivers_logs';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $log_table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_load INT UNSIGNED NOT NULL,
            id_user INT UNSIGNED NOT NULL,
            user_name varchar(255) NOT NULL,
            user_role varchar(100) NOT NULL,
            log_priority smallint(5) NOT NULL DEFAULT 0,
            log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            log_text TEXT NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_id_load (id_load),
            INDEX idx_id_user (id_user),
            INDEX idx_log_priority (log_priority),
            INDEX idx_log_date (log_date),
            INDEX idx_load_date (id_load, log_date),
            INDEX idx_user_date (id_user, log_date),
            INDEX idx_priority_date (log_priority, log_date),
            INDEX idx_date_priority (log_date, log_priority),
            INDEX idx_load_user (id_load, id_user),
            INDEX idx_role_date (user_role, log_date)
        ) $charset_collate;";
		
		dbDelta( $sql );
	}
}