<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );
$link_broker = get_field_value( $global_options, 'single_page_broker' );

$TMSUsers = new TMSUsers();
$TMSBroker = new TMSReportsCompany();
$helper = new TMSReportsHelper();
$logs = new TMSLogs();

$results       = get_field_value($args, 'results');
$total_pages   = get_field_value($args, 'total_pages');
$current_pages = get_field_value($args, 'current_pages');
$is_draft      = get_field_value($args, 'is_draft');
$page_type     =  get_field_value($args, 'page_type');
$archive     =  get_field_value($args, 'archive');

$current_user_id = get_current_user_id();

$my_team = $TMSUsers->check_group_access();
$all_statuses = $helper->get_statuses();


if ( ! empty( $results ) ) : ?>
    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">
                Dispatcher<br>
            </th>
            <th scope="col">Pick up location</th>
            <th scope="col">Delivery location</th>
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
   
			$attached_files = get_field_value( $meta, 'attached_files' );
			$array_id_files = $attached_files ? explode( ',', $attached_files ) : false;
			$files_count    = is_array( $array_id_files ) ? '(' . sizeof( $array_id_files ) . ')' : '';
			$files_state    = empty( $files_count ) ? 'disabled' : '';

			$delivery_raw = get_field_value( $meta, 'delivery_location' );
			$delivery     = $delivery_raw ? json_decode( $delivery_raw, ARRAY_A ) : [];
			
			$pick_up_raw = get_field_value( $meta, 'pick_up_location' );
			$pick_up     = $pick_up_raw ? json_decode( $pick_up_raw, ARRAY_A ) : [];
   
            
			$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
			$driver_phone = get_field_value( $meta, 'driver_phone' );

			$dispatcher          = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials      = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials )
				: '#030303';
			if ( ! $dispatcher ) {
				$dispatcher = array( 'full_name' => 'User not found', 'initials' => 'NF' );
			}

			$load_status  = get_field_value( $meta, 'load_status' );
			$status       = $load_status;
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
	
			$pick_up_date_raw = get_field_value( $row, 'pick_up_date' );
			$pick_up_date     = esc_html( date( 'm/d/Y', strtotime( $pick_up_date_raw ) ) );
            
            $delivery_date_raw = get_field_value( $row, 'delivery_date' );
			$delivery_date     = esc_html( date( 'm/d/Y', strtotime( $delivery_date_raw ) ) );
			
            
            $show_control = $TMSUsers->show_control_loads($my_team, $current_user_id, $dispatcher_initials, $is_draft);
			
			$id_customer              = get_field_value( $meta, 'customer_id' );
			$broker_info             = $TMSBroker->get_company_by_id($id_customer, ARRAY_A);
			
			$instructions_raw = get_field_value( $meta, 'instructions' );
			$instructions     = $helper->get_label_by_key( $instructions_raw, 'instructions' );
   
			$broker_name = '';
			$broker_mc = '';
			
   
			if (isset($broker_info[0]) && $broker_info[0]) {
				$broker_name = $broker_info[0]['company_name'];
				$broker_mc = $broker_info[0]['mc_number'];
			}
			
			
			if ( ! $broker_mc ) {
				$broker_mc = "N/A";
			}
			
			if (!$broker_name) {
				$broker_name = "N/A";
			}
			
			$disable_status = false;
			
			$proof_of_delivery   = get_field_value($meta, 'proof_of_delivery');
			if (!is_numeric($proof_of_delivery)) {
				$disable_status = true;
			}
            
            
            $tmpl = $logs->get_last_log_by_post($row[ 'id' ])
            
			?>

            <tr class="<?php echo 'status-tracking-'. $status; ?>">

                <td>
                    <div class="d-flex gap-1 align-items-center">
                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                              class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                              <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
                        </span>
                        <span>PL</span>
                    </div>
                    <span class="mt-1" class="text-small">
                        <?php echo $reference_number; ?>
                    </span>
                </td>
                
                <td>
					<?php if ( is_array( $pick_up ) ): ?>
						<?php foreach ( $pick_up as $val ):
							if (isset($val['date'])) {
								$date = esc_html( date( 'm/d/Y', strtotime( $val['date'] ) ) );
							} else {
								$date = '';
							}
                            ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                            <div class="w-100 d-flex flex-column align-items-start">
                                <p class="m-0" >
		                            <?php echo $val[ 'address' ]; ?>
                                </p>
                                <span class="text-small">
                                    <?php echo $date; ?>
                                    
                                    <?php
                                    if (isset($val['time_start'])):
	                                    $time_start = get_field_value($val, 'time_start');
	                                    $time_end = get_field_value($val, 'time_end');
	                                    $strict_time = get_field_value($val, 'strict_time');
	                                    
	                                    if ( $strict_time === "false" ) :
		                                    echo $time_start . ' - ' . $time_end;
	                                    else:
		                                    echo $time_start . ' - strict';
	                                    endif;
                                    endif; ?>
                                </span>
                            </div>
                            
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td>
					<?php if ( is_array( $delivery ) ): ?>
						<?php foreach ( $delivery as $val ):
							if (isset($val['date'])) {
								$date = esc_html( date( 'm/d/Y', strtotime( $val['date'] ) ) );
							} else {
								$date = '';
							}
							?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                            <div class="w-100 d-flex flex-column align-items-start">
                                <p class="m-0" >
		                            <?php echo $val[ 'address' ]; ?>
                                </p>
                                
                                <span class="text-small">
                                    <?php echo $date; ?>
	                                <?php if (isset($val['time_start'])):
	                                    $time_start = get_field_value($val, 'time_start');
	                                    $time_end = get_field_value($val, 'time_end');
	                                    $strict_time = get_field_value($val, 'strict_time');
                                     
	                                    if ( $strict_time === "false" ) :
		                                    echo $time_start . ' - ' . $time_end;
	                                    else:
		                                    echo $time_start . ' - strict';
	                                    endif;
                                    endif; ?>
            
                                </span>
                            </div>
						<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td>
                    <div class="w-100 d-flex flex-column align-items-start">
                        <p class="m-0">
                            <?php echo $unit_number_name; ?>
                        </p>
                        <?php if ($driver_phone): ?>
                            <span class="text-small"><?php echo $driver_phone; ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <?php if ($broker_name != 'N/A'): ?>
                            <a class="m-0" href="<?php echo $link_broker . '?broker_id='. $id_customer; ?>"><?php echo $broker_name; ?></a>
                        <?php else: ?>
                            <p class="m-0"><?php echo $broker_name; ?></p>
                        <?php endif; ?>
                        <span class="text-small">МС: <?php echo $broker_mc; ?></span>
                    </div>
                </td>

                <td class="">
                    <?php if (!$archive): ?>
                    <form class="js-save-status d-flex gap-1 align-items-center form-quick-tracking" >
                        <input type="hidden" name="id_load" value="<?php echo $row[ 'id' ]; ?>">
                        <?php if (is_array($all_statuses)) { ?>
                            <select name="status" class="js-trigger-disable-btn" >
                                <?php foreach ($all_statuses as $key => $st):?>
                                    <option <?php echo $key === $status ? 'selected' : ''; ?> <?php echo $disable_status && $key === 'delivered' ? ' disabled' : ''; ?> value="<?php echo $key; ?>"><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php } ?>
                        <button type="submit" disabled>
                            <?php echo $helper->get_icon_save(); ?>
                        </button>
                    </form>
                    <?php else:
                        echo $helper->get_label_by_key($status,'statuses');
                    endif; ?>
                </td>
                
                <td>
                    <div class="table-list-icons">
                    <?php echo $instructions; ?>
                    </div>
                </td>

                <td width="300">
                    <?php echo $tmpl; ?>
                </td>
                
             
				<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                    <td>
                        
                        <?php if ($show_control): ?>
                        
                        <div class="dropdown">
                            <button class="btn button-action" type="button" id="dropdownMenu2" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <?php echo $helper->get_dropdown_load_icon(); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
								<?php if ( $TMSUsers->check_user_role_access( array( 'billing' ), true ) ) : ?>
                                    <li><a href="<?php echo $add_new_load . '?post_id=' . $row[ 'id' ]; ?>"
                                           class="dropdown-item">View</a></li>
								<?php else: ?>
                                    <li><a href="<?php echo $add_new_load . '?post_id=' . $row[ 'id' ]; ?>"
                                           class="dropdown-item">Edit</a></li>
								<?php endif; ?>
		
		                        <?php if ( $TMSUsers->check_user_role_access( array( 'administrator' ), true ) ): ?>
                                <li>
                                    <button class="dropdown-item text-danger js-remove-load" data-id="<?php echo $row[ 'id' ]; ?>" type="button">Delete</button>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
        
                        <?php endif; ?>
                    </td>
				<?php endif; ?>
            </tr>
		
		<?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	
	
	echo esc_html( get_template_part( 'src/template-parts/report/report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>

<?php else : ?>
    <p>No reports found.</p>
<?php endif; ?>