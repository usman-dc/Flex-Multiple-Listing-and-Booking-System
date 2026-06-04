<?php
/**
 * All bookings — full-width table, filters, admin workflow actions, customer notifications.
 *
 * @package FlexBookingSystem
 *
 * @var array                                $ulbm_bookings             Current page rows.
 * @var int                                  $ulbm_bookings_total       Total rows (filtered).
 * @var int                                  $ulbm_bookings_paged       Current page (1-based).
 * @var int                                  $ulbm_bookings_per_page    Page size.
 * @var int                                  $ulbm_bookings_total_pages Total pages.
 * @var string                               $ulbm_status_filter        Active status filter or ''.
 * @var int                                  $ulbm_type_filter          Booking type id filter or 0.
 * @var array<int, array<string, mixed>>     $ulbm_booking_type_options All types for filter dropdown.
 * @var array<int, string>                   $ulbm_type_names           Type id => display name.
 * @var array<int, string>                   $ulbm_customer_emails      customer_id => email.
 * @var array<int, array<string, mixed>>     $ulbm_booking_answers      booking_id => decoded form_values.
 */

use FlexBooking\Booking\BookingAdminUpdater;

defined( 'ABSPATH' ) || exit;

$ulbm_status_filter        = isset( $ulbm_status_filter ) ? (string) $ulbm_status_filter : '';
$ulbm_type_filter          = isset( $ulbm_type_filter ) ? (int) $ulbm_type_filter : 0;
$ulbm_booking_type_options = isset( $ulbm_booking_type_options ) && is_array( $ulbm_booking_type_options ) ? $ulbm_booking_type_options : array();
$ulbm_type_names           = isset( $ulbm_type_names ) && is_array( $ulbm_type_names ) ? $ulbm_type_names : array();
$ulbm_customer_emails      = isset( $ulbm_customer_emails ) && is_array( $ulbm_customer_emails ) ? $ulbm_customer_emails : array();
$ulbm_booking_answers      = isset( $ulbm_booking_answers ) && is_array( $ulbm_booking_answers ) ? $ulbm_booking_answers : array();

$ulbm_format_form_answers_cell = static function ( $answers ) {
	if ( ! is_array( $answers ) || empty( $answers ) ) {
		return array(
			'short' => '',
			'title' => '',
		);
	}
	$lines = array();
	foreach ( $answers as $key => $val ) {
		if ( is_array( $val ) ) {
			$val = wp_json_encode( $val );
		}
		$val = trim( (string) $val );
		if ( '' === $val ) {
			continue;
		}
		$label = is_string( $key ) ? ucwords( str_replace( array( '_', '-' ), ' ', $key ) ) : (string) $key;
		$lines[] = $label . ': ' . $val;
	}
	if ( empty( $lines ) ) {
		return array(
			'short' => '',
			'title' => '',
		);
	}
	$full  = implode( "\n", $lines );
	$short = $full;
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		if ( mb_strlen( $short, 'UTF-8' ) > 160 ) {
			$short = mb_substr( $short, 0, 157, 'UTF-8' ) . '…';
		}
	} elseif ( strlen( $short ) > 160 ) {
		$short = substr( $short, 0, 157 ) . '…';
	}
	return array(
		'short' => $short,
		'title' => $full,
	);
};

$ulbm_booking_status_class = static function ( $status ) {
	$s = strtolower( (string) $status );
	if ( in_array( $s, array( 'confirmed', 'completed', 'approved' ), true ) ) {
		return 'success';
	}
	if ( in_array( $s, array( 'cancelled', 'canceled', 'refunded', 'rejected' ), true ) ) {
		return 'danger';
	}
	if ( in_array( $s, array( 'pending', 'hold', 'draft', 'on_hold' ), true ) ) {
		return 'warning';
	}
	return 'secondary';
};

