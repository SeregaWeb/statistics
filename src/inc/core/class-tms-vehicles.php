<?php

class TMSVehicles {
	
	public $table_main = 'vehicles';
	public $table_meta = 'vehicles_meta';
	
	public $log_controller = false;
	public $helper = false;
	
	public function __construct() {
		$this->log_controller = new TMSLogs();
		$this->helper = new TMSCommonHelper();
	}
	
	public function init() {
		$this->ajax_actions();

		if ( current_user_can( 'administrator' ) ) {
			$this->create_tables();
		}
	}
	
	public function ajax_actions() {
		$actions = array(
			'add_vehicle' => 'add_vehicle',
			'update_vehicle' => 'update_vehicle',
			'get_vehicle' => 'get_vehicle',
			'delete_vehicle' => 'delete_vehicle',
			'optimize_vehicles_tables' => 'optimize_vehicles_tables',
			'remove_one_vehicle' => 'remove_one_vehicle',
			'upload_vehicle_helper' => 'upload_vehicle_helper',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this->helper, 'need_login' ] );
		}
	}
	
	public function create_tables() {
		$this->table_vehicle();
		$this->table_vehicle_meta();
		$this->register_vehicle_tables();
	}
	
	public function table_vehicle() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			    id mediumint(9) NOT NULL AUTO_INCREMENT,
			    user_id_added mediumint(9) NOT NULL,
			    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    user_id_updated mediumint(9) NULL,
			    date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    status_post varchar(50) NULL DEFAULT NULL,
			    PRIMARY KEY (id),
			    INDEX idx_date_created (date_created),
			    INDEX idx_status_post (status_post),
			    INDEX idx_user_id_added (user_id_added)
			) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	public function table_vehicle_meta() {
		global $wpdb;
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_meta_name (
		        id mediumint(9) NOT NULL AUTO_INCREMENT,
		        post_id mediumint(9) NOT NULL,
		        meta_key longtext,
		        meta_value longtext,
		        PRIMARY KEY  (id),
                INDEX idx_post_id (post_id),
         		INDEX idx_meta_key (meta_key(191)),
         		INDEX idx_meta_key_value (meta_key(191), meta_value(191))
    		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	public function register_vehicle_tables() {
		// Additional tables can be registered here if needed
	}
	
	/**
	 * Get vehicle by ID
	 *
	 * @param int $ID Vehicle ID
	 * @return array|null
	 */
	public function get_vehicle_by_id( $ID ) {
		global $wpdb;
		
		$table_main = "{$wpdb->prefix}{$this->table_main}";
		$table_meta = "{$wpdb->prefix}{$this->table_meta}";
		
		$query = $wpdb->prepare( "
        SELECT main.*, meta.meta_key, meta.meta_value
        FROM $table_main AS main
        LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
        WHERE main.id = %d
    ", $ID );
		
		$results = $wpdb->get_results( $query );
		
		if ( ! empty( $results ) ) {
			$vehicle = array(
				'main' => array(),
				'meta' => array()
			);
			
			foreach ( $results as $row ) {
				if ( empty( $vehicle[ 'main' ] ) ) {
					$vehicle[ 'main' ] = (array) $row;
					unset( $vehicle[ 'main' ][ 'meta_key' ], $vehicle[ 'main' ][ 'meta_value' ] );
				}
				
				if ( $row->meta_key && $row->meta_value ) {
					$vehicle[ 'meta' ][ $row->meta_key ] = $row->meta_value;
				}
			}
			
			return $vehicle;
		}
		
		return null;
	}

	/**
	 * Get multiple vehicles by IDs in one query (avoids N+1).
	 *
	 * @param array $ids Array of vehicle IDs (integers).
	 * @return array Keyed by ID: [ id => array( 'main' => ..., 'meta' => ... ), ... ]
	 */
	public function get_vehicles_by_ids( array $ids ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$ids         = array_unique( $ids );
		$table_main  = $wpdb->prefix . $this->table_main;
		$table_meta  = $wpdb->prefix . $this->table_meta;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$query  = $wpdb->prepare(
			"SELECT main.*, meta.meta_key, meta.meta_value
			FROM $table_main AS main
			LEFT JOIN $table_meta AS meta ON main.id = meta.post_id
			WHERE main.id IN ($placeholders)",
			$ids
		);
		$results = $wpdb->get_results( $query );

		if ( empty( $results ) ) {
			return array();
		}

		$by_id = array();
		foreach ( $results as $row ) {
			$id = (int) $row->id;
			if ( ! isset( $by_id[ $id ] ) ) {
				$by_id[ $id ] = array(
					'main' => array(),
					'meta' => array(),
				);
			}
			if ( empty( $by_id[ $id ]['main'] ) ) {
				$main = (array) $row;
				unset( $main['meta_key'], $main['meta_value'] );
				$by_id[ $id ]['main'] = $main;
			}
			if ( ! empty( $row->meta_key ) && isset( $row->meta_value ) ) {
				$by_id[ $id ]['meta'][ $row->meta_key ] = $row->meta_value;
			}
		}

		return $by_id;
	}

	/**
	 * Get vehicles by driver IDs (attached_driver). One vehicle per driver; keyed by driver_id.
	 *
	 * @param array $driver_ids Array of driver IDs (attached_driver in vehicles_meta).
	 * @return array Keyed by driver_id: [ driver_id => array( 'main' => ..., 'meta' => ... ), ... ]
	 */
	public function get_vehicles_by_driver_ids( array $driver_ids ) {
		global $wpdb;

		$driver_ids = array_filter( array_map( 'absint', $driver_ids ) );
		if ( empty( $driver_ids ) ) {
			return array();
		}

		$table_meta = $wpdb->prefix . $this->table_meta;
		$placeholders = implode( ',', array_fill( 0, count( $driver_ids ), '%s' ) );

		$query = $wpdb->prepare(
			"SELECT post_id AS vehicle_id, meta_value AS driver_id
			FROM $table_meta
			WHERE meta_key = 'attached_driver' AND meta_value IN ($placeholders)",
			$driver_ids
		);
		$rows = $wpdb->get_results( $query );

		if ( empty( $rows ) ) {
			return array();
		}

		$driver_to_vehicle = array();
		foreach ( $rows as $row ) {
			$driver_id = (int) $row->driver_id;
			$vehicle_id = (int) $row->vehicle_id;
			if ( ! isset( $driver_to_vehicle[ $driver_id ] ) ) {
				$driver_to_vehicle[ $driver_id ] = $vehicle_id;
			}
		}

		$vehicle_ids = array_values( array_unique( $driver_to_vehicle ) );
		$vehicles_by_id = $this->get_vehicles_by_ids( $vehicle_ids );

		$by_driver = array();
		foreach ( $driver_to_vehicle as $driver_id => $vehicle_id ) {
			if ( isset( $vehicles_by_id[ $vehicle_id ] ) ) {
				$by_driver[ $driver_id ] = $vehicles_by_id[ $vehicle_id ];
			}
		}

		return $by_driver;
	}

	/**
	 * Get all vehicles with pagination
	 *
	 * @param array $args
	 * @return array
	 */
	public function get_vehicles( $args = array() ) {
		global $wpdb;
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$per_page = isset( $args[ 'per_page' ] ) ? intval( $args[ 'per_page' ] ) : 30;
		$current_page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
		$offset = ( $current_page - 1 ) * $per_page;
		
		$where = "WHERE 1=1";
		
		if ( isset( $args[ 'status' ] ) && $args[ 'status' ] !== '' ) {
			$status = sanitize_text_field( $args[ 'status' ] );
			$where .= $wpdb->prepare( " AND main.status_post = %s", $status );
		}
		
		if ( isset( $args[ 'search' ] ) && ! empty( $args[ 'search' ] ) ) {
			$search = sanitize_text_field( $args[ 'search' ] );
			$where .= $wpdb->prepare( " AND (vin.meta_value LIKE %s OR plates.meta_value LIKE %s OR make.meta_value LIKE %s)", 
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}
		
		$sql = "
			SELECT DISTINCT main.id, main.date_created, main.date_updated, main.status_post
			FROM $table_main AS main
			LEFT JOIN $table_meta AS vin ON main.id = vin.post_id AND vin.meta_key = 'vin'
			LEFT JOIN $table_meta AS plates ON main.id = plates.post_id AND plates.meta_key = 'plates'
			LEFT JOIN $table_meta AS make ON main.id = make.post_id AND make.meta_key = 'make'
			$where
			ORDER BY main.date_created DESC
			LIMIT %d, %d
		";
		
		$vehicles = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $per_page ), ARRAY_A );
		
		$total_sql = "
			SELECT COUNT(DISTINCT main.id)
			FROM $table_main AS main
			LEFT JOIN $table_meta AS vin ON main.id = vin.post_id AND vin.meta_key = 'vin'
			LEFT JOIN $table_meta AS plates ON main.id = plates.post_id AND plates.meta_key = 'plates'
			LEFT JOIN $table_meta AS make ON main.id = make.post_id AND make.meta_key = 'make'
			$where
		";
		
		$total = $wpdb->get_var( $total_sql );

		$ids    = wp_list_pluck( $vehicles, 'id' );
		$by_id  = $this->get_vehicles_by_ids( $ids );
		$result = array();
		foreach ( $vehicles as $vehicle ) {
			$id = isset( $vehicle['id'] ) ? (int) $vehicle['id'] : 0;
			if ( $id && isset( $by_id[ $id ] ) ) {
				$result[] = $by_id[ $id ];
			}
		}

		return array(
			'vehicles' => $result,
			'total' => intval( $total ),
			'per_page' => $per_page,
			'current_page' => $current_page,
			'total_pages' => ceil( $total / $per_page )
		);
	}
	
	/**
	 * Add new vehicle
	 */
	public function add_vehicle() {
		if ( ! check_ajax_referer( 'vehicle_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'User not logged in' ) );
		}

		$attached_driver = isset( $_POST['attached_driver'] ) ? trim( (string) $_POST['attached_driver'] ) : '';
		if ( $attached_driver !== '' && is_numeric( $attached_driver ) ) {
			$conflict = $this->get_vehicle_already_using_driver( (int) $attached_driver, 0 );
			if ( $conflict && is_array( $conflict ) ) {
				$make  = isset( $conflict['make'] ) ? (string) $conflict['make'] : '';
				$model = isset( $conflict['model'] ) ? (string) $conflict['model'] : '';
				$year  = isset( $conflict['year'] ) ? (string) $conflict['year'] : '';
				$label = trim( $make . ' ' . $model . ' ' . $year );
				$label = $label !== '' ? $label : 'ID ' . ( isset( $conflict['vehicle_id'] ) ? (int) $conflict['vehicle_id'] : 0 );
				wp_send_json_error( array(
					'message' => 'This driver is already assigned to vehicle (' . $label . '). One driver can only be assigned to one vehicle.',
				) );
			}
		}
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Insert main record
		$wpdb->insert(
			$table_main,
			array(
				'user_id_added' => $user_id,
				'status_post' => 'publish'
			),
			array( '%d', '%s' )
		);
		
		$vehicle_id = $wpdb->insert_id;
		
		if ( ! $vehicle_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create vehicle' ) );
		}
		
		// Save meta data
		$this->save_vehicle_meta( $vehicle_id, $_POST );
		
		wp_send_json_success( array(
			'message' => 'Vehicle added successfully',
			'vehicle_id' => $vehicle_id
		) );
	}
	
	/**
	 * Update vehicle
	 */
	public function update_vehicle() {
		if ( ! check_ajax_referer( 'vehicle_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		global $wpdb;
		
		$vehicle_id = isset( $_POST[ 'vehicle_id' ] ) ? intval( $_POST[ 'vehicle_id' ] ) : 0;
		
		if ( ! $vehicle_id ) {
			wp_send_json_error( array( 'message' => 'Vehicle ID is required' ) );
		}

		$attached_driver = isset( $_POST['attached_driver'] ) ? trim( (string) $_POST['attached_driver'] ) : '';
		if ( $attached_driver !== '' && is_numeric( $attached_driver ) ) {
			$conflict = $this->get_vehicle_already_using_driver( (int) $attached_driver, $vehicle_id );
			if ( $conflict && is_array( $conflict ) ) {
				$make  = isset( $conflict['make'] ) ? (string) $conflict['make'] : '';
				$model = isset( $conflict['model'] ) ? (string) $conflict['model'] : '';
				$year  = isset( $conflict['year'] ) ? (string) $conflict['year'] : '';
				$label = trim( $make . ' ' . $model . ' ' . $year );
				$label = $label !== '' ? $label : 'ID ' . ( isset( $conflict['vehicle_id'] ) ? (int) $conflict['vehicle_id'] : 0 );
				wp_send_json_error( array(
					'message' => 'This driver is already assigned to vehicle (' . $label . '). One driver can only be assigned to one vehicle.',
				) );
			}
		}
		
		$user_id = get_current_user_id();
		$table_main = $wpdb->prefix . $this->table_main;
		
		// Update main record
		$wpdb->update(
			$table_main,
			array(
				'user_id_updated' => $user_id
			),
			array( 'id' => $vehicle_id ),
			array( '%d' ),
			array( '%d' )
		);
		
		// Save meta data
		$this->save_vehicle_meta( $vehicle_id, $_POST );
		
		wp_send_json_success( array(
			'message' => 'Vehicle updated successfully',
			'vehicle_id' => $vehicle_id
		) );
	}
	
	/**
	 * Check if driver is already assigned to another vehicle. One driver = one vehicle.
	 *
	 * @param int $driver_id          Attached driver ID.
	 * @param int $exclude_vehicle_id Optional. Vehicle ID to exclude (current vehicle when editing).
	 * @return array|null Null if driver is free; else array with keys vehicle_id, make, model, year for the conflicting vehicle.
	 */
	private function get_vehicle_already_using_driver( $driver_id, $exclude_vehicle_id = 0 ) {
		global $wpdb;

		$driver_id = (int) $driver_id;
		if ( $driver_id <= 0 ) {
			return null;
		}

		$table_meta  = $wpdb->prefix . $this->table_meta;
		$exclude_sql = $exclude_vehicle_id > 0 ? $wpdb->prepare( ' AND post_id != %d', $exclude_vehicle_id ) : '';

		$other_vehicle_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $table_meta WHERE meta_key = 'attached_driver' AND meta_value = %s" . $exclude_sql,
				(string) $driver_id
			)
		);

		if ( ! $other_vehicle_id ) {
			return null;
		}

		$other_vehicle_id = (int) $other_vehicle_id;
		$make             = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'make'", $other_vehicle_id ) );
		$model            = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'model'", $other_vehicle_id ) );
		$year             = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $table_meta WHERE post_id = %d AND meta_key = 'vehicle_year'", $other_vehicle_id ) );

		return array(
			'vehicle_id' => $other_vehicle_id,
			'make'       => $make !== null && $make !== '' ? (string) $make : '',
			'model'      => $model !== null && $model !== '' ? (string) $model : '',
			'year'       => $year !== null && $year !== '' ? (string) $year : '',
		);
	}

	/**
	 * Save vehicle meta data
	 *
	 * @param int $vehicle_id
	 * @param array $data
	 */
	private function save_vehicle_meta( $vehicle_id, $data ) {
		global $wpdb;
		
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$meta_fields = array(
			'vehicle_type',
			'make',
			'model',
			'vehicle_year',
			'vin',
			'tare_weight',
			'gvwr',
			'dock_high',
			'eld_model',
			'plates',
			'license_state',
			'plates_status',
			'plates_expiration_date',
			'fuel_type',
			'vehicle_registration',
			'registration_expiration_date',
			'fleet_registration_id_card',
			'annual_vehicle_inspection',
			'dot_inspection',
			'unit_number',
			'unit_number_name',
			'attached_driver',
		);
		
		foreach ( $meta_fields as $field ) {
			$value = isset( $data[ $field ] ) ? $data[ $field ] : '';
			
			// Handle checkbox (dock_high)
			if ( $field === 'dock_high' ) {
				// For semi-truck, always set to 'on'
				if ( isset( $data[ 'vehicle_type' ] ) && $data[ 'vehicle_type' ] === 'semi-truck' ) {
					$value = 'on';
				} else {
					$value = isset( $data[ $field ] ) && $data[ $field ] ? 'on' : '';
				}
			}
			
			// Handle single file uploads
			if ( in_array( $field, array( 'vehicle_registration', 'fleet_registration_id_card', 'annual_vehicle_inspection' ) ) ) {
				if ( isset( $_FILES[ $field ] ) && $_FILES[ $field ][ 'error' ] === UPLOAD_ERR_OK ) {
					$file_id = $this->handle_file_upload( $_FILES[ $field ] );
					if ( $file_id ) {
						$value = $file_id;
					}
				} elseif ( ! empty( $value ) ) {
					// Keep existing file if no new upload
					$value = intval( $value );
				} else {
					continue;
				}
			}
			
			// Handle multiple file uploads (dot_inspection) - only during form submission
			// Note: Multiple files are usually uploaded via upload_vehicle_helper, not during form save
			// So we just keep existing value if no new upload
			if ( $field === 'dot_inspection' ) {
				if ( ! empty( $_FILES[ $field ] ) && ! empty( $_FILES[ $field ][ 'name' ][ 0 ] ) ) {
					$uploaded_files = $this->multy_upload_files( $field );
					if ( ! empty( $uploaded_files ) ) {
						// Get existing files
						$existing_value = $wpdb->get_var( $wpdb->prepare( "
							SELECT meta_value
							FROM $table_meta
							WHERE post_id = %d AND meta_key = %s
						", $vehicle_id, $field ) );
						
						// Merge with existing files
						$existing_files = ! empty( $existing_value ) ? explode( ', ', $existing_value ) : array();
						$all_files = array_merge( $existing_files, $uploaded_files );
						$value = implode( ', ', $all_files );
					} else {
						// Keep existing files if upload failed
						$existing_value = $wpdb->get_var( $wpdb->prepare( "
							SELECT meta_value
							FROM $table_meta
							WHERE post_id = %d AND meta_key = %s
						", $vehicle_id, $field ) );
						$value = $existing_value ? $existing_value : '';
					}
				} elseif ( ! empty( $value ) ) {
					// Keep existing files if no new upload (value comes from form, but usually empty for multiple files)
					// Get existing value from DB
					$existing_value = $wpdb->get_var( $wpdb->prepare( "
						SELECT meta_value
						FROM $table_meta
						WHERE post_id = %d AND meta_key = %s
					", $vehicle_id, $field ) );
					$value = $existing_value ? $existing_value : '';
				} else {
					// If no value and no upload, keep existing or skip
					$existing_value = $wpdb->get_var( $wpdb->prepare( "
						SELECT meta_value
						FROM $table_meta
						WHERE post_id = %d AND meta_key = %s
					", $vehicle_id, $field ) );
					if ( $existing_value ) {
						$value = $existing_value;
					} else {
						continue;
					}
				}
			}
			
			// Delete existing meta
			$wpdb->delete(
				$table_meta,
				array(
					'post_id' => $vehicle_id,
					'meta_key' => $field
				),
				array( '%d', '%s' )
			);
			
			// Insert new meta if value is not empty (or is 0 for numeric fields)
			$numeric_fields = array( 'tare_weight', 'gvwr', 'vehicle_year' );
			
			if ( in_array( $field, $numeric_fields ) ) {
				// For numeric fields, save even if empty to clear old values
				$value = trim( $value );
				$wpdb->insert(
					$table_meta,
					array(
						'post_id' => $vehicle_id,
						'meta_key' => $field,
						'meta_value' => $value
					),
					array( '%d', '%s', '%s' )
				);
			} elseif ( $value !== '' ) {
				// For other fields, only save if not empty
				$wpdb->insert(
					$table_meta,
					array(
						'post_id' => $vehicle_id,
						'meta_key' => $field,
						'meta_value' => $value
					),
					array( '%d', '%s', '%s' )
				);
			}
		}
	}
	
	/**
	 * Handle multiple file uploads
	 *
	 * @param string $fields_name Field name
	 * @return array Array of attachment IDs
	 */
	private function multy_upload_files( $fields_name ) {
		if ( ! isset( $_FILES[ $fields_name ] ) || empty( $_FILES[ $fields_name ][ 'name' ][ 0 ] ) ) {
			return []; // No files to upload
		}
		
		$files          = $_FILES[ $fields_name ];
		$uploaded_files = [];
		$errors         = [];
		$user_id        = get_current_user_id();
		
		foreach ( $files[ 'name' ] as $key => $original_name ) {
			if ( empty( $original_name ) ) {
				continue;
			}
			
			// Check for upload errors
			if ( $files[ 'error' ][ $key ] !== UPLOAD_ERR_OK ) {
				$errors[] = "Upload error: " . $original_name;
				continue;
			}
			
			// Validate file type
			$file_info     = pathinfo( $original_name );
			$extension     = isset( $file_info[ 'extension' ] ) ? strtolower( $file_info[ 'extension' ] ) : '';
			$allowed_types = $this->get_allowed_formats();
			
			if ( ! in_array( $extension, $allowed_types ) ) {
				$errors[] = "Unsupported file format: " . $original_name;
				continue;
			}
			
			// Validate file size (max 50MB)
			$max_size = 50 * 1024 * 1024; // 50MB
			
			if ( $files[ 'size' ][ $key ] > $max_size ) {
				$errors[] = "File is too large (max 50MB): " . $original_name;
				continue;
			}
			
			// Generate unique file name: {user_id}_{timestamp}_{random}_{filename}.{extension}
			$timestamp    = time();
			$unique       = rand( 1000, 99999 );
			$new_filename = "{$user_id}_{$timestamp}_{$unique}_" . sanitize_file_name( $file_info[ 'filename' ] );
			if ( ! empty( $extension ) ) {
				$new_filename .= '.' . $extension;
			}
			
			// Prepare file array for upload
			$file = [
				'name'     => $new_filename,
				'type'     => $files[ 'type' ][ $key ],
				'tmp_name' => $files[ 'tmp_name' ][ $key ],
				'error'    => $files[ 'error' ][ $key ],
				'size'     => $files[ 'size' ][ $key ],
			];
			
			// Upload file using handle_file_upload
			$file_id = $this->handle_file_upload( $file );
			
			if ( $file_id ) {
				$uploaded_files[] = $file_id;
			}
		}
		
		return $uploaded_files;
	}
	
	/**
	 * Handle file upload
	 *
	 * @param array $file
	 * @return int|false Attachment ID or false on failure
	 */
	private function handle_file_upload( $file ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		
		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
		
		if ( isset( $upload[ 'error' ] ) ) {
			return false;
		}
		
		$attachment = array(
			'post_mime_type' => $upload[ 'type' ],
			'post_title' => sanitize_file_name( basename( $upload[ 'file' ] ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		
		$attach_id = wp_insert_attachment( $attachment, $upload[ 'file' ] );
		
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
		
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload[ 'file' ] );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		
		return $attach_id;
	}
	
	/**
	 * Get vehicle (AJAX)
	 */
	public function get_vehicle() {
		if ( ! check_ajax_referer( 'vehicle_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		$vehicle_id = isset( $_POST[ 'vehicle_id' ] ) ? intval( $_POST[ 'vehicle_id' ] ) : 0;
		
		if ( ! $vehicle_id ) {
			wp_send_json_error( array( 'message' => 'Vehicle ID is required' ) );
		}
		
		$vehicle = $this->get_vehicle_by_id( $vehicle_id );
		
		if ( ! $vehicle ) {
			wp_send_json_error( array( 'message' => 'Vehicle not found' ) );
		}
		
		wp_send_json_success( $vehicle );
	}
	
	/**
	 * Delete vehicle (Admin only)
	 */
	public function delete_vehicle() {
		if ( ! check_ajax_referer( 'vehicle_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		// Check if user is administrator
		$current_user = wp_get_current_user();
		if ( ! in_array( 'administrator', $current_user->roles ) ) {
			wp_send_json_error( array( 'message' => 'Only administrators can delete vehicles' ) );
		}
		
		global $wpdb;
		
		$vehicle_id = isset( $_POST[ 'vehicle_id' ] ) ? intval( $_POST[ 'vehicle_id' ] ) : 0;
		
		if ( ! $vehicle_id ) {
			wp_send_json_error( array( 'message' => 'Vehicle ID is required' ) );
		}
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Delete meta
		$wpdb->delete( $table_meta, array( 'post_id' => $vehicle_id ), array( '%d' ) );
		
		// Delete main record
		$wpdb->delete( $table_main, array( 'id' => $vehicle_id ), array( '%d' ) );
		
		wp_send_json_success( array( 'message' => 'Vehicle deleted successfully' ) );
	}
	
	/**
	 * Optimize vehicles tables (AJAX)
	 */
	public function optimize_vehicles_tables() {
		if ( ! check_ajax_referer( 'vehicle_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		$optimization_type = isset( $_POST[ 'optimization_type' ] ) ? sanitize_text_field( $_POST[ 'optimization_type' ] ) : 'fast';
		
		if ( $optimization_type === 'full' ) {
			$results = $this->perform_full_vehicles_optimization();
		} else {
			$results = $this->perform_fast_vehicles_optimization();
		}
		
		wp_send_json_success( array(
			'message' => 'Optimization completed',
			'results' => $results
		) );
	}
	
	/**
	 * Perform fast optimization (indexes only)
	 */
	public function perform_fast_vehicles_optimization() {
		global $wpdb;
		$results = array();
		
		$main_table = $wpdb->prefix . $this->table_main;
		$results[ 'main_table' ] = $this->optimize_vehicles_main_table_fast( $main_table );
		
		$meta_table = $wpdb->prefix . $this->table_meta;
		$results[ 'meta_table' ] = $this->optimize_vehicles_meta_table_fast( $meta_table );
		
		return $results;
	}
	
	/**
	 * Perform full optimization (structural changes)
	 */
	public function perform_full_vehicles_optimization() {
		global $wpdb;
		$results = array();
		
		$main_table = $wpdb->prefix . $this->table_main;
		$results[ 'main_table' ] = $this->optimize_vehicles_main_table_full( $main_table );
		
		$meta_table = $wpdb->prefix . $this->table_meta;
		$results[ 'meta_table' ] = $this->optimize_vehicles_meta_table_full( $meta_table );
		
		return $results;
	}
	
	/**
	 * Fast optimization for main vehicles table
	 */
	private function optimize_vehicles_main_table_fast( $table_name ) {
		global $wpdb;
		$changes = array();
		
		$indexes = array(
			'idx_user_date_created' => 'user_id_added, date_created',
			'idx_status_date_created' => 'status_post, date_created',
			'idx_user_status' => 'user_id_added, status_post',
		);
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Full optimization for main vehicles table
	 */
	private function optimize_vehicles_main_table_full( $table_name ) {
		global $wpdb;
		$changes = array();
		
		$alter_queries = array(
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY user_id_added BIGINT UNSIGNED NOT NULL",
			"ALTER TABLE $table_name MODIFY user_id_updated BIGINT UNSIGNED NULL",
			"ALTER TABLE $table_name MODIFY date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
			"ALTER TABLE $table_name MODIFY date_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
		);
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		$indexes = array(
			'idx_user_date_created' => 'user_id_added, date_created',
			'idx_status_date_created' => 'status_post, date_created',
			'idx_user_status' => 'user_id_added, status_post',
			'idx_date_created_status' => 'date_created, status_post',
		);
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Fast optimization for vehicles meta table
	 */
	private function optimize_vehicles_meta_table_fast( $table_name ) {
		global $wpdb;
		$changes = array();
		
		$indexes = array(
			'idx_post_meta_key' => 'post_id, meta_key(191)',
			'idx_meta_key_value' => 'meta_key(191), meta_value(191)',
		);
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Full optimization for vehicles meta table
	 */
	private function optimize_vehicles_meta_table_full( $table_name ) {
		global $wpdb;
		$changes = array();
		
		$alter_queries = array(
			"ALTER TABLE $table_name MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE $table_name MODIFY post_id BIGINT UNSIGNED NOT NULL",
		);
		
		foreach ( $alter_queries as $query ) {
			$wpdb->query( $query );
			$changes[] = "Updated data types for better performance";
		}
		
		$indexes = array(
			'idx_post_meta_key' => 'post_id, meta_key(191)',
			'idx_meta_key_value' => 'meta_key(191), meta_value(191)',
		);
		
		foreach ( $indexes as $index_name => $columns ) {
			$index_exists = $wpdb->get_var( "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'" );
			if ( ! $index_exists ) {
				$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($columns)" );
				$changes[] = "Added composite index: $index_name";
			}
		}
		
		$wpdb->query( "OPTIMIZE TABLE $table_name" );
		$changes[] = "Table optimized";
		
		return $changes;
	}
	
	/**
	 * Remove one vehicle file (AJAX)
	 */
	public function remove_one_vehicle() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ) );
		}
		
		$MY_INPUT = filter_var_array( $_POST, array(
			"image-id"     => FILTER_SANITIZE_STRING,
			"image-fields" => FILTER_SANITIZE_STRING,
			"post_id"      => FILTER_SANITIZE_STRING,
		) );
		
		$result = $this->remove_one_file_in_db( $MY_INPUT );
		
		if ( $result === true ) {
			wp_send_json_success( array( 'message' => 'Remove success', 'data' => $MY_INPUT ) );
		}
		
		wp_send_json_error( array( 'message' => 'Error remove in database' ) );
	}
	
	/**
	 * Remove one file from database
	 */
	private function remove_one_file_in_db( $data ) {
		global $wpdb;
		
		$table_meta_name = $wpdb->prefix . $this->table_meta;
		
		$image_id    = intval( $data[ 'image-id' ] );
		$image_field = sanitize_text_field( $data[ 'image-fields' ] );
		$post_id     = intval( $data[ 'post_id' ] );
		
		if ( ! $image_id || ! $image_field || ! $post_id ) {
			return new WP_Error( 'invalid_input', 'Invalid image ID, field name or post ID.' );
		}
		
		$current_value = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_value
			FROM $table_meta_name
			WHERE post_id = %d AND meta_key = %s
		", $post_id, $image_field ) );
		
		if ( $current_value ) {
			// Check if this is a multiple files field (contains comma)
			if ( strpos( $current_value, ', ' ) !== false ) {
				// Multiple files field (e.g., dot_inspection)
				$files_array = explode( ', ', $current_value );
				$files_array = array_map( 'trim', $files_array );
				$files_array = array_map( 'intval', $files_array );
				
				// Remove the file ID from array
				$key = array_search( $image_id, $files_array );
				if ( $key !== false ) {
					unset( $files_array[ $key ] );
					$files_array = array_values( $files_array ); // Re-index array
					
					$new_value = ! empty( $files_array ) ? implode( ', ', $files_array ) : '';
					
					$result = $wpdb->update(
						$table_meta_name,
						array( 'meta_value' => $new_value ),
						array(
							'post_id'  => $post_id,
							'meta_key' => $image_field
						),
						array( '%s' ),
						array( '%d', '%s' )
					);
					
					$deleted = wp_delete_attachment( $image_id, true );
					
					if ( ! $deleted ) {
						return new WP_Error( 'delete_failed', 'Failed to delete the attachment.' );
					}
					
					if ( $result !== false ) {
						return true;
					} else {
						return new WP_Error( 'db_update_failed', 'Failed to update the database.' );
					}
				} else {
					return new WP_Error( 'id_not_found', 'The specified ID was not found in the field.' );
				}
			} else {
				// Single file field
				if ( $current_value == $image_id ) {
					$new_value = '';
					
					$result = $wpdb->update(
						$table_meta_name,
						array( 'meta_value' => $new_value ),
						array(
							'post_id'  => $post_id,
							'meta_key' => $image_field
						),
						array( '%s' ),
						array( '%d', '%s' )
					);
					
					$deleted = wp_delete_attachment( $image_id, true );
					
					if ( ! $deleted ) {
						return new WP_Error( 'delete_failed', 'Failed to delete the attachment.' );
					}
					
					if ( $result !== false ) {
						return true;
					} else {
						return new WP_Error( 'db_update_failed', 'Failed to update the database.' );
					}
				} else {
					return new WP_Error( 'id_not_found', 'The specified ID was not found in the field.' );
				}
			}
		} else {
			return new WP_Error( 'no_value_found', 'No value found for the specified field.' );
		}
	}
	
	/**
	 * Upload vehicle helper file (AJAX)
	 */
	public function upload_vehicle_helper() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ) );
		}
		
		$vehicle_id = isset( $_POST[ 'vehicle_id' ] ) ? intval( $_POST[ 'vehicle_id' ] ) : 0;
		
		if ( ! $vehicle_id ) {
			wp_send_json_error( array( 'message' => 'Please create the vehicle first, then you can upload files.' ) );
		}
		
		$keys_names = array(
			'vehicle_registration',
			'fleet_registration_id_card',
			'annual_vehicle_inspection',
		);
		
		// Handle multiple file uploads for dot_inspection
		if ( ! empty( $_FILES[ 'dot_inspection' ] ) && ! empty( $_FILES[ 'dot_inspection' ][ 'name' ][ 0 ] ) ) {
			$uploaded_files = $this->multy_upload_files( 'dot_inspection' );
			
			if ( ! empty( $uploaded_files ) ) {
				global $wpdb;
				$table_meta = $wpdb->prefix . $this->table_meta;
				
				// Get existing files
				$existing_value = $wpdb->get_var( $wpdb->prepare( "
					SELECT meta_value
					FROM $table_meta
					WHERE post_id = %d AND meta_key = 'dot_inspection'
				", $vehicle_id ) );
				
				// Merge with existing files
				$existing_files = ! empty( $existing_value ) ? explode( ', ', $existing_value ) : array();
				$all_files = array_merge( $existing_files, $uploaded_files );
				$new_value = implode( ', ', $all_files );
				
				// Delete existing meta
				$wpdb->delete(
					$table_meta,
					array(
						'post_id' => $vehicle_id,
						'meta_key' => 'dot_inspection'
					),
					array( '%d', '%s' )
				);
				
				// Insert new meta
				$wpdb->insert(
					$table_meta,
					array(
						'post_id' => $vehicle_id,
						'meta_key' => 'dot_inspection',
						'meta_value' => $new_value
					),
					array( '%d', '%s', '%s' )
				);
				
				// Calculate total file count
				$total_file_count = count( $all_files );
				
				wp_send_json_success( array(
					'message' => 'Files uploaded successfully',
					'file_ids' => $uploaded_files,
					'total_count' => $total_file_count
				) );
			}
		}
		
		foreach ( $keys_names as $key_name ) {
			if ( ! empty( $_FILES[ $key_name ] ) && $_FILES[ $key_name ][ 'size' ] > 0 ) {
				$id_uploaded = $this->upload_one_file( $_FILES[ $key_name ], $key_name );
				
				if ( is_numeric( $id_uploaded ) ) {
					global $wpdb;
					$table_meta = $wpdb->prefix . $this->table_meta;
					
					// Delete existing meta
					$wpdb->delete(
						$table_meta,
						array(
							'post_id' => $vehicle_id,
							'meta_key' => $key_name
						),
						array( '%d', '%s' )
					);
					
					// Insert new meta
					$wpdb->insert(
						$table_meta,
						array(
							'post_id' => $vehicle_id,
							'meta_key' => $key_name,
							'meta_value' => $id_uploaded
						),
						array( '%d', '%s', '%s' )
					);
					
					wp_send_json_success( array(
						'message' => 'File uploaded successfully',
						'file_id' => $id_uploaded
					) );
				}
			}
		}
		
		wp_send_json_error( array( 'message' => 'No file uploaded' ) );
	}
	
	/**
	 * Upload one file
	 */
	private function upload_one_file( $file, $field_name = '' ) {
		if ( ! isset( $file ) || empty( $file[ 'size' ] ) ) {
			return false;
		}
		
		if ( $file[ 'error' ] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => 'File upload error: ' . $file[ 'error' ] ) );
		}
		
		$file_info = pathinfo( $file[ 'name' ] );
		$extension = isset( $file_info[ 'extension' ] ) ? strtolower( $file_info[ 'extension' ] ) : '';
		$allowed_types = $this->get_allowed_formats();
		
		if ( ! in_array( $extension, $allowed_types ) ) {
			$allowed_formats_str = implode( ', ', $allowed_types );
			wp_send_json_error( array( 'message' => 'Unsupported file format: ' . $file[ 'name' ] . '. Allowed formats: ' . $allowed_formats_str ) );
		}
		
		$max_size = 50 * 1024 * 1024;
		if ( $file[ 'size' ] > $max_size ) {
			wp_send_json_error( array( 'message' => 'File is too large (max 50MB): ' . $file[ 'name' ] ) );
		}
		
		$user_id = get_current_user_id();
		$timestamp = time();
		$unique = rand( 1000, 99999 );
		$new_filename = "{$user_id}_{$timestamp}_{$unique}_" . sanitize_file_name( $file_info[ 'filename' ] );
		
		if ( ! empty( $extension ) ) {
			$new_filename .= '.' . $extension;
		}
		
		$file[ 'name' ] = $new_filename;
		
		$upload_result = wp_handle_upload( $file, array( 'test_form' => false ) );
		
		if ( ! isset( $upload_result[ 'error' ] ) ) {
			$attachment = array(
				'post_mime_type' => $upload_result[ 'type' ],
				'post_title' => sanitize_file_name( basename( $upload_result[ 'file' ] ) ),
				'post_content' => '',
				'post_status' => 'inherit'
			);
			
			$attach_id = wp_insert_attachment( $attachment, $upload_result[ 'file' ] );
			
			if ( ! is_wp_error( $attach_id ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_result[ 'file' ] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				
				return $attach_id;
			}
		}
		
		return false;
	}
	
	/**
	 * Get allowed file formats
	 */
	private function get_allowed_formats() {
		return array(
			'jpg',
			'jpeg',
			'png',
			'gif',
			'txt',
			'pdf',
			'doc',
			'docx',
			'xls',
			'xml',
			'xlsx',
			'svg',
			'mp3',
		);
	}
}
