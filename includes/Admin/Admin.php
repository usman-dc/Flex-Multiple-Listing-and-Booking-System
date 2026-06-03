<?php
/**
 * Admin menu, Bootstrap 5 dashboard shell, asset pipeline.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Admin;

use FlexBooking\Booking\BookingAdminUpdater;
use FlexBooking\Booking\BookingRepository;
use FlexBooking\Booking\BookingTypeRepository;
use FlexBooking\Core\Capabilities;
use FlexBooking\Front\ColorSettings;
use FlexBooking\Core\Plugin;
use FlexBooking\Database\Schema;
use FlexBooking\Setup\IndustryCatalog;
use FlexBooking\Listings\ListingReviewRepository;

defined( 'ABSPATH' ) || exit;

/**
 * wp-admin integration.
 */
final class Admin {

	/**
	 * Kernel reference.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'admin_init', array( $this, 'capture_booking_type_form_post' ), 0 );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		AdminMenu::register();
		SettingsSave::register();
	}

	/**
	 * Whether to load Flex Booking admin assets on this screen.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return bool
	 */
	private function should_enqueue_admin_assets( $hook_suffix ) {
		if ( preg_match( '/(toplevel_page_fbs-dashboard|_page_fbs-)/', (string) $hook_suffix ) ) {
			return true;
		}

		if ( in_array( $hook_suffix, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && ! empty( $screen->post_type ) && 0 === strpos( (string) $screen->post_type, 'fbs_' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Mark Flex Booking wp-admin screens for layout CSS (full-width tables).
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		if ( ! is_string( $classes ) ) {
			$classes = '';
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->id ) ) {
			return $classes;
		}
		if ( preg_match( '/(toplevel_page_fbs-dashboard|_page_fbs-)/', (string) $screen->id ) ) {
			$classes .= ' fbs-admin-screen';
			return $classes;
		}
		if ( ! empty( $screen->post_type ) && 0 === strpos( (string) $screen->post_type, 'fbs_' ) ) {
			$classes .= ' fbs-admin-screen';
		}
		return $classes;
	}

	/**
	 * Register top-level menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$cap = Capabilities::menu_capability();

		add_menu_page(
			fbs_plugin_display_name(),
			fbs_plugin_menu_label(),
			$cap,
			'fbs-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			56
		);

		add_submenu_page(
			'fbs-dashboard',
			__( 'Dashboard', 'flex-booking-system' ),
			__( 'Dashboard', 'flex-booking-system' ),
			$cap,
			'fbs-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'fbs-dashboard',
			__( 'Booking Types', 'flex-booking-system' ),
			__( 'Booking Types', 'flex-booking-system' ),
			$cap,
			'fbs-booking-types',
			array( $this, 'render_booking_types' )
		);

		add_submenu_page(
			'fbs-dashboard',
			__( 'All Bookings', 'flex-booking-system' ),
			__( 'Bookings', 'flex-booking-system' ),
			$cap,
			'fbs-bookings',
			array( $this, 'render_bookings' )
		);

		add_submenu_page(
			'fbs-dashboard',
			__( 'Listing Reviews', 'flex-booking-system' ),
			__( 'Reviews', 'flex-booking-system' ),
			$cap,
			'fbs-reviews',
			array( $this, 'render_reviews' )
		);

		add_submenu_page(
			'fbs-dashboard',
			__( 'Settings', 'flex-booking-system' ),
			__( 'Settings', 'flex-booking-system' ),
			$cap,
			'fbs-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin bundle on plugin screens.
	 *
	 * @param string $hook_suffix Current screen.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( ! $this->should_enqueue_admin_assets( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'fbs-bootstrap',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
			array(),
			'5.3.3'
		);

		wp_enqueue_style(
			'fbs-bootstrap-icons',
			'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
			array(),
			'1.11.3'
		);

		wp_enqueue_style(
			'fbs-admin',
			FBS_PLUGIN_URL . 'dist/admin.css',
			array( 'fbs-bootstrap', 'fbs-bootstrap-icons' ),
			FBS_VERSION
		);

		wp_enqueue_script(
			'fbs-bootstrap',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
			array(),
			'5.3.3',
			true
		);

		wp_enqueue_script(
			'fbs-admin',
			FBS_PLUGIN_URL . 'dist/admin.js',
			array( 'fbs-bootstrap', 'jquery' ),
			FBS_VERSION,
			true
		);

		$admin_localize = array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( \FlexBooking\Security\Nonce::ACTION_AJAX ),
			'restUrl'   => esc_url_raw( rest_url( 'flex-booking/v1' ) ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
		);
		if ( false !== strpos( (string) $hook_suffix, 'fbs-settings' ) ) {
			$admin_localize['colorDefaults'] = ColorSettings::defaults();
		}
		wp_localize_script( 'fbs-admin', 'fbsAdmin', $admin_localize );
	}

	/**
	 * Dashboard view.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$booking_repo = new BookingRepository();
		$type_repo    = new BookingTypeRepository();

		$cutoff_mysql = wp_date( 'Y-m-d H:i:s', strtotime( '-30 days', (int) current_time( 'timestamp' ) ) );

		$all_types    = $type_repo->get_all();
		$type_names   = array();
		foreach ( $all_types as $t ) {
			$type_names[ (int) $t['id'] ] = (string) $t['name'];
		}

		$this->render_view(
			'dashboard',
			array(
				'fbs_stat_bookings_30d'   => $booking_repo->count_since( $cutoff_mysql ),
				'fbs_stat_revenue_30d'    => $booking_repo->sum_total_since( $cutoff_mysql ),
				'fbs_stat_bookings_all'   => $booking_repo->count_all(),
				'fbs_stat_types_count'    => $type_repo->count_all(),
				'fbs_stat_customers'      => $this->count_customers(),
				'fbs_daily_bookings'      => $booking_repo->daily_counts( 30 ),
				'fbs_daily_revenue'       => $booking_repo->daily_revenue( 30 ),
				'fbs_count_by_status'     => $booking_repo->count_by_status(),
				'fbs_count_by_type'       => $booking_repo->count_by_type(),
				'fbs_type_names'          => $type_names,
				'fbs_recent_bookings'     => $booking_repo->get_recent( 10 ),
				'fbs_recent_activity'     => AdminActivityLog::get_recent( 10 ),
				'fbs_activity_total'      => AdminActivityLog::count_all(),
			)
		);
	}

	/**
	 * Count total customers.
	 *
	 * @return int
	 */
	private function count_customers() {
		global $wpdb;
		$table = Schema::tables()['customers'];
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
	}

	/**
	 * Booking types view.
	 *
	 * @return void
	 */
	public function render_booking_types() {
		if ( ! Capabilities::can_access_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flex-booking-system' ) );
		}

		$type_repo = new BookingTypeRepository();

		$notice_code = isset( $_GET['fbs_notice'] ) ? sanitize_key( wp_unslash( $_GET['fbs_notice'] ) ) : '';

		$edit_id = isset( $_GET['fbs_edit'] ) ? absint( wp_unslash( $_GET['fbs_edit'] ) ) : 0;
		$is_new  = isset( $_GET['fbs_new'] ) && '1' === (string) wp_unslash( $_GET['fbs_new'] );

		$editing = null;
		if ( $edit_id > 0 ) {
			$editing = $type_repo->get_by_id( $edit_id );
			if ( ! $editing ) {
				$edit_id = 0;
			}
		}

		$show_form = ( $is_new && ! $editing ) || ( $edit_id > 0 && $editing );

		$this->render_view(
			'booking-types',
			array(
				'fbs_booking_types'   => $type_repo->get_all(),
				'fbs_types_total'     => $type_repo->count_all(),
				'fbs_type_notice'     => $notice_code,
				'fbs_editing_type'    => $editing,
				'fbs_show_type_form'  => $show_form,
				'fbs_industry_catalog' => IndustryCatalog::definitions(),
			)
		);
	}

	/**
	 * Handle booking type save/delete before any admin output (avoids header warnings on redirect).
	 *
	 * @return void
	 */
	public function capture_booking_type_form_post() {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'fbs-booking-types' !== (string) wp_unslash( $_GET['page'] ) ) {
			return;
		}
		if ( ! isset( $_POST['fbs_booking_types_nonce'] ) || ! isset( $_POST['fbs_type_action'] ) ) {
			return;
		}

		if ( ! Capabilities::can_access_admin() ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fbs_booking_types_nonce'] ) ), 'fbs_booking_types' ) ) {
			return;
		}

		$type_repo    = new BookingTypeRepository();
		$booking_repo = new BookingRepository();

		$this->process_booking_type_form_submission( $type_repo, $booking_repo );
	}

	/**
	 * POST handler for booking type create / update / delete (invoked from admin_init only).
	 *
	 * @param BookingTypeRepository $type_repo    Types table.
	 * @param BookingRepository     $booking_repo Bookings table.
	 * @return void
	 */
	private function process_booking_type_form_submission( BookingTypeRepository $type_repo, BookingRepository $booking_repo ) {

		$action = sanitize_key( wp_unslash( $_POST['fbs_type_action'] ) );

		if ( 'delete' === $action ) {
			$id = isset( $_POST['fbs_type_id'] ) ? absint( wp_unslash( $_POST['fbs_type_id'] ) ) : 0;
			if ( $id < 1 ) {
				$this->redirect_booking_types( 'delete_invalid' );
			}
			$n = $booking_repo->count_for_booking_type( $id );
			if ( $n > 0 ) {
				$this->redirect_booking_types( 'delete_has_bookings' );
			}
			if ( $type_repo->delete_row( $id ) ) {
				$this->redirect_booking_types( 'deleted' );
			}
			$this->redirect_booking_types( 'delete_failed' );
			return;
		}

		if ( 'save' !== $action ) {
			return;
		}

		$id = isset( $_POST['fbs_type_id'] ) ? absint( wp_unslash( $_POST['fbs_type_id'] ) ) : 0;

		$name = isset( $_POST['fbs_type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fbs_type_name'] ) ) : '';
		if ( '' === $name ) {
			$this->redirect_booking_types( 'error_name' );
		}

		$slug_in = isset( $_POST['fbs_type_slug'] ) ? sanitize_title( wp_unslash( $_POST['fbs_type_slug'] ) ) : '';
		$slug    = '' !== $slug_in ? $slug_in : sanitize_title( $name );
		if ( '' === $slug ) {
			$this->redirect_booking_types( 'error_slug' );
		}

		if ( $type_repo->slug_exists( $slug, $id ) ) {
			$this->redirect_booking_types( 'error_duplicate_slug' );
		}

		$description = isset( $_POST['fbs_type_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fbs_type_description'] ) ) : '';
		$industry    = isset( $_POST['fbs_type_industry'] ) ? sanitize_key( wp_unslash( $_POST['fbs_type_industry'] ) ) : '';
		$status      = isset( $_POST['fbs_type_status'] ) ? sanitize_key( wp_unslash( $_POST['fbs_type_status'] ) ) : 'publish';
		if ( ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$status = 'publish';
		}

