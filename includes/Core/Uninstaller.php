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

		if ( ! defined( 'FBS_PLUGIN_DIR' ) ) {
			define( 'FBS_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) . '/' );
		}

		$remove_data = apply_filters( 'fbs_uninstall_remove_all_data', true );

		if ( ! $remove_data ) {
			return;
		}

		$tables = \FlexBooking\Database\Schema::table_names();

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from controlled list.
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		}

		$options = array(
			'fbs_db_version',
			'fbs_setup_completed',
			'fbs_enabled_industries',
			'fbs_general_settings',
			'fbs_payment_settings',
			'fbs_email_settings',
		);

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		delete_site_option( 'fbs_network_placeholder' );
	}
}
