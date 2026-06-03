<?php
/**
 * Registers a public CPT for every booking type row in the database.
 *
 * When admin creates "Car Rental" or "Hotel" as a booking type, a matching
 * CPT is registered automatically (fbs_{slug}) with:
 * - Public single pages
 * - Archive pages
 * - Appears under Flex Booking admin menu
 * - Supports title, editor, thumbnail, excerpt
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\PostTypes;

use FlexBooking\Core\Capabilities;
use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class BookingTypePostTypeRegistry {

	/**
	 * Cached booking types from DB (avoid repeated queries).
	 *
	 * @var array|null
	 */
	private static $types_cache = null;

	/**
	 * Boot — hooks on init.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 12 );
		add_filter( 'single_template', array( $this, 'override_single_template' ) );
		add_filter( 'archive_template', array( $this, 'override_archive_template' ) );
	}

	/**
	 * Get all published booking types from DB.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_registered_types() {
		if ( null !== self::$types_cache ) {
			return self::$types_cache;
		}

		global $wpdb;
		$table = Schema::tables()['booking_types'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM `{$table}` WHERE status = 'publish' ORDER BY id ASC",
			ARRAY_A
		);

		self::$types_cache = is_array( $rows ) ? $rows : array();
		return self::$types_cache;
	}

	/**
	 * Build CPT name from booking type slug (max 20 chars for WP).
	 *
	 * @param string $slug Booking type slug.
	 * @return string Post type name.
	 */
	public static function cpt_name_from_slug( $slug ) {
		$slug = sanitize_key( (string) $slug );
		$name = 'fbs_' . $slug;
		if ( strlen( $name ) > 20 ) {
			$name = substr( $name, 0, 20 );
		}
		return $name;
	}

	/**
	 * Register one CPT per published booking type.
	 */
	public function register() {
		$types  = self::get_registered_types();
		$manage = Capabilities::CAP_MANAGE;

		foreach ( $types as $type ) {
			$slug = (string) $type['slug'];
			$name = (string) $type['name'];
			$pt   = self::cpt_name_from_slug( $slug );

			if ( post_type_exists( $pt ) ) {
				continue;
			}

			$labels = array(
				'name'               => $name,
				'singular_name'      => $name,
				'menu_name'          => $name,
				'add_new'            => __( 'Add New', 'flex-booking-system' ),
				'add_new_item'       => sprintf( __( 'Add %s', 'flex-booking-system' ), $name ),
				'edit_item'          => sprintf( __( 'Edit %s', 'flex-booking-system' ), $name ),
				'new_item'           => sprintf( __( 'New %s', 'flex-booking-system' ), $name ),
				'view_item'          => sprintf( __( 'View %s', 'flex-booking-system' ), $name ),
				'search_items'       => sprintf( __( 'Search %s', 'flex-booking-system' ), $name ),
				'not_found'          => __( 'Nothing found.', 'flex-booking-system' ),
				'not_found_in_trash' => __( 'Nothing found in Trash.', 'flex-booking-system' ),
				'all_items'          => $name,
			);

			$rewrite_slug = $slug;

			register_post_type(
				$pt,
				array(
					'labels'              => $labels,
					'description'         => (string) $type['description'],
					'public'              => true,
					'publicly_queryable'  => true,
					'show_ui'             => true,
					'show_in_menu'        => 'fbs-dashboard',
					'show_in_rest'        => true,
					'show_in_nav_menus'   => true,
					'show_in_admin_bar'   => true,
					'exclude_from_search' => false,
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'hierarchical'        => false,
					'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
					'has_archive'         => true,
					'rewrite'             => array( 'slug' => $rewrite_slug, 'with_front' => false ),
					'query_var'           => true,
					'menu_position'       => null,
				)
			);
		}
	}

	/**
	 * Override single template for booking type CPTs to use our standard layout.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function override_single_template( $template ) {
		if ( ! is_singular() ) {
			return $template;
		}

		$post_type = get_post_type();
		if ( ! $post_type || 0 !== strpos( $post_type, 'fbs_' ) ) {
			return $template;
		}

		if ( $post_type === 'fbs_listing' ) {
			return $template;
		}

		$types = self::get_registered_types();
		$match = false;
		foreach ( $types as $t ) {
			if ( self::cpt_name_from_slug( $t['slug'] ) === $post_type ) {
				$match = $t;
				break;
			}
		}

		if ( ! $match ) {
			return $template;
		}

		$custom = FBS_PLUGIN_DIR . 'templates/public/single-booking-type.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}

		return $template;
	}

	/**
	 * Override archive template for booking type CPTs.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function override_archive_template( $template ) {
		if ( ! is_post_type_archive() ) {
			return $template;
		}

		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		if ( ! $post_type || 0 !== strpos( (string) $post_type, 'fbs_' ) ) {
			return $template;
		}

		if ( 'fbs_listing' === $post_type ) {
			return $template;
		}

		$custom = FBS_PLUGIN_DIR . 'templates/public/archive-booking-type.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}

		return $template;
	}
}
