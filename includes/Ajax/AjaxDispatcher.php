<?php
/**
 * Admin/public AJAX entrypoints — nonce verified; delegates to handlers.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Ajax;

use FlexBooking\Booking\BookingAdminUpdater;
use FlexBooking\Core\Capabilities;
use FlexBooking\Core\Plugin;
use FlexBooking\Front\ListingDisplay;
use FlexBooking\Front\PriceFormatter;
use FlexBooking\Listings\ListingMeta;
use FlexBooking\Listings\ListingReviewService;
use FlexBooking\Security\Nonce;
use FlexBooking\Security\PostData;
use FlexBooking\Setup\DemoContentSeeder;
use FlexBooking\Setup\IndustryProvisioner;
use FlexBooking\Vendor\VendorAuth;
use FlexBooking\Vendor\VendorListingService;
use FlexBooking\Vendor\VendorPageProvisioner;
use FlexBooking\Vendor\VendorRole;

defined( 'ABSPATH' ) || exit;

/**
 * Registers wp_ajax_* hooks.
 */
final class AjaxDispatcher {

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Kernel.
	 */
	public function __construct( Plugin $plugin ) {
		add_action( 'wp_ajax_ulbm_admin_ping', array( $this, 'admin_ping' ) );
		add_action( 'wp_ajax_ulbm_setup_complete', array( $this, 'setup_complete' ) );
		add_action( 'wp_ajax_ulbm_setup_finish', array( $this, 'setup_finish' ) );
		add_action( 'wp_ajax_ulbm_booking_update', array( $this, 'booking_update' ) );
		add_action( 'wp_ajax_nopriv_ulbm_public_search', array( $this, 'public_search' ) );
		add_action( 'wp_ajax_ulbm_public_search', array( $this, 'public_search' ) );

		add_action( 'wp_ajax_nopriv_ulbm_create_booking', array( $this, 'create_booking' ) );
		add_action( 'wp_ajax_ulbm_create_booking', array( $this, 'create_booking' ) );

		add_action( 'wp_ajax_nopriv_ulbm_grid_filter', array( $this, 'grid_filter' ) );
		add_action( 'wp_ajax_ulbm_grid_filter', array( $this, 'grid_filter' ) );

		add_action( 'wp_ajax_ulbm_import_demo_content', array( $this, 'import_demo_content' ) );
		add_action( 'wp_ajax_ulbm_delete_demo_content', array( $this, 'delete_demo_content' ) );

		add_action( 'wp_ajax_nopriv_ulbm_vendor_register', array( $this, 'vendor_register' ) );
		add_action( 'wp_ajax_ulbm_vendor_register', array( $this, 'vendor_register' ) );
		add_action( 'wp_ajax_nopriv_ulbm_vendor_login', array( $this, 'vendor_login' ) );
		add_action( 'wp_ajax_ulbm_vendor_login', array( $this, 'vendor_login' ) );
		add_action( 'wp_ajax_ulbm_vendor_become_partner', array( $this, 'vendor_become_partner' ) );
		add_action( 'wp_ajax_ulbm_vendor_save_listing', array( $this, 'vendor_save_listing' ) );
		add_action( 'wp_ajax_ulbm_vendor_delete_listing', array( $this, 'vendor_delete_listing' ) );
		add_action( 'wp_ajax_ulbm_provision_vendor_pages', array( $this, 'provision_vendor_pages' ) );

		add_action( 'wp_ajax_nopriv_ulbm_submit_review', array( $this, 'submit_review' ) );
		add_action( 'wp_ajax_ulbm_submit_review', array( $this, 'submit_review' ) );
		add_action( 'wp_ajax_ulbm_review_moderate', array( $this, 'review_moderate' ) );

		do_action( 'ulbm_register_ajax_actions', $plugin );
	}

