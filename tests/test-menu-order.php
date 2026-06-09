<?php
/**
 * Unit tests for the menu order helpers.
 *
 * Tests uas_sanitize_menu_order() and uas_apply_menu_order(), which power
 * the per-user drag-and-drop menu reordering feature.
 *
 * @package UserAdminSimplifier
 */

/**
 * Minimal stand-in for the WordPress sanitize_key() function so the helpers
 * can run outside of WordPress. Mirrors the WordPress implementation.
 *
 * @param string $key The key to sanitize.
 * @return string The sanitized key.
 */
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$sanitized_key = '';
		if ( is_scalar( $key ) ) {
			$sanitized_key = strtolower( (string) $key );
			$sanitized_key = preg_replace( '/[^a-z0-9_\-]/', '', $sanitized_key );
		}
		return $sanitized_key;
	}
}

/**
 * The functions being tested - copied from useradminsimplifier.php
 * These must be kept in sync with the main plugin file.
 */

/**
 * Sanitize a saved menu order list.
 *
 * @param  mixed $menu_order The raw menu order value.
 *
 * @return array Sanitized list of unique menu item keys.
 */
function uas_sanitize_menu_order( $menu_order ) {
	if ( ! is_array( $menu_order ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $menu_order as $menu_id ) {
		if ( ! is_string( $menu_id ) && ! is_int( $menu_id ) ) {
			continue;
		}
		$menu_id = sanitize_key( $menu_id );
		if ( '' !== $menu_id && ! in_array( $menu_id, $sanitized, true ) ) {
			$sanitized[] = $menu_id;
		}
	}

	return $sanitized;
}

/**
 * Reorder the admin menu array based on a saved menu order.
 *
 * Items found in the saved order are rearranged to match it, while items
 * not present in the saved order (including separators and newly added
 * menus) keep their default relative positions.
 *
 * @param  array $menu       The WordPress admin menu array, keyed by position.
 * @param  array $menu_order Ordered list of sanitized menu item keys.
 *
 * @return array The reordered menu array, re-keyed with the original positions.
 */
function uas_apply_menu_order( $menu, $menu_order ) {
	if ( ! is_array( $menu ) || empty( $menu ) || empty( $menu_order ) ) {
		return $menu;
	}

	// Work with the menu in its current (position keyed) order.
	ksort( $menu );
	$positions = array_keys( $menu );
	$items     = array_values( $menu );

	// Find the items that are part of the saved order, keyed by their menu id.
	$ordered_slots = array();
	$ordered_items = array();
	foreach ( $items as $index => $item ) {
		// Separators and items without an id always stay in place.
		if ( ! isset( $item[5] ) ) {
			continue;
		}
		$menu_id = sanitize_key( $item[5] );
		if ( in_array( $menu_id, $menu_order, true ) && ! isset( $ordered_items[ $menu_id ] ) ) {
			$ordered_slots[]           = $index;
			$ordered_items[ $menu_id ] = $item;
		}
	}

	if ( empty( $ordered_slots ) ) {
		return $menu;
	}

	// Refill the slots used by ordered items, following the saved order.
	$slot = 0;
	foreach ( $menu_order as $menu_id ) {
		if ( isset( $ordered_items[ $menu_id ] ) ) {
			$items[ $ordered_slots[ $slot ] ] = $ordered_items[ $menu_id ];
			$slot++;
		}
	}

	// Re-key the reordered items with the original menu positions.
	return array_combine( $positions, $items );
}

/**
 * Build a WordPress-style top-level menu item fixture.
 *
 * @param string $name The menu name.
 * @param string $slug The menu file slug.
 * @param string $id   The menu id (CSS class, index 5).
 * @return array Menu item array.
 */
function uas_test_menu_item( $name, $slug, $id ) {
	return array( $name, 'read', $slug, '', 'menu-top', $id, 'dashicons-admin-generic' );
}

/**
 * Build a WordPress-style menu separator fixture (no index 5).
 *
 * @param string $slug The separator slug.
 * @return array Separator item array.
 */
function uas_test_menu_separator( $slug ) {
	return array( '', 'read', $slug, '', 'wp-menu-separator' );
}

/**
 * Extract a readable order signature (slug list) from a menu array.
 *
 * @param array $menu The menu array.
 * @return array List of menu file slugs in array order.
 */
function uas_test_menu_slugs( $menu ) {
	$slugs = array();
	foreach ( $menu as $item ) {
		$slugs[] = $item[2];
	}
	return $slugs;
}

/**
 * Test cases for menu order sanitization and application
 */
class Test_Menu_Order {

	/**
	 * Test cases for uas_sanitize_menu_order()
	 *
	 * @return array Test cases with input, expected output, and description
	 */
	public static function get_sanitize_test_cases() {
		return array(
			array(
				'input'       => array( 'menu-posts', 'menu-pages', 'menu-comments' ),
				'expected'    => array( 'menu-posts', 'menu-pages', 'menu-comments' ),
				'description' => 'Valid keys pass through unchanged',
			),
			array(
				'input'       => 'menu-posts',
				'expected'    => array(),
				'description' => 'Non-array input returns an empty array',
			),
			array(
				'input'       => null,
				'expected'    => array(),
				'description' => 'Null input returns an empty array',
			),
			array(
				'input'       => array( 'Menu-Posts', 'MENU PAGES!', '<script>alert(1)</script>' ),
				'expected'    => array( 'menu-posts', 'menupages', 'scriptalert1script' ),
				'description' => 'Keys are lowercased and stripped of invalid characters',
			),
			array(
				'input'       => array( 'menu-posts', 'menu-posts', 'menu-pages' ),
				'expected'    => array( 'menu-posts', 'menu-pages' ),
				'description' => 'Duplicate keys are removed, keeping the first occurrence',
			),
			array(
				'input'       => array( '', '!!!', 'menu-pages' ),
				'expected'    => array( 'menu-pages' ),
				'description' => 'Keys that sanitize to an empty string are dropped',
			),
			array(
				'input'       => array( array( 'menu-posts' ), new stdClass(), true, 'menu-pages', 42 ),
				'expected'    => array( 'menu-pages', '42' ),
				'description' => 'Non string/int values are dropped, integers are kept as strings',
			),
		);
	}

	/**
	 * Test cases for uas_apply_menu_order()
	 *
	 * Each case provides a menu fixture, a saved order and the expected
	 * resulting slug order plus expected array keys.
	 *
	 * @return array Test cases.
	 */
	public static function get_apply_test_cases() {
		$default_menu = array(
			2  => uas_test_menu_item( 'Dashboard', 'index.php', 'menu-dashboard' ),
			4  => uas_test_menu_separator( 'separator1' ),
			5  => uas_test_menu_item( 'Posts', 'edit.php', 'menu-posts' ),
			10 => uas_test_menu_item( 'Media', 'upload.php', 'menu-media' ),
			20 => uas_test_menu_item( 'Pages', 'edit.php?post_type=page', 'menu-pages' ),
			59 => uas_test_menu_separator( 'separator2' ),
			60 => uas_test_menu_item( 'Appearance', 'themes.php', 'menu-appearance' ),
		);

		return array(
			array(
				'menu'           => $default_menu,
				'order'          => array(),
				'expected_slugs' => array( 'index.php', 'separator1', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'separator2', 'themes.php' ),
				'description'    => 'Empty saved order keeps the default order',
			),
			array(
				'menu'           => $default_menu,
				'order'          => array( 'menu-pages', 'menu-posts', 'menu-media', 'menu-dashboard', 'menu-appearance' ),
				'expected_slugs' => array( 'edit.php?post_type=page', 'separator1', 'edit.php', 'upload.php', 'index.php', 'separator2', 'themes.php' ),
				'description'    => 'Full reorder is applied while separators keep their relative positions',
			),
			array(
				'menu'           => $default_menu,
				'order'          => array( 'menu-appearance', 'menu-dashboard' ),
				'expected_slugs' => array( 'themes.php', 'separator1', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'separator2', 'index.php' ),
				'description'    => 'Items missing from the saved order keep their default relative position',
			),
			array(
				'menu'           => $default_menu,
				'order'          => array( 'menu-unknown', 'menu-other' ),
				'expected_slugs' => array( 'index.php', 'separator1', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'separator2', 'themes.php' ),
				'description'    => 'Saved order containing only unknown ids leaves the menu untouched',
			),
			array(
				'menu'           => $default_menu,
				'order'          => array( 'menu-media', 'menu-unknown', 'menu-posts' ),
				'expected_slugs' => array( 'index.php', 'separator1', 'upload.php', 'edit.php', 'edit.php?post_type=page', 'separator2', 'themes.php' ),
				'description'    => 'Unknown ids inside the saved order are skipped',
			),
			array(
				'menu'           => array(),
				'order'          => array( 'menu-posts' ),
				'expected_slugs' => array(),
				'description'    => 'Empty menu returns unchanged',
			),
		);
	}

	/**
	 * Run all tests
	 *
	 * @return array Results with passed and failed counts
	 */
	public static function run_tests() {
		$passed  = 0;
		$failed  = 0;
		$results = array();

		// Sanitization tests.
		foreach ( self::get_sanitize_test_cases() as $test ) {
			$result = uas_sanitize_menu_order( $test['input'] );
			$status = ( $result === $test['expected'] );

			if ( $status ) {
				$passed++;
			} else {
				$failed++;
			}

			$results[] = array(
				'description' => 'sanitize: ' . $test['description'],
				'expected'    => var_export( $test['expected'], true ),
				'actual'      => var_export( $result, true ),
				'passed'      => $status,
			);
		}

		// Reordering tests.
		foreach ( self::get_apply_test_cases() as $test ) {
			$result = uas_apply_menu_order( $test['menu'], $test['order'] );
			$status = ( uas_test_menu_slugs( $result ) === $test['expected_slugs'] );

			// The reordered menu must keep the exact same position keys.
			$expected_keys = array_keys( $test['menu'] );
			sort( $expected_keys );
			if ( array_keys( $result ) !== $expected_keys ) {
				$status = false;
			}

			if ( $status ) {
				$passed++;
			} else {
				$failed++;
			}

			$results[] = array(
				'description' => 'apply: ' . $test['description'],
				'expected'    => var_export( $test['expected_slugs'], true ),
				'actual'      => var_export( uas_test_menu_slugs( $result ), true ),
				'passed'      => $status,
			);
		}

		return array(
			'passed'  => $passed,
			'failed'  => $failed,
			'total'   => count( $results ),
			'results' => $results,
		);
	}
}

// Run tests if executed directly
if ( php_sapi_name() === 'cli' && basename( __FILE__ ) === basename( $argv[0] ?? '' ) ) {
	echo "Running menu order helper tests...\n";
	echo str_repeat( '=', 80 ) . "\n\n";

	$results = Test_Menu_Order::run_tests();

	foreach ( $results['results'] as $test ) {
		$status = $test['passed'] ? '✓ PASS' : '✗ FAIL';
		echo "$status: {$test['description']}\n";

		if ( ! $test['passed'] ) {
			echo "  Expected: {$test['expected']}\n";
			echo "  Got:      {$test['actual']}\n";
		}
		echo "\n";
	}

	echo str_repeat( '=', 80 ) . "\n";
	echo "Results: {$results['passed']} passed, {$results['failed']} failed out of {$results['total']} tests\n";

	exit( $results['failed'] > 0 ? 1 : 0 );
}
