<?php
$helper        = new TMSReportsHelper();
$driver_helper = new TMSDriversHelper();
$recruiters    = $helper->get_recruiters();
$sources       = $driver_helper->source;

$search             = get_field_value( $_GET, 'my_search' );
$recruiter_initials = trim( get_field_value( $_GET, 'recruiter' ) ?? '' );
$month              = get_field_value( $_GET, 'fmonth' );
$year_param         = get_field_value( $_GET, 'fyear' );
$source             = trim( get_field_value( $_GET, 'source' ) ?? '' );
$additional         = trim( get_field_value( $_GET, 'additional' ) ?? '' );
$additional_logic   = trim( get_field_value( $_GET, 'additional_logic' ) ?? 'has' ); // 'has' or 'not_has'
$driver_status      = trim( get_field_value( $_GET, 'driver_status' ) ?? '' );
$current_year       = date( 'Y' );
$driver_statuses    = $driver_helper->status;


$driver_capabilities = array(
	'twic'                    => 'TWIC',
	'tsa_approved'            => 'TSA',
	'hazmat_certificate'      => 'Hazmat certificate',
	'hazmat_endorsement'      => 'Hazmat endorsement',
	'global_entry'             => 'Global Entry',
	'change_9_training'       => 'Change 9',
	'canada_transition_proof' => 'Canada transition proof',
	'tanker_endorsement'      => 'Tanker endorsement',
	'background_check'        => 'Background check',
	'lift_gate'               => 'Lift gate',
	'pallet_jack'             => 'Pallet jack',
	'dolly'                   => 'Dolly',
	'ppe'                     => 'PPE',
	'e_tracks'                => 'E tracks',
	'ramp'                    => 'Ramp',
	'printer'                 => 'Printer',
	'sleeper'                 => 'Sleeper',
	'load_bars'               => 'Load_bars',
	'mc'                      => 'MC',
	'dot'                     => 'DOT',
	'canada'                  => 'Canada',
	'mexico'                  => 'Mexico',
);

?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#">Drivers</a>
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

                <select class="form-select w-auto" name="additional_logic" aria-label="Additional logic">
                    <option value="has" <?php echo $additional_logic === 'has' ? 'selected' : ''; ?>>Has</option>
                    <option value="not_has" <?php echo $additional_logic === 'not_has' ? 'selected' : ''; ?>>Does not have</option>
                </select>

                <select class="form-select w-auto" name="additional" aria-label=".form-select-sm example">
                    <option value="">Additional</option>
					<?php if ( is_array( $driver_capabilities ) ): ?>
						<?php foreach ( $driver_capabilities as $key => $val ): ?>
                            <option value="<?php echo $key; ?>" <?php echo $additional === $key ? 'selected' : '' ?> >
								<?php echo $val; ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>

			 <select class="form-select w-auto" name="driver_status" aria-label=".form-select-sm example">
				<option value="">Driver status</option>
				<?php if ( is_array( $driver_statuses ) ): ?>
					<?php foreach ( $driver_statuses as $key => $val ): ?>
						<option value="<?php echo $key; ?>" <?php echo $driver_status === $key ? 'selected' : '' ?> >
							<?php echo $val; ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
				
				<?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
				<?php endif; ?>
            </div>
        </form>
    </div>
</nav>
