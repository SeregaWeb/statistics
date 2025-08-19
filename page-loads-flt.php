<?php
/**
 * Template Name: Page loads flt
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports  = new TMSReportsFlt();
$TMSUsers = new TMSUsers();

$args   = array(
	'status_post' => 'publish',
);
$access = true;

$office_dispatcher   = get_field( 'work_location', 'user_' . get_current_user_id() );
$sellect_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'moderator',
	'driver_updates'
), true );

if ( ! $office_dispatcher || $sellect_all_offices ) {
	$office_dispatcher = 'all';
}

$args                 = $reports->set_filter_params( $args, $office_dispatcher );
$items                = $reports->get_table_items( $args );
$items[ 'office' ]    = $office_dispatcher;
$post_tp              = 'dispatcher';
$items[ 'page_type' ] = $post_tp;
$items[ 'flt' ]       = true;


$access_flt = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin = current_user_can( 'administrator' );

if ( ! $access_flt && ! $is_admin ) {
	$access = false;
}


?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
					<?php if ( $access ): ?>
                        <div class="col-12">
							
							<?php
							echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter', array( 'post_type' => $post_tp ) ) );
							?>
							
							
							<?php
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table', $items ) );
							?>

                        </div>
					<?php else: ?>
                        <div class="col-12 col-lg-9 mt-3">
							<?php
							echo $reports->message_top( 'danger', $reports->messages_prepare( 'not-access' ) );
							?>
                        </div>
					<?php endif; ?>
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
