<?php
/**
 * Template Name: Page contacts
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports  = new TMSReports();
$TMSUsers = new TMSUsers();

$bookmarks = $TMSUsers->get_all_bookmarks();

$args = array(
	'status_post' => 'publish',
);

$items = $reports->get_favorites( $bookmarks, $args );

$items[ 'hide_total' ] = true;

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
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            </tbody>
                        </table>
						<?php
						if ( $TMSUsers->check_user_role_access( array( 'billing', 'accounting' ), true ) ) {
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-accounting', $items ) );
						} else if ( $TMSUsers->check_user_role_access( array( 'tracking' ), true ) ) {
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table-tracking', $items ) );
						} else {
							echo esc_html( get_template_part( TEMPLATE_PATH . 'tables/report', 'table', $items ) );
						}
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
