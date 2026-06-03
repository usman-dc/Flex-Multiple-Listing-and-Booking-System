<?php
/**
 * Registers plugin-wide actions — extend via remove_action from themes.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Hooks;

use FlexBooking\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Central action bootstrap for extensions.
 */
final class ActionHooks {

	/**
	 * Constructor wires early actions.
	 *
	 * @param Plugin $plugin Kernel.
	 */
	public function __construct( Plugin $plugin ) {
		add_action(
			'fbs_booking_created',
			array( $this, 'maybe_queue_confirmation_email' ),
			10,
			2
		);

		/**
		 * Allow addons to subscribe without constructing this class.
		 *
		 * @param Plugin $plugin Kernel instance.
		 */
		do_action( 'fbs_register_actions', $plugin );
	}

	/**
	 * Placeholder notification pipeline — extend or replace via remove_action.
	 *
	 * @param int                  $booking_id ID.
	 * @param array<string, mixed> $payload    Original payload.
	 * @return void
	 */
	public function maybe_queue_confirmation_email( $booking_id, $payload ) {
		do_action( 'fbs_notification_enqueue', 'booking_created', absint( $booking_id ), $payload );
	}
}
