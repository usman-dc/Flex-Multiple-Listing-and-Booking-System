<?php

/**

 * Public booking form — contact, schedule, industry-specific questions.

 *

 * @package FlexBookingSystem

 *

 * @var array<string, mixed>              $atts              Shortcode attributes.

 * @var array<string, mixed>|null         $fbs_booking_type  Loaded type row or null.

 * @var array{industry: string, contact: array, extra: array, title: string} $fbs_form_groups Field definitions.

 * @var array<string, string>             $fbs_prefill       Default values for contact fields.

 */



use FlexBooking\Front\PriceFormatter;

use FlexBooking\Listings\ListingMeta;



defined( 'ABSPATH' ) || exit;



$type    = isset( $atts['type'] ) ? sanitize_title( $atts['type'] ) : '';

$id      = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;

$groups  = isset( $fbs_form_groups ) && is_array( $fbs_form_groups ) ? $fbs_form_groups : array(

	'industry' => 'generic',

	'contact'  => array(),

	'extra'    => array(),

	'title'    => '',

);

$prefill = isset( $fbs_prefill ) && is_array( $fbs_prefill ) ? $fbs_prefill : array();



$fbs_type_name = '';

if ( $fbs_booking_type && is_array( $fbs_booking_type ) && ! empty( $fbs_booking_type['name'] ) ) {

	$fbs_type_name = (string) $fbs_booking_type['name'];

}



$fbs_render_field = static function ( array $f, $compact = false ) use ( $prefill ) {

	$name     = isset( $f['name'] ) ? (string) $f['name'] : '';

	$label    = isset( $f['label'] ) ? (string) $f['label'] : '';

	$type_in  = isset( $f['type'] ) ? (string) $f['type'] : 'text';

	$required = ! empty( $f['required'] );

	$col      = isset( $f['col'] ) ? (string) $f['col'] : 'col-12';

	$ph       = isset( $f['placeholder'] ) ? (string) $f['placeholder'] : '';

	$val      = $prefill[ $name ] ?? '';



	$req = $required ? ' required' : '';

	$id_attr = 'fbs-f-' . sanitize_key( $name );

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

				<option value=""><?php esc_html_e( '— Select —', 'flex-booking-system' ); ?></option>

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



$fbs_listing_id  = isset( $fbs_listing_id ) ? (int) $fbs_listing_id : 0;
if ( ! $fbs_listing_id && is_singular() ) {
	$pt = get_post_type();
	if ( $pt && 0 === strpos( (string) $pt, 'fbs_' ) && 'fbs_listing' !== $pt ) {
		$fbs_listing_id = get_the_ID();
	}
}
$fbs_marketplace = is_singular() && $fbs_listing_id > 0;
$fbs_is_embedded = $fbs_marketplace || ( is_singular() && has_post_thumbnail() );



$fbs_nightly_price  = 0.0;

$fbs_cleaning_fee   = 0.0;

$fbs_service_fee    = 0.0;

$fbs_max_guests     = 1;

$fbs_price_suffix   = '/night';

$fbs_check_in_time  = '14:00';

$fbs_check_out_time = '11:00';



if ( $fbs_marketplace ) {

	$base = ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_BASE_PRICE, 'string' );

	$sale = ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_SALE_PRICE, 'string' );

	$fbs_nightly_price  = (float) ( $sale ?: $base );

	$fbs_max_guests     = max( 1, ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_MAX_GUESTS, 'int' ) );

	$fbs_price_suffix   = PriceFormatter::normalize_suffix( ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_PRICE_SUFFIX, 'string' ) ?: '/night' );

	$fbs_check_in_time  = ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_CHECK_IN_TIME, 'string' ) ?: '14:00';

	$fbs_check_out_time = ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_CHECK_OUT_TIME, 'string' ) ?: '11:00';

	$extra_svc          = ListingMeta::get( $fbs_listing_id, ListingMeta::KEY_EXTRA_SERVICES, 'array' );

	foreach ( $extra_svc as $svc ) {

		if ( ! is_array( $svc ) ) {

			continue;

		}

		$name = strtolower( (string) ( $svc['name'] ?? '' ) );

		$fee  = (float) ( $svc['price'] ?? 0 );

		if ( false !== strpos( $name, 'clean' ) ) {

			$fbs_cleaning_fee = $fee;

		} elseif ( false !== strpos( $name, 'service' ) ) {

			$fbs_service_fee = $fee;

		}

	}

}



$fbs_currency = PriceFormatter::currency_code();

?>

