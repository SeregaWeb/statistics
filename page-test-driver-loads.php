<?php
/**
 * Template Name: Test Driver Loads
 * 
 * Test page to view all loads for a driver
 */

get_header();

// Get driver ID from URL parameter
$driver_id = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;

// Initialize TMS Drivers class
if (!class_exists('TMS_Drivers')) {
    require_once get_template_directory() . '/src/inc/core/class-tms-drivers.php';
}

$tms_drivers = new TMS_Drivers();

// Get driver name
$driver_name = 'Unknown';
if ($driver_id) {
    $driver_meta = get_post_meta($driver_id);
    $driver_name_meta = isset($driver_meta['driver_name'][0]) ? $driver_meta['driver_name'][0] : '';
    if (empty($driver_name_meta)) {
        global $wpdb;
        $drivers_meta_table = $wpdb->prefix . 'drivers_meta';
        $driver_name_result = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM $drivers_meta_table WHERE post_id = %d AND meta_key = 'driver_name' LIMIT 1",
            $driver_id
        ));
        $driver_name = $driver_name_result ? $driver_name_result : "Driver ID: $driver_id";
    } else {
        $driver_name = $driver_name_meta;
    }
}

// Get all loads
$all_loads = $driver_id ? $tms_drivers->get_driver_loads($driver_id) : array();

// Get available loads for rating
$available_loads = $driver_id ? $tms_drivers->get_available_loads_for_rating($driver_id) : array();

// Get existing ratings
$existing_ratings = $driver_id ? $tms_drivers->get_user_ratings_for_driver($driver_id) : array();

// Get rated load numbers
$rated_load_numbers = array();
foreach ($existing_ratings as $rating) {
    if (!empty($rating['order_number'])) {
        $rated_load_numbers[] = $rating['order_number'];
    }
}

