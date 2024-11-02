<?php

$reports = new TMSReports();

// tab 5

$invoices = $reports->get_invoices();
$factoring_statuses = $reports->get_factoring_status();

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;

$bank_statuses = $reports->get_bank_statuses();
$driver_pay_statuses = $reports->get_driver_payment_statuses();

if ( $report_object ) {
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		
        $bank_payment_st = get_field_value( $meta, 'bank_payment_status' );
        $driver_pay_st = get_field_value( $meta, 'driver_pay_statuses' );
	
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
                        <option <?php echo $driver_pay_st === $key ? 'selected' : ''; ?>  value="<?php echo $key; ?>">
						    <?php echo $status; ?>
                        </option>
				    <?php endforeach; ?>
			    <?php endif ?>
            </select>
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