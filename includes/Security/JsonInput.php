<?php
/**
 * Safe decoding of JSON submitted via POST.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Decodes JSON request fields after basic sanitization of the raw string.
 */
final class JsonInput {

	/**
	 * Decode a POST field as an associative array.
	 *
	 * @param string $post_key $_POST key (requires PostData::allow_processing()).
	 * @return array<mixed>
	 */
	public static function decode_post_array( $post_key ) {
		if ( ! PostData::has( $post_key ) ) {
			return array();
		}

		$raw = PostData::raw( $post_key );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}

		$decoded = json_decode( $raw, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
