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

$items = $TMSReports->get_table_items_tracking_statistics();
$users = $TMSReports->get_tracking_users_for_statistics( $exclude );

$project     = $TMSReports->project;
$dispatchers = $items[ 'dispatchers' ];

?>
    <div class="container-fluid tracking-statistics">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12">
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
                                </ul>

                            </div>
                        </div>
                        <hr>

                        <div class="tracking-statistics__wrapper">
							<?php if ( $users ): ?>
								<?php foreach ( $users[ 'tracking' ] as $user ):
									$user_stats = $TMSReports->get_total_by_tracking_team( $user, $items );
									
									echo get_template_part( TEMPLATE_PATH . 'common/card', 'statistics-tracking', array(
										'user'       => $user,
										'user_stats' => $user_stats
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
										'user_stats' => $user_stats
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
