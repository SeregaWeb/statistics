<?php
$drivers      = new TMSDrivers();
$helper       = new TMSReportsHelper();
$icons        = new TMSReportsIcons();
$driverHelper = new TMSDriversHelper();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );

if ( ! empty( $results ) ) : ?>
	
	<?php if ( $driverHelper->can_copy_driver_phones() ): ?>
        <div class="mb-3 d-flex justify-content-end">
            <button type="button" class="btn btn-outline-primary" id="copy-driver-phones-btn">
                Copy All Phone Numbers
            </button>
        </div>
	<?php endif; ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Date hired</th>
            <th scope="col">Recruiter</th>
            <th scope="col">Driver</th>
            <th scope="col">Vehicle</th>
            <th scope="col">Home location</th>
            <th scope="col">Additional</th>
            <th scope="col">Status</th>
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
			$home_location = get_field_value( $meta, 'home_location' );
			$city = get_field_value( $meta, 'city' );
			$vehicle_type = get_field_value( $meta, 'vehicle_type' );
			$vehicle_year = get_field_value( $meta, 'vehicle_year' );
			$vehicle_model = get_field_value( $meta, 'vehicle_model' );
			$vehicle_make = get_field_value( $meta, 'vehicle_make' );
			$dimensions = get_field_value( $meta, 'dimensions' );
			$payload = get_field_value( $meta, 'payload' );
			$driver_status = get_field_value( $meta, 'driver_status' );
			
			$show_phone   = get_field_value( $meta, 'show_phone' ) ?? 'driver_phone';
			$driver_phone = get_field_value( $meta, $show_phone );
			
			include( get_template_directory() . '/src/template-parts/report/common/driver-capabilities.php' );
			
			
			$date_hired    = get_field_value( $row, 'date_created' );
			$user_id_added = get_field_value( $row, 'user_id_added' );
			$date_hired    = esc_html( date( 'm/d/Y', strtotime( $date_hired ) ) );
			
			$user_recruiter = $helper->get_user_full_name_by_id( $user_id_added );
			$color_initials = $user_recruiter ? get_field( 'initials_color', 'user_' . $user_id_added ) : '#030303';
			$user_recruiter = $user_recruiter ?: [ 'full_name' => 'User not found', 'initials' => 'NF' ];

//			if ( $user_recruiter[ 'initials' ] === 'NF' ) {
//				$drivers->update_user_id_added( $row[ 'id' ], '68' );
//			}
//
//			if ( $date_hired == '11/30/-0001' ) {
//				$drivers->update_date_created( $row[ 'id' ] );
//			}
			
			$driver_status = trim( $driver_status );
			
			if ( $driver_status && isset( $drivers->status[ $driver_status ] ) || $driver_status === 'on_hold' ) {
				
				if ( $driver_status === 'on_hold' ) {
					$status_text = 'On hold';
				} else {
					$status_text = $drivers->status[ $driver_status ];
				}
			} else {
				$status_text = "Need set status";
			}
			
			// Get driver data for quick update button
			$current_location = get_field_value( $meta, 'current_location' );
			$current_city     = get_field_value( $meta, 'current_city' );
			$current_zipcode  = get_field_value( $meta, 'current_zipcode' );
			$latitud          = get_field_value( $meta, 'latitude' );
			$longitude        = get_field_value( $meta, 'longitude' );
			$country          = get_field_value( $meta, 'country' );
			$status_date      = get_field_value( $meta, 'status_date' );
			

			// TODO: Remove this after testing
			$class_hide = $row['id'] === '3343' ? 'd-none' : '';
			?>

            <tr class="<?php echo $class_hide; ?>" data-driver-id="<?php echo $row[ 'id' ]; ?>">
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
                        <span class="text-small driver-phone"
                              data-phone="<?php echo esc_attr( $driver_phone ); ?>"><?php echo $driver_phone; ?></span>
                    </div>
                </td>
                <td>
                    <div class="d-flex  flex-column">
						<?php echo $driverHelper->vehicle[ $vehicle_type ] ?? ''; ?>
                        <span class="text-small">
                            <?php
                            echo $vehicle_model;
                            echo ' ' . $vehicle_make;
                            echo ' ' . $vehicle_year;
                            ?>
                        </span>
                    </div>
                </td>
                <td><?php echo $city . ', ' . $home_location; ?></td>

                <td>
                    <div class="table-tags d-flex flex-wrap">
						<?php include( get_template_directory() . '/src/template-parts/report/common/driver-capabilities-display.php' ); ?>
                    </div>
                </td>

                <td style="width: 100px;" class="<?php echo $driver_status ? $driver_status
					: 'text-danger'; ?> driver-status"><?php echo $status_text; ?></td>

                <td style="width: 92px;">
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm d-flex align-items-center justify-content-center js-quick-status-update"
                                data-bs-toggle="modal"
                                data-bs-target="#quickStatusUpdateModal"
                                data-driver-id="<?php echo $row[ 'id' ]; ?>"
                                data-driver-name="<?php echo esc_attr( $driver_name ); ?>"
                                data-driver-status="<?php echo esc_attr( $driver_status ); ?>"
                                data-current-location="<?php echo esc_attr( $current_location ); ?>"
                                data-current-city="<?php echo esc_attr( $current_city ); ?>"
                                data-current-zipcode="<?php echo esc_attr( $current_zipcode ); ?>"
                                data-latitude="<?php echo esc_attr( $latitud ); ?>"
                                data-longitude="<?php echo esc_attr( $longitude ); ?>"
                                data-country="<?php echo esc_attr( $country ); ?>"
                                data-status-date="<?php echo esc_attr( $status_date ); ?>"
                                style="width: 28px; height: 28px; padding: 0;"
                                title="Quick Status Update">
							<?php echo $icons->get_icon_edit_2(); ?>
                        </button>
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
	
	<?php get_template_part( TEMPLATE_PATH . 'popups/quick-status-update-modal' ); ?>


<?php else : ?>
    <p>No drivers were found.</p>
<?php endif; ?>
