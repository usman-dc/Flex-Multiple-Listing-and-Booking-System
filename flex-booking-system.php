<?php
/**
 * Plugin Name: Flex Listings and Booking Manager
 * Plugin URI: https://github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System
 * Description: Multipurpose multiple-listing and booking engine for rentals, tours, appointments, and services ? listing grids, calendars, partner portal, and REST API.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Usman Ali
 * Author URI: https://wprogers.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flex-booking-system
 * Domain Path: /languages
 *
 * @package FlexBookingSystem
 */

defined( 'ABSPATH' ) || exit;

define( 'ULBM_VERSION', '1.0.2' );
define( 'ULBM_PLUGIN_FILE', __FILE__ );
define( 'ULBM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ULBM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ULBM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ULBM_PLUGIN_DISPLAY_NAME', 'Flex Listings and Booking Manager' );
define( 'ULBM_PLUGIN_MENU_LABEL', 'Flex Listings & Booking' );

if ( ! function_exists( 'ulbm_plugin_display_name' ) ) {
	/**
	 * Public plugin display name (translatable).
	 *
	 * @return string
	 */
	function ulbm_plugin_display_name() {
		return __( 'Flex Listings and Booking Manager', 'flex-booking-system' );
	}
}

if ( ! function_exists( 'ulbm_plugin_menu_label' ) ) {
	/**
	 * Short admin menu label (translatable).
	 *
	 * @return string
	 */
	function ulbm_plugin_menu_label() {
		return __( 'Flex Listings & Booking', 'flex-booking-system' );
	}
}

/**
 * Load Composer autoload if present; otherwise register minimal PSR-4 autoloader.
 */
$ulbm_autoload = ULBM_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $ulbm_autoload ) ) {
	require_once $ulbm_autoload;
} else {
	require_once ULBM_PLUGIN_DIR . 'includes/Autoloader.php';
	FlexBooking\Autoloader::register();
}

if ( ! function_exists( 'ulbm_plugin' ) ) {
	/**
	 * Begins execution of the plugin.
	 *
	 * @return FlexBooking\Core\Plugin
	 */
	function ulbm_plugin() {
		return FlexBooking\Core\Plugin::instance();
	}
}

register_activation_hook( __FILE__, array( 'FlexBooking\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FlexBooking\Core\Deactivator', 'deactivate' ) );

ulbm_plugin()->run();
