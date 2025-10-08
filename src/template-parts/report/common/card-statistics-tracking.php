<?php
/**
 * Card Statistics Tracking Template
 *
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validate and sanitize arguments
$args       = $args ?? array();
$user       = wp_parse_args( $args[ 'user' ] ?? array(), array(
	'id'             => 0,
	'name'           => '',
	'initials'       => '',
	'initials_color' => '#030303',
	'my_team'        => array()
) );
$user_stats = $args[ 'user_stats' ] ?? array();
$total      = $args[ 'total' ] ?? 0;

// Validate data types
$user_stats        = is_array( $user_stats ) ? $user_stats : array();
$user[ 'my_team' ] = is_array( $user[ 'my_team' ] ) ? $user[ 'my_team' ] : array();
$total             = is_numeric( $total ) ? (int) $total : 0;

// Initialize helper class
$helper = new TMSReportsHelper();

// Only display card if user has statistics
if ( $helper->has_meaningful_stats( $user_stats ) && isset( $user_stats[ 'total' ] ) ) :
	
	// Set total class for styling
	$class_total = ( $total > 0 && $total <= $user_stats[ 'total' ] ) ? 'text-danger' : '';
	
	// Clean up team members and get valid ones
	$valid_team_members = ! empty( $user[ 'my_team' ] )
		? $helper->cleanup_invalid_team_members( $user[ 'id' ], $user[ 'my_team' ] ) : array();
	?>

    <div class="card-tracking-stats">
		<?php if ( ! empty( $user[ 'name' ] ) && ! empty( $user[ 'initials' ] ) ) : ?>
            <p class="card-tracking-stats__user"
               title="<?php echo esc_attr( $user[ 'name' ] ); ?>"
               style="background-color: <?php echo esc_attr( $user[ 'initials_color' ] ); ?>;">
				<?php echo esc_html( $user[ 'initials' ] ); ?>
            </p>
		<?php endif; ?>

        <ul>
            <li>
				<?php if ( ! empty( $user_stats[ 'at-pu' ] ) && $user_stats[ 'at-pu' ] > 0 ) : ?>
                    <span>At Pick Up: <?php echo esc_html( $user_stats[ 'at-pu' ] ); ?></span>
				<?php endif; ?>
				
				<?php if ( ! empty( $user_stats[ 'total' ] ) && $user_stats[ 'total' ] > 0 ) : ?>
                    <span>Total: <span
                                class="<?php echo esc_attr( $class_total ); ?>"><?php echo esc_html( $user_stats[ 'total' ] ); ?></span></span>
				<?php endif; ?>
            </li>
			
			<?php if ( ! empty( $user_stats[ 'at-del' ] ) && $user_stats[ 'at-del' ] > 0 ) : ?>
                <li>
                    <span>At Delivery: <?php echo esc_html( $user_stats[ 'at-del' ] ); ?></span>
                </li>
			<?php endif; ?>
			
			<?php if ( ! empty( $user_stats[ 'loaded-enroute' ] ) && $user_stats[ 'loaded-enroute' ] > 0 ) : ?>
                <li>
                    <span>Loaded: <?php echo esc_html( $user_stats[ 'loaded-enroute' ] ); ?></span>
                </li>
			<?php endif; ?>
			
			<?php if ( ! empty( $user_stats[ 'waiting-on-pu-date' ] ) && $user_stats[ 'waiting-on-pu-date' ] > 0 ) : ?>
                <li>
                    <span>Waiting: <?php echo esc_html( $user_stats[ 'waiting-on-pu-date' ] ); ?></span>
                </li>
			<?php endif; ?>
			
			<?php if ( ! empty( $valid_team_members ) ) : ?>
                <li class="mt-1">
                    <div>
                        <p class="mb-0">Team</p>
                        <div class="d-flex gap-1 flex-wrap">
							<?php
							$TMSUser              = new TMSUsers();
							foreach ( $valid_team_members as $member_id ) :
								$user_data = $TMSUser->get_user_full_name_by_id( $member_id );
								
								if ( $user_data && is_array( $user_data ) ) :
									$member_color = $helper->get_user_color( $member_id );
									$member_stats = $user_stats[ 'user_' . $member_id ] ?? 0;
									?>
                                    <span data-bs-toggle="tooltip"
                                          data-bs-placement="top"
                                          title="<?php echo esc_attr( $user_data[ 'full_name' ] ); ?> (<?php echo esc_attr( $member_stats ); ?>)"
                                          class="initials-circle"
                                          style="background-color: <?php echo esc_attr( $member_color ); ?>">
										<?php echo esc_html( $user_data[ 'initials' ] ); ?>
									</span>
								<?php endif; ?>
							<?php endforeach; ?>
                        </div>
                    </div>
                </li>
			<?php endif; ?>
        </ul>
    </div>

<?php endif; ?>