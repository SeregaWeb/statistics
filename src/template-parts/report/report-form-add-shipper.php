<?php
$helper = new TMSReportsHelper();
$states = $helper->get_states();

?>
<form class="ng-pristine ng-invalid ng-touched js-add-new-shipper">
	<div>
		<h4 class="text">Add new shipper</h4>
	</div>
	<div class="modal-body mb-3 row">
		<div class="form-group mt-3">
			<label for="input-name" class="form-label">
				Shipper Name <span class="required-star text-danger">*</span>
			</label>
			<input id="input-name" type="text" required name="shipper_name" placeholder="Shipper Name" class="form-control">
		</div>
		
		<div class="form-group mt-3">
			<div class="d-flex justify-content-between select-country">
				<label class="form-label">Select Country</label>
				<div>
					<input type="radio" checked name="country" value="USA" id="country-us" class="form-check-input js-country">
					<label for="country-us" class="form-check-label">USA</label>
				</div>
				<div>
					<input type="radio" name="country" value="Canada" id="country-ca" class="form-check-input js-country">
					<label for="country-ca" class="form-check-label">Canada</label>
				</div>
                <div>
                    <input type="radio" name="country" value="Mexico" id="country-mx" class="form-check-input js-country">
                    <label for="country-mx" class="form-check-label">Mexico</label>
                </div>
			</div>
		</div>
		
		<div class="form-group mt-3">
			<label for="input-address1" class="form-label">Address 1 <span class="required-star text-danger">*</span></label>
			<input id="input-address1" type="text" required name="Addr1" placeholder="Address 1" class="form-control">
		</div>
		
		<div class="form-group mt-3">
			<label for="input-address2" class="form-label">Address 2</label>
			<input id="input-address2" type="text" name="Addr2" placeholder="Address 2" class="form-control">
		</div>
		
		<div class="form-group mt-3">
			<label for="input-city" class="form-label">City <span class="required-star text-danger">*</span></label>
			<input id="input-city" type="text" name="City" required placeholder="City" class="form-control js-city">
		</div>
		
		<div class="form-group mt-3 col-6 custom-select">
			<label class="form-label">State <span class="required-star text-danger">*</span></label>
			<select name="State" required class="form-control form-select js-state">
				<option value="" disabled selected>Select State</option>
				<?php if (is_array($states)): ?>
					<?php foreach ($states as $key => $state): ?>
                        <option value="<?php echo $key; ?>" <?php echo is_array($state) ? 'disabled' : '';?>>
							<?php echo is_array($state) ? $state[0] : $state; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
			</select>
		</div>
		
		<div class="form-group mt-3 col-6 js-zip">
			<label for="input-zip" class="form-label">Zip Code <span class="required-star text-danger">*</span></label>
            <div class="d-flex gap-1">
			    <input id="input-zip" type="text" required name="ZipCode" placeholder="Zip Code" class="form-control">
                <button class="btn btn-primary js-fill-auto">
                    Fill
                </button>
            </div>
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-firstname" class="form-label">Contact Name</label>
			<input id="input-firstname" type="text" name="FirstName" placeholder="First Name" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label class="form-label">&nbsp;</label>
			<input id="input-lastname"  type="text" name="LastName" placeholder="Last Name" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-phone" class="form-label">Phone Number</label>
			<input id="input-phone" type="text" name="Phone" placeholder="Phone" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-email" class="form-label">Email</label>
			<input id="input-email" type="text" name="Email" placeholder="Email" class="form-control">
		</div>
	</div>
	
	<div class="modal-footer justify-content-start gap-2">
		<button type="button" class="btn btn-dark js-popup-close">Cancel</button>
		<button type="submit" class="btn btn-outline-primary">Submit <span class="spinner-border spinner-border-sm ms-2" style="display: none;"></span></button>
	</div>
</form>
