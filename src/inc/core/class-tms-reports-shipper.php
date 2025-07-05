<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsShipper extends TMSReportsHelper {
	public $table_main     = 'reports_shipper';
	public $posts_per_page = 25;
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_shipper', array( $this, 'add_new_shipper' ) );
		add_action( 'wp_ajax_update_shipper', array( $this, 'update_shipper' ) );
		add_action( 'wp_ajax_search_shipper', array( $this, 'search_shipper' ) );
		add_action( 'wp_ajax_delete_shipper', array( $this, 'delete_shipper' ) );
		add_action( 'wp_ajax_optimize_shipper_tables', array( $this, 'optimize_shipper_tables' ) );
	}
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table_shipper' ) );

//		add_action( 'after_setup_theme', array( $this, 'update_table_shipper_with_indexes' ) );
		
		$this->ajax_actions();
	}
	
	public function delete_shipper() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"id" => FILTER_SANITIZE_NUMBER_INT
			] );
			
			if ( empty( $MY_INPUT[ 'id' ] ) || ! is_numeric( $MY_INPUT[ 'id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Shipper not deleted, id not found' ] );
			}
			
			$delete = $this->delete_shipper_by_id( $MY_INPUT[ 'id' ] );
			
			if ( $delete[ 'status' ] ) {
				wp_send_json_success( $delete );
			}
			
			wp_send_json_error( $delete );
		}
	}
	
	public function delete_shipper_by_id( $post_id ) {
		
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main; // Основная таблица
		
		// Удаляем сам пост
		$post_deleted = $wpdb->delete( $table_main, [ 'id' => $post_id ], [ '%d' ] );
		
		// Проверяем удаление поста
		if ( $post_deleted === false ) {
			return array( 'status' => false, 'message' => 'Error deleting post: ' . $wpdb->last_error );
		}
		
		// Проверяем, были ли найдены записи для удаления
		if ( $post_deleted === 0 ) {
			echo 'Записи для удаления не найдены.';
			
			return array( 'status' => false, 'message' => 'No records found for deletion.' );
		} else {
			return array( 'status' => true, 'message' => 'Successfully deleted post and its meta data.' );
		}
	}
	
	public function update_table_shipper_with_indexes() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		// Добавляем индексы для полей, по которым выполняются поиски
		$wpdb->query( "
        ALTER TABLE $table_name
        ADD INDEX idx_full_address (full_address),
        ADD INDEX idx_shipper_name (shipper_name),
        ADD INDEX idx_email (email),
        ADD INDEX idx_phone_number (phone_number);
    " );
	}
	
	public function get_table_records() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_main;
		
		$current_page = isset( $_GET[ 'paged' ] ) ? (int) $_GET[ 'paged' ] : 1;
		
		$search = isset( $_GET[ 'my_search' ] ) ? sanitize_text_field( $_GET[ 'my_search' ] ) : '';
		
		$offset = ( $current_page - 1 ) * $this->posts_per_page;
		
		$count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
		
		$main_query = "SELECT * FROM $table_name WHERE 1=1";
		
		if ( ! empty( $search ) ) {
			$search           = '%' . $wpdb->esc_like( $search ) . '%';
			$search_condition = " AND (
            shipper_name LIKE %s OR
            zip_code LIKE %s OR
            phone_number LIKE %s OR
            full_address LIKE %s OR
            email LIKE %s
        )";
			$count_query      .= $search_condition;
			$main_query       .= $search_condition;
		}
		
		$main_query .= " LIMIT %d OFFSET %d";
		
		$params = [];
		if ( ! empty( $search ) ) {
			$params = array_merge( $params, [ $search, $search, $search, $search, $search ] );
		}
		if ( ! empty( $platform ) ) {
			$params[] = $platform;
		}
		
		$total_records = (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$params ) );
		$main_results  = $wpdb->get_results( $wpdb->prepare( $main_query, array_merge( $params, [
			$this->posts_per_page,
			$offset
		] ) ), ARRAY_A );
		
		$total_pages = ceil( $total_records / $this->posts_per_page );
		
		return array(
			'results'      => $main_results,
			'total_pages'  => $total_pages,
			'total_posts'  => $total_records,
			'current_page' => $current_page,
		);
	}
	
	
	public function add_new_shipper() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"shipper_name" => FILTER_SANITIZE_STRING,
				"country"      => FILTER_SANITIZE_STRING,
				"Addr1"        => FILTER_SANITIZE_STRING,
				"Addr2"        => FILTER_SANITIZE_STRING,
				"City"         => FILTER_SANITIZE_STRING,
				"State"        => FILTER_SANITIZE_STRING,
				"ZipCode"      => FILTER_SANITIZE_STRING,
				"FirstName"    => FILTER_SANITIZE_STRING,
				"LastName"     => FILTER_SANITIZE_STRING,
				"Phone"        => FILTER_SANITIZE_STRING,
				"Email"        => FILTER_SANITIZE_EMAIL,
			] );
			
			$st      = ! empty( $MY_INPUT[ 'Addr1' ] ) ? $MY_INPUT[ 'Addr1' ] . ', ' : '';
			$city    = ! empty( $MY_INPUT[ 'City' ] ) ? $MY_INPUT[ 'City' ] . ', ' : '';
			$state   = ! empty( $MY_INPUT[ 'State' ] ) ? $MY_INPUT[ 'State' ] . ' ' : '';
			$zip     = ! empty( $MY_INPUT[ 'ZipCode' ] ) ? $MY_INPUT[ 'ZipCode' ] : ' ';
			$country = $MY_INPUT[ 'country' ] !== 'USA' ? ' ' . $MY_INPUT[ 'country' ] : '';
			
			$MY_INPUT[ "full_address" ] = $st . $city . $state . $zip . $country;
			
			// Insert the company report
			$result = $this->add_shipper( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				
				$short_address = $city . ' ' . $state;
				$name          = $MY_INPUT[ "shipper_name" ];
				$templ         = $this->print_list_shipper( $MY_INPUT[ "full_address" ], $result, $short_address, $name );
				
				wp_send_json_success( [ 'message' => 'shipper successfully added', 'tmpl' => $templ ] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'shipper not created, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function update_shipper() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"shipper_id"   => FILTER_SANITIZE_STRING,
				"shipper_name" => FILTER_SANITIZE_STRING,
				"country"      => FILTER_SANITIZE_STRING,
				"Addr1"        => FILTER_SANITIZE_STRING,
				"Addr2"        => FILTER_SANITIZE_STRING,
				"City"         => FILTER_SANITIZE_STRING,
				"State"        => FILTER_SANITIZE_STRING,
				"ZipCode"      => FILTER_SANITIZE_STRING,
				"FirstName"    => FILTER_SANITIZE_STRING,
				"LastName"     => FILTER_SANITIZE_STRING,
				"Phone"        => FILTER_SANITIZE_STRING,
				"Email"        => FILTER_SANITIZE_EMAIL,
			] );
			
			$st      = ! empty( $MY_INPUT[ 'Addr1' ] ) ? $MY_INPUT[ 'Addr1' ] . ', ' : '';
			$city    = ! empty( $MY_INPUT[ 'City' ] ) ? $MY_INPUT[ 'City' ] . ', ' : '';
			$state   = ! empty( $MY_INPUT[ 'State' ] ) ? $MY_INPUT[ 'State' ] . ' ' : '';
			$zip     = ! empty( $MY_INPUT[ 'ZipCode' ] ) ? $MY_INPUT[ 'ZipCode' ] : ' ';
			$country = $MY_INPUT[ 'country' ] !== 'USA' ? ' ' . $MY_INPUT[ 'country' ] : '';
			
			$MY_INPUT[ "full_address" ] = $st . $city . $state . $zip . $country;
			
			// Insert the company report
			$result = $this->update_shipper_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'shipper successfully updated', 'data' => $MY_INPUT ] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'shipper not created, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function add_shipper( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$insert_params = array(
			'shipper_name'       => $data[ 'shipper_name' ],
			'country'            => $data[ 'country' ],
			'address1'           => $data[ 'Addr1' ],
			'address2'           => $data[ 'Addr2' ],
			'city'               => $data[ 'City' ],
			'state'              => $data[ 'State' ],
			'zip_code'           => $data[ 'ZipCode' ],
			'contact_first_name' => $data[ 'FirstName' ],
			'contact_last_name'  => $data[ 'LastName' ],
			'phone_number'       => $data[ 'Phone' ],
			'email'              => $data[ 'Email' ],
			'user_id_added'      => $user_id,
			'date_created'       => current_time( 'mysql' ),
			'user_id_updated'    => $user_id,
			'date_updated'       => current_time( 'mysql' ),
			'full_address'       => $data[ 'full_address' ],
		);
		
		$result = $wpdb->insert( $table_name, $insert_params, array(
			'%s',  // company_name
			'%s',  // country
			'%s',  // address1
			'%s',  // address2
			'%s',  // city
			'%s',  // state
			'%s',  // zip_code
			'%s',  // contact_first_name
			'%s',  // contact_last_name
			'%s',  // phone_number
			'%s',  // email
			'%d',  // user_id_added
			'%s',  // date_created
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // full_address
		) );
		
		// Check if the insert was successful
		if ( $result ) {
			return $wpdb->insert_id; // Return the ID of the added record
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Check for specific unique constraint violations
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'shipper_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A shipper with this name already exists.' );
				}
			}
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error adding the shipper report to the database: ' . $error );
		}
	}
	
	public function update_shipper_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		$shipper_id = $data[ 'shipper_id' ];
		
		$update_params = array(
			'shipper_name'       => $data[ 'shipper_name' ],
			'country'            => $data[ 'country' ],
			'address1'           => $data[ 'Addr1' ],
			'address2'           => $data[ 'Addr2' ],
			'city'               => $data[ 'City' ],
			'state'              => $data[ 'State' ],
			'zip_code'           => $data[ 'ZipCode' ],
			'contact_first_name' => $data[ 'FirstName' ],
			'contact_last_name'  => $data[ 'LastName' ],
			'phone_number'       => $data[ 'Phone' ],
			'email'              => $data[ 'Email' ],
			'user_id_updated'    => $user_id,
			'date_updated'       => current_time( 'mysql' ),
			'full_address'       => $data[ 'full_address' ],
		);
		
		$where = array(
			'id' => $shipper_id, // Assuming 'id' is the primary key column in your table
		);
		
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%s', // shipper_name
			'%s', // country
			'%s', // address1
			'%s', // address2
			'%s', // city
			'%s', // state
			'%s', // zip_code
			'%s', // contact_first_name
			'%s', // contact_last_name
			'%s', // phone_number
			'%s', // email
			'%d', // user_id_updated
			'%s', // date_updated
			'%s', // full_address
		), array( '%d' ) // Data type for the 'id' column in the WHERE clause
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Return true to indicate a successful update
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Handle specific error cases if needed
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'shipper_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A shipper with this name already exists.' );
				}
			}
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the shipper record in the database: ' . $error );
		}
	}
	
	public function create_table_shipper() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        shipper_name varchar(255) NOT NULL,
        country varchar(255) NOT NULL,
        address1 varchar(255) NOT NULL,
        address2 varchar(255),
        city varchar(100) NOT NULL,
        state varchar(100) NOT NULL,
        zip_code varchar(20) NOT NULL,
        contact_first_name varchar(100) NOT NULL,
        contact_last_name varchar(100),
        phone_number varchar(20),
        email varchar(255),
        full_address varchar(255),
        user_id_added mediumint(9) NOT NULL,
        date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        user_id_updated mediumint(9) NULL,
        date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY shipper_name (shipper_name),
        INDEX idx_full_address (full_address),
        INDEX idx_shipper_name (shipper_name),
        INDEX idx_email (email),
        INDEX idx_phone_number (phone_number)
    ) $charset_collate;";
		
		dbDelta( $sql );
	}
	
	
	public function search_shipper() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"search" => FILTER_SANITIZE_STRING
			] );
			
			$tmp = '';
			
			if ( empty( $MY_INPUT[ 'search' ] ) ) {
				wp_send_json_success( array( 'template' => $tmp ) );
			}
			
			$response = $this->search_shipper_in_db( $MY_INPUT[ 'search' ] );
			if ( is_array( $response ) ) {
				if ( sizeof( $response ) === 0 ) {
					
					$tmp = '
						<li class="my-dropdown-search__item">
	                        <p class="my-dropdown-search__not-found">
		                        Not found
		                        <a class="js-open-popup-activator link-primary"
		                           href="#popup_add_shipper">
		                           click here
		                        </a> to add a new shipper.
	                        </p>
	                    </li>
					';
					
					wp_send_json_success( array( 'template' => $tmp ) );
				}
				
				foreach ( $response as $value ) {
					$address       = $value->full_address;
					$short_address = $value->city . ' ' . $value->state;
					$name          = $value->shipper_name;
					$tmp           .= '
						<li class="my-dropdown-search__item">
	                        <a class="my-dropdown-search__link js-link-search-result" href="#">
	                            <span class="my-dropdown-search__name">
	                            	<span>' . $name . '</span>
	                                <span>' . $address . '</span>
	                            </span>
	                            <div class="d-none">
		                            <div class="js-content-company my-dropdown-search__hidden">
		                                ' . $this->print_list_shipper( $address, $value->id, $short_address, $name ) . '
									</div>
								</div>
	                        </a>
	                    </li>
					';
				}
				
				wp_send_json_success( array( 'template' => $tmp ) );
			}
		}
	}
	
	public function print_list_shipper( $address = '', $id, $short_address, $name = false ) {
		
		if ( ! $id ) {
			return false;
		}
		
		$name_tmpl = '';
		
		if ( $name ) {
			$name_tmpl = '<li><h4>' . $name . '</h4></li>';
		} else {
			$name_tmpl = '<li><h4>Selected shipper</h4></li>';
		}
		
		$template = '
		<ul class="result-search-el">
			' . $name_tmpl . '
            <li class="address">' . $address . '</li>
		</ul>
		<input type="hidden" class="js-full-address" data-short-address="' . $short_address . '" data-current-address="' . $address . '" name="shipper_id" value="' . $id . '">';
		
		return $template;
	}
	
	public function get_shipper_by_id( $ID, $type_return = OBJECT ) {
		static $cache = [];
		
		if ( isset( $cache[ $ID ] ) ) {
			return $cache[ $ID ];
		}
		
		global $wpdb;
		$query = $wpdb->prepare( "
		SELECT * FROM {$wpdb->prefix}{$this->table_main}
		WHERE id = %d
		", $ID );
		
		// Execute the query
		$results = $wpdb->get_results( $query, $type_return );
		
		$cache[ $ID ] = $results;
		
		return $results;
	}
	
	public function search_shipper_in_db( $search_term ) {
		global $wpdb;
		
		// Ensure the search term is sanitized
		$search_term = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		// Define the query to search in the specified fields, ignoring case sensitivity
		$query = $wpdb->prepare( "
        SELECT * FROM {$wpdb->prefix}{$this->table_main}
        WHERE
            LOWER(full_address) LIKE LOWER(%s) OR
            LOWER(shipper_name) LIKE LOWER(%s) OR
            LOWER(phone_number) LIKE LOWER(%s)
        LIMIT 5
    ", $search_term, $search_term, $search_term );
		
		// Execute the query
		$results = $wpdb->get_results( $query );
		
		return $results;
	}
	
	/**
	 * Optimize shipper tables for better performance with large datasets
	 */
	public function optimize_shipper_tables() {
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
				$results = $this->perform_full_shipper_optimization();
			} else {
				$results = $this->perform_fast_shipper_optimization();
			}
			
			wp_send_json_success( $results );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
	
	/**
	 * Perform fast optimization (indexes only)
	 */
	public function perform_fast_shipper_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main shipper table
		$main_table = $wpdb->prefix . $this->table_main;
		$results['main_table'] = $this->optimize_shipper_main_table_fast( $main_table );
		
		return $results;
	}
	
	/**
	 * Perform full optimization (structural changes)
	 */
	public function perform_full_shipper_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main shipper table
		$main_table = $wpdb->prefix . $this->table_main;
		$results['main_table'] = $this->optimize_shipper_main_table_full( $main_table );
		
		return $results;
	}
	
	/**
	 * Fast optimization for main shipper table
	 */
	private function optimize_shipper_main_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for better query performance
		$indexes = [
			'idx_user_date_created' => 'user_id_added, date_created',
			'idx_user_updated' => 'user_id_updated, date_updated',
			'idx_shipper_email' => 'shipper_name, email',
			'idx_shipper_phone' => 'shipper_name, phone_number',
			'idx_city_state' => 'city, state',
			'idx_zip_city' => 'zip_code, city',
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
	 * Full optimization for main shipper table
	 */
	private function optimize_shipper_main_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY user_id_added BIGINT UNSIGNED NOT NULL",
			"ALTER TABLE $table_name MODIFY user_id_updated BIGINT UNSIGNED NULL",
			"ALTER TABLE $table_name MODIFY date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
			"ALTER TABLE $table_name MODIFY date_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_user_date_created' => 'user_id_added, date_created',
			'idx_user_updated' => 'user_id_updated, date_updated',
			'idx_shipper_email' => 'shipper_name, email',
			'idx_shipper_phone' => 'shipper_name, phone_number',
			'idx_city_state' => 'city, state',
			'idx_zip_city' => 'zip_code, city',
			'idx_user_shipper_date' => 'user_id_added, shipper_name, date_created',
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