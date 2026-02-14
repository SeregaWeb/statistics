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
	// Determine ACF options key (with locale support)
	if ( function_exists( 'pll_current_language' ) ) {
		// @codingStandardsIgnoreStart
		$locale = get_locale();
		// @codingStandardsIgnoreEnd
		$options_key = 'theme-general-settings_' . $locale;
	} else {
		$options_key = 'theme-general-settings';
	}

	// Build transient key and try to load cached options
	$global_options_transient_key = 'tms_global_options_' . md5( $options_key );
	$cached_global_options        = get_transient( $global_options_transient_key );

	if ( false !== $cached_global_options && is_array( $cached_global_options ) ) {
		$global_options = $cached_global_options;
	} else {
		$global_options = get_fields( $options_key );
		if ( is_array( $global_options ) ) {
			set_transient( $global_options_transient_key, $global_options, DAY_IN_SECONDS );
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( 'Written to cache (1 day). Key: ' . $options_key, 'global-options-cache' );
			}
		}
	}
}

/**
 * Clear cached global ACF options when any ACF options page is updated.
 */
function tms_clear_global_options_cache( $post_id ) {
	// Only run when ACF is active
	if ( ! function_exists( 'get_fields' ) ) {
		return;
	}

	$option_keys = array( 'theme-general-settings' );

	// Add locale-specific keys if Polylang is available
	if ( function_exists( 'pll_languages_list' ) ) {
		$languages_locales = pll_languages_list(
			array(
				'fields' => 'locale',
			)
		);

		if ( is_array( $languages_locales ) ) {
			foreach ( $languages_locales as $locale ) {
				$option_keys[] = 'theme-general-settings_' . $locale;
			}
		}
	}

	foreach ( $option_keys as $options_key ) {
		$transient_key = 'tms_global_options_' . md5( $options_key );
		delete_transient( $transient_key );
	}
	if ( class_exists( 'TMSLogger' ) ) {
		TMSLogger::log_to_file( 'Cache cleared (ACF save_post). Post/options ID: ' . $post_id, 'global-options-cache' );
	}
}
add_action( 'acf/save_post', 'tms_clear_global_options_cache', 20 );

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

$TMSTrailers = new TMSTrailers();
$TMSTrailers->init();

$TMSVehicles = new TMSVehicles();
$TMSVehicles->init();

$TMSContact = new TMSContacts();
$TMSContact->init();

$reportsTimer = new TMSReportsTimer();
$reportsTimer->init();

$tms_recruiter = new TMSDriversRecruiter();

$eta_manager = new TMSEta();
$eta_manager->init();

$dark_mode = new DarkMode();

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

// import_drivers_from_json( 4 );

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

// Delete drivers data that was updated from old site
function delete_drivers_from_old_site() {
	global $wpdb;
	
	// Array of driver IDs that were updated
	$updated_driver_ids = [
		// 3274, 3288, 3289, 3290, 3291, 3297, 3321, 3336, 3318, 3335, 
		// 3317, 3334, 3292, 3293, 3303, 3304, 3294, 3295, 3296, 3298, 
		// 3299, 3300, 3301, 3302, 3305, 3308, 3306, 3307, 3312, 3311, 
		// 3309, 3310, 3315, 3331, 3313, 3333, 3314, 3332, 3319, 3320, 
		// 3322, 3323, 3324, 3325, 3326, 3327, 3328, 3329, 3266, 3260, 
		// 3259, 3257, 3254, 3277, 3271, 3273, 3281, 3284, 3275, 3276, 
		// 3278, 3279, 3280, 3282, 3285, 3286, 3287, 3261
	];
	
	echo "<h2>Deleting Drivers Data</h2>";
	echo "<p><strong>Drivers to delete:</strong> " . count( $updated_driver_ids ) . "</p>";
	
	$deleted_count = 0;
	$error_count   = 0;
	
	// Get current project tables
	
	$table_drivers      = $wpdb->prefix . 'drivers';
	$table_drivers_meta = $wpdb->prefix . 'drivers_meta';
	
	foreach ( $updated_driver_ids as $driver_id ) {
		try {
			// Delete from drivers table
			$drivers_result = $wpdb->delete( $table_drivers, array( 'id' => $driver_id ), array( '%d' ) );
			
			// Delete from drivers meta table
			$meta_result = $wpdb->delete( $table_drivers_meta, array( 'post_id' => $driver_id ), array( '%d' ) );
			
			if ( $drivers_result !== false && $meta_result !== false ) {
				$deleted_count ++;
				echo "<div style='color: green; font-size: 12px;'>✓ Deleted driver ID: $driver_id</div>";
			} else {
				$error_count ++;
				echo "<div style='color: red; font-size: 12px;'>✗ Failed to delete driver ID: $driver_id</div>";
			}
			
		}
		catch ( Exception $e ) {
			$error_count ++;
			echo "<div style='color: red; font-size: 12px;'>✗ Error deleting driver ID $driver_id: " . $e->getMessage() . "</div>";
		}
	}
	
	echo "<h3>Delete Summary</h3>";
	echo "<p><strong>Successfully deleted:</strong> $deleted_count drivers</p>";
	echo "<p><strong>Errors:</strong> $error_count drivers</p>";
	echo "<p><strong>Total processed:</strong> " . count( $updated_driver_ids ) . " drivers</p>";
}

