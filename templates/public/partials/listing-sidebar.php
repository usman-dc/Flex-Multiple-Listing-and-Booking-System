<?php
/**
 * Unified sticky sidebar: booking, help, favorites.
 *
 * @package FlexBookingSystem
 *
 * @var int    $ulbm_booking_type_id Booking type ID.
 * @var string $ulbm_help_phone      Help phone.
 * @var string $ulbm_help_email      Help email.
 * @var int    $ulbm_post_id         Listing post ID.
 */

defined( 'ABSPATH' ) || exit;

$ulbm_booking_type_id = isset( $ulbm_booking_type_id ) ? (int) $ulbm_booking_type_id : 0;
$ulbm_help_phone      = isset( $ulbm_help_phone ) ? (string) $ulbm_help_phone : '';
$ulbm_help_email      = isset( $ulbm_help_email ) ? (string) $ulbm_help_email : '';
$ulbm_post_id         = isset( $ulbm_post_id ) ? (int) $ulbm_post_id : get_the_ID();
?>
<div class="ulbm-sidebar ulbm-sidebar--unified">
	<?php include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-booking-widget.php'; ?>

	<div class="ulbm-help-widget">
		<h3 class="ulbm-help-widget-title"><?php esc_html_e( 'Need Help?', 'flex-multiple-listing-and-booking-system' ); ?></h3>
		<p class="ulbm-help-widget-text"><?php esc_html_e( 'Our team is here to help you.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		<?php if ( $ulbm_help_phone ) : ?>
			<p class="ulbm-help-contact"><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:<?php echo esc_attr( $ulbm_help_phone ); ?>"><?php echo esc_html( $ulbm_help_phone ); ?></a></p>
		<?php endif; ?>
		<?php if ( $ulbm_help_email ) : ?>
			<p class="ulbm-help-contact mb-0"><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:<?php echo esc_attr( $ulbm_help_email ); ?>"><?php echo esc_html( $ulbm_help_email ); ?></a></p>
		<?php endif; ?>
	</div>

	<div class="ulbm-favorite-card">
		<button type="button" class="ulbm-favorite-btn" data-id="<?php echo esc_attr( (string) $ulbm_post_id ); ?>">
			<i class="bi bi-heart" aria-hidden="true"></i><?php esc_html_e( 'Add to Favorites', 'flex-multiple-listing-and-booking-system' ); ?>
		</button>
	</div>
</div>
