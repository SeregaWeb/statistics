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

$states = $helper->get_states();

$disabled_tabs = 'disabled';

$report_object = '';
$status_publish = 'draft';
$print_status = false;

$post_id = isset( $_GET[ 'post_id' ] ) ? $_GET[ 'post_id' ] : false;

if ( $post_id && is_numeric( $post_id ) ) {
	$report_object = $reports->get_report_by_id( $_GET[ 'post_id' ] );
	if ( is_array( $report_object ) && sizeof( $report_object ) > 0 ) {
		$disabled_tabs = '';
        $status_publish = $report_object[0]->status_post;
	} else {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
	
	$message_arr = $reports->check_empty_fields($post_id);
	$print_status = true;
    $status_type = $message_arr['status'];
    $status_message = $message_arr['message'];
}


get_header();

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container js-section-tab">
                <div class="row">
                    
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
                        </ul>
                       
                        <div class="tab-content" id="pills-tabContent">

                            <div class="tab-pane fade show active" id="pills-customer" role="tabpanel"
                                 aria-labelledby="pills-customer-tab">
	                            <?php
	                            echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-customer', array(
		                            'report_object' => $report_object,
		                            'post_id'       => $post_id
	                            ) ) );
	                            ?>
                            </div>

                            <div class="tab-pane fade" id="pills-load" role="tabpanel" aria-labelledby="pills-load-tab">
								<?php
								echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-load', array(
									'report_object' => $report_object,
									'post_id'       => $post_id
								) ) );
								?>
                            </div>


                            <div class="tab-pane fade" id="pills-trip" role="tabpanel" aria-labelledby="pills-trip-tab">
	                            <?php
	                            echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-shipper', array(
		                            'report_object' => $report_object,
		                            'post_id'       => $post_id
	                            ) ) );
	                            ?>
                            </div>


                            <div class="tab-pane fade" id="pills-documents" role="tabpanel"
                                 aria-labelledby="pills-documents-tab">
								
								<?php
								echo esc_html( get_template_part( 'src/template-parts/report/report', 'tab-documents', array(
									'report_object' => $report_object,
									'post_id'       => $post_id
								) ) );
								?>
                            </div>
                        </div>
                    </div>
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
