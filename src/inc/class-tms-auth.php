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
		
		// Генерация случайного 6-значного кода
		$code = wp_rand( 100000, 999999 );
		
		// Сохранение кода в транзиент на 15 минут
		set_transient( 'auth_code_' . $user->ID, $code, 15 * MINUTE_IN_SECONDS );
		
		$emails_helper->send_custom_email( $email, array(
			'subject'      => 'Verification code',
			'project_name' => '',
			'subtitle'     => '',
			'message'      => 'Your verification code is: <strong style="font-size: 32px;">' . $code . '</strong>',
		) );
		wp_send_json_success( [ 'message' => 'Verification code sent to your email' ] );
	}
	
}