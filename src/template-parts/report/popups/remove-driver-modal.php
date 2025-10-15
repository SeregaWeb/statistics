<?php
/**
 * Remove Driver Modal (Soft Delete)
 *
 * @package WP-rock
 */
?>
<div class="modal fade" id="removeDriverModal" tabindex="-1" aria-labelledby="removeDriverModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="removeDriverModalLabel">Remove Driver</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form class="js-remove-driver-form">
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Reason</label>
						<input type="text" name="reason" class="form-control" required maxlength="200" placeholder="Short reason" />
					</div>
					<div class="mb-3">
						<label class="form-label">Notes</label>
						<textarea name="notes" class="form-control" rows="3" placeholder="Additional explanation (optional)"></textarea>
					</div>
					<div class="form-check">
						<input class="form-check-input" type="checkbox" id="removeDriverNotify" name="notify" value="1" />
						<label class="form-check-label" for="removeDriverNotify">Notify</label>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-danger">Remove</button>
				</div>
			</form>
		</div>
	</div>
</div>
