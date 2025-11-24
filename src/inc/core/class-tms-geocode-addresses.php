<?php
/**
 * Class for bulk geocoding addresses in shipper and company tables
 */
class TMSGeocodeAddresses {
	
	/**
	 * Add latitude and longitude columns to existing tables if they don't exist
	 */
	public function add_lat_lon_columns() {
		global $wpdb;
		
		$shipper_table = $wpdb->prefix . 'reports_shipper';
		$company_table = $wpdb->prefix . 'reports_company';
		
		// Check and add columns to shipper table
		$shipper_lat_exists = $wpdb->get_results( "SHOW COLUMNS FROM $shipper_table LIKE 'latitude'" );
		if ( empty( $shipper_lat_exists ) ) {
			$wpdb->query( "ALTER TABLE $shipper_table ADD COLUMN latitude decimal(10,8) NULL AFTER full_address" );
		}
		
		$shipper_lon_exists = $wpdb->get_results( "SHOW COLUMNS FROM $shipper_table LIKE 'longitude'" );
		if ( empty( $shipper_lon_exists ) ) {
			$wpdb->query( "ALTER TABLE $shipper_table ADD COLUMN longitude decimal(11,8) NULL AFTER latitude" );
		}
		
		$shipper_tz_exists = $wpdb->get_results( "SHOW COLUMNS FROM $shipper_table LIKE 'timezone'" );
		if ( empty( $shipper_tz_exists ) ) {
			$wpdb->query( "ALTER TABLE $shipper_table ADD COLUMN timezone varchar(50) NULL AFTER longitude" );
		}
		
		// Add index for shipper table
		$shipper_index_exists = $wpdb->get_results( "SHOW INDEX FROM $shipper_table WHERE Key_name = 'idx_latitude_longitude'" );
		if ( empty( $shipper_index_exists ) ) {
			$wpdb->query( "ALTER TABLE $shipper_table ADD INDEX idx_latitude_longitude (latitude, longitude)" );
		}
		
		// Check and add columns to company table
		$company_lat_exists = $wpdb->get_results( "SHOW COLUMNS FROM $company_table LIKE 'latitude'" );
		if ( empty( $company_lat_exists ) ) {
			$wpdb->query( "ALTER TABLE $company_table ADD COLUMN latitude decimal(10,8) NULL AFTER dot_number" );
		}
		
		$company_lon_exists = $wpdb->get_results( "SHOW COLUMNS FROM $company_table LIKE 'longitude'" );
		if ( empty( $company_lon_exists ) ) {
			$wpdb->query( "ALTER TABLE $company_table ADD COLUMN longitude decimal(11,8) NULL AFTER latitude" );
		}
		
		$company_tz_exists = $wpdb->get_results( "SHOW COLUMNS FROM $company_table LIKE 'timezone'" );
		if ( empty( $company_tz_exists ) ) {
			$wpdb->query( "ALTER TABLE $company_table ADD COLUMN timezone varchar(50) NULL AFTER longitude" );
		}
		
		// Add index for company table
		$company_index_exists = $wpdb->get_results( "SHOW INDEX FROM $company_table WHERE Key_name = 'idx_latitude_longitude'" );
		if ( empty( $company_index_exists ) ) {
			$wpdb->query( "ALTER TABLE $company_table ADD INDEX idx_latitude_longitude (latitude, longitude)" );
		}
	}
	
