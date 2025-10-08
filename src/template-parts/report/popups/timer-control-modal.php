<?php
/**
 * Timer Control Modal
 * Used for managing timers (start, pause, stop)
 */
?>

<!-- Timer Control Modal -->
<div class="modal fade" id="timerControlModal" tabindex="-1" aria-labelledby="timerControlModalLabel"
     aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timerControlModalLabel">Timer Control</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="js-timer-control-form">
                    <input type="hidden" class="js-load-id" name="load_id" value="">
                    <input type="hidden" class="js-timer-action" name="action" value="">

                    <!-- Timer Status Display -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info js-timer-status-display" style="display: none;">
                                <strong>Current Status:</strong> <span class="js-current-status"></span>
                                <br>
                                <strong>Started:</strong> <span class="js-timer-start-time"></span>
                                <br>
                                <strong>Duration:</strong> <span class="js-timer-duration"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Comment Field -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Comment</label>
                            <textarea class="form-control js-timer-comment" name="comment" rows="3" 
                                      placeholder="Add a comment about this timer action..."></textarea>
                            <div class="form-text js-comment-required" style="display: none; color: #dc3545;">
                                Comment is required when pausing timer
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-center">
                                <!-- Start Button -->
                                <button type="button" class="btn btn-success js-timer-start" style="display: none;">
                                    <i class="fas fa-play"></i> Start Timer
                                </button>
                                
                                <!-- Pause Button -->
                                <button type="button" class="btn btn-warning js-timer-pause" style="display: none;">
                                    <i class="fas fa-pause"></i> Pause Timer
                                </button>
                                
                                <!-- Resume Button -->
                                <button type="button" class="btn btn-info js-timer-resume" style="display: none;">
                                    <i class="fas fa-play"></i> Resume Timer
                                </button>
                                
                                <!-- Stop Button -->
                                <button type="button" class="btn btn-danger js-timer-stop" style="display: none;">
                                    <i class="fas fa-stop"></i> Stop Timer
                                </button>
                                
                                <!-- Update Button -->
                                <button type="button" class="btn btn-primary js-timer-update" style="display: none;">
                                    <i class="fas fa-sync-alt"></i> Update Timer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Timer History -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Timer History</h6>
                            <div class="js-timer-history" style="max-height: 200px; overflow-y: auto;">
                                <!-- Timer history will be loaded here -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
