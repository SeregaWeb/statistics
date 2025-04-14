<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$reports = new TMSReports();
$driver  = new TMSDrivers();

$main                      = get_field_value( $object_driver, 'main' );
$meta                      = get_field_value( $object_driver, 'meta' );
$record_notes              = get_field_value( $meta, 'record_notes' );
$driver_licence_type       = get_field_value( $meta, 'driver_licence_type' );
$real_id                   = get_field_value( $meta, 'real_id' );
$driver_licence_expiration = get_field_value( $meta, 'driver_licence_expiration' );
$tanker_endorsement        = get_field_value( $meta, 'tanker_endorsement' );
$hazmat_endorsement        = get_field_value( $meta, 'hazmat_endorsement' );
$hazmat_certificate        = get_field_value( $meta, 'hazmat_certificate' );
$hazmat_expiration         = get_field_value( $meta, 'hazmat_expiration' );
$twic                      = get_field_value( $meta, 'twic' );
$twic_expiration           = get_field_value( $meta, 'twic_expiration' );
$tsa_approved              = get_field_value( $meta, 'tsa_approved' );
$tsa_expiration            = get_field_value( $meta, 'tsa_expiration' );
$legal_document_type       = get_field_value( $meta, 'legal_document_type' );
$nationality               = get_field_value( $meta, 'nationality' );
$immigration_letter        = get_field_value( $meta, 'immigration_letter' );
$immigration_expiration    = get_field_value( $meta, 'immigration_expiration' );
$background_check          = get_field_value( $meta, 'background_check' );
$background_date           = get_field_value( $meta, 'background_date' );
$canada_transition_proof   = get_field_value( $meta, 'canada_transition_proof' );
$canada_transition_date    = get_field_value( $meta, 'canada_transition_date' );
$change_9_training         = get_field_value( $meta, 'change_9_training' );
$change_9_date             = get_field_value( $meta, 'change_9_date' );
$auto_liability_policy     = get_field_value( $meta, 'auto_liability_policy' );
$auto_liability_expiration = get_field_value( $meta, 'auto_liability_expiration' );
$auto_liability_insurer    = get_field_value( $meta, 'auto_liability_insurer' );
$motor_cargo_policy        = get_field_value( $meta, 'motor_cargo_policy' );
$motor_cargo_expiration    = get_field_value( $meta, 'motor_cargo_expiration' );
$motor_cargo_insurer       = get_field_value( $meta, 'motor_cargo_insurer' );
$insurance_declaration     = get_field_value( $meta, 'insurance_declaration' );
$insured                   = get_field_value( $meta, 'insured' );
$status                    = get_field_value( $meta, 'status' );
$cancellation_date         = get_field_value( $meta, 'cancellation_date' );
$notes                     = get_field_value( $meta, 'notes' );
$hazmat_certificate_file   = get_field_value( $meta, 'hazmat_certificate_file' );
$driving_record            = get_field_value( $meta, 'driving_record' );
$driver_licence            = get_field_value( $meta, 'driver_licence' );
$legal_document            = get_field_value( $meta, 'legal_document' );
$twic_file                 = get_field_value( $meta, 'twic_file' );
$tsa_file                  = get_field_value( $meta, 'tsa_file' );
$motor_cargo_coi           = get_field_value( $meta, 'motor_cargo_coi' );
$auto_liability_coi        = get_field_value( $meta, 'auto_liability_coi' );
$ic_agreement              = get_field_value( $meta, 'ic_agreement' );
$change_9_file             = get_field_value( $meta, 'change_9_file' );
$canada_transition_file    = get_field_value( $meta, 'canada_transition_file' );
$immigration_file          = get_field_value( $meta, 'immigration_file' );
$background_file           = get_field_value( $meta, 'background_file' );

// Initialize total images count
$total_images = 0;

