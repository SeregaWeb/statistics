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
        $locale         = get_locale();
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

//var_dump($emails->get_all_emails());

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
     return ( isset( $data_arr[ $key ] ) ) ? $data_arr[ $key ] : null;
}

// close admin panel for all roles 
add_action('admin_init', function () {
	// Проверяем, авторизован ли пользователь
	if (!is_user_logged_in()) {
		wp_redirect(home_url()); // Перенаправление на главную страницу
		exit;
	}
	
	// Получаем текущего пользователя
	$current_user = wp_get_current_user();
	
	// Проверяем роль пользователя
	if (!in_array('administrator', $current_user->roles)) {
		wp_redirect(home_url()); // Перенаправление на главную страницу
		exit;
	}
});