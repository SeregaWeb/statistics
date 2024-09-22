<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
class TMSReports extends TMSReportsHelper {
	
	public $table_main = 'reports';
	
	public function __construct() {
		$user_id = get_current_user_id();
		$curent_tables = get_field('current_select', 'user_'.$user_id);
		if ($curent_tables) {
			$this->table_main = 'reports_'. strtolower($curent_tables);
		}
		
	}
	
	public function update_new_draft_report() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"     => FILTER_SANITIZE_STRING,
				"contact_name"    => FILTER_SANITIZE_STRING,
				"contact_phone"   => FILTER_SANITIZE_STRING,
				"contact_email"   => FILTER_SANITIZE_STRING,
				"post_id"         => FILTER_SANITIZE_STRING,
			] );
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				$additional_names  = filter_var_array( $_POST[ 'additional_contact_name' ], FILTER_SANITIZE_STRING );
				$additional_phones = filter_var_array( $_POST[ 'additional_contact_phone' ], FILTER_SANITIZE_STRING );
				$additional_emails = filter_var_array( $_POST[ 'additional_contact_email' ], FILTER_SANITIZE_EMAIL );
				
				foreach ( $additional_names as $index => $name ) {
					$additional_contacts[] = [
						'name'  => $name,
						'phone' => $additional_phones[ $index ] ?? '',
						'email' => $additional_emails[ $index ] ?? ''
					];
				}
			}
			
			// Convert the additional contacts array to JSON format
			$MY_INPUT[ 'additional_contacts' ] = json_encode( $additional_contacts );
			
			// Insert the company report
			$result = $this->update_report_draft_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [
					'id_created_post' => $result,
					'message'         => 'Company successfully update',
					'data'            => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error updating to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function update_report_draft_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'customer_id'          => $data[ 'customer_id' ],
			'contact_name'         => $data[ 'contact_name' ],
			'contact_phone'        => $data[ 'contact_phone' ],
			'contact_email'        => $data[ 'contact_email' ],
			'user_id_updated'      => $user_id,
			'date_updated'         => current_time( 'mysql' ),
			'additional_contacts'  => $data[ 'additional_contacts' ],
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // customer_id
			'%s',  // contact_name
			'%s',  // contact_phone
			'%s',  // contact_email
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // additional_contacts
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	public function add_report_draft_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$insert_params = array(
			'customer_id'          => $data[ 'customer_id' ],
			'contact_name'         => $data[ 'contact_name' ],
			'contact_phone'        => $data[ 'contact_phone' ],
			'contact_email'        => $data[ 'contact_email' ],
			'user_id_added'        => $user_id,
			'date_created'         => current_time( 'mysql' ),
			'user_id_updated'      => $user_id,
			'date_updated'         => current_time( 'mysql' ),
			'status_post'          => $data[ 'status_post' ],
			'additional_contacts'  => $data[ 'additional_contacts' ],
		);
		
		$result = $wpdb->insert( $table_name, $insert_params, array(
			'%d',  // customer_id
			'%s',  // contact_name
			'%s',  // contact_phone
			'%s',  // contact_email
			'%d',  // user_id_added
			'%s',  // date_created
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // status_post
			'%s',  // set_up
			'%s',  // set_up_platform
			'%s',  // additional_contacts
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
	
	public function add_new_report_draft() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"     => FILTER_SANITIZE_STRING,
				"contact_name"    => FILTER_SANITIZE_STRING,
				"contact_phone"   => FILTER_SANITIZE_STRING,
				"contact_email"   => FILTER_SANITIZE_STRING,
				"set_up"          => FILTER_SANITIZE_STRING,
				"set_up_platform" => FILTER_SANITIZE_STRING,
			] );
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				$additional_names  = filter_var_array( $_POST[ 'additional_contact_name' ], FILTER_SANITIZE_STRING );
				$additional_phones = filter_var_array( $_POST[ 'additional_contact_phone' ], FILTER_SANITIZE_STRING );
				$additional_emails = filter_var_array( $_POST[ 'additional_contact_email' ], FILTER_SANITIZE_EMAIL );
				
				foreach ( $additional_names as $index => $name ) {
					$additional_contacts[] = [
						'name'  => $name,
						'phone' => $additional_phones[ $index ] ?? '',
						'email' => $additional_emails[ $index ] ?? ''
					];
				}
			}
			
			// Convert the additional contacts array to JSON format
			$MY_INPUT[ 'additional_contacts' ] = json_encode( $additional_contacts );
			
			// Check if 'set_up' is 'completed' and set the timestamp
			$set_up_timestamp = null;
			if ( isset( $MY_INPUT[ 'set_up' ] ) && $MY_INPUT[ 'set_up' ] === 'completed' ) {
				$set_up_timestamp = current_time( 'mysql' ); // or you can use date('Y-m-d H:i:s') if needed
			}
			
			$MY_INPUT[ 'completed' ] = $set_up_timestamp;
			
			$MY_INPUT[ "status_post" ] = 'draft';
			
			// Insert the company report
			$result = $this->add_report_draft_in_db( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				wp_send_json_success( [
					'id_created_post' => $result,
					'message'         => 'Company successfully added',
					'data'            => $MY_INPUT
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
	
	public function get_report_by_id( $ID ) {
		global $wpdb;
		$query = $wpdb->prepare( "
        SELECT * FROM {$wpdb->prefix}{$this->table_main}
        WHERE id = %d
    	", $ID );
		
		// Execute the query
		$results = $wpdb->get_results( $query );
		
		return $results;
	}
	
	public function create_table() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		foreach ($tables as $val) {
			$table_name      = $wpdb->prefix .'reports_' . strtolower($val);
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $table_name (
		        id mediumint(9) NOT NULL AUTO_INCREMENT,
		        customer_id mediumint(9) NOT NULL,
		        contact_name varchar(150) NOT NULL,
		        contact_phone varchar(150) NOT NULL,
		        contact_email varchar(150) NOT NULL,
		        user_id_added mediumint(9) NOT NULL,
		        date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		        user_id_updated mediumint(9) NULL NULL,
		        date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		        status_post varchar(50) NULL DEFAULT NULL,
		        date_booked date NOT NULL,
		        dispatcher_initials varchar(255) NOT NULL,
		        reference_number varchar(255) NOT NULL,
		        pick_up_location TEXT NOT NULL,
		        delivery_location TEXT NOT NULL,
		        unit_number_name varchar(255) NOT NULL,
		        booked_rate decimal(10, 2) NOT NULL,
		        driver_rate decimal(10, 2) NOT NULL,
		        profit decimal(10, 2) NOT NULL,
		        pick_up_date date NOT NULL,
		        load_status varchar(50) NOT NULL,
		        load_type varchar(50) NOT NULL,
		        instructions TEXT,
		        commodity varchar(255),
		        weight decimal(10, 2),
		        notes TEXT,
		        source varchar(100),
		        additional_contacts TEXT,
		        attached_file_required longtext,
		        attached_files longtext,
		        PRIMARY KEY  (id)
    		) $charset_collate;";
			
			dbDelta( $sql );
		}
	}
	
	public function update_files_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING
			] );
			
			
			if ( ! empty( $_FILES[ 'attached_file_required' ] ) ) {
				$files          = $_FILES[ 'attached_file_required' ];
				$uploaded_files = [];
				
				$file = [
					'name'     => $files[ 'name' ],
					'type'     => $files[ 'type' ],
					'tmp_name' => $files[ 'tmp_name' ],
					'error'    => $files[ 'error' ],
					'size'     => $files[ 'size' ]
				];
				
				// Используем wp_handle_upload для обработки загрузки
				$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
				
				if ( ! isset( $upload_result[ 'error' ] ) ) {
					// Данные о файле
					$file_url  = $upload_result[ 'url' ];
					$file_type = $upload_result[ 'type' ];
					$file_path = $upload_result[ 'file' ];
					
					// Подготовка данных для записи в медиабиблиотеку
					$attachment = array(
						'guid'           => $file_url,
						'post_mime_type' => $file_type,
						'post_title'     => basename( $file_url ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
					
					// Вставляем запись в базу данных (в таблицу attachments)
					$attachment_id = wp_insert_attachment( $attachment, $file_path );
					
					// Генерация метаданных для вложения и обновление базы данных
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );
					
					// Теперь у вас есть ID загруженного файла
					$uploaded_files[] = $attachment_id;
				} else {
					// Ошибка загрузки файла
					wp_send_json_error( [ 'message' => $upload_result[ 'error' ] ] );
				}
				
				// Теперь у нас есть массив $uploaded_files с загруженными файлами
				$MY_INPUT[ 'uploaded_file_required' ] = $uploaded_files;
			}
			
			if ( ! empty( $_FILES[ 'attached_files' ] ) ) {
				$files          = $_FILES[ 'attached_files' ];
				$uploaded_files = [];
				
				foreach ( $files[ 'name' ] as $key => $value ) {
					if ( $files[ 'name' ][ $key ] ) {
						$file = [
							'name'     => $files[ 'name' ][ $key ],
							'type'     => $files[ 'type' ][ $key ],
							'tmp_name' => $files[ 'tmp_name' ][ $key ],
							'error'    => $files[ 'error' ][ $key ],
							'size'     => $files[ 'size' ][ $key ]
						];
						
						// Используем wp_handle_upload для обработки загрузки
						$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
						
						if ( ! isset( $upload_result[ 'error' ] ) ) {
							// Данные о файле
							$file_url  = $upload_result[ 'url' ];
							$file_type = $upload_result[ 'type' ];
							$file_path = $upload_result[ 'file' ];
							
							// Подготовка данных для записи в медиабиблиотеку
							$attachment = array(
								'guid'           => $file_url,
								'post_mime_type' => $file_type,
								'post_title'     => basename( $file_url ),
								'post_content'   => '',
								'post_status'    => 'inherit'
							);
							
							// Вставляем запись в базу данных (в таблицу attachments)
							$attachment_id = wp_insert_attachment( $attachment, $file_path );
							
							// Генерация метаданных для вложения и обновление базы данных
							require_once( ABSPATH . 'wp-admin/includes/image.php' );
							$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
							wp_update_attachment_metadata( $attachment_id, $attachment_data );
							
							// Теперь у вас есть ID загруженного файла
							$uploaded_files[] = $attachment_id;
						} else {
							// Ошибка загрузки файла
							wp_send_json_error( [ 'message' => $upload_result[ 'error' ] ] );
						}
					}
				}
				
				// Теперь у нас есть массив $uploaded_files с загруженными файлами
				$MY_INPUT[ 'uploaded_files' ] = $uploaded_files;
			}
			
			$result = $this->add_report_files( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Files successfully update', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Files not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function update_shipper_info () {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$data = $_POST;
			
			if (!isset($data['pick_up_location_address_id'])) {
				wp_send_json_error( [ 'message' => 'Pick up not fill' ] );
			}
			
			if (!isset($data['delivery_location_address_id'])) {
				wp_send_json_error( [ 'message' => 'Delivery not fill' ] );
			}
			
			$pick_up_location = [];
			$delivery_location = [];

			for ($i = 0; $i < count($data['pick_up_location_address_id']); $i++) {
				$pick_up_location[] = [
					'address_id' => $data['pick_up_location_address_id'][$i],
					'address' => $data['pick_up_location_address'][$i],
					'contact' => $data['pick_up_location_contact'][$i],
					'date' => $data['pick_up_location_date'][$i],
					'info' => $data['pick_up_location_info'][$i],
					'type' => $data['pick_up_location_type'][$i]
				];
			}

			for ($i = 0; $i < count($data['delivery_location_address_id']); $i++) {
				$delivery_location[] = [
					'address_id' => $data['delivery_location_address_id'][$i],
					'address' => $data['delivery_location_address'][$i],
					'contact' => $data['delivery_location_contact'][$i],
					'date' => $data['delivery_location_date'][$i],
					'info' => $data['delivery_location_info'][$i],
					'type' => $data['delivery_location_type'][$i]
				];
			}
			
			$pick_up_location_json = json_encode($pick_up_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$delivery_location_json = json_encode($delivery_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			
			$data['pick_up_location_json'] = $pick_up_location_json;
			$data['delivery_location_json'] = $delivery_location_json;
			$result = $this->add_new_shipper_info( $data );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Shipper info successfully update', 'data' => $data ] );
			}
			
			wp_send_json_error( [ 'message' => 'Shipper not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function add_new_shipper_info($data) {
		global $wpdb;
		
		$post_id = +$data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated'      => $user_id,
			'date_updated'         => current_time( 'mysql' ),
			'pick_up_location'   => $data['pick_up_location_json'],
			'delivery_location'   => $data['delivery_location_json'],
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // customer_id
			'%s',  // date_updated
			'%s',  // pick_up_location
			'%s',  // delivery_location
		), array( '%d' ) );
		
		if ( false === $result ) {
			var_dump("Update failed: " . $wpdb->last_error);
			var_dump("Last query: " . $wpdb->last_query);
		}
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the shipper report in the database: ' . $error );
		}
	}
	
	public function add_new_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"date_booked"         => FILTER_SANITIZE_STRING,
				"dispatcher_initials" => FILTER_SANITIZE_STRING,
				"reference_number"    => FILTER_SANITIZE_STRING,
				"unit_number_name"    => FILTER_SANITIZE_STRING,
				"booked_rate"         => FILTER_SANITIZE_STRING,
				"driver_rate"         => FILTER_SANITIZE_STRING,
				"profit"              => FILTER_SANITIZE_STRING,
				"pick_up_date"        => FILTER_SANITIZE_STRING,
				"load_status"         => FILTER_SANITIZE_STRING,
				"instructions"        => [ 'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY ],
				"source"              => FILTER_SANITIZE_STRING,
				"load_type"           => FILTER_SANITIZE_STRING,
				"commodity"           => FILTER_SANITIZE_STRING,
				"weight"              => FILTER_SANITIZE_STRING,
				"notes"               => FILTER_SANITIZE_STRING,
				"post_id"             => FILTER_SANITIZE_STRING,
			] );
			
			
			$MY_INPUT[ "booked_rate" ] = $this->convert_to_number( $MY_INPUT[ "booked_rate" ] );
			$MY_INPUT[ "driver_rate" ] = $this->convert_to_number( $MY_INPUT[ "driver_rate" ] );
			$MY_INPUT[ "profit" ]      = $this->convert_to_number( $MY_INPUT[ "profit" ] );
			
			$result = $this->add_load( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Report successfully added', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Report not create, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function add_report_files( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		$post_id    = $data[ 'post_id' ];
		
		// Retrieve the current data from the database
		$current_data = $wpdb->get_row( $wpdb->prepare( "SELECT attached_files, attached_file_required FROM $table_name WHERE id = %d", $post_id ), ARRAY_A );
		
		// Prepare the new attached files (append to existing)
		$new_attached_files = ! empty( $data[ 'uploaded_files' ] ) ? implode( ', ', $data[ 'uploaded_files' ] ) : '';
		if ( $new_attached_files && ! empty( $current_data[ 'attached_files' ] ) ) {
			$new_attached_files = $current_data[ 'attached_files' ] . ', ' . $new_attached_files;
		} elseif ( empty( $new_attached_files ) ) {
			$new_attached_files = $current_data[ 'attached_files' ];
		}
		
		// Use the provided attached_file_required if available, otherwise keep the existing one
		$attached_files_required = ! empty( $data[ 'uploaded_file_required' ] )
			? implode( ', ', $data[ 'uploaded_file_required' ] ) : $current_data[ 'attached_file_required' ];
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated'        => $user_id,
			'date_updated'           => current_time( 'mysql' ),
			'attached_files'         => $new_attached_files,
			'attached_file_required' => $attached_files_required
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // attached_files
			'%s'   // attached_file_required
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	public function add_load( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the instructions field
		$instructions = ! empty( $data[ 'instructions' ] ) ? implode( ', ', $data[ 'instructions' ] ) : null;
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated'     => $user_id,
			'date_updated'        => current_time( 'mysql' ),
			'date_booked'         => $data[ 'date_booked' ],
			'dispatcher_initials' => $data[ 'dispatcher_initials' ],
			'reference_number'    => $data[ 'reference_number' ],
			'unit_number_name'    => $data[ 'unit_number_name' ],
			'booked_rate'         => $data[ 'booked_rate' ],
			'driver_rate'         => $data[ 'driver_rate' ],
			'profit'              => $data[ 'profit' ],
			'pick_up_date'        => $data[ 'pick_up_date' ],
			'load_status'         => $data[ 'load_status' ],
			'instructions'        => $instructions,
			'source'              => $data[ 'source' ],
			'load_type'           => $data[ 'load_type' ],
			'commodity'           => $data[ 'commodity' ],
			'weight'              => $data[ 'weight' ],
			'notes'               => $data[ 'notes' ],
		);
		
		// Specify the condition (WHERE clause) - assuming post_id is passed in the data array
		$where = array( 'id' => $data[ 'post_id' ] );
		// Perform the update
		$result = $wpdb->update( $table_name, $update_params, $where, array(
				'%d',  // user_id_updated
				'%s',  // date_updated
				'%s',  // date_booked
				'%s',  // dispatcher_initials
				'%s',  // reference_number
				'%s',  // unit_number_name
				'%f',  // booked_rate
				'%f',  // driver_rate
				'%f',  // profit
				'%s',  // pick_up_date
				'%s',  // load_status
				'%s',  // instructions
				'%s',  // source
				'%s',  // load_type
				'%s',  // commodity
				'%f',  // weight
				'%s',  // notes
			), array( '%d' ) // The data type of the where clause (id is an integer)
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			return false; // Error occurred during the update
		}
	}
	
	public function get_table_items() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;// Замените на имя вашей таблицы
		$per_page   = 10;                               // Количество записей на страницу
		
		// Получаем текущую страницу из параметров URL, если она есть, по умолчанию это страница 1
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		
		// Подсчитываем общее количество записей в таблице
		$total_records = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		
		// Вычисляем смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;
		
		// Запрашиваем записи с учетом разбивки по страницам
		$results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC LIMIT $offset, $per_page", ARRAY_A );
		
		return array(
			'results'       => $results,
			'total_pages'   => $total_pages,
			'current_pages' => $current_page,
		);
	}
	
	public function delete_open_image() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"image-id"     => FILTER_SANITIZE_STRING,
				"image-fields" => FILTER_SANITIZE_STRING,
				"post_id"      => FILTER_SANITIZE_STRING,
			] );
			
			$result = $this->remove_one_image_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Remove success', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error remove in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function update_post_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id"      => FILTER_SANITIZE_STRING,
			] );
			
			$MY_INPUT['post_status'] = 'publish';
			
			$result = $this->update_post_status_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Load successfully loaded', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	public function update_post_status_in_db ($data) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$update_params = array(
			'user_id_updated'     => $user_id,
			'date_updated'        => current_time( 'mysql' ),
			'status_post'         => $data[ 'post_status' ],
		);
		
		// Specify the condition (WHERE clause) - assuming post_id is passed in the data array
		$where = array( 'id' => $data[ 'post_id' ] );
		// Perform the update
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // post_status
		), array( '%d' ) // The data type of the where clause (id is an integer)
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			return false; // Error occurred during the update
		}
	}
	public function remove_one_image_in_db( $data ) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_main;
		// Извлекаем ID изображения и имя поля
		$image_id    = intval( $data[ 'image-id' ] );
		$image_field = sanitize_text_field( $data[ 'image-fields' ] );
		$post_id     = intval( $data[ 'post_id' ] );
		// Проверяем, существует ли такое поле и ID
		if ( ! $image_id || ! $image_field || ! $post_id ) {
			return new WP_Error( 'invalid_input', 'Invalid image ID or field name.' );
		}
		
		$current_value = $wpdb->get_var( $wpdb->prepare( "SELECT $image_field FROM $table_name WHERE id = %s", $post_id ) ); // Замените условия на ваши
		
		if ( $current_value ) {
			// Если поле attached_files, ID хранятся через запятую
			if ( $image_field === 'attached_files' ) {
				$ids = explode( ',', $current_value );
				$ids = array_map( 'intval', $ids );
				
				// Удаляем указанный ID
				$new_ids   = array_diff( $ids, array( $image_id ) );
				$new_value = implode( ',', $new_ids );
			} else if ( $image_field === 'attached_file_required' ) {
				// Если поле attached_file_required, храним только одно значение
				if ( $current_value == $image_id ) {
					$new_value = ''; // Удаляем ID
				} else {
					return new WP_Error( 'id_not_found', 'The specified ID was not found in the field.' );
				}
			} else {
				return new WP_Error( 'invalid_field', 'Invalid field name.' );
			}
			
			$result = $wpdb->update( $table_name, array( $image_field => $new_value ), array( 'id' => $post_id ), array( '%s' ), array( '%s' ) );
			
			$deleted = wp_delete_attachment( $image_id, true );
			
			if ( ! $deleted ) {
				return new WP_Error( 'delete_failed', 'Failed to delete the attachment.' );
			}
			
			if ( $result !== false ) {
				return true; // Успешное обновление
			} else {
				return new WP_Error( 'db_update_failed', 'Failed to update the database.' );
			}
		} else {
			return new WP_Error( 'no_value_found', 'No value found for the specified field.' );
		}
		
	}
	
	public function check_empty_fields($record_id) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		// SQL-запрос для проверки на заполненность полей
		$result = $wpdb->get_row(
			$wpdb->prepare("
            SELECT
                customer_id,
                contact_name,
                contact_phone,
                contact_email,
                date_booked,
                dispatcher_initials,
                reference_number,
                pick_up_location,
                delivery_location,
                unit_number_name,
                booked_rate,
                driver_rate,
                profit,
                pick_up_date,
                load_status,
                load_type,
                additional_contacts,
                attached_file_required
            FROM $table_name
            WHERE id = %d
        ", $record_id), ARRAY_A
		);
	
		// Список обязательных полей для проверки
		$required_fields = [
			'customer_id'           => 'Customer ID',
			'contact_name'          => 'Contact Name',
			'contact_phone'         => 'Contact Phone',
			'contact_email'         => 'Contact Email',
			'date_booked'           => 'Date Booked',
			'dispatcher_initials'   => 'Dispatcher Initials',
			'reference_number'      => 'Reference Number',
			'pick_up_location'      => 'Pick-up Location',
			'delivery_location'     => 'Delivery Location',
			'unit_number_name'      => 'Unit Number',
			'booked_rate'           => 'Booked Rate',
			'driver_rate'           => 'Driver Rate',
			'profit'                => 'Profit',
			'pick_up_date'          => 'Pick-up Date',
			'load_status'           => 'Load Status',
			'load_type'             => 'Load Type',
			'additional_contacts'   => 'Additional Contacts',
			'attached_file_required'=> 'Attached File Required'
		];
		
		$empty_fields = [];
		
		// Проверяем каждое обязательное поле
		foreach ($required_fields as $field => $label) {
			if (empty($result[$field]) || $result[$field] === '0000-00-00' || $result[$field] === '0.00') {
				$empty_fields[] = '<strong>' . $label . '</strong>';
			}
		}
		
		// Возвращаем сообщение о незаполненных полях
		if (!empty($empty_fields)) {
			return array('message' => "The following fields are empty: " . implode(', ', $empty_fields) , 'status' => false) ;
		} else {
			return array('message' => "All required fields are filled." , 'status' => true);
		}
	}
	
	public function rechange_status_load () {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id"      => FILTER_SANITIZE_STRING,
			] );
			
			$post_id = $MY_INPUT["post_id"];
			
			$message_arr = $this->check_empty_fields($post_id);
			
			$status_type = $message_arr['status'];
			$status_message = $message_arr['message'];
			$template = '';
			
			if ($status_type) {
				$template = $this->message_top('success', $status_message, 'js-update-post-status', 'Publish');
			} else {
				$template = $this->message_top('danger', $status_message);
			}
			
			if ( $template ) {
				wp_send_json_success( [ 'template' => $template ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_report', array( $this, 'add_new_report' ) );
		add_action( 'wp_ajax_add_new_draft_report', array( $this, 'add_new_report_draft' ) );
		add_action( 'wp_ajax_update_new_draft_report', array( $this, 'update_new_draft_report' ) );
		add_action( 'wp_ajax_update_files_report', array( $this, 'update_files_report' ) );
		add_action( 'wp_ajax_delete_open_image', array( $this, 'delete_open_image' ) );
		add_action( 'wp_ajax_update_shipper_info', array( $this, 'update_shipper_info' ) );
		add_action( 'wp_ajax_update_post_status', array( $this, 'update_post_status' ) );
		add_action( 'wp_ajax_rechange_status_load', array( $this, 'rechange_status_load' ) );
	}
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table' ) );
		$this->ajax_actions();
	}
}