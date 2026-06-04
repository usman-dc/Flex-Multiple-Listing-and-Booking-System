<?php

/**

 * Listing grid with AJAX filters — marketplace layout.

 *

 * @package FlexBookingSystem

 *

 * @var string $ulbm_grid_type    Booking type slug (maps to CPT). Empty = all.

 * @var int    $ulbm_grid_columns Columns count (1-6).

 * @var int    $ulbm_grid_limit   Max posts per page.

 */



use FlexBooking\Front\LayoutSettings;
use FlexBooking\Front\ListingDisplay;

use FlexBooking\Listings\ListingMeta;

use FlexBooking\PostTypes\BookingTypePostTypeRegistry;



defined( 'ABSPATH' ) || exit;



$ulbm_grid_type    = isset( $ulbm_grid_type ) ? (string) $ulbm_grid_type : '';

$ulbm_grid_columns = isset( $ulbm_grid_columns ) ? (int) $ulbm_grid_columns : 3;

$ulbm_grid_limit   = isset( $ulbm_grid_limit ) ? (int) $ulbm_grid_limit : 12;



$ulbm_all_types = BookingTypePostTypeRegistry::get_registered_types();



$ulbm_query_post_types = array();

if ( $ulbm_grid_type ) {
	foreach ( $ulbm_all_types as $bt ) {
		if ( (string) $bt['slug'] === $ulbm_grid_type ) {
			$ulbm_query_post_types[] = BookingTypePostTypeRegistry::cpt_name_from_slug( $bt['slug'] );
			$ulbm_query_post_types[] = BookingTypePostTypeRegistry::legacy_cpt_name_from_slug( $bt['slug'] );
			break;
		}
	}
} else {
	$ulbm_query_post_types = BookingTypePostTypeRegistry::listing_post_types_for_query();
}
$ulbm_query_post_types = array_values( array_unique( $ulbm_query_post_types ) );



if ( empty( $ulbm_query_post_types ) ) {

	echo '<p class="text-muted">' . esc_html__( 'No listing types configured yet.', 'flex-booking-system' ) . '</p>';

	return;

}



$ulbm_grid_query = new WP_Query(

	array(

		'post_type'      => $ulbm_query_post_types,

		'posts_per_page' => $ulbm_grid_limit,

		'post_status'    => 'publish',

		'orderby'        => 'date',

		'order'          => 'DESC',

	)

);



$general      = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );

$show_filters = ! isset( $general['show_filters'] ) || ! empty( $general['show_filters'] );

$col_class    = ListingDisplay::grid_col_class( $ulbm_grid_columns );

$uid          = 'ulbm-grid-' . wp_unique_id();

$total        = (int) $ulbm_grid_query->found_posts;

$showing_end  = min( $ulbm_grid_limit, $total );

$ulbm_grid_spacing = isset( $ulbm_grid_spacing ) && is_array( $ulbm_grid_spacing ) ? $ulbm_grid_spacing : array();
$grid_style       = LayoutSettings::grid_inline_style( $ulbm_grid_spacing );

