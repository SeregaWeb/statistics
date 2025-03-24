<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$reports = new TMSReports();
$driver  = new TMSDrivers();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$account_type        = get_field_value( $meta, 'account_type' );
$account_name        = get_field_value( $meta, 'account_name' );
$payment_instruction = get_field_value( $meta, 'payment_instruction' );
$payment_file        = get_field_value( $meta, 'payment_file' );
$w9_classification   = get_field_value( $meta, 'w9_classification' );
$w9_file             = get_field_value( $meta, 'w9_file' );
$address             = get_field_value( $meta, 'address' );
$city_state_zip      = get_field_value( $meta, 'city_state_zip' );
$ssn                 = get_field_value( $meta, 'ssn' );
$ssn_name            = get_field_value( $meta, 'ssn_name' );
$ssn_file            = get_field_value( $meta, 'ssn_file' );
$entity_name         = get_field_value( $meta, 'entity_name' );
$ein                 = get_field_value( $meta, 'ein' );
$ein_file            = get_field_value( $meta, 'ein_file' );
$nec_file            = get_field_value( $meta, 'nec_file' );
$authorized_email    = get_field_value( $meta, 'authorized_email' );

?>

<div class="container mt-4 pb-5">
    <form>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Account Type<span class="required-star text-danger">*</span></label>
                <select name="account_type" required class="form-control form-select">
                    <option value="Business" <?php echo $account_type === 'Business' ? 'selected' : ''; ?>>Business
                    </option>
                    <option value="Individual" <?php echo $account_type === 'Individual' ? 'selected' : ''; ?>>
                        Individual
                    </option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Account Name<span class="required-star text-danger">*</span></label>
                <input type="text" class="form-control" name="account_name" required
                       value="<?php echo $account_name; ?>">
            </div>

            <div class="col-md-4 mb-3">
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

            <div class="col-md-4 mb-3">
                <label class="form-label">File</label>
                <input type="file" class="form-control" name="payment_file" value="<?php echo $payment_file; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">W-9 Classification<span class="required-star text-danger">*</span></label>
                <select name="w9_classification" id="w9_classification" required class="form-control form-select">
                    <option value="Business" <?php echo $w9_classification === 'Business' ? 'selected' : ''; ?>>
                        Business
                    </option>
                    <option value="Individual" <?php echo $w9_classification === 'Individual' ? 'selected' : ''; ?>>
                        Individual
                    </option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">File</label>
                <input type="file" class="form-control" name="w9_file" value="<?php echo $w9_file; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="address" value="<?php echo $address; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">City, State, ZIP</label>
                <input type="text" class="form-control" name="city_state_zip" value="<?php echo $city_state_zip; ?>">
            </div>
        </div>

        <div id="individual_fields" class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">SSN<span class="required-star text-danger">*</span></label>
                <input type="text" class="form-control" name="ssn" value="<?php echo $ssn; ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">SSN Name<span class="required-star text-danger">*</span></label>
                <input type="text" class="form-control" name="ssn_name" value="<?php echo $ssn_name; ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">File<span class="required-star text-danger">*</span></label>
                <input type="file" class="form-control" name="ssn_file" value="<?php echo $ssn_file; ?>" required>
            </div>
        </div>

        <div id="business_fields" class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Entity Name<span class="required-star text-danger">*</span></label>
                <input type="text" class="form-control" name="entity_name" value="<?php echo $entity_name; ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">EIN<span class="required-star text-danger">*</span></label>
                <input type="text" class="form-control" name="ein" value="<?php echo $ein; ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">EIN Form<span class="required-star text-danger">*</span></label>
                <input type="file" class="form-control" name="ein_file" value="<?php echo $ein_file; ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">1099-NEC File</label>
                <input type="file" class="form-control" name="nec_file" value="<?php echo $nec_file; ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Authorized Email</label>
                <input type="email" class="form-control" name="authorized_email"
                       value="<?php echo $authorized_email; ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
