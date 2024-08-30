<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReports extends TMSReportsHelper {
	
	public $table_main = 'reports';
	public $table_media = 'reports_files';
	
	public function __construct() {}
	
	public function create_medias() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_media;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
		        id mediumint(9) NOT NULL AUTO_INCREMENT,
		        booking_id mediumint(9) NOT NULL,
		        file_id bigint(20) NOT NULL,
		        PRIMARY KEY  (id),
		        FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}$this->table_main(id) ON DELETE CASCADE
		    ) $charset_collate;";
		
		dbDelta( $sql );
	}
	public function create_table() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main ;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
	        id mediumint(9) NOT NULL AUTO_INCREMENT,
	        user_id_added mediumint(9) NOT NULL,
	        date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	        user_id_updated mediumint(9) NULL AFTER,
	        date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	        status_post varchar(255) NULL DEFAULT NULL,
	        date_booked date NOT NULL,
	        dispatcher_initials varchar(10) NOT NULL,
	        reference_number varchar(20) NOT NULL,
	        pick_up_location varchar(255) NOT NULL,
	        delivery_location varchar(255) NOT NULL,
	        unit_number_name varchar(255) NOT NULL,
	        booked_rate decimal(10, 2) NOT NULL,
	        driver_rate decimal(10, 2) NOT NULL,
	        profit decimal(10, 2) NOT NULL,
	        pick_up_date date NOT NULL,
	        load_status varchar(50) NOT NULL,
	        instructions varchar(255),
	        source varchar(50),
	        attached_files longtext,
	        PRIMARY KEY  (id)
    	) $charset_collate;";
		
		dbDelta( $sql );
	}
	
	public function update_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		// Добавляем новые столбцы, если они еще не существуют
		$sql = "ALTER TABLE $table_name
        ADD COLUMN status_post varchar(255) NULL DEFAULT NULL AFTER date_updated;";
		$wpdb->query($sql);
	}
	
	function get_booking_files($booking_id) {
		global $wpdb;
		
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT file_id FROM {$wpdb->prefix}booking_files WHERE booking_id = %d",
			$booking_id
		));
		
		foreach ($results as $result) {
			$file_url = wp_get_attachment_url($result->file_id);
			echo '<a href="' . esc_url($file_url) . '">Download File</a><br>';
		}
	}
	
	public function add_new_report() {
		// check if it's ajax request (simple defence)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Фильтруем входные данные
			$MY_INPUT = filter_var_array( $_POST, [
				"date_booked"             => FILTER_SANITIZE_STRING,
				"dispatcher_initials"     => FILTER_SANITIZE_STRING,
				"reference_number"        => FILTER_SANITIZE_STRING,
				"pick_up_location_city"   => FILTER_SANITIZE_STRING,
				"pick_up_location_state"  => FILTER_SANITIZE_STRING,
				"pick_up_location_zip"    => FILTER_SANITIZE_STRING,
				"delivery_location_city"  => FILTER_SANITIZE_STRING,
				"delivery_location_state" => FILTER_SANITIZE_STRING,
				"delivery_location_zip"   => FILTER_SANITIZE_STRING,
				"unit_number_name"        => FILTER_SANITIZE_STRING,
				"booked_rate"             => FILTER_SANITIZE_STRING,
				"driver_rate"             => FILTER_SANITIZE_STRING,
				"profit"                  => FILTER_SANITIZE_STRING,
				"pick_up_date"            => FILTER_SANITIZE_STRING,
				"load_status"             => FILTER_SANITIZE_STRING,
				"instructions"            => [ 'filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY ],
				"source"                  => FILTER_SANITIZE_STRING,
				"post_status"             => FILTER_SANITIZE_STRING
			] );
			
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
			
			if ( ! empty( $MY_INPUT[ 'pick_up_location_city' ] ) && ! empty( $MY_INPUT[ 'pick_up_location_state' ] ) && ! empty( $MY_INPUT[ 'pick_up_location_zip' ] ) ) {
				$pick_up_full = $MY_INPUT[ 'pick_up_location_city' ] . ', ' . $MY_INPUT[ 'pick_up_location_state' ] . ', ' . $MY_INPUT[ 'pick_up_location_zip' ];
				
				$MY_INPUT[ 'pick_up_full' ] = $pick_up_full;
			}
			
			if ( ! empty( $MY_INPUT[ 'delivery_location_city' ] ) && ! empty( $MY_INPUT[ 'delivery_location_state' ] ) && ! empty( $MY_INPUT[ 'delivery_location_zip' ] ) ) {
				$delivery_full = $MY_INPUT[ 'delivery_location_city' ] . ', ' . $MY_INPUT[ 'delivery_location_state' ] . ', ' . $MY_INPUT[ 'delivery_location_zip' ];
				
				$MY_INPUT[ 'delivery_full' ] = $delivery_full;
			}
			
			$MY_INPUT[ "booked_rate" ] = $this->convert_to_number( $MY_INPUT[ "booked_rate" ] );
			$MY_INPUT[ "driver_rate" ] = $this->convert_to_number( $MY_INPUT[ "driver_rate" ] );
			$MY_INPUT[ "profit" ]      = $this->convert_to_number( $MY_INPUT[ "profit" ] );
			
			$result = $this->add_report( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				wp_send_json_success( [ 'message' => 'Report successfully added', 'data' => $MY_INPUT ] );
			}
			
			wp_send_json_error( [ 'message' => 'Report not create, error add in database' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function add_report( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$pick_up_location  = $data[ 'pick_up_full' ];
		$delivery_location = $data[ 'delivery_full' ];
		
		$instructions = ! empty( $data[ 'instructions' ] ) ? implode( ', ', $data[ 'instructions' ] ) : null;
		
		$attached_files = ! empty( $data[ 'uploaded_files' ] ) ? implode( ', ', $data[ 'uploaded_files' ] ) : null;
		
		$wpdb->insert( $table_name, array(
				'user_id_added'       => $user_id,
				'date_created'        => current_time( 'mysql' ),
				'user_id_updated'     => $user_id,
				'date_updated'        => current_time( 'mysql' ),
				'status_post'         => $data[ 'post_status' ],
				'date_booked'         => $data[ 'date_booked' ],
				'dispatcher_initials' => $data[ 'dispatcher_initials' ],
				'reference_number'    => $data[ 'reference_number' ],
				'pick_up_location'    => $pick_up_location,
				'delivery_location'   => $delivery_location,
				'unit_number_name'    => $data[ 'unit_number_name' ],
				'booked_rate'         => $data[ 'booked_rate' ],
				'driver_rate'         => $data[ 'driver_rate' ],
				'profit'              => $data[ 'profit' ],
				'pick_up_date'        => $data[ 'pick_up_date' ],
				'load_status'         => $data[ 'load_status' ],
				'instructions'        => $instructions,
				'source'              => $data[ 'source' ],
				'attached_files'      => $attached_files,
			), array(
				'%d',  // user_id_added
				'%s',  // date_created
				'%d',  // user_id_updated
				'%s',  // date_update
				'%s',  // status_post
				'%s',  // date_booked
				'%s',  // dispatcher_initials
				'%s',  // reference_number
				'%s',  // pick_up_location
				'%s',  // delivery_location
				'%s',  // unit_number_name
				'%f',  // booked_rate
				'%f',  // driver_rate
				'%f',  // profit
				'%s',  // pick_up_date
				'%s',  // load_status
				'%s',  // instructions
				'%s',  // source
				'%s',  // attached_files
			) );
		
		if ( $wpdb->insert_id ) {
			return $wpdb->insert_id; // Возвращаем ID добавленной записи
		} else {
			return false; // Ошибка при добавлении
		}
	}
	
	public function get_table_items () {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;// Замените на имя вашей таблицы
		$per_page = 10; // Количество записей на страницу

		// Получаем текущую страницу из параметров URL, если она есть, по умолчанию это страница 1
		$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

		// Подсчитываем общее количество записей в таблице
		$total_records = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		// Вычисляем количество страниц
		$total_pages = ceil( $total_records / $per_page );

		// Вычисляем смещение для текущей страницы
		$offset = ( $current_page - 1 ) * $per_page;

		// Запрашиваем записи с учетом разбивки по страницам
		$results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC LIMIT $offset, $per_page", ARRAY_A );
		
		return array(
			'results' => $results,
			'total_pages' => $total_pages,
			'current_pages' => $current_page,
		);
	}
	
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_report', array($this, 'add_new_report') );
	}
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table' ) );
//		add_action( 'after_setup_theme', array( $this, 'update_table' ) );
//		add_action( 'after_setup_theme', array( $this, 'create_medias' ) );
//		add_action('init', array($this ,'custom_frontpage_rewrite_rules'));
		
		$this->ajax_actions();
	}
	
//	function custom_frontpage_rewrite_rules() {
//		// Получаем ID стартовой страницы
//		$front_page_id = get_option('page_on_front');
//		// Если стартовая страница установлена, создаем правило перезаписи
//		if ($front_page_id) {
//			$front_page_slug = get_post_field('post_name', $front_page_id);
//
//			// Добавляем правило перезаписи для страниц пагинации на стартовой странице
//			add_rewrite_rule(
//				"^{$front_page_slug}/paged/([0-9]+)/?$",
//				'index.php?pagename=' . $front_page_slug . '&paged=$matches[1]',
//				'top'
//			);
//		}
//
//		flush_rewrite_rules();
//	}

}