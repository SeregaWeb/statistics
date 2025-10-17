<?php
/**
 * ETA Pickup Popup
 * Uses the common ETA popup template
 *
 * @package WP-rock
 */

$popup_args = array(
    'popup_id' => 'popup_eta_pick_up',
    'title' => 'Set Pickup ETA',
    'form_class' => 'js-eta-pickup-form',
    'current_date' => '',
    'current_time' => ''
);

get_template_part(TEMPLATE_PATH . 'popups/eta', 'popup', $popup_args);


