<?php
/**
 * Formats listing prices using plugin currency settings.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Central price formatting for grids, singles, and AJAX cards.
 */
final class PriceFormatter {

	/**
	 * @return array<string,mixed>
	 */
	public static function settings() {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}

		$raw = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
		$cached = is_array( $raw ) ? $raw : array();

		return $cached;
	}

	/**
	 * @return string
	 */
	public static function currency_code() {
		$settings = self::settings();
		$code     = isset( $settings['currency'] ) ? (string) $settings['currency'] : 'USD';

		return strtoupper( sanitize_text_field( $code ) );
	}

	/**
	 * Format amount with currency position from settings.
	 *
	 * @param string|int|float $amount Raw amount.
	 * @return string
	 */
	public static function format_plain( $amount ) {
		$amount = trim( (string) $amount );
		if ( '' === $amount ) {
			return '';
		}

		$code     = self::currency_code();
		$settings = self::settings();
		$position = isset( $settings['currency_position'] ) ? (string) $settings['currency_position'] : 'left';

		switch ( $position ) {
			case 'right':
				return $amount . $code;
			case 'left_space':
				return $code . ' ' . $amount;
			case 'right_space':
				return $amount . ' ' . $code;
			default:
				return $code . ' ' . $amount;
		}
	}

	/**
	 * Normalize suffix text (e.g. "/night", "per booking").
	 *
	 * @param string $suffix Price suffix meta.
	 * @return string
	 */
	public static function normalize_suffix( $suffix ) {
		$suffix = trim( (string) $suffix );
		if ( '' === $suffix ) {
			return '';
		}

		if ( '/' === $suffix[0] ) {
			return $suffix;
		}

		if ( 0 === stripos( $suffix, 'per ' ) ) {
			return $suffix;
		}

		return $suffix;
	}

	/**
	 * HTML price with optional sale and suffix.
	 *
	 * @param string $base_price Base price.
	 * @param string $sale_price Sale price.
	 * @param string $suffix     Price suffix.
	 * @return string
	 */
	public static function render_html( $base_price, $sale_price = '', $suffix = '' ) {
		$base_price = trim( (string) $base_price );
		$sale_price = trim( (string) $sale_price );
		$suffix     = self::normalize_suffix( $suffix );

		if ( '' === $base_price && '' === $sale_price ) {
			return '';
		}

		$html = '';

		if ( '' !== $sale_price && '' !== $base_price ) {
			$html .= '<del class="ulbm-price-was text-muted me-1">' . esc_html( self::format_plain( $base_price ) ) . '</del> ';
			$html .= '<span class="ulbm-price-current">' . esc_html( self::format_plain( $sale_price ) ) . '</span>';
		} elseif ( '' !== $sale_price ) {
			$html .= '<span class="ulbm-price-current">' . esc_html( self::format_plain( $sale_price ) ) . '</span>';
		} else {
			$html .= '<span class="ulbm-price-current">' . esc_html( self::format_plain( $base_price ) ) . '</span>';
		}

		if ( '' !== $suffix ) {
			$html .= '<small class="text-muted fw-normal ulbm-price-suffix">' . esc_html( $suffix ) . '</small>';
		}

		return $html;
	}

	/**
	 * Echo formatted price safely.
	 *
	 * @param string $base_price Base price.
	 * @param string $sale_price Sale price.
	 * @param string $suffix     Suffix.
	 * @return void
	 */
	public static function echo_price( $base_price, $sale_price = '', $suffix = '' ) {
		echo wp_kses( self::render_html( $base_price, $sale_price, $suffix ), array(
			'del'   => array( 'class' => true ),
			'span'  => array( 'class' => true ),
			'small' => array( 'class' => true ),
		) );
	}
}
