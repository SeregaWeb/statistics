<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$reports  = new TMSReports();
$driver   = new TMSDrivers();
$TMSUsers = new TMSUsers();

$main                                  = get_field_value( $object_driver, 'main' );
$meta                                  = get_field_value( $object_driver, 'meta' );
$record_notes                          = get_field_value( $meta, 'record_notes' );
$driver_licence_type                   = get_field_value( $meta, 'driver_licence_type' );
$real_id                               = get_field_value( $meta, 'real_id' );
$driver_licence_expiration             = get_field_value( $meta, 'driver_licence_expiration' );
$tanker_endorsement                    = get_field_value( $meta, 'tanker_endorsement' );
$hazmat_endorsement                    = get_field_value( $meta, 'hazmat_endorsement' );
$hazmat_certificate                    = get_field_value( $meta, 'hazmat_certificate' );
$hazmat_expiration                     = get_field_value( $meta, 'hazmat_expiration' );
$twic                                  = get_field_value( $meta, 'twic' );
$twic_expiration                       = get_field_value( $meta, 'twic_expiration' );
$tsa_approved                          = get_field_value( $meta, 'tsa_approved' );
$tsa_expiration                        = get_field_value( $meta, 'tsa_expiration' );
$legal_document_type                   = get_field_value( $meta, 'legal_document_type' );
$nationality                           = get_field_value( $meta, 'nationality' );
$immigration_letter                    = get_field_value( $meta, 'immigration_letter' );
$background_check                      = get_field_value( $meta, 'background_check' );
$background_date                       = get_field_value( $meta, 'background_date' );
$canada_transition_proof               = get_field_value( $meta, 'canada_transition_proof' );
$canada_transition_date                = get_field_value( $meta, 'canada_transition_date' );
$change_9_training                     = get_field_value( $meta, 'change_9_training' );
$change_9_date                         = get_field_value( $meta, 'change_9_date' );
$martlet_ic_agreement                  = get_field_value( $meta, 'martlet_ic_agreement' );
$endurance_ic_agreement                = get_field_value( $meta, 'endurance_ic_agreement' );
$martlet_ic_agreement_on               = get_field_value( $meta, 'martlet_ic_agreement_on' );
$endurance_ic_agreement_on             = get_field_value( $meta, 'endurance_ic_agreement_on' );
$auto_liability_policy                 = get_field_value( $meta, 'auto_liability_policy' );
$auto_liability_expiration             = get_field_value( $meta, 'auto_liability_expiration' );
$auto_liability_insurer                = get_field_value( $meta, 'auto_liability_insurer' );
$motor_cargo_policy                    = get_field_value( $meta, 'motor_cargo_policy' );
$motor_cargo_expiration                = get_field_value( $meta, 'motor_cargo_expiration' );
$motor_cargo_insurer                   = get_field_value( $meta, 'motor_cargo_insurer' );
$insurance_declaration                 = get_field_value( $meta, 'insurance_declaration' );
$insured                               = get_field_value( $meta, 'insured' );
$status                                = get_field_value( $meta, 'status' );
$cancellation_date                     = get_field_value( $meta, 'cancellation_date' );
$notes                                 = get_field_value( $meta, 'notes' );
$hazmat_certificate_file               = get_field_value( $meta, 'hazmat_certificate_file' );
$driving_record                        = get_field_value( $meta, 'driving_record' );
$driver_licence                        = get_field_value( $meta, 'driver_licence' );
$legal_document                        = get_field_value( $meta, 'legal_document' );
$twic_file                             = get_field_value( $meta, 'twic_file' );
$tsa_file                              = get_field_value( $meta, 'tsa_file' );
$motor_cargo_coi                       = get_field_value( $meta, 'motor_cargo_coi' );
$auto_liability_coi                    = get_field_value( $meta, 'auto_liability_coi' );
$martlet_coi                           = get_field_value( $meta, 'martlet_coi' );
$endurance_coi                         = get_field_value( $meta, 'endurance_coi' );
$martlet_coi_on                        = get_field_value( $meta, 'martlet_coi_on' );
$endurance_coi_on                      = get_field_value( $meta, 'endurance_coi_on' );
$ic_agreement                          = get_field_value( $meta, 'ic_agreement' );
$change_9_file                         = get_field_value( $meta, 'change_9_file' );
$canada_transition_file                = get_field_value( $meta, 'canada_transition_file' );
$immigration_file                      = get_field_value( $meta, 'immigration_file' );
$background_file                       = get_field_value( $meta, 'background_file' );
$legal_document_expiration             = get_field_value( $meta, 'legal_document_expiration' );
$team_driver                           = get_field_value( $meta, 'team_driver_enabled' );
$team_driver_driving_record            = get_field_value( $meta, 'team_driver_driving_record' );
$record_notes_team_driver              = get_field_value( $meta, 'record_notes_team_driver' );
$driver_licence_type_team_driver       = get_field_value( $meta, 'driver_licence_type_team_driver' );
$real_id_team_driver                   = get_field_value( $meta, 'real_id_team_driver' );
$driver_licence_team_driver            = get_field_value( $meta, 'driver_licence_team_driver' );
$tanker_endorsement_team_driver        = get_field_value( $meta, 'tanker_endorsement_team_driver' );
$hazmat_endorsement_team_driver        = get_field_value( $meta, 'hazmat_endorsement_team_driver' );
$driver_licence_expiration_team_driver = get_field_value( $meta, 'driver_licence_expiration_team_driver' );
$immigration_file_team_driver          = get_field_value( $meta, 'immigration_file_team_driver' );
$immigration_letter_team_driver        = get_field_value( $meta, 'immigration_letter_team_driver' );
$legal_document_team_driver            = get_field_value( $meta, 'legal_document_team_driver' );
$legal_document_expiration_team_driver = get_field_value( $meta, 'legal_document_expiration_team_driver' );
$nationality_team_driver               = get_field_value( $meta, 'nationality_team_driver' );
$legal_document_type_team_driver       = get_field_value( $meta, 'legal_document_type_team_driver' );
$canada_transition_file_team_driver    = get_field_value( $meta, 'canada_transition_file_team_driver' );
$canada_transition_date_team_driver    = get_field_value( $meta, 'canada_transition_date_team_driver' );
$canada_transition_proof_team_driver   = get_field_value( $meta, 'canada_transition_proof_team_driver' );
$background_check_team_driver          = get_field_value( $meta, 'background_check_team_driver' );
$background_file_team_driver           = get_field_value( $meta, 'background_file_team_driver' );
$background_date_team_driver           = get_field_value( $meta, 'background_date_team_driver' );
$change_9_file_team_driver             = get_field_value( $meta, 'change_9_file_team_driver' );
$change_9_date_team_driver             = get_field_value( $meta, 'change_9_date_team_driver' );
$change_9_training_team_driver         = get_field_value( $meta, 'change_9_training_team_driver' );
$martlet_coi_expired_date             = get_field_value( $meta, 'martlet_coi_expired_date' );
$endurance_coi_expired_date            = get_field_value( $meta, 'endurance_coi_expired_date' );
$interview_martlet                = get_field_value( $meta, 'interview_martlet' );
$interview_endurance               = get_field_value( $meta, 'interview_endurance' );
// Initialize total images count
$total_images = 0;

