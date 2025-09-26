<?php
/**
 * Universal file upload block component
 * 
 * @param array $args {
 *     @type string $field_name        Field name for the upload
 *     @type string $label            Display label for the field
 *     @type string $toggle_block     CSS class for toggle block
 *     @type string $checkbox_name    Name attribute for checkbox
 *     @type string $checkbox_id      ID attribute for checkbox
 *     @type bool   $is_checked       Whether checkbox is checked
 *     @type mixed  $file_value       Current file value (for checking if uploaded)
 *     @type string $popup_id         ID for popup modal
 *     @type string $button_text      Text for upload button (default: "Upload file")
 *     @type string $uploaded_text    Text when file is uploaded (default: "File uploaded")
 *     @type string $col_class        Bootstrap column class (default: "col-12 col-md-6")
 *     @type string $button_class     CSS class for upload button (default: "btn btn-success")
 *     @type bool   $show_icon        Whether to show uploaded file icon (default: true)
 *     @type string $wrapper_class    Additional CSS class for wrapper div
 * }
 */

// Set defaults
$defaults = [
    'field_name'     => '',
    'label'          => '',
    'toggle_block'   => '',
    'checkbox_name'  => '',
    'checkbox_id'    => '',
    'is_checked'     => false,
    'file_value'     => null,
    'popup_id'       => '',
    'button_text'    => 'Upload file',
    'uploaded_text'  => 'File uploaded',
    'col_class'      => 'col-12 col-md-6',
    'button_class'   => 'btn btn-success',
    'show_icon'      => true,
    'wrapper_class'  => '',
    'expired_date'   => '',
    'expired_date_name' => '',
    'expired_date_id' => '',
    'expired_date_class' => '',
    'expired_date_label' => 'Expired date',
    'expired_date_placeholder' => 'Expired date',
    'expired_date_value' => '',
    'expired_date_required' => false,
    'interview' => '',
    'interview_popup_id' => '',
    'interview_field_name' => '',
    'interview_file_arr' => '',
    'full_only_view' => '',
    'post_id' => '',
    'class_name' => '',
    'field_name' => '',
    'field_label' => '',
    'delete_action' => '',
    'active_tab' => '',
];

$args = wp_parse_args( $args, $defaults );

// Validate required parameters
if ( empty( $args['field_name'] ) || empty( $args['label'] ) || empty( $args['toggle_block'] ) ) {
    return;
}

// Get helper instance
$helper = new TMSReportsHelper();
?>

<div class="<?php echo esc_attr( $args['col_class'] ); ?> js-add-new-report <?php echo esc_attr( $args['wrapper_class'] ); ?>">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input js-toggle"
                       data-block-toggle="<?php echo esc_attr( $args['toggle_block'] ); ?>"
                       type="checkbox"
                       name="<?php echo esc_attr( $args['checkbox_name'] ); ?>"
                       id="<?php echo esc_attr( $args['checkbox_id'] ); ?>"
                       <?php echo $args['is_checked'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="<?php echo esc_attr( $args['checkbox_id'] ); ?>">
                    <?php echo esc_html( $args['label'] ); ?>
                </label>
            </div>

            <div class="<?php echo esc_attr( $args['toggle_block'] ); ?> <?php echo $args['is_checked'] ? '' : 'd-none'; ?>">
                <label class="form-label d-flex align-items-center gap-1">
                    <?php 
                    if ( $args['file_value'] ) {
                        echo esc_html( $args['uploaded_text'] );
                        if ( $args['show_icon'] ) {
                            echo $helper->get_icon_uploaded_file();
                        }
                    }
                    ?>
                </label>

                <?php if ( ! $args['file_value'] ): ?>
                    <button data-href="#<?php echo esc_attr( $args['popup_id'] ); ?>"
                            class="<?php echo esc_attr( $args['button_class'] ); ?> js-open-popup-activator mt-1">
                        <?php echo esc_html( $args['button_text'] ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ( $args['expired_date'] ): ?>
                <div class="form-label mt-2"><?php echo esc_html( $args['expired_date_label'] ); ?></div>
                <input type="text" class="form-control js-new-format-date <?php echo esc_attr( $args['expired_date_class'] ); ?>" name="<?php echo esc_attr( $args['expired_date_name'] ); ?>"
                       id="<?php echo esc_attr( $args['expired_date_id'] ); ?>"
                       value="<?php echo $args['expired_date_value']; ?>"
                       placeholder="<?php echo esc_attr( $args['expired_date_placeholder'] ); ?>">
            <?php endif; ?>

            <?php if ( $args['interview'] ): ?>
                <label class="form-label d-flex align-items-center gap-1 mt-2">
                        Interview <?php echo esc_html( $args['field_label'] ); ?>
                    </label>

                    <?php if ( !$args['interview_file_arr'] ): ?>
                        <div class="js-interview-file-driver">
                            <?php if ( ! $args['full_only_view'] ): ?>
                                <button data-href="#<?php echo $args['interview_popup_id']; ?>"
                                        class="btn btn-outline-primary js-open-popup-activator">
                                    Upload interview
                                </button>
                            <?php else: ?>
                                <p>-</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                    <div class="<?php echo 'js-remove-one-no-form'; ?> d-flex align-items-center gap-1 <?php echo 'interview-file'; ?>" data-tab="<?php echo $args['active_tab']; ?>">
                        <input type="hidden" name="image-id" value="<?php echo $args['interview_file_arr'][ 'id' ]; ?>">
                        <input type="hidden" name="image-fields" value="<?php echo $args['interview_field_name'] ?>">
                        <input type="hidden" name="post_id" value="<?php echo $args['post_id']; ?>">

                        <audio controls class="w-100">
                            <source src="<?php echo $args['interview_file_arr'][ 'url' ]; ?>" type="audio/mpeg">
                        </audio>

                        <?php if ( ! $args['full_only_view'] ): ?> 
                            <button class="btn btn-transparent btn-remove-file js-remove-one-no-form-btn">
                                <?php echo $helper->get_close_icon(); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
