<?php
/**
 * Initial setup actions for site
 *
 * @package WP-rock
 */

/*Collect all ACF option fields to global variable. */
global $global_options;
const TIME_AVAILABLE_DRIVER = '-12 hours';

if ( function_exists( 'get_fields' ) ) {
	if ( function_exists( 'pll_current_language' ) ) {
		// @codingStandardsIgnoreStart
		$locale = get_locale();
		// @codingStandardsIgnoreEnd
		$global_options = get_fields( 'theme-general-settings_' . $locale );
	} else {
		$global_options = get_fields( 'theme-general-settings' );
	}
}

show_admin_bar( false );

/**
 * Check field and return its value or return null.
 *
 * @param {array}  $data_arr - Array to check and return data.
 * @param {string} $key      - key that should be found in array.
 *
 * @return mixed|null
 */
function get_field_value( $data_arr, $key ) {
	if ( isset( $data_arr[ $key ] ) ) {
		$value = $data_arr[ $key ];
		
		// Убираем слеши, если значение является строкой
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
	
	return null;
}


/**
 * Main theme's class init
 */
$wp_rock = new WP_Rock();
add_action( 'after_setup_theme', array( $wp_rock, 'px_site_setup' ) );

$reports = new TMSReports();
$reports->init();

$reports = new TMSReportsFlt();
$reports->init();

$users = new TMSUsers();
$users->init();

$emails = new TMSEmails();
$emails->init();

$usersAuth = new TMSAuth();
$usersAuth->init();

$reportsCompany = new TMSReportsCompany();
$reportsCompany->init();

$reportsShipper = new TMSReportsShipper();
$reportsShipper->init();

$reportsLogs = new TMSLogs();
$reportsLogs->init();

$reportsPerformance = new TMSReportsPerformance();
$reportsPerformance->init();

$createDocuments = new TMSGenerateDocument();
$createDocuments->init();

$TMSDrivers = new TMSDrivers();
$TMSDrivers->init();

$TMSContact = new TMSContacts();
$TMSContact->init();

// $tms_recruiter = new TMSDriversRecruiter();

function import_drivers_from_json( $page = 1 ) {
	$theme_dir  = get_stylesheet_directory();
	$import_dir = $theme_dir . '/import-drivers';
	$filename   = $import_dir . '/drivers-page-' . intval( $page ) . '.json';
	
	if ( ! file_exists( $filename ) ) {
		return new WP_Error( 'file_not_found', "Файл не найден: drivers-page-$page.json" );
	}
	
	$json_content = file_get_contents( $filename );
	
	$data = json_decode( $json_content, true );
	
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error( 'json_error', 'Ошибка разбора JSON: ' . json_last_error_msg() );
	}
	
	
	import_drivers( $data, $page );
}

