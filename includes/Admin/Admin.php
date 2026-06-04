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
use FlexBooking\Assets\VendorAssets;
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
		if ( preg_match( '/(toplevel_page_ulbm-dashboard|_page_ulbm-)/', (string) $hook_suffix ) ) {
			return true;
		}

		if ( in_array( $hook_suffix, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && ! empty( $screen->post_type ) && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( (string) $screen->post_type ) ) {
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
		if ( preg_match( '/(toplevel_page_ulbm-dashboard|_page_ulbm-)/', (string) $screen->id ) ) {
			$classes .= ' ulbm-admin-screen';
			return $classes;
		}
		if ( ! empty( $screen->post_type ) && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( (string) $screen->post_type ) ) {
			$classes .= ' ulbm-admin-screen';
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
			ulbm_plugin_display_name(),
			ulbm_plugin_menu_label(),
			$cap,
			'ulbm-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			56
		);

		add_submenu_page(
			'ulbm-dashboard',
			__( 'Dashboard', 'flex-multiple-listing-and-booking-system' ),
			__( 'Dashboard', 'flex-multiple-listing-and-booking-system' ),
			$cap,
			'ulbm-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'ulbm-dashboard',
			__( 'Booking Types', 'flex-multiple-listing-and-booking-system' ),
			__( 'Booking Types', 'flex-multiple-listing-and-booking-system' ),
			$cap,
			'ulbm-booking-types',
			array( $this, 'render_booking_types' )
		);

		add_submenu_page(
			'ulbm-dashboard',
			__( 'All Bookings', 'flex-multiple-listing-and-booking-system' ),
			__( 'Bookings', 'flex-multiple-listing-and-booking-system' ),
			$cap,
			'ulbm-bookings',
			array( $this, 'render_bookings' )
		);

		add_submenu_page(
			'ulbm-dashboard',
			__( 'Listing Reviews', 'flex-multiple-listing-and-booking-system' ),
			__( 'Reviews', 'flex-multiple-listing-and-booking-system' ),
			$cap,
			'ulbm-reviews',
			array( $this, 'render_reviews' )
		);

		add_submenu_page(
			'ulbm-dashboard',
			__( 'Settings', 'flex-multiple-listing-and-booking-system' ),
			__( 'Settings', 'flex-multiple-listing-and-booking-system' ),
			$cap,
			'ulbm-settings',
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

		VendorAssets::register_bootstrap();

		wp_enqueue_style( 'ulbm-bootstrap' );
		wp_enqueue_style( 'ulbm-bootstrap-icons' );

		wp_enqueue_style(
			'ulbm-admin',
			ULBM_PLUGIN_URL . 'dist/admin.css',
			array( 'ulbm-bootstrap', 'ulbm-bootstrap-icons' ),
			ULBM_VERSION
		);

		wp_enqueue_script( 'ulbm-bootstrap' );

		wp_enqueue_script(
			'ulbm-admin',
			ULBM_PLUGIN_URL . 'dist/admin.js',
			array( 'ulbm-bootstrap', 'jquery' ),
			ULBM_VERSION,
			true
		);

		$admin_localize = array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( \FlexBooking\Security\Nonce::ACTION_AJAX ),
			'restUrl'   => esc_url_raw( rest_url( 'ulbm/v1' ) ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
		);
		if ( false !== strpos( (string) $hook_suffix, 'ulbm-settings' ) ) {
			$admin_localize['colorDefaults'] = ColorSettings::defaults();
		}
		wp_localize_script( 'ulbm-admin', 'ulbmAdmin', $admin_localize );

		if ( false !== strpos( (string) $hook_suffix, 'ulbm-booking-types' ) ) {
			wp_enqueue_script(
				'ulbm-admin-booking-types-quick-add',
				ULBM_PLUGIN_URL . 'dist/admin-booking-types-quick-add.js',
				array(),
				ULBM_VERSION,
				true
			);
			wp_localize_script(
				'ulbm-admin-booking-types-quick-add',
				'ulbmAdmin',
				array_merge(
					$admin_localize,
					array(
						'quickAddCreating' => __( 'Creating...', 'flex-multiple-listing-and-booking-system' ),
						'quickAddFailed'   => __( 'Request failed.', 'flex-multiple-listing-and-booking-system' ),
					)
				)
			);
		}
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

		$this->enqueue_dashboard_charts(
			$booking_repo->daily_counts( 30 ),
			$booking_repo->daily_revenue( 30 ),
			$booking_repo->count_by_status(),
			$booking_repo->count_by_type(),
			$type_names
		);

		$this->render_view(
			'dashboard',
			array(
				'ulbm_stat_bookings_30d'   => $booking_repo->count_since( $cutoff_mysql ),
				'ulbm_stat_revenue_30d'    => $booking_repo->sum_total_since( $cutoff_mysql ),
				'ulbm_stat_bookings_all'   => $booking_repo->count_all(),
				'ulbm_stat_types_count'    => $type_repo->count_all(),
				'ulbm_stat_customers'      => $this->count_customers(),
				'ulbm_daily_bookings'      => $booking_repo->daily_counts( 30 ),
				'ulbm_daily_revenue'       => $booking_repo->daily_revenue( 30 ),
				'ulbm_count_by_status'     => $booking_repo->count_by_status(),
				'ulbm_count_by_type'       => $booking_repo->count_by_type(),
				'ulbm_type_names'          => $type_names,
				'ulbm_recent_bookings'     => $booking_repo->get_recent( 10 ),
				'ulbm_recent_activity'     => AdminActivityLog::get_recent( 10 ),
				'ulbm_activity_total'      => AdminActivityLog::count_all(),
			)
		);
	}

	/**
	 * Enqueue Chart.js and dashboard chart script (late enqueue on dashboard screen).
	 *
	 * @param array<string, int>   $daily_bookings Bookings per day (Y-m-d => count).
	 * @param array<string, float> $daily_revenue  Revenue per day.
	 * @param array<string, int>   $count_by_status Status => count.
	 * @param array<int, int>      $count_by_type   Type id => count.
	 * @param array<int, string>   $type_names      Type id => label.
	 * @return void
	 */
	private function enqueue_dashboard_charts( $daily_bookings, $daily_revenue, $count_by_status, $count_by_type, $type_names ) {
		$labels   = array();
		$bookings = array();
		$revenue  = array();

		for ( $i = 29; $i >= 0; $i-- ) {
			$d          = wp_date( 'Y-m-d', strtotime( "-{$i} days", (int) current_time( 'timestamp' ) ) );
			$labels[]   = wp_date( 'M j', strtotime( $d ) );
			$bookings[] = isset( $daily_bookings[ $d ] ) ? (int) $daily_bookings[ $d ] : 0;
			$revenue[]  = isset( $daily_revenue[ $d ] ) ? (float) $daily_revenue[ $d ] : 0;
		}

		$status_labels = array();
		$status_counts = array();
		$status_colors = array();
		$color_map     = array(
			'pending'   => '#ffc107',
			'confirmed' => '#198754',
			'completed' => '#0d6efd',
			'cancelled' => '#dc3545',
			'rejected'  => '#6c757d',
			'on_hold'   => '#fd7e14',
		);
		foreach ( $count_by_status as $st => $cnt ) {
			$status_labels[] = ucfirst( (string) $st );
			$status_counts[] = (int) $cnt;
			$status_colors[] = $color_map[ $st ] ?? '#adb5bd';
		}

		$type_labels = array();
		$type_counts = array();
		foreach ( $count_by_type as $tid => $cnt ) {
			$type_labels[] = isset( $type_names[ $tid ] ) ? $type_names[ $tid ] : '#' . $tid;
			$type_counts[] = (int) $cnt;
		}

		$general  = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
		$currency = is_array( $general ) && ! empty( $general['currency'] ) ? (string) $general['currency'] : 'USD';

		wp_enqueue_script(
			'ulbm-chartjs',
			ULBM_PLUGIN_URL . 'assets/vendor/chart.umd.min.js',
			array(),
			'4.5.1',
			true
		);

		wp_enqueue_script(
			'ulbm-dashboard-charts',
			ULBM_PLUGIN_URL . 'assets/js/dashboard-charts.js',
			array( 'ulbm-chartjs' ),
			ULBM_VERSION,
			true
		);

		wp_localize_script(
			'ulbm-dashboard-charts',
			'fbsDashboardCharts',
			array(
				'labels'        => $labels,
				'bookings'      => $bookings,
				'revenue'       => $revenue,
				'bookingsLabel' => __( 'Bookings', 'flex-multiple-listing-and-booking-system' ),
				'revenueLabel'  => sprintf(
					/* translators: %s: currency code */
					__( 'Revenue (%s)', 'flex-multiple-listing-and-booking-system' ),
					$currency
				),
				'currency'      => $currency,
				'statusLabels'  => $status_labels,
				'statusCounts'  => $status_counts,
				'statusColors'  => $status_colors,
				'typeLabels'    => $type_labels,
				'typeCounts'    => $type_counts,
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
		$table = Schema::table( 'customers' );
		if ( '' === $table ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	}

	/**
	 * Booking types view.
	 *
	 * @return void
	 */
	public function render_booking_types() {
		if ( ! Capabilities::can_access_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flex-multiple-listing-and-booking-system' ) );
		}

		$type_repo = new BookingTypeRepository();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin notice display after redirect.
		$notice_code = isset( $_GET['ulbm_notice'] ) ? sanitize_key( wp_unslash( $_GET['ulbm_notice'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin edit screen routing.
		$edit_id = isset( $_GET['ulbm_edit'] ) ? absint( wp_unslash( $_GET['ulbm_edit'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin new-item routing.
		$ulbm_new_flag = isset( $_GET['ulbm_new'] ) ? sanitize_text_field( wp_unslash( $_GET['ulbm_new'] ) ) : '';
		$is_new        = '1' === $ulbm_new_flag;

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
				'ulbm_booking_types'   => $type_repo->get_all(),
				'ulbm_types_total'     => $type_repo->count_all(),
				'ulbm_type_notice'     => $notice_code,
				'ulbm_editing_type'    => $editing,
				'ulbm_show_type_form'  => $show_form,
				'ulbm_industry_catalog' => IndustryCatalog::definitions(),
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin screen routing only.
		if ( ! isset( $_GET['page'] ) || 'ulbm-booking-types' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified before processing.
		if ( ! isset( $_POST['ulbm_booking_types_nonce'] ) || ! isset( $_POST['ulbm_type_action'] ) ) {
			return;
		}

		if ( ! Capabilities::can_access_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on next line.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ulbm_booking_types_nonce'] ) ), 'ulbm_booking_types' ) ) {
			return;
		}

		\FlexBooking\Security\PostData::allow_processing();

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

		$action = \FlexBooking\Security\PostData::key( 'ulbm_type_action' );

		if ( 'delete' === $action ) {
			$id = \FlexBooking\Security\PostData::int( 'ulbm_type_id' );
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

		$id = \FlexBooking\Security\PostData::int( 'ulbm_type_id' );

		$name = \FlexBooking\Security\PostData::string( 'ulbm_type_name' );
		if ( '' === $name ) {
			$this->redirect_booking_types( 'error_name' );
		}

		$slug_in = \FlexBooking\Security\PostData::has( 'ulbm_type_slug' ) ? sanitize_title( (string) \FlexBooking\Security\PostData::raw( 'ulbm_type_slug' ) ) : '';
		$slug    = '' !== $slug_in ? $slug_in : sanitize_title( $name );
		if ( '' === $slug ) {
			$this->redirect_booking_types( 'error_slug' );
		}

		if ( $type_repo->slug_exists( $slug, $id ) ) {
			$this->redirect_booking_types( 'error_duplicate_slug' );
		}

		$description = \FlexBooking\Security\PostData::has( 'ulbm_type_description' )
			? sanitize_textarea_field( (string) \FlexBooking\Security\PostData::raw( 'ulbm_type_description' ) )
			: '';
		$industry    = \FlexBooking\Security\PostData::key( 'ulbm_type_industry' );
		$status      = \FlexBooking\Security\PostData::has( 'ulbm_type_status' )
			? \FlexBooking\Security\PostData::key( 'ulbm_type_status', 'publish' )
			: 'publish';
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
					'page'       => 'ulbm-booking-types',
					'ulbm_notice' => sanitize_key( (string) $code ),
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List table pagination.
		$paged        = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page     = (int) apply_filters( 'ulbm_admin_bookings_per_page', 50 );
		$per_page     = min( 200, max( 1, $per_page ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter query arg.
		$status_filter = isset( $_GET['ulbm_status'] ) ? sanitize_key( wp_unslash( $_GET['ulbm_status'] ) ) : '';
		if ( '' !== $status_filter && ! in_array( $status_filter, BookingAdminUpdater::booking_statuses(), true ) ) {
			$status_filter = '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter query arg.
		$type_filter = isset( $_GET['ulbm_type'] ) ? absint( wp_unslash( $_GET['ulbm_type'] ) ) : 0;
		$type_repo   = new BookingTypeRepository();
		$all_types      = $type_repo->get_all();
		$ulbm_type_names = array();
		foreach ( $all_types as $tr ) {
			$ulbm_type_names[ (int) $tr['id'] ] = (string) $tr['name'];
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
		$ulbm_customer_emails = $booking_repo->get_customer_emails_by_ids( $cids );

		$bids                 = array_column( $rows, 'id' );
		$ulbm_booking_answers = $booking_repo->get_form_values_for_bookings( $bids );

		$this->render_view(
			'bookings',
			array(
				'ulbm_bookings'             => $rows,
				'ulbm_bookings_total'       => $total,
				'ulbm_bookings_paged'       => $paged,
				'ulbm_bookings_per_page'    => $per_page,
				'ulbm_bookings_total_pages' => $total_pages,
				'ulbm_status_filter'        => $status_filter,
				'ulbm_type_filter'          => $type_filter,
				'ulbm_booking_type_options' => $all_types,
				'ulbm_type_names'           => $ulbm_type_names,
				'ulbm_customer_emails'      => $ulbm_customer_emails,
				'ulbm_booking_answers'      => $ulbm_booking_answers,
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flex-multiple-listing-and-booking-system' ) );
		}

		$repo     = new ListingReviewRepository();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List table pagination.
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page = (int) apply_filters( 'ulbm_admin_reviews_per_page', 30 );
		$per_page = min( 100, max( 1, $per_page ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter query arg.
		$status_filter = isset( $_GET['ulbm_status'] ) ? sanitize_key( wp_unslash( $_GET['ulbm_status'] ) ) : '';
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
				'ulbm_reviews'               => $repo->get_page( $paged, $per_page, $status_filter ),
				'ulbm_reviews_total'         => $total,
				'ulbm_reviews_paged'         => $paged,
				'ulbm_reviews_per_page'      => $per_page,
				'ulbm_reviews_total_pages'   => $total_pages,
				'ulbm_reviews_status_filter' => $status_filter,
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
		$path = ULBM_PLUGIN_DIR . 'templates/admin/' . $view . '.php';
		if ( is_readable( $path ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped template variables for admin views only.
			extract( $vars, EXTR_SKIP );
			include $path;
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( ulbm_plugin_display_name() ) . '</h1><p>' . esc_html__( 'Missing template.', 'flex-multiple-listing-and-booking-system' ) . '</p></div>';
	}
}
