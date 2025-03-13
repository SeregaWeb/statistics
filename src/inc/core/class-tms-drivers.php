<?php

class TMSDrivers extends TMSDriversHelper {
	
	public $table_main     = 'drivers';
	public $table_meta     = 'drivers_meta';
	public $per_page_loads = 100;
	public $hold_time      = 15;
	
	public function init() {
		$this->ajax_actions();
		$this->create_tables();
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_driver'             => 'add_driver',
			'upload_driver_vehicle'  => 'upload_driver_vehicle',
			'upload_driver_contact'  => 'upload_driver_document',
			'upload_driver_document' => 'upload_driver_document',
			'update_driver_info'     => 'update_driver_info',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this, 'need_login' ] );
		}
	}
	
	public function get_driver_by_id() {
		return false;
	}
	
	public function add_driver() { }
	
	public function upload_driver_document() {
		
	}
	
	public function update_driver_info() {
	
	}
	
	public function need_login() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			wp_send_json_error( [ 'message' => 'You need to log in to perform this action.' ] );
		}
	}
	
	public function create_tables() {
		$this->table_driver();
		$this->table_driver_meta();
	}
	
	public function table_driver() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			    id mediumint(9) NOT NULL AUTO_INCREMENT,
			    user_id_added mediumint(9) NOT NULL,
			    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    user_id_updated mediumint(9) NULL,
			    date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    clean_check_date datetime NULL DEFAULT NULL,
			    updated_zipcode datetime NULL DEFAULT NULL,
			    date_available datetime NULL DEFAULT NULL,
			    checked_from_brokersnapshot datetime NULL DEFAULT NULL,
			    status_post varchar(50) NULL DEFAULT NULL,
			    PRIMARY KEY (id),
			    INDEX idx_date_created (date_created),
			    INDEX idx_clean_check_date (clean_check_date),
			    INDEX idx_checked_from_brokersnapshot (checked_from_brokersnapshot),
			    INDEX idx_date_available (date_available)
			) $charset_collate;";
		
		dbDelta( $sql );
	}
	
	public function table_driver_meta() {
		global $wpdb;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		$charset_collate = $wpdb->get_charset_collate();
		
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