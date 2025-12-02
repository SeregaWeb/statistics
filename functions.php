<?php
/**
 * Main function themes
 *
 * @package WP-rock
 * @since 4.4.0
 */

define( 'THEME_URI', get_template_directory_uri() );
define( 'THEME_DIR', get_template_directory() );
define( 'STYLE_URI', get_stylesheet_uri() );
define( 'STYLE_DIR', get_stylesheet_directory() );
define( 'ASSETS_CSS', THEME_URI . '/assets/public/css/' );
define( 'ASSETS_JS', THEME_URI . '/assets/public/js/' );
define( 'LIBS_JS', THEME_URI . '/src/js/libs/' );
define( 'TEMPLATE_PATH', 'src/template-parts/report/' );

// required files.
require THEME_DIR . '/src/inc/class-wp-rock.php';
require 'vendor/autoload.php';

// Core classes that admin tools depend on
require THEME_DIR . '/src/inc/core/class-tms-geocode-addresses.php';

// Admin tools (temporary)
if (is_admin()) {
    require THEME_DIR . '/src/inc/admin/class-fix-driver-data.php';
    require THEME_DIR . '/src/inc/admin/class-geocode-addresses-admin.php';
}

use Mpdf\Mpdf;

require THEME_DIR . '/src/inc/core/class-map-controller.php';
require THEME_DIR . '/src/inc/core/class-tms-common-helper.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-icons.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-helper.php';
require THEME_DIR . '/src/inc/core/class-tms-auth.php';
require THEME_DIR . '/src/inc/core/class-tms-reports.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-flt.php';
require THEME_DIR . '/src/inc/core/class-tms-users.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-statistics.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-company.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-shipper.php';
require THEME_DIR . '/src/inc/core/class-tms-emails.php';
require THEME_DIR . '/src/inc/core/class-tms-logs.php';
require THEME_DIR . '/src/inc/core/class-tms-performance.php';
require THEME_DIR . '/src/inc/core/class-tms-generate-document.php';
require THEME_DIR . '/src/inc/core/class-tms-drivers-helper.php';
require THEME_DIR . '/src/inc/core/class-tms-drivers.php';
require THEME_DIR . '/src/inc/core/class-tms-drivers-recruiter.php';
require THEME_DIR . '/src/inc/core/class-tms-drivers-api.php';
require THEME_DIR . '/src/inc/core/class-tms-trailers.php';
require THEME_DIR . '/src/inc/core/class-dark-mode.php';
require THEME_DIR . '/src/inc/core/class-tms-user-sync-api.php';
require THEME_DIR . '/src/inc/hooks/user-sync-hooks.php';
require THEME_DIR . '/src/inc/admin/user-sync-admin.php';
require THEME_DIR . '/src/inc/core/class-tms-contacts.php';
require THEME_DIR . '/src/inc/cron/driver-status-cron.php';
require THEME_DIR . '/src/inc/cron/driver-rating-block-cron.php';
require THEME_DIR . '/src/inc/cron/geocode-addresses-cron.php';
require THEME_DIR . '/src/inc/core/class-tms-timer-logs.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-timer.php';
require THEME_DIR . '/src/inc/core/class-tms-eta.php';
require THEME_DIR . '/src/inc/initial-setup.php';
require THEME_DIR . '/src/inc/enqueue-scripts.php';
require THEME_DIR . '/src/inc/acf-setting.php';
require THEME_DIR . '/src/inc/custom-posts-type.php';
require THEME_DIR . '/src/inc/custom-taxonomies.php';
require THEME_DIR . '/src/inc/class-wp-rock-blocks.php';
require THEME_DIR . '/src/inc/ajax-requests.php';
require THEME_DIR . '/src/inc/custom-hooks.php';
require THEME_DIR . '/src/inc/custom-shortcodes.php';
require THEME_DIR . '/src/inc/class-mobile-detect.php';
require THEME_DIR . '/src/inc/admin-cache-manager.php';

