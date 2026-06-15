# Role-based Menu Control Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add role-based menu visibility control with three modes (per-user, role-based, role-based with per-user overrides) alongside the existing per-user controls, with no behavior change for existing installs.

**Architecture:** A single pure resolver decides effective menu visibility from the per-user map, the user's role maps, and the active mode; a parallel resolver decides menu order. The three existing menu consumers are refactored to call one wrapper. New AJAX handlers persist the mode and per-role config. The React app gains a mode selector, a role editor (reusing existing menu components), and a tri-state Inherit/Show/Hide control for override mode.

**Tech Stack:** PHP (WordPress plugin, no framework), React 18 (webpack build to `build/admin.js`), standalone PHP unit tests run via `npm run test:php`, Playwright e2e.

**Spec:** `docs/superpowers/specs/2026-06-15-role-based-menu-control-design.md`

---

## File Structure

**PHP — `useradminsimplifier.php` (modify):**
- Option accessors: `uas_get_mode`, `uas_save_mode`, `uas_get_role_options`, `uas_save_role_options`.
- Pure resolvers: `uas_resolve_user_flags`, `uas_resolve_user_menu_order`, `uas_is_protected_menu_item`.
- Wrapper + role-map helper: `uas_get_user_role_maps`, `uas_get_effective_flags_for_current_user`, `uas_get_effective_menu_order_for_current_user`.
- Refactor consumers: `uas_init` (admin-bar check), `uas_edit_admin_menus`, `uas_edit_admin_bar_menu`.
- New AJAX handlers: `uas_ajax_save_mode`, `uas_ajax_save_role`, `uas_ajax_reset_role` + `add_action` registrations.
- Extend `useradminsimplifier_options_page` localized data + strings.

**PHP tests (create):**
- `tests/test-role-resolution.php` — copies the three pure resolvers + safeguard helper and asserts precedence.

**React (modify/create):**
- Create `src/components/ModeSelector.js`, `src/components/RoleSelector.js`.
- Modify `src/components/MenuList.js`, `src/components/AdminBarMenu.js` — optional tri-state.
- Modify `src/App.js` — wire modes, role editing, override resolution.

**Docs (modify):** `readme.txt`, `README.md` — document the new modes.

---

## Task 1: Option accessors for mode and role config

**Files:**
- Modify: `useradminsimplifier.php` (add near `uas_get_admin_options`, ~line 326)

- [ ] **Step 1: Add the mode accessors**

Add after `uas_save_admin_options()` (after line 335):

```php
	/**
	 * The allowed visibility modes.
	 *
	 * @return string[] List of valid mode slugs.
	 */
	function uas_get_modes() {
		return array( 'per-user', 'role', 'role-with-overrides' );
	}

	/**
	 * Retrieve the active visibility mode.
	 *
	 * @return string One of uas_get_modes(); defaults to 'per-user'.
	 */
	function uas_get_mode() {
		$mode = get_option( 'useradminsimplifier_mode', 'per-user' );
		return in_array( $mode, uas_get_modes(), true ) ? $mode : 'per-user';
	}

	/**
	 * Store the active visibility mode.
	 *
	 * @param string $mode The mode to store. Invalid values fall back to 'per-user'.
	 */
	function uas_save_mode( $mode ) {
		if ( ! in_array( $mode, uas_get_modes(), true ) ) {
			$mode = 'per-user';
		}
		update_option( 'useradminsimplifier_mode', $mode );
	}

	/**
	 * Retrieve the stored per-role options.
	 *
	 * @return array Map of role slug => flag map.
	 */
	function uas_get_role_options() {
		$saved = get_option( 'useradminsimplifier_roles' );
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * Store the per-role options.
	 *
	 * @param array $role_options Map of role slug => flag map.
	 */
	function uas_save_role_options( $role_options ) {
		update_option( 'useradminsimplifier_roles', $role_options );
	}
```

- [ ] **Step 2: Verify it parses**

Run: `php -l useradminsimplifier.php`
Expected: `No syntax errors detected in useradminsimplifier.php`

- [ ] **Step 3: Commit**

```bash
git add useradminsimplifier.php
git commit -m "Add mode and role option accessors"
```

---

## Task 2: Pure flag resolver (TDD)

The resolver is the precedence core. Written as a pure function so the standalone
PHP test harness can exercise it without WordPress.

**Files:**
- Create: `tests/test-role-resolution.php`
- Modify: `useradminsimplifier.php`

- [ ] **Step 1: Write the failing test file**

Create `tests/test-role-resolution.php`:

```php
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
```

- [ ] **Step 2: Run the test to confirm it passes**

Run: `php tests/test-role-resolution.php`
Expected: `Results: 6 passed, 0 failed out of 6 tests`

(The test file defines the function it tests, so it passes immediately — this
locks in the contract before the plugin copy is added.)

- [ ] **Step 3: Add the same function to the plugin**

In `useradminsimplifier.php`, after `uas_save_role_options()` (added in Task 1),
add the identical `uas_resolve_user_flags()` function body from Step 1 (the
function definition only, not the test class).

- [ ] **Step 4: Verify the plugin parses**

Run: `php -l useradminsimplifier.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add tests/test-role-resolution.php useradminsimplifier.php
git commit -m "Add pure flag resolver with precedence tests"
```

---

## Task 3: Pure menu-order resolver (TDD)

**Files:**
- Modify: `tests/test-role-resolution.php`, `useradminsimplifier.php`

- [ ] **Step 1: Add the order resolver + cases to the test file**

In `tests/test-role-resolution.php`, add this function after `uas_resolve_user_flags()`:

