<?php
/**
 * Driver Insurance Table Template
 * Displays driver insurance information in a table format
 *
 * @package WP-rock
 */

$drivers = new TMSDrivers();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );

/**
 * Get project-specific field configuration
 *
 * @return array Array with 'policy_field' and 'expiration_field' keys
 */
function get_project_insurance_fields() {
	$user_id        = get_current_user_id();
	$current_project = get_field( 'current_select', 'user_' . $user_id );
	
	if ( empty( $current_project ) ) {
		$current_project = 'odysseia';
	}
	
	$policy_field     = 'auto_liability_policy';
	$expiration_field = 'auto_liability_expiration';
	
	if ( $current_project === 'martlet' ) {
		$policy_field     = '';
		$expiration_field = 'martlet_coi_expired_date';
	} elseif ( $current_project === 'endurance' ) {
		$policy_field     = '';
		$expiration_field = 'endurance_coi_expired_date';
	}
	
	return array(
		'policy_field'     => $policy_field,
		'expiration_field' => $expiration_field,
	);
}

/**
 * Parse expiration date and calculate days until expiration
 *
 * @param string $expiration_date Raw expiration date string
 * @return array Array with 'formatted' and 'days_until' keys
 */
function parse_expiration_date( $expiration_date ) {
	if ( empty( $expiration_date ) ) {
		return array(
			'formatted'   => '',
			'days_until'  => '',
		);
	}
	
	$date_formats = array( 'm/d/Y', 'Y-m-d', 'm-d-Y', 'Y/m/d', 'm/d/y' );
	$exp_timestamp = false;
	
	// Try to parse date with known formats
	foreach ( $date_formats as $format ) {
		$date_obj = DateTime::createFromFormat( $format, trim( $expiration_date ) );
		if ( $date_obj !== false ) {
			$exp_timestamp = $date_obj->getTimestamp();
			break;
		}
	}
	
	// Fallback to strtotime
	if ( $exp_timestamp === false ) {
		$exp_timestamp = strtotime( str_replace( '/', '-', trim( $expiration_date ) ) );
	}
	
	if ( $exp_timestamp === false ) {
		return array(
			'formatted'   => $expiration_date,
			'days_until'  => '',
		);
	}
	
	// Calculate days difference
	$ny_timezone = new DateTimeZone( 'America/New_York' );
	$today = new DateTime( 'now', $ny_timezone );
	$today->setTime( 0, 0, 0 );
	
	$exp_date_obj = new DateTime( '@' . $exp_timestamp );
	$exp_date_obj->setTimezone( $ny_timezone );
	$exp_date_obj->setTime( 0, 0, 0 );
	
	$expiration_formatted = $exp_date_obj->format( 'm/d/Y' );
	
	$diff = $exp_date_obj->getTimestamp() - $today->getTimestamp();
	$days = floor( $diff / 86400 );
	
	// Generate days until expiration message
	$days_until_expiration = '';
	if ( $days < 0 ) {
		$days_until_expiration = '<span class="text-danger">Expired ' . abs( $days ) . ' days ago</span>';
	} elseif ( $days === 0 ) {
		$days_until_expiration = '<span class="text-warning">Expires today</span>';
	} elseif ( $days <= 30 ) {
		$days_until_expiration = '<span class="text-warning">' . $days . ' days left</span>';
	} else {
		$days_until_expiration = '<span class="text-success">' . $days . ' days left</span>';
	}
	
	return array(
		'formatted'   => $expiration_formatted,
		'days_until'  => $days_until_expiration,
	);
}

$project_fields = get_project_insurance_fields();
$policy_field     = $project_fields['policy_field'];
$expiration_field = $project_fields['expiration_field'];

