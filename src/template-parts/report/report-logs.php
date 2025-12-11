<?php
$helper = new TMSReportsHelper();

$user_id   = get_field_value( $args, 'user_id' );
$post_id   = get_field_value( $args, 'post_id' );
$post_type = get_field_value( $args, 'post_type' );
$meta      = get_field_value( $args, 'meta' );
$project   = get_field_value( $args, 'project' );


if ( ! $post_type ) {
	$post_type = 'report';
}

$userHelper    = new TMSUsers();
$logs          = new TMSLogs();
$logs_messages = $logs->get_user_logs_by_post( $post_id, $user_id, $post_type );
?>

<div class="w-100 sticky-top">
	
	<?php if ( $post_type === 'reports_flt' ): ?>
        <input type="hidden" name="flt" value="<?php echo $post_type; ?>">
	<?php endif; ?>

    <h4 class="mb-2 log-title-container">
        <span>Logs</span>
        <button class="js-hide-logs">
			<?php echo $helper->get_icon_logs(); ?>
            <span class="log-title-container__small">Logs</span>
        </button>
    </h4>
    <div class="log js-log-container">
		
		<?php echo $logs_messages; ?>

    </div>
    <form class="mb-3 d-flex align-items-end gap-1 mt-2 js-log-message log-message">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        <input type="hidden" name="post_type" value="<?php echo $post_type; ?>">
        <input type="hidden" name="project" value="<?php echo $project; ?>">
        <div class="w-100">
            <label for="exampleFormControlTextarea1" class="form-label">Your message</label>
            <textarea class="form-control" name="message" id="exampleFormControlTextarea1"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
	
	<?php if ( $post_type === 'report' || $post_type === 'reports_flt' ) :
		?>
        <div class="js-pin-container d-flex flex-column gap-1">
			<?php
			get_template_part( TEMPLATE_PATH . 'common/pinned', 'message', array(
				'id'   => $post_id,
				'meta' => $meta,
			) );
			?>
        </div>

        <form class="mb-3 d-flex align-items-end gap-1 mt-2 js-pinned-message log-message">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <input type="hidden" name="project" value="<?php echo $project; ?>">
            <div class="w-100">
                <label for="pinn" class="form-label">Pinned message</label>
                <textarea class="form-control" name="pinned_message" id="pinn"></textarea>
            </div>
            <button class="btn btn-success">Save</button>
        </form>
	<?php endif; ?>
</div>