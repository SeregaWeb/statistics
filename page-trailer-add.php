<?php
/**
 * Template Name: Page trailer add
 * 
 * Page to add a new trailer
 */

$trailer = new TMSTrailers();
$helper = new TMSReportsHelper();
$driverHelper = new TMSDriversHelper();
$TMSUsers = new TMSUsers();

$flt_access = $TMSUsers->get_flt_access( get_current_user_id() );

// Roles that can create and edit trailers
$roles_can_edit = array(
	'administrator',
	'recruiter-tl',
	'hr_manager',
);

// Roles that can only view trailers (with FTL access)
$roles_can_view = array(
	'dispatcher',
	'dispatcher-tl',
);

// Check if user can create/edit
$can_create_edit = $TMSUsers->check_user_role_access( $roles_can_edit, true );

// Check if user can only view (dispatcher/dispatcher-tl with FTL access)
$is_dispatcher = $TMSUsers->check_user_role_access( $roles_can_view, true );
$can_view_only = $is_dispatcher && $flt_access;

// Check if current user is administrator (for delete button)
$is_admin = $TMSUsers->check_user_role_access( array( 'administrator' ), true );

// Determine access
$access = $can_create_edit || $can_view_only;

// If no access, redirect
if ( ! $access ) {
	wp_redirect( home_url() );
	exit;
}

// Get trailer ID from URL parameter
$trailer_id = isset( $_GET['trailer'] ) ? intval( $_GET['trailer'] ) : 0;
$trailer_data = null;

// Set full_only_view: true if user can only view, false if can create/edit
$full_only_view = ! $can_create_edit;

