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
	
	const ROUTE_INDEXES_OPTION = 'tms_route_statistics_indexes_v1';

	public function __construct() {
		$this->TMSDrivers = new TMSDrivers();
		$this->TMSDriversHelper = new TMSDriversHelper();
		$this->TMSReportsHelper = new TMSReportsHelper();
		$this->ensure_route_statistics_indexes();
	}

	/**
	 * Run ALTER TABLE for route statistics indexes once per site (stored in option).
	 * New tables get idx_route_stats from create_location_tables(); this handles existing tables.
	 */
	private function ensure_route_statistics_indexes() {
		if ( get_option( self::ROUTE_INDEXES_OPTION, '' ) === 'yes' ) {
			return;
		}
		$this->add_route_statistics_indexes();
		update_option( self::ROUTE_INDEXES_OPTION, 'yes' );
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
	 * Get drivers with coordinates for USA only
	 * 
	 * @return array Array of drivers with id, latitude, longitude, home_location, driver_name
	 */
	public function get_usa_drivers_with_coordinates() {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->TMSDrivers->table_main;
		$table_meta = $wpdb->prefix . $this->TMSDrivers->table_meta;
		
		// Get all US state abbreviations
		$us_states = array( 'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 
			'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 
			'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 
			'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 
			'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'DC' );
		
		$placeholders = implode( ',', array_fill( 0, count( $us_states ), '%s' ) );
		
		$drivers_query = "
			SELECT DISTINCT
				m.id as driver_id,
				lat.meta_value as latitude,
				lng.meta_value as longitude,
				home_location.meta_value as home_location,
				driver_name.meta_value as driver_name,
				driver_status.meta_value as driver_status
			FROM $table_main AS m
			INNER JOIN $table_meta AS lat ON m.id = lat.post_id AND lat.meta_key = 'latitude'
			INNER JOIN $table_meta AS lng ON m.id = lng.post_id AND lng.meta_key = 'longitude'
			LEFT JOIN $table_meta AS home_location ON m.id = home_location.post_id AND home_location.meta_key = 'home_location'
			LEFT JOIN $table_meta AS driver_name ON m.id = driver_name.post_id AND driver_name.meta_key = 'driver_name'
			LEFT JOIN $table_meta AS driver_status ON m.id = driver_status.post_id AND driver_status.meta_key = 'driver_status'
			WHERE m.status_post = 'publish'
			AND lat.meta_value != ''
			AND lng.meta_value != ''
			AND lat.meta_value IS NOT NULL
			AND lng.meta_value IS NOT NULL
			AND (home_location.meta_value IN ($placeholders) OR home_location.meta_value IS NULL)
			AND (driver_status.meta_value IS NULL OR driver_status.meta_value NOT IN ('banned', 'blocked', 'expired_documents'))
		";
		
		$results = $wpdb->get_results( $wpdb->prepare( $drivers_query, $us_states ), ARRAY_A );
		
		// Filter results to ensure coordinates are within USA bounds
		$filtered_results = array();
		foreach ( $results as $driver ) {
			$lat = floatval( $driver['latitude'] );
			$lng = floatval( $driver['longitude'] );
			
			// USA bounds: approximately lat 24.5 to 49.4, lng -125 to -66.9
			if ( $lat >= 24.5 && $lat <= 49.4 && $lng >= -125 && $lng <= -66.9 ) {
				$filtered_results[] = $driver;
			}
		}
		
		return $filtered_results;
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
	 * Get loads statistics by US state based on new locations tables and shipper table.
	 *
	 * @param string $location_type 'pickup' or 'delivery'.
	 * @param string $country Filter by country: 'USA', 'Canada', 'Mexico' or 'all'.
	 * @param int|null $year Filter by year (null = all years, minimum 2024).
	 * @param int|null $month Filter by month 1-12 (null = all months).
	 *
	 * @return array Array of rows: [ 'state' => 'TX', 'country' => 'USA', 'label' => 'Texas (TX)', 'count' => 123 ].
	 */
	public function get_loads_state_statistics( $location_type = 'pickup', $country = 'USA', $year = null, $month = null ) {
		global $wpdb;
		
		// Normalize and validate location type
		$location_type = strtolower( $location_type ) === 'delivery' ? 'delivery' : 'pickup';
		
		// Normalize and validate country
		$country = strtoupper( trim( $country ) );
		$valid_countries = array( 'USA', 'CANADA', 'MEXICO', 'ALL' );
		if ( ! in_array( $country, $valid_countries, true ) ) {
			$country = 'USA';
		}
		
		// Projects that use the regular reports_* tables
		$projects = array( 'Odysseia', 'Martlet', 'Endurance' );
		
		$table_shipper = $wpdb->prefix . 'reports_shipper';
		
		$aggregated = array();
		
		foreach ( $projects as $project ) {
			$project_lower = strtolower( $project );
			$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
			
			// Skip if locations table does not exist
			$check_table = $wpdb->get_var( $wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$table_locations
			) );
			
			if ( $check_table !== $table_locations ) {
				continue;
			}
			
			// Build WHERE conditions
			$where_conditions = array();
			$params = array( $location_type );
			
			// Country filter
			if ( $country !== 'ALL' ) {
				$where_conditions[] = 's.country = %s';
				$params[] = $country;
			}
			
			// Year filter (minimum 2024)
			if ( $year !== null && is_numeric( $year ) ) {
				$year = (int) $year;
				if ( $year >= 2024 ) {
					if ( $month !== null && is_numeric( $month ) ) {
						// Filter by specific year and month
						$month = (int) $month;
						if ( $month >= 1 && $month <= 12 ) {
							$where_conditions[] = 'YEAR(l.date) = %d AND MONTH(l.date) = %d';
							$params[] = $year;
							$params[] = $month;
						}
					} else {
						// Filter by year only
						$where_conditions[] = 'YEAR(l.date) = %d';
						$params[] = $year;
					}
				}
			} elseif ( $month !== null && is_numeric( $month ) ) {
				// Month only (without year) - use current year or minimum 2024
				$month = (int) $month;
				if ( $month >= 1 && $month <= 12 ) {
					$current_year = (int) date( 'Y' );
					$min_year = max( 2024, $current_year );
					$where_conditions[] = 'YEAR(l.date) >= %d AND MONTH(l.date) = %d';
					$params[] = $min_year;
					$params[] = $month;
				}
			}
			
			$where_sql = '';
			if ( ! empty( $where_conditions ) ) {
				$where_sql = ' AND ' . implode( ' AND ', $where_conditions );
			}
			
			/**
			 * address_id in locations is stored as VARCHAR, while shipper.id is numeric.
			 * We cast shipper.id to CHAR for a reliable join.
			 * Also ensure address_id is not empty and date is not null.
			 */
			$sql = "
				SELECT 
					s.state AS state,
					s.country AS country,
					COUNT(DISTINCT l.load_id) AS count
				FROM $table_locations AS l
				INNER JOIN $table_shipper AS s 
					ON l.address_id = CAST(s.id AS CHAR)
				WHERE l.location_type = %s
					AND l.address_id IS NOT NULL
					AND l.address_id != ''
					AND l.date IS NOT NULL
				$where_sql
				GROUP BY s.state, s.country
			";
			
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
			
			if ( empty( $rows ) ) {
				continue;
			}
			
			foreach ( $rows as $row ) {
				$state_abbr = strtoupper( trim( (string) $row['state'] ) );
				$country_code = strtoupper( trim( (string) $row['country'] ) );
				
				if ( $state_abbr === '' ) {
					continue;
				}
				
				$key = $country_code . '|' . $state_abbr;
				
				if ( ! isset( $aggregated[ $key ] ) ) {
					$aggregated[ $key ] = array(
						'state'   => $state_abbr,
						'country' => $country_code,
						'count'   => 0,
					);
				}
				
				$aggregated[ $key ]['count'] += (int) $row['count'];
			}
		}
		
		if ( empty( $aggregated ) ) {
			return array();
		}
		
		// Convert to flat array with human-readable labels and sort by count DESC
		$result = array();
		foreach ( $aggregated as $item ) {
			$label = $this->get_state_label( $item['state'] );
			
			// Append country for non-USA if needed
			if ( $item['country'] !== 'USA' && $item['country'] !== '' ) {
				$label .= ' (' . $item['country'] . ')';
			}
			
			$result[] = array(
				'state'   => $item['state'],
				'country' => $item['country'],
				'label'   => $label,
				'count'   => (int) $item['count'],
			);
		}
		
		usort(
			$result,
			static function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);
		
		return $result;
	}
	
	/**
	 * Get loads statistics by route (Pickup State → Delivery State).
	 *
	 * @param string $country Filter by country: 'USA', 'Canada', 'Mexico' or 'all'.
	 * @param int|null $year Filter by year (null = all years, minimum 2024).
	 * @param int|null $month Filter by month 1-12 (null = all months).
	 *
	 * @return array Array of rows: [ 'pickup_state' => 'TX', 'delivery_state' => 'NY', 'pickup_country' => 'USA', 'delivery_country' => 'USA', 'label' => 'Texas (TX) → New York (NY)', 'count' => 123 ].
	 */
	public function get_loads_route_statistics( $country = 'USA', $year = null, $month = null ) {
		global $wpdb;
		
		// Normalize and validate country
		$country = strtoupper( trim( $country ) );
		$valid_countries = array( 'USA', 'CANADA', 'MEXICO', 'ALL' );
		if ( ! in_array( $country, $valid_countries, true ) ) {
			$country = 'USA';
		}

		// Projects that use the regular reports_* tables
		$projects = array( 'Odysseia', 'Martlet', 'Endurance' );
		
		$table_shipper = $wpdb->prefix . 'reports_shipper';
		
		$aggregated = array();
		
		foreach ( $projects as $project ) {
			$project_lower = strtolower( $project );
			$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
			
			// Skip if locations table does not exist
			$check_table = $wpdb->get_var( $wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$table_locations
			) );
			
			if ( $check_table !== $table_locations ) {
				continue;
			}
			
			// Build WHERE conditions
			$where_conditions = array();
			$params = array();
			
			// Country filter (apply to both pickup and delivery)
			if ( $country !== 'ALL' ) {
				$where_conditions[] = 'sp.country = %s AND sd.country = %s';
				$params[] = $country;
				$params[] = $country;
			}
			
			// Build date filter for efficient index usage (using date range instead of YEAR/MONTH functions)
			$date_filter = '';
			$date_params = array();
			
			if ( $year !== null && is_numeric( $year ) ) {
				$year = (int) $year;
				if ( $year >= 2024 ) {
					if ( $month !== null && is_numeric( $month ) ) {
						// Filter by specific year and month - use date range for index
						$month = (int) $month;
						if ( $month >= 1 && $month <= 12 ) {
							$date_start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
							$days_in_month = (int) date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
							$date_end = sprintf( '%04d-%02d-%02d 23:59:59', $year, $month, $days_in_month );
							$date_filter = 'date >= %s AND date <= %s';
							$date_params[] = $date_start;
							$date_params[] = $date_end;
						}
					} else {
						// Filter by year only - use date range for index
						$date_start = sprintf( '%04d-01-01 00:00:00', $year );
						$date_end = sprintf( '%04d-12-31 23:59:59', $year );
						$date_filter = 'date >= %s AND date <= %s';
						$date_params[] = $date_start;
						$date_params[] = $date_end;
					}
				}
			} elseif ( $month !== null && is_numeric( $month ) ) {
				// Month only (without year) - use current year or minimum 2024
				$month = (int) $month;
				if ( $month >= 1 && $month <= 12 ) {
					$current_year = (int) date( 'Y' );
					$min_year = max( 2024, $current_year );
					$date_start = sprintf( '%04d-%02d-01 00:00:00', $min_year, $month );
					$days_in_month = (int) date( 't', mktime( 0, 0, 0, $month, 1, $min_year ) );
					$date_end = sprintf( '%04d-%02d-%02d 23:59:59', $min_year, $month, $days_in_month );
					$date_filter = 'date >= %s AND date <= %s';
					$date_params[] = $date_start;
					$date_params[] = $date_end;
				}
			}
			
			// Build WHERE conditions for pickup subquery
			$pickup_where_conditions = array();
			$pickup_params = array();
			
			// Add date filter if exists (only for pickup subquery)
			if ( ! empty( $date_filter ) ) {
				$pickup_where_conditions[] = $date_filter;
				$pickup_params = array_merge( $pickup_params, $date_params );
			}
			
			$pickup_where_sql = '';
			if ( ! empty( $pickup_where_conditions ) ) {
				$pickup_where_sql = ' AND ' . implode( ' AND ', $pickup_where_conditions );
			}

			// Same date filter for delivery subquery so it uses the index and does not full-scan
			$delivery_where_sql = $pickup_where_sql;

			// Build WHERE conditions for main query (country filter)
			$main_where_conditions = array();
			$main_params = array();

			// Country filter (apply to both pickup and delivery)
			if ( $country !== 'ALL' ) {
				$main_where_conditions[] = 'sp.country = %s AND sd.country = %s';
				$main_params[] = $country;
				$main_params[] = $country;
			}

			$main_where_sql = '';
			if ( ! empty( $main_where_conditions ) ) {
				$main_where_sql = ' WHERE ' . implode( ' AND ', $main_where_conditions );
			}

			// Params: pickup date (2), delivery date (2 if same filter), main (country 2)
			if ( ! empty( $delivery_where_sql ) ) {
				$params = array_merge( $pickup_params, $pickup_params, $main_params );
			} else {
				$params = array_merge( $pickup_params, $main_params );
			}

			/**
			 * Optimized query: both pickup and delivery subqueries use the same date range
			 * so (location_type, date, load_id) index is used and delivery does not full-scan.
			 */
			$sql = "
				SELECT 
					sp.state AS pickup_state,
					sp.country AS pickup_country,
					sd.state AS delivery_state,
					sd.country AS delivery_country,
					COUNT(DISTINCT pickup_routes.load_id) AS count
				FROM (
					SELECT 
						load_id,
						MIN(id) AS location_id
					FROM $table_locations
					WHERE location_type = 'pickup'
						AND address_id IS NOT NULL
						AND address_id != ''
						AND date IS NOT NULL
						$pickup_where_sql
					GROUP BY load_id
				) AS pickup_routes
				INNER JOIN $table_locations AS lp
					ON pickup_routes.location_id = lp.id
				INNER JOIN $table_shipper AS sp 
					ON lp.address_id = CAST(sp.id AS CHAR)
					AND sp.state IS NOT NULL
					AND sp.state != ''
				INNER JOIN (
					SELECT 
						load_id,
						MIN(id) AS location_id
					FROM $table_locations
					WHERE location_type = 'delivery'
						AND address_id IS NOT NULL
						AND address_id != ''
						AND date IS NOT NULL
						$delivery_where_sql
					GROUP BY load_id
				) AS delivery_routes
					ON pickup_routes.load_id = delivery_routes.load_id
				INNER JOIN $table_locations AS ld
					ON delivery_routes.location_id = ld.id
				INNER JOIN $table_shipper AS sd
					ON ld.address_id = CAST(sd.id AS CHAR)
					AND sd.state IS NOT NULL
					AND sd.state != ''
				$main_where_sql
				GROUP BY sp.state, sp.country, sd.state, sd.country
			";
			
			$rows = empty( $params ) 
				? $wpdb->get_results( $sql, ARRAY_A )
				: $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
			
			if ( empty( $rows ) ) {
				continue;
			}
			
			foreach ( $rows as $row ) {
				$pickup_state = strtoupper( trim( (string) $row['pickup_state'] ) );
				$delivery_state = strtoupper( trim( (string) $row['delivery_state'] ) );
				$pickup_country = strtoupper( trim( (string) $row['pickup_country'] ) );
				$delivery_country = strtoupper( trim( (string) $row['delivery_country'] ) );
				
				if ( $pickup_state === '' || $delivery_state === '' ) {
					continue;
				}
				
				$key = $pickup_country . '|' . $pickup_state . '|' . $delivery_country . '|' . $delivery_state;
				
				if ( ! isset( $aggregated[ $key ] ) ) {
					$aggregated[ $key ] = array(
						'pickup_state'   => $pickup_state,
						'pickup_country' => $pickup_country,
						'delivery_state'   => $delivery_state,
						'delivery_country' => $delivery_country,
						'count'   => 0,
					);
				}
				
				$aggregated[ $key ]['count'] += (int) $row['count'];
			}
		}
		
		if ( empty( $aggregated ) ) {
			return array();
		}
		
		// Convert to flat array with human-readable labels and sort by count DESC
		$result = array();
		foreach ( $aggregated as $item ) {
			$pickup_label = $this->get_state_label( $item['pickup_state'] );
			$delivery_label = $this->get_state_label( $item['delivery_state'] );
			
			// Append country for non-USA if needed
			if ( $item['pickup_country'] !== 'USA' && $item['pickup_country'] !== '' ) {
				$pickup_label .= ' (' . $item['pickup_country'] . ')';
			}
			if ( $item['delivery_country'] !== 'USA' && $item['delivery_country'] !== '' ) {
				$delivery_label .= ' (' . $item['delivery_country'] . ')';
			}
			
			$route_label = $pickup_label . ' → ' . $delivery_label;
			
			$result[] = array(
				'pickup_state'   => $item['pickup_state'],
				'pickup_country' => $item['pickup_country'],
				'delivery_state'   => $item['delivery_state'],
				'delivery_country' => $item['delivery_country'],
				'label'   => $route_label,
				'count'   => (int) $item['count'],
			);
		}
		
		usort(
			$result,
			static function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);
		
		return $result;
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
	
	/**
	 * Get expired documents statistics
	 * Counts drivers with expired documents for each document type
	 * 
	 * @return array Array of expired document counts
	 */
	public function get_expired_documents_statistics() {
		global $wpdb;
		$table_main = $wpdb->prefix . $this->TMSDrivers->table_main;
		$table_meta = $wpdb->prefix . $this->TMSDrivers->table_meta;
		
		// Get all published drivers with their meta data
		$drivers_query = "
			SELECT m.id, m.status_post
			FROM $table_main AS m
			WHERE m.status_post = 'publish'
		";
		
		$driver_ids = $wpdb->get_col( $drivers_query );
		
		if ( empty( $driver_ids ) ) {
			return array();
		}
		
		// Get all meta data for these drivers
		$meta_query = "
			SELECT post_id, meta_key, meta_value
			FROM $table_meta
			WHERE post_id IN (" . implode( ',', array_map( 'intval', $driver_ids ) ) . ")
		";
		
		$meta_results = $wpdb->get_results( $meta_query, ARRAY_A );
		
		// Organize meta data by driver ID
		$drivers_meta = array();
		foreach ( $meta_results as $meta_row ) {
			$post_id = $meta_row['post_id'];
			if ( ! isset( $drivers_meta[ $post_id ] ) ) {
				$drivers_meta[ $post_id ] = array();
			}
			$drivers_meta[ $post_id ][ $meta_row['meta_key'] ] = $meta_row['meta_value'];
		}
		
		// Build drivers array with meta_data
		$drivers = array();
		foreach ( $driver_ids as $driver_id ) {
			$drivers[] = array(
				'id' => $driver_id,
				'meta_data' => isset( $drivers_meta[ $driver_id ] ) ? $drivers_meta[ $driver_id ] : array()
			);
		}
		
		// Define document types to check
		$document_types = array(
			'DL' => 'Driver\'s License',
			'COI' => 'Certificate of Insurance',
			'EA' => 'Employment Authorization',
			'PR' => 'Permanent Resident',
			'PS' => 'Passport',
			'HZ' => 'Hazmat Certificate',
			'GE' => 'Global Entry',
			'TWIC' => 'TWIC',
			'TSA' => 'TSA',
			'DL_TEAM' => 'Driver\'s License (Team driver)',
			'EA_TEAM' => 'Employment Authorization (Team driver)',
			'PR_TEAM' => 'Permanent Resident (Team driver)',
			'PS_TEAM' => 'Passport (Team driver)',
			'HZ_TEAM' => 'Hazmat Certificate (Team driver)',
			'GE_TEAM' => 'Global Entry (Team driver)',
			'TWIC_TEAM' => 'TWIC (Team driver)',
			'TSA_TEAM' => 'TSA (Team driver)',
		);
		
		// Count expired documents for each type
		$expired_counts = array();
		foreach ( $document_types as $doc_type => $doc_name ) {
			$filtered = $this->TMSDrivers->filter_drivers_by_document_type( $drivers, $doc_type, 'expired' );
			$expired_counts[ $doc_type ] = array(
				'name' => $doc_name,
				'count' => count( $filtered )
			);
		}
		
		return $expired_counts;
	}
	
	/**
	 * Add performance indexes to location tables for route statistics optimization
	 * Safe to run multiple times - checks if indexes exist before adding
	 * 
	 * @return array Results of index creation
	 */
	public function add_route_statistics_indexes() {
		global $wpdb;
		
		$results = array();
		$projects = array( 'Odysseia', 'Martlet', 'Endurance' );
		$table_shipper = $wpdb->prefix . 'reports_shipper';
		
		foreach ( $projects as $project ) {
			$project_lower = strtolower( $project );
			$table_locations = $wpdb->prefix . 'reports_' . $project_lower . '_locations';
			
			// Check if table exists
			$table_exists = $wpdb->get_var( $wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$table_locations
			) );
			
			if ( $table_exists !== $table_locations ) {
				continue;
			}
			
			$table_results = array(
				'table' => $table_locations,
				'indexes_added' => array(),
			);
			
			// Critical composite index for route statistics: (location_type, date, load_id)
			// This index will be used for filtering by location_type and date range
			$indexes_to_add = array(
				'idx_route_stats' => '(location_type, date, load_id)',
				'idx_date_type' => '(date, location_type)',
				'idx_load_date_type' => '(load_id, date, location_type)',
			);
			
			foreach ( $indexes_to_add as $index_name => $index_columns ) {
				// Check if index exists using SHOW INDEX
				$index_exists = $wpdb->get_var( $wpdb->prepare(
					"SHOW INDEX FROM $table_locations WHERE Key_name = %s",
					$index_name
				) );
				
				if ( ! $index_exists ) {
					$result = $wpdb->query( "
						ALTER TABLE $table_locations ADD INDEX $index_name $index_columns
					" );
					if ( $result !== false ) {
						$table_results['indexes_added'][] = $index_name;
					}
				}
			}

			// Update statistics when we added indexes so the optimizer uses them
			if ( ! empty( $table_results['indexes_added'] ) ) {
				$wpdb->query( "ANALYZE TABLE $table_locations" );
			}

			$results[] = $table_results;
		}
		
		// Add index to shipper table for address_id lookups
		$shipper_index_name = 'idx_address_id_lookup';
		$shipper_index_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW INDEX FROM $table_shipper WHERE Key_name = %s",
			$shipper_index_name
		) );
		
		if ( ! $shipper_index_exists ) {
			$result = $wpdb->query( "
				ALTER TABLE $table_shipper ADD INDEX $shipper_index_name (id, state, country)
			" );
			if ( $result !== false ) {
				$wpdb->query( "ANALYZE TABLE $table_shipper" );
				$results[] = array(
					'table' => $table_shipper,
					'indexes_added' => array( $shipper_index_name ),
				);
			}
		}

		return $results;
	}
}

