<?php
/*
Plugin Name: User Admin Simplifier
Plugin URI: http://www.earthbound.com/plugins/user-admin-simplifier
Description: Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.
Version: 1.0.1
Author: Adam Silverstein
Author URI: http://www.earthbound.com/plugins
License: MIT
*/

	add_action( 'init', 'uas_init' );

	//@todo show only user's available menus, eg. less than admins as per suggestion

	function uas_init() {
		global $current_user;

		wp_enqueue_script( 'jquery' );
		add_action( 'admin_menu', 'uas_add_admin_menu', 99 );
		add_action( 'admin_head', 'uas_edit_admin_menus', 100 );
		add_action( 'admin_head', 'uas_admin_js' );
		add_action( 'admin_head', 'uas_admin_css' );
		add_filter( 'plugin_action_links', 'uas_plugin_action_links', 10, 2 );
		add_action( 'admin_bar_menu', 'uas_edit_admin_bar_menu', 999 );

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
	 * Add a settings link to the pluginsd page.
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
	 * Display the options page.
	 */
	function useradminsimplifier_options_page() {
		$uas_options = uas_get_admin_options();
		$uas_selecteduser = isset( $_POST['uas_user_select'] ) ? $_POST['uas_user_select']: '';
		global $menu;
		global $submenu;
		global $current_user;
		global $storedmenu;
		global $wp_admin_bar_menu_items;

		if ( !isset( $storedmenu ) ) {
			$storedmenu = $menu;
		}
		global $storedsubmenu;
		if ( !isset( $storedsubmenu ) ) {
			$storedsubmenu = $submenu;
		}

		$nowselected = array (); //store selections to apply later in display loop where every menu option is iterated
		$menusectionsubmitted = false;
		if ( isset( $uas_options['selecteduser'] ) && $uas_options['selecteduser'] != $uas_selecteduser ) {
			//user was changed
			$uas_options['selecteduser'] = $uas_selecteduser;
		} else {
			$uas_options['selecteduser'] = $uas_selecteduser;
			// process submitted menu selections
			if ( isset ( $_POST['uas_reset'] ) ) {
				//reset options for this user by clearing all their options
				unset ( $uas_options[ $uas_selecteduser ] );
			} else {
				if ( isset ( $_POST['menuselection'] ) && is_array( $_POST['menuselection'] ) ) {
					$menusectionsubmitted = true;
					foreach ( $_POST['menuselection'] as $key => $value ) {
						$nowselected[ $uas_selecteduser ][ $value ] = 1; //disable this menu for this user
					}
				}
			}
		}

?>
<div class="wrap">
	<h2>
		<?php esc_html_e( 'User Admin Simplifier', 'user_admin_simplifier' ); ?>
	</h2>
	<form action="" method="post" id="uas_options_form" class="uas_options_form">
		<div class="uas_container" id="chooseauser">
			<h3>
				<?php esc_html_e( 'Choose a user', 'user_admin_simplifier' ); ?>:
			</h3>
			<select id="uas_user_select" name="uas_user_select" >
				<option><?php esc_html_e( 'Choose...', 'user_admin_simplifier' ); ?></option>
<?php
			$blogusers = get_users( 'orderby=nicename' );
			foreach ( $blogusers as $user ) {
				echo ( '<option value="'. $user->user_nicename . '" ' );
				echo ( ( $user->user_nicename == $uas_selecteduser ) ? 'selected' : '' );
				echo ( '>' . $user->user_nicename .  '</option>' );
			}
?>
			</select>
			</div>
<?php
	if( isset( $_POST['uas_user_select'] ) ) {
?>
	<div class="uas_container" id="choosemenus">
		<h3>
			<?php esc_html_e( 'Disable menus/submenus', 'user_admin_simplifier' ); ?>:
		</h3>
		<input class="uas_dummy" style="display:none;" type="checkbox" checked="checked" value="uas_dummy" id="menuselection[]" name="menuselection[]">
<?php
				//lets start with top level menus stored in global $menu
				//will add submenu support if needed later
				$rowcount = 0;

				// Iterate thru each menu item.
				foreach( $storedmenu as $menuitem ) {
					$menuuseroption = 0;

					// Don't process menu separators.
					if ( !( 'wp-menu-separator' == $menuitem[4] ) ) {

						// Process the values for this option.
						$menuuseroption = uas_process_option( $menusectionsubmitted, $menuitem[5], $nowselected, $uas_options, $uas_selecteduser );

						echo 	'<p class='. ( ( 0 == $rowcount++ %2 ) ? '"menumain"' : '"menualternate"' ) . '>' .
								'<input type="checkbox" name="menuselection[]" id="menuselection[]" ' . 'value="'. sanitize_key( $menuitem[5] )  .'" ' . ( 1 == $menuuseroption ? 'checked="checked"' : '' ) . ' /> ' .
								uas_clean_menu_name( $menuitem[0] ) . "</p>";
						if ( !( strpos( $menuitem[0], 'pending-count' ) ) ) { //top level menu items with pending count span don't have submenus
							$topmenu = $menuitem[2];
							if ( isset( $storedsubmenu[ $topmenu] ) ) { //display submenus
								echo ( '<div class="submenu uas-unselected"><a href="javascript:;">'. esc_html__( 'Show submenus', 'user_admin_simplifier' ) . '</a></div><div class="submenuinner">' );
								$subrowcount = 0;
								foreach ( $storedsubmenu[ $topmenu] as $subsub ) {
									$combinedname = sanitize_key( $menuitem[5] . $subsub[2] );
									$submenuuseroption = uas_process_option( $menusectionsubmitted, $combinedname, $nowselected, $uas_options, $uas_selecteduser );

									echo( '<p class='. ( ( 0 == $subrowcount++ %2 ) ? '"submain"' : '"subalternate"' ) . '>' .
										'<input type="checkbox" name="menuselection[]" id="menuselection[]" ' .
										'value="'. $combinedname . '" ' . ( 1 == $submenuuseroption ? 'checked="checked"' : '' ) .
										' /> ' . uas_clean_menu_name( $subsub[0] ) . '</p>' );
								}
								echo ( '</div>' );
							}
						}
					}
				}
	$subrowcount = 0;
	$disable_admin_bar = uas_process_option( $menusectionsubmitted, 'disable-admin-bar', $nowselected, $uas_options, $uas_selecteduser );

?>
	<hr />
		<h3>
			<?php esc_html_e( 'Disable the admin bar', 'user_admin_simplifier' ); ?>:
		</h3>
		<p class="<?php echo ( ( 0 == $rowcount++ %2 ) ? '"menumain"' : '"menualternate"' ) ?>">
			<input type="checkbox" name="menuselection[]" id="menuselection[]" value="disable-admin-bar" <?php
			checked( 1, $disable_admin_bar, true ); ?> />
		<?php
			esc_html_e( 'Completely disable the admin bar for this user.', 'user_admin_simplifier' );
		?>
		</p>		<h3>
			<?php esc_html_e( 'Disable admin bar menus/submenus', 'user_admin_simplifier' ); ?>:
		</h3>
	<?php
	$title_map = array(
		'wp-logo' => '(W)ordPress',
		'site-name' => 'Site',
		'updates' => 'Updates',
		'comments' => 'Comments',
		'new-content' => '+ New',
		'my-account' => 'User Menu',
	);


	// Move user-actions to the end of the list.
	$wp_admin_bar_menu_items[] = array_shift( $wp_admin_bar_menu_items );

	// Add some common front end actions.
	$add_for_front = array(
		'customize' => (object) array(
			'id' => 'customize',
			'title' => 'Customize',
			'parent' => false,
		),
		'edit' => (object) array(
			'id' => 'edit',
			'title' => 'Edit Page',
			'parent' => false,
		)
	);

	$wp_admin_bar_menu_items = array_merge( $wp_admin_bar_menu_items, $add_for_front );

	//var_dump($wp_admin_bar_menu_items);die;
	// Iterate thru each adminbar item.
	foreach( $wp_admin_bar_menu_items as $menu_bar_item ) {
		if (
			$menu_bar_item->title &&         /* exclude false */
			'' !==  $menu_bar_item->title && /* exclude blank */
			! $menu_bar_item->parent &&      /* exclude children */
			'Menu' !== wp_strip_all_tags( $menu_bar_item->title ) ||
			'user-actions' === $menu_bar_item->id
		) {

			// Process the values for this option.
			$menuuseroption = uas_process_option( $menusectionsubmitted, $menu_bar_item->id, $nowselected, $uas_options, $uas_selecteduser );
			$title = isset( $title_map[ $menu_bar_item->id ] ) ?
						$title_map[ $menu_bar_item->id ] :
						$menu_bar_item->title;
	?>
			<p class="<?php echo ( ( 0 == $rowcount++ %2 ) ? '"menumain"' : '"menualternate"' ) ?>">
				<input type="checkbox" name="menuselection[]" id="menuselection[]" value="<?php echo sanitize_key( $menu_bar_item->id ) ?>" <?php


				checked( 1, $menuuseroption, true ); ?> />
			<?php
				echo wp_strip_all_tags( $title );
			?>
			</p>

	<?php
			$wrapped = false;

			$subtitle_map = array(
			);

			// Add all the children of this menu item.
			foreach( $wp_admin_bar_menu_items as $sub_menu_bar_item ) {
				if (
					0 === strpos( $sub_menu_bar_item->parent, $menu_bar_item->id ) &&
					$sub_menu_bar_item->title &&
					'' !== wp_strip_all_tags( $sub_menu_bar_item->title )
				) {

					// Only add the wrapper once.
					if ( ! $wrapped ) {
						echo ( '<div class="submenu uas-unselected"><a href="javascript:;">'. esc_html__( 'Show submenus', 'user_admin_simplifier' ) . '</a></div><div class="submenuinner">' );
						$wrapped = true;
					}

					// Process the values for this option.
					$menuuseroption = uas_process_option( $menusectionsubmitted, $sub_menu_bar_item->id, $nowselected, $uas_options, $uas_selecteduser );

					$title = isset( $subtitle_map[ $sub_menu_bar_item->id ] ) ?
								$subtitle_map[ $sub_menu_bar_item->id ] :
								$sub_menu_bar_item->title;
					echo( '<p class='. ( ( 0 == $subrowcount++ %2 ) ? '"submain"' : '"subalternate"' ) . '>' .
						'<input type="checkbox" name="menuselection[]" id="menuselection[]" ' .
						'value="'. $sub_menu_bar_item->id . '" '.
						checked( 1, $menuuseroption, false ) .
						' /> ' . wp_strip_all_tags( $title ) . '</p>' );
				}
			}
			if ( $wrapped ) {
				echo '</div>';
			}
		}
	}
	?>

	<input name="uas_save" type="submit" id="uas_save" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'user_admin_simplifier' ); ?>" />

	</div>
	<?php
			}
?>
</form>
</div>
 <?php
	uas_save_admin_options( $uas_options );
	}
	function uas_admin_js() {
?>
<script type="text/javascript">
	jQuery( function() {
		jQuery( 'form#uas_options_form #uas_user_select' ).change( function() {
				jQuery( 'form#uas_options_form' ).submit();
			} )
	} );
	jQuery( document ).ready( function () {
		jQuery( 'div.submenuinner' ).slideUp( 'fast' ).hide();
		//TO-DO: makes these submenu openings persist, save state in cookies?
		jQuery( '.submenu' ).click( function() {
			inner=jQuery( this ).next( '.submenuinner' );
			if ( jQuery( inner ).is( ":hidden" ) ) {
				jQuery( inner ).show().slideDown( 'fast' );
				jQuery( this ).removeClass( 'uas-unselected' ).addClass( 'uas_selected' );
				jQuery( this ).children( 'a' ).text( '<?php esc_html_e( 'Hide submenus', 'user_admin_simplifier' )?>' );
			} else {
				jQuery( inner ).slideUp( 'fast' ).hide();
				jQuery( this ).removeClass( 'uas_selected' ).addClass( 'uas-unselected' );
				jQuery( this ).children( 'a' ).text( '<?php esc_html_e( 'Show submenus', 'user_admin_simplifier' )?>' );

			}
	} );
} );
</script>
<?php
}

