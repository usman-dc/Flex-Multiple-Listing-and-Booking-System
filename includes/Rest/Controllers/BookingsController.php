<?php
/**
 * REST: bookings collection and single — staff via Capabilities::can_access_admin().
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Rest\Controllers;

use FlexBooking\Core\Capabilities;
use FlexBooking\Core\Plugin;
use FlexBooking\Rest\RestRegistrar;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Bookings REST controller.
 */
final class BookingsController {

	/**
	 * Route registration.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			RestRegistrar::NS,
			'/bookings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'can_manage_or_book' ),
				),
			)
		);

		register_rest_route(
			RestRegistrar::NS,
			'/bookings/(?P<id>\\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Admin read access.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return Capabilities::can_access_admin();
	}

	/**
	 * Create via REST — staff or logged-in customer path.
	 *
	 * @return bool
	 */
	public function can_manage_or_book() {
		return Capabilities::can_access_admin() || current_user_can( Capabilities::CAP_BOOK );
	}

	/**
	 * List bookings (paginated stub — extend with WP_Query-like filters).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( WP_REST_Request $request ) {
		global $wpdb;

		$tables = \FlexBooking\Database\Schema::tables();
		$table  = $tables['bookings'];
		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$per    = min( 200, max( 1, (int) $request->get_param( 'per_page' ) ?: 20 ) );
		$offset = ( $page - 1 ) * $per;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from schema.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d OFFSET %d",
				$per,
				$offset
			),
			ARRAY_A
		);

		return new WP_REST_Response(
			array(
				'items'      => $rows,
				'total'      => $total,
				'page'       => $page,
				'per_page'   => $per,
				'total_pages'=> $total > 0 ? (int) ceil( $total / $per ) : 0,
			),
			200
		);
	}

	/**
	 * Single booking.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_item( WP_REST_Request $request ) {
		global $wpdb;

		$id     = absint( $request['id'] );
		$tables = \FlexBooking\Database\Schema::tables();
		$table  = $tables['bookings'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return new WP_REST_Response( array( 'message' => 'Not found' ), 404 );
		}

		return new WP_REST_Response( $row, 200 );
	}

	/**
	 * Create booking — delegates to engine.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function create_item( WP_REST_Request $request ) {
		$plugin = Plugin::instance();
		$engine = $plugin->container()->get( 'booking.engine' );

		$result = $engine->create_booking( $request->get_json_params() ?: array() );

		$code = ! empty( $result['success'] ) ? 201 : 400;
		return new WP_REST_Response( $result, $code );
	}
}
