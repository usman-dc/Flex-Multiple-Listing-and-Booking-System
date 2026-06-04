<?php

/**

 * Public booking form — contact, schedule, industry-specific questions.

 *

 * @package FlexBookingSystem

 *

 * @var array<string, mixed>              $atts              Shortcode attributes.

 * @var array<string, mixed>|null         $ulbm_booking_type  Loaded type row or null.

 * @var array{industry: string, contact: array, extra: array, title: string} $ulbm_form_groups Field definitions.

 * @var array<string, string>             $ulbm_prefill       Default values for contact fields.

 */



use FlexBooking\Front\PriceFormatter;

use FlexBooking\Listings\ListingMeta;



defined( 'ABSPATH' ) || exit;



$type    = isset( $atts['type'] ) ? sanitize_title( $atts['type'] ) : '';

$id      = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;

$groups  = isset( $ulbm_form_groups ) && is_array( $ulbm_form_groups ) ? $ulbm_form_groups : array(

	'industry' => 'generic',

	'contact'  => array(),

	'extra'    => array(),

	'title'    => '',

);

$prefill = isset( $ulbm_prefill ) && is_array( $ulbm_prefill ) ? $ulbm_prefill : array();



$ulbm_type_name = '';

if ( $ulbm_booking_type && is_array( $ulbm_booking_type ) && ! empty( $ulbm_booking_type['name'] ) ) {

	$ulbm_type_name = (string) $ulbm_booking_type['name'];

}



$ulbm_render_field = static function ( array $f, $compact = false ) use ( $prefill ) {

	$name     = isset( $f['name'] ) ? (string) $f['name'] : '';

	$label    = isset( $f['label'] ) ? (string) $f['label'] : '';

	$type_in  = isset( $f['type'] ) ? (string) $f['type'] : 'text';

	$required = ! empty( $f['required'] );

	$col      = isset( $f['col'] ) ? (string) $f['col'] : 'col-12';

	$ph       = isset( $f['placeholder'] ) ? (string) $f['placeholder'] : '';

	$val      = $prefill[ $name ] ?? '';



	$req = $required ? ' required' : '';

	$id_attr = 'ulbm-f-' . sanitize_key( $name );

	$control_class = $compact ? 'form-control' : 'form-control form-control-sm';

	$select_class  = $compact ? 'form-select' : 'form-select form-select-sm';

	?>

	<div class="<?php echo esc_attr( $col ); ?>">

		<label class="form-label small fw-semibold" for="<?php echo esc_attr( $id_attr ); ?>">

			<?php echo esc_html( $label ); ?>

			<?php if ( $required ) : ?><span class="text-danger">*</span><?php endif; ?>

		</label>

		<?php if ( 'textarea' === $type_in ) : ?>

			<textarea class="<?php echo esc_attr( $control_class ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id_attr ); ?>" rows="3"<?php echo $req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> placeholder="<?php echo esc_attr( $ph ); ?>"><?php echo esc_textarea( $val ); ?></textarea>

		<?php elseif ( 'select' === $type_in && ! empty( $f['options'] ) && is_array( $f['options'] ) ) : ?>

			<select class="<?php echo esc_attr( $select_class ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id_attr ); ?>"<?php echo $req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

				<option value=""><?php esc_html_e( '— Select —', 'flex-multiple-listing-and-booking-system' ); ?></option>

				<?php foreach ( $f['options'] as $opt_val => $opt_label ) : ?>

					<option value="<?php echo esc_attr( (string) $opt_val ); ?>"><?php echo esc_html( (string) $opt_label ); ?></option>

				<?php endforeach; ?>

			</select>

		<?php else : ?>

			<?php

			$attrs = isset( $f['attrs'] ) && is_array( $f['attrs'] ) ? $f['attrs'] : array();

			$extra = '';

			foreach ( $attrs as $ak => $av ) {

				$extra .= ' ' . esc_attr( (string) $ak ) . '="' . esc_attr( (string) $av ) . '"';

			}

			?>

			<input

				type="<?php echo esc_attr( in_array( $type_in, array( 'number', 'email', 'tel' ), true ) ? $type_in : 'text' ); ?>"

				class="<?php echo esc_attr( $control_class ); ?>"

				name="<?php echo esc_attr( $name ); ?>"

				id="<?php echo esc_attr( $id_attr ); ?>"

				value="<?php echo esc_attr( $val ); ?>"

				placeholder="<?php echo esc_attr( $ph ); ?>"

				<?php echo $req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			/>

		<?php endif; ?>

	</div>

	<?php

};



