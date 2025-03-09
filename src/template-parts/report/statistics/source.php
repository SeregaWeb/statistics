<?php
$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();
$TMSUsers   = new TMSUsers();

$active_item       = get_field_value( $_GET, 'active_state' );
$office_dispatcher = get_field_value( $_GET, 'office' );


$select_all_offices = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'administrator',
	'recruiter',
	'recruiter-tl',
	'moderator'
), true );

if ( $select_all_offices ) {
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
?>
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
                            <option value="<?php echo $key; ?>" <?php echo $office_dispatcher === $key ? 'selected'
								: '' ?> >
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