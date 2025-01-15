<?php

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;
$reports       = new TMSReports();
$helper        = new TMSReportsHelper();
$states        = $helper->get_states();

$full_view_only = get_field_value($args, 'full_view_only');

$pick_up_location_isset  = false;
$delivery_location_isset = false;

if ( $report_object ) {
	$values = $report_object;
    $meta = get_field_value($values, 'meta');
	$main = get_field_value($values, 'main');
	
	$pick_up_date        = get_field_value($main, 'pick_up_date');
	$pick_up_date_formatted = date('Y-m-d', strtotime($pick_up_date));
	
	$delivery_date        = get_field_value($main, 'delivery_date');
	$delivery_date_formatted = date('Y-m-d', strtotime($delivery_date));
 
	if ( is_array( $meta ) && sizeof( $meta ) > 0 ) {
        
        $pick_up_location = get_field_value($meta, 'pick_up_location');
        $delivery_location = get_field_value($meta, 'delivery_location');
		$reference_number    = get_field_value($meta, 'reference_number');
		$all_miles    = get_field_value($meta, 'all_miles');
		
		$post_status         = get_field_value( $main, 'status_post' );
  
    
		if ( ! empty( $pick_up_location ) && ! empty( $delivery_location ) ) {
			$pick_up_location_isset  = json_decode( $pick_up_location, ARRAY_A );
			$delivery_location_isset = json_decode( $delivery_location, ARRAY_A );
		}
	}
}
?>

