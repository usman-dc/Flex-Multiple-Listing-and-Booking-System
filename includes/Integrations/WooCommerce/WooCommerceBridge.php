<?php
/**
 * WooCommerce bridge — order meta linkage, checkout flow hooks (extend for production).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks WooCommerce when active — does not require WC at parse time.
 */
final class WooCommerceBridge {

	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action(
			'woocommerce_checkout_create_order',
			array( __CLASS__, 'attach_booking_meta' ),
			10,
			2
		);

		add_filter(
			'ulbm_calculate_booking_total',
			array( __CLASS__, 'maybe_add_cart_context' ),
			20,
			2
		);
	}

	/**
	 * Example: persist booking id on order when passed through session (stub).
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $data  Checkout data.
	 * @return void
	 */
	public static function attach_booking_meta( $order, $data ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$booking_id = absint( WC()->session->get( 'ulbm_pending_booking_id' ) );
		if ( $booking_id > 0 ) {
			$order->update_meta_data( '_ulbm_booking_id', $booking_id );
		}
	}

	/**
	 * Placeholder filter to let WC tax engine participate later.
	 *
	 * @param float                $total   Total.
	 * @param array<string, mixed> $payload Payload.
	 * @return float
	 */
	public static function maybe_add_cart_context( $total, $payload ) {
		return (float) $total;
	}
}
