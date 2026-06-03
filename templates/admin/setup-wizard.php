<?php
/**
 * Installation wizard — industries, CPT provisioning, professional ecosystem links.
 *
 * @package FlexBookingSystem
 *
 * @var array<string, array<string, mixed>> $fbs_industry_catalog
 * @var array<int, array<string, mixed>>   $fbs_professional_links
 * @var string[]                           $fbs_enabled_industries
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap fbs-admin-wrap container-fluid py-4">
	<div class="fbs-setup-shell w-100 overflow-hidden shadow bg-white">
		<div class="fbs-setup-hero bg-primary text-white py-4 px-4 px-lg-5">
			<h1 class="h4 mb-1"><?php echo esc_html( fbs_plugin_menu_label() . ' — ' . __( 'Setup', 'flex-multiple-listing-and-booking-system' ) ); ?></h1>
			<p class="mb-0 small opacity-75">
				<?php esc_html_e( 'Tell us what you sell or schedule. We create matching booking types and admin menus (for example Car bookings when you choose vehicle rental).', 'flex-multiple-listing-and-booking-system' ); ?>
			</p>
		</div>
		<div class="fbs-setup-content p-4 p-lg-5">
			<p class="text-muted mb-4">
				<?php esc_html_e( 'Select one or more verticals. You can change this later by adding booking types in the admin menu.', 'flex-multiple-listing-and-booking-system' ); ?>
			</p>

			<div class="row g-3 mb-4">
				<?php foreach ( $fbs_industry_catalog as $key => $def ) : ?>
					<div class="col-sm-6 col-lg-4 col-xxl-3">
						<div class="fbs-industry-panel h-100 border shadow-sm bg-white p-3">
							<div class="form-check">
								<input
									class="form-check-input fbs-industry-cb"
									type="checkbox"
									value="<?php echo esc_attr( (string) $key ); ?>"
									id="fbs-ind-<?php echo esc_attr( (string) $key ); ?>"
									<?php checked( in_array( (string) $key, $fbs_enabled_industries, true ) ); ?>
								/>
								<label class="form-check-label fw-semibold" for="fbs-ind-<?php echo esc_attr( (string) $key ); ?>">
									<?php echo esc_html( (string) $def['select_label'] ); ?>
								</label>
							</div>
							<p class="small text-muted mb-0 mt-2"><?php echo esc_html( (string) $def['description'] ); ?></p>
							<p class="small mb-0 mt-2">
								<span class="text-muted"><?php esc_html_e( 'Creates:', 'flex-multiple-listing-and-booking-system' ); ?></span>
								<code class="small"><?php echo esc_html( (string) $def['post_type'] ); ?></code>
							</p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="accordion mb-4" id="fbs-pro-integrations">
				<div class="accordion-item border-0 shadow-sm">
					<h2 class="accordion-header" id="fbs-acc-heading">
						<button class="accordion-button collapsed rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#fbs-acc-body" aria-expanded="false" aria-controls="fbs-acc-body">
							<?php esc_html_e( 'Popular booking plugins & platforms (reference)', 'flex-multiple-listing-and-booking-system' ); ?>
						</button>
					</h2>
					<div id="fbs-acc-body" class="accordion-collapse collapse" aria-labelledby="fbs-acc-heading" data-bs-parent="#fbs-pro-integrations">
						<div class="accordion-body bg-light bg-opacity-50">
							<p class="small text-muted mb-3">
								<?php esc_html_e( 'This plugin ships its own booking engine; listed items are optional complements for marketplaces or POS bridges.', 'flex-multiple-listing-and-booking-system' ); ?>
							</p>
							<div class="row g-4">
								<?php foreach ( $fbs_professional_links as $group ) : ?>
									<div class="col-md-6">
										<h3 class="h6 text-uppercase text-muted"><?php echo esc_html( (string) $group['title'] ); ?></h3>
										<ul class="list-unstyled mb-0">
											<?php foreach ( $group['items'] as $item ) : ?>
												<li class="mb-3 pb-3 border-bottom border-light-subtle">
													<a href="<?php echo esc_url( (string) $item['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none">
														<?php echo esc_html( (string) $item['name'] ); ?>
													</a>
													<span class="small text-muted d-block"><?php echo esc_html( (string) $item['note'] ); ?></span>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="d-flex flex-wrap gap-2 align-items-center">
				<button type="button" class="btn btn-primary btn-lg px-4" id="fbs-wizard-finish">
					<?php esc_html_e( 'Save selections & finish setup', 'flex-multiple-listing-and-booking-system' ); ?>
				</button>
				<span class="small text-muted" id="fbs-wizard-status" aria-live="polite"></span>
			</div>
		</div>
	</div>
</div>
