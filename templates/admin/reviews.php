<?php
/**
 * Admin — listing reviews moderation.
 *
 * @package FlexBookingSystem
 *
 * @var array<int, array<string, mixed>> $ulbm_reviews
 * @var int                               $ulbm_reviews_total
 * @var int                               $ulbm_reviews_paged
 * @var int                               $ulbm_reviews_per_page
 * @var int                               $ulbm_reviews_total_pages
 * @var string                            $ulbm_reviews_status_filter
 */

defined( 'ABSPATH' ) || exit;

$ulbm_list_url = add_query_arg( 'page', 'ulbm-reviews', admin_url( 'admin.php' ) );
$ulbm_statuses = array(
	''         => __( 'All', 'flex-multiple-listing-and-booking-system' ),
	'pending'  => __( 'Pending', 'flex-multiple-listing-and-booking-system' ),
	'approved' => __( 'Approved', 'flex-multiple-listing-and-booking-system' ),
	'rejected' => __( 'Rejected', 'flex-multiple-listing-and-booking-system' ),
);
?>
<div class="wrap ulbm-admin-wrap ulbm-reviews-page container-fluid py-3">
	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
		<div>
			<h1 class="h3 fw-bold mb-1"><?php esc_html_e( 'Listing Reviews', 'flex-multiple-listing-and-booking-system' ); ?></h1>
			<p class="text-muted mb-0 small"><?php esc_html_e( 'Approve or reject guest reviews before they appear on listing pages.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		</div>
	</div>

	<div id="ulbm-reviews-feedback" class="alert d-none" role="status"></div>

	<div class="ulbm-admin-panel border rounded bg-white p-3 mb-4">
		<form method="get" class="row g-2 align-items-end">
			<input type="hidden" name="page" value="ulbm-reviews" />
			<div class="col-auto">
				<label class="form-label small mb-1"><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></label>
				<select name="ulbm_status" class="form-select form-select-sm">
					<?php foreach ( $ulbm_statuses as $ulbm_key => $ulbm_label ) : ?>
						<option value="<?php echo esc_attr( $ulbm_key ); ?>" <?php selected( $ulbm_reviews_status_filter, $ulbm_key ); ?>><?php echo esc_html( $ulbm_label ); ?></option>
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
						<th><?php esc_html_e( 'Listing', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Guest', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Rating', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Review', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th><?php esc_html_e( 'Date', 'flex-multiple-listing-and-booking-system' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Actions', 'flex-multiple-listing-and-booking-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $ulbm_reviews ) ) : ?>
						<tr>
							<td colspan="7" class="text-center text-muted py-5"><?php esc_html_e( 'No reviews found.', 'flex-multiple-listing-and-booking-system' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $ulbm_reviews as $ulbm_review ) : ?>
							<?php
							$ulbm_listing_id    = (int) $ulbm_review['listing_id'];
							$ulbm_listing_title = get_the_title( $ulbm_listing_id );
							$ulbm_listing_link  = get_edit_post_link( $ulbm_listing_id );
							$ulbm_status        = (string) $ulbm_review['status'];
							$ulbm_badge_class   = 'approved' === $ulbm_status ? 'text-bg-success' : ( 'pending' === $ulbm_status ? 'text-bg-warning' : 'text-bg-secondary' );
							?>
							<tr data-review-id="<?php echo esc_attr( (string) $ulbm_review['id'] ); ?>">
								<td>
									<?php if ( $ulbm_listing_link ) : ?>
										<a href="<?php echo esc_url( $ulbm_listing_link ); ?>"><?php echo esc_html( $ulbm_listing_title ?: '#' . $ulbm_listing_id ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $ulbm_listing_title ?: '#' . $ulbm_listing_id ); ?>
									<?php endif; ?>
								</td>
								<td>
									<div class="fw-semibold"><?php echo esc_html( (string) $ulbm_review['author_name'] ); ?></div>
									<div class="small text-muted"><?php echo esc_html( (string) $ulbm_review['author_email'] ); ?></div>
								</td>
								<td>
									<span class="ulbm-admin-review-stars">
										<?php for ( $ulbm_i = 1; $ulbm_i <= 5; $ulbm_i++ ) : ?>
											<i class="bi bi-star<?php echo $ulbm_i <= (int) $ulbm_review['rating'] ? '-fill text-warning' : ''; ?>"></i>
										<?php endfor; ?>
									</span>
								</td>
								<td class="small" style="max-width:280px;"><?php echo esc_html( wp_trim_words( (string) $ulbm_review['content'], 24 ) ); ?></td>
								<td><span class="badge <?php echo esc_attr( $ulbm_badge_class ); ?> ulbm-review-status-badge"><?php echo esc_html( ucfirst( $ulbm_status ) ); ?></span></td>
								<td class="small text-muted"><?php echo esc_html( mysql2date( 'Y-m-d H:i', (string) $ulbm_review['created_at'] ) ); ?></td>
								<td class="text-end text-nowrap">
									<?php if ( 'pending' === $ulbm_status || 'rejected' === $ulbm_status ) : ?>
										<button type="button" class="btn btn-sm btn-success ulbm-review-approve" data-id="<?php echo esc_attr( (string) $ulbm_review['id'] ); ?>"><?php esc_html_e( 'Approve', 'flex-multiple-listing-and-booking-system' ); ?></button>
									<?php endif; ?>
									<?php if ( 'pending' === $ulbm_status || 'approved' === $ulbm_status ) : ?>
										<button type="button" class="btn btn-sm btn-outline-secondary ulbm-review-reject" data-id="<?php echo esc_attr( (string) $ulbm_review['id'] ); ?>"><?php esc_html_e( 'Reject', 'flex-multiple-listing-and-booking-system' ); ?></button>
									<?php endif; ?>
									<button type="button" class="btn btn-sm btn-outline-danger ulbm-review-delete" data-id="<?php echo esc_attr( (string) $ulbm_review['id'] ); ?>"><?php esc_html_e( 'Delete', 'flex-multiple-listing-and-booking-system' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ( $ulbm_reviews_total_pages > 1 ) : ?>
		<nav class="mt-3" aria-label="<?php esc_attr_e( 'Reviews pagination', 'flex-multiple-listing-and-booking-system' ); ?>">
			<ul class="pagination">
				<?php for ( $ulbm_p = 1; $ulbm_p <= $ulbm_reviews_total_pages; $ulbm_p++ ) : ?>
					<li class="page-item <?php echo $ulbm_p === $ulbm_reviews_paged ? 'active' : ''; ?>">
						<a class="page-link" href="<?php echo esc_url( add_query_arg( array( 'paged' => $ulbm_p, 'ulbm_status' => $ulbm_reviews_status_filter ), $ulbm_list_url ) ); ?>"><?php echo esc_html( (string) $ulbm_p ); ?></a>
					</li>
				<?php endfor; ?>
			</ul>
		</nav>
	<?php endif; ?>
</div>
