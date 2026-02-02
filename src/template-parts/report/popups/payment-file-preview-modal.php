<?php
/**
 * Payment file preview modal: 90vw x 90vh, driver finance params under header.
 * Used on accounting table page; opened by .js-payment-file-preview buttons.
 */
?>

<div class="modal fade payment-file-preview-modal" id="paymentFilePreviewModal" tabindex="-1" aria-labelledby="paymentFilePreviewModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentFilePreviewModalLabel"><?php esc_html_e( 'Payment file', 'wp-rock' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'wp-rock' ); ?>"></button>
			</div>
			<div id="paymentFilePreviewDriverInfo" class="p-1 border-bottom small text-muted" style="display: none;">
				<div class="row g-2">
					<div class="col-md-4"><strong><?php esc_html_e( 'Account Type', 'wp-rock' ); ?>:</strong> <span id="paymentFilePreviewAccountType">—</span></div>
					<div class="col-md-4"><strong><?php esc_html_e( 'Account Name', 'wp-rock' ); ?>:</strong> <span id="paymentFilePreviewAccountName">—</span></div>
					<div class="col-md-4"><strong><?php esc_html_e( 'Payment Instruction', 'wp-rock' ); ?>:</strong> <span id="paymentFilePreviewPaymentInstruction">—</span></div>
				</div>
			</div>
			<div class="modal-body p-0 d-flex align-items-center justify-content-center payment-file-preview-body">
				<iframe id="paymentFilePreviewIframe" class="d-none" title="<?php esc_attr_e( 'Payment file preview', 'wp-rock' ); ?>"></iframe>
				<img id="paymentFilePreviewImg" class="d-none img-fluid" alt="<?php esc_attr_e( 'Payment file', 'wp-rock' ); ?>">
				<p id="paymentFilePreviewFallback" class="p-3 text-muted d-none"><?php esc_html_e( 'Preview not available. Open link in new tab.', 'wp-rock' ); ?></p>
			</div>
			<div class="modal-footer">
				<a id="paymentFilePreviewLink" href="#" target="_blank" rel="noopener" class="btn btn-outline-primary"><?php esc_html_e( 'Open in new tab', 'wp-rock' ); ?></a>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'wp-rock' ); ?></button>
			</div>
		</div>
	</div>
</div>
