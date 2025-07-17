<?php
$helper        = new TMSReportsHelper();
$driver_helper = new TMSDriversHelper();

$search          = get_field_value( $_GET, 'my_search' );
$country         = get_field_value( $_GET, 'country' );
$radius          = get_field_value( $_GET, 'radius' );
$extended_search = get_field_value( $_GET, 'extended_search' );

// If extended_search is set, use it as the search value
if ( ! empty( $extended_search ) ) {
	$search = $extended_search;
}


//$driver_capabilities = array(
//	'twic'                    => 'TWIC',
//	'tsa_approved'            => 'TSA',
//	'hazmat_certificate'      => 'Hazmat certificate',
//	'hazmat_endorsement'      => 'Hazmat endorsement',
//	'change_9_training'       => 'Change 9',
//	'canada_transition_proof' => 'Canada transition proof',
//	'tanker_endorsement'      => 'Tanker endorsement',
//	'background_check'        => 'Background check',
//	'lift_gate'               => 'Lift gate',
//	'pallet_jack'             => 'Pallet jack',
//	'dolly'                   => 'Dolly',
//	'ppe'                     => 'PPE',
//	'e_tracks'                => 'E tracks',
//	'ramp'                    => 'Ramp',
//	'printer'                 => 'Printer',
//	'sleeper'                 => 'Sleeper',
//	'load_bars'               => 'Load_bars',
//	'mc'                      => 'MC',
//	'dot'                     => 'DOT',
//);

?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#">Drivers</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown"
                aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1"
              id="navbarNavDriverSearch">
            <div class="d-flex gap-1 align-items-center">

                <div class="form-check form-switch">
                    <label class="form-check-label" for="search_type">Extended search</label>
                    <input class="form-check-input" type="checkbox" id="search_type"
                           <?php echo ! empty( $_GET['extended_search'] ) ? 'checked' : ''; ?>>
                </div>

                <input class="form-control w-auto js-toggle-search" type="search" name="extended_search"
                       id="extended_search_input"
                       placeholder="Unit/name/phone/vehicle" 
                       value="<?php echo ! empty( $_GET['extended_search'] ) ? $search : ''; ?>" 
                       aria-label="Extended Search"
                       <?php echo ! empty( $_GET['extended_search'] ) ? '' : 'style="display: none;"'; ?>>
                <input class="form-control w-auto js-toggle-search" type="search" name="my_search" 
                       id="regular_search_input"
                       placeholder="Search" 
                       value="<?php echo empty( $_GET['extended_search'] ) ? $search : ''; ?>" 
                       aria-label="Search"
                       <?php echo ! empty( $_GET['extended_search'] ) ? 'style="display: none;"' : ''; ?>>
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </div>
            <div class="d-flex gap-1">

                <select class="form-select w-auto" name="country" aria-label=".form-select-sm example">
                    <option value="USA">USA</option>
                    <option value="CA">Canada</option>
                </select>

                <select class="form-select w-auto" name="radius" aria-label=".form-select-sm example">
                    <option value="100">100 miles</option>
                    <option value="150">150 miles</option>
                    <option value="200">200 miles</option>
                    <option value="250">250 miles</option>
                    <option value="300" selected>300 miles</option>
                    <option value="400">400 miles</option>
                    <option value="500">500 miles</option>
                    <option value="600">600 miles</option>
                </select>
				
				<?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
				<?php endif; ?>
            </div>
        </form>
    </div>
</nav>

