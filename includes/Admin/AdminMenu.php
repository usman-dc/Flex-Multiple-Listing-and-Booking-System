<?php
/**
 * Admin menu order for Flex Booking.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Admin;

use FlexBooking\PostTypes\BookingTypePostTypeRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Reorders Flex Booking submenu items.
 */
final class AdminMenu {

	/**
	 * Boot hooks.
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'reorder_submenu' ), 999 );
	}

	/**
	 * Put Dashboard first, then booking-type listing menus.
	 *
	 * @return void
	 */
	public static function reorder_submenu() {
		global $submenu;

		if ( empty( $submenu['ulbm-dashboard'] ) || ! is_array( $submenu['ulbm-dashboard'] ) ) {
			return;
		}

		$booking_cpts = array();
		foreach ( BookingTypePostTypeRegistry::get_registered_types() as $type ) {
			$booking_cpts[] = 'edit.php?post_type=' . BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $type['slug'] );
		}

		$desired = array_merge(
			array( 'ulbm-dashboard' ),
			$booking_cpts,
			array(
				'ulbm-booking-types',
				'ulbm-bookings',
				'ulbm-reviews',
				'ulbm-settings',
				'ulbm-setup',
			)
		);

		$by_slug = array();
		foreach ( $submenu['ulbm-dashboard'] as $item ) {
			if ( ! is_array( $item ) || empty( $item[2] ) ) {
				continue;
			}
			$by_slug[ (string) $item[2] ] = $item;
		}

		$ordered = array();
		foreach ( $desired as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$ordered[] = $by_slug[ $slug ];
				unset( $by_slug[ $slug ] );
			}
		}

		foreach ( $by_slug as $item ) {
			$ordered[] = $item;
		}

		$submenu['ulbm-dashboard'] = $ordered;
	}
}
