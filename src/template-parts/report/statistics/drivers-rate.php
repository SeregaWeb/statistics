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
    // Check if method exists
    if (!method_exists($tms_drivers, 'get_top_drivers')) {
        echo '<div class="alert alert-danger">Method get_top_drivers not found in TMS_Drivers class.</div>';
        return;
    }
    
    $top_drivers = $tms_drivers->get_top_drivers(25);
    
    // Ensure we have an array
    if (!is_array($top_drivers)) {
        $top_drivers = [];
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy text-warning"></i>
                        Top 25 Drivers by Performance
                    </h3>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($top_drivers)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No drivers found</h5>
                            <p class="text-muted">No drivers with rating ≥ 4.0 and delivered loads found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
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
                                    <?php foreach ($top_drivers as $index => $driver): ?>
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
                                                    echo '<span class="badge badge-light">' . $position . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $recruiter_info = get_recruiter_info($driver['recruiter_id'] ?? 0);
                                                    ?>
                                                    <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white me-2" 
                                                         style="background-color: <?php echo esc_attr($recruiter_info['color']); ?>"
                                                         data-bs-toggle="tooltip" 
                                                         data-bs-placement="top" 
                                                         title="<?php echo esc_attr($recruiter_info['name']); ?>">
                                                        <?php echo esc_html($recruiter_info['initials']); ?>
                                                    </div>
                                                    <div>
                                                        <?php if (!empty($add_new_driver_url)): ?>
                                                            <a href="<?php echo esc_url($add_new_driver_url . '?driver=' . $driver['driver_id']); ?>" 
                                                               class="driver-name-link">
                                                                <strong><?php echo esc_html($driver['driver_name']); ?></strong>
                                                            </a>
                                                        <?php else: ?>
                                                            <strong><?php echo esc_html($driver['driver_name']); ?></strong>
                                                        <?php endif; ?>
                                                        <br>
                                                        <small class="text-muted">ID: <?php echo esc_html($driver['driver_id']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="rating-display">
                                                    <div class="rating-stars">
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
                                                    <div class="rating-number">
                                                        <strong><?php echo number_format($driver['rating'], 1); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="loads-display">
                                                    <div class="loads-icon">
                                                        <i class="fas fa-truck text-success"></i>
                                                    </div>
                                                    <div class="loads-number">
                                                        <strong><?php echo number_format($driver['delivered_loads']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="profit-display">
                                                    <div class="profit-icon">
                                                        <i class="fas fa-dollar-sign text-success"></i>
                                                    </div>
                                                    <div class="profit-amount">
                                                        <strong>$<?php echo number_format($driver['total_profit'], 2); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="earnings-display">
                                                    <div class="earnings-icon">
                                                        <i class="fas fa-money-bill-wave text-primary"></i>
                                                    </div>
                                                    <div class="earnings-amount">
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
                <?php if (!empty($top_drivers)): ?>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Sorted by delivered loads count, then by total profit
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <small class="text-muted">
                                    Showing <?php echo count($top_drivers); ?> drivers with rating ≥ 4.0
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>


.card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    overflow: hidden;
}

.card-body {
    padding: 0;
}

/* Avatar styling */
.avatar-sm {
    width: 45px;
    height: 45px;
    font-size: 16px;
    font-weight: bold;
    margin-right: 12px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Table styling */
.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 1rem 0.75rem;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
    padding: 1rem 0.75rem;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

/* Rating display */
.rating-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.rating-stars {
    display: flex;
    gap: 2px;
    font-size: 1.1rem;
}

.rating-stars i {
    font-size: 1rem;
}

.rating-number {
    font-size: 1.1rem;
    color: #495057;
}

/* Loads display */
.loads-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.loads-icon {
    font-size: 1.2rem;
}

.loads-number {
    font-size: 1.2rem;
    color: #28a745;
}

/* Profit display */
.profit-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.profit-icon {
    font-size: 1.2rem;
}

.profit-amount {
    font-size: 1.1rem;
    color: #28a745;
}

/* Earnings display */
.earnings-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.earnings-icon {
    font-size: 1.2rem;
}

.earnings-amount {
    font-size: 1.1rem;
    color: #007bff;
}

/* Card header */
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
    padding: 1.5rem;
}

.card-header .card-title {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
}

.card-header .badge {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

/* Icons and decorations */
.fa-trophy, .fa-medal {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    font-size: 1.2rem;
}

.fa-trophy {
    color: #ffc107 !important;
}

.fa-medal {
    color: #6c757d !important;
}

/* Color scheme */
.text-warning {
    color: #ffc107 !important;
}

.text-success {
    color: #28a745 !important;
}

.text-primary {
    color: #007bff !important;
}

.text-muted {
    color: #6c757d !important;
}

/* Badge styling */
.badge-light {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
}

/* Card footer */
.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 1rem 1.5rem;
}

.card-footer small {
    font-size: 0.85rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem 0.5rem;
    }
    
    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
    }
    
    .rating-stars {
        font-size: 0.9rem;
    }
    
    .loads-number,
    .profit-amount,
    .earnings-amount {
        font-size: 1rem;
    }
}

/* Animation for table rows */
.table tbody tr {
    transition: all 0.2s ease;
}

/* Enhanced hover effects */
.table tbody tr:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Driver name styling */
.table td:first-child + td {
    padding-left: 1.5rem;
}

.table td:first-child + td strong {
    font-size: 1.1rem;
    color: #495057;
}

.table td:first-child + td small {
    font-size: 0.85rem;
    color: #6c757d;
}

/* Driver name link styling */
.driver-name-link {
    text-decoration: none;
    color: #495057;
    transition: all 0.2s ease;
}

.driver-name-link:hover {
    color: #007bff;
    text-decoration: none;
}

.driver-name-link strong {
    transition: all 0.2s ease;
}

.driver-name-link:hover strong {
    color: #007bff;
    transform: translateX(2px);
}
</style>
