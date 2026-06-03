<?php
/**
 * Auto-create partner portal WordPress pages with shortcodes.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Vendor;

use FlexBooking\Front\LayoutSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Creates register / login / dashboard pages and saves IDs in settings.
 */
final class VendorPageProvisioner {

	public const PAGE_META = '_fbs_vendor_portal_page';

	/**
	 * Page definitions keyed by settings field.
	 *
	 * @return array<string,array{title:string,slug:string,shortcode:string,meta:string}>
	 */
	public static function definitions() {
		return array(
			'vendor_register_page'  => array(
				'title'     => __( 'Partner Register', 'flex-multiple-listing-and-booking-system' ),
				'slug'      => 'partner-register',
				'shortcode' => '[fbs_register]',
				'meta'      => 'register',
			),
			'vendor_login_page'     => array(
				'title'     => __( 'Partner Login', 'flex-multiple-listing-and-booking-system' ),
				'slug'      => 'partner-login',
				'shortcode' => '[fbs_login]',
				'meta'      => 'login',
			),
			'vendor_dashboard_page' => array(
				'title'     => __( 'Partner Dashboard', 'flex-multiple-listing-and-booking-system' ),
				'slug'      => 'partner-dashboard',
				'shortcode' => '[fbs_dashboard]',
				'meta'      => 'dashboard',
			),
		);
	}

	/**
	 * Create pages on activation when none are configured.
	 *
	 * @return void
	 */
	public static function maybe_auto_provision() {
		$settings = VendorPages::settings();
		$missing  = false;

		foreach ( array_keys( self::definitions() ) as $key ) {
			$page_id = isset( $settings[ $key ] ) ? absint( $settings[ $key ] ) : 0;
			if ( $page_id < 1 || ! get_post( $page_id ) ) {
				$missing = true;
				break;
			}
		}

		if ( $missing ) {
			self::ensure_pages( false );
		}
	}

	/**
	 * Ensure all partner pages exist and settings point to them.
	 *
	 * @param bool $force Recreate if setting points to invalid page.
	 * @return array{pages:array<string,int>,created:int,updated:bool,messages:array<int,string>}
	 */
	public static function ensure_pages( $force = false ) {
		$raw = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$pages    = array();
		$created  = 0;
		$messages = array();
		$updated  = false;

		foreach ( self::definitions() as $key => $def ) {
			$page_id = isset( $raw[ $key ] ) ? absint( $raw[ $key ] ) : 0;
			$valid   = $page_id > 0 && get_post( $page_id ) && 'trash' !== get_post_status( $page_id );

			if ( ! $force && $valid ) {
				$pages[ $key ] = $page_id;
				continue;
			}

			$existing = self::find_page_by_meta( $def['meta'] );
			if ( $existing > 0 && ( ! $force || $existing !== $page_id ) ) {
				$page_id = $existing;
				self::sync_page_content( $page_id, $def );
				$messages[] = sprintf(
					/* translators: %s: page title */
					__( 'Linked existing page: %s', 'flex-multiple-listing-and-booking-system' ),
					$def['title']
				);
			} else {
				$page_id = self::create_page( $def );
				if ( $page_id > 0 ) {
					++$created;
					$messages[] = sprintf(
						/* translators: %s: page title */
						__( 'Created page: %s', 'flex-multiple-listing-and-booking-system' ),
						$def['title']
					);
				}
			}

			if ( $page_id > 0 ) {
				$pages[ $key ] = $page_id;
				if ( ! isset( $raw[ $key ] ) || absint( $raw[ $key ] ) !== $page_id ) {
					$raw[ $key ] = $page_id;
					$updated     = true;
				}
			}
		}

		if ( $updated ) {
			update_option( 'fbs_general_settings', wp_json_encode( $raw ), false );
			LayoutSettings::clear_cache();
		}

		return array(
			'pages'    => $pages,
			'created'  => $created,
			'updated'  => $updated,
			'messages' => $messages,
		);
	}

	/**
	 * @param string $meta Portal page meta value.
	 * @return int
	 */
	private static function find_page_by_meta( $meta ) {
		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 1,
				'meta_key'       => self::PAGE_META,
				'meta_value'     => $meta,
				'fields'         => 'ids',
			)
		);

		return ! empty( $posts[0] ) ? absint( $posts[0] ) : 0;
	}

	/**
	 * @param array{title:string,slug:string,shortcode:string,meta:string} $def Definition.
	 * @return int
	 */
	private static function create_page( array $def ) {
		$page_id = wp_insert_post(
			array(
				'post_title'   => $def['title'],
				'post_name'    => $def['slug'],
				'post_content' => $def['shortcode'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		update_post_meta( (int) $page_id, self::PAGE_META, $def['meta'] );

		return (int) $page_id;
	}

	/**
	 * Ensure page content contains the expected shortcode.
	 *
	 * @param int                                                           $page_id Page ID.
	 * @param array{title:string,slug:string,shortcode:string,meta:string} $def     Definition.
	 * @return void
	 */
	private static function sync_page_content( $page_id, array $def ) {
		$post = get_post( $page_id );
		if ( ! $post ) {
			return;
		}

		$content = trim( (string) $post->post_content );
		if ( '' === $content || false === strpos( $content, $def['shortcode'] ) ) {
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => $def['shortcode'],
				)
			);
		}

		update_post_meta( $page_id, self::PAGE_META, $def['meta'] );
	}

	/**
	 * Summary for settings UI.
	 *
	 * @return array<string,array{title:string,shortcode:string,page_id:int,url:string,edit_url:string}>
	 */
	public static function status_rows() {
		$settings = VendorPages::settings();
		$rows     = array();

		foreach ( self::definitions() as $key => $def ) {
			$page_id = isset( $settings[ $key ] ) ? absint( $settings[ $key ] ) : 0;
			$url     = $page_id > 0 ? (string) get_permalink( $page_id ) : '';
			$rows[ $key ] = array(
				'title'     => $def['title'],
				'shortcode' => $def['shortcode'],
				'page_id'   => $page_id,
				'url'       => $url,
				'edit_url'  => $page_id > 0 ? (string) get_edit_post_link( $page_id, 'raw' ) : '',
			);
		}

		return $rows;
	}
}
