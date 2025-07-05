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

// AJAX: получить выходные всех диспетчеров выбранного пользователя
add_action('wp_ajax_get_dispatchers_weekends', function() {
    if (!current_user_can('edit_users')) {
        wp_send_json_error('Insufficient permissions');
    }
    $user_id = intval($_POST['user_id'] ?? 0);
    if (!$user_id) {
        wp_send_json_error('No user_id');
    }
    $days = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
    ];
    $result = [];
    foreach ($days as $day) {
        $field = 'exclude_' . $day;
        $value = get_field($field, 'user_' . $user_id);
        $result[$day] = is_array($value) ? array_map('strval', $value) : [];
    }
    wp_send_json_success($result);
});

add_action('wp_ajax_debug_save_weekends', function() {
    $move_from = intval($_POST['move-from'] ?? 0);
    $move_to = intval($_POST['move-to'] ?? 0);
    $dispatcher = str_replace('user_', '', $_POST['dispatcher'] ?? '');
    if (!$move_from || !$move_to || !$dispatcher) {
        wp_send_json_error('Недостаточно данных');
    }

    // Ключи ACF
    $team_key = 'field_66f9240398a70';
    $days = [
        'monday'    => 'field_684aafd1edc47',
        'tuesday'   => 'field_684ab094edc48',
        'wednesday' => 'field_684ab0c8edc49',
        'thursday'  => 'field_684ab0d8edc4a',
        'friday'    => 'field_684ab0e7edc4b',
        'saturday'  => 'field_684ab0f5edc4c',
        'sunday'    => 'field_684ab105edc4d',
    ];

    // 1. Удаляем диспетчера из команды move-from
    $from_team = get_field($team_key, 'user_' . $move_from);
    if (is_array($from_team)) {
        $from_team = array_map('strval', $from_team);
        $from_team = array_diff($from_team, [$dispatcher]);
        update_field($team_key, array_values($from_team), 'user_' . $move_from);
    }

    // 2. Удаляем диспетчера из всех exclude_* у move-from
    foreach ($days as $day => $field_key) {
        $ex = get_field($field_key, 'user_' . $move_from);
        if (is_array($ex)) {
            $ex = array_map('strval', $ex);
            $ex = array_diff($ex, [$dispatcher]);
            update_field($field_key, array_values($ex), 'user_' . $move_from);
        }
    }

    // 3. Добавляем диспетчера в команду move-to
    $to_team = get_field($team_key, 'user_' . $move_to);
    if (!is_array($to_team)) $to_team = [];
    $to_team = array_map('strval', $to_team);
    if (!in_array($dispatcher, $to_team, true)) {
        $to_team[] = $dispatcher;
        update_field($team_key, array_values($to_team), 'user_' . $move_to);
    }

    // 4. Добавляем диспетчера в exclude_* move-to по отмеченным дням
    foreach ($days as $day => $field_key) {
        $key = 'exclude_' . $day . '_user_' . $dispatcher;
        $checked = !empty($_POST[$key]);
        $ex = get_field($field_key, 'user_' . $move_to);
        if (!is_array($ex)) $ex = [];
        $ex = array_map('strval', $ex);

        if ($checked && !in_array($dispatcher, $ex, true)) {
            $ex[] = $dispatcher;
        } elseif (!$checked && in_array($dispatcher, $ex, true)) {
            $ex = array_diff($ex, [$dispatcher]);
        }
        update_field($field_key, array_values($ex), 'user_' . $move_to);
    }

    wp_send_json_success(array('success' => true, 'message' => 'Moved successfully'));
});