$ulbm_listing_id  = isset( $ulbm_listing_id ) ? (int) $ulbm_listing_id : 0;
if ( ! $ulbm_listing_id && is_singular() ) {
	$pt = get_post_type();
	if ( $pt && \FlexBooking\PostTypes\BookingTypePostTypeRegistry::is_listing_post_type( (string) $pt ) ) {
		$ulbm_listing_id = get_the_ID();
	}
}
$ulbm_marketplace = is_singular() && $ulbm_listing_id > 0;
$ulbm_is_embedded = $ulbm_marketplace || ( is_singular() && has_post_thumbnail() );



$ulbm_nightly_price  = 0.0;

$ulbm_cleaning_fee   = 0.0;

$ulbm_service_fee    = 0.0;

$ulbm_max_guests     = 1;

$ulbm_price_suffix   = '/night';

$ulbm_check_in_time  = '14:00';

$ulbm_check_out_time = '11:00';



if ( $ulbm_marketplace ) {

	$base = ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_BASE_PRICE, 'string' );

	$sale = ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_SALE_PRICE, 'string' );

	$ulbm_nightly_price  = (float) ( $sale ?: $base );

	$ulbm_max_guests     = max( 1, ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_MAX_GUESTS, 'int' ) );

	$ulbm_price_suffix   = PriceFormatter::normalize_suffix( ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_PRICE_SUFFIX, 'string' ) ?: '/night' );

	$ulbm_check_in_time  = ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_CHECK_IN_TIME, 'string' ) ?: '14:00';

	$ulbm_check_out_time = ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_CHECK_OUT_TIME, 'string' ) ?: '11:00';

	$extra_svc          = ListingMeta::get( $ulbm_listing_id, ListingMeta::KEY_EXTRA_SERVICES, 'array' );

	foreach ( $extra_svc as $svc ) {

		if ( ! is_array( $svc ) ) {

			continue;

		}

		$name = strtolower( (string) ( $svc['name'] ?? '' ) );

		$fee  = (float) ( $svc['price'] ?? 0 );

		if ( false !== strpos( $name, 'clean' ) ) {

			$ulbm_cleaning_fee = $fee;

		} elseif ( false !== strpos( $name, 'service' ) ) {

			$ulbm_service_fee = $fee;

		}

	}

}



$ulbm_currency = PriceFormatter::currency_code();

?>

<div

	class="ulbm-booking-form <?php echo $ulbm_marketplace ? 'ulbm-booking-form--marketplace' : ( $ulbm_is_embedded ? '' : 'card border-0 shadow-sm' ); ?> w-100"

	data-ulbm-type="<?php echo esc_attr( $type ); ?>"

	data-ulbm-type-id="<?php echo esc_attr( (string) $id ); ?>"

	data-ulbm-listing-id="<?php echo esc_attr( (string) $ulbm_listing_id ); ?>"

	data-ulbm-industry="<?php echo esc_attr( (string) $groups['industry'] ); ?>"

	<?php if ( $ulbm_marketplace ) : ?>

	data-ulbm-nightly="<?php echo esc_attr( (string) $ulbm_nightly_price ); ?>"

	data-ulbm-cleaning="<?php echo esc_attr( (string) $ulbm_cleaning_fee ); ?>"

	data-ulbm-service="<?php echo esc_attr( (string) $ulbm_service_fee ); ?>"

	data-ulbm-currency="<?php echo esc_attr( $ulbm_currency ); ?>"

	data-ulbm-price-suffix="<?php echo esc_attr( $ulbm_price_suffix ); ?>"

	data-ulbm-check-in-time="<?php echo esc_attr( $ulbm_check_in_time ); ?>"

	data-ulbm-check-out-time="<?php echo esc_attr( $ulbm_check_out_time ); ?>"

	data-ulbm-max-guests="<?php echo esc_attr( (string) $ulbm_max_guests ); ?>"

	<?php endif; ?>