/**
 * Process options.
 */
function uas_process_option( $menusectionsubmitted, $id, $nowselected, &$uas_options, $uas_selecteduser ) {
	if ( $menusectionsubmitted ) {
		if ( isset( $nowselected[ $uas_selecteduser ][ sanitize_key( $id )  ] ) ) { //any selected options for this user/menu
			$menuuseroption = $uas_options[ $uas_selecteduser ][ sanitize_key( $id ) ] = $nowselected[ $uas_selecteduser ][ sanitize_key( $id )  ] ;
		}
		else {
			$menuuseroption = $uas_options[ $uas_selecteduser ][ sanitize_key( $id ) ] = 0;
		}
	}
	if ( isset( $uas_options[ $uas_selecteduser ][ sanitize_key( $id )  ] ) ) { //any saved options for this user/menu
		$menuuseroption = $uas_options[ $uas_selecteduser ][ sanitize_key( $id )  ];
	} else {
		$menuuseroption = 0;
		$uas_options[ $uas_selecteduser ][ sanitize_key( $id ) ] = 0;
	}

	return $menuuseroption;

}

	function uas_admin_css() {
?>
<style type="text/css">

	.uas-unselected {
		background-image:url( <?php echo ( plugins_url( 'images/plus15.png', __FILE__ ) ); ?> );
	}

	.uas_selected {
		background-image:url( <?php echo ( plugins_url( 'images/minus15.png', __FILE__ ) ); ?> );
	}

	.submenu {
		margin-left:200px;
		padding-left:20px;
		font-size:12px;
		height:22px;
		width:200px;
		margin-top:-25px;
		position:absolute;
		background-repeat:no-repeat;
		background-position:left top;
	}

	.submenuinner {
		margin-left:50px;
	}

	.uas_options_form {
		font-size:14px;
	}

	.uas_options_form p {
		margin:0 0;
		padding:.5em .5em;
	}

	.uas_options_form input[type="submit"] {
		font-size:18px;
		margin-top:10px;
		margin-bottom:10px;
	}

	.uas_options_form select {
		min-width:200px;
		padding:5px;
		font-size:16px;
		height: 34px;
	}

	#choosemenus {
		border-width:1px;
		border-color:#ccc;
		padding:10px;
		border-style:solid;
	}

	.submain {
		background-color: #F3F3F3;
	}

	.subalternate {
		background-color: #ECECEC;
	}

	.menumain {
		background-color: #FCFCFC;
	}

	.menualternate {
		background-color: #DDDDDD;
	}
</style>
<?php
}