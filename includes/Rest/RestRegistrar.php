<?php
/**
 * Registers REST API namespace and route controllers.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Rest;

use FlexBooking\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Binds REST routes.
 */
final class RestRegistrar {

	/**
	 * API namespace.
	 */
	public const NS = 'flex-booking/v1';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Kernel.
	 */
	public function __construct( Plugin $plugin ) {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Register controllers.
	 *
	 * @return void
	 */
	public function register() {
		$bookings = new Controllers\BookingsController();
		$bookings->register_routes();

		$settings = new Controllers\SettingsController();
		$settings->register_routes();

		do_action( 'fbs_rest_register_routes', self::NS );
	}
}
