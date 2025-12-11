<?php
/**
 * Home Location Chart Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare state chart data
$state_chart_data = array();
foreach ( $state_results as $state_row ) {
	if ( ! empty( $state_row['state'] ) && (int) $state_row['count'] > 0 ) {
		$state_label = $TMSDriversStatistics->get_state_label( $state_row['state'] );
		
		$state_chart_data[] = array( 
			'label' => esc_html( $state_label ), 
			'value' => (int) $state_row['count'] 
		);
	}
}
$state_chart_json = json_encode( $state_chart_data );

?>

<?php if ( ! empty( $state_chart_data ) ) : ?>
	<div class="col-12 mb-5 p-0">
		<div id="stateChart" 
			 data-chart-data="<?php echo esc_attr( $state_chart_json ); ?>" 
			 style="width:100%; height:1000px;"></div>
	</div>
<?php endif; ?>

