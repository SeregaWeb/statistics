<?php
/**
 * Template Name: Page vehicles
 * 
 * Page to view all vehicles
 */

global $global_options;
// Get API key from global options
$can_add_new_vehicle = get_field_value( $global_options, 'add_new_vehicle' );

$vehicle = new TMSVehicles();
$helper = new TMSReportsHelper();
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

// Get filters
$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

// Get vehicles
$args = array(
	'status' => $status,
	'search' => $search
);

$vehicles_data = $vehicle->get_vehicles( $args );

// Batch-load drivers for attached_driver (one query for all vehicles on the page)
$drivers_by_id = array();
if ( ! empty( $vehicles_data['vehicles'] ) ) {
	$driver_ids = array();
	foreach ( $vehicles_data['vehicles'] as $vehicle_item ) {
		$item_meta = get_field_value( $vehicle_item, 'meta' );
		$item_meta = is_array( $item_meta ) ? $item_meta : array();
		$attached  = get_field_value( $item_meta, 'attached_driver' );
		if ( ! empty( $attached ) && is_numeric( $attached ) ) {
			$driver_ids[] = (int) $attached;
		}
	}
	$driver_ids = array_unique( array_filter( $driver_ids ) );
	if ( ! empty( $driver_ids ) ) {
		$drivers        = new TMSDrivers();
		$drivers_list   = $drivers->get_drivers_by_ids( $driver_ids );
		$driver_results = isset( $drivers_list['results'] ) && is_array( $drivers_list['results'] ) ? $drivers_list['results'] : array();
		foreach ( $driver_results as $dr ) {
			$meta       = get_field_value( $dr, 'meta_data' );
			$meta       = is_array( $meta ) ? $meta : array();
			$did        = isset( $dr['id'] ) ? (int) $dr['id'] : 0;
			$show_phone = get_field_value( $meta, 'show_phone' );
			$show_phone = ( $show_phone && in_array( $show_phone, array( 'driver_phone', 'team_driver_phone', 'owner_phone' ), true ) ) ? $show_phone : 'driver_phone';
			$drivers_by_id[ $did ] = array(
				'id'           => $did,
				'driver_name'  => get_field_value( $meta, 'driver_name' ) ?: '',
				'driver_phone' => get_field_value( $meta, $show_phone ) ?: '',
				'driver_email' => get_field_value( $meta, 'driver_email' ) ?: '',
			);
		}
	}
}

$access_copy_email = $TMSUsers->check_user_role_access( array( 'administrator', 'recruiter', 'recruiter-tl', 'driver_updates' ), true );

get_header(); ?>

