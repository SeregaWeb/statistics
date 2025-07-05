<?php
/**
 * General template for 404 page
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

do_action( 'wp_rock_before_page_content' );
?>
<section class="section-404">
    <div class="section-404__content">

        <h1 class="section-404__heading">
            <?php esc_html_e( "Oops! We can't seem to find the page you're looking for.", 'wp-rock' ); ?>
        </h1>

        <a class="button section-404__btn"
           href="<?php echo esc_attr( get_home_url() ); ?>">
            <?php esc_html_e( 'Back to home', 'wr-rock' ); ?>
        </a>
    </div>
</section>

<?php do_action( 'wp_rock_after_page_content' ); ?>
<?php wp_footer(); ?>
