<?php
$helper        = new TMSReportsHelper();
$driver_helper = new TMSDriversHelper();

$title = isset( $args['title'] ) ? $args['title'] : 'Insurance';

$search        = get_field_value( $_GET, 'my_search' );
$driver_status = trim( get_field_value( $_GET, 'driver_status' ) ?? '' );
$date_filter   = trim( get_field_value( $_GET, 'date_filter' ) ?? '' );

$driver_statuses = $driver_helper->status;

// Date filter options
$date_options = array(
	'day'       => 'Day',
	'week'      => 'Week',
	'month'     => 'Month',
	'3months'   => '3 Months',
);

?>

<nav class="navbar navbar-sticky-custom mb-5 mt-3 navbar-expand-lg navbar-light">
    <div class="container-fluid p-0">
        <a class="navbar-brand" href="#"><?php echo esc_html( $title ); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDriverInsurance"
                aria-controls="navbarNavDriverInsurance" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <form class="collapse navbar-collapse flex-column align-items-end justify-content-end gap-1"
              id="navbarNavDriverInsurance">
            <div class="d-flex gap-1">
                <input class="form-control w-auto" type="search" name="my_search" placeholder="Search"
                       value="<?php echo esc_attr( $search ); ?>" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </div>
            <div class="d-flex gap-1">
                <select class="form-select w-auto" name="driver_status" aria-label="Driver status">
                    <option value="">Driver status</option>
                    <?php if ( is_array( $driver_statuses ) ): ?>
                        <?php foreach ( $driver_statuses as $key => $val ): ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php echo $driver_status === $key ? 'selected' : ''; ?>>
                                <?php echo esc_html( $val ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <select class="form-select w-auto" name="date_filter" aria-label="Date filter">
                    <option value="">Date</option>
                    <?php if ( is_array( $date_options ) ): ?>
                        <?php foreach ( $date_options as $key => $val ): ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php echo $date_filter === $key ? 'selected' : ''; ?>>
                                <?php echo esc_html( $val ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <?php if ( ! empty( $_GET ) ): ?>
                    <a class="btn btn-outline-danger" href="<?php echo esc_url( get_the_permalink() ); ?>">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</nav>

