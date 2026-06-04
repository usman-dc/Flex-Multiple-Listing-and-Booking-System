<?php
/**
 * Tabbed metabox for the listing CPT edit screen — Bootstrap 5 UI.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Listings;

use FlexBooking\Booking\BookingTypeRepository;

defined( 'ABSPATH' ) || exit;

final class ListingMetabox {

	public static function register() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metabox' ) );
		add_action( 'save_post', array( __CLASS__, 'save_any_ulbm_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Proxy save for any ulbm_* post type.
	 */
	public static function save_any_ulbm_post( $post_id, $post ) {
		if ( ! self::is_ulbm_post_type( $post->post_type ?? '' ) ) {
			return;
		}
		self::save( $post_id, $post );
	}

	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		global $post_type, $post;
		$pt = $post_type;
		if ( ! $pt && $post instanceof \WP_Post ) {
			$pt = $post->post_type;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin screen context only.
		if ( ! $pt && isset( $_GET['post_type'] ) ) {
			$pt = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin screen context only.
		if ( ! $pt && isset( $_GET['post'] ) ) {
			$pt = (string) get_post_type( absint( $_GET['post'] ) );
		}

		if ( ! self::is_ulbm_post_type( $pt ) ) {
			return;
		}

		wp_enqueue_media();

		\FlexBooking\Assets\VendorAssets::register_bootstrap();

		wp_enqueue_style( 'ulbm-bootstrap' );
		wp_enqueue_style( 'ulbm-bootstrap-icons' );
		wp_enqueue_script( 'ulbm-bootstrap' );

		wp_enqueue_style( 'ulbm-listing-metabox', ULBM_PLUGIN_URL . 'dist/admin.css', array( 'ulbm-bootstrap', 'ulbm-bootstrap-icons' ), ULBM_VERSION );
		wp_enqueue_script( 'ulbm-listing-metabox', ULBM_PLUGIN_URL . 'dist/listing-metabox.js', array( 'jquery', 'ulbm-bootstrap' ), ULBM_VERSION, true );
	}

	/**
	 * Check if post type belongs to our plugin (ulbm_listing or any ulbm_* booking type CPT).
	 */
	private static function is_ulbm_post_type( $post_type ) {
		if ( ! $post_type ) {
			return false;
		}
		return $post_type === ListingPostType::POST_TYPE
			|| \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( (string) $post_type );
	}

	public static function add_metabox() {
		$screens = array( ListingPostType::POST_TYPE );

		$types = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types();
		foreach ( $types as $t ) {
			$cpt = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $t['slug'] );
			$screens[] = $cpt;
		}

		foreach ( $screens as $screen ) {
			add_meta_box(
				'ulbm_listing_settings',
				__( 'Listing Settings', 'flex-multiple-listing-and-booking-system' ),
				array( __CLASS__, 'render' ),
				$screen,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the tabbed metabox.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'ulbm_listing_meta', 'ulbm_listing_meta_nonce' );
		$id = $post->ID;

		$type_repo     = new BookingTypeRepository();
		$booking_types = $type_repo->get_all();

		$meta = array();
		foreach ( ListingMeta::defaults() as $key => $default ) {
			$type = 'string';
			if ( is_int( $default ) ) {
				$type = 'int';
			} elseif ( is_float( $default ) ) {
				$type = 'float';
			} elseif ( is_bool( $default ) ) {
				$type = 'bool';
			} elseif ( is_array( $default ) ) {
				$type = 'array';
			}
			$meta[ $key ] = ListingMeta::get( $id, $key, $type );
		}

		include ULBM_PLUGIN_DIR . 'templates/admin/listing-metabox.php';
	}

	/**
	 * Save listing meta.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce field checked before verify.
		if ( ! isset( $_POST['ulbm_listing_meta_nonce'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on next line.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ulbm_listing_meta_nonce'] ) ), 'ulbm_listing_meta' ) ) {
			return;
		}

		\FlexBooking\Security\PostData::allow_processing();

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Auto-assign booking_type_id from CPT if not explicitly set.
		$pt = $post->post_type ?? '';
		if ( $pt && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( $pt ) ) {
			$current_type_id = (int) ListingMeta::get( $post_id, ListingMeta::KEY_BOOKING_TYPE_ID, 'int' );
			if ( 0 === $current_type_id ) {
				$registered = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types();
				foreach ( $registered as $rt ) {
					if ( \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $rt['slug'] ) === $pt ) {
						ListingMeta::set( $post_id, ListingMeta::KEY_BOOKING_TYPE_ID, (int) $rt['id'] );
						break;
					}
				}
			}
		}

		$text_keys = array(
			ListingMeta::KEY_BASE_PRICE,
			ListingMeta::KEY_SALE_PRICE,
			ListingMeta::KEY_PRICE_SUFFIX,
			ListingMeta::KEY_ADDRESS,
			ListingMeta::KEY_LATITUDE,
			ListingMeta::KEY_LONGITUDE,
			ListingMeta::KEY_CONTACT_EMAIL,
			ListingMeta::KEY_CONTACT_PHONE,
			ListingMeta::KEY_CHECK_IN_TIME,
			ListingMeta::KEY_CHECK_OUT_TIME,
			ListingMeta::KEY_VIDEO_URL,
		);

		foreach ( $text_keys as $key ) {
			$field = str_replace( '_ulbm_', 'ulbm_', $key );
			if ( \FlexBooking\Security\PostData::has( $field ) ) {
				ListingMeta::set( $post_id, $key, sanitize_text_field( (string) \FlexBooking\Security\PostData::raw( $field ) ) );
			}
		}

		$int_keys = array(
			ListingMeta::KEY_BOOKING_TYPE_ID,
			ListingMeta::KEY_MIN_BOOKING,
			ListingMeta::KEY_MAX_BOOKING,
			ListingMeta::KEY_MAX_GUESTS,
			ListingMeta::KEY_MAP_ZOOM,
			ListingMeta::KEY_DEPOSIT_PERCENT,
			ListingMeta::KEY_CANCELLATION_DAYS,
		);

		foreach ( $int_keys as $key ) {
			$field = str_replace( '_ulbm_', 'ulbm_', $key );
			if ( \FlexBooking\Security\PostData::has( $field ) ) {
				ListingMeta::set( $post_id, $key, absint( \FlexBooking\Security\PostData::raw( $field ) ) );
			}
		}

		$field_mode = str_replace( '_ulbm_', 'ulbm_', ListingMeta::KEY_BOOKING_MODE );
		if ( \FlexBooking\Security\PostData::has( $field_mode ) ) {
			$mode = sanitize_key( (string) \FlexBooking\Security\PostData::raw( $field_mode ) );
			$valid_modes = array( 'daily', 'hourly', 'time_slot' );
			ListingMeta::set( $post_id, ListingMeta::KEY_BOOKING_MODE, in_array( $mode, $valid_modes, true ) ? $mode : 'daily' );
		}

		$field_instant = str_replace( '_ulbm_', 'ulbm_', ListingMeta::KEY_INSTANT_BOOKING );
		ListingMeta::set( $post_id, ListingMeta::KEY_INSTANT_BOOKING, \FlexBooking\Security\PostData::has( $field_instant ) ? '1' : '' );

		// Gallery (comma-separated attachment IDs).
		if ( \FlexBooking\Security\PostData::has( 'ulbm_gallery' ) ) {
			$raw_ids = sanitize_text_field( (string) \FlexBooking\Security\PostData::raw( 'ulbm_gallery' ) );
			$ids     = array_values( array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) ) );
			ListingMeta::set( $post_id, ListingMeta::KEY_GALLERY, $ids );
		}

		// Features repeater (JSON string from JS).
		if ( \FlexBooking\Security\PostData::has( 'ulbm_features_json' ) ) {
			$decoded = \FlexBooking\Security\JsonInput::decode_post_array( 'ulbm_features_json' );
			if ( is_array( $decoded ) ) {
				$sanitized = array();
				foreach ( $decoded as $item ) {
					if ( is_array( $item ) && ! empty( $item['label'] ) ) {
						$sanitized[] = array(
							'icon'  => isset( $item['icon'] ) ? sanitize_text_field( $item['icon'] ) : '',
							'label' => sanitize_text_field( $item['label'] ),
							'value' => isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '',
						);
					}
				}
				ListingMeta::set( $post_id, ListingMeta::KEY_FEATURES, $sanitized );
			}
		}

		// FAQ repeater (JSON).
		if ( \FlexBooking\Security\PostData::has( 'ulbm_faq_json' ) ) {
			$decoded = \FlexBooking\Security\JsonInput::decode_post_array( 'ulbm_faq_json' );
			if ( is_array( $decoded ) ) {
				$sanitized = array();
				foreach ( $decoded as $item ) {
					if ( is_array( $item ) && ! empty( $item['question'] ) ) {
						$sanitized[] = array(
							'question' => sanitize_text_field( $item['question'] ),
							'answer'   => sanitize_textarea_field( $item['answer'] ?? '' ),
						);
					}
				}
				ListingMeta::set( $post_id, ListingMeta::KEY_FAQ, $sanitized );
			}
		}

		// Extra services repeater (JSON).
		if ( \FlexBooking\Security\PostData::has( 'ulbm_extra_services_json' ) ) {
			$decoded = \FlexBooking\Security\JsonInput::decode_post_array( 'ulbm_extra_services_json' );
			if ( is_array( $decoded ) ) {
				$sanitized = array();
				foreach ( $decoded as $item ) {
					if ( is_array( $item ) && ! empty( $item['name'] ) ) {
						$sanitized[] = array(
							'name'     => sanitize_text_field( $item['name'] ),
							'price'    => isset( $item['price'] ) ? (float) $item['price'] : 0,
							'per'      => isset( $item['per'] ) ? sanitize_key( $item['per'] ) : 'booking',
							'required' => ! empty( $item['required'] ),
						);
					}
				}
				ListingMeta::set( $post_id, ListingMeta::KEY_EXTRA_SERVICES, $sanitized );
			}
		}

		do_action( 'ulbm_listing_meta_saved', $post_id, $post );
	}
}
