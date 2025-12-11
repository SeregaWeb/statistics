<?php
/**
 * Vehicle Type Chart Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare vehicle type chart data using helper array for labels
$vehicle_chart_data = array();

// Map of vehicle keys to their corresponding totals variables
$vehicle_totals = array(
	'cargo-van'    => $cargo_van_all,
	'sprinter-van' => $sprinter_van_all,
	'box-truck'    => $box_truck_all,
	'reefer'       => $reefer_all,
	'pickup'       => isset( $pickup_all ) ? $pickup_all : 0,
	'semi-truck'   => isset( $semi_truck_all ) ? $semi_truck_all : 0,
);

// Iterate through all vehicle types from helper
foreach ( $TMSDriversHelper->vehicle as $vehicle_key => $vehicle_label ) {
	$total = isset( $vehicle_totals[ $vehicle_key ] ) ? (int) $vehicle_totals[ $vehicle_key ] : 0;
	if ( $total > 0 ) {
		$vehicle_chart_data[] = array( 
			'label' => esc_html( $TMSDriversStatistics->get_vehicle_label( $vehicle_key ) ), 
			'value' => $total 
		);
	}
}

$vehicle_chart_json = json_encode( $vehicle_chart_data );

?>

<?php if ( ! empty( $vehicle_chart_data ) ) : ?>
	<div class="col-12 mb-5 p-0">
		<div id="vehicleTypeChart" 
			 data-chart-data="<?php echo esc_attr( $vehicle_chart_json ); ?>" 
			 style="width:100%; height:600px;"></div>
	</div>
<?php endif; ?>

