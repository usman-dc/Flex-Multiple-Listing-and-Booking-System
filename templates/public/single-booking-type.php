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
	$booking_type_row = BookingTypePostTypeRegistry::booking_type_for_post_type( (string) $post_type );
	if ( $booking_type_row ) {
		$booking_type_id = (int) $booking_type_row['id'];
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

	$general  = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
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
	$ulbm_gallery_id    = 'ulbm-gallery-' . (string) $post_id;
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

<div class="ulbm-single-listing-wrap ulbm-marketplace-ui">

	<?php include ULBM_PLUGIN_DIR . 'templates/public/partials/account-toolbar.php'; ?>

	<div class="container ulbm-container ulbm-container--single pb-5">

		<div class="row g-4 ulbm-listing-layout-row">
			<div class="col-lg-8 ulbm-listing-content">

				<div class="ulbm-hero-media">
					<?php if ( ! empty( $slider_images ) ) : ?>
						<?php
						$ulbm_gallery_embedded = true;
						include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-gallery-mosaic.php';
						?>
					<?php endif; ?>

					<?php if ( $base_price || $max_guests || $bedrooms || $bathrooms || $size ) : ?>
						<div class="ulbm-quick-stats ulbm-quick-stats--hero">
							<?php if ( $price_amount ) : ?>
								<div class="ulbm-quick-stats-price">
									<strong><?php echo esc_html( PriceFormatter::format_plain( $price_amount ) ); ?></strong>
									<span><?php echo esc_html( $price_suffix_display ); ?></span>
								</div>
							<?php endif; ?>
							<div class="ulbm-quick-stats-specs">
								<?php if ( $max_guests > 0 ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-people" aria-hidden="true"></i><?php
									printf(
										/* translators: %d: maximum guest count */
										esc_html__( '%d Guests', 'flex-booking-system' ),
										(int) $max_guests
									);
									?></span>
								<?php endif; ?>
								<?php if ( $bedrooms ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-bed" aria-hidden="true"></i><?php echo esc_html( $bedrooms ); ?></span>
								<?php endif; ?>
								<?php if ( $bathrooms ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-badge-wc" aria-hidden="true"></i><?php echo esc_html( $bathrooms ); ?></span>
								<?php endif; ?>
								<?php if ( $size ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-arrows-angle-expand" aria-hidden="true"></i><?php echo esc_html( $size ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<h1 class="ulbm-listing-title ulbm-listing-title--hero"><?php the_title(); ?></h1>
				</div>

				<div class="ulbm-listing-main">
				<header class="ulbm-listing-header">
					<?php if ( $rating_data['rating'] > 0 ) : ?>
						<?php ListingDisplay::render_star_rating( $rating_data['rating'], $rating_data['count'] ); ?>
					<?php endif; ?>
					<?php if ( $address ) : ?>
						<p class="ulbm-listing-location">
							<i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
							<?php echo esc_html( $address ); ?>
							<?php if ( $latitude && $longitude ) : ?>
								<a href="#ulbm-map" class="ulbm-view-map"><?php esc_html_e( 'View on map', 'flex-booking-system' ); ?></a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<?php if ( has_excerpt() ) : ?>
						<p class="ulbm-listing-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $amenity_features ) ) : ?>
						<div class="ulbm-quick-amenities">
							<?php $shown = 0; foreach ( $amenity_features as $f ) : if ( $shown >= 6 ) break; $shown++; ?>
								<span class="ulbm-amenity-item">
									<i class="bi <?php echo esc_attr( ! empty( $f['icon'] ) ? $f['icon'] : 'bi-check-circle' ); ?>" aria-hidden="true"></i>
									<?php echo esc_html( $f['label'] ?? '' ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</header>

				<div class="ulbm-section mb-4">
					<h2 class="ulbm-section-title"><?php esc_html_e( 'About The Property', 'flex-booking-system' ); ?></h2>
					<div class="ulbm-content-area">
						<?php the_content(); ?>
					</div>
				</div>

				<!-- Extra Services -->
				<?php if ( ! empty( $extra_svc ) ) : ?>
					<div class="ulbm-section mb-4">
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
					<div class="ulbm-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-play-circle me-2 text-primary"></i><?php esc_html_e( 'Video', 'flex-booking-system' ); ?></h4>
						<div class="ratio ratio-16x9 rounded overflow-hidden">
							<?php
							$ulbm_oembed = wp_oembed_get( $video_url );
							if ( $ulbm_oembed ) {
								echo wp_kses_post( $ulbm_oembed );
							} else {
								echo '<a href="' . esc_url( $video_url ) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">' . esc_html( $video_url ) . '</a>';
							}
							?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Location / Map -->
				<?php if ( $address || ( $latitude && $longitude ) ) : ?>
					<div class="ulbm-section mb-4" id="ulbm-map">
						<h2 class="ulbm-section-title"><?php esc_html_e( 'Location', 'flex-booking-system' ); ?></h2>
						<?php if ( $address ) : ?>
							<p class="text-muted mb-2"><i class="bi bi-pin-map me-1"></i><?php echo esc_html( $address ); ?></p>
						<?php endif; ?>
						<?php if ( $latitude && $longitude ) : ?>
							<?php
							$ulbm_maps_embed = ! empty( VendorPages::settings()['enable_google_maps_embed'] );
							$ulbm_maps_url   = 'https://www.google.com/maps?q=' . rawurlencode( $latitude . ',' . $longitude );
							?>
							<?php if ( $ulbm_maps_embed ) : ?>
								<div class="ulbm-google-map-optin rounded border p-3"
									data-lat="<?php echo esc_attr( (string) $latitude ); ?>"
									data-lng="<?php echo esc_attr( (string) $longitude ); ?>">
									<p class="small text-muted mb-2">
										<?php esc_html_e( 'Showing the embedded map loads content from Google and may share your IP address with Google.', 'flex-booking-system' ); ?>
									</p>
									<button type="button" class="btn btn-sm btn-outline-primary ulbm-show-google-map">
										<?php esc_html_e( 'Show Google Map', 'flex-booking-system' ); ?>
									</button>
									<div class="ulbm-google-map-frame d-none mt-2 rounded overflow-hidden border" style="height:280px;" aria-hidden="true"></div>
								</div>
							<?php else : ?>
								<p class="mb-0">
									<a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url( $ulbm_maps_url ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Open in Google Maps', 'flex-booking-system' ); ?>
									</a>
								</p>
							<?php endif; ?>
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
					<div class="ulbm-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-question-circle me-2 text-primary"></i><?php esc_html_e( 'Frequently Asked Questions', 'flex-booking-system' ); ?></h4>
						<div class="accordion" id="ulbm-faq-accordion-<?php echo esc_attr( (string) $post_id ); ?>">
							<?php foreach ( $faq_items as $idx => $item ) : ?>
								<div class="accordion-item">
									<h2 class="accordion-header">
										<button class="accordion-button <?php echo $idx > 0 ? 'collapsed' : ''; ?>" type="button"
											data-bs-toggle="collapse" data-bs-target="#ulbm-faq-<?php echo esc_attr( (string) $post_id . '-' . $idx ); ?>">
											<?php echo esc_html( $item['question'] ); ?>
										</button>
									</h2>
									<div id="ulbm-faq-<?php echo esc_attr( (string) $post_id . '-' . $idx ); ?>" class="accordion-collapse collapse <?php echo 0 === $idx ? 'show' : ''; ?>" data-bs-parent="#ulbm-faq-accordion-<?php echo esc_attr( (string) $post_id ); ?>">
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
				$ulbm_review_listing_id = $post_id;
				include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-reviews.php';
				?>

				</div><!-- .ulbm-listing-main -->

			</div><!-- .col-lg-8 -->

			<div class="col-lg-4 ulbm-listing-sidebar-col">
				<?php
				$ulbm_booking_type_id = $booking_type_id;
				include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-sidebar.php';
				?>

				<div class="ulbm-partner-cta ulbm-partner-cta--compact d-none">
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
