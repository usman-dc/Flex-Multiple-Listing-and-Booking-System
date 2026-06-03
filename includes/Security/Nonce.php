<?php
/**
 * Namespaced nonce helpers for AJAX/REST.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Nonce factory for Flex Booking actions.
 */
final class Nonce {

	public const ACTION_AJAX = 'fbs_ajax';
	public const ACTION_REST = 'fbs_rest';

	/**
	 * Field name for forms.
	 *
	 * @return string
	 */
	public static function field_name() {
		return '_fbs_nonce';
	}

	/**
	 * Create nonce for context.
	 *
	 * @param string $action Action key.
	 * @return string
	 */
	public static function create( $action ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify request nonce.
	 *
	 * @param string $action Expected action.
	 * @param string $nonce  Nonce value.
	 * @return bool
	 */
	public static function verify( $action, $nonce ) {
		return (bool) wp_verify_nonce( $nonce, $action );
	}
}
