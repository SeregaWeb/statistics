<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );


$driver             = new TMSDrivers();
$languages          = $driver->languages;
$relation_options   = $driver->relation_options;
$owner_type_options = $driver->owner_type_options;

$helper = new TMSReportsHelper();
$states = $helper->get_states();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$driver_name     = get_field_value( $meta, 'driver_name' );
$driver_phone    = get_field_value( $meta, 'driver_phone' );
$driver_email    = get_field_value( $meta, 'driver_email' );
$home_location   = get_field_value( $meta, 'home_location' );
$city            = get_field_value( $meta, 'city' );
$dob             = get_field_value( $meta, 'dob' );
$language_str    = get_field_value( $meta, 'languages' );
$languages_array = $language_str ? explode( ',', $language_str ) : [];

$macro_point                = get_field_value( $meta, 'macro_point' );
$trucker_tools              = get_field_value( $meta, 'trucker_tools' );
$team_driver_enabled        = get_field_value( $meta, 'team_driver_enabled' );
$team_driver_name           = get_field_value( $meta, 'team_driver_name' );
$team_driver_phone          = get_field_value( $meta, 'team_driver_phone' );
$team_driver_email          = get_field_value( $meta, 'team_driver_email' );
$team_driver_dob            = get_field_value( $meta, 'team_driver_dob' );
$team_driver_macro_point    = get_field_value( $meta, 'team_driver_macro_point' );
$team_driver_trucker_tools  = get_field_value( $meta, 'team_driver_trucker_tools' );
$owner_enabled              = get_field_value( $meta, 'owner_enabled' );
$owner_name                 = get_field_value( $meta, 'owner_name' );
$owner_phone                = get_field_value( $meta, 'owner_phone' );
$owner_email                = get_field_value( $meta, 'owner_email' );
$owner_dob                  = get_field_value( $meta, 'owner_dob' );
$owner_type                 = get_field_value( $meta, 'owner_type' );
$owner_macro_point          = get_field_value( $meta, 'owner_macro_point' );
$owner_trucker_tools        = get_field_value( $meta, 'owner_trucker_tools' );
$emergency_contact_name     = get_field_value( $meta, 'emergency_contact_name' );
$emergency_contact_phone    = get_field_value( $meta, 'emergency_contact_phone' );
$emergency_contact_relation = get_field_value( $meta, 'emergency_contact_relation' );


$mc_enabled  = get_field_value( $meta, 'mc_enabled' );
$mc          = get_field_value( $meta, 'mc' );
$dot_enabled = get_field_value( $meta, 'dot_enabled' );
$dot         = get_field_value( $meta, 'dot' );
?>

