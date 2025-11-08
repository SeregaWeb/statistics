<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsCompany extends TMSReportsHelper {
	
	public $table_main     = 'reports_company';
	public $table_meta     = 'reportsmeta_company';
	public $table_notice   = 'brokers_notice';
	public $posts_per_page = 25;
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_company', array( $this, 'add_new_company' ) );
		add_action( 'wp_ajax_update_company', array( $this, 'update_company' ) );
		add_action( 'wp_ajax_search_company', array( $this, 'search_company' ) );
		add_action( 'wp_ajax_delete_broker', array( $this, 'delete_broker' ) );
		add_action( 'wp_ajax_optimize_company_tables', array( $this, 'optimize_company_tables' ) );
		add_action( 'wp_ajax_add_broker_notice', array( $this, 'ajax_add_broker_notice' ) );
		add_action( 'wp_ajax_get_broker_notices', array( $this, 'ajax_get_broker_notices' ) );
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
		$wpdb->query( "
        ALTER TABLE $table_name
        ADD INDEX idx_company_name (company_name),
        ADD INDEX idx_mc_number (mc_number),
        ADD INDEX idx_dot_number (dot_number),
        ADD INDEX idx_email (email);
    " );
	}
	
	public function get_broker_and_link_by_id( $id, $return_html = true ) {
		
		global $global_options;
		$ling_brocker_single = get_field_value( $global_options, 'single_page_broker' );
		
		$broker_info = $this->get_company_by_id( $id, ARRAY_A );
		
		$broker_name = '';
		$broker_mc   = '';
		$platform    = '';
		
		if ( isset( $broker_info[ 0 ] ) && $broker_info[ 0 ] ) {
			$broker_name = $broker_info[ 0 ][ 'company_name' ];
			$broker_mc   = $broker_info[ 0 ][ 'mc_number' ];
			$platform    = $broker_info[ 0 ][ 'set_up_platform' ];
		}
		
		if ( ! $broker_mc ) {
			$broker_mc = "N/A";
		}
		
		if ( ! $broker_name ) {
			$broker_name = "N/A";
		}
		
		ob_start();
		?>
        <div class="d-flex flex-column">
			
			<?php if ( ! isset( $broker_info[ 0 ] ) ): ?>
                <span class="text-small text-danger">This broker has been deleted</span>
			<?php else: ?>
				<?php if ( $broker_name != 'N/A' ): ?>
                    <a class="m-0"
                       href="<?php echo $ling_brocker_single . '?broker_id=' . $id; ?>"><?php echo $broker_name; ?></a>
				<?php else: ?>
                    <p class="m-0"><?php echo $broker_name; ?></p>
				<?php endif; ?>
                <span class="text-small"><?php echo $broker_mc; ?></span>
			<?php endif; ?>
        </div>
		<?php
		if ( $return_html ) {
			return ob_get_clean();
		}
		
		return array(
			'template' => ob_get_clean(),
			'name'     => $broker_name,
			'mc'       => $broker_mc,
			'platform' => $platform,
		);
	}
	
	public function search_company() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				"search" => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) )
			];
			
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
		                                ' . $this->print_list_customers( $value->id, $name, $address, $mc, $dot, $contact, $phone, $email ) . '
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
		$id, $name = '', $address = '', $mc = '', $dot = '', $contact = '', $phone = '', $email = ''
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
				"select_project"      => FILTER_SANITIZE_STRING,
				"company_name"        => FILTER_SANITIZE_STRING,
				"country"             => FILTER_SANITIZE_STRING,
				"Addr1"               => FILTER_SANITIZE_STRING,
				"Addr2"               => FILTER_SANITIZE_STRING,
				"City"                => FILTER_SANITIZE_STRING,
				"State"               => FILTER_SANITIZE_STRING,
				"ZipCode"             => FILTER_SANITIZE_STRING,
				"FirstName"           => FILTER_SANITIZE_STRING,
				"LastName"            => FILTER_SANITIZE_STRING,
				"Phone"               => FILTER_SANITIZE_STRING,
				"Email"               => FILTER_SANITIZE_EMAIL,
				"MotorCarrNo"         => FILTER_SANITIZE_STRING,
				"DotNo"               => FILTER_SANITIZE_STRING,
				"set_up"              => FILTER_SANITIZE_STRING,
				"set_up_platform"     => FILTER_SANITIZE_STRING,
				"notes"               => FILTER_SANITIZE_STRING,
				"factoring_broker"    => FILTER_SANITIZE_STRING,
				"work_with_odysseia"  => FILTER_VALIDATE_BOOLEAN,
				"work_with_martlet"   => FILTER_VALIDATE_BOOLEAN,
				"work_with_endurance" => FILTER_VALIDATE_BOOLEAN,
			] );
			
			$post_meta = array(
				"factoring_broker"    => $MY_INPUT[ "factoring_broker" ],
				"notes"               => $MY_INPUT[ "notes" ],
				"work_with_odysseia"  => $MY_INPUT[ "work_with_odysseia" ],
				"work_with_martlet"   => $MY_INPUT[ "work_with_martlet" ],
				"work_with_endurance" => $MY_INPUT[ "work_with_endurance" ],
			);
			
			// Check if 'set_up' is 'completed' and set the timestamp
			$set_up_timestamp = null;
			if ( isset( $MY_INPUT[ 'set_up' ] ) && $MY_INPUT[ 'set_up' ] === 'completed' ) {
				$set_up_timestamp = current_time( 'mysql' ); // or you can use date('Y-m-d H:i:s') if needed
			}
			
			$set_up_array = array(
				"Odysseia"  => '',
				"Martlet"   => '',
				"Endurance" => '',
			);
			
			$set_up_completed_array = array(
				"Odysseia"  => '',
				"Martlet"   => '',
				"Endurance" => '',
			);
			
			$set_up_array[ $MY_INPUT[ 'select_project' ] ]           = $MY_INPUT[ 'set_up' ];
			$set_up_completed_array[ $MY_INPUT[ 'select_project' ] ] = $set_up_timestamp;
			
			$MY_INPUT[ 'set_up' ]    = json_encode( $set_up_array );
			$MY_INPUT[ 'completed' ] = json_encode( $set_up_completed_array );
			
			// Insert the company report
			$result = $this->add_company( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				
				$this->update_post_meta_data( $result, $post_meta );
				
				$contact = $MY_INPUT[ 'FirstName' ] . ' ' . $MY_INPUT[ 'LastName' ];
				$phone   = $MY_INPUT[ 'Phone' ];
				$email   = $MY_INPUT[ 'Email' ];
				$name    = $MY_INPUT[ 'company_name' ];
				$address = $MY_INPUT[ 'Addr1' ] . ', ' . $this->get_label_by_key( $MY_INPUT[ 'State' ] ) . ' ' . $MY_INPUT[ 'ZipCode' ] . ', ' . $MY_INPUT[ 'country' ];
				$dot     = $MY_INPUT[ 'DotNo' ];
				$mc      = $MY_INPUT[ 'MotorCarrNo' ];
				
				$template_select_company = $this->print_list_customers( $result, $name, $address, $mc, $dot, $contact, $phone, $email );
				
				wp_send_json_success( [
					'message' => 'Company successfully added',
					'data'    => $MY_INPUT,
					'tmpl'    => $template_select_company,
					'name'    => $name
				] );
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
	
	public function update_company() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data - using wp_unslash to remove WordPress magic quotes
			$MY_INPUT = [
				"select_project"      => sanitize_text_field( wp_unslash( $_POST['select_project'] ?? '' ) ),
				"broker_id"           => sanitize_text_field( wp_unslash( $_POST['broker_id'] ?? '' ) ),
				"company_name"        => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
				"country"             => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
				"Addr1"               => sanitize_text_field( wp_unslash( $_POST['Addr1'] ?? '' ) ),
				"Addr2"               => sanitize_text_field( wp_unslash( $_POST['Addr2'] ?? '' ) ),
				"City"                => sanitize_text_field( wp_unslash( $_POST['City'] ?? '' ) ),
				"State"               => sanitize_text_field( wp_unslash( $_POST['State'] ?? '' ) ),
				"ZipCode"             => sanitize_text_field( wp_unslash( $_POST['ZipCode'] ?? '' ) ),
				"FirstName"           => sanitize_text_field( wp_unslash( $_POST['FirstName'] ?? '' ) ),
				"LastName"            => sanitize_text_field( wp_unslash( $_POST['LastName'] ?? '' ) ),
				"Phone"               => sanitize_text_field( wp_unslash( $_POST['Phone'] ?? '' ) ),
				"Email"               => sanitize_email( wp_unslash( $_POST['Email'] ?? '' ) ),
				"MotorCarrNo"         => sanitize_text_field( wp_unslash( $_POST['MotorCarrNo'] ?? '' ) ),
				"DotNo"               => sanitize_text_field( wp_unslash( $_POST['DotNo'] ?? '' ) ),
				"set_up"              => sanitize_text_field( wp_unslash( $_POST['set_up'] ?? '' ) ),
				"set_up_platform"     => sanitize_text_field( wp_unslash( $_POST['set_up_platform'] ?? '' ) ),
				"notes"               => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
				"work_with_odysseia"  => filter_var( $_POST['work_with_odysseia'] ?? false, FILTER_VALIDATE_BOOLEAN ),
				"work_with_martlet"   => filter_var( $_POST['work_with_martlet'] ?? false, FILTER_VALIDATE_BOOLEAN ),
				"work_with_endurance" => filter_var( $_POST['work_with_endurance'] ?? false, FILTER_VALIDATE_BOOLEAN ),
				
				"factoring_broker"  => sanitize_text_field( wp_unslash( $_POST['factoring_broker'] ?? '' ) ),
				"accounting_phone"  => sanitize_text_field( wp_unslash( $_POST['accounting_phone'] ?? '' ) ),
				"accounting_email"  => sanitize_email( wp_unslash( $_POST['accounting_email'] ?? '' ) ),
				"days_to_pay"       => sanitize_text_field( wp_unslash( $_POST['days_to_pay'] ?? '' ) ),
				"quick_pay_option"  => filter_var( $_POST['quick_pay_option'] ?? false, FILTER_VALIDATE_BOOLEAN ),
				"quick_pay_percent" => sanitize_text_field( wp_unslash( $_POST['quick_pay_percent'] ?? '' ) ),
				"company_status"    => sanitize_text_field( wp_unslash( $_POST['company_status'] ?? '' ) ),
			];
			
			$post_meta = array(
				"notes"               => $MY_INPUT[ "notes" ],
				"work_with_odysseia"  => $MY_INPUT[ "work_with_odysseia" ],
				"work_with_martlet"   => $MY_INPUT[ "work_with_martlet" ],
				"work_with_endurance" => $MY_INPUT[ "work_with_endurance" ],
				"factoring_broker"    => $MY_INPUT[ "factoring_broker" ],
				"accounting_phone"    => $MY_INPUT[ "accounting_phone" ],
				"accounting_email"    => $MY_INPUT[ "accounting_email" ],
				"days_to_pay"         => $MY_INPUT[ "days_to_pay" ],
				"quick_pay_option"    => $MY_INPUT[ "quick_pay_option" ],
				"quick_pay_percent"   => $MY_INPUT[ "quick_pay_percent" ],
				"company_status"      => $MY_INPUT[ "company_status" ],
			);
			
			// Check if 'set_up' is 'completed' and set the timestamp
			$set_up_timestamp = null;
			if ( isset( $MY_INPUT[ 'set_up' ] ) && $MY_INPUT[ 'set_up' ] === 'completed' ) {
				$set_up_timestamp = current_time( 'mysql' ); // or you can use date('Y-m-d H:i:s') if needed
			}
			
			$json_input  = str_replace( '"null"', 'null', $_POST[ 'json-set-up' ] );
			$json_input2 = str_replace( '"null"', 'null', $_POST[ 'json-completed' ] );
			
			$set_up_array           = json_decode( $json_input, true );
			$set_up_completed_array = json_decode( $json_input2, true );
			
			$set_up_array[ $MY_INPUT[ 'select_project' ] ]           = $MY_INPUT[ 'set_up' ];
			$set_up_completed_array[ $MY_INPUT[ 'select_project' ] ] = $set_up_timestamp;
			
			$MY_INPUT[ 'set_up' ]    = json_encode( $set_up_array );
			$MY_INPUT[ 'completed' ] = json_encode( $set_up_completed_array );

			$result                  = $this->update_company_in_db( $MY_INPUT );
			
			if ( $result ) {
				$this->update_post_meta_data( $MY_INPUT[ 'broker_id' ], $post_meta );
				wp_send_json_success( [ 'message' => 'Company successfully update', 'data' => $MY_INPUT ] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	
	public function update_company_in_db( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		$record_id  = $data[ 'broker_id' ];
		
		$result = $wpdb->update( $table_name, // Table name
			array(       // Columns to update
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
				'user_id_updated'      => $user_id,
				'date_updated'         => current_time( 'mysql' ),
				'set_up'               => $data[ 'set_up' ],
				'set_up_platform'      => $data[ 'set_up_platform' ],
				'date_set_up_compleat' => $data[ 'completed' ],
			), array(       // WHERE condition
				'id' => $record_id, // Assuming 'id' is the primary key for identifying records
			), array(       // Format for columns being updated
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			), array(       // Format for WHERE condition
				'%d',
			) );
		
		
		if ( false !== $result ) {
			if ( $result > 0 ) {
				// Update successful
				return true;
			} else {
				// No rows were updated (possibly because the data is the same)
				return new WP_Error( 'no_changes', 'No changes were made to the record.' );
			}
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Check for specific errors
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'company_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this name already exists.' );
				} elseif ( strpos( $error, 'mc_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this MC number already exists.' );
				} elseif ( strpos( $error, 'dot_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this DOT number already exists.' );
				}
			}
			
			// Generic error handling
			return new WP_Error( 'db_error', 'Error updating the record in the database: ' . $error );
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
	
	public function delete_broker() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"id" => FILTER_SANITIZE_NUMBER_INT
			] );
			
			if ( empty( $MY_INPUT[ 'id' ] ) || ! is_numeric( $MY_INPUT[ 'id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Broker not deleted, id not found' ] );
			}
			
			$delete = $this->delete_broker_by_id( $MY_INPUT[ 'id' ] );
			
			if ( $delete[ 'status' ] ) {
				wp_send_json_success( $delete );
			}
			
			wp_send_json_error( $delete );
		}
	}
	
	public function delete_broker_by_id( $post_id ) {
		
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main; // Основная таблица
		$table_meta = $wpdb->prefix . $this->table_meta; // Таблица мета-данных
		
		// Удаляем мета-данные
		$meta_deleted = $wpdb->delete( $table_meta, [ 'post_id' => $post_id ], [ '%d' ] );
		
		// Проверяем удаление мета-данных
		if ( $meta_deleted === false ) {
			return array( 'status' => false, 'message' => 'Error deleting meta data: ' . $wpdb->last_error );
		}
		
		// Удаляем сам пост
		$post_deleted = $wpdb->delete( $table_main, [ 'id' => $post_id ], [ '%d' ] );
		
		// Проверяем удаление поста
		if ( $post_deleted === false ) {
			return array( 'status' => false, 'message' => 'Error deleting post: ' . $wpdb->last_error );
		}
		
		// Проверяем, были ли найдены записи для удаления
		if ( $meta_deleted === 0 && $post_deleted === 0 ) {
			echo 'Записи для удаления не найдены.';
			
			return array( 'status' => false, 'message' => 'No records found for deletion.' );
		} else {
			return array( 'status' => true, 'message' => 'Successfully deleted post and its meta data.' );
		}
	}
	
	public function get_table_records_2() {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$current_page     = isset( $_GET[ 'paged' ] ) ? (int) $_GET[ 'paged' ] : 1;
		$search           = isset( $_GET[ 'my_search' ] ) ? sanitize_text_field( $_GET[ 'my_search' ] ) : '';
		$platform         = isset( $_GET[ 'platform' ] ) ? sanitize_text_field( $_GET[ 'platform' ] ) : '';
		$factoring_status = isset( $_GET[ 'factoring_status' ] ) ? sanitize_text_field( $_GET[ 'factoring_status' ] )
			: '';
		$setup_status     = isset( $_GET[ 'setup_status' ] ) ? sanitize_text_field( $_GET[ 'setup_status' ] ) : '';
		$company_status   = isset( $_GET[ 'company_status' ] ) ? sanitize_text_field( $_GET[ 'company_status' ] ) : '';
		
		$loads           = new TMSReports();
		$current_project = $loads->project;
		
		$offset = ( $current_page - 1 ) * $this->posts_per_page;
		
		// Собираем все условия в один массив
		$filters      = [];
		$where_params = [];
		
		if ( ! empty( $platform ) ) {
			$filters[]      = "main.set_up_platform = %s";
			$where_params[] = $platform;
		}
		
		if ( ! empty( $setup_status ) && ! empty( $current_project ) ) {
			$filters[]      = "JSON_UNQUOTE(JSON_EXTRACT(main.set_up, %s)) = %s";
			$where_params[] = '$."' . $current_project . '"';
			$where_params[] = $setup_status;
		}
		
		if ( ! empty( $factoring_status ) ) {
			$filters[]      = "(meta.meta_key = 'factoring_broker' AND meta.meta_value = %s)";
			$where_params[] = $factoring_status;
		}
		
		if ( ! empty( $company_status ) ) {
			$filters[]      = "(meta.meta_key = 'company_status' AND meta.meta_value = %s)";
			$where_params[] = $company_status;
		}
		
		if ( ! empty( $search ) ) {
			$search_term  = '%' . $wpdb->esc_like( $search ) . '%';
			$filters[]    = "(main.company_name LIKE %s OR main.zip_code LIKE %s OR main.mc_number LIKE %s OR main.phone_number LIKE %s OR main.email LIKE %s)";
			$where_params = array_merge( $where_params, array_fill( 0, 5, $search_term ) );
		}
		
		$where_clause = ! empty( $filters ) ? "WHERE " . implode( " AND ", $filters ) : "";
		
		// Основной SQL-запрос
		$query = "SELECT
                main.*,
                GROUP_CONCAT(COALESCE(meta.meta_key, '') SEPARATOR '||') AS meta_keys,
                GROUP_CONCAT(COALESCE(meta.meta_value, '') SEPARATOR '||') AS meta_values
              FROM $table_main AS main
              LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
              $where_clause
              GROUP BY main.id
              LIMIT %d, %d";
		
		$where_params[] = $offset;
		$where_params[] = $this->posts_per_page;
		
		$count_query = "SELECT COUNT(DISTINCT main.id)
                FROM wp_reports_company AS main
                LEFT JOIN wp_reportsmeta_company AS meta ON main.id = meta.post_id
                $where_clause"; // Где $where_clause это тот же фильтр, что и для основного запроса
		
		$prepared_query = $wpdb->prepare( $count_query, ...$where_params );
		$total_records  = $wpdb->get_var( $prepared_query );
		
		if ( is_null( $total_records ) ) {
			error_log( 'COUNT query returned NULL. Query: ' . $count_query );
		}
		
		// Подсчет количества страниц
		$total_pages = ( $total_records > 0 ) ? ceil( $total_records / $this->posts_per_page ) : 1;
		
		// Выполняем основной запрос
		$results = $wpdb->get_results( $wpdb->prepare( $query, $where_params ), ARRAY_A );
		
		// Обрабатываем мета-данные
		foreach ( $results as &$result ) {
			$meta_keys   = explode( '||', $result[ 'meta_keys' ] );
			$meta_values = explode( '||', $result[ 'meta_values' ] );
			
			unset( $result[ 'meta_keys' ], $result[ 'meta_values' ] );
			
			$meta_data = [];
			foreach ( $meta_keys as $index => $key ) {
				$meta_data[ $key ] = $meta_values[ $index ] ?? null;
			}
			
			$result[ 'meta' ] = $meta_data;
		}
		
		return [
			'results'      => array_values( $results ),
			'total_pages'  => $total_pages,
			'total_posts'  => $total_records,
			'current_page' => $current_page,
		];
	}
	
	
	public function get_table_records() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$current_page     = isset( $_GET[ 'paged' ] ) ? (int) $_GET[ 'paged' ] : 1;
		$search           = isset( $_GET[ 'my_search' ] ) ? sanitize_text_field( $_GET[ 'my_search' ] ) : '';
		$platform         = isset( $_GET[ 'platform' ] ) ? sanitize_text_field( $_GET[ 'platform' ] ) : '';
		$factoring_status = isset( $_GET[ 'factoring_status' ] ) ? sanitize_text_field( $_GET[ 'factoring_status' ] )
			: '';
		$setup_status     = isset( $_GET[ 'setup_status' ] ) ? sanitize_text_field( $_GET[ 'setup_status' ] ) : '';
		$company_status   = isset( $_GET[ 'company_status' ] ) ? sanitize_text_field( $_GET[ 'company_status' ] ) : '';
		$offset           = ( $current_page - 1 ) * $this->posts_per_page;
		$loads            = new TMSReports();
		$current_project  = $loads->project;
		
		$where_clause = " WHERE 1=1";
		$where_params = [];
		
		if ( ! empty( $search ) ) {
			$search_term  = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clause .= " AND (
            main.company_name LIKE %s OR
            main.zip_code LIKE %s OR
            main.mc_number LIKE %s OR
            main.phone_number LIKE %s OR
            main.email LIKE %s
        )";
			$where_params = array_merge( $where_params, array_fill( 0, 5, $search_term ) );
		}
		
		if ( ! empty( $platform ) ) {
			$where_clause   .= " AND main.set_up_platform = %s";
			$where_params[] = $platform;
		}
		
		if ( ! empty( $setup_status ) && ! empty( $current_project ) ) {
			$where_clause   .= " AND JSON_UNQUOTE(JSON_EXTRACT(main.set_up, %s)) = %s";
			$where_params[] = '$."' . $current_project . '"';
			$where_params[] = $setup_status;
		}
		
		$main_query = "
    SELECT main.*,
        MAX(CASE WHEN meta.meta_key = 'factoring_broker' THEN meta.meta_value END) AS factoring_broker,
        MAX(CASE WHEN meta.meta_key = 'company_status' THEN meta.meta_value END) AS company_status
    FROM $table_name AS main
    LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
    $where_clause
    GROUP BY main.id
