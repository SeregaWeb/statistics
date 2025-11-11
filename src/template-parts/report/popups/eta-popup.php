<?php
/**
 * ETA Popup Template
 * Reusable popup for setting pickup or delivery ETA
 *
 * @package WP-rock
 */

$popup_id = $args['popup_id'] ?? 'popup_eta';
$title = $args['title'] ?? 'Set ETA';
$form_class = $args['form_class'] ?? 'js-eta-form';
?>

<div id="<?php echo esc_attr($popup_id); ?>" class="popup popup-quick-edit">
    <div class="my_overlay js-popup-close"></div>
    <div class="popup__wrapper-inner">
        <div class="popup-container">
            <button class="popup-close js-popup-close" aria-label="Close">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24.3842 13.3431C24.0335 12.9924 23.4657 12.9924 23.115 13.3431L20 16.4581L16.885 13.3431C16.5343 12.9924 15.9665 12.9924 15.6158 13.3431C15.2651 13.6938 15.2651 14.2616 15.6158 14.6123L18.7308 17.7273L15.6158 20.8423C15.2651 21.193 15.2651 21.7608 15.6158 22.1115C15.9665 22.4622 16.5343 22.4622 16.885 22.1115L20 18.9965L23.115 22.1115C23.4657 22.4622 24.0335 22.4622 24.3842 22.1115C24.7349 21.7608 24.7349 21.193 24.3842 20.8423L21.2692 17.7273L24.3842 14.6123C24.7349 14.2616 24.7349 13.6938 24.3842 13.3431Z" fill="#333"/>
                </svg>
            </button>

            <h3 class="mb-3"><?php echo esc_html($title); ?></h3>

            <form class="<?php echo esc_attr($form_class); ?>">
                <input type="hidden" name="id_load" value="">

                <!-- Location Info -->
                <div class="mb-3 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Location:</small>
                        <strong class="js-eta-popup-state">--</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Timezone:</small>
                        <strong class="js-eta-popup-timezone">--</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Current Time:</small>
                        <strong class="js-eta-popup-current-time">--:--:--</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo esc_attr($args['current_date'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Time</label>
                    <input type="time" name="time" class="form-control" value="<?php echo esc_attr($args['current_time'] ?? ''); ?>" required>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-secondary js-popup-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
