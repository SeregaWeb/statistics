<?php
/**
 * Custom header template
 *
 * @package WP-rock
 */

global $global_options;

$helper       = new TMSReportsHelper();
$array_tables = $helper->tms_tables_with_label;
$user_id      = get_current_user_id();
$user_name    = $helper->get_user_full_name_by_id( $user_id );


$view_tables   = get_field( 'permission_view', 'user_' . $user_id );
$curent_tables = get_field( 'current_select', 'user_' . $user_id );

$TMSUsers = new TMSUsers();

$add_broker  = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'billing'
), true );
$add_shipper = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'tracking'
), true );

$login_link = get_field_value( $global_options, 'link_to_login' );
$logout_url = wp_logout_url( ! empty( $login_link ) ? $login_link : home_url() );

?>

<header id="site-header" class="site-header js-site-header">
    <div class="container-fluid">
        <div class="row justify-content-between align-items-center pt-2 pb-2">
            <div class="col main-menu js-main-menu order-2 d-flex gap-2 justify-content-end align-items-center">
				<?php if ( $add_broker ): ?>
                    <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_add_company">Add
                        broker
                    </button>
				<?php endif; ?>
				
				<?php if ( $add_shipper ): ?>
                    <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_add_shipper">Add
                        shipper
                    </button>
				<?php endif; ?>
				<?php if ( is_array( $view_tables ) && sizeof( $view_tables ) > 0 ) ?>
                <div>
                    <select class="form-select js-select-current-table" aria-label="Default select example">
						<?php if ( is_array( $array_tables ) ): ?>
							<?php foreach ( $array_tables as $key => $val ):
								$view = array_search( $key, $view_tables );
								if ( is_numeric( $view ) ): ?>
                                    <option <?php echo $curent_tables === $key ? 'selected' : ''; ?>
                                            value="<?php echo $key; ?>"><?php echo $val; ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php endif; ?>
                    </select>
                </div>
                <div class="text-right d-flex flex-column">
                    <p class="m-0"><?php echo $user_name[ 'full_name' ]; ?></p>
                    <a class="text-small text-danger " href="<?php echo $logout_url; ?>">Logout</a>
                </div>
            </div>
            <div class="col-auto order-1">
                <H2>TMS Portal</H2>
            </div>
            <!--            <div class="col-auto d-lg-none order-2">-->
            <!--                <button type="button" class="menu-btn js-menu-btn" aria-label="Menu" title="Menu" data-role="menu-action"></button>-->
            <!--            </div>-->
        </div>
    </div>
</header>
