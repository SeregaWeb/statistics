<?php
/**
 * Template Name: Page Performance
 *
 * @package WP-rock
 * @since 4.4.0
 */

$statistics  = new TMSStatistics();
$helper      = new TMSReportsHelper();
$TMSUsers    = new TMSUsers();

// Проверяем доступ к FLT
$flt_user_access = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin = current_user_can( 'administrator' );
$show_flt_tabs = $flt_user_access || $is_admin;

// Определяем тип данных для загрузки
$type = get_field_value( $_GET, 'type' );
$is_flt = $type === 'flt';

// Создаем класс с нужным режимом
$performance = new TMSReportsPerformance( $is_flt );

$dispatchers    = $statistics->get_dispatchers( null, $is_flt );
$dispatchers_tl = $statistics->get_dispatchers_tl( null, $is_flt );

$exclude_dispatchers = $helper->get_empty_dispatcher();

$exclude_users = get_field( 'exclude_users', get_the_ID() ) ?? [];

if ( ! empty( $exclude_users ) ) {
	$exclude_dispatchers = array_merge( $exclude_users, $exclude_dispatchers );
}

$date               = get_field_value( $_GET, 'date' );
$merged_dispatchers = $helper->merge_unique_dispatchers( $dispatchers, $dispatchers_tl, $exclude_dispatchers );
$week_dates         = $helper->get_week_dates_from_monday( $date );

if ( is_null( $date ) ) {
	$date = date( 'Y-m-d', strtotime( $week_dates[ 0 ] ) );
}

$office  = get_field_value( $_GET, 'office' );
$offices = $helper->get_offices_from_acf();

if ( ! $office ) {
	$office = get_field( 'work_location', "user_" . get_current_user_id() );
}

//'administrator',
$edit_access           = $TMSUsers->check_user_role_access( array( 'administrator', 'moderator' ), true );
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

get_header();

