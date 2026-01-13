<?php
/**
 * Simple WordPress Cron for Auto-Updating Driver Status
 * Runs every 5 minutes to update driver status from 'available_on'/'loaded_enroute' to 'available'
 */

// Add custom cron interval for 5 minutes
add_filter( 'cron_schedules', 'tms_add_five_minutes_interval' );
function tms_add_five_minutes_interval( $schedules ) {
    $schedules['five_minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display'  => __( 'Every 5 Minutes' )
    );
    return $schedules;
}

// Schedule the cron event
add_action( 'init', 'tms_schedule_driver_status_cron' );
function tms_schedule_driver_status_cron() {
    if ( ! wp_next_scheduled( 'tms_auto_update_driver_status' ) ) {
        wp_schedule_event( time(), 'five_minutes', 'tms_auto_update_driver_status' );
    }
}

// The actual cron function
add_action( 'tms_auto_update_driver_status', 'tms_auto_update_driver_status_function' );
function tms_auto_update_driver_status_function() {
    global $wpdb;
    
    // Get current time in New York timezone
    $ny_timezone = new DateTimeZone( 'America/New_York' );
    $current_ny_time = new DateTime( 'now', $ny_timezone );
    $current_time_str = $current_ny_time->format( 'Y-m-d H:i:s' );
    
    // Find drivers with status 'available_on' or 'loaded_enroute' 
    // where date_available is less than current NY time
    $query = "
        SELECT 
            main.id,
            main.date_available,
            status.meta_value as driver_status
        FROM {$wpdb->prefix}drivers AS main
        LEFT JOIN {$wpdb->prefix}drivers_meta AS status 
            ON main.id = status.post_id 
            AND status.meta_key = 'driver_status'
        WHERE main.status_post = 'publish'
            AND status.meta_value IN ('available_on', 'loaded_enroute')
            AND main.date_available IS NOT NULL
            AND main.date_available != ''
            AND main.date_available < %s
        ORDER BY main.date_available ASC
    ";
    
    $results = $wpdb->get_results( 
        $wpdb->prepare( $query, $current_time_str ) 
    );
    
    if ( empty( $results ) ) {
        return;
    }
    
    $updated_count = 0;
    
    foreach ( $results as $driver ) {
        $driver_id = $driver->id;
        $old_status = $driver->driver_status;
        
        // Update driver status to 'available'
        $update_result = $wpdb->update(
            $wpdb->prefix . 'drivers_meta',
            array( 'meta_value' => 'available' ),
            array( 
                'post_id' => $driver_id,
                'meta_key' => 'driver_status'
            ),
            array( '%s' ),
            array( '%d', '%s' )
        );
        
        if ( $update_result !== false ) {
            // Update date_available to current New York time
            $ny_timezone = new DateTimeZone( 'America/New_York' );
            $ny_time = new DateTime( 'now', $ny_timezone );
            $current_time = $ny_time->format( 'Y-m-d H:i:s' );
            
            $wpdb->update(
                $wpdb->prefix . 'drivers',
                array( 
                    'date_available' => null,
                    'updated_zipcode' => $current_time
                ),
                array( 'id' => $driver_id ),
                array( null, '%s' ),
                array( '%d' )
            );
            
            $updated_count++;
            
            // Log the change
            error_log( "Auto-updated driver ID {$driver_id} status from '{$old_status}' to 'available' at " . date('Y-m-d H:i:s') );
        }
    }
    
    if ( $updated_count > 0 ) {
        error_log( "TMS Cron: Updated {$updated_count} drivers to 'available' status" );
    }
}

// Add custom cron interval for 12 hours
add_filter( 'cron_schedules', 'tms_add_twelve_hours_interval' );
function tms_add_twelve_hours_interval( $schedules ) {
    $schedules['twelve_hours'] = array(
        'interval' => 43200, // 12 hours in seconds
        'display'  => __( 'Every 12 Hours' )
    );
    return $schedules;
}

