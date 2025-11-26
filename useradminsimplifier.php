<?php
/*
Plugin Name: User Admin Simplifier
Plugin URI: http://www.earthbound.com/plugins/user-admin-simplifier
Description: Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.
Version: 2.0.0
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

		// Remove the admin bar?
		$uas_options = uas_get_admin_options();
		if (
			isset( $uas_options[ $current_user->user_nicename ] ) &&
			isset( $uas_options[ $current_user->user_nicename ][ 'disable-admin-bar' ] ) &&
			1 === $uas_options[ $current_user->user_nicename ][ 'disable-admin-bar' ]
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
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$user = isset( $_POST['user'] ) ? sanitize_text_field( wp_unslash( $_POST['user'] ) ) : '';
		$options_json = isset( $_POST['options'] ) ? sanitize_text_field( wp_unslash( $_POST['options'] ) ) : '{}';
		$options = json_decode( $options_json, true );

		if ( empty( $user ) ) {
			wp_send_json_error( array( 'message' => 'No user specified' ) );
		}

		$uas_options = uas_get_admin_options();
		$uas_options[ $user ] = is_array( $options ) ? array_map( 'intval', $options ) : array();
		uas_save_admin_options( $uas_options );

		wp_send_json_success( array( 'message' => 'Options saved successfully' ) );
	}

	/**
	 * AJAX handler for resetting user options.
	 */
	function uas_ajax_reset_user() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'uas_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$user = isset( $_POST['user'] ) ? sanitize_text_field( wp_unslash( $_POST['user'] ) ) : '';

		if ( empty( $user ) ) {
			wp_send_json_error( array( 'message' => 'No user specified' ) );
		}

		$uas_options = uas_get_admin_options();
		unset( $uas_options[ $user ] );
		uas_save_admin_options( $uas_options );

		wp_send_json_success( array( 'message' => 'User settings reset successfully' ) );
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
		$uas_options = uas_get_admin_options();

		// Remove nodes for the current user.
		foreach( $wp_admin_bar_menu_items as $menu_item ) {
			if (
				isset( $uas_options[ $current_user->user_nicename ] ) &&
				isset( $uas_options[ $current_user->user_nicename ][ $menu_item->id ] ) &&
				1 === $uas_options[ $current_user->user_nicename ][ $menu_item->id ]
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
		$uas_options = uas_get_admin_options();
		$newmenu = array();
		if ( ! isset( $menu ) )
			return false;
		//rebuild menu based on saved options
		foreach ( $menu as $menuitem ) {
			if ( isset( $menuitem[5] ) && isset( $uas_options[ $current_user->user_nicename ][ sanitize_key( $menuitem[5] )  ] ) &&
					1 == $uas_options[ $current_user->user_nicename ][ sanitize_key( $menuitem[5] )  ] ) {
				remove_menu_page( $menuitem[2] );
			} else {
				// lets check the submenus
				if ( isset ( $storedsubmenu[ $menuitem[2] ] ) ) {
					foreach ( $storedsubmenu[ $menuitem[2] ] as $subsub ) {
						$combinedname = sanitize_key( $menuitem[5] . $subsub[2] );
						if  ( isset ( $subsub[2] ) && isset( $uas_options[ $current_user->user_nicename ][ $combinedname ] ) &&
							1 == $uas_options[ $current_user->user_nicename ][ $combinedname ] ) {
							remove_submenu_page( $menuitem[2], $subsub[2] );
						}
					}
				}
			}
		}
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
	 * Helper function to clean menu names.
	 *
	 * @param  string $menuname The stored menu name.
	 *
	 * @return string           The processed menu name.
	 */
	function uas_clean_menu_name( $menuname ) { //clean up menu names provided by WordPress
		$menuname = preg_replace( '/<span(.*?)span>/', '', $menuname ); //strip the count appended to menus like the post count
		return ( $menuname );
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
				( isset( $menu_bar_item->title ) && $menu_bar_item->title &&
				  '' !== $menu_bar_item->title &&
				  ( ! isset( $menu_bar_item->parent ) || ! $menu_bar_item->parent ) &&
				  'Menu' !== wp_strip_all_tags( $menu_bar_item->title ) ) ||
				( isset( $menu_bar_item->id ) && 'user-actions' === $menu_bar_item->id )
			) {
				$title = isset( $title_map[ $menu_bar_item->id ] )
					? $title_map[ $menu_bar_item->id ]
					: wp_strip_all_tags( $menu_bar_item->title );

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