// Process each file and store in respective arrays
$hazmat_certificate_file_arr            = $driver->process_file_attachment( $hazmat_certificate_file );
$driving_record_arr                     = $driver->process_file_attachment( $driving_record );
$driver_licence_arr                     = $driver->process_file_attachment( $driver_licence );
$legal_document_arr                     = $driver->process_file_attachment( $legal_document );
$twic_file_arr                          = $driver->process_file_attachment( $twic_file );
$tsa_file_arr                           = $driver->process_file_attachment( $tsa_file );
$motor_cargo_coi_arr                    = $driver->process_file_attachment( $motor_cargo_coi );
$auto_liability_coi_arr                 = $driver->process_file_attachment( $auto_liability_coi );
$ic_agreement_arr                       = $driver->process_file_attachment( $ic_agreement );
$martlet_ic_agreement_arr               = $driver->process_file_attachment( $martlet_ic_agreement );
$endurance_ic_agreement_arr             = $driver->process_file_attachment( $endurance_ic_agreement );
$martlet_coi_arr                        = $driver->process_file_attachment( $martlet_coi );
$endurance_coi_arr                      = $driver->process_file_attachment( $endurance_coi );
$change_9_file_arr                      = $driver->process_file_attachment( $change_9_file );
$canada_transition_file_arr             = $driver->process_file_attachment( $canada_transition_file );
$immigration_file_arr                   = $driver->process_file_attachment( $immigration_file );
$background_file_arr                    = $driver->process_file_attachment( $background_file );
$team_driver_driving_record_arr         = $driver->process_file_attachment( $team_driver_driving_record );
$driver_licence_team_driver_arr         = $driver->process_file_attachment( $driver_licence_team_driver );
$immigration_file_team_driver_arr       = $driver->process_file_attachment( $immigration_file_team_driver );
$legal_document_team_driver_arr         = $driver->process_file_attachment( $legal_document_team_driver );
$canada_transition_file_team_driver_arr = $driver->process_file_attachment( $canada_transition_file_team_driver );
$background_file_team_driver_arr        = $driver->process_file_attachment( $background_file_team_driver );
$change_9_file_team_driver_arr          = $driver->process_file_attachment( $change_9_file_team_driver );
$interview_martlet_arr                  = $driver->process_file_attachment( $interview_martlet );
$interview_endurance_arr                = $driver->process_file_attachment( $interview_endurance );


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
	$martlet_ic_agreement_arr,
	$endurance_ic_agreement_arr,
	$martlet_coi_arr,
	$endurance_coi_arr,
	$team_driver_driving_record_arr,
	$driver_licence_team_driver_arr,
	$immigration_file_team_driver_arr,
	$legal_document_team_driver_arr,
	$canada_transition_file_team_driver_arr,
	$background_file_team_driver_arr,
	$change_9_file_team_driver_arr,

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
		'file_arr'       => $martlet_ic_agreement_arr,
		'file'           => $martlet_ic_agreement,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'martlet-ic-agreement',
		'field_name'     => 'martlet_ic_agreement',
		'field_label'    => 'Martlet Express IC agreement',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $endurance_ic_agreement_arr,
		'file'           => $endurance_ic_agreement,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'endurance-ic-agreement',
		'field_name'     => 'endurance_ic_agreement',
		'field_label'    => 'Endurance Transport IC agreement',
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
	array(
		'file_arr'       => $martlet_coi_arr,
		'file'           => $martlet_coi,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'martlet-coi',
		'field_name'     => 'martlet_coi',
		'field_label'    => 'Martlet Express COI',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $endurance_coi_arr,
		'file'           => $endurance_coi,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'endurance-coi',
		'field_name'     => 'endurance_coi',
		'field_label'    => 'Endurance Transport COI',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $team_driver_driving_record_arr,
		'file'           => $team_driver_driving_record,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'team-driver-driving-record',
		'field_name'     => 'team_driver_driving_record',
		'field_label'    => 'Team driver driving record',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $driver_licence_team_driver_arr,
		'file'           => $driver_licence_team_driver,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'driver-licence-team-driver',
		'field_name'     => 'driver_licence_team_driver',
		'field_label'    => 'Driver Licence (Team driver)',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $immigration_file_team_driver_arr,
		'file'           => $immigration_file_team_driver,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'immigration-file-team-driver',
		'field_name'     => 'immigration_file_team_driver',
		'field_label'    => 'Immigration file (Team driver)',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $legal_document_team_driver_arr,
		'file'           => $legal_document_team_driver,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'legal-document-team-driver',
		'field_name'     => 'legal_document_team_driver',
		'field_label'    => 'Legal document (Team driver)',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $canada_transition_file_team_driver_arr,
		'file'           => $canada_transition_file_team_driver,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'canada-transition-file-team-driver',
		'field_name'     => 'canada_transition_file_team_driver',
		'field_label'    => 'Canada transition file (Team driver)',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $background_file_team_driver_arr,
		'file'           => $background_file_team_driver,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'background-file-team-driver',
		'field_name'     => 'background_file_team_driver',
		'field_label'    => 'Background file (Team driver)',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
	array(
		'file_arr'       => $change_9_file_team_driver_arr,
		'file'           => $change_9_file_team_driver,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'change-9-file-team-driver',
		'field_name'     => 'change_9_file_team_driver',
		'field_label'    => 'Change 9 file (Team driver)',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-documents-tab',
	),
);

