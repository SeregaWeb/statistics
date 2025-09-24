<?php

class TMSDrivers extends TMSDriversHelper {
	
	public $table_main     = 'drivers';
	public $table_meta     = 'drivers_meta';
	public $table_raiting  = 'drivers_raiting';
	public $table_notice   = 'drivers_notice';
	public $per_page_loads = 30;
	public $hold_time      = 15;
	
	public $log_controller = false;
	
	public $helper       = false;
	public $email_helper = false;
	public $user_sync_api = false;
	
	public function __construct() {
		$this->log_controller = new TMSLogs();
		$this->helper         = new TMSCommonHelper();
		$this->email_helper   = new TMSEmails();
		$this->user_sync_api  = new TMSUserSyncAPI();
	}
	
	public function init() {
		$this->ajax_actions();
		$this->create_tables();
		$this->init_cron();
		
		// Принудительно запускаем cron при каждом запросе (для тестирования)
		add_action( 'init', array( $this, 'maybe_run_cron' ) );
	}
	
	/**
	 * Проверяет и запускает cron задачу при необходимости
	 */
	public function maybe_run_cron() {
		// Проверяем, нужно ли запустить cron
		$next_scheduled = wp_next_scheduled( 'driver_hold_cleanup_hook' );
		if ( $next_scheduled && $next_scheduled <= time() ) {
			wp_schedule_single_event( time(), 'driver_hold_cleanup_hook' );
			spawn_cron();
		}
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_driver'                   => 'add_driver',
			'update_driver_contact'        => 'update_driver_contact',
			'update_driver_information'    => 'update_driver_information',
			'update_driver_finance'        => 'update_driver_finance',
			'delete_open_image_driver'     => 'delete_open_image_driver',
			'update_driver_document'       => 'update_driver_document',
			'update_driver_status'         => 'update_driver_status',
			'remove_one_driver'            => 'remove_one_driver',
			'upload_driver_helper'         => 'upload_driver_helper',
			'optimize_drivers_tables'      => 'optimize_drivers_tables',
			'update_location_driver'       => 'update_location_driver',
			'hold_driver_status'           => 'hold_driver_status',
			'add_driver_rating'            => 'ajax_add_driver_rating',
			'add_driver_notice'            => 'ajax_add_driver_notice',
			'update_notice_status'         => 'ajax_update_notice_status',
			'update_clean_background'      => 'update_clean_background',
			'update_background_check_date' => 'update_background_check_date',
			'update_driver_zipcode_date'   => 'update_driver_zipcode_date',
			'get_driver_ratings'           => 'get_driver_ratings',
			'get_driver_notices'           => 'get_driver_notices',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this->helper, 'need_login' ] );
		}
		
		add_action( 'delete_user', array( $this, 'handle_recruiter_deletion' ), 20 );
	}
	
	public function get_statistics() {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Собираем запрос
		$sql = "
		SELECT
			m.user_id_added,
			COUNT(DISTINCT m.id) as total,
			SUM(CASE WHEN tm1.meta_key = 'tanker_endorsement' AND tm1.meta_value = 'on' THEN 1 ELSE 0 END) AS tanker_on,
			SUM(CASE WHEN tm2.meta_key = 'twic' AND tm2.meta_value = 'on' THEN 1 ELSE 0 END) AS twic_on,
			SUM(CASE WHEN tm3.meta_key = 'hazmat_endorsement' AND tm3.meta_value = 'on' THEN 1 ELSE 0 END) AS hazmat_on,
			SUM(CASE WHEN tm4.meta_key = 'vehicle_type' AND tm4.meta_value = 'cargo-van' THEN 1 ELSE 0 END) AS cargo_van,
			SUM(CASE WHEN tm5.meta_key = 'vehicle_type' AND tm5.meta_value = 'sprinter-van' THEN 1 ELSE 0 END) AS sprinter_van,
			SUM(CASE WHEN tm6.meta_key = 'vehicle_type' AND tm6.meta_value = 'box-truck' THEN 1 ELSE 0 END) AS box_truck,
			SUM(CASE WHEN tm7.meta_key = 'vehicle_type' AND tm7.meta_value = 'reefer' THEN 1 ELSE 0 END) AS reefer
		FROM $table_main AS m
		LEFT JOIN $table_meta AS tm1 ON tm1.post_id = m.id AND tm1.meta_key = 'tanker_endorsement'
		LEFT JOIN $table_meta AS tm2 ON tm2.post_id = m.id AND tm2.meta_key = 'twic'
		LEFT JOIN $table_meta AS tm3 ON tm3.post_id = m.id AND tm3.meta_key = 'hazmat_endorsement'
		LEFT JOIN $table_meta AS tm4 ON tm4.post_id = m.id AND tm4.meta_key = 'vehicle_type' AND tm4.meta_value = 'cargo-van'
		LEFT JOIN $table_meta AS tm5 ON tm5.post_id = m.id AND tm5.meta_key = 'vehicle_type' AND tm5.meta_value = 'sprinter-van'
		LEFT JOIN $table_meta AS tm6 ON tm6.post_id = m.id AND tm6.meta_key = 'vehicle_type' AND tm6.meta_value = 'box-truck'
		LEFT JOIN $table_meta AS tm7 ON tm7.post_id = m.id AND tm7.meta_key = 'vehicle_type' AND tm7.meta_value = 'reefer'
		GROUP BY m.user_id_added
	";
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		return $results;
	}
	
	function handle_recruiter_deletion( $user_id ) {
		// Проверяем, является ли удаляемый пользователь диспетчером
		$user = get_user_by( 'ID', $user_id );
		
		if ( $user && in_array( 'recruiter', $user->roles ) || $user && in_array( 'recruiter-tl', $user->roles ) || $user && in_array( 'hr_manager', $user->roles ) ) {
			
			// Выполняем перенос лодов на нового диспетчера
			$result = $this->move_driver_for_new_recruiter( $user_id );
			
			// Логируем результат для отладки
			if ( is_wp_error( $result ) ) {
				error_log( 'Error transferring loads: ' . $result->get_error_message() );
			} else {
				error_log( 'Successful load transfer: ' . $result );
			}
		}
	}
	
	function move_driver_for_new_recruiter( $recruiter_id_to_find ) {
		global $global_options;
		
		// Получаем новый ID диспетчера из глобальных настроек
		$new_recruiter_id = get_field_value( $global_options, 'empty_recruiter' );
		
		// Проверяем, что новый ID не равен ID удаляемого диспетчера
		if ( $new_recruiter_id === $recruiter_id_to_find ) {
			return new WP_Error( 'invalid_id', 'Новый ID диспетчера не может совпадать с удаляемым.' );
		}
		
		// Получаем все записи, связанные с удаляемым диспетчером
		$records = $this->get_recruiter_initials_records( $recruiter_id_to_find );
		
		// Проверяем, есть ли что обновлять
		if ( empty( $records ) ) {
			return new WP_Error( 'no_records', 'error found drivers.' );
		}
		
		// Обновляем все записи на нового диспетчера
		$update_result = $this->update_recruiter_initials_records( $records, $new_recruiter_id );
		
		// Проверяем результат
		if ( $update_result > 0 ) {
			return 'success move to new recruiter.';
		} else {
			return new WP_Error( 'update_failed', 'Не удалось обновить записи.' );
		}
	}
	
	function update_recruiter_initials_records( $records, $new_recruiter_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		$updated_rows = 0;
		
		foreach ( $records as $record ) {
			if ( ! empty( $record[ 'id' ] ) ) {
				$result = $wpdb->update( $table_name, [ 'user_id_added' => $new_recruiter_id ], [ 'id' => $record[ 'id' ] ], [ '%d' ], [ '%d' ] );
				
				if ( $result !== false ) {
					$updated_rows += $result; // увеличиваем счетчик, если обновление прошло
				}
			}
		}
		
		return $updated_rows; // можно вернуть число успешно обновлённых строк
	}
	
	
	function get_recruiter_initials_records( $dispatcher_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		// Выполняем запрос к каждой таблице
		$query = $wpdb->prepare( "SELECT id FROM $table_name
        WHERE user_id_added = %s", $dispatcher_id );
		
		$table_results = $wpdb->get_results( $query, ARRAY_A );
		
		return $table_results;
	}
	
	public function update_location_driver() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"driver_id"        => FILTER_SANITIZE_STRING,
				"driver_status"    => FILTER_SANITIZE_STRING,
				"status_date"      => FILTER_SANITIZE_STRING,
				"current_location" => FILTER_SANITIZE_STRING,
				"current_city"     => FILTER_SANITIZE_STRING,
				"current_zipcode"  => FILTER_SANITIZE_STRING,
				"latitude"         => FILTER_SANITIZE_STRING,
				"longitude"        => FILTER_SANITIZE_STRING,
				"country"          => FILTER_SANITIZE_STRING,
				"current_country"  => FILTER_SANITIZE_STRING,
				"notes"            => FILTER_SANITIZE_STRING,
			] );
			
			// Проверяем, что driver_id передан
			if ( empty( $MY_INPUT[ 'driver_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID is required' ] );
				
				return;
			}
			
			$driver_id = intval( $MY_INPUT[ 'driver_id' ] );
			
			// Подготавливаем данные для обновления
			$update_data = [
				'driver_id'        => $driver_id,
				'driver_status'    => $MY_INPUT[ 'driver_status' ] ?? '',
				'status_date'      => $MY_INPUT[ 'status_date' ] ?? '',
				'current_location' => $MY_INPUT[ 'current_location' ] ?? '',
				'current_city'     => $MY_INPUT[ 'current_city' ] ?? '',
				'current_zipcode'  => $MY_INPUT[ 'current_zipcode' ] ?? '',
				'latitude'         => $MY_INPUT[ 'latitude' ] ?? '',
				'longitude'        => $MY_INPUT[ 'longitude' ] ?? '',
				'country'          => $MY_INPUT[ 'country' ] ?? '',
				'current_country'  => $MY_INPUT[ 'current_country' ] ?? '',
			];
			
			// Добавляем notes только если они переданы в запросе
			if ( isset( $MY_INPUT[ 'notes' ] ) ) {
				$update_data[ 'notes' ] = $MY_INPUT[ 'notes' ];
			}
			
			if ( $update_data[ 'latitude' ] === '' || $update_data[ 'longitude' ] === '' ) {
				wp_send_json_error( [ 'message' => 'Latitude or longitude is required, please check the address.' ] );
			}


			$user_id = get_current_user_id();
			$name_user = $this->get_user_full_name_by_id( $user_id );
			
			// Get current time in New York timezone and format it properly
			$ny_timezone = new DateTimeZone('America/New_York');
			$ny_time = new DateTime('now', $ny_timezone);
			$formatted_time = $ny_time->format('m/d/Y g:i a');
			
			$update_data[ 'last_user_update' ] = 'Last update: '.$name_user['full_name'] . ' - ' . $formatted_time;

			// Обновляем данные водителя
			$result = $this->update_driver_in_db( $update_data );
			
			if ( $result ) {
				// Get updated driver data
				$updated_driver_data = $this->get_driver_data_for_table_row( $driver_id );
				
				wp_send_json_success( [
					'message'   => 'Driver location updated successfully',
					'driver_id' => $driver_id,
					'data'      => $update_data,
					'updated_driver' => $updated_driver_data
				] );
			} else {
				wp_send_json_error( [ 'message' => 'Failed to update driver location' ] );
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * Get driver data for table row update
	 */
	private function get_driver_data_for_table_row( $driver_id ) {
		global $wpdb;
		
		// Get main driver data
		$main_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}drivers WHERE id = %d",
			$driver_id
		), ARRAY_A );
		
		if ( ! $main_data ) {
			return null;
		}
		
		// Get meta data
		$meta_data = $wpdb->get_results( $wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$wpdb->prefix}drivers_meta WHERE post_id = %d",
			$driver_id
		), ARRAY_A );
		
		$meta = array();
		foreach ( $meta_data as $meta_row ) {
			$meta[ $meta_row['meta_key'] ] = $meta_row['meta_value'];
		}
		
		// Get status text
		$driver_status = $meta['driver_status'] ?? '';
		$status_text = "Need set status";
		if ( $driver_status ) {
			if ( isset( $this->status[ $driver_status ] ) ) {
				$status_text = $this->status[ $driver_status ];
			}
		}
		
		// Get location HTML
		$location_html = $this->get_location_cell_html( $meta, $main_data, $driver_status );
		
		// Prepare data for table row
		$driver_data = array(
			'id' => $driver_id,
			'driver_name' => $meta['driver_name'] ?? '',
			'driver_status' => $driver_status,
			'status_text' => $status_text,
			'status_class' => $driver_status ? $driver_status : 'text-danger',
			'location_html' => $location_html,
			'current_location' => $meta['current_location'] ?? '',
			'current_city' => $meta['current_city'] ?? '',
			'current_zipcode' => $meta['current_zipcode'] ?? '',
			'latitude' => $meta['latitude'] ?? '',
			'longitude' => $meta['longitude'] ?? '',
			'country' => $meta['country'] ?? '',
			'status_date' => $meta['status_date'] ?? '',
			'updated_zipcode' => $main_data['updated_zipcode'] ?? '',
			'date_available' => $meta['date_available'] ?? '',
			'last_user_update' => $meta['last_user_update'] ?? '',
			'notes' => $meta['notes'] ?? ''
		);
		
		return $driver_data;
	}
	
	/**
	 * Generate location cell HTML (exact copy from template)
	 */
	private function get_location_cell_html( $meta, $main_data, $driver_status ) {
		$current_location = $meta['current_location'] ?? '';
		$current_city = $meta['current_city'] ?? '';
		$date_available = $main_data['date_available'] ?? '';
		$updated_zip_code = $main_data['updated_zipcode'] ?? '';  // Use same variable name as template
		
		
		// Function to check if date is valid and not a default/invalid date (exact copy from template)
		$is_valid_date = function( $date_string ) {
			if ( empty( $date_string ) || $date_string === '0000-00-00 00:00:00' || $date_string === '0000-00-00' ) {
				return false;
			}
			
			$timestamp = strtotime( $date_string );
			if ( $timestamp === false || $timestamp <= 0 ) {
				return false;
			}
			
			// Check for common invalid dates (Unix epoch, negative years, etc.)
			$year = date( 'Y', $timestamp );
			if ( $year < 1900 || $year > 2100 ) {
				return false;
			}
			
			return true;
		};
		
		$date_status = '';
		if ( $is_valid_date( $date_available ) ) {
			$date_status = esc_html( date( 'm/d/Y g:i a', strtotime( $date_available ) ) );
		}
		
		// Calculate update status (exact copy from template)
		$updated = true;
		$updated_text = '';
		$timestamp = null;
		$class_update_code = '';

		$ny_timezone = new DateTimeZone('America/New_York');
		$ny_time = new DateTime('now', $ny_timezone);

		$time = strtotime( $ny_time->format('Y-m-d H:i:s') . TIME_AVAILABLE_DRIVER );
		$updated_zip_code_time = strtotime( $updated_zip_code );
		
		
		if ( ! isset( $updated_zip_code_time ) || empty( $updated_zip_code_time ) ) {
			$updated_text = 'Update date not set!';
			$class_update_code = 'weiting';
		} else {
			if ( $time >= $updated_zip_code_time ) {
				$class_update_code = 'need_update';
				$updated_text = date( 'm/d/Y g:i a', $updated_zip_code_time );
			}
		}
		
		// Build HTML exactly like template
		$state = explode( ',', $updated_zip_code );
		$location_text = ( isset( $current_location ) && isset( $current_city ) )
			? $current_city . ', ' . $current_location . ' ' : 'Need to set this field ';
		
		// Add date status if not available (exact copy from template)
		if ( $driver_status !== 'available' ) {
			$location_text .= '<br>'. $date_status;
		}
		
		// Build full HTML exactly like template
		$html = '<td class="table-column js-location-update ' . $class_update_code . '" style="font-size: 12px; width: 200px;">';
		$html .= $location_text;
		$html .= '<br>';
		$html .= '<span>' . $updated_text . '</span>';
		$html .= '</td>';
		
		return $html;
	}
	
	public function remove_one_driver() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			// Clear drivers cache when driver is removed
			$this->clear_drivers_cache();
			
			// Получаем данные запроса
			$MY_INPUT = filter_var_array( $_POST, [
				"id_driver" => FILTER_SANITIZE_STRING,
			] );
			
			$id_load    = $MY_INPUT[ "id_driver" ];
			$table_name = $wpdb->prefix . $this->table_main;
			$table_meta = $wpdb->prefix . $this->table_meta;
			
			// Получаем метаданные
			$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta} WHERE post_id = %d", $id_load ), ARRAY_A );
			
			// Get driver info before deletion for email notification
			$driver_name  = 'Unknown Driver';
			$driver_phone = 'N/A';
			
			// Extract driver info from meta data
			$driver_email = 'N/A';
			$home_location = 'N/A';
			$vehicle_type = 'N/A';
			$vin = 'N/A';
			
			foreach ( $meta_data as $meta ) {
				if ( $meta[ 'meta_key' ] === 'driver_name' ) {
					$driver_name = $meta[ 'meta_value' ];
				}
				if ( $meta[ 'meta_key' ] === 'driver_phone' ) {
					$driver_phone = $meta[ 'meta_value' ];
				}
				if ( $meta[ 'meta_key' ] === 'driver_email' ) {
					$driver_email = $meta[ 'meta_value' ];
				}
				if ( $meta[ 'meta_key' ] === 'home_location' ) {
					$home_location = $meta[ 'meta_value' ];
				}
				if ( $meta[ 'meta_key' ] === 'vehicle_type' ) {
					$vehicle_type = $meta[ 'meta_value' ];
				}
				if ( $meta[ 'meta_key' ] === 'vin' ) {
					$vin = $meta[ 'meta_value' ];
				}
			}
			
			// Sync driver deletion before removing data
			$driver_sync_data = array(
				'driver_id' => $id_load,
				'driver_name' => $driver_name,
				'driver_email' => $driver_email,
				'driver_phone' => $driver_phone,
				'home_location' => $home_location,
				'vehicle_type' => $vehicle_type,
				'vin' => $vin
			);
			$this->user_sync_api->sync_user( 'delete', $driver_sync_data, 'driver' );
			
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
			
			// Get user info
			$user_id   = get_current_user_id();
			$user_name = $this->get_user_full_name_by_id( $user_id );
			$project   = get_field( 'current_select', 'user_' . $user_id );
			
			// Get admin email
			$admin_email = get_option( 'admin_email' );
			
			// Send email notification about driver removal
			$this->email_helper->send_custom_email( $admin_email, array(
				'subject'      => 'Driver Removed: (' . $id_load . ') ' . $driver_name,
				'project_name' => $project,
				'subtitle'     => ( is_array( $user_name ) && isset( $user_name[ 'full_name' ] )
						? $user_name[ 'full_name' ] : 'Unknown User' ) . ' has removed a driver from our system',
				'message'      => "Driver ID: " . $id_load . "<br>
					Driver Name: " . $driver_name . "<br>
					Driver Phone: " . $driver_phone . "<br>
					Removed by: " . ( is_array( $user_name ) && isset( $user_name[ 'full_name' ] )
						? $user_name[ 'full_name' ] : 'Unknown User' ) . "<br>
					Removal Date: " . current_time( 'mysql' ) . "<br><br>
					This driver has been permanently removed from the system along with all associated files and data."
			) );
			
			wp_send_json_success( [ 'message' => 'Load and associated files removed successfully' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function set_filter_params( $args ) {
		$my_search  = trim( get_field_value( $_GET, 'my_search' ) ?? '' );
		$recruiter  = trim( get_field_value( $_GET, 'recruiter' ) ?? '' );
		$year       = trim( get_field_value( $_GET, 'fyear' ) ?? '' );
		$month      = trim( get_field_value( $_GET, 'fmonth' ) ?? '' );
		$source     = trim( get_field_value( $_GET, 'source' ) ?? '' );
		$additional = trim( get_field_value( $_GET, 'additional' ) ?? '' );
		
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
	
	public function set_filter_params_search( $args ) {
		$my_search       = trim( get_field_value( $_GET, 'my_search' ) ?? '' );
		$extended_search = trim( get_field_value( $_GET, 'extended_search' ) ?? '' );
		$radius          = trim( get_field_value( $_GET, 'radius' ) ?? '' );
		$country         = trim( get_field_value( $_GET, 'country' ) ?? '' );
		$capabilities    = get_field_value( $_GET, 'capabilities' );
		
		if ( $my_search ) {
			$args[ 'my_search' ] = $my_search;
		}
		
		if ( $extended_search ) {
			$args[ 'extended_search' ] = $extended_search;
		}
		
		if ( $radius ) {
			$args[ 'radius' ] = $radius;
		}
		
		if ( $country ) {
			$args[ 'country' ] = $country;
		}
		
		// Handle capabilities filter
		if ( ! empty( $capabilities ) ) {
			$args[ 'capabilities' ] = is_array( $capabilities ) ? $capabilities : array( $capabilities );
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
			LEFT JOIN $table_meta AS driver_status
				ON main.id = driver_status.post_id AND driver_status.meta_key = 'driver_status'
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
			$search_term = $args[ 'my_search' ];
			
			// Check if search term is a phone number (formatted or unformatted)
			$phone_detected = $this->is_phone_number( $search_term );
			
			if ( $phone_detected ) {
				// Format phone number and search in driver_phone field
				$formatted_phone = $this->format_phone_number( $search_term );
				$where_conditions[] = "driver_phone.meta_value = %s";
				$where_values[] = $formatted_phone;
			} elseif ( is_numeric( $search_term ) ) {
				// For numeric search that's not a phone, search by ID
				$where_conditions[] = "main.id = %s";
				$where_values[] = $search_term;
			} else {
				// Search in text fields (name, email, insurance, entity, plates, vin)
				$where_conditions[] = "(" . "driver_name.meta_value LIKE %s OR " . "driver_email.meta_value LIKE %s OR " . "motor_cargo_insurer.meta_value LIKE %s OR " . "auto_liability_insurer.meta_value LIKE %s OR " . "entity_name.meta_value LIKE %s OR " . "plates.meta_value LIKE %s OR " . "vin.meta_value LIKE %s " . ")";
				
				$search_value = '%' . $wpdb->esc_like( $search_term ) . '%';
				for ( $i = 0; $i < 7; $i ++ ) {
					$where_values[] = $search_value;
				}
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
		
		// Add driver visibility condition based on user role
		$driverHelper    = new TMSDriversHelper();
		$visibility_data = $driverHelper->get_driver_visibility_condition();
		if ( ! empty( $visibility_data[ 'condition' ] ) ) {
			$where_conditions[] = $visibility_data[ 'condition' ];
		}
		
		// Применяем фильтры к запросу
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Подсчёт общего количества записей с учётом фильтров
		// Выполняем запрос без LIMIT для подсчёта общего количества
		$count_sql = "SELECT main.id" . $join_builder . " WHERE 1=1";
		if ( ! empty( $where_conditions ) ) {
			$count_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		$count_sql .= " ORDER BY main.$sort_by $sort_order";
		
		$all_results   = $wpdb->get_results( $wpdb->prepare( $count_sql, ...$where_values ), ARRAY_A );
		$total_records = count( $all_results );
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		// Корректировка: если записей меньше, чем на одну страницу, показываем только одну страницу (или 0, если записей нет)
		if ( $total_records == 0 ) {
			$total_pages = 0;
		} elseif ( $total_pages < 1 ) {
			$total_pages = 1;
		}
		
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
			
			// Сортировка по статусу и времени обновления для обычного поиска
			if ( ! isset( $args[ 'has_distance_data' ] ) || ! $args[ 'has_distance_data' ] || empty( $args[ 'filtered_drivers' ] ) ) {
				$main_results = $this->sort_drivers_by_status_priority_for_regular_search( $main_results );
			}
		}
		
		
		return array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
	}
	
	public function get_table_items_search( $args = array() ) {
		global $wpdb;
		
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// var_dump($args); // Debug info - uncomment if needed
		
		
		$per_page     = isset( $args[ 'per_page_loads' ] ) ? $args[ 'per_page_loads' ] : $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'id';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) == 'asc' ? 'ASC'
			: 'DESC';
		
		// Check if we have a search address for geocoding
		
		
		if ( isset( $args[ 'my_search' ] ) && $args[ 'my_search' ] ) {
			global $global_options;
			
			$api_key_here_map = get_field_value( $global_options, 'api_key_here_map' );
			$use_driver       = get_field_value( $global_options, 'use_driver' );
			$url_pelias       = get_field_value( $global_options, 'url_pelias' );
			$url_ors          = get_field_value( $global_options, 'url_ors' );
			$geocoder         = get_field_value( $global_options, 'use_geocoder' );
			
			// Get coordinates for the search address
			$search_coordinates = $this->get_coordinates_by_address( $args[ 'my_search' ], $geocoder, array(
				'api_key'      => $api_key_here_map,
				'url_pelias'   => $url_pelias,
				'region_value' => isset( $args[ 'country' ] ) ? $args[ 'country' ] : ''
			) );
			
			// If we have coordinates, use advanced distance-based filtering
			if ( $search_coordinates ) {
				$args[ 'search_lat' ] = $search_coordinates[ 'lat' ];
				$args[ 'search_lng' ] = $search_coordinates[ 'lng' ];
				
				// Get all available drivers with caching
				$cache_key   = 'tms_all_available_drivers';
				$all_drivers = get_transient( $cache_key );
				
				if ( false === $all_drivers ) {
					$all_drivers = $this->get_all_available_driver( true );
					// Cache for 15 minutes (900 seconds)
					set_transient( $cache_key, $all_drivers, 5 * MINUTE_IN_SECONDS );
				}
				
				// Filter and sort drivers by distance
				$max_distance  = isset( $args[ 'radius' ] ) ? intval( $args[ 'radius' ] ) : 300;
				$capabilities  = isset( $args[ 'capabilities' ] ) ? $args[ 'capabilities' ] : array();
				$valid_drivers = $this->filter_and_sort_drivers_by_distance( $all_drivers, $search_coordinates[ 'lat' ], $search_coordinates[ 'lng' ], $max_distance, $capabilities );
				// Get real road distances using mapping service
				if ( ! empty( $valid_drivers ) ) {
					$MapController = new Map_Controller();
					
					// Prepare start location
					$start_location = array(
						array(
							"lat" => (float) $search_coordinates[ 'lat' ],
							"lng" => (float) $search_coordinates[ 'lng' ]
						)
					);
					
					// Prepare driver locations
					$driver_locations = array();
					foreach ( $valid_drivers as $driver ) {
						$driver_locations[] = array(
							"lat" => (float) $driver[ 'lat' ],
							"lng" => (float) $driver[ 'lon' ]
						);
					}
					
					// Get real distances
					$real_distances = $MapController->getDistances( $start_location, $driver_locations );
					
					// Update drivers with real distances
					if ( $real_distances && is_array( $real_distances ) ) {
						$i = 0;
						foreach ( $valid_drivers as $driver_id => &$driver ) {
							if ( isset( $real_distances[ $i ] ) ) {
								// Handle different response formats from mapping services
								$distance_value = $real_distances[ $i ];
								if ( is_array( $distance_value ) && isset( $distance_value[ 'distance' ] ) ) {
									// OpenRouteServices format: array with 'distance' key
									$distance_value = $distance_value[ 'distance' ];
								}
								
								// Ensure we have a numeric value
								if ( is_numeric( $distance_value ) ) {
									$driver[ 'real_distance' ] = round( $distance_value, 2 );
								}
							}
							$i ++;
						}
					}
					
					// Sort drivers by status priority
					$valid_drivers = $this->sort_drivers_by_status_priority( $valid_drivers, $search_coordinates );
					
					// Filter drivers by max_distance after getting real distances
					$filtered_by_distance = array();
					foreach ( $valid_drivers as $key => $driver ) {
						if ( isset( $driver[ 'distance' ] ) && is_numeric( $driver[ 'distance' ] ) && $driver[ 'distance' ] <= $max_distance ) {
							$filtered_by_distance[ $key ] = $driver;
						}
					}
					$valid_drivers = $filtered_by_distance;
				}
				// Store filtered drivers for template use (even if empty)
				$args[ 'filtered_drivers' ]       = $valid_drivers;
				$args[ 'total_filtered_drivers' ] = count( $valid_drivers );
				$args[ 'has_distance_data' ]      = true;
				$args[ 'search_coordinates' ]     = $search_coordinates;
				
				
			}
		}
		
		$join_builder = "
			FROM $table_main AS main
			LEFT JOIN $table_meta AS driver_name
				ON main.id = driver_name.post_id AND driver_name.meta_key = 'driver_name'
			LEFT JOIN $table_meta AS driver_phone
				ON main.id = driver_phone.post_id AND driver_phone.meta_key = 'driver_phone'
			LEFT JOIN $table_meta AS driver_email
				ON main.id = driver_email.post_id AND driver_email.meta_key = 'driver_email'
			LEFT JOIN $table_meta AS vehicle_type
				ON main.id = vehicle_type.post_id AND vehicle_type.meta_key = 'vehicle_type'
			LEFT JOIN $table_meta AS mc
				ON main.id = mc.post_id AND mc.meta_key = 'mc'
			LEFT JOIN $table_meta AS dot
				ON main.id = dot.post_id AND dot.meta_key = 'dot'
			LEFT JOIN $table_meta AS driver_status
				ON main.id = driver_status.post_id AND driver_status.meta_key = 'driver_status'
			LEFT JOIN $table_meta AS mc_dot_human_tested
				ON main.id = mc_dot_human_tested.post_id AND mc_dot_human_tested.meta_key = 'mc_dot_human_tested'
			LEFT JOIN $table_meta AS clear_background
				ON main.id = clear_background.post_id AND clear_background.meta_key = 'clear_background'
		";
		
		// Add JOINs for capabilities filtering
		$capabilities_joins = array();
		$processed_joins    = array(); // Track processed joins to avoid duplicates
		
		if ( ! empty( $args[ 'capabilities' ] ) && is_array( $args[ 'capabilities' ] ) ) {
			foreach ( $args[ 'capabilities' ] as $capability ) {
				// Handle special cases for cross_border fields
				if ( $capability === 'cross_border_canada' || $capability === 'cross_border_mexico' ) {
					$alias    = 'cap_cross_border';
					$meta_key = 'cross_border';
				} else {
					$alias    = 'cap_' . $capability;
					$meta_key = $capability;
				}
				
				// Avoid duplicate joins for cross_border
				if ( ! in_array( $alias, $processed_joins ) ) {
					$capabilities_joins[] = "LEFT JOIN $table_meta AS $alias
						ON main.id = $alias.post_id AND $alias.meta_key = '$meta_key'";
					$processed_joins[]    = $alias;
				}
			}
			$join_builder .= "\n" . implode( "\n", $capabilities_joins );
		}
		
		
		$where_conditions = array();
		$where_values     = array();
		
		// Add capabilities filtering conditions
		if ( ! empty( $args[ 'capabilities' ] ) && is_array( $args[ 'capabilities' ] ) ) {
			$capability_conditions = array();
			foreach ( $args[ 'capabilities' ] as $capability ) {
				// Handle special cases for cross_border fields
				if ( $capability === 'cross_border_canada' || $capability === 'cross_border_mexico' ) {
					$alias = 'cap_cross_border';
				} else {
					$alias = 'cap_' . $capability;
				}
				
				switch ( $capability ) {
					case 'cross_border_canada':
						// cross_border stores values like "canada,mexico" - check for canada
						$capability_conditions[] = "($alias.meta_value IS NOT NULL AND $alias.meta_value != '' AND $alias.meta_value LIKE '%canada%')";
						break;
					
					case 'cross_border_mexico':
						// cross_border stores values like "canada,mexico" - check for mexico
						$capability_conditions[] = "($alias.meta_value IS NOT NULL AND $alias.meta_value != '' AND $alias.meta_value LIKE '%mexico%')";
						break;
					
					case 'team_driver_enabled':
						// team_driver_enabled stores "on" when enabled
						$capability_conditions[] = "($alias.meta_value = 'on')";
						break;
					
					default:
						// Standard capability check
						$capability_conditions[] = "($alias.meta_value IS NOT NULL AND $alias.meta_value != '' AND $alias.meta_value IN ('1', 'on', 'yes'))";
						break;
				}
			}
			if ( ! empty( $capability_conditions ) ) {
				$where_conditions[] = '(' . implode( ' AND ', $capability_conditions ) . ')';
			}
		}
		
		// Check if we have filtered drivers from distance-based search
		
		
		if ( isset( $args[ 'has_distance_data' ] ) && $args[ 'has_distance_data' ] && ! empty( $args[ 'filtered_drivers' ] ) ) {
			// For distance-based search, return all filtered drivers without pagination
			$filtered_driver_ids = array_keys( $args[ 'filtered_drivers' ] );
			
			if ( ! empty( $filtered_driver_ids ) ) {
				$where_conditions[] = "main.id IN (" . implode( ',', array_map( 'absint', $filtered_driver_ids ) ) . ")";
				// Remove pagination for distance-based results
				$per_page = count( $filtered_driver_ids );
				
				$current_page = 1;
			} else {
				// No drivers found in distance search, return empty results
				return array(
					'results'       => array(),
					'total_pages'   => 0,
					'total_posts'   => 0,
					'current_pages' => 1,
				);
			}
		} else if ( ! isset( $args[ 'my_search' ] ) || empty( $args[ 'my_search' ] ) ) {
			// Regular search without distance filtering (only if no my_search)
			if ( ! empty( $args[ 'status_post' ] ) ) {
				$where_conditions[] = "main.status_post = %s";
				$where_values[]     = $args[ 'status_post' ];
			}
			if ( ! empty( $args[ 'extended_search' ] ) ) {
				// Check if the search term is a vehicle display name and convert to key if found
				$search_term = $args[ 'extended_search' ];
				$vehicle_key = $this->get_vehicle_key_by_value( $search_term );
				
				// If it's a vehicle name, use the key for vehicle_type search, otherwise use original term
				if ( $vehicle_key !== false ) {
					// Search by vehicle key in vehicle_type field
					$where_conditions[] = "vehicle_type.meta_value = %s";
					$where_values[]     = $vehicle_key;
				} else {
					// Check for special search terms
					$search_lower = strtolower( trim( $search_term ) );
					
					// Debug logging
					error_log( 'DEBUG: Search term = "' . $search_term . '", search_lower = "' . $search_lower . '"' );
					
					if ( $search_lower === 'human tested' ) {
						// Search for drivers with human tested MC/DOT
						error_log( 'DEBUG: Found "human tested" condition' );
						$where_conditions[] = "mc_dot_human_tested.meta_value IN ('1', 'on')";
					} else if ( $search_lower === 'dot' ) {
						// Search for drivers with DOT (non-empty text field)
						error_log( 'DEBUG: Found "dot" condition' );
						$where_conditions[] = "dot.meta_value IS NOT NULL AND dot.meta_value != ''";
					} else if ( $search_lower === 'mc' ) {
						// Search for drivers with MC (non-empty text field)
						error_log( 'DEBUG: Found "mc" condition' );
						$where_conditions[] = "mc.meta_value IS NOT NULL AND mc.meta_value != ''";
					} else if ( $search_lower === 'clean background' ) {
						// Search for drivers with clean background
						error_log( 'DEBUG: Found "clean background" condition' );
						$where_conditions[] = "clear_background.meta_value IN ('1', 'on')";
					} else {
						// Check if the search term is a driver status and convert to key if found
						$status_key = $this->searchStatusKey( $search_term, $this->status );
						
						if ( $status_key !== null ) {
							// Search by status key in driver_status field
							$where_conditions[] = "driver_status.meta_value = %s";
							$where_values[]     = $status_key;
						} else {
							// Check if search term is a phone number (formatted or unformatted)
							$phone_detected = $this->is_phone_number( $search_term );
							
							if ( $phone_detected ) {
								// Format phone number and search in driver_phone field
								$formatted_phone = $this->format_phone_number( $search_term );
								$where_conditions[] = "driver_phone.meta_value = %s";
								$where_values[] = $formatted_phone;
							} elseif ( is_numeric( $search_term ) ) {
								// For numeric search that's not a phone, search by ID
								$where_conditions[] = "main.id = %s";
								$where_values[] = $search_term;
							} else {
								// Search only in text fields (name, phone, vehicle, mc, dot), not in ID
								$where_conditions[] = "(" . "driver_name.meta_value LIKE %s OR " . "driver_email.meta_value LIKE %s OR " . "driver_phone.meta_value LIKE %s OR " . "vehicle_type.meta_value LIKE %s OR " . "mc.meta_value LIKE %s OR " . "dot.meta_value LIKE %s" . ")";
								
								$search_value = '%' . $wpdb->esc_like( $search_term ) . '%';
								// Add 5 values for the 5 placeholders in the search condition
								for ( $i = 0; $i < 6; $i ++ ) {
									$where_values[] = $search_value;
								}
							}
						}
					}
				}
			}
		} else {
			// Distance search was performed but no results found - return empty results
			return array(
				'results'       => array(),
				'total_pages'   => 0,
				'total_posts'   => 0,
				'current_pages' => 1,
			);
		}
		
		// Add driver visibility condition based on user role
		$driverHelper    = new TMSDriversHelper();
		$visibility_data = $driverHelper->get_driver_visibility_condition();
		if ( ! empty( $visibility_data[ 'condition' ] ) ) {
			$where_conditions[] = $visibility_data[ 'condition' ];
		}
		
		// Основной запрос
		$sql = "SELECT DISTINCT main.*" . $join_builder . " WHERE 1=1";
		
		// Применяем фильтры к запросу
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Подсчёт общего количества записей с учётом фильтров
		if ( isset( $args[ 'has_distance_data' ] ) && $args[ 'has_distance_data' ] && ! empty( $args[ 'filtered_drivers' ] ) ) {
			// For distance-based search, use the count of filtered drivers
			$total_records = count( $args[ 'filtered_drivers' ] );
		} else {
			// For regular search, we'll count records after the main query to avoid JOIN duplication issues
			$total_records = 0; // Will be updated after main query
		}
		
		// Для обычного поиска сначала получаем общее количество записей
		if ( ! isset( $args[ 'has_distance_data' ] ) || ! $args[ 'has_distance_data' ] || empty( $args[ 'filtered_drivers' ] ) ) {
			// Выполняем запрос без LIMIT для подсчёта общего количества
			$count_sql = "SELECT main.id" . $join_builder . " WHERE 1=1";
			if ( ! empty( $where_conditions ) ) {
				$count_sql .= ' AND ' . implode( ' AND ', $where_conditions );
			}
			$count_sql .= " ORDER BY main.$sort_by $sort_order";
			
			$all_results   = $wpdb->get_results( $wpdb->prepare( $count_sql, ...$where_values ), ARRAY_A );
			$total_records = count( $all_results );
		}
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		// Корректировка: если записей меньше, чем на одну страницу, показываем только одну страницу (или 0, если записей нет)
		if ( $total_records == 0 ) {
			$total_pages = 0;
		} elseif ( $total_pages < 1 ) {
			$total_pages = 1;
		}
		// Смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;
		// Теперь добавляем LIMIT/OFFSET для основного запроса
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
			foreach ( $main_results as $key => $result ) {
				$post_id                             = $result[ 'id' ];
				$main_results[ $key ][ 'meta_data' ] = isset( $meta_data[ $post_id ] ) ? $meta_data[ $post_id ]
					: array();
			}
		}
		
		// Prepare return data
		$return_data = array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
		
		// Add distance data if available
		if ( isset( $args[ 'has_distance_data' ] ) && $args[ 'has_distance_data' ] && ! empty( $args[ 'filtered_drivers' ] ) ) {
			$return_data[ 'id_posts' ]          = $args[ 'filtered_drivers' ];
			$return_data[ 'has_distance_data' ] = true;
			
			
			// Sort results by the order from filtered_drivers
			if ( ! empty( $main_results ) ) {
				$sorted_results      = array();
				$filtered_driver_ids = array_keys( $args[ 'filtered_drivers' ] );
				
				// Create a lookup array for quick access to results by ID
				// Use the first occurrence of each ID to avoid duplicates
				$results_by_id = array();
				foreach ( $main_results as $result ) {
					$id = $result[ 'id' ];
					if ( ! isset( $results_by_id[ $id ] ) ) {
						$results_by_id[ $id ] = $result;
					}
				}
				
				// Sort results according to the order in filtered_drivers
				foreach ( $filtered_driver_ids as $driver_id ) {
					if ( isset( $results_by_id[ $driver_id ] ) ) {
						$sorted_results[] = $results_by_id[ $driver_id ];
					}
				}
				
				// Update the results with sorted order
				$return_data[ 'results' ] = $sorted_results;
			}
		}
		
		return $return_data;
	}
	
	public function send_email_new_driver( $ID_DRIVER ) {
		global $global_options;
		$user_id        = get_current_user_id();
		$project        = get_field( 'current_select', 'user_' . $user_id );
		$select_emails  = get_field_value( $global_options, 'mails_add_driver' );
		$add_new_driver = get_field_value( $global_options, 'add_new_driver' );
		$user_name      = $this->get_user_full_name_by_id( $user_id );
		$driver_object  = $this->get_driver_by_id( $ID_DRIVER );
		$meta           = get_field_value( $driver_object, 'meta' );
		
		$driver_name   = get_field_value( $meta, 'driver_name' ) ?? '';
		$driver_phone  = get_field_value( $meta, 'driver_phone' ) ?? '';
		$vehicle_type  = get_field_value( $meta, 'vehicle_type' ) ?? '';
		$vehicle_model = get_field_value( $meta, 'vehicle_model' ) ?? '';
		$vehicle_make  = get_field_value( $meta, 'vehicle_make' );
		$vehicle_year  = get_field_value( $meta, 'vehicle_year' );
		$dimensions    = get_field_value( $meta, 'dimensions' );
		$payload       = get_field_value( $meta, 'payload' );
		$home_location = get_field_value( $meta, 'home_location' );
		$city          = get_field_value( $meta, 'city' );
		
		$driver_capabilities = array(
			'twic'               => get_field_value( $meta, 'twic' ),
			'tsa'                => get_field_value( $meta, 'tsa_approved' ),
			'hazmat'             => get_field_value( $meta, 'hazmat_certificate' ) || get_field_value( $meta, 'hazmat_endorsement' ),
			'change-9'           => get_field_value( $meta, 'change_9_training' ),
			'tanker-endorsement' => get_field_value( $meta, 'tanker_endorsement' ),
			'background-check'   => get_field_value( $meta, 'background_check' ),
			'liftgate'           => get_field_value( $meta, 'lift_gate' ),
			'pallet-jack'        => get_field_value( $meta, 'pallet_jack' ),
			'dolly'              => get_field_value( $meta, 'dolly' ),
			'ppe'                => get_field_value( $meta, 'ppe' ),
			'e-track'            => get_field_value( $meta, 'e_tracks' ),
			'ramp'               => get_field_value( $meta, 'ramp' ),
			'printer'            => get_field_value( $meta, 'printer' ),
			'sleeper'            => get_field_value( $meta, 'sleeper' ),
			'load-bars'          => get_field_value( $meta, 'load_bars' ),
			'mc'                 => get_field_value( $meta, 'mc' ),
			'dot'                => get_field_value( $meta, 'dot' ),
			'real_id'            => get_field_value( $meta, 'real_id' ),
			'macropoint'         => get_field_value( $meta, 'macro_point' ),
			'tucker-tools'       => get_field_value( $meta, 'trucker_tools' ),
		);
		
		$labels = $this->labels;
		
		$available_labels = array_intersect_key( $labels, array_filter( $driver_capabilities, function( $value ) {
			return ! empty( $value );
		} ) );
		
		$str = '';
		if ( is_array( $available_labels ) && ! empty( $available_labels ) ) {
			$available_labels_str = implode( ', ', $available_labels );
			$str                  = "\nAdditional details: " . $available_labels_str;
		}
		$vehicle_parts   = array();
		$vehicle_parts[] = $this->vehicle[ $vehicle_type ];
		$vehicle_parts[] = $vehicle_make;
		$vehicle_parts[] = $vehicle_model;
		$vehicle_parts[] = $vehicle_year;
		
		// Filter out empty values and join with spaces
		$vehicle_parts = array_filter( $vehicle_parts, function( $part ) {
			return ! empty( trim( $part ) );
		} );
		
		$vehicle_type = implode( ' ', $vehicle_parts );
		
		if ( $add_new_driver ) {
			$link = '<a href="' . $add_new_driver . '?post_id=' . $ID_DRIVER . '">' . '(' . $ID_DRIVER . ') ' . $driver_name . '</a>';
		}
		
		
		$this->email_helper->send_custom_email( $select_emails, array(
			'subject'      => 'New Driver added:' . ' (' . $ID_DRIVER . ') ' . $driver_name,
			'project_name' => $project,
			'subtitle'     => $user_name[ 'full_name' ] . ' has added the new driver to our system ' . $link,
			'message'      => "Contact phone number: " . $driver_phone . "<br>
				Vehicle: " . $vehicle_type . "<br>
				Cargo space details: " . $dimensions . " inches, " . $payload . " lbs.<br>
				Home location: " . $city . ', ' . $home_location . "<br>
				" . $str . "<br><br>
				Don't forget to rate your experience with this driver in our system if you happen to book a load for this unit."
		) );
	}
	
	public function update_driver_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
			] );
			
			$MY_INPUT[ 'post_status' ] = 'publish';
			
			$result = $this->update_driver_status_in_db( $MY_INPUT );
			
			if ( $result ) {
				$this->send_email_new_driver( $MY_INPUT[ 'post_id' ] );
				
				// Sync driver data after successful publication
				$driver_sync_data = $this->get_driver_sync_data( $MY_INPUT[ 'post_id' ] );
				if ( $driver_sync_data ) {
					$this->user_sync_api->sync_user( 'add', $driver_sync_data, 'driver' );
				}
				
				wp_send_json_success( [ 'message' => 'Published', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function update_driver_status_in_db( $data ) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		$user_id    = get_current_user_id();
		
		$driver_id = $data[ 'post_id' ];
		
		
		// Обновляем основную таблицу
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
		);
		
		// Добавляем status_post если он передан
		if ( isset( $data[ 'post_status' ] ) ) {
			$update_params[ 'status_post' ] = $data[ 'post_status' ];
		}
		
		// Обновляем основную таблицу
		$result_main = $wpdb->update( $table_main, $update_params, array( 'id' => $driver_id ) );
		
		// Обновляем статус водителя в мета-таблице если передан
		$result_meta = true;
		if ( isset( $data[ 'driver_status' ] ) ) {
			// Проверяем, существует ли уже запись с этим ключом
			$existing = $wpdb->get_var( $wpdb->prepare( "
				SELECT meta_value FROM {$table_meta} 
				WHERE post_id = %d AND meta_key = 'driver_status'
			", $driver_id ) );
			
			if ( $existing !== null ) {
				// Обновляем существующую запись
				$result_meta = $wpdb->update( $table_meta, array( 'meta_value' => $data[ 'driver_status' ] ), array(
					'post_id'  => $driver_id,
					'meta_key' => 'driver_status'
				), array( '%s' ), array( '%d', '%s' ) );
			} else {
				// Создаем новую запись
				$result_meta = $wpdb->insert( $table_meta, array(
					'post_id'    => $driver_id,
					'meta_key'   => 'driver_status',
					'meta_value' => $data[ 'driver_status' ]
				), array( '%d', '%s', '%s' ) );
			}
		}
		
		// Clear drivers cache when driver status is updated
		$this->clear_drivers_cache();
		
		// Also clear search cache to ensure fresh data
		delete_transient( 'tms_all_available_drivers' );
		
		
		// Возвращаем true если оба обновления прошли успешно
		return ( $result_main !== false && $result_meta !== false );
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
	
	/**
	 * Get driver data for synchronization
	 * 
	 * @param int $driver_id Driver ID
	 * @return array Driver data for sync
	 */
	public function get_driver_sync_data($driver_id) {
		$driver_data = $this->get_driver_by_id($driver_id);
		
		if (!$driver_data) {
			return null;
		}
		
		$meta = $driver_data['meta'];
		
		return array(
			'driver_id' => $driver_id,
			'driver_name' => isset($meta['driver_name']) ? $meta['driver_name'] : '',
			'driver_email' => isset($meta['driver_email']) ? $meta['driver_email'] : '',
			'driver_phone' => isset($meta['driver_phone']) ? $meta['driver_phone'] : '',
			'home_location' => isset($meta['home_location']) ? $meta['home_location'] : '',
			'vehicle_type' => isset($meta['vehicle_type']) ? $meta['vehicle_type'] : '',
			'vin' => isset($meta['vin']) ? $meta['vin'] : ''
		);
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
				'show_phone'                 => isset( $_POST[ 'show_phone' ] )
					? sanitize_text_field( $_POST[ 'show_phone' ] ) : '',
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
				'cross_border'               => isset( $_POST[ 'cross_border' ] ) && is_array( $_POST[ 'cross_border' ] )
					? implode( ',', array_map( 'sanitize_text_field', $_POST[ 'cross_border' ] ) ) : '',
				'owner_type'                 => isset( $_POST[ 'owner_type' ] )
					? sanitize_text_field( $_POST[ 'owner_type' ] ) : '',
				'emergency_contact_name'     => isset( $_POST[ 'emergency_contact_name' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_name' ] ) : '',
				'emergency_contact_phone'    => isset( $_POST[ 'emergency_contact_phone' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_phone' ] ) : '',
				'emergency_contact_relation' => isset( $_POST[ 'emergency_contact_relation' ] )
					? sanitize_text_field( $_POST[ 'emergency_contact_relation' ] ) : '',
				'mc_dot_human_tested'        => isset( $_POST[ 'mc_dot_human_tested' ] )
					? sanitize_text_field( $_POST[ 'mc_dot_human_tested' ] ) : '',
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
				'cross_border'               => isset( $_POST[ 'cross_border' ] ) && is_array( $_POST[ 'cross_border' ] )
					? implode( ',', array_map( 'sanitize_text_field', $_POST[ 'cross_border' ] ) ) : '',
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
				'show_phone'                 => isset( $_POST[ 'show_phone' ] )
					? sanitize_text_field( $_POST[ 'show_phone' ] ) : '',
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
				'mc_dot_human_tested'        => isset( $_POST[ 'mc_dot_human_tested' ] )
					? sanitize_text_field( $_POST[ 'mc_dot_human_tested' ] ) : '',
			
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
				'mc_dot_human_tested',
				'clear_background',
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
				
				// Sync driver update for contact fields (driver_name, driver_email, driver_phone, home_location)
				$driver_sync_data = $this->get_driver_sync_data( $data[ 'driver_id' ] );
				if ( $driver_sync_data ) {
					$this->user_sync_api->sync_user( 'update', $driver_sync_data, 'driver' );
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
			);
			
			$MY_INPUT = filter_var_array( $_POST, [
				"bank_payees" => [ 'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY ]
			] );
			
			$bank_payees = ! empty( $MY_INPUT[ 'bank_payees' ] ) ? implode( ',', $MY_INPUT[ 'bank_payees' ] ) : null;
			
			$data[ 'bank_payees' ] = $bank_payees;
			// At this point, the data is sanitized and ready for further processing or saving to the database
			
			$driver_object = $this->get_driver_by_id( $data[ 'driver_id' ] );
			$meta          = get_field_value( $driver_object, 'meta' );
			$main          = get_field_value( $driver_object, 'main' );
			$post_status   = $main[ 'status_post' ];
			$file_EIN      = get_field_value( $meta, 'ein_file' );
			$file_SSN      = get_field_value( $meta, 'ssn_file' );
			$array_track   = array(
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
				'bank_payees',
			);
			
			if ( $post_status === 'publish' ) {
				// Переменная для хранения результатов изменений
				$changes = $this->get_log_template( $array_track, $meta, $data );
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
				
				if ( ! is_numeric( $file_EIN ) ) {
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
				
				if ( ! is_numeric( $file_SSN ) ) {
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
				'side_door_on'            => sanitize_text_field( get_field_value( $_POST, 'side_door_on' ) ),
				'side_door'               => sanitize_text_field( get_field_value( $_POST, 'side_door_1' ) . ' / ' . get_field_value( $_POST, 'side_door_2' ) ),
				'overall_dimensions'      => sanitize_text_field( get_field_value( $_POST, 'overall_dimensions_1' ) . ' / ' . get_field_value( $_POST, 'overall_dimensions_2' ) . ' / ' . get_field_value( $_POST, 'overall_dimensions_3' ) ),
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
				'dock_high'               => sanitize_text_field( get_field_value( $_POST, 'dock_high' ) ),
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
				
				// Sync driver update for vehicle_type and vin fields
				$driver_sync_data = $this->get_driver_sync_data( $data[ 'driver_id' ] );
				if ( $driver_sync_data ) {
					$this->user_sync_api->sync_user( 'update', $driver_sync_data, 'driver' );
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
			$data = array(
				'driver_id' => isset( $_POST[ 'driver_id' ] ) ? sanitize_textarea_field( $_POST[ 'driver_id' ] ) : '',
			);
			
			$need_check = isset( $_POST[ 'need_check' ] ) ? sanitize_textarea_field( $_POST[ 'need_check' ] ) : false;
			
			if ( $need_check ) {
				$data[ $need_check ] = 'on';
			}
			
			$driver_object = $this->get_driver_by_id( $data[ 'driver_id' ] );
			$meta          = get_field_value( $driver_object, 'meta' );
			$main          = get_field_value( $driver_object, 'main' );
			$post_status   = $main[ 'status_post' ];
			$changes       = '';
			
			$picture_fields = [
				'vehicle_pictures'    => 'Uploaded vehicle pictures',
				'dimensions_pictures' => 'Uploaded dimensions pictures'
			];
			
			foreach ( $picture_fields as $field => $message ) {
				if ( ! empty( $_FILES[ $field ] ) && $_FILES[ $field ][ 'size' ][ 0 ] > 0 ) {
					$data[ $field ] = $this->process_uploaded_files( $field, $meta[ $field ] );
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
				wp_send_json_success( [ 'message' => 'successfully upload', 'id_driver' => $result ] );
			}
			
			wp_send_json_error( [ 'message' => 'Upload error' ] );
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
		
		// Clear drivers cache when new driver is added
		$this->clear_drivers_cache();
		
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
	
	function insert_driver_rating( $driver_id, $name, $time, $reit, $message = '', $order_number = '' ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_raiting;
		
		$data = [
			'driver_id'    => (int) $driver_id,
			'name'         => sanitize_text_field( $name ),
			'time'         => (int) $time,
			'reit'         => (int) $reit,
			'message'      => sanitize_textarea_field( $message ),
			'order_number' => sanitize_text_field( $order_number ),
		];
		
		$formats = [ '%d', '%s', '%d', '%d', '%s', '%s' ];
		
		return $wpdb->insert( $table_name, $data, $formats );
	}
	
	function insert_driver_notice( $driver_id, $name, $date, $message = '', $status = false ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_notice;
		
		$data = [
			'driver_id' => (int) $driver_id,
			'name'      => sanitize_text_field( $name ),
			'date'      => (int) $date,
			'message'   => sanitize_textarea_field( $message ),
			'status'    => (int) $status,
		];
		
		$formats = [ '%d', '%s', '%d', '%s', '%d' ];
		
		return $wpdb->insert( $table_name, $data, $formats );
	}
	
	
	public function update_driver_in_db( $data = [] ) {
		global $wpdb;
		
		
		if ( empty( $data ) ) {
			return false;
		}
		
		// Clear drivers cache when driver data is updated
		$this->clear_drivers_cache();
		
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
		
		if ( isset( $data[ 'current_zipcode' ] ) ) {
			$data_main[ 'updated_zipcode' ] = current_time( 'mysql' );
		}
		
		if ( isset( $data[ 'status_date' ] ) && ! empty( $data[ 'status_date' ] ) ) {
			// Convert date from m/d/Y H:i format to MySQL datetime format
			$date_obj = DateTime::createFromFormat( 'm/d/Y H:i', $data[ 'status_date' ] );
			if ( $date_obj ) {
				$data_main[ 'date_available' ] = $date_obj->format( 'Y-m-d H:i:s' );
			} else {
				// Try alternative format if the first one fails
				$date_obj = DateTime::createFromFormat( 'm/d/Y g:i a', $data[ 'status_date' ] );
				if ( $date_obj ) {
					$data_main[ 'date_available' ] = $date_obj->format( 'Y-m-d H:i:s' );
				}
			}
		}
		
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
	
	public function create_tables() {
		$this->table_driver();
		$this->table_driver_meta();
		$this->table_driver_hold_status();
		$this->register_driver_tables();
	}
	
	public function table_driver() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
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
	
	public function update_user_id_added( $driver_id, $user_id ) {
		global $wpdb;
		
		if ( empty( $driver_id ) || empty( $user_id ) ) {
			return false;
		}
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		return $wpdb->update( $table_name, [ 'user_id_added' => intval( $user_id ) ], [ 'id' => intval( $driver_id ) ], [ '%d' ], [ '%d' ] );
	}
	
	public function update_date_created( $driver_id ) {
		global $wpdb;
		
		if ( empty( $driver_id ) ) {
			return false;
		}
		
		$date_created = date( "Y-m-d H:i:s" );
		$table_name   = $wpdb->prefix . $this->table_main;
		
		return $wpdb->update( $table_name, [ 'date_created' => $date_created ], [ 'id' => intval( $driver_id ) ], [ '%s' ], [ '%d' ] );
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
	
	public function table_driver_hold_status() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . 'driver_hold_status';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			driver_id mediumint(9) NOT NULL,
			dispatcher_id mediumint(9) NOT NULL,
			driver_status varchar(50) NULL DEFAULT NULL,
			update_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_driver_id (driver_id),
			INDEX idx_dispatcher_id (dispatcher_id),
			INDEX idx_update_date (update_date)
		) $charset_collate;";
		
		dbDelta( $sql );
	}
	
	public function find_driver_id_by_meta( $name = '', $phone = '', $email = '' ) {
		global $wpdb;
		
		$meta_table = $wpdb->prefix . $this->table_meta;
		
		$where_clauses = [];
		$params        = [];
		
		if ( ! empty( $name ) ) {
			$where_clauses[] = "(meta_key = 'driver_name' AND meta_value = %s)";
			$params[]        = $name;
		}
		
		if ( ! empty( $phone ) ) {
			$where_clauses[] = "(meta_key = 'driver_phone' AND meta_value = %s)";
			$params[]        = $phone;
		}
		
		if ( ! empty( $email ) ) {
			$where_clauses[] = "(meta_key = 'driver_email' AND meta_value = %s)";
			$params[]        = $email;
		}
		
		if ( empty( $where_clauses ) ) {
			return false; // нет параметров для поиска
		}
		
		$sql = "
		SELECT post_id
		FROM $meta_table
		WHERE " . implode( ' OR ', $where_clauses ) . "
		LIMIT 1
	";
		
		$query     = $wpdb->prepare( $sql, ...$params );
		$driver_id = $wpdb->get_var( $query );
		
		return $driver_id ? (int) $driver_id : false;
	}
	
	public function replace_driver_id( $old_id, $new_id ) {
		global $wpdb;
		
		$main_table   = $wpdb->prefix . $this->table_main;
		$meta_table   = $wpdb->prefix . $this->table_meta;
		$notice_table = $wpdb->prefix . $this->table_notice;
		$rating_table = $wpdb->prefix . $this->table_raiting;
		
		$old_id = (int) $old_id;
		$new_id = (int) $new_id;
		
		if ( ! $old_id || ! $new_id || $old_id === $new_id ) {
			return false;
		}
		
		// 1. Обновление ID в основной таблице
		$wpdb->update( $main_table, [ 'id' => $new_id ], [ 'id' => $old_id ] );
		
		// 2. Обновление post_id в meta-таблице
		$wpdb->update( $meta_table, [ 'post_id' => $new_id ], [ 'post_id' => $old_id ] );
		
		// 3. Обновление driver_id в таблице рейтингов
		$wpdb->update( $rating_table, [ 'driver_id' => $new_id ], [ 'driver_id' => $old_id ] );
		
		// 4. Обновление driver_id в таблице notice
		$wpdb->update( $notice_table, [ 'driver_id' => $new_id ], [ 'driver_id' => $old_id ] );
		
		return true;
	}
	
	
	function register_driver_tables() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$table_rating = $wpdb->prefix . $this->table_raiting;
		$table_notice = $wpdb->prefix . $this->table_notice;
		
		$sql_rating = "CREATE TABLE $table_rating (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		driver_id BIGINT UNSIGNED DEFAULT NULL,
		name VARCHAR(255) NOT NULL,
		time INT(11) NOT NULL,
		reit TINYINT UNSIGNED NOT NULL,
		message TEXT,
		order_number VARCHAR(100),
		PRIMARY KEY  (id),
		KEY idx_driver_id (driver_id)
	) $charset_collate;";
		
		$sql_notice = "CREATE TABLE $table_notice (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		driver_id BIGINT UNSIGNED DEFAULT NULL,
		name VARCHAR(255) NOT NULL,
		date INT(11) NOT NULL,
		message TEXT,
		status TINYINT(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY idx_driver_id (driver_id)
	) $charset_collate;";
		
		dbDelta( $sql_rating );
		dbDelta( $sql_notice );
	}
	
	/**
	 * Optimize drivers tables for better performance with large datasets
	 */
	public function optimize_drivers_tables() {
		// Check nonce for security
		if ( ! wp_verify_nonce( $_POST[ 'tms_optimize_nonce' ], 'tms_optimize_database' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		$optimization_type = sanitize_text_field( $_POST[ 'optimization_type' ] ?? 'indexes' );
		$results           = [];
		
		try {
			if ( $optimization_type === 'full' ) {
				$results = $this->perform_full_drivers_optimization();
			} else {
				$results = $this->perform_fast_drivers_optimization();
			}
			
			wp_send_json_success( $results );
		}
		catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
	
	/**
	 * Perform fast optimization (indexes only)
	 */
	public function perform_fast_drivers_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main drivers table
		$main_table              = $wpdb->prefix . $this->table_main;
		$results[ 'main_table' ] = $this->optimize_drivers_main_table_fast( $main_table );
		
		// Optimize meta table
		$meta_table              = $wpdb->prefix . $this->table_meta;
		$results[ 'meta_table' ] = $this->optimize_drivers_meta_table_fast( $meta_table );
		
		// Optimize rating table
		$rating_table              = $wpdb->prefix . $this->table_raiting;
		$results[ 'rating_table' ] = $this->optimize_drivers_rating_table_fast( $rating_table );
		
		// Optimize notice table
		$notice_table              = $wpdb->prefix . $this->table_notice;
		$results[ 'notice_table' ] = $this->optimize_drivers_notice_table_fast( $notice_table );
		
		return $results;
	}
	
	/**
	 * Perform full optimization (structural changes)
	 */
	public function perform_full_drivers_optimization() {
		global $wpdb;
		$results = [];
		
		// Optimize main drivers table
		$main_table              = $wpdb->prefix . $this->table_main;
		$results[ 'main_table' ] = $this->optimize_drivers_main_table_full( $main_table );
		
		// Optimize meta table
		$meta_table              = $wpdb->prefix . $this->table_meta;
		$results[ 'meta_table' ] = $this->optimize_drivers_meta_table_full( $meta_table );
		
		// Optimize rating table
		$rating_table              = $wpdb->prefix . $this->table_raiting;
		$results[ 'rating_table' ] = $this->optimize_drivers_rating_table_full( $rating_table );
		
		// Optimize notice table
		$notice_table              = $wpdb->prefix . $this->table_notice;
		$results[ 'notice_table' ] = $this->optimize_drivers_notice_table_full( $notice_table );
		
		return $results;
	}
	
	/**
	 * Fast optimization for main drivers table
	 */
	private function optimize_drivers_main_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for better query performance
		$indexes = [
			'idx_user_date_created'     => 'user_id_added, date_created',
			'idx_status_date_available' => 'status_post, date_available',
			'idx_user_status'           => 'user_id_added, status_post',
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
	 * Full optimization for main drivers table
	 */
	private function optimize_drivers_main_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY user_id_added BIGINT UNSIGNED NOT NULL",
			"ALTER TABLE $table_name MODIFY user_id_updated BIGINT UNSIGNED NULL",
			"ALTER TABLE $table_name MODIFY date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
			"ALTER TABLE $table_name MODIFY date_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
			"ALTER TABLE $table_name MODIFY clean_check_date TIMESTAMP NULL DEFAULT NULL",
			"ALTER TABLE $table_name MODIFY updated_zipcode TIMESTAMP NULL DEFAULT NULL",
			"ALTER TABLE $table_name MODIFY date_available TIMESTAMP NULL DEFAULT NULL",
			"ALTER TABLE $table_name MODIFY checked_from_brokersnapshot TIMESTAMP NULL DEFAULT NULL",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_user_date_created'     => 'user_id_added, date_created',
			'idx_status_date_available' => 'status_post, date_available',
			'idx_user_status'           => 'user_id_added, status_post',
			'idx_date_created_status'   => 'date_created, status_post',
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
	 * Fast optimization for drivers meta table
	 */
	private function optimize_drivers_meta_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes for meta queries
		$indexes = [
			'idx_post_meta_key'  => 'post_id, meta_key(191)',
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
	 * Full optimization for drivers meta table
	 */
	private function optimize_drivers_meta_table_full( $table_name ) {
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
			'idx_post_meta_key'       => 'post_id, meta_key(191)',
			'idx_meta_key_value'      => 'meta_key(191), meta_value(191)',
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
	 * Fast optimization for drivers rating table
	 */
	private function optimize_drivers_rating_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes
		$indexes = [
			'idx_driver_time' => 'driver_id, time',
			'idx_driver_reit' => 'driver_id, reit',
			'idx_time_reit'   => 'time, reit',
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
	 * Full optimization for drivers rating table
	 */
	private function optimize_drivers_rating_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY time BIGINT UNSIGNED NOT NULL",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_driver_time'      => 'driver_id, time',
			'idx_driver_reit'      => 'driver_id, reit',
			'idx_time_reit'        => 'time, reit',
			'idx_driver_time_reit' => 'driver_id, time, reit',
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
	 * Fast optimization for drivers notice table
	 */
	private function optimize_drivers_notice_table_fast( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Add composite indexes
		$indexes = [
			'idx_driver_date'   => 'driver_id, date',
			'idx_driver_status' => 'driver_id, status',
			'idx_date_status'   => 'date, status',
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
	 * Full optimization for drivers notice table
	 */
	private function optimize_drivers_notice_table_full( $table_name ) {
		global $wpdb;
		$changes = [];
		
		// Change data types for better performance
		$alter_queries = [
			"ALTER TABLE $table_name MODIFY date BIGINT UNSIGNED NOT NULL",
		];
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		// Add composite indexes
		$indexes = [
			'idx_driver_date'        => 'driver_id, date',
			'idx_driver_status'      => 'driver_id, status',
			'idx_date_status'        => 'date, status',
			'idx_driver_date_status' => 'driver_id, date, status',
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
	 * Конвертирует время из любого часового пояса в время Нью-Йорка
	 *
	 * @param string $input_time Время в формате "m/d/Y H:i" или "Y-m-d H:i:s"
	 *
	 * @return string Время в Нью-Йорке в формате "Y-m-d H:i:s"
	 */
	public function convert_time_to_new_york( $input_time ) {
		if ( empty( $input_time ) ) {
			return '';
		}
		
		try {
			// Создаем объект DateTime из входного времени
			$date_time = new DateTime( $input_time );
			
			// Устанавливаем часовой пояс Нью-Йорка
			$ny_timezone = new DateTimeZone( 'America/New_York' );
			
			// Конвертируем время в Нью-Йорк
			$date_time->setTimezone( $ny_timezone );
			
			// Возвращаем время в формате MySQL
			return $date_time->format( 'Y-m-d H:i:s' );
			
		}
		catch ( Exception $e ) {
			// Если произошла ошибка, возвращаем исходное время
			error_log( 'Error converting time to New York: ' . $e->getMessage() );
			
			return $input_time;
		}
	}
	
	/**
	 * Get driver statistics from rating and notice tables
	 *
	 * @param int $driver_id Driver ID
	 * @param bool $full_data Whether to return full data or just summary
	 *
	 * @return array Array with rating and notice data
	 */
	public function get_driver_statistics( $driver_id, $full_data = false ) {
		global $wpdb;
		
		if ( empty( $driver_id ) || ! is_numeric( $driver_id ) ) {
			return [
				'rating' => [
					'avg_rating' => 0,
					'count'      => 0,
					'data'       => []
				],
				'notice' => [
					'count' => 0,
					'data'  => []
				]
			];
		}
		
		$driver_id    = (int) $driver_id;
		$rating_table = $wpdb->prefix . $this->table_raiting;
		$notice_table = $wpdb->prefix . $this->table_notice;
		
		$result = [
			'rating' => [
				'avg_rating' => 0,
				'count'      => 0,
				'data'       => []
			],
			'notice' => [
				'count' => 0,
				'data'  => []
			]
		];
		
		// Get rating statistics
		$rating_query = $wpdb->prepare( "
			SELECT 
				AVG(reit) as avg_rating,
				COUNT(*) as count
			FROM $rating_table 
			WHERE driver_id = %d
		", $driver_id );
		
		$rating_stats = $wpdb->get_row( $rating_query );
		
		if ( $rating_stats ) {
			$result[ 'rating' ][ 'avg_rating' ] = round( (float) $rating_stats->avg_rating, 2 );
			$result[ 'rating' ][ 'count' ]      = (int) $rating_stats->count;
		}
		
		// Get notice count
		$notice_count_query = $wpdb->prepare( "
			SELECT COUNT(*) as count
			FROM $notice_table 
			WHERE driver_id = %d
		", $driver_id );
		
		$notice_count                  = $wpdb->get_var( $notice_count_query );
		$result[ 'notice' ][ 'count' ] = (int) $notice_count;
		
		// Get full data if requested
		if ( $full_data ) {
			// Get all rating records
			$rating_data_query = $wpdb->prepare( "
				SELECT 
					id,
					name,
					time,
					reit,
					message,
					order_number
				FROM $rating_table 
				WHERE driver_id = %d
				ORDER BY time DESC
			", $driver_id );
			
			$result[ 'rating' ][ 'data' ] = $wpdb->get_results( $rating_data_query, ARRAY_A );
			
			// Get all notice records
			$notice_data_query = $wpdb->prepare( "
				SELECT 
					id,
					name,
					date,
					message,
					status
				FROM $notice_table 
				WHERE driver_id = %d
				ORDER BY date DESC
			", $driver_id );
			
			$result[ 'notice' ][ 'data' ] = $wpdb->get_results( $notice_data_query, ARRAY_A );
		}
		
		return $result;
	}
	
	/**
	 * Add new rating for driver
	 */
	public function add_driver_rating( $driver_id, $rating, $load_number = '', $comments = '' ) {
		global $wpdb;
		
		if ( empty( $driver_id ) || ! is_numeric( $driver_id ) ) {
			return false;
		}
		
		$driver_id = (int) $driver_id;
		$rating    = (int) $rating;
		
		// Validate rating (1-5)
		if ( $rating < 1 || $rating > 5 ) {
			return false;
		}
		
		$current_user_id = get_current_user_id();
		$helper          = new TMSReportsHelper();
		$user_info       = $helper->get_user_full_name_by_id( $current_user_id );
		$user_name       = $user_info ? $user_info[ 'full_name' ] : 'Unknown User';
		
		$time = current_time( 'timestamp' );
		
		return $this->insert_driver_rating( $driver_id, $user_name, $time, $rating, $comments, $load_number );
	}
	
	/**
	 * Add new notice for driver
	 */
	public function add_driver_notice( $driver_id, $message ) {
		global $wpdb;
		
		if ( empty( $driver_id ) || ! is_numeric( $driver_id ) ) {
			return false;
		}
		
		$driver_id = (int) $driver_id;
		
		$current_user_id = get_current_user_id();
		$helper          = new TMSReportsHelper();
		$user_info       = $helper->get_user_full_name_by_id( $current_user_id );
		$user_name       = $user_info ? $user_info[ 'full_name' ] : 'Unknown User';
		
		$date = current_time( 'timestamp' );
		
		return $this->insert_driver_notice( $driver_id, $user_name, $date, $message, 0 );
	}
	
	/**
	 * Update notice status (toggle between 0 and 1)
	 */
	public function update_notice_status( $notice_id ) {
		global $wpdb;
		
		if ( empty( $notice_id ) || ! is_numeric( $notice_id ) ) {
			return false;
		}
		
		$notice_id  = (int) $notice_id;
		$table_name = $wpdb->prefix . $this->table_notice;
		
		// Get current status
		$current_status = $wpdb->get_var( $wpdb->prepare( "
			SELECT status FROM $table_name WHERE id = %d
		", $notice_id ) );
		
		if ( $current_status === null ) {
			return false;
		}
		
		// Toggle status (0 to 1, 1 to 0)
		$new_status = $current_status == 1 ? 0 : 1;
		
		$result = $wpdb->update( $table_name, array( 'status' => $new_status ), array( 'id' => $notice_id ), array( '%d' ), array( '%d' ) );
		
		return $result !== false;
	}
	
	/**
	 * AJAX handler for adding driver rating
	 */
	public function ajax_add_driver_rating() {
		// Check nonce for security
		if ( ! wp_verify_nonce( $_POST[ 'tms_rating_nonce' ], 'tms_add_rating' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		$driver_id   = intval( $_POST[ 'driver_id' ] ?? 0 );
		$rating      = intval( $_POST[ 'rating' ] ?? 0 );
		$load_number = sanitize_text_field( $_POST[ 'load_number' ] ?? '' );
		$comments    = sanitize_textarea_field( $_POST[ 'comments' ] ?? '' );
		
		if ( empty( $driver_id ) || $rating < 1 || $rating > 5 ) {
			wp_send_json_error( 'Invalid data provided' );
		}
		
		$result = $this->add_driver_rating( $driver_id, $rating, $load_number, $comments );
		
		if ( $result ) {
			wp_send_json_success( 'Rating added successfully' );
		} else {
			wp_send_json_error( 'Failed to add rating' );
		}
	}
	
	/**
	 * AJAX handler for adding driver notice
	 */
	public function ajax_add_driver_notice() {
		// Check nonce for security
		if ( ! wp_verify_nonce( $_POST[ 'tms_notice_nonce' ], 'tms_add_notice' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		$driver_id = intval( $_POST[ 'driver_id' ] ?? 0 );
		$message   = sanitize_textarea_field( $_POST[ 'message' ] ?? '' );
		
		if ( empty( $driver_id ) || empty( $message ) ) {
			wp_send_json_error( 'Invalid data provided' );
		}
		
		$result = $this->add_driver_notice( $driver_id, $message );
		
		if ( $result ) {
			wp_send_json_success( 'Notice added successfully' );
		} else {
			wp_send_json_error( 'Failed to add notice' );
		}
	}
	
	/**
	 * AJAX handler for updating notice status
	 */
	public function ajax_update_notice_status() {
		// Check nonce for security
		if ( ! wp_verify_nonce( $_POST[ 'tms_notice_status_nonce' ], 'tms_update_notice_status' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		$notice_id = intval( $_POST[ 'notice_id' ] ?? 0 );
		
		if ( empty( $notice_id ) ) {
			wp_send_json_error( 'Invalid notice ID' );
		}
		
		$result = $this->update_notice_status( $notice_id );
		
		if ( $result ) {
			wp_send_json_success( 'Notice status updated successfully' );
		} else {
			wp_send_json_error( 'Failed to update notice status' );
		}
	}
	
	/**
	 * Get coordinates (lat/lng) using different geocoders with caching
	 *
	 * @param string $address Address to geocode
	 * @param string $geocoder_type 'default' for Pelias or 'here' for Here Maps
	 * @param array $options Additional options (api_key, url_pelias, region_value)
	 *
	 * @return array|false Array with 'lat' and 'lng' or false on error
	 */
	public function get_coordinates_by_address( $address, $geocoder_type = 'default', $options = array() ) {
		global $global_options;
		
		// Get default options from global settings
		$api_key_here_map = get_field_value( $global_options, 'api_key_here_map' );
		$url_pelias       = get_field_value( $global_options, 'url_pelias' );
		
		// Override with passed options
		$api_key_here_map = isset( $options[ 'api_key' ] ) ? $options[ 'api_key' ] : $api_key_here_map;
		$url_pelias       = isset( $options[ 'url_pelias' ] ) ? $options[ 'url_pelias' ] : $url_pelias;
		$region_value     = isset( $options[ 'region_value' ] ) ? $options[ 'region_value' ] : '';
		
		// Create cache key based on address, geocoder type, and options
		$cache_key = $this->generate_coordinates_cache_key( $address, $geocoder_type, $options );
		
		// Try to get from cache first
		$cached_coordinates = get_transient( $cache_key );
		if ( $cached_coordinates !== false ) {
			return $cached_coordinates;
		}
		
		// If not in cache, get from API
		if ( $geocoder_type === 'here' ) {
			$coordinates = $this->get_coordinates_here_maps( $address, $api_key_here_map, $region_value );
		} else {
			$coordinates = $this->get_coordinates_pelias( $address, $url_pelias );
		}
		
		// Cache the result for 30 days (2592000 seconds)
		if ( $coordinates !== false ) {
			set_transient( $cache_key, $coordinates, 30 * DAY_IN_SECONDS );
		}
		
		return $coordinates;
	}
	
	/**
	 * Generate unique cache key for coordinates
	 *
	 * @param string $address Address to geocode
	 * @param string $geocoder_type Type of geocoder
	 * @param array $options Additional options
	 *
	 * @return string Cache key
	 */
	private function generate_coordinates_cache_key( $address, $geocoder_type, $options = array() ) {
		// Normalize address (trim, lowercase for consistency)
		$normalized_address = strtolower( trim( $address ) );
		
		// Create options hash
		$options_hash = '';
		if ( ! empty( $options ) ) {
			$options_hash = md5( serialize( $options ) );
		}
		
		// Create cache key
		$cache_key = 'tms_coordinates_' . $geocoder_type . '_' . md5( $normalized_address ) . '_' . $options_hash;
		
		return $cache_key;
	}
	
	/**
	 * Get coordinates using Here Maps API
	 *
	 * @param string $address Address to geocode
	 * @param string $api_key Here Maps API key
	 * @param string $region_value Additional region value
	 *
	 * @return array|false Array with 'lat' and 'lng' or false on error
	 */
	private function get_coordinates_here_maps( $address, $api_key, $region_value = '' ) {
		if ( empty( $api_key ) ) {
			return false;
		}
		
		// Prepare search text
		$search_text = $address;
		if ( ! empty( $region_value ) ) {
			$search_text .= ' ' . $region_value;
		}
		
		// Build Here Maps API URL
		$url    = 'https://geocode.search.hereapi.com/v1/geocode';
		$params = array(
			'q'      => $search_text,
			'apiKey' => $api_key,
			'gen'    => '9'
		);
		
		$full_url = $url . '?' . http_build_query( $params );
		
		// Make request
		$response = wp_remote_get( $full_url );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		// Check if we have results
		if ( isset( $data[ 'items' ] ) && ! empty( $data[ 'items' ] ) ) {
			$first_item = $data[ 'items' ][ 0 ];
			if ( isset( $first_item[ 'position' ] ) ) {
				return array(
					'lat' => $first_item[ 'position' ][ 'lat' ],
					'lng' => $first_item[ 'position' ][ 'lng' ]
				);
			}
		}
		
		return false;
	}
	
	/**
	 * Get coordinates using Pelias geocoder
	 *
	 * @param string $address Address to geocode
	 * @param string $url_pelias Pelias API URL
	 *
	 * @return array|false Array with 'lat' and 'lng' or false on error
	 */
	private function get_coordinates_pelias( $address, $url_pelias ) {
		if ( empty( $url_pelias ) ) {
			return false;
		}
		
		// Build Pelias API URL
		$url    = rtrim( $url_pelias, '/' ) . '/v1/search';
		$params = array(
			'text' => $address,
			'lang' => 'en-us'
		);
		
		$full_url = $url . '?' . http_build_query( $params );
		
		// Make request
		$response = wp_remote_get( $full_url );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		// Check if we have results
		if ( isset( $data[ 'features' ] ) && ! empty( $data[ 'features' ] ) ) {
			$first_feature = $data[ 'features' ][ 0 ];
			if ( isset( $first_feature[ 'geometry' ][ 'coordinates' ] ) ) {
				$coordinates = $first_feature[ 'geometry' ][ 'coordinates' ];
				
				// Pelias returns [lng, lat] format
				return array(
					'lng' => $coordinates[ 0 ],
					'lat' => $coordinates[ 1 ]
				);
			}
		}
		
		return false;
	}
	
	/**
	 * Clear all drivers cache
	 *
	 * @return bool Success status
	 */
	/**
	 * Clear all drivers related cache
	 *
	 * @return bool Success status
	 */
	public function clear_drivers_cache() {
		global $wpdb;
		
		// Clear the main drivers cache
		$result1 = delete_transient( 'tms_all_available_drivers' );
		
		// Clear any other drivers related transients that might exist
		$prefix  = 'tms_drivers_';
		$sql     = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_' . $prefix ) . '%' );
		$result2 = $wpdb->query( $sql );
		
		// Also delete the timeout entries
		$sql2    = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%' );
		$result3 = $wpdb->query( $sql2 );
		
		return ( $result1 !== false && $result2 !== false && $result3 !== false );
	}
	
	/**
	 * Clear coordinates cache for specific address or all coordinates cache
	 *
	 * @param string $address Optional specific address to clear cache for
	 * @param string $geocoder_type Optional specific geocoder type
	 *
	 * @return bool Success status
	 */
	public function clear_coordinates_cache( $address = '', $geocoder_type = '' ) {
		if ( ! empty( $address ) ) {
			// Clear cache for specific address
			$options   = array();
			$cache_key = $this->generate_coordinates_cache_key( $address, $geocoder_type, $options );
			
			return delete_transient( $cache_key );
		} else {
			// Clear all coordinates cache
			global $wpdb;
			
			// Get all transients that start with our prefix
			$prefix = 'tms_coordinates_';
			$sql    = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_' . $prefix ) . '%' );
			
			$result1 = $wpdb->query( $sql );
			
			// Also delete the timeout entries
			$sql2 = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%' );
			
			$result2 = $wpdb->query( $sql2 );
			
			return ( $result1 !== false && $result2 !== false );
		}
	}
	
	/**
	 * Get driver ratings for AJAX
	 */
	public function get_driver_ratings() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"driver_id" => FILTER_SANITIZE_NUMBER_INT,
			] );
			
			if ( ! isset( $MY_INPUT[ 'driver_id' ] ) || empty( $MY_INPUT[ 'driver_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID not found' ] );
			}
			
			$driver_id = (int) $MY_INPUT[ 'driver_id' ];
			global $wpdb;
			$table_rating = $wpdb->prefix . $this->table_raiting;
			
			$ratings = $wpdb->get_results( $wpdb->prepare( "
				SELECT id, name, time, reit, message, order_number
				FROM $table_rating
				WHERE driver_id = %d
				ORDER BY time DESC
			", $driver_id ) );
			
			if ( $ratings ) {
				wp_send_json_success( $ratings );
			} else {
				wp_send_json_success( [] );
			}
		}
	}
	
	/**
	 * Get driver notices for AJAX
	 */
	public function get_driver_notices() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"driver_id" => FILTER_SANITIZE_NUMBER_INT,
			] );
			
			if ( ! isset( $MY_INPUT[ 'driver_id' ] ) || empty( $MY_INPUT[ 'driver_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID not found' ] );
			}
			
			$driver_id = (int) $MY_INPUT[ 'driver_id' ];
			global $wpdb;
			$table_notice = $wpdb->prefix . $this->table_notice;
			
			$notices = $wpdb->get_results( $wpdb->prepare( "
				SELECT id, name, date, message, status
				FROM $table_notice
				WHERE driver_id = %d
				ORDER BY date DESC
			", $driver_id ) );
			
			if ( $notices ) {
				wp_send_json_success( $notices );
			} else {
				wp_send_json_success( [] );
			}
		}
	}
	
	/**
	 * Get drivers statistics by status
	 *
	 * @return array Array with counts for available, available_on, and not_updated drivers
	 */
	public function get_drivers_available() {
		$time_threshold = defined( 'TIME_AVAILABLE_DRIVER' ) ? TIME_AVAILABLE_DRIVER : '-12 hours';
		
		// Get current time in New York timezone
		$ny_timezone = new DateTimeZone('America/New_York');
		$ny_time = new DateTime('now', $ny_timezone);
		$ny_mysql_time = $ny_time->format('Y-m-d H:i:s');
		
		$b = strtotime( $ny_mysql_time . $time_threshold );
		
		global $wpdb;
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Count available drivers (available, on_hold) with recent updates
		$result = $wpdb->get_results( "
			SELECT COUNT(DISTINCT main.id) as count
			FROM $table_main main
			LEFT JOIN $table_meta status ON main.id = status.post_id AND status.meta_key = 'driver_status'
			WHERE status.meta_value IN('available','on_hold')
			AND main.updated_zipcode >= FROM_UNIXTIME($b)
		" );
		
		// Count available_on drivers with recent updates
		$result3 = $wpdb->get_results( "
			SELECT COUNT(DISTINCT main.id) as count
			FROM $table_main main
			LEFT JOIN $table_meta status ON main.id = status.post_id AND status.meta_key = 'driver_status'
			WHERE status.meta_value IN('available_on')
			AND main.updated_zipcode >= FROM_UNIXTIME($b)
		" );
		
		// Count not updated drivers (not banned, blocked, expired_documents) with old updates or NULL
		$result2 = $wpdb->get_results( "
			SELECT COUNT(DISTINCT main.id) as count
			FROM $table_main main
			LEFT JOIN $table_meta status ON main.id = status.post_id AND status.meta_key = 'driver_status'
			WHERE status.meta_value NOT IN ('blocked','banned', 'expired_documents')
			AND (main.updated_zipcode < FROM_UNIXTIME($b) OR main.updated_zipcode IS NULL)
		" );
		
		return array(
			'available'    => isset( $result[ 0 ]->count ) ? (int) $result[ 0 ]->count : 0,
			'available_on' => isset( $result3[ 0 ]->count ) ? (int) $result3[ 0 ]->count : 0,
			'not_updated'  => isset( $result2[ 0 ]->count ) ? (int) $result2[ 0 ]->count : 0
		);
	}
	
	/**
	 * Get all available drivers with location data
	 *
	 * @param bool $all Get all drivers or only available ones
	 * @param bool $only_updated Get only recently updated drivers
	 *
	 * @return array Array of drivers with location data
	 */
	public function get_all_available_driver( $all = false, $only_updated = false ) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$query_start = "
		SELECT main.id as post_id,
			   lat.meta_value as lat,
			   lng.meta_value as lon,
			   status.meta_value as status,
			   main.updated_zipcode as updated
		FROM $table_main main
		LEFT JOIN $table_meta lat ON main.id = lat.post_id AND lat.meta_key = 'latitude'
		LEFT JOIN $table_meta lng ON main.id = lng.post_id AND lng.meta_key = 'longitude'
		LEFT JOIN $table_meta status ON main.id = status.post_id AND status.meta_key = 'driver_status'
		WHERE 1=1
		";
		
		if ( ! $all ) {
			$query_start .= "
			AND status.meta_value IN('available_on','available','on_hold', 'loaded_enroute')
			";
		} else {
			$query_start .= "
			AND status.meta_value NOT IN ('banned', 'blocked', 'expired_documents')
			";
		}
		
		$query_end = "
		AND lat.meta_value != ''
		AND lng.meta_value != ''
		";
		
		if ( $only_updated ) {
			// Define time threshold for recently updated drivers
			$time_threshold = defined( 'TIME_AVAILABLE_DRIVER' ) ? TIME_AVAILABLE_DRIVER : '-12 hours';
			$b              = strtotime( $time_threshold );
			$query_start    .= "AND main.updated_zipcode >= '" . $b . "'";
			
			// Add UNION for banned drivers if only_updated is true
			$query_end .= "
			UNION SELECT
			main.id as post_id,
			lat.meta_value as lat,
			lng.meta_value as lon,
			status.meta_value as status,
			main.updated_zipcode as updated
			FROM $table_main main
			LEFT JOIN $table_meta lat ON main.id = lat.post_id AND lat.meta_key = 'latitude'
			LEFT JOIN $table_meta lng ON main.id = lng.post_id AND lng.meta_key = 'longitude'
			LEFT JOIN $table_meta status ON main.id = status.post_id AND status.meta_key = 'driver_status'
			WHERE status.meta_value = 'banned'
			AND lat.meta_value != ''
			AND lng.meta_value != ''
			";
		}
		
		$result = $wpdb->get_results( $query_start . $query_end, ARRAY_A );
		
		return $result;
	}
	
	/**
	 * Search for status key by status value
	 *
	 * @param string $search_prepear Status value to search for
	 * @param array $status_search Array of status values
	 *
	 * @return string|null Status key if found, null otherwise
	 */
	private function searchStatusKey( $search_prepear, $status_search ) {
		// Убираем лишние пробелы с начала и конца строки
		$search_prepear = trim( $search_prepear );
		
		// Поиск значения в массиве
		$key = array_search( $search_prepear, $status_search );
		
		// Если значение найдено, возвращаем ключ, иначе возвращаем null
		return $key !== false ? $key : null;
	}
	
	/**
	 * Determine if numeric search term is a phone number or driver ID
	 *
	 * @param string $search_term Numeric search term
	 * @return string 'phone' or 'id'
	 */
	private function determine_search_type( $search_term ) {
		// Remove any non-numeric characters
		$clean_number = preg_replace( '/[^0-9]/', '', $search_term );
		
		// Phone numbers are typically 10 digits (US format)
		// Driver IDs are typically shorter (1-6 digits)
		if ( strlen( $clean_number ) >= 10 ) {
			return 'phone';
		} else {
			return 'id';
		}
	}
	
	/**
	 * Check if search term is a phone number in any format
	 *
	 * @param string $search_term Search term
	 * @return bool True if it's a phone number
	 */
	private function is_phone_number( $search_term ) {
		// Remove any non-numeric characters
		$clean_number = preg_replace( '/[^0-9]/', '', $search_term );
		
		// Check if it's 10 digits (US phone format)
		if ( strlen( $clean_number ) === 10 ) {
			return true;
		}
		
		// Check if it's 11 digits starting with 1
		if ( strlen( $clean_number ) === 11 && substr( $clean_number, 0, 1 ) === '1' ) {
			return true;
		}
		
		// Check if it matches common phone patterns
		$phone_patterns = [
			'/^\(\d{3}\)\s\d{3}-\d{4}$/',  // (123) 456-7890
			'/^\(\d{3}\)\d{3}-\d{4}$/',    // (123)456-7890
			'/^\(\d{3}\)\s\d{7}$/',        // (123) 4567890
			'/^\(\d{3}\)\d{7}$/',          // (123)4567890
			'/^\d{3}-\d{3}-\d{4}$/',       // 123-456-7890
			'/^\d{3}\.\d{3}\.\d{4}$/',     // 123.456.7890
			'/^\d{3}\s\d{3}\s\d{4}$/',     // 123 456 7890
		];
		
		foreach ( $phone_patterns as $pattern ) {
			if ( preg_match( $pattern, $search_term ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Format phone number to standard format (800) 625-7805
	 *
	 * @param string $phone_number Phone number in any format
	 * @return string Formatted phone number
	 */
	private function format_phone_number( $phone_number ) {
		// Remove any non-numeric characters
		$clean_number = preg_replace( '/[^0-9]/', '', $phone_number );
		
		// If it's 10 digits, format as (XXX) XXX-XXXX
		if ( strlen( $clean_number ) === 10 ) {
			return '(' . substr( $clean_number, 0, 3 ) . ') ' . substr( $clean_number, 3, 3 ) . '-' . substr( $clean_number, 6, 4 );
		}
		
		// If it's 11 digits and starts with 1, remove the 1 and format
		if ( strlen( $clean_number ) === 11 && substr( $clean_number, 0, 1 ) === '1' ) {
			$clean_number = substr( $clean_number, 1 );
			return '(' . substr( $clean_number, 0, 3 ) . ') ' . substr( $clean_number, 3, 3 ) . '-' . substr( $clean_number, 6, 4 );
		}
		
		// If it doesn't match expected formats, return as is
		return $phone_number;
	}
	
	/**
	 * Calculate distance between two points using Haversine formula
	 *
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [miles]
	 *
	 * @return float Distance between points in [miles]
	 */
	public function calculate_distance( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 3959
	) {
		// Convert from degrees to radians
		$latFrom = deg2rad( $latitudeFrom );
		$lonFrom = deg2rad( $longitudeFrom );
		$latTo   = deg2rad( $latitudeTo );
		$lonTo   = deg2rad( $longitudeTo );
		
		$lonDelta = $lonTo - $lonFrom;
		$a        = pow( cos( $latTo ) * sin( $lonDelta ), 2 ) + pow( cos( $latFrom ) * sin( $latTo ) - sin( $latFrom ) * cos( $latTo ) * cos( $lonDelta ), 2 );
		$b        = sin( $latFrom ) * sin( $latTo ) + cos( $latFrom ) * cos( $latTo ) * cos( $lonDelta );
		
		$angle = atan2( sqrt( $a ), $b );
		
		return $angle * $earthRadius;
	}
	
	/**
	 * Filter and sort drivers by distance from given coordinates
	 *
	 * @param array $all_drivers Array of all drivers
	 * @param float $search_lat Latitude of search point
	 * @param float $search_lng Longitude of search point
	 * @param float $max_distance Maximum distance in miles
	 * @param array $capabilities Array of required capabilities (optional)
	 *
	 * @return array Filtered and sorted drivers
	 */
	public function filter_and_sort_drivers_by_distance(
		$all_drivers, $search_lat, $search_lng, $max_distance, $capabilities = array()
	) {
		
		
		if ( ! is_array( $all_drivers ) || empty( $all_drivers ) ) {
			error_log( "filter_and_sort_drivers_by_distance - No drivers to process" );
			
			return array();
		}
		
		$valid_drivers = array();
		
		$processed_count       = 0;
		$within_distance_count = 0;
		
		// Check driver visibility based on user role
		$driverHelper = new TMSDriversHelper();
		
		foreach ( $all_drivers as $driver ) {
			$processed_count ++;
			$lat_t   = floatval( $driver[ 'lat' ] );
			$lon_t   = floatval( $driver[ 'lon' ] );
			$updated = $driver[ 'updated' ];
			$status  = $driver[ 'status' ];
			
			// Check if current user can see this driver based on status
			if ( ! $driverHelper->can_see_driver_with_status( $status ) ) {
				continue; // Skip this driver if user can't see it
			}
			
			// Calculate distance
			$distance = $this->calculate_distance( $search_lat, $search_lng, $lat_t, $lon_t );
			
			// Check if driver is within specified distance
			if ( $distance <= $max_distance ) {
				$within_distance_count ++;
				// Check capabilities if specified
				$has_required_capabilities = true;
				if ( ! empty( $capabilities ) && is_array( $capabilities ) ) {
					$has_required_capabilities = $this->check_driver_capabilities( $driver[ 'post_id' ], $capabilities );
				}
				
				if ( $has_required_capabilities ) {
					$valid_drivers[ $driver[ 'post_id' ] ] = array(
						'updated'  => $updated,
						'distance' => round( $distance, 2 ),
						'lat'      => $lat_t,
						'lon'      => $lon_t,
						'status'   => $status,
						'post_id'  => $driver[ 'post_id' ]
					);
				} else {
					error_log( "Driver " . $driver[ 'post_id' ] . " failed capabilities check but is within distance" );
				}
			}
		}
		
		// Sort by distance (closest first)
		uasort( $valid_drivers, function( $item1, $item2 ) {
			return $item1[ 'distance' ] <=> $item2[ 'distance' ];
		} );
		
		
		return $valid_drivers;
	}
	
	/**
	 * Get driver locations for mapping
	 *
	 * @param array $valid_drivers Filtered drivers
	 * @param float $search_lat Search latitude
	 * @param float $search_lng Search longitude
	 *
	 * @return array Array with start location and driver locations
	 */
	public function get_driver_locations_for_map( $valid_drivers, $search_lat, $search_lng ) {
		$driver_locations = array();
		
		foreach ( $valid_drivers as $driver ) {
			$driver_locations[] = array(
				"lat"      => (float) $driver[ 'lat' ],
				"lng"      => (float) $driver[ 'lon' ],
				"distance" => $driver[ 'distance' ],
				"status"   => $driver[ 'status' ],
				"post_id"  => $driver[ 'post_id' ]
			);
		}
		
		$start_location = array(
			array(
				"lat" => (float) $search_lat,
				"lng" => (float) $search_lng
			)
		);
		
		return array(
			'start_location'   => $start_location,
			'driver_locations' => $driver_locations
		);
	}
	
	/**
	 * Sort drivers by status priority
	 *
	 * @param array $valid_drivers Array of valid drivers
	 * @param array $search_coordinates Search coordinates (optional)
	 *
	 * @return array Sorted drivers by status priority
	 */
	public function sort_drivers_by_status_priority( $valid_drivers, $search_coordinates = null ) {
		if ( ! is_array( $valid_drivers ) || empty( $valid_drivers ) ) {
			return $valid_drivers;
		}
		
		// Define time threshold for recently updated drivers
		$time_threshold = defined( 'TIME_AVAILABLE_DRIVER' ) ? TIME_AVAILABLE_DRIVER : '-12 hours';
		$time           = strtotime( $time_threshold );
		
		// Filter out drivers with null distances
		$last_filter_array = array();
		foreach ( $valid_drivers as $key => $driver ) {
			if ( isset( $driver[ 'real_distance' ] ) && is_numeric( $driver[ 'real_distance' ] ) ) {
				$last_filter_array[ $key ] = $driver;
			}
		}
		
		// Separate drivers by status and update flag
		$available_arr        = array();
		$available_on_arr     = array();
		$available_others_arr = array();
		
		foreach ( $last_filter_array as $key => $driver ) {
			$updated = true;
			
			// Check if driver was updated recently
			if ( isset( $driver[ 'updated' ] ) ) {
				$driver_updated_time = strtotime( $driver[ 'updated' ] );
				if ( $driver_updated_time && $driver_updated_time < $time ) {
					$updated = false;
				}
			}
			
			
			// Add Google Maps link
			$link = "";
			if ( $search_coordinates && isset( $search_coordinates[ 'lat' ] ) && isset( $search_coordinates[ 'lng' ] ) ) {
				$link = "https://www.google.com/maps/dir/?api=1&origin=" . $search_coordinates[ 'lat' ] . "," . $search_coordinates[ 'lng' ] . "&destination=" . $driver[ 'lat' ] . "," . $driver[ 'lon' ] . "&travelmode=driving";
			}
			$driver_data = array(
				'air_distance' => $driver[ 'distance' ],
				'distance'     => $driver[ 'real_distance' ],
				'status'       => $driver[ 'status' ],
				'updated'      => $updated,
				'air_mile'     => false, // We're using real distances now
				'link'         => $link,
				'lat'          => $driver[ 'lat' ],
				'lon'          => $driver[ 'lon' ],
				'post_id'      => $driver[ 'post_id' ]
			);
			
			// Clean status string (remove extra spaces)
			$clean_status = trim( $driver[ 'status' ] );
			
			// Правильная логика сортировки:
			// 1. available и updated = true
			// 2. available_on и updated = true  
			// 3. все остальные
			if ( $updated === true && $clean_status === 'available' ) {
				$available_arr[ $key ] = $driver_data;
			} elseif ( $updated === true && ( $clean_status === 'available_on' || $clean_status === 'loaded_enroute' ) ) {
				$available_on_arr[ $key ] = $driver_data;
			} else {
				// Все остальные: on_hold, not_available, или любой статус с updated = false
				$available_others_arr[ $key ] = $driver_data;
			}
		}
		
		// Sort each group by distance
		uasort( $available_arr, function( $item1, $item2 ) {
			return $item1[ 'distance' ] <=> $item2[ 'distance' ];
		} );
		
		uasort( $available_on_arr, function( $item1, $item2 ) {
			return $item1[ 'distance' ] <=> $item2[ 'distance' ];
		} );
		
		uasort( $available_others_arr, function( $item1, $item2 ) {
			return $item1[ 'distance' ] <=> $item2[ 'distance' ];
		} );
		
		// Combine arrays in priority order
		$order_status = array();
		
		// First: available and on_hold drivers (updated)
		foreach ( $available_arr as $key => $val ) {
			$order_status[ $key ] = $val;
		}
		
		// Second: available_on and loaded_enroute drivers (updated)
		foreach ( $available_on_arr as $key => $val ) {
			$order_status[ $key ] = $val;
		}
		
		// Third: all other drivers (not recently updated)
		foreach ( $available_others_arr as $key => $val ) {
			$order_status[ $key ] = $val;
		}
		
		return $order_status;
	}
	
	/**
	 * Check if driver has required capabilities
	 *
	 * @param int $driver_id Driver ID
	 * @param array $required_capabilities Array of required capabilities
	 *
	 * @return bool True if driver has all required capabilities
	 */
	private function check_driver_capabilities( $driver_id, $required_capabilities ) {
		global $wpdb;
		
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		
		// Prepare meta keys for database query (handle special cases)
		$meta_keys_to_query = array();
		foreach ( $required_capabilities as $capability ) {
			if ( $capability === 'cross_border_canada' || $capability === 'cross_border_mexico' ) {
				$meta_keys_to_query[] = 'cross_border';
			} else {
				$meta_keys_to_query[] = $capability;
			}
		}
		
		// Remove duplicates
		$meta_keys_to_query = array_unique( $meta_keys_to_query );
		
		// Get driver capabilities from database
		$capabilities_sql = $wpdb->prepare( "SELECT meta_key, meta_value FROM $table_meta
			WHERE post_id = %d AND meta_key IN (" . implode( ',', array_fill( 0, count( $meta_keys_to_query ), '%s' ) ) . ")", array_merge( array( $driver_id ), $meta_keys_to_query ) );
		
		$driver_capabilities = $wpdb->get_results( $capabilities_sql, ARRAY_A );
		
		
		// Convert to associative array
		$capabilities_map = array();
		foreach ( $driver_capabilities as $cap ) {
			$capabilities_map[ $cap[ 'meta_key' ] ] = $cap[ 'meta_value' ];
		}
		
		
		// Check if driver has all required capabilities
		foreach ( $required_capabilities as $capability ) {
			$has_capability = false;
			
			
			switch ( $capability ) {
				case 'cross_border_canada':
					// cross_border stores values like "canada,mexico" - check for canada
					if ( isset( $capabilities_map[ 'cross_border' ] ) && ! empty( $capabilities_map[ 'cross_border' ] ) ) {
						$cross_border_values = array_map( 'trim', explode( ',', $capabilities_map[ 'cross_border' ] ) );
						$has_capability      = in_array( 'canada', $cross_border_values, true );
					}
					break;
				
				case 'cross_border_mexico':
					// cross_border stores values like "canada,mexico" - check for mexico
					if ( isset( $capabilities_map[ 'cross_border' ] ) && ! empty( $capabilities_map[ 'cross_border' ] ) ) {
						$cross_border_values = array_map( 'trim', explode( ',', $capabilities_map[ 'cross_border' ] ) );
						$has_capability      = in_array( 'mexico', $cross_border_values, true );
					}
					break;
				
				case 'team_driver_enabled':
					// team_driver_enabled stores "on" when enabled
					$has_capability = isset( $capabilities_map[ $capability ] ) && ! empty( $capabilities_map[ $capability ] ) && $capabilities_map[ $capability ] === 'on';
					break;
				
				default:
					// Standard capability check
					$has_capability = isset( $capabilities_map[ $capability ] ) && ! empty( $capabilities_map[ $capability ] ) && in_array( $capabilities_map[ $capability ], array(
							'1',
							'on',
							'yes'
						) );
					break;
			}
			
			if ( ! $has_capability ) {
				return false;
			}
		}
		
		return true;
	}
	
	// Добавляю функцию сортировки для обычного поиска
	public function sort_drivers_by_status_priority_for_regular_search( $drivers ) {
		if ( ! is_array( $drivers ) || empty( $drivers ) ) {
			return $drivers;
		}
		
		$time         = strtotime( current_time( 'mysql' ) . '-12 hours' );
		$available    = [];
		$available_on = [];
		$others       = [];
		
		foreach ( $drivers as $driver ) {
			$meta            = isset( $driver[ 'meta_data' ] ) ? $driver[ 'meta_data' ] : [];
			$status          = isset( $meta[ 'driver_status' ] ) ? trim( $meta[ 'driver_status' ] ) : '';
			$updated_zipcode = isset( $driver[ 'updated_zipcode' ] ) ? $driver[ 'updated_zipcode' ] : '';
			$updated         = true;
			if ( $updated_zipcode ) {
				$updated_time = strtotime( $updated_zipcode );
				if ( $updated_time && $time >= $updated_time ) {
					$updated = false;
				}
			}
			if ( $updated === true && $status === 'available' ) {
				$available[] = $driver;
			} elseif ( $updated === true && ( $status === 'available_on' || $status === 'loaded_enroute' ) ) {
				$available_on[] = $driver;
			} else {
				$others[] = $driver;
			}
		}
		
		// Можно добавить дополнительную сортировку внутри групп, если нужно (например, по времени или расстоянию)
		return array_merge( $available, $available_on, $others );
	}
	
	/**
	 * AJAX обработчик для удержания/освобождения водителя
	 */
	public function hold_driver_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			global $wpdb;
			
			// Получаем данные запроса
			$MY_INPUT = filter_var_array( $_POST, [
				"id_driver"    => FILTER_SANITIZE_NUMBER_INT,
				"id_user"      => FILTER_SANITIZE_NUMBER_INT,
				'hold_user_id' => FILTER_SANITIZE_NUMBER_INT
			] );
			
			$driver_id     = $MY_INPUT[ 'id_driver' ];
			$dispatcher_id = $MY_INPUT[ 'id_user' ];
			$hold_user_id  = $MY_INPUT[ 'hold_user_id' ];
			
			// Получаем текущий статус водителя
			$driver_status = $this->get_driver_status_by_id( $driver_id );
			
			// Проверяем, не удерживается ли водитель другим диспетчером
			$driver_holded = $this->check_driver_on_hold( $driver_id );
			
			if ( ! is_null( $driver_holded ) && (int) $driver_holded->dispatcher_id !== (int) $dispatcher_id ) {
				wp_send_json_error( 'This driver is already on hold by another dispatcher' );
			}
			
			if ( $hold_user_id ) {
				// Освобождаем водителя
				$hold_record = $this->check_driver_on_hold( $driver_id );
				if ( $hold_record && $hold_record->driver_status ) {
					// Проверяем, что восстановленный статус не on_hold
					$restore_status = $hold_record->driver_status;
					if ( $restore_status === 'on_hold' ) {
						// Если в таблице холда оказался статус on_hold, устанавливаем available
						$restore_status = 'available';
					}
					
					// Восстанавливаем статус из базы данных
					$this->update_driver_status_in_db( array(
						'post_id'       => $driver_id,
						'driver_status' => $restore_status
					) );
				} else {
					// Если нет записи в таблице холда, устанавливаем available
					$this->update_driver_status_in_db( array(
						'post_id'       => $driver_id,
						'driver_status' => 'available'
					) );
				}
				
				$this->delete_hold_driver_by_dispatcher_id( $hold_user_id, $driver_id );
				
				// Clear drivers cache when driver is released
				$this->clear_drivers_cache();
				
				wp_send_json_success( 'Driver released' );
			} else {
				// Удерживаем водителя
				$count = $this->get_count_hold_driver( $dispatcher_id );
				
				if ( $count < 3 ) {
					// Проверяем, не находится ли водитель уже на удержании
					$existing_hold = $this->check_driver_on_hold( $driver_id );
					
					if ( $existing_hold && (int) $existing_hold->dispatcher_id === (int) $dispatcher_id ) {
						// Водитель уже удерживается этим диспетчером - продлеваем время удержания
						$this->extend_driver_hold_time( $driver_id, $dispatcher_id );
						wp_send_json_success( 'Hold time extended' );
					} else {
						// Сохраняем оригинальный статус (не on_hold)
						$original_status = $driver_status[ 'driver_status' ];
						
						// Если текущий статус уже on_hold, получаем оригинальный статус из таблицы холда
						if ( $original_status === 'on_hold' && $existing_hold ) {
							$original_status = $existing_hold->driver_status ?: 'available';
						}
						
						// Устанавливаем статус on_hold
						$this->update_driver_status_in_db( array(
							'post_id'       => $driver_id,
							'driver_status' => 'on_hold'
						) );
						
						// Записываем в таблицу удержания с оригинальным статусом
						$this->add_driver_hold_status( $driver_id, $dispatcher_id, array(
							'driver_status' => $original_status
						) );
						
						// Clear drivers cache when driver is put on hold
						$this->clear_drivers_cache();
						
						wp_send_json_success( 'Driver put on hold' );
					}
				}
				wp_send_json_error( 'Hold limit exceeded - maximum 3 drivers' );
			}
		} else {
			wp_send_json_error( 'Invalid request' );
		}
	}
	
	/**
	 * Добавляет запись об удержании водителя
	 */
	private function add_driver_hold_status( $driver_id, $dispatcher_id, $driver_data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		
		// Используем WordPress время и добавляем 15 минут
		$current_time = current_time( 'mysql' );
		$new_time     = date( 'Y-m-d H:i:s', strtotime( $current_time ) + ( $this->hold_time * 60 ) );
		
		$result = $wpdb->insert( $table_name, array(
			'driver_id'     => $driver_id,
			'dispatcher_id' => $dispatcher_id,
			'driver_status' => $driver_data[ 'driver_status' ] ?? null,
			'update_date'   => $new_time,
		) );
		
		// Clear cache when hold is added
		if ( $result ) {
			delete_transient( 'tms_all_available_drivers' );
		}
		
		return $result;
	}
	
	/**
	 * Проверяет, удерживается ли водитель
	 */
	private function check_driver_on_hold( $driver_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		
		$result = $wpdb->get_row( $wpdb->prepare( "
			SELECT * FROM {$table_name} 
			WHERE driver_id = %d 
			ORDER BY update_date DESC 
			LIMIT 1
		", $driver_id ) );
		
		return $result;
	}
	
	/**
	 * Получает информацию об удержании водителя (публичный метод)
	 */
	public function get_driver_hold_info( $driver_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		
		$result = $wpdb->get_row( $wpdb->prepare( "
			SELECT * FROM {$table_name} 
			WHERE driver_id = %d 
			ORDER BY update_date DESC 
			LIMIT 1
		", $driver_id ) );
		
		
		if ( $result ) {
			// Проверяем, не истекло ли время удержания
			$current_time = current_time( 'mysql' );
			$hold_expires = $result->update_date;
			
			if ( $current_time > $hold_expires ) {
				// Время удержания истекло
				return null;
			}
			
			// Вычисляем оставшееся время
			$time_diff    = strtotime( $hold_expires ) - strtotime( $current_time );
			$minutes_left = max( 0, round( $time_diff / 60 ) );
			
			// Получаем имя диспетчера
			$helper          = new TMSReportsHelper();
			$dispatcher_info = $helper->get_user_full_name_by_id( $result->dispatcher_id );
			$dispatcher_name = $dispatcher_info ? $dispatcher_info[ 'full_name' ] : 'Unknown';
			
			
			return array(
				'dispatcher_id'   => $result->dispatcher_id,
				'dispatcher_name' => $dispatcher_name,
				'hold_expires'    => $hold_expires,
				'minutes_left'    => $minutes_left,
				'original_status' => $result->driver_status
			);
		}
		
		return null;
	}
	
	/**
	 * Получает всех водителей на холде с полной информацией
	 */
	public function get_drivers_on_hold() {
		global $wpdb;
		
		$table_hold      = $wpdb->prefix . 'driver_hold_status';
		$current_user_id = get_current_user_id();
		
		// Получаем ID водителей на холде для текущего пользователя (только активные холды)
		$current_time = current_time( 'mysql' );
		$driver_ids   = $wpdb->get_col( $wpdb->prepare( "
			SELECT driver_id 
			FROM {$table_hold} 
			WHERE dispatcher_id = %d 
			AND update_date > %s
			AND update_date != '1970-01-01 00:00:00'
			ORDER BY update_date ASC
		", $current_user_id, $current_time ) );
		
		
		if ( empty( $driver_ids ) ) {
			return array();
		}
		
		// Получаем полную информацию о водителях используя существующий метод
		$args = array(
			'status_post' => 'publish',
			'id_posts'    => $driver_ids
		);
		
		$drivers_data = $this->get_table_items_search( $args );
		$results      = isset( $drivers_data[ 'results' ] ) ? $drivers_data[ 'results' ] : array();
		
		$drivers_on_hold = array();
		
		foreach ( $results as $driver ) {
			// Проверяем, что этот водитель действительно в списке запрошенных ID
			if ( ! in_array( $driver[ 'id' ], $driver_ids ) ) {
				continue;
			}
			
			// Получаем информацию о холде для этого водителя
			$hold_info = $this->get_driver_hold_info( $driver[ 'id' ] );
			
			// Добавляем только если холд активен
			if ( $hold_info ) {
				$driver[ 'hold_info' ] = $hold_info;
				$drivers_on_hold[]     = $driver;
			}
		}
		
		
		return $drivers_on_hold;
	}
	
	/**
	 * Получает статус водителя по ID
	 */
	private function get_driver_status_by_id( $driver_id ) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Получаем данные из основной таблицы
		$main_data = $wpdb->get_row( $wpdb->prepare( "
			SELECT 
				id,
				date_available
			FROM {$table_main}
			WHERE id = %d
		", $driver_id ), ARRAY_A );
		
		// Получаем статус из мета-таблицы
		$status_data = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_value
			FROM {$table_meta}
			WHERE post_id = %d AND meta_key = 'driver_status'
		", $driver_id ) );
		
		if ( $main_data ) {
			return array(
				'id'             => $main_data[ 'id' ],
				'date_available' => $main_data[ 'date_available' ],
				'driver_status'  => $status_data ?: ''
			);
		}
		
		return array();
	}
	
	/**
	 * Удаляет удержание водителя по ID диспетчера
	 */
	private function delete_hold_driver_by_dispatcher_id( $dispatcher_id, $driver_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		
		return $wpdb->delete( $table_name, array(
			'dispatcher_id' => $dispatcher_id,
			'driver_id'     => $driver_id
		), array( '%d', '%d' ) );
	}
	
	/**
	 * Получает количество удерживаемых водителей диспетчером
	 */
	private function get_count_hold_driver( $dispatcher_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		
		$result = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(id) as holded
			FROM {$table_name}
			WHERE dispatcher_id = %d
		", $dispatcher_id ) );
		
		return (int) $result;
	}
	
	/**
	 * Продлевает время удержания водителя
	 */
	private function extend_driver_hold_time( $driver_id, $dispatcher_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		
		// Используем WordPress время и добавляем 15 минут
		$current_time = current_time( 'mysql' );
		$new_time     = date( 'Y-m-d H:i:s', strtotime( $current_time ) + ( $this->hold_time * 60 ) );
		
		$result = $wpdb->update( $table_name, array( 'update_date' => $new_time ), array(
			'driver_id'     => $driver_id,
			'dispatcher_id' => $dispatcher_id
		), array( '%s' ), array( '%d', '%d' ) );
		
		// Clear drivers cache when hold time is extended
		$this->clear_drivers_cache();
		
		// Also clear search cache
		delete_transient( 'tms_all_available_drivers' );
		
		return $result;
	}
	
	/**
	 * Cron функция для удаления истекших удержаний
	 */
	public function cron_delete_expired_holds() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'driver_hold_status';
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Получаем только истекшие удержания (время истечения уже прошло)
		$current_wp_time = current_time( 'mysql' );
		
		$expired_holds = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$table_name}
			WHERE update_date < %s
		", $current_wp_time ) );
		
		// Проверяем, что результат не null
		if ( $expired_holds === null ) {
			return 0;
		}
		
		$status_updated = false;
		
		foreach ( $expired_holds as $hold ) {
			// Восстанавливаем старый статус водителя из базы данных
			if ( $hold->driver_status ) {
				// Проверяем, что восстановленный статус не on_hold
				$restore_status = $hold->driver_status;
				if ( $restore_status === 'on_hold' ) {
					// Если в таблице холда оказался статус on_hold, устанавливаем available
					$restore_status = 'available';
				}
				
				$this->update_driver_status_in_db( array(
					'post_id'       => $hold->driver_id,
					'driver_status' => $restore_status
				) );
				$status_updated = true;
			} else {
				// Если нет статуса в таблице холда, устанавливаем available
				$this->update_driver_status_in_db( array(
					'post_id'       => $hold->driver_id,
					'driver_status' => 'available'
				) );
				$status_updated = true;
			}
			
			// Удаляем запись об удержании
			$wpdb->delete( $table_name, array( 'id' => $hold->id ) );
		}
		
		// Clear drivers cache if any status was updated
		if ( $status_updated ) {
			$this->clear_drivers_cache();
			
			// Clear search cache as well
			delete_transient( 'tms_all_available_drivers' );
		}
		
		return count( $expired_holds ?: array() );
	}
	
	/**
	 * Инициализация cron задачи
	 */
	public function init_cron() {
		// Добавляем расписание каждую минуту
		add_filter( 'cron_schedules', function( $schedules ) {
			if ( ! isset( $schedules[ '1min' ] ) ) {
				$schedules[ '1min' ] = array(
					'interval' => 60,
					'display'  => __( 'Once every minute' )
				);
			}
			
			return $schedules;
		} );
		
		// Добавляем обработчик
		add_action( 'driver_hold_cleanup_hook', array( $this, 'cron_delete_expired_holds' ) );
		
		// Регистрируем cron задачу
		if ( ! wp_next_scheduled( 'driver_hold_cleanup_hook' ) ) {
			wp_schedule_event( time(), '1min', 'driver_hold_cleanup_hook' );
		}
	}
	
	public function update_clean_background() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"driver_id"       => FILTER_SANITIZE_NUMBER_INT,
				"checkbox_status" => FILTER_SANITIZE_STRING,
			] );
			
			if ( ! isset( $MY_INPUT[ 'driver_id' ] ) || empty( $MY_INPUT[ 'driver_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID not found' ] );
			}
			
			$driver_id = (int) $MY_INPUT[ 'driver_id' ];
			
			global $wpdb;
			$table_main = $wpdb->prefix . $this->table_main;
			
			// Update clean_check_date in main table with current time
			$current_time = current_time( 'mysql' );
			$update_data  = array(
				'clean_check_date' => $current_time,
			);
			
			$result = $wpdb->update( $table_main, $update_data, array( 'id' => $driver_id ) );
			
			// Update clear_background in meta table based on checkbox status
			$checkbox_status = isset( $MY_INPUT[ 'checkbox_status' ] ) ? $MY_INPUT[ 'checkbox_status' ] : '';
			$meta_data       = array(
				'clear_background' => $checkbox_status,
			);
			$this->update_post_meta_data( $driver_id, $meta_data );
			
			if ( $result !== false ) {
				// Clear drivers cache when driver data is updated
				$this->clear_drivers_cache();
				
				$formatted_date = date( "m/d/Y", strtotime( $current_time ) );
				
				wp_send_json_success( array(
					'date'    => $formatted_date,
					'message' => 'Clean background check date updated successfully'
				) );
			} else {
				wp_send_json_error( [ 'message' => 'Failed to update clean background check date' ] );
			}
		}
	}
	
	public function update_background_check_date() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"driver_id"       => FILTER_SANITIZE_NUMBER_INT,
				"checkbox_status" => FILTER_SANITIZE_STRING,
			] );
			
			if ( ! isset( $MY_INPUT[ 'driver_id' ] ) || empty( $MY_INPUT[ 'driver_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID not found' ] );
			}
			
			$driver_id = (int) $MY_INPUT[ 'driver_id' ];
			
			// Update background_check and background_date in meta table
			$checkbox_status = isset( $MY_INPUT[ 'checkbox_status' ] ) ? $MY_INPUT[ 'checkbox_status' ] : '';
			$current_date    = date( "m/d/Y" );
			
			$meta_data = array(
				'background_check' => $checkbox_status,
				'background_date'  => $current_date,
			);
			$this->update_post_meta_data( $driver_id, $meta_data );
			
			// Clear drivers cache when driver data is updated
			$this->clear_drivers_cache();
			
			wp_send_json_success( array(
				'date'    => $current_date,
				'message' => 'Background check date updated successfully'
			) );
		}
	}
	
	public function update_driver_zipcode_date() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"driver_id" => FILTER_SANITIZE_NUMBER_INT,
			] );
			
			if ( ! isset( $MY_INPUT[ 'driver_id' ] ) || empty( $MY_INPUT[ 'driver_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Driver ID not found' ] );
			}
			
			$driver_id = (int) $MY_INPUT[ 'driver_id' ];
			
			global $wpdb;
			$table_main = $wpdb->prefix . $this->table_main;
			
			// Update updated_zipcode in main table with current time
			$current_time = current_time( 'mysql' );
			$update_data  = array(
				'updated_zipcode' => $current_time,
			);
			
			$result = $wpdb->update( $table_main, $update_data, array( 'id' => $driver_id ) );
			
			if ( $result !== false ) {
				// Clear drivers cache when driver data is updated
				$this->clear_drivers_cache();
				
				$formatted_date = date( "m/d/Y H:i:s", strtotime( $current_time ) );
				
				wp_send_json_success( array(
					'date'    => $formatted_date,
					'message' => 'Driver zipcode date updated successfully'
				) );
			} else {
				wp_send_json_error( [ 'message' => 'Failed to update driver zipcode date' ] );
			}
		}
	}
}