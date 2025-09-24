<?php

/**
 * User Sync Hooks
 * 
 * WordPress hooks for automatic user synchronization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync user when created
 * 
 * @param int $user_id User ID
 */
function tms_sync_user_created($user_id) {
    // Get fresh user data
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        error_log("[TMS USER SYNC HOOK] ERROR: User {$user_id} not found during sync");
        return;
    }
    
    $user_roles = is_array($user->roles) ? $user->roles : array();
    $roles_string = !empty($user_roles) ? implode(', ', $user_roles) : 'none';
    error_log("[TMS USER SYNC HOOK] User created - ID: {$user_id}, Email: {$user->user_email}, Roles: {$roles_string}");
    
    // Initialize sync API
    $sync_api = new TMSUserSyncAPI();
    
    // Determine role based on user roles
    $role = 'employee'; // Default role
    if (is_array($user->roles) && in_array('driver', $user->roles)) {
        $role = 'driver';
    }
    
    error_log("[TMS USER SYNC HOOK] Determined role: {$role} for user {$user_id}");
    
    // Sync user
    $result = $sync_api->sync_user('add', $user, $role);
    
    // Log result
    if ($result['success']) {
        error_log("[TMS USER SYNC HOOK] Successfully synced new user {$user_id} ({$user->user_email}) with role {$role}");
    } else {
        error_log("[TMS USER SYNC HOOK] Failed to sync new user {$user_id} ({$user->user_email}): " . $result['error']);
    }
}
add_action('user_register', 'tms_sync_user_created_delayed', 10, 1);

/**
 * Delayed sync for newly created user
 * 
 * @param int $user_id User ID
 */
function tms_sync_user_created_delayed($user_id) {
    // Schedule the sync to run after WordPress has fully processed the user creation
    wp_schedule_single_event(time() + 5, 'tms_delayed_user_sync', array($user_id));
}
add_action('tms_delayed_user_sync', 'tms_sync_user_created', 10, 1);

/**
 * Sync user when updated
 * 
 * @param int $user_id User ID
 * @param WP_User $old_user_data Old user data
 */
function tms_sync_user_updated($user_id, $old_user_data) {
    // Get user data first to check if it's a new user
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        error_log("[TMS USER SYNC HOOK] ERROR: User {$user_id} not found after update");
        return;
    }
    
    // Skip if this is a new user (created within last 10 seconds)
    // This prevents double syncing for newly created users
    $user_registered = strtotime($user->user_registered);
    $current_time = time();
    
    // If user was created within last 10 seconds, skip this sync
    // The delayed sync will handle it properly
    if (($current_time - $user_registered) < 10) {
        error_log("[TMS USER SYNC HOOK] Skipping update sync for newly created user {$user_id} - will be handled by delayed sync");
        return;
    }
    
    error_log("[TMS USER SYNC HOOK] User updated - ID: {$user_id}");
    
    $user_roles = is_array($user->roles) ? $user->roles : array();
    $roles_string = !empty($user_roles) ? implode(', ', $user_roles) : 'none';
    error_log("[TMS USER SYNC HOOK] Updated user data - Email: {$user->user_email}, Roles: {$roles_string}");
    
    // Initialize sync API
    $sync_api = new TMSUserSyncAPI();
    
    // Determine role based on user roles
    $role = 'employee'; // Default role
    if (is_array($user->roles) && in_array('driver', $user->roles)) {
        $role = 'driver';
    }
    
    error_log("[TMS USER SYNC HOOK] Determined role: {$role} for updated user {$user_id}");
    
    // Sync user
    $result = $sync_api->sync_user('update', $user, $role);
    
    // Log result
    if ($result['success']) {
        error_log("[TMS USER SYNC HOOK] Successfully synced updated user {$user_id} ({$user->user_email}) with role {$role}");
    } else {
        error_log("[TMS USER SYNC HOOK] Failed to sync updated user {$user_id} ({$user->user_email}): " . $result['error']);
    }
}
add_action('profile_update', 'tms_sync_user_updated', 10, 2);

/**
 * Sync user when deleted
 * 
 * @param int $user_id User ID
 * @param int $reassign Reassign posts to this user ID
 * @param WP_User $user User object
 */