?>
<div class="ulbm-listing-grid ulbm-marketplace-ui" id="<?php echo esc_attr( $uid ); ?>" style="<?php echo esc_attr( $grid_style ); ?>" data-type="<?php echo esc_attr( $ulbm_grid_type ); ?>" data-per-page="<?php echo esc_attr( (string) $ulbm_grid_limit ); ?>">



	<header class="ulbm-grid-hero">

		<h2 class="ulbm-grid-hero-title"><?php esc_html_e( 'Find Your Perfect Stay', 'flex-booking-system' ); ?></h2>

		<p class="ulbm-grid-hero-sub"><?php esc_html_e( 'Search by location, price, and guests to discover available listings.', 'flex-booking-system' ); ?></p>

	</header>



	<?php if ( $show_filters ) : ?>

	<div class="ulbm-grid-filters ulbm-filter-panel">

		<div class="row g-3 align-items-end">

			<div class="col-lg-5">

				<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Keyword / Location', 'flex-booking-system' ); ?></label>

				<div class="input-group">

					<span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>

					<input type="text" class="form-control ulbm-filter-keyword" placeholder="<?php esc_attr_e( 'Search by location or property name…', 'flex-booking-system' ); ?>">

				</div>

			</div>

			<?php if ( ! $ulbm_grid_type && count( $ulbm_all_types ) > 1 ) : ?>

				<div class="col-md-6 col-lg-2">

					<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Type', 'flex-booking-system' ); ?></label>

					<select class="form-select ulbm-filter-type">

						<option value=""><?php esc_html_e( 'All types', 'flex-booking-system' ); ?></option>

						<?php foreach ( $ulbm_all_types as $ft ) : ?>

							<option value="<?php echo esc_attr( (string) $ft['slug'] ); ?>"><?php echo esc_html( (string) $ft['name'] ); ?></option>

						<?php endforeach; ?>

					</select>

				</div>

			<?php endif; ?>

			<div class="col-6 col-md-3 col-lg-2">

				<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Min price', 'flex-booking-system' ); ?></label>

				<input type="number" class="form-control ulbm-filter-min-price" placeholder="0" min="0" step="1">

			</div>

			<div class="col-6 col-md-3 col-lg-2">

				<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Max price', 'flex-booking-system' ); ?></label>

				<input type="number" class="form-control ulbm-filter-max-price" placeholder="<?php esc_attr_e( 'Any', 'flex-booking-system' ); ?>" min="0" step="1">

			</div>

			<div class="col-6 col-md-3 col-lg-1">

				<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Guests', 'flex-booking-system' ); ?></label>

				<input type="number" class="form-control ulbm-filter-guests" placeholder="<?php esc_attr_e( 'Any', 'flex-booking-system' ); ?>" min="0">

			</div>

			<div class="col-6 col-md-4 col-lg-2 d-grid">

				<button type="button" class="btn btn-primary ulbm-filter-submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i><?php esc_html_e( 'Show Results', 'flex-booking-system' ); ?></button>

			</div>

		</div>

		<input type="hidden" class="ulbm-filter-sort" value="date">

		<button type="button" class="d-none ulbm-filter-reset" aria-hidden="true"></button>

	</div>

	<?php endif; ?>



	<div class="ulbm-grid-toolbar">

		<span class="ulbm-grid-count small text-muted">

			<?php

			if ( $total > 0 ) {

				printf(
					/* translators: 1: number shown on page, 2: total listings */
					esc_html__( 'Showing 1–%1$d of %2$d properties', 'flex-booking-system' ),
					(int) $showing_end,
					(int) $total
				);

			} else {

				esc_html_e( 'No properties found', 'flex-booking-system' );

			}

			?>

		</span>

		<div class="d-flex align-items-center gap-2">

			<label class="small text-muted mb-0" for="<?php echo esc_attr( $uid ); ?>-sort"><?php esc_html_e( 'Sort by:', 'flex-booking-system' ); ?></label>

			<select class="form-select form-select-sm ulbm-filter-sort-select" id="<?php echo esc_attr( $uid ); ?>-sort" style="width:auto;">

				<option value="date"><?php esc_html_e( 'Newest', 'flex-booking-system' ); ?></option>

				<option value="price_asc"><?php esc_html_e( 'Price: low to high', 'flex-booking-system' ); ?></option>

				<option value="price_desc"><?php esc_html_e( 'Price: high to low', 'flex-booking-system' ); ?></option>

				<option value="title"><?php esc_html_e( 'Name A–Z', 'flex-booking-system' ); ?></option>

			</select>

			<span class="spinner-border spinner-border-sm text-primary d-none ulbm-grid-spinner" role="status"></span>

		</div>

	</div>



	<div class="ulbm-grid-results row">

		<?php if ( $ulbm_grid_query->have_posts() ) : ?>

			<?php

			while ( $ulbm_grid_query->have_posts() ) :

				$ulbm_grid_query->the_post();

				ListingDisplay::render_grid_card( get_the_ID(), $col_class );

			endwhile;

			?>

		<?php else : ?>

			<div class="col-12"><p class="text-muted text-center py-5"><?php esc_html_e( 'No listings found.', 'flex-booking-system' ); ?></p></div>

		<?php endif; ?>

	</div>



	<?php if ( $ulbm_grid_query->max_num_pages > 1 ) : ?>

		<nav class="ulbm-grid-pagination mt-4" data-pages="<?php echo esc_attr( (string) $ulbm_grid_query->max_num_pages ); ?>" aria-label="<?php esc_attr_e( 'Pagination', 'flex-booking-system' ); ?>">

			<ul class="pagination justify-content-center mb-0">

				<li class="page-item"><button class="page-link ulbm-grid-prev" disabled aria-label="<?php esc_attr_e( 'Previous', 'flex-booking-system' ); ?>">&laquo;</button></li>

				<li class="page-item active"><span class="page-link ulbm-grid-page-info">1</span></li>

				<li class="page-item"><button class="page-link ulbm-grid-next" aria-label="<?php esc_attr_e( 'Next', 'flex-booking-system' ); ?>">&raquo;</button></li>

			</ul>

		</nav>

	<?php endif; ?>

</div>



<?php wp_reset_postdata(); ?>

