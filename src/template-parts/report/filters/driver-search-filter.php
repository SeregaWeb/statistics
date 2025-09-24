<?php
$helper        = new TMSReportsHelper();
$driver_helper = new TMSDriversHelper();
$driver        = new TMSDrivers();

$search          = get_field_value( $_GET, 'my_search' );
$country         = get_field_value( $_GET, 'country' );
$radius          = get_field_value( $_GET, 'radius' );
$extended_search = get_field_value( $_GET, 'extended_search' );
$capabilities    = get_field_value( $_GET, 'capabilities' );

// Get drivers statistics
$drivers_stats = $driver->get_drivers_available();

// If extended_search is set, use it as the search value
if ( ! empty( $extended_search ) ) {
	$search = $extended_search;
}

// Convert capabilities string to array if it exists
$selected_capabilities = array();
if ( ! empty( $capabilities ) ) {
	$selected_capabilities = is_array( $capabilities ) ? $capabilities : array( $capabilities );
}

// Define driver capabilities for filtering
$driver_capabilities = array(
	'hazmat_certificate'      => 'Hazmat',
    'hazmat_endorsement'      => 'Hazmat Endorsement',
	'twic'                    => 'TWIC',
	'cross_border_canada'     => 'Canada', // Special handling for cross_border
	'cross_border_mexico'     => 'Mexico', // Special handling for cross_border
	'tsa_approved'            => 'TSA',
	'real_id'                 => 'Real ID',
	'pallet_jack'             => 'Pallet Jack',
	'lift_gate'               => 'Lift Gate',
	'team_driver_enabled'     => 'Team Driver', // Special handling for team_driver_enabled
	'ppe'                     => 'PPE',
	'load_bars'               => 'Load bars',
	'printer'                 => 'Printer',
	'cdl'                     => 'CDL',
	'tanker_endorsement'      => 'Tanker endorsement',
	'ramp'                    => 'Ramp',
	'dock_high'               => 'Dock High',
	'e_tracks'                => 'E-tracks',
);

?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-start w-100">
        
    
        
        <form class="d-flex flex-column align-items-start justify-content-end gap-1 w-100"
              id="navbarNavDriverSearch">
            <div class="d-flex gap-1 align-items-center">

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

                       <select class="form-select w-auto" name="country" aria-label=".form-select-sm example">
                    <option value="USA" <?php echo (isset($_GET['country']) && $_GET['country'] === 'USA') ? 'selected' : ''; ?>>USA</option>
                    <option value="CA" <?php echo (isset($_GET['country']) && $_GET['country'] === 'CA') ? 'selected' : ''; ?>>Canada</option>
                </select>

                <select class="form-select w-auto" name="radius" aria-label=".form-select-sm example">
                    <option value="100" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '100') ? 'selected' : ''; ?>>100 miles</option>
                    <option value="150" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '150') ? 'selected' : ''; ?>>150 miles</option>
                    <option value="200" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '200') ? 'selected' : ''; ?>>200 miles</option>
                    <option value="250" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '250') ? 'selected' : ''; ?>>250 miles</option>
                    <option value="300" <?php echo (!isset($_GET['radius']) || $_GET['radius'] === '300') ? 'selected' : ''; ?>>300 miles</option>
                    <option value="400" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '400') ? 'selected' : ''; ?>>400 miles</option>
                    <option value="500" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '500') ? 'selected' : ''; ?>>500 miles</option>
                    <option value="600" <?php echo (isset($_GET['radius']) && $_GET['radius'] === '600') ? 'selected' : ''; ?>>600 miles</option>
                </select>

                <button class="btn btn-outline-dark" type="submit">Search</button>

                <?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo get_the_permalink(); ?>">Reset</a>
				<?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check form-switch">
                    <label class="form-check-label" for="search_type">Extended search</label>
                    <input class="form-check-input" type="checkbox" id="search_type"
                           <?php echo ! empty( $_GET['extended_search'] ) ? 'checked' : ''; ?>>
                </div>
                

                <!-- Driver Capabilities Filter -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="capabilitiesDropdown" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        Capabilities 
                        <span class="badge bg-primary ms-1 js-capabilities-count">0</span>
                    </button>
                    <ul class="dropdown-menu js-capabilities-menu" aria-labelledby="capabilitiesDropdown">
                        <?php foreach ( $driver_capabilities as $capability_key => $capability_label ): ?>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" name="capabilities[]" 
                                           value="<?php echo esc_attr( $capability_key ); ?>"
                                           class="js-capability-checkbox"
                                           <?php echo in_array( $capability_key, $selected_capabilities ) ? 'checked' : ''; ?>>
                                    <?php echo esc_html( $capability_label ); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
				
            </div>
        </form>

        <div class="d-flex flex-column align-items-end gap-1">
            <div class="d-flex align-items-center gap-1 flex-1 no-wrap">
                <div class="text-small text-nowrap">Available: <span class="text-primary"><?php echo $drivers_stats['available']; ?></span></div>
                <div class="text-small text-nowrap">Available on: <span class="text-primary"><?php echo $drivers_stats['available_on']; ?></span></div>
                <div class="text-small text-nowrap">Not updated: <span class="text-primary"><?php echo $drivers_stats['not_updated']; ?></span></div>
            </div>
        </div>
        </div>
    </div>

</nav>

<?php if ( ! empty( $search ) ): ?>
        <!-- Quick Copy Block -->
        <div class="quick-copy-block d-flex justify-content-between align-items-center mb-3">
            <div class="text-small text-muted mb-2">QUICK COPY</div>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-success js-quick-copy" data-status="available">Available</button>
                <button type="button" class="btn btn-sm btn-outline-success js-quick-copy" data-status="available_on">Available on</button>
                <button type="button" class="btn btn-sm btn-outline-danger js-quick-copy" data-status="not_available">Not Available</button>
                <button type="button" class="btn btn-sm btn-primary js-quick-copy" data-status="all">all</button>
            </div>
        </div>
        <?php endif; ?>

