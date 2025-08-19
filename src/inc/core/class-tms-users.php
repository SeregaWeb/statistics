<?php

class  TMSUsers extends TMSReportsHelper {
	private $curent_select_table_key = 'field_66eeba6448749';
	private $project_for_bookmark    = '';
	
	public function __construct() {
		$this->project_for_bookmark = $this->get_select_project();
	}
	
	public function get_dispatchers_weekends() {
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		$user_id = intval( $_POST[ 'user_id' ] ?? 0 );
		if ( ! $user_id ) {
			wp_send_json_error( 'No user_id' );
		}
		$days   = [
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday'
		];
		$result = [];
		foreach ( $days as $day ) {
			$field          = 'exclude_' . $day;
			$value          = get_field( $field, 'user_' . $user_id );
			$result[ $day ] = is_array( $value ) ? array_map( 'strval', $value ) : [];
		}
		wp_send_json_success( $result );
	}
	
	public function debug_save_weekends() {
		
		$move_from   = intval( $_POST[ 'move-from' ] ?? 0 );
		$move_to     = intval( $_POST[ 'move-to' ] ?? 0 );
		$dispatchers = $_POST[ 'dispatcher' ] ?? [];
		
		// Преобразуем в массив если пришла строка
		if ( ! is_array( $dispatchers ) ) {
			$dispatchers = [ $dispatchers ];
		}
		
		// Очищаем от 'user_' префикса
		$dispatchers = array_map( function( $dispatcher ) {
			return str_replace( 'user_', '', $dispatcher );
		}, $dispatchers );
		
		if ( ! $move_from || ! $move_to || empty( $dispatchers ) ) {
			wp_send_json_error( 'Недостаточно данных' );
		}
		
		// Ключи ACF
		$team_key = 'field_66f9240398a70';
		$days     = [
			'monday'    => 'field_684aafd1edc47',
			'tuesday'   => 'field_684ab094edc48',
			'wednesday' => 'field_684ab0c8edc49',
			'thursday'  => 'field_684ab0d8edc4a',
			'friday'    => 'field_684ab0e7edc4b',
			'saturday'  => 'field_684ab0f5edc4c',
			'sunday'    => 'field_684ab105edc4d',
		];
		
		// Массив для сбора информации о задействованных пользователях
		$involved_users = [];
		
		// Получаем информацию о пользователе, который делает перенос
		$current_user     = wp_get_current_user();
		$involved_users[] = [
			'full_name'       => $current_user->display_name,
			'email'           => $current_user->user_email,
			'role'            => $this->get_user_role_name( $current_user->ID ),
			'action'          => 'mover',
			'weekend_changes' => null,
			'project'         => $this->project
		];
		
		// Получаем информацию о move-from пользователе
		$move_from_user = get_user_by( 'ID', $move_from );
		if ( $move_from_user ) {
			$involved_users[] = [
				'full_name'       => $move_from_user->display_name,
				'email'           => $move_from_user->user_email,
				'role'            => $this->get_user_role_name( $move_from ),
				'action'          => 'move_from',
				'weekend_changes' => null
			];
		}
		
		// Получаем информацию о move-to пользователе
		$move_to_user = get_user_by( 'ID', $move_to );
		if ( $move_to_user ) {
			$involved_users[] = [
				'full_name'       => $move_to_user->display_name,
				'email'           => $move_to_user->user_email,
				'role'            => $this->get_user_role_name( $move_to ),
				'action'          => 'move_to',
				'weekend_changes' => null
			];
		}
		
		// Обрабатываем каждого выбранного диспетчера
		foreach ( $dispatchers as $dispatcher ) {
			$dispatcher_user = get_user_by( 'ID', $dispatcher );
			
			// Сохраняем текущее состояние выходных ДО изменений
			// Проверяем состояние в команде move_from (откуда переносим)
			$current_weekend_state = [];
			foreach ( $days as $day => $field_key ) {
				$ex = get_field( $field_key, 'user_' . $move_from );
				if ( ! is_array( $ex ) ) {
					$ex = [];
				}
				$ex                            = array_map( 'strval', $ex );
				$current_weekend_state[ $day ] = in_array( $dispatcher, $ex, true );
			}
			$weekend_changes = $this->track_weekend_changes( $dispatcher, $move_to, $days, $current_weekend_state );
			
			$involved_users[] = [
				'full_name'       => $dispatcher_user ? $dispatcher_user->display_name : 'Unknown User',
				'email'           => $dispatcher_user ? $dispatcher_user->user_email : '',
				'role'            => $this->get_user_role_name( $dispatcher ),
				'action'          => 'dispatcher',
				'weekend_changes' => $weekend_changes
			];
			
			// 1. Удаляем диспетчера из команды move-from
			$from_team = get_field( $team_key, 'user_' . $move_from );
			if ( is_array( $from_team ) ) {
				$from_team = array_map( 'strval', $from_team );
				$from_team = array_diff( $from_team, [ $dispatcher ] );
				update_field( $team_key, array_values( $from_team ), 'user_' . $move_from );
			}
			
			// 2. Удаляем диспетчера из всех exclude_* у move-from
			foreach ( $days as $day => $field_key ) {
				$ex = get_field( $field_key, 'user_' . $move_from );
				if ( is_array( $ex ) ) {
					$ex = array_map( 'strval', $ex );
					$ex = array_diff( $ex, [ $dispatcher ] );
					update_field( $field_key, array_values( $ex ), 'user_' . $move_from );
				}
			}
			
			// 3. Добавляем диспетчера в команду move-to
			$to_team = get_field( $team_key, 'user_' . $move_to );
			if ( ! is_array( $to_team ) ) {
				$to_team = [];
			}
			$to_team = array_map( 'strval', $to_team );
			if ( ! in_array( $dispatcher, $to_team, true ) ) {
				$to_team[] = $dispatcher;
				update_field( $team_key, array_values( $to_team ), 'user_' . $move_to );
			}
			
			// 4. Добавляем диспетчера в exclude_* move-to по отмеченным дням
			foreach ( $days as $day => $field_key ) {
				$key     = 'exclude_' . $day . '_user_' . $dispatcher;
				$checked = ! empty( $_POST[ $key ] );
				
				$ex      = get_field( $field_key, 'user_' . $move_to );
				if ( ! is_array( $ex ) ) {
					$ex = [];
				}
				$ex = array_map( 'strval', $ex );
				
				if ( $checked && ! in_array( $dispatcher, $ex, true ) ) {
					$ex[] = $dispatcher;
				} elseif ( ! $checked && in_array( $dispatcher, $ex, true ) ) {
					$ex = array_diff( $ex, [ $dispatcher ] );
				}
				update_field( $field_key, array_values( $ex ), 'user_' . $move_to );
			}
		}
		
		
		// Send email notifications to involved users
		$this->send_team_transfer_notifications( $involved_users );
		
		wp_send_json_success( array(
			'success'        => true,
			'message'        => 'Moved successfully',
			'involved_users' => $involved_users
		) );
	}
	
