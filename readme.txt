=== User Admin Simplifier ===
Contributors: adamsilverstein
Donate link:
Tags: admin simplify menus submenus
Requires at least: 3.0.1
Tested up to: 4.0.0
Stable tag: 0.6.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.

== Description ==

Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.

== Installation ==

Install User Admin Simplifier either via the WordPress.org plugin directory, or by uploading the files to your server
That’s it. You’re ready to go! To edit your users menus, go to Tools->User Admin Simplifier

Submenus are now available for disabling.  Note that disabling a top level menu removes it, no submenu items will be available. On the other hand, disabling all submenu items does not disable the top level menu.

Unininstalling and deleting the plugin will remove all of its settings.

== Frequently Asked Questions ==

= Does it work with WordPress Multisite? =

Yes! In a multisite install, User Admin Simplifier works as follows:

* plugin works on a per site/user basis
* logging into dashboard for a specific site and visiting the plugin admin only shows the users with access to that site
* restricting menus for users works as expected and disables menus for user on that site only
* disabling menus for a user only affects current site. user's menus remain unaffected in other sites

== Upgrade Notice ==

= 0.6.3 =
Tested up to WordPress 4.0.0.
Added icons for WordPress 4.0 plugin installer.

= 0.6.2 =
Tested up to WordPress 3.9.1.

= 0.6.1 =
Make css class for +/- more specific to avoid conflicts.

== Screenshots ==

1. Choose a user to edit their menus
2. Check the menu section to disable. Click 'Save Changes' to apply your settings. Click 'Clear User Settings' to reset the disabled menus for the selected user.

== Changelog ==

= 0.6.3 =
Add icons for 4.0 plugin browser.
Tested up to WordPress 4.0.

= 0.6.2 =
Tested up to WordPress 3.9.1.

= 0.6.1 =
Make css class for +/- more specific to avoid conflicts.

= 0.6 =
Fixes issue with removing certain submenus, use sanitize_key for all key values.

= 0.5.4 =
Corrects a bug where $menu was unset causing an error message with some themes/plugins; added sanity isset check for $menu before trying to use.

= 0.5.3 =
Bug fixes, last update broke plugin for some users; also fixed issue with user_login used in one spot, user_nicename used in another. Code cleanup.

= 0.5.2 =
Bug fix.

= 0.5.1 =
* Corrected issue that prevented Appearance->Editor from showing up (at all).

= 0.4.3 =
* code cleanup

= 0.4.2 =
* added code to handle submenus as well as top level menus
* moved main plugin menu to Tools section of Dashboard

= 0.3.3 =
* corrected typo/internationalization issue

= 0.3.2 =
* bug fixes


= 0.3.1 =
* updated code for Clear User Settings button to clear settings for selected user
* updated plugin admin, added priorities - uses stored menu if menu already modified, that way current user can always see all menu items


= 0.3 =
* added uninistall file - will now remove options when deleting plugin
* added reset user options button to clear all checkmarks for selected user

= 0.23 =
* initial release.
