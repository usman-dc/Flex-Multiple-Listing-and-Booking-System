<?php
/**
 * Sidebar booking widget for single listing pages.
 *
 * @package FlexBookingSystem
 *
 * @var int $ulbm_booking_type_id Booking type ID.
 */

defined( 'ABSPATH' ) || exit;

$booking_type_id = isset( $ulbm_booking_type_id ) ? (int) $ulbm_booking_type_id : 0;
?>
<div class="ulbm-booking-widget">
	<h2 class="ulbm-booking-widget-title"><?php esc_html_e( 'Book Your Stay', 'flex-booking-system' ); ?></h2>
	<div class="ulbm-booking-widget-form">
		<?php if ( $booking_type_id > 0 ) : ?>
			<?php echo do_shortcode( '[ulbm_booking_form id="' . $booking_type_id . '"]' ); ?>
		<?php else : ?>
			<p class="text-muted small text-center py-3"><?php esc_html_e( 'Booking form not available.', 'flex-booking-system' ); ?></p>
		<?php endif; ?>
	</div>
	<p class="ulbm-trust-note"><i class="bi bi-shield-check" aria-hidden="true"></i><?php esc_html_e( "You won't be charged yet.", 'flex-booking-system' ); ?></p>
</div>
