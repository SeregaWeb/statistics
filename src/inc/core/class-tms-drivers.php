<?php

class TMSDrivers extends TMSDriversHelper {
	
	public $table_main     = 'drivers';
	public $table_meta     = 'drivers_meta';
	public $per_page_loads = 100;
	public $hold_time      = 15;
	
	public function init() {
		$this->ajax_actions();
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_driver'             => 'add_driver',
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
	
	public function add_driver() {
	
	}
	
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
	
	}
	
	public function table_driver_meta() { }
}