<?php
/**
 * Block - Example.
 *
 * @package WP-rock
 * @since   4.4.0
 */

$class_name = isset($args['className']) ? ' ' . $args['className'] : '';
$fields      = get_fields();
$title = get_field_value( $fields, 'title' );
?>

<section class="block-example my-6 py-4 my-lg-3 py-lg-2<?php echo esc_html($class_name); ?>" id="<?php echo $args['id']; ?>">
    <div class="container text-center">
        <h1 class="h3 h2-md h1-lg color-brand mb-2 mb-lg-4"><?php echo esc_html($title);?></h1>
    </div>
</section>
