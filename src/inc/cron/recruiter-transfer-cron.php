<?php
/**
 * Cron job for gradual transfer of recruiter data
 * Runs every 2 minutes to transfer driver data in batches (100 records per run)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Schedule the cron event
add_action( 'init', 'tms_schedule_recruiter_transfer_cron' );
function tms_schedule_recruiter_transfer_cron() {
	$next_scheduled = wp_next_scheduled( 'tms_transfer_recruiter_data' );
	if ( ! $next_scheduled ) {
		$scheduled = wp_schedule_event( time(), 'two_minutes', 'tms_transfer_recruiter_data' );
		if ( $scheduled !== false ) {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( '[Cron] Cron event scheduled successfully. Next run: ' . date( 'Y-m-d H:i:s', wp_next_scheduled( 'tms_transfer_recruiter_data' ) ), 'recruiter-transfer' );
			}
		} else {
			if ( class_exists( 'TMSLogger' ) ) {
				TMSLogger::log_to_file( '[Cron] ERROR: Failed to schedule cron event', 'recruiter-transfer' );
			}
		}
	}
	// Cron already scheduled - no need to log
}

// The actual cron function
add_action( 'tms_transfer_recruiter_data', 'tms_transfer_recruiter_data_function' );
function tms_transfer_recruiter_data_function() {
	if ( class_exists( 'TMSLogger' ) ) {
		TMSLogger::log_to_file( '[Cron] Cron job started', 'recruiter-transfer' );
	}
	
	$transfer_manager = new TMSRecruiterTransferManager();
	$transfer_manager->process_pending_transfers();
	
	if ( class_exists( 'TMSLogger' ) ) {
		TMSLogger::log_to_file( '[Cron] Cron job completed', 'recruiter-transfer' );
	}
}

// Auto-process queue on every page load if there are pending transfers (with rate limiting)
add_action( 'init', 'tms_auto_process_recruiter_transfer', 99 );
function tms_auto_process_recruiter_transfer() {
	// Skip if this is an AJAX request, cron request, or admin request (except status page)
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}
	
	// Check if there are pending transfers
	$queue = get_transient( 'remove_recruiters' );
	if ( ! is_array( $queue ) || empty( $queue ) ) {
		return; // No pending transfers
	}
	
	// Rate limiting: only process once every 2 minutes
	$last_run = get_transient( 'tms_recruiter_transfer_last_run' );
	$min_interval = 120; // 2 minutes in seconds
	
	if ( $last_run && ( time() - $last_run ) < $min_interval ) {
		return; // Too soon, skip this run
	}
	
	// Update last run time
	set_transient( 'tms_recruiter_transfer_last_run', time(), 300 ); // 5 minutes expiration
	
	// Process transfers
	$transfer_manager = new TMSRecruiterTransferManager();
	$transfer_manager->process_pending_transfers();
	$transfer_manager->update_summary();
}