```php
/**
 * Resolve the effective menu order for a user.
 *
 * Ordering is a sequence, so it cannot be unioned. Role mode uses the primary
 * role's order; override mode prefers the per-user order, falling back to the
 * primary role's order.
 *
 * Copied from useradminsimplifier.php - keep in sync.
 *
 * @param array  $per_user_order     The user's menu-order list (may be empty).
 * @param array  $primary_role_order The primary role's menu-order list (may be empty).
 * @param string $mode               The active mode.
 * @return array The effective ordered list of menu ids.
 */
function uas_resolve_user_menu_order( $per_user_order, $primary_role_order, $mode ) {
	$per_user_order     = is_array( $per_user_order ) ? $per_user_order : array();
	$primary_role_order = is_array( $primary_role_order ) ? $primary_role_order : array();

	if ( 'per-user' === $mode ) {
		return $per_user_order;
	}

	if ( 'role' === $mode ) {
		return $primary_role_order;
	}

	// role-with-overrides: per-user order wins when set.
	return ! empty( $per_user_order ) ? $per_user_order : $primary_role_order;
}
```

Add a new test method and register it in `run_tests()`. Insert this method into
the `Test_Role_Resolution` class, before `run_tests()`:

```php
	public static function order_cases() {
		return array(
			array(
				'description' => 'per-user mode uses the per-user order',
				'per_user'    => array( 'a', 'b' ),
				'role'        => array( 'c', 'd' ),
				'mode'        => 'per-user',
				'expected'    => array( 'a', 'b' ),
			),
			array(
				'description' => 'role mode uses the primary role order',
				'per_user'    => array( 'a', 'b' ),
				'role'        => array( 'c', 'd' ),
				'mode'        => 'role',
				'expected'    => array( 'c', 'd' ),
			),
			array(
				'description' => 'overrides: per-user order wins when set',
				'per_user'    => array( 'a', 'b' ),
				'role'        => array( 'c', 'd' ),
				'mode'        => 'role-with-overrides',
				'expected'    => array( 'a', 'b' ),
			),
			array(
				'description' => 'overrides: falls back to role order when per-user empty',
				'per_user'    => array(),
				'role'        => array( 'c', 'd' ),
				'mode'        => 'role-with-overrides',
				'expected'    => array( 'c', 'd' ),
			),
		);
	}
```

Then in `run_tests()`, after the existing `foreach` over `flag_cases()`, add:

```php
		foreach ( self::order_cases() as $test ) {
			$actual = uas_resolve_user_menu_order( $test['per_user'], $test['role'], $test['mode'] );
			$status = ( $actual === $test['expected'] );
			$status ? $passed++ : $failed++;
			$results[] = array(
				'description' => 'order: ' . $test['description'],
				'expected'    => var_export( $test['expected'], true ),
				'actual'      => var_export( $actual, true ),
				'passed'      => $status,
			);
		}
```

- [ ] **Step 2: Run the tests**

Run: `php tests/test-role-resolution.php`
Expected: `Results: 10 passed, 0 failed out of 10 tests`

- [ ] **Step 3: Add the resolver to the plugin**

Add the identical `uas_resolve_user_menu_order()` function to
`useradminsimplifier.php` after `uas_resolve_user_flags()`.

- [ ] **Step 4: Verify parse**

Run: `php -l useradminsimplifier.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add tests/test-role-resolution.php useradminsimplifier.php
git commit -m "Add pure menu-order resolver with tests"
```

---

## Task 4: Lockout safeguard helper (TDD)

**Files:**
- Modify: `tests/test-role-resolution.php`, `useradminsimplifier.php`

- [ ] **Step 1: Add the helper + cases to the test file**

In `tests/test-role-resolution.php`, add a `sanitize_key` stub at the top (after
the opening docblock) so the helper can run standalone:

```php
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
```

Add the helper function after `uas_resolve_user_menu_order()`:

```php
/**
 * Whether a menu item id must never be hidden (lockout safeguard).
 *
 * The plugin's own settings page and its parent Tools menu must always remain
 * reachable so an administrator can recover from a config that hides them.
 *
 * Copied from useradminsimplifier.php - keep in sync.
 *
 * @param string $menu_id The sanitized menu id (item[5] or combined submenu id).
 * @return bool True if the item is protected.
 */
function uas_is_protected_menu_item( $menu_id ) {
	$protected = array(
		sanitize_key( 'menu-tools' ),
		sanitize_key( 'menu-tools' . 'useradminsimplifier/useradminsimplifier.php' ),
	);
	return in_array( $menu_id, $protected, true );
}
```

Add a test method to the class:

```php
	public static function protected_cases() {
		return array(
			array(
				'description' => 'Tools top-level menu is protected',
				'menu_id'     => sanitize_key( 'menu-tools' ),
				'expected'    => true,
			),
			array(
				'description' => 'UAS settings submenu is protected',
				'menu_id'     => sanitize_key( 'menu-tools' . 'useradminsimplifier/useradminsimplifier.php' ),
				'expected'    => true,
			),
			array(
				'description' => 'an unrelated menu is not protected',
				'menu_id'     => sanitize_key( 'menu-posts' ),
				'expected'    => false,
			),
		);
	}
```

And in `run_tests()` add, after the order loop:

```php
		foreach ( self::protected_cases() as $test ) {
			$actual = uas_is_protected_menu_item( $test['menu_id'] );
			$status = ( $actual === $test['expected'] );
			$status ? $passed++ : $failed++;
			$results[] = array(
				'description' => 'protected: ' . $test['description'],
				'expected'    => var_export( $test['expected'], true ),
				'actual'      => var_export( $actual, true ),
				'passed'      => $status,
			);
		}
```

- [ ] **Step 2: Run the tests**

Run: `php tests/test-role-resolution.php`
Expected: `Results: 13 passed, 0 failed out of 13 tests`

- [ ] **Step 3: Add the helper to the plugin**

Add the identical `uas_is_protected_menu_item()` to `useradminsimplifier.php`
after `uas_resolve_user_menu_order()`.

- [ ] **Step 4: Verify parse + full PHP suite**

