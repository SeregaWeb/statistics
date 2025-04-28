<?php
$file_name  = $args[ 'file_name' ];
$title      = $args[ 'title' ];
$multiply   = $args[ 'multiply' ];
$driver_id  = $args[ 'driver_id' ];
$need_check = $args[ 'need_check' ] ?? false;

?>


<div id="popup_upload_<?php echo $file_name; ?>" class="popup popup-quick-edit js-upload-popup">
    <div class="my_overlay js-popup-close"></div>
    <div class="popup__wrapper-inner js-video-container">
        <div class="popup-container js-add-new-report">
            <button class="popup-close js-popup-close">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M31.6666 10.6833L29.3166 8.33331L20 17.65L10.6833 8.33331L8.33331 10.6833L17.65  20L8.33331 29.3166L10.6833 31.6666L20 22.35L29.3166 31.6666L31.6666 29.3166L22.35 20L31.6666 10.6833Z"
                          fill="black"/>
                </svg>
            </button>
            <form class="js-upload-driver-helper custom-upload">
				
				<?php if ( $need_check ): ?>
                    <input type="hidden" name="need_check" value="<?php echo $need_check; ?>">
				<?php endif; ?>

                <input type="hidden" name="driver_id" value="<?php echo $driver_id; ?>">
                <h2 class="custom-upload__title"><?php echo $title; ?></h2>
                <p class="custom-upload__description">file upload limit 16MB (one file)</p>

                <label class="upload-area">
                    <p>Drag & drop your file here or <strong>click to upload</strong></p>
                    <input type="file" <?php echo $multiply ? 'multiple' : ''; ?> class="file-input js-control-uploads"
                           name="<?php echo $file_name;
					       echo $multiply ? '[]' : '' ?>">
                </label>

                <div class="mb-1 mt-1 preview-photo js-preview-photo-upload">

                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

