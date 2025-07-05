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
	
	
	public function get_last_log_by_post( $post_id ) {
		global $wpdb;
		
		// Определяем таблицу логов для текущего проекта
		$logs_table = $wpdb->prefix . 'reports_logs_' . $this->use_project;
		
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
				"message"   => FILTER_SANITIZE_STRING,
				"priority"  => FILTER_SANITIZE_NUMBER_INT,
			] );
			
			// Проверка необходимых данных
			if ( ! $MY_INPUT[ 'user_id' ] || ! $MY_INPUT[ 'post_id' ] || ! $MY_INPUT[ 'post_type' ] || ! $MY_INPUT[ 'message' ] ) {
				wp_send_json_error( [ 'message' => 'Missing data' ] );
			}
			
			$result = $this->create_one_log( $MY_INPUT );
			
			if ( $result[ 'insert' ] === false ) {
				wp_send_json_error( [ 'message' => 'Ошибка при добавлении записи в лог.' ] );
			}
			
			// Генерация HTML карточки
			$html = $this->generate_log_card( [
				'role'    => $result[ 'user_role' ],
				'name'    => $result[ 'user_name' ],
				'date'    => date( 'm/d/Y H:i', strtotime( current_time( 'mysql' ) ) ),
				'message' => $MY_INPUT[ 'message' ],
			] );
			
			wp_send_json_success( [ 'template' => $html ] );
		}
		
		wp_send_json_error( [ 'message' => 'Неверный запрос.' ] );
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
		$message = $log_data[ 'message' ];
		
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
	
	public function delete_all_logs( $post_id ) {
		global $wpdb;
		
		$logs_table = $wpdb->prefix . 'reports_logs_' . strtolower( $this->use_project );
		
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
		
		if ( $post_type === 'report' ) {
			$logs_table = $wpdb->prefix . 'reports_logs_' . strtolower( $this->use_project );
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
		$restricted_roles = [ 'tracking', 'dispatcher', 'recruiter' ];
		
		// SQL-запрос с фильтрацией по роли, посту и сортировкой по дате
		$query = $wpdb->prepare( "
        SELECT id, id_user, user_name, user_role, log_priority, log_date, log_text
        FROM $logs_table
        WHERE id_load = %d
          AND (user_role != 'billing' OR %s NOT IN ('tracking', 'dispatcher', 'recruiter'))
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
		
		foreach ( $tables as $val ) {
			$log_table_name  = $wpdb->prefix . 'reports_logs_' . strtolower( $val );
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $log_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            id_load mediumint(9) NOT NULL, -- ID загрузки
            id_user mediumint(9) NOT NULL, -- ID пользователя, который добавил запись
            user_name varchar(255) NOT NULL, -- Имя пользователя
            user_role varchar(100) NOT NULL, -- Роль пользователя
            log_priority smallint(5) NOT NULL DEFAULT 0, -- Приоритет записи, по умолчанию 0
            log_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Дата и время добавления записи
            log_text longtext NOT NULL, -- Текст лога (включая HTML)
            PRIMARY KEY (id),
            INDEX idx_id_load (id_load),
            INDEX idx_id_user (id_user),
            INDEX idx_log_priority (log_priority),
            INDEX idx_log_date (log_date)
        ) $charset_collate;";
			
			dbDelta( $sql );
		}
		
		$log_table_name  = $wpdb->prefix . 'drivers_logs';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $log_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            id_load mediumint(9) NOT NULL, -- ID загрузки
            id_user mediumint(9) NOT NULL, -- ID пользователя, который добавил запись
            user_name varchar(255) NOT NULL, -- Имя пользователя
            user_role varchar(100) NOT NULL, -- Роль пользователя
            log_priority smallint(5) NOT NULL DEFAULT 0, -- Приоритет записи, по умолчанию 0
            log_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Дата и время добавления записи
            log_text longtext NOT NULL, -- Текст лога (включая HTML)
            PRIMARY KEY (id),
            INDEX idx_id_load (id_load),
            INDEX idx_id_user (id_user),
            INDEX idx_log_priority (log_priority),
            INDEX idx_log_date (log_date)
        ) $charset_collate;";
		
		dbDelta( $sql );
	}
}