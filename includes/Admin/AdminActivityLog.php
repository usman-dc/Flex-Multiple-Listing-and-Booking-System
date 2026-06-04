<?php
/**
 * Read-only activity log rows for admin QA / audit trail.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Admin;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches ulbm_activity_logs for dashboard.
 */
final class AdminActivityLog {

	/**
	 * Recent log entries, newest first.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_recent( $limit ) {
		global $wpdb;

		$table = Schema::table( 'activity_logs' );
		$lim   = min( 200, max( 1, (int) $limit ) );
		if ( '' === $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY id DESC LIMIT %d',
				$table,
				$lim
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Total log rows.
	 *
	 * @return int
	 */
	public static function count_all() {
		global $wpdb;

		$table = Schema::table( 'activity_logs' );
		if ( '' === $table ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	}
}
