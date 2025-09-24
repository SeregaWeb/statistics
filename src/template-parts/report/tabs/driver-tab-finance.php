<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$reports = new TMSReports();
$driver  = new TMSDrivers();
$TMSUsers = new TMSUsers();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$bank_payees = $driver->bank_payees;

$account_type        = get_field_value( $meta, 'account_type' );
$account_name        = get_field_value( $meta, 'account_name' );
$payment_instruction = get_field_value( $meta, 'payment_instruction' );
$w9_classification   = get_field_value( $meta, 'w9_classification' );
$address             = get_field_value( $meta, 'address' );
$city_state_zip      = get_field_value( $meta, 'city_state_zip' );
$ssn                 = get_field_value( $meta, 'ssn' );
$ssn_name            = get_field_value( $meta, 'ssn_name' );
$entity_name         = get_field_value( $meta, 'entity_name' );
$ein                 = get_field_value( $meta, 'ein' );
$authorized_email    = get_field_value( $meta, 'authorized_email' );

$bank_payees_raw = get_field_value( $meta, 'bank_payees' );
$bank_payees_str = $bank_payees_raw ? str_replace( ' ', '', $bank_payees_raw ) : '';
$bank_payees_val = $bank_payees_str ? explode( ',', $bank_payees_str ) : array();

$payment_file = get_field_value( $meta, 'payment_file' );
$w9_file      = get_field_value( $meta, 'w9_file' );
$ssn_file     = get_field_value( $meta, 'ssn_file' );
$ein_file     = get_field_value( $meta, 'ein_file' );
$nec_file     = get_field_value( $meta, 'nec_file' );
$total_images = 0;

$payment_file_arr = $driver->process_file_attachment( $payment_file );
$w9_file_arr      = $driver->process_file_attachment( $w9_file );
$ssn_file_arr     = $driver->process_file_attachment( $ssn_file );
$ein_file_arr     = $driver->process_file_attachment( $ein_file );
$nec_file_arr     = $driver->process_file_attachment( $nec_file );

$total_images = 0;

$files_check = array(
	$payment_file_arr,
	$w9_file_arr,
	$ssn_file_arr,
	$ein_file_arr,
	$nec_file_arr,
);

foreach ( $files_check as $file ) {
	if ( ! empty( $file ) ) {
		$total_images ++;
	}
}

$files = array(
	array(
		'file_arr'       => $payment_file_arr,
		'file'           => $payment_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'payment-file',
		'field_name'     => 'payment_file',
		'field_label'    => 'Payment file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-finance-tab',
	),
	array(
		'file_arr'       => $w9_file_arr,
		'file'           => $w9_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'w9-file',
		'field_name'     => 'w9_file',
		'field_label'    => 'W9 file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-finance-tab',
	),
	array(
		'file_arr'       => $ssn_file_arr,
		'file'           => $ssn_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'ssn-file',
		'field_name'     => 'ssn_file',
		'field_label'    => 'SSN file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-finance-tab',
	),
	array(
		'file_arr'       => $ein_file_arr,
		'file'           => $ein_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'ein-file',
		'field_name'     => 'ein_file',
		'field_label'    => 'EIN file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-finance-tab',
	),
	array(
		'file_arr'       => $nec_file_arr,
		'file'           => $nec_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'nec-file',
		'field_name'     => 'nec_file',
		'field_label'    => 'NEC file',
		'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-finance-tab',
	),
);

$access_vehicle = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'accounting',
	'billing',
	'billing-tl',
	'moderator',
	
], true );
?>

