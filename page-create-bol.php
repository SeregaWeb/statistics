<?php
/**
 * Template Name: Page create bol
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

global $global_options;
$account = get_field_value( $global_options, 'link_to_account' );


$TMSReports = new TMSReports();

?>
    <div class="container-fluid generate-bol">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12" style="max-width: 1200px; margin: 0 auto;">
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'files/create', 'bol' ) );
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
