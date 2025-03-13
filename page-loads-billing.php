<?php
/**
 * Template Name: Page loads billing
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();

$args = array(
	'status_post'              => 'publish',
	'exclude_factoring_status' => array( 'paid' ),
	'per_page_loads'           => 100,
	'exclude_status'           => array( 'cancelled' ),
);

$args  = $reports->set_filter_params( $args );
$items = $reports->get_table_items_billing( $args );

$post_tp              = 'accounting';
$items[ 'page_type' ] = $post_tp;
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12">
						
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter', array( 'post_type' => $post_tp ) ) );
						?>
						
						
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-billing', $items ) );
						?>

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
