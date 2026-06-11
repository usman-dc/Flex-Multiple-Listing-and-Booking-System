<?php
/**
 * Deactivation cleanup (non-destructive).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin deactivation hook handler.
 */
final class Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		\FlexBooking\License\LicenseManager::clear_cron();
		flush_rewrite_rules();
	}
}
