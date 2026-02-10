<?php

class TMSReportsHelper extends TMSReportsIcons {
	
	public $processing = array(
		'factoring'                 => 'Factoring (ACH)',
		'factoring-delayed-advance' => 'Factoring (Delayed advance)',
		'factoring-wire-transfer'   => 'Factoring (Wire Transfer)',
		'unapplied-payment'         => 'Unapplied payment',
		'direct'                    => 'Direct',
	);
	
	
	public $statuses_factoring_labels = [
		'factoring-delayed-advance' => 'Delayed advance',
		'unapplied-payment'         => 'Unapplied payments',
		'in-processing'             => 'Processing',
		'pending-to-tafs'           => 'Pending to factoring',
		'fraud'                     => 'Fraud',
		'company-closed'            => 'Company closed',
	];
	
	public $select = array(
		"USA_LABEL" => array( "---United states (US)---" ),
		"AL"        => "Alabama, AL",
		"AK"        => "Alaska, AK",
		"AZ"        => "Arizona, AZ",
		"AR"        => "Arkansas, AR",
		"CA"        => "California, CA",
		"CO"        => "Colorado, CO",
		"CT"        => "Connecticut, CT",
		"DE"        => "Delaware, DE",
		"DC"        => "District of Columbia, DC",
		"FL"        => "Florida, FL",
		"GA"        => "Georgia, GA",
		"HI"        => "Hawaii, HI",
		"ID"        => "Idaho, ID",
		"IL"        => "Illinois, IL",
		"IN"        => "Indiana, IN",
		"IA"        => "Iowa, IA",
		"KS"        => "Kansas, KS",
		"KY"        => "Kentucky, KY",
		"LA"        => "Louisiana, LA",
		"ME"        => "Maine, ME",
		"MD"        => "Maryland, MD",
		"MA"        => "Massachusetts, MA",
		"MI"        => "Michigan, MI",
		"MN"        => "Minnesota, MN",
		"MS"        => "Mississippi, MS",
		"MO"        => "Missouri, MO",
		"MT"        => "Montana, MT",
		"NE"        => "Nebraska, NE",
		"NV"        => "Nevada, NV",
		"NH"        => "New Hampshire, NH",
		"NJ"        => "New Jersey, NJ",
		"NM"        => "New Mexico, NM",
		"NY"        => "New York, NY",
		"NC"        => "North Carolina, NC",
		"ND"        => "North Dakota, ND",
		"OH"        => "Ohio, OH",
		"OK"        => "Oklahoma, OK",
		"OR"        => "Oregon, OR",
		"PA"        => "Pennsylvania, PA",
		"RI"        => "Rhode Island, RI",
		"SC"        => "South Carolina, SC",
		"SD"        => "South Dakota, SD",
		"TN"        => "Tennessee, TN",
		"TX"        => "Texas, TX",
		"UT"        => "Utah, UT",
		"VT"        => "Vermont, VT",
		"VA"        => "Virginia, VA",
		"WA"        => "Washington, WA",
		"WV"        => "West Virginia, WV",
		"WI"        => "Wisconsin, WI",
		"WY"        => "Wyoming, WY",
		"CA_LABEL"  => array( "---Canada (CA)---" ),
		"AB"        => "Alberta, AB",
		"BC"        => "British Columbia, BC",
		"MB"        => "Manitoba, MB",
		"NB"        => "New Brunswick, NB",
		"NL"        => "Newfoundland and Labrador, NL",
		"NT"        => "Northwest Territories, NT",
		"NS"        => "Nova Scotia, NS",
		"NU"        => "Nunavut, NU",
		"ON"        => "Ontario, ON",
		"PE"        => "Prince Edward Island, PE",
		"QC"        => "Quebec, QC",
		"SK"        => "Saskatchewan, SK",
		"YT"        => "Yukon, YT",
		"MX_LABEL"  => array( "--- Mexico (MX)---" ),
		"DIF"       => "Distrito Federal, DIF",
		"CDMX"      => "Distrito Federal, CDMX",
		"AGU"       => "Aguascalientes, AGU",
		"BCN"       => "Baja California, BCN",
		"BCS"       => "Baja California Sur, BCS",
		"CAM"       => "Campeche, CAM",
		"CHP"       => "Chiapas, CHP",
		"CHIS"      => "Chiapas, CHIS",
		"CHH"       => "Chihuahua, CHH",
		"COA"       => "Coahuila, COA",
		"COL"       => "Colima, COL",
		"DUR"       => "Durango, DUR",
		"GUA"       => "Guanajuato, GUA",
		"GRO"       => "Guerrero, GRO",
		"HID"       => "Hidalgo, HID",
		"JAL"       => "Jalisco, JAL",
		"MIC"       => "Michoacán, MIC",
		"MOR"       => "Morelos, MOR",
		"MEX"       => "México, MEX",
		"NAY"       => "Nayarit, NAY",
		"NLE"       => "Nuevo León, NLE",
		"OAX"       => "Oaxaca, OAX",
		"PUE"       => "Puebla, PUE",
		"QUE"       => "Querétaro, QUE",
		"QRO"       => "Querétaro, QRO",
		"NAQ"       => "Querétaro, NAQ",
		"ROO"       => "Quintana Roo, ROO",
		"SLP"       => "San Luis Potosí, SLP",
		"SIN"       => "Sinaloa, SIN",
		"SON"       => "Sonora, SON",
		"TAB"       => "Tabasco, TAB",
		"TAM"       => "Tamaulipas, TAM",
		"TLA"       => "Tlaxcala, TLA",
		"VER"       => "Veracruz, VER",
		"YUC"       => "Yucatán, YUC",
		"ZAC"       => "Zacatecas, ZAC",
	);
	
	public $invoices = array(
		'invoiced'     => 'Invoiced',
		'not-invoiced' => 'Not invoiced',
	);
	
	public $factoring_status = array(
		'unsubmitted'        => 'Unsubmitted',
		'in-processing'      => 'In Processing',
		'requires-attention' => 'Requires Attention',
		'in-dispute'         => 'In Dispute',
		'processed'          => 'Processed',
		'charge-back'        => 'Charge Back',
		'short-pay'          => 'Short Pay',
		'pending-to-tafs'    => 'Pending to factoring',
		'paid'               => 'Paid',
		'fraud'              => 'Fraud',
		'company-closed'     => 'Company closed',
	);
	
	public $bank_statuses = array(
		'approved'         => 'Approved',
		'not-in-payees'    => 'Not in payees',
		'adding-to-payees' => 'Adding to payees',
		'missing-vc'       => 'Missing VC',
	);
	
	public $driver_payment_statuses = array(
		'not-paid'   => 'Not paid',
		'processing' => 'Processing',
		'paid'       => 'Paid'
	);
	
	public $statuses = array(
		'waiting-on-pu-date' => 'Waiting on PU Date',
		'at-pu'              => '@PU',
		'loaded-enroute'     => 'Loaded & Enroute',
		'at-del'             => '@DEL',
		'delivered'          => 'Delivered',
		'tonu'               => 'TONU',
		'cancelled'          => 'Cancelled',
		'waiting-on-rc'      => 'Waiting on RC'
	);
	
	public $sources = array(
		'contact'   => 'Contact',
		'dat'       => 'DAT',
		'truckstop' => 'Truckstop',
		'sylectus'  => 'Sylectus',
		'rxo'       => 'RXO',
		'beon'      => 'Beon',
		'other'     => 'Other'
	);
	
	public $quick_pay_methods = array(
		'zelle'          => array( 'label' => 'Zelle', 'value' => '3', 'commission' => '0' ),
		'cashapp'        => array( 'label' => 'CashApp', 'value' => '3.5', 'commission' => '0' ),
		'ach-individual' => array( 'label' => 'ACH (individual)', 'value' => '2.5', 'commission' => '0.5' ),
		'ach-business'   => array( 'label' => 'ACH (business)', 'value' => '2.5', 'commission' => '3' ),
		'wire-transfer'  => array( 'label' => 'Wire transfer', 'value' => '2.5', 'commission' => '25' ),
		'cash'           => array( 'label' => 'Cash', 'value' => '0', 'commission' => '0' ),
		'check'          => array( 'label' => 'Check', 'value' => '0', 'commission' => '0' ),
	);
	
	public $features = array(
		'hazmat'              => 'Hazmat',
		'tanker-end'          => 'Tanker End.',
		'driver-assist'       => 'Driver assist',
		'liftgate'            => 'Liftgate',
		'pallet-jack'         => 'Pallet Jack',
		'dock-high'           => 'Dock High',
		'true-team'           => 'True team',
		'fake-team'           => 'Fake team',
		'tsa'                 => 'TSA',
		'twic'                => 'TWIC',
		'airport'             => 'Airport',
		'round-trip'          => 'Round trip',
		'alcohol'             => 'Alcohol',
		'temperature-control' => 'Temperature control',
		'ace'                 => 'ACE',
		'aci'                 => 'ACI',
		'mexico'              => 'Mexico',
		'military-base'       => 'Military base',
		'blind-shipment'      => 'Blind shipment',
		'partial'             => 'Partial',
		'white-glove-service' => 'White glove service',
		'high-value-freight'  => 'High value freight',
		'fragile'             => 'Fragile',
	);
	
	public $types = array(
		'ltl'           => 'LTL',
		'container'     => 'Container',
		'drop_and_hook' => 'Drop and Hook',
		'last_mile'     => 'Last Mile',
		'other'         => 'Other',
		'truck_load'    => 'Truck Load',
	);
	
	public $tms_tables = array(
		'Odysseia',
		'Martlet',
		'Endurance'
	);
	
	public $tms_tables_with_label = array(
		'Odysseia'  => 'Odysseia',
		'Martlet'   => 'Martlet Express',
		'Endurance' => 'Endurance Transport'
	);
	
	public $statuses_ar = array(
		'not-solved' => 'Not solved',
		'solved'     => 'Solved',
	);
	
	public $set_up = array(
		'completed'     => 'Completed',
		'not_completed' => 'Not completed',
		'error'         => 'Error',
	);
	
	public $set_up_platform = array(
		'rmis'    => 'RMIS',
		'dat'     => 'DAT',
		'highway' => 'Highway',
		'manual'  => 'Manual',
		'mcp'     => 'MCP',
		'other'   => 'Other',
	);
	
	public $factoring_broker = array(
		'approved'                 => 'Approved',
		'denied'                   => 'Denied',
		'credit-approval-required' => 'Credit Approval Required',
		'one-load-allowed'         => 'One load allowed',
		'not-found'                => 'Not Found',
		'can-be-discussed'         => 'Can be discussed',
	);
	
	public $company_status = array(
		'approved'             => 'Approved',
		'be_attentive'         => 'Be attentive',
		'discuss_with_manager' => 'Discuss with manager',
		'blocked'              => 'Blocked',
	);
	
	function get_offices_from_acf() {
		$field_key    = 'field_670828a1b54fc';
		$field_object = get_field_object( $field_key );
		
		return $field_object;
	}
	
	function formatDate( $inputDate ) {
		// Создаем объект даты из строки
		$dateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $inputDate );
		
