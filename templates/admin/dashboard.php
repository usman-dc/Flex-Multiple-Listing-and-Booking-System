<?php
/**
 * Professional admin dashboard — KPI cards, charts, recent tables.
 *
 * @package FlexBookingSystem
 *
 * @var int                    $fbs_stat_bookings_30d
 * @var float                  $fbs_stat_revenue_30d
 * @var int                    $fbs_stat_bookings_all
 * @var int                    $fbs_stat_types_count
 * @var int                    $fbs_stat_customers
 * @var array<string, int>     $fbs_daily_bookings
 * @var array<string, float>   $fbs_daily_revenue
 * @var array<string, int>     $fbs_count_by_status
 * @var array<int, int>        $fbs_count_by_type
 * @var array<int, string>     $fbs_type_names
 * @var array                  $fbs_recent_bookings
 * @var array                  $fbs_recent_activity
 * @var int                    $fbs_activity_total
 */

defined( 'ABSPATH' ) || exit;

$general  = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
$currency = is_array( $general ) && ! empty( $general['currency'] ) ? $general['currency'] : 'USD';

$fbs_dash_status_class = static function ( $status ) {
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
	$chart_bookings[] = isset( $fbs_daily_bookings[ $d ] ) ? (int) $fbs_daily_bookings[ $d ] : 0;
	$chart_revenue[]  = isset( $fbs_daily_revenue[ $d ] ) ? (float) $fbs_daily_revenue[ $d ] : 0;
}

// Status donut data.
$status_labels = array();
$status_counts = array();
$status_colors = array();
$color_map = array(
	'pending'   => '#ffc107', 'confirmed' => '#198754', 'completed' => '#0d6efd',
	'cancelled' => '#dc3545', 'rejected'  => '#6c757d', 'on_hold'   => '#fd7e14',
);
foreach ( $fbs_count_by_status as $st => $cnt ) {
	$status_labels[] = ucfirst( $st );
	$status_counts[] = $cnt;
	$status_colors[] = $color_map[ $st ] ?? '#adb5bd';
}

// Per-type bar data.
$type_labels = array();
$type_counts = array();
foreach ( $fbs_count_by_type as $tid => $cnt ) {
	$type_labels[] = isset( $fbs_type_names[ $tid ] ) ? $fbs_type_names[ $tid ] : '#' . $tid;
	$type_counts[] = $cnt;
}
?>

