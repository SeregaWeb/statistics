<?php

class TMSDriversHelper {
	
	public $status = array(
		'available'         => 'Available',
		'available_on'      => 'Available on',
		'available_off'     => 'Not available',
		'loaded_enroute'    => 'Loaded & Enroute',
		'banned'            => 'Out of service',
		'on_vocation'       => 'On vacation',
		'no_updates'        => 'No updates',
		'blocked'           => 'Blocked',
		'expired_documents' => 'Expired documents',
		'no_Interview'      => 'No Interview',
	);
	
	public $region_zip_code = array(
		'USA' => 'USA',
		'CA'  => 'Canada',
	);
	
	public $vehicle = array(
		"cargo-van"    => "Cargo van",
		"sprinter-van" => "Sprinter van",
		"box-truck"    => "Box truck",
		"pickup"       => "Pickup",
		"reefer"       => "Reefer",
		"semi-truck"   => "Semi truck",
	);
	
	/**
	 * Get vehicle key by value
	 *
	 * @param string $value Vehicle display name (e.g., "Cargo van")
	 *
	 * @return string|false Vehicle key (e.g., "cargo-van") or false if not found
	 */
	public function get_vehicle_key_by_value( $value ) {
		return array_search( $value, $this->vehicle );
	}
	
	public $source = array(
		'betterteam'     => 'Betterteam',
		'indeed'         => 'Indeed',
		'facebook'       => 'Facebook',
		'recommendation' => 'Recommendation',
		'cbdriver'       => 'CBDriver',
		'instagram'      => 'Instagram',
		'other'          => 'Other',
	);
	
	public $languages = array(
		'en' => 'English',
		'es' => 'Spanish',
		'ua' => 'Ukrainian',
		'ru' => 'Russian',
		'fr' => 'French',
		'pt' => 'Portuguese',
		'ar' => 'Arabic'
	);
	
	public $owner_type_options = array(
		'wife'           => 'Wife',
		'husband'        => 'Husband',
		'other_relative' => 'Other Relative',
	);
	public $relation_options   = array(
		'wife'           => 'Wife',
		'husband'        => 'Husband',
		'mother'         => 'Mother',
		'father'         => 'Father',
		'sibling'        => 'Sibling',
		'other_relative' => 'Other Relative',
	);
	
	public $labels = array(
		'cdl'                => 'CDL',
		'hazmat'             => 'Hazmat',
		'tsa'                => 'TSA',
		'twic'               => 'TWIC',
		'tanker-endorsement' => 'Tanker endorsement',
		'ppe'                => 'PPE',
		'dock-high'          => 'Dock High',
		'e-track'            => 'E-tracks',
		'pallet-jack'        => 'Pallet jack',
		'ramp'               => 'Ramp',
		'load-bars'          => 'Load bars',
		'liftgate'           => 'Liftgate',
		'team'               => 'Team',
		'canada'             => 'Canada',
		'mexico'             => 'Mexico',
		'alaska'             => 'Alaska',
		'real_id'            => 'Real ID',
		'macropoint'         => 'MacroPoint',
		'tucker-tools'       => 'Trucker Tools',
		'change-9'           => 'Change 9',
		'sleeper'            => 'Sleeper',
		'printer'            => 'Printer',
		'side_door'          => 'Side door',
	);
	
	public $labels_border   = array(
		'canada' => 'Canada',
		'mexico' => 'Mexico',
	);
	public $labels_distance = array(
		'otr'      => 'OTR',
		'regional' => 'Regional',
		'local'    => 'Local',
		'any'      => 'Any',
	);
	
	public $work_for_project = array(
		'odysseia'  => 'Odysseia',
		'martlet'   => 'Martlet',
		'endurance' => 'Endurance',
	);
	
	public $registration_types = array(
		'vehicle-registration' => 'Vehicle registration',
		'bill-of-sale'         => 'Bill of sale',
		'certificate-of-title' => 'Certificate of title'
	);
	
	public $registration_status_options = array(
		"Valid"     => "Valid",
		"Temporary" => "Temporary",
		"Expired"   => "Expired"
	);
	
	public $plates_status_options = array(
		"Permanent" => "Permanent",
		"Temporary" => "Temporary",
		"Expired"   => "Expired"
	);
	
