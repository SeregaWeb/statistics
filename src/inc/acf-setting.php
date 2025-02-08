<?php
/**
 * Create Theme General Settings
 *
 * @package acf/settings
 */

if ( function_exists( 'acf_add_options_page' ) ) {
	
	$parent = acf_add_options_page( array(
			'page_title' => 'Theme General Settings',
			'menu_title' => 'Theme Settings',
			'menu_slug'  => 'theme-general-settings',
			'post_id'    => 'theme-general-settings',
			'capability' => 'edit_posts',
			'redirect'   => false,
		) );
}

/**
 * Register the styles (CSS) for the ACF blocks (acf_register_block_type()) in head section instead of body or footer
 */
add_action( 'wp_enqueue_scripts', 'register_acf_block_styles' );
add_action( 'admin_enqueue_scripts', 'register_acf_block_styles' );

function register_acf_block_styles(): void {
	
	$wrock_blocks  = new WP_Rock_Blocks();
	$custom_blocks = $wrock_blocks->blocks;
	
	if ( ! empty( $custom_blocks ) ) {
		
		foreach ( array_keys( $custom_blocks ) as $key ) {
			if ( has_block( 'acf/' . $key ) ) {
				$style_file = ASSETS_CSS . $key . '.css';
				wp_enqueue_style( 'acf-block-' . $key, $style_file, array(), wp_get_theme()?->get( 'Version' ) );
			}
		}
	}
}

/**
 * Adding "preconnect" rel attribute to <link> tag for ACF styles to improve performance
 *
 * @param $html
 * @param $handle
 * @param $href
 * @param $media
 *
 * @return array|mixed|string|string[]
 */
function add_preconnect_rel_attribute( $html, $handle, $href, $media ) {
	
	$wrock_blocks  = new WP_Rock_Blocks();
	$custom_blocks = $wrock_blocks->blocks;
	
	if ( ! empty( $custom_blocks ) ) {
		
		foreach ( array_keys( $custom_blocks ) as $key ) {
			if ( $handle === 'acf-block-' . $key ) {
				// Adding attribute rel="preconnect"
				$html = str_replace( 'stylesheet', 'preconnect', $html );
			}
		}
	}
	
	return $html;
}
// add_filter('style_loader_tag', 'add_preconnect_rel_attribute', 90, 4);
