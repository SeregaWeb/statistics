<?php
/**
 * Birthday emails cron (Action Scheduler or WP-Cron fallback).
 */

/**
 * Test birthday greeting email
 * Usage: ?test_birthday_email=1 (admin only)
 */
function test_birthday_email() {
	if ( ! isset( $_GET['test_birthday_email'] ) || $_GET['test_birthday_email'] !== '1' ) {
		return;
	}

	// Only for admins
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}

	$to       = 'milchenko2k16@gmail.com';
	$subject  = 'Happy Birthday from Odysseia';
	$headers  = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: TMS <no-reply@odysseia-transport.com>',
	);

	$upload_dir = wp_upload_dir();
	$image_url  = trailingslashit( $upload_dir['baseurl'] ) . 'events/happy-birthday-driver-2.png';

	$message = tms_build_birthday_email_html( $image_url );
	$result  = wp_mail( $to, $subject, $message, $headers );

	$result_message = 'Birthday email test completed.<br><br>';
	if ( $result ) {
		$result_message .= '<p style="color: green;"><strong>✅ Email sent successfully!</strong></p>';
	} else {
		$result_message .= '<p style="color: red;"><strong>❌ Email sending failed!</strong></p>';
	}

	$result_message .= '<p><strong>To:</strong> ' . esc_html( $to ) . '</p>';
	$result_message .= '<p><strong>From:</strong> no-reply@odysseia-transport.com</p>';
	$result_message .= '<br><a href="' . esc_url( home_url() ) . '">← Back to home</a>';

	wp_die(
		$result_message,
		'Birthday Email Test',
		array( 'response' => 200 )
	);
}
add_action( 'template_redirect', 'test_birthday_email' );

/**
 * Schedule daily birthday greetings via Action Scheduler (fallback to WP-Cron if AS missing).
 */
add_action( 'init', function () {
	// Use Action Scheduler if available
	if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
		if ( ! as_has_scheduled_action( 'tms_send_daily_birthday_emails' ) ) {
			// Schedule daily at 07:00 server time
			$first_run = strtotime( 'today 07:00:00' );
			if ( $first_run <= time() ) {
				$first_run = strtotime( 'tomorrow 07:00:00' );
			}
			as_schedule_recurring_action( $first_run, DAY_IN_SECONDS, 'tms_send_daily_birthday_emails' );
		}
	} else {
		// Fallback: WP-Cron daily
		if ( ! wp_next_scheduled( 'tms_send_daily_birthday_emails' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 07:00:00' ), 'daily', 'tms_send_daily_birthday_emails' );
		}
	}
} );

/**
 * Birthday email sender - daily job.
 */
add_action( 'tms_send_daily_birthday_emails', function () {
	global $wpdb;

	$today_md = date( 'm/d' ); // Compare month/day only

	// Collect relevant meta for all posts (drivers)
	$meta_keys = array(
		'dob',
		'driver_email',
		'team_driver_dob',
		'team_driver_email',
		'owner_dob',
		'owner_email',
	);

	$table_meta   = $wpdb->prefix . 'drivers_meta';
	$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
	$query        = $wpdb->prepare(
		"SELECT post_id, meta_key, meta_value FROM {$table_meta} WHERE meta_key IN ($placeholders)",
		$meta_keys
	);
	$rows = $wpdb->get_results( $query, ARRAY_A );

	if ( ! $rows ) {
		error_log( '[BirthdayCron] No meta rows found for birthday processing.' );
		return;
	}

	// Group meta by post_id
	$by_post = array();
	foreach ( $rows as $row ) {
		$post_id                                = intval( $row['post_id'] );
		$by_post[ $post_id ][ $row['meta_key'] ] = $row['meta_value'];
	}

	$emails_to_send = array(); // unique emails

	foreach ( $by_post as $meta ) {
		// Driver
		if ( ! empty( $meta['dob'] ) && ! empty( $meta['driver_email'] ) && tms_birthday_is_today( $meta['dob'], $today_md ) ) {
			$emails_to_send[ $meta['driver_email'] ] = true;
		}
		// Team driver
		if ( ! empty( $meta['team_driver_dob'] ) && ! empty( $meta['team_driver_email'] ) && tms_birthday_is_today( $meta['team_driver_dob'], $today_md ) ) {
			$emails_to_send[ $meta['team_driver_email'] ] = true;
		}
		// Owner
		if ( ! empty( $meta['owner_dob'] ) && ! empty( $meta['owner_email'] ) && tms_birthday_is_today( $meta['owner_dob'], $today_md ) ) {
			$emails_to_send[ $meta['owner_email'] ] = true;
		}
	}

	if ( empty( $emails_to_send ) ) {
		error_log( '[BirthdayCron] No birthdays today.' );
		return;
	}

	$upload_dir = wp_upload_dir();
	$image_url  = trailingslashit( $upload_dir['baseurl'] ) . 'events/happy-birthday-driver-2.png';

	$subject = 'Happy Birthday from Odysseia';
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: TMS <no-reply@odysseia-transport.com>',
	);

	$sent   = 0;
	$failed = 0;

	foreach ( array_keys( $emails_to_send ) as $email ) {
		$message = tms_build_birthday_email_html( $image_url );
		$result  = wp_mail( $email, $subject, $message, $headers );

		if ( $result ) {
			$sent ++;
			error_log( '[BirthdayCron] Sent birthday email to: ' . $email );
		} else {
			$failed ++;
			error_log( '[BirthdayCron] FAILED to send birthday email to: ' . $email );
		}
	}

	error_log( sprintf( '[BirthdayCron] Done. Sent: %d, Failed: %d, Total unique: %d', $sent, $failed, count( $emails_to_send ) ) );
} );

/**
 * Helper: compare DOB month/day with today.
 */
function tms_birthday_is_today( $date_string, $today_md ) {
	// Expected format m/d/Y (e.g., 11/17/2025). We match month/day only.
	$dt = DateTime::createFromFormat( 'm/d/Y', $date_string );
	if ( ! $dt ) {
		// Try alternative separators if needed
		$dt = DateTime::createFromFormat( 'm-d-Y', $date_string );
	}
	if ( ! $dt ) {
		return false;
	}

	return $dt->format( 'm/d' ) === $today_md;
}

/**
 * Helper: build birthday email HTML.
 */
function tms_build_birthday_email_html( $image_url ) {
	$message = '
		<html>
		<head>
			<title>Happy Birthday</title>
		</head>
		<body style="font-family: Arial, sans-serif; background: #f8f9fb; margin: 0; padding: 0;">
			<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f9fb; padding: 24px 0;">
				<tr>
					<td align="center">
						<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; padding:32px; box-shadow:0 6px 18px rgba(0,0,0,0.06);">
							<tr>
								<td style="text-align:left; color:#1a1a1a; font-size:16px; line-height:1.6;">
									<p style="margin:0 0 12px 0;"><span style="font-weight: bold; color:#e55353;">Happy Birthday</span> to a truly invaluable member of our driver team!</p>
									<p style="margin:0 0 12px 0;">May all life\'s blessings be yours, on your birthday and always. Odysseia wishing you many more happy days ahead!</p>
									<p style="margin:0 0 12px 0;">Thank you for being a vital part of our company.</p>
									<p style="margin:0 0 20px 0;">Warmest wishes,<br>Odysseia🤍</p>
									<div style="text-align:center; margin-top:20px;">
										<img src="' . esc_url( $image_url ) . '" alt="Happy Birthday" style="max-width:100%; border-radius:10px;" />
									</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
	';

	return $message;
}

