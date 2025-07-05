<?php
/**
 * Taxonomy news category
 *
 * @package WP-rock
 * @since 4.4.0
 */

$cpt_category       = 'something';
$cpt_categories     = 'something';
$cpt_slug           = 'something';
$cpt_category_slug  = 'something';

// TODO: Don't forget to change {CPT_CATEGORY} and  {CPT_CATEGORIES} and {CPT_SLUG} and {CPT_CATEGORY_SLUG}.
$labels = array(
    'name'              => __( $cpt_category, 'wp-rock' ),
    'singular_name'     => __( $cpt_category, 'wp-rock' ),
    'search_items'      => __( 'Search for ' . $cpt_categories, 'wp-rock' ),
    'all_items'         => __( 'All ' . $cpt_categories, 'wp-rock' ),
    'parent_item'       => __( 'Parent ' . $cpt_categories, 'wp-rock' ),
    'parent_item_colon' => __( 'Parent ' . $cpt_category, 'wp-rock' ),
    'edit_item'         => __( 'Edit ' . $cpt_category, 'wp-rock' ),
    'update_item'       => __( 'Update ' . $cpt_category, 'wp-rock' ),
    'add_new_item'      => __( 'Add New ' . $cpt_category, 'wp-rock' ),
    'new_item_name'     => __( 'New ' . $cpt_category, 'wp-rock' ),
);

register_taxonomy(
    $cpt_category_slug,
    array( $cpt_slug ),
    array(
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => $cpt_category_slug ),
    )
);
