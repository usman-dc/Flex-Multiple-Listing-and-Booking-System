<?php
/**
 * Professional admin dashboard — KPI cards, charts, recent tables.
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

$general  = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
$currency = is_array( $general ) && ! empty( $general['currency'] ) ? $general['currency'] : 'USD';

$ulbm_dash_status_class = static function ( $status ) {
	$s = strtolower( (string) $status );
	if ( in_array( $s, array( 'confirmed', 'completed', 'approved' ), true ) ) return 'success';
	if ( in_array( $s, array( 'cancelled', 'canceled', 'refunded', 'rejected' ), true ) ) return 'danger';
	if ( in_array( $s, array( 'pending', 'hold', 'draft' ), true ) ) return 'warning';
	return 'secondary';
};

// Build chart data: fill in missing days with 0.
$chart_labels   = array();
$chart_bookings = array();
$chart_revenue  = array();
for ( $i = 29; $i >= 0; $i-- ) {
	$d = wp_date( 'Y-m-d', strtotime( "-{$i} days", (int) current_time( 'timestamp' ) ) );
	$chart_labels[]   = wp_date( 'M j', strtotime( $d ) );
	$chart_bookings[] = isset( $ulbm_daily_bookings[ $d ] ) ? (int) $ulbm_daily_bookings[ $d ] : 0;
	$chart_revenue[]  = isset( $ulbm_daily_revenue[ $d ] ) ? (float) $ulbm_daily_revenue[ $d ] : 0;
}

// Status donut data.
$status_labels = array();
$status_counts = array();
$status_colors = array();
$color_map = array(
	'pending'   => '#ffc107', 'confirmed' => '#198754', 'completed' => '#0d6efd',
	'cancelled' => '#dc3545', 'rejected'  => '#6c757d', 'on_hold'   => '#fd7e14',
);
foreach ( $ulbm_count_by_status as $st => $cnt ) {
	$status_labels[] = ucfirst( $st );
	$status_counts[] = $cnt;
	$status_colors[] = $color_map[ $st ] ?? '#adb5bd';
}

// Per-type bar data.
$type_labels = array();
$type_counts = array();
foreach ( $ulbm_count_by_type as $tid => $cnt ) {
	$type_labels[] = isset( $ulbm_type_names[ $tid ] ) ? $ulbm_type_names[ $tid ] : '#' . $tid;
	$type_counts[] = $cnt;
}
?>

<div class="wrap ulbm-admin-wrap container-fluid py-3">
	<!-- Header -->
	<div class="ulbm-page-header d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
		<div>
			<h1 class="h3 mb-1 ulbm-page-title"><?php esc_html_e( 'Dashboard', 'flex-multiple-listing-and-booking-system' ); ?></h1>
			<p class="text-muted small mb-0"><?php esc_html_e( 'Performance overview for the last 30 days.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		</div>
		<span class="badge text-bg-primary fs-6 ulbm-version-badge"><?php echo esc_html( ulbm_plugin_menu_label() . ' v' . ULBM_VERSION ); ?></span>
	</div>

	<!-- KPI Cards -->
	<div class="row g-3 mb-4">
		<div class="col-6 col-lg-3">
			<div class="ulbm-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="ulbm-stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-check"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Bookings (30d)', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( (string) (int) $ulbm_stat_bookings_30d ); ?></p>
				<p class="small text-muted mb-0"><?php
				printf(
					/* translators: %d: total booking count */
					esc_html__( '%d all-time', 'flex-multiple-listing-and-booking-system' ),
					(int) $ulbm_stat_bookings_all
				);
				?></p>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="ulbm-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="ulbm-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-currency-dollar"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Revenue (30d)', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( number_format_i18n( (float) $ulbm_stat_revenue_30d, 2 ) ); ?></p>
				<p class="small text-muted mb-0"><?php echo esc_html( $currency ); ?></p>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="ulbm-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="ulbm-stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-people"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Customers', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( (string) (int) $ulbm_stat_customers ); ?></p>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="ulbm-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="ulbm-stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-tags"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Booking Types', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( (string) (int) $ulbm_stat_types_count ); ?></p>
				<a class="small" href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-booking-types' ) ); ?>"><?php esc_html_e( 'Manage', 'flex-multiple-listing-and-booking-system' ); ?></a>
			</div>
		</div>
	</div>

	<!-- Charts Row -->
	<div class="row g-3 mb-4">
		<div class="col-lg-8">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Bookings & Revenue — Last 30 Days', 'flex-multiple-listing-and-booking-system' ); ?></h6>
				<canvas id="ulbm-chart-main" height="220"></canvas>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Status Breakdown', 'flex-multiple-listing-and-booking-system' ); ?></h6>
				<?php if ( ! empty( $status_counts ) ) : ?>
					<canvas id="ulbm-chart-status" height="200"></canvas>
				<?php else : ?>
					<p class="text-muted small text-center mt-5"><?php esc_html_e( 'No bookings yet.', 'flex-multiple-listing-and-booking-system' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Per-Type + Quick Links -->
	<div class="row g-3 mb-4">
		<div class="col-lg-6">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Bookings by Type', 'flex-multiple-listing-and-booking-system' ); ?></h6>
				<?php if ( ! empty( $type_counts ) ) : ?>
					<canvas id="ulbm-chart-types" height="180"></canvas>
				<?php else : ?>
					<p class="text-muted small text-center mt-4"><?php esc_html_e( 'No data yet.', 'flex-multiple-listing-and-booking-system' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Quick Links', 'flex-multiple-listing-and-booking-system' ); ?></h6>
				<div class="list-group list-group-flush">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-bookings' ) ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-calendar2-check text-primary"></i> <?php esc_html_e( 'All Bookings', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-booking-types' ) ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-tags text-primary"></i> <?php esc_html_e( 'Booking Types', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-settings' ) ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-gear text-primary"></i> <?php esc_html_e( 'Settings & Shortcodes', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<?php
					$ulbm_listings_url = admin_url( 'admin.php?page=ulbm-booking-types' );
					foreach ( \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types() as $ulbm_listing_type ) {
						$ulbm_listing_pt = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $ulbm_listing_type['slug'] );
						if ( post_type_exists( $ulbm_listing_pt ) ) {
							$ulbm_listings_url = admin_url( 'edit.php?post_type=' . $ulbm_listing_pt );
							break;
						}
					}
					?>
					<a href="<?php echo esc_url( $ulbm_listings_url ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-building text-primary"></i> <?php esc_html_e( 'Listings', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
				</div>
				<div class="mt-3 p-2 bg-light rounded small text-muted">
					<strong><?php esc_html_e( 'REST API:', 'flex-multiple-listing-and-booking-system' ); ?></strong>
					<code><?php echo esc_html( rest_url( 'ulbm/v1' ) ); ?></code>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent Bookings + Activity -->
	<div class="row g-3">
		<div class="col-lg-7">
			<div class="ulbm-admin-panel border rounded bg-white h-100">
				<div class="ulbm-admin-panel-head px-3 py-3 d-flex justify-content-between align-items-center border-bottom">
					<span><i class="bi bi-calendar-event text-primary me-1"></i><?php esc_html_e( 'Recent Bookings', 'flex-multiple-listing-and-booking-system' ); ?></span>
					<a class="small" href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-bookings' ) ); ?>"><?php esc_html_e( 'View all', 'flex-multiple-listing-and-booking-system' ); ?></a>
				</div>
				<div class="p-0">
					<div class="table-responsive">
						<table class="table ulbm-table mb-0 align-middle w-100">
							<thead>
								<tr>
									<th><?php esc_html_e( 'ID', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Type', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th class="text-end"><?php esc_html_e( 'Total', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Date', 'flex-multiple-listing-and-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $ulbm_recent_bookings ) ) : ?>
									<tr><td colspan="5" class="text-muted p-4"><?php esc_html_e( 'No bookings yet.', 'flex-multiple-listing-and-booking-system' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $ulbm_recent_bookings as $row ) : ?>
										<tr>
											<td>#<?php echo esc_html( (string) (int) $row['id'] ); ?></td>
											<td class="small"><?php echo isset( $ulbm_type_names[ (int) $row['booking_type_id'] ] ) ? esc_html( $ulbm_type_names[ (int) $row['booking_type_id'] ] ) : '#' . esc_html( (string) (int) $row['booking_type_id'] ); ?></td>
											<td><span class="badge rounded-pill text-bg-<?php echo esc_attr( $ulbm_dash_status_class( (string) $row['status'] ) ); ?>"><?php echo esc_html( (string) $row['status'] ); ?></span></td>
											<td class="text-end"><?php echo esc_html( number_format_i18n( (float) $row['total'], 2 ) ); ?> <?php echo esc_html( $currency ); ?></td>
											<td class="small"><?php echo esc_html( wp_date( 'M j, H:i', strtotime( (string) $row['created_at'] ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-5">
			<div class="ulbm-admin-panel border rounded bg-white h-100">
				<div class="ulbm-admin-panel-head px-3 py-3 d-flex justify-content-between align-items-center border-bottom">
					<span><i class="bi bi-activity text-primary me-1"></i><?php esc_html_e( 'Activity Log', 'flex-multiple-listing-and-booking-system' ); ?></span>
					<span class="badge text-bg-secondary"><?php echo esc_html( (string) (int) $ulbm_activity_total ); ?></span>
				</div>
				<div class="p-0">
					<div class="table-responsive">
						<table class="table ulbm-table mb-0 align-middle w-100">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Action', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Object', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'When', 'flex-multiple-listing-and-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $ulbm_recent_activity ) ) : ?>
									<tr><td colspan="3" class="text-muted p-4"><?php esc_html_e( 'No activity yet.', 'flex-multiple-listing-and-booking-system' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $ulbm_recent_activity as $log ) : ?>
										<tr>
											<td><code><?php echo esc_html( (string) $log['action'] ); ?></code></td>
											<td>#<?php echo esc_html( (string) (int) $log['object_id'] ); ?></td>
											<td><?php echo esc_html( wp_date( 'M j, H:i', strtotime( (string) $log['created_at'] ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
