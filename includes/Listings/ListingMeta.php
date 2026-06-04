<?php
/**
 * Defines all listing meta keys and provides typed getters/setters.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Listings;

defined( 'ABSPATH' ) || exit;

final class ListingMeta {

	/**
	 * Meta keys registry — single source of truth for all listing metadata.
	 */
	public const KEY_BOOKING_TYPE_ID    = '_ulbm_booking_type_id';
	public const KEY_BASE_PRICE         = '_ulbm_base_price';
	public const KEY_SALE_PRICE         = '_ulbm_sale_price';
	public const KEY_PRICE_SUFFIX       = '_ulbm_price_suffix';
	public const KEY_BOOKING_MODE       = '_ulbm_booking_mode';
	public const KEY_MIN_BOOKING        = '_ulbm_min_booking';
	public const KEY_MAX_BOOKING        = '_ulbm_max_booking';
	public const KEY_MAX_GUESTS         = '_ulbm_max_guests';
	public const KEY_GALLERY            = '_ulbm_gallery';
	public const KEY_ADDRESS            = '_ulbm_address';
	public const KEY_LATITUDE           = '_ulbm_latitude';
	public const KEY_LONGITUDE          = '_ulbm_longitude';
	public const KEY_MAP_ZOOM           = '_ulbm_map_zoom';
	public const KEY_FEATURES           = '_ulbm_features';
	public const KEY_FAQ                = '_ulbm_faq';
	public const KEY_EXTRA_SERVICES     = '_ulbm_extra_services';
	public const KEY_CONTACT_EMAIL      = '_ulbm_contact_email';
	public const KEY_CONTACT_PHONE      = '_ulbm_contact_phone';
	public const KEY_CHECK_IN_TIME      = '_ulbm_check_in_time';
	public const KEY_CHECK_OUT_TIME     = '_ulbm_check_out_time';
	public const KEY_INSTANT_BOOKING    = '_ulbm_instant_booking';
	public const KEY_DEPOSIT_PERCENT    = '_ulbm_deposit_percent';
	public const KEY_CANCELLATION_DAYS  = '_ulbm_cancellation_days';
	public const KEY_VIDEO_URL          = '_ulbm_video_url';
	public const KEY_RATING             = '_ulbm_rating';
	public const KEY_REVIEW_COUNT       = '_ulbm_review_count';

	/**
	 * Retrieve post meta with type casting.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param string $type    Cast type: string, int, float, bool, array.
	 * @return mixed
	 */
	public static function get( $post_id, $key, $type = 'string' ) {
		$val = get_post_meta( $post_id, $key, true );
		if ( ! metadata_exists( 'post', $post_id, $key ) && 0 === strpos( (string) $key, '_ulbm_' ) ) {
			$legacy_key = '_fbs_' . substr( (string) $key, 6 );
			if ( metadata_exists( 'post', $post_id, $legacy_key ) ) {
				$val = get_post_meta( $post_id, $legacy_key, true );
			}
		}

		switch ( $type ) {
			case 'int':
				return (int) $val;
			case 'float':
				return (float) $val;
			case 'bool':
				return ! empty( $val );
			case 'array':
				if ( is_array( $val ) ) {
					return $val;
				}
				if ( is_string( $val ) && '' !== $val ) {
					$decoded = json_decode( $val, true );
					if ( is_array( $decoded ) ) {
						return $decoded;
					}
					// Handle double-encoded JSON (stored with extra slashes).
					$decoded = json_decode( wp_unslash( $val ), true );
					if ( is_array( $decoded ) ) {
						return $decoded;
					}
					// Comma-separated IDs (legacy gallery field format).
					if ( false !== strpos( $val, ',' ) || ctype_digit( trim( $val ) ) ) {
						return array_values(
							array_filter(
								array_map( 'absint', explode( ',', $val ) )
							)
						);
					}
				}
				return array();
			default:
				return (string) $val;
		}
	}

	/**
	 * Update post meta with encoding for arrays.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Value.
	 */
	public static function set( $post_id, $key, $value ) {
		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE );
		}
		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Default values for new listings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			self::KEY_BOOKING_TYPE_ID   => 0,
			self::KEY_BASE_PRICE        => '',
			self::KEY_SALE_PRICE        => '',
			self::KEY_PRICE_SUFFIX      => '/night',
			self::KEY_BOOKING_MODE      => 'daily',
			self::KEY_MIN_BOOKING       => 1,
			self::KEY_MAX_BOOKING       => 30,
			self::KEY_MAX_GUESTS        => 1,
			self::KEY_GALLERY           => array(),
			self::KEY_ADDRESS           => '',
			self::KEY_LATITUDE          => '',
			self::KEY_LONGITUDE         => '',
			self::KEY_MAP_ZOOM          => 14,
			self::KEY_FEATURES          => array(),
			self::KEY_FAQ               => array(),
			self::KEY_EXTRA_SERVICES    => array(),
			self::KEY_CONTACT_EMAIL     => '',
			self::KEY_CONTACT_PHONE     => '',
			self::KEY_CHECK_IN_TIME     => '14:00',
			self::KEY_CHECK_OUT_TIME    => '11:00',
			self::KEY_INSTANT_BOOKING   => false,
			self::KEY_DEPOSIT_PERCENT   => 0,
			self::KEY_CANCELLATION_DAYS => 0,
			self::KEY_VIDEO_URL         => '',
			self::KEY_RATING            => '',
			self::KEY_REVIEW_COUNT      => 0,
		);
	}
}
