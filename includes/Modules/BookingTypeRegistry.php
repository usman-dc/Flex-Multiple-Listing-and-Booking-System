<?php
/**
 * Registry for booking module implementations — third parties register via filter.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Modules;

use FlexBooking\Modules\Contracts\BookingTypeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Maps module_key string => BookingTypeInterface.
 */
final class BookingTypeRegistry {

	/**
	 * Cached instances.
	 *
	 * @var array<string, BookingTypeInterface>|null
	 */
	private $modules = null;

	/**
	 * All registered modules.
	 *
	 * @return array<string, BookingTypeInterface>
	 */
	public function all() {
		if ( null === $this->modules ) {
			$defaults = array(
				'generic' => new GenericBookingType(),
			);

			/**
			 * Register custom booking type modules.
			 *
			 * @param array<string, BookingTypeInterface> $defaults Built-ins.
			 */
			$this->modules = apply_filters( 'fbs_register_booking_modules', $defaults );
		}

		return $this->modules;
	}

	/**
	 * Resolve module by key.
	 *
	 * @param string $key Module key.
	 * @return BookingTypeInterface
	 */
	public function get( $key ) {
		$all = $this->all();
		return $all[ $key ] ?? $all['generic'];
	}
}
