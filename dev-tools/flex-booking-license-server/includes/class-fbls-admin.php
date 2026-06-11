<?php
/**
 * wp-admin license management UI.
 *
 * @package FlexBookingLicenseServer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin screens for license CRUD.
 */
final class FBLS_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_post' ), 5 );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			__( 'Flex Licenses', 'flex-booking-license-server' ),
			__( 'Flex Licenses', 'flex-booking-license-server' ),
			'manage_options',
			'fbls-licenses',
			array( __CLASS__, 'render_list' ),
			'dashicons-admin-network',
			58
		);
	}

	/**
	 * Process create/update/delete before output.
	 *
	 * @return void
	 */
	public static function handle_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below per action.
		if ( empty( $_POST['fbls_action'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['fbls_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fbls_nonce'] ) ), 'fbls_admin' ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['fbls_action'] ) );
		$repo   = new FBLS_License_Repository();

		if ( 'create' === $action ) {
			$expires_days = isset( $_POST['expires_days'] ) ? sanitize_text_field( wp_unslash( $_POST['expires_days'] ) ) : '';
			$args         = array(
				'customer_email'   => isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '',
				'activation_limit' => isset( $_POST['activation_limit'] ) ? absint( wp_unslash( $_POST['activation_limit'] ) ) : 1,
				'notes'          => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			);
			if ( 'lifetime' !== $expires_days && '' !== $expires_days ) {
				$args['expires_days'] = max( 1, (int) $expires_days );
			}
			$id = $repo->create( $args );
			if ( $id ) {
				wp_safe_redirect( add_query_arg( array( 'page' => 'fbls-licenses', 'fbls_notice' => 'created', 'license_id' => $id ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		if ( 'update' === $action ) {
			$id = isset( $_POST['license_id'] ) ? absint( wp_unslash( $_POST['license_id'] ) ) : 0;
			if ( $id > 0 ) {
				$expires_days = isset( $_POST['expires_days'] ) ? sanitize_text_field( wp_unslash( $_POST['expires_days'] ) ) : 'keep';
				$data         = array(
					'customer_email'   => isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '',
					'status'           => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
					'activation_limit' => isset( $_POST['activation_limit'] ) ? absint( wp_unslash( $_POST['activation_limit'] ) ) : 1,
					'notes'            => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
				);
				if ( 'lifetime' === $expires_days ) {
					$data['expires_at'] = 0;
				} elseif ( 'keep' !== $expires_days && '' !== $expires_days ) {
					$data['expires_at'] = time() + ( max( 1, (int) $expires_days ) * DAY_IN_SECONDS );
				}
				$repo->update( $id, $data );
				wp_safe_redirect( add_query_arg( array( 'page' => 'fbls-licenses', 'fbls_notice' => 'updated', 'license_id' => $id ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		if ( 'delete' === $action ) {
			$id = isset( $_POST['license_id'] ) ? absint( wp_unslash( $_POST['license_id'] ) ) : 0;
			if ( $id > 0 ) {
				$repo->delete( $id );
				wp_safe_redirect( add_query_arg( array( 'page' => 'fbls-licenses', 'fbls_notice' => 'deleted' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		if ( 'delete_activation' === $action ) {
			$act_id = isset( $_POST['activation_id'] ) ? absint( wp_unslash( $_POST['activation_id'] ) ) : 0;
			$lic_id = isset( $_POST['license_id'] ) ? absint( wp_unslash( $_POST['license_id'] ) ) : 0;
			if ( $act_id > 0 ) {
				$repo->delete_activation( $act_id );
				wp_safe_redirect( add_query_arg( array( 'page' => 'fbls-licenses', 'license_id' => $lic_id, 'fbls_notice' => 'activation_removed' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}
	}

	/**
	 * List / detail screen.
	 *
	 * @return void
	 */
	public static function render_list() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden.', 'flex-booking-license-server' ) );
		}

		$repo = new FBLS_License_Repository();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view_id = isset( $_GET['license_id'] ) ? absint( wp_unslash( $_GET['license_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['fbls_notice'] ) ? sanitize_key( wp_unslash( $_GET['fbls_notice'] ) ) : '';

		$license     = $view_id ? $repo->get_by_id( $view_id ) : null;
		$activations = $license ? $repo->get_activations( $view_id ) : array();
		$page_data   = $repo->get_page( $paged, 20, $search );

		include FBLS_PLUGIN_DIR . 'templates/admin-licenses.php';
	}
}
