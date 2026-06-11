<?php
/**
 * REST API for Flex Booking client plugin.
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers flex-booking/v1/license endpoint.
 */
final class FBLS_REST_API {

	/**
	 * Hook REST routes.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			'flex-booking/v1',
			'/license',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle license activate / deactivate / check.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$key     = FBLS_Key_Generator::normalize( (string) ( $params['license_key'] ?? '' ) );
		$site    = esc_url_raw( (string) ( $params['site_url'] ?? '' ) );
		$action  = sanitize_key( (string) ( $params['action'] ?? 'check' ) );
		$slug    = sanitize_key( (string) ( $params['item_slug'] ?? 'flex-multiple-listing-and-booking-system' ) );
		$version = sanitize_text_field( (string) ( $params['version'] ?? '' ) );

		if ( '' === $key ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'invalid',
					'message' => __( 'License key is required.', 'flex-multiple-listing-and-booking-system' ),
				),
				400
			);
		}

		if ( '' === $site ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'invalid',
					'message' => __( 'Site URL is required.', 'flex-multiple-listing-and-booking-system' ),
				),
				400
			);
		}

		$repo    = new FBLS_License_Repository();
		$license = $repo->get_by_key( $key );

		if ( ! $license ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'invalid',
					'message' => __( 'Invalid license key.', 'flex-multiple-listing-and-booking-system' ),
				),
				404
			);
		}

		if ( $slug && $slug !== (string) $license['product_slug'] ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'status'  => 'invalid',
					'message' => __( 'License key is not valid for this product.', 'flex-multiple-listing-and-booking-system' ),
				),
				403
			);
		}

		if ( 'activate' === $action ) {
			$result = $repo->activate_site( $license, $site, $version );
		} elseif ( 'deactivate' === $action ) {
			$result = $repo->deactivate_site( $license, $site );
		} else {
			$result = $repo->check_site( $license, $site, $version );
		}

		$code = ! empty( $result['success'] ) ? 200 : 400;
		return new WP_REST_Response( $result, $code );
	}
}
