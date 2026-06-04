<?php
/**
 * Removes data when plugin is deleted from admin (optional tables wipe).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Handles uninstall cleanup.
 */
final class Uninstaller {

	/**
	 * Execute uninstall.
	 *
	 * @return void
	 */
	public static function run() {
		global $wpdb;

		if ( ! defined( 'ULBM_PLUGIN_DIR' ) ) {
			define( 'ULBM_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) . '/' );
		}

		$remove_data = apply_filters( 'ulbm_uninstall_remove_all_data', true );

		if ( ! $remove_data ) {
			return;
		}

		foreach ( \FlexBooking\Database\Schema::tables() as $logical_key => $table ) {
			$validated = \FlexBooking\Database\Schema::table( (string) $logical_key );
			if ( '' === $validated || $validated !== $table ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $validated ) );
		}

		$options = array(
			'ulbm_db_version',
			'ulbm_setup_completed',
			'ulbm_enabled_industries',
			'ulbm_general_settings',
			'ulbm_payment_settings',
			'ulbm_email_settings',
		);

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		delete_site_option( 'ulbm_network_placeholder' );
	}
}
