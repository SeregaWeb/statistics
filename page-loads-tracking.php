<?php
/**
 * Template Name: Page loads tracking
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports  = new TMSReports();
$TMSUsers = new TMSUsers();

$args = array(
	'status_post'    => 'publish',
	'user_id'        => get_current_user_id(),
	'sort_by'        => 'pick_up_date',
	'exclude_status' => array( 'delivered', 'tonu', 'cancelled', 'waiting-on-rc' ),
);

$office_dispatcher   = get_field( 'work_location', 'user_' . get_current_user_id() );
$sellect_all_offices = $TMSUsers->check_user_role_access( array(
	'tracking-tl',
	'dispatcher-tl',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'moderator'
), true );

if ( ! $office_dispatcher || $sellect_all_offices ) {
	$office_dispatcher = 'all';
}

$args  = $reports->set_filter_params( $args, $office_dispatcher );
$items = $reports->get_table_items_tracking( $args );

$post_tp              = 'tracking';
$items[ 'page_type' ] = $post_tp;

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
                        <h2><?php echo get_the_title(); ?></h2>
                        <p><?php echo get_the_excerpt(); ?></p>

                    </div>
                    <div class="col-12">
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-tracking' ) );
						
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-tracking', $items ) );
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
