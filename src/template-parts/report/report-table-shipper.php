<?php

global $global_options;

$helper = new TMSReportsHelper();

$link_shipper = get_field_value($global_options, 'single_page_shipper');
$results       = get_field_value( $args, 'results' );
$total_pages   = get_field_value( $args, 'total_pages' );
$current_pages = get_field_value( $args, 'current_pages' );

if ( ! empty( $results ) ) :
	?>
	
	<table class="table mb-5 w-100">
		<thead>
		<tr>
			<th scope="col">Shipper Name</th>
			<th scope="col">Address</th>
			<th scope="col">Contact</th>
			<th scope="col">Email</th>
			<th scope="col">Phone</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $results as $row ) :
			$full_address =  $row['address1'] . ' ' .  $row['city'] . ' ' . $row['state'] . ' ' . $row['zip_code'] . ' ' . $row['country'] ;
			
			?>
			<tr>
				<td><a  href="<?php echo $link_shipper . '?shipper_id=' . $row['id']; ?>"><?php echo esc_html( $row['shipper_name'] ); ?></a></td>
				<td style="width: 260px;"><?php echo esc_html( $full_address ); ?></td>
				<td>
                    <span>
                        <?php echo esc_html( $row['contact_first_name'] ); ?>
                        <?php echo esc_html( $row['contact_last_name'] ); ?>
                    </span>
				</td>
                <td><?php echo esc_html( $row['email'] ); ?></td>
                <td><?php echo esc_html( $row['phone_number'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	
	<?php
	
	echo esc_html( get_template_part( 'src/template-parts/report/report', 'pagination', array(
		'total_pages'  => $total_pages,
		'current_page' => $current_pages,
	) ) );

else : ?>
	<p>No reports found.</p>
<?php endif; ?>