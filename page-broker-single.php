<?php
/**
 * Template Name: Page single broker
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();


$brokers   = new TMSReportsCompany();
$loads = new TMSReports();


$id_broker = get_field_value( $_GET, 'broker_id' );
$broker    = $brokers->get_company_by_id( $id_broker, ARRAY_A );
$broker_meta = $brokers->get_all_meta_by_post_id($id_broker);

if ( ! empty( $broker ) ) {
	
	$get_counters_broker = $loads->get_counters_broker($id_broker);
	if ( isset( $broker[ 0 ] ) ) {
		$broker = $broker[ 0 ];
	}
	
	$full_address = $broker[ 'address1' ] . ' ' . $broker[ 'city' ] . ' ' . $broker[ 'state' ] . ' ' . $broker[ 'zip_code' ] . ' ' . $broker[ 'country' ];
	
	$platform = $brokers->get_label_by_key( $broker[ 'set_up_platform' ], 'set_up_platform' );

// Декодируем JSON в ассоциативный массив
	$set_up_array          = json_decode( $broker[ 'set_up' ], true );
	$set_up_array_complete = json_decode( $broker[ 'date_set_up_compleat' ], true );
	
	$completed_keys       = array();
	$completed_dated_keys = array();
	
	if ( is_array( $set_up_array ) ) {
		$completed_keys = array_keys( array_filter( $set_up_array, function( $value ) {
			return $value === "completed";
		} ) );
		
		$completed_dated_keys = array_keys( array_filter( $set_up_array, function( $value ) {
			return ! is_null( $value );
		} ) );
	}
	
	$properties = [];
	if (!empty($broker_meta['work_with_odysseia'])) {
		$properties[] = 'Odysseia';
	}
	if (!empty($broker_meta['work_with_endurance'])) {
		$properties[] = 'Endurance';
	}
	if (!empty($broker_meta['work_with_martlet'])) {
		$properties[] = "Martlet";
	}
 
 
// Преобразуем массив ключей в строку через запятую
	$completed_keys_string = implode( ', ', $completed_keys );
}

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <?php if ( ! empty( $broker ) ) { ?>
                        <div class="col-12 mt-2">
                            <ul class="nav nav-pills mb-2" id="pills-tab" role="tablist">
                                <li class="nav-item w-25" role="presentation">
                                    <button class="nav-link w-100 active" id="pills-info-tab" data-bs-toggle="pill" data-bs-target="#pills-info" type="button" role="tab" aria-controls="pills-info" aria-selected="true">Info</button>
                                </li>
                                <li class="nav-item w-25" role="presentation">
                                    <button class="nav-link w-100" id="pills-update-tab" data-bs-toggle="pill" data-bs-target="#pills-update" type="button" role="tab" aria-controls="pills-update" aria-selected="false">Edit</button>
                                </li>
                                
                            </ul>
                        </div>
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-info" role="tabpanel" aria-labelledby="pills-info-tab">
                                <div class="mt-3 mb-3">
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
                                            <span class="status-list__value"><?php echo $brokers->get_label_by_key( $broker[ 'set_up_platform' ], 'set_up_platform' ); ?></span>
                                        </li>
                                        <li class="status-list__item">
                                            <span class="status-list__label">In set-up with:</span>
                                            <span class="status-list__value"><?php echo $completed_keys_string; ?></span>
                                        </li>
                                        <li class="status-list__item">
                                            <span class="status-list__label">Notes:</span>
                                            <span class="status-list__value"><?php echo $broker_meta[ 'notes' ] ?? ''; ?></span>
                                        </li>

                                        <li class="status-list__item">
                                            <span class="status-list__label">Work with:</span>
                                            <span class="status-list__value"><?php echo implode(', ', $properties); ?></span>
                                        </li>

                                        <li class="status-list__item">
                                            <span class="status-list__label">Completed date:</span>
                                            <span class="status-list__value d-flex flex-column">
                                    <?php foreach ( $completed_dated_keys as $key ):
	                                    $date_set = esc_html( date( 'm/d/Y', strtotime( $set_up_array_complete[ $key ] ) ) );
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

                                <div class="mt-3 mb-3">
		                            <?php
		                            ?>
                                    <div class="counters-status d-flex gap-2">
			                            <?php if (is_array($get_counters_broker)): ?>
				                            <?php foreach ($get_counters_broker as $key => $count): ?>
					                            <?php if (+$count !== 0): ?>
                                                    <div class="counters-status-card d-flex align-items-center justify-content-center flex-column">
                                                        <span><?php echo $key === 'Others' ? 'In Process' : $key; ?></span>
                                                        <strong><?php echo $count; ?></strong>
                                                    </div>
					                            <?php endif; ?>
				                            <?php endforeach; ?>
			                            <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-update" role="tabpanel" aria-labelledby="pills-update-tab">
                            
                            </div>
                           
                        </div>
                        
                   
                    <?php } else { ?>
                        <div class="col-12">
                            <h2 class="mt-3 mb-2">Empty</h2>
                        </div>
                    <?php } ?>
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
