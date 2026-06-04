<?php
/**
 * Vendor records in ulbm_vendors table.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Vendor persistence.
 */
final class VendorRepository {

	/**
	 * Create vendor row for WordPress user.
	 *
	 * @param int    $user_id       WP user ID.
	 * @param string $business_name Business label.
	 * @param string $status        pending|approved|suspended.
	 * @return int Vendor row ID.
	 */
	public function create( $user_id, $business_name = '', $status = 'approved' ) {
		global $wpdb;

		$table = Schema::tables()['vendors'];
		$now   = current_time( 'mysql' );

		$wpdb->insert(
			$table,
			array(
				'wp_user_id'    => absint( $user_id ),
				'business_name' => sanitize_text_field( $business_name ),
				'status'        => sanitize_key( $status ),
				'meta'          => null,
				'created_at'    => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		$id = (int) $wpdb->insert_id;
		if ( $id ) {
			update_user_meta( absint( $user_id ), '_ulbm_vendor_id', $id );
		}

		return $id;
	}

	/**
	 * Get vendor by WP user ID.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>|null
	 */
	public function get_by_user_id( $user_id ) {
		global $wpdb;

		$table = Schema::tables()['vendors'];
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE wp_user_id = %d LIMIT 1", absint( $user_id ) ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get vendor ID for user.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public function get_vendor_id( $user_id ) {
		$row = $this->get_by_user_id( $user_id );
		if ( $row ) {
			return (int) $row['id'];
		}
		return (int) get_user_meta( absint( $user_id ), '_ulbm_vendor_id', true );
	}

	/**
	 * Whether vendor account is approved.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function is_approved( $user_id ) {
		$row = $this->get_by_user_id( $user_id );
		if ( ! $row ) {
			return VendorRole::is_vendor( $user_id ) && user_can( $user_id, 'manage_options' );
		}
		return 'approved' === (string) $row['status'];
	}
}
