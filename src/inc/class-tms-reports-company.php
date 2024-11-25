<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsCompany extends TMSReportsHelper {
	
	public $table_main = 'reports_company';
	public $posts_per_page = 2;
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_company', array( $this, 'add_new_company' ) );
		add_action( 'wp_ajax_search_company', array( $this, 'search_company' ) );
	}
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table_company' ) );
		
		//TODO update after create / set index db up speed
//		add_action( 'after_setup_theme', array( $this, 'update_table_company_with_indexes' ) );
		$this->ajax_actions();
	}
	
	public function update_table_company_with_indexes() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		// Добавляем индексы для полей, по которым выполняются поиски
		$wpdb->query("
        ALTER TABLE $table_name
        ADD INDEX idx_company_name (company_name),
        ADD INDEX idx_mc_number (mc_number),
        ADD INDEX idx_dot_number (dot_number),
        ADD INDEX idx_email (email);
    ");
	}
	
	public function search_company() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"search" => FILTER_SANITIZE_STRING
			] );
			
			$tmp = '';
			
			if ( empty( $MY_INPUT[ 'search' ] ) ) {
				wp_send_json_success( array( 'template' => $tmp ) );
			}
			
			$response = $this->search_company_in_db( $MY_INPUT[ 'search' ] );
			if ( is_array( $response ) ) {
				if ( sizeof( $response ) === 0 ) {
					
					$tmp = '
						<li class="my-dropdown-search__item">
	                        <p class="my-dropdown-search__not-found">
		                        Not found
		                        <a class="js-open-popup-activator link-primary"
		                           href="#popup_add_company">
		                           click here
		                        </a> to add a new customer.
	                        </p>
	                    </li>
					';
					
					wp_send_json_success( array( 'template' => $tmp ) );
				}
				
				foreach ( $response as $value ) {
					$contact = $value->contact_first_name . ' ' . $value->contact_last_name;
					$phone   = $value->phone_number;
					$email   = $value->email;
					$name    = $value->company_name;
					$address = $value->address1 . ', ' . $this->get_label_by_key( $value->state ) . ' ' . $value->zip_code . ', ' . $value->country;
					$dot     = $value->dot_number;
					$mc      = $value->mc_number;
					
					$dot_tmpl = ! empty( $dot ) ? '<span>
	                                <strong>#DOT:</strong>' . $dot . '
	                              </span>' : '';
					
					$mc_tmpl = ! empty( $mc ) ? '<span>
                                    <strong>#MC:</strong>' . $mc . '
                                </span>' : '';
					
					$tmp .= '
						<li class="my-dropdown-search__item">
	                        <a class="my-dropdown-search__link js-link-search-result" href="#">
	                            <span class="my-dropdown-search__name">
	                                <strong>' . $name . '</strong>
	                                <span>' . $address . '</span>
	                            </span>
	                            <span class="my-dropdown-search__others">
	                               ' . $dot_tmpl . $mc_tmpl . '
	                            </span>
	                            
	                            <div class="d-none">
		                            <div class="js-content-company my-dropdown-search__hidden">
		                                ' . $this->print_list_customers( $name, $address, $mc, $dot, $contact, $phone, $email, $value->id ) . '
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
	
	public function print_list_customers(
		$name = '', $address = '', $mc = '', $dot = '', $contact = '', $phone = '', $email = '', $id
	) {
		
		if ( ! $id ) {
			return false;
		}
		
		$dot_tmpl = ! empty( $dot ) ? '<span>
	                                <strong>#DOT:</strong>' . $dot . '
	                              </span>' : '';
		
		$mc_tmpl = ! empty( $mc ) ? '<span>
                                    <strong>#MC:</strong>' . $mc . '
                                </span>' : '';
		
		$template = '<ul class="result-search-el">
                        <li class="name">' . $name . '</li>
                        <li class="address">' . $address . '</li>
                        <li>' . $mc_tmpl . '</li>
                        <li>' . $dot_tmpl . '</li>
                        <li>' . $contact . '</li>
                        <li>' . $phone . '</li>
                        <li>' . $email . '</li>
					</ul>
					<input type="hidden" name="customer_id" value="' . $id . '">';
		
		return $template;
	}
	
	public function get_company_by_id( $ID, $return_type = OBJECT ) {
		global $wpdb;
		$query = $wpdb->prepare( "
        SELECT * FROM {$wpdb->prefix}{$this->table_main}
        WHERE id = %d
    	", $ID );
		
		// Execute the query
		$results = $wpdb->get_results( $query, $return_type );
		
		return $results;
	}
	
	public function search_company_in_db( $search_term ) {
		global $wpdb;
		
		// Ensure the search term is sanitized
		$search_term = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		// Define the query to search in the specified fields, ignoring case sensitivity
		$query = $wpdb->prepare( "
        SELECT * FROM {$wpdb->prefix}{$this->table_main}
        WHERE LOWER(company_name) LIKE LOWER(%s)
        OR mc_number LIKE %s
        OR dot_number LIKE %s
        LIMIT 5
    ", $search_term, $search_term, $search_term );
		
		// Execute the query
		$results = $wpdb->get_results( $query );
		
		return $results;
	}
	
	public function add_new_company() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"select_project"  => FILTER_SANITIZE_STRING,
				"company_name"    => FILTER_SANITIZE_STRING,
				"country"         => FILTER_SANITIZE_STRING,
				"Addr1"           => FILTER_SANITIZE_STRING,
				"Addr2"           => FILTER_SANITIZE_STRING,
				"City"            => FILTER_SANITIZE_STRING,
				"State"           => FILTER_SANITIZE_STRING,
				"ZipCode"         => FILTER_SANITIZE_STRING,
				"FirstName"       => FILTER_SANITIZE_STRING,
				"LastName"        => FILTER_SANITIZE_STRING,
				"Phone"           => FILTER_SANITIZE_STRING,
				"Email"           => FILTER_SANITIZE_EMAIL,
				"MotorCarrNo"     => FILTER_SANITIZE_STRING,
				"DotNo"           => FILTER_SANITIZE_STRING,
				"set_up"          => FILTER_SANITIZE_STRING,
				"set_up_platform" => FILTER_SANITIZE_STRING,
			] );
			
			// Check if 'set_up' is 'completed' and set the timestamp
			$set_up_timestamp = null;
			if ( isset( $MY_INPUT[ 'set_up' ] ) && $MY_INPUT[ 'set_up' ] === 'completed' ) {
				$set_up_timestamp = current_time( 'mysql' ); // or you can use date('Y-m-d H:i:s') if needed
			}
			
			$set_up_array = array(
				"Odysseia" => null,
				"Martlet" => null,
				"Endurance" => null,
			);
			
			$set_up_completed_array = array(
				"Odysseia" => null,
				"Martlet" => null,
				"Endurance" => null,
			);
			
			$set_up_array[$MY_INPUT['select_project']] = $MY_INPUT['set_up'];
			$set_up_completed_array[$MY_INPUT['select_project']] = $set_up_timestamp;
			
			$MY_INPUT[ 'set_up' ] = json_encode($set_up_array);
			$MY_INPUT[ 'completed' ] = json_encode($set_up_completed_array);
			
			// Insert the company report
			$result = $this->add_company( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				wp_send_json_success( [ 'message' => 'Company successfully added', 'data' => $MY_INPUT ] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not created, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function add_company( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$insert_params = array(
			'company_name'         => $data[ 'company_name' ],
			'country'              => $data[ 'country' ],
			'address1'             => $data[ 'Addr1' ],
			'address2'             => $data[ 'Addr2' ],
			'city'                 => $data[ 'City' ],
			'state'                => $data[ 'State' ],
			'zip_code'             => $data[ 'ZipCode' ],
			'contact_first_name'   => $data[ 'FirstName' ],
			'contact_last_name'    => $data[ 'LastName' ],
			'phone_number'         => $data[ 'Phone' ],
			'email'                => $data[ 'Email' ],
			'mc_number'            => $data[ 'MotorCarrNo' ],
			'dot_number'           => $data[ 'DotNo' ],
			'user_id_added'        => $user_id,
			'date_created'         => current_time( 'mysql' ),
			'user_id_updated'      => $user_id,
			'date_updated'         => current_time( 'mysql' ),
			'set_up'               => $data[ 'set_up' ],
			'set_up_platform'      => $data[ 'set_up_platform' ],
			'date_set_up_compleat' => $data[ 'completed' ],
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
			'%s',  // mc_number
			'%s',  // dot_number
			'%d',  // user_id_added
			'%s',  // date_created
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // set_up
			'%s',  // set_up_platform
			'%s',  // date_set_up_compleat
		) );
		
		// Check if the insert was successful
		if ( $result ) {
			return $wpdb->insert_id; // Return the ID of the added record
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Check for specific unique constraint violations
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'company_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this name already exists.' );
				} elseif ( strpos( $error, 'mc_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this MC number already exists.' );
				} elseif ( strpos( $error, 'dot_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this DOT number already exists.' );
				}
			}
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error adding the company report to the database: ' . $error );
		}
	}
	
	public function get_table_records() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_main;
		
		$current_page = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
		
		$search = isset($_GET['my_search']) ? sanitize_text_field($_GET['my_search']) : '';
		$platform = isset($_GET['platform']) ? sanitize_text_field($_GET['platform']) : '';
		
		$offset = ($current_page - 1) * $this->posts_per_page;
		
		$count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
		
		$main_query = "SELECT * FROM $table_name WHERE 1=1";
		
		if (!empty($search)) {
			$search = '%' . $wpdb->esc_like($search) . '%';
			$search_condition = " AND (
            company_name LIKE %s OR
            zip_code LIKE %s OR
            mc_number LIKE %s OR
            phone_number LIKE %s OR
            email LIKE %s
        )";
			$count_query .= $search_condition;
			$main_query .= $search_condition;
		}
		
		if (!empty($platform)) {
			$platform_condition = " AND set_up_platform = %s";
			$count_query .= $platform_condition;
			$main_query .= $platform_condition;
		}
		
		$main_query .= " LIMIT %d OFFSET %d";
		
		$params = [];
		if (!empty($search)) {
			$params = array_merge($params, [$search, $search, $search, $search, $search]);
		}
		if (!empty($platform)) {
			$params[] = $platform;
		}
		
		$total_records = (int) $wpdb->get_var($wpdb->prepare($count_query, ...$params));
		$main_results = $wpdb->get_results(
			$wpdb->prepare($main_query, array_merge($params, [$this->posts_per_page, $offset])),
			ARRAY_A
		);
		
		$total_pages = ceil($total_records / $this->posts_per_page);
		
		return array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_page'  => $current_page,
		);
	}

	
	public function create_table_company() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        country varchar(255) NOT NULL,
        address1 varchar(255) NOT NULL,
        address2 varchar(255),
        city varchar(100) NOT NULL,
        state varchar(100) NOT NULL,
        zip_code varchar(20) NOT NULL,
        contact_first_name varchar(100) NOT NULL,
        contact_last_name varchar(100),
        phone_number varchar(100) NOT NULL,
        email varchar(255) NOT NULL,
        mc_number varchar(50),
        dot_number varchar(50),
        user_id_added mediumint(9) NOT NULL,
        date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        user_id_updated mediumint(9) NULL,
        date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        set_up TEXT,
	    set_up_platform varchar(100),
	    date_set_up_compleat TEXT,
        PRIMARY KEY (id),
        UNIQUE KEY company_name (company_name),
        UNIQUE KEY mc_number (mc_number),
        INDEX idx_company_name (company_name),
        INDEX idx_mc_number (mc_number),
        INDEX idx_dot_number (dot_number),
        INDEX idx_email (email)
    ) $charset_collate;";
		dbDelta( $sql );
	}
	
}