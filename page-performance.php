<?php
/**
 * Template Name: Page Performance
 *
 * @package WP-rock
 * @since 4.4.0
 */

$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();

$dispatchers    = $statistics->get_dispatchers();
$dispatchers_tl = $statistics->get_dispatchers_tl();

get_header();

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-4 pb-6">
                        <h1>Performance</h1>

                        <table class='table table-performance'>
                            <thead>
                            <tr>
                                <th>Dispatcher</th>
                                <th colspan="4" class="week-divider">Mon 12/23/2024</th>
                                <th colspan="4" class="week-divider">Tue 12/24/2024</th>
                                <th colspan="4" class="week-divider">Wed 12/25/2024</th>
                                <th colspan="4" class="week-divider">Thu 12/26/2024</th>
                                <th colspan="4" class="week-divider">Fri 12/27/2024</th>
                                <th colspan="4" class="week-divider">Sat 12/28/2024</th>
                                <th colspan="4" class="week-divider">Sun 12/29/2024</th>
                                <th colspan="4">Total</th>
                                <th>Bonus</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                                <th>Calls</th>
                                <th>Loads</th>
                                <th>Profit</th>
                                <th>Perf</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Victor Miller</td>
                                <td>16</td>
                                <td>1</td>
                                <td>$250.00</td>
                                <td>61%</td>
                                <td>12</td>
                                <td>2</td>
                                <td>$400.00</td>
                                <td>142%</td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td>0%</td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td>0%</td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td>0%</td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td>0%</td>
                                <td>28</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>24</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>$200.00</td>
                            </tr>
                            <!-- Add more rows here for other dispatchers -->
                            <tr class="total">
                                <td>Total</td>
                                <td>380</td>
                                <td>13</td>
                                <td>$1,740.00</td>
                                <td></td>
                                <td>349</td>
                                <td>4</td>
                                <td>$600.00</td>
                                <td></td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td></td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td></td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td></td>
                                <td>0</td>
                                <td>0</td>
                                <td>$0.00</td>
                                <td></td>
                                <td>729</td>
                                <td>17</td>
                                <td>$2,340.00</td>
                                <td></td>
                                <td>25</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>$200.00</td>
                            </tr>
                            </tbody>
                        </table>
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
