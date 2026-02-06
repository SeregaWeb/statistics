<?php
/**
 * Read-only display of Owner & Drivers Information (contact tab content).
 * Used in driver stats popup. No interview_file, no form, no editing.
 * Expects $args: report_object, post_id (from get_template_part 3rd param or get_query_var).
 */

if ( ! isset( $args ) || ! is_array( $args ) ) {
	$args = get_query_var( 'args', array() );
}
$object_driver = get_field_value( $args, 'report_object' );
$post_id       = get_field_value( $args, 'post_id' );

if ( ! $object_driver || ! $post_id ) {
	echo '<p class="text-muted">No driver data.</p>';
	return;
}

$driver     = new TMSDrivers();
$helper     = new TMSReportsHelper();
$states     = $helper->get_states();
$recruiters = $helper->get_recruiters();

$languages          = $driver->languages;
$relation_options   = $driver->relation_options;
$owner_type_options = $driver->owner_type_options;
$labels_distance    = $driver->labels_distance;
$labels_border      = $driver->labels_border;
$sources            = $driver->source;

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$driver_name   = get_field_value( $meta, 'driver_name' );
$driver_phone  = get_field_value( $meta, 'driver_phone' );
$driver_email  = get_field_value( $meta, 'driver_email' );
$home_location = get_field_value( $meta, 'home_location' );
$second_main_driver_phone  = get_field_value( $meta, 'second_main_driver_phone' );
$second_main_driver_email  = get_field_value( $meta, 'second_main_driver_email' );
$city          = get_field_value( $meta, 'city' );
$dob           = get_field_value( $meta, 'dob' );

$language_str    = get_field_value( $meta, 'languages' );
$languages_array = $language_str ? array_map( 'trim', explode( ',', $language_str ) ) : array();

$team_driver_language_str    = get_field_value( $meta, 'team_driver_languages' );
$team_driver_languages_array = $team_driver_language_str ? array_map( 'trim', explode( ',', $team_driver_language_str ) ) : array();

$macro_point               = get_field_value( $meta, 'macro_point' );
$trucker_tools             = get_field_value( $meta, 'trucker_tools' );
$team_driver_enabled       = get_field_value( $meta, 'team_driver_enabled' );
$team_driver_name          = get_field_value( $meta, 'team_driver_name' );
$team_driver_phone         = get_field_value( $meta, 'team_driver_phone' );
$team_driver_email         = get_field_value( $meta, 'team_driver_email' );
$team_driver_dob           = get_field_value( $meta, 'team_driver_dob' );
$team_driver_macro_point   = get_field_value( $meta, 'team_driver_macro_point' );
$team_driver_trucker_tools = get_field_value( $meta, 'team_driver_trucker_tools' );
$owner_enabled             = get_field_value( $meta, 'owner_enabled' );
$owner_name                = get_field_value( $meta, 'owner_name' );
$owner_phone               = get_field_value( $meta, 'owner_phone' );
$owner_email               = get_field_value( $meta, 'owner_email' );
$owner_dob                 = get_field_value( $meta, 'owner_dob' );
$owner_type                = get_field_value( $meta, 'owner_type' );
$owner_macro_point         = get_field_value( $meta, 'owner_macro_point' );
$owner_trucker_tools       = get_field_value( $meta, 'owner_trucker_tools' );
$owner_van_proprietor      = get_field_value( $meta, 'owner_van_proprietor' );
$owner_operator            = get_field_value( $meta, 'owner_operator' );
$emergency_contact_name    = get_field_value( $meta, 'emergency_contact_name' );
$emergency_contact_phone   = get_field_value( $meta, 'emergency_contact_phone' );
$emergency_contact_relation = get_field_value( $meta, 'emergency_contact_relation' );
$preferred_distance        = get_field_value( $meta, 'preferred_distance' );
$cross_border              = get_field_value( $meta, 'cross_border' );
$source                    = get_field_value( $meta, 'source' );
$recruiter_add             = get_field_value( $meta, 'recruiter_add' );
$show_phone                = get_field_value( $meta, 'show_phone' );
$referer_name              = get_field_value( $meta, 'referer_name' );
$mc_enabled                = get_field_value( $meta, 'mc_enabled' );
$mc                        = get_field_value( $meta, 'mc' );
$dot_enabled               = get_field_value( $meta, 'dot_enabled' );
$dot                       = get_field_value( $meta, 'dot' );
$mc_dot_human_tested       = get_field_value( $meta, 'mc_dot_human_tested' );

