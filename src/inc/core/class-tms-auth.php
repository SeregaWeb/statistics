<?php

class  TMSAuth {
	public function ajax_actions() {
		add_action( 'wp_ajax_send_code', array( $this, 'send_verification_code' ) );
		add_action( 'wp_ajax_nopriv_send_code', array( $this, 'send_verification_code' ) );
		add_action( 'wp_ajax_verify_code', array( $this, 'verify_code_and_login' ) );
		add_action( 'wp_ajax_nopriv_verify_code', array( $this, 'verify_code_and_login' ) );
	}
	
	public function init() {
		$this->ajax_actions();
		$this->init_wp_admin_protection();
	}
	
	/**
	 * Initialize WordPress admin protection hooks
	 */
	public function init_wp_admin_protection() {
		add_filter( 'wp_authenticate_user', array( $this, 'check_deactivated_account_wp_admin' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'check_deactivated_account_admin_access' ) );
		add_filter( 'login_message', array( $this, 'display_deactivated_account_message' ) );
	}
	
	function verify_code_and_login() {
		if ( ! isset( $_POST[ 'email' ], $_POST[ 'code' ] ) ) {
			wp_send_json_error( [ 'message' => 'Email and code are required' ] );
		}
		
		$email = sanitize_email( $_POST[ 'email' ] );
		$code  = sanitize_text_field( $_POST[ 'code' ] );
		
		// Проверка, существует ли пользователь с таким email
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			wp_send_json_error( [ 'message' => 'Invalid email' ] );
		}
		
		// Получение кода из транзиента
		$stored_code = get_transient( 'auth_code_' . $user->ID );
		
		if ( ! $stored_code || $stored_code != $code ) {
			wp_send_json_error( [ 'message' => 'Invalid or expired code' ] );
		}
		
		// Если код верный, логиним пользователя
		wp_set_auth_cookie( $user->ID, true );
		delete_transient( 'auth_code_' . $user->ID ); // Удаляем транзиент после успешного входа
		
		wp_send_json_success( [ 'message' => 'Login successful', 'redirect' => home_url() ] );
	}
	
	
	function send_verification_code() {
		if ( ! isset( $_POST[ 'email' ], $_POST[ 'password' ] ) ) {
			wp_send_json_error( [ 'message' => 'Email and password are required' ] );
		}
		
		$emails_helper = new TMSEmails();
		
		$email    = sanitize_email( $_POST[ 'email' ] );
		$password = sanitize_text_field( $_POST[ 'password' ] );
		
		// Проверка, существует ли пользователь с таким email и правильный ли пароль
		$user = get_user_by( 'email', $email );
		if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			wp_send_json_error( [ 'message' => 'Invalid email or password' ] );
		}

		$deactivate_account = get_field( 'deactivate_account', 'user_' . $user->ID );
		if ( $deactivate_account ) {
			wp_send_json_error( [ 'message' => 'Account is deactivated' ] );
		}

		$first_name = get_user_meta( $user->ID, 'first_name', true );
		$last_name  = get_user_meta( $user->ID, 'last_name', true );
		$full_name  = trim( $first_name . ' ' . $last_name );
		
		// Генерация случайного 6-значного кода
		$code = wp_rand( 100000, 999999 );
		
		// Сохранение кода в транзиент на 15 минут
		set_transient( 'auth_code_' . $user->ID, $code, 15 * MINUTE_IN_SECONDS );
		
		$emails_helper->send_custom_email_login( $email, array(
			'subject' => 'Verification code',
			'code'    => $code,
			'name'    => $full_name,
		) );
		wp_send_json_success( [ 'message' => 'Verification code sent to your email' ] );
	}
	
	/**
	 * Check if user account is deactivated before allowing wp-admin login
	 * 
	 * @param WP_User $user User object
	 * @param string $password User password
	 * @return WP_User|WP_Error User object or error
	 */
	public function check_deactivated_account_wp_admin( $user, $password ) {
		// Check if account is deactivated
		$deactivate_account = get_field( 'deactivate_account', 'user_' . $user->ID );
		if ( $deactivate_account ) {
			// Return WP_Error to prevent login
			return new WP_Error( 'account_deactivated', 'Your account has been deactivated. Please contact administrator.' );
		}
		
		return $user;
	}
	
	/**
	 * Check if logged-in user account is deactivated and prevent admin access
	 */
	public function check_deactivated_account_admin_access() {
		// Only check for logged-in users
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		$user_id = get_current_user_id();
		$deactivate_account = get_field( 'deactivate_account', 'user_' . $user_id );
		
		if ( $deactivate_account ) {
			// Log out the user and redirect to login page with error message
			wp_logout();
			wp_redirect( add_query_arg( 'login', 'deactivated', wp_login_url() ) );
			exit;
		}
	}
	
	/**
	 * Display deactivated account message on login page
	 * 
	 * @param string $message Current login message
	 * @return string Modified message
	 */
	public function display_deactivated_account_message( $message ) {
		if ( isset( $_GET['login'] ) && $_GET['login'] === 'deactivated' ) {
			$message = '<div id="login_error">Your account has been deactivated. Please contact administrator.</div>';
		}
		
		return $message;
	}
	
}