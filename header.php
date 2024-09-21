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

$sidebar = get_field_value($global_options, 'sidebar_blocks');


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

        <?php if (is_array($sidebar)): ?>
        <div class="left-sidebar">
            <?php foreach ($sidebar as $key => $block): ?>
                <?php if (is_array($block['menu'])):?>

                <div class="left-sidebar__block">
                    
                    <a class="left-sidebar__btn small" href="<?php echo $block['menu'][0]['link']['url']; ?>">
                        <?php if (!empty($block['icon'])): ?>
                        <img class="left-sidebar__icon" src="<?php echo $block['icon'] ?>" alt="icon">
                        <?php endif; ?>
                    </a>
                    
                    <a class="left-sidebar__btn full js-toggle active" data-block-toggle="js-menu-block_<?php echo $key; ?>" href="<?php echo $block['menu'][0]['link']['url']; ?>">
                        <?php if (!empty($block['icon'])): ?>
                        <img class="left-sidebar__icon" src="<?php echo $block['icon'] ?>" alt="icon">
                        <?php endif; ?>
                        <?php echo $block['label_block']; ?>
                    </a>
                    <ul class="left-sidebar__menu js-menu-block_<?php echo $key; ?>">
                    <?php
                     foreach ($block['menu'] as $menu):
                     $current_page = get_the_permalink() === $menu['link']['url'] ? 'current-page' : '';
                     ?>
                        <li class="left-sidebar__item">
                            <a class="left-sidebar__link <?php echo $current_page; ?>" href="<?php echo $menu['link']['url']; ?>">
                            <?php echo $menu['link']['title']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>