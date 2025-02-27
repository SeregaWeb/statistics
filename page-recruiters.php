<?php
/**
 * Template Name: Page recruiters
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-15">
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'page', 'in-development' ) );
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
