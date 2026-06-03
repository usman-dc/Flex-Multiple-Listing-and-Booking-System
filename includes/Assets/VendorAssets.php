<?php
/**
 * Bundled third-party CSS/JS (Bootstrap, Bootstrap Icons).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Register bundled vendor styles and scripts.
 */
final class VendorAssets {

	public const BOOTSTRAP_VERSION      = '5.3.3';
	public const BOOTSTRAP_ICONS_VERSION = '1.11.3';

	/**
	 * Register Bootstrap and icons (does not enqueue).
	 *
	 * @return void
	 */
	public static function register_bootstrap() {
		if ( ! wp_style_is( 'fbs-bootstrap', 'registered' ) ) {
			wp_register_style(
				'fbs-bootstrap',
				FBS_PLUGIN_URL . 'assets/vendor/bootstrap/css/bootstrap.min.css',
				array(),
				self::BOOTSTRAP_VERSION
			);
		}

		if ( ! wp_style_is( 'fbs-bootstrap-icons', 'registered' ) ) {
			wp_register_style(
				'fbs-bootstrap-icons',
				FBS_PLUGIN_URL . 'assets/vendor/bootstrap-icons/bootstrap-icons.min.css',
				array(),
				self::BOOTSTRAP_ICONS_VERSION
			);
		}

		if ( ! wp_script_is( 'fbs-bootstrap', 'registered' ) ) {
			wp_register_script(
				'fbs-bootstrap',
				FBS_PLUGIN_URL . 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
				array(),
				self::BOOTSTRAP_VERSION,
				true
			);
		}
	}
}