<div

	class="fbs-booking-form <?php echo $fbs_marketplace ? 'fbs-booking-form--marketplace' : ( $fbs_is_embedded ? '' : 'card border-0 shadow-sm' ); ?> w-100"

	data-fbs-type="<?php echo esc_attr( $type ); ?>"

	data-fbs-type-id="<?php echo esc_attr( (string) $id ); ?>"

	data-fbs-listing-id="<?php echo esc_attr( (string) $fbs_listing_id ); ?>"

	data-fbs-industry="<?php echo esc_attr( (string) $groups['industry'] ); ?>"

	<?php if ( $fbs_marketplace ) : ?>

	data-fbs-nightly="<?php echo esc_attr( (string) $fbs_nightly_price ); ?>"

	data-fbs-cleaning="<?php echo esc_attr( (string) $fbs_cleaning_fee ); ?>"

	data-fbs-service="<?php echo esc_attr( (string) $fbs_service_fee ); ?>"

	data-fbs-currency="<?php echo esc_attr( $fbs_currency ); ?>"

	data-fbs-price-suffix="<?php echo esc_attr( $fbs_price_suffix ); ?>"

	data-fbs-check-in-time="<?php echo esc_attr( $fbs_check_in_time ); ?>"

	data-fbs-check-out-time="<?php echo esc_attr( $fbs_check_out_time ); ?>"

	data-fbs-max-guests="<?php echo esc_attr( (string) $fbs_max_guests ); ?>"

	<?php endif; ?>

