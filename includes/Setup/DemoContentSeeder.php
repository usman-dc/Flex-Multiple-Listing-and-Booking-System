<?php
/**
 * One-click demo listing generator for all booking type CPTs.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Setup;

use FlexBooking\Listings\ListingMeta;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Creates demo posts with full listing meta for preview / testing.
 */
final class DemoContentSeeder {

	public const DEMO_META_KEY   = '_ulbm_is_demo';
	public const POOL_OPTION_KEY = 'ulbm_demo_attachment_pool';
	public const POSTS_PER_TYPE  = 20;

	/**
	 * Count demo posts for a booking type CPT.
	 *
	 * @param string $cpt Post type slug.
	 * @return int
	 */
	public static function count_demo_posts( $cpt ) {
		$query = new \WP_Query(
			array(
				'post_type'      => $cpt,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Demo marker lookup during setup only.
				'meta_key'       => self::DEMO_META_KEY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => '1',
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Import demo listings for one booking type.
	 *
	 * @param int $booking_type_id Booking type row ID.
	 * @param int $count           Posts to create.
	 * @return array{created:int,skipped:int,type_name:string,cpt:string}
	 */
	public static function import_for_type( $booking_type_id, $count = self::POSTS_PER_TYPE ) {
		$booking_type_id = absint( $booking_type_id );
		$count           = max( 1, min( 50, absint( $count ) ) );

		$type_row = self::get_type_row( $booking_type_id );
		if ( ! $type_row ) {
			return array(
				'created'    => 0,
				'skipped'    => 0,
				'type_name'  => '',
				'cpt'        => '',
				'error'      => __( 'Booking type not found.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$cpt = BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $type_row['slug'] );
		if ( ! post_type_exists( $cpt ) ) {
			return array(
				'created'    => 0,
				'skipped'    => 0,
				'type_name'  => (string) $type_row['name'],
				'cpt'        => $cpt,
				'error'      => __( 'Post type is not registered yet. Save permalinks or reload admin.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return array(
				'created'    => 0,
				'skipped'    => 0,
				'type_name'  => (string) $type_row['name'],
				'cpt'        => $cpt,
				'error'      => __( 'Permission denied.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Batch demo import may exceed default PHP timeout.
			set_time_limit( 300 );
		}

		$pool    = self::ensure_attachment_pool();
		$slug    = (string) $type_row['slug'];
		$created = 0;
		$skipped = 0;

		for ( $i = 1; $i <= $count; $i++ ) {
			$seed   = self::listing_seed( $slug, (string) $type_row['name'], $i );
			$post_id = wp_insert_post(
				array(
					'post_type'    => $cpt,
					'post_status'  => 'publish',
					'post_title'   => $seed['title'],
					'post_content' => $seed['content'],
					'post_excerpt' => $seed['excerpt'],
				),
				true
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				++$skipped;
				continue;
			}

			update_post_meta( (int) $post_id, self::DEMO_META_KEY, '1' );
			self::apply_listing_meta( (int) $post_id, $booking_type_id, $seed, $pool );
			++$created;
		}

		return array(
			'created'   => $created,
			'skipped'   => $skipped,
			'type_name' => (string) $type_row['name'],
			'cpt'       => $cpt,
		);
	}

	/**
	 * Delete all demo posts (optionally for one booking type).
	 *
	 * @param int $booking_type_id 0 = all types.
	 * @return int Number deleted.
	 */
	public static function delete_demo( $booking_type_id = 0 ) {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return 0;
		}

		$post_types = array();
		if ( $booking_type_id > 0 ) {
			$row = self::get_type_row( $booking_type_id );
			if ( $row ) {
				$post_types[] = BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $row['slug'] );
			}
		} else {
			foreach ( BookingTypePostTypeRegistry::get_registered_types() as $t ) {
				$post_types[] = BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $t['slug'] );
			}
		}

		if ( empty( $post_types ) ) {
			return 0;
		}

		$query = new \WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Delete only plugin-seeded demo posts.
				'meta_key'       => self::DEMO_META_KEY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => '1',
			)
		);

		$deleted = 0;
		foreach ( $query->posts as $pid ) {
			if ( wp_delete_post( (int) $pid, true ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * @param int $id Booking type ID.
	 * @return array<string,mixed>|null
	 */
	private static function get_type_row( $id ) {
		$repo = new \FlexBooking\Booking\BookingTypeRepository();
		$row  = $repo->get_by_id( $id );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Ensure shared placeholder attachments exist.
	 *
	 * @return int[] Attachment IDs.
	 */
	private static function ensure_attachment_pool() {
		$stored = get_option( self::POOL_OPTION_KEY, array() );
		if ( is_array( $stored ) && count( $stored ) >= 6 ) {
			$valid = array();
			foreach ( $stored as $aid ) {
				$aid = absint( $aid );
				if ( $aid && wp_attachment_is_image( $aid ) ) {
					$valid[] = $aid;
				}
			}
			if ( count( $valid ) >= 6 ) {
				return $valid;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$pool = array();
		$seeds = array( 101, 102, 103, 104, 105, 106, 107, 108 );
		foreach ( $seeds as $seed ) {
			$url = 'https://picsum.photos/seed/ulbm' . $seed . '/1200/800.jpg';
			$id  = self::sideload_image( $url, 'ulbm-demo-' . $seed );
			if ( $id ) {
				$pool[] = $id;
			}
		}

		if ( ! empty( $pool ) ) {
			update_option( self::POOL_OPTION_KEY, $pool, false );
		}

		return $pool;
	}

	/**
	 * @param string $url     Remote image URL.
	 * @param string $filename Base filename.
	 * @return int Attachment ID or 0.
	 */
	private static function sideload_image( $url, $filename ) {
		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}

		$file_array = array(
			'name'     => sanitize_file_name( $filename . '.jpg' ),
			'tmp_name' => $tmp,
		);

		$id = media_handle_sideload( $file_array, 0 );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return 0;
		}

		return (int) $id;
	}

	/**
	 * @param int                  $post_id Post ID.
	 * @param int                  $type_id Booking type ID.
	 * @param array<string,mixed>  $seed    Content seed.
	 * @param int[]                $pool    Attachment IDs.
	 */
	private static function apply_listing_meta( $post_id, $type_id, array $seed, array $pool ) {
		if ( empty( $pool ) ) {
			return;
		}

		$featured = $pool[ array_rand( $pool ) ];
		set_post_thumbnail( $post_id, $featured );

		$gallery = array();
		$shuffled = $pool;
		shuffle( $shuffled );
		foreach ( array_slice( $shuffled, 0, min( 4, count( $shuffled ) ) ) as $gid ) {
			if ( (int) $gid !== (int) $featured ) {
				$gallery[] = (int) $gid;
			}
		}
		if ( empty( $gallery ) ) {
			$gallery[] = (int) $featured;
		}

		ListingMeta::set( $post_id, ListingMeta::KEY_BOOKING_TYPE_ID, $type_id );
		ListingMeta::set( $post_id, ListingMeta::KEY_BASE_PRICE, (string) $seed['base_price'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_SALE_PRICE, $seed['sale_price'] ? (string) $seed['sale_price'] : '' );
		ListingMeta::set( $post_id, ListingMeta::KEY_PRICE_SUFFIX, (string) $seed['price_suffix'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_BOOKING_MODE, (string) $seed['booking_mode'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_MIN_BOOKING, (int) $seed['min_booking'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_MAX_BOOKING, (int) $seed['max_booking'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_MAX_GUESTS, (int) $seed['max_guests'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_GALLERY, $gallery );
		ListingMeta::set( $post_id, ListingMeta::KEY_ADDRESS, (string) $seed['address'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_LATITUDE, (string) $seed['latitude'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_LONGITUDE, (string) $seed['longitude'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_MAP_ZOOM, 14 );
		ListingMeta::set( $post_id, ListingMeta::KEY_FEATURES, $seed['features'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_FAQ, $seed['faq'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_EXTRA_SERVICES, $seed['services'] );
		ListingMeta::set( $post_id, ListingMeta::KEY_CONTACT_EMAIL, 'demo@flexbooking.local' );
		ListingMeta::set( $post_id, ListingMeta::KEY_CONTACT_PHONE, '+1 (555) 010-' . str_pad( (string) ( $post_id % 100 ), 2, '0', STR_PAD_LEFT ) );
		ListingMeta::set( $post_id, ListingMeta::KEY_CHECK_IN_TIME, '14:00' );
		ListingMeta::set( $post_id, ListingMeta::KEY_CHECK_OUT_TIME, '11:00' );
		ListingMeta::set( $post_id, ListingMeta::KEY_INSTANT_BOOKING, '1' );
		ListingMeta::set( $post_id, ListingMeta::KEY_DEPOSIT_PERCENT, 20 );
		ListingMeta::set( $post_id, ListingMeta::KEY_CANCELLATION_DAYS, 3 );
		ListingMeta::set( $post_id, ListingMeta::KEY_VIDEO_URL, '' );
		ListingMeta::set( $post_id, ListingMeta::KEY_RATING, number_format( 4.5 + ( wp_rand( 0, 5 ) / 10 ), 1 ) );
		ListingMeta::set( $post_id, ListingMeta::KEY_REVIEW_COUNT, wp_rand( 8, 48 ) );
	}

	/**
	 * Build listing seed data for one demo post.
	 *
	 * @param string $slug      Booking type slug.
	 * @param string $type_name Booking type label.
	 * @param int    $index     1-based index.
	 * @return array<string,mixed>
	 */
	private static function listing_seed( $slug, $type_name, $index ) {
		$templates = self::templates_for_slug( $slug, $type_name );
		$titles      = $templates['titles'];
		$title       = $titles[ ( $index - 1 ) % count( $titles ) ] . ' #' . $index;
		$base_price  = 49 + ( $index * 7 ) + ( strlen( $slug ) % 5 ) * 10;
		$sale_price  = ( 0 === $index % 4 ) ? max( 29, $base_price - 15 ) : 0;
		$cities      = array( 'New York', 'London', 'Dubai', 'Paris', 'Tokyo', 'Sydney', 'Barcelona', 'Rome' );
		$city        = $cities[ ( $index - 1 ) % count( $cities ) ];
		$coords      = array(
			array( '40.7128', '-74.0060' ),
			array( '51.5074', '-0.1278' ),
			array( '25.2048', '55.2708' ),
			array( '48.8566', '2.3522' ),
			array( '35.6762', '139.6503' ),
			array( '-33.8688', '151.2093' ),
			array( '41.3874', '2.1686' ),
			array( '41.9028', '12.4964' ),
		);
		$coord       = $coords[ ( $index - 1 ) % count( $coords ) ];

		return array(
			'title'         => $title,
			'content'       => sprintf(
				/* translators: 1: listing title, 2: booking type name, 3: city */
				__( 'This is demo content for <strong>%1$s</strong> — a sample %2$s listing in %3$s. Use it to preview your grid, single page layout, filters, and booking form before adding real listings.', 'flex-multiple-listing-and-booking-system' ),
				$title,
				strtolower( $type_name ),
				$city
			),
			'excerpt'       => sprintf(
				/* translators: 1: booking type name, 2: city */
				__( 'Demo %1$s in %2$s with full pricing, gallery, FAQ, and amenities.', 'flex-multiple-listing-and-booking-system' ),
				strtolower( $type_name ),
				$city
			),
			'base_price'    => $base_price,
			'sale_price'    => $sale_price,
			'price_suffix'  => $templates['price_suffix'],
			'booking_mode'  => $templates['booking_mode'],
			'min_booking'   => $templates['min_booking'],
			'max_booking'   => $templates['max_booking'],
			'max_guests'    => $templates['max_guests'],
			'address'       => sprintf( '%d Demo Street, %s', 100 + $index, $city ),
			'latitude'      => $coord[0],
			'longitude'     => $coord[1],
			'features'      => $templates['features'],
			'faq'           => $templates['faq'],
			'services'      => $templates['services'],
		);
	}

	/**
	 * Industry-aware content templates.
	 *
	 * @param string $slug      Type slug.
	 * @param string $type_name Type name.
	 * @return array<string,mixed>
	 */
	private static function templates_for_slug( $slug, $type_name ) {
		$key = sanitize_key( $slug );
		$map = array(
			'car-rental'   => array(
				'titles'       => array( 'Compact City Car', 'SUV Family Pack', 'Luxury Sedan', 'Electric Hatchback', 'Convertible Weekend', 'Van for Groups', 'Hybrid Economy', 'Sports Coupe', '4x4 Adventure', 'Premium Executive' ),
				'price_suffix' => '/day',
				'booking_mode' => 'daily',
				'min_booking'  => 1,
				'max_booking'  => 14,
				'max_guests'   => 5,
				'features'     => self::features(
					array(
						array( 'bi-car-front', 'Automatic', 'Yes' ),
						array( 'bi-fuel-pump', 'Fuel', 'Petrol' ),
						array( 'bi-snow', 'A/C', 'Included' ),
						array( 'bi-shield-check', 'Insurance', 'Basic' ),
						array( 'bi-geo-alt', 'GPS', 'Optional' ),
					)
				),
				'faq'          => self::faq(
					array(
						array( 'What documents are required?', 'A valid driving license and credit card are required at pickup.' ),
						array( 'Is fuel included?', 'Vehicle is provided with a full tank; return full or pay refill fee.' ),
						array( 'Can I add a second driver?', 'Yes, additional drivers can be added during booking.' ),
					)
				),
				'services'     => self::services(
					array(
						array( 'Airport pickup', 25, 'booking' ),
						array( 'Child seat', 8, 'night' ),
						array( 'Full insurance', 15, 'night' ),
					)
				),
			),
			'hotel-stays'  => array(
				'titles'       => array( 'Beachfront Luxury Villa', 'Oceanview Villa with Pool', 'Deluxe King Room', 'Ocean View Suite', 'Garden Villa', 'Penthouse Loft', 'Family Apartment', 'Boutique Double', 'Executive Floor', 'Cityscape Room' ),
				'price_suffix' => '/night',
				'booking_mode' => 'daily',
				'min_booking'  => 1,
				'max_booking'  => 30,
				'max_guests'   => 6,
				'features'     => self::features(
					array(
						array( 'bi-wifi', 'Free Wi-Fi', '' ),
						array( 'bi-snow', 'Air Conditioning', '' ),
						array( 'bi-water', 'Infinity Pool', '' ),
						array( 'bi-binoculars', 'Ocean View', '' ),
						array( 'bi-cup-hot', 'Kitchen', '' ),
						array( 'bi-car-front', 'Free Parking', '' ),
						array( 'bi-door-closed', '3 Bedrooms', '3 Bedrooms' ),
						array( 'bi-droplet', '3 Bathrooms', '3 Bathrooms' ),
						array( 'bi-arrows-angle-expand', '250 m² Size', '250 m²' ),
					)
				),
				'faq'          => self::faq(
					array(
						array( 'What time is check-in?', 'Check-in from 2:00 PM; early check-in on request.' ),
						array( 'Is breakfast included?', 'Yes, continental breakfast is included for all guests.' ),
						array( 'Do you allow pets?', 'Small pets allowed in selected rooms with prior notice.' ),
					)
				),
				'services'     => self::services(
					array(
						array( 'Cleaning Fee', 80, 'booking' ),
						array( 'Service Fee', 45, 'booking' ),
						array( 'Airport shuttle', 45, 'booking' ),
					)
				),
			),
			'events'       => array(
				'titles'       => array( 'VIP Concert Pass', 'Conference Day Ticket', 'Workshop Seat', 'Festival Entry', 'Theatre Premium', 'Summit Access', 'Gala Dinner Ticket', 'Expo Pass', 'Masterclass Slot', 'Networking Event' ),
				'price_suffix' => '/ticket',
				'booking_mode' => 'time_slot',
				'min_booking'  => 1,
				'max_booking'  => 10,
				'max_guests'   => 1,
				'features'     => self::features(
					array(
						array( 'bi-ticket-perforated', 'Entry', '1 person' ),
						array( 'bi-mic', 'Live show', 'Yes' ),
						array( 'bi-cup-straw', 'Refreshments', 'Included' ),
						array( 'bi-parking', 'Parking', 'Nearby' ),
					)
				),
				'faq'          => self::faq(
					array(
						array( 'Are tickets refundable?', 'Free cancellation up to 48 hours before the event.' ),
						array( 'Can I transfer my ticket?', 'Tickets are transferable via your booking reference.' ),
					)
				),
				'services'     => self::services(
					array(
						array( 'VIP lounge access', 50, 'booking' ),
						array( 'Meet & greet', 120, 'booking' ),
					)
				),
			),
			'appointments' => array(
				'titles'       => array( 'Consultation Session', 'Hair Styling', 'Dental Checkup', 'Legal Advice', 'Coaching Call', 'Therapy Session', 'Fitness Assessment', 'Beauty Treatment', 'Tax Consult', 'Wellness Visit' ),
				'price_suffix' => '/session',
				'booking_mode' => 'hourly',
				'min_booking'  => 1,
				'max_booking'  => 3,
				'max_guests'   => 1,
				'features'     => self::features(
					array(
						array( 'bi-clock', 'Duration', '60 min' ),
						array( 'bi-person-check', 'Professional', 'Certified' ),
						array( 'bi-camera-video', 'Online option', 'Available' ),
					)
				),
				'faq'          => self::faq(
					array(
						array( 'How do I reschedule?', 'Reschedule free up to 24 hours before your appointment.' ),
						array( 'Is online booking available?', 'Yes, choose video call during checkout.' ),
					)
				),
				'services'     => self::services(
					array(
						array( 'Extended session', 40, 'booking' ),
						array( 'Follow-up call', 25, 'booking' ),
					)
				),
			),
			'tours'        => array(
				'titles'       => array( 'City Walking Tour', 'Sunset Cruise', 'Mountain Hike', 'Food Tasting Tour', 'Museum Guided Visit', 'Desert Safari', 'Wine Country Trip', 'Historical Walk', 'Snorkeling Day', 'Cultural Experience' ),
				'price_suffix' => '/person',
				'booking_mode' => 'daily',
				'min_booking'  => 1,
				'max_booking'  => 7,
				'max_guests'   => 12,
				'features'     => self::features(
					array(
						array( 'bi-person-badge', 'Guide', 'Licensed' ),
						array( 'bi-translate', 'Languages', 'EN / ES' ),
						array( 'bi-camera', 'Photos', 'Allowed' ),
						array( 'bi-bus-front', 'Transport', 'Included' ),
					)
				),
				'faq'          => self::faq(
					array(
						array( 'What should I bring?', 'Comfortable shoes, water, and sun protection recommended.' ),
						array( 'Is the tour weather dependent?', 'Tours run in most weather; severe conditions may reschedule.' ),
					)
				),
				'services'     => self::services(
					array(
						array( 'Private guide', 90, 'booking' ),
						array( 'Lunch package', 20, 'guest' ),
					)
				),
			),
		);

		if ( isset( $map[ $key ] ) ) {
			return $map[ $key ];
		}

		// Generic fallback for custom booking types.
		return array(
			'titles'       => array(
				'Premium ' . $type_name,
				'Standard ' . $type_name,
				'Deluxe ' . $type_name,
				'Classic ' . $type_name,
				'Elite ' . $type_name,
			),
			'price_suffix' => '/booking',
			'booking_mode' => 'daily',
			'min_booking'  => 1,
			'max_booking'  => 14,
			'max_guests'   => 4,
			'features'     => self::features(
				array(
					array( 'bi-check-circle', 'Verified', 'Yes' ),
					array( 'bi-star', 'Rating', '4.8' ),
					array( 'bi-headset', 'Support', '24/7' ),
					array( 'bi-shield-check', 'Secure booking', 'Yes' ),
				)
			),
			'faq'          => self::faq(
				array(
					array( 'Is this demo content?', 'Yes — generated for preview. Replace with your real listings anytime.' ),
					array( 'How do I book?', 'Use the booking form on the right side of the listing page.' ),
					array( 'Can I delete demo posts?', 'Yes, from Flex Listings & Booking → Settings → Demo Content.' ),
				)
			),
			'services'     => self::services(
				array(
					array( 'Priority support', 15, 'booking' ),
					array( 'Extra option', 10, 'guest' ),
				)
			),
		);
	}

	/**
	 * @param array<int,array{0:string,1:string,2:string}> $rows Icon, label, value.
	 * @return array<int,array{icon:string,label:string,value:string}>
	 */
	private static function features( array $rows ) {
		$out = array();
		foreach ( $rows as $r ) {
			$out[] = array(
				'icon'  => $r[0],
				'label' => $r[1],
				'value' => $r[2],
			);
		}
		return $out;
	}

	/**
	 * @param array<int,array{0:string,1:string}> $rows Question, answer.
	 * @return array<int,array{question:string,answer:string}>
	 */
	private static function faq( array $rows ) {
		$out = array();
		foreach ( $rows as $r ) {
			$out[] = array(
				'question' => $r[0],
				'answer'   => $r[1],
			);
		}
		return $out;
	}

	/**
	 * @param array<int,array{0:string,1:float|int,2:string}> $rows Name, price, per.
	 * @return array<int,array{name:string,price:float,per:string,required:bool}>
	 */
	private static function services( array $rows ) {
		$out = array();
		foreach ( $rows as $r ) {
			$out[] = array(
				'name'     => $r[0],
				'price'    => (float) $r[1],
				'per'      => $r[2],
				'required' => false,
			);
		}
		return $out;
	}
}
