<?php
$user       = $args[ 'user' ];
$user_stats = $args[ 'user_stats' ];

?>

<?php if ( $user_stats[ 'total' ] > 0 ): ?>

    <div class="card-tracking-stats">
        <p class="card-tracking-stats__user" title="<?php echo esc_attr( $user[ 'name' ] ); ?>"
           style="background-color: <?php echo esc_attr( $user[ 'initials_color' ] ); ?>;">
			<?php echo esc_html( $user[ 'initials' ] ); ?>
        </p>

        <ul>
            <li>
                <span>At Pick Up: <?php echo esc_html( $user_stats[ 'at-pu' ] ?? 0 ); ?></span>
                <span>Total: <?php echo esc_html( $user_stats[ 'total' ] ?? 0 ); ?></span>
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
        </ul>
    </div>

<?php endif; ?>