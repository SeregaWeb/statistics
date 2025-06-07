<?php
/**
 * Template Name: Page statistics tracking
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$TMSUser    = new TMSUsers();
$TMSReports = new TMSReports();
$statistics = new TMSStatistics();

$exclude = get_field( 'exclude' );

$office_dispatcher = get_field_value( $_GET, 'office' );
$active_item       = get_field_value( $_GET, 'active_state' );
$offices           = $TMSReports->get_offices_from_acf();


$select_all_offices = $TMSUser->check_user_role_access( array(
	'administrator',
	'tracking',
	'tracking-tl',
), true );

$show_filter_by_office = $select_all_offices;

if ( $select_all_offices ) {
	$office_dispatcher = $office_dispatcher ? $office_dispatcher : 'all';
} else if ( ! $office_dispatcher ) {
	$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );
}


$items = $TMSReports->get_table_items_tracking_statistics( $office_dispatcher );

$users       = $TMSReports->get_tracking_users_for_statistics( '', $office_dispatcher );
$project     = $TMSReports->project;
$dispatchers = $items[ 'dispatchers' ];

?>
    <div class="container-fluid tracking-statistics">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3">
						
						<?php if ( $show_filter_by_office ): ?>
                            <form class="w-100 d-flex gap-1 align-items-start">
                                <select class="form-select w-auto" name="office"
                                        aria-label=".form-select-sm example">
                                    <option value="all">All offices</option>
									<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
										<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key
												? 'selected' : '' ?> >
												<?php echo $val; ?>
                                            </option>
										<?php endforeach; ?>
									<?php endif; ?>
                                </select>
                                <button class="btn btn-primary">Select</button>
                            </form>
						<?php endif; ?>

                        <div class="tracking-statistics__wrapper">
                            <div class="card-tracking-stats">
                                <p class="card-tracking-stats__project"><?php echo $project; ?>:</p>

                                <ul>
                                    <li class="">
                                    <span>
                                        Al pick up: <?php echo $items[ 'grand_total' ][ 'at-pu' ]; ?></span> <span>Total: <?php echo $items[ 'grand_total' ][ 'total' ]; ?>
                                    </span>
                                    </li>
                                    <li>
                                        <span>At delivery: <?php echo $items[ 'grand_total' ][ 'at-del' ]; ?></span>
                                    </li>
                                    <li>
                                    <span>
                                        Loaded: <?php echo $items[ 'grand_total' ][ 'loaded-enroute' ]; ?>
                                    </span>
                                    </li>
                                    <li>
                                    <span>
                                        Waiting: <?php echo $items[ 'grand_total' ][ 'waiting-on-pu-date' ]; ?>
                                    </span>
                                    </li>
									
									<?php if ( isset( $items[ 'dispatchers' ] ) ): ?>
                                        <li>
                                            <div>
                                                <p class="mb-0">Dispatchers</p>
                                                <div class="d-flex gap-1 flex-wrap">
													<?php
													foreach ( $items[ 'dispatchers' ] as $key => $item ):
														$user_team = str_replace( 'user_', '', $key );
														$user_arr = $TMSUser->get_user_full_name_by_id( $user_team );
														$color_initials = $user_arr
															? get_field( 'initials_color', 'user_' . $user_team )
															: '#030303';
														if ( ! $user_arr ) {
															$user_arr = array(
																'full_name' => 'User not found',
																'initials'  => 'NF'
															);
														}
														?>
                                                        <span data-bs-toggle="tooltip" data-bs-placement="top"
                                                              title="<?php echo $user_arr[ 'full_name' ]; ?>"
                                                              class="initials-circle"
                                                              style="background-color: <?php echo $color_initials; ?>">
                                            <?php echo esc_html( $user_arr[ 'initials' ] ); ?>
                                        </span>
													<?php
													endforeach;
													?>
                                                </div>
                                            </div>
                                        </li>
									<?php endif; ?>
                                </ul>
                            </div>

                        </div>
                        <hr>

                        <div class="tracking-statistics__wrapper align-items-start">
							<?php if ( $users ): ?>
								<?php foreach ( $users[ 'tracking' ] as $user ):
									$user_stats = $TMSReports->get_total_by_tracking_team( $user, $items );
									echo get_template_part( TEMPLATE_PATH . 'common/card', 'statistics-tracking', array(
										'user'       => $user,
										'user_stats' => $user_stats,
										'total'      => 25,
									) );
									
									?>
								<?php endforeach; ?>
							<?php endif; ?>
                        </div>

                        <h3>Nightshift</h3>
                        <div class="tracking-statistics__wrapper">
							<?php if ( $users ): ?>
								<?php foreach ( $users[ 'nightshift' ] as $user ):
									$user_stats = $TMSReports->get_total_by_tracking_team( $user, $items );
									echo get_template_part( TEMPLATE_PATH . 'common/card', 'statistics-tracking', array(
										'user'       => $user,
										'user_stats' => $user_stats,
									) );
								endforeach; ?>
							<?php endif; ?>
                        </div>

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