// Schedule the expired documents cron event
add_action( 'init', 'tms_schedule_expired_documents_cron' );
function tms_schedule_expired_documents_cron() {
    if ( ! wp_next_scheduled( 'tms_check_expired_documents' ) ) {
        wp_schedule_event( time(), 'twelve_hours', 'tms_check_expired_documents' );
    }
}

// The actual cron function for checking expired documents
add_action( 'tms_check_expired_documents', 'tms_check_expired_documents_function' );
function tms_check_expired_documents_function() {
    global $wpdb;
    
    // Get current date in New York timezone
    $ny_timezone = new DateTimeZone( 'America/New_York' );
    $current_ny_date = new DateTime( 'now', $ny_timezone );
    $today_str = $current_ny_date->format( 'Y-m-d' );
    $today_timestamp = $current_ny_date->getTimestamp();
    
    // Helper function to parse date and check if expired
    $is_date_expired = function( $date_string ) use ( $today_timestamp, $ny_timezone ) {
        if ( empty( $date_string ) ) {
            return false;
        }
        
        $date_string = trim( $date_string );
        $date_timestamp = false;
        
        // Priority 1: Parse American format (m/d/Y) explicitly - month/day/year
        // Example: 03/08/2026 = March 8, 2026 (not August 3!)
        if ( preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches ) ) {
            $month = (int) $matches[1];
            $day   = (int) $matches[2];
            $year  = (int) $matches[3];
            
            // Validate ranges and check if date is valid
            if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && $year >= 1900 && $year <= 2100 ) {
                // Use checkdate() to validate the actual date (handles invalid dates like 02/30/2026)
                if ( checkdate( $month, $day, $year ) ) {
                    try {
                        // Create date explicitly as year-month-day to avoid ambiguity
                        $date_obj = new DateTime( sprintf( '%04d-%02d-%02d', $year, $month, $day ), $ny_timezone );
                        $date_obj->setTime( 0, 0, 0 );
                        $date_timestamp = $date_obj->getTimestamp();
                    } catch ( Exception $e ) {
                        // Invalid date, continue to other formats
                    }
                }
            }
        }
        
        // Priority 2: Try other common date formats
        if ( $date_timestamp === false ) {
            $date_formats = array( 
                'Y-m-d',           // ISO format: 2026-03-08
                'Y-m-d H:i:s',     // ISO with time: 2026-03-08 12:00:00
                'm-d-Y',           // American with dashes: 03-08-2026
                'Y/m/d',            // ISO with slashes: 2026/03/08
                'd/m/Y',            // European format: 08/03/2026
                'd-m-Y',            // European with dashes: 08-03-2026
                'm/d/y',            // American 2-digit year: 03/08/26
            );
            
            foreach ( $date_formats as $format ) {
                $date_obj = DateTime::createFromFormat( $format, $date_string, $ny_timezone );
                if ( $date_obj !== false ) {
                    // Verify the parsed date matches the input format (strict validation)
                    $parsed_back = $date_obj->format( $format );
                    if ( $parsed_back === $date_string ) {
                        $date_obj->setTime( 0, 0, 0 );
                        $date_timestamp = $date_obj->getTimestamp();
                        break;
                    }
                }
            }
        }
        
        // Priority 3: Last fallback - use mktime for American format if still not parsed
        if ( $date_timestamp === false && preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches ) ) {
            $month = (int) $matches[1];
            $day   = (int) $matches[2];
            $year  = (int) $matches[3];
            
            if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate( $month, $day, $year ) ) {
                $timestamp = mktime( 0, 0, 0, $month, $day, $year );
                if ( $timestamp !== false ) {
                    try {
                        $date_obj = new DateTime( '@' . $timestamp, $ny_timezone );
                        $date_obj->setTime( 0, 0, 0 );
                        $date_timestamp = $date_obj->getTimestamp();
                    } catch ( Exception $e ) {
                        // Invalid date
                    }
                }
            }
        }
        
        // If still not parsed, return false (date is invalid or unparseable)
        if ( $date_timestamp === false ) {
            return false;
        }
        
        // Compare dates (only date part, ignore time)
        // Convert both to date-only timestamps for accurate comparison
        $date_only = strtotime( date( 'Y-m-d', $date_timestamp ) );
        $today_only = strtotime( date( 'Y-m-d', $today_timestamp ) );
        
        // Return true if expiration date is today or in the past
        return $date_only <= $today_only;
    };
    
    // Get all published drivers with their document meta values
    $query = "
        SELECT 
            main.id as driver_id,
            status.meta_value as driver_status,
            auto_liability_policy.meta_value as auto_liability_policy,
            auto_liability_expiration.meta_value as auto_liability_expiration
        FROM {$wpdb->prefix}drivers AS main
        LEFT JOIN {$wpdb->prefix}drivers_meta AS status 
            ON main.id = status.post_id AND status.meta_key = 'driver_status'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS auto_liability_policy 
            ON main.id = auto_liability_policy.post_id AND auto_liability_policy.meta_key = 'auto_liability_policy'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS auto_liability_expiration 
            ON main.id = auto_liability_expiration.post_id AND auto_liability_expiration.meta_key = 'auto_liability_expiration'
        WHERE main.status_post = 'publish'
            AND (status.meta_value IS NULL OR status.meta_value != 'expired_documents')
    ";
    
    $results = $wpdb->get_results( $query );
    
    if ( empty( $results ) ) {
        return;
    }
    
    $updated_count = 0;
    $expired_reasons = array();
    
    foreach ( $results as $driver ) {
        $driver_id = $driver->driver_id;
        $current_status = $driver->driver_status;
        $has_expired_doc = false;
        $reason = array();
        
        // Check auto_liability_policy and auto_liability_expiration
        if ( ! empty( $driver->auto_liability_policy ) && ! empty( $driver->auto_liability_expiration ) ) {
            if ( $is_date_expired( $driver->auto_liability_expiration ) ) {
                $has_expired_doc = true;
                $reason[] = 'auto_liability_expiration';
            }
        }
        
        // Update driver status if expired documents found
        if ( $has_expired_doc ) {
            $update_result = $wpdb->update(
                $wpdb->prefix . 'drivers_meta',
                array( 'meta_value' => 'expired_documents' ),
                array( 
                    'post_id' => $driver_id,
                    'meta_key' => 'driver_status'
                ),
                array( '%s' ),
                array( '%d', '%s' )
            );
            
            if ( $update_result !== false ) {
                $updated_count++;
                $expired_reasons[ $driver_id ] = array(
                    'old_status' => $current_status ?: 'unknown',
                    'reasons' => $reason
                );
                
                error_log( "TMS Cron: Driver ID {$driver_id} status updated to 'expired_documents' due to expired: " . implode( ', ', $reason ) );
            }
        }
    }
    
    if ( $updated_count > 0 ) {
        error_log( "TMS Cron: Updated {$updated_count} drivers to 'expired_documents' status" );
    }
}

