<?php
/**
 * General header
 *
 * @package WP-rock
 * @since 4.4.0
 */

global $global_options;
$login_link = get_field_value($global_options, 'link_to_login');

if(!is_user_logged_in()) {
	$current_url = home_url(add_query_arg(null, null));
	if (isset($login_link) && $login_link !== $current_url) {
		wp_redirect($login_link);
		exit;
	}
    die;
}

$page_class = '';
$page_id    = get_queried_object_id();


$user = get_userdata( get_current_user_id() );

$role = '';
if ( ! empty( $user->roles ) ) {
    $role = $user->roles[ 0 ];
}



$sidebar = get_field_value($global_options, 'sidebar_blocks');


if ( function_exists( 'get_field' ) ) {
    $page_class = ( get_field( 'body_class', $page_id ) ) ?: '';
}

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

?>

<pre>
Dispatcher - loads view/edit personal loads - read only in table / delete - only draft
Dispatcher team leader - loads view/edit personal loads - read only others loads (team) / delete - only draft
(Disparcher - group monitoring)

если меняется стоимость - отправить сообщение (тим лиду и админу и billing)
статус - canceled tonu delivered - оповестить (тим лиду и админу)
если меняет стопы - оповесстить (tracking)
Pick Up Date - если меняется то надо уведомить (tracking)
(Updated rate confirmation) - если меняется то надо уведомить (tracking, team-lead-group, admin, billing)
tracking - group
billing - полный доступ без редактирования - скачивание файлов

букрейт
драйвер рейт
юнит
статус
пик ап дейт
инструкции - Additional information алл

при замене водилы уведомить (тимлид админ трекинг)

<del>новое поле для аккаунтинг/админ/биллинг - true rate
booked-rait - 2% - driver-rait - формула расчета тру рейт</del>
<del>new field - 2% save in db
new field dispetch message - (screen picture) required</del>

для аккаунтинг / биллинг добавить 5 вкладку
Date booked	Dispatcher	Reference №	Pick up location
Delivery location	Unit & name	Booked rate	Driver rate	(Profit true)
Pick Up Date	Load status invoice (dropdown - - Invoiced

- Not invoiced
- Invoiced directly
 ) factoring status (dropdown - - Unsubmitted
- In Processing
- Requires Attention
- In Dispute
- Processed
- Charge Back
- Short Pay )
разделить на 3 офиса (юа / ремоут / польша)

</pre>

<body <?php body_class( $page_class ); ?>>

    <?php do_action( 'wp_rock_after_open_body_tag' ); ?>

    <div id="wrapper" class="wrapper">

        <?php do_action( 'wp_rock_before_site_header' ); ?>

        <?php echo esc_html( get_template_part( 'src/template-parts/custom', 'header' )); ?>

        <?php do_action( 'wp_rock_after_site_header' ); ?>

        <div id="main-wrapper">

        <?php if (is_array($sidebar)): ?>
        <div class="left-sidebar js-sidebar">
            <?php foreach ($sidebar as $key => $block):
                $first_link = false;
                ?>
                <?php if (is_array($block['menu'])):?>

                <div class="left-sidebar__block">
                    
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
                        $exclude = array_search($role, $menu['exclude_role']);
                         if (!is_numeric($exclude)) :
                         
                         if ($first_link === false) {
                             $first_link = $menu['link']['url'];
                         }
                         
                         $link_url = $menu['link']['url'];
                         $link_title = $menu['link']['title'];
                         
                         if ($menu['logout']) {
                             $link_url = wp_logout_url( !empty($login_link) ? $login_link : home_url() );
                         }
                         
                         ?>
                            <li class="left-sidebar__item">
                                <a class="left-sidebar__link <?php echo $current_page; ?>" href="<?php echo $link_url; ?>">
                                <?php echo $link_title; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </ul>
                     <a class="left-sidebar__btn small" href="<?php echo $first_link; ?>">
                        <?php if (!empty($block['icon'])): ?>
                        <img class="left-sidebar__icon" src="<?php echo $block['icon'] ?>" alt="icon">
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button class="toggle-sidebar js-toggle-sidebar">
                <svg class="left-sidebar__icon" width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21.97 15V9C21.97 4 19.97 2 14.97 2H8.96997C3.96997 2 1.96997 4 1.96997 9V15C1.96997 20 3.96997 22 8.96997 22H14.97C19.97 22 21.97 20 21.97 15Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7.96997 2V22" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14.97 9.43994L12.41 11.9999L14.97 14.5599" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <?php endif; ?>