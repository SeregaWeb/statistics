<?php
/**
 * Add Log Message Modal
 * 
 * @package WP-rock
 * @since 4.4.0
 */

$user_id = get_field_value( $args, 'user_id' ) ?: get_current_user_id();
$page_type = get_field_value( $args, 'page_type' ) ?: 'tracking';
$project = get_field_value( $args, 'project' );
$flt = get_field_value( $args, 'flt' );

// Fallback to current user project if not provided
if ( ! $project ) {
    $user_id_for_project = get_current_user_id();
    $project = get_field( 'current_select', 'user_' . $user_id_for_project );
}
?>

<!-- Add Log Message Modal -->
<div class="modal fade" id="addLogModal" tabindex="-1" aria-labelledby="addLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLogModalLabel">Add Log Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="js-log-message-modal" id="logMessageForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="post_id" id="modal_post_id" value="">
                    <input type="hidden" name="post_type" value="<?php echo $page_type; ?>">
                    <input type="hidden" name="project" value="<?php echo $project; ?>">
                    <?php if ( $flt ): ?>
                        <input type="hidden" name="flt" value="<?php echo $flt; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="logMessageTextarea" class="form-label">Your message</label>
                        <textarea class="form-control" name="message" id="logMessageTextarea" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="form-check ">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="pinnedMessageCheck" value="1">
                        <label class="form-check-label" for="pinnedMessageCheck">
                            Pin this message
                        </label>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