// delete_drivers_from_old_site();

// Uncomment the line below to update drivers data from old site
// update_drivers_from_old_site();

// Replace driver IDs in all related tables (silent mode)
function replace_driver_ids() {
	global $wpdb;
	// Define ID mappings: old_id => new_id
	
	
	$id_mappings = [
		// '3258' => '3343',
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
// replace_driver_ids();

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

/**
 * Import driver ratings from array in chunks, avoiding duplicates
 *
 * @param array $ratings_array Array with driver ratings data
 * @param int $chunk_size Number of ratings to process per chunk (default: 500)
 *
 * @return array Results of import operation
 */
function import_driver_ratings( $ratings_array, $chunk_size = 500 ) {
	global $wpdb;
	
	$table_rating = $wpdb->prefix . 'drivers_raiting';
	$results      = [
		'total_processed'  => 0,
		'added'            => 0,
		'skipped'          => 0,
		'errors'           => [],
		'chunks_processed' => 0
	];
	
	// Convert array to flat list for chunking
	$all_ratings = [];
	foreach ( $ratings_array as $driver_id => $ratings ) {
		if ( ! is_array( $ratings ) ) {
			continue;
		}
		
		$driver_id = (int) $driver_id;
		foreach ( $ratings as $rating ) {
			$all_ratings[] = [
				'driver_id' => $driver_id,
				'rating'    => $rating
			];
		}
	}
	
	// Process in chunks
	$chunks = array_chunk( $all_ratings, $chunk_size );
	
	foreach ( $chunks as $chunk_index => $chunk ) {
		$results[ 'chunks_processed' ] ++;
		
		// Start transaction for this chunk
		$wpdb->query( 'START TRANSACTION' );
		
		try {
			foreach ( $chunk as $item ) {
				$driver_id = $item[ 'driver_id' ];
				$rating    = $item[ 'rating' ];
				
				$results[ 'total_processed' ] ++;
				
				// Extract rating data
				$name         = sanitize_text_field( $rating[ 'name' ] ?? '' );
				$time         = (int) ( $rating[ 'time' ] ?? 0 );
				$reit         = (int) ( $rating[ 'reit' ] ?? 0 );
				$message      = sanitize_textarea_field( $rating[ 'mess' ] ?? '' );
				$order_number = sanitize_text_field( $rating[ 'order_number' ] ?? '' );
				
				// Validate required fields
				if ( empty( $name ) || $time <= 0 || $reit < 1 || $reit > 5 ) {
					$results[ 'skipped' ] ++;
					continue;
				}
				
				// Check for duplicate rating (same driver, name, time, and message)
				$duplicate_check = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_rating
					WHERE driver_id = %d 
					AND name = %s 
					AND time = %d 
					AND message = %s", $driver_id, $name, $time, $message ) );
				
				if ( $duplicate_check ) {
					$results[ 'skipped' ] ++;
					continue;
				}
				
				// Insert new rating
				$insert_result = $wpdb->insert( $table_rating, [
					'driver_id'    => $driver_id,
					'name'         => $name,
					'time'         => $time,
					'reit'         => $reit,
					'message'      => $message,
					'order_number' => $order_number
				], [ '%d', '%s', '%d', '%d', '%s', '%s' ] );
				
				if ( $insert_result ) {
					$results[ 'added' ] ++;
				} else {
					$results[ 'errors' ][] = "Failed to insert rating for driver $driver_id: " . $wpdb->last_error;
				}
			}
			
			// Commit this chunk
			$wpdb->query( 'COMMIT' );
			
		}
		catch ( Exception $e ) {
			// Rollback this chunk on error
			$wpdb->query( 'ROLLBACK' );
			$results[ 'errors' ][] = "Chunk " . ( $chunk_index + 1 ) . " failed: " . $e->getMessage();
		}
		
		// Clear memory after each chunk
		unset( $chunk );
		gc_collect_cycles();
	}
	
	return $results;
}

/**
 * Alternative approach: Clear all ratings and import fresh data
 *
 * @param array $ratings_array Array with driver ratings data
 *
 * @return array Results of import operation
 */
function import_driver_ratings_fresh( $ratings_array, $chunk_size = 500 ) {
	global $wpdb;
	
	$table_rating = $wpdb->prefix . 'drivers_raiting';
	$results      = [
		'total_processed'  => 0,
		'added'            => 0,
		'deleted'          => 0,
		'errors'           => [],
		'chunks_processed' => 0
	];
	
	// Clear all existing ratings first
	$wpdb->query( 'START TRANSACTION' );
	try {
		$deleted_count        = $wpdb->query( "DELETE FROM $table_rating" );
		$results[ 'deleted' ] = $deleted_count;
		$wpdb->query( 'COMMIT' );
	}
	catch ( Exception $e ) {
		$wpdb->query( 'ROLLBACK' );
		$results[ 'errors' ][] = "Failed to clear existing ratings: " . $e->getMessage();
		
		return $results;
	}
	
	// Convert array to flat list for chunking
	$all_ratings = [];
	foreach ( $ratings_array as $driver_id => $ratings ) {
		if ( ! is_array( $ratings ) ) {
			continue;
		}
		
		$driver_id = (int) $driver_id;
		foreach ( $ratings as $rating ) {
			$all_ratings[] = [
				'driver_id' => $driver_id,
				'rating'    => $rating
			];
		}
	}
	
	// Process in chunks
	$chunks = array_chunk( $all_ratings, $chunk_size );
	
	foreach ( $chunks as $chunk_index => $chunk ) {
		$results[ 'chunks_processed' ] ++;
		
		// Start transaction for this chunk
		$wpdb->query( 'START TRANSACTION' );
		
		try {
			foreach ( $chunk as $item ) {
				$driver_id = $item[ 'driver_id' ];
				$rating    = $item[ 'rating' ];
				
				$results[ 'total_processed' ] ++;
				
				// Extract rating data
				$name         = sanitize_text_field( $rating[ 'name' ] ?? '' );
				$time         = (int) ( $rating[ 'time' ] ?? 0 );
				$reit         = (int) ( $rating[ 'reit' ] ?? 0 );
				$message      = sanitize_textarea_field( $rating[ 'mess' ] ?? '' );
				$order_number = sanitize_text_field( $rating[ 'order_number' ] ?? '' );
				
				// Validate required fields
				if ( empty( $name ) || $time <= 0 || $reit < 1 || $reit > 5 ) {
					continue;
				}
				
				// Insert rating
				$insert_result = $wpdb->insert( $table_rating, [
					'driver_id'    => $driver_id,
					'name'         => $name,
					'time'         => $time,
					'reit'         => $reit,
					'message'      => $message,
					'order_number' => $order_number
				], [ '%d', '%s', '%d', '%d', '%s', '%s' ] );
				
				if ( $insert_result ) {
					$results[ 'added' ] ++;
				} else {
					$results[ 'errors' ][] = "Failed to insert rating for driver $driver_id: " . $wpdb->last_error;
				}
			}
			
			// Commit this chunk
			$wpdb->query( 'COMMIT' );
			
		}
		catch ( Exception $e ) {
			// Rollback this chunk on error
			$wpdb->query( 'ROLLBACK' );
			$results[ 'errors' ][] = "Chunk " . ( $chunk_index + 1 ) . " failed: " . $e->getMessage();
		}
		
		// Clear memory after each chunk
		unset( $chunk );
		gc_collect_cycles();
	}
	
	return $results;
}

/**
 * Import driver ratings from array with offset for step-by-step processing
 *
 * @param array $ratings_array Array with driver ratings data
 * @param int $offset Starting position (0, 500, 1000, etc.)
 * @param int $limit Number of ratings to process per step (default: 500)
 *
 * @return array Results of import operation
 */
function import_driver_ratings_step( $ratings_array, $offset = 0, $limit = 500 ) {
	global $wpdb;
	
	$table_rating = $wpdb->prefix . 'drivers_raiting';
	$results      = [
		'total_processed' => 0,
		'added'           => 0,
		'skipped'         => 0,
		'errors'          => [],
		'offset'          => $offset,
		'limit'           => $limit,
		'has_more'        => false,
		'total_available' => 0
	];
	
	// Convert array to flat list
	$all_ratings = [];
	foreach ( $ratings_array as $driver_id => $ratings ) {
		if ( ! is_array( $ratings ) ) {
			continue;
		}
		
		$driver_id = (int) $driver_id;
		foreach ( $ratings as $rating ) {
			$all_ratings[] = [
				'driver_id' => $driver_id,
				'rating'    => $rating
			];
		}
	}
	
	$results[ 'total_available' ] = count( $all_ratings );
	
	// Get slice for current step
	$current_batch         = array_slice( $all_ratings, $offset, $limit );
	$results[ 'has_more' ] = ( $offset + $limit ) < count( $all_ratings );
	
	if ( empty( $current_batch ) ) {
		$results[ 'errors' ][] = "No data available for offset $offset";
		
		return $results;
	}
	
	// Start transaction for this batch
	$wpdb->query( 'START TRANSACTION' );
	
	try {
		foreach ( $current_batch as $item ) {
			$driver_id = $item[ 'driver_id' ];
			$rating    = $item[ 'rating' ];
			
			$results[ 'total_processed' ] ++;
			
			// Extract rating data
			$name         = sanitize_text_field( $rating[ 'name' ] ?? '' );
			$time         = (int) ( $rating[ 'time' ] ?? 0 );
			$reit         = (int) ( $rating[ 'reit' ] ?? 0 );
			$message      = sanitize_textarea_field( $rating[ 'mess' ] ?? '' );
			$order_number = sanitize_text_field( $rating[ 'order_number' ] ?? '' );
			
			// Validate required fields
			if ( empty( $name ) || $time <= 0 || $reit < 1 || $reit > 5 ) {
				$results[ 'skipped' ] ++;
				continue;
			}
			
			// Check for duplicate rating
			$duplicate_check = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_rating
				WHERE driver_id = %d 
				AND name = %s 
				AND time = %d 
				AND message = %s", $driver_id, $name, $time, $message ) );
			
			if ( $duplicate_check ) {
				$results[ 'skipped' ] ++;
				continue;
			}
			
			// Insert new rating
			$insert_result = $wpdb->insert( $table_rating, [
				'driver_id'    => $driver_id,
				'name'         => $name,
				'time'         => $time,
				'reit'         => $reit,
				'message'      => $message,
				'order_number' => $order_number
			], [ '%d', '%s', '%d', '%d', '%s', '%s' ] );
			
			if ( $insert_result ) {
				$results[ 'added' ] ++;
			} else {
				$results[ 'errors' ][] = "Failed to insert rating for driver $driver_id: " . $wpdb->last_error;
			}
		}
		
		// Commit this batch
		$wpdb->query( 'COMMIT' );
		
	}
	catch ( Exception $e ) {
		// Rollback on error
		$wpdb->query( 'ROLLBACK' );
		$results[ 'errors' ][] = "Batch failed: " . $e->getMessage();
	}
	
	return $results;
}

/**
 * Test function to import ratings from the provided array
 * Call this function to import your ratings data
 */
function test_import_ratings() {
	// Your ratings array (replace with your actual data)
	$ratings_array = [
		62 => [
			[
				'name' => 'Andriy Moore',
				'time' => 1664566020,
				'reit' => '5',
				'mess' => 'Load #22189294'
			],
			[
				'name' => 'Dave Oldman',
				'time' => 1667419620,
				'reit' => '5',
				'mess' => ''
			],
			// Add more ratings here...
		],
		// Add more drivers here...
	];
	
	echo "<h2>Importing Driver Ratings</h2>";
	echo "<pre>";
	
	// Choose which method to use:
	// Method 1: Import avoiding duplicates
	$results = import_driver_ratings( $ratings_array );
	
	// Method 2: Clear all and import fresh (uncomment if needed)
	// $results = import_driver_ratings_fresh($ratings_array);
	
	echo "Import Results:\n";
	echo "Total processed: " . $results[ 'total_processed' ] . "\n";
	echo "Added: " . $results[ 'added' ] . "\n";
	echo "Skipped: " . $results[ 'skipped' ] . "\n";
	echo "Chunks processed: " . $results[ 'chunks_processed' ] . "\n";
	
	if ( isset( $results[ 'deleted' ] ) ) {
		echo "Deleted: " . $results[ 'deleted' ] . "\n";
	}
	
	if ( ! empty( $results[ 'errors' ] ) ) {
		echo "Errors:\n";
		foreach ( $results[ 'errors' ] as $error ) {
			echo "- " . $error . "\n";
		}
	}
	
	echo "</pre>";
}

/**
 * Pretty print ratings array as JSON
 * Use this to format your ratings data for import
 */
function pretty_print_ratings_json( $ratings_array ) {
	echo "<h2>Ratings Array (JSON Format)</h2>";
	echo "<pre>";
	echo json_encode( $ratings_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	echo "</pre>";
}