<?php
/**
 * Add Dropcap option but keep the defaults.
 *
 * @package tiny_mce/customization
 */

add_filter( 'tiny_mce_before_init', 'my_wpeditor_formats_options' );

/**
 * Customizing WP Editor formats options.
 *
 * @param {array} $settings  -  Existing settings.
 * @return mixed
 */
function my_wpeditor_formats_options( $settings ) {
    /* Set to true to include the default settings. */
    $settings['style_formats_merge'] = true;

    $style_formats = array(
        array(
            'title' => 'BodyM',
            'block' => 'p',
            'classes' => 'bodyM',
        ),
        array(
            'title' => 'Body Small',
            'block' => 'p',
            'classes' => 'bodySmall',
        ),
        array(
            'title' => 'SubtitleM Bold',
            'block' => 'p',
            'classes' => 'subtitleMBold',
        ),
    );

    $settings['style_formats'] = json_encode( $style_formats );

    return $settings;
}

