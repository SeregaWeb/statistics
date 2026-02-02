<?php
global $global_options;

$flt        = get_field_value( $args, 'flt' );
$TMSReports = new TMSReports();

if ( $flt ) {
	$TMSReports = new TMSReportsFlt();
}

$TMSUsers  = new TMSUsers();
$TMSHelper = new TMSReportsHelper();
$TMSBroker = new TMSReportsCompany();
$TMSDrivers = new TMSDrivers();

$results                   = get_field_value( $args, 'results' );
$total_pages               = get_field_value( $args, 'total_pages' );
$current_pages             = get_field_value( $args, 'current_pages' );
$is_draft                  = get_field_value( $args, 'is_draft' );
$is_ar_problev             = get_field_value( $args, 'ar_problem' );
$office                    = get_field_value( $_GET, 'office' ) ?: get_field_value( $args, 'office' );
$hide_total                = get_field_value( $args, 'hide_total' );
$show_separator            = false;
$page_type                 = get_field_value( $args, 'page_type' );
$current_user_id           = get_current_user_id();
$profit_mod                = false;
$billing_info              = $TMSUsers->check_user_role_access( [ 'administrator', 'billing', 'accounting' ], true );
$hide_billing_and_shipping = $TMSUsers->check_user_role_access( [ 'billing', 'accounting' ], true );
$my_team                   = $TMSUsers->check_group_access();
$helper                    = $TMSUsers;

$rating_access             = $TMSUsers->check_user_role_access( [
	'administrator',
	'dispatcher',
	'dispatcher-tl',
	'tracking',
	'tracking-tl',
	'morning_tracking',
	'nightshift_tracking',
	'expedite_manager',
], true );

$trigger_keys = [ 'dispatcher', 'my_search', 'load_status', 'source' ];

foreach ( $trigger_keys as $key ) {
	if ( isset( $_GET[ $key ] ) && $_GET[ $key ] !== '' ) {
		$hide_total = true;
		break;
	}
}

// Ensure $results is an array (get_field_value can return null); explicit count for display
$results_list = is_array( $results ) ? $results : array();
$has_results  = count( $results_list ) > 0;

