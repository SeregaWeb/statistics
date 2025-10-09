<?php
/**
 * Template Name: Page loads tracking
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

// Analytics caching now uses WordPress transients (no session needed)

$TMSUsers = new TMSUsers();
$TMSReportsTimer = new TMSReportsTimer();

// Проверяем доступ к FLT
$flt_user_access = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin = current_user_can( 'administrator' );
$show_flt_tabs = $flt_user_access || $is_admin;

// Get user team for filtering dispatchers
$my_team = $TMSUsers->check_group_access();

// Определяем тип данных для загрузки
$type = get_field_value( $_GET, 'type' );
$is_flt = $type === 'flt';



// Выбираем класс в зависимости от типа
if ( $is_flt ) {
	$reports = new TMSReportsFlt();
} else {
	$reports = new TMSReports();
}

$args = array(
	'status_post'    => 'publish',
	'user_id'        => get_current_user_id(),
	'sort_by'        => 'pick_up_date',
	'exclude_status' => array( 'delivered', 'tonu', 'cancelled' ),
);

$office_dispatcher   = get_field( 'work_location', 'user_' . get_current_user_id() );
$sellect_all_offices = $TMSUsers->check_user_role_access( array(
	'tracking-tl',
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'moderator'
), true );

if ( ! $office_dispatcher || $sellect_all_offices ) {
	$office_dispatcher = 'all';
}

$args                 = $reports->set_filter_params( $args, $office_dispatcher );
$items                = $reports->get_table_items_tracking( $args );
$post_tp              = 'tracking';
$items[ 'page_type' ] = $post_tp;
$items['hide_time_controls'] = true;
if ( $is_flt ) {
	$items[ 'flt' ] = true;
}

// Initialize smart analytics for current user
$current_project = $is_flt ? 'flt' : '';
$smart_analytics = $TMSReportsTimer->get_smart_analytics( $current_project, $is_flt );
$items['smart_analytics'] = $smart_analytics;

// Add project to items for modal log form
$user_id = get_current_user_id();
$user_project = get_field( 'current_select', 'user_' . $user_id );
$items['project'] = $user_project;

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
					
					<?php if ( $is_flt && ! $show_flt_tabs ): ?>
                        <div class="col-12  mt-3">
							<?php echo $reports->message_top( 'danger', $reports->messages_prepare( 'not-access' ) ); ?>
                        </div>
					<?php else: ?>

                    <div class="col-12 pt-3 pb-3">
                        <h2><?php echo get_the_title(); ?></h2>
                        <p><?php echo get_the_excerpt(); ?></p>

                    </div>
                    <div class="col-12">
                        
                        <?php
                        echo esc_html( get_template_part( TEMPLATE_PATH . 'common/flt', 'tabs', array( 'show_flt_tabs' => $show_flt_tabs, 'is_flt' => $is_flt ) ) );
                        ?>
                        
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-tracking', array( 'my_team' => $my_team ) ) );
						
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-tracking', $items ) );
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
