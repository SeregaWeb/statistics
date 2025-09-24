<?php
$drivers      = new TMSDrivers();
$helper       = new TMSReportsHelper();
$icons        = new TMSReportsIcons();
$driverHelper = new TMSDriversHelper();
$TMSUsers     = new TMSUsers();

$access_hold = $TMSUsers->check_user_role_access( array( 'administrator', 'expedite_manager', 'dispatcher-tl', 'dispatcher' ), true );
global $global_options;

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );
$add_new_load  = get_field_value( $global_options, 'add_new_driver' );


// Debug: Show sorting result if we have filtered_drivers
if ( isset( $args[ 'filtered_drivers' ] ) && ! empty( $args[ 'filtered_drivers' ] ) ) {
	echo '<div style="background: #ffffcc; padding: 10px; margin: 10px 0; border: 1px solid #ffcc00; border-radius: 5px;">';
	echo '<strong>SORTING RESULT:</strong><br>';
	$counter = 1;
	foreach ( $args[ 'filtered_drivers' ] as $driver_id => $driver_data ) {
		$status   = isset( $driver_data[ 'status' ] ) ? trim( $driver_data[ 'status' ] ) : '';
		$updated  = isset( $driver_data[ 'updated' ] ) ? ( $driver_data[ 'updated' ] ? 'true' : 'false' ) : 'unknown';
		$distance = isset( $driver_data[ 'distance' ] ) ? $driver_data[ 'distance' ] : 'N/A';
		echo "#{$counter}: Driver {$driver_id} - Status: '{$status}' - Updated: {$updated} - Distance: {$distance} ml.<br>";
		$counter ++;
	}
	echo '</div>';
}

if ( ! empty( $results ) ) : ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>

            <th scope="col">
                Status
            </th>

            <th scope="col">
                Location & Date
            </th>
			<?php if ( isset( $args[ 'has_distance_data' ] ) && $args[ 'has_distance_data' ] ) : ?>
                <th scope="col">
                    Distance
                </th>
			<?php endif; ?>
            <th scope="col">
                Driver
            </th>
            <th scope="col">
                Vehicle
            </th>
            <th scope="col">
                Dimensions
            </th>
            <th scope="col">
            </th>
            <th scope="col">
                Comments
            </th>
            <th scope="col">
                Rating
            </th>
            <th scope="col">
                Notes
            </th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php
		
		foreach ( $results as $row ) :
			// var_dump($row);
			$meta = get_field_value( $row, 'meta_data' );
			$updated_zip_code = get_field_value( $row, 'updated_zipcode' );
			$driver_status = get_field_value( $meta, 'driver_status' );
			$driver_name = get_field_value( $meta, 'driver_name' );
			$languages = get_field_value( $meta, 'languages' );
			$driver_email = get_field_value( $meta, 'driver_email' );
			$show_phone = get_field_value( $meta, 'show_phone' ) ?? 'driver_phone';
			$driver_phone = get_field_value( $meta, $show_phone );
			$home_location = get_field_value( $meta, 'home_location' );
			$city = get_field_value( $meta, 'city' );
			$available_date = get_field_value( $row, 'date_available' );
			
			$vehicle_type     = get_field_value( $meta, 'vehicle_type' );
			$vehicle_year     = get_field_value( $meta, 'vehicle_year' );
			$vehicle_model    = get_field_value( $meta, 'vehicle_model' );
			$vehicle_make     = get_field_value( $meta, 'vehicle_make' );
			$dimensions       = get_field_value( $meta, 'dimensions' );
			$current_location = get_field_value( $meta, 'current_location' );
			$current_city     = get_field_value( $meta, 'current_city' );
			$current_zipcode  = get_field_value( $meta, 'current_zipcode' );
			$latitud          = get_field_value( $meta, 'latitude' );
			$longitude        = get_field_value( $meta, 'longitude' );
			$country          = get_field_value( $meta, 'country' );
			// Convert MySQL datetime format to flatpickr format (m/d/Y H:i) for modal
			$status_date = TMSDriversHelper::convert_mysql_to_flatpickr_date( $available_date );
			$notes            = get_field_value( $meta, 'notes' );
			$last_user_update = get_field_value( $meta, 'last_user_update' );
			
			// Get hold information if driver is on hold
			$hold_info       = null;
			$current_user_id = get_current_user_id();
			$show_phone      = true;
			$show_controls   = true;

            $is_hold = $driver_status === 'on_hold';
			
			if ( $is_hold ) {
				$hold_info = $drivers->get_driver_hold_info( $row[ 'id' ] );
				if ( $hold_info ) {
					// Hide phone number and controls from other users
					$show_phone    = ( $current_user_id == $hold_info[ 'dispatcher_id' ] );
					$show_controls = ( $current_user_id == $hold_info[ 'dispatcher_id' ] );
				}
			}
			
			$payload               = get_field_value( $meta, 'payload' );
			include( get_template_directory() . '/src/template-parts/report/common/driver-capabilities.php' );
			
			
			$date_available = get_field_value( $row, 'date_available' );
			$user_id_added  = get_field_value( $row, 'user_id_added' );
			
			$driver_statistics = $drivers->get_driver_statistics( $row[ 'id' ] );
			
			// Function to determine button color based on value
			$get_button_color = function( $value ) {
				
				if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
					return 'btn-secondary'; // grey
				}
				
				if ( + $value <= 1 ) {
					return 'btn-danger'; // red
				}
				
				if ( + $value <= 4 ) {
					return 'btn-warning'; // orange
				}
				
				if ( + $value > 4 ) {
					return 'btn-success'; // green
				}
				
				return 'btn-secondary'; // default grey
			};
			
			// Function to check if date is valid and not a default/invalid date
			$is_valid_date = function( $date_string ) {
				if ( empty( $date_string ) || $date_string === '0000-00-00 00:00:00' || $date_string === '0000-00-00' ) {
					return false;
				}
				
				$timestamp = strtotime( $date_string );
				if ( $timestamp === false || $timestamp <= 0 ) {
					return false;
				}
				
				// Check for common invalid dates (Unix epoch, negative years, etc.)
				$year = date( 'Y', $timestamp );
				if ( $year < 1900 || $year > 2100 ) {
					return false;
				}
				
				return true;
			};
			
			$date_status = '';
			if ( $is_valid_date( $date_available ) ) {
				$date_status = esc_html( date( 'm/d/Y g:i a', strtotime( $date_available ) ) );
			}
			
			
			$user_recruiter = $helper->get_user_full_name_by_id( $user_id_added );
			$color_initials = $user_recruiter ? get_field( 'initials_color', 'user_' . $user_id_added ) : '#030303';
			$user_recruiter = $user_recruiter ?: [ 'full_name' => 'User not found', 'initials' => 'NF' ];

