<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );
$flt          = get_field_value( $args, 'flt' );
$user_id = get_current_user_id();
$curent_project        = get_field( 'current_select', 'user_' . $user_id );

$hide_time_controls = get_field_value( $args, 'hide_time_controls' );

$TMSUsers    = new TMSUsers();
$TMSBroker   = new TMSReportsCompany();
$helper      = new TMSReportsHelper();
$logs        = new TMSLogs();
$TMSReports  = new TMSReports();
$TMSDrivers  = new TMSDrivers();
$eta_manager = new TMSEta();

if ( $flt ) {
	$TMSReports = new TMSReportsFlt();
}

$TMSReportsTimer = new TMSReportsTimer();

$rating_access = $TMSUsers->check_user_role_access( array(
	'administrator',
	'dispatcher',
	'dispatcher-tl',
	'tracking',
	'tracking-tl',
	'morning_tracking',
	'nightshift_tracking',
	'expedite_manager',
), true );


$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );
$page_type     = get_field_value( $args, 'page_type' );
$archive       = get_field_value( $args, 'archive' );
$high_priority_count = get_field_value( $args, 'high_priority_count' );

$current_user_id = get_current_user_id();

$my_team      = $TMSUsers->check_group_access();
$all_statuses = $helper->get_statuses();

$blocked_update = $TMSUsers->check_user_role_access( array( 'driver_updates', 'expedite_manager', 'dispatcher-tl' ) );

