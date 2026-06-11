<?php
/**
 * Optional WooCommerce auto-license on order complete.
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates license keys when licensed WooCommerce products are purchased.
 */
final class FBLS_WooCommerce {

	/**
	 * Product meta key — enable on product edit screen.
	 */
	public const PRODUCT_META = '_fbls_generate_license';

	/**
	 * Register hooks when WooCommerce is active.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_field' ) );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'order_completed' ), 10, 1 );
		add_action( 'woocommerce_email_order_details', array( __CLASS__, 'email_license_keys' ), 20, 4 );
	}

	/**
	 * Checkbox on product edit.
	 *
	 * @return void
	 */
	public static function product_field() {
		woocommerce_wp_checkbox(
			array(
				'id'          => self::PRODUCT_META,
				'label'       => __( 'Flex license key', 'flex-booking-license-server' ),
				'description' => __( 'Generate a license key when this product is purchased.', 'flex-booking-license-server' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_fbls_activation_limit',
				'label'             => __( 'Activation limit', 'flex-booking-license-server' ),
				'type'              => 'number',
				'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
				'description'       => __( '0 = unlimited sites.', 'flex-booking-license-server' ),
				'value'             => get_post_meta( get_the_ID(), '_fbls_activation_limit', true ) ?: '1',
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_fbls_license_days',
				'label'       => __( 'License days', 'flex-booking-license-server' ),
				'type'        => 'number',
				'description' => __( 'Leave empty for lifetime license.', 'flex-booking-license-server' ),
				'value'       => get_post_meta( get_the_ID(), '_fbls_license_days', true ),
			)
		);
	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public static function save_product_field( $post_id ) {
		$enabled = isset( $_POST[ self::PRODUCT_META ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, self::PRODUCT_META, $enabled );

		if ( isset( $_POST['_fbls_activation_limit'] ) ) {
			update_post_meta( $post_id, '_fbls_activation_limit', absint( wp_unslash( $_POST['_fbls_activation_limit'] ) ) );
		}
		if ( isset( $_POST['_fbls_license_days'] ) ) {
			$days = sanitize_text_field( wp_unslash( $_POST['_fbls_license_days'] ) );
			update_post_meta( $post_id, '_fbls_license_days', '' === $days ? '' : absint( $days ) );
		}
	}

	/**
	 * Generate licenses for order line items.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function order_completed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$existing = $order->get_meta( '_fbls_licenses_created' );
		if ( $existing ) {
			return;
		}

		$repo   = new FBLS_License_Repository();
		$keys   = array();
		$email  = $order->get_billing_email();

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( 'yes' !== get_post_meta( $product_id, self::PRODUCT_META, true ) ) {
				continue;
			}

			$limit = (int) get_post_meta( $product_id, '_fbls_activation_limit', true );
			$days  = get_post_meta( $product_id, '_fbls_license_days', true );

			$args = array(
				'customer_email'   => $email,
				'activation_limit' => $limit > 0 ? $limit : 1,
				'order_id'         => $order_id,
				'notes'            => sprintf( 'WooCommerce order #%d', $order_id ),
			);

			if ( '' !== $days && is_numeric( $days ) ) {
				$args['expires_days'] = (int) $days;
			}

			$qty = max( 1, (int) $item->get_quantity() );
			for ( $i = 0; $i < $qty; $i++ ) {
				$lic_id = $repo->create( $args );
				if ( $lic_id ) {
					$row = $repo->get_by_id( $lic_id );
					if ( $row ) {
						$keys[] = (string) $row['license_key'];
					}
				}
			}
		}

		if ( ! empty( $keys ) ) {
			$order->update_meta_data( '_fbls_license_keys', $keys );
			$order->update_meta_data( '_fbls_licenses_created', '1' );
			$order->save();
		}
	}

	/**
	 * Append license keys to order emails.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $sent_to_admin Admin email.
	 * @param bool     $plain_text Plain text.
	 * @param WC_Email $email Email object.
	 * @return void
	 */
	public static function email_license_keys( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $sent_to_admin || ! $order instanceof WC_Order ) {
			return;
		}

		$keys = $order->get_meta( '_fbls_license_keys' );
		if ( ! is_array( $keys ) || empty( $keys ) ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n\n" . esc_html__( 'Your license key(s):', 'flex-booking-license-server' ) . "\n";
			foreach ( $keys as $key ) {
				echo esc_html( $key ) . "\n";
			}
			return;
		}

		echo '<h2>' . esc_html__( 'Your license key(s)', 'flex-booking-license-server' ) . '</h2>';
		echo '<p><code style="font-size:14px;">' . esc_html( implode( '</code></p><p><code style="font-size:14px;">', $keys ) ) . '</code></p>';
	}
}
