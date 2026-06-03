<?php
/**
 * Listing metabox — tabbed Bootstrap 5 builder.
 *
 * @package FlexBookingSystem
 *
 * @var \WP_Post $post           Current post.
 * @var array    $meta           Listing meta values.
 * @var array    $booking_types  Booking type rows.
 */

use FlexBooking\Listings\ListingMeta;

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'general'      => __( 'General', 'flex-booking-system' ),
	'pricing'      => __( 'Pricing', 'flex-booking-system' ),
	'gallery'      => __( 'Gallery', 'flex-booking-system' ),
	'location'     => __( 'Location', 'flex-booking-system' ),
	'availability' => __( 'Availability', 'flex-booking-system' ),
	'features'     => __( 'Features / Amenities', 'flex-booking-system' ),
	'services'     => __( 'Extra Services', 'flex-booking-system' ),
	'faq'          => __( 'FAQ', 'flex-booking-system' ),
);
?>
<div class="fbs-listing-metabox">
	<ul class="nav nav-tabs mb-3" role="tablist">
		<?php $first = true; ?>
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<li class="nav-item" role="presentation">
				<button class="nav-link <?php echo $first ? 'active' : ''; ?>" id="fbs-tab-<?php echo esc_attr( $tab_key ); ?>"
					data-bs-toggle="tab" data-bs-target="#fbs-pane-<?php echo esc_attr( $tab_key ); ?>"
					type="button" role="tab" aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
					<?php echo esc_html( $tab_label ); ?>
				</button>
			</li>
			<?php $first = false; ?>
		<?php endforeach; ?>
	</ul>

	<div class="tab-content">
		<!-- General -->
		<div class="tab-pane fade show active" id="fbs-pane-general" role="tabpanel">
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Linked Booking Type', 'flex-booking-system' ); ?></label>
					<select class="form-select" name="fbs_booking_type_id">
						<option value="0"><?php esc_html_e( '— None —', 'flex-booking-system' ); ?></option>
						<?php foreach ( $booking_types as $bt ) : ?>
							<option value="<?php echo esc_attr( (string) (int) $bt['id'] ); ?>" <?php selected( (int) $meta[ ListingMeta::KEY_BOOKING_TYPE_ID ], (int) $bt['id'] ); ?>>
								<?php echo esc_html( (string) $bt['name'] ); ?> (#<?php echo esc_html( (string) (int) $bt['id'] ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<p class="form-text"><?php esc_html_e( 'Links this listing to a booking type for form fields and reservations.', 'flex-booking-system' ); ?></p>
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Booking Mode', 'flex-booking-system' ); ?></label>
					<select class="form-select" name="fbs_booking_mode">
						<option value="daily" <?php selected( $meta[ ListingMeta::KEY_BOOKING_MODE ], 'daily' ); ?>><?php esc_html_e( 'Daily (check-in / check-out)', 'flex-booking-system' ); ?></option>
						<option value="hourly" <?php selected( $meta[ ListingMeta::KEY_BOOKING_MODE ], 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'flex-booking-system' ); ?></option>
						<option value="time_slot" <?php selected( $meta[ ListingMeta::KEY_BOOKING_MODE ], 'time_slot' ); ?>><?php esc_html_e( 'Fixed time slots', 'flex-booking-system' ); ?></option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Max guests', 'flex-booking-system' ); ?></label>
					<input type="number" class="form-control" name="fbs_max_guests" value="<?php echo esc_attr( (string) $meta[ ListingMeta::KEY_MAX_GUESTS ] ); ?>" min="1">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Check-in time', 'flex-booking-system' ); ?></label>
					<input type="time" class="form-control" name="fbs_check_in_time" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_CHECK_IN_TIME ] ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Check-out time', 'flex-booking-system' ); ?></label>
					<input type="time" class="form-control" name="fbs_check_out_time" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_CHECK_OUT_TIME ] ); ?>">
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Contact email', 'flex-booking-system' ); ?></label>
					<input type="email" class="form-control" name="fbs_contact_email" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_CONTACT_EMAIL ] ); ?>">
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Contact phone', 'flex-booking-system' ); ?></label>
					<input type="tel" class="form-control" name="fbs_contact_phone" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_CONTACT_PHONE ] ); ?>">
				</div>
				<div class="col-md-6">
					<label class="form-label"><?php esc_html_e( 'Video URL (YouTube / Vimeo)', 'flex-booking-system' ); ?></label>
					<input type="url" class="form-control" name="fbs_video_url" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_VIDEO_URL ] ); ?>">
				</div>
				<div class="col-md-6">
					<div class="form-check mt-4">
						<input class="form-check-input" type="checkbox" name="fbs_instant_booking" id="fbs_instant_booking" <?php checked( $meta[ ListingMeta::KEY_INSTANT_BOOKING ] ); ?>>
						<label class="form-check-label" for="fbs_instant_booking"><?php esc_html_e( 'Enable instant booking (no admin approval needed)', 'flex-booking-system' ); ?></label>
					</div>
				</div>
			</div>
		</div>

		<!-- Pricing -->
		<div class="tab-pane fade" id="fbs-pane-pricing" role="tabpanel">
			<div class="row g-3">
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Base price', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control" name="fbs_base_price" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_BASE_PRICE ] ); ?>" placeholder="0.00">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Sale price', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control" name="fbs_sale_price" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_SALE_PRICE ] ); ?>" placeholder="">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Price suffix', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control" name="fbs_price_suffix" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_PRICE_SUFFIX ] ); ?>" placeholder="<?php esc_attr_e( '/ night', 'flex-booking-system' ); ?>">
					<span class="form-text"><?php esc_html_e( 'Shown after the price, e.g. / night or / booking', 'flex-booking-system' ); ?></span>
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Min booking (days/hours)', 'flex-booking-system' ); ?></label>
					<input type="number" class="form-control" name="fbs_min_booking" value="<?php echo esc_attr( (string) $meta[ ListingMeta::KEY_MIN_BOOKING ] ); ?>" min="1">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Max booking (days/hours)', 'flex-booking-system' ); ?></label>
					<input type="number" class="form-control" name="fbs_max_booking" value="<?php echo esc_attr( (string) $meta[ ListingMeta::KEY_MAX_BOOKING ] ); ?>" min="1">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Deposit %', 'flex-booking-system' ); ?></label>
					<input type="number" class="form-control" name="fbs_deposit_percent" value="<?php echo esc_attr( (string) $meta[ ListingMeta::KEY_DEPOSIT_PERCENT ] ); ?>" min="0" max="100">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Free cancellation (days before)', 'flex-booking-system' ); ?></label>
					<input type="number" class="form-control" name="fbs_cancellation_days" value="<?php echo esc_attr( (string) $meta[ ListingMeta::KEY_CANCELLATION_DAYS ] ); ?>" min="0">
				</div>
			</div>
		</div>

		<!-- Gallery -->
		<div class="tab-pane fade" id="fbs-pane-gallery" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Manage listing photos. Click to add or drag to reorder.', 'flex-booking-system' ); ?></p>
			<input type="hidden" name="fbs_gallery" id="fbs-gallery-ids" value="<?php echo esc_attr( implode( ',', $meta[ ListingMeta::KEY_GALLERY ] ) ); ?>">
			<div id="fbs-gallery-preview" class="d-flex flex-wrap gap-2 mb-3">
				<?php foreach ( $meta[ ListingMeta::KEY_GALLERY ] as $att_id ) : ?>
					<?php $thumb = wp_get_attachment_image_url( $att_id, 'thumbnail' ); ?>
					<?php if ( $thumb ) : ?>
						<div class="fbs-gallery-thumb position-relative" data-id="<?php echo esc_attr( (string) $att_id ); ?>">
							<img src="<?php echo esc_url( $thumb ); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:4px;">
							<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 fbs-gallery-remove" style="background-color:rgba(0,0,0,.5);padding:4px;font-size:8px;" aria-label="<?php esc_attr_e( 'Remove', 'flex-booking-system' ); ?>"></button>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="fbs-gallery-add"><?php esc_html_e( 'Add images', 'flex-booking-system' ); ?></button>
		</div>

		<!-- Location -->
		<div class="tab-pane fade" id="fbs-pane-location" role="tabpanel">
			<div class="row g-3">
				<div class="col-12">
					<label class="form-label"><?php esc_html_e( 'Address', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control" name="fbs_address" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_ADDRESS ] ); ?>" placeholder="<?php esc_attr_e( '123 Main St, City, Country', 'flex-booking-system' ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Latitude', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control" name="fbs_latitude" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_LATITUDE ] ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Longitude', 'flex-booking-system' ); ?></label>
					<input type="text" class="form-control" name="fbs_longitude" value="<?php echo esc_attr( $meta[ ListingMeta::KEY_LONGITUDE ] ); ?>">
				</div>
				<div class="col-md-4">
					<label class="form-label"><?php esc_html_e( 'Map zoom', 'flex-booking-system' ); ?></label>
					<input type="number" class="form-control" name="fbs_map_zoom" value="<?php echo esc_attr( (string) $meta[ ListingMeta::KEY_MAP_ZOOM ] ); ?>" min="1" max="20">
				</div>
			</div>
		</div>

		<!-- Availability -->
		<div class="tab-pane fade" id="fbs-pane-availability" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Availability rules are managed per-listing via the Availability Rules engine (coming soon). Basic min/max booking constraints are in the Pricing tab.', 'flex-booking-system' ); ?></p>
			<div class="alert alert-info small mb-0">
				<?php esc_html_e( 'Current mode:', 'flex-booking-system' ); ?>
				<strong><?php echo esc_html( ucfirst( (string) $meta[ ListingMeta::KEY_BOOKING_MODE ] ) ); ?></strong>
			</div>
		</div>

		<!-- Features / Amenities -->
		<div class="tab-pane fade" id="fbs-pane-features" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Add listing-specific highlights (e.g. "WiFi", "Pool", "Parking"). You can also assign taxonomy amenities above.', 'flex-booking-system' ); ?></p>
			<input type="hidden" name="fbs_features_json" id="fbs-features-json" value="<?php echo esc_attr( wp_json_encode( $meta[ ListingMeta::KEY_FEATURES ] ) ); ?>">
			<div id="fbs-features-list" class="mb-3"></div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="fbs-feature-add"><?php esc_html_e( 'Add feature', 'flex-booking-system' ); ?></button>
		</div>

		<!-- Extra Services -->
		<div class="tab-pane fade" id="fbs-pane-services" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Optional paid add-ons customers can select during booking (e.g. "Airport pickup — $20").', 'flex-booking-system' ); ?></p>
			<input type="hidden" name="fbs_extra_services_json" id="fbs-services-json" value="<?php echo esc_attr( wp_json_encode( $meta[ ListingMeta::KEY_EXTRA_SERVICES ] ) ); ?>">
			<div id="fbs-services-list" class="mb-3"></div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="fbs-service-add"><?php esc_html_e( 'Add service', 'flex-booking-system' ); ?></button>
		</div>

		<!-- FAQ -->
		<div class="tab-pane fade" id="fbs-pane-faq" role="tabpanel">
			<p class="text-muted small"><?php esc_html_e( 'Frequently asked questions shown on the listing page.', 'flex-booking-system' ); ?></p>
			<input type="hidden" name="fbs_faq_json" id="fbs-faq-json" value="<?php echo esc_attr( wp_json_encode( $meta[ ListingMeta::KEY_FAQ ] ) ); ?>">
			<div id="fbs-faq-list" class="mb-3"></div>
			<button type="button" class="btn btn-outline-primary btn-sm" id="fbs-faq-add"><?php esc_html_e( 'Add FAQ', 'flex-booking-system' ); ?></button>
		</div>
	</div>
</div>
