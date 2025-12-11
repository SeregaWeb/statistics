<?php
/**
 * Home location & Vehicle type Tab Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get selected chart from GET parameter (default: vehicle-type)
$selected_chart = isset( $_GET['chart'] ) ? sanitize_text_field( $_GET['chart'] ) : 'vehicle-type';
$valid_charts = array( 'home-location', 'vehicle-type', 'nationality', 'languages' );
if ( ! in_array( $selected_chart, $valid_charts, true ) ) {
	$selected_chart = 'vehicle-type';
}

?>

<div class="col-12 mb-3">
	<!-- Chart Filter Dropdown -->
	<div class="row mb-4">
		<div class="col-12 col-md-4">
			<label for="chartFilter" class="form-label fw-bold">Select Chart:</label>
			<select class="form-select" id="chartFilter" name="chart">
				<option value="vehicle-type" <?php selected( $selected_chart, 'vehicle-type' ); ?>>Vehicle Type</option>
				<option value="home-location" <?php selected( $selected_chart, 'home-location' ); ?>>Home Location</option>
				<option value="nationality" <?php selected( $selected_chart, 'nationality' ); ?>>Nationality</option>
				<option value="languages" <?php selected( $selected_chart, 'languages' ); ?>>Languages</option>
			</select>
		</div>
	</div>

	<div class="row">
		<?php
		// Include individual chart components with visibility control
		$charts = array(
			'home-location' => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/home-location.php',
			'vehicle-type' => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/vehicle-type.php',
			'nationality' => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/nationality.php',
			'languages' => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/languages.php',
		);
		
		foreach ( $charts as $chart_key => $chart_path ) {
			$is_visible = ( $selected_chart === $chart_key ) ? '' : 'style="display:none;"';
			echo '<div class="chart-container" data-chart="' . esc_attr( $chart_key ) . '" ' . $is_visible . '>';
			include $chart_path;
			echo '</div>';
		}
		?>
	</div>
</div>

<script>
(function() {
	// Handle chart filter dropdown
	const chartFilter = document.getElementById('chartFilter');
	
	if (chartFilter) {
		chartFilter.addEventListener('change', function() {
			const selectedChart = this.value;
			const chartContainers = document.querySelectorAll('.chart-container');
			
			// Hide all charts
			chartContainers.forEach(function(container) {
				container.style.display = 'none';
			});
			
			// Show selected chart
			const selectedContainer = document.querySelector('.chart-container[data-chart="' + selectedChart + '"]');
			if (selectedContainer) {
				selectedContainer.style.display = 'block';
				
				// Re-initialize the selected chart
				setTimeout(function() {
					// Map chart names to chart IDs and chart types
					const chartMap = {
						'home-location': { id: 'stateChart', useBar: true },
						'vehicle-type': { id: 'vehicleTypeChart', useBar: false },
						'nationality': { id: 'nationalityChart', useBar: true },
						'languages': { id: 'languageChart', useBar: false }
					};
					
					const chartConfig = chartMap[selectedChart];
					if (chartConfig) {
						const chartElement = document.getElementById(chartConfig.id);
						if (chartElement) {
							// Reset initialization flag
							chartElement.dataset.initialized = 'false';
							
							// Directly initialize the chart using global function
							if (typeof window.initDriversChart === 'function') {
								window.initDriversChart(chartConfig.id, chartConfig.useBar);
							} else {
								// Fallback: trigger window resize and call initDriversStatisticsCharts
								window.dispatchEvent(new Event('resize'));
								if (typeof window.initDriversStatisticsCharts === 'function') {
									window.initDriversStatisticsCharts();
								}
							}
						}
					}
				}, 300);
			}
			
			// Update URL without reload
			const url = new URL(window.location.href);
			url.searchParams.set('chart', selectedChart);
			window.history.replaceState(null, '', url);
		});
	}
})();
</script>