//			if ( $user_recruiter[ 'initials' ] === 'NF' ) {
//				$drivers->update_user_id_added( $row[ 'id' ], '68' );
//			}
//
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
			// TODO: Remove this after testing
			$class_hide = $row['id'] === '3343' ? 'd-none' : '';
			?>

            <tr class="<?php echo $class_hide; ?>" data-driver-id="<?php echo $row[ 'id' ]; ?>">
                <td style="width: 100px;" class="<?php echo $driver_status ? $driver_status
					: 'text-danger'; ?> driver-status"><?php echo $status_text; ?></td>
				
				<?php
				$updated               = true;
				$updated_text          = '';
				$timestamp             = null;
				$class_update_code     = '';

                $ny_timezone = new DateTimeZone('America/New_York');
                $ny_time = new DateTime('now', $ny_timezone);
                $time = strtotime( $ny_time->format('Y-m-d H:i:s') . TIME_AVAILABLE_DRIVER );
				$updated_zip_code_time = strtotime( $updated_zip_code );
			
            
                if ( ! isset( $updated_zip_code_time ) || empty( $updated_zip_code_time ) ) {
					$updated_text      = 'Update date not set!';
					$class_update_code = 'weiting';
				} else {
					if ( $time >= $updated_zip_code_time ) {
						$class_update_code = 'need_update';
						$updated_text      = date( 'm/d/Y g:i a', $updated_zip_code_time );
					}
				}
				
				
				?>
                <td class="table-column js-location-update <?php echo $class_update_code; ?>"
                    style="font-size: 13px; width: 200px;">
					<?php
					
					$state = explode( ',', $updated_zip_code );
					echo ( isset( $current_location ) && isset( $current_city ) )
						? $current_city . ', ' . $current_location . ' ' : 'Need to set this field ';
					echo $driver_status !== 'available' ? '<br>' . $date_status . '<br>' : '<br>';
					?>
                    
                    <span style="font-size: 10px;"><?php echo $updated_text; ?></span>
                </td>
				
				<?php if ( isset( $args[ 'has_distance_data' ] ) && $args[ 'has_distance_data' ] ) : ?>
                    <td>
						<?php
						// Display distance if available from distance-based search
						if ( isset( $args[ 'id_posts' ] ) && isset( $args[ 'id_posts' ][ $row[ 'id' ] ] ) && is_numeric( $args[ 'id_posts' ][ $row[ 'id' ] ][ 'distance' ] ) ) {
							$driver_data = $args[ 'id_posts' ][ $row[ 'id' ] ];
							if ( defined( 'USE_DRIVER' ) && USE_DRIVER === 'openrouteservices' ) {
								?>
                                <a href="<?php echo esc_url( $driver_data[ 'link' ] ); ?>" target="_blank">
									<?php
									if ( isset( $driver_data[ 'air_mile' ] ) && $driver_data[ 'air_mile' ] ) {
										echo '<span style="color:red;">' . round( $driver_data[ 'distance' ] ) . ' air ml. </span>';
									} else {
										echo round( $driver_data[ 'distance' ] ) . ' ml.';
									}
									?>
                                </a>
								<?php
							} else {
								echo round( $driver_data[ 'distance' ] ) . ' ml.';
							}
						} else {
							// Debug: Show if driver data is missing
							echo '<!-- Debug: No distance data for driver ' . $row[ 'id' ] . ' -->';
						}
						?>
                    </td>
				<?php endif; ?>

                <td>
                    <div class="d-flex  flex-column w-100">
                        <div class="d-flex justify-content-between gap-1">
                            <div>
								<?php echo '(' . $row[ 'id' ] . ') ' . $driver_name; ?>
								<?php echo $icons->get_flags( $languages ); ?>
                            </div>
                            <div class="text-small text-right">
								<?php echo $city . ', ' . $home_location; ?>
                            </div>
                        </div>
                        <span class="text-small js-phone-driver">
							<?php if ( $show_phone ) : ?>
								<?php echo $driver_phone; ?>
							<?php else : ?>
                                <span style="color: #999;">***-***-****</span>
							<?php endif; ?>
						</span>
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
                <td>
                    <div class="d-flex  flex-column">
						<?php echo $dimensions; ?>
                        <span class="text-small">
                            <?php
                            echo $payload;
                            ?> - lbs
                        </span>
                    </div>
                </td>
                <td>
                    <div class="table-tags d-flex flex-wrap" style="min-width: 186px;">
						<?php
						if ( $hold_info ) {
							// Show hold information instead of capabilities
							echo '<div style="font-size: 13px; color: #666; line-height: 1.2;">';
							echo 'On hold by<br>';
							echo '<strong>' . esc_html( $hold_info[ 'dispatcher_name' ] ) . '</strong>';
							echo '</div>';
						} else {
							include( get_template_directory() . '/src/template-parts/report/common/driver-capabilities-display.php' );
						}
						?>
                    </div>
                </td>

                <td style="width: 252px;" class="js-notes-td">
					<?php
					if ( $hold_info ) {
						echo 'Will be available in<br>' . $hold_info[ 'minutes_left' ] . ' min';
					} else {
						echo $notes;
					}
					?>
                </td>

                <td style="width: 86px;">
					<?php if ( $show_controls ) : ?>
                        <button
                                type="button"
                                class="btn w-100 <?php echo $get_button_color( $driver_statistics[ 'rating' ][ 'avg_rating' ] ); ?> btn-sm d-flex align-items-center justify-content-between gap-1 js-driver-rating-btn"
                                data-driver-id="<?php echo $row[ 'id' ]; ?>"
                                data-driver-name="<?php echo esc_attr( $driver_name ); ?>"
                                data-rating="<?php echo $driver_statistics[ 'rating' ][ 'avg_rating' ]; ?>">
							<?php echo $driver_statistics[ 'rating' ][ 'avg_rating' ] > 0
								? $driver_statistics[ 'rating' ][ 'avg_rating' ] : '-'; ?>
                            <svg fill="white" width="12" height="12" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 460 460"
                                 style="enable-background:new 0 0 460 460;" xml:space="preserve">
                                    <path d="M230,0C102.975,0,0,102.975,0,230s102.975,230,230,230s230-102.974,230-230S357.025,0,230,0z M268.333,377.36
                                        c0,8.676-7.034,15.71-15.71,15.71h-43.101c-8.676,0-15.71-7.034-15.71-15.71V202.477c0-8.676,7.033-15.71,15.71-15.71h43.101
                                        c8.676,0,15.71,7.033,15.71,15.71V377.36z M230,157c-21.539,0-39-17.461-39-39s17.461-39,39-39s39,17.461,39,39
                                        S251.539,157,230,157z"></path>
                            </svg>
                        </button>
					<?php else : ?>
                        <span class="btn <?php echo $get_button_color( $driver_statistics[ 'rating' ][ 'avg_rating' ] ); ?> btn-sm d-flex align-items-center justify-content-between gap-1 disabled"
                              style="opacity: 0.5; pointer-events: none;">
						<?php echo $driver_statistics[ 'rating' ][ 'avg_rating' ] > 0
							? $driver_statistics[ 'rating' ][ 'avg_rating' ] : '-'; ?>
                        <svg fill="white" width="12" height="12" version="1.1" xmlns="http://www.w3.org/2000/svg"
                             xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 460 460"
                             style="enable-background:new 0 0 460 460;" xml:space="preserve">
                                    <path d="M230,0C102.975,0,0,102.975,0,230s102.975,230,230,230s230-102.974,230-230S357.025,0,230,0z M268.333,377.36
                                        c0,8.676-7.034,15.71-15.71,15.71h-43.101c-8.676,0-15.71-7.034-15.71-15.71V202.477c0-8.676,7.033-15.71,15.71-15.71h43.101
                                        c8.676,0,15.71,7.033,15.71,15.71V377.36z M230,157c-21.539,0-39-17.461-39-39s17.461-39,39-39s39,17.461,39,39
                                        S251.539,157,230,157z"></path>
                            </svg>
                    </span>
					<?php endif; ?>

                </td>

                <td style="width: 86px;">
					<?php if ( $show_controls ) : ?>
                        <button type="button"
                                class="btn btn-primary w-100 btn-sm d-flex align-items-center justify-content-between gap-1 js-driver-notice-btn"
                                data-driver-id="<?php echo $row[ 'id' ]; ?>"
                                data-driver-name="<?php echo esc_attr( $driver_name ); ?>"
                                data-notice-count="<?php echo $driver_statistics[ 'notice' ][ 'count' ]; ?>">
							<?php echo $driver_statistics[ 'notice' ][ 'count' ] > 0
								? $driver_statistics[ 'notice' ][ 'count' ] : '-'; ?>
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
					<?php else : ?>
                        <span class="btn btn-primary btn-sm d-flex align-items-center justify-content-between gap-1 disabled"
                              style="opacity: 0.5; pointer-events: none;">
						<?php echo $driver_statistics[ 'notice' ][ 'count' ] > 0
							? $driver_statistics[ 'notice' ][ 'count' ] : '-'; ?>
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
                    </span>
					<?php endif; ?>

                </td>

                <td style="width: 92px;">
					<?php if ( $show_controls ) : ?>
                        <div class="d-flex align-items-center justify-content-end">
							
							<?php 
                            if ( $access_hold ): ?>
                                <button class="btn btn-sm d-flex align-items-center justify-content-center js-hold-driver"
                                        data-id="<?php echo $row[ 'id' ]; ?>"
                                        data-dispatcher="<?php echo get_current_user_id(); ?>"
                                        data-hold="null"
                                        style="width: 28px; height: 28px; padding: 0;">
									<?php echo $icons->get_icon_hold(); ?>
                                </button>
							<?php endif; ?>

                            <?php if ( !$is_hold ): ?>
                                <!-- Quick Status Update Button -->
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
                                        data-last-user-update="<?php echo esc_attr( $last_user_update ); ?>"
                                        data-notes="<?php echo esc_attr( $notes ); ?>"
                                        style="width: 28px; height: 28px; padding: 0;"
                                        title="Quick Status Update">
                                    <?php echo $icons->get_icon_edit_2(); ?>
                                </button>
                            <?php endif; ?>
							
							<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown-driver', [
								'id'       => $row[ 'id' ],
								'is_draft' => $is_draft,
							] ) ); ?>
                        </div>
					<?php endif; ?>
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
    <p>No drivers were found.</p>
