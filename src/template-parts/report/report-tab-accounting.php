<?php

$reports = new TMSReports();

// tab 5

$invoices           = $reports->get_invoices();
$factoring_statuses = $reports->get_factoring_status();

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;

$bank_statuses       = $reports->get_bank_statuses();
$driver_pay_statuses = $reports->get_driver_payment_statuses();
$quick_pay_methods   = $reports->get_quick_pay_methods();

if ( $report_object ) {
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		
		$bank_payment_st         = get_field_value( $meta, 'bank_payment_status' );
		$driver_pay_st           = get_field_value( $meta, 'driver_pay_statuses' );
		$quick_pay_accounting    = get_field_value( $meta, 'quick_pay_accounting' );
		$quick_pay_method        = get_field_value( $meta, 'quick_pay_method' );
		$quick_pay_driver_amount = get_field_value( $meta, 'quick_pay_driver_amount' );
		$driver_rate             = get_field_value( $meta, 'driver_rate' );
	}
}

?>

<h3 class="p-0 display-6 mb-4">Accounting info</h3>

<form class="js-uploads-accounting d-grid">
    <div class="row">

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="bank_payment_status" class="form-label">Bank status</label>
            <select name="bank_payment_status" class="form-control form-select">
                <option value="">Select status</option>
				<?php if ( is_array( $bank_statuses ) ): ?>
					<?php foreach ( $bank_statuses as $key => $status ): ?>
                        <option <?php echo $bank_payment_st === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="driver_pay_statuses" class="form-label">Driver pay status</label>
            <select name="driver_pay_statuses" class="form-control form-select">
                <option value="">Select status</option>
				<?php if ( is_array( $driver_pay_statuses ) ): ?>
					<?php foreach ( $driver_pay_statuses as $key => $status ): ?>
                        <option <?php echo $driver_pay_st === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>


        <div class="col-12"></div>
        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <div class="form-check form-switch">
                <input class="form-check-input js-switch-toggle"
                       data-toggle="js-quick-actions" <?php echo $quick_pay_accounting ? 'checked' : ''; ?>
                       name="quick_pay_accounting" type="checkbox" id="quick_pay_accounting">
                <label class="form-check-label" for="invoiced_proof">Quick pay?</label>
            </div>
        </div>

        <div class="col-12 js-quick-actions <?php echo $quick_pay_accounting ? '' : 'd-none'; ?>">

            <div class="row">
                <div class="mb-2 col-12 col-md-6 col-xl-4">
                    <label for="ar_status" class="form-label">Quick pay method</label>
                    <select name="quick_pay_method" class="form-control form-select js-quick-pay-method">
                        <option value="">Select method</option>
						<?php if ( is_array( $quick_pay_methods ) ): ?>
							<?php foreach ( $quick_pay_methods as $key => $status ): ?>
                                <option <?php echo $quick_pay_method === $key ? 'selected' : ''; ?>
                                        value="<?php echo $key; ?>">
									<?php echo $status[ 'label' ]; ?> ( <?php echo $status[ 'value' ]; ?>% )
									
									<?php if ( floatval( $status[ 'commission' ] ) > 0 ): ?>
                                        commission
										<?php if ( floatval( $status[ 'commission' ] ) < 1 ): ?>
											<?php echo $status[ 'commission' ] * 100; ?>¢
										<?php else: ?>
                                            $<?php echo $status[ 'commission' ]; ?>
										<?php endif; ?>
									<?php endif; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif ?>
                    </select>
                    <p class="text-medium text-center mt-1">$<?php echo $driver_rate; ?> without quick pay percent</p>
                </div>
				
				<?php if ( is_array( $quick_pay_methods ) ): ?>
					<?php foreach ( $quick_pay_methods as $key => $status ): ?>
                        <input type="hidden" data-reit="<?php echo $driver_rate; ?>"
                               data-commission="<?php echo $status[ 'commission' ]; ?>"
                               class="js-select-quick-<?php echo $key; ?>" value="<?php echo $status[ 'value' ]; ?>">
					<?php endforeach; ?>
				<?php endif ?>

                <div class="mb-2 col-12 col-md-6 col-xl-4">
                    <!-- gross - this percent -->
                    <label for="quick_pay_driver_amount" class="form-label">
                        Will charge the driver </label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" readonly value="<?php echo $quick_pay_driver_amount; ?>"
                               name="quick_pay_driver_amount"
                               class="form-control js-money js-quick-pay-driver">
                    </div>
	                <?php if (is_numeric($quick_pay_driver_amount)): ?>
                        <p class="text-medium js-sum-after-count text-center mt-1">Sum to pay $<?php echo $driver_rate - $quick_pay_driver_amount; ?></p>
	                <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 mt-4 order-5" role="presentation">
            <div class="justify-content-start gap-2">
                <button type="button" data-tab-id="pills-billing-tab"
                        class="btn btn-dark js-next-tab">Previous
                </button>
                <button type="submit" class="btn btn-primary js-submit-and-next-tab">Update
                </button>
            </div>
        </div>
    </div>
	<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php endif; ?>
</form>