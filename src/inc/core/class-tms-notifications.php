<?php

/**
 * TMS Notifications manager.
 *
 * Responsible for storing notifications in the database and
 * providing a simple API for sending and cleaning them up.
 *
 * This class is intentionally generic so it can be reused
 * for different roles (tracking, dispatchers, accounting, etc.).
 */
class TMSNotifications {

	/**
	 * Relative table name without WordPress prefix.
	 *
	 * Full name will be "{$wpdb->prefix}{$table_name}".
	 *
	 * @var string
	 */
	protected $table_name = 'tms_notifications';

	/**
	 * Default time to live for notifications in days.
	 */
	const DEFAULT_TTL_DAYS = 7;

	/**
	 * Default batch size for cleanup operation.
	 */
	const DEFAULT_CLEANUP_BATCH = 500;

	/**
	 * Constructor.
	 *
	 * Kept lightweight: table creation is handled lazily in methods via maybe_create_table().
	 */
	public function __construct() {
	}

	/**
	 * Get full table name with WordPress prefix.
	 *
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	/**
	 * Ensure notifications table exists (call on init so table is created without sending first).
	 *
	 * @return void
	 */
	public static function ensure_table() {
		$instance = new self();
		$instance->maybe_create_table();
	}

	/**
	 * Ensure notifications table exists.
	 *
	 * This uses a lightweight CREATE TABLE IF NOT EXISTS
	 * to avoid heavy dbDelta dependency for runtime calls.
	 *
	 * @return void
	 */
	protected function maybe_create_table() {
		global $wpdb;

		$table_name = $this->get_table_name();
		// Skip CREATE if table already exists (avoids query on every request when table is present).
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE IF NOT EXISTS {$table_name} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				role VARCHAR(100) NOT NULL DEFAULT '',
				type VARCHAR(100) NOT NULL DEFAULT '',
				title VARCHAR(255) NOT NULL DEFAULT '',
				message TEXT NULL,
				data LONGTEXT NULL,
				created_at DATETIME NOT NULL,
				read_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY role (role),
				KEY created_at (created_at)
			) {$charset_collate};
		";

