<?php
/**
 * Loads by State Chart Component (Pickup / Delivery)
 *
 * Uses new locations tables and shipper table to build
 * statistics of loads per state for pickup or delivery.
 *
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read filters from GET
$location_type = isset( $_GET['loads_location_type'] )
	? sanitize_text_field( $_GET['loads_location_type'] )
	: 'pickup';

if ( $location_type !== 'delivery' ) {
	$location_type = 'pickup';
}

$country = isset( $_GET['loads_country'] )
	? sanitize_text_field( $_GET['loads_country'] )
	: 'USA';

$country = strtoupper( $country );
$valid_countries = array( 'USA', 'CANADA', 'MEXICO', 'ALL' );
if ( ! in_array( $country, $valid_countries, true ) ) {
	$country = 'USA';
}

// Year filter (default: current year, minimum 2024, or 'all' for all years)
$year_input = isset( $_GET['loads_year'] ) ? sanitize_text_field( $_GET['loads_year'] ) : null;
$current_year = (int) date( 'Y' );
$min_year = 2024;
$year = null;

// Check if "all" was explicitly selected
if ( $year_input === 'all' ) {
	// All years - no filter
	$year = null;
} elseif ( $year_input !== null && $year_input !== '' && is_numeric( $year_input ) ) {
	// Specific year selected
	$year = intval( $year_input );
	if ( $year < $min_year ) {
		$year = $current_year;
	}
	if ( $year > $current_year ) {
		$year = $current_year;
	}
} else {
	// No year in GET - default to current year (first visit, no selection made)
	$year = $current_year;
}

// Month filter (default: null = all months)
$month = null;
if ( isset( $_GET['loads_month'] ) && $_GET['loads_month'] !== '' ) {
	$month = intval( $_GET['loads_month'] );
	if ( $month < 1 || $month > 12 ) {
		$month = null;
	}
}

// Get statistics from new locations tables
$loads_state_results = $TMSDriversStatistics->get_loads_state_statistics( $location_type, $country, $year, $month );

// Prepare chart data for Google Bar Chart
$loads_chart_data = array();
foreach ( $loads_state_results as $row ) {
	if ( ! empty( $row['label'] ) && (int) $row['count'] > 0 ) {
		$loads_chart_data[] = array(
			'label' => esc_html( $row['label'] ),
			'value' => (int) $row['count'],
		);
	}
}

$loads_chart_json = json_encode( $loads_chart_data );

// Country options for filter
$country_options = array(
	'USA'    => 'USA',
	'CANADA' => 'Canada',
	'MEXICO' => 'Mexico',
	'ALL'    => 'All countries',
);

// Generate year options (from 2024 to current year, plus "All years")
$year_options = array(
	'all' => 'All years',
);
for ( $y = $current_year; $y >= $min_year; $y-- ) {
	$year_options[ $y ] = (string) $y;
}

// Month options
$month_options = array(
	''   => 'All months',
	'1'  => 'January',
	'2'  => 'February',
	'3'  => 'March',
	'4'  => 'April',
	'5'  => 'May',
	'6'  => 'June',
	'7'  => 'July',
	'8'  => 'August',
	'9'  => 'September',
	'10' => 'October',
	'11' => 'November',
	'12' => 'December',
);

?>

<div class="col-12 mb-3 p-0">
	<div class="row mb-4">
		<div class="col-12 col-md-3">
			<label for="loadsLocationType" class="form-label fw-bold">Location type:</label>
			<select class="form-select" id="loadsLocationType" name="loads_location_type">
				<option value="pickup" <?php selected( $location_type, 'pickup' ); ?>>Pickup</option>
				<option value="delivery" <?php selected( $location_type, 'delivery' ); ?>>Delivery</option>
			</select>
		</div>
		<div class="col-12 col-md-3">
			<label for="loadsCountry" class="form-label fw-bold">Country:</label>
			<select class="form-select" id="loadsCountry" name="loads_country">
				<?php foreach ( $country_options as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="col-12 col-md-3">
			<label for="loadsYear" class="form-label fw-bold">Year:</label>
			<select class="form-select" id="loadsYear" name="loads_year">
				<?php foreach ( $year_options as $y => $label ) : ?>
					<?php
					$is_selected = false;
					// Check if "all" is selected (explicitly chosen by user)
					if ( $y === 'all' && $year_input === 'all' ) {
						$is_selected = true;
					}
					// Check if specific year is selected
					elseif ( $y !== 'all' && $year !== null && (int) $y === $year && $year_input !== 'all' ) {
						$is_selected = true;
					}
					// Default: if no year_input and y is current year, select it
					elseif ( $y !== 'all' && $year_input === null && ! isset( $_GET['loads_year'] ) && (int) $y === $current_year ) {
						$is_selected = true;
					}
					?>
					<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $is_selected, true ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="col-12 col-md-3">
			<label for="loadsMonth" class="form-label fw-bold">Month:</label>
			<select class="form-select" id="loadsMonth" name="loads_month">
				<?php foreach ( $month_options as $m => $label ) : ?>
					<?php
					$is_selected = false;
					if ( $m === '' && $month === null ) {
						$is_selected = true;
					} elseif ( $m !== '' && $month !== null && (int) $m === $month ) {
						$is_selected = true;
					}
					?>
					<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $is_selected, true ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<?php if ( ! empty( $loads_chart_data ) ) : ?>
		<div class="col-12 mb-5 p-0">
			<div id="loadsByStateChart"
				 data-chart-data="<?php echo esc_attr( $loads_chart_json ); ?>"
				 data-initialized="false"
				 style="width:100%; height:800px;"></div>
		</div>
	<?php else : ?>
		<div class="alert alert-info">
			<p>No load statistics found for selected filters.</p>
		</div>
	<?php endif; ?>
</div>

