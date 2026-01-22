<?php
/**
 * Loads by Route Chart Component (Pickup → Delivery)
 *
 * Uses new locations tables and shipper table to build
 * statistics of loads by route (Pickup State → Delivery State).
 * Supports comparison of two periods.
 *
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$country = isset( $_GET['route_country'] )
	? sanitize_text_field( $_GET['route_country'] )
	: 'USA';

$country = strtoupper( $country );
$valid_countries = array( 'USA', 'CANADA', 'MEXICO', 'ALL' );
if ( ! in_array( $country, $valid_countries, true ) ) {
	$country = 'USA';
}

// Comparison mode
$compare_mode = isset( $_GET['route_compare'] ) && $_GET['route_compare'] === '1';

// Helper function to parse year/month from GET parameters
function parse_period_filter( $year_key, $month_key, $current_year, $min_year ) {
	$year_input = isset( $_GET[ $year_key ] ) ? sanitize_text_field( $_GET[ $year_key ] ) : null;
	$year = null;
	
	if ( $year_input === 'all' ) {
		$year = null;
	} elseif ( $year_input !== null && $year_input !== '' && is_numeric( $year_input ) ) {
		$year = intval( $year_input );
		if ( $year < $min_year ) {
			$year = $current_year;
		}
		if ( $year > $current_year ) {
			$year = $current_year;
		}
	} else {
		$year = $current_year;
	}
	
	$month = null;
	if ( isset( $_GET[ $month_key ] ) && $_GET[ $month_key ] !== '' ) {
		$month = intval( $_GET[ $month_key ] );
		if ( $month < 1 || $month > 12 ) {
			$month = null;
		}
	}
	
	return array( 'year' => $year, 'month' => $month );
}

$current_year = (int) date( 'Y' );
$min_year = 2024;

if ( $compare_mode ) {
	// Comparison mode: parse two periods
	$period1 = parse_period_filter( 'route_year1', 'route_month1', $current_year, $min_year );
	$period2 = parse_period_filter( 'route_year2', 'route_month2', $current_year, $min_year );
	
	// Get statistics for both periods
	$loads_route_results1 = $TMSDriversStatistics->get_loads_route_statistics( $country, $period1['year'], $period1['month'] );
	$loads_route_results2 = $TMSDriversStatistics->get_loads_route_statistics( $country, $period2['year'], $period2['month'] );
	
	// Prepare comparison data
	$comparison_data = array();
	$all_routes = array();
	
	// Index results by route key
	$results1_indexed = array();
	foreach ( $loads_route_results1 as $row ) {
		$key = $row['pickup_state'] . '|' . $row['pickup_country'] . '|' . $row['delivery_state'] . '|' . $row['delivery_country'];
		$results1_indexed[ $key ] = $row;
		// Store short label for table (TX → OK format)
		$pickup_state_short = $row['pickup_state'];
		$delivery_state_short = $row['delivery_state'];
		// Add country suffix if not USA
		if ( $row['pickup_country'] !== 'USA' && $row['pickup_country'] !== '' ) {
			$pickup_state_short .= ' (' . $row['pickup_country'] . ')';
		}
		if ( $row['delivery_country'] !== 'USA' && $row['delivery_country'] !== '' ) {
			$delivery_state_short .= ' (' . $row['delivery_country'] . ')';
		}
		$all_routes[ $key ] = array(
			'full_label' => $row['label'],
			'short_label' => $pickup_state_short . ' → ' . $delivery_state_short,
			'pickup_state' => $row['pickup_state'],
			'delivery_state' => $row['delivery_state'],
			'pickup_country' => $row['pickup_country'],
			'delivery_country' => $row['delivery_country'],
		);
	}
	
	$results2_indexed = array();
	foreach ( $loads_route_results2 as $row ) {
		$key = $row['pickup_state'] . '|' . $row['pickup_country'] . '|' . $row['delivery_state'] . '|' . $row['delivery_country'];
		$results2_indexed[ $key ] = $row;
		// Update short label if not already set
		if ( ! isset( $all_routes[ $key ] ) ) {
			$pickup_state_short = $row['pickup_state'];
			$delivery_state_short = $row['delivery_state'];
			// Add country suffix if not USA
			if ( $row['pickup_country'] !== 'USA' && $row['pickup_country'] !== '' ) {
				$pickup_state_short .= ' (' . $row['pickup_country'] . ')';
			}
			if ( $row['delivery_country'] !== 'USA' && $row['delivery_country'] !== '' ) {
				$delivery_state_short .= ' (' . $row['delivery_country'] . ')';
			}
			$all_routes[ $key ] = array(
				'full_label' => $row['label'],
				'short_label' => $pickup_state_short . ' → ' . $delivery_state_short,
				'pickup_state' => $row['pickup_state'],
				'delivery_state' => $row['delivery_state'],
				'pickup_country' => $row['pickup_country'],
				'delivery_country' => $row['delivery_country'],
			);
		}
	}
	
	// Build comparison array
	foreach ( $all_routes as $key => $route_info ) {
		$count1 = isset( $results1_indexed[ $key ] ) ? (int) $results1_indexed[ $key ]['count'] : 0;
		$count2 = isset( $results2_indexed[ $key ] ) ? (int) $results2_indexed[ $key ]['count'] : 0;
		
		$diff = $count2 - $count1;
		$diff_percent = $count1 > 0 ? ( ( $diff / $count1 ) * 100 ) : ( $count2 > 0 ? 100 : 0 );
		
		$comparison_data[] = array(
			'label' => $route_info['short_label'], // Use short label for table
			'full_label' => $route_info['full_label'], // Keep full label for charts
			'count1' => $count1,
			'count2' => $count2,
			'diff' => $diff,
			'diff_percent' => $diff_percent,
		);
	}
	
	// Sort by count2 (period 2) descending
	usort( $comparison_data, function( $a, $b ) {
		return $b['count2'] <=> $a['count2'];
	} );
	
	// Calculate summary statistics
	$total1 = array_sum( array_column( $comparison_data, 'count1' ) );
	$total2 = array_sum( array_column( $comparison_data, 'count2' ) );
	$total_diff = $total2 - $total1;
	$total_diff_percent = $total1 > 0 ? ( ( $total_diff / $total1 ) * 100 ) : ( $total2 > 0 ? 100 : 0 );
	
	// Prepare chart data for both periods (use short_label for charts to save space)
	$loads_chart_data1 = array();
	$loads_chart_data2 = array();
	foreach ( $comparison_data as $row ) {
		if ( $row['count1'] > 0 ) {
			$loads_chart_data1[] = array(
				'label' => esc_html( $row['label'] ), // Use short label for charts
				'value' => $row['count1'],
			);
		}
		if ( $row['count2'] > 0 ) {
			$loads_chart_data2[] = array(
				'label' => esc_html( $row['label'] ), // Use short label for charts
				'value' => $row['count2'],
			);
		}
	}
	
	$loads_chart_json1 = json_encode( $loads_chart_data1 );
	$loads_chart_json2 = json_encode( $loads_chart_data2 );
	$comparison_json = json_encode( $comparison_data );
	
	// Format period labels
	$period1_label = '';
	if ( $period1['year'] !== null ) {
		$period1_label = (string) $period1['year'];
		if ( $period1['month'] !== null ) {
			$month_names = array( '', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
			$period1_label = $month_names[ $period1['month'] ] . ' ' . $period1_label;
		}
	} else {
		$period1_label = 'All years';
		if ( $period1['month'] !== null ) {
			$month_names = array( '', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
			$period1_label = $month_names[ $period1['month'] ] . ' (all years)';
		}
	}
	
	$period2_label = '';
	if ( $period2['year'] !== null ) {
		$period2_label = (string) $period2['year'];
		if ( $period2['month'] !== null ) {
			$month_names = array( '', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
			$period2_label = $month_names[ $period2['month'] ] . ' ' . $period2_label;
		}
	} else {
		$period2_label = 'All years';
		if ( $period2['month'] !== null ) {
			$month_names = array( '', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
			$period2_label = $month_names[ $period2['month'] ] . ' (all years)';
		}
	}
	
} else {
	// Normal mode: single period
	$period = parse_period_filter( 'route_year', 'route_month', $current_year, $min_year );
	$loads_route_results = $TMSDriversStatistics->get_loads_route_statistics( $country, $period['year'], $period['month'] );
	
	// Prepare chart data for Google Bar Chart
	$loads_chart_data = array();
	foreach ( $loads_route_results as $row ) {
		if ( ! empty( $row['label'] ) && (int) $row['count'] > 0 ) {
			$loads_chart_data[] = array(
				'label' => esc_html( $row['label'] ),
				'value' => (int) $row['count'],
			);
		}
	}
	
	$loads_chart_json = json_encode( $loads_chart_data );
}

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
	<!-- Comparison Toggle -->
	<div class="row mb-3">
		<div class="col-12">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="routeCompareToggle" name="route_compare" value="1" <?php checked( $compare_mode, true ); ?>>
				<label class="form-check-label fw-bold" for="routeCompareToggle">
					Compare 2 periods
				</label>
			</div>
		</div>
	</div>
	
	<?php if ( $compare_mode ) : ?>
		<!-- Comparison Mode: Two Periods -->
		<div class="row mb-4">
			<div class="col-12 col-md-4">
				<label for="routeCountry" class="form-label fw-bold">Country:</label>
				<select class="form-select" id="routeCountry" name="route_country">
					<?php foreach ( $country_options as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		
		<div class="row mb-4">
			<div class="col-12">
				<h5 class="mb-3">Period 1</h5>
			</div>
			<div class="col-12 col-md-6">
				<label for="routeYear1" class="form-label fw-bold">Year:</label>
				<select class="form-select" id="routeYear1" name="route_year1">
					<?php foreach ( $year_options as $y => $label ) : ?>
						<?php
						$is_selected = false;
						if ( $y === 'all' && $period1['year'] === null ) {
							$is_selected = true;
						} elseif ( $y !== 'all' && $period1['year'] !== null && (int) $y === $period1['year'] ) {
							$is_selected = true;
						}
						?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $is_selected, true ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-md-6">
				<label for="routeMonth1" class="form-label fw-bold">Month:</label>
				<select class="form-select" id="routeMonth1" name="route_month1">
					<?php foreach ( $month_options as $m => $label ) : ?>
						<?php
						$is_selected = false;
						if ( $m === '' && $period1['month'] === null ) {
							$is_selected = true;
						} elseif ( $m !== '' && $period1['month'] !== null && (int) $m === $period1['month'] ) {
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
		
		<div class="row mb-4">
			<div class="col-12">
				<h5 class="mb-3">Period 2</h5>
			</div>
			<div class="col-12 col-md-6">
				<label for="routeYear2" class="form-label fw-bold">Year:</label>
				<select class="form-select" id="routeYear2" name="route_year2">
					<?php foreach ( $year_options as $y => $label ) : ?>
						<?php
						$is_selected = false;
						if ( $y === 'all' && $period2['year'] === null ) {
							$is_selected = true;
						} elseif ( $y !== 'all' && $period2['year'] !== null && (int) $y === $period2['year'] ) {
							$is_selected = true;
						}
						?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $is_selected, true ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-md-6">
				<label for="routeMonth2" class="form-label fw-bold">Month:</label>
				<select class="form-select" id="routeMonth2" name="route_month2">
					<?php foreach ( $month_options as $m => $label ) : ?>
						<?php
						$is_selected = false;
						if ( $m === '' && $period2['month'] === null ) {
							$is_selected = true;
						} elseif ( $m !== '' && $period2['month'] !== null && (int) $m === $period2['month'] ) {
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
		
		<!-- Summary Statistics -->
		<?php if ( ! empty( $comparison_data ) ) : ?>
			<div class="row mb-4">
				<div class="col-12">
					<div class="card">
						<div class="card-header bg-primary text-white">
							<h5 class="mb-0">Summary Comparison</h5>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-3">
									<strong>Period 1 (<?php echo esc_html( $period1_label ); ?>):</strong><br>
									<span class="fs-4"><?php echo number_format( $total1 ); ?></span> loads
								</div>
								<div class="col-md-3">
									<strong>Period 2 (<?php echo esc_html( $period2_label ); ?>):</strong><br>
									<span class="fs-4"><?php echo number_format( $total2 ); ?></span> loads
								</div>
								<div class="col-md-3">
									<strong>Difference:</strong><br>
									<span class="fs-4 <?php echo $total_diff >= 0 ? 'text-success' : 'text-danger'; ?>">
										<?php echo $total_diff >= 0 ? '+' : ''; ?><?php echo number_format( $total_diff ); ?>
									</span>
								</div>
								<div class="col-md-3">
									<strong>Change:</strong><br>
									<span class="fs-4 <?php echo $total_diff_percent >= 0 ? 'text-success' : 'text-danger'; ?>">
										<?php echo $total_diff_percent >= 0 ? '+' : ''; ?><?php echo number_format( $total_diff_percent, 1 ); ?>%
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Two Charts Side by Side -->
			<div class="row mb-5 g-3">
				<div class="col-12 col-md-6" style="padding-left: 15px; padding-right: 15px; box-sizing: border-box;">
					<h5 class="mb-3">Period 1: <?php echo esc_html( $period1_label ); ?></h5>
					<div id="loadsByRouteChart1"
						 data-chart-data="<?php echo esc_attr( $loads_chart_json1 ); ?>"
						 data-initialized="false"
						 style="width:100%; height:600px; position: relative; z-index: 1; max-width: 100%; box-sizing: border-box; overflow: auto;"></div>
				</div>
				<div class="col-12 col-md-6" style="padding-left: 15px; padding-right: 15px; box-sizing: border-box;">
					<h5 class="mb-3">Period 2: <?php echo esc_html( $period2_label ); ?></h5>
					<div id="loadsByRouteChart2"
						 data-chart-data="<?php echo esc_attr( $loads_chart_json2 ); ?>"
						 data-initialized="false"
						 style="width:100%; height:600px; position: relative; z-index: 1; max-width: 100%; box-sizing: border-box; overflow: auto;"></div>
				</div>
			</div>
			
			<!-- Comparison Table -->
			<div class="row mt-4">
				<div class="col-12">
					<h5 class="mb-3">Route Comparison</h5>
					<div class="table-responsive">
						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<th>Route</th>
									<th>Period 1</th>
									<th>Period 2</th>
									<th>Difference</th>
									<th>Change %</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $comparison_data as $row ) : ?>
									<?php if ( $row['count1'] > 0 || $row['count2'] > 0 ) : ?>
										<tr>
											<td><?php echo esc_html( $row['label'] ); ?></td>
											<td><?php echo number_format( $row['count1'] ); ?></td>
											<td><?php echo number_format( $row['count2'] ); ?></td>
											<td class="<?php echo $row['diff'] >= 0 ? 'text-success' : 'text-danger'; ?>">
												<?php echo $row['diff'] >= 0 ? '+' : ''; ?><?php echo number_format( $row['diff'] ); ?>
											</td>
											<td class="<?php echo $row['diff_percent'] >= 0 ? 'text-success' : 'text-danger'; ?>">
												<?php echo $row['diff_percent'] >= 0 ? '+' : ''; ?><?php echo number_format( $row['diff_percent'], 1 ); ?>%
											</td>
										</tr>
									<?php endif; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="alert alert-info">
				<p>No load route statistics found for selected periods.</p>
			</div>
		<?php endif; ?>
		
	<?php else : ?>
		<!-- Normal Mode: Single Period -->
		<div class="row mb-4">
			<div class="col-12 col-md-4">
				<label for="routeCountry" class="form-label fw-bold">Country:</label>
				<select class="form-select" id="routeCountry" name="route_country">
					<?php foreach ( $country_options as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-md-4">
				<label for="routeYear" class="form-label fw-bold">Year:</label>
				<select class="form-select" id="routeYear" name="route_year">
					<?php foreach ( $year_options as $y => $label ) : ?>
						<?php
						$is_selected = false;
						$year_input = isset( $_GET['route_year'] ) ? sanitize_text_field( $_GET['route_year'] ) : null;
						if ( $y === 'all' && $year_input === 'all' ) {
							$is_selected = true;
						} elseif ( $y !== 'all' && $period['year'] !== null && (int) $y === $period['year'] && $year_input !== 'all' ) {
							$is_selected = true;
						} elseif ( $y !== 'all' && $year_input === null && ! isset( $_GET['route_year'] ) && (int) $y === $current_year ) {
							$is_selected = true;
						}
						?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $is_selected, true ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-md-4">
				<label for="routeMonth" class="form-label fw-bold">Month:</label>
				<select class="form-select" id="routeMonth" name="route_month">
					<?php foreach ( $month_options as $m => $label ) : ?>
						<?php
						$is_selected = false;
						if ( $m === '' && $period['month'] === null ) {
							$is_selected = true;
						} elseif ( $m !== '' && $period['month'] !== null && (int) $m === $period['month'] ) {
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
				<div id="loadsByRouteChart"
					 data-chart-data="<?php echo esc_attr( $loads_chart_json ); ?>"
					 data-initialized="false"
					 style="width:100%; height:800px;"></div>
			</div>
		<?php else : ?>
			<div class="alert alert-info">
				<p>No load route statistics found for selected filters.</p>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
