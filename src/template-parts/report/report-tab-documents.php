<?php

$reports  = new TMSReports();
$TMSUsers = new TMSUsers();

// tab 4
$required_file = '';
$others_files  = '';
$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;

$billing_info = $TMSUsers->check_user_role_access( array( 'administrator', 'billing', 'accounting' ), true );

$required_file_for_user = $TMSUsers->check_user_role_access( array( 'billing', 'tracking' ), true );

if ( $report_object ) {
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	$full_view_only = get_field_value( $args, 'full_view_only' );
	$tracking_tl    = get_field_value( $args, 'tracking_tl' );
	
	if ( $full_view_only && $tracking_tl ) {
		$full_view_only = false;
	}
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		
		$post_status = get_field_value( $main, 'status_post' );
		
		// tab documents start
		$required_file     = get_field_value( $meta, 'attached_file_required' );
		$others_files      = get_field_value( $meta, 'attached_files' );
		$update_rate_conf  = get_field_value( $meta, 'updated_rate_confirmation' );
		$screen_picture    = get_field_value( $meta, 'screen_picture' );
		$proof_of_delivery = get_field_value( $meta, 'proof_of_delivery' );
		
		$reference_number = get_field_value( $meta, 'reference_number' );
		
		$tbd = get_field_value( $meta, 'tbd' );
		
		$required_file_arr    = false;
		$others_files_arr     = false;
		$update_rate_conf_arr = false;
		if ( ! empty( $required_file ) ) {
			
			$attachment_url = wp_get_attachment_url( $required_file );
			if ( wp_attachment_is_image( $required_file ) ) {
				$required_file_arr = array(
					'id'  => $required_file,
					'url' => $attachment_url,
				);
			} else {
				$file_name = basename( $attachment_url );
				
				$required_file_arr = array(
					'id'        => $required_file,
					'url'       => $attachment_url,
					'file_name' => $file_name,
				);
			}
		}
		
		if ( ! empty( $screen_picture ) ) {
			
			$attachment_url = wp_get_attachment_url( $screen_picture );
			if ( wp_attachment_is_image( $screen_picture ) ) {
				$screen_picture_arr = array(
					'id'  => $screen_picture,
					'url' => $attachment_url,
				);
			} else {
				$file_name = basename( $attachment_url );
				
				$screen_picture_arr = array(
					'id'        => $screen_picture,
					'url'       => $attachment_url,
					'file_name' => $file_name,
				);
			}
		}
		
		if ( ! empty( $proof_of_delivery ) ) {
			
			$attachment_url = wp_get_attachment_url( $proof_of_delivery );
			if ( wp_attachment_is_image( $proof_of_delivery ) ) {
				$proof_of_delivery_arr = array(
					'id'  => $proof_of_delivery,
					'url' => $attachment_url,
				);
			} else {
				$file_name = basename( $attachment_url );
				
				$proof_of_delivery_arr = array(
					'id'        => $proof_of_delivery,
					'url'       => $attachment_url,
					'file_name' => $file_name,
				);
			}
		}
		
		if ( ! empty( $update_rate_conf ) ) {
			
			$attachment_url = wp_get_attachment_url( $update_rate_conf );
			if ( wp_attachment_is_image( $update_rate_conf ) ) {
				$update_rate_conf_arr = array(
					'id'  => $update_rate_conf,
					'url' => $attachment_url,
				);
			} else {
				$file_name = basename( $attachment_url );
				
				$update_rate_conf_arr = array(
					'id'        => $update_rate_conf,
					'url'       => $attachment_url,
					'file_name' => $file_name,
				);
			}
		}
		
		if ( ! empty( $others_files ) ) {
			$array_ids_image = explode( ',', $others_files );
			
			if ( is_array( $array_ids_image ) ) {
				foreach ( $array_ids_image as $id_image ) {
					
					$attachment_url = wp_get_attachment_url( $id_image );
					if ( wp_attachment_is_image( $id_image ) ) {
						$others_files_arr[] = array(
							'id'  => $id_image,
							'url' => $attachment_url,
						);
					} else {
						$file_name = basename( $attachment_url );
						
						$others_files_arr[] = array(
							'id'        => $id_image,
							'url'       => $attachment_url,
							'file_name' => $file_name,
						);
					}
				}
			}
			
		}
	}
}

