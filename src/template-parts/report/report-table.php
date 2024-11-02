<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );

$TMSUsers = new TMSUsers();
$TMSShipper = new TMSReportsShipper();

$results       = get_field_value($args, 'results');
$total_pages   = get_field_value($args, 'total_pages');
$current_pages = get_field_value($args, 'current_pages');
$is_draft      = get_field_value($args, 'is_draft');

$page_type =  get_field_value($args, 'page_type');

$current_user_id = get_current_user_id();

$billing_info = $TMSUsers->check_user_role_access(array('administrator', 'billing', 'accounting'),true);
$hide_billing_and_shipping = $TMSUsers->check_user_role_access(array('billing', 'accounting'),true);

$my_team = $TMSUsers->check_group_access();

$helper = new TMSReportsHelper();

if ( ! empty( $results ) ) : ?>
	
	<?php if ($page_type === 'accounting'): ?>
	    <div class="w-100 mb-3">
            <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_quick_edit">Quick edit</button>
        </div>
	<?php endif; ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <?php if ($page_type === 'accounting'): ?>
                <th><input class="checkbox-big js-select-load-all" type="checkbox" name="select-all"></th>
            <?php endif; ?>
            
            <th scope="col">Date booked</th>
            <th scope="col" title="dispatcher">Disp.</th>
            <th scope="col">Pick up location</th>
            <th scope="col">Delivery location</th>
            <th scope="col">Unit & name</th>
            <th scope="col">
	            <?php if ($page_type === 'accounting'): ?>
                    Gross
	            <?php else: ?>
                    Booked rate
                <?php endif; ?>
        
            </th>
            <th scope="col">Driver rate</th>
            <?php if ($page_type === 'dispatcher'): ?>
                <th scope="col">Profit</th>
            <?php endif; ?>
    
	    	<?php if ($page_type === 'accounting'): ?>
                <th scope="col">True Profit</th>
            <?php endif; ?>
            <th scope="col">Pick Up Date</th>
	        <?php if ($page_type === 'accounting'): ?>
                <th scope="col">Delivery date</th>
            <?php endif; ?>
            
            <th scope="col">Load status</th>
	        <?php if ($page_type === 'dispatcher'): ?>
            <th scope="col">Instructions</th>
            <th scope="col">Source</th>
            <?php endif; ?>
	
	        <?php if ($page_type === 'accounting'): ?>
            <th scope="col">Invoice</th>
            <th scope="col">Factoring status</th>
            <th scope="col">Bank st.</th>
            <th scope="col">Payment st.</th>
	        <?php endif; ?>
			
            <?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                <th scope="col"></th>
			<?php endif; ?>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$main = get_field_value( $row, 'main' );
   
			$attached_files = get_field_value( $meta, 'attached_files' );
			$array_id_files = $attached_files ? explode( ',', $attached_files ) : false;
			$files_count    = is_array( $array_id_files ) ? '(' . sizeof( $array_id_files ) . ')' : '';
			$files_state    = empty( $files_count ) ? 'disabled' : '';

			$delivery_raw = get_field_value( $meta, 'delivery_location' );
			$delivery     = $delivery_raw ? json_decode( $delivery_raw, ARRAY_A ) : [];
			
			$pick_up_raw = get_field_value( $meta, 'pick_up_location' );
			$pick_up     = $pick_up_raw ? json_decode( $pick_up_raw, ARRAY_A ) : [];

			$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );

			$dispatcher          = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials      = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials )
				: '#030303';
			if ( ! $dispatcher ) {
				$dispatcher = array( 'full_name' => 'User not found', 'initials' => 'NF' );
			}

			$load_status  = get_field_value( $meta, 'load_status' );
			$status_label = $helper->get_label_by_key( $load_status, 'statuses' );
			$status       = esc_html( $status_label );
			
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
			$driver_phone = esc_html( get_field_value( $meta, 'driver_phone' ) );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' );
			$booked_rate     = esc_html( '$' . str_replace( '.00', '', $booked_rate_raw ) );
			
			$driver_rate_raw = get_field_value( $meta, 'driver_rate' );
			$driver_rate     = esc_html( '$' . str_replace( '.00', '', $driver_rate_raw ) );
			
			$profit_raw = get_field_value( $meta, 'profit' );
			$profit     = esc_html( '$' . str_replace( '.00', '', $profit_raw ) );
			$true_profit_raw = get_field_value( $meta, 'true_profit' );
			$true_profit     = esc_html( '$' . str_replace( '.00', '', $true_profit_raw ) );
			$pick_up_date_raw = get_field_value( $row, 'pick_up_date' );
			$pick_up_date     = esc_html( date( 'm/d/Y', strtotime( $pick_up_date_raw ) ) );
            
            $delivery_date_raw = get_field_value( $row, 'delivery_date' );
			$delivery_date     = esc_html( date( 'm/d/Y', strtotime( $delivery_date_raw ) ) );
			
			$instructions_raw = get_field_value( $meta, 'instructions' );
			$instructions     = $helper->get_label_by_key( $instructions_raw, 'instructions' );
			
			$source_raw = get_field_value( $meta, 'source' );
			$source     = esc_html( $helper->get_label_by_key( $source_raw, 'sources' ) );
   
			$factoring_status_row = get_field_value( $meta, 'factoring_status' );
			$factoring_status     = esc_html( $helper->get_label_by_key( $factoring_status_row, 'factoring_status' ) );
			
            $invoice_raw = get_field_value( $meta, 'invoiced_proof' );
