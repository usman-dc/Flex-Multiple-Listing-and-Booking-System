<?php
/**
 * License key generator.
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates unique FLEX-XXXX-XXXX-XXXX-XXXX keys.
 */
final class FBLS_Key_Generator {

	/**
	 * Generate a unique license key.
	 *
	 * @return string
	 */
	public static function generate() {
		$prefix = (string) apply_filters( 'fbls_license_key_prefix', 'FLEX' );
		$prefix = strtoupper( preg_replace( '/[^A-Z0-9]/', '', $prefix ) );
		if ( '' === $prefix ) {
			$prefix = 'FLEX';
		}

		$repo = new FBLS_License_Repository();
		$max  = 25;

		for ( $i = 0; $i < $max; $i++ ) {
			$segments = array();
			for ( $s = 0; $s < 4; $s++ ) {
				$segments[] = self::random_segment( 4 );
			}
			$key = $prefix . '-' . implode( '-', $segments );
			if ( ! $repo->get_by_key( $key ) ) {
				return $key;
			}
		}

		return $prefix . '-' . strtoupper( wp_generate_password( 16, false, false ) );
	}

	/**
	 * Random alphanumeric segment.
	 *
	 * @param int $length Length.
	 * @return string
	 */
	private static function random_segment( $length ) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$out   = '';
		$max   = strlen( $chars ) - 1;
		for ( $i = 0; $i < $length; $i++ ) {
			$out .= $chars[ wp_rand( 0, $max ) ];
		}
		return $out;
	}

	/**
	 * Normalize key for lookup.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	public static function normalize( $key ) {
		$key = strtoupper( trim( (string) $key ) );
		$key = preg_replace( '/[^A-Z0-9\-]/', '', $key );
		return is_string( $key ) ? $key : '';
	}
}
