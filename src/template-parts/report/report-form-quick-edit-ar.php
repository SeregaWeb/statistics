
<?php

$reports = new TMSReports();
$ar_statuses = $reports->get_ar_statuses();

?>

<form class="w-100 js-quick-edit-ar">

    <div class="w-100 mt-3 mb-3">
        <label for="factoring_status" class="form-label">A/R status</label>
        <select name="ar_status" class="form-control form-select">
            <option value="">Select A/R status</option>
		    <?php if ( is_array( $ar_statuses ) ): ?>
			    <?php foreach ( $ar_statuses as $key => $status ): ?>
                    <option value="<?php echo $key; ?>">
					    <?php echo $status; ?>
                    </option>
			    <?php endforeach; ?>
		    <?php endif ?>
        </select>
    </div>
	
	<div class="modal-footer justify-content-start gap-2">
		<button type="button" class="btn btn-dark js-popup-close">Cancel</button>
		<button type="submit" class="btn btn-outline-primary">Submit</button>
	</div>
	
</form>