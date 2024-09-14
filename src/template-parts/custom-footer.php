<?php
/**
 * Custom footer template
 *
 * @package WP-rock
 */

global $global_options;

$copyright = get_field_value( $global_options, 'copyright' );

echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add-company' ) );
echo esc_html( get_template_part( 'src/template-parts/report/report', 'popup-add-shipper' ) );

?>

<div class="message-container js-show-info-message"></div>

<footer id="site-footer" class="site-footer">
    <div class="container site-footer__container">
        <div class="site-footer__wrapper">
            <div class="site-footer__copyright">
                © <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <lb><?php echo wp_kses_post( $copyright ); ?>
            </div>
        </div>
    </div>
</footer>
