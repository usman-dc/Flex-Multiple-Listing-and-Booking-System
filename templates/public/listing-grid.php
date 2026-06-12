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



use FlexBooking\Front\GridFilterUi;
use FlexBooking\Front\GridDesignRegistry;
use FlexBooking\Front\LayoutSettings;
use FlexBooking\Front\ListingDisplay;

use FlexBooking\Listings\ListingMeta;

use FlexBooking\PostTypes\BookingTypePostTypeRegistry;



defined( 'ABSPATH' ) || exit;



$ulbm_grid_type    = isset( $ulbm_grid_type ) ? (string) $ulbm_grid_type : '';

$ulbm_grid_columns = isset( $ulbm_grid_columns ) ? (int) $ulbm_grid_columns : LayoutSettings::grid_columns();
$ulbm_grid_limit   = isset( $ulbm_grid_limit ) ? (int) $ulbm_grid_limit : LayoutSettings::grid_per_page();



$ulbm_all_types = BookingTypePostTypeRegistry::get_registered_types();



$ulbm_query_post_types = array();

if ( $ulbm_grid_type ) {
	foreach ( $ulbm_all_types as $ulbm_bt ) {
		if ( (string) $ulbm_bt['slug'] === $ulbm_grid_type ) {
			$ulbm_query_post_types[] = BookingTypePostTypeRegistry::cpt_name_from_slug( $ulbm_bt['slug'] );
			$ulbm_query_post_types[] = BookingTypePostTypeRegistry::legacy_cpt_name_from_slug( $ulbm_bt['slug'] );
			break;
		}
	}
} else {
	$ulbm_query_post_types = BookingTypePostTypeRegistry::listing_post_types_for_query();
}
$ulbm_query_post_types = array_values( array_unique( $ulbm_query_post_types ) );



if ( empty( $ulbm_query_post_types ) ) {

	echo '<p class="text-muted">' . esc_html__( 'No listing types configured yet.', 'flex-multiple-listing-and-booking-system' ) . '</p>';

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



$ulbm_general      = json_decode( (string) get_option( 'ulbm_general_settings', '{}' ), true );

$ulbm_show_filters = ! isset( $ulbm_general['show_filters'] ) || ! empty( $ulbm_general['show_filters'] );

$ulbm_col_class    = ListingDisplay::grid_col_class( $ulbm_grid_columns );

$ulbm_uid          = 'ulbm-grid-' . wp_unique_id();

$ulbm_total        = (int) $ulbm_grid_query->found_posts;

$ulbm_showing_end  = min( $ulbm_grid_limit, $ulbm_total );

$ulbm_grid_spacing = isset( $ulbm_grid_spacing ) && is_array( $ulbm_grid_spacing ) ? $ulbm_grid_spacing : array();
$ulbm_grid_style   = LayoutSettings::grid_root_style( $ulbm_grid_columns, $ulbm_grid_spacing );

?>
<?php GridFilterUi::enqueue_critical_styles( $ulbm_uid ); ?>
<div class="ulbm-listing-grid ulbm-marketplace-ui <?php echo esc_attr( GridDesignRegistry::css_class( $ulbm_grid_design ?? null ) ); ?>" id="<?php echo esc_attr( $ulbm_uid ); ?>" style="<?php echo esc_attr( $ulbm_grid_style ); ?>" data-type="<?php echo esc_attr( $ulbm_grid_type ); ?>" data-per-page="<?php echo esc_attr( (string) $ulbm_grid_limit ); ?>" data-columns="<?php echo esc_attr( (string) $ulbm_grid_columns ); ?>">

	<?php if ( $ulbm_show_filters ) : ?>
		<?php
		GridFilterUi::render_panel(
			$ulbm_uid,
			array(
				'type'      => $ulbm_grid_type,
				'all_types' => $ulbm_all_types,
			)
		);
		?>
	<?php endif; ?>



	<div class="ulbm-grid-toolbar">

		<span class="ulbm-grid-count small text-muted">

			<?php

			if ( $ulbm_total > 0 ) {

				printf(
					/* translators: 1: number shown on page, 2: total listings */
					esc_html__( 'Showing 1–%1$d of %2$d properties', 'flex-multiple-listing-and-booking-system' ),
					(int) $ulbm_showing_end,
					(int) $ulbm_total
				);

			} else {

				esc_html_e( 'No properties found', 'flex-multiple-listing-and-booking-system' );

			}

			?>

		</span>

		<div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">

			<?php ListingDisplay::render_view_toggle( $ulbm_uid ); ?>

			<span class="small text-muted mb-0"><?php esc_html_e( 'Sort by:', 'flex-multiple-listing-and-booking-system' ); ?></span>

			<?php ListingDisplay::render_sort_toggle( $ulbm_uid ); ?>

			<span class="spinner-border spinner-border-sm text-primary d-none ulbm-grid-spinner" role="status"></span>

		</div>

	</div>



	<div class="ulbm-grid-results ulbm-view-grid">

		<?php if ( $ulbm_grid_query->have_posts() ) : ?>

			<?php

			while ( $ulbm_grid_query->have_posts() ) :

				$ulbm_grid_query->the_post();

				ListingDisplay::render_grid_card( get_the_ID(), $ulbm_col_class );

			endwhile;

			?>

		<?php else : ?>

			<div class="col-12"><p class="text-muted text-center py-5"><?php esc_html_e( 'No listings found.', 'flex-multiple-listing-and-booking-system' ); ?></p></div>

		<?php endif; ?>

	</div>



	<?php if ( $ulbm_grid_query->max_num_pages > 1 ) : ?>

		<nav class="ulbm-grid-pagination mt-4" data-pages="<?php echo esc_attr( (string) $ulbm_grid_query->max_num_pages ); ?>" aria-label="<?php esc_attr_e( 'Pagination', 'flex-multiple-listing-and-booking-system' ); ?>">

			<ul class="pagination justify-content-center mb-0">

				<li class="page-item"><button class="page-link ulbm-grid-prev" disabled aria-label="<?php esc_attr_e( 'Previous', 'flex-multiple-listing-and-booking-system' ); ?>">&laquo;</button></li>

				<li class="page-item active"><span class="page-link ulbm-grid-page-info">1</span></li>

				<li class="page-item"><button class="page-link ulbm-grid-next" aria-label="<?php esc_attr_e( 'Next', 'flex-multiple-listing-and-booking-system' ); ?>">&raquo;</button></li>

			</ul>

		</nav>

	<?php endif; ?>

</div>



<?php wp_reset_postdata(); ?>

