<?php
$id        = get_field_value( $args, 'id' );
$meta      = get_field_value( $args, 'meta' );
$hide_ctrl = get_field_value( $args, 'hide_ctrl' );
$TMSUsers  = new TMSUsers();

// Get pinned messages - support both old and new format
$pinned_messages_json = get_field_value( $meta, 'message_pinned' );
$pinned_messages = array();

if ( ! empty( $pinned_messages_json ) ) {
	// get_field_value already uses stripslashes, but we need to ensure proper handling
	// If it's already a string (not an array), try to decode it
	if ( is_string( $pinned_messages_json ) ) {
		// get_field_value already applies stripslashes, but we need wp_unslash for consistency
		$pinned_messages_json_clean = wp_unslash( $pinned_messages_json );
		
		// Try to unserialize (new format using PHP serialize)
		$unserialized = @unserialize( $pinned_messages_json_clean );
		if ( $unserialized !== false && is_array( $unserialized ) ) {
			// New format: serialized PHP array
			$pinned_messages = $unserialized;
		} else {
			// Try JSON format (for backward compatibility with old data)
			$trimmed = trim( $pinned_messages_json_clean );
			if ( ! empty( $trimmed ) && ( $trimmed[0] === '[' || $trimmed[0] === '{' ) ) {
				// Try to decode JSON
				$decoded = json_decode( $pinned_messages_json_clean, true );
				
				// Check if json_decode was successful and returned an array
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) && ! empty( $decoded ) ) {
					// Validate that all items in array have required fields
					$valid_messages = array();
					foreach ( $decoded as $msg ) {
						if ( is_array( $msg ) && isset( $msg['user_pinned_id'] ) && isset( $msg['time_pinned'] ) && isset( $msg['message_pinned'] ) ) {
							$valid_messages[] = $msg;
						}
					}
					if ( ! empty( $valid_messages ) ) {
						// JSON format: convert to array
						$pinned_messages = $valid_messages;
					}
				}
			}
			
			// If we still don't have messages, try old format
			if ( empty( $pinned_messages ) ) {
				$trimmed = trim( $pinned_messages_json_clean );
				// Only use old format if it doesn't look like JSON (not starting with [ or {)
				if ( empty( $trimmed ) || ( $trimmed[0] !== '[' && $trimmed[0] !== '{' ) ) {
					$time_pinned_old = get_field_value( $meta, 'time_pinned' );
					$user_pinned_id_old = get_field_value( $meta, 'user_pinned_id' );
					if ( ! empty( $pinned_messages_json_clean ) && ! empty( $time_pinned_old ) && ! empty( $user_pinned_id_old ) ) {
						$pinned_messages[] = array(
							'user_pinned_id' => intval( $user_pinned_id_old ),
							'time_pinned'    => intval( $time_pinned_old ),
							'message_pinned' => $pinned_messages_json_clean,
						);
					}
				}
			}
		}
	} elseif ( is_array( $pinned_messages_json ) ) {
		// Already an array (shouldn't happen, but handle it)
		$pinned_messages = $pinned_messages_json;
	}
}

// Display all pinned messages
if ( ! empty( $pinned_messages ) ) {
	foreach ( $pinned_messages as $index => $pinned_data ) {
		$pinned_message = $pinned_data[ 'message_pinned' ];
		$time_pinned = $pinned_data[ 'time_pinned' ];
		$user_pinned_id = $pinned_data[ 'user_pinned_id' ];
		
		if ( $pinned_message && $time_pinned && $user_pinned_id ) {
			$name_user = $TMSUsers->get_user_full_name_by_id( $user_pinned_id );
			$time_pinned_formatted = date( 'm/d/Y H:i', $time_pinned );
			?>
			<div class="pinned-message">
				<div class="d-flex justify-content-between align-items-center pinned-message__header">
					<span class="d-flex align-items-center ">
						<svg fill="#000000" width="18px" height="18px" viewBox="0 0 32 32" version="1.1"
						     xmlns="http://www.w3.org/2000/svg">
							<path d="M18.973 17.802l-7.794-4.5c-0.956-0.553-2.18-0.225-2.732 0.731-0.552 0.957-0.224 2.18 0.732 2.732l7.793 4.5c0.957 0.553 2.18 0.225 2.732-0.732 0.554-0.956 0.226-2.179-0.731-2.731zM12.545 12.936l6.062 3.5 2.062-5.738-4.186-2.416-3.938 4.654zM8.076 27.676l5.799-7.044-2.598-1.5-3.201 8.544zM23.174 7.525l-5.195-3c-0.718-0.414-1.635-0.169-2.049 0.549-0.415 0.718-0.168 1.635 0.549 2.049l5.196 3c0.718 0.414 1.635 0.169 2.049-0.549s0.168-1.635-0.55-2.049z"></path>
						</svg>
						<?php echo esc_html( $name_user[ 'full_name' ] ); ?></span>
					<span><?php echo esc_html( $time_pinned_formatted ); ?></span>
				</div>
				<div class="pinned-message__content">
					<?php echo esc_html( $pinned_message ); ?>
				</div>
				<?php if ( ! $hide_ctrl ): ?>
					<div class="pinned-message__footer d-flex justify-content-end">
						<button class="btn btn-danger btn-sm js-delete-pinned-message" 
						        data-id="<?php echo esc_attr( $id ); ?>" 
						        data-message-index="<?php echo esc_attr( $index ); ?>">
							Remove
						</button>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
?>