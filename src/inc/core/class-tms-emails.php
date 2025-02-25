<?php

class TMSEmails extends TMSUsers {
	
	private $my_admin_email       = '';
	private $my_billing_email     = '';
	private $my_team_leader_email = '';
	private $my_tracking_email    = '';
	
	private $my_accounting_email = '';
	
	private $user_fields = array();
	
	public function __construct() {
	}
	
	public function init() {
		
		$this->user_fields = get_fields( 'user_' . get_current_user_id() );
		$this->fill_all_emails();
	}
	
	function fill_all_emails() {
		$this->my_admin_email       = $this->get_admin_email();
		$this->my_billing_email     = $this->get_billing_email();
		$this->my_team_leader_email = $this->get_team_leader_email();
		$this->my_tracking_email    = $this->get_tracking_email();
		$this->my_accounting_email  = $this->get_accounting_email();
	}
	
	function get_all_emails() {
		return array(
			'admin_email'       => $this->my_admin_email,
			'billing_email'     => $this->my_billing_email,
			'team_leader_email' => $this->my_team_leader_email,
			'tracking_email'    => $this->my_tracking_email,
			'accounting_email'  => $this->my_accounting_email,
		);
	}
	
	function get_selected_emails( $emails, $selected_keys ) {
		$combined_emails = [];
		
		// Перебираем выбранные ключи
		foreach ( $selected_keys as $key ) {
			// Проверяем, существует ли ключ в массиве
			if ( isset( $emails[ $key ] ) ) {
				// Разбиваем строки с адресами на массив и добавляем в общий массив
				$combined_emails = array_merge( $combined_emails, explode( ',', $emails[ $key ] ) );
			}
		}
		
		// Очищаем массив от лишних пробелов и пустых значений
		$cleaned_emails = array_filter( array_map( 'trim', $combined_emails ) );
		
		return $cleaned_emails; // Возвращаем уникальные адреса электронной почты
	}
	
	function get_accounting_email() {
		$args = array(
			'role' => 'accounting',
		);
		
		$users  = get_users( $args );
		$emails = array();
		
		foreach ( $users as $user ) {
			array_push( $emails, $user->user_email );
		}
		
		return implode( ',', $emails );
	}
	
