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
    $is_date_expired = function( $date_string ) use ( $today_timestamp ) {
        if ( empty( $date_string ) ) {
            return false;
        }
        
        // Try different date formats
        $date_formats = array( 'm/d/Y', 'Y-m-d', 'm-d-Y', 'Y/m/d', 'm/d/y', 'Y-m-d H:i:s' );
        $date_timestamp = false;
        
        foreach ( $date_formats as $format ) {
            $date_obj = DateTime::createFromFormat( $format, trim( $date_string ) );
            if ( $date_obj !== false ) {
                $date_timestamp = $date_obj->getTimestamp();
                break;
            }
        }
        
        // Fallback to strtotime
        if ( $date_timestamp === false ) {
            $date_timestamp = strtotime( str_replace( '/', '-', trim( $date_string ) ) );
        }
        
        if ( ! $date_timestamp || $date_timestamp === false ) {
            return false;
        }
        
        // Compare dates (only date part, ignore time)
        $date_only = strtotime( date( 'Y-m-d', $date_timestamp ) );
        $today_only = strtotime( date( 'Y-m-d', $today_timestamp ) );
        
        return $date_only <= $today_only;
    };
    
    // Get all published drivers with their document meta values
    $query = "
        SELECT 
            main.id as driver_id,
            status.meta_value as driver_status,
            auto_liability_policy.meta_value as auto_liability_policy,
            auto_liability_expiration.meta_value as auto_liability_expiration,
            martlet_coi_on.meta_value as martlet_coi_on,
            martlet_coi_expired_date.meta_value as martlet_coi_expired_date,
            endurance_coi_on.meta_value as endurance_coi_on,
            endurance_coi_expired_date.meta_value as endurance_coi_expired_date
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
        
        // Check martlet_coi_on and martlet_coi_expired_date
        if ( ! empty( $driver->martlet_coi_on ) && strtolower( trim( $driver->martlet_coi_on ) ) === 'on' ) {
            if ( ! empty( $driver->martlet_coi_expired_date ) && $is_date_expired( $driver->martlet_coi_expired_date ) ) {
                $has_expired_doc = true;
                $reason[] = 'martlet_coi_expired_date';
            }
        }
        
        // Check endurance_coi_on and endurance_coi_expired_date
        if ( ! empty( $driver->endurance_coi_on ) && strtolower( trim( $driver->endurance_coi_on ) ) === 'on' ) {
            if ( ! empty( $driver->endurance_coi_expired_date ) && $is_date_expired( $driver->endurance_coi_expired_date ) ) {
                $has_expired_doc = true;
                $reason[] = 'endurance_coi_expired_date';
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
