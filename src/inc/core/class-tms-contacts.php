<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSContacts extends TMSDriversHelper {
	
	public $table_main         = 'contacts';
	public $additional_contact = 'contacts_additional_info';
	public $helper             = false;
	public $per_page_loads     = 25;
	
	public function __construct() {
		$this->helper = new TMSCommonHelper();
	}
	
	public function init() {
		// Run table creation/update only for admins to avoid unnecessary DB load for all users.
		if ( current_user_can( 'administrator' ) ) {
			$this->table_contacts_init();
			$this->table_contacts_additional_init();
		}
		$this->ajax_actions();
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_new_contact'    => 'add_new_contact',
			'edit_contact'       => 'edit_contact',
			'search_contact'     => 'search_contact',
			'delete_one_contact' => 'delete_one_contact',
			'optimize_contacts_tables' => 'optimize_contacts_tables',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this->helper, 'need_login' ] );
		}
	}
	
	public function delete_one_contact() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			$table_main       = $wpdb->prefix . $this->table_main;
			$table_additional = $wpdb->prefix . $this->additional_contact;
			
			$MY_INPUT = filter_var_array( $_POST, [
				'id' => FILTER_SANITIZE_NUMBER_INT
			] );
			
			$contact_id = (int) $MY_INPUT[ 'id' ];
			
			if ( $contact_id > 0 ) {
				// Удаляем дополнительные контакты
				$wpdb->delete( $table_additional, [ 'contact_id' => $contact_id ], [ '%d' ] );
				
				// Удаляем основной контакт
				$wpdb->delete( $table_main, [ 'id' => $contact_id ], [ '%d' ] );
				
				wp_send_json_success( [ 'message' => 'Contact successfully deleted.' ] );
			} else {
				wp_send_json_error( [ 'message' => 'Invalid ID .' ] );
			}
		}
	}
	
	public function search_contact() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			$table_main       = $wpdb->prefix . $this->table_main;
			$table_additional = $wpdb->prefix . $this->additional_contact;
			$table_companies  = $wpdb->prefix . 'reports_company';
			
			$current_user_id = get_current_user_id();
			
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				"search" => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) )
			];
			
			if ( empty( $MY_INPUT[ 'search' ] ) ) {
				wp_send_json_success( '' );
			}
			
			$search = '%' . $wpdb->esc_like( trim( $MY_INPUT[ 'search' ] ) ) . '%';
			
			// Get IDs only for current user's contacts
			$main_contact_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT m.id
				FROM $table_main m
				WHERE m.user_id_added = %d
				AND (m.name LIKE %s OR m.email LIKE %s)
			", $current_user_id, $search, $search ) );
			
			
			// Получаем ID из дополнительных контактов
			$additional_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT a.contact_id
				FROM $table_additional a
				INNER JOIN $table_main m ON a.contact_id = m.id
				WHERE m.user_id_added = %d AND (a.contact_name LIKE %s OR a.contact_email LIKE %s)
			", $current_user_id, $search, $search ) );
			
			// Объединяем ID
			$all_ids = array_unique( array_merge( $main_contact_ids, $additional_ids ) );
			
			if ( empty( $all_ids ) ) {
				$template = '<p class="text-small text-danger mt-1">Not found</p>';
				wp_send_json_success( $template );
			}
			
			// Получаем основную информацию
			$placeholders  = implode( ',', array_fill( 0, count( $all_ids ), '%d' ) );
			$sql           = "
			SELECT
				m.*,
				m.email AS direct_email,
				m.id AS main_id,
				c.id AS company_id_alias,
				c.email AS company_email,
				c.*
			FROM $table_main m
			LEFT JOIN $table_companies c ON m.company_id = c.id
			WHERE m.id IN ($placeholders)
		";
			$query         = $wpdb->prepare( $sql, ...$all_ids );
			$main_contacts = $wpdb->get_results( $query, ARRAY_A );
			
			$template = '';
			
			// Добавляем дополнительные контакты
			foreach ( $main_contacts as &$contact ) {
				$contact_id                       = (int) $contact[ 'main_id' ];
				$contact[ 'additional_contacts' ] = $wpdb->get_results( $wpdb->prepare( "
				SELECT contact_name, contact_phone, contact_email, contact_ext
				FROM $table_additional
				WHERE contact_id = %d
			", $contact_id ), ARRAY_A );
				
				$template .= '<div class="result-search-contact__item js-preset-click">
					<p>' . $contact[ 'name' ] . ' - (' . $contact[ 'company_name' ] . ' | ' . $contact[ 'direct_email' ] . ')</p>
					<input type="hidden" value="' . $contact[ 'main_id' ] . '" name="main_contact_id" />
					<div class="d-none js-preset-json">
						' . json_encode( $contact ) . '
					</div>
				</div>';
			}
			
			wp_send_json_success( $template );
		}
	}
	
	public function add_new_contact() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				"customer_id"         => filter_var( $_POST['customer_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT ),
				'name'                => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'office_number'       => sanitize_text_field( wp_unslash( $_POST['office_number'] ?? '' ) ),
				'direct_number'       => sanitize_text_field( wp_unslash( $_POST['direct_number'] ?? '' ) ),
				'direct_ext'          => sanitize_text_field( wp_unslash( $_POST['direct_ext'] ?? '' ) ),
				'email'               => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				'support_contact'     => sanitize_text_field( wp_unslash( $_POST['support_contact'] ?? '' ) ),
				'support_phone'       => sanitize_text_field( wp_unslash( $_POST['support_phone'] ?? '' ) ),
				'support_ext'         => sanitize_text_field( wp_unslash( $_POST['support_ext'] ?? '' ) ),
				'support_email'       => sanitize_email( wp_unslash( $_POST['support_email'] ?? '' ) ),
				'additional_contacts' => $_POST['additional_contacts'] ?? []
			];
			
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
				'direct_ext'      => $MY_INPUT[ 'direct_ext' ],
				'email'           => $MY_INPUT[ 'email' ],
				'support_contact' => $MY_INPUT[ 'support_contact' ],
				'support_phone'   => $MY_INPUT[ 'support_phone' ],
				'support_ext'     => $MY_INPUT[ 'support_ext' ],
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
						'contact_name'  => sanitize_text_field( wp_unslash( $contact[ 'name' ] ) ),
						'contact_phone' => sanitize_text_field( wp_unslash( $contact[ 'phone' ] ) ),
						'contact_ext'   => sanitize_text_field( wp_unslash( $contact[ 'ext' ] ) ),
						'contact_email' => sanitize_email( wp_unslash( $contact[ 'email' ] ) ),
					], [
						'%d',
						'%s',
						'%s',
						'%s',
						'%s'
					] );
				}
			}
			
			wp_send_json_success( [ 'message' => 'Contact added', 'contact_id' => $contact_id ] );
		}
	}
	
	public function edit_contact() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				'main_id'             => filter_var( $_POST['main_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT ),
				"customer_id"         => filter_var( $_POST['customer_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT ),
				'name'                => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'office_number'       => sanitize_text_field( wp_unslash( $_POST['office_number'] ?? '' ) ),
				'direct_number'       => sanitize_text_field( wp_unslash( $_POST['direct_number'] ?? '' ) ),
				'direct_ext'          => sanitize_text_field( wp_unslash( $_POST['direct_ext'] ?? '' ) ),
				'email'               => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				'support_contact'     => sanitize_text_field( wp_unslash( $_POST['support_contact'] ?? '' ) ),
				'support_phone'       => sanitize_text_field( wp_unslash( $_POST['support_phone'] ?? '' ) ),
				'support_ext'         => sanitize_text_field( wp_unslash( $_POST['support_ext'] ?? '' ) ),
				'support_email'       => sanitize_email( wp_unslash( $_POST['support_email'] ?? '' ) ),
				'additional_contacts' => $_POST['additional_contacts'] ?? []
			];
			
			
			$table_main       = $wpdb->prefix . $this->table_main;
			$table_additional = $wpdb->prefix . $this->additional_contact;
			$user_id          = get_current_user_id();
			$main_id          = (int) $MY_INPUT[ 'main_id' ];
			
			$data = [
				'user_id_added'   => $user_id,
				'company_id'      => (int) $MY_INPUT[ 'customer_id' ],
				'name'            => $MY_INPUT[ 'name' ],
				'office_number'   => $MY_INPUT[ 'office_number' ],
				'direct_number'   => $MY_INPUT[ 'direct_number' ],
				'direct_ext'      => $MY_INPUT[ 'direct_ext' ],
				'email'           => $MY_INPUT[ 'email' ],
				'support_contact' => $MY_INPUT[ 'support_contact' ],
				'support_phone'   => $MY_INPUT[ 'support_phone' ],
				'support_ext'     => $MY_INPUT[ 'support_ext' ],
				'support_email'   => $MY_INPUT[ 'support_email' ],
			];
			
			$format = [ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ];
			
			if ( $main_id > 0 ) {
				// UPDATE
				$wpdb->update( $table_main, $data, [ 'id' => $main_id ], $format, [ '%d' ] );
				$contact_id = $main_id;
				
				// Удаляем старые доп. контакты
				$wpdb->delete( $table_additional, [ 'contact_id' => $contact_id ], [ '%d' ] );
			} else {
				// INSERT
				$wpdb->insert( $table_main, $data, $format );
				$contact_id = $wpdb->insert_id;
			}
			
			// Вставка дополнительных контактов
			if ( ! empty( $MY_INPUT[ 'additional_contacts' ] ) && is_array( $MY_INPUT[ 'additional_contacts' ] ) ) {
				foreach ( $MY_INPUT[ 'additional_contacts' ] as $contact ) {
					if ( empty( $contact[ 'name' ] ) && empty( $contact[ 'phone' ] ) && empty( $contact[ 'email' ] ) ) {
						continue;
					}
					
					$wpdb->insert( $table_additional, [
						'contact_id'    => $contact_id,
						'contact_name'  => sanitize_text_field( wp_unslash( $contact[ 'name' ] ) ),
						'contact_phone' => sanitize_text_field( wp_unslash( $contact[ 'phone' ] ) ),
						'contact_ext'   => sanitize_text_field( wp_unslash( $contact[ 'ext' ] ) ),
						'contact_email' => sanitize_email( wp_unslash( $contact[ 'email' ] ) ),
					], [ '%d', '%s', '%s', '%s', '%s' ] );
				}
			}
			
			wp_send_json_success( [ 'message' => 'Contact saved', 'contact_id' => $contact_id ] );
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
		
		if ( isset( $args[ 'dispatcher' ] ) && ! empty( $args[ 'dispatcher' ] ) ) {
			$current_user_id = $args[ 'dispatcher' ];
		}
		
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? (int) $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? esc_sql( $args[ 'sort_by' ] ) : 'date_created';
		$month        = isset( $args[ 'month' ] ) ? (int) $args[ 'month' ] : null;
		$year         = isset( $args[ 'year' ] ) ? (int) $args[ 'year' ] : null;

		// Подсчёт общего количества
		$total_records = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_main WHERE user_id_added = %d", $current_user_id ) );
		
		$total_pages = (int) ceil( $total_records / $per_page );
		
		// Получение основной информации с JOIN на компании
		$main_contacts = $wpdb->get_results( $wpdb->prepare( "
		    SELECT
		        m.*,
		        m.email AS direct_email,
		        m.id AS main_id,
		        c.id AS company_id_alias,
		        c.email AS company_email,
		        c.*
		    FROM $table_main m
		    LEFT JOIN $table_companies c ON m.company_id = c.id
		    WHERE m.user_id_added = %d
		    ORDER BY m.$sort_by DESC
		    LIMIT %d OFFSET %d
		", $current_user_id, $per_page, $offset ), ARRAY_A );
		
		// Присоединяем дополнительные контакты
		foreach ( $main_contacts as &$contact ) {
			$contact_id                       = (int) $contact[ 'main_id' ];
			$contact[ 'additional_contacts' ] = $wpdb->get_results( $wpdb->prepare( "SELECT contact_name, contact_phone, contact_email, contact_ext
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
		$additional_table = $wpdb->prefix . $this->additional_contact;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $additional_table ) ) !== $additional_table ) {
			$charset_collate  = $wpdb->get_charset_collate();
			$sql_additional   = "CREATE TABLE $additional_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			contact_id mediumint(9) NOT NULL,
			contact_name varchar(255) DEFAULT '',
			contact_phone varchar(50) DEFAULT '',
			contact_ext varchar(50) DEFAULT '',
			contact_email varchar(255) DEFAULT '',
			PRIMARY KEY  (id),
			KEY idx_contact_id (contact_id)
		) $charset_collate;";
			dbDelta( $sql_additional );
		}
	}
	
	public function table_contacts_init() {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->table_main;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id_added mediumint(9) NOT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			company_id mediumint(9) NOT NULL,
			name varchar(255) NOT NULL,
			office_number varchar(50) DEFAULT '',
			direct_number varchar(50) DEFAULT '',
			direct_ext varchar(50) DEFAULT '',
			email varchar(255) NOT NULL,
			support_contact varchar(255) DEFAULT '',
			support_phone varchar(50) DEFAULT '',
			support_ext varchar(50) DEFAULT '',
			support_email varchar(255) DEFAULT '',
			PRIMARY KEY  (id),
			KEY idx_date_created (date_created),
			KEY idx_company_id (company_id),
			KEY idx_email (email)
		) $charset_collate;";
			dbDelta( $sql );
		}
	}
	
	/**
	 * Optimize contacts tables for better performance with large datasets
	 */
	public function optimize_contacts_tables() {
		// Check nonce for security
		if ( ! wp_verify_nonce( $_POST['tms_optimize_nonce'], 'tms_optimize_database' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		$optimization_type = sanitize_text_field( $_POST['optimization_type'] ?? 'indexes' );
		$results = [];
		
		try {
			if ( $optimization_type === 'full' ) {
				$results = $this->perform_full_contacts_optimization();
			} else {
				$results = $this->perform_fast_contacts_optimization();
			}
			
			wp_send_json_success( $results );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
	
	/**
	 * Perform fast optimization (indexes only)
	 */
	public function perform_fast_contacts_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main contacts table
		$main_table = $wpdb->prefix . $this->table_main;
		$results['main_table'] = $this->optimize_contacts_main_table_fast( $main_table );
		
		// Optimize additional contacts table
		$additional_table = $wpdb->prefix . $this->additional_contact;
		$results['additional_table'] = $this->optimize_contacts_additional_table_fast( $additional_table );
		
		return $results;
	}
	
	/**
	 * Perform full optimization (structural changes)
	 */
	public function perform_full_contacts_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main contacts table
		$main_table = $wpdb->prefix . $this->table_main;
		$results['main_table'] = $this->optimize_contacts_main_table_full( $main_table );
		
		// Optimize additional contacts table
		$additional_table = $wpdb->prefix . $this->additional_contact;
		$results['additional_table'] = $this->optimize_contacts_additional_table_full( $additional_table );
		
		return $results;
	}
	
	/**
	 * Fast optimization for main contacts table
	 */
	private function optimize_contacts_main_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for better query performance
		$indexes = [
			'idx_user_company' => 'user_id_added, company_id',
			'idx_user_date' => 'user_id_added, date_created',
			'idx_company_email' => 'company_id, email',
			'idx_user_email' => 'user_id_added, email',
		];
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		// Optimize table
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Full optimization for main contacts table
	 */
	private function optimize_contacts_main_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY user_id_added BIGINT UNSIGNED NOT NULL",
			"ALTER TABLE $table_name MODIFY company_id BIGINT UNSIGNED NOT NULL",
			"ALTER TABLE $table_name MODIFY date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_user_company' => 'user_id_added, company_id',
			'idx_user_date' => 'user_id_added, date_created',
			'idx_company_email' => 'company_id, email',
			'idx_user_email' => 'user_id_added, email',
			'idx_user_company_date' => 'user_id_added, company_id, date_created',
		];
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		// Optimize table
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Fast optimization for additional contacts table
	 */
	private function optimize_contacts_additional_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for additional contacts queries
		$indexes = [
			'idx_contact_email' => 'contact_id, contact_email',
			'idx_contact_name' => 'contact_id, contact_name',
			'idx_contact_phone' => 'contact_id, contact_phone',
		];
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		// Optimize table
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Full optimization for additional contacts table
	 */
	private function optimize_contacts_additional_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY contact_id BIGINT UNSIGNED NOT NULL",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_contact_email' => 'contact_id, contact_email',
			'idx_contact_name' => 'contact_id, contact_name',
			'idx_contact_phone' => 'contact_id, contact_phone',
			'idx_contact_email_name' => 'contact_id, contact_email, contact_name',
		];
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		// Optimize table
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
}