$ulbm_payment_status_class = static function ( $payment_status ) {
	$s = strtolower( (string) $payment_status );
	if ( false !== strpos( $s, 'paid' ) || 'captured' === $s ) {
		return 'success';
	}
	if ( false !== strpos( $s, 'fail' ) || false !== strpos( $s, 'declin' ) ) {
		return 'danger';
	}
	if ( 'unpaid' === $s || 'pending' === $s ) {
		return 'warning';
	}
	return 'secondary';
};

$list_url = add_query_arg( 'page', 'ulbm-bookings', admin_url( 'admin.php' ) );
if ( '' !== $ulbm_status_filter ) {
	$list_url = add_query_arg( 'ulbm_status', $ulbm_status_filter, $list_url );
}
if ( $ulbm_type_filter > 0 ) {
	$list_url = add_query_arg( 'ulbm_type', (string) $ulbm_type_filter, $list_url );
}
$pagination_base = esc_url_raw( add_query_arg( 'paged', '%#%', $list_url ) );

$pagination = paginate_links(
	array(
		'base'      => $pagination_base,
		'format'    => '',
		'prev_text' => '&laquo; ' . __( 'Previous', 'flex-booking-system' ),
		'next_text' => __( 'Next', 'flex-booking-system' ) . ' &raquo;',
		'total'     => max( 1, (int) $ulbm_bookings_total_pages ),
		'current'   => (int) $ulbm_bookings_paged,
		'type'      => 'plain',
	)
);

$showing_from = $ulbm_bookings_total > 0 ? ( ( (int) $ulbm_bookings_paged - 1 ) * (int) $ulbm_bookings_per_page ) + 1 : 0;
$showing_to   = min( (int) $ulbm_bookings_total, ( (int) $ulbm_bookings_paged * (int) $ulbm_bookings_per_page ) );

