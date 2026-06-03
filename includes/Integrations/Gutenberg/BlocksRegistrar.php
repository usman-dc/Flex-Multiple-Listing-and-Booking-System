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

		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );



		register_block_type(

			'flex-booking/form',

			array(

				'api_version'     => 2,

				'title'           => __( 'Flex MLS Booking Form', 'flex-booking-system' ),

				'render_callback' => array( __CLASS__, 'render_booking_form' ),

				'attributes'      => array(

					'type' => array( 'type' => 'string', 'default' => '' ),

					'id'   => array( 'type' => 'number', 'default' => 0 ),

				),

			)

		);



		register_block_type(

			'flex-booking/grid',

			array(

				'api_version'     => 2,

				'title'           => __( 'Flex MLS Listing Grid', 'flex-booking-system' ),

				'render_callback' => array( __CLASS__, 'render_listing_grid' ),

				'attributes'      => self::grid_block_attributes(),

			)

		);



		register_block_type(

			'flex-booking/search',

			array(

				'api_version'     => 2,

				'title'           => __( 'Flex MLS Listing Search', 'flex-booking-system' ),

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

			'columns'       => array( 'type' => 'number', 'default' => 3 ),

			'limit'         => array( 'type' => 'number', 'default' => 12 ),

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

		$asset_path = FBS_PLUGIN_URL . 'dist/blocks.js';



		wp_enqueue_script(

			'fbs-blocks-editor',

			$asset_path,

			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ),

			FBS_VERSION,

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



		wp_localize_script( 'fbs-blocks-editor', 'fbsBlockData', array( 'types' => $types_js ) );

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



		return do_shortcode( sprintf( '[fbs_booking_form type="%s" id="%d"]', $type, $id ) );

	}



	/**

	 * Render listing grid block.

	 *

	 * @param array<string, mixed> $attributes Block attributes.

	 * @return string

	 */

	public static function render_listing_grid( $attributes ) {

		$type    = isset( $attributes['type'] ) ? sanitize_key( $attributes['type'] ) : '';

		$columns = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;

		$limit   = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 12;



		$parts = array(

			sprintf( 'type="%s"', esc_attr( $type ) ),

			sprintf( 'columns="%d"', $columns ),

			sprintf( 'limit="%d"', $limit ),

		);



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



		return do_shortcode( '[fbs_listing_grid ' . implode( ' ', $parts ) . ']' );

	}



	/**

	 * Render search block.

	 *

	 * @param array<string, mixed> $attributes Block attrs.

	 * @return string

	 */

	public static function render_search( $attributes ) {

		$layout = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'horizontal';



		return do_shortcode( sprintf( '[fbs_search layout="%s"]', $layout ) );

	}

}