$rd = function( $val ) {
	return ( $val !== '' && $val !== null && $val !== false ) ? esc_html( (string) $val ) : '—';
};

$state_label = ( is_array( $states ) && isset( $states[ $home_location ] ) )
	? ( is_array( $states[ $home_location ] ) ? $states[ $home_location ][0] : $states[ $home_location ] )
	: $home_location;
$source_label = ( is_array( $sources ) && isset( $sources[ $source ] ) )
	? ( is_array( $sources[ $source ] ) ? $sources[ $source ][0] : $sources[ $source ] )
	: $source;
$relation_label = ( is_array( $relation_options ) && isset( $relation_options[ $emergency_contact_relation ] ) )
	? $relation_options[ $emergency_contact_relation ] : $emergency_contact_relation;
$owner_type_label = ( is_array( $owner_type_options ) && isset( $owner_type_options[ $owner_type ] ) )
	? $owner_type_options[ $owner_type ] : $owner_type;

$recruiter_name = '';
if ( $recruiter_add && is_array( $recruiters ) ) {
	foreach ( $recruiters as $r ) {
		if ( (string) $r['id'] === (string) $recruiter_add ) {
			$recruiter_name = $r['fullname'];
			break;
		}
	}
}
if ( ! $recruiter_name && $recruiter_add ) {
	$recruiter_name = $helper->get_user_full_name_by_id( (int) $recruiter_add );
	$recruiter_name = is_array( $recruiter_name ) && isset( $recruiter_name['full_name'] ) ? $recruiter_name['full_name'] : (string) $recruiter_add;
}

$language_labels = array();
if ( is_array( $languages ) && ! empty( $languages_array ) ) {
	foreach ( $languages_array as $k ) {
		if ( isset( $languages[ $k ] ) ) {
			$language_labels[] = is_array( $languages[ $k ] ) ? $languages[ $k ][0] : $languages[ $k ];
		}
	}
}
$team_language_labels = array();
if ( is_array( $languages ) && ! empty( $team_driver_languages_array ) ) {
	foreach ( $team_driver_languages_array as $k ) {
		if ( isset( $languages[ $k ] ) ) {
			$team_language_labels[] = is_array( $languages[ $k ] ) ? $languages[ $k ][0] : $languages[ $k ];
		}
	}
}

$distance_labels = array();
if ( $preferred_distance && is_array( $labels_distance ) ) {
	$selected_distances = array_map( 'trim', explode( ',', $preferred_distance ) );
	foreach ( $selected_distances as $k ) {
		if ( isset( $labels_distance[ $k ] ) ) {
			$distance_labels[] = esc_html( is_array( $labels_distance[ $k ] ) ? $labels_distance[ $k ][0] : $labels_distance[ $k ] );
		}
	}
}
$border_labels = array();
if ( $cross_border && is_array( $labels_border ) ) {
	$selected_border = array_map( 'trim', explode( ',', $cross_border ) );
	foreach ( $selected_border as $k ) {
		if ( isset( $labels_border[ $k ] ) ) {
			$border_labels[] = esc_html( is_array( $labels_border[ $k ] ) ? $labels_border[ $k ][0] : $labels_border[ $k ] );
		}
	}
}

$show_phone_label = $show_phone === 'team_driver_phone' ? 'Team driver phone' : ( $show_phone === 'owner_phone' ? 'Owner phone' : 'Driver phone' );
?>

