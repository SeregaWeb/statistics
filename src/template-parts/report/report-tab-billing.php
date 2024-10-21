<?php

$reports = new TMSReports();

// tab 5

$invoices = $reports->get_invoices();
$factoring_statuses = $reports->get_factoring_status();

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;

if ( $report_object ) {
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		
		$load_problem           = get_field_value( $main, 'load_problem' );
		$load_problem_formatted = date( 'Y-m-d', strtotime( $load_problem ) );
        
        $invoice = get_field_value($meta, 'invoice');
        $factoring_status = get_field_value($meta, 'factoring_status');
		
	}
}

?>

<h3 class="p-0 display-6 mb-4">Billing info</h3>

<form class="js-uploads-billing d-grid">
    <div class="row">
    <div class="mb-2 col-12 col-md-6 col-xl-4">
        <label for="load_problem" class="form-label">Date start problem</label>
        <input type="date" name="load_problem" value="<?php echo $load_problem_formatted; ?>" class="form-control">
    </div>
        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="invoice" class="form-label">Select invoice</label>
            <select name="invoice" class="form-control form-select" required>
                <option value="">invoices</option>
			    <?php if ( is_array( $invoices ) ): ?>
				    <?php foreach ( $invoices as $key => $status ): ?>
                        <option <?php echo $invoice === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
						    <?php echo $status; ?>
                        </option>
				    <?php endforeach; ?>
			    <?php endif ?>
            </select>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="factoring_status" class="form-label">Select factoring status</label>
            <select name="factoring_status" class="form-control form-select" required>
                <option value="">factoring statuses</option>
			    <?php if ( is_array( $factoring_statuses ) ): ?>
				    <?php foreach ( $factoring_statuses as $key => $status ): ?>
                        <option <?php echo $factoring_status === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
						    <?php echo $status; ?>
                        </option>
				    <?php endforeach; ?>
			    <?php endif ?>
            </select>
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