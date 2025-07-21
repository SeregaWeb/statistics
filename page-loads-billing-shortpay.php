<?php
/**
 * Template Name: Page loads billing short pay
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



// Выбираем класс в зависимости от типа
if ( $is_flt ) {
	$reports = new TMSReportsFlt();
} else {
	$reports = new TMSReports();
}

$args = array(
	'status_post'              => 'publish',
	'exclude_factoring_status' => array( 'paid' ),
	'include_factoring_status' => array( 'short-pay', 'charge-back' ),
	'per_page_loads'           => 100,
);

$args  = $reports->set_filter_params( $args );
$items = $reports->get_table_items_billing_shortpay( $args );

$post_tp              = 'accounting';
$items[ 'page_type' ] = $post_tp;
if ( $is_flt ) {
	$items[ 'flt' ] = true;
}
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12  mt-3">
						
						<?php if ( $is_flt && ! $show_flt_tabs ):
							echo $reports->message_top( 'danger', $reports->messages_prepare( 'not-access' ) );
						else: ?>
                        
                        <?php
                        echo esc_html( get_template_part( TEMPLATE_PATH . 'common/flt', 'tabs', array( 'show_flt_tabs' => $show_flt_tabs, 'is_flt' => $is_flt ) ) );
                        ?>
                        
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter', array( 'post_type' => $post_tp ) ) );
						?>
						
						<?php
						echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-billing-shortpay', $items ) );
						?>

						<?php endif; ?>
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
