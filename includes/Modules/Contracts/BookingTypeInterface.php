<?php
/**
 * Contract for pluggable booking vertical modules (hotel, car, appointment, ...).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Modules\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Booking type module interface — implemented per vertical.
 */
interface BookingTypeInterface {

	/**
	 * Unique module key, e.g. hotel, car_rental.
	 *
	 * @return string
	 */
	public function get_key();

	/**
	 * Human label.
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Default settings schema (used by admin UI JSON forms).
	 *
	 * @return array<string, mixed>
	 */
	public function default_settings();

	/**
	 * Validate booking-specific payload fragment.
	 *
	 * @param array<string, mixed> $payload Booking payload.
	 * @return array<int, string> Error messages.
	 */
	public function validate_payload( array $payload );

	/**
	 * Compute price fragment for this module (added to engine total via filter).
	 *
	 * @param array<string, mixed> $payload Booking payload.
	 * @return float
	 */
	public function calculate_price_fragment( array $payload );
}
