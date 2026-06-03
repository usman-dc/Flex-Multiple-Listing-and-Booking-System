<?php
/**
 * Main plugin bootstrap — wires container, hooks, admin, public, REST.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton application kernel.
 */
final class Plugin {

	/**
	 * Instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Hook loader.
	 *
	 * @var Loader
	 */
	private $loader;

	/**
	 * Get singleton.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — private for singleton.
	 */
	private function __construct() {
		$this->container = new Container();
		$this->loader    = new Loader();

		$this->register_services();
	}

	/**
	 * Register default services.
	 *
	 * @return void
	 */
	private function register_services() {
		$this->container->set(
			'logger',
			function () {
				return new \FlexBooking\Logging\Logger();
			}
		);

		$this->container->set(
			'booking.engine',
			function ( Container $c ) {
				return new \FlexBooking\Booking\BookingEngine( $c );
			}
		);

		$this->container->set(
			'booking.repository',
			function () {
				return new \FlexBooking\Booking\BookingRepository();
			}
		);

		$this->container->set(
			'booking_type.registry',
			function () {
				return new \FlexBooking\Modules\BookingTypeRegistry();
			}
		);
	}

	/**
	 * Expose container for extensions.
	 *
	 * @return Container
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Boot plugin.
	 *
	 * @return void
	 */
	public function run() {
		$migrator = new \FlexBooking\Database\Migrator();
		$migrator->maybe_upgrade();

		// Grant custom caps on every load — activation-only registration skips sites where the hook
		// never ran (manual deploy, migration); without `manage_fbs_bookings`, wp-admin routes 403.
		add_action(
			'init',
			static function () {
				Capabilities::register();
			},
			1,
			0
		);

		$this->load_textdomain();
		$this->define_hooks();

		$this->loader->register();

		new \FlexBooking\Hooks\ActionHooks( $this );
		new \FlexBooking\Hooks\FilterHooks( $this );

		new \FlexBooking\PostTypes\IndustryPostTypeRegistry();
		new \FlexBooking\PostTypes\BookingTypePostTypeRegistry();

		\FlexBooking\Listings\ListingPostType::register();
		if ( is_admin() ) {
			\FlexBooking\Listings\ListingMetabox::register();
		}

		if ( is_admin() ) {
			new \FlexBooking\Admin\Admin( $this );
			new \FlexBooking\Admin\SetupWizard( $this );
		} else {
			new \FlexBooking\Front\FrontController( $this );
		}

		new \FlexBooking\Rest\RestRegistrar( $this );
		new \FlexBooking\Ajax\AjaxDispatcher( $this );

		new \FlexBooking\Vendor\VendorController( $this );

		\FlexBooking\Integrations\IntegrationLoader::register( $this );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		$this->loader->add_action(
			'plugins_loaded',
			function () {
				load_plugin_textdomain(
					'flex-booking-system',
					false,
					dirname( FBS_PLUGIN_BASENAME ) . '/languages/'
				);
			},
			5,
			0
		);
	}

	/**
	 * Core hooks shared across requests.
	 *
	 * @return void
	 */
	private function define_hooks() {
		$this->loader->add_action(
			'init',
			array( $this, 'register_shortcodes' ),
			10,
			0
		);
	}

	/**
	 * Register legacy shortcodes (blocks/widgets call same render services later).
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'fbs_booking_form', array( $this, 'shortcode_booking_form' ) );
		add_shortcode( 'fbs_listing_grid', array( $this, 'shortcode_listing_grid' ) );
		add_shortcode( 'fbs_search', array( $this, 'shortcode_search' ) );
	}

	/**
	 * Shortcode: booking form shell.
	 *
	 * @param array<string,mixed> $atts Attributes.
	 * @return string
	 */
	public function shortcode_booking_form( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'       => '',
				'id'         => 0,
				'listing_id' => 0,
			),
			$atts,
			'fbs_booking_form'
		);

		$fbs_booking_type = null;
		if ( ! empty( $atts['id'] ) ) {
			$type_repo        = new \FlexBooking\Booking\BookingTypeRepository();
			$fbs_booking_type = $type_repo->get_by_id( (int) $atts['id'] );
		}

		$fbs_listing_id = absint( $atts['listing_id'] );
		if ( ! $fbs_listing_id && is_singular() ) {
			$pt = get_post_type();
			if ( $pt && 0 === strpos( (string) $pt, 'fbs_' ) && 'fbs_listing' !== $pt ) {
				$fbs_listing_id = get_the_ID();
			}
		}

		$fbs_form_groups = \FlexBooking\Forms\PublicBookingFields::groups_for_type( $fbs_booking_type );

		$fbs_prefill = array(
			'customer_email'      => '',
			'customer_phone'      => '',
			'customer_first_name' => '',
			'customer_last_name'  => '',
		);
		if ( is_user_logged_in() ) {
			$user                      = wp_get_current_user();
			$fbs_prefill['customer_email']      = $user->user_email;
			$fbs_prefill['customer_first_name'] = (string) get_user_meta( $user->ID, 'first_name', true );
			$fbs_prefill['customer_last_name']  = (string) get_user_meta( $user->ID, 'last_name', true );
		}

		ob_start();
		include FBS_PLUGIN_DIR . 'templates/public/booking-form.php';

		return \FlexBooking\Front\LayoutSettings::wrap( (string) ob_get_clean(), 'fbs-booking-form-wrap' );
	}

	/**
	 * Shortcode: listing grid — displays cards for a booking type's posts.
	 *
	 * Usage: [fbs_listing_grid type="car-rental" columns="3" limit="12"]
	 *
	 * @param array<string,mixed> $atts Attributes.
	 * @return string
	 */
	public function shortcode_listing_grid( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'          => '',
				'columns'       => 3,
				'limit'         => 12,
				'gap'           => '',
				'padding_x'     => '',
				'padding_y'     => '',
				'margin_top'    => '',
				'margin_bottom' => '',
				'card_padding'  => '',
			),
			$atts,
			'fbs_listing_grid'
		);

		$fbs_grid_type    = sanitize_key( $atts['type'] );
		$fbs_grid_columns = max( 1, min( 6, (int) $atts['columns'] ) );
		$fbs_grid_limit   = max( 1, min( 100, (int) $atts['limit'] ) );
		$fbs_grid_spacing = array();
		foreach ( array( 'gap', 'padding_x', 'padding_y', 'margin_top', 'margin_bottom', 'card_padding' ) as $spacing_key ) {
			if ( '' !== (string) $atts[ $spacing_key ] ) {
				$fbs_grid_spacing[ $spacing_key ] = (int) $atts[ $spacing_key ];
			}
		}

		ob_start();
		include FBS_PLUGIN_DIR . 'templates/public/listing-grid.php';

		return \FlexBooking\Front\LayoutSettings::wrap( (string) ob_get_clean(), 'fbs-listing-grid-wrap' );
	}

	/**
	 * Shortcode: search placeholder (AJAX-powered UI loads via asset bundle).
	 *
	 * @param array<string,mixed> $atts Attributes.
	 * @return string
	 */
	public function shortcode_search( $atts ) {
		$atts = shortcode_atts(
			array(
				'layout' => 'horizontal',
			),
			$atts,
			'fbs_search'
		);

		return \FlexBooking\Front\LayoutSettings::wrap(
			sprintf(
				'<div class="fbs-search-root" data-layout="%s"><div class="fbs-loading spinner-border text-primary" role="status"></div></div>',
				esc_attr( $atts['layout'] )
			),
			'fbs-search-wrap'
		);
	}
}
