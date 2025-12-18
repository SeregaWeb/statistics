<?php
/**
 * TMS Drivers Statistics Class
 * Handles all statistics queries and data preparation for drivers statistics page
 * 
 * @package WP-rock
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TMSDriversStatistics {
	
	private $TMSDrivers;
	private $TMSDriversHelper;
	private $TMSReportsHelper;
	
	public function __construct() {
		$this->TMSDrivers = new TMSDrivers();
		$this->TMSDriversHelper = new TMSDriversHelper();
		$this->TMSReportsHelper = new TMSReportsHelper();
	}
	
	/**
	 * Get state (home_location) statistics
	 * 
	 * @return array Array of state statistics with count
	 */
	public function get_state_statistics() {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->TMSDrivers->table_main;
		$table_meta = $wpdb->prefix . $this->TMSDrivers->table_meta;
		
		$state_query = "
			SELECT 
				COALESCE(tm.meta_value, 'N/A') as state,
				COUNT(DISTINCT m.id) as count
			FROM $table_main AS m
			LEFT JOIN $table_meta AS tm ON tm.post_id = m.id AND tm.meta_key = 'home_location'
			WHERE m.status_post = 'publish'
			GROUP BY tm.meta_value
			HAVING state != 'N/A' AND state != '' AND state IS NOT NULL
			ORDER BY count DESC
		";
		
		return $wpdb->get_results( $state_query, ARRAY_A );
	}
	
	/**
	 * Get nationality statistics
	 * 
	 * @return array Array of nationality statistics with count
	 */
	public function get_nationality_statistics() {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->TMSDrivers->table_main;
		$table_meta = $wpdb->prefix . $this->TMSDrivers->table_meta;
		
		$nationality_query = "
			SELECT 
				COALESCE(tm.meta_value, 'N/A') as nationality,
				COUNT(DISTINCT m.id) as count
			FROM $table_main AS m
			LEFT JOIN $table_meta AS tm ON tm.post_id = m.id AND tm.meta_key = 'nationality'
			WHERE m.status_post = 'publish'
			GROUP BY tm.meta_value
			HAVING nationality != 'N/A' AND nationality != '' AND nationality IS NOT NULL
			ORDER BY count DESC
		";
		
		return $wpdb->get_results( $nationality_query, ARRAY_A );
	}
	
	/**
	 * Get language statistics
	 * Languages are stored as comma-separated values
	 * 
	 * @return array Array of language counts with human-readable labels
	 */
	public function get_language_statistics() {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->TMSDrivers->table_main;
		$table_meta = $wpdb->prefix . $this->TMSDrivers->table_meta;
		
		$language_query = "
			SELECT 
				tm.meta_value as languages
			FROM $table_main AS m
			LEFT JOIN $table_meta AS tm ON tm.post_id = m.id AND tm.meta_key = 'languages'
			WHERE m.status_post = 'publish' AND tm.meta_value IS NOT NULL AND tm.meta_value != ''
		";
		
		$language_results = $wpdb->get_results( $language_query, ARRAY_A );
		
		// Process language data (split comma-separated values)
		$language_counts = array();
		foreach ( $language_results as $lang_row ) {
			if ( ! empty( $lang_row['languages'] ) ) {
				$languages = explode( ',', $lang_row['languages'] );
				foreach ( $languages as $lang ) {
					$lang = trim( $lang );
					if ( ! empty( $lang ) ) {
						// Get human-readable label from helper
						$lang_label = isset( $this->TMSDriversHelper->languages[ $lang ] ) 
							? $this->TMSDriversHelper->languages[ $lang ] 
							: ucfirst( $lang );
						if ( ! isset( $language_counts[ $lang_label ] ) ) {
							$language_counts[ $lang_label ] = 0;
						}
						$language_counts[ $lang_label ]++;
					}
				}
			}
		}
		arsort( $language_counts );
		
		return $language_counts;
	}
	
	/**
	 * Get total drivers count
	 * 
	 * @return int Total number of published drivers
	 */
	public function get_total_drivers_count() {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->TMSDrivers->table_main;
		
		return (int) $wpdb->get_var( "
			SELECT COUNT(DISTINCT id) 
			FROM $table_main 
			WHERE status_post = 'publish'
		" );
	}
	
	/**
	 * Calculate totals from statistics array
	 * 
	 * @param array $statistics Statistics array from get_statistics()
	 * @return array Array of totals for all vehicle types and capabilities
	 */
	public function calculate_totals( $statistics ) {
		$totals = array(
			'total_all'            => 0,
			'tanker_all'           => 0,
			'twic_all'             => 0,
			'hazmat_all'           => 0,
			'cargo_van_all'        => 0,
			'sprinter_van_all'     => 0,
			'box_truck_all'        => 0,
			'reefer_all'           => 0,
			'pickup_all'           => 0,
			'semi_truck_all'       => 0,
			'hazmat_cdl_all'       => 0,
			'hazmat_certificate_all' => 0,
			'tsa_all'              => 0,
			'change_9_all'         => 0,
			'sleeper_all'           => 0,
			'printer_all'           => 0,
			'canada_all'           => 0,
			'mexico_all'           => 0,
		);
		
		if ( ! is_array( $statistics ) || empty( $statistics ) ) {
			return $totals;
		}
		
		foreach ( $statistics as $statistic ) {
			$totals['total_all'] += (int) $statistic['total'];
			$totals['tanker_all'] += (int) $statistic['tanker_on'];
			$totals['twic_all'] += (int) $statistic['twic_on'];
			$totals['hazmat_all'] += (int) $statistic['hazmat_on'];
			$totals['cargo_van_all'] += (int) $statistic['cargo_van'];
			$totals['sprinter_van_all'] += (int) $statistic['sprinter_van'];
			$totals['box_truck_all'] += (int) $statistic['box_truck'];
			$totals['reefer_all'] += (int) $statistic['reefer'];
			$totals['pickup_all'] += (int) ( isset( $statistic['pickup'] ) ? $statistic['pickup'] : 0 );
			$totals['semi_truck_all'] += (int) ( isset( $statistic['semi_truck'] ) ? $statistic['semi_truck'] : 0 );
			$totals['hazmat_cdl_all'] += (int) ( isset( $statistic['hazmat_cdl'] ) ? $statistic['hazmat_cdl'] : 0 );
			$totals['hazmat_certificate_all'] += (int) ( isset( $statistic['hazmat_certificate'] ) ? $statistic['hazmat_certificate'] : 0 );
			$totals['tsa_all'] += (int) ( isset( $statistic['tsa'] ) ? $statistic['tsa'] : 0 );
			$totals['change_9_all'] += (int) ( isset( $statistic['change_9'] ) ? $statistic['change_9'] : 0 );
			$totals['sleeper_all'] += (int) ( isset( $statistic['sleeper'] ) ? $statistic['sleeper'] : 0 );
			$totals['printer_all'] += (int) ( isset( $statistic['printer'] ) ? $statistic['printer'] : 0 );
			$totals['canada_all'] += (int) ( isset( $statistic['canada_on'] ) ? $statistic['canada_on'] : 0 );
			$totals['mexico_all'] += (int) ( isset( $statistic['mexico_on'] ) ? $statistic['mexico_on'] : 0 );
		}
		
		return $totals;
	}
	
	/**
	 * Get state label with full name from helper
	 * 
	 * @param string $state_abbr State abbreviation
	 * @return string State label with full name or abbreviation
	 */
	public function get_state_label( $state_abbr ) {
		$state_abbr = strtoupper( trim( $state_abbr ) );
		
		if ( isset( $this->TMSReportsHelper->select[ $state_abbr ] ) ) {
			return $this->TMSReportsHelper->select[ $state_abbr ];
		}
		
		return $state_abbr;
	}
	
	/**
	 * Get vehicle label from helper
	 * 
	 * @param string $vehicle_key Vehicle key (e.g., 'cargo-van')
	 * @return string Vehicle label or key if not found
	 */
	public function get_vehicle_label( $vehicle_key ) {
		if ( isset( $this->TMSDriversHelper->vehicle[ $vehicle_key ] ) ) {
			return $this->TMSDriversHelper->vehicle[ $vehicle_key ];
		}
		
		return ucfirst( str_replace( '-', ' ', $vehicle_key ) );
	}
}

