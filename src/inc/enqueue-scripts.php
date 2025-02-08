<?php
/**
 * Connecting scripts to site
 *
 * @package WP-rock
 */


/**
 * Enqueue scripts and styles.
 *
 * @return void
 */
function px_site_scripts() {
	
	global $global_options;
	
	$general_style_ver = gmdate( 'ymd-Gis', filemtime( STYLE_DIR . '/style.css' ) );
	$custom_style_ver  = gmdate( 'ymd-Gis', filemtime( STYLE_DIR . '/assets/public/css/frontend.css' ) );
	$custom_js_ver     = gmdate( 'ymd-Gis', filemtime( STYLE_DIR . '/assets/public/js/frontend.js' ) );
	
	// Load our main stylesheet.
	wp_enqueue_style( 'wp-rock-style', STYLE_URI, $general_style_ver );
	
	wp_enqueue_style( 'wp-rock_style', ASSETS_CSS . 'frontend.css', array(), $custom_style_ver );

//    wp_enqueue_style( 'swiper_css', 'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css', array(), null );
//    wp_style_add_data( 'swiper_css', 'async', true );
	
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	
	if ( is_404() ) {
		$page_404_style_file = THEME_DIR . '/assets/public/css/page-404.css';
		if ( file_exists( $page_404_style_file ) && file_get_contents( $page_404_style_file ) ) {
			wp_enqueue_style( 'page_404', ASSETS_CSS . 'page-404.css', array(), null );
		}
	}
	
	wp_enqueue_script( 'chart_js', LIBS_JS . 'gstatic.js', array( 'jquery' ), $custom_js_ver, true );
	wp_enqueue_script( 'frontend_js', ASSETS_JS . 'frontend.js', array( 'jquery' ), $custom_js_ver, true );
	
	$link_web_service_odysseia  = get_field_value( $global_options, 'link_web_service_odysseia' );
	$link_web_service_endurance = get_field_value( $global_options, 'link_web_service_endurance' );
	$link_web_service_martlet   = get_field_value( $global_options, 'link_web_service_martlet' );
	$api_key_here_map           = get_field_value( $global_options, 'api_key_here_map' );
	
	$vars = array(
		'ajax_url'                   => admin_url( 'admin-ajax.php' ),
		'theme_path'                 => get_stylesheet_directory_uri(),
		'site_url'                   => get_site_url(),
		'link_web_service_odysseia'  => $link_web_service_odysseia,
		'link_web_service_endurance' => $link_web_service_endurance,
		'link_web_service_martlet'   => $link_web_service_martlet,
		'here_api_key'               => $api_key_here_map,
	);
	
	wp_localize_script( 'frontend_js', 'var_from_php', $vars );
	
	remove_action( 'wp_head', 'wp_print_scripts' );
	remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
	remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
	
	add_action( 'wp_footer', 'wp_print_scripts', 5 );
	add_action( 'wp_footer', 'wp_enqueue_scripts', 5 );
	add_action( 'wp_footer', 'wp_print_head_scripts', 5 );
	
}

add_action( 'wp_enqueue_scripts', 'px_site_scripts' );


add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'wp-rock_style_admin', ASSETS_CSS . 'backend.css', array(), '1.2.0' );
	
	wp_enqueue_script( 'backend_js', ASSETS_JS . 'backend.js', array(
			'jquery',
		), '1.2.0', true );
}, 99 );