<div class="container">
	<div class="row">
		<div class="col-12">
			<h1>Vehicles</h1>
		</div>
	</div>
	
	<div class="row mt-3">
		<div class="col-12">
			<div class="card">
				<div class="card-header navbar-sticky-custom">
					<div class="row">
						<div class="col-md-6">
							<?php if ( $can_add_new_vehicle && $can_create_edit ): ?>
								<a href="<?php echo esc_url( $can_add_new_vehicle ); ?>" class="btn btn-primary">
									Add New Vehicle
								</a>
							<?php endif; ?>
						</div>
						<div class="col-md-6">
							<form method="get" class="d-flex gap-2">
								<input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
								<input type="text" class="form-control" name="search" placeholder="Search by VIN, plates or make" 
									value="<?php echo esc_attr( $search ); ?>">
								<button type="submit" class="btn btn-secondary">Search</button>
								<?php if ( $search || $status ): ?>
									<a href="<?php echo esc_url( remove_query_arg( array( 'search', 'status' ) ) ); ?>" class="btn btn-outline-secondary">Clear</a>
								<?php endif; ?>
							</form>
						</div>
					</div>
				</div>
				<div class="card-body">
					<?php if ( ! empty( $vehicles_data['vehicles'] ) ): ?>
						<div class="table-responsive">
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Type</th>
										<th>Make</th>
										<th>Model</th>
										<th>Year</th>
										<th>VIN</th>
										<th>Driver</th>
										<th>Plates</th>
										<th>License State</th>
										<th>Plates Status</th>
										<th>Additional</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $vehicles_data['vehicles'] as $vehicle_item ): ?>
										<?php
										$item_main  = get_field_value( $vehicle_item, 'main' );
										$item_meta  = get_field_value( $vehicle_item, 'meta' );
										$item_meta  = is_array( $item_meta ) ? $item_meta : array();
										$vehicle_id = get_field_value( $item_main, 'id' );
										
										// Prepare vehicle capabilities array
										$vehicle_capabilities = array(
											'dock-high.svg' => get_field_value( $item_meta, 'dock_high' ),
										);
										
										// Get capabilities icons
										$array_additionals = $helper->get_capabilities( $vehicle_capabilities );
										?>
										<tr>
											<td><?php echo esc_html( ucfirst( str_replace( '-', ' ', get_field_value( $item_meta, 'vehicle_type' ) ) ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'make' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'model' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'vehicle_year' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'vin' ) ); ?></td>
											<td>
												<?php
												$attached_driver_id = get_field_value( $item_meta, 'attached_driver' );
												$attached_driver_id = ! empty( $attached_driver_id ) && is_numeric( $attached_driver_id ) ? (int) $attached_driver_id : 0;
												$driver_info       = $attached_driver_id && isset( $drivers_by_id[ $attached_driver_id ] ) ? $drivers_by_id[ $attached_driver_id ] : null;
												if ( $driver_info && is_array( $driver_info ) ) :
													?>
													<div class="d-flex flex-column">
														<div class="d-flex align-items-center gap-1">
															<?php echo '(' . (int) $driver_info['id'] . ') ' . esc_html( $driver_info['driver_name'] ); ?>
														</div>
														<?php if ( ! empty( $driver_info['driver_phone'] ) ) : ?>
															<span class="text-small driver-phone" data-phone="<?php echo esc_attr( $driver_info['driver_phone'] ); ?>"><?php echo esc_html( $driver_info['driver_phone'] ); ?></span>
														<?php endif; ?>
														<?php if ( $access_copy_email && ! empty( $driver_info['driver_email'] ) ) : ?>
															<span class="text-small driver-email" data-email="<?php echo esc_attr( $driver_info['driver_email'] ); ?>"><?php echo esc_html( $driver_info['driver_email'] ); ?></span>
														<?php endif; ?>
													</div>
												<?php else : ?>
													—
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'plates' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'license_state' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'plates_status' ) ); ?></td>
											<td>
												<div class="table-tags d-flex flex-wrap">
													<?php if ( ! empty( $array_additionals ) ): ?>
														<?php
														// Mapping of capability icons to human-readable names
														$capability_names = array(
															'dock-high.svg' => 'Dock High',
														);
														
														foreach ( $array_additionals as $icon_url ):
															// Extract filename from URL
															$filename = basename( $icon_url );
															
															// Get human-readable name
															$tooltip_text = isset( $capability_names[ $filename ] ) ? $capability_names[ $filename ] : $filename;
															?>
															<img width="34" height="34" 
																 src="<?php echo esc_url( $icon_url ); ?>" 
																 alt="<?php echo esc_attr( $tooltip_text ); ?>"
																 title="<?php echo esc_attr( $tooltip_text ); ?>"
																 data-bs-toggle="tooltip" 
																 data-bs-placement="top">
														<?php endforeach; ?>
													<?php endif; ?>
												</div>
											</td>
											<td width="142px">
												<div class="d-flex gap-2 justify-content-end">
													<a href="<?php echo esc_url( add_query_arg( 'vehicle', $vehicle_id, $can_add_new_vehicle ) ); ?>" 
														class="btn btn-sm btn-primary">
														<?php echo !$can_create_edit ? 'View' : 'Edit'; ?>
													</a>
													<?php if ( $is_admin ): ?>
														<button type="button" class="btn btn-sm btn-danger js-delete-vehicle" 
															data-vehicle-id="<?php echo esc_attr( $vehicle_id ); ?>">Delete</button>
													<?php endif; ?>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						
						<?php if ( $vehicles_data['total_pages'] > 1 ): ?>
							<?php
							get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
								'total_pages'  => $vehicles_data['total_pages'],
								'current_page' => $vehicles_data['current_page'],
							) );
							?>
						<?php endif; ?>
					<?php else: ?>
						<div class="alert alert-info">
							No vehicles found. <a href="<?php echo esc_url( $can_add_new_vehicle ); ?>">Add your first vehicle</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php 
// Add nonce for AJAX requests (hidden input)
$nonce = wp_create_nonce('vehicle_nonce');
echo '<input type="hidden" name="nonce" value="' . esc_attr($nonce) . '" id="vehicle-nonce">';
get_footer(); 
?>