>

	<div class="<?php echo ( $ulbm_marketplace || $ulbm_is_embedded ) ? '' : 'card-body p-4'; ?>">

		<?php if ( ! $ulbm_marketplace && ! $ulbm_is_embedded ) : ?>

			<h2 class="h5 mb-1"><i class="bi bi-calendar-check me-1"></i><?php esc_html_e( 'Book Now', 'flex-multiple-listing-and-booking-system' ); ?></h2>

			<?php if ( $ulbm_type_name ) : ?>

				<p class="text-muted small mb-3"><?php echo esc_html( $ulbm_type_name ); ?></p>

			<?php endif; ?>

		<?php endif; ?>



		<?php if ( $ulbm_type_name && $ulbm_is_embedded && ! $ulbm_marketplace ) : ?>

			<div class="alert alert-light border py-2 px-3 mb-3 small">

				<i class="bi bi-tag me-1 text-primary"></i>

				<strong><?php esc_html_e( 'Booking type:', 'flex-multiple-listing-and-booking-system' ); ?></strong> <?php echo esc_html( $ulbm_type_name ); ?>

			</div>

		<?php endif; ?>



		<form class="<?php echo $ulbm_marketplace ? 'ulbm-marketplace-form' : 'row g-2'; ?>" id="ulbm-booking-form-fields" novalidate>



			<?php if ( $ulbm_marketplace ) : ?>

				<div class="ulbm-mp-fields">

					<div class="ulbm-mp-field">

						<label class="ulbm-mp-label" for="ulbm-checkin"><?php esc_html_e( 'Check-in', 'flex-multiple-listing-and-booking-system' ); ?></label>

						<div class="ulbm-mp-input-wrap">

							<i class="bi bi-calendar3" aria-hidden="true"></i>

							<input type="date" class="ulbm-mp-input ulbm-mp-checkin" id="ulbm-checkin" name="ulbm_checkin" required>

						</div>

					</div>

					<div class="ulbm-mp-field">

						<label class="ulbm-mp-label" for="ulbm-checkout"><?php esc_html_e( 'Check-out', 'flex-multiple-listing-and-booking-system' ); ?></label>

						<div class="ulbm-mp-input-wrap">

							<i class="bi bi-calendar3" aria-hidden="true"></i>

							<input type="date" class="ulbm-mp-input ulbm-mp-checkout" id="ulbm-checkout" name="ulbm_checkout" required>

						</div>

					</div>

					<div class="ulbm-mp-field">

						<label class="ulbm-mp-label" for="ulbm-guests"><?php esc_html_e( 'Guests', 'flex-multiple-listing-and-booking-system' ); ?></label>

						<div class="ulbm-mp-input-wrap">

							<i class="bi bi-people" aria-hidden="true"></i>

							<select class="ulbm-mp-input ulbm-mp-guests" id="ulbm-guests" name="guests_count">

								<?php for ( $g = 1; $g <= $ulbm_max_guests; $g++ ) : ?>

									<option value="<?php echo esc_attr( (string) $g ); ?>"<?php selected( 2, $g ); ?>>

										<?php
										printf(
											/* translators: %d: guest count */
											esc_html( _n( '%d Guest', '%d Guests', $g, 'flex-multiple-listing-and-booking-system' ) ),
											(int) $g
										);
										?>

									</option>

								<?php endfor; ?>

							</select>

						</div>

					</div>

				</div>



				<div class="ulbm-price-breakdown" aria-live="polite">

					<div class="ulbm-price-line ulbm-price-line--nights">

						<span class="ulbm-price-line-label"></span>

						<span class="ulbm-price-line-value"></span>

					</div>

					<div class="ulbm-price-line ulbm-price-line--cleaning<?php echo $ulbm_cleaning_fee <= 0 ? ' d-none' : ''; ?>">

						<span class="ulbm-price-line-label"><?php esc_html_e( 'Cleaning Fee', 'flex-multiple-listing-and-booking-system' ); ?></span>

						<span class="ulbm-price-line-value"></span>

					</div>

					<div class="ulbm-price-line ulbm-price-line--service">

						<span class="ulbm-price-line-label">

							<?php esc_html_e( 'Service Fee', 'flex-multiple-listing-and-booking-system' ); ?>

							<i class="bi bi-info-circle ulbm-fee-info" title="<?php esc_attr_e( 'Platform service fee', 'flex-multiple-listing-and-booking-system' ); ?>" aria-hidden="true"></i>

						</span>

						<span class="ulbm-price-line-value"></span>

					</div>

					<div class="ulbm-price-total">

						<span><?php esc_html_e( 'Total', 'flex-multiple-listing-and-booking-system' ); ?></span>

						<strong class="ulbm-price-total-value"></strong>

					</div>

				</div>



				<input type="hidden" name="start" id="ulbm-start" value="">

				<input type="hidden" name="end" id="ulbm-end" value="">

			<?php else : ?>

				<div class="col-12"><p class="text-uppercase text-muted fw-bold mb-1" style="font-size:.7rem;letter-spacing:.05em;"><i class="bi bi-person me-1"></i><?php esc_html_e( 'Your details', 'flex-multiple-listing-and-booking-system' ); ?></p></div>

				<?php foreach ( $groups['contact'] as $field ) : ?>

					<?php $ulbm_render_field( $field ); ?>

				<?php endforeach; ?>



				<div class="col-12 mt-2"><p class="text-uppercase text-muted fw-bold mb-1" style="font-size:.7rem;letter-spacing:.05em;"><i class="bi bi-calendar3 me-1"></i><?php esc_html_e( 'Schedule', 'flex-multiple-listing-and-booking-system' ); ?></p></div>

				<div class="col-md-6">

					<label class="form-label small fw-semibold" for="ulbm-start"><?php esc_html_e( 'Start', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>

					<input type="datetime-local" class="form-control form-control-sm" name="start" id="ulbm-start" required>

				</div>

				<div class="col-md-6">

					<label class="form-label small fw-semibold" for="ulbm-end"><?php esc_html_e( 'End', 'flex-multiple-listing-and-booking-system' ); ?> <span class="text-danger">*</span></label>

					<input type="datetime-local" class="form-control form-control-sm" name="end" id="ulbm-end" required>

				</div>



				<?php if ( ! empty( $groups['extra'] ) ) : ?>

					<div class="col-12 mt-2"><p class="text-uppercase text-muted fw-bold mb-1" style="font-size:.7rem;letter-spacing:.05em;"><i class="bi bi-list-check me-1"></i><?php echo esc_html( $groups['title'] ?: __( 'Booking details', 'flex-multiple-listing-and-booking-system' ) ); ?></p></div>

					<?php foreach ( $groups['extra'] as $field ) : ?>

						<?php $ulbm_render_field( $field ); ?>

					<?php endforeach; ?>

				<?php endif; ?>

			<?php endif; ?>



			<input type="hidden" name="base_price" value="0">

			<?php if ( $ulbm_listing_id ) : ?>

				<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $ulbm_listing_id ); ?>">

			<?php endif; ?>



			<?php if ( $ulbm_marketplace ) : ?>

				<div class="ulbm-booking-contact-panel">

					<p class="ulbm-contact-panel-title"><?php esc_html_e( 'Your details', 'flex-multiple-listing-and-booking-system' ); ?></p>

					<div class="row g-2">

						<?php foreach ( $groups['contact'] as $field ) : ?>

							<?php $ulbm_render_field( $field, true ); ?>

						<?php endforeach; ?>

						<?php if ( ! empty( $groups['extra'] ) ) : ?>

							<?php foreach ( $groups['extra'] as $field ) : ?>

								<?php
								$fname = isset( $field['name'] ) ? (string) $field['name'] : '';
								// Marketplace already collects guests via .ulbm-mp-guests.
								if ( 'guests_count' === $fname ) {
									continue;
								}
								$ulbm_render_field( $field, true );
								?>

							<?php endforeach; ?>

						<?php endif; ?>

					</div>

				</div>



				<button type="submit" class="btn ulbm-btn-request w-100">

					<?php esc_html_e( 'Request to Book', 'flex-multiple-listing-and-booking-system' ); ?>

				</button>

			<?php else : ?>

				<div class="col-12 mt-3">

					<button type="submit" class="btn btn-primary w-100">

						<i class="bi bi-check-circle me-1"></i><?php esc_html_e( 'Submit Booking', 'flex-multiple-listing-and-booking-system' ); ?>

					</button>

				</div>

			<?php endif; ?>



			<div class="<?php echo $ulbm_marketplace ? '' : 'col-12'; ?>">

				<div class="ulbm-form-feedback d-none mt-2 alert py-2 small" role="alert"></div>

			</div>

		</form>

	</div>

</div>


