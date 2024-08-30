<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsShipper extends TMSReportsHelper {
	
	public $table_main = 'reports_shipper';
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_shipper', array( $this, 'add_new_shipper' ) );
	}
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table_shipper' ) );
		
		$this->ajax_actions();
	}
	
	public function add_new_shipper() {
		// Check if it's an AJAX request (simple defense)
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Sanitize input data
			$MY_INPUT = filter_var_array( $_POST, [
				"shipper_name" => FILTER_SANITIZE_STRING,
				"country"      => FILTER_SANITIZE_STRING,
				"Addr1"        => FILTER_SANITIZE_STRING,
				"Addr2"        => FILTER_SANITIZE_STRING,
				"City"         => FILTER_SANITIZE_STRING,
				"State"        => FILTER_SANITIZE_STRING,
				"ZipCode"      => FILTER_SANITIZE_STRING,
				"FirstName"    => FILTER_SANITIZE_STRING,
				"LastName"     => FILTER_SANITIZE_STRING,
				"Phone"        => FILTER_SANITIZE_STRING,
				"Email"        => FILTER_SANITIZE_EMAIL,
				"status_post"  => FILTER_SANITIZE_STRING,
			] );
			
			$MY_INPUT[ "status_post" ] = 'publish';
			
			// Insert the company report
			$result = $this->add_shipper( $MY_INPUT );
			
			if ( is_numeric( $result ) ) {
				wp_send_json_success( [ 'message' => 'shipper successfully added', 'data' => $MY_INPUT ] );
			} else {
				// Handle specific errors
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message() ] );
				} else {
					wp_send_json_error( [ 'message' => 'shipper not created, error adding to database' ] );
				}
			}
		} else {
			wp_send_json_error( [ 'message' => 'Invalid request' ] );
		}
	}
	
	public function add_shipper( $data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		$user_id    = get_current_user_id();
		
		$insert_params = array(
			'shipper_name'       => $data[ 'shipper_name' ],
			'country'            => $data[ 'country' ],
			'address1'           => $data[ 'Addr1' ],
			'address2'           => $data[ 'Addr2' ],
			'city'               => $data[ 'City' ],
			'state'              => $data[ 'State' ],
			'zip_code'           => $data[ 'ZipCode' ],
			'contact_first_name' => $data[ 'FirstName' ],
			'contact_last_name'  => $data[ 'LastName' ],
			'phone_number'       => $data[ 'Phone' ],
			'email'              => $data[ 'Email' ],
			'user_id_added'      => $user_id,
			'date_created'       => current_time( 'mysql' ),
			'user_id_updated'    => $user_id,
			'date_updated'       => current_time( 'mysql' ),
			'status_post'        => $data[ 'status_post' ],
		);
		
		$result = $wpdb->insert( $table_name, $insert_params, array(
				'%s',  // company_name
				'%s',  // country
				'%s',  // address1
				'%s',  // address2
				'%s',  // city
				'%s',  // state
				'%s',  // zip_code
				'%s',  // contact_first_name
				'%s',  // contact_last_name
				'%s',  // phone_number
				'%s',  // email
				'%d',  // user_id_added
				'%s',  // date_created
				'%d',  // user_id_updated
				'%s',  // date_updated
				'%s',  // status_post
			) );
		
		// Check if the insert was successful
		if ( $result ) {
			return $wpdb->insert_id; // Return the ID of the added record
		} else {
			// Get the last SQL error
			$error = $wpdb->last_error;
			
			// Check for specific unique constraint violations
			if ( strpos( $error, 'Duplicate entry' ) !== false ) {
				if ( strpos( $error, 'shipper_name' ) !== false ) {
					return new WP_Error( 'db_error', 'A shipper with this name already exists.' );
				}
			}
			
			// Return a generic database error if no specific match is found
			return new WP_Error( 'db_error', 'Error adding the shipper report to the database: ' . $error );
		}
	}
	
	public function create_table_shipper() {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . $this->table_main;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        shipper_name varchar(255) NOT NULL,
        country varchar(255) NOT NULL,
        address1 varchar(255) NOT NULL,
        address2 varchar(255),
        city varchar(100) NOT NULL,
        state varchar(100) NOT NULL,
        zip_code varchar(20) NOT NULL,
        contact_first_name varchar(100) NOT NULL,
        contact_last_name varchar(100),
        phone_number varchar(20),
        email varchar(255),
        mc_number varchar(50),
        dot_number varchar(50),
        user_id_added mediumint(9) NOT NULL,
        date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        user_id_updated mediumint(9) NULL,
        date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status_post varchar(255) NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY shipper_name (shipper_name)
    ) $charset_collate;";
		
		dbDelta( $sql );
	}
	
}