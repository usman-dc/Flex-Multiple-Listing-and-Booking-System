<?php
/**
 * Admin — listing reviews moderation.
 *
 * @package FlexBookingSystem
 *
 * @var array<int, array<string, mixed>> $fbs_reviews
 * @var int                               $fbs_reviews_total
 * @var int                               $fbs_reviews_paged
 * @var int                               $fbs_reviews_per_page
 * @var int                               $fbs_reviews_total_pages
 * @var string                            $fbs_reviews_status_filter
 */

defined( 'ABSPATH' ) || exit;

$list_url = add_query_arg( 'page', 'fbs-reviews', admin_url( 'admin.php' ) );
$statuses = array(
	''         => __( 'All', 'flex-multiple-listing-and-booking-system' ),
	'pending'  => __( 'Pending', 'flex-multiple-listing-and-booking-system' ),
	'approved' => __( 'Approved', 'flex-multiple-listing-and-booking-system' ),
	'rejected' => __( 'Rejected', 'flex-multiple-listing-and-booking-system' ),
);
?>
<div class="wrap fbs-admin-wrap fbs-reviews-page container-fluid py-3">
	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
		<div>
			<h1 class="h3 fw-bold mb-1"><?php esc_html_e( 'Listing Reviews', 'flex-multiple-listing-and-booking-system' ); ?></h1>
			<p class="text-muted mb-0 small"><?php esc_html_e( 'Approve or reject guest reviews before they appear on listing pages.', 'flex-multiple-listing-and-booking-system' ); ?></p>
		</div>
	</div>

	<div id="fbs-reviews-feedback" class="alert d-none" role="status"></div>

	<div class="fbs-admin-panel border rounded bg-white p-3 mb-4">
		<form method="get" class="row g-2 align-items-end">
			<input type="hidden" name="page" value="fbs-reviews" />
			<div class="col-auto">
				<label class="form-label small mb-1"><?php esc_html_e( 'Status', 'flex-multiple-listing-and-booking-system' ); ?></label>
				<select name="fbs_status" class="form-select form-select-sm">
					<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $fbs_reviews_status_filter, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-auto">
				<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e( 'Filter', 'flex-multiple-listing-and-booking-system' ); ?></button>
			</div>
		</form>
	</div>

	<div class="fbs-admin-panel border rounded bg-white p-0">
		<div class="table-responsive">
			<table class="table table-hover align-middle mb-0 fbs-table">
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
					<?php if ( empty( $fbs_reviews ) ) : ?>
						<tr>
							<td colspan="7" class="text-center text-muted py-5"><?php esc_html_e( 'No reviews found.', 'flex-multiple-listing-and-booking-system' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $fbs_reviews as $review ) : ?>
							<?php
							$listing_id   = (int) $review['listing_id'];
							$listing_title = get_the_title( $listing_id );
							$listing_link  = get_edit_post_link( $listing_id );
							$status        = (string) $review['status'];
							$badge_class   = 'approved' === $status ? 'text-bg-success' : ( 'pending' === $status ? 'text-bg-warning' : 'text-bg-secondary' );
							?>
							<tr data-review-id="<?php echo esc_attr( (string) $review['id'] ); ?>">
								<td>
									<?php if ( $listing_link ) : ?>
										<a href="<?php echo esc_url( $listing_link ); ?>"><?php echo esc_html( $listing_title ?: '#' . $listing_id ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $listing_title ?: '#' . $listing_id ); ?>
									<?php endif; ?>
								</td>
								<td>
									<div class="fw-semibold"><?php echo esc_html( (string) $review['author_name'] ); ?></div>
									<div class="small text-muted"><?php echo esc_html( (string) $review['author_email'] ); ?></div>
								</td>
								<td>
									<span class="fbs-admin-review-stars">
										<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
											<i class="bi bi-star<?php echo $i <= (int) $review['rating'] ? '-fill text-warning' : ''; ?>"></i>
										<?php endfor; ?>
									</span>
								</td>
								<td class="small" style="max-width:280px;"><?php echo esc_html( wp_trim_words( (string) $review['content'], 24 ) ); ?></td>
								<td><span class="badge <?php echo esc_attr( $badge_class ); ?> fbs-review-status-badge"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
								<td class="small text-muted"><?php echo esc_html( mysql2date( 'Y-m-d H:i', (string) $review['created_at'] ) ); ?></td>
								<td class="text-end text-nowrap">
									<?php if ( 'pending' === $status || 'rejected' === $status ) : ?>
										<button type="button" class="btn btn-sm btn-success fbs-review-approve" data-id="<?php echo esc_attr( (string) $review['id'] ); ?>"><?php esc_html_e( 'Approve', 'flex-multiple-listing-and-booking-system' ); ?></button>
									<?php endif; ?>
									<?php if ( 'pending' === $status || 'approved' === $status ) : ?>
										<button type="button" class="btn btn-sm btn-outline-secondary fbs-review-reject" data-id="<?php echo esc_attr( (string) $review['id'] ); ?>"><?php esc_html_e( 'Reject', 'flex-multiple-listing-and-booking-system' ); ?></button>
									<?php endif; ?>
									<button type="button" class="btn btn-sm btn-outline-danger fbs-review-delete" data-id="<?php echo esc_attr( (string) $review['id'] ); ?>"><?php esc_html_e( 'Delete', 'flex-multiple-listing-and-booking-system' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ( $fbs_reviews_total_pages > 1 ) : ?>
		<nav class="mt-3" aria-label="<?php esc_attr_e( 'Reviews pagination', 'flex-multiple-listing-and-booking-system' ); ?>">
			<ul class="pagination">
				<?php for ( $p = 1; $p <= $fbs_reviews_total_pages; $p++ ) : ?>
					<li class="page-item <?php echo $p === $fbs_reviews_paged ? 'active' : ''; ?>">
						<a class="page-link" href="<?php echo esc_url( add_query_arg( array( 'paged' => $p, 'fbs_status' => $fbs_reviews_status_filter ), $list_url ) ); ?>"><?php echo esc_html( (string) $p ); ?></a>
					</li>
				<?php endfor; ?>
			</ul>
		</nav>
	<?php endif; ?>
</div>
