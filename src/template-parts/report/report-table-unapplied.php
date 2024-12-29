<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );
$link_broker = get_field_value($global_options, 'single_page_broker');

$TMSUsers   = new TMSUsers();
$TMSShipper = new TMSReportsShipper();
$TMSBroker = new TMSReportsCompany();
$helper = new TMSReportsHelper();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );
$is_ar_problev = get_field_value( $args, 'ar_problem' );

$page_type = get_field_value( $args, 'page_type' );

$current_user_id = get_current_user_id();

$billing_info              = $TMSUsers->check_user_role_access( array(
	'administrator',
	'billing',
	'accounting'
), true );
$hide_billing_and_shipping = $TMSUsers->check_user_role_access( array( 'billing', 'accounting' ), true );

$my_team = $TMSUsers->check_group_access();



if ( ! empty( $results ) ) :?>


    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th>Date booked</th>
            <th scope="col" title="dispatcher">Disp.</th>
            <th scope="col">Broker</th>
            <th scope="col">Factoring status </th>
            
            <th scope="col">Pick up location</th>
            <th scope="col">Delivery location</th>
            <th scope="col">Booked rate </th>
            <th scope="col">Load status</th>
            
            <th scope="col">QP option</th>
            <th scope="col">QP percent</th>
            <th scope="col">Delivery Date</th>
            <th scope="col">Days to pay</th>
            <th scope="col">Days since invoiced</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results as $row ) :
   
			$meta = get_field_value( $row, 'meta_data' );
        
            $days_to_pay_value = get_field_value( $row, 'days_to_pay_value' );
            $quick_pay_option_value = get_field_value( $row, 'quick_pay_option_value' );
            $quick_pay_percent_value = get_field_value( $row, 'quick_pay_percent_value' );
            $factoring_broker_value = get_field_value( $row, 'factoring_broker_value' );
            
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
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' );
			$booked_rate     = esc_html( '$' . str_replace( '.00', '', $booked_rate_raw ) );
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$delivery_date_raw = get_field_value( $row, 'delivery_date' );
			$delivery_date     = esc_html( date( 'm/d/Y', strtotime( $delivery_date_raw ) ) );
            
            $factoring_status = $factoring_broker_value ? $factoring_broker_value : 'N/A';
            $load_status      = get_field_value( $meta, 'load_status' );
            
			
			$load_problem_raw = get_field_value( $row, 'load_problem' );
            
            
            if ($load_problem_raw == '0000-00-00 00:00:00') {
	            $days_passed = 'N/A';
            } else {
                $date_problem     = new DateTime( $load_problem_raw );
                $current_date     = new DateTime();
                $interval         = $date_problem->diff( $current_date );
                $days_passed      = $interval->days;
            }
            
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			
			$ar_status = get_field_value($meta, 'ar_status');
			$modify_booked_price       = get_field_value( $meta, 'modify_price' );
			$modify_booked_price_class = '';
			
			if ( $modify_booked_price === '1' ) {
				$modify_booked_price_class = 'modified-price';
			}
			
			
			$id_customer              = get_field_value( $meta, 'customer_id' );
            $broker_info             = $TMSBroker->get_company_by_id($id_customer);
			
			$broker_name = '';
			$broker_mc = '';
            
            if (isset($broker_info[0])) {
	            $broker_name = $broker_info[0]->company_name;
	            $broker_mc = $broker_info[0]->mc_number;
            }
            
            
            if ( ! $broker_mc ) {
	            $broker_mc = "N/A";
            }
            
            if (!$broker_name) {
			    $broker_name = "N/A";
            }
			?>

            <tr class="">
                <td>
                    <?php echo $date_booked; ?>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-column">
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
                    <div class="d-flex flex-column">
			            <?php if ($broker_name != 'N/A'): ?>
                            <a class="m-0" href="<?php echo $link_broker . '?broker_id='. $id_customer; ?>"><?php echo $broker_name; ?></a>
			            <?php else: ?>
                            <p class="m-0"><?php echo $broker_name; ?></p>
			            <?php endif; ?>
                        <span class="text-small"><?php echo $broker_mc; ?></span>
                    </div>
                </td>
                <td><?php echo $helper->get_label_by_key($factoring_status, 'factoring_status'); ?></td>
                
                <td>
					<?php if ( is_array( $pick_up ) ): ?>
						<?php foreach ( $pick_up as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="top"
                                   title="<?php echo $val[ 'address' ]; ?>">
									<?php echo $val[ 'short_address' ];
									
									$detailed_address = $TMSShipper->get_shipper_by_id( $val[ 'address_id' ] );
									if ( is_array( $detailed_address ) ) {
										echo ' ' . $detailed_address[ 0 ]->zip_code;
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
									<?php echo $val[ 'short_address' ];
									$detailed_address = $TMSShipper->get_shipper_by_id( $val[ 'address_id' ] );
									if ( is_array( $detailed_address ) ) {
										echo ' ' . $detailed_address[ 0 ]->zip_code;
									}
									?>
                                </p>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>

                <td>
                    <span class="<?php echo $modify_booked_price_class; ?>"><?php echo $booked_rate; ?></span>
                </td>
                
                <td>
                    <?php echo $helper->get_label_by_key($load_status, 'statuses'); ?>
                </td>
                
                <td><?php echo $quick_pay_option_value === '1' ? 'On' : 'Off'; ?></td>
                <td><?php echo $quick_pay_percent_value ? $quick_pay_percent_value.'%' : ''; ?></td>
                <td><?php echo $delivery_date; ?></td>
                <td><?php echo $days_to_pay_value; ?></td>
                <td><?php echo $days_passed; ?> days</td>
                
                <td>
					
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
                </td>
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