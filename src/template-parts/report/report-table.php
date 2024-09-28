<?php
global $global_options;

$add_new_load = get_field_value($global_options, 'add_new_load');

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
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $results as $row ) :
			$array_id_files = ! is_null( $row[ "attached_files" ] ) ? explode( ',', $row[ "attached_files" ] ) : false;
			$files_count = is_array( $array_id_files ) ? '(' . sizeof( $array_id_files ) . ')' : '';
			$files_state = $files_count === '' ? 'disabled' : '';
            
            $delivery = json_decode($row[ 'delivery_location' ], ARRAY_A);
            $pick_up = json_decode($row['pick_up_location'], ARRAY_A);
			
            $dispatcher = $helper->get_user_full_name_by_id($row[ 'dispatcher_initials' ] );
			$color_initials = '#030303';
            if (!$dispatcher) {
                $dispatcher = array('full_name' => 'User not found', 'initials' => 'NF');
            } else {
	            $color_initials = get_field('initials_color', 'user_'.$row[ 'dispatcher_initials' ]);
            }
            
			?>
            <tr>
                <td><?php echo esc_html( date( 'm/d/Y', strtotime( $row[ 'date_booked' ] ) ) ); ?></td>
                <td><span data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo $dispatcher['full_name']; ?>"  class="initials-circle" style="background-color: <?php echo $color_initials; ?>"><?php echo esc_html( $dispatcher['initials'] ); ?></span></td>
                <td><?php echo esc_html( $row[ 'reference_number' ] ); ?></td>
                <td> <?php if (is_array($pick_up)):
		                foreach ($pick_up as $val):
			                echo '<span class="hide-long-text-100" data-bs-toggle="tooltip" data-bs-placement="top" title="'.$val['address'].'">'.$val['address'].'</span>';
		                endforeach;
	                endif; ?></td>
                <td>
                    <?php if (is_array($delivery)):
                        foreach ($delivery as $val):
	                        echo '<span class="hide-long-text-100" data-bs-toggle="tooltip" data-bs-placement="top" title="'.$val['address'].'">'.$val['address'].'</span>';
                        endforeach;
                    endif; ?>
                </td>
                <td><?php echo esc_html( $row[ 'unit_number_name' ] ); ?></td>
                <td><?php echo esc_html( '$' . str_replace( '.00', '', $row[ 'booked_rate' ] ) ); ?></td>
                <td><?php echo esc_html( '$' . str_replace( '.00', '', $row[ 'driver_rate' ] ) ); ?></td>
                <td><?php echo esc_html( '$' . str_replace( '.00', '', $row[ 'profit' ] ) ); ?></td>
                <td><?php echo esc_html( date( 'm/d/Y', strtotime( $row[ 'pick_up_date' ] ) ) ); ?></td>
                <td><?php echo esc_html( $helper->get_label_by_key( $row[ 'load_status' ], 'statuses' ) ); ?></td>
                <td>
                    <div class="table-list-icons"><?php echo $helper->get_label_by_key( $row[ 'instructions' ], 'instructions' ); ?></div>
                </td>
                <td><?php echo esc_html( $helper->get_label_by_key( $row[ 'source' ], 'sources' ) ); ?></td>
                <td>
                    <div class="dropdown">
                        <button class="btn button-action" type="button" id="dropdownMenu2"
                                data-bs-toggle="dropdown" aria-expanded="false">
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
                            <li>
                                <a href="<?php echo $add_new_load . '?post_id=' . $row['id']; ?>" class="dropdown-item" type="button">Edit</a>
                            </li>
                            <li>
                                <button class="dropdown-item text-danger" type="button">Delete</button>
                            </li>
                        </ul>
                    </div>
                </td>
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