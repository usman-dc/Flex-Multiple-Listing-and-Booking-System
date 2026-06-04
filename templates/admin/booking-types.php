<?php
/**
 * Booking types — create/edit many types (cars, hotels, …), list, shortcodes, delete.
 *
 * @package FlexBookingSystem
 *
 * @var array                                $ulbm_booking_types    All type rows.
 * @var int                                  $ulbm_types_total      Row count.
 * @var string                               $ulbm_type_notice      Notice code from redirect.
 * @var array<string, mixed>|null            $ulbm_editing_type     Row when editing.
 * @var bool                                 $ulbm_show_type_form   Show add/edit form.
 * @var array<string, array<string, mixed>>  $ulbm_industry_catalog Industry definitions.
 */

use FlexBooking\Forms\PublicBookingFields;
use FlexBooking\Setup\IndustryCatalog;

defined( 'ABSPATH' ) || exit;

$ulbm_booking_types    = isset( $ulbm_booking_types ) && is_array( $ulbm_booking_types ) ? $ulbm_booking_types : array();
$ulbm_types_total      = isset( $ulbm_types_total ) ? (int) $ulbm_types_total : 0;
$ulbm_type_notice      = isset( $ulbm_type_notice ) ? (string) $ulbm_type_notice : '';
$ulbm_editing_type     = isset( $ulbm_editing_type ) && is_array( $ulbm_editing_type ) ? $ulbm_editing_type : null;
$ulbm_show_type_form   = ! empty( $ulbm_show_type_form );
$ulbm_industry_catalog = isset( $ulbm_industry_catalog ) && is_array( $ulbm_industry_catalog ) ? $ulbm_industry_catalog : array();

$ulbm_type_notice_messages = array(
	'created'               => array( 'success', __( 'Booking type created.', 'flex-booking-system' ) ),
	'updated'               => array( 'success', __( 'Booking type updated.', 'flex-booking-system' ) ),
	'deleted'               => array( 'success', __( 'Booking type deleted.', 'flex-booking-system' ) ),
	'save_failed'           => array( 'danger', __( 'Could not save the booking type.', 'flex-booking-system' ) ),
	'delete_failed'         => array( 'danger', __( 'Could not delete the booking type.', 'flex-booking-system' ) ),
	'delete_invalid'        => array( 'danger', __( 'Invalid booking type for delete.', 'flex-booking-system' ) ),
	'delete_has_bookings'   => array( 'danger', __( 'This type has bookings. Remove or reassign them before deleting.', 'flex-booking-system' ) ),
	'error_name'            => array( 'danger', __( 'Name is required.', 'flex-booking-system' ) ),
	'error_slug'            => array( 'danger', __( 'Slug could not be generated. Enter a valid slug.', 'flex-booking-system' ) ),
	'error_duplicate_slug'  => array( 'danger', __( 'That slug is already used. Choose another.', 'flex-booking-system' ) ),
);

$ulbm_notice_alert = null;
if ( '' !== $ulbm_type_notice && isset( $ulbm_type_notice_messages[ $ulbm_type_notice ] ) ) {
	$ulbm_notice_alert = $ulbm_type_notice_messages[ $ulbm_type_notice ];
}

$ulbm_row_industry_label = static function ( $row ) {
	$key = PublicBookingFields::industry_from_type( $row );
	if ( 'generic' === $key ) {
		return __( 'Generic', 'flex-booking-system' );
	}
	$def = IndustryCatalog::get( $key );
	return $def ? (string) $def['select_label'] : $key;
};

$ulbm_row_industry_value = static function ( $row ) {
	if ( ! empty( $row['settings'] ) ) {
		$d = json_decode( (string) $row['settings'], true );
		if ( is_array( $d ) && isset( $d['industry'] ) ) {
			return sanitize_key( (string) $d['industry'] );
		}
	}
	return 'generic';
};

$edit_industry = 'generic';
if ( $ulbm_editing_type ) {
	$edit_industry = $ulbm_row_industry_value( $ulbm_editing_type );
	if ( '' === $edit_industry ) {
		$edit_industry = 'generic';
	}
}

