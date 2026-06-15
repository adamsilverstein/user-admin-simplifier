<?php
/**
 * Unit tests for the role-based visibility resolvers.
 *
 * Tests uas_resolve_user_flags(), uas_resolve_user_menu_order() and
 * uas_is_protected_menu_item(). These pure helpers are copied from
 * useradminsimplifier.php and MUST be kept in sync with it.
 *
 * @package UserAdminSimplifier
 */

/**
 * Resolve the effective hidden-flag map for a user.
 *
 * Copied from useradminsimplifier.php - keep in sync.
 *
 * @param array  $per_user_map Map of menuId => 0|1 for the user.
 * @param array  $role_maps    List of role flag maps (each menuId => 1 hidden).
 * @param string $mode         One of 'per-user', 'role', 'role-with-overrides'.
 * @return array Effective map of menuId => 0|1.
 */
function uas_resolve_user_flags( $per_user_map, $role_maps, $mode ) {
	$per_user_map = is_array( $per_user_map ) ? $per_user_map : array();
	$role_maps    = is_array( $role_maps ) ? $role_maps : array();

	if ( 'per-user' === $mode ) {
		return $per_user_map;
	}

	// Union of all role maps: hidden (1) if any role hides it.
	$role_union = array();
	foreach ( $role_maps as $role_map ) {
		if ( ! is_array( $role_map ) ) {
			continue;
		}
		foreach ( $role_map as $key => $value ) {
			if ( 'menu-order' === $key ) {
				continue;
			}
			if ( 1 === (int) $value ) {
				$role_union[ $key ] = 1;
			}
		}
	}

	if ( 'role' === $mode ) {
		return $role_union;
	}

	// role-with-overrides: per-user explicit keys win over the role union.
	$resolved = $role_union;
	foreach ( $per_user_map as $key => $value ) {
		if ( 'menu-order' === $key ) {
			continue;
		}
		$resolved[ $key ] = (int) $value;
	}

	return $resolved;
}

/**
 * Test cases.
 */
class Test_Role_Resolution {

	public static function flag_cases() {
		return array(
			array(
				'description' => 'per-user mode returns the per-user map unchanged',
				'per_user'    => array( 'menu-a' => 1, 'menu-b' => 0 ),
				'role_maps'   => array( array( 'menu-c' => 1 ) ),
				'mode'        => 'per-user',
				'expected'    => array( 'menu-a' => 1, 'menu-b' => 0 ),
			),
			array(
				'description' => 'role mode unions conflicting multi-role config (hide if any)',
				'per_user'    => array( 'menu-a' => 0 ),
				'role_maps'   => array(
					array( 'menu-a' => 1, 'menu-b' => 0 ),
					array( 'menu-b' => 1, 'menu-c' => 1 ),
				),
				'mode'        => 'role',
				'expected'    => array( 'menu-a' => 1, 'menu-b' => 1, 'menu-c' => 1 ),
			),
			array(
				'description' => 'overrides: explicit user hide (1) wins over role show',
				'per_user'    => array( 'menu-a' => 1 ),
				'role_maps'   => array( array( 'menu-b' => 1 ) ),
				'mode'        => 'role-with-overrides',
				'expected'    => array( 'menu-b' => 1, 'menu-a' => 1 ),
			),
			array(
				'description' => 'overrides: explicit user show (0) wins over role hide',
				'per_user'    => array( 'menu-b' => 0 ),
				'role_maps'   => array( array( 'menu-b' => 1 ) ),
				'mode'        => 'role-with-overrides',
				'expected'    => array( 'menu-b' => 0 ),
			),
			array(
				'description' => 'overrides: absent user key inherits role value',
				'per_user'    => array(),
				'role_maps'   => array( array( 'menu-b' => 1 ) ),
				'mode'        => 'role-with-overrides',
				'expected'    => array( 'menu-b' => 1 ),
			),
			array(
				'description' => 'disable-admin-bar resolves through the same precedence',
				'per_user'    => array( 'disable-admin-bar' => 0 ),
				'role_maps'   => array( array( 'disable-admin-bar' => 1 ) ),
				'mode'        => 'role-with-overrides',
				'expected'    => array( 'disable-admin-bar' => 0 ),
			),
		);
	}

	public static function run_tests() {
		$passed  = 0;
		$failed  = 0;
		$results = array();

		foreach ( self::flag_cases() as $test ) {
			$actual = uas_resolve_user_flags( $test['per_user'], $test['role_maps'], $test['mode'] );
			ksort( $actual );
			$expected = $test['expected'];
			ksort( $expected );
			$status = ( $actual === $expected );
			$status ? $passed++ : $failed++;
			$results[] = array(
				'description' => $test['description'],
				'expected'    => var_export( $expected, true ),
				'actual'      => var_export( $actual, true ),
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

// Run tests if executed directly.
if ( php_sapi_name() === 'cli' && basename( __FILE__ ) === basename( $argv[0] ?? '' ) ) {
	echo "Running role resolution tests...\n";
	echo str_repeat( '=', 80 ) . "\n\n";

	$results = Test_Role_Resolution::run_tests();

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
