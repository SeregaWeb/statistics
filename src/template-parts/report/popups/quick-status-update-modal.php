<?php
/**
 * Quick Status Update Modal
 * Used in driver tables for quick status updates
 */
?>

<!-- Quick Status Update Modal -->
<div class="modal fade" id="quickStatusUpdateModal" tabindex="-1" aria-labelledby="quickStatusUpdateModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickStatusUpdateModalLabel">Quick Status Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="js-quick-update-location-driver">
                    <input type="hidden" class="js-id_driver" name="driver_id" value="">

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Status<span class="required-star text-danger">*</span></label>
                            <div class="js-status-container">
                                <select name="driver_status" class="form-control form-select js-state">
                                    <option value="">Select Status</option>
                                <?php
                                $driver = new TMSDrivers();
                                $changeable_statuses = $driver->get_changeable_statuses();
                                $all_statuses = $driver->status;
                               
                                
                                if ( is_array( $changeable_statuses ) && is_array( $all_statuses ) ):
                                    foreach ( $changeable_statuses as $status_key ):
                                        if ( isset( $all_statuses[ $status_key ] ) ):
                                            ?>
                                            <option value="<?php echo $status_key; ?>"><?php echo $all_statuses[ $status_key ]; ?></option>
                                            <?php
                                        endif;
                                    endforeach;
                                endif;
                                ?>
                                </select>
                                <div class="js-status-readonly" style="display: none;">
                                    <p class="form-control-static js-status-text"></p>
                                    <input type="hidden" name="driver_status_readonly" class="js-status-hidden">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Date<span class="required-star text-danger">*</span></label>
                            <input type="text" class="form-control js-new-format-datetime" name="status_date"
                                   value="">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">State<span class="required-star text-danger">*</span></label>
                            <select name="current_location" required class="form-control form-select js-state-new">
                                <option value="" disabled selected>Select State</option>
                                <?php
                                $helper = new TMSReportsHelper();
                                $states = $helper->get_states();
                                if ( is_array( $states ) ):
                                    foreach ( $states as $key => $state ):
                                        ?>
                                        <option value="<?php echo $key; ?>" <?php echo is_array( $state )
                                            ? 'disabled' : ''; ?>>
                                            <?php echo is_array( $state ) ? $state[ 0 ] : $state; ?>
                                        </option>
                                        <?php
                                    endforeach;
                                endif
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">City<span class="required-star text-danger">*</span></label>
                            <input required type="text" class="form-control js-city-new" name="current_city"
                                   value="">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Zip code<span
                                        class="required-star text-danger">*</span></label>
                            <input required type="text" class="form-control js-zip-code" name="current_zipcode"
                                   value="">
                        </div>

                        <input type="hidden" class="js-latitude" value="" name="latitude">
                        <input type="hidden" class="js-longitude" value="" name="longitude">
                        <input type="hidden" class="js-country" name="country">
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-end gap-1 align-items-center">
                                <p class="m-0">Fill in the rest of the fields by zip code</p>
                                <select name="current_country" required
                                        class="form-control form-select w-auto js-search-country">
                                    <option value="USA" selected>USA</option>
                                    <option value="CAN">Canada</option>
                                    <option value="MEX">Mexico</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary js-fill-new-location">Fill
                                    out
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control js-notes" name="notes" rows="3" placeholder="Add notes about this status update..."></textarea>
                        </div>
                    </div>

                    <p class="text-center m-0 js-last-user-update">
                    
                    </p>
                </form>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary js-submit-quick-update">Update</button>
            </div>
        </div>
    </div>
</div>