<div class="wrap fbs-admin-wrap container-fluid py-3">
	<!-- Header -->
	<div class="fbs-page-header d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
		<div>
			<h1 class="h3 mb-1 fbs-page-title"><?php esc_html_e( 'Dashboard', 'flex-booking-system' ); ?></h1>
			<p class="text-muted small mb-0"><?php esc_html_e( 'Performance overview for the last 30 days.', 'flex-booking-system' ); ?></p>
		</div>
		<span class="badge text-bg-primary fs-6 fbs-version-badge"><?php echo esc_html( fbs_plugin_menu_label() . ' v' . FBS_VERSION ); ?></span>
	</div>

	<!-- KPI Cards -->
	<div class="row g-3 mb-4">
		<div class="col-6 col-lg-3">
			<div class="fbs-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="fbs-stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-check"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Bookings (30d)', 'flex-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( (string) (int) $fbs_stat_bookings_30d ); ?></p>
				<p class="small text-muted mb-0"><?php printf( esc_html__( '%d all-time', 'flex-booking-system' ), (int) $fbs_stat_bookings_all ); ?></p>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="fbs-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="fbs-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-currency-dollar"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Revenue (30d)', 'flex-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( number_format_i18n( (float) $fbs_stat_revenue_30d, 2 ) ); ?></p>
				<p class="small text-muted mb-0"><?php echo esc_html( $currency ); ?></p>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="fbs-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="fbs-stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-people"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Customers', 'flex-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( (string) (int) $fbs_stat_customers ); ?></p>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="fbs-stat-card border rounded bg-white p-3 h-100">
				<div class="d-flex align-items-center gap-2 mb-2">
					<span class="fbs-stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-tags"></i></span>
					<span class="small text-muted"><?php esc_html_e( 'Booking Types', 'flex-booking-system' ); ?></span>
				</div>
				<p class="fs-3 fw-bold mb-0"><?php echo esc_html( (string) (int) $fbs_stat_types_count ); ?></p>
				<a class="small" href="<?php echo esc_url( admin_url( 'admin.php?page=fbs-booking-types' ) ); ?>"><?php esc_html_e( 'Manage', 'flex-booking-system' ); ?></a>
			</div>
		</div>
	</div>

	<!-- Charts Row -->
	<div class="row g-3 mb-4">
		<div class="col-lg-8">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Bookings & Revenue — Last 30 Days', 'flex-booking-system' ); ?></h6>
				<canvas id="fbs-chart-main" height="220"></canvas>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Status Breakdown', 'flex-booking-system' ); ?></h6>
				<?php if ( ! empty( $status_counts ) ) : ?>
					<canvas id="fbs-chart-status" height="200"></canvas>
				<?php else : ?>
					<p class="text-muted small text-center mt-5"><?php esc_html_e( 'No bookings yet.', 'flex-booking-system' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Per-Type + Quick Links -->
	<div class="row g-3 mb-4">
		<div class="col-lg-6">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Bookings by Type', 'flex-booking-system' ); ?></h6>
				<?php if ( ! empty( $type_counts ) ) : ?>
					<canvas id="fbs-chart-types" height="180"></canvas>
				<?php else : ?>
					<p class="text-muted small text-center mt-4"><?php esc_html_e( 'No data yet.', 'flex-booking-system' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="border rounded bg-white p-3 h-100">
				<h6 class="fw-semibold mb-3"><?php esc_html_e( 'Quick Links', 'flex-booking-system' ); ?></h6>
				<div class="list-group list-group-flush">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fbs-bookings' ) ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-calendar2-check text-primary"></i> <?php esc_html_e( 'All Bookings', 'flex-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fbs-booking-types' ) ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-tags text-primary"></i> <?php esc_html_e( 'Booking Types', 'flex-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fbs-settings' ) ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-gear text-primary"></i> <?php esc_html_e( 'Settings & Shortcodes', 'flex-booking-system' ); ?>
					</a>
					<?php
					$fbs_listings_url = admin_url( 'admin.php?page=fbs-booking-types' );
					foreach ( \FlexBooking\PostTypes\BookingTypePostTypeRegistry::get_registered_types() as $fbs_listing_type ) {
						$fbs_listing_pt = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $fbs_listing_type['slug'] );
						if ( post_type_exists( $fbs_listing_pt ) ) {
							$fbs_listings_url = admin_url( 'edit.php?post_type=' . $fbs_listing_pt );
							break;
						}
					}
					?>
					<a href="<?php echo esc_url( $fbs_listings_url ); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<i class="bi bi-building text-primary"></i> <?php esc_html_e( 'Listings', 'flex-booking-system' ); ?>
					</a>
				</div>
				<div class="mt-3 p-2 bg-light rounded small text-muted">
					<strong><?php esc_html_e( 'REST API:', 'flex-booking-system' ); ?></strong>
					<code><?php echo esc_html( rest_url( 'flex-booking/v1' ) ); ?></code>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent Bookings + Activity -->
	<div class="row g-3">
		<div class="col-lg-7">
			<div class="fbs-admin-panel border rounded bg-white h-100">
				<div class="fbs-admin-panel-head px-3 py-3 d-flex justify-content-between align-items-center border-bottom">
					<span><i class="bi bi-calendar-event text-primary me-1"></i><?php esc_html_e( 'Recent Bookings', 'flex-booking-system' ); ?></span>
					<a class="small" href="<?php echo esc_url( admin_url( 'admin.php?page=fbs-bookings' ) ); ?>"><?php esc_html_e( 'View all', 'flex-booking-system' ); ?></a>
				</div>
				<div class="p-0">
					<div class="table-responsive">
						<table class="table fbs-table mb-0 align-middle w-100">
							<thead>
								<tr>
									<th><?php esc_html_e( 'ID', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Type', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Status', 'flex-booking-system' ); ?></th>
									<th class="text-end"><?php esc_html_e( 'Total', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Date', 'flex-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $fbs_recent_bookings ) ) : ?>
									<tr><td colspan="5" class="text-muted p-4"><?php esc_html_e( 'No bookings yet.', 'flex-booking-system' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $fbs_recent_bookings as $row ) : ?>
										<tr>
											<td>#<?php echo esc_html( (string) (int) $row['id'] ); ?></td>
											<td class="small"><?php echo isset( $fbs_type_names[ (int) $row['booking_type_id'] ] ) ? esc_html( $fbs_type_names[ (int) $row['booking_type_id'] ] ) : '#' . esc_html( (string) (int) $row['booking_type_id'] ); ?></td>
											<td><span class="badge rounded-pill text-bg-<?php echo esc_attr( $fbs_dash_status_class( (string) $row['status'] ) ); ?>"><?php echo esc_html( (string) $row['status'] ); ?></span></td>
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
			<div class="fbs-admin-panel border rounded bg-white h-100">
				<div class="fbs-admin-panel-head px-3 py-3 d-flex justify-content-between align-items-center border-bottom">
					<span><i class="bi bi-activity text-primary me-1"></i><?php esc_html_e( 'Activity Log', 'flex-booking-system' ); ?></span>
					<span class="badge text-bg-secondary"><?php echo esc_html( (string) (int) $fbs_activity_total ); ?></span>
				</div>
				<div class="p-0">
					<div class="table-responsive">
						<table class="table fbs-table mb-0 align-middle w-100">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Action', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Object', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'When', 'flex-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $fbs_recent_activity ) ) : ?>
									<tr><td colspan="3" class="text-muted p-4"><?php esc_html_e( 'No activity yet.', 'flex-booking-system' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $fbs_recent_activity as $log ) : ?>
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var mainCtx = document.getElementById('fbs-chart-main');
	if (mainCtx) {
		new Chart(mainCtx, {
			type: 'line',
			data: {
				labels: <?php echo wp_json_encode( $chart_labels ); ?>,
				datasets: [
					{
						label: '<?php echo esc_js( __( 'Bookings', 'flex-booking-system' ) ); ?>',
						data: <?php echo wp_json_encode( $chart_bookings ); ?>,
						borderColor: '#0d6efd',
						backgroundColor: 'rgba(13,110,253,0.1)',
						fill: true,
						tension: 0.3,
						yAxisID: 'y'
					},
					{
						label: '<?php echo esc_js( __( 'Revenue', 'flex-booking-system' ) ); ?> (<?php echo esc_js( $currency ); ?>)',
						data: <?php echo wp_json_encode( $chart_revenue ); ?>,
						borderColor: '#198754',
						backgroundColor: 'rgba(25,135,84,0.08)',
						fill: true,
						tension: 0.3,
						yAxisID: 'y1'
					}
				]
			},
			options: {
				responsive: true,
				interaction: { mode: 'index', intersect: false },
				plugins: { legend: { position: 'top' } },
				scales: {
					y: { beginAtZero: true, position: 'left', title: { display: true, text: '<?php echo esc_js( __( 'Bookings', 'flex-booking-system' ) ); ?>' } },
					y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: '<?php echo esc_js( $currency ); ?>' } }
				}
			}
		});
	}

	var statusCtx = document.getElementById('fbs-chart-status');
	if (statusCtx) {
		new Chart(statusCtx, {
			type: 'doughnut',
			data: {
				labels: <?php echo wp_json_encode( $status_labels ); ?>,
				datasets: [{
					data: <?php echo wp_json_encode( $status_counts ); ?>,
					backgroundColor: <?php echo wp_json_encode( $status_colors ); ?>
				}]
			},
			options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
		});
	}

	var typesCtx = document.getElementById('fbs-chart-types');
	if (typesCtx) {
		new Chart(typesCtx, {
			type: 'bar',
			data: {
				labels: <?php echo wp_json_encode( $type_labels ); ?>,
				datasets: [{
					label: '<?php echo esc_js( __( 'Bookings', 'flex-booking-system' ) ); ?>',
					data: <?php echo wp_json_encode( $type_counts ); ?>,
					backgroundColor: '#0d6efd'
				}]
			},
			options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
		});
	}
});
</script>
