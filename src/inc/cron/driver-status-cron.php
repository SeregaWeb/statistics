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
