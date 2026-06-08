<?php

/**

 * Public assets, shortcode dependencies, dashboard routes (future).

 *

 * @package FlexBookingSystem

 */



namespace FlexBooking\Front;



use FlexBooking\Core\Plugin;

use FlexBooking\Assets\VendorAssets;
use FlexBooking\Front\ColorSettings;



defined( 'ABSPATH' ) || exit;



/**

 * Frontend bootstrap.

 */

final class FrontController {



	/**

	 * Whether public asset handles were registered this request.

	 *

	 * @var bool

	 */

	private static $assets_registered = false;



	/**

	 * Constructor registers assets.

	 *

	 * @param Plugin $plugin Kernel.

	 */

	public function __construct( Plugin $plugin ) {

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_public_assets' ), 5 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ), 20 );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_block_assets' ) );

		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );

	}



	/**

	 * Register Bootstrap + plugin bundles (handles only).

	 *

	 * @return void

	 */

	public static function register_public_assets() {

		if ( self::$assets_registered ) {

			return;

		}

		self::$assets_registered = true;



		VendorAssets::register_bootstrap();



		wp_register_style(

			'ulbm-public',

			ULBM_PLUGIN_URL . 'dist/public.css',

			array( 'ulbm-bootstrap', 'ulbm-bootstrap-icons' ),

			ULBM_VERSION

		);






		wp_register_script(

			'ulbm-public',

			ULBM_PLUGIN_URL . 'dist/public.js',

			array( 'ulbm-bootstrap', 'jquery' ),

			ULBM_VERSION,

			true

		);

	}



	/**
	 * Styles for block editor chrome (sidebar, toolbar).
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_assets() {
		self::enqueue_editor_block_styles();
	}

	/**
	 * Styles inside the block editor canvas iframe (ServerSideRender previews).
	 *
	 * @return void
	 */
	public static function enqueue_block_assets() {
		if ( ! is_admin() ) {
			return;
		}

		self::enqueue_editor_block_styles();
	}

	/**
	 * Bootstrap + plugin CSS for Gutenberg block previews.
	 *
	 * @return void
	 */
	private static function enqueue_editor_block_styles() {
		self::register_public_assets();

		wp_enqueue_style( 'ulbm-bootstrap' );
		wp_enqueue_style( 'ulbm-bootstrap-icons' );
		wp_enqueue_style( 'ulbm-public' );

		$inline = ColorSettings::inline_css();
		if ( '' !== $inline ) {
			wp_add_inline_style( 'ulbm-public', $inline );
		}
	}



	/**
	 * Mark pages that use Flex Booking UI (for color variable scope).
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		if ( self::is_ulbm_frontend_page() ) {
			$classes[] = 'ulbm-booking-active';
		}

		return $classes;
	}



	/**

	 * Enqueue Bootstrap 5 + built bundle when shortcode/block present (lightweight global handle).

	 *

	 * @return void

	 */

	public function enqueue() {

		if ( ! apply_filters( 'ulbm_enqueue_public_assets', self::is_ulbm_frontend_page() ) ) {

			return;

		}



		self::register_public_assets();



		wp_enqueue_style( 'ulbm-bootstrap' );

		wp_enqueue_style( 'ulbm-bootstrap-icons' );

		wp_enqueue_style( 'ulbm-public' );



		$inline = ColorSettings::inline_css();

		if ( '' !== $inline ) {

			wp_add_inline_style( 'ulbm-public', $inline );

		}



		wp_enqueue_script( 'ulbm-bootstrap' );

		wp_enqueue_script( 'ulbm-public' );



		wp_localize_script(
			'ulbm-public',
			'ulbmPublic',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'ulbm/v1' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'bookingNonce' => wp_create_nonce( 'ulbm_public_booking' ),
				'i18n'         => array(
					'noProperties'   => __( 'No properties found', 'flex-multiple-listing-and-booking-system' ),
					'showingCount'   =>
						/* translators: %1$d: first visible listing number, %2$d: last visible listing number, %3$d: total listings. */
						__( 'Showing %1$d–%2$d of %3$d properties', 'flex-multiple-listing-and-booking-system' ),
					'filterFailed'    => __( 'Filter request failed.', 'flex-multiple-listing-and-booking-system' ),
					'sessionExpired'  => __( 'Session expired. Please refresh the page and try again.', 'flex-multiple-listing-and-booking-system' ),
				),
			)
		);

	}



	/**

	 * Detect if current singular content likely needs assets.

	 *

	 * @return bool

	 */

	/**
	 * Whether the current request should load Flex Booking frontend assets.
	 *
	 * @return bool
	 */
	public static function is_ulbm_frontend_page() {
		$post = get_post();

		$post_type = '';
		if ( is_singular() ) {
			$queried_id = get_queried_object_id();
			$post_type  = $queried_id ? (string) get_post_type( $queried_id ) : (string) get_post_type();
		}

		if ( is_singular() && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( $post_type ) ) {
			return true;
		}

		if ( is_post_type_archive() ) {
			$archive_type = get_query_var( 'post_type' );
			if ( is_array( $archive_type ) ) {
				$archive_type = reset( $archive_type );
			}
			if ( is_string( $archive_type ) && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( $archive_type ) ) {
				return true;
			}
		}

		if ( \FlexBooking\Vendor\VendorPages::is_vendor_page( $post ) ) {
			return true;
		}

		if ( self::page_has_elementor_ulbm_widgets() ) {
			return true;
		}

		if ( ! is_singular() ) {
			return false;
		}

		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'ulbm_booking_form' )
			|| has_shortcode( $post->post_content, 'ulbm_search' )
			|| has_shortcode( $post->post_content, 'ulbm_listing_grid' )
			|| has_shortcode( $post->post_content, 'ulbm_register' )
			|| has_shortcode( $post->post_content, 'ulbm_login' )
			|| has_shortcode( $post->post_content, 'ulbm_dashboard' )
			|| has_shortcode( $post->post_content, 'ulbm_become_partner' )
			|| ( function_exists( 'has_block' ) && ( has_block( 'ulbm-booking/form', $post ) || has_block( 'ulbm-booking/search', $post ) || has_block( 'ulbm-booking/grid', $post ) ) );
	}

	/**
	 * Whether current singular page is built with Elementor and contains FBS widgets.
	 *
	 * @return bool
	 */
	private static function page_has_elementor_ulbm_widgets() {

		if ( ! is_singular() || ! class_exists( '\Elementor\Plugin' ) ) {

			return false;

		}



		$post_id = get_the_ID();

		if ( ! $post_id ) {

			return false;

		}



		$db = \Elementor\Plugin::$instance->db;

		if ( ! $db || ! method_exists( $db, 'is_built_with_elementor' ) || ! $db->is_built_with_elementor( $post_id ) ) {

			return false;

		}



		$data = get_post_meta( $post_id, '_elementor_data', true );

		if ( ! is_string( $data ) || '' === $data ) {

			return false;

		}



		return false !== strpos( $data, 'ulbm_listing_grid' )

			|| false !== strpos( $data, 'ulbm_booking_form' );

	}

}