Run: `php -l useradminsimplifier.php && npm run test:php`
Expected: no syntax errors; existing menu-name and menu-order suites still pass.

- [ ] **Step 5: Wire the new test into the test script**

Modify `package.json` `test:php`:

```json
    "test:php": "php tests/test-menu-name-cleaning.php && php tests/test-menu-order.php && php tests/test-role-resolution.php",
```

- [ ] **Step 6: Run and commit**

Run: `npm run test:php`
Expected: all three suites pass.

```bash
git add tests/test-role-resolution.php useradminsimplifier.php package.json
git commit -m "Add lockout safeguard helper and wire role tests into test:php"
```

---

## Task 5: Effective-flags wrapper and consumer refactor

Centralizes input gathering and refactors the three consumers to use the resolver.

**Files:**
- Modify: `useradminsimplifier.php` — `uas_init` (lines 29-41), `uas_edit_admin_bar_menu` (lines 147-170), `uas_edit_admin_menus` (lines 175-215), plus new helpers.

- [ ] **Step 1: Add the role-map + wrapper helpers**

Add after `uas_is_protected_menu_item()`:

```php
	/**
	 * Get the flag maps for each of a user's roles, plus the primary role's order.
	 *
	 * @param WP_User $user The user object.
	 * @return array {
	 *     @type array   $maps          List of role flag maps.
	 *     @type array   $primary_order The primary role's menu-order list.
	 * }
	 */
	function uas_get_user_role_maps( $user ) {
		$role_options = uas_get_role_options();
		$maps         = array();
		$primary_order = array();

		$roles = ( $user instanceof WP_User ) ? (array) $user->roles : array();
		foreach ( $roles as $index => $role_slug ) {
			if ( ! isset( $role_options[ $role_slug ] ) || ! is_array( $role_options[ $role_slug ] ) ) {
				continue;
			}
			$maps[] = $role_options[ $role_slug ];
			if ( 0 === $index && isset( $role_options[ $role_slug ]['menu-order'] ) ) {
				$primary_order = uas_sanitize_menu_order( $role_options[ $role_slug ]['menu-order'] );
			}
		}

		return array(
			'maps'          => $maps,
			'primary_order' => $primary_order,
		);
	}

	/**
	 * Resolve the effective hidden-flag map for the current user.
	 *
	 * @return array Map of menuId => 0|1.
	 */
	function uas_get_effective_flags_for_current_user() {
		global $current_user;

		$mode        = uas_get_mode();
		$uas_options = uas_get_admin_options();
		$per_user    = isset( $uas_options[ $current_user->user_nicename ] )
			? $uas_options[ $current_user->user_nicename ]
			: array();
		$role_data   = uas_get_user_role_maps( $current_user );

		return uas_resolve_user_flags( $per_user, $role_data['maps'], $mode );
	}

	/**
	 * Resolve the effective menu order for the current user.
	 *
	 * @return array Ordered list of menu ids (may be empty).
	 */
	function uas_get_effective_menu_order_for_current_user() {
		global $current_user;

		$mode        = uas_get_mode();
		$uas_options = uas_get_admin_options();
		$per_user_order = array();
		if ( isset( $uas_options[ $current_user->user_nicename ]['menu-order'] ) ) {
			$per_user_order = uas_sanitize_menu_order( $uas_options[ $current_user->user_nicename ]['menu-order'] );
		}
		$role_data = uas_get_user_role_maps( $current_user );

		return uas_resolve_user_menu_order( $per_user_order, $role_data['primary_order'], $mode );
	}
```

- [ ] **Step 2: Refactor the admin-bar disable check in `uas_init`**

Replace lines 29-41 (the `$uas_options = uas_get_admin_options();` block through
the closing `}` of the `if`) with:

```php
		// Remove the admin bar?
		$uas_flags = uas_get_effective_flags_for_current_user();
		if (
			isset( $uas_flags['disable-admin-bar'] ) &&
			1 === (int) $uas_flags['disable-admin-bar']
		) {
			// Hide on the admin side where its not possible to disable.
			add_action( 'admin_head', 'uas_hide_admin_bar' );

			// Disable on the front end.
			add_filter( 'show_admin_bar', '__return_false' );

		}
```

- [ ] **Step 3: Refactor `uas_edit_admin_bar_menu`**

Replace the body lines 152-167 (from `$wp_admin_bar_menu_items = $wp_admin_bar->get_nodes();`
through the closing of the `foreach`) with:

```php
		// Store the menubar nodes (menu items) in a global.
		$wp_admin_bar_menu_items = $wp_admin_bar->get_nodes();
		$uas_flags = uas_get_effective_flags_for_current_user();

		// Remove nodes for the current user.
		foreach( $wp_admin_bar_menu_items as $menu_item ) {
			if (
				isset( $uas_flags[ $menu_item->id ] ) &&
				1 === (int) $uas_flags[ $menu_item->id ]
			) {
				$wp_admin_bar->remove_node( $menu_item->id );
				if ( 'user-actions' === $menu_item->id ) {
					$wp_admin_bar->remove_node( 'my-account' );
				}
			}
		}
```

(Note: admin-bar ids are not run through `sanitize_key` here today; the resolver
keys mirror the raw `$menu_item->id` as stored by the React app, matching the
existing behavior.)

- [ ] **Step 4: Refactor `uas_edit_admin_menus`**

Replace the body from line 185 (`$uas_options = uas_get_admin_options();`) through
line 214 (end of the menu-order block, before the closing `}`) with:

