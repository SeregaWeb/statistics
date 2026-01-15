<?php
/**
 * Universal logging class for TMS Statistics
 * Allows creating separate log files for different modules
 * 
 * Usage:
 * TMSLogger::log_to_file('Message', 'dispatcher-transfer');
 * TMSLogger::log_to_file('Message', 'emails');
 * TMSLogger::log_to_file('Message', 'drivers');
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TMSLogger {
	
	const LOG_DIR = 'tms-logs'; // Subdirectory in wp-content
	const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
	
	/**
	 * Log message to separate file
	 *
	 * @param string $message Message to log
	 * @param string $log_type Log type (e.g., 'dispatcher-transfer', 'emails', 'drivers')
	 * @return void
	 */
	public static function log_to_file( $message, $log_type = 'general' ) {
		// Sanitize log type (only alphanumeric, dash, underscore)
		$log_type = preg_replace( '/[^a-z0-9_-]/i', '', $log_type );
		if ( empty( $log_type ) ) {
			$log_type = 'general';
		}
		
		// Create log directory if it doesn't exist
		$log_dir = WP_CONTENT_DIR . '/' . self::LOG_DIR;
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}
		
		// Log file path
		$log_file = $log_dir . '/' . $log_type . '.log';
		
		// Rotate log file if it's larger than MAX_FILE_SIZE
		if ( file_exists( $log_file ) && filesize( $log_file ) > self::MAX_FILE_SIZE ) {
			$backup_file = $log_file . '.' . date( 'Y-m-d' ) . '.bak';
			if ( ! file_exists( $backup_file ) ) {
				rename( $log_file, $backup_file );
			}
		}
		
		// Create log file if it doesn't exist
		if ( ! file_exists( $log_file ) ) {
			touch( $log_file );
			chmod( $log_file, 0644 );
		}
		
		// Format log entry
		$timestamp = date( 'Y-m-d H:i:s' );
		$log_entry = sprintf( "[%s] %s\n", $timestamp, $message );
		
		// Write to file
		file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}
	
	/**
	 * Get log file path
	 *
	 * @param string $log_type Log type
	 * @return string Log file path
	 */
	public static function get_log_file_path( $log_type = 'general' ) {
		$log_type = preg_replace( '/[^a-z0-9_-]/i', '', $log_type );
		if ( empty( $log_type ) ) {
			$log_type = 'general';
		}
		
		return WP_CONTENT_DIR . '/' . self::LOG_DIR . '/' . $log_type . '.log';
	}
	
	/**
	 * Get log file URL
	 *
	 * @param string $log_type Log type
	 * @return string Log file URL
	 */
	public static function get_log_file_url( $log_type = 'general' ) {
		$log_type = preg_replace( '/[^a-z0-9_-]/i', '', $log_type );
		if ( empty( $log_type ) ) {
			$log_type = 'general';
		}
		
		return content_url( '/' . self::LOG_DIR . '/' . $log_type . '.log' );
	}
	
	/**
	 * Check if log file exists
	 *
	 * @param string $log_type Log type
	 * @return bool
	 */
	public static function log_file_exists( $log_type = 'general' ) {
		$log_file = self::get_log_file_path( $log_type );
		return file_exists( $log_file );
	}
	
	/**
	 * Get log file size
	 *
	 * @param string $log_type Log type
	 * @return int File size in bytes, 0 if file doesn't exist
	 */
	public static function get_log_file_size( $log_type = 'general' ) {
		$log_file = self::get_log_file_path( $log_type );
		return file_exists( $log_file ) ? filesize( $log_file ) : 0;
	}
	
	/**
	 * Get log file modification time
	 *
	 * @param string $log_type Log type
	 * @return int|false Timestamp or false if file doesn't exist
	 */
	public static function get_log_file_mtime( $log_type = 'general' ) {
		$log_file = self::get_log_file_path( $log_type );
		return file_exists( $log_file ) ? filemtime( $log_file ) : false;
	}
	
	/**
	 * Clear log file
	 *
	 * @param string $log_type Log type
	 * @return bool True on success, false on failure
	 */
	public static function clear_log_file( $log_type = 'general' ) {
		$log_file = self::get_log_file_path( $log_type );
		if ( file_exists( $log_file ) ) {
			return unlink( $log_file );
		}
		return true; // File doesn't exist, consider it cleared
	}
}
