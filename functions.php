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

// required files.
require THEME_DIR . '/src/inc/class-wp-rock.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

require THEME_DIR . '/src/inc/core/class-tms-reports-icons.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-helper.php';
require THEME_DIR . '/src/inc/core/class-tms-auth.php';
require THEME_DIR . '/src/inc/core/class-tms-reports.php';
require THEME_DIR . '/src/inc/core/class-tms-users.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-statistics.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-company.php';
require THEME_DIR . '/src/inc/core/class-tms-reports-shipper.php';
require THEME_DIR . '/src/inc/core/class-tms-emails.php';
require THEME_DIR . '/src/inc/core/class-tms-logs.php';
require THEME_DIR . '/src/inc/core/class-tms-performance.php';
require THEME_DIR . '/src/inc/core/class-tms-generate-document.php';

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

function disable_canonical_redirect_for_paged( $redirect_url ) {
	if ( is_paged() && strpos( $redirect_url, '/page/' ) !== false ) {
		return false;
	}
	
	return $redirect_url;
}

function test_email() {
	
	$html_body = "<html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
                background-color: #f9f9f9;
                text-align: center;
            }
            .email-header {
                font-size: 20px;
                font-weight: bold;
                color: #000000;
                max-width: 600px;
                margin: 0 auto 20px;
                text-align: center;
                
            }
            .email-body {
                font-size: 16px;
                color: #444;
            }
            .email-footer {
                max-width: 600px;
                margin: 20px auto;
                font-size: 14px;
                color: #777;
                text-align: left;
            }
            
            .email-logo {
            	text-align: center;
            }
            
            .email-logo-image {
            	width: 180px;
            	height: auto;
            }
            
            .text {
                color: #000000;
            	margin: 0;
            }
            
        </style>
    </head>
    <body>
    
    	<div class='email-logo'>
    		<img class='email-logo-image' src='https://www.endurance-tms.com/wp-content/uploads/logos/odysseia.png' alt='logo'>
		</div>
    
        <div class='email-header'>Tracking email chain: Load # 848761 Nikolaev AL - Nikolaev AL </div>
                                                        
                                                        <div class='email-container'>
            <div class='email-body'>
                <p>Thank you for running this load with Odysseia.
		<br>Our team will keep you updated during the whole process of transportation in this email thread.
		<br>If you need to add any other email for the updates, please feel free to do that.
		<br>We will immediately let you know once the truck is on-site.</p>
            </div>
        </div>
      	<div class='email-footer'>
            	<p class='text'>Email: <a href='mailto:tracking@odysseia.one'>tracking@odysseia.one</a></p><p class='text'>Phone: (667) 239-7805</p>
        </div>
    </body>
    </html>";
	
	wp_mail( 'milchenko2k16@gmail.com', 'Tracking email chain: Load # 848761 Nikolaev AL - Nikolaev AL', $html_body, array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Tracking chain <tracking@endurance-tms.com>'
	) );
}

function test_email_login() {
	$html_body = "
	<html>
		<head>
			<style>
				body {
					font-family: Arial, sans-serif;
					line-height: 1.6;
					color: #333;
					padding: 0;
					max-width: 600px;
					margin: 0 auto;
				}
				.email-container {
					max-width: 600px;
					margin: 20px auto;
					padding: 20px;
					text-align: left;
				}
				.email-body {
					font-size: 16px;
					color: #444;
				}
				.email-footer {
					max-width: 600px;
					margin: 20px auto;
					font-size: 14px;
					color: #777;
					text-align: left;
				}
				
				.text-danger {
					color: rgb(255,72,0);
				}
				
				.email-body-text {
					margin: 0;
					line-height: 2;
				}
				.email-logo {
					text-align: left;
					padding: 0 20px;
				}
				.email-logo-image {
					width: 180px;
					height: auto;
				}
				.text {
					color: #000000;
					margin: 0;
				}
				
				.separator {
				margin-top: 20px;
				margin-bottom: 20px;
				}
			</style>
		</head>
		<body>
			<div class='email-logo'>
				<img class='email-logo-image' src='https://www.endurance-tms.com/wp-content/uploads/logos/odysseia.png' alt='logo'>
			</div>
			
			<hr class='separator'>
			
			<div class='email-container'>
				<div class='email-body'>
					<strong class='email-body-text'>Dear John Doe,</strong>
					<p class='email-body-text'> To complete your login, enter the following verification code:</p>
					<strong class='email-body-text'>123456</strong>
					<p class='email-body-text'>This code will expire in 15 minutes.</p>
					<p class='email-body-text'>If you were not expecting this code, <a class='text-danger' href='mailto:operations@odysseia.one'>contact</a> the administrator immediately.</p>
					<br>
					<p class='email-body-text'>Thank you.</p>
				</div>
			</div>
		</body>
	</html>";
	
	echo $html_body;
}

//add_action( 'init', function() {
//	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
////		test_email();
//		test_email_login();
//	}
//} );

add_filter( 'redirect_canonical', 'disable_canonical_redirect_for_paged' );
