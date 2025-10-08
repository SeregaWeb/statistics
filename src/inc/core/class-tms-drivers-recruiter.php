<?php

class TMSDriversRecruiter {
	
	private $driver_dot_number = 'dot';
	private $driver_mc_number = 'mc';
	private $checked_from_brokersnapshot = 'checked_from_brokersnapshot'; // table_main
	private $dot_bool = 'dot_enabled';
	private $mc_bool = 'mc_enabled';
	
	private $api_key;
	private $reports_path;

	/**
	 * Map Brokersnapshot status codes to human readable
	 */
	private function map_brokersnapshot_status( $status_code ) {
		$status_code = strtoupper( trim( (string) $status_code ) );
		$map = array(
			'A' => 'Active',
			'I' => 'Inactive',
			'S' => 'Suspended',
			'R' => 'Revoked',
			'D' => 'Deleted',
		);
		return $map[ $status_code ] ?? $status_code;
	}
	
	public function __construct() {
		global $global_options;
    
		$this->api_key = get_field_value( $global_options, 'brokersnapshot' );
		$this->reports_path = get_template_directory() . '/backershot/';
		$this->initCronAction();
		$this->initAjaxActions();
	}
	
	public function initCronAction() {
		add_action('init', array($this, 'schedule_my_custom_cron_job'));
		add_filter('cron_schedules', array($this, 'add_every_two_minutes_schedule'));
		add_action('my_custom_cron_hook', array($this, 'my_custom_cron_job'));
	}
	
	/**
	 * Get drivers that need to be checked
	 */
	public function get_drivers() {
		global $wpdb;
		
		$table_main = $wpdb->prefix . 'drivers';
		$table_meta = $wpdb->prefix . 'drivers_meta';
		
		error_log('=== DEBUG: get_drivers() started ===');
		error_log('Current time: ' . date('Y-m-d H:i:s'));
		error_log('7 days ago timestamp: ' . strtotime('-7 days'));
		
		$query = $wpdb->prepare(
			"
			SELECT main.id
			FROM {$table_main} main
			LEFT JOIN {$table_meta} warning ON main.id = warning.post_id AND warning.meta_key = 'warning'
			WHERE
				(warning.meta_value = 0 OR warning.meta_value IS NULL) -- warning не чекнуто (false или не заполнено)
				AND (
					main.checked_from_brokersnapshot IS NULL 
					OR main.checked_from_brokersnapshot = 0
					OR main.checked_from_brokersnapshot < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
				) -- checked_from_brokersnapshot NULL, 0 или старше 7 дней
			ORDER BY main.id DESC LIMIT 1
			"
		);
		
		error_log('SQL Query: ' . $query);
		
		$results = $wpdb->get_results($query);
		
		error_log('Query results count: ' . count($results));
		
		if (is_array($results) && !empty($results)) {
			$driver_id = $results[0]->id;
			error_log('Found driver ID: ' . $driver_id);
			
			// Get additional info about this driver
			$driver_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_main} WHERE id = %d", $driver_id));
			if ($driver_info) {
				error_log('Driver info - checked_from_brokersnapshot: ' . $driver_info->checked_from_brokersnapshot);
				error_log('Driver info - date_created: ' . $driver_info->date_created);
			}
			
			return $driver_id;
		}
		
		error_log('No drivers found to check');
		
		// Debug: Let's see what drivers exist and their status
		$all_drivers = $wpdb->get_results("SELECT id, checked_from_brokersnapshot FROM {$table_main} ORDER BY id DESC LIMIT 10");
		error_log('Last 10 drivers in database:');
		foreach ($all_drivers as $driver) {
			$checked_value = $driver->checked_from_brokersnapshot;
			$checked_type = is_null($checked_value) ? 'NULL' : (is_numeric($checked_value) ? 'NUMBER' : 'OTHER');
			error_log("Driver ID: {$driver->id}, checked_from_brokersnapshot: {$checked_value} (type: {$checked_type})");
		}
		
