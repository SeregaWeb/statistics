<?php
$helper        = new TMSReportsHelper();
$dispatchers   = $helper->get_dispatchers();
$dispatcher_initials = get_field_value($_GET, 'dispatcher');
?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light" >
	<div class="container-fluid p-0">
		<a class="navbar-brand" href="#">Loads</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown" aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<form class="collapse navbar-collapse justify-content-end gap-3" id="navbarNavDarkDropdown">

            
            <select class="form-select" name="dispatcher" aria-label=".form-select-sm example">
                <option value="">Select dispatcher</option>
                <?php if (is_array($dispatchers)): ?>
                    <?php foreach ($dispatchers as $dispatcher):  ?>
                        <option value="<?php echo $dispatcher['id']; ?>" <?php echo strval($dispatcher_initials) === strval($dispatcher['id']) ? 'selected' : ''; ?> >
                            <?php echo $dispatcher['fullname']; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            
			<ul class="navbar-nav">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDarkDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						Months
					</a>
					<ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="navbarDarkDropdownMenuLink">
						<li><a class="dropdown-item" href="#">January</a></li>
						<li><a class="dropdown-item" href="#">February</a></li>
						<li><a class="dropdown-item" href="#">March</a></li>
						<li><a class="dropdown-item" href="#">April</a></li>
						<li><a class="dropdown-item" href="#">May</a></li>
						<li><a class="dropdown-item" href="#">June</a></li>
						<li><a class="dropdown-item" href="#">July</a></li>
						<li><a class="dropdown-item" href="#">August</a></li>
						<li><a class="dropdown-item" href="#">September</a></li>
						<li><a class="dropdown-item" href="#">October</a></li>
						<li><a class="dropdown-item" href="#">November</a></li>
						<li><a class="dropdown-item" href="#">December</a></li>
					</ul>
				</li>
			</ul>
			
			<ul class="navbar-nav">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDarkDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						Years
					</a>
					<ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="navbarDarkDropdownMenuLink">
						<li><a class="dropdown-item" href="#">2021</a></li>
						<li><a class="dropdown-item" href="#">2022</a></li>
						<li><a class="dropdown-item" href="#">2023</a></li>
					</ul>
				</li>
			</ul>
			
			<ul class="navbar-nav">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDarkDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						Dispatcher
					</a>
					<ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="navbarDarkDropdownMenuLink">
						<li><a class="dropdown-item" href="#">Daniel Dou</a></li>
						<li><a class="dropdown-item" href="#">Jana Sweet</a></li>
						<li><a class="dropdown-item" href="#">Alan Delone</a></li>
					</ul>
				</li>
			</ul>
			
			<form class="d-flex m-0">
				<input class="form-control me-1" type="search" placeholder="Search" aria-label="Search">
				<button class="btn btn-outline-dark" type="submit">Search</button>
			</form>
		</form>
	</div>
</nav>
