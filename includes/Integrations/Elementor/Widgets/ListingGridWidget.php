<?php

/**

 * Elementor widget: embeds [ulbm_listing_grid] with full controls.

 *

 * @package FlexBookingSystem

 */



namespace FlexBooking\Integrations\Elementor\Widgets;



use Elementor\Controls_Manager;

use Elementor\Widget_Base;



defined( 'ABSPATH' ) || exit;



/**

 * Listing grid widget for Elementor.

 */

final class ListingGridWidget extends Widget_Base {



	/**

	 * Widget slug.

	 *

	 * @return string

	 */

	public function get_name() {

		return 'ulbm_listing_grid';

	}



	/**

	 * Title in Elementor panel.

	 *

	 * @return string

	 */

	public function get_title() {

		return __( 'Flex Listing Grid', 'flex-multiple-listing-and-booking-system' );

	}



	/**

	 * Icon.

	 *

	 * @return string

	 */

	public function get_icon() {

		return 'eicon-gallery-grid';

	}



	/**

	 * Categories.

	 *

	 * @return string[]

	 */

	public function get_categories() {

		return array( 'general' );

	}



	/**

	 * Keywords for search.

	 *

	 * @return string[]

	 */

	public function get_keywords() {

		return array( 'booking', 'listing', 'grid', 'filter', 'search' );

	}



	/**

	 * @return string[]

	 */

	public function get_style_depends() {

		return array( 'ulbm-bootstrap', 'ulbm-bootstrap-icons', 'ulbm-public' );

	}



	/**

	 * @return string[]

	 */

	public function get_script_depends() {

		return array( 'ulbm-bootstrap', 'ulbm-public' );

	}



	/**

	 * Register controls.

	 *

	 * @return void

	 */

	protected function register_controls() {

		$type_repo = new \FlexBooking\Booking\BookingTypeRepository();

		$all_types = $type_repo->get_all();

		$type_opts = array( '' => __( 'All Types', 'flex-multiple-listing-and-booking-system' ) );

		foreach ( $all_types as $t ) {

			$type_opts[ (string) $t['slug'] ] = (string) $t['name'] . ' (#' . (int) $t['id'] . ')';

		}



		$this->start_controls_section(

			'content_section',

			array(

				'label' => __( 'Grid Settings', 'flex-multiple-listing-and-booking-system' ),

				'tab'   => Controls_Manager::TAB_CONTENT,

			)

		);



		$this->add_control(

			'booking_type',

			array(

				'label'   => __( 'Booking Type', 'flex-multiple-listing-and-booking-system' ),

				'type'    => Controls_Manager::SELECT,

				'default' => '',

				'options' => $type_opts,

			)

		);



		$this->add_control(

			'columns',

			array(

				'label'   => __( 'Columns', 'flex-multiple-listing-and-booking-system' ),

				'type'    => Controls_Manager::SELECT,

				'default' => '3',

				'options' => array(

					'2' => '2',

					'3' => '3',

					'4' => '4',

				),

			)

		);



		$this->add_control(

			'limit',

			array(

				'label'   => __( 'Listings per page', 'flex-multiple-listing-and-booking-system' ),

				'type'    => Controls_Manager::NUMBER,

				'default' => 12,

				'min'     => 1,

				'max'     => 50,

			)

		);



		$this->end_controls_section();



		$this->start_controls_section(

			'spacing_section',

			array(

				'label' => __( 'Spacing', 'flex-multiple-listing-and-booking-system' ),

				'tab'   => Controls_Manager::TAB_STYLE,

			)

		);



		$this->add_responsive_control(

			'grid_gap',

			array(

				'label'      => __( 'Column gap (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-grid' => '--ulbm-grid-gap: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->add_control(

			'grid_padding_x',

			array(

				'label'      => __( 'Padding left/right (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-grid' => '--ulbm-grid-padding-x: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->add_control(

			'grid_padding_y',

			array(

				'label'      => __( 'Padding top/bottom (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-grid' => '--ulbm-grid-padding-y: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->add_control(

			'grid_margin_top',

			array(

				'label'      => __( 'Margin top (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-grid' => '--ulbm-grid-margin-top: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->add_control(

			'grid_margin_bottom',

			array(

				'label'      => __( 'Margin bottom (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-grid' => '--ulbm-grid-margin-bottom: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->add_control(

			'grid_card_padding',

			array(

				'label'      => __( 'Card body padding (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-grid' => '--ulbm-grid-card-padding: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->end_controls_section();



		$this->start_controls_section(

			'style_section',

			array(

				'label' => __( 'Cards', 'flex-multiple-listing-and-booking-system' ),

				'tab'   => Controls_Manager::TAB_STYLE,

			)

		);



		$this->add_control(

			'card_border_radius',

			array(

				'label'      => __( 'Card border radius (px)', 'flex-multiple-listing-and-booking-system' ),

				'type'       => Controls_Manager::SLIDER,

				'size_units' => array( 'px' ),

				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),

				'default'    => array( 'unit' => 'px', 'size' => 12 ),

				'selectors'  => array(

					'{{WRAPPER}} .ulbm-listing-card' => 'border-radius: {{SIZE}}{{UNIT}};',

				),

			)

		);



		$this->add_control(

			'card_hover_shadow',

			array(

				'label'   => __( 'Hover shadow', 'flex-multiple-listing-and-booking-system' ),

				'type'    => Controls_Manager::SWITCHER,

				'default' => 'yes',

			)

		);



		$this->add_control(

			'primary_color',

			array(

				'label'       => __( 'Override primary color (optional)', 'flex-multiple-listing-and-booking-system' ),

				'type'        => Controls_Manager::COLOR,

				'description' => __( 'Leave empty to use Flex Listings & Booking → Settings → Colors.', 'flex-multiple-listing-and-booking-system' ),

				'selectors'   => array(

					'{{WRAPPER}} .ulbm-root' => '--ulbm-primary: {{VALUE}}; --bs-primary: {{VALUE}};',

					'{{WRAPPER}} .btn-primary' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',

					'{{WRAPPER}} .ulbm-card-price .ulbm-price-current' => 'color: {{VALUE}};',

					'{{WRAPPER}} .ulbm-listing-card .ulbm-card-title a:hover' => 'color: {{VALUE}};',

				),

			)

		);



		$this->end_controls_section();

	}



	/**

	 * Frontend output.

	 *

	 * @return void

	 */

	protected function render() {

		$settings = $this->get_settings_for_display();

		$type     = isset( $settings['booking_type'] ) ? sanitize_key( $settings['booking_type'] ) : '';

		$columns  = isset( $settings['columns'] ) ? absint( $settings['columns'] ) : 3;

		$limit    = isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 12;



		$parts = array(

			sprintf( 'type="%s"', esc_attr( $type ) ),

			sprintf( 'columns="%d"', $columns ),

			sprintf( 'limit="%d"', $limit ),

		);



		$map = array(

			'grid_gap'           => 'gap',

			'grid_padding_x'     => 'padding_x',

			'grid_padding_y'     => 'padding_y',

			'grid_margin_top'    => 'margin_top',

			'grid_margin_bottom' => 'margin_bottom',

			'grid_card_padding'  => 'card_padding',

		);



		foreach ( $map as $setting_key => $short_key ) {

			if ( ! empty( $settings[ $setting_key ]['size'] ) ) {

				$parts[] = sprintf( '%s="%d"', $short_key, absint( $settings[ $setting_key ]['size'] ) );

			}

		}



		echo '<div class="ulbm-root">';

		echo do_shortcode( '[ulbm_listing_grid ' . implode( ' ', $parts ) . ']' );

		echo '</div>';

	}

}

