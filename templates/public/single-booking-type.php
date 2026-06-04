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
	$ulbm_post_id   = get_the_ID();
	$ulbm_post_type = get_post_type();

	$ulbm_booking_type_id  = 0;
	$ulbm_booking_type_row = null;
	$ulbm_types = BookingTypePostTypeRegistry::get_registered_types();
	$ulbm_booking_type_row = BookingTypePostTypeRegistry::booking_type_for_post_type( (string) $ulbm_post_type );
	if ( $ulbm_booking_type_row ) {
		$ulbm_booking_type_id = (int) $ulbm_booking_type_row['id'];
	}

	$ulbm_gallery       = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_GALLERY, 'array' );
	$ulbm_base_price    = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_BASE_PRICE, 'string' );
	$ulbm_sale_price    = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_SALE_PRICE, 'string' );
	$ulbm_price_suffix  = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_PRICE_SUFFIX, 'string' );
	$ulbm_max_guests    = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_MAX_GUESTS, 'int' );
	$ulbm_check_in      = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_CHECK_IN_TIME, 'string' );
	$ulbm_check_out     = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_CHECK_OUT_TIME, 'string' );
	$ulbm_features      = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_FEATURES, 'array' );
	$ulbm_faq           = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_FAQ, 'array' );
	$ulbm_extra_svc     = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_EXTRA_SERVICES, 'array' );
	$ulbm_address       = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_ADDRESS, 'string' );
	$ulbm_latitude      = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_LATITUDE, 'string' );
	$ulbm_longitude     = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_LONGITUDE, 'string' );
	$ulbm_contact_email = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_CONTACT_EMAIL, 'string' );
	$ulbm_contact_phone = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_CONTACT_PHONE, 'string' );
	$ulbm_video_url     = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_VIDEO_URL, 'string' );
	$ulbm_instant       = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_INSTANT_BOOKING, 'bool' );
	$ulbm_deposit       = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_DEPOSIT_PERCENT, 'int' );
	$ulbm_cancel_days   = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_CANCELLATION_DAYS, 'int' );
	$ulbm_booking_mode  = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_BOOKING_MODE, 'string' );
	$ulbm_min_booking   = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_MIN_BOOKING, 'int' );
	$ulbm_max_booking   = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_MAX_BOOKING, 'int' );

	$ulbm_general  = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
	$ulbm_currency = is_array( $ulbm_general ) && ! empty( $ulbm_general['currency'] ) ? $ulbm_general['currency'] : 'USD';

	// Build slider images array: featured image first, then gallery.
	$ulbm_slider_images = array();
	if ( has_post_thumbnail() ) {
		$ulbm_slider_images[] = array(
			'full'  => get_the_post_thumbnail_url( $ulbm_post_id, 'full' ),
			'large' => get_the_post_thumbnail_url( $ulbm_post_id, 'large' ),
			'alt'   => get_the_title(),
		);
	}
	if ( ! empty( $ulbm_gallery ) ) {
		foreach ( $ulbm_gallery as $ulbm_att_id ) {
			$ulbm_full_url  = wp_get_attachment_image_url( (int) $ulbm_att_id, 'full' );
			$ulbm_large_url = wp_get_attachment_image_url( (int) $ulbm_att_id, 'large' );
			if ( $ulbm_full_url ) {
				$ulbm_slider_images[] = array(
					'full'  => $ulbm_full_url,
					'large' => $ulbm_large_url ?: $ulbm_full_url,
					'alt'   => get_the_title() . ' gallery',
				);
			}
		}
	}

	$ulbm_type_display_name = $ulbm_booking_type_row ? (string) $ulbm_booking_type_row['name'] : '';
	$ulbm_gallery_id    = 'ulbm-gallery-' . (string) $ulbm_post_id;
	$ulbm_bedrooms          = ListingDisplay::feature_spec( $ulbm_features, 'bed' );
	$ulbm_bathrooms         = ListingDisplay::feature_spec( $ulbm_features, 'bath' );
	$ulbm_size              = ListingDisplay::feature_spec( $ulbm_features, 'size' );
	if ( ! $ulbm_size ) {
		$ulbm_size = ListingDisplay::feature_spec( $ulbm_features, 'm²' );
	}
	$ulbm_rating_data = ListingDisplay::rating_data( $ulbm_post_id );
	$ulbm_price_amount = $ulbm_sale_price ? $ulbm_sale_price : $ulbm_base_price;
	$ulbm_price_suffix_display = PriceFormatter::normalize_suffix( $ulbm_price_suffix ?: '/night' );
	$ulbm_amenity_features = array();
	foreach ( $ulbm_features as $ulbm_feat ) {
		if ( is_array( $ulbm_feat ) && ! ListingDisplay::is_spec_feature( $ulbm_feat ) ) {
			$ulbm_amenity_features[] = $ulbm_feat;
		}
	}
	$ulbm_help_email = $ulbm_contact_email ?: get_option( 'admin_email' );
	$ulbm_help_phone = $ulbm_contact_phone ?: '';