//			$invoice     = esc_html( $helper->get_label_by_key( $invoice_raw, 'invoices' ) );
            
            $show_control = $TMSUsers->show_control_loads($my_team, $current_user_id, $dispatcher_initials, $is_draft);
            
			$status_class = $load_status;
            $factoring_class = strtolower($factoring_status_row);
            $factoring_class = str_replace(' ', '-', $factoring_class);
			
			$bank_status = 'Approved';
			$driver_pay_status = 'Processing';
			?>

            <tr>
	            <?php if ($page_type === 'accounting'): ?>
                    <td><input type="checkbox" id="load-<?php echo $row['id']; ?>" class="checkbox-big js-select-load" value="<?php echo $row[ 'id' ]; ?>" name="select-load"></td>
	            <?php endif; ?>
                
                <td><label class="h-100 cursor-pointer" for="load-<?php echo $row['id']; ?>"><?php echo $date_booked; ?></label></td>
                <td>
                    <div class="d-flex gap-1 flex-column">
                        <p class="m-0">
                              <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                                    class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                                  <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
                              </span>
                        </p>
                        <span class="text-small"><?php echo $reference_number; ?></span>
                    </div>
                </td>
              
                <td>
					<?php if ( is_array( $pick_up ) ): ?>
						<?php foreach ( $pick_up as $val ):  ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="<?php echo $val[ 'address' ]; ?>">
                              <?php echo $val[ 'short_address' ];
                              
	                              $detailed_address = $TMSShipper->get_shipper_by_id($val['address_id']);
                                  if (is_array($detailed_address)) {
                                    echo ' '.$detailed_address[0]->zip_code;
                                  }
                              
                              ?>
                              
                              
                        </p>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td>
					<?php if ( is_array( $delivery ) ): ?>
						<?php foreach ( $delivery as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <p class="m-0" data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="<?php echo $val[ 'address' ]; ?>">
                                  <?php echo $val[ 'short_address' ];
                                        $detailed_address = $TMSShipper->get_shipper_by_id($val['address_id']);
                                        if ( is_array( $detailed_address ) ) {
                                            echo ' ' . $detailed_address[ 0 ]->zip_code;
                                        }
                              ?>
                              
                              
                        </p>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <p class="m-0"><?php echo $unit_number_name; ?></p>
                        <?php if ( $page_type === 'accounting') { ?>
                        <span class="text-small">
                            <?php echo $driver_phone; ?>
                        </span>
                        <?php } ?>
                    </div></td>
                <td><?php echo $booked_rate; ?></td>
                <td><?php echo $driver_rate; ?></td>

	            <?php if ($page_type === 'dispatcher'): ?>
                    <td><?php echo $profit; ?></td>
	            <?php endif; ?>
	            
	            <?php if ($page_type === 'accounting'): ?>
                    <td><?php echo $true_profit; ?></td>
	            <?php endif; ?>
                
                <td><?php echo $pick_up_date; ?></td>
                <?php if ($page_type === 'accounting'): ?>
                    <td><?php echo $delivery_date; ?></td>
                <?php endif; ?>
                
                
                <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
	            
	            <?php if ($page_type === 'dispatcher'): ?>
                    <td>
                        <div class="table-list-icons"><?php echo $instructions; ?></div>
                    </td>
                    <td><?php echo $source; ?></td>
	            <?php endif; ?>
	            
	            <?php if ($page_type === 'accounting'): ?>
                    <td><?php echo $invoice_raw ? 'Invoiced' : 'Not invoiced'; ?></td>
                    <td class="<?php echo $factoring_class; ?>"><?php echo $factoring_status; ?></td>
                    <td>
                        <?php echo $bank_status; ?>
                    </td>
                    <td>
	                    <?php echo $driver_pay_status; ?>
                    </td>
	            <?php endif; ?>
             
             
				<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                    <td>
                        
                        <?php if ($show_control): ?>
                        
                        <div class="dropdown">
                            <button class="btn button-action" type="button" id="dropdownMenu2" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <svg fill="#000000" height="18px" width="18px" version="1.1"
                                     xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 512 512" xml:space="preserve">
                                <g>
                                    <g>
                                        <path d="M498.723,89.435H183.171V76.958c0-18.3-14.888-33.188-33.188-33.188h-51.5c-18.3,0-33.188,14.888-33.188,33.188v12.477
                                            H13.275C5.943,89.435,0,95.38,0,102.711c0,7.331,5.943,13.275,13.275,13.275h52.018v12.473c0,18.3,14.888,33.188,33.188,33.188
                                            h51.501c18.3,0,33.188-14.888,33.188-33.188v-12.473h315.553c7.332,0,13.275-5.945,13.275-13.275
                                            C511.999,95.38,506.055,89.435,498.723,89.435z M156.621,128.459c0,3.66-2.978,6.638-6.638,6.638H98.482
                                            c-3.66,0-6.638-2.978-6.638-6.638V76.958c0-3.66,2.978-6.638,6.638-6.638h51.501c3.66,0,6.638,2.978,6.638,6.638V128.459z"/>
                                    </g>
                                </g>
                                    <g>
                                        <g>
                                            <path d="M498.725,237.295h-52.019v-12.481c0-18.3-14.888-33.188-33.188-33.188h-51.501c-18.3,0-33.188,14.888-33.188,33.188
			v12.481H13.275C5.943,237.295,0,243.239,0,250.57c0,7.331,5.943,13.275,13.275,13.275h315.553v12.469
			c0,18.3,14.888,33.188,33.188,33.188h51.501c18.3,0,33.188-14.888,33.188-33.188v-12.469h52.019
			c7.332,0,13.275-5.945,13.275-13.275C512,243.239,506.057,237.295,498.725,237.295z M420.155,276.315
			c0,3.66-2.978,6.638-6.638,6.638h-51.501c-3.66,0-6.638-2.978-6.638-6.638v-51.501c0-3.66,2.978-6.638,6.638-6.638h51.501
			c3.66,0,6.638,2.978,6.638,6.638V276.315z"/>
                                        </g>
                                    </g>
                                    <g>
                                        <g>
                                            <path d="M498.725,396.014H276.432v-12.473c0-18.3-14.888-33.188-33.188-33.188h-51.501c-18.3,0-33.188,14.888-33.188,33.188
			v12.473H13.275C5.943,396.014,0,401.959,0,409.289c0,7.331,5.943,13.275,13.275,13.275h145.279v12.477
			c0,18.3,14.888,33.188,33.188,33.188h51.501c18.3,0,33.188-14.888,33.188-33.188v-12.477h222.293
			c7.332,0,13.275-5.945,13.275-13.275C512,401.957,506.057,396.014,498.725,396.014z M249.881,435.042
			c0,3.66-2.978,6.638-6.638,6.638h-51.501c-3.66,0-6.638-2.978-6.638-6.638v-51.501c0-3.66,2.978-6.638,6.638-6.638h51.501
			c3.66,0,6.638,2.978,6.638,6.638V435.042z"/>
                                        </g>
                                    </g>
                            </svg>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
								<?php if ( $TMSUsers->check_user_role_access( array( 'billing' ), true ) ) : ?>
                                    <li><a href="<?php echo $add_new_load . '?post_id=' . $row[ 'id' ]; ?>"
                                           class="dropdown-item">View</a></li>
								<?php else: ?>
                                    <li><a href="<?php echo $add_new_load . '?post_id=' . $row[ 'id' ]; ?>"
                                           class="dropdown-item">Edit</a></li>
								<?php endif; ?>
		
		                        <?php if ( $TMSUsers->check_user_role_access( array( 'administrator' ), true ) ): ?>
                                <li>
                                    <button class="dropdown-item text-danger js-remove-load" data-id="<?php echo $row[ 'id' ]; ?>" type="button">Delete</button>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
        
                        <?php endif; ?>
                    </td>
				<?php endif; ?>
            </tr>
		
		<?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	
	
	echo esc_html( get_template_part( 'src/template-parts/report/report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>

<?php else : ?>
    <p>No reports found.</p>
<?php endif; ?>