<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$object_driver  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

$driver = new TMSDrivers();
$helper = new TMSReportsHelper();

$main = get_field_value( $object_driver, 'main' );
$meta = get_field_value( $object_driver, 'meta' );

$driver_name = get_field_value( $meta, 'driver_name' );

// Get driver statistics
$driver_statistics = $driver->get_driver_statistics( $post_id, true );

$TMSUsers = new TMSUsers();

$access_change_auto_block = $TMSUsers->check_user_role_access( array(
    'administrator',
    'recruiter',
    'recruiter-tl',
), true );

?>

<div class="container mt-4 pb-5">
    <h2 class="mb-3">Driver Statistics & Rating</h2>
    
    <div class="row">

        <?php if ( $access_change_auto_block ) : ?>
            <div class="col-12">

                <form id="ratingForm">

                    <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">

                    <div class="form-check form-switch mb-3">
                        <?php 
                        $exclude_from_auto_block = get_field_value( $meta, 'exclude_from_auto_block' );
                        $is_excluded = ! empty( $exclude_from_auto_block ) && $exclude_from_auto_block === '1';
                        ?>
                        <input class="form-check-input" type="checkbox" id="exclude-from-auto-block" 
                            name="exclude_from_auto_block" value="1" 
                            <?php echo $is_excluded ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="exclude-from-auto-block">
                            Exclude from auto blocking (low rating)
                        </label>
                    </div>

                </form>

            </div>
        <?php endif; ?>

        <!-- Rating Section -->
        <div class="col-md-6 mb-4">
        
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Rating</h5>
                    <button type="button" <?php echo $full_only_view ? 'disabled' : ''; ?> class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ratingModal">
                        Add Rating
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h6>Average Rating</h6>
                            <?php 
                            $avg_rating = $driver_statistics['rating']['avg_rating'];
                            $get_button_color = function( $value ) {
                                if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
                                    return 'text-secondary'; // grey
                                }
                                if ( + $value <= 1 ) {
                                    return 'text-danger'; // red
                                }
                                if ( + $value <= 4 ) {
                                    return 'text-warning'; // orange
                                }
                                if ( + $value > 4 ) {
                                    return 'text-success'; // green
                                }
                                return 'text-secondary'; // default grey
                            };
                            ?>
                            <div class="display-4 <?php echo $get_button_color( $avg_rating ); ?>"><?php echo $avg_rating; ?></div>
                        </div>
                        <div class="col-6">
                            <h6>Total Ratings</h6>
                            <?php 
                            $total_ratings = $driver_statistics['rating']['count'];
                            $get_total_color = function( $value ) {
                                if ( $value == 0 ) {
                                    return 'text-secondary'; // grey for no ratings
                                }
                                if ( $value <= 5 ) {
                                    return 'text-warning'; // orange for few ratings
                                }
                                return 'text-success'; // green for many ratings
                            };
                            ?>
                            <div class="display-4 <?php echo $get_total_color( $total_ratings ); ?>"><?php echo $total_ratings; ?></div>
                        </div>
                    </div>
                    
                    <?php if ( ! empty( $driver_statistics['rating']['data'] ) ) : ?>
                        <div class="mt-3">
                            <h6>Recent Ratings</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Rating</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $ratings_list = $driver_statistics['rating']['data'];
                                        $ratings_total = is_array( $ratings_list ) ? count( $ratings_list ) : 0;
                                        $row_index = 0;
                                        foreach ( $ratings_list as $rating ) : 
                                            $is_hidden = $row_index >= 5;
                                            $row_index++;
                                        ?>
                                            <tr class="js-rating-row"<?php echo $is_hidden ? ' style="display:none"' : ''; ?>>
                                                <td><?php echo esc_html( $rating['name'] ); ?></td>
                                                <td><?php echo date( 'm/d/Y g:i a', $rating['time'] ); ?></td>
                                                <td>
                                                    <?php 
                                                    $rating_value = $rating['reit'];
                                                    $order_number = $rating['order_number'];
                                                    $get_rating_color = function( $value ) {
                                                        if ( ! is_numeric( $value ) || intval( $value ) === 0 ) {
                                                            return 'bg-secondary'; // grey
                                                        }
                                                        if ( + $value <= 1 ) {
                                                            return 'bg-danger'; // red
                                                        }
                                                        if ( + $value <= 4 ) {
                                                            return 'bg-warning'; // orange
                                                        }
                                                        if ( + $value > 4 ) {
                                                            return 'bg-success'; // green
                                                        }
                                                        return 'bg-secondary'; // default grey
                                                    };
                                                    ?>
                                                    <span class="badge <?php echo $get_rating_color( $rating_value ); ?>"><?php echo $rating_value; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ( $order_number ) : ?>
                                                        <span class="text-small">Order: <?php echo esc_html( $order_number ); ?></strong>
                                                        <br>
                                                    <?php endif; ?>
                                                <?php echo stripslashes( $rating['message'] ); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm <?php echo ( $ratings_total > 5 ) ? '' : 'd-none'; ?>" id="ratingsLoadMore" data-step="5">Load more</button>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="text-muted mt-3">No ratings yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Notifications Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifications</h5>
                    <button type="button" <?php echo $full_only_view ? 'disabled' : ''; ?> class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#noticeModal">
                        Add Notice
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Total Notifications</h6>
                        <div class="display-4 text-info"><?php echo $driver_statistics['notice']['count']; ?></div>
                    </div>
                    
                    <?php if ( ! empty( $driver_statistics['notice']['data'] ) ) : ?>
                        <div class="mt-3">
                            <h6>Recent Notifications</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $notices_list = $driver_statistics['notice']['data'];
                                        $notices_total = is_array( $notices_list ) ? count( $notices_list ) : 0;
                                        $notice_index = 0;
                                        foreach ( $notices_list as $notice ) : 
                                            $notice_hidden = $notice_index >= 5;
                                            $notice_index++;
                                        ?>
                                            <tr class="js-notice-row <?php echo $notice['status'] == 1 ? 'table-success' : ''; ?>"<?php echo $notice_hidden ? ' style="display:none"' : ''; ?>>
                                                <td><?php echo esc_html( $notice['name'] ); ?></td>
                                                <td><?php echo date( 'm/d/Y g:i a', $notice['date'] ); ?></td>
                                                <td><?php echo esc_html( $notice['message'] ); ?></td>
                                                <td>
                                                    <input type="checkbox" 
                                                           class="form-check-input notice-status-checkbox" 
                                                           data-notice-id="<?php echo $notice['id']; ?>"
                                                           <?php echo $notice['status'] == 1 ? 'checked' : ''; ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm <?php echo ( $notices_total > 5 ) ? '' : 'd-none'; ?>" id="noticesLoadMore" data-step="5">Load more</button>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="text-muted mt-3">No notifications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <h2 class="mb-3">Driver Statistics</h2>
        </div>
    </div>

    <!-- Driver Statistics Cards -->
    <div class="row mb-4" id="driver-statistics-container">
        <div class="col-12">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading driver statistics...</p>
            </div>
        </div>
    </div>

    <!-- Hidden nonce for statistics -->
    <input type="hidden" id="driver-statistics-nonce" value="<?php echo wp_create_nonce('driver_statistics_nonce'); ?>">
