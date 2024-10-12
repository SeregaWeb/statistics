<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );

$TMSUsers = new TMSUsers();

$results       = $args[ 'results' ];
$total_pages   = $args[ 'total_pages' ];
$current_pages = $args[ 'current_pages' ];

$billing_info = $TMSUsers->check_user_role_access(array('administrator', 'billing', 'accounting'),true);
$hide_billing_and_shipping = $TMSUsers->check_user_role_access(array('billing', 'accounting'),true);

$helper = new TMSReportsHelper();

if ( ! empty( $results ) ) : ?>
    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Date booked</th>
            <th scope="col">Dispatcher</th>
            <th scope="col">Reference №</th>
            <th scope="col">Pick up location</th>
            <th scope="col">Delivery location</th>
            <th scope="col">Unit & name</th>
            <th scope="col">Booked rate</th>
            <th scope="col">Driver rate</th>
            <?php if (!$hide_billing_and_shipping): ?>
                <th scope="col">Profit</th>
            <?php endif; ?>
    
	    	<?php if ($billing_info): ?>
                <th scope="col">True Profit</th>
            <?php endif; ?>
            <th scope="col">Pick Up Date</th>
            <th scope="col">Load status</th>
	        <?php if (!$hide_billing_and_shipping): ?>
            <th scope="col">Instructions</th>
            <th scope="col">Source</th>
            <?php endif; ?>
	
	        <?php if ($billing_info): ?>
            <th scope="col">invoice</th>
            <th scope="col">factoring status</th>
	        <?php endif; ?>
			
            <?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                <th scope="col"></th>
			<?php endif; ?>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results as $row ) :
			// Получение значений из $row
			$meta = get_field_value( $row, 'meta_data' );

            // Обработка вложенных файлов
			$attached_files = get_field_value( $meta, 'attached_files' );
			$array_id_files = $attached_files ? explode( ',', $attached_files ) : false;
			$files_count    = is_array( $array_id_files ) ? '(' . sizeof( $array_id_files ) . ')' : '';
			$files_state    = empty( $files_count ) ? 'disabled' : '';

            // Обработка адресов доставки и забора
			$delivery_raw = get_field_value( $meta, 'delivery_location' );
			$delivery     = $delivery_raw ? json_decode( $delivery_raw, ARRAY_A ) : [];
			
			$pick_up_raw = get_field_value( $meta, 'pick_up_location' );
			$pick_up     = $pick_up_raw ? json_decode( $pick_up_raw, ARRAY_A ) : [];

            // Получение данных диспетчера
			$dispatcher_initials = get_field_value( $meta, 'dispatcher_initials' );
			$dispatcher          = $helper->get_user_full_name_by_id( $dispatcher_initials );
			$color_initials      = $dispatcher ? get_field( 'initials_color', 'user_' . $dispatcher_initials )
				: '#030303';
			if ( ! $dispatcher ) {
				$dispatcher = array( 'full_name' => 'User not found', 'initials' => 'NF' );
			}

            // Обработка статуса
			$load_status  = get_field_value( $meta, 'load_status' );
			$status_label = $helper->get_label_by_key( $load_status, 'statuses' );
			$status       = esc_html( $status_label );
			
			// Получение и форматирование остальных значений
			$date_booked_raw = get_field_value( $row, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
			
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
			
			$instructions_raw = get_field_value( $meta, 'instructions' );
			$instructions     = $helper->get_label_by_key( $instructions_raw, 'instructions' );
			
			$source_raw = get_field_value( $meta, 'source' );
			$source     = esc_html( $helper->get_label_by_key( $source_raw, 'sources' ) );
   
			$factoring_status_row = get_field_value( $meta, 'factoring_status' );
			$factoring_status     = esc_html( $helper->get_label_by_key( $factoring_status_row, 'factoring_status' ) );
			
            $invoice_raw = get_field_value( $meta, 'invoice' );
			$invoice     = esc_html( $helper->get_label_by_key( $invoice_raw, 'invoices' ) );
			
			?>

            <tr>
                <td><?php echo $date_booked; ?></td>
                <td>
            <span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo $dispatcher[ 'full_name' ]; ?>"
                  class="initials-circle" style="background-color: <?php echo $color_initials; ?>">
                  <?php echo esc_html( $dispatcher[ 'initials' ] ); ?>
            </span>
                </td>
                <td><?php echo $reference_number; ?></td>
                <td>
					<?php if ( is_array( $pick_up ) ): ?>
						<?php foreach ( $pick_up as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <span class="hide-long-text-100" data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="<?php echo $val[ 'address' ]; ?>">
                              <?php echo $val[ 'short_address' ]; ?>
                        </span>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td>
					<?php if ( is_array( $delivery ) ): ?>
						<?php foreach ( $delivery as $val ): ?>
							<?php if ( isset( $val[ 'short_address' ] ) ): ?>
                                <span class="hide-long-text-100" data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="<?php echo $val[ 'address' ]; ?>">
                              <?php echo $val[ 'short_address' ]; ?>
                        </span>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </td>
                <td><?php echo $unit_number_name; ?></td>
                <td><?php echo $booked_rate; ?></td>
                <td><?php echo $driver_rate; ?></td>

	            <?php if (!$hide_billing_and_shipping): ?>
                    <td><?php echo $profit; ?></td>
	            <?php endif; ?>
	            
	            <?php if ($billing_info): ?>
                    <td><?php echo $true_profit; ?></td>
	            <?php endif; ?>
                
                <td><?php echo $pick_up_date; ?></td>
                <td class="<?php echo strtolower( $status ); ?>"><?php echo $status; ?></td>
	            
	            <?php if (!$hide_billing_and_shipping): ?>
                    <td>
                        <div class="table-list-icons"><?php echo $instructions; ?></div>
                    </td>
                    <td><?php echo $source; ?></td>
	            <?php endif; ?>
	            
	            <?php if ($billing_info): ?>
                    <td><?php echo $invoice; ?></td>
                    <td><?php echo $factoring_status; ?></td>
	            <?php endif; ?>
             
             
             
             
				<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                    <td>
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
                                <li>
                                    <button class="dropdown-item text-danger" type="button">Delete</button>
                                </li>
                            </ul>
                        </div>
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