// Process each file and store in respective arrays
$hazmat_certificate_file_arr = $driver->process_file_attachment( $hazmat_certificate_file );
$driving_record_arr          = $driver->process_file_attachment( $driving_record );
$driver_licence_arr          = $driver->process_file_attachment( $driver_licence );
$legal_document_arr          = $driver->process_file_attachment( $legal_document );
$twic_file_arr               = $driver->process_file_attachment( $twic_file );
$tsa_file_arr                = $driver->process_file_attachment( $tsa_file );
$motor_cargo_coi_arr         = $driver->process_file_attachment( $motor_cargo_coi );
$auto_liability_coi_arr      = $driver->process_file_attachment( $auto_liability_coi );
$ic_agreement_arr            = $driver->process_file_attachment( $ic_agreement );
$change_9_file_arr           = $driver->process_file_attachment( $change_9_file );
$canada_transition_file_arr  = $driver->process_file_attachment( $canada_transition_file );
$immigration_file_arr        = $driver->process_file_attachment( $immigration_file );
$background_file_arr         = $driver->process_file_attachment( $background_file );

// Files check array
$files_check = array(
	$hazmat_certificate_file_arr,
	$driving_record_arr,
	$driver_licence_arr,
	$legal_document_arr,
	$twic_file_arr,
	$tsa_file_arr,
	$motor_cargo_coi_arr,
	$auto_liability_coi_arr,
	$ic_agreement_arr,
	$change_9_file_arr,
	$canada_transition_file_arr,
	$immigration_file_arr,
	$background_file_arr,
);

// Calculate the total number of files
foreach ( $files_check as $file ) {
	if ( ! empty( $file ) ) {
		$total_images ++;
	}
}

