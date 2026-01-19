<?php
/**
 * Class for managing gradual transfer of driver data from deleted recruiters
 * Handles transfer of drivers from deleted recruiter to new recruiter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TMSRecruiterTransferManager {
	
	const TRANSIENT_KEY = 'remove_recruiters';
	const TRANSIENT_SUMMARY_KEY = 'remove_recruiters_summary';
	const BATCH_SIZE_DRIVERS = 100; // Number of driver records to transfer per batch
	
	/**
	 * Log message to separate log file
	 *
	 * @param string $message Message to log
	 * @return void
	 */
	private function log_to_file( $message ) {
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( $message, 'recruiter-transfer' );
		}
	}
	
	/**
	 * Add recruiter ID to queue for gradual transfer
	 *
	 * @param int $recruiter_id Recruiter user ID to transfer
	 * @return bool|WP_Error
	 */
	public function add_recruiter_to_queue( $recruiter_id ) {
		$recruiter_id = absint( $recruiter_id );
		
		if ( ! $recruiter_id ) {
			return new WP_Error( 'invalid_id', 'Invalid recruiter ID' );
		}
		
		// Get new recruiter ID from global options
		global $global_options;
		$new_recruiter_id = get_field_value( $global_options, 'empty_recruiter' );
		
		if ( ! $new_recruiter_id || $new_recruiter_id === $recruiter_id ) {
			return new WP_Error( 'invalid_new_recruiter', 'Invalid or same new recruiter ID' );
		}
		
		// Get current queue
		$queue = get_transient( self::TRANSIENT_KEY );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}
		
		// Check if already in queue
		if ( isset( $queue[ $recruiter_id ] ) ) {
			return true; // Already in queue
		}
		
		// Get drivers count
		$drivers_count = $this->get_drivers_count( $recruiter_id );
		
		// If no drivers to transfer, don't add to queue
		if ( $drivers_count === 0 ) {
			$this->log_to_file( sprintf( 'No drivers found for recruiter ID %d, skipping queue', $recruiter_id ) );
			return true; // Nothing to transfer
		}
		
		// Initialize recruiter data
		$queue[ $recruiter_id ] = array(
			'recruiter_id' => $recruiter_id,
			'new_recruiter_id' => absint( $new_recruiter_id ),
			'drivers_progress' => array(
				'total' => $drivers_count,
				'processed' => 0,
				'last_id' => 0,
			),
			'created_at' => time(),
		);
		
		$this->log_to_file( sprintf( 'Added to queue - Recruiter ID: %d, New Recruiter ID: %d, Drivers: %d', 
			$recruiter_id,
			$new_recruiter_id,
			$drivers_count
		) );
		
		// Save queue
		set_transient( self::TRANSIENT_KEY, $queue, 0 );
		
		// Update summary
		$this->update_summary();
		
		return true;
	}
	
	/**
	 * Get drivers count for recruiter
	 *
	 * @param int $recruiter_id
	 * @return int
	 */
	private function get_drivers_count( $recruiter_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'drivers';
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE user_id_added = %d",
			$recruiter_id
		) );
		
		return (int) $count;
	}
	
	/**
	 * Process pending transfers
	 *
	 * @return void
	 */
	public function process_pending_transfers() {
		$queue = get_transient( self::TRANSIENT_KEY );
		
		if ( ! is_array( $queue ) || empty( $queue ) ) {
			return; // No queue to process
		}
		
		$log_msg = sprintf( '[START] Processing queue - %d recruiter(s) pending', count( $queue ) );
		$this->log_to_file( $log_msg );
		
		// Process one recruiter per cron run to avoid timeout
		foreach ( $queue as $recruiter_id => $data ) {
			$this->log_to_file( sprintf( 'Processing recruiter ID: %d', $recruiter_id ) );
			
			$result = $this->process_recruiter_transfer( $recruiter_id, $data );
			
			if ( $result['completed'] ) {
				// Remove from queue
				unset( $queue[ $recruiter_id ] );
				set_transient( self::TRANSIENT_KEY, $queue, 0 );
				
				$log_message = sprintf(
					'[COMPLETED] Recruiter ID %d -> %d - Drivers: %d',
					$recruiter_id,
					$data['new_recruiter_id'],
					$result['drivers_transferred']
				);
				$this->log_to_file( $log_message );
			} else {
				// Update progress
				$queue[ $recruiter_id ] = $data;
				set_transient( self::TRANSIENT_KEY, $queue, 0 );
				
				$log_message = sprintf(
					'[PROGRESS] Recruiter ID %d -> %d - Drivers: %d/%d (batch: +%d, %.1f%%)',
					$recruiter_id,
					$data['new_recruiter_id'],
					$data['drivers_progress']['processed'],
					$data['drivers_progress']['total'],
					$result['drivers_transferred'],
					$result['progress_percent']
				);
				$this->log_to_file( $log_message );
			}
			
			// Update summary
			$this->update_summary();
			
			// Process only one recruiter per cron run
			break;
		}
	}
	
	/**
	 * Process transfer for one recruiter
	 *
	 * @param int $recruiter_id
	 * @param array $data Recruiter data from queue
	 * @return array Result with completion status
	 */
	private function process_recruiter_transfer( $recruiter_id, &$data ) {
		$this->log_to_file( sprintf( 'process_recruiter_transfer() started - Recruiter ID: %d -> %d', 
			$recruiter_id, 
			$data['new_recruiter_id'] 
		) );
		
		$new_recruiter_id = $data['new_recruiter_id'];
		$drivers_transferred = 0;
		
		// If no drivers to transfer (total = 0), mark as completed immediately
		if ( $data['drivers_progress']['total'] === 0 ) {
			$this->log_to_file( 'No drivers to transfer (total = 0), marking as completed' );
			return array(
				'completed' => true,
				'drivers_transferred' => 0,
			);
		}
		
		// Check if already completed
		if ( $data['drivers_progress']['processed'] >= $data['drivers_progress']['total'] ) {
			$this->log_to_file( 'All drivers already transferred' );
			return array(
				'completed' => true,
				'drivers_transferred' => $data['drivers_progress']['total'],
			);
		}
		
		// Transfer batch of drivers
		$batch_result = $this->transfer_drivers_batch( $recruiter_id, $new_recruiter_id, $data['drivers_progress'] );
		
		if ( is_wp_error( $batch_result ) ) {
			$this->log_to_file( sprintf( 'Error transferring drivers: %s', $batch_result->get_error_message() ) );
			return array(
				'completed' => false,
				'drivers_transferred' => 0,
				'progress_percent' => 0,
			);
		}
		
		$drivers_transferred = $batch_result['transferred'];
		$data['drivers_progress']['processed'] += $drivers_transferred;
		$data['drivers_progress']['last_id'] = $batch_result['last_id'];
		
		// Check if completed
		$completed = $data['drivers_progress']['processed'] >= $data['drivers_progress']['total'];
		
		$progress_percent = $data['drivers_progress']['total'] > 0 
			? ( $data['drivers_progress']['processed'] / $data['drivers_progress']['total'] ) * 100 
			: 0;
		
		return array(
			'completed' => $completed,
			'drivers_transferred' => $drivers_transferred,
			'progress_percent' => $progress_percent,
		);
	}
	
	/**
	 * Transfer batch of drivers
	 *
	 * @param int $old_recruiter_id
	 * @param int $new_recruiter_id
	 * @param array $progress Progress data
	 * @return array|WP_Error
	 */
	private function transfer_drivers_batch( $old_recruiter_id, $new_recruiter_id, &$progress ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'drivers';
		$last_id = isset( $progress['last_id'] ) ? (int) $progress['last_id'] : 0;
		
		// Get batch of driver IDs
		$query = $wpdb->prepare(
			"SELECT id FROM $table_name 
			WHERE user_id_added = %d AND id > %d 
			ORDER BY id ASC 
			LIMIT %d",
			$old_recruiter_id,
			$last_id,
			self::BATCH_SIZE_DRIVERS
		);
		
		$driver_ids = $wpdb->get_col( $query );
		
		if ( empty( $driver_ids ) ) {
			$this->log_to_file( 'No drivers found in batch' );
			return array(
				'transferred' => 0,
				'last_id' => $last_id,
			);
		}
		
		$this->log_to_file( sprintf( 'Found %d drivers to transfer (IDs: %s)', 
			count( $driver_ids ),
			implode( ', ', array_slice( $driver_ids, 0, 10 ) ) . ( count( $driver_ids ) > 10 ? '...' : '' )
		) );
		
		// Update drivers in batch
		$ids_string = implode( ',', array_map( 'intval', $driver_ids ) );
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE $table_name 
			SET user_id_added = %d 
			WHERE id IN ($ids_string)",
			$new_recruiter_id
		) );
		
		if ( $updated === false ) {
			return new WP_Error( 'update_failed', 'Failed to update drivers' );
		}
		
		$new_last_id = max( $driver_ids );
		
		$this->log_to_file( sprintf( 'Transferred %d drivers (last ID: %d)', $updated, $new_last_id ) );
		
		return array(
			'transferred' => (int) $updated,
			'last_id' => $new_last_id,
		);
	}
	
	/**
	 * Get queue status
	 *
	 * @return array
	 */
	public function get_queue_status() {
		$queue = get_transient( self::TRANSIENT_KEY );
		
		if ( ! is_array( $queue ) || empty( $queue ) ) {
			return array(
				'total' => 0,
				'items' => array(),
			);
		}
		
		$items = array();
		foreach ( $queue as $recruiter_id => $data ) {
			$progress = $data['drivers_progress'];
			$progress_percent = $progress['total'] > 0 
				? ( $progress['processed'] / $progress['total'] ) * 100 
				: 0;
			
			$items[] = array(
				'recruiter_id' => $recruiter_id,
				'new_recruiter_id' => $data['new_recruiter_id'],
				'drivers_total' => $progress['total'],
				'drivers_processed' => $progress['processed'],
				'drivers_remaining' => $progress['total'] - $progress['processed'],
				'progress_percent' => $progress_percent,
				'created_at' => isset( $data['created_at'] ) ? $data['created_at'] : 0,
			);
		}
		
		return array(
			'total' => count( $queue ),
			'items' => $items,
		);
	}
	
	/**
	 * Update summary statistics
	 *
	 * @return void
	 */
	public function update_summary() {
		$queue = get_transient( self::TRANSIENT_KEY );
		
		if ( ! is_array( $queue ) || empty( $queue ) ) {
			set_transient( self::TRANSIENT_SUMMARY_KEY, array(
				'total_recruiters' => 0,
				'total_drivers' => 0,
				'processed_drivers' => 0,
				'remaining_drivers' => 0,
				'updated_at' => time(),
			), 0 );
			return;
		}
		
		$total_drivers = 0;
		$processed_drivers = 0;
		
		foreach ( $queue as $data ) {
			$total_drivers += $data['drivers_progress']['total'];
			$processed_drivers += $data['drivers_progress']['processed'];
		}
		
		set_transient( self::TRANSIENT_SUMMARY_KEY, array(
			'total_recruiters' => count( $queue ),
			'total_drivers' => $total_drivers,
			'processed_drivers' => $processed_drivers,
			'remaining_drivers' => $total_drivers - $processed_drivers,
			'updated_at' => time(),
		), 0 );
	}
	
	/**
	 * Get summary statistics
	 *
	 * @return array
	 */
	public function get_summary() {
		$summary = get_transient( self::TRANSIENT_SUMMARY_KEY );
		
		if ( ! is_array( $summary ) ) {
			return array(
				'total_recruiters' => 0,
				'total_drivers' => 0,
				'processed_drivers' => 0,
				'remaining_drivers' => 0,
				'updated_at' => 0,
			);
		}
		
		return $summary;
	}
}
