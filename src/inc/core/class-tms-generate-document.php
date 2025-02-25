<?php

class TMSGenerateDocument extends TMSReports {
	/**
	 * not to overwrite each other's documents or create unique documents
	 * @var int|string|null
	 */
	private $prefix_file = null;
	
	/**
	 * originally written for wordpress, for use outside of wordpress
	 * you need to add 3 functions, described below
	 * @var bool|mixed
	 */
	private $use_wordpress = true;
	
	private $logo                   = 'http://www.odysseia-tms.kiev.ua/wp-content/uploads/2023/11/photo-2023-11-05-100451.jpeg';
	private $company_name           = 'ODYSSEIA INC';
	private $company_phone          = '(800) 922-0760';
	private $company_email          = 'accounting@odysseia.one';
	private $company_address        = '521 S port Street';
	private $company_sity_state_zip = 'Baltimore, MD, 21224';
	private $company_mc             = '1287234';
	private $company_dot            = '3690406';
	
	public function __construct( $use_wordpress = true ) {
		
		if ( is_user_logged_in() ):
			global $global_options;
			$user_id = get_current_user_id();
			$project = get_field( 'current_select', 'user_' . $user_id );
			$project = strtolower( $project );
			
			
			$upload_dir = wp_get_upload_dir();
			$upload_url = $upload_dir[ 'baseurl' ];
			
			if ( $project === 'odysseia' ) {
				$extend_file = '.jpeg';
			}
			
			if ( $project === 'endurance' || $project === 'martlet' ) {
				$extend_file = '.jpg';
			}
			
			$upload_url .= '/logos/' . $project . $extend_file;
			
			$this->logo                   = $upload_url;
			$this->company_name           = get_field_value( $global_options, 'company_name_' . $project );
			$this->company_phone          = get_field_value( $global_options, 'company_phone_' . $project );
			$this->company_email          = get_field_value( $global_options, 'company_email_' . $project );
			$this->company_address        = get_field_value( $global_options, 'company_address_' . $project );
			$this->company_sity_state_zip = get_field_value( $global_options, 'company_sity_state_zip_' . $project );
			$this->company_mc             = get_field_value( $global_options, 'company_mc_' . $project );
			$this->company_dot            = get_field_value( $global_options, 'company_dot_' . $project );
			
			$this->use_wordpress = $use_wordpress;
			
			if ( $this->use_wordpress ) {
				if ( is_user_logged_in() ) {
					$this->prefix_file = get_current_user_id() . '_';
				}
			} else {
				$this->prefix_file = $this->custom_get_user_id();
			}
			
			$this->create_dir();
		endif;
	}
	
	public function init() {
		$this->init_ajax();
	}
	
	function init_ajax() {
		add_action( 'wp_ajax_generate_invoice', array( $this, 'generate_invoice' ) );
	}
	
