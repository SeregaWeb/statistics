<?php
/**
 * Template Name: Page trailers
 * 
 * Page to view all trailers
 */

 global $global_options;
// Get API key from global options
$can_add_new_trailer = get_field_value( $global_options, 'add_new_trailer' );

$trailer = new TMSTrailers();
$helper = new TMSReportsHelper();
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

// Get filters
$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

// Get trailers
$args = array(
	'status' => $status,
	'search' => $search
);

$trailers_data = $trailer->get_trailers( $args );

get_header(); ?>

<div class="container">
	<div class="row">
		<div class="col-12">
			<h1>Trailers</h1>
		</div>
	</div>
	
	<div class="row mt-3">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<div class="row">
						<div class="col-md-6">
							<?php if ( $can_add_new_trailer && $can_create_edit ): ?>
								<a href="<?php echo esc_url( $can_add_new_trailer ); ?>" class="btn btn-primary">
									Add New Trailer
								</a>
							<?php endif; ?>
						</div>
						<div class="col-md-6">
							<form method="get" class="d-flex gap-2">
								<input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
								<input type="text" class="form-control" name="search" placeholder="Search by trailer number or license plate" 
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
					<?php if ( ! empty( $trailers_data['trailers'] ) ): ?>
						<div class="table-responsive">
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Trailer Number</th>
										<th>Type</th>
										<th>License Plate</th>
										<th>License State</th>
										<th>VIN</th>
										<th>Make</th>
										<th>Year</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $trailers_data['trailers'] as $trailer_item ): ?>
										<?php
										$item_main = get_field_value( $trailer_item, 'main' );
										$item_meta = get_field_value( $trailer_item, 'meta' );
										$trailer_id = get_field_value( $item_main, 'id' );
										?>
										<tr>
											<td><?php echo esc_html( get_field_value( $item_meta, 'trailer_number' ) ); ?></td>
											<td><?php echo esc_html( ucfirst( str_replace( '-', ' ', get_field_value( $item_meta, 'trailer_type' ) ) ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'license_plate' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'license_state' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'vin' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'make' ) ); ?></td>
											<td><?php echo esc_html( get_field_value( $item_meta, 'year' ) ); ?></td>
											
											<td width="142px">
                                                            <div class="d-flex gap-2 justify-content-end">
												<a href="<?php echo esc_url( add_query_arg( 'trailer', $trailer_id, $can_add_new_trailer ) ); ?>" 
													class="btn btn-sm btn-primary">

                                                                 <?php echo !$can_create_edit ? 'View' : 'Edit'; ?>
                                                                 </a>
												<?php if ( $is_admin ): ?>
													<button type="button" class="btn btn-sm btn-danger js-delete-trailer" 
														data-trailer-id="<?php echo esc_attr( $trailer_id ); ?>">Delete</button>
												<?php endif; ?>
                                                            </div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						
						<?php if ( $trailers_data['total_pages'] > 1 ): ?>
							<?php
							get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
								'total_pages'  => $trailers_data['total_pages'],
								'current_page' => $trailers_data['current_page'],
							) );
							?>
						<?php endif; ?>
					<?php else: ?>
						<div class="alert alert-info">
							No trailers found. <a href="<?php echo esc_url( $can_add_new_trailer ); ?>">Add your first trailer</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php 
// Add nonce for AJAX requests (hidden input)
$nonce = wp_create_nonce('trailer_nonce');
echo '<input type="hidden" name="nonce" value="' . esc_attr($nonce) . '" id="trailer-nonce">';
get_footer(); 
?>
