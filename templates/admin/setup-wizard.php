<?php
/**
 * Installation wizard — industries, CPT provisioning, professional ecosystem links.
 *
 * @package FlexBookingSystem
 *
 * @var array<string, array<string, mixed>> $ulbm_industry_catalog
 * @var array<int, array<string, mixed>>   $ulbm_professional_links
 * @var string[]                           $ulbm_enabled_industries
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap ulbm-admin-wrap container-fluid py-4">
	<div class="ulbm-setup-shell w-100 overflow-hidden shadow bg-white">
		<div class="ulbm-setup-hero bg-primary text-white py-4 px-4 px-lg-5">
			<h1 class="h4 mb-1"><?php echo esc_html( ulbm_plugin_menu_label() . ' — ' . __( 'Setup', 'flex-booking-system' ) ); ?></h1>
			<p class="mb-0 small opacity-75">
				<?php esc_html_e( 'Tell us what you sell or schedule. We create matching booking types and admin menus (for example Car bookings when you choose vehicle rental).', 'flex-booking-system' ); ?>
			</p>
		</div>
		<div class="ulbm-setup-content p-4 p-lg-5">
			<p class="text-muted mb-4">
				<?php esc_html_e( 'Select one or more verticals. You can change this later by adding booking types in the admin menu.', 'flex-booking-system' ); ?>
			</p>

			<div class="row g-3 mb-4">
				<?php foreach ( $ulbm_industry_catalog as $key => $def ) : ?>
					<div class="col-sm-6 col-lg-4 col-xxl-3">
						<div class="ulbm-industry-panel h-100 border shadow-sm bg-white p-3">
							<div class="form-check">
								<input
									class="form-check-input ulbm-industry-cb"
									type="checkbox"
									value="<?php echo esc_attr( (string) $key ); ?>"
									id="ulbm-ind-<?php echo esc_attr( (string) $key ); ?>"
									<?php checked( in_array( (string) $key, $ulbm_enabled_industries, true ) ); ?>
								/>
								<label class="form-check-label fw-semibold" for="ulbm-ind-<?php echo esc_attr( (string) $key ); ?>">
									<?php echo esc_html( (string) $def['select_label'] ); ?>
								</label>
							</div>
							<p class="small text-muted mb-0 mt-2"><?php echo esc_html( (string) $def['description'] ); ?></p>
							<p class="small mb-0 mt-2">
								<span class="text-muted"><?php esc_html_e( 'Creates:', 'flex-booking-system' ); ?></span>
								<code class="small"><?php echo esc_html( (string) $def['post_type'] ); ?></code>
							</p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="accordion mb-4" id="ulbm-pro-integrations">
				<div class="accordion-item border-0 shadow-sm">
					<h2 class="accordion-header" id="ulbm-acc-heading">
						<button class="accordion-button collapsed rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#ulbm-acc-body" aria-expanded="false" aria-controls="ulbm-acc-body">
							<?php esc_html_e( 'Popular booking plugins & platforms (reference)', 'flex-booking-system' ); ?>
						</button>
					</h2>
					<div id="ulbm-acc-body" class="accordion-collapse collapse" aria-labelledby="ulbm-acc-heading" data-bs-parent="#ulbm-pro-integrations">
						<div class="accordion-body bg-light bg-opacity-50">
							<p class="small text-muted mb-3">
								<?php esc_html_e( 'This plugin ships its own booking engine; listed items are optional complements for marketplaces or POS bridges.', 'flex-booking-system' ); ?>
							</p>
							<div class="row g-4">
								<?php foreach ( $ulbm_professional_links as $group ) : ?>
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
				<button type="button" class="btn btn-primary btn-lg px-4" id="ulbm-wizard-finish">
					<?php esc_html_e( 'Save selections & finish setup', 'flex-booking-system' ); ?>
				</button>
				<span class="small text-muted" id="ulbm-wizard-status" aria-live="polite"></span>
			</div>
		</div>
	</div>
</div>
