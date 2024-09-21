<?php

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;
$reports       = new TMSReports();
$helper        = new TMSReportsHelper();
$states        = $helper->get_states();

$pick_up_location_isset  = false;
$delivery_location_isset = false;

if ( $report_object ) {
	$values = $report_object;
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		if ( ! empty( $values[ 0 ]->pick_up_location ) && ! empty( $values[ 0 ]->delivery_location ) ) {
			$pick_up_location_isset  = json_decode( $values[ 0 ]->pick_up_location, ARRAY_A );
			$delivery_location_isset = json_decode( $values[ 0 ]->delivery_location, ARRAY_A );
		}
	}
}
?>

<form class="js-shipper">
    <h3 class="p-0 display-6 mb-4">Shipper info</h3>

    <div class="row">
		<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
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
            <label for="address" class="form-label">Address</label>
            <div class="form-group position-relative js-container-search">
                <input id="input-name" type="text" name="address"
                       placeholder=""
                       class="form-control js-search-shipper">
                <ul class="my-dropdown-search js-container-search-list">

                </ul>
            </div>

            <div class="result-search js-result-search"></div>

        </div>

        <div class="col-12"></div>

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
            <input type="datetime-local" name="pick_up_date" class="form-control  js-shipper-date">
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="info" class="form-label">Dock, Gate #, or other location specific info</label>
            <input type="text" name="info" class="form-control  js-shipper-info">
        </div>

        <div class="col-12"></div>

        <div class="col-12 mt-3 mb-5 justify-content-start gap-2">
            <button class="btn btn-outline-primary js-add-point js-add-ship">Add point</button>
            <button class="btn btn-primary d-none js-add-point js-end-edit-ship">End edit</button>
        </div>

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
                <div class="col-12 col-md-1 p-0">Actions</div>
            </div>
        </div>

        <div class="col-12 d-flex flex-column gap-2 js-table-shipper mb-5">
			<?php if ( is_array( $pick_up_location_isset ) ):
				foreach ( $pick_up_location_isset as $val ):
					?>
                    <div class="row js-current-shipper card-shipper">
                        <div class="d-none">
                            <input type="hidden" class="js-current-shipper_address_id"
                                   name="pick_up_location_address_id[]" value="<?php echo $val[ 'address_id' ] ?>">
                            <input type="hidden" class="js-current-shipper_address" name="pick_up_location_address[]"
                                   value="<?php echo $val[ 'address' ] ?>">
                            <input type="hidden" class="js-current-shipper_contact" name="pick_up_location_contact[]"
                                   value="<?php echo $val[ 'contact' ] ?>">
                            <input type="hidden" class="js-current-shipper_date" name="pick_up_location_date[]"
                                   value="<?php echo $val[ 'date' ] ?>">
                            <input type="hidden" class="js-current-shipper_info" name="pick_up_location_info[]"
                                   value="<?php echo $val[ 'info' ] ?>">
                            <input type="hidden" class="js-current-shipper_type" name="pick_up_location_type[]"
                                   value="<?php echo $val[ 'type' ] ?>">
                        </div>
                        <div class="col-12 col-md-1">Pick Up</div>
                        <div class="col-12 col-md-2"><?php echo $val[ 'date' ] ?></div>
                        <div class="col-12 col-md-3"><?php echo $val[ 'address' ] ?></div>
                        <div class="col-12 col-md-2"><?php echo $val[ 'contact' ] ?></div>
                        <div class="col-12 col-md-3"><?php echo $val[ 'info' ] ?></div>
                        <div class="col-12 col-md-1 p-0 card-shipper__btns">
                            <button class="additional-card__edit js-edit-ship">
								<?php echo $reports->get_icon_edit(); ?>
                            </button>
                            <button class="additional-card__remove js-remove-ship">
								<?php echo $reports->get_close_icon(); ?>
                            </button>
                        </div>
                    </div>
				<?php endforeach; endif; ?>
	        
	        <?php if ( is_array( $delivery_location_isset ) ):
		        foreach ( $delivery_location_isset as $val ):
			        ?>
                    <div class="row js-current-shipper card-shipper">
                        <div class="d-none">
                            <input type="hidden" class="js-current-shipper_address_id"
                                   name="delivery_location_address_id[]" value="<?php echo $val[ 'address_id' ] ?>">
                            <input type="hidden" class="js-current-shipper_address" name="delivery_location_address[]"
                                   value="<?php echo $val[ 'address' ] ?>">
                            <input type="hidden" class="js-current-shipper_contact" name="delivery_location_contact[]"
                                   value="<?php echo $val[ 'contact' ] ?>">
                            <input type="hidden" class="js-current-shipper_date" name="delivery_location_date[]"
                                   value="<?php echo $val[ 'date' ] ?>">
                            <input type="hidden" class="js-current-shipper_info" name="delivery_location_info[]"
                                   value="<?php echo $val[ 'info' ] ?>">
                            <input type="hidden" class="js-current-shipper_type" name="delivery_location_type[]"
                                   value="<?php echo $val[ 'type' ] ?>">
                        </div>
                        <div class="col-12 col-md-1">Delivery</div>
                        <div class="col-12 col-md-2"><?php echo $val[ 'date' ] ?></div>
                        <div class="col-12 col-md-3"><?php echo $val[ 'address' ] ?></div>
                        <div class="col-12 col-md-2"><?php echo $val[ 'contact' ] ?></div>
                        <div class="col-12 col-md-3"><?php echo $val[ 'info' ] ?></div>
                        <div class="col-12 col-md-1 p-0 card-shipper__btns">
                            <button class="additional-card__edit js-edit-ship">
						        <?php echo $reports->get_icon_edit(); ?>
                            </button>
                            <button class="additional-card__remove js-remove-ship">
						        <?php echo $reports->get_close_icon(); ?>
                            </button>
                        </div>
                    </div>
		        <?php endforeach; endif; ?>
        </div>

    </div>

    <div class="col-12 pl-0" role="presentation">
        <div class="justify-content-start gap-2">
            <button type="button" data-tab-id="pills-load-tab"
                    class="btn btn-dark js-next-tab">Previous
            </button>
            <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                    data-tab-id="pills-documents-tab">Next
            </button>
        </div>
    </div>
</form>