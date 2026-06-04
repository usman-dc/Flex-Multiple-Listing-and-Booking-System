<?php
/**
 * Partner page URLs and settings helpers.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves register / login / dashboard page URLs from settings.
 */
final class VendorPages {

	/**
	 * Partner-related settings with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function settings() {
		$settings_json = get_option( 'ulbm_general_settings', false );
		if ( false === $settings_json ) {
			$settings_json = get_option( 'fbs_general_settings', '{}' );
		}
		$raw = json_decode( (string) $settings_json, true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$defaults = array(
			'vendor_register_page'  => 0,
			'vendor_login_page'     => 0,
			'vendor_dashboard_page' => 0,
			'vendor_auto_approve'   => false,
			'enable_google_maps_embed' => false,
			'vendor_auto_publish'   => true,
		);

		return array_merge( $defaults, $raw );
	}

	/**
	 * @param string $key Setting key.
	 * @return string URL.
	 */
	public static function page_url( $key ) {
		$s    = self::settings();
		$page = isset( $s[ $key ] ) ? absint( $s[ $key ] ) : 0;
		if ( $page > 0 ) {
			$url = get_permalink( $page );
			if ( $url ) {
				return $url;
			}
		}

		switch ( $key ) {
			case 'vendor_register_page':
				return home_url( '/partner-register/' );
			case 'vendor_login_page':
				return wp_login_url( self::dashboard_url() );
			default:
				return home_url( '/partner-dashboard/' );
		}
	}

	/**
	 * @return string
	 */
	public static function register_url() {
		return self::page_url( 'vendor_register_page' );
	}

	/**
	 * @return string
	 */
	public static function login_url() {
		$s = self::settings();
		if ( ! empty( $s['vendor_login_page'] ) ) {
			return self::page_url( 'vendor_login_page' );
		}
		return self::page_url( 'vendor_login_page' );
	}

	/**
	 * @return string
	 */
	public static function dashboard_url() {
		return self::page_url( 'vendor_dashboard_page' );
	}

	/**
	 * Dashboard tab URL.
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	public static function dashboard_tab_url( $tab = 'overview' ) {
		return add_query_arg( 'ulbm_tab', sanitize_key( $tab ), self::dashboard_url() );
	}

	/**
	 * @return string
	 */
	public static function add_listing_url() {
		return self::dashboard_tab_url( 'add' );
	}

	/**
	 * @return string
	 */
	public static function listings_url() {
		return self::dashboard_tab_url( 'listings' );
	}

	/**
	 * @return string
	 */
	public static function bookings_url() {
		return self::dashboard_tab_url( 'bookings' );
	}

	/**
	 * @return string
	 */
	public static function profile_url() {
		return self::dashboard_tab_url( 'profile' );
	}

	/**
	 * Logout URL returning to current page when possible.
	 *
	 * @param string $redirect Optional redirect after logout.
	 * @return string
	 */
	public static function logout_url( $redirect = '' ) {
		if ( '' === $redirect ) {
			$redirect = is_singular() ? get_permalink() : home_url( '/' );
		}
		return wp_logout_url( $redirect );
	}

	/**
	 * Whether current page contains a vendor shortcode.
	 *
	 * @param \WP_Post|null $post Post.
	 * @return bool
	 */
	public static function is_vendor_page( $post = null ) {
		if ( ! $post ) {
			$post = get_post();
		}
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'ulbm_register' )
			|| has_shortcode( $post->post_content, 'ulbm_login' )
			|| has_shortcode( $post->post_content, 'ulbm_dashboard' )
			|| has_shortcode( $post->post_content, 'ulbm_become_partner' );
	}
}
