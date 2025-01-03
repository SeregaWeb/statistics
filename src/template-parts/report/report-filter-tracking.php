<?php
$helper        = new TMSReportsHelper();
$statuses = $helper->get_statuses();

$search = get_field_value($_GET, 'my_search');
$load_status = get_field_value($_GET, 'load_status');

?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light" >
	<div class="container-fluid p-0">
		<a class="navbar-brand" href="#">Loads</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown" aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1" id="navbarNavDarkDropdown">
			<div class="d-flex gap-1">
				<input class="form-control w-auto" type="search" name="my_search" placeholder="Search" value="<?php echo $search; ?>" aria-label="Search">
				<button class="btn btn-outline-dark" type="submit">Search</button>
			</div>
            <div class="d-flex gap-1">

                <select class="form-select w-auto" name="load_status" aria-label=".form-select-sm example">
                    <option value="">Load status</option>
					<?php if (is_array($statuses)): ?>
						<?php foreach ($statuses as $key => $val):  ?>
                            <option value="<?php echo $key; ?>" <?php echo $load_status === $key ? 'selected' : '' ?> >
								<?php echo $val; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>

                <?php if (!empty($_GET)): ?>
                <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
                <?php endif; ?>
            </div>
		</form>
	</div>
</nav>
