<?php
/*
Plugin Name: User Admin Simplifier
Plugin URI: http://www.earthbound.com/plugins/user-admin-simplifier
Description: Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.
Version: 0.6.5
Author: Adam Silverstein
Author URI: http://www.earthbound.com/plugins
License: GPLv2 or later
*/

	add_action( 'init', 'uas_init' );

	//future: implement show only user's available menus, eg. less than admins as per suggestion

	function uas_init() {
		wp_enqueue_script( 'jquery' );
		add_action( 'admin_menu', 'uas_add_admin_menu', 99 );
		add_action( 'admin_head', 'uas_edit_admin_menus', 100 );
		add_action( 'admin_head', 'uas_admin_js' );
		add_action( 'admin_head', 'uas_admin_css' );
		add_filter( 'plugin_action_links', 'uas_plugin_action_links', 10, 2 );
		add_action( 'admin_bar_menu', 'uas_edit_admin_bar_menu', 999 );
	}


	/**
	 * Filter the admin bar dropdowns.
	 */
	function uas_edit_admin_bar_menu( $wp_admin_bar ) {
		global $wp_admin_bar_menu_items;

		$wp_admin_bar_menu_items = $wp_admin_bar->get_nodes();

		return $wp_admin_bar;
	}


/*
default core:
add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 0 );
add_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 4 );
add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 7 );
// Site related.
add_action( 'admin_bar_menu', 'wp_admin_bar_sidebar_toggle', 0 );
add_action( 'admin_bar_menu', 'wp_admin_bar_wp_menu', 10 );
add_action( 'admin_bar_menu', 'wp_admin_bar_my_sites_menu', 20 );
add_action( 'admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
add_action( 'admin_bar_menu', 'wp_admin_bar_customize_menu', 40 );
add_action( 'admin_bar_menu', 'wp_admin_bar_updates_menu', 50 );
// Content related.
if ( ! is_network_admin() && ! is_user_admin() ) {
	add_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
	add_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );
}
add_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
add_action( 'admin_bar_menu', 'wp_admin_bar_add_secondary_groups', 200 );

*/function uas_edit_admin_menus() {
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

	function uas_plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$uas_links = '<a href="' . get_admin_url() . 'admin.php?page=useradminsimplifier/useradminsimplifier.php">' . esc_html__( 'Settings', 'useradminsimplifier' ) . '</a>';
			// make the 'Settings' link appear first
			array_unshift( $links, $uas_links );
		}
		return $links;
	}

	function uas_add_admin_menu() {
		add_management_page( 	esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ),
								esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ),
								'manage_options',
								'useradminsimplifier/useradminsimplifier.php',
								'useradminsimplifier_options_page' );
	}

	function uas_get_admin_options() {
		$saved_options = get_option( 'useradminsimplifier_options' );
		return is_array( $saved_options ) ? $saved_options : array();
	}

	function uas_save_admin_options( $uas_options ) {
		update_option( 'useradminsimplifier_options', $uas_options );
	}

	function uas_clean_menu_name( $menuname ) { //clean up menu names provided by WordPress
		$menuname = preg_replace( '/<span(.*?)span>/', '', $menuname ); //strip the count appended to menus like the post count
		return ( $menuname );
	}

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
			<?php esc_html_e( 'Select menus and submenus to disable for this user', 'user_admin_simplifier' ); ?>:
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

						$menuuseroption = uas_process_options( $menusectionsubmitted, $menuitem[5], $nowselected, $uas_options, $uas_selecteduser );


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
									$submenuuseroption = 0;
									if ( $menusectionsubmitted ) { //deal with submitted checkboxes
										if ( isset( $nowselected[ $uas_selecteduser ][ $combinedname ] ) ) { // selected option for this user/submenu
											$submenuuseroption = $uas_options[ $uas_selecteduser ][ $combinedname ] = $nowselected[ $uas_selecteduser ][ $combinedname ];
										}
										else {
											$uas_options[ $uas_selecteduser ][ $combinedname ] = 0;
										}
									}
									if ( isset( $uas_options[ $uas_selecteduser ][ $combinedname ] ) ) { // now show saved options for this user/submenu
										$submenuuseroption = $uas_options[ $uas_selecteduser ][ $combinedname ];
									} else {
										$uas_options[ $uas_selecteduser ][ $combinedname ] = 0;
									}
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
?>
	<hr />
		<h3>
			<?php esc_html_e( 'Select admin bar menus and submenus to disable for this user', 'user_admin_simplifier' ); ?>:
		</h3>
	<?php
	$subrowcount = 0;

//	var_dump($wp_admin_bar_menu_items);die;
	// Iterate thru each adminbar item.
	foreach( $wp_admin_bar_menu_items as $menu_bar_item ) {
		if (
			$menu_bar_item->title &&         /* exclude false */
			'' !==  $menu_bar_item->title && /* exclude blank */
			! $menu_bar_item->parent &&      /* exclude children */
			'Menu' !== wp_strip_all_tags( $menu_bar_item->title )
		) {

			$menuuseroption = uas_process_options( $menusectionsubmitted, $menu_bar_item->id, $nowselected, $uas_options, $uas_selecteduser );
		error_log($menu_bar_item->id . ' - ' . $menuuseroption );
	?>
			<p class="<?php echo ( ( 0 == $rowcount++ %2 ) ? '"menumain"' : '"menualternate"' ) ?>">
				<input type="checkbox" name="menuselection[]" id="menuselection[]" value="<?php echo sanitize_key( $menu_bar_item->id ) ?>" <?php


				checked( 1, $menuuseroption, true ); ?> />
			<?php
				echo uas_clean_menu_name( wp_strip_all_tags( $menu_bar_item->id ) );
			?>
			</p>

	<?php
			$wrapped = false;

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

					$combinedname = sanitize_key ( ( $sub_menu_bar_item->parent ? $sub_menu_bar_item->parent . '-' : '')   . $sub_menu_bar_item->id );

					$menuuseroption = uas_process_options( $menusectionsubmitted, $combinedname, $nowselected, $uas_options, $uas_selecteduser );

					echo( '<p class='. ( ( 0 == $subrowcount++ %2 ) ? '"submain"' : '"subalternate"' ) . '>' .
						'<input type="checkbox" name="menuselection[]" id="menuselection[]" ' .
						'value="'. $combinedname . '" '.
						checked( 1, $menuuseroption, false ) .
						' /> ' . uas_clean_menu_name( $sub_menu_bar_item->title ) . '</p>' );
				}
			}
			if ( $wrapped ) {
				echo '</div>';
			}

		}

	}
	?>

	<input name="uas_save" type="submit" id="uas_save" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'user_admin_simplifier' ); ?>" /> <br />
	<?php esc_html_e( 'or', 'user_admin_simplifier' ); ?>: <br />
	<input name="uas_reset" type="submit" id="uas_reset" class="button" value="<?php esc_attr_e( 'Clear User Settings', 'user_admin_simplifier' ); ?>" />
	</div>
	<?php
			}
?>
</form>
</div>
 <?php
				//error_log(json_encode($uas_options));
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
function uas_process_options( $menusectionsubmitted, $id, $nowselected, &$uas_options, $uas_selecteduser ) {
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