$statuses        = BookingAdminUpdater::booking_statuses();
$payment_statuses = BookingAdminUpdater::payment_statuses();
?>
<div class="wrap ulbm-admin-wrap ulbm-bookings-page container-fluid py-3">
	<div class="ulbm-page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
		<div>
			<h1 class="h3 mb-1 ulbm-page-title d-flex align-items-center gap-2">
				<i class="bi bi-calendar2-check text-primary" aria-hidden="true"></i>
				<?php esc_html_e( 'Bookings', 'flex-booking-system' ); ?>
			</h1>
			<p class="text-muted small mb-0"><?php esc_html_e( 'Review reservations, change status, payment, and optionally email the customer.', 'flex-booking-system' ); ?></p>
		</div>
	</div>

	<div class="ulbm-admin-panel border rounded bg-white mb-3">
		<div class="p-3 d-flex flex-wrap align-items-end gap-3">
			<form method="get" class="d-flex flex-wrap align-items-end gap-2">
				<input type="hidden" name="page" value="ulbm-bookings" />
				<div>
					<label class="form-label small text-muted mb-0" for="ulbm_status"><?php esc_html_e( 'Filter by status', 'flex-booking-system' ); ?></label>
					<select class="form-select form-select-sm" name="ulbm_status" id="ulbm_status">
						<option value=""><?php esc_html_e( 'All statuses', 'flex-booking-system' ); ?></option>
						<?php foreach ( $statuses as $st ) : ?>
							<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $ulbm_status_filter, $st ); ?>>
								<?php echo esc_html( $st ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="form-label small text-muted mb-0" for="ulbm_type"><?php esc_html_e( 'Booking type', 'flex-booking-system' ); ?></label>
					<select class="form-select form-select-sm" name="ulbm_type" id="ulbm_type">
						<option value="0"><?php esc_html_e( 'All types', 'flex-booking-system' ); ?></option>
						<?php foreach ( $ulbm_booking_type_options as $tto ) : ?>
							<option value="<?php echo esc_attr( (string) (int) $tto['id'] ); ?>" <?php selected( $ulbm_type_filter, (int) $tto['id'] ); ?>>
								<?php echo esc_html( (string) $tto['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<button type="submit" class="btn btn-sm btn-outline-primary"><?php esc_html_e( 'Apply', 'flex-booking-system' ); ?></button>
			</form>
			<div class="form-check ms-auto">
				<input class="form-check-input" type="checkbox" id="ulbm-bookings-notify" checked />
				<label class="form-check-label small" for="ulbm-bookings-notify">
					<?php esc_html_e( 'Email customer when booking status changes (see Settings → notifications).', 'flex-booking-system' ); ?>
				</label>
			</div>
		</div>
	</div>

	<p class="text-muted small mb-2">
		<?php
		printf(
			/* translators: 1: total rows (filtered), 2: rows per page */
			esc_html__( 'Showing %1$d record(s) total (per page: %2$d).', 'flex-booking-system' ),
			(int) $ulbm_bookings_total,
			(int) $ulbm_bookings_per_page
		);
		?>
	</p>
	<?php if ( $ulbm_bookings_total > 0 ) : ?>
		<p class="small text-muted mb-3">
			<?php
			printf(
				/* translators: 1: first row index, 2: last row index */
				esc_html__( 'Rows %1$d–%2$d on this page.', 'flex-booking-system' ),
				(int) $showing_from,
				(int) $showing_to
			);
			?>
		</p>
	<?php endif; ?>

	<div id="ulbm-bookings-feedback" class="alert d-none" role="status"></div>

	<div class="ulbm-admin-panel border rounded bg-white">
		<div class="ulbm-admin-panel-head px-3 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom bg-white">
			<span class="fw-semibold"><?php esc_html_e( 'Booking records', 'flex-booking-system' ); ?></span>
			<span class="badge text-bg-light border"><?php echo esc_html( (string) (int) $ulbm_bookings_total ); ?> <?php esc_html_e( 'rows', 'flex-booking-system' ); ?></span>
		</div>
		<div class="p-0 ulbm-bookings-table-wrap">
			<div class="table-responsive">
				<table class="table ulbm-table mb-0 align-middle w-100">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'ID', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'UID', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Booking type', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Guest email', 'flex-booking-system' ); ?></th>
							<th scope="col" class="ulbm-col-form-answers"><?php esc_html_e( 'Form answers', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Payment', 'flex-booking-system' ); ?></th>
							<th scope="col" class="text-end"><?php esc_html_e( 'Total', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Start', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'End', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Created', 'flex-booking-system' ); ?></th>
							<th scope="col" class="text-nowrap"><?php esc_html_e( 'Workflow', 'flex-booking-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $ulbm_bookings ) ) : ?>
							<tr>
								<td colspan="12" class="text-muted p-4"><?php esc_html_e( 'No bookings match this filter.', 'flex-booking-system' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $ulbm_bookings as $b ) : ?>
								<?php
								$bid = (int) $b['id'];
								$cid = ! empty( $b['customer_id'] ) ? (int) $b['customer_id'] : 0;
								$guest_email = '';
								if ( $cid && isset( $ulbm_customer_emails[ $cid ] ) ) {
									$guest_email = $ulbm_customer_emails[ $cid ];
								} elseif ( ! empty( $b['wp_user_id'] ) ) {
									$u = get_userdata( (int) $b['wp_user_id'] );
									if ( $u ) {
										$guest_email = $u->user_email;
									}
								}
								$answers_cell = $ulbm_format_form_answers_cell(
									isset( $ulbm_booking_answers[ $bid ] ) ? $ulbm_booking_answers[ $bid ] : array()
								);
								?>
								<tr data-booking-id="<?php echo esc_attr( (string) $bid ); ?>">
									<td><?php echo esc_html( (string) $bid ); ?></td>
									<td><code class="small"><?php echo esc_html( (string) $b['booking_uid'] ); ?></code></td>
									<td class="small">
										<?php
										$btid = (int) $b['booking_type_id'];
										$btnm = isset( $ulbm_type_names[ $btid ] ) ? $ulbm_type_names[ $btid ] : '';
										echo $btnm ? esc_html( $btnm ) : esc_html( (string) $btid );
										?>
										<span class="text-muted"><?php echo $btnm ? ' · #' . esc_html( (string) $btid ) : ''; ?></span>
									</td>
									<td class="small">
										<?php echo $guest_email ? esc_html( $guest_email ) : '—'; ?>
									</td>
									<td class="small text-break ulbm-col-form-answers" style="max-width: 14rem;">
										<?php if ( '' !== $answers_cell['short'] ) : ?>
											<span class="d-inline-block" title="<?php echo esc_attr( $answers_cell['title'] ); ?>"><?php echo esc_html( $answers_cell['short'] ); ?></span>
										<?php else : ?>
											<span class="text-muted">—</span>
										<?php endif; ?>
									</td>
									<td>
										<span class="badge rounded-pill text-bg-<?php echo esc_attr( $ulbm_booking_status_class( (string) $b['status'] ) ); ?> ulbm-cell-status">
											<?php echo esc_html( (string) $b['status'] ); ?>
										</span>
									</td>
									<td>
										<span class="badge rounded-pill text-bg-<?php echo esc_attr( $ulbm_payment_status_class( (string) $b['payment_status'] ) ); ?> ulbm-cell-payment">
											<?php echo esc_html( (string) $b['payment_status'] ); ?>
										</span>
									</td>
									<td class="text-end"><?php echo esc_html( number_format_i18n( (float) $b['total'], 2 ) ); ?> <?php echo esc_html( (string) $b['currency'] ); ?></td>
									<td class="small"><?php echo esc_html( (string) $b['start_datetime'] ); ?></td>
									<td class="small"><?php echo esc_html( (string) $b['end_datetime'] ); ?></td>
									<td class="small"><?php echo esc_html( (string) $b['created_at'] ); ?></td>
									<td class="small">
										<div class="d-flex flex-column gap-1" style="min-width: 12rem;">
											<span class="text-muted text-uppercase" style="font-size: 0.65rem;"><?php esc_html_e( 'Booking', 'flex-booking-system' ); ?></span>
											<div class="btn-group btn-group-sm flex-wrap" role="group">
												<button type="button" class="btn btn-outline-success ulbm-booking-action" data-field="status" data-value="confirmed" title="<?php esc_attr_e( 'Confirm / accept', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Accept', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-warning ulbm-booking-action" data-field="status" data-value="on_hold" title="<?php esc_attr_e( 'On hold', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Hold', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-primary ulbm-booking-action" data-field="status" data-value="completed" title="<?php esc_attr_e( 'Completed', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Complete', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-danger ulbm-booking-action" data-field="status" data-value="cancelled" title="<?php esc_attr_e( 'Cancel', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Cancel', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-danger ulbm-booking-action" data-field="status" data-value="rejected" title="<?php esc_attr_e( 'Reject', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Reject', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-secondary ulbm-booking-action" data-field="status" data-value="pending" title="<?php esc_attr_e( 'Back to pending', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Pending', 'flex-booking-system' ); ?></button>
											</div>
											<span class="text-muted text-uppercase mt-1" style="font-size: 0.65rem;"><?php esc_html_e( 'Payment', 'flex-booking-system' ); ?></span>
											<div class="btn-group btn-group-sm flex-wrap" role="group">
												<?php foreach ( $payment_statuses as $ps ) : ?>
													<button type="button" class="btn btn-outline-secondary ulbm-booking-action" data-field="payment_status" data-value="<?php echo esc_attr( $ps ); ?>">
														<?php echo esc_html( $ps ); ?>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php if ( $pagination ) : ?>
			<div class="ulbm-admin-panel-foot px-3 py-2 border-top bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
				<span class="small text-muted"><?php esc_html_e( 'Use pagination to reach every row.', 'flex-booking-system' ); ?></span>
				<nav class="ulbm-pagination" aria-label="<?php esc_attr_e( 'Bookings pagination', 'flex-booking-system' ); ?>">
					<?php echo wp_kses_post( $pagination ); ?>
				</nav>
			</div>
		<?php endif; ?>
	</div>
</div>
