<?php
$helper = new TMSReportsHelper();
$states = $helper->get_states();

$set_up_platform = $helper->get_set_up_platform();
$set_up          = $helper->get_set_up();

$user_id = get_current_user_id();
$curent_tables = get_field('current_select', 'user_'.$user_id);


?>
<form class="ng-pristine ng-invalid ng-touched js-add-new-company">
    <input type="hidden" name="select_project" value="<?php echo $curent_tables; ?>">
	<div>
		<h4 class="text">Add new broker</h4>
        <p class="form-text">Current project <strong class="text-primary"><?php echo $curent_tables; ?></strong></p>
	</div>
	<div class="modal-body mb-5 row">
		<div class="form-group mt-3">
			<label for="input-name" class="form-label">
				Company Name <span class="required-star text-danger">*</span>
			</label>
			<input id="input-name" type="text" required name="company_name" placeholder="Company Name" class="form-control">
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
		
		<div class="form-group mt-3 col-6">
			<label for="input-address1" class="form-label">Address 1 <span class="required-star text-danger">*</span></label>
			<input id="input-address1" type="text" required name="Addr1" placeholder="Address 1" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
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
			<label for="input-firstname" class="form-label">Contact Name </label>
			<input id="input-firstname" type="text" name="FirstName" placeholder="First Name" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label class="form-label">&nbsp;</label>
			<input id="input-lastname"  type="text" name="LastName" placeholder="Last Name" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-phone" class="form-label">Phone Number <span class="required-star text-danger">*</span></label>
			<input id="input-phone" required type="text" name="Phone" placeholder="Phone" class="form-control js-tel-mask">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-email" class="form-label">Email</label>
			<input id="input-email" type="text" name="Email" placeholder="Email" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-mcnumber" class="form-label">MC Number <span class="required-star text-danger">*</span></label>
			<input id="input-mcnumber" required type="text" name="MotorCarrNo" placeholder="MC Number" class="form-control">
		</div>
		
		<div class="form-group mt-3 col-6">
			<label for="input-dotnumber" class="form-label">DOT Number</label>
			<input id="input-dotnumber" type="text" name="DotNo" placeholder="DOT Number" class="form-control">
		</div>

        <div class="form-group mt-3 col-6">
            <label for="set_up" class="form-label">Set up<span
                        class="required-star text-danger">*</span></label>
            <select name="set_up" class="form-control form-select" required>
                <option value="">Select set up</option>
		        <?php if ( is_array( $set_up ) ): ?>
			        <?php foreach ( $set_up as $key => $val ): ?>
                        <option value="<?php echo $key; ?>">
					        <?php echo $val; ?>
                        </option>
			        <?php endforeach; ?>
		        <?php endif ?>
            </select>
        </div>

        <div class="form-group mt-3 col-6">
            <label for="set_up_platform" class="form-label">Set up platform<span
                        class="required-star text-danger">*</span></label>
            <select name="set_up_platform" class="form-control form-select" required>
                <option value="">Select platform</option>
		        <?php if ( is_array( $set_up_platform ) ): ?>
			        <?php foreach ( $set_up_platform as $key => $val ): ?>
                        <option value="<?php echo $key; ?>">
					        <?php echo $val; ?>
                        </option>
			        <?php endforeach; ?>
		        <?php endif ?>
            </select>
        </div>
        
	</div>

    <div class="input-group mt-3">
        <span class="input-group-text">Notes</span>
        <textarea class="form-control" aria-label="With textarea" name="notes"></textarea>
    </div>
    
    <div class="w-100 mt-3">
        <p>Work with</p>
        
        <div class="d-flex gap-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="work_with_odysseia" id="odysseia">
            <label class="form-check-label" for="odysseia" >Odysseia</label>
        </div>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="work_with_martlet" id="martlet">
            <label class="form-check-label" for="martlet" >Martlet</label>
        </div>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="work_with_endurance" id="endurance">
            <label class="form-check-label" for="endurance" >Endurance</label>
        </div>
        </div>
        
    </div>
 
	<div class="modal-footer justify-content-start gap-2 mt-3">
		<button type="button" class="btn btn-dark js-popup-close">Cancel</button>
		<button type="submit" class="btn btn-outline-primary">Submit <span class="spinner-border spinner-border-sm ms-2" style="display: none;"></span></button>
	</div>

</form>
