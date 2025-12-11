<?php
/**
 * Drivers Statistics Template
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$TMSReports = new TMSReports();
$TMSDrivers = new TMSDrivers();
$TMSUsers   = new TMSUsers();
$TMSDriversHelper = new TMSDriversHelper();
$TMSReportsHelper = new TMSReportsHelper();
$TMSDriversStatistics = new TMSDriversStatistics();

global $global_options;

$empty_recruiter = get_field_value( $global_options, 'empty_recruiter' );

// Get active tab from GET parameter (default: recruiters-chart)
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'recruiters-chart';
$valid_tabs = array( 'recruiters-chart', 'location-vehicle', 'capabilities' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
	$active_tab = 'recruiters-chart';
}

// Initialize variables for lazy loading
$statistics = array();
$state_results = array();
$nationality_results = array();
$language_counts = array();
$total_drivers = 0;

// Load data only for active tab
if ( $active_tab === 'recruiters-chart' ) {
	// Load statistics for recruiters chart only
	$statistics = $TMSDrivers->get_statistics();
} elseif ( $active_tab === 'location-vehicle' ) {
	// Load data for location-vehicle tab
	$statistics = $TMSDrivers->get_statistics(); // Needed for vehicle type calculation
	$state_results = $TMSDriversStatistics->get_state_statistics();
	$nationality_results = $TMSDriversStatistics->get_nationality_statistics();
	$language_counts = $TMSDriversStatistics->get_language_statistics();
} elseif ( $active_tab === 'capabilities' ) {
	// Load data for capabilities tab
	$statistics = $TMSDrivers->get_statistics(); // Needed for totals calculation
	$total_drivers = $TMSDriversStatistics->get_total_drivers_count();
}

// Auto-move drivers from non-existent recruiters to OR
if ( is_array( $statistics ) && ! empty( $statistics ) ) {
	foreach ( $statistics as $key => $statistic ) {
		if ( isset( $statistic['user_id_added'] ) && ! empty( $statistic['user_id_added'] ) ) {
			$user = $TMSUsers->get_user_full_name_by_id( $statistic['user_id_added'] );
			
			// If user not found, move drivers to OR in background
			if ( ! $user || ( isset( $user['full_name'] ) && $user['full_name'] === 'User not found' ) ) {
				// Move drivers to OR silently
				$result = $TMSDrivers->move_driver_for_new_recruiter( $statistic['user_id_added'] );
				
				// Remove this statistic from the array so it won't be displayed
				unset( $statistics[ $key ] );
			}
		}
	}
	
	// Re-index array after unset
	$statistics = array_values( $statistics );
}

// Calculate totals from statistics (needed for location-vehicle and capabilities tabs)
$totals = array();
if ( $active_tab === 'location-vehicle' || $active_tab === 'capabilities' ) {
	$totals = $TMSDriversStatistics->calculate_totals( $statistics );
} else {
	// Initialize empty totals for recruiters-chart tab
	$totals = $TMSDriversStatistics->calculate_totals( array() );
}

// Extract totals to individual variables for backward compatibility
extract( $totals );

?>

<div class="row w-100 statistics-styles">
	<div class="col-12">
		<h2 class="mb-3">HR</h2>
	</div>
	
	<?php if ( is_array( $statistics ) && ! empty( $statistics ) ) : ?>
		
		<?php
		// Sort statistics by total (descending), but put empty_recruiter at the end
		usort( $statistics, function( $a, $b ) use ( $empty_recruiter ) {
			$a_id = isset( $a['user_id_added'] ) ? (int) $a['user_id_added'] : 0;
			$b_id = isset( $b['user_id_added'] ) ? (int) $b['user_id_added'] : 0;
			
			// If empty_recruiter is set, check if either item is the empty recruiter
			if ( ! empty( $empty_recruiter ) ) {
				$a_is_empty = ( $a_id === (int) $empty_recruiter );
				$b_is_empty = ( $b_id === (int) $empty_recruiter );
				
				// If one is empty recruiter and the other is not, empty recruiter goes to the end
				if ( $a_is_empty && ! $b_is_empty ) {
					return 1; // a goes after b
				}
				if ( ! $a_is_empty && $b_is_empty ) {
					return -1; // a goes before b
				}
				// If both are empty recruiter or both are not, sort by total
			}
			
			$total_a = isset( $a['total'] ) ? (int) $a['total'] : 0;
			$total_b = isset( $b['total'] ) ? (int) $b['total'] : 0;
			return $total_b - $total_a;
		} );
		?>
		
		<!-- Subsection Navigation -->
		<ul class="nav nav-tabs mt-4" id="driversStatisticsTabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $active_tab === 'recruiters-chart' ? 'active' : ''; ?>" 
						id="recruiters-chart-tab" 
						data-tab-name="recruiters-chart"
						type="button" 
						role="tab" 
						aria-controls="recruiters-chart" 
						aria-selected="<?php echo $active_tab === 'recruiters-chart' ? 'true' : 'false'; ?>">
					Recruiters chart
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $active_tab === 'location-vehicle' ? 'active' : ''; ?>" 
						id="location-vehicle-tab" 
						data-tab-name="location-vehicle"
						type="button" 
						role="tab" 
						aria-controls="location-vehicle" 
						aria-selected="<?php echo $active_tab === 'location-vehicle' ? 'true' : 'false'; ?>">
					Home location & vehicle type
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $active_tab === 'capabilities' ? 'active' : ''; ?>" 
						id="capabilities-tab" 
						data-tab-name="capabilities"
						type="button" 
						role="tab" 
						aria-controls="capabilities" 
						aria-selected="<?php echo $active_tab === 'capabilities' ? 'true' : 'false'; ?>">
					Capabilities & Endorsements
				</button>
			</li>
		</ul>

		<!-- Tab Content -->
		<div class="tab-content mt-3" id="driversStatisticsTabContent">
			<!-- Recruiters Chart Tab (Default) -->
			<div class="tab-pane fade <?php echo $active_tab === 'recruiters-chart' ? 'show active' : ''; ?>" id="recruiters-chart" role="tabpanel" aria-labelledby="recruiters-chart-tab">
				<?php include get_template_directory() . '/src/template-parts/report/statistics/recruiters-chart.php'; ?>
			</div> <!-- End Recruiters Chart Tab -->

			<!-- Home location & Vehicle type Tab -->
			<div class="tab-pane fade <?php echo $active_tab === 'location-vehicle' ? 'show active' : ''; ?>" id="location-vehicle" role="tabpanel" aria-labelledby="location-vehicle-tab">
				<?php include get_template_directory() . '/src/template-parts/report/statistics/location-vehicle.php'; ?>
			</div> <!-- End Home location & Vehicle type Tab -->

			<!-- Capabilities & Endorsements Tab -->
			<div class="tab-pane fade <?php echo $active_tab === 'capabilities' ? 'show active' : ''; ?>" id="capabilities" role="tabpanel" aria-labelledby="capabilities-tab">
				<?php include get_template_directory() . '/src/template-parts/report/statistics/capabilities.php'; ?>
			</div> <!-- End Capabilities & Endorsements Tab -->
		</div> <!-- End Tab Content -->
	<?php endif; ?>
</div>
