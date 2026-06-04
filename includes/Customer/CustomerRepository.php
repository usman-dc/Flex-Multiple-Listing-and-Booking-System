<?php
/**
 * Guest / customer records for bookings (email-keyed).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Customer;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD helpers for ulbm_customers.
 */
final class CustomerRepository {

	/**
	 * Create or update a customer by email.
	 *
	 * @param string   $email      Email (required).
	 * @param string   $first_name First name.
	 * @param string   $last_name  Last name.
	 * @param string   $phone      Phone.
	 * @param int|null $wp_user_id WordPress user id if known.
	 * @return int|null Customer id or null if email invalid.
	 */
	public function upsert_guest( $email, $first_name, $last_name, $phone, $wp_user_id = null ) {
		$email = sanitize_email( (string) $email );
		if ( ! $email || ! is_email( $email ) ) {
			return null;
		}

		global $wpdb;

		$table = Schema::table( 'customers' );
		$now   = current_time( 'mysql' );
		if ( '' === $table ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE email = %s LIMIT 1', $table, $email ) );

		$data = array(
			'first_name' => sanitize_text_field( (string) $first_name ),
			'last_name'  => sanitize_text_field( (string) $last_name ),
			'phone'      => sanitize_text_field( (string) $phone ),
		);

		if ( $wp_user_id ) {
			$data['wp_user_id'] = absint( $wp_user_id );
		}

		if ( $existing ) {
			$formats = array( '%s', '%s', '%s' );
			if ( $wp_user_id ) {
				$formats[] = '%d';
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				$data,
				array( 'id' => (int) $existing ),
				$formats,
				array( '%d' )
			);
			return (int) $existing;
		}

		$insert = array(
			'email'      => $email,
			'first_name' => $data['first_name'],
			'last_name'  => $data['last_name'],
			'phone'      => $data['phone'],
			'created_at' => $now,
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s' );
		if ( $wp_user_id ) {
			$insert  = array_merge( array( 'wp_user_id' => absint( $wp_user_id ) ), $insert );
			$formats = array_merge( array( '%d' ), $formats );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $table, $insert, $formats );

		return $wpdb->insert_id ? (int) $wpdb->insert_id : null;
	}
}
