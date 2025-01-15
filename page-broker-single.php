<?php
/**
 * Template Name: Page single broker
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


$brokers = new TMSReportsCompany();
$loads   = new TMSReports();


$id_broker   = get_field_value( $_GET, 'broker_id' );
$broker      = $brokers->get_company_by_id( $id_broker, ARRAY_A );
$broker_meta = $brokers->get_all_meta_by_post_id( $id_broker );

if ( ! empty( $broker ) ) {
	
	$get_counters_broker = $loads->get_counters_broker( $id_broker );
	if ( isset( $broker[ 0 ] ) ) {
		$broker = $broker[ 0 ];
	}
	
	$full_address = $broker[ 'address1' ] . ' ' . $broker[ 'city' ] . ' ' . $broker[ 'state' ] . ' ' . $broker[ 'zip_code' ] . ' ' . $broker[ 'country' ];
	
	$platform = $brokers->get_label_by_key( $broker[ 'set_up_platform' ], 'set_up_platform' );

// Декодируем JSON в ассоциативный массив
	$set_up_array          = json_decode( $broker[ 'set_up' ], true );
	$set_up_array_complete = json_decode( $broker[ 'date_set_up_compleat' ], true );
	
	$completed_keys       = array();
	$completed_dated_keys = array();
	
	if ( is_array( $set_up_array ) ) {
		$completed_keys = array_keys( array_filter( $set_up_array, function( $value ) {
			return $value === "completed";
		} ) );
		
		$completed_dated_keys = array_keys( array_filter( $set_up_array, function( $value ) {
			return ! is_null( $value );
		} ) );
	}
	
	$properties = [];
	if ( ! empty( $broker_meta[ 'work_with_odysseia' ] ) ) {
		$properties[] = 'Odysseia';
	}
	if ( ! empty( $broker_meta[ 'work_with_endurance' ] ) ) {
		$properties[] = 'Endurance';
	}
	if ( ! empty( $broker_meta[ 'work_with_martlet' ] ) ) {
		$properties[] = "Martlet";
	}


// Преобразуем массив ключей в строку через запятую
	$completed_keys_string = implode( ', ', $completed_keys );
}

$TMSUsers = new TMSUsers();
$add_broker = $TMSUsers->check_user_role_access( array( 'dispatcher', 'dispatcher-tl', 'administrator', 'billing' ), true );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
					<?php if ( ! empty( $broker ) ) { ?>
                    <div class="col-12 mt-2">
                        <ul class="nav nav-pills mb-2" id="pills-tab" role="tablist">
                            <li class="nav-item w-25" role="presentation">
                                <button class="nav-link w-100 active" id="pills-info-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-info" type="button" role="tab" aria-controls="pills-info"
                                        aria-selected="true">Info
                                </button>
                            </li>
                            <?php if ($add_broker): ?>
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
                            <div class="mt-3 mb-3" style="max-width: 944px;">
								<?php
								$user_id          = $broker[ 'user_id_added' ];
								$user_name        = $brokers->get_user_full_name_by_id( $user_id );
								$date_created     = $broker[ 'date_created' ];
								$last_user_id     = $broker[ 'user_id_updated' ];
								$user_update_name = $brokers->get_user_full_name_by_id( $last_user_id );
								$date_updated     = $broker[ 'date_updated' ];
								
								$date_created = esc_html( date( 'm/d/Y', strtotime( $date_created ) ) );
								$date_updated = esc_html( date( 'm/d/Y', strtotime( $date_updated ) ) );
								?>
                                <h2><?php echo $broker[ 'company_name' ] ?></h2>
                                <ul class="status-list">
                                    <li class="status-list__item">
                                        <span class="status-list__label">Address:</span>
                                        <span class="status-list__value"><?php echo $full_address; ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">MС number:</span>
                                        <span class="status-list__value"><?php echo $broker[ 'mc_number' ] ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">DOT number:</span>
                                        <span class="status-list__value"><?php echo $broker[ 'dot_number' ] ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">Contact name:</span>
                                        <span class="status-list__value"><?php echo $broker[ 'contact_first_name' ] . ' ' . $broker[ 'contact_last_name' ] ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">Contact phone:</span>
                                        <span class="status-list__value"><?php echo $broker[ 'phone_number' ] ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">Contact email:</span>
                                        <span class="status-list__value"><?php echo $broker[ 'email' ] ?></span>
                                    </li>
									
									<?php
									
									
									if ( isset( $broker_meta[ 'days_to_pay' ] ) ) { ?>
                                        <li class="status-list__item">
                                            <span class="status-list__label">Days to pay:</span>
                                            <span class="status-list__value"><?php echo $broker_meta[ 'days_to_pay' ] ?></span>
                                        </li>
									<?php } ?>
									<?php if ( isset( $broker_meta[ 'quick_pay_option' ] ) ) { ?>

                                        <li class="status-list__item">
                                            <span class="status-list__label">Quick pay option:</span>
                                            <span class="status-list__value"><?php echo $broker_meta[ 'quick_pay_option' ] == '1'
													? 'On' : 'Off'; ?></span>
                                        </li>
									
									<?php } ?>
									<?php if ( isset( $broker_meta[ 'quick_pay_percent' ] ) ) { ?>

                                        <li class="status-list__item">
                                            <span class="status-list__label">Quick pay percent:</span>
                                            <span class="status-list__value"><?php echo $broker_meta[ 'quick_pay_percent' ] ?></span>
                                        </li>
									
									<?php } ?>
									<?php if ( isset( $broker_meta[ 'accounting_phone' ] ) ) { ?>

                                        <li class="status-list__item">
                                            <span class="status-list__label">Accounting phone:</span>
                                            <span class="status-list__value"><?php echo $broker_meta[ 'accounting_phone' ] ?></span>
                                        </li>
									
									<?php } ?>
									<?php if ( isset( $broker_meta[ 'accounting_email' ] ) ) { ?>
                                        <li class="status-list__item">
                                            <span class="status-list__label">Accounting email:</span>
                                            <span class="status-list__value"><?php echo $broker_meta[ 'accounting_email' ] ?></span>
                                        </li>
									<?php } ?>
                                    <li class="status-list__item">
                                        <span class="status-list__label">Set-up platform:</span>
                                        <span class="status-list__value"><?php echo $brokers->get_label_by_key( $broker[ 'set_up_platform' ], 'set_up_platform' ); ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">In set-up with:</span>
                                        <span class="status-list__value"><?php echo $completed_keys_string; ?></span>
                                    </li>
                                    <li class="status-list__item">
                                        <span class="status-list__label">Notes:</span>
                                        <span class="status-list__value"><?php echo stripslashes($broker_meta['notes'] ?? ''); ?></span>
                                    </li>

                                    <li class="status-list__item">
                                        <span class="status-list__label">Work with:</span>
                                        <span class="status-list__value"><?php echo implode( ', ', $properties ); ?></span>
                                    </li>

                                    <li class="status-list__item">
                                        <span class="status-list__label">Completed date:</span>
                                        <span class="status-list__value d-flex flex-column">
                                         <?php foreach ( $completed_dated_keys as $key ):
	                                         if ( $set_up_array_complete[ $key ] !== "null" && $set_up_array_complete[ $key ] !== ""  ) {
		                                         $date_set = esc_html( date( 'm/d/Y', strtotime( $set_up_array_complete[ $key ] ) ) );
		                                         ?>
                                                 <span><?php echo $key . ': ' . $date_set ?></span>
	                                         <?php }
                                         endforeach; ?>
                                </span>
                                    </li>

                                    <li class="status-list__item">
                                        <span class="status-list__label">Profile created:</span>
                                        <?php if ($user_name) { ?>
                                        <span class="status-list__value"><?php echo $user_name[ 'full_name' ];
											echo ' ' . $date_created; ?></span>
                                        <?php } else { ?>
                                        <span class="status-list__value">N/A</span>
                                        <?php } ?>
                                    </li>
									<?php if ( $broker[ 'date_created' ] !== $broker[ 'date_updated' ] ) { ?>
                                        <li class="status-list__item">
                                            <span class="status-list__label">Profile updated:</span>
                                            <span class="status-list__value"><?php echo $user_update_name[ 'full_name' ];
												echo ' ' . $date_updated; ?></span>
                                        </li>
									<?php } ?>
                                </ul>

                            </div>

                            <div class="mt-3 mb-3" style="max-width: 944px;">
								<?php
								?>
                                <div class="counters-status d-flex gap-2">
									<?php if ( is_array( $get_counters_broker ) ): ?>
										<?php foreach ( $get_counters_broker as $key => $count ): ?>
											<?php if ( + $count !== 0 ): ?>
                                                <div class="counters-status-card d-flex align-items-center justify-content-center flex-column">
                                                    <span><?php echo $key === 'Others' ? 'In Process' : $key; ?></span>
                                                    <strong><?php echo $count; ?></strong>
                                                </div>
											<?php endif; ?>
										<?php endforeach; ?>
									<?php endif; ?>
                                </div>
                            </div>
                        </div>
	                    
	                    <?php if ($add_broker): ?>
                        <div class="tab-pane fade" id="pills-update" role="tabpanel" aria-labelledby="pills-update-tab">
							
							<?php
							if ( ! $broker || ! isset( $broker ) ) {
								echo '<p>No broker data found.</p>';
								
								return;
							}
							
							$broker_data = $broker;
							
							function fill_field( $field_name, $data, $default = '' ) {
								return isset( $data[ $field_name ] ) ? esc_attr( $data[ $field_name ] ) : $default;
							}
							
							function is_checked( $field_name, $data ) {
								return isset( $data[ $field_name ] ) && $data[ $field_name ] ? 'checked' : '';
							}
							
							?>
                            <form class="ng-pristine ng-invalid ng-touched js-update-company" style="max-width: 944px;">
                                <input type="hidden" name="broker_id" value="<?php echo $id_broker; ?>">
                                <div>
                                    <h4 class="text">Edit Broker</h4>
                                </div>
                                <div class="modal-body mb-5 row">
                                    <div class="form-group mt-3">
                                        <label for="input-name" class="form-label">
                                            Company Name <span class="required-star text-danger">*</span>
                                        </label>
                                        <input id="input-name" type="text" required name="company_name"
                                               placeholder="Company Name"
                                               value="<?php echo fill_field( 'company_name', $broker_data ); ?>"
                                               class="form-control">
                                    </div>

                                    <div class="form-group mt-3">
                                        <div class="d-flex justify-content-between select-country">
                                            <label class="form-label">Select Country</label>
											<?php $countries = [ 'USA', 'Canada', 'Mexico' ]; ?>
											<?php foreach ( $countries as $country ): ?>
                                                <div>
                                                    <input type="radio" name="country" value="<?php echo $country; ?>"
                                                           id="country-<?php echo strtolower( $country ); ?>"
                                                           class="form-check-input js-country"
														<?php echo $broker_data[ 'country' ] === $country ? 'checked'
															: ''; ?>>
                                                    <label for="country-<?php echo strtolower( $country ); ?>"
                                                           class="form-check-label"><?php echo $country; ?></label>
                                                </div>
											<?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-address1" class="form-label">Address 1 <span
                                                    class="required-star text-danger">*</span></label>
                                        <input id="input-address1" type="text" required name="Addr1"
                                               placeholder="Address 1"
                                               value="<?php echo fill_field( 'address1', $broker_data ); ?>"
                                               class="form-control">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-address2" class="form-label">Address 2</label>
                                        <input id="input-address2" type="text" name="Addr2" placeholder="Address 2"
                                               value="<?php echo fill_field( 'address2', $broker_data ); ?>"
                                               class="form-control">
                                    </div>

                                    <div class="form-group mt-3">
                                        <label for="input-city" class="form-label">City <span
                                                    class="required-star text-danger">*</span></label>
                                        <input id="input-city" type="text" name="City" required placeholder="City"
                                               value="<?php echo fill_field( 'city', $broker_data ); ?>"
                                               class="form-control js-city">
                                    </div>

                                    <div class="form-group mt-3 col-6 custom-select">
                                        <label class="form-label">State <span class="required-star text-danger">*</span></label>
                                        <select name="State" required class="form-control form-select js-state">
                                            <option value="" disabled>Select State</option>
											<?php foreach ( $brokers->select as $key => $state ): ?>
                                                <option value="<?php echo esc_attr( $key ); ?>"
													<?php echo $broker_data[ 'state' ] === $key ? 'selected' : ''; ?>>
													<?php echo is_array( $state ) ? esc_html( $state[ 0 ] )
														: esc_html( $state ); ?>
                                                </option>
											<?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group mt-3 col-6 js-zip">
                                        <label for="input-zip" class="form-label">Zip Code <span
                                                    class="required-star text-danger">*</span></label>
                                        <input id="input-zip" type="text" required name="ZipCode" placeholder="Zip Code"
                                               value="<?php echo fill_field( 'zip_code', $broker_data ); ?>"
                                               class="form-control">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-firstname" class="form-label">Contact Name </label>
                                        <input id="input-firstname" type="text" name="FirstName"
                                               placeholder="First Name"
                                               value="<?php echo fill_field( 'contact_first_name', $broker_data ); ?>"
                                               class="form-control">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label class="form-label">&nbsp;</label>
                                        <input id="input-lastname" type="text" name="LastName" placeholder="Last Name"
                                               class="form-control"
                                               value="<?php echo fill_field( 'contact_last_name', $broker_data ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-phone" class="form-label">Phone Number <span
                                                    class="required-star text-danger">*</span></label>
                                        <input id="input-phone" required type="text" name="Phone" placeholder="Phone"
                                               class="form-control js-tel-mask"
                                               value="<?php echo fill_field( 'phone_number', $broker_data ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-email" class="form-label">Email</label>
                                        <input id="input-email" type="text" name="Email" placeholder="Email"
                                               class="form-control"
                                               value="<?php echo fill_field( 'email', $broker_data ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-mcnumber" class="form-label">MC Number <span
                                                    class="required-star text-danger">*</span></label>
                                        <input id="input-mcnumber" required type="text" name="MotorCarrNo"
                                               placeholder="MC Number" class="form-control"
                                               value="<?php echo fill_field( 'mc_number', $broker_data ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="input-dotnumber" class="form-label">DOT Number</label>
                                        <input id="input-dotnumber" type="text" name="DotNo" placeholder="DOT Number"
                                               class="form-control"
                                               value="<?php echo fill_field( 'dot_number', $broker_data ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="set_up" class="form-label">Set up<span
                                                    class="required-star text-danger">*</span></label>
										<?php if ( is_array( $brokers->set_up ) ):
											$data = $broker_data[ 'set_up' ];
											$data = mb_convert_encoding( $data, 'UTF-8', 'auto' );
											$data = json_decode( $data, true );
											?>
                                            <select name="set_up" class="form-control form-select" required>
                                                <option value="">Select set up</option>
												
												<?php foreach ( $brokers->set_up as $key => $val ): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $data[ $loads->project ] === $key
														? 'selected' : ''; ?>>
														<?php echo $val; ?>
                                                    </option>
												<?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="json-set-up"
                                                   value='<?php echo $broker_data[ 'set_up' ]; ?>'>
                                            <input type="hidden" name="json-completed"
                                                   value='<?php echo $broker_data[ 'date_set_up_compleat' ]; ?>'>
                                            <input type="hidden" name="select_project"
                                                   value="<?php echo $loads->project; ?>">
										<?php endif ?>
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="set_up_platform" class="form-label">Set up platform<span
                                                    class="required-star text-danger">*</span></label>
                                        <select name="set_up_platform" class="form-control form-select" required>
                                            <option value="">Select platform</option>
											<?php if ( is_array( $brokers->set_up_platform ) ): ?>
												<?php foreach ( $brokers->set_up_platform as $key => $val ): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo fill_field( 'set_up_platform', $broker_data ) === $key
														? 'selected' : ''; ?>>
														<?php echo $val; ?>
                                                    </option>
												<?php endforeach; ?>
											<?php endif ?>
                                        </select>
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="factoring_broker" class="form-label">Factoring status</label>
                                        <select name="factoring_broker" class="form-control form-select">
                                            <option value="">Select Factoring status</option>
											<?php if ( is_array( $brokers->factoring_broker ) ): ?>
												<?php foreach ( $brokers->factoring_broker as $key => $val ): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo fill_field( 'factoring_broker', $broker_meta ) === $key
														? 'selected' : ''; ?>>
														<?php echo $val; ?>
                                                    </option>
												<?php endforeach; ?>
											<?php endif ?>
                                        </select>
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="accounting-input-phone" class="form-label">Accounting Phone
                                            Number </label>
                                        <input id="accounting-input-phone" type="text" name="accounting_phone"
                                               placeholder="Phone"
                                               class="form-control js-tel-mask"
                                               value="<?php echo fill_field( 'accounting_phone', $broker_meta ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="accounting-input-email" class="form-label">Accounting Email</label>
                                        <input id="accounting-input-email" type="text" name="accounting_email"
                                               placeholder="Email"
                                               class="form-control"
                                               value="<?php echo fill_field( 'accounting_email', $broker_meta ); ?>">
                                    </div>

                                    <div class="form-group mt-3 col-6">
                                        <label for="days-to-pay" class="form-label">Days to pay</label>
                                        <input id="days-to-pay" type="number" name="days_to_pay"
                                               placeholder="Days to pay"
                                               class="form-control"
                                               value="<?php echo fill_field( 'days_to_pay', $broker_meta ); ?>">
                                    </div>

                                    <div class="col-12"></div>
                                    <div class="mb-2 col-12 col-md-6 col-xl-4 mt-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input js-switch-toggle"
                                                   data-toggle="js-quick-actions" <?php echo is_checked( 'quick_pay_option', $broker_meta ); ?>
                                                   name="quick_pay_option" type="checkbox" id="quick_pay_option">
                                            <label class="form-check-label" for="quick_pay_option">Quick Pay
                                                option?</label>
                                        </div>
                                    </div>

                                    <div class="col-12 js-quick-actions <?php echo isset( $broker_meta[ 'quick_pay_option' ] ) && $broker_meta[ 'quick_pay_option' ]
										? '' : 'd-none'; ?>">
                                        <div class="row">
                                            <div class="form-group mt-3 col-6">
                                                <label for="quick_pay_percent" class="form-label">Quick pay
                                                    percent</label>
                                                <div class="input-group mt-3">
                                                    <span class="input-group-text">%</span>
                                                    <input id="quick_pay_percent" type="number" name="quick_pay_percent" step="0.1"
                                                           placeholder=""
                                                           class="form-control"
                                                           value="<?php echo fill_field( 'quick_pay_percent', $broker_meta ); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="input-group mt-3">
                                    <span class="input-group-text">Notes</span>
                                    <textarea class="form-control" aria-label="With textarea"
                                              name="notes"><?php echo fill_field( 'notes', $broker_meta ); ?></textarea>
                                </div>

                                <div class="form-group d-flex gap-2 mt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="work_with_odysseia"
                                               id="odysseia"
											<?php echo is_checked( 'work_with_odysseia', $broker_meta ); ?>>
                                        <label class="form-check-label" for="odysseia">Odysseia</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="work_with_endurance"
                                               id="endurance"
											<?php echo is_checked( 'work_with_endurance', $broker_meta ); ?>>
                                        <label class="form-check-label" for="endurance">Endurance</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="work_with_martlet"
                                               id="martlet"
											<?php echo is_checked( 'work_with_martlet', $broker_meta ); ?>>
                                        <label class="form-check-label" for="martlet">Martlet</label>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button class="btn btn-primary" type="submit">End edit</button>
                                </div>
                        </form>


                    </div>
                    <?php endif; ?>

                </div>
				
				
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
