<?php
/**
 * Global settings — currency, colors, layout, notifications, shortcodes reference.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Front\ColorSettings;

defined( 'ABSPATH' ) || exit;

$raw    = get_option( 'ulbm_general_settings', '{}' );
$parsed = json_decode( (string) $raw, true );
if ( ! is_array( $parsed ) ) {
	$parsed = array();
}

$defaults = array(
	'currency'           => 'USD',
	'currency_position'  => 'left',
	'date_format'        => 'Y-m-d',
	'time_format'        => 'H:i',
	'grid_columns'       => 3,
	'grid_per_page'      => 12,
	'card_border_radius' => 12,
	'card_shadow'        => true,
	'show_filters'       => true,
	'slider_height'      => 480,
	'sidebar_position'   => 'right',
	'container_width'    => 1400,
	'notify_customer_status' => false,
	'notify_on_confirmed'    => true,
	'notify_on_completed'    => false,
	'notify_on_cancelled'    => true,
	'notify_on_rejected'     => false,
	'notify_on_on_hold'      => false,
	'notify_on_pending'      => false,
	'notify_reply_to'        => '',
	'vendor_register_page'   => 0,
	'vendor_login_page'      => 0,
	'vendor_dashboard_page'  => 0,
	'vendor_auto_approve'    => false,
	'enable_google_maps_embed' => false,
	'vendor_auto_publish'    => true,
	'grid_gap'               => 24,
	'grid_padding_x'         => 0,
	'grid_padding_y'         => 0,
	'grid_margin_top'        => 0,
	'grid_margin_bottom'     => 0,
	'grid_card_padding'      => 16,
	'reviews_enabled'        => true,
	'reviews_auto_approve'   => false,
	'grid_show_rating'       => true,
	'grid_show_amenities'    => true,
	'grid_amenities_limit'   => 4,
);
$s = array_merge( $defaults, ColorSettings::defaults(), is_array( $parsed ) ? $parsed : array() );
foreach ( ColorSettings::fields() as $color_field_key => $color_field ) {
	$s[ $color_field_key ] = ColorSettings::sanitize_hex(
		(string) ( $parsed[ $color_field_key ] ?? $s[ $color_field_key ] ?? $color_field['default'] ),
		$color_field['default']
	);
}

$ulbm_settings_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Auto-create partner pages when not configured yet.
if ( \FlexBooking\Core\Capabilities::can_access_admin() ) {
	\FlexBooking\Vendor\VendorPageProvisioner::maybe_auto_provision();
	$parsed = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );
	if ( is_array( $parsed ) ) {
		$s = array_merge( $defaults, ColorSettings::defaults(), $parsed );
		foreach ( ColorSettings::fields() as $color_field_key => $color_field ) {
			$s[ $color_field_key ] = ColorSettings::sanitize_hex(
				(string) ( $parsed[ $color_field_key ] ?? $s[ $color_field_key ] ?? $color_field['default'] ),
				$color_field['default']
			);
		}
	}
}

$ulbm_vendor_page_rows = \FlexBooking\Vendor\VendorPageProvisioner::status_rows();

// Shortcodes reference.
$ulbm_shortcodes_help = array(
	array( 'tag' => 'ulbm_booking_form', 'description' => __( 'Booking form for a specific type.', 'flex-booking-system' ), 'example' => '[ulbm_booking_form id="1"]', 'attrs' => '<code>id</code> (required)' ),
	array( 'tag' => 'ulbm_listing_grid', 'description' => __( 'Listing grid with AJAX filters.', 'flex-booking-system' ), 'example' => '[ulbm_listing_grid type="car-rental" columns="3" limit="12"]', 'attrs' => '<code>type</code>, <code>columns</code>, <code>limit</code>' ),
	array( 'tag' => 'ulbm_search', 'description' => __( 'Search UI (AJAX).', 'flex-booking-system' ), 'example' => '[ulbm_search]', 'attrs' => '<code>layout</code>' ),
	array( 'tag' => 'ulbm_register', 'description' => __( 'Partner registration form.', 'flex-booking-system' ), 'example' => '[ulbm_register]', 'attrs' => '—' ),
	array( 'tag' => 'ulbm_login', 'description' => __( 'Partner login form.', 'flex-booking-system' ), 'example' => '[ulbm_login]', 'attrs' => '—' ),
	array( 'tag' => 'ulbm_dashboard', 'description' => __( 'Partner dashboard (listings, bookings, profile).', 'flex-booking-system' ), 'example' => '[ulbm_dashboard]', 'attrs' => '—' ),
	array( 'tag' => 'ulbm_become_partner', 'description' => __( 'Call-to-action block for partner signup.', 'flex-booking-system' ), 'example' => '[ulbm_become_partner]', 'attrs' => '<code>title</code>, <code>text</code>' ),
);
$ulbm_type_repo_for_sc = new \FlexBooking\Booking\BookingTypeRepository();
$ulbm_all_types_for_sc = $ulbm_type_repo_for_sc->get_all();
foreach ( $ulbm_all_types_for_sc as $ulbm_sc_type ) {
	$tid = (int) $ulbm_sc_type['id'];
	$ulbm_shortcodes_help[] = array(
		'tag'         => 'ulbm_booking_form id="' . $tid . '"',
		'description' => sprintf(
			/* translators: %s: booking type name */
			__( 'Form: %s', 'flex-booking-system' ),
			esc_html( (string) $ulbm_sc_type['name'] )
		),
		'example'     => '[ulbm_booking_form id="' . $tid . '"]',
		'attrs'       => '<code>id="' . $tid . '"</code>',
	);
}
$ulbm_shortcodes_help = apply_filters( 'ulbm_settings_shortcodes_help', $ulbm_shortcodes_help );
?>
<div class="wrap ulbm-admin-wrap container-fluid py-3">
	<?php if ( ! empty( $_GET['ulbm-settings-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="alert alert-success" role="status"><?php esc_html_e( 'Settings saved.', 'flex-booking-system' ); ?></div>
	<?php endif; ?>
	<div class="ulbm-page-header mb-4">
		<h1 class="h3 mb-1 ulbm-page-title"><?php echo esc_html( ulbm_plugin_menu_label() . ' — ' . __( 'Settings', 'flex-booking-system' ) ); ?></h1>
		<p class="text-muted small mb-0"><?php esc_html_e( 'Currency, layout, notifications, shortcodes, and partner portal.', 'flex-booking-system' ); ?></p>
	</div>

	<form method="post" id="ulbm-settings-form" action="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-settings' ) ); ?>">
		<?php wp_nonce_field( 'ulbm_save_settings', 'ulbm_settings_nonce' ); ?>
		<input type="hidden" name="ulbm_save_settings" value="1" />
		<input type="hidden" name="page" value="ulbm-settings" />
		<input type="hidden" name="ulbm_settings_tab" id="ulbm_settings_tab" value="<?php echo esc_attr( $ulbm_settings_tab ); ?>" />

		<!-- TABS -->
		<ul class="nav nav-tabs mb-4" role="tablist" id="ulbm-settings-tabs">
			<li class="nav-item"><button class="nav-link<?php echo 'general' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-general" data-ulbm-tab="general" type="button"><?php esc_html_e( 'General', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'colors' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-colors" data-ulbm-tab="colors" type="button"><?php esc_html_e( 'Colors', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'layout' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-layout" data-ulbm-tab="layout" type="button"><?php esc_html_e( 'Layout', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'notify' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-notify" data-ulbm-tab="notify" type="button"><?php esc_html_e( 'Notifications', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'shortcodes' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-shortcodes" data-ulbm-tab="shortcodes" type="button"><?php esc_html_e( 'Shortcodes', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'cpts' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-cpts" data-ulbm-tab="cpts" type="button"><?php esc_html_e( 'Post Types', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'demo' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-demo" data-ulbm-tab="demo" type="button"><?php esc_html_e( 'Demo Content', 'flex-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'partner' === $ulbm_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#ulbm-st-partner" data-ulbm-tab="partner" type="button"><?php esc_html_e( 'Partner Portal', 'flex-booking-system' ); ?></button></li>
		</ul>

		<div class="tab-content">

			<!-- GENERAL -->
			<div class="tab-pane fade<?php echo 'general' === $ulbm_settings_tab ? ' show active' : ''; ?>" id="ulbm-st-general">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i><?php esc_html_e( 'Currency & Formats', 'flex-booking-system' ); ?></h5>
					<div class="row g-3">
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Currency code', 'flex-booking-system' ); ?></label>
							<input class="form-control" name="ulbm_currency" value="<?php echo esc_attr( $s['currency'] ); ?>" maxlength="3">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Position', 'flex-booking-system' ); ?></label>
							<select class="form-select" name="ulbm_currency_position">
								<?php foreach ( array( 'left' => 'Left ($99)', 'right' => 'Right (99$)', 'left_space' => 'Left space ($ 99)', 'right_space' => 'Right space (99 $)' ) as $pv => $pl ) : ?>
									<option value="<?php echo esc_attr( $pv ); ?>" <?php selected( $s['currency_position'], $pv ); ?>><?php echo esc_html( $pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Date format', 'flex-booking-system' ); ?></label>
							<input class="form-control" name="ulbm_date_format" value="<?php echo esc_attr( $s['date_format'] ); ?>">
							<span class="form-text"><?php esc_html_e( 'PHP date format', 'flex-booking-system' ); ?></span>
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Time format', 'flex-booking-system' ); ?></label>
							<input class="form-control" name="ulbm_time_format" value="<?php echo esc_attr( $s['time_format'] ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- COLORS -->
			<div class="tab-pane fade<?php echo 'colors' === $ulbm_settings_tab ? ' show active' : ''; ?>" id="ulbm-st-colors">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
						<h5 class="fw-bold mb-0"><i class="bi bi-palette me-2"></i><?php esc_html_e( 'Color Scheme', 'flex-booking-system' ); ?></h5>
						<button type="button" class="btn btn-outline-secondary btn-sm" id="ulbm-reset-colors"><?php esc_html_e( 'Reset all colors to defaults', 'flex-booking-system' ); ?></button>
					</div>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Colors apply only inside plugin listing grids and forms — not your whole WordPress page. Use the hex field (e.g. #f5f6f8) for each color, then Save All Settings.', 'flex-booking-system' ); ?></p>
					<?php if ( ! empty( $_GET['ulbm-settings-saved'] ) && 'colors' === $ulbm_settings_tab ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
						<div class="alert alert-info small py-2 mb-3">
							<?php
							printf(
								/* translators: %s: hex color */
								esc_html__( 'Saved. Page background is now: %s', 'flex-booking-system' ),
								'<code>' . esc_html( (string) ( $s['color_page_bg'] ?? '' ) ) . '</code>'
							);
							?>
						</div>
					<?php endif; ?>
					<input type="hidden" name="ulbm_colors_json" id="ulbm_colors_json" value="">
					<button type="button" class="btn btn-sm btn-outline-warning mb-3" id="ulbm-fix-page-bg"><?php esc_html_e( 'Fix red page background (reset to light gray)', 'flex-booking-system' ); ?></button>
					<?php
					$color_fields = ColorSettings::fields();
					$color_groups = ColorSettings::groups();
					foreach ( $color_groups as $group_id => $group_label ) :
						?>
						<div class="ulbm-color-group mb-4">
							<h6 class="fw-semibold text-uppercase small text-muted mb-3 border-bottom pb-2"><?php echo esc_html( $group_label ); ?></h6>
							<div class="row g-3">
								<?php
								foreach ( $color_fields as $field_key => $field ) :
									if ( $field['group'] !== $group_id ) {
										continue;
									}
									$preview_key = str_replace( 'color_', '', $field_key );
									$val         = isset( $s[ $field_key ] ) ? (string) $s[ $field_key ] : $field['default'];
									?>
									<div class="col-md-6 col-lg-4 col-xl-3">
										<label class="form-label small fw-semibold mb-1" for="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
										<div class="d-flex align-items-center gap-2">
											<input type="color" class="form-control form-control-color ulbm-color-picker flex-shrink-0" data-ulbm-target="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>" data-ulbm-color-key="<?php echo esc_attr( $preview_key ); ?>" data-ulbm-settings-key="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $val ); ?>" aria-hidden="true" tabindex="-1">
											<input type="text" id="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>" class="form-control form-control-sm ulbm-color-input ulbm-color-hex-input" name="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>" data-ulbm-color-key="<?php echo esc_attr( $preview_key ); ?>" data-ulbm-settings-key="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $val ); ?>" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" spellcheck="false" autocomplete="off">
										</div>
										<?php if ( ! empty( $field['hint'] ) ) : ?>
											<span class="form-text d-block"><?php echo esc_html( $field['hint'] ); ?></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
					<div id="ulbm-color-preview" class="ulbm-color-preview mt-2 p-4 border rounded" style="<?php echo esc_attr( ColorSettings::admin_preview_inline_style() ); ?>">
						<p class="small mb-3"><strong><?php esc_html_e( 'Live preview', 'flex-booking-system' ); ?></strong> <span class="text-muted"><?php esc_html_e( '(updates as you pick colors)', 'flex-booking-system' ); ?></span></p>
						<div class="ulbm-color-preview-page rounded p-3">
							<div class="ulbm-color-preview-card rounded p-3 shadow-sm">
								<span class="ulbm-color-preview-badge"><?php esc_html_e( 'Featured', 'flex-booking-system' ); ?></span>
								<div class="ulbm-color-preview-title fw-bold"><?php esc_html_e( 'Sample listing title', 'flex-booking-system' ); ?></div>
								<div class="ulbm-color-preview-muted small"><?php esc_html_e( 'Downtown · 4 guests', 'flex-booking-system' ); ?></div>
								<div class="ulbm-color-preview-price fw-bold my-2">$299 <span class="ulbm-color-preview-sale small"><?php esc_html_e( 'was $349', 'flex-booking-system' ); ?></span></div>
								<div class="ulbm-color-preview-stars small mb-2" aria-hidden="true">★★★★★</div>
								<button type="button" class="ulbm-color-preview-btn me-1" data-ulbm-preview="primary"><?php esc_html_e( 'Book now', 'flex-booking-system' ); ?></button>
								<button type="button" class="ulbm-color-preview-btn me-1" data-ulbm-preview="secondary"><?php esc_html_e( 'Secondary', 'flex-booking-system' ); ?></button>
								<button type="button" class="ulbm-color-preview-btn me-1" data-ulbm-preview="success"><?php esc_html_e( 'Success', 'flex-booking-system' ); ?></button>
								<button type="button" class="ulbm-color-preview-btn" data-ulbm-preview="accent"><?php esc_html_e( 'Accent', 'flex-booking-system' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- LAYOUT -->
			<div class="tab-pane fade" id="ulbm-st-layout">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-arrows-angle-expand me-2"></i><?php esc_html_e( 'Container Width', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Maximum content width for all plugin pages, shortcodes, blocks, and Elementor widgets.', 'flex-booking-system' ); ?></p>
					<div class="row g-3 align-items-end">
						<div class="col-md-4">
							<label class="form-label" for="ulbm_container_width"><?php esc_html_e( 'Max container width (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" id="ulbm_container_width" name="ulbm_container_width" value="<?php echo esc_attr( (string) (int) ( $s['container_width'] ?? 1400 ) ); ?>" min="768" max="2400" step="10">
							<span class="form-text"><?php esc_html_e( 'Default: 1400px. Applies to listing pages, partner portal, grids, and forms.', 'flex-booking-system' ); ?></span>
						</div>
						<div class="col-md-8">
							<div class="border rounded p-3 bg-light small">
								<strong><?php esc_html_e( 'Preview', 'flex-booking-system' ); ?></strong>
								<div class="mt-2 mx-auto border border-primary border-2 bg-white text-center py-2" style="max-width:<?php echo esc_attr( (string) (int) ( $s['container_width'] ?? 1400 ) ); ?>px;width:100%;">
									<?php
									printf(
										/* translators: %d: container max width in pixels */
										esc_html__( 'Content area — %d px max', 'flex-booking-system' ),
										(int) ( $s['container_width'] ?? 1400 )
									);
									?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-grid me-2"></i><?php esc_html_e( 'Grid & Card Settings', 'flex-booking-system' ); ?></h5>
					<div class="row g-3">
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Grid columns', 'flex-booking-system' ); ?></label>
							<select class="form-select" name="ulbm_grid_columns">
								<?php for ( $c = 2; $c <= 4; $c++ ) : ?>
									<option value="<?php echo esc_attr( (string) $c ); ?>" <?php selected( (int) $s['grid_columns'], $c ); ?>><?php echo esc_html( (string) $c ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Posts per page', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_per_page" value="<?php echo esc_attr( (string) (int) $s['grid_per_page'] ); ?>" min="1" max="100">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Card border radius (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_card_border_radius" value="<?php echo esc_attr( (string) (int) $s['card_border_radius'] ); ?>" min="0" max="50">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Slider height (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_slider_height" value="<?php echo esc_attr( (string) (int) $s['slider_height'] ); ?>" min="200" max="800">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Sidebar position', 'flex-booking-system' ); ?></label>
							<select class="form-select" name="ulbm_sidebar_position">
								<option value="right" <?php selected( $s['sidebar_position'], 'right' ); ?>><?php esc_html_e( 'Right (default)', 'flex-booking-system' ); ?></option>
								<option value="left" <?php selected( $s['sidebar_position'], 'left' ); ?>><?php esc_html_e( 'Left', 'flex-booking-system' ); ?></option>
							</select>
						</div>
						<div class="col-md-4">
							<div class="form-check mt-4">
								<input class="form-check-input" type="checkbox" name="ulbm_card_shadow" id="ulbm_card_shadow" <?php checked( ! empty( $s['card_shadow'] ) ); ?>>
								<label class="form-check-label" for="ulbm_card_shadow"><?php esc_html_e( 'Card shadow on hover', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-check mt-4">
								<input class="form-check-input" type="checkbox" name="ulbm_show_filters" id="ulbm_show_filters" <?php checked( ! empty( $s['show_filters'] ) ); ?>>
								<label class="form-check-label" for="ulbm_show_filters"><?php esc_html_e( 'Show filter bar on grid', 'flex-booking-system' ); ?></label>
							</div>
						</div>
					</div>
				</div>

				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-arrows-move me-2"></i><?php esc_html_e( 'Grid Spacing', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Control padding, margin, and gap for listing grids (shortcode, block, Elementor, and archives).', 'flex-booking-system' ); ?></p>
					<div class="row g-3">
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Column gap (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_gap" value="<?php echo esc_attr( (string) (int) ( $s['grid_gap'] ?? 24 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Padding X (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_padding_x" value="<?php echo esc_attr( (string) (int) ( $s['grid_padding_x'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Padding Y (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_padding_y" value="<?php echo esc_attr( (string) (int) ( $s['grid_padding_y'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Margin top (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_margin_top" value="<?php echo esc_attr( (string) (int) ( $s['grid_margin_top'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Margin bottom (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_margin_bottom" value="<?php echo esc_attr( (string) (int) ( $s['grid_margin_bottom'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Card padding (px)', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" name="ulbm_grid_card_padding" value="<?php echo esc_attr( (string) (int) ( $s['grid_card_padding'] ?? 16 ) ); ?>" min="0" max="120">
						</div>
					</div>
				</div>

				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-card-list me-2"></i><?php esc_html_e( 'Grid Card Content', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Choose what appears on each listing card in grids, archives, and AJAX search results.', 'flex-booking-system' ); ?></p>
					<div class="row g-3">
						<div class="col-md-4">
							<input type="hidden" name="ulbm_grid_show_rating" value="0" />
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_grid_show_rating" id="ulbm_grid_show_rating" value="1" <?php checked( ! isset( $s['grid_show_rating'] ) || ! empty( $s['grid_show_rating'] ) ); ?>>
								<label class="form-check-label" for="ulbm_grid_show_rating"><?php esc_html_e( 'Show star rating & review count', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-4">
							<input type="hidden" name="ulbm_grid_show_amenities" value="0" />
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_grid_show_amenities" id="ulbm_grid_show_amenities" value="1" <?php checked( ! isset( $s['grid_show_amenities'] ) || ! empty( $s['grid_show_amenities'] ) ); ?>>
								<label class="form-check-label" for="ulbm_grid_show_amenities"><?php esc_html_e( 'Show amenities on cards', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="ulbm_grid_amenities_limit"><?php esc_html_e( 'Max amenities per card', 'flex-booking-system' ); ?></label>
							<input type="number" class="form-control" id="ulbm_grid_amenities_limit" name="ulbm_grid_amenities_limit" value="<?php echo esc_attr( (string) (int) ( $s['grid_amenities_limit'] ?? 4 ) ); ?>" min="1" max="8">
						</div>
					</div>
				</div>

				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-chat-square-text me-2"></i><?php esc_html_e( 'Listing Reviews', 'flex-booking-system' ); ?></h5>
					<div class="row g-3">
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_reviews_enabled" id="ulbm_reviews_enabled" <?php checked( ! isset( $s['reviews_enabled'] ) || ! empty( $s['reviews_enabled'] ) ); ?>>
								<label class="form-check-label" for="ulbm_reviews_enabled"><?php esc_html_e( 'Allow guests to submit reviews on listing pages', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_reviews_auto_approve" id="ulbm_reviews_auto_approve" <?php checked( ! empty( $s['reviews_auto_approve'] ) ); ?>>
								<label class="form-check-label" for="ulbm_reviews_auto_approve"><?php esc_html_e( 'Publish reviews immediately (skip admin approval)', 'flex-booking-system' ); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- NOTIFICATIONS -->
			<div class="tab-pane fade" id="ulbm-st-notify">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-envelope me-2"></i><?php esc_html_e( 'Customer Email Notifications', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small"><?php esc_html_e( 'When staff change booking status, the customer receives an email if a valid address exists.', 'flex-booking-system' ); ?></p>
					<div class="row g-3">
						<div class="col-12">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_notify_customer_status" id="ulbm_notify_customer_status" <?php checked( ! empty( $s['notify_customer_status'] ) ); ?>>
								<label class="form-check-label fw-semibold" for="ulbm_notify_customer_status"><?php esc_html_e( 'Enable customer emails on status changes', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check"><input class="form-check-input" type="checkbox" name="ulbm_notify_on_confirmed" id="ulbm_nc1" <?php checked( ! empty( $s['notify_on_confirmed'] ) ); ?>><label class="form-check-label" for="ulbm_nc1"><?php esc_html_e( 'Confirmed / Accepted', 'flex-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="ulbm_notify_on_completed" id="ulbm_nc2" <?php checked( ! empty( $s['notify_on_completed'] ) ); ?>><label class="form-check-label" for="ulbm_nc2"><?php esc_html_e( 'Completed', 'flex-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="ulbm_notify_on_cancelled" id="ulbm_nc3" <?php checked( ! empty( $s['notify_on_cancelled'] ) ); ?>><label class="form-check-label" for="ulbm_nc3"><?php esc_html_e( 'Cancelled', 'flex-booking-system' ); ?></label></div>
						</div>
						<div class="col-md-6">
							<div class="form-check"><input class="form-check-input" type="checkbox" name="ulbm_notify_on_rejected" id="ulbm_nc4" <?php checked( ! empty( $s['notify_on_rejected'] ) ); ?>><label class="form-check-label" for="ulbm_nc4"><?php esc_html_e( 'Rejected', 'flex-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="ulbm_notify_on_hold" id="ulbm_nc5" <?php checked( ! empty( $s['notify_on_on_hold'] ) ); ?>><label class="form-check-label" for="ulbm_nc5"><?php esc_html_e( 'On hold', 'flex-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="ulbm_notify_on_pending" id="ulbm_nc6" <?php checked( ! empty( $s['notify_on_pending'] ) ); ?>><label class="form-check-label" for="ulbm_nc6"><?php esc_html_e( 'Pending', 'flex-booking-system' ); ?></label></div>
						</div>
						<div class="col-md-6">
							<label class="form-label"><?php esc_html_e( 'Reply-To address', 'flex-booking-system' ); ?></label>
							<input class="form-control" type="email" name="ulbm_notify_reply_to" value="<?php echo esc_attr( $s['notify_reply_to'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- SHORTCODES -->
			<div class="tab-pane fade" id="ulbm-st-shortcodes">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-code-slash me-2"></i><?php esc_html_e( 'Available Shortcodes', 'flex-booking-system' ); ?></h5>
					<div class="table-responsive">
						<table class="table ulbm-table table-bordered align-middle mb-0 w-100">
							<thead class="table-light">
								<tr>
									<th><?php esc_html_e( 'Shortcode', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Attributes', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Description', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Copy', 'flex-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $ulbm_shortcodes_help as $row ) : ?>
									<tr>
										<td><code><?php echo esc_html( '[' . $row['tag'] . ']' ); ?></code></td>
										<td class="small"><?php echo wp_kses_post( $row['attrs'] ); ?></td>
										<td class="small"><?php echo wp_kses_post( $row['description'] ); ?></td>
										<td><code class="user-select-all small"><?php echo esc_html( $row['example'] ); ?></code></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- POST TYPES -->
			<div class="tab-pane fade" id="ulbm-st-cpts">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-collection me-2"></i><?php esc_html_e( 'Registered Post Types', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Each published booking type auto-creates a CPT. Add posts under the plugin admin menu.', 'flex-booking-system' ); ?></p>
					<?php if ( ! empty( $ulbm_all_types_for_sc ) ) : ?>
						<div class="table-responsive">
							<table class="table ulbm-table table-bordered align-middle mb-0 w-100">
								<thead class="table-light">
									<tr>
										<th><?php esc_html_e( 'Type', 'flex-booking-system' ); ?></th>
										<th><?php esc_html_e( 'CPT Slug', 'flex-booking-system' ); ?></th>
										<th><?php esc_html_e( 'Archive', 'flex-booking-system' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'flex-booking-system' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $ulbm_all_types_for_sc as $ulbm_rpt ) :
										$rpt_slug    = (string) $ulbm_rpt['slug'];
										$rpt_cpt     = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $rpt_slug );
										$rpt_archive = home_url( '/' . $rpt_slug . '/' );
									?>
										<tr>
											<td><strong><?php echo esc_html( (string) $ulbm_rpt['name'] ); ?></strong> <span class="text-muted small">#<?php echo esc_html( (string) (int) $ulbm_rpt['id'] ); ?></span></td>
											<td><code><?php echo esc_html( $rpt_cpt ); ?></code></td>
											<td class="small"><a href="<?php echo esc_url( $rpt_archive ); ?>" target="_blank"><?php echo esc_html( $rpt_archive ); ?></a></td>
											<td>
												<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $rpt_cpt ) ); ?>" class="btn btn-sm btn-outline-primary me-1"><?php esc_html_e( 'View', 'flex-booking-system' ); ?></a>
												<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $rpt_cpt ) ); ?>" class="btn btn-sm btn-outline-success"><?php esc_html_e( 'Add New', 'flex-booking-system' ); ?></a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<p class="text-muted"><?php esc_html_e( 'No booking types created yet.', 'flex-booking-system' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- DEMO CONTENT -->
			<div class="tab-pane fade" id="ulbm-st-demo">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-2"><i class="bi bi-magic me-2"></i><?php esc_html_e( 'One-Click Demo Content', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small mb-4">
						<?php esc_html_e( 'Generate sample listings with images, pricing, gallery, features, FAQ, and extra services. Perfect for testing your grid, filters, and single pages before adding real content.', 'flex-booking-system' ); ?>
					</p>

					<?php if ( ! empty( $ulbm_all_types_for_sc ) ) : ?>
						<div class="row g-3 align-items-end mb-3">
							<div class="col-md-3">
								<label class="form-label" for="ulbm-demo-count"><?php esc_html_e( 'Posts per type', 'flex-booking-system' ); ?></label>
								<input type="number" class="form-control" id="ulbm-demo-count" value="20" min="1" max="50">
							</div>
							<div class="col-md-9">
								<div class="form-check mt-4">
									<input class="form-check-input" type="checkbox" id="ulbm-demo-select-all" checked>
									<label class="form-check-label fw-semibold" for="ulbm-demo-select-all"><?php esc_html_e( 'Select all booking types', 'flex-booking-system' ); ?></label>
								</div>
							</div>
						</div>

						<div class="table-responsive mb-3">
							<table class="table ulbm-table table-bordered align-middle mb-0 w-100">
								<thead class="table-light">
									<tr>
										<th scope="col" style="width:40px;"></th>
										<th scope="col"><?php esc_html_e( 'Booking Type', 'flex-booking-system' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Post Type', 'flex-booking-system' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Existing demo posts', 'flex-booking-system' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $ulbm_all_types_for_sc as $ulbm_demo_type ) :
										$demo_tid = (int) $ulbm_demo_type['id'];
										$demo_cpt = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $ulbm_demo_type['slug'] );
										$demo_cnt = \FlexBooking\Setup\DemoContentSeeder::count_demo_posts( $demo_cpt );
									?>
										<tr>
											<td>
												<input class="form-check-input ulbm-demo-type-cb" type="checkbox" value="<?php echo esc_attr( (string) $demo_tid ); ?>" checked>
											</td>
											<td><strong><?php echo esc_html( (string) $ulbm_demo_type['name'] ); ?></strong></td>
											<td><code><?php echo esc_html( $demo_cpt ); ?></code></td>
											<td><span class="badge text-bg-secondary ulbm-demo-count-<?php echo esc_attr( (string) $demo_tid ); ?>"><?php echo esc_html( (string) $demo_cnt ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<div class="d-flex flex-wrap gap-2 align-items-center">
							<button type="button" class="btn btn-primary" id="ulbm-demo-import">
								<i class="bi bi-download me-1"></i><?php esc_html_e( 'Import demo content', 'flex-booking-system' ); ?>
							</button>
							<button type="button" class="btn btn-outline-danger" id="ulbm-demo-delete-all">
								<i class="bi bi-trash me-1"></i><?php esc_html_e( 'Remove all demo content', 'flex-booking-system' ); ?>
							</button>
							<span class="spinner-border spinner-border-sm text-primary d-none" id="ulbm-demo-spinner" role="status"></span>
						</div>

						<div class="progress mt-3 d-none" id="ulbm-demo-progress-wrap" style="height:22px;">
							<div class="progress-bar progress-bar-striped progress-bar-animated" id="ulbm-demo-progress" role="progressbar" style="width:0%">0%</div>
						</div>
						<div class="alert alert-info small mt-3 mb-0 d-none" id="ulbm-demo-status" role="status"></div>
					<?php else : ?>
						<div class="alert alert-warning mb-0">
							<?php esc_html_e( 'Create at least one booking type first (Booking Types or Setup Wizard), then return here to import demo listings.', 'flex-booking-system' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ulbm-booking-types' ) ); ?>" class="alert-link"><?php esc_html_e( 'Open Booking Types', 'flex-booking-system' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- PARTNER PORTAL -->
			<div class="tab-pane fade" id="ulbm-st-partner">
				<div class="ulbm-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-2"><i class="bi bi-people me-2"></i><?php esc_html_e( 'Partner / Vendor Portal', 'flex-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Partner pages are created automatically with the correct shortcodes. You can reassign pages below or click Create Pages to repair missing pages.', 'flex-booking-system' ); ?></p>

					<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
						<button type="button" class="btn btn-primary" id="ulbm-provision-vendor-pages">
							<i class="bi bi-magic me-1"></i><?php esc_html_e( 'Create / repair partner pages', 'flex-booking-system' ); ?>
						</button>
						<span class="spinner-border spinner-border-sm text-primary d-none" id="ulbm-provision-spinner" role="status"></span>
					</div>
					<div class="alert alert-info small d-none mb-4" id="ulbm-provision-status" role="status"></div>

					<h6 class="fw-semibold mb-2"><?php esc_html_e( 'Partner pages & shortcodes', 'flex-booking-system' ); ?></h6>
					<div class="table-responsive mb-4">
						<table class="table ulbm-table table-bordered align-middle mb-0 w-100" id="ulbm-vendor-pages-table">
							<thead class="table-light">
								<tr>
									<th><?php esc_html_e( 'Page', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Shortcode', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Front-end URL', 'flex-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'flex-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$ulbm_vendor_labels = array(
									'vendor_register_page'  => __( 'Registration', 'flex-booking-system' ),
									'vendor_login_page'     => __( 'Login', 'flex-booking-system' ),
									'vendor_dashboard_page' => __( 'Dashboard', 'flex-booking-system' ),
								);
								foreach ( $ulbm_vendor_page_rows as $ulbm_vkey => $ulbm_vrow ) :
									?>
									<tr data-page-key="<?php echo esc_attr( $ulbm_vkey ); ?>">
										<td><strong><?php echo esc_html( $ulbm_vendor_labels[ $ulbm_vkey ] ?? $ulbm_vrow['title'] ); ?></strong></td>
										<td><code class="user-select-all"><?php echo esc_html( $ulbm_vrow['shortcode'] ); ?></code></td>
										<td class="small ulbm-vendor-page-url">
											<?php if ( $ulbm_vrow['url'] ) : ?>
												<a href="<?php echo esc_url( $ulbm_vrow['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ulbm_vrow['url'] ); ?></a>
											<?php else : ?>
												<span class="text-muted"><?php esc_html_e( 'Not created yet', 'flex-booking-system' ); ?></span>
											<?php endif; ?>
										</td>
										<td class="ulbm-vendor-page-actions">
											<?php if ( $ulbm_vrow['edit_url'] ) : ?>
												<a href="<?php echo esc_url( $ulbm_vrow['edit_url'] ); ?>" class="btn btn-sm btn-outline-secondary"><?php esc_html_e( 'Edit', 'flex-booking-system' ); ?></a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="row g-3 mb-4">
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Registration page', 'flex-booking-system' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'ulbm_vendor_register_page',
									'selected'          => (int) ( $s['vendor_register_page'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select page —', 'flex-booking-system' ),
									'option_none_value' => '0',
									'class'             => 'form-select',
								)
							);
							?>
						</div>
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Login page', 'flex-booking-system' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'ulbm_vendor_login_page',
									'selected'          => (int) ( $s['vendor_login_page'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select page —', 'flex-booking-system' ),
									'option_none_value' => '0',
									'class'             => 'form-select',
								)
							);
							?>
						</div>
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Dashboard page', 'flex-booking-system' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'ulbm_vendor_dashboard_page',
									'selected'          => (int) ( $s['vendor_dashboard_page'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select page —', 'flex-booking-system' ),
									'option_none_value' => '0',
									'class'             => 'form-select',
								)
							);
							?>
						</div>
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_vendor_auto_approve" id="ulbm_vendor_auto_approve" <?php checked( ! empty( $s['vendor_auto_approve'] ) ); ?>>
								<label class="form-check-label" for="ulbm_vendor_auto_approve"><?php esc_html_e( 'Auto-approve new partner registrations', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_vendor_auto_publish" id="ulbm_vendor_auto_publish" <?php checked( ! empty( $s['vendor_auto_publish'] ) ); ?>>
								<label class="form-check-label" for="ulbm_vendor_auto_publish"><?php esc_html_e( 'Publish partner listings immediately (otherwise pending review)', 'flex-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-12">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="ulbm_enable_google_maps_embed" id="ulbm_enable_google_maps_embed" <?php checked( ! empty( $s['enable_google_maps_embed'] ) ); ?>>
								<label class="form-check-label" for="ulbm_enable_google_maps_embed"><?php esc_html_e( 'Allow visitors to opt in to embedded Google Maps on listing pages (off by default; otherwise only a link is shown)', 'flex-booking-system' ); ?></label>
							</div>
						</div>
					</div>

					<div class="alert alert-light border small mb-0">
						<strong><?php esc_html_e( 'Optional CTA shortcode:', 'flex-booking-system' ); ?></strong>
						<code class="user-select-all ms-1">[ulbm_become_partner]</code>
						<span class="text-muted ms-2"><?php esc_html_e( 'Add this on your homepage to promote partner signup.', 'flex-booking-system' ); ?></span>
					</div>
				</div>
			</div>

		</div><!-- .tab-content -->

		<div class="mt-3">
			<button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle me-1"></i><?php esc_html_e( 'Save All Settings', 'flex-booking-system' ); ?></button>
		</div>
	</form>
</div>
