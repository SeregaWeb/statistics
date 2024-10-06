<?php

class  TMSUsers extends TMSReportsHelper {
	
	private $curent_select_table_key = 'field_66eeba6448749';
	
	public function ajax_actions() {
		add_action( 'wp_ajax_select_project', array( $this, 'select_project' ) );
	}
	
	public function init() {
		$this->ajax_actions();
		$this->add_custom_roles();
	}
	
	public function add_custom_roles() {
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
	
	public function select_project() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"select_table" => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'select_table' ] ) && ! empty( $MY_INPUT[ 'select_table' ] ) ) {
				$user_id = get_current_user_id();
				update_field( $this->curent_select_table_key, $MY_INPUT[ 'select_table' ], 'user_' . $user_id );
				wp_send_json_success( [ 'message' => 'Table changed', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Need select table' ] );
		}
		wp_send_json_error( [ 'message' => 'Invalid request' ] );
	}
	
	public function get_account_info( $user_id ) {
		$user = get_userdata( $user_id );
		
		if ( $user ) {
			$email = $user->user_email;
			$names = $this->get_user_full_name_by_id( $user_id );
			
			$color_initials = get_field( 'initials_color', 'user_' . $user_id );
			
			$role = '';
			if ( ! empty( $user->roles ) ) {
				$role = $user->roles[ 0 ];
			}
			
			$my_team = get_field('my_team', 'user_' . $user_id );
			
			$view_tables = get_field( 'permission_view', 'user_' . $user_id );
			
			$result = array(
				'name'               => $names[ 'full_name' ],
				'initials'           => $names[ 'initials' ],
				'email'              => $email,
				'role'               => $role,
				'color'              => $color_initials,
				'permission_project' => $view_tables,
				'my_team'            => $my_team,
			);
			
			return $result;
		}
		
		return false;
		
	}
	
	public function check_user_role_access($roles = array(), $invert_logic = false) {
		// Получение текущего пользователя
		$current_user = wp_get_current_user();
		
		// Проверка, есть ли роли у пользователя
		if ( !empty( $current_user->roles ) ) {
			// Проходим по каждой роли пользователя
			foreach ( $current_user->roles as $user_role ) {
				if ( $invert_logic ) {
					// Если включён режим инверсии и роль есть в списке
					if ( in_array( $user_role, $roles ) ) {
						return true; // Доступ разрешён
					}
				} else {
					// Если роль пользователя совпадает с одной из запрещённых
					if ( in_array( $user_role, $roles ) ) {
						return false; // Доступ запрещён
					}
				}
			}
		}
		
		return $invert_logic ? false : true; // Доступ в зависимости от логики
	}
}