function import_drivers( $drivers, $page = 1 ) {
	$table_main    = 'drivers';
	$table_meta    = 'drivers_meta';
	$table_raiting = 'drivers_raiting';
	$table_notice  = 'drivers_notice';
	$driverClass   = new TMSDrivers();
	global $wpdb;
	
	foreach ( $drivers as $driver ) {
		$id     = $driver[ 'ID' ];
		$fields = $driver[ 'fields' ];
		$data   = $fields[ 'driver' ];

//		$status          = $data[ 'status' ][ 'value' ] ?? '';
//		$status_label    = $data[ 'status' ][ 'label' ] ?? '';
		
		$date_hr      = $data[ 'date_hr' ] ?? '';
		$date_created = null;
		
		if ( ! empty( $date_hr ) ) {
			$datetime = DateTime::createFromFormat( 'm/d/Y g:i a', $date_hr );
			
			if ( $datetime ) {
				$date_created = $datetime->format( 'Y-m-d H:i:s' );
			}
		}
		
		$date = $date_created;
//		$availability    = $data[ 'availability' ] ?? '';
//		$top_driver      = $data[ 'top_driver' ] ?? false;
//		$paid            = $data[ 'paid' ] ?? false;
		$owner_driver    = $data[ 'owner_driver' ] ?? '';
		$owner_phone     = $data[ 'owner_phone' ] ?? '';
		$owner_home_city = $data[ 'owner_home_city' ] ?? '';
		$owner_email     = $data[ 'owner_email' ] ?? '';
		$owner_home      = $data[ 'owner_home' ] ?? '';
		$parts           = explode( ',', $owner_home );
		if ( isset( $parts[ 1 ] ) ) {
			$owner_home = trim( $parts[ 1 ] );
		}
		
		$type               = str_replace( ' ', '-', strtolower( $data[ 'type' ] ) ) ?? '';
		$lwh                = $data[ 'lwh' ] ?? '';
		$payload_max        = $data[ 'payload_max' ] ?? '';
		$city               = $data[ 'city' ] ?? '';
		$comments           = $data[ 'comments' ] ?? '';
		$vin_code           = $data[ 'vin_code' ] ?? '';
		$driver_name        = $data[ 'driver_name' ] ?? '';
		$driver_phone       = $data[ 'driver_phone' ] ?? '';
		$driver_email       = $data[ 'driver_email' ] ?? '';
		$driver_license     = $data[ 'driver_license' ] ?? '';
		$ein                = strtolower( $data[ 'ein' ] ) ?? '';
		$vehicle_year       = $data[ 'vehicle_year' ] ?? '';
		$id_hr              = $data[ 'id_hr' ] ?? '';
		$source_hr          = $data[ 'source_hr' ] ?? '';
		$nationality        = $data[ 'nationality' ] ?? '';
		$emergency_contact  = $data[ 'emergency_contact' ] ?? '';
		$emergency_phone    = $data[ 'emergency_phone' ] ?? '';
		$select_labels      = implode( ', ', $data[ 'select_labels' ] ?? [] );
		$select_labels_dist = implode( ', ', $data[ 'select_labels_distance' ] ?? [] );
		$language_icon      = $data[ 'preferred_language_icon' ] ?? '';
		
		$zipcode            = $fields[ 'zipcode' ] ?? '';
		$mc_number          = $fields[ 'mc_number' ] ?? false;
		$dot_number         = $fields[ 'dot_number' ] ?? false;
		$driver_rating      = $fields[ 'driver_rating' ] ?? '';
		$driver_notice_list = $fields[ 'driver_notice_list' ] ?? '';
		$driver2_name       = $data[ 'driver2_name' ] ?? '';
		$driver2_phone      = $data[ 'driver2_phone' ] ?? '';
		$driver2_email      = $data[ 'driver2_email' ] ?? '';
		
		$location_access = [];
		
		if ( strpos( $select_labels, 'canada' ) !== false ) {
			$location_access[] = 'canada';
		}
		
		if ( strpos( $select_labels, 'mexico' ) !== false ) {
			$location_access[] = 'mexico';
		}
		
		$location_result = implode( ',', $location_access );
		
		$data_new_driver = array(
			'driver_name'                => $driver_name,
			'driver_phone'               => $driver_phone,
			'driver_email'               => $driver_email,
			'home_location'              => $owner_home,
			'city'                       => $owner_home_city,
			'macro_point'                => strpos( $select_labels, 'macropoint' ) !== false ? 'on' : '',
			'trucker_tools'              => strpos( $select_labels, 'tucker-tools' ) !== false ? 'on' : '',
			'languages'                  => $language_icon,
			'team_driver_enabled'        => $driver2_name ? 'on' : '',
			'team_driver_name'           => $driver2_name,
			'team_driver_phone'          => $driver2_phone,
			'team_driver_email'          => $driver2_email,
			'owner_enabled'              => 'on',
			'owner_name'                 => $owner_driver,
			'owner_phone'                => $owner_phone,
			'owner_email'                => $owner_email,
			'source'                     => $source_hr,
			'recruiter_add'              => '68',
			'driver_license'             => $driver_license,
			'preferred_distance'         => $select_labels_dist,
			'cross_border'               => $location_result,
			'emergency_contact_name'     => $emergency_contact,
			'emergency_contact_phone'    => $emergency_phone,
			'emergency_contact_relation' => strpos( $select_labels, 'wife' ) !== false ? 'wife' : '',
			'vehicle_type'               => $type,
			'vehicle_year'               => $vehicle_year,
			'payload'                    => $payload_max,
			'dimensions'                 => $lwh,
			'vin'                        => $vin_code,
			'ppe'                        => strpos( $select_labels, 'ppe' ) !== false ? 'on' : '',
			'e_tracks'                   => strpos( $select_labels, 'e-track' ) !== false ? 'on' : '',
			'pallet_jack'                => strpos( $select_labels, 'pallet-jack' ) !== false ? 'on' : '',
			'lift_gate'                  => strpos( $select_labels, 'liftgate' ) !== false ? 'on' : '',
			'dolly'                      => strpos( $select_labels, 'dolly' ) !== false ? 'on' : '',
			'load_bars'                  => strpos( $select_labels, 'load-bars' ) !== false ? 'on' : '',
			'ramp'                       => strpos( $select_labels, 'ramp' ) !== false ? 'on' : '',
			'printer'                    => strpos( $select_labels, 'printer' ) !== false ? 'on' : '',
			'sleeper'                    => strpos( $select_labels, 'sleeper' ) !== false ? 'on' : '',
			'account_type'               => $ein,
			'city_state_zip'             => $city . ', ' . $owner_home . ', ' . $zipcode,
			'nationality'                => $nationality,
			'notes'                      => $comments,
		);
		
		// 68 all others
		
		// Polly Bronska 162 new 43
		//Olha Allison 129 new 33
		//Mary Kolten 216
		//Kate Fisher 149 new 42
		//Alla Martin 113 new 40
		//Julia Donovan 94 new 13
		
		if ( strval( $id_hr ) === '162' ) {
			$data_new_driver[ 'recruiter_add' ] = '43';
		}
		if ( strval( $id_hr ) === '129' ) {
			$data_new_driver[ 'recruiter_add' ] = '33';
		}
		if ( strval( $id_hr ) === '149' ) {
			$data_new_driver[ 'recruiter_add' ] = '42';
		}
		if ( strval( $id_hr ) === '113' ) {
			$data_new_driver[ 'recruiter_add' ] = '40';
		}
		if ( strval( $id_hr ) === '94' ) {
			$data_new_driver[ 'recruiter_add' ] = '13';
		}
		
		$dot_enabled = stripos( $dot_number, 'Active' ) !== false ? 'on' : '';
		$mc_enabled  = stripos( $mc_number, 'Active' ) !== false ? 'on' : '';
		
		// Удаление " - Active", оставляем только число
		$dot_clean = '';
		$mc_clean  = '';
		
		if ( ! empty( $dot_number ) ) {
			$dot_parts = explode( '-', $dot_number );
			$dot_clean = trim( $dot_parts[ 0 ] );
		}
		
		if ( ! empty( $mc_number ) ) {
			$mc_parts = explode( '-', $mc_number );
			$mc_clean = trim( $mc_parts[ 0 ] );
		}
		
		$data_new_driver[ 'dot_enabled' ] = $dot_enabled;
		$data_new_driver[ 'dot' ]         = $dot_clean;
		$data_new_driver[ 'mc_enabled' ]  = $mc_enabled;
		$data_new_driver[ 'mc' ]          = $mc_clean;
		
		$parsed_driver_rating = json_decode( $driver_rating, true ); // Декодируем JSON в массив
		
		$rating_data = [];
		
		if ( is_array( $parsed_driver_rating ) ) {
			foreach ( $parsed_driver_rating as $item ) {
				$rating_data[] = [
					'name'         => $item[ 'name' ] ?? '',
					'time'         => isset( $item[ 'time' ] ) ? (int) $item[ 'time' ] : 0,
					'reit'         => isset( $item[ 'reit' ] ) ? (int) $item[ 'reit' ] : 0,
					'message'      => $item[ 'mess' ] ?? '',
					'order_number' => $item[ 'order_number' ] ?? '',
				];
			}
		}
		
		$notice_data = [];
		
		if ( is_array( $driver_notice_list ) ) {
			foreach ( $driver_notice_list as $notice ) {
				$notice_data[] = [
					'name'    => $notice[ 'name' ] ?? '',
					'date'    => isset( $notice[ 'date' ] ) ? (int) $notice[ 'date' ] : 0,
					'message' => $notice[ 'message' ] ?? '',
					'status'  => ! empty( $notice[ 'status' ] ) ? 1 : 0, // Преобразуем bool в 0/1
				];
			}
		}
		
		
		$table_name = $wpdb->prefix . $table_main;
		
		$data_main[ 'id' ]              = $id;
		$data_main[ 'user_id_added' ]   = $data_new_driver[ 'recruiter_add' ];
		$data_main[ 'date_created' ]    = $date;
		$data_main[ 'user_id_updated' ] = $id_hr;
		$data_main[ 'date_updated' ]    = current_time( 'mysql' );
		$data_main[ 'status_post' ]     = 'publish';
		
		// Use REPLACE INTO to handle existing IDs properly
		$insert_result = $wpdb->replace( $table_name, $data_main );
		
		if ( $insert_result !== false ) {
			$driver_id = $id; // Use the original ID instead of auto-generated one
			
			$res = $driverClass->update_post_meta_data( $driver_id, $data_new_driver );
			
			if ( ! empty( $driver_id ) ) {
				// Добавление рейтингов
				if ( ! empty( $rating_data ) ) {
					foreach ( $rating_data as $item ) {
						$driverClass->insert_driver_rating( $driver_id, $item[ 'name' ], $item[ 'time' ], $item[ 'reit' ], $item[ 'message' ], $item[ 'order_number' ] );
					}
				}
				
				// Добавление уведомлений
				if ( ! empty( $notice_data ) ) {
					foreach ( $notice_data as $notice ) {
						$driverClass->insert_driver_notice( $driver_id, $notice[ 'name' ], $notice[ 'date' ], $notice[ 'message' ], $notice[ 'status' ] );
					}
				}
			}
		}
	}
	
	$theme_dir  = get_stylesheet_directory();
	$import_dir = $theme_dir . '/import-drivers';
	$filename   = $import_dir . '/drivers-page-' . intval( $page ) . '.json';

// Новый путь с префиксом added
	$renamed_filename = $import_dir . '/added-drivers-page-' . intval( $page ) . '.json';

// Переименовываем файл
	if ( file_exists( $filename ) ) {
		rename( $filename, $renamed_filename );
	}
}

