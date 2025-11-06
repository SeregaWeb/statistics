<?php
/**
 * Company Report Table Template
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $global_options;

$helper   = new TMSReportsHelper();
$report   = new TMSReports();
$brokers  = new TMSReportsCompany();
$TMSUsers = new TMSUsers();

// Check user role access with validation
$select_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'moderator',
	'administrator',
	'accounting',
	'billing',
), true );

// Extract and validate data with proper fallbacks
$link_broker  = '';
$results      = array();
$total_pages  = 0;
$current_page = 1;

if ( is_array( $global_options ) ) {
	$link_broker = isset( $global_options['single_page_broker'] ) ? $global_options['single_page_broker'] : '';
}

if ( is_array( $args ) ) {
	$results      = isset( $args['results'] ) ? $args['results'] : array();
	$total_pages  = isset( $args['total_pages'] ) ? (int) $args['total_pages'] : 0;
	$current_page = isset( $args['current_page'] ) ? (int) $args['current_page'] : 1;
}

// Ensure current_page is at least 1
$current_page = max( 1, $current_page );

if ( ! empty( $results ) && is_array( $results ) ) : ?>

	<table class="table mb-5 w-100">
		<thead>
			<tr>
				<th scope="col">Company Name</th>
				<th scope="col">Address</th>
				<th scope="col">Contacts</th>
				<th scope="col">MC</th>
				<th scope="col">Factoring status</th>
				<th scope="col">Set up platform</th>
				<?php if ( $select_all_offices ) : ?>
					<th scope="col">Gross</th>
					<th scope="col">Profit</th>
				<?php endif; ?>
				<th scope="col">Notes</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $results as $row ) : ?>
				<?php
				// Validate row data
				if ( ! is_array( $row ) ) {
					continue;
				}
				
				// Get profit data with validation
				$profit = array();
				if ( isset( $row['id'] ) ) {
					$profit = $report->get_profit_and_gross_by_brocker_id( $row['id'] );
					if ( ! is_array( $profit ) ) {
						$profit = array();
					}
				}
				
				// Build full address with validation
				$address_parts = array();
				if ( isset( $row['address1'] ) ) {
					$address_parts[] = $row['address1'];
				}
				if ( isset( $row['city'] ) ) {
					$address_parts[] = $row['city'];
				}
				if ( isset( $row['state'] ) ) {
					$address_parts[] = $row['state'];
				}
				if ( isset( $row['zip_code'] ) ) {
					$address_parts[] = $row['zip_code'];
				}
				if ( isset( $row['country'] ) ) {
					$address_parts[] = $row['country'];
				}
				$full_address = implode( ' ', array_filter( $address_parts ) );
				
				// Get company status with validation
				$company_status = '';
				if ( isset( $row['meta']['company_status'] ) ) {
					$company_status = $row['meta']['company_status'];
				}

				// Get platform label with validation
				$platform = '';
				if ( isset( $row['set_up_platform'] ) ) {
					$platform = $helper->get_label_by_key( $row['set_up_platform'], 'set_up_platform' );
				}
				
				// Get meta data with validation
				$meta = array();
				$factoring_status = '';
				$notice = '';
				
				if ( isset( $row['meta'] ) && is_array( $row['meta'] ) ) {
					$meta = $row['meta'];
					$factoring_status = isset( $meta['factoring_broker'] ) ? $meta['factoring_broker'] : '';
					$notice = isset( $meta['notes'] ) ? $meta['notes'] : '';
				}
				
				// Get broker statistics (notices count)
				$broker_statistics = $brokers->get_broker_statistics( $row['id'] );
				
				// Get factoring status label
				$factoring_label = '';
				if ( ! empty( $factoring_status ) && isset( $helper->factoring_broker[$factoring_status] ) ) {
					$factoring_label = $helper->factoring_broker[$factoring_status];
				}
				
				// Determine row CSS class based on status
				$class_status = '';
				
				if ( $factoring_status === 'denied' || $company_status === 'blocked' ) {
					$class_status = 'brocker-red';
				} elseif ( $company_status === 'be_attentive' || 
						  $company_status === 'discuss_with_manager' || 
						  $factoring_status === 'can-be-discussed' || 
						  $factoring_status === 'one-load-allowed' ) {
					$class_status = 'brocker-orange';
				} elseif ( $factoring_status === 'not-found' ) {
					$class_status = 'brocker-gray';
				}
				?>
				<tr class="<?php echo esc_attr( $class_status ); ?>">
					<td>
						<?php if ( ! empty( $link_broker ) && isset( $row['id'] ) ) : ?>
							<a href="<?php echo esc_url( $link_broker . '?broker_id=' . $row['id'] ); ?>">
								<?php echo esc_html( isset( $row['company_name'] ) ? $row['company_name'] : '' ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( isset( $row['company_name'] ) ? $row['company_name'] : '' ); ?>
						<?php endif; ?>
					</td>
					<td style="width: 260px;">
						<?php echo esc_html( $full_address ); ?>
					</td>
					<td>
						<div class="d-flex flex-column">
							<span>
								<?php echo esc_html( isset( $row['contact_first_name'] ) ? $row['contact_first_name'] : '' ); ?>
								<?php echo esc_html( isset( $row['contact_last_name'] ) ? $row['contact_last_name'] : '' ); ?>
							</span>
							<span class="text-small">
								<?php echo esc_html( isset( $row['phone_number'] ) ? $row['phone_number'] : '' ); ?>
							</span>
							<span class="text-small">
								<?php echo esc_html( isset( $row['email'] ) ? $row['email'] : '' ); ?>
							</span>
						</div>
					</td>
					<td>
						<?php echo esc_html( isset( $row['mc_number'] ) ? $row['mc_number'] : '' ); ?>
					</td>
					<td>
						<?php echo esc_html( $factoring_label ); ?>
					</td>
					<td>
						<?php echo esc_html( $platform ); ?>
					</td>
					<?php if ( $select_all_offices ) : ?>
						<td>
							<?php 
							$booked_rate = isset( $profit['booked_rate_total'] ) ? $profit['booked_rate_total'] : 0;
							echo $booked_rate === 0 ? '' : esc_html( $booked_rate );
							?>
						</td>
						<td>
							<?php 
							$profit_total = isset( $profit['profit_total'] ) ? $profit['profit_total'] : 0;
							echo $profit_total === 0 ? '' : esc_html( $profit_total );
							?>
						</td>
					<?php endif; ?>
					<td style="width: 200px; min-width: 200px;">
						<?php echo esc_html( $notice ); ?>
					</td>
					<td style="width: 86px;">
					<button type="button"
                                class="btn btn-primary w-100 btn-sm d-flex align-items-center justify-content-between gap-1 js-broker-notice-btn"
                                data-broker-id="<?php echo $row[ 'id' ]; ?>"
                                data-broker-name="<?php echo esc_attr( isset( $row['company_name'] ) ? $row['company_name'] : '' ); ?>"
                                data-notice-count="<?php echo $broker_statistics[ 'notice' ][ 'count' ]; ?>">
							<?php echo $broker_statistics[ 'notice' ][ 'count' ] > 0
								? $broker_statistics[ 'notice' ][ 'count' ] : '-'; ?>
                            <svg viewBox="-1 0 46 46" width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                 fill="white">
                                <g id="_6" data-name="6" transform="translate(-832 -151.466)">
                                    <g id="Group_263" data-name="Group 263">
                                        <rect id="Rectangle_63" data-name="Rectangle 63" width="6" height="7"
                                              transform="translate(832 155.466)"></rect>
                                        <path id="Path_188" data-name="Path 188"
                                              d="M832,191.827l3,5.419,3-5.419V163.466h-6Z"></path>
                                        <g id="Group_262" data-name="Group 262">
                                            <g id="Group_261" data-name="Group 261">
                                                <path id="Path_189" data-name="Path 189"
                                                      d="M864.907,155.466l-.3-1H862v-3h-6v3h-3.033l-.3,1H842v42h34v-42Zm9.093,40H844v-38h8.171l-.66,3h14.556l-.66-3H874Z"></path>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<?php
	// Load pagination template with validated data
	get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_page,
	) );
	?>
	
	<?php get_template_part( TEMPLATE_PATH . 'popups/broker-notice' ); ?>

<?php else : ?>
	<p>No reports found.</p>
<?php endif; ?>