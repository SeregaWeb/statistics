<?php

class TMSTrailers {
	
	public $table_main = 'trailers';
	public $table_meta = 'trailers_meta';
	
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
			'add_trailer' => 'add_trailer',
			'update_trailer' => 'update_trailer',
			'get_trailer' => 'get_trailer',
			'delete_trailer' => 'delete_trailer',
			'optimize_trailers_tables' => 'optimize_trailers_tables',
			'remove_one_trailer' => 'remove_one_trailer',
			'upload_trailer_helper' => 'upload_trailer_helper',
		);
		
		foreach ( $actions as $ajax_action => $method ) {
			add_action( "wp_ajax_{$ajax_action}", [ $this, $method ] );
			add_action( "wp_ajax_nopriv_{$ajax_action}", [ $this->helper, 'need_login' ] );
		}
	}
	
	public function create_tables() {
		$this->table_trailer();
		$this->table_trailer_meta();
		$this->register_trailer_tables();
	}
	
	public function table_trailer() {
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
	
	public function table_trailer_meta() {
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
	
	public function register_trailer_tables() {
		// Additional tables can be registered here if needed
	}
	
	/**
	 * Get trailer by ID
	 *
	 * @param int $ID Trailer ID
	 * @return array|null
	 */
	public function get_trailer_by_id( $ID ) {
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
			$trailer = array(
				'main' => array(),
				'meta' => array()
			);
			
			foreach ( $results as $row ) {
				if ( empty( $trailer[ 'main' ] ) ) {
					$trailer[ 'main' ] = (array) $row;
					unset( $trailer[ 'main' ][ 'meta_key' ], $trailer[ 'main' ][ 'meta_value' ] );
				}
				
				if ( $row->meta_key && $row->meta_value ) {
					$trailer[ 'meta' ][ $row->meta_key ] = $row->meta_value;
				}
			}
			
			return $trailer;
		}
		
		return null;
	}

	/**
	 * Get multiple trailers by IDs in one query (avoids N+1).
	 *
	 * @param array $ids Array of trailer IDs (integers).
	 * @return array Keyed by ID: [ id => array( 'main' => ..., 'meta' => ... ), ... ]
	 */
	public function get_trailers_by_ids( array $ids ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$ids = array_unique( $ids );
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$query = $wpdb->prepare(
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
	 * Get all trailers with pagination
	 *
	 * @param array $args
	 * @return array
	 */
	public function get_trailers( $args = array() ) {
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
			$where .= $wpdb->prepare( " AND (trailer_number.meta_value LIKE %s OR license_plate.meta_value LIKE %s)", 
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}
		
		$sql = "
			SELECT DISTINCT main.id, main.date_created, main.date_updated, main.status_post
			FROM $table_main AS main
			LEFT JOIN $table_meta AS trailer_number ON main.id = trailer_number.post_id AND trailer_number.meta_key = 'trailer_number'
			LEFT JOIN $table_meta AS license_plate ON main.id = license_plate.post_id AND license_plate.meta_key = 'license_plate'
			$where
			ORDER BY main.date_created DESC
			LIMIT %d, %d
		";
		
		$trailers = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $per_page ), ARRAY_A );
		
		$total_sql = "
			SELECT COUNT(DISTINCT main.id)
			FROM $table_main AS main
			LEFT JOIN $table_meta AS trailer_number ON main.id = trailer_number.post_id AND trailer_number.meta_key = 'trailer_number'
			LEFT JOIN $table_meta AS license_plate ON main.id = license_plate.post_id AND license_plate.meta_key = 'license_plate'
			$where
		";
		
		$total = $wpdb->get_var( $total_sql );

		$ids    = wp_list_pluck( $trailers, 'id' );
		$by_id  = $this->get_trailers_by_ids( $ids );
		$result = array();
		foreach ( $trailers as $trailer ) {
			$id = isset( $trailer['id'] ) ? (int) $trailer['id'] : 0;
			if ( $id && isset( $by_id[ $id ] ) ) {
				$result[] = $by_id[ $id ];
			}
		}

		return array(
			'trailers' => $result,
			'total' => intval( $total ),
			'per_page' => $per_page,
			'current_page' => $current_page,
			'total_pages' => ceil( $total / $per_page )
		);
	}
	
	/**
	 * Add new trailer
	 */
	public function add_trailer() {
		if ( ! check_ajax_referer( 'trailer_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		global $wpdb;
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'User not logged in' ) );
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
		
		$trailer_id = $wpdb->insert_id;
		
		if ( ! $trailer_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create trailer' ) );
		}
		
		// Save meta data
		$this->save_trailer_meta( $trailer_id, $_POST );
		
		wp_send_json_success( array(
			'message' => 'Trailer added successfully',
			'trailer_id' => $trailer_id
		) );
	}
	
	/**
	 * Update trailer
	 */
	public function update_trailer() {
		if ( ! check_ajax_referer( 'trailer_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		global $wpdb;
		
		$trailer_id = isset( $_POST[ 'trailer_id' ] ) ? intval( $_POST[ 'trailer_id' ] ) : 0;
		
		if ( ! $trailer_id ) {
			wp_send_json_error( array( 'message' => 'Trailer ID is required' ) );
		}
		
		$user_id = get_current_user_id();
		$table_main = $wpdb->prefix . $this->table_main;
		
		// Update main record
		$wpdb->update(
			$table_main,
			array(
				'user_id_updated' => $user_id
			),
			array( 'id' => $trailer_id ),
			array( '%d' ),
			array( '%d' )
		);
		
		// Save meta data
		$this->save_trailer_meta( $trailer_id, $_POST );
		
		wp_send_json_success( array(
			'message' => 'Trailer updated successfully',
			'trailer_id' => $trailer_id
		) );
	}
	
	/**
	 * Save trailer meta data
	 *
	 * @param int $trailer_id
	 * @param array $data
	 */
	private function save_trailer_meta( $trailer_id, $data ) {
		global $wpdb;
		
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		$meta_fields = array(
			'trailer_type',
			'trailer_number',
			'license_plate',
			'license_plate_file',
			'license_state',
			'vin',
			'make',
			'year',
			'cargo_maximum',
			'length',
			'width',
			'door_height',
			'total_height',
			'length_main_well',
			'length_rear_deck',
			'height',
			'length_total',
			'length_lower_deck',
			'length_upper_deck',
			'height_lower_deck',
			'height_upper_deck',
			'trailer_registration',
			'lease',
			'lease_agreement',
			'air_ride'
		);
		
		foreach ( $meta_fields as $field ) {
			$value = isset( $data[ $field ] ) ? $data[ $field ] : '';
			
			// Handle checkbox (lease)
			if ( $field === 'lease' || $field === 'air_ride' ) {
				$value = isset( $data[ $field ] ) && $data[ $field ] ? 'on' : '';
			}
			
			// Handle file uploads
			if ( in_array( $field, array( 'license_plate_file', 'trailer_registration', 'lease_agreement' ) ) ) {
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
			
			// Delete existing meta
			$wpdb->delete(
				$table_meta,
				array(
					'post_id' => $trailer_id,
					'meta_key' => $field
				),
				array( '%d', '%s' )
			);
			
			// Insert new meta if value is not empty (or is 0 for numeric fields)
			// For numeric dimension fields, allow 0 and empty string to clear old values
			$dimension_fields = array( 'length', 'width', 'door_height', 'total_height', 'length_main_well', 
				'length_rear_deck', 'height', 'length_total', 'length_lower_deck', 'length_upper_deck', 
				'height_lower_deck', 'height_upper_deck', 'cargo_maximum' );
			
			if ( in_array( $field, $dimension_fields ) ) {
				// For dimension fields, save even if empty to clear old values
				$value = trim( $value );
				$wpdb->insert(
					$table_meta,
					array(
						'post_id' => $trailer_id,
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
						'post_id' => $trailer_id,
						'meta_key' => $field,
						'meta_value' => $value
					),
					array( '%d', '%s', '%s' )
				);
			}
		}
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
	 * Get trailer (AJAX)
	 */
	public function get_trailer() {
		if ( ! check_ajax_referer( 'trailer_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		$trailer_id = isset( $_POST[ 'trailer_id' ] ) ? intval( $_POST[ 'trailer_id' ] ) : 0;
		
		if ( ! $trailer_id ) {
			wp_send_json_error( array( 'message' => 'Trailer ID is required' ) );
		}
		
		$trailer = $this->get_trailer_by_id( $trailer_id );
		
		if ( ! $trailer ) {
			wp_send_json_error( array( 'message' => 'Trailer not found' ) );
		}
		
		wp_send_json_success( $trailer );
	}
	
	/**
	 * Delete trailer (Admin only)
	 */
	public function delete_trailer() {
		if ( ! check_ajax_referer( 'trailer_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		// Check if user is administrator
		$current_user = wp_get_current_user();
		if ( ! in_array( 'administrator', $current_user->roles ) ) {
			wp_send_json_error( array( 'message' => 'Only administrators can delete trailers' ) );
		}
		
		global $wpdb;
		
		$trailer_id = isset( $_POST[ 'trailer_id' ] ) ? intval( $_POST[ 'trailer_id' ] ) : 0;
		
		if ( ! $trailer_id ) {
			wp_send_json_error( array( 'message' => 'Trailer ID is required' ) );
		}
		
		$table_main = $wpdb->prefix . $this->table_main;
		$table_meta = $wpdb->prefix . $this->table_meta;
		
		// Delete meta
		$wpdb->delete( $table_meta, array( 'post_id' => $trailer_id ), array( '%d' ) );
		
		// Delete main record
		$wpdb->delete( $table_main, array( 'id' => $trailer_id ), array( '%d' ) );
		
		wp_send_json_success( array( 'message' => 'Trailer deleted successfully' ) );
	}
	
	/**
	 * Perform fast optimization (indexes only)
	 */
	public function perform_fast_trailers_optimization() {
		global $wpdb;
		$results = array();
		
		$main_table = $wpdb->prefix . $this->table_main;
		$results[ 'main_table' ] = $this->optimize_trailers_main_table_fast( $main_table );
		
		$meta_table = $wpdb->prefix . $this->table_meta;
		$results[ 'meta_table' ] = $this->optimize_trailers_meta_table_fast( $meta_table );
		
		return $results;
	}
	
	/**
	 * Perform full optimization (structural changes)
	 */
	public function perform_full_trailers_optimization() {
		global $wpdb;
		$results = array();
		
		$main_table = $wpdb->prefix . $this->table_main;
		$results[ 'main_table' ] = $this->optimize_trailers_main_table_full( $main_table );
		
		$meta_table = $wpdb->prefix . $this->table_meta;
		$results[ 'meta_table' ] = $this->optimize_trailers_meta_table_full( $meta_table );
		
		return $results;
	}
	
	/**
	 * Fast optimization for main trailers table
	 */
	private function optimize_trailers_main_table_fast( $table_name ) {
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
	 * Full optimization for main trailers table
	 */
	private function optimize_trailers_main_table_full( $table_name ) {
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
	 * Fast optimization for trailers meta table
	 */
	private function optimize_trailers_meta_table_fast( $table_name ) {
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
	 * Full optimization for trailers meta table
	 */
	private function optimize_trailers_meta_table_full( $table_name ) {
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
	 * Optimize trailers tables (AJAX)
	 */
	public function optimize_trailers_tables() {
		if ( ! check_ajax_referer( 'trailer_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}
		
		$optimization_type = isset( $_POST[ 'optimization_type' ] ) ? sanitize_text_field( $_POST[ 'optimization_type' ] ) : 'fast';
		
		if ( $optimization_type === 'full' ) {
			$results = $this->perform_full_trailers_optimization();
		} else {
			$results = $this->perform_fast_trailers_optimization();
		}
		
		wp_send_json_success( array(
			'message' => 'Optimization completed',
			'results' => $results
		) );
	}
	
	/**
	 * Remove one trailer file (AJAX)
	 */
	public function remove_one_trailer() {
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
		} else {
			return new WP_Error( 'no_value_found', 'No value found for the specified field.' );
		}
	}
	
	/**
	 * Upload trailer helper file (AJAX)
	 */
	public function upload_trailer_helper() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ) );
		}
		
		$trailer_id = isset( $_POST[ 'trailer_id' ] ) ? intval( $_POST[ 'trailer_id' ] ) : 0;
		
		if ( ! $trailer_id ) {
			wp_send_json_error( array( 'message' => 'Please create the trailer first, then you can upload files.' ) );
		}
		
		$keys_names = array(
			'license_plate_file',
			'trailer_registration',
			'lease_agreement',
		);
		
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
							'post_id' => $trailer_id,
							'meta_key' => $key_name
						),
						array( '%d', '%s' )
					);
					
					// Insert new meta
					$wpdb->insert(
						$table_meta,
						array(
							'post_id' => $trailer_id,
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

