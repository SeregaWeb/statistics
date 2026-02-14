<?php
global $global_options, $report_data;

$flt = get_field_value( $args, 'flt' );


$add_new_load = get_field_value( $global_options, 'add_new_load' ) ?? '';
$link_broker  = get_field_value( $global_options, 'single_page_broker' ) ?? '';

$TMSUsers        = new TMSUsers();
$TMSShipper      = new TMSReportsShipper();
$TMSBroker       = new TMSReportsCompany();
$helper          = new TMSReportsHelper();
$statuses_labels = $helper->statuses_factoring_labels;

$results       = get_field_value( $args, 'results' ) ?? [];
$total_pages   = get_field_value( $args, 'total_pages' ) ?? 0;
$current_pages = get_field_value( $args, 'current_pages' ) ?? 1;
$is_draft      = get_field_value( $args, 'is_draft' ) ?? false;
$is_ar_problev = get_field_value( $args, 'ar_problem' ) ?? false;

$page_type = get_field_value( $args, 'page_type' ) ?? '';

$current_user_id = get_current_user_id();

$billing_info              = $TMSUsers->check_user_role_access( array(
	'administrator',
	'billing',
	'accounting'
), true );
$hide_billing_and_shipping = $TMSUsers->check_user_role_access( array( 'billing', 'accounting' ), true );

$my_team     = $TMSUsers->check_group_access() ?: [];
$report_data = [];

$results_list = is_array( $results ) ? $results : array();
if ( ! empty( $results_list ) ) {
	$customer_ids = array();
	foreach ( $results_list as $row ) {
		$meta_row = get_field_value( $row, 'meta_data' );
		$cid      = get_field_value( $meta_row, 'customer_id' );
		if ( $cid !== '' && $cid !== null ) {
			$customer_ids[] = (int) $cid;
		}
	}
	$customer_ids    = array_unique( array_filter( $customer_ids ) );
	$companies_by_id = $TMSBroker->get_companies_by_ids( $customer_ids );
	$brokers_by_id   = $TMSBroker->get_brokers_data_by_ids( $customer_ids, $companies_by_id );
}

