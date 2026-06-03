<?php
/**
 * REST: public-safe settings for frontend (no secrets) and admin full settings.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Rest\Controllers;

use FlexBooking\Core\Capabilities;
use FlexBooking\Rest\RestRegistrar;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes whitelisted settings to the booking UI.
 */
final class SettingsController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			RestRegistrar::NS,
			'/settings/public',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_public' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			RestRegistrar::NS,
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			RestRegistrar::NS,
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Admin capability.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return Capabilities::can_access_admin();
	}

	/**
	 * Public booking UI configuration (currency, labels).
	 *
	 * @return WP_REST_Response
	 */
	public function get_public() {
		$general = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
		if ( ! is_array( $general ) ) {
			$general = array();
		}

		$data = array(
			'currency'          => $general['currency'] ?? 'USD',
			'currency_position' => $general['currency_position'] ?? 'left',
			'date_format'       => $general['date_format'] ?? 'Y-m-d',
			'time_format'       => $general['time_format'] ?? 'H:i',
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Full settings for admin SPA.
	 *
	 * @return WP_REST_Response
	 */
	public function get_all() {
		return new WP_REST_Response(
			array(
				'general' => json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true ),
				'payment' => json_decode( (string) get_option( 'fbs_payment_settings', '{}' ), true ),
				'email'   => json_decode( (string) get_option( 'fbs_email_settings', '{}' ), true ),
			),
			200
		);
	}

	/**
	 * Merge-save settings groups (simple whole-json replace per group).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save( WP_REST_Request $request ) {
		$params = $request->get_json_params() ?: array();

		if ( isset( $params['general'] ) && is_array( $params['general'] ) ) {
			update_option( 'fbs_general_settings', wp_json_encode( $params['general'] ), false );
		}
		if ( isset( $params['payment'] ) && is_array( $params['payment'] ) ) {
			update_option( 'fbs_payment_settings', wp_json_encode( $params['payment'] ), false );
		}
		if ( isset( $params['email'] ) && is_array( $params['email'] ) ) {
			update_option( 'fbs_email_settings', wp_json_encode( $params['email'] ), false );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
