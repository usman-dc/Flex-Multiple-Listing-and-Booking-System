<?php
/**
 * Customer email when booking status changes (wp_mail).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Sends transactional emails for booking workflow.
 */
final class BookingNotifier {

	/**
	 * Merged notification settings from fbs_general_settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$raw = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$defaults = array(
			'notify_customer_status' => true,
			'notify_on_confirmed'    => true,
			'notify_on_completed'    => true,
			'notify_on_cancelled'    => true,
			'notify_on_rejected'     => true,
			'notify_on_on_hold'      => true,
			'notify_on_pending'      => false,
			'notify_reply_to'        => '',
		);

		return array_merge( $defaults, $raw );
	}

	/**
	 * Whether this transition should trigger email per settings.
	 *
	 * @param string $new_status Normalized status.
	 * @return bool
	 */
	public static function should_notify_for_status( $new_status ) {
		$s  = self::settings();
		$st = sanitize_key( (string) $new_status );

		if ( empty( $s['notify_customer_status'] ) ) {
			return false;
		}

		$key = array(
			'confirmed'  => 'notify_on_confirmed',
			'completed'  => 'notify_on_completed',
			'cancelled'  => 'notify_on_cancelled',
			'rejected'   => 'notify_on_rejected',
			'on_hold'    => 'notify_on_on_hold',
			'pending'    => 'notify_on_pending',
		);

		if ( ! isset( $key[ $st ] ) ) {
			return false;
		}

		return ! empty( $s[ $key[ $st ] ] );
	}

	/**
	 * Resolve recipient email for a booking.
	 *
	 * @param array<string, mixed> $booking Row from fbs_bookings.
	 * @return string
	 */
	public static function recipient_email( array $booking ) {
		$repo = new BookingRepository();
		$cid  = ! empty( $booking['customer_id'] ) ? (int) $booking['customer_id'] : 0;
		if ( $cid > 0 ) {
			$map = $repo->get_customer_emails_by_ids( array( $cid ) );
			if ( ! empty( $map[ $cid ] ) && is_email( $map[ $cid ] ) ) {
				return $map[ $cid ];
			}
		}

		$uid = ! empty( $booking['wp_user_id'] ) ? (int) $booking['wp_user_id'] : 0;
		if ( $uid > 0 ) {
			$user = get_userdata( $uid );
			if ( $user && is_email( $user->user_email ) ) {
				return $user->user_email;
			}
		}

		return '';
	}

	/**
	 * Send status-change email if allowed.
	 *
	 * @param array<string, mixed> $booking Row after update.
	 * @param string               $old_status Previous status.
	 * @param string               $new_status New status.
	 * @return bool True if wp_mail reported send (best-effort).
	 */
	public static function notify_booking_status_change( array $booking, $old_status, $new_status ) {
		if ( (string) $old_status === (string) $new_status ) {
			return false;
		}

		if ( ! self::should_notify_for_status( $new_status ) ) {
			return false;
		}

		$to = self::recipient_email( $booking );
		$to = apply_filters( 'fbs_booking_status_email_recipient', $to, $booking, $old_status, $new_status );
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$s      = self::settings();
		$site   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$uid    = isset( $booking['booking_uid'] ) ? (string) $booking['booking_uid'] : '';
		/* translators: 1: site name, 2: booking reference */
		$subject = sprintf( __( '[%1$s] Booking update: %2$s', 'flex-multiple-listing-and-booking-system' ), $site, $uid );
		$subject = apply_filters( 'fbs_booking_status_email_subject', $subject, $booking, $old_status, $new_status );

		$labels = array(
			'pending'   => __( 'Pending', 'flex-multiple-listing-and-booking-system' ),
			'confirmed' => __( 'Confirmed', 'flex-multiple-listing-and-booking-system' ),
			'on_hold'   => __( 'On hold', 'flex-multiple-listing-and-booking-system' ),
			'completed' => __( 'Completed', 'flex-multiple-listing-and-booking-system' ),
			'cancelled' => __( 'Cancelled', 'flex-multiple-listing-and-booking-system' ),
			'rejected'  => __( 'Rejected', 'flex-multiple-listing-and-booking-system' ),
		);
		$label_new = $labels[ sanitize_key( $new_status ) ] ?? $new_status;
		$label_old = $labels[ sanitize_key( $old_status ) ] ?? $old_status;

		$start = isset( $booking['start_datetime'] ) ? (string) $booking['start_datetime'] : '';
		$end   = isset( $booking['end_datetime'] ) ? (string) $booking['end_datetime'] : '';
		$total = isset( $booking['total'] ) ? number_format_i18n( (float) $booking['total'], 2 ) : '';
		$cur   = isset( $booking['currency'] ) ? (string) $booking['currency'] : '';

		$body_lines = array(
			__( 'Hello,', 'flex-multiple-listing-and-booking-system' ),
			'',
			sprintf(
				/* translators: 1: booking reference */
				__( 'Your booking (reference %s) has been updated.', 'flex-multiple-listing-and-booking-system' ),
				$uid
			),
			sprintf(
				/* translators: 1: previous status label, 2: new status label */
				__( 'Status: %1$s → %2$s', 'flex-multiple-listing-and-booking-system' ),
				$label_old,
				$label_new
			),
		);

		if ( $start ) {
			$body_lines[] = sprintf(
				/* translators: %s: start datetime */
				__( 'Start: %s', 'flex-multiple-listing-and-booking-system' ),
				$start
			);
		}
		if ( $end ) {
			$body_lines[] = sprintf(
				/* translators: %s: end datetime */
				__( 'End: %s', 'flex-multiple-listing-and-booking-system' ),
				$end
			);
		}
		if ( $total ) {
			$body_lines[] = sprintf(
				/* translators: 1: amount, 2: currency */
				__( 'Total: %1$s %2$s', 'flex-multiple-listing-and-booking-system' ),
				$total,
				$cur
			);
		}

		$body_lines[] = '';
		$body_lines[] = __( 'If you have questions, reply to this email.', 'flex-multiple-listing-and-booking-system' );

		$message = implode( "\n", $body_lines );
		$message = apply_filters( 'fbs_booking_status_email_body', $message, $booking, $old_status, $new_status );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( ! empty( $s['notify_reply_to'] ) && is_email( $s['notify_reply_to'] ) ) {
			$headers[] = 'Reply-To: ' . sanitize_email( (string) $s['notify_reply_to'] );
		}

		$headers = apply_filters( 'fbs_booking_status_email_headers', $headers, $booking, $old_status, $new_status );

		return (bool) wp_mail( $to, $subject, $message, $headers );
	}
}
