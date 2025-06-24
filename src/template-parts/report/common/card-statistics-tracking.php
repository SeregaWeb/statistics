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

// Extract and validate arguments
$user       = isset( $args['user'] ) ? $args['user'] : array();
$user_stats = isset( $args['user_stats'] ) ? $args['user_stats'] : array();
$TMSUser    = new TMSUsers();

$total = isset( $args['total'] ) ? $args['total'] : false;

$class_total = '';

// Check if user has any meaningful statistics
$has_stats = false;
if ( is_array( $user_stats ) ) {
	foreach ( $user_stats as $stat_value ) {
		if ( is_numeric( $stat_value ) && $stat_value > 0 ) {
			$has_stats = true;
			break;
		}
	}
}

// Only display card if user has statistics
if ( $has_stats && isset( $user_stats['total'] ) && $user_stats['total'] > 0 ) :
	
	// Set total class for styling
	if ( is_numeric( $total ) && $total <= $user_stats['total'] ) {
		$class_total = 'text-danger';
	}
	?>

	<div class="card-tracking-stats">
		<?php if ( isset( $user['name'] ) && isset( $user['initials'] ) && isset( $user['initials_color'] ) ) : ?>
			<p class="card-tracking-stats__user" 
			   title="<?php echo esc_attr( $user['name'] ); ?>"
			   style="background-color: <?php echo esc_attr( $user['initials_color'] ); ?>;">
				<?php echo esc_html( $user['initials'] ); ?>
			</p>
		<?php endif; ?>

		<ul>
			<?php if ( isset( $user_stats['at-pu'] ) && $user_stats['at-pu'] > 0 ) : ?>
				<li>
					<span>At Pick Up: <?php echo esc_html( $user_stats['at-pu'] ); ?></span>
					<?php if ( isset( $user_stats['total'] ) && $user_stats['total'] > 0 ) : ?>
						<span>
							Total: <span class="<?php echo esc_attr( $class_total ); ?>">
								<?php echo esc_html( $user_stats['total'] ); ?>
							</span>
						</span>
					<?php endif; ?>
				</li>
			<?php endif; ?>
			
			<?php if ( isset( $user_stats['at-del'] ) && $user_stats['at-del'] > 0 ) : ?>
				<li>
					<span>At Delivery: <?php echo esc_html( $user_stats['at-del'] ); ?></span>
				</li>
			<?php endif; ?>
			
			<?php if ( isset( $user_stats['loaded-enroute'] ) && $user_stats['loaded-enroute'] > 0 ) : ?>
				<li>
					<span>Loaded: <?php echo esc_html( $user_stats['loaded-enroute'] ); ?></span>
				</li>
			<?php endif; ?>
			
			<?php if ( isset( $user_stats['waiting-on-pu-date'] ) && $user_stats['waiting-on-pu-date'] > 0 ) : ?>
				<li>
					<span>Waiting: <?php echo esc_html( $user_stats['waiting-on-pu-date'] ); ?></span>
				</li>
			<?php endif; ?>
			
			<?php if ( isset( $user['my_team'] ) && is_array( $user['my_team'] ) && ! empty( $user['my_team'] ) ) : ?>
				<li class="mt-1">
					<div>
						<p class="mb-0">Team</p>
						<div class="d-flex gap-1 flex-wrap">
							<?php foreach ( $user['my_team'] as $user_team ) : ?>
								<?php
								$user_arr = $TMSUser->get_user_full_name_by_id( $user_team );
								
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
									  title="<?php echo esc_attr( $user_arr['full_name'] ); ?>"
									  class="initials-circle" 
									  style="background-color: <?php echo esc_attr( $color_initials ); ?>">
									<?php echo esc_html( $user_arr['initials'] ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					</div>
				</li>
			<?php endif; ?>
		</ul>
	</div>

<?php endif; ?>