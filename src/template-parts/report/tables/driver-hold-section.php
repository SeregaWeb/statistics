<?php
/**
 * Template for displaying drivers on hold section
 */

$drivers      = new TMSDrivers();
$helper       = new TMSReportsHelper();
$icons        = new TMSReportsIcons();
$driverHelper = new TMSDriversHelper();
global $global_options;
$add_new_load = get_field_value( $global_options, 'add_new_driver' );

// Get all drivers on hold using an optimized method
$hold_drivers = $drivers->get_drivers_on_hold();

if ( ! empty( $hold_drivers ) ) :
	?>
    <div class="hold-drivers-section mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 d-flex align-items-center">
                On hold now
                <span class="badge bg-primary ms-2"
                      style="font-size: 0.75rem;"><?php echo count( $hold_drivers ); ?></span>
            </h6>
            <button class="btn btn-sm btn-outline-secondary js-toggle-hold-section">
                <span class="button-text">Hide</span>
            </button>
        </div>

        <div class="hold-section-content">

            <table class="table mb-5 w-100">
                <thead>
                <tr>
                    <th scope="col">
                        Status
                    </th>

                    <th scope="col">
                        Location & Date
                    </th>
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
				<?php foreach ( $hold_drivers as $driver ) :
					$meta = $driver[ 'meta_data' ];
					$driver_name = get_field_value( $meta, 'driver_name' );
					$languages = get_field_value( $meta, 'languages' );
					$show_phone = get_field_value( $meta, 'show_phone' ) ?? 'driver_phone';
			        $driver_phone = get_field_value( $meta, $show_phone );
					$vehicle_type = get_field_value( $meta, 'vehicle_type' );
					$vehicle_year = get_field_value( $meta, 'vehicle_year' );
					$vehicle_model = get_field_value( $meta, 'vehicle_model' );
					$vehicle_make = get_field_value( $meta, 'vehicle_make' );
					$dimensions = get_field_value( $meta, 'dimensions' );
					$current_location = get_field_value( $meta, 'current_location' );
					$current_city = get_field_value( $meta, 'current_city' );
					$payload = get_field_value( $meta, 'payload' );
					$hold_info = $driver[ 'hold_info' ];
					$current_user_id = get_current_user_id();
					$show_phone = ( $hold_info && $current_user_id == $hold_info[ 'dispatcher_id' ] );
					
					// Get driver statistics for rating
					$driver_statistics = $drivers->get_driver_statistics( $driver[ 'id' ] );
					
					// Function to determine button color based on value
					$get_button_color = function( $value ) {
						if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
							return 'btn-secondary';
						}
						if ( + $value <= 1 ) {
							return 'btn-danger';
						}
						if ( + $value <= 4 ) {
							return 'btn-warning';
						}
						if ( + $value > 4 ) {
							return 'btn-success';
						}
						
						return 'btn-secondary';
					};
					?>
                    <tr>
                        <td style="width: 100px;" class="on_hold driver-status">On hold</td>

                        <td class="table-column js-location-update" style="font-size: 12px; width: 270px;">
							<?php echo ( isset( $current_location ) && isset( $current_city ) )
								? $current_city . ', ' . $current_location . ' ' : 'Need to set this field '; ?>
							<?php echo date( 'm/d/Y g:i a', strtotime( $driver[ 'date_available' ] ) ); ?>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <div>
									<?php echo '(' . $driver[ 'id' ] . ') ' . $driver_name; ?>
									<?php echo $icons->get_flags( $languages ); ?>
                                </div>
                                <span class="text-small">
                            <?php if ( $show_phone ) : ?>
	                            <?php echo $driver_phone; ?>
                            <?php else : ?>
                                <span style="color: #999;">***-***-****</span>
                            <?php endif; ?>
                        </span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
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
                            <div class="d-flex flex-column">
								<?php echo $dimensions; ?>
                                <span class="text-small">
                            <?php echo $payload; ?> - lbs
                        </span>
                            </div>
                        </td>
                        <td>
                            <div class="table-tags d-flex flex-wrap">
								<?php if ( $hold_info ) : ?>
                                    <div style="font-size: 12px; color: #666; line-height: 1.2;">
                                        On hold by<br>
                                        <strong><?php echo esc_html( $hold_info[ 'dispatcher_name' ] ); ?></strong>
                                    </div>
								<?php else : ?>
                                    <div style="font-size: 12px; color: #999;">
                                        Hold expired
                                    </div>
								<?php endif; ?>
                            </div>
                        </td>
                        <td style="width: 282px;">
							<?php if ( $hold_info ) : ?>
                                Will be available in<br><?php echo $hold_info[ 'minutes_left' ]; ?> min
							<?php else : ?>
                                <span style="color: #999;">Expired</span>
							<?php endif; ?>
                        </td>
                        <td style="width: 86px;">
							<?php if ( $show_phone ) : ?>
                                <a target="_blank"
                                   href="<?php echo $add_new_load . '?driver=' . $driver[ 'id' ] . '&tab=pills-driver-stats-tab'; ?>"
                                   class="btn <?php echo $get_button_color( $driver_statistics[ 'rating' ][ 'avg_rating' ] ); ?> btn-sm d-flex align-items-center justify-content-between gap-1">
									<?php echo $driver_statistics[ 'rating' ][ 'avg_rating' ] > 0
										? $driver_statistics[ 'rating' ][ 'avg_rating' ] : '-'; ?>
                                    <svg fill="white" width="12" height="12" version="1.1"
                                         xmlns="http://www.w3.org/2000/svg"
                                         xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                         viewBox="0 0 460 460"
                                         style="enable-background:new 0 0 460 460;" xml:space="preserve">
                            <path d="M230,0C102.975,0,0,102.975,0,230s102.975,230,230,230s230-102.974,230-230S357.025,0,230,0z M268.333,377.36
                                c0,8.676-7.034,15.71-15.71,15.71h-43.101c-8.676,0-15.71-7.034-15.71-15.71V202.477c0-8.676,7.033-15.71,15.71-15.71h43.101
                                c8.676,0,15.71,7.033,15.71,15.71V377.36z M230,157c-21.539,0-39-17.461-39-39s17.461-39,39-39s39,17.461,39,39
                                S251.539,157,230,157z"></path>
                        </svg>
                                </a>
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
							<?php if ( $show_phone ) : ?>
                                <a target="_blank"
                                   href="<?php echo $add_new_load . '?driver=' . $driver[ 'id' ] . '&tab=pills-driver-stats-tab'; ?>"
                                   class="btn btn-primary btn-sm d-flex align-items-center justify-content-between gap-1">
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
                                </a>
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
                        <td style="width: 72px;">
                            <div class="d-flex align-items-center justify-content-end">
                                <button class="btn btn-sm d-flex align-items-center justify-content-center js-hold-driver"
                                        style="width: 28px; height: 28px; padding: 0;"
                                        data-id="<?php echo $driver[ 'id' ]; ?>"
                                        data-dispatcher="<?php echo get_current_user_id(); ?>"
                                        data-hold="<?php echo $hold_info ? $hold_info[ 'dispatcher_id' ] : 'null'; ?>">
                                    <svg style="width: 16px; height: 16px; pointer-events: none; enable-background:new 0 0 511.992 511.992;"
                                         xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                         version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 511.992 511.992"
                                         xml:space="preserve" width="512" height="512" fill="red">
                                <g id="XMLID_806_">
                                    <g id="XMLID_386_">
                                        <path id="XMLID_389_"
                                              d="M511.005,279.646c-4.597-46.238-25.254-89.829-58.168-122.744    c-28.128-28.127-62.556-46.202-98.782-54.239V77.255c14.796-3.681,25.794-17.074,25.794-32.993c0-18.748-15.252-34-34-34h-72    c-18.748,0-34,15.252-34,34c0,15.918,10.998,29.311,25.793,32.993v25.479c-36.115,8.071-70.429,26.121-98.477,54.169    c-6.138,6.138-11.798,12.577-16.979,19.269c-0.251-0.019-0.502-0.038-0.758-0.038H78.167c-5.522,0-10,4.477-10,10s4.478,10,10,10    h58.412c-7.332,12.275-13.244,25.166-17.744,38.436H10c-5.522,0-10,4.477-10,10s4.478,10,10,10h103.184    c-2.882,12.651-4.536,25.526-4.963,38.437H64c-5.522,0-10,4.477-10,10s4.478,10,10,10h44.54    c0.844,12.944,2.925,25.82,6.244,38.437H50c-5.522,0-10,4.477-10,10s4.478,10,10,10h71.166    c9.81,25.951,25.141,50.274,45.999,71.132c32.946,32.946,76.582,53.608,122.868,58.181c6.606,0.652,13.217,0.975,19.819,0.975    c39.022,0,77.548-11.293,110.238-32.581c4.628-3.014,5.937-9.209,2.923-13.837s-9.209-5.937-13.837-2.923    c-71.557,46.597-167.39,36.522-227.869-23.957c-70.962-70.962-70.962-186.425,0-257.388c70.961-70.961,186.424-70.961,257.387,0    c60.399,60.4,70.529,156.151,24.086,227.673c-3.008,4.632-1.691,10.826,2.94,13.833c4.634,3.008,10.826,1.691,13.833-2.941    C504.367,371.396,515.537,325.241,511.005,279.646z M259.849,44.263c0-7.72,6.28-14,14-14h72c7.72,0,14,6.28,14,14s-6.28,14-14,14    h-1.794h-68.413h-1.793C266.129,58.263,259.849,51.982,259.849,44.263z M285.642,99.296V78.263h48.413v20.997    C317.979,97.348,301.715,97.36,285.642,99.296z"/>
                                        <path id="XMLID_391_"
                                              d="M445.77,425.5c-2.64,0-5.21,1.07-7.069,2.93c-1.87,1.86-2.931,4.44-2.931,7.07    c0,2.63,1.061,5.21,2.931,7.07c1.859,1.87,4.43,2.93,7.069,2.93c2.63,0,5.2-1.06,7.07-2.93c1.86-1.86,2.93-4.44,2.93-7.07    c0-2.63-1.069-5.21-2.93-7.07C450.97,426.57,448.399,425.5,445.77,425.5z"/>
                                        <path id="XMLID_394_"
                                              d="M310.001,144.609c-85.538,0-155.129,69.59-155.129,155.129s69.591,155.129,155.129,155.129    s155.129-69.59,155.129-155.129S395.539,144.609,310.001,144.609z M310.001,434.867c-74.511,0-135.129-60.619-135.129-135.129    s60.618-135.129,135.129-135.129S445.13,225.228,445.13,299.738S384.512,434.867,310.001,434.867z"/>
                                        <path id="XMLID_397_"
                                              d="M373.257,222.34l-49.53,49.529c-4.142-2.048-8.801-3.205-13.726-3.205c-4.926,0-9.584,1.157-13.726,3.205    l-22.167-22.167c-3.906-3.905-10.236-3.905-14.143,0c-3.905,3.905-3.905,10.237,0,14.142l22.167,22.167    c-2.049,4.142-3.205,8.801-3.205,13.726c0,17.134,13.939,31.074,31.074,31.074s31.074-13.94,31.074-31.074    c0-4.925-1.157-9.584-3.205-13.726l48.076-48.076v0l1.453-1.453c3.905-3.905,3.905-10.237,0-14.142    S377.164,218.435,373.257,222.34z M310.001,310.812c-6.106,0-11.074-4.968-11.074-11.074s4.968-11.074,11.074-11.074    s11.074,4.968,11.074,11.074S316.107,310.812,310.001,310.812z"/>
                                        <path id="XMLID_398_"
                                              d="M416.92,289.86h-9.265c-5.522,0-10,4.477-10,10s4.478,10,10,10h9.265c5.522,0,10-4.477,10-10    S422.442,289.86,416.92,289.86z"/>
                                        <path id="XMLID_399_"
                                              d="M212.346,289.616h-9.264c-5.522,0-10,4.477-10,10s4.478,10,10,10h9.264c5.522,0,10-4.477,10-10    S217.868,289.616,212.346,289.616z"/>
                                        <path id="XMLID_400_"
                                              d="M310.123,212.083c5.522,0,10-4.477,10-10v-9.264c0-5.523-4.478-10-10-10s-10,4.477-10,10v9.264    C300.123,207.606,304.601,212.083,310.123,212.083z"/>
                                        <path id="XMLID_424_"
                                              d="M309.879,387.393c-5.522,0-10,4.477-10,10v9.264c0,5.523,4.478,10,10,10s10-4.477,10-10v-9.264    C319.879,391.87,315.401,387.393,309.879,387.393z"/>
                                        <path id="XMLID_425_"
                                              d="M10,351.44c-2.63,0-5.21,1.07-7.07,2.93c-1.86,1.86-2.93,4.44-2.93,7.07c0,2.64,1.069,5.21,2.93,7.07    s4.44,2.93,7.07,2.93s5.21-1.07,7.069-2.93c1.86-1.86,2.931-4.44,2.931-7.07s-1.07-5.21-2.931-7.07    C15.21,352.51,12.63,351.44,10,351.44z"/>
                                    </g>
                                </g>
                            </svg>
                                </button>
								<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown-driver', [
									'id'       => $driver[ 'id' ],
									'is_draft' => false,
								] ) ); ?>
                            </div>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?> 