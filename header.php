<?php
/**
 * General header
 *
 * @package WP-rock
 * @since 4.4.0
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />

    <?php
    // TODO: Change for fonts that you need. Please uncomment bottom code line if you want you use Google Fonts
    // $fonts_google = 'https://fonts.googleapis.com/css2?family=Neuton:wght@300&family=Schibsted+Grotesk:wght@400;500;600;700&display=swap';
    ?>
    <!-- connect to domain of font files -->
    <!--<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>-->
    <!-- optionally increase loading priority -->
    <!--
    <link rel="preload" as="style" href=<?php /*echo $fonts_google; */?> />
    <link rel="stylesheet" media="print" onload="this.onload=null;this.removeAttribute('media');"
          href="<?php //echo $fonts_google; ?>" />
    -->
    <?php if ( is_404() ) { ?>
        <meta name="robots" content="noindex, nofollow" />
    <?php } ?>
    <?php wp_head(); ?>
    <?php do_action( 'wp_rock_before_close_head_tag' ); ?>
</head>

<?php
global $global_options;
$page_class = '';
$page_id    = get_queried_object_id();

if ( function_exists( 'get_field' ) ) {
    $page_class = ( get_field( 'body_class', $page_id ) ) ?: '';
}
?>

<body <?php body_class( $page_class ); ?>>

    <?php do_action( 'wp_rock_after_open_body_tag' ); ?>

    <div id="wrapper" class="wrapper">

        <?php do_action( 'wp_rock_before_site_header' ); ?>

        <?php echo esc_html( get_template_part( 'src/template-parts/custom', 'header' ) ); ?>

        <?php do_action( 'wp_rock_after_site_header' ); ?>

        <div id="main-wrapper">
