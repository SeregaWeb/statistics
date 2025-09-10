<?php
/**
 * Template Name: Page add driver
 *
 * @package WP-rock
 * @since 4.4.0
 */

$dtiver       = new TMSDrivers();
$helperDriver = new TMSDriversHelper();
$helper       = new TMSReportsHelper();
$TMSUsers     = new TMSUsers();


$disabled_tabs  = 'disabled';
$driver_object  = '';
$status_publish = 'draft';
$access_publish = false;
$full_only_view = true;

$report_object = null;
$post_id       = isset( $_GET[ 'driver' ] ) ? $_GET[ 'driver' ] : false;

if ( $post_id && is_numeric( $post_id ) ) {
	$driver_object   = $dtiver->get_driver_by_id( $post_id );
	$main            = get_field_value( $driver_object, 'main' );
	$meta            = get_field_value( $driver_object, 'meta' );
	$user_id_added   = get_field_value( $main, 'user_id_added' );
	$current_user_id = get_current_user_id();
	
	$recruiter_add = get_field_value( $meta, 'recruiter_add' );
	
	$driver_name = get_field_value( $meta, 'driver_name' );
	
	$access_publish = ( + $user_id_added === + $current_user_id ) || ( + $recruiter_add === + $current_user_id ) || $TMSUsers->check_user_role_access( array(
			'administrator',
		), true );
	
	$full_only_view = ( + $user_id_added !== + $current_user_id ) && ( + $recruiter_add !== + $current_user_id );
	
	// Check if driver is on hold
	$driver_status = '';
	$is_on_hold    = false;
	$hold_info     = null;
	
	$driver_status = get_field_value( $meta, 'driver_status' );
	$is_on_hold    = ( $driver_status === 'on_hold' );
	
	
	// Get hold information if driver is on hold
	$hold_info        = null;
	$can_edit_on_hold = false;
	if ( $is_on_hold ) {
		$hold_info = $dtiver->get_driver_hold_info( $post_id );
		
		// Check if current user can edit driver on hold
		$current_user_id = get_current_user_id();
		
		// Administrator can always edit
		if ( $TMSUsers->check_user_role_access( array( 'administrator' ), true ) ) {
			$can_edit_on_hold = true;
		} // Dispatcher who placed the hold can edit
        elseif ( $hold_info && isset( $hold_info[ 'dispatcher_id' ] ) && $hold_info[ 'dispatcher_id' ] == $current_user_id ) {
			$can_edit_on_hold = true;
		}
	}
	
	if ( is_array( $driver_object ) && sizeof( $driver_object ) > 0 ) {
		$disabled_tabs  = '';
		$status_publish = get_field_value( $main, 'status_post' );
	} else {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
	
	if ( $TMSUsers->check_user_role_access( array(
		'administrator',
		'recruiter-tl',
		'recruiter',
		'hr_manager',
	), true ) ) {
		$full_only_view = false;
	}
	
	// If user can edit on hold, override full_only_view
	if ( $is_on_hold && $can_edit_on_hold ) {
		$full_only_view = false;
	}
	
	
}

if ( $status_publish === 'draft' ) {
	$full_only_view = false;
}

get_header();

$logshow        = isset( $_COOKIE[ 'logshow' ] ) && + $_COOKIE[ 'logshow' ] !== 0 ? 'hidden-logs col-lg-1' : 'col-lg-3';
$logshowcontent = isset( $_COOKIE[ 'logshow' ] ) && + $_COOKIE[ 'logshow' ] !== 0 ? 'col-lg-11' : 'col-lg-9';


$access = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'accounting',
	'billing',
	'driver_updates',
	'dispatcher',
	'dispatcher-tl',
	'tracking',
	'tracking-tl',
	'morning_tracking',
	'nightshift_tracking'
], true );


$access_only_location = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'accounting',
	'driver_updates',
	'dispatcher',
	'dispatcher-tl',
	'tracking',
	'tracking-tl',
	'morning_tracking',
	'nightshift_tracking'
], false );


$access_vehicle = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'accounting',
	'billing',
	'billing-tl',
	'moderator',
], true );


