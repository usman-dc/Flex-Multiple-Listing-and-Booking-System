<?php
/**
 * Admin booking status / payment updates with validation and audit log.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Booking;

use FlexBooking\Core\Plugin;
use FlexBooking\Logging\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Validates and persists booking workflow changes from wp-admin.
 */
final class BookingAdminUpdater {

	/**
	 * Allowed booking lifecycle statuses.
	 *
	 * @return string[]
	 */
	public static function booking_statuses() {
		return array(
			'pending',
			'confirmed',
			'on_hold',
			'completed',
			'cancelled',
			'rejected',
		);
	}

	/**
	 * Allowed payment statuses for admin updates.
	 *
	 * @return string[]
	 */
	public static function payment_statuses() {
		return array(
			'unpaid',
			'pending',
			'paid',
			'partial',
			'refunded',
			'failed',
		);
	}

	/**
	 * Apply status and/or payment change.
	 *
	 * @param int         $booking_id      Booking id.
	 * @param string|null $new_status       Null to skip.
	 * @param string|null $new_payment      Null to skip.
	 * @param bool        $send_notification Whether to email customer (subject to settings).
	 * @return array<string, mixed>|\WP_Error Success payload or error.
	 */
	public static function update( $booking_id, $new_status, $new_payment, $send_notification ) {
		$repo = new BookingRepository();
		$row  = $repo->get_by_id( $booking_id );
		if ( ! $row ) {
			return new \WP_Error( 'fbs_not_found', __( 'Booking not found.', 'flex-multiple-listing-and-booking-system' ), array( 'status' => 404 ) );
		}

		$data    = array();
		$old_st  = (string) $row['status'];
		$old_pay = (string) $row['payment_status'];

		if ( null !== $new_status && '' !== $new_status ) {
			$st = sanitize_key( (string) $new_status );
			if ( ! in_array( $st, self::booking_statuses(), true ) ) {
				return new \WP_Error( 'fbs_bad_status', __( 'Invalid booking status.', 'flex-multiple-listing-and-booking-system' ), array( 'status' => 400 ) );
			}
			if ( $st !== $old_st ) {
				$data['status'] = $st;
			}
		}

		if ( null !== $new_payment && '' !== $new_payment ) {
			$py = sanitize_key( (string) $new_payment );
			if ( ! in_array( $py, self::payment_statuses(), true ) ) {
				return new \WP_Error( 'fbs_bad_payment', __( 'Invalid payment status.', 'flex-multiple-listing-and-booking-system' ), array( 'status' => 400 ) );
			}
			if ( $py !== $old_pay ) {
				$data['payment_status'] = $py;
			}
		}

		if ( empty( $data ) ) {
			return new \WP_Error( 'fbs_no_change', __( 'Nothing to update.', 'flex-multiple-listing-and-booking-system' ), array( 'status' => 400 ) );
		}

		$repo->update_booking( (int) $row['id'], $data );

		$fresh = $repo->get_by_id( (int) $row['id'] );
		if ( ! $fresh ) {
			return new \WP_Error( 'fbs_reload', __( 'Update failed to persist.', 'flex-multiple-listing-and-booking-system' ), array( 'status' => 500 ) );
		}

		try {
			$plugin = Plugin::instance();
			$logger = $plugin->container()->get( 'logger' );
			if ( $logger instanceof Logger ) {
				$logger->log(
					'booking',
					(int) $fresh['id'],
					'admin_update',
					array(
						'old_status'           => $old_st,
						'new_status'           => (string) $fresh['status'],
						'old_payment_status'   => $old_pay,
						'new_payment_status'   => (string) $fresh['payment_status'],
					)
				);
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		$mailed = false;
		if ( $send_notification && isset( $data['status'] ) ) {
			$mailed = BookingNotifier::notify_booking_status_change( $fresh, $old_st, (string) $fresh['status'] );
		}

		return array(
			'booking' => $fresh,
			'emailed' => $mailed,
		);
	}
}
