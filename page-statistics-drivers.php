<?php
/**
 * Template Name: Page statistics drivers
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


?>
    <div class="container-fluid pt-3">
        <div class="row">
			<?php get_template_part( TEMPLATE_PATH . 'statistics/drivers' ); ?>
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
