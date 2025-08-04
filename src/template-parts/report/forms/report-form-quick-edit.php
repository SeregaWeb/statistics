<?php

$helper   = new TMSReportsHelper();
$TMSUsers = new TMSUsers();

$type   = get_field_value( $_GET, 'type' );
$is_flt = $type === 'flt';

$reports = new TMSReports();
$project = $reports->project;

$billing_info    = $TMSUsers->check_user_role_access( array( 'administrator', 'billing' ), true );
$accounting_info = $TMSUsers->check_user_role_access( array( 'administrator', 'accounting' ), true );


$factoring_statuses  = $helper->get_factoring_status();
$bank_statuses       = $helper->get_bank_statuses();
$driver_pay_statuses = $helper->get_driver_payment_statuses();

$include_statuses = array( 'unsubmitted', 'in-processing', 'processed' );

?>

<form class="w-100 js-quick-edit">
	
	<?php if ( $project ): ?>
        <input type="hidden" name="project" value="<?php echo $project; ?>">
	<?php endif; ?>
	
	<?php if ( $is_flt ): ?>
        <input type="hidden" name="flt" value="1">
	<?php endif ?>
	
	<?php if ( $billing_info ): ?>

        <div class="w-100 mt-3 mb-3">
            <label for="factoring_status" class="form-label">Factoring status</label>
            <select name="factoring_status" class="form-control form-select">
                <option value="">Select factoring status</option>
				<?php if ( is_array( $factoring_statuses ) ): ?>
					<?php foreach ( $factoring_statuses as $key => $status ):
						if ( in_array( $key, $include_statuses ) ): ?>
                            <option value="<?php echo $key; ?>">
								<?php echo $status; ?>
                            </option>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>

        <div class="w-1oo mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" name="invoiced_proof" type="checkbox" id="invoiced_proof">
                <label class="form-check-label" for="invoiced_proof">Invoiced</label>
            </div>
        </div>
	
	<?php endif; ?>
	
	<?php if ( $accounting_info ): ?>


        <div class="w-100 mt-3 mb-3">
            <label for="bank_payment_status" class="form-label">Bank status</label>
            <select name="bank_payment_status" class="form-control form-select">
                <option value="">Select status</option>
				<?php if ( is_array( $bank_statuses ) ): ?>
					<?php foreach ( $bank_statuses as $key => $status ): ?>
                        <option value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>

        <div class="w-100 mt-3 mb-3">
            <label for="driver_pay_statuses" class="form-label">Payment status</label>
            <select name="driver_pay_statuses" class="form-control form-select">
                <option value="">Select status</option>
				<?php if ( is_array( $driver_pay_statuses ) ): ?>
					<?php foreach ( $driver_pay_statuses as $key => $status ): ?>
                        <option value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>
	
	<?php endif; ?>

    <div class="modal-footer justify-content-start gap-2">
        <button type="button" class="btn btn-dark js-popup-close">Cancel</button>
        <button type="submit" class="btn btn-outline-primary">Submit</button>
    </div>

</form>