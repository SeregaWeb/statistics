<?php
$array_file     = $args[ 'file_arr' ];
$full_only_view = $args[ 'full_only_view' ];
$post_id        = $args[ 'post_id' ];
$reports        = new TMSReportsHelper();
$class_name     = $args[ 'class_name' ];
$field_name     = $args[ 'field_name' ];
$field_label    = $args[ 'field_label' ];
$delete_action  = $args[ 'delete_action' ];
?>

<form class="<?php echo $delete_action; ?> card-upload <?php echo $class_name; ?>">
    <a class="view-document" target="_blank"
       href="<?php echo $array_file[ 'url' ]; ?>"><?php echo $reports->get_icon_view( 'view' ); ?></a>
    <span class="required-label"><?php echo $field_label; ?></span>
    <figure class="card-upload__figure">
		<?php
		if ( ! isset( $array_file[ 'file_name' ] ) ) : ?>
            <img class="card-upload__img" src="<?php echo $array_file[ 'url' ] ?>" alt="img">
		<?php else: ?>
			<?php echo $reports->get_file_icon(); ?>
            <p><?php echo $array_file[ 'file_name' ]; ?></p>
		<?php endif; ?>

    </figure>
    <input type="hidden" name="image-id" value="<?php echo $array_file[ 'id' ]; ?>">
    <input type="hidden" name="image-fields" value="<?php echo $field_name; ?>">
    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	
	<?php if ( ! $full_only_view ): ?>
        <button class="card-upload__btn card-upload__btn--remove" type="submit">
			<?php echo $reports->get_close_icon(); ?>
        </button>
	<?php endif; ?>
    <a class="card-upload__btn card-upload__btn--download" download
       href="<?php echo $array_file[ 'url' ]; ?>">
		<?php echo $reports->get_download_icon(); ?>
    </a>
</form>