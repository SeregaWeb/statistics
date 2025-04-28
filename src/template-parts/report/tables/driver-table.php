<?php

$helper       = new TMSReportsHelper();
$icons        = new TMSReportsIcons();
$driverHelper = new TMSDriversHelper();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );

if ( ! empty( $results ) ) : ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Date hired</th>
            <th scope="col">Recruiter</th>
            <th scope="col">Driver</th>
            <th scope="col">Vehicle</th>
            <th scope="col">Home location</th>
            <th scope="col">Additional</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php
		
		foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$driver_name = get_field_value( $meta, 'driver_name' );
			$languages = get_field_value( $meta, 'languages' );
			$driver_email = get_field_value( $meta, 'driver_email' );
			$driver_phone = get_field_value( $meta, 'driver_phone' );
			$home_location = get_field_value( $meta, 'home_location' );
			$city = get_field_value( $meta, 'city' );
			$vehicle_type = get_field_value( $meta, 'vehicle_type' );
			$vehicle_year = get_field_value( $meta, 'vehicle_year' );
			$dimensions = get_field_value( $meta, 'dimensions' );
			$payload = get_field_value( $meta, 'payload' );
			$preferred_distance = get_field_value( $meta, 'preferred_distance' );
			$selected_distances = array_map( 'trim', explode( ',', $preferred_distance ) );
			
			$driver_capabilities = array(
				'twic.svg'               => get_field_value( $meta, 'twic' ),
				'tsa.svg'                => get_field_value( $meta, 'tsa_approved' ),
				'hazmat.svg'             => get_field_value( $meta, 'hazmat_certificate' ) || get_field_value( $meta, 'hazmat_endorsement' ),
				'change-9.svg'           => get_field_value( $meta, 'change_9_training' ),
				'canada.svg'             => get_field_value( $meta, 'canada_transition_proof' ),
				'tanker-endorsement.svg' => get_field_value( $meta, 'tanker_endorsement' ),
				'background-check.svg'   => get_field_value( $meta, 'background_check' ),
				'liftgate.svg'           => get_field_value( $meta, 'lift_gate' ),
				'pallet-jack.svg'        => get_field_value( $meta, 'pallet_jack' ),
				'dolly.svg'              => get_field_value( $meta, 'dolly' ),
				'ppe.svg'                => get_field_value( $meta, 'ppe' ),
				'e-track.svg'            => get_field_value( $meta, 'e_tracks' ),
				'ramp.svg'               => get_field_value( $meta, 'ramp' ),
				'printer.svg'            => get_field_value( $meta, 'printer' ),
				'sleeper.svg'            => get_field_value( $meta, 'sleeper' ),
				'load-bars.svg'          => get_field_value( $meta, 'load_bars' ),
				'mc.svg'                 => get_field_value( $meta, 'mc' ),
				'dot.svg'                => get_field_value( $meta, 'dot' ),
				'real_id.svg'            => get_field_value( $meta, 'real_id' ),
				'macropoint.png'         => get_field_value( $meta, 'macro_point' ),
				'tucker-tools.png'       => get_field_value( $meta, 'trucker_tools' ),
				'any.svg'                => is_numeric( array_search( 'any', $selected_distances ) ),
				'otr.svg'                => is_numeric( array_search( 'otr', $selected_distances ) ),
				'local.svg'              => is_numeric( array_search( 'local', $selected_distances ) ),
				'regional.svg'           => is_numeric( array_search( 'regional', $selected_distances ) ),
			);
			
			
			$date_hired    = get_field_value( $row, 'date_created' );
			$user_id_added = get_field_value( $row, 'user_id_added' );
			$date_hired    = esc_html( date( 'm/d/Y', strtotime( $date_hired ) ) );
			
			$user_recruiter = $helper->get_user_full_name_by_id( $user_id_added );
			$color_initials = $user_recruiter ? get_field( 'initials_color', 'user_' . $user_id_added ) : '#030303';
			$user_recruiter = $user_recruiter ?: [ 'full_name' => 'User not found', 'initials' => 'NF' ];
			?>

            <tr>
                <td><?php echo $date_hired; ?></td>
                <td>

                    <div class="d-flex  flex-row align-items-center">
                        <p class="m-0">
                            <span data-bs-toggle="tooltip" data-bs-placement="top"
                                  title="<?php echo $user_recruiter[ 'full_name' ]; ?>"
                                  class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                                <?php echo esc_html( $user_recruiter[ 'initials' ] ); ?>
                            </span>
                        </p>
                    </div>
                </td>
                <td>
                    <div class="d-flex  flex-column">
                        <div>
							<?php echo '(' . $row[ 'id' ] . ') ' . $driver_name; ?>
							<?php echo $icons->get_flags( $languages ); ?>
                        </div>
                        <span class="text-small"><?php echo $driver_phone; ?></span>
                    </div>
                </td>
                <td>
                    <div class="d-flex  flex-column">
						<?php echo $driverHelper->vehicle[ $vehicle_type ] ?? ''; ?>
                        <span class="text-small"><?php echo $vehicle_year; ?></span>
                    </div>
                </td>
                <td><?php echo $city . ', ' . $home_location; ?></td>

                <td>
                    <div class="table-tags d-flex gap-1 flex-wrap">
						<?php
						$array_additionals = $icons->get_capabilities( $driver_capabilities );
						if ( ! empty( $array_additionals ) ) {
							foreach ( $array_additionals as $value ) {
								?>
                                <img width="30" height="30" src="<?php echo $value; ?>" alt="tag">
								<?php
							}
						}
						
						?>
                    </div>
                </td>
                <td>
                    <div class="d-flex">
						<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown-driver', [
							'id'       => $row[ 'id' ],
							'is_draft' => $is_draft,
						] ) ); ?>
                    </div>
                </td>
            </tr> <?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	
	
	echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>

<?php else : ?>
    <p>No loads found.</p>
<?php endif; ?>