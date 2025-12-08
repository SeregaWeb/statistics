<?php

$reports  = new TMSReports();
$TMSUsers = new TMSUsers();
// tab 5

$invoices            = $reports->get_invoices();
$factoring_statuses  = $reports->get_factoring_status();
$report_object       = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id             = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;
$flt                 = ! empty( $args[ 'flt' ] ) ? $args[ 'flt' ] : null;
$project             = ! empty( $args[ 'project' ] ) ? $args[ 'project' ] : null;
$bank_statuses       = $reports->get_bank_statuses();
$driver_pay_statuses = $reports->get_driver_payment_statuses();
$quick_pay_methods   = $reports->get_quick_pay_methods();

$bank_payment_st = $driver_pay_st = $quick_pay_accounting = $quick_pay_method = $quick_pay_driver_amount = $driver_rate = null;

// Second driver variables
$second_bank_payment_st = $second_driver_pay_st = $second_quick_pay_accounting = $second_quick_pay_method = $second_quick_pay_driver_amount = $second_driver_rate = null;
$second_driver = $second_unit_number_name = null;

// Third driver variables
$third_bank_payment_st = $third_driver_pay_st = $third_quick_pay_accounting = $third_quick_pay_method = $third_quick_pay_driver_amount = $third_driver_rate = null;
$third_driver = $third_unit_number_name = null;

$log_file_isset   = false;
$factoring_status = '';
$ar_status        = '';
$driver_pay_st    = '';
$short_pay        = '';
$rc_proof         = false;
$pod_proof        = false;
$invoiced_proof   = false;
$processing       = '';
$type_pay         = '';
$type_pay_method  = '';

if ( $report_object ) {
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		$log_file_isset          = get_field_value( $meta, 'log_file' );
		$factoring_status        = get_field_value( $meta, 'factoring_status' );
		$bank_payment_st         = get_field_value( $meta, 'bank_payment_status' );
		$driver_pay_st           = get_field_value( $meta, 'driver_pay_statuses' );
		$quick_pay_accounting    = get_field_value( $meta, 'quick_pay_accounting' );
		$quick_pay_method        = get_field_value( $meta, 'quick_pay_method' );
		$quick_pay_driver_amount = get_field_value( $meta, 'quick_pay_driver_amount' );
		$driver_rate             = get_field_value( $meta, 'driver_rate' );
		
		// Second driver data
		$second_driver              = get_field_value( $meta, 'second_driver' );
		$second_unit_number_name    = get_field_value( $meta, 'second_unit_number_name' );
		$second_bank_payment_st     = get_field_value( $meta, 'second_bank_payment_status' );
		$second_driver_pay_st       = get_field_value( $meta, 'second_driver_pay_statuses' );
		$second_quick_pay_accounting = get_field_value( $meta, 'second_quick_pay_accounting' );
		$second_quick_pay_method    = get_field_value( $meta, 'second_quick_pay_method' );
		$second_quick_pay_driver_amount = get_field_value( $meta, 'second_quick_pay_driver_amount' );
		$second_driver_rate         = get_field_value( $meta, 'second_driver_rate' );
		
		// Third driver data
		$third_driver              = get_field_value( $meta, 'third_driver' );
		$third_unit_number_name    = get_field_value( $meta, 'third_unit_number_name' );
		$third_bank_payment_st     = get_field_value( $meta, 'third_bank_payment_status' );
		$third_driver_pay_st       = get_field_value( $meta, 'third_driver_pay_statuses' );
		$third_quick_pay_accounting = get_field_value( $meta, 'third_quick_pay_accounting' );
		$third_quick_pay_method    = get_field_value( $meta, 'third_quick_pay_method' );
		$third_quick_pay_driver_amount = get_field_value( $meta, 'third_quick_pay_driver_amount' );
		$third_driver_rate         = get_field_value( $meta, 'third_driver_rate' );
	}
}

// Check if drivers are enabled
$has_second_driver = ! empty( $second_driver ) || ! empty( $second_unit_number_name );
$has_third_driver = ! empty( $third_driver ) || ! empty( $third_unit_number_name );

$full_view_only = get_field_value( $args, 'full_view_only' );

?>

<h3 class="p-0 display-6 mb-4">Accounting info</h3>

