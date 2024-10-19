<?php
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TMSReportsShipper extends TMSReportsHelper {
	
	public $table_main = 'reports_shipper';
	
	public function ajax_actions() {
		add_action( 'wp_ajax_add_new_shipper', array( $this, 'add_new_shipper' ) );
		add_action( 'wp_ajax_search_shipper', array( $this, 'search_shipper' ) );
		
	}
	
	public function init() {
		add_action( 'after_setup_theme', array( $this, 'create_table_shipper' ) );
		
//		add_action( 'after_setup_theme', array( $this, 'update_table_shipper_with_indexes' ) );
		
		$this->ajax_actions();
	}
	
	public function update_table_shipper_with_indexes() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_main;
		
		// Добавляем индексы для полей, по которым выполняются поиски
		$wpdb->query("
        ALTER TABLE $table_name
        ADD INDEX idx_full_address (full_address),
        ADD INDEX idx_shipper_name (shipper_name),
        ADD INDEX idx_email (email),
        ADD INDEX idx_phone_number (phone_number);
    ");
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
			] );
			
			$st = !empty($MY_INPUT['Addr1']) ? $MY_INPUT['Addr1'] . ', ' : '';
			$city = !empty($MY_INPUT['City']) ? $MY_INPUT['City'] . ', ' : '';
			$state = !empty($MY_INPUT['State']) ? $MY_INPUT['State'] . ' ' : '';
			$zip = !empty($MY_INPUT['ZipCode']) ? $MY_INPUT['ZipCode'] : ' ';
			$country = $MY_INPUT['country'] !== 'USA' ? ' '.$MY_INPUT['country'] : '';
			
			$MY_INPUT[ "full_address" ] = $st . $city . $state . $zip . $country;
			
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
			'full_address'       => $data[ 'full_address' ],
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
				'%s',  // full_address
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
        full_address varchar(255),
        user_id_added mediumint(9) NOT NULL,
        date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        user_id_updated mediumint(9) NULL,
        date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY shipper_name (shipper_name),
        INDEX idx_full_address (full_address),
        INDEX idx_shipper_name (shipper_name),
        INDEX idx_email (email),
        INDEX idx_phone_number (phone_number)
    ) $charset_collate;";
		
		dbDelta( $sql );
	}
	
	
	public function search_shipper() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$MY_INPUT = filter_var_array( $_POST, [
				"search" => FILTER_SANITIZE_STRING
			] );
			
			$tmp = '';
			
			if ( empty( $MY_INPUT[ 'search' ] ) ) {
				wp_send_json_success( array( 'template' => $tmp ) );
			}
			
			$response = $this->search_shipper_in_db( $MY_INPUT[ 'search' ] );
			if ( is_array( $response ) ) {
				if ( sizeof( $response ) === 0 ) {
					
					$tmp = '
						<li class="my-dropdown-search__item">
	                        <p class="my-dropdown-search__not-found">
		                        Not found
		                        <a class="js-open-popup-activator link-primary"
		                           href="#popup_add_shipper">
		                           click here
		                        </a> to add a new shipper.
	                        </p>
	                    </li>
					';
					
					wp_send_json_success( array( 'template' => $tmp ) );
				}
				
				foreach ( $response as $value ) {
					$address = $value->full_address;
					$short_address = $value->city . ' ' . $value->state;
					
					$tmp .= '
						<li class="my-dropdown-search__item">
	                        <a class="my-dropdown-search__link js-link-search-result" href="#">
	                            <span class="my-dropdown-search__name">
	                                <span>' . $address . '</span>
	                            </span>
	                            <div class="d-none">
		                            <div class="js-content-company my-dropdown-search__hidden">
		                                ' . $this->print_list_shipper( $address, $value->id , $short_address ) . '
									</div>
								</div>
	                        </a>
	                    </li>
					';
				}
				
				wp_send_json_success( array( 'template' => $tmp) );
			}
		}
	}
	
	public function print_list_shipper( $address = '', $id, $short_address ) {
		
		if ( ! $id ) {
			return false;
		}
		
		$template = '
		<ul class="result-search-el">
			<li><h4>Selected shipper</h4></li>
            <li class="address">' . $address . '</li>
		</ul>
		<input type="hidden" class="js-full-address" data-short-address="'.$short_address.'" data-current-address="'. $address .'" name="shipper_id" value="' . $id . '">';
		
		return $template;
	}
	
	public function get_shipper_by_id( $ID ) {
		global $wpdb;
		$query = $wpdb->prepare( "
        SELECT * FROM {$wpdb->prefix}{$this->table_main}
        WHERE id = %d
    	", $ID );
		
		// Execute the query
		$results = $wpdb->get_results( $query );
		
		return $results;
	}
	
	public function search_shipper_in_db( $search_term ) {
		global $wpdb;
		
		// Ensure the search term is sanitized
		$search_term = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		// Define the query to search in the specified fields, ignoring case sensitivity
		$query = $wpdb->prepare( "
        SELECT * FROM {$wpdb->prefix}{$this->table_main}
        WHERE LOWER(full_address) LIKE LOWER(%s) LIMIT 5
    ", $search_term, $search_term, $search_term );
		
		// Execute the query
		$results = $wpdb->get_results( $query );
		
		return $results;
	}
	
}