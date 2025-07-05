<?php
/**
 * Base settings that can be used by any site.
 *
 * @package WP-rock
 * @since 4.4.0
 */

/**
 * Theme class used different useful settings
 */
class WP_Rock {


    /**
     * Produces cleaner FILE NAMES for uploads.
     *
     * @param {string} $filename - Name of file.
     *
     * @return string - sanitized filename
     */
    public function custom_sanitize_file_name( $filename ) {
        $sanitized_filename = remove_accents( $filename ); // Convert to ASCII.

        // Standard replacements.
        $invalid            = array(
            ' ' => '-',
            '%20' => '-',
            '_' => '-',
        );
        $sanitized_filename = str_replace( array_keys( $invalid ), array_values( $invalid ), $sanitized_filename );

        // Remove all non-alphanumeric except.
        $sanitized_filename = preg_replace( '/[^A-Za-z0-9-\. ]/', '', $sanitized_filename );
        // Remove all but last.
        $sanitized_filename = preg_replace( '/\.(?=.*\.)/', '', $sanitized_filename );
        // Replace any more than one - in a row.
        $sanitized_filename = preg_replace( '/-+/', '-', $sanitized_filename );
        // Remove last - if at the end.
        $sanitized_filename = str_replace( '-.', '.', $sanitized_filename );
        // Lowercase.
        $sanitized_filename = strtolower( $sanitized_filename );

        return $sanitized_filename;
    }

    /**
     * Custom body class depends on device.
     *
     * @param {array} $classes - existing classes.
     *
     * @return array of classes
     */
    public function stag_body_class( $classes ) {
        global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;

        global $browser_detect;
        $browser_detect = '';

        if ( $is_lynx ) {
            $browser_detect = 'lynx';
        } elseif ( $is_gecko ) {
            $browser_detect = 'gecko';
        } elseif ( $is_opera ) {
            $browser_detect = 'opera';
        } elseif ( $is_NS4 ) {
            $browser_detect = 'ns4';
        } elseif ( $is_safari ) {
            $browser_detect = 'safari';
        } elseif ( $is_chrome ) {
            $browser_detect = 'chrome';
        } elseif ( $is_IE ) {
            $http_user_agent = filter_input( 'server', 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
            $browser         = substr( "$http_user_agent", 25, 8 );

            if ( 'MSIE 7.0' === $browser ) {
                $browser_detect = 'ie7';
                $browser_detect .= ' ie';
            } elseif ( 'MSIE 6.0' === $browser ) {
                $browser_detect = 'ie6';
                $browser_detect .= ' ie';
            } elseif ( 'MSIE 8.0' === $browser ) {
                $browser_detect = 'ie8';
                $browser_detect .= ' ie';
            } elseif ( 'MSIE 9.0' === $browser ) {
                $browser_detect = 'ie9';
                $browser_detect .= ' ie';
            } elseif ( 'MSIE 11.0' === $browser ) {
                $browser_detect = 'ie11';
                $browser_detect .= ' ie';
            } else {
                $browser_detect = 'ie';
            }
        } else {
            $browser_detect = ' unknown';
        }

        if ( $is_iphone ) {
            $browser_detect .= ' iphone mobile ';
        }

        $classes[] = $browser_detect;

        return $classes;
    }

    /**
     * Remove version from source in url.
     *
     * @param {string} $src - src before changes.
     *
     * @return string - src without ?ver
     */
    public function px_remove_version_data( $src ) {
        $parts = explode( '?ver', $src );
        return $parts[0];
    }

    /**
     * Add a screen-reader-text` class to the search form's submit button.
     *
     * @param {string} $html - Search form HTML.
     *
     * @return string Modified search form HTML.
     */
    public function px_search_form_modify( $html ) {
        return str_replace(
            'class="search-submit"',
            'class="search-submit screen-reader-text"',
            $html
        );
    }

    /**
     * JavaScript Detection.
     * Adds a `js` class to the root `<html>` element when JavaScript is detected.
     *
     * @return void
     */
    public function px_javascript_detection() {
         echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
    }

    /**
     * The excerpt based on words.
     *
     * @param {string} $string     - Initial string.
     * @param {number} $word_limit - numbers of words that should be cropped.
     *
     * @return string  -  cropped string
     */
    public function px_string_limit_words( $string, $word_limit ) {
        $words = explode( ' ', $string, ( $word_limit + 1 ) );

        if ( count( $words ) > $word_limit ) {
            array_pop( $words );
        }

        return implode( ' ', $words ) . '<span class="excert_arrow"></span>';

    }

    /**
     * The excerpt based on character.
     *
     * @param {string} $excerpt - string that should be cropped.
     * @param {number} $substr  - letters limit.
     *
     * @return bool|string
     */
    public function px_string_limit_char( $excerpt, $substr = 0 ) {
        $string = strip_tags( str_replace( '...', '...', $excerpt ) );

        if ( $substr > 0 ) {
            $string = substr( $string, 0, $substr );
        }

        return $string;

    }

    /**
     * Set custom upload size limit.
     *
     * @param {number} $value - number for set up as new file upload limit.
     * @return void
     */
    public function px_custom_upload_size_limit( $value ) {
        global $upload_size_limit;
        $upload_size_limit = $value;

        @ini_set( 'upload_max_size', $value . 'M' );
        @ini_set( 'post_max_size', $value . 'M' );

        set_time_limit( 300 );

        add_filter(
            'upload_size_limit',
            function ( $new_limit ) use ( $upload_size_limit ) {
                return $new_limit * 1048576; // megabytes.
            }
        );
    }

    /**
     * Check if page is blog page.
     *
     * @return bool
     */
    public function is_blog() {
         global $post;
        $posttype = get_post_type( $post );

        return (
            'post' === ( is_archive() || is_author() || is_category() || is_home() || is_single() || is_tag() ) && $posttype )
            ? true : false;
    }

    /**
     * Find YouTube Link in string and Convert it into embed code.
     *
     * @param {string} $youtube_url - Youtube url.
     * @return string  Embed url
     */
    public function px_get_youtube_embed_url( $youtube_url ) {
        $short_url_regex = '/youtu.be\/([a-zA-Z0-9_]+)\??/i';
        $long_url_regex  = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))(\w+)/i';

