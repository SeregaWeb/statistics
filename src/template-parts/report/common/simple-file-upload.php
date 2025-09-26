<?php
/**
 * Simple file upload block component (without toggle)
 * 
 * @param array $args {
 *     @type string $field_name        Field name for the upload
 *     @type string $label            Display label for the field
 *     @type mixed  $file_value       Current file value (for checking if uploaded)
 *     @type string $popup_id         ID for popup modal
 *     @type string $button_text      Text for upload button (default: "Upload file")
 *     @type string $uploaded_text    Text when file is uploaded (default: "File uploaded")
 *     @type string $col_class        Bootstrap column class (default: "col-12")
 *     @type string $button_class     CSS class for upload button (default: "btn btn-success")
 *     @type bool   $show_icon        Whether to show uploaded file icon (default: true)
 *     @type string $wrapper_class    Additional CSS class for wrapper div
 * }
 */

// Set defaults
$defaults = [
    'field_name'     => '',
    'label'          => '',
    'file_value'     => null,
    'popup_id'       => '',
    'button_text'    => 'Upload file',
    'uploaded_text'  => 'File uploaded',
    'col_class'      => 'col-12',
    'button_class'   => 'btn btn-success',
    'show_icon'      => true,
    'wrapper_class'  => ''
];

$args = wp_parse_args( $args, $defaults );

// Validate required parameters
if ( empty( $args['field_name'] ) || empty( $args['label'] ) || empty( $args['popup_id'] ) ) {
    return;
}

// Get helper instance
$helper = new TMSReportsHelper();
?>

<div class="<?php echo esc_attr( $args['col_class'] ); ?> js-add-new-report <?php echo esc_attr( $args['wrapper_class'] ); ?>">
    <div class="row">
        <div class="col-12 mb-3">
            <label class="form-label d-flex align-items-center gap-1">
                <?php echo esc_html( $args['label'] ); ?>
                <?php 
                if ( $args['file_value'] && $args['show_icon'] ) {
                    echo $helper->get_icon_uploaded_file();
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
    </div>
</div>
