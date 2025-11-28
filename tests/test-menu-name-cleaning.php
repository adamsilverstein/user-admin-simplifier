<?php
/**
 * Unit tests for uas_clean_menu_name function
 *
 * Tests that the function properly strips span tags from WordPress menu names.
 * WordPress menus can contain nested span tags for notification counts.
 *
 * @package UserAdminSimplifier
 */

/**
 * The function being tested - copied from useradminsimplifier.php
 * This must be kept in sync with the main plugin file.
 *
 * @param string $menuname The menu name from WordPress.
 * @return string The cleaned menu name.
 */
function uas_clean_menu_name( $menuname ) {
	// Use greedy matching to remove all span tags including nested spans
	// WordPress menu names can have nested spans like:
	// "Comments <span class='x'><span class='y'>1</span><span class='z'>text</span></span>"
	$menuname = preg_replace( '/<span[^>]*>.*<\/span>/s', '', $menuname );
	return trim( $menuname );
}

/**
 * Test cases for menu name cleaning
 */
class Test_Menu_Name_Cleaning {

	/**
	 * Data provider with WordPress menu name formats
	 *
	 * @return array Test cases with input, expected output, and description
	 */
	public static function get_test_cases() {
		return array(
			// Simple names (no spans)
			array(
				'input'       => 'Dashboard',
				'expected'    => 'Dashboard',
				'description' => 'Simple menu name without spans',
			),
			array(
				'input'       => 'Posts',
				'expected'    => 'Posts',
				'description' => 'Simple menu name without spans',
			),
			array(
				'input'       => 'Media',
				'expected'    => 'Media',
				'description' => 'Simple menu name without spans',
			),

			// Updates submenu - nested spans (from wp-admin/menu.php)
			array(
				'input'       => 'Updates <span class="update-plugins count-2"><span class="update-count">2</span></span>',
				'expected'    => 'Updates',
				'description' => 'Updates with nested count spans',
			),

			// Comments - complex nested spans with screen reader text
			array(
				'input'       => 'Comments <span class="awaiting-mod count-5"><span class="pending-count" aria-hidden="true">5</span><span class="comments-in-moderation-text screen-reader-text">5 Comments in moderation</span></span>',
				'expected'    => 'Comments',
				'description' => 'Comments with multiple nested spans including screen reader text',
			),

			// Themes - nested spans
			array(
				'input'       => 'Themes <span class="update-plugins count-1"><span class="theme-count">1</span></span>',
				'expected'    => 'Themes',
				'description' => 'Themes with update count',
			),

			// Plugins - nested spans
			array(
				'input'       => 'Plugins <span class="update-plugins count-3"><span class="plugin-count">3</span></span>',
				'expected'    => 'Plugins',
				'description' => 'Plugins with update count',
			),

			// Site Health in Tools submenu
			array(
				'input'       => 'Site Health <span class="menu-counter site-health-counter count-2"><span class="count">2</span></span>',
				'expected'    => 'Site Health',
				'description' => 'Site Health with critical issues count',
			),

			// Edge cases
			array(
				'input'       => 'Updates <span class="update-plugins count-0"><span class="update-count">0</span></span>',
				'expected'    => 'Updates',
				'description' => 'Menu with zero count',
			),
			array(
				'input'       => 'Comments <span class="awaiting-mod count-999"><span class="pending-count" aria-hidden="true">999</span><span class="comments-in-moderation-text screen-reader-text">999 Comments in moderation</span></span>',
				'expected'    => 'Comments',
				'description' => 'Menu with large count',
			),
		);
	}

	/**
	 * Run all tests
	 *
	 * @return array Results with passed and failed counts
	 */
	public static function run_tests() {
		$test_cases = self::get_test_cases();
		$passed     = 0;
		$failed     = 0;
		$results    = array();

		foreach ( $test_cases as $test ) {
			$result = uas_clean_menu_name( $test['input'] );
			$status = ( $result === $test['expected'] );

			if ( $status ) {
				$passed++;
			} else {
				$failed++;
			}

			$results[] = array(
				'description' => $test['description'],
				'input'       => $test['input'],
				'expected'    => $test['expected'],
				'actual'      => $result,
				'passed'      => $status,
			);
		}

		return array(
			'passed'  => $passed,
			'failed'  => $failed,
			'total'   => count( $test_cases ),
			'results' => $results,
		);
	}
}

// Run tests if executed directly
if ( php_sapi_name() === 'cli' && basename( __FILE__ ) === basename( $argv[0] ?? '' ) ) {
	echo "Running uas_clean_menu_name() tests...\n";
	echo str_repeat( '=', 80 ) . "\n\n";

	$results = Test_Menu_Name_Cleaning::run_tests();

	foreach ( $results['results'] as $test ) {
		$status = $test['passed'] ? '✓ PASS' : '✗ FAIL';
		echo "$status: {$test['description']}\n";

		if ( ! $test['passed'] ) {
			echo "  Input:    \"{$test['input']}\"\n";
			echo "  Expected: \"{$test['expected']}\"\n";
			echo "  Got:      \"{$test['actual']}\"\n";
		}
		echo "\n";
	}

	echo str_repeat( '=', 80 ) . "\n";
	echo "Results: {$results['passed']} passed, {$results['failed']} failed out of {$results['total']} tests\n";

	exit( $results['failed'] > 0 ? 1 : 0 );
}
