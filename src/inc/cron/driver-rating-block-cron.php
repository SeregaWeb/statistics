<?php
/**
 * Auto-block drivers with low rating (below 2.5)
 * Runs every 5 minutes via Action Scheduler to check and block drivers with average rating < 2.5
 */

error_log( 'TMS Driver Rating Block Cron: File loaded successfully' );

// Add custom cron interval for 5 minutes (for WP Cron fallback)
add_filter( 'cron_schedules', 'tms_add_five_minutes_interval_rating_block' );
function tms_add_five_minutes_interval_rating_block( $schedules ) {
	$schedules['five_minutes_rating_block'] = array(
		'interval' => 300, // 5 minutes in seconds
		'display'  => __( 'Every 5 Minutes (Rating Block)' )
	);
	return $schedules;
}

// Schedule the cron event after plugins are loaded (Action Scheduler should be available by then)
add_action( 'plugins_loaded', 'tms_schedule_driver_rating_block_cron', 20 );
add_action( 'init', 'tms_schedule_driver_rating_block_cron', 20 );
function tms_schedule_driver_rating_block_cron() {
	// Hook the action first (only once)
	if ( ! has_action( 'tms_auto_block_low_rated_drivers', 'tms_auto_block_low_rated_drivers_function' ) ) {
		add_action( 'tms_auto_block_low_rated_drivers', 'tms_auto_block_low_rated_drivers_function' );
		error_log( 'TMS Driver Rating Block Cron: Action hook registered' );
	}
	
	// Try Action Scheduler first (more reliable)
	if ( function_exists( 'as_schedule_recurring_action' ) ) {
		$next_scheduled = as_next_scheduled_action( 'tms_auto_block_low_rated_drivers' );
		if ( ! $next_scheduled ) {
			$result = as_schedule_recurring_action(
				time(), // Start time
				300,    // Interval: 5 minutes in seconds
				'tms_auto_block_low_rated_drivers', // Action hook
				array(), // Arguments
				'driver-rating-block' // Group
			);
			
			if ( $result ) {
				error_log( 'TMS Driver Rating Block Cron: Scheduled via Action Scheduler. Action ID: ' . $result );
			} else {
				error_log( 'TMS Driver Rating Block Cron: Failed to schedule via Action Scheduler, falling back to WP Cron' );
				// Fallback to WP Cron
				if ( ! wp_next_scheduled( 'tms_auto_block_low_rated_drivers_wp_cron' ) ) {
					wp_schedule_event( time(), 'five_minutes_rating_block', 'tms_auto_block_low_rated_drivers_wp_cron' );
					error_log( 'TMS Driver Rating Block Cron: Scheduled via WP Cron' );
				}
			}
		} else {
			error_log( 'TMS Driver Rating Block Cron: Already scheduled via Action Scheduler. Next run: ' . date( 'Y-m-d H:i:s', $next_scheduled ) );
		}
	} else {
		// Fallback to WP Cron if Action Scheduler is not available
		error_log( 'TMS Driver Rating Block Cron: Action Scheduler not available, using WP Cron' );
		if ( ! wp_next_scheduled( 'tms_auto_block_low_rated_drivers_wp_cron' ) ) {
			wp_schedule_event( time(), 'five_minutes_rating_block', 'tms_auto_block_low_rated_drivers_wp_cron' );
			error_log( 'TMS Driver Rating Block Cron: Scheduled via WP Cron' );
		}
	}
	
	// Also hook WP Cron action
	if ( ! has_action( 'tms_auto_block_low_rated_drivers_wp_cron', 'tms_auto_block_low_rated_drivers_function' ) ) {
		add_action( 'tms_auto_block_low_rated_drivers_wp_cron', 'tms_auto_block_low_rated_drivers_function' );
	}
	
	// Force run check on every page load (similar to driver-status-cron.php)
	// This ensures it runs even if cron is not triggered
	$last_run = get_transient( 'tms_rating_block_last_run' );
	$current_time = time();
	if ( ! $last_run || ( $current_time - $last_run ) >= 300 ) { // 5 minutes
		// Run in background to avoid blocking page load
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			register_shutdown_function( 'tms_auto_block_low_rated_drivers_function' );
		} else {
			// Run directly if fastcgi_finish_request is not available
			add_action( 'shutdown', 'tms_auto_block_low_rated_drivers_function', 999 );
		}
	}
}

