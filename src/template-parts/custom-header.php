<?php
/**
 * Custom header template
 *
 * @package WP-rock
 */

global $global_options;
?>

<header id="site-header" class="site-header js-site-header">
    <div class="container-fluid">
        <div class="row justify-content-between align-items-center pt-2 pb-2">
            <div class="col main-menu js-main-menu order-2 d-flex gap-2 justify-content-end align-items-center">
<!--                --><?php
//                wp_nav_menu(
//                    array(
//                        'theme_location' => 'primary_menu',
//                        'menu_class' => 'main-menu__list list-reset d-flex gap-2 justify-content-end align-items-center js-menu-wrapper',
//                        'container' => 'nav',
//                        'container_class' => 'main-menu__container',
//                    )
//                );
//                ?>
                <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_add_company">Add broker</button>
                <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_add_shipper">Add shipper</button>
            </div>
            <div class="col-auto order-1">
               <H2>TMS Portal</H2>
            </div>
            <div class="col-auto d-lg-none order-2">
                <button type="button" class="menu-btn js-menu-btn" aria-label="Menu" title="Menu" data-role="menu-action"></button>
            </div>
        </div>
    </div>
</header>
