<?php
/**
 * Registers the fbs_listing CPT and its taxonomies.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Listings;

use FlexBooking\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

final class ListingPostType {

	public const POST_TYPE        = 'fbs_listing';
	public const TAX_CATEGORY     = 'fbs_listing_category';
	public const TAX_AMENITY      = 'fbs_listing_amenity';
	public const TAX_LOCATION     = 'fbs_listing_location';
	public const TAX_LISTING_TYPE = 'fbs_listing_type';

	/**
	 * Boot.
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
	}

	/**
	 * CPT registration.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Listings', 'flex-booking-system' ),
			'singular_name'      => __( 'Listing', 'flex-booking-system' ),
			'add_new'            => __( 'Add Listing', 'flex-booking-system' ),
			'add_new_item'       => __( 'Add New Listing', 'flex-booking-system' ),
			'edit_item'          => __( 'Edit Listing', 'flex-booking-system' ),
			'new_item'           => __( 'New Listing', 'flex-booking-system' ),
			'view_item'          => __( 'View Listing', 'flex-booking-system' ),
			'search_items'       => __( 'Search Listings', 'flex-booking-system' ),
			'not_found'          => __( 'No listings found.', 'flex-booking-system' ),
			'not_found_in_trash' => __( 'No listings in trash.', 'flex-booking-system' ),
			'all_items'          => __( 'All Listings', 'flex-booking-system' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'listing', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-building',
				'hierarchical'        => false,
				'exclude_from_search' => false,
			)
		);
	}

	/**
	 * Taxonomy registrations.
	 */
	public static function register_taxonomies() {
		register_taxonomy(
			self::TAX_CATEGORY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Listing Categories', 'flex-booking-system' ),
					'singular_name' => __( 'Category', 'flex-booking-system' ),
					'add_new_item'  => __( 'Add Category', 'flex-booking-system' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'listing-category' ),
			)
		);

		register_taxonomy(
			self::TAX_AMENITY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Amenities', 'flex-booking-system' ),
					'singular_name' => __( 'Amenity', 'flex-booking-system' ),
					'add_new_item'  => __( 'Add Amenity', 'flex-booking-system' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'listing-amenity' ),
			)
		);

		register_taxonomy(
			self::TAX_LOCATION,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Locations', 'flex-booking-system' ),
					'singular_name' => __( 'Location', 'flex-booking-system' ),
					'add_new_item'  => __( 'Add Location', 'flex-booking-system' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'listing-location' ),
			)
		);

		register_taxonomy(
			self::TAX_LISTING_TYPE,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Listing Types', 'flex-booking-system' ),
					'singular_name' => __( 'Listing Type', 'flex-booking-system' ),
					'add_new_item'  => __( 'Add Type', 'flex-booking-system' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'listing-type' ),
			)
		);
	}
}
