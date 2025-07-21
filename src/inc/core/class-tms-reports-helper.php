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
		'high-value-freight'  => 'High value freight'
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
			'role__in' => array( 'dispatcher', 'dispatcher-tl' ),
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
			'role'       => 'dispatcher-tl',
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
				$datetime = DateTime::createFromFormat( 'Y-m-d\TH:i', $location[ 'date' ] );
				
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
	
	function get_tracking_message( $tracking_email, $nightshift_email ) {
		// Сначала ищем nightshift
		$nightshift_user = $this->find_user_by_emails( $nightshift_email );
		
		// Теперь ищем tracking, исключая nightshift_user
		$tracking_user = $this->find_user_by_emails( $tracking_email, $nightshift_user ? $nightshift_user->ID : null );
		
		// Проверяем, что пользователи найдены
		if ( ! $tracking_user ) {
			return 'Tracking user not found.';
		}
		if ( ! $nightshift_user ) {
			return 'Nightshift user not found.';
		}
		
		$tracking_text = $tracking_user
			? sprintf( "%s %s", get_field( 'phone_number', 'user_' . $tracking_user->ID ), $tracking_user->first_name )
			: '';
		
		$nightshift_text = $nightshift_user
			? sprintf( "%s %s", get_field( 'phone_number', 'user_' . $nightshift_user->ID ), $nightshift_user->first_name )
			: '';
		
		$text_parts = [];
		
		if ( $tracking_text ) {
			$text_parts[] = 'Our tracking team will be reaching out to you: ' . $tracking_text;
		}
		
		if ( $nightshift_text ) {
			$text_parts[] = 'Afterhours contact: ' . $nightshift_text;
		}
		
		$text = implode( '. ', $text_parts ) . '.';
		
		
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
		$nightshift             = get_field( 'nightshift_tracking', 'user_' . $dispatcher_initials );
		$tracking               = $emails->get_tracking_email( $dispatcher_initials );
		$last_message           = $this->get_tracking_message( $tracking, $nightshift );
		
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
			);
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
		
		return $args;
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
	
	function format_currency( $value ) {
		$cleaned   = str_replace( ',', '', $value ?? '' );
		$formatted = number_format( (float) $cleaned, 2, '.', ',' );
		if ( str_ends_with( $formatted, '.00' ) ) {
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
	
	function get_driver_tempate( $meta ) {
		$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
		$driver_phone     = esc_html( get_field_value( $meta, 'driver_phone' ) );
		$macropoint_set   = get_field_value( $meta, 'macropoint_set' );
		$trucker_tools    = get_field_value( $meta, 'trucker_tools' );
		
		$second_unit_number_name = esc_html( get_field_value( $meta, 'second_unit_number_name' ) );
		$second_driver_phone     = esc_html( get_field_value( $meta, 'second_driver_phone' ) );
		$shared_with_client      = get_field_value( $meta, 'shared_with_client' );
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
            <p class="m-0"><?php echo $unit_number_name; ?></p>
			<?php if ( $driver_phone ) { ?>
                <span class="text-small relative <?php echo $shared_with_client ? 'text-primary'
					: ''; ?> <?php echo $macropoint_set ? 'macropoint' : ''; ?> <?php echo $trucker_tools
					? 'trucker_tools' : ''; ?>" title="<?php echo $title_str; ?>">
				<?php echo $driver_phone; ?>
                </span>
			<?php } ?>
			<?php if ( $second_unit_number_name && $second_driver_phone ): ?>
                <p class=" m-0"><?php echo $second_unit_number_name; ?></p>
				<?php if ( $second_driver_phone ) { ?>
                    <span class="text-small relative">
                                <?php echo $second_driver_phone; ?>
                            </span>
				<?php } ?>
			<?php endif; ?>
        </div>
		<?php
		return ob_get_clean();
	}
	
	function get_locations_template( $row, $template = 'default' ) {
		$meta = get_field_value( $row, 'meta_data' );
		
		// Даты с проверкой, чтобы избежать ошибок
		$delivery_date_raw = get_field_value( $row, 'delivery_date' );
		$pick_up_date_raw  = get_field_value( $row, 'pick_up_date' );
		
		$delivery_date = ! empty( $delivery_date_raw )
			? esc_html( DateTime::createFromFormat( 'Y-m-d H:i:s', $delivery_date_raw )->format( 'm/d/Y' ) ) : '';
		$pick_up_date  = ! empty( $pick_up_date_raw )
			? esc_html( DateTime::createFromFormat( 'Y-m-d H:i:s', $pick_up_date_raw )->format( 'm/d/Y' ) ) : '';
		
		// Декодируем JSON, если он не пуст
		$delivery = ! empty( get_field_value( $meta, 'delivery_location' ) )
			? json_decode( str_replace( "\'", "'", stripslashes( get_field_value( $meta, 'delivery_location' ) ) ), ARRAY_A )
			: [];
		$pick_up  = ! empty( get_field_value( $meta, 'pick_up_location' ) )
			? json_decode( str_replace( "\'", "'", stripslashes( get_field_value( $meta, 'pick_up_location' ) ) ), ARRAY_A )
			: [];
		
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
					
					// Получаем значения времени
					$time_start  = get_field_value( $val, 'time_start' );
					$time_end    = get_field_value( $val, 'time_end' );
					$strict_time = get_field_value( $val, 'strict_time' );
					
					// Формируем строку времени
					$time_range = '';
					if ( ! empty( $time_start ) ) {
						$time_range = esc_html( $time_start );
						if ( $strict_time === "false" && ! empty( $time_end ) ) {
							$time_range .= ' - ' . esc_html( $time_end );
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
			$pick_up_location_array = json_decode( $pick_up_location, true );
			if ( is_array( $pick_up_location_array ) ) {
				foreach ( $pick_up_location_array as $pick_up ) {
					if ( ! empty( $pick_up[ 'short_address' ] ) ) {
						$template_p[] = $pick_up[ 'short_address' ];
					}
				}
			}
		}
		
		if ( ! empty( $delivery_location ) ) {
			$delivery_location_array = json_decode( $delivery_location, true );
			if ( is_array( $delivery_location_array ) ) {
				foreach ( $delivery_location_array as $delivery ) {
					if ( ! empty( $delivery[ 'short_address' ] ) ) {
						$template_d[] = $delivery[ 'short_address' ];
					}
				}
			}
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
	
}

