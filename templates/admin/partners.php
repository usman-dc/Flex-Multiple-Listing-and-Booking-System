<?php
/**
 * Admin — partner account management.
 *
 * @package FlexBookingSystem
 *
 * @var array<int, array<string, mixed>> $ulbm_partners
 * @var int                               $ulbm_partners_total
 * @var int                               $ulbm_partners_paged
 * @var int                               $ulbm_partners_per_page
 * @var int                               $ulbm_partners_total_pages
 * @var string                            $ulbm_partners_status_filter
 */

defined( 'ABSPATH' ) || exit;

use FlexBooking\Vendor\VendorAdminService;

$ulbm_list_url = add_query_arg( 'page', 'ulbm-partners', admin_url( 'admin.php' ) );
$ulbm_statuses = array(
	''          => __( 'All', 'flex-multiple-listing-and-booking-system' ),
	'pending'   => __( 'Pending', 'flex-multiple-listing-and-booking-system' ),
	'approved'  => __( 'Approved', 'flex-multiple-listing-and-booking-system' ),
	'suspended' => __( 'Suspended', 'flex-multiple-listing-and-booking-system' ),
);
?>
<div class="wrap ulbm-admin-wrap ulbm-partners-page container-fluid py-3">
	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
		<div>
			<h1 class="h3 fw-bold mb-1"><?php esc_html_e( 'Partners', 'flex-multiple-listing-and-booking-system' ); ?></h1>
			<p class="text-muted mb-0 small"><?php esc_html_e( 'Approve partner registrations, suspend access, or remove partner accounts.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		</div>
	</div>

	<div id="ulbm-partners-feedback" class="alert d-none" role="status"></div>

	<div class="ulbm-admin-panel border rounded bg-white p-3 mb-4">
		<form method="get" class="row g-2 align-items-end">
			<input type="hidden" name="page" value="ulbm-partners" />
			<div class="col-auto">
				<label class="form-label small mb-1"><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></label>
				<select name="ulbm_status" class="form-select form-select-sm">
					<?php foreach ( $ulbm_statuses as $ulbm_key => $ulbm_label ) : ?>
						<option value="<?php echo esc_attr( $ulbm_key ); ?>" <?php selected( $ulbm_partners_status_filter, $ulbm_key ); ?>><?php echo esc_html( $ulbm_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-auto">
				<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e( 'Filter', 'flex-multiple-listing-and-booking-system' ); ?></button>
			</div>
		</form>
	</div>

	<div class="ulbm-admin-panel border rounded bg-white p-0">
		<div class="table-responsive">
			<table class="table table-hover align-middle mb-0 ulbm-table">
				<thead class="table-light">
					<tr>
						<th><?php esc_html_e( 'Partner', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Business', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Listings', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Registered', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'flex-multiple-listing-and-booking-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $ulbm_partners ) ) : ?>
						<tr>
							<td colspan="7" class="text-center text-muted py-5"><?php esc_html_e( 'No partner accounts found.', 'flex-multiple-listing-and-booking-system' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $ulbm_partners as $ulbm_partner ) : ?>
							<?php
							$ulbm_user_id    = (int) $ulbm_partner['wp_user_id'];
							$ulbm_user       = get_userdata( $ulbm_user_id );
							$ulbm_status     = (string) $ulbm_partner['status'];
							$ulbm_badge      = 'approved' === $ulbm_status ? 'text-bg-success' : ( 'pending' === $ulbm_status ? 'text-bg-warning' : 'text-bg-secondary' );
							$ulbm_edit_link  = $ulbm_user ? get_edit_user_link( $ulbm_user_id ) : '';
							$ulbm_listings   = VendorAdminService::count_listings( $ulbm_user_id );
							$ulbm_phone      = $ulbm_user ? (string) get_user_meta( $ulbm_user_id, 'ulbm_phone', true ) : '';
							$ulbm_is_admin   = $ulbm_user && user_can( $ulbm_user_id, 'manage_options' );
							?>
							<tr data-partner-id="<?php echo esc_attr( (string) $ulbm_partner['id'] ); ?>">
								<td>
									<?php if ( $ulbm_user ) : ?>
										<div class="fw-semibold"><?php echo esc_html( $ulbm_user->display_name ); ?></div>
										<div class="small text-muted"><?php echo esc_html( $ulbm_user->user_login ); ?></div>
									<?php else : ?>
										<span class="text-muted"><?php esc_html_e( 'User deleted', 'flex-multiple-listing-and-booking-system' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<input
										type="text"
										class="form-control form-control-sm ulbm-partner-business-input"
										value="<?php echo esc_attr( (string) $ulbm_partner['business_name'] ); ?>"
										data-vendor-id="<?php echo esc_attr( (string) $ulbm_partner['id'] ); ?>"
										<?php disabled( $ulbm_is_admin ); ?>
									/>
								</td>
								<td>
									<?php if ( $ulbm_user ) : ?>
										<div class="small"><?php echo esc_html( $ulbm_user->user_email ); ?></div>
										<?php if ( $ulbm_phone ) : ?>
											<div class="small text-muted"><?php echo esc_html( $ulbm_phone ); ?></div>
										<?php endif; ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( (string) $ulbm_listings ); ?></td>
								<td><span class="badge <?php echo esc_attr( $ulbm_badge ); ?> ulbm-partner-status-badge"><?php echo esc_html( ucfirst( $ulbm_status ) ); ?></span></td>
								<td class="small text-muted"><?php echo esc_html( mysql2date( 'Y-m-d H:i', (string) $ulbm_partner['created_at'] ) ); ?></td>
								<td class="text-end text-nowrap">
									<?php if ( $ulbm_edit_link ) : ?>
										<a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url( $ulbm_edit_link ); ?>"><?php esc_html_e( 'Edit user', 'flex-multiple-listing-and-booking-system' ); ?></a>
									<?php endif; ?>
									<?php if ( ! $ulbm_is_admin ) : ?>
										<?php if ( 'pending' === $ulbm_status || 'suspended' === $ulbm_status ) : ?>
											<button type="button" class="btn btn-sm btn-success ulbm-partner-approve" data-id="<?php echo esc_attr( (string) $ulbm_partner['id'] ); ?>"><?php esc_html_e( 'Approve', 'flex-multiple-listing-and-booking-system' ); ?></button>
										<?php endif; ?>
										<?php if ( 'approved' === $ulbm_status ) : ?>
											<button type="button" class="btn btn-sm btn-outline-secondary ulbm-partner-suspend" data-id="<?php echo esc_attr( (string) $ulbm_partner['id'] ); ?>"><?php esc_html_e( 'Suspend', 'flex-multiple-listing-and-booking-system' ); ?></button>
										<?php endif; ?>
										<button type="button" class="btn btn-sm btn-outline-danger ulbm-partner-delete" data-id="<?php echo esc_attr( (string) $ulbm_partner['id'] ); ?>"><?php esc_html_e( 'Remove', 'flex-multiple-listing-and-booking-system' ); ?></button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ( $ulbm_partners_total_pages > 1 ) : ?>
		<nav class="mt-3" aria-label="<?php esc_attr_e( 'Partners pagination', 'flex-multiple-listing-and-booking-system' ); ?>">
			<ul class="pagination">
				<?php for ( $ulbm_p = 1; $ulbm_p <= $ulbm_partners_total_pages; $ulbm_p++ ) : ?>
					<li class="page-item <?php echo $ulbm_p === $ulbm_partners_paged ? 'active' : ''; ?>">
						<a class="page-link" href="<?php echo esc_url( add_query_arg( array( 'paged' => $ulbm_p, 'ulbm_status' => $ulbm_partners_status_filter ), $ulbm_list_url ) ); ?>"><?php echo esc_html( (string) $ulbm_p ); ?></a>
					</li>
				<?php endfor; ?>
			</ul>
		</nav>
	<?php endif; ?>
</div>
