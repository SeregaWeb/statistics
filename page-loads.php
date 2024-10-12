<?php
/**
 * Template Name: Page loads
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();

$args = array(
	'status_post' => 'publish',
);

$dispatcher_filter = get_field_value($_GET, 'dispatcher');
$my_search = get_field_value($_GET, 'my_search');
$year = get_field_value($_GET, 'fyear');
$month = get_field_value($_GET, 'fmonth');
$load_status = get_field_value($_GET, 'load_status');
$source = get_field_value($_GET, 'source');

if ($dispatcher_filter) {
    $args['dispatcher'] = $dispatcher_filter;
}

if ($my_search) {
	$args['my_search'] = $my_search;
}

if ($year) {
	$args['year'] = $year;
}

if ($month) {
	$args['month'] = $month;
}

if ($source) {
	$args['source'] = $source;
}

if ($load_status) {
	$args['load_status'] = $load_status;
}


$items = $reports->get_table_items($args);

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12">

                        <?php
						echo esc_html( get_template_part( 'src/template-parts/report/report', 'filter' ) );
						?>
						
						
						<?php
						echo esc_html( get_template_part( 'src/template-parts/report/report', 'table', $items ) );
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
