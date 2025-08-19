<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$driver   = new TMSDrivers();
$statuses = $driver->status;

$helper = new TMSReportsHelper();
$states = $helper->get_states();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$driver_status    = get_field_value( $meta, 'driver_status' );
$status_date      = get_field_value( $meta, 'status_date' );
$current_location = get_field_value( $meta, 'current_location' );
$current_city     = get_field_value( $meta, 'current_city' );
$current_zipcode  = get_field_value( $meta, 'current_zipcode' );
$latitud          = get_field_value( $meta, 'latitude' );
$longitude        = get_field_value( $meta, 'longitude' );
$country          = get_field_value( $meta, 'country' );

// Get updated_zipcode from main table
$updated_zipcode = get_field_value( $main, 'updated_zipcode' );

?>

<div class="container mt-4 pb-5">
    <h2 class="mb-3">Current location</h2>
	<?php if ( $full_only_view ): ?>
    <form>
		<?php else: ?>
        <form class="<?php echo $post_id ? 'js-update-location-driver' : ''; ?>">
			<?php endif; ?>

			<?php if ( $post_id ): ?>
                <input type="hidden" class="js-id_driver" name="driver_id" value="<?php echo $post_id; ?>">
			<?php endif; ?>


            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Status<span class="required-star text-danger">*</span></label>
                    <select name="driver_status" required class="form-control form-select js-state">
						<?php if ( is_array( $statuses ) ):
							foreach ( $statuses as $key => $status ):
								?>
                                <option value="<?php echo $key; ?>" <?php echo $key === $driver_status ? 'selected'
									: '' ?>><?php echo $status; ?></option>
							<?php
							endforeach;
						endif;
						?>
                    </select>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Date<span class="required-star text-danger">*</span>
                    </label>
                    <input type="text" class="form-control js-new-format-datetime" name="status_date"
                           value="<?php echo $status_date; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">State<span
                                class="required-star text-danger">*</span></label>
                    <select name="current_location" required class="form-control form-select js-state-new">
                        <option value="" disabled selected>Select State</option>
						<?php if ( is_array( $states ) ): ?>
							<?php foreach ( $states as $key => $state ): ?>
                                <option value="<?php echo $key; ?>"
									<?php echo $key === $current_location ? 'selected' : ''; ?>
									<?php echo is_array( $state ) ? 'disabled' : ''; ?>>
									<?php echo is_array( $state ) ? $state[ 0 ] : $state; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">City<span class="required-star text-danger">*</span></label>
                    <input required type="text" class="form-control js-city-new" name="current_city"
                           value="<?php echo $current_city; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Zipcode<span class="required-star text-danger">*</span></label>
                    <input required type="text" class="form-control js-zip-code" name="current_zipcode"
                           value="<?php echo $current_zipcode; ?>">
                </div>

                <input type="hidden" class="js-latitude" value="<?php echo $latitud; ?>" name="latitude">
                <input type="hidden" class="js-longitude" value="<?php echo $longitude; ?>" name="longitude">
                <input type="hidden" class="js-country" name="country">

            </div>

            <div class="row">
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-end gap-1 align-items-center">
                        <p class="m-0">Fill in the rest of the fields by zip code</p>
                        <select name="current_country" required
                                class="form-control form-select w-auto js-search-country">
                            <option value="USA" selected>USA</option>
                            <option value="CAN">Canada</option>
                            <option value="MEX">Mexico</option>
                        </select>
                        <button type="button" class="btn btn-outline-primary js-fill-new-location">Fill out</button>
                    </div>
                </div>
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-end gap-1 align-items-center">
                        <p class="m-0 text-small text-danger">
                            Update without set new location <br>
                            last update <?php echo $updated_zipcode ? date( 'm/d/Y g:i a', strtotime( $updated_zipcode ) ) : 'not set'; ?>
                        </p>
                        <button type="button" class="btn btn-outline-primary js-update-only-date">Update date</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>

        </form>
</div>