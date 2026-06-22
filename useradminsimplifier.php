<?php
/*
Plugin Name: User Admin Simplifier
Plugin URI: http://www.earthbound.com/plugins/user-admin-simplifier
Description: Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.
Version: 3.1.0
Author: Adam Silverstein
Author URI: http://www.earthbound.com/plugins
License: MIT
*/

	add_action( 'init', 'uas_init' );

	//@todo show only user's available menus, eg. less than admins as per suggestion

	function uas_init() {
		global $current_user;

		add_action( 'admin_menu', 'uas_add_admin_menu', 99 );
		add_action( 'admin_head', 'uas_edit_admin_menus', 100 );
		add_filter( 'plugin_action_links', 'uas_plugin_action_links', 10, 2 );
		add_action( 'admin_bar_menu', 'uas_edit_admin_bar_menu', 999 );

		// Register AJAX actions for React UI
		add_action( 'wp_ajax_uas_save_options', 'uas_ajax_save_options' );
		add_action( 'wp_ajax_uas_reset_user', 'uas_ajax_reset_user' );
		add_action( 'wp_ajax_uas_save_mode', 'uas_ajax_save_mode' );
		add_action( 'wp_ajax_uas_save_role', 'uas_ajax_save_role' );
		add_action( 'wp_ajax_uas_reset_role', 'uas_ajax_reset_role' );

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
	}

	/**
	 * AJAX handler for saving options.
	 */
	function uas_ajax_save_options() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'uas_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'useradminsimplifier' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied', 'useradminsimplifier' ) ) );
		}

		$user = isset( $_POST['user'] ) ? sanitize_text_field( wp_unslash( $_POST['user'] ) ) : '';
		$options_json = isset( $_POST['options'] ) ? wp_unslash( $_POST['options'] ) : '{}';
		$options = json_decode( $options_json, true );

		// Validate JSON decode
		if ( null === $options && JSON_ERROR_NONE !== json_last_error() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid options payload.', 'useradminsimplifier' ) ) );
		}

		if ( empty( $user ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No user specified', 'useradminsimplifier' ) ) );
		}

		$user_options = uas_sanitize_flag_map( $options );

		$uas_options = uas_get_admin_options();
		$uas_options[ $user ] = $user_options;
		uas_save_admin_options( $uas_options );

		wp_send_json_success( array( 'message' => esc_html__( 'Options saved successfully', 'useradminsimplifier' ) ) );
	}

	/**
	 * AJAX handler for resetting user options.
	 */
	function uas_ajax_reset_user() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'uas_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'useradminsimplifier' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied', 'useradminsimplifier' ) ) );
		}

		$user = isset( $_POST['user'] ) ? sanitize_text_field( wp_unslash( $_POST['user'] ) ) : '';

		if ( empty( $user ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No user specified', 'useradminsimplifier' ) ) );
		}

		$uas_options = uas_get_admin_options();
		unset( $uas_options[ $user ] );
		uas_save_admin_options( $uas_options );

		wp_send_json_success( array( 'message' => esc_html__( 'User settings reset successfully', 'useradminsimplifier' ) ) );
	}

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

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
		if ( '' === $mode || ! in_array( $mode, uas_get_modes(), true ) ) {
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
		if ( ! is_array( $options ) ) {
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
		if ( '' === $role || ! array_key_exists( $role, get_editable_roles() ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid role', 'useradminsimplifier' ) ) );
		}

		$role_options = uas_get_role_options();
		unset( $role_options[ $role ] );
		uas_save_role_options( $role_options );

		wp_send_json_success( array( 'message' => esc_html__( 'Role settings reset successfully', 'useradminsimplifier' ) ) );
	}

	/**
	 * Hide the WordPress admin bar on the admin side.
	 */
	function uas_hide_admin_bar() {
		?>
		<script type="text/javascript">
			jQuery( 'html' ).removeClass( 'wp-toolbar' );
		</script>
		<style>
			#wpadminbar {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Filter the admin bar dropdowns.
	 */
	function uas_edit_admin_bar_menu( $wp_admin_bar ) {
		global $wp_admin_bar_menu_items;
		global $current_user;

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

		return $wp_admin_bar;
	}

	/**
	 * Edit the items available in the menu globals based on the current user settings.
	 */
	function uas_edit_admin_menus() {
		global $menu;
		global $current_user;
		global $storedmenu;
		global $storedsubmenu;
		global $submenu;


		$storedmenu = $menu; //store the original menu
		$storedsubmenu = $submenu; //store the original menu
		$uas_flags = uas_get_effective_flags_for_current_user();
		$newmenu = array();
		if ( ! isset( $menu ) )
			return false;

		// The lockout safeguard only matters for users who can reach the plugin's
		// settings page (it lives under Tools and requires manage_options). Other
		// roles cannot open it, so the Tools menu is hideable for them like any other.
		$protect_settings = current_user_can( 'manage_options' );

		//rebuild menu based on saved options
		foreach ( $menu as $menuitem ) {
			if ( ! isset( $menuitem[5] ) ) {
				continue;
			}
			$top_id = sanitize_key( $menuitem[5] );
			if ( isset( $uas_flags[ $top_id ] ) && 1 === (int) $uas_flags[ $top_id ]
					&& ! ( $protect_settings && uas_is_protected_menu_item( $top_id ) ) ) {
				remove_menu_page( $menuitem[2] );
			} else {
				// lets check the submenus
				if ( isset ( $storedsubmenu[ $menuitem[2] ] ) ) {
					foreach ( $storedsubmenu[ $menuitem[2] ] as $subsub ) {
						$combinedname = sanitize_key( $menuitem[5] . $subsub[2] );
						if  ( isset ( $subsub[2] ) && isset( $uas_flags[ $combinedname ] ) &&
							1 === (int) $uas_flags[ $combinedname ]
							&& ! ( $protect_settings && uas_is_protected_menu_item( $combinedname ) ) ) {
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
	}

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
	 * Add a settings link to the plugins page.
	 */
	function uas_plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$uas_links = '<a href="' . get_admin_url() . 'admin.php?page=useradminsimplifier/useradminsimplifier.php">' . esc_html__( 'Settings', 'useradminsimplifier' ) . '</a>';
			// make the 'Settings' link appear first
			array_unshift( $links, $uas_links );
		}
		return $links;
	}

	/**
	 * Add the User Admin Simplifier menu item.
	 */
	function uas_add_admin_menu() {
		add_management_page( 	esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ),
								esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ),
								'manage_options',
								'useradminsimplifier/useradminsimplifier.php',
								'useradminsimplifier_options_page' );
	}

	/**
	 * Retrieve the stored options.
	 */
	function uas_get_admin_options() {
		$saved_options = get_option( 'useradminsimplifier_options' );
		return is_array( $saved_options ) ? $saved_options : array();
	}

	/**
	 * Store the passed options.
	 *
	 * @param  array $uas_options The selected user options.
	 */
	function uas_save_admin_options( $uas_options ) {
		update_option( 'useradminsimplifier_options', $uas_options );
	}

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

	/**
	 * Resolve the effective hidden-flag map for a user.
	 *
	 * Copied into tests/test-role-resolution.php - keep in sync.
	 *
	 * @param mixed  $per_user_map Map of menuId => 0|1 for the user.
	 * @param mixed  $role_maps    List of role flag maps (each menuId => 1 hidden).
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
	 * Resolve the effective menu order for a user.
	 *
	 * Ordering is a sequence, so it cannot be unioned. Role mode uses the primary
	 * role's order; override mode prefers the per-user order, falling back to the
	 * primary role's order.
	 *
	 * Copied into tests/test-role-resolution.php - keep in sync.
	 *
	 * @param mixed  $per_user_order     The user's menu-order list (may be empty).
	 * @param mixed  $primary_role_order The primary role's menu-order list (may be empty).
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

	/**
	 * Whether a menu item id must never be hidden (lockout safeguard).
	 *
	 * The plugin's own settings page and its parent Tools menu must always remain
	 * reachable so an administrator can recover from a config that hides them.
	 *
	 * The test suite keeps a copy of this helper in sync (see
	 * tests/test-role-resolution.php) for standalone resolver testing.
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

	/**
	 * Get the flag maps for each of a user's roles, plus the primary role's order.
	 *
	 * @param mixed $user The user object (expected to be a WP_User).
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

	/**
	 * Helper function to clean menu names.
	 *
	 * @param  string $menuname The stored menu name.
	 *
	 * @return string           The processed menu name.
	 */
	function uas_clean_menu_name( $menuname ) { //clean up menu names provided by WordPress
		// Use greedy matching to remove all span tags including nested spans
		// WordPress menu names can have nested spans like:
		// "Comments <span class='x'><span class='y'>1</span><span class='z'>text</span></span>"
		$menuname = preg_replace( '/<span[^>]*>.*<\/span>/s', '', $menuname );
		return trim( $menuname );
	}

	/**
	 * Display the options page with React UI.
	 */
	function useradminsimplifier_options_page() {
		global $menu;
		global $submenu;
		global $storedmenu;
		global $storedsubmenu;
		global $wp_admin_bar_menu_items;

		if ( ! isset( $storedmenu ) ) {
			$storedmenu = $menu;
		}
		if ( ! isset( $storedsubmenu ) ) {
			$storedsubmenu = $submenu;
		}

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

		// Prepare menu items data
		$menu_items = uas_prepare_menu_items( $storedmenu, $storedsubmenu );

		// Prepare admin bar items data
		$admin_bar_items = uas_prepare_admin_bar_items( $wp_admin_bar_menu_items );

		// Get saved options
		$uas_options = uas_get_admin_options();

		// Prepare localized strings
		$strings = array(
			'title'                => esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ),
			'chooseUser'           => esc_html__( 'Choose a user', 'useradminsimplifier' ),
			'choose'               => esc_html__( 'Choose...', 'useradminsimplifier' ),
			'disableMenus'         => esc_html__( 'Disable menus/submenus', 'useradminsimplifier' ),
			'disableAdminBar'      => esc_html__( 'Disable the admin bar', 'useradminsimplifier' ),
			'disableAdminBarLabel' => esc_html__( 'Completely disable the admin bar for this user.', 'useradminsimplifier' ),
			'disableAdminBarMenus' => esc_html__( 'Disable admin bar menus/submenus', 'useradminsimplifier' ),
			'showSubmenus'         => esc_html__( 'Show submenus', 'useradminsimplifier' ),
			'hideSubmenus'         => esc_html__( 'Hide submenus', 'useradminsimplifier' ),
			'saveChanges'          => esc_html__( 'Save Changes', 'useradminsimplifier' ),
			'resetUser'            => esc_html__( 'Reset User Settings', 'useradminsimplifier' ),
			'saving'               => esc_html__( 'Saving...', 'useradminsimplifier' ),
			'saveSuccess'          => esc_html__( 'Settings saved successfully!', 'useradminsimplifier' ),
			'saveError'            => esc_html__( 'Failed to save settings.', 'useradminsimplifier' ),
			'resetSuccess'         => esc_html__( 'User settings reset successfully!', 'useradminsimplifier' ),
			'resetError'           => esc_html__( 'Failed to reset settings.', 'useradminsimplifier' ),
			'disableAllMenus'      => esc_html__( 'Disable all menus', 'useradminsimplifier' ),
			'enableAllMenus'       => esc_html__( 'Enable all menus', 'useradminsimplifier' ),
			'disableAllAdminBar'   => esc_html__( 'Disable all admin bar items', 'useradminsimplifier' ),
			'enableAllAdminBar'    => esc_html__( 'Enable all admin bar items', 'useradminsimplifier' ),
			'reorderHint'          => esc_html__( 'Drag a menu item, or use its arrow buttons, to change the menu order for this user.', 'useradminsimplifier' ),
			'dragToReorder'        => esc_html__( 'Drag to reorder', 'useradminsimplifier' ),
			'moveUp'               => esc_html__( 'Move up', 'useradminsimplifier' ),
			'moveDown'             => esc_html__( 'Move down', 'useradminsimplifier' ),
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
			'adminBarEnabled'      => esc_html__( 'Enabled', 'useradminsimplifier' ),
			'adminBarDisabled'     => esc_html__( 'Disabled', 'useradminsimplifier' ),
			'saveRole'             => esc_html__( 'Save Role Settings', 'useradminsimplifier' ),
			'resetRole'            => esc_html__( 'Reset Role Settings', 'useradminsimplifier' ),
			'modeSaved'            => esc_html__( 'Mode saved.', 'useradminsimplifier' ),
		);

		// Enqueue React app
		$plugin_url = plugin_dir_url( __FILE__ );
		$plugin_path = plugin_dir_path( __FILE__ );

		// Check if build files exist
		$js_file = $plugin_path . 'build/admin.js';
		$css_file = $plugin_path . 'build/admin.css';

		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'uas-admin-react',
				$plugin_url . 'build/admin.js',
				array(),
				filemtime( $js_file ),
				true
			);

			// Pass data to React app
			wp_localize_script( 'uas-admin-react', 'uasData', array(
				'users'         => $users_data,
				'menuItems'     => $menu_items,
				'adminBarItems' => $admin_bar_items,
				'options'       => $uas_options,
				'roles'         => $roles_data,
				'roleOptions'   => uas_get_role_options(),
				'mode'          => uas_get_mode(),
				'nonce'         => wp_create_nonce( 'uas_nonce' ),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'strings'       => $strings,
				'imagesUrl'     => $plugin_url . 'images/',
			) );
		}

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'uas-admin-react-css',
				$plugin_url . 'build/admin.css',
				array(),
				filemtime( $css_file )
			);
		}

		// Render the React root container
		echo '<div id="uas-react-root"></div>';
	}

	/**
	 * Prepare menu items for React.
	 *
	 * @param array $stored_menu The WordPress menu array.
	 * @param array $stored_submenu The WordPress submenu array.
	 * @return array Formatted menu items for React.
	 */
	function uas_prepare_menu_items( $stored_menu, $stored_submenu ) {
		$menu_items = array();

		if ( ! is_array( $stored_menu ) ) {
			return $menu_items;
		}

		foreach ( $stored_menu as $menuitem ) {
			// Don't process menu separators
			if ( isset( $menuitem[4] ) && 'wp-menu-separator' === $menuitem[4] ) {
				continue;
			}

			if ( ! isset( $menuitem[5] ) || ! isset( $menuitem[0] ) ) {
				continue;
			}

			$item = array(
				'id'       => sanitize_key( $menuitem[5] ),
				'name'     => uas_clean_menu_name( $menuitem[0] ),
				'submenus' => array(),
			);

			// Add submenus if available
			$topmenu = isset( $menuitem[2] ) ? $menuitem[2] : '';
			if ( ! empty( $topmenu ) && isset( $stored_submenu[ $topmenu ] ) ) {
				foreach ( $stored_submenu[ $topmenu ] as $subsub ) {
					if ( isset( $subsub[0] ) && isset( $subsub[2] ) ) {
						$item['submenus'][] = array(
							'id'   => sanitize_key( $menuitem[5] . $subsub[2] ),
							'name' => uas_clean_menu_name( $subsub[0] ),
						);
					}
				}
			}

			$menu_items[] = $item;
		}

		return $menu_items;
	}

	/**
	 * Prepare admin bar items for React.
	 *
	 * @param array $wp_admin_bar_menu_items The admin bar menu items.
	 * @return array Formatted admin bar items for React.
	 */
	function uas_prepare_admin_bar_items( $wp_admin_bar_menu_items ) {
		$admin_bar_items = array();

		if ( ! is_array( $wp_admin_bar_menu_items ) ) {
			return $admin_bar_items;
		}

		$title_map = array(
			'wp-logo'     => '(W)ordPress',
			'site-name'   => 'Site',
			'updates'     => 'Updates',
			'comments'    => 'Comments',
			'new-content' => '+ New',
			'my-account'  => 'User Menu',
		);

		// Add some common front end actions
		$add_for_front = array(
			'customize' => (object) array(
				'id'     => 'customize',
				'title'  => 'Customize',
				'parent' => false,
			),
			'edit' => (object) array(
				'id'     => 'edit',
				'title'  => 'Edit Page',
				'parent' => false,
			),
		);

		$wp_admin_bar_menu_items = array_merge( (array) $wp_admin_bar_menu_items, $add_for_front );

		// First pass: collect top-level items
		foreach ( $wp_admin_bar_menu_items as $menu_bar_item ) {
			if ( ! is_object( $menu_bar_item ) ) {
				continue;
			}

			if (
				( isset( $menu_bar_item->id ) &&
				  isset( $menu_bar_item->title ) && $menu_bar_item->title &&
				  '' !== $menu_bar_item->title &&
				  ( ! isset( $menu_bar_item->parent ) || ! $menu_bar_item->parent ) &&
				  'Menu' !== wp_strip_all_tags( $menu_bar_item->title ) ) ||
				( isset( $menu_bar_item->id ) && 'user-actions' === $menu_bar_item->id )
			) {
				$title = isset( $title_map[ $menu_bar_item->id ] )
					? $title_map[ $menu_bar_item->id ]
					: ( isset( $menu_bar_item->title ) ? wp_strip_all_tags( $menu_bar_item->title ) : '' );

				$item = array(
					'id'       => sanitize_key( $menu_bar_item->id ),
					'title'    => $title,
					'children' => array(),
				);

				// Find children
				foreach ( $wp_admin_bar_menu_items as $sub_menu_bar_item ) {
					if ( ! is_object( $sub_menu_bar_item ) ) {
						continue;
					}

					if (
						isset( $sub_menu_bar_item->id ) &&
						isset( $sub_menu_bar_item->parent ) &&
						0 === strpos( $sub_menu_bar_item->parent, $menu_bar_item->id ) &&
						isset( $sub_menu_bar_item->title ) &&
						$sub_menu_bar_item->title &&
						'' !== wp_strip_all_tags( $sub_menu_bar_item->title )
					) {
						$item['children'][] = array(
							'id'    => sanitize_key( $sub_menu_bar_item->id ),
							'title' => wp_strip_all_tags( $sub_menu_bar_item->title ),
						);
					}
				}

				$admin_bar_items[] = $item;
			}
		}

		return $admin_bar_items;
	}
