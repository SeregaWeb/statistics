<?php
/**
 * Template Name: Page shipper single
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


$shipper   = new TMSReportsShipper();
$id_broker = get_field_value( $_GET, 'shipper_id' );
$shipper_fields    = $shipper->get_shipper_by_id( $id_broker, ARRAY_A );

if ( isset( $shipper_fields[ 0 ] ) ) {
	$shipper_fields = $shipper_fields[ 0 ];
}

$full_address = $shipper_fields[ 'address1' ] . ' ' . $shipper_fields[ 'city' ] . ' ' . $shipper_fields[ 'state' ] . ' ' . $shipper_fields[ 'zip_code' ] . ' ' . $shipper_fields[ 'country' ];

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mt-3 mb-3">
						<?php
						$user_id          = $shipper_fields[ 'user_id_added' ];
						$user_name        = $shipper->get_user_full_name_by_id( $user_id );
						$date_created     = $shipper_fields[ 'date_created' ];
						$last_user_id     = $shipper_fields[ 'user_id_updated' ];
						$user_update_name = $shipper->get_user_full_name_by_id( $last_user_id );
						$date_updated     = $shipper_fields[ 'date_updated' ];
						
						$date_created = esc_html( date( 'm/d/Y', strtotime( $date_created ) ) );
						$date_updated = esc_html( date( 'm/d/Y', strtotime( $date_updated ) ) );
						?>
                        <h2><?php echo $shipper_fields[ 'shipper_name' ] ?></h2>
                        <ul class="status-list">
                            <li class="status-list__item">
                                <span class="status-list__label">Address:</span>
                                <span class="status-list__value"><?php echo $full_address; ?></span>
                            </li>
                           
                            <li class="status-list__item">
                                <span class="status-list__label">Contact name:</span>
                                <span class="status-list__value"><?php echo $shipper_fields[ 'contact_first_name' ] . ' ' . $shipper_fields[ 'contact_last_name' ] ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">Contact phone:</span>
                                <span class="status-list__value"><?php echo $shipper_fields[ 'phone_number' ] ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">Contact email:</span>
                                <span class="status-list__value"><?php echo $shipper_fields[ 'email' ] ?></span>
                            </li>

                            <li class="status-list__item">
                                <span class="status-list__label">Profile created:</span>
                                <span class="status-list__value"><?php echo $user_name[ 'full_name' ];
									echo ' ' . $date_created; ?></span>
                            </li>
							<?php if ( $shipper_fields[ 'date_created' ] !== $shipper_fields[ 'date_updated' ] ) { ?>
                                <li class="status-list__item">
                                    <span class="status-list__label">Profile updated:</span>
                                    <span class="status-list__value"><?php echo $user_update_name[ 'full_name' ];
										echo ' ' . $date_updated; ?></span>
                                </li>
							<?php } ?>
                        </ul>

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

echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add' ) );

get_footer();
