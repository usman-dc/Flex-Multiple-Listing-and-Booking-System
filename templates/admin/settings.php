<?php
/**
 * Global settings — currency, colors, layout, notifications, shortcodes reference.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Front\ColorSettings;

defined( 'ABSPATH' ) || exit;

$raw    = get_option( 'fbs_general_settings', '{}' );
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
	'vendor_auto_approve'    => true,
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

$fbs_settings_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Auto-create partner pages when not configured yet.
if ( \FlexBooking\Core\Capabilities::can_access_admin() ) {
	\FlexBooking\Vendor\VendorPageProvisioner::maybe_auto_provision();
	$parsed = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
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

$fbs_vendor_page_rows = \FlexBooking\Vendor\VendorPageProvisioner::status_rows();

// Shortcodes reference.
$fbs_shortcodes_help = array(
	array( 'tag' => 'fbs_booking_form', 'description' => __( 'Booking form for a specific type.', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_booking_form id="1"]', 'attrs' => '<code>id</code> (required)' ),
	array( 'tag' => 'fbs_listing_grid', 'description' => __( 'Listing grid with AJAX filters.', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_listing_grid type="car-rental" columns="3" limit="12"]', 'attrs' => '<code>type</code>, <code>columns</code>, <code>limit</code>' ),
	array( 'tag' => 'fbs_search', 'description' => __( 'Search UI (AJAX).', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_search]', 'attrs' => '<code>layout</code>' ),
	array( 'tag' => 'fbs_register', 'description' => __( 'Partner registration form.', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_register]', 'attrs' => '—' ),
	array( 'tag' => 'fbs_login', 'description' => __( 'Partner login form.', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_login]', 'attrs' => '—' ),
	array( 'tag' => 'fbs_dashboard', 'description' => __( 'Partner dashboard (listings, bookings, profile).', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_dashboard]', 'attrs' => '—' ),
	array( 'tag' => 'fbs_become_partner', 'description' => __( 'Call-to-action block for partner signup.', 'flex-multiple-listing-and-booking-system' ), 'example' => '[fbs_become_partner]', 'attrs' => '<code>title</code>, <code>text</code>' ),
);
$fbs_type_repo_for_sc = new \FlexBooking\Booking\BookingTypeRepository();
$fbs_all_types_for_sc = $fbs_type_repo_for_sc->get_all();
foreach ( $fbs_all_types_for_sc as $fbs_sc_type ) {
	$tid = (int) $fbs_sc_type['id'];
	$fbs_shortcodes_help[] = array(
		'tag'         => 'fbs_booking_form id="' . $tid . '"',
		'description' => sprintf(
			/* translators: %s: booking type name */
			__( 'Form: %s', 'flex-multiple-listing-and-booking-system' ),
			esc_html( (string) $fbs_sc_type['name'] )
		),
		'example'     => '[fbs_booking_form id="' . $tid . '"]',
		'attrs'       => '<code>id="' . $tid . '"</code>',
	);
}
$fbs_shortcodes_help = apply_filters( 'fbs_settings_shortcodes_help', $fbs_shortcodes_help );
?>
<div class="wrap fbs-admin-wrap container-fluid py-3">
	<?php if ( ! empty( $_GET['fbs-settings-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="alert alert-success" role="status"><?php esc_html_e( 'Settings saved.', 'flex-multiple-listing-and-booking-system' ); ?></div>
	<?php endif; ?>
	<div class="fbs-page-header mb-4">
		<h1 class="h3 mb-1 fbs-page-title"><?php echo esc_html( fbs_plugin_menu_label() . ' — ' . __( 'Settings', 'flex-multiple-listing-and-booking-system' ) ); ?></h1>
		<p class="text-muted small mb-0"><?php esc_html_e( 'Currency, layout, notifications, shortcodes, and partner portal.', 'flex-multiple-listing-and-booking-system' ); ?></p>
	</div>

	<form method="post" id="fbs-settings-form" action="<?php echo esc_url( admin_url( 'admin.php?page=fbs-settings' ) ); ?>">
		<?php wp_nonce_field( 'fbs_save_settings', 'fbs_settings_nonce' ); ?>
		<input type="hidden" name="fbs_save_settings" value="1" />
		<input type="hidden" name="page" value="fbs-settings" />
		<input type="hidden" name="fbs_settings_tab" id="fbs_settings_tab" value="<?php echo esc_attr( $fbs_settings_tab ); ?>" />

		<!-- TABS -->
		<ul class="nav nav-tabs mb-4" role="tablist" id="fbs-settings-tabs">
			<li class="nav-item"><button class="nav-link<?php echo 'general' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-general" data-fbs-tab="general" type="button"><?php esc_html_e( 'General', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'colors' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-colors" data-fbs-tab="colors" type="button"><?php esc_html_e( 'Colors', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'layout' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-layout" data-fbs-tab="layout" type="button"><?php esc_html_e( 'Layout', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'notify' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-notify" data-fbs-tab="notify" type="button"><?php esc_html_e( 'Notifications', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'shortcodes' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-shortcodes" data-fbs-tab="shortcodes" type="button"><?php esc_html_e( 'Shortcodes', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'cpts' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-cpts" data-fbs-tab="cpts" type="button"><?php esc_html_e( 'Post Types', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'demo' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-demo" data-fbs-tab="demo" type="button"><?php esc_html_e( 'Demo Content', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
			<li class="nav-item"><button class="nav-link<?php echo 'partner' === $fbs_settings_tab ? ' active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#fbs-st-partner" data-fbs-tab="partner" type="button"><?php esc_html_e( 'Partner Portal', 'flex-multiple-listing-and-booking-system' ); ?></button></li>
		</ul>

		<div class="tab-content">

			<!-- GENERAL -->
			<div class="tab-pane fade<?php echo 'general' === $fbs_settings_tab ? ' show active' : ''; ?>" id="fbs-st-general">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i><?php esc_html_e( 'Currency & Formats', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<div class="row g-3">
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Currency code', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input class="form-control" name="fbs_currency" value="<?php echo esc_attr( $s['currency'] ); ?>" maxlength="3">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Position', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<select class="form-select" name="fbs_currency_position">
								<?php foreach ( array( 'left' => 'Left ($99)', 'right' => 'Right (99$)', 'left_space' => 'Left space ($ 99)', 'right_space' => 'Right space (99 $)' ) as $pv => $pl ) : ?>
									<option value="<?php echo esc_attr( $pv ); ?>" <?php selected( $s['currency_position'], $pv ); ?>><?php echo esc_html( $pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Date format', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input class="form-control" name="fbs_date_format" value="<?php echo esc_attr( $s['date_format'] ); ?>">
							<span class="form-text"><?php esc_html_e( 'PHP date format', 'flex-multiple-listing-and-booking-system' ); ?></span>
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Time format', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input class="form-control" name="fbs_time_format" value="<?php echo esc_attr( $s['time_format'] ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- COLORS -->
			<div class="tab-pane fade<?php echo 'colors' === $fbs_settings_tab ? ' show active' : ''; ?>" id="fbs-st-colors">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
						<h5 class="fw-bold mb-0"><i class="bi bi-palette me-2"></i><?php esc_html_e( 'Color Scheme', 'flex-multiple-listing-and-booking-system' ); ?></h5>
						<button type="button" class="btn btn-outline-secondary btn-sm" id="fbs-reset-colors"><?php esc_html_e( 'Reset all colors to defaults', 'flex-multiple-listing-and-booking-system' ); ?></button>
					</div>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Colors apply only inside plugin listing grids and forms — not your whole WordPress page. Use the hex field (e.g. #f5f6f8) for each color, then Save All Settings.', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<?php if ( ! empty( $_GET['fbs-settings-saved'] ) && 'colors' === $fbs_settings_tab ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
						<div class="alert alert-info small py-2 mb-3">
							<?php
							printf(
								/* translators: %s: hex color */
								esc_html__( 'Saved. Page background is now: %s', 'flex-multiple-listing-and-booking-system' ),
								'<code>' . esc_html( (string) ( $s['color_page_bg'] ?? '' ) ) . '</code>'
							);
							?>
						</div>
					<?php endif; ?>
					<input type="hidden" name="fbs_colors_json" id="fbs_colors_json" value="">
					<button type="button" class="btn btn-sm btn-outline-warning mb-3" id="fbs-fix-page-bg"><?php esc_html_e( 'Fix red page background (reset to light gray)', 'flex-multiple-listing-and-booking-system' ); ?></button>
					<?php
					$color_fields = ColorSettings::fields();
					$color_groups = ColorSettings::groups();
					foreach ( $color_groups as $group_id => $group_label ) :
						?>
						<div class="fbs-color-group mb-4">
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
											<input type="color" class="form-control form-control-color fbs-color-picker flex-shrink-0" data-fbs-target="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>" data-fbs-color-key="<?php echo esc_attr( $preview_key ); ?>" data-fbs-settings-key="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $val ); ?>" aria-hidden="true" tabindex="-1">
											<input type="text" id="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>" class="form-control form-control-sm fbs-color-input fbs-color-hex-input" name="<?php echo esc_attr( ColorSettings::post_key( $field_key ) ); ?>" data-fbs-color-key="<?php echo esc_attr( $preview_key ); ?>" data-fbs-settings-key="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $val ); ?>" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" spellcheck="false" autocomplete="off">
										</div>
										<?php if ( ! empty( $field['hint'] ) ) : ?>
											<span class="form-text d-block"><?php echo esc_html( $field['hint'] ); ?></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
					<div id="fbs-color-preview" class="fbs-color-preview mt-2 p-4 border rounded" style="<?php echo esc_attr( ColorSettings::admin_preview_inline_style() ); ?>">
						<p class="small mb-3"><strong><?php esc_html_e( 'Live preview', 'flex-multiple-listing-and-booking-system' ); ?></strong> <span class="text-muted"><?php esc_html_e( '(updates as you pick colors)', 'flex-multiple-listing-and-booking-system' ); ?></span></p>
						<div class="fbs-color-preview-page rounded p-3">
							<div class="fbs-color-preview-card rounded p-3 shadow-sm">
								<span class="fbs-color-preview-badge"><?php esc_html_e( 'Featured', 'flex-multiple-listing-and-booking-system' ); ?></span>
								<div class="fbs-color-preview-title fw-bold"><?php esc_html_e( 'Sample listing title', 'flex-multiple-listing-and-booking-system' ); ?></div>
								<div class="fbs-color-preview-muted small"><?php esc_html_e( 'Downtown · 4 guests', 'flex-multiple-listing-and-booking-system' ); ?></div>
								<div class="fbs-color-preview-price fw-bold my-2">$299 <span class="fbs-color-preview-sale small"><?php esc_html_e( 'was $349', 'flex-multiple-listing-and-booking-system' ); ?></span></div>
								<div class="fbs-color-preview-stars small mb-2" aria-hidden="true">★★★★★</div>
								<button type="button" class="fbs-color-preview-btn me-1" data-fbs-preview="primary"><?php esc_html_e( 'Book now', 'flex-multiple-listing-and-booking-system' ); ?></button>
								<button type="button" class="fbs-color-preview-btn me-1" data-fbs-preview="secondary"><?php esc_html_e( 'Secondary', 'flex-multiple-listing-and-booking-system' ); ?></button>
								<button type="button" class="fbs-color-preview-btn me-1" data-fbs-preview="success"><?php esc_html_e( 'Success', 'flex-multiple-listing-and-booking-system' ); ?></button>
								<button type="button" class="fbs-color-preview-btn" data-fbs-preview="accent"><?php esc_html_e( 'Accent', 'flex-multiple-listing-and-booking-system' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- LAYOUT -->
			<div class="tab-pane fade" id="fbs-st-layout">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-arrows-angle-expand me-2"></i><?php esc_html_e( 'Container Width', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Maximum content width for all plugin pages, shortcodes, blocks, and Elementor widgets.', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<div class="row g-3 align-items-end">
						<div class="col-md-4">
							<label class="form-label" for="fbs_container_width"><?php esc_html_e( 'Max container width (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" id="fbs_container_width" name="fbs_container_width" value="<?php echo esc_attr( (string) (int) ( $s['container_width'] ?? 1400 ) ); ?>" min="768" max="2400" step="10">
							<span class="form-text"><?php esc_html_e( 'Default: 1400px. Applies to listing pages, partner portal, grids, and forms.', 'flex-multiple-listing-and-booking-system' ); ?></span>
						</div>
						<div class="col-md-8">
							<div class="border rounded p-3 bg-light small">
								<strong><?php esc_html_e( 'Preview', 'flex-multiple-listing-and-booking-system' ); ?></strong>
								<div class="mt-2 mx-auto border border-primary border-2 bg-white text-center py-2" style="max-width:<?php echo esc_attr( (string) (int) ( $s['container_width'] ?? 1400 ) ); ?>px;width:100%;">
									<?php
									printf(
										/* translators: %d: container max width in pixels */
										esc_html__( 'Content area — %d px max', 'flex-multiple-listing-and-booking-system' ),
										(int) ( $s['container_width'] ?? 1400 )
									);
									?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-grid me-2"></i><?php esc_html_e( 'Grid & Card Settings', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<div class="row g-3">
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Grid columns', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<select class="form-select" name="fbs_grid_columns">
								<?php for ( $c = 2; $c <= 4; $c++ ) : ?>
									<option value="<?php echo esc_attr( (string) $c ); ?>" <?php selected( (int) $s['grid_columns'], $c ); ?>><?php echo esc_html( (string) $c ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Posts per page', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_per_page" value="<?php echo esc_attr( (string) (int) $s['grid_per_page'] ); ?>" min="1" max="100">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Card border radius (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_card_border_radius" value="<?php echo esc_attr( (string) (int) $s['card_border_radius'] ); ?>" min="0" max="50">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Slider height (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_slider_height" value="<?php echo esc_attr( (string) (int) $s['slider_height'] ); ?>" min="200" max="800">
						</div>
						<div class="col-md-3">
							<label class="form-label"><?php esc_html_e( 'Sidebar position', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<select class="form-select" name="fbs_sidebar_position">
								<option value="right" <?php selected( $s['sidebar_position'], 'right' ); ?>><?php esc_html_e( 'Right (default)', 'flex-multiple-listing-and-booking-system' ); ?></option>
								<option value="left" <?php selected( $s['sidebar_position'], 'left' ); ?>><?php esc_html_e( 'Left', 'flex-multiple-listing-and-booking-system' ); ?></option>
							</select>
						</div>
						<div class="col-md-4">
							<div class="form-check mt-4">
								<input class="form-check-input" type="checkbox" name="fbs_card_shadow" id="fbs_card_shadow" <?php checked( ! empty( $s['card_shadow'] ) ); ?>>
								<label class="form-check-label" for="fbs_card_shadow"><?php esc_html_e( 'Card shadow on hover', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-check mt-4">
								<input class="form-check-input" type="checkbox" name="fbs_show_filters" id="fbs_show_filters" <?php checked( ! empty( $s['show_filters'] ) ); ?>>
								<label class="form-check-label" for="fbs_show_filters"><?php esc_html_e( 'Show filter bar on grid', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
					</div>
				</div>

				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-arrows-move me-2"></i><?php esc_html_e( 'Grid Spacing', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Control padding, margin, and gap for listing grids (shortcode, block, Elementor, and archives).', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<div class="row g-3">
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Column gap (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_gap" value="<?php echo esc_attr( (string) (int) ( $s['grid_gap'] ?? 24 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Padding X (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_padding_x" value="<?php echo esc_attr( (string) (int) ( $s['grid_padding_x'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Padding Y (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_padding_y" value="<?php echo esc_attr( (string) (int) ( $s['grid_padding_y'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Margin top (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_margin_top" value="<?php echo esc_attr( (string) (int) ( $s['grid_margin_top'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Margin bottom (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_margin_bottom" value="<?php echo esc_attr( (string) (int) ( $s['grid_margin_bottom'] ?? 0 ) ); ?>" min="0" max="120">
						</div>
						<div class="col-md-2">
							<label class="form-label"><?php esc_html_e( 'Card padding (px)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" name="fbs_grid_card_padding" value="<?php echo esc_attr( (string) (int) ( $s['grid_card_padding'] ?? 16 ) ); ?>" min="0" max="120">
						</div>
					</div>
				</div>

				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-card-list me-2"></i><?php esc_html_e( 'Grid Card Content', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Choose what appears on each listing card in grids, archives, and AJAX search results.', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<div class="row g-3">
						<div class="col-md-4">
							<input type="hidden" name="fbs_grid_show_rating" value="0" />
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_grid_show_rating" id="fbs_grid_show_rating" value="1" <?php checked( ! isset( $s['grid_show_rating'] ) || ! empty( $s['grid_show_rating'] ) ); ?>>
								<label class="form-check-label" for="fbs_grid_show_rating"><?php esc_html_e( 'Show star rating & review count', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-4">
							<input type="hidden" name="fbs_grid_show_amenities" value="0" />
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_grid_show_amenities" id="fbs_grid_show_amenities" value="1" <?php checked( ! isset( $s['grid_show_amenities'] ) || ! empty( $s['grid_show_amenities'] ) ); ?>>
								<label class="form-check-label" for="fbs_grid_show_amenities"><?php esc_html_e( 'Show amenities on cards', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="fbs_grid_amenities_limit"><?php esc_html_e( 'Max amenities per card', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input type="number" class="form-control" id="fbs_grid_amenities_limit" name="fbs_grid_amenities_limit" value="<?php echo esc_attr( (string) (int) ( $s['grid_amenities_limit'] ?? 4 ) ); ?>" min="1" max="8">
						</div>
					</div>
				</div>

				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-chat-square-text me-2"></i><?php esc_html_e( 'Listing Reviews', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<div class="row g-3">
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_reviews_enabled" id="fbs_reviews_enabled" <?php checked( ! isset( $s['reviews_enabled'] ) || ! empty( $s['reviews_enabled'] ) ); ?>>
								<label class="form-check-label" for="fbs_reviews_enabled"><?php esc_html_e( 'Allow guests to submit reviews on listing pages', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_reviews_auto_approve" id="fbs_reviews_auto_approve" <?php checked( ! empty( $s['reviews_auto_approve'] ) ); ?>>
								<label class="form-check-label" for="fbs_reviews_auto_approve"><?php esc_html_e( 'Publish reviews immediately (skip admin approval)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- NOTIFICATIONS -->
			<div class="tab-pane fade" id="fbs-st-notify">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-envelope me-2"></i><?php esc_html_e( 'Customer Email Notifications', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small"><?php esc_html_e( 'When staff change booking status, the customer receives an email if a valid address exists.', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<div class="row g-3">
						<div class="col-12">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_notify_customer_status" id="fbs_notify_customer_status" <?php checked( ! empty( $s['notify_customer_status'] ) ); ?>>
								<label class="form-check-label fw-semibold" for="fbs_notify_customer_status"><?php esc_html_e( 'Enable customer emails on status changes', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check"><input class="form-check-input" type="checkbox" name="fbs_notify_on_confirmed" id="fbs_nc1" <?php checked( ! empty( $s['notify_on_confirmed'] ) ); ?>><label class="form-check-label" for="fbs_nc1"><?php esc_html_e( 'Confirmed / Accepted', 'flex-multiple-listing-and-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="fbs_notify_on_completed" id="fbs_nc2" <?php checked( ! empty( $s['notify_on_completed'] ) ); ?>><label class="form-check-label" for="fbs_nc2"><?php esc_html_e( 'Completed', 'flex-multiple-listing-and-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="fbs_notify_on_cancelled" id="fbs_nc3" <?php checked( ! empty( $s['notify_on_cancelled'] ) ); ?>><label class="form-check-label" for="fbs_nc3"><?php esc_html_e( 'Cancelled', 'flex-multiple-listing-and-booking-system' ); ?></label></div>
						</div>
						<div class="col-md-6">
							<div class="form-check"><input class="form-check-input" type="checkbox" name="fbs_notify_on_rejected" id="fbs_nc4" <?php checked( ! empty( $s['notify_on_rejected'] ) ); ?>><label class="form-check-label" for="fbs_nc4"><?php esc_html_e( 'Rejected', 'flex-multiple-listing-and-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="fbs_notify_on_hold" id="fbs_nc5" <?php checked( ! empty( $s['notify_on_on_hold'] ) ); ?>><label class="form-check-label" for="fbs_nc5"><?php esc_html_e( 'On hold', 'flex-multiple-listing-and-booking-system' ); ?></label></div>
							<div class="form-check"><input class="form-check-input" type="checkbox" name="fbs_notify_on_pending" id="fbs_nc6" <?php checked( ! empty( $s['notify_on_pending'] ) ); ?>><label class="form-check-label" for="fbs_nc6"><?php esc_html_e( 'Pending', 'flex-multiple-listing-and-booking-system' ); ?></label></div>
						</div>
						<div class="col-md-6">
							<label class="form-label"><?php esc_html_e( 'Reply-To address', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<input class="form-control" type="email" name="fbs_notify_reply_to" value="<?php echo esc_attr( $s['notify_reply_to'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- SHORTCODES -->
			<div class="tab-pane fade" id="fbs-st-shortcodes">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-code-slash me-2"></i><?php esc_html_e( 'Available Shortcodes', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<div class="table-responsive">
						<table class="table fbs-table table-bordered align-middle mb-0 w-100">
							<thead class="table-light">
								<tr>
									<th><?php esc_html_e( 'Shortcode', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Attributes', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Description', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Copy', 'flex-multiple-listing-and-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $fbs_shortcodes_help as $row ) : ?>
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
			<div class="tab-pane fade" id="fbs-st-cpts">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-3"><i class="bi bi-collection me-2"></i><?php esc_html_e( 'Registered Post Types', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Each published booking type auto-creates a CPT. Add posts under the plugin admin menu.', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<?php if ( ! empty( $fbs_all_types_for_sc ) ) : ?>
						<div class="table-responsive">
							<table class="table fbs-table table-bordered align-middle mb-0 w-100">
								<thead class="table-light">
									<tr>
										<th><?php esc_html_e( 'Type', 'flex-multiple-listing-and-booking-system' ); ?></th>
										<th><?php esc_html_e( 'CPT Slug', 'flex-multiple-listing-and-booking-system' ); ?></th>
										<th><?php esc_html_e( 'Archive', 'flex-multiple-listing-and-booking-system' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'flex-multiple-listing-and-booking-system' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $fbs_all_types_for_sc as $fbs_rpt ) :
										$rpt_slug    = (string) $fbs_rpt['slug'];
										$rpt_cpt     = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( $rpt_slug );
										$rpt_archive = home_url( '/' . $rpt_slug . '/' );
									?>
										<tr>
											<td><strong><?php echo esc_html( (string) $fbs_rpt['name'] ); ?></strong> <span class="text-muted small">#<?php echo esc_html( (string) (int) $fbs_rpt['id'] ); ?></span></td>
											<td><code><?php echo esc_html( $rpt_cpt ); ?></code></td>
											<td class="small"><a href="<?php echo esc_url( $rpt_archive ); ?>" target="_blank"><?php echo esc_html( $rpt_archive ); ?></a></td>
											<td>
												<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $rpt_cpt ) ); ?>" class="btn btn-sm btn-outline-primary me-1"><?php esc_html_e( 'View', 'flex-multiple-listing-and-booking-system' ); ?></a>
												<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $rpt_cpt ) ); ?>" class="btn btn-sm btn-outline-success"><?php esc_html_e( 'Add New', 'flex-multiple-listing-and-booking-system' ); ?></a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<p class="text-muted"><?php esc_html_e( 'No booking types created yet.', 'flex-multiple-listing-and-booking-system' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- DEMO CONTENT -->
			<div class="tab-pane fade" id="fbs-st-demo">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-2"><i class="bi bi-magic me-2"></i><?php esc_html_e( 'One-Click Demo Content', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small mb-4">
						<?php esc_html_e( 'Generate sample listings with images, pricing, gallery, features, FAQ, and extra services. Perfect for testing your grid, filters, and single pages before adding real content.', 'flex-multiple-listing-and-booking-system' ); ?>
					</p>

					<?php if ( ! empty( $fbs_all_types_for_sc ) ) : ?>
						<div class="row g-3 align-items-end mb-3">
							<div class="col-md-3">
								<label class="form-label" for="fbs-demo-count"><?php esc_html_e( 'Posts per type', 'flex-multiple-listing-and-booking-system' ); ?></label>
								<input type="number" class="form-control" id="fbs-demo-count" value="20" min="1" max="50">
							</div>
							<div class="col-md-9">
								<div class="form-check mt-4">
									<input class="form-check-input" type="checkbox" id="fbs-demo-select-all" checked>
									<label class="form-check-label fw-semibold" for="fbs-demo-select-all"><?php esc_html_e( 'Select all booking types', 'flex-multiple-listing-and-booking-system' ); ?></label>
								</div>
							</div>
						</div>

						<div class="table-responsive mb-3">
							<table class="table fbs-table table-bordered align-middle mb-0 w-100">
								<thead class="table-light">
									<tr>
										<th scope="col" style="width:40px;"></th>
										<th scope="col"><?php esc_html_e( 'Booking Type', 'flex-multiple-listing-and-booking-system' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Post Type', 'flex-multiple-listing-and-booking-system' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Existing demo posts', 'flex-multiple-listing-and-booking-system' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $fbs_all_types_for_sc as $fbs_demo_type ) :
										$demo_tid = (int) $fbs_demo_type['id'];
										$demo_cpt = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $fbs_demo_type['slug'] );
										$demo_cnt = \FlexBooking\Setup\DemoContentSeeder::count_demo_posts( $demo_cpt );
									?>
										<tr>
											<td>
												<input class="form-check-input fbs-demo-type-cb" type="checkbox" value="<?php echo esc_attr( (string) $demo_tid ); ?>" checked>
											</td>
											<td><strong><?php echo esc_html( (string) $fbs_demo_type['name'] ); ?></strong></td>
											<td><code><?php echo esc_html( $demo_cpt ); ?></code></td>
											<td><span class="badge text-bg-secondary fbs-demo-count-<?php echo esc_attr( (string) $demo_tid ); ?>"><?php echo esc_html( (string) $demo_cnt ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<div class="d-flex flex-wrap gap-2 align-items-center">
							<button type="button" class="btn btn-primary" id="fbs-demo-import">
								<i class="bi bi-download me-1"></i><?php esc_html_e( 'Import demo content', 'flex-multiple-listing-and-booking-system' ); ?>
							</button>
							<button type="button" class="btn btn-outline-danger" id="fbs-demo-delete-all">
								<i class="bi bi-trash me-1"></i><?php esc_html_e( 'Remove all demo content', 'flex-multiple-listing-and-booking-system' ); ?>
							</button>
							<span class="spinner-border spinner-border-sm text-primary d-none" id="fbs-demo-spinner" role="status"></span>
						</div>

						<div class="progress mt-3 d-none" id="fbs-demo-progress-wrap" style="height:22px;">
							<div class="progress-bar progress-bar-striped progress-bar-animated" id="fbs-demo-progress" role="progressbar" style="width:0%">0%</div>
						</div>
						<div class="alert alert-info small mt-3 mb-0 d-none" id="fbs-demo-status" role="status"></div>
					<?php else : ?>
						<div class="alert alert-warning mb-0">
							<?php esc_html_e( 'Create at least one booking type first (Booking Types or Setup Wizard), then return here to import demo listings.', 'flex-multiple-listing-and-booking-system' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=fbs-booking-types' ) ); ?>" class="alert-link"><?php esc_html_e( 'Open Booking Types', 'flex-multiple-listing-and-booking-system' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- PARTNER PORTAL -->
			<div class="tab-pane fade" id="fbs-st-partner">
				<div class="fbs-admin-panel border rounded bg-white p-4 mb-4">
					<h5 class="fw-bold mb-2"><i class="bi bi-people me-2"></i><?php esc_html_e( 'Partner / Vendor Portal', 'flex-multiple-listing-and-booking-system' ); ?></h5>
					<p class="text-muted small mb-3"><?php esc_html_e( 'Partner pages are created automatically with the correct shortcodes. You can reassign pages below or click Create Pages to repair missing pages.', 'flex-multiple-listing-and-booking-system' ); ?></p>

					<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
						<button type="button" class="btn btn-primary" id="fbs-provision-vendor-pages">
							<i class="bi bi-magic me-1"></i><?php esc_html_e( 'Create / repair partner pages', 'flex-multiple-listing-and-booking-system' ); ?>
						</button>
						<span class="spinner-border spinner-border-sm text-primary d-none" id="fbs-provision-spinner" role="status"></span>
					</div>
					<div class="alert alert-info small d-none mb-4" id="fbs-provision-status" role="status"></div>

					<h6 class="fw-semibold mb-2"><?php esc_html_e( 'Partner pages & shortcodes', 'flex-multiple-listing-and-booking-system' ); ?></h6>
					<div class="table-responsive mb-4">
						<table class="table fbs-table table-bordered align-middle mb-0 w-100" id="fbs-vendor-pages-table">
							<thead class="table-light">
								<tr>
									<th><?php esc_html_e( 'Page', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Shortcode', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Front-end URL', 'flex-multiple-listing-and-booking-system' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'flex-multiple-listing-and-booking-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$fbs_vendor_labels = array(
									'vendor_register_page'  => __( 'Registration', 'flex-multiple-listing-and-booking-system' ),
									'vendor_login_page'     => __( 'Login', 'flex-multiple-listing-and-booking-system' ),
									'vendor_dashboard_page' => __( 'Dashboard', 'flex-multiple-listing-and-booking-system' ),
								);
								foreach ( $fbs_vendor_page_rows as $fbs_vkey => $fbs_vrow ) :
									?>
									<tr data-page-key="<?php echo esc_attr( $fbs_vkey ); ?>">
										<td><strong><?php echo esc_html( $fbs_vendor_labels[ $fbs_vkey ] ?? $fbs_vrow['title'] ); ?></strong></td>
										<td><code class="user-select-all"><?php echo esc_html( $fbs_vrow['shortcode'] ); ?></code></td>
										<td class="small fbs-vendor-page-url">
											<?php if ( $fbs_vrow['url'] ) : ?>
												<a href="<?php echo esc_url( $fbs_vrow['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $fbs_vrow['url'] ); ?></a>
											<?php else : ?>
												<span class="text-muted"><?php esc_html_e( 'Not created yet', 'flex-multiple-listing-and-booking-system' ); ?></span>
											<?php endif; ?>
										</td>
										<td class="fbs-vendor-page-actions">
											<?php if ( $fbs_vrow['edit_url'] ) : ?>
												<a href="<?php echo esc_url( $fbs_vrow['edit_url'] ); ?>" class="btn btn-sm btn-outline-secondary"><?php esc_html_e( 'Edit', 'flex-multiple-listing-and-booking-system' ); ?></a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="row g-3 mb-4">
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Registration page', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'fbs_vendor_register_page',
									'selected'          => (int) ( $s['vendor_register_page'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select page —', 'flex-multiple-listing-and-booking-system' ),
									'option_none_value' => '0',
									'class'             => 'form-select',
								)
							);
							?>
						</div>
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Login page', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'fbs_vendor_login_page',
									'selected'          => (int) ( $s['vendor_login_page'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select page —', 'flex-multiple-listing-and-booking-system' ),
									'option_none_value' => '0',
									'class'             => 'form-select',
								)
							);
							?>
						</div>
						<div class="col-md-4">
							<label class="form-label"><?php esc_html_e( 'Dashboard page', 'flex-multiple-listing-and-booking-system' ); ?></label>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'fbs_vendor_dashboard_page',
									'selected'          => (int) ( $s['vendor_dashboard_page'] ?? 0 ),
									'show_option_none'  => esc_html__( '— Select page —', 'flex-multiple-listing-and-booking-system' ),
									'option_none_value' => '0',
									'class'             => 'form-select',
								)
							);
							?>
						</div>
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_vendor_auto_approve" id="fbs_vendor_auto_approve" <?php checked( ! empty( $s['vendor_auto_approve'] ) ); ?>>
								<label class="form-check-label" for="fbs_vendor_auto_approve"><?php esc_html_e( 'Auto-approve new partner registrations', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="fbs_vendor_auto_publish" id="fbs_vendor_auto_publish" <?php checked( ! empty( $s['vendor_auto_publish'] ) ); ?>>
								<label class="form-check-label" for="fbs_vendor_auto_publish"><?php esc_html_e( 'Publish partner listings immediately (otherwise pending review)', 'flex-multiple-listing-and-booking-system' ); ?></label>
							</div>
						</div>
					</div>

					<div class="alert alert-light border small mb-0">
						<strong><?php esc_html_e( 'Optional CTA shortcode:', 'flex-multiple-listing-and-booking-system' ); ?></strong>
						<code class="user-select-all ms-1">[fbs_become_partner]</code>
						<span class="text-muted ms-2"><?php esc_html_e( 'Add this on your homepage to promote partner signup.', 'flex-multiple-listing-and-booking-system' ); ?></span>
					</div>
				</div>
			</div>

		</div><!-- .tab-content -->

		<div class="mt-3">
			<button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle me-1"></i><?php esc_html_e( 'Save All Settings', 'flex-multiple-listing-and-booking-system' ); ?></button>
		</div>
	</form>
</div>