<?php endif; ?>

<?php get_template_part( TEMPLATE_PATH . 'popups/quick-status-update-modal' ); ?>

    <!-- Driver Rating Popup -->
    <div class="popup" id="driver-rating-popup">
        <div class="my_overlay js-popup-close"></div>
        <div class="popup__wrapper-inner">
            <div class="popup-container">
                <button class="popup-close js-popup-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="popup-content">
                    <h3 class="mb-3">Driver Ratings</h3>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" id="driverRatingName"></h6>
                        <span class="badge bg-primary" id="driverRatingScore"></span>
                    </div>
                    <div id="driverRatingContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <a href="#" class="btn btn-primary" id="driverRatingFullPage" target="_blank">Go to Full
                            Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Notice Popup -->
    <div class="popup" id="driver-notice-popup">
        <div class="my_overlay js-popup-close"></div>
        <div class="popup__wrapper-inner">
            <div class="popup-container">
                <button class="popup-close js-popup-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="popup-content">
                    <h3 class="mb-3">Driver Notices</h3>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" id="driverNoticeName"></h6>
                        <span class="badge bg-primary" id="driverNoticeCount"></span>
                    </div>
                    <div id="driverNoticeContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <a href="#" class="btn btn-primary" id="driverNoticeFullPage" target="_blank">Go to Full
                            Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// TODO need add json $data for import driver ratings
