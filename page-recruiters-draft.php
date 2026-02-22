<?php
/**
 * Template Name: Page recruiters draft
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$Drivers  = new TMSDrivers();
$TMSUsers = new TMSUsers();
$helper   = new TMSReportsHelper();

// FTL switcher: show only for users with FTL access.
$flt_user_access = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin        = current_user_can( 'administrator' );
$show_flt_tabs   = (bool) $flt_user_access || $is_admin;

$type   = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
$is_flt = ( $type === 'flt' );

$args = array(
	'status_post' => 'draft',
);
// Recruiters see only their drafts; administrators see all drafts.
if ( ! current_user_can( 'administrator' ) ) {
	$args['recruiter'] = get_current_user_id();
}

$args                = $Drivers->set_filter_params( $args );
$items               = $Drivers->get_table_items( $args, $is_flt );

$items['is_draft'] = true;
if ( $is_flt ) {
	$items['flt'] = true;
}


$access = $TMSUsers->check_user_role_access( [ 'administrator', 'recruiter', 'recruiter-tl', 'hr_manager' ], true );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
						<?php if ( ! $access ) :
							echo $helper->message_top( 'error', 'Access only Administrator, recruiters and recruiters team leader have access to this page.' );
						else:
							?>
                            <h4 class="mb-3">Draft drivers</h4>
							<?php
							if ( $show_flt_tabs ) {
								echo esc_html( get_template_part( TEMPLATE_PATH . 'common/flt', 'tabs', array( 'show_flt_tabs' => $show_flt_tabs, 'is_flt' => $is_flt ) ) );
							}
							if ( $is_flt ) {
								echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/driver', 'table-ftl', $items ) );
							} else {
								echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/driver', 'table', $items ) );
							}
						endif; ?>
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

get_footer();
