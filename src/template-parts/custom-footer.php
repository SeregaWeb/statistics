<?php
/**
 * Custom footer template
 *
 * @package WP-rock
 */

global $global_options;

$copyright = get_field_value( $global_options, 'copyright' );
$TMSUsers  = new TMSUsers();

$add_broker  = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'billing'
), true );
$add_shipper = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'tracking', 
	'morning_tracking',	
	'nightshift_tracking',
), true );

$rating_access = $TMSUsers->check_user_role_access( array(
	'administrator',
	'dispatcher',
	'dispatcher-tl',
	'tracking',
	'tracking-tl',
	'morning_tracking',
	'nightshift_tracking',
	'expedite_manager',
), true );

if ( $add_broker ):
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/report', 'popup-add-company' ) );
endif;

if ( $add_shipper ):
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/report', 'popup-add-shipper' ) );
endif;

echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/report', 'popup-add-contact' ) );
echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/report', 'popup-quick-edit' ) );
echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/report', 'popup-quick-edit-ar' ) );

if ( $rating_access ) {
	echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/load', 'rating', array( 'rating_access' => $rating_access ) ) );
}
?>

<div class="message-container js-show-info-message"></div>

<footer id="site-footer" class="site-footer">
    <div class="container site-footer__container">
        <div class="site-footer__wrapper">

        </div>
    </div>
</footer>
