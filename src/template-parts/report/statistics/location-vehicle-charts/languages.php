<?php
/**
 * Languages Chart Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare language chart data
$language_chart_data = array();
foreach ( $language_counts as $lang_label => $lang_count ) {
	if ( $lang_count > 0 ) {
		$language_chart_data[] = array( 
			'label' => esc_html( $lang_label ), 
			'value' => (int) $lang_count 
		);
	}
}
$language_chart_json = json_encode( $language_chart_data );

?>

<?php if ( ! empty( $language_chart_data ) ) : ?>
	<div class="col-12 mb-5 p-0">
		<div id="languageChart" 
			 data-chart-data="<?php echo esc_attr( $language_chart_json ); ?>" 
			 style="width:100%; height:600px;"></div>
	</div>
<?php endif; ?>

