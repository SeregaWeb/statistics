<?php

class TMSDriversHelper {
	
	public $status = array(
		'available'         => 'Available',
		'available_on'      => 'Available on',
		'not_available'     => 'Not available',
		'loaded_enroute'    => 'Loaded & Enroute',
		'out_of_service'    => 'Out of service',
		'on_vacation'       => 'On vacation',
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
		"dry-van"      => "Dry van",
	);
	
	public $source = array(
		'betterteam'     => 'Betterteam',
		'indeed'         => 'Indeed',
		'facebook'       => 'Facebook',
		'recommendation' => 'Recommendation',
		'cbdriver'       => 'CBDriver',
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
	
}