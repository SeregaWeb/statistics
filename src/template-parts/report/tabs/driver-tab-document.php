<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$reports = new TMSReports();
$driver  = new TMSDrivers();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$record_notes        = get_field_value( $meta, 'record_notes' );
$driver_licence_type = get_field_value( $meta, 'driver_licence_type' );
$real_id             = get_field_value( $meta, 'real_id' );

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
$legal_document            = get_field_value( $meta, 'legal_document' );
//
$nationality               = get_field_value( $meta, 'nationality' );
$immigration_letter        = get_field_value( $meta, 'immigration_letter' );
$immigration_expiration    = get_field_value( $meta, 'immigration_expiration' );
$immigration_file          = get_field_value( $meta, 'immigration_file' );
$background_check          = get_field_value( $meta, 'background_check' );
$background_date           = get_field_value( $meta, 'background_date' );
$background_check_file     = get_field_value( $meta, 'background_check_file' );
$canada_transition_proof   = get_field_value( $meta, 'canada_transition_proof' );
$canada_transition_date    = get_field_value( $meta, 'canada_transition_date' );
$canada_transition_file    = get_field_value( $meta, 'canada_transition_file' );
$change_9_training         = get_field_value( $meta, 'change_9_training' );
$change_9_date             = get_field_value( $meta, 'change_9_date' );
$change_9_file             = get_field_value( $meta, 'change_9_file' );
$ic_agreement              = get_field_value( $meta, 'ic_agreement' );
$auto_liability_policy     = get_field_value( $meta, 'auto_liability_policy' );
$auto_liability_expiration = get_field_value( $meta, 'auto_liability_expiration' );
$auto_liability_insurer    = get_field_value( $meta, 'auto_liability_insurer' );
$auto_liability_coi        = get_field_value( $meta, 'auto_liability_coi' );
$motor_cargo_policy        = get_field_value( $meta, 'motor_cargo_policy' );
$motor_cargo_expiration    = get_field_value( $meta, 'motor_cargo_expiration' );
$motor_cargo_insurer       = get_field_value( $meta, 'motor_cargo_insurer' );
$motor_cargo_coi           = get_field_value( $meta, 'motor_cargo_coi' );
$insurance_declaration     = get_field_value( $meta, 'insurance_declaration' );

$insured           = get_field_value( $meta, 'insured' );
$status            = get_field_value( $meta, 'status' );
$cancellation_date = get_field_value( $meta, 'cancellation_date' );
$notes             = get_field_value( $meta, 'notes' );

$hazmat_certificate = get_field_value( $meta, 'hazmat_certificate' );
$driving_record     = get_field_value( $meta, 'driving_record' );
$driver_licence     = get_field_value( $meta, 'driver_licence' );
//$payment_file = get_field_value( $meta, 'payment_file' );
//$payment_file_arr = $driver->process_file_attachment( $payment_file );


$total_images = 0;

$files_check = array();

foreach ( $files_check as $file ) {
	if ( ! empty( $file ) ) {
		$total_images ++;
	}
}

$files = array();

$driverLicenceTypes = [
	'regular'  => 'Regular',
	'cdl'      => 'CDL',
	'enhanced' => 'Enhanced'
];

$legalDocumentTypes = [
	"no-document"                     => "No document",
	"us-passport"                     => "US passport",
	"permanent-residency"             => "Permanent residentship",
	"work-authorization"              => "Work authorization",
	"certificate-of-naturalization"   => "Certificate of naturalization",
	"enhanced-driver-licence-real-id" => "Enhanced driver licence Real ID"
];

$insuredOptions = [
	"business"   => "Business",
	"individual" => "Individual"
];

