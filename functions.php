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

/**
 * Test email functionality
 * Usage: ?test_email=1 (admin only)
 * Debug info is logged to error_log
 */
function test_email_functionality() {
	if (!isset($_GET['test_email']) || $_GET['test_email'] !== '1') {
		return;
	}
	
	// Check if user is admin
	if (!current_user_can('administrator')) {
		return; // Silent return for non-admins
	}
	
	$to = 'milchenko2k16@gmail.com';
	$subject = 'Test Email from TMS Statistics';
	$current_time = current_time('mysql');
	$site_name = get_bloginfo('name');
	$site_url = home_url();
	
	$message = '
		<html>
		<head>
			<title>Test Email</title>
		</head>
		<body>
			<h2>Test Email</h2>
			<p>This is a test email to verify email functionality.</p>
			<p><strong>Time:</strong> ' . $current_time . '</p>
			<p><strong>Site:</strong> ' . $site_name . '</p>
			<p><strong>URL:</strong> ' . $site_url . '</p>
			<hr>
			<p>If you received this email, the email system is working correctly!</p>
		</body>
		</html>
	';
	
	// Use existing admin email as sender to avoid delivery issues
	$admin_email = get_option('admin_email');
	$from_email = 'no-reply@odysseia-transport.com';
	$from_name = 'TMS Statistics';
	
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: TMS <no-reply@odysseia-transport.com>'
	);
	
	// Log debug info before sending
	error_log('=== EMAIL TEST START ===');
	error_log('To: ' . $to);
	error_log('Subject: ' . $subject);
	error_log('Time: ' . $current_time);
	error_log('Site: ' . $site_name);
	error_log('URL: ' . $site_url);
	error_log('User ID: ' . get_current_user_id());
	error_log('User Email: ' . wp_get_current_user()->user_email);
	
	// Check if SMTP is configured
	$smtp_host = defined('SMTP_HOST') ? SMTP_HOST : 'Not defined';
	$smtp_port = defined('SMTP_PORT') ? SMTP_PORT : 'Not defined';
	$smtp_user = defined('SMTP_USER') ? SMTP_USER : 'Not defined';
	error_log('SMTP_HOST: ' . $smtp_host);
	error_log('SMTP_PORT: ' . $smtp_port);
	error_log('SMTP_USER: ' . $smtp_user);
	
	// Check WordPress mail settings
	error_log('WordPress admin_email: ' . get_option('admin_email'));
	error_log('WordPress mail_from: ' . get_option('mail_from'));
	error_log('WordPress mail_from_name: ' . get_option('mail_from_name'));
	error_log('Using From Email: ' . $from_email);
	error_log('Using From Name: ' . $from_name);
	
	// Log headers
	error_log('Headers: ' . print_r($headers, true));
	
	// Warning about mail() function
	error_log('⚠️ WARNING: Using PHP mail() function. Emails may:');
	error_log('   - Go to spam folder');
	error_log('   - Be blocked by recipient server');
	error_log('   - Not be delivered if SPF/DKIM not configured');
	error_log('   - Consider configuring SMTP for better delivery');
	
	// Hook into PHPMailer to capture detailed info
	add_action('phpmailer_init', function($phpmailer) {
		error_log('PHPMailer initialized');
		error_log('PHPMailer Host: ' . $phpmailer->Host);
		error_log('PHPMailer Port: ' . $phpmailer->Port);
		error_log('PHPMailer SMTPAuth: ' . ($phpmailer->SMTPAuth ? 'true' : 'false'));
		error_log('PHPMailer Username: ' . $phpmailer->Username);
		error_log('PHPMailer From: ' . $phpmailer->From);
		error_log('PHPMailer FromName: ' . $phpmailer->FromName);
		error_log('PHPMailer Mailer: ' . $phpmailer->Mailer);
		error_log('PHPMailer SMTPDebug: ' . $phpmailer->SMTPDebug);
	}, 10, 1);
	
	// Enable SMTP debug temporarily
	add_action('phpmailer_init', function($phpmailer) {
		$phpmailer->SMTPDebug = 2; // Enable verbose debug output
		$phpmailer->Debugoutput = function($str, $level) {
			error_log('PHPMailer Debug [' . $level . ']: ' . $str);
		};
	}, 20, 1);
	
	// Send email
	$result = wp_mail($to, $subject, $message, $headers);
	
	// Get PHPMailer instance after sending
	global $phpmailer;
	
	// Log result
	if ($result) {
		error_log('✅ wp_mail() returned: true');
		error_log('Result: true');
	} else {
		error_log('❌ wp_mail() returned: false');
		error_log('Result: false');
	}
	
	// Always check PHPMailer for errors (even if wp_mail returned true)
	if (isset($phpmailer)) {
		error_log('PHPMailer ErrorInfo: ' . ($phpmailer->ErrorInfo ?: 'No error info'));
		error_log('PHPMailer Host: ' . ($phpmailer->Host ?: 'Not set'));
		error_log('PHPMailer Mailer: ' . ($phpmailer->Mailer ?: 'mail()'));
		
		if (!empty($phpmailer->ErrorInfo)) {
			error_log('⚠️ WARNING: PHPMailer has error info even though wp_mail() returned true!');
			error_log('PHPMailer Error: ' . $phpmailer->ErrorInfo);
		}
	} else {
		error_log('⚠️ WARNING: PHPMailer global not available');
	}
	
	// Check if mail function exists
	if (!function_exists('mail')) {
		error_log('⚠️ WARNING: PHP mail() function is not available!');
	}
	
	// Log last error if available
	$last_error = error_get_last();
	if ($last_error && $last_error['type'] === E_ERROR) {
		error_log('Last PHP Error: ' . print_r($last_error, true));
	}
	
	error_log('=== EMAIL TEST END ===');
	
	// Show simple message only to admin
	$message = 'Email test completed. Check error_log for details.<br><br>';
	
	if ($result) {
		$message .= '<p style="color: orange;"><strong>⚠️ Note:</strong> wp_mail() returned true, but email may not be delivered.</p>';
		$message .= '<p>Possible issues:</p>';
		$message .= '<ul>';
		$message .= '<li>Email may be in spam folder</li>';
		$message .= '<li>Server may not be configured for mail delivery</li>';
		$message .= '<li>SPF/DKIM records may not be set up</li>';
		$message .= '<li>Consider using SMTP plugin for better delivery</li>';
		$message .= '</ul>';
		$message .= '<p><strong>From:</strong> ' . esc_html($from_email) . '</p>';
		$message .= '<p><strong>To:</strong> ' . esc_html($to) . '</p>';
	}
	
	$message .= '<br><a href="' . esc_url(home_url()) . '">← Back to home</a>';
	
	wp_die(
		$message,
		'Email Test',
		array('response' => 200)
	);
}

