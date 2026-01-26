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
$valid_tabs = array( 'recruiters-chart', 'location-vehicle', 'loads-by-state', 'loads-by-route', 'capabilities', 'expired-documents' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
	$active_tab = 'recruiters-chart';
}

// Initialize variables for lazy loading
$statistics = array();
$state_results = array();
$nationality_results = array();
$language_counts = array();
$total_drivers = 0;
$expired_documents_stats = array();
$usa_drivers_with_coords = array();

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
	$usa_drivers_with_coords = $TMSDriversStatistics->get_usa_drivers_with_coordinates();
} elseif ( $active_tab === 'loads-by-state' || $active_tab === 'loads-by-route' ) {
	// Loads by State and Loads by Route tabs load their own data in their included files
	// No additional data loading needed here - files handle it themselves
} elseif ( $active_tab === 'capabilities' ) {
	// Load data for capabilities tab
	$statistics = $TMSDrivers->get_statistics(); // Needed for totals calculation
	$total_drivers = $TMSDriversStatistics->get_total_drivers_count();
} elseif ( $active_tab === 'expired-documents' ) {
	// Load data for expired documents tab
	$statistics = $TMSDrivers->get_statistics(); // Needed for totals calculation
	$total_drivers = $TMSDriversStatistics->get_total_drivers_count();
	$expired_documents_stats = $TMSDriversStatistics->get_expired_documents_statistics();
}


$access_expired_documents = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
], true );
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
	
	<?php 
	if ( is_array( $statistics ) && ! empty( $statistics ) ) {
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
	}
	?>
	
	<!-- Subsection Navigation - Always show for all tabs -->
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
				<button class="nav-link <?php echo $active_tab === 'loads-by-state' ? 'active' : ''; ?>" 
						id="loads-by-state-tab" 
						data-tab-name="loads-by-state"
						type="button" 
						role="tab" 
						aria-controls="loads-by-state" 
						aria-selected="<?php echo $active_tab === 'loads-by-state' ? 'true' : 'false'; ?>">
					Loads by State
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $active_tab === 'loads-by-route' ? 'active' : ''; ?>" 
						id="loads-by-route-tab" 
						data-tab-name="loads-by-route"
						type="button" 
						role="tab" 
						aria-controls="loads-by-route" 
						aria-selected="<?php echo $active_tab === 'loads-by-route' ? 'true' : 'false'; ?>">
					Loads by Route
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

			<?php if ( $access_expired_documents ) : ?>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $active_tab === 'expired-documents' ? 'active' : ''; ?>" 
						id="expired-documents-tab" 
						data-tab-name="expired-documents"
						type="button" 
						role="tab" 
						aria-controls="expired-documents" 
						aria-selected="<?php echo $active_tab === 'expired-documents' ? 'true' : 'false'; ?>">
						Expired Documents
					</button>
				</li>
			<?php endif; ?>
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

			<!-- Loads by State Tab -->
			<div class="tab-pane fade <?php echo $active_tab === 'loads-by-state' ? 'show active' : ''; ?>" id="loads-by-state" role="tabpanel" aria-labelledby="loads-by-state-tab">
				<div class="row">
					<?php 
					try {
						$loads_by_state_file = get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/loads-by-state.php';
						if ( file_exists( $loads_by_state_file ) ) {
							// Ensure TMSDriversStatistics is available for included file
							if ( ! isset( $TMSDriversStatistics ) || ! is_object( $TMSDriversStatistics ) ) {
								$TMSDriversStatistics = new TMSDriversStatistics();
							}
							include $loads_by_state_file;
						} else {
							echo '<div class="col-12"><div class="alert alert-danger">Loads by State chart file not found: ' . esc_html( $loads_by_state_file ) . '</div></div>';
						}
					} catch ( Exception $e ) {
						echo '<div class="col-12"><div class="alert alert-danger">Error loading Loads by State chart: ' . esc_html( $e->getMessage() ) . '</div></div>';
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Loads by State chart error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString() );
						}
					}
					?>
				</div>
			</div> <!-- End Loads by State Tab -->

			<!-- Loads by Route Tab -->
			<div class="tab-pane fade <?php echo $active_tab === 'loads-by-route' ? 'show active' : ''; ?>" id="loads-by-route" role="tabpanel" aria-labelledby="loads-by-route-tab">
				<div class="row">
					<?php 
					try {
						$loads_by_route_file = get_template_directory() . '/src/template-parts/report/statistics/location-vehicle-charts/loads-by-route.php';
						if ( file_exists( $loads_by_route_file ) ) {
							// Ensure TMSDriversStatistics is available for included file
							if ( ! isset( $TMSDriversStatistics ) || ! is_object( $TMSDriversStatistics ) ) {
								$TMSDriversStatistics = new TMSDriversStatistics();
							}
							include $loads_by_route_file;
						} else {
							echo '<div class="col-12"><div class="alert alert-danger">Loads by Route chart file not found: ' . esc_html( $loads_by_route_file ) . '</div></div>';
						}
					} catch ( Exception $e ) {
						echo '<div class="col-12"><div class="alert alert-danger">Error loading Loads by Route chart: ' . esc_html( $e->getMessage() ) . '</div></div>';
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Loads by Route chart error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString() );
						}
					}
					?>
				</div>
			</div> <!-- End Loads by Route Tab -->

			<!-- Capabilities & Endorsements Tab -->
			<div class="tab-pane fade <?php echo $active_tab === 'capabilities' ? 'show active' : ''; ?>" id="capabilities" role="tabpanel" aria-labelledby="capabilities-tab">
				<?php include get_template_directory() . '/src/template-parts/report/statistics/capabilities.php'; ?>
			</div> <!-- End Capabilities & Endorsements Tab -->

			<?php if ( $access_expired_documents ) : ?>
			<!-- Expired Documents Tab -->
			<div class="tab-pane fade <?php echo $active_tab === 'expired-documents' ? 'show active' : ''; ?>" id="expired-documents" role="tabpanel" aria-labelledby="expired-documents-tab">
				<?php include get_template_directory() . '/src/template-parts/report/statistics/expired-documents.php'; ?>
			</div> <!-- End Expired Documents Tab -->
			<?php endif; ?>
		</div> <!-- End Tab Content -->
</div>
