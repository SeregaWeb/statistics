<?php
/**
 * Template Name: Page loads draft
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();
$args = array(
	'status_post' => 'draft',
    'user_id' => get_current_user_id(),
);


$items = $reports->get_table_items($args);

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
                        <h2><?php echo get_the_title(); ?></h2>
                        <p><?php echo get_the_excerpt(); ?></p>
                        
                    </div>
                    <div class="col-12">

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