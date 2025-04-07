<?php

$helper = new TMSReportsHelper();

$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );
$is_draft      = get_field_value( $args, 'is_draft' );

if ( ! empty( $results ) ) : ?>

    <table class="table mb-5 w-100">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Driver_name</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php
		
		foreach ( $results as $row ) :
			$meta = get_field_value( $row, 'meta_data' );
			$driver_name = get_field_value( $meta, 'driver_name' );
			$driver_email = get_field_value( $meta, 'driver_email' );
			$driver_phone = get_field_value( $meta, 'driver_phone' );
			?>

            <tr>
                <td><?php echo $row[ 'id' ]; ?></td>
                <td><?php echo $driver_name; ?></td>
                <td><?php echo $driver_email; ?></td>
                <td><?php echo $driver_phone; ?></td>
                <td>
                    <div class="d-flex">
						<?php echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/control', 'dropdown-driver', [
							'id'       => $row[ 'id' ],
							'is_draft' => $is_draft,
						] ) ); ?>
                    </div>
                </td>
            </tr> <?php endforeach; ?>

        </tbody>
    </table>
	
	<?php
	
	
	echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );
	?>

<?php else : ?>
    <p>No loads found.</p>
<?php endif; ?>