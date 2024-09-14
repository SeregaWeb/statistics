<?php
$helper        = new TMSReportsHelper();
$states        = $helper->get_states();
$statuses      = $helper->get_statuses();
$sources       = $helper->get_sources();
$instructions  = $helper->get_instructions();
$types         = $helper->get_load_types();
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;
$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;

$date_booked         = '';
$dispatcher_initials = '';
$reference_number    = '';
$unit_number_name    = '';
$booked_rate         = '';
$driver_rate         = '';
$profit              = '';
$pick_up_date        = '';
$load_status         = '';
$instructions_val    = array();
$source              = '';
$load_type           = '';
$commodity           = '';
$weight              = '';
$notes               = '';

if ( $report_object ) {
	$values = $report_object;
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		$date_booked         = $values[ 0 ]->date_booked;
		$dispatcher_initials = $values[ 0 ]->dispatcher_initials;
		$reference_number    = $values[ 0 ]->reference_number;
		$unit_number_name    = $values[ 0 ]->unit_number_name;
		$booked_rate         = $values[ 0 ]->booked_rate;
		$driver_rate         = $values[ 0 ]->driver_rate;
		$profit              = $values[ 0 ]->profit;
		$pick_up_date        = $values[ 0 ]->pick_up_date;
		$load_status         = $values[ 0 ]->load_status;
        $instructions_str    = str_replace(' ', '',$values[ 0 ]->instructions);
        $instructions_val    = explode(',', $instructions_str);
		$source_val          = $values[ 0 ]->source;
		$load_type           = $values[ 0 ]->load_type;
		$commodity           = $values[ 0 ]->commodity;
		$weight              = $values[ 0 ]->weight;
		$notes               = $values[ 0 ]->notes;
	}
}

?>

<form method="post" enctype="multipart/form-data" class="form-group js-add-new-report">
	<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php endif; ?>
    <div class="row">
        <h3 class="display-6 mb-4">Load info</h3>

        <div class="col-12">
            <p class="h5">Load</p>
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="reference_number" class="form-label">Reference Number</label>
            <input type="text" name="reference_number" value="<?php echo $reference_number; ?>" class="form-control"
                   required>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="booked_rate" class="form-label">Booked Rate</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" value="<?php echo $booked_rate; ?>" name="booked_rate"
                       class="form-control js-money js-all-value" required>
            </div>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="load_status" class="form-label">Load Status</label>
            <select name="load_status" class="form-control form-select" required>
                <option value="">Select status</option>
				<?php if ( is_array( $statuses ) ): ?>
					<?php foreach ( $statuses as $key => $status ): ?>
                        <option <?php echo $load_status === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="load_type" class="form-label">Load Type</label>
            <select name="load_type" class="form-control form-select" required>
                <option value="">Load type</option>
				<?php if ( is_array( $types ) ): ?>
					<?php foreach ( $types as $key => $type ): ?>
                        <option <?php echo $load_type === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $type; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>

        <div class="col-12 mt-5">
            <p class="h5">Dispatcher</p>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="dispatcher_initials" class="form-label">Dispatcher Initials</label>
            <select name="dispatcher_initials" class="form-control form-select" required>
                <option value="">Select dispatcher</option>
                <option value="24" <?php echo $dispatcher_initials === '24' ? 'selected' : ''; ?> >Alex morgan</option>
                <option value="25" <?php echo $dispatcher_initials === '25' ? 'selected' : ''; ?> >Sergey Milchenko
                </option>
                <option value="26" <?php echo $dispatcher_initials === '26' ? 'selected' : ''; ?> >Anna Abramova
                </option>
            </select>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="date_booked" class="form-label">Date Booked</label>
            <input type="date" name="date_booked" value="<?php echo $date_booked; ?>" class="form-control" required>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="source" class="form-label">Source</label>
            <select name="source" class="form-control form-select" required>
                <option value="">Select source</option>
				<?php if ( is_array( $sources ) ): ?>
					<?php foreach ( $sources as $key => $source ): ?>
                        <option <?php echo $source_val === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $source; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
        </div>

        <div class="col-12 mt-5">
            <p class="h5">Driver</p>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="unit_number_name" class="form-label">Unit Number & Name</label>
            <input type="text" name="unit_number_name" value="<?php echo $unit_number_name; ?>" class="form-control" required>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="driver_rate" class="form-label">Driver Rate</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" name="driver_rate" value="<?php echo $driver_rate; ?>"
                       class="form-control js-money js-driver-value" required>
            </div>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="pick_up_date" class="form-label">Pick Up Date</label>
            <input type="date" name="pick_up_date" value="<?php echo $pick_up_date; ?>" class="form-control" required>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <label for="profit" class="form-label">Profit</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" name="profit" value="<?php echo $profit; ?>" readonly
                       class="form-control js-money js-money-total" required>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label mt-3">Instructions</label>
        </div>
        <div class="col-12 col-xl-8 d-flex flex-wrap ">
			<?php
            if ( is_array( $instructions ) ): ?>
				<?php foreach ( $instructions as $key => $instruction ):
                    $checked = array_search($key, $instructions_val);
                    ?>
                    <div class="form-check form-switch p-0 col-12 col-md-6 col-xl-4">
                        <input class="form-check-input ml-0" <?php echo is_numeric($checked) ? 'checked': ''; ?> name="instructions[]" value="<?php echo $key; ?>"
                               type="checkbox" id="flexSwitchCheckDefault_<?php echo $key; ?>">
                        <label class="form-check-label ml-2"
                               for="flexSwitchCheckDefault_<?php echo $key; ?>"><?php echo $instruction; ?>
                        </label>
                    </div>
				<?php endforeach; ?>
			<?php endif ?>
        </div>

        <div class="col-12 mt-5">
            <p class="h5">Additional information about the cargo</p>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="commodity" class="form-label">Commodity</label>
            <input type="text" value="<?php echo $commodity; ?>" name="commodity" class="form-control" required>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="weight" class="form-label">Weight (lbs)</label>
            <input type="number" step="0.1" name="weight" value="<?php echo $weight; ?>" class="form-control" required>
        </div>

        <div class="mb-5 col-12 col-xl-8">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" class="form-control"><?php echo $notes; ?></textarea>
        </div>

        <div class="col-12" role="presentation">
            <div class="justify-content-start gap-2">
                <button type="button" data-tab-id="pills-customer-tab"
                        class="btn btn-dark js-next-tab">Previous
                </button>
                <button type="submit" class="btn btn-primary js-submit-and-next-tab" data-tab-id="pills-trip-tab">Next
                </button>
            </div>
        </div>
    </div>

</form>