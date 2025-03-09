<?php
$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();

$top3                = $statistics->get_table_top_3_loads();
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );
$dispatchers         = $statistics->get_dispatchers();

if ( ! is_numeric( $dispatcher_initials ) ) {
	$dispatcher_initials = $dispatchers[ 0 ][ 'id' ];
}

$statistics_with_status = $statistics->get_dispatcher_statistics_with_status( $dispatcher_initials );

$all_stats = $statistics->get_all_users_statistics();

?>

<div class="row w-100">
    <div class="col-12 col-md-6">
        <div class="top d-flex justify-content-start align-items-start flex-column">
            <h2 class="top-title">Biggest profit from a single load</h2>
            <div class="top-3">
				<?php if ( is_array( $top3 ) ): ?>
					<?php foreach ( $top3 as $top ):
						$names = $statistics->get_user_full_name_by_id( $top[ 'dispatcher_initials' ] );
						?>
                        <div class="top-3__card">
                            <div class="top-3__small">
								<?php echo $names[ 'initials' ]; ?>
                            </div>
                            <p class="top-3__name">
								<?php echo $names[ 'full_name' ]; ?>
                            </p>
                            <p class="top-3__sum">
                                $<?php echo $top[ 'profit' ]; ?>
                            </p>
                            <span class="text-primary text-small">
                                <?php echo $top[ 'reference_number' ]; ?>
                            </span>
                        </div>
					<?php endforeach; ?>
				<?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">

        <form class="w-100 mt-3">
            <div class="w-100 mb-2">
                <h2>Total cancelled loads</h2>
            </div>
            <div class="d-flex gap-1">
                <input type="hidden" name="active_state" value="top">
                <select class="form-select w-auto" name="dispatcher"
                        aria-label=".form-select-sm example">
					<?php if ( is_array( $dispatchers ) ): ?>
						<?php foreach ( $dispatchers as $dispatcher ): ?>
                            <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
								? 'selected' : ''; ?> >
								<?php echo $dispatcher[ 'fullname' ]; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
                <button class="btn btn-primary" type="submit">Select</button>
            </div>
			
			<?php if ( isset( $statistics_with_status[ 0 ] ) ): ?>
                <p class="mt-2  mb-0">Load with status <span
                            class="text-uppercase text-danger">Cancelled</span>
                </p>
                <p class="text-danger text-l fs-1 mt-0"><?php echo $statistics_with_status[ 0 ][ 'post_count' ]; ?></p>
			<?php endif; ?>

        </form>

    </div>
    <div class="col-12 mt-3">
		<?php
		if ( is_array( $all_stats ) ):
			echo '<table border="1" class="table-stat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>Dispatcher Initials</th>';
			echo '<th>Loads</th>';
			echo '<th>Profit</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			
			foreach ( $all_stats as $dispatcher ) {
				echo '<tr>';
				echo '<td>
                    <div class="d-flex gap-1 flex-row align-items-center">
                        <p class="m-0">
                            <span data-bs-toggle="tooltip" class="initials-circle" style="background-color:' . $dispatcher[ 'color' ] . '">
                              ' . $dispatcher[ "initials" ] . '
                            </span>
                        </p>
                        ' . htmlspecialchars( $dispatcher[ 'name' ] ) . '
                    </div>
                </td>';
				echo '<td>' . htmlspecialchars( $dispatcher[ 'post_count' ] ) . '</td>';
				echo '<td>' . esc_html( '$' . $helper->format_currency( $dispatcher[ 'total_profit' ] ) ) . '</td>';
				echo '</tr>';
			}
			
			echo '</tbody>';
			echo '</table>';
		endif; ?>
    </div>
</div>
