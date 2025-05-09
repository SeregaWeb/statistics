<?php
/**
 * Template Name: Page recruiters
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

$args  = $Drivers->set_filter_params( $args );
$items = $Drivers->get_table_items( $args );

$access = $TMSUsers->check_user_role_access( [ 'administrator', 'recruiter', 'recruiter-tl', 'accounting' ], true );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
						<?php if ( ! $access ) :
							echo $helper->message_top( 'error', 'Access only Administrator, recruiters and recruiters team leader have access to this page.' );
						else:
							
							echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/driver', 'filter' ) );
							
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/driver', 'table', $items ) );
						endif; ?>
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
