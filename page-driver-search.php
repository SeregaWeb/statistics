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

$items = $Drivers->get_table_items_search( $args );

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
