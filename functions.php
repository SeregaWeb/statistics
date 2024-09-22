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

// required files.
require THEME_DIR . '/src/inc/class-wp-rock.php';

require THEME_DIR . '/src/inc/class-tms-reports-icons.php';
require THEME_DIR . '/src/inc/class-tms-reports-helper.php';
require THEME_DIR . '/src/inc/class-tms-reports.php';
require THEME_DIR . '/src/inc/class-tms-users.php';
require THEME_DIR . '/src/inc/class-tms-reports-company.php';
require THEME_DIR . '/src/inc/class-tms-reports-shipper.php';

require THEME_DIR . '/src/inc/initial-setup.php';
require THEME_DIR . '/src/inc/enqueue-scripts.php';
require THEME_DIR . '/src/inc/wpeditor-formats-options.php';
require THEME_DIR . '/src/inc/analytics-settings.php';
require THEME_DIR . '/src/inc/acf-setting.php';
require THEME_DIR . '/src/inc/custom-posts-type.php';
require THEME_DIR . '/src/inc/custom-taxonomies.php';
require THEME_DIR . '/src/inc/woocommerce-customization.php';
require THEME_DIR . '/src/inc/class-wp-rock-blocks.php';
require THEME_DIR . '/src/inc/ajax-requests.php';
require THEME_DIR . '/src/inc/custom-accept-cookies.php';
require THEME_DIR . '/src/inc/custom-hooks.php';
require THEME_DIR . '/src/inc/custom-shortcodes.php';
require THEME_DIR . '/src/inc/class-mobile-detect.php';

