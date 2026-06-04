<?php
/**
 * Data access for listing reviews.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Listings;

use FlexBooking\Database\Migrator;
use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Repository for listing_reviews table.
 */
final class ListingReviewRepository {

	/**
	 * Insert a review row.
	 *
	 * @param array<string, mixed> $data Row data.
	 * @return int Insert id.
	 */
	public function insert( array $data ) {
		global $wpdb;

		self::ensure_table_exists();

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return 0;
		}

		$now = current_time( 'mysql' );

		if ( isset( $data['wp_user_id'] ) && ( null === $data['wp_user_id'] || '' === $data['wp_user_id'] || 0 === (int) $data['wp_user_id'] ) ) {
			unset( $data['wp_user_id'] );
		} elseif ( isset( $data['wp_user_id'] ) ) {
			$data['wp_user_id'] = (int) $data['wp_user_id'];
		}

		$row = array_merge(
			array(
				'created_at' => $now,
				'updated_at' => $now,
			),
			$data
		);

		$formats = self::formats_for_row( $row );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert( $table, $row, $formats );

		if ( false === $inserted ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Create listing_reviews table if missing (e.g. upgrade from 1.0.0 without re-activation).
	 *
	 * @return void
	 */
	public static function ensure_table_exists() {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$ddl = Schema::ddl();
		if ( ! empty( $ddl['listing_reviews'] ) ) {
			dbDelta( $ddl['listing_reviews'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			( new Migrator() )->install();
		}
	}

	/**
	 * @param array<string, mixed> $row Insert row.
	 * @return string[]
	 */
	private static function formats_for_row( array $row ) {
		$int_cols = array( 'listing_id', 'wp_user_id', 'rating' );
		$formats  = array();

		foreach ( $row as $key => $val ) {
			$formats[] = in_array( $key, $int_cols, true ) ? '%d' : '%s';
		}

		return $formats;
	}

	/**
	 * Last database error from wpdb (for logging).
	 *
	 * @return string
	 */
	public static function last_db_error() {
		global $wpdb;

		return isset( $wpdb->last_error ) ? (string) $wpdb->last_error : '';
	}

	/**
	 * Update review fields.
	 *
	 * @param int                  $id   Review id.
	 * @param array<string, mixed> $data Fields.
	 * @return bool
	 */
	public function update( $id, array $data ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return false;
		}

		$data['updated_at'] = current_time( 'mysql' );

		$formats = array();
		foreach ( $data as $key => $val ) {
			if ( in_array( $key, array( 'listing_id', 'wp_user_id', 'rating' ), true ) ) {
				$formats[] = is_null( $val ) ? '%s' : '%d';
				continue;
			}
			$formats[] = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$n = $wpdb->update(
			$table,
			$data,
			array( 'id' => (int) $id ),
			$formats,
			array( '%d' )
		);

		return false !== $n;
	}

	/**
	 * Get review by id.
	 *
	 * @param int $id Review id.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, (int) $id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Approved reviews for a listing (newest first).
	 *
	 * @param int $listing_id Post id.
	 * @param int $limit      Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_approved_for_listing( $listing_id, $limit = 50 ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return array();
		}

		$limit = max( 1, min( 100, (int) $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE listing_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT %d",
				$table,
				(int) $listing_id,
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Average rating + count for approved reviews on a listing.
	 *
	 * @param int $listing_id Post id.
	 * @return array{rating: float, count: int}
	 */
	public function approved_stats( $listing_id ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return array(
				'rating' => 0.0,
				'count'  => 0,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM %i WHERE listing_id = %d AND status = 'approved'",
				$table,
				(int) $listing_id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) || empty( $row['review_count'] ) ) {
			return array(
				'rating' => 0.0,
				'count'  => 0,
			);
		}

		return array(
			'rating' => round( (float) $row['avg_rating'], 1 ),
			'count'  => (int) $row['review_count'],
		);
	}

	/**
	 * Paginated admin list.
	 *
	 * @param int    $page   Page number (1-based).
	 * @param int    $limit  Per page.
	 * @param string $status Filter status or empty for all.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_page( $page, $limit, $status = '' ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return array();
		}

		$page   = max( 1, (int) $page );
		$limit  = max( 1, min( 100, (int) $limit ) );
		$offset = ( $page - 1 ) * $limit;

		if ( '' !== $status && in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
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
	 * Count reviews for admin list.
	 *
	 * @param string $status Filter status or empty.
	 * @return int
	 */
	public function count_all( $status = '' ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return 0;
		}

		if ( '' !== $status && in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $table, $status )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	}

	/**
	 * Delete a review.
	 *
	 * @param int $id Review id.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$table = Schema::table( 'listing_reviews' );
		if ( '' === $table ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$n = $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );

		return false !== $n && $n > 0;
	}
}
