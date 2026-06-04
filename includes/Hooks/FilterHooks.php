<?php
/**
 * Default filters — pricing pipeline entrypoint.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Hooks;

use FlexBooking\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers baseline filters third-parties can reorder.
 */
final class FilterHooks {

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Kernel.
	 */
	public function __construct( Plugin $plugin ) {
		add_filter( 'ulbm_calculate_booking_total', array( $this, 'default_total' ), 5, 2 );

		/**
		 * Allow addons to register filters early.
		 *
		 * @param Plugin $plugin Kernel instance.
		 */
		do_action( 'ulbm_register_filters', $plugin );
	}

	/**
	 * Starter pricing hook — returns numeric total until modules contribute.
	 *
	 * @param float                $total   Running total.
	 * @param array<string, mixed> $payload Payload.
	 * @return float
	 */
	public function default_total( $total, $payload ) {
		$base = isset( $payload['base_price'] ) ? (float) $payload['base_price'] : 0.0;
		return (float) $total + $base;
	}
}