?>

<h3 class="p-0 display-6 mb-4">Upload files</h3>

<?php if ( ( $others_files || $required_file || $screen_picture || $update_rate_conf || $proof_of_delivery ) && isset( $post_id ) ): ?>
    <div class="container-uploads <?php echo $full_view_only ? "read-only" : '' ?>">
		<?php if ( isset( $required_file_arr ) && $required_file ): ?>
            <form class="js-remove-one card-upload required">
                <a class="view-document" target="_blank"
                   href="<?php echo $required_file_arr[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                <span class="required-label">Rate Confirmation</span>
                <figure class="card-upload__figure">
					<?php
					if ( ! isset( $required_file_arr[ 'file_name' ] ) ) : ?>
                        <img class="card-upload__img" src="<?php echo $required_file_arr[ 'url' ] ?>" alt="img">
					<?php else: ?>
						<?php echo $reports->get_file_icon(); ?>
                        <p><?php echo $required_file_arr[ 'file_name' ]; ?></p>
					<?php endif; ?>

                </figure>
                <input type="hidden" name="status_post" value="<?php echo $post_status ?>">
                <input type="hidden" name="image-id" value="<?php echo $required_file_arr[ 'id' ]; ?>">
                <input type="hidden" name="image-fields" value="attached_file_required">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="hidden" name="reference_number" value="<?php echo $reference_number; ?>">
				
				<?php if ( ! $full_view_only ): ?>
                    <button class="card-upload__btn card-upload__btn--remove" type="submit">
						<?php echo $reports->get_close_icon(); ?>
                    </button>
				<?php endif; ?>
                <a class="card-upload__btn card-upload__btn--download" download
                   href="<?php echo $required_file_arr[ 'url' ]; ?>">
					<?php echo $reports->get_download_icon(); ?>
                </a>
            </form>
		<?php endif; ?>
		
		
		<?php
		if ( isset( $update_rate_conf_arr ) && $update_rate_conf ): ?>
            <form class="js-remove-one card-upload updated">
                <a class="view-document" target="_blank"
                   href="<?php echo $update_rate_conf_arr[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                <span class="required-label">Updated Rate Confirmation</span>
                <figure class="card-upload__figure">
					
					<?php
					if ( ! isset( $update_rate_conf_arr[ 'file_name' ] ) ) : ?>
                        <img class="card-upload__img" src="<?php echo $update_rate_conf_arr[ 'url' ] ?>" alt="img">
					<?php else: ?>
						<?php echo $reports->get_file_icon(); ?>
                        <p><?php echo $update_rate_conf_arr[ 'file_name' ]; ?></p>
					<?php endif; ?>

                </figure>
                <input type="hidden" name="image-id" value="<?php echo $update_rate_conf_arr[ 'id' ]; ?>">
                <input type="hidden" name="image-fields" value="updated_rate_confirmation">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
				
				<?php if ( ! $full_view_only ): ?>
                    <button class="card-upload__btn card-upload__btn--remove" type="submit">
						<?php echo $reports->get_close_icon(); ?>
                    </button>
				<?php endif; ?>
                <a class="card-upload__btn card-upload__btn--download" download
                   href="<?php echo $update_rate_conf_arr[ 'url' ]; ?>">
					<?php echo $reports->get_download_icon(); ?>
                </a>
            </form>
		<?php endif; ?>
		
		<?php if ( isset( $screen_picture_arr ) && $screen_picture ): ?>
            <form class="js-remove-one card-upload screen-picture">
                <a class="view-document" target="_blank"
                   href="<?php echo $screen_picture_arr[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                <span class="required-label">Dispatch message</span>
                <figure class="card-upload__figure">
					
					<?php
					if ( ! isset( $screen_picture_arr[ 'file_name' ] ) ) : ?>
                        <img class="card-upload__img" src="<?php echo $screen_picture_arr[ 'url' ] ?>" alt="img">
					<?php else: ?>
						<?php echo $reports->get_file_icon(); ?>
                        <p><?php echo $screen_picture_arr[ 'file_name' ]; ?></p>
					<?php endif; ?>

                </figure>
                <input type="hidden" name="image-id" value="<?php echo $screen_picture_arr[ 'id' ]; ?>">
                <input type="hidden" name="image-fields" value="screen_picture">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
				
				<?php if ( ! $full_view_only ): ?>
                    <button class="card-upload__btn card-upload__btn--remove" type="submit">
						<?php echo $reports->get_close_icon(); ?>
                    </button>
				<?php endif; ?>
                <a class="card-upload__btn card-upload__btn--download" download
                   href="<?php echo $screen_picture_arr[ 'url' ]; ?>">
					<?php echo $reports->get_download_icon(); ?>
                </a>
            </form>
		<?php endif; ?>
		
		<?php if ( isset( $proof_of_delivery_arr ) && $proof_of_delivery ): ?>
            <form class="js-remove-one card-upload proof_of_delivery">
                <a class="view-document" target="_blank"
                   href="<?php echo $proof_of_delivery_arr[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                <span class="required-label">Proof Of Delivery</span>
                <figure class="card-upload__figure">
					
					<?php
					if ( ! isset( $proof_of_delivery_arr[ 'file_name' ] ) ) : ?>
                        <img class="card-upload__img" src="<?php echo $proof_of_delivery_arr[ 'url' ] ?>" alt="img">
					<?php else: ?>
						<?php echo $reports->get_file_icon(); ?>
                        <p><?php echo $proof_of_delivery_arr[ 'file_name' ]; ?></p>
					<?php endif; ?>

                </figure>
                <input type="hidden" name="image-id" value="<?php echo $proof_of_delivery_arr[ 'id' ]; ?>">
                <input type="hidden" name="image-fields" value="proof_of_delivery">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
				
				<?php if ( ! $full_view_only ): ?>
                    <button class="card-upload__btn card-upload__btn--remove" type="submit">
						<?php echo $reports->get_close_icon(); ?>
                    </button>
				<?php endif; ?>
                <a class="card-upload__btn card-upload__btn--download" download
                   href="<?php echo $proof_of_delivery_arr[ 'url' ]; ?>">
					<?php echo $reports->get_download_icon(); ?>
                </a>
            </form>
		<?php endif; ?>
		
		
		<?php if ( isset( $others_files_arr ) && is_array( $others_files_arr ) ):
			foreach ( $others_files_arr as $value ):?>
                <form class="js-remove-one card-upload">
                    <a class="view-document" target="_blank"
                       href="<?php echo $value[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
                    <figure class="card-upload__figure">
						
						<?php
						if ( ! isset( $value[ 'file_name' ] ) ) : ?>
                            <img class="card-upload__img" src="<?php echo $value[ 'url' ] ?>" alt="img">
						<?php else: ?>
							<?php echo $reports->get_file_icon(); ?>
                            <p><?php echo $value[ 'file_name' ]; ?></p>
						<?php endif; ?>

                    </figure>
                    <input type="hidden" name="image-id"
                           value="<?php echo $value[ 'id' ]; ?>">
                    <input type="hidden" name="image-fields" value="attached_files">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
					<?php if ( ! $full_view_only ): ?>
                        <button class="card-upload__btn card-upload__btn--remove" type="submit">
							<?php echo $reports->get_close_icon(); ?>
                        </button>
					<?php endif; ?>
                    <a class="card-upload__btn card-upload__btn--download" download
                       href="<?php echo $required_file_arr[ 'url' ]; ?>">
						<?php echo $reports->get_download_icon(); ?>
                    </a>
                </form>
			<?php endforeach;
		endif; ?>
    </div>
