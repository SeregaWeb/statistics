<?php
/**
 * Drivers Statistics Template
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$TMSReports = new TMSReports();
$TMSDrivers = new TMSDrivers();
$statistics = $TMSDrivers->get_statistics();
$TMSUsers   = new TMSUsers();

// Initialize totals
$total_all        = 0;
$tanker_all       = 0;
$twic_all         = 0;
$hazmat_all       = 0;
$cargo_van_all    = 0;
$sprinter_van_all = 0;
$box_truck_all    = 0;
$reefer_all       = 0;

?>

<div class="row w-100 statistics-styles">
	<div class="col-12">
		<h2 class="mb-3">HR</h2>
	</div>
	
	<?php if ( is_array( $statistics ) && ! empty( $statistics ) ) : ?>
		
		<div class="tracking-statistics__wrapper order-2">
			<?php foreach ( $statistics as $statistic ) : ?>
				<?php
				// Calculate totals
				$total_all += (int) $statistic['total'];
				$tanker_all += (int) $statistic['tanker_on'];
				$twic_all += (int) $statistic['twic_on'];
				$hazmat_all += (int) $statistic['hazmat_on'];
				$cargo_van_all += (int) $statistic['cargo_van'];
				$sprinter_van_all += (int) $statistic['sprinter_van'];
				$box_truck_all += (int) $statistic['box_truck'];
				$reefer_all += (int) $statistic['reefer'];
				
				// Check if user has any meaningful statistics
				$has_stats = false;
				$stat_values = array(
					$statistic['cargo_van'],
					$statistic['sprinter_van'],
					$statistic['box_truck'],
					$statistic['reefer'],
					$statistic['hazmat_on'],
					$statistic['twic_on'],
					$statistic['tanker_on']
				);
				
				foreach ( $stat_values as $value ) {
					if ( (int) $value > 0 ) {
						$has_stats = true;
						break;
					}
				}
				
				// Only display card if user has statistics
				if ( $has_stats ) :
				?>
					<div class="mb-2 card-hr">
						<div class="flex-column d-flex">
							<div class="justify-content-center d-flex">
								<?php if ( isset( $statistic['user_id_added'] ) && $statistic['user_id_added'] ) : ?>
									<?php
									$user = $TMSUsers->get_user_full_name_by_id( $statistic['user_id_added'] );
									
									// Get user color with fallback
									$color_initials = '#030303';
									if ( $user ) {
										$user_color = get_field( 'initials_color', 'user_' . $statistic['user_id_added'] );
										if ( $user_color ) {
											$color_initials = $user_color;
										}
									} else {
										$user = array(
											'full_name' => 'User not found',
											'initials'  => 'NF'
										);
									}
									?>
									<p class="card-tracking-stats__user"
									   title="<?php echo esc_attr( $user['full_name'] ); ?>"
									   style="background-color: <?php echo esc_attr( $color_initials ); ?>;">
										<?php echo esc_html( $user['initials'] ); ?>
									</p>
								<?php endif; ?>
							</div>

							<div class="d-flex gap-1 justify-content-between list-groups">
								<ul class="list-group">
									<?php if ( isset( $statistic['cargo_van'] ) && (int) $statistic['cargo_van'] > 0 ) : ?>
										<li>Cargo van: <?php echo esc_html( $statistic['cargo_van'] ); ?></li>
									<?php endif; ?>
									<?php if ( isset( $statistic['sprinter_van'] ) && (int) $statistic['sprinter_van'] > 0 ) : ?>
										<li>Sprinter van: <?php echo esc_html( $statistic['sprinter_van'] ); ?></li>
									<?php endif; ?>
									<?php if ( isset( $statistic['box_truck'] ) && (int) $statistic['box_truck'] > 0 ) : ?>
										<li>Box truck: <?php echo esc_html( $statistic['box_truck'] ); ?></li>
									<?php endif; ?>
									<?php if ( isset( $statistic['reefer'] ) && (int) $statistic['reefer'] > 0 ) : ?>
										<li>Reefer: <?php echo esc_html( $statistic['reefer'] ); ?></li>
									<?php endif; ?>
								</ul>
								<ul class="list-group">
									<?php if ( isset( $statistic['hazmat_on'] ) && (int) $statistic['hazmat_on'] > 0 ) : ?>
										<li>Hazmat: <?php echo esc_html( $statistic['hazmat_on'] ); ?></li>
									<?php endif; ?>
									<?php if ( isset( $statistic['twic_on'] ) && (int) $statistic['twic_on'] > 0 ) : ?>
										<li>TWIC: <?php echo esc_html( $statistic['twic_on'] ); ?></li>
									<?php endif; ?>
									<?php if ( isset( $statistic['tanker_on'] ) && (int) $statistic['tanker_on'] > 0 ) : ?>
										<li>Tanker end: <?php echo esc_html( $statistic['tanker_on'] ); ?></li>
									<?php endif; ?>
								</ul>
								<ul class="list-group">
									<?php if ( isset( $statistic['total'] ) && (int) $statistic['total'] > 0 ) : ?>
										<li>Total: <?php echo esc_html( $statistic['total'] ); ?></li>
									<?php endif; ?>
								</ul>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<div class="col-12 order-1 mb-3">
			<div class="row">
				<div class="card-hr">
					<p class="card-tracking-stats__project">
						<?php echo esc_html( $TMSReports->project ); ?>
					</p>

					<div class="d-flex gap-1 justify-content-between list-groups">
						<ul class="list-group">
							<?php if ( $cargo_van_all > 0 ) : ?>
								<li>Cargo van: <?php echo esc_html( $cargo_van_all ); ?></li>
							<?php endif; ?>
							<?php if ( $sprinter_van_all > 0 ) : ?>
								<li>Sprinter van: <?php echo esc_html( $sprinter_van_all ); ?></li>
							<?php endif; ?>
							<?php if ( $box_truck_all > 0 ) : ?>
								<li>Box truck: <?php echo esc_html( $box_truck_all ); ?></li>
							<?php endif; ?>
							<?php if ( $reefer_all > 0 ) : ?>
								<li>Reefer: <?php echo esc_html( $reefer_all ); ?></li>
							<?php endif; ?>
						</ul>
						<ul class="list-group">
							<?php if ( $hazmat_all > 0 ) : ?>
								<li>Hazmat: <?php echo esc_html( $hazmat_all ); ?></li>
							<?php endif; ?>
							<?php if ( $twic_all > 0 ) : ?>
								<li>TWIC: <?php echo esc_html( $twic_all ); ?></li>
							<?php endif; ?>
							<?php if ( $tanker_all > 0 ) : ?>
								<li>Tanker end: <?php echo esc_html( $tanker_all ); ?></li>
							<?php endif; ?>
						</ul>
						<ul class="list-group">
							<?php if ( $total_all > 0 ) : ?>
								<li>Total: <?php echo esc_html( $total_all ); ?></li>
							<?php endif; ?>
						</ul>
					</div>
				</div>
				
				<div class="col-12 col-lg-8">
					<?php
					// Prepare chart data with only non-zero values
					$chart_data = array();
					
					if ( $cargo_van_all > 0 ) {
						$chart_data[] = array( 'label' => 'Cargo Van', 'value' => (int) $cargo_van_all );
					}
					if ( $sprinter_van_all > 0 ) {
						$chart_data[] = array( 'label' => 'Sprinter Van', 'value' => (int) $sprinter_van_all );
					}
					if ( $box_truck_all > 0 ) {
						$chart_data[] = array( 'label' => 'Box Truck', 'value' => (int) $box_truck_all );
					}
					if ( $reefer_all > 0 ) {
						$chart_data[] = array( 'label' => 'Reefer', 'value' => (int) $reefer_all );
					}
					
					$chart_json = json_encode( $chart_data );
					?>

					<?php if ( ! empty( $chart_data ) ) : ?>
						<div id="endorsementsChart" style="width:100%; height:300px;"></div>

						<script>
							const endorsementChartData = <?php echo $chart_json; ?>;
							console.log('endorsementChartData', endorsementChartData);
							
							window.document.addEventListener('DOMContentLoaded', () => {
								google.charts.load('current', { 'packages': ['corechart'] });
								google.charts.setOnLoadCallback(drawEndorsementsChart);
								
								function drawEndorsementsChart() {
									const dataArray = [['Type', 'Count']];
									
									endorsementChartData.forEach(row => {
										dataArray.push([row.label, row.value]);
									});
									
									const data = google.visualization.arrayToDataTable(dataArray);
									
									const options = {
										pieSliceText: 'value',
										legend: { position: 'right' },
									};
									
									const chart = new google.visualization.PieChart(document.getElementById('endorsementsChart'));
									chart.draw(data, options);
								}
							});
						</script>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
