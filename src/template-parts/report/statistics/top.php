<?php
$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();
$TMSUsers   = new TMSUsers();

$top3                = $statistics->get_table_top_3_loads();
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );
$dispatchers         = $statistics->get_dispatchers();

$office_dispatcher  = get_field_value( $_GET, 'office' );
$active_item        = get_field_value( $_GET, 'active_state' );
$select_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'moderator',
	'hr_manager',
), true );

if ( ! $office_dispatcher ) {
	$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );
}

$statistics_with_status = $statistics->get_dispatcher_statistics_with_status( $dispatcher_initials );

$all_stats = $statistics->get_all_users_statistics();

$offices = $helper->get_offices_from_acf();
if ( ! $office_dispatcher ) {
	$office_dispatcher = $offices[ 'choices' ][ 0 ];
}

$office_stat = array(
	'stats'  => $statistics->get_profit_by_office_stats( $office_dispatcher ),
	'office' => $office_dispatcher,
);

$show_only = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'moderator',
), true );

?>

<div class="row w-100">
    <div class="col-12 col-lg-6 mb-3">
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
    <hr>
    <div class="col-12 mb-3"></div>
	
	<?php if ( $show_only ): ?>
        <div class="col-12 col-lg-6 ">
            <h2 class="top-title">Highest result a day</h2>
            <form class="w-100 d-flex gap-1 js-auto-submit-form">
                <select class="form-select w-auto" name="office" aria-label=".form-select-sm example">
                    <option value="all">All Offices</option>
					
					<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
						<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                            <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key ? 'selected'
								: '' ?> >
								<?php echo $val; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
                <input type="hidden" name="active_state" value="<?php echo $active_item; ?>">
				<?php if ( $dispatcher_initials ): ?>
                    <input type="hidden" name="dispatcher" value="<?php echo $dispatcher_initials; ?>">
				<?php endif; ?>
                <!-- <button class="btn btn-primary">Select Office</button> -->
            </form>

            <table class="table-stat">
                <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Best load</th>
                    <th scope="col">Total Profit</th>
                </tr>
                </thead>
                <tbody>
				<?php if ( ! empty( $office_stat[ 'stats' ] ) ) : ?>
                    <tr>
                        <td><?php echo $office_stat[ 'stats' ][ 'date_us' ]; ?></td>
                        <td><?php echo esc_html( '$' . $helper->format_currency( $office_stat[ 'stats' ][ 'max' ] ) ); ?></td>
                        <td><?php echo esc_html( '$' . $helper->format_currency( $office_stat[ 'stats' ][ 'total' ] ) ); ?></td>
                    </tr>
				<?php else : ?>
                    <tr>
                        <td colspan="2" class="text-center text-muted">No data available</td>
                    </tr>
				<?php endif; ?>
                </tbody>
            </table>
        </div>
	<?php endif; ?>

    <div class="col-12 col-lg-6">

        <form class="w-100 js-auto-submit-form">
            <div class="w-100 ">
                <h2>Total cancelled loads</h2>
            </div>
            <div class="d-flex gap-1">
                <input type="hidden" name="active_state" value="top">
				<?php if ( $office_dispatcher ): ?>
                    <input type="hidden" name="office" value="<?php echo $office_dispatcher; ?>">
				<?php endif; ?>
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
                <!-- <button class="btn btn-primary" type="submit">Select</button> -->
            </div>
			
			<?php if ( isset( $statistics_with_status[ 0 ] ) ): ?>
                <p class="mt-2  mb-0">Load with status <span
                            class="text-uppercase text-danger">Cancelled</span>
                </p>
                <p class="text-danger text-l fs-1 mt-0"><?php echo $statistics_with_status[ 0 ][ 'post_count' ]; ?></p>
			<?php endif; ?>

        </form>

    </div>

    <div class="col-12 mb-3"></div>

    <div class="col-12 ">
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