```php
		$uas_flags = uas_get_effective_flags_for_current_user();
		$newmenu = array();
		if ( ! isset( $menu ) )
			return false;
		//rebuild menu based on saved options
		foreach ( $menu as $menuitem ) {
			if ( ! isset( $menuitem[5] ) ) {
				continue;
			}
			$top_id = sanitize_key( $menuitem[5] );
			if ( isset( $uas_flags[ $top_id ] ) && 1 === (int) $uas_flags[ $top_id ]
					&& ! uas_is_protected_menu_item( $top_id ) ) {
				remove_menu_page( $menuitem[2] );
			} else {
				// lets check the submenus
				if ( isset ( $storedsubmenu[ $menuitem[2] ] ) ) {
					foreach ( $storedsubmenu[ $menuitem[2] ] as $subsub ) {
						$combinedname = sanitize_key( $menuitem[5] . $subsub[2] );
						if  ( isset ( $subsub[2] ) && isset( $uas_flags[ $combinedname ] ) &&
							1 === (int) $uas_flags[ $combinedname ]
							&& ! uas_is_protected_menu_item( $combinedname ) ) {
							remove_submenu_page( $menuitem[2], $subsub[2] );
						}
					}
				}
			}
		}

		// Apply the effective custom menu order for this user.
		$saved_order = uas_get_effective_menu_order_for_current_user();
		if ( ! empty( $saved_order ) ) {
			$menu = uas_apply_menu_order( $menu, $saved_order );
		}
```

- [ ] **Step 5: Verify parse + tests**

Run: `php -l useradminsimplifier.php && npm run test:php`
Expected: no syntax errors; all PHP suites pass.

- [ ] **Step 6: Commit**

```bash
git add useradminsimplifier.php
git commit -m "Route menu consumers through the effective-flags resolver"
```

---

## Task 6: AJAX handlers for mode and role config

**Files:**
- Modify: `useradminsimplifier.php` — registrations near lines 25-26, handlers near `uas_ajax_reset_user` (line 126).

- [ ] **Step 1: Register the new AJAX actions**

After line 26 (`add_action( 'wp_ajax_uas_reset_user', 'uas_ajax_reset_user' );`) add:

```php
		add_action( 'wp_ajax_uas_save_mode', 'uas_ajax_save_mode' );
		add_action( 'wp_ajax_uas_save_role', 'uas_ajax_save_role' );
		add_action( 'wp_ajax_uas_reset_role', 'uas_ajax_reset_role' );
```

- [ ] **Step 2: Add a shared sanitizer + the handlers**

After `uas_ajax_reset_user()` (after line 126) add:

```php
	/**
	 * Sanitize a decoded flag map (menuId => int, with a menu-order list).
	 *
	 * @param array $options Raw decoded options.
	 * @return array Sanitized flag map.
	 */
	function uas_sanitize_flag_map( $options ) {
		$menu_order = array();
		if ( is_array( $options ) && isset( $options['menu-order'] ) ) {
			$menu_order = uas_sanitize_menu_order( $options['menu-order'] );
			unset( $options['menu-order'] );
		}

		$clean = array();
		if ( is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				$clean_key = sanitize_key( $key );
				if ( '' === $clean_key ) {
					continue;
				}
				$clean[ $clean_key ] = intval( $value );
			}
		}

		if ( ! empty( $menu_order ) ) {
			$clean['menu-order'] = $menu_order;
		}

		return $clean;
	}

	/**
	 * AJAX handler for saving the active visibility mode.
	 */
	function uas_ajax_save_mode() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'uas_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'useradminsimplifier' ) ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied', 'useradminsimplifier' ) ) );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'per-user';
		if ( ! in_array( $mode, uas_get_modes(), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid mode', 'useradminsimplifier' ) ) );
		}

		uas_save_mode( $mode );
		wp_send_json_success( array( 'message' => esc_html__( 'Mode saved successfully', 'useradminsimplifier' ) ) );
	}

	/**
	 * AJAX handler for saving a single role's options.
	 */
	function uas_ajax_save_role() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'uas_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'useradminsimplifier' ) ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied', 'useradminsimplifier' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		if ( '' === $role || ! array_key_exists( $role, get_editable_roles() ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid role', 'useradminsimplifier' ) ) );
		}

		$options_json = isset( $_POST['options'] ) ? wp_unslash( $_POST['options'] ) : '{}';
		$options      = json_decode( $options_json, true );
		if ( null === $options && JSON_ERROR_NONE !== json_last_error() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid options payload.', 'useradminsimplifier' ) ) );
		}

		$role_options          = uas_get_role_options();
		$role_options[ $role ] = uas_sanitize_flag_map( $options );
		uas_save_role_options( $role_options );

		wp_send_json_success( array( 'message' => esc_html__( 'Role settings saved successfully', 'useradminsimplifier' ) ) );
	}

	/**
	 * AJAX handler for resetting a single role's options.
	 */
	function uas_ajax_reset_role() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'uas_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'useradminsimplifier' ) ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied', 'useradminsimplifier' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		if ( '' === $role ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No role specified', 'useradminsimplifier' ) ) );
		}

		$role_options = uas_get_role_options();
		unset( $role_options[ $role ] );
		uas_save_role_options( $role_options );

		wp_send_json_success( array( 'message' => esc_html__( 'Role settings reset successfully', 'useradminsimplifier' ) ) );
	}
```

- [ ] **Step 3: DRY the existing save handler onto the shared sanitizer**

In `uas_ajax_save_options()`, replace lines 71-92 (the `$menu_order` extraction
through the `$user_options['menu-order'] = $menu_order;` block) with:

```php
		$user_options = uas_sanitize_flag_map( $options );
```

- [ ] **Step 4: Verify parse + tests**

Run: `php -l useradminsimplifier.php && npm run test:php`
Expected: no syntax errors; all PHP suites pass.

- [ ] **Step 5: Commit**

```bash
git add useradminsimplifier.php
git commit -m "Add AJAX handlers for mode and per-role config"
```

---

## Task 7: Pass role/mode data and strings to the React app

**Files:**
- Modify: `useradminsimplifier.php` — `useradminsimplifier_options_page` (users loop ~line 370, strings ~line 388, localize ~line 433).

- [ ] **Step 1: Include each user's roles in the users data**

Replace the users loop (lines 370-376) with:

