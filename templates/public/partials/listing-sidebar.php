<?php
/**
 * Unified sticky sidebar: booking, help, favorites.
 *
 * @package FlexBookingSystem
 *
 * @var int    $fbs_booking_type_id Booking type ID.
 * @var string $help_phone            Help phone.
 * @var string $help_email            Help email.
 * @var int    $post_id               Listing post ID.
 */

defined( 'ABSPATH' ) || exit;

$booking_type_id = isset( $fbs_booking_type_id ) ? (int) $fbs_booking_type_id : 0;
$help_phone      = isset( $help_phone ) ? (string) $help_phone : '';
$help_email      = isset( $help_email ) ? (string) $help_email : '';
$post_id         = isset( $post_id ) ? (int) $post_id : get_the_ID();
?>
<div class="fbs-sidebar fbs-sidebar--unified">
	<?php include FBS_PLUGIN_DIR . 'templates/public/partials/listing-booking-widget.php'; ?>

	<div class="fbs-help-widget">
		<h3 class="fbs-help-widget-title"><?php esc_html_e( 'Need Help?', 'flex-booking-system' ); ?></h3>
		<p class="fbs-help-widget-text"><?php esc_html_e( 'Our team is here to help you.', 'flex-booking-system' ); ?></p>
		<?php if ( $help_phone ) : ?>
			<p class="fbs-help-contact"><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:<?php echo esc_attr( $help_phone ); ?>"><?php echo esc_html( $help_phone ); ?></a></p>
		<?php endif; ?>
		<?php if ( $help_email ) : ?>
			<p class="fbs-help-contact mb-0"><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:<?php echo esc_attr( $help_email ); ?>"><?php echo esc_html( $help_email ); ?></a></p>
		<?php endif; ?>
	</div>

	<div class="fbs-favorite-card">
		<button type="button" class="fbs-favorite-btn" data-id="<?php echo esc_attr( (string) $post_id ); ?>">
			<i class="bi bi-heart" aria-hidden="true"></i><?php esc_html_e( 'Add to Favorites', 'flex-booking-system' ); ?>
		</button>
	</div>
</div>
