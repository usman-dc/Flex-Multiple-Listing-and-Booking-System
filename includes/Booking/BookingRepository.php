<?php
/**
 * Data access for bookings — optimized inserts with meta sidecar.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Booking;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Repository for bookings table.
 */
final class BookingRepository {

	/**
	 * Insert booking row.
	 *
	 * @param array<string, mixed> $data Row data.
	 * @return int Insert id.
	 */
	public function insert_booking( array $data ) {
		global $wpdb;

		$tables = Schema::tables();
		$now    = current_time( 'mysql' );

		$defaults = array(
			'created_at' => $now,
			'updated_at' => $now,
		);

		$row = array_merge( $defaults, $data );

		$formats = array();
		foreach ( $row as $key => $val ) {
			if ( in_array( $key, array( 'booking_type_id', 'customer_id', 'wp_user_id', 'vendor_id' ), true ) ) {
				$formats[] = is_null( $val ) ? '%s' : '%d';
				continue;
			}
			if ( in_array( $key, array( 'total', 'tax_total', 'discount_total', 'deposit_total' ), true ) ) {
				$formats[] = '%f';
				continue;
			}
			$formats[] = '%s';
		}

		$wpdb->insert( $tables['bookings'], $row, $formats );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update booking.
	 *
	 * @param int                    $id   Booking id.
	 * @param array<string, mixed>   $data Fields.
	 * @return void
	 */
	public function update_booking( $id, array $data ) {
		global $wpdb;

		$tables = Schema::tables();
		$data['updated_at'] = current_time( 'mysql' );

		$formats = array();
		foreach ( $data as $key => $val ) {
			if ( in_array( $key, array( 'booking_type_id', 'customer_id', 'wp_user_id', 'vendor_id' ), true ) ) {
				$formats[] = is_null( $val ) ? '%s' : '%d';
				continue;
			}
			if ( in_array( $key, array( 'total', 'tax_total', 'discount_total', 'deposit_total' ), true ) ) {
				$formats[] = '%f';
				continue;
			}
			$formats[] = '%s';
		}

		$wpdb->update(
			$tables['bookings'],
			$data,
			array( 'id' => absint( $id ) ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Add booking meta (EAV).
	 *
	 * @param int    $booking_id Booking id.
	 * @param string $key        Meta key.
	 * @param mixed  $value      Value (scalar or array).
	 * @return void
	 */
	public function add_meta( $booking_id, $key, $value ) {
		global $wpdb;

		$tables = Schema::tables();
		$val     = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );

		$wpdb->insert(
			$tables['booking_meta'],
			array(
				'booking_id' => $booking_id,
				'meta_key'   => $key,
				'meta_value' => $val,
			),
			array( '%d', '%s', '%s' )
		);
	}

	/**
	 * Total bookings (all statuses).
	 *
	 * @return int
	 */
	public function count_all( $status_filter = '', $booking_type_id = 0 ) {
		global $wpdb;

		$table      = Schema::tables()['bookings'];
		$conditions = array();
		$st         = sanitize_key( (string) $status_filter );
		if ( '' !== $st ) {
			$conditions[] = $wpdb->prepare( 'status = %s', $st );
		}
		$tid = absint( $booking_type_id );
		if ( $tid > 0 ) {
			$conditions[] = $wpdb->prepare( 'booking_type_id = %d', $tid );
		}
		$where = empty( $conditions ) ? '' : ' WHERE ' . implode( ' AND ', $conditions );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`{$where}" );
	}

	/**
	 * Bookings tied to a booking type (for safe delete checks).
	 *
	 * @param int $booking_type_id Type id.
	 * @return int
	 */
	public function count_for_booking_type( $booking_type_id ) {
		global $wpdb;

		$table = Schema::tables()['bookings'];
		$tid   = absint( $booking_type_id );
		if ( $tid < 1 ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE booking_type_id = %d",
				$tid
			)
		);
	}

	/**
	 * Paginated list for admin (newest first).
	 *
	 * @param int $page    1-based page.
	 * @param int $per_page Rows per page (clamped 1–200).
	 * @return array<int, array<string, mixed>>
	 */
	public function get_page( $page, $per_page, $status_filter = '', $booking_type_id = 0 ) {
		global $wpdb;

		$table      = Schema::tables()['bookings'];
		$page       = max( 1, (int) $page );
		$per        = min( 200, max( 1, (int) $per_page ) );
		$offset     = ( $page - 1 ) * $per;
		$conditions = array();
		$st         = sanitize_key( (string) $status_filter );
		if ( '' !== $st ) {
			$conditions[] = $wpdb->prepare( 'status = %s', $st );
		}
		$tid = absint( $booking_type_id );
		if ( $tid > 0 ) {
			$conditions[] = $wpdb->prepare( 'booking_type_id = %d', $tid );
		}
		$where = empty( $conditions ) ? '' : ' WHERE ' . implode( ' AND ', $conditions );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM `{$table}`{$where} ORDER BY id DESC LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $where is empty or prepared clauses only.
		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $per, $offset ),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Single booking row.
	 *
	 * @param int $id Booking id.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$table = Schema::tables()['bookings'];
		$bid   = absint( $id );
		if ( $bid < 1 ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d LIMIT 1", $bid ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Customer id => email for list screens.
	 *
	 * @param int[] $customer_ids Unique customer ids.
	 * @return array<int, string>
	 */
	public function get_customer_emails_by_ids( array $customer_ids ) {
		global $wpdb;

		$customer_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $customer_ids )
				)
			)
		);

		if ( empty( $customer_ids ) ) {
			return array();
		}

		$table    = Schema::tables()['customers'];
		$placeholders = implode( ',', array_fill( 0, count( $customer_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT id, email FROM `{$table}` WHERE id IN ($placeholders)";

		$prepared = $wpdb->prepare( $sql, $customer_ids );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows     = $wpdb->get_results( $prepared, ARRAY_A );

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$out[ (int) $r['id'] ] = (string) $r['email'];
			}
		}

		return $out;
	}

	/**
	 * Bookings created on or after datetime (site timezone).
	 *
	 * @param string $mysql_datetime Datetime string.
	 * @return int
	 */
	public function count_since( $mysql_datetime ) {
		global $wpdb;

		$table = Schema::tables()['bookings'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s",
				$mysql_datetime
			)
		);
	}

	/**
	 * Sum total for paid-like rows in window (best-effort until payment_status workflow is final).
	 *
	 * @param string $mysql_datetime Start inclusive.
	 * @return float
	 */
	public function sum_total_since( $mysql_datetime ) {
		global $wpdb;

		$table = Schema::tables()['bookings'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sum = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(total), 0) FROM `{$table}` WHERE created_at >= %s",
				$mysql_datetime
			)
		);

		return (float) $sum;
	}

	/**
	 * Daily counts for the last N days (for charts).
	 *
	 * @param int $days Number of days back.
	 * @return array<string, int> date => count.
	 */
	public function daily_counts( $days = 30 ) {
		global $wpdb;

		$table  = Schema::tables()['bookings'];
		$cutoff = wp_date( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days', (int) current_time( 'timestamp' ) ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COUNT(*) AS cnt FROM `{$table}` WHERE created_at >= %s GROUP BY d ORDER BY d ASC",
				$cutoff . ' 00:00:00'
			),
			ARRAY_A
		);

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$out[ $r['d'] ] = (int) $r['cnt'];
			}
		}
		return $out;
	}

	/**
	 * Daily revenue for the last N days (for charts).
	 *
	 * @param int $days Number of days back.
	 * @return array<string, float> date => sum.
	 */
	public function daily_revenue( $days = 30 ) {
		global $wpdb;

		$table  = Schema::tables()['bookings'];
		$cutoff = wp_date( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days', (int) current_time( 'timestamp' ) ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COALESCE(SUM(total), 0) AS rev FROM `{$table}` WHERE created_at >= %s GROUP BY d ORDER BY d ASC",
				$cutoff . ' 00:00:00'
			),
			ARRAY_A
		);

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$out[ $r['d'] ] = (float) $r['rev'];
			}
		}
		return $out;
	}

	/**
	 * Counts grouped by status.
	 *
	 * @return array<string, int>
	 */
	public function count_by_status() {
		global $wpdb;

		$table = Schema::tables()['bookings'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT status, COUNT(*) AS cnt FROM `{$table}` GROUP BY status",
			ARRAY_A
		);

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$out[ (string) $r['status'] ] = (int) $r['cnt'];
			}
		}
		return $out;
	}

	/**
	 * Counts grouped by booking_type_id.
	 *
	 * @return array<int, int>
	 */
	public function count_by_type() {
		global $wpdb;

		$table = Schema::tables()['bookings'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT booking_type_id, COUNT(*) AS cnt FROM `{$table}` GROUP BY booking_type_id",
			ARRAY_A
		);

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$out[ (int) $r['booking_type_id'] ] = (int) $r['cnt'];
			}
		}
		return $out;
	}

	/**
	 * Decoded form_values meta for booking ids (public form answers).
	 *
	 * @param int[] $booking_ids Booking ids.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_form_values_for_bookings( array $booking_ids ) {
		global $wpdb;

		$booking_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $booking_ids )
				)
			)
		);

		if ( empty( $booking_ids ) ) {
			return array();
		}

		$table        = Schema::tables()['booking_meta'];
		$placeholders = implode( ',', array_fill( 0, count( $booking_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql          = "SELECT booking_id, meta_value FROM `{$table}` WHERE meta_key = %s AND booking_id IN ({$placeholders})";
		$args         = array_merge( array( 'form_values' ), $booking_ids );
		$prep         = $wpdb->prepare( $sql, $args );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows         = $wpdb->get_results( $prep, ARRAY_A );
		$out          = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$bid = (int) $r['booking_id'];
				$dec = json_decode( (string) $r['meta_value'], true );
				$out[ $bid ] = is_array( $dec ) ? $dec : array();
			}
		}

		return $out;
	}

	/**
	 * Recent bookings for dashboard.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent( $limit ) {
		global $wpdb;

		$table = Schema::tables()['bookings'];
		$lim   = min( 100, max( 1, (int) $limit ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d",
				$lim
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}
}