```php
		// Prepare users data
		$blogusers = get_users( 'orderby=nicename' );
		$users_data = array();
		foreach ( $blogusers as $user ) {
			$users_data[] = array(
				'nicename' => $user->user_nicename,
				'roles'    => array_values( (array) $user->roles ),
			);
		}

		// Prepare roles data (slug => display name).
		$roles_data = array();
		foreach ( get_editable_roles() as $slug => $details ) {
			$roles_data[] = array(
				'slug' => $slug,
				'name' => isset( $details['name'] ) ? $details['name'] : $slug,
			);
		}
```

- [ ] **Step 2: Add new localized strings**

In the `$strings` array (before the closing `);` at line 413) add:

```php
			'modeLabel'            => esc_html__( 'Menu control mode', 'useradminsimplifier' ),
			'modePerUser'          => esc_html__( 'Per-user only', 'useradminsimplifier' ),
			'modeRole'             => esc_html__( 'Role-based only', 'useradminsimplifier' ),
			'modeRoleOverrides'    => esc_html__( 'Role-based with per-user overrides', 'useradminsimplifier' ),
			'chooseRole'           => esc_html__( 'Choose a role', 'useradminsimplifier' ),
			'editingRole'          => esc_html__( 'Editing role defaults', 'useradminsimplifier' ),
			'fromRole'             => esc_html__( '(from role)', 'useradminsimplifier' ),
			'inherit'              => esc_html__( 'Inherit', 'useradminsimplifier' ),
			'show'                 => esc_html__( 'Show', 'useradminsimplifier' ),
			'hide'                 => esc_html__( 'Hide', 'useradminsimplifier' ),
			'saveRole'             => esc_html__( 'Save Role Settings', 'useradminsimplifier' ),
			'resetRole'            => esc_html__( 'Reset Role Settings', 'useradminsimplifier' ),
			'modeSaved'            => esc_html__( 'Mode saved.', 'useradminsimplifier' ),
```

- [ ] **Step 3: Add roles/roleOptions/mode to the localized payload**

In the `wp_localize_script` data array (lines 433-442) add these keys after
`'options' => $uas_options,`:

```php
				'roles'         => $roles_data,
				'roleOptions'   => uas_get_role_options(),
				'mode'          => uas_get_mode(),
```

- [ ] **Step 4: Verify parse**

Run: `php -l useradminsimplifier.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add useradminsimplifier.php
git commit -m "Expose roles, role options, mode and strings to the React app"
```

---

## Task 8: ModeSelector component

**Files:**
- Create: `src/components/ModeSelector.js`

- [ ] **Step 1: Create the component**

```jsx
import React from 'react';

/**
 * ModeSelector component
 * Radio group to choose the menu control mode.
 */
const ModeSelector = ({ mode, onChange, strings }) => {
  const modes = [
    { value: 'per-user', label: strings.modePerUser || 'Per-user only' },
    { value: 'role', label: strings.modeRole || 'Role-based only' },
    {
      value: 'role-with-overrides',
      label: strings.modeRoleOverrides || 'Role-based with per-user overrides',
    },
  ];

  return (
    <fieldset className="uas-mode-selector">
      <legend>{strings.modeLabel || 'Menu control mode'}</legend>
      {modes.map((m) => (
        <label key={m.value} className="uas-mode-option">
          <input
            type="radio"
            name="uas-mode"
            value={m.value}
            checked={mode === m.value}
            onChange={() => onChange(m.value)}
          />
          {m.label}
        </label>
      ))}
    </fieldset>
  );
};

export default ModeSelector;
```

- [ ] **Step 2: Lint**

Run: `npm run lint`
Expected: no errors for `src/components/ModeSelector.js`.

- [ ] **Step 3: Commit**

```bash
git add src/components/ModeSelector.js
git commit -m "Add ModeSelector component"
```

---

## Task 9: RoleSelector component

**Files:**
- Create: `src/components/RoleSelector.js`

- [ ] **Step 1: Create the component**

```jsx
import React from 'react';

/**
 * RoleSelector component
 * Dropdown to select a role to edit.
 */
const RoleSelector = ({ roles, selectedRole, onChange, strings }) => {
  const handleChange = (e) => {
    onChange(e.target.value);
  };

  return (
    <div className="uas-role-selector-wrapper">
      <select
        id="uas_role_select"
        name="uas_role_select"
        value={selectedRole}
        onChange={handleChange}
        aria-label={strings.chooseRole || 'Choose a role'}
      >
        <option value="">{strings.choose || 'Choose...'}</option>
        {roles.map((role) => (
          <option key={role.slug} value={role.slug}>
            {role.name}
          </option>
        ))}
      </select>
    </div>
  );
};

export default RoleSelector;
```

- [ ] **Step 2: Lint + commit**

Run: `npm run lint`
Expected: no errors.

```bash
git add src/components/RoleSelector.js
git commit -m "Add RoleSelector component"
```

---

## Task 10: Tri-state support in MenuList and AdminBarMenu

Adds an optional Inherit/Show/Hide control. When `triState` is false (default),
components behave exactly as today.

**Files:**
- Modify: `src/components/MenuList.js`, `src/components/AdminBarMenu.js`

- [ ] **Step 1: Add a shared TriStateControl to MenuList.js**

At the top of `src/components/MenuList.js`, after the import line, add:

```jsx
/**
 * TriStateControl
 * Renders Inherit / Show / Hide radios for override mode. `value` is one of
 * 'inherit' | 'show' | 'hide'. onChange receives the new value.
 */
const TriStateControl = ({ id, value, onChange, strings }) => (
  <span className="uas-tristate" role="radiogroup">
    {['inherit', 'show', 'hide'].map((opt) => (
      <label key={opt} className="uas-tristate-option">
        <input
          type="radio"
          name={`tristate-${id}`}
          checked={value === opt}
          onChange={() => onChange(opt)}
        />
        {opt === 'inherit'
          ? (strings.inherit || 'Inherit')
          : opt === 'show'
          ? (strings.show || 'Show')
          : (strings.hide || 'Hide')}
      </label>
    ))}
  </span>
);
```

