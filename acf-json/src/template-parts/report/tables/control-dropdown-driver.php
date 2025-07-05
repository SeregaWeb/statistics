<?php

global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_driver' );
$id           = get_field_value( $args, 'id' );
$is_draft     = get_field_value( $args, 'is_draft' );
$TMSHelper    = new TMSReportsHelper();
$TMSUsers     = new TMSUsers();
?>

<div class="dropdown">
    <button class="btn button-action" type="button" id="dropdownMenu2"
            data-bs-toggle="dropdown"
            aria-expanded="false">
		<?php echo $TMSHelper->get_dropdown_load_icon(); ?>
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
		<?php if ( $TMSUsers->check_user_role_access( array( 'billing' ), true ) ) : ?>
            <li><a href="<?php echo $add_new_load . '?driver=' . $id; ?>"
                   class="dropdown-item">View</a></li>
		<?php else: ?>
            <li><a href="<?php echo $add_new_load . '?driver=' . $id; ?>"
                   class="dropdown-item">Edit</a></li>
		<?php endif; ?>
		
		<?php if ( $TMSUsers->check_user_role_access( array( 'administrator' ), true ) || $is_draft ): ?>
            <li>
                <button class="dropdown-item text-danger js-remove-driver"
                        data-id="<?php echo $id; ?>" type="button">Delete
                </button>
            </li>
		<?php endif; ?>
    </ul>
</div>