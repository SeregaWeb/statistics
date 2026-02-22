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

$helper     = new TMSReportsHelper();
$states     = $helper->get_states();
$recruiters = $helper->get_recruiters();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$driver_name   = get_field_value( $meta, 'driver_name' );
$driver_phone  = get_field_value( $meta, 'driver_phone' );
$driver_email  = get_field_value( $meta, 'driver_email' );
$home_location = get_field_value( $meta, 'home_location' );
$second_main_driver_phone = get_field_value( $meta, 'second_main_driver_phone' );
$second_main_driver_email = get_field_value( $meta, 'second_main_driver_email' );
$city          = get_field_value( $meta, 'city' );
$dob           = get_field_value( $meta, 'dob' );

$language_str    = get_field_value( $meta, 'languages' );
$languages_array = $language_str ? explode( ',', $language_str ) : [];

$team_driver_language_str    = get_field_value( $meta, 'team_driver_languages' );
$team_driver_languages_array = $team_driver_language_str ? explode( ',', $team_driver_language_str ) : [];

$macro_point                = get_field_value( $meta, 'macro_point' );
$trucker_tools              = get_field_value( $meta, 'trucker_tools' );
$emergency_contact_name     = get_field_value( $meta, 'emergency_contact_name' );
$emergency_contact_phone    = get_field_value( $meta, 'emergency_contact_phone' );
$emergency_contact_relation = get_field_value( $meta, 'emergency_contact_relation' );
$preferred_distance         = get_field_value( $meta, 'preferred_distance' );
$cross_border               = get_field_value( $meta, 'cross_border' );
$source                     = get_field_value( $meta, 'source' );
$recruiter_add              = get_field_value( $meta, 'recruiter_add' );
$show_phone                 = get_field_value( $meta, 'show_phone' );
$referer_by                 = get_field_value( $meta, 'referer_by' );
$referer_name               = get_field_value( $meta, 'referer_name' );

$interview_file            = get_field_value( $meta, 'interview_file' );
$interview_file_arr        = $driver->process_file_attachment( $interview_file );

$driver_photo = get_field_value( $meta, 'driver_photo' );
$driver_photo_arr = $driver->process_file_attachment( $driver_photo );




$files = array(
	array(
		'file_arr'       => $interview_file_arr,
		'file'           => $interview_file,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
    ), 
    array(
        'file_arr'       => $driver_photo_arr,
        'file'           => $driver_photo,
        'full_only_view' => $full_only_view,
        'post_id'        => $post_id,
    ),
);

$access_vehicle = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'hr_manager',
	'accounting',
	'billing',
	'billing-tl',
	'moderator',
], true );

$total_images = count( $files );

$files = array(
	array(
		'file_arr'       => $driver_photo_arr,
		'file'           => $driver_photo,
		'full_only_view' => $full_only_view,
		'post_id'        => $post_id,
		'class_name'     => 'driver-photo',
		'field_name'     => 'driver_photo',
		'field_label'    => 'Driver photo',
        'delete_action'  => 'js-remove-one-driver',
		'active_tab'     => 'pills-driver-contact-tab',
	),
);
?>

