<?php
/**
 * Registers custom post types for industries chosen in setup (e.g. car bookings).
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\PostTypes;

use FlexBooking\Core\Capabilities;
use FlexBooking\Setup\IndustryCatalog;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks init to register CPTs under the Flex Booking admin menu.
 */
final class IndustryPostTypeRegistry {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 15 );
	}

	/**
	 * Register one CPT per enabled industry that has a catalog definition.
	 *
	 * @return void
	 */
	public function register() {
		$enabled = get_option( 'fbs_enabled_industries', array() );
		if ( ! is_array( $enabled ) || empty( $enabled ) ) {
			return;
		}

		$manage = Capabilities::CAP_MANAGE;

		foreach ( $enabled as $key ) {
			$def = IndustryCatalog::get( (string) $key );
			if ( null === $def ) {
				continue;
			}

			$pt = $def['post_type'];

			$labels = array(
				'name'               => $def['plural'],
				'singular_name'      => $def['singular'],
				'menu_name'          => $def['plural'],
				'add_new'            => __( 'Add New', 'flex-multiple-listing-and-booking-system' ),
				'add_new_item'       => sprintf(
					/* translators: %s: singular entity name */
					__( 'Add %s', 'flex-multiple-listing-and-booking-system' ),
					$def['singular']
				),
				'edit_item'          => sprintf(
					/* translators: %s: singular entity name */
					__( 'Edit %s', 'flex-multiple-listing-and-booking-system' ),
					$def['singular']
				),
				'new_item'           => sprintf(
					/* translators: %s: singular entity name */
					__( 'New %s', 'flex-multiple-listing-and-booking-system' ),
					$def['singular']
				),
				'view_item'          => sprintf(
					/* translators: %s: singular entity name */
					__( 'View %s', 'flex-multiple-listing-and-booking-system' ),
					$def['singular']
				),
				'search_items'       => sprintf(
					/* translators: %s: plural entity name */
					__( 'Search %s', 'flex-multiple-listing-and-booking-system' ),
					$def['plural']
				),
				'not_found'          => __( 'Nothing found.', 'flex-multiple-listing-and-booking-system' ),
				'not_found_in_trash' => __( 'Nothing found in Trash.', 'flex-multiple-listing-and-booking-system' ),
			);

			$caps = array(
				'edit_post'              => $manage,
				'read_post'              => $manage,
				'delete_post'            => $manage,
				'edit_posts'             => $manage,
				'edit_others_posts'      => $manage,
				'publish_posts'          => $manage,
				'read_private_posts'     => $manage,
				'delete_posts'           => $manage,
				'delete_private_posts'   => $manage,
				'delete_published_posts' => $manage,
				'delete_others_posts'    => $manage,
				'edit_private_posts'     => $manage,
				'edit_published_posts'   => $manage,
				'create_posts'           => $manage,
			);

			register_post_type(
				$pt,
				array(
					'labels'              => $labels,
					'description'         => $def['description'],
					'public'              => false,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'fbs-dashboard',
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
					'exclude_from_search' => true,
					'capability_type'     => 'post',
					'capabilities'        => $caps,
					'map_meta_cap'        => true,
					'hierarchical'        => false,
					'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
					'has_archive'         => false,
					'rewrite'             => false,
					'query_var'           => false,
					'menu_position'       => null,
				)
			);
		}
	}
}
