<?php
$helper        = new TMSReportsHelper();
$setup_platform = $helper->get_set_up_platform();
$search = get_field_value($_GET, 'my_search');


?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light" >
	<div class="container-fluid p-0">
		<a class="navbar-brand" href="#">Filter</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown" aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1" id="navbarNavDarkDropdownPlatform">
			<div class="d-flex gap-1">
				<input class="form-control w-auto" type="search" name="my_search" placeholder="Search" value="<?php echo $search; ?>" aria-label="Search">
				<button class="btn btn-outline-dark" type="submit">Search</button>
				<?php if (!empty($_GET)): ?>
                    <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
				<?php endif; ?>
			</div>
		</form>
	</div>
</nav>
