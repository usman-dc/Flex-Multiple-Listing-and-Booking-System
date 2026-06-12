<?php
/**
 * Marketplace-style listing grid card.
 *
 * @package FlexBookingSystem
 *
 * @var int    $ulbm_card_post_id Post ID.
 * @var string $ulbm_card_col     Column CSS classes.
 */

use FlexBooking\Front\ListingDisplay;
use FlexBooking\Front\PriceFormatter;
use FlexBooking\Listings\ListingMeta;

defined( 'ABSPATH' ) || exit;

$ulbm_post_id    = isset( $ulbm_card_post_id ) ? (int) $ulbm_card_post_id : get_the_ID();
$ulbm_col_class  = isset( $ulbm_card_col ) ? (string) $ulbm_card_col : 'col-sm-6 col-lg-4';
$ulbm_base_price = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_BASE_PRICE, 'string' );
$ulbm_sale_price = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_SALE_PRICE, 'string' );
$ulbm_suffix     = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_PRICE_SUFFIX, 'string' );
$ulbm_max_guests = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_MAX_GUESTS, 'int' );
$ulbm_address    = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_ADDRESS, 'string' );
$ulbm_features   = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_FEATURES, 'array' );
$ulbm_instant    = ListingMeta::get( $ulbm_post_id, ListingMeta::KEY_INSTANT_BOOKING, 'bool' );
$ulbm_permalink  = get_permalink( $ulbm_post_id );
$ulbm_thumb_url  = has_post_thumbnail( $ulbm_post_id ) ? get_the_post_thumbnail_url( $ulbm_post_id, 'medium_large' ) : '';
$ulbm_title      = get_the_title( $ulbm_post_id );

$ulbm_bedrooms   = ListingDisplay::feature_spec( $ulbm_features, 'bed' );
$ulbm_bathrooms  = ListingDisplay::feature_spec( $ulbm_features, 'bath' );
$ulbm_is_feat    = ListingDisplay::is_featured( $ulbm_post_id );
$ulbm_is_new     = ListingDisplay::is_new_listing( $ulbm_post_id );
$ulbm_excerpt    = ListingDisplay::card_excerpt( $ulbm_post_id );
$ulbm_category   = ListingDisplay::booking_type_label( $ulbm_post_id );
$ulbm_price_badge = ListingDisplay::card_price_label( $ulbm_base_price, $ulbm_sale_price, $ulbm_suffix );
$ulbm_time_ago   = ListingDisplay::card_time_ago( $ulbm_post_id );

$ulbm_grid_opts   = ListingDisplay::grid_card_options();
$ulbm_amenities   = $ulbm_grid_opts['show_amenities']
	? ListingDisplay::amenity_features( is_array( $ulbm_features ) ? $ulbm_features : array(), $ulbm_grid_opts['amenities_limit'] )
	: array();
$ulbm_rating_data = ListingDisplay::rating_data( $ulbm_post_id );