<form class="js-shipper">
    
    
    <h3 class="p-0 display-6 mb-4">Shipper info</h3>

    <div class="row">
        
        <?php if (!$full_view_only): ?>
        
		<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <input type="hidden" name="old_pick_up_location" value='<?php echo $pick_up_location; ?>'>
            <input type="hidden" name="old_delivery_location" value='<?php echo $delivery_location; ?>'>
            <input type="hidden" name="reference_number" value="<?php echo $reference_number; ?>">
		<?php endif; ?>
	       
        <?php if ($post_status): ?>
            <input type="hidden" name="post_status" value="<?php echo $post_status; ?>">
        <?php endif; ?>
        
        <?php if ($delivery_date): ?>
                <input type="hidden" class="js-delivery-date-setup" value="<?php echo $delivery_date_formatted; ?>">
        <?php endif; ?>
        
        <?php if ($pick_up_date): ?>
                <input type="hidden" class="js-pick-up-date-setup" value="<?php echo $pick_up_date_formatted; ?>">
        <?php endif; ?>
        
        <figure>
            <blockquote class="blockquote">
                <p>Enter your Shipper address<span
                            class="required-star text-danger">*</span></p>
            </blockquote>
            <figcaption class="blockquote-footer">
                Find your shipper in our database or <a
                        class="js-open-popup-activator link-primary"
                        href="#popup_add_shipper">click here</a> to add a new
                shipper.
            </figcaption>
        </figure>

        <div class="mb-3 col-12 col-xl-8 js-result-search-wrap">
            <label for="address" class="form-label">Location</label>
            <div class="form-group position-relative js-container-search">
                <input id="input-name" type="text" name="address"
                       placeholder=""
                       autocomplete="off"
                       class="form-control js-search-shipper">
                <ul class="my-dropdown-search js-container-search-list">

                </ul>
            </div>

            <div class="result-search js-result-search"></div>

        </div>

        <div class="col-12">
        
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="stop_type" class="form-label">Stop Type</label>
            <select name="stop_type" class="form-control form-select js-shipper-stop-type">
                <option value="pick_up_location">Pick Up Location</option>
                <option value="delivery_location">Delivery Location</option>
            </select>
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="shipper_name" class="form-label">Contact</label>
            <input type="text" name="shipper_name" class="form-control  js-shipper-contact">
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="pick_up_date" class="form-label">Local Date and time</label>
            <input type="date" name="pick_up_date" class="form-control  js-shipper-date" value="<?php echo $pick_up_date_formatted; ?>">
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="info" class="form-label">Dock, Gate #, or other location specific info</label>
            <input type="text" name="info" class="form-control  js-shipper-info">
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="pick_up_date_time_start" class="form-label">Time start</label>
            <input type="time" name="pick_up_date_time_start" class="form-control  js-shipper-time-start">
        </div>
        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="pick_up_date_time_end" class="form-label">Time end</label>
            <input type="time" name="pick_up_date_time_end" class="form-control  js-shipper-time-end">
        </div>
        
        <div class="col-12"></div>
        
        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <div class="form-check form-switch">
                <input class="form-check-input js-shipper-time-strict" name="pick_up_date_time_strict" type="checkbox" id="flexSwitchCheckDefault">
                <label class="form-check-label" for="flexSwitchCheckDefault">Strict time</label>
            </div>
        </div>
        
        <div class="col-12"></div>
        
        <div class="col-12 mt-3 mb-5 justify-content-start gap-2">
            <button class="btn btn-outline-primary js-add-point js-add-ship">Add stop</button>
            <button class="btn btn-primary d-none js-add-point js-end-edit-ship">End edit</button>
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="all_miles" class="form-label m-0">Enter route length in miles</label>
            <p class="text-small mt-0">Enter mileage to see rate per mile.</p>
            <input value="<?php echo $all_miles; ?>" type="number" step="0.1" name="all_miles" class="form-control">
        </div>
        
        <?php endif; ?>

        <div class="col-12 mt-3 mb-3">
            <h6>Trip Summary</h6>
            <hr class="mt-2 mb-0">
        </div>


        <div class="col-12 d-none d-md-block mb-3">
            <div class="row">
                <div class="col-12 col-md-1">Type</div>
                <div class="col-12 col-md-2">Date</div>
                <div class="col-12 col-md-3">Address</div>
                <div class="col-12 col-md-2">Contact</div>
                <div class="col-12 col-md-3">Notes</div>
	            <?php if (!$full_view_only): ?>
                    <div class="col-12 col-md-1 p-0">Actions</div>
                <?php else:?>
                    <div class="col-12 col-md-1 p-0"></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 d-flex flex-column gap-2 js-table-shipper mb-5">
			<?php if ( is_array( $pick_up_location_isset ) ):
				foreach ( $pick_up_location_isset as $val ):
                    $address_id = get_field_value($val, 'address_id');
                    $address = get_field_value($val, 'address');
                    $contact = get_field_value($val, 'contact');
                    $date = get_field_value($val, 'date');
					$date_format = esc_html( date( 'm/d/Y', strtotime( $date ) ) );
                    $info = get_field_value($val, 'info');
                    $type = get_field_value($val, 'type');
                    $short_address = get_field_value($val, 'short_address');
                    $time_start = get_field_value($val, 'time_start');
                    $time_end = get_field_value($val, 'time_end');
                    $strict_time = get_field_value($val, 'strict_time');
                    
					?>
                    <div class="row js-current-shipper card-shipper">
                        <div class="d-none">
                            <input type="hidden" class="js-current-shipper_address_id"
                                   name="pick_up_location_address_id[]" value="<?php echo $address_id ?>">
                            <input type="hidden" class="js-current-shipper_address" name="pick_up_location_address[]"
                                   value="<?php echo $address ?>">
                            <input type="hidden" class="js-current-shipper_contact" name="pick_up_location_contact[]"
                                   value="<?php echo $contact ?>">
                            <input type="hidden" class="js-current-shipper_short_address" name="pick_up_location_short_address[]"
                                   value="<?php echo $short_address ?>">
                            <input type="hidden" class="js-current-shipper_date" name="pick_up_location_date[]"
                                   value="<?php echo $date ?>">
                            <input type="hidden" class="js-current-shipper_info" name="pick_up_location_info[]"
                                   value="<?php echo $info ?>">
                            <input type="hidden" class="js-current-shipper_type" name="pick_up_location_type[]"
                                   value="<?php echo $type ?>">
                            <input type="hidden" class="js-current-shipper_start" name="pick_up_location_start[]"
                                   value="<?php echo $time_start ?>">
                            <input type="hidden" class="js-current-shipper_end" name="pick_up_location_end[]"
                                   value="<?php echo $time_end ?>">
                            <input type="hidden" class="js-current-shipper_strict" name="pick_up_location_strict[]"
                                   value="<?php echo $strict_time ?>">
                        </div>
                        <div class="col-12 col-md-1">Pick Up</div>
                        <div class="col-12 col-md-2">
                            <div class="d-flex flex-column">
                                <p class="m-0"><?php echo $date_format ?></p>
                                <?php if ($time_start): ?>
                                <span class="small-text">
                                    <?php if (!$strict_time === "" || $strict_time === "false") :
	                                    echo $time_start . ' - ' . $time_end;
                                    else:
	                                    echo $time_start . ' - strict';
                                    endif; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-3"><?php echo $address ?></div>
                        <div class="col-12 col-md-2"><?php echo $contact ?></div>
                        <div class="col-12 col-md-3"><?php echo $info ?></div>
	                    
	                    <?php if (!$full_view_only): ?>
                        
                        <div class="col-12 col-md-1 p-0 card-shipper__btns">
                            <button class="additional-card__edit js-edit-ship">
								<?php echo $reports->get_icon_edit(); ?>
                            </button>
                            <button class="additional-card__remove js-remove-ship">
								<?php echo $reports->get_close_icon(); ?>
                            </button>
                        </div>
                        
                        <?php endif; ?>
                    </div>
				<?php endforeach; endif; ?>
	        
	        <?php if ( is_array( $delivery_location_isset ) ):
		        foreach ( $delivery_location_isset as $val ):
			        
			        $address_id = get_field_value($val, 'address_id');
			        $address = get_field_value($val, 'address');
			        $contact = get_field_value($val, 'contact');
			        $date = get_field_value($val, 'date');
			        $date_format = esc_html( date( 'm/d/Y', strtotime( $date ) ) );
			        $info = get_field_value($val, 'info');
			        $type = get_field_value($val, 'type');
			        $short_address = get_field_value($val, 'short_address');
			        $time_start = get_field_value($val, 'time_start');
			        $time_end = get_field_value($val, 'time_end');
			        $strict_time = get_field_value($val, 'strict_time');
			        ?>
                    <div class="row js-current-shipper card-shipper">
                        <div class="d-none">
                            <input type="hidden" class="js-current-shipper_address_id"
                                   name="delivery_location_address_id[]" value="<?php echo $address_id ?>">
                            <input type="hidden" class="js-current-shipper_address" name="delivery_location_address[]"
                                   value="<?php echo $address ?>">
                            <input type="hidden" class="js-current-shipper_short_address" name="delivery_location_short_address[]"
                                   value="<?php echo $short_address ?>">
                            <input type="hidden" class="js-current-shipper_contact" name="delivery_location_contact[]"
                                   value="<?php echo $contact ?>">
                            <input type="hidden" class="js-current-shipper_date" name="delivery_location_date[]"
                                   value="<?php echo $date ?>">
                            <input type="hidden" class="js-current-shipper_info" name="delivery_location_info[]"
                                   value="<?php echo $info ?>">
                            <input type="hidden" class="js-current-shipper_type" name="delivery_location_type[]"
                                   value="<?php echo $type ?>">
                            <input type="hidden" class="js-current-shipper_start" name="delivery_location_start[]"
                                   value="<?php echo $time_start ?>">
                            <input type="hidden" class="js-current-shipper_end" name="delivery_location_end[]"
                                   value="<?php echo $time_end ?>">
                            <input type="hidden" class="js-current-shipper_strict" name="delivery_location_strict[]"
                                   value="<?php echo $strict_time ?>">
                        </div>
                        <div class="col-12 col-md-1">Delivery</div>
                        <div class="col-12 col-md-2">
                            <div class="d-flex flex-column">
                                <p class="m-0"><?php echo $date_format ?></p>
                                <span class="small-text">
                                    <?php
                                    if (!$strict_time === "" || $strict_time === "false") :
                                        echo $time_start . ' - ' . $time_end;
                                    else:
                                        echo $time_start . ' - strict';
                                    endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3"><?php echo $address ?></div>
                        <div class="col-12 col-md-2"><?php echo $contact ?></div>
                        <div class="col-12 col-md-3"><?php echo $info ?></div>
	                    
	                    <?php if (!$full_view_only): ?>
                        
                        <div class="col-12 col-md-1 p-0 card-shipper__btns">
                            <button class="additional-card__edit js-edit-ship">
						        <?php echo $reports->get_icon_edit(); ?>
                            </button>
                            <button class="additional-card__remove js-remove-ship">
						        <?php echo $reports->get_close_icon(); ?>
                            </button>
                        </div>
                        
                        <?php endif; ?>
                    </div>
		        <?php endforeach; endif; ?>
        </div>

    </div>

    <div class="col-12 pl-0" role="presentation">
        <div class="justify-content-start gap-2">
            <button type="button" data-tab-id="pills-load-tab"
                    class="btn btn-dark js-next-tab">Previous
            </button>
	        
	        <?php if (!$full_view_only): ?>
                <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                        data-tab-id="pills-documents-tab">Next
                </button>
	        <?php else:?>
                <button type="button" data-tab-id="pills-documents-tab"
                        class="btn btn-primary js-next-tab">Next
                </button>
	        <?php endif; ?>
         
         
        </div>
    </div>
</form>