	public $driverLicenceTypes = array(
		'regular'  => 'Regular',
		'cdl'      => 'CDL',
		'enhanced' => 'Enhanced',
		'temporary' => 'Temporary',
		'chauffeurs-licence' => 'Chauffeur\'s licence',
	);
	
	
	public $legalDocumentTypes = array(
		"no-document"                     => "No document",
		"us-passport"                     => "US passport",
		"permanent-residency"             => "Permanent residentship",
		"work-authorization"              => "Work authorization",
		"certificate-of-naturalization"   => "Certificate of naturalization",
		"enhanced-driver-licence-real-id" => "Enhanced driver licence Real ID"
	);
	
	public $insuredOptions = array(
		"business"   => "Business",
		"individual" => "Individual"
	);
	
	public $statusOptions = array(
		"additional-insured" => "Additional insured",
		"company-not-listed" => "Company not listed",
		"cancelled"          => "Cancelled",
		"hold"               => "Hold"
	);
	
	public $bank_payees = array(
		'odysseia'            => 'Odysseia',
		'martlet_express'     => 'Martlet Express',
		'endurance_transport' => 'Endurance Transport'
	);
	
	
	public $homeDriver = array(); // states
	public $recruiters = array(); // recruiter ID
	
	function get_user_full_name_by_id( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		
		if ( $user ) {
			$first_name = $user->first_name;
			$last_name  = $user->last_name;
			
			$full_name = $first_name . ' ' . $last_name;
			$initials  = mb_strtoupper( mb_substr( $first_name, 0, 1 ) . mb_substr( $last_name, 0, 1 ) );
			
			return array(
				'full_name' => $full_name,
				'initials'  => $initials,
				'user_id'   => $user_id,
				'user_email' => $user->user_email,
			);
		}
		
		return false;
	}
	
	function process_file_attachment( $file_id ) {
		if ( empty( $file_id ) ) {
			return null;
		}
		
		$attachment_url = wp_get_attachment_url( $file_id );
		
		if ( wp_attachment_is_image( $file_id ) ) {
			return array(
				'id'  => $file_id,
				'url' => $attachment_url,
			);
		} else {
			$file_name = basename( $attachment_url );
			
			return array(
				'id'        => $file_id,
				'url'       => $attachment_url,
				'file_name' => $file_name,
			);
		}
	}
	
	public function get_files( $files ) {
		$array_ids_image = explode( ',', $files );
		
		if ( is_array( $array_ids_image ) ) {
			foreach ( $array_ids_image as $id_image ) {
				
				$attachment_url = wp_get_attachment_url( $id_image );
				if ( wp_attachment_is_image( $id_image ) ) {
					$files_arr[] = array(
						'id'  => $id_image,
						'url' => $attachment_url,
					);
				} else {
					$file_name = basename( $attachment_url );
					
					$files_arr[] = array(
						'id'        => $id_image,
						'url'       => $attachment_url,
						'file_name' => $file_name,
					);
				}
			}
			
			return $files_arr;
		}
		
		return false;
	}
	
	function format_field_name( $field ) {
		return ucwords( str_replace( '_', ' ', $field ) );
	}
	
	function get_log_template( $array_track, $meta, $data ) {
		$changes = '';
		
		if ( is_array( $array_track ) ) {
			foreach ( $array_track as $field ) {
				// Старое значение
				$old_value = isset( $meta[ $field ] ) ? $meta[ $field ] : '';
				
				// Новое значение из массива данных
				$new_value = isset( $data[ $field ] ) ? $data[ $field ] : '';
				
				// Сравниваем старое и новое значение
				if ( $old_value != '' && $old_value != $new_value ) {
					$changes .= '<strong>' . $this->format_field_name( $field ) . '</strong> - Value changed<br>';
					$changes .= '<strong>New meaning</strong>: <span style="color: green">' . $new_value . '</span><br>';
					$changes .= '<strong>Old meaning</strong>: <span style="color: red">' . $old_value . '</span><br><br>';
				}
			}
		}
		
		return $changes;
	}
	
	/**
	 * Check if current user can change driver status
	 *
	 * @param string $status_key Status key to check
	 *
	 * @return bool True if user can change this status, false otherwise
	 */
	public function can_change_driver_status( $status_key ) {
		// Restricted statuses that only Admin, Recruiter, Recruiter Team Leader can change
		$restricted_statuses = array(
			'no_Interview',
			'expired_documents',
			'blocked'
		);
		
		// If status is not restricted, anyone can change it
		if ( ! in_array( $status_key, $restricted_statuses ) ) {
			return true;
		}
		
		// Check if current user has permission to change restricted statuses
		$current_user  = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'recruiter', 'recruiter-tl' );
		
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get allowed statuses for current user
	 *
	 * @return array Array of allowed status keys
	 */
	public function get_allowed_statuses() {
		$current_user  = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'recruiter', 'recruiter-tl', 'driver_updates' );
		