// Import driver ratings from the data array
// if (isset($data['data']['drivers']) && is_array($data['data']['drivers'])) {

// 	echo "<div style='background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd;'>";
// 	echo "<h3>Driver Ratings Import</h3>";

// 	// Get current offset from URL parameter
// 	$current_offset = isset($_GET['import_offset']) ? (int) $_GET['import_offset'] : 0;
// 	$step_size = 500;

// 	// Import current batch
// 	$import_results = import_driver_ratings_step($data['data']['drivers'], $current_offset, $step_size);

// 	echo "<h4>Import Results (Batch " . ($current_offset / $step_size + 1) . "):</h4>";
// 	echo "<ul>";
// 	echo "<li><strong>Total available:</strong> " . $import_results['total_available'] . "</li>";
// 	echo "<li><strong>Current batch:</strong> " . $import_results['total_processed'] . " processed</li>";
// 	echo "<li><strong>Added:</strong> " . $import_results['added'] . "</li>";
// 	echo "<li><strong>Skipped (duplicates):</strong> " . $import_results['skipped'] . "</li>";
// 	echo "<li><strong>Progress:</strong> " . ($current_offset + $import_results['total_processed']) . " / " . $import_results['total_available'] . "</li>";

// 	if (!empty($import_results['errors'])) {
// 		echo "<li><strong>Errors:</strong> " . count($import_results['errors']) . "</li>";
// 		echo "<ul>";
// 		foreach ($import_results['errors'] as $error) {
// 			echo "<li style='color: red;'>" . esc_html($error) . "</li>";
// 		}
// 		echo "</ul>";
// 	}
// 	echo "</ul>";

// 	// Navigation buttons
// 	echo "<div style='margin-top: 20px;'>";

// 	if ($current_offset > 0) {
// 		$prev_offset = max(0, $current_offset - $step_size);
// 		echo "<a href='?import_offset=" . $prev_offset . "' class='button button-secondary' style='margin-right: 10px;'>← Previous Batch</a>";
// 	}

// 	if ($import_results['has_more']) {
// 		$next_offset = $current_offset + $step_size;
// 		echo "<a href='?import_offset=" . $next_offset . "' class='button button-primary'>Next Batch →</a>";
// 	} else {
// 		echo "<span style='color: green; font-weight: bold;'>✓ Import completed!</span>";
// 	}

// 	echo "</div>";

// 	echo "<p><em>Processing in batches of $step_size records. Use navigation buttons to continue.</em></p>";
// 	echo "</div>";
// } else {
// 	echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;'>";
// 	echo "<strong>No driver ratings data found to import.</strong>";
// 	echo "</div>";
// }
?>