<?php
/**
 * Listing reviews section — approved list + submit form.
 *
 * @package FlexBookingSystem
 *
 * @var int $fbs_review_listing_id Listing post ID.
 */

use FlexBooking\Front\ListingDisplay;
use FlexBooking\Listings\ListingReviewRepository;
use FlexBooking\Listings\ListingReviewService;

defined( 'ABSPATH' ) || exit;

$post_id = isset( $fbs_review_listing_id ) ? (int) $fbs_review_listing_id : get_the_ID();
if ( $post_id < 1 || ! ListingReviewService::reviews_enabled() ) {
	return;
}

$repo     = new ListingReviewRepository();
$reviews  = $repo->get_approved_for_listing( $post_id, 20 );
$rating   = ListingDisplay::rating_data( $post_id );
$user     = wp_get_current_user();
$prefill_name  = is_user_logged_in() ? trim( $user->display_name ?: $user->user_login ) : '';
$prefill_email = is_user_logged_in() ? $user->user_email : '';
?>
<div class="fbs-section fbs-reviews-section mb-4" id="fbs-reviews">
	<h2 class="fbs-section-title"><?php esc_html_e( 'Guest Reviews', 'flex-booking-system' ); ?></h2>

	<?php if ( $rating['rating'] > 0 ) : ?>
		<div class="fbs-reviews-summary mb-4">
			<?php ListingDisplay::render_star_rating( $rating['rating'], $rating['count'] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $reviews ) ) : ?>
		<div class="fbs-reviews-list mb-4">
			<?php foreach ( $reviews as $review ) : ?>
				<article class="fbs-review-item">
					<header class="fbs-review-item-header">
						<strong class="fbs-review-author"><?php echo esc_html( (string) $review['author_name'] ); ?></strong>
						<span class="fbs-review-stars" aria-label="<?php
						printf(
							/* translators: %d: star rating 1-5 */
							esc_attr__( 'Rated %d out of 5', 'flex-booking-system' ),
							(int) $review['rating']
						);
						?>">
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<i class="bi bi-star<?php echo $i <= (int) $review['rating'] ? '-fill' : ''; ?>" aria-hidden="true"></i>
							<?php endfor; ?>
						</span>
						<time class="fbs-review-date text-muted small" datetime="<?php echo esc_attr( (string) $review['created_at'] ); ?>">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ), (string) $review['created_at'] ) ); ?>
						</time>
					</header>
					<div class="fbs-review-content">
						<?php echo wp_kses_post( nl2br( esc_html( (string) $review['content'] ) ) ); ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="text-muted mb-4"><?php esc_html_e( 'No reviews yet. Be the first to share your experience.', 'flex-booking-system' ); ?></p>
	<?php endif; ?>

	<div class="fbs-review-form-wrap">
		<h3 class="h5 fw-bold mb-3"><?php esc_html_e( 'Write a Review', 'flex-booking-system' ); ?></h3>
		<form class="fbs-review-form" data-listing-id="<?php echo esc_attr( (string) $post_id ); ?>" novalidate>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label" for="fbs-review-name-<?php echo esc_attr( (string) $post_id ); ?>"><?php esc_html_e( 'Your name', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control fbs-review-name" id="fbs-review-name-<?php echo esc_attr( (string) $post_id ); ?>" value="<?php echo esc_attr( $prefill_name ); ?>" required>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="fbs-review-email-<?php echo esc_attr( (string) $post_id ); ?>"><?php esc_html_e( 'Email', 'flex-booking-system' ); ?></label>
					<input type="email" class="form-control fbs-review-email" id="fbs-review-email-<?php echo esc_attr( (string) $post_id ); ?>" value="<?php echo esc_attr( $prefill_email ); ?>" required>
				</div>
				<div class="col-12">
					<label class="form-label" for="fbs-review-rating-<?php echo esc_attr( (string) $post_id ); ?>"><?php esc_html_e( 'Rating', 'flex-booking-system' ); ?></label>
					<select class="form-select fbs-review-rating" id="fbs-review-rating-<?php echo esc_attr( (string) $post_id ); ?>">
						<option value="5"><?php esc_html_e( '5 — Excellent', 'flex-booking-system' ); ?></option>
						<option value="4"><?php esc_html_e( '4 — Good', 'flex-booking-system' ); ?></option>
						<option value="3"><?php esc_html_e( '3 — Average', 'flex-booking-system' ); ?></option>
						<option value="2"><?php esc_html_e( '2 — Poor', 'flex-booking-system' ); ?></option>
						<option value="1"><?php esc_html_e( '1 — Terrible', 'flex-booking-system' ); ?></option>
					</select>
				</div>
				<div class="col-12">
					<label class="form-label" for="fbs-review-content-<?php echo esc_attr( (string) $post_id ); ?>"><?php esc_html_e( 'Your review', 'flex-booking-system' ); ?></label>
					<textarea class="form-control fbs-review-content" id="fbs-review-content-<?php echo esc_attr( (string) $post_id ); ?>" rows="4" required placeholder="<?php esc_attr_e( 'Tell others about your stay or experience…', 'flex-booking-system' ); ?>"></textarea>
				</div>
				<div class="col-12">
					<div class="fbs-review-feedback alert d-none" role="status"></div>
					<button type="submit" class="btn btn-primary fbs-review-submit">
						<?php esc_html_e( 'Submit Review', 'flex-booking-system' ); ?>
					</button>
				</div>
			</div>
		</form>
	</div>
</div>
