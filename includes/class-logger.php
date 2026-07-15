<?php
namespace MANSMTP;

defined( 'ABSPATH' ) || exit;

class Logger {

	private const TABLE = 'mansmtp_log';

	public static function install(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			to_email VARCHAR(255) NOT NULL,
			subject VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'sent',
			response TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_status (status),
			INDEX idx_created (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function add( array $data ): int {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . self::TABLE,
			array(
				'to_email' => $data['to_email'] ?? '',
				'subject'  => $data['subject'] ?? '',
				'status'   => $data['status'] ?? 'sent',
				'response' => $data['response'] ?? '',
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	public static function get_recent( int $limit = 20 ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT * FROM " . $table . " ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);
	}

	public static function clean( int $days = 30 ): void {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"DELETE FROM " . $table . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}
}
