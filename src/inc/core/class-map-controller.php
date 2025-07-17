<?php

/**
 * Map Controller Class
 * Handles distance calculations using different mapping services
 */
class Map_Controller {
	
	public function __construct() {
		$this->initial_constants();
	}
	
	/**
	 * Initialize constants from global options
	 */
	public function initial_constants() {
		global $global_options;
		
		$main_api_key       = get_field_value( $global_options, 'api_key_here_map' );
		$use_hero_or_google = get_field_value( $global_options, 'use_driver' );
		$geocoder           = get_field_value( $global_options, 'use_geocoder' );
		$url_ors            = get_field_value( $global_options, 'url_ors' );


		$driver = 'here';
		
		if ( $use_hero_or_google === 'OpenRouteServices' ) {
			$apiKey = $main_api_key;
			$driver = 'openrouteservices';
		} else {
			$apiKey = $main_api_key;
		}
		
		define( 'USE_GEOCODER', $geocoder );
		define( 'API_KEY', $apiKey );
		define( 'USE_DRIVER', $driver );
		define( 'URL_ORS', $url_ors );
	}
	
	/**
	 * Get distances using selected mapping service
	 * 
	 * @param array $start Starting location
	 * @param array $dest Destination locations
	 * @return array|false Array of distances or false on error
	 */
	public function getDistances( $start, $dest ) {
		if ( USE_DRIVER ) {
			if ( USE_DRIVER === 'openrouteservices' ) {
				return $this->getDistancesORS( $start, $dest );
			} else {
				return $this->getDistancesHere( $start, $dest );
			}
		}
		return false;
	}
	
	/**
	 * Prepare query after error for OpenRouteServices
	 * 
	 * @param array $array_drivers Array of drivers
	 * @return array Processed drivers array
	 */
	public function preperedQueryAfterError( $array_drivers ) {
		$array_queries = array();
		
		if ( is_array( $array_drivers ) ) {
			foreach ( $array_drivers as $driver ) {
				if ( ! isset( $driver[ 'exclude' ] ) ) {
					$array_queries[] = $driver;
				}
			}
		}
		
		$query = array(
			"locations" => $array_queries,
			"sources"   => array( 0 ),
			"metrics"   => array( "distance" ),
			"units"     => "mi",
		);
		
		$curl = curl_init();
		
		curl_setopt_array( $curl, [
			CURLOPT_URL            => URL_ORS . "/ors/v2/matrix/driving-car",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => "POST",
			CURLOPT_POSTFIELDS     => json_encode( $query ),
			CURLOPT_HTTPHEADER     => [
				"Content-Type: application/json",
			],
		] );
		
		$response        = curl_exec( $curl );
		$response_array  = json_decode( $response, ARRAY_A );
		
		if ( is_array( $response_array ) && isset( $response_array[ 'distances' ] ) ) {
			$distance_array = $response_array[ 'distances' ][ 0 ];
			
			$i = 0;
			foreach ( $array_drivers as $key => $value ) {
				if ( ! isset( $value[ 'exclude' ] ) ) {
					$array_drivers[ $key ][ 'distance' ] = $distance_array[ $i ];
					$i += 1;
				}
			}
		}
		
		array_shift( $array_drivers );
		$array_drivers = array_values( $array_drivers );
		
		return $array_drivers;
	}
	
