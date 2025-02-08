<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );

$TMSReports = new TMSReports();
$TMSUsers   = new TMSUsers();
$TMSShipper = new TMSReportsShipper();
$TMSHelper  = new TMSReportsHelper();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );
$is_ar_problev = get_field_value( $args, 'ar_problem' );
$profit_mod    = false;
$page_type = get_field_value( $args, 'page_type' );

$current_user_id = get_current_user_id();

$billing_info = $TMSUsers->check_user_role_access( array(
	'administrator',
	'billing',
	'accounting'
), true );

$hide_billing_and_shipping = $TMSUsers->check_user_role_access( array( 'billing', 'accounting' ), true );

$my_team = $TMSUsers->check_group_access();

$helper = $TMSUsers;

if ( ! empty( $results ) ) : ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Date booked</th>
            <th scope="col" title="dispatcher">Disp.</th>
            <th scope="col">Pick up location</th>
            <th scope="col">Delivery location</th>
            <th scope="col">Unit & name</th>
            <th scope="col">Booked rate</th>
            <th scope="col">Driver rate</th>
            <th scope="col">Profit</th>
            <th scope="col">Miles</th>
            <th scope="col">Pick Up Date</th>
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
		
		$array_date = array();
		$previous_date  = null;
	    foreach ( $results as $row ) :
		    $meta = get_field_value( $row, 'meta_data' );
		    $date_booked_raw = get_field_value( $row, 'date_booked' );
            
            if ($date_booked_raw) {
                $array_date[] = substr($date_booked_raw, 0, 10);
            }
		   
        endforeach;
            $array_date = array_unique($array_date);
            $new_array_date = $TMSReports->get_profit_by_dates($array_date);
            
        $index = 0;
        foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			
			$delivery_raw = get_field_value( $meta, 'delivery_location' );
			$delivery     = $delivery_raw ? json_decode( $delivery_raw, ARRAY_A ) : [];
			
			$pick_up_raw = get_field_value( $meta, 'pick_up_location' );
			$pick_up     = $pick_up_raw ? json_decode( $pick_up_raw, ARRAY_A ) : [];
			
			$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
			
			$dispatcher     = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials ) : '#030303';
			if ( ! $dispatcher ) {
				$dispatcher = array( 'full_name' => 'User not found', 'initials' => 'NF' );
			}
			
			$load_status  = get_field_value( $meta, 'load_status' );
			$status_label = $helper->get_label_by_key( $load_status, 'statuses' );
			$status       = esc_html( $status_label );
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
            
            $driver_with_macropoint = $TMSHelper->get_driver_tempate($meta);
	        
	        $booked_rate_raw = get_field_value($meta, 'booked_rate');
	        $booked_rate = esc_html('$' . $helper->format_currency($booked_rate_raw));
	        
	        $driver_rate_raw = get_field_value($meta, 'driver_rate');
         
	        $driver_rate = esc_html('$' . $helper->format_currency($driver_rate_raw));
	        
	        $all_miles = get_field_value($meta, 'all_miles');
            
            $miles = $helper->calculate_price_per_mile( $booked_rate_raw, $driver_rate_raw, $all_miles );

	        $profit_raw = get_field_value($meta, 'profit');
	        $profit_class = $profit_raw < 0 ? 'modified-price' : '';
	        $profit = esc_html('$' . $helper->format_currency($profit_raw));
	        
	        $pick_up_date_raw = get_field_value( $row, 'pick_up_date' );
			$pick_up_date     = esc_html( date( 'm/d/Y', strtotime( $pick_up_date_raw ) ) );
			
			$delivery_date_raw = get_field_value( $row, 'delivery_date' );
			$delivery_date     = esc_html( date( 'm/d/Y', strtotime( $delivery_date_raw ) ) );
			
			$instructions_raw = get_field_value( $meta, 'instructions' );
			$instructions     = $helper->get_label_by_key( $instructions_raw, 'instructions' );
			$source_raw = get_field_value( $meta, 'source' );
			$source     = esc_html( $helper->get_label_by_key( $source_raw, 'sources' ) );
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			$status_class    = $load_status;
			
			$modify_booked_price       = get_field_value( $meta, 'modify_price' );
			$modify_booked_price_class = '';
	        
	        
	        if ( $modify_booked_price === '1' ) {
				$modify_booked_price_class = 'modified-price';
			}
			
			$bank_status       = 'Approved';
			$driver_pay_status = 'Processing';
            
            
            $show_separator = false;
            
            if ($previous_date !== $date_booked && !is_null($previous_date)) {
	            $show_separator = true;
            }
            
            $previous_date = $date_booked;
			?>
        
            <?php if($show_separator || $index === 0):
                $date_search = substr($date_booked_raw, 0, 10);
                if ($date_booked_raw && isset($new_array_date) && $date_search) {
	                $profit_mod = esc_html('$' . $helper->format_currency($new_array_date[$date_search]));
                    $profit_mod = '<span style="text-transform: capitalize">Profit: <b>'.$profit_mod.'</b></span>';
                }
                
                if ($profit_mod) {}
                
                $index = 1;
            
            ?>
                <tr><td colspan="14" class="separator-date"><?php echo $date_booked; echo ' '. $profit_mod; ?> </td></tr>
            <?php
            endif; ?>

            <tr class="load-status-<?php echo $load_status; ?>">
                <td><label class="h-100 cursor-pointer"
                               for="load-<?php echo $row[ 'id' ]; ?>"><?php echo $date_booked; ?></label></td>
                <td>
                    <div class="d-flex gap-1 flex-row align-items-center">
                        <p class="m-0">
                              <span data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                                    class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                                  <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
                              </span>
                        </p>
                        <span class="text-small"><?php echo $reference_number; ?></span>
                    </div>
                </td>

                <td>
					<?php if ( is_array( $pick_up ) ): ?>
						<?php foreach ( $pick_up as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="top"
                                   title="<?php echo $val[ 'address' ]; ?>">
                                   
									<?php
									echo $val[ 'short_address' ];
									
									$detailed_address = $TMSShipper->get_shipper_by_id( $val[ 'address_id' ] );
									if ( is_array( $detailed_address ) && !empty($detailed_address) ) {
										echo ' ' . $detailed_address[ 0 ]->zip_code;
									} else {
										echo '<br/><span class="text-danger">This shipper has been deleted</span>';
									}
									?>


                                </p>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td>
					<?php if ( is_array( $delivery ) ): ?>
						<?php foreach ( $delivery as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="top"
                                   title="<?php echo $val[ 'address' ]; ?>">
                                   
	                                <?php
	                                echo $val[ 'short_address' ];
	                                
	                                $detailed_address = $TMSShipper->get_shipper_by_id( $val[ 'address_id' ] );
	                                if ( is_array( $detailed_address ) && !empty($detailed_address) ) {
		                                echo ' ' . $detailed_address[ 0 ]->zip_code;
	                                } else {
		                                echo '<br/><span class="text-danger">This shipper has been deleted</span>';
	                                }
									?>
                                </p>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
		
                
                <td>
                    <?php echo $driver_with_macropoint; ?>
                </td>
                
                <td>
                    <span class="<?php echo $modify_booked_price_class; ?>"><?php echo $booked_rate; ?></span>
                    <?php if ($miles['booked_rate_per_mile'] != 0 && $miles['booked_rate_per_mile'] != '0'): ?>
                    <p class="text-small mb-0 mt-1"><?php echo '$'. $miles['booked_rate_per_mile'] . ' per mile' ?></p>
                    <?php endif; ?>
                </td>
        
                <td>
                    <span><?php echo $driver_rate; ?></span>
	                <?php if ($miles['driver_rate_per_mile'] != 0 && $miles['driver_rate_per_mile'] != '0'): ?>
                        <p class="text-small mb-0 mt-1"><?php echo '$'. $miles['driver_rate_per_mile'] . ' per mile' ?></p>
	                <?php endif; ?>
                </td>
                    
                <td><span class="<?php echo $profit_class; ?>"><?php echo $profit; ?></span></td>
                <td><?php if ($all_miles && is_numeric($all_miles)) { echo $all_miles ;} ?></td>
                <td><?php echo $pick_up_date; ?></td>
            
                <td class="<?php echo $status_class; ?>"><span><?php echo $status; ?></span></td>
                
                <td>
                    <div class="table-list-icons"><?php echo $instructions; ?></div>
                </td>
                
                <td><?php echo $source; ?></td>
				
				<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                    <td>
                        <div class="d-flex">
						    <button class="btn-bookmark js-btn-bookmark <?php echo $TMSUsers->is_bookmarked($row['id']) ? 'active' : ''; ?>" data-id="<?php echo $row[ 'id' ]; ?>">
                                <?php echo $helper->get_icon_bookmark(); ?>
                            </button>
						<?php if ( $show_control ): ?>

                            <div class="dropdown">
                                <button class="btn button-action" type="button" id="dropdownMenu2"
                                        data-bs-toggle="dropdown"
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
									
									<?php if ( $TMSUsers->check_user_role_access( array( 'administrator' ), true ) || $is_draft ): ?>
                                        <li>
                                            <button class="dropdown-item text-danger js-remove-load"
                                                    data-id="<?php echo $row[ 'id' ]; ?>" type="button">Delete
                                            </button>
                                        </li>
									<?php endif; ?>
                                </ul>
                            </div>
						
						<?php endif; ?>
                        </div>
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
    <p>No loads found.</p>
<?php endif; ?>