<?php
/**
 * Purchase / license key storage and remote validation.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\License;

defined( 'ABSPATH' ) || exit;

/**
 * Manages license activation against a remote purchase server.
 */
final class LicenseManager {

	public const OPTION_KEY   = 'ulbm_license';
	public const CRON_HOOK    = 'ulbm_license_daily_check';
	public const STATUS_ACTIVE  = 'active';
	public const STATUS_INACTIVE = 'inactive';
	public const STATUS_EXPIRED = 'expired';
	public const STATUS_INVALID = 'invalid';

	/**
	 * Register cron and hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_check' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Daily license re-validation.
	 *
	 * @return void
	 */
	public static function cron_check() {
		$data = self::get();
		if ( empty( $data['key'] ) || self::STATUS_ACTIVE !== ( $data['status'] ?? '' ) ) {
			return;
		}
		self::remote_request( 'check', (string) $data['key'] );
	}

	/**
	 * Whether the license is active and not expired.
	 *
	 * @return bool
	 */
	public static function is_active() {
		$active = self::is_active_raw();
		return (bool) apply_filters( 'ulbm_license_is_active', $active );
	}

	/**
	 * Internal active check without filter.
	 *
	 * @return bool
	 */
	private static function is_active_raw() {
		$data = self::get();
		if ( self::STATUS_ACTIVE !== ( $data['status'] ?? '' ) || empty( $data['key'] ) ) {
			return false;
		}
		if ( ! empty( $data['expires'] ) && is_numeric( $data['expires'] ) && (int) $data['expires'] < time() ) {
			return false;
		}
		return true;
	}

	/**
	 * Stored license data.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$raw = get_option( self::OPTION_KEY, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Public status for admin UI.
	 *
	 * @return array<string, mixed>
	 */
	public static function status_for_display() {
		$data   = self::get();
		$status = (string) ( $data['status'] ?? self::STATUS_INACTIVE );
		$key    = (string) ( $data['key'] ?? '' );

		return array(
			'status'      => $status,
			'is_active'   => self::is_active(),
			'key_masked'  => self::mask_key( $key ),
			'expires'     => ! empty( $data['expires'] ) ? (int) $data['expires'] : 0,
			'expires_human' => self::format_expires( $data ),
			'message'     => (string) ( $data['message'] ?? '' ),
			'last_check'  => ! empty( $data['last_check'] ) ? (int) $data['last_check'] : 0,
			'purchase_url' => self::purchase_url(),
		);
	}

