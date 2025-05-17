<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSContacts extends TMSDriversHelper {
	
	public $table_main         = 'contacts';
	public $additional_contact = 'contacts_additional_info';
	public $helper             = false;
	public $per_page_loads     = 1;
	
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
			global $wpdb;
			
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"         => FILTER_SANITIZE_NUMBER_INT,
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
			
			$table_main       = $wpdb->prefix . $this->table_main;
			$table_additional = $wpdb->prefix . $this->additional_contact;
			
			$user_id = get_current_user_id();
			
			// Вставка в основную таблицу
			$wpdb->insert( $table_main, [
				'user_id_added'   => $user_id,
				'company_id'      => (int) $MY_INPUT[ 'customer_id' ],
				'name'            => $MY_INPUT[ 'name' ],
				'office_number'   => $MY_INPUT[ 'office_number' ],
				'direct_number'   => $MY_INPUT[ 'direct_number' ],
				'email'           => $MY_INPUT[ 'email' ],
				'support_contact' => $MY_INPUT[ 'support_contact' ],
				'support_phone'   => $MY_INPUT[ 'support_phone' ],
				'support_email'   => $MY_INPUT[ 'support_email' ],
			], [
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			] );
			
			$contact_id = $wpdb->insert_id;
			
			// Вставка дополнительных контактов
			if ( ! empty( $MY_INPUT[ 'additional_contacts' ] ) && is_array( $MY_INPUT[ 'additional_contacts' ] ) ) {
				foreach ( $MY_INPUT[ 'additional_contacts' ] as $contact ) {
					if ( empty( $contact[ 'name' ] ) && empty( $contact[ 'phone' ] ) && empty( $contact[ 'email' ] ) ) {
						continue;
					}
					
					$wpdb->insert( $table_additional, [
						'contact_id'    => $contact_id,
						'contact_name'  => sanitize_text_field( $contact[ 'name' ] ),
						'contact_phone' => sanitize_text_field( $contact[ 'phone' ] ),
						'contact_email' => sanitize_email( $contact[ 'email' ] ),
					], [
						'%d',
						'%s',
						'%s',
						'%s'
					] );
				}
			}
			
			wp_send_json_success( [ 'message' => 'Contact added', 'contact_id' => $contact_id ] );
		}
	}
	
	
	public function get_contact_by_id( $id ) {
		global $wpdb;
		
		$table_main       = $wpdb->prefix . $this->table_main;
		$table_additional = $wpdb->prefix . $this->additional_contact;
		
		$contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_main} WHERE id = %d", $id ), ARRAY_A );
		
		if ( ! $contact ) {
			return null;
		}
		
		$contact[ 'additional_contacts' ] = $wpdb->get_results( $wpdb->prepare( "SELECT contact_name, contact_phone, contact_email FROM {$table_additional} WHERE contact_id = %d", $id ), ARRAY_A );
		
		return $contact;
	}
	
	public function get_all_contacts( $args = [] ) {
		global $wpdb;
		
		$table_main       = $wpdb->prefix . $this->table_main;
		$table_additional = $wpdb->prefix . $this->additional_contact;
		$table_companies  = $wpdb->prefix . 'reports_company'; // таблица компаний
		
		$current_user_id = get_current_user_id();
		
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? (int) $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? esc_sql( $args[ 'sort_by' ] ) : 'date_created';
		
		// Подсчёт общего количества
		$total_records = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_main WHERE user_id_added = %d", $current_user_id ) );
		
		$total_pages = (int) ceil( $total_records / $per_page );
		
		// Получение основной информации с JOIN на компании
		$main_contacts = $wpdb->get_results( $wpdb->prepare( "SELECT m.*, c.*
		 FROM $table_main m
		 LEFT JOIN $table_companies c ON m.company_id = c.id
		 WHERE m.user_id_added = %d
		 ORDER BY m.$sort_by DESC
		 LIMIT %d OFFSET %d", $current_user_id, $per_page, $offset ), ARRAY_A );
		
		// Присоединяем дополнительные контакты
		foreach ( $main_contacts as &$contact ) {
			$contact_id                       = (int) $contact[ 'id' ];
			$contact[ 'additional_contacts' ] = $wpdb->get_results( $wpdb->prepare( "SELECT contact_name, contact_phone, contact_email
			 FROM $table_additional
			 WHERE contact_id = %d", $contact_id ), ARRAY_A );
		}
		
		return [
			'data'       => $main_contacts,
			'pagination' => [
				'total_pages'   => $total_pages,
				'total_records' => $total_records,
				'current_page'  => $current_page,
				'per_page'      => $per_page,
			],
			'sort_by'    => $sort_by,
		];
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