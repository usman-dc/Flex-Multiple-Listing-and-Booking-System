<?php
/**
 * PSR-4 autoloader fallback when Composer vendor is not installed.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking;

defined( 'ABSPATH' ) || exit;

/**
 * Registers FlexBooking\* classes under includes/.
 */
final class Autoloader {

	/**
	 * Namespace prefix.
	 *
	 * @var string
	 */
	private const PREFIX = 'FlexBooking\\';

	/**
	 * Register spl autoload.
	 *
	 * @return void
	 */
	public static function register() {
		self::init_base_dir();
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Resolve base path once.
	 *
	 * @return void
	 */
	private static function init_base_dir() {
		if ( ! defined( 'FBS_PLUGIN_DIR' ) ) {
			define( 'FBS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
		}
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( strpos( $class, self::PREFIX ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = FBS_PLUGIN_DIR . 'includes/' . $relative . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
