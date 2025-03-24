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
			'add_driver'                => 'add_driver',
			'update_driver_contact'     => 'update_driver_contact',
			'update_driver_information' => 'update_driver_information',
			'delete_open_image_driver'  => 'delete_open_image_driver',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this, 'need_login' ] );
		}
	}
	
	public function delete_open_image_driver() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"image-id"         => FILTER_SANITIZE_STRING,
				"image-fields"     => FILTER_SANITIZE_STRING,
				"post_id"          => FILTER_SANITIZE_STRING,
				"reference_number" => FILTER_SANITIZE_STRING,
			] );
			$result   = $this->remove_one_image_in_db( $MY_INPUT );
			
			if ( $result === true ) {
				wp_send_json_success( [ 'message' => 'Remove success', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error remove in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function remove_one_image_in_db( $data ) {
		global $wpdb;
		
		$table_meta_name = $wpdb->prefix . $this->table_meta; // Имя таблицы мета данных
		
		// Извлекаем ID изображения и имя мета-ключа
		$image_id    = intval( $data[ 'image-id' ] );
		$image_field = sanitize_text_field( $data[ 'image-fields' ] );
		$post_id     = intval( $data[ 'post_id' ] );
		
		// Проверяем корректность входных данных
		if ( ! $image_id || ! $image_field || ! $post_id ) {
			return new WP_Error( 'invalid_input', 'Invalid image ID, field name or post ID.' );
		}
		
		// Извлекаем текущее значение поля meta_key для поста
		$current_value = $wpdb->get_var( $wpdb->prepare( "
		SELECT meta_value
		FROM $table_meta_name
		WHERE post_id = %d AND meta_key = %s", $post_id, $image_field ) );
		
		if ( $current_value ) {
			$new_value = '';
			
			// Для поля attached_files, где значения хранятся через запятую
			if ( $image_field === 'vehicle_pictures' || $image_field === 'dimensions_pictures' ) {
				$ids = explode( ',', $current_value );
				$ids = array_map( 'intval', $ids );
				// Удаляем указанный ID
				$new_ids   = array_diff( $ids, array( $image_id ) );
				$new_value = implode( ',', $new_ids );
			} elseif ( in_array( $image_field, [
				'registration_file',
				'ppe_file',
				'e_tracks_file',
				'pallet_jack_file',
				'lift_gate_file',
				'dolly_file',
				'ramp_file'
			], true ) ) {
				// Для полей attached_file_required и updated_rate_confirmation
				if ( $current_value == $image_id ) {
					$new_value = ''; // Удаляем значение, если оно совпадает
				} else {
					return new WP_Error( 'id_not_found', 'The specified ID was not found in the field.' );
				}
			} else {
				return new WP_Error( 'invalid_field', 'Invalid field name.' );
			}
			
			// Обновляем запись в таблице мета-данных
			$result = $wpdb->update( $table_meta_name, array( 'meta_value' => $new_value ), array(
				'post_id'  => $post_id,
				'meta_key' => $image_field
			), array( '%s' ),       // Формат для meta_value
				array( '%d', '%s' ) // Форматы для post_id и meta_key
			);
			
			// Удаляем вложение из медиа библиотеки
			$deleted = wp_delete_attachment( $image_id, true );
			
			if ( ! $deleted ) {
				return new WP_Error( 'delete_failed', 'Failed to delete the attachment.' );
			}
			
			// Проверяем результат обновления в базе данных
			if ( $result !== false ) {
				return true; // Успешное обновление
			} else {
				return new WP_Error( 'db_update_failed', 'Failed to update the database.' );
			}
		} else {
			return new WP_Error( 'no_value_found', 'No value found for the specified field.' );
		}
	}
	
	function get_allowed_formats() {
		return array(
			'jpg',
			'jpeg',
			'png',
			'gif',
			'txt',
			'pdf',
			'doc',
			'docx',
			'xls',
			'xml',
			'xlsx',
			'svg',
		);
	}
	
	function multy_upload_files( $fields_name ) {
		if ( ! isset( $_FILES[ $fields_name ] ) || empty( $_FILES[ $fields_name ][ 'name' ][ 0 ] ) ) {
			return []; // No files to upload
		}
		
		$files          = $_FILES[ $fields_name ];
		$uploaded_files = [];
		$errors         = [];
		$user_id        = get_current_user_id();
		
		foreach ( $files[ 'name' ] as $key => $original_name ) {
			if ( empty( $original_name ) ) {
				continue;
			}
			
			// Check for upload errors
			if ( $files[ 'error' ][ $key ] !== UPLOAD_ERR_OK ) {
				$errors[] = "Upload error: " . $original_name;
				continue;
			}
			
			// Validate file type
			$file_info     = pathinfo( $original_name );
			$extension     = isset( $file_info[ 'extension' ] ) ? strtolower( $file_info[ 'extension' ] ) : '';
			$allowed_types = $this->get_allowed_formats();                                          // Allowed formats
			
			if ( ! in_array( $extension, $allowed_types ) ) {
				$errors[] = "Unsupported file format: " . $original_name;
				continue;
			}
			
			// Validate file size (max 50MB)
			
			$max_size = 50 * 1024 * 1024; // 50MB
			
			if ( $files[ 'size' ][ $key ] > $max_size ) {
				$errors[] = "File is too large (max 50MB): " . $original_name;
				continue;
			}
			
			// Generate unique file name: {user_id}_{timestamp}_{random}_{filename}.{extension}
			$timestamp    = time();
			$unique       = rand( 1000, 99999 );
			$new_filename = "{$user_id}_{$timestamp}_{$unique}_" . sanitize_file_name( $file_info[ 'filename' ] );
			if ( ! empty( $extension ) ) {
				$new_filename .= '.' . $extension;
			}
			
			// Prepare file array for upload
			$file = [
				'name'     => $new_filename,
				'type'     => $files[ 'type' ][ $key ],
				'tmp_name' => $files[ 'tmp_name' ][ $key ],
				'error'    => $files[ 'error' ][ $key ],
				'size'     => $files[ 'size' ][ $key ],
			];
			
			// Upload file using wp_handle_upload()
			$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
			
			if ( ! isset( $upload_result[ 'error' ] ) ) {
				// File uploaded successfully, add to media library
				$file_url  = $upload_result[ 'url' ];
				$file_type = $upload_result[ 'type' ];
				$file_path = $upload_result[ 'file' ];
				
				$attachment = [
					'guid'           => $file_url,
					'post_mime_type' => $file_type,
					'post_title'     => basename( $file_url ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				];
				
				$attachment_id = wp_insert_attachment( $attachment, $file_path );
				
				if ( ! is_wp_error( $attachment_id ) ) {
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );
					$uploaded_files[] = $attachment_id;
				} else {
					$errors[] = "Error adding file to media library: " . $original_name;
				}
			} else {
				$errors[] = "Upload failed: " . $upload_result[ 'error' ];
			}
		}
		
		// Return errors if any
		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'message' => $errors ] );
		}
		
		return $uploaded_files;
	}
	
	function upload_one_file( $file ) {
		if ( ! isset( $file ) || empty( $file[ 'size' ] ) ) {
			return false; // No file uploaded
		}
		
		// Validate upload error
		if ( $file[ 'error' ] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( [ 'message' => 'File upload error: ' . $file[ 'error' ] ] );
		}
		
		// Validate file type
		$file_info     = pathinfo( $file[ 'name' ] );
		$extension     = isset( $file_info[ 'extension' ] ) ? strtolower( $file_info[ 'extension' ] ) : '';
		$allowed_types = $this->get_allowed_formats(); // Allowed formats
		
		if ( ! in_array( $extension, $allowed_types ) ) {
			wp_send_json_error( [ 'message' => 'Unsupported file format: ' . $file[ 'name' ] ] );
		}
		
		// Validate file size (max 50MB)
		$max_size = 50 * 1024 * 1024;                  // 50MB
		if ( $file[ 'size' ] > $max_size ) {
			wp_send_json_error( [ 'message' => 'File is too large (max 50MB): ' . $file[ 'name' ] ] );
		}
		
		// Generate unique file name: {user_id}_{timestamp}_{random}_{filename}.{extension}
		$user_id      = get_current_user_id();
		$timestamp    = time();
		$unique       = rand( 1000, 99999 );
		$new_filename = "{$user_id}_{$timestamp}_{$unique}_" . sanitize_file_name( $file_info[ 'filename' ] );
		
		if ( ! empty( $extension ) ) {
			$new_filename .= '.' . $extension;
		}
		
		// Prepare file array for upload
		$file[ 'name' ] = $new_filename;
		
		// Upload file using wp_handle_upload()
		$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
		
		if ( ! isset( $upload_result[ 'error' ] ) ) {
			// File uploaded successfully, add to media library
			$file_url  = $upload_result[ 'url' ];
			$file_type = $upload_result[ 'type' ];
			$file_path = $upload_result[ 'file' ];
			
			$attachment = [
				'guid'           => $file_url,
				'post_mime_type' => $file_type,
				'post_title'     => basename( $file_url ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			];
			
			$attachment_id = wp_insert_attachment( $attachment, $file_path );
			
			if ( ! is_wp_error( $attachment_id ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
				
				return $attachment_id; // Return uploaded file ID
			} else {
				wp_send_json_error( [ 'message' => 'Error adding file to media library' ] );
			}
		} else {
			wp_send_json_error( [ 'message' => 'Upload failed: ' . $upload_result[ 'error' ] ] );
		}
		
		return false;
	}
	
	public function get_drivers() { }
	
	public function get_driver_by_id( $ID ) {
		global $wpdb;
		
		$table_main = "{$wpdb->prefix}{$this->table_main}";
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		// SQL запрос для получения данных из основной таблицы и мета-данных
		$query = $wpdb->prepare( "
        SELECT main.*, meta.meta_key, meta.meta_value
        FROM $table_main AS main
        LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
        WHERE main.id = %d
    ", $ID );
		
		// Выполняем запрос
		$results = $wpdb->get_results( $query );
		// Преобразуем результаты, чтобы сгруппировать мета-данные
		if ( ! empty( $results ) ) {
			$report = array(
				'main' => array(),
				'meta' => array()
			);
			
			foreach ( $results as $row ) {
				// Заполняем основную информацию только один раз
				if ( empty( $report[ 'main' ] ) ) {
					$report[ 'main' ] = (array) $row;
					unset( $report[ 'main' ][ 'meta_key' ], $report[ 'main' ][ 'meta_value' ] );
				}
				
				// Добавляем мета-данные в массив
				if ( $row->meta_key && $row->meta_value ) {
					$report[ 'meta' ][ $row->meta_key ] = $row->meta_value;
				}
			}
			
			return $report;
		}
		
		return null; // Если нет результатов
	}
	
	public function add_driver() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize incoming data
			$data = array(
				'driver_name'                => isset( $_POST[ 'driver_name' ] )
					? sanitize_text_field( $_POST[ 'driver_name' ] ) : '',
				'driver_phone'               => isset( $_POST[ 'driver_phone' ] )
					? sanitize_text_field( $_POST[ 'driver_phone' ] ) : '',
				'driver_email'               => isset( $_POST[ 'driver_email' ] )
					? sanitize_email( $_POST[ 'driver_email' ] ) : '',
				'home_location'              => isset( $_POST[ 'home_location' ] )
					? sanitize_text_field( $_POST[ 'home_location' ] ) : '',
				'dob'                        => isset( $_POST[ 'dob' ] ) ? sanitize_text_field( $_POST[ 'dob' ] ) : '',
				// Additional date validation might be required
				'macro_point'                => isset( $_POST[ 'macro_point' ] )
					? sanitize_text_field( $_POST[ 'macro_point' ] ) : '',
				'trucker_tools'              => isset( $_POST[ 'trucker_tools' ] )
					? sanitize_text_field( $_POST[ 'trucker_tools' ] ) : '',
				'languages'                  => isset( $_POST[ 'language' ] ) && is_array( $_POST[ 'language' ] )
					? implode( ',', array_map( 'sanitize_text_field', $_POST[ 'language' ] ) ) : '',
				'team_driver_enabled'        => isset( $_POST[ 'team_driver_enabled' ] )
					? sanitize_text_field( $_POST[ 'team_driver_enabled' ] ) : '',
				'team_driver_name'           => isset( $_POST[ 'team_driver_name' ] )
					? sanitize_text_field( $_POST[ 'team_driver_name' ] ) : '',
				'team_driver_phone'          => isset( $_POST[ 'team_driver_phone' ] )
					? sanitize_text_field( $_POST[ 'team_driver_phone' ] ) : '',
				'team_driver_email'          => isset( $_POST[ 'team_driver_email' ] )
					? sanitize_email( $_POST[ 'team_driver_email' ] ) : '',
				'team_driver_dob'            => isset( $_POST[ 'team_driver_dob' ] )
					? sanitize_text_field( $_POST[ 'team_driver_dob' ] ) : '',
				'team_driver_macro_point'    => isset( $_POST[ 'team_driver_macro_point' ] )
					? sanitize_text_field( $_POST[ 'team_driver_macro_point' ] ) : '',
				'team_driver_trucker_tools'  => isset( $_POST[ 'team_driver_trucker_tools' ] )
					? sanitize_text_field( $_POST[ 'team_driver_trucker_tools' ] ) : '',
				// Additional date validation might be required
				'owner_enabled'              => isset( $_POST[ 'owner_enabled' ] )
					? sanitize_text_field( $_POST[ 'owner_enabled' ] ) : '',
				'owner_name'                 => isset( $_POST[ 'owner_name' ] )
					? sanitize_text_field( $_POST[ 'owner_name' ] ) : '',
				'owner_phone'                => isset( $_POST[ 'owner_phone' ] )
					? sanitize_text_field( $_POST[ 'owner_phone' ] ) : '',
				'owner_email'                => isset( $_POST[ 'owner_email' ] )
					? sanitize_email( $_POST[ 'owner_email' ] ) : '',
				'owner_dob'                  => isset( $_POST[ 'owner_dob' ] )
					? sanitize_text_field( $_POST[ 'owner_dob' ] ) : '',
				'owner_macro_point'          => isset( $_POST[ 'owner_macro_point' ] )
					? sanitize_text_field( $_POST[ 'owner_macro_point' ] ) : '',
				'owner_trucker_tools'        => isset( $_POST[ 'owner_trucker_tools' ] )
					? sanitize_text_field( $_POST[ 'owner_trucker_tools' ] ) : '',
				// Additional date validation might be required
				'owner_type'                 => isset( $_POST[ 'owner_type' ] )
					? sanitize_text_field( $_POST[ 'owner_type' ] ) : '',
				'emergency_contact_name'     => isset( $_POST[ 'emergency_contact_name' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_name' ] ) : '',
				'emergency_contact_phone'    => isset( $_POST[ 'emergency_contact_phone' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_phone' ] ) : '',
				'emergency_contact_relation' => isset( $_POST[ 'emergency_contact_relation' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_relation' ] ) : '',
			);
			
			// At this point, the data is sanitized and ready for further processing or saving to the database
			$result = $this->add_driver_in_db( $data );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Driver successfully added', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Report not create, error add in database' ] );
		}
	}
	
	public function update_driver_contact() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize incoming data
			$data = array(
				'driver_id'                  => isset( $_POST[ 'driver_id' ] )
					? sanitize_text_field( $_POST[ 'driver_id' ] ) : '',
				'driver_name'                => isset( $_POST[ 'driver_name' ] )
					? sanitize_text_field( $_POST[ 'driver_name' ] ) : '',
				'driver_phone'               => isset( $_POST[ 'driver_phone' ] )
					? sanitize_text_field( $_POST[ 'driver_phone' ] ) : '',
				'driver_email'               => isset( $_POST[ 'driver_email' ] )
					? sanitize_email( $_POST[ 'driver_email' ] ) : '',
				'home_location'              => isset( $_POST[ 'home_location' ] )
					? sanitize_text_field( $_POST[ 'home_location' ] ) : '',
				'dob'                        => isset( $_POST[ 'dob' ] ) ? sanitize_text_field( $_POST[ 'dob' ] ) : '',
				// Additional date validation might be required
				'macro_point'                => isset( $_POST[ 'macro_point' ] )
					? sanitize_text_field( $_POST[ 'macro_point' ] ) : '',
				'trucker_tools'              => isset( $_POST[ 'trucker_tools' ] )
					? sanitize_text_field( $_POST[ 'trucker_tools' ] ) : '',
				'languages'                  => isset( $_POST[ 'language' ] ) && is_array( $_POST[ 'language' ] )
					? implode( ',', array_map( 'sanitize_text_field', $_POST[ 'language' ] ) ) : '',
				'team_driver_enabled'        => isset( $_POST[ 'team_driver_enabled' ] )
					? sanitize_text_field( $_POST[ 'team_driver_enabled' ] ) : '',
				'team_driver_name'           => isset( $_POST[ 'team_driver_name' ] )
					? sanitize_text_field( $_POST[ 'team_driver_name' ] ) : '',
				'team_driver_phone'          => isset( $_POST[ 'team_driver_phone' ] )
					? sanitize_text_field( $_POST[ 'team_driver_phone' ] ) : '',
				'team_driver_email'          => isset( $_POST[ 'team_driver_email' ] )
					? sanitize_email( $_POST[ 'team_driver_email' ] ) : '',
				'team_driver_dob'            => isset( $_POST[ 'team_driver_dob' ] )
					? sanitize_text_field( $_POST[ 'team_driver_dob' ] ) : '',
				'team_driver_macro_point'    => isset( $_POST[ 'team_driver_macro_point' ] )
					? sanitize_text_field( $_POST[ 'team_driver_macro_point' ] ) : '',
				'team_driver_trucker_tools'  => isset( $_POST[ 'team_driver_trucker_tools' ] )
					? sanitize_text_field( $_POST[ 'team_driver_trucker_tools' ] ) : '',
				// Additional date validation might be required
				'owner_enabled'              => isset( $_POST[ 'owner_enabled' ] )
					? sanitize_text_field( $_POST[ 'owner_enabled' ] ) : '',
				'owner_name'                 => isset( $_POST[ 'owner_name' ] )
					? sanitize_text_field( $_POST[ 'owner_name' ] ) : '',
				'owner_phone'                => isset( $_POST[ 'owner_phone' ] )
					? sanitize_text_field( $_POST[ 'owner_phone' ] ) : '',
				'owner_email'                => isset( $_POST[ 'owner_email' ] )
					? sanitize_email( $_POST[ 'owner_email' ] ) : '',
				'owner_dob'                  => isset( $_POST[ 'owner_dob' ] )
					? sanitize_text_field( $_POST[ 'owner_dob' ] ) : '',
				'owner_macro_point'          => isset( $_POST[ 'owner_macro_point' ] )
					? sanitize_text_field( $_POST[ 'owner_macro_point' ] ) : '',
				'owner_trucker_tools'        => isset( $_POST[ 'owner_trucker_tools' ] )
					? sanitize_text_field( $_POST[ 'owner_trucker_tools' ] ) : '',
				// Additional date validation might be required
				'owner_type'                 => isset( $_POST[ 'owner_type' ] )
					? sanitize_text_field( $_POST[ 'owner_type' ] ) : '',
				'emergency_contact_name'     => isset( $_POST[ 'emergency_contact_name' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_name' ] ) : '',
				'emergency_contact_phone'    => isset( $_POST[ 'emergency_contact_phone' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_phone' ] ) : '',
				'emergency_contact_relation' => isset( $_POST[ 'emergency_contact_relation' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_relation' ] ) : '',
			);
			
			// At this point, the data is sanitized and ready for further processing or saving to the database
			$result = $this->update_driver_in_db( $data );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Driver successfully added', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Driver not update, error add in database' ] );
		}
	}
	
	public function update_driver_information() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize incoming data
			$data = array(
				'driver_id'               => isset( $_POST[ 'driver_id' ] )
					? sanitize_text_field( $_POST[ 'driver_id' ] ) : '',
				'vehicle_type'            => sanitize_text_field( get_field_value( $_POST, 'vehicle_type' ) ),
				'vehicle_make'            => sanitize_text_field( get_field_value( $_POST, 'vehicle_make' ) ),
				'vehicle_model'           => sanitize_text_field( get_field_value( $_POST, 'vehicle_model' ) ),
				'vehicle_year'            => sanitize_text_field( get_field_value( $_POST, 'vehicle_year' ) ),
				'gvwr'                    => sanitize_text_field( get_field_value( $_POST, 'gvwr' ) ),
				'payload'                 => sanitize_text_field( get_field_value( $_POST, 'payload' ) ),
				'dimensions'              => sanitize_text_field( get_field_value( $_POST, 'dimensions_1' ) . ' / ' . get_field_value( $_POST, 'dimensions_2' ) . ' / ' . get_field_value( $_POST, 'dimensions_3' ) ),
				'vin'                     => sanitize_text_field( get_field_value( $_POST, 'vin' ) ),
				'registration_type'       => sanitize_text_field( get_field_value( $_POST, 'registration_type' ) ),
				'registration_status'     => sanitize_text_field( get_field_value( $_POST, 'registration_status' ) ),
				'registration_expiration' => sanitize_text_field( get_field_value( $_POST, 'registration_expiration' ) ),
				'plates'                  => sanitize_text_field( get_field_value( $_POST, 'plates' ) ),
				'plates_status'           => sanitize_text_field( get_field_value( $_POST, 'plates_status' ) ),
				'plates_expiration'       => sanitize_text_field( get_field_value( $_POST, 'plates_expiration' ) ),
				'ppe'                     => sanitize_text_field( get_field_value( $_POST, 'ppe' ) ),
				'e_tracks'                => sanitize_text_field( get_field_value( $_POST, 'e_tracks' ) ),
				'pallet_jack'             => sanitize_text_field( get_field_value( $_POST, 'pallet_jack' ) ),
				'lift_gate'               => sanitize_text_field( get_field_value( $_POST, 'lift_gate' ) ),
				'dolly'                   => sanitize_text_field( get_field_value( $_POST, 'dolly' ) ),
				'load_bars'               => sanitize_text_field( get_field_value( $_POST, 'load_bars' ) ),
				'ramp'                    => sanitize_text_field( get_field_value( $_POST, 'ramp' ) ),
				'printer'                 => sanitize_text_field( get_field_value( $_POST, 'printer' ) ),
				'sleeper'                 => sanitize_text_field( get_field_value( $_POST, 'sleeper' ) ),
			);
			
			global $wpdb;
			$table_meta_name = $wpdb->prefix . $this->table_meta;
			$meta_data       = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta_name} WHERE post_id = %d", $data[ 'driver_id' ] ), ARRAY_A );
			$current_data    = array_column( $meta_data, 'meta_value', 'meta_key' );
			
			
			if ( ! empty( $_FILES[ 'vehicle_pictures' ] ) ) {
				$data[ 'vehicle_pictures' ] = $this->process_uploaded_files( 'vehicle_pictures', $current_data[ 'vehicle_pictures' ] );
			}
			
			if ( ! empty( $_FILES[ 'dimensions_pictures' ] ) ) {
				$data[ 'dimensions_pictures' ] = $this->process_uploaded_files( 'dimensions_pictures', $current_data[ 'dimensions_pictures' ] );
			}
			
			
			$keys_names = array(
				'registration_file',
				'ppe_file',
				'e_tracks_file',
				'pallet_jack_file',
				'lift_gate_file',
				'dolly_file',
				'ramp_file'
			);
			
			foreach ( $keys_names as $key_name ) {
				if ( ! empty( $_FILES[ $key_name ] ) ) {
					$id_uploaded       = $this->upload_one_file( $_FILES[ $key_name ] );
					$data[ $key_name ] = is_numeric( $id_uploaded ) ? $id_uploaded : '';
				}
			}
			
			
			// At this point, the data is sanitized and ready for further processing or saving to the database
			$result = $this->update_driver_in_db( $data );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Driver successfully added', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Driver not update, error add in database' ] );
		}
	}
	
	private function process_uploaded_files( $field_name, $current_data_key ) {
		$uploaded_files = $this->multy_upload_files( $field_name );
		
		$new_files = ! empty( $uploaded_files ) ? implode( ', ', $uploaded_files ) : '';
		if ( $new_files && ! empty( $current_data_key ) ) {
			$new_files = $current_data_key . ', ' . $new_files;
		} elseif ( empty( $new_files ) ) {
			$new_files = $current_data_key;
		}
		
		return $new_files;
	}
	
	public function add_driver_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		$user_id = get_current_user_id();
		
		$data_main[ 'user_id_added' ]   = $user_id;
		$data_main[ 'date_created' ]    = current_time( 'mysql' );
		$data_main[ 'user_id_updated' ] = $user_id;
		$data_main[ 'date_updated' ]    = current_time( 'mysql' );
		$data_main[ 'status_post' ]     = 'draft';
		
		$insert_result = $wpdb->insert( $table_name, $data_main );
		
		if ( $insert_result ) {
			$driver_id = $wpdb->insert_id;
			
			if ( $this->update_post_meta_data( $driver_id, $data ) ) {
				return $driver_id;
			}
		}
		
		return false;
	}
	
	public function update_driver_in_db( $data = [] ) {
		global $wpdb;
		
		
		if ( empty( $data ) ) {
			return false;
		}
		
		$driver_id = $data[ 'driver_id' ];
		
		if ( ! $driver_id ) {
			return false;
		}
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		$user_id = get_current_user_id();
		
		$data_main[ 'user_id_updated' ] = $user_id;
		$data_main[ 'date_updated' ]    = current_time( 'mysql' );
		
		$update_result = $wpdb->update( $table_name, $data_main, array( 'id' => $driver_id ) );
		
		if ( $update_result !== false ) {
			if ( $this->update_post_meta_data( $driver_id, $data ) ) {
				return $driver_id;
			}
		}
		
		return false;
	}
	
	
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