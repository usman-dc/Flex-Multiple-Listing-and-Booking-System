<?php
/**
 * First-run setup wizard — driven by option ulbm_setup_completed.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Admin;

use FlexBooking\Core\Capabilities;
use FlexBooking\Core\Plugin;
use FlexBooking\Setup\IndustryCatalog;

defined( 'ABSPATH' ) || exit;

/**
 * Registers wizard submenu when setup incomplete.
 */
final class SetupWizard {

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Kernel (reserved for future steps persistence API).
	 */
	public function __construct( Plugin $plugin ) {
		add_action(
			'admin_menu',
			function () {
				$label = get_option( 'ulbm_setup_completed', false )
					? __( 'Add Industries', 'flex-booking-system' )
					: __( 'Setup Wizard', 'flex-booking-system' );

				add_submenu_page(
					'ulbm-dashboard',
					$label,
					$label,
					Capabilities::menu_capability(),
					'ulbm-setup',
					array( $this, 'render' )
				);
			},
			20
		);

		add_action( 'admin_notices', array( $this, 'incomplete_notice' ) );
	}

	/**
	 * Prompt admins until onboarding is finished.
	 *
	 * @return void
	 */
	public function incomplete_notice() {
		if ( get_option( 'ulbm_setup_completed', false ) ) {
			return;
		}

		if ( ! Capabilities::can_access_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && isset( $screen->id ) && false !== strpos( (string) $screen->id, 'ulbm-setup' ) ) {
			return;
		}

		$url = admin_url( 'admin.php?page=ulbm-setup' );

		echo '<div class="notice notice-warning is-dismissible"><p>';
		printf(
			wp_kses(
				/* translators: %s: URL to setup wizard */
				__( 'Flex Listings & Booking needs a quick setup: <a href="%s">choose your booking industries</a> (cars, hotels, appointments, …) and finish.', 'flex-booking-system' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_url( $url )
		);
		echo '</p></div>';
	}

	/**
	 * Render wizard steps.
	 *
	 * @return void
	 */
	public function render() {
		$ulbm_industry_catalog      = IndustryCatalog::definitions();
		$ulbm_professional_links    = IndustryCatalog::professional_integrations();
		$ulbm_enabled_industries    = get_option( 'ulbm_enabled_industries', array() );
		if ( ! is_array( $ulbm_enabled_industries ) ) {
			$ulbm_enabled_industries = array();
		}

		$path = ULBM_PLUGIN_DIR . 'templates/admin/setup-wizard.php';
		if ( is_readable( $path ) ) {
			include $path;
			return;
		}
		echo '<div class="wrap"><h1>Setup</h1></div>';
	}
}