	/**
	 * Получает название роли пользователя
	 */
	private function get_user_role_name( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return 'Unknown';
		}
		
		$roles = $user->roles;
		if ( empty( $roles ) ) {
			return 'No Role';
		}
		
		$role_names = [
			'administrator' => 'Administrator',
			'editor'        => 'Editor',
			'author'        => 'Author',
			'contributor'   => 'Contributor',
			'subscriber'    => 'Subscriber',
			'dispatcher'    => 'Dispatcher',
			'driver'        => 'Driver'
		];
		
		$role = $roles[ 0 ];
		
		return $role_names[ $role ] ?? ucfirst( $role );
	}
	
	/**
	 * Отслеживает изменения в выходных днях диспетчера
	 */
	private function track_weekend_changes( $dispatcher_id, $move_to_id, $days, $current_weekend_state ) {
		$added_days   = [];
		$removed_days = [];
		
		foreach ( $days as $day => $field_key ) {
			$key     = 'exclude_' . $day . '_user_' . $dispatcher_id;
			$checked = ! empty( $_POST[ $key ] );
			
			// Используем сохраненное состояние ДО изменений
			$was_excluded = $current_weekend_state[ $day ] ?? false;
			
			if ( $checked && ! $was_excluded ) {
				$added_days[] = $day;
			} elseif ( ! $checked && $was_excluded ) {
				$removed_days[] = $day;
			}
		}
		
		if ( empty( $added_days ) && empty( $removed_days ) ) {
			return 'Weekend changes: no changes';
		}
		
		$changes = [];
		if ( ! empty( $added_days ) ) {
			$changes[] = 'added new (' . implode( ', ', $added_days ) . ')';
		}
		if ( ! empty( $removed_days ) ) {
			$changes[] = 'removed (' . implode( ', ', $removed_days ) . ')';
		}
		
		return 'Weekend changes: ' . implode( ' ', $changes );
	}
	
	
	public function ajax_actions() {
		add_action( 'wp_ajax_select_project', array( $this, 'select_project' ) );
		add_action( 'wp_ajax_toggle_bookmark', array( $this, 'toggle_bookmark' ) );
		add_action( 'wp_ajax_get_dispatchers_weekends', array( $this, 'get_dispatchers_weekends' ) );
		add_action( 'wp_ajax_debug_save_weekends', array( $this, 'debug_save_weekends' ) );
	}
	
	function get_select_project() {
		$user_id       = get_current_user_id();
		$curent_tables = get_field( 'current_select', 'user_' . $user_id );
		if ( ! $curent_tables ) {
			return false;
		}
		
		return strtolower( $curent_tables );
	}
	
	function is_bookmarked( $id, $is_flt = false ) {
		$meta_key = $is_flt ? 'user_bookmarks_flt_' . $this->project_for_bookmark : 'user_bookmarks_' . $this->project_for_bookmark;
		$user_bookmarks = get_user_meta( get_current_user_id(), $meta_key, true ) ?: [];
		$is_bookmarked  = in_array( $id, $user_bookmarks );
		
		return $is_bookmarked;
	}
	
	function get_all_bookmarks( $is_flt = false ) {
		$meta_key = $is_flt ? 'user_bookmarks_flt_' . $this->project_for_bookmark : 'user_bookmarks_' . $this->project_for_bookmark;
		$user_bookmarks = get_user_meta( get_current_user_id(), $meta_key, true ) ?: [];
		
		return $user_bookmarks;
	}
	
	function toggle_bookmark() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'You must be logged in to bookmark posts.' ] );
		}
		
		$post_id = isset( $_POST[ 'post_id' ] ) ? intval( $_POST[ 'post_id' ] ) : 0;
		if ( ! $post_id || $post_id === 0 ) {
			wp_send_json_error( [ 'message' => 'Invalid post ID.' ] );
		}
		
		$is_flt = isset( $_POST[ 'is_flt' ] ) ? (bool) $_POST[ 'is_flt' ] : false;
		$meta_key = $is_flt ? 'user_bookmarks_flt_' . $this->project_for_bookmark : 'user_bookmarks_' . $this->project_for_bookmark;
		
		$user_id        = get_current_user_id();
		$user_bookmarks = get_user_meta( $user_id, $meta_key, true ) ?: [];
		
		if ( in_array( $post_id, $user_bookmarks ) ) {
			$user_bookmarks = array_diff( $user_bookmarks, [ $post_id ] );
			$is_bookmarked  = false;
		} else {
			$user_bookmarks[] = $post_id;
			$is_bookmarked    = true;
		}
		
		update_user_meta( $user_id, $meta_key, $user_bookmarks );
		
		wp_send_json_success( [ 'is_bookmarked' => $is_bookmarked ] );
	}
	
	public function init() {
		$this->ajax_actions();
		$this->add_custom_roles();
	}
	
	public function add_custom_roles() {
		$roles = [
			'dispatcher'          => 'Dispatcher',
			'dispatcher-tl'       => 'Dispatcher Team Leader',
			'tracking'            => 'Tracking',
			'tracking-tl'         => 'Tracking Team Leader',
			'billing'             => 'Billing',
			'recruiter'           => 'Recruiter',
			'recruiter-tl'        => 'Recruiter Team Leader',
			'accounting'          => 'Accounting',
			'moderator'           => 'Moderator',
			'morning_tracking'    => 'Morning Tracking',
			'nightshift_tracking' => 'Nightshift Tracking',
			'expedite_manager'    => 'Expedite Manager',
			'hr_manager'          => 'HR Manager',
			'driver_updates'      => 'Driver Updates',
		];
		
		$capabilities = [
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		];
		
		foreach ( $roles as $key => $name ) {
			add_role( $key, $name, $capabilities );
		}
	}
	
	public function get_region( $key ) {
		$array_labels = array(
			'ua' => 'Ukraine',
			'pl' => 'Poland',
			'rm' => 'Remote',
		);
		
		return $array_labels[ $key ];
	}
	
	public function get_role_label( $key ) {
		$array_labels = array(
			'dispatcher'          => 'Dispatcher',
			'dispatcher-tl'       => 'Dispatcher Team Leader',
			'tracking'            => 'Tracking',
			'tracking-tl'         => 'Tracking Team Leader',
			'billing'             => 'Billing',
			'recruiter'           => 'Recruiter',
			'recruiter-tl'        => 'Recruiter Team Leader',
			'administrator'       => 'Administrator',
			'accounting'          => 'Accounting',
			'moderator'           => 'Moderator',
			'morning_tracking'    => 'Morning Tracking',
			'nightshift_tracking' => 'Nightshift Tracking',
			'expedite_manager'    => 'Expedite Manager',
			'hr_manager'          => 'HR Manager',
			'driver_updates'      => 'Driver Updates',
		);
		
		return $array_labels[ $key ];
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
			
			$work_location = get_field( 'work_location', 'user_' . $user_id );
			if ( ! $work_location ) {
				$work_location = 'rm';
			}
			$work_location = $this->get_region( $work_location );
			
			$role = '';
			if ( ! empty( $user->roles ) ) {
				$role = $this->get_role_label( $user->roles[ 0 ] );
			}
			
			$my_team = get_field( 'my_team', 'user_' . $user_id );
			
			$view_tables = get_field( 'permission_view', 'user_' . $user_id );
			
			$result = array(
				'name'               => $names[ 'full_name' ],
				'initials'           => $names[ 'initials' ],
				'email'              => $email,
				'role'               => $role,
				'region'             => $work_location,
				'color'              => $color_initials,
				'permission_project' => $view_tables,
				'my_team'            => $my_team,
			);
			
			return $result;
		}
		
		return false;
		
	}
	
	public function check_user_role_access( $roles = array(), $invert_logic = false ) {
		// Получение текущего пользователя
		$current_user = wp_get_current_user();
		
		// Проверка, есть ли роли у пользователя
		if ( ! empty( $current_user->roles ) ) {
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
	
	public function check_group_access() {
		$check_group = $this->check_user_role_access( array(
			'tracking-tl',
			'dispatcher-tl',
			'tracking',
			'expedite_manager',
			'morning_tracking',
			'nightshift_tracking'
		), true );
		$my_team     = null;
		if ( $check_group ) {
			$current_user_id = get_current_user_id();
			$my_team         = get_field( 'my_team', 'user_' . $current_user_id );
			
			$day_name        = strtolower( date( 'l' ) ); // Получаем день недели, например: 'Monday'
			$key_name        = 'exclude_' . $day_name;
			$exclude_drivers = get_field( $key_name, 'user_' . $current_user_id );
			
			if ( is_array( $exclude_drivers ) && ! empty( $exclude_drivers ) ) {
				$exclude_drivers = array_map( 'intval', $exclude_drivers );
			}
			if ( is_array( $my_team ) && ! empty( $my_team ) ) {
				$my_team = array_map( 'intval', $my_team );
			}
			
			if ( is_array( $exclude_drivers ) && ! empty( $exclude_drivers ) ) {
				$filtered_team = array_filter( $my_team, function( $driver_id ) use ( $exclude_drivers ) {
					return ! in_array( $driver_id, $exclude_drivers, true );
				} );
				$my_team       = $filtered_team;
			}
			
		}
		
		return $my_team;
	}
	
	public function check_user_in_my_group( $my_team, $id_user ) {
		if ( $my_team === null || ! is_array( $my_team ) ) {
			return false;
		}
		
		$in_team = array_search( $id_user, $my_team );
		
		return is_numeric( $in_team );
	}
	
	public function show_control_loads( $my_team, $current_user_id, $id_user, $is_draft ) {
		
		$allowed_role = $this->check_user_role_access( array(
			'administrator',
			'billing',
			'accounting',
			'moderator',
			'tracking-tl',
			'recruiter-tl',
			'hr_manager',
			'driver_updates',
			'recruiter'
		), true );
		
		if ( $allowed_role || intval( $current_user_id ) === intval( $id_user ) || $is_draft ) {
			return true;
		}
		
		if ( is_null( $my_team ) ) {
			return false;
		}
		
		return $this->check_user_in_my_group( $my_team, $id_user );
	}
	
	public function check_read_only( $post_status ) {
		
		if ( $post_status === 'draft' ) {
			return false;
		}
		
		$read_only = false;
		
		if ( $this->check_user_role_access( array( 'billing' ), true ) ) {
			$read_only = true;
		}
		
		if ( $this->check_user_role_access( array(
				'dispatcher',
				'expedite_manager',
				'dispatcher-tl'
			), true ) && $post_status === 'publish' ) {
			$read_only = false;
		}
		
		return $read_only;
	}
	
	/**
	 * Send email notifications for team transfers
	 */
	private function send_team_transfer_notifications( $involved_users ) {
		$emails = new TMSEmails();
		$emails->init();
		
		$project_name = $this->get_select_project();
		
		if ( strtolower( $project_name ) === 'odysseia' ) {
			$project_name = 'Odysseia';
		}
		
		if ( strtolower( $project_name ) === 'martlet' ) {
			$project_name = 'Martlet';
		}
		
		if ( strtolower( $project_name ) === 'endurance' ) {
			$project_name = 'Endurance';
		}
		
		// Find mover, move_from, move_to users
		$mover       = null;
		$move_from   = null;
		$move_to     = null;
		$dispatchers = [];
		
		foreach ( $involved_users as $user ) {
			switch ( $user[ 'action' ] ) {
				case 'mover':
					$mover = $user;
					break;
				case 'move_from':
					$move_from = $user;
					break;
				case 'move_to':
					$move_to = $user;
					break;
				case 'dispatcher':
					$dispatchers[] = $user;
					break;
			}
		}
		
		// Get dispatcher names for email content
		$dispatcher_names        = array_map( function( $d ) {
			return $d[ 'full_name' ];
		}, $dispatchers );
		$dispatcher_names_string = implode( ', ', $dispatcher_names );
		
		// Send email to move_from user
		if ( $move_from && ! empty( $move_from[ 'email' ] ) ) {
			$move_from_texts = [
				'subject'      => 'Changes in your tracking team',
				'project_name' => $project_name,
				'message'      => sprintf( 'User %s (%s) has transferred dispatchers: %s to %s.', $mover[ 'full_name' ], $mover[ 'role' ], $dispatcher_names_string, $move_to[ 'full_name' ] )
			];
			
			$emails->send_custom_email( $move_from[ 'email' ], $move_from_texts );
		}
		
		// Send email to move_to user
		if ( $move_to && ! empty( $move_to[ 'email' ] ) ) {
			$move_to_texts = [
				'subject'      => 'Changes in your tracking team',
				'project_name' => $project_name,
				'message'      => sprintf( 'User %s (%s) has transferred dispatchers: %s from %s.', $mover[ 'full_name' ], $mover[ 'role' ], $dispatcher_names_string, $move_from[ 'full_name' ] )
			];
			
			$emails->send_custom_email( $move_to[ 'email' ], $move_to_texts );
		}
		
		// Send email to each dispatcher
		foreach ( $dispatchers as $dispatcher ) {
			if ( ! empty( $dispatcher[ 'email' ] ) ) {
				$dispatcher_texts = [
					'subject'      => 'You have been added to another tracking team',
					'project_name' => $project_name,
					'message'      => sprintf( 'User %s (%s) has transferred you from %s to %s.<br><br>%s', $mover[ 'full_name' ], $mover[ 'role' ], $move_from[ 'full_name' ], $move_to[ 'full_name' ], $dispatcher[ 'weekend_changes' ] )
				];
				
				$emails->send_custom_email( $dispatcher[ 'email' ], $dispatcher_texts );
			}
		}
		
		// Send control email to administrator
		$this->send_admin_control_email( $mover, $move_from, $move_to, $dispatchers, $project_name );
	}
	
	/**
	 * Send detailed control email to administrator
	 */
	private function send_admin_control_email( $mover, $move_from, $move_to, $dispatchers, $project_name ) {
		$emails = new TMSEmails();
		$emails->init();
		
		// Get admin emails with safe checks
		$all_emails   = $emails->get_all_emails();
		$admin_emails = isset( $all_emails[ 'admin_email' ] ) ? $all_emails[ 'admin_email' ] : '';
		
		if ( empty( $admin_emails ) ) {
			return; // No admin emails configured
		}
		
		// Build detailed content
		$content = sprintf( '<h2>Team Transfer Control Report</h2>
			<p><strong>Project:</strong> %s</p>
			<p><strong>Operation performed by:</strong> %s (%s) - %s</p>
			<p><strong>Date:</strong> %s</p>
			<br>
			<h3>Transfer Details:</h3>
			<p><strong>From Team:</strong> %s (%s) - %s</p>
			<p><strong>To Team:</strong> %s (%s) - %s</p>
			<br>
			<h3>Transferred Dispatchers:</h3>', $project_name, $mover[ 'full_name' ], $mover[ 'role' ], $mover[ 'email' ], date( 'Y-m-d H:i:s' ), $move_from[ 'full_name' ], $move_from[ 'role' ], $move_from[ 'email' ], $move_to[ 'full_name' ], $move_to[ 'role' ], $move_to[ 'email' ] );
		
		// Add dispatcher details
		foreach ( $dispatchers as $dispatcher ) {
			$content .= sprintf( '<div style="margin: 10px 0; padding: 10px; border-left: 3px solid #007cba; background: #f8f9fa;">
					<p><strong>%s</strong> (%s) - %s</p>
					<p><strong>Weekend Changes:</strong> %s</p>
				</div>', $dispatcher[ 'full_name' ], $dispatcher[ 'role' ], $dispatcher[ 'email' ], $dispatcher[ 'weekend_changes' ] );
		}
		
		$content .= '<br><p><em>This is an automated control report for administrative purposes.</em></p>';
		
		$admin_texts = [
			'subject'      => 'Team Transfer Control Report - ' . $project_name . ' - ' . date( 'Y-m-d H:i' ),
			'project_name' => $project_name,
			'message'      => $content
		];
		
		$emails->send_custom_email( $admin_emails, $admin_texts );
	}
}