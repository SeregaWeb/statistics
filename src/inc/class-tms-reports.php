<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReports extends TMSReportsHelper {
	
	public $table_main     = '';
	public $table_meta     = '';
	public $per_page_loads = 100;
	
	public function __construct() {
		$user_id       = get_current_user_id();
		$curent_tables = get_field( 'current_select', 'user_' . $user_id );
		if ( $curent_tables ) {
			$this->table_main = 'reports_' . strtolower( $curent_tables );
			$this->table_meta = 'reportsmeta_' . strtolower( $curent_tables );
		}
	}
	
	// GET ITEMS
	
	/**
	 * @param $args
	 * Get table items and filter
	 *
	 * @return array
	 */
	public function get_table_items( $args = array() ) {
		global $wpdb;
		
		$table_main   = $wpdb->prefix . $this->table_main;
		$table_meta   = $wpdb->prefix . $this->table_meta;
		$per_page     = $this->per_page_loads;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$sort_by      = ! empty( $args[ 'sort_by' ] ) ? $args[ 'sort_by' ] : 'date_booked';
		$sort_order   = ! empty( $args[ 'sort_order' ] ) && strtolower( $args[ 'sort_order' ] ) == 'asc' ? 'ASC'
			: 'DESC';
		
		
		$join_builder = "
			FROM $table_main AS main
			LEFT JOIN $table_meta AS dispatcher
				ON main.id = dispatcher.post_id
				AND dispatcher.meta_key = 'dispatcher_initials'
			LEFT JOIN $table_meta AS reference
				ON main.id = reference.post_id
				AND reference.meta_key = 'reference_number'
			LEFT JOIN $table_meta AS unit_number
				ON main.id = unit_number.post_id
				AND unit_number.meta_key = 'unit_number_name'
			LEFT JOIN $table_meta AS load_status
				ON main.id = load_status.post_id
				AND load_status.meta_key = 'load_status'
			LEFT JOIN $table_meta AS source
				ON main.id = source.post_id
				AND source.meta_key = 'source'
			LEFT JOIN $table_meta AS invoice
				ON main.id = invoice.post_id
				AND invoice.meta_key = 'invoice'
			LEFT JOIN $table_meta AS factoring_status
				ON main.id = factoring_status.post_id
				AND factoring_status.meta_key = 'factoring_status'
			WHERE 1=1
		";
		
		// Основной запрос
		$sql = "SELECT main.*,
			dispatcher.meta_value AS dispatcher_initials_value,
			reference.meta_value AS reference_number_value,
			unit_number.meta_value AS unit_number_value
	" . $join_builder;
		
		$where_conditions = array();
		$where_values     = array();
		
		// Фильтрация по статусу
		if ( ! empty( $args[ 'status_post' ] ) ) {
			$where_conditions[] = "main.status_post = %s";
			$where_values[]     = $args[ 'status_post' ];
		}
		
		// Фильтрация по dispatcher_initials
		if ( ! empty( $args[ 'dispatcher' ] ) ) {
			$where_conditions[] = "dispatcher.meta_value = %s";
			$where_values[]     = $args[ 'dispatcher' ];
		}
		
		if ( ! empty( $args[ 'load_status' ] ) ) {
			$where_conditions[] = "load_status.meta_value = %s";
			$where_values[]     = $args[ 'load_status' ];
		}
		
		if ( ! empty( $args[ 'invoice' ] ) ) {
			$where_conditions[] = "invoice.meta_value = %s";
			$where_values[]     = $args[ 'invoice' ];
		}
		
		if ( ! empty( $args[ 'factoring' ] ) ) {
			$where_conditions[] = "factoring_status.meta_value = %s";
			$where_values[]     = $args[ 'factoring' ];
		}
		
		if ( ! empty( $args[ 'source' ] ) ) {
			$where_conditions[] = "source.meta_value = %s";
			$where_values[]     = $args[ 'source' ];
		}
		
		// Фильтрация по reference_number
		if ( ! empty( $args[ 'my_search' ] ) ) {
			$where_conditions[] = "(reference.meta_value LIKE %s OR unit_number.meta_value LIKE %s)";
			$search_value       = '%' . $wpdb->esc_like( $args[ 'my_search' ] ) . '%';
			$where_values[]     = $search_value;
			$where_values[]     = $search_value;
		}
		
		if ( ! empty( $args[ 'month' ] ) && ! empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
			$where_values[]     = $args[ 'month' ];
		}
		
		// Фильтрация по только году
		if ( ! empty( $args[ 'year' ] ) && empty( $args[ 'month' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND YEAR(date_booked) = %d";
			$where_values[]     = $args[ 'year' ];
		}
		
		// Фильтрация по только месяцу
		if ( ! empty( $args[ 'month' ] ) && empty( $args[ 'year' ] ) ) {
			$where_conditions[] = "date_booked IS NOT NULL
        AND MONTH(date_booked) = %d";
			$where_values[]     = $args[ 'month' ];
		}
		
		// Применяем фильтры к запросу
		if ( ! empty( $where_conditions ) ) {
			$sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		// Подсчёт общего количества записей с учётом фильтров
		$total_records_sql = "SELECT COUNT(*)" . $join_builder;
		if ( ! empty( $where_conditions ) ) {
			$total_records_sql .= ' AND ' . implode( ' AND ', $where_conditions );
		}
		
		$total_records = $wpdb->get_var( $wpdb->prepare( $total_records_sql, ...$where_values ) );
		
		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );
		
		// Смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;
		
		// Добавляем сортировку и лимит для текущей страницы
		$sql            .= " ORDER BY main.$sort_by $sort_order LIMIT %d, %d";
		$where_values[] = $offset;
		$where_values[] = $per_page;
		
		// Выполняем запрос
		$main_results = $wpdb->get_results( $wpdb->prepare( $sql, ...$where_values ), ARRAY_A );
		
		// Собираем все ID записей для получения дополнительных метаданных
		$post_ids = wp_list_pluck( $main_results, 'id' );
		
		// Если есть записи, получаем метаданные
		$meta_data = array();
		if ( ! empty( $post_ids ) ) {
			$meta_sql     = "SELECT post_id, meta_key, meta_value
					 FROM $table_meta
					 WHERE post_id IN (" . implode( ',', array_map( 'absint', $post_ids ) ) . ")";
			$meta_results = $wpdb->get_results( $meta_sql, ARRAY_A );
			
			// Преобразуем метаданные в ассоциативный массив по post_id
			foreach ( $meta_results as $meta_row ) {
				$post_id = $meta_row[ 'post_id' ];
				if ( ! isset( $meta_data[ $post_id ] ) ) {
					$meta_data[ $post_id ] = array();
				}
				$meta_data[ $post_id ][ $meta_row[ 'meta_key' ] ] = $meta_row[ 'meta_value' ];
			}
		}
		
		if ( is_array( $main_results ) && ! empty( $main_results ) ) {
			// Объединяем основную таблицу с метаданными
			foreach ( $main_results as &$result ) {
				$post_id               = $result[ 'id' ];
				$result[ 'meta_data' ] = isset( $meta_data[ $post_id ] ) ? $meta_data[ $post_id ] : array();
			}
		}
		
		return array(
			'results'       => $main_results,
			'total_pages'   => $total_pages,
			'total_posts'   => $total_records,
			'current_pages' => $current_page,
		);
	}
	
	/**
	 * @param $ID
	 * Get report by id
	 *
	 * @return array|array[]|null
	 */
	public function get_report_by_id( $ID ) {
		global $wpdb;
		
		$table_main = "{$wpdb->prefix}{$this->table_main}";
		$table_meta = "{$wpdb->prefix}{$this->table_meta}"; // Убедись, что ты правильно указал имя таблицы мета-данных
		
		// SQL запрос для получения данных из основной таблицы и мета-данных
		$query = $wpdb->prepare( "
        SELECT main.*, meta.meta_key, meta.meta_value
        FROM $table_main AS main
        LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
        WHERE main.id = %d
    ", $ID );
		
		// Выполняем запрос
		$results = $wpdb->get_results( $query );
		
		// Преобразуем результаты, чтобы сгруппировать мета-данные
		if ( ! empty( $results ) ) {
			$report = array(
				'main' => array(),
				'meta' => array()
			);
			
			foreach ( $results as $row ) {
				// Заполняем основную информацию только один раз
				if ( empty( $report[ 'main' ] ) ) {
					$report[ 'main' ] = (array) $row;
					unset( $report[ 'main' ][ 'meta_key' ], $report[ 'main' ][ 'meta_value' ] );
				}
				
				// Добавляем мета-данные в массив
				if ( $row->meta_key && $row->meta_value ) {
					$report[ 'meta' ][ $row->meta_key ] = $row->meta_value;
				}
			}
			
			return $report;
		}
		
		return null; // Если нет результатов
	}
	
	/**
	 * @param $user_id
	 * @param $project_needs
	 * Get all loads count by user
	 *
	 * @return array
	 */
	public function get_load_counts_by_user_id( $user_id, $project_needs ) {
		global $wpdb;
		
		$table_odysseia       = $wpdb->prefix . 'reports_odysseia';
		$table_martlet        = $wpdb->prefix . 'reports_martlet';
		$table_endurance      = $wpdb->prefix . 'reports_endurance';
		$table_meta_odysseia  = $wpdb->prefix . 'reportsmeta_odysseia';
		$table_meta_martlet   = $wpdb->prefix . 'reportsmeta_martlet';
		$table_meta_endurance = $wpdb->prefix . 'reportsmeta_endurance';
		$result               = array();
		
		if ( is_numeric( array_search( 'Odysseia', $project_needs ) ) ) {
			$odysseia_count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(o.id)
            FROM {$table_odysseia} o
            INNER JOIN {$table_meta_odysseia} m ON o.id = m.post_id
            WHERE m.meta_key = 'dispatcher_initials'
            AND m.meta_value = %s
            AND o.status_post = 'publish'", strval( $user_id ) ) );
			
			$result[ 'Odysseia' ] = $odysseia_count;
		}
		
		if ( is_numeric( array_search( 'Martlet', $project_needs ) ) ) {
			$martlet_count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(m.id)
            FROM {$table_martlet} m
            INNER JOIN {$table_meta_martlet} meta ON m.id = meta.post_id
            WHERE meta.meta_key = 'dispatcher_initials'
            AND meta.meta_value = %s
            AND m.status_post = 'publish'", strval( $user_id ) ) );
			
			$result[ 'Martlet' ] = $martlet_count;
		}
		
		if ( is_numeric( array_search( 'Endurance', $project_needs ) ) ) {
			$endurance_count       = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(e.id)
            FROM {$table_endurance} e
            INNER JOIN {$table_meta_endurance} meta ON e.id = meta.post_id
            WHERE meta.meta_key = 'dispatcher_initials'
            AND meta.meta_value = %s
            AND e.status_post = 'publish'", strval( $user_id ) ) );
			$result[ 'Endurance' ] = $endurance_count;
		}
		
		return $result;
	}
	
	/**
	 * @param $record_id
	 * function check required fields for load before publication
	 *
	 * @return array
	 */
	public function check_empty_fields( $record_id ) {
		global $wpdb;
		
		// Таблица мета-данных
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
		// Список обязательных полей для проверки
		$required_fields = [
			'customer_id'            => 'Customer ID',
			'contact_name'           => 'Contact Name',
			'contact_phone'          => 'Contact Phone',
			'contact_email'          => 'Contact Email',
			'dispatcher_initials'    => 'Dispatcher Initials',
			'reference_number'       => 'Reference Number',
			'pick_up_location'       => 'Pick-up Location',
			'delivery_location'      => 'Delivery Location',
			'unit_number_name'       => 'Unit Number',
			'booked_rate'            => 'Booked Rate',
			'driver_rate'            => 'Driver Rate',
			'profit'                 => 'Profit',
			'load_status'            => 'Load Status',
			'load_type'              => 'Load Type',
			'additional_contacts'    => 'Additional Contacts',
			'attached_file_required' => 'Attached File Required'
		];
		
		$empty_fields = [];
		
		// Проходим по каждому обязательному полю
		foreach ( $required_fields as $meta_key => $label ) {
			// Получаем значение для текущего поля из таблицы мета-данных
			$meta_value = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_value
			FROM $table_meta_name
			WHERE post_id = %d AND meta_key = %s
		", $record_id, $meta_key ) );
			
			// Проверяем пустые значения или некорректные даты/числа
			if ( empty( $meta_value ) || $meta_value === '0000-00-00' || ( $meta_value === '0.00' && $meta_key !== 'load_status' ) ) {
				$empty_fields[] = '<strong>' . $label . '</strong>';
			}
		}
		
		// Возвращаем сообщение о незаполненных полях
		if ( ! empty( $empty_fields ) ) {
			return array(
				'message' => "The following fields are empty: " . implode( ', ', $empty_fields ),
				'status'  => false
			);
		} else {
			return array( 'message' => "All required fields are filled.", 'status' => true );
		}
	}
	
	// GET ITEMS END
	
	// AJAX ACTIONS
	
	/**
	 * function update draft report (tab 1)
	 * @return void
	 */
	public function update_new_draft_report() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"   => FILTER_SANITIZE_STRING,
				"contact_name"  => FILTER_SANITIZE_STRING,
				"contact_phone" => FILTER_SANITIZE_STRING,
				"contact_email" => FILTER_SANITIZE_STRING,
				"post_id"       => FILTER_SANITIZE_STRING,
				"read_only"     => FILTER_SANITIZE_STRING,
			] );
			
			if ( isset( $MY_INPUT[ 'read_only' ] ) ) {
				wp_send_json_success();
			}
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				$additional_names  = filter_var_array( $_POST[ 'additional_contact_name' ], FILTER_SANITIZE_STRING );
				$additional_phones = filter_var_array( $_POST[ 'additional_contact_phone' ], FILTER_SANITIZE_STRING );
				$additional_emails = filter_var_array( $_POST[ 'additional_contact_email' ], FILTER_SANITIZE_EMAIL );
				
				foreach ( $additional_names as $index => $name ) {
					$additional_contacts[] = [
						'name'  => $name,
						'phone' => $additional_phones[ $index ] ?? '',
						'email' => $additional_emails[ $index ] ?? ''
					];
				}
			}
			
			// Convert the additional contacts array to JSON format
			$MY_INPUT[ 'additional_contacts' ] = json_encode( $additional_contacts );
			
			// Insert the company report
			$result = $this->update_report_draft_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [
					'id_created_post' => $result,
					'message'         => 'Company successfully update',
					'data'            => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error updating to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report (tab 5)
	 * @return void
	 */
	public function update_billing_report() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id"          => FILTER_SANITIZE_STRING,
				"factoring_status" => FILTER_SANITIZE_STRING,
				"invoice"          => FILTER_SANITIZE_STRING,
				"load_problem"     => FILTER_SANITIZE_STRING,
			] );
			
			// Insert the company report
			$result = $this->update_report_billing_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [
					'message' => 'Billing info successfully update',
					'data'    => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not update, error updating to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function add new report (tab 1)
	 * @return void
	 */
	public function add_new_report_draft() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"customer_id"   => FILTER_SANITIZE_STRING,
				"contact_name"  => FILTER_SANITIZE_STRING,
				"contact_phone" => FILTER_SANITIZE_STRING,
				"contact_email" => FILTER_SANITIZE_STRING,
			] );
			
			$additional_contacts = [];
			if ( ! empty( $_POST[ 'additional_contact_name' ] ) && ! empty( $_POST[ 'additional_contact_phone' ] ) && ! empty( $_POST[ 'additional_contact_email' ] ) ) {
				
				$additional_names  = filter_var_array( $_POST[ 'additional_contact_name' ], FILTER_SANITIZE_STRING );
				$additional_phones = filter_var_array( $_POST[ 'additional_contact_phone' ], FILTER_SANITIZE_STRING );
				$additional_emails = filter_var_array( $_POST[ 'additional_contact_email' ], FILTER_SANITIZE_EMAIL );
				
				foreach ( $additional_names as $index => $name ) {
					$additional_contacts[] = [
						'name'  => $name,
						'phone' => $additional_phones[ $index ] ?? '',
						'email' => $additional_emails[ $index ] ?? ''
					];
				}
			}
			
			// Convert the additional contacts array to JSON format
			$MY_INPUT[ 'additional_contacts' ] = json_encode( $additional_contacts );
			
			// Check if 'set_up' is 'completed' and set the timestamp
			$set_up_timestamp = null;
			if ( isset( $MY_INPUT[ 'set_up' ] ) && $MY_INPUT[ 'set_up' ] === 'completed' ) {
				$set_up_timestamp = current_time( 'mysql' ); // or you can use date('Y-m-d H:i:s') if needed
			}
			
			$MY_INPUT[ 'completed' ] = $set_up_timestamp;
			
			$MY_INPUT[ "status_post" ] = 'draft';
			
			// Insert the company report
			$result = $this->add_report_draft_in_db( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				wp_send_json_success( [
					'id_created_post' => $result,
					'message'         => 'Company successfully added',
					'data'            => $MY_INPUT
				] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'Company not created, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report files (tab 4)
	 * @return void
	 */
	public function update_files_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING
			] );
			
			if ( ! empty( $_FILES[ 'screen_picture' ] ) ) {
				$files = $_FILES[ 'screen_picture' ];
				if ( $files[ 'size' ] > 0 ) {
					
					$uploaded_files = [];
					
					$file = [
						'name'     => $files[ 'name' ],
						'type'     => $files[ 'type' ],
						'tmp_name' => $files[ 'tmp_name' ],
						'error'    => $files[ 'error' ],
						'size'     => $files[ 'size' ]
					];
					
					// Используем wp_handle_upload для обработки загрузки
					$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
					
					if ( ! isset( $upload_result[ 'error' ] ) ) {
						// Данные о файле
						$file_url  = $upload_result[ 'url' ];
						$file_type = $upload_result[ 'type' ];
						$file_path = $upload_result[ 'file' ];
						
						// Подготовка данных для записи в медиабиблиотеку
						$attachment = array(
							'guid'           => $file_url,
							'post_mime_type' => $file_type,
							'post_title'     => basename( $file_url ),
							'post_content'   => '',
							'post_status'    => 'inherit'
						);
						
						// Вставляем запись в базу данных (в таблицу attachments)
						$attachment_id = wp_insert_attachment( $attachment, $file_path );
						
						// Генерация метаданных для вложения и обновление базы данных
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
						wp_update_attachment_metadata( $attachment_id, $attachment_data );
						
						// Теперь у вас есть ID загруженного файла
						$uploaded_files[] = $attachment_id;
					} else {
						// Ошибка загрузки файла
						wp_send_json_error( [ 'message' => $upload_result[ 'error' ] ] );
					}
					
					// Теперь у нас есть массив $uploaded_files с загруженными файлами
					$MY_INPUT[ 'screen_picture' ] = $uploaded_files;
				}
			}
			
			if ( ! empty( $_FILES[ 'update_rate_confirmation' ] ) ) {
				$files = $_FILES[ 'update_rate_confirmation' ];
				if ( $files[ 'size' ] > 0 ) {
					
					$uploaded_files = [];
					
					$file = [
						'name'     => $files[ 'name' ],
						'type'     => $files[ 'type' ],
						'tmp_name' => $files[ 'tmp_name' ],
						'error'    => $files[ 'error' ],
						'size'     => $files[ 'size' ]
					];
					
					// Используем wp_handle_upload для обработки загрузки
					$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
					
					if ( ! isset( $upload_result[ 'error' ] ) ) {
						// Данные о файле
						$file_url  = $upload_result[ 'url' ];
						$file_type = $upload_result[ 'type' ];
						$file_path = $upload_result[ 'file' ];
						
						// Подготовка данных для записи в медиабиблиотеку
						$attachment = array(
							'guid'           => $file_url,
							'post_mime_type' => $file_type,
							'post_title'     => basename( $file_url ),
							'post_content'   => '',
							'post_status'    => 'inherit'
						);
						
						// Вставляем запись в базу данных (в таблицу attachments)
						$attachment_id = wp_insert_attachment( $attachment, $file_path );
						
						// Генерация метаданных для вложения и обновление базы данных
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
						wp_update_attachment_metadata( $attachment_id, $attachment_data );
						
						// Теперь у вас есть ID загруженного файла
						$uploaded_files[] = $attachment_id;
					} else {
						// Ошибка загрузки файла
						wp_send_json_error( [ 'message' => $upload_result[ 'error' ] ] );
					}
					
					// Теперь у нас есть массив $uploaded_files с загруженными файлами
					$MY_INPUT[ 'updated_rate_confirmation' ] = $uploaded_files;
				}
			}
			
			if ( ! empty( $_FILES[ 'attached_file_required' ] ) ) {
				$files          = $_FILES[ 'attached_file_required' ];
				$uploaded_files = [];
				
				$file = [
					'name'     => $files[ 'name' ],
					'type'     => $files[ 'type' ],
					'tmp_name' => $files[ 'tmp_name' ],
					'error'    => $files[ 'error' ],
					'size'     => $files[ 'size' ]
				];
				
				// Используем wp_handle_upload для обработки загрузки
				$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
				
				if ( ! isset( $upload_result[ 'error' ] ) ) {
					// Данные о файле
					$file_url  = $upload_result[ 'url' ];
					$file_type = $upload_result[ 'type' ];
					$file_path = $upload_result[ 'file' ];
					
					// Подготовка данных для записи в медиабиблиотеку
					$attachment = array(
						'guid'           => $file_url,
						'post_mime_type' => $file_type,
						'post_title'     => basename( $file_url ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
					
					// Вставляем запись в базу данных (в таблицу attachments)
					$attachment_id = wp_insert_attachment( $attachment, $file_path );
					
					// Генерация метаданных для вложения и обновление базы данных
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );
					
					// Теперь у вас есть ID загруженного файла
					$uploaded_files[] = $attachment_id;
				} else {
					// Ошибка загрузки файла
					wp_send_json_error( [ 'message' => $upload_result[ 'error' ] ] );
				}
				
				// Теперь у нас есть массив $uploaded_files с загруженными файлами
				$MY_INPUT[ 'uploaded_file_required' ] = $uploaded_files;
			}
			
			if ( ! empty( $_FILES[ 'attached_files' ] ) ) {
				$files          = $_FILES[ 'attached_files' ];
				$uploaded_files = [];
				
				foreach ( $files[ 'name' ] as $key => $value ) {
					if ( $files[ 'name' ][ $key ] ) {
						$file = [
							'name'     => $files[ 'name' ][ $key ],
							'type'     => $files[ 'type' ][ $key ],
							'tmp_name' => $files[ 'tmp_name' ][ $key ],
							'error'    => $files[ 'error' ][ $key ],
							'size'     => $files[ 'size' ][ $key ]
						];
						
						// Используем wp_handle_upload для обработки загрузки
						$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
						
						if ( ! isset( $upload_result[ 'error' ] ) ) {
							// Данные о файле
							$file_url  = $upload_result[ 'url' ];
							$file_type = $upload_result[ 'type' ];
							$file_path = $upload_result[ 'file' ];
							
							// Подготовка данных для записи в медиабиблиотеку
							$attachment = array(
								'guid'           => $file_url,
								'post_mime_type' => $file_type,
								'post_title'     => basename( $file_url ),
								'post_content'   => '',
								'post_status'    => 'inherit'
							);
							
							// Вставляем запись в базу данных (в таблицу attachments)
							$attachment_id = wp_insert_attachment( $attachment, $file_path );
							
							// Генерация метаданных для вложения и обновление базы данных
							require_once( ABSPATH . 'wp-admin/includes/image.php' );
							$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
							wp_update_attachment_metadata( $attachment_id, $attachment_data );
							
							// Теперь у вас есть ID загруженного файла
							$uploaded_files[] = $attachment_id;
						} else {
							// Ошибка загрузки файла
							wp_send_json_error( [ 'message' => $upload_result[ 'error' ] ] );
						}
					}
				}
				
				// Теперь у нас есть массив $uploaded_files с загруженными файлами
				$MY_INPUT[ 'uploaded_files' ] = $uploaded_files;
			}
			
			$result = $this->add_report_files( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Files successfully update', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Files not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report (tab 3)
	 * @return void
	 */
	public function update_shipper_info() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$data = $_POST;
			
			if ( ! isset( $data[ 'pick_up_location_address_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Pick up not fill' ] );
			}
			
			if ( ! isset( $data[ 'delivery_location_address_id' ] ) ) {
				wp_send_json_error( [ 'message' => 'Delivery not fill' ] );
			}
			
			$pick_up_location  = [];
			$delivery_location = [];
			
			for ( $i = 0; $i < count( $data[ 'pick_up_location_address_id' ] ); $i ++ ) {
				$pick_up_location[] = [
					'address_id'    => $data[ 'pick_up_location_address_id' ][ $i ],
					'address'       => $data[ 'pick_up_location_address' ][ $i ],
					'short_address' => $data[ 'pick_up_location_short_address' ][ $i ],
					'contact'       => $data[ 'pick_up_location_contact' ][ $i ],
					'date'          => $data[ 'pick_up_location_date' ][ $i ],
					'info'          => $data[ 'pick_up_location_info' ][ $i ],
					'type'          => $data[ 'pick_up_location_type' ][ $i ]
				];
			}
			
			for ( $i = 0; $i < count( $data[ 'delivery_location_address_id' ] ); $i ++ ) {
				$delivery_location[] = [
					'address_id'    => $data[ 'delivery_location_address_id' ][ $i ],
					'address'       => $data[ 'delivery_location_address' ][ $i ],
					'short_address' => $data[ 'delivery_location_short_address' ][ $i ],
					'contact'       => $data[ 'delivery_location_contact' ][ $i ],
					'date'          => $data[ 'delivery_location_date' ][ $i ],
					'info'          => $data[ 'delivery_location_info' ][ $i ],
					'type'          => $data[ 'delivery_location_type' ][ $i ]
				];
			}
			
			$pick_up_location_json  = json_encode( $pick_up_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			$delivery_location_json = json_encode( $delivery_location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			
			$data[ 'pick_up_location_json' ]  = $pick_up_location_json;
			$data[ 'delivery_location_json' ] = $delivery_location_json;
			$result                           = $this->add_new_shipper_info( $data );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Shipper info successfully update', 'data' => $data ] );
			}
			
			wp_send_json_error( [ 'message' => 'Shipper not update, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update draft report (tab 2)
	 * @return void
	 */
	public function add_new_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"date_booked"         => FILTER_SANITIZE_STRING,
				"dispatcher_initials" => FILTER_SANITIZE_STRING,
				"reference_number"    => FILTER_SANITIZE_STRING,
				"unit_number_name"    => FILTER_SANITIZE_STRING,
				"booked_rate"         => FILTER_SANITIZE_STRING,
				"driver_rate"         => FILTER_SANITIZE_STRING,
				"profit"              => FILTER_SANITIZE_STRING,
				"pick_up_date"        => FILTER_SANITIZE_STRING,
				"load_status"         => FILTER_SANITIZE_STRING,
				"instructions"        => [ 'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY ],
				"source"              => FILTER_SANITIZE_STRING,
				"load_type"           => FILTER_SANITIZE_STRING,
				"commodity"           => FILTER_SANITIZE_STRING,
				"weight"              => FILTER_SANITIZE_STRING,
				"notes"               => FILTER_SANITIZE_STRING,
				"post_id"             => FILTER_SANITIZE_STRING,
			] );
			
			
			if ( $MY_INPUT[ 'load_status' ] === 'cancelled' ) {
				$MY_INPUT[ "booked_rate" ] = 0;
				$MY_INPUT[ "driver_rate" ] = 0;
				$MY_INPUT[ "profit" ]      = 0;
			} else {
				$MY_INPUT[ "booked_rate" ] = $this->convert_to_number( $MY_INPUT[ "booked_rate" ] );
				$MY_INPUT[ "driver_rate" ] = $this->convert_to_number( $MY_INPUT[ "driver_rate" ] );
				$MY_INPUT[ "profit" ]      = $this->convert_to_number( $MY_INPUT[ "profit" ] );
				
				$MY_INPUT[ 'percent_booked_rate' ] = $MY_INPUT[ "booked_rate" ] * 0.02;
				$MY_INPUT[ 'true_profit' ]         = $MY_INPUT[ "booked_rate" ] - ( $MY_INPUT[ 'percent_booked_rate' ] + $MY_INPUT[ "driver_rate" ] );
				
			}
			
			
			$result = $this->add_load( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Report successfully added', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Report not create, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function delete one file (tab 1)
	 * @return void
	 */
	public function delete_open_image() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"image-id"     => FILTER_SANITIZE_STRING,
				"image-fields" => FILTER_SANITIZE_STRING,
				"post_id"      => FILTER_SANITIZE_STRING,
			] );
			
			$result = $this->remove_one_image_in_db( $MY_INPUT );
			
			if ( $result === true ) {
				wp_send_json_success( [ 'message' => 'Remove success', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error remove in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function update post status
	 * @return void
	 */
	public function update_post_status() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
			] );
			
			$MY_INPUT[ 'post_status' ] = 'publish';
			
			$result = $this->update_post_status_in_db( $MY_INPUT );
			
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Load successfully loaded', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	/**
	 * function change status post
	 * @return void
	 */
	public function rechange_status_load() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			
			$MY_INPUT = filter_var_array( $_POST, [
				"post_id" => FILTER_SANITIZE_STRING,
			] );
			
			$post_id = $MY_INPUT[ "post_id" ];
			
			$message_arr = $this->check_empty_fields( $post_id );
			
			$status_type    = $message_arr[ 'status' ];
			$status_message = $message_arr[ 'message' ];
			$template       = '';
			
			if ( $status_type ) {
				$template = $this->message_top( 'success', $status_message, 'js-update-post-status', 'Publish' );
			} else {
				$template = $this->message_top( 'danger', $status_message );
			}
			
			if ( $template ) {
				wp_send_json_success( [ 'template' => $template ] );
			}
			
			wp_send_json_error( [ 'message' => 'Error update status' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	// AJAX ACTIONS END
	
	
	// UPDATE IN DATABASE
	
	/**
	 * @param $data
	 * function update in db (tab 5)
	 *
	 * @return true|WP_Error
	 */
	public function update_report_billing_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'load_problem'    => $data[ 'load_problem' ]
		);
		
		$post_meta = array(
			'invoice'          => $data[ 'invoice' ],
			'factoring_status' => $data[ 'factoring_status' ],
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // load_problem
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta ); // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 1)
	 *
	 * @return true|WP_Error
	 */
	public function update_report_draft_in_db( $data ) {
		global $wpdb;
		
		$post_id = $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
		);
		
		$post_meta = array(
			'customer_id'         => $data[ 'customer_id' ],
			'contact_name'        => $data[ 'contact_name' ],
			'contact_phone'       => $data[ 'contact_phone' ],
			'contact_email'       => $data[ 'contact_email' ],
			'additional_contacts' => $data[ 'additional_contacts' ],
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta ); // Update was successful
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function create in db (tab 1)
	 *
	 * @return int|WP_Error
	 */
	public function add_report_draft_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$insert_params = array(
			'user_id_added'   => $user_id,
			'date_created'    => current_time( 'mysql' ),
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'status_post'     => $data[ 'status_post' ],
		);
		
		$post_meta = array(
			'customer_id'         => $data[ 'customer_id' ],
			'contact_name'        => $data[ 'contact_name' ],
			'contact_phone'       => $data[ 'contact_phone' ],
			'contact_email'       => $data[ 'contact_email' ],
			'additional_contacts' => $data[ 'additional_contacts' ],
		);
		
		$result = $wpdb->insert( $table_name, $insert_params, array(
			'%d',  // user_id_added
			'%s',  // date_created
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // status_post
		) );
		
		// Check if the insert was successful
		
		if ( $result ) {
			$id_new_post = $wpdb->insert_id;
			$result      = $this->update_post_meta_data( $id_new_post, $post_meta );
			
			return $id_new_post; // Return the ID of the added record
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Check for specific unique constraint violations
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'company_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this name already exists.' );
				} elseif ( strpos( $error, 'mc_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this MC number already exists.' );
				} elseif ( strpos( $error, 'dot_number' ) !== false ) {
					return new WP_Error( 'db_error', 'A company with this DOT number already exists.' );
				}
			}
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error adding the company report to the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 3)
	 *
	 * @return true|WP_Error
	 */
	public function add_new_shipper_info( $data ) {
		global $wpdb;
		
		$post_id = + $data[ 'post_id' ]; // ID of the post to update
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
		);
		
		$post_meta = array(
			'pick_up_location'  => $data[ 'pick_up_location_json' ],
			'delivery_location' => $data[ 'delivery_location_json' ],
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // customer_id
			'%s',  // date_updated
			'%s',  // pick_up_location
			'%s',  // delivery_location
		), array( '%d' ) );
		
		if ( false === $result ) {
			var_dump( "Update failed: " . $wpdb->last_error );
			var_dump( "Last query: " . $wpdb->last_query );
		}
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta );
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the shipper report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 4)
	 *
	 * @return true|WP_Error
	 */
	public function add_report_files( $data ) {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		$user_id         = get_current_user_id();
		$post_id         = $data[ 'post_id' ];
		
		// Получаем текущие данные по мета-ключам для поста
		$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table_meta_name} WHERE post_id = %d", $post_id ), ARRAY_A );
		
		// Преобразуем мета-данные в удобный массив
		$current_data = array_column( $meta_data, 'meta_value', 'meta_key' );
		
		
		// Подготавливаем новые прикрепленные файлы (добавляем к существующим)
		$new_attached_files = ! empty( $data[ 'uploaded_files' ] ) ? implode( ', ', $data[ 'uploaded_files' ] ) : '';
		if ( $new_attached_files && ! empty( $current_data[ 'attached_files' ] ) ) {
			$new_attached_files = $current_data[ 'attached_files' ] . ', ' . $new_attached_files;
		} elseif ( empty( $new_attached_files ) ) {
			$new_attached_files = $current_data[ 'attached_files' ];
		}
		
		// Используем переданные данные для `attached_file_required`, если они есть, иначе оставляем существующие
		$attached_files_required = ! empty( $data[ 'uploaded_file_required' ] )
			? implode( ', ', $data[ 'uploaded_file_required' ] ) : $current_data[ 'attached_file_required' ];
		
		// Обновляем подтверждение ставки, если оно изменилось
		$updated_rate_confirmation = ! empty( $data[ 'updated_rate_confirmation' ] )
			? implode( ', ', $data[ 'updated_rate_confirmation' ] ) : $current_data[ 'updated_rate_confirmation' ];
		
		$updated_screen_picture = ! empty( $data[ 'screen_picture' ] ) ? implode( ', ', $data[ 'screen_picture' ] )
			: $current_data[ 'screen_picture' ];
		
		
		if ( ! empty( $data[ 'updated_rate_confirmation' ] ) ) {
			if ( implode( ', ', $data[ 'updated_rate_confirmation' ] ) !== $current_data[ 'updated_rate_confirmation' ] ) {
				var_dump( 'NEED SEND MESSAGE USER' );
			}
		}
		
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
		);
		
		$post_meta = array(
			'attached_files'            => $new_attached_files,
			'attached_file_required'    => $attached_files_required,
			'updated_rate_confirmation' => $updated_rate_confirmation,
			'screen_picture'            => $updated_screen_picture,
		);
		
		// Specify the condition (WHERE clause)
		$where = array( 'id' => $post_id );
		
		// Update the record in the database
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
		), array( '%d' ) );
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta );
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error updating the company report in the database: ' . $error );
		}
	}
	
	/**
	 * @param $data
	 * function update in db (tab 2)
	 *
	 * @return bool|WP_Error
	 */
	public function add_load( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		// Prepare the instructions field
		$instructions = ! empty( $data[ 'instructions' ] ) ? implode( ', ', $data[ 'instructions' ] ) : null;
		
		// Prepare the data to update
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'pick_up_date'    => $data[ 'pick_up_date' ],
			'date_booked'     => $data[ 'date_booked' ],
		);
		
		$post_meta = array(
			'load_status'         => $data[ 'load_status' ],
			'instructions'        => $instructions,
			'source'              => $data[ 'source' ],
			'load_type'           => $data[ 'load_type' ],
			'commodity'           => $data[ 'commodity' ],
			'weight'              => $data[ 'weight' ],
			'notes'               => $data[ 'notes' ],
			'dispatcher_initials' => $data[ 'dispatcher_initials' ],
			'reference_number'    => $data[ 'reference_number' ],
			'unit_number_name'    => $data[ 'unit_number_name' ],
			'booked_rate'         => $data[ 'booked_rate' ],
			'driver_rate'         => $data[ 'driver_rate' ],
			'profit'              => $data[ 'profit' ],
			'percent_booked_rate' => $data[ 'percent_booked_rate' ],
			'true_profit'         => $data[ 'true_profit' ],
		);
		
		$post_id = $data[ 'post_id' ];
		// Specify the condition (WHERE clause) - assuming post_id is passed in the data array
		$where = array( 'id' => $data[ 'post_id' ] );
		// Perform the update
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // pick_up_date
			'%s',  // date_booked
		), array( '%d' ) // The data type of the where clause (id is an integer)
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return $this->update_post_meta_data( $post_id, $post_meta );
		} else {
			return false; // Error occurred during the update
		}
	}
	
	/**
	 * @param $data
	 * update load status
	 *
	 * @return bool
	 */
	public function update_post_status_in_db( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$update_params = array(
			'user_id_updated' => $user_id,
			'date_updated'    => current_time( 'mysql' ),
			'status_post'     => $data[ 'post_status' ],
		);
		
		// Specify the condition (WHERE clause) - assuming post_id is passed in the data array
		$where = array( 'id' => $data[ 'post_id' ] );
		// Perform the update
		$result = $wpdb->update( $table_name, $update_params, $where, array(
			'%d',  // user_id_updated
			'%s',  // date_updated
			'%s',  // post_status
		), array( '%d' ) // The data type of the where clause (id is an integer)
		);
		
		// Check if the update was successful
		if ( $result !== false ) {
			return true; // Update was successful
		} else {
			return false; // Error occurred during the update
		}
	}
	
	/**
	 * @param $data
	 * remove image in db
	 *
	 * @return true|WP_Error
	 */
	public function remove_one_image_in_db( $data ) {
		global $wpdb;
		$table_meta_name = $wpdb->prefix . $this->table_meta; // Имя таблицы мета данных
		
		// Извлекаем ID изображения и имя мета-ключа
		$image_id    = intval( $data[ 'image-id' ] );
		$image_field = sanitize_text_field( $data[ 'image-fields' ] );
		$post_id     = intval( $data[ 'post_id' ] );
		
		// Проверяем корректность входных данных
		if ( ! $image_id || ! $image_field || ! $post_id ) {
			return new WP_Error( 'invalid_input', 'Invalid image ID, field name or post ID.' );
		}
		
		// Извлекаем текущее значение поля meta_key для поста
		$current_value = $wpdb->get_var( $wpdb->prepare( "
		SELECT meta_value
		FROM $table_meta_name
		WHERE post_id = %d AND meta_key = %s", $post_id, $image_field ) );
		
		if ( $current_value ) {
			$new_value = '';
			
			// Для поля attached_files, где значения хранятся через запятую
			if ( $image_field === 'attached_files' ) {
				$ids = explode( ',', $current_value );
				$ids = array_map( 'intval', $ids );
				
				// Удаляем указанный ID
				$new_ids   = array_diff( $ids, array( $image_id ) );
				$new_value = implode( ',', $new_ids );
			} elseif ( $image_field === 'attached_file_required' || $image_field === 'updated_rate_confirmation' || $image_field === 'screen_picture' ) {
				// Для полей attached_file_required и updated_rate_confirmation
				if ( $current_value == $image_id ) {
					$new_value = ''; // Удаляем значение, если оно совпадает
				} else {
					return new WP_Error( 'id_not_found', 'The specified ID was not found in the field.' );
				}
			} else {
				return new WP_Error( 'invalid_field', 'Invalid field name.' );
			}
			
			// Обновляем запись в таблице мета-данных
			$result = $wpdb->update( $table_meta_name, array( 'meta_value' => $new_value ), array(
				'post_id'  => $post_id,
				'meta_key' => $image_field
			), array( '%s' ),       // Формат для meta_value
				array( '%d', '%s' ) // Форматы для post_id и meta_key
			);
			
			// Удаляем вложение из медиа библиотеки
			$deleted = wp_delete_attachment( $image_id, true );
			
			if ( ! $deleted ) {
				return new WP_Error( 'delete_failed', 'Failed to delete the attachment.' );
			}
			
			// Проверяем результат обновления в базе данных
			if ( $result !== false ) {
				return true; // Успешное обновление
			} else {
				return new WP_Error( 'db_update_failed', 'Failed to update the database.' );
			}
		} else {
			return new WP_Error( 'no_value_found', 'No value found for the specified field.' );
		}
	}
	
	/**
	 * @param $post_id
	 * @param $meta_data
	 * update post meta fields in db
	 *
	 * @return true|WP_Error
	 */
	function update_post_meta_data( $post_id, $meta_data ) {
		global $wpdb;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
		foreach ( $meta_data as $meta_key => $meta_value ) {
			$existing = $wpdb->get_var( $wpdb->prepare( "
            SELECT id FROM $table_meta_name
            WHERE post_id = %d AND meta_key = %s
        ", $post_id, $meta_key ) );
			
			if ( $existing ) {
				// Обновляем существующую запись
				$wpdb->update( $table_meta_name, array( 'meta_value' => $meta_value ), array( 'id' => $existing ), array( '%s' ), array( '%d' ) );
			} else {
				// Вставляем новую запись
				$wpdb->insert( $table_meta_name, array(
					'post_id'    => $post_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value
				), array( '%d', '%s', '%s' ) );
			}
		}
		
		// Проверка на ошибки
		if ( $wpdb->last_error ) {
			return new WP_Error( 'db_error', 'Ошибка при обновлении метаданных: ' . $wpdb->last_error );
		}
		
		return true;
	}
	
	// UPDATE IN DATABASE END
	
	// CREATE TABLE AND UPDATE SQL
	
	/**
	 * create table
	 * @return void
	 */
	public function create_table() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $table_name (
			    id mediumint(9) NOT NULL AUTO_INCREMENT,
			    user_id_added mediumint(9) NOT NULL,
			    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    user_id_updated mediumint(9) NULL,
			    date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    pick_up_date datetime NOT NULL,
			    delivery_date datetime NOT NULL,
			    date_booked datetime NOT NULL,
			    load_problem datetime NULL DEFAULT NULL,
			    status_post varchar(50) NULL DEFAULT NULL,
			    PRIMARY KEY (id),
			    INDEX idx_date_created (date_created),
			    INDEX idx_pick_up_date (pick_up_date),
			    INDEX idx_delivery_date (delivery_date),
			    INDEX idx_date_booked (date_booked),
			    INDEX idx_load_problem (load_problem)
			) $charset_collate;";
			
			dbDelta( $sql );
			
			$sql = "CREATE TABLE $table_meta_name (
		        id mediumint(9) NOT NULL AUTO_INCREMENT,
		        post_id mediumint(9) NOT NULL,
		        meta_key longtext,
		        meta_value longtext,
		        PRIMARY KEY  (id),
                INDEX idx_post_id (post_id),
         		INDEX idx_meta_key (meta_key(191)),
         		INDEX idx_meta_key_value (meta_key(191), meta_value(191));
    		) $charset_collate;";
			
			dbDelta( $sql );
		}
	}
	
	/**
	 * update table add new fields and indexes isset fields
	 * @return void
	 */
	public function update_tables_with_delivery_and_indexes() {
		global $wpdb;
		
		$tables = $this->tms_tables;
		
		foreach ( $tables as $val ) {
			$table_name      = $wpdb->prefix . 'reports_' . strtolower( $val );
			$table_meta_name = $wpdb->prefix . 'reportsmeta_' . strtolower( $val );
			
			// Добавляем новое поле delivery_date и индексы на все даты
			$wpdb->query( "
            ALTER TABLE $table_name
            ADD COLUMN delivery_date datetime NOT NULL AFTER pick_up_date,
            ADD INDEX idx_date_created (date_created),
            ADD INDEX idx_pick_up_date (pick_up_date),
            ADD INDEX idx_delivery_date (delivery_date),
            ADD INDEX idx_date_booked (date_booked),
            ADD INDEX idx_load_problem (load_problem);
        " );
			
			// Добавляем индексы в мета таблицу
			$wpdb->query( "
            ALTER TABLE $table_meta_name
            ADD INDEX idx_post_id (post_id),
            ADD INDEX idx_meta_key (meta_key(191)),
            ADD INDEX idx_meta_key_value (meta_key(191), meta_value(191));
        " );
		}
	}
	
	// INIT Actions
	
	/**
	 * init all ajax actions for fork
	 * @return void
	 */
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_report', array( $this, 'add_new_report' ) );
		add_action( 'wp_ajax_add_new_draft_report', array( $this, 'add_new_report_draft' ) );
		add_action( 'wp_ajax_update_new_draft_report', array( $this, 'update_new_draft_report' ) );
		add_action( 'wp_ajax_update_billing_report', array( $this, 'update_billing_report' ) );
		add_action( 'wp_ajax_update_files_report', array( $this, 'update_files_report' ) );
		add_action( 'wp_ajax_delete_open_image', array( $this, 'delete_open_image' ) );
		add_action( 'wp_ajax_update_shipper_info', array( $this, 'update_shipper_info' ) );
		add_action( 'wp_ajax_update_post_status', array( $this, 'update_post_status' ) );
		add_action( 'wp_ajax_rechange_status_load', array( $this, 'rechange_status_load' ) );
	}
	
	/**
	 * init functions need for start work all functions loads
	 * @return void
	 */
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table' ) );
		
		//TODO UPDATE DATABASE AFTER CREATE , up speed search in database (NOT delete)
		//add_action( 'after_setup_theme', array( $this, 'update_tables_with_delivery_and_indexes' ) );
		
		$this->ajax_actions();
	}
	
	// CREATE TABLE AND UPDATE SQL END
	
}