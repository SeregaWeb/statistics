    <!-- Driver Rating Popup -->
    <div class="popup" id="driver-rating-popup">
        <div class="my_overlay js-popup-close"></div>
        <div class="popup__wrapper-inner">
            <div class="popup-container">
                <button class="popup-close js-popup-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="popup-content">
                    <h3 class="mb-3">Driver Ratings</h3>

                    <!-- Add Rating Form -->
                    <div class="mb-3">
                        <form id="ratingForm" class="row g-3">
                            <input type="hidden" name="driver_id" id="ratingDriverId">
                            <?php wp_nonce_field( 'tms_add_rating', 'tms_rating_nonce' ); ?>
                            
                            <div class="col-12">
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
                            
                            <div class="col-12">
                                <label for="loadNumber" class="form-label">Load number</label>
                                <input type="text" class="form-control" id="loadNumber" required name="load_number">
                            </div>
                            
                            <div class="col-12">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" required rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-sm">Add Rating</button>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" id="driverRatingName"></h6>
                        <span class="badge bg-primary" id="driverRatingScore"></span>
                    </div>
                    <div id="driverRatingContent" >
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                
                    
                    <div class="mt-4 text-end">
                        <a href="#" class="btn btn-primary" id="driverRatingFullPage" target="_blank">Go to Full
                            Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>