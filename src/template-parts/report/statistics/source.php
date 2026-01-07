<?php
$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();
$TMSUsers   = new TMSUsers();

$active_item       = get_field_value( $_GET, 'active_state' );
$office_dispatcher = get_field_value( $_GET, 'office' );

$current_year  = date( 'Y' ); // Returns the current year
$current_month = date( 'm' ); // Returns the current month

$year_param          = get_field_value( $_GET, 'year_param' );
$mount_param         = get_field_value( $_GET, 'mount_param' );
$office              = get_field_value( $_GET, 'office' );
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' ) ?? 'all';
$dispatchers         = $statistics->get_dispatchers( null, false, true );

if ( ! $year_param ) {
	$year_param = $current_year;
}
if ( ! $mount_param ) {
	$mount_param = $current_month;
}

$select_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
  'hr_manager',
	'moderator'
), true );

if ( $select_all_offices ) {
	$office_dispatcher = $office_dispatcher ? $office_dispatcher : 'all';
} else if ( ! $office_dispatcher ) {
	$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );
}
$offices = $helper->get_offices_from_acf();

$show_filter_by_office = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
  'hr_manager',
	'tracking',
  'morning_tracking',
  'nightshift_tracking',
	'moderator'
), true );
?>
<div class="w-100 ">
    <div class="w-100 mb-2">
        <h2>Source</h2>
		<?php
		$dispatcher_json = $statistics->get_sources_statistics( $year_param, $mount_param, $office_dispatcher, $dispatcher_initials );
		
		if ( $show_filter_by_office ): ?>
            <form class="w-100 d-flex flex-column gap-1 pb-1">

                <div class="w-100 d-flex gap-1">
                    <select class="form-select w-auto" required name="year_param"
                            aria-label=".form-select-sm example">
                        <option value="">Year</option>
                        <option value="all" <?php echo $year_param === 'all' ? 'selected' : ''; ?>>All
                            time
                        </option>
						<?php
						
						for ( $year = 2024; $year <= $current_year; $year ++ ) {
							$select = is_numeric( $year_param ) && + $year_param === + $year ? 'selected' : '';
							echo '<option ' . $select . ' value="' . $year . '">' . $year . '</option>';
						}
						?>
                    </select>
					
					<?php
					$months = $statistics->get_months();
					?>
                    <select class="form-select w-auto" name="mount_param"
                            aria-label=".form-select-sm example">
                        <option value="">Month</option>
                        <option value="all" <?php echo $mount_param === 'all' ? 'selected' : ''; ?>>All
                            time
                        </option>
						<?php
						foreach ( $months as $num => $name ) {
							
							$select = is_numeric( $mount_param ) && + $mount_param === + $num ? 'selected' : '';
							
							echo '<option ' . $select . ' value="' . $num . '">' . $name . '</option>';
						}
						?>
                    </select>

                    <select class="form-select w-auto" name="office"
                            aria-label=".form-select-sm example">
                        <option value="all">Company total</option>
						<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
							<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                                <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key ? 'selected'
									: '' ?> >
									<?php echo $val; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif; ?>
                    </select>
                    <input type="hidden" name="active_state"
                           value="<?php echo $active_item; ?>">
                    <button class="btn btn-primary">Show results</button>
                </div>

                <div class="w-100 d-flex gap-1">
                    <select class="form-select w-auto" name="dispatcher"
                            aria-label=".form-select-sm example">
                        <option value="all">Select dispatcher</option>
						<?php if ( is_array( $dispatchers ) ): ?>
							<?php foreach ( $dispatchers as $dispatcher ): ?>
                                <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
									? 'selected' : ''; ?> >
									<?php echo $dispatcher[ 'fullname' ]; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif; ?>
                    </select>
                </div>
            </form>
		<?php endif; ?>

        <div class="d-flex">
            <div id="sourcePostCountChart"
                 data-chart-data="<?php echo esc_attr( $dispatcher_json ); ?>"
                 style="width:100%; max-width:50%; height:50vh;"></div>
            <div id="sourceProfitChart"
                 data-chart-data="<?php echo esc_attr( $dispatcher_json ); ?>"
                 style="width:100%; max-width:50%; height:50vh;"></div>
        </div>

        <div>
            <h3>Total</h3>
			<?php
			// Decode JSON data to calculate totals
			$sources_data = json_decode( $dispatcher_json, true );
			$total_loads = 0;
			$total_profit_sum = 0;
			
			if ( is_array( $sources_data ) && ! empty( $sources_data ) ) {
				foreach ( $sources_data as $source_key => $source_data ) {
					if ( isset( $source_data['post_count'] ) ) {
						$total_loads += (int) $source_data['post_count'];
					}
					if ( isset( $source_data['total_profit'] ) ) {
						// Remove formatting (commas, dollar signs) and convert to float
						$profit_value = str_replace( array( ',', '$' ), '', $source_data['total_profit'] );
						$total_profit_sum += (float) $profit_value;
					}
				}
			}
			?>
			
			<?php if ( $total_loads > 0 || $total_profit_sum > 0 ): ?>
				<table class="table-stat total" border="1" cellpadding="5" cellspacing="0">
					<thead>
						<tr class="text-left">
							<th>Total Loads</th>
							<th>Total Profit</th>
						</tr>
					</thead>
					<tbody>
						<tr class="text-left">
							<td><?php echo esc_html( number_format( $total_loads, 0 ) ); ?></td>
							<td>$<?php echo esc_html( number_format( $total_profit_sum, 2 ) ); ?></td>
						</tr>
					</tbody>
				</table>
			<?php else: ?>
				<div class="alert alert-info">
					<p>No data available for the selected period.</p>
				</div>
			<?php endif; ?>
        </div>
    </div>
</div>