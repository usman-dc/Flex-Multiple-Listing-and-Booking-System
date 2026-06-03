<?php
/**
 * Core booking orchestration: validation, pricing hooks, persistence.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Booking;

use FlexBooking\Core\Container;
use FlexBooking\Customer\CustomerRepository;
use FlexBooking\Security\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates creation and mutation of bookings.
 */
final class BookingEngine {

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Services.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Create a booking from a normalized payload.
	 *
	 * @param array<string, mixed> $payload Input data.
	 * @return array<string, mixed> Result with id, uid, messages.
	 */
	public function create_booking( array $payload ) {
		$repo = $this->container->get( 'booking.repository' );

		$booking_type_id = isset( $payload['booking_type_id'] ) ? absint( $payload['booking_type_id'] ) : 0;
		$start           = isset( $payload['start'] ) ? $payload['start'] : '';
		$end             = isset( $payload['end'] ) ? $payload['end'] : '';

		$errors = array();
		if ( $booking_type_id < 1 ) {
			$errors[] = __( 'Invalid booking type.', 'flex-booking-system' );
		}
		if ( empty( $start ) || empty( $end ) ) {
			$errors[] = __( 'Start and end are required.', 'flex-booking-system' );
		}

		if ( apply_filters( 'fbs_require_booking_contact', true, $payload ) ) {
			$ce = isset( $payload['customer_email'] ) ? sanitize_email( (string) $payload['customer_email'] ) : '';
			if ( ! $ce || ! is_email( $ce ) ) {
				$errors[] = __( 'A valid email is required.', 'flex-booking-system' );
			}
			$ph = isset( $payload['customer_phone'] ) ? trim( (string) $payload['customer_phone'] ) : '';
			if ( '' === $ph ) {
				$errors[] = __( 'Mobile / phone is required.', 'flex-booking-system' );
			}
			$fn = isset( $payload['customer_first_name'] ) ? trim( (string) $payload['customer_first_name'] ) : '';
			$ln = isset( $payload['customer_last_name'] ) ? trim( (string) $payload['customer_last_name'] ) : '';
			if ( '' === $fn || '' === $ln ) {
				$errors[] = __( 'First and last name are required.', 'flex-booking-system' );
			}
		}

		if ( ! empty( $errors ) ) {
			return array(
				'success' => false,
				'errors'  => $errors,
			);
		}

		$uid   = $this->generate_uid();
		$total = apply_filters( 'fbs_calculate_booking_total', 0, $payload );

		$customer_id = isset( $payload['customer_id'] ) ? absint( $payload['customer_id'] ) : null;
		if ( ! $customer_id && ! empty( $payload['customer_email'] ) ) {
			$cust = new CustomerRepository();
			$customer_id = $cust->upsert_guest(
				sanitize_email( (string) $payload['customer_email'] ),
				(string) ( $payload['customer_first_name'] ?? '' ),
				(string) ( $payload['customer_last_name'] ?? '' ),
				(string) ( $payload['customer_phone'] ?? '' ),
				get_current_user_id() ? get_current_user_id() : null
			);
		}

		$data = array(
			'booking_uid'     => $uid,
			'booking_type_id' => $booking_type_id,
			'customer_id'     => $customer_id,
			'wp_user_id'      => get_current_user_id() ? get_current_user_id() : null,
			'status'          => 'pending',
			'payment_status'  => 'unpaid',
			'currency'        => Sanitizer::currency_code( $payload['currency'] ?? 'USD' ),
			'total'           => $total,
			'start_datetime'  => $this->normalize_datetime( $start ),
			'end_datetime'    => $this->normalize_datetime( $end ),
			'source'          => sanitize_key( $payload['source'] ?? 'web' ),
			'meta'            => isset( $payload['meta'] ) ? wp_json_encode( $payload['meta'] ) : null,
		);

		$data = apply_filters( 'fbs_before_insert_booking', $data, $payload );

		$booking_id = $repo->insert_booking( $data );

		if ( isset( $payload['form_values'] ) && is_array( $payload['form_values'] ) ) {
			$repo->add_meta( $booking_id, 'form_values', $payload['form_values'] );
		}

		do_action( 'fbs_booking_created', $booking_id, $payload );

		$logger = $this->container->get( 'logger' );
		$logger->log( 'booking', $booking_id, 'created', array( 'uid' => $uid ) );

		return array(
			'success'    => true,
			'booking_id' => $booking_id,
			'uid'        => $uid,
		);
	}

	/**
	 * Convert incoming datetime to mysql format.
	 *
	 * @param string $dt Datetime string.
	 * @return string
	 */
	private function normalize_datetime( $dt ) {
		$ts = strtotime( $dt );
		if ( ! $ts ) {
			return current_time( 'mysql' );
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Unique public reference.
	 *
	 * @return string
	 */
	private function generate_uid() {
		return 'FBS-' . strtoupper( wp_generate_password( 10, false, false ) );
	}
}