<div class="container mt-4 pb-5">
<?php if ( $access_vehicle ): ?>
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <h2>Finance</h2>
		<?php if ( $total_images > 2 ): ?>
            <div class=" <?php echo ( $total_images === 3 ) ? 'show-more-hide-desktop' : 'd-flex '; ?>">
                <button class="js-toggle btn btn-primary change-text"
                        data-block-toggle="js-hide-upload-finance-container">
                    <span class="unactive-text">Show more images (<?php echo $total_images; ?>)</span>
                    <span class="active-text">Show less images (<?php echo $total_images; ?>)</span>
                </button>
            </div>
		<?php endif; ?>
    </div>
	
	<?php if ( $total_images > 0 ): ?>

        <div class="js-hide-upload-finance-container hide-upload-files mb-3" data-class-toggle="hide-upload-files">
            <div class="container-uploads <?php echo $full_only_view ? "read-only" : '' ?>">
				
				<?php
				
				foreach ( $files as $file ):
					if ( isset( $file[ 'file_arr' ] ) && $file[ 'file' ] ):
						echo esc_html( get_template_part( TEMPLATE_PATH . 'common/card', 'file', array(
							'file_arr'       => $file[ 'file_arr' ],
							'file'           => $file[ 'file' ],
							'full_only_view' => $file[ 'full_only_view' ],
							'post_id'        => $file[ 'post_id' ],
							'class_name'     => $file[ 'class_name' ],
							'field_name'     => $file[ 'field_name' ],
							'field_label'    => $file[ 'field_label' ],
							'delete_action'  => $file[ 'delete_action' ],
							'active_tab'     => $file[ 'active_tab' ],
						) ) );
					endif;
				endforeach; ?>
            </div>
        </div>
	
	<?php endif; ?>

    <form class="<?php echo $full_only_view ? '' : 'js-driver-finance-form'; ?>">
		
		<?php if ( $post_id ): ?>
            <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
		<?php endif; ?>

        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label">Account Type<span class="required-star text-danger">*</span></label>
                <select name="account_type" required class="form-control form-select">
                    <option value="Business" <?php echo $account_type === 'Business' ? 'selected' : ''; ?>>Business
                    </option>
                    <option value="Individual" <?php echo $account_type === 'Individual' ? 'selected' : ''; ?>>
                        Individual
                    </option>
                </select>
            </div>

            <div class="col-6 mb-3">
                <label class="form-label">Account Name<span class="required-star text-danger">*</span></label>
                <input type="text" class="form-control" name="account_name" required
                       value="<?php echo $account_name; ?>">
            </div>

            <div class="col-12"></div>

            <div class="col-6 mb-3">
                <label class="form-label">Payment Instruction<span class="required-star text-danger">*</span></label>
                <select name="payment_instruction" required class="form-control form-select">
                    <option value="Void check" <?php echo $payment_instruction === 'Void check' ? 'selected' : ''; ?>>
                        Void check
                    </option>
                    <option value="Direct deposit form" <?php echo $payment_instruction === 'Direct deposit form'
						? 'selected' : ''; ?>>Direct deposit form
                    </option>
                    <option value="Bank statement" <?php echo $payment_instruction === 'Bank statement' ? 'selected'
						: ''; ?>>Bank statement
                    </option>
                </select>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label">Bank payee</label>

                <div class="d-flex flex-wrap gap-4">
					<?php
					if ( is_array( $bank_payees ) ): ?>
						<?php foreach ( $bank_payees as $key => $item ):
							$checked = array_search( $key, $bank_payees_val );
							?>
                            <div class="form-check form-switch p-0">
                                <input class="form-check-input ml-0" <?php echo is_numeric( $checked ) ? 'checked'
									: ''; ?> name="bank_payees[]" value="<?php echo $key; ?>"
                                       type="checkbox" id="flexSwitchCheckDefault_<?php echo $key; ?>">
                                <label class="form-check-label ml-2"
                                       for="flexSwitchCheckDefault_<?php echo $key; ?>"><?php echo $item; ?>
                                </label>
                            </div>
						<?php endforeach; ?>
					<?php endif ?>
                </div>
            </div>

            <div class="col-12">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex align-items-center gap-1">Payment File <?php echo $payment_file
								? $reports->get_icon_uploaded_file() : ''; ?></label>
						<?php if ( ! $payment_file ): ?>

                            <button data-href="#popup_upload_payment_file"
                                    class="btn btn-success js-open-popup-activator mt-1">
                                Upload file
                            </button>
						<?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12"></div>

            <div class="col-12 mb-3">
                <label class="form-label">W-9 Classification<span class="required-star text-danger">*</span></label>
				
				<?php if ( ! $w9_classification ):
					$w9_classification = 'business';
				endif; ?>

                <div class="d-flex flex-wrap gap-2">
                    <div class="form-check">
                        <input class="form-check-input js-toggle-radio" data-target="js-classifications" type="radio"
                               name="w9_classification"
                               id="w9_classification_business"
                               value="business" <?php echo $w9_classification === 'business' ? 'checked' : ''; ?> >
                        <label class="form-check-label" for="w9_classification_business">
                            Business
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input js-toggle-radio" data-target="js-classifications" type="radio"
                               name="w9_classification"
                               id="w9_classification_individual"
                               value="individual" <?php echo $w9_classification === 'individual' ? 'checked' : ''; ?>
                        >
                        <label class="form-check-label" for="w9_classification_individual">
                            Individual
                        </label>
                    </div>
                </div>
            </div>


            <div class="col-12 ">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex align-items-center gap-1">W9 File <?php echo $w9_file
								? $reports->get_icon_uploaded_file() : ''; ?></label>
						<?php if ( ! $w9_file ): ?>
                            <button data-href="#popup_upload_w9_file"
                                    class="btn btn-success js-open-popup-activator mt-1">
                                Upload file
                            </button>
						<?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-6 mb-3">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="address" value="<?php echo $address; ?>">
            </div>

            <div class="col-6 mb-3">
                <label class="form-label">City, State, ZIP</label>
                <input type="text" class="form-control" name="city_state_zip" value="<?php echo $city_state_zip; ?>">
            </div>
        </div>

        <div id="individual_fields"
             class="col-12 js-classifications js-classifications-individual <?php echo $w9_classification === 'individual'
			     ? '' : 'd-none'; ?>">
            <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded">

                <div class="col-6 mb-3">
                    <label class="form-label">SSN<span class="required-star text-danger">*</span></label>
                    <input type="text" class="form-control js-ssn-mask" name="ssn" value="<?php echo $ssn; ?>">
                </div>

                <div class="col-6 mb-3">
                    <label class="form-label">SSN Name<span class="required-star text-danger">*</span></label>
                    <input type="text" class="form-control" name="ssn_name" value="<?php echo $ssn_name; ?>">
                </div>

                <div class="col-12 ">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label d-flex align-items-center gap-1"><span>File<span
                                            class="required-star text-danger">*</span></span> <?php echo $ssn_file
									? $reports->get_icon_uploaded_file() : ''; ?></label>
							<?php if ( ! $ssn_file ): ?>

                                <button data-href="#popup_upload_ssn_file"
                                        class="btn btn-success js-open-popup-activator mt-1">
                                    Upload file
                                </button>
							<?php endif; ?>

                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div id="business_fields"
             class="col-12  js-classifications js-classifications-business  <?php echo $w9_classification === 'business'
			     ? '' : 'd-none'; ?>">

            <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded">
                <div class="col-6 mb-3">
                    <label class="form-label">Entity Name<span class="required-star text-danger">*</span></label>
                    <input type="text" class="form-control" name="entity_name" value="<?php echo $entity_name; ?>">
                </div>

                <div class="col-6 mb-3">
                    <label class="form-label">EIN<span class="required-star text-danger">*</span></label>
                    <input type="text" class="form-control js-ein-mask" name="ein" value="<?php echo $ein; ?>">
                </div>

                <div class="col-12 ">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label d-flex align-items-center gap-1">
                                <span>EIN Form<span
                                            class="required-star text-danger">*</span></span> <?php echo $ein_file
									? $reports->get_icon_uploaded_file() : ''; ?></label>
							
							<?php if ( ! $ein_file ): ?>
                                <button data-href="#popup_upload_ein_file"
                                        class="btn btn-success js-open-popup-activator mt-1">
                                    Upload file
                                </button>
							<?php endif; ?>

                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-12 ">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label d-flex align-items-center gap-1">1099-NEC File <?php echo $nec_file
								? $reports->get_icon_uploaded_file() : ''; ?></label>
						<?php if ( ! $nec_file ): ?>
                            <button data-href="#popup_upload_nec_file"
                                    class="btn btn-success js-open-popup-activator mt-1">
                                Upload file
                            </button>
						<?php endif; ?>
                    </div>
                    <div class="col-12 mb-1 mt-1 preview-photo js-preview-photo-upload">

                    </div>
                </div>
            </div>

            <div class="col-6 mb-3">
                <label class="form-label">Authorized Email</label>
                <input type="email" class="form-control" name="authorized_email"
                       value="<?php echo $authorized_email; ?>">
            </div>
        </div>
        <div class="row">

            <div class="col-12" role="presentation">
                <div class="justify-content-start gap-2">
                    <button type="button" data-tab-id="pills-driver-vehicle-tab"
                            class="btn btn-dark js-next-tab">Previous
                    </button>
					<?php if ( $full_only_view ): ?>
                        <button type="button" data-tab-id="pills-driver-documents-tab"
                                class="btn btn-primary js-next-tab">Next
                        </button>
					<?php else: ?>
                        <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                data-tab-id="pills-driver-documents-tab">
                            Next
                        </button>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </form>
	
	
	<?php
	
	$popups_upload = array(
		array(
			'title'     => 'Upload Payment File',
			'file_name' => 'payment_file',
			'multiply'  => false,
			'driver_id' => $post_id,
		),
		array(
			'title'     => 'Upload W9 File',
			'file_name' => 'w9_file',
			'multiply'  => false,
			'driver_id' => $post_id,
		),
		array(
			'title'     => 'Upload SSN File',
			'file_name' => 'ssn_file',
			'multiply'  => false,
			'driver_id' => $post_id,
		),
		array(
			'title'     => 'Upload EIN File',
			'file_name' => 'ein_file',
			'multiply'  => false,
			'driver_id' => $post_id,
		),
		array(
			'title'     => 'Upload 1099-NEC File',
			'file_name' => 'nec_file',
			'multiply'  => false,
			'driver_id' => $post_id,
		),
	);
	
	foreach ( $popups_upload as $popup ):
		echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload', 'file', $popup ) );
	endforeach;

else: ?> 
    <div class="alert alert-info">You do not have permission to upload documents.</div>
<?php endif; ?>
	
</div>
