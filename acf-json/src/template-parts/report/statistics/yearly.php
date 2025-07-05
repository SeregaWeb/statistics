<?php

$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();
$TMSUsers   = new TMSUsers();

$current_year        = date( 'Y' );
$year_param          = get_field_value( $_GET, 'fyear' );
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );

$need_office = $TMSUsers->check_user_role_access( array(
	'dispatcher',
), true );

$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );

$dispatchers = $statistics->get_dispatchers( $need_office ? $office_dispatcher : null );

if ( ! is_numeric( $dispatcher_initials ) ) {
	$dispatcher_initials = $dispatchers[ 0 ][ 'id' ];
}

if ( ! is_numeric( $year_param ) ) {
	$year_param = $current_year;
}

$montly = $statistics->get_monthly_dispatcher_stats( intval( $dispatcher_initials ), intval( $year_param ) );

?>
    <form class="monthly w-100 ">
        <input type="hidden" name="active_state" value="yearly">

        <div class="d-flex gap-1">
            <select class="form-select w-auto" required name="fyear"
                    aria-label=".form-select-sm example">
                <option value="">Year</option>
				<?php
				for ( $year = 2023; $year <= $current_year; $year ++ ) {
					$select = is_numeric( $year_param ) && + $year_param === + $year ? 'selected' : '';
					echo '<option ' . $select . ' value="' . $year . '">' . $year . '</option>';
				}
				?>
            </select>
            <select class="form-select w-auto" required name="dispatcher"
                    aria-label=".form-select-sm example">
                <option value="">Dispatcher</option>
				<?php if ( is_array( $dispatchers ) ): ?>
					<?php foreach ( $dispatchers as $dispatcher ): ?>
                        <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
							? 'selected' : ''; ?> >
							<?php echo $dispatcher[ 'fullname' ]; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif; ?>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
		<?php
		echo '<table class="table-stat">';
		echo '<thead><tr><th>Month</th><th>Loads</th><th>Profit</th><th>Average per load</th><th>Average daily per month</th></tr></thead>';
		echo '<tbody>';
		
		$total_loads          = 0;
		$total_total_profit   = 0;
		$total_average_profit = 0;
		
		foreach ( $montly as $month_data ) {
			
			$hide_column = $month_data[ 'post_count' ] === 0 ? 'd-none' : '';
			$work_day    = $statistics->countWeekdays( $month_data[ 'month' ], $year_param );
			
			echo '<tr class="' . $hide_column . '">';
			echo '<td>' . $month_data[ 'month' ] . '</td>';
			echo '<td>' . $month_data[ 'post_count' ] . '</td>';
			echo '<td>$' . number_format( $month_data[ 'total_profit' ], 2 ) . '</td>';
			echo '<td>$' . number_format( $month_data[ 'average_profit' ], 2 ) . '</td>';
			echo '<td title="Days: ' . $work_day . '">$' . number_format( $month_data[ 'total_profit' ] / $work_day, 2 ) . '</td>';
			echo '</tr>';
			
			
			$total_loads          += $month_data[ 'post_count' ];
			$total_total_profit   += $month_data[ 'total_profit' ];
			$total_average_profit += $month_data[ 'average_profit' ];
		}
		
		echo '</tbody>';
		echo '</table>';
		?>
    </form>

<?php
if ( isset( $total_loads ) && isset( $total_total_profit ) && isset( $total_average_profit ) ):
	echo '<h2>Total per year</h2>';
	
	echo '<table class="table-stat total" border="1" cellpadding="5" cellspacing="0">';
	echo '<tr class="text-left">';
	echo '<th>Loads</th>';
	echo '<th>Profit</th>';
	echo '<th>Average Profit</th>';
	echo '</tr>';
	echo '<tr class="text-left">';
	echo '<td>' . $total_loads . '</td>';
	echo '<td>$' . number_format( $total_total_profit, 2 ) . '</td>';
	echo '<td>$' . number_format( $total_average_profit, 2 ) . '</td>';
	echo '</tr>';
	echo '</table>';
endif;
?>