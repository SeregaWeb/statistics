<?php
$helper        = new TMSReportsHelper();
$dispatchers   = $helper->get_dispatchers();
$statuses = $helper->get_statuses();
$sources = $helper->get_sources();
$dispatcher_initials = get_field_value($_GET, 'dispatcher');
$search = get_field_value($_GET, 'my_search');
$month = get_field_value($_GET, 'fmonth');
$year_param = get_field_value($_GET, 'fyear');
$load_status = get_field_value($_GET, 'load_status');
$source = get_field_value($_GET, 'source');
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
				<?php
				$months = array(
					1  => 'January',
					2  => 'February',
					3  => 'March',
					4  => 'April',
					5  => 'May',
					6  => 'June',
					7  => 'July',
					8  => 'August',
					9  => 'September',
					10 => 'October',
					11 => 'November',
					12 => 'December',
				);
				?>
                <select class="form-select w-auto" name="fmonth" aria-label=".form-select-sm example">
                    <option value="">Month</option>
					<?php
					foreach ( $months as $num => $name ) {
						
						$select = is_numeric($month) && +$month === +$num ? 'selected' : '' ;
						
						echo '<option '.$select.' value="' . $num . '">' . $name . '</option>';
					}
					?>
                </select>
				
				<?php $current_year = date('Y'); ?>
                <select class="form-select w-auto" name="fyear" aria-label=".form-select-sm example">
                    <option value="">Year</option>
					<?php
					
					for ( $year = 2023; $year <= $current_year; $year++ ) {
						$select = is_numeric($year_param) &&  +$year_param === +$year ? 'selected' : '' ;
						echo '<option '.$select.' value="' . $year . '">' . $year . '</option>';
					}
					?>
                </select>

                <select class="form-select w-auto" name="dispatcher" aria-label=".form-select-sm example">
                    <option value="">Dispatcher</option>
					<?php if (is_array($dispatchers)): ?>
						<?php foreach ($dispatchers as $dispatcher):  ?>
                            <option value="<?php echo $dispatcher['id']; ?>" <?php echo strval($dispatcher_initials) === strval($dispatcher['id']) ? 'selected' : ''; ?> >
								<?php echo $dispatcher['fullname']; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>

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

                <select class="form-select w-auto" name="source" aria-label=".form-select-sm example">
                    <option value="">Source</option>
						<?php if (is_array($sources)): ?>
							<?php foreach ($sources as $key => $val):  ?>
                                <option value="<?php echo $key; ?>"  <?php echo $source === $key ? 'selected' : '' ?> >
									<?php echo $val; ?>
                                </option>
							<?php endforeach; ?>
					    <?php endif; ?>
                </select>
            </div>
		</form>
	</div>
</nav>
