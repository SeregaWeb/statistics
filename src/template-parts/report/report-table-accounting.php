<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );

$TMSUsers   = new TMSUsers();
$TMSShipper = new TMSReportsShipper();

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

$helper = new TMSReportsHelper();

if ( ! empty( $results ) ) : ?>

    <div class="w-100 mb-3">
        <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_quick_edit">Quick edit
        </button>
    </div>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th><input class="checkbox-big js-select-load-all" type="checkbox" name="select-all"></th>
            <th scope="col">Booked</th>
            <th scope="col" title="dispatcher">Disp.</th>
            <th scope="col">Pick up location</th>
            <th scope="col">Delivery location</th>
            <th scope="col">Unit & name</th>
            <th scope="col">
                Gross
            </th>


            <th scope="col">Driver rate</th>

            <th scope="col">True Profit</th>

            <th scope="col">Pick Up</th>

            <th scope="col">Delivery</th>

            <th scope="col">Load status</th>

            <th scope="col">Invoice</th>
            <th scope="col">Factoring status</th>
            <th scope="col">Bank</th>
            <th scope="col">Payment</th>

            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$main = get_field_value( $row, 'main' );
			
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
			$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
			$driver_phone     = esc_html( get_field_value( $meta, 'driver_phone' ) );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' );
			$booked_rate     = esc_html( '$' . str_replace( '.00', '', $booked_rate_raw ) );
			
			$driver_rate_raw         = get_field_value( $meta, 'driver_rate' );
			$quick_pay_driver_amount = get_field_value( $meta, 'quick_pay_driver_amount' );
			
			if ( ! is_null( $quick_pay_driver_amount ) ) {
				$driver_rate_raw = floatval( $driver_rate_raw ) - floatval( $quick_pay_driver_amount );
			}
			
			$driver_rate = esc_html( '$' . str_replace( '.00', '', $driver_rate_raw ) );
			
			$true_profit_raw = get_field_value( $meta, 'true_profit' );
			$profit_class    = $true_profit_raw < 0 ? 'modified-price' : '';
			$true_profit     = esc_html( '$' . str_replace( '.00', '', $true_profit_raw ) );
			
			$pick_up_date_raw = get_field_value( $row, 'pick_up_date' );
			$pick_up_date     = esc_html( date( 'm/d/Y', strtotime( $pick_up_date_raw ) ) );
			
			$delivery_date_raw = get_field_value( $row, 'delivery_date' );
			$delivery_date     = esc_html( date( 'm/d/Y', strtotime( $delivery_date_raw ) ) );
			
			$factoring_status_row = get_field_value( $meta, 'factoring_status' );
			$factoring_status     = esc_html( $helper->get_label_by_key( $factoring_status_row, 'factoring_status' ) );
			
			$invoice_raw = get_field_value( $meta, 'invoiced_proof' );
			
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			
			$status_class    = $load_status;
			$factoring_class = strtolower( $factoring_status_row );
			$factoring_class = str_replace( ' ', '-', $factoring_class );
			
			$modify_booked_price       = get_field_value( $meta, 'modify_price' );
			$modify_booked_price_class = '';
			
			if ( $modify_booked_price === '1' ) {
				$modify_booked_price_class = 'modified-price';
			}
			
			$bank_status       = get_field_value( $meta, 'bank_payment_status' );
			$driver_pay_status = get_field_value( $meta, 'driver_pay_statuses' );
			
			
			$bank_status       = $helper->get_label_by_key( $bank_status, 'bank_statuses' );
			$driver_pay_status = $helper->get_label_by_key( $driver_pay_status, 'driver_payment_statuses' );
			
			$quick_pay_driver_amount = get_field_value( $meta, 'quick_pay_driver_amount' );
			$quick_pay_method        = get_field_value( $meta, 'quick_pay_method' );
			
			if ( $quick_pay_driver_amount && $quick_pay_method ) {
				$quick_pay_show        = floatval( $driver_rate_raw ) - floatval( $quick_pay_driver_amount );
				$quick_pay_show_method = $helper->get_quick_pay_methods_for_accounting( $quick_pay_method );
				$component_quick_pay   = "<span class='text-small'>$" . $quick_pay_show . " - " . $quick_pay_show_method . "</span>";
			}
			
			?>

            <tr class="">
                <td><input type="checkbox" id="load-<?php echo $row[ 'id' ]; ?>" class="checkbox-big js-select-load"
                           value="<?php echo $row[ 'id' ]; ?>" name="select-load"></td>

                <td><label class="h-100 cursor-pointer text-small"
                           for="load-<?php echo $row[ 'id' ]; ?>"><?php echo $date_booked; ?></label></td>
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
					<?php if ( is_array( $pick_up ) ): ?>
						<?php foreach ( $pick_up as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <p class="m-0 text-small" data-bs-toggle="tooltip" data-bs-placement="top"
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
                                <p class="m-0 text-small" data-bs-toggle="tooltip" data-bs-placement="top"
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
                    <div class="d-flex flex-column">
                        <p class="m-0"><?php echo $unit_number_name; ?></p>
                        <span class="text-small">
                            <?php echo $driver_phone; ?>
                        </span>
                    </div>
                </td>

                <td><span class="<?php echo $modify_booked_price_class; ?>"><?php echo $booked_rate; ?></span></td>


                <td>
                    <div class="d-flex flex-column gap-0">
						<?php echo $driver_rate; ?>
						<?php if ( $quick_pay_method ):
							echo $component_quick_pay;
						endif; ?>
                    </div>
                </td>

                <td><span class="<?php echo $profit_class; ?>"><?php echo $true_profit; ?></span></td>

                <td><span class="text-small"><?php echo $pick_up_date; ?></span></td>

                <td><span class="text-small"><?php echo $delivery_date; ?></span></td>


                <td class="<?php echo $status_class; ?>"><span><?php echo $status; ?></span></td>

                <td class="<?php echo $invoice_raw ? 'invoiced' : 'not-invoiced'; ?> ?>" ><span><?php echo $invoice_raw ? 'Invoiced' : 'Not invoiced'; ?></span></td>
                <td class="<?php echo $factoring_class; ?>"><span><?php echo $factoring_status; ?></span></td>
                <td>
					<?php echo $bank_status; ?>
                </td>
                <td>
					<?php echo $driver_pay_status; ?>
                </td>

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