	function get_billing_email() {
		$current_select = get_field_value( $this->user_fields, 'current_select' );
		// Get all users with the 'billing' role.
		$args = array(
			'role'       => 'billing',
			'meta_query' => array(
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"',
					// Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		$users  = get_users( $args );
		$emails = array();
		
		foreach ( $users as $user ) {
			array_push( $emails, $user->user_email );
		}
		
		return implode( ',', $emails );
	}
	
	function get_team_leader_email( $user_id = null, $project = null ) {
		$current_select  = $project ?? get_field_value( $this->user_fields, 'current_select' );
		$current_user_id = $user_id ? intval( $user_id ) : get_current_user_id(); // Get the current user ID
		$args            = array(
			'role'       => 'dispatcher-tl',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $current_user_id . '"',
					// Ensure the ID is treated as a whole number in serialized arrays
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"',
					// Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		$query        = new WP_User_Query( $args );
		$team_leaders = $query->get_results();
		
		$emails = array();
		
		if ( ! empty( $team_leaders ) ) {
			foreach ( $team_leaders as $leader ) {
				$emails[] = $leader->user_email;
			}
		}
		
		return implode( ',', $emails );
	}
	
	function get_tracking_email( $user_id = null, $project = null ) {
		$current_select  = $project ?? get_field_value( $this->user_fields, 'current_select' );
		$current_user_id = $user_id ? intval( $user_id ) : get_current_user_id(); // Get the current user ID
		
		$args = array(
			'role'       => 'tracking',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $current_user_id . '"',
					// Ensure the ID is treated as a whole number in serialized arrays
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"',
					// Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		$query    = new WP_User_Query( $args );
		$tracking = $query->get_results();
		$emails   = array();
		
		if ( ! empty( $tracking ) ) {
			foreach ( $tracking as $leader ) {
				$emails[] = $leader->user_email;
			}
		}
		
		return implode( ',', $emails );
	}
	
	function get_admin_email() {
		global $global_options;
		$emails = get_field_value( $global_options, 'admin_emails' );
		
		return $emails;
	}
	
	function send_custom_email( $emails, $texts ) {
		
		if ( ! is_array( $emails ) ) {
			// Split the emails into an array
			$email_addresses = explode( ',', $emails );
		} else {
			$email_addresses = $emails;
		}
		
		// Define the basic structure of the email template
		$subject      = ! empty( $texts[ 'subject' ] ) ? $texts[ 'subject' ] : 'No Subject';
		$project_name = ! empty( $texts[ 'project_name' ] ) ? $texts[ 'project_name' ] : '';
		$subtitle     = ! empty( $texts[ 'subtitle' ] ) ? $texts[ 'subtitle' ] : '';
		$message      = ! empty( $texts[ 'message' ] ) ? $texts[ 'message' ] : 'No message provided';
		
		// Construct the email template
		$html_template = "
	    <html>
	        <head>
	            <style>
	                body { font-family: Arial, sans-serif; }
	                .header { background-color: #f2f2f2; padding: 10px; text-align: center; }
	                .content { padding: 20px; }
	                .footer { background-color: #f2f2f2; padding: 10px; text-align: center; }
	            </style>
	        </head>
	        <body>
	            <div class='header'>
	                <h2>{$subject}</h2>
	            </div>
	            <div class='content'>
	                <h3>{$project_name}</h3>
	                <h4>{$subtitle}</h4>
	                <p>{$message}</p>
	            </div>
	            <div class='footer'>
	                <p>Thank you for reading!</p>
	            </div>
	        </body>
	    </html>";
		
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: TMS <no-reply@endurance-tms.com>' // Replace with your sender name and email
		);
		
		// Send the email to each recipient
		foreach ( $email_addresses as $email ) {
			$trimmed_email = trim( $email );
			if ( ! empty( $trimmed_email ) && filter_var( $trimmed_email, FILTER_VALIDATE_EMAIL ) ) {
				wp_mail( $trimmed_email, $subject, $html_template, $headers );
			}
		}
	}
	
	function send_email_create_load( $id_load ) {
		global $global_options;
		// Получение пользователей для ответа
		$reply_users = get_field_value( $global_options, 'reply_create_loads_emails' );
		if ( empty( $reply_users ) ) {
			$reply_users = []; // Убедимся, что это массив
		}
		
		//TODO THIS NEED DELETE FOR PRODUCTION
//		$check_tmp = $this->check_user_role_access(array('administrator'), true);
//		if (!$check_tmp) {
//			return ['success' => false, 'message' => 'No Send email chains'];
//		}
//
		$reports       = new TMSReports();
		$project_name  = $reports->project;
		$project_email = get_field_value( $global_options, strtolower( $project_name ) . '_email' );
		$project_phone = get_field_value( $global_options, strtolower( $project_name ) . '_phone' );
		
		
		$upload_dir = wp_get_upload_dir();
		$upload_url = $upload_dir[ 'baseurl' ];
		
		if ( strtolower( $project_name ) === 'odysseia' ) {
			$extend_file = '.jpeg';
		}
		
		if ( strtolower( $project_name ) === 'endurance' || strtolower( $project_name ) === 'martlet' ) {
			$extend_file = '.jpg';
		}
		
		$upload_url .= '/logos/' . strtolower( $project_name ) . $extend_file;
		
		// Проверка наличия post_id
		if ( empty( $id_load ) ) {
			return [ 'success' => false, 'message' => 'Missing post ID' ];
		}
		
		$report_object = $reports->get_report_by_id( $id_load );
		if ( ! $report_object ) {
			return [ 'success' => false, 'message' => 'Report not found' ];
		}
		
		$meta = get_field_value( $report_object, 'meta' );
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			return [ 'success' => false, 'message' => 'Invalid meta data' ];
		}
		
		$errors = [];
		
		// Получение данных из мета
		$dispatcher = get_field_value( $meta, 'dispatcher_initials' );
		
		$dispatcher_email = $dispatcher ? get_the_author_meta( 'user_email', $dispatcher ) : '';
		$nightshift       = get_field( 'nightshift_tracking', 'user_' . $dispatcher );
		
		$tl_dispatcher = $this->get_team_leader_email( + $dispatcher, $project_name );
		$tracking      = $this->get_tracking_email( + $dispatcher, $project_name );
		
		$reference_number    = get_field_value( $meta, 'reference_number' );
		$pick_up_location    = get_field_value( $meta, 'pick_up_location' );
		$delivery_location   = get_field_value( $meta, 'delivery_location' );
		$value_contact_email = get_field_value( $meta, 'contact_email' );
		
		// Проверка email брокера
		if ( empty( $value_contact_email ) || ! filter_var( $value_contact_email, FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = 'Invalid contact email';
		}
		
		// Обработка дополнительных контактов
		$additional_emails        = [];
		$additional_contacts_json = get_field_value( $meta, 'additional_contacts' );
		if ( ! empty( $additional_contacts_json ) ) {
			$additional_contacts = json_decode( $additional_contacts_json, true );
			if ( is_array( $additional_contacts ) ) {
				foreach ( $additional_contacts as $contact ) {
					if ( isset( $contact[ 'email' ] ) && filter_var( $contact[ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
						$additional_emails[] = $contact[ 'email' ];
					}
				}
			}
		}
		
		// Обработка локаций
		$template_p = [];
		$template_d = [];
		
		if ( ! empty( $pick_up_location ) ) {
			$pick_up_location_array = json_decode( $pick_up_location, true );
			if ( is_array( $pick_up_location_array ) ) {
				foreach ( $pick_up_location_array as $pick_up ) {
					if ( ! empty( $pick_up[ 'short_address' ] ) ) {
						$template_p[] = $pick_up[ 'short_address' ];
					}
				}
			} else {
				$errors[] = 'Problem with pick up location';
			}
		} else {
			$errors[] = 'Empty pick up location';
		}
		
		if ( ! empty( $delivery_location ) ) {
			$delivery_location_array = json_decode( $delivery_location, true );
			if ( is_array( $delivery_location_array ) ) {
				foreach ( $delivery_location_array as $delivery ) {
					if ( ! empty( $delivery[ 'short_address' ] ) ) {
						$template_d[] = $delivery[ 'short_address' ];
					}
				}
			} else {
				$errors[] = 'Problem with delivery location';
			}
		} else {
			$errors[] = 'Empty delivery location';
		}
		
		// Проверка обязательных данных
		if ( empty( $reference_number ) ) {
			$errors[] = 'Missing reference number';
		}
		
		// Если есть ошибки, возвращаем их
		if ( ! empty( $errors ) ) {
			return [ 'success' => false, 'message' => implode( ', ', $errors ) ];
		}
		// Формирование темы письма
		$subject = sprintf( 'Tracking email chain: Load # %s %s - %s ', $reference_number, implode( ', ', $template_p ), implode( ', ', $template_d ) );
		
		// Формирование текста письма
		$text = sprintf( "Thank you for running this load with %s.
		<br>Our team will keep you updated during the whole process of transportation in this email thread.
		<br>If you need to add any other email for the updates, please feel free to do that.
		<br>We will immediately let you know once the truck is on-site.", $project_name );
		
		// Возврат собранных данных
		return $this->send_email_for_brocker( [
			'subject'           => $subject,
			'text'              => $text,
			'emails'            => $reply_users,
			'email_main_broker' => $value_contact_email,
			'additional_emails' => $additional_emails,
			'team_leader_email' => $tl_dispatcher,
			'tracking_email'    => $tracking,
			'project_name'      => $project_name,
			'project_email'     => $project_email,
			'project_phone'     => $project_phone,
			'dispatcher_email'  => $dispatcher_email,
			'nightshift'        => $nightshift,
			'logo'              => $upload_url,
		] );
	}
	
	function build_email_content( $data ) {
		// Проверка входных данных
		if ( empty( $data[ 'subject' ] ) || empty( $data[ 'text' ] ) ) {
			return [ 'success' => false, 'message' => 'Missing required data for email content' ];
		}
		
		$all_emails = array_merge( isset( $data[ 'emails' ] ) ? explode( ',', $data[ 'emails' ] )
			: [], isset( $data[ 'nightshift' ] ) ? explode( ',', $data[ 'nightshift' ] )
			: [], isset( $data[ 'team_leader_email' ] ) ? [ $data[ 'team_leader_email' ] ]
			: [], isset( $data[ 'additional_emails' ] ) ? $data[ 'additional_emails' ]
			: [], isset( $data[ 'email_main_broker' ] ) ? [ $data[ 'email_main_broker' ] ]
			: [], isset( $data[ 'tracking_email' ] ) ? [ $data[ 'tracking_email' ] ]
			: [], isset( $data[ 'dispatcher_email' ] ) ? [ $data[ 'dispatcher_email' ] ] : [], );

//		$mails_bcc = array_merge( isset( $data[ 'emails' ] ) ? explode( ',', $data[ 'emails' ] )
//			: [], isset( $data[ 'nightshift' ] ) ? explode( ',', $data[ 'nightshift' ] )
//			: [], isset( $data[ 'team_leader_email' ] ) ? [ $data[ 'team_leader_email' ] ] : [] );
//
		// Объединение всех email
		$email_project = ( isset( $data[ 'project_email' ] ) && $data[ 'project_email' ] )
			? "<p class='text'>Email: <a href='" . $data[ 'project_email' ] . "'>" . $data[ 'project_email' ] . "</a></p>"
			: "";
		
		$phone_project = ( isset( $data[ 'project_phone' ] ) && $data[ 'project_phone' ] )
			? "<p class='text'>Phone: " . $data[ 'project_phone' ] . "</p>" : "";
		
		// Удаление дубликатов и пустых значений
		$all_emails   = array_filter( array_unique( $all_emails ) );
		$html_content = "
    	<html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
                background-color: #f9f9f9;
                text-align: center;
            }
            .email-header {
                font-size: 20px;
                font-weight: bold;
                color: #000000;
                max-width: 600px;
                margin: 20px auto;
                text-align: center;
                
            }
            .email-body {
                font-size: 16px;
                color: #444;
            }
            .email-footer {
                max-width: 600px;
                margin: 20px auto;
                font-size: 14px;
                color: #777;
                text-align: left;
            }
            
            .email-logo {
            	text-align: center;
            }
            
            .email-logo-image {
            	width: 180px;
            	height: auto;
            }
            
            .text {
                color: #000000;
            	margin: 0;
            }
            
        </style>
    </head>
    <body>
    
    	<div class='email-logo'>
    		<img class='email-logo-image' src='" . $data[ 'logo' ] . "' alt='logo'>
		</div>
    
        <div class='email-header'>{$data['subject']}</div>
    
        <div class='email-container'>
            <div class='email-body'>
                <p>" . $data[ 'text' ] . "</p>
            </div>
        </div>
      	<div class='email-footer'>
            	" . $email_project . $phone_project . "
        </div>
    </body>
    </html>";
		
		return [
			'html'       => $html_content,
			'all_emails' => implode( ', ', $all_emails ),
		];
	}
	
	function send_email_for_brocker( $data ) {
		// Создание HTML контента
		$email_content = $this->build_email_content( $data );
		
		// Проверка на ошибки
		if ( isset( $email_content[ 'error' ] ) ) {
			return [ 'success' => false, 'message' => implode( ', ', $email_content[ 'error' ] ) ];
		}
		
		// Получение HTML-контента и email-адресов
		$html_body  = $email_content[ 'html' ];
		$all_emails = $email_content[ 'all_emails' ];
		
		// Настройка заголовков для HTML email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Tracking chain <' . $data[ 'project_email' ] . '>'
		);
		
		// Отправка письма с помощью wp_mail() (для WordPress) или mail()
		if ( function_exists( 'wp_mail' ) ) {
			$result = wp_mail( $all_emails, $data[ 'subject' ], $html_body, $headers );
		} else {
			// Для PHP mail()
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-Transfer-Encoding: 8bit';
			$result    = mail( $all_emails, $data[ 'subject' ], $html_body, implode( "\r\n", $headers ) );
		}
		
		if ( $result ) {
			return [
				'success' => true,
				'message' => 'Send email chains. for emails: ' . $all_emails
			];
		} else {
			return [ 'success' => false, 'message' => 'Failed to send email for broker' ];
		}
	}
	
	
}