	function generate_invoice() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$url = $this->create_file( 'invoice' );
			if ( $url ) {
				wp_send_json_success( $url );
			}
			wp_send_json_error( 'Error create!' );
		}
	}
	
	/**
	 * if dont use wordpress
	 * function get current user id
	 * @return int
	 */
	private function custom_get_user_id() {
		return 1;
	}
	
	/**
	 * if dont use wordpress
	 * function get path pdf dir
	 * @return string
	 */
	private function custom_get_path() {
		return '/';
	}
	
	/**
	 * if dont use wordpress
	 * function get url pdf dir
	 * @return string
	 */
	private function custom_get_url() {
		return '/';
	}
	
	/**
	 * create directory pdf files if dir not exists
	 * @return void
	 */
	private function create_dir() {
		$dir = $this->get_path();
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
	}
	
	/**
	 * create pdf document
	 * @return false|string
	 * @throws \Mpdf\MpdfException
	 */
	public function create_file( $template = 'bol' ) {
		
		if ( is_null( $this->prefix_file ) ) {
			return false;
		}
		
		if ( $template === 'invoice' ) {
			$html = $this->get_template_invoice();
		} else if ( $template === 'settlement' ) {
			$html = $this->get_template_settlement_summary();
		} else {
			$html = $this->get_template();
		}
		
		try {
			$mpdf = new \Mpdf\Mpdf();
			$mpdf->AddPage( '', '', '', '', '', 5, 5, 5, 5, 5, 5 );
			$mpdf->WriteHTML( $html );
			$file_path     = $this->get_path() . $this->prefix_file . 'file.pdf';
			$file_path_url = $this->get_url() . $this->prefix_file . 'file.pdf';
			$mpdf->Output( $file_path, "F" );
			
			return $file_path_url;
		}
		catch ( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * get path Wordpress project \ Custom project
	 * @return string
	 */
	public function get_path() {
		if ( $this->use_wordpress ) {
			return get_theme_file_path() . '/pdf-files/';
		}
		
		return $this->custom_get_path();
	}
	
	/**
	 * get url Wordpress project \ Custom project
	 * @return string
	 */
	public function get_url() {
		if ( $this->use_wordpress ) {
			return get_theme_file_uri() . '/pdf-files/';
		}
		
		return $this->custom_get_url();
	}
	
	/**
	 * function check isset exist module in server
	 * @return void
	 */
	public function _is_mpdf_exists( $print = true ) {
		if ( $print ) {
			echo '<p>';
			if ( class_exists( 'Mpdf' ) || class_exists( '\Mpdf\Mpdf' ) || class_exists( '\mPDF' ) || class_exists( 'mPDF' ) ) {
				echo "Mpdf is <span style=\"color:#4fa361;\">exists</span> on this server";
			} else {
				echo "Mpdf is <span style=\"color:#dc4f49\">not exists</span> on this server";
			}
			echo '</p>';
		} else {
			return ( class_exists( 'Mpdf' ) || class_exists( '\Mpdf\Mpdf' ) || class_exists( '\mPDF' ) || class_exists( 'mPDF' ) );
		}
	}
	
	public function get_template_settlement_summary( $input = false ) {
		
		$data                 = filter_input( INPUT_POST, 'date', FILTER_SANITIZE_STRING );
		$str_date             = strtotime( $data );
		$corect_formate_date  = date( 'm/d/Y', $str_date );
		$data1                = filter_input( INPUT_POST, 'date1', FILTER_SANITIZE_STRING );
		$str_date2            = strtotime( $data1 );
		$corect_formate_date1 = date( 'm/d/Y', $str_date2 );
		$data2                = filter_input( INPUT_POST, 'date2', FILTER_SANITIZE_STRING );
		$str_date3            = strtotime( $data2 );
		$corect_formate_date2 = date( 'm/d/Y', $str_date3 );
		$title                = filter_input( INPUT_POST, 'title', FILTER_SANITIZE_STRING );
		$name                 = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING );
		$description          = filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING );
		$for                  = filter_input( INPUT_POST, 'for', FILTER_SANITIZE_STRING );
		$for2                 = filter_input( INPUT_POST, 'for2', FILTER_SANITIZE_STRING );
		$check_number         = filter_input( INPUT_POST, 'check_number', FILTER_SANITIZE_STRING );
		$settlement           = filter_input( INPUT_POST, 'settlement', FILTER_SANITIZE_STRING );
		for ( $i = 0; $i < 15; $i ++ ) {
			${"item" . $i}              = filter_input( INPUT_POST, 'item' . $i, FILTER_SANITIZE_STRING );
			${"item_country" . $i}      = filter_input( INPUT_POST, 'item_country' . $i, FILTER_SANITIZE_STRING );
			${"item_from" . $i}         = filter_input( INPUT_POST, 'item_from' . $i, FILTER_SANITIZE_STRING );
			${"item_from_country" . $i} = filter_input( INPUT_POST, 'item_from_country' . $i, FILTER_SANITIZE_STRING );
			${"item_loaded" . $i}       = filter_input( INPUT_POST, 'item_loaded' . $i, FILTER_SANITIZE_STRING );
			${"item_miles" . $i}        = filter_input( INPUT_POST, 'item_miles' . $i, FILTER_SANITIZE_STRING );
			${"item_numbers" . $i}      = filter_input( INPUT_POST, 'item_numbers' . $i, FILTER_SANITIZE_STRING );
			${"item_date" . $i}         = filter_input( INPUT_POST, 'item_date' . $i, FILTER_SANITIZE_STRING );
			$str_date                   = strtotime( ${"item_date" . $i} );
			${"item_date" . $i}         = date( 'm/d/Y', $str_date );
			${"item_sum" . $i}          = filter_input( INPUT_POST, 'item_sum' . $i, FILTER_SANITIZE_STRING );
		}
		
		$total             = filter_input( INPUT_POST, 'total', FILTER_SANITIZE_STRING );
		$total2            = filter_input( INPUT_POST, 'total2', FILTER_SANITIZE_STRING );
		$total3            = filter_input( INPUT_POST, 'total3', FILTER_SANITIZE_STRING );
		$total4            = filter_input( INPUT_POST, 'total4', FILTER_SANITIZE_STRING );
		$total5            = filter_input( INPUT_POST, 'total5', FILTER_SANITIZE_STRING );
		$total6            = filter_input( INPUT_POST, 'total6', FILTER_SANITIZE_STRING );
		$total7            = filter_input( INPUT_POST, 'total7', FILTER_SANITIZE_STRING );
		$checking          = filter_input( INPUT_POST, 'checking', FILTER_SANITIZE_STRING );
		$checking_number   = filter_input( INPUT_POST, 'checking_number', FILTER_SANITIZE_STRING );
		$acct_proc         = filter_input( INPUT_POST, 'acct_proc', FILTER_SANITIZE_STRING );
		$acct_num          = filter_input( INPUT_POST, 'acct_num', FILTER_SANITIZE_STRING );
		$orders            = filter_input( INPUT_POST, 'orders', FILTER_SANITIZE_STRING );
		$miles_count_1     = filter_input( INPUT_POST, 'miles_count_1', FILTER_SANITIZE_STRING );
		$miles_count_2     = filter_input( INPUT_POST, 'miles_count_2', FILTER_SANITIZE_STRING );
		$miles_count_empty = filter_input( INPUT_POST, 'miles_count_empty', FILTER_SANITIZE_STRING );
		$text1             = filter_input( INPUT_POST, 'text1', FILTER_SANITIZE_STRING );
		$text2             = filter_input( INPUT_POST, 'text2', FILTER_SANITIZE_STRING );
		$text3             = filter_input( INPUT_POST, 'text3', FILTER_SANITIZE_STRING );
		$text4             = filter_input( INPUT_POST, 'text4', FILTER_SANITIZE_STRING );
		
		ob_start();
		?>
        <table style="font-family: sans-serif; border-collapse: collapse;
    border-spacing: 0; margin-bottom: 10px;" border="0" width="100%">
            <tr style="width: 100%;">
                <td width="150px">
                    <p style="margin: 0;">
						<?php if ( $input ) { ?>
                            <input type="date" value="<?php echo date( 'Y-m-d' ); ?>" name="date">
						<?php } else {
							echo $corect_formate_date;
						} ?>
                    </p>
                </td>
                <td width="100%" style="text-align: center">
                    <p style="font-size: 22px;
						width: 100%;
						display: block;
					    color: #000000;
					    margin: 15px 0 15px;
					    text-align: center;
					    font-family: sans-serif, areal;
					    font-weight: 600;">
						<?php if ( $input ) { ?>
                            <input type="text" style="text-align: center" value="<?php echo 'Settlement Summary'; ?>"
                                   name="title">
						<?php } else { ?>
                            <strong style="font-size: 22px;">
								<?php echo $title; ?>
                            </strong>
						<?php } ?>

                    </p>
                </td>
                <td width="150px">
                    <img height="120px" width="150px" style="height: 90px;"
                         src="<?php echo $this->logo; ?>"
                         alt="logo">
                </td>
            </tr>
        </table>
        <table style="font-family: sans-serif; border-collapse: collapse;
    border-spacing: 0; margin-bottom: 10px;" border="0" width="100%">
            <tr>
                <td width="100%" style="text-align: center">
                    <p style="text-align: center;margin: 0;">
						<?php if ( $input ) { ?>
                            <input type="text" style="text-align: center" value="<?php echo $this->company_name; ?>"
                                   name="name">
						<?php } else {
							echo $name;
						} ?>
                    </p>
                </td>
            </tr>
            <tr>
                <td width="100%" style="text-align: center">
                    <p style="text-align: center;margin: 0;">
						<?php if ( $input ) { ?>
                            <input type="text" style="text-align: center"
                                   value="<?php echo $this->company_address . ', ' . $this->company_sity_state_zip . ', phone: ' . $this->company_phone; ?>"
                                   name="description">
						<?php } else {
							echo $description;
						} ?>
                    </p>
                </td>
            </tr>
        </table>

        <table style="font-family: sans-serif; border-collapse: collapse;
    border-spacing: 0; font-size: 14px; margin-bottom: 10px;" border="0" width="100%">
            <tr>
                <td width="33.33%">
                    <p style="text-align: left;margin: 0; font-size: 14px; font-weight: bold;">For: <strong
                                style="margin-right: 20px;">
							<?php if ( $input ) { ?>
                                <input style="display: inline-block; width: 120px;" type="text" name="for">
							<?php } else {
								echo $for;
							} ?>
                        </strong>
                        <strong>
							
							<?php if ( $input ) { ?>
                                <input style="display: inline-block; width: 120px;" type="text" name="for2">
							<?php } else {
								echo $for2;
							} ?>
                        </strong>
                    </p>
                </td>
                <td width="33.33%">
                    <p style="text-align: center;margin: 0; font-size: 14px; font-weight: bold;">Period ending:
						<?php if ( $input ) { ?>
                            <input type="date" value="<?php echo date( 'Y-m-d' ); ?>" name="date1">
						<?php } else {
							echo $corect_formate_date1;
						} ?>
                    </p>
                </td>
                <td width="33.33%">
                    <p style="text-align:right; font-size: 14px;margin: 0; font-weight: bold;">Check #: <strong
                                style="width: 120px;display: inline-block;text-align: left;">
							<?php if ( $input ) { ?>
                                <input type="text" name="check_number">
							<?php } else {
								echo $check_number;
							} ?>
                        </strong></p>
                    <p style="text-align: right; font-size: 14px; margin: 0; font-weight: bold;">Check date: <strong
                                style="width: 120px;display: inline-block;text-align: left;">
							<?php if ( $input ) { ?>
                                <input type="date" value="<?php echo date( 'Y-m-d' ); ?>" name="date2">
							<?php } else {
								echo $corect_formate_date2;
							} ?>
                        </strong>
                    </p>
                </td>
            </tr>
        </table>

        <table
                style="font-family: sans-serif; border-collapse: collapse; width: 100%; border-top: 1px solid #000000; border-bottom: 1px solid #000000;">
            <tr>
                <td width="22%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"><strong>Origin</strong>
                </td>
                <td width="22%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
                    <strong>Destination</strong></td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"><strong>Loaded</strong>
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"><strong>Miles</strong>
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
                    <strong>Invoice</strong></td>
                <td width="16%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"><strong>Date
                        Received</strong></td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    <strong>Net
                        Pay</strong></td>
            </tr>
        </table>

        <table
                style="font-family: sans-serif; border-collapse: collapse; width: 100%; border-top: 1px solid #000000; margin-top: 10px;">
            <tr>
                <td width="22%" colspan="2" style="padding-top: 10px;padding-bottom:10px;">
                    <strong>SETTLEMENT </strong><strong>
						<?php if ( $input ) { ?>
                            <input type="text" name="settlement">
						<?php } else {
							echo $settlement;
						} ?>
                    </strong></td>
            </tr>
			<?php
			for ( $i = 0; $i < 15; $i ++ ) {
				if ( ! empty( ${"item_sum" . strval( $i )} ) || $input ) { ?>
                    <tr>
                        <td width="22%" style="  font-size: 14px; padding-top: 10px;padding-bottom:10px;">
							<?php if ( $input ) { ?>
                                <input style="width:80px;" type="text" name="item<?php echo $i; ?>">
							<?php } else {
								echo ${"item" . strval( $i )};
							} ?> -
                            <span>
							<?php if ( $input ) { ?>
                                <input style="width:50px;" type="text" name="item_country<?php echo $i; ?>">
							<?php } else {
								echo ${"item_country" . strval( $i )};
							} ?>
						</span></td>
                        <td width="22%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
							<?php if ( $input ) { ?>
                                <input style="width:80px;" type="text" name="item_from<?php echo $i; ?>">
							<?php } else {
								echo ${"item_from" . strval( $i )};
							} ?> -
                            <span>
							<?php if ( $input ) { ?>
                                <input style="width:50px;" type="text" name="item_from_country<?php echo $i; ?>">
							<?php } else {
								echo ${"item_from_country" . strval( $i )};
							} ?>
						</span></td>
                        <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
							<?php if ( $input ) { ?>
                                <input type="text" style="width:80px;" value="Loaded"
                                       name="item_loaded<?php echo $i; ?>">
							<?php } else {
								echo ${"item_loaded" . strval( $i )};
							} ?>
                        </td>
                        <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
							<?php if ( $input ) { ?>
                                <input type="number" class="js-miles-all" step="0.01" style="width:80px;"
                                       name="item_miles<?php echo $i; ?>">
							<?php } else {
								echo ${"item_miles" . strval( $i )};
							} ?>
                        </td>
                        <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
							<?php if ( $input ) { ?>
                                <input type="number" style="width: 80px;" name="item_numbers<?php echo $i; ?>">
							<?php } else {
								echo ${"item_numbers" . strval( $i )};
							} ?>
                        </td>
                        <td width="16%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;">
							<?php if ( $input ) { ?>
                                <input type="date" style="width: 150px;" value="<?php echo date( 'Y-m-d' ); ?>"
                                       name="item_date<?php echo $i; ?>">
							<?php } else {
								echo ${"item_date" . strval( $i )};
							} ?>
                        </td>
                        <td width="10%"
                            style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right; border-bottom: 1px solid #000000">
							<?php if ( $input ) { ?>
                                <input type="currency" class="custom4-multival" step="0.01" style="width: 80px;"
                                       name="item_sum<?php echo $i; ?>">
							<?php } else {
								echo ${"item_sum" . strval( $i )};
							} ?>
                        </td>
                    </tr>
				<?php }
			} ?>
            <tr>
                <td width="22%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="22%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="16%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;text-align: right;">
                    <strong>ORDER TOTAL</strong></td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="currency" class="js-value" style="width: 80px;" name="total">
					<?php } else {
						echo $total;
					} ?>
                </td>
            </tr>
        </table>

        <table
                style=" font-size: 14px; font-family: sans-serif; border-collapse: collapse; width: 100%; border-top: 1px solid #000000; margin-top: 10px;">
            <tr>
                <td width="50%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"><strong>PAY
                        SUMMARY</strong></td>
                <td width="40%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    ORDER PAY:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="currency" class="js-value" style="width: 80px;" name="total2">
					<?php } else {
						echo $total2;
					} ?>
                </td>
            </tr>
            <tr>
                <td width="50%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="40%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    TOTAL GROSS
                    EARNINGS:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="currency" class="js-value" style="width: 80px;" name="total3">
					<?php } else {
						echo $total3;
					} ?>
                </td>
            </tr>
            <tr>
                <td width="50%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px;"></td>
                <td width="40%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">NET
                    PAY:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="currency" class="js-value" style="width: 80px;" name="total4">
					<?php } else {
						echo $total4;
					} ?>
                </td>
            </tr>
        </table>

        <table
                style=" font-size: 14px; margin-top: 20px; font-family: sans-serif; border-collapse: collapse; width: 100%; border-top: 1px solid #000000; border-bottom: 1px solid #000000;">
            <tr width="100%">
                <td width="100%" style=" font-size: 14px; text-align: center; padding-top: 10px;padding-bottom:10px;">
                    <p style=" font-size: 14px;
						width: 100%;
						display: block;
					    color: #000000;
					    text-align: center;
					    font-family: sans-serif, areal;
					    font-weight: 600;">
                        <strong>
                            DIRECT DEPOSIT DISTRIBUTION
                        </strong>
                    </p>
                </td>
            </tr>

        </table>

        <table style=" font-size: 18px;font-family: sans-serif; border-collapse: collapse; width: 100%;">
            <tr>
                <td width="22.5%" style=" font-size: 14px;text-align: center">Acct type</td>
                <td width="22.5%" style=" font-size: 14px;text-align: center">Bank ABA #</td>
                <td width="22.5%" style=" font-size: 14px;text-align: center">Acct number</td>
                <td width="22.5%" style=" font-size: 14px;text-align: right;">Acct distribution</td>
                <td width="10%" style=" font-size: 14px;padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="currency" class="js-value" style="width: 80px;" name="total5">
					<?php } else {
						echo $total5;
					} ?>
                </td>
            </tr>
            <tr>
                <td width="22.5%" style=" font-size: 14px;text-align: center">
					<?php if ( $input ) { ?>
                        <input type="text" value="Checking" style="width: 140px;" name="checking">
					<?php } else {
						echo $checking;
					} ?>
                </td>
                <td width="22.5%" style=" font-size: 14px;text-align: center">
					<?php if ( $input ) { ?>
                        <input type="text" style="width: 140px;" name="checking_number">
					<?php } else {
						echo $checking_number;
					} ?>
                </td>
                <td width="22.5%" style=" font-size: 14px;text-align: center">******
					<?php if ( $input ) { ?>
                        <input type="text" maxlength="4" minlength="4" style="width: 80px;" name="acct_num">
					<?php } else {
						echo $acct_num;
					} ?>
                </td>
                <td width="22.5%" style=" font-size: 14px;text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="text" value="100.00%" style="width: 80px;" name="acct_proc">
					<?php } else {
						echo $acct_proc;
					} ?>
                </td>
                <td width="10%" style=" font-size: 14px;padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="currency" class="js-value" style="width: 80px;" name="total6">
					<?php } else {
						echo $total6;
					} ?>
                </td>
            </tr>
        </table>

        <table
                style=" font-size: 14px;margin-top: 20px; font-family: sans-serif; border-collapse: collapse; width: 100%; border-top: 1px solid #000000; border-bottom: 1px solid #000000;">
            <tr width="100%">
                <td width="100%" style=" font-size: 14px;text-align: center; padding-top: 10px;padding-bottom:10px;">
                    <p style=" font-size: 14px;
						width: 100%;
						display: block;
					    color: #000000;
					    text-align: center;
					    font-family: sans-serif, areal;
					    font-weight: 600;">
                        <strong>
                            DISPATCH SUMMARY
                        </strong>
                    </p>
                </td>
            </tr>
        </table>
        <table style=" font-size: 14px; font-family: sans-serif; border-collapse: collapse; width: 100%;">
            <tr>
                <td width="90%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    ORDERS:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="text" class="js-orders-count" style="width: 140px; text-align: right;"
                               name="orders">
					<?php } else {
						echo $orders;
					} ?>
                </td>
            </tr>
            <tr>
                <td width="90%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    LOADED MILES:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="text" class="js-miles-count" style="width: 140px; text-align: right;"
                               name="miles_count_1">
					<?php } else {
						echo $miles_count_1;
					} ?>
                </td>
            </tr>
            <tr>
                <td width="90%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    EMPTY MILES:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="text" value="0" style="width: 140px; text-align: right;" name="miles_count_empty">
					<?php } else {
						echo $miles_count_empty;
					} ?>
                </td>
            </tr>
            <tr>
                <td width="90%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    TOTAL MILES:
                </td>
                <td width="10%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
					<?php if ( $input ) { ?>
                        <input type="text" class="js-miles-count" style="width: 140px; text-align: right;"
                               name="miles_count_2">
					<?php } else {
						echo $miles_count_2;
					} ?>
                </td>
            </tr>
        </table>

        <table
                style=" font-size: 14px;margin-top: 20px; font-family: sans-serif; border-collapse: collapse; width: 100%; border-top: 1px solid #000000; border-bottom: 1px solid #000000;">
            <tr width="100%">
                <td width="100%" style=" font-size: 14px;text-align: center; padding-top: 10px;padding-bottom:10px;">
                    <p style=" font-size: 14px;
						width: 100%;
						display: block;
					    color: #000000;
					    text-align: center;
					    font-family: sans-serif, areal;
					    font-weight: 600;">
                        <strong>
                            YTD SUMMARY
                        </strong>
                    </p>
                </td>
            </tr>
        </table>

        <table style=" font-size: 14px; font-family: sans-serif; border-collapse: collapse; width: 100%;">
            <tr>
                <td width="85%" style="font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    <strong>EARNINGS:</strong>
                </td>
                <td width="15%" style=" font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: right;">
                    <strong>
						<?php if ( $input ) { ?>
                            <input type="currency" class="js-value" style="width: 80px;" name="total7">
						<?php } else {
							echo $total7;
						} ?>
                    </strong></td>
            </tr>
            <tr>
                <td width="85%" style="font-size: 14px; padding-top: 10px;padding-bottom:10px; text-align: left;">
                    <p>
						<?php if ( $input ) { ?>
                            <input type="text" placeholder="<?php echo $this->company_name; ?>"
                                   value="<?php echo $this->company_name; ?>" style="width: 240px; text-align: left;"
                                   name="text1">
						<?php } else {
							echo $text1;
						} ?>
                    </p>
                    <p>
						<?php if ( $input ) { ?>
                            <input type="text" placeholder="c/o TAFS, Inc. (Master File)"
                                   style="width: 240px; text-align: left;"
                                   name="text2">
						<?php } else {
							echo $text2;
						} ?>
                    </p>
                    <p>
						<?php if ( $input ) { ?>
                            <input type="text" placeholder="P.O. Box 872632" style="width: 240px; text-align: left;"
                                   name="text3">
						<?php } else {
							echo $text3;
						} ?>
                    </p>
                    <p>
						<?php if ( $input ) { ?>
                            <input type="text" placeholder="Kansas City MO 64187"
                                   style="width: 240px; text-align: left;"
                                   name="text4">
						<?php } else {
							echo $text4;
						} ?>
                    </p>

                </td>
            </tr>
        </table>
		<?php
		return ob_get_clean();
	}
	
	public function get_template_invoice( $input = false ) {
		$data                = filter_input( INPUT_POST, 'date', FILTER_SANITIZE_STRING );
		$str_date            = strtotime( $data );
		$corect_formate_date = date( 'm/d/Y', $str_date );
		$number              = filter_input( INPUT_POST, 'number', FILTER_SANITIZE_STRING );
		$number2             = filter_input( INPUT_POST, 'number2', FILTER_SANITIZE_STRING );
		
		$from_1 = filter_input( INPUT_POST, 'from_1', FILTER_SANITIZE_STRING );
		$from_2 = filter_input( INPUT_POST, 'from_2', FILTER_SANITIZE_STRING );
		$from_3 = filter_input( INPUT_POST, 'from_3', FILTER_SANITIZE_STRING );
		$from_4 = filter_input( INPUT_POST, 'from_4', FILTER_SANITIZE_STRING );
		$from_5 = filter_input( INPUT_POST, 'from_5', FILTER_SANITIZE_STRING );
		$from_6 = filter_input( INPUT_POST, 'from_6', FILTER_SANITIZE_STRING );
		
		$for_1 = filter_input( INPUT_POST, 'for_1', FILTER_SANITIZE_STRING );
		$for_2 = filter_input( INPUT_POST, 'for_2', FILTER_SANITIZE_STRING );
		$for_3 = filter_input( INPUT_POST, 'for_3', FILTER_SANITIZE_STRING );
		$for_4 = filter_input( INPUT_POST, 'for_4', FILTER_SANITIZE_STRING );
		$for_5 = filter_input( INPUT_POST, 'for_5', FILTER_SANITIZE_STRING );
		$for_6 = filter_input( INPUT_POST, 'for_6', FILTER_SANITIZE_STRING );
		
		$item             = filter_input( INPUT_POST, 'item', FILTER_SANITIZE_STRING );
		$item2            = filter_input( INPUT_POST, 'item2', FILTER_SANITIZE_STRING );
		$rate             = filter_input( INPUT_POST, 'rate', FILTER_SANITIZE_STRING );
		$amount3          = filter_input( INPUT_POST, 'amount3', FILTER_SANITIZE_STRING );
		$quick_pay_change = filter_input( INPUT_POST, 'quick_pay_change', FILTER_SANITIZE_STRING );
		$procent          = filter_input( INPUT_POST, 'procent', FILTER_SANITIZE_STRING );
		
		ob_start();
		?>
        <div style="font-family: sans-serif;">
            <table style="border-collapse: collapse;
    border-spacing: 0; margin-bottom: 20px;" border="0" width="100%">
                <tr>
                    <td>
                        <h3 style="    font-size: 40px;
    color: #69b4ff;
    margin: 40px 0 15px;
    font-family: sans-serif, areal; font-weight: 400">
                            Invoice
                        </h3>
                        <table style="<?php echo ( $input ) ? 'border-spacing: 0px 15px;     border-collapse: initial;'
							: ''; ?>">
                            <tr>
                                <td width="150px">
                                    <span style="
                                            width: 150px;
                                            font-size: 16px;
                                            color: #717171;
                                            display: inline-block;
                                            font-family: sans-serif;
                                            font-size: 16px;">
                                        Invoice number:
                                    </span>
                                </td>
                                <td width="250px">
                                    <span style="
                                    font-weight: bold;
                                    font-size: 16px;
                                    font-family: sans-serif;">
                                        <?php if ( $input ) { ?>
                                            <input type="text" name="number">
                                        <?php } else {
	                                        echo $number;
                                        } ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td width="150px">
                                    <span style="width: 150px;     font-size: 16px;
    color: #717171;
    display: inline-block;
    font-family: sans-serif;
    font-size: 16px;">Invoice date:</span>
                                </td>
                                <td width="250px">
                                    <span style="    font-weight: bold;
    font-size: 16px;
    font-family: sans-serif;">
                                        <?php if ( $input ) { ?>
                                            <input type="date" name="date">
                                        <?php } else {
	                                        echo $corect_formate_date;
                                        } ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td width="150px">
                                    <span style="width: 150px;     font-size: 16px;
    color: #717171;
    display: inline-block;
    font-family: sans-serif;
    font-size: 16px;">Load number:</span>
                                </td>
                                <td width="250px">
                                    <span style="    font-weight: bold;
    font-size: 16px;
    font-family: sans-serif;">
                                         <?php if ( $input ) { ?>
                                             <input type="text" name="number2">
                                         <?php } else {
	                                         echo $number2;
                                         } ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="180px" style="vertical-align: bottom;">
                        <img height="120px" style="height: 120px;"
                             src="<?php echo $this->logo; ?>"
                             alt="logo">
                    </td>
                </tr>
            </table>
            <table border="0" style="width: 100%; margin-bottom: 20px; border-collapse: collapse;
    border-spacing: 0;">
                <tbody>
                <tr>
                    <td width="50%" style="background-color: rgba(105, 180, 255, .17);
    padding: 25px 15px;
    box-sizing: border-box;
    border-radius: 15px; overflow: hidden;">
                        <p style="font-size: 30px;
    color: #69b4ff;
    margin: 0;
    font-family: sans-serif, areal; margin-bottom: 10px;">From</p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal;
    font-weight: 600; margin-bottom: 10px;">
                            <strong>
                                <strong>Name:</strong>
								<?php if ( $input ) { ?>
                                    <input type="text" name="from_1" value="<?php echo $this->company_name; ?>.">
								<?php } else {
									echo $from_1;
								} ?>
                            </strong>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>Address:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="from_2" value="<?php echo $this->company_address; ?>">
							<?php } else {
								echo $from_2;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong> City, State, Zip code:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="from_3" value="<?php echo $this->company_sity_state_zip; ?>">
							<?php } else {
								echo $from_3;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>Country:</strong>
							
							<?php if ( $input ) { ?>
                                <select name="from_4">
                                    <option value="USA" selected>USA</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Mexico">Mexico</option>
                                    <option value="Ukraine">Ukraine</option>
                                    <option value="Poland">Poland</option>
                                    <option value="Czech Republic">Czech Republic</option>
                                </select>
							<?php } else {
								echo $from_4;
							} ?>

                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>Email:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="from_5" value="<?php echo $this->company_email; ?>">
							<?php } else {
								echo $from_5;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal;">
                            <strong>Phone number:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="from_6" value="<?php echo $this->company_phone; ?>">
							<?php } else {
								echo $from_6;
							} ?>
                        </p>
                    </td>
                    <td width="50%" style="background-color: rgba(105, 180, 255, .17);
    padding: 25px 15px;
    padding: 25px 15px;
    box-sizing: border-box;
    border-radius: 15px; overflow: hidden; margin-bottom: 10px;">

                        <p style="font-size: 30px;
    color: #69b4ff;
    margin: 0;
    font-family: sans-serif, areal; margin-bottom: 10px;">For</p>
                        <br>
                        <p style="    font-size: 16px; font-family: sans-serif, areal;
    font-weight: 600; margin-bottom: 10px;"><strong>
                                <strong>Name:</strong>
								<?php if ( $input ) { ?>
                                    <input type="text" name="for_1">
								<?php } else {
									echo $for_1;
								} ?>
                            </strong></p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>Address:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="for_2">
							<?php } else {
								echo $for_2;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>City, State, Zip code:</strong>
							
							<?php if ( $input ) { ?>
                                <input type="text" name="for_3">
							<?php } else {
								echo $for_3;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>Country:</strong>
							
							<?php if ( $input ) { ?>
                                <select name="for_4">
                                    <option value="USA" selected>USA</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Mexico">Mexico</option>
                                    <option value="Ukraine">Ukraine</option>
                                    <option value="Poland">Poland</option>
                                    <option value="Czech Republic">Czech Republic</option>
                                </select>
							<?php } else {
								echo $for_4;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; margin-bottom: 10px;">
                            <strong>Email:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="for_5">
							<?php } else {
								echo $for_5;
							} ?>
                        </p>
                        <br>
                        <p style="font-size: 16px; font-family: sans-serif, areal; ">
                            <strong>Phone:</strong>
							<?php if ( $input ) { ?>
                                <input type="text" name="for_6">
							<?php } else {
								echo $for_6;
							} ?>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>

            <table border="0" width="100%" style="margin-bottom: 20px; border-collapse: collapse;
    border-spacing: 0;   border-radius: 15px;
    overflow: hidden;">
                <thead>
                <tr>
                    <td style="padding: 15px; font-family: sans-serif, areal; background: #69b4ff; color: #ffffff; font-size: 16px; font-weight: bold;"
                        width="50%">Item
                    </td>
                    <td style="padding: 15px; font-family: sans-serif, areal; background: #69b4ff; color: #ffffff; font-size: 16px; font-weight: bold;"
                        width="25%">
                        Quick pay charge
                    </td>
                    <td style="padding: 15px; font-family: sans-serif, areal; background: #69b4ff; color: #ffffff; font-size: 16px; font-weight: bold;"
                        width="25%">Linehaul
                    </td>

                </tr>
                </thead>
                <tbody>
                <tr>

                    <td style="padding: 15px; font-family: sans-serif, areal;" width="50%"> <?php if ( $input ) { ?>
                            <p style="margin-bottom: 16px;">
                                Shipper location
                                <input type="text" name="item">
                            </p>
                            <p>
                                Delivery location
                                <input type="text" name="item2">
                            </p>
						<?php } else {
							echo '<p><strong>Shipper location </strong>', $item . '</p>';
							echo '<p><strong>Delivery location </strong>', $item2 . '</p>';
						} ?>
                    </td>
                    <td style="padding: 15px; font-family: sans-serif, areal;" width="25%">
						<?php if ( $input ) { ?>
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <input type="checkbox" class="js-quick_pay_change" name="quick_pay_change">
                                <label style="display: flex; align-items: center; gap: 16px;">
                                    <input type="number" class="js-procent" min="0" name="procent" step="0.01"
                                           value="3">%</label>
                            </div>
						<?php } else { ?>
							<?php if ( $quick_pay_change !== 'on' ) { ?>
                                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                                     xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                     viewBox="0 0 60 60" xml:space="preserve">

<!--                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411-->
                                    <!--                                                                    c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156-->
                                    <!--                                                                    c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>-->
                                    <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                                </svg>
							<?php } ?>
							
							<?php if ( $quick_pay_change === 'on' ) { ?>
                                <span><?php echo $procent; ?>%</span>
							<?php } ?>
                            </p>
						
						<?php } ?>
                    </td>
                    <td style="padding: 15px; font-family: sans-serif, areal;" width="25%">
						<?php if ( $input ) { ?>
                            <input type="currency" class="custom4" name="rate">
						<?php } else {
							echo $rate;
						} ?>
                    </td>

                </tr>
                </tbody>
            </table>

            <table width="100%">
                <tbody>
                <tr>
                    <td style="padding: 15px; font-family: sans-serif, areal; font-size: 20px; font-weight: bold; text-align: left"
                        width="35%">

                    </td>
                    <td style="padding: 15px; font-family: sans-serif, areal;" width="30%"></td>
                    <td style="padding: 15px; font-family: sans-serif, areal; font-size: 20px; font-weight: bold;"
                        width="25%">Total (USD)
                    </td>
                    <td style="padding: 15px; font-family: sans-serif, areal; font-size: 20px; font-weight: bold; text-align: left"
                        width="10%">
						<?php if ( $input ) { ?>
                            <input type="currency" class="js-value" name="amount3">
						<?php } else {
							echo $amount3;
						} ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <div>
                <p>
                    Make all checks payable to <?php echo $this->company_name; ?><br>
                    If you have any questions concerning this invoice, contact us <?php echo $this->company_phone; ?>
                </p>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * get html template document
	 * @return string
	 */
	public function get_template( $input = false ) {
		$data                = filter_input( INPUT_POST, 'date', FILTER_SANITIZE_STRING );
		$str_date            = strtotime( $data );
		$corect_formate_date = date( 'm/d/Y', $str_date );
		$sf_name             = filter_input( INPUT_POST, 'ship-from-name', FILTER_SANITIZE_STRING );
		$sf_address          = filter_input( INPUT_POST, 'ship-from-address', FILTER_SANITIZE_STRING );
		$sf_zip              = filter_input( INPUT_POST, 'ship-from-zip', FILTER_SANITIZE_STRING );
		$sf_sid              = filter_input( INPUT_POST, 'ship-from-sid', FILTER_SANITIZE_STRING );
		$sf_fov              = filter_input( INPUT_POST, 'ship-from-fov', FILTER_SANITIZE_STRING );
		$st_name             = filter_input( INPUT_POST, 'ship-to-name', FILTER_SANITIZE_STRING );
		$st_address          = filter_input( INPUT_POST, 'ship-to-address', FILTER_SANITIZE_STRING );
		$st_zip              = filter_input( INPUT_POST, 'ship-to-zip', FILTER_SANITIZE_STRING );
		$st_sid              = filter_input( INPUT_POST, 'ship-to-sid', FILTER_SANITIZE_STRING );
		$st_fov              = filter_input( INPUT_POST, 'ship-to-fov', FILTER_SANITIZE_STRING );
		$bill_name           = filter_input( INPUT_POST, 'bill-name', FILTER_SANITIZE_STRING );
		$bill_address        = filter_input( INPUT_POST, 'bill-address', FILTER_SANITIZE_STRING );
		$bill_zip            = filter_input( INPUT_POST, 'bill-zip', FILTER_SANITIZE_STRING );
		$instruction         = filter_input( INPUT_POST, 'instruction', FILTER_SANITIZE_STRING );
		$bill_landing        = filter_input( INPUT_POST, 'bill-of-landing', FILTER_SANITIZE_STRING );
		$trailer             = filter_input( INPUT_POST, 'trailer', FILTER_SANITIZE_STRING );
		$seal                = filter_input( INPUT_POST, 'seal', FILTER_SANITIZE_STRING );
		$scac                = filter_input( INPUT_POST, 'scac', FILTER_SANITIZE_STRING );
		$pro_number          = filter_input( INPUT_POST, 'pro-number', FILTER_SANITIZE_STRING );
		$fee_terms           = filter_input( INPUT_POST, 'fee-terms', FILTER_SANITIZE_STRING );
		$prepaid             = filter_input( INPUT_POST, 'prepaid', FILTER_SANITIZE_STRING );
		$acceptable          = filter_input( INPUT_POST, 'acceptable', FILTER_SANITIZE_STRING );
		$master_bill         = filter_input( INPUT_POST, 'master-bill', FILTER_SANITIZE_STRING );
		
		$customer_order = array();
		for ( $i = 0; $i < 5; $i ++ ) {
			$number = filter_input( INPUT_POST, 'customer-order-number-' . $i, FILTER_SANITIZE_STRING );
			$pkgs   = filter_input( INPUT_POST, 'customer-pkgs-' . $i, FILTER_SANITIZE_STRING );
			$weight = filter_input( INPUT_POST, 'customer-weight-' . $i, FILTER_SANITIZE_STRING );
			$ps     = filter_input( INPUT_POST, 'palet-slip-' . $i, FILTER_SANITIZE_STRING );
			$info   = filter_input( INPUT_POST, 'customer-info-' . $i, FILTER_SANITIZE_STRING );
			if ( ! empty( $number ) || ! empty( $pkgs ) || ! empty( $weight ) || ! empty( $ps ) || ! empty( $info ) ) {
				array_push( $customer_order, array(
					$number,
					$pkgs,
					$weight,
					$ps,
					$info,
				) );
			}
		}
		
		$customer_total = filter_input( INPUT_POST, 'customer-total-5', FILTER_SANITIZE_STRING );
		
		$table = array();
		for ( $i = 0; $i < 5; $i ++ ) {
			$hedline_qty         = filter_input( INPUT_POST, 'hedline-qty-' . $i, FILTER_SANITIZE_STRING );
			$hedline_type        = filter_input( INPUT_POST, 'hedline-type-' . $i, FILTER_SANITIZE_STRING );
			$package_qty         = filter_input( INPUT_POST, 'package-qty-' . $i, FILTER_SANITIZE_STRING );
			$package_type        = filter_input( INPUT_POST, 'package-type-' . $i, FILTER_SANITIZE_STRING );
			$package_weight      = filter_input( INPUT_POST, 'package-weight-' . $i, FILTER_SANITIZE_STRING );
			$package_hm          = filter_input( INPUT_POST, 'package-hm-' . $i, FILTER_SANITIZE_STRING );
			$package_description = filter_input( INPUT_POST, 'package-description-' . $i, FILTER_SANITIZE_STRING );
			$package_nmfc        = filter_input( INPUT_POST, 'package-nmfc-' . $i, FILTER_SANITIZE_STRING );
			$package_class       = filter_input( INPUT_POST, 'package-class-' . $i, FILTER_SANITIZE_STRING );
			if ( ! empty( $hedline_qty ) || ! empty( $hedline_type ) || ! empty( $package_qty ) || ! empty( $package_type ) || ! empty( $package_weight ) || ! empty( $package_hm ) || ! empty( $package_description ) || ! empty( $package_nmfc ) || ! empty( $package_class ) ) {
				array_push( $table, array(
					$hedline_qty,
					$hedline_type,
					$package_qty,
					$package_type,
					$package_weight,
					$package_hm,
					$package_description,
					$package_nmfc,
					$package_class
				) );
			}
		}
		
		$table_hendling_total = filter_input( INPUT_POST, 'table-hendling-total', FILTER_SANITIZE_STRING );
		$table_package_total  = filter_input( INPUT_POST, 'table-package-total', FILTER_SANITIZE_STRING );
		$table_weight_total   = filter_input( INPUT_POST, 'table-weight-total', FILTER_SANITIZE_STRING );
		$table_total          = filter_input( INPUT_POST, 'table-total', FILTER_SANITIZE_STRING );
		$cod_fee_terms        = filter_input( INPUT_POST, 'cod-fee-terms', FILTER_SANITIZE_STRING );
		$cod_prepaid          = filter_input( INPUT_POST, 'cod-prepaid', FILTER_SANITIZE_STRING );
		$cod_acceptable       = filter_input( INPUT_POST, 'cod-acceptable', FILTER_SANITIZE_STRING );
		$trailer_by_ship      = filter_input( INPUT_POST, 'trailer-by-ship', FILTER_SANITIZE_STRING );
		$trailer_by_driver    = filter_input( INPUT_POST, 'trailer-by-driver', FILTER_SANITIZE_STRING );
		$freight_by_ship      = filter_input( INPUT_POST, 'freight-by-ship', FILTER_SANITIZE_STRING );
		$freight_by_pallets   = filter_input( INPUT_POST, 'freight-by-pallets', FILTER_SANITIZE_STRING );
		$freight_by_pieces    = filter_input( INPUT_POST, 'freight-by-pieces', FILTER_SANITIZE_STRING );
		
		
		ob_start();
		?>
        <table border="1" style="width: 100%; font-family: sans-serif; border-collapse: collapse; ">
            <tr>
                <td width="20%" style="padding:3px; font-size: 11px;">date:
					<?php if ( $input ) { ?>
                        <input type="date" name="date" value="<?php echo date( 'Y-m-d' ); ?>">
					<?php } else {
						echo $corect_formate_date;
					} ?>
                </td>
                <td width="80%" style="padding:3px; text-align: center;">
                    <h1 style="text-align: center; font-size: 20px; margin: 0; width: 100%;">Bill of Lading</h1>
                </td>
            </tr>
        </table>
        <table border="0" style="width: 100%; border-spacing: 0; border-collapse: collapse;">
            <tr style="padding: 0;">
                <td width="50%" style="padding: 0; border: 1px solid #000000;  vertical-align: top;">
                    <!-- SHIP FORM -->
                    <table border="0"
                           style="width: 100%; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
                        <tr>
                            <td width="100%"
                                style="text-align: center; padding:3px; font-size: 13px; background-color: #000000; color: #ffffff">
                                SHIP FROM
                            </td>
                        </tr>
                    </table>
                    <table border="0"
                           style="width: 100%; border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
                        <tr>
                            <td width="100%" style="padding:3px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Name:
									<?php if ( $input ) { ?>
                                        <input type="text" name="ship-from-name">
									<?php } else {
										echo $sf_name;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Address:
									<?php if ( $input ) { ?>
                                        <input type="text" name="ship-from-address">
									<?php } else {
										echo $sf_address;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    City/State/Zip:
									<?php if ( $input ) { ?>
                                        <input type="text" name="ship-from-zip">
									<?php } else {
										echo $sf_zip;
									} ?>
                                </p>
                                <table border="0"
                                       style="border-collapse: initial; font-size: 11px; margin: 0; padding: 0; border: 0; width: 100%;">
                                    <tr>
                                        <td width="86%">
                                            CID#:
											<?php if ( $input ) { ?>
                                                <input type="text" name="ship-from-sid">
											<?php } else {
												echo $sf_sid;
											} ?>
                                        </td>
                                        <td width="14%">
                                            <p style="font-size: 11px; margin: 0; text-align: right; line-height: 20px;">
                                                FOV:
												<?php if ( $input ) { ?>
                                                    <input type="checkbox" name="ship-from-fov">
												<?php } else { ?>
                                                    <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                                         xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                                         viewBox="0 0 60 60" xml:space="preserve">
                                                    <?php if ( $sf_fov === 'on' ) { ?>
                                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                                    <?php } ?>
                                                        <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                                </svg>
												<?php } ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>

                    </table>
                    <!--   SHIP TO -->
                    <table border="0"
                           style="width: 100%; border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
                        <tr>
                            <td width="100%"
                                style="text-align: center;padding:3px; font-size: 13px; background-color: #000000; color: #ffffff">
                                SHIP TO
                            </td>
                        </tr>
                    </table>
                    <table border="0"
                           style="width: 100%;    border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
                        <tr>
                            <td width="100%" style="padding:3px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Name:
									<?php if ( $input ) { ?>
                                        <input type="text" name="ship-to-name">
									<?php } else {
										echo $st_name;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Address:
									<?php if ( $input ) { ?>
                                        <input type="text" name="ship-to-address">
									<?php } else {
										echo $st_address;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    City/State/Zip:
									<?php if ( $input ) { ?>
                                        <input type="text" name="ship-to-zip">
									<?php } else {
										echo $st_zip;
									} ?>
                                </p>
                                <table border="0"
                                       style="border-collapse: initial; font-size: 11px; margin: 0; padding: 0; border: 0; width: 100%;">
                                    <tr>
                                        <td width="86%">
                                            CID#:
											<?php if ( $input ) { ?>
                                                <input type="text" name="ship-to-sid">
											<?php } else {
												echo $st_sid;
											} ?>
                                        </td>
                                        <td width="14%">
                                            <p style="font-size: 11px; margin: 0; text-align: right; line-height: 20px;">
                                                FOV:
												<?php if ( $input ) { ?>
                                                    <input type="checkbox" name="ship-to-fov">
												<?php } else { ?>
                                                    <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                                         xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                                         viewBox="0 0 60 60" xml:space="preserve">
                                                        <?php if ( $st_fov === 'on' ) { ?>

                                                            <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                                        <?php } ?>
                                                        <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                                </svg>
												<?php } ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>

                    </table>

                    <!-- THIRD PARTY FREIGHT CHARGES BILL TO -->
                    <table border="0"
                           style="width: 100%; border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
                        <tr>
                            <td width="100%"
                                style="text-align: center;padding:3px; font-size: 13px; background-color: #000000; color: #ffffff">
                                THIRD PARTY FREIGHT CHARGES BILL TO
                            </td>
                        </tr>
                    </table>
                    <table border="0"
                           style="width: 100%; border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
                        <tr>
                            <td width="100%" style="padding:3px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Name:
									<?php if ( $input ) { ?>
                                        <input type="text" name="bill-name">
									<?php } else {
										echo $bill_name;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Address:
									<?php if ( $input ) { ?>
                                        <input type="text" name="bill-address">
									<?php } else {
										echo $bill_address;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    City/State/Zip:
									<?php if ( $input ) { ?>
                                        <input type="text" name="bill-zip">
									<?php } else {
										echo $bill_zip;
									} ?>
                                </p>
                            </td>
                        </tr>

                    </table>
                    <!-- SPECIAL INSTRUCTIONS: -->
                    <table border="0"
                           style="width: 100%; border-top: 1px solid #000000;  font-family: sans-serif; border-collapse: collapse;">
                        <tr>
                            <td width="100%" style="padding:3px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    SPECIAL INSTRUCTIONS:
									<?php if ( $input ) { ?>
                                        <br>
                                        <textarea name="instruction"></textarea>
									<?php } else {
										echo $instruction;
									} ?>
                                </p>
                            </td>
                        </tr>

                    </table>
                </td>
                <!-- Right column -->
                <td width="50%" style="padding: 0; vertical-align: top; border: 1px solid #000000;">
                    <table border="0"
                           style="width: 100%; border-bottom: 1px solid #000000; font-family: sans-serif; border-collapse: collapse;">
                        <tr>
                            <td width="100%" style="padding:3px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0;">
                                    Bill of Lading Number:
									<?php if ( $input ) { ?>
                                        <input type="text" name="bill-of-landing">
									<?php } else {
										echo $bill_landing;
									} ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table border="0"
                           style="width: 100%; font-family: sans-serif; border-bottom: 1px solid #000000; border-collapse: collapse;">
                        <tr>
                            <td width="100%" style="padding:3px 3px 25px; font-size: 13px; color: #000000;">
                                <table style="border-collapse: initial; margin: 0; padding: 0; border: 0; width: 100%;">
                                    <tr>
                                        <td style="vertical-align: top;">
                                            <p style="font-size: 11px; margin: 0; margin-bottom: 0;">
                                                CARRIER: <?php echo $this->company_name; ?>
                                            </p>
                                            <p style="font-size: 11px; margin: 0; margin-bottom: 0;">
                                                MC# <?php echo $this->company_mc; ?>
                                                DOT# <?php echo $this->company_dot; ?>
                                            </p>
                                        </td>
                                        <td style="width: 70px; ">
                                            <p style="margin: 0;">
                                                <img height="60px"
                                                     src="<?php echo $this->logo; ?>"
                                                     alt="logo">
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    Trailer number:
									<?php if ( $input ) { ?>
                                        <input type="text" name="trailer">
									<?php } else {
										echo $trailer;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0;">
                                    Seal number(s):
									<?php if ( $input ) { ?>
                                        <input type="text" name="seal">
									<?php } else {
										echo $seal;
									} ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table border="0"
                           style="width: 100%; font-family: sans-serif;border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-collapse: collapse;">
                        <tr>
                            <td width="100%" style="padding:3px 3px 25px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0; margin-bottom: 5px;">
                                    SCAC:
									<?php if ( $input ) { ?>
                                        <input type="text" name="scac">
									<?php } else {
										echo $scac;
									} ?>
                                </p>
                                <p style="font-size: 11px; margin: 0;">
                                    Pro number:
									<?php if ( $input ) { ?>
                                        <input type="text" name="pro-number">
									<?php } else {
										echo $pro_number;
									} ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table border="0"
                           style="width: 100%; border-bottom: 1px solid #000000; font-family: sans-serif; border-collapse: collapse;">
                        <tr>
                            <td width="100%" style="padding:3px; font-size: 13px; color: #000000;">
                                <p style="font-size: 11px; margin: 0;">
                                    Freight Charge Terms: (freight charges are prepaid unless marked otherwise)<br>
                                    Prepaid: ________ Collect: ________ 3rd Party:
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table
                            style="width: 100%; border-bottom: 1px solid #000000;  font-family: sans-serif; border-collapse: collapse;">
                        <tr>
                            <td style="padding:3px;">
                                <p style="font-weight: 600; font-size: 13px; margin: 0;">Freight Charge Terms <span
                                            style="font-size: 11px; margin-bottom: 5px;">(Freight charges are prepaid unless marked
                                    otherwise):</span></p>
                                <span
                                        style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
                                Fee terms Collect:
                            <?php if ( $input ) { ?>
                                <input type="checkbox" name="fee-terms">
                            <?php } else { ?>
                                <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                     xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                     xml:space="preserve">
                                    <?php if ( $fee_terms === 'on' ) { ?>
                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                    <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
                            <?php } ?>
                            </span>
                                <span
                                        style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
                                Prepaid:
                                <?php if ( $input ) { ?>
                                    <input type="checkbox" name="prepaid">
                                <?php } else { ?>
                                    <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                         xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                         xml:space="preserve">
                                    <?php if ( $prepaid === 'on' ) { ?>
                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                            c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                    <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
                                <?php } ?>
                            </span>
                                <span
                                        style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
                                Customer check acceptable:
                                    <?php if ( $input ) { ?>
                                        <input type="checkbox" name="acceptable">
                                    <?php } else { ?>
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                             xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                             viewBox="0 0 60 60"
                                             xml:space="preserve">
                                    <?php if ( $acceptable === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                    <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
                                    <?php } ?>
                            </span>
                            </td>
                        </tr>
                    </table>
                    <table style="width: 100%;  font-family: sans-serif; border-collapse: collapse;">
                        <tr>
                            <td style="padding:3px;">
                                <p
                                        style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
									<?php if ( $input ) { ?>
                                        <input type="checkbox" name="master-bill">
									<?php } else { ?>
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                             xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                             viewBox="0 0 60 60"
                                             xml:space="preserve">
                                    <?php if ( $master_bill === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                            <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
									<?php } ?>
                                    Master bill of lading with attached underlying bills of lading.
                                </p>

                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table border="0"
               style="width: 100%; border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
            <tr>
                <td width="100%"
                    style="text-align: center;padding:3px; font-size: 13px; background-color: #000000; color: #ffffff">
                    CUSTOMER ORDER INFORMATION
                </td>
            </tr>
        </table>
        <table border="1"
               style="font-size: 11px; width: 100%; border: 1px solid #000000; font-family: sans-serif; border-collapse: collapse;">
            <tr>
                <td style=" text-align: center; width: 29%; padding: 3px 0;">
                    CUSTOMER ORDER NUMBER
                </td>
                <td style=" text-align: center; width: 14%; padding: 3px 0;">PKGS</td>
                <td style=" text-align: center; width: 14%; padding: 3px 0;">WEIGHT</td>
                <td style=" text-align: center; width: 14%; padding: 3px 0;" colspan="2">PALLET/SLIP</td>
                <td style=" text-align: center; width: 29%; padding: 3px 0;">
                    ADDITIONAL SHIPPER INFO
                </td>
            </tr>
			
			<?php
			
			$margin = 210;
			
			$count = ( is_array( $customer_order ) && sizeof( $customer_order ) >= 3 ) ? sizeof( $customer_order ) : 3;
			if ( $input ) {
				$count = 5;
			}
			for ( $i = 0; $i < $count; $i ++ ) {
				?>

                <tr>
                    <td style="padding: 3px; width: 29%; min-height: 21px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="customer-order-number-<?php echo $i; ?>">
						<?php } else {
							echo isset( $customer_order[ $i ][ 0 ] ) ? $customer_order[ $i ][ 0 ] : '&nbsp;';
						} ?>
                    </td>
                    <td style="padding: 3px; width: 14%; min-height: 21px; text-align: right;">
						<?php if ( $input ) { ?>
                            <input type="text" name="customer-pkgs-<?php echo $i; ?>">
						<?php } else {
							echo isset( $customer_order[ $i ][ 1 ] ) ? $customer_order[ $i ][ 1 ] : '';
						} ?>
                    </td>
                    <td style="padding: 3px; width: 14%; min-height: 21px; text-align: right;">
						<?php if ( $input ) { ?>
                            <input type="text" name="customer-weight-<?php echo $i; ?>">
						<?php } else {
							echo isset( $customer_order[ $i ][ 2 ] ) ? $customer_order[ $i ][ 2 ] : '';
						} ?>
                    </td>
                    <td style="padding: 3px; width: 7%; min-height: 21px; text-align: center;">
						<?php if ( $input ) { ?>
                            <input type="radio" value="palet" name="palet-slip-<?php echo $i; ?>">
						<?php } else {
							echo ( isset( $customer_order[ $i ][ 3 ] ) && $customer_order[ $i ][ 3 ] === 'palet' ) ? 'X'
								: '';
						} ?>
                    </td>
                    <td style="padding: 3px; width: 7%; min-height: 21px; text-align: center;">
						<?php if ( $input ) { ?>
                            <input type="radio" value="slip" name="palet-slip-<?php echo $i; ?>">
						<?php } else {
							echo ( isset( $customer_order[ $i ][ 3 ] ) && $customer_order[ $i ][ 3 ] === 'slip' ) ? 'X'
								: '';
						} ?>
                    </td>
                    <td style="padding: 3px; min-height: 21px; width: 29%;">
						<?php if ( $input ) { ?>
                            <input type="text" name="customer-info-<?php echo $i; ?>">
						<?php } else {
							echo isset( $customer_order[ $i ][ 4 ] ) ? $customer_order[ $i ][ 4 ] : '';
						} ?>
                    </td>
                </tr>
			
			<?php } ?>
            <tr>
                <td style="padding:3px; width: 29%;">
					<?php if ( $input ) { ?>
                        <input type="text" placeholder="GRAND TOTAL" name="customer-total-<?php echo $i; ?>">
					<?php } else {
						echo 'GRAND TOTAL ' . $customer_total;
					} ?>
                </td>
                <td style="padding:3px; width: 14%;"></td>
                <td style="padding:3px; width: 14%;"></td>
                <td style="padding:3px; width: 43%; background-color: #939393;" colspan="3">
                </td>
            </tr>
        </table>
        <table border="0"
               style="width: 100%; border-top:0; font-family: sans-serif; border-collapse: collapse; border-top: 0;">
            <tr>
                <td width="100%"
                    style="text-align: center;padding:3px; font-size: 13px; background-color: #000000; color: #ffffff">
                    CARRIER INFORMATION
                </td>
            </tr>
        </table>
        <table border="1"
               style="font-size: 11px; width: 100%; border: 1px solid #000000; font-family: sans-serif; border-collapse: collapse;">
            <tr>
                <td style=" text-align: center; width: 14%; padding: 3px 0;" colspan="2">
                    HANDLING
                </td>

                <td style=" text-align: center; width: 14%; padding: 3px 0;" colspan="2">
                    PACKAGE
                </td>

                <td style=" text-align: center; width: 10%; padding: 3px 0;" rowspan="2">
                    WEIGHT
                </td>

                <td style=" text-align: center; width: 5%; padding: 3px 0;" rowspan="2">
                    H.M.
                    <br>
                    (X)
                </td>
                <td style=" text-align: center; width: 43%; padding: 3px 0;" rowspan="2">
                    COMMODITY DESCRIPTION
                </td>
                <td style=" text-align: center; width: 14%; padding: 3px 0;" colspan="2">
                    LTL ONLY
                </td>
            </tr>
            <tr>
                <td style=" text-align: center; width: 7%; padding: 3px 0;">
                    QTY
                </td>

                <td style=" text-align: center; width: 7%; padding: 3px 0;">
                    TYPE
                </td>
                <td style=" text-align: center; width: 7%; padding: 3px 0;">
                    QTY
                </td>

                <td style=" text-align: center; width: 7%; padding: 3px 0;">
                    TYPE
                </td>

                <td style=" text-align: center; width: 7%; padding: 3px 0;">
                    NMFC
                </td>

                <td style=" text-align: center; width: 7%; padding: 3px 0;">
                    CLASS
                </td>
            </tr>
			<?php
			$count_table = ( is_array( $table ) && sizeof( $table ) >= 3 ) ? sizeof( $table ) : 3;
			
			if ( $input ) {
				$count_table = 5;
			}
			
			for ( $i = 0; $i < $count_table; $i ++ ) { ?>
                <tr>
                    <td style=" text-align: right; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="hedline-qty-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 0 ] ) ? $table[ $i ][ 0 ] : '&nbsp;';
						} ?>
                    </td>

                    <td style=" text-align: left; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="hedline-type-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 1 ] ) ? $table[ $i ][ 1 ] : '';
						} ?>
                    </td>
                    <td style=" text-align: right; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="package-qty-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 2 ] ) ? $table[ $i ][ 2 ] : '';
						} ?>
                    </td>

                    <td style=" text-align: left; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="package-type-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 3 ] ) ? $table[ $i ][ 3 ] : '';
						} ?>
                    </td>
                    <td style=" text-align: right; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="package-weight-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 4 ] ) ? $table[ $i ][ 4 ] : '';
						} ?>
                    </td>
                    <td style=" text-align: center; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="checkbox" name="package-hm-<?php echo $i; ?>">
						<?php } else {
							echo ( isset( $table[ $i ][ 5 ] ) && $table[ $i ][ 5 ] === 'on' ) ? 'X' : '';
						} ?>
                    </td>
                    <td style=" text-align: left; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="package-description-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 6 ] ) ? $table[ $i ][ 6 ] : '';
						} ?>
                    </td>

                    <td style=" text-align: right; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="package-nmfc-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 7 ] ) ? $table[ $i ][ 7 ] : '';
						} ?>
                    </td>

                    <td style=" text-align: left; width: 7%; padding: 3px;">
						<?php if ( $input ) { ?>
                            <input type="text" name="package-class-<?php echo $i; ?>">
						<?php } else {
							echo isset( $table[ $i ][ 8 ] ) ? $table[ $i ][ 8 ] : '';
						} ?>
                    </td>
                </tr>
			<?php } ?>
            <tr>
                <td style=" text-align: right; width: 7%; padding: 3px;">
					<?php if ( $input ) { ?>
                        <input type="text" name="table-hendling-total">
					<?php } else {
						echo $table_hendling_total;
					} ?>
                </td>

                <td style="background-color: #939393; text-align: left; width: 7%; padding: 3px;">
                </td>
                <td style=" text-align: right; width: 7%; padding: 3px;">
					<?php if ( $input ) { ?>
                        <input type="text" name="table-package-total">
					<?php } else {
						echo $table_package_total;
					} ?>
                </td>

                <td style="background-color: #939393; text-align: left; width: 7%; padding: 3px;">
                </td>
                <td style=" text-align: right; width: 7%; padding: 3px;">
					<?php if ( $input ) { ?>
                        <input type="text" name="table-weight-total">
					<?php } else {
						echo $table_weight_total;
					} ?>
                </td>
                <td style="background-color: #939393; text-align: center; width: 7%; padding: 3px;">
                </td>
                <td style=" text-align: left; width: 7%; padding: 3px;">
					
					<?php if ( $input ) { ?>
                        <input type="text" placeholder="GRAND TOTAL:" name="table-total">
					<?php } else {
						echo 'GRAND TOTAL: ' . $table_total;
					} ?>
                </td>

                <td style="background-color: #939393; text-align: right; width: 7%; padding: 3px;">
                </td>

                <td style="background-color: #939393; text-align: left; width: 7%; padding: 3px;">
                </td>
            </tr>
        </table>
		
		<?php
		
		$margin_bottom = $margin - ( ( $count + $count_table ) * 21 );
		?>

        <table border="1"
               style="font-size: 11px; margin-top: <?php echo $margin_bottom; ?>px; border-collapse: collapse; width: 100%; font-family: sans-serif;">
            <tr>
                <td width="50%" style="padding: 3px;">
                    Where the rate is dependant on value, shippers are required to state specifically in writing the
                    agreed
                    or
                    declared value of the property as follows: "The agreed or declared value of the property is
                    specifically
                    stated by the shipper to be not exceeding
                </td>
                <td width="50%" style="font-size:13px; vertical-align: top; padding: 3px">
                    <p style="margin: 0 0 5px; "><strong>COD Amount</strong> ___________________________________</p>
                    <br>
                    <span style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
                    Fee terms Collect:
                    <?php if ( $input ) { ?>
                        <input type="checkbox" name="cod-fee-terms">
                    <?php } else { ?>
                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                             xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                             xml:space="preserve">
                                   <?php if ( $cod_fee_terms === 'on' ) { ?>

                                       <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                   <?php } ?>
                                            <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
                    <?php } ?>
                </span>
                    <span style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
                    Prepaid:
                   <?php if ( $input ) { ?>
                       <input type="checkbox" name="cod-prepaid">
                   <?php } else { ?>
                       <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                            xml:space="preserve">
                                    <?php if ( $cod_prepaid === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                            <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
                   <?php } ?>
                </span>
                    <span style="font-size: 11px; margin: 0; margin-right: 20px; text-align: left; line-height: 20px;">
                    Customer check acceptable:
                    <?php if ( $input ) { ?>
                        <input type="checkbox" name="cod-acceptable">
                    <?php } else { ?>
                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                             xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                             xml:space="preserve">
                                    <?php if ( $cod_acceptable === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                            <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
                    <?php } ?>
                </span>
                </td>
            </tr>
        </table>
        <table border="1"
               style="font-size: 11px; width: 100%; border: 1px solid #000000; border-bottom: 0; font-family: sans-serif; border-collapse: collapse;">
            <tr>
                <td style="font-size: 11px; text-align:center; font-weight: 600; padding: 3px 0;">
                    <strong> Note: Liability limitation for loss or damage in this shipment may be applicable. See 49
                        USC &
                        14706(c)(1)(A) and (B).</strong>
                </td>
            </tr>
        </table>
        <table border="1"
               style="font-size: 11px; width: 100%; border: 1px solid #000000; border-bottom: 0; font-family: sans-serif; border-collapse: collapse;">
            <tr>
                <td width="45%" style="padding: 3px; font-size: 11px;">
                    Recived, subject to individually determined rates or contracts that have been agreed
                    upon in writing between the carrier and shipper, if applicable, otherwise to the rates,
                    classifications, and rules that have been established by the carrier and are available to
                    the shipper, on request, and to all applicable state and federal regulations.
                </td>
                <td width="55%" style="font-size:13px; vertical-align: top; padding: 3px;">
                    <p style="margin: 0; padding-bottom: 5px;">The carrier shall not make delivery of this shipment
                        without
                        payment of charges and all other lawful fees.</p>
                    <br>
                    <p style="margin: 0;"><strong>Shipper Signature</strong> ________________________________________
                    </p>
                </td>
            </tr>
        </table>
        <table border="1"
               style="font-size: 11px; width: 100%; vertical-align: top; border: 1px solid #000000; font-family: sans-serif; border-collapse: collapse;">
            <tr>
                <td width="30%" style="padding: 3px; vertical-align: top;">
                    <h2 style=" margin: 0 0 10px; font-size: 13px;">Shipper Signature/Date</h2>
                    <p>________________________________</p>
                    <br>
                    <p style="margin: 0;">
                        This is to certify that the above named materials are
                        properly classified, packaged, marked, and labeled, and are
                        in proper condition for transportation according to the
                        applicable regulations of the DOT.
                    </p>
                </td>
                <td width="15%" style="padding: 3px; vertical-align: top;">
                    <h2 style=" margin: 0 0 10px; font-size: 13px;">Trailer Loaded:</h2>
                    <br>
                    <p style="font-size: 11px; margin: 0 ; text-align: left; line-height: 20px;">
						<?php if ( $input ) { ?>
                            <input type="checkbox" name="trailer-by-ship">
						<?php } else { ?>
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                 xml:space="preserve">
                                    <?php if ( $trailer_by_ship === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
						<?php } ?>
                        By shipper
                    </p>

                    <p style="font-size: 11px; margin: 0 ; text-align: left; line-height: 20px;">
						<?php if ( $input ) { ?>
                            <input type="checkbox" name="trailer-by-driver">
						<?php } else { ?>
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                 xml:space="preserve">
                                    <?php if ( $trailer_by_driver === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
						<?php } ?>
                        By driver
                    </p>
                </td>
                <td width="25%" style="padding: 3px; vertical-align: top;">
                    <h2 style="margin: 0 0 10px; font-size: 13px;">Freight Counted:</h2>
                    <br>
                    <p style="font-size: 11px; margin: 0 ; text-align: left; line-height: 20px;">
						<?php if ( $input ) { ?>
                            <input type="checkbox" name="freight-by-ship">
						<?php } else { ?>
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                 xml:space="preserve">
                                    <?php if ( $freight_by_ship === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
						<?php } ?>
                        By shipper
                    </p>

                    <p style="font-size: 11px; margin: 0 ; text-align: left; line-height: 20px;">
						<?php if ( $input ) { ?>
                            <input type="checkbox" name="freight-by-pallets">
						<?php } else { ?>
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                 xml:space="preserve">
                                   <?php if ( $freight_by_pallets === 'on' ) { ?>

                                       <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                   <?php } ?>
                                <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
						<?php } ?>
                        By driver/pallets said to contain
                    </p>

                    <p style="font-size: 11px; margin: 0 ; text-align: left; line-height: 20px;">
						<?php if ( $input ) { ?>
                            <input type="checkbox" name="freight-by-pieces">
						<?php } else { ?>
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60"
                                 xml:space="preserve">
                                    <?php if ( $freight_by_pieces === 'on' ) { ?>

                                        <path d="M26.375,39.781C26.559,39.928,26.78,40,27,40c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411
                                                        c-0.414-0.368-1.045-0.33-1.412,0.083l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.273-1.405,0.156
                                                        c-0.345,0.432-0.275,1.061,0.156,1.406L26.375,39.781z"/>
                                    <?php } ?>
                                <path d="M0,0v60h60V0H0z M58,58H2V2h56V58z"/>
                                </svg>
						<?php } ?>
                        By driver/pieces
                    </p>
                </td>
                <td width="30%" style="padding: 3px; vertical-align: top;">
                    <h2 style=" margin: 0 0 10px; font-size: 13px;">Carrier Signature/Pickup Date</h2>
                    <p>________________________________</p>
                    <br>
                    Carrier acknowledges receipt of packages and required
                    placards. Carrier certifies emergency response information
                    was made available and/or carrier has the DOT emergency
                    response guidebook or equivalent documentation in the
                    vehicle, Property described above is received in good order, except as noted
                </td>
            </tr>
        </table>
        <table border="0"
               style="font-size: 13px; margin-top: 10px; font-weight: bold; width: 100%; vertical-align: top; font-family: sans-serif; border-collapse: collapse;">
            <tr>
                <td width="33%">
                    <p>In Time: _________________________</p>
                </td>
                <td width="33%">
                    <p>Out Time: _________________________</p>
                </td>
                <td width="33%">
                    <p style="text-align: right;">Signature: _________________________</p>
                </td>
            </tr>
        </table>
		<?php
		return ob_get_clean();
	}
	
}