if ( $trailer_id ) {
	$trailer_data = $trailer->get_trailer_by_id( $trailer_id );
	if ( ! $trailer_data ) {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
}


$main = $trailer_data ? get_field_value( $trailer_data, 'main' ) : null;
$meta = $trailer_data ? get_field_value( $trailer_data, 'meta' ) : array();

// Process file attachments
$license_plate_file = get_field_value( $meta, 'license_plate_file' );
$trailer_registration = get_field_value( $meta, 'trailer_registration' );
$lease_agreement = get_field_value( $meta, 'lease_agreement' );

$license_plate_file_arr = $driverHelper->process_file_attachment( $license_plate_file );
$trailer_registration_arr = $driverHelper->process_file_attachment( $trailer_registration );
$lease_agreement_arr = $driverHelper->process_file_attachment( $lease_agreement );

// Count total images
$total_images = 0;
$files_check = array(
	$license_plate_file,
	$trailer_registration,
	$lease_agreement,
);

foreach ( $files_check as $file ) {
	if ( ! empty( $file ) ) {
		$total_images++;
	}
}

// Prepare files array for display
$files = array();
if ( $license_plate_file_arr ) {
	$files[] = array(
		'file_arr'       => $license_plate_file_arr,
		'file'           => $license_plate_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $trailer_id,
		'class_name'     => 'license-plate-file',
		'field_name'     => 'license_plate_file',
		'field_label'    => 'License Plate',
		'delete_action'  => 'js-remove-one-trailer',
		'active_tab'     => 'trailer-form',
	);
}
if ( $trailer_registration_arr ) {
	$files[] = array(
		'file_arr'       => $trailer_registration_arr,
		'file'           => $trailer_registration,
		'full_only_view' => $full_only_view,
		'post_id'        => $trailer_id,
		'class_name'     => 'trailer-registration',
		'field_name'     => 'trailer_registration',
		'field_label'    => 'Trailer registration',
		'delete_action'  => 'js-remove-one-trailer',
		'active_tab'     => 'trailer-form',
	);
}
if ( $lease_agreement_arr ) {
	$files[] = array(
		'file_arr'       => $lease_agreement_arr,
		'file'           => $lease_agreement,
		'full_only_view' => $full_only_view,
		'post_id'        => $trailer_id,
		'class_name'     => 'lease-agreement',
		'field_name'     => 'lease_agreement',
		'field_label'    => 'Lease agreement',
		'delete_action'  => 'js-remove-one-trailer',
		'active_tab'     => 'trailer-form',
	);
}

get_header(); ?>

<div class="container mt-4 pb-5">
	<div class="mb-3 d-flex justify-content-between align-items-center">
		<h2><?php echo $trailer_id ? 'Edit Trailer' : 'Add New Trailer'; ?></h2>
		<div class="d-flex align-items-center gap-2">
			<?php if ( $trailer_id && ! $full_only_view ): ?>
				<button class='btn btn-outline-success js-remote-send-form' data-form="js-trailer-form">Save</button>
				<?php if ( $is_admin ): ?>
					<button type="button" class='btn btn-outline-danger js-delete-trailer-single' 
						data-trailer-id="<?php echo esc_attr( $trailer_id ); ?>">
						Delete
					</button>
				<?php endif; ?>
			<?php elseif ( ! $trailer_id && ! $full_only_view ): ?>
				<button type="button" class='btn btn-outline-success js-submit-create-trailer'>Create</button>
			<?php endif; ?>
		</div>
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
	
	<form class="<?php echo $full_only_view ? '' : ( $trailer_id ? 'js-trailer-form' : 'js-create-trailer' ); ?>" id="js-trailer-form">
		<?php wp_nonce_field( 'trailer_nonce', 'nonce' ); ?>
		<?php if ( $trailer_id ): ?>
			<input type="hidden" name="trailer_id" value="<?php echo esc_attr( $trailer_id ); ?>">
		<?php endif; ?>
		<?php 
		// Add trailers list page URL for redirect after deletion
		$trailers_page = get_page_by_path( 'trailers' );
		if ( $trailers_page ) {
			$trailers_url = get_permalink( $trailers_page );
			echo '<input type="hidden" id="trailers-list-url" value="' . esc_attr( $trailers_url ) . '">';
		}
		?>
		
		<div class="row">
			<div class="col-md-4 mb-3">
				<label class="form-label">Trailer type<span class="required-star text-danger">*</span></label>
				<select class="form-control form-select" id="trailer_type" name="trailer_type" required>
					<option value="" disabled selected>Select Trailer Type</option>
					<option value="dry-van" <?php selected( get_field_value( $meta, 'trailer_type' ), 'dry-van' ); ?>>Dry van</option>
					<option value="flatbed" <?php selected( get_field_value( $meta, 'trailer_type' ), 'flatbed' ); ?>>Flatbed</option>
					<option value="step-deck" <?php selected( get_field_value( $meta, 'trailer_type' ), 'step-deck' ); ?>>Step Deck</option>
					<option value="refrigerated" <?php selected( get_field_value( $meta, 'trailer_type' ), 'refrigerated' ); ?>>Refrigerated</option>
					<option value="step-deck-alt" <?php selected( get_field_value( $meta, 'trailer_type' ), 'step-deck-alt' ); ?>>Step-Deck</option>
					<option value="hot-shot" <?php selected( get_field_value( $meta, 'trailer_type' ), 'hot-shot' ); ?>>Hot Shot</option>
					<option value="conestoga" <?php selected( get_field_value( $meta, 'trailer_type' ), 'conestoga' ); ?>>Conestoga</option>
					<option value="curtainside" <?php selected( get_field_value( $meta, 'trailer_type' ), 'curtainside' ); ?>>Curtainside</option>
					<option value="rgn" <?php selected( get_field_value( $meta, 'trailer_type' ), 'rgn' ); ?>>RGN</option>
					<option value="pup" <?php selected( get_field_value( $meta, 'trailer_type' ), 'pup' ); ?>>Pup</option>
				</select>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Trailer number<span class="required-star text-danger">*</span></label>
				<input type="text" class="form-control" name="trailer_number" 
					value="<?php echo esc_attr( get_field_value( $meta, 'trailer_number' ) ); ?>" required>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label d-flex align-items-center gap-1">License Plate

                    <?php if ( $license_plate_file ): ?>
                        <?php echo $helper->get_icon_uploaded_file(); ?>
                    <?php endif; ?>
                    </label>
				<div class="d-flex gap-2 align-items-center">
					<div class="flex-grow-1">
						<input type="text" class="form-control" name="license_plate" 
							value="<?php echo esc_attr( get_field_value( $meta, 'license_plate' ) ); ?>">
					</div>
					<?php if ( ! $full_only_view && $trailer_id ): ?>
						<?php if ( ! $license_plate_file ): ?>
							<button <?php echo $full_only_view ? 'disabled' : ''; ?> data-href="#popup_upload_license_plate_file"
									class="btn btn-sm btn-success js-open-popup-activator">
								Upload file
							</button>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">License State / Province</label>
				<select class="form-control form-select" name="license_state">
					<option value="">Select state/province</option>
					<?php
					$states = $helper->select;
					foreach ( $states as $key => $value ) {
						if ( is_array( $value ) ) {
							echo '<optgroup label="' . esc_attr( $value[0] ) . '"></optgroup>';
						} else {
							$selected = get_field_value( $meta, 'license_state' ) === $key ? 'selected' : '';
							echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $value ) . '</option>';
						}
					}
					?>
				</select>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">VIN</label>
				<input type="text" class="form-control" name="vin" 
					value="<?php echo esc_attr( get_field_value( $meta, 'vin' ) ); ?>">
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Make</label>
				<input type="text" class="form-control" name="make" 
					value="<?php echo esc_attr( get_field_value( $meta, 'make' ) ); ?>">
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Year</label>
				<input type="text" class="form-control" name="year" 
					value="<?php echo esc_attr( get_field_value( $meta, 'year' ) ); ?>" 
					maxlength="4" pattern="[0-9]{4}">
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Cargo Maximum</label>
				<div class="input-group">
					<input type="number" class="form-control" name="cargo_maximum" 
						value="<?php echo esc_attr( get_field_value( $meta, 'cargo_maximum' ) ); ?>" step="0.01">
					<span class="input-group-text">lbs</span>
				</div>
			</div>
		</div>
		
		<!-- Dimensions - Default (for most types) -->
		<div class="row js-dimensions-default" style="display: none;">
			<div class="col-12 mb-3">
				<h5>Dimensions</h5>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Width</label>
				<div class="input-group">
					<input type="number" class="form-control" name="width" 
						value="<?php echo esc_attr( get_field_value( $meta, 'width' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Door Height</label>
				<div class="input-group">
					<input type="number" class="form-control" name="door_height" 
						value="<?php echo esc_attr( get_field_value( $meta, 'door_height' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Total Height</label>
				<div class="input-group">
					<input type="number" class="form-control" name="total_height" 
						value="<?php echo esc_attr( get_field_value( $meta, 'total_height' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
		</div>
		
		<!-- Dimensions - RGN -->
		<div class="row js-dimensions-rgn" style="display: none;">
			<div class="col-12 mb-3">
				<h5>Dimensions</h5>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length: Main Well</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length_main_well" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length_main_well' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length: Rear Deck</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length_rear_deck" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length_rear_deck' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Width</label>
				<div class="input-group">
					<input type="number" class="form-control" name="width" 
						value="<?php echo esc_attr( get_field_value( $meta, 'width' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Height</label>
				<div class="input-group">
					<input type="number" class="form-control" name="height" 
						value="<?php echo esc_attr( get_field_value( $meta, 'height' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
		</div>
		
		<!-- Dimensions - Step Deck -->
		<div class="row js-dimensions-step-deck" style="display: none;">
			<div class="col-12 mb-3">
				<h5>Dimensions</h5>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length: Total</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length_total" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length_total' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length: Lower Deck</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length_lower_deck" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length_lower_deck' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length: Upper Deck</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length_upper_deck" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length_upper_deck' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Width</label>
				<div class="input-group">
					<input type="number" class="form-control" name="width" 
						value="<?php echo esc_attr( get_field_value( $meta, 'width' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Height: Lower Deck</label>
				<div class="input-group">
					<input type="number" class="form-control" name="height_lower_deck" 
						value="<?php echo esc_attr( get_field_value( $meta, 'height_lower_deck' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Height: Upper Deck</label>
				<div class="input-group">
					<input type="number" class="form-control" name="height_upper_deck" 
						value="<?php echo esc_attr( get_field_value( $meta, 'height_upper_deck' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
		</div>
		
		<!-- Dimensions - Flatbed and Hot Shot -->
		<div class="row js-dimensions-flatbed-hotshot" style="display: none;">
			<div class="col-12 mb-3">
				<h5>Dimensions</h5>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Length</label>
				<div class="input-group">
					<input type="number" class="form-control" name="length" 
						value="<?php echo esc_attr( get_field_value( $meta, 'length' ) ); ?>" step="0.01">
					<span class="input-group-text">ft</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Width</label>
				<div class="input-group">
					<input type="number" class="form-control" name="width" 
						value="<?php echo esc_attr( get_field_value( $meta, 'width' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Height</label>
				<div class="input-group">
					<input type="number" class="form-control" name="height" 
						value="<?php echo esc_attr( get_field_value( $meta, 'height' ) ); ?>" step="0.01">
					<span class="input-group-text">in</span>
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="col-12 mb-3">
				<h5>Documents</h5>
			</div>
			
			<?php
			// Trailer registration file upload
			$simple_upload_args = [
				'full_only_view' => $full_only_view || ! $trailer_id,
				'field_name' => 'trailer_registration',
				'label'      => 'Trailer registration',
				'file_value' => $trailer_registration,
				'popup_id'   => 'popup_upload_trailer_registration',
				'col_class'  => 'col-12 col-md-6'
			];
			echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
			?>
		</div>
		
		<div class="row">
			<div class="col-12 mb-3">
				<h5>Lease Information</h5>
			</div>
			
			<div class="col-md-6 mb-3">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="lease" name="lease" value="1"
						<?php checked( get_field_value( $meta, 'lease' ), 'on' ); ?>
						<?php echo ! $trailer_id ? 'disabled' : ''; ?>>
					<label class="form-check-label" for="lease">Lease</label>
				</div>
			</div>
			
			<div class="col-md-6 mb-3 js-lease-agreement" style="display: none;">
				<?php
				// Lease agreement file upload
				$simple_upload_args = [
					'full_only_view' => $full_only_view || ! $trailer_id,
					'field_name' => 'lease_agreement',
					'label'      => 'Lease agreement',
					'file_value' => $lease_agreement,
					'popup_id'   => 'popup_upload_lease_agreement',
					'col_class'  => 'w-100'
				];
				echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
				?>
			</div>
		</div>
		
		<?php if ( $trailer_id && ! $full_only_view ): ?>
			<div class="row mt-4">
				<div class="col-12 d-flex justify-content-end">
					<button class='btn btn-outline-success js-remote-send-form' data-form="js-trailer-form">Save</button>
				</div>
			</div>
		<?php endif; ?>
	</form>
