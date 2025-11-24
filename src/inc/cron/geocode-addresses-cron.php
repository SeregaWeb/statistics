<?php
/**
 * Cron job for automatic geocoding of addresses
 * Runs once per day to process new addresses that don't have coordinates or timezone
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register cron event for geocoding addresses
 */
function tms_geocode_addresses_cron_init() {
    if (!wp_next_scheduled('tms_geocode_addresses_cron')) {
        // Schedule to run once per day at 2 AM
        wp_schedule_event(strtotime('tomorrow 2:00'), 'daily', 'tms_geocode_addresses_cron');
    }
}

/**
 * Cron callback function to geocode addresses
 */
function tms_geocode_addresses_cron_callback() {
    if (!class_exists('TMSGeocodeAddresses')) {
        error_log('TMSGeocodeAddresses: Class not found');
        return;
    }
    
    $geocode = new TMSGeocodeAddresses();
    
    // Process shippers first (100 records per batch for faster processing)
    $shipper_stats = $geocode->geocode_shippers(100);
    
    // Log results
    if ($shipper_stats['total'] > 0) {
        error_log(sprintf(
            'TMSGeocodeAddresses Cron: Shippers - Total: %d, Success: %d, Failed: %d',
            $shipper_stats['total'],
            $shipper_stats['success'],
            $shipper_stats['failed']
        ));
    }
    
    // Process companies (100 records per batch for faster processing)
    $company_stats = $geocode->geocode_companies(100);
    
    // Log results
    if ($company_stats['total'] > 0) {
        error_log(sprintf(
            'TMSGeocodeAddresses Cron: Companies - Total: %d, Success: %d, Failed: %d',
            $company_stats['total'],
            $company_stats['success'],
            $company_stats['failed']
        ));
    }
}

// Register hooks
add_action('tms_geocode_addresses_cron', 'tms_geocode_addresses_cron_callback');

// Initialize cron on admin init
add_action('admin_init', 'tms_geocode_addresses_cron_init');

// Also initialize on wp_loaded for frontend
add_action('wp_loaded', 'tms_geocode_addresses_cron_init');

