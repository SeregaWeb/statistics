<?php
/**
 * Template Name: Page add reports
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();
$items   = $reports->get_table_items();

?>
	<div class="container-fluid">
		<div class="row">
			<div class="container">
				<div class="row">
					<div class="col-12">
					
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