		// Suppress errors to avoid breaking frontend if table cannot be created.
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Send notification(s) to users and/or roles.
	 *
	 * Usage example:
	 * $notifications = new TMSNotifications();
	 * $notifications->send(
	 *     array(
	 *         'type'     => 'load_created',
	 *         'title'    => 'New load created',
	 *         'message'  => 'Load #12345 has been created.',
	 *         'data'     => array( 'load_id' => 12345 ),
	 *         'user_ids' => array( 10, 11 ),
	 *         'roles'    => array( 'tracking' ), // optional
	 *     )
	 * );
	 *
	 * Supported args:
	 * - type        (string, required)  Short machine name, e.g. "load_created".
	 * - title       (string, required)  Short human readable title.
	 * - message     (string, optional)  Detailed text (can contain basic HTML).
	 * - data        (array, optional)   Structured payload (will be JSON encoded).
	 * - user_ids    (int|int[] optional) Single user ID or array of IDs.
	 * - roles       (string|string[] optional) Single role or array of roles.
	 * - ttl_days    (int, optional)     Override default TTL in days (>=1).
	 *
	 * Returns array of inserted notification IDs.
	 *
	 * @param array $args Notification arguments.
	 *
	 * @return int[] Array of inserted notification IDs.
	 */
	public function send( array $args ) {
		global $wpdb;

		$this->maybe_create_table();

		$table_name = $this->get_table_name();

		$type    = isset( $args['type'] ) ? sanitize_key( $args['type'] ) : '';
		$title   = isset( $args['title'] ) ? wp_strip_all_tags( $args['title'] ) : '';
		$message = isset( $args['message'] ) ? wp_kses_post( $args['message'] ) : '';
		$data    = isset( $args['data'] ) ? $args['data'] : array();

		if ( '' === $type || '' === $title ) {
			return array();
		}

		// Normalize user_ids to an array of unique integers.
		$user_ids = array();
		if ( isset( $args['user_ids'] ) ) {
			if ( is_array( $args['user_ids'] ) ) {
				$user_ids = array_map( 'absint', $args['user_ids'] );
			} else {
				$user_ids = array( absint( $args['user_ids'] ) );
			}

			$user_ids = array_filter( array_unique( $user_ids ) );
		}

		// Normalize roles to an array of unique role slugs.
		$roles = array();
		if ( isset( $args['roles'] ) ) {
			if ( is_array( $args['roles'] ) ) {
				$roles = $args['roles'];
			} else {
				$roles = array( $args['roles'] );
			}

			$roles = array_filter(
				array_unique(
					array_map(
						static function( $role ) {
							return sanitize_key( $role );
						},
						$roles
					)
				)
			);
		}

		if ( empty( $user_ids ) && empty( $roles ) ) {
			// No recipients defined, nothing to send.
			return array();
		}

		// TTL handling.
		$ttl_days = isset( $args['ttl_days'] ) ? (int) $args['ttl_days'] : self::DEFAULT_TTL_DAYS;
		if ( $ttl_days < 1 ) {
			$ttl_days = self::DEFAULT_TTL_DAYS;
		}

		$created_at = current_time( 'mysql' );

		$encoded_data = ! empty( $data ) ? wp_json_encode( $data ) : null;

		$inserted_ids = array();

		// Insert notifications for specific users.
		foreach ( $user_ids as $user_id ) {
			$inserted = $wpdb->insert(
				$table_name,
				array(
					'user_id'    => $user_id,
					'role'       => '',
					'type'       => $type,
					'title'      => $title,
					'message'    => $message,
					'data'       => $encoded_data,
					'created_at' => $created_at,
					'read_at'    => null,
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( false !== $inserted ) {
				$inserted_ids[] = (int) $wpdb->insert_id;
			}
		}

		// Insert broadcast notifications for roles (no specific user).
		foreach ( $roles as $role ) {
			$inserted = $wpdb->insert(
				$table_name,
				array(
					'user_id'    => 0,
					'role'       => $role,
					'type'       => $type,
					'title'      => $title,
					'message'    => $message,
					'data'       => $encoded_data,
					'created_at' => $created_at,
					'read_at'    => null,
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( false !== $inserted ) {
				$inserted_ids[] = (int) $wpdb->insert_id;
			}
		}

		return $inserted_ids;
	}

	/**
	 * Get notifications for a specific user (paginated).
	 *
	 * @param int  $user_id     User ID.
	 * @param int  $limit       Maximum number of notifications to return per page.
	 * @param bool $only_unread Whether to return only unread notifications.
	 * @param int  $page        Page number (1-based).
	 *
	 * @return array {
	 *   @type array $items        List of notifications.
	 *   @type int   $unread_count Total unread notifications for user.
	 *   @type int   $total_count  Total notifications matching filter (all pages).
	 * }
	 */
	public function get_user_notifications( $user_id, $limit = 20, $only_unread = false, $page = 1 ) {
		global $wpdb;

		$this->maybe_create_table();

		$table_name = $this->get_table_name();
		$user_id    = (int) $user_id;
		$limit      = (int) $limit;
		$page       = max( 1, (int) $page );
		$offset     = ( $page - 1 ) * $limit;

		if ( $user_id <= 0 ) {
			return array(
				'items'        => array(),
				'unread_count' => 0,
				'total_count'  => 0,
			);
		}

		if ( $limit < 1 ) {
			$limit = 20;
		}

		$where_clauses   = array( 'user_id = %d' );
		$where_values    = array( $user_id );
		$unread_where    = 'user_id = %d AND read_at IS NULL';
		$unread_where_values = array( $user_id );

		if ( $only_unread ) {
			$where_clauses[] = 'read_at IS NULL';
		}

		$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );

		// Total count (all pages).
		$sql_total = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} {$where_sql}",
			$where_values
		);
		$total_count = (int) $wpdb->get_var( $sql_total ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$sql_items = $wpdb->prepare(
			"SELECT id, type, title, message, data, created_at, read_at
			 FROM {$table_name}
			 {$where_sql}
			 ORDER BY created_at DESC
			 LIMIT %d OFFSET %d",
			array_merge( $where_values, array( $limit, $offset ) )
		);

		$rows = $wpdb->get_results( $sql_items, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$sql_unread = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE {$unread_where}",
			$unread_where_values
		);

		$unread_count = (int) $wpdb->get_var( $sql_unread ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$items = array();

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$decoded_data = null;
				if ( ! empty( $row['data'] ) ) {
					$decoded = json_decode( $row['data'], true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						$decoded_data = $decoded;
					}
				}

				$items[] = array(
					'id'         => (int) $row['id'],
					'type'       => $row['type'],
					'title'      => $row['title'],
					'message'    => $row['message'],
					'data'       => $decoded_data,
					'created_at' => $row['created_at'],
					'read_at'    => $row['read_at'],
				);
			}
		}

		return array(
			'items'        => $items,
			'unread_count' => $unread_count,
			'total_count'  => $total_count,
		);
	}

	/**
	 * Mark notifications as read for a specific user.
	 *
	 * @param int   $user_id User ID.
	 * @param int[] $ids     Notification IDs.
	 *
	 * @return int Number of updated rows.
	 */
	public function mark_notifications_read( $user_id, array $ids ) {
		global $wpdb;

		$this->maybe_create_table();

		$table_name = $this->get_table_name();
		$user_id    = (int) $user_id;

		if ( $user_id <= 0 ) {
			return 0;
		}

		$ids = array_filter(
			array_map(
				'intval',
				$ids
			),
			static function( $id ) {
				return $id > 0;
			}
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$now          = current_time( 'mysql' );

		$params = array_merge( array( $now, $user_id ), $ids );

		$sql = $wpdb->prepare(
			"UPDATE {$table_name}
			 SET read_at = %s
			 WHERE user_id = %d
			 AND id IN ({$placeholders})",
			$params
		);

		$updated = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $updated ) {
			return 0;
		}

		return (int) $updated;
	}

	/**
	 * Mark all notifications as read for a specific user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int Number of updated rows.
	 */
	public function mark_all_notifications_read( $user_id ) {
		global $wpdb;

		$this->maybe_create_table();

		$table_name = $this->get_table_name();
		$user_id    = (int) $user_id;

		if ( $user_id <= 0 ) {
			return 0;
		}

		$now = current_time( 'mysql' );

		$sql = $wpdb->prepare(
			"UPDATE {$table_name}
			 SET read_at = %s
			 WHERE user_id = %d
			 AND read_at IS NULL",
			$now,
			$user_id
		);

		$updated = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $updated ) {
			return 0;
		}

		return (int) $updated;
	}

