<?php
/**
 * Vendor listing CRUD on frontend.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

use FlexBooking\Listings\ListingMeta;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Create and manage partner-owned listings.
 */
final class VendorListingService {

	/**
	 * All fbs_* CPT slugs for booking types.
	 *
	 * @return string[]
	 */
	public static function listing_post_types() {
		$types = array();
		foreach ( BookingTypePostTypeRegistry::get_registered_types() as $t ) {
			$types[] = BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $t['slug'] );
		}
		return array_values( array_filter( $types, 'post_type_exists' ) );
	}

	/**
	 * Get listings owned by user.
	 *
	 * @param int $user_id User ID.
	 * @return \WP_Post[]
	 */
	public static function get_listings( $user_id ) {
		$post_types = self::listing_post_types();
		if ( empty( $post_types ) ) {
			return array();
		}

		$query = new \WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'author'         => absint( $user_id ),
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return $query->posts;
	}

	/**
	 * Whether user owns the listing post.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function user_owns_listing( $user_id, $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return in_array( $post->post_type, self::listing_post_types(), true );
		}
		return (int) $post->post_author === (int) $user_id
			&& in_array( $post->post_type, self::listing_post_types(), true );
	}

	/**
	 * Create or update a listing.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<string,mixed>  $data    Input.
	 * @return array{success:bool,post_id?:int,message?:string}
	 */
	public static function save_listing( $user_id, array $data ) {
		if ( ! VendorRole::can_manage_listings( $user_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to manage listings.', 'flex-booking-system' ),
			);
		}

		$repo = new VendorRepository();
		if ( ! $repo->is_approved( $user_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Your partner account is pending approval.', 'flex-booking-system' ),
			);
		}

		$post_id         = isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;
		$booking_type_id = isset( $data['booking_type_id'] ) ? absint( $data['booking_type_id'] ) : 0;
		$title           = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$content         = isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '';
		$base_price      = isset( $data['base_price'] ) ? sanitize_text_field( (string) $data['base_price'] ) : '';
		$address         = isset( $data['address'] ) ? sanitize_text_field( $data['address'] ) : '';
		$max_guests      = isset( $data['max_guests'] ) ? absint( $data['max_guests'] ) : 1;

		if ( '' === $title ) {
			return array(
				'success' => false,
				'message' => __( 'Title is required.', 'flex-booking-system' ),
			);
		}

		$cpt = self::cpt_for_booking_type( $booking_type_id );
		if ( ! $cpt ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid listing type.', 'flex-booking-system' ),
			);
		}

		$settings     = VendorPages::settings();
		$post_status  = ! empty( $settings['vendor_auto_publish'] ) ? 'publish' : 'pending';

		if ( $post_id > 0 ) {
			if ( ! self::user_owns_listing( $user_id, $post_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'Listing not found.', 'flex-booking-system' ),
				);
			}
			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_title'   => $title,
					'post_content' => $content,
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'message' => $result->get_error_message(),
				);
			}
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'    => $cpt,
					'post_status'  => $post_status,
					'post_title'   => $title,
					'post_content' => $content,
					'post_author'  => absint( $user_id ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return array(
					'success' => false,
					'message' => $post_id->get_error_message(),
				);
			}
		}

		ListingMeta::set( (int) $post_id, ListingMeta::KEY_BOOKING_TYPE_ID, $booking_type_id );
		ListingMeta::set( (int) $post_id, ListingMeta::KEY_BASE_PRICE, $base_price );
		ListingMeta::set( (int) $post_id, ListingMeta::KEY_ADDRESS, $address );
		ListingMeta::set( (int) $post_id, ListingMeta::KEY_MAX_GUESTS, max( 1, $max_guests ) );
		ListingMeta::set( (int) $post_id, ListingMeta::KEY_PRICE_SUFFIX, '/night' );
		ListingMeta::set( (int) $post_id, ListingMeta::KEY_BOOKING_MODE, 'daily' );

		if ( ! empty( $data['attachment_id'] ) ) {
			set_post_thumbnail( (int) $post_id, absint( $data['attachment_id'] ) );
		}

		return array(
			'success' => true,
			'post_id' => (int) $post_id,
			'message' => $post_id > 0 && ! empty( $data['post_id'] ) && absint( $data['post_id'] ) > 0
				? __( 'Listing updated.', 'flex-booking-system' )
				: __( 'Listing submitted successfully.', 'flex-booking-system' ),
		);
	}

	/**
	 * Delete vendor listing.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return array{success:bool,message?:string}
	 */
	public static function delete_listing( $user_id, $post_id ) {
		if ( ! self::user_owns_listing( $user_id, $post_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Listing not found.', 'flex-booking-system' ),
			);
		}
		$deleted = wp_delete_post( absint( $post_id ), true );
		if ( ! $deleted ) {
			return array(
				'success' => false,
				'message' => __( 'Could not delete listing.', 'flex-booking-system' ),
			);
		}
		return array(
			'success' => true,
			'message' => __( 'Listing deleted.', 'flex-booking-system' ),
		);
	}

	/**
	 * Count bookings on vendor listings.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public static function count_vendor_bookings( $user_id ) {
		global $wpdb;

		$listings = self::get_listings( $user_id );
		if ( empty( $listings ) ) {
			return 0;
		}

		$ids = wp_list_pluck( $listings, 'ID' );
		$meta_table = \FlexBooking\Database\Schema::tables()['booking_meta'];
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder
		$sql = "SELECT COUNT(DISTINCT booking_id) FROM `{$meta_table}` WHERE meta_key = 'listing_id' AND meta_value IN ($placeholders)";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $ids ) );
	}

	/**
	 * Get bookings for vendor listings.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Max rows.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_vendor_bookings( $user_id, $limit = 20 ) {
		global $wpdb;

		$listings = self::get_listings( $user_id );
		if ( empty( $listings ) ) {
			return array();
		}

		$ids = array_map( 'absint', wp_list_pluck( $listings, 'ID' ) );
		$meta_table     = \FlexBooking\Database\Schema::tables()['booking_meta'];
		$bookings_table = \FlexBooking\Database\Schema::tables()['bookings'];
		$placeholders   = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT b.* FROM `{$bookings_table}` b
			INNER JOIN `{$meta_table}` m ON m.booking_id = b.id
			WHERE m.meta_key = 'listing_id' AND m.meta_value IN ($placeholders)
			ORDER BY b.created_at DESC
			LIMIT %d";

		$args = array_merge( $ids, array( absint( $limit ) ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Resolve CPT from booking type ID.
	 *
	 * @param int $booking_type_id Type ID.
	 * @return string Empty if not found.
	 */
	private static function cpt_for_booking_type( $booking_type_id ) {
		$repo = new \FlexBooking\Booking\BookingTypeRepository();
		$row  = $repo->get_by_id( absint( $booking_type_id ) );
		if ( ! $row ) {
			return '';
		}
		$cpt = BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $row['slug'] );
		return post_type_exists( $cpt ) ? $cpt : '';
	}
}