if ( ! empty( $results_list ) ) :?>


    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col" title="dispatcher">LOAD NO</th>
            <th scope="col">Broker</th>
            <th scope="col">Factoring status</th>

            <th scope="col">ORIGIN</th>
            <th scope="col">DESTINATION</th>
            <th scope="col">Booked rate</th>
            <th scope="col">Load status</th>

            <th scope="col">QP option</th>
            <th scope="col">QP percent</th>
            <th scope="col">Delivery Date</th>
            <th scope="col">Days to pay</th>
            <th scope="col">Invoice status</th>
            <th scope="col">Days since invoiced</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results_list as $row ) :
			
			$meta = get_field_value( $row, 'meta_data' ) ?? [];
			$pdlocations = $helper->get_locations_template( $row ) ?: [];
			
			$days_to_pay_value       = get_field_value( $row, 'days_to_pay_value' ) ?? '';
			$quick_pay_option_value  = get_field_value( $row, 'quick_pay_option_value' ) ?? '';
			$quick_pay_percent_value = get_field_value( $row, 'quick_pay_percent_value' ) ?? '';
			$factoring_broker_value  = get_field_value( $row, 'factoring_broker_value' ) ?? '';
			
			$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' ) ?? '';
			$invoice_status      = get_field_value( $meta, 'factoring_status' ) ?? '';
			$processing          = get_field_value( $meta, 'processing' ) ?? '';
			
			$dispatcher     = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials ) : '#030303';
			if ( ! $dispatcher ) {
				$dispatcher = [ 'full_name' => 'User not found', 'initials' => 'NF' ];
			}
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) ?? 'N/A' );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' ) ?? 0;
			$booked_rate     = esc_html( '$' . $helper->format_currency( $booked_rate_raw ) );
			
			$factoring_status = $factoring_broker_value ?: 'N/A';
			$load_status      = get_field_value( $meta, 'load_status' ) ?? '';
			
			$load_problem_raw = get_field_value( $row, 'load_problem' ) ?? '0000-00-00 00:00:00';
			
			
			if ( $load_problem_raw === '0000-00-00 00:00:00' ) {
				$days_passed = '';
			} else {
				try {
					$date_problem = new DateTime( $load_problem_raw );
					$current_date = new DateTime();
					$interval     = $date_problem->diff( $current_date );
					
					if ( $interval->days > 0 ) {
						$days_passed = $interval->days . ' days';
					} else {
						$days_passed = '';
					}
				}
				catch ( Exception $e ) {
					$days_passed = 'Invalid date';
				}
			}
			
			$show_control = $TMSUsers->show_control_loads( $my_team, $current_user_id, $dispatcher_initials, $is_draft );
			
			$ar_status = get_field_value( $meta, 'ar_status' ) ?? '';
			
			$booked_price_class = $helper->get_modify_class( $meta, 'modify_price' ) ?? '';
			
			$id_customer          = (int) ( get_field_value( $meta, 'customer_id' ) ?? 0 );
			$template_broker_data = isset( $brokers_by_id[ $id_customer ] ) ? $brokers_by_id[ $id_customer ] : array();

			$template_broker = $template_broker_data[ 'template' ] ?? 'N/A';
			$broker_name     = $template_broker_data[ 'name' ] ?? 'N/A';
			$broker_mc       = $template_broker_data[ 'mc' ] ?? 'N/A';

			$current_company_name = isset( $companies_by_id[ $id_customer ]['company_name'] ) ? $companies_by_id[ $id_customer ]['company_name'] : '';
			
			$all_paid = 0;
			if ( $invoice_status !== 'in-processing' && $invoice_status !== 'paid' ) {
				$all_paid += $booked_rate_raw;
			}
			
			if ( in_array( $processing, [ 'factoring-delayed-advance', 'unapplied-payment' ], true ) ) {
				
				if ( ! isset( $report_data[ 'broker_name_' . $id_customer ] ) ) {
					$report_data[ 'broker_name_' . $id_customer ] = [
						'name'     => $broker_name,
						'mc'       => $broker_mc,
						'statuses' => []
					];
				}
				
				$report_data[ 'broker_name_' . $id_customer ][ 'statuses' ][ $processing ] = ( $report_data[ 'broker_name_' . $id_customer ][ 'statuses' ][ $processing ] ?? 0 ) + $booked_rate_raw;
				
			} elseif ( in_array( $invoice_status, [
				'fraud',
				'company-closed',
				'pending-to-tafs',
				'in-processing'
			], true ) ) {
				
				if ( ! isset( $report_data[ 'broker_name_' . $id_customer ] ) ) {
					$report_data[ 'broker_name_' . $id_customer ] = [
						'name'     => $broker_name,
						'mc'       => $broker_mc,
						'statuses' => []
					];
				}
				
				$report_data[ 'broker_name_' . $id_customer ][ 'statuses' ][ $invoice_status ] = ( $report_data[ 'broker_name_' . $id_customer ][ 'statuses' ][ $invoice_status ] ?? 0 ) + $booked_rate_raw;
			}
			
			if ( isset( $report_data ) ) {
				$report_data[ 'all_paid_without_two_status' ] = $all_paid;
			}
			
			?>

            <tr class="direct_invoicing_<?php echo $invoice_status; ?>">
                <td>
                    <div class="d-flex gap-1 flex-column">
                        <p class="m-0">
                              <span data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                                    class="initials-circle"
                                    style="background-color: <?php echo esc_attr( $color_initials ); ?>">
                                  <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
                              </span>
                        </p>
                        <span class="text-small"><?php echo $reference_number; ?></span>
                    </div>
                    <?php if ( ! empty( $current_company_name ) ): ?>
                        <div class="d-flex flex-column">
                            <span style="font-size: 10px;"><?php echo $current_company_name; ?></span>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
					<?php echo $template_broker; ?>
                </td>
                <td><?php echo $helper->get_label_by_key( $factoring_status, 'factoring_broker' ); ?></td>

                <td>
					<?php echo $pdlocations[ 'pick_up_template' ] ?? 'N/A'; ?>
                </td>
                <td>
					<?php echo $pdlocations[ 'delivery_template' ] ?? 'N/A'; ?>
                </td>

                <td>
                    <span class="<?php echo esc_attr( $booked_price_class ); ?>"><?php echo $booked_rate; ?></span>
                </td>

                <td>
					<?php echo $helper->get_label_by_key( $load_status, 'statuses' ); ?>
                </td>

                <td><?php echo $quick_pay_option_value === '1' ? 'Av.' : ''; ?></td>
                <td><?php echo $quick_pay_percent_value ? $quick_pay_percent_value . '%' : ''; ?></td>
                <td><?php echo $pdlocations[ 'delivery_date' ] ?? 'N/A'; ?></td>
                <td><?php echo $days_to_pay_value; ?></td>
                <td>
					<?php echo $helper->get_label_by_key( $invoice_status, 'factoring_status' ); ?><br>
                    <span class="text-small">
                        <?php echo $helper->get_label_by_key( $processing, 'processing' ); ?>
                    </span>
                </td>
                <td><?php echo $invoice_status !== "paid" ? $days_passed : ''; ?></td>

                <td>
					
					<?php if ( $show_control ):
						
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown', array(
							'id'       => $row[ 'id' ],
							'is_draft' => $is_draft,
							'flt'      => $flt,
						) ) );
					
					
					endif; ?>
                </td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
	
	<?php
	
	
	echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', [
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	] ) );
	?>

<?php else : ?>
    <p>No reports found.</p>
<?php endif; ?>