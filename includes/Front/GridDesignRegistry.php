<?php
/**
 * Listing grid card design presets (admin picker + frontend CSS class).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of grid card layout designs.
 */
final class GridDesignRegistry {

	public const DEFAULT = 'marketplace';

	/**
	 * All available designs.
	 *
	 * @return array<string, array{id: string, label: string, description: string}>
	 */
	public static function all() {
		$designs = array(
			'marketplace' => array(
				'id'          => 'marketplace',
				'label'       => __( 'Classic Marketplace', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Image on top, details below — balanced cards with hover lift.', 'flex-multiple-listing-and-booking-system' ),
			),
			'minimal'     => array(
				'id'          => 'minimal',
				'label'       => __( 'Minimal Flat', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Clean flat cards with thin borders and no shadow.', 'flex-multiple-listing-and-booking-system' ),
			),
			'luxury'      => array(
				'id'          => 'luxury',
				'label'       => __( 'Luxury Elevated', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Large rounded corners, deep shadow, premium spacing.', 'flex-multiple-listing-and-booking-system' ),
			),
			'commerce'    => array(
				'id'          => 'commerce',
				'label'       => __( 'Commerce Shop', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Category tab, price badge, tags, and a full-width book button.', 'flex-multiple-listing-and-booking-system' ),
			),
			'pill'        => array(
				'id'          => 'pill',
				'label'       => __( 'Pill CTA', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Centered title and excerpt with a rounded learn-more button.', 'flex-multiple-listing-and-booking-system' ),
			),
			'editorial'   => array(
				'id'          => 'editorial',
				'label'       => __( 'Editorial Card', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Category badge, headline, excerpt, and view-more link with arrow.', 'flex-multiple-listing-and-booking-system' ),
			),
			'stats'       => array(
				'id'          => 'stats',
				'label'       => __( 'Stats Footer', 'flex-multiple-listing-and-booking-system' ),
				'description' => __( 'Image, time stamp, excerpt, and a colored stats bar.', 'flex-multiple-listing-and-booking-system' ),
			),
		);

		return apply_filters( 'ulbm_grid_designs', $designs );
	}

	/**
	 * Single design or null.
	 *
	 * @param string $id Design slug.
	 * @return array{id: string, label: string, description: string}|null
	 */
	public static function get( $id ) {
		$id      = sanitize_key( (string) $id );
		$designs = self::all();

		return $designs[ $id ] ?? null;
	}

	/**
	 * Options for select controls (Elementor, Gutenberg, shortcode docs).
	 *
	 * @param bool $include_global Include empty value for site-wide default.
	 * @return array<string, string>
	 */
	public static function select_options( $include_global = true ) {
		$options = array();
		if ( $include_global ) {
			$options[''] = __( 'Use global setting', 'flex-multiple-listing-and-booking-system' );
		}
		foreach ( self::all() as $design ) {
			$options[ $design['id'] ] = $design['label'];
		}

		return $options;
	}

	/**
	 * Validated design slug from settings.
	 *
	 * @return string
	 */
	public static function active_id() {
		$settings = LayoutSettings::get();
		return self::sanitize_id( $settings['grid_design'] ?? self::DEFAULT );
	}

	/**
	 * CSS class for the active design on grid root.
	 *
	 * @param string|null $id Optional override.
	 * @return string
	 */
	public static function css_class( $id = null ) {
		$id = null === $id ? self::active_id() : self::sanitize_id( $id );

		return 'ulbm-grid-design-' . $id;
	}

	/**
	 * Sanitize design id.
	 *
	 * @param string $id Raw id.
	 * @return string
	 */
	public static function sanitize_id( $id ) {
		$id = sanitize_key( (string) $id );

		return self::get( $id ) ? $id : self::DEFAULT;
	}

	/**
	 * Preview image URL for admin picker.
	 *
	 * @param string $id Design slug.
	 * @return string
	 */
	public static function preview_url( $id ) {
		$id   = self::sanitize_id( $id );
		$path = ULBM_PLUGIN_DIR . 'assets/grid-designs/preview-' . $id . '.png';
		$url  = ULBM_PLUGIN_URL . 'assets/grid-designs/preview-' . $id . '.png';

		if ( ! is_readable( $path ) ) {
			return '';
		}

		return $url . '?ver=' . rawurlencode( ULBM_VERSION );
	}
}
