<?php
/**
 * ACF fields processing for adding different analytics like Google, Facebook etc., in admin dashboard
 *
 * @package WP-rock/analytics-fields
 */

if ( function_exists( 'get_field' ) ) {

    add_action( 'wp_rock_before_close_head_tag', 'custom_analytics_1', 100 );

    /**
     * Render content from Theme settings (should be filled in admin dashboard).
     *
     * @return void
     */
    function custom_analytics_1(): void {
        echo do_shortcode( get_field( 'analytics_content_before_closing_head_tag', 'theme-general-settings' ) );
    }

    add_action( 'wp_rock_after_open_body_tag', 'custom_analytics_2', 100 );

    /**
     * Render content from Theme settings (should be filled in admin dashboard).
     *
     * @return void
     */
    function custom_analytics_2(): void {
        echo do_shortcode( get_field( 'analytics_content_after_open_body_tag', 'theme-general-settings' ) );
    }
}
