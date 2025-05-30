<?php
/**
 * Template Name: Page Login
 *
 * @package WP-rock
 * @since 4.4.0
 */

if ( is_user_logged_in()) {
    wp_redirect( home_url() );
    die;
}

get_header('noauth');

?>
    <div class="container">
        <div class="row h-100 align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-12 col-md-4 offset-md-4 ">
                        <form class="js-login-form">
                            <h2 class="mb-2">Login</h2>
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Email address</label>
                                <input type="email" name="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com">
                            </div>
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com">
                            </div>

                            <div class="mb-3 d-none js-hide-field">
                                <label for="exampleFormControlInput1" class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" id="exampleFormControlInput1" placeholder="12345">
                            </div>
                            
                            <div class="mb-3 d-flex gap-1">
                                <button type="submit" class="btn btn-success js-login-btn d-none">Login</button>
                                <button class="btn btn-primary js-send-code">Send code</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
