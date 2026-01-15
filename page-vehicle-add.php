<?php
/**
 * Template Name: Page vehicle add
 * 
 * Page to add a new vehicle
 */

$vehicle = new TMSVehicles();
$helper = new TMSReportsHelper();
$driverHelper = new TMSDriversHelper();
$TMSUsers = new TMSUsers();

$flt_access = $TMSUsers->get_flt_access( get_current_user_id() );

// Roles that can create and edit vehicles
$roles_can_edit = array(
	'administrator',
	'recruiter-tl',
	'hr_manager',
);

// Roles that can only view vehicles (with FTL access)
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

// Get vehicle ID from URL parameter
$vehicle_id = isset( $_GET['vehicle'] ) ? intval( $_GET['vehicle'] ) : 0;
$vehicle_data = null;

// Set full_only_view: true if user can only view, false if can create/edit
$full_only_view = ! $can_create_edit;

if ( $vehicle_id ) {
	$vehicle_data = $vehicle->get_vehicle_by_id( $vehicle_id );
	if ( ! $vehicle_data ) {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
}

$main = $vehicle_data ? get_field_value( $vehicle_data, 'main' ) : null;
$meta = $vehicle_data ? get_field_value( $vehicle_data, 'meta' ) : array();

// Process file attachments
$vehicle_registration = get_field_value( $meta, 'vehicle_registration' );
$fleet_registration_id_card = get_field_value( $meta, 'fleet_registration_id_card' );
$annual_vehicle_inspection = get_field_value( $meta, 'annual_vehicle_inspection' );
$dot_inspection = get_field_value( $meta, 'dot_inspection' );

$vehicle_registration_arr = $driverHelper->process_file_attachment( $vehicle_registration );
$fleet_registration_id_card_arr = $driverHelper->process_file_attachment( $fleet_registration_id_card );
$annual_vehicle_inspection_arr = $driverHelper->process_file_attachment( $annual_vehicle_inspection );

// Process multiple files for dot_inspection
$dot_inspection_arr = array();
if ( ! empty( $dot_inspection ) ) {
	$dot_files_ids = explode( ', ', $dot_inspection );
	foreach ( $dot_files_ids as $file_id ) {
		$file_id = trim( $file_id );
		if ( ! empty( $file_id ) ) {
			$file_arr = $driverHelper->process_file_attachment( $file_id );
			if ( $file_arr ) {
				$dot_inspection_arr[] = $file_arr;
			}
		}
	}
}

// Count total images
$total_images = 0;
if ( ! empty( $vehicle_registration ) ) {
	$total_images++;
}
if ( ! empty( $fleet_registration_id_card ) ) {
	$total_images++;
}
if ( ! empty( $annual_vehicle_inspection ) ) {
	$total_images++;
}
if ( ! empty( $dot_inspection_arr ) ) {
	$total_images += count( $dot_inspection_arr );
}

// Prepare files array for display
$files = array();

if ( $vehicle_registration_arr ) {
	$files[] = array(
		'file_arr'       => $vehicle_registration_arr,
		'file'           => $vehicle_registration,
		'full_only_view' => $full_only_view,
		'post_id'        => $vehicle_id,
		'class_name'     => 'vehicle-registration',
		'field_name'     => 'vehicle_registration',
		'field_label'    => 'Vehicle Registration',
		'delete_action'  => 'js-remove-one-vehicle',
		'active_tab'     => 'vehicle-form',
	);
}
if ( $fleet_registration_id_card_arr ) {
	$files[] = array(
		'file_arr'       => $fleet_registration_id_card_arr,
		'file'           => $fleet_registration_id_card,
		'full_only_view' => $full_only_view,
		'post_id'        => $vehicle_id,
		'class_name'     => 'fleet-registration-id-card',
		'field_name'     => 'fleet_registration_id_card',
		'field_label'    => 'Fleet Registration ID card',
		'delete_action'  => 'js-remove-one-vehicle',
		'active_tab'     => 'vehicle-form',
	);
}
if ( $annual_vehicle_inspection_arr ) {
	$files[] = array(
		'file_arr'       => $annual_vehicle_inspection_arr,
		'file'           => $annual_vehicle_inspection,
		'full_only_view' => $full_only_view,
		'post_id'        => $vehicle_id,
		'class_name'     => 'annual-vehicle-inspection',
		'field_name'     => 'annual_vehicle_inspection',
		'field_label'    => 'Annual Vehicle Inspection',
		'delete_action'  => 'js-remove-one-vehicle',
		'active_tab'     => 'vehicle-form',
	);
}
// Add multiple files for dot_inspection
if ( ! empty( $dot_inspection_arr ) ) {
	foreach ( $dot_inspection_arr as $dot_file_arr ) {
		$files[] = array(
			'file_arr'       => $dot_file_arr,
			'file'           => $dot_file_arr[ 'id' ],
			'full_only_view' => $full_only_view,
			'post_id'        => $vehicle_id,
			'class_name'     => 'dot-inspection',
			'field_name'     => 'dot_inspection',
			'field_label'    => 'DOT Inspection',
			'delete_action'  => 'js-remove-one-vehicle',
			'active_tab'     => 'vehicle-form',
		);
	}
}

get_header(); ?>

<div class="container mt-4 pb-5">
	<div class="mb-3 d-flex justify-content-between align-items-center">
		<h2><?php echo $vehicle_id ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h2>
		<div class="d-flex align-items-center gap-2">
			<?php if ( $vehicle_id && ! $full_only_view ): ?>
				<button class='btn btn-outline-success js-remote-send-form' data-form="js-vehicle-form">Save</button>
				<?php if ( $is_admin ): ?>
					<button type="button" class='btn btn-outline-danger js-delete-vehicle-single' 
						data-vehicle-id="<?php echo esc_attr( $vehicle_id ); ?>">
						Delete
					</button>
				<?php endif; ?>
			<?php elseif ( ! $vehicle_id && ! $full_only_view ): ?>
				<button type="button" class='btn btn-outline-success js-submit-create-vehicle'>Create</button>
			<?php endif; ?>
			<?php if ( $total_images > 2 ): ?>
				<div class="<?php echo ( $total_images === 3 ) ? 'show-more-hide-desktop' : 'd-flex '; ?>">
					<button class="js-toggle btn btn-primary change-text"
							data-block-toggle="js-hide-upload-doc-container">
						<span class="unactive-text">Show more images (<?php echo $total_images; ?>)</span>
						<span class="active-text">Show less images (<?php echo $total_images; ?>)</span>
					</button>
				</div>
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
	
	<form class="<?php echo $full_only_view ? '' : ( $vehicle_id ? 'js-vehicle-form' : 'js-create-vehicle' ); ?>" id="js-vehicle-form">
		<?php wp_nonce_field( 'vehicle_nonce', 'nonce' ); ?>
		<?php if ( $vehicle_id ): ?>
			<input type="hidden" name="vehicle_id" value="<?php echo esc_attr( $vehicle_id ); ?>">
		<?php endif; ?>
		<?php 
		// Add vehicles list page URL for redirect after deletion
		$vehicles_page = get_page_by_path( 'vehicles' );
		if ( $vehicles_page ) {
			$vehicles_url = get_permalink( $vehicles_page );
			echo '<input type="hidden" id="vehicles-list-url" value="' . esc_attr( $vehicles_url ) . '">';
		}
		?>
		
		<div class="row">
			<div class="col-md-4 mb-3">
				<label class="form-label">Type<span class="required-star text-danger">*</span></label>
				<select class="form-control form-select" id="vehicle_type" name="vehicle_type" required>
					<option value="" disabled selected>Select Vehicle Type</option>
					<option value="semi-truck" <?php selected( get_field_value( $meta, 'vehicle_type' ), 'semi-truck' ); ?>>Semi truck</option>
					<option value="cargo-van" <?php selected( get_field_value( $meta, 'vehicle_type' ), 'cargo-van' ); ?>>Cargo van</option>
					<option value="sprinter-van" <?php selected( get_field_value( $meta, 'vehicle_type' ), 'sprinter-van' ); ?>>Sprinter van</option>
					<option value="box-truck" <?php selected( get_field_value( $meta, 'vehicle_type' ), 'box-truck' ); ?>>Box truck</option>
					<option value="hotshot" <?php selected( get_field_value( $meta, 'vehicle_type' ), 'hotshot' ); ?>>Hotshot</option>
				</select>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Make<span class="required-star text-danger">*</span></label>
				<input type="text" class="form-control" name="make" 
					value="<?php echo esc_attr( get_field_value( $meta, 'make' ) ); ?>" required>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Model<span class="required-star text-danger">*</span></label>
				<input type="text" class="form-control" name="model" 
					value="<?php echo esc_attr( get_field_value( $meta, 'model' ) ); ?>" required>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Vehicle Year<span class="required-star text-danger">*</span></label>
				<input type="text" class="form-control" name="vehicle_year" 
					value="<?php echo esc_attr( get_field_value( $meta, 'vehicle_year' ) ); ?>" 
					maxlength="4" pattern="[0-9]{4}" required>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">VIN</label>
				<input type="text" class="form-control" name="vin" 
					value="<?php echo esc_attr( get_field_value( $meta, 'vin' ) ); ?>">
			</div>
		</div>
		
		<!-- Fields for Semi truck and Box truck: Tare Weight, GVWR -->
		<div class="row js-fields-semi-box" style="display: none;">
			<div class="col-md-6 mb-3">
				<label class="form-label">Tare Weight</label>
				<div class="input-group">
					<input type="number" class="form-control" name="tare_weight" 
						value="<?php echo esc_attr( get_field_value( $meta, 'tare_weight' ) ); ?>" step="0.01">
					<span class="input-group-text">lbs</span>
				</div>
			</div>
			
			<div class="col-md-6 mb-3">
				<label class="form-label">GVWR</label>
				<div class="input-group">
					<input type="number" class="form-control" name="gvwr" 
						value="<?php echo esc_attr( get_field_value( $meta, 'gvwr' ) ); ?>" step="0.01">
					<span class="input-group-text">lbs</span>
				</div>
			</div>
		</div>
		
		<!-- Dock High for Semi truck (always on, disabled) and Box truck (can toggle) -->
		<div class="row js-dock-high-section" style="display: none;">
			<div class="col-md-6 mb-3">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="dock_high" name="dock_high" value="1"
						<?php checked( get_field_value( $meta, 'dock_high' ), 'on' ); ?>
						<?php echo ( get_field_value( $meta, 'vehicle_type' ) === 'semi-truck' ) ? 'checked disabled' : ''; ?>>
					<label class="form-check-label" for="dock_high">Dock High</label>
				</div>
			</div>
		</div>
		
		<!-- ELD Model for Semi truck and Box truck -->
		<div class="row js-eld-section" style="display: none;">
			<div class="col-md-4 mb-3">
				<label class="form-label">ELD Model</label>
				<select class="form-control form-select" name="eld_model">
					<option value="">Select ELD Model</option>
					<option value="PT30" <?php selected( get_field_value( $meta, 'eld_model' ), 'PT30' ); ?>>PT30</option>
				</select>
			</div>
		</div>
		
		<div class="row">
			<div class="col-md-4 mb-3">
				<label class="form-label">Plates</label>
				<input type="text" class="form-control" name="plates" 
					value="<?php echo esc_attr( get_field_value( $meta, 'plates' ) ); ?>">
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
				<label class="form-label">Plates Status</label>
				<select class="form-control form-select" name="plates_status">
					<option value="">Select status</option>
					<option value="Permanent" <?php selected( get_field_value( $meta, 'plates_status' ), 'Permanent' ); ?>>Permanent</option>
					<option value="Temporary" <?php selected( get_field_value( $meta, 'plates_status' ), 'Temporary' ); ?>>Temporary</option>
					<option value="Expired" <?php selected( get_field_value( $meta, 'plates_status' ), 'Expired' ); ?>>Expired</option>
				</select>
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Plates Expiration Date</label>
				<input type="date" class="form-control" name="plates_expiration_date" 
					value="<?php echo esc_attr( get_field_value( $meta, 'plates_expiration_date' ) ); ?>">
			</div>
			
			<div class="col-md-4 mb-3">
				<label class="form-label">Fuel Type</label>
				<select class="form-control form-select" name="fuel_type">
					<option value="">Select fuel type</option>
					<option value="Diesel" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Diesel' ); ?>>Diesel</option>
					<option value="Gasoline" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Gasoline' ); ?>>Gasoline</option>
					<option value="Gasohol" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Gasohol' ); ?>>Gasohol</option>
					<option value="Special-diesel" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Special-diesel' ); ?>>Special-diesel</option>
					<option value="Propane" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Propane' ); ?>>Propane</option>
					<option value="LNG" <?php selected( get_field_value( $meta, 'fuel_type' ), 'LNG' ); ?>>LNG</option>
					<option value="CNG" <?php selected( get_field_value( $meta, 'fuel_type' ), 'CNG' ); ?>>CNG</option>
					<option value="Ethanol" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Ethanol' ); ?>>Ethanol</option>
					<option value="Methanol" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Methanol' ); ?>>Methanol</option>
					<option value="E-85" <?php selected( get_field_value( $meta, 'fuel_type' ), 'E-85' ); ?>>E-85</option>
					<option value="A55" <?php selected( get_field_value( $meta, 'fuel_type' ), 'A55' ); ?>>A55</option>
					<option value="Biodiesel" <?php selected( get_field_value( $meta, 'fuel_type' ), 'Biodiesel' ); ?>>Biodiesel</option>
				</select>
			</div>
		</div>
		
		<div class="row">
			<div class="col-12 mb-3">
				<h5>Documents</h5>
			</div>
			
			<?php
			// Vehicle registration file upload
			$simple_upload_args = [
				'full_only_view' => $full_only_view || ! $vehicle_id,
				'field_name' => 'vehicle_registration',
				'label'      => 'Vehicle Registration',
				'file_value' => $vehicle_registration,
				'popup_id'   => 'popup_upload_vehicle_registration',
				'col_class'  => 'col-12 col-md-4'
			];
			echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
			
			// Fleet Registration ID card file upload
			$simple_upload_args = [
				'full_only_view' => $full_only_view || ! $vehicle_id,
				'field_name' => 'fleet_registration_id_card',
				'label'      => 'Fleet Registration ID card',
				'file_value' => $fleet_registration_id_card,
				'popup_id'   => 'popup_upload_fleet_registration_id_card',
				'col_class'  => 'col-12 col-md-4'
			];
			echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
			
			// Annual Vehicle Inspection file upload
			$simple_upload_args = [
				'full_only_view' => $full_only_view || ! $vehicle_id,
				'field_name' => 'annual_vehicle_inspection',
				'label'      => 'Annual Vehicle Inspection',
				'file_value' => $annual_vehicle_inspection,
				'popup_id'   => 'popup_upload_annual_vehicle_inspection',
				'col_class'  => 'col-12 col-md-4'
			];
			echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
			
			// DOT Inspection - multiple files upload
			$dot_inspection_count = ! empty( $dot_inspection_arr ) ? count( $dot_inspection_arr ) : 0;
			$simple_upload_args = [
				'full_only_view' => $full_only_view || ! $vehicle_id,
				'field_name' => 'dot_inspection',
				'label'      => 'DOT Inspection' . ( $dot_inspection_count > 0 ? ' (' . $dot_inspection_count . ')' : '' ),
				'file_value' => $dot_inspection_count > 0 ? 'multiple' : null,
				'popup_id'   => 'popup_upload_dot_inspection',
				'col_class'  => 'col-12 col-md-4',
				'allow_multiple' => true, // Always show button for multiple file uploads
			];
			echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
			?>
		</div>
		
		<div class="row">
			<div class="col-md-6 mb-3">
				<label class="form-label">Registration Expiration Date</label>
				<input type="date" class="form-control" name="registration_expiration_date" 
					value="<?php echo esc_attr( get_field_value( $meta, 'registration_expiration_date' ) ); ?>">
			</div>
		</div>
		
		<?php if ( $vehicle_id && ! $full_only_view ): ?>
			<div class="row mt-4">
				<div class="col-12 d-flex justify-content-end">
					<button class='btn btn-outline-success js-remote-send-form' data-form="js-vehicle-form">Save</button>
				</div>
			</div>
		<?php endif; ?>
	</form>
</div>

<?php
// Popups for file uploads (only show after vehicle is created)
if ( ! $full_only_view && $vehicle_id ):
	// Vehicle Registration
	$popup_args = [
		'file_name' => 'vehicle_registration',
		'title'     => 'Upload Vehicle Registration',
		'multiply'  => false,
		'vehicle_id' => $vehicle_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-vehicle', 'file', $popup_args ) );
	
	// Fleet Registration ID card
	$popup_args = [
		'file_name' => 'fleet_registration_id_card',
		'title'     => 'Upload Fleet Registration ID card',
		'multiply'  => false,
		'vehicle_id' => $vehicle_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-vehicle', 'file', $popup_args ) );
	
	// Annual Vehicle Inspection
	$popup_args = [
		'file_name' => 'annual_vehicle_inspection',
		'title'     => 'Upload Annual Vehicle Inspection',
		'multiply'  => false,
		'vehicle_id' => $vehicle_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-vehicle', 'file', $popup_args ) );
	
	// DOT Inspection (multiple files)
	$popup_args = [
		'file_name' => 'dot_inspection',
		'title'     => 'Upload DOT Inspection',
		'multiply'  => true,
		'vehicle_id' => $vehicle_id,
	];
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload-vehicle', 'file', $popup_args ) );
endif;
?>

<?php get_footer(); ?>
