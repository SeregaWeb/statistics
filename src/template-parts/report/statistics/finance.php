<?php
$statistics         = new TMSStatistics();
$report             = new TMSReports();
$helper             = new TMSReportsHelper();
$TMSUsers           = new TMSUsers();
$office_dispatcher  = get_field_value( $_GET, 'office' );
$active_item        = get_field_value( $_GET, 'active_state' );
$offices            = $helper->get_offices_from_acf();
$select_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
  'hr_manager',
	'moderator'
), true );

if ( $select_all_offices ) {
	$office_dispatcher = $office_dispatcher ? $office_dispatcher : 'all';
} else if ( ! $office_dispatcher ) {
	$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );
}

$show_filter_by_office = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter',
	'recruiter-tl',
  'hr_manager',
	'tracking',
  'morning_tracking',
  'nightshift_tracking',
	'moderator'
), true );

$project = $report->project;

$dispatcher_json = $statistics->get_dispatcher_statistics( $office_dispatcher, $project );
$dispatcher_arr  = json_decode( $dispatcher_json, true );

if ( ! $active_item ) {
	$active_item = 'finance';
}

if ( $show_filter_by_office ): ?>
    <form class="w-100 d-flex gap-1">
        <select class="form-select w-auto" name="office" aria-label=".form-select-sm example">
            <option value="all">Company total</option>
			<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
				<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                    <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key ? 'selected' : '' ?> >
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
		
		$total_loads        += $dispatcher[ 'post_count' ];
		$total_total_profit += $dispatcher[ 'total_profit' ];
	}
	
	echo '</tbody>';
	echo '</table>';
	
	if ( isset( $total_loads ) && isset( $total_total_profit ) && isset( $total_average_profit ) ):
		$total_average_profit = $total_total_profit / $total_loads;
		
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
						