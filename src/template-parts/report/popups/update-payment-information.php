<?php
$driver_id = $args['driver_id'];
$account_type = $args['account_type'] ?? '';
$account_name = $args['account_name'] ?? '';
$payment_instruction = $args['payment_instruction'] ?? '';
?>

<div id="popup_update_payment_information" class="popup popup-quick-edit">
    <div class="my_overlay js-popup-close"></div>
    <div class="popup__wrapper-inner js-video-container">
        <div class="popup-container js-add-new-report">
            <button class="popup-close js-popup-close">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M31.6666 10.6833L29.3166 8.33331L20 17.65L10.6833 8.33331L8.33331 10.6833L17.65  20L8.33331 29.3166L10.6833 31.6666L20 22.35L29.3166 31.6666L31.6666 29.3166L22.35 20L31.6666 10.6833Z"
                          fill="black"/>
                </svg>
            </button>
            <form class="js-update-payment-information-form">
                <input type="hidden" name="driver_id" value="<?php echo esc_attr($driver_id); ?>">
                <?php wp_nonce_field('tms_update_payment_info', 'tms_payment_info_nonce'); ?>
                
                <h2 class="custom-upload__title">Update Payment Information</h2>
                
                <div class="mb-3">
                    <label class="form-label">Account Type<span class="required-star text-danger">*</span></label>
                    <select name="account_type" required class="form-control form-select">
                        <option value="Business" <?php echo $account_type === 'Business' ? 'selected' : ''; ?>>Business</option>
                        <option value="Individual" <?php echo $account_type === 'Individual' ? 'selected' : ''; ?>>Individual</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Account Name<span class="required-star text-danger">*</span></label>
                    <input type="text" class="form-control" name="account_name" required value="<?php echo esc_attr($account_name); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Instruction<span class="required-star text-danger">*</span></label>
                    <select name="payment_instruction" required class="form-control form-select">
                        <option value="Void check" <?php echo $payment_instruction === 'Void check' ? 'selected' : ''; ?>>Void check</option>
                        <option value="Direct deposit form" <?php echo $payment_instruction === 'Direct deposit form' ? 'selected' : ''; ?>>Direct deposit form</option>
                        <option value="Bank statement" <?php echo $payment_instruction === 'Bank statement' ? 'selected' : ''; ?>>Bank statement</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment File</label>
                    <p class="custom-upload__description">file upload limit 16MB (one file)</p>
                    <label class="upload-area">
                        <p>Drag & drop your file here or <strong>click to upload</strong></p>
                        <input type="file" class="file-input js-control-uploads" name="payment_file">
                    </label>
                    <div class="mb-1 mt-1 preview-photo js-preview-photo-upload"></div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary js-popup-close">Cancel</button>
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