// Передаем информацию о FLT в JavaScript
wp_localize_script( 'frontend_js', 'performanceData', array(
	'is_flt' => $is_flt,
	'ajax_url' => admin_url( 'admin-ajax.php' )
) );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-4 pb-6">
						
						<?php if ( $office ): ?>

                            <h1 class="mb-4">Performance</h1>
                            
                            <?php if ( $show_flt_tabs ): ?>
                                <?php
                                echo esc_html( get_template_part( TEMPLATE_PATH . 'common/flt', 'tabs', array( 'show_flt_tabs' => $show_flt_tabs, 'is_flt' => $is_flt ) ) );
                                ?>
                            <?php endif; ?>

                            <div class="d-flex justify-content-start mb-2 w-100">
                                <form class="d-flex gap-1">
                                    <?php if ( $is_flt ): ?>
                                        <input type="hidden" name="type" value="flt">
                                    <?php endif; ?>
                                    <select class="form-select" name="date" id="date">
										<?php echo $helper->generateWeeks( $date ); ?>
                                    </select>
									
									<?php if ( $show_filter_by_office ): ?>
                                        <select class="form-select w-auto" name="office"
                                                aria-label=".form-select-sm example">
                                            <option value="all">Company total</option>
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

                                    <button class="btn btn-primary" type="submit">Select</button>
                                </form>
                            </div>
							<?php if ( is_array( $week_dates ) ): ?>

                                <table class='table table-performance'>
                                    <thead>
                                    <tr>
                                        <th>Dispatcher</th>
                                        <th colspan="4" class="week-divider">Mon <?php echo $week_dates[ 0 ]; ?></th>
                                        <th colspan="4" class="week-divider">Tue <?php echo $week_dates[ 1 ]; ?></th>
                                        <th colspan="4" class="week-divider">Wed <?php echo $week_dates[ 2 ]; ?></th>
                                        <th colspan="4" class="week-divider">Thu <?php echo $week_dates[ 3 ]; ?></th>
                                        <th colspan="4" class="week-divider">Fri <?php echo $week_dates[ 4 ]; ?></th>
                                        <th colspan="4" class="week-divider">Sat <?php echo $week_dates[ 5 ]; ?></th>
                                        <th colspan="4" class="week-divider">Sun <?php echo $week_dates[ 6 ]; ?></th>
                                        <th colspan="3">Total</th>
                                        <th>Bonus</th>
                                        <th></th>
                                    </tr>
                                    <tr class="need-separate">
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
										<?php if ( $edit_access ): ?>
                                            <th></th>
										<?php endif; ?>
                                    </tr>
                                    </thead>
                                    <tbody>
									<?php if ( $merged_dispatchers ):
										
										$all_total = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
											'bonus'  => 0,
										);
										
										$all_total_monday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										$all_total_tuesday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										$all_total_wednesday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										$all_total_thursday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										$all_total_friday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										$all_total_saturday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										$all_total_sunday = array(
											'calls'  => 0,
											'profit' => 0,
											'loads'  => 0,
										);
										
										foreach ( $merged_dispatchers as $dispatcher ):
											
											if ( $office !== 'all' && $dispatcher[ 'office' ] !== $office ) {
												continue;
											}
											
											$dbdate = $performance->get_or_create_performance_record( $dispatcher[ 'id' ], $date );
											
											$report = $performance->get_dispatcher_weekly_report( $date, $dispatcher[ 'id' ] );
											
											$print_date = array(
												'user_id'         => is_numeric( $dbdate ) ? $dispatcher[ 'id' ]
													: $dbdate[ 'user_id' ],
												'monday_calls'    => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'monday_calls' ] ),
												'monday_date'     => isset( $report[ 0 ] ) ? $report[ 0 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'tuesday_calls'   => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'tuesday_calls' ] ),
												'tuesday_date'    => isset( $report[ 1 ] ) ? $report[ 1 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'wednesday_calls' => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'wednesday_calls' ] ),
												'wednesday_date'  => isset( $report[ 2 ] ) ? $report[ 2 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'thursday_calls'  => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'thursday_calls' ] ),
												'thursday_date'   => isset( $report[ 3 ] ) ? $report[ 3 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'friday_calls'    => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'friday_calls' ] ),
												'friday_date'     => isset( $report[ 4 ] ) ? $report[ 4 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'saturday_calls'  => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'saturday_calls' ] ),
												'saturday_date'   => isset( $report[ 5 ] ) ? $report[ 5 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'sunday_calls'    => is_numeric( $dbdate ) ? 0
													: intval( $dbdate[ 'sunday_calls' ] ),
												'sunday_date'     => isset( $report[ 6 ] ) ? $report[ 6 ]
													: array( 'post_count' => 0, 'profit' => 0 ),
												'bonus'           => is_numeric( $dbdate ) ? 0 : $dbdate[ 'bonus' ],
											);
											
											$print_date[ 'monday_date' ][ 'performance' ]    = $performance->calculate_performance( $print_date[ 'monday_calls' ], $print_date[ 'monday_date' ][ 'post_count' ], $print_date[ 'monday_date' ][ 'profit' ] );
											$print_date[ 'tuesday_date' ][ 'performance' ]   = $performance->calculate_performance( $print_date[ 'tuesday_calls' ], $print_date[ 'tuesday_date' ][ 'post_count' ], $print_date[ 'tuesday_date' ][ 'profit' ] );
											$print_date[ 'wednesday_date' ][ 'performance' ] = $performance->calculate_performance( $print_date[ 'wednesday_calls' ], $print_date[ 'wednesday_date' ][ 'post_count' ], $print_date[ 'wednesday_date' ][ 'profit' ] );
											$print_date[ 'thursday_date' ][ 'performance' ]  = $performance->calculate_performance( $print_date[ 'thursday_calls' ], $print_date[ 'thursday_date' ][ 'post_count' ], $print_date[ 'thursday_date' ][ 'profit' ] );
											$print_date[ 'friday_date' ][ 'performance' ]    = $performance->calculate_performance( $print_date[ 'friday_calls' ], $print_date[ 'friday_date' ][ 'post_count' ], $print_date[ 'friday_date' ][ 'profit' ] );
											$print_date[ 'saturday_date' ][ 'performance' ]  = $performance->calculate_performance( $print_date[ 'saturday_calls' ], $print_date[ 'saturday_date' ][ 'post_count' ], $print_date[ 'saturday_date' ][ 'profit' ] );
											$print_date[ 'sunday_date' ][ 'performance' ]    = $performance->calculate_performance( $print_date[ 'sunday_calls' ], $print_date[ 'sunday_date' ][ 'post_count' ], $print_date[ 'sunday_date' ][ 'profit' ] );
											
											$all_total_monday[ 'calls' ]  += $print_date[ 'monday_calls' ];
											$all_total_monday[ 'profit' ] += $print_date[ 'monday_date' ][ 'profit' ];
											$all_total_monday[ 'loads' ]  += $print_date[ 'monday_date' ][ 'post_count' ];
											
											$all_total_tuesday[ 'calls' ]  += $print_date[ 'tuesday_calls' ];
											$all_total_tuesday[ 'profit' ] += $print_date[ 'tuesday_date' ][ 'profit' ];
											$all_total_tuesday[ 'loads' ]  += $print_date[ 'tuesday_date' ][ 'post_count' ];
											
											$all_total_wednesday[ 'calls' ]  += $print_date[ 'wednesday_calls' ];
											$all_total_wednesday[ 'profit' ] += $print_date[ 'wednesday_date' ][ 'profit' ];
											$all_total_wednesday[ 'loads' ]  += $print_date[ 'wednesday_date' ][ 'post_count' ];
											
											$all_total_thursday[ 'calls' ]  += $print_date[ 'thursday_calls' ];
											$all_total_thursday[ 'profit' ] += $print_date[ 'thursday_date' ][ 'profit' ];
											$all_total_thursday[ 'loads' ]  += $print_date[ 'thursday_date' ][ 'post_count' ];
											
											$all_total_friday[ 'calls' ]  += $print_date[ 'friday_calls' ];
											$all_total_friday[ 'profit' ] += $print_date[ 'friday_date' ][ 'profit' ];
											$all_total_friday[ 'loads' ]  += $print_date[ 'friday_date' ][ 'post_count' ];
											
											$all_total_saturday[ 'calls' ]  += $print_date[ 'saturday_calls' ];
											$all_total_saturday[ 'profit' ] += $print_date[ 'saturday_date' ][ 'profit' ];
											$all_total_saturday[ 'loads' ]  += $print_date[ 'saturday_date' ][ 'post_count' ];
											
											$all_total_sunday[ 'calls' ]  += $print_date[ 'sunday_calls' ];
											$all_total_sunday[ 'profit' ] += $print_date[ 'sunday_date' ][ 'profit' ];
											$all_total_sunday[ 'loads' ]  += $print_date[ 'sunday_date' ][ 'post_count' ];
											
											$total_calls  = 0;
											$total_profit = 0.00;
											$total_count  = 0;
											
											foreach (
												[
													'monday',
													'tuesday',
													'wednesday',
													'thursday',
													'friday',
													'saturday',
													'sunday'
												] as $day
											) {
												$total_calls  += isset( $print_date[ "{$day}_calls" ] )
													? $print_date[ "{$day}_calls" ] : 0;
												$total_profit += isset( $print_date[ "{$day}_date" ][ 'profit' ] )
													? $print_date[ "{$day}_date" ][ 'profit' ] : 0;
												$total_count  += isset( $print_date[ "{$day}_date" ][ 'post_count' ] )
													? $print_date[ "{$day}_date" ][ 'post_count' ] : 0;
											}
											
											$all_total[ 'calls' ]  += $total_calls;
											$all_total[ 'profit' ] += $total_profit;
											$all_total[ 'loads' ]  += $total_count;
											$all_total[ 'bonus' ]  += $print_date[ 'bonus' ];
											
											?>
                                            <tr class="js-disable-container js-fake-form">
                                                <input type="hidden" name="user_id"
                                                       value="<?php echo $print_date[ 'user_id' ]; ?>">
                                                <input type="hidden" name="date" value="<?php echo $date; ?>">

                                                <td><?php echo $dispatcher[ 'fullname' ]; ?></td>
												
												<?php
												$days_keys = [
													'monday_date',
													'tuesday_date',
													'wednesday_date',
													'thursday_date',
													'friday_date'
												];
												$colors    = [];
												
												foreach ( $days_keys as $day_key ) {
													if ( isset( $print_date[ $day_key ][ 'performance' ] ) && $print_date[ $day_key ][ 'performance' ] !== '' ) {
														$performance_val = (float) $print_date[ $day_key ][ 'performance' ];
														if ( $performance_val == 0 ) {
															$colors[ $day_key ] = '#ffffff';
														} else {
															$colors[ $day_key ] = $performance_val >= 100 ? '#d9facb'
																: '#f4cccc';
														}
													} else {
														$colors[ $day_key ] = '#ffffff';
													}
												}
												
												
												// Теперь у тебя есть:
												$md_color = $colors[ 'monday_date' ];
												$tu_color = $colors[ 'tuesday_date' ];
												$wd_color = $colors[ 'wednesday_date' ];
												$th_color = $colors[ 'thursday_date' ];
												$fr_color = $colors[ 'friday_date' ];
												?>

                                                <td style="background-color: <?php echo $md_color; ?>">
													<?php if ( $edit_access ): ?>
                                                        <input value="<?php echo $print_date[ 'monday_calls' ] === 0
															? '' : $print_date[ 'monday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="monday_calls">
													<?php else:
														echo $print_date[ 'monday_calls' ];
													endif; ?>

                                                </td>
                                                <td style="background-color: <?php echo $md_color; ?>"><?php echo $print_date[ 'monday_date' ][ 'post_count' ]; ?></td>
                                                <td style="background-color: <?php echo $md_color; ?>">
                                                    $<?php echo $helper->format_currency( $print_date[ 'monday_date' ][ 'profit' ] ); ?></td>
                                                <td style="background-color: <?php echo $md_color; ?>"><?php echo $print_date[ 'monday_date' ][ 'performance' ]; ?>
                                                    %
                                                </td>

                                                <td style="background-color: <?php echo $tu_color; ?>">
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'tuesday_calls' ] === 0
															? '' : $print_date[ 'tuesday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="tuesday_calls">
													<?php else:
														echo $print_date[ 'tuesday_calls' ];
													endif; ?>
                                                </td>
                                                <td style="background-color: <?php echo $tu_color; ?>"><?php echo $print_date[ 'tuesday_date' ][ 'post_count' ]; ?></td>
                                                <td style="background-color: <?php echo $tu_color; ?>">
                                                    $<?php echo $helper->format_currency( $print_date[ 'tuesday_date' ][ 'profit' ] ); ?></td>
                                                <td style="background-color: <?php echo $tu_color; ?>"><?php echo $print_date[ 'tuesday_date' ][ 'performance' ]; ?>
                                                    %
                                                </td>

                                                <td style="background-color: <?php echo $wd_color; ?>">
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'wednesday_calls' ] === 0
															? '' : $print_date[ 'wednesday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="wednesday_calls">
													<?php else:
														echo $print_date[ 'wednesday_calls' ];
													endif; ?>
                                                </td>
                                                <td style="background-color: <?php echo $wd_color; ?>"><?php echo $print_date[ 'wednesday_date' ][ 'post_count' ]; ?></td>
                                                <td style="background-color: <?php echo $wd_color; ?>">
                                                    $<?php echo $helper->format_currency( $print_date[ 'wednesday_date' ][ 'profit' ] ); ?></td>
                                                <td style="background-color: <?php echo $wd_color; ?>"><?php echo $print_date[ 'wednesday_date' ][ 'performance' ]; ?>
                                                    %
                                                </td>


                                                <td style="background-color: <?php echo $th_color; ?>">
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'thursday_calls' ] === 0
															? '' : $print_date[ 'thursday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="thursday_calls">
													<?php else:
														echo $print_date[ 'thursday_calls' ];
													endif; ?>
                                                </td>
                                                <td style="background-color: <?php echo $th_color; ?>"><?php echo $print_date[ 'thursday_date' ][ 'post_count' ]; ?></td>
                                                <td style="background-color: <?php echo $th_color; ?>">
                                                    $<?php echo $helper->format_currency( $print_date[ 'thursday_date' ][ 'profit' ] ); ?></td>


                                                <td style="background-color: <?php echo $th_color; ?>"><?php echo $print_date[ 'thursday_date' ][ 'performance' ]; ?>
                                                    %
                                                </td>


                                                <td style="background-color: <?php echo $fr_color; ?>">
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'friday_calls' ] === 0
															? '' : $print_date[ 'friday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="friday_calls">
													<?php else:
														echo $print_date[ 'friday_calls' ];
													endif; ?>
                                                </td>
                                                <td style="background-color: <?php echo $fr_color; ?>"><?php echo $print_date[ 'friday_date' ][ 'post_count' ]; ?></td>
                                                <td style="background-color: <?php echo $fr_color; ?>">
                                                    $<?php echo $helper->format_currency( $print_date[ 'friday_date' ][ 'profit' ] ); ?></td>
                                                <td style="background-color: <?php echo $fr_color; ?>"><?php echo $print_date[ 'friday_date' ][ 'performance' ]; ?>
                                                    %
                                                </td>

                                                <td>
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'saturday_calls' ] === 0
															? '' : $print_date[ 'saturday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="saturday_calls">
													<?php else:
														echo $print_date[ 'saturday_calls' ];
													endif; ?>
                                                </td>
                                                <td><?php echo $print_date[ 'saturday_date' ][ 'post_count' ]; ?></td>
                                                <td>
                                                    $<?php echo $helper->format_currency( $print_date[ 'saturday_date' ][ 'profit' ] ); ?></td>
                                                <td><?php echo $print_date[ 'saturday_date' ][ 'performance' ]; ?>%</td>
                                                <td>
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'sunday_calls' ] === 0
															? '' : $print_date[ 'sunday_calls' ]; ?>" type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 48px;" name="sunday_calls">
													<?php else:
														echo $print_date[ 'sunday_calls' ];
													endif; ?>
                                                </td>
                                                <td><?php echo $print_date[ 'sunday_date' ][ 'post_count' ]; ?></td>
                                                <td>
                                                    $<?php echo $helper->format_currency( $print_date[ 'sunday_date' ][ 'profit' ] ); ?></td>
                                                <td><?php echo $print_date[ 'sunday_date' ][ 'performance' ]; ?>%</td>
                                                <td><?php echo $total_calls; ?></td>
                                                <td><?php echo $total_count; ?></td>
                                                <td>$<?php echo $helper->format_currency( $total_profit ); ?></td>
                                                <td>$
													<?php if ( $edit_access ): ?>

                                                        <input value="<?php echo $print_date[ 'bonus' ]; ?>"
                                                               type="number"
                                                               class="js-disable-container-trigger" min="0"
                                                               style="max-width: 80px;" name="bonus">
													<?php else:
														echo $print_date[ 'bonus' ];
													endif; ?>
                                                </td>
												<?php if ( $edit_access ): ?>

                                                    <td>
                                                        <button class="save-button js-fake-form-send"
                                                                disabled><?php echo $helper->get_icon_save(); ?></button>
                                                    </td>
												<?php endif; ?>
                                            </tr>
										<?php endforeach; ?>
									<?php endif; ?>
                                    <!-- Add more rows here for other dispatchers -->
                                    <tr class="total">
                                        <td>Total</td>
                                        <td><?php echo $all_total_monday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_monday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_monday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total_tuesday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_tuesday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_tuesday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total_wednesday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_wednesday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_wednesday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total_thursday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_thursday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_thursday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total_friday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_friday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_friday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total_saturday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_saturday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_saturday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total_sunday[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total_sunday[ 'loads' ]; ?></td>
                                        <td>
                                            $<?php echo $helper->format_currency( $all_total_sunday[ 'profit' ] ); ?></td>
                                        <td></td>
                                        <td><?php echo $all_total[ 'calls' ]; ?></td>
                                        <td><?php echo $all_total[ 'loads' ]; ?></td>
                                        <td>$<?php echo $helper->format_currency( $all_total[ 'profit' ] ); ?></td>
                                        <td>$<?php echo $helper->format_currency( $all_total[ 'bonus' ] ); ?></td>
										<?php if ( $edit_access ): ?>

                                            <td></td>
										<?php endif; ?>
                                    </tr>
                                    </tbody>
                                </table>
							<?php endif; ?>
						
						<?php elseif ( $is_flt && ! $show_flt_tabs ):
							echo $helper->message_top( 'danger', $helper->messages_prepare( 'not-access' ) );
						else:
							echo $helper->message_top( 'error', '
it is impossible to determine your office, contact the Administrator' );
						endif; ?>
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
