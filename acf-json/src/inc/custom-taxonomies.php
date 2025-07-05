<?php
/**
 * Custom post type definitions
 *
 * @package WP-rock
 * @since 4.4.0
 */

add_action( 'init', 'register_taxonomies' );

/**
 * Register taxonomies
 */
function register_taxonomies() {
    foreach ( glob( get_template_directory() . '/src/inc/custom-taxonomies/*.php' ) as $file ) {
        require $file;
    }
}
