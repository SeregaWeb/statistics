<?php
/**
 * Admin page for importing location data from JSON to new tables
 * Access via ?location-import=1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check GET parameter
if ( ! isset( $_GET['location-import'] ) || $_GET['location-import'] != '1' ) {
	return;
}

// Check if user has permission
if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Administrator only.' );
}

// Create instances only when page is accessed
$reports = new TMSReports();
$reports_flt = new TMSReportsFlt();
$drivers_statistics = new TMSDriversStatistics();

// Handle actions
$action_notice = '';
$import_stats = array();
$index_results = array();

if ( isset( $_GET['action'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'location_import_action' ) ) {
	if ( $_GET['action'] === 'create_indexes' ) {
		$index_results = $drivers_statistics->add_route_statistics_indexes();
		$index_count = 0;
		foreach ( $index_results as $result ) {
			$index_count += count( $result['indexes_added'] );
		}
		if ( $index_count > 0 ) {
			$action_notice = '<div class="notice notice-success"><p>Successfully created ' . $index_count . ' index(es) for route statistics optimization.</p></div>';
		} else {
			$action_notice = '<div class="notice notice-info"><p>All indexes already exist. No new indexes were created.</p></div>';
		}
	} elseif ( $_GET['action'] === 'import_batch' ) {
		$project = isset( $_GET['project'] ) ? sanitize_text_field( $_GET['project'] ) : '';
		$is_flt = isset( $_GET['is_flt'] ) && $_GET['is_flt'] === '1';
		$batch_size = isset( $_GET['batch_size'] ) ? absint( $_GET['batch_size'] ) : 50;
		
		if ( $is_flt ) {
			$import_stats = $reports_flt->import_locations_from_json( $project, $batch_size );
		} else {
			$import_stats = $reports->import_locations_from_json( $project, $batch_size );
		}
		
		if ( $import_stats['success'] ) {
			$action_notice = '<div class="notice notice-success"><p>Batch imported successfully. Processed: ' . $import_stats['processed'] . ', Imported: ' . $import_stats['imported'] . ', Skipped: ' . $import_stats['skipped'] . '</p></div>';
		} else {
			$action_notice = '<div class="notice notice-error"><p>Error: ' . esc_html( $import_stats['message'] ) . '</p></div>';
		}
	} elseif ( $_GET['action'] === 'get_stats' ) {
		$project = isset( $_GET['project'] ) ? sanitize_text_field( $_GET['project'] ) : '';
		$is_flt = isset( $_GET['is_flt'] ) && $_GET['is_flt'] === '1';
		
		if ( $is_flt ) {
			$import_stats = $reports_flt->get_location_import_stats( $project );
		} else {
			$import_stats = $reports->get_location_import_stats( $project );
		}
	}
}

// Get all projects
$projects = array( 'Odysseia', 'Martlet', 'Endurance' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Location Data Import</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 20px;
			background: #f0f0f1;
		}
		.container {
			max-width: 1400px;
			margin: 0 auto;
			background: white;
			padding: 20px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		h1 {
			margin-top: 0;
			color: #1d2327;
		}
		.project-section {
			margin: 30px 0;
			padding: 20px;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
		}
		.project-section h2 {
			margin-top: 0;
			border-bottom: 2px solid #c3c4c7;
			padding-bottom: 10px;
		}
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 15px;
			margin: 15px 0;
		}
		.stat-card {
			background: #f6f7f7;
			border: 1px solid #c3c4c7;
			border-left: 4px solid #2271b1;
			padding: 15px;
		}
		.stat-card h3 {
			margin: 0 0 10px 0;
			font-size: 14px;
			text-transform: uppercase;
			color: #50575e;
		}
		.stat-card .value {
			font-size: 24px;
			font-weight: 600;
			color: #1d2327;
		}
		.button {
			display: inline-block;
			padding: 8px 16px;
			background: #2271b1;
			color: white;
			text-decoration: none;
			border-radius: 3px;
			margin-right: 10px;
			margin-top: 10px;
		}
		.button:hover {
			background: #135e96;
		}
		.button-success {
			background: #00a32a;
		}
		.button-success:hover {
			background: #008a20;
		}
		.notice {
			padding: 10px;
			margin: 20px 0;
			border-left: 4px solid;
		}
		.notice-success {
			background: #d4edda;
			border-color: #00a32a;
		}
		.notice-error {
			background: #f8d7da;
			border-color: #d63638;
		}
		.notice-info {
			background: #e7f5fe;
			border-color: #2271b1;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>Location Data Import</h1>
		
		<?php if ( $action_notice ): ?>
			<?php echo $action_notice; ?>
		<?php endif; ?>
		
		<div class="notice notice-info">
			<p><strong>Instructions:</strong></p>
			<ul>
				<li>This tool imports location data from JSON (stored in meta tables) to new location tables</li>
				<li>Data is imported in batches to avoid timeouts</li>
				<li>Locations are imported in reverse order to maintain correct display order</li>
				<li>Click "Import Batch" to process next batch of loads</li>
				<li>Click "Get Stats" to see current import progress</li>
				<li><strong>Performance:</strong> Click "Create Route Statistics Indexes" to add optimized indexes for route statistics queries (recommended for large datasets)</li>
			</ul>
		</div>
		
		<div style="margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
			<h3 style="margin-top: 0;">Performance Optimization</h3>
			<p>For route statistics queries (Loads by Route), it's recommended to create optimized indexes. This will significantly speed up queries for large datasets (30k+ records).</p>
			<a href="<?php echo esc_url( add_query_arg( array( 
				'location-import' => '1', 
				'action' => 'create_indexes', 
				'nonce' => wp_create_nonce( 'location_import_action' ) 
			) ) ); ?>" class="button button-success">Create Route Statistics Indexes</a>
		</div>
		
		<?php foreach ( $projects as $project ): ?>
			<?php
			$project_lower = strtolower( $project );
			$stats_odysseia = $reports->get_location_import_stats( $project );
			$stats_flt = $reports_flt->get_location_import_stats( $project );
			?>
			
			<div class="project-section">
				<h2><?php echo esc_html( $project ); ?> - Odysseia</h2>
				<div class="stats-grid">
					<div class="stat-card">
						<h3>Total Loads</h3>
						<div class="value"><?php echo esc_html( $stats_odysseia['total_loads'] ); ?></div>
					</div>
					<div class="stat-card">
						<h3>Processed</h3>
						<div class="value"><?php echo esc_html( $stats_odysseia['processed'] ); ?></div>
					</div>
					<div class="stat-card">
						<h3>Imported Locations</h3>
						<div class="value"><?php echo esc_html( $stats_odysseia['imported_locations'] ); ?></div>
					</div>
					<div class="stat-card">
						<h3>Progress</h3>
						<div class="value"><?php echo esc_html( number_format( $stats_odysseia['progress_percent'], 1 ) ); ?>%</div>
					</div>
				</div>
				<a href="<?php echo esc_url( add_query_arg( array( 
					'location-import' => '1', 
					'action' => 'import_batch', 
					'project' => $project,
					'is_flt' => '0',
					'batch_size' => 50,
					'nonce' => wp_create_nonce( 'location_import_action' ) 
				) ) ); ?>" class="button button-success">Import Batch (50 loads)</a>
				<a href="<?php echo esc_url( add_query_arg( array( 
					'location-import' => '1', 
					'action' => 'get_stats', 
					'project' => $project,
					'is_flt' => '0',
					'nonce' => wp_create_nonce( 'location_import_action' ) 
				) ) ); ?>" class="button">Refresh Stats</a>
			</div>
			
			<div class="project-section">
				<h2><?php echo esc_html( $project ); ?> - FLT</h2>
				<div class="stats-grid">
					<div class="stat-card">
						<h3>Total Loads</h3>
						<div class="value"><?php echo esc_html( $stats_flt['total_loads'] ); ?></div>
					</div>
					<div class="stat-card">
						<h3>Processed</h3>
						<div class="value"><?php echo esc_html( $stats_flt['processed'] ); ?></div>
					</div>
					<div class="stat-card">
						<h3>Imported Locations</h3>
						<div class="value"><?php echo esc_html( $stats_flt['imported_locations'] ); ?></div>
					</div>
					<div class="stat-card">
						<h3>Progress</h3>
						<div class="value"><?php echo esc_html( number_format( $stats_flt['progress_percent'], 1 ) ); ?>%</div>
					</div>
				</div>
				<a href="<?php echo esc_url( add_query_arg( array( 
					'location-import' => '1', 
					'action' => 'import_batch', 
					'project' => $project,
					'is_flt' => '1',
					'batch_size' => 50,
					'nonce' => wp_create_nonce( 'location_import_action' ) 
				) ) ); ?>" class="button button-success">Import Batch (50 loads)</a>
				<a href="<?php echo esc_url( add_query_arg( array( 
					'location-import' => '1', 
					'action' => 'get_stats', 
					'project' => $project,
					'is_flt' => '1',
					'nonce' => wp_create_nonce( 'location_import_action' ) 
				) ) ); ?>" class="button">Refresh Stats</a>
			</div>
		<?php endforeach; ?>
	</div>
</body>
</html>
<?php
exit;
