<?php
$helper = new TMSReportsHelper();

$user_id = get_field_value( $args, 'user_id' );
$post_id = get_field_value( $args, 'post_id' );

$logs          = new TMSLogs();
$logs_messages = $logs->get_user_logs_by_post( $post_id, $user_id );
?>

<div class="w-100">
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
        <div class="w-100">
            <label for="exampleFormControlTextarea1" class="form-label">Your message</label>
            <textarea class="form-control" name="message" id="exampleFormControlTextarea1"></textarea>
        </div>
        <button class="btn btn-primary">Send</button>
    </form>
</div>