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

use Mpdf\Mpdf;

require THEME_DIR . '/src/inc/core/class-map-controller.php';
require THEME_DIR . '/src/inc/core/class-tms-common-helper.php';
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
require THEME_DIR . '/src/inc/core/class-tms-drivers-helper.php';
require THEME_DIR . '/src/inc/core/class-tms-drivers.php';
require THEME_DIR . '/src/inc/core/class-tms-contacts.php';

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