?>
    <div class="container-fluid">
        <input type="hidden" name="post_id" class="js-post-id" value="<?php echo $post_id; ?>"/>

        <div class="row">
            <div class="container js-section-tab">
                <div class="row js-logs-wrap">
					
					<?php if ( $access ): ?>

                        <div class="col-12">
                            <div class="row">
                                <div class="col-12 mt-2 mb-2">
                                    <h3>
                                        (<?php echo $post_id; ?>) <?php echo $driver_name; ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 js-logs-content <?php echo $logshowcontent; ?>">
							<?php
							
							// Show hold message if driver is on hold
							if ( isset( $is_on_hold ) && $is_on_hold ) {
								$hold_message = 'This driver is currently on hold.';
								
								if ( $can_edit_on_hold ) {
									$hold_message .= ' You can edit this driver.';
								} else {
									$hold_message .= ' You cannot edit this driver while it is on hold.';
								}
								
								if ( $hold_info && is_array( $hold_info ) ) {
									if ( isset( $hold_info[ 'dispatcher_name' ] ) ) {
										$hold_message .= ' Hold placed by: ' . $hold_info[ 'dispatcher_name' ];
									}
									if ( isset( $hold_info[ 'minutes_left' ] ) ) {
										if ( $hold_info[ 'minutes_left' ] > 0 ) {
											$hold_message .= ' (Expires in ' . $hold_info[ 'minutes_left' ] . ' minutes)';
										} else {
											$hold_message .= ' (Hold expired)';
										}
									}
								}
								
								$message_type = $can_edit_on_hold ? 'info' : 'warning';
								echo $helper->message_top( $message_type, $hold_message, '', '' );
							}
							
							if ( isset( $status_publish ) && $status_publish === 'draft' ) {
								if ( $access_publish ) {
									echo $helper->message_top( 'success', 'Publish this driver ?', 'js-update-driver-status', 'Publish' );
								}
							}
							
							if ( $full_only_view && $access_vehicle ) {
								echo $helper->message_top( 'warning', 'View only.', '', '' );
							}
							
							?>
							
							<?php if ( ! $is_on_hold || $can_edit_on_hold ): ?>
                                <ul class="nav nav-pills gap-2 mb-3" id="pills-tab" role="tablist">
									
									<?php if ( $post_id ): ?>
                                        <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                            <button class="nav-link w-100 <?php echo $disabled_tabs;
											echo $helper->change_active_tab( 'pills-driver-location-tab', 'show', $access_vehicle
												? 'drivers' : 'dispatchers' ); ?> "
                                                    id="pills-driver-location-tab"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#pills-driver-location" type="button" role="tab"
                                                    aria-controls="pills-driver-location" aria-selected="false">Current
                                                location
                                            </button>
                                        </li>
									<?php endif; ?>
									
									
									<?php if ( $access_vehicle ): ?>
                                        <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                            <button class="nav-link w-100 <?php echo $helper->change_active_tab( 'pills-driver-contact-tab', 'show', $access_vehicle
												? 'drivers' : 'dispatchers' ); ?> "
                                                    id="pills-driver-contact-tab" data-bs-toggle="pill"
                                                    data-bs-target="#pills-driver-contact" type="button" role="tab"
                                                    aria-controls="pills-driver-contact" aria-selected="true">Contact
                                            </button>
                                        </li>

                                        <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                            <button class="nav-link w-100 <?php echo $disabled_tabs; ?> <?php echo $helper->change_active_tab( 'pills-driver-vehicle-tab', 'show', $access_vehicle
												? 'drivers' : 'dispatchers' ); ?> "
                                                    id="pills-driver-vehicle-tab"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#pills-driver-vehicle" type="button" role="tab"
                                                    aria-controls="pills-driver-vehicle"
                                                    aria-selected="false">Vehicle
                                            </button>
                                        </li>

                                        <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                            <button class="nav-link w-100 <?php echo $disabled_tabs;
											echo $helper->change_active_tab( 'pills-driver-finance-tab', 'show', $access_vehicle
												? 'drivers' : 'dispatchers' ); ?> "
                                                    id="pills-driver-finance-tab"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#pills-driver-finance" type="button" role="tab"
                                                    aria-controls="pills-driver-finance" aria-selected="false">Financial
                                            </button>
                                        </li>

                                        <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                            <button class="nav-link w-100 <?php echo $disabled_tabs;
											echo $helper->change_active_tab( 'pills-driver-documents-tab', 'show', $access_vehicle
												? 'drivers' : 'dispatchers' ); ?> "
                                                    id="pills-driver-documents-tab"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#pills-driver-documents" type="button" role="tab"
                                                    aria-controls="pills-driver-documents" aria-selected="false">
                                                Documents
                                            </button>
                                        </li>
									<?php else: ?>
                                        <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                            <button class="nav-link w-100 <?php echo $disabled_tabs; ?> <?php echo $helper->change_active_tab( 'pills-driver-vehicle-tab', 'show', $access_vehicle
												? 'drivers' : 'dispatchers' ); ?> "
                                                    id="pills-driver-vehicle-tab"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#pills-driver-vehicle" type="button" role="tab"
                                                    aria-controls="pills-driver-vehicle"
                                                    aria-selected="false">Information
                                            </button>
                                        </li>
									<?php endif; ?>


                                    <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                        <button class="nav-link w-100 <?php echo $disabled_tabs;
										echo $helper->change_active_tab( 'pills-driver-stats-tab', 'show', $access_vehicle
											? 'drivers' : 'dispatchers' ); ?> "
                                                id="pills-driver-stats-tab"
                                                data-bs-toggle="pill"
                                                data-bs-target="#pills-driver-stats" type="button" role="tab"
                                                aria-controls="pills-driver-stats" aria-selected="false">Statistic
                                        </button>
                                    </li>

                                </ul>
							<?php endif; ?>
							
							<?php if ( ! $is_on_hold || $can_edit_on_hold ): ?>
                                <div class="tab-content" id="pills-tabContent">
									
									<?php if ( $access_vehicle ): ?>
                                        <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-contact-tab', 'show', $access_vehicle
											? 'drivers' : '' ); ?>"
                                             id="pills-driver-contact" role="tabpanel"
                                             aria-labelledby="pills-driver-contact-tab">
											<?php
											
											
											echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-contact', array(
												'full_view_only' => $full_only_view,
												'report_object'  => $driver_object,
												'post_id'        => $post_id
											) ) );
											?>
                                        </div>

                                        <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-vehicle-tab', 'show' ); ?>"
                                             id="pills-driver-vehicle" role="tabpanel"
                                             aria-labelledby="pills-driver-vehicle-tab">
											<?php
											echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-information', array(
												'full_view_only' => $full_only_view,
												'report_object'  => $driver_object,
												'post_id'        => $post_id
											) ) );
											?>
                                        </div>

                                        <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-finance-tab', 'show' ); ?>"
                                             id="pills-driver-finance" role="tabpanel"
                                             aria-labelledby="pills-driver-finance-tab">
											<?php
											
											$not_access_for_finance = $full_only_view;
											
											if ( $TMSUsers->check_user_role_access( array( 'accounting' ), true ) ) {
												$not_access_for_finance = false;
											}
											
											// If user can edit on hold, allow finance editing
											if ( $is_on_hold && $can_edit_on_hold ) {
												$not_access_for_finance = false;
											}
											
											echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-finance', array(
												'full_view_only' => $not_access_for_finance,
												'report_object'  => $driver_object,
												'post_id'        => $post_id
											) ) );
											?>

                                        </div>

                                        <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-documents-tab', 'show' ); ?>"
                                             id="pills-driver-documents" role="tabpanel"
                                             aria-labelledby="pills-driver-documents-tab">
											
											<?php
											echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-document', array(
												'full_view_only' => $full_only_view,
												'report_object'  => $driver_object,
												'post_id'        => $post_id
											) ) );
											?>
                                        </div>
									
									
									<?php else: ?>
                                        <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-vehicle-tab', 'show' ); ?>"
                                             id="pills-driver-vehicle" role="tabpanel"
                                             aria-labelledby="pills-driver-vehicle-tab">
											<?php
											echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-information-disabled', array(
												'full_view_only' => $full_only_view,
												'report_object'  => $driver_object,
												'post_id'        => $post_id
											) ) );
											?>
                                        </div>
									<?php endif; ?>

                                    <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-location-tab', 'show', ! $access_vehicle
										? 'dispatchers' : '' ); ?>"
                                         id="pills-driver-location" role="tabpanel"
                                         aria-labelledby="pills-driver-location-tab">
										<?php
										echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-location', array(
											'full_view_only' => $access_only_location,
											'report_object'  => $driver_object,
											'post_id'        => $post_id
										) ) );
										?>
                                    </div>

                                    <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-stats-tab', 'show', 'drivers' ); ?>"
                                         id="pills-driver-stats" role="tabpanel"
                                         aria-labelledby="pills-driver-stats-tab">
										<?php
										echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-stats', array(
											'full_view_only' => $full_only_view,
											'report_object'  => $driver_object,
											'post_id'        => $post_id
										) ) );
										?>
                                    </div>


                                </div>
							<?php endif; ?>
                        </div>

                        <div class="col-12 js-logs-container <?php echo $logshow; ?>">
							<?php
							echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'logs', array(
								'post_id'   => $post_id,
								'user_id'   => get_current_user_id(),
								'post_type' => 'driver',
							) ) );
							?>
                        </div>
					
					<?php else: ?>
                        <div class="col-12 col-lg-9 mt-3">
							<?php
							echo $helper->message_top( 'danger', $helper->messages_prepare( 'not-access' ) );
							?>
                        </div>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
