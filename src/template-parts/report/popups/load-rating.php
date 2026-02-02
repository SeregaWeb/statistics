<?php
/**
 * Load rating popup – rate driver for a specific load (fixed load + driver, no selection).
 * Included from report-table.php when user has rating access.
 */

?>
<!-- Load Rating Popup (from loads table) -->
<div class="popup" id="load-rating-popup">
	<div class="my_overlay js-popup-close"></div> 
	<div class="popup__wrapper-inner">
		<div class="popup-container"> 
				<h3 class="mb-3"><?php esc_html_e( 'Rate driver for this load', 'wp-rock' ); ?></h3>
				<div class="mb-3">
					<form id="loadRatingForm" class="row g-3">
						<input type="hidden" name="driver_id" id="loadRatingDriverId" value="">
						<input type="hidden" name="load_number" id="loadRatingLoadNumber" value="">
						<input type="hidden" id="loadRatingLoadStatus" value="">
						<?php wp_nonce_field( 'tms_add_rating', 'tms_rating_nonce' ); ?>
						<div class="col-12">
							<label class="form-label"><?php esc_html_e( 'Load number', 'wp-rock' ); ?></label>
							<p class="form-control-plaintext fw-semibold mb-0" id="loadRatingLoadDisplay">—</p>
						</div>
						<div class="col-12">
							<label class="form-label"><?php esc_html_e( 'Driver', 'wp-rock' ); ?></label>
							<p class="form-control-plaintext fw-semibold mb-0" id="loadRatingDriverDisplay">—</p>
						</div>
						<div class="col-12">
							<label class="form-label"><?php esc_html_e( 'Select Rating', 'wp-rock' ); ?></label>
							<div class="d-flex gap-2 flex-wrap">
								<?php
								$get_rating_btn_color = function ( $value ) {
									if ( (int) $value <= 1 ) {
										return 'btn-outline-danger';
									}
									if ( (int) $value <= 4 ) {
										return 'btn-outline-warning';
									}
									return 'btn-outline-success';
								};
								for ( $i = 1; $i <= 5; $i++ ) :
									?>
									<button type="button" class="btn <?php echo esc_attr( $get_rating_btn_color( $i ) ); ?> load-rating-btn" data-rating="<?php echo (int) $i; ?>">
										<?php echo (int) $i; ?>
									</button>
								<?php endfor; ?>
							</div>
							<input type="hidden" name="rating" id="loadRatingSelectedRating" value="" required>
						</div>
						<div class="col-12">
							<label for="loadRatingComments" class="form-label"><?php esc_html_e( 'Comments', 'wp-rock' ); ?></label>
							<textarea class="form-control" id="loadRatingComments" name="comments" required rows="2"></textarea>
						</div>
						<div class="col-12">
							<button type="submit" class="btn btn-success btn-sm"><?php esc_html_e( 'Add Rating', 'wp-rock' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
