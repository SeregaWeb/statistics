    <!-- Driver Notice Popup -->
    <div class="popup" id="driver-notice-popup">
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
                    <h3 class="mb-3">Driver Notes</h3>

                    <!-- Add Notice Form -->
                    <div class="mb-3">
                        <form id="noticeForm" class="row g-3">
                            <input type="hidden" name="driver_id" id="noticeDriverId">
                            <?php wp_nonce_field( 'tms_add_notice', 'tms_notice_nonce' ); ?>
                            
                            <div class="col-12">
                                <label for="message" class="form-label">Comments</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-sm">Add Notice</button>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" id="driverNoticeName"></h6>
                        <span class="badge bg-primary" id="driverNoticeCount"></span>
                    </div>
                    <div id="driverNoticeContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                
                    
                    <div class="mt-4 text-end">
                        <a href="#" class="btn btn-primary" id="driverNoticeFullPage" target="_blank">Go to Full
                            Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>