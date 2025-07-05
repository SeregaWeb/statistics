<?php
/**
 * Custom post type {cpt_title}
 *
 * @package WP-rock
 * @since   4.4.0
 */

$cpt_title      = 'something';
$cpt_category   = 'something';
$cpt_slug       = 'something';

// TODO: Don't forget to change {CPT_CATEGORY} and {cpt_title} and {CPT_SLUG}.
$labels = array(
    'name'                  => _x( $cpt_title, 'Post type general name', 'wp-rock' ),
    'singular_name'         => _x( $cpt_title, 'Post type singular name', 'wp-rock' ),
    'menu_name'             => _x( $cpt_title, 'Admin Menu text', 'wp-rock' ),
    'name_admin_bar'        => _x( $cpt_title, 'Add New on Toolbar', 'wp-rock' ),
    'add_new'               => __( 'Add New', 'wp-rock' ),
    'add_new_item'          => __( 'Add New' . $cpt_title, 'wp-rock' ),
    'new_item'              => __( 'New ' . $cpt_title, 'wp-rock' ),
    'edit_item'             => __( 'Edit ' . $cpt_title, 'wp-rock' ),
    'view_item'             => __( 'View ' . $cpt_title, 'wp-rock' ),
    'all_items'             => __( 'All ' . $cpt_title, 'wp-rock' ),
    'search_items'          => __( 'Search ' . $cpt_title, 'wp-rock' ),
    'parent_item_colon'     => __( 'Parent ' . $cpt_title, 'wp-rock' ),
    'not_found'             => __( 'No ' . $cpt_title . ' found.', 'wp-rock' ),
    'not_found_in_trash'    => __( 'No ' . $cpt_title . ' found in Trash.', 'wp-rock' ),
    'featured_image'        => _x( $cpt_title . ' Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'wp-rock' ),
    'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'wp-rock' ),
    'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'wp-rock' ),
    'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'wp-rock' ),
    'archives'              => _x( $cpt_title . ' archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'wp-rock' ),
);
$args = array(
    'labels'                => $labels,
    'public'                => true,
    'publicly_queryable'    => true,
    'show_ui'               => true,
    'show_in_menu'          => true,
    'query_var'             => true,
    'rewrite'               => array( 'slug' => $cpt_slug ),
    'capability_type'       => 'post',
    'show_in_rest'          => true,
    'has_archive'           => true,
    'hierarchical'          => false,
    'menu_position'         => null,
    'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
    'taxonomies'            => array( $cpt_category ),
);

register_post_type( $cpt_slug, $args );