		// Debug: Check how many drivers have NULL values
		$null_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_main} WHERE checked_from_brokersnapshot IS NULL");
		$zero_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_main} WHERE checked_from_brokersnapshot = 0");
		$total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_main}");
		
		error_log("Total drivers: {$total_count}");
		error_log("Drivers with NULL checked_from_brokersnapshot: {$null_count}");
		error_log("Drivers with 0 checked_from_brokersnapshot: {$zero_count}");
		
		return false;
	}
	
	/**
	 * Check driver using Brokersnapshot API
	 */
	public function check_driver($driver_id) {
		error_log('=== DEBUG: check_driver() started for driver ID: ' . $driver_id . ' ===');
		
		if (!$driver_id) {
			error_log('ERROR: No driver ID provided');
			return false;
		}
		
		$drivers = new TMSDrivers();
		$driver_object = $drivers->get_driver_by_id($driver_id);
		
		if (!$driver_object) {
			error_log('ERROR: Could not get driver object for ID: ' . $driver_id);
			return false;
		}
		
		error_log('Driver object retrieved successfully');
		
		$meta = get_field_value($driver_object, 'meta');
		$main = get_field_value($driver_object, 'main');
		
		error_log('Meta data retrieved: ' . (is_array($meta) ? 'yes' : 'no'));
		error_log('Main data retrieved: ' . (is_array($main) ? 'yes' : 'no'));
		
		$date_check = get_field_value($main, 'checked_from_brokersnapshot');
		$sevenDaysAgoTimestamp = strtotime('-7 days');
		
		error_log('Date check value: ' . $date_check);
		error_log('Date check type: ' . (is_null($date_check) ? 'NULL' : (is_numeric($date_check) ? 'NUMBER' : 'OTHER')));
		error_log('Seven days ago timestamp: ' . $sevenDaysAgoTimestamp);
		
		// Check if driver needs to be checked
		$should_check = false;
		if (is_null($date_check) || $date_check == 0 || $date_check == '0000-00-00 00:00:00' || $date_check == '0') {
			$should_check = true;
			error_log('Should check: YES (NULL, 0, or invalid date value)');
		} elseif (is_numeric($date_check) && $date_check < $sevenDaysAgoTimestamp) {
			$should_check = true;
			error_log('Should check: YES (older than 7 days)');
		} else {
			error_log('Should check: NO (recently checked)');
		}
		
		if ($should_check) {
			$driver_contacts = array();
			
			// Get driver contact information
			$main_contact = get_field_value($meta, 'main_contact');
			$owner_phone = get_field_value($meta, 'owner_phone');
			$driver_name = get_field_value($meta, 'driver_name');
			$driver_phone = get_field_value($meta, 'driver_phone');
			$driver_email = get_field_value($meta, 'driver_email');
			$driver2_phone = get_field_value($meta, 'team_driver_phone');
			$driver_test_human = get_field_value($meta, 'mc_dot_human_tested');
			
			// Build unique contacts array
			$driver_contacts[] = $main_contact;
			$driver_contacts[] = $driver_email;
			
			if ($main_contact !== $owner_phone) {
				$driver_contacts[] = $owner_phone;
			}
			
			if ($driver_phone !== $main_contact && $driver_phone !== $owner_phone) {
				$driver_contacts[] = $driver_phone;
			}
			
			if ($driver2_phone !== $main_contact && $driver2_phone !== $owner_phone && $driver2_phone !== $driver_phone) {
				$driver_contacts[] = $driver2_phone;
			}
			
			$driver_contacts = array_unique(array_filter($driver_contacts));
			$report = array();
			
			if (is_array($driver_contacts)) {
				$time = strtotime('now');
				$current_date = date("m/d/Y", $time);
				$status = true;
				
				foreach ($driver_contacts as $index => $contact) {
					if (empty($contact)) continue;
					
					error_log('Processing contact #' . ($index + 1) . ': ' . $contact);
					
					// Add delay between API calls to avoid rate limiting
					if ($index > 0) {
						error_log('Adding 2 second delay before API call #' . ($index + 1));
						sleep(2);
					}
					
					$response = $this->get_info_by_brokersnapshot($contact);
				
					if (isset($response['error'])) {
						$report[] = 'Date: ' . $current_date . ' ID Driver: ' . $driver_id . ' Name: ' . $driver_name . ' Param search:' . $contact . ' Error: ' . $response['error'];
					} else {
						$dot_highlight = $response['dot'] ? '<span class="dot-highlight">DOT:' . $response['dot'] . '</span>' : '';
						$mc_highlight = $response['mc'] ? '<span class="mc-highlight">MC: ' . $response['mc'] . '</span>' : '';
						$report[] = 'Date: ' . $current_date . ' ID Driver: ' . $driver_id . ' Name: ' . $driver_name . ' Param search:' . $contact . ' ' . $dot_highlight . ' ' . $mc_highlight . ' Status:' . $response['status'];
						
						if ($driver_test_human) {
							$report[] = 'The status has not been changed, the driver has been verified by a person !';
						}
						
						if (!$driver_test_human || is_null($driver_test_human)) {
							// Update driver data
							$this->update_driver_data($driver_id, $response);
						}
						break;
					}
				}
				
				// Update checked timestamp
				$this->update_checked_timestamp($driver_id, $time);
				$this->update_reports($report);
			}
		}
	}
	
	/**
	 * Update driver DOT/MC data
	 */
	private function update_driver_data($driver_id, $response) {
		$drivers = new TMSDrivers();
		
		$meta_data = array();
		
		if ($response['dot']) {
			$meta_data['dot_enabled'] = 'on';
			$meta_data['dot'] = $response['dot'] . ' - ' . $response['status'];
		}
		
		if ($response['mc']) {
			$meta_data['mc_enabled'] = ($response['status'] === 'Active') ? 'on' : '';
			$meta_data['mc'] = $response['mc'] . ' - ' . $response['status'];
		}
		
		if (!empty($meta_data)) {
			$drivers->update_post_meta_data($driver_id, $meta_data);
		}
	}
	
	/**
	 * Update checked timestamp in main table
	 */
	private function update_checked_timestamp($driver_id, $timestamp) {
		global $wpdb;
		$table_main = $wpdb->prefix . 'drivers';
		
		error_log('=== DEBUG: update_checked_timestamp() ===');
		error_log('Driver ID: ' . $driver_id);
		error_log('New timestamp: ' . $timestamp);
		error_log('Current time: ' . time());
		
		// Get current value before update
		$current_value = $wpdb->get_var($wpdb->prepare("SELECT checked_from_brokersnapshot FROM {$table_main} WHERE id = %d", $driver_id));
		error_log('Current checked_from_brokersnapshot value: ' . $current_value);
		
		// Convert timestamp to MySQL datetime format
		$mysql_datetime = date('Y-m-d H:i:s', $timestamp);
		
		$result = $wpdb->update(
			$table_main,
			array('checked_from_brokersnapshot' => $mysql_datetime),
			array('id' => $driver_id),
			array('%s'),
			array('%d')
		);
		
		if ($result !== false) {
			error_log('Timestamp updated successfully. Rows affected: ' . $result);
		} else {
			error_log('ERROR: Failed to update timestamp');
			error_log('Last SQL error: ' . $wpdb->last_error);
		}
	}
	
	/**
	 * Get information from Brokersnapshot API
	 */
	public function get_info_by_brokersnapshot($contact) {
		error_log('=== DEBUG: API call for contact: ' . $contact . ' ===');
		
		$endpoint = 'https://brokersnapshot.com/api/v1/companies';

		$params = [
			'contact' => $contact,
			'include' => '3',  // Для получения общих, авторитетных и адресных данных
			'limit' => 10,     // Максимальное количество записей для возврата
			'skip' => 0        // Пропустить записи (в данном случае не используется)
		];
		
		// Формируем URL для запроса
		$url = $endpoint . '?' . http_build_query($params);
		
		error_log('API URL: ' . $url);
		error_log('API Key: ' . (empty($this->api_key) ? 'EMPTY' : 'SET'));
		error_log('API Key length: ' . strlen($this->api_key));
		error_log('API Key first 10 chars: ' . substr($this->api_key, 0, 10));

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $this->api_key
			]
		]);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($curl);
		$curl_info = curl_getinfo($curl);
		
		error_log('HTTP Code: ' . $http_code);
		error_log('CURL Info: ' . print_r($curl_info, true));
		if ($curl_error) {
			error_log('CURL Error: ' . $curl_error);
		}
		
		curl_close($curl);
		
		// Check for HTTP errors
		if ($http_code >= 400) {
			error_log('HTTP Error: ' . $http_code . ' - ' . $response);
			return array("error" => 'Technical error (HTTP ' . $http_code . ')');
		}

		if ($response) {
			error_log('API Response received, length: ' . strlen($response));
			error_log('API Response content: ' . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
			$data = json_decode($response, true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				error_log('JSON Decode Error: ' . json_last_error_msg());
				error_log('Raw response that failed to decode: ' . $response);
			}
			
			if ($data && isset($data['Success'])) {
				error_log('API Success: ' . ($data['Success'] ? 'true' : 'false'));
				
				if ($data['Success']) {
					
					if (!empty($data['Data'])) {
						error_log('API Data count: ' . count($data['Data']));
						
						if (isset($data['Data'][0])) {
							$MC = $data['Data'][0]['DOCKET_NUMBER'] ? $data['Data'][0]['DOCKET_NUMBER'] : '';
							$DOT = $data['Data'][0]['DOT_NUMBER'] ? $data['Data'][0]['DOT_NUMBER'] : '';
                            		$status = $data['Data'][0]['ACT_STAT'] ? $data['Data'][0]['ACT_STAT'] : '';

                            // Map raw status codes to human-readable
                            $mapped_status = $this->map_brokersnapshot_status($status);

                            error_log('API Result - MC: ' . $MC . ', DOT: ' . $DOT . ', Status raw: ' . $status . ', Status mapped: ' . $mapped_status);

                            return array("mc" => $MC, 'dot' => $DOT, 'status' => $mapped_status);
						}
					} else {
						error_log('API Response: No data found');
						return array("error" => 'Not found');
					}
				} else {
					error_log('API Response: Success = false');
				}
			} else {
				error_log('API Response: Invalid response format');
			}
		} else {
			error_log('API Response: No response received');
			if ($curl_error) {
				return array("error" => 'Technical error (CURL: ' . $curl_error . ')');
			} else {
				return array("error" => 'Technical error (No response)');
			}
		}
		
		error_log('API call failed - returning technical error');
		return array("error" => 'Technical error');
	}
	
	/**
	 * Update reports file
	 */
	public function update_reports($data) {
		error_log('=== DEBUG: update_reports() started ===');
		
		$fileName = date('m_Y') . '.txt';
		$filePath = $this->reports_path . $fileName;
		
		error_log('Report file path: ' . $filePath);
		error_log('Reports directory: ' . $this->reports_path);
		error_log('Data count: ' . count($data));

		// Create directory if it doesn't exist
		if (!is_dir($this->reports_path)) {
			error_log('Creating reports directory...');
			wp_mkdir_p($this->reports_path);
		}

		if (!file_exists($filePath)) {
			error_log('Creating new report file...');
			$fileHandle = fopen($filePath, 'w');
			if ($fileHandle === false) {
				error_log('ERROR: Could not create report file');
				return false;
			}
			fclose($fileHandle);
			error_log('Report file created successfully');
		} else {
			error_log('Report file already exists');
		}

		$fileHandle = fopen($filePath, 'a');
		if ($fileHandle === false) {
			error_log('ERROR: Could not open report file for writing');
			return false;
		}

		foreach ($data as $line) {
			fwrite($fileHandle, $line . PHP_EOL);
			error_log('Wrote line: ' . substr($line, 0, 100) . '...');
		}
		fwrite($fileHandle, '-------------------------------------------------------' . PHP_EOL);
		fclose($fileHandle);
		
		error_log('Report file updated successfully. File size: ' . filesize($filePath) . ' bytes');
	}
	
	/**
	 * Schedule cron job
	 */
	public function schedule_my_custom_cron_job() {
		if (!wp_next_scheduled('my_custom_cron_hook')) {
			wp_schedule_event(time(), 'every_two_minutes', 'my_custom_cron_hook');
		}
	}

	/**
	 * Add custom cron schedule
	 */
	public function add_every_two_minutes_schedule($schedules) {
		$schedules['every_two_minutes'] = array(
			'interval' => 120, // Интервал в секундах (120 секунд = 2 минуты)
			'display'  => __('Every Two Minutes')
		);
		return $schedules;
	}
	
	/**
	 * Cron job execution
	 */
	public function my_custom_cron_job() {
		error_log('=== CRON JOB STARTED at ' . date('Y-m-d H:i:s') . ' ===');
		
		$host = $_SERVER['HTTP_HOST'];
		error_log('Host: ' . $host);
		
		if ($host !== 'localhost' && $host !== '127.0.0.1' && !preg_match('/^localhost:\d+$/', $host)) {
			error_log('Not localhost - proceeding with cron job');
			
			$driver_id = $this->get_drivers();
			if ($driver_id) {
				error_log('Found driver to check: ' . $driver_id);
				$this->check_driver($driver_id);
				error_log('Cron job completed successfully - Driver ID: ' . $driver_id);
			} else {
				error_log('No drivers found to check');
			}
		} else {
			error_log('Cron job skipped on localhost');
		}
		
		error_log('=== CRON JOB ENDED ===');
	}
	
	/**
	 * Initialize AJAX actions
	 */
	public function initAjaxActions() {
		add_action('wp_ajax_test_check_driver', array($this, 'ajax_test_check_driver'));
		add_action('wp_ajax_manual_check_driver', array($this, 'ajax_manual_check_driver'));
	}
	
	/**
	 * AJAX handler for testing driver check
	 */
	public function ajax_test_check_driver() {
		if (defined('DOING_AJAX') && DOING_AJAX) {
			$driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
			
			if ($driver_id) {
				ob_start();
				$this->test_check_driver($driver_id);
				$output = ob_get_clean();
				
				wp_send_json_success(array(
					'output' => $output,
					'message' => 'Driver check completed'
				));
			} else {
				wp_send_json_error(array('message' => 'Driver ID not provided'));
			}
		}
	}
	
	/**
	 * AJAX handler for manual driver check
	 */
	public function ajax_manual_check_driver() {
		if (defined('DOING_AJAX') && DOING_AJAX) {
			$driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
			
			if ($driver_id) {
				$this->check_driver($driver_id);
				wp_send_json_success(array('message' => 'Driver check completed successfully'));
			} else {
				wp_send_json_error(array('message' => 'Driver ID not provided'));
			}
		}
	}
	

	

	
	/**
	 * Manual test method for checking a specific driver
	 */
	public function test_check_driver($driver_id) {
		if (!$driver_id) {
			return false;
		}
		
		$drivers = new TMSDrivers();
		$driver_object = $drivers->get_driver_by_id($driver_id);
		
		if (!$driver_object) {
			return false;
		}
		
		$meta = get_field_value($driver_object, 'meta');
		$main = get_field_value($driver_object, 'main');
		
		echo "<h3>Driver Information</h3>";
		echo "Driver ID: " . $driver_id . "<br>";
		echo "Driver Name: " . get_the_title($driver_id) . "<br>";
		
		// Get driver contact information
		$main_contact = get_field_value($meta, 'main_contact');
		$owner_phone = get_field_value($meta, 'owner_phone');
		$driver_phone = get_field_value($meta, 'driver_phone');
		$driver_email = get_field_value($meta, 'driver_email');
		$driver2_phone = get_field_value($meta, 'team_driver_phone');
		$driver_test_human = get_field_value($meta, 'mc_dot_human_tested');
		
		echo "<h4>Contact Information</h4>";
		echo "Main Contact: " . $main_contact . "<br>";
		echo "Owner Phone: " . $owner_phone . "<br>";
		echo "Driver Phone: " . $driver_phone . "<br>";
		echo "Driver Email: " . $driver_email . "<br>";
		echo "Team Driver Phone: " . $driver2_phone . "<br>";
		echo "Human Tested: " . ($driver_test_human ? 'Yes' : 'No') . "<br>";
		
		// Build unique contacts array
		$driver_contacts = array();
		$driver_contacts[] = $main_contact;
		$driver_contacts[] = $driver_email;
		
		if ($main_contact !== $owner_phone) {
			$driver_contacts[] = $owner_phone;
		}
		
		if ($driver_phone !== $main_contact && $driver_phone !== $owner_phone) {
			$driver_contacts[] = $driver_phone;
		}
		
		if ($driver2_phone !== $main_contact && $driver2_phone !== $owner_phone && $driver2_phone !== $driver_phone) {
			$driver_contacts[] = $driver2_phone;
		}
		
		$driver_contacts = array_unique(array_filter($driver_contacts));
		
		echo "<h4>Unique Contacts for API Check</h4>";
		foreach ($driver_contacts as $contact) {
			echo "- " . $contact . "<br>";
		}
		
		// Test API call for first contact
		if (!empty($driver_contacts)) {
			$first_contact = $driver_contacts[0];
			echo "<h4>API Test for: " . $first_contact . "</h4>";
			$response = $this->get_info_by_brokersnapshot($first_contact);
			echo "API Response: ";
			var_dump($response);
		}
		
		return true;
	}
} 