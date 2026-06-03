<?php
/**
 * First-run setup wizard — driven by option fbs_setup_completed.
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
				$label = get_option( 'fbs_setup_completed', false )
					? __( 'Add Industries', 'flex-booking-system' )
					: __( 'Setup Wizard', 'flex-booking-system' );

				add_submenu_page(
					'fbs-dashboard',
					$label,
					$label,
					Capabilities::menu_capability(),
					'fbs-setup',
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
		if ( get_option( 'fbs_setup_completed', false ) ) {
			return;
		}

		if ( ! Capabilities::can_access_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && isset( $screen->id ) && false !== strpos( (string) $screen->id, 'fbs-setup' ) ) {
			return;
		}

		$url = admin_url( 'admin.php?page=fbs-setup' );

		echo '<div class="notice notice-warning is-dismissible"><p>';
		printf(
			wp_kses(
				/* translators: %s: URL to setup wizard */
				__( 'Flex MLS & Booking needs a quick setup: <a href="%s">choose your booking industries</a> (cars, hotels, appointments, …) and finish.', 'flex-booking-system' ),
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
		$fbs_industry_catalog      = IndustryCatalog::definitions();
		$fbs_professional_links    = IndustryCatalog::professional_integrations();
		$fbs_enabled_industries    = get_option( 'fbs_enabled_industries', array() );
		if ( ! is_array( $fbs_enabled_industries ) ) {
			$fbs_enabled_industries = array();
		}

		$path = FBS_PLUGIN_DIR . 'templates/admin/setup-wizard.php';
		if ( is_readable( $path ) ) {
			include $path;
			return;
		}
		echo '<div class="wrap"><h1>Setup</h1></div>';
	}
}
