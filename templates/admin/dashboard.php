<?php
/**
 * Admin dashboard — KPI cards, quick actions, compact charts.
 *
 * @package FlexBookingSystem
 *
 * @var int                    $ulbm_stat_bookings_30d
 * @var float                  $ulbm_stat_revenue_30d
 * @var int                    $ulbm_stat_bookings_all
 * @var int                    $ulbm_stat_types_count
 * @var int                    $ulbm_stat_customers
 * @var array<string, int>     $ulbm_daily_bookings
 * @var array<string, float>   $ulbm_daily_revenue
 * @var array<string, int>     $ulbm_count_by_status
 * @var array<int, int>        $ulbm_count_by_type
 * @var array<int, string>     $ulbm_type_names
 * @var array                  $ulbm_recent_bookings
 * @var array                  $ulbm_recent_activity
 * @var int                    $ulbm_activity_total
 */

defined( 'ABSPATH' ) || exit;

$ulbm_general  = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
$ulbm_currency = is_array( $ulbm_general ) && ! empty( $ulbm_general['currency'] ) ? $ulbm_general['currency'] : 'USD';

$ulbm_settings_url = static function ( $tab = 'general' ) {
	return admin_url( 'admin.php?page=ulbm-settings&tab=' . sanitize_key( $tab ) );
};

$ulbm_dash_status_class = static function ( $status ) {
	$s = strtolower( (string) $status );
	if ( in_array( $s, array( 'confirmed', 'completed', 'approved', 'paid' ), true ) ) {
		return 'success';
	}
	if ( in_array( $s, array( 'cancelled', 'canceled', 'refunded', 'rejected', 'unpaid' ), true ) ) {
		return 'danger';
	}
	if ( in_array( $s, array( 'pending', 'hold', 'draft' ), true ) ) {
		return 'warning';
	}
	return 'secondary';
};

$ulbm_dash_customer_label = static function ( $row ) {
	if ( ! empty( $row['wp_user_id'] ) ) {
		$user = get_userdata( (int) $row['wp_user_id'] );
		if ( $user && $user->display_name ) {
			return (string) $user->display_name;
		}
	}
	if ( ! empty( $row['customer_id'] ) ) {
		global $wpdb;
		$table = \FlexBooking\Database\Schema::table( 'customers' );
		if ( $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$customer = $wpdb->get_row(
				$wpdb->prepare( 'SELECT first_name, last_name, email FROM %i WHERE id = %d LIMIT 1', $table, (int) $row['customer_id'] ),
				ARRAY_A
			);
			if ( is_array( $customer ) ) {
				$name = trim( (string) $customer['first_name'] . ' ' . (string) $customer['last_name'] );
				if ( '' !== $name ) {
					return $name;
				}
				if ( ! empty( $customer['email'] ) ) {
					return (string) $customer['email'];
				}
			}
		}
	}
	return __( 'Guest', 'flex-multiple-listing-and-booking-system' );
};

$ulbm_dash_initials = static function ( $name ) {
	$parts = preg_split( '/\s+/', trim( (string) $name ) );
	if ( ! is_array( $parts ) || empty( $parts[0] ) ) {
		return 'G';
	}
	$first = strtoupper( substr( $parts[0], 0, 1 ) );
	$last  = count( $parts ) > 1 ? strtoupper( substr( $parts[ count( $parts ) - 1 ], 0, 1 ) ) : '';
	return $first . $last;
};

// Status donut data.
$ulbm_status_labels = array();
$ulbm_status_counts = array();
$ulbm_status_colors = array();
$ulbm_status_total  = 0;
$ulbm_color_map     = array(
	'pending'   => '#3b82f6',
	'confirmed' => '#22c55e',
	'completed' => '#4f46e5',
	'cancelled' => '#f97316',
	'rejected'  => '#94a3b8',
	'on_hold'   => '#eab308',
);
foreach ( $ulbm_count_by_status as $ulbm_st => $ulbm_cnt ) {
	$ulbm_status_labels[] = ucfirst( (string) $ulbm_st );
	$ulbm_status_counts[] = (int) $ulbm_cnt;
	$ulbm_status_colors[] = $ulbm_color_map[ $ulbm_st ] ?? '#cbd5e1';
	$ulbm_status_total   += (int) $ulbm_cnt;
}

