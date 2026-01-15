<?php
/**
 * Testing Center - Centralized page for all test pages
 * Access via ?testing=1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user has permission
if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Administrator only.' );
}

// Check GET parameter
if ( ! isset( $_GET['testing'] ) || $_GET['testing'] != '1' ) {
	return;
}

$site_url = site_url();
$theme_url = get_template_directory_uri();
$root_url = site_url(); // Use site_url for root-level files

// Define all test pages
$test_pages = array(
	'Dispatcher Transfer Manager' => array(
		'url' => add_query_arg( array( 'dispatcher-transfer-manager' => '1' ), home_url() ),
		'description' => 'Track gradual transfer of dispatcher data. Shows queue status, progress by project (odysseia/flt) and tables (Odysseia/Martlet/Endurance), and recent logs.',
		'category' => 'System Management',
		'type' => 'WordPress Page',
	),
	
	'Driver Rating Block Cron Test' => array(
		'url' => add_query_arg( array( 'tms_test_rating_block' => '1' ), home_url() ),
		'description' => 'Manually trigger driver rating block cron. Blocks drivers with low ratings. Check debug.log for details.',
		'category' => 'Cron Jobs',
		'type' => 'GET Parameter',
	),
	
	'Birthday Email Test' => array(
		'url' => add_query_arg( array( 'test_birthday_email' => '1' ), home_url() ),
		'description' => 'Test birthday email notifications. Sends test birthday emails to check email functionality.',
		'category' => 'Cron Jobs',
		'type' => 'GET Parameter',
	),
	
	'User Sync Admin' => array(
		'url' => admin_url( 'tools.php?page=tms-user-sync' ),
		'description' => 'Test user synchronization between systems. View sync logs, test manual sync, and check sync status. Access via Tools > User Sync Test in admin menu.',
		'category' => 'User Management',
		'type' => 'Admin Page',
	),
	
	'Test Driver Loads (HTML)' => array(
		'url' => $root_url . '/test-driver-loads.html',
		'description' => 'Comprehensive test page for driver loads. Shows all loads, available loads for rating, and already rated loads. Includes statistics and debug information.',
		'category' => 'Driver Testing',
		'type' => 'HTML File',
	),
	
	'Test Driver Loads AJAX (HTML)' => array(
		'url' => $root_url . '/test-driver-loads-ajax.html',
		'description' => 'Simple AJAX test for getting available loads for rating by driver ID.',
		'category' => 'Driver Testing',
		'type' => 'HTML File',
	),
	
	'Test Driver Loads (WordPress Page)' => array(
		'url' => get_permalink( get_page_by_path( 'test-driver-loads' ) ) ?: '#',
		'description' => 'WordPress page template for testing driver loads functionality. Requires page with slug "test-driver-loads" to exist.',
		'category' => 'Driver Testing',
		'type' => 'WordPress Page',
		'conditional' => function_exists( 'get_page_by_path' ) && get_page_by_path( 'test-driver-loads' ),
	),
	
	'ETA Test (HTML)' => array(
		'url' => $theme_url . '/eta-test.html',
		'description' => 'Test ETA system. Create test ETA records and test ETA notifications via AJAX.',
		'category' => 'ETA System',
		'type' => 'HTML File',
	),
	
	'ETA Reference Debug (HTML)' => array(
		'url' => $root_url . '/test-eta-reference-debug.html',
		'description' => 'Debug reference number retrieval for ETA system. Test getting reference_number by load ID (supports FLT).',
		'category' => 'ETA System',
		'type' => 'HTML File',
	),
	
	'Check Driver Holds' => array(
		'url' => $theme_url . '/check-holds.php',
		'description' => 'Check driver hold status. View all holds, expired holds, and manually run cron to clean expired holds.',
		'category' => 'Driver Testing',
		'type' => 'PHP File',
	),
	
	'Generate Test Trailers' => array(
		'url' => $root_url . '/generate-test-trailers.php',
		'description' => 'Generate test trailer records for testing purposes.',
		'category' => 'Data Generation',
		'type' => 'PHP File',
	),
);

// ETA Test Pages from tests-eta directory
$eta_test_pages = array(
	'ETA Test Main' => array(
		'url' => $root_url . '/tests-eta/test-eta.php',
		'description' => 'Main ETA test page. Test ETA functionality and logic.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Logic' => array(
		'url' => $root_url . '/tests-eta/test-eta-logic.php',
		'description' => 'Test ETA logic and calculations.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Notifications' => array(
		'url' => $root_url . '/tests-eta/test-notifications.php',
		'description' => 'Test ETA notification system.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Cron' => array(
		'url' => $root_url . '/tests-eta/test-cron.php',
		'description' => 'Test ETA cron job execution.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Cron Soon' => array(
		'url' => $root_url . '/tests-eta/test-cron-soon.php',
		'description' => 'Test ETA cron for soon-to-arrive notifications.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Removal' => array(
		'url' => $root_url . '/tests-eta/test-eta-removal.php',
		'description' => 'Test ETA record removal functionality.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Removal AJAX' => array(
		'url' => $root_url . '/tests-eta/test-eta-removal-ajax.php',
		'description' => 'Test ETA removal via AJAX.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Removal Fixed' => array(
		'url' => $root_url . '/tests-eta/test-eta-removal-fixed.php',
		'description' => 'Test fixed ETA removal functionality.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Update' => array(
		'url' => $root_url . '/tests-eta/test-update.php',
		'description' => 'Test ETA update functionality.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'ETA Test Action Scheduler' => array(
		'url' => $root_url . '/tests-eta/test-action-scheduler.php',
		'description' => 'Test Action Scheduler integration for ETA system.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'Create Test ETA Records' => array(
		'url' => $root_url . '/tests-eta/create-test-records.php',
		'description' => 'Create test ETA records in database.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'Create Soon Test ETA Records' => array(
		'url' => $root_url . '/tests-eta/create-soon-test-records.php',
		'description' => 'Create test ETA records for soon-to-arrive testing.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'Check ETA Status' => array(
		'url' => $root_url . '/tests-eta/check-eta-status.php',
		'description' => 'Check current ETA status and records.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
	
	'Force Schedule ETA Cron' => array(
		'url' => $root_url . '/tests-eta/force-schedule-eta-cron.php',
		'description' => 'Force schedule ETA cron job for testing.',
		'category' => 'ETA System',
		'type' => 'PHP File',
	),
);

// Merge all test pages
$all_test_pages = array_merge( $test_pages, $eta_test_pages );

// Group by category
$grouped_pages = array();
foreach ( $all_test_pages as $name => $page ) {
	$category = $page['category'];
	if ( ! isset( $grouped_pages[ $category ] ) ) {
		$grouped_pages[ $category ] = array();
	}
	
	// Check conditional pages
	if ( isset( $page['conditional'] ) && ! $page['conditional'] ) {
		continue;
	}
	
	$grouped_pages[ $category ][ $name ] = $page;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Testing Center - TMS Statistics</title>
	<style>
		* {
			box-sizing: border-box;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 0;
			padding: 20px;
			background: #f0f0f1;
			color: #1d2327;
		}
		.container {
			max-width: 1400px;
			margin: 0 auto;
			background: white;
			padding: 30px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			border-radius: 4px;
		}
		h1 {
			margin-top: 0;
			color: #1d2327;
			border-bottom: 3px solid #2271b1;
			padding-bottom: 15px;
		}
		.category {
			margin: 30px 0;
		}
		.category h2 {
			color: #2271b1;
			margin-bottom: 15px;
			padding: 10px;
			background: #f6f7f7;
			border-left: 4px solid #2271b1;
		}
		.test-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
			gap: 20px;
			margin-bottom: 20px;
		}
		.test-card {
			background: #f6f7f7;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			padding: 20px;
			transition: box-shadow 0.2s;
		}
		.test-card:hover {
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		}
		.test-card h3 {
			margin: 0 0 10px 0;
			font-size: 18px;
			color: #1d2327;
		}
		.test-card .description {
			color: #646970;
			font-size: 14px;
			line-height: 1.5;
			margin: 10px 0;
		}
		.test-card .meta {
			display: flex;
			gap: 10px;
			margin-top: 15px;
			font-size: 12px;
		}
		.badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.badge-wordpress { background: #2271b1; color: white; }
		.badge-html { background: #e34c26; color: white; }
		.badge-php { background: #777bb4; color: white; }
		.badge-get { background: #00a32a; color: white; }
		.badge-admin { background: #d63638; color: white; }
		.test-link {
			display: inline-block;
			margin-top: 15px;
			padding: 10px 20px;
			background: #2271b1;
			color: white;
			text-decoration: none;
			border-radius: 3px;
			font-weight: 600;
			transition: background 0.2s;
		}
		.test-link:hover {
			background: #135e96;
		}
		.test-link:target {
			background: #00a32a;
		}
		.info-box {
			background: #f0f6fc;
			border-left: 4px solid #2271b1;
			padding: 15px;
			margin: 20px 0;
		}
		.info-box p {
			margin: 5px 0;
		}
		.search-box {
			margin: 20px 0;
			padding: 15px;
			background: #f6f7f7;
			border-radius: 4px;
		}
		.search-box input {
			width: 100%;
			padding: 10px;
			font-size: 16px;
			border: 1px solid #c3c4c7;
			border-radius: 3px;
		}
		.hidden {
			display: none;
		}
		.stats {
			display: flex;
			gap: 20px;
			margin: 20px 0;
			padding: 15px;
			background: #f6f7f7;
			border-radius: 4px;
		}
		.stat-item {
			text-align: center;
		}
		.stat-item .number {
			font-size: 32px;
			font-weight: 600;
			color: #2271b1;
		}
		.stat-item .label {
			font-size: 12px;
			color: #646970;
			text-transform: uppercase;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🧪 Testing Center</h1>
		
		<div class="info-box">
			<p><strong>Welcome to the Testing Center!</strong></p>
			<p>This page provides centralized access to all test pages and debugging tools in the TMS Statistics system.</p>
			<p><strong>Note:</strong> All test pages require administrator access.</p>
		</div>
		
		<div class="stats">
			<div class="stat-item">
				<div class="number"><?php echo count( $all_test_pages ); ?></div>
				<div class="label">Total Test Pages</div>
			</div>
			<div class="stat-item">
				<div class="number"><?php echo count( $grouped_pages ); ?></div>
				<div class="label">Categories</div>
			</div>
		</div>
		
		<div class="search-box">
			<input type="text" id="searchInput" placeholder="Search test pages by name or description..." onkeyup="filterTests()">
		</div>
		
		<?php foreach ( $grouped_pages as $category => $pages ): ?>
			<div class="category" data-category="<?php echo esc_attr( strtolower( $category ) ); ?>">
				<h2><?php echo esc_html( $category ); ?> (<?php echo count( $pages ); ?>)</h2>
				<div class="test-grid">
					<?php foreach ( $pages as $name => $page ): ?>
						<div class="test-card" data-name="<?php echo esc_attr( strtolower( $name ) ); ?>" data-description="<?php echo esc_attr( strtolower( $page['description'] ) ); ?>">
							<h3><?php echo esc_html( $name ); ?></h3>
							<div class="description"><?php echo esc_html( $page['description'] ); ?></div>
							<div class="meta">
								<?php
								$badge_class = 'badge-php';
								if ( $page['type'] === 'WordPress Page' ) {
									$badge_class = 'badge-wordpress';
								} elseif ( $page['type'] === 'HTML File' ) {
									$badge_class = 'badge-html';
								} elseif ( $page['type'] === 'GET Parameter' ) {
									$badge_class = 'badge-get';
								} elseif ( $page['type'] === 'Admin Page' ) {
									$badge_class = 'badge-admin';
								}
								?>
								<span class="badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $page['type'] ); ?></span>
								<span class="badge" style="background: #646970; color: white;"><?php echo esc_html( $category ); ?></span>
							</div>
							<a href="<?php echo esc_url( $page['url'] ); ?>" target="_blank" class="test-link">
								Open Test Page →
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
		
		<div class="info-box" style="margin-top: 40px;">
			<h3>Quick Access Links</h3>
			<ul>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'dispatcher-transfer-manager' => '1' ), home_url() ) ); ?>" target="_blank">Dispatcher Transfer Manager</a></li>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'tms_test_rating_block' => '1' ), home_url() ) ); ?>" target="_blank">Driver Rating Block Test</a></li>
				<li><a href="<?php echo esc_url( $root_url . '/test-driver-loads.html' ); ?>" target="_blank">Test Driver Loads</a></li>
				<li><a href="<?php echo esc_url( $theme_url . '/eta-test.html' ); ?>" target="_blank">ETA Test</a></li>
			</ul>
		</div>
	</div>
	
	<script>
		function filterTests() {
			const input = document.getElementById('searchInput');
			const filter = input.value.toLowerCase();
			const cards = document.querySelectorAll('.test-card');
			const categories = document.querySelectorAll('.category');
			
			let visibleCount = 0;
			
			cards.forEach(card => {
				const name = card.getAttribute('data-name');
				const description = card.getAttribute('data-description');
				const matches = name.includes(filter) || description.includes(filter);
				
				if (matches) {
					card.classList.remove('hidden');
					visibleCount++;
				} else {
					card.classList.add('hidden');
				}
			});
			
			// Hide/show categories based on visible cards
			categories.forEach(category => {
				const visibleCards = category.querySelectorAll('.test-card:not(.hidden)');
				if (visibleCards.length === 0) {
					category.style.display = 'none';
				} else {
					category.style.display = 'block';
				}
			});
		}
	</script>
</body>
</html>
<?php
exit;
