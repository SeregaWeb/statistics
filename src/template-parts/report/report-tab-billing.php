<?php

$reports = new TMSReports();

// tab 5

$invoices           = $reports->get_invoices();
$factoring_statuses = $reports->get_factoring_status();
$ar_statuses        = $reports->get_ar_statuses();

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;

$booked_rate = 0;
$driver_rate = 0;
$profit      = 0;
$tbd         = false;


if ( $report_object ) {
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	$percent_quick_pay = 0;
	$processing_fees   = '';
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		
		$load_problem           = get_field_value( $main, 'load_problem' );
		$load_problem_formatted = ( ! is_null( $load_problem ) ) ? date( 'Y-m-d', strtotime( $load_problem ) ) : null;
		
		$factoring_status = get_field_value( $meta, 'factoring_status' );
		
		$short_pay      = get_field_value( $meta, 'short_pay' );
		$rc_proof       = get_field_value( $meta, 'rc_proof' );
		$pod_proof      = get_field_value( $meta, 'pod_proof' );
		$invoiced_proof = get_field_value( $meta, 'invoiced_proof' );
		$processing     = get_field_value( $meta, 'processing' );
		$type_pay       = get_field_value( $meta, 'quick_pay' );
		
		$processing_fees   = get_field_value( $meta, 'processing_fees' );
		$type_pay_method   = get_field_value( $meta, 'type_pay' );
		$percent_quick_pay = get_field_value( $meta, 'percent_quick_pay' );
		$booked_rate       = get_field_value( $meta, 'booked_rate' );
		$driver_rate       = get_field_value( $meta, 'driver_rate' );
		$profit            = get_field_value( $meta, 'profit' );
		$tbd               = get_field_value( $meta, 'tbd' );
		
		$ar_status       = get_field_value( $meta, 'ar_status' );
		$ar_action_field = get_field_value( $meta, 'ar-action' );
		$ar_action       = $ar_action_field ? 'checked' : '';
		
		if ( ! $type_pay ) {
			$type_pay = 'delayed-advance';
		}
	}
	
}

?>

<h3 class="p-0 display-6 mb-4">Billing info</h3>

