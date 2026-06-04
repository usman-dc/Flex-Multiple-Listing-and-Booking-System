<?php
/**
 * Listing reviews section — approved list + submit form.
 *
 * @package FlexBookingSystem
 *
 * @var int $ulbm_review_listing_id Listing post ID.
 */

use FlexBooking\Front\ListingDisplay;
use FlexBooking\Listings\ListingReviewRepository;
use FlexBooking\Listings\ListingReviewService;

defined( 'ABSPATH' ) || exit;

$ulbm_post_id = isset( $ulbm_review_listing_id ) ? (int) $ulbm_review_listing_id : get_the_ID();
if ( $ulbm_post_id < 1 || ! ListingReviewService::reviews_enabled() ) {
	return;
}

$ulbm_repo          = new ListingReviewRepository();
$ulbm_reviews       = $ulbm_repo->get_approved_for_listing( $ulbm_post_id, 20 );
$ulbm_rating        = ListingDisplay::rating_data( $ulbm_post_id );
$ulbm_user          = wp_get_current_user();
$ulbm_prefill_name  = is_user_logged_in() ? trim( $ulbm_user->display_name ?: $ulbm_user->user_login ) : '';
$ulbm_prefill_email = is_user_logged_in() ? $ulbm_user->user_email : '';
?>
<div class="ulbm-section ulbm-reviews-section mb-4" id="ulbm-reviews">
	<h2 class="ulbm-section-title"><?php esc_html_e( 'Guest Reviews', 'flex-multiple-listing-and-booking-system' ); ?></h2>

	<?php if ( $ulbm_rating['rating'] > 0 ) : ?>
		<div class="ulbm-reviews-summary mb-4">
			<?php ListingDisplay::render_star_rating( $ulbm_rating['rating'], $ulbm_rating['count'] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $ulbm_reviews ) ) : ?>
		<div class="ulbm-reviews-list mb-4">
			<?php foreach ( $ulbm_reviews as $ulbm_review ) : ?>
				<article class="ulbm-review-item">
					<header class="ulbm-review-item-header">
						<strong class="ulbm-review-author"><?php echo esc_html( (string) $ulbm_review['author_name'] ); ?></strong>
						<span class="ulbm-review-stars" aria-label="<?php
						printf(
							/* translators: %d: star rating 1-5 */
							esc_attr__( 'Rated %d out of 5', 'flex-multiple-listing-and-booking-system' ),
							(int) $ulbm_review['rating']
						);
						?>">
							<?php for ( $ulbm_i = 1; $ulbm_i <= 5; $ulbm_i++ ) : ?>
								<i class="bi bi-star<?php echo $ulbm_i <= (int) $ulbm_review['rating'] ? '-fill' : ''; ?>" aria-hidden="true"></i>
							<?php endfor; ?>
						</span>
						<time class="ulbm-review-date text-muted small" datetime="<?php echo esc_attr( (string) $ulbm_review['created_at'] ); ?>">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ), (string) $ulbm_review['created_at'] ) ); ?>
						</time>
					</header>
					<div class="ulbm-review-content">
						<?php echo wp_kses_post( nl2br( esc_html( (string) $ulbm_review['content'] ) ) ); ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="text-muted mb-4"><?php esc_html_e( 'No reviews yet. Be the first to share your experience.', 'flex-multiple-listing-and-booking-system' ); ?></p>
	<?php endif; ?>

	<div class="ulbm-review-form-wrap">
		<h3 class="h5 fw-bold mb-3"><?php esc_html_e( 'Write a Review', 'flex-multiple-listing-and-booking-system' ); ?></h3>
		<form class="ulbm-review-form" data-listing-id="<?php echo esc_attr( (string) $ulbm_post_id ); ?>" novalidate>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label" for="ulbm-review-name-<?php echo esc_attr( (string) $ulbm_post_id ); ?>"><?php esc_html_e( 'Your name', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control ulbm-review-name" id="ulbm-review-name-<?php echo esc_attr( (string) $ulbm_post_id ); ?>" value="<?php echo esc_attr( $ulbm_prefill_name ); ?>" required>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="ulbm-review-email-<?php echo esc_attr( (string) $ulbm_post_id ); ?>"><?php esc_html_e( 'Email', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="email" class="form-control ulbm-review-email" id="ulbm-review-email-<?php echo esc_attr( (string) $ulbm_post_id ); ?>" value="<?php echo esc_attr( $ulbm_prefill_email ); ?>" required>
				</div>
				<div class="col-12">
					<label class="form-label" for="ulbm-review-rating-<?php echo esc_attr( (string) $ulbm_post_id ); ?>"><?php esc_html_e( 'Rating', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<select class="form-select ulbm-review-rating" id="ulbm-review-rating-<?php echo esc_attr( (string) $ulbm_post_id ); ?>">
						<option value="5"><?php esc_html_e( '5 — Excellent', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="4"><?php esc_html_e( '4 — Good', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="3"><?php esc_html_e( '3 — Average', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="2"><?php esc_html_e( '2 — Poor', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="1"><?php esc_html_e( '1 — Terrible', 'flex-multiple-listing-and-booking-system' ); ?></option>
					</select>
				</div>
				<div class="col-12">
					<label class="form-label" for="ulbm-review-content-<?php echo esc_attr( (string) $ulbm_post_id ); ?>"><?php esc_html_e( 'Your review', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<textarea class="form-control ulbm-review-content" id="ulbm-review-content-<?php echo esc_attr( (string) $ulbm_post_id ); ?>" rows="4" required placeholder="<?php esc_attr_e( 'Tell others about your stay or experience…', 'flex-multiple-listing-and-booking-system' ); ?>"></textarea>
				</div>
				<div class="col-12">
					<div class="ulbm-review-feedback alert d-none" role="status"></div>
					<button type="submit" class="btn btn-primary ulbm-review-submit">
						<?php esc_html_e( 'Submit Review', 'flex-multiple-listing-and-booking-system' ); ?>
					</button>
				</div>
			</div>
		</form>
	</div>
</div>
