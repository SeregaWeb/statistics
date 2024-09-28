<?php
class  TMSUsers {
	
	private $curent_select_table_key = 'field_66eeba6448749';
	public function ajax_actions() {
		add_action( 'wp_ajax_select_project', array( $this, 'select_project' ) );
	}
	public function init() {
		$this->ajax_actions();
		$this->add_custom_roles();
	}
	
	public function add_custom_roles () {
		add_role( 'dispatcher', 'Dispatcher', [
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		] );
		
		add_role( 'dispatcher-tl', 'Dispatcher Team Leader', [
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		] );
		
		add_role( 'tracking', 'Tracking', [
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		] );
		
		add_role( 'billing', 'Billing', [
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		] );
		
		add_role( 'recruiter', 'Recruiter', [
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		] );
		
	}
	public function select_project () {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){
			$MY_INPUT = filter_var_array( $_POST, [
				"select_table" => FILTER_SANITIZE_STRING,
			] );
			
			if (isset($MY_INPUT['select_table']) && !empty($MY_INPUT['select_table'])) {
				$user_id = get_current_user_id();
				update_field($this->curent_select_table_key, $MY_INPUT['select_table'], 'user_'.$user_id);
				wp_send_json_success( [ 'message' => 'Table changed', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Need select table' ] );
		}
		wp_send_json_error( [ 'message' => 'Invalid request' ] );
	}
}