// import_drivers_from_json( 5 );

// Get drivers data from old site endpoint
function get_drivers_from_old_site() {
	$url = 'https://www.odysseia-tms.kiev.ua/wp-json/wp/v2/all-drivers-positions';
	
	// Make HTTP request
	$response = wp_remote_get( $url );
	
	if ( is_wp_error( $response ) ) {
		error_log( 'Error fetching drivers data: ' . $response->get_error_message() );
		
		return;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		error_log( 'Error parsing JSON: ' . json_last_error_msg() );
		
		return;
	}
	
	if ( ! isset( $data[ 'success' ] ) || ! $data[ 'success' ] ) {
		error_log( 'API request was not successful' );
		
		return;
	}
	
	$drivers = $data[ 'data' ][ 'drivers' ] ?? [];
	$total   = $data[ 'data' ][ 'total' ] ?? 0;
	
	echo "<h2>Drivers Data from Old Site</h2>";
	echo "<p><strong>Total drivers:</strong> " . $total . "</p>";
	echo "<div style='max-height: 500px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
	echo "<pre style='font-size: 10px; line-height: 1.2;'>";
	print_r( $drivers );
	echo "</pre>";
	echo "</div>";
}

// Uncomment the line below to fetch and display drivers data
// get_drivers_from_old_site();

