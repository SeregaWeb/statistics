<?php
/**
 * Expired Documents Statistics Component
 * 
 * @package WP-rock
 * @since 4.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_drivers_int = (int) $total_drivers;

// Define colors for each document type
$document_colors = array(
	'DL' => '#dc3545',
	'COI' => '#fd7e14',
	'EA' => '#ffc107',
	'PR' => '#20c997',
	'PS' => '#0dcaf0',
	'HZ' => '#e83e8c',
	'GE' => '#6f42c1',
	'TWIC' => '#0d6efd',
	'TSA' => '#198754',
	'DL_TEAM' => '#dc3545',
	'EA_TEAM' => '#ffc107',
	'PR_TEAM' => '#20c997',
	'PS_TEAM' => '#0dcaf0',
	'HZ_TEAM' => '#e83e8c',
	'GE_TEAM' => '#6f42c1',
	'TWIC_TEAM' => '#0d6efd',
	'TSA_TEAM' => '#198754',
);

?>

<div class="col-12 mb-3">
	<div class="row">
		<?php
		if ( $total_drivers_int > 0 && isset( $expired_documents_stats ) && ! empty( $expired_documents_stats ) ) :
			// Build capabilities array with colors
			$expired_docs = array();
			foreach ( $expired_documents_stats as $doc_type => $doc_data ) {
				if ( $doc_data['count'] > 0 ) {
					$color = isset( $document_colors[ $doc_type ] ) ? $document_colors[ $doc_type ] : '#6c757d';
					$expired_docs[] = array(
						'name' => $doc_data['name'],
						'count' => $doc_data['count'],
						'color' => $color
					);
				}
			}
			
			// Sort by count descending
			usort( $expired_docs, function( $a, $b ) {
				return $b['count'] - $a['count'];
			} );
			
			foreach ( $expired_docs as $doc ) :
				$percentage = ( $doc['count'] / $total_drivers_int ) * 100;
				$percentage_formatted = number_format( $percentage, 1 );
		?>
			<div class="col-12 col-md-6 col-lg-4 mb-4">
				<div class="card h-100 shadow-sm">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<h6 class="card-title mb-0 fw-bold"><?php echo esc_html( $doc['name'] ); ?></h6>
							<span class="badge rounded-pill" style="background-color: <?php echo esc_attr( $doc['color'] ); ?>;">
								<?php echo esc_html( $doc['count'] ); ?>
							</span>
						</div>
						<div class="mb-2">
							<div class="d-flex justify-content-between align-items-center mb-1">
								<small class="text-muted">Percentage</small>
								<small class="fw-bold" style="color: <?php echo esc_attr( $doc['color'] ); ?>;">
									<?php echo esc_html( $percentage_formatted ); ?>%
								</small>
							</div>
							<div class="progress" style="height: 12px; border-radius: 10px; background-color: #e9ecef;">
								<div class="progress-bar" 
									 role="progressbar" 
									 style="width: <?php echo esc_attr( $percentage ); ?>%; background-color: <?php echo esc_attr( $doc['color'] ); ?>; border-radius: 10px; transition: width 0.6s ease;" 
									 aria-valuenow="<?php echo esc_attr( $doc['count'] ); ?>" 
									 aria-valuemin="0" 
									 aria-valuemax="<?php echo esc_attr( $total_drivers_int ); ?>">
								</div>
							</div>
						</div>
						<div class="text-center mt-3">
							<small class="text-muted">
								<?php echo esc_html( $doc['count'] ); ?> of <?php echo esc_html( $total_drivers_int ); ?> drivers
							</small>
						</div>
					</div>
				</div>
			</div>
		<?php 
			endforeach;
		elseif ( $total_drivers_int > 0 ) :
		?>
			<div class="col-12">
				<div class="alert alert-success">
					<strong>Great news!</strong> No expired documents found among all drivers.
				</div>
			</div>
		<?php 
		endif; 
		?>
	</div>
</div>
