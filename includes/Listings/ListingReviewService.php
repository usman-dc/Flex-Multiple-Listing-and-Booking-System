<?php
/**
 * Business logic for listing reviews — submit, moderate, sync rating meta.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Listings;

use FlexBooking\Vendor\VendorListingService;

defined( 'ABSPATH' ) || exit;

/**
 * Listing review workflows.
 */
final class ListingReviewService {

	/**
	 * @var ListingReviewRepository
	 */
	private $repo;

	/**
	 * @param ListingReviewRepository|null $repo Repository.
	 */
	public function __construct( ListingReviewRepository $repo = null ) {
		$this->repo = $repo ?: new ListingReviewRepository();
	}

	/**
	 * Whether reviews are enabled site-wide.
	 *
	 * @return bool
	 */
	public static function reviews_enabled() {
		$raw = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
		if ( ! is_array( $raw ) ) {
			return true;
		}
		return ! isset( $raw['reviews_enabled'] ) || ! empty( $raw['reviews_enabled'] );
	}

	/**
	 * Whether new reviews publish without admin approval.
	 *
	 * @return bool
	 */
	public static function auto_approve() {
		$raw = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
		return is_array( $raw ) && ! empty( $raw['reviews_auto_approve'] );
	}

	/**
	 * Submit a review for a listing.
	 *
	 * @param int    $listing_id Listing post id.
	 * @param string $name       Author name.
	 * @param string $email      Author email.
	 * @param int    $rating     Rating 1-5.
	 * @param string $content    Review text.
	 * @return array{success: bool, message: string, review_id?: int}
	 */
	public function submit( $listing_id, $name, $email, $rating, $content ) {
		if ( ! self::reviews_enabled() ) {
			return array(
				'success' => false,
				'message' => __( 'Reviews are currently disabled.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$listing_id = (int) $listing_id;
		if ( $listing_id < 1 || ! $this->is_valid_listing( $listing_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid listing.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$name    = sanitize_text_field( $name );
		$email   = sanitize_email( $email );
		$content = sanitize_textarea_field( $content );
		$rating  = max( 1, min( 5, (int) $rating ) );

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( '' === $name ) {
				$name = trim( $user->display_name ?: $user->user_login );
			}
			if ( '' === $email ) {
				$email = $user->user_email;
			}
		}

		if ( '' === $name || '' === $email || ! is_email( $email ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter your name and a valid email.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		if ( strlen( $content ) < 10 ) {
			return array(
				'success' => false,
				'message' => __( 'Please write at least 10 characters in your review.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$status = self::auto_approve() ? 'approved' : 'pending';

		$review_id = $this->repo->insert(
			array(
				'listing_id'   => $listing_id,
				'wp_user_id'   => is_user_logged_in() ? get_current_user_id() : null,
				'author_name'  => $name,
				'author_email' => $email,
				'rating'       => $rating,
				'content'      => $content,
				'status'       => $status,
			)
		);

		if ( $review_id < 1 ) {
			$message = __( 'Could not save your review. Please try again.', 'flex-multiple-listing-and-booking-system' );
			$db_err  = ListingReviewRepository::last_db_error();
			if ( '' !== $db_err && ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
				$message .= ' (' . $db_err . ')';
			}

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		if ( 'approved' === $status ) {
			$this->sync_listing_rating( $listing_id );
		}

		$message = 'approved' === $status
			? __( 'Thank you! Your review has been published.', 'flex-multiple-listing-and-booking-system' )
			: __( 'Thank you! Your review was submitted and is awaiting approval.', 'flex-multiple-listing-and-booking-system' );

		return array(
			'success'   => true,
			'message'   => $message,
			'review_id' => $review_id,
		);
	}

	/**
	 * Approve a pending review.
	 *
	 * @param int $review_id Review id.
	 * @return bool
	 */
	public function approve( $review_id ) {
		$row = $this->repo->get_by_id( $review_id );
		if ( ! $row ) {
			return false;
		}

		$ok = $this->repo->update( $review_id, array( 'status' => 'approved' ) );
		if ( $ok ) {
			$this->sync_listing_rating( (int) $row['listing_id'] );
		}

		return $ok;
	}

	/**
	 * Reject a review.
	 *
	 * @param int $review_id Review id.
	 * @return bool
	 */
	public function reject( $review_id ) {
		$row = $this->repo->get_by_id( $review_id );
		if ( ! $row ) {
			return false;
		}

		$was_approved = 'approved' === (string) $row['status'];
		$ok           = $this->repo->update( $review_id, array( 'status' => 'rejected' ) );

		if ( $ok && $was_approved ) {
			$this->sync_listing_rating( (int) $row['listing_id'] );
		}

		return $ok;
	}

	/**
	 * Permanently delete a review.
	 *
	 * @param int $review_id Review id.
	 * @return bool
	 */
	public function delete( $review_id ) {
		$row = $this->repo->get_by_id( $review_id );
		if ( ! $row ) {
			return false;
		}

		$listing_id   = (int) $row['listing_id'];
		$was_approved = 'approved' === (string) $row['status'];
		$ok           = $this->repo->delete( $review_id );

		if ( $ok && $was_approved ) {
			$this->sync_listing_rating( $listing_id );
		}

		return $ok;
	}

	/**
	 * Update listing meta from approved reviews aggregate.
	 *
	 * @param int $listing_id Post id.
	 * @return void
	 */
	public function sync_listing_rating( $listing_id ) {
		$stats = $this->repo->approved_stats( $listing_id );
		ListingMeta::set( $listing_id, ListingMeta::KEY_RATING, (string) $stats['rating'] );
		ListingMeta::set( $listing_id, ListingMeta::KEY_REVIEW_COUNT, (string) $stats['count'] );
	}

	/**
	 * @param int $post_id Post id.
	 * @return bool
	 */
	private function is_valid_listing( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		$allowed = VendorListingService::listing_post_types();
		if ( in_array( $post->post_type, $allowed, true ) ) {
			return true;
		}

		// Fallback: any booking-type CPT slug prefix.
		return 0 === strpos( (string) $post->post_type, 'fbs_' );
	}
}