<div class="container driver-contact-display-popup pt-0 pb-2">

	<div class="row mb-2">
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Driver Name</label>
			<div class="small"><?php echo $rd( $driver_name ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Driver Phone</label>
			<div class="small"><?php echo $rd( $driver_phone ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Driver Email</label>
			<div class="small"><?php echo $rd( $driver_email ); ?></div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Main contact</label>
			<div class="small"><?php echo $rd( $show_phone_label ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Second Driver Phone</label>
			<div class="small"><?php echo $rd( $second_main_driver_phone ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Second Driver Email</label>
			<div class="small"><?php echo $rd( $second_main_driver_email ); ?></div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">State</label>
			<div class="small"><?php echo $rd( $state_label ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">City</label>
			<div class="small"><?php echo $rd( $city ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Date of Birth</label>
			<div class="small"><?php echo $rd( $dob ); ?></div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-md-6 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="macroPoint" disabled <?php echo $macro_point ? 'checked' : ''; ?>>
				<label class="form-check-label" for="macroPoint">MacroPoint</label>
			</div>
		</div>
		<div class="col-md-6 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="truckerTools" disabled <?php echo $trucker_tools ? 'checked' : ''; ?>>
				<label class="form-check-label" for="truckerTools">Trucker Tools</label>
			</div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-md-12 mb-2">
			<label class="form-label mb-0">Language</label>
			<div class="small"><?php echo ! empty( $language_labels ) ? esc_html( implode( ', ', $language_labels ) ) : '—'; ?></div>
		</div>
	</div>

	<h4 class="h6 mt-3 mb-2 text-muted">Team Driver (Optional)</h4>
	<div class="row mb-2">
		<div class="col-md-6 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="teamDriverSwitch" disabled <?php echo $team_driver_enabled ? 'checked' : ''; ?>>
				<label class="form-check-label" for="teamDriverSwitch">Enable Team Driver</label>
			</div>
		</div>
	</div>
	<?php if ( $team_driver_enabled ) : ?>
		<div class="row border border-primary bg-light pt-2 pb-2 mb-2 rounded">
			<div class="col-md-12 p-0 mb-2">
				<label class="form-label mb-0">Team Driver Language</label>
				<div class="small"><?php echo ! empty( $team_language_labels ) ? esc_html( implode( ', ', $team_language_labels ) ) : '—'; ?></div>
			</div>
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Team Driver Name</label>
				<div class="small"><?php echo $rd( $team_driver_name ); ?></div>
			</div>
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Team Driver Phone</label>
				<div class="small"><?php echo $rd( $team_driver_phone ); ?></div>
			</div>
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Team Driver Email</label>
				<div class="small"><?php echo ( $team_driver_email && $team_driver_email !== '-' ) ? $rd( $team_driver_email ) : '—'; ?></div>
			</div>
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Date of Birth</label>
				<div class="small"><?php echo $rd( $team_driver_dob ); ?></div>
			</div>
			<div class="col-md-6 mb-2">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="teamDriverMacroPoint" disabled <?php echo $team_driver_macro_point ? 'checked' : ''; ?>>
					<label class="form-check-label" for="teamDriverMacroPoint">MacroPoint</label>
				</div>
			</div>
			<div class="col-md-6 mb-2">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="teamDriverTruckerTools" disabled <?php echo $team_driver_trucker_tools ? 'checked' : ''; ?>>
					<label class="form-check-label" for="teamDriverTruckerTools">Trucker Tools</label>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<h4 class="h6 mt-3 mb-2 text-muted">Owner (Optional)</h4>
	<div class="row mb-2">
		<div class="col-12 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="ownerSwitch" disabled <?php echo $owner_enabled ? 'checked' : ''; ?>>
				<label class="form-check-label" for="ownerSwitch">Enable Owner</label>
			</div>
		</div>
	</div>
	<?php if ( $owner_enabled ) : ?>
		<div class="row border border-primary bg-light pt-2 pb-2 mb-2 rounded">
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Owner Name</label>
				<div class="small"><?php echo $rd( $owner_name ); ?></div>
			</div>
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Owner Phone</label>
				<div class="small"><?php echo $rd( $owner_phone ); ?></div>
			</div>
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">Owner Email</label>
				<div class="small"><?php echo ( $owner_email && $owner_email !== '-' ) ? $rd( $owner_email ) : '—'; ?></div>
			</div>
			<div class="col-md-6 mb-2">
				<label class="form-label mb-0">Date of Birth</label>
				<div class="small"><?php echo $rd( $owner_dob ); ?></div>
			</div>
			<div class="col-md-6 mb-2">
				<label class="form-label mb-0">Type</label>
				<div class="small"><?php echo $rd( $owner_type_label ); ?></div>
			</div>
			<div class="col-md-6 mb-2">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="ownerMacroPoint" disabled <?php echo $owner_macro_point ? 'checked' : ''; ?>>
					<label class="form-check-label" for="ownerMacroPoint">MacroPoint</label>
				</div>
			</div>
			<div class="col-md-6 mb-2">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="ownerTruckerTools" disabled <?php echo $owner_trucker_tools ? 'checked' : ''; ?>>
					<label class="form-check-label" for="ownerTruckerTools">Trucker Tools</label>
				</div>
			</div>
			<div class="col-md-6 mb-2">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="ownerVanProprietor" disabled <?php echo $owner_van_proprietor ? 'checked' : ''; ?>>
					<label class="form-check-label" for="ownerVanProprietor">Van Proprietor</label>
				</div>
			</div>
			<div class="col-md-6 mb-2">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="ownerOperator" disabled <?php echo $owner_operator ? 'checked' : ''; ?>>
					<label class="form-check-label" for="ownerOperator">Owner Operator</label>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<h4 class="h6 mt-3 mb-2 text-muted">Preferred distance</h4>
	<div class="row mb-2">
		<div class="col-12 mb-2">
			<div class="small"><?php echo ! empty( $distance_labels ) ? implode( ', ', $distance_labels ) : '—'; ?></div>
		</div>
	</div>

	<h4 class="h6 mt-3 mb-2 text-muted">Cross border</h4>
	<div class="row mb-2">
		<div class="col-12 mb-2">
			<div class="small"><?php echo ! empty( $border_labels ) ? implode( ', ', $border_labels ) : '—'; ?></div>
		</div>
	</div>

	<h4 class="h6 mt-3 mb-2 text-muted">Emergency Contact</h4>
	<div class="row border border-primary bg-light pt-2 pb-2 mb-2 rounded">
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Emergency Contact Name</label>
			<div class="small"><?php echo $rd( $emergency_contact_name ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Emergency Phone</label>
			<div class="small"><?php echo $rd( $emergency_contact_phone ); ?></div>
		</div>
		<div class="col-md-4 mb-2">
			<label class="form-label mb-0">Relation</label>
			<div class="small"><?php echo $rd( $relation_label ); ?></div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-12 col-md-4 mb-2">
			<label class="form-label mb-0">Source</label>
			<div class="small"><?php echo $rd( $source_label ); ?></div>
		</div>
		<div class="col-12 col-md-8 mb-2">
			<label class="form-label mb-0">Referred by</label>
			<div class="small"><?php echo $rd( $referer_name ); ?></div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-12 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="mcSwitch" disabled <?php echo $mc_enabled ? 'checked' : ''; ?>>
				<label class="form-check-label" for="mcSwitch">MC enabled</label>
			</div>
		</div>
	</div>
	<?php if ( $mc_enabled ) : ?>
		<div class="row mb-2">
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">MC Number</label>
				<div class="small"><?php echo $rd( $mc ); ?></div>
			</div>
		</div>
	<?php endif; ?>

	<div class="row mb-2">
		<div class="col-12 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="dotSwitch" disabled <?php echo $dot_enabled ? 'checked' : ''; ?>>
				<label class="form-check-label" for="dotSwitch">DOT enabled</label>
			</div>
		</div>
	</div>
	<?php if ( $dot_enabled ) : ?>
		<div class="row mb-2">
			<div class="col-md-4 mb-2">
				<label class="form-label mb-0">DOT Number</label>
				<div class="small"><?php echo $rd( $dot ); ?></div>
			</div>
		</div>
	<?php endif; ?>

	<div class="row mb-2">
		<div class="col-12 mb-2">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" id="mcDotHumanTestedSwitch" disabled <?php echo $mc_dot_human_tested ? 'checked' : ''; ?>>
				<label class="form-check-label" for="mcDotHumanTestedSwitch">MC/DOT human tested ?</label>
			</div>
		</div>
	</div>

	<div class="row mb-0">
		<div class="col-12 col-md-6 col-xl-4">
			<label class="form-label mb-0">Recruiter Initials</label>
			<div class="small"><?php echo $rd( $recruiter_name ); ?></div>
		</div>
	</div>
</div>


