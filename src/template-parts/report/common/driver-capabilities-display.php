<?php
/**
 * Driver Capabilities Display
 * Used in driver tables to display capabilities with tooltips
 */

// Mapping of capability icons to human-readable names
$capability_names = array(
	'twic.svg'               => 'TWIC',
	'team.svg'               => 'Team Driver',
	'cdl.svg'                => 'CDL License',
	'tsa.svg'                => 'TSA',
	'hazmat.svg'             => 'Hazmat Certificate',
	'hazmat2.svg'            => 'Hazmat Endorsement',
	'change-9.svg'           => 'Change 9',
	'tanker-endorsement.svg' => 'Tanker Endorsement',
	'background-check.svg'   => 'Background Check',
	'liftgate.svg'           => 'Lift Gate',
	'pallet-jack.svg'        => 'Pallet Jack',
	'dolly.svg'              => 'Dolly',
	'ppe.svg'                => 'PPE Equipment',
	'e-track.svg'            => 'E-Tracks',
	'ramp.svg'               => 'Ramp',
	'printer.svg'            => 'Printer',
	'sleeper.svg'            => 'Sleeper',
	'load-bars.svg'          => 'Load Bars',
	'mc.svg'                 => 'MC Number',
	'dot.svg'                => 'DOT Number',
	'real_id.svg'            => 'Real ID',
	'macropoint.png'         => 'MacroPoint',
	'tucker-tools.png'       => 'Trucker Tools',
	'dock-high.svg'          => 'Dock High',
	'any.svg'                => 'Any Distance',
	'otr.svg'                => 'OTR',
	'local.svg'              => 'Local',
	'regional.svg'           => 'Regional',
	'canada.svg'             => 'Canada',
	'mexico.svg'             => 'Mexico',
);

// Get capabilities icons
$array_additionals = $icons->get_capabilities( $driver_capabilities );

if ( ! empty( $array_additionals ) ) {
	foreach ( $array_additionals as $icon_url ) {
		// Extract filename from URL
		$filename = basename( $icon_url );
		
		// Get human-readable name
		$tooltip_text = isset( $capability_names[ $filename ] ) ? $capability_names[ $filename ] : $filename;
		
		?>
		<img width="24" height="24" 
			 src="<?php echo $icon_url; ?>" 
			 alt="<?php echo esc_attr( $tooltip_text ); ?>"
			 title="<?php echo esc_attr( $tooltip_text ); ?>"
			 data-bs-toggle="tooltip" 
			 data-bs-placement="top">
		<?php
	}
}
?>