// Save drivers data to file
function save_drivers_to_file() {
	$url = 'https://www.odysseia-tms.kiev.ua/wp-json/wp/v2/all-drivers-positions';
	
	// Make HTTP request
	$response = wp_remote_get( $url );
	
	if ( is_wp_error( $response ) ) {
		error_log( 'Error fetching drivers data: ' . $response->get_error_message() );
		
		return;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		error_log( 'Error parsing JSON: ' . json_last_error_msg() );
		
		return;
	}
	
	if ( ! isset( $data[ 'success' ] ) || ! $data[ 'success' ] ) {
		error_log( 'API request was not successful' );
		
		return;
	}
	
	$drivers = $data[ 'data' ][ 'drivers' ] ?? [];
	$total   = $data[ 'data' ][ 'total' ] ?? 0;
	
	// Create directory if it doesn't exist
	$theme_dir  = get_stylesheet_directory();
	$import_dir = $theme_dir . '/import-drivers';
	
	if ( ! is_dir( $import_dir ) ) {
		wp_mkdir_p( $import_dir );
	}
	
	// Save to file
	$filename  = $import_dir . '/drivers-from-old-site.json';
	$json_data = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	
	if ( file_put_contents( $filename, $json_data ) ) {
		echo "<h2>Drivers Data Saved Successfully</h2>";
		echo "<p><strong>Total drivers:</strong> " . $total . "</p>";
		echo "<p><strong>File saved to:</strong> " . $filename . "</p>";
		echo "<p><strong>File size:</strong> " . number_format( filesize( $filename ) ) . " bytes</p>";
	} else {
		echo "<h2>Error Saving File</h2>";
		echo "<p>Could not save data to file: " . $filename . "</p>";
	}
}

