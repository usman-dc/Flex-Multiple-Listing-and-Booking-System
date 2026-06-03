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
use FlexBooking\Listings\ListingReviewService;
use FlexBooking\Security\Nonce;
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
		add_action( 'wp_ajax_fbs_admin_ping', array( $this, 'admin_ping' ) );
		add_action( 'wp_ajax_fbs_setup_complete', array( $this, 'setup_complete' ) );
		add_action( 'wp_ajax_fbs_setup_finish', array( $this, 'setup_finish' ) );
		add_action( 'wp_ajax_fbs_booking_update', array( $this, 'booking_update' ) );
		add_action( 'wp_ajax_nopriv_fbs_public_search', array( $this, 'public_search' ) );
		add_action( 'wp_ajax_fbs_public_search', array( $this, 'public_search' ) );

		add_action( 'wp_ajax_nopriv_fbs_create_booking', array( $this, 'create_booking' ) );
		add_action( 'wp_ajax_fbs_create_booking', array( $this, 'create_booking' ) );

		add_action( 'wp_ajax_nopriv_fbs_grid_filter', array( $this, 'grid_filter' ) );
		add_action( 'wp_ajax_fbs_grid_filter', array( $this, 'grid_filter' ) );

		add_action( 'wp_ajax_fbs_import_demo_content', array( $this, 'import_demo_content' ) );
		add_action( 'wp_ajax_fbs_delete_demo_content', array( $this, 'delete_demo_content' ) );

		add_action( 'wp_ajax_nopriv_fbs_vendor_register', array( $this, 'vendor_register' ) );
		add_action( 'wp_ajax_fbs_vendor_register', array( $this, 'vendor_register' ) );
		add_action( 'wp_ajax_nopriv_fbs_vendor_login', array( $this, 'vendor_login' ) );
		add_action( 'wp_ajax_fbs_vendor_login', array( $this, 'vendor_login' ) );
		add_action( 'wp_ajax_fbs_vendor_save_listing', array( $this, 'vendor_save_listing' ) );
		add_action( 'wp_ajax_fbs_vendor_delete_listing', array( $this, 'vendor_delete_listing' ) );
		add_action( 'wp_ajax_fbs_provision_vendor_pages', array( $this, 'provision_vendor_pages' ) );

		add_action( 'wp_ajax_nopriv_fbs_submit_review', array( $this, 'submit_review' ) );
		add_action( 'wp_ajax_fbs_submit_review', array( $this, 'submit_review' ) );
		add_action( 'wp_ajax_fbs_review_moderate', array( $this, 'review_moderate' ) );

		do_action( 'fbs_register_ajax_actions', $plugin );
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
		update_option( 'fbs_setup_completed', true, false );
		wp_send_json_success( array( 'completed' => true ) );
	}

	/**
	 * Save selected industries, provision booking types + CPTs, mark setup complete.
	 *
	 * @return void
	 */
	public function setup_finish() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$raw = isset( $_POST['industries'] ) ? wp_unslash( $_POST['industries'] ) : array();
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$summary = IndustryProvisioner::provision( $raw );
		update_option( 'fbs_setup_completed', true, false );

		wp_send_json_success(
			array(
				'completed' => true,
				'summary'   => $summary,
				'redirect'  => admin_url( 'admin.php?page=fbs-dashboard' ),
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
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
		if ( $booking_id < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking.', 'flex-multiple-listing-and-booking-system' ) ), 400 );
		}

		$status_in = isset( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : null;
		$pay_in    = isset( $_POST['payment_status'] ) ? wp_unslash( $_POST['payment_status'] ) : null;

		$new_status = null;
		if ( null !== $status_in && '' !== $status_in ) {
			$new_status = sanitize_key( (string) $status_in );
		}

		$new_payment = null;
		if ( null !== $pay_in && '' !== $pay_in ) {
			$new_payment = sanitize_key( (string) $pay_in );
		}

		$send = ! isset( $_POST['send_notification'] ) || '0' !== (string) wp_unslash( $_POST['send_notification'] );

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

		$query = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

		wp_send_json_success(
			array(
				'query'   => $query,
				'results' => apply_filters( 'fbs_ajax_search_results', array(), $query ),
			)
		);
	}

	/**
	 * Guest or logged-in booking submission via AJAX (REST remains for authenticated integrations).
	 *
	 * @return void
	 */
	public function create_booking() {
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		$allowed = apply_filters( 'fbs_allow_ajax_guest_booking', true );
		if ( ! $allowed && ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 401 );
		}

		$plugin = Plugin::instance();
		$engine = $plugin->container()->get( 'booking.engine' );

		$form_values = array();
		if ( isset( $_POST['form_values_json'] ) ) {
			$raw = wp_unslash( $_POST['form_values_json'] );
			$dec = is_string( $raw ) ? json_decode( $raw, true ) : null;
			if ( is_array( $dec ) ) {
				$form_values = $this->sanitize_public_form_values( $dec );
			}
		}

		$payload = array(
			'booking_type_id'      => isset( $_POST['booking_type_id'] ) ? absint( wp_unslash( $_POST['booking_type_id'] ) ) : 0,
			'start'                => isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '',
			'end'                  => isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '',
			'base_price'           => isset( $_POST['base_price'] ) ? (float) wp_unslash( $_POST['base_price'] ) : 0,
			'currency'             => isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : 'USD',
			'source'               => 'ajax',
			'customer_email'       => isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '',
			'customer_phone'       => isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '',
			'customer_first_name'  => isset( $_POST['customer_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_first_name'] ) ) : '',
			'customer_last_name'   => isset( $_POST['customer_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_last_name'] ) ) : '',
			'form_values'          => $form_values,
		);

		if ( is_user_logged_in() ) {
			$payload['wp_user_id'] = get_current_user_id();
		}

		$listing_id = isset( $_POST['listing_id'] ) ? absint( wp_unslash( $_POST['listing_id'] ) ) : 0;
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

		wp_send_json_error( $result, 400 );
	}

	/**
	 * Import demo listings for one booking type (20 posts by default).
	 *
	 * @return void
	 */
	public function import_demo_content() {
		check_ajax_referer( Nonce::ACTION_AJAX, 'nonce' );
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$type_id = isset( $_POST['booking_type_id'] ) ? absint( $_POST['booking_type_id'] ) : 0;
		$count   = isset( $_POST['count'] ) ? absint( $_POST['count'] ) : DemoContentSeeder::POSTS_PER_TYPE;

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
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$type_id = isset( $_POST['booking_type_id'] ) ? absint( $_POST['booking_type_id'] ) : 0;
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		$result = VendorAuth::register(
			array(
				'email'         => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
				'password'      => isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '',
				'first_name'    => isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '',
				'last_name'     => isset( $_POST['last_name'] ) ? wp_unslash( $_POST['last_name'] ) : '',
				'phone'         => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
				'business_name' => isset( $_POST['business_name'] ) ? wp_unslash( $_POST['business_name'] ) : '',
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		$result = VendorAuth::login(
			array(
				'login'    => isset( $_POST['login'] ) ? wp_unslash( $_POST['login'] ) : '',
				'password' => isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '',
				'remember' => ! empty( $_POST['remember'] ),
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$result = VendorAuth::become_partner(
			get_current_user_id(),
			array(
				'business_name' => isset( $_POST['business_name'] ) ? wp_unslash( $_POST['business_name'] ) : '',
				'phone'         => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
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
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$force  = ! empty( $_POST['force'] );
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		if ( ! is_user_logged_in() || ! VendorRole::can_manage_listings() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$data = array(
			'post_id'         => isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0,
			'booking_type_id' => isset( $_POST['booking_type_id'] ) ? absint( $_POST['booking_type_id'] ) : 0,
			'title'           => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
			'content'         => isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '',
			'base_price'      => isset( $_POST['base_price'] ) ? wp_unslash( $_POST['base_price'] ) : '',
			'address'         => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '',
			'max_guests'      => isset( $_POST['max_guests'] ) ? absint( $_POST['max_guests'] ) : 1,
		);

		if ( ! empty( $_FILES['featured_image']['name'] ) ) {
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		if ( ! is_user_logged_in() || ! VendorRole::can_manage_listings() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'flex-multiple-listing-and-booking-system' ) ), 403 );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		$post_types = array();
		$type_slug  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$all_types  = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types();

		if ( $type_slug ) {
			foreach ( $all_types as $bt ) {
				if ( (string) $bt['slug'] === $type_slug ) {
					$post_types[] = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $bt['slug'] );
					break;
				}
			}
		} else {
			foreach ( $all_types as $bt ) {
				$post_types[] = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $bt['slug'] );
			}
		}

		if ( empty( $post_types ) ) {
			wp_send_json_success( array( 'html' => '<p class="text-muted">' . esc_html__( 'No listings found.', 'flex-multiple-listing-and-booking-system' ) . '</p>', 'count' => 0 ) );
		}

		$keyword   = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$min_price = isset( $_POST['min_price'] ) ? (float) $_POST['min_price'] : 0;
		$max_price = isset( $_POST['max_price'] ) ? (float) $_POST['max_price'] : 0;
		$guests    = isset( $_POST['guests'] ) ? absint( $_POST['guests'] ) : 0;
		$sort      = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'date';
		$page      = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page  = isset( $_POST['per_page'] ) ? max( 1, min( 50, absint( $_POST['per_page'] ) ) ) : 12;

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
				$args['meta_key']  = '_fbs_base_price';
				$args['orderby']   = 'meta_value_num';
				$args['order']     = 'ASC';
				break;
			case 'price_desc':
				$args['meta_key']  = '_fbs_base_price';
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
			$meta_query[] = array(
				'key'     => '_fbs_base_price',
				'value'   => $min_price,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $max_price > 0 ) {
			$meta_query[] = array(
				'key'     => '_fbs_base_price',
				'value'   => $max_price,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $guests > 0 ) {
			$meta_query[] = array(
				'key'     => '_fbs_max_guests',
				'value'   => $guests,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query;
		}

		$query = new \WP_Query( $args );

		$general  = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
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
		check_ajax_referer( 'fbs_public_booking', 'nonce' );

		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		$name       = isset( $_POST['author_name'] ) ? wp_unslash( $_POST['author_name'] ) : '';
		$email      = isset( $_POST['author_email'] ) ? wp_unslash( $_POST['author_email'] ) : '';
		$rating     = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 5;
		$content    = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';

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
		if ( ! Capabilities::can_access_admin() ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
		$action    = isset( $_POST['review_action'] ) ? sanitize_key( wp_unslash( $_POST['review_action'] ) ) : '';

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
