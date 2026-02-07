<?php
$helper        = new TMSReportsHelper();
$driver_helper = new TMSDriversHelper();
$recruiters    = $helper->get_recruiters();
$sources       = $driver_helper->source;

$search_only = isset( $args['search_only'] ) ? $args['search_only'] : false;

$title = isset( $args['title'] ) ? $args['title'] : 'Drivers';

$search             = get_field_value( $_GET, 'my_search' );
$recruiter_initials = trim( get_field_value( $_GET, 'recruiter' ) ?? '' );
$source             = trim( get_field_value( $_GET, 'source' ) ?? '' );



?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#"><?php echo $title; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown"
                aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1"
              id="navbarNavDriver">
            <div class="d-flex gap-1">
                <input class="form-control w-auto" type="search" name="my_search" placeholder="Search"
                       value="<?php echo $search; ?>" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </div>
			<?php if ( ! $search_only ): ?>
            <div class="d-flex gap-1">
				


                <select class="form-select w-auto" name="recruiter" aria-label=".form-select-sm example">
                    <option value="">Recruiter</option>
					<?php if ( is_array( $recruiters ) ): ?>
						<?php foreach ( $recruiters as $recruiter ): ?>
                            <option value="<?php echo $recruiter[ 'id' ]; ?>" <?php echo strval( $recruiter_initials ) === strval( $recruiter[ 'id' ] )
								? 'selected' : ''; ?> >
								<?php echo $recruiter[ 'fullname' ]; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>


                <select class="form-select w-auto" name="source" aria-label=".form-select-sm example">
                    <option value="">Source</option>
					<?php if ( is_array( $sources ) ): ?>
						<?php foreach ( $sources as $key => $val ): ?>
                            <option value="<?php echo $key; ?>" <?php echo $source === $key ? 'selected' : '' ?> >
								<?php echo $val; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>

				<?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
				<?php endif; ?>
            </div>
		  <?php endif; ?>
        </form>
    </div>
</nav>
