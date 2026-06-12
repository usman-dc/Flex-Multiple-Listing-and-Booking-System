<?php
/**
 * Listing grid filter bar — markup + scoped critical CSS (theme/Elementor safe).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Renders grid search filters and queues scoped CSS for wp_footer (avoids kses stripping).
 */
final class GridFilterUi {

	/**
	 * Accumulated per-grid critical CSS for footer output.
	 *
	 * @var string
	 */
	private static $footer_css = '';

	/**
	 * Allowed HTML for block output (preserves filter inputs + inline styles).
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function kses_allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );
		foreach ( array( 'input', 'select', 'option', 'div', 'span', 'label', 'button', 'i' ) as $tag ) {
			if ( ! isset( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = array();
			}
			$allowed[ $tag ]['style']     = true;
			$allowed[ $tag ]['class']     = true;
			$allowed[ $tag ]['id']        = true;
			$allowed[ $tag ]['type']      = true;
			$allowed[ $tag ]['name']      = true;
			$allowed[ $tag ]['value']     = true;
			$allowed[ $tag ]['for']       = true;
			$allowed[ $tag ]['placeholder'] = true;
			$allowed[ $tag ]['autocomplete'] = true;
			$allowed[ $tag ]['inputmode'] = true;
			$allowed[ $tag ]['aria-hidden'] = true;
			$allowed[ $tag ]['role']      = true;
		}
		$allowed['input']['type'] = true;
		$allowed['option']['selected'] = true;
		return $allowed;
	}

	/**
	 * Sanitize grid shortcode HTML for blocks/front output.
	 *
	 * @param string $html Raw HTML.
	 * @return string
	 */
	public static function kses_grid_html( $html ) {
		return wp_kses( (string) $html, self::kses_allowed_html() );
	}

	/**
	 * Queue critical CSS for this grid (printed once in wp_footer).
	 *
	 * @param string $grid_id Grid root element id.
	 * @return void
	 */
	public static function enqueue_critical_styles( $grid_id ) {
		FrontController::register_public_assets();

		$scope = '#' . sanitize_html_class( (string) $grid_id );
		$css    = self::critical_css( $scope );
		self::$footer_css .= $css;

		wp_enqueue_style( 'ulbm-public' );
		wp_add_inline_style( 'ulbm-public', $css );

		if ( ! has_action( 'wp_footer', array( __CLASS__, 'print_footer_styles' ) ) ) {
			add_action( 'wp_footer', array( __CLASS__, 'print_footer_styles' ), 99 );
		}
	}

	/**
	 * Output queued CSS in footer (not stripped by content sanitizers).
	 *
	 * @return void
	 */
	public static function print_footer_styles() {
		if ( '' === self::$footer_css ) {
			return;
		}
		echo '<style id="ulbm-grid-filter-critical-css">' . self::$footer_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS only.
		self::$footer_css = '';
	}

	/**
	 * Critical CSS string for a grid scope selector.
	 *
	 * @param string $scope CSS selector prefix (e.g. #ulbm-grid-abc).
	 * @return string
	 */
	public static function critical_css( $scope ) {
		return $scope . '{position:relative;z-index:10;pointer-events:auto}'
			. $scope . ' .ulbm-filter-panel{position:relative;z-index:100;pointer-events:auto;isolation:isolate}'
			. $scope . ' .ulbm-filter-grid{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end}'
			. $scope . ' .ulbm-filter-item{flex:1 1 140px;min-width:0;position:relative;z-index:1}'
			. $scope . ' .ulbm-filter-item--keyword{flex:2 1 220px;min-width:min(100%,220px)}'
			. $scope . ' .ulbm-filter-item--submit{flex:1 1 150px;min-width:150px}'
			. $scope . ' .ulbm-filter-label{display:block;margin:0 0 .35rem;font-size:.8125rem;font-weight:600;color:#212529;pointer-events:none}'
			. $scope . ' .ulbm-fctl{display:block!important;width:100%!important;min-height:42px!important;height:42px!important;padding:8px 12px!important;margin:0!important;border:1px solid #e5e7eb!important;border-radius:6px!important;background:#fff!important;color:#212529!important;font-size:16px!important;line-height:1.5!important;box-sizing:border-box!important;position:relative!important;z-index:2!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;touch-action:manipulation;box-shadow:none!important;transform:none!important;clip:auto!important;left:auto!important;top:auto!important}'
			. $scope . ' .ulbm-fctl:focus{outline:2px solid #0d6efd;outline-offset:0}'
			. $scope . ' .ulbm-fctl--search{padding-left:40px!important}'
			. $scope . ' .ulbm-filter-box{display:block!important;width:100%!important;min-height:42px!important;height:42px!important;border:1px solid #e5e7eb!important;border-radius:6px!important;background:#fff!important;box-sizing:border-box!important;overflow:hidden!important;position:relative!important}'
			. $scope . ' .ulbm-filter-box--search .ulbm-filter-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b7280;pointer-events:none;z-index:3;line-height:1}'
			. $scope . ' .ulbm-filter-box .ulbm-fctl{display:block!important;width:100%!important;height:42px!important;min-height:42px!important;padding:8px 12px!important;margin:0!important;border:0!important;background:transparent!important;color:#212529!important;font-size:16px!important;box-sizing:border-box!important;pointer-events:auto!important;opacity:1!important;visibility:visible!important}'
			. $scope . ' .ulbm-filter-box--search .ulbm-fctl{padding-left:40px!important}'
			. $scope . ' .ulbm-filter-submit{width:100%!important;min-height:42px!important;pointer-events:auto!important;cursor:pointer!important;position:relative!important;z-index:2!important}'
			. $scope . ' .ulbm-grid-toolbar{position:relative;z-index:5;pointer-events:auto!important}'
			. $scope . ' .ulbm-sort-toggle .ulbm-sort-btn{pointer-events:auto!important;cursor:pointer!important}'
			. $scope . ' select.ulbm-fctl{appearance:menulist!important;-webkit-appearance:menulist!important;cursor:pointer!important}';
	}

	/**
	 * Filter panel markup.
	 *
	 * @param string               $grid_id Grid root id.
	 * @param array<string, mixed> $args    type slug, all_types list.
	 * @return void
	 */
	public static function render_panel( $grid_id, array $args = array() ) {
		$grid_id   = sanitize_html_class( (string) $grid_id );
		$type      = isset( $args['type'] ) ? sanitize_key( (string) $args['type'] ) : '';
		$all_types = isset( $args['all_types'] ) && is_array( $args['all_types'] ) ? $args['all_types'] : array();
		$panel_id = $grid_id . '-filters';

		include ULBM_PLUGIN_DIR . 'templates/public/partials/grid-filters.php';
	}
}
