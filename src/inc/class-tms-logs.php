<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSLogs extends TMSReports {
	public function __construct() {
	}
	public function init() {
		$this->create_log_tables();
	}
	public function create_log_tables() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		foreach ($tables as $val) {
			$log_table_name = $wpdb->prefix . 'reports_logs_' . strtolower($val);
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
			
			dbDelta($sql);
		}
	}
}