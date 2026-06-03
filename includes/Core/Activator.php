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
			'fbs_db_version'       => \FlexBooking\Database\Schema::VERSION,
			'fbs_setup_completed'  => false,
			'fbs_enabled_industries' => array(),
			'fbs_general_settings' => wp_json_encode(
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

		$tables = \FlexBooking\Database\Schema::tables();
		$table  = $tables['booking_types'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		if ( $count > 0 ) {
			return;
		}

		$now = current_time( 'mysql' );
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