if ( $has_results ) :

	// Prepare filter arguments from $_GET
	$filter_args = array();
	$helper      = new TMSReportsHelper();
	
	// Get filter parameters using the same method as get_table_items
	$filter_args = $helper->set_filter_params( $filter_args );
	
	$platforms = $TMSReports->get_stat_platform( $filter_args );
	?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2 align-items-center">
			<?php if ( $platforms ):
				$labels_platforms = array(
					"highway" => "Highway",
					"mcp"     => "MCP",
					"rmis"    => "RMIS",
				)
				?>
				<?php foreach ( $platforms as $key => $platform ):
				if ( isset( $labels_platforms[ $key ] ) ): ?>
                    <div class="d-flex gap-1 align-items-start flex-column">
                        <p class="m-0"><?php echo $labels_platforms[ $key ]; ?> : <span
                                    class="text-primary m-0 font-weight-bold"><?php echo $platform; ?></span></p>
                    </div>
				<?php endif;
			endforeach; ?>
			<?php endif; ?>
        </div>
    </div>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">BOOKED DATE</th>
            <th scope="col" title="dispatcher">LOAD NO</th>
            <th scope="col">ORIGIN</th>
            <th scope="col">DESTINATION</th>
            <th scope="col">UNIT & NAME</th>
            <th scope="col">GROSS RATE</th>
            <th scope="col">DRIVER RATE</th>
            <th scope="col">Profit</th>
            <th scope="col">Miles</th>
            <th scope="col">PU DATE</th>
            <th scope="col">Load status</th>
            <th scope="col">Instructions</th>
            <th scope="col">Source</th>
			
			<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                <th scope="col"></th>
			<?php endif; ?>
        </tr>
        </thead>
        <tbody>
		<?php
		
		$array_date    = array();
		$previous_date = null;
		foreach ( $results_list as $row ) :
			$meta            = get_field_value( $row, 'meta_data' );
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			
			if ( $date_booked_raw ) {
				$array_date[] = substr( $date_booked_raw, 0, 10 );
			}
		
		endforeach;
		$array_date     = array_unique( $array_date );
		$new_array_date = $TMSReports->get_profit_by_dates( $array_date, $office );
		$index          = 0;
		foreach ( $results_list as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$driver_with_macropoint = $TMSHelper->get_driver_tempate( $meta );
			$pdlocations            = $helper->get_locations_template( $row );
			$dispatcher_initials    = get_field_value( $meta, 'dispatcher_initials' );

			$id_customer              = get_field_value( $meta, 'customer_id' );
			$current_company          = $TMSBroker->get_company_by_id( $id_customer );

			if ($current_company) {
				$current_company_name = $current_company[0]->company_name;
			} else {
				$current_company_name = '';
			}
			
			$dispatcher     = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials ) : '#030303';
			$dispatcher     = $dispatcher ?: [ 'full_name' => 'User not found', 'initials' => 'NF' ];
			
			$load_status = get_field_value( $meta, 'load_status' );

			$status      = esc_html( $helper->get_label_by_key( $load_status, 'statuses' ) );
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			
			// Primary driver for rating: priority third > second > first (same as load context)
			$attached_driver           = get_field_value( $meta, 'attached_driver' );
			$attached_second_driver   = get_field_value( $meta, 'attached_second_driver' );
			$attached_third_driver    = get_field_value( $meta, 'attached_third_driver' );
			$unit_number_name         = esc_html( get_field_value( $meta, 'unit_number_name' ) );
			$second_unit_number_name  = esc_html( get_field_value( $meta, 'second_unit_number_name' ) );
			$third_unit_number_name   = esc_html( get_field_value( $meta, 'third_unit_number_name' ) );
			$primary_driver_id        = 0;
			$primary_driver_name      = '';
			if ( ! empty( $attached_third_driver ) ) {
				$primary_driver_id   = (int) $attached_third_driver;
				$primary_driver_name = $third_unit_number_name ?: '';
			} elseif ( ! empty( $attached_second_driver ) ) {
				$primary_driver_id   = (int) $attached_second_driver;
				$primary_driver_name = $second_unit_number_name ?: '';
			} elseif ( ! empty( $attached_driver ) ) {
				$primary_driver_id   = (int) $attached_driver;
				$primary_driver_name = $unit_number_name ?: '';
			}
			
			// Load is "rated" for everyone when the dispatcher who created the load has rated it
			$dispatcher_name   = $dispatcher['full_name'] ?? '';
			$dispatcher_rated  = $TMSDrivers->has_rating_for_order_number_by_rater_name( $reference_number, $dispatcher_name );
			// Current user already rated — hide Rate button so they can't rate twice (secondary check)
			$current_user_info  = $helper->get_user_full_name_by_id( $current_user_id );
			$current_user_name  = $current_user_info ? $current_user_info['full_name'] : '';
			$current_user_rated = $TMSDrivers->has_rating_for_order_number_by_rater_name( $reference_number, $current_user_name );
			// Only the dispatcher who created the load can rate it (same as popup available_loads filter)
			$is_dispatcher_role = $TMSUsers->check_user_role_access( array( 'dispatcher', 'dispatcher-tl', 'expedite_manager' ), true );
			$can_rate_this_load = ! $is_dispatcher_role || ( (int) $dispatcher_initials === (int) $current_user_id );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' );
			$booked_rate     = esc_html( '$' . $helper->format_currency( $booked_rate_raw ) );
			
			$driver_rate_raw = get_field_value( $meta, 'driver_rate' );
			$driver_rate     = esc_html( '$' . $helper->format_currency( $driver_rate_raw ) );
			
			$second_driver_rate_raw = get_field_value( $meta, 'second_driver_rate' );
			$second_driver_rate     = esc_html( '$' . $helper->format_currency( $second_driver_rate_raw ) );

			$third_driver_rate_raw = get_field_value( $meta, 'third_driver_rate' );
			$third_driver_rate     = esc_html( '$' . $helper->format_currency( $third_driver_rate_raw ) );

			$high_priority = get_field_value( $meta, 'high_priority' );

			$clear_rate = $driver_rate_raw;

			if ( $second_driver_rate && $second_driver_rate_raw !== '0' ) {
				$clear_rate = $second_driver_rate_raw;
			}

			if ( $third_driver_rate && $third_driver_rate_raw !== '0' ) {
				$clear_rate = $third_driver_rate_raw;
			}
			
			$all_miles = get_field_value( $meta, 'all_miles' );
			$miles     = $helper->calculate_price_per_mile( $booked_rate_raw, $clear_rate, $all_miles );
			
			$tbd          = get_field_value( $meta, 'tbd' );
			$profit_raw   = get_field_value( $meta, 'profit' );
			$profit_class = $profit_raw < 0 ? 'modified-price' : '';
			$profit       = esc_html( '$' . $helper->format_currency( $profit_raw ) );
			
			// Determine if we should hide zeros based on status
			$is_tbd = ! empty( $tbd );
			$is_cancelled = ( $load_status === 'cancelled' );
			
			// Check if values are zero
			$booked_rate_is_zero = ( empty( $booked_rate_raw ) || $booked_rate_raw == '0' || $booked_rate_raw == 0 );
			$driver_rate_is_zero = ( empty( $driver_rate_raw ) || $driver_rate_raw == '0' || $driver_rate_raw == 0 );
			$profit_is_zero = ( empty( $profit_raw ) || $profit_raw == '0' || $profit_raw == 0 );
			
			$instructions = $helper->get_label_by_key( get_field_value( $meta, 'instructions' ), 'instructions' );
			$source       = esc_html( $helper->get_label_by_key( get_field_value( $meta, 'source' ), 'sources' ) );
			
			$modify_class              = $helper->get_modify_class( $meta, 'modify_price' );
			$modify_driver_price_class = $helper->get_modify_class( $meta, 'modify_driver_price' );
			$modify_second_driver_price_class = $helper->get_modify_class( $meta, 'modify_second_driver_price' );
			$modify_third_driver_price_class = $helper->get_modify_class( $meta, 'modify_third_driver_price' );
			
			if ( $previous_date !== $date_booked && ! is_null( $previous_date ) ) {
				$show_separator = true;
			}
			
			$id_customer   = get_field_value( $meta, 'customer_id' );
			$broker        = $TMSBroker->get_broker_and_link_by_id( $id_customer, false );
			$previous_date = $date_booked;
			
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			
			
			if ( ( $show_separator || $index === 0 ) && ! $hide_total ) {
				$date_search = substr( $date_booked_raw, 0, 10 );
				$profit_mod  = '';
				$average_mod = '';
				$source_mod  = '';
				
				if ( $date_booked_raw && isset( $new_array_date[ $date_search ] ) && ! $helper->hasUrlParams( [
						"fmonth",
						"fyear",
						"dispatcher",
						"load_status",
						"source"
					] ) ) {
					
					$formatted_profit = esc_html( '$' . $helper->format_currency( $new_array_date[ $date_search ][ 'total' ] ) );
					$profit_mod       = '<span style="text-transform: capitalize; margin-left: 40px;">Profit: <b>' . $formatted_profit . '</b></span>';
					
					$formatted_average = esc_html( '$' . $helper->format_currency( $new_array_date[ $date_search ][ 'average' ] ) );
					$average_mod       = '<span style="text-transform: capitalize; margin-left: 40px;">Average: <b>' . $formatted_average . '</b></span>';
					
					// Build source breakdown
					$source_mod = '';
					$sources = $TMSHelper->sources;
					$source_parts = array();
					
					foreach ( $sources as $source_key => $source_name ) {
						if ( isset( $new_array_date[ $date_search ][ $source_key ] ) ) {
							$source_data = $new_array_date[ $date_search ][ $source_key ];
							$source_count = isset( $source_data[ 'count' ] ) ? (int) $source_data[ 'count' ] : 0;
							$source_total = isset( $source_data[ 'total' ] ) ? (float) $source_data[ 'total' ] : 0;
							
							if ( $source_count > 0 && $source_total > 0 ) {
								$formatted_source_total = esc_html( '$' . $helper->format_currency( $source_total ) );
								$source_parts[] = $source_name . ' (' . $source_count . ') ' . $formatted_source_total;
							}
						}
					}
					
					if ( ! empty( $source_parts ) ) {
						$source_mod = '<span style="margin-left: 40px; text-transform: capitalize; font-size: 12px;">' . implode( ' | ', $source_parts ) . '</span>';
					}
					
				}
				
				$index          = 1;
				$show_separator = false;
				?>
                <tr>
                    <td colspan="14" class="separator-date">
					<div>
						<?php echo $date_booked . ' ' . $profit_mod . ' ' . $average_mod; ?>
						<?php if ( ! empty( $source_mod ) ) : ?>
							<?php echo $source_mod; ?>
						<?php endif; ?>
					</div>
                    </td>
                </tr>
				<?php
			}
			?>

            <tr  class="load-status-<?php echo $load_status; ?> <?php echo $tbd ? 'tbd' : ''; ?>">
                <td>
                    <label class="h-100 cursor-pointer" title="<?php echo $date_booked_raw; ?>"
                           for="load-<?php echo $row[ 'id' ]; ?>"><?php echo $date_booked; ?></label>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-row align-items-center">
                        <p class="m-0">
                        <span data-bs-toggle="tooltip" data-bs-placement="top"
                              title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                              class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                            <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
                        </span>
                        </p>
				    <div>
					<div class="d-flex gap-1 flex-row align-items-center">
						<?php echo $high_priority ? $TMSHelper->get_icon_high_priority() : ''; ?>

						<span class="text-small <?php echo $high_priority ? 'fw-bold text-decoration-underline' : ''; ?> platform-<?php echo isset( $broker[ 'platform' ] )
									? $broker[ 'platform' ] : ""; ?> <?php echo $has_rating ? 'has-rating' : ''; ?>"
								title="<?php echo isset( $broker[ 'platform' ] ) ? strtoupper( $broker[ 'platform' ] )
										: ""; ?><?php echo $has_rating ? ' | Has rating' : ''; ?>">
								<?php echo $reference_number; ?>
						</span>
					</div>
						
						<div class="d-flex flex-column">
							<span style="font-size: 10px;"><?php echo $current_company_name; ?></span>
						</div>
					</div>
                    </div>
				
                </td>
                <td><?php echo $pdlocations[ 'pick_up_template' ]; ?></td>
                <td><?php echo $pdlocations[ 'delivery_template' ]; ?></td>
                <td>
				<div class="d-flex gap-1 align-items-start">
				<?php echo $driver_with_macropoint; ?>
				<?php if ( $load_status === 'delivered' || $load_status === 'cancelled' || $load_status === 'tonu' ) : ?>
					<?php if ( $dispatcher_rated ) : ?>
						<?php echo $TMSHelper->get_icon_rating(); ?>
					<?php elseif ( $current_user_rated ) : ?>
						<?php echo $TMSHelper->get_icon_rating(); ?>
					<?php elseif ( $primary_driver_id && $rating_access && $can_rate_this_load ) : ?>
						<button type="button"
								class="btn btn-link p-0 border-0 align-baseline js-load-rating-btn"
								data-driver-id="<?php echo (int) $primary_driver_id; ?>"
								data-driver-name="<?php echo esc_attr( $primary_driver_name ?: __( 'Driver', 'wp-rock' ) ); ?>"
								data-load-number="<?php echo esc_attr( $reference_number ); ?>"
								data-load-status="<?php echo esc_attr( $load_status ); ?>"
								title="<?php esc_attr_e( 'Rate driver for this load', 'wp-rock' ); ?>"
								aria-label="<?php esc_attr_e( 'Rate driver for this load', 'wp-rock' ); ?>">
							<svg class="icon_no_rating" fill="#6c757d" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<title><?php esc_attr_e( 'Rate driver for this load', 'wp-rock' ); ?></title>
								<path d="M16.15,5.47l-4.06,6.49l-0.2-0.41c-0.25-0.49-0.85-0.69-1.34-0.45c-0.49,0.25-0.69,0.85-0.45,1.34l1,2  c0.16,0.32,0.49,0.53,0.85,0.55c0.02,0,0.03,0,0.05,0c0.34,0,0.67-0.18,0.85-0.47l5-8c0.29-0.47,0.15-1.08-0.32-1.38  C17.06,4.86,16.44,5,16.15,5.47z"/><path d="M5.95,15.23l-0.93,5.61c-0.06,0.37,0.09,0.75,0.4,0.97c0.31,0.22,0.71,0.25,1.05,0.08L12,18.98l5.54,2.91  C17.68,21.96,17.84,22,18,22c0.21,0,0.41-0.06,0.59-0.19c0.31-0.22,0.46-0.6,0.4-0.97l-0.93-5.61l3.78-5.67  c0.2-0.31,0.22-0.7,0.05-1.03S21.37,8,21,8h-2c-0.55,0-1,0.45-1,1s0.45,1,1,1h0.13l-2.96,4.45c-0.14,0.21-0.2,0.47-0.15,0.72  l0.67,4.01l-4.22-2.21c-0.29-0.15-0.64-0.15-0.93,0l-4.22,2.21l0.67-4.01c0.04-0.25-0.01-0.51-0.15-0.72L4.87,10H9  c0.38,0,0.72-0.21,0.89-0.55l1.97-3.94l0.31,0.84c0.19,0.52,0.77,0.78,1.28,0.59c0.52-0.19,0.78-0.77,0.59-1.28l-1.11-3  C12.8,2.28,12.45,2.02,12.05,2c-0.41-0.02-0.77,0.2-0.95,0.55L8.38,8H3C2.63,8,2.29,8.2,2.12,8.53S1.96,9.25,2.17,9.55L5.95,15.23z"/>
							</svg>
						</button>
					<?php endif; ?>
				<?php endif; ?>
				</div>
			</td>
                <td>
					<?php 
					// Hide Gross rate if cancelled and value is zero
					if ( ! ( $is_cancelled && $booked_rate_is_zero ) ): ?>
                        <span class="<?php echo $modify_class; ?>"><?php echo $booked_rate; ?></span>
						<?php if ( ! empty( $miles[ 'booked_rate_per_mile' ] ) ): ?>
                            <p class="text-small mb-0 mt-1"><?php echo '$' . $miles[ 'booked_rate_per_mile' ] . ' per mile'; ?></p>
						<?php endif; ?>
					<?php endif; ?>
                </td>
                <td>
					<?php 
					// Hide Driver rate if (TBD or Cancelled) and value is zero
					if ( ! ( ( $is_tbd || $is_cancelled ) && $driver_rate_is_zero ) ): ?>
                        <span class="<?php echo $modify_driver_price_class; ?>"><?php echo $driver_rate; ?></span>
						<?php if ( $second_driver_rate !== '$0' && $second_driver_rate_raw ): ?>
                            <br><br>
                            <span class="<?php echo $modify_second_driver_price_class; ?>"><?php echo $second_driver_rate; ?></span>
						<?php endif; ?>

						<?php if ( $third_driver_rate !== '$0' && $third_driver_rate_raw ): ?>
                            <br><br>
                            <span class="<?php echo $modify_third_driver_price_class; ?>"><?php echo $third_driver_rate; ?></span>
						<?php endif; ?>
						<br><br>
						<?php if ( ! empty( $miles[ 'driver_rate_per_mile' ] ) ): ?>
                            <p class="text-small mb-0 mt-1"><?php echo '$' . $miles[ 'driver_rate_per_mile' ] . ' per mile'; ?></p>
						<?php endif; ?>
					<?php endif; ?>
                </td>
                <td>
					<span class="<?php echo $profit_class; ?>">
						<?php 
						// Hide Profit if (TBD or Cancelled) and value is zero, or if waiting-on-rc
						if ( $load_status !== 'waiting-on-rc' && ! ( ( $is_tbd || $is_cancelled ) && $profit_is_zero ) ): 
							echo $profit; 
						endif; 
						?>
					</span>
				</td>
                <td><?php echo is_numeric( $all_miles ) ? $all_miles : ''; ?></td>
                <td><?php echo $pdlocations[ 'pick_up_date' ]; ?></td>
                <td class="<?php echo $load_status; ?>"><span><?php echo $status; ?></span></td>
                <td>
                    <div class="table-list-icons"><?php echo $instructions; ?></div>
                </td>
                <td><?php echo $source; ?></td>
                <td>
                    <div class="d-flex">
                        <button class="btn-bookmark js-btn-bookmark <?php echo $TMSUsers->is_bookmarked( $row[ 'id' ], isset( $flt ) ? $flt : false )
							? 'active' : ''; ?>"
                                data-id="<?php echo $row[ 'id' ]; ?>"
                                data-flt="<?php echo isset( $flt ) ? ( $flt ? '1' : '0' ) : '0'; ?>">
							<?php echo $helper->get_icon_bookmark(); ?>
                        </button>
						<?php if ( $show_control ): ?>
							<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown', [
								'id'       => $row[ 'id' ],
								'is_draft' => $is_draft,
								'flt'      => $flt,
							] ) ); ?>
						<?php endif; ?>
                    </div>
                </td>
            </tr> <?php endforeach; ?>

        </tbody>
    </table>
	<?php
	echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>

<?php else : ?>
    <p>No loads found.</p>
<?php endif; ?>