	/**
	 * Activate a license key.
	 *
	 * @param string $license_key License key from purchase.
	 * @return array{success:bool,message:string,status?:string}
	 */
	public static function activate( $license_key ) {
		$key = self::sanitize_key( $license_key );
		if ( '' === $key ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid license key.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$result = self::remote_request( 'activate', $key );
		if ( empty( $result['success'] ) ) {
			return array(
				'success' => false,
				'message' => (string) ( $result['message'] ?? __( 'License could not be activated.', 'flex-multiple-listing-and-booking-system' ) ),
			);
		}

		return array(
			'success' => true,
			'message' => (string) ( $result['message'] ?? __( 'License activated successfully.', 'flex-multiple-listing-and-booking-system' ) ),
			'status'  => (string) ( $result['status'] ?? self::STATUS_ACTIVE ),
		);
	}

	/**
	 * Deactivate license on this site.
	 *
	 * @return array{success:bool,message:string}
	 */
	public static function deactivate() {
		$data = self::get();
		$key  = (string) ( $data['key'] ?? '' );

		if ( '' !== $key ) {
			self::remote_request( 'deactivate', $key );
		}

		delete_option( self::OPTION_KEY );

		return array(
			'success' => true,
			'message' => __( 'License deactivated on this site.', 'flex-multiple-listing-and-booking-system' ),
		);
	}

	/**
	 * Manual re-check from admin.
	 *
	 * @return array{success:bool,message:string}
	 */
	public static function check_now() {
		$data = self::get();
		$key  = (string) ( $data['key'] ?? '' );
		if ( '' === $key ) {
			return array(
				'success' => false,
				'message' => __( 'No license key is saved.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$result = self::remote_request( 'check', $key );
		return array(
			'success' => ! empty( $result['success'] ),
			'message' => (string) ( $result['message'] ?? __( 'License status updated.', 'flex-multiple-listing-and-booking-system' ) ),
		);
	}

	/**
	 * Call remote license API.
	 *
	 * @param string $action activate|deactivate|check.
	 * @param string $key    License key.
	 * @return array<string, mixed>
	 */
	private static function remote_request( $action, $key ) {
		$action = sanitize_key( $action );
		$key    = self::sanitize_key( $key );

		$api_url = (string) apply_filters( 'ulbm_license_api_url', 'https://wprogers.com/wp-json/flex-booking/v1/license' );
		if ( '' === $api_url ) {
			return self::save_local(
				array(
					'success' => false,
					'status'  => self::STATUS_INVALID,
					'message' => __( 'License server is not configured.', 'flex-multiple-listing-and-booking-system' ),
				),
				$key
			);
		}

		$body = apply_filters(
			'ulbm_license_remote_body',
			array(
				'license_key' => $key,
				'site_url'    => home_url(),
				'action'      => $action,
				'item_slug'   => 'flex-multiple-listing-and-booking-system',
				'version'     => defined( 'ULBM_VERSION' ) ? ULBM_VERSION : '1.0.0',
			),
			$action
		);

		$response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::save_local(
				array(
					'success' => false,
					'status'  => (string) ( self::get()['status'] ?? self::STATUS_INACTIVE ),
					'message' => $response->get_error_message(),
				),
				$key,
				false
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$parsed = apply_filters( 'ulbm_license_remote_response', self::parse_response( $raw, $code ), $raw, $action, $key );

		$preserve_key = ( 'deactivate' !== $action );
		return self::save_local( $parsed, $key, $preserve_key );
	}

	/**
	 * Normalize API JSON into internal shape.
	 *
	 * @param array<string, mixed> $raw  Decoded body.
	 * @param int                  $code HTTP status.
	 * @return array<string, mixed>
	 */
	private static function parse_response( array $raw, $code ) {
		$success = ! empty( $raw['success'] ) || ( isset( $raw['license'] ) && 'valid' === $raw['license'] );
		$status  = sanitize_key( (string) ( $raw['status'] ?? ( $success ? self::STATUS_ACTIVE : self::STATUS_INVALID ) ) );

		if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_EXPIRED, self::STATUS_INVALID ), true ) ) {
			$status = $success ? self::STATUS_ACTIVE : self::STATUS_INVALID;
		}

		$expires = 0;
		if ( ! empty( $raw['expires'] ) ) {
			if ( is_numeric( $raw['expires'] ) ) {
				$expires = (int) $raw['expires'];
			} else {
				$expires = (int) strtotime( (string) $raw['expires'] );
			}
		} elseif ( ! empty( $raw['expires_at'] ) ) {
			$expires = (int) strtotime( (string) $raw['expires_at'] );
		}

		$message = (string) ( $raw['message'] ?? '' );
		if ( '' === $message && $code >= 400 ) {
			$message = __( 'License server rejected the request.', 'flex-multiple-listing-and-booking-system' );
		}

		return array(
			'success' => $success && self::STATUS_ACTIVE === $status,
			'status'  => $status,
			'message' => $message,
			'expires' => $expires,
		);
	}

	/**
	 * Persist license state.
	 *
	 * @param array<string, mixed> $parsed       Parsed response.
	 * @param string               $key          License key.
	 * @param bool                 $update_key   Whether to store the key.
	 * @return array<string, mixed>
	 */
	private static function save_local( array $parsed, $key, $update_key = true ) {
		$prev = self::get();

		if ( ! $update_key && empty( $parsed['success'] ) ) {
			$parsed['message'] = (string) ( $parsed['message'] ?? $prev['message'] ?? '' );
			return $parsed;
		}

		$row = array(
			'key'        => $update_key ? $key : (string) ( $prev['key'] ?? $key ),
			'status'     => (string) ( $parsed['status'] ?? self::STATUS_INACTIVE ),
			'message'    => (string) ( $parsed['message'] ?? '' ),
			'expires'    => ! empty( $parsed['expires'] ) ? (int) $parsed['expires'] : 0,
			'last_check' => time(),
		);

		if ( ! empty( $parsed['success'] ) || $update_key ) {
			update_option( self::OPTION_KEY, $row, false );
		} else {
			$row['key']    = (string) ( $prev['key'] ?? $key );
			$row['status'] = (string) ( $parsed['status'] ?? $prev['status'] ?? self::STATUS_INVALID );
			update_option( self::OPTION_KEY, $row, false );
		}

		return $parsed;
	}

	/**
	 * Sanitize license key input.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	public static function sanitize_key( $key ) {
		$key = strtoupper( trim( (string) $key ) );
		$key = preg_replace( '/[^A-Z0-9\-]/', '', $key );
		return is_string( $key ) ? $key : '';
	}

	/**
	 * Mask key for display.
	 *
	 * @param string $key License key.
	 * @return string
	 */
	public static function mask_key( $key ) {
		$key = (string) $key;
		if ( strlen( $key ) < 8 ) {
			return $key;
		}
		return substr( $key, 0, 4 ) . str_repeat( '•', max( 4, strlen( $key ) - 8 ) ) . substr( $key, -4 );
	}

	/**
	 * Human-readable expiry.
	 *
	 * @param array<string, mixed> $data License row.
	 * @return string
	 */
	private static function format_expires( array $data ) {
		if ( empty( $data['expires'] ) ) {
			return __( 'Lifetime', 'flex-multiple-listing-and-booking-system' );
		}
		return date_i18n( get_option( 'date_format' ), (int) $data['expires'] );
	}

	/**
	 * Purchase page URL.
	 *
	 * @return string
	 */
	public static function purchase_url() {
		return (string) apply_filters(
			'ulbm_license_purchase_url',
			'https://wprogers.com/product/flex-listings-and-booking-manager/'
		);
	}

	/**
	 * Clear scheduled cron (deactivation).
	 *
	 * @return void
	 */
	public static function clear_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
