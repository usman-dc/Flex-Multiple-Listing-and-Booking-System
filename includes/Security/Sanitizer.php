<?php
/**
 * Central sanitization helpers for booking payloads.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Static sanitizers — keeps XSS out of stored booking meta.
 */
final class Sanitizer {

	/**
	 * ISO currency code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function currency_code( $code ) {
		$code = strtoupper( preg_replace( '/[^A-Z]/i', '', (string) $code ) );
		return strlen( $code ) === 3 ? $code : 'USD';
	}
}