?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1>Test Driver Loads</h1>
            
            <div class="mb-4">
                <form method="GET" class="d-inline-flex gap-2">
                    <input type="hidden" name="p" value="<?php echo get_the_ID(); ?>">
                    <label for="driver_id" class="form-label mb-0 align-self-center">Driver ID:</label>
                    <input type="number" 
                           id="driver_id" 
                           name="driver_id" 
                           value="<?php echo esc_attr($driver_id); ?>" 
                           class="form-control" 
                           style="width: 150px;">
                    <button type="submit" class="btn btn-primary">Load</button>
                </form>
            </div>

            <?php if ($driver_id): ?>
                <div class="alert alert-info">
                    <h5><?php echo esc_html($driver_name); ?></h5>
                    <p class="mb-0">Driver ID: <?php echo $driver_id; ?></p>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Loads</h5>
                                <h2><?php echo count($all_loads); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Available for Rating</h5>
                                <h2><?php echo count($available_loads); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Already Rated</h5>
                                <h2><?php echo count($existing_ratings); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h3>All Loads (<?php echo count($all_loads); ?>)</h3>
                    <div class="alert alert-info">
                        <small>
                            <strong>Note:</strong> Loads are filtered by date >= 2025-10-01 and checked for both attached_driver and attached_second_driver.
                            Dispatchers see only their loads.
                        </small>
                    </div>
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Load Number (reference_number)</th>
                                <th>Load ID</th>
                                <th>Type</th>
                                <th>Date Created</th>
                                <th>Status</th>
                                <th>Has Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_loads)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No loads found</td>
                                </tr>
                            <?php else: ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($all_loads as $load): ?>
                                    <?php 
                                    $is_rated = in_array($load['load_number'], $rated_load_numbers);
                                    $is_available = false;
                                    foreach ($available_loads as $avail_load) {
                                        if ($avail_load['load_number'] === $load['load_number']) {
                                            $is_available = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr class="<?php echo $is_rated ? 'table-warning' : ($is_available ? 'table-success' : ''); ?>">
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <strong><?php echo esc_html($load['load_number'] ?: 'N/A'); ?></strong>
                                            <?php if (empty($load['load_number'])): ?>
                                                <span class="badge bg-danger">Missing reference_number</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($load['id']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $load['load_type'] === 'flt' ? 'info' : 'primary'; ?>">
                                                <?php echo esc_html(strtoupper($load['load_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($load['date_created']); ?></td>
                                        <td>
                                            <?php if ($is_rated): ?>
                                                <span class="badge bg-warning">Rated</span>
                                            <?php elseif ($is_available): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $is_rated ? 'Yes' : 'No'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    <h3>Available Loads for Rating (<?php echo count($available_loads); ?>)</h3>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Load Number</th>
                                <th>Load ID</th>
                                <th>Type</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($available_loads)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No available loads for rating</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($available_loads as $load): ?>
                                    <tr>
                                        <td><?php echo esc_html($load['load_number'] ?: 'N/A'); ?></td>
                                        <td><?php echo esc_html($load['id']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $load['load_type'] === 'flt' ? 'info' : 'primary'; ?>">
                                                <?php echo esc_html(strtoupper($load['load_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($load['date_created']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    <h3>Already Rated Loads (<?php echo count($existing_ratings); ?>)</h3>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Load Number</th>
                                <th>Rating</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($existing_ratings)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No ratings yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($existing_ratings as $rating): ?>
                                    <tr>
                                        <td><?php echo esc_html($rating['order_number'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $rating['reit'] >= 4 ? 'success' : 
                                                    ($rating['reit'] >= 3 ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo esc_html($rating['reit']); ?>/5
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($rating['message'] ?: '-'); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', $rating['time']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    <h3>Debug Information</h3>
                    <div class="card">
                        <div class="card-body">
                            <h5>Missing Loads Analysis</h5>
                            <?php
                            // Find loads that are in all_loads but not in available_loads
                            $all_load_numbers = array_column($all_loads, 'load_number');
                            $available_load_numbers = array_column($available_loads, 'load_number');
                            $missing_loads = array_diff($all_load_numbers, $available_load_numbers);
                            
                            echo '<p><strong>Loads in "All Loads" but NOT in "Available Loads":</strong> ' . count($missing_loads) . '</p>';
                            
                            if (!empty($missing_loads)) {
                                echo '<ul>';
                                foreach ($missing_loads as $load_num) {
                                    $reason = in_array($load_num, $rated_load_numbers) ? ' (Already rated)' : ' (Unknown reason)';
                                    echo '<li>' . esc_html($load_num ?: 'EMPTY') . $reason . '</li>';
                                }
                                echo '</ul>';
                            }
                            
                            // Additional debug info
                            echo '<hr>';
                            echo '<h6>Query Details:</h6>';
                            echo '<ul>';
                            echo '<li>Current User ID: ' . get_current_user_id() . '</li>';
                            $user = wp_get_current_user();
                            echo '<li>Current User Roles: ' . implode(', ', $user->roles) . '</li>';
                            $current_project = get_field('current_select', 'user_' . get_current_user_id());
                            echo '<li>Current Project: ' . ($current_project ?: 'odysseia (fallback)') . '</li>';
                            
                            global $wpdb;
                            $reports_table = $wpdb->prefix . 'reports_' . strtolower($current_project ?: 'odysseia');
                            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$reports_table'");
                            echo '<li>Regular Reports Table: ' . ($table_exists ? $reports_table . ' (exists)' : $reports_table . ' (NOT exists)') . '</li>';
                            
                            $flt_table = $wpdb->prefix . 'reports_flt_' . strtolower($current_project ?: 'odysseia');
                            $flt_exists = $wpdb->get_var("SHOW TABLES LIKE '$flt_table'");
                            echo '<li>FLT Reports Table: ' . ($flt_exists ? $flt_table . ' (exists)' : $flt_table . ' (NOT exists)') . '</li>';
                            
                            $is_dispatcher = in_array('dispatcher', $user->roles) || in_array('dispatcher-tl', $user->roles);
                            echo '<li>Is Dispatcher: ' . ($is_dispatcher ? 'Yes' : 'No') . '</li>';
                            
                            $is_admin = current_user_can('administrator');
                            $access_flt = get_field('flt', 'user_' . get_current_user_id());
                            $has_flt_access = $is_admin || $access_flt;
                            echo '<li>Has FLT Access: ' . ($has_flt_access ? 'Yes' : 'No') . '</li>';
                            echo '</ul>';
                            
                            // Show load counts by type
                            $regular_count = 0;
                            $flt_count = 0;
                            foreach ($all_loads as $load) {
                                if ($load['load_type'] === 'flt') {
                                    $flt_count++;
                                } else {
                                    $regular_count++;
                                }
                            }
                            echo '<hr>';
                            echo '<h6>Load Breakdown:</h6>';
                            echo '<ul>';
                            echo '<li>Regular Loads: ' . $regular_count . '</li>';
                            echo '<li>FLT Loads: ' . $flt_count . '</li>';
                            echo '</ul>';
                            ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">
                    Please enter a Driver ID to view loads.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>