$statusOptions = [
	"additional-insured" => "Additional insured",
	"company-not-listed" => "Company not listed",
	"cancelled"          => "Cancelled",
	"hold"               => "Hold"
];

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
            <div class="col-12 mb-3">
                <label class="form-label">Driving record</label>
                <input type="file" class="form-control" name="driving_record">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Record notes</label>
                <input type="text" class="form-control" name="record_notes" value="<?php echo $record_notes; ?>">
            </div>

            <!-- Driver licence type -->
            <div class="col-6 mb-3">
                <label class="form-label">Driver licence type</label>
                <select name="driver_licence_type" class="form-control form-select js-licence-type">
					<?php foreach ( $driverLicenceTypes as $value => $label ) : ?>
                        <option value="<?php echo $value; ?>" <?php echo $driver_licence_type === $label ? 'selected'
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
            <div class="col-12 mb-3">
                <label class="form-label">Driver licence</label>
                <input type="file" class="form-control" name="driver_licence">
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Expiration date</label>
                <input type="date" class="form-control" name="driver_licence_expiration"
                       value="<?php echo $driver_licence_expiration; ?>">
            </div>

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
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-hazmat-certificate" type="checkbox" name="hazmat_endorsement"
                           id="hazmat_endorsement" <?php echo $hazmat_endorsement ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="hazmat_endorsement">Hazmat endorsement</label>
                </div>
            </div>

            <div class="col-12  mb-3 js-hazmat-certificate <?php echo $hazmat_endorsement ? '' : 'd-none'; ?>">

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
                    <div class="col-12 col-md-6 ">
                        <label class="form-label">Hazmat certificate</label>
                        <input type="file" class="form-control" name="hazmat_certificate">
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
                           data-target=".js-twic-section" type="checkbox" name="twic"
                           id="twic" <?php echo $twic ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="twic">TWIC</label>
                </div>
            </div>


            <div class="col-12 mb-3 js-twic-section ">
                <label class="form-label">TWIC File</label>
                <input type="file" class="form-control" name="twic_file">
                <label class="form-label">Expiration date</label>
                <input type="date" class="form-control" name="twic_expiration" value="<?php echo $twic_expiration; ?>">
            </div>

            <!-- Legal document type -->
            <div class="col-6 mb-3">
                <label class="form-label">Legal document type</label>
                <select name="legal_document_type" class="form-control form-select js-legal-doc">
					<?php foreach ( $legalDocumentTypes as $value => $label ) : ?>
                        <option value="<?php echo $value ?>" <?php echo $legal_document_type === $value ? 'selected'
							: ''; ?>>
							<?php echo $label ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 mb-3 js-legal-doc-section ">
                <label class="form-label">Legal document</label>
                <input type="file" class="form-control" name="legal_document">
            </div>

            <!-- Insurance -->
            <div class="col-6 mb-3">
                <label class="form-label">Insured</label>
                <select name="insured" class="form-control form-select">
					<?php foreach ( $insuredOptions as $value => $label ) : ?>
                        <option value="<?php echo $value ?>" <?php echo $insured === $value ? 'selected' : ''; ?>>
							<?php echo $label ?>
                        </option>
					<?php endforeach; ?>
                </select>
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
                <input type="date" class="form-control" name="cancellation_date">
            </div>

            <!-- Notes -->
            <div class="col-12 mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes"><?php echo $notes; ?></textarea>
            </div>
        </div>

        <!--        <script>-->
        <!--          document.addEventListener('DOMContentLoaded', function () {-->
        <!--            // Toggle fields based on driver licence type-->
        <!--            document.querySelector('.js-licence-type').addEventListener('change', function () {-->
        <!--              let isCDL = this.value === 'CDL'-->
        <!--              document.querySelector('.js-tanker-endorsement').classList.toggle('d-none', !isCDL)-->
        <!--              document.querySelector('.js-hazmat-endorsement').classList.toggle('d-none', !isCDL)-->
        <!--            })-->
        <!--            -->
        <!--            // Toggle dependent fields-->
        <!--            document.querySelectorAll('.js-toggle').forEach(function (toggle) {-->
        <!--              toggle.addEventListener('change', function () {-->
        <!--                document.querySelector(this.dataset.target).classList.toggle('d-none', !this.checked)-->
        <!--              })-->
        <!--            })-->
        <!--            -->
        <!--            // Toggle cancellation date-->
        <!--            document.querySelector('.js-status').addEventListener('change', function () {-->
        <!--              document.querySelector('.js-cancellation-date').classList.toggle('d-none', this.value !== 'Cancelled')-->
        <!--            })-->
        <!--          })-->
        <!--        </script>-->


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
