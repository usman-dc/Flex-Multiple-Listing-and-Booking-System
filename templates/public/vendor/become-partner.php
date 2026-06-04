<?php
/**
 * Become partner form for logged-in users without vendor role.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Vendor\VendorPages;

defined( 'ABSPATH' ) || exit;

$user = wp_get_current_user();
?>
<div class="ulbm-vendor-auth ulbm-vendor-become-partner">
	<div class="row justify-content-center">
		<div class="col-lg-7 col-xl-6">
			<div class="border rounded bg-white p-4 shadow-sm">
				<h2 class="h4 fw-bold mb-1"><?php esc_html_e( 'Become a Partner', 'flex-multiple-listing-and-booking-system' ); ?></h2>
				<p class="text-muted small mb-4">
					<?php
					printf(
						/* translators: %s: user display name */
						esc_html__( 'Hi %s — enable partner access to add listings and manage bookings.', 'flex-multiple-listing-and-booking-system' ),
						esc_html( $user->display_name )
					);
					?>
				</p>

				<form id="ulbm-vendor-become-partner-form" class="row g-3" novalidate>
					<div class="col-12">
						<label class="form-label" for="ulbm-bp-business"><?php esc_html_e( 'Business / brand name', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="text" class="form-control" id="ulbm-bp-business" name="business_name" value="<?php echo esc_attr( $user->display_name ); ?>">
					</div>
					<div class="col-12">
						<label class="form-label" for="ulbm-bp-phone"><?php esc_html_e( 'Phone', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="tel" class="form-control" id="ulbm-bp-phone" name="phone" value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'ulbm_phone', true ) ); ?>">
					</div>
					<div class="col-12">
						<div class="ulbm-vendor-feedback d-none alert py-2 small" role="alert"></div>
						<button type="submit" class="btn btn-primary w-100">
							<i class="bi bi-person-check me-1"></i><?php esc_html_e( 'Enable Partner Access', 'flex-multiple-listing-and-booking-system' ); ?>
						</button>
					</div>
				</form>

				<p class="text-center small text-muted mt-3 mb-0">
					<a href="<?php echo esc_url( VendorPages::dashboard_url() ); ?>"><?php esc_html_e( 'Back to account', 'flex-multiple-listing-and-booking-system' ); ?></a>
				</p>
			</div>
		</div>
	</div>
</div>
