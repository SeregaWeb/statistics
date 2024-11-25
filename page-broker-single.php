<?php
/**
 * Template Name: Page single broker
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


$brokers   = new TMSReportsCompany();
$id_broker = get_field_value( $_GET, 'broker_id' );
$broker    = $brokers->get_company_by_id( $id_broker, ARRAY_A );

if ( isset( $broker[ 0 ] ) ) {
	$broker = $broker[ 0 ];
}

$full_address = $broker[ 'address1' ] . ' ' . $broker[ 'city' ] . ' ' . $broker[ 'state' ] . ' ' . $broker[ 'zip_code' ] . ' ' . $broker[ 'country' ];

$platform = $brokers->get_label_by_key( $broker[ 'set_up_platform' ], 'set_up_platform' );

// Декодируем JSON в ассоциативный массив
$set_up_array = json_decode( $broker[ 'set_up' ], true );
$set_up_array_complete = json_decode( $broker[ 'date_set_up_compleat' ], true );

// Фильтруем ключи с значением "completed"
$completed_keys = array_keys( array_filter( $set_up_array, function( $value ) {
	return $value === "completed";
} ) );

$completed_dated_keys = array_keys( array_filter( $set_up_array, function( $value ) {
	return !is_null($value);
} ) );

// Преобразуем массив ключей в строку через запятую
$completed_keys_string = implode( ', ', $completed_keys );


?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 mt-3 mb-3">
						<?php
						$user_id          = $broker[ 'user_id_added' ];
						$user_name        = $brokers->get_user_full_name_by_id( $user_id );
						$date_created     = $broker[ 'date_created' ];
						$last_user_id     = $broker[ 'user_id_updated' ];
						$user_update_name = $brokers->get_user_full_name_by_id( $last_user_id );
						$date_updated     = $broker[ 'date_updated' ];
						
						$date_created = esc_html( date( 'm/d/Y', strtotime( $date_created ) ) );
						$date_updated = esc_html( date( 'm/d/Y', strtotime( $date_updated ) ) );
						?>
                        <h2><?php echo $broker[ 'company_name' ] ?></h2>
                        <ul class="status-list">
                            <li class="status-list__item">
                                <span class="status-list__label">Address:</span>
                                <span class="status-list__value"><?php echo $full_address; ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">MС number:</span>
                                <span class="status-list__value"><?php echo $broker[ 'mc_number' ] ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">DOT number:</span>
                                <span class="status-list__value"><?php echo $broker[ 'dot_number' ] ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">Contact name:</span>
                                <span class="status-list__value"><?php echo $broker[ 'contact_first_name' ] . ' ' . $broker[ 'contact_last_name' ] ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">Contact phone:</span>
                                <span class="status-list__value"><?php echo $broker[ 'phone_number' ] ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">Contact email:</span>
                                <span class="status-list__value"><?php echo $broker[ 'email' ] ?></span>
                            </li>

                            <li class="status-list__item">
                                <span class="status-list__label">Set-up platform:</span>
                                <span class="status-list__value"><?php echo $brokers->get_label_by_key($broker[ 'set_up_platform' ], 'set_up_platform') ; ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">In set-up with:</span>
                                <span class="status-list__value"><?php echo $completed_keys_string; ?></span>
                            </li>
                            <li class="status-list__item">
                                <span class="status-list__label">Completed date:</span>
                                <span class="status-list__value d-flex flex-column">
                                    <?php foreach ($completed_dated_keys as $key):
	                                    $date_set = esc_html( date( 'm/d/Y', strtotime( $set_up_array_complete[$key] ) ) );
                                        ?>
                                        <span><?php echo $key . ': ' . $date_set ?></span>
                                    <?php endforeach; ?>
                                </span>
                            </li>

                            <li class="status-list__item">
                                <span class="status-list__label">Profile created:</span>
                                <span class="status-list__value"><?php echo $user_name[ 'full_name' ];
									echo ' ' . $date_created; ?></span>
                            </li>
							<?php if ( $broker[ 'date_created' ] !== $broker[ 'date_updated' ] ) { ?>
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
