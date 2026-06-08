<?php
/**
 * Admin partner approval and management.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

defined( 'ABSPATH' ) || exit;

/**
 * Approve, suspend, update, or remove partner accounts from wp-admin.
 */
final class VendorAdminService {

	/**
	 * @var VendorRepository
	 */
	private $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new VendorRepository();
	}

	/**
	 * Approve a pending or suspended partner.
	 *
	 * @param int $vendor_id Vendor row ID.
	 * @return bool
	 */
	public function approve( $vendor_id ) {
		$row = $this->repo->get_by_id( $vendor_id );
		if ( ! $row ) {
			return false;
		}

		$user_id = (int) $row['wp_user_id'];
		if ( $user_id < 1 || ! get_userdata( $user_id ) ) {
			return false;
		}

		if ( ! $this->repo->update( $vendor_id, array( 'status' => 'approved' ) ) ) {
			return false;
		}

		self::grant_partner_role( $user_id );

		return true;
	}

	/**
	 * Suspend an approved partner (revokes portal access).
	 *
	 * @param int $vendor_id Vendor row ID.
	 * @return bool
	 */
	public function suspend( $vendor_id ) {
		$row = $this->repo->get_by_id( $vendor_id );
		if ( ! $row ) {
			return false;
		}

		$user_id = (int) $row['wp_user_id'];
		if ( $user_id < 1 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		if ( ! $this->repo->update( $vendor_id, array( 'status' => 'suspended' ) ) ) {
			return false;
		}

		self::revoke_partner_role( $user_id );

		return true;
	}

	/**
	 * Update partner business name.
	 *
	 * @param int    $vendor_id     Vendor row ID.
	 * @param string $business_name New label.
	 * @return bool
	 */
	public function update_business_name( $vendor_id, $business_name ) {
		return $this->repo->update( $vendor_id, array( 'business_name' => $business_name ) );
	}

	/**
	 * Remove partner record and revoke portal role (WordPress user is kept).
	 *
	 * @param int $vendor_id Vendor row ID.
	 * @return bool
	 */
	public function delete( $vendor_id ) {
		$row = $this->repo->get_by_id( $vendor_id );
		if ( ! $row ) {
			return false;
		}

		$user_id = (int) $row['wp_user_id'];
		if ( user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		if ( ! $this->repo->delete( $vendor_id ) ) {
			return false;
		}

		self::revoke_partner_role( $user_id );

		return true;
	}

	/**
	 * Count published listings owned by a partner user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int
	 */
	public static function count_listings( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id < 1 ) {
			return 0;
		}

		return count( VendorListingService::get_listings( $user_id ) );
	}

	/**
	 * @param int $user_id User ID.
	 * @return void
	 */
	private static function grant_partner_role( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$user->add_role( VendorRole::ROLE );
	}

	/**
	 * @param int $user_id User ID.
	 * @return void
	 */
	private static function revoke_partner_role( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$user->remove_role( VendorRole::ROLE );
		if ( empty( $user->roles ) ) {
			$user->set_role( 'subscriber' );
		}
	}
}