$list_url  = admin_url( 'admin.php?page=ulbm-booking-types' );
$new_url   = add_query_arg( 'ulbm_new', '1', $list_url );
$edit_base = $list_url;
?>
<div class="wrap ulbm-admin-wrap container-fluid py-3">
	<div class="ulbm-page-header d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
		<div>
			<h1 class="h3 mb-1 ulbm-page-title d-flex align-items-center gap-2">
				<i class="bi bi-tags text-primary" aria-hidden="true"></i>
				<?php esc_html_e( 'Booking Types', 'flex-booking-system' ); ?>
			</h1>
			<p class="text-muted small mb-0">
				<?php esc_html_e( 'Create separate booking flows for cars, hotels, tours, and more. Each type auto-creates a Custom Post Type (CPT) in your admin menu.', 'flex-booking-system' ); ?>
			</p>
		</div>
		<?php if ( ! $ulbm_show_type_form ) : ?>
			<a class="btn btn-primary" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Add booking type', 'flex-booking-system' ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( $ulbm_notice_alert ) : ?>
		<div class="alert alert-<?php echo esc_attr( $ulbm_notice_alert[0] ); ?>" role="status"><?php echo esc_html( $ulbm_notice_alert[1] ); ?></div>
	<?php endif; ?>

	<?php if ( $ulbm_show_type_form ) : ?>
		<?php
		$is_edit   = (bool) $ulbm_editing_type;
		$type_id   = $is_edit ? (int) $ulbm_editing_type['id'] : 0;
		$val_name  = $is_edit ? (string) $ulbm_editing_type['name'] : '';
		$val_slug  = $is_edit ? (string) $ulbm_editing_type['slug'] : '';
		$val_desc  = $is_edit ? (string) $ulbm_editing_type['description'] : '';
		$val_stat  = $is_edit ? (string) $ulbm_editing_type['status'] : 'publish';
		$form_title = $is_edit ? __( 'Edit booking type', 'flex-booking-system' ) : __( 'New booking type', 'flex-booking-system' );
		?>
		<div class="ulbm-admin-panel border rounded bg-white mb-4">
			<div class="ulbm-admin-panel-head px-3 py-3 border-bottom bg-white">
				<span class="fw-semibold"><?php echo esc_html( $form_title ); ?></span>
			</div>
			<div class="p-3">
				<form method="post" action="<?php echo esc_url( $list_url ); ?>" class="row g-3">
					<?php wp_nonce_field( 'ulbm_booking_types', 'ulbm_booking_types_nonce' ); ?>
					<input type="hidden" name="ulbm_type_action" value="save" />
					<input type="hidden" name="ulbm_type_id" value="<?php echo esc_attr( (string) $type_id ); ?>" />

					<div class="col-md-6">
						<label class="form-label" for="ulbm_type_name"><?php esc_html_e( 'Name', 'flex-booking-system' ); ?></label>
						<input class="form-control" name="ulbm_type_name" id="ulbm_type_name" value="<?php echo esc_attr( $val_name ); ?>" required maxlength="191" />
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm_type_slug"><?php esc_html_e( 'Slug', 'flex-booking-system' ); ?></label>
						<input class="form-control" name="ulbm_type_slug" id="ulbm_type_slug" value="<?php echo esc_attr( $val_slug ); ?>" maxlength="191" />
						<p class="form-text small mb-0"><?php esc_html_e( 'URL-safe identifier; leave blank to generate from the name. Must be unique.', 'flex-booking-system' ); ?></p>
					</div>
					<div class="col-12">
						<label class="form-label" for="ulbm_type_description"><?php esc_html_e( 'Description', 'flex-booking-system' ); ?></label>
						<textarea class="form-control" name="ulbm_type_description" id="ulbm_type_description" rows="3"><?php echo esc_textarea( $val_desc ); ?></textarea>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm_type_industry"><?php esc_html_e( 'Industry / form profile', 'flex-booking-system' ); ?></label>
						<select class="form-select" name="ulbm_type_industry" id="ulbm_type_industry">
							<option value="generic" <?php selected( $edit_industry, 'generic' ); ?>><?php esc_html_e( 'Generic (simple notes only)', 'flex-booking-system' ); ?></option>
							<?php foreach ( $ulbm_industry_catalog as $ik => $idef ) : ?>
								<option value="<?php echo esc_attr( (string) $ik ); ?>" <?php selected( $edit_industry, (string) $ik ); ?>>
									<?php echo esc_html( (string) $idef['select_label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="form-text small mb-0"><?php esc_html_e( 'Controls extra questions on the public booking form (e.g. vehicle type for cars).', 'flex-booking-system' ); ?></p>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="ulbm_type_status"><?php esc_html_e( 'Status', 'flex-booking-system' ); ?></label>
						<select class="form-select" name="ulbm_type_status" id="ulbm_type_status">
							<option value="publish" <?php selected( $val_stat, 'publish' ); ?>><?php esc_html_e( 'Published', 'flex-booking-system' ); ?></option>
							<option value="draft" <?php selected( $val_stat, 'draft' ); ?>><?php esc_html_e( 'Draft', 'flex-booking-system' ); ?></option>
						</select>
					</div>
					<div class="col-12 d-flex flex-wrap gap-2">
						<button type="submit" class="btn btn-primary"><?php echo $is_edit ? esc_html__( 'Update type', 'flex-booking-system' ) : esc_html__( 'Create type', 'flex-booking-system' ); ?></button>
						<a class="btn btn-outline-secondary" href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'Cancel', 'flex-booking-system' ); ?></a>
					</div>
				</form>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! $ulbm_show_type_form ) : ?>
		<?php
		$ulbm_existing_slugs = array();
		foreach ( $ulbm_booking_types as $et ) {
			$ulbm_existing_slugs[] = (string) $et['slug'];
		}
		$ulbm_available_industries = array();
		foreach ( $ulbm_industry_catalog as $ikey => $idef ) {
			if ( ! in_array( (string) $idef['booking_slug'], $ulbm_existing_slugs, true ) ) {
				$ulbm_available_industries[ $ikey ] = $idef;
			}
		}
		?>

		<?php if ( ! empty( $ulbm_available_industries ) ) : ?>
			<div class="ulbm-admin-panel border rounded bg-white mb-4">
				<div class="ulbm-admin-panel-head px-3 py-3 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
					<span class="fw-semibold"><i class="bi bi-lightning-charge text-warning me-1"></i><?php esc_html_e( 'Quick-add from catalog', 'flex-booking-system' ); ?></span>
					<span class="small text-muted"><?php esc_html_e( 'Select the booking types you need — each gets its own CPT and form.', 'flex-booking-system' ); ?></span>
				</div>
				<div class="p-3">
					<div class="row g-2" id="ulbm-quick-add-grid">
						<?php foreach ( $ulbm_available_industries as $qakey => $qadef ) : ?>
							<div class="col-sm-6 col-lg-4 col-xxl-3">
								<label class="d-flex align-items-start gap-2 border rounded p-2 h-100" style="cursor:pointer;">
									<input type="checkbox" class="form-check-input mt-1 ulbm-quick-add-cb" value="<?php echo esc_attr( (string) $qakey ); ?>" />
									<span>
										<span class="fw-semibold small d-block"><?php echo esc_html( (string) $qadef['select_label'] ); ?></span>
										<span class="text-muted" style="font-size:.75rem;"><?php echo esc_html( (string) $qadef['description'] ); ?></span>
									</span>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="mt-3 d-flex flex-wrap align-items-center gap-2">
						<button type="button" class="btn btn-success btn-sm" id="ulbm-quick-add-btn" disabled>
							<i class="bi bi-plus-circle me-1"></i><?php esc_html_e( 'Create selected types', 'flex-booking-system' ); ?>
						</button>
						<span class="small text-muted" id="ulbm-quick-add-status"></span>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<p class="text-muted small mb-3">
			<?php
			printf(
				/* translators: %d: number of booking types */
				esc_html__( '%d booking type(s) configured. Each published type auto-creates a Custom Post Type (CPT) in the plugin menu.', 'flex-booking-system' ),
				(int) $ulbm_types_total
			);
			?>
		</p>

		<div class="ulbm-admin-panel border rounded bg-white">
			<div class="ulbm-admin-panel-head px-3 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom bg-white">
				<span class="fw-semibold"><?php esc_html_e( 'All types', 'flex-booking-system' ); ?></span>
				<span class="badge text-bg-light border"><?php echo esc_html( (string) (int) $ulbm_types_total ); ?> <?php esc_html_e( 'records', 'flex-booking-system' ); ?></span>
			</div>
			<div class="p-0 ulbm-bookings-table-wrap">
				<div class="table-responsive">
					<table class="table ulbm-table mb-0 align-middle w-100">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'ID', 'flex-booking-system' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Name', 'flex-booking-system' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Slug', 'flex-booking-system' ); ?></th>
								<th scope="col"><?php esc_html_e( 'CPT (auto)', 'flex-booking-system' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Industry', 'flex-booking-system' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'flex-booking-system' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Shortcode', 'flex-booking-system' ); ?></th>
								<th scope="col" class="text-nowrap"><?php esc_html_e( 'Actions', 'flex-booking-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $ulbm_booking_types ) ) : ?>
								<tr>
									<td colspan="8" class="text-muted p-4">
										<?php esc_html_e( 'No booking types yet. Use the catalog above or click "Add booking type" to create one.', 'flex-booking-system' ); ?>
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $ulbm_booking_types as $t ) : ?>
									<?php
									$tid      = (int) $t['id'];
									$sc       = '[ulbm_booking_form id="' . $tid . '"]';
									$cpt_slug = \FlexBooking\PostTypes\BookingTypePostTypeRegistry::cpt_name_from_slug( (string) $t['slug'] );
									?>
									<tr>
										<td><?php echo esc_html( (string) $tid ); ?></td>
										<td><strong><?php echo esc_html( (string) $t['name'] ); ?></strong></td>
										<td><code><?php echo esc_html( (string) $t['slug'] ); ?></code></td>
										<td>
											<code class="small"><?php echo esc_html( $cpt_slug ); ?></code>
											<?php if ( 'publish' === (string) $t['status'] ) : ?>
												<a class="d-block small" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $cpt_slug ) ); ?>"><?php esc_html_e( 'View posts', 'flex-booking-system' ); ?></a>
											<?php endif; ?>
										</td>
										<td class="small"><?php echo esc_html( $ulbm_row_industry_label( $t ) ); ?></td>
										<td>
											<span class="badge rounded-pill <?php echo 'publish' === (string) $t['status'] ? 'text-bg-success' : 'text-bg-warning'; ?>">
												<?php echo esc_html( (string) $t['status'] ); ?>
											</span>
										</td>
										<td><code class="small user-select-all text-break"><?php echo esc_html( $sc ); ?></code></td>
										<td class="small">
											<div class="d-flex flex-wrap gap-1">
												<a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url( add_query_arg( 'ulbm_edit', (string) $tid, $edit_base ) ); ?>"><?php esc_html_e( 'Edit', 'flex-booking-system' ); ?></a>
												<form method="post" action="<?php echo esc_url( $list_url ); ?>" class="d-inline" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Delete this booking type? This cannot be undone.', 'flex-booking-system' ) ); ?>);">
													<?php wp_nonce_field( 'ulbm_booking_types', 'ulbm_booking_types_nonce' ); ?>
													<input type="hidden" name="ulbm_type_action" value="delete" />
													<input type="hidden" name="ulbm_type_id" value="<?php echo esc_attr( (string) $tid ); ?>" />
													<button type="submit" class="btn btn-sm btn-outline-danger"><?php esc_html_e( 'Delete', 'flex-booking-system' ); ?></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
