<?php
/**
 * Uninstall routine — removes plugin options and custom tables when delete is triggered from WP admin.
 *
 * @package FlexBookingSystem
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

define( 'ULBM_PLUGIN_DIR', dirname( __FILE__ ) . '/' );

$ulbm_autoload = ULBM_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $ulbm_autoload ) ) {
	require_once $ulbm_autoload;
} else {
	require_once ULBM_PLUGIN_DIR . 'includes/Autoloader.php';
	FlexBooking\Autoloader::register();
}

FlexBooking\Core\Uninstaller::run();
