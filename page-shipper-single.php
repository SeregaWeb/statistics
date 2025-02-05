<?php
/**
 * Template Name: Page shipper single
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


$shipper        = new TMSReportsShipper();
$load           = new TMSReports();
$id_shipper     = get_field_value( $_GET, 'shipper_id' );
$shipper_fields = $shipper->get_shipper_by_id( $id_shipper, ARRAY_A );
$counters       = $load->get_counters_shipper( $id_shipper );
//var_dump($shipper_fields);
if ( ! empty( $shipper_fields ) ) {
	
	if ( isset( $shipper_fields[ 0 ] ) ) {
		$shipper_fields = $shipper_fields[ 0 ];
	}
	
	$full_address = $shipper_fields[ 'address1' ] . ' ' . $shipper_fields[ 'city' ] . ' ' . $shipper_fields[ 'state' ] . ' ' . $shipper_fields[ 'zip_code' ] . ' ' . $shipper_fields[ 'country' ];
}

$TMSUsers = new TMSUsers();

$add_shipper = $TMSUsers->check_user_role_access( array( 'dispatcher', 'dispatcher-tl', 'administrator', 'tracking' ), true );

$remove_shipper = $TMSUsers->check_user_role_access( array( 'administrator' ), true );

?>
    <div class="container-fluid">
    <div class="row">
        <div class="container">
            <div class="row">
				<?php if ( ! empty( $shipper_fields ) ) { ?>

                <div class="col-12 mt-2">
                    <ul class="nav nav-pills mb-2" id="pills-tab" role="tablist">
                        <li class="nav-item w-25" role="presentation">
                            <button class="nav-link w-100 active" id="pills-info-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-info" type="button" role="tab" aria-controls="pills-info"
                                    aria-selected="true">Info
                            </button>
                        </li>
                        <?php if($add_shipper): ?>
                        <li class="nav-item w-25" role="presentation">
                            <button class="nav-link w-100" id="pills-update-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-update" type="button" role="tab"
                                    aria-controls="pills-update" aria-selected="false">Edit
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-info" role="tabpanel"
                         aria-labelledby="pills-info-tab">

                        <div class="col-12 mt-3 mb-3" style="max-width: 964px;">
							<?php
							$user_id          = $shipper_fields[ 'user_id_added' ];
							$user_name        = $shipper->get_user_full_name_by_id( $user_id );
							$date_created     = $shipper_fields[ 'date_created' ];
							$last_user_id     = $shipper_fields[ 'user_id_updated' ];
							$user_update_name = $shipper->get_user_full_name_by_id( $last_user_id );
							$date_updated     = $shipper_fields[ 'date_updated' ];
							
							$date_created = esc_html( date( 'm/d/Y', strtotime( $date_created ) ) );
							$date_updated = esc_html( date( 'm/d/Y', strtotime( $date_updated ) ) );
							?>
                            <h2><?php echo $shipper_fields[ 'shipper_name' ] ?></h2>
                            <ul class="status-list">
                                <li class="status-list__item">
                                    <span class="status-list__label">Address:</span>
                                    <span class="status-list__value"><?php echo $full_address; ?></span>
                                </li>

                                <li class="status-list__item">
                                    <span class="status-list__label">Contact name:</span>
                                    <span class="status-list__value"><?php echo $shipper_fields[ 'contact_first_name' ] . ' ' . $shipper_fields[ 'contact_last_name' ] ?></span>
                                </li>
                                <li class="status-list__item">
                                    <span class="status-list__label">Contact phone:</span>
                                    <span class="status-list__value"><?php echo $shipper_fields[ 'phone_number' ] ?></span>
                                </li>
                                <li class="status-list__item">
                                    <span class="status-list__label">Contact email:</span>
                                    <span class="status-list__value"><?php echo $shipper_fields[ 'email' ] ?></span>
                                </li>

                                <li class="status-list__item">
                                    <span class="status-list__label">Profile created:</span>
                                    <span class="status-list__value"><?php echo $user_name[ 'full_name' ];
										echo ' ' . $date_created; ?></span>
                                </li>
								<?php if ( $shipper_fields[ 'date_created' ] !== $shipper_fields[ 'date_updated' ] ) { ?>
                                    <li class="status-list__item">
                                        <span class="status-list__label">Profile updated:</span>
                                        <span class="status-list__value"><?php echo $user_update_name[ 'full_name' ];
											echo ' ' . $date_updated; ?></span>
                                    </li>
								<?php } ?>
                            </ul>

                        </div>

                        <div class="col-12 mt-3 mb-3" style="max-width: 964px;">
							<?php
							?>
                            <div class="counters-status d-flex gap-2">
								<?php if ( is_array( $counters ) ): ?>
									<?php foreach ( $counters as $key => $count ): ?>
										<?php if ( + $count !== 0 ): ?>
                                            <div class="counters-status-card d-flex align-items-center justify-content-center flex-column">
                                                <span><?php echo $key === 'Pickup' ? 'Pick-up' : 'Delivered'; ?></span>
                                                <strong><?php echo $count; ?></strong>
                                            </div>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
                            </div>
                        </div>
                    </div>
	                
	                <?php if($add_shipper): ?>
                    <div class="tab-pane fade" id="pills-update" role="tabpanel" aria-labelledby="pills-update-tab">
                        <form class="ng-pristine ng-invalid ng-touched js-update-shipper" style="max-width: 964px;">
                            <input type="hidden" name="shipper_id" value="<?php echo $id_shipper; ?>">
                            <div>
                                <h4 class="text">update shipper</h4>
                            </div>
                            <div class="modal-body mb-3 row">
                                <div class="form-group mt-3">
                                    <label for="input-name" class="form-label">
                                        Shipper Name <span class="required-star text-danger">*</span>
                                    </label>
                                    <input id="input-name" type="text" required name="shipper_name" placeholder="Shipper Name"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['shipper_name']); ?>">
                                </div>

                                <div class="form-group mt-3">
                                    <div class="d-flex justify-content-between select-country">
                                        <label class="form-label">Select Country</label>
                                        <div>
                                            <input type="radio" name="country" value="USA" id="country-us"
                                                   class="form-check-input js-country"
							                    <?php echo $shipper_fields['country'] === 'USA' ? 'checked' : ''; ?>>
                                            <label for="country-us" class="form-check-label">USA</label>
                                        </div>
                                        <div>
                                            <input type="radio" name="country" value="Canada" id="country-ca"
                                                   class="form-check-input js-country"
							                    <?php echo $shipper_fields['country'] === 'Canada' ? 'checked' : ''; ?>>
                                            <label for="country-ca" class="form-check-label">Canada</label>
                                        </div>
                                        <div>
                                            <input type="radio" name="country" value="Mexico" id="country-mx"
                                                   class="form-check-input js-country"
							                    <?php echo $shipper_fields['country'] === 'Mexico' ? 'checked' : ''; ?>>
                                            <label for="country-mx" class="form-check-label">Mexico</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <label for="input-address1" class="form-label">Address 1 <span class="required-star text-danger">*</span></label>
                                    <input id="input-address1" type="text" required name="Addr1" placeholder="Address 1"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['address1']); ?>">
                                </div>

                                <div class="form-group mt-3">
                                    <label for="input-address2" class="form-label">Address 2</label>
                                    <input id="input-address2" type="text" name="Addr2" placeholder="Address 2"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['address2']); ?>">
                                </div>

                                <div class="form-group mt-3">
                                    <label for="input-city" class="form-label">City <span class="required-star text-danger">*</span></label>
                                    <input id="input-city" type="text" name="City" required placeholder="City"
                                           class="form-control js-city" value="<?php echo htmlspecialchars($shipper_fields['city']); ?>">
                                </div>

                                <div class="form-group mt-3 col-6 custom-select">
                                    <label class="form-label">State <span class="required-star text-danger">*</span></label>
                                    <select name="State" required class="form-control form-select js-state">
                                        <option value="" disabled>Select State</option>
					                    <?php if (is_array($load->select)): ?>
						                    <?php foreach ($load->select as $key => $state): ?>
                                                <option value="<?php echo $key; ?>" <?php echo $shipper_fields['state'] === $key ? 'selected' : ''; ?>>
								                    <?php echo htmlspecialchars(is_array($state) ? $state[0] : $state); ?>
                                                </option>
						                    <?php endforeach; ?>
					                    <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group mt-3 col-6 js-zip">
                                    <label for="input-zip" class="form-label">Zip Code <span class="required-star text-danger">*</span></label>
                                    <input id="input-zip" type="text" required name="ZipCode" placeholder="Zip Code"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['zip_code']); ?>">
                                </div>

                                <div class="form-group mt-3 col-6">
                                    <label for="input-firstname" class="form-label">Contact Name</label>
                                    <input id="input-firstname" type="text" name="FirstName" placeholder="First Name"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['contact_first_name']); ?>">
                                </div>

                                <div class="form-group mt-3 col-6">
                                    <label class="form-label">&nbsp;</label>
                                    <input id="input-lastname" type="text" name="LastName" placeholder="Last Name"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['contact_last_name']); ?>">
                                </div>

                                <div class="form-group mt-3 col-6">
                                    <label for="input-phone" class="form-label">Phone Number</label>
                                    <input id="input-phone" type="text" name="Phone" placeholder="Phone"
                                           class="form-control js-tel-mask" value="<?php echo htmlspecialchars($shipper_fields['phone_number']); ?>">
                                </div>

                                <div class="form-group mt-3 col-6">
                                    <label for="input-email" class="form-label">Email</label>
                                    <input id="input-email" type="text" name="Email" placeholder="Email"
                                           class="form-control" value="<?php echo htmlspecialchars($shipper_fields['email']); ?>">
                                </div>
                            </div>

                            <div class="modal-footer justify-content-start gap-2">
                                <button type="submit" class="btn btn-outline-primary">End edit <span class="spinner-border spinner-border-sm ms-2" style="display: none;"></span></button>
                            </div>
                        </form>
                    </div>
	                <?php endif; ?>
	                
	                <?php if ($remove_shipper): ?>
                        <div class="col-12 d-flex justify-content-end">
                            <form class="js-delete-shipper text-end">
                                <input type="hidden" name="id" value="<?php echo $id_shipper; ?>">
                                <button class="btn btn-danger mb-1" type="submit">Remove this shipper</button>
                                <p class="text-small">the shipper will be permanently deleted, it will not be possible to return it</p>
                            </form>
                        </div>
	                <?php endif; ?>
                 
					<?php } else { ?>
                        <div class="col-12">
                            <h2 class="mt-3 mb-2">Empty</h2>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add' ) );

get_footer();
