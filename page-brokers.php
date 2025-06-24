<?php
/**
 * Template Name: Page brokers
 *
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Initialize brokers class with error handling
$brokers = new TMSReportsCompany();

// Get brokers data with validation
$brokers_items = $brokers->get_table_records_2();

// Extract and validate data with proper fallbacks
$results       = array();
$total_pages   = 0;
$current_pages = 1;

if ( is_array( $brokers_items ) ) {
	$results       = isset( $brokers_items['results'] ) ? $brokers_items['results'] : array();
	$total_pages   = isset( $brokers_items['total_pages'] ) ? (int) $brokers_items['total_pages'] : 0;
	$current_pages = isset( $brokers_items['current_page'] ) ? (int) $brokers_items['current_page'] : 1;
}

// Ensure current_page is at least 1
$current_pages = max( 1, $current_pages );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mt-3 mb-3">
                        <h2>Brokers</h2>
						
						<?php
						// Load filter template
						get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-company' );
						
						// Load table template with validated data
						get_template_part( TEMPLATE_PATH . 'tables/report-table', 'company', array(
							'results'      => $results,
							'total_pages'  => $total_pages,
							'current_page' => $current_pages,
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
