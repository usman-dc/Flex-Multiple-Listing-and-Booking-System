<?php
/**
 * Marketplace-style listing grid card.
 *
 * @package FlexBookingSystem
 *
 * @var int    $fbs_card_post_id Post ID.
 * @var string $fbs_card_col     Column CSS classes.
 */

use FlexBooking\Front\ListingDisplay;
use FlexBooking\Front\PriceFormatter;
use FlexBooking\Listings\ListingMeta;

defined( 'ABSPATH' ) || exit;

$post_id    = isset( $fbs_card_post_id ) ? (int) $fbs_card_post_id : get_the_ID();
$col_class  = isset( $fbs_card_col ) ? (string) $fbs_card_col : 'col-sm-6 col-lg-4';
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
<div class="<?php echo esc_attr( $col_class ); ?> fbs-grid-item">
	<article class="fbs-listing-card h-100">
		<div class="fbs-card-media">
			<a href="<?php echo esc_url( $permalink ); ?>" class="fbs-card-media-link" tabindex="-1" aria-hidden="true">
				<?php if ( $thumb_url ) : ?>
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="fbs-card-thumb" loading="lazy">
				<?php else : ?>
					<div class="fbs-card-thumb fbs-card-thumb--empty">
						<i class="bi bi-image" aria-hidden="true"></i>
					</div>
				<?php endif; ?>
			</a>
			<?php if ( $is_feat ) : ?>
				<span class="fbs-card-badge fbs-card-badge--featured"><?php esc_html_e( 'Featured', 'flex-booking-system' ); ?></span>
			<?php elseif ( $is_new ) : ?>
				<span class="fbs-card-badge fbs-card-badge--new"><?php esc_html_e( 'New', 'flex-booking-system' ); ?></span>
			<?php endif; ?>
			<button type="button" class="fbs-card-wishlist" aria-label="<?php esc_attr_e( 'Add to favorites', 'flex-booking-system' ); ?>" data-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<i class="bi bi-heart" aria-hidden="true"></i>
			</button>
		</div>
		<div class="fbs-card-body">
			<div class="fbs-card-head-row">
				<h3 class="fbs-card-title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>
				<?php if ( $grid_opts['show_rating'] && $rating_data['rating'] > 0 ) : ?>
					<?php ListingDisplay::render_grid_star_rating( $post_id ); ?>
				<?php endif; ?>
			</div>
			<?php if ( $address ) : ?>
				<p class="fbs-card-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
			<div class="fbs-card-specs">
				<?php if ( $max_guests > 0 ) : ?>
					<span><i class="bi bi-people" aria-hidden="true"></i><?php printf( esc_html__( '%d Guests', 'flex-booking-system' ), $max_guests ); ?></span>
				<?php endif; ?>
				<?php if ( $bedrooms ) : ?>
					<span><i class="bi bi-door-closed" aria-hidden="true"></i><?php echo esc_html( $bedrooms ); ?></span>
				<?php endif; ?>
				<?php if ( $bathrooms ) : ?>
					<span><i class="bi bi-droplet" aria-hidden="true"></i><?php echo esc_html( $bathrooms ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $amenities ) ) : ?>
				<div class="fbs-card-amenities">
					<?php foreach ( $amenities as $amenity ) : ?>
						<span class="fbs-card-amenity">
							<i class="bi <?php echo esc_attr( $amenity['icon'] ); ?>" aria-hidden="true"></i>
							<?php echo esc_html( $amenity['label'] ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="fbs-card-footer">
				<div class="fbs-card-price">
					<?php PriceFormatter::echo_price( $base_price, $sale_price, $suffix ); ?>
				</div>
				<?php if ( $instant ) : ?>
					<span class="fbs-card-instant"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i><?php esc_html_e( 'Instant', 'flex-booking-system' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</article>
</div>