// The actual cron function
function tms_auto_block_low_rated_drivers_function() {
	global $wpdb;
	
	// Prevent multiple simultaneous runs
	$lock_key = 'tms_rating_block_running';
	if ( get_transient( $lock_key ) ) {
		return; // Already running
	}
	set_transient( $lock_key, true, 60 ); // Lock for 60 seconds
	
	// Update last run time
	set_transient( 'tms_rating_block_last_run', time(), 3600 );
	
	error_log( 'TMS Driver Rating Block Cron: Function started at ' . date( 'Y-m-d H:i:s' ) );
	
	$table_main = $wpdb->prefix . 'drivers';
	$table_meta = $wpdb->prefix . 'drivers_meta';
	$table_rating = $wpdb->prefix . 'drivers_raiting';
	
	// Minimum rating threshold
	$min_rating = 2.5;
	
	// Find drivers with average rating below threshold
	// Exclude drivers that are already blocked or banned
	$query = "
		SELECT 
			main.id as driver_id,
			AVG(rating.reit) as avg_rating,
			COUNT(rating.id) as rating_count,
			COALESCE(status.meta_value, '') as current_status
		FROM {$table_main} AS main
		INNER JOIN {$table_rating} AS rating
			ON main.id = rating.driver_id
		LEFT JOIN {$table_meta} AS status
			ON main.id = status.post_id
			AND status.meta_key = 'driver_status'
		WHERE main.status_post = 'publish'
			AND (status.meta_value IS NULL OR status.meta_value NOT IN ('blocked', 'banned'))
		GROUP BY main.id
		HAVING AVG(rating.reit) < %f
			AND COUNT(rating.id) > 0
		ORDER BY avg_rating ASC
	";
	
	$results = $wpdb->get_results( 
		$wpdb->prepare( $query, $min_rating ), 
		ARRAY_A 
	);
	
	if ( empty( $results ) ) {
		error_log( 'TMS Driver Rating Block Cron: No drivers found with rating below ' . $min_rating );
		return;
	}
	
	error_log( 'TMS Driver Rating Block Cron: Found ' . count( $results ) . ' driver(s) with rating below ' . $min_rating );
	
	$blocked_count = 0;
	$helper = new TMSReportsHelper();
	
	foreach ( $results as $driver ) {
		$driver_id = (int) $driver['driver_id'];
		$avg_rating = round( (float) $driver['avg_rating'], 2 );
		$rating_count = (int) $driver['rating_count'];
		$current_status = $driver['current_status'] ? $driver['current_status'] : 'no status';
		
		// Double-check that driver is not already blocked or banned
		$current_status_check = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_value 
			FROM {$table_meta}
			WHERE post_id = %d AND meta_key = 'driver_status'
		", $driver_id ) );
		
		if ( in_array( $current_status_check, array( 'blocked', 'banned' ) ) ) {
			continue; // Skip if already blocked or banned
		}
		
		// Get driver name for logging
		$driver_name_meta = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_value 
			FROM {$table_meta}
			WHERE post_id = %d AND meta_key = 'driver_name'
		", $driver_id ) );
		
		$driver_name = $driver_name_meta ? $driver_name_meta : 'Unknown Driver';
		
		// Update driver status to 'blocked'
		$existing_status = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_value FROM {$table_meta} 
			WHERE post_id = %d AND meta_key = 'driver_status'
		", $driver_id ) );
		
		if ( $existing_status !== null ) {
			// Update existing status
			$update_result = $wpdb->update(
				$table_meta,
				array( 'meta_value' => 'blocked' ),
				array( 
					'post_id' => $driver_id,
					'meta_key' => 'driver_status'
				),
				array( '%s' ),
				array( '%d', '%s' )
			);
		} else {
			// Insert new status
			$update_result = $wpdb->insert(
				$table_meta,
				array(
					'post_id'    => $driver_id,
					'meta_key'   => 'driver_status',
					'meta_value' => 'blocked'
				),
				array( '%d', '%s', '%s' )
			);
		}
		
		if ( $update_result !== false ) {
			$blocked_count++;
			
			// Clear driver cache (similar to update_driver_status_in_db)
			$Drivers = new TMSDrivers();
			$Drivers->clear_drivers_cache();
			delete_transient( 'tms_all_available_drivers' );
			
			// Log the blocking action
			$log_message = sprintf(
				'TMS Auto-Block Driver: Driver ID %d (%s) has been automatically blocked. ' .
				'Average rating: %.2f (from %d ratings). Previous status: %s. ' .
				'Blocked at: %s',
				$driver_id,
				$driver_name,
				$avg_rating,
				$rating_count,
				$current_status,
				date( 'Y-m-d H:i:s' )
			);
			
			error_log( $log_message );
		}
	}
	
	if ( $blocked_count > 0 ) {
		error_log( sprintf( 
			'TMS Driver Rating Block Cron: Successfully blocked %d driver(s) with rating below %.2f',
			$blocked_count,
			$min_rating
		) );
	} else {
		error_log( 'TMS Driver Rating Block Cron: Function completed. No drivers were blocked.' );
	}
	
	// Release lock
	delete_transient( $lock_key );
}

// Add manual trigger function for testing (can be called via AJAX or directly)
// Usage: Add ?tms_test_rating_block=1 to any page URL (admin only)
add_action( 'admin_init', 'tms_manual_test_rating_block' );
function tms_manual_test_rating_block() {
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}
	
	if ( isset( $_GET['tms_test_rating_block'] ) && $_GET['tms_test_rating_block'] === '1' ) {
		error_log( 'TMS Driver Rating Block Cron: Manual test triggered by admin' );
		tms_auto_block_low_rated_drivers_function();
		wp_die( 'Rating block cron executed. Check debug.log for details.', 'Test Complete', array( 'response' => 200 ) );
	}
}