add_action('template_redirect', 'test_email_functionality');

/**
 * Override WordPress default email sender address
 * Prevents WordPress from using wordpress@$sitename and uses no-reply@odysseia-transport.com instead
 */
add_filter( 'wp_mail_from', function( $from_email ) {
	// Override default WordPress email address
	return 'no-reply@odysseia-transport.com';
}, 10, 1 );

/**
 * Override WordPress default email sender name
 */
add_filter( 'wp_mail_from_name', function( $from_name ) {
	// Override default WordPress sender name
	return 'TMS';
}, 10, 1 );

/**
 * Test email with attachments from Wasabi
 * Usage: ?test_email_attachments=1 (admin only)
 * Tests downloading files from Wasabi and attaching them to email
 */
function test_email_with_attachments() {
	if (!isset($_GET['test_email_attachments']) || $_GET['test_email_attachments'] !== '1') {
		return;
	}
	
	// Check if user is admin
	if (!current_user_can('administrator')) {
		return; // Silent return for non-admins
	}
	
	$to = 'milchenko2k16@gmail.com';
	$subject = 'Test Email with Attachments from Wasabi';
	$current_time = current_time('mysql');
	$site_name = get_bloginfo('name');
	$site_url = home_url();
	
	// File IDs to test
	$file_ids = array( 68766, 68765 );
	
	$message = '
		<html>
		<head>
			<title>Test Email with Attachments</title>
		</head>
		<body>
			<h2>Test Email with Attachments</h2>
			<p>This is a test email to verify file attachment functionality from Wasabi.</p>
			<p><strong>Time:</strong> ' . $current_time . '</p>
			<p><strong>Site:</strong> ' . $site_name . '</p>
			<p><strong>URL:</strong> ' . $site_url . '</p>
			<p><strong>File IDs:</strong> ' . implode( ', ', $file_ids ) . '</p>
			<hr>
			<p>This email should have 2 file attachments.</p>
		</body>
		</html>
	';
	
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: TMS <no-reply@odysseia-transport.com>'
	);
	
	// Log debug info
	error_log('=== EMAIL ATTACHMENTS TEST START ===');
	error_log('To: ' . $to);
	error_log('Subject: ' . $subject);
	error_log('File IDs: ' . implode( ', ', $file_ids ) );
	
	// Create temporary uploads directory if it doesn't exist
	$upload_dir = wp_upload_dir();
	$tmp_uploads_dir = $upload_dir['basedir'] . '/tmp-uploads';
	if ( ! file_exists( $tmp_uploads_dir ) ) {
		$dir_created = wp_mkdir_p( $tmp_uploads_dir );
		if ( ! $dir_created ) {
			error_log( 'Failed to create temporary uploads directory: ' . $tmp_uploads_dir );
		} else {
			error_log( 'Created temporary uploads directory: ' . $tmp_uploads_dir );
		}
	}
	
	// Helper function to download file from Wasabi/CDN to temporary directory
	$download_file_to_tmp = function( $attachment_id, $tmp_dir ) {
		error_log( 'Processing file ID: ' . $attachment_id );
		
		// First try to get local file path
		$local_path = get_attached_file( $attachment_id );
		error_log( 'Local path from get_attached_file: ' . ( $local_path ?: 'empty' ) );
		
		// Check if path contains S3 protocol (s3://, s3eucentral1://, etc.)
		// If it does, we need to download the file, not use the S3 path
		$is_s3_path = false;
		if ( $local_path && ( strpos( $local_path, 's3://' ) === 0 || strpos( $local_path, 's3' ) === 0 && strpos( $local_path, '://' ) !== false ) ) {
			$is_s3_path = true;
			error_log( 'Path is S3 protocol, will download from URL' );
		}
		
		// Only use local path if it's a real file path (not S3) and file exists
		if ( ! $is_s3_path && $local_path && file_exists( $local_path ) && is_readable( $local_path ) ) {
			error_log( 'File exists locally, using: ' . $local_path );
			return $local_path;
		}
		
		// File not found locally or is S3 path, try to download from Wasabi/CDN
		$file_url = wp_get_attachment_url( $attachment_id );
		error_log( 'File URL from wp_get_attachment_url: ' . ( $file_url ?: 'empty' ) );
		
		if ( ! $file_url ) {
			error_log( 'Failed to get file URL for attachment ID: ' . $attachment_id );
			return false;
		}
		
		// Get file name from attachment
		$file_name = basename( get_attached_file( $attachment_id ) );
		if ( empty( $file_name ) ) {
			// Fallback: extract filename from URL
			$file_name = basename( parse_url( $file_url, PHP_URL_PATH ) );
		}
		
		if ( empty( $file_name ) ) {
			$file_name = 'file_' . $attachment_id;
		}
		
		error_log( 'File name: ' . $file_name );
		
		// Create unique filename to avoid conflicts
		$tmp_file_name = $attachment_id . '_' . time() . '_' . $file_name;
		$tmp_file_path = $tmp_dir . '/' . $tmp_file_name;
		
		error_log( 'Temporary file path: ' . $tmp_file_path );
		
		// Download file from URL
		error_log( 'Downloading file from URL: ' . $file_url );
		$response = wp_remote_get( $file_url, array(
			'timeout' => 30,
		) );
		
		if ( is_wp_error( $response ) ) {
			error_log( 'Failed to download file from Wasabi: ' . $response->get_error_message() );
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		error_log( 'Response code: ' . $response_code );
		
		if ( $response_code !== 200 ) {
			error_log( 'Failed to download file from Wasabi. Response code: ' . $response_code );
			return false;
		}
		
		// Get file content and save to temporary file
		$file_content = wp_remote_retrieve_body( $response );
		if ( empty( $file_content ) ) {
			error_log( 'Downloaded file content is empty' );
			return false;
		}
		
		error_log( 'File content size: ' . strlen( $file_content ) . ' bytes' );
		
		// Write file content to temporary file
		$file_written = file_put_contents( $tmp_file_path, $file_content );
		if ( $file_written === false ) {
			error_log( 'Failed to write file to temporary directory: ' . $tmp_file_path );
			return false;
		}
		
		error_log( 'File written: ' . $file_written . ' bytes to ' . $tmp_file_path );
		
		// Verify file was written and is readable
		if ( ! file_exists( $tmp_file_path ) || ! is_readable( $tmp_file_path ) ) {
			error_log( 'Downloaded file does not exist or is not readable: ' . $tmp_file_path );
			return false;
		}
		
		error_log( 'Successfully downloaded file from Wasabi to: ' . $tmp_file_path . ' (Size: ' . $file_written . ' bytes)' );
		
		return $tmp_file_path;
	};
	
	// Download files to temporary directory
	$attachments = array();
	$temp_files = array(); // Track temporary files
	
	foreach ( $file_ids as $file_id ) {
		error_log( 'Processing file ID: ' . $file_id );
		$file_path = $download_file_to_tmp( $file_id, $tmp_uploads_dir );
		
		if ( $file_path && file_exists( $file_path ) && is_readable( $file_path ) ) {
			$attachments[] = $file_path;
			// Mark as temporary if it's in tmp-uploads directory
			if ( strpos( $file_path, $tmp_uploads_dir ) !== false ) {
				$temp_files[] = $file_path;
			}
			error_log( 'File added to attachments: ' . $file_path );
		} else {
			error_log( 'Failed to get file path for ID: ' . $file_id );
		}
	}
	
	error_log( 'Total attachments: ' . count( $attachments ) );
	error_log( 'Total temporary files: ' . count( $temp_files ) );
	
	// Send email
	error_log( 'Sending email with ' . count( $attachments ) . ' attachments...' );
	$result = false;
	if ( ! empty( $attachments ) ) {
		$result = wp_mail( $to, $subject, $message, $headers, $attachments );
	} else {
		$result = wp_mail( $to, $subject, $message, $headers );
	}
	
	error_log( 'Email send result: ' . ( $result ? 'true' : 'false' ) );
	
	// Log attachment info
	foreach ( $attachments as $index => $attachment_path ) {
		$file_size = file_exists( $attachment_path ) ? filesize( $attachment_path ) : 0;
		error_log( 'Attachment ' . ( $index + 1 ) . ': ' . $attachment_path . ' (Size: ' . $file_size . ' bytes)' );
	}
	
	// NOTE: For testing, we do NOT delete temporary files
	// In production, they should be deleted after email is sent
	error_log( 'Temporary files kept for inspection (NOT deleted for testing):' );
	foreach ( $temp_files as $temp_file ) {
		error_log( '  - ' . $temp_file );
	}
	
	error_log('=== EMAIL ATTACHMENTS TEST END ===');
	
	// Show result to admin
	$result_message = 'Email attachments test completed. Check error_log for details.<br><br>';
	
	if ( $result ) {
		$result_message .= '<p style="color: green;"><strong>✅ Email sent successfully!</strong></p>';
	} else {
		$result_message .= '<p style="color: red;"><strong>❌ Email sending failed!</strong></p>';
	}
	
	$result_message .= '<p><strong>Attachments:</strong> ' . count( $attachments ) . '</p>';
	$result_message .= '<ul>';
	foreach ( $attachments as $attachment_path ) {
		$file_size = file_exists( $attachment_path ) ? filesize( $attachment_path ) : 0;
		$result_message .= '<li>' . esc_html( basename( $attachment_path ) ) . ' (' . size_format( $file_size ) . ')</li>';
	}
	$result_message .= '</ul>';
	
	$result_message .= '<p><strong>To:</strong> ' . esc_html( $to ) . '</p>';
	$result_message .= '<p><strong>From:</strong> no-reply@odysseia-transport.com</p>';
	
	if ( ! empty( $temp_files ) ) {
		$result_message .= '<p style="color: orange;"><strong>⚠️ Note:</strong> Temporary files were NOT deleted for testing purposes.</p>';
		$result_message .= '<p>Temporary files location: ' . esc_html( $tmp_uploads_dir ) . '</p>';
	}
	
	$result_message .= '<br><a href="' . esc_url( home_url() ) . '">← Back to home</a>';
	
	wp_die(
		$result_message,
		'Email Attachments Test',
		array( 'response' => 200 )
	);
}

add_action( 'template_redirect', 'test_email_with_attachments' );