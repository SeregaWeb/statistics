<?php
/**
 * Template Name: Page loads accounting ar problem
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();

$args = array(
	'status_post' => 'publish',
    'ar_problem' => true,
    'sort_by' => 'load_problem',
    'sort_order' => 'asc',
);

$args = $reports->set_filter_params_arr($args);
$items = $reports->get_table_items_ar($args);

$post_tp = 'accounting';
$items['page_type'] = $post_tp;
$items['ar_problem'] = true;
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mb-3 mt-3">
                        <h2>A/R aging</h2>
                    </div>
                    <div class="col-12">
	                    <?php
	                    echo esc_html( get_template_part( 'src/template-parts/report/report', 'filter-ar' ) );
	                    ?>
	                    
	                    <?php
						echo esc_html( get_template_part( 'src/template-parts/report/report', 'table-ar', $items ) );
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

echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add' ) );

get_footer();