$driverLicenceTypes = $driver->driverLicenceTypes;
$legalDocumentTypes = $driver->legalDocumentTypes;
$insuredOptions     = $driver->insuredOptions;
$statusOptions      = $driver->statusOptions;

$access_vehicle = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'accounting',
	'billing',
	'billing-tl',
	'moderator',

], true );

?>

<div class="container mt-4 pb-5">

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <h2>Documents</h2>
	   <div class="d-flex align-items-center gap-2">
            <?php if ( $post_id && !$full_only_view ): ?>
                <button class='btn btn-outline-success js-remote-send-form' data-form="js-driver-document-form">Save</button>
            <?php endif; ?>
            <?php if ( $total_images > 2 ): ?>
            <div class=" <?php echo ( $total_images === 3 ) ? 'show-more-hide-desktop' : 'd-flex '; ?>">
                <button class="js-toggle btn btn-primary change-text"
                        data-block-toggle="js-hide-upload-doc-container">
                    <span class="unactive-text">Show more images (<?php echo $total_images; ?>)</span>
                    <span class="active-text">Show less images (<?php echo $total_images; ?>)</span>
                </button>
            </div>
		<?php endif; ?>
        </div>
		
    </div>
	
	<?php if ( $access_vehicle ): ?>
		
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

        <form class="<?php echo $full_only_view ? '' : 'js-driver-document-form'; ?>">
			
			<?php if ( $post_id ): ?>
                <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
			<?php endif; ?>

            <div class="row">
				<?php
				// Driving record
				$simple_upload_args = [
					'full_only_view' => $full_only_view,
					'field_name' => 'driving_record',
					'label'      => 'Driving Record',
					'file_value' => $driving_record,
					'popup_id'   => 'popup_upload_driving_record',
					'col_class'  => 'col-12 col-md-6'
				];
				echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
				?>
				
				
				<?php if ( $team_driver ): ?>
					<?php
					// Driving record team driver
					$simple_upload_args = [
						'full_only_view' => $full_only_view,
						'field_name' => 'team_driver_driving_record',
						'label'      => 'Driving Record (Team driver)',
						'file_value' => $team_driver_driving_record,
						'popup_id'   => 'popup_upload_team_driver_driving_record',
						'col_class'  => 'col-12 col-md-6'
					];
					echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
					?>
				<?php endif; ?>

                <div class="col-12"></div>

                <div class="col-6 mb-3">
                    <label class="form-label">Record notes</label>
                    <input type="text" class="form-control" name="record_notes" value="<?php echo $record_notes; ?>">
                </div>
				<?php if ( $team_driver ): ?>
                    <div class="col-6 mb-3">
                        <label class="form-label">Record notes (Team driver)</label>
                        <input type="text" class="form-control" name="record_notes_team_driver"
                               value="<?php echo $record_notes_team_driver; ?>">
                    </div>
				<?php endif; ?>


                <div class="col-12"></div>

                <!-- Driver licence type -->
                <div class="col-6 mb-3">
                    <label class="form-label">Driver licence type</label>
                    <select name="driver_licence_type"
                            class="form-control form-select js-show-hidden-values" data-value="cdl"
                            data-selector=".js-cdl-section">
						<?php foreach ( $driverLicenceTypes as $value => $label ) : ?>
                            <option value="<?php echo $value; ?>" <?php echo $driver_licence_type === $value
								? 'selected' : ''; ?>>
								<?php echo $label; ?>
                            </option>
						<?php endforeach; ?>
                    </select>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-6 mb-3">
                        <label class="form-label">Driver licence type (Team driver)</label>
                        <select name="driver_licence_type_team_driver"
                                class="form-control form-select js-show-hidden-values" data-value="cdl"
                                data-selector=".js-cdl-section-team-driver">
							<?php foreach ( $driverLicenceTypes as $value => $label ) : ?>
                                <option value="<?php echo $value; ?>" <?php echo $driver_licence_type_team_driver === $value
									? 'selected' : ''; ?>>
									<?php echo $label; ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                    </div>
				<?php endif; ?>
                <div class="col-12"></div>

                <!-- Real ID -->
                <div class="col-12 col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="real_id"
                               id="real_id" <?php echo $real_id ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="real_id">Real ID</label>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="real_id_team_driver"
                                   id="real_id_team_driver" <?php echo $real_id_team_driver ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="real_id_team_driver">Real ID (Team driver)</label>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12"></div>
				
				<?php
				// Driver licence
				$simple_upload_args = [
					'full_only_view' => $full_only_view,
					'field_name' => 'driver_licence',
					'label'      => 'Driver Licence',
					'file_value' => $driver_licence,
					'popup_id'   => 'popup_upload_driver_licence',
					'col_class'  => 'col-12 col-md-6'
				];
				echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
				?>
				
				<?php if ( $team_driver ):
					// Driver licence team driver
					$simple_upload_args = [
						'full_only_view' => $full_only_view,
						'field_name' => 'driver_licence_team_driver',
						'label'      => 'Driver Licence (Team driver)',
						'file_value' => $driver_licence_team_driver,
						'popup_id'   => 'popup_upload_driver_licence_team_driver',
						'col_class'  => 'col-12 col-md-6'
					];
					echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
				endif; ?>

                <div class="col-12"></div>

                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Expiration date</label>
                    <input type="text" class="form-control js-new-format-date" name="driver_licence_expiration"
                           value="<?php echo $driver_licence_expiration; ?>">
                </div>
				
				<?php if ( $team_driver ): ?>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Expiration date (Team driver)</label>
                        <input type="text" class="form-control js-new-format-date"
                               name="driver_licence_expiration_team_driver"
                               value="<?php echo $driver_licence_expiration_team_driver; ?>">
                    </div>
				<?php endif; ?>

                <div class="col-12"></div>

                <div class="col-12 col-md-6 js-cdl-section <?php echo $driver_licence_type === 'cdl' ? ''
					: 'd-none' ?>">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="tanker_endorsement"
                                       id="tanker_endorsement" <?php echo $tanker_endorsement ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tanker_endorsement">Tanker endorsement</label>
                            </div>
                        </div>

                        <!-- Hazmat endorsement -->
                        <div class="col-12  mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input "
                                       type="checkbox" name="hazmat_endorsement"
                                       id="hazmat_endorsement" <?php echo $hazmat_endorsement ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="hazmat_endorsement">Hazmat endorsement</label>
                            </div>
                        </div>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>

                    <div class="col-12 col-md-6 js-cdl-section-team-driver <?php echo $driver_licence_type_team_driver === 'cdl'
						? '' : 'd-none' ?>">
                        <div class="row">
                            <div class="col-12  mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           name="tanker_endorsement_team_driver"
                                           id="tanker_endorsement_team_driver" <?php echo $tanker_endorsement_team_driver
										? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tanker_endorsement_team_driver">Tanker
                                        endorsement
                                        (Team driver)</label>
                                </div>
                            </div>

                            <!-- Hazmat endorsement -->
                            <div class="col-12   mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input "
                                           type="checkbox" name="hazmat_endorsement_team_driver"
                                           id="hazmat_endorsement_team_driver" <?php echo $hazmat_endorsement_team_driver
										? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="hazmat_endorsement_team_driver">Hazmat
                                        endorsement
                                        (Team driver)</label>
                                </div>
                            </div>
                        </div>
                    </div>
				<?php endif; ?>

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
						<?php
						// Hazmat Certificate
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'hazmat_certificate_file',
							'label'      => 'Hazmat Certificate',
							'file_value' => $hazmat_certificate_file,
							'popup_id'   => 'popup_upload_hazmat_certificate_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                        <div class="col-12 col-md-6 ">
                            <label class="form-label ">Expiration date</label>
                            <input type="text" class="form-control js-new-format-date" name="hazmat_expiration"
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
						
						<?php
						// TWIC File
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'twic_file',
							'label'      => 'TWIC File',
							'file_value' => $twic_file,
							'popup_id'   => 'popup_upload_twic_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                        <div class="col-12 col-md-6 ">
                            <label class="form-label">Expiration date</label>
                            <input type="text" class="form-control js-new-format-date" name="twic_expiration"
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
						
						<?php
						// TSA File
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'tsa_file',
							'label'      => 'TSA File',
							'file_value' => $tsa_file,
							'popup_id'   => 'popup_upload_tsa_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                        <div class="col-12 col-md-6 ">
                            <label class="form-label">Expiration date</label>
                            <input type="text" class="form-control js-new-format-date" name="tsa_expiration"
                                   value="<?php echo $tsa_expiration; ?>">
                        </div>
                    </div>
                </div>

                <!-- Legal document type -->
                <div class="col-12 col-md-6 mb-3">
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
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Legal document type (Team driver)</label>
                        <select name="legal_document_type_team_driver"
                                data-value="us-passport|permanent-residency|work-authorization|certificate-of-naturalization|enhanced-driver-licence-real-id"
                                data-selector=".js-legal-doc-section-team-driver"
                                class="form-control form-select js-show-hidden-values js-legal-doc-team-driver">
							<?php foreach ( $legalDocumentTypes as $value => $label ) : ?>
                                <option value="<?php echo $value ?>" <?php echo $legal_document_type_team_driver === $value
									? 'selected' : ''; ?>>
									<?php echo $label ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                    </div>
				<?php endif; ?>

                <div class="col-12 js-legal-doc-section  <?php echo $legal_document_type !== 'no-document' ? ''
					: 'd-none'; ?>">
                    <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">
						<?php
						// Legal document
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'legal_document',
							'label'      => 'Legal document',
							'file_value' => $legal_document,
							'popup_id'   => 'popup_upload_legal_document',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>

                        <div class="col-12 col-md-6 mb-2 js-expiration-date-field" <?php echo $legal_document_type === 'certificate-of-naturalization' ? 'style="display: none;"' : ''; ?>>
                            <label class="form-label">Expiration date</label>
                            <input type="text" class="form-control js-new-format-date" name="legal_document_expiration"
                                   value="<?php echo $legal_document_expiration; ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Nationality</label>
                            <input type="text" value="<?php echo $nationality; ?>" class="form-control"
                                   name="nationality">
                        </div>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 js-legal-doc-section-team-driver  <?php echo $legal_document_type_team_driver !== 'no-document'
						? '' : 'd-none'; ?>">
                        <div class="row border-1 border-success border bg-light pt-3 pb-3 mb-3 rounded ">
							<?php
							// Legal document
							$simple_upload_args = [
								'full_only_view' => $full_only_view,
								'field_name'   => 'legal_document_team_driver',
								'label'        => 'Legal document (Team driver)',
								'file_value'   => $legal_document_team_driver,
								'popup_id'     => 'popup_upload_legal_document_team_driver',
								'col_class'    => 'col-12 col-md-6',
								'button_class' => 'btn btn-outline-success'
							];
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
							?>

                            <div class="col-12 col-md-6 mb-2 js-expiration-date-field-team-driver" <?php echo $legal_document_type_team_driver === 'certificate-of-naturalization' ? 'style="display: none;"' : ''; ?>>
                                <label class="form-label">Expiration date (Team driver)</label>
                                <input type="text" class="form-control js-new-format-date"
                                       name="legal_document_expiration_team_driver"
                                       value="<?php echo $legal_document_expiration_team_driver; ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Nationality (Team driver)</label>
                                <input type="text" value="<?php echo $nationality_team_driver; ?>" class="form-control"
                                       name="nationality_team_driver">
                            </div>
                        </div>

                    </div>
				<?php endif; ?>

                <div class="col-12 col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input js-toggle"
                               data-block-toggle="js-immigration-section" type="checkbox" name="immigration_letter"
                               id="immigration-letter" <?php echo $immigration_letter ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="immigration-letter">Immigration letter</label>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input js-toggle"
                                   data-block-toggle="js-immigration-section-team-driver" type="checkbox"
                                   name="immigration_letter_team_driver"
                                   id="immigration-letter-team-driver" <?php echo $immigration_letter_team_driver
								? 'checked' : ''; ?>>
                            <label class="form-check-label" for="immigration-letter-team-driver">Immigration letter
                                (Team driver)</label>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12  js-immigration-section <?php echo $immigration_letter ? '' : 'd-none'; ?>">
                    <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">
						
						<?php
						// Expiration File (Immigration)
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'immigration_file',
							'label'      => 'Expiration File',
							'file_value' => $immigration_file,
							'popup_id'   => 'popup_upload_immigration_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                       
                    </div>
                </div>
				
				
				<?php if ( $team_driver ): ?>

                    <div class="col-12  js-immigration-section-team-driver <?php echo $immigration_letter_team_driver
						? '' : 'd-none'; ?>">
                        <div class="row border-1 border-success border bg-light pt-3 pb-3 mb-3 rounded ">
							
							<?php
							// Expiration File (Immigration) team driver
							$simple_upload_args = [
								'full_only_view' => $full_only_view,
								'field_name'   => 'immigration_file_team_driver',
								'label'        => 'Expiration File (Team driver)',
								'file_value'   => $immigration_file_team_driver,
								'popup_id'     => 'popup_upload_immigration_file_team_driver',
								'col_class'    => 'col-12 col-md-6',
								'button_class' => 'btn btn-outline-success'
							];
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
							?>
                            
                        </div>
                    </div>
				
				<?php endif; ?>
                <div class="col-12"></div>

                <div class="col-12 col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input js-toggle"
                               data-block-toggle="js-background-check" type="checkbox" name="background_check"
                               id="background_check" <?php echo $background_check ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="background_check">Background check</label>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input js-toggle"
                                   data-block-toggle="js-background-check-team-driver" type="checkbox"
                                   name="background_check_team_driver"
                                   id="background_check_team_driver" <?php echo $background_check_team_driver
								? 'checked' : ''; ?>>
                            <label class="form-check-label" for="background_check_team_driver">Background check
                                (Team driver)</label>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12 js-background-check <?php echo $background_check ? '' : 'd-none'; ?>">
                    <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">
						
						<?php
						// Background File
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'background_file',
							'label'      => 'Background File',
							'file_value' => $background_file,
							'popup_id'   => 'popup_upload_background_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                        <div class="col-12 col-md-6 ">
                            <label class="form-label">date</label>
                            <input type="text" class="form-control js-new-format-date" name="background_date"
                                   value="<?php echo $background_date; ?>">
                        </div>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 js-background-check-team-driver <?php echo $background_check_team_driver ? ''
						: 'd-none'; ?>">
                        <div class="row border-1 border-success border bg-light pt-3 pb-3 mb-3 rounded ">
							
							<?php
							// Background File
							$simple_upload_args = [
								'field_name'   => 'background_file_team_driver',
								'label'        => 'Background File (Team driver)',
								'file_value'   => $background_file_team_driver,
								'popup_id'     => 'popup_upload_background_file_team_driver',
								'col_class'    => 'col-12 col-md-6',
								'button_class' => 'btn btn-outline-success'
							];
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
							?>
                            <div class="col-12 col-md-6 ">
                                <label class="form-label">date (Team driver)</label>
                                <input type="text" class="form-control js-new-format-date"
                                       name="background_date_team_driver"
                                       value="<?php echo $background_date_team_driver; ?>">
                            </div>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12 col-md-6 mb-3">
                    <button type="button" <?php echo $full_only_view ? 'disabled' : ''; ?> class="btn btn-outline-primary btn-sm js-update-background-date">Update
                        background check date
                    </button>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <button type="button" <?php echo $full_only_view ? 'disabled' : ''; ?> 
					class="btn btn-outline-success btn-sm js-update-background-date js-team-driver">Update
                            background check date (Team driver)
                        </button>
                    </div>
				<?php endif; ?>

                <div class="col-12"></div>

                <div class="col-12 col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input js-toggle"
                               data-block-toggle="js-us-canada" type="checkbox" name="canada_transition_proof"
                               id="canada_transition" <?php echo $canada_transition_proof ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="canada_transition">US — Canada transition proof</label>
                    </div>
                </div>
				
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input js-toggle"
                                   data-block-toggle="js-canada-transition-proof-team-driver" type="checkbox"
                                   name="canada_transition_proof_team_driver"
                                   id="canada_transition_proof_team_driver" <?php echo $canada_transition_proof_team_driver
								? 'checked' : ''; ?>>
                            <label class="form-check-label" for="canada_transition_proof_team_driver">US — Canada
                                transition proof
                                (Team driver)</label>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12"></div>

                <div class="col-12 js-us-canada <?php echo $canada_transition_proof ? '' : 'd-none'; ?>">
                    <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">
						
						<?php
						// Canada transition File
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'canada_transition_file',
							'label'      => 'Canada transition File',
							'file_value' => $canada_transition_file,
							'popup_id'   => 'popup_upload_canada_transition_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                        <div class="col-12 col-md-6 ">
                            <label class="form-label">date</label>
                            <input type="text" class="form-control js-new-format-date" name="canada_transition_date"
                                   value="<?php echo $canada_transition_date; ?>">
                        </div>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>


                    <div class="col-12 js-canada-transition-proof-team-driver <?php echo $canada_transition_proof_team_driver
						? '' : 'd-none'; ?>">
                        <div class="row border-1 border-success border bg-light pt-3 pb-3 mb-3 rounded ">
							
							<?php
							// Canada transition File team driver
							$simple_upload_args = [
								'field_name'   => 'canada_transition_file_team_driver',
								'label'        => 'Canada transition File (Team driver)',
								'file_value'   => $canada_transition_file_team_driver,
								'popup_id'     => 'popup_upload_canada_transition_file_team_driver',
								'col_class'    => 'col-12 col-md-6',
								'button_class' => 'btn btn-outline-success'
							];
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
							?>
                            <div class="col-12 col-md-6 ">
                                <label class="form-label">date (Team driver)</label>
                                <input type="text" class="form-control js-new-format-date"
                                       name="canada_transition_date_team_driver"
                                       value="<?php echo $canada_transition_date_team_driver; ?>">
                            </div>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12 col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input js-toggle"
                               data-block-toggle="js-change-9" type="checkbox" name="change_9_training"
                               id="change-9" <?php echo $change_9_training ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="change-9">Change 9 training</label>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input js-toggle"
                                   data-block-toggle="js-change-9-team-driver" type="checkbox"
                                   name="change_9_training_team_driver"
                                   id="change-9-team-driver" <?php echo $change_9_training_team_driver ? 'checked'
								: ''; ?>>
                            <label class="form-check-label" for="change-9-team-driver">Change 9 training (Team
                                driver)</label>
                        </div>
                    </div>
				<?php endif; ?>

                <div class="col-12 js-change-9 <?php echo $change_9_training ? '' : 'd-none'; ?>">
                    <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded ">
						
						<?php
						// Change 9 File
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'change_9_file',
							'label'      => 'Change 9 File',
							'file_value' => $change_9_file,
							'popup_id'   => 'popup_upload_change_9_file',
							'col_class'  => 'col-12 col-md-6'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
                        <div class="col-12 col-md-6 ">
                            <label class="form-label">date</label>
                            <input type="text" class="form-control js-new-format-date" name="change_9_date"
                                   value="<?php echo $change_9_date; ?>">
                        </div>
                    </div>
                </div>
				
				<?php if ( $team_driver ): ?>
                    <div class="col-12 js-change-9-team-driver <?php echo $change_9_training_team_driver ? ''
						: 'd-none'; ?>">
                        <div class="row border-1 border-success border bg-light pt-3 pb-3 mb-3 rounded ">
							
							<?php
							// Change 9 File team driver
							$simple_upload_args = [
								'full_only_view' => $full_only_view,
								'field_name'   => 'change_9_file_team_driver',
								'label'        => 'Change 9 File (Team driver)',
								'file_value'   => $change_9_file_team_driver,
								'popup_id'     => 'popup_upload_change_9_file_team_driver',
								'col_class'    => 'col-12 col-md-6',
								'button_class' => 'btn btn-outline-success'
							];
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
							?>
                            <div class="col-12 col-md-6 ">
                                <label class="form-label">date (Team driver)</label>
                                <input type="text" class="form-control js-new-format-date"
                                       name="change_9_date_team_driver"
                                       value="<?php echo $change_9_date_team_driver; ?>">
                            </div>
                        </div>
                    </div>
				<?php endif; ?>
				
				
				<?php
				// Odysseia IC agreement
				$simple_upload_args = [
					'full_only_view' => $full_only_view,
					'field_name' => 'ic_agreement',
					'label'      => 'Odysseia IC agreement',
					'file_value' => $ic_agreement,
					'popup_id'   => 'popup_upload_ic_agreement',
					'col_class'  => 'col-12 col-md-6'
				];
				echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
				?>

                <div class='col-12'></div>
				
				<?php

				// Martlet Express IC agreement
				$file_upload_args = [
					'field_name'    => 'martlet_ic_agreement',
					'label'         => 'Martlet Express IC agreement',
					'toggle_block'  => 'js-martlet-ic-agreement-files',
					'checkbox_name' => 'martlet_ic_agreement_on',
					'checkbox_id'   => 'martlet-ic-agreement',
					'is_checked'    => $martlet_ic_agreement_on,
					'file_value'    => $martlet_ic_agreement,
					'popup_id'      => 'popup_upload_martlet_ic_agreement',
                    'interview'     => 1,
                    'interview_popup_id' => 'popup_upload_interview_martlet',
                    'interview_file_arr' => $interview_martlet_arr,
                    'full_only_view' => $full_only_view,
                    'post_id' => $post_id,
                    'class_name' => 'martlet-ic-agreement',
                    'interview_field_name' => 'interview_martlet',
                    'delete_action' => 'js-remove-one-driver',
                    'active_tab' => 'pills-driver-documents-tab',
				];
				echo esc_html( get_template_part( TEMPLATE_PATH . 'common/file', 'upload-block-and-interview', $file_upload_args ) );
				?>
				
				<?php
				// Endurance Transport IC agreement
				$file_upload_args = [
					'field_name'    => 'endurance_ic_agreement',
					'label'         => 'Endurance Transport IC agreement',
					'toggle_block'  => 'js-endurance-ic-agreement-files',
					'checkbox_name' => 'endurance_ic_agreement_on',
					'checkbox_id'   => 'endurance-ic-agreement',
					'is_checked'    => $endurance_ic_agreement_on,
					'file_value'    => $endurance_ic_agreement, 
					'popup_id'      => 'popup_upload_endurance_ic_agreement',
                    'interview'     => 1,
                    'interview_popup_id' => 'popup_upload_interview_endurance',
                    'interview_file_arr' => $interview_endurance_arr,
                    'full_only_view' => $full_only_view,
                    'post_id' => $post_id,
                    'class_name' => 'endurance-ic-agreement',
                    'interview_field_name' => 'interview_endurance',
                    'delete_action' => 'js-remove-one-driver',
                    'active_tab' => 'pills-driver-documents-tab',
				];
				echo esc_html( get_template_part( TEMPLATE_PATH . 'common/file', 'upload-block-and-interview', $file_upload_args ) );
				?>

                <div class='col-12'></div>

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
                            <input type="text" class="form-control js-new-format-date" name="auto_liability_expiration"
                                   value="<?php echo $auto_liability_expiration; ?>">
                        </div>

                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label">Insurer</label>
                            <input type="text" class="form-control" value="<?php echo $auto_liability_insurer; ?>"
                                   name="auto_liability_insurer">
                        </div>
						
						<?php
						// Odysseia COI
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'auto_liability_coi',
							'label'      => 'Odysseia COI',
							'file_value' => $auto_liability_coi,
							'popup_id'   => 'popup_upload_auto_liability_coi',
							'col_class'  => 'col-12 col-md-6 mb-2'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>


                
                        <?php
                        // Martlet Express COI
                        $file_upload_args = [
                            'full_only_view' => $full_only_view,
                            'field_name'    => 'martlet_coi',
                            'label'         => 'Martlet Express COI',
                            'toggle_block'  => 'js-martlet-coi-files',
                            'checkbox_name' => 'martlet_coi_on',
                            'checkbox_id'   => 'martlet-coi',
                            'expired_date'  => 1,
                            'is_checked'    => $martlet_coi_on,
                            'file_value'    => $martlet_coi,
                            'popup_id'      => 'popup_upload_martlet_coi',
                            'expired_date_name' => 'martlet_coi_expired_date',
                            'expired_date_id' => 'martlet-coi-expired-date',
                            'expired_date_class' => 'js-new-format-date',
                            'expired_date_label' => 'Expired date (Martlet Express)',
                            'expired_date_placeholder' => 'Expired date',
                            'expired_date_value' => $martlet_coi_expired_date
                        ];
                        echo esc_html( get_template_part( TEMPLATE_PATH . 'common/file', 'upload-block-and-interview', $file_upload_args ) );
                        ?>
                            
						
						<?php
						// Endurance Transport COI
						$file_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name'    => 'endurance_coi',
							'label'         => 'Endurance Transport COI',
							'toggle_block'  => 'js-endurance-coi-files',
							'checkbox_name' => 'endurance_coi_on',
							'checkbox_id'   => 'endurance-coi',
                            'expired_date'  => 1,
							'is_checked'    => $endurance_coi_on,
							'file_value'    => $endurance_coi,
							'popup_id'      => 'popup_upload_endurance_coi',
                            'expired_date_name' => 'endurance_coi_expired_date',
                            'expired_date_id' => 'endurance-coi-expired-date',
                            'expired_date_class' => 'js-new-format-date w-100',
                            'expired_date_label' => 'Expired date (Endurance Transport)',
                            'expired_date_placeholder' => 'Expired date',
                            'expired_date_value' => $endurance_coi_expired_date
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/file', 'upload-block-and-interview', $file_upload_args ) );
						?>

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
                            <input type="text" class="form-control js-new-format-date" name="motor_cargo_expiration"
                                   value="<?php echo $motor_cargo_expiration; ?>">
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label">Insurer</label>
                            <input type="text" class="form-control" value="<?php echo $motor_cargo_insurer; ?>"
                                   name="motor_cargo_insurer">
                        </div>
						<?php
						// Motor Cargo COI
						$simple_upload_args = [
							'full_only_view' => $full_only_view,
							'field_name' => 'motor_cargo_coi',
							'label'      => 'Motor Cargo COI',
							'file_value' => $motor_cargo_coi,
							'popup_id'   => 'popup_upload_motor_cargo_coi',
							'col_class'  => 'col-12 col-md-6 mb-2'
						];
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
						?>
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
                    <input type="text" class="form-control js-new-format-date" value="<?php echo $cancellation_date; ?>"
                           name="cancellation_date">
                </div>

                <!-- Notes -->
                <div class="col-12 mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes"><?php echo esc_html( trim( $notes ) ); ?></textarea>
                </div>
            </div>

            <div class="row">

                <div class="col-12" role="presentation">
                    <div class="justify-content-start gap-2">
                        <button type="button" data-tab-id="pills-driver-finance-tab"
                                class="btn btn-dark js-next-tab">Previous
                        </button>
						<?php if ( $full_only_view ): ?>
						
						<?php else: ?>
                            <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                    data-tab-id="pills-driver-documents-tab">
                                update
                            </button>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
		
		<?php
		$popups_upload = array(
			array(
				'title'     => 'Upload Hazmat Certificate File',
				'file_name' => 'hazmat_certificate_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Driving record',
				'file_name' => 'driving_record',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Driving licence',
				'file_name' => 'driver_licence',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Legal document',
				'file_name' => 'legal_document',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload TWIC file',
				'file_name' => 'twic_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload TSA file',
				'file_name' => 'tsa_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Motor cargo COI',
				'file_name' => 'motor_cargo_coi',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Auto liability coi',
				'file_name' => 'auto_liability_coi',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Martlet Express COI',
				'file_name' => 'martlet_coi',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Endurance Transport COI',
				'file_name' => 'endurance_coi',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Odysseia IC agreement',
				'file_name' => 'ic_agreement',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Martlet Express IC agreement',
				'file_name' => 'martlet_ic_agreement',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Endurance Transport IC agreement',
				'file_name' => 'endurance_ic_agreement',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload change 9 file',
				'file_name' => 'change_9_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Canada transition file',
				'file_name' => 'canada_transition_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Immigration file',
				'file_name' => 'immigration_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Background file',
				'file_name' => 'background_file',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Team driver driving record',
				'file_name' => 'team_driver_driving_record',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Driver licence (Team driver)',
				'file_name' => 'driver_licence_team_driver',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Immigration file (Team driver)',
				'file_name' => 'immigration_file_team_driver',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Legal document (Team driver)',
				'file_name' => 'legal_document_team_driver',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Canada transition file (Team driver)',
				'file_name' => 'canada_transition_file_team_driver',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Background file (Team driver)',
				'file_name' => 'background_file_team_driver',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Change 9 file (Team driver)',
				'file_name' => 'change_9_file_team_driver',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Interview (Martlet Express)',
				'file_name' => 'interview_martlet',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
			array(
				'title'     => 'Upload Interview (Endurance Transport)',
				'file_name' => 'interview_endurance',
				'multiply'  => false,
				'driver_id' => $post_id,
			),
		);
		
		foreach ( $popups_upload as $popup ):
			echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload', 'file', $popup ) );
		endforeach;
	
	else: ?>
        <div class="alert alert-info">You do not have permission to upload documents.</div>
	<?php endif; ?>

</div>
