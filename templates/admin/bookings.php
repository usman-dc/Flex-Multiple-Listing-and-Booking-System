<?php
/**
 * All bookings — full-width table, filters, admin workflow actions, customer notifications.
 *
 * @package FlexBookingSystem
 *
 * @var array                                $fbs_bookings             Current page rows.
 * @var int                                  $fbs_bookings_total       Total rows (filtered).
 * @var int                                  $fbs_bookings_paged       Current page (1-based).
 * @var int                                  $fbs_bookings_per_page    Page size.
 * @var int                                  $fbs_bookings_total_pages Total pages.
 * @var string                               $fbs_status_filter        Active status filter or ''.
 * @var int                                  $fbs_type_filter          Booking type id filter or 0.
 * @var array<int, array<string, mixed>>     $fbs_booking_type_options All types for filter dropdown.
 * @var array<int, string>                   $fbs_type_names           Type id => display name.
 * @var array<int, string>                   $fbs_customer_emails      customer_id => email.
 * @var array<int, array<string, mixed>>     $fbs_booking_answers      booking_id => decoded form_values.
 */

use FlexBooking\Booking\BookingAdminUpdater;

defined( 'ABSPATH' ) || exit;

$fbs_status_filter        = isset( $fbs_status_filter ) ? (string) $fbs_status_filter : '';
$fbs_type_filter          = isset( $fbs_type_filter ) ? (int) $fbs_type_filter : 0;
$fbs_booking_type_options = isset( $fbs_booking_type_options ) && is_array( $fbs_booking_type_options ) ? $fbs_booking_type_options : array();
$fbs_type_names           = isset( $fbs_type_names ) && is_array( $fbs_type_names ) ? $fbs_type_names : array();
$fbs_customer_emails      = isset( $fbs_customer_emails ) && is_array( $fbs_customer_emails ) ? $fbs_customer_emails : array();
$fbs_booking_answers      = isset( $fbs_booking_answers ) && is_array( $fbs_booking_answers ) ? $fbs_booking_answers : array();

