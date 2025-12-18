<?php
/**
 * Capabilities & Endorsements Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_drivers_int = (int) $total_drivers;

?>

<div class="col-12 mb-3">
	<div class="row">
		<?php
		if ( $total_drivers_int > 0 ) :
			// Define capabilities with colors
			$capabilities = array();
			if ( $twic_all > 0 ) {
				$capabilities[] = array( 'name' => 'TWIC', 'count' => $twic_all, 'color' => '#0d6efd' );
			}
			if ( $tsa_all > 0 ) {
				$capabilities[] = array( 'name' => 'TSA', 'count' => $tsa_all, 'color' => '#198754' );
			}
			if ( $tanker_all > 0 ) {
				$capabilities[] = array( 'name' => 'Tanker', 'count' => $tanker_all, 'color' => '#fd7e14' );
			}
			if ( $change_9_all > 0 ) {
				$capabilities[] = array( 'name' => 'Change 9', 'count' => $change_9_all, 'color' => '#6f42c1' );
			}
			if ( $hazmat_cdl_all > 0 ) {
				$capabilities[] = array( 'name' => 'Hazmat CDL', 'count' => $hazmat_cdl_all, 'color' => '#dc3545' );
			}
			if ( $hazmat_certificate_all > 0 ) {
				$capabilities[] = array( 'name' => 'Hazmat Certificate', 'count' => $hazmat_certificate_all, 'color' => '#e83e8c' );
			}
			if ( $sleeper_all > 0 ) {
				$capabilities[] = array( 'name' => 'Sleepers', 'count' => $sleeper_all, 'color' => '#20c997' );
			}
			if ( $printer_all > 0 ) {
				$capabilities[] = array( 'name' => 'Printers', 'count' => $printer_all, 'color' => '#6610f2' );
			}
			if ( isset( $canada_all ) && $canada_all > 0 ) {
				$capabilities[] = array( 'name' => 'Canada', 'count' => $canada_all, 'color' => '#0dcaf0' );
			}
			if ( isset( $mexico_all ) && $mexico_all > 0 ) {
				$capabilities[] = array( 'name' => 'Mexico', 'count' => $mexico_all, 'color' => '#fdc14e' );
			}
			
			foreach ( $capabilities as $capability ) :
				$percentage = ( $capability['count'] / $total_drivers_int ) * 100;
				$percentage_formatted = number_format( $percentage, 1 );
		?>
			<div class="col-12 col-md-6 col-lg-4 mb-4">
				<div class="card h-100 shadow-sm">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<h6 class="card-title mb-0 fw-bold"><?php echo esc_html( $capability['name'] ); ?></h6>
							<span class="badge rounded-pill" style="background-color: <?php echo esc_attr( $capability['color'] ); ?>;">
								<?php echo esc_html( $capability['count'] ); ?>
							</span>
						</div>
						<div class="mb-2">
							<div class="d-flex justify-content-between align-items-center mb-1">
								<small class="text-muted">Progress</small>
								<small class="fw-bold" style="color: <?php echo esc_attr( $capability['color'] ); ?>;">
									<?php echo esc_html( $percentage_formatted ); ?>%
								</small>
							</div>
							<div class="progress" style="height: 12px; border-radius: 10px; background-color: #e9ecef;">
								<div class="progress-bar" 
									 role="progressbar" 
									 style="width: <?php echo esc_attr( $percentage ); ?>%; background-color: <?php echo esc_attr( $capability['color'] ); ?>; border-radius: 10px; transition: width 0.6s ease;" 
									 aria-valuenow="<?php echo esc_attr( $capability['count'] ); ?>" 
									 aria-valuemin="0" 
									 aria-valuemax="<?php echo esc_attr( $total_drivers_int ); ?>">
								</div>
							</div>
						</div>
						<div class="text-center mt-3">
							<small class="text-muted">
								<?php echo esc_html( $capability['count'] ); ?> of <?php echo esc_html( $total_drivers_int ); ?> drivers
							</small>
						</div>
					</div>
				</div>
			</div>
		<?php 
			endforeach;
		endif; 
		?>
	</div>
</div>

