<?php
/**
 * Nationality Chart Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare nationality chart data
$nationality_chart_data = array();
foreach ( $nationality_results as $nationality_row ) {
	if ( ! empty( $nationality_row['nationality'] ) && (int) $nationality_row['count'] > 0 ) {
		$nationality_chart_data[] = array( 
			'label' => esc_html( $nationality_row['nationality'] ), 
			'value' => (int) $nationality_row['count'] 
		);
	}
}
$nationality_chart_json = json_encode( $nationality_chart_data );

?>

<?php if ( ! empty( $nationality_chart_data ) ) : ?>
	<div class="col-12 mb-5 p-0">
		<div id="nationalityChart" 
			 data-chart-data="<?php echo esc_attr( $nationality_chart_json ); ?>" 
			 style="width:100%; height:1000px;"></div>
	</div>
<?php endif; ?>

