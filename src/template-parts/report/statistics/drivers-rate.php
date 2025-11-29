<?php
/**
 * Template for displaying top drivers by performance
 * Criteria: Rating >= 4, sorted by profit, then by delivered loads count
 * Shows recruiter initials instead of driver initials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure WordPress is fully loaded
if (!function_exists('get_current_user_id')) {
    echo '<div class="col-12 alert alert-danger">WordPress not fully loaded. Please refresh the page.</div>';
    return;
}

// Check access - restrict tracking roles
if (!class_exists('TMSUsers')) {
    echo '<div class="col-12 alert alert-danger">Access denied. TMSUsers class not found.</div>';
    return;
}

$TMSUsers = new TMSUsers();

// Check if user has restricted role (tracking roles)
$restricted_roles = array(
    'tracking',
    'tracking-tl',
    'morning_tracking',
    'nightshift_tracking',
);

// If user has any of the restricted roles, deny access
if (!$TMSUsers->check_user_role_access($restricted_roles, false)) {
    echo '<div class="col-12 alert alert-danger">Access denied. You are not allowed to access this page.</div>';
    return;
}

// Check if TMS_Drivers class exists, if not try to load it
if (!class_exists('TMSDrivers')) {
     echo '<div class="alert alert-danger">TMS_Drivers class not found. Please check if the plugin is properly loaded.</div>';
     return;
 }

global $global_options;

// Get TMS_Drivers instance
try {
    $tms_drivers = new TMSDrivers();
    // Check if methods exist
    if (!method_exists($tms_drivers, 'get_top_drivers')) {
        echo '<div class="alert alert-danger">Method get_top_drivers not found in TMS_Drivers class.</div>';
        return;
    }
    if (!method_exists($tms_drivers, 'get_top_drivers_by_ratings')) {
        echo '<div class="alert alert-danger">Method get_top_drivers_by_ratings not found in TMS_Drivers class.</div>';
        return;
    }
    
    $top_drivers_by_performance = $tms_drivers->get_top_drivers(25);
    $top_drivers_by_ratings = $tms_drivers->get_top_drivers_by_ratings(25, 5);
    
    // Ensure we have arrays
    if (!is_array($top_drivers_by_performance)) {
        $top_drivers_by_performance = [];
    }
    if (!is_array($top_drivers_by_ratings)) {
        $top_drivers_by_ratings = [];
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading driver statistics: ' . esc_html($e->getMessage()) . '</div>';
    return;
} catch (Error $e) {
    echo '<div class="alert alert-danger">Fatal error loading driver statistics: ' . esc_html($e->getMessage()) . '</div>';
    return;
}

// Get driver page URL
$add_new_driver_url = get_field_value($global_options, 'add_new_driver');

// Helper function to get recruiter info
function get_recruiter_info($recruiter_id) {
    if (!$recruiter_id) {
        return ['initials' => 'NF', 'color' => '#030303', 'name' => 'User not found'];
    }
    
    $user_recruiter = get_user_by('id', $recruiter_id);
    if (!$user_recruiter) {
        return ['initials' => 'NF', 'color' => '#030303', 'name' => 'User not found'];
    }
    
    $full_name = trim($user_recruiter->first_name . ' ' . $user_recruiter->last_name);
    if (empty($full_name)) {
        $full_name = $user_recruiter->display_name;
    }
    
    $initials = '';
    $name_parts = explode(' ', $full_name);
    if (count($name_parts) >= 2) {
        $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
    } else {
        $initials = strtoupper(substr($full_name, 0, 2));
    }
    
    $color = get_field('initials_color', 'user_' . $recruiter_id) ?: '#030303';
    
    return [
        'initials' => $initials,
        'color' => $color,
        'name' => $full_name
    ];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card dr-card">
                <div class="card-header dr-card-header">
                    <h3 class="card-title dr-card-title">
                        <i class="fas fa-trophy text-warning"></i>
                        Top 25 Drivers
                    </h3>
                </div>
                <div class="card-body p-0 dr-card-body">
                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-tabs dr-nav-tabs" id="driversTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dr-performance-tab" 
                                    data-bs-toggle="tab" data-bs-target="#dr-performance" 
                                    data-info="Sorted by delivered loads count, then by total profit"
                                    data-count="Showing <?php echo count($top_drivers_by_performance); ?> drivers with rating ≥ 4.0"
                                    type="button" role="tab" aria-controls="dr-performance" aria-selected="true">
                                <i class="fas fa-dollar-sign"></i> By Performance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dr-ratings-tab" 
                                    data-bs-toggle="tab" data-bs-target="#dr-ratings" 
                                    data-info="Sorted by rating count (desc), then by average rating (desc)"
                                    data-count="Showing <?php echo count($top_drivers_by_ratings); ?> drivers with at least 5 ratings"
                                    type="button" role="tab" aria-controls="dr-ratings" aria-selected="false">
                                <i class="fas fa-star"></i> By Ratings
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content dr-tab-content" id="driversTabsContent">
                        <!-- Performance Tab -->
                        <div class="tab-pane fade show active" id="dr-performance" role="tabpanel" aria-labelledby="dr-performance-tab">
                            <?php if (empty($top_drivers_by_performance)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No drivers found</h5>
                            <p class="text-muted">No drivers with rating ≥ 4.0 and delivered loads found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 dr-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">
                                            <i class="fas fa-medal"></i>
                                        </th>
                                        <th>Driver Name</th>
                                        <th class="text-center">
                                            <i class="fas fa-star text-warning"></i>
                                            Rating
                                        </th>
                                        <th class="text-center">
                                            <i class="fas fa-truck text-success"></i>
                                            Delivered Loads
                                        </th>
                                        <th class="text-center">
                                            <i class="fas fa-dollar-sign text-success"></i>
                                            Total Profit
                                        </th>
                                        <th class="text-center">
                                            <i class="fas fa-money-bill-wave text-primary"></i>
                                            Driver's Earnings
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_drivers_by_performance as $index => $driver): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php
                                                $position = $index + 1;
                                                if ($position == 1) {
                                                    echo '<i class="fas fa-trophy text-warning fa-lg" title="1st Place"></i>';
                                                } elseif ($position == 2) {
                                                    echo '<i class="fas fa-medal text-secondary fa-lg" title="2nd Place"></i>';
                                                } elseif ($position == 3) {
                                                    echo '<i class="fas fa-medal text-warning fa-lg" title="3rd Place"></i>';
                                                } else {
                                                    echo '<span class="badge badge-light dr-badge-light">' . $position . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $recruiter_info = get_recruiter_info($driver['recruiter_id'] ?? 0);
                                                    ?>
                                                    <div class="avatar-sm dr-avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white me-2" 
                                                         style="background-color: <?php echo esc_attr($recruiter_info['color']); ?>"
                                                         data-bs-toggle="tooltip" 
                                                         data-bs-placement="top" 
                                                         title="<?php echo esc_attr($recruiter_info['name']); ?>">
                                                        <?php echo esc_html($recruiter_info['initials']); ?>
                                                    </div>
                                                    <div>
                                                        <?php if (!empty($add_new_driver_url)): ?>
                                                            <a href="<?php echo esc_url($add_new_driver_url . '?driver=' . $driver['driver_id']); ?>" 
                                                               class="driver-name-link dr-driver-name-link">
                                                                <strong><?php echo esc_html($driver['driver_name']); ?></strong>
                                                            </a>
                                                        <?php else: ?>
                                                            <strong><?php echo esc_html($driver['driver_name']); ?></strong>
                                                        <?php endif; ?>
                                                        <br>
                                                        <small class="text-muted dr-text-muted">ID: <?php echo esc_html($driver['driver_id']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="rating-display dr-rating-display">
                                                    <div class="rating-stars dr-rating-stars">
                                                        <?php 
                                                        $rating = $driver['rating'];
                                                        $full_stars = floor($rating);
                                                        $has_half_star = ($rating - $full_stars) >= 0.5;
                                                        
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $full_stars) {
                                                                echo '<i class="fas fa-star text-warning"></i>';
                                                            } elseif ($i == $full_stars + 1 && $has_half_star) {
                                                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star text-muted"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="rating-number dr-rating-number">
                                                        <strong><?php echo number_format($driver['rating'], 1); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="loads-display dr-loads-display">
                                                    <div class="loads-icon dr-loads-icon">
                                                        <i class="fas fa-truck text-success"></i>
                                                    </div>
                                                    <div class="loads-number dr-loads-number">
                                                        <strong><?php echo number_format($driver['delivered_loads']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="profit-display dr-profit-display">
                                                    <div class="profit-icon dr-profit-icon">
                                                        <i class="fas fa-dollar-sign text-success"></i>
                                                    </div>
                                                    <div class="profit-amount dr-profit-amount">
                                                        <strong>$<?php echo number_format($driver['total_profit'], 2); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="earnings-display dr-earnings-display">
                                                    <div class="earnings-icon dr-earnings-icon">
                                                        <i class="fas fa-money-bill-wave text-primary"></i>
                                                    </div>
                                                    <div class="earnings-amount dr-earnings-amount">
                                                        <strong>$<?php echo number_format($driver['total_earnings'], 2); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                        </div>
                        
                        <!-- Ratings Tab -->
                        <div class="tab-pane fade" id="dr-ratings" role="tabpanel" aria-labelledby="dr-ratings-tab">
                            <?php if (empty($top_drivers_by_ratings)): ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No drivers found</h5>
                                    <p class="text-muted">No drivers with at least 5 ratings found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0 dr-table">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th class="text-center" style="width: 60px;">
                                                    <i class="fas fa-medal"></i>
                                                </th>
                                                <th>Driver Name</th>
                                                <th class="text-center">
                                                    <i class="fas fa-star text-warning"></i>
                                                    Average Rating
                                                </th>
                                                <th class="text-center">
                                                    <i class="fas fa-list-ol text-info"></i>
                                                    Rating Count
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_drivers_by_ratings as $index => $driver): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <?php
                                                        $position = $index + 1;
                                                        if ($position == 1) {
                                                            echo '<i class="fas fa-trophy text-warning fa-lg" title="1st Place"></i>';
                                                        } elseif ($position == 2) {
                                                            echo '<i class="fas fa-medal text-secondary fa-lg" title="2nd Place"></i>';
                                                        } elseif ($position == 3) {
                                                            echo '<i class="fas fa-medal text-warning fa-lg" title="3rd Place"></i>';
                                                        } else {
                                                            echo '<span class="badge badge-light dr-badge-light">' . $position . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php 
                                                            $recruiter_info = get_recruiter_info($driver['recruiter_id'] ?? 0);
                                                            ?>
                                                            <div class="avatar-sm dr-avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white me-2" 
                                                                 style="background-color: <?php echo esc_attr($recruiter_info['color']); ?>"
                                                                 data-bs-toggle="tooltip" 
                                                                 data-bs-placement="top" 
                                                                 title="<?php echo esc_attr($recruiter_info['name']); ?>">
                                                                    <?php echo esc_html($recruiter_info['initials']); ?>
                                                            </div>
                                                            <div>
                                                                <?php if (!empty($add_new_driver_url)): ?>
                                                                    <a href="<?php echo esc_url($add_new_driver_url . '?driver=' . $driver['driver_id']); ?>" 
                                                                       class="driver-name-link dr-driver-name-link">
                                                                        <strong><?php echo esc_html($driver['driver_name']); ?></strong>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <strong><?php echo esc_html($driver['driver_name']); ?></strong>
                                                                <?php endif; ?>
                                                                <br>
                                                                <small class="text-muted dr-text-muted">ID: <?php echo esc_html($driver['driver_id']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="rating-display dr-rating-display">
                                                            <div class="rating-stars dr-rating-stars">
                                                                <?php 
                                                                $rating = $driver['rating'];
                                                                $full_stars = floor($rating);
                                                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                                                
                                                                for ($i = 1; $i <= 5; $i++) {
                                                                    if ($i <= $full_stars) {
                                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                                    } elseif ($i == $full_stars + 1 && $has_half_star) {
                                                                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                                    } else {
                                                                        echo '<i class="far fa-star text-muted"></i>';
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                            <div class="rating-number dr-rating-number">
                                                                <strong><?php echo number_format($driver['rating'], 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="loads-display dr-loads-display">
                                                            <div class="loads-icon dr-loads-icon">
                                                                <i class="fas fa-list-ol text-info"></i>
                                                            </div>
                                                            <div class="loads-number dr-loads-number">
                                                                <strong><?php echo number_format($driver['rating_count']); ?></strong>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($top_drivers_by_performance) || !empty($top_drivers_by_ratings)): ?>
                    <div class="card-footer dr-card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted dr-text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="dr-footer-info-text">Sorted by delivered loads count, then by total profit</span>
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <small class="text-muted dr-text-muted">
                                    <span id="dr-footer-count-text">Showing <?php echo count($top_drivers_by_performance); ?> drivers with rating ≥ 4.0</span>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
