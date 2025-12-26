<?php
$search = get_field_value( $_GET, 'my_search' );
$document_type = trim( get_field_value( $_GET, 'document_type' ) ?? '' );
$document_status = trim( get_field_value( $_GET, 'document_status' ) ?? 'expired' ); // 'expired' or 'missing'

$document_types = array(
	'VR' => 'Vehicle Registration',
	'BS' => 'Bill of sale',
	'CT' => 'Certificate of title',
	'PL' => 'Plates',
	'DL' => 'Driver\'s License',
	'COI' => 'Certificate of Insurance',
	'EA' => 'Employment Authorization',
	'PR' => 'Permanent Resident',
	'PS' => 'Passport',
	'HZ' => 'Hazmat Certificate',
	'GE' => 'Global Entry',
	'TWIC' => 'TWIC',
	'TSA' => 'TSA',
	'DL_TEAM' => 'Driver\'s License (Team driver)',
	'EA_TEAM' => 'Employment Authorization (Team driver)',
	'PR_TEAM' => 'Permanent Resident (Team driver)',
	'PS_TEAM' => 'Passport (Team driver)',
	'USP_OWNER' => 'US Passport (Owner)',
	'PR_OWNER' => 'Permanent Residency (Owner)',
	'EA_OWNER' => 'Employment Authorization (Owner)',
	'CN_OWNER' => 'Certificate of Naturalization (Owner)',
	'EDL_OWNER' => 'Enhanced Driver License / Real ID (Owner)',
);

?>

<nav class="navbar mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#">Documents status</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDarkDropdown"
                aria-controls="navbarNavDarkDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1"
              id="navbarNavDriverDocuments">
            <div class="d-flex gap-1">
                <input class="form-control w-auto" type="search" name="my_search" placeholder="Search"
                       value="<?php echo esc_attr( $search ); ?>" aria-label="Search">
                
                <select class="form-select w-auto" name="document_type" aria-label="Document type">
                    <option value="">All documents</option>
					<?php if ( is_array( $document_types ) ): ?>
						<?php foreach ( $document_types as $key => $val ): ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php echo $document_type === $key ? 'selected' : ''; ?>>
								<?php echo esc_html( $val ); ?>
                            </option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
                
                <?php if ( ! empty( $document_type ) ): ?>
                <select class="form-select w-auto" name="document_status" aria-label="Document status">
                    <option value="expired" <?php echo $document_status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="missing" <?php echo $document_status === 'missing' ? 'selected' : ''; ?>>Missing</option>
                    <option value="valid" <?php echo $document_status === 'valid' ? 'selected' : ''; ?>>Valid</option>
                    <option value="temporary" <?php echo $document_status === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                </select>
                <?php endif; ?>
                
                <button class="btn btn-outline-dark" type="submit">Search</button>
                
				<?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo esc_url( get_the_permalink() ); ?>">Reset</a>
				<?php endif; ?>
            </div>
        </form>
    </div>
</nav>
