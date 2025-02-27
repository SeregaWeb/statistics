<?php
/**
 * Template Name: Page loads accounting ar problem
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports = new TMSReports();

$args = array(
	'status_post' => 'publish',
	'ar_problem'  => true,
	'sort_by'     => 'load_problem',
	'sort_order'  => 'asc',
);

$args  = $reports->set_filter_params_arr( $args );
$items = $reports->get_table_items_ar( $args );

$get_statistics = $reports->get_problem_statistics_with_sums();

$post_tp               = 'accounting';
$items[ 'page_type' ]  = $post_tp;
$items[ 'ar_problem' ] = true;
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mb-3 mt-3">
                        <h2>A/R aging</h2>
                    </div>
                    <div class="col-12">
						
						<?php
						
						$class1 = ( $get_statistics[ 'sum_range_31_61' ] <= 1000 ) ? 'green'
							: ( ( $get_statistics[ 'sum_range_31_61' ] <= 10000 ) ? 'orange' : 'red' );
						$class2 = ( $get_statistics[ 'sum_range_62_90' ] <= 1000 ) ? 'green'
							: ( ( $get_statistics[ 'sum_range_62_90' ] <= 10000 ) ? 'orange' : 'red' );
						$class3 = ( $get_statistics[ 'sum_range_91_121' ] <= 1000 ) ? 'green'
							: ( ( $get_statistics[ 'sum_range_91_121' ] <= 10000 ) ? 'orange' : 'red' );
						$class4 = ( $get_statistics[ 'sum_range_121_plus' ] <= 1000 ) ? 'green'
							: ( ( $get_statistics[ 'sum_range_121_plus' ] <= 10000 ) ? 'orange' : 'red' );
						
						?>

                        <div class="statistic-ar">
                            <div class="statistic-ar__card <?php echo $class1; ?>">
                                <span class="statistic-ar__count">$<?php echo is_null( $get_statistics[ 'sum_range_31_61' ] )
		                                ? '0' : $get_statistics[ 'sum_range_31_61' ]; ?></span>
                                <span class="statistic-ar__days">31-61 Days</span>
                            </div>
                            <div class="statistic-ar__card <?php echo $class2; ?>">
                                <span class="statistic-ar__count">$<?php echo is_null( $get_statistics[ 'sum_range_62_90' ] )
		                                ? '0' : $get_statistics[ 'sum_range_62_90' ]; ?></span>
                                <span class="statistic-ar__days">62-90 Days</span>
                            </div>
                            <div class="statistic-ar__card <?php echo $class3; ?>">
                                <span class="statistic-ar__count">$<?php echo is_null( $get_statistics[ 'sum_range_91_121' ] )
		                                ? '0' : $get_statistics[ 'sum_range_91_121' ]; ?></span>
                                <span class="statistic-ar__days">91-121 Days</span>
                            </div>
                            <div class="statistic-ar__card <?php echo $class4; ?>">
                                <span class="statistic-ar__count">$<?php echo is_null( $get_statistics[ 'sum_range_121_plus' ] )
		                                ? '0' : $get_statistics[ 'sum_range_121_plus' ]; ?></span>
                                <span class="statistic-ar__days">121+ Days</span>
                            </div>
                        </div>
						
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-ar' ) );
						?>
						
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-ar', $items ) );
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
