<?php
/**
 * Driver Capabilities Logic
 * Used in driver tables to generate capabilities array
 */

// Get driver capabilities data
$preferred_distance = get_field_value( $meta, 'preferred_distance' );
$selected_distances = array_map( 'trim', explode( ',', $preferred_distance ) );

$cross_border = get_field_value( $meta, 'cross_border' );
$selected_cross_border = array_map( 'trim', explode( ',', $cross_border ) );

$driver_licence_type_cdl = get_field_value( $meta, 'driver_licence_type' );
$driver_licence_type_cdl = $driver_licence_type_cdl === 'cdl';

// Prepare data for Military capability (military.svg)
$legal_document_type        = get_field_value( $meta, 'legal_document_type' );
$legal_document_expiration  = get_field_value( $meta, 'legal_document_expiration' );
$legal_document_file        = get_field_value( $meta, 'legal_document' );

$background_check           = get_field_value( $meta, 'background_check' );
$background_file            = get_field_value( $meta, 'background_file' );

// Helper: check if date is not expired (>= today in America/New_York)
$ny_timezone = new DateTimeZone( 'America/New_York' );
$now_ny      = new DateTime( 'now', $ny_timezone );
$now_ts      = $now_ny->getTimestamp();

$legal_valid = false;
if ( $legal_document_type === 'us-passport' && ! empty( $legal_document_file ) && ! empty( $legal_document_expiration ) ) {
	$legal_exp_ts = strtotime( $legal_document_expiration );
	if ( $legal_exp_ts !== false && $legal_exp_ts >= $now_ts ) {
		$legal_valid = true;
	}
}

$background_valid = false;
if ( $background_check && ! empty( $background_file ) ) {
	$background_valid = true;
}

$military_capability = $legal_valid && $background_valid;

$driver_capabilities = array(
	'twic.svg'               => get_field_value( $meta, 'twic' ),
	'team.svg'               => get_field_value( $meta, 'team_driver_enabled' ),
	'cdl.svg'                => $driver_licence_type_cdl,
	'tsa.svg'                => get_field_value( $meta, 'tsa_approved' ),
	'hazmat.svg'             => get_field_value( $meta, 'hazmat_certificate' ),
	'hazmat2.svg'            => get_field_value( $meta, 'hazmat_endorsement' ),
	'change-9.svg'           => get_field_value( $meta, 'change_9_training' ),
	'tanker-endorsement.svg' => get_field_value( $meta, 'tanker_endorsement' ),
	'background-check.svg'   => get_field_value( $meta, 'background_check' ),
	'liftgate.svg'           => get_field_value( $meta, 'lift_gate' ),
	'pallet-jack.svg'        => get_field_value( $meta, 'pallet_jack' ),
	'dolly.svg'              => get_field_value( $meta, 'dolly' ),
	'ppe.svg'                => get_field_value( $meta, 'ppe' ),
	'e-track.svg'            => get_field_value( $meta, 'e_tracks' ),
	'ramp.svg'               => get_field_value( $meta, 'ramp' ),
	'printer.svg'            => get_field_value( $meta, 'printer' ),
	'sleeper.svg'            => get_field_value( $meta, 'sleeper' ),
	'load-bars.svg'          => get_field_value( $meta, 'load_bars' ),
	'mc.svg'                 => get_field_value( $meta, 'mc_enabled' ),
	'dot.svg'                => get_field_value( $meta, 'dot_enabled' ),
	'real_id.svg'            => get_field_value( $meta, 'real_id' ),
	'military.svg'           => $military_capability,
	'macropoint.png'         => get_field_value( $meta, 'macro_point' ),
	'tucker-tools.png'       => get_field_value( $meta, 'trucker_tools' ),
	'dock-high.svg'          => get_field_value( $meta, 'dock_high' ),
	'any.svg'                => is_numeric( array_search( 'any', $selected_distances ) ),
	'otr.svg'                => is_numeric( array_search( 'otr', $selected_distances ) ),
	'local.svg'              => is_numeric( array_search( 'local', $selected_distances ) ),
	'regional.svg'           => is_numeric( array_search( 'regional', $selected_distances ) ),
	'canada.svg'             => is_numeric( array_search( 'canada', $selected_cross_border ) ) || get_field_value( $meta, 'canada_transition_proof' ),
	'mexico.svg'             => is_numeric( array_search( 'mexico', $selected_cross_border ) ),
);
?>
