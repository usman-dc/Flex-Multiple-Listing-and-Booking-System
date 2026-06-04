<?php
/**
 * Loads optional integrations — WooCommerce, Elementor, Gutenberg — only when plugins active.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Integrations;

use FlexBooking\Core\Plugin;
use FlexBooking\Front\ColorSettings;
use FlexBooking\Front\FrontController;

defined( 'ABSPATH' ) || exit;

/**
 * Integration bootstrap — keeps core free of hard dependencies.
 */
final class IntegrationLoader {

	/**
	 * Register integration modules.
	 *
	 * @param Plugin $plugin Kernel.
	 * @return void
	 */
	public static function register( Plugin $plugin ) {
		if ( class_exists( '\WooCommerce' ) ) {
			WooCommerce\WooCommerceBridge::boot();
		}

		add_action(
			'elementor/widgets/register',
			function ( $widgets_manager ) {
				if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
					return;
				}
				require_once ULBM_PLUGIN_DIR . 'includes/Integrations/Elementor/Widgets/BookingFormWidget.php';
				require_once ULBM_PLUGIN_DIR . 'includes/Integrations/Elementor/Widgets/ListingGridWidget.php';
				$widgets_manager->register( new Elementor\Widgets\BookingFormWidget() );
				$widgets_manager->register( new Elementor\Widgets\ListingGridWidget() );
			}
		);

		add_action(
			'elementor/frontend/after_register_styles',
			function () {
				FrontController::register_public_assets();
			}
		);

		add_action(
			'elementor/editor/after_enqueue_scripts',
			function () {
				FrontController::register_public_assets();
				wp_enqueue_style( 'ulbm-public' );
				$inline = ColorSettings::inline_css();
				if ( '' !== $inline ) {
					wp_add_inline_style( 'ulbm-public', $inline );
				}
			}
		);

		add_action(
			'init',
			function () {
				if ( function_exists( 'register_block_type' ) ) {
					Gutenberg\BlocksRegistrar::register();
				}
			}
		);

		do_action( 'ulbm_integrations_loaded', $plugin );
	}
}