	/**
	 * Get distances using OpenRouteServices
	 * 
	 * @param array $start Starting location
	 * @param array $dest Destination locations
	 * @return array|false Array of distances or false on error
	 */
	public function getDistancesORS( $start, $dest ) {
		$origin = array( $start[ 0 ][ 'lng' ], $start[ 0 ][ 'lat' ] ); // Starting point
		$array_queries = array();
		
		$array_queries[] = $origin;
		
		if ( is_array( $dest ) ) {
			foreach ( $dest as $key => $item ) {
				$array_queries[] = array( $item[ 'lng' ], $item[ 'lat' ] );
			}
		}
		
		$origin_array = $array_queries;
		
		$query = array(
			"locations" => $array_queries,
			"sources"   => array( 0 ),
			"metrics"   => array( "distance" ),
			"units"     => "mi",
		);
		
		$curl = curl_init();
		curl_setopt_array( $curl, [
			CURLOPT_URL            => URL_ORS . "/ors/v2/matrix/driving-car",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => "POST",
			CURLOPT_POSTFIELDS     => json_encode( $query ),
			CURLOPT_HTTPHEADER     => [
				"Content-Type: application/json",
			],
		] );
		
		$response = curl_exec( $curl );
		$data     = json_decode( $response, true );
		
		if ( isset( $data[ 'error' ] ) ) {
			if ( $data[ 'error' ][ 'code' ] === 6010 ) {
				preg_match( '/\[([^\]]+)\]/', $data[ 'error' ][ 'message' ], $matches );
				
				if ( isset( $matches[ 1 ] ) ) {
					// Get string with point numbers, split by comma and remove extra spaces
					$points_string = $matches[ 1 ];
					$points_array  = array_map( 'trim', explode( ',', $points_string ) );
					
					if ( is_array( $points_array ) ) {
						foreach ( $points_array as $val ) {
							$origin_array[ $val ][ 'exclude' ] = true;
						}
					}
					return $this->preperedQueryAfterError( $origin_array );
				}
			}
			return false;
		} else {
			$response_array = $data;
			
			if ( is_array( $response_array ) && isset( $response_array[ 'distances' ] ) ) {
				$distance_array = $response_array[ 'distances' ][ 0 ];
				
				$i = 0;
				foreach ( $array_queries as $key => $value ) {
					if ( ! isset( $value[ 'exclude' ] ) ) {
						$array_queries[ $key ][ 'distance' ] = $distance_array[ $i ];
						$i += 1;
					}
				}
			}
			
			array_shift( $array_queries );
			$array_queries = array_values( $array_queries );
			
			return $array_queries;
		}
		return false;
	}
	
	/**
	 * Get distances using Here Maps API
	 * 
	 * @param array $start Starting location
	 * @param array $dest Destination locations
	 * @return array|false Array of distances or false on error
	 */
	public function getDistancesHere( $start, $dest ) {
		$MileInOneMeter = 0.000621371;
		
		$apiKey = API_KEY;
		$url    = "https://matrix.router.hereapi.com/v8/matrix?async=false&apiKey=$apiKey";
		
		// Starting points array
		$origins = $start;
		
		// Limit destinations to 50 points
		if ( is_array( $dest ) && sizeof( $dest ) > 50 ) {
			$dest = array_slice( $dest, 0, 50 );
		}
		
		$destinations = $dest;
		
		// Request body
		$body = [
			"origins"          => $origins,
			"destinations"     => $destinations,
			"profile"          => "truckFast", // fast road with car
			"regionDefinition" => [
				"type" => "world"
			],
			"matrixAttributes" => [ "travelTimes", "distances" ]
		];
		
		// Make request
		$curl = curl_init();
		
		curl_setopt_array( $curl, array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => json_encode( $body ),
			CURLOPT_HTTPHEADER     => array(
				'Content-Type: application/json'
			),
		) );
		
		// Get response
		$response = curl_exec( $curl );
		
		$statusCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		if ( $statusCode !== 200 ) {
			return false;
		}
		if ( ! $response ) {
			return false;
		}
		
		curl_close( $curl );
		
		$decoded = json_decode( $response );
		
		if ( ! isset( $decoded->matrix->distances ) ) {
			return false;
		}
		
		// Convert meters to miles
		foreach ( $decoded->matrix->distances as &$distance ) {
			$distance = $distance * $MileInOneMeter;
		}
		
		return $decoded->matrix->distances;
	}
} 