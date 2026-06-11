<?php
/**
 * Database schema for license server.
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades license tables.
 */
final class FBLS_Database {

	/**
	 * Table suffixes.
	 */
	public const TABLE_LICENSES    = 'fbls_licenses';
	public const TABLE_ACTIVATIONS = 'fbls_activations';

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'fbls_db_version', FBLS_DB_VERSION, false );
	}

	/**
	 * Run dbDelta when version changes.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'fbls_db_version', '' ) === FBLS_DB_VERSION ) {
			return;
		}
		self::create_tables();
		update_option( 'fbls_db_version', FBLS_DB_VERSION, false );
	}

	/**
	 * Full table name.
	 *
	 * @param string $logical Logical key.
	 * @return string
	 */
	public static function table( $logical ) {
		global $wpdb;
		$map = array(
			'licenses'    => self::TABLE_LICENSES,
			'activations' => self::TABLE_ACTIVATIONS,
		);
		if ( ! isset( $map[ $logical ] ) ) {
			return '';
		}
		return $wpdb->prefix . $map[ $logical ];
	}

	/**
	 * Create tables via dbDelta.
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$licenses = self::table( 'licenses' );
		$acts     = self::table( 'activations' );

		$sql_licenses = "CREATE TABLE {$licenses} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_key varchar(64) NOT NULL,
			customer_email varchar(191) NOT NULL DEFAULT '',
			product_slug varchar(100) NOT NULL DEFAULT 'flex-multiple-listing-and-booking-system',
			status varchar(20) NOT NULL DEFAULT 'active',
			activation_limit int(11) NOT NULL DEFAULT 1,
			expires_at datetime NULL DEFAULT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			notes text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY license_key (license_key),
			KEY status (status),
			KEY customer_email (customer_email),
			KEY order_id (order_id)
		) {$charset};";

		$sql_activations = "CREATE TABLE {$acts} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_id bigint(20) unsigned NOT NULL,
			site_url varchar(255) NOT NULL,
			site_hash char(40) NOT NULL,
			ip_address varchar(45) NOT NULL DEFAULT '',
			plugin_version varchar(20) NOT NULL DEFAULT '',
			activated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_seen_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY license_site (license_id, site_hash),
			KEY license_id (license_id)
		) {$charset};";

		dbDelta( $sql_licenses );
		dbDelta( $sql_activations );
	}
}
