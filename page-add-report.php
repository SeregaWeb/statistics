<?php
/**
 * Template Name: Page add reports
 *
 * @package WP-rock
 * @since 4.4.0
 */

$reports = new TMSReports();
$company = new TMSReportsCompany();
$helper = new TMSReportsHelper();
$TMSUsers = new TMSUsers();

$states = $helper->get_states();

$disabled_tabs = 'disabled';

$report_object = '';
$status_publish = 'draft';
$print_status = false;

$post_id = isset( $_GET[ 'post_id' ] ) ? $_GET[ 'post_id' ] : false;

if ( $post_id && is_numeric( $post_id ) ) {
	$report_object = $reports->get_report_by_id( $_GET[ 'post_id' ] );
    $main = get_field_value($report_object, 'main');
    $meta = get_field_value($report_object, 'meta');
    
	if ( is_array( $report_object ) && sizeof( $report_object ) > 0 ) {
		$disabled_tabs = '';
        $status_publish = get_field_value($main, 'status_post');
	} else {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
	
	$message_arr = $reports->check_empty_fields($post_id);
 
 
	$print_status = true;
    $status_type = $message_arr['status'];
    $status_message = $message_arr['message'];
	
    if (!$status_type && $status_publish === "publish") {
        $res = $reports->update_post_status_in_db(array('post_status' => 'draft', 'post_id' => $post_id));
        if ($res) {
	        $status_publish = 'draft';
        }
    }
 
}

$access = $TMSUsers->check_user_role_access(array('recruiter'));

$full_only_view = false;

if ($TMSUsers->check_user_role_access(array('dispatcher'), true) && isset($meta)) {
	$user_id_added = get_field_value($meta, 'dispatcher_initials');

    if (is_array($report_object) && intval($user_id_added) !== get_current_user_id()) {
	    $access = false;
    }
}

if ($TMSUsers->check_user_role_access(array('dispatcher-tl','tracking'), true) && isset($meta)) {
	$user_id_added = get_field_value($meta, 'dispatcher_initials');
	$my_team = $TMSUsers->check_group_access();
	$access = $TMSUsers->check_user_in_my_group($my_team, $user_id_added);
}

if ($access) {
	$full_only_view = $TMSUsers->check_user_role_access(array('billing', 'dispatcher-tl'), true);
}

$billing_info = $TMSUsers->check_user_role_access(array('administrator', 'billing', 'accounting'),true);

get_header();

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container js-section-tab">
                <div class="row">

                    <?php if ($access): ?>
                    
                    <div class="col-12 js-update-status mt-3">

                        <?php
                        if (isset($status_publish) && $status_publish === 'draft') {
                            if ($print_status) {
                                if ($status_type) {
                                    echo $helper->message_top('success', $status_message, 'js-update-post-status', 'Publish');
                                } else {
                                    echo $helper->message_top('danger', $status_message);
                                }
                            }
                        }
                        ?>
                    
                    </div>
                    
                    <div class="col-12">

                        <ul class="nav nav-pills gap-2 mb-3" id="pills-tab" role="tablist">
                            <li class="nav-item flex-grow-1" role="presentation">
                                <button class="nav-link w-100 active" id="pills-customer-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-customer" type="button" role="tab"
                                        aria-controls="pills-customer" aria-selected="true">Customer
                                </button>
                            </li>
                            <li class="nav-item flex-grow-1" role="presentation">
                                <button class="nav-link w-100 <?php echo $disabled_tabs; ?> " id="pills-load-tab"
                                        data-bs-toggle="pill"
                                        data-bs-target="#pills-load" type="button" role="tab" aria-controls="pills-load"
                                        aria-selected="false">Load
                                </button>
                            </li>
                            <li class="nav-item flex-grow-1" role="presentation">
                                <button class="nav-link w-100 <?php echo $disabled_tabs; ?> " id="pills-trip-tab"
                                        data-bs-toggle="pill"
                                        data-bs-target="#pills-trip" type="button" role="tab" aria-controls="pills-trip"
                                        aria-selected="false">Trip
                                </button>
                            </li>
                            <li class="nav-item flex-grow-1" role="presentation">
                                <button class="nav-link w-100 <?php echo $disabled_tabs; ?> " id="pills-documents-tab"
                                        data-bs-toggle="pill"
                                        data-bs-target="#pills-documents" type="button" role="tab"
                                        aria-controls="pills-documents" aria-selected="false">Documents
                                </button>
                            </li>

                            <?php if ($billing_info): ?>
                            <li class="nav-item flex-grow-1" role="presentation">
                                <button class="nav-link w-100 <?php echo $disabled_tabs; ?> " id="pills-billing-tab"
                                        data-bs-toggle="pill"
                                        data-bs-target="#pills-billing" type="button" role="tab"
                                        aria-controls="pills-billing" aria-selected="false">Billing
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>
                       
                        <div class="tab-content" id="pills-tabContent">

                            <div class="tab-pane fade show active" id="pills-customer" role="tabpanel"
                                 aria-labelledby="pills-customer-tab">
	                            <?php
	                            echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-customer', array(
                                    'full_view_only' => $full_only_view,
		                            'report_object' => $report_object,
		                            'post_id'       => $post_id
	                            ) ) );
	                            ?>
                            </div>

                            <div class="tab-pane fade" id="pills-load" role="tabpanel" aria-labelledby="pills-load-tab">
								<?php
								echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-load', array(
									'full_view_only' => $full_only_view,
                                    'report_object' => $report_object,
									'post_id'       => $post_id
								) ) );
								?>
                            </div>
                            
                            <div class="tab-pane fade" id="pills-trip" role="tabpanel" aria-labelledby="pills-trip-tab">
	                            <?php
	                            echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-shipper', array(
		                            'full_view_only' => $full_only_view,
		                            'report_object' => $report_object,
		                            'post_id'       => $post_id
	                            ) ) );
	                            ?>
                            </div>
                            
                            <div class="tab-pane fade" id="pills-documents" role="tabpanel"
                                 aria-labelledby="pills-documents-tab">
								
								<?php
								echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-documents', array(
									'full_view_only' => $full_only_view,
									'report_object' => $report_object,
									'post_id'       => $post_id
								) ) );
								?>
                            </div>
	
	                        <?php if ($billing_info): ?>
                            
                            <div class="tab-pane fade" id="pills-billing" role="tabpanel"
                                 aria-labelledby="pills-billing-tab">
		                        
		                        <?php
		                        echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-billing', array(
			                        'full_view_only' => $full_only_view,
			                        'report_object' => $report_object,
			                        'post_id'       => $post_id
		                        ) ) );
		                        ?>
                            </div>
                            
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php else: ?>
                        <div class="col-12 mt-3">
                        <?php
                            echo $helper->message_top('danger', $helper->messages_prepare('not-access'));
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