// Uncomment the line below to fetch and save drivers data to file
// save_drivers_to_file();

// Update drivers data from old site
function update_drivers_from_old_site() {
	$url = 'https://www.odysseia-tms.kiev.ua/wp-json/wp/v2/all-drivers-positions';
	
	// Make HTTP request
	$response = wp_remote_get( $url );
	
	if ( is_wp_error( $response ) ) {
		error_log( 'Error fetching drivers data: ' . $response->get_error_message() );
		
		return;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		error_log( 'Error parsing JSON: ' . json_last_error_msg() );
		
		return;
	}
	
	if ( ! isset( $data[ 'success' ] ) || ! $data[ 'success' ] ) {
		error_log( 'API request was not successful' );
		
		return;
	}
	
	$drivers = $data[ 'data' ][ 'drivers' ] ?? [];
	$total   = $data[ 'data' ][ 'total' ] ?? 0;
	
	echo "<h2>Updating Drivers Data from Old Site</h2>";
	echo "<p><strong>Total drivers in API:</strong> " . $total . "</p>";
	
	// Filter drivers with ID >= 3175
	$filtered_drivers = [];
	foreach ( $drivers as $driver_id => $driver_data ) {
		if ( intval( $driver_id ) >= 3175 ) {
			$filtered_drivers[ $driver_id ] = $driver_data;
		}
	}
	
	echo "<p><strong>Drivers to update (ID >= 3175):</strong> " . count( $filtered_drivers ) . "</p>";
	
	// Initialize drivers class
	$drivers_class = new TMSDrivers();
	$updated_count = 0;
	$error_count   = 0;
	
	foreach ( $filtered_drivers as $driver_id => $driver_data ) {
		try {
			// Prepare update data
			$update_data = [
				'driver_id'        => $driver_id,
				'driver_status'    => $driver_data[ 'status' ][ 'value' ] ?? '',
				'status_date'      => $driver_data[ 'date' ] ?? '',
				'current_location' => $driver_data[ 'zipcode' ] ?? '',
				'current_city'     => $driver_data[ 'city' ] ?? '',
				'current_zipcode'  => $driver_data[ 'zipcode' ] ?? '',
				'latitude'         => $driver_data[ 'latitude' ] ?? '',
				'longitude'        => $driver_data[ 'longitude' ] ?? '',
				'country'          => '', // Extract from state if needed
				'current_country'  => '', // Extract from state if needed
			];
			
			// Extract country from state if possible
			if ( ! empty( $driver_data[ 'state' ] ) ) {
				$state_parts = explode( '_', $driver_data[ 'state' ] );
				if ( count( $state_parts ) > 1 ) {
					$update_data[ 'current_country' ] = trim( $state_parts[ 1 ] );
				}
			}
			
			// Update driver in database
			$result = $drivers_class->update_driver_in_db( $update_data );
			
			if ( $result ) {
				$updated_count ++;
				echo "<div style='color: green; font-size: 12px;'>✓ Updated driver ID: $driver_id</div>";
			} else {
				$error_count ++;
				echo "<div style='color: red; font-size: 12px;'>✗ Failed to update driver ID: $driver_id</div>";
			}
			
		}
		catch ( Exception $e ) {
			$error_count ++;
			echo "<div style='color: red; font-size: 12px;'>✗ Error updating driver ID $driver_id: " . $e->getMessage() . "</div>";
		}
	}
	
	echo "<h3>Update Summary</h3>";
	echo "<p><strong>Successfully updated:</strong> $updated_count drivers</p>";
	echo "<p><strong>Errors:</strong> $error_count drivers</p>";
	echo "<p><strong>Total processed:</strong> " . count( $filtered_drivers ) . " drivers</p>";
}

