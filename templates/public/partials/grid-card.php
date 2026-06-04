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

$post_id    = isset( $ulbm_card_post_id ) ? (int) $ulbm_card_post_id : get_the_ID();
$col_class  = isset( $ulbm_card_col ) ? (string) $ulbm_card_col : 'col-sm-6 col-lg-4';
$base_price = ListingMeta::get( $post_id, ListingMeta::KEY_BASE_PRICE, 'string' );
$sale_price = ListingMeta::get( $post_id, ListingMeta::KEY_SALE_PRICE, 'string' );
$suffix     = ListingMeta::get( $post_id, ListingMeta::KEY_PRICE_SUFFIX, 'string' );
$max_guests = ListingMeta::get( $post_id, ListingMeta::KEY_MAX_GUESTS, 'int' );
$address    = ListingMeta::get( $post_id, ListingMeta::KEY_ADDRESS, 'string' );
$features   = ListingMeta::get( $post_id, ListingMeta::KEY_FEATURES, 'array' );
$instant    = ListingMeta::get( $post_id, ListingMeta::KEY_INSTANT_BOOKING, 'bool' );
$permalink  = get_permalink( $post_id );
$thumb_url  = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'medium_large' ) : '';
$title      = get_the_title( $post_id );

$bedrooms  = ListingDisplay::feature_spec( $features, 'bed' );
$bathrooms = ListingDisplay::feature_spec( $features, 'bath' );
$is_feat   = ListingDisplay::is_featured( $post_id );
$is_new    = ListingDisplay::is_new_listing( $post_id );

$grid_opts  = ListingDisplay::grid_card_options();
$amenities  = $grid_opts['show_amenities']
	? ListingDisplay::amenity_features( is_array( $features ) ? $features : array(), $grid_opts['amenities_limit'] )
	: array();
$rating_data = ListingDisplay::rating_data( $post_id );
?>
<div class="<?php echo esc_attr( $col_class ); ?> ulbm-grid-item">
	<article class="ulbm-listing-card h-100">
		<div class="ulbm-card-media">
			<a href="<?php echo esc_url( $permalink ); ?>" class="ulbm-card-media-link" tabindex="-1" aria-hidden="true">
				<?php if ( $thumb_url ) : ?>
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="ulbm-card-thumb" loading="lazy">
				<?php else : ?>
					<div class="ulbm-card-thumb ulbm-card-thumb--empty">
						<i class="bi bi-image" aria-hidden="true"></i>
					</div>
				<?php endif; ?>
			</a>
			<?php if ( $is_feat ) : ?>
				<span class="ulbm-card-badge ulbm-card-badge--featured"><?php esc_html_e( 'Featured', 'flex-booking-system' ); ?></span>
			<?php elseif ( $is_new ) : ?>
				<span class="ulbm-card-badge ulbm-card-badge--new"><?php esc_html_e( 'New', 'flex-booking-system' ); ?></span>
			<?php endif; ?>
			<button type="button" class="ulbm-card-wishlist" aria-label="<?php esc_attr_e( 'Add to favorites', 'flex-booking-system' ); ?>" data-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<i class="bi bi-heart" aria-hidden="true"></i>
			</button>
		</div>
		<div class="ulbm-card-body">
			<div class="ulbm-card-head-row">
				<h3 class="ulbm-card-title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>
				<?php if ( $grid_opts['show_rating'] && $rating_data['rating'] > 0 ) : ?>
					<?php ListingDisplay::render_grid_star_rating( $post_id ); ?>
				<?php endif; ?>
			</div>
			<?php if ( $address ) : ?>
				<p class="ulbm-card-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
			<div class="ulbm-card-specs">
				<?php if ( $max_guests > 0 ) : ?>
					<span><i class="bi bi-people" aria-hidden="true"></i><?php
					printf(
						/* translators: %d: maximum guest count */
						esc_html__( '%d Guests', 'flex-booking-system' ),
						(int) $max_guests
					);
					?></span>
				<?php endif; ?>
				<?php if ( $bedrooms ) : ?>
					<span><i class="bi bi-door-closed" aria-hidden="true"></i><?php echo esc_html( $bedrooms ); ?></span>
				<?php endif; ?>
				<?php if ( $bathrooms ) : ?>
					<span><i class="bi bi-droplet" aria-hidden="true"></i><?php echo esc_html( $bathrooms ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $amenities ) ) : ?>
				<div class="ulbm-card-amenities">
					<?php foreach ( $amenities as $amenity ) : ?>
						<span class="ulbm-card-amenity">
							<i class="bi <?php echo esc_attr( $amenity['icon'] ); ?>" aria-hidden="true"></i>
							<?php echo esc_html( $amenity['label'] ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="ulbm-card-footer">
				<div class="ulbm-card-price">
					<?php PriceFormatter::echo_price( $base_price, $sale_price, $suffix ); ?>
				</div>
				<?php if ( $instant ) : ?>
					<span class="ulbm-card-instant"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i><?php esc_html_e( 'Instant', 'flex-booking-system' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</article>
</div>
