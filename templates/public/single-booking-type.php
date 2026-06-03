<?php
/**
 * Single page template for booking type CPTs.
 *
 * Layout:
 *   - Top: Image slider (featured + gallery)
 *   - Meta info bar with icons
 *   - Two columns: left = description, features, services, map, FAQ
 *                  right = booking form (sticky sidebar)
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Listings\ListingMeta;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;
use FlexBooking\Front\PriceFormatter;
use FlexBooking\Front\ListingDisplay;
use FlexBooking\Vendor\VendorPages;
use FlexBooking\Vendor\VendorRole;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$post_id   = get_the_ID();
	$post_type = get_post_type();

	$booking_type_id  = 0;
	$booking_type_row = null;
	$types = BookingTypePostTypeRegistry::get_registered_types();
	foreach ( $types as $t ) {
		if ( BookingTypePostTypeRegistry::cpt_name_from_slug( $t['slug'] ) === $post_type ) {
			$booking_type_id  = (int) $t['id'];
			$booking_type_row = $t;
			break;
		}
	}

	$gallery       = ListingMeta::get( $post_id, ListingMeta::KEY_GALLERY, 'array' );
	$base_price    = ListingMeta::get( $post_id, ListingMeta::KEY_BASE_PRICE, 'string' );
	$sale_price    = ListingMeta::get( $post_id, ListingMeta::KEY_SALE_PRICE, 'string' );
	$price_suffix  = ListingMeta::get( $post_id, ListingMeta::KEY_PRICE_SUFFIX, 'string' );
	$max_guests    = ListingMeta::get( $post_id, ListingMeta::KEY_MAX_GUESTS, 'int' );
	$check_in      = ListingMeta::get( $post_id, ListingMeta::KEY_CHECK_IN_TIME, 'string' );
	$check_out     = ListingMeta::get( $post_id, ListingMeta::KEY_CHECK_OUT_TIME, 'string' );
	$features      = ListingMeta::get( $post_id, ListingMeta::KEY_FEATURES, 'array' );
	$faq           = ListingMeta::get( $post_id, ListingMeta::KEY_FAQ, 'array' );
	$extra_svc     = ListingMeta::get( $post_id, ListingMeta::KEY_EXTRA_SERVICES, 'array' );
	$address       = ListingMeta::get( $post_id, ListingMeta::KEY_ADDRESS, 'string' );
	$latitude      = ListingMeta::get( $post_id, ListingMeta::KEY_LATITUDE, 'string' );
	$longitude     = ListingMeta::get( $post_id, ListingMeta::KEY_LONGITUDE, 'string' );
	$contact_email = ListingMeta::get( $post_id, ListingMeta::KEY_CONTACT_EMAIL, 'string' );
	$contact_phone = ListingMeta::get( $post_id, ListingMeta::KEY_CONTACT_PHONE, 'string' );
	$video_url     = ListingMeta::get( $post_id, ListingMeta::KEY_VIDEO_URL, 'string' );
	$instant       = ListingMeta::get( $post_id, ListingMeta::KEY_INSTANT_BOOKING, 'bool' );
	$deposit       = ListingMeta::get( $post_id, ListingMeta::KEY_DEPOSIT_PERCENT, 'int' );
	$cancel_days   = ListingMeta::get( $post_id, ListingMeta::KEY_CANCELLATION_DAYS, 'int' );
	$booking_mode  = ListingMeta::get( $post_id, ListingMeta::KEY_BOOKING_MODE, 'string' );
	$min_booking   = ListingMeta::get( $post_id, ListingMeta::KEY_MIN_BOOKING, 'int' );
	$max_booking   = ListingMeta::get( $post_id, ListingMeta::KEY_MAX_BOOKING, 'int' );

	$general  = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
	$currency = is_array( $general ) && ! empty( $general['currency'] ) ? $general['currency'] : 'USD';

	// Build slider images array: featured image first, then gallery.
	$slider_images = array();
	if ( has_post_thumbnail() ) {
		$slider_images[] = array(
			'full'  => get_the_post_thumbnail_url( $post_id, 'full' ),
			'large' => get_the_post_thumbnail_url( $post_id, 'large' ),
			'alt'   => get_the_title(),
		);
	}
	if ( ! empty( $gallery ) ) {
		foreach ( $gallery as $att_id ) {
			$full_url  = wp_get_attachment_image_url( (int) $att_id, 'full' );
			$large_url = wp_get_attachment_image_url( (int) $att_id, 'large' );
			if ( $full_url ) {
				$slider_images[] = array(
					'full'  => $full_url,
					'large' => $large_url ?: $full_url,
					'alt'   => get_the_title() . ' gallery',
				);
			}
		}
	}

	$type_display_name = $booking_type_row ? (string) $booking_type_row['name'] : '';
	$fbs_gallery_id    = 'fbs-gallery-' . (string) $post_id;
	$bedrooms          = ListingDisplay::feature_spec( $features, 'bed' );
	$bathrooms         = ListingDisplay::feature_spec( $features, 'bath' );
	$size              = ListingDisplay::feature_spec( $features, 'size' );
	if ( ! $size ) {
		$size = ListingDisplay::feature_spec( $features, 'm²' );
	}
	$rating_data = ListingDisplay::rating_data( $post_id );
	$price_amount = $sale_price ? $sale_price : $base_price;
	$price_suffix_display = PriceFormatter::normalize_suffix( $price_suffix ?: '/night' );
	$amenity_features = array();
	foreach ( $features as $feat ) {
		if ( is_array( $feat ) && ! ListingDisplay::is_spec_feature( $feat ) ) {
			$amenity_features[] = $feat;
		}
	}
	$help_email = $contact_email ?: get_option( 'admin_email' );
	$help_phone = $contact_phone ?: '';
?>

<div class="fbs-single-listing-wrap fbs-marketplace-ui">

	<?php include FBS_PLUGIN_DIR . 'templates/public/partials/account-toolbar.php'; ?>

	<div class="container fbs-container fbs-container--single pb-5">

		<div class="row g-4 fbs-listing-layout-row">
			<div class="col-lg-8 fbs-listing-content">

				<div class="fbs-hero-media">
					<?php if ( ! empty( $slider_images ) ) : ?>
						<?php
						$fbs_gallery_embedded = true;
						include FBS_PLUGIN_DIR . 'templates/public/partials/listing-gallery-mosaic.php';
						?>
					<?php endif; ?>

					<?php if ( $base_price || $max_guests || $bedrooms || $bathrooms || $size ) : ?>
						<div class="fbs-quick-stats fbs-quick-stats--hero">
							<?php if ( $price_amount ) : ?>
								<div class="fbs-quick-stats-price">
									<strong><?php echo esc_html( PriceFormatter::format_plain( $price_amount ) ); ?></strong>
									<span><?php echo esc_html( $price_suffix_display ); ?></span>
								</div>
							<?php endif; ?>
							<div class="fbs-quick-stats-specs">
								<?php if ( $max_guests > 0 ) : ?>
									<span class="fbs-quick-stat"><i class="bi bi-people" aria-hidden="true"></i><?php printf( esc_html__( '%d Guests', 'flex-booking-system' ), $max_guests ); ?></span>
								<?php endif; ?>
								<?php if ( $bedrooms ) : ?>
									<span class="fbs-quick-stat"><i class="bi bi-bed" aria-hidden="true"></i><?php echo esc_html( $bedrooms ); ?></span>
								<?php endif; ?>
								<?php if ( $bathrooms ) : ?>
									<span class="fbs-quick-stat"><i class="bi bi-badge-wc" aria-hidden="true"></i><?php echo esc_html( $bathrooms ); ?></span>
								<?php endif; ?>
								<?php if ( $size ) : ?>
									<span class="fbs-quick-stat"><i class="bi bi-arrows-angle-expand" aria-hidden="true"></i><?php echo esc_html( $size ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<h1 class="fbs-listing-title fbs-listing-title--hero"><?php the_title(); ?></h1>
				</div>

				<div class="fbs-listing-main">
				<header class="fbs-listing-header">
					<?php if ( $rating_data['rating'] > 0 ) : ?>
						<?php ListingDisplay::render_star_rating( $rating_data['rating'], $rating_data['count'] ); ?>
					<?php endif; ?>
					<?php if ( $address ) : ?>
						<p class="fbs-listing-location">
							<i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
							<?php echo esc_html( $address ); ?>
							<?php if ( $latitude && $longitude ) : ?>
								<a href="#fbs-map" class="fbs-view-map"><?php esc_html_e( 'View on map', 'flex-booking-system' ); ?></a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<?php if ( has_excerpt() ) : ?>
						<p class="fbs-listing-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $amenity_features ) ) : ?>
						<div class="fbs-quick-amenities">
							<?php $shown = 0; foreach ( $amenity_features as $f ) : if ( $shown >= 6 ) break; $shown++; ?>
								<span class="fbs-amenity-item">
									<i class="bi <?php echo esc_attr( ! empty( $f['icon'] ) ? $f['icon'] : 'bi-check-circle' ); ?>" aria-hidden="true"></i>
									<?php echo esc_html( $f['label'] ?? '' ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</header>

				<div class="fbs-section mb-4">
					<h2 class="fbs-section-title"><?php esc_html_e( 'About The Property', 'flex-booking-system' ); ?></h2>
					<div class="fbs-content-area">
						<?php the_content(); ?>
					</div>
				</div>

				<!-- Extra Services -->
				<?php if ( ! empty( $extra_svc ) ) : ?>
					<div class="fbs-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i><?php esc_html_e( 'Extra Services', 'flex-booking-system' ); ?></h4>
						<div class="list-group">
							<?php foreach ( $extra_svc as $svc ) : ?>
								<div class="list-group-item d-flex justify-content-between align-items-center">
									<span>
										<i class="bi bi-check2 text-success me-1"></i>
										<?php echo esc_html( $svc['name'] ); ?>
										<?php if ( ! empty( $svc['required'] ) ) : ?>
											<span class="badge text-bg-warning ms-1"><?php esc_html_e( 'Required', 'flex-booking-system' ); ?></span>
										<?php endif; ?>
									</span>
									<span class="fw-semibold">
										<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $svc['price'], 2 ) ); ?>
										<small class="text-muted fw-normal">/<?php echo esc_html( $svc['per'] ?? 'booking' ); ?></small>
									</span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Video -->
				<?php if ( $video_url ) : ?>
					<div class="fbs-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-play-circle me-2 text-primary"></i><?php esc_html_e( 'Video', 'flex-booking-system' ); ?></h4>
						<div class="ratio ratio-16x9 rounded overflow-hidden">
							<?php echo wp_oembed_get( $video_url ) ?: '<a href="' . esc_url( $video_url ) . '" target="_blank" class="btn btn-outline-primary">' . esc_html( $video_url ) . '</a>'; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Location / Map -->
				<?php if ( $address || ( $latitude && $longitude ) ) : ?>
					<div class="fbs-section mb-4" id="fbs-map">
						<h2 class="fbs-section-title"><?php esc_html_e( 'Location', 'flex-booking-system' ); ?></h2>
						<?php if ( $address ) : ?>
							<p class="text-muted mb-2"><i class="bi bi-pin-map me-1"></i><?php echo esc_html( $address ); ?></p>
						<?php endif; ?>
						<?php if ( $latitude && $longitude ) : ?>
							<div class="rounded overflow-hidden border" style="height:280px;">
								<iframe width="100%" height="280" frameborder="0" style="border:0"
									src="https://maps.google.com/maps?q=<?php echo esc_attr( $latitude ); ?>,<?php echo esc_attr( $longitude ); ?>&z=14&output=embed"
									allowfullscreen loading="lazy"></iframe>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- FAQ -->
				<?php
				$faq_items = array();
				if ( ! empty( $faq ) ) {
					foreach ( $faq as $fi ) {
						if ( is_array( $fi ) && ! empty( $fi['question'] ) ) {
							$faq_items[] = $fi;
						}
					}
				}
				?>
				<?php if ( ! empty( $faq_items ) ) : ?>
					<div class="fbs-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-question-circle me-2 text-primary"></i><?php esc_html_e( 'Frequently Asked Questions', 'flex-booking-system' ); ?></h4>
						<div class="accordion" id="fbs-faq-accordion-<?php echo esc_attr( (string) $post_id ); ?>">
							<?php foreach ( $faq_items as $idx => $item ) : ?>
								<div class="accordion-item">
									<h2 class="accordion-header">
										<button class="accordion-button <?php echo $idx > 0 ? 'collapsed' : ''; ?>" type="button"
											data-bs-toggle="collapse" data-bs-target="#fbs-faq-<?php echo esc_attr( (string) $post_id . '-' . $idx ); ?>">
											<?php echo esc_html( $item['question'] ); ?>
										</button>
									</h2>
									<div id="fbs-faq-<?php echo esc_attr( (string) $post_id . '-' . $idx ); ?>" class="accordion-collapse collapse <?php echo 0 === $idx ? 'show' : ''; ?>" data-bs-parent="#fbs-faq-accordion-<?php echo esc_attr( (string) $post_id ); ?>">
										<div class="accordion-body">
											<?php echo wp_kses_post( nl2br( esc_html( $item['answer'] ?? '' ) ) ); ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				$fbs_review_listing_id = $post_id;
				include FBS_PLUGIN_DIR . 'templates/public/partials/listing-reviews.php';
				?>

				</div><!-- .fbs-listing-main -->

			</div><!-- .col-lg-8 -->

			<div class="col-lg-4 fbs-listing-sidebar-col">
				<?php
				$fbs_booking_type_id = $booking_type_id;
				include FBS_PLUGIN_DIR . 'templates/public/partials/listing-sidebar.php';
				?>

				<div class="fbs-partner-cta fbs-partner-cta--compact d-none">
						<?php if ( is_user_logged_in() && VendorRole::can_manage_listings() ) : ?>
							<h6 class="fw-semibold mb-2"><i class="bi bi-building me-1 text-primary"></i><?php esc_html_e( 'Manage your listings', 'flex-booking-system' ); ?></h6>
							<p class="small text-muted mb-3"><?php esc_html_e( 'Add a new property, car, tour, or service from your partner dashboard.', 'flex-booking-system' ); ?></p>
							<div class="d-grid gap-2">
								<a href="<?php echo esc_url( VendorPages::add_listing_url() ); ?>" class="btn btn-primary btn-sm">
									<i class="bi bi-plus-lg me-1"></i><?php esc_html_e( 'Add New Listing', 'flex-booking-system' ); ?>
								</a>
								<a href="<?php echo esc_url( VendorPages::dashboard_url() ); ?>" class="btn btn-outline-secondary btn-sm">
									<i class="bi bi-person-circle me-1"></i><?php esc_html_e( 'Go to Account', 'flex-booking-system' ); ?>
								</a>
							</div>
						<?php else : ?>
							<h6 class="fw-semibold mb-2"><i class="bi bi-megaphone me-1 text-primary"></i><?php esc_html_e( 'Want to list here?', 'flex-booking-system' ); ?></h6>
							<p class="small text-muted mb-3"><?php esc_html_e( 'Register as a partner to add your property, car, tour, or service and receive bookings.', 'flex-booking-system' ); ?></p>
							<div class="d-grid gap-2">
								<a href="<?php echo esc_url( VendorPages::register_url() ); ?>" class="btn btn-primary btn-sm">
									<i class="bi bi-person-plus me-1"></i><?php esc_html_e( 'Register & Add Listing', 'flex-booking-system' ); ?>
								</a>
								<?php if ( ! is_user_logged_in() ) : ?>
									<a href="<?php echo esc_url( VendorPages::login_url() ); ?>" class="btn btn-outline-secondary btn-sm">
										<i class="bi bi-box-arrow-in-right me-1"></i><?php esc_html_e( 'Already have an account? Log in', 'flex-booking-system' ); ?>
									</a>
								<?php else : ?>
									<a href="<?php echo esc_url( VendorPages::register_url() ); ?>" class="btn btn-outline-secondary btn-sm">
										<i class="bi bi-person-circle me-1"></i><?php esc_html_e( 'Apply for Partner Access', 'flex-booking-system' ); ?>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
			</div><!-- .col-lg-4 -->

		</div><!-- .row -->
	</div><!-- .container -->
</div>

<?php
endwhile;

get_footer();
