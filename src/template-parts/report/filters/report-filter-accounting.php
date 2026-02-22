<?php
$helper              = new TMSReportsHelper();
$TMSUsers            = new TMSUsers();
$dispatchers         = $helper->get_dispatchers();
$statuses            = $helper->get_statuses();
$sources             = $helper->get_sources();
$invoices            = $helper->get_invoices();
$factoring_statuses  = $helper->get_factoring_status();
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );
$search              = get_field_value( $_GET, 'my_search' );
$month               = get_field_value( $_GET, 'fmonth' );
$year_param          = get_field_value( $_GET, 'fyear' );
$load_status         = get_field_value( $_GET, 'load_status' );
$source              = get_field_value( $_GET, 'source' );
$factoring           = get_field_value( $_GET, 'factoring' );
$invoice             = get_field_value( $_GET, 'invoice' );
$office              = get_field_value( $_GET, 'office' );
$type                = get_field_value( $_GET, 'type' );

$post_tp = get_field_value( $args, 'post_type' );
$offices = $helper->get_offices_from_acf();

$show_filter_by_office = $TMSUsers->check_user_role_access( array(
	'dispatcher-tl',
	'expedite_manager',
	'administrator',
	'recruiter'
), true );

?>

<nav class="navbar navbar-sticky-custom mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#">Loads</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown"
                aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1"
              id="navbarNavDarkDropdown">
            <div class="d-flex gap-1">
                <input class="form-control w-auto" type="search" name="my_search" placeholder="Search"
                       value="<?php echo $search; ?>" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </div>
            <div class="d-flex gap-1">
				<?php
				$months = $helper->get_months();
				?>
                <select class="form-select w-auto" name="fmonth" aria-label=".form-select-sm example">
                    <option value="">Month</option>
					<?php
					foreach ( $months as $num => $name ) {
						
						$select = is_numeric( $month ) && + $month === + $num ? 'selected' : '';
						
						echo '<option ' . $select . ' value="' . $num . '">' . $name . '</option>';
					}
					?>
                </select>
				
				<?php $current_year = date( 'Y' ); ?>
                <select class="form-select w-auto" name="fyear" aria-label=".form-select-sm example">
                    <option value="">Year</option>
					<?php
					
					for ( $year = 2023; $year <= $current_year; $year ++ ) {
						$select = is_numeric( $year_param ) && + $year_param === + $year ? 'selected' : '';
						echo '<option ' . $select . ' value="' . $year . '">' . $year . '</option>';
					}
					?>
                </select>

                <select class="form-select w-auto" name="dispatcher" aria-label=".form-select-sm example">
                    <option value="">Dispatcher</option>
					<?php if ( is_array( $dispatchers ) ): ?>
						<?php foreach ( $dispatchers as $dispatcher ): ?>
                            <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
								? 'selected' : ''; ?> >
								<?php echo $dispatcher[ 'fullname' ]; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>

                <select class="form-select w-auto" name="load_status" aria-label=".form-select-sm example">
                    <option value="">Load status</option>
					<?php if ( is_array( $statuses ) ): ?>
						<?php foreach ( $statuses as $key => $val ): ?>
                            <option value="<?php echo $key; ?>" <?php echo $load_status === $key ? 'selected' : '' ?> >
								<?php echo $val; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
				
				<?php if ( $post_tp === 'dispatcher' ): ?>
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
					
					<?php if ( $show_filter_by_office ): ?>
                        <select class="form-select w-auto" name="office" aria-label=".form-select-sm example">
                            <option value="all">Office</option>
							<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
								<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $office === $key ? 'selected'
										: '' ?> >
										<?php echo $val; ?>
                                    </option>
								<?php endforeach; ?>
							<?php endif; ?>
                        </select>
					<?php endif; ?>
				
				<?php endif; ?>
				<?php if ( $post_tp === 'accounting' ): ?>
                    <select class="form-select w-auto" name="invoice" aria-label=".form-select-sm example">
                        <option value="">invoices</option>
						<?php if ( is_array( $invoices ) ): ?>
							<?php foreach ( $invoices as $key => $val ): ?>
                                <option value="<?php echo $key; ?>" <?php echo $invoice === $key ? 'selected' : '' ?> >
									<?php echo $val; ?>
                                </option>
							<?php endforeach; ?>
						<?php endif; ?>
                    </select>
				<?php endif; ?>
				
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
