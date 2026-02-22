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

$show_notifications_button = $TMSUsers->check_user_role_access(
	array(
		'administrator',
		'tracking',
		'tracking-tl',
		'nightshift_tracking',
		'morning_tracking',
	),
	true
);

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

$login_link = get_field_value( $global_options, 'link_to_login' );
$logout_url = wp_logout_url( ! empty( $login_link ) ? $login_link : home_url() );

?>

<header id="site-header" class="site-header js-site-header">
    <div class="container-fluid">
        <div class="row justify-content-between align-items-center pt-2 pb-2">
            <div class="col main-menu js-main-menu order-2 d-flex gap-2 justify-content-end align-items-center">
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
                    <div class="align-items-center m-0 d-flex gap-2">
                        <div class="d-flex align-items-center gap-1">
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
                </div>
                <div class="text-right d-flex flex-column">
                    <div class="d-flex gap-1 align-items-center justify-content-between">

                
                    
                    <p class="m-0"><?php echo $user_name[ 'full_name' ]; ?></p>
                    
                    </div>
                    <a class="text-small text-danger " href="<?php echo $logout_url; ?>">Logout</a>
                </div>

			 <div>
			 <?php if ( $show_notifications_button ) : ?>
                            <button
                                type="button"
                                class="btn p-0 tms-notifications-toggle night_mode__label"
                                id="tms-notifications-toggle"
                                aria-label="Notifications"
                                style="color: inherit; text-decoration: none;"
                            >
                                <span class="tms-notifications-icon" aria-hidden="true" style="position: relative; display: inline-flex;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 22C10.9 22 10 21.1 10 20H14C14 21.1 13.1 22 12 22Z" fill="currentColor"/>
                                        <path d="M18 16V11C18 7.93 16.36 5.36 13.5 4.68V4C13.5 3.17 12.83 2.5 12 2.5C11.17 2.5 10.5 3.17 10.5 4V4.68C7.64 5.36 6 7.92 6 11V16L4.71 17.29C4.08 17.92 4.52 19 5.41 19H18.59C19.48 19 19.92 17.92 19.29 17.29L18 16Z" fill="currentColor"/>
                                    </svg>
                                    <span
                                        class="tms-notifications-badge"
                                        id="tms-notifications-badge"
                                        style="position: absolute; top: -10px; right: -10px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 50%; background-color: #dc3545; color: #fff; font-size: 11px; font-weight: 700; display: none; align-items: center; justify-content: center; line-height: 1;"
                                    ></span>
                                </span>
                            </button>
						<?php endif; ?>
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
$tms_sound_muted = false;
if ( $show_notifications_button && $user_id > 0 ) {
	$tms_sound_muted = get_user_meta( $user_id, 'tms_notifications_sound_muted', true ) === '1';
}
if ( $show_notifications_button ) : ?>
	<div
		id="tms-notifications-panel"
		class="tms-notifications-panel"
	>
		<div class="tms-notifications-panel__header">
			<strong class="tms-notifications-panel__title">Notifications</strong>
			<button type="button" id="tms-notifications-close" class="btn btn-sm btn-light tms-notifications-panel__close">×</button>
		</div>
		<div id="tms-notifications-list" class="tms-notifications-panel__list"></div>
		<div id="tms-notifications-footer" class="tms-notifications-panel__footer">
			<button type="button" id="tms-notifications-load-older" class="btn btn-sm btn-outline-secondary tms-notifications-panel__footer-btn tms-notifications-panel__footer-btn--hidden">Load older</button>
			<div class="d-flex gap-1 align-items-center">
				<button type="button" id="tms-notifications-sound-toggle" class="btn btn-sm <?php echo $tms_sound_muted ? 'btn-outline-danger' : 'btn-outline-secondary'; ?> tms-notifications-sound-toggle-btn tms-notifications-panel__footer-btn" title="<?php echo esc_attr( $tms_sound_muted ? __( 'Unmute sound', 'wp-rock' ) : __( 'Mute sound', 'wp-rock' ) ); ?>" aria-label="<?php echo esc_attr( $tms_sound_muted ? __( 'Unmute sound', 'wp-rock' ) : __( 'Mute sound', 'wp-rock' ) ); ?>">
					<span class="tms-notifications-sound-icon" aria-hidden="true"><?php echo $tms_sound_muted ? '<svg width="18" height="18" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path fill-rule="evenodd" d="m1505.845 72.093-187.52 223.467c-44.16-32.64-93.333-61.013-147.2-83.947-67.84-27.626-138.773-43.84-211.093-49.386V53H853.365v109.333c-357.44 27.414-640 326.4-640 690.667v373.333c0 56.427-22.72 111.574-62.293 151.04-39.467 39.574-94.613 62.294-151.04 62.294v106.666h269.333L119.18 1725.427l81.706 68.48L1587.552 140.573l-81.707-68.48ZM1479.467 462.6C1558.293 577.587 1600 712.627 1600 853v373.333c0 117.654 95.68 213.334 213.333 213.334 29.44 0 53.334 23.893 53.334 53.333 0 29.44-23.894 53.333-53.334 53.333h-586.666c0 176.427-143.574 320-320 320-176.427 0-320-143.573-320-320V1493c0-29.44 23.893-53.333 53.333-53.333h935.04c-50.773-56.64-81.707-131.414-81.707-213.334V853c0-118.72-35.2-232.96-101.76-330.027ZM1120 1546.333H693.333c0 117.654 95.68 213.334 213.334 213.334 117.653 0 213.333-95.68 213.333-213.334Zm-213.301-1280c77.12 0 152.426 14.827 223.253 43.734 43.733 18.666 83.84 41.813 119.573 67.626L358.86 1439.667h-120c51.733-58.027 81.173-134.827 81.173-213.334V853c0-323.413 263.253-586.667 586.667-586.667Z"/></svg>' : '<svg width="18" height="18" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path fill-rule="evenodd" d="M1185.928 1581.176c0 124.575-101.309 225.883-225.883 225.883-124.574 0-225.882-101.308-225.882-225.883h451.765ZM960.045 225.882c342.438 0 621.177 278.626 621.177 621.177v395.294c0 86.739 32.753 165.91 86.4 225.882H252.356c53.76-59.971 86.513-139.143 86.513-225.882V847.059c0-342.55 278.626-621.177 621.176-621.177Zm734.118 1016.47V847.06c0-385.694-299.294-702.268-677.647-731.294V0H903.575v115.765c-378.466 29.026-677.647 345.6-677.647 731.294v395.294c0 124.574-101.309 225.882-225.883 225.882v112.941h621.177c0 186.805 151.906 338.824 338.823 338.824 186.805 0 338.824-152.019 338.824-338.824h621.176v-112.94c-124.574 0-225.882-101.309-225.882-225.883Z"/></svg>'; ?></span>
				</button>
				<button type="button" id="tms-notifications-mark-all" class="btn btn-sm btn-outline-secondary tms-notifications-panel__footer-btn">Mark all read</button>
				<button type="button" id="tms-notifications-clear-all" class="btn btn-sm btn-outline-danger tms-notifications-panel__footer-btn">Clear all</button>
			</div>
		</div>
	</div>

	<?php
		$notifications_endpoint       = esc_url_raw( rest_url( 'tms/v1/notifications' ) );
		$notifications_read_endpoint  = esc_url_raw( rest_url( 'tms/v1/notifications/read' ) );
		$notifications_read_all_url   = esc_url_raw( rest_url( 'tms/v1/notifications/read-all' ) );
		$notifications_clear_all_url  = esc_url_raw( rest_url( 'tms/v1/notifications/clear-all' ) );
		$notifications_nonce          = wp_create_nonce( 'wp_rest' );

		$notifications_initial = array(
			'items'        => array(),
			'unread_count' => 0,
			'total_count'  => 0,
			'has_more'     => false,
		);
		if ( $user_id > 0 ) {
			$tms_notifications = new TMSNotifications();
			$limit             = 20;
			$page              = 1;
			$result            = $tms_notifications->get_user_notifications( $user_id, $limit, false, $page );
			$notifications_initial['items']        = $result['items'];
			$notifications_initial['unread_count'] = (int) $result['unread_count'];
			$notifications_initial['total_count']  = (int) $result['total_count'];
			$notifications_initial['has_more']     = ( ( $page * $limit ) < (int) $result['total_count'] );
		}
		$notifications_sound_muted_url = esc_url_raw( rest_url( 'tms/v1/notifications/sound-muted' ) );
		$tms_notifications_config      = array(
			'apiListUrl'           => $notifications_endpoint,
			'apiReadUrl'           => $notifications_read_endpoint,
			'apiReadAllUrl'        => $notifications_read_all_url,
			'apiClearAllUrl'       => $notifications_clear_all_url,
			'apiSoundMutedUrl'     => $notifications_sound_muted_url,
			'restNonce'            => $notifications_nonce,
			'initial'              => $notifications_initial,
			'soundUrl'             => get_template_directory_uri() . '/assets/sounds/notification-1.mp3',
			'soundMuted'           => $tms_sound_muted,
			'soundMutedLabelMute'  => __( 'Mute sound', 'wp-rock' ),
			'soundMutedLabelUnmute' => __( 'Unmute sound', 'wp-rock' ),
		);
		?>
	<script>
		window.TMSNotificationsConfig = <?php echo wp_json_encode( $tms_notifications_config ); ?>;
	</script>
<?php endif; ?>

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
	if ( ! empty( $dispatcher_ratings ) && $dispatcher_ratings['unrated_loads'] >= 5 && $dispatcher_ratings['rated_percentage'] < 90 ) {
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
