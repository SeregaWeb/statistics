<?php
/**
 * Template Name: Page driver statistics rating
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$Drivers = new TMSDrivers();
$helper  = new TMSReportsHelper();

// Check FLT access
$current_user_id = get_current_user_id();
$access_flt = get_field( 'flt', 'user_' . $current_user_id );
$is_admin = current_user_can( 'administrator' );
$has_flt_access = $is_admin || $access_flt;

// Get month and year from GET parameters, default to current month/year
$month = isset( $_GET['fmonth'] ) && ! empty( $_GET['fmonth'] ) ? (int) $_GET['fmonth'] : (int) date( 'm' );
$year  = isset( $_GET['fyear'] ) && ! empty( $_GET['fyear'] ) ? (int) $_GET['fyear'] : (int) date( 'Y' );

// Get load type filter (regular, flt, or all)
// Default to 'regular' - users with FLT access can change it if needed
$load_type = isset( $_GET['load_type'] ) ? $_GET['load_type'] : 'regular';

// Security check: if user doesn't have FLT access, ignore FLT filter and show only regular loads
if ( ! $has_flt_access && $load_type === 'flt' ) {
	$load_type = 'regular';
}

$is_flt = null; // null = both, true = FLT only, false = regular only
if ( $load_type === 'flt' ) {
	$is_flt = true;
} elseif ( $load_type === 'regular' ) {
	$is_flt = false;
}

// Check if we're viewing a specific dispatcher profile
$dispatcher_id = isset( $_GET['dispatcher'] ) && ! empty( $_GET['dispatcher'] ) ? (int) $_GET['dispatcher'] : 0;

if ( $dispatcher_id > 0 ) {
	// Show dispatcher profile with detailed ratings
	$dispatcher_ratings = $Drivers->get_dispatcher_detailed_ratings( $dispatcher_id, $year, $month, $is_flt );
    
	$helper_user = new TMSReportsHelper();
	$user_info = $helper_user->get_user_full_name_by_id( $dispatcher_id );
	$dispatcher_name = $user_info ? $user_info['full_name'] : 'Unknown Dispatcher';
	
	// Get dispatcher role
	$dispatcher_user = get_userdata( $dispatcher_id );
	$dispatcher_role_key = ! empty( $dispatcher_user->roles ) ? $dispatcher_user->roles[0] : '';
	$TMSUsers = new TMSUsers();
	$dispatcher_role_label = $dispatcher_role_key ? $TMSUsers->get_role_label( $dispatcher_role_key ) : '';
	
	// Get months for dropdown
	$months = $helper->get_months();
	$current_year = (int) date( 'Y' );
	
	// Calculate statistics for this dispatcher
	$all_stats = $Drivers->get_dispatcher_rating_statistics( $year, $month, $is_flt );
	$dispatcher_stats = null;
	foreach ( $all_stats as $stat ) {
		if ( $stat['dispatcher_id'] == $dispatcher_id ) {
			$dispatcher_stats = $stat;
			break;
		}
	}
	
	?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-0">
                                    <?php echo esc_html( $dispatcher_name ); ?>
                                    <?php if ( ! empty( $dispatcher_role_label ) ): ?>
                                        <span class="text-muted small">(<?php echo esc_html( $dispatcher_role_label ); ?>)</span>
                                    <?php endif; ?>
                                </h2>
                                <p class="text-muted mb-0">Rating Details</p>
                            </div>
                            <a href="<?php echo esc_url( remove_query_arg( 'dispatcher' ) ); ?>" 
                               class="btn btn-outline-secondary">
                                ← Back to Statistics
                            </a>
                        </div>
                        
                        <!-- Filters -->
                        <form method="GET" class="mb-4 js-auto-submit-form">
                            <input type="hidden" name="dispatcher" value="<?php echo esc_attr( $dispatcher_id ); ?>">
                            <div class="d-flex gap-2 align-items-end flex-wrap">
                                <div>
                                    <label class="form-label">Month</label>
                                    <select class="form-select" name="fmonth" required>
                                        <?php foreach ( $months as $num => $name ): ?>
                                            <option value="<?php echo esc_attr( $num ); ?>" 
                                                    <?php selected( $month, $num ); ?>>
                                                <?php echo esc_html( $name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="form-label">Year</label>
                                    <select class="form-select" name="fyear" required>
                                        <?php for ( $y = 2024; $y <= $current_year; $y++ ): ?>
                                            <option value="<?php echo esc_attr( $y ); ?>" 
                                                    <?php selected( $year, $y ); ?>>
                                                <?php echo esc_html( $y ); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <?php if ( $has_flt_access ): ?>
                                    <div>
                                        <label class="form-label">Load Type</label>
                                        <select class="form-select" name="load_type">
                                            <option value="all" <?php selected( $load_type, 'all' ); ?>>All Loads</option>
                                            <option value="regular" <?php selected( $load_type, 'regular' ); ?>>Regular</option>
                                            <option value="flt" <?php selected( $load_type, 'flt' ); ?>>FLT</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        </form>
                        
                        <!-- Summary Statistics -->
                        <?php if ( $dispatcher_stats ): ?>
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-muted">Total Loads</h5>
                                            <h3 class="mb-0"><?php echo esc_html( $dispatcher_stats['total_loads'] ); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-muted">Rated Loads</h5>
                                            <h3 class="mb-0">
                                                <span class="badge bg-success">
                                                    <?php echo esc_html( $dispatcher_stats['rated_loads'] ); ?>
                                                </span>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-muted">Rating Rate</h5>
                                            <h3 class="mb-0">
                                                <?php 
                                                $rating_rate = $dispatcher_stats['total_loads'] > 0 
                                                    ? round( ( $dispatcher_stats['rated_loads'] / $dispatcher_stats['total_loads'] ) * 100, 1 ) 
                                                    : 0;
                                                echo esc_html( $rating_rate ); ?>%
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-muted">Average Rating</h5>
                                            <h3 class="mb-0">
                                                <?php if ( $dispatcher_stats['average_rating'] > 0 ): ?>
                                                    <span class="badge bg-<?php echo $dispatcher_stats['average_rating'] >= 4.5 ? 'success' : ( $dispatcher_stats['average_rating'] >= 3.5 ? 'warning' : 'danger' ); ?>">
                                                        <?php echo esc_html( $dispatcher_stats['average_rating'] ); ?>/5
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Detailed Ratings -->
                        <h3 class="mb-3">Rating Cards</h3>
                        <?php if ( ! empty( $dispatcher_ratings ) ): ?>
                            <div class="row">
                                <?php foreach ( $dispatcher_ratings as $rating ): ?>
                                    <?php
                                    // Format date
                                    $rating_date = date( 'M d, Y g:i a', $rating['time'] );
                                    
                                    // Determine badge color
                                    $badge_color = 'secondary';
                                    if ( $rating['reit'] >= 5 ) {
                                        $badge_color = 'success';
                                    } elseif ( $rating['reit'] >= 4 ) {
                                        $badge_color = 'info';
                                    } elseif ( $rating['reit'] >= 3 ) {
                                        $badge_color = 'warning';
                                    } else {
                                        $badge_color = 'danger';
                                    }
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">
                                                            <?php echo esc_html( $rating['name'] ); ?>
                                                        </h6>
                                                        <?php if ( ! empty( $rating['order_number'] ) ): ?>
                                                            <small class="text-muted">
                                                                Order: <?php echo esc_html( $rating['order_number'] ); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge bg-<?php echo esc_attr( $badge_color ); ?> fs-6">
                                                        <?php echo esc_html( $rating['reit'] ); ?>/5
                                                    </span>
                                                </div>
                                                
                                                <?php if ( ! empty( $rating['message'] ) ): ?>
                                                    <p class="card-text">
                                                        <?php echo nl2br( esc_html( $rating['message'] ) ); ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="card-text text-muted">
                                                        <em>No comment</em>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <small class="text-muted d-block mt-2">
                                                    <?php echo esc_html( $rating_date ); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No ratings found for this dispatcher in the selected period.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<?php
} else {
	// Show main statistics table
	// Get statistics
	$statistics = $Drivers->get_dispatcher_rating_statistics( $year, $month, $is_flt );
	
	// Get months for dropdown
	$months = $helper->get_months();
	$current_year = (int) date( 'Y' );
	
	// Get current page URL for dispatcher profile links
	$current_page_url = get_permalink();

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
                        <h2 class="mb-4">Driver Rating Statistics</h2>
                        
                        <!-- Filters -->
                        <form method="GET" class="mb-4 js-auto-submit-form">
                            <div class="d-flex gap-2 align-items-end flex-wrap">
                                <div>
                                    <label class="form-label">Month</label>
                                    <select class="form-select" name="fmonth" required>
                                        <?php foreach ( $months as $num => $name ): ?>
                                            <option value="<?php echo esc_attr( $num ); ?>" 
                                                    <?php selected( $month, $num ); ?>>
                                                <?php echo esc_html( $name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="form-label">Year</label>
                                    <select class="form-select" name="fyear" required>
                                        <?php for ( $y = 2024; $y <= $current_year; $y++ ): ?>
                                            <option value="<?php echo esc_attr( $y ); ?>" 
                                                    <?php selected( $year, $y ); ?>>
                                                <?php echo esc_html( $y ); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <?php if ( $has_flt_access ): ?>
                                    <div>
                                        <label class="form-label">Load Type</label>
                                        <select class="form-select" name="load_type">
                                            <option value="all" <?php selected( $load_type, 'all' ); ?>>All Loads</option>
                                            <option value="regular" <?php selected( $load_type, 'regular' ); ?>>Expedite</option>
                                            <option value="flt" <?php selected( $load_type, 'flt' ); ?>>FLT</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <!-- Statistics Table -->
                        <?php if ( ! empty( $statistics ) ): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dispatcher</th>
                                            <th class="text-end">Total Loads</th>
                                            <th class="text-end">Rated Loads</th>
                                            <th class="text-end">Rating Rate</th>
                                            <th class="text-end">Average Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $statistics as $stat ): ?>
                                            <?php
                                            $rating_rate = $stat['total_loads'] > 0 
                                                ? round( ( $stat['rated_loads'] / $stat['total_loads'] ) * 100, 1 ) 
                                                : 0;
                                            
                                            $rated_badge_class = 'secondary';
                                            if ( $stat['total_loads'] > 0 ) {
                                                if ( $rating_rate >= 90 ) {
                                                    $rated_badge_class = 'success';
                                                } elseif ( $rating_rate >= 80 ) {
                                                    $rated_badge_class = 'warning';
                                                } else {
                                                    $rated_badge_class = 'danger';
                                                }
                                            }
                                            
                                            // Build profile link
                                            $profile_url_params = array(
                                                'dispatcher' => $stat['dispatcher_id'],
                                                'fmonth' => $month,
                                                'fyear' => $year,
                                            );
                                            if ( $has_flt_access && $load_type !== 'all' ) {
                                                $profile_url_params['load_type'] = $load_type;
                                            }
                                            $profile_url = add_query_arg( $profile_url_params, $current_page_url );
                                            
                                            // Determine rating badge color
                                            $rating_color = 'secondary';
                                            if ( $stat['average_rating'] >= 4.5 ) {
                                                $rating_color = 'success';
                                            } elseif ( $stat['average_rating'] >= 3.5 ) {
                                                $rating_color = 'warning';
                                            } elseif ( $stat['average_rating'] > 0 ) {
                                                $rating_color = 'danger';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo esc_url( $profile_url ); ?>" 
                                                       class="text-decoration-none">
                                                        <strong><?php echo esc_html( $stat['dispatcher_name'] ); ?></strong>
                                                        <?php if ( ! empty( $stat['dispatcher_role'] ) ): ?>
                                                            <span class="text-muted small ms-2">(<?php echo esc_html( $stat['dispatcher_role'] ); ?>)</span>
                                                        <?php endif; ?>
                                                    </a>
                                                </td>
                                                <td class="text-end">
                                                    <?php echo esc_html( $stat['total_loads'] ); ?>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-<?php echo esc_attr( $rated_badge_class ); ?>">
                                                        <?php echo esc_html( $stat['rated_loads'] ); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php echo esc_html( $rating_rate ); ?>%
                                                </td>
                                                <td class="text-end">
                                                    <?php if ( $stat['average_rating'] > 0 ): ?>
                                                        <span class="badge bg-<?php echo esc_attr( $rating_color ); ?>">
                                                            <?php echo esc_html( $stat['average_rating'] ); ?>/5
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No statistics found for the selected period.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<?php
} // End else block for main statistics table

do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
