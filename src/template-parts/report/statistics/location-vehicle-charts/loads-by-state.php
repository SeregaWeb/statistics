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
// Ensure TMSDriversStatistics is available (should be defined in drivers.php)
if ( ! isset( $TMSDriversStatistics ) || ! is_object( $TMSDriversStatistics ) ) {
	$TMSDriversStatistics = new TMSDriversStatistics();
}
$loads_state_results = $TMSDriversStatistics->get_loads_state_statistics( $location_type, $country, $year, $month );

// Prepare chart data for Google Bar Chart
$loads_chart_data = array();
$loads_state_map_data = array(); // For map visualization
foreach ( $loads_state_results as $row ) {
	if ( ! empty( $row['label'] ) && (int) $row['count'] > 0 ) {
		$loads_chart_data[] = array(
			'label' => esc_html( $row['label'] ),
			'value' => (int) $row['count'],
		);
		
		// Prepare data for map (state abbreviation as key, only for USA)
		if ( $row['country'] === 'USA' && ! empty( $row['state'] ) ) {
			$state_abbr = strtoupper( trim( $row['state'] ) );
			$loads_state_map_data[ $state_abbr ] = array(
				'name' => esc_html( $row['label'] ),
				'count' => (int) $row['count'],
			);
		}
	}
}

$loads_chart_json = json_encode( $loads_chart_data );
$loads_state_map_json = json_encode( $loads_state_map_data );

// US States center coordinates (approximate centroids) - same as in home-location.php
$us_state_centers = array(
	'AL' => array('lat' => 32.806671, 'lng' => -86.791130), // Alabama
	'AK' => array('lat' => 61.370716, 'lng' => -152.404419), // Alaska
	'AZ' => array('lat' => 33.729759, 'lng' => -111.431221), // Arizona
	'AR' => array('lat' => 34.969704, 'lng' => -92.373123), // Arkansas
	'CA' => array('lat' => 36.116203, 'lng' => -119.681564), // California
	'CO' => array('lat' => 39.059811, 'lng' => -105.311104), // Colorado
	'CT' => array('lat' => 41.597782, 'lng' => -72.755371), // Connecticut
	'DE' => array('lat' => 39.318523, 'lng' => -75.507141), // Delaware
	'FL' => array('lat' => 27.766279, 'lng' => -81.686783), // Florida
	'GA' => array('lat' => 33.040619, 'lng' => -83.643074), // Georgia
	'HI' => array('lat' => 21.094318, 'lng' => -157.498337), // Hawaii
	'ID' => array('lat' => 44.240459, 'lng' => -114.478828), // Idaho
	'IL' => array('lat' => 40.349457, 'lng' => -88.986137), // Illinois
	'IN' => array('lat' => 39.849426, 'lng' => -86.258278), // Indiana
	'IA' => array('lat' => 42.011539, 'lng' => -93.210526), // Iowa
	'KS' => array('lat' => 38.526600, 'lng' => -96.726486), // Kansas
	'KY' => array('lat' => 37.668140, 'lng' => -84.670067), // Kentucky
	'LA' => array('lat' => 31.169546, 'lng' => -91.867805), // Louisiana
	'ME' => array('lat' => 44.323535, 'lng' => -69.765261), // Maine
	'MD' => array('lat' => 39.063946, 'lng' => -76.802101), // Maryland
	'MA' => array('lat' => 42.230171, 'lng' => -71.530106), // Massachusetts
	'MI' => array('lat' => 43.326618, 'lng' => -84.536095), // Michigan
	'MN' => array('lat' => 45.694454, 'lng' => -93.900192), // Minnesota
	'MS' => array('lat' => 32.741646, 'lng' => -89.678696), // Mississippi
	'MO' => array('lat' => 38.456085, 'lng' => -92.288368), // Missouri
	'MT' => array('lat' => 46.921925, 'lng' => -110.454353), // Montana
	'NE' => array('lat' => 41.125370, 'lng' => -98.268082), // Nebraska
	'NV' => array('lat' => 38.313515, 'lng' => -117.055374), // Nevada
	'NH' => array('lat' => 43.452492, 'lng' => -71.563896), // New Hampshire
	'NJ' => array('lat' => 40.298904, 'lng' => -74.521011), // New Jersey
	'NM' => array('lat' => 34.840515, 'lng' => -106.248482), // New Mexico
	'NY' => array('lat' => 42.165726, 'lng' => -74.948051), // New York
	'NC' => array('lat' => 35.630066, 'lng' => -79.806419), // North Carolina
	'ND' => array('lat' => 47.528912, 'lng' => -99.784012), // North Dakota
	'OH' => array('lat' => 40.388783, 'lng' => -82.764915), // Ohio
	'OK' => array('lat' => 35.565342, 'lng' => -96.928917), // Oklahoma
	'OR' => array('lat' => 44.572021, 'lng' => -122.070938), // Oregon
	'PA' => array('lat' => 40.590752, 'lng' => -77.209755), // Pennsylvania
	'RI' => array('lat' => 41.680893, 'lng' => -71.51178), // Rhode Island
	'SC' => array('lat' => 33.856892, 'lng' => -80.945007), // South Carolina
	'SD' => array('lat' => 44.299782, 'lng' => -99.438828), // South Dakota
	'TN' => array('lat' => 35.747845, 'lng' => -86.692345), // Tennessee
	'TX' => array('lat' => 31.054487, 'lng' => -97.563461), // Texas
	'UT' => array('lat' => 40.150032, 'lng' => -111.862434), // Utah
	'VT' => array('lat' => 44.045876, 'lng' => -72.710686), // Vermont
	'VA' => array('lat' => 37.769337, 'lng' => -78.169968), // Virginia
	'WA' => array('lat' => 47.400902, 'lng' => -121.490494), // Washington
	'WV' => array('lat' => 38.491226, 'lng' => -80.954453), // West Virginia
	'WI' => array('lat' => 44.268543, 'lng' => -89.616508), // Wisconsin
	'WY' => array('lat' => 42.755966, 'lng' => -107.302490), // Wyoming
	'DC' => array('lat' => 38.907192, 'lng' => -77.036873)  // District of Columbia
);

