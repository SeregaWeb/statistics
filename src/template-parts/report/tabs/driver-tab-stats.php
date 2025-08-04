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
?>

<div class="container mt-4 pb-5">
    <h2 class="mb-3">Driver Statistics & Rating</h2>
    
    <div class="row">
        <!-- Rating Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Rating</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ratingModal">
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
                                        <?php foreach ( array_slice( $driver_statistics['rating']['data'], 0, 5 ) as $rating ) : ?>
                                            <tr>
                                                <td><?php echo esc_html( $rating['name'] ); ?></td>
                                                <td><?php echo date( 'm/d/Y g:i a', $rating['time'] ); ?></td>
                                                <td>
                                                    <?php 
                                                    $rating_value = $rating['reit'];
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
                                                <td><?php echo esc_html( $rating['message'] ); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#noticeModal">
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
                                        <?php foreach ( array_slice( $driver_statistics['notice']['data'], 0, 5 ) as $notice ) : ?>
                                            <tr class="<?php echo $notice['status'] == 1 ? 'table-success' : ''; ?>">
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
                        </div>
                    <?php else : ?>
                        <p class="text-muted mt-3">No notifications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
                        <input type="text" class="form-control" id="loadNumber" name="load_number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
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

<script>
// Define ajaxurl for TypeScript
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
