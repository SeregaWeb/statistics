<?php
/**
 * Admin page for tracking dispatcher transfer status
 * Access via ?dispatcher-transfer-manager=1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



// Check GET parameter
if ( ! isset( $_GET['dispatcher-transfer-manager'] ) || $_GET['dispatcher-transfer-manager'] != '1' ) {
	return;
}

// Check if user has permission
if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Administrator only.' );
}

$transfer_manager = new TMSDispatcherTransferManager();

// Debug: Check transient directly (no logging)
$queue_debug = get_transient( 'remove_dispatchers' );

// Auto-process queue if there are pending transfers (but not if action is already being processed)
if ( ! isset( $_GET['action'] ) && is_array( $queue_debug ) && ! empty( $queue_debug ) ) {
	$transfer_manager->process_pending_transfers();
	$transfer_manager->update_summary();
}

$summary = $transfer_manager->get_summary();
$queue_status = $transfer_manager->get_queue_status();
$log_entries = $transfer_manager->get_log( 50 );

// Handle actions
$action_notice = '';
if ( isset( $_GET['action'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'dispatcher_transfer_action' ) ) {
	if ( $_GET['action'] === 'clear_log' ) {
		$transfer_manager->clear_log();
		$log_entries = array();
		$action_notice = '<div class="notice notice-success"><p>Log cleared successfully.</p></div>';
	} elseif ( $_GET['action'] === 'update_summary' ) {
		$transfer_manager->update_summary();
		$summary = $transfer_manager->get_summary();
		$action_notice = '<div class="notice notice-success"><p>Summary updated successfully.</p></div>';
	} elseif ( $_GET['action'] === 'run_transfer' ) {
		// Manually trigger transfer processing
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( 'Manual run_transfer action triggered', 'dispatcher-transfer' );
		}
		$transfer_manager->process_pending_transfers();
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( 'process_pending_transfers() completed', 'dispatcher-transfer' );
		}
		$transfer_manager->update_summary();
		$summary = $transfer_manager->get_summary();
		$queue_status = $transfer_manager->get_queue_status();
		$log_entries = $transfer_manager->get_log( 50 );
		$action_notice = '<div class="notice notice-success"><p>Transfer processing executed. Progress updated below. Check debug.log for details.</p></div>';
	}
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Dispatcher Transfer Manager - Status</title>
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
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin: 20px 0;
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
			font-size: 32px;
			font-weight: 600;
			color: #1d2327;
		}
		.stat-card .label {
			font-size: 12px;
			color: #646970;
			margin-top: 5px;
		}
		.progress-bar {
			background: #dcdcde;
			height: 20px;
			border-radius: 10px;
			overflow: hidden;
			margin: 10px 0;
		}
		.progress-bar-fill {
			background: #2271b1;
			height: 100%;
			transition: width 0.3s ease;
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-size: 12px;
			font-weight: 600;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin: 20px 0;
		}
		table th, table td {
			padding: 10px;
			text-align: left;
			border-bottom: 1px solid #c3c4c7;
		}
		table th {
			background: #f6f7f7;
			font-weight: 600;
		}
		table tr:hover {
			background: #f6f7f7;
		}
		.badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.badge-success { background: #00a32a; color: white; }
		.badge-info { background: #2271b1; color: white; }
		.badge-warning { background: #dba617; color: white; }
		.badge-error { background: #d63638; color: white; }
		.actions {
			margin: 20px 0;
		}
		.button {
			display: inline-block;
			padding: 8px 16px;
			background: #2271b1;
			color: white;
			text-decoration: none;
			border-radius: 3px;
			margin-right: 10px;
		}
		.button:hover {
			background: #135e96;
		}
		.button-secondary {
			background: #f6f7f7;
			color: #2c3338;
			border: 1px solid #c3c4c7;
		}
		.button-secondary:hover {
			background: #f0f0f1;
		}
		.log-entry {
			padding: 8px;
			border-left: 3px solid #c3c4c7;
			margin: 5px 0;
			font-family: monospace;
			font-size: 12px;
		}
		.log-entry.success { border-left-color: #00a32a; }
		.log-entry.info { border-left-color: #2271b1; }
		.log-entry.warning { border-left-color: #dba617; }
		.log-entry.error { border-left-color: #d63638; }
		.log-timestamp {
			color: #646970;
			margin-right: 10px;
		}
		.empty-state {
			text-align: center;
			padding: 40px;
			color: #646970;
		}
		.section {
			margin: 30px 0;
		}
		.section h2 {
			border-bottom: 2px solid #c3c4c7;
			padding-bottom: 10px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>Dispatcher Transfer Manager - Status</h1>
		
		<?php if ( $action_notice ): ?>
			<?php echo $action_notice; ?>
		<?php endif; ?>
		
		<?php
		// Check if running on localhost
		$is_localhost = in_array( $_SERVER['HTTP_HOST'], array( 'localhost', '127.0.0.1' ) ) || preg_match( '/^localhost:\d+$/', $_SERVER['HTTP_HOST'] );
		
		// Debug information
		$queue_debug = get_transient( 'remove_dispatchers' );
		$queue_debug_count = is_array( $queue_debug ) ? count( $queue_debug ) : 0;
		$queue_debug_keys = is_array( $queue_debug ) ? array_keys( $queue_debug ) : array();
		?>
		
		<div class="notice notice-info" style="padding: 10px; margin: 20px 0; background: #e7f5fe; border-left: 4px solid #2271b1;">
			<p><strong>Debug Information:</strong></p>
			<ul style="margin: 5px 0; padding-left: 20px;">
				<li>Transient 'remove_dispatchers': <?php echo esc_html( $queue_debug_count ); ?> item(s)</li>
				<?php if ( ! empty( $queue_debug_keys ) ): ?>
					<li>Queue keys: <?php echo esc_html( implode( ', ', $queue_debug_keys ) ); ?></li>
				<?php endif; ?>
				<li>Queue status from manager: <?php echo esc_html( is_array( $queue_status ) ? count( $queue_status ) : 'not an array' ); ?> item(s)</li>
				<li>Summary total dispatchers: <?php echo esc_html( $summary['total_dispatchers'] ); ?></li>
			</ul>
		</div>
		
		<?php if ( $is_localhost && ! empty( $queue_status ) ): ?>
			<div class="notice notice-warning" style="padding: 10px; margin: 20px 0; background: #fff3cd; border-left: 4px solid #dba617;">
				<p><strong>Note:</strong> Running on localhost. Cron jobs are skipped. Use "Run Transfer Now" button to manually process transfers.</p>
			</div>
		<?php endif; ?>
		
		<div class="actions">
			<a href="<?php echo esc_url( add_query_arg( array( 'dispatcher-transfer-manager' => '1', 'action' => 'run_transfer', 'nonce' => wp_create_nonce( 'dispatcher_transfer_action' ) ) ) ); ?>" class="button" style="background: #00a32a;">Run Transfer Now</a>
			<?php if ( $is_localhost ): ?>
				<a href="<?php echo esc_url( site_url( '/wp-cron.php' ) ); ?>" target="_blank" class="button" style="background: #2271b1;">Trigger WordPress Cron</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'dispatcher-transfer-manager' => '1', 'action' => 'update_summary', 'nonce' => wp_create_nonce( 'dispatcher_transfer_action' ) ) ) ); ?>" class="button">Refresh Summary</a>
			<a href="<?php echo esc_url( add_query_arg( array( 'dispatcher-transfer-manager' => '1', 'action' => 'clear_log', 'nonce' => wp_create_nonce( 'dispatcher_transfer_action' ) ) ) ); ?>" class="button button-secondary" onclick="return confirm('Are you sure you want to clear the log?');">Clear Log</a>
		</div>
		
		<!-- Overall Statistics -->
		<div class="section">
			<h2>Overall Statistics</h2>
			<div class="stats-grid">
				<div class="stat-card">
					<h3>Dispatchers in Queue</h3>
					<div class="value"><?php echo esc_html( $summary['total_dispatchers'] ); ?></div>
					<div class="label">Active transfers</div>
				</div>
				<div class="stat-card">
					<h3>Total Progress</h3>
					<div class="value"><?php echo esc_html( $summary['grand_total']['progress_percent'] ); ?>%</div>
					<div class="progress-bar">
						<div class="progress-bar-fill" style="width: <?php echo esc_attr( $summary['grand_total']['progress_percent'] ); ?>%;">
							<?php echo esc_html( $summary['grand_total']['progress_percent'] ); ?>%
						</div>
					</div>
					<div class="label">
						<?php echo esc_html( $summary['grand_total']['processed_loads'] + $summary['grand_total']['processed_contacts'] ); ?> / 
						<?php echo esc_html( $summary['grand_total']['total_loads'] + $summary['grand_total']['total_contacts'] ); ?> items
					</div>
				</div>
				<div class="stat-card">
					<h3>Loads</h3>
					<div class="value"><?php echo esc_html( $summary['grand_total']['processed_loads'] ); ?> / <?php echo esc_html( $summary['grand_total']['total_loads'] ); ?></div>
					<div class="label"><?php echo esc_html( $summary['grand_total']['remaining_loads'] ); ?> remaining</div>
				</div>
				<div class="stat-card">
					<h3>Contacts</h3>
					<div class="value"><?php echo esc_html( $summary['grand_total']['processed_contacts'] ); ?> / <?php echo esc_html( $summary['grand_total']['total_contacts'] ); ?></div>
					<div class="label"><?php echo esc_html( $summary['grand_total']['remaining_contacts'] ); ?> remaining</div>
				</div>
			</div>
		</div>
		
		<!-- By Project -->
		<div class="section">
			<h2>By Project Type</h2>
			<div class="stats-grid">
				<?php foreach ( array( 'odysseia', 'flt' ) as $project_type ): ?>
					<?php $project = $summary['by_project'][ $project_type ]; ?>
					<div class="stat-card">
						<h3><?php echo esc_html( ucfirst( $project_type ) ); ?></h3>
						<div class="value"><?php echo esc_html( $project['count'] ); ?></div>
						<div class="label">Dispatchers</div>
						<div style="margin-top: 15px;">
							<div><strong>Loads:</strong> <?php echo esc_html( $project['processed_loads'] ); ?> / <?php echo esc_html( $project['total_loads'] ); ?></div>
							<div><strong>Contacts:</strong> <?php echo esc_html( $project['processed_contacts'] ); ?> / <?php echo esc_html( $project['total_contacts'] ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		
		<!-- By Table (Project) -->
		<div class="section">
			<h2>By Table (Odysseia / Martlet / Endurance)</h2>
			<?php foreach ( array( 'odysseia', 'flt' ) as $project_type ): ?>
				<h3><?php echo esc_html( ucfirst( $project_type ) ); ?></h3>
				<table>
					<thead>
						<tr>
							<th>Table</th>
							<th>Total</th>
							<th>Processed</th>
							<th>Remaining</th>
							<th>Progress</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $summary['by_table'][ $project_type ] as $table => $data ): ?>
							<?php
							$remaining = $data['total'] - $data['processed'];
							$percent = $data['total'] > 0 ? round( ( $data['processed'] / $data['total'] ) * 100, 2 ) : 0;
							?>
							<tr>
								<td><strong><?php echo esc_html( ucfirst( $table ) ); ?></strong></td>
								<td><?php echo esc_html( $data['total'] ); ?></td>
								<td><?php echo esc_html( $data['processed'] ); ?></td>
								<td><?php echo esc_html( $remaining ); ?></td>
								<td>
									<div class="progress-bar" style="width: 200px;">
										<div class="progress-bar-fill" style="width: <?php echo esc_attr( $percent ); ?>%;">
											<?php echo esc_html( $percent ); ?>%
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
		</div>
		
		<!-- Queue Details -->
		<div class="section">
			<h2>Queue Details</h2>
			<?php if ( empty( $queue_status ) ): ?>
				<div class="empty-state">
					<p>No dispatchers in queue.</p>
				</div>
			<?php else: ?>
				<table>
					<thead>
						<tr>
							<th>Dispatcher ID</th>
							<th>Project Type</th>
							<th>Loads</th>
							<th>Contacts</th>
							<th>Progress</th>
							<th>Created</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $queue_status as $queue_key => $status ): ?>
							<tr>
								<td><?php echo esc_html( $status['dispatcher_id'] ); ?></td>
								<td><span class="badge badge-info"><?php echo esc_html( $status['project_type'] ); ?></span></td>
								<td><?php echo esc_html( $status['processed_loads'] ); ?> / <?php echo esc_html( $status['total_loads'] ); ?></td>
								<td><?php echo esc_html( $status['processed_contacts'] ); ?> / <?php echo esc_html( $status['total_contacts'] ); ?></td>
								<td>
									<div class="progress-bar" style="width: 200px;">
										<div class="progress-bar-fill" style="width: <?php echo esc_attr( $status['progress_percent'] ); ?>%;">
											<?php echo esc_html( $status['progress_percent'] ); ?>%
										</div>
									</div>
								</td>
								<td><?php echo esc_html( date( 'Y-m-d H:i:s', $status['created_at'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		
		<!-- Log -->
		<div class="section">
			<h2>Recent Log Entries (Last 50)</h2>
			<?php if ( empty( $log_entries ) ): ?>
				<div class="empty-state">
					<p>No log entries yet.</p>
				</div>
			<?php else: ?>
				<div style="max-height: 500px; overflow-y: auto; border: 1px solid #c3c4c7; padding: 10px;">
					<?php foreach ( $log_entries as $entry ): ?>
						<div class="log-entry <?php echo esc_attr( $entry['type'] ); ?>">
							<span class="log-timestamp">[<?php echo esc_html( $entry['timestamp'] ); ?>]</span>
							<?php echo esc_html( $entry['message'] ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		
		<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #c3c4c7; color: #646970; font-size: 12px;">
			<p>Last updated: <?php echo esc_html( date( 'Y-m-d H:i:s', $summary['last_updated'] ) ); ?></p>
			<p>Cron runs every 2 minutes. Batch size: 100 loads, 50 contacts per run.</p>
			<?php
			$next_scheduled = wp_next_scheduled( 'tms_transfer_dispatcher_data' );
			if ( $next_scheduled ): ?>
				<p>Next cron run scheduled: <?php echo esc_html( date( 'Y-m-d H:i:s', $next_scheduled ) ); ?> (<?php echo esc_html( human_time_diff( $next_scheduled, time() ) ); ?> from now)</p>
			<?php else: ?>
				<p style="color: #d63638;"><strong>Warning:</strong> Cron event not scheduled. Please check if cron is properly configured.</p>
			<?php endif; ?>
			<?php
			// Check queue directly from transient
			$queue_check = get_transient( 'remove_dispatchers' );
			$queue_count = is_array( $queue_check ) ? count( $queue_check ) : 0;
			?>
			<p>Queue status: <?php echo esc_html( $queue_count ); ?> dispatcher(s) in transient storage.</p>
			<?php
			// Log file info
			if ( class_exists( 'TMSLogger' ) ) {
				$log_file_exists = TMSLogger::log_file_exists( 'dispatcher-transfer' );
				$log_file_size = TMSLogger::get_log_file_size( 'dispatcher-transfer' );
				$log_file_url = TMSLogger::get_log_file_url( 'dispatcher-transfer' );
				$log_file_mtime = TMSLogger::get_log_file_mtime( 'dispatcher-transfer' );
			} else {
				$log_file_exists = false;
				$log_file_size = 0;
				$log_file_url = '';
				$log_file_mtime = false;
			}
			?>
			<p>
				<strong>Log file:</strong> 
				<?php if ( $log_file_exists ): ?>
					<a href="<?php echo esc_url( $log_file_url ); ?>" target="_blank" style="color: #2271b1;">dispatcher-transfer.log</a> 
					(<?php echo esc_html( size_format( $log_file_size ) ); ?>, 
					<?php echo esc_html( $log_file_mtime ? date( 'Y-m-d H:i:s', $log_file_mtime ) : 'N/A' ); ?>)
				<?php else: ?>
					<span style="color: #646970;">Not created yet</span>
				<?php endif; ?>
			</p>
			<?php if ( $is_localhost ): ?>
				<p style="color: #dba617;"><strong>Note:</strong> On localhost, WordPress cron only runs when someone visits the site. You can manually trigger cron by visiting: <code><?php echo esc_url( site_url( '/wp-cron.php' ) ); ?></code></p>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
<?php
exit;
