<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );

$TMSUsers   = new TMSUsers();
$TMSShipper = new TMSReportsShipper();
$TMSBroker  = new TMSReportsCompany();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );
$is_ar_problev = get_field_value( $args, 'ar_problem' );

$page_type = get_field_value( $args, 'page_type' );

$current_user_id = get_current_user_id();

$hide_billing_and_shipping = $TMSUsers->check_user_role_access( array(
	'billing',
	'accounting',
	'administrator'
), true );

$my_team = $TMSUsers->check_group_access();

$helper = new TMSReportsHelper();

if ( ! empty( $results ) ) : ?>
	
	<?php if ( $hide_billing_and_shipping ): ?>
        <div class="w-100 mb-3">
            <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_quick_edit">Quick edit
            </button>
        </div>
	<?php endif; ?>
    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th><input class="checkbox-big js-select-load-all" type="checkbox" name="select-all"></th>
            <th scope="col">Booked</th>
            <th scope="col" title="dispatcher">LOAD NO</th>
            <th scope="col">Client</th>
            <th scope="col">Pick up</th>
            <th scope="col">Delivery</th>
            <th scope="col">Gross</th>
            <th scope="col">Load status</th>
            <th scope="col">Invoice</th>
            <th scope="col">Factoring status</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$main = get_field_value( $row, 'main' );
			
			$pdlocations = $helper->get_locations_template( $row );
			
			
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
			$booked_rate     = esc_html( '$' . $helper->format_currency( $booked_rate_raw ) );
			
			$factoring_status_row = get_field_value( $meta, 'factoring_status' );
			$factoring_status     = esc_html( $helper->get_label_by_key( $factoring_status_row, 'factoring_status' ) );
			
			$invoice_raw   = get_field_value( $meta, 'invoiced_proof' );
			$short_pay_raw = get_field_value( $meta, 'short_pay' );
			$short_pay     = esc_html( '$' . $helper->format_currency( $short_pay_raw ) );
			
			$show_control    = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			$factoring_class = strtolower( $factoring_status_row );
			$factoring_class = str_replace( ' ', '-', $factoring_class );
			
			$booked_price_class = $helper->get_modify_class( $meta, 'modify_price' );
			
			$bank_status       = get_field_value( $meta, 'bank_payment_status' );
			$driver_pay_status = get_field_value( $meta, 'driver_pay_statuses' );
			$tbd               = get_field_value( $meta, 'tbd' );
			
			
			$bank_status       = $helper->get_label_by_key( $bank_status, 'bank_statuses' );
			$driver_pay_status = $helper->get_label_by_key( $driver_pay_status, 'driver_payment_statuses' );
			
			$now_show = ( $factoring_status_row === 'paid' );
			
			$id_customer     = get_field_value( $meta, 'customer_id' );
			$template_broker = $TMSBroker->get_broker_and_link_by_id( $id_customer );
			?>

            <tr class="factoring-color-<?php echo $factoring_class; ?> <?php echo $tbd ? 'tbd' : ''; ?>">
                <td><input <?php echo $now_show ? 'disabled' : ''; ?> type="checkbox"
                                                                      id="load-<?php echo $row[ 'id' ]; ?>"
                                                                      class="checkbox-big js-select-load"
                                                                      value="<?php echo $row[ 'id' ]; ?>"
                                                                      name="select-load"></td>

                <td><label class="h-100 cursor-pointer text-small"
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
					<?php echo $template_broker; ?>
                </td>

                <td>
					<?php echo $pdlocations[ 'pick_up_template' ] ?>
					<?php echo $pdlocations[ 'pick_up_date' ] ?>
                </td>
                <td>
					<?php echo $pdlocations[ 'delivery_template' ] ?>
					<?php echo $pdlocations[ 'delivery_date' ] ?>
                </td>

                <td>
                    <span class="<?php echo $booked_price_class; ?>">
                        <?php echo $factoring_status_row === 'short-pay' ? $short_pay : $booked_rate; ?>
                    </span>
                </td>
                <td>
                    <span>
                        <?php echo $status; ?>
                    </span>
                </td>
                <td>
                    <span>
                        <?php echo $invoice_raw ? 'Invoiced' : 'Not invoiced'; ?>
                    </span>
                </td>
                <td>
                    <span>
                        <?php echo $factoring_status; ?>
                    </span>
                </td>

                <td>
					<?php if ( $show_control ): ?>
                        <div class="d-flex">
                            <button class="btn-bookmark js-btn-bookmark <?php echo $TMSUsers->is_bookmarked( $row[ 'id' ], isset( $flt ) ? $flt : false )
								? 'active' : ''; ?>" data-id="<?php echo $row[ 'id' ]; ?>"
								data-flt="<?php echo isset( $flt ) ? ( $flt ? '1' : '0' ) : '0'; ?>">
								<?php echo $helper->get_icon_bookmark(); ?>
                            </button>
							<?php
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown', array(
								'id'       => $row[ 'id' ],
								'is_draft' => $is_draft,
							) ) );
							?>

                        </div>
					<?php endif; ?>
                </td>
            </tr>
		
		<?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>

<?php else : ?>
    <p>No reports found.</p>
<?php endif; ?>