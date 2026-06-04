<?php
/**
 * Shared listing display helpers for grid cards and single pages.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Front;

use FlexBooking\Listings\ListingMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Renders grid cards and parses listing specs for templates.
 */
final class ListingDisplay {

	/**
	 * Bootstrap column class for grid.
	 *
	 * @param int $columns Columns 2-4.
	 * @return string
	 */
	public static function grid_col_class( $columns = 3 ) {
		$columns = max( 2, min( 4, (int) $columns ) );
		$map     = array(
			2 => 'col-lg-6',
			3 => 'col-lg-4',
			4 => 'col-lg-3',
		);

		return 'col-sm-6 ' . ( $map[ $columns ] ?? 'col-lg-4' );
	}

	/**
	 * Find feature value by keyword in label/value.
	 *
	 * @param array<int,array<string,string>> $features Features.
	 * @param string                            $needle   Search term.
	 * @return string
	 */
	public static function feature_spec( array $features, $needle ) {
		foreach ( $features as $feature ) {
			if ( ! is_array( $feature ) ) {
				continue;
			}
			$label = strtolower( (string) ( $feature['label'] ?? '' ) );
			$value = (string) ( $feature['value'] ?? '' );
			if ( false !== strpos( $label, strtolower( $needle ) ) ) {
				return $value ? $value : (string) ( $feature['label'] ?? '' );
			}
		}

		return '';
	}

	/**
	 * Whether listing should show featured badge.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_featured( $post_id ) {
		return is_sticky( $post_id )
			|| ListingMeta::get( $post_id, ListingMeta::KEY_INSTANT_BOOKING, 'bool' );
	}

	/**
	 * Whether listing is recently published.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_new_listing( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		return ( time() - strtotime( $post->post_date ) ) < ( 21 * DAY_IN_SECONDS );
	}

	/**
	 * Rating + review count for display (filterable).
	 *
	 * @param int $post_id Post ID.
	 * @return array{rating: float, count: int}
	 */
	public static function rating_data( $post_id ) {
		$rating = (float) ListingMeta::get( $post_id, ListingMeta::KEY_RATING, 'float' );
		$count  = (int) ListingMeta::get( $post_id, ListingMeta::KEY_REVIEW_COUNT, 'int' );

		$data = array(
			'rating' => $rating,
			'count'  => $count,
		);

		$data = apply_filters( 'ulbm_listing_rating_data', $data, $post_id );

		return array(
			'rating' => max( 0, min( 5, (float) ( $data['rating'] ?? 0 ) ) ),
			'count'  => max( 0, (int) ( $data['count'] ?? 0 ) ),
		);
	}

	/**
	 * Whether a feature row is a spec stat (beds/baths/size), not an amenity pill.
	 *
	 * @param array<string, string> $feature Feature row.
	 * @return bool
	 */
	public static function is_spec_feature( array $feature ) {
		$label = strtolower( (string) ( $feature['label'] ?? '' ) );
		return (bool) preg_match( '/\b(bed|bath|size|m²|sq|guest|room)\b/', $label );
	}

	/**
	 * Grid card display options from settings.
	 *
	 * @return array{show_rating: bool, show_amenities: bool, amenities_limit: int}
	 */
	public static function grid_card_options() {
		$settings = LayoutSettings::get();

		return array(
			'show_rating'      => ! isset( $settings['grid_show_rating'] ) || ! empty( $settings['grid_show_rating'] ),
			'show_amenities'   => ! isset( $settings['grid_show_amenities'] ) || ! empty( $settings['grid_show_amenities'] ),
			'amenities_limit'  => max( 1, min( 8, (int) ( $settings['grid_amenities_limit'] ?? 4 ) ) ),
		);
	}

