<?php
/**
 * Template Name: Page loads accounting unapplied
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();

$args = array(
	'status_post' => 'publish',
);

$args = $reports->set_filter_unapplied($args);
$items = $reports->get_table_items_unapplied($args);

$post_tp = 'accounting';
$items['page_type'] = $post_tp;
$items['ar_problem'] = true;
?>
	<div class="container-fluid">
		<div class="row">
			<div class="container">
				<div class="row">
					<div class="col-12 mb-3 mt-3">
                        <ul class="nav nav-pills" id="pills-tab" role="tablist">
                            <li class="nav-item w-25" role="presentation">
                                <button class="nav-link w-100 active" id="pills-info-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-info" type="button" role="tab" aria-controls="pills-info"
                                        aria-selected="true">Direct Invoicing & Unapplied Payments
                                </button>
                            </li>
                            <li class="nav-item w-25" role="presentation">
                                <button class="nav-link w-100" id="pills-update-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-update" type="button" role="tab"
                                        aria-controls="pills-update" aria-selected="false">Debt
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-info" role="tabpanel"
                                 aria-labelledby="pills-info-tab">
                                <?php
                                echo esc_html( get_template_part( 'src/template-parts/report/report', 'filter-unapplied' ) );
                                ?>
                                
                                <?php
                                echo esc_html( get_template_part( 'src/template-parts/report/report', 'table-unapplied', $items ) );
                                ?>
                            </div>

                            <div class="tab-pane fade" id="pills-update" role="tabpanel" aria-labelledby="pills-update-tab">
                            
                            </div>
                        </div>
					
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
