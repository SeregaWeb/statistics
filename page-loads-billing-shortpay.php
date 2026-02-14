<?php
/**
 * Template Name: Page loads billing short pay
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

global $global_options;

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
$stats = $reports->get_shortpay_stats_by_broker( $args );

// Pre-fetch brokers for Total tab (same approach as report-table-billing-shortpay) to avoid N+1.
$TMSBroker        = new TMSReportsCompany();
$stats_broker_ids = array();
if ( is_array( $stats ) && ! empty( $stats ) ) {
	$stats_broker_ids = array_unique( array_filter( array_map( function( $row ) {
		return isset( $row['customer_id'] ) ? (int) $row['customer_id'] : 0;
	}, $stats ) ) );
}
$companies_by_id_stats = ! empty( $stats_broker_ids ) ? $TMSBroker->get_companies_by_ids( $stats_broker_ids ) : array();
$brokers_by_id_stats   = ! empty( $stats_broker_ids ) ? $TMSBroker->get_brokers_data_by_ids( $stats_broker_ids, $companies_by_id_stats ) : array();

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

                        <ul class="nav nav-pills" id="pills-tab" role="tablist">
                            <li class="nav-item w-25" role="presentation">
                                <button class="nav-link w-100 active" id="pills-info-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-info" type="button" role="tab"
                                        aria-controls="pills-info" aria-selected="true">Charge back & Short pay
                                </button>
                            </li>
                            <li class="nav-item w-25" role="presentation">
                                <button class="nav-link w-100" id="pills-update-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-update" type="button" role="tab"
                                        aria-controls="pills-update" aria-selected="false">Total
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-info" role="tabpanel"
                                 aria-labelledby="pills-info-tab">
                                <?php
                                echo esc_html( get_template_part( TEMPLATE_PATH . 'filters/report', 'filter', array( 'post_type' => $post_tp ) ) );
                                ?>

                                <?php
                                echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-billing-shortpay', $items ) );
                                ?>
                            </div>

                            <div class="tab-pane fade" id="pills-update" role="tabpanel"
                                 aria-labelledby="pills-update-tab">

                                <?php $link_broker = get_field_value( $global_options, 'single_page_broker' ) ?? ''; ?>

                                <table class="table mb-5 mt-5 w-100">
                                    <thead>
                                    <tr>
                                        <th scope="col" style="width: 200px;" title="broker">Broker</th>
                                        <th scope="col">Debt</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $total_overall   = 0;
                                    $statuses_totals = [
                                        'charge-back' => 0,
                                        'short-pay'   => 0,
                                    ];

                                    if ( is_array( $stats ) && ! empty( $stats ) ) :
                                        foreach ( $stats as $row ) :
                                            $company_total = 0;
                                            $id_broker     = intval( $row['customer_id'] );
                                            $broker_data   = isset( $brokers_by_id_stats[ $id_broker ] ) ? $brokers_by_id_stats[ $id_broker ] : array();
                                            $name          = $broker_data['name'] ?? '';
                                            $mc            = $broker_data['mc'] ?? '';
                                            $charge_back_v = floatval( $row['charge_back_total'] );
                                            $short_pay_v   = floatval( $row['short_pay_total'] );
                                            ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo $link_broker ? $link_broker . '?broker_id=' . $id_broker : '#'; ?>"><?php echo esc_html( $name ); ?></a>
                                                        <br>
                                                        <span class="text-small"><?php echo esc_html( $mc ); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $formatted_cb = $reports->format_currency( $charge_back_v );
                                                        $formatted_sp = $reports->format_currency( $short_pay_v );
                                                        $company_total += ( $charge_back_v + $short_pay_v );
                                                        $statuses_totals['charge-back'] += $charge_back_v;
                                                        $statuses_totals['short-pay']   += $short_pay_v;
                                                        ?>
                                                        <span>Charge back: $<?php echo esc_html( $formatted_cb ); ?></span><br>
                                                        <span>Short pay: $<?php echo esc_html( $formatted_sp ); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo '$' . esc_html( $reports->format_currency( $company_total ) ); ?>
                                                    </td>
                                                </tr>
                                                <?php $total_overall += $company_total; ?>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="2" style="text-align: right;"><strong>Total Overall:</strong></td>
                                        <td>
                                            <strong>
                                                <?php echo '$' . esc_html( $reports->format_currency( $total_overall ) ); ?>
                                            </strong>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>

                                <div class="d-flex gap-3">
                                    <table class="table mb-5 mt-5 w-50">
                                        <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Total</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $labels = [
                                            'charge-back' => 'Charge back',
                                            'short-pay'   => 'Short pay',
                                        ];
                                        foreach ( $labels as $status_key => $label ) :
                                            if ( isset( $statuses_totals[ $status_key ] ) && $statuses_totals[ $status_key ] > 0 ) :
                                                ?>
                                                <tr>
                                                    <td><?php echo esc_html( $label ); ?></td>
                                                    <td><?php echo '$' . esc_html( $reports->format_currency( $statuses_totals[ $status_key ] ) ); ?></td>
                                                </tr>
                                            <?php endif; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

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