- [ ] **Step 2: Teach MenuItem to render tri-state**

In `MenuItem`, replace the `isChecked` derivation (line 26) and the `<label>`
checkbox block (lines 108-115) so it branches on a new `triState` prop. Replace
line 26:

```jsx
  const menuId = item.id;
  const isChecked = userOptions[menuId] === 1;
  const triValue =
    userOptions[menuId] === 1 ? 'hide' : userOptions[menuId] === 0 ? 'show' : 'inherit';
  const hasSubmenus = item.submenus && item.submenus.length > 0;
```

Replace the `<label>` checkbox block (lines 108-115) with:

```jsx
        {triState ? (
          <span className="uas-tristate-row">
            <span className="uas-tristate-name">{item.name}</span>
            <TriStateControl
              id={menuId}
              value={triValue}
              onChange={(v) => onTriToggle(menuId, v)}
              strings={strings}
            />
          </span>
        ) : (
          <label>
            <input type="checkbox" checked={isChecked} onChange={handleToggle} />
            {item.name}
          </label>
        )}
```

Add `triState` and `onTriToggle` to the `MenuItem` props destructure (line 8-22
list) — add `triState,` and `onTriToggle,` before `strings`.

Update the submenu checkbox block (lines 131-148) to also branch. Replace the
`item.submenus.map` body return with:

```jsx
            const submenuId = submenu.id;
            const subTriValue =
              userOptions[submenuId] === 1
                ? 'hide'
                : userOptions[submenuId] === 0
                ? 'show'
                : 'inherit';
            const isSubmenuChecked = userOptions[submenuId] === 1;
            const subRowClass = subIndex % 2 === 0 ? 'submain' : 'subalternate';

            return (
              <p key={submenuId} className={subRowClass}>
                {triState ? (
                  <span className="uas-tristate-row">
                    <span className="uas-tristate-name">{submenu.name}</span>
                    <TriStateControl
                      id={submenuId}
                      value={subTriValue}
                      onChange={(v) => onTriToggle(submenuId, v)}
                      strings={strings}
                    />
                  </span>
                ) : (
                  <label>
                    <input
                      type="checkbox"
                      checked={isSubmenuChecked}
                      onChange={(e) => onToggle(submenuId, e.target.checked)}
                    />
                    {submenu.name}
                  </label>
                )}
              </p>
            );
```

- [ ] **Step 3: Thread the props through MenuList**

In `MenuList` props (line 159), change the signature to:

```jsx
const MenuList = ({ menuItems, userOptions, onToggle, onTriToggle, onReorder, triState = false, strings }) => {
```

In the `<MenuItem ... />` render (lines 272-287), add these props:

```jsx
          triState={triState}
          onTriToggle={onTriToggle}
```

- [ ] **Step 4: Mirror the tri-state in AdminBarMenu.js**

Add the same `TriStateControl` definition to the top of
`src/components/AdminBarMenu.js`. In `AdminBarMenuItem`, add `triState` and
`onTriToggle` props, derive `triValue` like MenuList, and branch the top-level
`<label>` (lines 26-33) and child `<label>` (lines 56-63) the same way (showing
`item.title` / `child.title` as the name). In `AdminBarMenu` props (line 77),
add `onTriToggle` and `triState = false`, and pass them into `AdminBarMenuItem`.

- [ ] **Step 5: Lint + build**

Run: `npm run lint && npm run build`
Expected: lint clean; webpack build succeeds, writing `build/admin.js`.

- [ ] **Step 6: Commit**

```bash
git add src/components/MenuList.js src/components/AdminBarMenu.js
git commit -m "Add optional tri-state Inherit/Show/Hide to menu components"
```

---

## Task 11: Wire it together in App.js

**Files:**
- Modify: `src/App.js`

- [ ] **Step 1: Import the new components and seed state**

After the existing imports (line 5) add:

```jsx
import ModeSelector from './components/ModeSelector';
import RoleSelector from './components/RoleSelector';
```

In the destructure of `uasData` (lines 13-21), add `roles`, `roleOptions`,
`mode`:

```jsx
  const {
    users = [],
    menuItems = [],
    adminBarItems = [],
    options = {},
    roles = [],
    roleOptions = {},
    mode: initialMode = 'per-user',
    nonce = '',
    ajaxUrl = '',
    strings = {}
  } = typeof uasData !== 'undefined' ? uasData : {};
```

After the existing `useState` declarations (line 26) add:

```jsx
  const [mode, setMode] = useState(initialMode);
  const [selectedRole, setSelectedRole] = useState('');
  const [roleOpts, setRoleOpts] = useState(roleOptions);
```

- [ ] **Step 2: Add a small AJAX helper to DRY the POST calls**

After the destructure block, add:

```jsx
  const postAjax = useCallback(async (fields) => {
    const formData = new FormData();
    formData.append('nonce', nonce);
    Object.entries(fields).forEach(([k, v]) => formData.append(k, v));
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
    return response.json();
  }, [nonce, ajaxUrl]);
```

- [ ] **Step 3: Handle mode changes (persist immediately)**

Add:

```jsx
  const handleModeChange = useCallback(async (newMode) => {
    setMode(newMode);
    setMessage({ text: '', type: '' });
    try {
      const data = await postAjax({ action: 'uas_save_mode', mode: newMode });
      if (data.success) {
        setMessage({ text: strings.modeSaved || 'Mode saved.', type: 'success' });
      }
    } catch (e) {
      setMessage({ text: strings.saveError || 'Failed to save settings.', type: 'error' });
    }
  }, [postAjax, strings]);
```

- [ ] **Step 4: Add role editing handlers**

Add:

```jsx
  const currentRoleOptions = selectedRole ? (roleOpts[selectedRole] || {}) : {};

  const handleRoleToggle = useCallback((menuId, isChecked) => {
    if (!selectedRole) return;
    setRoleOpts(prev => ({
      ...prev,
      [selectedRole]: { ...prev[selectedRole], [menuId]: isChecked ? 1 : 0 },
    }));
  }, [selectedRole]);

  const handleRoleReorder = useCallback((menuOrder) => {
    if (!selectedRole) return;
    setRoleOpts(prev => ({
      ...prev,
      [selectedRole]: { ...prev[selectedRole], 'menu-order': menuOrder },
    }));
  }, [selectedRole]);

  const handleRoleSave = useCallback(async () => {
    if (!selectedRole) return;
    setIsSaving(true);
    setMessage({ text: '', type: '' });
    try {
      const data = await postAjax({
        action: 'uas_save_role',
        role: selectedRole,
        options: JSON.stringify(roleOpts[selectedRole] || {}),
      });
      setMessage(data.success
        ? { text: strings.saveSuccess || 'Settings saved successfully!', type: 'success' }
        : { text: data.data?.message || strings.saveError, type: 'error' });
    } catch (e) {
      setMessage({ text: strings.saveError || 'Failed to save settings.', type: 'error' });
    } finally {
      setIsSaving(false);
    }
  }, [selectedRole, roleOpts, postAjax, strings]);

  const handleRoleReset = useCallback(async () => {
    if (!selectedRole) return;
    setIsSaving(true);
    try {
      const data = await postAjax({ action: 'uas_reset_role', role: selectedRole });
      if (data.success) {
        setRoleOpts(prev => ({ ...prev, [selectedRole]: {} }));
        setMessage({ text: strings.resetSuccess || 'Settings reset!', type: 'success' });
      }
    } catch (e) {
      setMessage({ text: strings.resetError || 'Failed to reset.', type: 'error' });
    } finally {
      setIsSaving(false);
    }
  }, [selectedRole, postAjax, strings]);
```

- [ ] **Step 5: Add a tri-state handler for per-user override editing**

The tri-state control emits `'inherit' | 'show' | 'hide'`. Inherit removes the
key; show stores 0; hide stores 1.

```jsx
  const handleUserTriToggle = useCallback((menuId, value) => {
    if (!selectedUser) return;
    setUserOptions(prev => {
      const next = { ...(prev[selectedUser] || {}) };
      if (value === 'inherit') {
        delete next[menuId];
      } else {
        next[menuId] = value === 'hide' ? 1 : 0;
      }
      return { ...prev, [selectedUser]: next };
    });
  }, [selectedUser]);
```

- [ ] **Step 6: Compute visibility of the user vs role editors**

Before the `return` (replacing the `currentUserOptions` line 181), add:

```jsx
  const currentUserOptions = selectedUser ? (userOptions[selectedUser] || {}) : {};
  const showUserEditor = mode === 'per-user' || mode === 'role-with-overrides';
  const showRoleEditor = mode === 'role' || mode === 'role-with-overrides';
  const overrideMode = mode === 'role-with-overrides';
```

- [ ] **Step 7: Render mode selector, role editor, and gate the user editor**

Replace the JSX `return (...)` body (lines 183-247). Put the `ModeSelector`
right after the `<h2>`; wrap the existing user block in `showUserEditor`; add a
role editor block under `showRoleEditor`. For the role editor, reuse `MenuList`
(with `onReorder={handleRoleReorder}`) and `AdminBarMenu`, passing
`userOptions={currentRoleOptions}` and `onToggle={handleRoleToggle}`. For the
per-user `MenuList`/`AdminBarMenu` in override mode, pass `triState={overrideMode}`
and `onTriToggle={handleUserTriToggle}`. Full replacement:

```jsx
  return (
    <div className="wrap">
      <h2>{strings.title || 'User Admin Simplifier'}</h2>

      <ModeSelector mode={mode} onChange={handleModeChange} strings={strings} />

      {showRoleEditor && (
        <div className="uas-container" id="chooserole">
          <h3>{strings.editingRole || 'Editing role defaults'}:</h3>
          <RoleSelector
            roles={roles}
            selectedRole={selectedRole}
            onChange={(r) => { setSelectedRole(r); setMessage({ text: '', type: '' }); }}
            strings={strings}
          />
          {selectedRole && (
            <div className="uas-container" id="rolemenus">
              <h3>{strings.disableMenus || 'Disable menus/submenus'}:</h3>
              <MenuList
                menuItems={menuItems}
                userOptions={currentRoleOptions}
                onToggle={handleRoleToggle}
                onReorder={handleRoleReorder}
                strings={strings}
              />
              <hr />
              <h3>{strings.disableAdminBar || 'Disable the admin bar'}:</h3>
              <div className="menu-item uas-admin-bar-toggle">
                <label>
                  <input
                    type="checkbox"
                    checked={currentRoleOptions['disable-admin-bar'] === 1}
                    onChange={(e) => handleRoleToggle('disable-admin-bar', e.target.checked)}
                  />
                  {strings.disableAdminBarLabel || 'Completely disable the admin bar for this user.'}
                </label>
              </div>
              <h3>{strings.disableAdminBarMenus || 'Disable admin bar menus/submenus'}:</h3>
              <AdminBarMenu
                adminBarItems={adminBarItems}
                userOptions={currentRoleOptions}
                onToggle={handleRoleToggle}
                strings={strings}
              />
              <SaveButton
                onSave={handleRoleSave}
                onReset={handleRoleReset}
                isSaving={isSaving}
                strings={{ ...strings, saveChanges: strings.saveRole || strings.saveChanges, resetUser: strings.resetRole || strings.resetUser }}
              />
            </div>
          )}
        </div>
      )}

      {showUserEditor && (
        <div className="uas-container" id="chooseauser">
          <h3>{strings.chooseUser || 'Choose a user'}:</h3>
          <UserSelector
            users={users}
            selectedUser={selectedUser}
            onChange={handleUserChange}
            strings={strings}
          />
        </div>
      )}

      {showUserEditor && selectedUser && (
        <div className="uas-container" id="choosemenus">
          <h3>{strings.disableMenus || 'Disable menus/submenus'}:</h3>

          <MenuList
            menuItems={menuItems}
            userOptions={currentUserOptions}
            onToggle={handleMenuToggle}
            onTriToggle={handleUserTriToggle}
            onReorder={handleMenuReorder}
            triState={overrideMode}
            strings={strings}
          />

          <hr />

          <h3>{strings.disableAdminBar || 'Disable the admin bar'}:</h3>
          <div className="menu-item uas-admin-bar-toggle">
            <label>
              <input
                type="checkbox"
                checked={currentUserOptions['disable-admin-bar'] === 1}
                onChange={(e) => handleMenuToggle('disable-admin-bar', e.target.checked)}
              />
              {strings.disableAdminBarLabel || 'Completely disable the admin bar for this user.'}
            </label>
          </div>

          <h3>{strings.disableAdminBarMenus || 'Disable admin bar menus/submenus'}:</h3>

          <AdminBarMenu
            adminBarItems={adminBarItems}
            userOptions={currentUserOptions}
            onToggle={handleMenuToggle}
            onTriToggle={handleUserTriToggle}
            triState={overrideMode}
            strings={strings}
          />

          <SaveButton
            onSave={handleSave}
            onReset={handleReset}
            isSaving={isSaving}
            strings={strings}
          />
        </div>
      )}

      {message.text && (
        <div className={`uas-message ${message.type}`}>
          {message.text}
        </div>
      )}
    </div>
  );
```