function tms_sync_user_deleted($user_id, $reassign, $user) {
    $user_roles = is_array($user->roles) ? $user->roles : array();
    $roles_string = !empty($user_roles) ? implode(', ', $user_roles) : 'none';
    error_log("[TMS USER SYNC HOOK] User deleted - ID: {$user_id}, Email: {$user->user_email}, Roles: {$roles_string}");
    
    // Initialize sync API
    $sync_api = new TMSUserSyncAPI();
    
    // Determine role based on user roles
    $role = 'employee'; // Default role
    if (is_array($user->roles) && in_array('driver', $user->roles)) {
        $role = 'driver';
    }
    
    error_log("[TMS USER SYNC HOOK] Determined role: {$role} for deleted user {$user_id}");
    
    // Sync user deletion
    $result = $sync_api->sync_user('delete', $user, $role);
    
    // Log result
    if ($result['success']) {
        error_log("[TMS USER SYNC HOOK] Successfully synced deleted user {$user_id} ({$user->user_email}) with role {$role}");
    } else {
        error_log("[TMS USER SYNC HOOK] Failed to sync deleted user {$user_id} ({$user->user_email}): " . $result['error']);
    }
}
add_action('delete_user', 'tms_sync_user_deleted', 10, 3);

/**
 * Sync user when role is changed
 * 
 * @param int $user_id User ID
 * @param string $role New role
 * @param array $old_roles Old roles
 */
function tms_sync_user_role_changed($user_id, $role, $old_roles) {
    // Get user data first to check if it's a new user
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        error_log("[TMS USER SYNC HOOK] ERROR: User {$user_id} not found after role change");
        return;
    }
    
    // Skip if this is a new user (created within last 10 seconds)
    // This prevents double syncing for newly created users
    $user_registered = strtotime($user->user_registered);
    $current_time = time();
    
    // If user was created within last 10 seconds, skip this sync
    // The delayed sync will handle it properly
    if (($current_time - $user_registered) < 10) {
        error_log("[TMS USER SYNC HOOK] Skipping role change sync for newly created user {$user_id} - will be handled by delayed sync");
        return;
    }
    
    error_log("[TMS USER SYNC HOOK] User role changed - ID: {$user_id}, New role: {$role}, Old roles: " . implode(', ', $old_roles));
    
    $user_roles = is_array($user->roles) ? $user->roles : array();
    $roles_string = !empty($user_roles) ? implode(', ', $user_roles) : 'none';
    error_log("[TMS USER SYNC HOOK] User data after role change - Email: {$user->user_email}, Current roles: {$roles_string}");
    
    // Initialize sync API
    $sync_api = new TMSUserSyncAPI();
    
    // Determine role for sync
    $sync_role = 'employee'; // Default role
    if ($role === 'driver' || (is_array($user->roles) && in_array('driver', $user->roles))) {
        $sync_role = 'driver';
    }
    
    error_log("[TMS USER SYNC HOOK] Determined sync role: {$sync_role} for user {$user_id} after role change");
    
    // Sync user with new role
    $result = $sync_api->sync_user('update', $user, $sync_role);
    
    // Log result
    if ($result['success']) {
        error_log("[TMS USER SYNC HOOK] Successfully synced role change for user {$user_id} ({$user->user_email}) - new role: {$role}, sync role: {$sync_role}");
    } else {
        error_log("[TMS USER SYNC HOOK] Failed to sync role change for user {$user_id} ({$user->user_email}): " . $result['error']);
    }
}
add_action('set_user_role', 'tms_sync_user_role_changed', 10, 3);

/**
 * Manual sync function for testing
 * 
 * @param int $user_id User ID
 * @param string $type Operation type: 'add', 'update', 'delete'
 * @return array Result
 */
function tms_manual_sync_user($user_id, $type = 'update') {
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return array(
            'success' => false,
            'error' => 'User not found'
        );
    }
    
    $sync_api = new TMSUserSyncAPI();
    
    // Determine role
    $role = 'employee';
    if (in_array('driver', $user->roles)) {
        $role = 'driver';
    }
    
    return $sync_api->sync_user($type, $user, $role);
}