<div class="container mt-4 pb-5">
    <h2 class="mb-3">Owner & Drivers Information</h2>
    <form class="<?php echo $post_id ? 'js-update-driver' : 'js-create-driver'; ?>">
		
		<?php if ( $post_id ): ?>
            <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
		<?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Driver Name<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="driver_name" value="<?php echo $driver_name; ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Driver Phone<span class="required-star text-danger">*</span></label>
                <input required type="tel" class="form-control js-tel-mask" name="driver_phone"
                       value="<?php echo $driver_phone; ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Driver Email<span class="required-star text-danger">*</span></label>
                <input required type="email" class="form-control" name="driver_email"
                       value="<?php echo $driver_email; ?>">
            </div>
        </div>


        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">State<span
                            class="required-star text-danger">*</span></label>
                <select name="home_location" required class="form-control form-select js-state">
                    <option value="" disabled selected>Select State</option>
					<?php if ( is_array( $states ) ): ?>
						<?php foreach ( $states as $key => $state ): ?>
                            <option value="<?php echo $key; ?>"
								<?php echo $key === $home_location ? 'selected' : ''; ?>
								<?php echo is_array( $state ) ? 'disabled' : ''; ?>>
								<?php echo is_array( $state ) ? $state[ 0 ] : $state; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">City<span class="required-star text-danger">*</span></label>
                <input required type="text" class="form-control" name="city" value="<?php echo $city; ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Date of Birth<span class="required-star text-danger">*</span></label>
                <input required type="date" class="form-control" name="dob" value="<?php echo $dob; ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="macroPoint" name="macro_point"
						<?php echo $macro_point ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="macroPoint">MacroPoint</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="truckerTools" name="trucker_tools"
						<?php echo $macro_point ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="truckerTools">Trucker Tools</label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">Language<span class="required-star text-danger">*</span></label>

                <div class="d-flex flex-wrap ">
					<?php foreach ( $languages as $key => $language ): ?>
                        <div class="form-check form-switch w-25">
                            <input class="form-check-input" type="checkbox"
                                   id="language_<?php echo strtolower( $key ); ?>" name="language[]"
                                   value="<?php echo $key; ?>" <?php echo in_array( $key, $languages_array ) ? 'checked'
								: ''; ?>>
                            <label class="form-check-label"
                                   for="language_<?php echo strtolower( $key ); ?>"><?php echo $language; ?></label>
                        </div>
					<?php endforeach; ?>
                </div>
            </div>
        </div>

        <h4 class="mt-4">Team Driver (Optional)</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-team-driver" type="checkbox" id="teamDriverSwitch"
						<?php echo $team_driver_enabled ? 'checked' : ''; ?>
                           name="team_driver_enabled">
                    <label class="form-check-label" for="teamDriverSwitch">Enable Team Driver</label>
                </div>
            </div>
        </div>

        <div class="col-12 js-team-driver <?php echo $team_driver_enabled ? '' : 'd-none'; ?>">
            <div class="row  border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded"
                 id="team-driver-fields">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Team Driver Name</label>
                    <input type="text" class="form-control" name="team_driver_name"
                           value="<?php echo $team_driver_name; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Team Driver Phone</label>
                    <input type="tel" class="form-control js-tel-mask" name="team_driver_phone"
                           value="<?php echo $team_driver_phone; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Team Driver Email</label>
                    <input type="email" class="form-control" name="team_driver_email"
                           value="<?php echo $team_driver_email; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="team_driver_dob"
                           value="<?php echo $team_driver_dob; ?>">
                </div>

                <div class="col-12"></div>

                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="teamDriverMacroPoint"
                               name="team_driver_macro_point" <?php echo $team_driver_macro_point ? 'checked' : ''; ?> >
                        <label class="form-check-label" for="teamDriverMacroPoint">MacroPoint</label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="teamDriverTruckerTools"
                               name="team_driver_trucker_tools" <?php echo $team_driver_trucker_tools ? 'checked'
							: ''; ?> >
                        <label class="form-check-label" for="teamDriverTruckerTools">Trucker Tools</label>
                    </div>
                </div>
            </div>
        </div>
        <h4 class="mt-4">Owner (Optional)</h4>
        <div class="row">
            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-owner-driver" type="checkbox" id="ownerSwitch" name="owner_enabled"
						<?php echo $owner_enabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ownerSwitch">Enable Owner</label>
                </div>
            </div>
        </div>
        <div class="col-12 js-owner-driver <?php echo $owner_enabled ? '' : 'd-none'; ?> ">
            <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded">
                <div class="col-md-4 mb-3 ">
                    <label class="form-label">Owner Name</label>
                    <input type="text" class="form-control" name="owner_name" value="<?php echo $owner_name; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Owner Phone</label>
                    <input type="tel" class="form-control js-tel-mask" name="owner_phone"
                           value="<?php echo $owner_phone; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Owner Email</label>
                    <input type="email" class="form-control" name="owner_email" value="<?php echo $owner_email; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="owner_dob" value="<?php echo $owner_dob; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Type</label>

                    <select class="form-control" name="owner_type">
						<?php foreach ( $owner_type_options as $key => $label ): ?>
                            <option value="<?php echo $key; ?>" <?php echo $owner_type === $key ? 'selected' : ''; ?>>
								<?php echo $label; ?>
                            </option>
						<?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ownerMacroPoint"
                               name="owner_macro_point"
							<?php echo $owner_macro_point ? 'checked' : ''; ?>
                        >
                        <label class="form-check-label" for="ownerMacroPoint">MacroPoint</label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ownerTruckerTools"
							<?php echo $owner_trucker_tools ? 'checked' : ''; ?>
                               name="owner_trucker_tools">
                        <label class="form-check-label" for="ownerTruckerTools">Trucker Tools</label>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="mt-4">Emergency Contact</h4>


        <div class="col-12">
            <div class="row border-1 border-primary border bg-light pt-3 pb-3 mb-3 rounded">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Emergency Contact Name<span
                                class="required-star text-danger">*</span></label>
                    <input required type="text" class="form-control" name="emergency_contact_name"
                           value="<?php echo $emergency_contact_name; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Emergency Phone<span class="required-star text-danger">*</span></label>
                    <input required type="tel" class="form-control js-tel-mask" name="emergency_contact_phone"
                           value="<?php echo $emergency_contact_phone; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relation<span class="required-star text-danger">*</span></label>
					<?php
					
					?>
                    <select required class="form-control" name="emergency_contact_relation">
						<?php foreach ( $relation_options as $key => $label ): ?>
                            <option value="<?php echo $key; ?>" <?php echo $emergency_contact_relation === $key
								? 'selected' : ''; ?>>
								<?php echo $label; ?>
                            </option>
						<?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-mc-enabled" type="checkbox" id="mcSwitch" name="mc_enabled"
						<?php echo $mc_enabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="mcSwitch">MC enabled</label>
                </div>
            </div>

            <div class="col-12 js-mc-enabled <?php echo $mc_enabled ? '' : 'd-none'; ?> ">
                <div class="row">
                    <div class="col-md-4 mb-3 ">
                        <label class="form-label">MC Number</label>
                        <input type="text" class="form-control" name="mc" value="<?php echo $mc; ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-2">
                <div class="form-check form-switch">
                    <input class="form-check-input js-toggle"
                           data-block-toggle="js-dot-enabled" type="checkbox" id="dotSwitch" name="dot_enabled"
						<?php echo $dot_enabled ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="dotSwitch">DOT enabled</label>
                </div>
            </div>

            <div class="col-12 js-dot-enabled <?php echo $dot_enabled ? '' : 'd-none'; ?> ">
                <div class="row">
                    <div class="col-md-4 mb-3 ">
                        <label class="form-label">DOT Number</label>
                        <input type="text" class="form-control" name="dot" value="<?php echo $dot; ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-12" role="presentation">
                <div class="justify-content-start gap-2">
                    <button type="button" data-tab-id="pills-customer-tab"
                            class="btn btn-dark js-next-tab">Previous
                    </button>
					<?php if ( $full_only_view ): ?>
                        <button type="button" data-tab-id="pills-driver-vehicle-tab"
                                class="btn btn-primary js-next-tab">Next
                        </button>
					<?php else: ?>
                        <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                data-tab-id="pills-driver-vehicle-tab">
                            Next
                        </button>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>