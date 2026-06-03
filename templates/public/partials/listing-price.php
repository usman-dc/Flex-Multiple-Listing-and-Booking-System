<?php
/**
 * Listing price display partial.
 *
 * @package FlexBookingSystem
 *
 * @var string $fbs_price_base  Base price.
 * @var string $fbs_price_sale  Sale price (optional).
 * @var string $fbs_price_suffix Price suffix (optional).
 * @var string $fbs_price_wrap  Wrapper tag: span|strong (default span).
 */

use FlexBooking\Front\PriceFormatter;

defined( 'ABSPATH' ) || exit;

$fbs_price_base   = isset( $fbs_price_base ) ? (string) $fbs_price_base : '';
$fbs_price_sale   = isset( $fbs_price_sale ) ? (string) $fbs_price_sale : '';
$fbs_price_suffix = isset( $fbs_price_suffix ) ? (string) $fbs_price_suffix : '';
$fbs_price_wrap   = isset( $fbs_price_wrap ) ? (string) $fbs_price_wrap : 'span';

if ( '' === $fbs_price_base && '' === $fbs_price_sale ) {
	return;
}

$tag = in_array( $fbs_price_wrap, array( 'span', 'strong' ), true ) ? $fbs_price_wrap : 'span';
?>
<<?php echo tag_escape( $tag ); ?> class="fbs-price">
	<?php PriceFormatter::echo_price( $fbs_price_base, $fbs_price_sale, $fbs_price_suffix ); ?>
</<?php echo tag_escape( $tag ); ?>>