$access_timer = $TMSUsers->check_user_role_access( array( 'administrator', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking' ), true );
$access_quick_comment = $TMSUsers->check_user_role_access( array( 'administrator', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking' ), true );

$fragment_only = ! empty( $args['fragment_only'] );
if ( ! empty( $results ) || $fragment_only ) :
	if ( ! $fragment_only ) {
		$tools = $TMSReports->get_stat_tools();
	}
	?>
	<?php if ( ! $fragment_only ) : ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
		
		
		<?php if ( $flt ): ?>
            <input type="hidden" name="flt" value="1">
		<?php endif; ?>
		
		<?php if ( is_array( $tools ) ): ?>
            <div class="d-flex gap-2 align-items-center">
				<?php
				$labels = array(
					'macropoint_count'   => 'Macropoint',
					'truckertools_count' => 'Truckers Tools',
				);
				foreach ( $tools as $key => $tool ):
					if ( isset( $labels[ $key ] ) ): ?>
                        <div class="d-flex gap-1 align-items-start flex-column">
                            <p class="m-0"><?php echo $labels[ $key ]; ?> : <span
                                        class="text-primary m-0 font-weight-bold"><?php echo $tool; ?></span></p>
                        </div>
					<?php endif;
				endforeach; ?>
            </div>
		<?php endif; ?>
    </div>

    <div class="w-100">
        <form class="js-save-all-tracking d-none mb-3">
            <input type="hidden" name="project" value="<?php echo $TMSReports->project; ?>">
            <button class="btn btn-primary" type="submit">Save all</button>
        </form>
    </div>

    <table class="table mb-5 w-100 js-table-tracking">
        <thead>
        <tr>
            <?php if ( $access_timer && !$archive ): ?>
            <th>Timer</th>
            <?php endif; ?>
            <th scope="col">
                LOAD NO
            </th>
            <th scope="col">ORIGIN</th>
            <th scope="col">DESTINATION</th>
            <th scope="col">Unit & name</th>
            <th scope="col">Client</th>
            <th scope="col">Load status</th>
            <th scope="col">Instructions</th>
            <th scope="col">Last log</th>
            <th scope="col"></th>
        </tr>
        </thead>
	<?php endif; ?>
	<?php if ( ! $fragment_only ) : ?><tbody class="js-tracking-tbody"><?php endif; ?>
		<?php
		$row_index = 0;
		if ( ! empty( $results ) ) :
		foreach ( $results as $row ) :
			$row_index++;
			$meta = get_field_value( $row, 'meta_data' );
			$main = get_field_value( $row, 'main' );
			
			$pdlocations = $helper->get_locations_template( $row, 'tracking', true, $TMSReports );


            $eta_data = $helper->get_eta_data( $row );

			$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
			$dispatcher          = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials      = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials )
				: '#030303';
			if ( ! $dispatcher ) {
				$dispatcher = array( 'full_name' => 'User not found', 'initials' => 'NF' );
			}
			
			$factoring_status_row = get_field_value( $meta, 'factoring_status' );
			$load_status          = get_field_value( $meta, 'load_status' );
			$status               = $load_status;
			
			$office_dispatcher = get_field_value( $meta, 'office_dispatcher' );
			$tbd               = get_field_value( $meta, 'tbd' );
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );

			// Primary driver for rating: priority third > second > first (same as report-table)
			$attached_driver          = get_field_value( $meta, 'attached_driver' );
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
			// Load is "rated" for everyone when the dispatcher who created the load has rated it; one query for both raters
			$dispatcher_name    = $dispatcher['full_name'] ?? '';
			$current_user_info  = $helper->get_user_full_name_by_id( $current_user_id );
			$current_user_name  = $current_user_info ? $current_user_info['full_name'] : '';
			$rated_by           = $TMSDrivers->get_raters_for_order_number( $reference_number, array( $dispatcher_name, $current_user_name ) );
			$dispatcher_rated   = in_array( $dispatcher_name, $rated_by, true );
			$current_user_rated = in_array( $current_user_name, $rated_by, true );
			// Tracking: can rate if load's dispatcher is in my group (or user is administrator)
			$can_rate_this_load = $TMSUsers->check_user_role_access( array( 'administrator' ), true )
				|| $TMSUsers->check_user_in_my_group( $my_team, (int) $dispatcher_initials );
			
			$high_priority = get_field_value( $meta, 'high_priority' );
			
			$driver_with_macropoint = $helper->get_driver_tempate( $meta );
			
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			
			$id_customer     = get_field_value( $meta, 'customer_id' );
			$template_broker = $TMSBroker->get_broker_and_link_by_id( $id_customer );
			
			$current_company = $TMSBroker->get_company_by_id( $id_customer );
			if ( $current_company ) {
				$current_company_name = $current_company[0]->company_name;
			} else {
				$current_company_name = '';
			}
			
			$instructions_raw = get_field_value( $meta, 'instructions' );
			$instructions     = $helper->get_label_by_key( $instructions_raw, 'instructions' );
			
			$disable_status = false;
			
			$proof_of_delivery = get_field_value( $meta, 'proof_of_delivery' );
			if ( ! is_numeric( $proof_of_delivery ) ) {
				$disable_status = true;
			}
			
			$tmpl = $logs->get_last_log_by_post( $row[ 'id' ], $flt ? 'reports_flt' : 'report' );
			
			$now_show = ( $factoring_status_row === 'paid' );
			
			
			?>

            <?php
            // Determine if there is an active timer for this load (used to show quick update button)
            $has_active_timer = $access_timer && ! $archive && $TMSReportsTimer->get_active_timer_for_load( $row['id'] );
            ?>

            <tr class="<?php echo 'status-tracking-' . $status; ?> <?php echo $tbd ? 'tbd' : ''; ?> <?php echo $high_priority ? 'hight-priority' : ''; ?>" data-load-id="<?php echo (int) $row['id']; ?>">

                <?php if ( $access_timer && !$archive ): ?>
                <td class="js-timer-status-cell" data-load-id="<?php echo (int) $row['id']; ?>">
                    <?php echo $TMSReportsTimer->get_timer_status( $row[ 'id' ] ); ?>
                </td>
                <?php endif; ?>

                <td>
                    <div class="d-flex gap-1 align-items-center">
                        <span data-bs-toggle="tooltip" data-bs-placement="top"
                              title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                              class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                              <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
                        </span>
						<?php if ( $office_dispatcher ): ?>
                            <span><?php echo strtoupper( $office_dispatcher ); ?></span>
						<?php endif; ?>
                    </div>
                    <div class="d-flex gap-1 align-items-center mt-1">
                        <?php echo $high_priority ? $helper->get_icon_high_priority() : ''; ?>
                        <span class="text-small <?php echo $high_priority ? 'fw-bold text-decoration-underline' : ''; ?>">
                            <?php echo $reference_number; ?>
                        </span>
                    </div>
                    <?php if ( ! empty( $current_company_name ) ): ?>
                        <div class="d-flex flex-column">
                            <span style="font-size: 10px;"><?php echo $current_company_name; ?></span>
                        </div>
                    <?php endif; ?>
                </td>

                <td>
                    <div class="d-flex gap-1 align-items-start">
                    <div class="w-100">
					<?php echo $pdlocations[ 'pick_up_template' ]; ?>
                    </div>
                    <?php 

                    if ($load_status !== 'loaded-enroute' && $load_status !== 'at-del' && $load_status !== 'at-pu' && $load_status !== 'waiting-on-rc' && $load_status !== 'delivered' && $load_status !== 'tonu' && $load_status !== 'cancelled'):

                    $helper = new TMSReportsHelper();
                    $eta_data = $helper->get_eta_data($row);
                    $pickup_data = $helper->get_eta_display_data($eta_data, 'pick_up');
                    // Get ETA record for display (for all users)
                    $pickup_eta_record = $eta_manager->get_eta_record_for_display($row['id'], 'pickup', $flt, $TMSReports->project);
                    $pickup_button_class = $pickup_eta_record['exists'] ? 'btn-success' : 'btn-outline-primary';
                    
                    // Use ETA record data if exists, otherwise use location data
                    $pickup_date = $pickup_eta_record['exists'] ? $pickup_eta_record['date'] : $pickup_data['date'];
                    $pickup_time = $pickup_eta_record['exists'] ? $pickup_eta_record['time'] : $pickup_data['time'];
            
                    // Use timezone from ETA record if exists (it's already correct, saved with coordinates)
                    // Only fallback to location timezone or state-based calculation if ETA record doesn't have timezone
                
                    // if (current_user_can('administrator')) {
                    //     var_dump($pickup_data);
                    // }
                
                    if ($pickup_eta_record['exists'] && !empty($pickup_eta_record['timezone'])) {
                        // Use timezone from DB - it's already correct (saved with coordinates)
                        $pickup_timezone = $pickup_eta_record['timezone'];
                    } elseif (!empty($pickup_data['timezone'])) {
                        // Use timezone from location data
                        $pickup_timezone = $pickup_data['timezone'];
                    } elseif (!empty($pickup_data['state'])) {
                        // Fallback to state-based calculation only if no timezone available
                        $pickup_timezone = $helper->get_timezone_by_state($pickup_data['state'], $pickup_date);
                    } else {
                        $pickup_timezone = '';
                    }

                    // if (current_user_can('administrator')) {
                    //     var_dump($pickup_timezone);
                    // }

                    $date_shipper_tmp = empty($pickup_data['shipper_eta_date']) ? $pickup_data['date'] : $pickup_data['shipper_eta_date'];
                    $time_shipper_tmp = empty($pickup_data['shipper_eta_time']) ? $pickup_data['time'] : $pickup_data['shipper_eta_time'];

                    ?>
                    <div class="d-flex flex-column align-items-start gap-1" style="min-width: 52px;">
                        <button class="btn btn-sm <?php echo $pickup_button_class; ?> js-open-popup-activator" 
                                data-href="#popup_eta_pick_up"
                                data-load-id="<?php echo $row['id']; ?>"
                                data-current-date="<?php echo esc_attr($pickup_date); ?>"
                                data-current-time="<?php echo esc_attr($pickup_time); ?>"
                                data-state="<?php echo esc_attr($pickup_data['state']); ?>"
                                data-timezone="<?php echo esc_attr($pickup_timezone); ?>"
                                data-eta-type="pickup"
                                data-is-flt="<?php echo $flt ? '1' : '0'; ?>"
                                data-shipper-eta-date="<?php echo esc_attr($date_shipper_tmp); ?>"
                                data-shipper-eta-time="<?php echo esc_attr($time_shipper_tmp); ?>"
                                title="Pickup ETA - <?php echo esc_attr($pickup_timezone); ?>">
                            ETA
                        </button>
                        <?php if ($pickup_eta_record['exists']): ?>
                            <div class="js-eta-timer" 
                                 data-load-id="<?php echo $row['id']; ?>"
                                 data-eta-type="pickup"
                                 data-eta-datetime="<?php echo esc_attr($pickup_eta_record['eta_datetime']); ?>"
                                 data-timezone="<?php echo esc_attr($pickup_timezone); ?>"
                                 data-is-flt="<?php echo $flt ? '1' : '0'; ?>"
                                 data-load-status="<?php echo esc_attr($load_status); ?>"
                                 style="font-size: 11px; line-height: 1.2;">
                                <span class="js-eta-timer-text">--:--</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="d-flex gap-1 align-items-start">
                    <div class="w-100">
					<?php echo $pdlocations[ 'delivery_template' ]; ?>
                    </div>
                    <?php 

                if ($load_status !== 'delivered' && $load_status !== 'waiting-on-rc' && $load_status !== 'tonu' && $load_status !== 'cancelled'):
                    $delivery_data = $helper->get_eta_display_data($eta_data, 'delivery');
                    
                    // Get ETA record for display (for all users)
                    $delivery_eta_record = $eta_manager->get_eta_record_for_display($row['id'], 'delivery', $flt, $TMSReports->project);
                    $delivery_button_class = $delivery_eta_record['exists'] ? 'btn-success' : 'btn-outline-primary';
                    
                    // Use ETA record data if exists, otherwise use location data
                    $delivery_date = $delivery_eta_record['exists'] ? $delivery_eta_record['date'] : $delivery_data['date'];
                    $delivery_time = $delivery_eta_record['exists'] ? $delivery_eta_record['time'] : $delivery_data['time'];
                    
                    // Use timezone from ETA record if exists (it's already correct, saved with coordinates)
                    // Only fallback to location timezone or state-based calculation if ETA record doesn't have timezone
                    if ($delivery_eta_record['exists'] && !empty($delivery_eta_record['timezone'])) {
                        // Use timezone from DB - it's already correct (saved with coordinates)
                        $delivery_timezone = $delivery_eta_record['timezone'];
                    } elseif (!empty($delivery_data['timezone'])) {
                        // Use timezone from location data
                        $delivery_timezone = $delivery_data['timezone'];
                    } elseif (!empty($delivery_data['state'])) {
                        // Fallback to state-based calculation only if no timezone available
                        $delivery_timezone = $helper->get_timezone_by_state($delivery_data['state'], $delivery_date);
                    } else {
                        $delivery_timezone = '';
                    }

                    $date_shipper_tmp = empty($delivery_data['shipper_eta_date']) ? $delivery_data['date'] : $delivery_data['shipper_eta_date'];
                    $time_shipper_tmp = empty($delivery_data['shipper_eta_time']) ? $delivery_data['time'] : $delivery_data['shipper_eta_time'];

                    ?>
                    <div class="d-flex flex-column align-items-start gap-1" style="min-width: 52px;">
                        <button class="btn btn-sm <?php echo $delivery_button_class; ?> js-open-popup-activator" 
                                data-href="#popup_eta_delivery"
                                data-load-id="<?php echo $row['id']; ?>"
                                data-current-date="<?php echo esc_attr($delivery_date); ?>"
                                data-current-time="<?php echo esc_attr($delivery_time); ?>"
                                data-state="<?php echo esc_attr($delivery_data['state']); ?>"
                                data-timezone="<?php echo esc_attr($delivery_timezone); ?>"
                                data-eta-type="delivery"
                                data-is-flt="<?php echo $flt ? '1' : '0'; ?>"
                                data-shipper-eta-date="<?php echo esc_attr($date_shipper_tmp); ?>"
                                data-shipper-eta-time="<?php echo esc_attr($time_shipper_tmp); ?>"
                                title="Delivery ETA - <?php echo esc_attr($delivery_timezone); ?>">
                            ETA
                        </button>
                        <?php if ($delivery_eta_record['exists']): ?>
                            <div class="js-eta-timer" 
                                 data-load-id="<?php echo $row['id']; ?>"
                                 data-eta-type="delivery"
                                 data-eta-datetime="<?php echo esc_attr($delivery_eta_record['eta_datetime']); ?>"
                                 data-timezone="<?php echo esc_attr($delivery_timezone); ?>"
                                 data-is-flt="<?php echo $flt ? '1' : '0'; ?>"
                                 data-load-status="<?php echo esc_attr($load_status); ?>"
                                 style="font-size: 11px; line-height: 1.2;">
                                <span class="js-eta-timer-text">--:--</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php endif; ?>
                    </div>
                </td>
                <td>
					<div class="d-flex gap-1 align-items-start">
						<?php echo $driver_with_macropoint; ?>
						<?php if ( $load_status === 'delivered' || $load_status === 'cancelled' || $load_status === 'tonu' ) : ?>
							<?php if ( $dispatcher_rated ) : ?>
								<?php echo $helper->get_icon_rating(); ?>
							<?php elseif ( $current_user_rated ) : ?>
								<?php echo $helper->get_icon_rating(); ?>
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
					<?php echo $template_broker; ?>
                </td>

                <td class="">
					<?php if ( ! $archive && $show_control && ! $now_show && $blocked_update ): ?>
                        <form class="js-save-status d-flex gap-1 align-items-center form-quick-tracking">
                            <input type="hidden" name="id_load" value="<?php echo $row[ 'id' ]; ?>">
                            <input type="hidden" name="project" value="<?php echo $TMSReports->project; ?>">
							<?php if ( is_array( $all_statuses ) ) { ?>
                                <select name="status" class="js-trigger-disable-btn">
									<?php foreach ( $all_statuses as $key => $st ): ?>
                                        <option <?php echo $key === $status ? 'selected'
											: ''; ?> <?php echo $disable_status && $key === 'delivered' ? ' disabled'
											: ''; ?> value="<?php echo $key; ?>"><?php echo $st; ?></option>
									<?php endforeach; ?>
                                </select>
							<?php } ?>
                            <button type="submit" disabled>
								<?php echo $helper->get_icon_save(); ?>
                            </button>
                        </form>
					<?php else:
						echo $helper->get_label_by_key( $status, 'statuses' );
					endif; ?>
                </td>

                <td>
                    <div class="table-list-icons">
						<?php echo $instructions; ?>
                    </div>
                </td>

                <td width="300" style="max-width: 300px;">
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-start gap-1 w-100">
                            <div class="w-100 js-log-wrapper" style="max-width: calc(100% - 38px);">
						    <?php echo $tmpl; ?>
                            </div>
                            <?php if ( $access_quick_comment ): ?>
                            <button class="btn btn-sm btn-outline-success js-open-log-modal" 
                                    style="width: 30px; height: 30px; padding: 0;"
                                    data-post-id="<?php echo $row['id']; ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#addLogModal">
                                <b style="font-size: 18px;">+</b>
                            </button>
                            <?php endif; ?>
                        </div>
						<?php 
						?>
						<div class="w-100 js-pinned-wrapper d-flex flex-column gap-1">
							<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'common/pinned', 'message', array(
								'id'        => $row[ 'id' ],
								'meta'      => $meta,
								'hide_ctrl' => true,
							) ) ); ?>
						</div>
						<?php 
 						?>

                    </div>
                </td>
				
				
				<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                    <td>
                        <div class="d-flex justify-content-end">

                            <?php if ( $access_timer && !$archive ): ?>
                                <button class="btn btn-sm d-flex align-items-center justify-content-center js-timer-tracking"
                                        data-id="<?php echo $row[ 'id' ]; ?>"
                                        data-tracking="<?php echo get_current_user_id(); ?>"
                                        data-flt="<?php echo isset( $flt ) ? ( $flt ? '1' : '0' ) : '0'; ?>"
                                        data-project="<?php echo esc_attr( $curent_project ); ?>"
                                        style="width: 28px; height: 28px; padding: 0;">
									<?php echo $TMSReports->get_icon_hold(); ?>
                                </button>
								<?php if ( $has_active_timer ) : ?>
									<button class="btn btn-sm d-flex align-items-center justify-content-center js-timer-quick-update use-stroke"
											data-id="<?php echo $row[ 'id' ]; ?>"
											data-flt="<?php echo isset( $flt ) ? ( $flt ? '1' : '0' ) : '0'; ?>"
											data-project="<?php echo esc_attr( $curent_project ); ?>"
											title="<?php esc_attr_e( 'Update timer without opening modal', 'wp-rock' ); ?>"
											style="width: 28px; height: 28px; padding: 0; margin-left: 4px;">
										<?php echo $helper->get_icon_update_timer(); ?>
									</button>
								<?php endif; ?>
							<?php endif; ?>

                            <button class="btn-bookmark js-btn-bookmark <?php echo $TMSUsers->is_bookmarked( $row[ 'id' ], isset( $flt )
								? $flt : false ) ? 'active' : ''; ?>" data-id="<?php echo $row[ 'id' ]; ?>"
                                    data-flt="<?php echo isset( $flt ) ? ( $flt ? '1' : '0' ) : '0'; ?>">
								<?php echo $helper->get_icon_bookmark(); ?>
                            </button>
							
							<?php if ( $show_control ):
								echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown', array(
									'id'       => $row[ 'id' ],
									'is_draft' => $is_draft,
									'flt'      => $flt,
								) ) );
							endif; ?>
                        </div>
                    </td>
				<?php endif; ?>
            </tr>
		<?php endforeach; endif; ?>

	<?php if ( ! $fragment_only ) : ?></tbody>
    </table>

    <div class="d-flex justify-content-between">
        <div class="w-100">
            <form class="js-save-all-tracking d-none mb-3">
                <input type="hidden" name="project" value="<?php echo $TMSReports->project; ?>">
                <button class="btn btn-primary" type="submit">Save all</button>
            </form>
        </div>
		
		<?php
		
		
		echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
			'total_pages'  => $total_pages,
			'current_page' => $current_pages,
		) ) );
		?>
    </div>
	<?php endif; ?>

<?php else : ?>
	<?php if ( ! $fragment_only ) : ?>
    <p>No reports found.</p>
	<?php endif; ?>
<?php endif; ?>

<?php if ( ! $fragment_only ) : ?>
<!-- Timer Control Modal -->
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/timer', 'control-modal' ) ); ?>

<!-- Add Log Message Modal -->
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/add-log', 'modal', $args ) ); ?>

<!-- ETA Popups -->
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/eta', 'pickup' ) ); ?>
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/eta', 'delivery' ) ); ?>

<!-- Driver Statistics popup (opened from driver name in table) -->
<div class="modal fade" id="js-driver-stats-modal" tabindex="-1" aria-labelledby="js-driver-stats-modal-label" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="js-driver-stats-modal-label"><?php esc_html_e( 'Driver Statistics', 'wp-rock' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'wp-rock' ); ?>"></button>
			</div>
			<div class="modal-body" id="js-driver-stats-modal-body">
				<div class="text-center py-4 text-muted"><?php esc_html_e( 'Click a driver name to load statistics.', 'wp-rock' ); ?></div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>