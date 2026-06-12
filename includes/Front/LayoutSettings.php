<?php
/**
 * Layout settings helpers — container width, CSS variables.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Reads layout options from general settings.
 */
final class LayoutSettings {

	public const DEFAULT_CONTAINER_WIDTH = 1400;

	public const MIN_CONTAINER_WIDTH = 768;

	public const MAX_CONTAINER_WIDTH = 2400;

	public const DEFAULT_GRID_GAP = 24;

	public const DEFAULT_GRID_PADDING = 0;

	public const DEFAULT_GRID_MARGIN = 0;

	public const DEFAULT_GRID_CARD_PADDING = 16;

	public const DEFAULT_GRID_COLUMNS = 3;

	public const MIN_GRID_COLUMNS = 2;

	public const MAX_GRID_COLUMNS = 4;

	public const DEFAULT_GRID_PER_PAGE = 12;

	/**
	 * @var array<string,mixed>|null
	 */
	private static $cached = null;

	/**
	 * @return array<string,mixed>
	 */
	public static function get() {
		if ( null !== self::$cached ) {
			return self::$cached;
		}

		$settings_json = get_option( 'ulbm_general_settings', false );
		if ( false === $settings_json ) {
			$settings_json = get_option( 'fbs_general_settings', '{}' );
		}
		$raw = json_decode( (string) $settings_json, true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		self::$cached = array_merge(
			array(
				'container_width'    => self::DEFAULT_CONTAINER_WIDTH,
				'grid_gap'           => self::DEFAULT_GRID_GAP,
				'grid_padding_x'     => self::DEFAULT_GRID_PADDING,
				'grid_padding_y'     => self::DEFAULT_GRID_PADDING,
				'grid_margin_top'    => self::DEFAULT_GRID_MARGIN,
				'grid_margin_bottom' => self::DEFAULT_GRID_MARGIN,
				'grid_card_padding'  => self::DEFAULT_GRID_CARD_PADDING,
				'grid_columns'       => self::DEFAULT_GRID_COLUMNS,
				'grid_per_page'      => self::DEFAULT_GRID_PER_PAGE,
			),
			$raw
		);

		return self::$cached;
	}

	/**
	 * Sanitized grid column count from settings (2–4).
	 *
	 * @param int|string|null $override Optional explicit columns (widget/shortcode).
	 * @return int
	 */
	public static function grid_columns( $override = null ) {
		if ( null !== $override && '' !== (string) $override ) {
			return max( 1, min( 6, (int) $override ) );
		}

		$settings = self::get();

		return max(
			self::MIN_GRID_COLUMNS,
			min( self::MAX_GRID_COLUMNS, (int) ( $settings['grid_columns'] ?? self::DEFAULT_GRID_COLUMNS ) )
		);
	}

	/**
	 * Posts per page for grids from settings.
	 *
	 * @param int|string|null $override Optional explicit limit.
	 * @return int
	 */
	public static function grid_per_page( $override = null ) {
		if ( null !== $override && '' !== (string) $override ) {
			return max( 1, min( 100, (int) $override ) );
		}

		$settings = self::get();

		return max( 1, min( 100, (int) ( $settings['grid_per_page'] ?? self::DEFAULT_GRID_PER_PAGE ) ) );
	}

	/**
	 * Clear cached settings after save.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		self::$cached = null;
	}

	/**
	 * Sanitized container max-width in pixels.
	 *
	 * @return int
	 */
	public static function container_width_px() {
		$settings = self::get();
		$width    = isset( $settings['container_width'] ) ? absint( $settings['container_width'] ) : self::DEFAULT_CONTAINER_WIDTH;
		if ( $width < 1 ) {
			$width = self::DEFAULT_CONTAINER_WIDTH;
		}

		return max( self::MIN_CONTAINER_WIDTH, min( self::MAX_CONTAINER_WIDTH, $width ) );
	}

	/**
	 * Clamp spacing value in pixels (0–120).
	 *
	 * @param mixed $value Raw value.
	 * @param int   $fallback Fallback.
	 * @return int
	 */
	public static function spacing_px( $value, $fallback = 0 ) {
		$px = absint( $value );
		if ( $px < 1 && (string) $value !== '0' && 0 !== $value ) {
			$px = absint( $fallback );
		}

		return max( 0, min( 120, $px ) );
	}

	/**
	 * Grid spacing CSS variables merged with optional overrides.
	 *
	 * @param array<string, mixed> $overrides Shortcode/block overrides.
	 * @return array<string, string>
	 */
	public static function grid_css_vars( array $overrides = array() ) {
		$settings = self::get();

		$gap = array_key_exists( 'gap', $overrides )
			? self::spacing_px( $overrides['gap'], self::DEFAULT_GRID_GAP )
			: self::spacing_px( $settings['grid_gap'] ?? self::DEFAULT_GRID_GAP, self::DEFAULT_GRID_GAP );

		$pad_x = array_key_exists( 'padding_x', $overrides )
			? self::spacing_px( $overrides['padding_x'], self::DEFAULT_GRID_PADDING )
			: self::spacing_px( $settings['grid_padding_x'] ?? self::DEFAULT_GRID_PADDING, self::DEFAULT_GRID_PADDING );

		$pad_y = array_key_exists( 'padding_y', $overrides )
			? self::spacing_px( $overrides['padding_y'], self::DEFAULT_GRID_PADDING )
			: self::spacing_px( $settings['grid_padding_y'] ?? self::DEFAULT_GRID_PADDING, self::DEFAULT_GRID_PADDING );

		$margin_top = array_key_exists( 'margin_top', $overrides )
			? self::spacing_px( $overrides['margin_top'], self::DEFAULT_GRID_MARGIN )
			: self::spacing_px( $settings['grid_margin_top'] ?? self::DEFAULT_GRID_MARGIN, self::DEFAULT_GRID_MARGIN );

		$margin_bottom = array_key_exists( 'margin_bottom', $overrides )
			? self::spacing_px( $overrides['margin_bottom'], self::DEFAULT_GRID_MARGIN )
			: self::spacing_px( $settings['grid_margin_bottom'] ?? self::DEFAULT_GRID_MARGIN, self::DEFAULT_GRID_MARGIN );

		$card_padding = array_key_exists( 'card_padding', $overrides )
			? self::spacing_px( $overrides['card_padding'], self::DEFAULT_GRID_CARD_PADDING )
			: self::spacing_px( $settings['grid_card_padding'] ?? self::DEFAULT_GRID_CARD_PADDING, self::DEFAULT_GRID_CARD_PADDING );

		return array(
			'--ulbm-grid-gap'           => $gap . 'px',
			'--ulbm-grid-padding-x'     => $pad_x . 'px',
			'--ulbm-grid-padding-y'     => $pad_y . 'px',
			'--ulbm-grid-margin-top'    => $margin_top . 'px',
			'--ulbm-grid-margin-bottom' => $margin_bottom . 'px',
			'--ulbm-grid-card-padding'  => $card_padding . 'px',
		);
	}

	/**
	 * Inline style attribute for a listing grid root element.
	 *
	 * @param array<string, mixed> $overrides Overrides.
	 * @return string
	 */
	public static function grid_inline_style( array $overrides = array() ) {
		$vars = self::grid_css_vars( $overrides );
		$parts = array();
		foreach ( $vars as $key => $val ) {
			$parts[] = $key . ':' . $val;
		}

		return implode( ';', $parts );
	}

	/**
	 * Inline style for listing grid root (spacing vars + column count).
	 *
	 * @param int                  $columns   Resolved column count (2–4).
	 * @param array<string, mixed> $overrides Spacing overrides.
	 * @return string
	 */
	public static function grid_root_style( $columns, array $overrides = array() ) {
		$columns = max( self::MIN_GRID_COLUMNS, min( self::MAX_GRID_COLUMNS, (int) $columns ) );
		$style   = self::grid_inline_style( $overrides );

		return $style ? $style . ';--ulbm-grid-columns:' . $columns : '--ulbm-grid-columns:' . $columns;
	}

	/**
	 * CSS custom properties for inline injection.
	 *
	 * @return array<string,string>
	 */
	public static function css_vars() {
		return array_merge(
			array(
				'--ulbm-container-width'       => self::container_width_px() . 'px',
				'--ulbm-container-padding-x'   => '20px',
				'--ulbm-container-padding-y'   => '20px',
			),
			self::grid_css_vars()
		);
	}

	/**
	 * Wrap frontend markup so container width applies to widgets/shortcodes.
	 *
	 * @param string $html  Markup.
	 * @param string $class Optional extra class.
	 * @return string
	 */
	public static function wrap( $html, $class = '' ) {
		$classes = trim( 'ulbm-root ' . $class );

		return '<div class="' . esc_attr( $classes ) . '">' . $html . '</div>';
	}
}
