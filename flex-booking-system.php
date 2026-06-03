<?php
/**
 * Plugin Name: Flex Multiple Listing and Booking System
 * Plugin URI: https://github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System
 * Description: Multipurpose multiple-listing and booking engine for rentals, tours, appointments, and services — listing grids, calendars, partner portal, and REST API.
 * Version: 1.0.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Usman Ali
 * Author URI: https://wprogers.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flex-multiple-listing-and-booking-system
 * Domain Path: /languages
 *
 * @package FlexBookingSystem
 */

defined( 'ABSPATH' ) || exit;

define( 'FBS_VERSION', '1.0.1' );
define( 'FBS_PLUGIN_FILE', __FILE__ );
define( 'FBS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FBS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FBS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FBS_PLUGIN_DISPLAY_NAME', 'Flex Multiple Listing and Booking System' );
define( 'FBS_PLUGIN_MENU_LABEL', 'Flex MLS & Booking' );

if ( ! function_exists( 'fbs_plugin_display_name' ) ) {
	/**
	 * Public plugin display name (translatable).
	 *
	 * @return string
	 */
	function fbs_plugin_display_name() {
		return __( 'Flex Multiple Listing and Booking System', 'flex-multiple-listing-and-booking-system' );
	}
}

if ( ! function_exists( 'fbs_plugin_menu_label' ) ) {
	/**
	 * Short admin menu label (translatable).
	 *
	 * @return string
	 */
	function fbs_plugin_menu_label() {
		return __( 'Flex MLS & Booking', 'flex-multiple-listing-and-booking-system' );
	}
}

/**
 * Load Composer autoload if present; otherwise register minimal PSR-4 autoloader.
 */
$fbs_autoload = FBS_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $fbs_autoload ) ) {
	require_once $fbs_autoload;
} else {
	require_once FBS_PLUGIN_DIR . 'includes/Autoloader.php';
	FlexBooking\Autoloader::register();
}

if ( ! function_exists( 'fbs_plugin' ) ) {
	/**
	 * Begins execution of the plugin.
	 *
	 * @return FlexBooking\Core\Plugin
	 */
	function fbs_plugin() {
		return FlexBooking\Core\Plugin::instance();
	}
}

register_activation_hook( __FILE__, array( 'FlexBooking\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FlexBooking\Core\Deactivator', 'deactivate' ) );

fbs_plugin()->run();
