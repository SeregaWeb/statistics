<?php
/**
 * Template Name: Page loads accounting factoring
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$statistics = new TMSStatistics();

$current_year = date('Y'); // Returns the current year
$current_month = date('m'); // Returns the current month

$year_param = get_field_value($_GET, 'year_param');
$mount_param = get_field_value($_GET, 'mount_param');

if (!$year_param) {
    $year_param = $current_year;
}
if (!$mount_param) {
    $mount_param = $current_month;
}

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mb-3 mt-3">
                        <h2>Factoring</h2>
                    </div>
                    <div class="col-12">

                        <form action="" class="w-100">

                            <div class="d-flex gap-1">
                                <select class="form-select w-auto" required name="year_param" aria-label=".form-select-sm example">
                                    <option value="">Year</option>
			                        <?php
			                        
			                        for ( $year = 2023; $year <= $current_year; $year++ ) {
				                        $select = is_numeric($year_param) &&  +$year_param === +$year ? 'selected' : '' ;
				                        echo '<option '.$select.' value="' . $year . '">' . $year . '</option>';
			                        }
			                        ?>
                                </select>
	                            
	                            <?php
	                            $months = array(
		                            1  => 'January',
		                            2  => 'February',
		                            3  => 'March',
		                            4  => 'April',
		                            5  => 'May',
		                            6  => 'June',
		                            7  => 'July',
		                            8  => 'August',
		                            9  => 'September',
		                            10 => 'October',
		                            11 => 'November',
		                            12 => 'December',
	                            );
	                            ?>
                                <select class="form-select w-auto" name="mount_param" aria-label=".form-select-sm example">
                                    <option value="">Month</option>
		                            <?php
		                            foreach ( $months as $num => $name ) {
			                            
			                            $select = is_numeric($mount_param) && +$mount_param === +$num ? 'selected' : '' ;
			                            
			                            echo '<option '.$select.' value="' . $num . '">' . $name . '</option>';
		                            }
		                            ?>
                                </select>
                                
                                <button class="btn btn-primary" type="submit">Select</button>
                            </div>
                            
                            <div class="d-flex gap-1">
                                <?php
                                var_dump($statistics->get_monthly_fuctoring_stats($year_param, $mount_param ));
                                ?>
                            </div>
                        </form>

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
