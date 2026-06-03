<?php
/**
 * Standalone QA for ColorSettings save merge (run: php tests/color-settings-save-test.php).
 *
 * @package FlexBookingSystem
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	if ( 'cli' !== php_sapi_name() ) {
		exit;
	}
	define( 'ABSPATH', __DIR__ );
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	function sanitize_hex_color( $color ) {
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', (string) $color ) ) {
			return strtolower( (string) $color );
		}
		return '';
	}
}

require_once dirname( __DIR__ ) . '/includes/Front/LayoutSettings.php';
require_once dirname( __DIR__ ) . '/includes/Front/ColorSettings.php';

use FlexBooking\Front\ColorSettings;

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

/**
 * CLI test output line.
 *
 * @param string $message Message.
 * @return void
 */
function fbs_test_line( $message ) {
	echo esc_html( (string) $message ) . "\n";
}

$prev = array(
	'color_primary' => '#0d6efd',
	'color_page_bg' => '#ff0000',
	'currency'      => 'USD',
);

$post = array(
	'fbs_colors_json'   => wp_json_encode(
		array(
			'color_primary' => '#112233',
			'color_page_bg' => '#f5f6f8',
		)
	),
	'fbs_color_primary' => '#112233',
	'fbs_color_page_bg' => '#f5f6f8',
);

$merged = ColorSettings::merge_from_post( $prev, $post );
$fail   = 0;

if ( '#112233' !== $merged['color_primary'] ) {
	fbs_test_line( 'FAIL: color_primary expected #112233 got ' . $merged['color_primary'] );
	++$fail;
}

if ( '#f5f6f8' !== $merged['color_page_bg'] ) {
	fbs_test_line( 'FAIL: color_page_bg expected #f5f6f8 got ' . $merged['color_page_bg'] );
	++$fail;
}

if ( count( ColorSettings::fields() ) !== count( array_intersect_key( $merged, ColorSettings::fields() ) ) ) {
	fbs_test_line( 'FAIL: not all color fields returned in merge' );
	++$fail;
}

$post_partial = array(
	'fbs_color_primary' => '#abcdef',
);

$merged_partial = ColorSettings::merge_from_post( $prev, $post_partial );
if ( '#abcdef' !== $merged_partial['color_primary'] ) {
	fbs_test_line( 'FAIL: partial save primary expected #abcdef got ' . $merged_partial['color_primary'] );
	++$fail;
}

if ( '#ff0000' !== $merged_partial['color_page_bg'] ) {
	fbs_test_line( 'FAIL: partial save without page bg should keep previous #ff0000 got ' . $merged_partial['color_page_bg'] );
	++$fail;
}

$post_no_hash = array(
	'fbs_colors_json' => wp_json_encode( array( 'color_page_bg' => 'f5f6f8' ) ),
);

$merged_no_hash = ColorSettings::merge_from_post( $prev, $post_no_hash );
if ( '#f5f6f8' !== $merged_no_hash['color_page_bg'] ) {
	fbs_test_line( 'FAIL: hex without hash should save as #f5f6f8 got ' . $merged_no_hash['color_page_bg'] );
	++$fail;
}

if ( 0 === $fail ) {
	fbs_test_line( 'OK: ColorSettings merge_from_post QA passed (' . count( ColorSettings::fields() ) . ' fields)' );
	exit( 0 );
}

exit( 1 );
