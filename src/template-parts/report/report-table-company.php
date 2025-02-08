<?php

global $global_options;

$helper = new TMSReportsHelper();
$report = new TMSReports();

$link_broker  = get_field_value( $global_options, 'single_page_broker' );
$results      = get_field_value( $args, 'results' );
$total_pages  = get_field_value( $args, 'total_pages' );
$current_page = get_field_value( $args, 'current_page' );

if ( ! empty( $results ) ) : ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">Company Name</th>
            <th scope="col">Address</th>
            <th scope="col">Contacts</th>
            <th scope="col">MC</th>
            <th scope="col">Factoring status</th>
            <th scope="col">Set up platform</th>
            <th scope="col">Set up</th>
            <th scope="col">Gross</th>
            <th scope="col">Profit</th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results as $row ) :
			
			$profit = $report->get_profit_and_gross_by_brocker_id( $row[ 'id' ] );
//            var_dump($profit);
			
			$full_address = $row[ 'address1' ] . ' ' . $row[ 'city' ] . ' ' . $row[ 'state' ] . ' ' . $row[ 'zip_code' ] . ' ' . $row[ 'country' ];
			
			$platform         = $helper->get_label_by_key( $row[ 'set_up_platform' ], 'set_up_platform' );
			$meta             = get_field_value( $row, 'meta_fields' );
			$factoring_status = get_field_value( $meta, 'factoring_broker' );
			// Декодируем JSON в ассоциативный массив
			$set_up_array = json_decode( $row[ 'set_up' ], true );
			
			// Фильтруем ключи с значением "completed"
			$completed_keys = array_keys( array_filter( $set_up_array, function( $value ) {
				return $value === "completed";
			} ) );
			
			// Преобразуем массив ключей в строку через запятую
			$completed_keys_string = implode( ', ', $completed_keys );
			
			?>
            <tr class="broker-<?php echo $factoring_status; ?>">
                <td>
                    <a href="<?php echo $link_broker . '?broker_id=' . $row[ 'id' ]; ?>"><?php echo esc_html( $row[ 'company_name' ] ); ?></a>
                </td>
                <td style="width: 260px;"><?php echo esc_html( $full_address ); ?></td>
                <td>
                    <div class="d-flex flex-column">
                        <span>
                            <?php echo esc_html( $row[ 'contact_first_name' ] ); ?>
                            <?php echo esc_html( $row[ 'contact_last_name' ] ); ?>
                        </span>
                        <span class="text-small"><?php echo esc_html( $row[ 'phone_number' ] ); ?></span>
                        <span class="text-small"><?php echo esc_html( $row[ 'email' ] ); ?></span>
                    </div>
                </td>
                <td><?php echo esc_html( $row[ 'mc_number' ] ); ?></td>
                <td>
					<?php
					if ( isset( $factoring_status ) ) {
						echo isset( $helper->factoring_broker[ $factoring_status ] )
							? $helper->factoring_broker[ $factoring_status ] : '';
					}
					?>
                </td>
                <td><?php echo esc_html( $platform ); ?></td>
                <td><?php echo esc_html( $completed_keys_string ); ?></td>
                <td><?php echo $profit[ 'booked_rate_total' ] === 0 ? '' : $profit[ 'booked_rate_total' ]; ?></td>
                <td><?php echo $profit[ 'profit_total' ] === 0 ? '' : $profit[ 'profit_total' ]; ?></td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
	
	<?php
	
	echo esc_html( get_template_part( 'src/template-parts/report/report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_page,
	) ) );

else : ?>
    <p>No reports found.</p>
<?php endif; ?>