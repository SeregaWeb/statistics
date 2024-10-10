<?php
global $global_options;

$add_new_load = get_field_value( $global_options, 'add_new_load' );

$TMSUsers = new TMSUsers();

$results       = $args[ 'results' ];
$total_pages   = $args[ 'total_pages' ];
$current_pages = $args[ 'current_pages' ];

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
            <th scope="col">Profit</th>
            <th scope="col">Pick Up Date</th>
            <th scope="col">Load status</th>
            <th scope="col">Instructions</th>
            <th scope="col">Source</th>
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
			$date_booked_raw = get_field_value( $meta, 'date_booked' );
			$date_booked     = esc_html( date( 'm/d/Y', strtotime( $date_booked_raw ) ) );
			
			$reference_number = esc_html( get_field_value( $meta, 'reference_number' ) );
			$unit_number_name = esc_html( get_field_value( $meta, 'unit_number_name' ) );
			
			$booked_rate_raw = get_field_value( $meta, 'booked_rate' );
			$booked_rate     = esc_html( '$' . str_replace( '.00', '', $booked_rate_raw ) );
			
			$driver_rate_raw = get_field_value( $meta, 'driver_rate' );
			$driver_rate     = esc_html( '$' . str_replace( '.00', '', $driver_rate_raw ) );
			
			$profit_raw = get_field_value( $meta, 'profit' );
			$profit     = esc_html( '$' . str_replace( '.00', '', $profit_raw ) );
			
			$pick_up_date_raw = get_field_value( $meta, 'pick_up_date' );
			$pick_up_date     = esc_html( date( 'm/d/Y', strtotime( $pick_up_date_raw ) ) );
			
			$instructions_raw = get_field_value( $meta, 'instructions' );
			$instructions     = $helper->get_label_by_key( $instructions_raw, 'instructions' );
			
			$source_raw = get_field_value( $meta, 'source' );
			$source     = esc_html( $helper->get_label_by_key( $source_raw, 'sources' ) );
			
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
                <td><?php echo $profit; ?></td>
                <td><?php echo $pick_up_date; ?></td>
                <td class="<?php echo strtolower( $status ); ?>"><?php echo $status; ?></td>
                <td>
                    <div class="table-list-icons"><?php echo $instructions; ?></div>
                </td>
                <td><?php echo $source; ?></td>
				<?php if ( $TMSUsers->check_user_role_access( array( 'recruiter' ) ) ): ?>
                    <td>
                        <div class="dropdown">
                            <button class="btn button-action" type="button" id="dropdownMenu2" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <!-- SVG Icon -->
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