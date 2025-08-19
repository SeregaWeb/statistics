<?php
$title             = $args[ 'title' ];
$users             = $args[ 'users' ];
$dispatchers_users = $args[ 'dispatchers_users' ];
$weekends          = isset( $users[ 'weekends' ] ) ? $users[ 'weekends' ] : [];


$exclude_users  = get_field( 'exclude_users_for_move', get_the_ID() );
$users_tracking = $users[ 'tracking_move' ];

// Оставляем только тех, чьи id не в $exclude_users
$filtered = array_filter( $users_tracking, function( $user ) use ( $exclude_users ) {
	// id может быть строкой, приводим к int для сравнения
	return ! in_array( (int) $user[ 'id' ], $exclude_users, true );
} );

// Если нужен обычный индексированный массив:
$filtered = array_values( $filtered );

$weekends_add      = array_unique( array_merge( $filtered, $weekends ), SORT_REGULAR );
$weekends_filtered = array_filter( $weekends_add, function( $user ) use ( $exclude_users ) {
	return ! in_array( (int) $user[ 'id' ], $exclude_users, true );
} );

?>


<div id="popup_move_driver" class="popup js-move-dispatcher-popup">
    <div class="my_overlay js-popup-close"></div>
    <div class="popup__wrapper-inner ">
        <div class="popup-container ">
            <button class="popup-close js-popup-close">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M31.6666 10.6833L29.3166 8.33331L20 17.65L10.6833 8.33331L8.33331 10.6833L17.65  20L8.33331 29.3166L10.6833 31.6666L20 22.35L29.3166 31.6666L31.6666 29.3166L22.35 20L31.6666 10.6833Z"
                          fill="black"/>
                </svg>
            </button>
			<?php if ( isset( $filtered ) && is_array( $filtered ) ) : ?>
                <form class="tracking-statistics__move js-move-dispatcher-form">

                    <h2 class="custom-upload__title"><?php echo $title; ?></h2>
                    <div class="d-flex gap-1">
                        <div class="tracking-statistics__move-container w-50">
                            <h4>From</h4>
                            <div class="d-flex gap-1 flex-column dispatcher-section">
								<?php foreach ( $weekends_filtered as $user ) : ?>
                                    <label class="dispatcher-option">
                                        <input type="radio" name="move-from"
                                               value="<?php echo esc_attr( $user[ 'id' ] ); ?>"
                                               data-team="<?php echo esc_attr( json_encode( isset( $user[ 'my_team_without_weekends' ] )
											       ? $user[ 'my_team_without_weekends' ] : array() ) ); ?>">
                                        <span><?php echo esc_html( $user[ 'name' ] ); ?></span>
                                    </label>
								<?php endforeach; ?>
                            </div>
                        </div>
                        <div class="tracking-statistics__move-container w-50">
                            <h4>To</h4>
                            <div class="d-flex gap-1 flex-column dispatcher-section">
								<?php foreach ( $filtered as $user ) : ?>
                                    <label class="dispatcher-option">
                                        <input type="radio" name="move-to"
                                               value="<?php echo esc_attr( $user[ 'id' ] ); ?>">
                                        <span><?php echo esc_html( $user[ 'name' ] ); ?></span>
                                    </label>
								<?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="tracking-statistics__move-container">
                        <h4>Dispatcher</h4>
                        <div class="d-flex gap-1 flex-column" id="dispatcher-container">
							<?php foreach ( $dispatchers_users as $user ) : ?>
                                <div class="dispatcher-section" style="display: none;">
                                    <label class="dispatcher-option"
                                           data-user-id="<?php echo esc_attr( $user[ 'id' ] ); ?>">
                                        <input type="checkbox" name="dispatcher[]"
                                               value="<?php echo esc_attr( $user[ 'id' ] ); ?>">
										<?php echo esc_html( $user[ 'fullname' ] ); ?>
                                    </label>
                                    <div class="weekend-exclusions mt-2" style="display: none;">
                                        <h6 class="mb-2">Exclude <?php echo esc_html( $user[ 'fullname' ] ); ?> for certain
                                            days: </h6>
										<?php
										$days              = array(
											'monday'    => 'Monday',
											'tuesday'   => 'Tuesday',
											'wednesday' => 'Wednesday',
											'thursday'  => 'Thursday',
											'friday'    => 'Friday',
											'saturday'  => 'Saturday',
											'sunday'    => 'Sunday'
										);
										$current_user_id   = $user[ 'id' ];
                                        $user[ 'id' ] = 'user_' . $current_user_id;
										
										echo '<div class="d-flex gap-2 flex-wrap">';
										foreach ( $days as $day_key => $day_name ) :
											$exclude_field = 'exclude_' . $day_key;
											$exclude_value = get_field( $exclude_field, 'user_' . $user[ 'id' ] );
											$is_checked    = is_array( $exclude_value ) && in_array( $current_user_id, array_map( 'strval', $exclude_value ) );
											?>
                                            <div class="day-exclusion mb-1">
                                                <label>
                                                    <input type="checkbox"
                                                           name="exclude_<?php echo esc_attr( $day_key ); ?>_<?php echo esc_attr( $user[ 'id' ] ); ?>"
                                                           value="1"
														<?php checked( $is_checked, true ); ?>
                                                           class="exclude-checkbox"
                                                           data-user-id="<?php echo esc_attr( $user[ 'id' ] ); ?>"
                                                           data-day="<?php echo esc_attr( $day_key ); ?>">
													<?php echo esc_html( $day_name ); ?>
                                                </label>
                                            </div>
										<?php endforeach;
										
										echo '</div>';
										?>
                                    </div>
                                </div>
							<?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <button id="save-all-weekends-btn" type="button" class="btn btn-primary">Move</button>
                    </div>
                </form>
			<?php endif; ?>
        </div>
    </div>
</div>

