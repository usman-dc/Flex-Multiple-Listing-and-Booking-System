<?php
/**
 * Partner login form.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Vendor\VendorPages;

defined( 'ABSPATH' ) || exit;
?>
<div class="ulbm-vendor-auth ulbm-vendor-login">
	<div class="row justify-content-center">
		<div class="col-lg-5 col-xl-4">
			<div class="border rounded bg-white p-4 shadow-sm">
				<h2 class="h4 fw-bold mb-1"><?php esc_html_e( 'Partner Login', 'flex-booking-system' ); ?></h2>
				<p class="text-muted small mb-4"><?php esc_html_e( 'Sign in to manage your listings and bookings.', 'flex-booking-system' ); ?></p>

				<form id="ulbm-vendor-login-form" novalidate>
					<div class="mb-3">
						<label class="form-label" for="ulbm-login-email"><?php esc_html_e( 'Email', 'flex-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="email" class="form-control" id="ulbm-login-email" name="login" required>
					</div>
					<div class="mb-3">
						<label class="form-label" for="ulbm-login-pass"><?php esc_html_e( 'Password', 'flex-booking-system' ); ?> <span class="text-danger">*</span></label>
						<input type="password" class="form-control" id="ulbm-login-pass" name="password" required>
					</div>
					<div class="mb-3 form-check">
						<input type="checkbox" class="form-check-input" id="ulbm-login-remember" name="remember" value="1">
						<label class="form-check-label" for="ulbm-login-remember"><?php esc_html_e( 'Remember me', 'flex-booking-system' ); ?></label>
					</div>
					<div class="ulbm-vendor-feedback d-none alert py-2 small mb-3" role="alert"></div>
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-box-arrow-in-right me-1"></i><?php esc_html_e( 'Log In', 'flex-booking-system' ); ?>
					</button>
				</form>

				<p class="text-center small text-muted mt-3 mb-0">
					<?php esc_html_e( 'New partner?', 'flex-booking-system' ); ?>
					<a href="<?php echo esc_url( VendorPages::register_url() ); ?>"><?php esc_html_e( 'Register here', 'flex-booking-system' ); ?></a>
				</p>
			</div>
		</div>
	</div>
</div>