<div class="container mt-4 pb-5">
	<?php if ( $access_vehicle ): ?>
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <h2>Owner & Drivers Information</h2>

            <?php if ( $post_id && !$full_only_view ): ?>
            <div class="d-flex align-items-center gap-2">
                <button class='btn btn-outline-success js-remote-send-form' data-form="js-update-driver">Save</button>
            </div>
            <?php endif; ?>
        </div>


        <div class="container-uploads <?php echo $full_only_view ? "read-only" : '' ?>">
					
					<?php
					
					foreach ( $files as $file ):
						if ( isset( $file[ 'file_arr' ] ) && $file[ 'file' ] ):
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/card', 'file', array(
								'file_arr'       => $file[ 'file_arr' ],
								'file'           => $file[ 'file' ],
								'full_only_view' => $file[ 'full_only_view' ],
								'post_id'        => $file[ 'post_id' ],
								'class_name'     => $file[ 'class_name' ],
								'field_name'     => $file[ 'field_name' ],
								'field_label'    => $file[ 'field_label' ],
								'delete_action'  => $file[ 'delete_action' ] ?? 'js-remove-one-driver',
								'active_tab'     => $file[ 'active_tab' ] ?? 'pills-driver-contact-tab',
							) ) );
						endif;
					endforeach; ?>


        </div>
	<?php if ( $full_only_view ): ?>
    <form>
		<?php else: ?>
        <form class="<?php echo $post_id ? 'js-update-driver' : 'js-create-driver'; ?>">
			<?php endif; ?>
			<?php if ( $post_id ): ?>
                <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
			<?php endif; ?>

            <input type="hidden" name="ftl_driver" value="1">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Driver Name<span class="required-star text-danger">*</span></label>
                    <input required type="text" class="form-control" name="driver_name"
                           value="<?php echo $driver_name; ?>">
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
                <?php if ( !$driver_photo ):
					// Driver photo 
					$simple_upload_args = [
						'full_only_view' => $full_only_view || !$post_id,
						'field_name' => 'driver_photo',
						'label'      => 'Driver photo',
						'file_value' => $driver_photo,
						'popup_id'   => 'popup_upload_driver_photo',
						'col_class'  => 'col-12 col-md-6',
					];
					echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
				endif; ?>
            </div>

            <div class="row">
            

            <div class="col-md-4 mb-3">
                    <label class="form-label">Second Driver Phone</label>
                    <input type="tel" class="form-control js-tel-mask" name="second_main_driver_phone"
                           value="<?php echo $second_main_driver_phone; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Second Driver Email</label>
                    <input type="email" class="form-control" name="second_main_driver_email"
                           value="<?php echo $second_main_driver_email; ?>">
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
                    <input required type="text" class="form-control js-new-format-date" name="dob"
                           value="<?php echo $dob; ?>">
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
                    <label class="form-label">Language</label>

                    <div class="d-flex flex-wrap ">
						<?php foreach ( $languages as $key => $language ): ?>
                            <div class="form-check form-switch w-25">
                                <input class="form-check-input" type="checkbox"
                                       id="language_<?php echo strtolower( $key ); ?>" name="language[]"
                                       value="<?php echo $key; ?>" <?php echo in_array( $key, $languages_array )
									? 'checked' : ''; ?>>
                                <label class="form-check-label"
                                       for="language_<?php echo strtolower( $key ); ?>"><?php echo $language; ?></label>
                            </div>
						<?php endforeach; ?>
                    </div>
                </div>
            </div>

            <h4 class="mt-4">Preferred distance</h4>

            <div class="row">

                <div class="col-12 d-flex align-items-center gap-4 js-container-checkboxes">
					<?php
					// Преобразуем строку из базы в массив
					$selected_distances = array_map( 'trim', explode( ',', (string) ( $preferred_distance ?? '' ) ) );
					// Флаг, выбран ли 'any'
					$any_selected = in_array( 'any', $selected_distances, true );
					
					if ( is_array( $labels_distance ) ):
						foreach ( $labels_distance as $key => $label ):
							// Проверяем, нужно ли выделить этот чекбокс
							$is_checked = in_array( $key, $selected_distances, true );
							
							// Если 'any' выбран, и текущий чекбокс — не 'any', делаем его disabled + checked
							$is_disabled = $any_selected && $key !== 'any';
							?>
                            <div class="form-check form-switch">
                                <input class="form-check-input js-disable-with-logic"
                                       data-value="any"
                                       type="checkbox"
                                       id="<?php echo esc_attr( $key ); ?>"
                                       value="<?php echo esc_attr( $key ); ?>"
                                       name="preferred_distance[]"
									<?php echo $is_disabled ? 'checked' : checked( $is_checked ); ?>
									<?php disabled( $is_disabled ); ?>
                                />
                                <label class="form-check-label" for="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
                                </label>
                            </div>
						<?php
						endforeach;
					endif;
					?>

                </div>
            </div>

            <h4 class="mt-4">Cross border</h4>

            <div class="row">
                <div class="col-12 d-flex align-items-center gap-4">
					<?php
					
					$selected_cross_border = $cross_border ? array_map( 'trim', explode( ',', $cross_border ) ) : array();
					
					if ( is_array( $labels_border ) ):
						foreach ( $labels_border as $key => $label ):
							$is_checked = in_array( $key, $selected_cross_border, true );
							
							?>
                            <div class="form-check form-switch">
                                <input class="form-check-input js-disable-with-logic"
                                       data-value="any"
                                       type="checkbox"
                                       id="<?php echo esc_attr( $key ); ?>"
                                       value="<?php echo esc_attr( $key ); ?>"
                                       name="cross_border[]"
									<?php echo checked( $is_checked ); ?>
                                />
                                <label class="form-check-label" for="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
                                </label>
                            </div>
						<?php
						endforeach;
					endif;
					?>
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
                        <label class="form-label">Emergency Phone<span
                                    class="required-star text-danger">*</span></label>
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
                <div class="col-12 col-md-4 mb-3">
                    <label class="form-label">Source<span
                                class="required-star text-danger">*</span></label>
                    <select name="source" required class="form-control form-select js-source">
                        <option value="" disabled selected>Select source</option>
						<?php if ( is_array( $sources ) ): ?>
							<?php foreach ( $sources as $key => $val ): ?>
                                <option value="<?php echo $key; ?>"
									<?php echo $key === $source ? 'selected' : ''; ?>
									<?php echo is_array( $val ) ? 'disabled' : ''; ?>>
									<?php echo is_array( $val ) ? $val[ 0 ] : $val; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif ?>
                    </select>
                </div>

                <div class="col-12 col-md-8 mb-3">

                    <label class="form-label d-flex align-items-center gap-1">
                        Interview
                    </label>

                    <?php if ( !$interview_file_arr ): ?>
                        <div class="js-interview-file-driver">
                            <?php if ( ! $full_only_view ): ?>
                                <button <?php echo !$post_id ? 'disabled' : ''; ?> data-href="#popup_upload_interview_file"
                                        class="btn btn-success js-open-popup-activator">
                                    Upload file
                                </button>
                            <?php else: ?>
                                <p>-</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                    <div class="<?php echo 'js-remove-one-no-form'; ?> d-flex align-items-center gap-1 <?php echo 'interview-file'; ?>" data-tab="<?php echo 'pills-driver-contact-tab'; ?>">
                        <input type="hidden" name="image-id" value="<?php echo $interview_file_arr[ 'id' ]; ?>">
                        <input type="hidden" name="image-fields" value="<?php echo 'interview_file' ?>">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                        <audio controls class="w-100">
                            <source src="<?php echo $interview_file_arr[ 'url' ]; ?>" type="audio/mpeg">
                        </audio>

                        <?php if ( ! $full_only_view ): ?> 
                            <button class="btn btn-transparent btn-remove-file js-remove-one-no-form-btn">
                                <?php echo $helper->get_close_icon(); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
            </div>

                            <!-- Referer block - shown when source is "recommendation" -->
                            <div class="col-12 mb-3 js-referer-block" style="display: <?php echo $source === 'recommendation' ? 'block' : 'none'; ?>;">
                    <div class="row">
                    <div class="col-12">
                        <p class="h5">Referred by</p>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                            
                            <label for="referer_unit_number" class="form-label">Unit Number</label>
                            <div class="position-relative">
                                <input type="text" id="referer_unit_number" name="referer_unit_number" 
                                       value="<?php echo !empty($referer_name) && preg_match('/^\((\d+)\)/', $referer_name, $matches) ? $matches[1] : ''; ?>" 
                                       class="form-control js-referer-unit-number-input" 
                                       placeholder="Enter unit number..." autocomplete="off">
                                <div class="js-referer-driver-dropdown dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000; background: white; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <!-- Driver options will be populated here -->
                                </div>
                            </div>
                            
                            <!-- Hidden fields -->
                            <input type="hidden" name="referer_by" id="referer_by" value="<?php echo $referer_by; ?>">
                            <input type="hidden" name="referer_name" id="referer_name" value="<?php echo $referer_name; ?>">
                            <input type="hidden" name="old_referer_name" value="<?php echo $referer_name; ?>">
                            <input type="hidden" id="referer-driver-search-nonce" value="<?php echo wp_create_nonce('driver_search_nonce'); ?>">
                        </div>
                    </div>
                    
                    <style>
                        .js-referer-driver-dropdown .dropdown-item {
                            padding: 8px 12px;
                            cursor: pointer;
                            border-bottom: 1px solid #eee;
                        }
                        .js-referer-driver-dropdown .dropdown-item:hover,
                        .js-referer-driver-dropdown .dropdown-item.active {
                            background-color: #f8f9fa;
                        }
                    </style>
                </div>

                
            </div>


            <div class="row">
                <div class="mb-4 col-12 col-md-6 col-xl-4">
                    <label for="dispatcher_initials" class="form-label">Recruiter Initials</label>
					
					<?php
					if ( current_user_can( 'recruiter' ) || current_user_can( 'hr_manager' ) ) {
						if ( ! $recruiter_add ) {
							$recruiter_add = get_current_user_id();
							$user_name     = $helper->get_user_full_name_by_id( $recruiter_add );
							
							?>
                            <input type="hidden" name="recruiter_add" value="<?php echo $recruiter_add; ?>"
                                   required>
                            <p class="text-primary"><?php echo is_array( $user_name ) && isset( $user_name[ 'full_name' ] ) ? $user_name[ 'full_name' ] : 'Unknown User'; ?></p>
							<?php
						} else {
							$user_name = $helper->get_user_full_name_by_id( $recruiter_add );
							?>
                            <input type="hidden" name="recruiter_add" value="<?php echo $recruiter_add; ?>"
                                   required>
                            <p class="text-primary"><?php echo is_array( $user_name ) && isset( $user_name[ 'full_name' ] ) ? $user_name[ 'full_name' ] : 'Unknown User'; ?></p>
							<?php
						}
					} else { ?>
                        <select name="recruiter_add" class="form-control form-select" required>
                            <option value="">Select recruiter</option>
							<?php if ( is_array( $recruiters ) ): ?>
								<?php foreach ( $recruiters as $recruiter ): ?>
                                    <option value="<?php echo $recruiter[ 'id' ]; ?>" <?php echo strval( $recruiter_add ) === strval( $recruiter[ 'id' ] )
										? 'selected' : ''; ?> >
										<?php echo $recruiter[ 'fullname' ]; ?>
                                    </option>
								<?php endforeach; ?>
							<?php endif; ?>
                        </select>
					<?php } ?>

                </div>
            </div>

            <div class="row">

                <div class="col-12" role="presentation">
                    <div class="justify-content-start gap-2">
                        <button type="button" data-tab-id="pills-customer-tab"
                                class="btn btn-dark js-next-tab">Previous
                        </button>
						<?php if ( $full_only_view ): ?>
                            <button type="button" data-tab-id="pills-driver-finance-tab"
                                    class="btn btn-primary js-next-tab">Next
                            </button>
						<?php else: ?>
                            <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                    data-tab-id="pills-driver-finance-tab">
                                Next
                            </button>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
		
		<?php 
        $popups_upload = array(
            array(
                'title'     => 'Upload Interview file',
                'file_name' => 'interview_file',
                'multiply'  => false,
                'driver_id' => $post_id,
            ),
            array(
                'title'     => 'Upload Driver photo',
                'file_name' => 'driver_photo',
                'multiply'  => false,
                'driver_id' => $post_id,
            ),
        );

        foreach ( $popups_upload as $popup ):
            echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/upload', 'file', $popup ) );
        endforeach;

        ?>
		<?php else: ?>
            <div class="alert alert-info">You do not have permission to upload documents.</div>
		<?php endif; ?>

</div>