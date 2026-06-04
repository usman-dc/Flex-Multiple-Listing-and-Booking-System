<?php
/**
 * Account / partner toolbar for listing pages.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Vendor\VendorPages;
use FlexBooking\Vendor\VendorRole;

defined( 'ABSPATH' ) || exit;

$ulbm_is_logged_in = is_user_logged_in();
$ulbm_is_vendor    = $ulbm_is_logged_in && VendorRole::can_manage_listings();
$ulbm_user         = $ulbm_is_logged_in ? wp_get_current_user() : null;

$ulbm_login_url    = VendorPages::login_url();
$ulbm_register_url = VendorPages::register_url();
$ulbm_account_url  = $ulbm_is_logged_in ? VendorPages::dashboard_url() : $ulbm_login_url;
$ulbm_logout_url   = VendorPages::logout_url();
?>
<div class="ulbm-account-toolbar border-bottom bg-white shadow-sm">
	<div class="container ulbm-container py-2">
		<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
			<div class="ulbm-account-toolbar-left small text-muted d-none d-md-block">
				<?php if ( $ulbm_is_vendor ) : ?>
					<i class="bi bi-shop me-1"></i><?php esc_html_e( 'Partner account', 'flex-multiple-listing-and-booking-system' ); ?>
				<?php else : ?>
					<i class="bi bi-megaphone me-1"></i><?php esc_html_e( 'Have something to list?', 'flex-multiple-listing-and-booking-system' ); ?>
				<?php endif; ?>
			</div>

			<div class="ulbm-account-toolbar-actions d-flex align-items-center gap-2 ms-auto">
				<?php if ( $ulbm_is_vendor ) : ?>
					<a href="<?php echo esc_url( VendorPages::add_listing_url() ); ?>" class="btn btn-primary btn-sm">
						<i class="bi bi-plus-lg me-1"></i><?php esc_html_e( 'Add Listing', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>

					<div class="dropdown">
						<button class="btn btn-outline-secondary btn-sm ulbm-account-user-btn dropdown-toggle d-flex align-items-center gap-2"
							type="button"
							id="ulbm-account-menu"
							data-bs-toggle="dropdown"
							aria-expanded="false"
							aria-label="<?php esc_attr_e( 'Account menu', 'flex-multiple-listing-and-booking-system' ); ?>">
							<span class="ulbm-account-avatar rounded-circle overflow-hidden d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary">
								<?php
								if ( $ulbm_user ) {
									echo get_avatar( $ulbm_user->ID, 28, '', '', array( 'class' => 'rounded-circle' ) );
								} else {
									echo '<i class="bi bi-person-fill"></i>';
								}
								?>
							</span>
							<span class="d-none d-sm-inline"><?php echo esc_html( $ulbm_user ? $ulbm_user->display_name : '' ); ?></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-end shadow-sm ulbm-account-dropdown" aria-labelledby="ulbm-account-menu">
							<li class="dropdown-header small">
								<?php echo esc_html( $ulbm_user ? $ulbm_user->user_email : '' ); ?>
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
								<a class="dropdown-item text-danger" href="<?php echo esc_url( $ulbm_logout_url ); ?>">
									<i class="bi bi-box-arrow-right me-2"></i><?php esc_html_e( 'Log out', 'flex-multiple-listing-and-booking-system' ); ?>
								</a>
							</li>
						</ul>
					</div>

				<?php elseif ( $ulbm_is_logged_in ) : ?>
					<a href="<?php echo esc_url( $ulbm_register_url ); ?>" class="btn btn-primary btn-sm">
						<i class="bi bi-person-plus me-1"></i><?php esc_html_e( 'Become a Partner', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( $ulbm_account_url ); ?>" class="btn btn-outline-secondary btn-sm" title="<?php esc_attr_e( 'My account', 'flex-multiple-listing-and-booking-system' ); ?>">
						<i class="bi bi-person-circle me-1"></i><span class="d-none d-sm-inline"><?php esc_html_e( 'Account', 'flex-multiple-listing-and-booking-system' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $ulbm_logout_url ); ?>" class="btn btn-link btn-sm text-muted text-decoration-none">
						<?php esc_html_e( 'Log out', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>

				<?php else : ?>
					<a href="<?php echo esc_url( $ulbm_register_url ); ?>" class="btn btn-primary btn-sm">
						<i class="bi bi-plus-circle me-1"></i><?php esc_html_e( 'List Your Property', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( $ulbm_login_url ); ?>" class="btn btn-outline-secondary btn-sm">
						<i class="bi bi-box-arrow-in-right me-1"></i><?php esc_html_e( 'Log in', 'flex-multiple-listing-and-booking-system' ); ?>
					</a>
					<a href="<?php echo esc_url( $ulbm_account_url ); ?>" class="btn btn-light btn-sm border ulbm-account-icon-btn" title="<?php esc_attr_e( 'Account / Log in', 'flex-multiple-listing-and-booking-system' ); ?>" aria-label="<?php esc_attr_e( 'Account / Log in', 'flex-multiple-listing-and-booking-system' ); ?>">
						<i class="bi bi-person-circle fs-5"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
