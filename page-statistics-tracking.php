<?php
/**
 * Template Name: Page statistics tracking
 *
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Initialize classes with proper error handling
$TMSUser       = new TMSUsers();
$TMSReports    = new TMSReports();
$TMSStatistics = new TMSStatistics();

// Get exclude field with validation
$exclude = get_field( 'exclude' );

// Sanitize and validate GET parameters
$office_dispatcher = isset( $_GET[ 'office' ] ) ? sanitize_text_field( $_GET[ 'office' ] ) : '';
$active_item       = isset( $_GET[ 'active_state' ] ) ? sanitize_text_field( $_GET[ 'active_state' ] ) : '';


// Get offices with validation
$offices = $TMSReports->get_offices_from_acf();

// Check user role access with proper validation
$select_all_offices = $TMSUser->check_user_role_access( array(
	'administrator',
	'tracking',
	'morning_tracking',
	'nightshift_tracking',
	'tracking-tl',
), true );

$show_move_dispatcher = $TMSUser->check_user_role_access( array(
	'administrator',
	'tracking-tl',
), true );

$users_access_for_popup = get_field( 'access_for_popup', get_the_ID() );
$current_user_id        = get_current_user_id();

if ( in_array( $current_user_id, $users_access_for_popup ) ) {
	$show_move_dispatcher = true;
}

$show_filter_by_office = true;

// Check if user has access to tabs (managers, tracking-teamleader, administrator)
$show_tabs = $TMSUser->check_user_role_access( array(
	'managers',
	'tracking-tl', 
	'administrator'
), true );

//var_dump( $exclude );
// Set office dispatcher based on user permissions
if ( $select_all_offices ) {
	$office_dispatcher = ! empty( $office_dispatcher ) ? $office_dispatcher : 'all';
} elseif ( empty( $office_dispatcher ) ) {
	if ( $current_user_id ) {
		$office_dispatcher = get_field( 'work_location', 'user_' . $current_user_id );
	}
}

$unique_access = get_field( 'field_68e412df3bd1d', get_the_ID() );

if ( is_array( $unique_access ) && ! empty( $unique_access ) && ! $show_tabs ) {
    $show_tabs = in_array( $current_user_id, $unique_access );  
}

// Get tracking statistics data
$items = $TMSReports->get_table_items_tracking_statistics( $office_dispatcher );

// Get users for statistics
$users = $TMSReports->get_tracking_users_for_statistics( '', $office_dispatcher );

// Get project and dispatchers with validation
$project     = isset( $TMSReports->project ) ? $TMSReports->project : '';
$dispatchers = isset( $items[ 'dispatchers' ] ) ? $items[ 'dispatchers' ] : array();

$dispatchers_users = $TMSStatistics->get_dispatchers( $office_dispatcher, false, true );

?>
    <div class="container-fluid tracking-statistics">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3">
						<?php if ( $show_filter_by_office ) : ?>
                            <form class="w-100 d-flex gap-1 align-items-start" method="GET">
                                <select class="form-select w-auto" name="office" aria-label="Select office">
                                    <option value="all">All offices</option>
									<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ) : ?>
										<?php foreach ( $offices[ 'choices' ] as $key => $val ) : ?>
                                            <option value="<?php echo esc_attr( $key ); ?>"
												<?php echo $office_dispatcher === $key ? 'selected' : ''; ?>>
												<?php echo esc_html( $val ); ?>
                                            </option>
										<?php endforeach; ?>
									<?php endif; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">Select</button>
                            </form>
						<?php endif; ?>

						<?php if ( $show_tabs ) : ?>
                            <!-- Tabs Navigation -->
                            <ul class="nav nav-tabs mt-4" id="statisticsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                                        Overview
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="detailed-tab" data-bs-toggle="tab" data-bs-target="#detailed" type="button" role="tab" aria-controls="detailed" aria-selected="false">
                                        Detailed Statistics
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content mt-3" id="statisticsTabContent">
                                <!-- Overview Tab -->
                                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
						<?php endif; ?>

                        <div class="tracking-statistics__wrapper">
                            <div class="card-tracking-stats">
                                <p class="card-tracking-stats__project">
									<?php echo esc_html( $project ); ?>:
                                </p>

                                <ul>
									<?php if ( isset( $items[ 'grand_total' ][ 'at-pu' ] ) && $items[ 'grand_total' ][ 'at-pu' ] > 0 ) : ?>
                                        <li class="">
										<span>
											Al pick up: <?php echo esc_html( $items[ 'grand_total' ][ 'at-pu' ] ); ?>
										</span>
											<?php if ( isset( $items[ 'grand_total' ][ 'total' ] ) && $items[ 'grand_total' ][ 'total' ] > 0 ) : ?>
                                                <span>
												Total: <?php echo esc_html( $items[ 'grand_total' ][ 'total' ] ); ?>
											</span>
											<?php endif; ?>
                                        </li>
									<?php endif; ?>
									
									<?php if ( isset( $items[ 'grand_total' ][ 'at-del' ] ) && $items[ 'grand_total' ][ 'at-del' ] > 0 ) : ?>
                                        <li>
										<span>
											At delivery: <?php echo esc_html( $items[ 'grand_total' ][ 'at-del' ] ); ?>
										</span>
                                        </li>
									<?php endif; ?>
									
									<?php if ( isset( $items[ 'grand_total' ][ 'loaded-enroute' ] ) && $items[ 'grand_total' ][ 'loaded-enroute' ] > 0 ) : ?>
                                        <li>
										<span>
											Loaded: <?php echo esc_html( $items[ 'grand_total' ][ 'loaded-enroute' ] ); ?>
										</span>
                                        </li>
									<?php endif; ?>
									
									<?php if ( isset( $items[ 'grand_total' ][ 'waiting-on-pu-date' ] ) && $items[ 'grand_total' ][ 'waiting-on-pu-date' ] > 0 ) : ?>
                                        <li>
										<span>
											Waiting: <?php echo esc_html( $items[ 'grand_total' ][ 'waiting-on-pu-date' ] ); ?>
										</span>
                                        </li>
									<?php endif; ?>
									
									<?php if ( isset( $items[ 'dispatchers' ] ) && is_array( $items[ 'dispatchers' ] ) ) : ?>
                                        <li class="mt-1">
                                            <div>
                                                <p class="mb-0">Dispatchers</p>
                                                <div style="display: none">
													<?php var_dump( $items[ 'dispatchers' ] ); ?>
                                                </div>
                                                <div class="d-flex gap-1 flex-wrap">
													<?php foreach ( $items[ 'dispatchers' ] as $key => $item ) : ?>
                                                        <div style="display: none">
															<?php var_dump( $key, $item ); ?>
                                                        </div>
														
														<?php
														
														$user_team = str_replace( 'user_', '', $key );
														$user_arr  = $TMSUser->get_user_full_name_by_id( $user_team );
														// Get user color with fallback
														$color_initials = '#030303';
														if ( $user_arr ) {
															$user_color = get_field( 'initials_color', 'user_' . $user_team );
															if ( $user_color ) {
																$color_initials = $user_color;
															}
														} else {
															$user_arr = array(
																'full_name' => 'User not found',
																'initials'  => 'NF'
															);
														}
														
														?>
                                                        <span data-bs-toggle="tooltip"
                                                              data-bs-placement="top"
                                                              title="<?php echo esc_attr( $user_arr[ 'full_name' ] ); ?> (<?php echo esc_attr( $item[ 'total' ] ); ?>)"
                                                              class="initials-circle"
                                                              style="background-color: <?php echo esc_attr( $color_initials ); ?>">
														<?php echo esc_html( $user_arr[ 'initials' ] ); ?>
													</span>
													<?php endforeach; ?>
                                                </div>
                                            </div>
                                        </li>
									<?php endif; ?>
                                </ul>
                            </div>
							
							<?php if ( $show_move_dispatcher ) : ?>
                                <div class="d-flex justify-content-start align-items-start">
                                    <button class="btn btn-primary js-open-popup-activator"
                                            data-href="#popup_move_driver">Move dispatcher
                                    </button>
                                </div>
							<?php endif; ?>
                        </div>

                        <hr>

                        <div class="tracking-statistics__wrapper align-items-start">
							<?php
							
							if ( isset( $users[ 'tracking' ] ) && is_array( $users[ 'tracking' ] ) ) : ?>
								<?php foreach ( $users[ 'tracking' ] as $user ) : ?>
									<?php
							
									$user_stats = $TMSReports->get_total_by_tracking_team( $user, $items );
//									var_dump( $user, $items );


									echo get_template_part( TEMPLATE_PATH . 'common/card', 'statistics-tracking', array(
										'user'       => $user,
										'user_stats' => $user_stats,
										'total'      => 25,
									) );
									?>
								<?php endforeach; ?>
							<?php endif; ?>
                        </div>
						
						<?php if ( isset( $users[ 'tracking_move' ] ) && is_array( $users[ 'tracking_move' ] ) && $show_move_dispatcher ) :
							echo get_template_part( TEMPLATE_PATH . 'popups/move', 'dispatcher', array(
								'title'             => 'Move dispatcher',
								'users'             => $users,
								'dispatchers_users' => $dispatchers_users,
							) );
						endif; ?>


                        <h3>Nightshift</h3>
                        <div class="tracking-statistics__wrapper">
							<?php if ( isset( $users[ 'nightshift' ] ) && is_array( $users[ 'nightshift' ] ) ) : ?>
								<?php foreach ( $users[ 'nightshift' ] as $user ) : ?>
									<?php
									$user_stats = $TMSReports->get_total_by_tracking_team( $user, $items );
									echo get_template_part( TEMPLATE_PATH . 'common/card', 'statistics-tracking', array(
										'user'       => $user,
										'user_stats' => $user_stats,
									) );
									?>
								<?php endforeach; ?>
							<?php endif; ?>
                        </div>


                        <h3>Morning shift</h3>
                        <div class="tracking-statistics__wrapper">
							<?php if ( isset( $users[ 'morning_tracking' ] ) && is_array( $users[ 'morning_tracking' ] ) ) : ?>
								<?php foreach ( $users[ 'morning_tracking' ] as $user ) : ?>
									<?php
									$user_stats = $TMSReports->get_total_by_tracking_team( $user, $items );
									echo get_template_part( TEMPLATE_PATH . 'common/card', 'statistics-tracking', array(
										'user'       => $user,
										'user_stats' => $user_stats,
									) );
									?>
								<?php endforeach; ?>
							<?php endif; ?>
                        </div>

						<?php if ( $show_tabs ) : ?>
                                </div> <!-- End Overview Tab -->

                                <!-- Detailed Statistics Tab -->
                                <div class="tab-pane fade" id="detailed" role="tabpanel" aria-labelledby="detailed-tab">
                                    <div class="detailed-statistics">
                                        <h4>Timer Analytics</h4>
                                        
                                        <!-- Filters -->
                                        <div class="row mb-4">
                                            <div class="col-md-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6>Filters</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <form id="analyticsFilters" class="row g-3">
                                                            <div class="col-md-3">
                                                                <label for="analyticsPeriod" class="form-label">Period</label>
                                                                <select class="form-select" id="analyticsPeriod" name="period">
                                                                    <option value="day">Day</option>
                                                                    <option value="week">Week</option>
                                                                    <option value="month">Month</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label for="analyticsDate" class="form-label">Date</label>
                                                                <input type="date" class="form-control" id="analyticsDate" name="date" value="<?php echo date('Y-m-d'); ?>">
                                                            </div>

												<input type="hidden" name="project" value="<?php echo esc_attr( $project ); ?>">
                                                            
                                                            <div class="col-md-3">
                                                                <label for="analyticsUser" class="form-label">User</label>
                                                                <select class="form-select" id="analyticsUser" name="user_id">
                                                                    <option value="">All Users</option>
                                                                    <?php
                                                                    // Get all tracking users
                                                                    $tracking_users = get_users( array(
                                                                        'role__in' => array( 'morning_tracking', 'nightshift_tracking', 'tracking', 'tracking-tl' ),
                                                                        'orderby' => 'display_name',
                                                                        'order' => 'ASC'
                                                                    ) );
                                                                    foreach ( $tracking_users as $user ) :
                                                                    ?>
                                                                        <option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label for="analyticsAction" class="form-label">Action</label>
                                                                <select class="form-select" id="analyticsAction" name="action">
                                                                    <option value="">All Actions</option>
                                                                    <option value="start">Start</option>
                                                                    <option value="pause">Pause</option>
                                                                    <option value="stop">Stop</option>
                                                                    <option value="update">Update</option>
                                                                </select>
                                                            </div>
                                                            <?php
                                                            // Check if current user has FLT access
                                                            $flt_user_access = get_field( 'flt', 'user_' . get_current_user_id() );
                                                            $is_admin = current_user_can( 'administrator' );
                                                            $show_flt_filter = $flt_user_access || $is_admin;
                                                            ?>
                                                            <?php if ( $show_flt_filter ) : ?>
                                                            <div class="col-md-3">
                                                                <label for="analyticsFlt" class="form-label">Load Type</label>
                                                                <select class="form-select" id="analyticsFlt" name="flt">
                                                                    <option value="">All Types</option>
                                                                    <option value="0">Regular</option>
                                                                    <option value="1">FLT</option>
                                                                </select>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div class="col-12">
                                                                <button type="submit" class="btn btn-primary">Load Analytics</button>
                                                                <button type="button" class="btn btn-info" id="loadTimerLogs">Load Timer Logs</button>
                                                                <button type="button" class="btn btn-success" id="exportAnalytics">Export to Excel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Analytics Results -->
                                        <div id="analyticsResults" class="mb-2" style="display: none;">
                                           

                                            <!-- Zone Distribution -->
                                            <div class="row mb-4">

								    	<div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6>Overall Statistics</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row" id="overallStats">
                                                                <!-- Will be populated by JavaScript -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6>Timer Zone Distribution</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div id="zoneDistribution">
                                                                <!-- Will be populated by JavaScript -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- User Analytics -->
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6>User Performance</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-striped" id="userAnalyticsTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>User</th>
                                                                            <th>Timer Uses</th>
                                                                            <th>Updates</th>
                                                                            <th>Yellow Zone</th>
                                                                            <th>Red Zone</th>
                                                                            <th>Black Zone</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <!-- Will be populated by JavaScript -->
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Timer Logs -->
                                            <div class="row mt-4">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6>Timer Logs</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-striped" id="timerLogsTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Time</th>
                                                                            <th>User</th>
                                                                            <th>Load ID</th>
                                                                            <th>Action</th>
                                                                            <th>Comment</th>
                                                                            <th>Project</th>
                                                                            <th>Type</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <!-- Will be populated by JavaScript -->
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div class="text-center mt-3">
                                                                <button type="button" class="btn btn-outline-primary" id="loadMoreLogs" style="display: none;">Load More Logs</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Loading State -->
                                        <div id="analyticsLoading" style="display: none;">
                                            <div class="text-center">
                                                <div class="spinner-border" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p>Loading analytics...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- End Detailed Statistics Tab -->
                            </div> <!-- End Tab Content -->
						<?php endif; ?>

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
