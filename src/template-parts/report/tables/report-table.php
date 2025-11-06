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

$trigger_keys = [ 'dispatcher', 'my_search', 'load_status', 'source' ];

foreach ( $trigger_keys as $key ) {
	if ( isset( $_GET[ $key ] ) && $_GET[ $key ] !== '' ) {
		$hide_total = true;
		break;
	}
}

if ( ! empty( $results ) ) :

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
		foreach ( $results as $row ) :
			$meta            = get_field_value( $row, 'meta_data' );
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			
			if ( $date_booked_raw ) {
				$array_date[] = substr( $date_booked_raw, 0, 10 );
			}
		
		endforeach;
		$array_date     = array_unique( $array_date );
		$new_array_date = $TMSReports->get_profit_by_dates( $array_date, $office );
		
		$index                      = 0;
		foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$driver_with_macropoint = $TMSHelper->get_driver_tempate( $meta );
			$pdlocations            = $helper->get_locations_template( $row );
			$dispatcher_initials    = get_field_value( $meta, 'dispatcher_initials' );
			
			$dispatcher     = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials ) : '#030303';
			$dispatcher     = $dispatcher ?: [ 'full_name' => 'User not found', 'initials' => 'NF' ];
			
			$load_status = get_field_value( $meta, 'load_status' );
			$status      = esc_html( $helper->get_label_by_key( $load_status, 'statuses' ) );
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' );
			$booked_rate     = esc_html( '$' . $helper->format_currency( $booked_rate_raw ) );
			
			$driver_rate_raw = get_field_value( $meta, 'driver_rate' );
			$driver_rate     = esc_html( '$' . $helper->format_currency( $driver_rate_raw ) );
			
			$second_driver_rate_raw = get_field_value( $meta, 'second_driver_rate' );
			$second_driver_rate     = esc_html( '$' . $helper->format_currency( $second_driver_rate_raw ) );
			
			$all_miles = get_field_value( $meta, 'all_miles' );
			$miles     = $helper->calculate_price_per_mile( $booked_rate_raw, ( $second_driver_rate && $second_driver_rate_raw !== '0' )
				? $second_driver_rate_raw : $driver_rate_raw, $all_miles );
			
			$tbd          = get_field_value( $meta, 'tbd' );
			$profit_raw   = get_field_value( $meta, 'profit' );
			$profit_class = $profit_raw < 0 ? 'modified-price' : '';
			$profit       = esc_html( '$' . $helper->format_currency( $profit_raw ) );
			
			$instructions = $helper->get_label_by_key( get_field_value( $meta, 'instructions' ), 'instructions' );
			$source       = esc_html( $helper->get_label_by_key( get_field_value( $meta, 'source' ), 'sources' ) );
			
			$modify_class              = $helper->get_modify_class( $meta, 'modify_price' );
			$modify_driver_price_class = $helper->get_modify_class( $meta, 'modify_driver_price' );
			
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
					
				}
				
				$index          = 1;
				$show_separator = false;
				?>
                <tr>
                    <td colspan="14" class="separator-date">
						<?php echo $date_booked . ' ' . $profit_mod . ' ' . $average_mod; ?>
                    </td>
                </tr>
				<?php
			}
			?>

            <tr class="load-status-<?php echo $load_status; ?> <?php echo $tbd ? 'tbd' : ''; ?>">
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
                        <span class="text-small platform-<?php echo isset( $broker[ 'platform' ] )
							? $broker[ 'platform' ] : ""; ?>"
                              title="<?php echo isset( $broker[ 'platform' ] ) ? strtoupper( $broker[ 'platform' ] )
							      : ""; ?>">
                            <?php echo $reference_number; ?>
                        </span>
                    </div>
                </td>
                <td><?php echo $pdlocations[ 'pick_up_template' ]; ?></td>
                <td><?php echo $pdlocations[ 'delivery_template' ]; ?></td>
                <td><?php echo $driver_with_macropoint; ?></td>
                <td>
                    <span class="<?php echo $modify_class; ?>"><?php echo $booked_rate; ?></span>
					<?php if ( ! empty( $miles[ 'booked_rate_per_mile' ] ) ): ?>
                        <p class="text-small mb-0 mt-1"><?php echo '$' . $miles[ 'booked_rate_per_mile' ] . ' per mile'; ?></p>
					<?php endif; ?>
                </td>
                <td>
                    <span class="<?php echo $modify_driver_price_class; ?>"><?php echo $driver_rate; ?></span>
					<?php if ( $second_driver_rate !== '$0' && $second_driver_rate_raw ): ?>
                        <br><br>
                        <span class="<?php echo $modify_driver_price_class; ?>"><?php echo $second_driver_rate; ?></span>
					<?php endif; ?>
					
					<?php if ( ! empty( $miles[ 'driver_rate_per_mile' ] ) ): ?>
                        <p class="text-small mb-0 mt-1"><?php echo '$' . $miles[ 'driver_rate_per_mile' ] . ' per mile'; ?></p>
					<?php endif; ?>
                </td>
                <td><span class="<?php echo $profit_class; ?>">
					<?php if ( $load_status !== 'waiting-on-rc' ): echo $profit; endif; ?></span></td>
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