$ulbm_listings_url    = admin_url( 'admin.php?page=ulbm-booking-types' );
$ulbm_add_listing_url = $ulbm_listings_url;
foreach ( \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types() as $ulbm_listing_type ) {
	$ulbm_listing_pt = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $ulbm_listing_type['slug'] );
	if ( post_type_exists( $ulbm_listing_pt ) ) {
		$ulbm_listings_url    = admin_url( 'edit.php?post_type=' . $ulbm_listing_pt );
		$ulbm_add_listing_url = admin_url( 'post-new.php?post_type=' . $ulbm_listing_pt );
		break;
	}
}

$ulbm_dash_actions = array(
	array(
		'label' => __( 'Import Demo', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_settings_url( 'demo' ),
		'icon'  => 'bi-download',
		'tone'  => 'green',
	),
	array(
		'label' => __( 'Change Colors', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_settings_url( 'colors' ),
		'icon'  => 'bi-palette',
		'tone'  => 'pink',
	),
	array(
		'label' => __( 'Layout Options', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_settings_url( 'layout' ),
		'icon'  => 'bi-layout-three-columns',
		'tone'  => 'blue',
	),
	array(
		'label' => __( 'Partner Pages', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_settings_url( 'partner' ),
		'icon'  => 'bi-people',
		'tone'  => 'teal',
	),
	array(
		'label' => __( 'All Bookings', 'flex-multiple-listing-and-booking-system' ),
		'url'   => admin_url( 'admin.php?page=ulbm-bookings' ),
		'icon'  => 'bi-calendar2-check',
		'tone'  => 'navy',
	),
	array(
		'label' => __( 'Add Listing', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_add_listing_url,
		'icon'  => 'bi-plus-circle',
		'tone'  => 'indigo',
	),
	array(
		'label' => __( 'Booking Types', 'flex-multiple-listing-and-booking-system' ),
		'url'   => admin_url( 'admin.php?page=ulbm-booking-types' ),
		'icon'  => 'bi-tags',
		'tone'  => 'cyan',
	),
	array(
		'label' => __( 'Reviews', 'flex-multiple-listing-and-booking-system' ),
		'url'   => admin_url( 'admin.php?page=ulbm-reviews' ),
		'icon'  => 'bi-star',
		'tone'  => 'orange',
	),
	array(
		'label' => __( 'Partners', 'flex-multiple-listing-and-booking-system' ),
		'url'   => admin_url( 'admin.php?page=ulbm-partners' ),
		'icon'  => 'bi-person-badge',
		'tone'  => 'violet',
	),
	array(
		'label' => __( 'Settings', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_settings_url( 'general' ),
		'icon'  => 'bi-gear',
		'tone'  => 'slate',
	),
	array(
		'label' => __( 'Shortcodes', 'flex-multiple-listing-and-booking-system' ),
		'url'   => $ulbm_settings_url( 'shortcodes' ),
		'icon'  => 'bi-code-slash',
		'tone'  => 'sky',
	),
	array(
		'label' => __( 'View Site', 'flex-multiple-listing-and-booking-system' ),
		'url'   => home_url( '/' ),
		'icon'  => 'bi-box-arrow-up-right',
		'tone'  => 'gray',
		'attrs' => ' target="_blank" rel="noopener noreferrer"',
	),
);
?>

<div class="wrap ulbm-admin-wrap ulbm-dashboard-v2 container-fluid py-3">
	<div class="ulbm-page-header d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
		<div>
			<h1 class="h3 mb-1 ulbm-page-title"><?php esc_html_e( 'Dashboard', 'flex-multiple-listing-and-booking-system' ); ?></h1>
			<p class="text-muted small mb-0"><?php esc_html_e( 'Manage your listings, bookings, and partner portal from one place.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		</div>
		<span class="badge ulbm-version-badge"><?php echo esc_html( ULBM_VERSION ); ?></span>
	</div>

	<div class="row g-3 mb-4 ulbm-dash-kpis align-items-start">
		<div class="col-6 col-xl-3">
			<div class="ulbm-stat-card ulbm-dash-stat">
				<div class="ulbm-dash-stat-top">
					<span class="ulbm-dash-stat-icon"><i class="bi bi-calendar-check"></i></span>
					<span class="ulbm-dash-stat-label"><?php esc_html_e( 'Bookings (30d)', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="ulbm-dash-stat-value mb-0"><?php echo esc_html( (string) (int) $ulbm_stat_bookings_30d ); ?></p>
				<div class="ulbm-stat-spark-wrap">
					<canvas class="ulbm-stat-spark" id="ulbm-spark-bookings" width="320" height="40" aria-hidden="true"></canvas>
				</div>
			</div>
		</div>
		<div class="col-6 col-xl-3">
			<div class="ulbm-stat-card ulbm-dash-stat ulbm-dash-stat--featured">
				<div class="ulbm-dash-stat-top">
					<span class="ulbm-dash-stat-icon"><i class="bi bi-currency-dollar"></i></span>
					<span class="ulbm-dash-stat-label"><?php esc_html_e( 'Revenue', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="ulbm-dash-stat-value mb-0"><?php echo esc_html( number_format_i18n( (float) $ulbm_stat_revenue_30d, 0 ) ); ?></p>
				<div class="ulbm-stat-spark-wrap">
					<canvas class="ulbm-stat-spark" id="ulbm-spark-revenue" width="320" height="40" aria-hidden="true"></canvas>
				</div>
			</div>
		</div>
		<div class="col-6 col-xl-3">
			<div class="ulbm-stat-card ulbm-dash-stat">
				<div class="ulbm-dash-stat-top">
					<span class="ulbm-dash-stat-icon"><i class="bi bi-people"></i></span>
					<span class="ulbm-dash-stat-label"><?php esc_html_e( 'Customers', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="ulbm-dash-stat-value mb-0"><?php echo esc_html( (string) (int) $ulbm_stat_customers ); ?></p>
				<div class="ulbm-stat-spark-wrap">
					<canvas class="ulbm-stat-spark" id="ulbm-spark-customers" width="320" height="40" aria-hidden="true"></canvas>
				</div>
			</div>
		</div>
		<div class="col-6 col-xl-3">
			<div class="ulbm-stat-card ulbm-dash-stat">
				<div class="ulbm-dash-stat-top">
					<span class="ulbm-dash-stat-icon"><i class="bi bi-grid-3x3-gap"></i></span>
					<span class="ulbm-dash-stat-label"><?php esc_html_e( 'Booking Types', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="ulbm-dash-stat-value mb-0"><?php echo esc_html( (string) (int) $ulbm_stat_types_count ); ?></p>
				<div class="ulbm-stat-spark-wrap">
					<canvas class="ulbm-stat-spark" id="ulbm-spark-types" width="320" height="40" aria-hidden="true"></canvas>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-lg-5">
			<div class="ulbm-dash-panel ulbm-dash-chart-panel h-100">
				<div class="ulbm-dash-panel-head">
					<h2 class="ulbm-dash-panel-title mb-0"><?php esc_html_e( 'Bookings — Last 30 Days', 'flex-multiple-listing-and-booking-system' ); ?></h2>
					<span class="ulbm-dash-chip"><?php esc_html_e( 'Last 30 Days', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<div class="ulbm-dash-chart-wrap">
					<canvas id="ulbm-chart-main" height="160"></canvas>
				</div>
			</div>
		</div>
		<div class="col-lg-7">
			<div class="ulbm-dash-panel ulbm-dash-actions-panel h-100">
				<div class="ulbm-dash-panel-head">
					<h2 class="ulbm-dash-panel-title mb-0"><?php esc_html_e( 'Quick Actions', 'flex-multiple-listing-and-booking-system' ); ?></h2>
					<span class="ulbm-dash-panel-sub"><?php esc_html_e( 'Do everything from here', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<div class="ulbm-dash-actions-grid">
					<?php foreach ( $ulbm_dash_actions as $ulbm_action ) : ?>
						<a
							href="<?php echo esc_url( $ulbm_action['url'] ); ?>"
							class="ulbm-dash-action ulbm-dash-action--<?php echo esc_attr( $ulbm_action['tone'] ); ?>"
							<?php echo isset( $ulbm_action['attrs'] ) ? $ulbm_action['attrs'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>
							<span class="ulbm-dash-action-icon"><i class="bi <?php echo esc_attr( $ulbm_action['icon'] ); ?>"></i></span>
							<span class="ulbm-dash-action-label"><?php echo esc_html( $ulbm_action['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3">
		<div class="col-lg-8">
			<div class="ulbm-dash-panel ulbm-admin-panel h-100">
				<div class="ulbm-dash-panel-head border-bottom">
					<h2 class="ulbm-dash-panel-title mb-0"><?php esc_html_e( 'Recent Bookings', 'flex-multiple-listing-and-booking-system' ); ?></h2>
					<a class="small fw-semibold" href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-bookings' ) ); ?>"><?php esc_html_e( 'View all', 'flex-multiple-listing-and-booking-system' ); ?></a>
				</div>
				<div class="table-responsive">
					<table class="table ulbm-table ulbm-dash-table mb-0 align-middle w-100">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID', 'flex-multiple-listing-and-booking-system' ); ?></th>
								<th><?php esc_html_e( 'Customer', 'flex-multiple-listing-and-booking-system' ); ?></th>
								<th><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></th>
								<th class="text-end"><?php esc_html_e( 'Total', 'flex-multiple-listing-and-booking-system' ); ?></th>
								<th><?php esc_html_e( 'Date', 'flex-multiple-listing-and-booking-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $ulbm_recent_bookings ) ) : ?>
								<tr><td colspan="5" class="text-muted p-4"><?php esc_html_e( 'No bookings yet.', 'flex-multiple-listing-and-booking-system' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( array_slice( $ulbm_recent_bookings, 0, 5 ) as $ulbm_row ) : ?>
									<?php
									$ulbm_customer_name = $ulbm_dash_customer_label( $ulbm_row );
									$ulbm_payment       = ! empty( $ulbm_row['payment_status'] ) ? (string) $ulbm_row['payment_status'] : 'unpaid';
									?>
									<tr>
										<td class="fw-semibold">#<?php echo esc_html( (string) (int) $ulbm_row['id'] ); ?></td>
										<td>
											<span class="ulbm-dash-customer">
												<span class="ulbm-dash-avatar"><?php echo esc_html( $ulbm_dash_initials( $ulbm_customer_name ) ); ?></span>
												<span><?php echo esc_html( $ulbm_customer_name ); ?></span>
											</span>
										</td>
										<td>
											<span class="badge rounded-pill ulbm-dash-pill ulbm-dash-pill--<?php echo esc_attr( $ulbm_dash_status_class( $ulbm_payment ) ); ?>">
												<?php echo esc_html( ucfirst( $ulbm_payment ) ); ?>
											</span>
										</td>
										<td class="text-end fw-semibold"><?php echo esc_html( number_format_i18n( (float) $ulbm_row['total'], 2 ) ); ?></td>
										<td class="small text-muted"><?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( (string) $ulbm_row['created_at'] ) ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="ulbm-dash-panel ulbm-dash-status-panel h-100">
				<div class="ulbm-dash-panel-head">
					<h2 class="ulbm-dash-panel-title mb-0"><?php esc_html_e( 'Status Breakdown', 'flex-multiple-listing-and-booking-system' ); ?></h2>
					<span class="ulbm-dash-chip"><?php esc_html_e( 'Last 30 Days', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<?php if ( ! empty( $ulbm_status_counts ) ) : ?>
					<div class="ulbm-dash-status-body">
						<div class="ulbm-dash-donut-wrap">
							<canvas id="ulbm-chart-status" height="140"></canvas>
						</div>
						<ul class="ulbm-dash-status-legend list-unstyled mb-0">
							<?php foreach ( $ulbm_status_labels as $ulbm_i => $ulbm_label ) : ?>
								<?php
								$ulbm_cnt = (int) $ulbm_status_counts[ $ulbm_i ];
								$ulbm_pct = $ulbm_status_total > 0 ? round( ( $ulbm_cnt / $ulbm_status_total ) * 100, 1 ) : 0;
								?>
								<li>
									<span class="ulbm-dash-legend-dot" style="background:<?php echo esc_attr( $ulbm_status_colors[ $ulbm_i ] ); ?>"></span>
									<span class="ulbm-dash-legend-label"><?php echo esc_html( $ulbm_label ); ?></span>
									<span class="ulbm-dash-legend-meta"><?php echo esc_html( (string) $ulbm_cnt ); ?> (<?php echo esc_html( (string) $ulbm_pct ); ?>%)</span>
								</li>
							<?php endforeach; ?>
						</ul>
						<p class="ulbm-dash-status-total small text-muted mb-0">
							<?php
							printf(
								/* translators: %d: total bookings */
								esc_html__( 'Total: %d bookings', 'flex-multiple-listing-and-booking-system' ),
								(int) $ulbm_status_total
							);
							?>
						</p>
					</div>
				<?php else : ?>
					<p class="text-muted small p-4 mb-0 text-center"><?php esc_html_e( 'No bookings yet.', 'flex-multiple-listing-and-booking-system' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
