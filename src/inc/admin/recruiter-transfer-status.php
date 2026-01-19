<?php
/**
 * Admin page for tracking recruiter transfer status
 * Access via ?recruiter-transfer-manager=1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Check GET parameter
if ( ! isset( $_GET['recruiter-transfer-manager'] ) || $_GET['recruiter-transfer-manager'] != '1' ) {
	return;
}

// Check if user has permission
if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Administrator only.' );
}


$transfer_manager = new TMSRecruiterTransferManager();

// Debug: Check transient directly (no logging)
$queue_debug = get_transient( 'remove_recruiters' );

// Auto-process queue if there are pending transfers (but not if action is already being processed)
if ( ! isset( $_GET['action'] ) && is_array( $queue_debug ) && ! empty( $queue_debug ) ) {
	$transfer_manager->process_pending_transfers();
	$transfer_manager->update_summary();
}

$summary = $transfer_manager->get_summary();
$queue_status = $transfer_manager->get_queue_status();

// Handle actions
$action_notice = '';
if ( isset( $_GET['action'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'recruiter_transfer_action' ) ) {
	if ( $_GET['action'] === 'update_summary' ) {
		$transfer_manager->update_summary();
		$summary = $transfer_manager->get_summary();
		$action_notice = '<div class="notice notice-success"><p>Summary updated successfully.</p></div>';
	} elseif ( $_GET['action'] === 'run_transfer' ) {
		// Manually trigger transfer processing
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( 'Manual run_transfer action triggered', 'recruiter-transfer' );
		}
		$transfer_manager->process_pending_transfers();
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( 'process_pending_transfers() completed', 'recruiter-transfer' );
		}
		$transfer_manager->update_summary();
		$summary = $transfer_manager->get_summary();
		$queue_status = $transfer_manager->get_queue_status();
		$action_notice = '<div class="notice notice-success"><p>Transfer processing executed. Progress updated below.</p></div>';
	}
}

// Get next cron run time
$next_cron = wp_next_scheduled( 'tms_transfer_recruiter_data' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Recruiter Transfer Manager - Status</title>
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
		.notice {
			padding: 10px;
			margin: 20px 0;
			border-left: 4px solid;
		}
		.notice-info {
			background: #e7f5fe;
			border-color: #2271b1;
		}
		.notice-warning {
			background: #fff3cd;
			border-color: #dba617;
		}
		.notice-success {
			background: #d4edda;
			border-color: #00a32a;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>Recruiter Transfer Manager - Status</h1>
		
		<?php if ( $action_notice ): ?>
			<?php echo $action_notice; ?>
		<?php endif; ?>
		
		<?php
		// Check if running on localhost
		$is_localhost = in_array( $_SERVER['HTTP_HOST'], array( 'localhost', '127.0.0.1' ) ) || preg_match( '/^localhost:\d+$/', $_SERVER['HTTP_HOST'] );
		
		// Debug information
		$queue_debug_count = is_array( $queue_debug ) ? count( $queue_debug ) : 0;
		$queue_debug_keys = is_array( $queue_debug ) ? array_keys( $queue_debug ) : array();
		?>
		
		<div class="notice notice-info">
			<p><strong>Debug Information:</strong></p>
			<ul style="margin: 5px 0; padding-left: 20px;">
				<li>Transient 'remove_recruiters': <?php echo esc_html( $queue_debug_count ); ?> item(s)</li>
				<?php if ( ! empty( $queue_debug_keys ) ): ?>
					<li>Queue keys: <?php echo esc_html( implode( ', ', $queue_debug_keys ) ); ?></li>
				<?php endif; ?>
				<li>Queue status from manager: <?php echo esc_html( is_array( $queue_status ) && isset( $queue_status['items'] ) ? count( $queue_status['items'] ) : 'not an array' ); ?> item(s)</li>
				<li>Summary total recruiters: <?php echo esc_html( $summary['total_recruiters'] ); ?></li>
				<?php if ( $next_cron ): ?>
					<li>Next cron run: <?php echo esc_html( date( 'Y-m-d H:i:s', $next_cron ) ); ?></li>
				<?php else: ?>
					<li>Next cron run: <span style="color: #d63638;">Not scheduled</span></li>
				<?php endif; ?>
			</ul>
		</div>
		
		<?php if ( $is_localhost && ! empty( $queue_status['items'] ) ): ?>
			<div class="notice notice-warning">
				<p><strong>Note:</strong> Running on localhost. Cron jobs are skipped. Use "Run Transfer Now" button to manually process transfers.</p>
			</div>
		<?php endif; ?>
		
		<div class="actions">
			<a href="<?php echo esc_url( add_query_arg( array( 'recruiter-transfer-manager' => '1', 'action' => 'run_transfer', 'nonce' => wp_create_nonce( 'recruiter_transfer_action' ) ) ) ); ?>" class="button" style="background: #00a32a;">Run Transfer Now</a>
			<?php if ( $is_localhost ): ?>
				<a href="<?php echo esc_url( site_url( '/wp-cron.php' ) ); ?>" target="_blank" class="button" style="background: #2271b1;">Trigger WordPress Cron</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'recruiter-transfer-manager' => '1', 'action' => 'update_summary', 'nonce' => wp_create_nonce( 'recruiter_transfer_action' ) ) ) ); ?>" class="button">Refresh Summary</a>
		</div>
		
		<!-- Overall Statistics -->
		<div class="section">
			<h2>Overall Statistics</h2>
			<div class="stats-grid">
				<div class="stat-card">
					<h3>Recruiters in Queue</h3>
					<div class="value"><?php echo esc_html( $summary['total_recruiters'] ); ?></div>
					<div class="label">Active transfers</div>
				</div>
				<div class="stat-card">
					<h3>Total Progress</h3>
					<?php 
					$total_progress = $summary['total_drivers'] > 0 
						? ( $summary['processed_drivers'] / $summary['total_drivers'] ) * 100 
						: 0;
					?>
					<div class="value"><?php echo esc_html( number_format( $total_progress, 1 ) ); ?>%</div>
					<div class="progress-bar">
						<div class="progress-bar-fill" style="width: <?php echo esc_attr( $total_progress ); ?>%;">
							<?php echo esc_html( number_format( $total_progress, 1 ) ); ?>%
						</div>
					</div>
					<div class="label">
						<?php echo esc_html( $summary['processed_drivers'] ); ?> / 
						<?php echo esc_html( $summary['total_drivers'] ); ?> drivers
					</div>
				</div>
				<div class="stat-card">
					<h3>Drivers</h3>
					<div class="value"><?php echo esc_html( $summary['processed_drivers'] ); ?> / <?php echo esc_html( $summary['total_drivers'] ); ?></div>
					<div class="label"><?php echo esc_html( $summary['remaining_drivers'] ); ?> remaining</div>
				</div>
			</div>
		</div>
		
		<!-- Queue Details -->
		<div class="section">
			<h2>Queue Details</h2>
			<?php if ( empty( $queue_status['items'] ) ): ?>
				<div class="empty-state">
					<p>No recruiters in queue. All transfers completed.</p>
				</div>
			<?php else: ?>
				<table>
					<thead>
						<tr>
							<th>Recruiter ID</th>
							<th>New Recruiter ID</th>
							<th>Drivers</th>
							<th>Progress</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $queue_status['items'] as $item ): ?>
							<?php
							$old_user = get_user_by( 'id', $item['recruiter_id'] );
							$new_user = get_user_by( 'id', $item['new_recruiter_id'] );
							$old_name = $old_user ? ( trim( $old_user->first_name . ' ' . $old_user->last_name ) ?: $old_user->display_name ) : 'User #' . $item['recruiter_id'];
							$new_name = $new_user ? ( trim( $new_user->first_name . ' ' . $new_user->last_name ) ?: $new_user->display_name ) : 'User #' . $item['new_recruiter_id'];
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $old_name ); ?></strong><br>
									<small style="color: #646970;">ID: <?php echo esc_html( $item['recruiter_id'] ); ?></small>
								</td>
								<td>
									<strong><?php echo esc_html( $new_name ); ?></strong><br>
									<small style="color: #646970;">ID: <?php echo esc_html( $item['new_recruiter_id'] ); ?></small>
								</td>
								<td>
									<?php echo esc_html( $item['drivers_processed'] ); ?> / <?php echo esc_html( $item['drivers_total'] ); ?><br>
									<small style="color: #646970;"><?php echo esc_html( $item['drivers_remaining'] ); ?> remaining</small>
								</td>
								<td>
									<div class="progress-bar">
										<div class="progress-bar-fill" style="width: <?php echo esc_attr( $item['progress_percent'] ); ?>%;">
											<?php echo esc_html( number_format( $item['progress_percent'], 1 ) ); ?>%
										</div>
									</div>
								</td>
								<td>
									<?php if ( $item['progress_percent'] >= 100 ): ?>
										<span class="badge badge-success">Completed</span>
									<?php elseif ( $item['progress_percent'] > 0 ): ?>
										<span class="badge badge-info">In Progress</span>
									<?php else: ?>
										<span class="badge badge-warning">Pending</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		
		<!-- Log File -->
		<div class="section">
			<h2>Log File</h2>
			<?php
			// Log file info
			if ( class_exists( 'TMSLogger' ) ) {
				$log_file_exists = TMSLogger::log_file_exists( 'recruiter-transfer' );
				$log_file_size = TMSLogger::get_log_file_size( 'recruiter-transfer' );
				$log_file_url = TMSLogger::get_log_file_url( 'recruiter-transfer' );
				$log_file_mtime = TMSLogger::get_log_file_mtime( 'recruiter-transfer' );
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
					<a href="<?php echo esc_url( $log_file_url ); ?>" target="_blank" style="color: #2271b1;">recruiter-transfer.log</a> 
					(<?php echo esc_html( size_format( $log_file_size ) ); ?>, 
					<?php echo esc_html( $log_file_mtime ? date( 'Y-m-d H:i:s', $log_file_mtime ) : 'N/A' ); ?>)
				<?php else: ?>
					<span style="color: #646970;">Not created yet</span>
				<?php endif; ?>
			</p>
		</div>
	</div>
</body>
</html>
<?php
exit;