<?php
/**
 * Main function themes
 *
 * @package WP-rock
 * @since 4.4.0
 */

define( 'THEME_URI', get_template_directory_uri() );
define( 'THEME_DIR', get_template_directory() );
define( 'STYLE_URI', get_stylesheet_uri() );
define( 'STYLE_DIR', get_stylesheet_directory() );
define( 'ASSETS_CSS', THEME_URI . '/assets/public/css/' );
define( 'ASSETS_JS', THEME_URI . '/assets/public/js/' );
define( 'LIBS_JS', THEME_URI . '/src/js/libs/' );

// required files.
require THEME_DIR . '/src/inc/class-wp-rock.php';

require THEME_DIR . '/src/inc/class-tms-reports-icons.php';
require THEME_DIR . '/src/inc/class-tms-reports-helper.php';
require THEME_DIR . '/src/inc/class-tms-auth.php';
require THEME_DIR . '/src/inc/class-tms-reports.php';
require THEME_DIR . '/src/inc/class-tms-users.php';
require THEME_DIR . '/src/inc/class-tms-reports-statistics.php';
require THEME_DIR . '/src/inc/class-tms-reports-company.php';
require THEME_DIR . '/src/inc/class-tms-reports-shipper.php';

require THEME_DIR . '/src/inc/initial-setup.php';
require THEME_DIR . '/src/inc/enqueue-scripts.php';
require THEME_DIR . '/src/inc/wpeditor-formats-options.php';
require THEME_DIR . '/src/inc/analytics-settings.php';
require THEME_DIR . '/src/inc/acf-setting.php';
require THEME_DIR . '/src/inc/custom-posts-type.php';
require THEME_DIR . '/src/inc/custom-taxonomies.php';
require THEME_DIR . '/src/inc/woocommerce-customization.php';
require THEME_DIR . '/src/inc/class-wp-rock-blocks.php';
require THEME_DIR . '/src/inc/ajax-requests.php';
require THEME_DIR . '/src/inc/custom-accept-cookies.php';
require THEME_DIR . '/src/inc/custom-hooks.php';
require THEME_DIR . '/src/inc/custom-shortcodes.php';
require THEME_DIR . '/src/inc/class-mobile-detect.php';

