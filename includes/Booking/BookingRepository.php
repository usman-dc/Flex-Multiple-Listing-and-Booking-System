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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return 0;
		}

		$now = current_time( 'mysql' );

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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $row, $formats );

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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return;
		}

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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
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

		$table = Schema::table( 'booking_meta' );
		if ( '' === $table ) {
			return;
		}

		$val = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return 0;
		}

		$st  = sanitize_key( (string) $status_filter );
		$tid = absint( $booking_type_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( '' !== $st && $tid > 0 ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status = %s AND booking_type_id = %d',
					$table,
					$st,
					$tid
				)
			);
		}
		if ( '' !== $st ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status = %s',
					$table,
					$st
				)
			);
		}
		if ( $tid > 0 ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE booking_type_id = %d',
					$table,
					$tid
				)
			);
		}

		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	}

	/**
	 * Bookings tied to a booking type (for safe delete checks).
	 *
	 * @param int $booking_type_id Type id.
	 * @return int
	 */
	public function count_for_booking_type( $booking_type_id ) {
		global $wpdb;

		$table = Schema::table( 'bookings' );
		$tid   = absint( $booking_type_id );
		if ( '' === $table || $tid < 1 ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE booking_type_id = %d',
				$table,
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

		$table  = Schema::table( 'bookings' );
		$page   = max( 1, (int) $page );
		$per    = min( 200, max( 1, (int) $per_page ) );
		$offset = ( $page - 1 ) * $per;
		if ( '' === $table ) {
			return array();
		}

		$st  = sanitize_key( (string) $status_filter );
		$tid = absint( $booking_type_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( '' !== $st && $tid > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s AND booking_type_id = %d ORDER BY id DESC LIMIT %d OFFSET %d',
					$table,
					$st,
					$tid,
					$per,
					$offset
				),
				ARRAY_A
			);
		} elseif ( '' !== $st ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d',
					$table,
					$st,
					$per,
					$offset
				),
				ARRAY_A
			);
		} elseif ( $tid > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE booking_type_id = %d ORDER BY id DESC LIMIT %d OFFSET %d',
					$table,
					$tid,
					$per,
					$offset
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
					$table,
					$per,
					$offset
				),
				ARRAY_A
			);
		}

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

		$table = Schema::table( 'bookings' );
		$bid   = absint( $id );
		if ( '' === $table || $bid < 1 ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, $bid ),
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

		$table = Schema::table( 'customers' );
		if ( '' === $table ) {
			return array();
		}

		$args = array_merge( array( $table ), $customer_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- IN clause uses one %d per absint() customer ID.
		$prepared = $wpdb->prepare(
			'SELECT id, email FROM %i WHERE id IN (' . implode( ',', array_fill( 0, count( $customer_ids ), '%d' ) ) . ')',
			...$args
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $prepared, ARRAY_A );

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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE created_at >= %s',
				$table,
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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return 0.0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$sum = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(total), 0) FROM %i WHERE created_at >= %s',
				$table,
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

		$table  = Schema::table( 'bookings' );
		$cutoff = wp_date( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days', (int) current_time( 'timestamp' ) ) );
		if ( '' === $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DATE(created_at) AS d, COUNT(*) AS cnt FROM %i WHERE created_at >= %s GROUP BY d ORDER BY d ASC',
				$table,
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

		$table  = Schema::table( 'bookings' );
		$cutoff = wp_date( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days', (int) current_time( 'timestamp' ) ) );
		if ( '' === $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DATE(created_at) AS d, COALESCE(SUM(total), 0) AS rev FROM %i WHERE created_at >= %s GROUP BY d ORDER BY d ASC',
				$table,
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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT status, COUNT(*) AS cnt FROM %i GROUP BY status', $table ),
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

		$table = Schema::table( 'bookings' );
		if ( '' === $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT booking_type_id, COUNT(*) AS cnt FROM %i GROUP BY booking_type_id', $table ),
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

		$table = Schema::table( 'booking_meta' );
		if ( '' === $table ) {
			return array();
		}

		$args = array_merge( array( $table, 'form_values' ), $booking_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- IN clause uses one %d per absint() booking ID.
		$prepared = $wpdb->prepare(
			'SELECT booking_id, meta_value FROM %i WHERE meta_key = %s AND booking_id IN (' . implode( ',', array_fill( 0, count( $booking_ids ), '%d' ) ) . ')',
			...$args
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $prepared, ARRAY_A );
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

		$table = Schema::table( 'bookings' );
		$lim   = min( 100, max( 1, (int) $limit ) );
		if ( '' === $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY id DESC LIMIT %d',
				$table,
				$lim
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}
}