(Note: the message block moved out of the per-user container so it also shows for
mode changes and role saves.)

- [ ] **Step 8: Lint + build**

Run: `npm run lint && npm run build`
Expected: lint clean; build writes `build/admin.js`.

- [ ] **Step 9: Commit**

```bash
git add src/App.js
git commit -m "Wire mode selector, role editor and override tri-state into App"
```

---

## Task 12: Styles, manual verification, and docs

**Files:**
- Modify: `src/styles.css`, `readme.txt`, `README.md`

- [ ] **Step 1: Add minimal styles**

Append to `src/styles.css`:

```css
.uas-mode-selector { margin: 1em 0; padding: 1em; border: 1px solid #c3c4c7; background: #fff; }
.uas-mode-selector legend { font-weight: 600; }
.uas-mode-option { display: block; margin: .25em 0; }
.uas-tristate-row { display: inline-flex; align-items: center; gap: 1em; }
.uas-tristate-option { margin-right: .75em; }
.uas-tristate-name { min-width: 12em; display: inline-block; }
```

- [ ] **Step 2: Build**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 3: Manual verification in wp-env**

Run: `npm run env:start`
Then in the browser at the local wp-env admin (Tools → User Admin Simplifier):
1. Default mode is "Per-user only"; existing per-user editing works unchanged.
2. Switch to "Role-based only": role dropdown appears; pick a role, hide a menu,
   reorder a menu, Save. Log in as a user with that role (or check the menu)
   and confirm the menu is hidden/reordered.
3. Switch to "Role-based with per-user overrides": both editors show; the
   per-user menu rows show Inherit/Show/Hide; set an override and confirm it
   wins over the role default.
4. As an administrator, hide "Tools" for the administrator role and confirm the
   User Admin Simplifier page is still reachable (lockout safeguard).

Record pass/fail for each; fix regressions before continuing.

- [ ] **Step 4: Run the full PHP suite**

Run: `npm run test:php`
Expected: all suites pass.

- [ ] **Step 5: Update docs**

In `readme.txt` (Description section) and `README.md`, add a short paragraph
describing the three modes and that per-user remains the default with no
breaking change. Bump the changelog/"Stable tag" notes only if the maintainer
normally does so as part of release (leave the version bump to the release flow).

- [ ] **Step 6: Commit**

```bash
git add src/styles.css build/ readme.txt README.md
git commit -m "Add role-mode styles and document the new menu control modes"
```

---

## Task 13: Open the pull request

- [ ] **Step 1: Push the branch**

```bash
git push -u origin feature/role-based-menu-control
```

- [ ] **Step 2: Open the PR**

```bash
gh pr create --repo adamsilverstein/user-admin-simplifier --base main \
  --title "Add role-based menu control with optional per-user overrides" \
  --body "Implements role-based menu visibility with three modes (per-user, role-based, role-based with per-user overrides), per-role menu reordering, a lockout safeguard, and resolution unit tests. Closes #39."
```

Expected: PR URL printed. Verify CI runs (PHP tests, lint, e2e) and is green.

---

## Self-Review notes

- **Spec coverage:** three modes (Tasks 1,5,8,11) ✓; role editor reusing components (Task 11) ✓; per-role show/hide + reorder (Tasks 6,11,10) ✓; per-user overrides tri-state (Tasks 10,11) ✓; no breaking change / default per-user (Tasks 1,5) ✓; multi-role union + primary-role order (Tasks 2,3) ✓; lockout safeguard (Tasks 4,5) ✓; tests (Tasks 2,3,4) ✓; data model 3 options (Tasks 1,6,7) ✓.
- **Type/name consistency:** resolver names (`uas_resolve_user_flags`, `uas_resolve_user_menu_order`, `uas_is_protected_menu_item`), prop names (`triState`, `onTriToggle`, `roleOpts`), and AJAX actions (`uas_save_mode`, `uas_save_role`, `uas_reset_role`) are used consistently across PHP, tests, and React.
- **Deferred (per spec):** import-per-user-into-role helper; e2e tests for the new modes (manual verification in Task 12 covers the first pass).
