<?php
/**
 * Custom hooks
 *
 * @package WP-rock/custom-hooks
 */

/**
 * Remove windows LSEP from content
 *
 * @param { string } $html - Text content.
 *
 * @return array|string|string[]
 */
function remove_lsep( $html ) {
    $pattern = '/\x{2028}/u';

    return preg_replace( $pattern, '', $html );
}


/**
 * Remove windows LSEP from content
 *
 * @param {string} $content - Text content.
 * @return string|string[]
 */
function remove_windows_lsep_from_content( $content ) {
    return str_replace( "\r\n", '', $content );
}
add_filter( 'the_content', 'remove_windows_lsep_from_content' );



/**
 * Change display type for language switcher in Frontend
 */
add_filter(
    'pll_the_languages_args',
    function( $args ) {
        $args['display_names_as'] = 'slug';
        return $args;
    }
);



/**
 * Remove tag <p> и <br> in plugin contact form.
 */
add_filter( 'wpcf7_autop_or_not', '__return_false' );



/**
 * Add Formats to TinyMCE
 * - https://developer.wordpress.org/reference/hooks/tiny_mce_before_init/
 * - https://codex.wordpress.org/Plugin_API/Filter_Reference/tiny_mce_before_init
 *
 * @param array $args - Arguments used to initialize the tinyMCE
 *
 * @return array $args  - Modified arguments
 */
function prefix_tinymce_toolbar($args ): array
{

    $args['fontsize_formats'] = "8px 9px 10px 11px 12px 13px 14px 15px 16px 17px 18px 19px 20px 21px 22px 23px 24px 25px 26px 27px 28px 29px 30px 31px 32px 33px 34px 35px 36px 37px 38px 39px 40px 41px 42px 43px 44px 45px 46px 47px 48px 49px 50px 51px 52px 53px 54px 55px 56px 57px 58px 59px 60px 61px 62px 63px 64px 65px 66px 67px 68px 69px 70px 71px 72px 73px 74px 75px 76px 77px 78px 79px 80px 81px 82px 83px 84px 85px 86px 87px 88px 89px 90px 91px 92px 93px 94px 95px 96px 97px 98px 99px 100px";

    return $args;

}
add_filter( 'tiny_mce_before_init', 'prefix_tinymce_toolbar' );


function my_mce_buttons_2($buttons) {
    array_unshift($buttons, 'fontsizeselect');
    return $buttons;
}
add_filter('mce_buttons_2', 'my_mce_buttons_2');