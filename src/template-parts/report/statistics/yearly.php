<?php
/**
 * Yearly Statistics Report Template
 * 
 * Displays monthly statistics for a selected dispatcher and year
 * with filtering capabilities and totals calculation.
 */

// Initialize required classes
$statistics = new TMSStatistics();
$helper     = new TMSReportsHelper();
$TMSUsers   = new TMSUsers();

// Configuration constants
const START_YEAR = 2023;
const DECIMAL_PLACES = 2;

// Get and validate parameters
$current_year = (int) date( 'Y' );
$year_param = get_field_value( $_GET, 'fyear' );
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );

// Check user permissions
$need_office = $TMSUsers->check_user_role_access( ['dispatcher'], true );
$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );

// Get available dispatchers
$dispatchers = $statistics->get_dispatchers( $need_office ? $office_dispatcher : null, false, true );

// Validate and set default values
$dispatcher_initials = is_numeric( $dispatcher_initials ) 
	? (int) $dispatcher_initials 
	: ( $dispatchers[0]['id'] ?? null );

$year_param = is_numeric( $year_param ) 
	? (int) $year_param 
	: $current_year;

// Get monthly statistics data
$monthly_stats = $statistics->get_monthly_dispatcher_stats( $dispatcher_initials, $year_param );

?>

<!-- Filter Form -->
<form class="monthly w-100 js-auto-submit-form" method="GET">
    <input type="hidden" name="active_state" value="yearly">
    
    <div class="d-flex gap-1">
        <!-- Year Selector -->
        <select class="form-select w-auto" required name="fyear" aria-label="Select year">
            <option value="">Year</option>
            <?php for ( $year = START_YEAR; $year <= $current_year; $year++ ): ?>
                <option value="<?php echo esc_attr( $year ); ?>" 
                        <?php selected( $year_param, $year ); ?>>
                    <?php echo esc_html( $year ); ?>
                </option>
            <?php endfor; ?>
        </select>
        
        <!-- Dispatcher Selector -->
        <select class="form-select w-auto" required name="dispatcher" aria-label="Select dispatcher">
            <option value="">Dispatcher</option>
            <?php if ( is_array( $dispatchers ) && ! empty( $dispatchers ) ): ?>
                <?php foreach ( $dispatchers as $dispatcher ): ?>
                    <option value="<?php echo esc_attr( $dispatcher['id'] ); ?>" 
                            <?php selected( $dispatcher_initials, $dispatcher['id'] ); ?>>
                        <?php echo esc_html( $dispatcher['fullname'] ); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <!-- <button class="btn btn-primary" type="submit">Filter</button> -->
    </div>
    
    <!-- Monthly Statistics Table -->
    <?php if ( ! empty( $monthly_stats ) ): ?>
        <table class="table-stat">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Loads</th>
                    <th>Profit</th>
                    <th>Average per load</th>
                    <th>Average daily per month</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Initialize totals
                $totals = [
                    'loads' => 0,
                    'profit' => 0,
                    'average_profit' => 0
                ];
                
                foreach ( $monthly_stats as $month_data ):
                    // Skip months with no data
                    if ( empty( $month_data['post_count'] ) ) {
                        continue;
                    }
                    
                    $work_days = $statistics->countWeekdays( $month_data['month'], $year_param );
                    $daily_average = $work_days > 0 ? $month_data['total_profit'] / $work_days : 0;
                    
                    // Update totals
                    $totals['loads'] += $month_data['post_count'];
                    $totals['profit'] += $month_data['total_profit'];
                    $totals['average_profit'] += $month_data['average_profit'];
                    ?>
                    <tr>
                        <td><?php echo esc_html( $month_data['month'] ); ?></td>
                        <td><?php echo esc_html( $month_data['post_count'] ); ?></td>
                        <td>$<?php echo number_format( $month_data['total_profit'], DECIMAL_PLACES ); ?></td>
                        <td>$<?php echo number_format( $month_data['average_profit'], DECIMAL_PLACES ); ?></td>
                        <td title="Working days: <?php echo esc_attr( $work_days ); ?>">
                            $<?php echo number_format( $daily_average, DECIMAL_PLACES ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">
            <p>No data available for the selected dispatcher and year.</p>
        </div>
    <?php endif; ?>
    </form>

<!-- Yearly Totals -->
<?php if ( ! empty( $totals ) && $totals['loads'] > 0 ): ?>
    <h2>Total per year</h2>
    
    <table class="table-stat total" border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr class="text-left">
                <th>Loads</th>
                <th>Profit</th>
                <th>Average Profit</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-left">
                <td><?php echo esc_html( $totals['loads'] ); ?></td>
                <td>$<?php echo number_format( $totals['profit'], DECIMAL_PLACES ); ?></td>
                <td>$<?php echo number_format( $totals['average_profit'], DECIMAL_PLACES ); ?></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>