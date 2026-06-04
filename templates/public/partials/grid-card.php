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

$ulbm_bedrooms  = ListingDisplay::feature_spec( $ulbm_features, 'bed' );
$ulbm_bathrooms = ListingDisplay::feature_spec( $ulbm_features, 'bath' );
$ulbm_is_feat   = ListingDisplay::is_featured( $ulbm_post_id );
$ulbm_is_new    = ListingDisplay::is_new_listing( $ulbm_post_id );

$ulbm_grid_opts  = ListingDisplay::grid_card_options();
$ulbm_amenities  = $ulbm_grid_opts['show_amenities']
	? ListingDisplay::amenity_features( is_array( $ulbm_features ) ? $ulbm_features : array(), $ulbm_grid_opts['amenities_limit'] )
	: array();
$ulbm_rating_data = ListingDisplay::rating_data( $ulbm_post_id );
?>
<div class="<?php echo esc_attr( $ulbm_col_class ); ?> ulbm-grid-item">
	<article class="ulbm-listing-card h-100">
		<div class="ulbm-card-media">
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
			<div class="ulbm-card-head-row">
				<h3 class="ulbm-card-title">
					<a href="<?php echo esc_url( $ulbm_permalink ); ?>"><?php echo esc_html( $ulbm_title ); ?></a>
				</h3>
				<?php if ( $ulbm_grid_opts['show_rating'] && $ulbm_rating_data['rating'] > 0 ) : ?>
					<?php ListingDisplay::render_grid_star_rating( $ulbm_post_id ); ?>
				<?php endif; ?>
			</div>
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
		</div>
	</article>
</div>
