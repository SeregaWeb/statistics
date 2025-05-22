<?php
/**
 * Template Name: Page loads accounting factoring
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$statistics = new TMSStatistics();
$TMSUsers   = new TMSUsers();
$TMSBroker  = new TMSReportsCompany();

$current_year  = date( 'Y' ); // Returns the current year
$current_month = date( 'm' ); // Returns the current month

$year_param  = get_field_value( $_GET, 'year_param' );
$mount_param = get_field_value( $_GET, 'mount_param' );
$office      = get_field_value( $_GET, 'office' );

if ( ! $year_param ) {
	$year_param = $current_year;
}
if ( ! $mount_param ) {
	$mount_param = $current_month;
}

$offices = $statistics->get_offices_from_acf();

if ( ! $office ) {
	$office = 'all';
}

$show_filter_by_office = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'tracking-tl',
	'administrator',
	'recruiter-tl',
	'moderator'
), true );

if ( ! $show_filter_by_office ) {
	$office = get_field( 'work_location', "user_" . get_current_user_id() );
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
								<?php if ( $show_filter_by_office ): ?>
                                    <select class="form-select w-auto" name="office"
                                            aria-label=".form-select-sm example">
                                        <option value="all">Office</option>
										<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
											<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                                                <option value="<?php echo $key; ?>" <?php echo $office === $key
													? 'selected' : '' ?> >
													<?php echo $val; ?>
                                                </option>
											<?php endforeach; ?>
										<?php endif; ?>
                                    </select>
								<?php endif; ?>

                                <select class="form-select w-auto" required name="year_param"
                                        aria-label=".form-select-sm example">
                                    <option value="">Year</option>
                                    <option value="all" <?php echo $year_param === 'all' ? 'selected' : ''; ?>>All
                                        time
                                    </option>
									<?php
									
									for ( $year = 2023; $year <= $current_year; $year ++ ) {
										$select = is_numeric( $year_param ) && + $year_param === + $year ? 'selected'
											: '';
										echo '<option ' . $select . ' value="' . $year . '">' . $year . '</option>';
									}
									?>
                                </select>
								
								<?php
								$months = $statistics->get_months();
								?>
                                <select class="form-select w-auto" name="mount_param"
                                        aria-label=".form-select-sm example">
                                    <option value="">Month</option>
                                    <option value="all" <?php echo $mount_param === 'all' ? 'selected' : ''; ?>>All
                                        time
                                    </option>
									<?php
									foreach ( $months as $num => $name ) {
										
										$select = is_numeric( $mount_param ) && + $mount_param === + $num ? 'selected'
											: '';
										
										echo '<option ' . $select . ' value="' . $num . '">' . $name . '</option>';
									}
									?>
                                </select>

                                <button class="btn btn-primary" type="submit">Select</button>
                            </div>

                            <div class="d-flex flex-column gap-1">
								<?php
								$data               = $statistics->get_monthly_fuctoring_stats( $year_param, $mount_param, $office );
								$second_driver_rate = floatval( $data[ 'total_second_driver_rate' ] );
								
								$general_profit      = floatval( $data[ 'total_booked_rate' ] ) - floatval( $data[ 'total_driver_rate' ] ) - $second_driver_rate;
								$general_true_profit = floatval( $data[ 'total_true_profit' ] ) - $second_driver_rate;
								
								$paid_to_factoring = $general_profit - $general_true_profit;
								$after_factoring   = floatval( $data[ 'total_booked_rate' ] ) - $paid_to_factoring;
								
								$data[ 'total_driver_rate' ] = floatval( $data[ 'total_driver_rate' ] ) + $second_driver_rate;
								
								?>

                                <div class="table-values">
                                    <div class="table-values-col">
                                        <p>Gross</p>
										<?php echo esc_html( '$' . $statistics->format_currency( $data[ 'total_booked_rate' ] ) ); ?>
                                    </div>

                                    <div class="table-values-col">
                                        <p>Driver rate</p>
										<?php echo esc_html( '$' . $statistics->format_currency( $data[ 'total_driver_rate' ] ) ); ?>
                                    </div>

                                    <div class="table-values-col">
                                        <p>After Factoring</p>
										<?php echo esc_html( '$' . $statistics->format_currency( $after_factoring ) ); ?>
                                    </div>


                                    <div class="table-values-col">
                                        <p>General profit</p>
										<?php echo esc_html( '$' . $statistics->format_currency( $general_profit ) ); ?>
                                    </div>

                                    <div class="table-values-col">
                                        <p>True profit</p>
										<?php echo esc_html( '$' . $statistics->format_currency( $general_true_profit ) ); ?>
                                    </div>

                                    <div></div>
                                    <div></div>
                                    <div class="table-values-col">
                                        <p>Paid to Factoring</p>
										<?php echo esc_html( '$' . $statistics->format_currency( $paid_to_factoring ) ); ?>
                                    </div>

                                </div>

                            </div>
                        </form>

                    </div>

                    <div class="col-12 mt-3 mb-3">
                        <h2>Debtor Summary</h2>
                    </div>

                    <div class="col-12">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Profit</th>
                                <th>Count</th>
                            </tr>
                            </thead>
                            <tbody>
							
							
							<?php
							$top5 = $statistics->get_top_10_customers( $year_param, $mount_param, $office );
							
							if ( is_array( $top5 ) ) {
								foreach ( $top5 as $item ) {
									$template_broker = $TMSBroker->get_broker_and_link_by_id( $item[ 'customer_id' ] );
									$total_profit    = esc_html( '$' . $statistics->format_currency( $item[ 'total_profit' ] ) );
									?>

                                    <tr>
                                        <td><?php echo $template_broker; ?></td>
                                        <td><?php echo $total_profit; ?></td>
                                        <td><?php echo $item[ 'post_count' ]; ?></td>
                                    </tr>
									
									<?php
								}
							}
							?>
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

get_footer();