		if ( $dateTime ) {
			// Возвращаем дату в формате MM/DD/YYYY HH:mm:ss
			return $dateTime->format( 'm/d/Y H:i:s' );
		} else {
			// Возвращаем ошибку, если формат не соответствует
			return "Неверный формат даты.";
		}
	}
	
	function get_recruiters() {
		// Аргументы для получения пользователей с ролью 'recruiter'
		$args = array(
			'role__in' => array( 'recruiter', 'recruiter-tl' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$office     = get_field( 'work_location', "user_" . $user->ID );
			// Собираем массив с ID и полным именем
			$dispatchers[] = array(
				'id'       => $user->ID,
				'fullname' => trim( $first_name . ' ' . $last_name ),
				'office'   => $office,
			);
		}
		
		return $dispatchers;
	}
	
	function get_dispatchers( $office_user = null ) {
		// Аргументы для получения пользователей с ролью 'dispatcher'
		
		$report  = new TMSReports();
		$project = $report->project;
		
		$args = array(
			'role__in' => array( 'dispatcher', 'dispatcher-tl', 'expedite_manager' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$office     = get_field( 'work_location', "user_" . $user->ID );
			$access     = get_field( 'permission_view', 'user_' . $user->ID );
			// Собираем массив с ID и полным именем
			if ( in_array( $project, $access ) ) {
				if ( is_null( $office_user ) ):
					$dispatchers[] = array(
						'id'       => $user->ID,
						'fullname' => trim( $first_name . ' ' . $last_name ),
						'office'   => $office,
					);
				else:
					if ( $office_user === $office ):
						$dispatchers[] = array(
							'id'       => $user->ID,
							'fullname' => trim( $first_name . ' ' . $last_name ),
							'office'   => $office,
						);
					endif;
				endif;
			}
		}
		
		return $dispatchers;
	}
	
	function get_administrators() {
		// Аргументы для получения пользователей с ролью 'dispatcher'
		$args = array(
			'role__in' => array( 'administrator' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			
			// Собираем массив с ID и полным именем
			$dispatchers[] = array(
				'id'       => $user->ID,
				'fullname' => trim( $first_name . ' ' . $last_name ),
			);
		}
		
		return $dispatchers;
	}
	
	function get_dispatchers_tl( $office_user = null ) {
		// Аргументы для получения пользователей с ролью 'dispatcher'
		$args = array(
			'role__in' => array( 'dispatcher-tl' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		);
		
		// Получаем пользователей с заданной ролью
		$users = get_users( $args );
		
		// Массив для хранения информации о пользователях
		$dispatchers = array();
		
		// Перебираем каждого пользователя
		foreach ( $users as $user ) {
			// Получаем имя и фамилию пользователя
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$office     = get_field( 'work_location', "user_" . $user->ID );
			
			if ( is_null( $office_user ) ):
				$dispatchers[] = array(
					'id'       => $user->ID,
					'fullname' => trim( $first_name . ' ' . $last_name ),
					'office'   => $office,
				);
			else:
				if ( $office_user === $office ):
					$dispatchers[] = array(
						'id'       => $user->ID,
						'fullname' => trim( $first_name . ' ' . $last_name ),
						'office'   => $office,
					);
				endif;
			endif;
		}
		
		return $dispatchers;
	}
	
	function get_my_team_leader( $user_id = null, $project = null ) {
		$current_user_id = $user_id ? intval( $user_id ) : get_current_user_id(); // Get the current user ID
		$current_select  = $project ?? get_field( 'current_select', 'user_' . $current_user_id );
		
		$args = array(
			'role__in'   => array( 'dispatcher-tl', 'expedite_manager' ),
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $current_user_id . '"',
					// Ensure the ID is treated as a whole number in serialized arrays
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"',
					// Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		
		$query        = new WP_User_Query( $args );
		$team_leaders = $query->get_results();
		$ids          = array();
		
		if ( ! empty( $team_leaders ) ) {
			foreach ( $team_leaders as $leader ) {
				$ids[] = $leader->ID;
			}
		}
		
		return $ids;
	}

	/**
	 * Get expedite managers for the current user
	 * 
	 * @param int|null $user_id User ID, defaults to current user
	 * @param string|null $project Project name, defaults to current_select field
	 * @return array Array of expedite manager IDs
	 */
	function get_my_expedite_manager( $user_id = null, $project = null ) {
		$current_user_id = $user_id ? intval( $user_id ) : get_current_user_id(); // Get the current user ID
		$current_select  = $project ?? get_field( 'current_select', 'user_' . $current_user_id );
		
		$args = array(
			'role'       => 'expedite_manager',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $current_user_id . '"',
					// Ensure the ID is treated as a whole number in serialized arrays
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"',
					// Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		
		$query             = new WP_User_Query( $args );
		$expedite_managers = $query->get_results();
		$ids               = array();
		
		if ( ! empty( $expedite_managers ) ) {
			foreach ( $expedite_managers as $manager ) {
				$ids[] = $manager->ID;
			}
		}
		
		return $ids;
	}
	
	function compare_pick_up_locations( $originalJson, $modifiedJson ) {
		
		$originalArray = json_decode( $originalJson, true );
		$modifiedArray = json_decode( $modifiedJson, true );
		
		if ( $originalArray === null || $modifiedArray === null ) {
			return "JSON decoding error: " . json_last_error_msg();
		}
		
		$originalKeys = array_column( $originalArray, 'address' );
		$modifiedKeys = array_column( $modifiedArray, 'address' );
		
		$added   = array_diff( $modifiedKeys, $originalKeys );
		$removed = array_diff( $originalKeys, $modifiedKeys );
		
		$output = "";
		
		// Handle added points
		if ( ! empty( $added ) ) {
			foreach ( $added as $index => $address ) {
				$output .= "Added new point: $address<br>";
			}
		}
		
		// Handle removed points
		if ( ! empty( $removed ) ) {
			foreach ( $removed as $index => $address ) {
				$output .= "Removed point: $address<br>";
			}
		}
		
		// Handle modified points
		foreach ( $modifiedArray as $modifiedLocation ) {
			foreach ( $originalArray as $originalLocation ) {
				if ( $originalLocation[ 'address' ] === $modifiedLocation[ 'address' ] ) {
					$changes = [];
					foreach ( $modifiedLocation as $key => $value ) {
						if ( isset( $originalLocation[ $key ] ) && $originalLocation[ $key ] !== $value ) {
							$changes[] = ucfirst( $key ) . " changed from '{$originalLocation[$key]}' to '$value'";
						}
					}
					if ( ! empty( $changes ) ) {
						$output .= "Modified point: {$modifiedLocation['address']}<br>" . implode( "<br>", $changes ) . "<br>";
					}
				}
			}
		}
		
		// If no changes were detected
		if ( empty( $output ) ) {
			$output = "No changes detected.<br>";
		}
		
		return $output;
	}
	
	function formatJsonForEmail( $jsonString ) {
		$decodedArray = json_decode( $jsonString, true );
		
		if ( $decodedArray === null ) {
			return "JSON decoding error: " . json_last_error_msg();
		}
		
		$output = "";
		foreach ( $decodedArray as $location ) {
			$output .= "Address: " . $location[ 'address' ] . "<br>";
			$output .= "Contact: " . $location[ 'contact' ] . "<br>";
			$output .= "Date: " . $location[ 'date' ] . "<br>";
			$output .= "Information: " . $location[ 'info' ] . "<br>";
			$output .= "Type: " . $location[ 'type' ] . "<br>";
			$output .= "Start Time: " . ( $location[ 'time_start' ] ?: 'not specified' ) . "<br>";
			$output .= "End Time: " . ( $location[ 'time_end' ] ?: 'not specified' ) . "<br>";
			$output .= "Strict Time: " . ( $location[ 'strict_time' ] ?: 'no' ) . "<br>";
			$output .= "--------------------------<br>";
		}
		
		return $output;
	}
	
	function get_quick_pay_methods() {
		return $this->quick_pay_methods;
	}
	
	function get_quick_pay_methods_for_accounting( $key ) {
		$array_methods = $this->get_quick_pay_methods();
		
		if ( ! in_array( $key, $array_methods ) ) {
			return $array_methods[ $key ][ 'label' ];
		}
		
		return false;
	}
	
	function get_ar_statuses() {
		return $this->statuses_ar;
	}
	
	function get_company_status() {
		return $this->company_status;
	}
	
	function get_factoring_broker() {
		return $this->factoring_broker;
	}
	
	function get_factoring_statuses() {
		return $this->factoring_status;
	}
	
	function get_set_up_platform() {
		return $this->set_up_platform;
	}
	
	function get_bank_statuses() {
		return $this->bank_statuses;
	}
	
	function get_driver_payment_statuses() {
		return $this->driver_payment_statuses;
	}
	
	function get_set_up() {
		return $this->set_up;
	}
	
	function get_load_types() {
		return $this->types;
	}
	
	function get_states() {
		return $this->select;
	}
	
	function get_statuses() {
		return $this->statuses;
	}
	
	function get_invoices() {
		return $this->invoices;
	}
	
	function get_factoring_status() {
		return $this->factoring_status;
	}
	
	function convert_to_number( $string ) {
		// Убираем все запятые из строки
		$cleaned_string = str_replace( ',', '', $string );
		
		// Преобразуем строку в число
		return floatval( $cleaned_string );
	}
	
	function get_sources() {
		return $this->sources;
	}
	
	function get_instructions() {
		
		return $this->features;
	}
	
	function get_locations_plain_text( $json_string, $type = 'Shipper' ) {
		
		$shipper   = new TMSReportsShipper();
		$locations = json_decode( str_replace( "\'", "'", stripslashes( $json_string ) ), ARRAY_A ) ?: [];
		
		if ( ! is_array( $locations ) ) {
			return "Invalid location data\n";
		}
		
		$output = '';
		
		foreach ( $locations as $index => $location ) {
			$title = $type . ( $index > 0 ? ' #' . ( $index + 1 ) : '' );
			
			$address_id  = $location[ 'address_id' ] ?? '';
			$shipper_obj = $shipper->get_shipper_by_id( $address_id, ARRAY_A );
			
			if ( ! empty( $shipper_obj ) ) {
				
				if ( isset( $shipper_obj[ 0 ] ) ) {
					$shipper_obj = $shipper_obj[ 0 ];
				}
			}
			$shipper_name = $shipper_obj[ 'shipper_name' ] ?? $type;
			
			$address_parts  = explode( ',', $location[ 'address' ] );
			$address_line   = trim( $address_parts[ 0 ] ?? '' );
			$city_state_zip = trim( implode( ',', array_slice( $address_parts, 1 ) ) );
			

			if ( ! empty( $location[ 'date' ] ) ) {
				// Пробуем с форматом Y-m-d\TH:i
				$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $location[ 'date' ] );

				if ( ! $datetime ) {
					// Если не получилось, пробуем с форматом Y-m-d
					$datetime = DateTime::createFromFormat( 'Y-m-d', $location[ 'date' ] );
				}
				
				if ( $datetime ) {
					$date = esc_html( $datetime->format( 'm/d/Y' ) );
				}
			}


			
			if ( isset( $location[ 'time_start' ] ) && isset( $location[ 'time_end' ] ) ) {
				$time = trim( $location[ 'time_start' ] . ( $location[ 'time_end' ] ? ' - ' . $location[ 'time_end' ]
						: '' ) );
			} else {
				$time = null;
			}
			
			$address_line   = preg_replace( '/[\x{2028}\x{2029}]/u', '', $address_line );
			$title          = preg_replace( '/[\x{2028}\x{2029}]/u', '', $title );
			$shipper_name   = preg_replace( '/[\x{2028}\x{2029}]/u', '', $shipper_name );
			$city_state_zip = preg_replace( '/[\x{2028}\x{2029}]/u', '', $city_state_zip );
			
			$output .= $title . ": $shipper_name:\n";
			$output .= "Address: $address_line\n";
			$output .= "$city_state_zip\n";
			$output .= "Date & time: $date" . ( $time ? " | $time" : '' ) . "\n\n";
		}
		
		return $output;
	}
	
	function find_user_by_emails( $emails_string, $exclude_user_id = null ) {
		$emails = array_map( 'trim', explode( ',', $emails_string ) );
		
		foreach ( $emails as $email ) {
			$user = get_user_by( 'email', $email );
			// get weekend
			$fields   = get_fields( 'user_' . $user->ID );
			$today    = strtolower( date( 'l' ) );
			$weekends = get_field_value( $fields, 'weekends' ) ?? [];
			
			if ( is_array( $weekends ) && in_array( $today, $weekends, true ) ) {
				continue;
			}
			
			if ( $user && $user->ID !== $exclude_user_id ) {
				return $user;
			}
		}
		
		return null;
	}
	
	/**
	 * Get chat participants for load chat creation
	 * 
	 * @param int $dispatcher_id Dispatcher user ID
	 * @param string $project Project name (Odysseia, Martlet, Endurance)
	 * @return array Array of participants with id and role
	 */
	function get_chat_participants( $dispatcher_id, $project ) {
		$participants = array();
		
		// 1. Add dispatcher
		if ( $dispatcher_id ) {
			$dispatcher_user = get_user_by( 'id', $dispatcher_id );
			if ( $dispatcher_user ) {
				$participants[] = array(
					'id' => (string) $dispatcher_id,
					'role' => 'dispatcher'
				);
			}
		}
		
		// Get dispatcher's work_location
		$dispatcher_work_location = get_field( 'work_location', 'user_' . $dispatcher_id );
		
		// 2. Get tracking/nightshift/morning users by dispatcher group
		$emails = new TMSEmails();
		$user_fields = get_fields( 'user_' . $dispatcher_id );
		$current_select = $project ?? get_field_value( $user_fields, 'current_select' );
		
		// Define role groups
		$role_groups = array(
			'tracking' => array('tracking', 'tracking-tl'),
			'nightshift' => array('nightshift_tracking'),
			'morning' => array('morning_tracking')
		);
		
		// Query each role group separately
		foreach ( $role_groups as $group_name => $roles ) {
			$meta_query_args = array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $dispatcher_id . '"',
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"',
					'compare' => 'LIKE'
				)
			);
			
			// Add work_location filter if dispatcher has one (but not for nightshift/morning - office doesn't matter for them)
			if ( $dispatcher_work_location && $group_name !== 'nightshift' && $group_name !== 'morning' ) {
				$meta_query_args[] = array(
					'key'     => 'work_location',
					'value'   => $dispatcher_work_location,
					'compare' => '='
				);
			}
			
			$args = array(
				'role__in'   => $roles,
				'meta_query' => $meta_query_args
			);
			
			$query = new WP_User_Query( $args );
			$users = $query->get_results();
			
			if ( ! empty( $users ) ) {
				foreach ( $users as $user ) {
					// Get user's primary role (first role in array)
					$user_roles = $user->roles;
					$user_role = ! empty( $user_roles ) ? $user_roles[0] : 'subscriber';
					
					$participants[] = array(
						'id' => (string) $user->ID,
						'role' => $user_role
					);
				}
			}
		}
		
		// 3. Get tracking-tl in dispatcher's group with same work_location (if not already added)
		if ( $dispatcher_work_location ) {
			$args = array(
				'role' => 'tracking-tl',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'my_team',
						'value'   => '"' . $dispatcher_id . '"',
						'compare' => 'LIKE'
					),
					array(
						'key'     => 'permission_view',
						'value'   => '"' . $current_select . '"',
						'compare' => 'LIKE'
					),
					array(
						'key'     => 'work_location',
						'value'   => $dispatcher_work_location,
						'compare' => '='
					)
				)
			);
			
			$query = new WP_User_Query( $args );
			$tracking_tls = $query->get_results();
			
			if ( ! empty( $tracking_tls ) ) {
				foreach ( $tracking_tls as $user ) {
					// Check if already added
					$already_added = false;
					foreach ( $participants as $participant ) {
						if ( $participant['id'] == (string) $user->ID ) {
							$already_added = true;
							break;
						}
					}
					
					if ( ! $already_added ) {
						// Get user's primary role
						$user_roles = $user->roles;
						$user_role = ! empty( $user_roles ) ? $user_roles[0] : 'subscriber';
						
						$participants[] = array(
							'id' => (string) $user->ID,
							'role' => $user_role
						);
					}
				}
			}
		}
		
		// 4. Get billing users by project and work_location
		if ( $dispatcher_work_location ) {
			$args = array(
				'role' => 'billing',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'permission_view',
						'value'   => '"' . $current_select . '"',
						'compare' => 'LIKE'
					),
					array(
						'key'     => 'work_location',
						'value'   => $dispatcher_work_location,
						'compare' => '='
					)
				)
			);
			
			$billing_users = get_users( $args );
			if ( ! empty( $billing_users ) ) {
				foreach ( $billing_users as $user ) {
					// Get user's primary role
					$user_roles = $user->roles;
					$user_role = ! empty( $user_roles ) ? $user_roles[0] : 'subscriber';
					
					$participants[] = array(
						'id' => (string) $user->ID,
						'role' => $user_role
					);
				}
			}
		}
		
		// 5. Get all tracking-tl by project and work_location (not only in dispatcher's group)
		if ( $dispatcher_work_location ) {
			$args = array(
				'role' => 'tracking-tl',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'permission_view',
						'value'   => '"' . $current_select . '"',
						'compare' => 'LIKE'
					),
					array(
						'key'     => 'work_location',
						'value'   => $dispatcher_work_location,
						'compare' => '='
					)
				)
			);
			
			$all_tracking_tls = get_users( $args );
			if ( ! empty( $all_tracking_tls ) ) {
				foreach ( $all_tracking_tls as $user ) {
					// Check if already added
					$already_added = false;
					foreach ( $participants as $participant ) {
						if ( $participant['id'] == (string) $user->ID ) {
							$already_added = true;
							break;
						}
					}
					
					if ( ! $already_added ) {
						// Get user's primary role
						$user_roles = $user->roles;
						$user_role = ! empty( $user_roles ) ? $user_roles[0] : 'subscriber';
						
						$participants[] = array(
							'id' => (string) $user->ID,
							'role' => $user_role
						);
					}
				}
			}
		}
		
		// Remove duplicates by ID
		$unique_participants = array();
		$seen_ids = array();
		foreach ( $participants as $participant ) {
			if ( ! in_array( $participant['id'], $seen_ids ) ) {
				$unique_participants[] = $participant;
				$seen_ids[] = $participant['id'];
			}
		}
		
		return $unique_participants;
	}

	/**
	 * Build full chat context (participants, display info, missing required roles) for a given load meta.
	 *
	 * Used both for create and future update chat actions.
	 *
	 * @param array  $meta    Load meta array.
	 * @param string $project Project name (Odysseia, Martlet, Endurance).
	 *
	 * @return array{
	 *     participants: array<array{id:string,role:string}>,
	 *     participants_info: string[],
	 *     missing_roles: string[]
	 * }
	 */
	public function get_load_chat_context( $meta, $project ) {
		// Dispatcher is the entry point for team context
		$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
		$dispatcher_id       = $dispatcher_initials ? (int) $dispatcher_initials : 0;

		$chat_participants  = array();
		$participants_info  = array();
		$required_roles     = array( 'dispatcher', 'tracking', 'nightshift_tracking', 'morning_tracking' );
		$found_roles        = array();
		$missing_roles      = array();

		if ( $dispatcher_id ) {
			// Base participants (dispatcher + tracking/night/morning/billing/tl)
			$chat_participants = $this->get_chat_participants( $dispatcher_id, $project );

			// Select attached driver for chat
			// Priority: 3 > 2 > 1 (if 1 and 2 exist, use 2)
			$driver_id        = get_field_value( $meta, 'attached_driver' );
			$second_driver_id = get_field_value( $meta, 'attached_second_driver' );
			$third_driver_id  = get_field_value( $meta, 'attached_third_driver' );

			$selected_driver_id = null;
			if ( ! empty( $third_driver_id ) ) {
				$selected_driver_id = (int) $third_driver_id;
			} elseif ( ! empty( $second_driver_id ) ) {
				$selected_driver_id = (int) $second_driver_id;
			} elseif ( ! empty( $driver_id ) ) {
				$selected_driver_id = (int) $driver_id;
			}

			// Add selected driver if exists
			if ( $selected_driver_id ) {
				$chat_participants[] = array(
					'id'   => (string) $selected_driver_id,
					'role' => 'driver',
				);
			}

			// Build participants info for tooltip / debug and collect roles present
			foreach ( $chat_participants as $participant ) {
				$user_id   = isset( $participant['id'] ) ? (int) $participant['id'] : 0;
				$user_role = isset( $participant['role'] ) ? $participant['role'] : '';

				if ( $user_role === 'driver' ) {
					// Drivers are stored in custom tables; use TMSDrivers to get full info
					$TMSDrivers    = new TMSDrivers();
					$driver_object = $TMSDrivers->get_driver_by_id( $user_id );

					if ( $driver_object ) {
						$driver_meta = get_field_value( $driver_object, 'meta' );
						$driver_name = get_field_value( $driver_meta, 'driver_name' );

						if ( ! empty( $driver_name ) ) {
							$participants_info[] = $driver_name . ' (driver)';
							$found_roles[]       = $user_role;
						}
					}
				} else {
					$user = get_user_by( 'id', $user_id );
					if ( $user ) {
						$first_name   = get_user_meta( $user_id, 'first_name', true );
						$last_name    = get_user_meta( $user_id, 'last_name', true );
						$display_name = trim( ( $first_name ? $first_name . ' ' : '' ) . ( $last_name ? $last_name : '' ) );
						if ( empty( $display_name ) ) {
							$display_name = $user->display_name;
						}

						$participants_info[] = $display_name . ' (' . $user_role . ')';
						$found_roles[]       = $user_role;
					}
				}
			}

			// Determine which required roles are missing
			foreach ( $required_roles as $required_role ) {
				if ( ! in_array( $required_role, $found_roles, true ) ) {
					$missing_roles[] = $required_role;
				}
			}
		} else {
			// If no dispatcher, all required roles are missing
			$missing_roles = $required_roles;
		}

		return array(
			'participants'      => $chat_participants,
			'participants_info' => $participants_info,
			'missing_roles'     => $missing_roles,
		);
	}
	
	function get_tracking_message( $tracking_email, $nightshift_email, $morning_email ) {
		// Сначала ищем nightshift
		$nightshift_user = $this->find_user_by_emails( $nightshift_email );

		$morning_user = $this->find_user_by_emails( $morning_email );
		
		// Теперь ищем tracking, исключая nightshift_user
		$tracking_user = $this->find_user_by_emails( $tracking_email, $nightshift_user ? $nightshift_user->ID : null );
		// Проверяем, что пользователи найдены
		if ( ! $tracking_user ) {
			return 'Tracking user not found.';
		}
		if ( ! $nightshift_user ) {
			return 'Nightshift user not found.';
		}

		if ( ! $morning_user ) {
			return 'Morning user not found.';
		}
		
		$tracking_text = $tracking_user
			? sprintf( "%s %s",$tracking_user->first_name, get_field( 'phone_number', 'user_' . $tracking_user->ID ) )
			: '';
		
		$nightshift_text = $nightshift_user
			? sprintf( "%s %s", $nightshift_user->first_name, get_field( 'phone_number', 'user_' . $nightshift_user->ID ) )
			: '';

		$morning_text = $morning_user
			? sprintf( "%s %s", get_field( 'phone_number', 'user_' . $morning_user->ID ), $morning_user->first_name )
			: '';
		
		$text_parts = [];
		
		if ( $tracking_text ) {
			$text_parts[] = 'Our tracking team will be reaching out to you: ' . $tracking_text;
		}
		
		if ( $nightshift_text ) {
			$text_parts[] = 'Afterhours contact: ' . $nightshift_text;
		}

		if ( $morning_text ) {
			$text_parts[] = $morning_text;
		}
		
		$text = implode( ', ', $text_parts ) . '.';
		
		
		return $text;
	}
	
	function create_message_dispatch( $meta ) {
		$emails = new TMSEmails();
		global $global_options;
		
		$user_id                = get_current_user_id();
		$project                = get_field( 'current_select', 'user_' . $user_id );
		$project_without_format = $project;
		$project                = strtolower( $project );
		$commodity              = get_field_value( $meta, 'commodity' );
		$weight                 = get_field_value( $meta, 'weight' );
		$instructions           = get_field_value( $meta, 'instructions' );
		$notes                  = get_field_value( $meta, 'notes' );
		$driver_rate_raw        = get_field_value( $meta, 'driver_rate' );
		$pick_up_location       = get_field_value( $meta, 'pick_up_location' );
		$delivery_location      = get_field_value( $meta, 'delivery_location' );
		$dispatcher_initials    = get_field_value( $meta, 'dispatcher_initials' );

		$emails_by_groups = $emails->get_tracking_emails_by_groups($dispatcher_initials);
		$tracking = $emails_by_groups['tracking'][0];
		$nightshift = $emails_by_groups['nightshift'][0];
		$morning = $emails_by_groups['morning'][0];
		
		$last_message = $this->get_tracking_message( $tracking, $nightshift, $morning );
		
		$driver_rate             = esc_html( '$' . $this->format_currency( $driver_rate_raw ) );
		$get_instructions_values = $this->get_instructions_values( $instructions );
		$user                    = get_user_by( 'id', $dispatcher_initials );
		$text                    = "Hello, it's {$user->first_name} from {$project_without_format}.\n\n";
		$text                    .= $this->get_locations_plain_text( $pick_up_location, 'Shipper' );
		$text                    .= $this->get_locations_plain_text( $delivery_location, 'Receiver' );
		
		$company_name = $this->company_name = get_field_value( $global_options, 'company_name_' . $project );
		$company_mc   = $this->company_mc = get_field_value( $global_options, 'company_mc_' . $project );
		$company_dot  = $this->company_dot = get_field_value( $global_options, 'company_dot_' . $project );
		
		$text .= "Commodity: {$commodity}\n";
		$text .= "Weight: {$weight} lbs\n";
		
		if ( $instructions ) {
			$text .= "Instructions: {$get_instructions_values}\n";
		}
		
		$text .= "Rate: {$driver_rate}\n";
		
		if ( $notes ) {
			$text .= "Notes: {$notes}\n";
		}
		
		$text .= "\n{$last_message}\n\n";
		
		$text .= trim( "You're working under {$company_name}. authority: MC# {$company_mc}, DOT# {$company_dot}.\n
Must be confirmed if you happen to contact shipper, receiver or broker.\n
Please make sure to send pictures of the freight and a clear photo of the Bill of Lading (BOL) both once the freight is loaded and after it has been delivered.\n
Safe travels, and we hope you have a smooth trip!\n
Kindly confirm once you've received this message." ) . "\n";
		
		return preg_replace( '/[\x{2028}\x{2029}\x{0085}\x{000B}\x{000C}\x{000D}\x{2424}]/u', ' ', $text );
	}
	
	function get_instructions_values( $instructions ) {
		if ( empty( $instructions ) ) {
			return false;
		}
		
		$instructions_label = $this->get_instructions();
		
		// Return early if instructions labels are empty
		if ( empty( $instructions_label ) ) {
			return false;
		}
		
		// Safely handle different input formats
		$instruction_keys = is_array( $instructions ) ? $instructions
			: array_map( 'trim', explode( ',', $instructions ) );
		
		$labels = array_filter( array_map( function( $key ) use ( $instructions_label ) {
			return isset( $instructions_label[ $key ] ) ? $instructions_label[ $key ] : null;
		}, $instruction_keys ) );
		
		return empty( $labels ) ? false : implode( ', ', $labels );
	}
	
	function get_label_by_key( $key = null, $search_list = null ) {
		
		if ( is_null( $key ) || is_null( $search_list ) ) {
			return false;
		}
		
		if ( $search_list === 'company_status' ) {
			return isset( $this->company_status[ $key ] ) ? $this->company_status[ $key ] : $key;
		}
		
		if ( $search_list === 'bank_statuses' ) {
			return isset( $this->bank_statuses[ $key ] ) ? $this->bank_statuses[ $key ] : $key;
		}
		if ( $search_list === 'driver_payment_statuses' ) {
			return isset( $this->driver_payment_statuses[ $key ] ) ? $this->driver_payment_statuses[ $key ] : $key;
		}
		
		if ( $search_list === 'set_up_platform' ) {
			return isset( $this->set_up_platform[ $key ] ) ? $this->set_up_platform[ $key ] : $key;
		}
		
		if ( $search_list === 'invoices' ) {
			return isset( $this->invoices[ $key ] ) ? $this->invoices[ $key ] : $key;
		}
		
		if ( $search_list === 'factoring_status' ) {
			return isset( $this->factoring_status[ $key ] ) ? $this->factoring_status[ $key ] : $key;
		}
		
		if ( $search_list === 'statuses' ) {
			return isset( $this->statuses[ $key ] ) ? $this->statuses[ $key ] : $key;
		}
		
		if ( $search_list === 'types' ) {
			return isset( $this->types[ $key ] ) ? $this->types[ $key ] : $key;
		}
		
		if ( $search_list === 'sources' ) {
			return isset( $this->sources[ $key ] ) ? $this->sources[ $key ] : $key;
		}
		
		if ( $search_list === 'factoring_broker' ) {
			return isset( $this->factoring_broker[ $key ] ) ? $this->factoring_broker[ $key ] : $key;
		}
		if ( $search_list === 'processing' ) {
			return isset( $this->processing[ $key ] ) ? $this->processing[ $key ] : $key;
		}
		
		if ( $search_list === 'instructions' && ! empty( $key ) ) {
			return $this->get_icons_from_keys( $key );
		}
		
		return false;
	}
	
	function get_icons_from_keys( $keys_string ) {
		// Разбиваем строку на массив по запятым и удаляем пробелы
		$keys = array_map( 'trim', explode( ',', $keys_string ) );
		
		// Создаем массив для хранения иконок
		$icons = [];
		
		// Перебираем каждый ключ и выполняем switch
		foreach ( $keys as $key ) {
			$tooltip = isset( $this->features[ $key ] ) ? $this->features[ $key ] : $key;
			switch ( $key ) {
				case 'hazmat':
					$icons[] = $this->get_icon_hazmat( $tooltip ); // +
					break;
				case 'tanker-end':
					$icons[] = $this->get_icon_tanker_end( $tooltip ); // +
					break;
				case 'driver-assist':
					$icons[] = $this->get_icon_assist( $tooltip ); // new
					break;
				case 'liftgate':
					$icons[] = $this->get_icon_liftgate( $tooltip ); // +
					break;
				case 'pallet-jack':
					$icons[] = $this->get_icon_palet_jack( $tooltip ); // +
					break;
				case 'dock-high':
					$icons[] = $this->get_icon_dock_high( $tooltip ); // +
					break;
				case 'true-team':
					$icons[] = $this->get_icon_true_team( $tooltip ); // new
					break;
				case 'fake-team':
					$icons[] = $this->get_icon_fake_team( $tooltip ); // new
					break;
				case 'tsa':
					$icons[] = $this->get_icon_tsa( $tooltip ); // +
					break;
				case 'twic':
					$icons[] = $this->get_icon_twic( $tooltip ); // +
					break;
				case 'airport':
					$icons[] = $this->get_icon_airport( $tooltip ); // new
					break;
				case 'round-trip':
					$icons[] = $this->get_icon_round_trip( $tooltip ); // new
					break;
				case 'alcohol':
					$icons[] = $this->get_icon_alcohol( $tooltip ); // new
					break;
				case 'temperature-control':
					$icons[] = $this->get_icon_temperature_control( $tooltip ); // new
					break;
				case 'ace':
					$icons[] = $this->get_icon_ace( $tooltip ); // new
					break;
				case 'aci':
					$icons[] = $this->get_icon_aci( $tooltip ); // new
					break;
				case 'mexico':
					$icons[] = $this->get_icon_mexico( $tooltip ); // +/-
					break;
				case 'military-base':
					$icons[] = $this->get_icon_military( $tooltip ); // new
					break;
				case 'blind-shipment':
					$icons[] = $this->get_icon_blind_shipment( $tooltip ); // new
					break;
				case 'partial':
					$icons[] = $this->get_icon_partial( $tooltip ); // new
					break;
				case 'white-glove-service':
					$icons[] = $this->get_icon_glove( $tooltip ); // new
					break;
				case 'high-value-freight':
					$icons[] = $this->get_icon_diamond( $tooltip ); // new
					break;
				case 'fragile':
					$icons[] = $this->get_icon_fragile( $tooltip ); // new
					break;
				default:
					break;
			}
		}
		
		// Объединяем иконки в строку и возвращаем
		return implode( '', $icons );
	}
	
	function get_months() {
		$months = array(
			1  => 'January',
			2  => 'February',
			3  => 'March',
			4  => 'April',
			5  => 'May',
			6  => 'June',
			7  => 'July',
			8  => 'August',
			9  => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December',
		);
		
		return $months;
	}
	
	function message_top( $type, $message, $button_class = '', $button_text = '' ) {
		ob_start();
		
		if ( ! $message ) {
			return '';
		}
		
		if ( $type === 'success' ) {
			$typeMessage      = 'Success';
			$typeMessageClass = 'alert-success';
			$typeMessageSvg   = '#check-circle-fill';
			$button_class     .= ' btn-outline-success';
		} else if ( $type === 'info' ) {
			$typeMessage      = 'Info';
			$typeMessageClass = 'alert-info';
			$typeMessageSvg   = '#info-fill';
			$button_class     .= ' btn-outline-info';
		} else {
			$typeMessage      = 'Danger';
			$typeMessageClass = 'alert-danger';
			$typeMessageSvg   = '#exclamation-triangle-fill';
			$button_class     .= ' btn-outline-danger';
		}
		
		?>
        <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
            <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </symbol>
            <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
            </symbol>
            <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </symbol>
        </svg>

        <div class="alert <?php echo $typeMessageClass; ?> d-flex align-items-center" role="alert">
            <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img"
                 aria-label="<?php echo $typeMessage; ?>:">
                <use xlink:href="<?php echo $typeMessageSvg; ?>"/>
            </svg>
            <div class="d-flex justify-content-between align-items-center w-100">
                <span>
				    <?php echo $message; ?>
                </span>
				<?php if ( ! empty( $button_class ) && ! empty( $button_text ) ): ?>
                    <button class="btn <?php echo $button_class; ?>">
						<?php echo $button_text; ?>
                    </button>
				<?php endif; ?>
            </div>
        </div>
		<?php
		
		return ob_get_clean();
	}
	
	function get_user_full_name_by_id( $user_id ) {
		$user           = get_user_by( 'id', $user_id );
		$duplicate_name = get_field( 'duplicate_name', 'user_' . $user_id );
		
		if ( $user ) {
			$first_name = $user->first_name;
			$last_name  = $user->last_name;
			
			$full_name = $first_name . ' ' . $last_name;
			if ( $duplicate_name ) {
				$initials = mb_strtoupper( mb_substr( $first_name, 0, 1 ) . mb_substr( $last_name, 0, 1 ) ) . mb_substr( $last_name, 1, 1 );
			} else {
				$initials = mb_strtoupper( mb_substr( $first_name, 0, 1 ) . mb_substr( $last_name, 0, 1 ) );
			}
			
			return array(
				'full_name' => $full_name,
				'initials'  => $initials,
				'user_id'   => $user_id,
				'user_email' => $user->user_email,
			);
		}
		
		return false;
	}

	/**
	 * Get user role by user ID
	 * 
	 * @param int $user_id User ID
	 * @return string|false User role or false if user not found
	 */
	function get_user_role_by_id( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		
		if ( $user ) {
			$user_roles = $user->roles;
			
			// Return the first role (users typically have one primary role)
			if ( ! empty( $user_roles ) ) {
				return $user_roles[0];
			}
		}
		
		return false;
	}
	
	function messages_prepare( $type ) {
		
		$message = '';
		
		switch ( $type ) {
			case 'not-access':
				$message = 'You do not have access to edit or view these materials';
				break;
			case 'user-not-found':
				$message = 'User with this ID not found';
				break;
		}
		
		return $message;
	}
	
	function set_filter_params( $args, $default_office = false ) {
		$dispatcher_filter = trim( get_field_value( $_GET, 'dispatcher' ) ?? '' );
		$my_search         = trim( get_field_value( $_GET, 'my_search' ) ?? '' );
		$year              = trim( get_field_value( $_GET, 'fyear' ) ?? '' );
		$month             = trim( get_field_value( $_GET, 'fmonth' ) ?? '' );
		$load_status       = trim( get_field_value( $_GET, 'load_status' ) ?? '' );
		$source            = trim( get_field_value( $_GET, 'source' ) ?? '' );
		$factoring         = trim( get_field_value( $_GET, 'factoring' ) ?? '' );
		$invoice           = trim( get_field_value( $_GET, 'invoice' ) ?? '' );
		$office            = trim( get_field_value( $_GET, 'office' ) ?? '' );
		$type              = trim( get_field_value( $_GET, 'type' ) ?? '' );
		$date_pickup       = trim( get_field_value( $_GET, 'date_pickup' ) ?? '' );
		$date_delivery     = trim( get_field_value( $_GET, 'date_delivery' ) ?? '' );

		if ( $default_office ) {
			$args[ 'office' ] = $default_office;
		}
		
		if ( $office ) {
			$args[ 'office' ] = $office;
		}
		
		if ( $dispatcher_filter ) {
			$args[ 'dispatcher' ] = $dispatcher_filter;
		}
		
		if ( $my_search ) {
			$args[ 'my_search' ] = $my_search;
		}
		
		if ( $factoring ) {
			$args[ 'factoring' ] = $factoring;
		}
		
		if ( $invoice ) {
			$args[ 'invoice' ] = $invoice;
		}
		
		if ( $year ) {
			$args[ 'year' ] = $year;
		}
		
		if ( $month ) {
			$args[ 'month' ] = $month;
		}
		
		if ( $source ) {
			$args[ 'source' ] = $source;
		}
		
		if ( $load_status ) {
			$args[ 'load_status' ] = $load_status;
		}
		
		if ( $type ) {
			$args[ 'type' ] = $type;
		}

		if ( $date_pickup ) {
			$args[ 'date_pickup' ] = $date_pickup;
		}
		if ( $date_delivery ) {
			$args[ 'date_delivery' ] = $date_delivery;
		}

		return $args;
	}

	/**
	 * AJAX handler: returns current tracking state (counts + load_ids + tbody HTML) for real-time polling.
	 * Expects filter params in POST (or GET): type, office, dispatcher, load_status, date_pickup, date_delivery, my_search, paged.
	 */
	public function ajax_get_tracking_live_state() {
		$allowed = array( 'type', 'office', 'dispatcher', 'load_status', 'date_pickup', 'date_delivery', 'my_search', 'paged' );
		foreach ( $allowed as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$_GET[ $key ] = $_POST[ $key ];
			}
		}

		$TMSUsers = new TMSUsers();
		$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );
		$select_all_offices = $TMSUsers->check_user_role_access( array(
			'tracking', 'tracking-tl', 'morning_tracking', 'nightshift_tracking',
			'dispatcher-tl', 'expedite_manager', 'administrator', 'recruiter', 'recruiter-tl', 'hr_manager', 'moderator',
		), true );
		if ( ! $office_dispatcher || $select_all_offices ) {
			$office_dispatcher = 'all';
		}

		$type   = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
		$is_flt = ( $type === 'flt' );
		$reports = $is_flt ? new TMSReportsFlt() : $this;

		$args = array(
			'status_post'    => 'publish',
			'user_id'        => get_current_user_id(),
			'sort_by'        => 'pick_up_date',
			'exclude_status' => array( 'delivered', 'tonu', 'cancelled' ),
			'exclude_tbd'    => true,
		);
		$args = $reports->set_filter_params( $args, $office_dispatcher );

		$tracking_context = isset( $_POST['tracking_context'] ) ? sanitize_text_field( $_POST['tracking_context'] ) : '';
		if ( $tracking_context === 'my_team' ) {
			$args['my_team']        = $TMSUsers->check_group_access();
			$args['exclude_status'] = array( 'delivered', 'tonu', 'cancelled', 'waiting-on-rc' );
		}

		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

		$high_priority_args = $args;
		unset( $high_priority_args['exclude_ids'] );
		$all_high_priority = $reports->get_high_priority_loads( $high_priority_args );
		$high_priority_ids = wp_list_pluck( $all_high_priority, 'id' );
		if ( ! empty( $high_priority_ids ) ) {
			$args['exclude_ids'] = $high_priority_ids;
		}

		$high_priority_loads = array();
		if ( $current_page === 1 && ! empty( $all_high_priority ) ) {
			$high_priority_loads = $all_high_priority;
		}

		$items = $reports->get_table_items_tracking( $args );
		$items['page_type'] = 'tracking';
		$items['hide_time_controls'] = true;
		if ( $is_flt ) {
			$items['flt'] = true;
		}

		$quick_status_keys = array( '', 'waiting-on-pu-date', 'at-pu', 'loaded-enroute', 'at-del' );
		$quick_status_counts = array();
		foreach ( $quick_status_keys as $qsk ) {
			$args_count = $args;
			if ( $qsk === '' ) {
				unset( $args_count['load_status'] );
			} else {
				$args_count['load_status'] = $qsk;
			}
			$res = $reports->get_table_items_tracking( $args_count );
			$quick_status_counts[ $qsk ] = isset( $res['total_posts'] ) ? (int) $res['total_posts'] : 0;
		}
		if ( ! empty( $high_priority_loads ) ) {
			$quick_status_counts[''] += count( $high_priority_loads );
		}

		if ( $current_page === 1 && ! empty( $high_priority_loads ) ) {
			$items['results'] = array_merge( $high_priority_loads, $items['results'] );
			$items['high_priority_count'] = count( $high_priority_loads );
			$items['total_posts'] = $items['total_posts'] + count( $high_priority_loads );
			$per_page = $reports->per_page_loads;
			$items['total_pages'] = ceil( $items['total_posts'] / $per_page );
		} else {
			$items['high_priority_count'] = 0;
		}

		$items['fragment_only'] = true;

		if ( ! defined( 'TEMPLATE_PATH' ) ) {
			define( 'TEMPLATE_PATH', 'src/template-parts/report/' );
		}
		ob_start();
		get_template_part( TEMPLATE_PATH . 'tables/report', 'table-tracking', $items );
		$rows_html = ob_get_clean();

		$load_ids = array_map( 'intval', wp_list_pluck( $items['results'], 'id' ) );

		wp_send_json_success( array(
			'counts'    => $quick_status_counts,
			'load_ids'  => $load_ids,
			'rows_html' => $rows_html,
		) );
	}

	/**
	 * Parse tracking filter datetime (American format m/d/Y H:i or m/d/Y) to MySQL datetime Y-m-d H:i:s.
	 *
	 * @param string $str User input from filter.
	 * @return string|null MySQL datetime or null if invalid.
	 */
	public function parse_tracking_filter_datetime( $str ) {
		if ( empty( $str ) || ! is_string( $str ) ) {
			return null;
		}
		$str = trim( $str );
		$formats = array( 'm/d/Y H:i', 'n/j/Y G:i', 'm/d/Y', 'n/j/Y' );
		foreach ( $formats as $fmt ) {
			$dt = \DateTime::createFromFormat( $fmt, $str );
			if ( $dt ) {
				if ( strpos( $fmt, 'H' ) !== false || strpos( $fmt, 'G' ) !== false ) {
					return $dt->format( 'Y-m-d H:i:s' );
				}
				return $dt->format( 'Y-m-d' ) . ' 00:00:00';
			}
		}
		return null;
	}
	
	function set_filter_params_arr( $args ) {
		$my_search = trim( get_field_value( $_GET, 'my_search' ) );
		$status    = trim( get_field_value( $_GET, 'status' ) );
		
		if ( ! $status ) {
			$status = 'not-solved';
		}
		
		if ( $status ) {
			$args[ 'status' ] = $status;
		}
		
		if ( $my_search ) {
			$args[ 'my_search' ] = $my_search;
		}
		
		return $args;
	}
	
	function set_filter_unapplied( $args ) {
		$my_search = trim( get_field_value( $_GET, 'my_search' ) );
		$status    = trim( get_field_value( $_GET, 'status' ) );
		
		if ( ! $status ) {
			$status = 'all';
		}
		
		if ( $status ) {
			$args[ 'status' ] = $status;
		}
		
		if ( $my_search ) {
			$args[ 'my_search' ] = $my_search;
		}
		
		return $args;
	}
	
	public function normalize_string( $string ) {
		return preg_replace( '/\s+/', ' ', trim( $string ) );
	}
	
	function format_currency( $value, $remove_zero = true ) {
		$cleaned   = str_replace( ',', '', $value ?? '' );
		$formatted = number_format( (float) $cleaned, 2, '.', ',' );
		if ( str_ends_with( $formatted, '.00' ) && $remove_zero ) {
			return substr( $formatted, 0, - 3 );
		}
		
		return $formatted;
	}
	
	function get_week_dates_from_monday( $date = null ) {
		// Если дата не передана, использовать текущую
		$current_date = $date ? new DateTime( $date ) : new DateTime();
		
		// Найти ближайший понедельник
		$current_day    = $current_date->format( 'w' );                 // 0 (вс) - 6 (сб)
		$days_to_monday = ( $current_day == 0 ) ? 6 : $current_day - 1; // Дни до понедельника
		$monday         = $current_date->modify( "-$days_to_monday days" );
		
		// Получить даты с понедельника по воскресенье
		$week_dates = [];
		for ( $i = 0; $i < 7; $i ++ ) {
			$week_dates[] = $monday->format( 'm/d/Y' );
			$monday->modify( '+1 day' );
		}
		
		return $week_dates;
	}
	
	function merge_unique_dispatchers( $array1, $array2, $exclude_ids = array() ) {
		// Проверяем, что $exclude_ids — это массив
		if ( ! is_array( $exclude_ids ) ) {
			$exclude_ids = array();
		}
		
		$merged = array_merge( $array1, $array2 );
		$unique = [];
		
		foreach ( $merged as $dispatcher ) {
			if ( in_array( $dispatcher[ 'id' ], $exclude_ids ) ) {
				continue;
			}
			
			$exists = false;
			foreach ( $unique as $u ) {
				if ( $u[ 'id' ] === $dispatcher[ 'id' ] ) {
					$exists = true;
					break;
				}
			}
			
			if ( ! $exists ) {
				$unique[] = $dispatcher;
			}
		}
		
		return $unique;
	}
	
	
	function generateWeeks( $date_select = null ) {
		$startDate = new DateTime( '2025-02-09' );
		$startDate->modify( 'Monday this week' ); // Find the first Monday before or on the start date
		$endDate = new DateTime();                // Current date
		
		$options      = "";
		$currentMonth = '';
		
		while ( $startDate <= $endDate ) {
			$monday = clone $startDate;
			$sunday = clone $startDate;
			$sunday->modify( 'Sunday this week' );
			
			$optionValue = $monday->format( 'Y-m-d' );
			$optionLabel = $monday->format( 'M d' ) . ' - ' . $sunday->format( 'M d' ) . ' ' . $monday->format( 'Y' );
			
			// Add a month separator if a new month is encountered
			if ( $currentMonth !== $monday->format( 'F Y' ) ) {
				$currentMonth = $monday->format( 'F Y' );
				$options      .= "<option disabled>--{$currentMonth}--</option>\n";
			}
			
			$selected = '';
			
			if ( $date_select === $optionValue ) {
				$selected = 'selected';
			}
			
			$options .= "<option " . $selected . " value=\"{$optionValue}\">{$optionLabel}</option>\n";
			
			$startDate->modify( '+1 week' ); // Move to the next week
		}
		
		return $options;
	}
	
	public function change_active_tab( $current_tab, $additional_class = '', $default_tab = 'report' ) {
		$active_tab = get_field_value( $_GET, 'tab' );
		
		if ( ! $active_tab ) {
			if ( $default_tab === 'report' ) {
				$active_tab = 'pills-customer-tab';
			} else if ( $default_tab === 'drivers' ) {
				$active_tab = 'pills-driver-contact-tab';
			} else if ( $default_tab === 'dispatchers' ) {
				$active_tab = 'pills-driver-location-tab';
			} else if ( $default_tab === 'dispatchers_new' ) {
				$active_tab = 'pills-driver-vehicle-tab';
			}
		}
		
		if ( $current_tab === $active_tab ) {
			return 'active ' . $additional_class;
		} else {
			return '';
		}
	}
	
	public function is_valid_date( $date ) {
		// Check if the date is a valid format and not '0000-00-00'
		$date_object = DateTime::createFromFormat( 'Y-m-d', $date );
		
		return $date_object && $date_object->format( 'Y-m-d' ) === $date && $date !== '0000-00-00';
	}
	
	function calculate_price_per_mile( $booked_rate_raw, $driver_rate_raw, $all_miles ) {
		// Преобразуем входные данные в числа
		$booked_rate = is_numeric( $booked_rate_raw ) ? floatval( $booked_rate_raw ) : 0.0;
		$driver_rate = is_numeric( $driver_rate_raw ) ? floatval( $driver_rate_raw ) : 0.0;
		$miles       = is_numeric( $all_miles ) ? floatval( $all_miles ) : 0.0;
		
		// Проверяем, чтобы количество миль было больше 0
		if ( $miles > 0 ) {
			$booked_rate_per_mile = $booked_rate / $miles;
			$driver_rate_per_mile = $driver_rate / $miles;
		} else {
			// Если миль нет, цена за милю неопределена
			$booked_rate_per_mile = 0.0;
			$driver_rate_per_mile = 0.0;
		}
		
		// Возвращаем результаты
		return [
			'booked_rate_per_mile' => round( $booked_rate_per_mile, 2 ),
			'driver_rate_per_mile' => round( $driver_rate_per_mile, 2 ),
		];
	}
	
	/**
	 * Driver names/phones block for load tables. Driver names are clickable and open Statistics popup.
	 *
	 * @param array $meta Load meta (must contain unit_number_name, attached_driver, etc.).
	 * @return string HTML.
	 */
	function get_driver_tempate( $meta ) {
		$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
		$driver_phone     = esc_html( get_field_value( $meta, 'driver_phone' ) );
		$macropoint_set   = get_field_value( $meta, 'macropoint_set' );
		$trucker_tools    = get_field_value( $meta, 'trucker_tools' );
		$attached_driver  = get_field_value( $meta, 'attached_driver' );

		$second_unit_number_name = esc_html( get_field_value( $meta, 'second_unit_number_name' ) );
		$second_driver_phone     = esc_html( get_field_value( $meta, 'second_driver_phone' ) );
		$attached_second_driver  = get_field_value( $meta, 'attached_second_driver' );

		$third_unit_number_name  = esc_html( get_field_value( $meta, 'third_unit_number_name' ) );
		$third_driver_phone      = esc_html( get_field_value( $meta, 'third_driver_phone' ) );
		$attached_third_driver   = get_field_value( $meta, 'attached_third_driver' );

		$shared_with_client = get_field_value( $meta, 'shared_with_client' );
		ob_start();

		$title = array();
		if ( $macropoint_set ) {
			$title[] = 'MacroPoint set';
		}
		if ( $trucker_tools ) {
			$title[] = 'Trucker Tools set';
		}
		$title_str = implode( ', ', $title );
		?>
        <div class="d-flex flex-column">
            <p class="m-0 d-inline-flex align-items-center " style="gap:2px;">
				<?php if ( ! empty( $attached_driver ) ) : ?>
					<button type="button" class="btn btn-link btn-sm p-0 text-start text-decoration-none js-driver-stats-trigger user-select-text" data-driver-id="<?php echo esc_attr( $attached_driver ); ?>"><?php echo esc_html( $unit_number_name ); ?></button>
				<?php else : ?>
					<?php echo esc_html( $unit_number_name ); ?>
				<?php endif; ?>
            </p>
			<?php if ( $driver_phone ) { ?>
                <span class="text-small relative <?php echo $shared_with_client ? 'text-primary'
					: ''; ?> <?php echo $macropoint_set ? 'macropoint' : ''; ?> <?php echo $trucker_tools
					? 'trucker_tools' : ''; ?>" title="<?php echo esc_attr( $title_str ); ?>">
				<?php echo $driver_phone; ?>
                </span>
			<?php } ?>
			<?php if ( $second_unit_number_name || $second_driver_phone ) : ?>
                <p class="m-0 d-inline-flex align-items-center " style="gap:2px;">
				<?php if ( ! empty( $attached_second_driver ) ) : ?>
					<button type="button" class="btn btn-link btn-sm p-0 text-start text-decoration-none js-driver-stats-trigger user-select-text" data-driver-id="<?php echo esc_attr( $attached_second_driver ); ?>"><?php echo esc_html( $second_unit_number_name ); ?></button>
				<?php else : ?>
					<?php echo esc_html( $second_unit_number_name ); ?>
				<?php endif; ?>
                </p>
				<?php if ( $second_driver_phone ) { ?>
                    <span class="text-small relative"><?php echo $second_driver_phone; ?></span>
				<?php } ?>
			<?php endif; ?>
			<?php if ( $third_unit_number_name || $third_driver_phone ) : ?>
                <p class="m-0 d-inline-flex align-items-center " style="gap:2px;">
				<?php if ( ! empty( $attached_third_driver ) ) : ?>
					<button type="button" class="btn btn-link btn-sm p-0 text-start text-decoration-none js-driver-stats-trigger user-select-text" data-driver-id="<?php echo esc_attr( $attached_third_driver ); ?>"><?php echo esc_html( $third_unit_number_name ); ?></button>
				<?php else : ?>
					<?php echo esc_html( $third_unit_number_name ); ?>
				<?php endif; ?>
                </p>
				<?php if ( $third_driver_phone ) { ?>
                    <span class="text-small relative"><?php echo $third_driver_phone; ?></span>
				<?php } ?>
			<?php endif; ?>
        </div>
		<?php
		return ob_get_clean();
	}

	function get_eta_data ($row) {

		$meta = get_field_value( $row, 'meta_data' );

		// Декодируем JSON, если он не пуст
		$delivery = ! empty( get_field_value( $meta, 'delivery_location' ) )
			? json_decode( str_replace( "\'", "'", stripslashes( get_field_value( $meta, 'delivery_location' ) ) ), ARRAY_A )
			: [];
		$pick_up  = ! empty( get_field_value( $meta, 'pick_up_location' ) )
			? json_decode( str_replace( "\'", "'", stripslashes( get_field_value( $meta, 'pick_up_location' ) ) ), ARRAY_A )
			: [];

		return [
			'delivery' => $delivery,
			'pick_up' => $pick_up,
		];
	}

	/**
	 * Extract ETA data for popup display
	 * Gets date, time_start, and state from short_address
	 * Attempts to get coordinates from shipper/company tables for accurate timezone determination
	 */
	function get_eta_display_data($eta_data, $type = 'delivery') {
		$location_data = isset($eta_data[$type][0]) ? $eta_data[$type][0] : null;
		
		if (!$location_data) {
			return [
				'date' => '',
				'time' => '',
				'state' => '',
				'timezone' => '',
				'latitude' => null,
				'longitude' => null,
				'shipper_eta_date' => '',
				'shipper_eta_time' => ''
			];
		}

		// Extract state from short_address (e.g., "Nikolaev AL" -> "AL")
		$short_address = $location_data['short_address'] ?? '';
		$state = '';
		if ($short_address) {
			$parts = explode(' ', trim($short_address));
			$state = end($parts); // Get last part (state)
		}

		// Get date from location data
		$date = $location_data['date'] ?? '';
		
		// Get ETA date and time from shipper form if filled
		$shipper_eta_date = $location_data['eta_date'] ?? '';
		$shipper_eta_time = $location_data['eta_time'] ?? '';
		
		// Try to get timezone and coordinates from shipper/company tables using address_id
		$latitude = null;
		$longitude = null;
		$timezone = null;
		$address_id = isset($location_data['address_id']) ? intval($location_data['address_id']) : 0;
		
		if ($address_id > 0) {
			global $wpdb;
			
			// Try to get timezone and coordinates from shipper table
			$shipper_table = $wpdb->prefix . 'reports_shipper';
			$shipper_data = $wpdb->get_row($wpdb->prepare(
				"SELECT latitude, longitude, timezone FROM $shipper_table WHERE id = %d",
				$address_id
			), ARRAY_A);
			
			if ($shipper_data) {
				if (!empty($shipper_data['latitude']) && !empty($shipper_data['longitude'])) {
					$latitude = floatval($shipper_data['latitude']);
					$longitude = floatval($shipper_data['longitude']);
				}
				if (!empty($shipper_data['timezone'])) {
					$timezone = $shipper_data['timezone'];
				}
			} else {
				// Try to get timezone and coordinates from company table
				$company_table = $wpdb->prefix . 'reports_company';
				$company_data = $wpdb->get_row($wpdb->prepare(
					"SELECT latitude, longitude, timezone FROM $company_table WHERE id = %d",
					$address_id
				), ARRAY_A);
				
				if ($company_data) {
					if (!empty($company_data['latitude']) && !empty($company_data['longitude'])) {
						$latitude = floatval($company_data['latitude']);
						$longitude = floatval($company_data['longitude']);
					}
					if (!empty($company_data['timezone'])) {
						$timezone = $company_data['timezone'];
					}
				}
			}
		}

		// If timezone is not in DB, fallback to state-based calculation (no API calls)
		// We don't pass coordinates here to avoid unnecessary API calls
		// State-based calculation is sufficient for fallback
		if (empty($timezone)) {
			$timezone = $this->get_timezone_by_state($state, $date);
		} else {
			// Timezone is stored for current date, but we need to adjust for DST if date is different
			// The stored timezone should already account for DST at the time of creation
			// For different dates, we might need to recalculate, but for now use stored timezone
			// as it's usually close enough (timezone boundaries don't change often)
		}

		return [
			'date' => $date,
			'time' => $location_data['time_start'] ?? '',
			'state' => $state,
			'timezone' => $timezone,
			'latitude' => $latitude,
			'longitude' => $longitude,
			'shipper_eta_date' => $shipper_eta_date,
			'shipper_eta_time' => $shipper_eta_time
		];
	}

	/**
	 * Ensure delivery ETA record exists when load status becomes loaded-enroute.
	 * Creates ETA from delivery location: shipper_eta_date/shipper_eta_time or date/time, same as tracking table.
	 *
	 * @param int    $post_id         Load post ID.
	 * @param string $table_meta_name Full meta table name (with prefix).
	 * @param bool   $is_flt          Is FLT load.
	 * @return void
	 */
	public function ensure_delivery_eta_for_loaded_enroute( $post_id, $table_meta_name, $is_flt ) {
		global $wpdb;

		TMSLogger::log_to_file( '[ETA-auto] ensure_delivery_eta: start post_id=' . $post_id . ', table=' . $table_meta_name . ', is_flt=' . ( $is_flt ? '1' : '0' ), 'eta-auto' );

		$eta_manager = new TMSEta();
		$existing    = $eta_manager->get_eta_record_for_display( $post_id, 'delivery', $is_flt );
		if ( ! empty( $existing['exists'] ) ) {
			TMSLogger::log_to_file( '[ETA-auto] ensure_delivery_eta: ETA already exists for post_id=' . $post_id . ', skip', 'eta-auto' );
			return;
		}

		$meta_rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta_name} WHERE post_id = %d", $post_id ),
			ARRAY_A
		);
		if ( empty( $meta_rows ) ) {
			TMSLogger::log_to_file( '[ETA-auto] ensure_delivery_eta: no meta rows for post_id=' . $post_id . ', table=' . $table_meta_name, 'eta-auto' );
			return;
		}

		$meta_assoc = array_column( $meta_rows, 'meta_value', 'meta_key' );
		$row        = [ 'id' => $post_id, 'meta_data' => $meta_assoc ];
		$eta_data   = $this->get_eta_data( $row );
		$delivery   = $this->get_eta_display_data( $eta_data, 'delivery' );

		$date = ! empty( $delivery['shipper_eta_date'] ) ? $delivery['shipper_eta_date'] : ( $delivery['date'] ?? '' );
		$time = ! empty( $delivery['shipper_eta_time'] ) ? $delivery['shipper_eta_time'] : ( $delivery['time'] ?? '' );

		TMSLogger::log_to_file( '[ETA-auto] ensure_delivery_eta: delivery data date=' . $date . ', time=' . $time . ', state=' . ( $delivery['state'] ?? '' ) . ', timezone=' . ( $delivery['timezone'] ?? '' ), 'eta-auto' );

		if ( empty( $date ) || empty( $time ) ) {
			TMSLogger::log_to_file( '[ETA-auto] ensure_delivery_eta: missing date or time, skip post_id=' . $post_id, 'eta-auto' );
			return;
		}

		$timezone = ! empty( $delivery['timezone'] ) ? $delivery['timezone'] : $this->get_timezone_by_state( $delivery['state'] ?? '', $date );
		$ok       = $eta_manager->create_or_update_eta_record( $post_id, 'delivery', $date, $time, $timezone, $is_flt, null, null, true );
		TMSLogger::log_to_file( '[ETA-auto] ensure_delivery_eta: create_or_update_eta_record result=' . ( $ok ? 'ok' : 'fail' ) . ' post_id=' . $post_id, 'eta-auto' );
	}

	/**
	 * Get timezone abbreviation by coordinates (lat/lon) or state code
	 * If coordinates are provided, uses them for more accurate timezone determination
	 * Falls back to state-based determination if coordinates are not available
	 * 
	 * @param string $state State code (e.g., 'WA', 'CA')
	 * @param string $date Date string (Y-m-d format) - if empty, uses current date
	 * @param float|null $latitude Latitude coordinate (optional)
	 * @param float|null $longitude Longitude coordinate (optional)
	 * @return string Timezone string (e.g., 'PST (UTC-8)' or 'PDT (UTC-7)')
	 */
	function get_timezone_by_state($state, $date = '', $latitude = null, $longitude = null) {
		// If coordinates are provided, try to use them for more accurate timezone
		if ( $latitude !== null && $longitude !== null ) {
			$timezone_by_coords = $this->get_timezone_by_coordinates( $latitude, $longitude, $date );
			if ( ! empty( $timezone_by_coords ) ) {
				return $timezone_by_coords;
			}
			// If coordinate-based lookup fails, fall back to state-based
		}
		// Use current date if no date provided
		if (empty($date)) {
			$date = date('Y-m-d');
		}
		
		// Parse date
		$date_obj = DateTime::createFromFormat('Y-m-d', $date);
		if (!$date_obj) {
			// Fallback to current date if parsing fails
			$date_obj = new DateTime();
		}
		
		$year = (int)$date_obj->format('Y');
		$month = (int)$date_obj->format('m');
		$day = (int)$date_obj->format('d');
		
		// Determine if DST is active for the date
		// DST in US: Second Sunday in March (2:00 AM) to First Sunday in November (2:00 AM)
		// Reference: https://time.gov/
		$is_dst = $this->is_dst_active($year, $month, $day);
		
		// Timezone maps: [state => [DST => 'abbrev (offset)', Standard => 'abbrev (offset)']]
		// Based on official US time zones from time.gov
		$timezone_map = [
			// Alaska Time
			'AK' => ['dst' => 'AKDT (UTC-8)', 'standard' => 'AKST (UTC-9)'],
			
			// Pacific Time (PST UTC-8, PDT UTC-7)
			// States: WA, OR, CA, NV (per time.gov)
			'CA' => ['dst' => 'PDT (UTC-7)', 'standard' => 'PST (UTC-8)'],
			'NV' => ['dst' => 'PDT (UTC-7)', 'standard' => 'PST (UTC-8)'],
			'WA' => ['dst' => 'PDT (UTC-7)', 'standard' => 'PST (UTC-8)'],
			'OR' => ['dst' => 'PDT (UTC-7)', 'standard' => 'PST (UTC-8)'],
			
			// Mountain Time (MST UTC-7, MDT UTC-6)
			// Arizona does NOT observe DST - always MST (UTC-7) per time.gov
			'AZ' => ['dst' => 'MST (UTC-7)', 'standard' => 'MST (UTC-7)'], // Arizona doesn't observe DST
			'CO' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'],
			'ID' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'],
			'MT' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'],
			'NM' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'],
			'UT' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'],
			'WY' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'],
			
			// Central Time
			'AL' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'AR' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'IA' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'IL' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'IN' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'KS' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'KY' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'LA' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'MN' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'MO' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'MS' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'ND' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'NE' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'OK' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'SD' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'TN' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'TX' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			'WI' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'],
			
			// Eastern Time
			'CT' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'DC' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'DE' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'FL' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'GA' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'MA' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'MD' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'ME' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'MI' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'NC' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'NH' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'NJ' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'NY' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'OH' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'PA' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'RI' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'SC' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'VA' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'VT' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			'WV' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'],
			
			// Canada - Pacific Time
			'BC' => ['dst' => 'PDT (UTC-7)', 'standard' => 'PST (UTC-8)'], // British Columbia
			'YT' => ['dst' => 'PDT (UTC-7)', 'standard' => 'PST (UTC-8)'], // Yukon
			
			// Canada - Mountain Time
			'AB' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'], // Alberta
			'NT' => ['dst' => 'MDT (UTC-6)', 'standard' => 'MST (UTC-7)'], // Northwest Territories
			// Saskatchewan - most of the province does NOT observe DST (always CST UTC-6)
			'SK' => ['dst' => 'CST (UTC-6)', 'standard' => 'CST (UTC-6)'], // Saskatchewan doesn't observe DST
			
			// Canada - Central Time
			'MB' => ['dst' => 'CDT (UTC-5)', 'standard' => 'CST (UTC-6)'], // Manitoba
			// Ontario - most uses Eastern, but western part uses Central
			'ON' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'], // Ontario (most of province)
			
			// Canada - Eastern Time
			'QC' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'], // Quebec
			'NU' => ['dst' => 'EDT (UTC-4)', 'standard' => 'EST (UTC-5)'], // Nunavut (most of territory)
			
			// Canada - Atlantic Time (AST UTC-4, ADT UTC-3)
			'NB' => ['dst' => 'ADT (UTC-3)', 'standard' => 'AST (UTC-4)'], // New Brunswick
			'NS' => ['dst' => 'ADT (UTC-3)', 'standard' => 'AST (UTC-4)'], // Nova Scotia
			'PE' => ['dst' => 'ADT (UTC-3)', 'standard' => 'AST (UTC-4)'], // Prince Edward Island
			// Newfoundland and Labrador - most uses Atlantic, but Newfoundland uses Newfoundland Time
			'NL' => ['dst' => 'NDT (UTC-2.5)', 'standard' => 'NST (UTC-3.5)'], // Newfoundland and Labrador (Newfoundland Time)
		];

		if (!isset($timezone_map[$state])) {
			return '';
		}
		
		$timezone_info = $timezone_map[$state];
		return $is_dst ? $timezone_info['dst'] : $timezone_info['standard'];
	}
	
	/**
	 * Get timezone abbreviation by coordinates (latitude/longitude) using HERE Time Zone API
	 * This function provides accurate timezone determination based on actual timezone boundaries
	 * 
	 * @param float $latitude Latitude coordinate
	 * @param float $longitude Longitude coordinate
	 * @param string $date Date string (Y-m-d format) - if empty, uses current date
	 * @return string Timezone string (e.g., 'PST (UTC-8)' or 'PDT (UTC-7)') or empty string if unable to determine
	 */
	function get_timezone_by_coordinates($latitude, $longitude, $date = '') {
		global $global_options;
		
		$api_key_here_map = get_field_value( $global_options, 'api_key_here_map' );
		
		if ( empty( $api_key_here_map ) ) {
			return '';
		}
		
		// Use current date if no date provided
		if ( empty( $date ) ) {
			$date = date( 'Y-m-d' );
		}
		
		// Parse date to timestamp for HERE API
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $date );
		if ( ! $date_obj ) {
			$date_obj = new DateTime();
		}
		$timestamp = $date_obj->getTimestamp();
		
		// Create cache key for this coordinate and date
		$cache_key = 'tms_timezone_' . md5( $latitude . '_' . $longitude . '_' . $date );
		
		// Try to get from cache first (cache for 30 days)
		$cached_timezone = get_transient( $cache_key );
		if ( $cached_timezone !== false ) {
			return $cached_timezone;
		}
		
		// Build HERE Reverse Geocoding API URL with timezone
		// Use revgeocode API with show=tz parameter to get timezone
		$url = 'https://revgeocode.search.hereapi.com/v1/revgeocode';
		$params = array(
			'at' => $latitude . ',' . $longitude,
			'lang' => 'en-US',
			'show' => 'tz',
			'apiKey' => $api_key_here_map,
		);
		
		$full_url = $url . '?' . http_build_query( $params );
		
		error_log( 'HERE Reverse Geocoding API request for timezone: ' . $full_url );
		
		// Make request
		$response = wp_remote_get( $full_url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			// Log error for debugging
			error_log( 'HERE Reverse Geocoding API request error: ' . $response->get_error_message() );
			return '';
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		// Log response for debugging if it's not successful
		if ( $response_code !== 200 || empty( $data ) ) {
			error_log( 'HERE Time Zone API error. Code: ' . $response_code . ', Response: ' . substr( $body, 0, 200 ) );
		}
		
		// Check if we have timezone information
		// HERE Reverse Geocoding API returns timezone in items[0].timeZone
		$timezone_name = null;
		if ( isset( $data[ 'items' ] ) && ! empty( $data[ 'items' ] ) ) {
			$first_item = $data[ 'items' ][ 0 ];
			if ( isset( $first_item[ 'timeZone' ] ) ) {
				$tz_data = $first_item[ 'timeZone' ];
				// timeZone can be an object/array with 'name' property or a string
				if ( is_array( $tz_data ) && isset( $tz_data[ 'name' ] ) ) {
					$timezone_name = $tz_data[ 'name' ];
				} elseif ( is_string( $tz_data ) ) {
					$timezone_name = $tz_data;
				} elseif ( is_object( $tz_data ) && isset( $tz_data->name ) ) {
					$timezone_name = $tz_data->name;
				}
			}
		}
		
		// Fallback to other possible formats
		if ( empty( $timezone_name ) ) {
			if ( isset( $data[ 'timeZone' ] ) ) {
				$tz_data = $data[ 'timeZone' ];
				if ( is_array( $tz_data ) && isset( $tz_data[ 'name' ] ) ) {
					$timezone_name = $tz_data[ 'name' ];
				} elseif ( is_string( $tz_data ) ) {
					$timezone_name = $tz_data;
				}
			} elseif ( isset( $data[ 'TimeZone' ] ) ) {
				$tz_data = $data[ 'TimeZone' ];
				if ( is_array( $tz_data ) && isset( $tz_data[ 'name' ] ) ) {
					$timezone_name = $tz_data[ 'name' ];
				} elseif ( is_string( $tz_data ) ) {
					$timezone_name = $tz_data;
				}
			} elseif ( isset( $data[ 'timezone' ] ) ) {
				$tz_data = $data[ 'timezone' ];
				if ( is_array( $tz_data ) && isset( $tz_data[ 'name' ] ) ) {
					$timezone_name = $tz_data[ 'name' ];
				} elseif ( is_string( $tz_data ) ) {
					$timezone_name = $tz_data;
				}
			}
		}
		
		// Log the raw timezone data for debugging
		if ( ! empty( $data ) ) {
			error_log( 'HERE API response data: ' . json_encode( $data ) );
		}
		
		if ( ! empty( $timezone_name ) && is_string( $timezone_name ) ) {
			
			// Log HERE API response for debugging
			error_log( 'HERE Time Zone API response for lat=' . $latitude . ', lon=' . $longitude . ': timeZone=' . $timezone_name );
			
			// Get timezone abbreviation and offset using PHP DateTimeZone
			try {
				$tz = new DateTimeZone( $timezone_name );
				$date_time = new DateTime( '@' . $timestamp, $tz );
				$offset = $tz->getOffset( $date_time );
				$offset_hours = $offset / 3600;
				
				// Format offset as UTC+X or UTC-X
				$offset_str = sprintf( 'UTC%+d', $offset_hours );
				
				// Get abbreviation (PST, PDT, etc.)
				$abbreviation = $date_time->format( 'T' );
				
				// Format as "PST (UTC-8)" or "PDT (UTC-7)"
				$timezone_string = $abbreviation . ' (' . $offset_str . ')';
				
				// Log final timezone string
				error_log( 'HERE Time Zone API final result: ' . $timezone_string . ' (from timezone name: ' . $timezone_name . ')' );
				
				// Cache the result for 30 days
				set_transient( $cache_key, $timezone_string, 30 * DAY_IN_SECONDS );
				
				return $timezone_string;
			} catch ( Exception $e ) {
				// If timezone name is invalid, log and return empty
				error_log( 'HERE Time Zone API: Invalid timezone name "' . $timezone_name . '": ' . $e->getMessage() );
				return '';
			}
		}
		
		// If API didn't return timezone, log and return empty to trigger fallback
		if ( isset( $data[ 'error' ] ) || isset( $data[ 'errorDescription' ] ) ) {
			$error_msg = isset( $data[ 'errorDescription' ] ) ? $data[ 'errorDescription' ] : ( isset( $data[ 'error' ] ) ? $data[ 'error' ] : 'Unknown error' );
			error_log( 'HERE Time Zone API error for lat=' . $latitude . ', lon=' . $longitude . ': ' . $error_msg );
		}
		
		return '';
	}
	
	/**
	 * Check if DST (Daylight Saving Time) is active for a given date
	 * According to time.gov: DST starts second Sunday in March at 2:00 AM, ends first Sunday in November at 2:00 AM
	 * Reference: https://time.gov/
	 * 
	 * @param int $year Year
	 * @param int $month Month (1-12)
	 * @param int $day Day of month
	 * @return bool True if DST is active, false otherwise
	 */
	private function is_dst_active($year, $month, $day) {
		// DST starts: Second Sunday in March at 2:00 AM (local time)
		$dst_start = $this->get_nth_sunday($year, 3, 2); // Second Sunday of March
		$dst_start->setTime(2, 0, 0);
		
		// DST ends: First Sunday in November at 2:00 AM (local time)
		// After 2:00 AM on first Sunday, clocks "fall back" 1 hour, so standard time begins
		$dst_end = $this->get_nth_sunday($year, 11, 1); // First Sunday of November
		$dst_end->setTime(2, 0, 0);
		
		// Create date object for comparison (use noon to avoid edge cases at 2:00 AM transition)
		$check_date = new DateTime();
		$check_date->setDate($year, $month, $day);
		$check_date->setTime(12, 0, 0);
		
		// DST is active if:
		// - Date is on or after DST start (second Sunday in March at 2:00 AM)
		// - Date is before DST end (first Sunday in November at 2:00 AM)
		// After DST ends, standard time is used
		return $check_date >= $dst_start && $check_date < $dst_end;
	}
	
	/**
	 * Get the Nth Sunday of a given month and year
	 * 
	 * @param int $year Year
	 * @param int $month Month (1-12)
	 * @param int $n Nth Sunday (1 = first, 2 = second, etc.)
	 * @return DateTime DateTime object for the Nth Sunday
	 */
	private function get_nth_sunday($year, $month, $n) {
		// Start with the first day of the month
		$date = new DateTime();
		$date->setDate($year, $month, 1);
		
		// Find the first Sunday
		$day_of_week = (int)$date->format('w'); // 0 = Sunday, 6 = Saturday
		$days_to_first_sunday = (7 - $day_of_week) % 7;
		
		// If today is not Sunday, move to the first Sunday
		if ($days_to_first_sunday > 0) {
			$date->modify("+{$days_to_first_sunday} days");
		}
		
		// Move to the Nth Sunday (n-1 weeks after first Sunday)
		if ($n > 1) {
			$date->modify('+' . (($n - 1) * 7) . ' days');
		}
		
		return $date;
	}
	
	function get_locations_template( $row, $template = 'default', $use_db = false, $reports_object = null ) {
		$meta = get_field_value( $row, 'meta_data' );
		
		// Даты с проверкой, чтобы избежать ошибок
		$delivery_date_raw = get_field_value( $row, 'delivery_date' );
		$pick_up_date_raw  = get_field_value( $row, 'pick_up_date' );

		$delivery_date = ! empty( $delivery_date_raw )
			? esc_html( DateTime::createFromFormat( 'Y-m-d H:i:s', $delivery_date_raw )->format( 'm/d/Y' ) ) : '';
		$pick_up_date  = ! empty( $pick_up_date_raw )
			? esc_html( DateTime::createFromFormat( 'Y-m-d H:i:s', $pick_up_date_raw )->format( 'm/d/Y' ) ) : '';
		
		$load_id = get_field_value( $row, 'id' );
		
		// Get locations from database or JSON
		if ( $use_db && $reports_object && method_exists( $reports_object, 'get_locations_from_db' ) && ! empty( $load_id ) ) {
			$db_locations = $reports_object->get_locations_from_db( $load_id );
			$pick_up = isset( $db_locations['pickup'] ) ? $db_locations['pickup'] : array();
			$delivery = isset( $db_locations['delivery'] ) ? $db_locations['delivery'] : array();
		} else {
			// Декодируем JSON, если он не пуст
			$delivery = ! empty( get_field_value( $meta, 'delivery_location' ) )
				? json_decode( str_replace( "\'", "'", stripslashes( get_field_value( $meta, 'delivery_location' ) ) ), ARRAY_A )
				: [];
			$pick_up  = ! empty( get_field_value( $meta, 'pick_up_location' ) )
				? json_decode( str_replace( "\'", "'", stripslashes( get_field_value( $meta, 'pick_up_location' ) ) ), ARRAY_A )
				: [];
		}
		
		// Обработка времени подтверждения доставки
		$proof_of_delivery_time = get_field_value( $meta, 'proof_of_delivery_time' );
		$proof_of_delivery_time = ! empty( $proof_of_delivery_time )
			? esc_html( DateTime::createFromFormat( 'Y-m-d H:i:s', $proof_of_delivery_time )->format( 'H:i:s' ) ) : '';
		
		// Подставляем нужные шаблоны
		$pick_up_template  = '';
		$delivery_template = '';
		
		if ( $template === 'default' ) {
			$pick_up_template  = $this->template_location( $pick_up );
			$delivery_template = $this->template_location( $delivery );
		} elseif ( $template === 'tracking' ) {
			$commodity = get_field_value( $meta, 'commodity' );
			$weight    = get_field_value( $meta, 'weight' );
			
			$pick_up_template  = $this->template_location_tracking( $pick_up, $commodity, $weight );
			$delivery_template = $this->template_location_tracking( $delivery, $commodity, $weight );
		}
		
		return [
			'pick_up_date'           => $pick_up_date,
			'pick_up_template'       => $pick_up_template,
			'delivery_date'          => $delivery_date,
			'delivery_template'      => $delivery_template,
			'proof_of_delivery_time' => $proof_of_delivery_time,
		];
	}
	
	
	function template_location( $data ) {
		$TMSShipper = new TMSReportsShipper();
		$output     = [];
		
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $val ) {
				if ( ! empty( $val[ 'short_address' ] ) ) {
					$tooltip       = ! empty( $val[ 'address' ] ) ? esc_attr( $val[ 'address' ] ) : '';
					$short_address = esc_html( $val[ 'short_address' ] );
					$zip_code      = '';
					
					if ( ! empty( $val[ 'address_id' ] ) ) {
						$detailed_address = $TMSShipper->get_shipper_by_id( $val[ 'address_id' ] );
						if ( ! empty( $detailed_address ) && is_array( $detailed_address ) ) {
							$zip_code = ' ' . esc_html( $detailed_address[ 0 ]->zip_code );
						} else {
							$zip_code = '<br/><span class="text-danger">This shipper has been deleted</span>';
						}
					}
					
					$output[] = '<p class="m-0" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $tooltip . '">' . $short_address . $zip_code . '</p>';
				}
			}
		}
		
		return implode( "\n", $output );
	}
	
	function get_modify_class( $meta, $key ) {
		$modify_price       = get_field_value( $meta, $key );
		$modify_price_class = '';
		
		
		if ( $modify_price === '1' ) {
			$modify_price_class = 'modified-price';
		}
		
		return $modify_price_class;
	}
	
	function template_location_tracking( $data, $commodity, $weight ) {
		$output = [];
		
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $val ) {
				if ( ! empty( $val[ 'address' ] ) ) {
					$date    = ! empty( $val[ 'date' ] ) ? esc_html( date( 'm/d/Y', strtotime( $val[ 'date' ] ) ) )
						: '';
					$tooltip = esc_attr( "Commodity: $commodity Weight: $weight" );
					$address = esc_html( $val[ 'address' ] );
					
					// Time as HH:MM (no seconds)
					$time_start  = get_field_value( $val, 'time_start' );
					$time_end    = get_field_value( $val, 'time_end' );
					$strict_time = get_field_value( $val, 'strict_time' );
					$format_time = function( $t ) {
						if ( empty( $t ) ) {
							return '';
						}
						$t = trim( (string) $t );
						return ( strlen( $t ) >= 5 ) ? substr( $t, 0, 5 ) : $t;
					};
					$time_range = '';
					if ( ! empty( $time_start ) ) {
						$time_range = esc_html( $format_time( $time_start ) );
						if ( ! empty( $time_end ) && ( $strict_time === 'false' || $strict_time === '' || $strict_time == 0 ) ) {
							$time_range .= ' - ' . esc_html( $format_time( $time_end ) );
						} else {
							$time_range .= ' - strict';
						}
					}
					
					$output[] = '
                    <div data-bs-toggle="tooltip" data-bs-placement="top" title="' . $tooltip . '" class="w-100 d-flex flex-column align-items-start">
                        <p class="m-0">' . $address . '</p>
                        <span class="text-small">' . trim( "$date $time_range" ) . '</span>
                    </div>';
				}
			}
		}
		
		return implode( "\n", $output );
	}
	
	function get_allowed_formats() {
		return array(
			'jpg',
			'jpeg',
			'png',
			'gif',
			'txt',
			'pdf',
			'doc',
			'docx',
			'xls',
			'xml',
			'xlsx',
		);
	}
	
	function get_empty_dispatcher() {
		global $global_options;
		
		$empty_dispatcher = get_field_value( $global_options, 'empty_dispatcher' );
		
		$exclude_dispatchers = array();
		
		if ( $empty_dispatcher ) {
			if ( is_array( $empty_dispatcher ) ) {
				foreach ( $empty_dispatcher as $dispatcher ) {
					$exclude_dispatchers[] = $dispatcher;
				}
			} else if ( is_numeric( $empty_dispatcher ) ) {
				$exclude_dispatchers[] = $empty_dispatcher;
			}
		}
		
		return $exclude_dispatchers;
	}
	
	function hasUrlParams( array $params ): bool {
		return ! empty( array_intersect_key( $_GET, array_flip( $params ) ) );
	}
	
	/**
	 * Clean and decode JSON string with proper quote handling
	 *
	 * @param string $json_string The JSON string to clean and decode
	 *
	 * @return array|null Decoded array or null if failed
	 */
	private function cleanAndDecodeJson( $json_string ) {
		if ( empty( $json_string ) ) {
			return null;
		}
		
		// Clean up escaped quotes before JSON decode
		$clean_json = stripslashes( $json_string );
		// Replace apostrophes with a different character to avoid JSON issues
		$clean_json = str_replace( "'", "`", $clean_json );
		// Also handle escaped double quotes
		$clean_json = str_replace( '\"', '"', $clean_json );
		// Handle escaped backticks
		$clean_json = str_replace( '\`', '`', $clean_json );
		
		return json_decode( $clean_json, true );
	}
	
	/**
	 * Extract short addresses from location array
	 *
	 * @param array|null $location_array Array of location data
	 *
	 * @return array Array of short addresses
	 */
	private function extractShortAddresses( $location_array ) {
		$addresses = [];
		
		if ( is_array( $location_array ) ) {
			foreach ( $location_array as $location ) {
				if ( ! empty( $location[ 'short_address' ] ) ) {
					$addresses[] = $location[ 'short_address' ];
				}
			}
		}
		
		return $addresses;
	}
	
	function buildHeaderAddReport( $meta ) {
		
		if ( ! $meta ) {
			return '';
		}
		
		$reference_number  = get_field_value( $meta, 'reference_number' );
		$pick_up_location  = get_field_value( $meta, 'pick_up_location' );
		$delivery_location = get_field_value( $meta, 'delivery_location' );
		
		$template_p = [];
		$template_d = [];
		
		
		if ( ! empty( $pick_up_location ) ) {
			$pick_up_location_array = $this->cleanAndDecodeJson( $pick_up_location );
			$template_p             = $this->extractShortAddresses( $pick_up_location_array );
		}
		
		if ( ! empty( $delivery_location ) ) {
			$delivery_location_array = $this->cleanAndDecodeJson( $delivery_location );
			$template_d              = $this->extractShortAddresses( $delivery_location_array );
		}
		
		$subject = sprintf( '%s %s - %s ', $reference_number, implode( ', ', $template_p ), implode( ', ', $template_d ) );
		
		return $subject;
	}
	
	/**
	 * @throws DateMalformedStringException
	 */
	function getCurrentTimeForAmerica() {
		$date_est = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
		
		return $date_est->format( 'Y-m-d H:i:s' );
	}
	
	/**
	 * Get current date in EST timezone for form inputs
	 *
	 * @return string
	 */
	function getCurrentDateForAmerica() {
		$date_est = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
		
		return $date_est->format( 'Y-m-d' );
	}
	
	/**
	 * Преобразует строку с датой из разных возможных форматов в Y-m-d (формат для input[type="date"])
	 *
	 * @param string $date_string
	 *
	 * @return string|null
	 */
	public function convert_date_for_input( $date_string ) {
		if ( empty( $date_string ) ) {
			return null;
		}
		
		$formats_to_try = array(
			'Y-m-d', // 2025-03-27
			'm/d/Y', // 03/27/2025
			'd/m/Y', // 27/03/2025
			'Y/m/d', // 2025/03/27
			'd-m-Y', // 27-03-2025
			'm-d-Y', // 03-27-2025
			'd.m.Y', // 27.03.2025
		);
		
		foreach ( $formats_to_try as $format ) {
			$date = DateTime::createFromFormat( $format, $date_string );
			if ( $date && $date->format( $format ) === $date_string ) {
				return $date->format( 'Y-m-d' );
			}
		}
		
		$timestamp = strtotime( $date_string );
		if ( $timestamp ) {
			return date( 'Y-m-d', $timestamp );
		}
		
		return null;
	}
	
	/**
	 * Check if user has meaningful statistics
	 * 
	 * @param array $stats User statistics array
	 * @return bool True if user has meaningful statistics
	 */
	public function has_meaningful_stats( $stats ) {
		if ( ! is_array( $stats ) ) {
			return false;
		}
		
		foreach ( $stats as $stat_value ) {
			if ( is_numeric( $stat_value ) && $stat_value > 0 ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get user color with fallback
	 * 
	 * @param int $user_id User ID
	 * @return string User color or default color
	 */
	public function get_user_color( $user_id ) {
		$color = get_field( 'initials_color', 'user_' . $user_id );
		return $color ?: '#030303';
	}
	
	/**
	 * Clean up invalid team members
	 * 
	 * @param int $user_id User ID
	 * @param array $team_members Array of team member IDs
	 * @return array Array of valid team member IDs
	 */
	public function cleanup_invalid_team_members( $user_id, $team_members ) {
		$TMSUser = new TMSUsers();
		$valid_members = array();
		
		foreach ( $team_members as $member_id ) {
			$user_data = $TMSUser->get_user_full_name_by_id( $member_id );
			
			if ( $user_data && is_array( $user_data ) ) {
				$valid_members[] = $member_id;
			} else {
				// Remove invalid user from ACF field
				$current_team = get_field( 'field_66f9240398a70', 'user_' . $user_id );
				if ( is_array( $current_team ) ) {
					$updated_team = array_diff( $current_team, array( $member_id ) );
					update_field( 'field_66f9240398a70', $updated_team, 'user_' . $user_id );
				}
			}
		}
		
		return $valid_members;
	}

	/**
	 * Remove ETA records for completed loads
	 * 
	 * @param int $post_id The post ID (load ID)
	 * @param string $status The load status
	 * @param bool $is_flt Whether this is an FLT load (default: false for regular loads)
	 */
	public function remove_eta_records_by_status( $post_id, $status, $is_flt = false ) {
		global $wpdb;
		
		$eta_table = $wpdb->prefix . 'eta_records';
		
		// Determine which ETA types to remove based on status
		$eta_types_to_remove = array();
		
		// For loaded-enroute, delivered, tonu, cancelled - remove pickup ETA
		if ( in_array( $status, array( 'loaded-enroute', 'at-del', 'at-pu', 'waiting-on-rc', 'delivered', 'tonu', 'cancelled' ) ) ) {
			$eta_types_to_remove[] = 'pickup';
		}
		
		// For delivered, tonu, cancelled - remove delivery ETA
		if ( in_array( $status, array( 'delivered', 'waiting-on-rc', 'tonu', 'cancelled' ) ) ) {
			$eta_types_to_remove[] = 'delivery';
		}
		
		// Remove ETA records for the specified load type
		// Since load_number in eta_records table actually stores the post_id
		foreach ( $eta_types_to_remove as $eta_type ) {
			$wpdb->delete(
				$eta_table,
				array(
					'load_number' => $post_id,
					'eta_type' => $eta_type,
					'is_flt' => $is_flt ? 1 : 0
				),
				array( '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Check driver rate change and send email notification if changed
	 * 
	 * @param string|float $old_rate Old driver rate value
	 * @param string|float $new_rate New driver rate value
	 * @param int $driver_number Driver number (1, 2, or 3)
	 * @param array $user_name User name array with 'full_name' key
	 * @param string $link Link to the load
	 * @param int $post_id Post ID of the load (optional, for checking accounting fields)
	 * @return bool True if rate was changed, false otherwise
	 */
	public function handle_driver_rate_change( $old_rate, $new_rate, $driver_number, $user_name, $link, $post_id = null ) {
		global $wpdb;

		// Temporary logging for debugging
		error_log( '=== handle_driver_rate_change CALLED ===' );
		error_log( 'Driver number: ' . $driver_number );
		error_log( 'Old rate: ' . $old_rate . ' (is_numeric: ' . ( is_numeric( $old_rate ) ? 'yes' : 'no' ) . ')' );
		error_log( 'New rate: ' . $new_rate );
		error_log( 'User name: ' . ( isset( $user_name[ 'full_name' ] ) ? $user_name[ 'full_name' ] : 'N/A' ) );
		error_log( 'Link: ' . $link );
		error_log( 'Project: ' . $this->project );
		error_log( 'Post ID: ' . ( $post_id ?: 'not provided' ) );
		
		// Check if rate changed
		if ( ! is_numeric( $old_rate ) ) {
			error_log( 'EXIT: old_rate is not numeric' );
			return false;
		}
		
		if ( $new_rate === floatval( $old_rate ) ) {
			error_log( 'EXIT: rates are equal (old: ' . floatval( $old_rate ) . ', new: ' . $new_rate . ')' );
			return false;
		}
		
		// Determine driver label for email
		$driver_labels = array(
			1 => 'Driver rate',
			2 => 'Second driver rate',
			3 => 'Third driver rate'
		);
		
		$driver_label = isset( $driver_labels[ $driver_number ] ) ? $driver_labels[ $driver_number ] : 'Driver rate';
		error_log( 'Driver label: ' . $driver_label );
		
		// Check if accounting fields are filled (payment status or quick pay method)
		$accounting_fields_filled = false;
		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$table_meta = $wpdb->prefix . $this->table_meta;
			
			// Determine which fields to check based on driver number
			$pay_status_key = '';
			$quick_pay_key = '';
			
			if ( $driver_number === 1 ) {
				$pay_status_key = 'driver_pay_statuses';
				$quick_pay_key = 'quick_pay_method';
			} elseif ( $driver_number === 2 ) {
				$pay_status_key = 'second_driver_pay_statuses';
				$quick_pay_key = 'second_quick_pay_method';
			} elseif ( $driver_number === 3 ) {
				$pay_status_key = 'third_driver_pay_statuses';
				$quick_pay_key = 'third_quick_pay_method';
			}
			
			if ( ! empty( $pay_status_key ) && ! empty( $quick_pay_key ) ) {
				// Get accounting fields from database
				$pay_status = $wpdb->get_var( $wpdb->prepare(
					"SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = %s",
					$post_id,
					$pay_status_key
				) );
				
				$quick_pay_method = $wpdb->get_var( $wpdb->prepare(
					"SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = %s",
					$post_id,
					$quick_pay_key
				) );
				
				// Check if at least one field is filled
				$accounting_fields_filled = ! empty( $pay_status ) || ! empty( $quick_pay_method );
				
				error_log( 'Accounting check - Pay status: ' . ( $pay_status ?: 'empty' ) . ', Quick pay: ' . ( $quick_pay_method ?: 'empty' ) );
				error_log( 'Accounting fields filled: ' . ( $accounting_fields_filled ? 'yes' : 'no' ) );
			}
		}
		
		// Get reference number from link or extract from post_id
		$reference_number = '';
		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$table_meta = $wpdb->prefix . $this->table_meta;
			$reference_number = $wpdb->get_var( $wpdb->prepare(
				"SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'reference_number'",
				$post_id
			) );
		}
		
		// If reference number not found, try to extract from link
		if ( empty( $reference_number ) && ! empty( $link ) ) {
			// Link format: <a href="...">reference_number</a>
			preg_match( '/>([^<]+)</', $link, $matches );
			if ( ! empty( $matches[1] ) ) {
				$reference_number = $matches[1];
			}
		}
		
		// Send email notification
		$select_emails = $this->email_helper->get_selected_emails( $this->user_emails, array(
			'admin_email',
			'billing_email',
			'team_leader_email',
			'accounting_email',
		) );


		if (is_array($select_emails) && count($select_emails) > 0) {
			array_push($select_emails, 'billing@odysseia.one', $user_name[ 'user_email' ]);
		}
		
		// If accounting fields are filled, send special warning email
		if ( $accounting_fields_filled ) {
			error_log( 'Sending WARNING email - accounting fields were filled before rate change' );
			
			$warning_subject = 'Someone made changes after the Payment';
			$warning_subtitle = '<span style="color: red; font-weight: bold;">Attention, ' . esc_html( $user_name[ 'full_name' ] ) . ' has made changes on the load ' . $link . '. Driver rate was changed from $' . esc_html( $old_rate ) . ' to $' . esc_html( $new_rate ) . '.</span>';
			$warning_message = '<span style="color: red;">Please immediately contact the person who made these changes and head of Expedite department to clarify the situation.</span>';
			
			$this->email_helper->send_custom_email( $select_emails, array(
				'subject'      => $warning_subject,
				'project_name' => $this->project,
				'subtitle'     => $warning_subtitle,
				'message'      => $warning_message,
			) );
		} else {
			error_log( 'Sending NORMAL email - accounting fields were not filled' );
			error_log( 'Email subject: Changed ' . $driver_label );
			error_log( 'Email message: <del>$' . $old_rate . '</del>, now: $' . $new_rate );
			
			$this->email_helper->send_custom_email( $select_emails, array(
				'subject'      => 'Changed ' . $driver_label,
				'project_name' => $this->project,
				'subtitle'     => $user_name[ 'full_name' ] . ' has changed the ' . $driver_label . ' for the load ' . $link,
				'message'      => '<del>$' . $old_rate . '</del>, now: $' . $new_rate,
			) );
		}
		
		error_log( 'Email sent successfully' );
		error_log( '=== handle_driver_rate_change COMPLETED ===' );
		
		return true;
	}
	
	/**
	 * Check booked rate change and send email notification if changed
	 * Sends warning email if billing fields (processing or factoring_status) are filled
	 * 
	 * @param string|float $old_rate Old booked rate value
	 * @param string|float $new_rate New booked rate value
	 * @param array $user_name User name array with 'full_name' key
	 * @param string $link Link to the load
	 * @param int $post_id Post ID of the load (for checking billing fields)
	 * @return bool True if rate was changed, false otherwise
	 */
	public function handle_booked_rate_change( $old_rate, $new_rate, $user_name, $link, $post_id = null ) {
		global $wpdb;
		
		error_log( '=== handle_booked_rate_change CALLED ===' );
		error_log( 'Old rate: ' . $old_rate . ' (is_numeric: ' . ( is_numeric( $old_rate ) ? 'yes' : 'no' ) . ')' );
		error_log( 'New rate: ' . $new_rate );
		error_log( 'User name: ' . ( isset( $user_name[ 'full_name' ] ) ? $user_name[ 'full_name' ] : 'N/A' ) );
		error_log( 'Link: ' . $link );
		error_log( 'Project: ' . $this->project );
		error_log( 'Post ID: ' . ( $post_id ?: 'not provided' ) );
		
		// Check if rate changed
		if ( ! is_numeric( $old_rate ) ) {
			error_log( 'EXIT: old_rate is not numeric' );
			return false;
		}
		
		if ( $new_rate === floatval( $old_rate ) ) {
			error_log( 'EXIT: rates are equal (old: ' . floatval( $old_rate ) . ', new: ' . $new_rate . ')' );
			return false;
		}
		
		// Check if billing fields are filled (processing or factoring_status)
		$billing_fields_filled = false;
		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$table_meta = $wpdb->prefix . $this->table_meta;
			
			// Get billing fields from database
			$processing = $wpdb->get_var( $wpdb->prepare(
				"SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'processing'",
				$post_id
			) );
			
			$factoring_status = $wpdb->get_var( $wpdb->prepare(
				"SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'factoring_status'",
				$post_id
			) );
			
			// Check if at least one field is filled
			$billing_fields_filled = ! empty( $processing ) || ! empty( $factoring_status );
			
			error_log( 'Billing check - Processing: ' . ( $processing ?: 'empty' ) . ', Factoring status: ' . ( $factoring_status ?: 'empty' ) );
			error_log( 'Billing fields filled: ' . ( $billing_fields_filled ? 'yes' : 'no' ) );
		}
		
		// Get reference number from link or extract from post_id
		$reference_number = '';
		if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
			$table_meta = $wpdb->prefix . $this->table_meta;
			$reference_number = $wpdb->get_var( $wpdb->prepare(
				"SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'reference_number'",
				$post_id
			) );
		}
		
		// If reference number not found, try to extract from link
		if ( empty( $reference_number ) && ! empty( $link ) ) {
			// Link format: <a href="...">reference_number</a>
			preg_match( '/>([^<]+)</', $link, $matches );
			if ( ! empty( $matches[1] ) ) {
				$reference_number = $matches[1];
			}
		}
		
		// Get billing emails
		$select_emails_billing = $this->email_helper->get_selected_emails( $this->user_emails, array(
			'admin_email',
			'billing_email',
		) );
		
		// Add required billing emails
		$required_billing_emails = array(
			'billing@odysseia.one',
			'operations@odysseia.one',
			'daniel@odysseia.one',
			$user_name[ 'user_email' ],
		);
		
		if ( is_array( $select_emails_billing ) ) {
			foreach ( $required_billing_emails as $email ) {
				if ( ! in_array( $email, $select_emails_billing ) ) {
					$select_emails_billing[] = $email;
				}
			}
		} else {
			$select_emails_billing = $required_billing_emails;
		}
		
		// If billing fields are filled, send special warning email
		if ( $billing_fields_filled ) {
			error_log( 'Sending WARNING email - billing fields were filled before booked rate change' );
			
			$warning_subject = 'Someone made changes after the Payment';
			$warning_subtitle = '<span style="color: red; font-weight: bold;">Attention, ' . esc_html( $user_name[ 'full_name' ] ) . ' has made changes on the load ' . $link . '. Booked rate was changed from $' . esc_html( $old_rate ) . ' to $' . esc_html( $new_rate ) . '.</span>';
			$warning_message = '<span style="color: red;">Please immediately contact the person who made these changes and head of Expedite department to clarify the situation.</span>';
			
			$this->email_helper->send_custom_email( $select_emails_billing, array(
				'subject'      => $warning_subject,
				'project_name' => $this->project,
				'subtitle'     => $warning_subtitle,
				'message'      => $warning_message,
			) );
		} else {
			error_log( 'Sending NORMAL email - billing fields were not filled' );
			error_log( 'Email subject: Changed Booked rate' );
			error_log( 'Email message: <del>$' . $old_rate . '</del>, now: $' . $new_rate );
			
			$this->email_helper->send_custom_email( $select_emails_billing, array(
				'subject'      => 'Changed Booked rate',
				'project_name' => $this->project,
				'subtitle'     => $user_name[ 'full_name' ] . ' has changed the Booked rate for the load ' . $link,
				'message'      => '<del>$' . $old_rate . '</del>, now: $' . $new_rate,
			) );
		}
		
		error_log( 'Email sent successfully' );
		error_log( '=== handle_booked_rate_change COMPLETED ===' );
		
		return true;
	}

}

