<?php
class TMSEmails extends TMSUsers {
	
	private $my_admin_email = '';
	private $my_billing_email = '';
	private $my_team_leader_email = '';
	private $my_tracking_email = '';
	
	private $user_fields = array();
	
	public function __construct() {
	
	}
	
	public function init () {
		
		$this->user_fields = get_fields('user_'.get_current_user_id());
		$this->fill_all_emails();
	}
	
	function fill_all_emails () {
		$this->my_admin_email = $this->get_admin_email();
		$this->my_billing_email = $this->get_billing_email();
		$this->my_team_leader_email = $this->get_team_leader_email();
		$this->my_tracking_email = $this->get_tracking_email();
	}
	
	function get_all_emails () {
		return array(
			'admin_email' => $this->my_admin_email,
			'billing_email' => $this->my_billing_email,
			'team_leader_email' => $this->my_team_leader_email,
			'tracking_email' => $this->my_tracking_email,
		);
	}
	
	function get_selected_emails($emails, $selected_keys) {
		$combined_emails = [];
		
		// Перебираем выбранные ключи
		foreach ($selected_keys as $key) {
			// Проверяем, существует ли ключ в массиве
			if (isset($emails[$key])) {
				// Разбиваем строки с адресами на массив и добавляем в общий массив
				$combined_emails = array_merge($combined_emails, explode(',', $emails[$key]));
			}
		}
		
		// Очищаем массив от лишних пробелов и пустых значений
		$cleaned_emails = array_filter(array_map('trim', $combined_emails));
		
		return $cleaned_emails; // Возвращаем уникальные адреса электронной почты
	}
	
	function get_billing_email () {
		$current_select = get_field_value($this->user_fields, 'current_select');
		// Get all users with the 'billing' role.
		$args = array(
			'role'    => 'billing',
			'meta_query' => array(
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"', // Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		$users = get_users($args);
		$emails = array();
		
		foreach ($users as $user) {
			array_push($emails, $user->user_email);
		}
		return implode(',' , $emails);
	}
	
	function get_team_leader_email() {
		$current_select = get_field_value($this->user_fields, 'current_select');
		$current_user_id = get_current_user_id(); // Get the current user ID
		
		$args = array(
			'role'       => 'dispatcher-tl',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $current_user_id . '"', // Ensure the ID is treated as a whole number in serialized arrays
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"', // Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		$query = new WP_User_Query($args);
		$team_leaders = $query->get_results();
		$emails = array();
		
		if (!empty($team_leaders)) {
			foreach ($team_leaders as $leader) {
				$emails[] = $leader->user_email;
			}
		}
		
		return implode(',' , $emails);
	}
	
	function get_tracking_email() {
		$current_select = get_field_value($this->user_fields, 'current_select');
		$current_user_id = get_current_user_id(); // Get the current user ID
		
		$args = array(
			'role'       => 'tracking',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'my_team',
					'value'   => '"' . $current_user_id . '"', // Ensure the ID is treated as a whole number in serialized arrays
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'permission_view',
					'value'   => '"' . $current_select . '"', // Encapsulate in quotes to match serialized array elements
					'compare' => 'LIKE'
				)
			)
		);
		
		$query = new WP_User_Query($args);
		$tracking = $query->get_results();
		$emails = array();
		
		if (!empty($tracking)) {
			foreach ($tracking as $leader) {
				$emails[] = $leader->user_email;
			}
		}
		
		return implode(',' , $emails);
	}
	
	function get_admin_email () {
		global $global_options;
		$emails = get_field_value($global_options, 'admin_emails');
		return $emails;
	}
	
	function send_custom_email($emails, $texts) {
		
		if (!is_array($emails)) {
			// Split the emails into an array
			$email_addresses = explode( ',', $emails );
		} else {
			$email_addresses = $emails;
		}
		
		// Define the basic structure of the email template
		$subject = !empty($texts['subject']) ? $texts['subject'] : 'No Subject';
		$project_name = !empty($texts['project_name']) ? $texts['project_name'] : '';
		$subtitle = !empty($texts['subtitle']) ? $texts['subtitle'] : '';
		$message = !empty($texts['message']) ? $texts['message'] : 'No message provided';
		
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
		foreach ($email_addresses as $email) {
			$trimmed_email = trim($email);
			if (!empty($trimmed_email) && filter_var($trimmed_email, FILTER_VALIDATE_EMAIL)) {
				wp_mail($trimmed_email, $subject, $html_template, $headers);
			}
		}
	}
}