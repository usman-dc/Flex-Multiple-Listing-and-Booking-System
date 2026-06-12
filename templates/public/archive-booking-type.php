<?php
/**
 * Archive template for booking type CPTs — marketplace grid layout.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Front\GridDesignRegistry;
use FlexBooking\Front\LayoutSettings;
use FlexBooking\Front\ListingDisplay;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;

defined( 'ABSPATH' ) || exit;

get_header();

$ulbm_post_type = get_query_var( 'post_type' );
$ulbm_type_slug = '';
$ulbm_type_name = '';
$ulbm_all_types = BookingTypePostTypeRegistry::get_registered_types();
$ulbm_matched_type = BookingTypePostTypeRegistry::booking_type_for_post_type( (string) $ulbm_post_type );
if ( $ulbm_matched_type ) {
	$ulbm_type_name = (string) $ulbm_matched_type['name'];
	$ulbm_type_slug = (string) $ulbm_matched_type['slug'];
}

$ulbm_general      = LayoutSettings::get();
$ulbm_show_filters = ! isset( $ulbm_general['show_filters'] ) || ! empty( $ulbm_general['show_filters'] );
$ulbm_grid_columns = LayoutSettings::grid_columns();
$ulbm_col_class    = ListingDisplay::grid_col_class( $ulbm_grid_columns );
$ulbm_per_page     = LayoutSettings::grid_per_page();
$ulbm_uid          = 'ulbm-grid-archive-' . wp_unique_id();

global $wp_query;
$ulbm_total       = (int) $wp_query->found_posts;
$ulbm_showing_end = min( $ulbm_per_page, $ulbm_total );
?>

<div class="ulbm-single-listing-wrap ulbm-marketplace-ui">

	<?php include ULBM_PLUGIN_DIR . 'templates/public/partials/account-toolbar.php'; ?>

	<div class="container ulbm-container">

		<?php $ulbm_grid_style = LayoutSettings::grid_root_style( $ulbm_grid_columns ); ?>
		<?php GridFilterUi::enqueue_critical_styles( $ulbm_uid ); ?>
		<div class="ulbm-listing-grid ulbm-marketplace-ui <?php echo esc_attr( GridDesignRegistry::css_class() ); ?>" id="<?php echo esc_attr( $ulbm_uid ); ?>" style="<?php echo esc_attr( $ulbm_grid_style ); ?>" data-type="<?php echo esc_attr( $ulbm_type_slug ); ?>" data-per-page="<?php echo esc_attr( (string) $ulbm_per_page ); ?>" data-columns="<?php echo esc_attr( (string) $ulbm_grid_columns ); ?>">

			<header class="ulbm-grid-hero">
				<h1 class="ulbm-grid-hero-title"><?php echo esc_html( $ulbm_type_name ?: post_type_archive_title( '', false ) ); ?></h1>
				<p class="ulbm-grid-hero-sub">
					<?php
					printf(
						/* translators: %s: booking type name (lowercase) */
						esc_html__( 'Browse all %s listings and find your perfect match.', 'flex-multiple-listing-and-booking-system' ),
						esc_html( strtolower( $ulbm_type_name ) )
					);
					?>
				</p>
			</header>

			<?php if ( $ulbm_show_filters ) : ?>
				<?php
				GridFilterUi::render_panel(
					$ulbm_uid,
					array(
						'type'      => $ulbm_type_slug,
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
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<?php ListingDisplay::render_grid_card( get_the_ID(), $ulbm_col_class ); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<div class="col-12"><p class="text-muted text-center py-5"><?php esc_html_e( 'No listings found.', 'flex-multiple-listing-and-booking-system' ); ?></p></div>
				<?php endif; ?>
			</div>

			<?php if ( $ulbm_show_filters && $wp_query->max_num_pages > 1 ) : ?>
				<nav class="ulbm-grid-pagination mt-4" data-pages="<?php echo esc_attr( (string) $wp_query->max_num_pages ); ?>" aria-label="<?php esc_attr_e( 'Pagination', 'flex-multiple-listing-and-booking-system' ); ?>">
					<ul class="pagination justify-content-center mb-0">
						<li class="page-item"><button class="page-link ulbm-grid-prev" disabled aria-label="<?php esc_attr_e( 'Previous', 'flex-multiple-listing-and-booking-system' ); ?>">&laquo;</button></li>
						<li class="page-item active"><span class="page-link ulbm-grid-page-info">1</span></li>
						<li class="page-item"><button class="page-link ulbm-grid-next" aria-label="<?php esc_attr_e( 'Next', 'flex-multiple-listing-and-booking-system' ); ?>">&raquo;</button></li>
					</ul>
				</nav>
			<?php elseif ( ! $ulbm_show_filters && $wp_query->max_num_pages > 1 ) : ?>
				<div class="mt-4">
					<?php
					the_posts_pagination( array(
						'mid_size'  => 2,
						'prev_text' => '&laquo; ' . __( 'Previous', 'flex-multiple-listing-and-booking-system' ),
						'next_text' => __( 'Next', 'flex-multiple-listing-and-booking-system' ) . ' &raquo;',
					) );
					?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>

<?php
get_footer();
