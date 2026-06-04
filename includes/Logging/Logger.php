<?php
/**
 * Structured activity logging to database + optional error_log.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Logging;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Persists auditable events to ulbm_activity_logs.
 */
final class Logger {

	/**
	 * Log an event.
	 *
	 * @param string               $context  Domain: booking, payment, etc.
	 * @param int                  $object_id Related object id.
	 * @param string               $action   Machine key.
	 * @param array<string, mixed> $payload   JSON-serializable data.
	 * @return void
	 */
	public function log( $context, $object_id, $action, $payload = array() ) {
		global $wpdb;

		$table = Schema::table( 'activity_logs' );
		if ( '' === $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			array(
				'context'       => sanitize_key( $context ),
				'object_id'     => absint( $object_id ),
				'action'        => sanitize_key( $action ),
				'actor_user_id' => get_current_user_id() ? get_current_user_id() : null,
				'payload'       => wp_json_encode( $payload ),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%d', '%s', '%s' )
		);
	}
}
