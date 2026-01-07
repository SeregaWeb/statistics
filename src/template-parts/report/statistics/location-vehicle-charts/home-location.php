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
$state_map_data = array(); // For map visualization
foreach ( $state_results as $state_row ) {
	if ( ! empty( $state_row['state'] ) && (int) $state_row['count'] > 0 ) {
		$state_label = $TMSDriversStatistics->get_state_label( $state_row['state'] );
		
		$state_chart_data[] = array( 
			'label' => esc_html( $state_label ), 
			'value' => (int) $state_row['count'] 
		);
		
		// Prepare data for map (state abbreviation as key)
		$state_abbr = strtoupper( trim( $state_row['state'] ) );
		$state_map_data[ $state_abbr ] = array(
			'name' => $state_label,
			'count' => (int) $state_row['count']
		);
	}
}
$state_chart_json = json_encode( $state_chart_data );
$state_map_json = json_encode( $state_map_data );

// Prepare drivers data grouped by state (using state_map_data which already has counts)
// US States center coordinates (approximate centroids)
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
$state_markers_data = array();
foreach ( $state_map_data as $state_abbr => $state_info ) {
	if ( isset( $us_state_centers[ $state_abbr ] ) && $state_info['count'] > 0 ) {
		$state_markers_data[] = array(
			'state' => $state_abbr,
			'name' => $state_info['name'],
			'count' => $state_info['count'],
			'lat' => $us_state_centers[ $state_abbr ]['lat'],
			'lng' => $us_state_centers[ $state_abbr ]['lng']
		);
	}
}
$state_markers_json = json_encode( $state_markers_data );

// Find max count for color scaling
$max_count = 0;
if ( ! empty( $state_map_data ) ) {
	$max_count = max( array_column( $state_map_data, 'count' ) );
}

?>

<?php if ( ! empty( $state_chart_data ) ) : ?>
	<!-- Tabs Navigation -->
	<ul class="nav nav-pills home-location-view-tabs mb-4" id="homeLocationTabs" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" 
					id="map-tab" 
					data-bs-toggle="tab" 
					data-bs-target="#map-view" 
					type="button" 
					role="tab" 
					aria-controls="map-view" 
					aria-selected="true">
				Map
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" 
					id="list-tab" 
					data-bs-toggle="tab" 
					data-bs-target="#list-view" 
					type="button" 
					role="tab" 
					aria-controls="list-view" 
					aria-selected="false">
				List
			</button>
		</li>
	</ul>

	<!-- Tab Content -->
	<div class="tab-content" id="homeLocationTabContent">
		<!-- Map Tab -->
		<div class="tab-pane fade show active" 
			 id="map-view" 
			 role="tabpanel" 
			 aria-labelledby="map-tab">
			<div class="col-12 mb-5 p-0">
				<div id="usaStatesMap" 
					 class="home-location-map-container"
					 data-state-map-data="<?php echo esc_attr( $state_map_json ); ?>"
					 data-max-count="<?php echo esc_attr( $max_count ); ?>"
					 data-state-markers-data="<?php echo esc_attr( $state_markers_json ); ?>"
					 data-geojson-source="<?php echo esc_url( THEME_URI . '/src/data/us-states.json' ); ?>">
				</div>
			</div>
		</div>

		<!-- List Tab -->
		<div class="tab-pane fade" 
			 id="list-view" 
			 role="tabpanel" 
			 aria-labelledby="list-tab">
			<div class="col-12 mb-5 p-0">
				<div id="stateChart" 
					 data-chart-data="<?php echo esc_attr( $state_chart_json ); ?>" 
					 style="width:100%; height:1000px;"></div>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="<?php echo THEME_URI; ?>/src/css/libs/leaflet/leaflet.css" />

<!-- Leaflet JS -->
<script src="<?php echo LIBS_JS; ?>leaflet/leaflet.js"></script>

<script>
(function() {
	// Handle tab switching for home location - initialize chart when List tab is shown
	const listTab = document.getElementById('list-tab');
	if (listTab) {
		listTab.addEventListener('shown.bs.tab', function() {
			const chartElement = document.getElementById('stateChart');
			if (chartElement) {
				// Reset initialization flag
				chartElement.dataset.initialized = 'false';
				
				// Wait for tab transition to complete and container to be fully visible
				setTimeout(function() {
					// Initialize chart using the global function if available
					if (typeof window.initDriversChart === 'function') {
						window.initDriversChart('stateChart', true);
					} else if (typeof window.initDriversStatisticsCharts === 'function') {
						window.initDriversStatisticsCharts();
					}
				}, 300);
			}
		});
	}
})();
</script>

