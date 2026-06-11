<?php
/**
 * Plugin Name: Flex Booking License Server
 * Plugin URI: https://wprogers.com/
 * Description: Generate and validate purchase license keys for Flex Listings and Booking Manager. Install on wprogers.com only.
 * Version: 1.0.0
 * Author: WpRogers
 * Author URI: https://wprogers.com/
 * License: GPL v2 or later
 * Text Domain: flex-multiple-listing-and-booking-system
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

define( 'FBLS_VERSION', '1.0.0' );
define( 'FBLS_PLUGIN_FILE', __FILE__ );
define( 'FBLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FBLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FBLS_DB_VERSION', '1.0.0' );

require_once FBLS_PLUGIN_DIR . 'includes/class-fbls-database.php';
require_once FBLS_PLUGIN_DIR . 'includes/class-fbls-key-generator.php';
require_once FBLS_PLUGIN_DIR . 'includes/class-fbls-license-repository.php';
require_once FBLS_PLUGIN_DIR . 'includes/class-fbls-rest-api.php';
require_once FBLS_PLUGIN_DIR . 'includes/class-fbls-admin.php';
require_once FBLS_PLUGIN_DIR . 'includes/class-fbls-woocommerce.php';

/**
 * Bootstrap license server.
 */
final class FBLS_Plugin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		register_activation_hook( FBLS_PLUGIN_FILE, array( 'FBLS_Database', 'activate' ) );
		register_deactivation_hook( FBLS_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Load components.
	 *
	 * @return void
	 */
	public function init() {
		FBLS_Database::maybe_upgrade();
		FBLS_REST_API::register();
		FBLS_WooCommerce::register();

		if ( is_admin() ) {
			FBLS_Admin::register();
		}
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Keep license data on deactivation.
	}
}

FBLS_Plugin::instance();
