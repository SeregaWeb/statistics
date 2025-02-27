<?php
/**
 * Template Name: Page statistics
 *
 * @package WP-rock
 * @since 4.4.0
 */

$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();
$TMSUsers   = new TMSUsers();

get_header();
$dispatchers    = $statistics->get_dispatchers();
$dispatchers_tl = $statistics->get_dispatchers_tl();

$active_item = get_field_value( $_GET, 'active_state' );

$office_dispatcher = get_field_value( $_GET, 'office' );

$sellect_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'moderator'
), true );

if ( $sellect_all_offices ) {
	$office_dispatcher = $office_dispatcher ? $office_dispatcher : 'all';
} else if ( ! $office_dispatcher ) {
	$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );
}
$offices = $helper->get_offices_from_acf();

$show_filter_by_office = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'tracking',
	'moderator'
), true );


if ( ! $active_item ) {
	$active_item = 'finance';
}
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">

                    <div class="col-12 pt-4 pb-4">
                        <ul class="nav nav-pills nav-fill gap-2 justify-content-center">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'finance' ? 'active' : '' ?>"
                                   href="<?php echo get_the_permalink() . '?active_state=finance' ?>">Total</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'yearly' ? 'active' : '' ?>"
                                   href="<?php echo get_the_permalink() . '?active_state=yearly' ?>">Statistics</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'goal' ? 'active' : '' ?>"
                                   href="<?php echo get_the_permalink() . '?active_state=goal' ?>">Monthly goal</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'top' ? 'active' : '' ?>"
                                   href="<?php echo get_the_permalink() . '?active_state=top' ?>">Charts</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'source' ? 'active' : '' ?>"
                                   href="<?php echo get_the_permalink() . '?active_state=source' ?>">Source</a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-12 d-flex flex-wrap">
						
						
						<?php if ( $active_item === 'finance' ):
							
							$dispatcher_json = $statistics->get_dispatcher_statistics( $office_dispatcher );
							$dispatcher_arr  = json_decode( $dispatcher_json, true );
						
						if ( $show_filter_by_office ): ?>
                            <form class="w-100 d-flex gap-1">
                                <select class="form-select w-auto" name="office" aria-label=".form-select-sm example">
                                    <option value="all">Office</option>
									<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
										<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key
												? 'selected' : '' ?> >
												<?php echo $val; ?>
                                            </option>
										<?php endforeach; ?>
									<?php endif; ?>
                                </select>
                                <input type="hidden" name="active_state" value="<?php echo $active_item; ?>">
                                <button class="btn btn-primary">Select Office</button>
                            </form>
						<?php endif; ?>


                            <div id="mainChart" style="width:100%; max-width:600px; height:400px;"></div>
                            <div id="mainChartPrise" style="width:100%; max-width:600px; height:400px;"></div>
						<?php
						
						if ( ! empty( $dispatcher_arr ) ) {
							
							$total_loads          = 0;
							$total_total_profit   = 0;
							$total_average_profit = 0;
							
							
							echo '<table border="1" class="table-stat">';
							echo '<thead>';
							echo '<tr>';
							echo '<th>Dispatcher Initials</th>';
							echo '<th>Loads</th>';
							echo '<th>Total Profit</th>';
							echo '<th>Average Profit</th>';
							echo '</tr>';
							echo '</thead>';
							echo '<tbody>';
							
							foreach ( $dispatcher_arr as $dispatcher ) {
								echo '<tr>';
								echo '<td>' . htmlspecialchars( $dispatcher[ 'dispatcher_initials' ] ) . '</td>';
								echo '<td>' . htmlspecialchars( $dispatcher[ 'post_count' ] ) . '</td>';
								echo '<td>$' . number_format( $dispatcher[ 'total_profit' ], 2 ) . '</td>';
								echo '<td>$' . number_format( $dispatcher[ 'average_profit' ], 2 ) . '</td>';
								echo '</tr>';
								
								$total_loads          += $dispatcher[ 'post_count' ];
								$total_total_profit   += $dispatcher[ 'total_profit' ];
								$total_average_profit += $dispatcher[ 'average_profit' ];
							}
							
							echo '</tbody>';
							echo '</table>';
							
							if ( isset( $total_loads ) && isset( $total_total_profit ) && isset( $total_average_profit ) ):
								
								echo '<h2>Total</h2>';
								
								echo '<table class="table-stat total" border="1" cellpadding="5" cellspacing="0">';
								echo '<tr class="text-left">';
								echo '<th>Loads</th>';
								echo '<th>Profit</th>';
								echo '<th>Average Profit</th>';
								echo '</tr>';
								echo '<tr class="text-left">';
								echo '<td>' . $total_loads . '</td>';
								echo '<td>$' . number_format( $total_total_profit, 2 ) . '</td>';
								echo '<td>$' . number_format( $total_average_profit, 2 ) . '</td>';
								echo '</tr>';
								echo '</table>';
							
							endif;
							
						} else {
							echo 'No data available.';
						}
						
						?>
                            <script>
                              
                              const dispatcherData = <?php echo $dispatcher_json; ?>;
                              console.log('dispatcherData', dispatcherData)
                              window.document.addEventListener('DOMContentLoaded', () => {
                                google.charts.load('current', { 'packages': ['corechart'] })
                                google.charts.setOnLoadCallback(drawChart)
                                
                                function drawChart () {
                                  // Prepare the data in Google Charts format
                                  const dataArray = [['Dispatcher', 'Post Count']]
                                  
                                  dispatcherData.forEach((item) => {
                                    // Parse post_count and if negative, set it to 0
                                    let postCount = parseInt(item.post_count)
                                    if (postCount < 0) {
                                      postCount = 0
                                    }
                                    dataArray.push([
                                      `${ item.dispatcher_initials } \n${ item.post_count }`,
                                      postCount
                                    ])
                                  })
                                  
                                  const data = google.visualization.arrayToDataTable(dataArray)
                                  
                                  const options = {
                                    title       : 'Loads',
                                    pieSliceText: 'value',
                                    legend      : { position: 'center' },
                                    // pieHole: 0.2,  // Optional: make it a donut chart
                                  }
                                  
                                  const chart = new google.visualization.PieChart(document.getElementById('mainChart'))
                                  chart.draw(data, options)
                                }
                                
                                google.charts.setOnLoadCallback(drawChartPrice)
                                
                                function drawChartPrice () {
                                  // Prepare the data in Google Charts format
                                  const dataArray = [['Dispatcher', 'Profit']]
                                  
                                  dispatcherData.forEach(item => {
                                    // Parse total_profit and average_profit, rounding to two decimals as strings
                                    // Then convert total_profit to a number for the chart
                                    let item_total = parseFloat(item?.total_profit)
                                    let item_average = parseFloat(item?.average_profit)
                                    
                                    // If total profit is negative, set it to 0
                                    if (item_total < 0) {
                                      item_total = 0
                                    }
                                    
                                    // Format numbers to two decimals for display purposes
                                    const formattedTotal = item_total.toFixed(2)
                                    const formattedAverage = item_average.toFixed(2)
                                    
                                    dataArray.push([
                                      `${ item.dispatcher_initials }\n $${ formattedTotal }\n $${ formattedAverage }`,
                                      item_total
                                    ])
                                  })
                                  
                                  const data = google.visualization.arrayToDataTable(dataArray)
                                  
                                  // Create a formatter for dollar values
                                  const formatter = new google.visualization.NumberFormat({
                                    prefix: '$',
                                  })
                                  
                                  // Apply the formatter to the numeric column (index 1)
                                  formatter.format(data, 1)
                                  
                                  const options = {
                                    title       : 'Profit',
                                    pieSliceText: 'value',
                                    legend      : { position: 'center' },
                                  }
                                  
                                  const chart = new google.visualization.PieChart(document.getElementById('mainChartPrise'))
                                  chart.draw(data, options)
                                }
                                
                              })
                            </script>
						
						<?php endif; ?>
						
						<?php if ( $active_item === 'top' ):
							$top3 = $statistics->get_table_top_3_loads();
							$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );
							
							if ( ! is_numeric( $dispatcher_initials ) ) {
								$dispatcher_initials = $dispatchers[ 0 ][ 'id' ];
							}
							
							$statistics_with_status = $statistics->get_dispatcher_statistics_with_status( $dispatcher_initials );
							
							$all_stats = $statistics->get_all_users_statistics();
							
							?>

                            <div class="row w-100">
                                <div class="col-12 col-md-6">
                                    <div class="top d-flex justify-content-start align-items-start flex-column">
                                        <h2 class="top-title">Biggest profit from a single load</h2>
                                        <div class="top-3">
											<?php if ( is_array( $top3 ) ): ?>
												<?php foreach ( $top3 as $top ):
													$names = $statistics->get_user_full_name_by_id( $top[ 'dispatcher_initials' ] );
													?>
                                                    <div class="top-3__card">
                                                        <div class="top-3__small">
															<?php echo $names[ 'initials' ]; ?>
                                                        </div>
                                                        <p class="top-3__name">
															<?php echo $names[ 'full_name' ]; ?>
                                                        </p>
                                                        <p class="top-3__sum">
                                                            $<?php echo $top[ 'profit' ]; ?>
                                                        </p>
                                                        <span class="text-primary text-small">
                                                    <?php echo $top[ 'reference_number' ]; ?>
                                                </span>
                                                    </div>
												<?php endforeach; ?>
											<?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">

                                    <form class="w-100 mt-3">
                                        <div class="w-100 mb-2">
                                            <h2>Total cancelled loads</h2>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <input type="hidden" name="active_state" value="top">
                                            <select class="form-select w-auto" name="dispatcher"
                                                    aria-label=".form-select-sm example">
												<?php if ( is_array( $dispatchers ) ): ?>
													<?php foreach ( $dispatchers as $dispatcher ): ?>
                                                        <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
															? 'selected' : ''; ?> >
															<?php echo $dispatcher[ 'fullname' ]; ?>
                                                        </option>
													<?php endforeach; ?>
												<?php endif; ?>
                                            </select>
                                            <button class="btn btn-primary" type="submit">Select</button>
                                        </div>
										
										<?php if ( isset( $statistics_with_status[ 0 ] ) ): ?>
                                            <p class="mt-2  mb-0">Load with status <span
                                                        class="text-uppercase text-danger">Cancelled</span>
                                            </p>
                                            <p class="text-danger text-l fs-1 mt-0"><?php echo $statistics_with_status[ 0 ][ 'post_count' ]; ?></p>
										<?php endif; ?>

                                    </form>

                                </div>
                                <div class="col-12 mt-3">
									<?php
									if ( is_array( $all_stats ) ):
										echo '<table border="1" class="table-stat">';
										echo '<thead>';
										echo '<tr>';
										echo '<th>Dispatcher Initials</th>';
										echo '<th>Loads</th>';
										echo '<th>Profit</th>';
										echo '</tr>';
										echo '</thead>';
										echo '<tbody>';
										
										foreach ( $all_stats as $dispatcher ) {
											echo '<tr>';
											echo '<td>
                                                    <div class="d-flex gap-1 flex-row align-items-center">
                                                        <p class="m-0">
                                                            <span data-bs-toggle="tooltip" class="initials-circle" style="background-color:' . $dispatcher[ 'color' ] . '">
                                                              ' . $dispatcher[ "initials" ] . '
                                                            </span>
                                                        </p>
                                                        ' . htmlspecialchars( $dispatcher[ 'name' ] ) . '
                                                    </div>
                                                </td>';
											echo '<td>' . htmlspecialchars( $dispatcher[ 'post_count' ] ) . '</td>';
											echo '<td>' . esc_html( '$' . $helper->format_currency( $dispatcher[ 'total_profit' ] ) ) . '</td>';
											echo '</tr>';
											
										}
										
										echo '</tbody>';
										echo '</table>';
									endif; ?>
                                </div>
                            </div>
						
						
						<?php endif; ?>
						
						<?php if ( $active_item === 'yearly' ):
							
							$current_year = date( 'Y' );
							$year_param = get_field_value( $_GET, 'fyear' );
							$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );
							
							if ( ! is_numeric( $dispatcher_initials ) ) {
								$dispatcher_initials = $dispatchers[ 0 ][ 'id' ];
							}
							
							if ( ! is_numeric( $year_param ) ) {
								$year_param = $current_year;
							}
							
							$mountly = $statistics->get_monthly_dispatcher_stats( intval( $dispatcher_initials ), intval( $year_param ) );
							
							?>
                            <form class="monthly w-100 ">
                                <input type="hidden" name="active_state" value="yearly">

                                <div class="d-flex gap-1">
                                    <select class="form-select w-auto" required name="fyear"
                                            aria-label=".form-select-sm example">
                                        <option value="">Year</option>
										<?php
										for ( $year = 2023; $year <= $current_year; $year ++ ) {
											$select = is_numeric( $year_param ) && + $year_param === + $year
												? 'selected' : '';
											echo '<option ' . $select . ' value="' . $year . '">' . $year . '</option>';
										}
										?>
                                    </select>
                                    <select class="form-select w-auto" required name="dispatcher"
                                            aria-label=".form-select-sm example">
                                        <option value="">Dispatcher</option>
										<?php if ( is_array( $dispatchers ) ): ?>
											<?php foreach ( $dispatchers as $dispatcher ): ?>
                                                <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
													? 'selected' : ''; ?> >
													<?php echo $dispatcher[ 'fullname' ]; ?>
                                                </option>
											<?php endforeach; ?>
										<?php endif; ?>
                                    </select>
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                </div>
								<?php
								echo '<table class="table-stat">';
								echo '<thead><tr><th>Month</th><th>Loads</th><th>Profit</th><th>Average per load</th><th>Average daily per month</th></tr></thead>';
								echo '<tbody>';
								
								$total_loads          = 0;
								$total_total_profit   = 0;
								$total_average_profit = 0;
								
								foreach ( $mountly as $month_data ) {
									
									$hide_column = $month_data[ 'post_count' ] === 0 ? 'd-none' : '';
									$work_day    = $statistics->countWeekdays( $month_data[ 'month' ], $year_param );
									
									echo '<tr class="' . $hide_column . '">';
									echo '<td>' . $month_data[ 'month' ] . '</td>';
									echo '<td>' . $month_data[ 'post_count' ] . '</td>';
									echo '<td>$' . number_format( $month_data[ 'total_profit' ], 2 ) . '</td>';
									echo '<td>$' . number_format( $month_data[ 'average_profit' ], 2 ) . '</td>';
									echo '<td title="Days: ' . $work_day . '">$' . number_format( $month_data[ 'total_profit' ] / $work_day, 2 ) . '</td>';
									echo '</tr>';
									
									
									$total_loads          += $month_data[ 'post_count' ];
									$total_total_profit   += $month_data[ 'total_profit' ];
									$total_average_profit += $month_data[ 'average_profit' ];
								}
								
								echo '</tbody>';
								echo '</table>';
								?>
                            </form>
							
							<?php
							if ( isset( $total_loads ) && isset( $total_total_profit ) && isset( $total_average_profit ) ):
								echo '<h2>Total per year</h2>';
								
								echo '<table class="table-stat total" border="1" cellpadding="5" cellspacing="0">';
								echo '<tr class="text-left">';
								echo '<th>Loads</th>';
								echo '<th>Profit</th>';
								echo '<th>Average Profit</th>';
								echo '</tr>';
								echo '<tr class="text-left">';
								echo '<td>' . $total_loads . '</td>';
								echo '<td>$' . number_format( $total_total_profit, 2 ) . '</td>';
								echo '<td>$' . number_format( $total_average_profit, 2 ) . '</td>';
								echo '</tr>';
								echo '</table>';
							endif;
							?>
						
						<?php endif; ?>
						
						<?php if ( $active_item === 'goal' ):
						
						$dispatcher_tl_initials = get_field_value( $_GET, 'team-lead' );
						
						if ( ! $dispatcher_tl_initials ) {
							$dispatcher_tl_initials = $dispatchers_tl[ 0 ][ 'id' ];
						}
						
						$my_team        = get_field( 'my_team', 'user_' . $dispatcher_tl_initials );
						$my_team[]      = $dispatcher_tl_initials;
						$dispatcher_arr = $statistics->get_dispatcher_statistics_current_month( $my_team );
						
						$dispatcher_stats_indexed = [];
						if ( is_array( $dispatcher_arr ) && ! empty( $dispatcher_arr ) ) {
							foreach ( $dispatcher_arr as $dispatcher_stat ) {
								$dispatcher_stats_indexed[ $dispatcher_stat[ 'dispatcher_initials' ] ] = $dispatcher_stat;
							}
						}
						$total_team_load     = 0;
						$total_team_profit   = 0;
						$total_team_goals    = 0;
						$total_team_average  = 0;
						$total_team_left     = 0;
						$total_team_complete = 0;
						
						
						?>

                        <form class="w-100">
                            <div class="d-flex gap-1">
                                <input type="hidden" name="active_state" value="goal">
                                <select class="form-select w-auto" name="team-lead"
                                        aria-label=".form-select-sm example">
                                    <option value="">Team lead</option>
									<?php if ( is_array( $dispatchers_tl ) ): ?>
										<?php foreach ( $dispatchers_tl as $dispatcher ): ?>
                                            <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_tl_initials ) === strval( $dispatcher[ 'id' ] )
												? 'selected' : ''; ?> >
												<?php echo $dispatcher[ 'fullname' ]; ?>
                                            </option>
										<?php endforeach; ?>
									<?php endif; ?>
                                </select>
                                <button class="btn btn-primary" type="submit">Filter</button>
                            </div>
							<?php
							// HTML код для отображения таблицы
							echo '<table class="table-stat" border="1" cellpadding="5" cellspacing="0">';
							echo '<tr class="text-center">';
							echo '<th class="text-left">Dispatcher</th>';
							echo '<th>Loads</th>';
							echo '<th>Profit</th>';
							echo '<th>Goal</th>';
							echo '<th>Left</th>';
							echo '<th>Average Profit</th>';
							echo '<th>Completed</th>';
							echo '</tr>';
							
							// Проходим по массиву диспетчеров, чтобы гарантировать вывод всех диспетчеров
							foreach ( $dispatchers as $dispatcher ) {
								if ( $my_team !== null && is_array( $my_team ) && in_array( $dispatcher[ 'id' ], $my_team ) ) {
									$fullname = $dispatcher[ 'fullname' ];
									// Если данные по диспетчеру есть в $dispatcher_stats_indexed, используем их, иначе нули
									if ( isset( $dispatcher_stats_indexed[ $fullname ] ) ) {
										$stat           = $dispatcher_stats_indexed[ $fullname ];
										$post_count     = $stat[ 'post_count' ];
										$total_profit   = number_format( $stat[ 'total_profit' ], 2 );
										$average_profit = number_format( $stat[ 'average_profit' ], 2 );
										$goal           = $stat[ 'goal' ];
										$left           = $stat[ 'goal' ] - $stat[ 'total_profit' ];
										
										$compleat_color = '';
										$text_color     = '#000000';
										
										if ( $left < 0 ) {
											$left = 0;
										}
										
										if ( is_numeric( $goal ) && $goal > 0 ) {
											$value_pr = ( $stat[ 'total_profit' ] / + $goal ) * 100;
											
											$goal_completion = number_format( $value_pr, 2 );
											
											
											if ( $value_pr > 0 && $value_pr <= 80 ) {
												$compleat_color = '#ff0000';
												$text_color     = '#ffffff';
											} else if ( $value_pr > 80 && $value_pr <= 90 ) {
												$compleat_color = '#ff5858 ';
												$text_color     = '#ffffff';
											} else if ( $value_pr > 90 && $value_pr <= 99.99 ) {
												$compleat_color = '#ff8989';
												$text_color     = '#ffffff';
											} else {
												$compleat_color = '#b2d963';
											}
											
										} else {
											$goal_completion = 'N/A'; // Если цель равна 0
										}
									} else {
										// Если данных по диспетчеру нет, выставляем 0 для всех полей
										$post_count      = 0;
										$compleat_color  = '#ff0000';
										$text_color      = '#ffffff';
										$total_profit    = number_format( 0, 2 );
										$average_profit  = number_format( 0, 2 );
										$goal            = 0;
										$left            = 0;
										$goal            = get_field( 'monthly_goal', 'user_' . $dispatcher[ 'id' ] );
										$goal_completion = 0;
									}
									
									
									if ( isset( $stat ) ) {
										$total_team_load   += is_numeric( $post_count ) ? $post_count : 0;
										$total_team_profit += is_numeric( $stat[ 'total_profit' ] )
											? $stat[ 'total_profit' ] : 0;
										$total_team_goals  += is_numeric( $stat[ 'goal' ] ) ? $stat[ 'goal' ] : 0;
										$total_team_left   = is_numeric( $stat[ 'goal' ] ) && is_numeric( $stat[ 'total_profit' ] )
											? $stat[ 'goal' ] - $stat[ 'total_profit' ] : 0;
									}
									
									if ( $total_team_left < 0 ) {
										$total_team_left = 0;
									}
									
									// Вывод строки таблицы для текущего диспетчера
									echo '<tr class="text-center">';
									echo '<td class="text-left">' . $fullname . '</td>';
									echo '<td>' . $post_count . '</td>';
									echo '<td>$' . $total_profit . '</td>';
									echo '<td>$' . $goal . '</td>';
									echo '<td style="background-color:' . $compleat_color . '; color: ' . $text_color . ';">$' . $left . '</td>';
									echo '<td>$' . $average_profit . '</td>';
									echo '<td>' . $goal_completion . '%</td>';
									echo '</tr>';
								}
							}
							
							echo '</table>';
							
							
							if ( $total_team_profit > 0 && $total_team_load > 0 && $total_team_goals > 0 ) :
								
								$total_team_average  += $total_team_profit / $total_team_load;
								$total_team_complete = ( + $total_team_profit / + $total_team_goals ) * 100;
								$total_team_complete = number_format( $total_team_complete, 2 );
								
								echo '<h2>Total team</h2>';
								
								echo '<table class="table-stat total" border="1" cellpadding="5" cellspacing="0">';
								echo '<tr class="text-center">';
								echo '<th>Loads</th>';
								echo '<th>Profit</th>';
								echo '<th>Goal</th>';
								echo '<th>Left</th>';
								echo '<th>Average Profit</th>';
								echo '<th>Completed</th>';
								echo '</tr>';
								echo '<tr class="text-center">';
								echo '<td>' . $total_team_load . '</td>';
								echo '<td>$' . number_format( $total_team_profit, 2 ) . '</td>';
								echo '<td>$' . number_format( $total_team_goals, 2 ) . '</td>';
								echo '<td>$' . number_format( $total_team_left, 2 ) . '</td>';
								echo '<td>$' . number_format( $total_team_average, 2 ) . '</td>';
								echo '<td>' . number_format( $total_team_complete, 2 ) . '%</td>';
								echo '</tr>';
								echo '</table>';
							
							endif;
							endif; ?>
							
							<?php if ( $active_item === 'source' ): ?>


                                <div class="w-100 ">
                                    <div class="w-100 mb-2">
                                        <h2>Source</h2>
										<?php
										$dispatcher_json = $statistics->get_sources_statistics( $office_dispatcher );
										
										if ( $show_filter_by_office ): ?>
                                            <form class="w-100 d-flex gap-1">
                                                <select class="form-select w-auto" name="office"
                                                        aria-label=".form-select-sm example">
                                                    <option value="all">Office</option>
													<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
														<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key
																? 'selected' : '' ?> >
																<?php echo $val; ?>
                                                            </option>
														<?php endforeach; ?>
													<?php endif; ?>
                                                </select>
                                                <input type="hidden" name="active_state"
                                                       value="<?php echo $active_item; ?>">
                                                <button class="btn btn-primary">Select Office</button>
                                            </form>
										<?php endif; ?>

                                        <div class="d-flex">
                                            <div id="sourcePostCountChart"
                                                 style="width:100%; max-width:50%; height:50vh;"></div>
                                            <div id="sourceProfitChart"
                                                 style="width:100%; max-width:50%; height:50vh;"></div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                  
                                  const sourcesData = <?php echo $dispatcher_json; ?>;
                                  console.log('sourcesData', sourcesData)
                                  window.document.addEventListener('DOMContentLoaded', () => {
                                    google.charts.load('current', { 'packages': ['corechart'] })
                                    
                                    google.charts.setOnLoadCallback(drawSourcePostCountChart)
                                    google.charts.setOnLoadCallback(drawSourceProfitChart)
                                    
                                    // График количества постов по источникам
                                    function drawSourcePostCountChart () {
                                      const dataArray = [['Source', 'Post Count']]
                                      
                                      Object.keys(sourcesData).forEach(key => {
                                        const source = sourcesData[key]
                                        dataArray.push([source.label, parseInt(source.post_count)])
                                      })
                                      
                                      const data = google.visualization.arrayToDataTable(dataArray)
                                      
                                      const options = {
                                        title       : 'Loads',
                                        pieSliceText: 'value',
                                        legend      : { position: 'center' },
                                      }
                                      
                                      const chart = new google.visualization.PieChart(document.getElementById('sourcePostCountChart'))
                                      chart.draw(data, options)
                                    }
                                    
                                    // График суммарного профита по источникам
                                    function drawSourceProfitChart () {
                                      const dataArray = [['Source', 'Total Profit']]
                                      
                                      Object.keys(sourcesData).forEach(key => {
                                        const source = sourcesData[key]
                                        const profit = parseFloat(source.total_profit.replace(',', '')) // Убираем $ и запятые
                                        dataArray.push([source.label, profit])
                                      })
                                      
                                      const data = google.visualization.arrayToDataTable(dataArray)
                                      
                                      // Создаем форматтер для добавления доллара
                                      const formatter = new google.visualization.NumberFormat({
                                        prefix: '$',
                                      })
                                      
                                      // Применяем форматтер к колонке с числами (индекс 1)
                                      formatter.format(data, 1)
                                      
                                      const options = {
                                        title       : 'Profit',
                                        pieSliceText: 'value',
                                        legend      : { position: 'center' },
                                      }
                                      
                                      const chart = new google.visualization.PieChart(document.getElementById('sourceProfitChart'))
                                      chart.draw(data, options)
                                    }
                                  })
                                </script>
							
							<?php endif; ?>
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

get_footer();