<form class="js-uploads-billing d-grid">

    <input type="hidden" name="booked_rate" value="<?php echo $booked_rate; ?>">
    <input type="hidden" name="driver_rate" value="<?php echo $driver_rate; ?>">
    <input type="hidden" name="profit" value="<?php echo $profit; ?>">
    <input type="hidden" name="tbd" value="<?php echo $tbd; ?>">

    <div class="row">
        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="processing" class="form-label">Select processing</label>
            <select name="processing"
                    class="form-control form-select js-show-hidden-values js-blocked-value"
                    data-blocked="charge-back"
                    data-blocked-selector="js-blocked-status"
                    data-blocked-current="direct"
                    required
                    data-value="direct"
                    data-selector=".js-select-type-direct">
                <option value="factoring">Factoring</option>
                <option value="direct" <?php echo $processing === 'direct' ? 'selected' : ''; ?>>Direct</option>
            </select>
        </div>

        <div class="col-12"></div>
		
		<?php $hide_all_info = $processing === "direct" ? '' : 'd-none'; ?>

        <div class="col-12 mb-2 js-select-type-direct <?php echo $hide_all_info; ?>">
            <div class="row">

                <div class="mb-2 col-12 col-md-6 col-xl-4">
                    <!-- gross - this percent -->
                    <label for="processing_fees" class="form-label">Processing fees</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" value="<?php echo $processing_fees; ?>" name="processing_fees"
                               class="form-control js-money">
                    </div>
                </div>


                <div class="col-12"></div>

                <div class="mb-2 col-12 col-md-6 col-xl-4">
                    <label for="type_pay" class="form-label">Pay method</label>
                    <select name="type_pay" class="form-control form-select js-show-hidden-values" data-required="true"
                            data-value="quick-pay" data-selector=".js-select-quick-pay-percent">
                        <option value="delayed-advance" <?php echo $type_pay_method === 'delayed-advance' ? 'selected'
							: ''; ?>>Delayed advance
                        </option>
                        <option value="quick-pay" <?php echo $type_pay_method === 'quick-pay' ? 'selected' : ''; ?>>
                            Quick pay
                        </option>
                    </select>
                </div>
				
				<?php $hide_dop_info = $type_pay_method === "quick-pay" ? '' : 'd-none' ?>

                <div class="mb-2 col-12 col-md-6 col-xl-4 js-select-quick-pay-percent <?php echo $hide_dop_info; ?>">
                    <!-- gross - this percent -->
                    <label for="percent_quick_pay" class="form-label">Quick pay percent </label>
                    <div class="input-group">
                        <span class="input-group-text">%</span>
                        <input type="number" value="<?php echo $percent_quick_pay; ?>" name="percent_quick_pay"
                               class="form-control">
                    </div>
                </div>
            </div>
        </div>


        <!--	    --><?php
		//	    $classShortPay = 'd-none';
		//	    if ($factoring_status === 'short-pay') {
		//		    $classShortPay = '';
		//	    } ?>
        <!---->
        <!--        <div class="mb-2 col-12 col-md-6 col-xl-4 js-short-pay --><?php //echo $classShortPay; ?><!--">-->
        <!--            <label for="short_pay" class="form-label">Short pay value</label>-->
        <!--            <input type="text" value="" name="short_pay" required-->
        <!--                   class="form-control js-money">-->
        <!--        </div>-->

        <div class="col-12 d-flex gap-2 mt-2 mb-4">
            <div class="form-check form-switch">
                <input class="form-check-input" <?php echo $rc_proof ? 'checked' : ''; ?> name="rc_proof"
                       type="checkbox" id="rc_proof">
                <label class="form-check-label" for="rc_proof">Rate confirmation</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" <?php echo $pod_proof ? 'checked' : ''; ?> name="pod_proof"
                       type="checkbox" id="pod_proof">
                <label class="form-check-label" for="pod_proof">Proof of Delivery</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" <?php echo $invoiced_proof ? 'checked' : ''; ?> name="invoiced_proof"
                       type="checkbox" id="invoiced_proof">
                <label class="form-check-label" for="invoiced_proof">Invoiced</label>
            </div>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="factoring_status" class="form-label">Select status</label>
            <select name="factoring_status" class="form-control form-select js-show-hidden-values js-blocked-status"
                    data-required="true" data-value="short-pay" data-selector=".js-short-pay">
				<?php if ( is_array( $factoring_statuses ) ): ?>
					<?php foreach ( $factoring_statuses as $key => $status ):
						$disabled_option = ( $processing === 'direct' && $key === 'charge-back' ) ? 'disabled' : '';
						
						?>
                        <option <?php echo $factoring_status === $key ? 'selected' : '';
						echo $disabled_option; ?> value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>
		
		
		<?php
		$classShortPay    = 'd-none';
		$requiredVariable = '';
		if ( $factoring_status === 'short-pay' ) {
			$classShortPay    = '';
			$requiredVariable = 'required';
		} ?>

        <div class="mb-2 col-12 col-md-6 col-xl-4 js-short-pay <?php echo $classShortPay; ?>">
            <label for="short_pay" class="form-label">Short pay value</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" value="<?php echo $short_pay; ?>" name="short_pay" <?php echo $requiredVariable; ?>
                       class="form-control js-money">
            </div>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <div class="form-check form-switch">
                <input class="form-check-input js-switch-toggle" data-toggle="js-ar-actions" name="ar-action"
                       type="checkbox" id="ar_action" <?php echo $ar_action; ?>>
                <label class="form-check-label" for="ar_action">A/R aging</label>
            </div>
        </div>

        <div class="col-12 js-ar-actions <?php echo $ar_action_field ? '' : 'd-none'; ?>">
            <div class="row">
                <div class="mb-2 col-12 col-md-6 col-xl-4">
                    <label for="load_problem" class="form-label">Select invoice date</label>
                    <input type="date" name="load_problem" value="<?php echo $load_problem_formatted; ?>"
                           class="form-control">
                </div>

                <div class="mb-2 col-12 col-md-6 col-xl-4">
                    <label for="ar_status" class="form-label">Select invoice status</label>
                    <select name="ar_status" class="form-control form-select">
						<?php if ( is_array( $ar_statuses ) ): ?>
							<?php foreach ( $ar_statuses as $key => $status ): ?>
                                <option <?php echo $ar_status === $key ? 'selected' : ''; ?>
                                        value="<?php echo $key; ?>">
									<?php echo $status; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif ?>
                    </select>
                </div>


            </div>
        </div>

        <div class="col-12 mt-4 order-5" role="presentation">
            <div class="justify-content-start gap-2">
                <button type="button" data-tab-id="pills-documents-tab"
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