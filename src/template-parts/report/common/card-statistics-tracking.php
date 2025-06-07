<?php
$user       = $args[ 'user' ];
$user_stats = $args[ 'user_stats' ];
$TMSUser    = new TMSUsers();

$total = isset( $args[ 'total' ] ) ? $args[ 'total' ] : false;

$class_total = '';

?>

<?php if ( $user_stats[ 'total' ] > 0 ):
	if ( is_numeric( $total ) && $total <= $user_stats[ 'total' ] ) {
		$class_total = 'text-danger';
	}
	?>

    <div class="card-tracking-stats">
        <p class="card-tracking-stats__user" title="<?php echo esc_attr( $user[ 'name' ] ); ?>"
           style="background-color: <?php echo esc_attr( $user[ 'initials_color' ] ); ?>;">
			<?php echo esc_html( $user[ 'initials' ] ); ?>
        </p>

        <ul>
            <li>
                <span>At Pick Up: <?php echo esc_html( $user_stats[ 'at-pu' ] ?? 0 ); ?></span>
                <span>Total:<span
                            class="<?php echo $class_total; ?>"> <?php echo esc_html( $user_stats[ 'total' ] ?? 0 ); ?></span></span>
            </li>
            <li>
                <span>At Delivery: <?php echo esc_html( $user_stats[ 'at-del' ] ?? 0 ); ?></span>
            </li>
            <li>
                <span>Loaded: <?php echo esc_html( $user_stats[ 'loaded-enroute' ] ?? 0 ); ?></span>
            </li>
            <li>
                <span>Waiting: <?php echo esc_html( $user_stats[ 'waiting-on-pu-date' ] ?? 0 ); ?></span>
            </li>
			
			<?php if ( isset( $user[ 'my_team' ] ) ): ?>
                <li>
                    <div>
                        <p class="mb-0">Team</p>
                        <div class="d-flex gap-1 flex-wrap ">
							<?php foreach ( $user[ 'my_team' ] as $user_team ):
								$user_arr = $TMSUser->get_user_full_name_by_id( $user_team );
								$color_initials = $user_arr ? get_field( 'initials_color', 'user_' . $user_team )
									: '#030303';
								if ( ! $user_arr ) {
									$user_arr = array( 'full_name' => 'User not found', 'initials' => 'NF' );
								}
								?>
                                <span data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="<?php echo $user_arr[ 'full_name' ]; ?>"
                                      class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                            <?php echo esc_html( $user_arr[ 'initials' ] ); ?>
                        </span>
							<?php endforeach; ?>
                        </div>
                    </div>
                </li>
			<?php endif; ?>
        </ul>
    </div>

<?php endif; ?>