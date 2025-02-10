<?php
/**
 * Template Name: Page brokers
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


$brokers = new TMSReportsCompany();


$brokers_items = $brokers->get_table_records_2();
$results       = get_field_value( $brokers_items, 'results' );
$total_pages   = get_field_value( $brokers_items, 'total_pages' );
$current_pages = get_field_value( $brokers_items, 'current_page' );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mt-3 mb-3">
                        <h2>Brokers</h2>
						
						<?php
						echo esc_html( get_template_part( 'src/template-parts/report/report', 'filter-company' ) );
						
						echo esc_html( get_template_part( 'src/template-parts/report/report-table', 'company', array(
							'results'      => $results,
							'total_pages'  => $total_pages,
							'current_page' => $current_pages,
						) ) );
						
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