// Prepare state markers data (grouped by state)
$loads_state_markers_data = array();
foreach ( $loads_state_map_data as $state_abbr => $state_info ) {
	if ( isset( $us_state_centers[ $state_abbr ] ) && $state_info['count'] > 0 ) {
		$loads_state_markers_data[] = array(
			'state' => $state_abbr,
			'name' => $state_info['name'],
			'count' => $state_info['count'],
			'lat' => $us_state_centers[ $state_abbr ]['lat'],
			'lng' => $us_state_centers[ $state_abbr ]['lng']
		);
	}
}
$loads_state_markers_json = json_encode( $loads_state_markers_data );

// Find max count for color scaling
$loads_max_count = 0;
if ( ! empty( $loads_state_map_data ) ) {
	$loads_max_count = max( array_column( $loads_state_map_data, 'count' ) );
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

<div class="col-12 mb-3">
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
		<!-- Tabs Navigation -->
		<ul class="nav nav-pills home-location-view-tabs mb-4" id="loadsByStateTabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" 
						id="loads-map-tab" 
						data-bs-toggle="tab" 
						data-bs-target="#loads-map-view" 
						type="button" 
						role="tab" 
						aria-controls="loads-map-view" 
						aria-selected="true">
					Map
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" 
						id="loads-list-tab" 
						data-bs-toggle="tab" 
						data-bs-target="#loads-list-view" 
						type="button" 
						role="tab" 
						aria-controls="loads-list-view" 
						aria-selected="false">
					List
				</button>
			</li>
		</ul>

		<!-- Tab Content -->
		<div class="tab-content" id="loadsByStateTabContent">
			<!-- Map Tab -->
			<div class="tab-pane fade show active" 
				 id="loads-map-view" 
				 role="tabpanel" 
				 aria-labelledby="loads-map-tab">
				<?php if ( ! empty( $loads_state_map_data ) ) : ?>
					<div class="row w-100">
						<div class="col-12 mb-5">
							<div id="loadsByStateMap" 
								 class="home-location-map-container"
								 data-state-map-data="<?php echo esc_attr( $loads_state_map_json ); ?>"
								 data-max-count="<?php echo esc_attr( $loads_max_count ); ?>"
								 data-state-markers-data="<?php echo esc_attr( $loads_state_markers_json ); ?>"
								 data-geojson-source="<?php echo esc_url( THEME_URI . '/src/data/us-states.json' ); ?>"
								 style="width:100%; min-width:100%; height:600px;">
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="alert alert-info">
						<p>No map data available for selected filters (map only shows USA states).</p>
					</div>
				<?php endif; ?>
			</div>

			<!-- List Tab -->
			<div class="tab-pane fade" 
				 id="loads-list-view" 
				 role="tabpanel" 
				 aria-labelledby="loads-list-tab">
				<div class="row w-100">
					<div class="col-12 mb-5">
						<div id="loadsByStateChart"
							 data-chart-data="<?php echo esc_attr( $loads_chart_json ); ?>"
							 data-initialized="false"
							 style="width:100%; min-width:100%; height:800px;"></div>
					</div>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="alert alert-info">
			<p>No load statistics found for selected filters.</p>
		</div>
	<?php endif; ?>
</div>

<?php if ( ! empty( $loads_chart_data ) && ! empty( $loads_state_map_data ) ) : ?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="<?php echo THEME_URI; ?>/src/css/libs/leaflet/leaflet.css" />

<!-- Leaflet JS -->
<script src="<?php echo LIBS_JS; ?>leaflet/leaflet.js"></script>

<script>
(function() {
	// Initialize map when Map tab is shown
	const loadsMapTab = document.getElementById('loads-map-tab');
	if (loadsMapTab) {
		loadsMapTab.addEventListener('shown.bs.tab', function() {
			const mapElement = document.getElementById('loadsByStateMap');
			if (mapElement && !mapElement.dataset.mapInitialized) {
				const stateMapData = mapElement.dataset.stateMapData;
				const maxCount = mapElement.dataset.maxCount;
				const stateMarkersData = mapElement.dataset.stateMarkersData;
				const geojsonSource = mapElement.dataset.geojsonSource;
				
				if (stateMapData && maxCount && stateMarkersData && geojsonSource && typeof window.initHomeLocationMap === 'function') {
					try {
						window.initHomeLocationMap(
							JSON.parse(stateMapData),
							parseInt(maxCount, 10),
							JSON.parse(stateMarkersData),
							geojsonSource,
							'loadsByStateMap',
							'loadsByStateMapInfoPanel'
						);
						mapElement.dataset.mapInitialized = 'true';
					} catch (e) {
						console.error('Error initializing loads by state map:', e);
					}
				}
			}
		});
	}
	
	// Handle tab switching for loads by state - initialize chart when List tab is shown
	const loadsListTab = document.getElementById('loads-list-tab');
	if (loadsListTab) {
		loadsListTab.addEventListener('shown.bs.tab', function() {
			const chartElement = document.getElementById('loadsByStateChart');
			if (chartElement) {
				// Reset initialization flag
				chartElement.dataset.initialized = 'false';
				
				// Wait for tab transition to complete and container to be fully visible
				setTimeout(function() {
					// Initialize chart using the global function if available
					if (typeof window.initDriversChart === 'function') {
						window.initDriversChart('loadsByStateChart', true);
					} else if (typeof window.initDriversStatisticsCharts === 'function') {
						window.initDriversStatisticsCharts();
					}
				}, 300);
			}
		});
	}
	
	// Initialize map immediately if Map tab is already active on page load
	function initLoadsByStateMapIfVisible() {
		const mapView = document.getElementById('loads-map-view');
		const mapElement = document.getElementById('loadsByStateMap');
		
		if (mapView && mapElement && !mapElement.dataset.mapInitialized) {
			// Check if tab is visible (has 'active' class or is in visible tab pane)
			const isVisible = mapView.classList.contains('active') && 
			                  mapView.classList.contains('show') &&
			                  mapView.offsetParent !== null;
			
			if (isVisible) {
				const stateMapData = mapElement.dataset.stateMapData;
				const maxCount = mapElement.dataset.maxCount;
				const stateMarkersData = mapElement.dataset.stateMarkersData;
				const geojsonSource = mapElement.dataset.geojsonSource;
				
				if (stateMapData && maxCount && stateMarkersData && geojsonSource) {
					// Wait for initHomeLocationMap to be available
					const checkInit = function() {
						if (typeof window.initHomeLocationMap === 'function') {
							try {
								window.initHomeLocationMap(
									JSON.parse(stateMapData),
									parseInt(maxCount, 10),
									JSON.parse(stateMarkersData),
									geojsonSource,
									'loadsByStateMap',
									'loadsByStateMapInfoPanel'
								);
								mapElement.dataset.mapInitialized = 'true';
							} catch (e) {
								console.error('Error initializing loads by state map:', e);
							}
						} else {
							setTimeout(checkInit, 100);
						}
					};
					checkInit();
				}
			}
		}
	}
	
	// Try to initialize on page load
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			setTimeout(initLoadsByStateMapIfVisible, 500);
		});
	} else {
		setTimeout(initLoadsByStateMapIfVisible, 500);
	}
})();
</script>
<?php endif; ?>