		$has_restricted_access = false;
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				$has_restricted_access = true;
				break;
			}
		}
		
		// If user has restricted access, they can change all statuses
		if ( $has_restricted_access ) {
			return array_keys( $this->status );
		}
		
		// Otherwise, exclude restricted statuses
		$restricted_statuses = array(
			'no_Interview',
			'expired_documents',
			'blocked'
		);
		
		return array_diff( array_keys( $this->status ), $restricted_statuses );
	}
	
	/**
	 * Get statuses that current user can change (not just see)
	 *
	 * @return array Array of status keys that user can change
	 */
	public function get_changeable_statuses() {
		$current_user  = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'recruiter', 'recruiter-tl' );
		
		$has_restricted_access = false;
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				$has_restricted_access = true;
				break;
			}
		}
		
		// If user has restricted access, they can change all statuses
		if ( $has_restricted_access ) {
			return array_keys( $this->status );
		}
		
		// Otherwise, exclude restricted statuses
		$restricted_statuses = array(
			'no_Interview',
			'expired_documents',
			'blocked'
		);
		
		return array_diff( array_keys( $this->status ), $restricted_statuses );
	}
	
	/**
	 * Check if current user can copy driver phones
	 *
	 * @return bool True if user can copy phones, false otherwise
	 */
	public function can_copy_driver_phones() {
		$current_user  = wp_get_current_user();
		$allowed_roles = array(
			'administrator',
			'driver_updates',
			'recruiter',
			'recruiter-tl',
			'tracking-tl',
			'morning_tracking',
			'nightshift_tracking'
		);
		
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check if current user can see driver with specific status
	 *
	 * @param string $status_key Driver status to check
	 *
	 * @return bool True if user can see this driver, false otherwise
	 */
	public function can_see_driver_with_status( $status_key ) {
		// Restricted statuses that only Admin, Recruiter, Recruiter Team Leader can see
		$restricted_statuses = array(
			'no_Interview',
			'expired_documents',
			'blocked'
		);
		
		// If status is not restricted, anyone can see it
		if ( ! in_array( $status_key, $restricted_statuses ) ) {
			return true;
		}
		
		// Check if current user has permission to see restricted status drivers
		$current_user  = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'recruiter', 'recruiter-tl', 'driver_updates' );
		
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get SQL WHERE condition for driver visibility based on user role
	 *
	 * @return array Array with 'condition' and 'values' keys
	 */
	public function get_driver_visibility_condition() {
		$current_user  = wp_get_current_user();
		$allowed_roles = array( 'administrator', 'recruiter', 'recruiter-tl', 'driver_updates' );
		
		$has_restricted_access = false;
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				$has_restricted_access = true;
				break;
			}
		}
		
		// If user has restricted access, they can see all drivers
		if ( $has_restricted_access ) {
			return array(
				'condition' => '',
				'values'    => array()
			);
		}
		
		// Otherwise, exclude drivers with restricted statuses
		$restricted_statuses = array(
			'no_Interview',
			'expired_documents',
			'blocked'
		);
		
		$condition = "(driver_status.meta_value IS NULL OR driver_status.meta_value NOT IN ('" . implode( "', '", $restricted_statuses ) . "'))";
		
		return array(
			'condition' => $condition,
			'values'    => array()
		);
	}
	
	/**
	 * Convert MySQL datetime format to flatpickr format
	 *
	 * @param string $mysql_date Date in MySQL format (Y-m-d H:i:s)
	 *
	 * @return string Date in flatpickr format (m/d/Y H:i) or empty string
	 */
	public static function convert_mysql_to_flatpickr_date( $mysql_date ) {
		if ( empty( $mysql_date ) ) {
			return '';
		}
		
		$date_obj = DateTime::createFromFormat( 'Y-m-d H:i:s', $mysql_date );
		if ( $date_obj ) {
			return $date_obj->format( 'm/d/Y H:i' );
		}
		
		// If parsing failed, return original value
		return $mysql_date;
	}
}