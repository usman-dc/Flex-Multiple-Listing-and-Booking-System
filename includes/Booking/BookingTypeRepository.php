<?php
/**
 * Data access for booking_types — full lists for admin UI.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Booking;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Repository for booking_types table.
 */
final class BookingTypeRepository {

	/**
	 * All booking types, oldest first (stable admin ordering).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all() {
		global $wpdb;

		$table = Schema::tables()['booking_types'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from schema.
		$rows = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id ASC", ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Total rows (for dashboard).
	 *
	 * @return int
	 */
	public function count_all() {
		global $wpdb;

		$table = Schema::tables()['booking_types'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
	}

	/**
	 * Single booking type row.
	 *
	 * @param int $id Primary key.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$table = Schema::tables()['booking_types'];
		$tid   = absint( $id );
		if ( $tid < 1 ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d LIMIT 1", $tid ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Whether slug is already used.
	 *
	 * @param string $slug        URL-safe slug.
	 * @param int    $exclude_id  Exclude this id (when updating).
	 * @return bool
	 */
	public function slug_exists( $slug, $exclude_id = 0 ) {
		global $wpdb;

		$table = Schema::tables()['booking_types'];
		$slug  = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return false;
		}

		$exclude_id = absint( $exclude_id );
		if ( $exclude_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$table}` WHERE slug = %s AND id != %d LIMIT 1",
					$slug,
					$exclude_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$table}` WHERE slug = %s LIMIT 1",
					$slug
				)
			);
		}

		return (int) $found > 0;
	}

	/**
	 * Insert booking type row.
	 *
	 * @param array<string, mixed> $data Row fields.
	 * @return int Insert id or 0.
	 */
	public function insert_row( array $data ) {
		global $wpdb;

		$table = Schema::tables()['booking_types'];
		$now   = current_time( 'mysql' );

		$row = array_merge(
			array(
				'created_at' => $now,
				'updated_at' => $now,
				'status'     => 'publish',
				'module_key' => 'generic',
				'form_id'    => null,
			),
			$data
		);

		$formats = array();
		foreach ( $row as $key => $val ) {
			if ( 'form_id' === $key ) {
				$formats[] = is_null( $val ) ? '%s' : '%d';
				continue;
			}
			$formats[] = '%s';
		}

		$ok = $wpdb->insert( $table, $row, $formats );

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update booking type row.
	 *
	 * @param int                    $id   Primary key.
	 * @param array<string, mixed>   $data Fields.
	 * @return bool
	 */
	public function update_row( $id, array $data ) {
		global $wpdb;

		$table = Schema::tables()['booking_types'];
		$tid   = absint( $id );
		if ( $tid < 1 ) {
			return false;
		}

		$data['updated_at'] = current_time( 'mysql' );

		$formats = array();
		foreach ( $data as $key => $val ) {
			if ( 'form_id' === $key ) {
				$formats[] = is_null( $val ) ? '%s' : '%d';
				continue;
			}
			$formats[] = '%s';
		}

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $tid ),
			$formats,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete row by id.
	 *
	 * @param int $id Primary key.
	 * @return bool
	 */
	public function delete_row( $id ) {
		global $wpdb;

		$table = Schema::tables()['booking_types'];
		$tid   = absint( $id );
		if ( $tid < 1 ) {
			return false;
		}

		$deleted = $wpdb->delete( $table, array( 'id' => $tid ), array( '%d' ) );

		return false !== $deleted && $deleted > 0;
	}
}
