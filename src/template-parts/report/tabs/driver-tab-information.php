<?php
$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$reports                     = new TMSReports();
$driver                      = new TMSDrivers();
$vehicle_types               = $driver->vehicle;
$registration_status_options = $driver->registration_status_options;
$registration_types          = $driver->registration_types;
$plates_status_options       = $driver->registration_types;

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$vehicle_type            = get_field_value( $meta, 'vehicle_type' );
$vehicle_make            = get_field_value( $meta, 'vehicle_make' );
$vehicle_model           = get_field_value( $meta, 'vehicle_model' );
$vehicle_year            = get_field_value( $meta, 'vehicle_year' );
$gvwr                    = get_field_value( $meta, 'gvwr' );
$payload                 = get_field_value( $meta, 'payload' );
$dimensions              = get_field_value( $meta, 'dimensions' );
$vin                     = get_field_value( $meta, 'vin' );
$registration_type       = get_field_value( $meta, 'registration_type' );
$registration_status     = get_field_value( $meta, 'registration_status' );
$registration_expiration = get_field_value( $meta, 'registration_expiration' );
$plates                  = get_field_value( $meta, 'plates' );
$plates_status           = get_field_value( $meta, 'plates_status' );
$plates_expiration       = get_field_value( $meta, 'plates_expiration' );
$ppe                     = get_field_value( $meta, 'ppe' );
$e_tracks                = get_field_value( $meta, 'e_tracks' );
$pallet_jack             = get_field_value( $meta, 'pallet_jack' );
$lift_gate               = get_field_value( $meta, 'lift_gate' );
$dolly                   = get_field_value( $meta, 'dolly' );
$load_bars               = get_field_value( $meta, 'load_bars' );
$ramp                    = get_field_value( $meta, 'ramp' );
$printer                 = get_field_value( $meta, 'printer' );
$sleeper                 = get_field_value( $meta, 'sleeper' );

$dimension_arr = explode( '/', $dimensions );
$dimensions_1  = $dimensions ? $dimension_arr[ 0 ] : '';
$dimensions_2  = $dimensions ? $dimension_arr[ 1 ] : '';
$dimensions_3  = $dimensions ? $dimension_arr[ 2 ] : '';

$vehicle_pictures     = get_field_value( $meta, 'vehicle_pictures' );
$vehicle_pictures_arr = false;

$dimensions_pictures     = get_field_value( $meta, 'dimensions_pictures' );
$dimensions_pictures_arr = false;

$registration_file = get_field_value( $meta, 'registration_file' );
$gvwr_placard      = get_field_value( $meta, 'gvwr_placard' );
$ppe_file          = get_field_value( $meta, 'ppe_file' );
$e_tracks_file     = get_field_value( $meta, 'e_tracks_file' );
$pallet_jack_file  = get_field_value( $meta, 'pallet_jack_file' );
$lift_gate_file    = get_field_value( $meta, 'lift_gate_file' );
$dolly_file        = get_field_value( $meta, 'dolly_file' );
$ramp_file         = get_field_value( $meta, 'ramp_file' );
$total_images      = 0;

$files_check = array(
	$registration_file,
	$ppe_file,
	$e_tracks_file,
	$pallet_jack_file,
	$lift_gate_file,
	$dolly_file,
	$ramp_file,
	$gvwr_placard,
);

foreach ( $files_check as $file ) {
	if ( ! empty( $file ) ) {
		$total_images ++;
	}
}


if ( ! empty( $vehicle_pictures ) ) {
	$vehicle_pictures_arr = $driver->get_files( $vehicle_pictures );
	
	$total_images += sizeof( $vehicle_pictures_arr );
}

if ( ! empty( $dimensions_pictures ) ) {
	$dimensions_pictures_arr = $driver->get_files( $dimensions_pictures );
	$total_images            += sizeof( $dimensions_pictures_arr );
}


$gvwr_placard_arr      = $driver->process_file_attachment( $gvwr_placard );
$registration_file_arr = $driver->process_file_attachment( $registration_file );
$ppe_file_arr          = $driver->process_file_attachment( $ppe_file );
$e_tracks_file_arr     = $driver->process_file_attachment( $e_tracks_file );
$pallet_jack_file_arr  = $driver->process_file_attachment( $pallet_jack_file );
$lift_gate_file_arr    = $driver->process_file_attachment( $lift_gate_file );
$dolly_file_arr        = $driver->process_file_attachment( $dolly_file );
$ramp_file_arr         = $driver->process_file_attachment( $ramp_file );