$ulbm_card_classes = array( 'ulbm-listing-card', 'h-100' );
if ( $ulbm_is_feat ) {
	$ulbm_card_classes[] = 'ulbm-card--featured';
}
?>
<div class="<?php echo esc_attr( $ulbm_col_class ); ?> ulbm-grid-item">
	<article class="<?php echo esc_attr( implode( ' ', $ulbm_card_classes ) ); ?>">
		<div class="ulbm-card-media">
			<?php if ( $ulbm_category ) : ?>
				<span class="ulbm-card-category-tab"><?php echo esc_html( $ulbm_category ); ?></span>
			<?php endif; ?>
			<a href="<?php echo esc_url( $ulbm_permalink ); ?>" class="ulbm-card-media-link" tabindex="-1" aria-hidden="true">
				<?php if ( $ulbm_thumb_url ) : ?>
					<img src="<?php echo esc_url( $ulbm_thumb_url ); ?>" alt="<?php echo esc_attr( $ulbm_title ); ?>" class="ulbm-card-thumb" loading="lazy">
				<?php else : ?>
					<div class="ulbm-card-thumb ulbm-card-thumb--empty">
						<i class="bi bi-image" aria-hidden="true"></i>
					</div>
				<?php endif; ?>
			</a>
			<?php if ( $ulbm_is_feat ) : ?>
				<span class="ulbm-card-badge ulbm-card-badge--featured"><?php esc_html_e( 'Featured', 'flex-multiple-listing-and-booking-system' ); ?></span>
			<?php elseif ( $ulbm_is_new ) : ?>
				<span class="ulbm-card-badge ulbm-card-badge--new"><?php esc_html_e( 'New', 'flex-multiple-listing-and-booking-system' ); ?></span>
			<?php endif; ?>
			<button type="button" class="ulbm-card-wishlist" aria-label="<?php esc_attr_e( 'Add to favorites', 'flex-multiple-listing-and-booking-system' ); ?>" data-id="<?php echo esc_attr( (string) $ulbm_post_id ); ?>">
				<i class="bi bi-heart" aria-hidden="true"></i>
			</button>
		</div>
		<div class="ulbm-card-body">
			<?php if ( $ulbm_category ) : ?>
				<span class="ulbm-card-category-badge"><?php echo esc_html( strtoupper( $ulbm_category ) ); ?></span>
			<?php endif; ?>
			<?php if ( $ulbm_time_ago ) : ?>
				<p class="ulbm-card-time-ago"><?php echo esc_html( $ulbm_time_ago ); ?></p>
			<?php endif; ?>
			<div class="ulbm-card-head-row">
				<h3 class="ulbm-card-title">
					<a href="<?php echo esc_url( $ulbm_permalink ); ?>"><?php echo esc_html( $ulbm_title ); ?></a>
				</h3>
				<?php if ( $ulbm_price_badge ) : ?>
					<span class="ulbm-card-price-badge"><?php echo esc_html( $ulbm_price_badge ); ?></span>
				<?php endif; ?>
				<?php if ( $ulbm_grid_opts['show_rating'] && $ulbm_rating_data['rating'] > 0 ) : ?>
					<?php ListingDisplay::render_grid_star_rating( $ulbm_post_id ); ?>
				<?php endif; ?>
			</div>
			<?php if ( $ulbm_excerpt ) : ?>
				<p class="ulbm-card-excerpt"><?php echo wp_kses_post( $ulbm_excerpt ); ?></p>
			<?php endif; ?>
			<?php if ( $ulbm_address ) : ?>
				<p class="ulbm-card-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><?php echo esc_html( $ulbm_address ); ?></p>
			<?php endif; ?>
			<div class="ulbm-card-specs">
				<?php if ( $ulbm_max_guests > 0 ) : ?>
					<span><i class="bi bi-people" aria-hidden="true"></i><?php
					printf(
						/* translators: %d: maximum guest count */
						esc_html__( '%d Guests', 'flex-multiple-listing-and-booking-system' ),
						(int) $ulbm_max_guests
					);
					?></span>
				<?php endif; ?>
				<?php if ( $ulbm_bedrooms ) : ?>
					<span><i class="bi bi-door-closed" aria-hidden="true"></i><?php echo esc_html( $ulbm_bedrooms ); ?></span>
				<?php endif; ?>
				<?php if ( $ulbm_bathrooms ) : ?>
					<span><i class="bi bi-droplet" aria-hidden="true"></i><?php echo esc_html( $ulbm_bathrooms ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $ulbm_amenities ) ) : ?>
				<div class="ulbm-card-amenities">
					<?php foreach ( $ulbm_amenities as $ulbm_amenity ) : ?>
						<span class="ulbm-card-amenity">
							<i class="bi <?php echo esc_attr( $ulbm_amenity['icon'] ); ?>" aria-hidden="true"></i>
							<?php echo esc_html( $ulbm_amenity['label'] ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="ulbm-card-footer">
				<div class="ulbm-card-price">
					<?php PriceFormatter::echo_price( $ulbm_base_price, $ulbm_sale_price, $ulbm_suffix ); ?>
				</div>
				<?php if ( $ulbm_instant ) : ?>
					<span class="ulbm-card-instant"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i><?php esc_html_e( 'Instant', 'flex-multiple-listing-and-booking-system' ); ?></span>
				<?php endif; ?>
			</div>
			<a href="<?php echo esc_url( $ulbm_permalink ); ?>" class="ulbm-card-cta ulbm-card-cta--bar">
				<i class="bi bi-cart3" aria-hidden="true"></i>
				<?php esc_html_e( 'View Details', 'flex-multiple-listing-and-booking-system' ); ?>
			</a>
			<a href="<?php echo esc_url( $ulbm_permalink ); ?>" class="ulbm-card-cta ulbm-card-cta--pill">
				<?php esc_html_e( 'Learn More', 'flex-multiple-listing-and-booking-system' ); ?>
			</a>
			<div class="ulbm-card-editorial-foot">
				<a href="<?php echo esc_url( $ulbm_permalink ); ?>" class="ulbm-card-editorial-link">
					<?php esc_html_e( 'View more', 'flex-multiple-listing-and-booking-system' ); ?>
				</a>
				<a href="<?php echo esc_url( $ulbm_permalink ); ?>" class="ulbm-card-editorial-arrow" aria-label="<?php esc_attr_e( 'View listing', 'flex-multiple-listing-and-booking-system' ); ?>">
					<i class="bi bi-arrow-right" aria-hidden="true"></i>
				</a>
			</div>
		</div>
		<div class="ulbm-card-stats-bar">
			<div class="ulbm-card-stat">
				<strong><?php echo $ulbm_max_guests > 0 ? esc_html( (string) $ulbm_max_guests ) : '&mdash;'; ?></strong>
				<span><?php esc_html_e( 'Guests', 'flex-multiple-listing-and-booking-system' ); ?></span>
			</div>
			<div class="ulbm-card-stat">
				<strong><?php echo $ulbm_rating_data['rating'] > 0 ? esc_html( number_format_i18n( $ulbm_rating_data['rating'], 1 ) ) : '&mdash;'; ?></strong>
				<span><?php esc_html_e( 'Rating', 'flex-multiple-listing-and-booking-system' ); ?></span>
			</div>
			<div class="ulbm-card-stat">
				<strong><?php echo $ulbm_price_badge ? esc_html( wp_strip_all_tags( $ulbm_price_badge ) ) : '&mdash;'; ?></strong>
				<span><?php esc_html_e( 'Price', 'flex-multiple-listing-and-booking-system' ); ?></span>
			</div>
		</div>
	</article>
</div>
