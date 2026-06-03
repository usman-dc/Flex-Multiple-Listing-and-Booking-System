<?php
/**
 * Registers custom capabilities for granular booking administration.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Capability registration helper.
 */
final class Capabilities {

	public const CAP_MANAGE = 'manage_fbs_bookings';
	public const CAP_BOOK    = 'create_fbs_bookings';

	/**
	 * Capability for add_menu_page / add_submenu_page.
	 *
	 * Core only runs `add_action( $hookname, $callback )` when current_user_can( $cap ) is true at
	 * registration time. Custom caps often fail there, so we use caps WordPress grants to real admins.
	 *
	 * @return string
	 */
	public static function menu_capability() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return 'manage_woocommerce';
		}
		return 'manage_options';
	}

	/**
	 * Who may use wp-admin Flex Booking screens, AJAX admin actions, and management REST routes.
	 *
	 * @return bool
	 */
	public static function can_access_admin() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}
		return user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'manage_woocommerce' )
			|| user_can( $user_id, self::CAP_MANAGE );
	}

	/**
	 * Whether the map_meta_cap filter is registered.
	 *
	 * @var bool
	 */
	private static $filter_registered = false;

	/**
	 * Satisfy `manage_fbs_bookings` when the user can run the site or WooCommerce store.
	 *
	 * WordPress maps unknown caps to a primitive with the same name; CPT UI also requires that
	 * primitive in `allcaps`. Role rows sometimes never get updated (no activation, object cache),
	 * so administrators would see "Sorry, you are not allowed to access this page" on menu URLs.
	 *
	 * @param string[] $caps    Primitive caps required.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id User ID.
	 * @param mixed[]  $args    Extra arguments.
	 * @return string[]
	 */
	public static function map_meta_manage( $caps, $cap, $user_id, $args ) {
		$manage = self::CAP_MANAGE;
		$needs  = ( $manage === $cap ) || in_array( $manage, (array) $caps, true );

		if ( ! $needs || $user_id < 1 ) {
			return $caps;
		}

		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'manage_woocommerce' ) ) {
			return array();
		}

		return $caps;
	}

	/**
	 * Attach caps to administrator + shop_manager (WooCommerce) when present.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! self::$filter_registered ) {
			add_filter( 'map_meta_cap', array( self::class, 'map_meta_manage' ), 5, 4 );
			self::$filter_registered = true;
		}

		$roles = array( 'administrator' );

		if ( get_role( 'shop_manager' ) ) {
			$roles[] = 'shop_manager';
		}

		$caps = array(
			self::CAP_MANAGE => true,
			self::CAP_BOOK   => true,
		);

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap => $grant ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap, $grant );
				}
			}
		}

		$subscriber_caps = array( self::CAP_BOOK => true );
		$customer_role   = get_role( 'customer' );
		if ( $customer_role ) {
			foreach ( $subscriber_caps as $cap => $grant ) {
				if ( ! $customer_role->has_cap( $cap ) ) {
					$customer_role->add_cap( $cap, $grant );
				}
			}
		}

		$subscriber = get_role( 'subscriber' );
		if ( $subscriber && ! $subscriber->has_cap( self::CAP_BOOK ) ) {
			$subscriber->add_cap( self::CAP_BOOK, true );
		}
	}
}
