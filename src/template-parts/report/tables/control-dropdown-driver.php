<?php

global $global_options;

$args_drivers_ftl = get_field_value( $args, 'drivers_ftl' );


$add_new_load = $args_drivers_ftl ? get_field_value( $global_options, 'add_new_driver_ftl' ) : get_field_value( $global_options, 'add_new_driver' );

$id           = get_field_value( $args, 'id' );
$is_draft     = get_field_value( $args, 'is_draft' );
$is_archive   = get_field_value( $args, 'is_archive' );
$TMSHelper    = new TMSReportsHelper();
$TMSUsers     = new TMSUsers();
$TMSIcons     = new TMSReportsIcons();

$is_admin = $TMSUsers->check_user_role_access( array( 'administrator', 'recruiter-tl' ), true );


if ( $is_admin || $is_draft ): ?>
    <div class="dropdown">
        <button class="btn button-action" type="button" id="dropdownMenu2"
                data-bs-toggle="dropdown"
                aria-expanded="false">
			<?php echo $TMSHelper->get_dropdown_load_icon(); ?>
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
            <li><a href="<?php echo $add_new_load . '?driver=' . $id; ?>"
                   class="dropdown-item">Edit</a></li>
			
            <?php if ( $is_admin || $is_draft ): ?>
                <?php if ( $is_draft || $is_archive ): ?>
                    <li>
                        <button class="dropdown-item text-danger js-remove-driver"
                                data-id="<?php echo $id; ?>" type="button">Delete
                        </button>
                    </li>
                <?php else: ?>
                    <li>
                        <button class="dropdown-item text-danger js-remove-driver-soft"
                                data-id="<?php echo $id; ?>" type="button"
                                data-bs-toggle="modal" data-bs-target="#removeDriverModal">Delete
                        </button>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($is_admin && $is_archive): ?>
                <li>
                    <button class="dropdown-item text-success js-restore-driver"
                            data-id="<?php echo $id; ?>" type="button">Restore
                    </button>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php else: ?>
    <a href="<?php echo $add_new_load . '?driver=' . $id; ?>" class="btn btn-sm button-action">
		<?php echo $TMSIcons->get_icon_link_out(); ?>
    </a>
<?php endif; ?>