";
		
		$having_clauses = [];
		$having_params  = [];
		
		if ( ! empty( $factoring_status ) ) {
			$having_clauses[] = "MAX(CASE WHEN meta.meta_key = 'factoring_broker' THEN meta.meta_value END) = %s";
			$having_params[]  = $factoring_status;
		}
		if ( ! empty( $company_status ) ) {
			$having_clauses[] = "MAX(CASE WHEN meta.meta_key = 'company_status' THEN meta.meta_value END) = %s";
			$having_params[]  = $company_status;
		}
		
		if ( ! empty( $having_clauses ) ) {
			$main_query .= " HAVING " . implode( " AND ", $having_clauses );
		}
		
		$main_query     .= " ORDER BY main.date_created DESC LIMIT %d OFFSET %d";
		$where_params[] = $this->posts_per_page;
		$where_params[] = $offset;
		
		$final_params = array_merge( $where_params, $having_params );
		$raw_results  = $wpdb->get_results( $wpdb->prepare( $main_query, ...$final_params ), ARRAY_A );
//		var_dump($wpdb->prepare($main_query, ...$final_params));
		$count_query = "
        SELECT COUNT(*) FROM (
            SELECT main.id
            FROM $table_name AS main
            LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
            " . $where_clause . "
            GROUP BY main.id
    ";
		if ( ! empty( $having_clauses ) ) {
			$count_query .= " HAVING " . implode( " AND ", $having_clauses );
		}
		$count_query .= ") AS sub";
		
		$count_where_params = array_slice( $where_params, 0, count( $where_params ) - 2 );
		$count_params       = array_merge( $count_where_params, $having_params );
		$total_records      = (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$count_params ) );
		$total_pages        = ceil( $total_records / $this->posts_per_page );
		
		$results = [];
		foreach ( $raw_results as $row ) {
			$post_id = $row[ 'id' ];
			if ( ! isset( $results[ $post_id ] ) ) {
				$results[ $post_id ]                  = $row;
				$results[ $post_id ][ 'meta_fields' ] = [];
			}
			if ( ! empty( $row[ 'factoring_broker' ] ) ) {
				$results[ $post_id ][ 'meta_fields' ][ 'factoring_broker' ] = $row[ 'factoring_broker' ];
			}
			if ( ! empty( $row[ 'company_status' ] ) ) {
				$results[ $post_id ][ 'meta_fields' ][ 'company_status' ] = $row[ 'company_status' ];
			}
		}
		
		return [
			'results'      => array_values( $results ),
			'total_pages'  => $total_pages,
			'total_posts'  => $total_records,
			'current_page' => $current_page,
		];
	}
	
	
	public function create_table_company() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$charset_collate = $wpdb->get_charset_collate();
		
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
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
		
		// Create brokers notice table
		$table_notice_name = $wpdb->prefix . $this->table_notice;
		
		$sql_notice = "CREATE TABLE $table_notice_name (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		broker_id BIGINT UNSIGNED DEFAULT NULL,
		name VARCHAR(255) NOT NULL,
		date INT(11) NOT NULL,
		message TEXT,
		load_number VARCHAR(100),
		status TINYINT(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY idx_broker_id (broker_id)
	) $charset_collate;";
		
		dbDelta( $sql_notice );
		
	}
	
	public function get_all_meta_by_post_id( $post_id ) {
		global $wpdb;
		$post_id         = intval( $post_id );
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		$sql             = $wpdb->prepare( "SELECT meta_key, meta_value FROM $table_meta_name WHERE post_id = %d", $post_id );
		
		// Выполняем запрос
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		// Если результаты пусты, возвращаем пустой массив
		if ( empty( $results ) ) {
			return [];
		}
		
		// Преобразуем записи в удобный для работы массив
		$meta_data = [];
		foreach ( $results as $row ) {
			$meta_data[ $row[ 'meta_key' ] ] = maybe_unserialize( $row[ 'meta_value' ] );
		}
		
		return $meta_data;
	}
	
	/**
	 * @param $post_id
	 * @param $meta_data
	 * update post meta fields in db
	 *
	 * @return true|WP_Error
	 */
	function update_post_meta_data( $post_id, $meta_data ) {
		global $wpdb;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
		foreach ( $meta_data as $meta_key => $meta_value ) {
			$existing = $wpdb->get_var( $wpdb->prepare( "
            SELECT id FROM $table_meta_name
            WHERE post_id = %d AND meta_key = %s
        ", $post_id, $meta_key ) );
			
			if ( $existing ) {
				// Обновляем существующую запись
				$wpdb->update( $table_meta_name, array( 'meta_value' => $meta_value ), array( 'id' => $existing ), array( '%s' ), array( '%d' ) );
			} else {
				// Вставляем новую запись
				$wpdb->insert( $table_meta_name, array(
					'post_id'    => $post_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value
				), array( '%d', '%s', '%s' ) );
			}
		}
		
		// Проверка на ошибки
		if ( $wpdb->last_error ) {
			return new WP_Error( 'db_error', 'Ошибка при обновлении метаданных: ' . $wpdb->last_error );
		}
		
		return true;
	}
	
	/**
	 * Optimize company tables for better performance with large datasets
	 */
	public function optimize_company_tables() {
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
				$results = $this->perform_full_company_optimization();
			} else {
				$results = $this->perform_fast_company_optimization();
			}
			
			wp_send_json_success( $results );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
	
	/**
	 * Perform fast optimization (indexes only)
	 */
	public function perform_fast_company_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main company table
		$main_table = $wpdb->prefix . $this->table_main;
		$results['main_table'] = $this->optimize_company_main_table_fast( $main_table );
		
		// Optimize meta table
		$meta_table = $wpdb->prefix . $this->table_meta;
		$results['meta_table'] = $this->optimize_company_meta_table_fast( $meta_table );
		
		return $results;
	}
	
	/**
	 * Perform full optimization (structural changes)
	 */
	public function perform_full_company_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main company table
		$main_table = $wpdb->prefix . $this->table_main;
		$results['main_table'] = $this->optimize_company_main_table_full( $main_table );
		
		// Optimize meta table
		$meta_table = $wpdb->prefix . $this->table_meta;
		$results['meta_table'] = $this->optimize_company_meta_table_full( $meta_table );
		
		return $results;
	}
	
	/**
	 * Fast optimization for main company table
	 */
	private function optimize_company_main_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for better query performance
		$indexes = [
			'idx_user_date_created' => 'user_id_added, date_created',
			'idx_platform_date' => 'set_up_platform, date_created',
			'idx_user_platform' => 'user_id_added, set_up_platform',
			'idx_company_email' => 'company_name, email',
			'idx_mc_dot' => 'mc_number, dot_number',
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
	 * Full optimization for main company table
	 */
	private function optimize_company_main_table_full( $table_name ) {
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
			'idx_platform_date' => 'set_up_platform, date_created',
			'idx_user_platform' => 'user_id_added, set_up_platform',
			'idx_company_email' => 'company_name, email',
			'idx_mc_dot' => 'mc_number, dot_number',
			'idx_user_platform_date' => 'user_id_added, set_up_platform, date_created',
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
	 * Fast optimization for company meta table
	 */
	private function optimize_company_meta_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for meta queries
		$indexes = [
			'idx_post_meta_key' => 'post_id, meta_key(191)',
			'idx_meta_key_value' => 'meta_key(191), meta_value(191)',
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
	 * Full optimization for company meta table
	 */
	private function optimize_company_meta_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY post_id BIGINT UNSIGNED NOT NULL",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_post_meta_key' => 'post_id, meta_key(191)',
			'idx_meta_key_value' => 'meta_key(191), meta_value(191)',
			'idx_post_meta_key_value' => 'post_id, meta_key(191), meta_value(191)',
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
	 * Insert broker notice into database
	 */
	private function insert_broker_notice( $broker_id, $name, $date, $message = '', $load_number = '', $status = false ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_notice;
		
		$data = [
			'broker_id'   => (int) $broker_id,
			'name'        => sanitize_text_field( $name ),
			'date'        => (int) $date,
			'message'     => sanitize_textarea_field( $message ),
			'load_number' => sanitize_text_field( $load_number ),
			'status'      => (int) $status,
		];
		
		$formats = [ '%d', '%s', '%d', '%s', '%s', '%d' ];
		
		return $wpdb->insert( $table_name, $data, $formats );
	}
	
	/**
	 * Add new notice for broker
	 */
	public function add_broker_notice( $broker_id, $message, $load_number = '' ) {
		global $wpdb;
		
		if ( empty( $broker_id ) || ! is_numeric( $broker_id ) ) {
			return false;
		}
		
		$broker_id = (int) $broker_id;
		
		$current_user_id = get_current_user_id();
		$helper          = new TMSReportsHelper();
		$user_info       = $helper->get_user_full_name_by_id( $current_user_id );
		$user_name       = $user_info ? $user_info[ 'full_name' ] : 'Unknown User';
		
		$date = current_time( 'timestamp' );
		
		return $this->insert_broker_notice( $broker_id, $user_name, $date, $message, $load_number, 0 );
	}
	
	/**
	 * Get broker notices for AJAX
	 */
	public function ajax_get_broker_notices() {
		$MY_INPUT = filter_var_array( $_POST, [
			"broker_id" => FILTER_SANITIZE_NUMBER_INT,
		] );
		
		if ( ! isset( $MY_INPUT[ 'broker_id' ] ) || empty( $MY_INPUT[ 'broker_id' ] ) ) {
			wp_send_json_error( [ 'message' => 'Broker ID not found' ] );
		}
		
		$broker_id = (int) $MY_INPUT[ 'broker_id' ];
		global $wpdb;
		$table_notice = $wpdb->prefix . $this->table_notice;
		
		$notices = $wpdb->get_results( $wpdb->prepare( "
			SELECT id, name, date, message, load_number, status
			FROM $table_notice
			WHERE broker_id = %d
			ORDER BY date DESC
		", $broker_id ) );
		
		// Clean escaped slashes from message field
		if ( $notices && is_array( $notices ) ) {
			foreach ( $notices as $key => $notice ) {
				if ( isset( $notice->message ) ) {
					$notices[ $key ]->message = stripslashes( $notice->message );
				}
			}
		}
		
		if ( $notices ) {
			wp_send_json_success( $notices );
		} else {
			wp_send_json_success( [] );
		}
	}
	
	/**
	 * AJAX handler for adding broker notice
	 */
	public function ajax_add_broker_notice() {
		// Check nonce for security
		if ( ! wp_verify_nonce( $_POST[ 'tms_broker_notice_nonce' ], 'tms_add_broker_notice' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		$broker_id  = intval( $_POST[ 'broker_id' ] ?? 0 );
		$message    = sanitize_textarea_field( $_POST[ 'message' ] ?? '' );
		$load_number = sanitize_text_field( $_POST[ 'load_number' ] ?? '' );
		
		if ( empty( $broker_id ) || empty( $message ) ) {
			wp_send_json_error( 'Invalid data provided. Message is required.' );
		}
		
		$result = $this->add_broker_notice( $broker_id, $message, $load_number );
		
		if ( $result ) {
			global $global_options;
			$add_new_broker = get_field_value( $global_options, 'single_page_broker' );
			
			$current_user_id = get_current_user_id();
			$project        = get_field( 'current_select', 'user_' . $current_user_id );
			
			$broker_current = $this->get_company_by_id( $broker_id, ARRAY_A );
			$broker_name = isset( $broker_current[ 0 ][ 'company_name' ] ) ? $broker_current[ 0 ][ 'company_name' ] : 'Unknown Broker';
			
			$helper = new TMSReportsHelper();
			$user_name = $helper->get_user_full_name_by_id( $current_user_id );
			
			// Get email addresses
			$emails = array();
			
			// Get admin emails from settings
			$email_helper = new TMSEmails();
			$admin_emails = $email_helper->get_admin_email();
			if ( ! empty( $admin_emails ) ) {
				// Split by comma and trim each email
				$admin_emails_array = array_map( 'trim', explode( ',', $admin_emails ) );
				$emails = array_merge( $emails, $admin_emails_array );
			}
			
			// Get users with Dispatcher Team Leader and Expedite Manager roles
			$users_with_roles = get_users( array(
				'role__in' => array( 'dispatcher-tl', 'expedite_manager' ),
				'fields'   => array( 'user_email' ),
			) );
			
			foreach ( $users_with_roles as $user ) {
				if ( ! empty( $user->user_email ) ) {
					$emails[] = $user->user_email;
				}
			}
			
			// Remove duplicates
			$emails = array_unique( $emails );
			$email_list = implode( ',', $emails );
			
			if ( $add_new_broker ) {
				$link = '<a href="' . $add_new_broker . '?broker_id=' . $broker_id . '">' . '(' . $broker_id . ') ' . $broker_name . '</a>';
			} else {
				$link = '(' . $broker_id . ') ' . $broker_name;
			}
			
			// Prepare email message
			$email_message = "Notice: " . $message;
			if ( ! empty( $load_number ) ) {
				$email_message .= "<br>Load Number: " . esc_html( $load_number );
			}
			
			$email_helper = new TMSEmails();
			$email_helper->send_custom_email( $email_list, array(
				'subject'      => 'New broker note added' . ' (' . $broker_id . ') ' . $broker_name,
				'project_name' => $project,
				'subtitle'     => $user_name[ 'full_name' ] . ' has added broker note ' . $link,
				'message'      => $email_message
			) );
			
			error_log( 'Broker note added successfully. Emails: ' . $email_list . ' User: ' . $user_name[ 'full_name' ] . ' Broker: ' . $link . ' Message: ' . $message );
			
			// Get the newly added notice data to return to frontend
			global $wpdb;
			$table_notice = $wpdb->prefix . $this->table_notice;
			$notice_id = $wpdb->insert_id;
			
			$new_notice = $wpdb->get_row( $wpdb->prepare( "
				SELECT id, name, date, message, load_number, status
				FROM $table_notice
				WHERE id = %d
			", $notice_id ), ARRAY_A );
			
			// Clean escaped slashes from message and load_number fields
			if ( $new_notice && is_array( $new_notice ) ) {
				if ( isset( $new_notice['message'] ) ) {
					$new_notice['message'] = stripslashes( $new_notice['message'] );
				}
				if ( isset( $new_notice['load_number'] ) ) {
					$new_notice['load_number'] = stripslashes( $new_notice['load_number'] );
				}
				if ( isset( $new_notice['name'] ) ) {
					$new_notice['name'] = stripslashes( $new_notice['name'] );
				}
			}
			
			wp_send_json_success( array(
				'message' => 'Note added successfully',
				'notice' => $new_notice ? $new_notice : array()
			) );
			exit;
		} else {
			wp_send_json_error( 'Failed to add note' );
			exit;
		}
	}
	
	/**
	 * Get broker statistics (notices count)
	 */
	public function get_broker_statistics( $broker_id ) {
		global $wpdb;
		
		$table_notice = $wpdb->prefix . $this->table_notice;
		
		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM $table_notice
			WHERE broker_id = %d
		", $broker_id ) );
		
		$notices = $wpdb->get_results( $wpdb->prepare( "
			SELECT id, name, date, message, load_number, status
			FROM $table_notice
			WHERE broker_id = %d
			ORDER BY date DESC
			LIMIT 10
		", $broker_id ) );
		
		return array(
			'notice' => array(
				'count' => (int) $count,
				'data'  => $notices ? $notices : array(),
			),
		);
	}
	
}