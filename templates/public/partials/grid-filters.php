<?php
/**
 * Listing grid AJAX filter fields.
 *
 * @package FlexBookingSystem
 *
 * @var string $grid_id
 * @var string $panel_id
 * @var string $type
 * @var array  $all_types
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ulbm-grid-filters ulbm-filter-panel" id="<?php echo esc_attr( $panel_id ); ?>">
	<div class="ulbm-filter-grid">
		<div class="ulbm-filter-item ulbm-filter-item--keyword">
			<label class="ulbm-filter-label" for="<?php echo esc_attr( $grid_id ); ?>-keyword"><?php esc_html_e( 'Keyword / Location', 'flex-multiple-listing-and-booking-system' ); ?></label>
			<div class="ulbm-filter-box ulbm-filter-box--search">
				<span class="ulbm-filter-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
				<input type="text" class="ulbm-fctl ulbm-fctl--search ulbm-filter-keyword" id="<?php echo esc_attr( $grid_id ); ?>-keyword" placeholder="<?php esc_attr_e( 'Search by location or property name…', 'flex-multiple-listing-and-booking-system' ); ?>" autocomplete="off">
			</div>
		</div>

		<?php if ( ! $type && count( $all_types ) > 1 ) : ?>
			<div class="ulbm-filter-item">
				<label class="ulbm-filter-label" for="<?php echo esc_attr( $grid_id ); ?>-type"><?php esc_html_e( 'Type', 'flex-multiple-listing-and-booking-system' ); ?></label>
				<div class="ulbm-filter-box">
					<select class="ulbm-fctl ulbm-filter-type" id="<?php echo esc_attr( $grid_id ); ?>-type">
						<option value=""><?php esc_html_e( 'All types', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<?php foreach ( $all_types as $ulbm_ft ) : ?>
							<option value="<?php echo esc_attr( (string) $ulbm_ft['slug'] ); ?>"><?php echo esc_html( (string) $ulbm_ft['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		<?php endif; ?>

		<div class="ulbm-filter-item">
			<label class="ulbm-filter-label" for="<?php echo esc_attr( $grid_id ); ?>-min-price"><?php esc_html_e( 'Min price', 'flex-multiple-listing-and-booking-system' ); ?></label>
			<div class="ulbm-filter-box">
				<input type="text" class="ulbm-fctl ulbm-filter-min-price" id="<?php echo esc_attr( $grid_id ); ?>-min-price" placeholder="0" autocomplete="off">
			</div>
		</div>

		<div class="ulbm-filter-item">
			<label class="ulbm-filter-label" for="<?php echo esc_attr( $grid_id ); ?>-max-price"><?php esc_html_e( 'Max price', 'flex-multiple-listing-and-booking-system' ); ?></label>
			<div class="ulbm-filter-box">
				<input type="text" class="ulbm-fctl ulbm-filter-max-price" id="<?php echo esc_attr( $grid_id ); ?>-max-price" placeholder="<?php esc_attr_e( 'Any', 'flex-multiple-listing-and-booking-system' ); ?>" autocomplete="off">
			</div>
		</div>

		<div class="ulbm-filter-item">
			<label class="ulbm-filter-label" for="<?php echo esc_attr( $grid_id ); ?>-guests"><?php esc_html_e( 'Guests', 'flex-multiple-listing-and-booking-system' ); ?></label>
			<div class="ulbm-filter-box">
				<input type="text" class="ulbm-fctl ulbm-filter-guests" id="<?php echo esc_attr( $grid_id ); ?>-guests" placeholder="<?php esc_attr_e( 'Any', 'flex-multiple-listing-and-booking-system' ); ?>" autocomplete="off">
			</div>
		</div>

		<div class="ulbm-filter-item ulbm-filter-item--submit">
			<button type="button" class="btn btn-primary ulbm-filter-submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i><?php esc_html_e( 'Show Results', 'flex-multiple-listing-and-booking-system' ); ?></button>
		</div>
	</div>

	<input type="hidden" class="ulbm-filter-sort" value="date">
	<button type="button" class="d-none ulbm-filter-reset" aria-hidden="true"></button>
</div>
