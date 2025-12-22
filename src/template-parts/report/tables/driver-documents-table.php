<?php
$drivers      = new TMSDrivers();
$helper       = new TMSReportsHelper();
$icons        = new TMSReportsIcons();
$driverHelper = new TMSDriversHelper();
$TMSUsers     = new TMSUsers();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );

// Get selected document type from filter
$selected_document_type = trim( get_field_value( $_GET, 'document_type' ) ?? '' );

$access_show_recruiter_bonus = $TMSUsers->check_user_role_access( array( 'administrator', 'recruiter', 'recruiter-tl', 'accounting', 'moderator' ), true );

if ( ! empty( $results ) ) : ?>
	
    <div class="mb-3 d-flex gap-1 justify-content-end">

        <button type="button" class="btn btn-outline-success" id="copy-driver-emails-btn">
            Copy All Emails
        </button>
            
        <button type="button" class="btn btn-outline-primary" id="copy-driver-phones-btn">
            Copy All Phone Numbers
        </button>
    </div>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Driver</th>
            <th scope="col">Expiration date</th>
            <th scope="col">Documents</th>
            <th scope="col">Notes</th>
            <th scope="col">Status</th>
        </tr>
        </thead>
        <tbody>
		<?php
		
		foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
            $available_date = get_field_value( $row, 'date_available' );
			$driver_name = get_field_value( $meta, 'driver_name' );
			$languages = get_field_value( $meta, 'languages' );
			$recruiter_bonus_paid = get_field_value( $meta, 'recruiter_bonus_paid' );
			$driver_email = get_field_value( $meta, 'driver_email' );
			$home_location = get_field_value( $meta, 'home_location' );
			$city = get_field_value( $meta, 'city' );
			$vehicle_type = get_field_value( $meta, 'vehicle_type' );
			$vehicle_year = get_field_value( $meta, 'vehicle_year' );
			$vehicle_model = get_field_value( $meta, 'vehicle_model' );
			$vehicle_make = get_field_value( $meta, 'vehicle_make' );
			$dimensions = get_field_value( $meta, 'dimensions' );
			$payload = get_field_value( $meta, 'payload' );
			$driver_status = get_field_value( $meta, 'driver_status' );
			
			$show_phone   = get_field_value( $meta, 'show_phone' ) ?? 'driver_phone';
			$driver_phone = get_field_value( $meta, $show_phone );
			
			include( get_template_directory() . '/src/template-parts/report/common/driver-capabilities.php' );
			
			$driver_status = trim( $driver_status );
			$is_hold = $driver_status === 'on_hold';
			
			if ( $driver_status && isset( $drivers->status[ $driver_status ] ) || $driver_status === 'on_hold' ) {
				
				if ( $driver_status === 'on_hold' ) {
					$status_text = 'On hold';
				} else {
					$status_text = $drivers->status[ $driver_status ];
				}
			} else {
				$status_text = "Need set status";
			}
			
			// Get driver data for quick update button
			$current_location = get_field_value( $meta, 'current_location' );
			$current_city     = get_field_value( $meta, 'current_city' );
			$current_zipcode  = get_field_value( $meta, 'current_zipcode' );
			$latitud          = get_field_value( $meta, 'latitude' );
			$longitude        = get_field_value( $meta, 'longitude' );
			$country          = get_field_value( $meta, 'country' );
			// Convert MySQL datetime format to flatpickr format (m/d/Y H:i) for modal
			$status_date = TMSDriversHelper::convert_mysql_to_flatpickr_date( $available_date );
			$last_user_update = get_field_value( $meta, 'last_user_update' );
			$recruiter_notes = get_field_value( $meta, 'recruiter_notes' );

			// TODO: Remove this after testing
			$class_hide = $row['id'] === '3343' ? 'd-none' : '';


            $driver_statistics = $drivers->get_driver_statistics( $row[ 'id' ] );
			
			// Function to determine button color based on value
			$get_button_color = function( $value ) {
				
				if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
					return 'btn-secondary'; // grey
				}
				
				if ( + $value <= 1 ) {
					return 'btn-danger'; // red
				}
				
				if ( + $value <= 4 ) {
					return 'btn-warning'; // orange
				}
				
				if ( + $value > 4 ) {
					return 'btn-success'; // green
				}
				
				return 'btn-secondary'; // default grey
			};


			?>

            <tr class="<?php echo $class_hide; ?>" data-driver-id="<?php echo $row[ 'id' ]; ?>">
                
                <td>
                    <?php
                    // Helper function to check document status
                    $check_document_status = function($expiration_date, $immigration_letter = '') {
                        if (empty($expiration_date)) {
                            return array('status' => 'no_date', 'is_expired' => false, 'badge_class' => 'bg-secondary');
                        }
                        
                        if (!empty($immigration_letter)) {
                            return array('status' => 'extended', 'is_expired' => false, 'badge_class' => 'bg-info');
                        }
                        
                        // Parse date - try different formats
                        $date_timestamp = false;
                        $date_formats = array('m/d/Y', 'Y-m-d', 'm-d-Y', 'Y/m/d');
                        
                        foreach ($date_formats as $format) {
                            $date_obj = DateTime::createFromFormat($format, $expiration_date);
                            if ($date_obj !== false) {
                                $date_timestamp = $date_obj->getTimestamp();
                                break;
                            }
                        }
                        
                        // Fallback to strtotime if DateTime::createFromFormat failed
                        if ($date_timestamp === false) {
                            $date_timestamp = strtotime($expiration_date);
                        }
                        
                        if (!$date_timestamp || $date_timestamp === false) {
                            return array('status' => 'invalid', 'is_expired' => false, 'badge_class' => 'bg-secondary');
                        }
                        
                        $today = strtotime('today');
                        $days_diff = floor(($date_timestamp - $today) / 86400);
                        
                        if ($days_diff < 0) {
                            return array('status' => 'expired', 'is_expired' => true, 'badge_class' => 'bg-danger', 'days' => abs($days_diff));
                        } elseif ($days_diff <= 30) {
                            return array('status' => 'expires_soon', 'is_expired' => false, 'badge_class' => 'bg-warning', 'days' => $days_diff);
                        } else {
                            return array('status' => 'valid', 'is_expired' => false, 'badge_class' => 'bg-success', 'days' => $days_diff);
                        }
                    };
                    
                    // Collect all documents with status
                    $documents_data = array(
                        'VR' => array(
                            'name' => 'Vehicle Registration',
                            'doc_type' => get_field_value($meta, 'registration_type'),
                            'exp_date' => get_field_value($meta, 'registration_expiration'),
                            'is_expired' => false
                        ),
                        'PL' => array(
                            'name' => 'Plates',
                            'doc_type' => get_field_value($meta, 'plates'),
                            'exp_date' => get_field_value($meta, 'plates_expiration'),
                            'is_expired' => false
                        ),
                        'DL' => array(
                            'name' => 'Driver\'s License',
                            'doc_type' => get_field_value($meta, 'driver_licence_type'),
                            'exp_date' => get_field_value($meta, 'driver_licence_expiration'),
                            'is_expired' => false
                        ),
                        'COI' => array(
                            'name' => 'Certificate of Insurance',
                            'doc_type' => get_field_value($meta, 'auto_liability_coi'),
                            'exp_date' => get_field_value($meta, 'auto_liability_expiration'),
                            'is_expired' => false
                        ),
                    );
                    
                    // Check COI variants
                    $coi_variants = array(
                        'martlet_coi' => get_field_value($meta, 'martlet_coi_expired_date'),
                        'endurance_coi' => get_field_value($meta, 'endurance_coi_expired_date'),
                        'motor_cargo_coi' => get_field_value($meta, 'motor_cargo_expiration')
                    );
                    
                    // Legal document mapping
                    $legal_doc_type = get_field_value($meta, 'legal_document_type');
                    $legal_doc_exp = get_field_value($meta, 'legal_document_expiration');
                    $immigration_letter = get_field_value($meta, 'immigration_letter');
                    
                    $legal_doc_map = array(
                        'work-authorization' => 'EA',
                        'permanent-resident-card' => 'PR',
                        'passport' => 'PS'
                    );
                    
                    // Add legal document to documents_data if it exists
                    if (!empty($legal_doc_type) && isset($legal_doc_map[$legal_doc_type])) {
                        $abbr = $legal_doc_map[$legal_doc_type];
                        $doc_name = $abbr === 'EA' ? 'Employment Authorization' : ($abbr === 'PR' ? 'Permanent Resident' : 'Passport');
                        $documents_data[$abbr] = array(
                            'name' => $doc_name,
                            'doc_type' => $legal_doc_type,
                            'exp_date' => $legal_doc_exp,
                            'is_expired' => false,
                            'immigration_letter' => $immigration_letter
                        );
                    }
                    
                    // Additional documents with abbreviations
                    $additional_docs = array(
                        'HZ' => array(
                            'name' => 'Hazmat Certificate',
                            'exp_date' => get_field_value($meta, 'hazmat_expiration')
                        ),
                        'GE' => array(
                            'name' => 'Global Entry',
                            'exp_date' => get_field_value($meta, 'global_entry_expiration')
                        ),
                        'TWIC' => array(
                            'name' => 'TWIC',
                            'exp_date' => get_field_value($meta, 'twic_expiration')
                        ),
                        'TSA' => array(
                            'name' => 'TSA',
                            'exp_date' => get_field_value($meta, 'tsa_expiration')
                        )
                    );
                    
                    // Process all documents and create labels - show ALL documents including missing ones
                    $document_labels = array();
                    
                    // Define all possible documents in order
                    // VR types: vehicle-registration -> VR, bill-of-sale -> BS, certificate-of-title -> CT
                    $all_documents_list = array(
                        'VR' => array('name' => 'Vehicle Registration', 'required' => false),
                        'BS' => array('name' => 'Bill of sale', 'required' => false),
                        'CT' => array('name' => 'Certificate of title', 'required' => false),
                        'PL' => array('name' => 'Plates', 'required' => false),
                        'DL' => array('name' => 'Driver\'s License', 'required' => true),
                        'COI' => array('name' => 'Certificate of Insurance', 'required' => false),
                        'EA' => array('name' => 'Employment Authorization', 'required' => false),
                        'PR' => array('name' => 'Permanent Resident', 'required' => false),
                        'PS' => array('name' => 'Passport', 'required' => false),
                        'HZ' => array('name' => 'Hazmat Certificate', 'required' => false),
                        'GE' => array('name' => 'Global Entry', 'required' => false),
                        'TWIC' => array('name' => 'TWIC', 'required' => false),
                        'TSA' => array('name' => 'TSA', 'required' => false),
                    );
                    
                    // Map registration types to abbreviations
                    $registration_type_map = array(
                        'vehicle-registration' => 'VR',
                        'bill-of-sale' => 'BS',
                        'certificate-of-title' => 'CT'
                    );
                    
                    // Process Vehicle Registration documents first (VR, BS, CT) based on registration_type
                    $vr_reg_type = get_field_value($meta, 'registration_type');
                    $vr_exp_date = get_field_value($meta, 'registration_expiration');
                    $vr_file = get_field_value($meta, 'registration_file');
                    $vr_has_doc = !empty($vr_reg_type) || !empty($vr_exp_date) || !empty($vr_file);
                    
                    if ($vr_has_doc && !empty($vr_reg_type) && isset($registration_type_map[$vr_reg_type])) {
                        // Map registration type to abbreviation (VR, BS, or CT)
                        $vr_abbr = $registration_type_map[$vr_reg_type];
                        $vr_doc_info = $all_documents_list[$vr_abbr];
                        $vr_check_result = $check_document_status($vr_exp_date);
                        
                        // Build tooltip text
                        $vr_tooltip_text = '<strong>' . esc_html($vr_doc_info['name']) . '</strong>';
                        if (!empty($vr_exp_date)) {
                            $vr_tooltip_text .= '<br>Expiration: ' . esc_html($vr_exp_date);
                            if (isset($vr_check_result['days'])) {
                                if ($vr_check_result['status'] === 'expired') {
                                    $vr_tooltip_text .= '<br><span class="text-danger">Expired ' . $vr_check_result['days'] . ' days ago</span>';
                                } elseif ($vr_check_result['status'] === 'expires_soon') {
                                    $vr_tooltip_text .= '<br><span class="text-warning">Expires in ' . $vr_check_result['days'] . ' days</span>';
                                } else {
                                    $vr_tooltip_text .= '<br><span class="text-success">Valid (' . $vr_check_result['days'] . ' days remaining)</span>';
                                }
                            }
                        } else {
                            $vr_tooltip_text .= '<br><span class="text-white">No expiration date</span>';
                        }
                        
                        $document_labels[] = array(
                            'abbr' => $vr_abbr,
                            'name' => $vr_doc_info['name'],
                            'exp_date' => $vr_exp_date,
                            'badge_class' => $vr_check_result['badge_class'],
                            'status' => $vr_check_result['status'],
                            'days' => $vr_check_result['days'] ?? null,
                            'immigration_letter' => '',
                            'tooltip' => $vr_tooltip_text
                        );
                    }
                    
                    // Process main documents (skip VR, BS, CT as they are handled above)
                    foreach ($all_documents_list as $abbr => $doc_info) {
                        $has_doc = false;
                        $doc_data = null;
                        
                        // Skip VR, BS, CT (handled above) and EA/PR/PS (handled separately)
                        if (in_array($abbr, array('VR', 'BS', 'CT', 'EA', 'PR', 'PS'))) {
                            continue;
                        }
                        
                        // Check if document exists in documents_data
                        if (isset($documents_data[$abbr])) {
                            $doc_data = $documents_data[$abbr];
                            $has_doc = !empty($doc_data['doc_type']) || !empty($doc_data['exp_date']);
                        } elseif (isset($additional_docs[$abbr])) {
                            $doc_data = $additional_docs[$abbr];
                            $has_doc = !empty($doc_data['exp_date']);
                        }
                        
                        if ($has_doc && $doc_data) {
                            // Document exists - check status
                            $exp_date = $doc_data['exp_date'] ?? '';
                            $immigration_letter = $doc_data['immigration_letter'] ?? '';
                            $check_result = $check_document_status($exp_date, $immigration_letter);
                            
                            // Build tooltip text
                            $tooltip_text = '<strong>' . esc_html($doc_info['name']) . '</strong>';
                            
                            if (!empty($exp_date)) {
                                $tooltip_text .= '<br>Expiration: ' . esc_html($exp_date);
                                if (isset($check_result['days'])) {
                                    if ($check_result['status'] === 'expired') {
                                        $tooltip_text .= '<br><span class="text-danger">Expired ' . $check_result['days'] . ' days ago</span>';
                                    } elseif ($check_result['status'] === 'expires_soon') {
                                        $tooltip_text .= '<br><span class="text-warning">Expires in ' . $check_result['days'] . ' days</span>';
                                    } else {
                                        $tooltip_text .= '<br><span class="text-success">Valid (' . $check_result['days'] . ' days remaining)</span>';
                                    }
                                }
                            } else {
                                $tooltip_text .= '<br><span class="text-white">No expiration date</span>';
                            }
                            
                            if (!empty($immigration_letter)) {
                                $tooltip_text .= '<br><span class="text-info">Extended [Immigration Letter]</span>';
                            }
                            
                            $document_labels[] = array(
                                'abbr' => $abbr,
                                'name' => $doc_info['name'],
                                'exp_date' => $exp_date,
                                'badge_class' => $check_result['badge_class'],
                                'status' => $check_result['status'],
                                'days' => $check_result['days'] ?? null,
                                'immigration_letter' => $immigration_letter,
                                'tooltip' => $tooltip_text
                            );
                        }
                    }
                    
                    // Handle legal documents (EA, PR, PS) separately
                    $legal_docs_list = array(
                        'EA' => array('name' => 'Employment Authorization', 'type' => 'work-authorization'),
                        'PR' => array('name' => 'Permanent Resident', 'type' => 'permanent-resident-card'),
                        'PS' => array('name' => 'Passport', 'type' => 'passport'),
                    );
                    
                    foreach ($legal_docs_list as $abbr => $legal_doc_info) {
                        $has_legal_doc = false;
                        $legal_doc_data = null;
                        
                        // Check if this legal document type exists
                        if (isset($documents_data[$abbr])) {
                            $legal_doc_data = $documents_data[$abbr];
                            $has_legal_doc = !empty($legal_doc_data['doc_type']) || !empty($legal_doc_data['exp_date']);
                        }
                        
                        if ($has_legal_doc && $legal_doc_data) {
                            // Legal document exists - check status
                            $exp_date = $legal_doc_data['exp_date'] ?? '';
                            $immigration_letter = $legal_doc_data['immigration_letter'] ?? '';
                            $check_result = $check_document_status($exp_date, $immigration_letter);
                            
                            // Build tooltip text
                            $tooltip_text = '<strong>' . esc_html($legal_doc_info['name']) . '</strong>';
                            if (!empty($exp_date)) {
                                $tooltip_text .= '<br>Expiration: ' . esc_html($exp_date);
                                if (isset($check_result['days'])) {
                                    if ($check_result['status'] === 'expired') {
                                        $tooltip_text .= '<br><span class="text-danger">Expired ' . $check_result['days'] . ' days ago</span>';
                                    } elseif ($check_result['status'] === 'expires_soon') {
                                        $tooltip_text .= '<br><span class="text-warning">Expires in ' . $check_result['days'] . ' days</span>';
                                    } else {
                                        $tooltip_text .= '<br><span class="text-success">Valid (' . $check_result['days'] . ' days remaining)</span>';
                                    }
                                }
                            } else {
                                $tooltip_text .= '<br><span class="text-white">No expiration date</span>';
                            }
                            
                            if (!empty($immigration_letter)) {
                                $tooltip_text .= '<br><span class="text-info">Extended [Immigration Letter]</span>';
                            }
                            
                            $document_labels[] = array(
                                'abbr' => $abbr,
                                'name' => $legal_doc_info['name'],
                                'exp_date' => $exp_date,
                                'badge_class' => $check_result['badge_class'],
                                'status' => $check_result['status'],
                                'days' => $check_result['days'] ?? null,
                                'immigration_letter' => $immigration_letter,
                                'tooltip' => $tooltip_text
                            );
                        }
                    }
                    
                    // Handle COI variants separately
                    $has_coi = false;
                    $coi_tooltip_parts = array();
                    $coi_exp_dates = array();
                    foreach ($coi_variants as $coi_name => $coi_exp) {
                        if (!empty($coi_exp)) {
                            $has_coi = true;
                            $check_result = $check_document_status($coi_exp);
                            $coi_name_display = ucfirst(str_replace('_', ' ', str_replace('_coi', '', $coi_name)));
                            $coi_tooltip_parts[] = '<strong>' . esc_html($coi_name_display) . '</strong>: ' . esc_html($coi_exp);
                            if ($check_result['status'] === 'expired') {
                                $coi_tooltip_parts[] = ' <span class="text-danger">(Expired)</span>';
                            }
                            $coi_exp_dates[] = $coi_exp;
                        }
                    }
                    
                    // Check if COI already in labels (from auto_liability_coi)
                    $coi_exists = false;
                    foreach ($document_labels as $label) {
                        if ($label['abbr'] === 'COI') {
                            $coi_exists = true;
                            break;
                        }
                    }
                    
                    if ($has_coi && !$coi_exists) {
                        // Get the earliest expiration date for COI
                        $earliest_coi_exp = !empty($coi_exp_dates) ? min($coi_exp_dates) : '';
                        $coi_check_result = !empty($earliest_coi_exp) ? $check_document_status($earliest_coi_exp) : array('badge_class' => 'bg-success', 'status' => 'valid');
                        
                        $coi_tooltip = '<strong>Certificate of Insurance</strong><br>' . implode('<br>', $coi_tooltip_parts);
                        $document_labels[] = array(
                            'abbr' => 'COI',
                            'name' => 'Certificate of Insurance',
                            'exp_date' => $earliest_coi_exp,
                            'badge_class' => $coi_check_result['badge_class'],
                            'status' => $coi_check_result['status'] ?? 'valid',
                            'days' => $coi_check_result['days'] ?? null,
                            'immigration_letter' => '',
                            'tooltip' => $coi_tooltip
                        );
                    }
                    ?>
                    <div class="d-flex flex-column align-items-start">
                        <span class="badge bg-primary">Driver</span>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <strong>(<?php echo esc_html( $row['id'] ); ?>) <?php echo esc_html( $driver_name ); ?></strong>
							<?php echo $icons->get_flags( $languages ); ?>
                        </div>
                        <span class="text-small driver-phone" data-phone="<?php echo esc_attr( $driver_phone ); ?>">
                            <?php echo esc_html( $driver_phone ); ?>
                        </span>
                        <span class="text-small text-muted driver-email" data-email="<?php echo esc_attr( $driver_email ); ?>">
                            <?php echo esc_html( $driver_email ); ?>
                        </span>
                    </div>
                </td>
                <td>
                    <?php
                    // Display expiration date for selected document type
                    if ( ! empty( $selected_document_type ) ) {
                        $expiration_date = '';
                        $document_found = false;
                        
                        // Check if it's a team driver document type
                        $is_team_driver_doc = in_array( $selected_document_type, array( 'DL_TEAM', 'EA_TEAM', 'PR_TEAM', 'PS_TEAM' ) );
                        
                        if ( ! $is_team_driver_doc ) {
                            // Find the selected document in document_labels
                            foreach ( $document_labels as $label_data ) {
                                if ( $label_data['abbr'] === $selected_document_type ) {
                                    $expiration_date = $label_data['exp_date'];
                                    $document_found = true;
                                    break;
                                }
                            }
                        }
                        
                        if ( $document_found && ! empty( $expiration_date ) ) {
                            echo '<span class="text-nowrap">' . esc_html( $expiration_date ) . '</span>';
                        } elseif ( $document_found ) {
                            echo '<span class="text-muted">No date</span>';
                        }
                    }
                    ?>
                </td>
            
                <td>
                    <?php
                    // Display all documents in a row with tooltips
                    if (!empty($document_labels)) {
                        echo '<div class="d-flex gap-1 flex-wrap align-items-center">';
                        foreach ($document_labels as $label_data) {
                            // Special styling for missing required documents
                            $badge_style = '';
                            if ($label_data['status'] === 'missing') {
                                $badge_style = 'style="font-size: 12px; padding: 6px 10px; cursor: help; opacity: 0.6; border: 1px dashed #6c757d;"';
                            } else {
                                $badge_style = 'style="font-size: 12px; padding: 6px 10px; cursor: help;"';
                            }
                            
                            echo '<span class="badge ' . esc_attr($label_data['badge_class']) . ' js-document-tooltip" 
                                  ' . $badge_style . '
                                  data-bs-toggle="tooltip" 
                                  data-bs-html="true"
                                  data-bs-placement="top"
                                  data-bs-custom-class="driver-document-tooltip"
                                  data-bs-title="' . esc_attr($label_data['tooltip']) . '">';
                            
                            // Add visual indicator for missing documents
                            if ($label_data['status'] === 'missing') {
                                echo '<span style="text-decoration: line-through;">' . esc_html($label_data['abbr']) . '</span>';
                            } else {
                                echo esc_html($label_data['abbr']);
                            }
                            
                            echo '</span>';
                        }
                        echo '</div>';
                    } else {
                        echo '<span class="text-white" style="font-size: 11px;">No documents</span>';
                    }
                    ?>
                </td>
                
                <td class="js-driver-notes-cell" width="300" data-driver-id="<?php echo esc_attr( $row['id'] ); ?>">
                    <div class="js-driver-notes-display">
                        <div class="d-flex align-items-start gap-2">
                            <div class="flex-grow-1">
                                <?php if ( ! empty( $recruiter_notes ) ): ?>
                                    <div class="text-small"><?php echo nl2br( esc_html( $recruiter_notes ) ); ?></div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success js-edit-notes js-driver-notes-edit-btn" title="Edit notes">
                                <?php echo $icons->get_icon_edit_2(); ?>
                            </button>
                        </div>
                    </div>
                    <div class="js-driver-notes-edit d-none">
                        <textarea class="form-control form-control-sm js-notes-textarea" rows="3" style="min-width: 200px;"><?php echo esc_textarea( $recruiter_notes ); ?></textarea>
                        <div class="d-flex gap-1 mt-2">
                            <button type="button" class="btn btn-sm btn-success js-save-notes">Save</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary js-cancel-notes">Cancel</button>
                        </div>
                    </div>
                </td>

                <td style="width: 100px;" class="<?php echo $driver_status ? $driver_status
					: 'text-danger'; ?> driver-status"><?php echo $status_text; ?></td>

                <td style="width: 36px;">
                    <div class="d-flex gap-1 align-items-center justify-content-end">
						<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown-driver', [
							'id'       => $row[ 'id' ],
							'is_draft' => $is_draft,
							'is_archive' => $is_archive,
						] ) ); ?>
                    </div>
                </td>
            </tr>
            
            <?php
            // Get team driver and owner data for separate rows
            $team_driver_enabled = get_field_value( $meta, 'team_driver_enabled' );
            $team_driver_name = get_field_value( $meta, 'team_driver_name' );
            $team_driver_phone = get_field_value( $meta, 'team_driver_phone' );
            
            $owner_enabled = get_field_value( $meta, 'owner_enabled' );
            $owner_name = get_field_value( $meta, 'owner_name' );
            $owner_phone = get_field_value( $meta, 'owner_phone' );
            $owner_van_proprietor = get_field_value( $meta, 'owner_van_proprietor' );
            $owner_operator = get_field_value( $meta, 'owner_operator' );
            
            // Team driver row
            if ( ! empty( $team_driver_enabled ) && $team_driver_enabled !== '0' && $team_driver_enabled !== 'false' 
                 && ( ! empty( $team_driver_name ) || ! empty( $team_driver_phone ) ) ) {
                
                // Get team driver document data
                $team_driver_licence_type = get_field_value( $meta, 'driver_licence_type_team_driver' );
                $team_driver_licence_exp = get_field_value( $meta, 'driver_licence_expiration_team_driver' );
                $team_driver_legal_doc_type = get_field_value( $meta, 'legal_document_type_team_driver' );
                $team_driver_legal_doc_exp = get_field_value( $meta, 'legal_document_expiration_team_driver' );
                $team_driver_immigration_letter = get_field_value( $meta, 'immigration_letter_team_driver' );
                
                // Collect team driver documents - show ALL documents including missing ones
                $team_driver_documents = array();
                
                // Define all possible team driver documents
                $team_driver_docs_list = array(
                    'DL' => array('name' => 'Driver\'s License (Team driver)', 'required' => false),
                    'EA' => array('name' => 'Employment Authorization (Team driver)', 'type' => 'work-authorization'),
                    'PR' => array('name' => 'Permanent Resident (Team driver)', 'type' => 'permanent-resident-card'),
                    'PS' => array('name' => 'Passport (Team driver)', 'type' => 'passport'),
                );
                
                // Process DL (Driver's License)
                $has_dl = ! empty( $team_driver_licence_type ) || ! empty( $team_driver_licence_exp );
                if ( $has_dl ) {
                    $check_result = $check_document_status( $team_driver_licence_exp ?? '', '' );
                    $tooltip_text = '<strong>Driver\'s License (Team driver)</strong>';
                    if ( ! empty( $team_driver_licence_exp ) ) {
                        $tooltip_text .= '<br>Expiration: ' . esc_html( $team_driver_licence_exp );
                        if ( isset( $check_result['days'] ) ) {
                            if ( $check_result['status'] === 'expired' ) {
                                $tooltip_text .= '<br><span class="text-danger">Expired ' . $check_result['days'] . ' days ago</span>';
                            } elseif ( $check_result['status'] === 'expires_soon' ) {
                                $tooltip_text .= '<br><span class="text-warning">Expires in ' . $check_result['days'] . ' days</span>';
                            } else {
                                $tooltip_text .= '<br><span class="text-success">Valid (' . $check_result['days'] . ' days remaining)</span>';
                            }
                        }
                    } else {
                        $tooltip_text .= '<br><span class="text-white">No expiration date</span>';
                    }
                    
                    $team_driver_documents[] = array(
                        'abbr' => 'DL',
                        'name' => 'Driver\'s License (Team driver)',
                        'exp_date' => $team_driver_licence_exp ?? '',
                        'badge_class' => $check_result['badge_class'],
                        'status' => $check_result['status'],
                        'days' => $check_result['days'] ?? null,
                        'immigration_letter' => '',
                        'tooltip' => $tooltip_text
                    );
                }
                
                // Process legal documents (EA, PR, PS)
                $legal_doc_map = array(
                    'work-authorization' => 'EA',
                    'permanent-resident-card' => 'PR',
                    'passport' => 'PS'
                );
                
                foreach ( $team_driver_docs_list as $abbr => $doc_info ) {
                    // Skip DL, already processed
                    if ( $abbr === 'DL' ) {
                        continue;
                    }
                    
                    $has_legal_doc = false;
                    $legal_doc_data = null;
                    
                    // Check if this legal document type exists
                    if ( ! empty( $team_driver_legal_doc_type ) && isset( $legal_doc_map[ $team_driver_legal_doc_type ] ) && $legal_doc_map[ $team_driver_legal_doc_type ] === $abbr ) {
                        $has_legal_doc = true;
                        $legal_doc_data = array(
                            'type' => $team_driver_legal_doc_type,
                            'exp_date' => $team_driver_legal_doc_exp,
                            'immigration_letter' => $team_driver_immigration_letter
                        );
                    }
                    
                    if ( $has_legal_doc && $legal_doc_data ) {
                        // Legal document exists - check status
                        $exp_date = $legal_doc_data['exp_date'] ?? '';
                        $immigration_letter = $legal_doc_data['immigration_letter'] ?? '';
                        $check_result = $check_document_status( $exp_date, $immigration_letter );
                        
                        $tooltip_text = '<strong>' . esc_html( $doc_info['name'] ) . '</strong>';
                        if ( ! empty( $exp_date ) ) {
                            $tooltip_text .= '<br>Expiration: ' . esc_html( $exp_date );
                            if ( isset( $check_result['days'] ) ) {
                                if ( $check_result['status'] === 'expired' ) {
                                    $tooltip_text .= '<br><span class="text-danger">Expired ' . $check_result['days'] . ' days ago</span>';
                                } elseif ( $check_result['status'] === 'expires_soon' ) {
                                    $tooltip_text .= '<br><span class="text-warning">Expires in ' . $check_result['days'] . ' days</span>';
                                } else {
                                    $tooltip_text .= '<br><span class="text-success">Valid (' . $check_result['days'] . ' days remaining)</span>';
                                }
                            }
                        } else {
                            $tooltip_text .= '<br><span class="text-white">No expiration date</span>';
                        }
                        
                        if ( ! empty( $immigration_letter ) ) {
                            $tooltip_text .= '<br><span class="text-info">Extended [Immigration Letter]</span>';
                        }
                        
                        $team_driver_documents[] = array(
                            'abbr' => $abbr,
                            'name' => $doc_info['name'],
                            'exp_date' => $exp_date,
                            'badge_class' => $check_result['badge_class'],
                            'status' => $check_result['status'],
                            'days' => $check_result['days'] ?? null,
                            'immigration_letter' => $immigration_letter,
                            'tooltip' => $tooltip_text
                        );
                    }
                }
                
                ?>
                <tr class="<?php echo $class_hide; ?> driver-team-row" data-driver-id="<?php echo $row[ 'id' ]; ?>" style="background-color: rgba(220, 53, 69, 0.12) !important;">
                    <td>
                        <div class="d-flex flex-column align-items-start">
                        <span class="badge bg-primary">Team driver</span>
                        <div class="d-flex align-items-center gap-2">
                                <?php if ( ! empty( $team_driver_name ) ) : ?>
                                    <strong><?php echo esc_html( $team_driver_name ); ?></strong>
                                <?php endif; ?>
                            </div>
                            <?php if ( ! empty( $team_driver_phone ) ) : ?>
                                <span class="text-small">
                                    <?php echo esc_html( $team_driver_phone ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php
                        // Display expiration date for selected document type (team driver)
                        if ( ! empty( $selected_document_type ) ) {
                            $expiration_date = '';
                            $document_found = false;
                            
                            // Check if selected document type is for team driver (DL_TEAM, EA_TEAM, PR_TEAM, PS_TEAM)
                            $is_team_driver_doc = in_array( $selected_document_type, array( 'DL_TEAM', 'EA_TEAM', 'PR_TEAM', 'PS_TEAM' ) );
                            
                            if ( $is_team_driver_doc ) {
                                // Map team driver document types to abbreviations
                                $team_doc_map = array(
                                    'DL_TEAM' => 'DL',
                                    'EA_TEAM' => 'EA',
                                    'PR_TEAM' => 'PR',
                                    'PS_TEAM' => 'PS',
                                );
                                $abbr_to_find = $team_doc_map[ $selected_document_type ];
                                
                                // Find the document in team_driver_documents
                                foreach ( $team_driver_documents as $label_data ) {
                                    if ( $label_data['abbr'] === $abbr_to_find ) {
                                        $expiration_date = $label_data['exp_date'];
                                        $document_found = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ( $document_found && ! empty( $expiration_date ) ) {
                                echo '<span class="text-nowrap">' . esc_html( $expiration_date ) . '</span>';
                            } elseif ( $document_found ) {
                                echo '<span class="text-muted">No date</span>';
                            } else {
                                echo '<span class="text-muted">—</span>';
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Display team driver documents
                        if ( ! empty( $team_driver_documents ) ) {
                            echo '<div class="d-flex gap-1 flex-wrap align-items-center">';
                            foreach ( $team_driver_documents as $label_data ) {
                                // Special styling for missing documents
                                $badge_style = '';
                                if ( isset( $label_data['status'] ) && $label_data['status'] === 'missing' ) {
                                    $badge_style = 'style="font-size: 12px; padding: 6px 10px; cursor: help; opacity: 0.6; border: 1px dashed #6c757d;"';
                                } else {
                                    $badge_style = 'style="font-size: 12px; padding: 6px 10px; cursor: help;"';
                                }
                                
                                echo '<span class="badge ' . esc_attr( $label_data['badge_class'] ) . ' js-document-tooltip" 
                                      ' . $badge_style . '
                                      data-bs-toggle="tooltip" 
                                      data-bs-html="true"
                                      data-bs-placement="top"
                                      data-bs-custom-class="driver-document-tooltip"
                                      data-bs-title="' . esc_attr( $label_data['tooltip'] ) . '">';
                                
                                // Add visual indicator for missing documents
                                if ( isset( $label_data['status'] ) && $label_data['status'] === 'missing' ) {
                                    echo '<span style="text-decoration: line-through;">' . esc_html( $label_data['abbr'] ) . '</span>';
                                } else {
                                    echo esc_html( $label_data['abbr'] );
                                }
                                
                                echo '</span>';
                            }
                            echo '</div>';
                        } else {
                            echo '<span class="text-white" style="font-size: 11px;">No documents</span>';
                        }
                        ?>
                    </td>
                    <td></td>
                
                    <td></td>
                </tr>
                <?php
            }
            
            // Owner row
            if ( ! empty( $owner_enabled ) && $owner_enabled !== '0' && $owner_enabled !== 'false' 
                 && ( ! empty( $owner_name ) || ! empty( $owner_phone ) ) ) {
                $owner_types = array();
                if ( ! empty( $owner_van_proprietor ) && $owner_van_proprietor !== '0' && $owner_van_proprietor !== 'false' ) {
                    $owner_types[] = 'Van Proprietor';
                }
                if ( ! empty( $owner_operator ) && $owner_operator !== '0' && $owner_operator !== 'false' ) {
                    $owner_types[] = 'Owner Operator';
                }
                ?>
                <tr class="<?php echo $class_hide; ?> driver-owner-row" data-driver-id="<?php echo $row[ 'id' ]; ?>" style="background-color: rgba(25, 135, 84, 0.12) !important;">
                    <td>
                        <div class="d-flex flex-column align-items-start">
                        <span class="badge bg-dark">Owner<?php echo ! empty( $owner_types ) ? ' - ' . implode( ' / ', $owner_types ) : ''; ?></span>
                        <div class="d-flex align-items-center gap-2">
                                <?php if ( ! empty( $owner_name ) ) : ?>
                                    <strong><?php echo esc_html( $owner_name ); ?></strong>
                                <?php endif; ?>
                            </div>
                            <?php if ( ! empty( $owner_phone ) ) : ?>
                                <span class="text-small">
                                    <?php echo esc_html( $owner_phone ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php
                        // Owner doesn't have documents, so expiration date column is empty
                        ?>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    
                    <td></td>
                </tr>
                <?php
            }
            ?>
            
            <?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	
	
	echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>
	
	<?php get_template_part( TEMPLATE_PATH . 'popups/quick-status-update-modal' ); ?>
    <?php get_template_part( TEMPLATE_PATH . 'popups/driver-notice' ); ?>
    <?php get_template_part( TEMPLATE_PATH . 'popups/driver-raiting' ); ?>
    <?php get_template_part( TEMPLATE_PATH . 'popups/remove-driver', 'modal', array() );?>

<?php else : ?>
    <p>No drivers were found.</p>
<?php endif; ?>
