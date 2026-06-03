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
 * Fetches fbs_activity_logs for dashboard.
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

		$table = Schema::tables()['activity_logs'];
		$lim   = min( 200, max( 1, (int) $limit ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d",
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

		$table = Schema::tables()['activity_logs'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
	}
}
