<?php
/**
 * Partner registration form.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Vendor\VendorPages;

defined( 'ABSPATH' ) || exit;
?>
<div class="ulbm-vendor-auth ulbm-vendor-register">
	<div class="row justify-content-center">
		<div class="col-lg-7 col-xl-6">
			<div class="border rounded bg-white p-4 shadow-sm">
				<h2 class="h4 fw-bold mb-1"><?php esc_html_e( 'Join as a Partner', 'flex-multiple-listing-and-booking-system' ); ?></h2>
				<p class="text-muted small mb-4"><?php esc_html_e( 'Register to list your property, car, tour, or service and manage bookings from your dashboard.', 'flex-multiple-listing-and-booking-system' ); ?></p>

				<form id="ulbm-vendor-register-form" class="row g-3" novalidate>
					<div class="col-md-6">
						<label class="form-label" for="ulbm-reg-first"><?php esc_html_e( 'First name', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="ulbm-reg-first" name="first_name" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm-reg-last"><?php esc_html_e( 'Last name', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="ulbm-reg-last" name="last_name" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm-reg-email"><?php esc_html_e( 'Email', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="email" class="form-control" id="ulbm-reg-email" name="email" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm-reg-phone"><?php esc_html_e( 'Phone', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="tel" class="form-control" id="ulbm-reg-phone" name="phone">
					</div>
					<div class="col-12">
						<label class="form-label" for="ulbm-reg-business"><?php esc_html_e( 'Business / brand name', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="text" class="form-control" id="ulbm-reg-business" name="business_name" placeholder="<?php esc_attr_e( 'Optional', 'flex-multiple-listing-and-booking-system' ); ?>">
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm-reg-pass"><?php esc_html_e( 'Password', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="password" class="form-control" id="ulbm-reg-pass" name="password" minlength="6" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm-reg-pass2"><?php esc_html_e( 'Confirm password', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="password" class="form-control" id="ulbm-reg-pass2" name="password_confirm" minlength="6" required>
					</div>
					<div class="col-12">
						<div class="ulbm-vendor-feedback d-none alert py-2 small" role="alert"></div>
						<button type="submit" class="btn btn-primary w-100">
							<i class="bi bi-person-plus me-1"></i><?php esc_html_e( 'Create Partner Account', 'flex-multiple-listing-and-booking-system' ); ?>
						</button>
					</div>
				</form>

				<p class="text-center small text-muted mt-3 mb-0">
					<?php esc_html_e( 'Already have an account?', 'flex-multiple-listing-and-booking-system' ); ?>
					<a href="<?php echo esc_url( VendorPages::login_url() ); ?>"><?php esc_html_e( 'Log in', 'flex-multiple-listing-and-booking-system' ); ?></a>
				</p>
			</div>
		</div>
	</div>
</div>