</div>

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ratingModalLabel"><?php echo esc_html( $driver_name ); ?> - Add Rating</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ratingForm">
                <div class="modal-body">
                    <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
                    <?php wp_nonce_field( 'tms_add_rating', 'tms_rating_nonce' ); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Rating</label>
                        <div class="d-flex gap-2">
                            <?php 
                            $get_rating_btn_color = function( $value ) {
                                if ( + $value <= 1 ) {
                                    return 'btn-outline-danger'; // red
                                }
                                if ( + $value <= 4 ) {
                                    return 'btn-outline-warning'; // orange
                                }
                                if ( + $value > 4 ) {
                                    return 'btn-outline-success'; // green
                                }
                                return 'btn-outline-secondary'; // default grey
                            };
                            
                            for ( $i = 1; $i <= 5; $i++ ) : ?>
                                <button type="button" class="btn <?php echo $get_rating_btn_color( $i ); ?> rating-btn" data-rating="<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="selectedRating" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="loadNumber" class="form-label">Load number</label>
                        <select class="form-control" id="loadNumber" required name="load_number">
                            <option value="">Select a load...</option>
                            <option value="Canceled">Canceled</option>
                        </select>
                        <div class="form-text">
                            <small class="text-muted" id="loadsInfo">Loading available loads...</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea class="form-control" id="comments" name="comments" required rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notice Modal -->
<div class="modal fade" id="noticeModal" tabindex="-1" aria-labelledby="noticeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="noticeModalLabel"><?php echo esc_html( $driver_name ); ?> - Add Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="noticeForm">
                <div class="modal-body">
                    <input type="hidden" name="driver_id" value="<?php echo $post_id; ?>">
                    <?php wp_nonce_field( 'tms_add_notice', 'tms_notice_nonce' ); ?>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Comments</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