	/**
	 * Delete all notifications for a specific user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int Number of deleted rows.
	 */
	public function delete_all_for_user( $user_id ) {
		global $wpdb;

		$this->maybe_create_table();

		$table_name = $this->get_table_name();
		$user_id    = (int) $user_id;

		if ( $user_id <= 0 ) {
			return 0;
		}

		$sql = $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE user_id = %d",
			$user_id
		);

		$deleted = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $deleted ) {
			return 0;
		}

		return (int) $deleted;
	}

	/**
	 * Cleanup old notifications in batches.
	 *
	 * This method is designed to be called periodically (for example from WP-Cron)
	 * and will delete the oldest notifications older than $days_back days,
	 * limited by $batch_size per call to avoid long-running queries.
	 *
	 * @param int $days_back  Number of days to keep (default: self::DEFAULT_TTL_DAYS).
	 * @param int $batch_size Maximum rows to delete per call.
	 *
	 * @return int Number of deleted rows.
	 */
	public function cleanup_old_notifications( $days_back = self::DEFAULT_TTL_DAYS, $batch_size = self::DEFAULT_CLEANUP_BATCH ) {
		global $wpdb;

		$this->maybe_create_table();

		$days_back  = (int) $days_back;
		$batch_size = (int) $batch_size;

		if ( $days_back < 1 ) {
			$days_back = self::DEFAULT_TTL_DAYS;
		}

		if ( $batch_size < 1 ) {
			$batch_size = self::DEFAULT_CLEANUP_BATCH;
		}

		$table_name = $this->get_table_name();

		// Calculate cutoff datetime.
		$cutoff_timestamp = current_time( 'timestamp' ) - ( $days_back * DAY_IN_SECONDS );
		$cutoff_datetime  = gmdate( 'Y-m-d H:i:s', $cutoff_timestamp );

		$sql = $wpdb->prepare(
			// Order by created_at to always delete the oldest first.
			"DELETE FROM {$table_name}
			 WHERE created_at < %s
			 ORDER BY created_at ASC
			 LIMIT %d",
			$cutoff_datetime,
			$batch_size
		);

		$deleted = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $deleted ) {
			return 0;
		}

		return (int) $deleted;
	}
}