// Create files array for display and handling
$files = array(
	array(
		'file_arr'       => $hazmat_certificate_file_arr,
		'file'           => $hazmat_certificate_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'hazmat-certificate-file',
		'field_name'     => 'hazmat_certificate_file',
		'field_label'    => 'Hazmat certificate',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $driving_record_arr,
		'file'           => $driving_record,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'driving-record',
		'field_name'     => 'driving_record',
		'field_label'    => 'Driving record',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $driver_licence_arr,
		'file'           => $driver_licence,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'driver-licence',
		'field_name'     => 'driver_licence',
		'field_label'    => 'Driver licence',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $legal_document_arr,
		'file'           => $legal_document,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'legal-document',
		'field_name'     => 'legal_document',
		'field_label'    => 'Legal document',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $twic_file_arr,
		'file'           => $twic_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'twic-file',
		'field_name'     => 'twic_file',
		'field_label'    => 'TWIC file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $tsa_file_arr,
		'file'           => $tsa_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'tsa-file',
		'field_name'     => 'tsa_file',
		'field_label'    => 'TSA file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $motor_cargo_coi_arr,
		'file'           => $motor_cargo_coi,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'motor-cargo-coi',
		'field_name'     => 'motor_cargo_coi',
		'field_label'    => 'Motor cargo COI',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $auto_liability_coi_arr,
		'file'           => $auto_liability_coi,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'auto-liability-coi',
		'field_name'     => 'auto_liability_coi',
		'field_label'    => 'Auto liability COI',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $ic_agreement_arr,
		'file'           => $ic_agreement,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'ic-agreement',
		'field_name'     => 'ic_agreement',
		'field_label'    => 'IC agreement',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $change_9_file_arr,
		'file'           => $change_9_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'change-9-file',
		'field_name'     => 'change_9_file',
		'field_label'    => 'Change 9 file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $canada_transition_file_arr,
		'file'           => $canada_transition_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'canada-transition-file',
		'field_name'     => 'canada_transition_file',
		'field_label'    => 'Canada transition file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $immigration_file_arr,
		'file'           => $immigration_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'immigration-file',
		'field_name'     => 'immigration_file',
		'field_label'    => 'Immigration file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $background_file_arr,
		'file'           => $background_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'background-file',
		'field_name'     => 'background_file',
		'field_label'    => 'Background file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
);

$driverLicenceTypes = $driver->driverLicenceTypes;
$legalDocumentTypes = $driver->legalDocumentTypes;
$insuredOptions     = $driver->insuredOptions;
$statusOptions      = $driver->statusOptions;

?>

<div class="container mt-4 pb-5">

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <h2>Documents</h2>
		<?php if ( $total_images > 2 ): ?>
            <div class=" <?php echo ( $total_images === 3 ) ? 'show-more-hide-desktop' : 'd-flex '; ?>">
                <button class="js-toggle btn btn-primary change-text"
                        data-block-toggle="js-hide-upload-doc-container">
                    <span class="unactive-text">Show more images</span>
                    <span class="active-text">Show less images</span>
                </button>
            </div>
		<?php endif; ?>
    </div>
	
	<?php if ( $total_images > 0 ): ?>

        <div class="js-hide-upload-doc-container hide-upload-files mb-3" data-class-toggle="hide-upload-files">
            <div class="container-uploads <?php echo $full_only_view ? "read-only" : '' ?>">
				
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

    <form class="js-driver-document-form">
		
		<?php if ( $post_id ): ?>
            <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
		<?php endif; ?>

        <div class="row">
            <!-- Driving record -->
            <div class="col-12 js-add-new-report">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex align-items-center gap-1">
                            Driving Record
							<?php echo $driving_record ? $reports->get_icon_uploaded_file() : ''; ?>
                        </label>
						<?php if ( ! $driving_record ): ?>
                            <input type="file" class="form-control js-control-uploads" name="driving_record"
                                   value="<?php echo $driving_record; ?>">
						<?php endif; ?>
                    </div>

                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Record notes</label>
                <input type="text" class="form-control" name="record_notes" value="<?php echo $record_notes; ?>">
            </div>

            <!-- Driver licence type -->
            <div class="col-6 mb-3">
                <label class="form-label">Driver licence type</label>
                <select name="driver_licence_type"
                        class="form-control form-select js-show-hidden-values" data-value="cdl"
                        data-selector=".js-cdl-section">
					<?php foreach ( $driverLicenceTypes as $value => $label ) : ?>
                        <option value="<?php echo $value; ?>" <?php echo $driver_licence_type === $value ? 'selected'
							: ''; ?>>
							<?php echo $label; ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <!-- Real ID -->
            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="real_id"
                           id="real_id" <?php echo $real_id ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="real_id">Real ID</label>
                </div>
            </div>

            <!-- Driver licence -->
            <div class="col-12 js-add-new-report">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex align-items-center gap-1">
                            Driver Licence
							<?php echo $driver_licence ? $reports->get_icon_uploaded_file() : ''; ?>
                        </label>
						<?php if ( ! $driver_licence ): ?>
                            <input type="file" class="form-control js-control-uploads" name="driver_licence"
                                   value="<?php echo $driver_licence; ?>">
						<?php endif; ?>
                    </div>

                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Expiration date</label>
                <input type="date" class="form-control" name="driver_licence_expiration"
                       value="<?php echo $driver_licence_expiration; ?>">
            </div>

            <div class="col-12 js-cdl-section <?php echo $driver_licence_type === 'cdl' ? '' : 'd-none' ?>">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tanker_endorsement"
                                   id="tanker_endorsement" <?php echo $tanker_endorsement ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tanker_endorsement">Tanker endorsement</label>
                        </div>
                    </div>

                    <!-- Hazmat endorsement -->
                    <div class="col-12 col-md-6  mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input "
                                   type="checkbox" name="hazmat_endorsement"
                                   id="hazmat_endorsement" <?php echo $hazmat_endorsement ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hazmat_endorsement">Hazmat endorsement</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12  mb-3 js-hazmat-certificate">

                <div class="row">
                    <div class="col-12  mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input js-toggle"
                                   data-block-toggle="js-hazmat-certificate-files" type="checkbox"
                                   name="hazmat_certificate"
                                   id="hazmat_certificate" <?php echo $hazmat_certificate ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hazmat_certificate">Hazmat certificate</label>
                        </div>
                    </div>
                </div>

                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded js-hazmat-certificate-files  <?php echo $hazmat_certificate
					? '' : 'd-none'; ?>">
                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Hazmat Certificate
									<?php echo $hazmat_certificate_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $hazmat_certificate_file ): ?>
                                    <input type="file" class="form-control js-control-uploads"
                                           name="hazmat_certificate_file"
                                           value="<?php echo $hazmat_certificate_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label ">Expiration date</label>
                        <input type="date" class="form-control" name="hazmat_expiration"
                               value="<?php echo $hazmat_expiration; ?>">
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-twic-section" type="checkbox" name="twic"
                           id="twic" <?php echo $twic ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="twic">TWIC</label>
                </div>
            </div>

            <div class="col-12 js-twic-section <?php echo $twic ? '' : 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">

                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    TWIC File
									<?php echo $twic_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $twic_file ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="twic_file"
                                           value="<?php echo $twic_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">Expiration date</label>
                        <input type="date" class="form-control" name="twic_expiration"
                               value="<?php echo $twic_expiration; ?>">
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-tsa_approved-section" type="checkbox" name="tsa_approved"
                           id="tsa_approved" <?php echo $tsa_approved ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="tsa_approved">TSA approved</label>
                </div>
            </div>

            <div class="col-12 js-tsa_approved-section <?php echo $tsa_approved ? '' : 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">

                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    TSA File
									<?php echo $tsa_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $tsa_file ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="tsa_file"
                                           value="<?php echo $tsa_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">Expiration date</label>
                        <input type="date" class="form-control" name="tsa_expiration"
                               value="<?php echo $tsa_expiration; ?>">
                    </div>
                </div>
            </div>

            <!-- Legal document type -->
            <div class="col-12 mb-3">
                <label class="form-label">Legal document type</label>
                <select name="legal_document_type"
                        data-value="us-passport|permanent-residency|work-authorization|certificate-of-naturalization|enhanced-driver-licence-real-id"
                        data-selector=".js-legal-doc-section"
                        class="form-control form-select js-show-hidden-values js-legal-doc">
					<?php foreach ( $legalDocumentTypes as $value => $label ) : ?>
                        <option value="<?php echo $value ?>" <?php echo $legal_document_type === $value ? 'selected'
							: ''; ?>>
							<?php echo $label ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 js-legal-doc-section  <?php echo $legal_document_type !== 'no-document' ? ''
				: 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">
                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Legal document
									<?php echo $legal_document ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $legal_document ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="legal_document"
                                           value="<?php echo $legal_document; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6  ">
                        <label class="form-label">Nationality</label>
                        <input type="text" value="<?php echo $nationality; ?>" class="form-control" name="nationality">
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-immigration-section" type="checkbox" name="immigration_letter"
                           id="immigration-letter" <?php echo $immigration_letter ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="immigration-letter">Immigration letter</label>
                </div>
            </div>

            <div class="col-12 js-immigration-section <?php echo $immigration_letter ? '' : 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">

                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Expiration File
									<?php echo $immigration_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $immigration_file ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="immigration_file"
                                           value="<?php echo $immigration_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">Expiration date</label>
                        <input type="date" class="form-control" name="immigration_expiration"
                               value="<?php echo $immigration_expiration; ?>">
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-background-check" type="checkbox" name="background_check"
                           id="background_check" <?php echo $background_check ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="background_check">Background check</label>
                </div>
            </div>

            <div class="col-12 js-background-check <?php echo $background_check ? '' : 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">

                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Background File
									<?php echo $background_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $background_file ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="background_file"
                                           value="<?php echo $background_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">date</label>
                        <input type="date" class="form-control" name="background_date"
                               value="<?php echo $background_date; ?>">
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-us-canada" type="checkbox" name="canada_transition_proof"
                           id="canada_transition" <?php echo $canada_transition_proof ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="canada_transition">US — Canada transition proof</label>
                </div>
            </div>

            <div class="col-12 js-us-canada <?php echo $canada_transition_proof ? '' : 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">

                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Canada transition File
									<?php echo $canada_transition_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $canada_transition_file ): ?>
                                    <input type="file" class="form-control js-control-uploads"
                                           name="canada_transition_file" value="<?php echo $canada_transition_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">date</label>
                        <input type="date" class="form-control" name="canada_transition_date"
                               value="<?php echo $canada_transition_date; ?>">
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-change-9" type="checkbox" name="change_9_training"
                           id="change-9" <?php echo $change_9_training ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="change-9">Change 9 training</label>
                </div>
            </div>

            <div class="col-12 js-change-9 <?php echo $change_9_training ? '' : 'd-none'; ?>">
                <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">

                    <div class="col-12 col-md-6 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Change 9 File
									<?php echo $change_9_file ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $change_9_file ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="change_9_file"
                                           value="<?php echo $change_9_file; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">date</label>
                        <input type="date" class="form-control" name="change_9_date"
                               value="<?php echo $change_9_date; ?>">
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 js-add-new-report">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex align-items-center gap-1">
                            IC agreement
							<?php echo $ic_agreement ? $reports->get_icon_uploaded_file() : ''; ?>
                        </label>
						<?php if ( ! $ic_agreement ): ?>
                            <input type="file" class="form-control js-control-uploads" name="ic_agreement"
                                   value="<?php echo $ic_agreement; ?>">
						<?php endif; ?>
                    </div>

                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                </div>
            </div>

            <!-- Insurance -->
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Insured</label>
                <select name="insured" class="form-control form-select">
					<?php foreach ( $insuredOptions as $value => $label ) : ?>
                        <option value="<?php echo $value ?>" <?php echo $insured === $value ? 'selected' : ''; ?>>
							<?php echo $label ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 mb-3">
                <h4>Automobile Liability</h4>
                <div class="row">
                    <div class="col-12 col-md-6 mb-2">
                        <label class="form-label">Policy number</label>
                        <input type="text" class="form-control" value="<?php echo $auto_liability_policy; ?>"
                               name="auto_liability_policy">
                    </div>
                    <div class="col-12 col-md-6 mb-2">
                        <label class="form-label">Expiration date</label>
                        <input type="date" class="form-control" name="auto_liability_expiration"
                               value="<?php echo $auto_liability_expiration; ?>">
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <label class="form-label">Insurer</label>
                        <input type="text" class="form-control" value="<?php echo $auto_liability_insurer; ?>"
                               name="auto_liability_insurer">
                    </div>

                    <div class="col-12 col-md-6 mb-2 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    COI
									<?php echo $auto_liability_coi ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $auto_liability_coi ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="auto_liability_coi"
                                           value="<?php echo $auto_liability_coi; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 mb-3">
                <h4>Motor Truck Cargo</h4>

                <div class="row">
                    <div class="col-12 col-md-6 mb-2">
                        <label class="form-label">Policy number</label>
                        <input type="text" class="form-control" value="<?php echo $motor_cargo_policy; ?>"
                               name="motor_cargo_policy">
                    </div>
                    <div class="col-12 col-md-6 mb-2">
                        <label class="form-label">Expiration date</label>
                        <input type="date" class="form-control" name="motor_cargo_expiration"
                               value="<?php echo $motor_cargo_expiration; ?>">
                    </div>
                    <div class="col-12 col-md-6 mb-2">
                        <label class="form-label">Insurer</label>
                        <input type="text" class="form-control" value="<?php echo $motor_cargo_insurer; ?>"
                               name="motor_cargo_insurer">
                    </div>
                    <div class="col-12 col-md-6 mb-2 js-add-new-report">
                        <div class="row">
                            <div class="col-12 ">
                                <label class="form-label d-flex align-items-center gap-1">
                                    Motor Cargo COI
									<?php echo $motor_cargo_coi ? $reports->get_icon_uploaded_file() : ''; ?>
                                </label>
								<?php if ( ! $motor_cargo_coi ): ?>
                                    <input type="file" class="form-control js-control-uploads" name="motor_cargo_coi"
                                           value="<?php echo $motor_cargo_coi; ?>">
								<?php endif; ?>
                            </div>

                            <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Cancellation -->
            <div class="col-6 mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control form-select js-status">
					<?php foreach ( $statusOptions as $value => $label ) : ?>
                        <option value="<?php echo $value ?>" <?php echo $status === $value ? 'selected' : ''; ?>>
							<?php echo $label ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 mb-3 js-cancellation-date ">
                <label class="form-label">Cancellation date</label>
                <input type="date" class="form-control" value="<?php echo $cancellation_date; ?>"
                       name="cancellation_date">
            </div>

            <!-- Notes -->
            <div class="col-12 mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes"><?php echo $notes; ?></textarea>
            </div>
        </div>

        <div class="row">

            <div class="col-12" role="presentation">
                <div class="justify-content-start gap-2">
                    <button type="button" data-tab-id="pills-driver-vehicle-tab"
                            class="btn btn-dark js-next-tab">Previous
                    </button>
					<?php if ( $full_only_view ): ?>
                        <button type="button" data-tab-id="pills-driver-documents-tab"
                                class="btn btn-primary js-next-tab">Next
                        </button>
					<?php else: ?>
                        <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                data-tab-id="pills-driver-documents-tab">
                            Next
                        </button>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>
