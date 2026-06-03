<?php
/**
 * Account / partner toolbar for listing pages.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Vendor\VendorPages;
use FlexBooking\Vendor\VendorRole;

defined( 'ABSPATH' ) || exit;

$is_logged_in = is_user_logged_in();
$is_vendor    = $is_logged_in && VendorRole::can_manage_listings();
$user         = $is_logged_in ? wp_get_current_user() : null;

$login_url    = VendorPages::login_url();
$register_url = VendorPages::register_url();
$account_url  = $is_logged_in ? VendorPages::dashboard_url() : $login_url;
$logout_url   = VendorPages::logout_url();
?>
<div class="fbs-account-toolbar border-bottom bg-white shadow-sm">
	<div class="container fbs-container py-2">
		<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
			<div class="fbs-account-toolbar-left small text-muted d-none d-md-block">
				<?php if ( $is_vendor ) : ?>
					<i class="bi bi-shop me-1"></i><?php esc_html_e( 'Partner account', 'flex-multiple-listing-and-booking-system' ); ?>
				<?php else : ?>
					<i class="bi bi-megaphone me-1"></i><?php esc_html_e( 'Have something to list?', 'flex-multiple-listing-and-booking-system' ); ?>
				<?php endif; ?>
			</div>

			<div class="fbs-account-toolbar-actions d-flex align-items-center gap-2 ms-auto">
				<?php if ( $is_vendor ) : ?>
					<a href="<?php echo esc_url( VendorPages::add_listing_url() ); ?>" class="btn btn-primary btn-sm">
						<i class="bi bi-plus-lg me-1"></i><?php esc_html_e( 'Add Listing', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>

					<div class="dropdown">
						<button class="btn btn-outline-secondary btn-sm fbs-account-user-btn dropdown-toggle d-flex align-items-center gap-2"
							type="button"
							id="fbs-account-menu"
							data-bs-toggle="dropdown"
							aria-expanded="false"
							aria-label="<?php esc_attr_e( 'Account menu', 'flex-multiple-listing-and-booking-system' ); ?>">
							<span class="fbs-account-avatar rounded-circle overflow-hidden d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary">
								<?php
								if ( $user ) {
									echo get_avatar( $user->ID, 28, '', '', array( 'class' => 'rounded-circle' ) );
								} else {
									echo '<i class="bi bi-person-fill"></i>';
								}
								?>
							</span>
							<span class="d-none d-sm-inline"><?php echo esc_html( $user ? $user->display_name : '' ); ?></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-end shadow-sm fbs-account-dropdown" aria-labelledby="fbs-account-menu">
							<li class="dropdown-header small">
								<?php echo esc_html( $user ? $user->user_email : '' ); ?>
							</li>
							<li><hr class="dropdown-divider"></li>
							<li>
								<a class="dropdown-item" href="<?php echo esc_url( VendorPages::dashboard_url() ); ?>">
									<i class="bi bi-speedometer2 me-2"></i><?php esc_html_e( 'Dashboard', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?php echo esc_url( VendorPages::add_listing_url() ); ?>">
									<i class="bi bi-plus-circle me-2"></i><?php esc_html_e( 'Add Listing', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?php echo esc_url( VendorPages::listings_url() ); ?>">
									<i class="bi bi-grid me-2"></i><?php esc_html_e( 'My Listings', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?php echo esc_url( VendorPages::bookings_url() ); ?>">
									<i class="bi bi-calendar-check me-2"></i><?php esc_html_e( 'Bookings', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?php echo esc_url( VendorPages::profile_url() ); ?>">
									<i class="bi bi-gear me-2"></i><?php esc_html_e( 'Profile', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
							<li><hr class="dropdown-divider"></li>
							<li>
								<a class="dropdown-item text-danger" href="<?php echo esc_url( $logout_url ); ?>">
									<i class="bi bi-box-arrow-right me-2"></i><?php esc_html_e( 'Log out', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
						</ul>
					</div>

				<?php elseif ( $is_logged_in ) : ?>
					<a href="<?php echo esc_url( $register_url ); ?>" class="btn btn-primary btn-sm">
						<i class="bi bi-person-plus me-1"></i><?php esc_html_e( 'Become a Partner', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( $account_url ); ?>" class="btn btn-outline-secondary btn-sm" title="<?php esc_attr_e( 'My account', 'flex-multiple-listing-and-booking-system' ); ?>">
						<i class="bi bi-person-circle me-1"></i><span class="d-none d-sm-inline"><?php esc_html_e( 'Account', 'flex-multiple-listing-and-booking-system' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $logout_url ); ?>" class="btn btn-link btn-sm text-muted text-decoration-none">
						<?php esc_html_e( 'Log out', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>

				<?php else : ?>
					<a href="<?php echo esc_url( $register_url ); ?>" class="btn btn-primary btn-sm">
						<i class="bi bi-plus-circle me-1"></i><?php esc_html_e( 'List Your Property', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( $login_url ); ?>" class="btn btn-outline-secondary btn-sm">
						<i class="bi bi-box-arrow-in-right me-1"></i><?php esc_html_e( 'Log in', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( $account_url ); ?>" class="btn btn-light btn-sm border fbs-account-icon-btn" title="<?php esc_attr_e( 'Account / Log in', 'flex-multiple-listing-and-booking-system' ); ?>" aria-label="<?php esc_attr_e( 'Account / Log in', 'flex-multiple-listing-and-booking-system' ); ?>">
						<i class="bi bi-person-circle fs-5"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
