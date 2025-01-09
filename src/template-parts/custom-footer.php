<?php
/**
 * Custom footer template
 *
 * @package WP-rock
 */

global $global_options;

$copyright = get_field_value( $global_options, 'copyright' );
$TMSUsers = new TMSUsers();

$add_broker = $TMSUsers->check_user_role_access( array( 'dispatcher-tl', 'administrator', 'billing' ), true );
$add_shipper = $TMSUsers->check_user_role_access( array( 'dispatcher', 'dispatcher-tl', 'administrator', 'tracking' ), true );

if ($add_broker):
echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add-company' ) );
endif;

if ($add_shipper):
echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add-shipper' ) );
endif;
echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-quick-edit' ) );
echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-quick-edit-ar' ) );

?>

<div class="message-container js-show-info-message"></div>

<footer id="site-footer" class="site-footer">
    <div class="container site-footer__container">
        <div class="site-footer__wrapper">

        </div>
    </div>
</footer>