?>

<div class="ulbm-single-listing-wrap ulbm-marketplace-ui">

	<?php include ULBM_PLUGIN_DIR . 'templates/public/partials/account-toolbar.php'; ?>

	<div class="container ulbm-container ulbm-container--single pb-5">

		<div class="row g-4 ulbm-listing-layout-row">
			<div class="col-lg-8 ulbm-listing-content">

				<div class="ulbm-hero-media">
					<?php if ( ! empty( $ulbm_slider_images ) ) : ?>
						<?php
						$ulbm_gallery_embedded = true;
						include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-gallery-mosaic.php';
						?>
					<?php endif; ?>

					<?php if ( $ulbm_base_price || $ulbm_max_guests || $ulbm_bedrooms || $ulbm_bathrooms || $ulbm_size ) : ?>
						<div class="ulbm-quick-stats ulbm-quick-stats--hero">
							<?php if ( $ulbm_price_amount ) : ?>
								<div class="ulbm-quick-stats-price">
									<strong><?php echo esc_html( PriceFormatter::format_plain( $ulbm_price_amount ) ); ?></strong>
									<span><?php echo esc_html( $ulbm_price_suffix_display ); ?></span>
								</div>
							<?php endif; ?>
							<div class="ulbm-quick-stats-specs">
								<?php if ( $ulbm_max_guests > 0 ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-people" aria-hidden="true"></i><?php
									printf(
										/* translators: %d: maximum guest count */
										esc_html__( '%d Guests', 'flex-multiple-listing-and-booking-system' ),
										(int) $ulbm_max_guests
									);
									?></span>
								<?php endif; ?>
								<?php if ( $ulbm_bedrooms ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-bed" aria-hidden="true"></i><?php echo esc_html( $ulbm_bedrooms ); ?></span>
								<?php endif; ?>
								<?php if ( $ulbm_bathrooms ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-badge-wc" aria-hidden="true"></i><?php echo esc_html( $ulbm_bathrooms ); ?></span>
								<?php endif; ?>
								<?php if ( $ulbm_size ) : ?>
									<span class="ulbm-quick-stat"><i class="bi bi-arrows-angle-expand" aria-hidden="true"></i><?php echo esc_html( $ulbm_size ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<h1 class="ulbm-listing-title ulbm-listing-title--hero"><?php the_title(); ?></h1>
				</div>

				<div class="ulbm-listing-main">
				<header class="ulbm-listing-header">
					<?php if ( $ulbm_rating_data['rating'] > 0 ) : ?>
						<?php ListingDisplay::render_star_rating( $ulbm_rating_data['rating'], $ulbm_rating_data['count'] ); ?>
					<?php endif; ?>
					<?php if ( $ulbm_address ) : ?>
						<p class="ulbm-listing-location">
							<i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
							<?php echo esc_html( $ulbm_address ); ?>
							<?php if ( $ulbm_latitude && $ulbm_longitude ) : ?>
								<a href="#ulbm-map" class="ulbm-view-map"><?php esc_html_e( 'View on map', 'flex-multiple-listing-and-booking-system' ); ?></a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<?php if ( has_excerpt() ) : ?>
						<p class="ulbm-listing-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $ulbm_amenity_features ) ) : ?>
						<div class="ulbm-quick-amenities">
							<?php $ulbm_shown = 0; foreach ( $ulbm_amenity_features as $ulbm_f ) : if ( $ulbm_shown >= 6 ) break; $ulbm_shown++; ?>
								<span class="ulbm-amenity-item">
									<i class="bi <?php echo esc_attr( ! empty( $ulbm_f['icon'] ) ? $ulbm_f['icon'] : 'bi-check-circle' ); ?>" aria-hidden="true"></i>
									<?php echo esc_html( $ulbm_f['label'] ?? '' ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</header>

				<div class="ulbm-section mb-4">
					<h2 class="ulbm-section-title"><?php esc_html_e( 'About The Property', 'flex-multiple-listing-and-booking-system' ); ?></h2>
					<div class="ulbm-content-area">
						<?php the_content(); ?>
					</div>
				</div>

				<!-- Extra Services -->
				<?php if ( ! empty( $ulbm_extra_svc ) ) : ?>
					<div class="ulbm-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i><?php esc_html_e( 'Extra Services', 'flex-multiple-listing-and-booking-system' ); ?></h4>
						<div class="list-group">
							<?php foreach ( $ulbm_extra_svc as $ulbm_svc ) : ?>
								<div class="list-group-item d-flex justify-content-between align-items-center">
									<span>
										<i class="bi bi-check2 text-success me-1"></i>
										<?php echo esc_html( $ulbm_svc['name'] ); ?>
										<?php if ( ! empty( $ulbm_svc['required'] ) ) : ?>
											<span class="badge text-bg-warning ms-1"><?php esc_html_e( 'Required', 'flex-multiple-listing-and-booking-system' ); ?></span>
										<?php endif; ?>
									</span>
									<span class="fw-semibold">
										<?php echo esc_html( $ulbm_currency . ' ' . number_format_i18n( (float) $ulbm_svc['price'], 2 ) ); ?>
										<small class="text-muted fw-normal">/<?php echo esc_html( $ulbm_svc['per'] ?? 'booking' ); ?></small>
									</span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Video -->
				<?php if ( $ulbm_video_url ) : ?>
					<div class="ulbm-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-play-circle me-2 text-primary"></i><?php esc_html_e( 'Video', 'flex-multiple-listing-and-booking-system' ); ?></h4>
						<div class="ratio ratio-16x9 rounded overflow-hidden">
							<?php
							$ulbm_oembed = wp_oembed_get( $ulbm_video_url );
							if ( $ulbm_oembed ) {
								echo wp_kses_post( $ulbm_oembed );
							} else {
								echo '<a href="' . esc_url( $ulbm_video_url ) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">' . esc_html( $ulbm_video_url ) . '</a>';
							}
							?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Location / Map -->
				<?php if ( $ulbm_address || ( $ulbm_latitude && $ulbm_longitude ) ) : ?>
					<div class="ulbm-section mb-4" id="ulbm-map">
						<h2 class="ulbm-section-title"><?php esc_html_e( 'Location', 'flex-multiple-listing-and-booking-system' ); ?></h2>
						<?php if ( $ulbm_address ) : ?>
							<p class="text-muted mb-2"><i class="bi bi-pin-map me-1"></i><?php echo esc_html( $ulbm_address ); ?></p>
						<?php endif; ?>
						<?php if ( $ulbm_latitude && $ulbm_longitude ) : ?>
							<?php
							$ulbm_maps_embed = ! empty( VendorPages::settings()['enable_google_maps_embed'] );
							$ulbm_maps_url   = 'https://www.google.com/maps?q=' . rawurlencode( $ulbm_latitude . ',' . $ulbm_longitude );
							?>
							<?php if ( $ulbm_maps_embed ) : ?>
								<div class="ulbm-google-map-optin rounded border p-3"
									data-lat="<?php echo esc_attr( (string) $ulbm_latitude ); ?>"
									data-lng="<?php echo esc_attr( (string) $ulbm_longitude ); ?>">
									<p class="small text-muted mb-2">
										<?php esc_html_e( 'Showing the embedded map loads content from Google and may share your IP address with Google.', 'flex-multiple-listing-and-booking-system' ); ?>
									</p>
									<button type="button" class="btn btn-sm btn-outline-primary ulbm-show-google-map">
										<?php esc_html_e( 'Show Google Map', 'flex-multiple-listing-and-booking-system' ); ?>
									</button>
									<div class="ulbm-google-map-frame d-none mt-2 rounded overflow-hidden border" style="height:280px;" aria-hidden="true"></div>
								</div>
							<?php else : ?>
								<p class="mb-0">
									<a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url( $ulbm_maps_url ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Open in Google Maps', 'flex-multiple-listing-and-booking-system' ); ?>
									</a>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- FAQ -->
				<?php
				$ulbm_faq_items = array();
				if ( ! empty( $ulbm_faq ) ) {
					foreach ( $ulbm_faq as $ulbm_fi ) {
						if ( is_array( $ulbm_fi ) && ! empty( $ulbm_fi['question'] ) ) {
							$ulbm_faq_items[] = $ulbm_fi;
						}
					}
				}
				?>
				<?php if ( ! empty( $ulbm_faq_items ) ) : ?>
					<div class="ulbm-section mb-4">
						<h4 class="fw-bold mb-3"><i class="bi bi-question-circle me-2 text-primary"></i><?php esc_html_e( 'Frequently Asked Questions', 'flex-multiple-listing-and-booking-system' ); ?></h4>
						<div class="accordion" id="ulbm-faq-accordion-<?php echo esc_attr( (string) $ulbm_post_id ); ?>">
							<?php foreach ( $ulbm_faq_items as $ulbm_idx => $ulbm_item ) : ?>
								<div class="accordion-item">
									<h2 class="accordion-header">
										<button class="accordion-button <?php echo $ulbm_idx > 0 ? 'collapsed' : ''; ?>" type="button"
											data-bs-toggle="collapse" data-bs-target="#ulbm-faq-<?php echo esc_attr( (string) $ulbm_post_id . '-' . $ulbm_idx ); ?>">
											<?php echo esc_html( $ulbm_item['question'] ); ?>
										</button>
									</h2>
									<div id="ulbm-faq-<?php echo esc_attr( (string) $ulbm_post_id . '-' . $ulbm_idx ); ?>" class="accordion-collapse collapse <?php echo 0 === $ulbm_idx ? 'show' : ''; ?>" data-bs-parent="#ulbm-faq-accordion-<?php echo esc_attr( (string) $ulbm_post_id ); ?>">
										<div class="accordion-body">
											<?php echo wp_kses_post( nl2br( esc_html( $ulbm_item['answer'] ?? '' ) ) ); ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				$ulbm_review_listing_id = $ulbm_post_id;
				include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-reviews.php';
				?>

				</div><!-- .ulbm-listing-main -->

			</div><!-- .col-lg-8 -->

			<div class="col-lg-4 ulbm-listing-sidebar-col">
				<?php include ULBM_PLUGIN_DIR . 'templates/public/partials/listing-sidebar.php'; ?>

				<div class="ulbm-partner-cta ulbm-partner-cta--compact d-none">
						<?php if ( is_user_logged_in() && VendorRole::can_manage_listings() ) : ?>
							<h6 class="fw-semibold mb-2"><i class="bi bi-building me-1 text-primary"></i><?php esc_html_e( 'Manage your listings', 'flex-multiple-listing-and-booking-system' ); ?></h6>
							<p class="small text-muted mb-3"><?php esc_html_e( 'Add a new property, car, tour, or service from your partner dashboard.', 'flex-multiple-listing-and-booking-system' ); ?></p>
							<div class="d-grid gap-2">
								<a href="<?php echo esc_url( VendorPages::add_listing_url() ); ?>" class="btn btn-primary btn-sm">
									<i class="bi bi-plus-lg me-1"></i><?php esc_html_e( 'Add New Listing', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
								<a href="<?php echo esc_url( VendorPages::dashboard_url() ); ?>" class="btn btn-outline-secondary btn-sm">
									<i class="bi bi-person-circle me-1"></i><?php esc_html_e( 'Go to Account', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</div>
						<?php else : ?>
							<h6 class="fw-semibold mb-2"><i class="bi bi-megaphone me-1 text-primary"></i><?php esc_html_e( 'Want to list here?', 'flex-multiple-listing-and-booking-system' ); ?></h6>
							<p class="small text-muted mb-3"><?php esc_html_e( 'Register as a partner to add your property, car, tour, or service and receive bookings.', 'flex-multiple-listing-and-booking-system' ); ?></p>
							<div class="d-grid gap-2">
								<a href="<?php echo esc_url( VendorPages::register_url() ); ?>" class="btn btn-primary btn-sm">
									<i class="bi bi-person-plus me-1"></i><?php esc_html_e( 'Register & Add Listing', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
								<?php if ( ! is_user_logged_in() ) : ?>
									<a href="<?php echo esc_url( VendorPages::login_url() ); ?>" class="btn btn-outline-secondary btn-sm">
										<i class="bi bi-box-arrow-in-right me-1"></i><?php esc_html_e( 'Already have an account? Log in', 'flex-multiple-listing-and-booking-system' ); ?>
									</a>
								<?php else : ?>
									<a href="<?php echo esc_url( VendorPages::register_url() ); ?>" class="btn btn-outline-secondary btn-sm">
										<i class="bi bi-person-circle me-1"></i><?php esc_html_e( 'Apply for Partner Access', 'flex-multiple-listing-and-booking-system' ); ?>
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