</div>

<?php
// Popups for file uploads (only show after trailer is created)
if ( ! $full_only_view && $trailer_id ):
	$popup_args = [
		'file_name' => 'license_plate_file',
		'title'     => 'Upload License Plate',
		'multiply'  => false,
		'trailer_id' => $trailer_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-trailer', 'file', $popup_args ) );
	
	$popup_args = [
		'file_name' => 'trailer_registration',
		'title'     => 'Upload Trailer Registration',
		'multiply'  => false,
		'trailer_id' => $trailer_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-trailer', 'file', $popup_args ) );
	
	$popup_args = [
		'file_name' => 'lease_agreement',
		'title'     => 'Upload Lease Agreement',
		'multiply'  => false,
		'trailer_id' => $trailer_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-trailer', 'file', $popup_args ) );
endif;
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const trailerTypeSelect = document.getElementById('trailer_type');
	const dimensionsDefault = document.querySelector('.js-dimensions-default');
	const dimensionsRGN = document.querySelector('.js-dimensions-rgn');
	const dimensionsStepDeck = document.querySelector('.js-dimensions-step-deck');
	const dimensionsFlatbedHotshot = document.querySelector('.js-dimensions-flatbed-hotshot');
	const leaseSwitch = document.getElementById('lease');
	const leaseAgreement = document.querySelector('.js-lease-agreement');
	
	function toggleDimensions() {
		const type = trailerTypeSelect.value;
		
		// Hide all dimension sections
		if ( dimensionsDefault ) dimensionsDefault.style.display = 'none';
		if ( dimensionsRGN ) dimensionsRGN.style.display = 'none';
		if ( dimensionsStepDeck ) dimensionsStepDeck.style.display = 'none';
		if ( dimensionsFlatbedHotshot ) dimensionsFlatbedHotshot.style.display = 'none';
		
		// Show appropriate section
		if ( type === 'rgn' && dimensionsRGN ) {
			dimensionsRGN.style.display = 'block';
		} else if ( type === 'step-deck' && dimensionsStepDeck ) {
			dimensionsStepDeck.style.display = 'block';
		} else if ( ( type === 'flatbed' || type === 'hot-shot' ) && dimensionsFlatbedHotshot ) {
			dimensionsFlatbedHotshot.style.display = 'block';
		} else if ( type && dimensionsDefault ) {
			dimensionsDefault.style.display = 'block';
		}
	}
	
	function toggleLeaseAgreement() {
		if ( leaseSwitch && leaseAgreement ) {
			if ( leaseSwitch.checked ) {
				leaseAgreement.style.display = 'block';
			} else {
				leaseAgreement.style.display = 'none';
			}
		}
	}
	
	if ( trailerTypeSelect ) {
		trailerTypeSelect.addEventListener('change', toggleDimensions);
		toggleDimensions(); // Initialize on page load
	}
	
	if ( leaseSwitch ) {
		leaseSwitch.addEventListener('change', toggleLeaseAgreement);
		toggleLeaseAgreement(); // Initialize on page load
	}
});
</script>

<?php get_footer(); ?>
