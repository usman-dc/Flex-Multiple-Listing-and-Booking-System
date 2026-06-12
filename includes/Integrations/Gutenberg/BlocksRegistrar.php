<?php

/**

 * Registers Gutenberg blocks with full editor UI + server-side rendering.

 *

 * @package FlexBookingSystem

 */



namespace FlexBooking\Integrations\Gutenberg;



defined( 'ABSPATH' ) || exit;



/**

 * Block API registration.

 */

final class BlocksRegistrar {



	/**

	 * Register blocks.

	 *

	 * @return void

	 */

	public static function register() {
		\FlexBooking\Front\FrontController::register_public_assets();

		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );

		$block_styles = array( 'ulbm-bootstrap', 'ulbm-bootstrap-icons', 'ulbm-public' );

		register_block_type(
			'ulbm-booking/form',
			array(
				'api_version'     => 2,
				'title'           => __( 'Flex Listings Booking Form', 'flex-multiple-listing-and-booking-system' ),
				'editor_style'    => $block_styles,
				'style'           => $block_styles,
				'render_callback' => array( __CLASS__, 'render_booking_form' ),
				'attributes'      => array(

					'type' => array( 'type' => 'string', 'default' => '' ),

					'id'   => array( 'type' => 'number', 'default' => 0 ),

				),

			)

		);



		register_block_type(
			'ulbm-booking/grid',
			array(
				'api_version'     => 2,
				'title'           => __( 'Flex Listing Grid', 'flex-multiple-listing-and-booking-system' ),
				'editor_style'    => $block_styles,
				'style'           => $block_styles,
				'render_callback' => array( __CLASS__, 'render_listing_grid' ),
				'attributes'      => self::grid_block_attributes(),
			)
		);



		register_block_type(
			'ulbm-booking/search',
			array(
				'api_version'     => 2,
				'title'           => __( 'Flex Listing Search', 'flex-multiple-listing-and-booking-system' ),
				'editor_style'    => $block_styles,
				'style'           => $block_styles,
				'render_callback' => array( __CLASS__, 'render_search' ),
				'attributes'      => array(

					'layout' => array( 'type' => 'string', 'default' => 'horizontal' ),

				),

			)

		);

	}



	/**

	 * Grid block attribute schema.

	 *

	 * @return array<string, array<string, mixed>>

	 */

	private static function grid_block_attributes() {

		return array(

			'type'          => array( 'type' => 'string', 'default' => '' ),

			'columns'       => array( 'type' => 'number', 'default' => 0 ),

			'limit'         => array( 'type' => 'number', 'default' => 12 ),

			'design'        => array( 'type' => 'string', 'default' => '' ),

			'gap'           => array( 'type' => 'number', 'default' => 0 ),

			'paddingX'      => array( 'type' => 'number', 'default' => 0 ),

			'paddingY'      => array( 'type' => 'number', 'default' => 0 ),

			'marginTop'     => array( 'type' => 'number', 'default' => 0 ),

			'marginBottom'  => array( 'type' => 'number', 'default' => 0 ),

			'cardPadding'   => array( 'type' => 'number', 'default' => 0 ),

		);

	}



	/**

	 * Enqueue blocks editor script with inline data.

	 *

	 * @return void

	 */

	public static function enqueue_editor_assets() {

		$asset_path = ULBM_PLUGIN_URL . 'dist/blocks.js';



		wp_enqueue_script(

			'ulbm-blocks-editor',

			$asset_path,

			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ),

			ULBM_VERSION,

			true

		);



		$type_repo = new \FlexBooking\Booking\BookingTypeRepository();

		$all_types = $type_repo->get_all();

		$types_js  = array();

		foreach ( $all_types as $t ) {

			$types_js[] = array(

				'id'   => (int) $t['id'],

				'name' => (string) $t['name'],

				'slug' => (string) $t['slug'],

			);

		}



		wp_localize_script(
			'ulbm-blocks-editor',
			'ulbmBlockData',
			array(
				'types'   => $types_js,
				'designs' => array_values(
					array_map(
						static function ( $design ) {
							return array(
								'id'    => $design['id'],
								'label' => $design['label'],
							);
						},
						\FlexBooking\Front\GridDesignRegistry::all()
					)
				),
			)
		);

	}



	/**

	 * Render booking form block.

	 *

	 * @param array<string, mixed> $attributes Block attributes.

	 * @return string

	 */

	public static function render_booking_form( $attributes ) {

		$type = isset( $attributes['type'] ) ? sanitize_title( $attributes['type'] ) : '';

		$id   = isset( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;



		return self::render_shortcode_html(
			sprintf(
				'[ulbm_booking_form type="%s" id="%d"]',
				esc_attr( $type ),
				$id
			)
		);

	}



	/**

	 * Render listing grid block.

	 *

	 * @param array<string, mixed> $attributes Block attributes.

	 * @return string

	 */

	public static function render_listing_grid( $attributes ) {

		$type    = isset( $attributes['type'] ) ? sanitize_key( $attributes['type'] ) : '';

		$columns_raw = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 0;

		$limit   = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 12;



		$parts = array(

			sprintf( 'type="%s"', esc_attr( $type ) ),

		);

		if ( $columns_raw > 0 ) {
			$parts[] = sprintf( 'columns="%d"', $columns_raw );
		}

		$parts[] = sprintf( 'limit="%d"', $limit );



		$map = array(

			'gap'          => 'gap',

			'paddingX'     => 'padding_x',

			'paddingY'     => 'padding_y',

			'marginTop'    => 'margin_top',

			'marginBottom' => 'margin_bottom',

			'cardPadding'  => 'card_padding',

		);



		foreach ( $map as $attr_key => $short_key ) {

			if ( ! empty( $attributes[ $attr_key ] ) ) {

				$parts[] = sprintf( '%s="%d"', $short_key, absint( $attributes[ $attr_key ] ) );

			}

		}

		$design = isset( $attributes['design'] ) ? sanitize_key( $attributes['design'] ) : '';

		if ( '' !== $design && \FlexBooking\Front\GridDesignRegistry::get( $design ) ) {

			$parts[] = sprintf( 'design="%s"', esc_attr( $design ) );

		}



		return self::render_shortcode_html( '[ulbm_listing_grid ' . implode( ' ', $parts ) . ']' );

	}



	/**

	 * Render search block.

	 *

	 * @param array<string, mixed> $attributes Block attrs.

	 * @return string

	 */

	public static function render_search( $attributes ) {

		$layout = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'horizontal';



		return self::render_shortcode_html(
			sprintf(
				'[ulbm_search layout="%s"]',
				esc_attr( $layout )
			)
		);

	}



	/**
	 * Run a plugin shortcode and return KSES-safe HTML for block front-end output.
	 *
	 * @param string $shortcode Shortcode invocation string.
	 * @return string
	 */
	private static function render_shortcode_html( $shortcode ) {

		return \FlexBooking\Front\GridFilterUi::kses_grid_html( (string) do_shortcode( $shortcode ) );

	}

}

