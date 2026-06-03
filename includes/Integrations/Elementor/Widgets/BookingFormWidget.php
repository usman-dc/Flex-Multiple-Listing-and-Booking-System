<?php
/**
 * Elementor widget: embeds [fbs_booking_form] with full controls.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Booking form widget for Elementor.
 */
final class BookingFormWidget extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'fbs_booking_form';
	}

	/**
	 * Title in Elementor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Flex MLS Booking Form', 'flex-booking-system' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
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
		return array( 'booking', 'form', 'reservation', 'appointment' );
	}

	/**
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'fbs-bootstrap', 'fbs-bootstrap-icons', 'fbs-public' );
	}

	/**
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'fbs-bootstrap', 'fbs-public' );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$type_repo = new \FlexBooking\Booking\BookingTypeRepository();
		$all_types = $type_repo->get_all();
		$id_opts   = array( '0' => __( '— Select Booking Type —', 'flex-booking-system' ) );
		foreach ( $all_types as $t ) {
			$id_opts[ (string) (int) $t['id'] ] = (string) $t['name'] . ' (#' . (int) $t['id'] . ')';
		}

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Booking Form', 'flex-booking-system' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'booking_type_id',
			array(
				'label'   => __( 'Booking Type', 'flex-booking-system' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '0',
				'options' => $id_opts,
			)
		);

		$this->add_control(
			'booking_type_slug',
			array(
				'label'       => __( 'Or type slug (advanced)', 'flex-booking-system' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Override with a custom slug. Leave blank to use the dropdown above.', 'flex-booking-system' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Style', 'flex-booking-system' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'form_border_radius',
			array(
				'label'      => __( 'Border radius (px)', 'flex-booking-system' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .fbs-booking-form' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_color',
			array(
				'label'       => __( 'Override button color (optional)', 'flex-booking-system' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'Leave empty to use Flex MLS & Booking → Settings → Colors.', 'flex-booking-system' ),
				'selectors'   => array(
					'{{WRAPPER}} .fbs-root' => '--fbs-primary: {{VALUE}}; --bs-primary: {{VALUE}};',
					'{{WRAPPER}} .btn-primary' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
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
		$slug     = isset( $settings['booking_type_slug'] ) ? sanitize_title( $settings['booking_type_slug'] ) : '';
		$id       = isset( $settings['booking_type_id'] ) ? absint( $settings['booking_type_id'] ) : 0;

		$shortcode = sprintf( '[fbs_booking_form type="%s" id="%d"]', esc_attr( $slug ), $id );
		echo '<div class="fbs-root">';
		echo do_shortcode( $shortcode );
		echo '</div>';
	}
}