>

	<div class="<?php echo ( $fbs_marketplace || $fbs_is_embedded ) ? '' : 'card-body p-4'; ?>">

		<?php if ( ! $fbs_marketplace && ! $fbs_is_embedded ) : ?>

			<h2 class="h5 mb-1"><i class="bi bi-calendar-check me-1"></i><?php esc_html_e( 'Book Now', 'flex-booking-system' ); ?></h2>

			<?php if ( $fbs_type_name ) : ?>

				<p class="text-muted small mb-3"><?php echo esc_html( $fbs_type_name ); ?></p>

			<?php endif; ?>

		<?php endif; ?>



		<?php if ( $fbs_type_name && $fbs_is_embedded && ! $fbs_marketplace ) : ?>

			<div class="alert alert-light border py-2 px-3 mb-3 small">

				<i class="bi bi-tag me-1 text-primary"></i>

				<strong><?php esc_html_e( 'Booking type:', 'flex-booking-system' ); ?></strong> <?php echo esc_html( $fbs_type_name ); ?>

			</div>

		<?php endif; ?>



		<form class="<?php echo $fbs_marketplace ? 'fbs-marketplace-form' : 'row g-2'; ?>" id="fbs-booking-form-fields" novalidate>



			<?php if ( $fbs_marketplace ) : ?>

				<div class="fbs-mp-fields">

					<div class="fbs-mp-field">

						<label class="fbs-mp-label" for="fbs-checkin"><?php esc_html_e( 'Check-in', 'flex-booking-system' ); ?></label>

						<div class="fbs-mp-input-wrap">

							<i class="bi bi-calendar3" aria-hidden="true"></i>

							<input type="date" class="fbs-mp-input fbs-mp-checkin" id="fbs-checkin" name="fbs_checkin" required>

						</div>

					</div>

					<div class="fbs-mp-field">

						<label class="fbs-mp-label" for="fbs-checkout"><?php esc_html_e( 'Check-out', 'flex-booking-system' ); ?></label>

						<div class="fbs-mp-input-wrap">

							<i class="bi bi-calendar3" aria-hidden="true"></i>

							<input type="date" class="fbs-mp-input fbs-mp-checkout" id="fbs-checkout" name="fbs_checkout" required>

						</div>

					</div>

					<div class="fbs-mp-field">

						<label class="fbs-mp-label" for="fbs-guests"><?php esc_html_e( 'Guests', 'flex-booking-system' ); ?></label>

						<div class="fbs-mp-input-wrap">

							<i class="bi bi-people" aria-hidden="true"></i>

							<select class="fbs-mp-input fbs-mp-guests" id="fbs-guests" name="guests_count">

								<?php for ( $g = 1; $g <= $fbs_max_guests; $g++ ) : ?>

									<option value="<?php echo esc_attr( (string) $g ); ?>"<?php selected( 2, $g ); ?>>

										<?php
										printf(
											/* translators: %d: guest count */
											esc_html( _n( '%d Guest', '%d Guests', $g, 'flex-booking-system' ) ),
											(int) $g
										);
										?>

									</option>

								<?php endfor; ?>

							</select>

						</div>

					</div>

				</div>



				<div class="fbs-price-breakdown" aria-live="polite">

					<div class="fbs-price-line fbs-price-line--nights">

						<span class="fbs-price-line-label"></span>

						<span class="fbs-price-line-value"></span>

					</div>

					<div class="fbs-price-line fbs-price-line--cleaning<?php echo $fbs_cleaning_fee <= 0 ? ' d-none' : ''; ?>">

						<span class="fbs-price-line-label"><?php esc_html_e( 'Cleaning Fee', 'flex-booking-system' ); ?></span>

						<span class="fbs-price-line-value"></span>

					</div>

					<div class="fbs-price-line fbs-price-line--service">

						<span class="fbs-price-line-label">

							<?php esc_html_e( 'Service Fee', 'flex-booking-system' ); ?>

							<i class="bi bi-info-circle fbs-fee-info" title="<?php esc_attr_e( 'Platform service fee', 'flex-booking-system' ); ?>" aria-hidden="true"></i>

						</span>

						<span class="fbs-price-line-value"></span>

					</div>

					<div class="fbs-price-total">

						<span><?php esc_html_e( 'Total', 'flex-booking-system' ); ?></span>

						<strong class="fbs-price-total-value"></strong>

					</div>

				</div>



				<input type="hidden" name="start" id="fbs-start" value="">

				<input type="hidden" name="end" id="fbs-end" value="">

			<?php else : ?>

				<div class="col-12"><p class="text-uppercase text-muted fw-bold mb-1" style="font-size:.7rem;letter-spacing:.05em;"><i class="bi bi-person me-1"></i><?php esc_html_e( 'Your details', 'flex-booking-system' ); ?></p></div>

				<?php foreach ( $groups['contact'] as $field ) : ?>

					<?php $fbs_render_field( $field ); ?>

				<?php endforeach; ?>



				<div class="col-12 mt-2"><p class="text-uppercase text-muted fw-bold mb-1" style="font-size:.7rem;letter-spacing:.05em;"><i class="bi bi-calendar3 me-1"></i><?php esc_html_e( 'Schedule', 'flex-booking-system' ); ?></p></div>

				<div class="col-md-6">

					<label class="form-label small fw-semibold" for="fbs-start"><?php esc_html_e( 'Start', 'flex-booking-system' ); ?> <span class="text-danger">*</span></label>

					<input type="datetime-local" class="form-control form-control-sm" name="start" id="fbs-start" required>

				</div>

				<div class="col-md-6">

					<label class="form-label small fw-semibold" for="fbs-end"><?php esc_html_e( 'End', 'flex-booking-system' ); ?> <span class="text-danger">*</span></label>

					<input type="datetime-local" class="form-control form-control-sm" name="end" id="fbs-end" required>

				</div>



				<?php if ( ! empty( $groups['extra'] ) ) : ?>

					<div class="col-12 mt-2"><p class="text-uppercase text-muted fw-bold mb-1" style="font-size:.7rem;letter-spacing:.05em;"><i class="bi bi-list-check me-1"></i><?php echo esc_html( $groups['title'] ?: __( 'Booking details', 'flex-booking-system' ) ); ?></p></div>

					<?php foreach ( $groups['extra'] as $field ) : ?>

						<?php $fbs_render_field( $field ); ?>

					<?php endforeach; ?>

				<?php endif; ?>

			<?php endif; ?>



			<input type="hidden" name="base_price" value="0">

			<?php if ( $fbs_listing_id ) : ?>

				<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $fbs_listing_id ); ?>">

			<?php endif; ?>



			<?php if ( $fbs_marketplace ) : ?>

				<div class="fbs-booking-contact-panel d-none">

					<p class="fbs-contact-panel-title"><?php esc_html_e( 'Your details', 'flex-booking-system' ); ?></p>

					<div class="row g-2">

						<?php foreach ( $groups['contact'] as $field ) : ?>

							<?php $fbs_render_field( $field, true ); ?>

						<?php endforeach; ?>

						<?php if ( ! empty( $groups['extra'] ) ) : ?>

							<?php foreach ( $groups['extra'] as $field ) : ?>

								<?php $fbs_render_field( $field, true ); ?>

							<?php endforeach; ?>

						<?php endif; ?>

					</div>

				</div>



				<button type="submit" class="btn fbs-btn-request w-100">

					<?php esc_html_e( 'Request to Book', 'flex-booking-system' ); ?>

				</button>

			<?php else : ?>

				<div class="col-12 mt-3">

					<button type="submit" class="btn btn-primary w-100">

						<i class="bi bi-check-circle me-1"></i><?php esc_html_e( 'Submit Booking', 'flex-booking-system' ); ?>

					</button>

				</div>

			<?php endif; ?>



			<div class="<?php echo $fbs_marketplace ? '' : 'col-12'; ?>">

				<div class="fbs-form-feedback d-none mt-2 alert py-2 small" role="alert"></div>

			</div>

		</form>

	</div>

</div>


