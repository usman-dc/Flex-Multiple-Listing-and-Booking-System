<?php
/**
 * Uninstall routine — removes plugin options and custom tables when delete is triggered from WP admin.
 *
 * @package FlexBookingSystem
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

define( 'FBS_PLUGIN_DIR', dirname( __FILE__ ) . '/' );

$fbs_autoload = FBS_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $fbs_autoload ) ) {
	require_once $fbs_autoload;
} else {
	require_once FBS_PLUGIN_DIR . 'includes/Autoloader.php';
	FlexBooking\Autoloader::register();
}

FlexBooking\Core\Uninstaller::run();