if ( false ) {
	global $wpdb;
	
	// Названия таблиц
	$table_reports = $wpdb->prefix . 'reports_odysseia';
	$table_meta    = $wpdb->prefix . 'reportsmeta_odysseia';
	
	// Список ID диспетчеров
	
	
	
	// Функция для генерации случайных данных
	function generateRandomData() {
		$sources = array(
			'contact',
			'dat',
			'truckstop',
			'sylectus',
			'rxo',
			'beon',
			'other'
		);
		
		$statuses = array(
			'waiting-on-pu-date',
			'at-pu',
			'loaded-enroute',
			'at-del',
			'delivered',
			'tonu',
			'cancelled',
			'waiting-on-rc'
		);
		$dispatcher_ids = [ 2, 3, 8, 9, 10, 11, 12 ];
		$random_dispatcher = $dispatcher_ids[ array_rand( $dispatcher_ids ) ];
		$random_source_key = array_rand( $sources );
		$random_status_key = array_rand( $statuses );
		return [
			'customer_id'               => rand( 1, 10 ),
			'contact_name'              => 'Test Name ' . rand( 1, 100 ),
			'contact_phone'             => '+1-234-567-' . str_pad( rand( 0, 9999 ), 4, '0', STR_PAD_LEFT ),
			'contact_email'             => 'test' . rand( 1, 100 ) . '@example.com',
			'additional_contacts'       => json_encode( [ 'Contact1', 'Contact2' ] ),
			'load_status'               => $statuses[ $random_status_key ],
			'instructions'              => 'Instruction ' . rand( 1, 10 ),
			'source'                    => $sources[ $random_source_key ],
			'load_type'                 => 'Type ' . rand( 1, 3 ),
			'commodity'                 => 'Commodity ' . rand( 1, 20 ),
			'weight'                    => rand( 10, 1000 ),
			'notes'                     => 'Notes for record ' . rand( 1, 100 ),
			'dispatcher_initials'       => $random_dispatcher,
			'reference_number'          => rand( 1000000000, 9999999999 ),
			'unit_number_name'          => '(Unit ' . rand( 1, 100 ) . ') Name ' . rand( 1, 100 ),
			'booked_rate'               => rand( 1000, 5000 ) . '.' . rand( 0, 99 ),
			'driver_rate'               => rand( 100, 300 ) . '.' . rand( 0, 99 ),
			'profit'                    => rand( 500, 2000 ) . '.' . rand( 0, 99 ),
			'percent_booked_rate'       => rand( 20, 100 ) . '.' . rand( 0, 99 ),
			'true_profit'               => rand( 300, 1500 ) . '.' . rand( 0, 99 ),
			'pick_up_location'          => json_encode( [ 'address' => 'Location ' . rand( 1, 100 ) ] ),
			'delivery_location'         => json_encode( [ 'address' => 'Delivery ' . rand( 1, 100 ) ] ),
			'attached_files'            => json_encode( [] ),
			'attached_file_required'    => rand( 1, 300 ),
			'updated_rate_confirmation' => null,
			'screen_picture'            => rand( 1, 500 )
		];
	}
	
	// Заполнение таблицы wp_reports_odysseia
	for ( $i = 0; $i < 1000; $i ++ ) {
		$data = [
			'user_id_added'   => rand( 1, 10 ),
			'date_created'    => current_time( 'mysql' ),
			'user_id_updated' => rand( 1, 10 ),
			'date_updated'    => current_time( 'mysql' ),
			'pick_up_date'    => date( 'Y-m-d H:i:s', strtotime( '-' . rand( 1, 180 ) . ' days' ) ),
			'date_booked'     => date( 'Y-m-d H:i:s', strtotime( '-' . rand( 1, 180 ) . ' days' ) ),
			'load_problem'    => date( 'Y-m-d H:i:s', strtotime( '-' . rand( 1, 180 ) . ' days' ) ),
			'status_post'     => 'publish',
		];
		$wpdb->insert( $table_reports, $data );
		$post_id = $wpdb->insert_id;
		
		// Заполнение таблицы wp_reportsmeta_odysseia
		$metaData = generateRandomData();
		foreach ( $metaData as $meta_key => $meta_value ) {
			$wpdb->insert( $table_meta, [
				'post_id'    => $post_id,
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
			] );
		}
	}
	
	// Вывод сообщения об успешном заполнении таблиц
	echo "Таблицы успешно заполнены!";
}

function update_custom_post_meta_with_random_values() {
	global $wpdb;
	
	// Название вашей кастомной таблицы метаданных
	$table_meta = $wpdb->prefix . 'reportsmeta_odysseia';
	
	// Возможные значения для полей
	$sources = array(
		'contact',
		'dat',
		'truckstop',
		'sylectus',
		'rxo',
		'beon',
		'other'
	);
	
	$statuses = array(
		'waiting-on-pu-date',
		'at-pu',
		'loaded-enroute',
		'at-del',
		'delivered',
		'tonu',
		'cancelled',
		'waiting-on-rc'
	);
	
	// Список ID диспетчеров
	$dispatcher_ids = [ 2, 3, 8, 9, 10, 11, 12 ];
	
	// Получаем все уникальные post_id из вашей кастомной таблицы метаданных
	$posts = $wpdb->get_results( "SELECT DISTINCT post_id FROM {$table_meta}" );
	
	foreach ( $posts as $post ) {
		// Генерируем случайные значения
		$random_source_key = array_rand( $sources );
		$random_status_key = array_rand( $statuses );
		$random_dispatcher = $dispatcher_ids[ array_rand( $dispatcher_ids ) ];
		
		// Подготовка SQL-запроса для обновления мета-данных
		$wpdb->update( $table_meta, array(
				'meta_value' => $sources[ $random_source_key ]
			), array(
				'post_id'  => $post->post_id,
				'meta_key' => 'source'
			) );
		
		$wpdb->update( $table_meta, array(
				'meta_value' => $statuses[ $random_status_key ]
			), array(
				'post_id'  => $post->post_id,
				'meta_key' => 'load_status'
			) );
		
		$wpdb->update( $table_meta, array(
				'meta_value' => $random_dispatcher
			), array(
				'post_id'  => $post->post_id,
				'meta_key' => 'dispatcher_initials'
			) );
	}
	
	echo "Мета-данные успешно обновлены для всех постов в кастомной таблице!";
}

// Вызов функции
//update_custom_post_meta_with_random_values();