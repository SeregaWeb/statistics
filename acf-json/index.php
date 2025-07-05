<?php
/**
 * Index template
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();
?>


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
?>


<?php
get_footer();