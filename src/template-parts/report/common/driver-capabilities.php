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