function disable_canonical_redirect_for_paged( $redirect_url ) {
	if ( is_paged() && strpos( $redirect_url, '/page/' ) !== false ) {
		return false;
	}
	
	return $redirect_url;
}

add_filter( 'redirect_canonical', 'disable_canonical_redirect_for_paged' );

add_action( 'template_redirect', function() {
	if ( isset( $_GET[ 'use_driver' ] ) ) {
		$use_driver  = $_GET[ 'use_driver' ];
		$user_id     = get_current_user_id();
		$raw         = get_field( 'field_66eeb9e964c67', 'user_' . $user_id );
		$need_select = false;
		foreach ( $raw as $key => $value ) {
			if ( strtolower( $value ) === strtolower( $use_driver ) ) {
				$need_select = $value;
			}
		}
		
		if ( $need_select ) {
			update_field( 'field_66eeba6448749', $need_select, 'user_' . $user_id );
		} else {
			wp_die( 'Access denied. <a href="' . home_url() . '">Go to home page</a>' );
		}
		$clean_url = remove_query_arg( 'use_driver' );
		wp_safe_redirect( $clean_url );
		exit;
	}
} );


//$reports = new TMSReports();
//$reports->update_contacts_for_new_user( 5, 1 );

/**
 * Test Brokersnapshot API key
 * Usage: ?test_api=1
 */
function test_brokersnapshot_api() {
	if (!isset($_GET['test_api']) || $_GET['test_api'] !== '1') {
		return;
	}
	
	// Check if user is admin
	if (!current_user_can('administrator')) {
		wp_die('Access denied');
	}
	
	global $global_options;
	// Get API key from global options
	$api_key = get_field_value($global_options, 'brokersnapshot');
	
	if (empty($api_key)) {
		echo '<h2>API Test Result</h2>';
		echo '<p style="color: red;">ERROR: API key is empty!</p>';
		return;
	}
	
	echo '<h2>API Test Result</h2>';
	echo '<p><strong>API Key:</strong> ' . substr($api_key, 0, 10) . '... (length: ' . strlen($api_key) . ')</p>';
	
	// Test API call
	$endpoint = 'https://brokersnapshot.com/api/v1/Companies';
	$params = [
		'name' => 'transportation',
		'limit' => 2
	];
	
	$url = $endpoint . '?' . http_build_query($params);
	
	echo '<p><strong>Request URL:</strong> ' . $url . '</p>';
	
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => [
			'Authorization: Bearer ' . $api_key
		]
	]);
	
	$response = curl_exec($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$curl_error = curl_error($curl);
	
	echo '<p><strong>HTTP Code:</strong> ' . $http_code . '</p>';
	
	if ($curl_error) {
		echo '<p style="color: red;"><strong>CURL Error:</strong> ' . $curl_error . '</p>';
	} else {
		echo '<p><strong>Response:</strong></p>';
		echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
		echo htmlspecialchars($response);
		echo '</pre>';
		
		$data = json_decode($response, true);
		if ($data && isset($data['Success'])) {
			if ($data['Success']) {
				echo '<p style="color: green;"><strong>✅ API Test Successful!</strong></p>';
				if (!empty($data['Data'])) {
					echo '<p><strong>Found ' . count($data['Data']) . ' companies</strong></p>';
					foreach ($data['Data'] as $company) {
						echo '<p>• ' . $company['NAME'] . ' (DOT: ' . $company['DOT_NUMBER'] . ', MC: ' . $company['DOCKET_NUMBER'] . ')</p>';
					}
				}
			} else {
				echo '<p style="color: red;"><strong>❌ API Test Failed</strong></p>';
			}
		}
	}
	
	curl_close($curl);
	
	echo '<p><a href="' . home_url() . '">← Back to home</a></p>';
	exit;
}

add_action('template_redirect', 'test_brokersnapshot_api');