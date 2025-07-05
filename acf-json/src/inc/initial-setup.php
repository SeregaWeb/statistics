<?php
/**
 * Initial setup actions for site
 *
 * @package WP-rock
 */

/*Collect all ACF option fields to global variable. */
global $global_options;

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
 * Main theme's class init
 */
$wp_rock = new WP_Rock();
add_action( 'after_setup_theme', array( $wp_rock, 'px_site_setup' ) );

$reports = new TMSReports();
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
		
		$insert_result = $wpdb->insert( $table_name, $data_main );
		
		if ( is_numeric( $insert_result ) ) {
			$driver_id = $wpdb->insert_id;
			
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

//import_drivers_from_json( 6 );

/**
 * Sanitize uploaded file name
 */
add_filter( 'sanitize_file_name', array( $wp_rock, 'custom_sanitize_file_name' ), 10, 1 );


/**
 * Set custom upload size limit
 */
$wp_rock->px_custom_upload_size_limit( 5 );


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