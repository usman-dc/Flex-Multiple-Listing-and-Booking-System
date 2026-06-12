<?php
/**
 * Saves Flex Booking general settings from wp-admin.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Admin;

use FlexBooking\Core\Capabilities;
use FlexBooking\Front\ColorSettings;
use FlexBooking\Front\LayoutSettings;
use FlexBooking\Security\PostData;

defined( 'ABSPATH' ) || exit;

/**
 * Persists ulbm_general_settings option.
 */
final class SettingsSave {

	/**
	 * Hook admin_init handler.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_save' ), 5 );
	}

	/**
	 * Save settings POST before any admin output.
	 *
	 * @return void
	 */
	public static function maybe_save() {
		if ( ! is_admin() || ! Capabilities::can_access_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked via check_admin_referer() below.
		if ( empty( $_POST['ulbm_save_settings'] ) || empty( $_POST['ulbm_settings_nonce'] ) ) {
			return;
		}

		if ( ! check_admin_referer( 'ulbm_save_settings', 'ulbm_settings_nonce' ) ) {
			return;
		}

		PostData::allow_processing();

		$prev = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
		if ( ! is_array( $prev ) ) {
			$prev = array();
		}

		$colors  = ColorSettings::merge_from_post( $prev );
		$general = self::merge_from_post( $prev );
		$general = array_merge( $general, $colors );

		update_option( 'ulbm_general_settings', wp_json_encode( $general ), false );
		LayoutSettings::clear_cache();

		$tab = PostData::key( 'ulbm_settings_tab', 'general' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => 'ulbm-settings',
					'ulbm-settings-saved' => '1',
					'tab'                 => $tab,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Build settings array from POST + previous values.
	 *
	 * @param array<string,mixed> $prev Previous settings.
	 * @return array<string,mixed>
	 */
	public static function merge_from_post( array $prev ) {
		$general = array_merge(
			$prev,
			array(
				'currency'           => PostData::has( 'ulbm_currency' ) ? sanitize_text_field( PostData::string( 'ulbm_currency' ) ) : ( $prev['currency'] ?? 'USD' ),
				'currency_position'  => PostData::has( 'ulbm_currency_position' ) ? sanitize_text_field( PostData::string( 'ulbm_currency_position' ) ) : ( $prev['currency_position'] ?? 'left' ),
				'date_format'        => PostData::has( 'ulbm_date_format' ) ? sanitize_text_field( PostData::string( 'ulbm_date_format' ) ) : ( $prev['date_format'] ?? 'Y-m-d' ),
				'time_format'        => PostData::has( 'ulbm_time_format' ) ? sanitize_text_field( PostData::string( 'ulbm_time_format' ) ) : ( $prev['time_format'] ?? 'H:i' ),
				'grid_columns'       => PostData::has( 'ulbm_grid_columns' )
					? max( 2, min( 4, absint( PostData::int( 'ulbm_grid_columns' ) ) ) )
					: max( 2, min( 4, (int) ( $prev['grid_columns'] ?? 3 ) ) ),
				'grid_per_page'      => PostData::has( 'ulbm_grid_per_page' ) ? absint( PostData::int( 'ulbm_grid_per_page' ) ) : (int) ( $prev['grid_per_page'] ?? 12 ),
				'card_border_radius' => PostData::has( 'ulbm_card_border_radius' ) ? absint( PostData::int( 'ulbm_card_border_radius' ) ) : (int) ( $prev['card_border_radius'] ?? 12 ),
				'card_shadow'        => self::checkbox_from_post( 'ulbm_card_shadow', $prev, 'card_shadow', true ),
				'show_filters'       => self::checkbox_from_post( 'ulbm_show_filters', $prev, 'show_filters', true ),
				'slider_height'      => PostData::has( 'ulbm_slider_height' ) ? absint( PostData::int( 'ulbm_slider_height' ) ) : (int) ( $prev['slider_height'] ?? 480 ),
				'sidebar_position'   => PostData::has( 'ulbm_sidebar_position' ) ? sanitize_key( PostData::key( 'ulbm_sidebar_position' ) ) : ( $prev['sidebar_position'] ?? 'right' ),
				'container_width'    => max(
					768,
					min(
						2400,
						PostData::has( 'ulbm_container_width' ) ? absint( PostData::int( 'ulbm_container_width' ) ) : (int) ( $prev['container_width'] ?? 1400 )
					)
				),
				'notify_customer_status' => self::checkbox_from_post( 'ulbm_notify_customer_status', $prev, 'notify_customer_status', false ),
				'notify_on_confirmed'    => self::checkbox_from_post( 'ulbm_notify_on_confirmed', $prev, 'notify_on_confirmed', true ),
				'notify_on_completed'    => self::checkbox_from_post( 'ulbm_notify_on_completed', $prev, 'notify_on_completed', false ),
				'notify_on_cancelled'    => self::checkbox_from_post( 'ulbm_notify_on_cancelled', $prev, 'notify_on_cancelled', true ),
				'notify_on_rejected'     => self::checkbox_from_post( 'ulbm_notify_on_rejected', $prev, 'notify_on_rejected', false ),
				'notify_on_on_hold'      => self::checkbox_from_post( 'ulbm_notify_on_on_hold', $prev, 'notify_on_on_hold', false ),
				'notify_on_pending'      => self::checkbox_from_post( 'ulbm_notify_on_pending', $prev, 'notify_on_pending', false ),
				'notify_reply_to'        => PostData::has( 'ulbm_notify_reply_to' ) ? sanitize_email( PostData::email( 'ulbm_notify_reply_to' ) ) : ( $prev['notify_reply_to'] ?? '' ),
				'vendor_register_page'   => PostData::has( 'ulbm_vendor_register_page' ) ? absint( PostData::int( 'ulbm_vendor_register_page' ) ) : (int) ( $prev['vendor_register_page'] ?? 0 ),
				'vendor_login_page'      => PostData::has( 'ulbm_vendor_login_page' ) ? absint( PostData::int( 'ulbm_vendor_login_page' ) ) : (int) ( $prev['vendor_login_page'] ?? 0 ),
				'vendor_dashboard_page'  => PostData::has( 'ulbm_vendor_dashboard_page' ) ? absint( PostData::int( 'ulbm_vendor_dashboard_page' ) ) : (int) ( $prev['vendor_dashboard_page'] ?? 0 ),
				'vendor_auto_approve'    => self::checkbox_from_post( 'ulbm_vendor_auto_approve', $prev, 'vendor_auto_approve', false ),
				'vendor_auto_publish'    => self::checkbox_from_post( 'ulbm_vendor_auto_publish', $prev, 'vendor_auto_publish', true ),
				'enable_google_maps_embed' => self::checkbox_from_post( 'ulbm_enable_google_maps_embed', $prev, 'enable_google_maps_embed', false ),
				'grid_gap'               => PostData::has( 'ulbm_grid_gap' ) ? max( 0, min( 120, absint( PostData::int( 'ulbm_grid_gap' ) ) ) ) : (int) ( $prev['grid_gap'] ?? 24 ),
				'grid_padding_x'         => PostData::has( 'ulbm_grid_padding_x' ) ? max( 0, min( 120, absint( PostData::int( 'ulbm_grid_padding_x' ) ) ) ) : (int) ( $prev['grid_padding_x'] ?? 0 ),
				'grid_padding_y'         => PostData::has( 'ulbm_grid_padding_y' ) ? max( 0, min( 120, absint( PostData::int( 'ulbm_grid_padding_y' ) ) ) ) : (int) ( $prev['grid_padding_y'] ?? 0 ),
				'grid_margin_top'        => PostData::has( 'ulbm_grid_margin_top' ) ? max( 0, min( 120, absint( PostData::int( 'ulbm_grid_margin_top' ) ) ) ) : (int) ( $prev['grid_margin_top'] ?? 0 ),
				'grid_margin_bottom'     => PostData::has( 'ulbm_grid_margin_bottom' ) ? max( 0, min( 120, absint( PostData::int( 'ulbm_grid_margin_bottom' ) ) ) ) : (int) ( $prev['grid_margin_bottom'] ?? 0 ),
				'grid_card_padding'      => PostData::has( 'ulbm_grid_card_padding' ) ? max( 0, min( 120, absint( PostData::int( 'ulbm_grid_card_padding' ) ) ) ) : (int) ( $prev['grid_card_padding'] ?? 16 ),
				'reviews_enabled'        => self::checkbox_from_post( 'ulbm_reviews_enabled', $prev, 'reviews_enabled', true ),
				'reviews_auto_approve'   => self::checkbox_from_post( 'ulbm_reviews_auto_approve', $prev, 'reviews_auto_approve', false ),
				'grid_show_rating'       => self::checkbox_from_post( 'ulbm_grid_show_rating', $prev, 'grid_show_rating', true ),
				'grid_show_amenities'    => self::checkbox_from_post( 'ulbm_grid_show_amenities', $prev, 'grid_show_amenities', true ),
				'grid_amenities_limit'   => PostData::has( 'ulbm_grid_amenities_limit' ) ? max( 1, min( 8, absint( PostData::int( 'ulbm_grid_amenities_limit' ) ) ) ) : (int) ( $prev['grid_amenities_limit'] ?? 4 ),
				'grid_design'            => PostData::has( 'ulbm_grid_design' )
					? \FlexBooking\Front\GridDesignRegistry::sanitize_id( PostData::key( 'ulbm_grid_design' ) )
					: \FlexBooking\Front\GridDesignRegistry::sanitize_id( (string) ( $prev['grid_design'] ?? \FlexBooking\Front\GridDesignRegistry::DEFAULT ) ),
			)
		);

		return $general;
	}

	/**
	 * Read checkbox value from POST without resetting when the field is absent.
	 *
	 * @param string              $post_key Field name.
	 * @param array<string,mixed> $prev     Previous settings.
	 * @param string              $prev_key Settings key.
	 * @param bool                $default  Default when unset.
	 * @return bool
	 */
	private static function checkbox_from_post( $post_key, array $prev, $prev_key, $default = false ) {
		if ( PostData::has( $post_key ) ) {
			return PostData::bool( $post_key );
		}

		if ( array_key_exists( $prev_key, $prev ) ) {
			return ! empty( $prev[ $prev_key ] );
		}

		return $default;
	}
}
