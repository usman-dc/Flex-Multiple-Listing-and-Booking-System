<?php
/**
 * Archive template for booking type CPTs — marketplace grid layout.
 *
 * @package FlexBookingSystem
 */

use FlexBooking\Front\LayoutSettings;
use FlexBooking\Front\ListingDisplay;
use FlexBooking\PostTypes\BookingTypePostTypeRegistry;

defined( 'ABSPATH' ) || exit;

get_header();

$post_type = get_query_var( 'post_type' );
$type_slug = '';
$type_name = '';
$all_types = BookingTypePostTypeRegistry::get_registered_types();
foreach ( $all_types as $bt ) {
	if ( BookingTypePostTypeRegistry::cpt_name_from_slug( $bt['slug'] ) === $post_type ) {
		$type_name = (string) $bt['name'];
		$type_slug = (string) $bt['slug'];
		break;
	}
}

$general      = json_decode( (string) get_option( 'fbs_general_settings', '{}' ), true );
$show_filters = ! isset( $general['show_filters'] ) || ! empty( $general['show_filters'] );
$col_class    = ListingDisplay::grid_col_class( (int) ( $general['grid_columns'] ?? 3 ) );
$per_page     = (int) get_option( 'posts_per_page', 12 );
$uid          = 'fbs-grid-archive-' . wp_unique_id();

global $wp_query;
$total       = (int) $wp_query->found_posts;
$showing_end = min( $per_page, $total );
?>

<div class="fbs-single-listing-wrap fbs-marketplace-ui">

	<?php include FBS_PLUGIN_DIR . 'templates/public/partials/account-toolbar.php'; ?>

	<div class="container fbs-container">

		<?php $grid_style = LayoutSettings::grid_inline_style(); ?>
		<div class="fbs-listing-grid fbs-marketplace-ui" id="<?php echo esc_attr( $uid ); ?>" style="<?php echo esc_attr( $grid_style ); ?>" data-type="<?php echo esc_attr( $type_slug ); ?>" data-per-page="<?php echo esc_attr( (string) $per_page ); ?>">

			<header class="fbs-grid-hero">
				<h1 class="fbs-grid-hero-title"><?php echo esc_html( $type_name ?: post_type_archive_title( '', false ) ); ?></h1>
				<p class="fbs-grid-hero-sub">
					<?php
					printf(
						/* translators: %s: booking type name (lowercase) */
						esc_html__( 'Browse all %s listings and find your perfect match.', 'flex-multiple-listing-and-booking-system' ),
						esc_html( strtolower( $type_name ) )
					);
					?>
				</p>
			</header>

			<?php if ( $show_filters ) : ?>
			<div class="fbs-grid-filters fbs-filter-panel">
				<div class="row g-3 align-items-end">
					<div class="col-lg-5">
						<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Keyword / Location', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<div class="input-group">
							<span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
							<input type="text" class="form-control fbs-filter-keyword" placeholder="<?php esc_attr_e( 'Search by location or property name…', 'flex-multiple-listing-and-booking-system' ); ?>">
						</div>
					</div>
					<div class="col-6 col-md-3 col-lg-2">
						<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Min price', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="number" class="form-control fbs-filter-min-price" placeholder="0" min="0" step="1">
					</div>
					<div class="col-6 col-md-3 col-lg-2">
						<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Max price', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="number" class="form-control fbs-filter-max-price" placeholder="<?php esc_attr_e( 'Any', 'flex-multiple-listing-and-booking-system' ); ?>" min="0" step="1">
					</div>
					<div class="col-6 col-md-3 col-lg-1">
						<label class="form-label small fw-semibold mb-1"><?php esc_html_e( 'Guests', 'flex-multiple-listing-and-booking-system' ); ?></label>
						<input type="number" class="form-control fbs-filter-guests" placeholder="<?php esc_attr_e( 'Any', 'flex-multiple-listing-and-booking-system' ); ?>" min="0">
					</div>
					<div class="col-6 col-md-4 col-lg-2 d-grid">
						<button type="button" class="btn btn-primary fbs-filter-submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i><?php esc_html_e( 'Show Results', 'flex-multiple-listing-and-booking-system' ); ?></button>
					</div>
				</div>
				<input type="hidden" class="fbs-filter-sort" value="date">
				<button type="button" class="d-none fbs-filter-reset" aria-hidden="true"></button>
			</div>
			<?php endif; ?>

			<div class="fbs-grid-toolbar">
				<span class="fbs-grid-count small text-muted">
					<?php
					if ( $total > 0 ) {
						printf(
							/* translators: 1: number shown on page, 2: total listings */
							esc_html__( 'Showing 1–%1$d of %2$d properties', 'flex-multiple-listing-and-booking-system' ),
							(int) $showing_end,
							(int) $total
						);
					} else {
						esc_html_e( 'No properties found', 'flex-multiple-listing-and-booking-system' );
					}
					?>
				</span>
				<div class="d-flex align-items-center gap-2">
					<label class="small text-muted mb-0" for="<?php echo esc_attr( $uid ); ?>-sort"><?php esc_html_e( 'Sort by:', 'flex-multiple-listing-and-booking-system' ); ?></label>
					<select class="form-select form-select-sm fbs-filter-sort-select" id="<?php echo esc_attr( $uid ); ?>-sort" style="width:auto;">
						<option value="date"><?php esc_html_e( 'Newest', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="price_asc"><?php esc_html_e( 'Price: low to high', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="price_desc"><?php esc_html_e( 'Price: high to low', 'flex-multiple-listing-and-booking-system' ); ?></option>
						<option value="title"><?php esc_html_e( 'Name A–Z', 'flex-multiple-listing-and-booking-system' ); ?></option>
					</select>
					<span class="spinner-border spinner-border-sm text-primary d-none fbs-grid-spinner" role="status"></span>
				</div>
			</div>

			<div class="fbs-grid-results row">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<?php ListingDisplay::render_grid_card( get_the_ID(), $col_class ); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<div class="col-12"><p class="text-muted text-center py-5"><?php esc_html_e( 'No listings found.', 'flex-multiple-listing-and-booking-system' ); ?></p></div>
				<?php endif; ?>
			</div>

			<?php if ( $show_filters && $wp_query->max_num_pages > 1 ) : ?>
				<nav class="fbs-grid-pagination mt-4" data-pages="<?php echo esc_attr( (string) $wp_query->max_num_pages ); ?>" aria-label="<?php esc_attr_e( 'Pagination', 'flex-multiple-listing-and-booking-system' ); ?>">
					<ul class="pagination justify-content-center mb-0">
						<li class="page-item"><button class="page-link fbs-grid-prev" disabled aria-label="<?php esc_attr_e( 'Previous', 'flex-multiple-listing-and-booking-system' ); ?>">&laquo;</button></li>
						<li class="page-item active"><span class="page-link fbs-grid-page-info">1</span></li>
						<li class="page-item"><button class="page-link fbs-grid-next" aria-label="<?php esc_attr_e( 'Next', 'flex-multiple-listing-and-booking-system' ); ?>">&raquo;</button></li>
					</ul>
				</nav>
			<?php elseif ( ! $show_filters && $wp_query->max_num_pages > 1 ) : ?>
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