$fbs_format_form_answers_cell = static function ( $answers ) {
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

$fbs_booking_status_class = static function ( $status ) {
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

$fbs_payment_status_class = static function ( $payment_status ) {
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

$list_url = add_query_arg( 'page', 'fbs-bookings', admin_url( 'admin.php' ) );
if ( '' !== $fbs_status_filter ) {
	$list_url = add_query_arg( 'fbs_status', $fbs_status_filter, $list_url );
}
if ( $fbs_type_filter > 0 ) {
	$list_url = add_query_arg( 'fbs_type', (string) $fbs_type_filter, $list_url );
}
$pagination_base = esc_url_raw( add_query_arg( 'paged', '%#%', $list_url ) );

$pagination = paginate_links(
	array(
		'base'      => $pagination_base,
		'format'    => '',
		'prev_text' => '&laquo; ' . __( 'Previous', 'flex-booking-system' ),
		'next_text' => __( 'Next', 'flex-booking-system' ) . ' &raquo;',
		'total'     => max( 1, (int) $fbs_bookings_total_pages ),
		'current'   => (int) $fbs_bookings_paged,
		'type'      => 'plain',
	)
);

$showing_from = $fbs_bookings_total > 0 ? ( ( (int) $fbs_bookings_paged - 1 ) * (int) $fbs_bookings_per_page ) + 1 : 0;
$showing_to   = min( (int) $fbs_bookings_total, ( (int) $fbs_bookings_paged * (int) $fbs_bookings_per_page ) );

$statuses        = BookingAdminUpdater::booking_statuses();
$payment_statuses = BookingAdminUpdater::payment_statuses();
?>
<div class="wrap fbs-admin-wrap fbs-bookings-page container-fluid py-3">
	<div class="fbs-page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
		<div>
			<h1 class="h3 mb-1 fbs-page-title d-flex align-items-center gap-2">
				<i class="bi bi-calendar2-check text-primary" aria-hidden="true"></i>
				<?php esc_html_e( 'Bookings', 'flex-booking-system' ); ?>
			</h1>
			<p class="text-muted small mb-0"><?php esc_html_e( 'Review reservations, change status, payment, and optionally email the customer.', 'flex-booking-system' ); ?></p>
		</div>
	</div>

	<div class="fbs-admin-panel border rounded bg-white mb-3">
		<div class="p-3 d-flex flex-wrap align-items-end gap-3">
			<form method="get" class="d-flex flex-wrap align-items-end gap-2">
				<input type="hidden" name="page" value="fbs-bookings" />
				<div>
					<label class="form-label small text-muted mb-0" for="fbs_status"><?php esc_html_e( 'Filter by status', 'flex-booking-system' ); ?></label>
					<select class="form-select form-select-sm" name="fbs_status" id="fbs_status">
						<option value=""><?php esc_html_e( 'All statuses', 'flex-booking-system' ); ?></option>
						<?php foreach ( $statuses as $st ) : ?>
							<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $fbs_status_filter, $st ); ?>>
								<?php echo esc_html( $st ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="form-label small text-muted mb-0" for="fbs_type"><?php esc_html_e( 'Booking type', 'flex-booking-system' ); ?></label>
					<select class="form-select form-select-sm" name="fbs_type" id="fbs_type">
						<option value="0"><?php esc_html_e( 'All types', 'flex-booking-system' ); ?></option>
						<?php foreach ( $fbs_booking_type_options as $tto ) : ?>
							<option value="<?php echo esc_attr( (string) (int) $tto['id'] ); ?>" <?php selected( $fbs_type_filter, (int) $tto['id'] ); ?>>
								<?php echo esc_html( (string) $tto['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<button type="submit" class="btn btn-sm btn-outline-primary"><?php esc_html_e( 'Apply', 'flex-booking-system' ); ?></button>
			</form>
			<div class="form-check ms-auto">
				<input class="form-check-input" type="checkbox" id="fbs-bookings-notify" checked />
				<label class="form-check-label small" for="fbs-bookings-notify">
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
			(int) $fbs_bookings_total,
			(int) $fbs_bookings_per_page
		);
		?>
	</p>
	<?php if ( $fbs_bookings_total > 0 ) : ?>
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

	<div id="fbs-bookings-feedback" class="alert d-none" role="status"></div>

	<div class="fbs-admin-panel border rounded bg-white">
		<div class="fbs-admin-panel-head px-3 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom bg-white">
			<span class="fw-semibold"><?php esc_html_e( 'Booking records', 'flex-booking-system' ); ?></span>
			<span class="badge text-bg-light border"><?php echo esc_html( (string) (int) $fbs_bookings_total ); ?> <?php esc_html_e( 'rows', 'flex-booking-system' ); ?></span>
		</div>
		<div class="p-0 fbs-bookings-table-wrap">
			<div class="table-responsive">
				<table class="table fbs-table mb-0 align-middle w-100">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'ID', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'UID', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Booking type', 'flex-booking-system' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Guest email', 'flex-booking-system' ); ?></th>
							<th scope="col" class="fbs-col-form-answers"><?php esc_html_e( 'Form answers', 'flex-booking-system' ); ?></th>
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
						<?php if ( empty( $fbs_bookings ) ) : ?>
							<tr>
								<td colspan="12" class="text-muted p-4"><?php esc_html_e( 'No bookings match this filter.', 'flex-booking-system' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $fbs_bookings as $b ) : ?>
								<?php
								$bid = (int) $b['id'];
								$cid = ! empty( $b['customer_id'] ) ? (int) $b['customer_id'] : 0;
								$guest_email = '';
								if ( $cid && isset( $fbs_customer_emails[ $cid ] ) ) {
									$guest_email = $fbs_customer_emails[ $cid ];
								} elseif ( ! empty( $b['wp_user_id'] ) ) {
									$u = get_userdata( (int) $b['wp_user_id'] );
									if ( $u ) {
										$guest_email = $u->user_email;
									}
								}
								$answers_cell = $fbs_format_form_answers_cell(
									isset( $fbs_booking_answers[ $bid ] ) ? $fbs_booking_answers[ $bid ] : array()
								);
								?>
								<tr data-booking-id="<?php echo esc_attr( (string) $bid ); ?>">
									<td><?php echo esc_html( (string) $bid ); ?></td>
									<td><code class="small"><?php echo esc_html( (string) $b['booking_uid'] ); ?></code></td>
									<td class="small">
										<?php
										$btid = (int) $b['booking_type_id'];
										$btnm = isset( $fbs_type_names[ $btid ] ) ? $fbs_type_names[ $btid ] : '';
										echo $btnm ? esc_html( $btnm ) : esc_html( (string) $btid );
										?>
										<span class="text-muted"><?php echo $btnm ? ' · #' . esc_html( (string) $btid ) : ''; ?></span>
									</td>
									<td class="small">
										<?php echo $guest_email ? esc_html( $guest_email ) : '—'; ?>
									</td>
									<td class="small text-break fbs-col-form-answers" style="max-width: 14rem;">
										<?php if ( '' !== $answers_cell['short'] ) : ?>
											<span class="d-inline-block" title="<?php echo esc_attr( $answers_cell['title'] ); ?>"><?php echo esc_html( $answers_cell['short'] ); ?></span>
										<?php else : ?>
											<span class="text-muted">—</span>
										<?php endif; ?>
									</td>
									<td>
										<span class="badge rounded-pill text-bg-<?php echo esc_attr( $fbs_booking_status_class( (string) $b['status'] ) ); ?> fbs-cell-status">
											<?php echo esc_html( (string) $b['status'] ); ?>
										</span>
									</td>
									<td>
										<span class="badge rounded-pill text-bg-<?php echo esc_attr( $fbs_payment_status_class( (string) $b['payment_status'] ) ); ?> fbs-cell-payment">
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
												<button type="button" class="btn btn-outline-success fbs-booking-action" data-field="status" data-value="confirmed" title="<?php esc_attr_e( 'Confirm / accept', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Accept', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-warning fbs-booking-action" data-field="status" data-value="on_hold" title="<?php esc_attr_e( 'On hold', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Hold', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-primary fbs-booking-action" data-field="status" data-value="completed" title="<?php esc_attr_e( 'Completed', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Complete', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-danger fbs-booking-action" data-field="status" data-value="cancelled" title="<?php esc_attr_e( 'Cancel', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Cancel', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-danger fbs-booking-action" data-field="status" data-value="rejected" title="<?php esc_attr_e( 'Reject', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Reject', 'flex-booking-system' ); ?></button>
												<button type="button" class="btn btn-outline-secondary fbs-booking-action" data-field="status" data-value="pending" title="<?php esc_attr_e( 'Back to pending', 'flex-booking-system' ); ?>"><?php esc_html_e( 'Pending', 'flex-booking-system' ); ?></button>
											</div>
											<span class="text-muted text-uppercase mt-1" style="font-size: 0.65rem;"><?php esc_html_e( 'Payment', 'flex-booking-system' ); ?></span>
											<div class="btn-group btn-group-sm flex-wrap" role="group">
												<?php foreach ( $payment_statuses as $ps ) : ?>
													<button type="button" class="btn btn-outline-secondary fbs-booking-action" data-field="payment_status" data-value="<?php echo esc_attr( $ps ); ?>">
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
			<div class="fbs-admin-panel-foot px-3 py-2 border-top bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
				<span class="small text-muted"><?php esc_html_e( 'Use pagination to reach every row.', 'flex-booking-system' ); ?></span>
				<nav class="fbs-pagination" aria-label="<?php esc_attr_e( 'Bookings pagination', 'flex-booking-system' ); ?>">
					<?php echo wp_kses_post( $pagination ); ?>
				</nav>
			</div>
		<?php endif; ?>
	</div>
</div>
