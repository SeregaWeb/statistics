<?php
/**
 * Template Name: Page statistics
 *
 * @package WP-rock
 * @since 4.4.0
 */

$statistics = new TMSStatistics();


get_header();
$dispatchers = $statistics->get_dispatchers();

$active_item = get_field_value($_GET, 'active_state');

if (!$active_item) {
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
                                <a class="nav-link <?php echo $active_item === 'finance' ? 'active' : '' ?>" href="<?php echo get_the_permalink() . '?active_state=finance' ?>">Finance</a>
                            </li>
                           
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'yearly' ? 'active' : '' ?>" href="<?php echo get_the_permalink() . '?active_state=yearly' ?>">Yearly</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'goal' ? 'active' : '' ?>" href="<?php echo get_the_permalink() . '?active_state=goal' ?>">Monthly goal</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo $active_item === 'top' ? 'active' : '' ?>" href="<?php echo get_the_permalink() . '?active_state=top' ?>">Top list</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="col-12 d-flex flex-wrap">
                        
                        <?php if ($active_item === 'finance'):
                            $dispatcher_json = $statistics->get_dispatcher_statistics();
                            $dispatcher_arr = json_decode($dispatcher_json, true);
                        ?>
                        
                            <div id="mainChart" style="width:100%; max-width:600px; height:400px;"></div>
                            <div id="mainChartPrise" style="width:100%; max-width:600px; height:400px;"></div>
                            <?php
                            
                            if (!empty($dispatcher_arr)) {
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
                                
                                foreach ($dispatcher_arr as $dispatcher) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($dispatcher['dispatcher_initials']) . '</td>';
                                    echo '<td>' . htmlspecialchars($dispatcher['post_count']) . '</td>';
                                    echo '<td>$' . number_format($dispatcher['total_profit'], 2) . '</td>';
                                    echo '<td>$' . number_format($dispatcher['average_profit'], 2) . '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                            } else {
                                echo 'No data available.';
                            }
                            
                            ?>
                            <script>
                              
                              const dispatcherData = <?php echo $dispatcher_json; ?>;
                              
                              window.document.addEventListener('DOMContentLoaded', ()=> {
                                google.charts.load('current', {'packages':['corechart']});
                                google.charts.setOnLoadCallback(drawChart);
                                
                                function drawChart() {
                                  // Prepare the data in Google Charts format
                                  const dataArray = [['Dispatcher', 'Post Count']];
                                  
                                  dispatcherData.forEach(item => {
                                    dataArray.push([`${item.dispatcher_initials} \n${item.post_count}`, parseInt(item.post_count)]);
                                  });
                                  
                                  const data = google.visualization.arrayToDataTable(dataArray);
                                  
                                  const options = {
                                    title: 'Dispatcher Loads',
                                    pieSliceText: 'value',
                                    legend: { position: 'center' },
                                    // pieHole: 0.2,  // Optional: make it a donut chart
                                    is3D: true
                                  };
                                  
                                  const chart = new google.visualization.PieChart(document.getElementById('mainChart'));
                                  chart.draw(data, options);
                                }
                                
                                google.charts.setOnLoadCallback(drawChartPrice);
                                
                                function drawChartPrice () {
                                  // Prepare the data in Google Charts format
                                  const dataArray = [['Dispatcher', 'Post Count']];
                                  
                                  dispatcherData.forEach(item => {
                                    let item_total = parseFloat(item?.total_profit).toFixed(2);  // Convert to number, then round
                                    let item_average = parseFloat(item?.average_profit).toFixed(2);  // Convert to number, then round
                                    
                                    // Use item_total instead of item.item_total
                                    dataArray.push([`${item.dispatcher_initials}\n $${item_total}\n $${item_average}`, parseInt(item_total)]);
                                  });
                                  
                                  const data = google.visualization.arrayToDataTable(dataArray);
                                  
                                  const options = {
                                    title: 'Dispatcher Prices',
                                    pieSliceText: 'value',
                                    legend: { position: 'center' },
                                    is3D: true
                                  };
                                  
                                  const chart = new google.visualization.PieChart(document.getElementById('mainChartPrise'));
                                  chart.draw(data, options);
                                }
                              });
                            </script>
                        
                        <?php endif; ?>
                        
                        
                        <?php if ($active_item === 'top'):
	                        $top3 = $statistics->get_table_top_3_loads();
                            ?>
                        
                            <div class="top">
                        <h2 class="top-title">Top 3 loads</h2>
                        <div class="top-3">
                            <?php if (is_array($top3)): ?>
                                <?php foreach ($top3 as $top):
                                    $names = $statistics->get_user_full_name_by_id($top['dispatcher_initials']);
                                    ?>
                                    <div class="top-3__card">
                                        <div class="top-3__small">
                                            <?php echo $names['initials']; ?>
                                        </div>
                                        <p class="top-3__name">
                                          <?php echo $names['full_name']; ?>
                                        </p>
                                        <p class="top-3__sum">
                                            $<?php echo $top['profit']; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        </div>

                        <?php endif; ?>

                        <?php if ($active_item === 'yearly'):
	                        
	                        
	                        $current_year = date('Y');
	                        $year_param = get_field_value($_GET,'fyear');
	                        $dispatcher_initials = get_field_value($_GET, 'dispatcher');
	                        
	                        if (!is_numeric($dispatcher_initials)) {
		                        $dispatcher_initials = $dispatchers[0]['id'];
	                        }
	                        
	                        if (!is_numeric($year_param)) {
		                        $year_param = $current_year;
	                        }
	                        
	                        $mountly = $statistics->get_monthly_dispatcher_stats(intval($dispatcher_initials), intval($year_param));
                            
                            ?>
                            <form class="monthly w-100 ">
                            <input type="hidden" name="active_state" value="yearly">
                            <h2 class="top-title">Stats for year</h2>
                            
                            <div class="d-flex gap-1">
                                <select class="form-select w-auto" required name="fyear" aria-label=".form-select-sm example">
                                    <option value="">Year</option>
		                            <?php
		                            
		                            for ( $year = 2023; $year <= $current_year; $year++ ) {
			                            $select = is_numeric($year_param) &&  +$year_param === +$year ? 'selected' : '' ;
			                            echo '<option '.$select.' value="' . $year . '">' . $year . '</option>';
		                            }
		                            ?>
                                </select>
                                <select class="form-select w-auto" required name="dispatcher" aria-label=".form-select-sm example">
                                    <option value="">Dispatcher</option>
		                            <?php if (is_array($dispatchers)): ?>
			                            <?php foreach ($dispatchers as $dispatcher):  ?>
                                            <option value="<?php echo $dispatcher['id']; ?>" <?php echo strval($dispatcher_initials) === strval($dispatcher['id']) ? 'selected' : ''; ?> >
					                            <?php echo $dispatcher['fullname']; ?>
                                            </option>
			                            <?php endforeach; ?>
		                            <?php endif; ?>
                                </select>
                                <button class="btn btn-primary" type="submit">Filter</button>
                            </div>
                            <?php
                            echo '<table class="table-stat">';
                            echo '<thead><tr><th>Month</th><th>Post Count</th><th>Total Profit</th><th>Average Profit</th></tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($mountly as $month_data) {
                                
                                $hide_column = $month_data['post_count'] === 0 ? 'd-none' : '';
                                
	                            echo '<tr class="'.$hide_column.'">';
	                            echo '<td>' . $month_data['month'] . '</td>';
	                            echo '<td>' . $month_data['post_count'] . '</td>';
	                            echo '<td>$' . number_format($month_data['total_profit'], 2) . '</td>';
	                            echo '<td>$' . number_format($month_data['average_profit'], 2) . '</td>';
	                            echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                            ?>
                        </form>
                        <?php endif; ?>

                        <?php if ($active_item === 'goal'):
	                        $dispatcher_arr = $statistics->get_dispatcher_statistics_current_month();
	                        
	                        // Преобразуем $dispatcher_arr в удобный для поиска формат (по dispatcher_initials)
	                        $dispatcher_stats_indexed = [];
	                        foreach ($dispatcher_arr as $dispatcher_stat) {
		                        $dispatcher_stats_indexed[$dispatcher_stat['dispatcher_initials']] = $dispatcher_stat;
	                        }

                            // HTML код для отображения таблицы
	                        echo '<table class="table-stat" border="1" cellpadding="5" cellspacing="0">';
	                        echo '<tr>';
	                        echo '<th>Dispatcher</th>';
	                        echo '<th>Post Count</th>';
	                        echo '<th>completion percentage</th>';
	                        echo '<th>Goal</th>';
	                        echo '<th>Total Profit</th>';
	                        echo '<th>Average Profit</th>';
	                        echo '</tr>';

                            // Проходим по массиву диспетчеров, чтобы гарантировать вывод всех диспетчеров
	                        foreach ($dispatchers as $dispatcher) {
		                        $fullname = $dispatcher['fullname'];
		                        
		                        // Если данные по диспетчеру есть в $dispatcher_stats_indexed, используем их, иначе нули
		                        if (isset($dispatcher_stats_indexed[$fullname])) {
			                        $stat = $dispatcher_stats_indexed[$fullname];
			                        $post_count = $stat['post_count'];
			                        $total_profit = number_format($stat['total_profit'], 2);
			                        $average_profit = number_format($stat['average_profit'], 2);
			                        $goal = $stat['goal'];
			                        if (is_numeric($goal) && $goal > 0 ) {
				                        $goal_completion = number_format(($stat['total_profit'] / +$goal) * 100, 2);
			                        } else {
				                        $goal_completion = 'N/A'; // Если цель равна 0
			                        }
		                        } else {
			                        // Если данных по диспетчеру нет, выставляем 0 для всех полей
			                        $post_count = 0;
			                        $total_profit = number_format(0, 2);
			                        $average_profit = number_format(0, 2);
			                        $goal = 0;
			                        $goal = get_field('monthly_goal', 'user_'.$dispatcher['id']);
			                        $goal_completion = 0;
		                        }
		                        
		                        // Вывод строки таблицы для текущего диспетчера
		                        echo '<tr>';
		                        echo '<td>' . $fullname . '</td>';
		                        echo '<td>' . $post_count . '</td>';
		                        echo '<td>' . $goal_completion . '%</td>';
		                        echo '<td>$' . $goal . '</td>';
		                        echo '<td>$' . $total_profit . '</td>';
		                        echo '<td>$' . $average_profit . '</td>';
		                        echo '</tr>';
	                        }
	                        
	                        echo '</table>';
	                        
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

echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add' ) );

get_footer();
