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

$modal_rated = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'dispatcher-tl',
	'expedite_manager',
), true );

$exclude_dispatchers_modal = get_field_value( $global_options, 'exclude_users_rating_modal' );
if ( is_array( $exclude_dispatchers_modal ) && ! empty( $exclude_dispatchers_modal ) ) {
	$exclude_dispatchers_modal = array_map( 'intval', $exclude_dispatchers_modal );
} else {
    $exclude_dispatchers_modal = array();
}

if ( in_array( $user_id, $exclude_dispatchers_modal ) ) {
	$modal_rated = false;
}

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
	'tracking',
	'morning_tracking',
	'nightshift_tracking',
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
                <div>
                <div class=" align-items-center m-0 d-flex gap-1">
                        <input class="form-check-input night_mode__checkbox" type="checkbox" id="night_mode" name="night_mode" value="1">
                        <label class="form-check-label night_mode__label" for="night_mode">
                        <svg class="night_mode__icon light" 
                        width="24" height="24" viewBox="0 0 20 20" 
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" 
                            d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z" fill="currentColor">
                            </path>
                        </svg>
                        <svg class="night_mode__icon dark" width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z" fill="currentColor">
                            </path>
                        </svg>
                        </label>
                    </div>
                </div>
                <div class="text-right d-flex flex-column">
                    <div class="d-flex gap-1 align-items-center justify-content-between">

                
                    
                    <p class="m-0"><?php echo $user_name[ 'full_name' ]; ?></p>
                    
                    </div>
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

<?php 
// Alert banner for administrators
if ($modal_rated) {
	$Drivers = new TMSDrivers();
	$current_month = date('m');
	$current_year = date('Y');

	// if (current_user_can('administrator')) {
	// 	$user_id = 51;
	// } 
	
	$dispatcher_ratings = $Drivers->get_dispatcher_rating_statistics_summary( $user_id, $current_year, $current_month );
	
	// Show alert only if there are unrated loads
	if ( ! empty( $dispatcher_ratings ) && $dispatcher_ratings['unrated_loads'] > 0 && $dispatcher_ratings['rated_percentage'] < 90 ) {
		$unrated_loads = $dispatcher_ratings['unrated_loads'];
		$rated_percentage = $dispatcher_ratings['rated_percentage'];
		
		// Determine alert color: red if unrated > 5 OR rated < 50%, otherwise primary (blue)
		$alert_class = ( $unrated_loads > 5 || $rated_percentage < 50 ) ? 'alert-danger' : 'alert-primary';
		
		?>
		<div class="alert <?php echo esc_attr( $alert_class ); ?> d-flex align-items-center justify-content-center text-center mb-1" role="alert">
			
			<div>
				You currently have <?php echo esc_html( $unrated_loads ); ?> unrated load<?php echo $unrated_loads !== 1 ? 's' : ''; ?>. Please take a moment to rate the drivers.
			</div>
		</div>

		<!-- Rating Reminder Modal -->
		<div class="modal fade" id="ratingReminderModal" tabindex="-1" aria-labelledby="ratingReminderModalLabel" aria-hidden="true" data-bs-backdrop="false" data-user-id="<?php echo esc_attr( $user_id ); ?>">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header border-0 pb-0">
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body text-center">
						<h5 class="modal-title mb-3" id="ratingReminderModalLabel">Rating Reminder</h5>
						<p class="mb-0">
							You currently have <?php echo esc_html( $unrated_loads ); ?> unrated load<?php echo $unrated_loads !== 1 ? 's' : ''; ?>. Please take a moment to rate the drivers.
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
?>