<form class="<?php echo ( $full_view_only ) ? '' : 'js-uploads-accounting' ?> d-grid">
	
	<?php if ( $project ): ?>
        <input type="hidden" name="project" value="<?php echo $project; ?>">
	<?php endif; ?>
	
	<?php if ( $flt ): ?>
        <input type="hidden" name="flt" value="<?php echo $flt; ?>">
	<?php endif; ?>

    <div class="row">
		<?php if ( $log_file_isset ): ?>
            <input type="hidden" name="log_file_isset" value="1">
		<?php endif; ?>

        <input type="hidden" name="factoring_status" value="<?php echo $factoring_status; ?>">

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
            <label for="driver_pay_statuses" class="form-label">Payment status</label>
            <select name="driver_pay_statuses" class="form-control form-select js-select-status-factoring"
                    data-previous-value="<?php echo $factoring_status; ?>">
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
					<?php if ( is_numeric( $quick_pay_driver_amount ) ): ?>
                        <p class="text-medium js-sum-after-count text-center mt-1">Sum to pay
                            $<?php echo $driver_rate - $quick_pay_driver_amount; ?></p>
					<?php endif; ?>
                </div>
            </div>
        </div>

		<?php if ( $has_second_driver ): ?>
			<!-- Second Driver Accounting Section -->
			<div class="col-12 mt-4">
				<h4 class="mb-3">Second Driver Accounting</h4>
			</div>

			<div class="mb-2 col-12 col-md-6 col-xl-4">
				<label for="second_bank_payment_status" class="form-label">Bank status (Second Driver)</label>
				<select name="second_bank_payment_status" class="form-control form-select">
					<option value="">Select status</option>
					<?php if ( is_array( $bank_statuses ) ): ?>
						<?php foreach ( $bank_statuses as $key => $status ): ?>
							<option <?php echo $second_bank_payment_st === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
								<?php echo $status; ?>
							</option>
						<?php endforeach; ?>
					<?php endif ?>
				</select>
			</div>

			<div class="mb-2 col-12 col-md-6 col-xl-4">
				<label for="second_driver_pay_statuses" class="form-label">Payment status (Second Driver)</label>
				<select name="second_driver_pay_statuses" class="form-control form-select js-select-status-factoring"
						data-previous-value="<?php echo $factoring_status; ?>">
					<option value="">Select status</option>
					<?php if ( is_array( $driver_pay_statuses ) ): ?>
						<?php foreach ( $driver_pay_statuses as $key => $status ): ?>
							<option <?php echo $second_driver_pay_st === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
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
						   data-toggle="js-second-quick-actions" <?php echo $second_quick_pay_accounting ? 'checked' : ''; ?>
						   name="second_quick_pay_accounting" type="checkbox" id="second_quick_pay_accounting">
					<label class="form-check-label" for="second_quick_pay_accounting">Quick pay? (Second Driver)</label>
				</div>
			</div>

			<div class="col-12 js-second-quick-actions <?php echo $second_quick_pay_accounting ? '' : 'd-none'; ?>">
				<div class="row">
					<div class="mb-2 col-12 col-md-6 col-xl-4">
						<label for="second_quick_pay_method" class="form-label">Quick pay method (Second Driver)</label>
						<select name="second_quick_pay_method" class="form-control form-select js-second-quick-pay-method">
							<option value="">Select method</option>
							<?php if ( is_array( $quick_pay_methods ) ): ?>
								<?php foreach ( $quick_pay_methods as $key => $status ): ?>
									<option <?php echo $second_quick_pay_method === $key ? 'selected' : ''; ?>
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
						<p class="text-medium text-center mt-1">$<?php echo $second_driver_rate; ?> without quick pay percent</p>
					</div>
					
					<?php if ( is_array( $quick_pay_methods ) ): ?>
						<?php foreach ( $quick_pay_methods as $key => $status ): ?>
							<input type="hidden" data-reit="<?php echo $second_driver_rate; ?>"
								   data-commission="<?php echo $status[ 'commission' ]; ?>"
								   class="js-select-second-quick-<?php echo $key; ?>" value="<?php echo $status[ 'value' ]; ?>">
						<?php endforeach; ?>
					<?php endif ?>

					<div class="mb-2 col-12 col-md-6 col-xl-4">
						<label for="second_quick_pay_driver_amount" class="form-label">
							Will charge the driver (Second Driver) </label>
						<div class="input-group">
							<span class="input-group-text">$</span>
							<input type="text" readonly value="<?php echo $second_quick_pay_driver_amount; ?>"
								   name="second_quick_pay_driver_amount"
								   class="form-control js-money js-second-quick-pay-driver">
						</div>
						<?php if ( is_numeric( $second_quick_pay_driver_amount ) ): ?>
							<p class="text-medium js-second-sum-after-count text-center mt-1">Sum to pay
								$<?php echo $second_driver_rate - $second_quick_pay_driver_amount; ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $has_third_driver ): ?>
			<!-- Third Driver Accounting Section -->
			<div class="col-12 mt-4">
				<h4 class="mb-3">Third Driver Accounting</h4>
			</div>

			<div class="mb-2 col-12 col-md-6 col-xl-4">
				<label for="third_bank_payment_status" class="form-label">Bank status (Third Driver)</label>
				<select name="third_bank_payment_status" class="form-control form-select">
					<option value="">Select status</option>
					<?php if ( is_array( $bank_statuses ) ): ?>
						<?php foreach ( $bank_statuses as $key => $status ): ?>
							<option <?php echo $third_bank_payment_st === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
								<?php echo $status; ?>
							</option>
						<?php endforeach; ?>
					<?php endif ?>
				</select>
			</div>

			<div class="mb-2 col-12 col-md-6 col-xl-4">
				<label for="third_driver_pay_statuses" class="form-label">Payment status (Third Driver)</label>
				<select name="third_driver_pay_statuses" class="form-control form-select js-select-status-factoring"
						data-previous-value="<?php echo $factoring_status; ?>">
					<option value="">Select status</option>
					<?php if ( is_array( $driver_pay_statuses ) ): ?>
						<?php foreach ( $driver_pay_statuses as $key => $status ): ?>
							<option <?php echo $third_driver_pay_st === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
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
						   data-toggle="js-third-quick-actions" <?php echo $third_quick_pay_accounting ? 'checked' : ''; ?>
						   name="third_quick_pay_accounting" type="checkbox" id="third_quick_pay_accounting">
					<label class="form-check-label" for="third_quick_pay_accounting">Quick pay? (Third Driver)</label>
				</div>
			</div>

			<div class="col-12 js-third-quick-actions <?php echo $third_quick_pay_accounting ? '' : 'd-none'; ?>">
				<div class="row">
					<div class="mb-2 col-12 col-md-6 col-xl-4">
						<label for="third_quick_pay_method" class="form-label">Quick pay method (Third Driver)</label>
						<select name="third_quick_pay_method" class="form-control form-select js-third-quick-pay-method">
							<option value="">Select method</option>
							<?php if ( is_array( $quick_pay_methods ) ): ?>
								<?php foreach ( $quick_pay_methods as $key => $status ): ?>
									<option <?php echo $third_quick_pay_method === $key ? 'selected' : ''; ?>
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
						<p class="text-medium text-center mt-1">$<?php echo $third_driver_rate; ?> without quick pay percent</p>
					</div>
					
					<?php if ( is_array( $quick_pay_methods ) ): ?>
						<?php foreach ( $quick_pay_methods as $key => $status ): ?>
							<input type="hidden" data-reit="<?php echo $third_driver_rate; ?>"
								   data-commission="<?php echo $status[ 'commission' ]; ?>"
								   class="js-select-third-quick-<?php echo $key; ?>" value="<?php echo $status[ 'value' ]; ?>">
						<?php endforeach; ?>
					<?php endif ?>

					<div class="mb-2 col-12 col-md-6 col-xl-4">
						<label for="third_quick_pay_driver_amount" class="form-label">
							Will charge the driver (Third Driver) </label>
						<div class="input-group">
							<span class="input-group-text">$</span>
							<input type="text" readonly value="<?php echo $third_quick_pay_driver_amount; ?>"
								   name="third_quick_pay_driver_amount"
								   class="form-control js-money js-third-quick-pay-driver">
						</div>
						<?php if ( is_numeric( $third_quick_pay_driver_amount ) ): ?>
							<p class="text-medium js-third-sum-after-count text-center mt-1">Sum to pay
								$<?php echo $third_driver_rate - $third_quick_pay_driver_amount; ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

        <div class="col-12 mt-4 order-5" role="presentation">
            <div class="justify-content-start gap-2">
				
				<?php
				$previous_tab = $TMSUsers->check_user_role_access( array(
					'recruiter-tl',
                    'hr_manager',
					'recruiter'
				), true ) && isset( $meta ) ? 'pills-documents-tab' : 'pills-billing-tab';
				?>

                <button type="button" data-tab-id="<?php echo $previous_tab; ?>"
                        class="btn btn-dark js-next-tab">Previous
                </button>
				
				<?php if ( ! $full_view_only ): ?>
                    <button type="submit" class="btn btn-primary js-submit-and-next-tab">Update
                    </button>
				<?php endif; ?>
            </div>
        </div>
    </div>
	<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php endif; ?>
</form>