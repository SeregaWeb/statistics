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


$disabled_tabs  = 'disabled';
$driver_object  = '';
$status_publish = 'draft';


$post_id = isset( $_GET[ 'post_id' ] ) ? $_GET[ 'post_id' ] : false;

if ( $post_id && is_numeric( $post_id ) ) {
	$driver_object = $dtiver->get_driver_by_id( $_GET[ 'post_id' ] );
	$main          = get_field_value( $driver_object, 'main' );
	$meta          = get_field_value( $driver_object, 'meta' );
	
	if ( is_array( $driver_object ) && sizeof( $driver_object ) > 0 ) {
		$disabled_tabs  = '';
		$status_publish = get_field_value( $main, 'status_post' );
	} else {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
	
}
$access         = true;
$full_only_view = false;


if ( $status_publish === 'draft' ) {
	$full_only_view = false;
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

                        <div class="col-12 js-logs-content <?php echo $logshowcontent; ?>">

                            <ul class="nav nav-pills gap-2 mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $helper->change_active_tab( 'pills-customer-tab' ); ?> "
                                            id="pills-customer-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-customer" type="button" role="tab"
                                            aria-controls="pills-customer" aria-selected="true">Drivers
                                    </button>
                                </li>
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs; ?> <?php echo $helper->change_active_tab( 'pills-load-tab' ); ?> "
                                            id="pills-load-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-load" type="button" role="tab"
                                            aria-controls="pills-load"
                                            aria-selected="false">Inforamtion
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

                            </ul>

                            <div class="tab-content" id="pills-tabContent">

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-customer-tab', 'show' ); ?>"
                                     id="pills-customer" role="tabpanel"
                                     aria-labelledby="pills-customer-tab">

                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-load-tab', 'show' ); ?>"
                                     id="pills-load" role="tabpanel" aria-labelledby="pills-load-tab">

                                </div>


                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-documents-tab', 'show' ); ?>"
                                     id="pills-documents" role="tabpanel"
                                     aria-labelledby="pills-documents-tab">


                                </div>

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
//								echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'logs', array(
//									'post_id' => $post_id,
//									'user_id' => get_current_user_id(),
//								) ) );
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

get_footer();
