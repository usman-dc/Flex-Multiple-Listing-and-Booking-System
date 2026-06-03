<?php
/**
 * Installs or upgrades schema via dbDelta.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Schema installer / upgrader.
 */
final class Migrator {

	/**
	 * Install all tables.
	 *
	 * @return void
	 */
	public function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$ddl = Schema::ddl();

		foreach ( $ddl as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'fbs_db_version', Schema::VERSION );
	}

	/**
	 * Run migrations when version bumps.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		$installed = get_option( 'fbs_db_version', '0' );

		if ( version_compare( (string) $installed, Schema::VERSION, '<' ) ) {
			$this->install();
		}

		\FlexBooking\Listings\ListingReviewRepository::ensure_table_exists();
	}
}
