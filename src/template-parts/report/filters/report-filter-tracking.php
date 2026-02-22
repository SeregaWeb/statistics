<?php
$helper   = new TMSReportsHelper();
$statuses = $helper->get_statuses();
$dispatchers = $helper->get_dispatchers();

$search       = get_field_value( $_GET, 'my_search' );
$load_status  = get_field_value( $_GET, 'load_status' );
$type         = get_field_value( $_GET, 'type' );
$dispatcher   = get_field_value( $_GET, 'dispatcher' );
$date_pickup  = get_field_value( $_GET, 'date_pickup' );
$date_delivery = get_field_value( $_GET, 'date_delivery' );

$hide_status         = get_field_value( $args, 'hide_status' );
$my_team             = get_field_value( $args, 'my_team' );
$quick_status_counts  = get_field_value( $args, 'quick_status_counts' );
$quick_status_counts  = is_array( $quick_status_counts ) ? $quick_status_counts : array();

$office  = get_field_value( $_GET, 'office' );
$offices = $helper->get_offices_from_acf();

// Filter dispatchers by team if my_team is provided
if ( is_array( $my_team ) && ! empty( $my_team ) ) {
	$dispatchers = array_filter( $dispatchers, function( $dispatcher_item ) use ( $my_team ) {
		return in_array( $dispatcher_item['id'], $my_team );
	});
}
?>

<div class="navbar-sticky-custom d-flex flex-column ">

<?php
		$quick_statuses = array(
			''                   => 'All',
			'waiting-on-pu-date' => 'Waiting on PU',
			'at-pu'              => '@PU',
			'loaded-enroute'     => 'Loaded & Enroute',
			'at-del'             => '@DEL',
		);
		if ( ! $hide_status ) :
			$base_url = get_the_permalink();
			$get_copy = isset( $_GET ) ? array_filter( $_GET ) : array();
			?>


        <div class="d-flex flex-wrap gap-1 align-items-center">
			<?php foreach ( $quick_statuses as $status_key => $label ) : ?>
				<?php
				$params = $get_copy;
				if ( $status_key === '' ) {
					unset( $params['load_status'] );
				} else {
					$params['load_status'] = $status_key;
				}
				$href = add_query_arg( $params, $base_url );
				$active = (string) $load_status === (string) $status_key;
				$count = isset( $quick_status_counts[ $status_key ] ) ? (int) $quick_status_counts[ $status_key ] : 0;
				?>
				<a href="<?php echo esc_url( $href ); ?>"
				   class="btn btn-sm <?php echo $active ? 'btn-primary' : 'btn-outline-secondary'; ?> js-tracking-quick-status"
				   data-status-key="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $label ); ?> <span class="badge bg-secondary js-tracking-count"><?php echo esc_html( $count ); ?></span></a>
			<?php endforeach; ?>
        </div>
		<?php endif; ?>

	<nav class="navbar navbar-expand-lg navbar-light">

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


				<select class="form-select w-auto" name="office" aria-label=".form-select-sm example">
					<option value="all">Office</option>
						<?php if ( isset( $offices[ 'choices' ] ) && is_array( $offices[ 'choices' ] ) ): ?>
							<?php foreach ( $offices[ 'choices' ] as $key => $val ): ?>
						<option value="<?php echo $key; ?>" <?php echo $office === $key ? 'selected' : '' ?> >
									<?php echo $val; ?>
						</option>
							<?php endforeach; ?>
						<?php endif; ?>
				</select>
					
					<select class="form-select w-auto" name="dispatcher" aria-label=".form-select-sm example">
					<option value="">Dispatcher</option>
						<?php if ( is_array( $dispatchers ) ): ?>
							<?php foreach ( $dispatchers as $dispatcher_item ): ?>
						<option value="<?php echo $dispatcher_item[ 'id' ]; ?>" <?php echo strval( $dispatcher ) === strval( $dispatcher_item[ 'id' ] ) ? 'selected' : ''; ?> >
									<?php echo $dispatcher_item[ 'fullname' ]; ?>
						</option>
							<?php endforeach; ?>
						<?php endif; ?>
				</select>

					<div class="d-flex align-items-center gap-1">
						<input type="text"
							class="form-control form-control-sm js-tracking-date-pickup"
							name="date_pickup"
							placeholder="Pickup"
							value="<?php echo esc_attr( $date_pickup ); ?>"
							aria-label="Pickup date"
							autocomplete="off">
					</div>
					<div class="d-flex align-items-center gap-1">
						<input type="text"
							class="form-control form-control-sm js-tracking-date-delivery"
							name="date_delivery"
							placeholder="Delivery"
							value="<?php echo esc_attr( $date_delivery ); ?>"
							aria-label="Delivery date"
							autocomplete="off">
					</div>
					
					<?php if ( ! $hide_status && (!isset($_GET['load_status']) || $load_status !== 'at-del' && $load_status !== 'loaded-enroute' && $load_status !== 'at-pu' && $load_status !== 'waiting-on-pu-date' ) ) : ?>
					<select class="form-select w-auto" name="load_status" aria-label=".form-select-sm example">
					<option value="">Load status</option>
							<?php if ( is_array( $statuses ) ): ?>
								<?php foreach ( $statuses as $key => $val ): 
							
							if ($key !== 'at-del' && $key !== 'loaded-enroute' && $key !== 'at-pu' && $key !== 'waiting-on-pu-date') {
								echo '<option value="' . $key . '" ' . ($load_status === $key ? 'selected' : '') . ' >' . $val . '</option>';
							}
							?>
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
</div>
