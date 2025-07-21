<?php
/**
 * Template Name: Page loads draft flt
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReportsFlt();
$args    = array(
	'status_post' => 'draft',
	'user_id'     => get_current_user_id(),
);

$access = true;

$args                  = $reports->set_filter_params( $args );
$items                 = $reports->get_table_items( $args );
$post_tp               = 'dispatcher';
$items[ 'page_type' ]  = $post_tp;
$items[ 'is_draft' ]   = true;
$items[ 'hide_total' ] = true;
$items[ 'flt' ]        = true;
$access_flt            = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin              = current_user_can( 'administrator' );

if ( ! $access_flt && ! $is_admin ) {
	$access = false;
}

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
					<?php if ( $access ): ?>

                        <div class="col-12 pt-3 pb-3">
                            <h2><?php echo get_the_title(); ?></h2>
                            <p><?php echo get_the_excerpt(); ?></p>
                        </div>
                        <div class="col-12">
							
							<?php
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table', $items ) );
							?>

                        </div>
					
					<?php else: ?>
                        <div class="col-12 col-lg-9 mt-3">
							<?php
							echo $reports->message_top( 'danger', $reports->messages_prepare( 'not-access' ) );
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
