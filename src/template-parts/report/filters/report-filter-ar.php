<?php
$helper    = new TMSReportsHelper();
$ar_status = $helper->get_ar_statuses();
$search    = get_field_value( $_GET, 'my_search' );
$status    = get_field_value( $_GET, 'status' );
$type      = get_field_value( $_GET, 'type' );

if ( ! $status ) {
	$status = 'not-solved';
}
?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#">Loads</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown"
                aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1"
              id="navbarNavDarkDropdownAr">
            <div class="d-flex gap-1">
                <input class="form-control w-auto" type="search" name="my_search" placeholder="Search"
                       value="<?php echo $search; ?>" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </div>
            <div class="d-flex gap-1">

                <select class="form-select w-auto" name="status" aria-label=".form-select-sm example">
                    <option <?php echo $status === 'all' ? 'selected' : ''; ?> value="all">All statuses</option>
					<?php
					foreach ( $ar_status as $key => $val ) {
						
						$select = $status === $key ? 'selected' : '';
						
						echo '<option ' . $select . ' value="' . $key . '">' . $val . '</option>';
					}
					?>
                </select>
				
				<?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
				<?php endif; ?>
            </div>
            
            <?php if ( $type ): ?>
                <input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
            <?php endif; ?>
        </form>
    </div>
</nav>
