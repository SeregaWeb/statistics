<?php
/**
 * Template Name: Page account
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

global $global_options;

$account = get_field_value( $global_options, 'link_to_account' );

$user_id = ( isset( $_GET[ 'user' ] ) && is_numeric( $_GET[ 'user' ] ) ) ? $_GET[ 'user' ] : get_current_user_id();

$TMSUser    = new TMSUsers();
$TMSReports = new TMSReports();
$statistics = new TMSStatistics();

$USER_OBJECT = $TMSUser->get_account_info( $user_id );

?>
    <div class="container-fluid account">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12">
						
						<?php if ( $USER_OBJECT ) {
							// USER ISSET
							$counts = $TMSReports->get_load_counts_by_user_id( $user_id, $USER_OBJECT[ 'permission_project' ] );
							$total  = array_sum( $counts );
							
							$progress = $statistics->get_dispatcher_statistics_current_month( $user_id );
							?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="account-avatar"
                                     style="background-color: <?php echo $USER_OBJECT[ 'color' ]; ?>">
									<?php echo $USER_OBJECT[ 'initials' ]; ?>
                                </div>
                                <div>
                                    <h2 class="account-name"><?php echo $USER_OBJECT[ 'name' ]; ?> <span
                                                class="account-role"><?php echo $USER_OBJECT[ 'role' ]; ?></span></h2>
                                    <p class="account-email"><?php echo $USER_OBJECT[ 'email' ]; ?></p>
                                    <p class="account-region"><?php echo $USER_OBJECT[ 'region' ]; ?></p>
                                </div>
                            </div>

                            <div class="account-info">
								<?php if ( is_array( $USER_OBJECT[ 'permission_project' ] ) ): ?>
                                    <div class="account-access">


                                        <h5>
                                            Access to projects
											<?php if ( $TMSUser->check_user_role_access( array(
												'recruiter',
												'billing',
												'tracking',
												'accounting'
											) ) ) : ?>
                                                <span class="text">(Total loads: <?php echo $total ?>)</span>
											<?php endif; ?>
                                        </h5>

                                        <div class="d-flex gap-1 mt-1">
											<?php foreach ( $USER_OBJECT[ 'permission_project' ] as $project ): ?>
                                                <span class="label-border <?php echo strtolower( $project ); ?>"><?php echo $project; ?>
													<?php
													if ( isset( $counts[ $project ] ) && + $counts[ $project ] !== 0 ) {
														echo '(' . $counts[ $project ] . ')';
													}
													?>
                                                </span>
											<?php endforeach; ?>
                                        </div>
                                    </div>
								<?php endif; ?>
								
								<?php if ( $TMSUser->check_user_role_access( array(
									'dispatcher',
									'dispatcher-tl'
								), true ) ) :
									if ( is_array( $progress ) && isset( $progress[ 0 ] ) ):
										$total_profit = number_format( $progress[ 0 ][ 'total_profit' ], 2 );
										$goal = $progress[ 0 ][ 'goal' ];
										$post_count = $progress[ 0 ][ 'post_count' ];
										$proc = 0;
										$current_month_name = date( 'F' );
										if ( is_numeric( $goal ) && $goal > 0 ) {
											$proc            = ( $progress[ 0 ][ 'total_profit' ] / + $goal ) * 100;
											$goal_completion = number_format( $proc, 2 );
										} else {
											$goal_completion = 'N/A';
										}
										
										?>
                                        <div class="mt-4 mb-4">
                                            <h5 class="mb-2">Your goal for <?php echo $current_month_name; ?></h5>
                                            <div class="d-flex justify-content-between">
                                                <p>$<?php echo $total_profit; ?>
                                                    <span>(<?php echo $post_count; ?>)</span></p>
                                                <p>$<?php echo $progress[ 0 ][ 'goal' ]; ?></p>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-striped <?php echo $proc >= 100
													? 'bg-success' : ''; ?>" role="progressbar"
                                                     style="width: <?php echo $proc; ?>%;"
                                                     aria-valuenow="<?php echo $goal_completion; ?>" aria-valuemin="0"
                                                     aria-valuemax="100"><?php echo $goal_completion; ?>%
                                                </div>
                                            </div>
                                        </div>
									<?php endif; ?>
								<?php endif; ?>
								
								<?php if ( is_array( $USER_OBJECT[ 'my_team' ] ) ): ?>
                                    <div class="account-team mt-3 w-100">
                                        <h5>My team</h5>
                                        <div class="d-flex gap-1 mt-1">
                                            <ol class="list-users">
												<?php foreach ( $USER_OBJECT[ 'my_team' ] as $user ):
													$user_names = $TMSUser->get_user_full_name_by_id( $user );
													$color = get_field( 'initials_color', 'user_' . $user );
													$view_tables = get_field( 'permission_view', 'user_' . $user );
													?>
                                                    <li class="user-card">
                                                        <div class="user-card__container">
                                                    <span class="user-card__circle"
                                                          style="background-color: <?php echo $color; ?>">
                                                        <?php echo $user_names[ 'initials' ]; ?>
                                                    </span>
                                                            <div>
                                                                <span class="user-card__name"><?php echo $user_names[ 'full_name' ]; ?></span>
																<?php if ( is_array( $view_tables ) ): ?>
                                                                    <div class="d-flex gap-1">
																		<?php foreach ( $view_tables as $project ): ?>
                                                                            <span class="label-border label-border__small <?php echo strtolower( $project ); ?>"><?php echo $project; ?></span>
																		<?php endforeach; ?>
                                                                    </div>
																<?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <a class="user-card__link link link-primary"
                                                           href="<?php echo $account . '?user=' . $user ?>">View
                                                            profile</a>
                                                    </li>
												<?php endforeach; ?>
                                            </ol>
                                        </div>
                                    </div>
								<?php endif; ?>
                            </div>
							<?php
						} else {
							echo $TMSUser->message_top( 'danger', $TMSUser->messages_prepare( 'user-not-found' ) );
						} ?>

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
