<?php
/**
 * Single payment file card for Accounting tab.
 * Used in report-tab-accounting.php; same style as Documents tab cards (view + download, no delete).
 *
 * Expected in get_query_var('args'):
 * - payment_file: array with url, is_image, account_type, account_name, payment_instruction
 * - driver_name: string (unit number / driver name for label)
 * - reports: TMSReports instance for icons
 */

// Data can come from get_template_part() 3rd param (extract) or from set_query_var( 'args', ... ).
if ( isset( $payment_file ) && isset( $reports ) ) {
	$driver_name = isset( $driver_name ) ? $driver_name : '';
} else {
	$card_args    = get_query_var( 'args' );
	$payment_file = isset( $card_args['payment_file'] ) && is_array( $card_args['payment_file'] ) ? $card_args['payment_file'] : null;
	$driver_name  = isset( $card_args['driver_name'] ) ? $card_args['driver_name'] : '';
	$reports      = isset( $card_args['reports'] ) ? $card_args['reports'] : null;
}

if ( ! $payment_file || empty( $payment_file['url'] ) || ! $reports ) {
	return;
}

?>
<div class="card-upload payment-file justify-content-center">
	<button type="button" class="view-document js-payment-file-preview" title="<?php esc_attr_e( 'View payment file', 'wp-rock' ); ?>"
			data-url="<?php echo esc_url( $payment_file['url'] ); ?>"
			data-is-image="<?php echo ! empty( $payment_file['is_image'] ) ? '1' : '0'; ?>"
			data-driver-name="<?php echo esc_attr( $driver_name ); ?>"
			data-account-type="<?php echo esc_attr( $payment_file['account_type'] ?? '' ); ?>"
			data-account-name="<?php echo esc_attr( $payment_file['account_name'] ?? '' ); ?>"
			data-payment-instruction="<?php echo esc_attr( $payment_file['payment_instruction'] ?? '' ); ?>"
			aria-label="<?php esc_attr_e( 'View payment file', 'wp-rock' ); ?>"><?php echo $reports->get_icon_view( 'view' ); ?></button>
	<span class="required-label"><?php echo esc_html( $driver_name ); ?></span>
	<figure class="card-upload__figure m-0">
		<?php if ( ! empty( $payment_file['is_image'] ) ) : ?>
			<img class="card-upload__img" src="<?php echo esc_url( $payment_file['url'] ); ?>" alt="">
		<?php else : ?>
			<?php echo $reports->get_file_icon(); ?>
			<p class="mb-0"><?php esc_html_e( 'Payment file', 'wp-rock' ); ?></p>
		<?php endif; ?>
	</figure>
	<a class="card-upload__btn" download href="<?php echo esc_url( $payment_file['url'] ); ?>">
		<?php echo $reports->get_download_icon(); ?>
	</a>
</div>
