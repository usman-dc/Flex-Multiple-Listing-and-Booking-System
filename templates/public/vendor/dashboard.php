<?php
/**
 * Partner dashboard � overview, listings, add, bookings, profile.
 *
 * @package FlexBookingSystem
 *
 * @var \WP_User                    $ulbm_vendor_user
 * @var string                      $ulbm_vendor_tab
 * @var \WP_Post[]                  $ulbm_vendor_listings
 * @var array<int,array<string,mixed>> $ulbm_vendor_bookings
 * @var array<int,array<string,mixed>> $ulbm_booking_types
 * @var array<string,mixed>|null    $ulbm_vendor_record
 * @var \WP_Post|null               $ulbm_edit_listing
 */

use FlexBooking\Listings\ListingMeta;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;
use FlexBooking\Vendor\VendorPages;

defined( 'ABSPATH' ) || exit;

$dashboard_base = VendorPages::dashboard_url();
$tab_url        = static function ( $tab ) use ( $dashboard_base ) {
	return add_query_arg( 'ulbm_tab', $tab, $dashboard_base );
};

$listing_count  = count( $ulbm_vendor_listings );
$booking_count  = count( $ulbm_vendor_bookings );
$published_cnt  = 0;
foreach ( $ulbm_vendor_listings as $lp ) {
	if ( 'publish' === $lp->post_status ) {
		++$published_cnt;
	}
}
?>
<div class="ulbm-vendor-dashboard">
	<div class="row g-4">
		<div class="col-lg-3">
			<div class="border rounded bg-white p-3 shadow-sm">
				<div class="text-center mb-3 pb-3 border-bottom">
					<div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-2" style="width:56px;height:56px;">
						<i class="bi bi-person-badge fs-4"></i>
					</div>
					<h3 class="h6 fw-bold mb-0"><?php echo esc_html( $ulbm_vendor_user->display_name ); ?></h3>
					<p class="small text-muted mb-0"><?php echo esc_html( $ulbm_vendor_user->user_email ); ?></p>
					<?php if ( $ulbm_vendor_record ) : ?>
						<span class="badge text-bg-<?php echo 'approved' === (string) $ulbm_vendor_record['status'] ? 'success' : 'warning'; ?> mt-2">
							<?php echo esc_html( ucfirst( (string) $ulbm_vendor_record['status'] ) ); ?>
						</span>
					<?php endif; ?>
				</div>
				<nav class="nav flex-column gap-1">
					<a class="nav-link rounded <?php echo 'overview' === $ulbm_vendor_tab ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo esc_url( $tab_url( 'overview' ) ); ?>"><i class="bi bi-speedometer2 me-2"></i><?php esc_html_e( 'Overview', 'flex-booking-system' ); ?></a>
					<a class="nav-link rounded <?php echo 'listings' === $ulbm_vendor_tab ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo esc_url( $tab_url( 'listings' ) ); ?>"><i class="bi bi-grid me-2"></i><?php esc_html_e( 'My Listings', 'flex-booking-system' ); ?></a>
					<a class="nav-link rounded <?php echo 'add' === $ulbm_vendor_tab ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo esc_url( $tab_url( 'add' ) ); ?>"><i class="bi bi-plus-circle me-2"></i><?php esc_html_e( 'Add Listing', 'flex-booking-system' ); ?></a>
					<a class="nav-link rounded <?php echo 'bookings' === $ulbm_vendor_tab ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo esc_url( $tab_url( 'bookings' ) ); ?>"><i class="bi bi-calendar-check me-2"></i><?php esc_html_e( 'Bookings', 'flex-booking-system' ); ?></a>
					<a class="nav-link rounded <?php echo 'profile' === $ulbm_vendor_tab ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo esc_url( $tab_url( 'profile' ) ); ?>"><i class="bi bi-gear me-2"></i><?php esc_html_e( 'Profile', 'flex-booking-system' ); ?></a>
					<a class="nav-link rounded text-danger" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><i class="bi bi-box-arrow-right me-2"></i><?php esc_html_e( 'Log out', 'flex-booking-system' ); ?></a>
				</nav>
			</div>
		</div>

		<div class="col-lg-9">
			<?php if ( 'overview' === $ulbm_vendor_tab ) : ?>
				<div class="border rounded bg-white p-4 shadow-sm mb-4">
					<h2 class="h5 fw-bold mb-3"><?php esc_html_e( 'Dashboard Overview', 'flex-booking-system' ); ?></h2>
					<div class="row g-3">
						<div class="col-sm-4">
							<div class="border rounded p-3 text-center">
								<div class="display-6 fw-bold text-primary"><?php echo esc_html( (string) $listing_count ); ?></div>
								<div class="small text-muted"><?php esc_html_e( 'Total Listings', 'flex-booking-system' ); ?></div>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="border rounded p-3 text-center">
								<div class="display-6 fw-bold text-success"><?php echo esc_html( (string) $published_cnt ); ?></div>
								<div class="small text-muted"><?php esc_html_e( 'Published', 'flex-booking-system' ); ?></div>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="border rounded p-3 text-center">
								<div class="display-6 fw-bold text-info"><?php echo esc_html( (string) $booking_count ); ?></div>
								<div class="small text-muted"><?php esc_html_e( 'Bookings', 'flex-booking-system' ); ?></div>
							</div>
						</div>
					</div>
					<div class="mt-4">
						<a href="<?php echo esc_url( $tab_url( 'add' ) ); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i><?php esc_html_e( 'Add your first listing', 'flex-booking-system' ); ?></a>
					</div>
				</div>

			<?php elseif ( 'listings' === $ulbm_vendor_tab ) : ?>
				<div class="border rounded bg-white p-4 shadow-sm">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h2 class="h5 fw-bold mb-0"><?php esc_html_e( 'My Listings', 'flex-booking-system' ); ?></h2>
						<a href="<?php echo esc_url( $tab_url( 'add' ) ); ?>" class="btn btn-sm btn-primary"><?php esc_html_e( 'Add New', 'flex-booking-system' ); ?></a>
					</div>
					<?php if ( empty( $ulbm_vendor_listings ) ) : ?>
						<p class="text-muted mb-0"><?php esc_html_e( 'No listings yet. Add a property, car, or service to get started.', 'flex-booking-system' ); ?></p>
					<?php else : ?>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead><tr><th><?php esc_html_e( 'Title', 'flex-booking-system' ); ?></th><th><?php esc_html_e( 'Type', 'flex-booking-system' ); ?></th><th><?php esc_html_e( 'Price', 'flex-booking-system' ); ?></th><th><?php esc_html_e( 'Status', 'flex-booking-system' ); ?></th><th></th></tr></thead>
								<tbody>
									<?php foreach ( $ulbm_vendor_listings as $listing ) :
										$lid   = (int) $listing->ID;
										$price = ListingMeta::get( $lid, ListingMeta::KEY_BASE_PRICE, 'string' );
										$type_id = ListingMeta::get( $lid, ListingMeta::KEY_BOOKING_TYPE_ID, 'int' );
										$type_label = '';
										foreach ( $ulbm_booking_types as $bt ) {
											if ( (int) $bt['id'] === $type_id ) {
												$type_label = (string) $bt['name'];
												break;
											}
										}
									?>
										<tr data-listing-id="<?php echo esc_attr( (string) $lid ); ?>">
											<td><a href="<?php echo esc_url( get_permalink( $lid ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $listing ) ); ?></a></td>
											<td><?php echo esc_html( $type_label ); ?></td>
											<td><?php echo esc_html( $price ); ?></td>
											<td><span class="badge text-bg-<?php echo 'publish' === $listing->post_status ? 'success' : 'warning'; ?>"><?php echo esc_html( ucfirst( $listing->post_status ) ); ?></span></td>
											<td class="text-end">
												<a href="<?php echo esc_url( add_query_arg( 'edit', $lid, $tab_url( 'add' ) ) ); ?>" class="btn btn-sm btn-outline-primary"><?php esc_html_e( 'Edit', 'flex-booking-system' ); ?></a>
												<button type="button" class="btn btn-sm btn-outline-danger ulbm-vendor-delete-listing" data-id="<?php echo esc_attr( (string) $lid ); ?>"><?php esc_html_e( 'Delete', 'flex-booking-system' ); ?></button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>

			<?php elseif ( 'add' === $ulbm_vendor_tab ) : ?>
				<?php
				$edit_id    = $ulbm_edit_listing ? (int) $ulbm_edit_listing->ID : 0;
				$edit_title = $ulbm_edit_listing ? $ulbm_edit_listing->post_title : '';
				$edit_content = $ulbm_edit_listing ? $ulbm_edit_listing->post_content : '';
				$edit_type  = $ulbm_edit_listing ? ListingMeta::get( $edit_id, ListingMeta::KEY_BOOKING_TYPE_ID, 'int' ) : 0;
				$edit_price = $ulbm_edit_listing ? ListingMeta::get( $edit_id, ListingMeta::KEY_BASE_PRICE, 'string' ) : '';
				$edit_addr  = $ulbm_edit_listing ? ListingMeta::get( $edit_id, ListingMeta::KEY_ADDRESS, 'string' ) : '';
				$edit_guests = $ulbm_edit_listing ? ListingMeta::get( $edit_id, ListingMeta::KEY_MAX_GUESTS, 'int' ) : 2;
				?>
				<div class="border rounded bg-white p-4 shadow-sm">
					<h2 class="h5 fw-bold mb-3"><?php echo $edit_id ? esc_html__( 'Edit Listing', 'flex-booking-system' ) : esc_html__( 'Add New Listing', 'flex-booking-system' ); ?></h2>
					<form id="ulbm-vendor-listing-form" class="row g-3" enctype="multipart/form-data" novalidate>
						<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $edit_id ); ?>">
						<div class="col-md-6">
							<label class="form-label"><?php esc_html_e( 'Listing type', 'flex-booking-system' ); ?> <span class="text-danger">*</span></label>
							<select class="form-select" name="booking_type_id" required <?php echo $edit_id ? 'disabled' : ''; ?>>
								<option value=""><?php esc_html_e( '� Select �', 'flex-booking-system' ); ?></option>
								<?php foreach ( $ulbm_booking_types as $bt ) : ?>
									<option value="<?php echo esc_attr( (string) (int) $bt['id'] ); ?>" <?php selected( $edit_type, (int) $bt['id'] ); ?>><?php echo esc_html( (string) $bt['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( $edit_id ) : ?>
								<input type="hidden" name="booking_type_id" value="<?php echo esc_attr( (string) $edit_type ); ?>">
							<?php endif; ?>
						</div>
						<div class="col-md-6">
							<label class="form-label"><?php esc_html_e( 'Base price', 'flex-booking-system' ); ?></label>
							<input type="text" class="form-control" name="base_price" value="<?php echo esc_attr( $edit_price ); ?>" placeholder="99.00">
						</div>
						<div class="col-12">
							<label class="form-label"><?php esc_html_e( 'Title', 'flex-booking-system' ); ?> <span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="title" value="<?php echo esc_attr( $edit_title ); ?>" required>
						</div>
						<div class="col-12">
							<label class="form-label"><?php esc_html_e( 'Description', 'flex-booking-system' ); ?></label>
							<textarea class="form-control" name="content" rows="5"><?php echo esc_textarea( $edit_content ); ?></textarea>
						</div>
						<div class="col-md-8">
							<label class="form-label"><?php esc_html_e( 'Address / location', 'flex-booking-system' ); ?></label>
							<input type="text" class="form-control" name="address" value="<?php echo esc_attr( $edit_addr ); ?>">
						</div>
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Max guests', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="max_guests" value="<?php echo esc_attr( (string) $edit_guests ); ?>" min="1">
						</div>
						<div class="col-12">
							<label class="form-label"><?php esc_html_e( 'Featured image', 'flex-booking-system' ); ?></label>
							<input type="file" class="form-control" name="featured_image" accept="image/*">
						</div>
						<div class="col-12">
							<div class="ulbm-vendor-feedback d-none alert py-2 small" role="alert"></div>
							<button type="submit" class="btn btn-primary">
								<i class="bi bi-check-circle me-1"></i><?php echo $edit_id ? esc_html__( 'Update Listing', 'flex-booking-system' ) : esc_html__( 'Submit Listing', 'flex-booking-system' ); ?>
							</button>
							<a href="<?php echo esc_url( $tab_url( 'listings' ) ); ?>" class="btn btn-outline-secondary ms-2"><?php esc_html_e( 'Cancel', 'flex-booking-system' ); ?></a>
						</div>
					</form>
				</div>

			<?php elseif ( 'bookings' === $ulbm_vendor_tab ) : ?>
				<div class="border rounded bg-white p-4 shadow-sm">
					<h2 class="h5 fw-bold mb-3"><?php esc_html_e( 'Bookings on My Listings', 'flex-booking-system' ); ?></h2>
					<?php if ( empty( $ulbm_vendor_bookings ) ) : ?>
						<p class="text-muted mb-0"><?php esc_html_e( 'No bookings yet for your listings.', 'flex-booking-system' ); ?></p>
					<?php else : ?>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead><tr><th><?php esc_html_e( 'Reference', 'flex-booking-system' ); ?></th><th><?php esc_html_e( 'Dates', 'flex-booking-system' ); ?></th><th><?php esc_html_e( 'Total', 'flex-booking-system' ); ?></th><th><?php esc_html_e( 'Status', 'flex-booking-system' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $ulbm_vendor_bookings as $bk ) : ?>
										<tr>
											<td><code><?php echo esc_html( (string) $bk['booking_uid'] ); ?></code></td>
											<td class="small"><?php echo esc_html( (string) $bk['start_datetime'] . ' ? ' . (string) $bk['end_datetime'] ); ?></td>
											<td><?php echo esc_html( (string) $bk['currency'] . ' ' . (string) $bk['total'] ); ?></td>
											<td><span class="badge text-bg-secondary"><?php echo esc_html( (string) $bk['status'] ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>

			<?php elseif ( 'profile' === $ulbm_vendor_tab ) : ?>
				<div class="border rounded bg-white p-4 shadow-sm">
					<h2 class="h5 fw-bold mb-3"><?php esc_html_e( 'Profile', 'flex-booking-system' ); ?></h2>
					<dl class="row mb-0">
						<dt class="col-sm-3"><?php esc_html_e( 'Name', 'flex-booking-system' ); ?></dt>
						<dd class="col-sm-9"><?php echo esc_html( $ulbm_vendor_user->display_name ); ?></dd>
						<dt class="col-sm-3"><?php esc_html_e( 'Email', 'flex-booking-system' ); ?></dt>
						<dd class="col-sm-9"><?php echo esc_html( $ulbm_vendor_user->user_email ); ?></dd>
						<dt class="col-sm-3"><?php esc_html_e( 'Phone', 'flex-booking-system' ); ?></dt>
						<dd class="col-sm-9"><?php echo esc_html( (string) get_user_meta( $ulbm_vendor_user->ID, 'ulbm_phone', true ) ); ?></dd>
						<?php if ( $ulbm_vendor_record && ! empty( $ulbm_vendor_record['business_name'] ) ) : ?>
							<dt class="col-sm-3"><?php esc_html_e( 'Business', 'flex-booking-system' ); ?></dt>
							<dd class="col-sm-9"><?php echo esc_html( (string) $ulbm_vendor_record['business_name'] ); ?></dd>
						<?php endif; ?>
						<dt class="col-sm-3"><?php esc_html_e( 'Account status', 'flex-booking-system' ); ?></dt>
						<dd class="col-sm-9"><?php echo esc_html( $ulbm_vendor_record ? ucfirst( (string) $ulbm_vendor_record['status'] ) : __( 'Active', 'flex-booking-system' ) ); ?></dd>
					</dl>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
