<?php
/**
 * Template Name: Page Shippers
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$brokers       = new TMSReportsShipper();
$brokers_items = $brokers->get_table_records();

$results      = get_field_value( $brokers_items, 'results' );
$total_pages  = get_field_value( $brokers_items, 'total_pages' );
$current_page = get_field_value( $brokers_items, 'current_page' );
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mt-3 mb-3">
                        <h2>Shippers</h2>
						
						<?php
						get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-shipper' );
						
						get_template_part( TEMPLATE_PATH . 'tables/report-table', 'shipper', array(
							'results'      => $results,
							'total_pages'  => $total_pages,
							'current_page' => $current_page,
						) );
						?>

                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
