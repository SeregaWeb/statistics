<?php
$id             = get_field_value( $args, 'id' );
$meta           = get_field_value( $args, 'meta' );
$hide_ctrl      = get_field_value( $args, 'hide_ctrl' );
$pinned_message = get_field_value( $meta, 'message_pinned' );
$time_pinned    = get_field_value( $meta, 'time_pinned' );
$user_pinned_id = get_field_value( $meta, 'user_pinned_id' );
$TMSUsers       = new TMSUsers();

//$delete_pin = $TMSUsers->check_user_role_access( array(
//	'administrator',
//	'tracking-tl',
//), true );

if ( $pinned_message && $time_pinned && $user_pinned_id ) {
	$name_user   = $TMSUsers->get_user_full_name_by_id( $user_pinned_id );
	$time_pinned = date( 'm/d/Y H:i', $time_pinned );
}

if ( $pinned_message && $time_pinned && $user_pinned_id ) { ?>
    <div class="pinned-message">
        <div class="d-flex justify-content-between align-items-center pinned-message__header">
            <span class="d-flex align-items-center ">
                <svg fill="#000000" width="18px" height="18px" viewBox="0 0 32 32" version="1.1"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.973 17.802l-7.794-4.5c-0.956-0.553-2.18-0.225-2.732 0.731-0.552 0.957-0.224 2.18 0.732 2.732l7.793 4.5c0.957 0.553 2.18 0.225 2.732-0.732 0.554-0.956 0.226-2.179-0.731-2.731zM12.545 12.936l6.062 3.5 2.062-5.738-4.186-2.416-3.938 4.654zM8.076 27.676l5.799-7.044-2.598-1.5-3.201 8.544zM23.174 7.525l-5.195-3c-0.718-0.414-1.635-0.169-2.049 0.549-0.415 0.718-0.168 1.635 0.549 2.049l5.196 3c0.718 0.414 1.635 0.169 2.049-0.549s0.168-1.635-0.55-2.049z"></path>
                </svg>
                <?php echo $name_user[ 'full_name' ]; ?></span>
            <span><?php echo $time_pinned; ?></span>
        </div>
        <div class="pinned-message__content">
			<?php echo $pinned_message; ?>
        </div>
		<?php if ( ! $hide_ctrl ): ?>
            <div class="pinned-message__footer d-flex justify-content-end">
                <button class="btn btn-danger btn-sm js-delete-pinned-message" data-id="<?php echo $id; ?>">Remove
                </button>
            </div>
		<?php endif; ?>
    </div>
<?php } ?>