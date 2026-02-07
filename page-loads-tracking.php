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
	'exclude_tbd'    => true,
);

$office_dispatcher   = get_field( 'work_location', 'user_' . get_current_user_id() );
$sellect_all_offices = $TMSUsers->check_user_role_access( array(
	'tracking',
	'tracking-tl',
	'morning_tracking',
	'nightshift_tracking',
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

$args = $reports->set_filter_params( $args, $office_dispatcher );

// Get current page number
$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;

// Always get all high priority loads to exclude them from main query
// Use the same args but without exclude_ids to get all high priority loads
$high_priority_args = $args;
unset( $high_priority_args[ 'exclude_ids' ] );
$all_high_priority = $reports->get_high_priority_loads( $high_priority_args );
$high_priority_ids = wp_list_pluck( $all_high_priority, 'id' );

// Exclude high priority loads from main query (always, to avoid duplicates)
if ( ! empty( $high_priority_ids ) ) {
	$args[ 'exclude_ids' ] = $high_priority_ids;
}

// Get high priority loads for display (only on first page)
$high_priority_loads = array();
if ( $current_page === 1 && ! empty( $all_high_priority ) ) {
	$high_priority_loads = $all_high_priority;
}

$items                = $reports->get_table_items_tracking( $args );
$post_tp              = 'tracking';
$items[ 'page_type' ] = $post_tp;
$items['hide_time_controls'] = true;
if ( $is_flt ) {
	$items[ 'flt' ] = true;
}

// Counts for quick status filter buttons (same filters, per load_status)
$quick_status_keys = array( '', 'waiting-on-pu-date', 'at-pu', 'loaded-enroute', 'at-del' );
$quick_status_counts = array();
foreach ( $quick_status_keys as $qsk ) {
	$args_count = $args;
	if ( $qsk === '' ) {
		unset( $args_count['load_status'] );
	} else {
		$args_count['load_status'] = $qsk;
	}
	$res = $reports->get_table_items_tracking( $args_count );
	$quick_status_counts[ $qsk ] = isset( $res['total_posts'] ) ? (int) $res['total_posts'] : 0;
}
// "All" count should include high priority loads (they are shown when no status filter)
if ( ! empty( $high_priority_loads ) ) {
	$quick_status_counts[''] += count( $high_priority_loads );
}

// Merge high priority loads at the beginning (only on first page)
if ( $current_page === 1 && ! empty( $high_priority_loads ) ) {
	$items[ 'results' ] = array_merge( $high_priority_loads, $items[ 'results' ] );
	// Store count of high priority loads for template to add separator
	$items[ 'high_priority_count' ] = count( $high_priority_loads );
	// Update total count to include high priority loads
	$items[ 'total_posts' ] = $items[ 'total_posts' ] + count( $high_priority_loads );
	// Recalculate total pages
	$per_page = $reports->per_page_loads;
	$items[ 'total_pages' ] = ceil( $items[ 'total_posts' ] / $per_page );
} else {
	$items[ 'high_priority_count' ] = 0;
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
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-tracking', array( 'my_team' => $my_team, 'quick_status_counts' => $quick_status_counts ) ) );
						
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
