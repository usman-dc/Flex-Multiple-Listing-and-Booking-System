<?php
/**
 * Frontend partner portal — shortcodes and boot.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

use FlexBooking\Core\Plugin;
use FlexBooking\Front\LayoutSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers partner shortcodes.
 */
final class VendorController {

	/**
	 * @param Plugin $plugin Kernel.
	 */
	public function __construct( Plugin $plugin ) {
		add_action( 'init', array( VendorRole::class, 'register' ), 2 );
		add_action( 'init', array( $this, 'register_shortcodes' ), 15 );
		add_action( 'init', array( VendorPageProvisioner::class, 'maybe_auto_provision' ), 25 );
	}

	/**
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ulbm_register', array( $this, 'shortcode_register' ) );
		add_shortcode( 'ulbm_login', array( $this, 'shortcode_login' ) );
		add_shortcode( 'ulbm_dashboard', array( $this, 'shortcode_dashboard' ) );
		add_shortcode( 'ulbm_become_partner', array( $this, 'shortcode_become_partner' ) );
	}

	/**
	 * Partner registration form.
	 *
	 * @return string
	 */
	public function shortcode_register() {
		if ( is_user_logged_in() ) {
			if ( VendorRole::can_manage_listings() ) {
				return $this->redirect_notice(
					VendorPages::dashboard_url(),
					__( 'You are already a partner.', 'flex-multiple-listing-and-booking-system' ),
					__( 'Go to dashboard', 'flex-multiple-listing-and-booking-system' )
				);
			}

			ob_start();
			include ULBM_PLUGIN_DIR . 'templates/public/vendor/become-partner.php';
			return $this->wrap_output( (string) ob_get_clean(), 'ulbm-vendor-register-wrap' );
		}

		ob_start();
		include ULBM_PLUGIN_DIR . 'templates/public/vendor/register.php';
		return $this->wrap_output( (string) ob_get_clean(), 'ulbm-vendor-register-wrap' );
	}

	/**
	 * Partner login form.
	 *
	 * @return string
	 */
	public function shortcode_login() {
		if ( is_user_logged_in() ) {
			return $this->redirect_notice(
				VendorPages::dashboard_url(),
				__( 'You are already logged in.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Go to dashboard', 'flex-multiple-listing-and-booking-system' )
			);
		}

		ob_start();
		include ULBM_PLUGIN_DIR . 'templates/public/vendor/login.php';
		return $this->wrap_output( (string) ob_get_clean(), 'ulbm-vendor-login-wrap' );
	}

	/**
	 * Partner dashboard.
	 *
	 * @return string
	 */
	public function shortcode_dashboard() {
		if ( ! is_user_logged_in() ) {
			return $this->redirect_notice(
				VendorPages::login_url(),
				__( 'Please log in to access your dashboard.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Log in', 'flex-multiple-listing-and-booking-system' )
			);
		}

		if ( ! VendorRole::can_manage_listings() && ! current_user_can( 'manage_options' ) ) {
			return $this->wrap_output(
				'<div class="alert alert-warning">' . esc_html__( 'Your account does not have partner access. Contact the site administrator.', 'flex-multiple-listing-and-booking-system' ) . '</div>',
				'ulbm-vendor-dashboard-wrap'
			);
		}

		$user_id = get_current_user_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Dashboard tab navigation (read-only).
		$tab     = isset( $_GET['ulbm_tab'] ) ? sanitize_key( wp_unslash( $_GET['ulbm_tab'] ) ) : 'overview';
		$allowed = array( 'overview', 'listings', 'add', 'bookings', 'profile' );
		if ( ! in_array( $tab, $allowed, true ) ) {
			$tab = 'overview';
		}

		$ulbm_vendor_user      = wp_get_current_user();
		$ulbm_vendor_tab       = $tab;
		$ulbm_vendor_listings  = VendorListingService::get_listings( $user_id );
		$ulbm_vendor_bookings  = VendorListingService::get_vendor_bookings( $user_id, 25 );
		$ulbm_booking_types    = ( new \FlexBooking\Booking\BookingTypeRepository() )->get_all();
		$ulbm_vendor_record    = ( new VendorRepository() )->get_by_user_id( $user_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Edit query arg for own listing form.
		$ulbm_edit_listing_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$ulbm_edit_listing     = null;
		if ( $ulbm_edit_listing_id && VendorListingService::user_owns_listing( $user_id, $ulbm_edit_listing_id ) ) {
			$ulbm_edit_listing = get_post( $ulbm_edit_listing_id );
			$tab              = 'add';
			$ulbm_vendor_tab   = 'add';
		}

		ob_start();
		include ULBM_PLUGIN_DIR . 'templates/public/vendor/dashboard.php';
		return $this->wrap_output( (string) ob_get_clean(), 'ulbm-vendor-dashboard-wrap' );
	}

	/**
	 * CTA block linking to register / dashboard.
	 *
	 * @param array<string,mixed> $atts Attributes.
	 * @return string
	 */
	public function shortcode_become_partner( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => __( 'Become a Partner', 'flex-multiple-listing-and-booking-system' ),
				'text'  => __( 'List your property, car, tour, or service and start receiving bookings.', 'flex-multiple-listing-and-booking-system' ),
			),
			$atts,
			'ulbm_become_partner'
		);

		if ( is_user_logged_in() && VendorRole::can_manage_listings() ) {
			$url   = VendorPages::dashboard_url();
			$label = __( 'Go to Dashboard', 'flex-multiple-listing-and-booking-system' );
		} else {
			$url   = VendorPages::register_url();
			$label = __( 'Register Now', 'flex-multiple-listing-and-booking-system' );
		}

		ob_start();
		?>
		<div class="ulbm-become-partner border rounded p-4 bg-light text-center">
			<h3 class="h4 fw-bold mb-2"><?php echo esc_html( $atts['title'] ); ?></h3>
			<p class="text-muted mb-3"><?php echo esc_html( $atts['text'] ); ?></p>
			<a href="<?php echo esc_url( $url ); ?>" class="btn btn-primary btn-lg"><?php echo esc_html( $label ); ?></a>
			<?php if ( ! is_user_logged_in() ) : ?>
				<p class="small text-muted mt-3 mb-0">
					<?php esc_html_e( 'Already registered?', 'flex-multiple-listing-and-booking-system' ); ?>
					<a href="<?php echo esc_url( VendorPages::login_url() ); ?>"><?php esc_html_e( 'Log in', 'flex-multiple-listing-and-booking-system' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return $this->wrap_output( (string) ob_get_clean(), 'ulbm-become-partner-wrap' );
	}

	/**
	 * @param string $html  Markup.
	 * @param string $class Optional wrapper class.
	 * @return string
	 */
	private function wrap_output( $html, $class = '' ) {
		return LayoutSettings::wrap( $html, $class );
	}

	/**
	 * @param string $url   Target URL.
	 * @param string $msg   Message.
	 * @param string $label Link label.
	 * @return string
	 */
	private function redirect_notice( $url, $msg, $label ) {
		return $this->wrap_output(
			sprintf(
				'<div class="ulbm-auth-notice alert alert-info"><p class="mb-2">%s</p><a class="btn btn-primary btn-sm" href="%s">%s</a></div>',
				esc_html( $msg ),
				esc_url( $url ),
				esc_html( $label )
			),
			'ulbm-auth-notice-wrap'
		);
	}
}
