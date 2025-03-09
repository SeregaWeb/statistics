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
		'sprinter'     => 'Sprinter',
		'cargo_van'    => 'Cargo van',
		'box_truck'    => 'Box truck',
		'curtain_side' => 'Curtain side',
		'hot_shot'     => 'Hot shot',
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
	public $homeDriver       = array(); // states
	public $recruiters       = array(); // recruiter ID
	
}