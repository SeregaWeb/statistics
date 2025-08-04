<?php
/**
 * Admin Cache Manager for TMS Drivers
 * 
 * Provides administrative interface for managing drivers cache
 * 
 * @package WP-rock
 * @since 1.0.0
 */

/**
 * Admin Cache Manager Class
 */
class TMS_Admin_Cache_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_cache_actions' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Drivers Cache Manager',
            'Drivers Cache',
            'manage_options',
            'tms-drivers-cache',
            array( $this, 'admin_page' )
        );
    }

    /**
     * Handle cache actions
     */
    public function handle_cache_actions() {
        if ( ! isset( $_POST['tms_cache_action'] ) || ! wp_verify_nonce( $_POST['tms_cache_nonce'], 'tms_cache_action' ) ) {
            return;
        }

        $action = sanitize_text_field( $_POST['tms_cache_action'] );
        $tms_drivers = new TMSDrivers();

        switch ( $action ) {
            case 'clear_drivers_cache':
                $result = $tms_drivers->clear_drivers_cache();
                if ( $result ) {
                    $this->add_notice( 'Drivers cache cleared successfully!', 'success' );
                } else {
                    $this->add_notice( 'Failed to clear drivers cache.', 'error' );
                }
                break;

            case 'clear_coordinates_cache':
                $result = $tms_drivers->clear_coordinates_cache();
                if ( $result ) {
                    $this->add_notice( 'Coordinates cache cleared successfully!', 'success' );
                } else {
                    $this->add_notice( 'Failed to clear coordinates cache.', 'error' );
                }
                break;

            case 'clear_all_cache':
                $result1 = $tms_drivers->clear_drivers_cache();
                $result2 = $tms_drivers->clear_coordinates_cache();
                if ( $result1 && $result2 ) {
                    $this->add_notice( 'All cache cleared successfully!', 'success' );
                } else {
                    $this->add_notice( 'Failed to clear some cache items.', 'error' );
                }
                break;

            case 'refresh_drivers_cache':
                // Clear existing cache and force refresh
                $tms_drivers->clear_drivers_cache();
                $drivers = $tms_drivers->get_all_available_driver( true );
                if ( ! empty( $drivers ) ) {
                    set_transient( 'tms_all_available_drivers', $drivers, 5 * MINUTE_IN_SECONDS );
                    $this->add_notice( 'Drivers cache refreshed with ' . count( $drivers ) . ' drivers!', 'success' );
                } else {
                    $this->add_notice( 'No drivers found to cache.', 'warning' );
                }
                break;

            case 'fix_hold_status_issues':
                $result = $this->fix_hold_status_issues();
                if ( $result['success'] ) {
                    $this->add_notice( 'Fixed ' . $result['fixed_count'] . ' hold status issues!', 'success' );
                } else {
                    $this->add_notice( 'Failed to fix hold status issues: ' . $result['error'], 'error' );
                }
                break;
        }
    }

    /**
     * Add admin notice
     */
    private function add_notice( $message, $type = 'info' ) {
        $notices = get_option( 'tms_cache_notices', array() );
        $notices[] = array(
            'message' => $message,
            'type' => $type,
            'time' => current_time( 'timestamp' )
        );
        update_option( 'tms_cache_notices', $notices );
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'tms-drivers-cache' ) {
            return;
        }

        $notices = get_option( 'tms_cache_notices', array() );
        if ( empty( $notices ) ) {
            return;
        }

        foreach ( $notices as $notice ) {
            $class = 'notice notice-' . $notice['type'];
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) );
        }

        // Clear notices after displaying
        delete_option( 'tms_cache_notices' );
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        $tms_drivers = new TMSDrivers();
        
        // Get cache status
        $drivers_cache = get_transient( 'tms_all_available_drivers' );
        $drivers_cache_exists = $drivers_cache !== false;
        $drivers_count = $drivers_cache_exists ? count( $drivers_cache ) : 0;

        // Get cache info
        $cache_info = $this->get_cache_info();
        ?>
        <div class="wrap">
            <h1>Drivers Cache Manager</h1>
            
            <div class="card">
                <h2>Cache Status</h2>
                <table class="form-table">
                    <tr>
                        <th>Drivers Cache:</th>
                        <td>
                            <?php if ( $drivers_cache_exists ): ?>
                                <span style="color: green;">✓ Active</span> (<?php echo $drivers_count; ?> drivers)
                            <?php else: ?>
                                <span style="color: red;">✗ Not cached</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Cache Duration:</th>
                        <td>5 minutes</td>
                    </tr>
                    <tr>
                        <th>Last Updated:</th>
                        <td><?php echo $cache_info['last_updated']; ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Cache Actions</h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'tms_cache_action', 'tms_cache_nonce' ); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th>Clear Drivers Cache:</th>
                            <td>
                                <input type="hidden" name="tms_cache_action" value="clear_drivers_cache">
                                <input type="submit" class="button button-secondary" value="Clear Drivers Cache">
                                <p class="description">Removes cached driver data. New data will be fetched on next request.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Refresh Drivers Cache:</th>
                            <td>
                                <input type="hidden" name="tms_cache_action" value="refresh_drivers_cache">
                                <input type="submit" class="button button-primary" value="Refresh Drivers Cache">
                                <p class="description">Clears and immediately rebuilds the drivers cache with fresh data.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Clear Coordinates Cache:</th>
                            <td>
                                <input type="hidden" name="tms_cache_action" value="clear_coordinates_cache">
                                <input type="submit" class="button button-secondary" value="Clear Coordinates Cache">
                                <p class="description">Removes cached geocoding data.</p>
                            </td>
                        </tr>
                        						<tr>
							<th>Clear All Cache:</th>
							<td>
								<input type="hidden" name="tms_cache_action" value="clear_all_cache">
								<input type="submit" class="button button-secondary" value="Clear All Cache">
								<p class="description">Removes all cached data (drivers and coordinates).</p>
							</td>
						</tr>
						<tr>
							<th>Fix Hold Status Issues:</th>
							<td>
								<input type="hidden" name="tms_cache_action" value="fix_hold_status_issues">
								<input type="submit" class="button button-primary" value="Fix Hold Status Issues" style="background: #d63638; border-color: #d63638;">
								<p class="description">Fixes orphaned on_hold drivers and problematic hold records.</p>
							</td>
						</tr>
                    </table>
                </form>
            </div>

            <div class="card">
                <h2>Cache Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Total Drivers in DB:</th>
                        <td><?php echo $cache_info['total_drivers']; ?></td>
                    </tr>
                    <tr>
                        <th>Available Drivers:</th>
                        <td><?php echo $cache_info['available_drivers']; ?></td>
                    </tr>
                    <tr>
                        <th>On Hold Drivers:</th>
                        <td><?php echo $cache_info['on_hold_drivers']; ?></td>
                    </tr>
                    <tr>
                        <th>Cache Size:</th>
                        <td><?php echo $cache_info['cache_size']; ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Cache Troubleshooting</h2>
                <p><strong>Problem:</strong> You see outdated driver status (e.g., "on_hold" for drivers that are no longer in the database)</p>
                <p><strong>Solution:</strong> Click "Clear All Cache" above to force a fresh data fetch.</p>
                
                <p><strong>Problem:</strong> Cache is not updating automatically when driver status changes</p>
                <p><strong>Solution:</strong> This should be fixed now. If the problem persists, check if the TMSDrivers class is properly loaded.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Get cache information
     */
    private function get_cache_info() {
        global $wpdb;
        
        $tms_drivers = new TMSDrivers();
        $table_main = $wpdb->prefix . $tms_drivers->table_main;
        $table_meta = $wpdb->prefix . $tms_drivers->table_meta;

        // Get total drivers
        $total_drivers = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_main}" );

        // Get available drivers
        $available_drivers = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(DISTINCT main.id) 
            FROM {$table_main} main
            LEFT JOIN {$table_meta} status ON main.id = status.post_id AND status.meta_key = 'driver_status'
            WHERE status.meta_value IN ('available', 'available_on', 'loaded_enroute')
        " ) );

        // Get on hold drivers
        $on_hold_drivers = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(DISTINCT main.id) 
            FROM {$table_main} main
            LEFT JOIN {$table_meta} status ON main.id = status.post_id AND status.meta_key = 'driver_status'
            WHERE status.meta_value = 'on_hold'
        " ) );

        // Get cache size
        $drivers_cache = get_transient( 'tms_all_available_drivers' );
        $cache_size = $drivers_cache !== false ? strlen( serialize( $drivers_cache ) ) : 0;
        $cache_size_formatted = size_format( $cache_size );

        // Get last updated
        $last_updated = 'Never';
        if ( $drivers_cache !== false ) {
            $transient_timeout = get_option( '_transient_timeout_tms_all_available_drivers' );
            if ( $transient_timeout ) {
                $last_updated = date( 'Y-m-d H:i:s', $transient_timeout - ( 5 * MINUTE_IN_SECONDS ) );
            }
        }

        		return array(
			'total_drivers' => $total_drivers ?: 0,
			'available_drivers' => $available_drivers ?: 0,
			'on_hold_drivers' => $on_hold_drivers ?: 0,
			'cache_size' => $cache_size_formatted,
			'last_updated' => $last_updated
		);
	}

	/**
	 * Fix hold status issues in database
	 */
	private function fix_hold_status_issues() {
		global $wpdb;
		
		try {
			$tms_drivers = new TMSDrivers();
			$table_main = $wpdb->prefix . $tms_drivers->table_main;
			$table_meta = $wpdb->prefix . $tms_drivers->table_meta;
			$table_hold = $wpdb->prefix . 'driver_hold_status';
			
			$fixed_count = 0;
			
			// 1. Fix drivers with on_hold status but no hold record
			$orphaned_hold_drivers = $wpdb->get_results( "
				SELECT DISTINCT main.id 
				FROM {$table_main} main
				LEFT JOIN {$table_meta} status ON main.id = status.post_id AND status.meta_key = 'driver_status'
				LEFT JOIN {$table_hold} hold ON main.id = hold.driver_id
				WHERE status.meta_value = 'on_hold' 
				AND hold.id IS NULL
			" );
			
			foreach ( $orphaned_hold_drivers as $driver ) {
				$wpdb->update( 
					$table_meta, 
					array( 'meta_value' => 'available' ), 
					array( 'post_id' => $driver->id, 'meta_key' => 'driver_status' ),
					array( '%s' ),
					array( '%d', '%s' )
				);
				$fixed_count++;
			}
			
			// 2. Fix hold records with on_hold status
			$problematic_holds = $wpdb->get_results( "
				SELECT * FROM {$table_hold}
				WHERE driver_status = 'on_hold'
			" );
			
			foreach ( $problematic_holds as $hold ) {
				$wpdb->update( 
					$table_hold, 
					array( 'driver_status' => 'available' ), 
					array( 'id' => $hold->id ),
					array( '%s' ),
					array( '%d' )
				);
				$fixed_count++;
			}
			
			// 3. Fix expired holds that weren't cleaned up
			$expired_holds = $wpdb->get_results( "
				SELECT * FROM {$table_hold}
				WHERE update_date < NOW()
			" );
			
			foreach ( $expired_holds as $hold ) {
				// Restore driver status
				$restore_status = $hold->driver_status ?: 'available';
				if ( $restore_status === 'on_hold' ) {
					$restore_status = 'available';
				}
				
				$wpdb->update( 
					$table_meta, 
					array( 'meta_value' => $restore_status ), 
					array( 'post_id' => $hold->driver_id, 'meta_key' => 'driver_status' ),
					array( '%s' ),
					array( '%d', '%s' )
				);
				
				// Delete expired hold record
				$wpdb->delete( $table_hold, array( 'id' => $hold->id ) );
				$fixed_count++;
			}
			
			// Clear cache after fixes
			$tms_drivers->clear_drivers_cache();
			
			return array(
				'success' => true,
				'fixed_count' => $fixed_count
			);
			
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'error' => $e->getMessage()
			);
		}
	}
}

// Initialize the admin cache manager
new TMS_Admin_Cache_Manager(); 