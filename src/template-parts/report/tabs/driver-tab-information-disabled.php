<?php
$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$TMSUsers = new TMSUsers();
$driver   = new TMSDrivers();

$languages          = $driver->languages;
$relation_options   = $driver->relation_options;
$owner_type_options = $driver->owner_type_options;
$labels_distance    = $driver->labels_distance;
$labels_border      = $driver->labels_border;
$sources            = $driver->source;
$legalDocumentTypes = $driver->legalDocumentTypes;

$helper     = new TMSReportsHelper();
$states     = $helper->get_states();
$recruiters = $helper->get_recruiters();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

// Driver information
$driver_name   = get_field_value( $meta, 'driver_name' );
$driver_phone  = get_field_value( $meta, 'driver_phone' );
$driver_email  = get_field_value( $meta, 'driver_email' );
$home_location = get_field_value( $meta, 'home_location' );
$city          = get_field_value( $meta, 'city' );
$dob           = get_field_value( $meta, 'dob' );

// Owner information
$owner_enabled              = get_field_value( $meta, 'owner_enabled' );
$owner_name                 = get_field_value( $meta, 'owner_name' );
$owner_phone                = get_field_value( $meta, 'owner_phone' );
$owner_email                = get_field_value( $meta, 'owner_email' );
$owner_type                 = get_field_value( $meta, 'owner_type' );

// Team driver information
$team_driver_enabled        = get_field_value( $meta, 'team_driver_enabled' );
$team_driver_name           = get_field_value( $meta, 'team_driver_name' );
$team_driver_phone          = get_field_value( $meta, 'team_driver_phone' );
$team_driver_email          = get_field_value( $meta, 'team_driver_email' );

// Vehicle information
$vehicle_type            = get_field_value( $meta, 'vehicle_type' );
$vehicle_make            = get_field_value( $meta, 'vehicle_make' );
$vehicle_model           = get_field_value( $meta, 'vehicle_model' );
$vehicle_year            = get_field_value( $meta, 'vehicle_year' );
$dimensions              = get_field_value( $meta, 'dimensions' );
$payload                 = get_field_value( $meta, 'payload' );

// Additional information
$residentship            = get_field_value( $meta, 'legal_document_type' );
$residentship_value      = $residentship ? $legalDocumentTypes[$residentship] : '';
$date_created            = get_field_value( $main, 'date_created' );

// Emergency contact
$emergency_contact_name     = get_field_value( $meta, 'emergency_contact_name' );
$emergency_contact_phone    = get_field_value( $meta, 'emergency_contact_phone' );
$emergency_contact_relation = get_field_value( $meta, 'emergency_contact_relation' );

// Recruiter
$recruiter_add = get_field_value( $meta, 'recruiter_add' );

// Language preferences
$language_str    = get_field_value( $meta, 'languages' );
$languages_array = $language_str ? explode( ',', $language_str ) : [];

// Get cross_border data
$cross_border = get_field_value( $meta, 'cross_border' );
$selected_cross_border = $cross_border ? array_map( 'trim', explode( ',', $cross_border ) ) : [];
$cdl_value = get_field_value( $meta, 'driver_licence_type' );
$cdl = $cdl_value === 'cdl';

// Capabilities and labels
$driver_capabilities = array(
	'cdl'               => $cdl,
	'hazmat'            => get_field_value( $meta, 'hazmat_certificate' ) || get_field_value( $meta, 'hazmat_endorsement' ),
	'tsa'               => get_field_value( $meta, 'tsa_approved' ),
	'twic'              => get_field_value( $meta, 'twic' ),
	'tanker-endorsement' => get_field_value( $meta, 'tanker_endorsement' ),
	'ppe'               => get_field_value( $meta, 'ppe' ),
	'e-track'           => get_field_value( $meta, 'e_tracks' ),
	'pallet-jack'       => get_field_value( $meta, 'pallet_jack' ),
	'ramp'              => get_field_value( $meta, 'ramp' ),
	'load-bars'         => get_field_value( $meta, 'load_bars' ),
	'liftgate'          => get_field_value( $meta, 'lift_gate' ),
	'team'              => get_field_value( $meta, 'team_driver_enabled' ),
	'canada'            => in_array( 'canada', $selected_cross_border, true ),
	'mexico'            => in_array( 'mexico', $selected_cross_border, true ),
	'alaska'            => in_array( 'alaska', $selected_cross_border, true ),
	'real_id'           => get_field_value( $meta, 'real_id' ),
	'macropoint'        => get_field_value( $meta, 'macro_point' ),
	'tucker-tools'      => get_field_value( $meta, 'trucker_tools' ),
	'change-9'          => get_field_value( $meta, 'change_9_training' ),
	'sleeper'           => get_field_value( $meta, 'sleeper' ),
	'dock-high'         => get_field_value( $meta, 'dock_high' ),
    'printer'         => get_field_value( $meta, 'printer' ),
);

$labels = $driver->labels;
$available_labels = array_intersect_key( $labels, array_filter( $driver_capabilities, function( $value ) {
	return ! empty( $value );
} ) );

// Format date
$formatted_date = '';
if ($date_created) {
	$date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $date_created);
	if ($date_obj) {
		$formatted_date = $date_obj->format('Y-m-d\TH:i');
	}
}

// Get recruiter name
$recruiter_name = '';
if ($recruiter_add) {
    $user = $TMSUsers->get_user_full_name_by_id($recruiter_add);
	$recruiter_name = $user['full_name'];
}

// Get state name
$state_name = '';
if ($home_location && isset($states[$home_location])) {
	$state_name = $states[$home_location];
}
?>

<div class="container mt-4 pb-5">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Driver Information</h2>
            
            <!-- Main Information Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Driver & Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Owner name:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($owner_name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Main contact:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($driver_phone ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>First driver name:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($driver_name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Second driver name:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($team_driver_name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Vehicle:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($vehicle_type ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Residentship:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($residentship_value ?: '-'); ?></div>
                            </div>
                        
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Owner phone:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($owner_phone ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Home state:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($state_name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>First driver phone:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($driver_phone ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Second driver phone:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($team_driver_phone ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Owner email:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($owner_email ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Home city:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($city ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>First driver email:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($driver_email ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Second driver email:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($team_driver_email ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Dimensions:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($dimensions ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Date:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($formatted_date ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Payload:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($payload ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Emergency contact:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($emergency_contact_name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Recruiter:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($recruiter_name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Emergency phone:</strong></div>
                                <div class="col-sm-8"><?php echo esc_html($emergency_contact_phone ?: '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Preferred Language Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Preferred Language</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($languages as $lang_key => $lang_name): ?>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           <?php echo in_array($lang_key, $languages_array) ? 'checked' : ''; ?> 
                                           disabled>
                                    <label class="form-check-label">
                                        <?php echo esc_html($lang_name); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Select Labels Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Capabilities & Features</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($labels as $label_key => $label_name): ?>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           <?php echo isset($available_labels[$label_key]) ? 'checked' : ''; ?> 
                                           disabled>
                                    <label class="form-check-label">
                                        <?php echo esc_html($label_name); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>