	/**
	 * Amenity feature rows for cards (excludes bed/bath/size specs).
	 *
	 * @param array<int, mixed> $features Feature meta rows.
	 * @param int               $limit    Max items.
	 * @return array<int, array<string, string>>
	 */
	public static function amenity_features( array $features, $limit = 4 ) {
		$out = array();
		foreach ( $features as $feat ) {
			if ( ! is_array( $feat ) ) {
				continue;
			}
			$label = trim( (string) ( $feat['label'] ?? '' ) );
			if ( '' === $label || self::is_spec_feature( $feat ) ) {
				continue;
			}
			$out[] = array(
				'label' => $label,
				'icon'  => ! empty( $feat['icon'] ) ? (string) $feat['icon'] : 'bi-check-circle',
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		return $out;
	}

	/**
	 * Compact star rating for grid cards.
	 *
	 * @param int $post_id Listing post id.
	 * @return void
	 */
	public static function render_grid_star_rating( $post_id ) {
		$data = self::rating_data( $post_id );
		if ( $data['rating'] <= 0 ) {
			return;
		}

		$rating = $data['rating'];
		$count  = $data['count'];
		$full   = (int) floor( $rating );
		?>
		<div class="ulbm-card-rating" aria-label="<?php echo esc_attr( self::grid_rating_aria_label( $rating ) ); ?>">
			<span class="ulbm-card-rating-stars" aria-hidden="true">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<i class="bi bi-star<?php echo $i <= $full ? '-fill' : ''; ?>"></i>
				<?php endfor; ?>
			</span>
			<span class="ulbm-card-rating-score"><?php echo esc_html( self::format_rating_score( $rating ) ); ?></span>
			<?php if ( $count > 0 ) : ?>
				<span class="ulbm-card-rating-count">(<?php echo esc_html( self::format_review_count( $count ) ); ?>)</span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Echo star rating row markup.
	 *
	 * @param float $rating Rating 0-5.
	 * @param int   $count  Review count.
	 * @return void
	 */
	public static function render_star_rating( $rating, $count ) {
		if ( $rating <= 0 ) {
			return;
		}
		$full = (int) floor( $rating );
		?>
		<div class="ulbm-listing-stars" aria-label="<?php echo esc_attr( self::stars_aria_label( $rating, $count ) ); ?>">
			<span class="ulbm-stars-icons" aria-hidden="true">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<i class="bi bi-star<?php echo $i <= $full ? '-fill' : ''; ?>"></i>
				<?php endfor; ?>
			</span>
			<span class="ulbm-stars-score"><?php echo esc_html( self::format_rating_score( $rating ) ); ?></span>
			<?php if ( $count > 0 ) : ?>
				<span class="ulbm-stars-count">(<?php echo esc_html( self::format_review_count( $count ) ); ?>)</span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a grid card.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $col_class Column classes.
	 * @return void
	 */
	public static function render_grid_card( $post_id, $col_class = 'col-sm-6 col-lg-4' ) {
		$ulbm_card_post_id = (int) $post_id;
		$ulbm_card_col     = $col_class;
		include ULBM_PLUGIN_DIR . 'templates/public/partials/grid-card.php';
	}

	/**
	 * Aria label for grid star rating.
	 *
	 * @param float $rating Average rating.
	 * @return string
	 */
	private static function grid_rating_aria_label( $rating ) {
		return sprintf(
			/* translators: 1: average rating, 2: max stars */
			__( 'Rated %1$s out of 5', 'flex-booking-system' ),
			number_format_i18n( $rating, 1 )
		);
	}

	/**
	 * Formatted rating for display.
	 *
	 * @param float $rating Rating value.
	 * @return string
	 */
	private static function format_rating_score( $rating ) {
		return number_format_i18n( $rating, 1 );
	}

	/**
	 * Formatted review count for display.
	 *
	 * @param int $count Review count.
	 * @return string
	 */
	private static function format_review_count( $count ) {
		return sprintf(
			/* translators: %d: number of reviews */
			__( '%d reviews', 'flex-booking-system' ),
			$count
		);
	}

	/**
	 * Aria label for full star row.
	 *
	 * @param float $rating Average rating.
	 * @param int   $count  Review count.
	 * @return string
	 */
	private static function stars_aria_label( $rating, $count ) {
		return sprintf(
			/* translators: 1: average rating, 2: review count */
			__( 'Rated %1$s out of 5 from %2$d reviews', 'flex-booking-system' ),
			number_format_i18n( $rating, 1 ),
			$count
		);
	}
}
