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

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return 0;
		}
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

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE wp_user_id = %d LIMIT 1', $table, absint( $user_id ) ),
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

	/**
	 * Allowed partner account statuses.
	 *
	 * @return string[]
	 */
	public static function statuses() {
		return array( 'pending', 'approved', 'suspended' );
	}

	/**
	 * Get vendor row by ID.
	 *
	 * @param int $id Vendor row ID.
	 * @return array<string,mixed>|null
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, absint( $id ) ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Paginated admin partner list.
	 *
	 * @param int    $page   Page number (1-based).
	 * @param int    $limit  Per page.
	 * @param string $status Filter status or empty for all.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_page( $page, $limit, $status = '' ) {
		global $wpdb;

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return array();
		}

		$page   = max( 1, (int) $page );
		$limit  = max( 1, min( 100, (int) $limit ) );
		$offset = ( $page - 1 ) * $limit;

		if ( '' !== $status && in_array( $status, self::statuses(), true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$status,
					$limit,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$limit,
					$offset
				),
				ARRAY_A
			);
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count partners for admin list.
	 *
	 * @param string $status Filter status or empty.
	 * @return int
	 */
	public function count_all( $status = '' ) {
		global $wpdb;

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return 0;
		}

		if ( '' !== $status && in_array( $status, self::statuses(), true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $table, $status )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	}

	/**
	 * Update vendor row fields.
	 *
	 * @param int                  $id   Vendor row ID.
	 * @param array<string,mixed>  $data Fields to update.
	 * @return bool
	 */
	public function update( $id, array $data ) {
		global $wpdb;

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return false;
		}

		$fields  = array();
		$formats = array();

		if ( array_key_exists( 'business_name', $data ) ) {
			$fields['business_name'] = sanitize_text_field( (string) $data['business_name'] );
			$formats[]               = '%s';
		}
		if ( array_key_exists( 'status', $data ) ) {
			$status = sanitize_key( (string) $data['status'] );
			if ( ! in_array( $status, self::statuses(), true ) ) {
				return false;
			}
			$fields['status'] = $status;
			$formats[]        = '%s';
		}

		if ( empty( $fields ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			$fields,
			array( 'id' => absint( $id ) ),
			$formats,
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Delete vendor row.
	 *
	 * @param int $id Vendor row ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$table = Schema::table( 'vendors' );
		if ( '' === $table ) {
			return false;
		}

		$row = $this->get_by_id( $id );
		if ( ! $row ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
		if ( false === $deleted ) {
			return false;
		}

		delete_user_meta( (int) $row['wp_user_id'], '_ulbm_vendor_id' );

		return true;
	}
}
