<?php

$reports = new TMSReports();

// tab 4
$required_file = '';
$others_files  = '';
$report_object = !empty($args['report_object'])? $args['report_object'] : null;
$post_id = !empty($args['post_id']) ? $args['post_id'] : null;

if ( $report_object ) {
	$values = $report_object;
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
	
	// tab documents start
		$required_file = $values[ 0 ]->attached_file_required;
		$others_files  = $values[ 0 ]->attached_files;

		$required_file_arr = false;
		$others_files_arr  = false;
		
		if ( ! empty( $required_file ) ) {
			$required_file_arr = array(
				'id'  => $required_file,
				'url' => wp_get_attachment_url( $required_file )
			);
		}
		
		if ( ! empty( $others_files ) ) {
			$array_ids_image = explode( ',', $others_files );
			
			if ( is_array( $array_ids_image ) ) {
				foreach ( $array_ids_image as $id_image ) {
					$others_files_arr[] = array(
						'id'  => $id_image,
						'url' => wp_get_attachment_url( $id_image )
					);
				}
			}
			
		}
	}
}

?>

<h3 class="p-0 display-6 mb-4">Upload files</h3>

<?php if ( ($others_files || $required_file) && isset($post_id) ): ?>
	<div class="container-uploads">
		<?php if ( isset( $required_file_arr ) && $required_file ): ?>
			<form class="js-remove-one card-upload required">
				<span class="required-label">Rate Confirmation</span>
				<figure class="card-upload__figure">
					<img class="card-upload__img" src="<?php echo $required_file_arr[ 'url' ] ?>" alt="img">
				</figure>
				<input type="hidden" name="image-id" value="<?php echo $required_file_arr[ 'id' ]; ?>">
				<input type="hidden" name="image-fields" value="attached_file_required">
				<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
				
				<button class="card-upload__btn card-upload__btn--remove" type="submit">
					<?php echo $reports->get_close_icon(); ?>
				</button>
				<a class="card-upload__btn card-upload__btn--download" download href="<?php echo $required_file_arr[ 'url' ]; ?>">
					<?php echo $reports->get_download_icon(); ?>
				</a>
			</form>
		<?php endif; ?>
		
		<?php if ( isset( $others_files_arr ) && is_array($others_files_arr) ):
			foreach ( $others_files_arr as $value ):?>
				<form class="js-remove-one card-upload">
					<figure class="card-upload__figure">
						<img class="card-upload__img" src="<?php echo $value[ 'url' ] ?>" alt="img">
					</figure>
					<input type="hidden" name="image-id"
					       value="<?php echo $value[ 'id' ]; ?>">
					<input type="hidden" name="image-fields" value="attached_files">
					<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
					<button class="card-upload__btn card-upload__btn--remove" type="submit">
						<?php echo $reports->get_close_icon(); ?>
					</button>
					<a class="card-upload__btn card-upload__btn--download" download href="<?php echo $required_file_arr[ 'url' ]; ?>">
						<?php echo $reports->get_download_icon(); ?>
					</a>
				</form>
			<?php endforeach;
		endif; ?>
	</div>
<?php endif; ?>

<form class="js-uploads-files">
	
	<?php if (!$required_file): ?>
		<div class="js-add-new-report">
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
	<div class="js-add-new-report">
		<div class="p-0 mb-2 col-12">
			<p class="h5">Others files</p>
			<label for="attached_files" class="form-label">Attached Files</label>
			<input type="file" name="attached_files[]"
			       class="form-control js-control-uploads" multiple>
		</div>
		
		<div class="p-0 col-12 mb-3 mt-3 preview-photo js-preview-photo-upload">
		
		</div>
	</div>
	
	<div class="col-12 pl-0" role="presentation">
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