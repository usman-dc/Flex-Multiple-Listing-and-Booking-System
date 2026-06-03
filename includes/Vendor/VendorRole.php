<?php
/**
 * Partner / vendor role and capabilities.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

defined( 'ABSPATH' ) || exit;

/**
 * Registers fbs_vendor role for frontend listing owners.
 */
final class VendorRole {

	public const ROLE = 'fbs_vendor';

	public const CAP_MANAGE_LISTINGS = 'manage_fbs_listings';

	/**
	 * Register role and caps on init.
	 *
	 * @return void
	 */
	public static function register() {
		$caps = array(
			'read'                   => true,
			'upload_files'           => true,
			'edit_posts'             => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'edit_published_posts'   => true,
			'delete_published_posts' => true,
			self::CAP_MANAGE_LISTINGS => true,
			\FlexBooking\Core\Capabilities::CAP_BOOK => true,
		);

		if ( ! get_role( self::ROLE ) ) {
			add_role(
				self::ROLE,
				__( 'Flex MLS Booking Partner', 'flex-multiple-listing-and-booking-system' ),
				$caps
			);
		} else {
			$role = get_role( self::ROLE );
			if ( $role ) {
				foreach ( $caps as $cap => $grant ) {
					if ( ! $role->has_cap( $cap ) ) {
						$role->add_cap( $cap, $grant );
					}
				}
			}
		}
	}

	/**
	 * Whether user is a vendor partner.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_vendor( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		return in_array( self::ROLE, (array) $user->roles, true )
			|| user_can( $user_id, 'manage_options' );
	}

	/**
	 * Whether user may manage frontend listings.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_manage_listings( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}
		return user_can( $user_id, self::CAP_MANAGE_LISTINGS )
			|| user_can( $user_id, 'manage_options' );
	}
}
