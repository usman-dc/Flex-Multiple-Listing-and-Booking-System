<?php
/**
 * Marketplace-style gallery mosaic (large image + side stack).
 *
 * @package FlexBookingSystem
 *
 * @var array<int,array{full:string,large:string,alt:string}> $slider_images Images.
 * @var int                                                   $post_id       Post ID.
 * @var string                                                $ulbm_gallery_id Gallery wrapper ID.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $slider_images ) ) {
	return;
}

$ulbm_gallery_id = isset( $ulbm_gallery_id ) ? (string) $ulbm_gallery_id : 'ulbm-gallery-' . wp_unique_id();
$img_count      = count( $slider_images );
$main           = $slider_images[0];
$side           = array_slice( $slider_images, 1, 3 );
$extra          = max( 0, $img_count - 1 - count( $side ) );
$is_featured    = \FlexBooking\Front\ListingDisplay::is_featured( $post_id );
$ulbm_gallery_embedded = ! empty( $ulbm_gallery_embedded );
?>
<div class="ulbm-gallery-mosaic-wrap<?php echo $ulbm_gallery_embedded ? ' ulbm-gallery-mosaic-wrap--embedded' : ''; ?>" id="<?php echo esc_attr( $ulbm_gallery_id ); ?>">
	<?php if ( ! $ulbm_gallery_embedded ) : ?><div class="container ulbm-container py-3"><?php endif; ?>
		<div class="ulbm-gallery-mosaic">
			<div class="ulbm-gallery-mosaic-main">
				<?php if ( $is_featured ) : ?>
					<span class="ulbm-gallery-badge"><?php esc_html_e( 'Featured', 'flex-multiple-listing-and-booking-system' ); ?></span>
				<?php endif; ?>
				<button type="button" class="ulbm-gallery-open ulbm-gallery-view-photos btn btn-light btn-sm" data-index="0">
					<i class="bi bi-images" aria-hidden="true"></i><?php esc_html_e( 'View Photos', 'flex-multiple-listing-and-booking-system' ); ?>
				</button>
				<button type="button" class="ulbm-gallery-open ulbm-gallery-main-trigger border-0 p-0 w-100 h-100" data-index="0" aria-label="<?php esc_attr_e( 'View full image', 'flex-multiple-listing-and-booking-system' ); ?>">
					<img src="<?php echo esc_url( $main['large'] ); ?>" data-full="<?php echo esc_url( $main['full'] ); ?>" alt="<?php echo esc_attr( $main['alt'] ); ?>" class="ulbm-gallery-main-img">
				</button>
			</div>
			<?php if ( ! empty( $side ) ) : ?>
				<div class="ulbm-gallery-mosaic-side">
					<?php foreach ( $side as $idx => $img ) : ?>
						<?php
						$image_index = $idx + 1;
						$is_last     = ( $idx === count( $side ) - 1 ) && $extra > 0;
						?>
						<button type="button" class="ulbm-gallery-open ulbm-gallery-side-item border-0 p-0" data-index="<?php echo esc_attr( (string) $image_index ); ?>" aria-label="<?php
						printf(
							/* translators: %d: image number in gallery */
							esc_attr__( 'View image %d', 'flex-multiple-listing-and-booking-system' ),
							(int) $image_index + 1
						);
						?>">
							<img src="<?php echo esc_url( $img['large'] ); ?>" data-full="<?php echo esc_url( $img['full'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>">
							<?php if ( $is_last ) : ?>
								<span class="ulbm-gallery-more">+<?php echo esc_html( (string) $extra ); ?> <?php esc_html_e( 'Photos', 'flex-multiple-listing-and-booking-system' ); ?></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php if ( ! $ulbm_gallery_embedded ) : ?></div><?php endif; ?>

	<!-- Hidden images for lightbox JS -->
	<div class="d-none ulbm-gallery-data">
		<?php foreach ( $slider_images as $si => $img ) : ?>
			<img src="<?php echo esc_url( $img['large'] ); ?>" data-full="<?php echo esc_url( $img['full'] ); ?>" data-index="<?php echo esc_attr( (string) $si ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>">
		<?php endforeach; ?>
	</div>

	<div class="modal fade ulbm-gallery-lightbox" id="<?php echo esc_attr( $ulbm_gallery_id ); ?>-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-fullscreen">
			<div class="modal-content bg-dark border-0">
				<div class="modal-header border-0 py-2 px-3">
					<span class="text-white small ulbm-lightbox-counter">1 / <?php echo esc_html( (string) $img_count ); ?></span>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'flex-multiple-listing-and-booking-system' ); ?>"></button>
				</div>
				<div class="modal-body d-flex align-items-center justify-content-center p-0 position-relative">
					<button type="button" class="ulbm-lightbox-prev btn btn-dark btn-lg rounded-circle border-0" aria-label="<?php esc_attr_e( 'Previous', 'flex-multiple-listing-and-booking-system' ); ?>"><i class="bi bi-chevron-left"></i></button>
					<img src="" alt="" class="ulbm-lightbox-img img-fluid">
					<button type="button" class="ulbm-lightbox-next btn btn-dark btn-lg rounded-circle border-0" aria-label="<?php esc_attr_e( 'Next', 'flex-multiple-listing-and-booking-system' ); ?>"><i class="bi bi-chevron-right"></i></button>
				</div>
				<div class="modal-footer border-0 justify-content-center py-2 px-3 ulbm-lightbox-thumbs d-flex gap-2 overflow-auto">
					<?php foreach ( $slider_images as $si => $img ) : ?>
						<button type="button" class="ulbm-lightbox-thumb border-0 p-0 flex-shrink-0 <?php echo 0 === $si ? 'active' : ''; ?>" data-index="<?php echo esc_attr( (string) $si ); ?>">
							<img src="<?php echo esc_url( $img['large'] ); ?>" alt="">
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
