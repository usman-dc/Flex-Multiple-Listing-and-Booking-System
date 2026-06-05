<?php
/**
 * Safe $_POST access after AJAX/admin nonce verification.
 *
 * Call PostData::allow_processing() only immediately after check_ajax_referer(),
 * wp_verify_nonce(), or check_admin_referer().
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Gated wrapper for request POST fields (Plugin Check / PHPCS friendly).
 */
final class PostData {

	/**
	 * Whether nonce was verified for the current request.
	 *
	 * @var bool
	 */
	private static $allowed = false;

	/**
	 * Mark POST data as safe to read (call right after nonce verification).
	 *
	 * @return void
	 */
	public static function allow_processing() {
		self::$allowed = true;
	}

	/**
	 * Reset gate (optional; new request resets automatically in FPM).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$allowed = false;
	}

	/**
	 * Whether a POST key is set.
	 *
	 * @param string $key Field name.
	 * @return bool
	 */
	public static function has( $key ) {
		if ( ! self::$allowed ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gated by allow_processing().
		return isset( $_POST[ $key ] );
	}

	/**
	 * Raw unslashed value or default.
	 *
	 * @param string $key     Field name.
	 * @param mixed  $default Default when missing.
	 * @return mixed
	 */
	public static function raw( $key, $default = '' ) {
		if ( ! self::has( $key ) ) {
			return $default;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Key verified via has(); wp_unslash for caller sanitization.
		return wp_unslash( $_POST[ $key ] );
	}

	/**
	 * Sanitized text field.
	 *
	 * @param string $key     Field name.
	 * @param string $default Default.
	 * @return string
	 */
	public static function string( $key, $default = '' ) {
		if ( ! self::has( $key ) ) {
			return $default;
		}
		return sanitize_text_field( (string) self::raw( $key ) );
	}

	/**
	 * Sanitized email.
	 *
	 * @param string $key     Field name.
	 * @param string $default Default.
	 * @return string
	 */
	public static function email( $key, $default = '' ) {
		if ( ! self::has( $key ) ) {
			return $default;
		}
		return sanitize_email( (string) self::raw( $key ) );
	}

	/**
	 * Positive integer.
	 *
	 * @param string $key     Field name.
	 * @param int    $default Default.
	 * @return int
	 */
	public static function int( $key, $default = 0 ) {
		if ( ! self::has( $key ) ) {
			return $default;
		}
		return absint( self::raw( $key ) );
	}

	/**
	 * Float value.
	 *
	 * @param string $key     Field name.
	 * @param float  $default Default.
	 * @return float
	 */
	public static function float( $key, $default = 0.0 ) {
		if ( ! self::has( $key ) ) {
			return (float) $default;
		}
		return (float) self::raw( $key );
	}

	/**
	 * Sanitized key.
	 *
	 * @param string $key     Field name.
	 * @param string $default Default.
	 * @return string
	 */
	public static function key( $key, $default = '' ) {
		if ( ! self::has( $key ) ) {
			return $default;
		}
		return sanitize_key( (string) self::raw( $key ) );
	}

	/**
	 * Truthy POST flag.
	 *
	 * @param string $key Field name.
	 * @return bool
	 */
	public static function bool( $key ) {
		return self::has( $key ) && ! empty( self::raw( $key ) );
	}

	/**
	 * Array value (e.g. industries[]); each element unslashed, not deeply sanitized.
	 *
	 * @param string $key Field name.
	 * @return array<int, mixed>
	 */
	public static function array( $key ) {
		if ( ! self::has( $key ) ) {
			return array();
		}
		$raw = self::raw( $key );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return array_map( 'wp_unslash', $raw );
	}
}