	/**
	 * Geocode all shipper addresses that don't have coordinates
	 * 
	 * @param int $limit Number of records to process per batch
	 * @param bool $force_regeocode If true, re-geocode even addresses that already have coordinates
	 * @return array Statistics about the geocoding process
	 */
	public function geocode_shippers( $limit = 50, $force_regeocode = false ) {
		global $wpdb;
		
		$Drivers = new TMSDrivers();
		$Shipper = new TMSReportsShipper();
		global $global_options;
		
		$api_key_here_map = get_field_value( $global_options, 'api_key_here_map' );
		$geocoder = get_field_value( $global_options, 'use_geocoder' );
		$url_pelias = get_field_value( $global_options, 'url_pelias' );
		
		$table_name = $wpdb->prefix . 'reports_shipper';
		
		// Get failed addresses to exclude
		$failed_addresses = $this->get_failed_addresses('shippers');
		$exclude_ids = !empty($failed_addresses) ? array_map('intval', array_keys($failed_addresses)) : array();
		
		// Get records without coordinates or timezone, or force regeocode
		$where_clause = $force_regeocode ? '1=1' : '((latitude IS NULL OR longitude IS NULL) OR timezone IS NULL)';
		$exclude_clause = !empty($exclude_ids) ? 'AND id NOT IN (' . implode(',', $exclude_ids) . ')' : '';
		
		$query = $wpdb->prepare( "
			SELECT id, full_address, country, zip_code, latitude, longitude, state
			FROM $table_name
			WHERE $where_clause
			AND full_address IS NOT NULL
			AND full_address != ''
			$exclude_clause
			LIMIT %d
		", $limit );
		
		$records = $wpdb->get_results( $query );
		
		$stats = array(
			'total' => count( $records ),
			'success' => 0,
			'failed' => 0,
			'errors' => array()
		);
		
		foreach ( $records as $record ) {
			$needs_coordinates = empty( $record->latitude ) || empty( $record->longitude );
			$needs_timezone = empty( $record->timezone );
			
			// If we have coordinates but need timezone, use existing coordinates
			if ( ! $needs_coordinates && $needs_timezone ) {
				// Get timezone using existing coordinates
				$helper = new TMSReportsHelper();
				$timezone = $helper->get_timezone_by_coordinates( $record->latitude, $record->longitude, date( 'Y-m-d' ) );
				
				// Fallback to state-based timezone if HERE API fails
				if ( empty( $timezone ) && ! empty( $record->state ) ) {
					$timezone = $helper->get_timezone_by_state( $record->state, date( 'Y-m-d' ), $record->latitude, $record->longitude );
				}
				
				if ( ! empty( $timezone ) ) {
					$result = $wpdb->update(
						$table_name,
						array( 'timezone' => $timezone ),
						array( 'id' => $record->id ),
						array( '%s' ),
						array( '%d' )
					);
					
					if ( $result !== false ) {
						$stats['success']++;
						// Remove from failed list if it was there
						$this->remove_failed_address('shippers', $record->id);
					} else {
						$stats['failed']++;
						$stats['errors'][] = "Record ID {$record->id}: Database update failed - " . $wpdb->last_error;
					}
				} else {
					$stats['failed']++;
					$error_msg = "Timezone lookup failed";
					if ( empty( $record->state ) ) {
						$error_msg .= " (no state available for fallback)";
					}
					$stats['errors'][] = "Record ID {$record->id}: " . $error_msg;
					
					// Add to failed addresses list to avoid repeated attempts
					$address = ! empty( $record->full_address ) ? $record->full_address : 'Coordinates: ' . $record->latitude . ', ' . $record->longitude;
					$this->add_failed_address('shippers', $record->id, $address, $error_msg);
				}
				
				// Small delay to avoid rate limiting (reduced for timezone-only requests)
				usleep( 50000 ); // 0.05 second delay
				continue;
			}
			
			// If we need coordinates, geocode the address
			if ( $needs_coordinates ) {
				// Use zip_code if full_address is empty
				$address = ! empty( $record->full_address ) ? $record->full_address : $record->zip_code;
				
				if ( empty( $address ) ) {
					$stats['failed']++;
					$stats['errors'][] = "Record ID {$record->id}: No address or zip code available";
					continue;
				}
				
				$options = array(
					'api_key' => $api_key_here_map,
					'url_pelias' => $url_pelias,
					'region_value' => $record->country ?? ''
				);
				
				$coordinates = $Drivers->get_coordinates_by_address( $address, $geocoder, $options );
				
				if ( $coordinates !== false && isset( $coordinates['lat'] ) && isset( $coordinates['lng'] ) ) {
					// Get timezone using HERE API
					$helper = new TMSReportsHelper();
					$timezone = $helper->get_timezone_by_coordinates( $coordinates['lat'], $coordinates['lng'], date( 'Y-m-d' ) );
					
					// Fallback to state-based timezone if HERE API fails
					if ( empty( $timezone ) && ! empty( $record->state ) ) {
						$timezone = $helper->get_timezone_by_state( $record->state, date( 'Y-m-d' ), $coordinates['lat'], $coordinates['lng'] );
					}
					
					$result = $wpdb->update(
						$table_name,
						array(
							'latitude' => $coordinates['lat'],
							'longitude' => $coordinates['lng'],
							'timezone' => $timezone
						),
						array( 'id' => $record->id ),
						array( '%f', '%f', '%s' ),
						array( '%d' )
					);
					
					if ( $result !== false ) {
						$stats['success']++;
						// Remove from failed list if it was there
						$this->remove_failed_address('shippers', $record->id);
					} else {
						$stats['failed']++;
						$stats['errors'][] = "Record ID {$record->id}: Database update failed - " . $wpdb->last_error;
					}
				} else {
					$stats['failed']++;
					$error_msg = "Geocoding failed for address: " . esc_html( $address );
					if ( ! empty( $record->country ) ) {
						$error_msg .= " (Country: " . esc_html( $record->country ) . ")";
					}
					$stats['errors'][] = "Record ID {$record->id}: " . $error_msg;
					
					// Add to failed addresses list
					$this->add_failed_address('shippers', $record->id, $address, $error_msg);
				}
				
				// Small delay to avoid rate limiting (reduced for better performance)
				usleep( 50000 ); // 0.05 second delay
			}
		}
		
		return $stats;
	}
	
	/**
	 * Geocode all company addresses that don't have coordinates
	 * 
	 * @param int $limit Number of records to process per batch
	 * @param bool $force_regeocode If true, re-geocode even addresses that already have coordinates
	 * @return array Statistics about the geocoding process
	 */
	public function geocode_companies( $limit = 50, $force_regeocode = false ) {
		global $wpdb;
		
		$Drivers = new TMSDrivers();
		global $global_options;
		
		$api_key_here_map = get_field_value( $global_options, 'api_key_here_map' );
		$geocoder = get_field_value( $global_options, 'use_geocoder' );
		$url_pelias = get_field_value( $global_options, 'url_pelias' );
		
		$table_name = $wpdb->prefix . 'reports_company';
		
		// Get failed addresses to exclude
		$failed_addresses = $this->get_failed_addresses('companies');
		$exclude_ids = !empty($failed_addresses) ? array_map('intval', array_keys($failed_addresses)) : array();
		
		// Get records without coordinates or timezone, or force regeocode
		$where_clause = $force_regeocode ? '1=1' : '((latitude IS NULL OR longitude IS NULL) OR timezone IS NULL)';
		$exclude_clause = !empty($exclude_ids) ? 'AND id NOT IN (' . implode(',', $exclude_ids) . ')' : '';
		
		$query = $wpdb->prepare( "
			SELECT id, address1, address2, city, state, zip_code, country, latitude, longitude
			FROM $table_name
			WHERE $where_clause
			AND (address1 IS NOT NULL OR zip_code IS NOT NULL)
			$exclude_clause
			LIMIT %d
		", $limit );
		
		$records = $wpdb->get_results( $query );
		
		$stats = array(
			'total' => count( $records ),
			'success' => 0,
			'failed' => 0,
			'errors' => array()
		);
		
		foreach ( $records as $record ) {
			$needs_coordinates = empty( $record->latitude ) || empty( $record->longitude );
			$needs_timezone = empty( $record->timezone );
			
			// If we have coordinates but need timezone, use existing coordinates
			if ( ! $needs_coordinates && $needs_timezone ) {
				// Get timezone using existing coordinates
				$helper = new TMSReportsHelper();
				$timezone = $helper->get_timezone_by_coordinates( $record->latitude, $record->longitude, date( 'Y-m-d' ) );
				
				// Fallback to state-based timezone if HERE API fails
				if ( empty( $timezone ) && ! empty( $record->state ) ) {
					$timezone = $helper->get_timezone_by_state( $record->state, date( 'Y-m-d' ), $record->latitude, $record->longitude );
				}
				
				if ( ! empty( $timezone ) ) {
					$result = $wpdb->update(
						$table_name,
						array( 'timezone' => $timezone ),
						array( 'id' => $record->id ),
						array( '%s' ),
						array( '%d' )
					);
					
					if ( $result !== false ) {
						$stats['success']++;
						// Remove from failed list if it was there
						$this->remove_failed_address('companies', $record->id);
					} else {
						$stats['failed']++;
						$stats['errors'][] = "Record ID {$record->id}: Database update failed - " . $wpdb->last_error;
					}
				} else {
					$stats['failed']++;
					$error_msg = "Timezone lookup failed";
					if ( empty( $record->state ) ) {
						$error_msg .= " (no state available for fallback)";
					}
					$stats['errors'][] = "Record ID {$record->id}: " . $error_msg;
					
					// Add to failed addresses list to avoid repeated attempts
					$address = ! empty( $record->address1 ) ? $record->address1 : 'Coordinates: ' . $record->latitude . ', ' . $record->longitude;
					$this->add_failed_address('companies', $record->id, $address, $error_msg);
				}
				
				// Small delay to avoid rate limiting (reduced for timezone-only requests)
				usleep( 50000 ); // 0.05 second delay
				continue;
			}
			
			// If we need coordinates, geocode the address
			if ( $needs_coordinates ) {
				// Build full address
				$st      = ! empty( $record->address1 ) ? $record->address1 . ', ' : '';
				$city    = ! empty( $record->city ) ? $record->city . ', ' : '';
				$state   = ! empty( $record->state ) ? $record->state . ' ' : '';
				$zip     = ! empty( $record->zip_code ) ? $record->zip_code : ' ';
				$country = $record->country !== 'USA' ? ' ' . $record->country : '';
				$full_address = $st . $city . $state . $zip . $country;
				
				// Fallback to zip_code if address is empty
				if ( empty( trim( $full_address ) ) && ! empty( $record->zip_code ) ) {
					$full_address = $record->zip_code;
				}
				
				if ( empty( $full_address ) ) {
					$stats['failed']++;
					$stats['errors'][] = "Record ID {$record->id}: No address or zip code available";
					continue;
				}
				
				$options = array(
					'api_key' => $api_key_here_map,
					'url_pelias' => $url_pelias,
					'region_value' => $record->country ?? ''
				);
				
				$coordinates = $Drivers->get_coordinates_by_address( $full_address, $geocoder, $options );
				
				if ( $coordinates !== false && isset( $coordinates['lat'] ) && isset( $coordinates['lng'] ) ) {
					// Get timezone using HERE API
					$helper = new TMSReportsHelper();
					$timezone = $helper->get_timezone_by_coordinates( $coordinates['lat'], $coordinates['lng'], date( 'Y-m-d' ) );
					
					// Fallback to state-based timezone if HERE API fails
					if ( empty( $timezone ) && ! empty( $record->state ) ) {
						$timezone = $helper->get_timezone_by_state( $record->state, date( 'Y-m-d' ), $coordinates['lat'], $coordinates['lng'] );
					}
					
					$result = $wpdb->update(
						$table_name,
						array(
							'latitude' => $coordinates['lat'],
							'longitude' => $coordinates['lng'],
							'timezone' => $timezone
						),
						array( 'id' => $record->id ),
						array( '%f', '%f', '%s' ),
						array( '%d' )
					);
					
					if ( $result !== false ) {
						$stats['success']++;
						// Remove from failed list if it was there
						$this->remove_failed_address('companies', $record->id);
					} else {
						$stats['failed']++;
						$stats['errors'][] = "Record ID {$record->id}: Database update failed - " . $wpdb->last_error;
					}
				} else {
					$stats['failed']++;
					$error_msg = "Geocoding failed for address: " . esc_html( $full_address );
					if ( ! empty( $record->country ) ) {
						$error_msg .= " (Country: " . esc_html( $record->country ) . ")";
					}
					$stats['errors'][] = "Record ID {$record->id}: " . $error_msg;
					
					// Add to failed addresses list
					$this->add_failed_address('companies', $record->id, $full_address, $error_msg);
				}
				
				// Small delay to avoid rate limiting (reduced for better performance)
				usleep( 50000 ); // 0.05 second delay
			}
		}
		
		return $stats;
	}
	
	/**
	 * Get count of records that need geocoding or timezone
	 * 
	 * @return array Counts for shippers and companies
	 */
	public function get_geocoding_stats() {
		global $wpdb;
		
		$shipper_table = $wpdb->prefix . 'reports_shipper';
		$company_table = $wpdb->prefix . 'reports_company';
		
		// Get failed addresses to exclude
		$failed_shippers = $this->get_failed_addresses('shippers');
		$failed_companies = $this->get_failed_addresses('companies');
		
		$shipper_exclude = !empty($failed_shippers) ? 'AND id NOT IN (' . implode(',', array_map('intval', array_keys($failed_shippers))) . ')' : '';
		$company_exclude = !empty($failed_companies) ? 'AND id NOT IN (' . implode(',', array_map('intval', array_keys($failed_companies))) . ')' : '';
		
		// Count shippers that need coordinates OR timezone
		$shipper_count = $wpdb->get_var( "
			SELECT COUNT(*) 
			FROM $shipper_table 
			WHERE (
				(latitude IS NULL OR longitude IS NULL)
				OR timezone IS NULL
			)
			AND full_address IS NOT NULL 
			AND full_address != ''
			$shipper_exclude
		" );
		
		// Count companies that need coordinates OR timezone
		$company_count = $wpdb->get_var( "
			SELECT COUNT(*) 
			FROM $company_table 
			WHERE (
				(latitude IS NULL OR longitude IS NULL)
				OR timezone IS NULL
			)
			AND (address1 IS NOT NULL OR zip_code IS NOT NULL)
			$company_exclude
		" );
		
		return array(
			'shippers' => (int) $shipper_count,
			'companies' => (int) $company_count
		);
	}
	
	/**
	 * Get failed addresses from transient
	 * 
	 * @param string $type 'shippers' or 'companies'
	 * @return array Array of failed addresses with record ID as key
	 */
	public function get_failed_addresses($type = 'shippers') {
		$transient_key = 'tms_geocode_failed_' . $type;
		$failed = get_transient($transient_key);
		return $failed ? $failed : array();
	}
	
	/**
	 * Add failed address to transient
	 * 
	 * @param string $type 'shippers' or 'companies'
	 * @param int $record_id Record ID
	 * @param string $address Address that failed
	 * @param string $error Error message
	 */
	public function add_failed_address($type, $record_id, $address, $error = '') {
		$transient_key = 'tms_geocode_failed_' . $type;
		$failed = $this->get_failed_addresses($type);
		
		$failed[$record_id] = array(
			'address' => $address,
			'error' => $error,
			'date' => current_time('mysql')
		);
		
		// Store for 1 year (31536000 seconds)
		set_transient($transient_key, $failed, 31536000);
	}
	
	/**
	 * Remove failed address from transient
	 * 
	 * @param string $type 'shippers' or 'companies'
	 * @param int $record_id Record ID to remove
	 */
	public function remove_failed_address($type, $record_id) {
		$transient_key = 'tms_geocode_failed_' . $type;
		$failed = $this->get_failed_addresses($type);
		
		if (isset($failed[$record_id])) {
			unset($failed[$record_id]);
			set_transient($transient_key, $failed, 31536000);
		}
	}
	
	/**
	 * Clear all failed addresses for a type
	 * 
	 * @param string $type 'shippers' or 'companies'
	 */
	public function clear_failed_addresses($type) {
		$transient_key = 'tms_geocode_failed_' . $type;
		delete_transient($transient_key);
	}
	
	/**
	 * Check if address is in failed list
	 * 
	 * @param string $type 'shippers' or 'companies'
	 * @param int $record_id Record ID
	 * @return bool True if address is in failed list
	 */
	public function is_failed_address($type, $record_id) {
		$failed = $this->get_failed_addresses($type);
		return isset($failed[$record_id]);
	}
	
	/**
	 * Get statistics for multi-zone states rechecking
	 * 
	 * @param string $type 'shippers' or 'companies'
	 * @return array Statistics with current progress
	 */
	public function get_recheck_timezone_stats($type = 'shippers') {
		global $wpdb;
		
		$multi_zone_states = array('NE', 'SD', 'ND', 'KS', 'TX', 'OR', 'IN', 'TN', 'FL', 'MI', 'AZ');
		$states_placeholders = implode(',', array_fill(0, count($multi_zone_states), '%s'));
		
		if ($type === 'shippers') {
			$table_name = $wpdb->prefix . 'reports_shipper';
		} else {
			$table_name = $wpdb->prefix . 'reports_company';
		}
		
		// Get total count
		$total_query = $wpdb->prepare("
			SELECT COUNT(*) as total
			FROM $table_name
			WHERE state IN ($states_placeholders)
			AND latitude IS NOT NULL
			AND longitude IS NOT NULL
		", $multi_zone_states);
		
		$total = (int) $wpdb->get_var($total_query);
		
		// Get current offset from transients (where we are in processing)
		$transient_key = 'tms_recheck_timezone_offset_' . $type;
		$current_offset = get_transient($transient_key);
		$processed = $current_offset !== false ? (int) $current_offset : 0;
		$remaining = max(0, $total - $processed);
		
		return array(
			'total' => $total,
			'processed' => $processed,
			'remaining' => $remaining,
			'needs_recheck' => $total
		);
	}
	
	/**
	 * Recheck timezone for addresses in states with multiple timezones
	 * Only updates if timezone is different from current value
	 * 
	 * @param string $type 'shippers' or 'companies'
	 * @param int $limit Number of records to process per batch
	 * @param int $offset Offset for pagination
	 * @return array Statistics about the rechecking process
	 */
	public function recheck_timezone_multi_zone_states($type = 'shippers', $limit = 50, $offset = 0) {
		global $wpdb;
		
		$helper = new TMSReportsHelper();
		
		// States that have multiple timezones
		// NE, SD, ND, KS, TX, OR - split between Mountain and Central
		// IN, TN, FL, MI - split between Eastern and Central
		// AZ - doesn't observe DST (always MST), but technically same timezone
		$multi_zone_states = array('NE', 'SD', 'ND', 'KS', 'TX', 'OR', 'IN', 'TN', 'FL', 'MI', 'AZ');
		$states_placeholders = implode(',', array_fill(0, count($multi_zone_states), '%s'));
		
		if ($type === 'shippers') {
			$table_name = $wpdb->prefix . 'reports_shipper';
			// Build query with proper parameter order: states first, then limit and offset
			$query = $wpdb->prepare("
				SELECT id, latitude, longitude, timezone, state, full_address
				FROM $table_name
				WHERE state IN ($states_placeholders)
				AND latitude IS NOT NULL
				AND longitude IS NOT NULL
				ORDER BY id ASC
				LIMIT %d OFFSET %d
			", array_merge($multi_zone_states, array($limit, $offset)));
		} else {
			$table_name = $wpdb->prefix . 'reports_company';
			// Build query with proper parameter order: states first, then limit and offset
			$query = $wpdb->prepare("
				SELECT id, latitude, longitude, timezone, state, 
					CONCAT_WS(', ', address1, city, state, zip_code) as full_address
				FROM $table_name
				WHERE state IN ($states_placeholders)
				AND latitude IS NOT NULL
				AND longitude IS NOT NULL
				ORDER BY id ASC
				LIMIT %d OFFSET %d
			", array_merge($multi_zone_states, array($limit, $offset)));
		}
		
		$records = $wpdb->get_results($query);
		
		// Get total stats
		$total_stats = $this->get_recheck_timezone_stats($type);
		
		$stats = array(
			'total' => count($records),
			'updated' => 0,
			'unchanged' => 0,
			'failed' => 0,
			'errors' => array(),
			'total_in_states' => $total_stats['total'],
			'offset' => $offset,
			'processed' => $offset + count($records),
			'remaining' => max(0, $total_stats['total'] - ($offset + count($records)))
		);
		
		foreach ($records as $record) {
			// Skip if this record is in failed list (to avoid repeated API calls for problematic addresses)
			if ($this->is_failed_address($type, $record->id)) {
				$stats['unchanged']++;
				error_log("TMSGeocodeAddresses: Skipping {$type} ID {$record->id} - in failed addresses list");
				continue;
			}
			
			// Get timezone from HERE API
			$new_timezone = $helper->get_timezone_by_coordinates($record->latitude, $record->longitude, date('Y-m-d'));
			
			if (empty($new_timezone)) {
				$stats['failed']++;
				$error_msg = "Record ID {$record->id}: Failed to get timezone from HERE API";
				$stats['errors'][] = $error_msg;
				
				// Add to failed list to avoid repeated attempts
				$address = !empty($record->full_address) ? $record->full_address : "Coordinates: {$record->latitude}, {$record->longitude}";
				$this->add_failed_address($type, $record->id, $address, $error_msg);
				continue;
			}
			
			// Compare with current timezone
			$current_timezone = $record->timezone;
			
			// Normalize timezones for comparison (remove spaces, convert to lowercase)
			$current_normalized = strtolower(str_replace(' ', '', $current_timezone ?? ''));
			$new_normalized = strtolower(str_replace(' ', '', $new_timezone));
			
			// Check if timezone is different
			if ($current_normalized !== $new_normalized) {
				// Update timezone
				$result = $wpdb->update(
					$table_name,
					array('timezone' => $new_timezone),
					array('id' => $record->id),
					array('%s'),
					array('%d')
				);
				
				if ($result !== false) {
					$stats['updated']++;
					error_log("TMSGeocodeAddresses: Updated timezone for {$type} ID {$record->id} from '{$current_timezone}' to '{$new_timezone}' (State: {$record->state})");
				} else {
					$stats['failed']++;
					$stats['errors'][] = "Record ID {$record->id}: Database update failed - " . $wpdb->last_error;
				}
			} else {
				$stats['unchanged']++;
			}
			
			// Small delay to avoid rate limiting
			usleep(50000); // 0.05 second delay
		}
		
		return $stats;
	}
}

