<?php
/**
 * Template Name: Page contacts
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports     = new TMSReports();
$TMSUsers    = new TMSUsers();
$TMSContacts = new TMSContacts();

$data = $TMSContacts->get_all_contacts();

$results       = get_field_value( $data, 'data' );
$total_pages   = get_field_value( $data[ 'pagination' ], 'total_pages' );
$current_pages = get_field_value( $data[ 'pagination' ], 'current_pages' );
?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-start gap-4 align-items-center mb-4">
                            <h2 class="mb-2 mt-3">Contacts</h2>
                            <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_contacts"

                            >Add a contact
                            </button>
                        </div>

                        <table class="table mb-5 w-100">
                            <thead>
                            <tr>
                                <th>Company name</th>
                                <th>Address</th>
                                <th>Name</th>
                                <th>Office number</th>
                                <th>Direct number</th>
                                <th>Email</th>
                                <th>Number of loads</th>
                                <th>Profit</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php if ( $results ):
								foreach ( $results as $item ):?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="id"
                                                   value="<?php echo esc_attr( $item[ 'id' ] ); ?>">
											<?php echo esc_html( $item[ 'company_name' ] ); ?>
                                        </td>
                                        <td><?php echo esc_html( $item[ 'city' ] ); ?>,
											<?php echo esc_html( $item[ 'state' ] ); ?>
											<?php echo esc_html( $item[ 'zip_code' ] ); ?></td>
                                        <td>
											<?php echo $item[ 'name' ]; ?>
                                        </td>
                                        <td><?php echo $item[ 'office_number' ]; ?></td>
                                        <td><?php echo $item[ 'direct_number' ]; ?></td>
                                        <td><?php echo $item[ 'email' ]; ?></td>
                                        <td>0</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
								<?php endforeach;
							endif; ?>
                            </tbody>
                        </table>
						<?php
						
						echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
							'total_pages'  => $total_pages,
							'current_page' => $current_pages,
						) ) );
						
						?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
