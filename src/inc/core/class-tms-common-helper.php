<?php

class TMSCommonHelper {
	public function need_login() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			wp_send_json_error( [ 'message' => 'You need to log in to perform this action.' ] );
		}
	}

	/**
	 * Get driver project permissions based on required documents
	 * 
	 * @param array $meta_data Driver meta data array
	 * @return array Array of project names the driver has access to
	 */
	public static function get_driver_project_permissions( $meta_data ) {
		$permissions = array();
		
		// Check Odysseia: requires ic_agreement and auto_liability_coi
		$ic_agreement = ! empty( $meta_data['ic_agreement'] );
		$auto_liability_coi = ! empty( $meta_data['auto_liability_coi'] );
		if ( $ic_agreement && $auto_liability_coi ) {
			$permissions[] = 'Odysseia';
		}
		
		// Check Martlet: requires martlet_ic_agreement and martlet_coi
		$martlet_ic_agreement = ! empty( $meta_data['martlet_ic_agreement'] );
		$martlet_coi = ! empty( $meta_data['martlet_coi'] );
		if ( $martlet_ic_agreement && $martlet_coi ) {
			$permissions[] = 'Martlet';
		}
		
		// Check Endurance: requires endurance_ic_agreement and endurance_coi
		$endurance_ic_agreement = ! empty( $meta_data['endurance_ic_agreement'] );
		$endurance_coi = ! empty( $meta_data['endurance_coi'] );
		if ( $endurance_ic_agreement && $endurance_coi ) {
			$permissions[] = 'Endurance';
		}
		
		return $permissions;
	}
}