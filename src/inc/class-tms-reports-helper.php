<?php

class TMSReportsHelper extends TMSReportsIcons {
	
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
		"DIF"  => "Distrito Federal, DIF",
		"CDMX" => "Distrito Federal, CDMX",
		"AGU"  => "Aguascalientes, AGU",
		"BCN"  => "Baja California, BCN",
		"BCS"  => "Baja California Sur, BCS",
		"CAM"  => "Campeche, CAM",
		"CHP"  => "Chiapas, CHP",
		"CHIS" => "Chiapas, CHIS",
		"CHH"  => "Chihuahua, CHH",
		"COA"  => "Coahuila, COA",
		"COL"  => "Colima, COL",
		"DUR"  => "Durango, DUR",
		"GUA"  => "Guanajuato, GUA",
		"GRO"  => "Guerrero, GRO",
		"HID"  => "Hidalgo, HID",
		"JAL"  => "Jalisco, JAL",
		"MIC"  => "Michoacán, MIC",
		"MOR"  => "Morelos, MOR",
		"MEX"  => "México, MEX",
		"NAY"  => "Nayarit, NAY",
		"NLE"  => "Nuevo León, NLE",
		"OAX"  => "Oaxaca, OAX",
		"PUE"  => "Puebla, PUE",
		"QUE"  => "Querétaro, QUE",
		"QRO"  => "Querétaro, QRO",
		"NAQ"  => "Querétaro, NAQ",
		"ROO"  => "Quintana Roo, ROO",
		"SLP"  => "San Luis Potosí, SLP",
		"SIN"  => "Sinaloa, SIN",
		"SON"  => "Sonora, SON",
		"TAB"  => "Tabasco, TAB",
		"TAM"  => "Tamaulipas, TAM",
		"TLA"  => "Tlaxcala, TLA",
		"VER"  => "Veracruz, VER",
		"YUC"  => "Yucatán, YUC",
		"ZAC"  => "Zacatecas, ZAC",
	);
    
    public $invoices = array(
      'invoiced' => 'Invoiced',
      'not-invoiced' => 'Not invoiced',
      'invoiced-directly' => 'Invoiced directly',
    );
    
    public $factoring_status = array (
       'unsubmitted' => 'Unsubmitted',
       'in-processing' => 'In Processing',
       'requires-attention' => 'Requires Attention',
       'in-dispute' => 'In Dispute',
       'processed' => 'Processed',
       'charge-back' => 'Charge Back',
       'short-pay' => 'Short Pay',
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
		'partial'             => 'Partial'
	);
	
	public $types = array (
		'ltl' => 'LTL',
        'container' => 'Container',
		'drop_and_hook' => 'Drop and Hook',
		'last_mile' => 'Last Mile',
		'other' => 'Other',
		'truck_load' => 'Truck Load',
	);
	
    public $tms_tables = array(
           'Odysseia',
           'Martlet',
           'Endurance'
    );
    
	public $set_up = array (
		'completed' => 'Completed',
		'not_completed' => 'Not completed',
		'error' => 'Error',
	);
 
	public $set_up_platform = array (
		'rmis' => 'RMIS',
		'dat' => 'DAT',
		'highway' => 'Highway',
		'manual' => 'Manual',
		'mcp' => 'MCP',
		'other' => 'Other',
	);
	
    function get_dispatchers () {
	    // Аргументы для получения пользователей с ролью 'dispatcher'
	    $args = array(
		    'role'    => 'dispatcher',
		    'orderby' => 'display_name',
		    'order'   => 'ASC',
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
			    'fullname' => trim( $first_name . ' ' . $last_name )
		    );
	    }
	    
	    return $dispatchers;
    }
	function get_set_up_platform() {
		return $this->set_up_platform;
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
	
	function get_label_by_key( $key = null, $search_list = null ) {
		
		if (is_null($key) || is_null($search_list)) return false;
		
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
		
		if ( $search_list === 'instructions' && !empty($key) ) {
			return $this->get_icons_from_keys($key);
		}
		
		return false;
	}
	
	function get_icons_from_keys($keys_string) {
		// Разбиваем строку на массив по запятым и удаляем пробелы
		$keys = array_map('trim', explode(',', $keys_string));
		
		// Создаем массив для хранения иконок
		$icons = [];
		
		// Перебираем каждый ключ и выполняем switch
		foreach ($keys as $key) {
			$tooltip = isset( $this->features[ $key ] ) ? $this->features[ $key ] : $key;
			switch ($key) {
				case 'hazmat':
					$icons[] = $this->get_icon_hazmat($tooltip);
					break;
				case 'tanker-end':
					$icons[] = $this->get_icon_tanker_end($tooltip);
					break;
				case 'driver-assist':
					$icons[] = $this->get_icon_assist($tooltip);
					break;
				case 'liftgate':
					$icons[] = $this->get_icon_liftgate($tooltip);
					break;
				case 'pallet-jack':
					$icons[] = $this->get_icon_palet_jack($tooltip);
					break;
				case 'dock-high':
					$icons[] = $this->get_icon_dock_high($tooltip);
					break;
				case 'true-team':
					$icons[] = $this->get_icon_true_team($tooltip);
					break;
				case 'fake-team':
					$icons[] = $this->get_icon_fake_team($tooltip);
					break;
				case 'tsa':
					$icons[] = $this->get_icon_tsa($tooltip);
					break;
				case 'twic':
					$icons[] = $this->get_icon_twic($tooltip);
					break;
				case 'airport':
					$icons[] = $this->get_icon_airport($tooltip);
					break;
				case 'round-trip':
					$icons[] = $this->get_icon_round_trip($tooltip);
					break;
				case 'alcohol':
					$icons[] = $this->get_icon_alcohol($tooltip);
					break;
				case 'temperature-control':
					$icons[] = $this->get_icon_temperature_control($tooltip);
					break;
				case 'ace':
					$icons[] = $this->get_icon_ace($tooltip);
					break;
				case 'aci':
					$icons[] = $this->get_icon_aci($tooltip);
					break;
				case 'mexico':
					$icons[] = $this->get_icon_mexico($tooltip);
					break;
				case 'military-base':
					$icons[] = $this->get_icon_military($tooltip);
					break;
				case 'blind-shipment':
					$icons[] = $this->get_icon_blind_shipment($tooltip);
					break;
				case 'partial':
					$icons[] = $this->get_icon_partial($tooltip);
					break;
				default:
					break;
			}
		}
		
		// Объединяем иконки в строку и возвращаем
		return implode('', $icons);
	}
	
	function message_top ($type, $message, $button_class = '', $button_text = '') {
		ob_start();
		
		if (!$message) return '';
		
		if ($type === 'success') {
			$typeMessage = 'Success';
			$typeMessageClass = 'alert-success';
			$typeMessageSvg = '#check-circle-fill';
			$button_class .= ' btn-outline-success';
		} else {
			$typeMessage = 'Danger';
			$typeMessageClass = 'alert-danger';
			$typeMessageSvg = '#exclamation-triangle-fill';
			$button_class .= ' btn-outline-danger';
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
			<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="<?php echo $typeMessage; ?>:"><use xlink:href="<?php echo $typeMessageSvg; ?>"/></svg>
			<div class="d-flex justify-content-between align-items-center w-100">
                <span>
				    <?php echo $message; ?>
                </span>
                <?php if (!empty($button_class) && !empty($button_text)): ?>
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
		$user = get_user_by( 'id', $user_id );
		
		if ( $user ) {
			$first_name = $user->first_name;
			$last_name  = $user->last_name;
   
			$full_name = $first_name . ' ' . $last_name;
			$initials = mb_strtoupper( mb_substr( $first_name, 0, 1 ) . mb_substr( $last_name, 0, 1 ) );
			return array(
				'full_name' => $full_name,
				'initials'  => $initials,
			);
		}
		
		return false;
	}
    
    function messages_prepare($type) {
        
        $message = '';
        
	    switch ($type) {
            case 'not-access':
	            $message = 'You do not have access to edit or view these materials';
                break;
            case 'user-not-found':
                $message = 'User with this ID not found';
                break;
	    }
        
        return $message;
    }
}

