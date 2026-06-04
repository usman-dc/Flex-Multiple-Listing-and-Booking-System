<?php
/**
 * Listing price display partial.
 *
 * @package FlexBookingSystem
 *
 * @var string $ulbm_price_base  Base price.
 * @var string $ulbm_price_sale  Sale price (optional).
 * @var string $ulbm_price_suffix Price suffix (optional).
 * @var string $ulbm_price_wrap  Wrapper tag: span|strong (default span).
 */

use FlexBooking\Front\PriceFormatter;

defined( 'ABSPATH' ) || exit;

$ulbm_price_base   = isset( $ulbm_price_base ) ? (string) $ulbm_price_base : '';
$ulbm_price_sale   = isset( $ulbm_price_sale ) ? (string) $ulbm_price_sale : '';
$ulbm_price_suffix = isset( $ulbm_price_suffix ) ? (string) $ulbm_price_suffix : '';
$ulbm_price_wrap   = isset( $ulbm_price_wrap ) ? (string) $ulbm_price_wrap : 'span';

if ( '' === $ulbm_price_base && '' === $ulbm_price_sale ) {
	return;
}

$tag = in_array( $ulbm_price_wrap, array( 'span', 'strong' ), true ) ? $ulbm_price_wrap : 'span';
?>
<<?php echo tag_escape( $tag ); ?> class="ulbm-price">
	<?php PriceFormatter::echo_price( $ulbm_price_base, $ulbm_price_sale, $ulbm_price_suffix ); ?>
</<?php echo tag_escape( $tag ); ?>>