if ( ! empty( $results ) ) : ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Status</th>
            <th scope="col">Driver</th>
            <th scope="col">Policy number</th>
            <th scope="col">Company</th>
            <th scope="col">Exp date</th>
            <th scope="col">Agent info</th>
            <th scope="col">Comments</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			
			// Driver basic info
			$driver_name   = get_field_value( $meta, 'driver_name' );
			$driver_status = trim( get_field_value( $meta, 'driver_status' ) );
			$driver_email  = get_field_value( $meta, 'driver_email' );
			
			// Driver phone
			$show_phone   = get_field_value( $meta, 'show_phone' ) ?? 'driver_phone';
			$driver_phone = get_field_value( $meta, $show_phone );
			
			// Policy and expiration data
			$policy_number    = ! empty( $policy_field ) ? get_field_value( $meta, $policy_field ) : '';
			$company          = get_field_value( $meta, 'auto_liability_insurer' );
			$expiration_date  = get_field_value( $meta, $expiration_field );
			
			// Agent info
			$insurance_agent_name  = get_field_value( $meta, 'insurance_agent_name' );
			$insurance_agent_phone = get_field_value( $meta, 'insurance_agent_phone' );
			$insurance_agent_email = get_field_value( $meta, 'insurance_agent_email' );
			
			// Notes
			$notes = get_field_value( $meta, 'notes' );
			
			// Parse expiration date
			$expiration_data = parse_expiration_date( $expiration_date );
			$expiration_formatted = $expiration_data['formatted'];
			$days_until_expiration = $expiration_data['days_until'];
			
			// Driver status text
			if ( $driver_status && isset( $drivers->status[ $driver_status ] ) ) {
				$status_text = $drivers->status[ $driver_status ];
			} else {
				$status_text = 'Need set status';
			}
			
			// Hide specific driver for testing (remove when no longer needed)
			$class_hide = $row['id'] === '3343' ? 'd-none' : '';
			?>

            <tr class="<?php echo esc_attr( $class_hide ); ?>" data-driver-id="<?php echo esc_attr( $row['id'] ); ?>">
                <td style="width: 100px;" class="<?php echo esc_attr( $driver_status ? $driver_status : 'text-danger' ); ?> driver-status">
					<?php echo esc_html( $status_text ); ?>
				</td>
                
                <td style="max-width: 300px;">
                    <div class="d-flex flex-column">
                        <div>
							<?php echo esc_html( '(' . $row['id'] . ') ' . $driver_name ); ?>
                        </div>
                        <span class="text-small driver-phone"
                              data-phone="<?php echo esc_attr( $driver_phone ); ?>"><?php echo esc_html( $driver_phone ); ?></span>
                        <span class="text-small driver-email"
                              data-email="<?php echo esc_attr( $driver_email ); ?>"><?php echo esc_html( $driver_email ); ?></span>
                    </div>
                </td>
                
                <td style="max-width: 300px;"><?php echo esc_html( $policy_number ); ?></td>
                
                <td style="max-width: 200px;"><?php echo esc_html( $company ); ?></td>
                
                <td>
					<?php if ( ! empty( $expiration_formatted ) ) : ?>
						<div class="d-flex flex-column">
							<span><?php echo esc_html( $expiration_formatted ); ?></span>
							<?php if ( ! empty( $days_until_expiration ) ) : ?>
								<span class="text-small"><?php echo $days_until_expiration; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</td>
                
                <td>
					<?php if ( ! empty( $insurance_agent_name ) || ! empty( $insurance_agent_phone ) || ! empty( $insurance_agent_email ) ) : ?>
						<div class="d-flex flex-column">
							<?php if ( ! empty( $insurance_agent_name ) ) : ?>
								<span><?php echo esc_html( $insurance_agent_name ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $insurance_agent_phone ) ) : ?>
								<span class="text-small"><?php echo esc_html( $insurance_agent_phone ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $insurance_agent_email ) ) : ?>
								<span class="text-small"><?php echo esc_html( $insurance_agent_email ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</td>
                
                <td style="max-width: 300px;">
					<?php if ( ! empty( $notes ) ) : ?>
						<span class="text-small"><?php echo esc_html( $notes ); ?></span>
					<?php endif; ?>
				</td>

                <td style="width: 36px;">
                    <div class="d-flex gap-1 align-items-center justify-content-end">
						<?php get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown-driver', array(
							'id' => $row['id'],
						) ); ?>
                    </div>
                </td>
            </tr> <?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) );
	?>
	
	<?php get_template_part( TEMPLATE_PATH . 'popups/quick-status-update-modal' ); ?>

<?php else : ?>
    <p>No drivers were found.</p>
<?php endif; ?>

