<?php
/**
 * Template Name: Page add reports
 *
 * @package WP-rock
 * @since 4.4.0
 */

$reports  = new TMSReports();
$company  = new TMSReportsCompany();
$helper   = new TMSReportsHelper();
$TMSUsers = new TMSUsers();

$states = $helper->get_states();

$disabled_tabs = 'disabled';

$report_object  = '';
$status_publish = 'draft';
$print_status   = false;

$post_id = isset( $_GET[ 'post_id' ] ) ? $_GET[ 'post_id' ] : false;

if ( $post_id && is_numeric( $post_id ) ) {
	$report_object = $reports->get_report_by_id( $_GET[ 'post_id' ] );
	$main          = get_field_value( $report_object, 'main' );
	$meta          = get_field_value( $report_object, 'meta' );
	
	if ( is_array( $report_object ) && sizeof( $report_object ) > 0 ) {
		$disabled_tabs  = '';
		$status_publish = get_field_value( $main, 'status_post' );
	} else {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
	
	$message_arr    = $reports->check_empty_fields( $post_id, $meta );
	$print_status   = true;
	$status_type    = $message_arr[ 'status' ];
	$status_message = $message_arr[ 'message' ];
	
	if ( ! $status_type && $status_publish === "publish" ) {
		$res = $reports->update_post_status_in_db( array( 'post_status' => 'draft', 'post_id' => $post_id ) );
		if ( $res ) {
			$status_publish = 'draft';
		}
	}
	
	$send_mesaage     = get_field_value( $meta, 'mail_chain_success_send' );
	$log_file         = get_field_value( $meta, 'log_file' );
	$factoring_status = get_field_value( $meta, 'factoring_status' );
}

$access         = $TMSUsers->check_user_role_access( array( 'recruiter' ) );
$full_only_view = false;


if ( $TMSUsers->check_user_role_access( array( 'dispatcher-tl', 'tracking' ), true ) && isset( $meta ) ) {
	$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
	$user_id_added       = get_field_value( $main, 'user_id_added' );
	$my_team             = $TMSUsers->check_group_access();
	$current_user_id     = get_current_user_id();
	
	
	if ( $current_user_id === intval( $user_id_added ) || intval( $dispatcher_initials ) === intval( $current_user_id ) ) {
		$access         = true;
		$full_only_view = false;
	} else {
		if ( $TMSUsers->check_user_in_my_group( $my_team, intval( $dispatcher_initials ) ) ) {
			$access = true;
		} else {
			$access = false;
		}
	}
}


if ( $access ) {
	$full_only_view = $TMSUsers->check_user_role_access( array( 'billing', 'moderator' ), true );
}

$billing_info = $TMSUsers->check_user_role_access( array( 'administrator', 'billing', 'accounting' ), true );

$tracking_tl = false;
if ( $TMSUsers->check_user_role_access( array( 'tracking-tl' ), true ) && isset( $meta ) ) {
	$full_only_view = true;
	$tracking_tl    = true;
}

if ( $TMSUsers->check_user_role_access( array( 'dispatcher' ), true ) && isset( $meta ) ) {
	$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
	$user_id_added       = get_field_value( $main, 'user_id_added' );
	
	if ( is_array( $report_object ) ) {
		$current_user_id = get_current_user_id();
		if ( intval( $user_id_added ) === $current_user_id || intval( $dispatcher_initials ) === $current_user_id ) {
			$access         = true;
			$full_only_view = false;
		} else {
			$access = false;
		}
	}
}

if ( $status_publish === 'draft' ) {
	$full_only_view = false;
}

if ( isset( $factoring_status ) && $factoring_status == 'paid' && ! $TMSUsers->check_user_role_access( array( 'administrator' ), true ) ) {
	$full_only_view = true;
}

get_header();

$logshow        = isset( $_COOKIE[ 'logshow' ] ) && + $_COOKIE[ 'logshow' ] !== 0 ? 'hidden-logs col-lg-1' : 'col-lg-3';
$logshowcontent = isset( $_COOKIE[ 'logshow' ] ) && + $_COOKIE[ 'logshow' ] !== 0 ? 'col-lg-11' : 'col-lg-9';

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container js-section-tab">
                <div class="row js-logs-wrap">
					
					<?php if ( $access ): ?>

                        <div class="col-12 js-update-status mt-3">
							
							<?php
							
							if ( 'Odysseia' === $reports->project ) {
								$access_for_btn = $TMSUsers->check_user_role_access( array(
									'administrator',
									'dispatcher-tl',
									'dispatcher',
									'tracking',
									'tracking-tl'
								), true );
								
								$pick_up_location  = get_field_value( $meta, 'pick_up_location' );
								$delivery_location = get_field_value( $meta, 'delivery_location' );
								
								if ( isset( $status_publish ) && $status_publish === 'publish' && ! empty( $pick_up_location ) && ! empty( $delivery_location ) ) {
									if ( ! isset( $send_mesaage ) || ! $send_mesaage ) {
										?>
                                        <form class="w-100 d-flex justify-content-end mb-3 js-send-email-chain">
                                            <input type="hidden" name="load_id" value="<?php echo $post_id; ?>">
                                            <button class="btn btn-warning">Create tracking chain</button>
                                        </form>
										<?php
									} else {
										?>
                                        <div class="w-100 d-flex justify-content-end mb-3">
                                            <button class="btn btn-success" disabled>Tracking chain created successful
                                            </button>
                                        </div>
										<?php
									}
								}
							}
							
							if ( isset( $status_publish ) && $status_publish === 'draft' ) {
								if ( $print_status ) {
									if ( $status_type ) {
										echo $helper->message_top( 'success', $status_message, 'js-update-post-status', 'Publish' );
									} else {
										echo $helper->message_top( 'danger', $status_message );
									}
								}
							}
							?>

                        </div>

                        <div class="col-12 js-logs-content <?php echo $logshowcontent; ?>">

                            <ul class="nav nav-pills gap-2 mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $helper->change_active_tab( 'pills-customer-tab' ); ?> "
                                            id="pills-customer-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-customer" type="button" role="tab"
                                            aria-controls="pills-customer" aria-selected="true">Customer
                                    </button>
                                </li>
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs; ?> <?php echo $helper->change_active_tab( 'pills-load-tab' ); ?> "
                                            id="pills-load-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-load" type="button" role="tab"
                                            aria-controls="pills-load"
                                            aria-selected="false">Load
                                    </button>
                                </li>
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs;
									echo $helper->change_active_tab( 'pills-trip-tab' ); ?> " id="pills-trip-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-trip" type="button" role="tab"
                                            aria-controls="pills-trip"
                                            aria-selected="false">Trip
                                    </button>
                                </li>
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs;
									echo $helper->change_active_tab( 'pills-documents-tab' ); ?> "
                                            id="pills-documents-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-documents" type="button" role="tab"
                                            aria-controls="pills-documents" aria-selected="false">Documents
                                    </button>
                                </li>
								
								<?php if ( $billing_info ): ?>
                                    <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                        <button class="nav-link w-100 <?php echo $disabled_tabs;
										echo $helper->change_active_tab( 'pills-billing-tab' ); ?> "
                                                id="pills-billing-tab"
                                                data-bs-toggle="pill"
                                                data-bs-target="#pills-billing" type="button" role="tab"
                                                aria-controls="pills-billing" aria-selected="false">Billing
                                        </button>
                                    </li>
                                    <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                        <button class="nav-link w-100 <?php echo $disabled_tabs;
										echo $helper->change_active_tab( 'pills-accounting-tab' ); ?> "
                                                id="pills-accounting-tab"
                                                data-bs-toggle="pill"
                                                data-bs-target="#pills-accounting" type="button" role="tab"
                                                aria-controls="pills-accounting" aria-selected="false">Accounting
                                        </button>
                                    </li>
								<?php endif; ?>
                            </ul>

                            <div class="tab-content" id="pills-tabContent">

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-customer-tab', 'show' ); ?>"
                                     id="pills-customer" role="tabpanel"
                                     aria-labelledby="pills-customer-tab">
									<?php
									echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-customer', array(
										'full_view_only' => $full_only_view,
										'report_object'  => $report_object,
										'post_id'        => $post_id
									) ) );
									?>
                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-load-tab', 'show' ); ?>"
                                     id="pills-load" role="tabpanel" aria-labelledby="pills-load-tab">
									<?php
									echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-load', array(
										'full_view_only' => $full_only_view,
										'tracking_tl'    => $tracking_tl,
										'report_object'  => $report_object,
										'post_id'        => $post_id
									) ) );
									?>
                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-trip-tab', 'show' ); ?>"
                                     id="pills-trip" role="tabpanel" aria-labelledby="pills-trip-tab">
									<?php
									echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-shipper', array(
										'full_view_only' => $full_only_view,
										'report_object'  => $report_object,
										'post_id'        => $post_id
									) ) );
									?>
                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-documents-tab', 'show' ); ?>"
                                     id="pills-documents" role="tabpanel"
                                     aria-labelledby="pills-documents-tab">
									
									<?php
									echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-documents', array(
										'full_view_only' => $full_only_view,
										'report_object'  => $report_object,
										'post_id'        => $post_id,
										'tracking_tl'    => $tracking_tl,
									) ) );
									?>
                                </div>
								
								<?php if ( $billing_info ): ?>

                                    <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-billing-tab', 'show' ); ?>"
                                         id="pills-billing" role="tabpanel"
                                         aria-labelledby="pills-billing-tab">
										
										<?php
										echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-billing', array(
											'full_view_only' => $full_only_view,
											'report_object'  => $report_object,
											'post_id'        => $post_id
										) ) );
										?>
                                    </div>

                                    <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-accounting-tab', 'show' ); ?>"
                                         id="pills-accounting" role="tabpanel"
                                         aria-labelledby="pills-accounting-tab">
										
										<?php
										echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-accounting', array(
											'full_view_only' => $full_only_view,
											'report_object'  => $report_object,
											'post_id'        => $post_id
										) ) );
										?>
                                    </div>
								
								<?php endif; ?>
                            </div>
                        </div>

                        <div class="col-12 js-logs-container <?php echo $logshow; ?>">
							<?php
							if ( isset( $log_file ) && ! empty( $log_file ) ) {
								$file_url = wp_get_attachment_url( $log_file );
								if ( $file_url ) {
									?>
                                    <a class="file-btn" href="<?php echo $file_url; ?>" target="_blank"
                                       rel="noopener noreferrer">
										<?php echo $helper->get_file_icon(); ?>
                                        Open Log Archive
                                    </a>
									<?php
								}
							} else {
								echo esc_html( get_template_part( 'src/template-parts/report/report', 'logs', array(
									'post_id' => $post_id,
									'user_id' => get_current_user_id(),
								) ) );
							}
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

echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add' ) );

get_footer();
