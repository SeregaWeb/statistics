<?php
/**
 * Template Name: Page driver search
 *
 * @package WP-rock
 * @since 4.4.0
 */


get_header();

$Drivers  = new TMSDrivers();
$TMSUsers = new TMSUsers();
$helper   = new TMSReportsHelper();
$args     = array(
	'status_post' => 'publish',
);

$args = $Drivers->set_filter_params_search( $args );

$my_search       = trim( get_field_value( $_GET, 'my_search' ) ?? '' );
$extended_search = trim( get_field_value( $_GET, 'extended_search' ) ?? '' );
$capabilities    = get_field_value( $_GET, 'capabilities' );

$access_view = $TMSUsers->check_user_role_access( array( 'administrator', 'recruiter', 'recruiter-tl','hr_manager', 'driver_updates' ), true );


if ( $access_view || ( $my_search || $extended_search || $capabilities ) ) {
	$items = $Drivers->get_table_items_search( $args );
} else {
	$items = array();
}


// Make items available globally for driver-search-filter template
global $driver_search_items;
$driver_search_items = $items;

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/driver', 'search-filter' ) );
						// Display hold drivers section first
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/driver', 'hold-section' ) );
						
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/driver', 'search-table', $items ) );
						?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
