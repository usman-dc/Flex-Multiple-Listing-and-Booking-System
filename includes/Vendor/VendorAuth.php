<?php
/**
 * Partner registration and login.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

defined( 'ABSPATH' ) || exit;

/**
 * Handles vendor signup and sign-in.
 */
final class VendorAuth {

	/**
	 * Register new partner account.
	 *
	 * @param array<string,mixed> $data Form data.
	 * @return array{success:bool,message?:string,redirect?:string,user_id?:int}
	 */
	public static function register( array $data ) {
		$email    = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
		$password = isset( $data['password'] ) ? (string) $data['password'] : '';
		$first    = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
		$last     = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
		$phone    = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
		$business = isset( $data['business_name'] ) ? sanitize_text_field( $data['business_name'] ) : '';

		if ( ! $email || ! is_email( $email ) ) {
			return array( 'success' => false, 'message' => __( 'Valid email is required.', 'flex-multiple-listing-and-booking-system' ) );
		}
		if ( strlen( $password ) < 6 ) {
			return array( 'success' => false, 'message' => __( 'Password must be at least 6 characters.', 'flex-multiple-listing-and-booking-system' ) );
		}
		if ( '' === $first || '' === $last ) {
			return array( 'success' => false, 'message' => __( 'First and last name are required.', 'flex-multiple-listing-and-booking-system' ) );
		}
		if ( email_exists( $email ) ) {
			return array(
				'success'  => false,
				'message'  => __( 'An account with this email already exists. Please log in.', 'flex-multiple-listing-and-booking-system' ),
				'redirect' => VendorPages::login_url(),
			);
		}

		$username = self::unique_username( $email, $first, $last );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => trim( $first . ' ' . $last ),
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return array(
				'success' => false,
				'message' => $user_id->get_error_message(),
			);
		}

		if ( $phone ) {
			update_user_meta( (int) $user_id, 'ulbm_phone', $phone );
		}

		$settings = VendorPages::settings();
		$status   = ! empty( $settings['vendor_auto_approve'] ) ? 'approved' : 'pending';

		$repo = new VendorRepository();
		$repo->create( (int) $user_id, $business ? $business : trim( $first . ' ' . $last ), $status );

		if ( 'approved' === $status ) {
			$user = get_userdata( (int) $user_id );
			if ( $user ) {
				$user->set_role( VendorRole::ROLE );
			}
			$signon = wp_signon(
				array(
					'user_login'    => $username,
					'user_password' => $password,
					'remember'      => true,
				),
				is_ssl()
			);
			if ( is_wp_error( $signon ) ) {
				return array(
					'success'  => true,
					'user_id'  => (int) $user_id,
					'message'  => __( 'Account created. Please log in.', 'flex-multiple-listing-and-booking-system' ),
					'redirect' => VendorPages::login_url(),
				);
			}
		}

		return array(
			'success'  => true,
			'user_id'  => (int) $user_id,
			'message'  => 'approved' === $status
				? __( 'Welcome! Your partner account is ready.', 'flex-multiple-listing-and-booking-system' )
				: __( 'Registration received. Your account is pending approval. You can log in after an administrator approves your partner request.', 'flex-multiple-listing-and-booking-system' ),
			'redirect' => 'approved' === $status ? VendorPages::dashboard_url() : VendorPages::login_url(),
		);
	}

	/**
	 * Log in partner.
	 *
	 * @param array<string,mixed> $data Credentials.
	 * @return array{success:bool,message?:string,redirect?:string}
	 */
	public static function login( array $data ) {
		$login    = isset( $data['login'] ) ? sanitize_text_field( $data['login'] ) : '';
		$password = isset( $data['password'] ) ? (string) $data['password'] : '';
		$remember = ! empty( $data['remember'] );

		if ( '' === $login || '' === $password ) {
			return array( 'success' => false, 'message' => __( 'Email and password are required.', 'flex-multiple-listing-and-booking-system' ) );
		}

		if ( is_email( $login ) ) {
			$user = get_user_by( 'email', $login );
			if ( $user ) {
				$login = $user->user_login;
			}
		}

		$creds = array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid email or password.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		$repo = new VendorRepository();
		if ( ! $repo->is_approved( (int) $user->ID ) && ! user_can( $user, 'manage_options' ) ) {
			wp_logout();
			return array(
				'success' => false,
				'message' => __( 'Your partner account is pending approval.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		if ( ! VendorRole::can_manage_listings( (int) $user->ID ) && ! user_can( $user, 'manage_options' ) ) {
			wp_logout();
			return array(
				'success' => false,
				'message' => __( 'This account does not have partner access.', 'flex-multiple-listing-and-booking-system' ),
			);
		}

		return array(
			'success'  => true,
			'message'  => __( 'Logged in successfully.', 'flex-multiple-listing-and-booking-system' ),
			'redirect' => VendorPages::dashboard_url(),
		);
	}

	/**
	 * Upgrade an existing logged-in user to partner.
	 *
	 * @param int                 $user_id User ID.
	 * @param array<string,mixed> $data    Optional business details.
	 * @return array{success:bool,message?:string,redirect?:string}
	 */
	public static function become_partner( $user_id, array $data = array() ) {
		$user_id = absint( $user_id );
		if ( $user_id < 1 ) {
			return array( 'success' => false, 'message' => __( 'You must be logged in.', 'flex-multiple-listing-and-booking-system' ) );
		}

		if ( VendorRole::can_manage_listings( $user_id ) ) {
			return array(
				'success'  => true,
				'message'  => __( 'You already have partner access.', 'flex-multiple-listing-and-booking-system' ),
				'redirect' => VendorPages::dashboard_url(),
			);
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'success' => false, 'message' => __( 'User not found.', 'flex-multiple-listing-and-booking-system' ) );
		}

		$business = isset( $data['business_name'] ) ? sanitize_text_field( $data['business_name'] ) : '';
		$phone    = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';

		if ( $phone ) {
			update_user_meta( $user_id, 'ulbm_phone', $phone );
		}

		$settings = VendorPages::settings();
		$status   = ! empty( $settings['vendor_auto_approve'] ) ? 'approved' : 'pending';

		$repo     = new VendorRepository();
		$existing = $repo->get_by_user_id( $user_id );
		if ( ! $existing ) {
			$label = $business ? $business : ( $user->display_name ? $user->display_name : $user->user_login );
			$repo->create( $user_id, $label, $status );
		}

		if ( 'approved' === $status ) {
			$user->add_role( VendorRole::ROLE );
		}

		return array(
			'success'  => true,
			'message'  => 'approved' === $status
				? __( 'Partner access enabled! You can now add listings.', 'flex-multiple-listing-and-booking-system' )
				: __( 'Partner request submitted. Your account is pending approval.', 'flex-multiple-listing-and-booking-system' ),
			'redirect' => 'approved' === $status ? VendorPages::add_listing_url() : VendorPages::dashboard_url(),
		);
	}

	/**
	 * @param string $email Email.
	 * @param string $first First name.
	 * @param string $last  Last name.
	 * @return string
	 */
	private static function unique_username( $email, $first, $last ) {
		$base = sanitize_user( strtolower( $first . '.' . $last ), true );
		if ( '' === $base ) {
			$base = sanitize_user( strstr( $email, '@', true ), true );
		}
		if ( '' === $base ) {
			$base = 'partner';
		}

		$username = $base;
		$i        = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $i;
			++$i;
		}

		return $username;
	}
}
