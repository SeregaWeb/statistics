<?php

class TMSDrivers extends TMSDriversHelper {
	
	public $table_main     = 'drivers';
	public $table_meta     = 'drivers_meta';
	public $per_page_loads = 100;
	public $hold_time      = 15;
	
	public $log_controller = false;
	
	public function __construct() {
		$this->log_controller = new TMSLogs();
	}
	
	public function init() {
		$this->ajax_actions();
		$this->create_tables();
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_driver'                => 'add_driver',
			'update_driver_contact'     => 'update_driver_contact',
			'update_driver_information' => 'update_driver_information',
			'update_driver_finance'     => 'update_driver_finance',
			'delete_open_image_driver'  => 'delete_open_image_driver',
			'update_driver_document'    => 'update_driver_document',
			'update_driver_status'      => 'update_driver_status',
			'remove_one_driver'         => 'remove_one_driver',
			'upload_driver_helper'      => 'upload_driver_helper',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this, 'need_login' ] );
		}
	}
	
	public function remove_one_driver() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			// Получаем данные запроса
			$MY_INPUT = filter_var_array( $_POST, [
				"id_driver" => FILTER_SANITIZE_STRING,
			] );
			
			$id_load    = $MY_INPUT[ "id_driver" ];
			$table_name = $wpdb->prefix . $this->table_main;
			$table_meta = $wpdb->prefix . $this->table_meta;
			
			// Получаем метаданные
			$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta} WHERE post_id = %d", $id_load ), ARRAY_A );
			
			// Удаляем файлы из метаданных
			foreach ( $meta_data as $meta ) {
				if ( in_array( $meta[ 'meta_key' ], [
					'plates_file',
					'vehicle_pictures',
					'dimensions_pictures',
					'registration_file',
					'ppe_file',
					'gvwr_placard',
					'e_tracks_file',
					'pallet_jack_file',
					'lift_gate_file',
					'dolly_file',
					'ramp_file',
					'payment_file',
					'w9_file',
					'ssn_file',
					'ein_file',
					'nec_file',
					'hazmat_certificate_file',
					'driving_record',
					'driver_licence',
					'legal_document',
					'twic_file',
					'tsa_file',
					'motor_cargo_coi',
					'auto_liability_coi',
					'ic_agreement',
					'change_9_file',
					'canada_transition_file',
					'immigration_file',
					'background_file',
				] ) ) {
					// Если это множественные файлы (attached_files), разбиваем на массив
					$files = explode( ',', $meta[ 'meta_value' ] );
					foreach ( $files as $file_id ) {
						if ( ! empty( $file_id ) ) {
							// Удаляем вложение по его ID
							wp_delete_attachment( $file_id, true );
						}
					}
				}
			}
			
			// Удаляем метаданные
			$wpdb->delete( $table_meta, [ 'post_id' => $id_load ] );
			
			// Удаляем запись из основной таблицы
			$wpdb->delete( $table_name, [ 'id' => $id_load ] );
			
			wp_send_json_success( [ 'message' => 'Load and associated files removed successfully' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function set_filter_params( $args ) {
		$my_search  = trim( get_field_value( $_GET, 'my_search' ) );
		$recruiter  = trim( get_field_value( $_GET, 'recruiter' ) );
		$year       = trim( get_field_value( $_GET, 'fyear' ) );
		$month      = trim( get_field_value( $_GET, 'fmonth' ) );
		$source     = trim( get_field_value( $_GET, 'source' ) );
		$additional = trim( get_field_value( $_GET, 'additional' ) );
		
		if ( $my_search ) {
			$args[ 'my_search' ] = $my_search;
		}
		
		if ( $recruiter ) {
			$args[ 'recruiter' ] = $recruiter;
		}
		if ( $additional ) {
			$args[ 'additional' ] = $additional;
		}
		if ( $year ) {
			$args[ 'year' ] = $year;
		}
		
		if ( $month ) {
			$args[ 'month' ] = $month;
		}
		
		if ( $source ) {
			$args[ 'source' ] = $source;
		}
		
		
		return $args;
	}
	
	public function get_table_items( $args = array() ) {
		global $wpdb;
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'id';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) == 'asc' ? 'ASC'
			: 'DESC';
		
		$join_builder = "
	FROM $table_main AS main
	LEFT JOIN $table_meta AS driver_name
		ON main.id = driver_name.post_id AND driver_name.meta_key = 'driver_name'
	LEFT JOIN $table_meta AS driver_phone
		ON main.id = driver_phone.post_id AND driver_phone.meta_key = 'driver_phone'
	LEFT JOIN $table_meta AS driver_email
		ON main.id = driver_email.post_id AND driver_email.meta_key = 'driver_email'
	LEFT JOIN $table_meta AS plates
		ON main.id = plates.post_id AND plates.meta_key = 'plates'
	LEFT JOIN $table_meta AS entity_name
		ON main.id = entity_name.post_id AND entity_name.meta_key = 'entity_name'
	LEFT JOIN $table_meta AS vin
		ON main.id = vin.post_id AND vin.meta_key = 'vin'
	LEFT JOIN $table_meta AS auto_liability_insurer
		ON main.id = auto_liability_insurer.post_id AND auto_liability_insurer.meta_key = 'auto_liability_insurer'
	LEFT JOIN $table_meta AS motor_cargo_insurer
		ON main.id = motor_cargo_insurer.post_id AND motor_cargo_insurer.meta_key = 'motor_cargo_insurer'
	LEFT JOIN $table_meta AS source
		ON main.id = source.post_id AND source.meta_key = 'source'
";
		
		$where_conditions = array();
		$where_values     = array();
		
		// Дополнительный LEFT JOIN по переданному полю
		if ( ! empty( $args[ 'additional' ] ) ) {
			$additional_key   = sanitize_key( $args[ 'additional' ] );
			$additional_alias = 'add_' . $additional_key;
			
			// Добавляем JOIN прямо в $join_builder
			$join_builder       .= "
		LEFT JOIN $table_meta AS {$additional_alias}
			ON main.id = {$additional_alias}.post_id
			AND {$additional_alias}.meta_key = %s
	";
			$where_conditions[] = "({$additional_alias}.meta_value IS NOT NULL AND {$additional_alias}.meta_value != '')";
			$where_values[]     = $additional_key;
		}
		
		
		// Основной запрос
		$sql = "SELECT main.*" . $join_builder . " WHERE 1=1";
		
		
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = "main.status_post = %s";
			$where_values[]     = $args[ 'status_post' ];
		}
		
		if ( ! empty( $args[ 'recruiter' ] ) ) {
			$where_conditions[] = "main.user_id_added = %s";
			$where_values[]     = $args[ 'recruiter' ];
		}
		
		if ( ! empty( $args[ 'source' ] ) ) {
			$where_conditions[] = "source.meta_value = %s";
			$where_values[]     = $args[ 'source' ];
		}
		
		
		// Фильтрация по reference_number
		//		motor_cargo_insurer auto_liability_insurer entity_name plates
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(" . "main.id LIKE %s OR " . "driver_name.meta_value LIKE %s OR " . "driver_phone.meta_value LIKE %s OR " . "driver_email.meta_value LIKE %s OR " . "motor_cargo_insurer.meta_value LIKE %s OR " . "auto_liability_insurer.meta_value LIKE %s OR " . "entity_name.meta_value LIKE %s OR " . "plates.meta_value LIKE %s OR " . "vin.meta_value LIKE %s " . ")";
			
			$search_value = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			for ( $i = 0; $i < 9; $i ++ ) {
				$where_values[] = $search_value;
			}
		}
		
		if ( ! empty( $args[ 'month' ] ) && ! empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "main.date_created IS NOT NULL
        AND YEAR(main.date_created) = %d
        AND MONTH(main.date_created) = %d";
			$where_values[]     = $args[ 'year' ];
			$where_values[]     = $args[ 'month' ];
		}
		
		// Фильтрация по только году
		if ( ! empty( $args[ 'year' ] ) && empty( $args[ 'month' ] ) ) {
			$where_conditions[] = "main.date_created IS NOT NULL
        AND YEAR(main.date_created) = %d";
			$where_values[]     = $args[ 'year' ];
		}
		
		// Фильтрация по только месяцу
		if ( ! empty( $args[ 'month' ] ) && empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "main.date_created IS NOT NULL
        AND MONTH(main.date_created) = %d";
			$where_values[]     = $args[ 'month' ];
		}
		
		
		// Применяем фильтры к запросу
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Подсчёт общего количества записей с учётом фильтров
		$total_records_sql = "SELECT COUNT(*)" . $join_builder;
		if ( ! empty( $where_conditions ) ) {
			$total_records_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		
		// Смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;
		
		// Добавляем сортировку и лимит для текущей страницы
		$sql            .= " ORDER BY main.$sort_by $sort_order LIMIT %d, %d";
		$where_values[] = $offset;
		$where_values[] = $per_page;
		
		// Выполняем запрос
		$main_results = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ), ARRAY_A );
		// Собираем все ID записей для получения дополнительных метаданных
		$post_ids = wp_list_pluck( $main_results, 'id' );
		
		// Если есть записи, получаем метаданные
		$meta_data = array();
		if ( ! empty( $post_ids ) ) {
			$meta_sql     = "SELECT post_id, meta_key, meta_value
					 FROM $table_meta
					 WHERE post_id IN (" . implode( ',', array_map( 'absint', $post_ids ) ) . ")";
			$meta_results = $wpdb->get_results( $meta_sql, ARRAY_A );
			
			// Преобразуем метаданные в ассоциативный массив по post_id
			foreach ( $meta_results as $meta_row ) {
				$post_id = $meta_row[ 'post_id' ];
				if ( ! isset( $meta_data[ $post_id ] ) ) {
					$meta_data[ $post_id ] = array();
				}
				$meta_data[ $post_id ][ $meta_row[ 'meta_key' ] ] = $meta_row[ 'meta_value' ];
			}
		}
		
		if ( is_array( $main_results ) && ! empty( $main_results ) ) {
			// Объединяем основную таблицу с метаданными
			foreach ( $main_results as &$result ) {
				$post_id               = $result[ 'id' ];
				$result[ 'meta_data' ] = isset( $meta_data[ $post_id ] ) ? $meta_data[ $post_id ] : array();
			}
		}
		
		return array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
	}
	
	public function update_driver_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
			] );
			
			$MY_INPUT[ 'post_status' ] = 'publish';
			
			$result = $this->update_driver_status_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Published', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function update_driver_status_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'status_post'     => $data[ 'post_status' ],
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
				'plates_file',
				'registration_file',
				'ppe_file',
				'gvwr_placard',
				'e_tracks_file',
				'pallet_jack_file',
				'lift_gate_file',
				'dolly_file',
				'ramp_file',
				'payment_file',
				'w9_file',
				'ssn_file',
				'ein_file',
				'nec_file',
				'hazmat_certificate_file',
				'driving_record',
				'driver_licence',
				'legal_document',
				'twic_file',
				'tsa_file',
				'motor_cargo_coi',
				'auto_liability_coi',
				'ic_agreement',
				'change_9_file',
				'canada_transition_file',
				'immigration_file',
				'background_file',
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
				
				$changes = '<strong>' . $this->format_field_name( $image_field ) . '</strong> <span style="color:red">removed</span>';
				
				if ( ! empty( $changes ) ) {
					$user_id = get_current_user_id();
					$this->log_controller->create_one_log( array(
						'user_id'   => $user_id,
						'post_id'   => $post_id,
						'message'   => $changes,
						'post_type' => 'driver',
					) );
				}
				
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
				'city'                       => isset( $_POST[ 'city' ] ) ? sanitize_text_field( $_POST[ 'city' ] )
					: '',
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
				'mc_enabled'                 => isset( $_POST[ 'mc_enabled' ] )
					? sanitize_text_field( $_POST[ 'mc_enabled' ] ) : '',
				'mc'                         => isset( $_POST[ 'mc' ] ) ? sanitize_text_field( $_POST[ 'mc' ] ) : '',
				'dot_enabled'                => isset( $_POST[ 'dot_enabled' ] )
					? sanitize_text_field( $_POST[ 'dot_enabled' ] ) : '',
				'dot'                        => isset( $_POST[ 'dot' ] ) ? sanitize_text_field( $_POST[ 'dot' ] ) : '',
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
				'source'                     => isset( $_POST[ 'source' ] ) ? sanitize_text_field( $_POST[ 'source' ] )
					: '',
				'recruiter_add'              => isset( $_POST[ 'recruiter_add' ] )
					? sanitize_text_field( $_POST[ 'recruiter_add' ] ) : '',
				'preferred_distance'         => isset( $_POST[ 'preferred_distance' ] ) && is_array( $_POST[ 'preferred_distance' ] )
					? implode( ',', array_map( 'sanitize_text_field', $_POST[ 'preferred_distance' ] ) ) : '',
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
				
				$user_id = get_current_user_id();
				$this->log_controller->create_one_log( array(
					'user_id'   => $user_id,
					'post_id'   => $data[ 'driver_id' ],
					'message'   => "<strong>Driver's profile has been created</div>",
					'post_type' => 'driver',
				) );
				
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
				'preferred_distance'         => isset( $_POST[ 'preferred_distance' ] ) && is_array( $_POST[ 'preferred_distance' ] )
					? implode( ',', array_map( 'sanitize_text_field', $_POST[ 'preferred_distance' ] ) ) : '',
				'home_location'              => isset( $_POST[ 'home_location' ] )
					? sanitize_text_field( $_POST[ 'home_location' ] ) : '',
				'city'                       => isset( $_POST[ 'city' ] ) ? sanitize_text_field( $_POST[ 'city' ] )
					: '',
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
				'recruiter_add'              => isset( $_POST[ 'recruiter_add' ] )
					? sanitize_text_field( $_POST[ 'recruiter_add' ] ) : '',
				'mc_enabled'                 => isset( $_POST[ 'mc_enabled' ] )
					? sanitize_text_field( $_POST[ 'mc_enabled' ] ) : '',
				'mc'                         => isset( $_POST[ 'mc' ] ) ? sanitize_text_field( $_POST[ 'mc' ] ) : '',
				'dot_enabled'                => isset( $_POST[ 'dot_enabled' ] )
					? sanitize_text_field( $_POST[ 'dot_enabled' ] ) : '',
				'dot'                        => isset( $_POST[ 'dot' ] ) ? sanitize_text_field( $_POST[ 'dot' ] ) : '',
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
				'source'                     => isset( $_POST[ 'source' ] ) ? sanitize_text_field( $_POST[ 'source' ] )
					: '',
				'owner_type'                 => isset( $_POST[ 'owner_type' ] )
					? sanitize_text_field( $_POST[ 'owner_type' ] ) : '',
				'emergency_contact_name'     => isset( $_POST[ 'emergency_contact_name' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_name' ] ) : '',
				'emergency_contact_phone'    => isset( $_POST[ 'emergency_contact_phone' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_phone' ] ) : '',
				'emergency_contact_relation' => isset( $_POST[ 'emergency_contact_relation' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_relation' ] ) : '',
			);
			
			
			$driver_object = $this->get_driver_by_id( $data[ 'driver_id' ] );
			$meta          = get_field_value( $driver_object, 'meta' );
			$main          = get_field_value( $driver_object, 'main' );
			$post_status   = $main[ 'status_post' ];
			
			$array_track = array(
				'driver_name',
				'driver_phone',
				'driver_email',
				'home_location',
				'city',
				'dob',
				'languages',
				'team_driver_name',
				'team_driver_phone',
				'team_driver_email',
				'team_driver_dob',
				'owner_name',
				'owner_phone',
				'owner_email',
				'owner_dob',
				'emergency_contact_name',
				'emergency_contact_phone',
				'mc',
				'dot',
				'macro_point',
				'trucker_tools',
				'source',
			);
			
			if ( $post_status === 'publish' ) {
				$changes = $this->get_log_template( $array_track, $meta, $data );
			}
			// At this point, the data is sanitized and ready for further processing or saving to the database
			$result = $this->update_driver_in_db( $data );
			
			if ( $result ) {
				
				if ( ! empty( $changes ) && $post_status === 'publish' ) {
					$user_id = get_current_user_id();
					$this->log_controller->create_one_log( array(
						'user_id'   => $user_id,
						'post_id'   => $data[ 'driver_id' ],
						'message'   => $changes,
						'post_type' => 'driver',
					) );
				}
				
				wp_send_json_success( [ 'message' => 'Driver successfully added', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Driver not update, error add in database' ] );
		}
	}
	
	public function update_driver_finance() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize incoming data
			$data = array(
				'driver_id'           => isset( $_POST[ 'driver_id' ] ) ? sanitize_text_field( $_POST[ 'driver_id' ] )
					: '',
				'account_type'        => isset( $_POST[ 'account_type' ] )
					? sanitize_text_field( $_POST[ 'account_type' ] ) : '',
				'account_name'        => isset( $_POST[ 'account_name' ] )
					? sanitize_text_field( $_POST[ 'account_name' ] ) : '',
				'payment_instruction' => isset( $_POST[ 'payment_instruction' ] )
					? sanitize_text_field( $_POST[ 'payment_instruction' ] ) : '',
				
				'w9_classification' => isset( $_POST[ 'w9_classification' ] )
					? sanitize_text_field( $_POST[ 'w9_classification' ] ) : '',
				
				'address'        => isset( $_POST[ 'address' ] ) ? sanitize_text_field( $_POST[ 'address' ] ) : '',
				'city_state_zip' => isset( $_POST[ 'city_state_zip' ] )
					? sanitize_text_field( $_POST[ 'city_state_zip' ] ) : '',
				'ssn'            => isset( $_POST[ 'ssn' ] ) ? sanitize_text_field( $_POST[ 'ssn' ] ) : '',
				'ssn_name'       => isset( $_POST[ 'ssn_name' ] ) ? sanitize_text_field( $_POST[ 'ssn_name' ] ) : '',
				
				'entity_name' => isset( $_POST[ 'entity_name' ] ) ? sanitize_text_field( $_POST[ 'entity_name' ] ) : '',
				'ein'         => isset( $_POST[ 'ein' ] ) ? sanitize_text_field( $_POST[ 'ein' ] ) : '',
				
				'authorized_email' => isset( $_POST[ 'authorized_email' ] )
					? sanitize_email( $_POST[ 'authorized_email' ] ) : '',
				'ein_file_id'      => isset( $_POST[ 'ein_file_id' ] ) ? sanitize_text_field( $_POST[ 'ein_file_id' ] )
					: '',
				'ssn_file_id'      => isset( $_POST[ 'ssn_file_id' ] ) ? sanitize_text_field( $_POST[ 'ssn_file_id' ] )
					: '',
			);
			// At this point, the data is sanitized and ready for further processing or saving to the database
			
			$driver_object = $this->get_driver_by_id( $data[ 'driver_id' ] );
			$meta          = get_field_value( $driver_object, 'meta' );
			
			$main        = get_field_value( $driver_object, 'main' );
			$post_status = $main[ 'status_post' ];
			
			
			$array_track = array(
				'account_type',
				'account_name',
				'payment_instruction',
				'w9_classification',
				'address',
				'city_state_zip',
				'ssn',
				'ssn_name',
				'entity_name',
				'ein',
				'authorized_email',
			);
			
			if ( $post_status === 'publish' ) {
				// Переменная для хранения результатов изменений
				$changes = $this->get_log_template( $array_track, $meta, $data );
			}
			
			$keys_names = array(
				'payment_file',
				'w9_file',
				'ssn_file',
				'ein_file',
				'nec_file',
			);
			
			foreach ( $keys_names as $key_name ) {
				if ( ! empty( $_FILES[ $key_name ] && $_FILES[ $key_name ][ 'size' ] > 0 ) ) {
					$id_uploaded       = $this->upload_one_file( $_FILES[ $key_name ] );
					$data[ $key_name ] = is_numeric( $id_uploaded ) ? $id_uploaded : '';
					if ( $post_status === 'publish' ) {
						$changes .= '<strong>Uploaded ' . $this->format_field_name( $key_name ) . ' </strong><br><br>';
					}
				}
			}
			
			
			if ( $data[ 'w9_classification' ] === 'business' ) {
				if ( empty( $data[ 'entity_name' ] ) ) {
					wp_send_json_error( [ 'message' => 'Please fill in the entity name.' ] );
				}
				if ( empty( $data[ 'ein' ] ) ) {
					wp_send_json_error( [ 'message' => 'Please fill in the EIN.' ] );
				}
				
				if ( ! preg_match( '/^\d{2}-\d{7}$/', $data[ 'ein' ] ) ) {
					wp_send_json_error( [ 'message' => 'EIN format is incorrect. It should be XX-XXXXXXX.' ] );
				}
				
				if ( $_FILES[ 'ein_file' ][ 'size' ] === 0 && ! $data[ 'ein_file_id' ] ) {
					wp_send_json_error( [ 'message' => 'EIN file is required.' ] );
				}
				
			}
			if ( $data[ 'w9_classification' ] === 'individual' ) {
				if ( empty( $data[ 'ssn' ] ) ) {
					wp_send_json_error( [ 'message' => 'Please fill in the SSN.' ] );
				}
				
				if ( ! preg_match( '/^\d{3}-\d{2}-\d{4}$/', $data[ 'ssn' ] ) ) {
					wp_send_json_error( [ 'message' => 'SSN format is incorrect. It should be XXX-XX-XXXX.' ] );
				}
				
				if ( empty( $data[ 'ssn_name' ] ) ) {
					wp_send_json_error( [ 'message' => 'Please fill in the SSN name.' ] );
				}
				if ( $_FILES[ 'ssn_file' ][ 'size' ] === 0 && ! $data[ 'ssn_file_id' ] ) {
					wp_send_json_error( [ 'message' => 'SSN file is required.' ] );
				}
			}
			
			$result = $this->update_driver_in_db( $data );
			
			if ( $result ) {
				
				if ( ! empty( $changes ) && $post_status === 'publish' ) {
					$user_id = get_current_user_id();
					$this->log_controller->create_one_log( array(
						'user_id'   => $user_id,
						'post_id'   => $data[ 'driver_id' ],
						'message'   => $changes,
						'post_type' => 'driver',
					) );
				}
				
				wp_send_json_success( [ 'message' => 'Driver successfully update', 'id_driver' => $result ] );
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
			
			$driver_object = $this->get_driver_by_id( $data[ 'driver_id' ] );
			$meta          = get_field_value( $driver_object, 'meta' );
			$main          = get_field_value( $driver_object, 'main' );
			$post_status   = $main[ 'status_post' ];
			
			$array_track = array(
				'vehicle_type',
				'vehicle_make',
				'vehicle_model',
				'vehicle_year',
				'gvwr',
				'payload',
				'dimensions',
				'vin',
				'registration_type',
				'registration_status',
				'registration_expiration',
				'plates',
				'plates_status',
				'plates_expiration',
				'ppe',
				'e_tracks',
				'pallet_jack',
				'dolly',
				'ramp',
				'printer',
				'sleeper',
				'load_bars',
			);
			
			if ( $post_status === 'publish' ) {
				// Переменная для хранения результатов изменений
				$changes = $this->get_log_template( $array_track, $meta, $data );
			}
			
			global $wpdb;
			$table_meta_name = $wpdb->prefix . $this->table_meta;
			$meta_data       = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta_name} WHERE post_id = %d", $data[ 'driver_id' ] ), ARRAY_A );
			$current_data    = array_column( $meta_data, 'meta_value', 'meta_key' );
			
			$picture_fields = [
				'vehicle_pictures'    => 'Uploaded vehicle pictures',
				'dimensions_pictures' => 'Uploaded dimensions pictures'
			];
			
			foreach ( $picture_fields as $field => $message ) {
				if ( ! empty( $_FILES[ $field ] ) && $_FILES[ $field ][ 'size' ][ 0 ] > 0 ) {
					$data[ $field ] = $this->process_uploaded_files( $field, $current_data[ $field ] );
					if ( $post_status === 'publish' ) {
						$changes .= "<strong>$message</strong><br><br>";
					}
				}
			}
			
			$keys_names = array(
				'plates_file',
				'registration_file',
				'ppe_file',
				'gvwr_placard',
				'e_tracks_file',
				'pallet_jack_file',
				'lift_gate_file',
				'dolly_file',
				'ramp_file'
			);
			
			foreach ( $keys_names as $key_name ) {
				if ( ! empty( $_FILES[ $key_name ] && $_FILES[ $key_name ][ 'size' ] > 0 ) ) {
					$id_uploaded       = $this->upload_one_file( $_FILES[ $key_name ] );
					$data[ $key_name ] = is_numeric( $id_uploaded ) ? $id_uploaded : '';
					
					if ( $post_status === 'publish' ) {
						
						if ( $key_name == 'gvwr_placard' ) {
							$changes .= '<strong>Uploaded GVWR placard </strong><br><br>';
						}
						if ( $key_name == 'registration_file' ) {
							$changes .= '<strong>Uploaded Registration file</strong><br><br>';
						}
					}
				}
			}
			
			
			// At this point, the data is sanitized and ready for further processing or saving to the database
			$result = $this->update_driver_in_db( $data );
			
			if ( $result ) {
				
				if ( ! empty( $changes ) && $post_status === 'publish' ) {
					$user_id = get_current_user_id();
					$this->log_controller->create_one_log( array(
						'user_id'   => $user_id,
						'post_id'   => $data[ 'driver_id' ],
						'message'   => $changes,
						'post_type' => 'driver',
					) );
				}
				wp_send_json_success( [ 'message' => 'Driver successfully added', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Driver not update, error add in database' ] );
		}
	}
	
	public function update_driver_document() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize incoming data
			
			$driver_id = isset( $_POST[ 'driver_id' ] ) ? sanitize_textarea_field( $_POST[ 'driver_id' ] ) : '';
			
			$record_notes        = isset( $_POST[ 'record_notes' ] )
				? sanitize_textarea_field( $_POST[ 'record_notes' ] ) : '';
			$driver_licence_type = isset( $_POST[ 'driver_licence_type' ] )
				? sanitize_text_field( $_POST[ 'driver_licence_type' ] ) : '';
			$real_id             = isset( $_POST[ 'real_id' ] ) ? sanitize_text_field( $_POST[ 'real_id' ] ) : '';
			
			$driver_licence_expiration = isset( $_POST[ 'driver_licence_expiration' ] )
				? sanitize_text_field( $_POST[ 'driver_licence_expiration' ] ) : '';
			$tanker_endorsement        = isset( $_POST[ 'tanker_endorsement' ] )
				? sanitize_text_field( $_POST[ 'tanker_endorsement' ] ) : '';
			
			$hazmat_endorsement = isset( $_POST[ 'hazmat_endorsement' ] )
				? sanitize_text_field( $_POST[ 'hazmat_endorsement' ] ) : '';
			
			$hazmat_certificate = isset( $_POST[ 'hazmat_certificate' ] )
				? sanitize_text_field( $_POST[ 'hazmat_certificate' ] ) : '';
			
			$hazmat_expiration   = isset( $_POST[ 'hazmat_expiration' ] )
				? sanitize_text_field( $_POST[ 'hazmat_expiration' ] ) : '';
			$twic                = isset( $_POST[ 'twic' ] ) ? sanitize_text_field( $_POST[ 'twic' ] ) : '';
			$twic_expiration     = isset( $_POST[ 'twic_expiration' ] )
				? sanitize_text_field( $_POST[ 'twic_expiration' ] ) : '';
			$tsa_approved        = isset( $_POST[ 'tsa_approved' ] ) ? sanitize_text_field( $_POST[ 'tsa_approved' ] )
				: '';
			$tsa_expiration      = isset( $_POST[ 'tsa_expiration' ] )
				? sanitize_text_field( $_POST[ 'tsa_expiration' ] ) : '';
			$legal_document_type = isset( $_POST[ 'legal_document_type' ] )
				? sanitize_text_field( $_POST[ 'legal_document_type' ] ) : '';
			
			$nationality             = isset( $_POST[ 'nationality' ] ) ? sanitize_text_field( $_POST[ 'nationality' ] )
				: '';
			$immigration_letter      = isset( $_POST[ 'immigration_letter' ] )
				? sanitize_text_field( $_POST[ 'immigration_letter' ] ) : '';
			$immigration_expiration  = isset( $_POST[ 'immigration_expiration' ] )
				? sanitize_text_field( $_POST[ 'immigration_expiration' ] ) : '';
			$background_check        = isset( $_POST[ 'background_check' ] )
				? sanitize_text_field( $_POST[ 'background_check' ] ) : '';
			$background_date         = isset( $_POST[ 'background_date' ] )
				? sanitize_text_field( $_POST[ 'background_date' ] ) : '';
			$canada_transition_proof = isset( $_POST[ 'canada_transition_proof' ] )
				? sanitize_text_field( $_POST[ 'canada_transition_proof' ] ) : '';
			$canada_transition_date  = isset( $_POST[ 'canada_transition_date' ] )
				? sanitize_text_field( $_POST[ 'canada_transition_date' ] ) : '';
			$change_9_training       = isset( $_POST[ 'change_9_training' ] )
				? sanitize_text_field( $_POST[ 'change_9_training' ] ) : '';
			$change_9_date           = isset( $_POST[ 'change_9_date' ] )
				? sanitize_text_field( $_POST[ 'change_9_date' ] ) : '';
			
			$insured                   = isset( $_POST[ 'insured' ] ) ? sanitize_text_field( $_POST[ 'insured' ] ) : '';
			$auto_liability_policy     = isset( $_POST[ 'auto_liability_policy' ] )
				? sanitize_text_field( $_POST[ 'auto_liability_policy' ] ) : '';
			$auto_liability_expiration = isset( $_POST[ 'auto_liability_expiration' ] )
				? sanitize_text_field( $_POST[ 'auto_liability_expiration' ] ) : '';
			$auto_liability_insurer    = isset( $_POST[ 'auto_liability_insurer' ] )
				? sanitize_text_field( $_POST[ 'auto_liability_insurer' ] ) : '';
			
			$motor_cargo_policy     = isset( $_POST[ 'motor_cargo_policy' ] )
				? sanitize_text_field( $_POST[ 'motor_cargo_policy' ] ) : '';
			$motor_cargo_expiration = isset( $_POST[ 'motor_cargo_expiration' ] )
				? sanitize_text_field( $_POST[ 'motor_cargo_expiration' ] ) : '';
			$motor_cargo_insurer    = isset( $_POST[ 'motor_cargo_insurer' ] )
				? sanitize_text_field( $_POST[ 'motor_cargo_insurer' ] ) : '';
			
			$status                = isset( $_POST[ 'status' ] ) ? sanitize_text_field( $_POST[ 'status' ] ) : '';
			$cancellation_date     = isset( $_POST[ 'cancellation_date' ] )
				? sanitize_text_field( $_POST[ 'cancellation_date' ] ) : '';
			$insurance_declaration = isset( $_POST[ 'insurance_declaration' ] )
				? sanitize_text_field( $_POST[ 'insurance_declaration' ] ) : '';
			$notes                 = isset( $_POST[ 'notes' ] ) ? sanitize_textarea_field( $_POST[ 'notes' ] ) : '';
			
			$data = [
				'driver_id'                 => $driver_id,
				'record_notes'              => $record_notes,
				'driver_licence_type'       => $driver_licence_type,
				'real_id'                   => $real_id,
				'driver_licence_expiration' => $driver_licence_expiration,
				'tanker_endorsement'        => $tanker_endorsement,
				'hazmat_endorsement'        => $hazmat_endorsement,
				'hazmat_certificate'        => $hazmat_certificate,
				'hazmat_expiration'         => $hazmat_expiration,
				'twic'                      => $twic,
				'twic_expiration'           => $twic_expiration,
				'tsa_approved'              => $tsa_approved,
				'tsa_expiration'            => $tsa_expiration,
				'legal_document_type'       => $legal_document_type,
				'nationality'               => $nationality,
				'immigration_letter'        => $immigration_letter,
				'immigration_expiration'    => $immigration_expiration,
				'background_check'          => $background_check,
				'background_date'           => $background_date,
				'canada_transition_proof'   => $canada_transition_proof,
				'canada_transition_date'    => $canada_transition_date,
				'change_9_training'         => $change_9_training,
				'change_9_date'             => $change_9_date,
				'insured'                   => $insured,
				'auto_liability_policy'     => $auto_liability_policy,
				'auto_liability_expiration' => $auto_liability_expiration,
				'auto_liability_insurer'    => $auto_liability_insurer,
				'motor_cargo_policy'        => $motor_cargo_policy,
				'motor_cargo_expiration'    => $motor_cargo_expiration,
				'motor_cargo_insurer'       => $motor_cargo_insurer,
				'status'                    => $status,
				'cancellation_date'         => $cancellation_date,
				'insurance_declaration'     => $insurance_declaration,
				'notes'                     => $notes,
			];
			
			$driver_object = $this->get_driver_by_id( $data[ 'driver_id' ] );
			$meta          = get_field_value( $driver_object, 'meta' );
			$main          = get_field_value( $driver_object, 'main' );
			$post_status   = $main[ 'status_post' ];
			
			$array_track = array(
				'record_notes',
				'driver_licence_type',
				'real_id',
				'driver_licence_expiration',
				'tanker_endorsement',
				'hazmat_endorsement',
				'hazmat_certificate',
				'hazmat_expiration',
				'twic',
				'twic_expiration',
				'tsa_approved',
				'tsa_expiration',
				'legal_document_type',
				'nationality',
				'immigration_letter',
				'immigration_expiration',
				'background_check',
				'background_date',
				'canada_transition_proof',
				'canada_transition_date',
				'change_9_training',
				'change_9_date',
				'insured',
				'auto_liability_policy',
				'auto_liability_expiration',
				'auto_liability_insurer',
				'motor_cargo_policy',
				'motor_cargo_expiration',
				'motor_cargo_insurer',
				'status',
				'cancellation_date',
				'insurance_declaration',
			);
			
			if ( $post_status === 'publish' ) {
				// Переменная для хранения результатов изменений
				$changes = $this->get_log_template( $array_track, $meta, $data );
			}
			
			$keys_names = array(
				'hazmat_certificate_file',
				'driving_record',
				'driver_licence',
				'legal_document',
				'twic_file',
				'tsa_file',
				'motor_cargo_coi',
				'auto_liability_coi',
				'ic_agreement',
				'change_9_file',
				'canada_transition_file',
				'immigration_file',
				'background_file',
			);
			
			foreach ( $keys_names as $key_name ) {
				if ( ! empty( $_FILES[ $key_name ] && $_FILES[ $key_name ][ 'size' ] > 0 ) ) {
					$id_uploaded       = $this->upload_one_file( $_FILES[ $key_name ] );
					$data[ $key_name ] = is_numeric( $id_uploaded ) ? $id_uploaded : '';
					if ( $post_status === 'publish' ) {
						$changes .= '<strong>Uploaded ' . $this->format_field_name( $key_name ) . ' </strong><br><br>';
					}
				}
			}
			
			// At this point, the data is sanitized and ready for further processing or saving to the database
			$result = $this->update_driver_in_db( $data );
			
			if ( $result ) {
				
				if ( ! empty( $changes ) && $post_status === 'publish' ) {
					$user_id = get_current_user_id();
					$this->log_controller->create_one_log( array(
						'user_id'   => $user_id,
						'post_id'   => $data[ 'driver_id' ],
						'message'   => $changes,
						'post_type' => 'driver',
					) );
					
				}
				
				wp_send_json_success( [ 'message' => 'Driver successfully added', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Driver not update, error add in database' ] );
		}
	}
	
	public function upload_driver_helper( $data ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$driver_id = isset( $_POST[ 'driver_id' ] ) ? sanitize_textarea_field( $_POST[ 'driver_id' ] ) : '';
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
		
		$data_main[ 'user_id_added' ]   = $data[ 'recruiter_add' ];
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
		
		if ( ! empty( $data[ 'recruiter_add' ] ) ) {
			$data_main[ 'user_id_added' ] = $data[ 'recruiter_add' ];
		}
		
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