<?php
/**
 * Class for managing gradual transfer of dispatcher data
 * Handles transfer of loads and contacts from deleted dispatchers to new dispatcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TMSDispatcherTransferManager {
	
	const TRANSIENT_KEY = 'remove_dispatchers';
	const TRANSIENT_SUMMARY_KEY = 'remove_dispatchers_summary';
	const TRANSIENT_LOG_KEY = 'remove_dispatchers_log';
	const BATCH_SIZE_LOADS = 100; // Number of load records to transfer per batch
	const BATCH_SIZE_CONTACTS = 50; // Number of contacts to transfer per batch
	const MAX_LOG_ENTRIES = 500; // Maximum number of log entries to keep
	
	/**
	 * Log message to separate log file
	 *
	 * @param string $message Message to log
	 * @return void
	 */
	private function log_to_file( $message ) {
		if ( class_exists( 'TMSLogger' ) ) {
			TMSLogger::log_to_file( $message, 'dispatcher-transfer' );
		}
	}
	
	/**
	 * Add dispatcher ID to queue for gradual transfer
	 *
	 * @param int $dispatcher_id Dispatcher user ID to transfer
	 * @param string $project_type Project type: 'odysseia' or 'flt'
	 * @return bool|WP_Error
	 */
	public function add_dispatcher_to_queue( $dispatcher_id, $project_type = 'odysseia' ) {
		$dispatcher_id = absint( $dispatcher_id );
		
		if ( ! $dispatcher_id ) {
			return new WP_Error( 'invalid_id', 'Invalid dispatcher ID' );
		}
		
		// Get new dispatcher ID from global options
		global $global_options;
		$new_dispatcher_id = get_field_value( $global_options, 'empty_dispatcher' );
		
		if ( ! $new_dispatcher_id || $new_dispatcher_id === $dispatcher_id ) {
			return new WP_Error( 'invalid_new_dispatcher', 'Invalid or same new dispatcher ID' );
		}
		
		// Get current queue
		$queue = get_transient( self::TRANSIENT_KEY );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}
		
		// Use composite key to support both projects for same dispatcher
		$queue_key = $dispatcher_id . '_' . $project_type;
		
		// Check if already in queue for this project type
		if ( isset( $queue[ $queue_key ] ) ) {
			return true; // Already in queue for this project type
		}
		
		// Get all tables that need to be processed
		$tables_data = $this->get_all_tables_data( $dispatcher_id, $project_type );
		
		// Check if there are any records to transfer
		$has_records = false;
		foreach ( $tables_data as $records ) {
			if ( ! empty( $records ) ) {
				$has_records = true;
				break;
			}
		}
		
		// Check contacts count for any project (contacts are shared, but we need to process them at least once)
		// Priority: odysseia > first project with loads
		$contacts_count = 0;
		$should_process_contacts = false;
		
		// Check if odysseia already exists in queue for this dispatcher
		$odysseia_key = $dispatcher_id . '_odysseia';
		$odysseia_exists = isset( $queue[ $odysseia_key ] );
		
		// Get contacts count
		$contacts_count = $this->get_contacts_count( $dispatcher_id );
		
		if ( $contacts_count > 0 ) {
			$has_records = true;
			
			// Process contacts for this project if:
			// 1. It's odysseia (always preferred), OR
			// 2. It's not odysseia, but odysseia doesn't exist in queue (so this will be the first/only project)
			if ( $project_type === 'odysseia' ) {
				$should_process_contacts = true;
			} elseif ( ! $odysseia_exists ) {
				// Check if any other project already processes contacts
				$other_project_processes_contacts = false;
				foreach ( $queue as $key => $queue_data ) {
					if ( strpos( $key, $dispatcher_id . '_' ) === 0 && 
						 isset( $queue_data['should_process_contacts'] ) && 
						 $queue_data['should_process_contacts'] ) {
						$other_project_processes_contacts = true;
						break;
					}
				}
				// If no other project processes contacts, this one should
				if ( ! $other_project_processes_contacts ) {
					$should_process_contacts = true;
				}
			}
		}
		
		// If no records to transfer, don't add to queue
		if ( ! $has_records ) {
			return true; // Nothing to transfer
		}
		
		// Initialize dispatcher data
		$queue[ $queue_key ] = array(
			'dispatcher_id' => $dispatcher_id,
			'project_type' => $project_type,
			'tables_progress' => array(),
			'contacts_progress' => array(
				'total' => $contacts_count,
				'processed' => 0,
			),
			'should_process_contacts' => $should_process_contacts, // Flag to indicate if this project should process contacts
			'new_dispatcher_id' => absint( $new_dispatcher_id ),
			'created_at' => time(),
		);
		
		$this->log_to_file( sprintf( 'Added to queue - Dispatcher ID: %d, Project: %s, Contacts: %d, Should process contacts: %s', 
			$dispatcher_id,
			$project_type,
			$contacts_count,
			$should_process_contacts ? 'YES' : 'NO'
		) );
		
		// Initialize progress for each table
		foreach ( $tables_data as $table_name => $records ) {
			if ( ! empty( $records ) ) {
				$queue[ $queue_key ]['tables_progress'][ $table_name ] = array(
					'total' => count( $records ),
					'processed' => 0,
					'last_id' => 0,
					'records' => $records, // Store all record IDs for batch processing
				);
			}
		}
		
		// Save queue
		$saved = set_transient( self::TRANSIENT_KEY, $queue, 0 ); // No expiration
		$this->log_to_file( sprintf( 'Queue saved to transient: %s (key: %s)', $saved ? 'SUCCESS' : 'FAILED', self::TRANSIENT_KEY ) );
		
		// Verify it was saved
		$verify = get_transient( self::TRANSIENT_KEY );
		$this->log_to_file( sprintf( 'Queue verification: %s items in transient', is_array( $verify ) ? count( $verify ) : 'not an array' ) );
		
		// Log addition to queue
		$log_message = sprintf(
			'[ADDED] Dispatcher ID %d (%s) added to queue - Loads: %d, Contacts: %d',
			$dispatcher_id,
			$project_type,
			array_sum( array_map( function( $progress ) { return $progress['total']; }, $queue[ $queue_key ]['tables_progress'] ) ),
			$contacts_count
		);
		$this->log_message( $log_message, 'info' );
		$this->log_to_file( $log_message );
		
		// Update summary
		$this->update_summary();
		
		return true;
	}
	
	/**
	 * Process pending transfers
	 *
	 * @return void
	 */
	public function process_pending_transfers() {
		$queue = get_transient( self::TRANSIENT_KEY );
		
		if ( ! is_array( $queue ) || empty( $queue ) ) {
			return; // No dispatchers to process
		}
		
		$log_msg = sprintf( '[START] Processing queue - %d dispatcher(s) pending', count( $queue ) );
		$this->log_message( $log_msg, 'info' );
		$this->log_to_file( $log_msg );
		
		// Process one dispatcher per cron run to avoid timeout
		foreach ( $queue as $queue_key => $data ) {
			$this->log_to_file( sprintf( 'Processing queue key: %s, dispatcher_id: %d, project_type: %s', 
				$queue_key, 
				isset( $data['dispatcher_id'] ) ? $data['dispatcher_id'] : 'NOT SET',
				isset( $data['project_type'] ) ? $data['project_type'] : 'NOT SET'
			) );
			$this->log_to_file( sprintf( 'Tables progress count: %d', 
				isset( $data['tables_progress'] ) && is_array( $data['tables_progress'] ) ? count( $data['tables_progress'] ) : 0
			) );
			
			$dispatcher_id = $data['dispatcher_id'];
			$result = $this->process_dispatcher_transfer( $dispatcher_id, $data );
			
			if ( $result['completed'] ) {
				// Remove from queue
				unset( $queue[ $queue_key ] );
				set_transient( self::TRANSIENT_KEY, $queue, 0 );
				
				$log_message = sprintf(
					'[COMPLETED] Dispatcher ID %d (%s) - Loads: %d, Contacts: %d',
					$dispatcher_id,
					$data['project_type'],
					$result['loads_transferred'],
					$result['contacts_transferred']
				);
			$this->log_message( $log_message, 'success' );
			$this->log_to_file( $log_message );
			} else {
				// Update progress
				$queue[ $queue_key ] = $data;
				set_transient( self::TRANSIENT_KEY, $queue, 0 );
				
				// Calculate total processed loads from all tables
				$total_processed_loads = 0;
				foreach ( $data['tables_progress'] as $progress ) {
					$total_processed_loads += $progress['processed'];
				}
				
				$log_message = sprintf(
					'[PROGRESS] Dispatcher ID %d (%s) - Loads: %d/%d (batch: +%d), Contacts: %d/%d (%.1f%%)',
					$dispatcher_id,
					$data['project_type'],
					$total_processed_loads,
					$result['total_loads'],
					$result['loads_transferred'],
					isset( $data['contacts_progress'] ) ? $data['contacts_progress']['processed'] : 0,
					isset( $data['contacts_progress'] ) ? $data['contacts_progress']['total'] : 0,
					$result['progress_percent']
				);
				$this->log_message( $log_message, 'info' );
				$this->log_to_file( $log_message );
			}
			
			// Update summary
			$this->update_summary();
			
			// Process only one dispatcher per cron run
			break;
		}
	}
	
	/**
	 * Process transfer for one dispatcher
	 *
	 * @param int $dispatcher_id
	 * @param array $data Dispatcher data from queue
	 * @return array Result with completion status
	 */
	private function process_dispatcher_transfer( $dispatcher_id, &$data ) {
		$this->log_to_file( sprintf( 'process_dispatcher_transfer() started - Dispatcher ID: %d, Project: %s', $dispatcher_id, $data['project_type'] ) );
		
		$new_dispatcher_id = $data['new_dispatcher_id'];
		$project_type = $data['project_type'];
		$loads_transferred = 0;
		$total_loads = 0;
		$contacts_transferred = 0;
		
		// Process loads (tables)
		foreach ( $data['tables_progress'] as $table_name => &$progress ) {
			$total_loads += $progress['total'];
			
			$this->log_to_file( sprintf( 'Table %s - Total: %d, Processed: %d', 
				$table_name, 
				$progress['total'], 
				$progress['processed']
			) );
			
			if ( $progress['processed'] >= $progress['total'] ) {
				$this->log_to_file( sprintf( 'Table %s already fully processed, skipping', $table_name ) );
				continue; // Table already processed
			}
			
			// Get batch of records to process
			$batch = array_slice( $progress['records'], $progress['processed'], self::BATCH_SIZE_LOADS );
			
			if ( empty( $batch ) ) {
				continue;
			}
			
			// Transfer batch
			$transferred = $this->transfer_loads_batch( $table_name, $batch, $new_dispatcher_id );
			
			if ( $transferred > 0 ) {
				$progress['processed'] += $transferred;
				$loads_transferred += $transferred;
				
				// Update last processed ID
				if ( ! empty( $batch ) ) {
					$last_record = end( $batch );
					$progress['last_id'] = isset( $last_record['id'] ) ? intval( $last_record['id'] ) : 0;
				}
			}
		}
		
		// Check if all loads are done (if no loads, consider them done)
		$all_loads_done = true;
		if ( ! empty( $data['tables_progress'] ) ) {
			foreach ( $data['tables_progress'] as $progress ) {
				if ( $progress['processed'] < $progress['total'] ) {
					$all_loads_done = false;
					break;
				}
			}
		}
		// If no loads at all (empty tables_progress), loads are considered done
		if ( empty( $data['tables_progress'] ) ) {
			$all_loads_done = true;
			$this->log_to_file( 'No loads to process, all loads considered done' );
		}
		
		// Process contacts if loads are done (for the project that should process contacts)
		$should_process_contacts = isset( $data['should_process_contacts'] ) ? $data['should_process_contacts'] : false;
		
		$this->log_to_file( sprintf( 'Should process contacts: %s, All loads done: %s, Project: %s', 
			$should_process_contacts ? 'YES' : 'NO',
			$all_loads_done ? 'YES' : 'NO',
			$project_type
		) );
		
		if ( $all_loads_done && $should_process_contacts ) {
			$contacts_progress = isset( $data['contacts_progress'] ) ? $data['contacts_progress'] : array( 'total' => 0, 'processed' => 0 );
			
			$this->log_to_file( sprintf( 'Contacts progress: %d/%d', 
				$contacts_progress['processed'],
				$contacts_progress['total']
			) );
			
			if ( $contacts_progress['processed'] < $contacts_progress['total'] ) {
				// Transfer contacts in batches (same logic as original move_contacts_for_new_dispatcher)
				$contacts_result = $this->transfer_contacts_batch( $dispatcher_id, $new_dispatcher_id );
				$this->log_to_file( sprintf( 'Contacts batch result: %d transferred', $contacts_result['transferred'] ) );
				
				if ( $contacts_result['transferred'] > 0 ) {
					$contacts_progress['processed'] += $contacts_result['transferred'];
					$contacts_transferred = $contacts_result['transferred'];
					$data['contacts_progress'] = $contacts_progress;
					$this->log_to_file( sprintf( 'Contacts batch transferred: %d (total processed: %d/%d) for project %s', 
						$contacts_result['transferred'],
						$contacts_progress['processed'],
						$contacts_progress['total'],
						$project_type
					) );
				} else {
					$this->log_to_file( 'WARNING: No contacts were transferred, but contacts remain to be processed' );
				}
			} else {
				$this->log_to_file( 'All contacts already processed' );
			}
		} else {
			// For projects that don't process contacts, check if contacts were already processed by another project
			if ( ! $should_process_contacts ) {
				// Check if contacts were already processed for this dispatcher (by odysseia or another project)
				$contacts_count_remaining = $this->get_contacts_count( $dispatcher_id );
				
				if ( $contacts_count_remaining == 0 ) {
					// Contacts were already processed by another project, mark as done
					$data['contacts_progress'] = array( 'total' => 0, 'processed' => 0 );
					$this->log_to_file( sprintf( 'Project %s: Contacts already processed by another project, marking as done', $project_type ) );
				} else {
					// Contacts still exist, but this project shouldn't process them
					// Keep the original contacts_progress to show in status, but mark as done for completion check
					$this->log_to_file( sprintf( 'Project %s should not process contacts (will be processed by another project)', $project_type ) );
				}
			} elseif ( ! $all_loads_done ) {
				$this->log_to_file( sprintf( 'Loads not done yet, waiting before processing contacts for project %s', $project_type ) );
			}
		}
		
		// Check if all done
		// For projects that don't process contacts, if contacts were already processed (count = 0), consider them done
		$contacts_done = true;
		if ( isset( $data['contacts_progress'] ) && $data['contacts_progress']['total'] > 0 ) {
			if ( ! $should_process_contacts ) {
				// Check if contacts were already processed by another project
				$contacts_count_remaining = $this->get_contacts_count( $dispatcher_id );
				$contacts_done = ( $contacts_count_remaining == 0 );
				$this->log_to_file( sprintf( 'Project %s: Contacts done check - remaining: %d, done: %s', 
					$project_type, 
					$contacts_count_remaining,
					$contacts_done ? 'YES' : 'NO'
				) );
			} else {
				$contacts_done = ( $data['contacts_progress']['processed'] >= $data['contacts_progress']['total'] );
			}
		} elseif ( ! $should_process_contacts && $all_loads_done ) {
			// If this project doesn't process contacts but loads are done, check if contacts exist
			// If no contacts exist for this dispatcher, they were already processed by another project
			$contacts_count_remaining = $this->get_contacts_count( $dispatcher_id );
			$contacts_done = ( $contacts_count_remaining == 0 );
			$this->log_to_file( sprintf( 'Project %s: No contacts progress data, checking remaining contacts: %d, done: %s', 
				$project_type,
				$contacts_count_remaining,
				$contacts_done ? 'YES' : 'NO'
			) );
		}
		
		$completed = $all_loads_done && $contacts_done;
		
		$this->log_to_file( sprintf( 'Completion check - All loads done: %s, Contacts done: %s, Completed: %s', 
			$all_loads_done ? 'YES' : 'NO',
			$contacts_done ? 'YES' : 'NO',
			$completed ? 'YES' : 'NO'
		) );
		
		// Calculate total processed loads from all tables
		$total_processed_loads = 0;
		foreach ( $data['tables_progress'] as $progress ) {
			$total_processed_loads += $progress['processed'];
		}
		
		// Calculate progress percent
		$total_items = $total_loads + ( isset( $data['contacts_progress'] ) ? $data['contacts_progress']['total'] : 0 );
		$processed_items = $total_processed_loads + ( isset( $data['contacts_progress'] ) ? $data['contacts_progress']['processed'] : 0 );
		$progress_percent = $total_items > 0 ? round( ( $processed_items / $total_items ) * 100, 2 ) : 0;
		
		return array(
			'completed' => $completed,
			'loads_transferred' => $loads_transferred,
			'total_loads' => $total_loads,
			'contacts_transferred' => $contacts_transferred,
			'progress_percent' => $progress_percent,
		);
	}
	
	/**
	 * Get all tables data for dispatcher
	 *
	 * @param int $dispatcher_id
	 * @param string $project_type
	 * @return array Array of table_name => records
	 */
	private function get_all_tables_data( $dispatcher_id, $project_type ) {
		global $wpdb;
		$results = array();
		
		// Get table prefix based on project type
		$table_prefix = $project_type === 'flt' ? 'reportsmeta_flt_' : 'reportsmeta_';
		
		// Get tables list from helper (both classes extend TMSReportsHelper)
		$helper = new TMSReportsHelper();
		$tables = $helper->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_meta_name = $wpdb->prefix . $table_prefix . strtolower( $val );
			
			// Get all records for this dispatcher
			$query = $wpdb->prepare(
				"SELECT id FROM $table_meta_name
				WHERE meta_key = %s AND meta_value = %s
				ORDER BY id ASC",
				'dispatcher_initials',
				$dispatcher_id
			);
			
			$table_results = $wpdb->get_results( $query, ARRAY_A );
			
			if ( ! empty( $table_results ) ) {
				$results[ $table_meta_name ] = $table_results;
			}
		}
		
		return $results;
	}
	
	/**
	 * Transfer batch of loads
	 *
	 * @param string $table_name
	 * @param array $batch Array of records with 'id'
	 * @param int $new_dispatcher_id
	 * @return int Number of transferred records
	 */
	private function transfer_loads_batch( $table_name, $batch, $new_dispatcher_id ) {
		global $wpdb;
		
		if ( empty( $batch ) || ! is_array( $batch ) ) {
			return 0;
		}
		
		$ids = array_column( $batch, 'id' );
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );
		
		if ( empty( $ids ) ) {
			return 0;
		}
		
		$table_name = esc_sql( $table_name );
		$ids_string = implode( ',', $ids );
		
		$query = $wpdb->prepare(
			"UPDATE $table_name
			SET meta_value = %s
			WHERE id IN ($ids_string)
			AND meta_key = 'dispatcher_initials'",
			$new_dispatcher_id
		);
		
		$result = $wpdb->query( $query );
		
		if ( $result === false ) {
			$error_message = sprintf(
				'[ERROR] Failed to transfer loads batch in table %s: %s',
				$table_name,
				$wpdb->last_error
			);
			$this->log_message( $error_message, 'error' );
			$this->log_to_file( $error_message );
		}
		
		return $result !== false ? intval( $result ) : 0;
	}
	
	/**
	 * Get contacts count for dispatcher
	 *
	 * @param int $dispatcher_id
	 * @return int
	 */
	private function get_contacts_count( $dispatcher_id ) {
		global $wpdb;
		$table_contacts = $wpdb->prefix . 'contacts';
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_contacts WHERE user_id_added = %d",
			$dispatcher_id
		) );
		
		return intval( $count );
	}
	
	/**
	 * Transfer contacts in batches
	 *
	 * @param int $old_dispatcher_id
	 * @param int $new_dispatcher_id
	 * @return array Result with transferred count
	 */
	private function transfer_contacts_batch( $old_dispatcher_id, $new_dispatcher_id ) {
		global $wpdb;
		
		$table_contacts = $wpdb->prefix . 'contacts';
		
		// Check how many contacts exist before transfer
		$contacts_before = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_contacts WHERE user_id_added = %d",
			$old_dispatcher_id
		) );
		
		$this->log_to_file( sprintf( 'transfer_contacts_batch - Contacts before: %d, Old dispatcher: %d, New dispatcher: %d', 
			$contacts_before,
			$old_dispatcher_id,
			$new_dispatcher_id
		) );
		
		// Transfer batch using LIMIT
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE $table_contacts
			SET user_id_added = %d
			WHERE user_id_added = %d
			LIMIT %d",
			$new_dispatcher_id,
			$old_dispatcher_id,
			self::BATCH_SIZE_CONTACTS
		) );
		
		if ( $updated === false ) {
			$error_message = sprintf(
				'[ERROR] Failed to transfer contacts batch: %s',
				$wpdb->last_error
			);
			$this->log_message( $error_message, 'error' );
			$this->log_to_file( $error_message );
		} else {
			$this->log_to_file( sprintf( 'transfer_contacts_batch - Updated %d contacts', $updated ) );
		}
		
		return array(
			'transferred' => $updated !== false ? intval( $updated ) : 0,
		);
	}
	
	/**
	 * Remove dispatcher from queue (manual cleanup if needed)
	 *
	 * @param int $dispatcher_id
	 * @return bool
	 */
	public function remove_dispatcher_from_queue( $dispatcher_id ) {
		$queue = get_transient( self::TRANSIENT_KEY );
		
		if ( ! is_array( $queue ) ) {
			return false;
		}
		
		if ( isset( $queue[ $dispatcher_id ] ) ) {
			unset( $queue[ $dispatcher_id ] );
			set_transient( self::TRANSIENT_KEY, $queue, 0 );
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get queue status
	 *
	 * @return array Queue status
	 */
	public function get_queue_status() {
		$queue = get_transient( self::TRANSIENT_KEY );
		
		if ( ! is_array( $queue ) || empty( $queue ) ) {
			return array();
		}
		
		$status = array();
		foreach ( $queue as $queue_key => $data ) {
			$dispatcher_id = $data['dispatcher_id'];
			$total_loads = 0;
			$processed_loads = 0;
			
			foreach ( $data['tables_progress'] as $progress ) {
				$total_loads += $progress['total'];
				$processed_loads += $progress['processed'];
			}
			
			$contacts_progress = isset( $data['contacts_progress'] ) ? $data['contacts_progress'] : array( 'total' => 0, 'processed' => 0 );
			$total_items = $total_loads + $contacts_progress['total'];
			$processed_items = $processed_loads + $contacts_progress['processed'];
			
			$status[ $queue_key ] = array(
				'dispatcher_id' => $dispatcher_id,
				'project_type' => $data['project_type'],
				'new_dispatcher_id' => $data['new_dispatcher_id'],
				'total_loads' => $total_loads,
				'processed_loads' => $processed_loads,
				'total_contacts' => $contacts_progress['total'],
				'processed_contacts' => $contacts_progress['processed'],
				'created_at' => $data['created_at'],
				'progress_percent' => $total_items > 0 ? round( ( $processed_items / $total_items ) * 100, 2 ) : 0,
			);
		}
		
		return $status;
	}
	
	/**
	 * Log message to transient log
	 *
	 * @param string $message
	 * @param string $type success|info|error|warning
	 * @return void
	 */
	private function log_message( $message, $type = 'info' ) {
		$log = get_transient( self::TRANSIENT_LOG_KEY );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'time' => time(),
			'message' => $message,
			'type' => $type,
		);
		
		// Add to beginning of array
		array_unshift( $log, $log_entry );
		
		// Keep only last MAX_LOG_ENTRIES entries
		if ( count( $log ) > self::MAX_LOG_ENTRIES ) {
			$log = array_slice( $log, 0, self::MAX_LOG_ENTRIES );
		}
		
		set_transient( self::TRANSIENT_LOG_KEY, $log, 0 );
	}
	
	/**
	 * Get log entries
	 *
	 * @param int $limit Number of entries to return
	 * @return array
	 */
	public function get_log( $limit = 100 ) {
		$log = get_transient( self::TRANSIENT_LOG_KEY );
		if ( ! is_array( $log ) ) {
			return array();
		}
		
		return array_slice( $log, 0, $limit );
	}
	
	/**
	 * Clear log
	 *
	 * @return bool
	 */
	public function clear_log() {
		return delete_transient( self::TRANSIENT_LOG_KEY );
	}
	
	/**
	 * Update summary statistics
	 *
	 * @return void
	 */
	public function update_summary() {
		$queue = get_transient( self::TRANSIENT_KEY );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}
		
		$summary = array(
			'last_updated' => time(),
			'total_dispatchers' => count( $queue ),
			'by_project' => array(
				'odysseia' => array(
					'count' => 0,
					'total_loads' => 0,
					'processed_loads' => 0,
					'total_contacts' => 0,
					'processed_contacts' => 0,
				),
				'flt' => array(
					'count' => 0,
					'total_loads' => 0,
					'processed_loads' => 0,
					'total_contacts' => 0,
					'processed_contacts' => 0,
				),
			),
			'by_table' => array(
				'odysseia' => array(),
				'flt' => array(),
			),
		);
		
		$helper = new TMSReportsHelper();
		$tables = $helper->tms_tables;
		
		// Initialize table counters
		foreach ( $tables as $table ) {
			$table_lower = strtolower( $table );
			$summary['by_table']['odysseia'][ $table_lower ] = array(
				'total' => 0,
				'processed' => 0,
			);
			$summary['by_table']['flt'][ $table_lower ] = array(
				'total' => 0,
				'processed' => 0,
			);
		}
		
		foreach ( $queue as $queue_key => $data ) {
			$project_type = $data['project_type'];
			$summary['by_project'][ $project_type ]['count']++;
			
			// Process loads
			foreach ( $data['tables_progress'] as $table_name => $progress ) {
				$summary['by_project'][ $project_type ]['total_loads'] += $progress['total'];
				$summary['by_project'][ $project_type ]['processed_loads'] += $progress['processed'];
				
				// Extract table name from full table name (e.g., wp_reportsmeta_odysseia -> odysseia)
				foreach ( $tables as $table ) {
					$table_lower = strtolower( $table );
					if ( strpos( $table_name, $table_lower ) !== false ) {
						$summary['by_table'][ $project_type ][ $table_lower ]['total'] += $progress['total'];
						$summary['by_table'][ $project_type ][ $table_lower ]['processed'] += $progress['processed'];
						break;
					}
				}
			}
			
			// Process contacts
			if ( isset( $data['contacts_progress'] ) ) {
				$summary['by_project'][ $project_type ]['total_contacts'] += $data['contacts_progress']['total'];
				$summary['by_project'][ $project_type ]['processed_contacts'] += $data['contacts_progress']['processed'];
			}
		}
		
		// Calculate totals
		$summary['grand_total'] = array(
			'total_loads' => $summary['by_project']['odysseia']['total_loads'] + $summary['by_project']['flt']['total_loads'],
			'processed_loads' => $summary['by_project']['odysseia']['processed_loads'] + $summary['by_project']['flt']['processed_loads'],
			'total_contacts' => $summary['by_project']['odysseia']['total_contacts'] + $summary['by_project']['flt']['total_contacts'],
			'processed_contacts' => $summary['by_project']['odysseia']['processed_contacts'] + $summary['by_project']['flt']['processed_contacts'],
		);
		
		$summary['grand_total']['remaining_loads'] = $summary['grand_total']['total_loads'] - $summary['grand_total']['processed_loads'];
		$summary['grand_total']['remaining_contacts'] = $summary['grand_total']['total_contacts'] - $summary['grand_total']['processed_contacts'];
		
		if ( $summary['grand_total']['total_loads'] + $summary['grand_total']['total_contacts'] > 0 ) {
			$summary['grand_total']['progress_percent'] = round(
				( ( $summary['grand_total']['processed_loads'] + $summary['grand_total']['processed_contacts'] ) /
				  ( $summary['grand_total']['total_loads'] + $summary['grand_total']['total_contacts'] ) ) * 100,
				2
			);
		} else {
			$summary['grand_total']['progress_percent'] = 0;
		}
		
		set_transient( self::TRANSIENT_SUMMARY_KEY, $summary, 0 );
	}
	
	/**
	 * Get summary statistics
	 *
	 * @return array
	 */
	public function get_summary() {
		$summary = get_transient( self::TRANSIENT_SUMMARY_KEY );
		if ( ! is_array( $summary ) ) {
			$this->update_summary();
			$summary = get_transient( self::TRANSIENT_SUMMARY_KEY );
		}
		return $summary;
	}
}

/**
 * Helper function to log dispatcher transfer messages to separate file
 * (Backward compatibility wrapper for TMSLogger)
 *
 * @param string $message Message to log
 * @return void
 */
function tms_dispatcher_transfer_log( $message ) {
	if ( class_exists( 'TMSLogger' ) ) {
		TMSLogger::log_to_file( $message, 'dispatcher-transfer' );
	}
}
