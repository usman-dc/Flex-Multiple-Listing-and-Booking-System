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
		// never ran (manual deploy, migration); without `manage_ulbm_bookings`, wp-admin routes 403.
		add_action(
			'init',
			static function () {
				Capabilities::register();
			},
			1,
			0
		);

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
		add_shortcode( 'ulbm_booking_form', array( $this, 'shortcode_booking_form' ) );
		add_shortcode( 'ulbm_listing_grid', array( $this, 'shortcode_listing_grid' ) );
		add_shortcode( 'ulbm_search', array( $this, 'shortcode_search' ) );
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
			'ulbm_booking_form'
		);

		$ulbm_booking_type = null;
		$type_repo         = new \FlexBooking\Booking\BookingTypeRepository();
		if ( ! empty( $atts['id'] ) ) {
			$ulbm_booking_type = $type_repo->get_by_id( (int) $atts['id'] );
		}

		$ulbm_listing_id = absint( $atts['listing_id'] );
		if ( ! $ulbm_listing_id && is_singular() ) {
			$pt = get_post_type();
			if ( $pt && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( (string) $pt ) ) {
				$ulbm_listing_id = get_the_ID();
			}
		}

		if ( ! $ulbm_booking_type && $ulbm_listing_id > 0 ) {
			$pt = get_post_type( $ulbm_listing_id );
			if ( $pt ) {
				$row = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::booking_type_for_post_type( (string) $pt );
				if ( $row ) {
					$ulbm_booking_type = $type_repo->get_by_id( (int) $row['id'] ) ?: $row;
					if ( empty( $atts['id'] ) ) {
						$atts['id'] = (int) $row['id'];
					}
				}
			}
		}

		$ulbm_form_groups = \FlexBooking\Forms\PublicBookingFields::groups_for_type(
			$ulbm_booking_type,
			$ulbm_listing_id > 0 ? (string) get_post_type( $ulbm_listing_id ) : ''
		);

		$ulbm_prefill = array(
			'customer_email'      => '',
			'customer_phone'      => '',
			'customer_first_name' => '',
			'customer_last_name'  => '',
		);
		if ( is_user_logged_in() ) {
			$user                      = wp_get_current_user();
			$ulbm_prefill['customer_email']      = $user->user_email;
			$ulbm_prefill['customer_first_name'] = (string) get_user_meta( $user->ID, 'first_name', true );
			$ulbm_prefill['customer_last_name']  = (string) get_user_meta( $user->ID, 'last_name', true );
		}

		ob_start();
		include ULBM_PLUGIN_DIR . 'templates/public/booking-form.php';

		return \FlexBooking\Front\LayoutSettings::wrap( (string) ob_get_clean(), 'ulbm-booking-form-wrap' );
	}

	/**
	 * Shortcode: listing grid — displays cards for a booking type's posts.
	 *
	 * Usage: [ulbm_listing_grid type="car-rental" columns="3" limit="12"]
	 *
	 * @param array<string,mixed> $atts Attributes.
	 * @return string
	 */
	public function shortcode_listing_grid( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'          => '',
				'columns'       => '',
				'limit'         => '',
				'design'        => '',
				'gap'           => '',
				'padding_x'     => '',
				'padding_y'     => '',
				'margin_top'    => '',
				'margin_bottom' => '',
				'card_padding'  => '',
			),
			$atts,
			'ulbm_listing_grid'
		);

		$ulbm_grid_type    = sanitize_key( $atts['type'] );
		$ulbm_grid_columns = \FlexBooking\Front\LayoutSettings::grid_columns( $atts['columns'] );
		$ulbm_grid_limit   = \FlexBooking\Front\LayoutSettings::grid_per_page( $atts['limit'] );
		$ulbm_grid_design  = '' !== (string) $atts['design']
			? \FlexBooking\Front\GridDesignRegistry::sanitize_id( (string) $atts['design'] )
			: null;
		$ulbm_grid_spacing = array();
		foreach ( array( 'gap', 'padding_x', 'padding_y', 'margin_top', 'margin_bottom', 'card_padding' ) as $spacing_key ) {
			if ( '' !== (string) $atts[ $spacing_key ] ) {
				$ulbm_grid_spacing[ $spacing_key ] = (int) $atts[ $spacing_key ];
			}
		}

		ob_start();
		include ULBM_PLUGIN_DIR . 'templates/public/listing-grid.php';

		return \FlexBooking\Front\LayoutSettings::wrap( (string) ob_get_clean(), 'ulbm-listing-grid-wrap' );
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
			'ulbm_search'
		);

		return \FlexBooking\Front\LayoutSettings::wrap(
			sprintf(
				'<div class="ulbm-search-root" data-layout="%s"><div class="ulbm-loading spinner-border text-primary" role="status"></div></div>',
				esc_attr( $atts['layout'] )
			),
			'ulbm-search-wrap'
		);
	}
}
