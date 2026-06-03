<?php
/**
 * Default booking module — suitable for custom configured types.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Modules;

use FlexBooking\Modules\Contracts\BookingTypeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Fallback module when no specialized handler is registered.
 */
final class GenericBookingType implements BookingTypeInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_key() {
		return 'generic';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return __( 'Generic / Custom', 'flex-multiple-listing-and-booking-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function default_settings() {
		return array(
			'mode' => 'daily',
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_payload( array $payload ) {
		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function calculate_price_fragment( array $payload ) {
		return 0.0;
	}
}
