<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSContacts extends TMSDriversHelper {
	
	public $table_main         = 'contacts';
	public $additional_contact = 'contacts_additional_info';
	public $helper             = false;
	
	public function __construct() {
		$this->helper = new TMSCommonHelper();
	}
	
	public function init() {
		$this->table_contacts_init();
		$this->table_contacts_additional_init();
		$this->ajax_actions();
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_new_contact' => 'add_new_contact',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this->helper, 'need_login' ] );
		}
	}
	
	public function add_new_contact() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				'company_id'          => FILTER_SANITIZE_NUMBER_INT,
				'name'                => FILTER_SANITIZE_STRING,
				'office_number'       => FILTER_SANITIZE_STRING,
				'direct_number'       => FILTER_SANITIZE_STRING,
				'email'               => FILTER_SANITIZE_EMAIL,
				'support_contact'     => FILTER_SANITIZE_STRING,
				'support_phone'       => FILTER_SANITIZE_STRING,
				'support_email'       => FILTER_SANITIZE_EMAIL,
				'additional_contacts' => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY
				]
			] );
			
			var_dump( $MY_INPUT );
		}
	}
	
	public function table_contacts_additional_init() {
		global $wpdb;
		$charset_collate  = $wpdb->get_charset_collate();
		$additional_table = $wpdb->prefix . $this->additional_contact;
		
		$sql_additional = "CREATE TABLE $additional_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		contact_id mediumint(9) NOT NULL,
		contact_name varchar(255) DEFAULT '',
		contact_phone varchar(50) DEFAULT '',
		contact_email varchar(255) DEFAULT '',
		PRIMARY KEY  (id),
		KEY idx_contact_id (contact_id)
	) $charset_collate;";
		
		dbDelta( $sql_additional );
	}
	
	public function table_contacts_init() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id_added mediumint(9) NOT NULL,
		date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		company_id mediumint(9) NOT NULL,
		name varchar(255) NOT NULL,
		office_number varchar(50) DEFAULT '',
		direct_number varchar(50) DEFAULT '',
		email varchar(255) NOT NULL,
		support_contact varchar(255) DEFAULT '',
		support_phone varchar(50) DEFAULT '',
		support_email varchar(255) DEFAULT '',
		PRIMARY KEY  (id),
		KEY idx_date_created (date_created),
		KEY idx_company_id (company_id),
		KEY idx_email (email)
	) $charset_collate;";
		
		dbDelta( $sql );
		
		
	}
	
}