// Uncomment the line below to update drivers data from old site
// update_drivers_from_old_site();

// Replace driver IDs in all related tables (silent mode)
function replace_driver_ids() {
	global $wpdb;
	// Define ID mappings: old_id => new_id
	$id_mappings = [
		3251 => 419,
		3253 => 345,
		3252 => 268,
		// Add more mappings here as needed
		// old_id => new_id,
	];
	
	$total_updated = 0;
	$errors        = [];
	
	foreach ( $id_mappings as $old_id => $new_id ) {
		try {
			// Start transaction
			$wpdb->query( 'START TRANSACTION' );
			
			$tables_updated = 0;
			
			// 1. Update main drivers table
			$result = $wpdb->update( $wpdb->prefix . 'drivers', [ 'id' => $new_id ], [ 'id' => $old_id ], [ '%d' ], [ '%d' ] );
			
			if ( $result !== false ) {
				$tables_updated ++;
			}
			
			// 2. Update drivers_meta table
			$result = $wpdb->update( $wpdb->prefix . 'drivers_meta', [ 'post_id' => $new_id ], [ 'post_id' => $old_id ], [ '%d' ], [ '%d' ] );
			
			if ( $result !== false ) {
				$tables_updated ++;
			}
			
			// 3. Update drivers_raiting table
			$result = $wpdb->update( $wpdb->prefix . 'drivers_raiting', [ 'driver_id' => $new_id ], [ 'driver_id' => $old_id ], [ '%d' ], [ '%d' ] );
			
			if ( $result !== false ) {
				$tables_updated ++;
			}
			
			// 4. Update drivers_notice table
			$result = $wpdb->update( $wpdb->prefix . 'drivers_notice', [ 'driver_id' => $new_id ], [ 'driver_id' => $old_id ], [ '%d' ], [ '%d' ] );
			
			if ( $result !== false ) {
				$tables_updated ++;
			}
			
			// 5. Update logs table (if exists)
			$logs_table = $wpdb->prefix . 'tms_logs';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) == $logs_table ) {
				$result = $wpdb->update( $logs_table, [ 'driver_id' => $new_id ], [ 'driver_id' => $old_id ], [ '%d' ], [ '%d' ] );
				
				if ( $result !== false ) {
					$tables_updated ++;
				}
			}
			
			// Check if any tables were updated
			if ( $tables_updated > 0 ) {
				// Commit transaction
				$wpdb->query( 'COMMIT' );
				$total_updated ++;
			} else {
				// Rollback transaction
				$wpdb->query( 'ROLLBACK' );
				$errors[] = "No tables updated for ID $old_id → $new_id";
			}
			
		}
		catch ( Exception $e ) {
			// Rollback transaction on error
			$wpdb->query( 'ROLLBACK' );
			$errors[] = "Error for ID $old_id → $new_id: " . $e->getMessage();
		}
	}
	
	// Log results to error log only
	if ( $total_updated > 0 ) {
		error_log( "Driver ID replacement: Successfully processed $total_updated mappings" );
	}
	
	if ( ! empty( $errors ) ) {
		error_log( "Driver ID replacement errors: " . implode( ', ', $errors ) );
	}
}

// Uncomment the line below to replace driver IDs
//replace_driver_ids();

/**
 * Sanitize uploaded file name
 */
add_filter( 'sanitize_file_name', array( $wp_rock, 'custom_sanitize_file_name' ), 10, 1 );


/**
 * Set custom upload size limit
 */
$wp_rock->px_custom_upload_size_limit( 3 );


// close admin panel for all roles 
add_action( 'admin_init', function() {
	
	// Пропускаем, если это AJAX-запрос
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}
	
	// Проверяем, авторизован ли пользователь
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url() ); // Перенаправление на главную страницу
		exit;
	}
	
	// Получаем текущего пользователя
	$current_user = wp_get_current_user();
	
	// Проверяем роль пользователя
	if ( ! in_array( 'administrator', $current_user->roles ) ) {
		wp_redirect( home_url() ); // Перенаправление на главную страницу
		exit;
	}
} );