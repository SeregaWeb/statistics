<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsCompany extends TMSReportsHelper {
	
	public $table_main     = 'reports_company';
	public $table_meta     = 'reportsmeta_company';
	public $posts_per_page = 25;
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_company', array( $this, 'add_new_company' ) );
		add_action( 'wp_ajax_update_company', array( $this, 'update_company' ) );
		add_action( 'wp_ajax_search_company', array( $this, 'search_company' ) );
		add_action( 'wp_ajax_delete_broker', array( $this, 'delete_broker' ) );
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
		
		if ( isset( $broker_info[ 0 ] ) && $broker_info[ 0 ] ) {
			$broker_name = $broker_info[ 0 ][ 'company_name' ];
			$broker_mc   = $broker_info[ 0 ][ 'mc_number' ];
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
		);
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
				
				$template_select_company = $this->print_list_customers( $name, $address, $mc, $dot, $contact, $phone, $email, $result );
				
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
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"select_project"      => FILTER_SANITIZE_STRING,
				"broker_id"           => FILTER_SANITIZE_STRING,
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
				"work_with_odysseia"  => FILTER_VALIDATE_BOOLEAN,
				"work_with_martlet"   => FILTER_VALIDATE_BOOLEAN,
				"work_with_endurance" => FILTER_VALIDATE_BOOLEAN,
				
				"factoring_broker"  => FILTER_SANITIZE_STRING,
				"accounting_phone"  => FILTER_SANITIZE_STRING,
				"accounting_email"  => FILTER_SANITIZE_STRING,
				"days_to_pay"       => FILTER_SANITIZE_STRING,
				"quick_pay_option"  => FILTER_VALIDATE_BOOLEAN,
				"quick_pay_percent" => FILTER_SANITIZE_STRING,
				"company_status"    => FILTER_SANITIZE_STRING,
			
			
			] );
			
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
			
			$set_up_array           = json_decode( stripslashes( $json_input ), true );
			$set_up_completed_array = json_decode( stripslashes( $json_input2 ), true );
			
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
}