// Check expired drivers with auto_liability_expiration OK but blocked by other documents
add_action( 'init', 'tms_check_expired_bag_function' );
function tms_check_expired_bag_function() {
    // Only run if check_exiperd_bag=1 is in URL
    if ( ! isset( $_GET['check_exiperd_bag'] ) || $_GET['check_exiperd_bag'] != '1' ) {
        return;
    }
    
    // Check if user is admin
    if ( ! current_user_can( 'administrator' ) ) {
        wp_die( 'Access denied. Administrator only.' );
    }
    
    global $wpdb;
    
    // Get current date in New York timezone
    $ny_timezone = new DateTimeZone( 'America/New_York' );
    $current_ny_date = new DateTime( 'now', $ny_timezone );
    $today_timestamp = $current_ny_date->getTimestamp();
    
    // Helper function to parse date and check if expired (same as in cron)
    $is_date_expired = function( $date_string ) use ( $today_timestamp, $ny_timezone ) {
        if ( empty( $date_string ) ) {
            return false;
        }
        
        $date_string = trim( $date_string );
        $date_timestamp = false;
        
        // Priority 1: Parse American format (m/d/Y)
        if ( preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches ) ) {
            $month = (int) $matches[1];
            $day   = (int) $matches[2];
            $year  = (int) $matches[3];
            
            if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && $year >= 1900 && $year <= 2100 ) {
                if ( checkdate( $month, $day, $year ) ) {
                    try {
                        $date_obj = new DateTime( sprintf( '%04d-%02d-%02d', $year, $month, $day ), $ny_timezone );
                        $date_obj->setTime( 0, 0, 0 );
                        $date_timestamp = $date_obj->getTimestamp();
                    } catch ( Exception $e ) {
                        // Invalid date
                    }
                }
            }
        }
        
        // Priority 2: Try other common date formats
        if ( $date_timestamp === false ) {
            $date_formats = array( 
                'Y-m-d',
                'Y-m-d H:i:s',
                'm-d-Y',
                'Y/m/d',
                'd/m/Y',
                'd-m-Y',
                'm/d/y',
            );
            
            foreach ( $date_formats as $format ) {
                $date_obj = DateTime::createFromFormat( $format, $date_string, $ny_timezone );
                if ( $date_obj !== false ) {
                    $parsed_back = $date_obj->format( $format );
                    if ( $parsed_back === $date_string ) {
                        $date_obj->setTime( 0, 0, 0 );
                        $date_timestamp = $date_obj->getTimestamp();
                        break;
                    }
                }
            }
        }
        
        // Priority 3: Last fallback
        if ( $date_timestamp === false && preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches ) ) {
            $month = (int) $matches[1];
            $day   = (int) $matches[2];
            $year  = (int) $matches[3];
            
            if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate( $month, $day, $year ) ) {
                $timestamp = mktime( 0, 0, 0, $month, $day, $year );
                if ( $timestamp !== false ) {
                    try {
                        $date_obj = new DateTime( '@' . $timestamp, $ny_timezone );
                        $date_obj->setTime( 0, 0, 0 );
                        $date_timestamp = $date_obj->getTimestamp();
                    } catch ( Exception $e ) {
                        // Invalid date
                    }
                }
            }
        }
        
        if ( $date_timestamp === false ) {
            return false;
        }
        
        $date_only = strtotime( date( 'Y-m-d', $date_timestamp ) );
        $today_only = strtotime( date( 'Y-m-d', $today_timestamp ) );
        
        return $date_only <= $today_only;
    };
    
    // Get all drivers with expired_documents status and check their dates
    $query = "
        SELECT 
            main.id as driver_id,
            status.meta_value as driver_status,
            auto_liability_policy.meta_value as auto_liability_policy,
            auto_liability_expiration.meta_value as auto_liability_expiration,
            martlet_coi_on.meta_value as martlet_coi_on,
            martlet_coi_expired_date.meta_value as martlet_coi_expired_date,
            endurance_coi_on.meta_value as endurance_coi_on,
            endurance_coi_expired_date.meta_value as endurance_coi_expired_date,
            driver_name.meta_value as driver_name
        FROM {$wpdb->prefix}drivers AS main
        LEFT JOIN {$wpdb->prefix}drivers_meta AS status 
            ON main.id = status.post_id AND status.meta_key = 'driver_status'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS auto_liability_policy 
            ON main.id = auto_liability_policy.post_id AND auto_liability_policy.meta_key = 'auto_liability_policy'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS auto_liability_expiration 
            ON main.id = auto_liability_expiration.post_id AND auto_liability_expiration.meta_key = 'auto_liability_expiration'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS martlet_coi_on 
            ON main.id = martlet_coi_on.post_id AND martlet_coi_on.meta_key = 'martlet_coi_on'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS martlet_coi_expired_date 
            ON main.id = martlet_coi_expired_date.post_id AND martlet_coi_expired_date.meta_key = 'martlet_coi_expired_date'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS endurance_coi_on 
            ON main.id = endurance_coi_on.post_id AND endurance_coi_on.meta_key = 'endurance_coi_on'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS endurance_coi_expired_date 
            ON main.id = endurance_coi_expired_date.post_id AND endurance_coi_expired_date.meta_key = 'endurance_coi_expired_date'
        LEFT JOIN {$wpdb->prefix}drivers_meta AS driver_name 
            ON main.id = driver_name.post_id AND driver_name.meta_key = 'driver_name'
        WHERE main.status_post = 'publish'
            AND status.meta_value = 'expired_documents'
        ORDER BY main.id ASC
    ";
    
    $results = $wpdb->get_results( $query );
    
    if ( empty( $results ) ) {
        echo '<h1>No drivers found with expired_documents status</h1>';
        exit;
    }
    
    $problematic_drivers = array();
    
    foreach ( $results as $driver ) {
        $driver_id = $driver->driver_id;
        $auto_liability_expired = false;
        $martlet_expired = false;
        $endurance_expired = false;
        
        // Check auto_liability_expiration
        if ( ! empty( $driver->auto_liability_policy ) && ! empty( $driver->auto_liability_expiration ) ) {
            $auto_liability_expired = $is_date_expired( $driver->auto_liability_expiration );
        }
        
        // Check martlet_coi_expired_date
        if ( ! empty( $driver->martlet_coi_on ) && strtolower( trim( $driver->martlet_coi_on ) ) === 'on' ) {
            if ( ! empty( $driver->martlet_coi_expired_date ) ) {
                $martlet_expired = $is_date_expired( $driver->martlet_coi_expired_date );
            }
        }
        
        // Check endurance_coi_expired_date
        if ( ! empty( $driver->endurance_coi_on ) && strtolower( trim( $driver->endurance_coi_on ) ) === 'on' ) {
            if ( ! empty( $driver->endurance_coi_expired_date ) ) {
                $endurance_expired = $is_date_expired( $driver->endurance_coi_expired_date );
            }
        }
        
        // If auto_liability_expiration is OK but driver is blocked, it means other dates were the problem
        if ( ! $auto_liability_expired ) {
            $problematic_drivers[] = array(
                'driver_id' => $driver_id,
                'driver_name' => $driver->driver_name ?: 'N/A',
                'auto_liability_expiration' => $driver->auto_liability_expiration ?: 'N/A',
                'auto_liability_expired' => $auto_liability_expired,
                'martlet_coi_on' => $driver->martlet_coi_on ?: 'N/A',
                'martlet_coi_expired_date' => $driver->martlet_coi_expired_date ?: 'N/A',
                'martlet_expired' => $martlet_expired,
                'endurance_coi_on' => $driver->endurance_coi_on ?: 'N/A',
                'endurance_coi_expired_date' => $driver->endurance_coi_expired_date ?: 'N/A',
                'endurance_expired' => $endurance_expired,
            );
        }
    }
    
    // Update statuses if update_status=1 parameter is present
    $update_status = isset( $_GET['update_status'] ) && $_GET['update_status'] == '1';
    $updated_drivers = array();
    
    if ( $update_status && ! empty( $problematic_drivers ) ) {
        foreach ( $problematic_drivers as $driver ) {
            $driver_id = $driver['driver_id'];
            
            $update_result = $wpdb->update(
                $wpdb->prefix . 'drivers_meta',
                array( 'meta_value' => 'available' ),
                array( 
                    'post_id' => $driver_id,
                    'meta_key' => 'driver_status'
                ),
                array( '%s' ),
                array( '%d', '%s' )
            );
            
            if ( $update_result !== false ) {
                $updated_drivers[] = $driver_id;
                error_log( "TMS Check: Driver ID {$driver_id} status updated from 'expired_documents' to 'available' (auto_liability_expiration OK)" );
            }
        }
    }
    
    // Output results
    header( 'Content-Type: text/html; charset=utf-8' );
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Expired Documents Check</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .expired { color: red; font-weight: bold; }
        .ok { color: green; }
        .count { font-size: 18px; font-weight: bold; margin: 20px 0; }
        .success { color: green; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .button:hover { background: #005a87; }
    </style></head><body>';
    
    echo '<h1>Drivers with expired_documents status but auto_liability_expiration OK</h1>';
    echo '<div class="count">Total found: ' . count( $problematic_drivers ) . '</div>';
    
    if ( $update_status ) {
        if ( ! empty( $updated_drivers ) ) {
            echo '<div class="success">Successfully updated ' . count( $updated_drivers ) . ' drivers from "expired_documents" to "available" status.</div>';
        } else {
            echo '<div class="success">No drivers were updated. Please check the list below.</div>';
        }
    } else {
        if ( ! empty( $problematic_drivers ) ) {
            $current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $update_url = add_query_arg( 'update_status', '1', $current_url );
            echo '<a href="' . esc_url( $update_url ) . '" class="button" onclick="return confirm(\'Are you sure you want to update ' . count( $problematic_drivers ) . ' drivers to available status?\');">Update All to Available Status</a>';
        }
    }
    
    if ( empty( $problematic_drivers ) ) {
        echo '<p>No problematic drivers found. All drivers with expired_documents have expired auto_liability_expiration.</p>';
    } else {
        echo '<table>';
        echo '<tr>
            <th>Driver ID</th>
            <th>Driver Name</th>
            <th>Auto Liability Expiration</th>
            <th>Auto Liability Status</th>
            <th>Martlet COI On</th>
            <th>Martlet Expiration</th>
            <th>Martlet Status</th>
            <th>Endurance COI On</th>
            <th>Endurance Expiration</th>
            <th>Endurance Status</th>
            ' . ( $update_status ? '<th>Update Status</th>' : '' ) . '
        </tr>';
        
        foreach ( $problematic_drivers as $driver ) {
            echo '<tr>';
            echo '<td>' . esc_html( $driver['driver_id'] ) . '</td>';
            echo '<td>' . esc_html( $driver['driver_name'] ) . '</td>';
            echo '<td>' . esc_html( $driver['auto_liability_expiration'] ) . '</td>';
            echo '<td class="' . ( $driver['auto_liability_expired'] ? 'expired' : 'ok' ) . '">' . ( $driver['auto_liability_expired'] ? 'EXPIRED' : 'OK' ) . '</td>';
            echo '<td>' . esc_html( $driver['martlet_coi_on'] ) . '</td>';
            echo '<td>' . esc_html( $driver['martlet_coi_expired_date'] ) . '</td>';
            echo '<td class="' . ( $driver['martlet_expired'] ? 'expired' : 'ok' ) . '">' . ( $driver['martlet_expired'] ? 'EXPIRED' : 'OK' ) . '</td>';
            echo '<td>' . esc_html( $driver['endurance_coi_on'] ) . '</td>';
            echo '<td>' . esc_html( $driver['endurance_coi_expired_date'] ) . '</td>';
            echo '<td class="' . ( $driver['endurance_expired'] ? 'expired' : 'ok' ) . '">' . ( $driver['endurance_expired'] ? 'EXPIRED' : 'OK' ) . '</td>';
            if ( $update_status ) {
                $status_text = in_array( $driver['driver_id'], $updated_drivers ) ? '<span class="ok">UPDATED</span>' : '<span class="expired">FAILED</span>';
                echo '<td>' . $status_text . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    echo '</body></html>';
    exit;
}