	/**
	 * Lightweight connectivity check from admin bundle.
	 *
	 * @return void
	 */
	public function admin_ping() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		wp_send_json_success( array( 'ok' => true, 'time' => time() ) );
	}

	/**
	 * Mark wizard complete.
	 *
	 * @return void
	 */
	public function setup_complete() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		update_option( 'ulbm_setup_completed', true, false );
		wp_send_json_success( array( 'completed' => true ) );
	}

	/**
	 * Save selected industries, provision booking types + CPTs, mark setup complete.
	 *
	 * @return void
	 */
	public function setup_finish() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		PostData::allow_processing();
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$raw = PostData::array( 'industries' );

		$summary = IndustryProvisioner::provision( $raw );
		update_option( 'ulbm_setup_completed', true, false );

		wp_send_json_success(
			array(
				'completed' => true,
				'summary'   => $summary,
				'redirect'  => admin_url( 'admin.php?page=ulbm-dashboard' ),
			)
		);
	}

	/**
	 * Update booking status and/or payment from Bookings admin screen.
	 *
	 * @return void
	 */
	public function booking_update() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		PostData::allow_processing();
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$booking_id = PostData::int( 'booking_id' );
		if ( $booking_id < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking.', 'flex-multiple-listing-and-booking-system' ) ), 400 );
		}

		$status_in = PostData::has( 'status' ) ? PostData::raw( 'status' ) : null;
		$pay_in    = PostData::has( 'payment_status' ) ? PostData::raw( 'payment_status' ) : null;

		$new_status = null;
		if ( null !== $status_in && '' !== $status_in ) {
			$new_status = sanitize_key( (string) $status_in );
		}

		$new_payment = null;
		if ( null !== $pay_in && '' !== $pay_in ) {
			$new_payment = sanitize_key( (string) $pay_in );
		}

		$send = ! PostData::has( 'send_notification' ) || '0' !== (string) PostData::raw( 'send_notification' );

		$result = BookingAdminUpdater::update( $booking_id, $new_status, $new_payment, $send );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			$code = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
			wp_send_json_error(
				array( 'message' => $result->get_error_message() ),
				$code
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * Public search stub — replace with repository query + caching layer.
	 *
	 * @return void
	 */
	public function public_search() {
		check_ajax_referer( 'wp_rest', 'nonce' );
		PostData::allow_processing();

		$query = PostData::string( 'q' );

		wp_send_json_success(
			array(
				'query'   => $query,
				'results' => apply_filters( 'ulbm_ajax_search_results', array(), $query ),
			)
		);
	}

	/**
	 * Guest or logged-in booking submission via AJAX (REST remains for authenticated integrations).
	 *
	 * @return void
	 */
	public function create_booking() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		PostData::allow_processing();

		$allowed = apply_filters( 'ulbm_allow_ajax_guest_booking', true );
		if ( ! $allowed && ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 401 );
		}

		$plugin = Plugin::instance();
		$engine = $plugin->container()->get( 'booking.engine' );

		$form_values = array();
		$dec = \FlexBooking\Security\JsonInput::decode_post_array( 'form_values_json' );
		if ( ! empty( $dec ) ) {
			$form_values = $this->sanitize_public_form_values( $dec );
		}

		$payload = array(
			'booking_type_id'      => PostData::int( 'booking_type_id' ),
			'start'                => PostData::string( 'start' ),
			'end'                  => PostData::string( 'end' ),
			'base_price'           => PostData::float( 'base_price' ),
			'currency'             => PostData::string( 'currency', 'USD' ),
			'source'               => 'ajax',
			'customer_email'       => PostData::email( 'customer_email' ),
			'customer_phone'       => PostData::string( 'customer_phone' ),
			'customer_first_name'  => PostData::string( 'customer_first_name' ),
			'customer_last_name'   => PostData::string( 'customer_last_name' ),
			'form_values'          => $form_values,
		);

		if ( is_user_logged_in() ) {
			$payload['wp_user_id'] = get_current_user_id();
		}

		$listing_id = PostData::int( 'listing_id' );
		if ( $listing_id > 0 ) {
			$payload['listing_id'] = $listing_id;
		}

		$result = $engine->create_booking( $payload );

		if ( ! empty( $result['success'] ) && ! empty( $result['booking_id'] ) ) {
			$repo = $plugin->container()->get( 'booking.repository' );
			if ( $listing_id > 0 ) {
				$repo->add_meta( (int) $result['booking_id'], 'listing_id', (string) $listing_id );
				$author = (int) get_post_field( 'post_author', $listing_id );
				if ( $author > 0 ) {
					$vendor_id = ( new \FlexBooking\Vendor\VendorRepository() )->get_vendor_id( $author );
					if ( $vendor_id > 0 ) {
						$repo->update_booking( (int) $result['booking_id'], array( 'vendor_id' => $vendor_id ) );
					}
				}
			}
			wp_send_json_success( $result );
		}

		if ( ! empty( $result['errors'] ) && is_array( $result['errors'] ) ) {
			$result['message'] = implode( ' ', $result['errors'] );
		} elseif ( empty( $result['message'] ) ) {
			$result['message'] = __( 'Booking could not be saved.', 'flex-multiple-listing-and-booking-system' );
		}

		wp_send_json_error( $result, 400 );
	}

	/**
	 * Import demo listings for one booking type (20 posts by default).
	 *
	 * @return void
	 */
	public function import_demo_content() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		PostData::allow_processing();
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$type_id = PostData::int( 'booking_type_id' );
		$count   = PostData::has( 'count' ) ? PostData::int( 'count' ) : DemoContentSeeder::POSTS_PER_TYPE;

		if ( $type_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking type.', 'flex-multiple-listing-and-booking-system' ) ), 400 );
		}

		$result = DemoContentSeeder::import_for_type( $type_id, $count );

		if ( ! empty( $result['error'] ) ) {
			wp_send_json_error(
				array(
					'message' => (string) $result['error'],
					'result'  => $result,
				),
				400
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * Delete demo listings (all types or one type).
	 *
	 * @return void
	 */
	public function delete_demo_content() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		PostData::allow_processing();
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$type_id = PostData::int( 'booking_type_id' );
		$deleted = DemoContentSeeder::delete_demo( $type_id );

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of posts deleted */
					_n( '%d demo listing removed.', '%d demo listings removed.', $deleted, 'flex-multiple-listing-and-booking-system' ),
					$deleted
				),
			)
		);
	}

	/**
	 * Partner registration.
	 *
	 * @return void
	 */
	public function vendor_register() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		$this->throttle_auth_request( 'vendor_register' );
		PostData::allow_processing();

		$result = VendorAuth::register(
			array(
				'email'         => PostData::email( 'email' ),
				'password'      => (string) PostData::raw( 'password' ),
				'first_name'    => PostData::string( 'first_name' ),
				'last_name'     => PostData::string( 'last_name' ),
				'phone'         => PostData::string( 'phone' ),
				'business_name' => PostData::string( 'business_name' ),
			)
		);

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result, 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Partner login.
	 *
	 * @return void
	 */
	public function vendor_login() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		$this->throttle_auth_request( 'vendor_login' );
		PostData::allow_processing();

		$result = VendorAuth::login(
			array(
				'login'    => PostData::has( 'login' ) ? PostData::string( 'login' ) : PostData::email( 'email' ),
				'password' => (string) PostData::raw( 'password' ),
				'remember' => PostData::bool( 'remember' ),
			)
		);

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result, 401 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Upgrade logged-in user to partner.
	 *
	 * @return void
	 */
	public function vendor_become_partner() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		PostData::allow_processing();

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$result = VendorAuth::become_partner(
			get_current_user_id(),
			array(
				'business_name' => PostData::string( 'business_name' ),
				'phone'         => PostData::string( 'phone' ),
			)
		);

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result, 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Create or repair partner portal pages.
	 *
	 * @return void
	 */
	public function provision_vendor_pages() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		PostData::allow_processing();
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$force = PostData::bool( 'force' );
		$result = VendorPageProvisioner::ensure_pages( $force );

		wp_send_json_success(
			array(
				'created'  => $result['created'],
				'messages' => $result['messages'],
				'rows'     => VendorPageProvisioner::status_rows(),
				'message'  => sprintf(
					/* translators: %d: number of pages created */
					_n(
						'%d partner page created and linked in settings.',
						'%d partner pages created and linked in settings.',
						$result['created'],
						'flex-multiple-listing-and-booking-system'
					),
					$result['created']
				),
			)
		);
	}

	/**
	 * Save partner listing from dashboard.
	 *
	 * @return void
	 */
	public function vendor_save_listing() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		PostData::allow_processing();

		if ( ! is_user_logged_in() || ! VendorRole::can_manage_listings() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$data = array(
			'post_id'         => PostData::int( 'post_id' ),
			'booking_type_id' => PostData::int( 'booking_type_id' ),
			'title'           => PostData::string( 'title' ),
			'content'         => PostData::has( 'content' ) ? wp_kses_post( (string) PostData::raw( 'content' ) ) : '',
			'base_price'      => PostData::string( 'base_price' ),
			'address'         => PostData::string( 'address' ),
			'max_guests'      => PostData::has( 'max_guests' ) ? PostData::int( 'max_guests', 1 ) : 1,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Same request; nonce verified above.
		$ulbm_featured_name = isset( $_FILES['featured_image']['name'] ) ? sanitize_file_name( wp_unslash( (string) $_FILES['featured_image']['name'] ) ) : '';
		if ( '' !== $ulbm_featured_name && ! empty( $_FILES['featured_image']['tmp_name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attachment_id = media_handle_upload( 'featured_image', 0 );
			if ( ! is_wp_error( $attachment_id ) ) {
				$data['attachment_id'] = (int) $attachment_id;
			}
		}

		$result = VendorListingService::save_listing( get_current_user_id(), $data );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result, 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Delete partner listing.
	 *
	 * @return void
	 */
	public function vendor_delete_listing() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		PostData::allow_processing();

		if ( ! is_user_logged_in() || ! VendorRole::can_manage_listings() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$post_id = PostData::int( 'post_id' );
		$result  = VendorListingService::delete_listing( get_current_user_id(), $post_id );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result, 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX grid filter — returns rendered HTML cards for filtered listings.
	 *
	 * @return void
	 */
	public function grid_filter() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		PostData::allow_processing();

		$post_types = array();
		$type_slug  = PostData::key( 'type' );
		$all_types  = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types();

		if ( $type_slug ) {
			foreach ( $all_types as $bt ) {
				if ( (string) $bt['slug'] === $type_slug ) {
					$post_types[] = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $bt['slug'] );
					$post_types[] = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::legacy_cpt_name_from_slug( $bt['slug'] );
					break;
				}
			}
		} else {
			$post_types = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::listing_post_types_for_query();
		}
		$post_types = array_values( array_unique( $post_types ) );

		if ( empty( $post_types ) ) {
			wp_send_json_success( array( 'html' => '<p class="text-muted">' . esc_html__( 'No listings found.', 'flex-multiple-listing-and-booking-system' ) . '</p>', 'count' => 0 ) );
		}

		$keyword   = PostData::string( 'keyword' );
		$min_price = PostData::float( 'min_price' );
		$max_price = PostData::float( 'max_price' );
		$guests    = PostData::int( 'guests' );
		$sort      = PostData::has( 'sort' ) ? PostData::key( 'sort', 'date' ) : 'date';
		$page      = max( 1, PostData::int( 'page', 1 ) );
		$per_page  = max( 1, min( 50, PostData::int( 'per_page', 12 ) ) );

		$args = array(
			'post_type'      => $post_types,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
		);

		if ( $keyword ) {
			$args['s'] = $keyword;
		}

		switch ( $sort ) {
			case 'price_asc':
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Indexed listing price sort for grid AJAX.
				$args['meta_key']  = '_ulbm_base_price';
				$args['orderby']   = 'meta_value_num';
				$args['order']     = 'ASC';
				break;
			case 'price_desc':
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['meta_key']  = '_ulbm_base_price';
				$args['orderby']   = 'meta_value_num';
				$args['order']     = 'DESC';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		$meta_query = array();
		if ( $min_price > 0 ) {
			$meta_query[] = self::meta_numeric_or_legacy( ListingMeta::KEY_BASE_PRICE, $min_price, '>=' );
		}
		if ( $max_price > 0 ) {
			$meta_query[] = self::meta_numeric_or_legacy( ListingMeta::KEY_BASE_PRICE, $max_price, '<=' );
		}
		if ( $guests > 0 ) {
			$meta_query[] = self::meta_numeric_or_legacy( ListingMeta::KEY_MAX_GUESTS, $guests, '>=' );
		}
		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded listing filters on public grid.
			$args['meta_query']     = $meta_query;
		}

		$query = new \WP_Query( $args );

		$general  = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
		$currency = is_array( $general ) && ! empty( $general['currency'] ) ? $general['currency'] : 'USD';

		ob_start();
		$col_class = ListingDisplay::grid_col_class( (int) ( $general['grid_columns'] ?? 3 ) );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				ListingDisplay::render_grid_card( get_the_ID(), $col_class );
			}
		} else {
			echo '<div class="col-12"><p class="text-muted text-center py-5">' . esc_html__( 'No listings match your filters.', 'flex-multiple-listing-and-booking-system' ) . '</p></div>';
		}
		$html = ob_get_clean();
		wp_reset_postdata();

		wp_send_json_success( array(
			'html'          => $html,
			'count'         => $query->found_posts,
			'pages'         => $query->max_num_pages,
			'current'       => $page,
			'showing_start' => $query->found_posts > 0 ? ( ( $page - 1 ) * $per_page + 1 ) : 0,
			'showing_end'   => min( $page * $per_page, $query->found_posts ),
		) );
	}

	/**
	 * Submit a listing review from the single listing page.
	 *
	 * @return void
	 */
	public function submit_review() {
		check_ajax_referer( 'ulbm_public_booking', 'nonce' );
		PostData::allow_processing();

		$listing_id = PostData::int( 'listing_id' );
		$name       = PostData::string( 'author_name' );
		$email      = PostData::email( 'author_email' );
		$rating     = PostData::has( 'rating' ) ? PostData::int( 'rating', 5 ) : 5;
		$content    = PostData::has( 'content' ) ? sanitize_textarea_field( (string) PostData::raw( 'content' ) ) : '';

		$service = new ListingReviewService();
		$result  = $service->submit( $listing_id, $name, $email, $rating, $content );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['message'] ), 400 );
		}

		wp_send_json_success( array( 'message' => $result['message'] ) );
	}

	/**
	 * Approve, reject, or delete a review from wp-admin.
	 *
	 * @return void
	 */
	public function review_moderate() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		PostData::allow_processing();
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$review_id = PostData::int( 'review_id' );
		$action    = PostData::key( 'review_action' );

		if ( $review_id < 1 || ! in_array( $action, array( 'approve', 'reject', 'delete' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'flex-multiple-listing-and-booking-system' ) ), 400 );
		}

		$service = new ListingReviewService();
		$ok      = false;
		$status  = '';

		if ( 'approve' === $action ) {
			$ok     = $service->approve( $review_id );
			$status = 'approved';
		} elseif ( 'reject' === $action ) {
			$ok     = $service->reject( $review_id );
			$status = 'rejected';
		} else {
			$ok = $service->delete( $review_id );
		}

		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Review could not be updated.', 'flex-multiple-listing-and-booking-system' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Review updated.', 'flex-multiple-listing-and-booking-system' ),
				'status'  => $status,
				'deleted' => 'delete' === $action,
			)
		);
	}

	/**
	 * Limit repeated partner auth attempts per IP.
	 *
	 * @param string $action Action key.
	 * @return void
	 */
	private function throttle_auth_request( $action ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
			: 'unknown';
		$key   = 'ulbm_auth_' . sanitize_key( (string) $action ) . '_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= 10 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many attempts. Please wait a few minutes and try again.', 'flex-multiple-listing-and-booking-system' ),
				),
				429
			);
		}
		set_transient( $key, $count + 1, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Meta query clause matching ulbm or legacy fbs meta keys.
	 *
	 * @param string $ulbm_key Meta key.
	 * @param float  $value  Value.
	 * @param string $compare Compare operator.
	 * @return array<string, mixed>
	 */
	private static function meta_numeric_or_legacy( $ulbm_key, $value, $compare ) {
		$legacy_key = '_fbs_' . substr( (string) $ulbm_key, 6 );
		return array(
			'relation' => 'OR',
			array(
				'key'     => $ulbm_key,
				'value'   => $value,
				'compare' => $compare,
				'type'    => 'NUMERIC',
			),
			array(
				'key'     => $legacy_key,
				'value'   => $value,
				'compare' => $compare,
				'type'    => 'NUMERIC',
			),
		);
	}

	/**
	 * Sanitize dynamic booking form key/value pairs from JSON.
	 *
	 * @param array<mixed, mixed> $input Raw decoded array.
	 * @return array<string, string>
	 */
	private function sanitize_public_form_values( array $input ) {
		$out = array();
		foreach ( $input as $k => $v ) {
			$key = sanitize_key( (string) $k );
			if ( '' === $key || ! is_scalar( $v ) ) {
				continue;
			}
			$str = (string) $v;
			$use_textarea = ( strlen( $str ) > 180 )
				|| ( false !== strpos( $key, 'note' ) )
				|| ( false !== strpos( $key, 'request' ) )
				|| ( false !== strpos( $key, 'description' ) );
			$out[ $key ] = $use_textarea ? sanitize_textarea_field( $str ) : sanitize_text_field( $str );
		}
		return $out;
	}
}
