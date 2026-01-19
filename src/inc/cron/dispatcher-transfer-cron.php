<?php
/**
 * Cron job for gradual transfer of dispatcher data
 * Runs every 2 minutes to transfer dispatcher data in batches (50-100 records per run)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add custom cron interval for 2 minutes
add_filter( 'cron_schedules', 'tms_add_two_minutes_interval' );
function tms_add_two_minutes_interval( $schedules ) {
	if ( ! isset( $schedules['two_minutes'] ) ) {
		$schedules['two_minutes'] = array(
			'interval' => 120, // 2 minutes in seconds
			'display'  => __( 'Every 2 Minutes' )
		);
	}
	return $schedules;
}

// Schedule the cron event
add_action( 'init', 'tms_schedule_dispatcher_transfer_cron' );
function tms_schedule_dispatcher_transfer_cron() {
	$next_scheduled = wp_next_scheduled( 'tms_transfer_dispatcher_data' );
	if ( ! $next_scheduled ) {
		$scheduled = wp_schedule_event( time(), 'two_minutes', 'tms_transfer_dispatcher_data' );
		if ( $scheduled !== false ) {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( '[Cron] Cron event scheduled successfully. Next run: ' . date( 'Y-m-d H:i:s', wp_next_scheduled( 'tms_transfer_dispatcher_data' ) ), 'dispatcher-transfer' );
			}
		} else {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( '[Cron] ERROR: Failed to schedule cron event', 'dispatcher-transfer' );
			}
		}
	}
	// Cron already scheduled - no need to log
}

// The actual cron function
add_action( 'tms_transfer_dispatcher_data', 'tms_transfer_dispatcher_data_function' );
function tms_transfer_dispatcher_data_function() {
	if ( class_exists( 'TMSLogger' ) ) {
		TMSLogger::log_to_file( '[Cron] Cron job started', 'dispatcher-transfer' );
	}
	
	$transfer_manager = new TMSDispatcherTransferManager();
	$transfer_manager->process_pending_transfers();
	
	if ( class_exists( 'TMSLogger' ) ) {
		TMSLogger::log_to_file( '[Cron] Cron job completed', 'dispatcher-transfer' );
	}
}

// Auto-process queue on every page load if there are pending transfers (with rate limiting)
add_action( 'init', 'tms_auto_process_dispatcher_transfer', 99 );
function tms_auto_process_dispatcher_transfer() {
	// Skip if this is an AJAX request, cron request, or admin request (except status page)
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}
	
	// Check if there are pending transfers
	$queue = get_transient( 'remove_dispatchers' );
	if ( ! is_array( $queue ) || empty( $queue ) ) {
		return; // No pending transfers
	}
	
	// Rate limiting: only process once every 2 minutes
	$last_run = get_transient( 'tms_dispatcher_transfer_last_run' );
	$min_interval = 120; // 2 minutes in seconds
	
	if ( $last_run && ( time() - $last_run ) < $min_interval ) {
		return; // Too soon, skip this run
	}
	
	// Update last run time
	set_transient( 'tms_dispatcher_transfer_last_run', time(), 300 ); // 5 minutes expiration
	
	// Process transfers
	$transfer_manager = new TMSDispatcherTransferManager();
	$transfer_manager->process_pending_transfers();
	$transfer_manager->update_summary();
}
