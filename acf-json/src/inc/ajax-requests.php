<?php
/**
 * Different Ajax requests
 *
 * @package WP-rock/ajax_requests
 */

$wp_rock = new WP_Rock();

$wp_rock->ajax_front_to_backend( 'more_blog_posts', 'more_blog_posts' );

/**
 * Get more blog posts.
 *
 * @return void
 */
function more_blog_posts() {
    $per_page = filter_input( INPUT_POST, 'per_page', FILTER_SANITIZE_STRING );
    $offset   = filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_STRING );
    $cat_id   = filter_input( INPUT_POST, 'cat_id', FILTER_SANITIZE_STRING );
    $records_type   = filter_input( INPUT_POST, 'records_type', FILTER_SANITIZE_STRING );

    // Check if mandatory parameters are set.
    if ( false === $per_page || false === $offset ) {
        wp_send_json_error( array( 'message' => 'Invalid input parameters.' ) );
        die();
    }

    $args = array(
        'post_type'      => $records_type,
        'post_status'    => 'publish',
        'posts_per_page' => $per_page ?: get_option( 'posts_per_page' ),
        'offset'         => $offset,
    );

    // If cat_id is set, and it's not 'all', add it to the query.
    if ( $cat_id && 'all' !== $cat_id ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => ( $records_type ) === 'news' ? 'news-category' : 'category',
                'field'    => 'term_id',
                'terms'    => $cat_id,
            ),
        );
    }

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        ob_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            get_template_part( 'src/template-parts/template', 'small-posts', array( 'records_type' => $records_type ) );
        }
        $output = ob_get_clean();
        wp_reset_postdata();
        wp_send_json_success( $output );
    } else {
        wp_send_json_error( array( 'message' => 'Posts not found.' ) );
    }

    die();
}
