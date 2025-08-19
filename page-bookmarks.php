<?php
/**
 * Template Name: Page bookmarks
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

// Проверяем доступ к FLT
$flt_user_access = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin = current_user_can( 'administrator' );
$show_flt_tabs = $flt_user_access || $is_admin;
// Определяем тип данных для загрузки
$type = get_field_value( $_GET, 'type' );
$is_flt = $type === 'flt';

if ( $is_flt && ! $show_flt_tabs ) {
	// Если запрошен FLT но нет доступа - редиректим на обычную страницу
	wp_redirect( remove_query_arg( 'type' ) );
	exit;
}
$TMSUsers = new TMSUsers();

// Выбираем класс в зависимости от типа
if ( $is_flt ) {
	$reports  = new TMSReportsFlt();
} else {
	$reports  = new TMSReports();
}

$bookmarks = $TMSUsers->get_all_bookmarks( $is_flt );

$args = array(
	'status_post' => 'publish',
);

$items = $reports->get_favorites( $bookmarks, $args );

$items[ 'hide_total' ] = true;
if ( $is_flt ) {
	$items[ 'flt' ] = true;
}

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-2 mt-3">Bookmarks</h2>
                        
                        <?php
                        echo esc_html( get_template_part( TEMPLATE_PATH . 'common/flt', 'tabs', array( 'show_flt_tabs' => $show_flt_tabs, 'is_flt' => $is_flt ) ) );
                        ?>
						
						<?php
						if ( $TMSUsers->check_user_role_access( array( 'billing', 'accounting' ), true ) ) {
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-accounting', $items ) );
						} else if ( $TMSUsers->check_user_role_access( array( 'tracking', 'morning_tracking', 'nightshift_tracking' ), true ) ) {
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-tracking', $items ) );
						} else {
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table', $items ) );
						}
						?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
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
