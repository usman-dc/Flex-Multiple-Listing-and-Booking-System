<?php
/**
 * Color scheme helpers for settings save and frontend CSS variables.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and sanitizes color options from fbs_general_settings.
 */
final class ColorSettings {

	/**
	 * All configurable colors: key => field definition.
	 *
	 * @return array<string, array{label: string, default: string, group: string, hint?: string}>
	 */
	public static function fields() {
		return array(
			'color_primary'       => array(
				'label'   => __( 'Primary', 'flex-booking-system' ),
				'default' => '#0d6efd',
				'group'   => 'brand',
				'hint'    => __( 'Main buttons, icons, prices, active states', 'flex-booking-system' ),
			),
			'color_secondary'     => array(
				'label'   => __( 'Secondary', 'flex-booking-system' ),
				'default' => '#6c757d',
				'group'   => 'brand',
				'hint'    => __( 'Secondary buttons, subtle UI', 'flex-booking-system' ),
			),
			'color_success'       => array(
				'label'   => __( 'Success', 'flex-booking-system' ),
				'default' => '#198754',
				'group'   => 'brand',
				'hint'    => __( 'Featured badge, instant booking, success messages', 'flex-booking-system' ),
			),
			'color_accent'        => array(
				'label'   => __( 'Accent', 'flex-booking-system' ),
				'default' => '#ffc107',
				'group'   => 'brand',
				'hint'    => __( 'Star ratings, highlights', 'flex-booking-system' ),
			),
			'color_danger'        => array(
				'label'   => __( 'Danger / Sale', 'flex-booking-system' ),
				'default' => '#dc3545',
				'group'   => 'brand',
				'hint'    => __( 'Errors, sale prices, wishlist active', 'flex-booking-system' ),
			),
			'color_page_bg'       => array(
				'label'   => __( 'Page background', 'flex-booking-system' ),
				'default' => '#f5f6f8',
				'group'   => 'backgrounds',
				'hint'    => __( 'Outer page area behind listings', 'flex-booking-system' ),
			),
			'color_surface_bg'    => array(
				'label'   => __( 'Panel / filter background', 'flex-booking-system' ),
				'default' => '#ffffff',
				'group'   => 'backgrounds',
				'hint'    => __( 'Filter bar, content panels, forms', 'flex-booking-system' ),
			),
			'color_card_bg'       => array(
				'label'   => __( 'Card background', 'flex-booking-system' ),
				'default' => '#ffffff',
				'group'   => 'backgrounds',
				'hint'    => __( 'Listing grid cards', 'flex-booking-system' ),
			),
			'color_booking_bg'    => array(
				'label'   => __( 'Booking box background', 'flex-booking-system' ),
				'default' => '#ffffff',
				'group'   => 'backgrounds',
				'hint'    => __( 'Sidebar booking widget on single listing', 'flex-booking-system' ),
			),
			'color_toolbar_bg'    => array(
				'label'   => __( 'Toolbar background', 'flex-booking-system' ),
				'default' => '#ffffff',
				'group'   => 'backgrounds',
				'hint'    => __( 'Top account / navigation bar', 'flex-booking-system' ),
			),
			'color_gallery_bg'    => array(
				'label'   => __( 'Gallery placeholder', 'flex-booking-system' ),
				'default' => '#dee2e6',
				'group'   => 'backgrounds',
				'hint'    => __( 'Empty image areas before photos load', 'flex-booking-system' ),
			),
			'color_heading'       => array(
				'label'   => __( 'Headings', 'flex-booking-system' ),
				'default' => '#1a2b48',
				'group'   => 'text',
				'hint'    => __( 'Titles, listing names, prices in hero', 'flex-booking-system' ),
			),
			'color_text'          => array(
				'label'   => __( 'Body text', 'flex-booking-system' ),
				'default' => '#212529',
				'group'   => 'text',
				'hint'    => __( 'Main paragraph and card text', 'flex-booking-system' ),
			),
			'color_muted'         => array(
				'label'   => __( 'Muted text', 'flex-booking-system' ),
				'default' => '#6b7280',
				'group'   => 'text',
				'hint'    => __( 'Locations, meta, helper text', 'flex-booking-system' ),
			),
			'color_link'          => array(
				'label'   => __( 'Links', 'flex-booking-system' ),
				'default' => '#0d6efd',
				'group'   => 'text',
				'hint'    => __( 'Text links (view map, etc.)', 'flex-booking-system' ),
			),
			'color_border'        => array(
				'label'   => __( 'Borders', 'flex-booking-system' ),
				'default' => '#e5e7eb',
				'group'   => 'borders',
				'hint'    => __( 'Cards, dividers, input outlines', 'flex-booking-system' ),
			),
			'color_border_focus'  => array(
				'label'   => __( 'Focus border', 'flex-booking-system' ),
				'default' => '#0d6efd',
				'group'   => 'borders',
				'hint'    => __( 'Focused form fields', 'flex-booking-system' ),
			),
			'color_input_bg'      => array(
				'label'   => __( 'Input background', 'flex-booking-system' ),
				'default' => '#ffffff',
				'group'   => 'borders',
				'hint'    => __( 'Search fields, booking form inputs', 'flex-booking-system' ),
			),
			'color_btn_text'      => array(
				'label'   => __( 'Primary button text', 'flex-booking-system' ),
				'default' => '#ffffff',
				'group'   => 'buttons',
				'hint'    => __( 'Text on primary buttons', 'flex-booking-system' ),
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function groups() {
		return array(
			'brand'       => __( 'Brand colors', 'flex-booking-system' ),
			'backgrounds' => __( 'Backgrounds', 'flex-booking-system' ),
			'text'        => __( 'Text', 'flex-booking-system' ),
			'borders'     => __( 'Borders & form fields', 'flex-booking-system' ),
			'buttons'     => __( 'Buttons', 'flex-booking-system' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function defaults() {
		$out = array();
		foreach ( self::fields() as $key => $field ) {
			$out[ $key ] = $field['default'];
		}
		return $out;
	}

	/**
	 * POST field name for a settings key.
	 *
	 * @param string $settings_key Settings key.
	 * @return string
	 */
	public static function post_key( $settings_key ) {
		return 'fbs_' . $settings_key;
	}

	/**
	 * Sanitize a hex color; keep fallback when invalid.
	 *
	 * @param string $value    Raw input.
	 * @param string $fallback Fallback hex.
	 * @return string
	 */
	public static function sanitize_hex( $value, $fallback ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return self::sanitize_hex( $fallback, '#000000' );
		}

		if ( '#' !== $value[0] && preg_match( '/^[a-fA-F0-9]{3,6}$/', $value ) ) {
			$value = '#' . $value;
		}

		if ( preg_match( '/^#([a-fA-F0-9]{3})$/', $value, $m ) ) {
			$value = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
		}

		$clean = sanitize_hex_color( $value );

		return $clean ? $clean : self::sanitize_hex( $fallback, '#000000' );
	}

	/**
	 * Merged color settings from the database.
	 *
	 * @return array<string, string>
	 */
	public static function get() {
		$raw = LayoutSettings::get();
		$out = self::defaults();

		foreach ( $out as $key => $default ) {
			if ( array_key_exists( $key, $raw ) && '' !== (string) $raw[ $key ] ) {
				$out[ $key ] = self::sanitize_hex( (string) $raw[ $key ], $default );
			}
		}

		return $out;
	}

	/**
	 * Merge color values from POST (preserve when field absent).
	 *
	 * @param array<string, mixed> $prev Previous settings.
	 * @param array<string, mixed> $post POST data.
	 * @return array<string, string>
	 */
	public static function merge_from_post( array $prev, array $post ) {
		$defaults = self::defaults();
		$out      = array();
		$json_in  = array();

		if ( ! empty( $post['fbs_colors_json'] ) ) {
			$decoded = json_decode( (string) $post['fbs_colors_json'], true );
			if ( is_array( $decoded ) ) {
				$json_in = $decoded;
			}
		}

		foreach ( self::fields() as $key => $field ) {
			$post_key = self::post_key( $key );
			$default  = $defaults[ $key ];

			if ( array_key_exists( $key, $json_in ) ) {
				$out[ $key ] = self::sanitize_hex( (string) $json_in[ $key ], $default );
			} elseif ( array_key_exists( $post_key, $post ) ) {
				$out[ $key ] = self::sanitize_hex( (string) $post[ $post_key ], $default );
			} elseif ( isset( $prev[ $key ] ) && '' !== (string) $prev[ $key ] ) {
				$out[ $key ] = self::sanitize_hex( (string) $prev[ $key ], $default );
			} else {
				$out[ $key ] = $default;
			}
		}

		return $out;
	}

	/**
	 * CSS custom properties for plugin UI (also maps Bootstrap vars).
	 *
	 * @return array<string, string>
	 */
	public static function css_var_map() {
		$c = self::get();

		return array(
			'--fbs-primary'        => $c['color_primary'],
			'--fbs-secondary'      => $c['color_secondary'],
			'--fbs-success'        => $c['color_success'],
			'--fbs-accent'         => $c['color_accent'],
			'--fbs-danger'         => $c['color_danger'],
			'--fbs-page-bg'        => $c['color_page_bg'],
			'--fbs-surface-bg'     => $c['color_surface_bg'],
			'--fbs-card-bg'        => $c['color_card_bg'],
			'--fbs-booking-bg'     => $c['color_booking_bg'],
			'--fbs-toolbar-bg'     => $c['color_toolbar_bg'],
			'--fbs-gallery-bg'     => $c['color_gallery_bg'],
			'--fbs-heading'        => $c['color_heading'],
			'--fbs-text'           => $c['color_text'],
			'--fbs-muted'          => $c['color_muted'],
			'--fbs-link'           => $c['color_link'],
			'--fbs-border'         => $c['color_border'],
			'--fbs-border-focus'   => $c['color_border_focus'],
			'--fbs-input-bg'       => $c['color_input_bg'],
			'--fbs-btn-text'       => $c['color_btn_text'],
			'--fbs-navy'           => $c['color_heading'],
			'--bs-primary'         => $c['color_primary'],
			'--bs-secondary'       => $c['color_secondary'],
			'--bs-success'         => $c['color_success'],
			'--bs-warning'         => $c['color_accent'],
			'--bs-danger'          => $c['color_danger'],
		);
	}

	/**
	 * CSS selectors that receive theme variables.
	 *
	 * @return string
	 */
	public static function css_scope_selector() {
		$selectors = array(
			'.fbs-root',
			'.fbs-single-listing-wrap',
			'.fbs-booking-form',
			'.fbs-listing-grid',
			'.fbs-vendor-dashboard',
			'.fbs-vendor-auth',
			'.fbs-search-root',
			'.fbs-become-partner',
		);

		return implode( ',', $selectors );
	}

	/**
	 * Map settings keys to preview CSS var names for admin JS.
	 *
	 * @return array<string, string>
	 */
	public static function admin_preview_var_map() {
		$map = array();
		foreach ( self::fields() as $key => $field ) {
			$map[ $key ] = '--fbs-preview-' . str_replace( 'color_', '', $key );
		}
		return $map;
	}

	/**
	 * Inline style attribute for admin color preview container.
	 *
	 * @return string
	 */
	public static function admin_preview_inline_style() {
		$c     = self::get();
		$parts = array();
		foreach ( self::admin_preview_var_map() as $key => $var ) {
			if ( isset( $c[ $key ] ) ) {
				$parts[] = $var . ':' . $c[ $key ];
			}
		}
		return implode( ';', $parts );
	}

	/**
	 * Inline CSS block for wp_add_inline_style (CSS variables only — scoped to plugin UI).
	 *
	 * @return string
	 */
	public static function inline_css() {
		$parts = array();
		foreach ( self::css_var_map() as $var => $value ) {
			$parts[] = $var . ':' . $value;
		}

		$layout = LayoutSettings::css_vars();
		foreach ( $layout as $var => $value ) {
			$parts[] = $var . ':' . $value;
		}

		$settings = LayoutSettings::get();
		if ( ! empty( $settings['card_border_radius'] ) ) {
			$parts[] = '--fbs-radius:' . absint( $settings['card_border_radius'] ) . 'px';
		}
		if ( ! empty( $settings['slider_height'] ) ) {
			$parts[] = '--fbs-slider-h:' . absint( $settings['slider_height'] ) . 'px';
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return self::css_scope_selector() . '{' . implode( ';', $parts ) . '}';
	}
}
