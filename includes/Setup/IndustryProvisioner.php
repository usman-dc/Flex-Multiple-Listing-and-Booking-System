<?php
/**
 * Persists selected industries — booking_types rows and option ulbm_enabled_industries.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Setup;

use FlexBooking\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Creates DB rows for chosen verticals.
 */
final class IndustryProvisioner {

	/**
	 * Save enabled keys and upsert booking_type rows.
	 *
	 * @param string[] $keys Industry keys from IndustryCatalog.
	 * @return array<string, mixed> Summary for AJAX/UI.
	 */
	public static function provision( array $keys ) {
		$valid = IndustryCatalog::valid_keys();
		$clean = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $k ) use ( $valid ) {
							$k = sanitize_key( (string) $k );
							return in_array( $k, $valid, true ) ? $k : null;
						},
						$keys
					)
				)
			)
		);

		update_option( 'ulbm_enabled_industries', $clean, false );

		global $wpdb;

		$table = Schema::tables()['booking_types'];
		$now   = current_time( 'mysql' );
		$added = 0;
		$skipped = 0;

		foreach ( $clean as $key ) {
			$def = IndustryCatalog::get( $key );
			if ( null === $def ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from schema.
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE slug = %s LIMIT 1", $def['booking_slug'] ) );
			if ( $existing ) {
				++$skipped;
				continue;
			}

			$settings = array(
				'industry'   => $key,
				'post_type'  => $def['post_type'],
				'mode'       => 'daily',
				'setup_seed' => true,
			);

			$wpdb->insert(
				$table,
				array(
					'name'        => $def['type_name'],
					'slug'        => $def['booking_slug'],
					'description' => $def['description'],
					'module_key'  => $def['module_key'],
					'settings'    => wp_json_encode( $settings ),
					'status'      => 'publish',
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( $wpdb->insert_id ) {
				++$added;
			}
		}

		flush_rewrite_rules( false );

		return array(
			'enabled'  => $clean,
			'added'    => $added,
			'skipped'  => $skipped,
		);
	}

	/**
	 * Ensure an industry key is in ulbm_enabled_industries so CPT + menus register (idempotent).
	 *
	 * @param string $key Industry catalog key.
	 * @return void
	 */
	public static function ensure_industry_enabled( $key ) {
		$key = sanitize_key( (string) $key );
		if ( ! in_array( $key, IndustryCatalog::valid_keys(), true ) ) {
			return;
		}

		$enabled = get_option( 'ulbm_enabled_industries', array() );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}
		if ( in_array( $key, $enabled, true ) ) {
			return;
		}

		$enabled[] = $key;
		update_option( 'ulbm_enabled_industries', array_values( array_unique( $enabled ) ), false );
		flush_rewrite_rules( false );
	}
}