        if ( preg_match( $long_url_regex, $youtube_url, $matches ) ) {
            $youtube_id = $matches[ count( $matches ) - 1 ];
        }

        if ( preg_match( $short_url_regex, $youtube_url, $matches ) ) {
            $youtube_id = $matches[ count( $matches ) - 1 ];
        }
        return 'https://www.youtube.com/embed/' . $youtube_id;
    }

    /**
     * Find YouTube preview image.
     *
     * @param {string} $youtube_url  - Youtube url.
     * @return string  Image url
     */
    public function get_youtube_iframe_data( $youtube_url ) {
        $pattern =
            '%^# Match any youtube URL
                    (?:https?://)?  # Optional scheme. Either http or https
                    (?:www\.)?      # Optional www subdomain
                    (?:             # Group host alternatives
                      youtu\.be/    # Either youtu.be,
                    | youtube\.com  # or youtube.com
                      (?:           # Group path alternatives
                        /embed/     # Either /embed/
                      | /v/         # or /v/
                      | /watch\?v=  # or /watch\?v=
                      )             # End path alternatives.
                    )               # End host alternatives.
                    ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
                    $%x';
        $result  = preg_match( $pattern, $youtube_url, $matches );

        if ( $result ) {
            $video_id = $matches[1];  // get youTube video ID from URL.
            // getting iframe image preview.
            return esc_html( "https://img.youtube.com/vi/$video_id/0.jpg" );
        }

        return null;
    }

    /**
     * Override custom logo filter
     *
     * @return string
     */
    public function custom_logo() {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $html = sprintf(
            '<a href="%1$s" class="custom-logo-link" rel="home" itemprop="url">%2$s</a>',
            esc_url( home_url( '/' ) ),
            wp_get_attachment_image(
                $custom_logo_id,
                'full',
                false,
                array(
                    'class' => 'custom-logo',
                )
            )
        );
        return $html;
    }

    /**
     * Ajax request from Front end to Backend.
     *
     * @param {string}   $action_name - action name.
     * @param {function} $callback_function - handler function.
     * @param {boolean}  $is_private - check if we need to process this ajax request only for logged-in users.
     *
     * @return void  - Send data back to frontend
     */
    public function ajax_front_to_backend( $action_name, $callback_function, $is_private = false ) {
        if ( ! $is_private ) {
            add_action( 'wp_ajax_nopriv_' . $action_name, $callback_function );
        }

        add_action( 'wp_ajax_' . $action_name, $callback_function );
    }

    /**
     * Main site setting setup.
     *
     * @return void|string
     */
    public function px_site_setup() {
        /*
         * Make theme available for translation.
         * Translations can be filed in the /languages/ directory.
         * If you're building a theme based on wp-rock, use a find and replace
         * to change 'wp-rock' to the name of your theme in all the template files.
         */
        load_theme_textdomain( 'wp-rock', get_template_directory() . '/languages' );

        // remove some unused things.
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
        remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
        remove_action( 'wp_head', 'rel_canonical' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'feed_links', 2 );
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'wp_resource_hints', 2 );
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );

        // File Size notification in admin area.
        add_action(
            'post-plupload-upload-ui',
            function () {
                echo esc_html( '<p>To compress files to the desired size you can use the service <a href="http://optimizilla.com/">http://optimizilla.com/</a</p>' );
            },
            10,
            1
        );

        /**
         * Add new image type to allowed list of types.
         *
         * @param {array} $upload_mimes - upload_mimes types.
         *
         * @return mixed
         */
        function add_svg_to_upload_mimes( $upload_mimes ) {
            $upload_mimes['svg']  = 'image/svg+xml';
            $upload_mimes['svgz'] = 'image/svg+xml';
            return $upload_mimes;
        }

        add_filter( 'upload_mimes', 'add_svg_to_upload_mimes', 10, 1 );

        // Specify which menu location will be used in theme.
        register_nav_menus(
            array(
                'primary_menu' => __( 'Primary Menu', 'wp-rock' ),
                'footer_menu'  => __( 'Footer Menu', 'wp-rock' ),
                'mobile_menu'  => __( 'Mobile menu', 'wp-rock' ),
            )
        );

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            )
        );

        // Declaring Woocommerce support for our theme.
        add_theme_support(
            'woocommerce',
            array(
                'thumbnail_image_width' => 150,
                'single_image_width' => 300,
                'product_grid' => array(
                    'default_rows' => 3,
                    'min_rows' => 2,
                    'max_rows' => 8,
                    'default_columns' => 4,
                    'min_columns' => 2,
                    'max_columns' => 5,
                ),
            )
        );

        // force remove "Archive" word from title.
        $this->px_remove_archive_word_from_title();

        // force remove cycling links.
        $this->px_no_link_current_page();

        add_post_type_support( 'page', 'excerpt' );

        // Add title tag support.
        add_theme_support( 'title-tag' );

        // Add default posts and comments RSS feed links to head.
        add_theme_support( 'automatic-feed-links' );

        // Enable support for Post Thumbnails on posts and pages.
        add_theme_support( 'post-thumbnails' );

        // Add shortcode support in text widgets.
        add_filter( 'widget_text', 'do_shortcode' );

        // DETECTING BROWSER = Add custom body class to the head.
        add_filter( 'body_class', array( $this, 'stag_body_class' ) );

        // Add a screen-reader-text` class to the search form's submit button.
        add_filter( 'get_search_form', array( $this, 'px_search_form_modify' ) );

        // Set  Revisions Config to Zero.
        add_filter( 'wp_revisions_to_keep', '__return_zero' );

        // remove SEO noodp meta tag.
        add_filter( 'wpseo_robots', '__return_empty_string', 999 );

        // JavaScript Detection.
        add_action( 'wp_head', array( $this, 'px_javascript_detection' ) );

        // Produces cleaner FILE NAMES for uploads.
        add_filter( 'sanitize_file_name', array( $this, 'custom_sanitize_file_name' ), 10, 1 );

        // get Custom Logo.
        add_filter( 'get_custom_logo', array( $this, 'custom_logo' ) );


        // some styles in admin area
        add_action( 'admin_head', 'my_custom_fonts' );
        function my_custom_fonts() {
            echo '<style>
            .acf-repeater .acf-row > td {
			    border-top: 10px solid #e6e6e6 !important;
                border-bottom: 10px solid #e6e6e6 !important;
			}
			.acf-repeater .acf-row-handle.order {
                color: #000;
                font-size: 24px;
            }
            
	        .acf-block-body .acf-block-fields {
			    border: #5a93ea solid 3px;
			}

            .mce-menu .mce-menu-item-normal.mce-active,
            .mce-menu .mce-menu-item-preview.mce-active,
            .mce-menu .mce-menu-item.mce-selected,
            .mce-menu .mce-menu-item:focus,
            .mce-menu .mce-menu-item:hover {
                background: #94d2f0 !important;
            }
            .wp-block {
			    max-width: 1280px;
			}
			.wp-block-image .components-resizable-box__container {
				overflow: hidden;
			}
			
			.is-selected .acf-block-fields.acf-fields {
                border-color: #6de8a0;
            }
          </style>';
        }
    }



    /**
     * Remove "Archive" word from title.
     *
     * @return void
     */
    public function px_remove_archive_word_from_title() {
        add_filter(
            'get_the_archive_title',
            function ( $title ) {
                if ( is_category() ) {
                    $title = single_cat_title( '', false );
                } elseif ( is_tag() ) {
                    $title = single_tag_title( '', false );
                } elseif ( is_post_type_archive() ) {
                    $title = post_type_archive_title( '', false );
                } elseif ( is_author() ) {
                    $title = '<span class="vcard"></span>';
                }

                return $title;
            }
        );
    }

    /**
     * Removing cycling links from menu.
     *
     * @param {string} $p - HTML menu item string.
     *
     * @return array|string|string[]|null
     */
    public function remove_cycling( $p ) {
        return preg_replace( '%((current_page_item|current-menu-item)[^<]+)[^>]+>([^<]+)</a>%', '$1<span class="like-link nav__link">$3</span>', $p, 1 );
    }

    /**
     * Removing cycling links from menu.
     *
     * @return void
     */
    private function px_no_link_current_page() {
        // Remove cycling links from menu.
        add_filter( 'wp_nav_menu', array( $this, 'remove_cycling' ) );
        add_filter( 'wp_list_categories', array( $this, 'remove_cycling' ) );
    }
}

