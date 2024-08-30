<?php
$helper = new TMSReportsHelper();
$states = $helper->get_states();
$statuses = $helper->get_statuses();
$sources = $helper->get_sources();
$instructions = $helper->get_instructions();

?>

<form method="post" enctype="multipart/form-data" class="form-group js-add-new-report">
	<div class="row">
        
        <h2 class="mb-2">Add new report</h2>
        
        <div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="date_booked" class="form-label">Date Booked</label>
			<input type="date" name="date_booked" class="form-control" required>
		</div>
  
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="dispatcher_initials" class="form-label">Dispatcher Initials</label>
			<select name="dispatcher_initials" class="form-control form-select" required>
				<option value="">Select dispatcher</option>
				<option value="24">Alex morgan</option>
				<option value="25">Sergey Milchenko</option>
				<option value="26">Anna Abramova</option>
			</select>
		</div>
		
		<div class="mb-2 col-12 col-xl-4">
			<label for="reference_number" class="form-label">Reference Number</label>
			<input type="text" name="reference_number" class="form-control" required>
		</div>
		
		<label class="form-label">Pick Up Location</label>
		<div class="input-group col-12 mb-2">
			<span class="input-group-text">City</span>
			<input type="text" name="pick_up_location_city" class="form-control" required>
			<span class="input-group-text">State</span>
			<select name="pick_up_location_state" class="form-control form-select" required>
                <option value="">Select state</option>
				<?php if (is_array($states)): ?>
					<?php foreach ($states as $key => $state): ?>
                        <option value="<?php echo $key; ?>" <?php echo is_array($state) ? 'disabled' : '';?>>
                            <?php echo is_array($state) ? $state[0] : $state; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
			</select>
			<span class="input-group-text">Zip code</span>
			<input type="text" name="pick_up_location_zip" class="form-control" required>
		</div>
		
		<label class="form-label">Delivery Location</label>
		<div class="input-group col-12 mb-2">
			<span class="input-group-text">City</span>
			<input type="text" name="delivery_location_city" class="form-control" required>
			<span class="input-group-text">State</span>
            <select name="delivery_location_state" class="form-control form-select" required>
                <option value="">Select state</option>
				<?php if (is_array($states)): ?>
					<?php foreach ($states as $key => $state): ?>
                        <option value="<?php echo $key; ?>" <?php echo is_array($state) ? 'disabled' : '';?>>
							<?php echo is_array($state) ? $state[0] : $state; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
			<span class="input-group-text">Zip code</span>
			<input type="text" name="delivery_location_zip" class="form-control" required>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="unit_number_name" class="form-label">Unit Number & Name</label>
			<input type="text" name="unit_number_name" class="form-control" required>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="booked_rate" class="form-label">Booked Rate</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" name="booked_rate" class="form-control js-money js-all-value" required>
            </div>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="driver_rate" class="form-label">Driver Rate</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" name="driver_rate" class="form-control js-money js-driver-value" required>
            </div>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="profit" class="form-label">Profit</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
			    <input type="text" name="profit" readonly class="form-control js-money js-money-total" required>
            </div>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="pick_up_date" class="form-label">Pick Up Date</label>
			<input type="date" name="pick_up_date" class="form-control" required>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="load_status" class="form-label">Load Status</label>
            <select name="load_status" class="form-control form-select" required>
                <option value="">Select status</option>
	            <?php if (is_array($statuses)): ?>
		            <?php foreach ($statuses as $key => $status): ?>
                        <option value="<?php echo $key; ?>" >
				            <?php echo $status; ?>
                        </option>
		            <?php endforeach; ?>
	            <?php endif ?>
            </select>
		</div>

        <label class="form-label">Instructions</label>
		<div class="mb-2 col-12 d-flex flex-wrap ">
            <?php if (is_array($instructions)): ?>
                <?php foreach ($instructions as $key => $instruction): ?>
                    <div class="form-check form-switch col-12 col-md-6 col-lg-4 col-xl-3">
                        <input class="form-check-input" name="instructions[]" value="<?php echo $key; ?>" type="checkbox" id="flexSwitchCheckDefault_<?php echo $key; ?>">
                        <label class="form-check-label" for="flexSwitchCheckDefault_<?php echo $key; ?>"><?php echo $instruction; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php endif ?>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="source" class="form-label">Source</label>
            <select name="source" class="form-control" required>
                <option value="">Select source</option>
	            <?php if (is_array($sources)): ?>
		            <?php foreach ($sources as $key => $source): ?>
                        <option value="<?php echo $key; ?>" >
				            <?php echo $source; ?>
                        </option>
		            <?php endforeach; ?>
	            <?php endif ?>
            </select>
		</div>
		
		<div class="mb-2 col-12 col-md-6 col-xl-4">
			<label for="attached_files" class="form-label">Attached Files (up to 10)</label>
			<input type="file" name="attached_files[]" class="form-control js-control-uploads" multiple>
		</div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="post_status" class="form-label">Status post</label>
            <select name="post_status" class="form-control form-select">
                <option value="publish">Publish</option>
                <option value="draft">Draft</option>
            </select>
        </div>
        
        <div class="col-12 mb-2 preview-photo js-preview-photo-upload">
        
        </div>
	</div>
	
	<button type="submit" name="submit_booking" class="btn btn-primary">Submit</button>
</form>