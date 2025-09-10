<?php
/**
 * Template Name: Page loads accounting unapplied
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

global $global_options, $report_data;

// Проверяем доступ к FLT
$flt_user_access = get_field( 'flt', 'user_' . get_current_user_id() );
$is_admin        = current_user_can( 'administrator' );
$show_flt_tabs   = $flt_user_access || $is_admin;

// Определяем тип данных для загрузки
$type   = get_field_value( $_GET, 'type' );
$is_flt = $type === 'flt';


// Выбираем класс в зависимости от типа
if ( $is_flt ) {
	$reports = new TMSReportsFlt();
} else {
	$reports = new TMSReports();
}

$link_broker = get_field_value( $global_options, 'single_page_broker' ) ?? '';


$args = array(
	'status_post'              => 'publish',
	'per_page_loads'           => 1000,
	'sort_by'                  => 'load_problem',
	'sort_order'               => 'asc',
	'exclude_factoring_status' => array( 'processed', 'paid' ),
);

$args  = $reports->set_filter_unapplied( $args );
$items = $reports->get_table_items_unapplied( $args );

if ( is_array( $items ) && ! empty( $items ) ) {
	$post_tp               = 'accounting';
	$items[ 'page_type' ]  = $post_tp;
	$items[ 'ar_problem' ] = true;
	if ( $is_flt ) {
		$items[ 'flt' ] = true;
	}
}
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mb-3 mt-3">
						
						<?php if ( $is_flt && ! $show_flt_tabs ):
							echo $reports->message_top( 'danger', $reports->messages_prepare( 'not-access' ) );
						else: ?>
							
							<?php
							echo esc_html( get_template_part( TEMPLATE_PATH . 'common/flt', 'tabs', array(
								'show_flt_tabs' => $show_flt_tabs,
								'is_flt'        => $is_flt
							) ) );
							?>

                            <ul class="nav nav-pills" id="pills-tab" role="tablist">
                                <li class="nav-item w-25" role="presentation">
                                    <button class="nav-link w-100 active" id="pills-info-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-info" type="button" role="tab"
                                            aria-controls="pills-info"
                                            aria-selected="true">Direct Invoicing & Unapplied Payments
                                    </button>
                                </li>
                                <li class="nav-item w-25" role="presentation">
                                    <button class="nav-link w-100" id="pills-update-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-update" type="button" role="tab"
                                            aria-controls="pills-update" aria-selected="false">Debt
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-info" role="tabpanel"
                                     aria-labelledby="pills-info-tab">
									<?php
									$template_part_filter = get_template_part( TEMPLATE_PATH . 'filters/report', 'filter-unapplied' );
									if ( $template_part_filter ) {
										echo esc_html( $template_part_filter );
									}
									
									$template_part_table = get_template_part( TEMPLATE_PATH . 'tables/report', 'table-unapplied', $items );
									if ( $template_part_table ) {
										echo esc_html( $template_part_table );
									}
									?>
                                </div>

                                <div class="tab-pane fade" id="pills-update" role="tabpanel"
                                     aria-labelledby="pills-update-tab">

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
										$total_overall         = 0;
										$statuses_totals       = [
											'factoring-delayed-advance' => 0,
											'unapplied-payment'         => 0,
											'in-processing'             => 0,
											'pending-to-tafs'           => 0,
											'fraud'                     => 0,
											'company-closed'            => 0,
										];
										
										if ( is_array( $report_data ) && ! empty( $report_data ) ) :
											foreach ( $report_data as $key => $report ) :
												if ( isset( $report[ 'statuses' ] ) && is_array( $report[ 'statuses' ] ) ) :
													$company_total = 0;
													$id_broker = str_replace( 'broker_name_', '', $key );
													
													?>
                                                    <tr>
                                                        <td>
                                                            <a href="<?php echo $link_broker . '?broker_id=' . $id_broker; ?>"><?php echo esc_html( $report[ 'name' ] ?? '' ); ?></a>
                                                            <br>
                                                            <span class="text-small"><?php echo esc_html( $report[ 'mc' ] ?? '' ); ?></span>
                                                        </td>
                                                        <td>
															<?php
															$statuses_labels                        = $reports->statuses_factoring_labels;
															
															foreach ( $statuses_labels as $status_key => $label ) :
																if ( isset( $report[ 'statuses' ][ $status_key ] ) ) :
																	$value = $report[ 'statuses' ][ $status_key ];
																	$formatted_value                = $reports->format_currency( $value );
																	$company_total                  += $value;
																	$statuses_totals[ $status_key ] += $value;
																	?>
                                                                    <span><?php echo esc_html( $label ); ?>: $<?php echo esc_html( $formatted_value ); ?></span>
                                                                    <br>
																<?php
																endif;
															endforeach;
															?>
                                                        </td>
                                                        <td>
															<?php
															echo '$' . esc_html( $reports->format_currency( $company_total ) );
															?>
                                                        </td>
                                                    </tr>
													<?php
													$total_overall += $company_total;
												endif;
											endforeach;
										endif;
										?>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="2" style="text-align: right;"><strong>Total Overall:</strong>
                                            </td>
                                            <td>
                                                <strong>
													<?php
													echo '$' . esc_html( $reports->format_currency( $total_overall ) );
													?>
                                                </strong>
                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>

                                    <div class="d-flex gap-3">
										
										<?php if ( isset( $statuses_labels ) && is_array( $statuses_labels ) ): ?>
                                            <table class="table mb-5 mt-5 w-50">
                                                <thead>
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                </tr>
                                                </thead>
                                                <tbody>
												<?php
												foreach ( $statuses_labels as $status_key => $label ) :
													if ( isset( $statuses_totals[ $status_key ] ) && $statuses_totals[ $status_key ] > 0 ) {
														?>
                                                        <tr>
                                                            <td><?php echo esc_html( $label ); ?></td>
                                                            <td>
																<?php
																echo '$' . esc_html( $reports->format_currency( $statuses_totals[ $status_key ] ) );
																?>
                                                            </td>
                                                        </tr>
													<?php }
												endforeach; ?>
                                                </tbody>
                                            </table>
										<?php endif; ?>
										
										<?php if ( isset( $report_data[ 'all_paid_without_two_status' ] ) ): ?>
                                            <table class="table mb-5 mt-5 w-50">
                                                <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Total</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>Total without "Processing" and "Paid"</td>
                                                    <td>
														<?php
														echo '$' . esc_html( $reports->format_currency( $report_data[ 'all_paid_without_two_status' ] ) );
														?>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
										<?php endif; ?>

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
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
