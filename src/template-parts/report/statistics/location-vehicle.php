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
$valid_charts = array( 'home-location', 'vehicle-type', 'nationality', 'languages', 'loads-by-state', 'loads-by-route' );
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
				<option value="loads-by-state" <?php selected( $selected_chart, 'loads-by-state' ); ?>>Loads by State (Pickup / Delivery)</option>
				<option value="loads-by-route" <?php selected( $selected_chart, 'loads-by-route' ); ?>>Loads by Route (Pickup → Delivery)</option>
			</select>
		</div>
	</div>

	<div class="row">
		<?php
		// Include individual chart components with visibility control
		$charts = array(
			'home-location'   => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/home-location.php',
			'vehicle-type'    => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/vehicle-type.php',
			'nationality'     => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/nationality.php',
			'languages'       => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/languages.php',
			'loads-by-state'  => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/loads-by-state.php',
			'loads-by-route'  => get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/loads-by-route.php',
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

