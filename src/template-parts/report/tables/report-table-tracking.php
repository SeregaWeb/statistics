<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );
$flt          = get_field_value( $args, 'flt' );
$user_id = get_current_user_id();
$curent_project        = get_field( 'current_select', 'user_' . $user_id );

$hide_time_controls = get_field_value( $args, 'hide_time_controls' );

$TMSUsers   = new TMSUsers();
$TMSBroker  = new TMSReportsCompany();
$helper     = new TMSReportsHelper();
$logs       = new TMSLogs();
$TMSReports = new TMSReports();
$eta_manager = new TMSEta();

if ( $flt ) {
	$TMSReports = new TMSReportsFlt();
}


$TMSReportsTimer = new TMSReportsTimer();


$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );
$page_type     = get_field_value( $args, 'page_type' );
$archive       = get_field_value( $args, 'archive' );

$current_user_id = get_current_user_id();

$my_team      = $TMSUsers->check_group_access();
$all_statuses = $helper->get_statuses();

$blocked_update = $TMSUsers->check_user_role_access( array( 'driver_updates', 'expedite_manager', 'dispatcher-tl' ) );

$access_timer = $TMSUsers->check_user_role_access( array( 'administrator', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking' ), true );
$access_quick_comment = $TMSUsers->check_user_role_access( array( 'administrator', 'tracking-tl', 'tracking', 'morning_tracking', 'nightshift_tracking' ), true );

if ( ! empty( $results ) ) :
	$tools = $TMSReports->get_stat_tools();
	
	?>
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
        <tbody>
		<?php foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$main = get_field_value( $row, 'main' );
			
			$pdlocations = $helper->get_locations_template( $row, 'tracking' );

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
			
			$driver_with_macropoint = $helper->get_driver_tempate( $meta );
			
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			
			$id_customer     = get_field_value( $meta, 'customer_id' );
			$template_broker = $TMSBroker->get_broker_and_link_by_id( $id_customer );
			
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

            <tr class="<?php echo 'status-tracking-' . $status; ?> <?php echo $tbd ? 'tbd' : ''; ?>">

                <?php if ( $access_timer && !$archive ): ?>
                <td>
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
                    <span class="mt-1" class="text-small">
                        <?php echo $reference_number; ?>
                    </span>
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
                    
                    // Check if ETA record exists
                    $pickup_eta_exists = $eta_manager->eta_record_exists($row['id'], 'pickup', $flt);
                    $pickup_button_class = $pickup_eta_exists ? 'btn-success' : 'btn-outline-primary';
                    ?>
                    <button class="btn btn-sm <?php echo $pickup_button_class; ?> js-open-popup-activator" 
                            data-href="#popup_eta_pick_up"
                            data-load-id="<?php echo $row['id']; ?>"
                            data-current-date="<?php echo esc_attr($pickup_data['date']); ?>"
                            data-current-time="<?php echo esc_attr($pickup_data['time']); ?>"
                            data-state="<?php echo esc_attr($pickup_data['state']); ?>"
                            data-timezone="<?php echo esc_attr($pickup_data['timezone']); ?>"
                            data-eta-type="pickup"
                            data-is-flt="<?php echo $flt ? '1' : '0'; ?>"
                            title="Pickup ETA - <?php echo esc_attr($pickup_data['timezone']); ?>">
                        ETA
                    </button>

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
                    
                    // Check if ETA record exists
                    $delivery_eta_exists = $eta_manager->eta_record_exists($row['id'], 'delivery', $flt);
                    $delivery_button_class = $delivery_eta_exists ? 'btn-success' : 'btn-outline-primary';
                    ?>
                    <button class="btn btn-sm <?php echo $delivery_button_class; ?> js-open-popup-activator" 
                            data-href="#popup_eta_delivery"
                            data-load-id="<?php echo $row['id']; ?>"
                            data-current-date="<?php echo esc_attr($delivery_data['date']); ?>"
                            data-current-time="<?php echo esc_attr($delivery_data['time']); ?>"
                            data-state="<?php echo esc_attr($delivery_data['state']); ?>"
                            data-timezone="<?php echo esc_attr($delivery_data['timezone']); ?>"
                            data-eta-type="delivery"
                            data-is-flt="<?php echo $flt ? '1' : '0'; ?>"
                            title="Delivery ETA - <?php echo esc_attr($delivery_data['timezone']); ?>">
                        ETA
                    </button>

                    <?php endif; ?>
                    </div>
                </td>
                <td>
					<?php echo $driver_with_macropoint; ?>
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

                <td width="300">
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-start gap-1 w-100">
                            <div class="w-100 js-log-wrapper">
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
						<div class="w-100 js-pinned-wrapper">
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
                        <div class="d-flex">

                            <?php if ( $access_timer && !$archive ): ?>
                                <button class="btn btn-sm d-flex align-items-center justify-content-center js-timer-tracking"
                                        data-id="<?php echo $row[ 'id' ]; ?>"
                                        data-tracking="<?php echo get_current_user_id(); ?>"
                                        data-flt="<?php echo isset( $flt ) ? ( $flt ? '1' : '0' ) : '0'; ?>"
                                        data-project="<?php echo esc_attr( $curent_project ); ?>"
                                        style="width: 28px; height: 28px; padding: 0;">
									<?php echo $TMSReports->get_icon_hold(); ?>
                                </button>
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
		
		<?php endforeach; ?>

        </tbody>
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

<?php else : ?>
    <p>No reports found.</p>
<?php endif; ?>

<!-- Timer Control Modal -->
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/timer', 'control-modal' ) ); ?>

<!-- Add Log Message Modal -->
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/add-log', 'modal', $args ) ); ?>

<!-- ETA Popups -->
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/eta', 'pickup' ) ); ?>
<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/eta', 'delivery' ) ); ?>