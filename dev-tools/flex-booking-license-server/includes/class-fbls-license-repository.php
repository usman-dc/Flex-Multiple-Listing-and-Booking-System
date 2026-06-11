<?php
/**
 * License CRUD and activation logic.
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * License data access.
 */
final class FBLS_License_Repository {

	/**
	 * Create a license row.
	 *
	 * @param array<string, mixed> $args License fields.
	 * @return int License ID or 0.
	 */
	public function create( array $args ) {
		global $wpdb;

		$key = FBLS_Key_Generator::normalize( (string) ( $args['license_key'] ?? '' ) );
		if ( '' === $key ) {
			$key = FBLS_Key_Generator::generate();
		}

		$email  = sanitize_email( (string) ( $args['customer_email'] ?? '' ) );
		$slug   = sanitize_key( (string) ( $args['product_slug'] ?? 'flex-multiple-listing-and-booking-system' ) );
		$status = sanitize_key( (string) ( $args['status'] ?? 'active' ) );
		$limit  = max( 0, (int) ( $args['activation_limit'] ?? 1 ) );
		$order  = absint( $args['order_id'] ?? 0 );
		$notes  = sanitize_textarea_field( (string) ( $args['notes'] ?? '' ) );

		$expires_at = null;
		if ( ! empty( $args['expires_at'] ) ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', (int) $args['expires_at'] );
		} elseif ( ! empty( $args['expires_days'] ) ) {
			$days       = max( 1, (int) $args['expires_days'] );
			$expires_at = gmdate( 'Y-m-d H:i:s', time() + ( $days * DAY_IN_SECONDS ) );
		}

		$table = FBLS_Database::table( 'licenses' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ok = $wpdb->insert(
			$table,
			array(
				'license_key'      => $key,
				'customer_email'   => $email,
				'product_slug'     => $slug,
				'status'           => in_array( $status, array( 'active', 'suspended', 'revoked' ), true ) ? $status : 'active',
				'activation_limit' => $limit,
				'expires_at'       => $expires_at,
				'order_id'         => $order,
				'notes'            => $notes,
				'created_at'       => current_time( 'mysql', true ),
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Get license by key.
	 *
	 * @param string $key License key.
	 * @return array<string, mixed>|null
	 */
	public function get_by_key( $key ) {
		global $wpdb;

		$key   = FBLS_Key_Generator::normalize( $key );
		$table = FBLS_Database::table( 'licenses' );
		if ( '' === $key || '' === $table ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE license_key = %s LIMIT 1", $key ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get license by ID.
	 *
	 * @param int $id License ID.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = FBLS_Database::table( 'licenses' );
		if ( $id < 1 ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Paginated license list.
	 *
	 * @param int    $page   Page.
	 * @param int    $per    Per page.
	 * @param string $search Search term.
	 * @return array{items:array<int,array<string,mixed>>,total:int}
	 */
	public function get_page( $page = 1, $per = 20, $search = '' ) {
		global $wpdb;

		$page   = max( 1, (int) $page );
		$per    = max( 1, min( 100, (int) $per ) );
		$offset = ( $page - 1 ) * $per;
		$table  = FBLS_Database::table( 'licenses' );

		$where = '1=1';
		$args  = array();

		$search = trim( (string) $search );
		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where  .= ' AND (license_key LIKE %s OR customer_email LIKE %s)';
			$args[]  = $like;
			$args[]  = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$args ) ) : $wpdb->get_var( $count_sql ) );

		$list_sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$args[]   = $per;
		$args[]   = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$args ), ARRAY_A );

		return array(
			'items' => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}

	/**
	 * Update license fields.
	 *
	 * @param int                  $id  License ID.
	 * @param array<string, mixed> $data Fields.
	 * @return bool
	 */
	public function update( $id, array $data ) {
		global $wpdb;

		$id  = absint( $id );
		$row = $this->get_by_id( $id );
		if ( ! $row ) {
			return false;
		}

		$update = array();
		$format = array();

		if ( isset( $data['customer_email'] ) ) {
			$update['customer_email'] = sanitize_email( (string) $data['customer_email'] );
			$format[]                 = '%s';
		}
		if ( isset( $data['status'] ) ) {
			$status = sanitize_key( (string) $data['status'] );
			if ( in_array( $status, array( 'active', 'suspended', 'revoked' ), true ) ) {
				$update['status'] = $status;
				$format[]         = '%s';
			}
		}
		if ( isset( $data['activation_limit'] ) ) {
			$update['activation_limit'] = max( 0, (int) $data['activation_limit'] );
			$format[]                   = '%d';
		}
		if ( array_key_exists( 'expires_at', $data ) ) {
			if ( empty( $data['expires_at'] ) ) {
				$update['expires_at'] = null;
				$format[]             = '%s';
			} else {
				$update['expires_at'] = gmdate( 'Y-m-d H:i:s', (int) $data['expires_at'] );
				$format[]             = '%s';
			}
		}
		if ( isset( $data['notes'] ) ) {
			$update['notes'] = sanitize_textarea_field( (string) $data['notes'] );
			$format[]        = '%s';
		}

		if ( empty( $update ) ) {
			return true;
		}

		$update['updated_at'] = current_time( 'mysql', true );
		$format[]             = '%s';

		$table = FBLS_Database::table( 'licenses' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$n = $wpdb->update( $table, $update, array( 'id' => $id ), $format, array( '%d' ) );

		return false !== $n;
	}

	/**
	 * Delete license and activations.
	 *
	 * @param int $id License ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( $id < 1 ) {
			return false;
		}

		$this->delete_activations_for_license( $id );

		$table = FBLS_Database::table( 'licenses' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$n = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		return (bool) $n;
	}

	/**
	 * Count activations for a license.
	 *
	 * @param int $license_id License ID.
	 * @return int
	 */
	public function count_activations( $license_id ) {
		global $wpdb;

		$table = FBLS_Database::table( 'activations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE license_id = %d", absint( $license_id ) )
		);
	}

	/**
	 * List activations.
	 *
	 * @param int $license_id License ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_activations( $license_id ) {
		global $wpdb;

		$table = FBLS_Database::table( 'activations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE license_id = %d ORDER BY activated_at DESC", absint( $license_id ) ),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Site hash for unique activation rows.
	 *
	 * @param string $site_url Site URL.
	 * @return string
	 */
	public function site_hash( $site_url ) {
		$url = strtolower( untrailingslashit( (string) $site_url ) );
		$url = preg_replace( '#^https?://#', '', $url );
		return sha1( $url );
	}

	/**
	 * Activate license on a site.
	 *
	 * @param array<string, mixed> $license License row.
	 * @param string               $site_url Site URL.
	 * @param string               $version Plugin version.
	 * @return array{success:bool,status:string,message:string,expires?:string|int}
	 */
	public function activate_site( array $license, $site_url, $version = '' ) {
		$check = $this->validate_license_row( $license );
		if ( ! $check['success'] ) {
			return $check;
		}

		$license_id = (int) $license['id'];
		$hash       = $this->site_hash( $site_url );
		$existing   = $this->get_activation_by_hash( $license_id, $hash );

		if ( $existing ) {
			$this->touch_activation( (int) $existing['id'], $version );
			return $this->success_response( $license, __( 'License already active on this site.', 'flex-multiple-listing-and-booking-system' ) );
		}

		$limit = (int) $license['activation_limit'];
		$count = $this->count_activations( $license_id );
		if ( $limit > 0 && $count >= $limit ) {
			return array(
				'success' => false,
				'status'  => 'invalid',
				'message' => __( 'Activation limit reached for this license.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		global $wpdb;
		$table = FBLS_Database::table( 'activations' );
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ok = $wpdb->insert(
			$table,
			array(
				'license_id'     => $license_id,
				'site_url'       => esc_url_raw( $site_url ),
				'site_hash'      => $hash,
				'ip_address'     => $ip,
				'plugin_version' => sanitize_text_field( (string) $version ),
				'activated_at'   => current_time( 'mysql', true ),
				'last_seen_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return array(
				'success' => false,
				'status'  => 'invalid',
				'message' => __( 'Could not register activation.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		return $this->success_response( $license, __( 'License activated successfully.', 'flex-multiple-listing-and-booking-system' ) );
	}

	/**
	 * Deactivate license on a site.
	 *
	 * @param array<string, mixed> $license License row.
	 * @param string               $site_url Site URL.
	 * @return array{success:bool,status:string,message:string}
	 */
	public function deactivate_site( array $license, $site_url ) {
		$license_id = (int) $license['id'];
		$hash       = $this->site_hash( $site_url );
		$existing   = $this->get_activation_by_hash( $license_id, $hash );

		if ( $existing ) {
			$this->delete_activation( (int) $existing['id'] );
		}

		return array(
			'success' => true,
			'status'  => 'inactive',
			'message' => __( 'License deactivated on this site.', 'flex-multiple-listing-and-booking-system' ),
		);
	}

	/**
	 * Check license for a site without activating.
	 *
	 * @param array<string, mixed> $license License row.
	 * @param string               $site_url Site URL.
	 * @param string               $version Plugin version.
	 * @return array{success:bool,status:string,message:string,expires?:string|int}
	 */
	public function check_site( array $license, $site_url, $version = '' ) {
		$check = $this->validate_license_row( $license );
		if ( ! $check['success'] ) {
			return $check;
		}

		$license_id = (int) $license['id'];
		$hash       = $this->site_hash( $site_url );
		$existing   = $this->get_activation_by_hash( $license_id, $hash );

		if ( ! $existing ) {
			return array(
				'success' => false,
				'status'  => 'inactive',
				'message' => __( 'License is valid but not activated on this site.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$this->touch_activation( (int) $existing['id'], $version );
		return $this->success_response( $license, __( 'License is valid.', 'flex-multiple-listing-and-booking-system' ) );
	}

	/**
	 * Validate license row status and expiry.
	 *
	 * @param array<string, mixed> $license License row.
	 * @return array{success:bool,status:string,message:string,expires?:string|int}
	 */
	public function validate_license_row( array $license ) {
		if ( 'revoked' === ( $license['status'] ?? '' ) ) {
			return array(
				'success' => false,
				'status'  => 'invalid',
				'message' => __( 'License has been revoked.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		if ( 'suspended' === ( $license['status'] ?? '' ) ) {
			return array(
				'success' => false,
				'status'  => 'invalid',
				'message' => __( 'License is suspended.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		if ( ! empty( $license['expires_at'] ) && '0000-00-00 00:00:00' !== $license['expires_at'] ) {
			$exp = strtotime( (string) $license['expires_at'] . ' UTC' );
			if ( $exp && $exp < time() ) {
				return array(
					'success' => false,
					'status'  => 'expired',
					'message' => __( 'License has expired.', 'flex-multiple-listing-and-booking-system' ),
					'expires' => gmdate( 'Y-m-d', $exp ),
				);
			}
		}

		return array(
			'success' => true,
			'status'  => 'active',
			'message' => '',
		);
	}

	/**
	 * Build success API payload.
	 *
	 * @param array<string, mixed> $license License row.
	 * @param string               $message Message.
	 * @return array{success:bool,status:string,message:string,expires?:string}
	 */
	private function success_response( array $license, $message ) {
		$expires = '';
		if ( ! empty( $license['expires_at'] ) && '0000-00-00 00:00:00' !== $license['expires_at'] ) {
			$expires = gmdate( 'Y-m-d', strtotime( (string) $license['expires_at'] . ' UTC' ) );
		}

		return array(
			'success' => true,
			'status'  => 'active',
			'license' => 'valid',
			'message' => $message,
			'expires' => $expires,
		);
	}

	/**
	 * @param int    $license_id License ID.
	 * @param string $hash Site hash.
	 * @return array<string, mixed>|null
	 */
	private function get_activation_by_hash( $license_id, $hash ) {
		global $wpdb;

		$table = FBLS_Database::table( 'activations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE license_id = %d AND site_hash = %s LIMIT 1", absint( $license_id ), $hash ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param int    $id Activation ID.
	 * @param string $version Plugin version.
	 * @return void
	 */
	private function touch_activation( $id, $version = '' ) {
		global $wpdb;

		$data = array( 'last_seen_at' => current_time( 'mysql', true ) );
		$fmt  = array( '%s' );
		if ( '' !== $version ) {
			$data['plugin_version'] = sanitize_text_field( $version );
			$fmt[]                  = '%s';
		}

		$table = FBLS_Database::table( 'activations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $table, $data, array( 'id' => absint( $id ) ), $fmt, array( '%d' ) );
	}

	/**
	 * @param int $id Activation ID.
	 * @return void
	 */
	public function delete_activation( $id ) {
		global $wpdb;

		$table = FBLS_Database::table( 'activations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * @param int $license_id License ID.
	 * @return void
	 */
	private function delete_activations_for_license( $license_id ) {
		global $wpdb;

		$table = FBLS_Database::table( 'activations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'license_id' => absint( $license_id ) ), array( '%d' ) );
	}
}
