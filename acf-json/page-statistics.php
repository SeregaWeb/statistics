<?php
/**
 * Template Name: Page statistics
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$active_item = get_field_value( $_GET, 'active_state' ) ?: 'finance';

$tabs = [
	'finance' => 'Total',
	'yearly'  => 'Statistics',
	'goal'    => 'Monthly goal',
	'top'     => 'Charts',
	'source'  => 'Source',
];

if ( ! in_array( $active_item, array_keys( $tabs ), true ) ) {
	$active_item = 'finance';
}

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">

                    <div class="col-12 pt-4 pb-4">
                        <ul class="nav nav-pills nav-fill gap-2 justify-content-center">
							<?php foreach ( $tabs as $state => $label ): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $active_item === $state ? 'active' : '' ?>"
                                       href="<?php echo add_query_arg( 'active_state', $state, get_the_permalink() ); ?>">
										<?php echo $label; ?>
                                    </a>
                                </li>
							<?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="col-12 d-flex flex-wrap">
						<?php if ( array_key_exists( $active_item, $tabs ) ): ?>
							<?php get_template_part( TEMPLATE_PATH . 'statistics/' . $active_item ); ?>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
