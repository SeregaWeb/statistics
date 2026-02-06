<?php
/**
 * Driver Statistics content for the popup opened from load tables (report-table, report-table-tracking).
 * No Bootstrap modals for rating/notice — forms are inline; switching views inside the same popup.
 * No "Exclude from auto blocking" block.
 *
 * Expects $args: report_object, post_id, full_view_only (not used here; buttons always enabled).
 */

$object_driver = get_field_value( $args, 'report_object' );
$post_id       = get_field_value( $args, 'post_id' );

$driver = new TMSDrivers();
$helper = new TMSReportsHelper();
$users = new TMSUsers();

$access_driver_money = $users->check_user_role_access( array(
	'administrator',
	'accounting',
	'billing',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'expedite_manager',
	'dispatcher-tl',
), true );

$main         = get_field_value( $object_driver, 'main' );
$meta         = get_field_value( $object_driver, 'meta' );
$driver_name  = get_field_value( $meta, 'driver_name' );
$driver_statistics = $driver->get_driver_statistics( $post_id, true );

$get_button_color = function( $value ) {
	if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
		return 'text-secondary';
	}
	if ( (float) $value <= 1 ) {
		return 'text-danger';
	}
	if ( (float) $value <= 4 ) {
		return 'text-warning';
	}
	return 'text-success';
};
$get_total_color = function( $value ) {
	if ( $value == 0 ) {
		return 'text-secondary';
	}
	if ( $value <= 5 ) {
		return 'text-warning';
	}
	return 'text-success';
};
$get_rating_color = function( $value ) {
	if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
		return 'bg-secondary';
	}
	if ( (float) $value <= 1 ) {
		return 'bg-danger';
	}
	if ( (float) $value <= 4 ) {
		return 'bg-warning';
	}
	return 'bg-success';
};
$get_rating_btn_color = function( $value ) {
	if ( (float) $value <= 1 ) {
		return 'btn-outline-danger';
	}
	if ( (float) $value <= 4 ) {
		return 'btn-outline-warning';
	}
	return 'btn-outline-success';
};
?>
<div class="js-driver-stats-popup-root container mt-2 pb-3" data-driver-id="<?php echo esc_attr( (string) $post_id ); ?>">

	<ul class="nav nav-tabs mb-3" id="driver-stats-popup-tabs" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" id="driver-popup-tab-stats-btn" data-bs-toggle="tab" data-bs-target="#driver-popup-tab-stats" type="button" role="tab" aria-controls="driver-popup-tab-stats" aria-selected="true"><?php esc_html_e( 'Driver Statistics & Rating', 'wp-rock' ); ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="driver-popup-tab-contact-btn" data-bs-toggle="tab" data-bs-target="#driver-popup-tab-contact" type="button" role="tab" aria-controls="driver-popup-tab-contact" aria-selected="false"><?php esc_html_e( 'Owner & Drivers Information', 'wp-rock' ); ?></button>
		</li>
	</ul>

	<div class="tab-content" id="driver-stats-popup-tab-content">
		<div class="tab-pane fade show active" id="driver-popup-tab-stats" role="tabpanel" aria-labelledby="driver-popup-tab-stats-btn">
			<!-- Stats view (default visible) -->
			<div class="js-driver-stats-popup-stats">
		<div class="row">
			<div class="col-md-6 mb-4">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0">Rating</h5>
						<button type="button" class="btn btn-primary btn-sm js-driver-stats-popup-show-rating">Add Rating</button>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-6">
								<h6>Average Rating</h6>
								<?php $avg_rating = $driver_statistics['rating']['avg_rating']; ?>
								<div class="display-4 <?php echo esc_attr( $get_button_color( $avg_rating ) ); ?>"><?php echo esc_html( $avg_rating ); ?></div>
							</div>
							<div class="col-6">
								<h6>Total Ratings</h6>
								<?php $total_ratings = $driver_statistics['rating']['count']; ?>
								<div class="display-4 <?php echo esc_attr( $get_total_color( $total_ratings ) ); ?>"><?php echo esc_html( (string) $total_ratings ); ?></div>
							</div>
						</div>
						<?php if ( ! empty( $driver_statistics['rating']['data'] ) ) : ?>
							<div class="mt-3">
								<h6>Recent Ratings</h6>
								<div class="table-responsive">
									<table class="table table-sm">
										<thead><tr><th>Name</th><th>Date</th><th>Rating</th><th>Comment</th></tr></thead>
										<tbody>
											<?php
											$ratings_list = $driver_statistics['rating']['data'];
											$row_index   = 0;
											foreach ( array_slice( $ratings_list, 0, 5 ) as $rating ) :
												?>
												<tr>
													<td><?php echo esc_html( $rating['name'] ); ?></td>
													<td><?php echo esc_html( date( 'm/d/Y g:i a', $rating['time'] ) ); ?></td>
													<td><span class="badge <?php echo esc_attr( $get_rating_color( $rating['reit'] ) ); ?>"><?php echo esc_html( $rating['reit'] ); ?></span></td>
													<td><?php echo esc_html( $rating['order_number'] ?? '' ); ?> <?php echo stripslashes( $rating['message'] ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						<?php else : ?>
							<p class="text-muted mt-3">No ratings yet.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="col-md-6 mb-4">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0">Notifications</h5>
						<button type="button" class="btn btn-primary btn-sm js-driver-stats-popup-show-notice">Add Notice</button>
					</div>
					<div class="card-body">
						<h6>Total Notifications</h6>
						<div class="display-4 text-info"><?php echo esc_html( (string) $driver_statistics['notice']['count'] ); ?></div>
						<?php if ( ! empty( $driver_statistics['notice']['data'] ) ) : ?>
							<div class="mt-3">
								<h6>Recent Notifications</h6>
								<div class="table-responsive">
									<table class="table table-sm">
										<thead><tr><th>Name</th><th>Date</th><th>Message</th></tr></thead>
										<tbody>
											<?php foreach ( array_slice( $driver_statistics['notice']['data'], 0, 5 ) as $notice ) : ?>
												<tr class="<?php echo (int) $notice['status'] === 1 ? 'table-success' : ''; ?>">
													<td><?php echo esc_html( $notice['name'] ); ?></td>
													<td><?php echo esc_html( date( 'm/d/Y g:i a', $notice['date'] ) ); ?></td>
													<td><?php echo esc_html( $notice['message'] ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						<?php else : ?>
							<p class="text-muted mt-3">No notifications yet.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php if ( $access_driver_money ) : ?>
		<div class="row">
			<div class="col-12"><h2 class="mb-3">Driver Statistics</h2></div>
		</div>
		<div class="row mb-4" id="driver-statistics-container">
			<div class="col-12">
				<div class="text-center">
					<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
					<p class="mt-2">Loading driver statistics...</p>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<input type="hidden" id="driver-statistics-nonce" value="<?php echo esc_attr( wp_create_nonce( 'driver_statistics_nonce' ) ); ?>">
	</div>

	<!-- Rating form view (hidden by default) -->
	<div class="js-driver-stats-popup-rating-panel d-none">
		<h5 class="mb-3"><?php echo esc_html( $driver_name ); ?> – Add Rating</h5>
		<form class="js-driver-stats-popup-rating-form">
			<input type="hidden" name="driver_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
			<?php wp_nonce_field( 'tms_add_rating', 'tms_rating_nonce' ); ?>
			<div class="mb-3">
				<label for="js-driver-stats-popup-load-number" class="form-label">Load number</label>
				<select class="form-control js-driver-stats-popup-load-select" id="js-driver-stats-popup-load-number" required name="load_number">
					<option value="">Select a load...</option>
					<option value="Canceled">Canceled</option>
				</select>
				<div class="form-text"><small class="text-muted js-driver-stats-popup-loads-info">Loading available loads...</small></div>
			</div>
			<div class="mb-3">
				<label class="form-label">Select Rating</label>
				<div class="d-flex gap-2">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<button type="button" class="btn <?php echo esc_attr( $get_rating_btn_color( $i ) ); ?> rating-btn js-driver-stats-popup-rating-btn" data-rating="<?php echo (int) $i; ?>"><?php echo (int) $i; ?></button>
					<?php endfor; ?>
				</div>
				<input type="hidden" name="rating" class="js-driver-stats-popup-selected-rating" required>
			</div>
			<div class="mb-3">
				<label for="js-driver-stats-popup-comments" class="form-label">Comments</label>
				<textarea class="form-control" id="js-driver-stats-popup-comments" name="comments" required rows="3"></textarea>
			</div>
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-secondary js-driver-stats-popup-back">Back</button>
				<button type="submit" class="btn btn-primary">Add</button>
			</div>
		</form>
	</div>

	<!-- Notice form view (hidden by default) -->
	<div class="js-driver-stats-popup-notice-panel d-none">
		<h5 class="mb-3"><?php echo esc_html( $driver_name ); ?> – Add Notice</h5>
		<form class="js-driver-stats-popup-notice-form">
			<input type="hidden" name="driver_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
			<?php wp_nonce_field( 'tms_add_notice', 'tms_notice_nonce' ); ?>
			<div class="mb-3">
				<label for="js-driver-stats-popup-message" class="form-label">Comments</label>
				<textarea class="form-control" id="js-driver-stats-popup-message" name="message" rows="4" required></textarea>
			</div>
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-secondary js-driver-stats-popup-back">Back</button>
				<button type="submit" class="btn btn-primary">Add</button>
			</div>
		</form>
	</div>
		</div><!-- .tab-pane #driver-popup-tab-stats -->

		<div class="tab-pane fade" id="driver-popup-tab-contact" role="tabpanel" aria-labelledby="driver-popup-tab-contact-btn">
			<?php get_template_part( TEMPLATE_PATH . 'tabs/driver-tab-contact', 'display', $args ); ?>
		</div>
	</div><!-- .tab-content -->
</div>
