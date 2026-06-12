<?php
/**
 * Sidebar booking widget for single listing pages.
 *
 * @package FlexBookingSystem
 *
 * @var int $ulbm_booking_type_id Booking type ID.
 * @var int $ulbm_post_id         Listing post ID.
 */

use FlexBooking\Booking\BookingTypeRepository;
use FlexBooking\Forms\PublicBookingFields;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;

defined( 'ABSPATH' ) || exit;

$ulbm_booking_type_id = isset( $ulbm_booking_type_id ) ? (int) $ulbm_booking_type_id : 0;
$ulbm_post_id         = isset( $ulbm_post_id ) ? (int) $ulbm_post_id : get_the_ID();

$ulbm_widget_title = __( 'Book Now', 'flex-multiple-listing-and-booking-system' );
if ( $ulbm_booking_type_id > 0 ) {
	$ulbm_type_repo = new BookingTypeRepository();
	$ulbm_type_row  = $ulbm_type_repo->get_by_id( $ulbm_booking_type_id );
	$ulbm_pt        = $ulbm_post_id > 0 ? (string) get_post_type( $ulbm_post_id ) : '';
	$ulbm_groups    = PublicBookingFields::groups_for_type( $ulbm_type_row, $ulbm_pt );
	$ulbm_widget_title = (string) ( $ulbm_groups['widget_title'] ?? $ulbm_widget_title );
}
?>
<div class="ulbm-booking-widget">
	<h2 class="ulbm-booking-widget-title"><?php echo esc_html( $ulbm_widget_title ); ?></h2>
	<div class="ulbm-booking-widget-form">
		<?php if ( $ulbm_booking_type_id > 0 ) : ?>
			<?php
			echo do_shortcode(
				'[ulbm_booking_form id="' . $ulbm_booking_type_id . '" listing_id="' . $ulbm_post_id . '"]'
			);
			?>
		<?php else : ?>
			<p class="text-muted small text-center py-3"><?php esc_html_e( 'Booking form not available.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		<?php endif; ?>
	</div>
	<p class="ulbm-trust-note"><i class="bi bi-shield-check" aria-hidden="true"></i><?php esc_html_e( "You won't be charged yet.", 'flex-multiple-listing-and-booking-system' ); ?></p>
</div>
