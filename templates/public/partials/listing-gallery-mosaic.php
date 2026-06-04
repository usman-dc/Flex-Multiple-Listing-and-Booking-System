<?php
/**
 * Marketplace-style gallery mosaic (large image + side stack).
 *
 * @package FlexBookingSystem
 *
 * @var array<int,array{full:string,large:string,alt:string}> $ulbm_slider_images Images.
 * @var int                                                   $ulbm_post_id       Post ID.
 * @var string                                                $ulbm_gallery_id    Gallery wrapper ID.
 * @var bool                                                  $ulbm_gallery_embedded Embedded in single layout.
 */

defined( 'ABSPATH' ) || exit;

// Support include from templates that pass prefixed globals (Plugin Check) or legacy names.
if ( ! isset( $ulbm_slider_images ) || ! is_array( $ulbm_slider_images ) ) {
	$ulbm_slider_images = isset( $slider_images ) && is_array( $slider_images ) ? $slider_images : array();
}
if ( ! isset( $ulbm_post_id ) || ! $ulbm_post_id ) {
	$ulbm_post_id = isset( $post_id ) ? (int) $post_id : 0;
}

if ( empty( $ulbm_slider_images ) ) {
	return;
}

$ulbm_gallery_id = isset( $ulbm_gallery_id ) ? (string) $ulbm_gallery_id : 'ulbm-gallery-' . wp_unique_id();
$ulbm_img_count  = count( $ulbm_slider_images );
$ulbm_main       = $ulbm_slider_images[0];
$ulbm_side       = array_slice( $ulbm_slider_images, 1, 3 );
$ulbm_extra      = max( 0, $ulbm_img_count - 1 - count( $ulbm_side ) );
$ulbm_is_featured = \FlexBooking\Front\ListingDisplay::is_featured( $ulbm_post_id );
$ulbm_gallery_embedded = ! empty( $ulbm_gallery_embedded );
?>
<div class="ulbm-gallery-mosaic-wrap<?php echo $ulbm_gallery_embedded ? ' ulbm-gallery-mosaic-wrap--embedded' : ''; ?>" id="<?php echo esc_attr( $ulbm_gallery_id ); ?>">
	<?php if ( ! $ulbm_gallery_embedded ) : ?><div class="container ulbm-container py-3"><?php endif; ?>
		<div class="ulbm-gallery-mosaic">
			<div class="ulbm-gallery-mosaic-main">
				<?php if ( $ulbm_is_featured ) : ?>
					<span class="ulbm-gallery-badge"><?php esc_html_e( 'Featured', 'flex-multiple-listing-and-booking-system' ); ?></span>
				<?php endif; ?>
				<button type="button" class="ulbm-gallery-open ulbm-gallery-view-photos btn btn-light btn-sm" data-index="0">
					<i class="bi bi-images" aria-hidden="true"></i><?php esc_html_e( 'View Photos', 'flex-multiple-listing-and-booking-system' ); ?>
				</button>
				<button type="button" class="ulbm-gallery-open ulbm-gallery-main-trigger border-0 p-0 w-100 h-100" data-index="0" aria-label="<?php esc_attr_e( 'View full image', 'flex-multiple-listing-and-booking-system' ); ?>">
					<img src="<?php echo esc_url( $ulbm_main['large'] ); ?>" data-full="<?php echo esc_url( $ulbm_main['full'] ); ?>" alt="<?php echo esc_attr( $ulbm_main['alt'] ); ?>" class="ulbm-gallery-main-img">
				</button>
			</div>
			<?php if ( ! empty( $ulbm_side ) ) : ?>
				<div class="ulbm-gallery-mosaic-side">
					<?php foreach ( $ulbm_side as $ulbm_idx => $ulbm_img ) : ?>
						<?php
						$ulbm_image_index = $ulbm_idx + 1;
						$ulbm_is_last     = ( $ulbm_idx === count( $ulbm_side ) - 1 ) && $ulbm_extra > 0;
						?>
						<button type="button" class="ulbm-gallery-open ulbm-gallery-side-item border-0 p-0" data-index="<?php echo esc_attr( (string) $ulbm_image_index ); ?>" aria-label="<?php
						printf(
							/* translators: %d: image number in gallery */
							esc_attr__( 'View image %d', 'flex-multiple-listing-and-booking-system' ),
							(int) $ulbm_image_index + 1
						);
						?>">
							<img src="<?php echo esc_url( $ulbm_img['large'] ); ?>" data-full="<?php echo esc_url( $ulbm_img['full'] ); ?>" alt="<?php echo esc_attr( $ulbm_img['alt'] ); ?>">
							<?php if ( $ulbm_is_last ) : ?>
								<span class="ulbm-gallery-more">+<?php echo esc_html( (string) $ulbm_extra ); ?> <?php esc_html_e( 'Photos', 'flex-multiple-listing-and-booking-system' ); ?></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php if ( ! $ulbm_gallery_embedded ) : ?></div><?php endif; ?>

	<!-- Hidden images for lightbox JS -->
	<div class="d-none ulbm-gallery-data">
		<?php foreach ( $ulbm_slider_images as $ulbm_si => $ulbm_img ) : ?>
			<img src="<?php echo esc_url( $ulbm_img['large'] ); ?>" data-full="<?php echo esc_url( $ulbm_img['full'] ); ?>" data-index="<?php echo esc_attr( (string) $ulbm_si ); ?>" alt="<?php echo esc_attr( $ulbm_img['alt'] ); ?>">
		<?php endforeach; ?>
	</div>

	<div class="modal fade ulbm-gallery-lightbox" id="<?php echo esc_attr( $ulbm_gallery_id ); ?>-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-fullscreen">
			<div class="modal-content bg-dark border-0">
				<div class="modal-header border-0 py-2 px-3">
					<span class="text-white small ulbm-lightbox-counter">1 / <?php echo esc_html( (string) $ulbm_img_count ); ?></span>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'flex-multiple-listing-and-booking-system' ); ?>"></button>
				</div>
				<div class="modal-body d-flex align-items-center justify-content-center p-0 position-relative">
					<button type="button" class="ulbm-lightbox-prev btn btn-dark btn-lg rounded-circle border-0" aria-label="<?php esc_attr_e( 'Previous', 'flex-multiple-listing-and-booking-system' ); ?>"><i class="bi bi-chevron-left"></i></button>
					<img src="" alt="" class="ulbm-lightbox-img img-fluid">
					<button type="button" class="ulbm-lightbox-next btn btn-dark btn-lg rounded-circle border-0" aria-label="<?php esc_attr_e( 'Next', 'flex-multiple-listing-and-booking-system' ); ?>"><i class="bi bi-chevron-right"></i></button>
				</div>
				<div class="modal-footer border-0 justify-content-center py-2 px-3 ulbm-lightbox-thumbs d-flex gap-2 overflow-auto">
					<?php foreach ( $ulbm_slider_images as $ulbm_si => $ulbm_img ) : ?>
						<button type="button" class="ulbm-lightbox-thumb border-0 p-0 flex-shrink-0 <?php echo 0 === $ulbm_si ? 'active' : ''; ?>" data-index="<?php echo esc_attr( (string) $ulbm_si ); ?>">
							<img src="<?php echo esc_url( $ulbm_img['large'] ); ?>" alt="">
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
