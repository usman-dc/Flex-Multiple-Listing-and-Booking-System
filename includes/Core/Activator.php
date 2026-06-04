<?php
/**
 * Activation: DB schema, defaults, capabilities.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation hook handler.
 */
final class Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @param bool $network_wide Multisite network activation.
	 * @return void
	 */
	public static function activate( $network_wide ) {
		if ( $network_wide && is_multisite() ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::activate_site();
				restore_current_blog();
			}
		} else {
			self::activate_site();
		}
	}

	/**
	 * Single-site activation tasks.
	 *
	 * @return void
	 */
	private static function activate_site() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$migrator = new \FlexBooking\Database\Migrator();
		$migrator->install();

		Capabilities::register();

		$defaults = array(
			'ulbm_db_version'       => \FlexBooking\Database\Schema::VERSION,
			'ulbm_setup_completed'  => false,
			'ulbm_enabled_industries' => array(),
			'ulbm_general_settings' => wp_json_encode(
				array(
					'currency'          => 'USD',
					'currency_position' => 'left',
					'date_format'       => 'Y-m-d',
					'time_format'       => 'H:i',
				)
			),
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key, false ) ) {
				add_option( $key, $value, '', 'no' );
			}
		}

		self::maybe_seed_demo_booking_type();

		\FlexBooking\Vendor\VendorPageProvisioner::maybe_auto_provision();

		flush_rewrite_rules();
	}

	/**
	 * Ensure at least one booking type exists so engines and demos validate.
	 *
	 * @return void
	 */
	private static function maybe_seed_demo_booking_type() {
		global $wpdb;

		$table = \FlexBooking\Database\Schema::table( 'booking_types' );
		if ( '' === $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
		if ( $count > 0 ) {
			return;
		}

		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table,
			array(
				'name'        => 'General Booking',
				'slug'        => 'general',
				'description' => '',
				'module_key'  => 'generic',
				'settings'    => wp_json_encode( array( 'mode' => 'daily' ) ),
				'status'      => 'publish',
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}