		$settings_json = $this->booking_type_settings_json( $industry );

		if ( $id > 0 ) {
			$ok = $type_repo->update_row(
				$id,
				array(
					'name'        => $name,
					'slug'        => $slug,
					'description' => $description,
					'module_key'  => 'generic',
					'settings'    => $settings_json,
					'status'      => $status,
				)
			);
			$this->redirect_booking_types( $ok ? 'updated' : 'save_failed' );
			return;
		}

		$new_id = $type_repo->insert_row(
			array(
				'name'        => $name,
				'slug'        => $slug,
				'description' => $description,
				'module_key'  => 'generic',
				'settings'    => $settings_json,
				'status'      => $status,
			)
		);

		if ( $new_id > 0 ) {
			flush_rewrite_rules( false );
		}
		$this->redirect_booking_types( $new_id > 0 ? 'created' : 'save_failed' );
	}

	/**
	 * Build settings JSON for a booking type from industry key.
	 *
	 * @param string $industry Industry catalog key or generic.
	 * @return string JSON.
	 */
	private function booking_type_settings_json( $industry ) {
		$industry = sanitize_key( (string) $industry );
		if ( '' === $industry || 'generic' === $industry ) {
			return wp_json_encode(
				array(
					'industry' => 'generic',
					'mode'     => 'daily',
				)
			);
		}

		$def = IndustryCatalog::get( $industry );
		if ( null === $def ) {
			return wp_json_encode(
				array(
					'industry' => 'generic',
					'mode'     => 'daily',
				)
			);
		}

		IndustryProvisioner::ensure_industry_enabled( $industry );

		return wp_json_encode(
			array(
				'industry'   => $industry,
				'post_type'  => $def['post_type'],
				'mode'       => 'daily',
				'admin_seed' => true,
			)
		);
	}

	/**
	 * Redirect back to booking types list with notice code.
	 *
	 * @param string $code Notice slug.
	 * @return void
	 */
	private function redirect_booking_types( $code ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'fbs-booking-types',
					'fbs_notice' => sanitize_key( (string) $code ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Bookings list placeholder SPA shell.
	 *
	 * @return void
	 */
	public function render_bookings() {
		$booking_repo = new BookingRepository();
		$paged        = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page     = (int) apply_filters( 'fbs_admin_bookings_per_page', 50 );
		$per_page     = min( 200, max( 1, $per_page ) );

		$status_filter = isset( $_GET['fbs_status'] ) ? sanitize_key( wp_unslash( $_GET['fbs_status'] ) ) : '';
		if ( '' !== $status_filter && ! in_array( $status_filter, BookingAdminUpdater::booking_statuses(), true ) ) {
			$status_filter = '';
		}

		$type_filter = isset( $_GET['fbs_type'] ) ? absint( wp_unslash( $_GET['fbs_type'] ) ) : 0;
		$type_repo   = new BookingTypeRepository();
		$all_types      = $type_repo->get_all();
		$fbs_type_names = array();
		foreach ( $all_types as $tr ) {
			$fbs_type_names[ (int) $tr['id'] ] = (string) $tr['name'];
		}
		if ( $type_filter > 0 ) {
			$found = false;
			foreach ( $all_types as $tr ) {
				if ( (int) $tr['id'] === $type_filter ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$type_filter = 0;
			}
		}

		$total       = $booking_repo->count_all( $status_filter, $type_filter );
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

		if ( $paged > $total_pages ) {
			$paged = $total_pages;
		}

		$rows = $booking_repo->get_page( $paged, $per_page, $status_filter, $type_filter );
		$cids = array();
		foreach ( $rows as $r ) {
			if ( ! empty( $r['customer_id'] ) ) {
				$cids[] = (int) $r['customer_id'];
			}
		}
		$fbs_customer_emails = $booking_repo->get_customer_emails_by_ids( $cids );

		$bids                 = array_column( $rows, 'id' );
		$fbs_booking_answers = $booking_repo->get_form_values_for_bookings( $bids );

		$this->render_view(
			'bookings',
			array(
				'fbs_bookings'             => $rows,
				'fbs_bookings_total'       => $total,
				'fbs_bookings_paged'       => $paged,
				'fbs_bookings_per_page'    => $per_page,
				'fbs_bookings_total_pages' => $total_pages,
				'fbs_status_filter'        => $status_filter,
				'fbs_type_filter'          => $type_filter,
				'fbs_booking_type_options' => $all_types,
				'fbs_type_names'           => $fbs_type_names,
				'fbs_customer_emails'      => $fbs_customer_emails,
				'fbs_booking_answers'      => $fbs_booking_answers,
			)
		);
	}

	/**
	 * Listing reviews moderation.
	 *
	 * @return void
	 */
	public function render_reviews() {
		if ( ! Capabilities::can_access_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flex-booking-system' ) );
		}

		$repo     = new ListingReviewRepository();
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page = (int) apply_filters( 'fbs_admin_reviews_per_page', 30 );
		$per_page = min( 100, max( 1, $per_page ) );

		$status_filter = isset( $_GET['fbs_status'] ) ? sanitize_key( wp_unslash( $_GET['fbs_status'] ) ) : '';
		if ( '' !== $status_filter && ! in_array( $status_filter, array( 'pending', 'approved', 'rejected' ), true ) ) {
			$status_filter = '';
		}

		$total       = $repo->count_all( $status_filter );
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
		if ( $paged > $total_pages ) {
			$paged = $total_pages;
		}

		$this->render_view(
			'reviews',
			array(
				'fbs_reviews'               => $repo->get_page( $paged, $per_page, $status_filter ),
				'fbs_reviews_total'         => $total,
				'fbs_reviews_paged'         => $paged,
				'fbs_reviews_per_page'      => $per_page,
				'fbs_reviews_total_pages'   => $total_pages,
				'fbs_reviews_status_filter' => $status_filter,
			)
		);
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		$this->render_view( 'settings' );
	}

	/**
	 * Include admin template.
	 *
	 * @param string $view View basename without .php.
	 * @return void
	 */
	private function render_view( $view, array $vars = array() ) {
		$path = FBS_PLUGIN_DIR . 'templates/admin/' . $view . '.php';
		if ( is_readable( $path ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped template variables for admin views only.
			extract( $vars, EXTR_SKIP );
			include $path;
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( fbs_plugin_display_name() ) . '</h1><p>' . esc_html__( 'Missing template.', 'flex-booking-system' ) . '</p></div>';
	}
}
