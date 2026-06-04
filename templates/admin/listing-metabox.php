<?php
/**
 * Listing metabox — tabbed Bootstrap 5 builder.
 *
 * @package FlexBookingSystem
 *
 * @var \WP_Post $post           Current post.
 * @var array    $ulbm_meta           Listing meta values.
 * @var array    $ulbm_booking_types  Booking type rows.
 */

use FlexBooking\Listings\ListingMeta;

defined( 'ABSPATH' ) || exit;

$ulbm_tabs = array(
	'general'      => __( 'General', 'flex-multiple-listing-and-booking-system' ),
	'pricing'      => __( 'Pricing', 'flex-multiple-listing-and-booking-system' ),
	'gallery'      => __( 'Gallery', 'flex-multiple-listing-and-booking-system' ),
	'location'     => __( 'Location', 'flex-multiple-listing-and-booking-system' ),
	'availability' => __( 'Availability', 'flex-multiple-listing-and-booking-system' ),
	'features'     => __( 'Features / Amenities', 'flex-multiple-listing-and-booking-system' ),
	'services'     => __( 'Extra Services', 'flex-multiple-listing-and-booking-system' ),
	'faq'          => __( 'FAQ', 'flex-multiple-listing-and-booking-system' ),
);
?>
<div class="ulbm-listing-metabox">
	<ul class="nav nav-tabs mb-3" role="tablist">
		<?php $ulbm_first = true; ?>
		<?php foreach ( $ulbm_tabs as $ulbm_tab_key => $ulbm_tab_label ) : ?>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $ulbm_first ? 'active' : ''; ?>" id="ulbm-tab-<?php echo esc_attr( $ulbm_tab_key ); ?>"
					data-bs-toggle="tab" data-bs-target="#ulbm-pane-<?php echo esc_attr( $ulbm_tab_key ); ?>"
					type="button" role="tab" aria-selected="<?php echo $ulbm_first ? 'true' : 'false'; ?>">
					<?php echo esc_html( $ulbm_tab_label ); ?>
				</button>
			</li>
			<?php $ulbm_first = false; ?>
		<?php endforeach; ?>
	</ul>

	<div class="tab-content">
		<!-- General -->
		<div class="tab-pane fade show active" id="ulbm-pane-general" role="tabpanel">
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Linked Booking Type', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<select class="form-select" name="ulbm_booking_type_id">
						<option value="0"><?php esc_html_e( '— None —', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<?php foreach ( $ulbm_booking_types as $ulbm_bt ) : ?>
							<option value="<?php echo esc_attr( (string) (int) $ulbm_bt['id'] ); ?>" <?php selected( (int) $ulbm_meta[ ListingMeta::KEY_BOOKING_TYPE_ID ], (int) $ulbm_bt['id'] ); ?>>
								<?php echo esc_html( (string) $ulbm_bt['name'] ); ?> (#<?php echo esc_html( (string) (int) $ulbm_bt['id'] ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<p class="form-text"><?php esc_html_e( 'Links this listing to a booking type for form fields and reservations.', 'flex-multiple-listing-and-booking-system' ); ?></p>
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Booking Mode', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<select class="form-select" name="ulbm_booking_mode">
						<option value="daily" <?php selected( $ulbm_meta[ ListingMeta::KEY_BOOKING_MODE ], 'daily' ); ?>><?php esc_html_e( 'Daily (check-in / check-out)', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="hourly" <?php selected( $ulbm_meta[ ListingMeta::KEY_BOOKING_MODE ], 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="time_slot" <?php selected( $ulbm_meta[ ListingMeta::KEY_BOOKING_MODE ], 'time_slot' ); ?>><?php esc_html_e( 'Fixed time slots', 'flex-multiple-listing-and-booking-system' ); ?></option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Max guests', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="number" class="form-control" name="ulbm_max_guests" value="<?php echo esc_attr( (string) $ulbm_meta[ ListingMeta::KEY_MAX_GUESTS ] ); ?>" min="1">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Check-in time', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="time" class="form-control" name="ulbm_check_in_time" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_CHECK_IN_TIME ] ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Check-out time', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="time" class="form-control" name="ulbm_check_out_time" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_CHECK_OUT_TIME ] ); ?>">
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Contact email', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="email" class="form-control" name="ulbm_contact_email" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_CONTACT_EMAIL ] ); ?>">
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Contact phone', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="tel" class="form-control" name="ulbm_contact_phone" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_CONTACT_PHONE ] ); ?>">
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Video URL (YouTube / Vimeo)', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="url" class="form-control" name="ulbm_video_url" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_VIDEO_URL ] ); ?>">
				</div>
				<div class="col-md-6">
					<div class="form-check mt-4">
						<input class="form-check-input" type="checkbox" name="ulbm_instant_booking" id="ulbm_instant_booking" <?php checked( $ulbm_meta[ ListingMeta::KEY_INSTANT_BOOKING ] ); ?>>
						<label class="form-check-label" for="ulbm_instant_booking"><?php esc_html_e( 'Enable instant booking (no admin approval needed)', 'flex-multiple-listing-and-booking-system' ); ?></label>
					</div>
				</div>
			</div>
		</div>

		<!-- Pricing -->
		<div class="tab-pane fade" id="ulbm-pane-pricing" role="tabpanel">
			<div class="row g-3">
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Base price', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control" name="ulbm_base_price" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_BASE_PRICE ] ); ?>" placeholder="0.00">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Sale price', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control" name="ulbm_sale_price" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_SALE_PRICE ] ); ?>" placeholder="">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Price suffix', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control" name="ulbm_price_suffix" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_PRICE_SUFFIX ] ); ?>" placeholder="<?php esc_attr_e( '/ night', 'flex-multiple-listing-and-booking-system' ); ?>">
					<span class="form-text"><?php esc_html_e( 'Shown after the price, e.g. / night or / booking', 'flex-multiple-listing-and-booking-system' ); ?></span>
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Min booking (days/hours)', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="number" class="form-control" name="ulbm_min_booking" value="<?php echo esc_attr( (string) $ulbm_meta[ ListingMeta::KEY_MIN_BOOKING ] ); ?>" min="1">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Max booking (days/hours)', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="number" class="form-control" name="ulbm_max_booking" value="<?php echo esc_attr( (string) $ulbm_meta[ ListingMeta::KEY_MAX_BOOKING ] ); ?>" min="1">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Deposit %', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="number" class="form-control" name="ulbm_deposit_percent" value="<?php echo esc_attr( (string) $ulbm_meta[ ListingMeta::KEY_DEPOSIT_PERCENT ] ); ?>" min="0" max="100">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Free cancellation (days before)', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="number" class="form-control" name="ulbm_cancellation_days" value="<?php echo esc_attr( (string) $ulbm_meta[ ListingMeta::KEY_CANCELLATION_DAYS ] ); ?>" min="0">
				</div>
			</div>
		</div>

		<!-- Gallery -->
		<div class="tab-pane fade" id="ulbm-pane-gallery" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Manage listing photos. Click to add or drag to reorder.', 'flex-multiple-listing-and-booking-system' ); ?></p>
			<input type="hidden" name="ulbm_gallery" id="ulbm-gallery-ids" value="<?php echo esc_attr( implode( ',', $ulbm_meta[ ListingMeta::KEY_GALLERY ] ) ); ?>">
			<div id="ulbm-gallery-preview" class="d-flex flex-wrap gap-2 mb-3">
				<?php foreach ( $ulbm_meta[ ListingMeta::KEY_GALLERY ] as $ulbm_att_id ) : ?>
					<?php $ulbm_thumb = wp_get_attachment_image_url( $ulbm_att_id, 'thumbnail' ); ?>
					<?php if ( $ulbm_thumb ) : ?>
						<div class="ulbm-gallery-thumb position-relative" data-id="<?php echo esc_attr( (string) $ulbm_att_id ); ?>">
							<img src="<?php echo esc_url( $ulbm_thumb ); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:4px;">
							<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 ulbm-gallery-remove" style="background-color:rgba(0,0,0,.5);padding:4px;font-size:8px;" aria-label="<?php esc_attr_e( 'Remove', 'flex-multiple-listing-and-booking-system' ); ?>"></button>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="ulbm-gallery-add"><?php esc_html_e( 'Add images', 'flex-multiple-listing-and-booking-system' ); ?></button>
		</div>

		<!-- Location -->
		<div class="tab-pane fade" id="ulbm-pane-location" role="tabpanel">
			<div class="row g-3">
				<div class="col-12">
					<label class="form-label"><?php esc_html_e( 'Address', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control" name="ulbm_address" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_ADDRESS ] ); ?>" placeholder="<?php esc_attr_e( '123 Main St, City, Country', 'flex-multiple-listing-and-booking-system' ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Latitude', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control" name="ulbm_latitude" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_LATITUDE ] ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Longitude', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="text" class="form-control" name="ulbm_longitude" value="<?php echo esc_attr( $ulbm_meta[ ListingMeta::KEY_LONGITUDE ] ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Map zoom', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<input type="number" class="form-control" name="ulbm_map_zoom" value="<?php echo esc_attr( (string) $ulbm_meta[ ListingMeta::KEY_MAP_ZOOM ] ); ?>" min="1" max="20">
				</div>
			</div>
		</div>

		<!-- Availability -->
		<div class="tab-pane fade" id="ulbm-pane-availability" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Availability rules are managed per-listing via the Availability Rules engine (coming soon). Basic min/max booking constraints are in the Pricing tab.', 'flex-multiple-listing-and-booking-system' ); ?></p>
			<div class="alert alert-info small mb-0">
				<?php esc_html_e( 'Current mode:', 'flex-multiple-listing-and-booking-system' ); ?>
				<strong><?php echo esc_html( ucfirst( (string) $ulbm_meta[ ListingMeta::KEY_BOOKING_MODE ] ) ); ?></strong>
			</div>
		</div>

		<!-- Features / Amenities -->
		<div class="tab-pane fade" id="ulbm-pane-features" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Add listing-specific highlights (e.g. "WiFi", "Pool", "Parking"). You can also assign taxonomy amenities above.', 'flex-multiple-listing-and-booking-system' ); ?></p>
			<input type="hidden" name="ulbm_features_json" id="ulbm-features-json" value="<?php echo esc_attr( wp_json_encode( $ulbm_meta[ ListingMeta::KEY_FEATURES ] ) ); ?>">
			<div id="ulbm-features-list" class="mb-3"></div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="ulbm-feature-add"><?php esc_html_e( 'Add feature', 'flex-multiple-listing-and-booking-system' ); ?></button>
		</div>

		<!-- Extra Services -->
		<div class="tab-pane fade" id="ulbm-pane-services" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Optional paid add-ons customers can select during booking (e.g. "Airport pickup — $20").', 'flex-multiple-listing-and-booking-system' ); ?></p>
			<input type="hidden" name="ulbm_extra_services_json" id="ulbm-services-json" value="<?php echo esc_attr( wp_json_encode( $ulbm_meta[ ListingMeta::KEY_EXTRA_SERVICES ] ) ); ?>">
			<div id="ulbm-services-list" class="mb-3"></div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="ulbm-service-add"><?php esc_html_e( 'Add service', 'flex-multiple-listing-and-booking-system' ); ?></button>
		</div>

		<!-- FAQ -->
		<div class="tab-pane fade" id="ulbm-pane-faq" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Frequently asked questions shown on the listing page.', 'flex-multiple-listing-and-booking-system' ); ?></p>
			<input type="hidden" name="ulbm_faq_json" id="ulbm-faq-json" value="<?php echo esc_attr( wp_json_encode( $ulbm_meta[ ListingMeta::KEY_FAQ ] ) ); ?>">
			<div id="ulbm-faq-list" class="mb-3"></div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="ulbm-faq-add"><?php esc_html_e( 'Add FAQ', 'flex-multiple-listing-and-booking-system' ); ?></button>
		</div>
	</div>
</div>