<?php endif; ?>

<?php if ( ! $full_view_only ): ?>
    <form class="js-uploads-files d-grid">
		
		<?php if ( ! $required_file ): ?>
            <div class="js-add-new-report order-1">
                <div class="p-0 mb-2 col-12">
                    <p class="h5">Required file <span
                                class="required-star text-danger">*</span></p>
                    <label for="attached_file_required" class="form-label">Rate
                        Confirmation</label>
                    <input type="file" <?php $required_file ? '' : 'required' ?>
                           name="attached_file_required"
                           class="form-control js-control-uploads">
                </div>

                <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

                </div>
            </div>
		<?php endif; ?>
        <div class="js-add-new-report order-2">
            <div class="p-0 mb-2 col-12">
                <p class="h5">Other files</p>
                <label for="attached_files" class="form-label">Attached Files</label>
                <input type="file" name="attached_files[]"
                       class="form-control js-control-uploads" multiple>
            </div>

            <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

            </div>
        </div>
		
		<?php if ( ! $update_rate_conf ): ?>
            <div class="js-add-new-report order-3">
                <div class="p-0 mb-2 col-12">
                    <p class="h5">Updated rate confirmation</p>
                    <label for="update_rate_confirmation" class="form-label">Optional file</label>
                    <input type="file" name="update_rate_confirmation"
                           class="form-control js-control-uploads">
                </div>

                <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

                </div>
            </div>
		<?php endif; ?>
		
		<?php if ( ! $screen_picture ): ?>
            <div class="js-add-new-report order-4">
                <div class="p-0 mb-2 col-12">
                    <p class="h5">Dispatch message
						
						<?php if ( ! $tbd ): ?>
                            <span class="required-star text-danger">*</span>
						<?php endif; ?>
                    </p>
                    <label for="screen_picture" class="form-label">screen picture</label>
                    <input type="file" <?php echo ! $tbd ? 'required' : ''; ?>
                           name="screen_picture"
                           class="form-control js-control-uploads">
                </div>

                <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

                </div>
            </div>
		<?php endif; ?>
		
		<?php if ( ! $proof_of_delivery ): ?>
            <div class="js-add-new-report order-4">
                <div class="p-0 mb-2 col-12">
                    <p class="h5">
                        Proof Of Delivery
						<?php if ( $required_file_for_user ): ?>
                            <span class="required-star text-danger">*</span>
						<?php endif; ?>
                    </p>
                    <label for="proof_of_delivery" class="form-label">POD file</label>
                    <input type="file" <?php echo $required_file_for_user ? 'required' : ''; ?>
                           name="proof_of_delivery"
                           class="form-control js-control-uploads">
                </div>

                <div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">

                </div>
            </div>
		<?php endif; ?>


        <div class="col-12 pl-0 order-5" role="presentation">
            <div class="justify-content-start gap-2">
                <button type="button" data-tab-id="pills-trip-tab"
                        class="btn btn-dark js-next-tab">Previous
                </button>
                <button type="submit" class="btn btn-primary js-submit-and-next-tab">Upload
                </button>
            </div>
        </div>
		
		<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
		<?php endif; ?>
    </form>
<?php else: ?>

    <div class="col-12 pl-0 order-5" role="presentation">
        <div class="justify-content-start gap-2">
            <button type="button" data-tab-id="pills-trip-tab"
                    class="btn btn-dark js-next-tab">Previous
            </button>
			
			<?php if ( $billing_info ): ?>
                <button type="button" data-tab-id="pills-billing-tab"
                        class="btn btn-primary js-next-tab">Next
                </button>
			<?php endif; ?>
        </div>
    </div>

<?php endif; ?>