$files = array(
	array(
		'file_arr'       => $gvwr_placard_arr,
		'file'           => $gvwr_placard,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'gvwr-placard',
		'field_name'     => 'gvwr_placard',
		'field_label'    => 'GVWR file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $registration_file_arr,
		'file'           => $registration_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'registration-file',
		'field_name'     => 'registration_file',
		'field_label'    => 'Registration file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $ppe_file_arr,
		'file'           => $ppe_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'ppe-file',
		'field_name'     => 'ppe_file',
		'field_label'    => 'PPE file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $e_tracks_file_arr,
		'file'           => $e_tracks_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'e-tracks-file',
		'field_name'     => 'e_tracks_file',
		'field_label'    => 'E-Tracks file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $pallet_jack_file_arr,
		'file'           => $pallet_jack_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'pallet-jack-file',
		'field_name'     => 'pallet_jack_file',
		'field_label'    => 'Pallet Jack file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $lift_gate_file_arr,
		'file'           => $lift_gate_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'lift-gate-file',
		'field_name'     => 'lift_gate_file',
		'field_label'    => 'Lift Gate file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $dolly_file_arr,
		'file'           => $dolly_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'dolly-file',
		'field_name'     => 'dolly_file',
		'field_label'    => 'Dolly file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	),
	array(
		'file_arr'       => $ramp_file_arr,
		'file'           => $ramp_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'ramp-file',
		'field_name'     => 'ramp_file',
		'field_label'    => 'Ramp file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-vehicle-tab',
	)
);

?>

<div class="container mt-4 pb-5">
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <h2>Owner & Drivers Information</h2>
		<?php if ( $total_images > 2 ): ?>
            <div class=" <?php echo ( $total_images === 3 ) ? 'show-more-hide-desktop' : 'd-flex '; ?>">
                <button class="js-toggle btn btn-primary change-text"
                        data-block-toggle="js-hide-upload-files-container">
                    <span class="unactive-text">Show more images</span>
                    <span class="active-text">Show less images</span>
                </button>
            </div>
		<?php endif; ?>
    </div>
	
	<?php if ( $total_images > 0 ): ?>
        <div class="js-hide-upload-files-container hide-upload-files mb-3" data-class-toggle="hide-upload-files">
            <div class="container-uploads <?php echo $full_only_view ? "read-only" : '' ?>">
				
				<?php if ( ( $vehicle_pictures ) && isset( $post_id ) ): ?>
					<?php
					if ( isset( $vehicle_pictures_arr ) && is_array( $vehicle_pictures_arr ) ):
						foreach ( $vehicle_pictures_arr as $value ):?>
                            <form class="js-remove-one-driver card-upload vehicle-label"
                                  data-tab="pills-driver-vehicle-tab">
                                <a class="view-document" target="_blank"
                                   href="<?php echo $value[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                                <span class="required-label ">Other files</span>
                                <figure class="card-upload__figure">
									<?php
									if ( ! isset( $value[ 'file_name' ] ) ) : ?>
                                        <img class="card-upload__img" src="<?php echo $value[ 'url' ] ?>" alt="img">
									<?php else: ?>
										<?php echo $reports->get_file_icon(); ?>
                                        <p><?php echo $value[ 'file_name' ]; ?></p>
									<?php endif; ?>

                                </figure>
                                <input type="hidden" name="image-id"
                                       value="<?php echo $value[ 'id' ]; ?>">
                                <input type="hidden" name="image-fields" value="vehicle_pictures">
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
								<?php if ( ! $full_only_view ): ?>
                                    <button class="card-upload__btn card-upload__btn--remove" type="submit">
										<?php echo $reports->get_close_icon(); ?>
                                    </button>
								<?php endif; ?>
                                <a class="card-upload__btn card-upload__btn--download" download
                                   href="<?php echo $value[ 'url' ]; ?>">
									<?php echo $reports->get_download_icon(); ?>
                                </a>
                            </form>
						<?php endforeach;
					endif;
					?>
				<?php endif; ?>
				
				<?php if ( ( $dimensions_pictures ) && isset( $post_id ) ): ?>
					<?php
					if ( isset( $dimensions_pictures_arr ) && is_array( $dimensions_pictures_arr ) ):
						foreach ( $dimensions_pictures_arr as $value ):?>
                            <form class="js-remove-one-driver card-upload dimensions-pictures"
                                  data-tab="pills-driver-vehicle-tab">
                                <a class="view-document" target="_blank"
                                   href="<?php echo $value[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                                <span class="required-label ">Dimensions picture</span>
                                <figure class="card-upload__figure">
									<?php
									if ( ! isset( $value[ 'file_name' ] ) ) : ?>
                                        <img class="card-upload__img" src="<?php echo $value[ 'url' ] ?>" alt="img">
									<?php else: ?>
										<?php echo $reports->get_file_icon(); ?>
                                        <p><?php echo $value[ 'file_name' ]; ?></p>
									<?php endif; ?>

                                </figure>
                                <input type="hidden" name="image-id"
                                       value="<?php echo $value[ 'id' ]; ?>">
                                <input type="hidden" name="image-fields" value="dimensions_pictures">
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
								<?php if ( ! $full_only_view ): ?>
                                    <button class="card-upload__btn card-upload__btn--remove" type="submit">
										<?php echo $reports->get_close_icon(); ?>
                                    </button>
								<?php endif; ?>
                                <a class="card-upload__btn card-upload__btn--download" download
                                   href="<?php echo $value[ 'url' ]; ?>">
									<?php echo $reports->get_download_icon(); ?>
                                </a>
                            </form>
						<?php endforeach;
					endif;
					?>
				<?php endif; ?>
				
				<?php
				
				foreach ( $files as $file ):
					if ( isset( $file[ 'file_arr' ] ) && $file[ 'file' ] ):
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/card', 'file', array(
							'file_arr'       => $file[ 'file_arr' ],
							'file'           => $file[ 'file' ],
							'full_only_view' => $file[ 'full_only_view' ],
							'post_id'        => $file[ 'post_id' ],
							'class_name'     => $file[ 'class_name' ],
							'field_name'     => $file[ 'field_name' ],
							'field_label'    => $file[ 'field_label' ],
							'delete_action'  => $file[ 'delete_action' ],
							'active_tab'     => $file[ 'active_tab' ],
						) ) );
					endif;
				endforeach; ?>


            </div>

        </div>
	<?php endif; ?>

    <form class="js-update-driver-information">
		<?php if ( $post_id ): ?>
            <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
		<?php endif; ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Type<span class="required-star text-danger">*</span></label>
                <select name="vehicle_type" required class="form-control form-select">
                    <option value="" disabled selected>Select Vehicle Type</option>
					<?php foreach ( $vehicle_types as $value => $label ): ?>
                        <option value="<?php echo $value; ?>" <?php echo $vehicle_type === $value ? 'selected'
							: ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Make<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="vehicle_make"
                       value="<?php echo $vehicle_make; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Model<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="vehicle_model"
                       value="<?php echo $vehicle_model; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Vehicle Year<span class="required-star text-danger">*</span></label>
                <input required type="number" class="form-control" name="vehicle_year"
                       value="<?php echo $vehicle_year; ?>">
            </div>
			
			<?php if ( $vehicle_type === 'box-truck' || $vehicle_type === 'dry-van' ): ?>
                <div class="col-md-4 mb-3">
                    <label class="form-label">GVWR (Optional)</label>
                    <input type="text" class="form-control" name="gvwr" value="<?php echo $gvwr; ?>">
                </div>

                <div class="col-12 js-add-new-report">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label"><span
                                        style="position: relative; top: -2px;"><?php if ( $gvwr_placard ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                                GVWR Placard
                                (Optional) </label>
							<?php if ( ! $gvwr_placard ): ?>
                                <input type="file" class="form-control js-control-uploads" name="gvwr_placard">
							<?php endif; ?>
                        </div>

                        <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                        </div>
                    </div>
                </div>
			<?php endif; ?>

            <div class="col-12"></div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Payload<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="payload" value="<?php echo $payload; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Dimensions<span class="required-star text-danger">*</span></label>
                <div class="d-flex gap-1">
                    <input required type="text" class="form-control" name="dimensions_1"
                           value="<?php echo $dimensions_1; ?>">
                    <input required type="text" class="form-control" name="dimensions_2"
                           value="<?php echo $dimensions_2; ?>">
                    <input required type="text" class="form-control" name="dimensions_3"
                           value="<?php echo $dimensions_3; ?>">
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">VIN<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="vin" value="<?php echo $vin; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Registration Type<span class="required-star text-danger">*</span></label>

                <select name="registration_type" required class="form-control form-select">
                    <option value="" disabled selected>Select Registration Type</option>
					<?php foreach ( $registration_types as $value => $label ): ?>
                        <option value="<?php echo $value; ?>" <?php echo $registration_type === $value ? 'selected'
							: ''; ?>>
							<?php echo $label; ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Registration Status<span class="required-star text-danger">*</span></label>
                <select name="registration_status" required class="form-control form-select">
                    <option value="" disabled selected>Select Status</option>
					<?php foreach ( $registration_status_options as $value => $label ): ?>
                        <option value="<?php echo $value; ?>" <?php echo $registration_status === $value ? 'selected'
							: ''; ?>>
							<?php echo $label; ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Registration Expiration Date<span
                            class="required-star text-danger">*</span></label>
                <input required type="date" class="form-control" name="registration_expiration"
                       value="<?php echo $registration_expiration; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Plates<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="plates" value="<?php echo $plates; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Plates Status<span class="required-star text-danger">*</span></label>

                <select name="plates_status" required class="form-control form-select">
                    <option value="" disabled selected>Select Plates Status</option>
					<?php foreach ( $plates_status_options as $value => $label ): ?>
                        <option value="<?php echo $value; ?>" <?php echo $plates_status === $value ? 'selected'
							: ''; ?>>
							<?php echo $label; ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Plates Expiration Date<span class="required-star text-danger">*</span></label>
                <input required type="date" class="form-control" name="plates_expiration"
                       value="<?php echo $plates_expiration; ?>">
            </div>

            <div class="col-12"></div>

            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="load_bars"
                           id="load_bars" <?php echo $load_bars ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="load_bars">Load Bars</label>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="printer" id="printer" <?php echo $printer
						? 'checked' : ''; ?>>
                    <label class="form-check-label" for="printer">Printer</label>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="sleeper" id="sleeper" <?php echo $sleeper
						? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sleeper">Sleeper</label>
                </div>
            </div>

            <div class="col-12"></div>

            <div class="col-12">
                <div class="js-add-new-report w-100">
                    <div class="p-0 mb-2 col-12">
                        <p class="h5">Pictures of Vehicle</p>
                        <label for="vehicle_pictures" class="form-label">Attached Files</label>
                        <input type="file" name="vehicle_pictures[]"
                               class="form-control js-control-uploads" multiple>
                    </div>

                    <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="js-add-new-report w-100">
                    <div class="p-0 mb-2 col-12">
                        <p class="h5">Dimensions Pictures</p>
                        <label for="dimensions_pictures" class="form-label">Attached Files</label>
                        <input type="file" name="dimensions_pictures[]"
                               class="form-control js-control-uploads" multiple>
                    </div>

                    <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>

            <div class="col-12 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <span style="position: relative; top: -2px;"><?php if ( $registration_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span
                        <label class="form-label">File
                        </label>
						<?php if ( ! $registration_file ): ?>
                            <input type="file" class="form-control js-control-uploads" name="registration_file">
						<?php endif; ?>
                    </div>

                    <div class="col-12 mb-3 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>

            <div class="col-12 mb-3 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <span style="position: relative; top: -2px;"><?php if ( $ppe_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                            <input class="form-check-input js-toggle <?php echo $ppe_file ? 'disabled' : ''; ?>"
                                   data-block-toggle="js-ppe-driver" type="checkbox"
                                   name="ppe" id="ppe" <?php echo $ppe ? 'checked' : ''; ?>>
                            <label class="form-check-label"
                                   for="ppe">PPE </label>
                        </div>
						
						<?php if ( ! $ppe_file ): ?>
                            <div class="js-ppe-driver <?php echo $ppe ? '' : 'd-none'; ?>">
                                <input type="file" class="form-control js-control-uploads" name="ppe_file">
                            </div>
						<?php endif; ?>
                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>
            <div class="col-12 mb-3 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <span style="position: relative; top: -2px;"><?php if ( $e_tracks_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                            <input class="form-check-input js-toggle <?php echo $e_tracks_file ? 'disabled' : ''; ?>"
                                   data-block-toggle="js-e-tracks-driver"
                                   type="checkbox" name="e_tracks"
                                   id="e_tracks" <?php echo $e_tracks ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="e_tracks">
                                E-Tracks
                            </label>
                        </div>
						
						<?php if ( ! $e_tracks_file ): ?>
                            <div class="js-e-tracks-driver <?php echo $e_tracks ? '' : 'd-none'; ?>">
                                <input type="file" class="form-control js-control-uploads" name="e_tracks_file">
                            </div>
						<?php endif; ?>

                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>
            <div class="col-12 mb-3 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <span style="position: relative; top: -2px;"><?php if ( $pallet_jack_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                            <input class="form-check-input js-toggle <?php echo $pallet_jack_file ? 'disabled' : ''; ?>"
                                   data-block-toggle="js-pallet-jack-driver"
                                   type="checkbox" name="pallet_jack"
                                   id="pallet_jack" <?php echo $pallet_jack ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="pallet_jack">
                                Pallet Jack
                            </label>
                        </div>
						
						<?php if ( ! $pallet_jack_file ): ?>
                            <div class="js-pallet-jack-driver <?php echo $pallet_jack ? '' : 'd-none'; ?>">
                                <input type="file" class="form-control js-control-uploads" name="pallet_jack_file">
                            </div>
						<?php endif; ?>


                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>
            <div class="col-12 mb-3 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <span style="position: relative; top: -2px;"><?php if ( $lift_gate_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                            <input class="form-check-input  js-toggle  <?php echo $lift_gate_file ? 'disabled' : ''; ?>"
                                   data-block-toggle="js-lift-gate-driver"
                                   type="checkbox" name="lift_gate"
                                   id="lift_gate" <?php echo $lift_gate ? 'checked' : ''; ?>>
                            <label class="form-check-label"
                                   for="lift_gate">
                                Lift
                                Gate
                            </label>
                        </div>
						
						<?php if ( ! $lift_gate_file ): ?>
                            <div class="js-lift-gate-driver <?php echo $lift_gate ? '' : 'd-none'; ?>">
                                <input type="file" class="form-control js-control-uploads" name="lift_gate_file">
                            </div>
						<?php endif; ?>


                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>
            <div class="col-12 mb-3 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <span style="position: relative; top: -2px;"><?php if ( $dolly_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                            <input class="form-check-input  js-toggle <?php echo $dolly_file ? 'disabled' : ''; ?>"
                                   data-block-toggle="js-dolly-driver"
                                   type="checkbox" name="dolly" id="dolly" <?php echo $dolly ? 'checked' : ''; ?>>
                            <label class="form-check-label"
                                   for="dolly">
                                Dolly
                            </label>
                        </div>
						
						<?php if ( ! $dolly_file ): ?>
                            <div class="js-dolly-driver <?php echo $dolly ? '' : 'd-none'; ?>">
                                <input type="file" class="form-control js-control-uploads" name="dolly_file">
                            </div>
						<?php endif; ?>


                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>
            <div class="col-12 mb-3 js-add-new-report">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <span style="position: relative; top: -2px;"><?php if ( $ramp_file ): echo $reports->get_icon_uploaded_file(); endif; ?></span>
                            <input class="form-check-input js-toggle <?php echo $ramp_file ? 'disabled' : ''; ?>"
                                   data-block-toggle="js-ramp-driver"
                                   type="checkbox" name="ramp" id="ramp" <?php echo $ramp ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ramp">
                                Ramp
                            </label>
                        </div>
						
						<?php if ( ! $ramp_file ): ?>
                            <div class="js-ramp-driver <?php echo $ramp ? '' : 'd-none'; ?>">
                                <input type="file" class="form-control js-control-uploads" name="ramp_file">
                            </div>
						<?php endif; ?>

                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12" role="presentation">
                    <div class="justify-content-start gap-2">
                        <button type="button" data-tab-id="pills-driver-contact-tab"
                                class="btn btn-dark js-next-tab">Previous
                        </button>
						<?php if ( $full_only_view ): ?>
                            <button type="button" data-tab-id="pills-driver-vehicle-tab"
                                    class="btn btn-primary js-next-tab">Next
                            </button>
						<?php else: ?>
                            <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                    data-tab-id="pills-driver-vehicle-tab">
                                Next
                            </button>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


    </form>
</div>