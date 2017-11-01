=== User Role Editor ===
Contributors: shinephp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=vladimir%40shinephp%2ecom&lc=RU&item_name=ShinePHP%2ecom&item_number=User%20Role%20Editor%20WordPress%20plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 4.37
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

User Role Editor WordPress plugin makes user roles and capabilities changing easy. Edit/add/delete WordPress user roles and capabilities.

== Description ==

With User Role Editor WordPress plugin you can change user role (except Administrator) capabilities easy, with a few clicks.
Just turn on check boxes of capabilities you wish to add to the selected role and click "Update" button to save your changes. That's done. 
Add new roles and customize its capabilities according to your needs, from scratch of as a copy of other existing role. 
Unnecessary self-made role can be deleted if there are no users whom such role is assigned.
Role assigned every new created user by default may be changed too.
Capabilities could be assigned on per user basis. Multiple roles could be assigned to user simultaneously.
You can add new capabilities and remove unnecessary capabilities which could be left from uninstalled plugins.
Multi-site support is provided.

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](http://shinephp.com)


Do you need more functionality with quality support in a real time? Do you wish to remove advertisements from User Role Editor pages? 
[Buy Pro version](https://www.role-editor.com). 
[User Role Editor Pro](https://www.role-editor.com) includes extra modules:
<ul>
<li>Block selected admin menu items for role.</li>
<li>Hide selected front-end menu items for no logged-in visitors, logged-in users, roles.</li>
<li>Block selected widgets under "Appearance" menu for role.</li>
<li>Show widgets at front-end for selected roles.</li>
<li>Block selected meta boxes (dashboard, posts, pages, custom post types) for role.</li>
<li>"Export/Import" module. You can export user roles to the local file and import them then to any WordPress site or other sites of the multi-site WordPress network.</li> 
<li>Roles and Users permissions management via Network Admin  for multisite configuration. One click Synchronization to the whole network.</li>
<li>"Other roles access" module allows to define which other roles user with current role may see at WordPress: dropdown menus, e.g assign role to user editing user profile, etc.</li>
<li>Manage user access to editing posts/pages/custom post type using posts/pages, authors, taxonomies ID list.</li>
<li>Per plugin users access management for plugins activate/deactivate operations.</li>
<li>Per form users access management for Gravity Forms plugin.</li>
<li>Shortcode to show enclosed content to the users with selected roles only.</li>
<li>Posts and pages view restrictions for selected roles.</li>
</ul>
Pro version is advertisement free. Premium support is included.

== Installation ==

Installation procedure:

1. Deactivate plugin if you have the previous version installed.
2. Extract "user-role-editor.zip" archive content to the "/wp-content/plugins/user-role-editor" directory.
3. Activate "User Role Editor" plugin via 'Plugins' menu in WordPress admin menu. 
4. Go to the "Users"-"User Role Editor" menu item and change your WordPress standard roles capabilities according to your needs.

== Frequently Asked Questions ==
- Does it work with WordPress in multi-site environment?
Yes, it works with WordPress multi-site. By default plugin works for every blog from your multi-site network as for locally installed blog.
To update selected role globally for the Network you should turn on the "Apply to All Sites" checkbox. You should have superadmin privileges to use User Role Editor under WordPress multi-site.
Pro version allows to manage roles of the whole network from the Netwok Admin.

To read full FAQ section visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/#faq) at [shinephp.com](shinephp.com).

== Screenshots ==
1. screenshot-1.png User Role Editor main form
2. screenshot-2.png Add/Remove roles or capabilities
3. screenshot-3.png User Capabilities link
4. screenshot-4.png User Capabilities Editor
5. screenshot-5.png Bulk change role for users without roles
6. screenshot-6.png Assign multiple roles to the selected users

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](shinephp.com).

= Translations =

If you wish to check available translations or help with plugin translation to your language visit this link
https://translate.wordpress.org/projects/wp-plugins/user-role-editor/


== Changelog =

= [4.37] 01.11.2017 =
* New: New option was added to the "Settings->User Role Editor->General" tab. It's possible to set a default value for a quant of columns at capabilities section of the main User Role Editor page.
* Update: User capabilities are shown for custom post types which use the WordPress built-in 'post' or 'page' capability type. (0/0) was shown earlier instead of the quantity of user capabilities really used.
* Update: Restriction was added for 'do_not_allow' user capability (used internally by WordPress). You can not add it manually.
* Fix: URE hides users with 'administrator' role by default from any other user. Quantity of users of 'All' view (tab) at 'Users' page is decreased now for the quantity of hidden administrators.

= [4.36.1] 02.10.2017 =
* Update: Direct access to the global $current_user variable was excluded. Current user data is initialized via WordPress core functions wp_get_current_user() or get_current_user_id().

= [4.36] 19.09.2017 =
* New: It's possible to set any URE's option value programmatically: use custom filter 'ure_get_option_<option_name>'. It takes a single parameter with current/default value for required options.
  Full list of User Role Editor options is available here: https://www.role-editor.com/documentation/options-list
* Update: Users page - Grant Roles. It's possible to change just "Other roles" for multiple users and leave their primary roles untouched. Just leave a "Primary role" field empty. If you select the "- No role for this site -" option from a "Primary role" drop-down list, plugin will revoke all roles from the selected users.
* Update: Options page screen help text was updated.
* Fix: Additional (other) default roles set at URE's settings page are not granted to a new user now, if they were deselected at a 'Add New User' page. 

= [4.35.3] 20.07.2017 =
* Fix: Multiple roles assignment (including default roles) did not work at "Users->Add New" new-user.php (contexts: add-existing-user, add-new-user) page for WordPress multisite.

= [4.35.2] 18.07.2017 =
* Fix: Multiple default roles (if defined at URE's settings) are selected automatically at new-user.php (context: add-new-user) page.
* Update: Code enhancement for protection of users with 'administrator' role from each other. Current user can see his own record and edit own profile.

= [4.35.1] 10.07.2017 =
* Fix: "Grant Roles" button at the bottom of "Users" page did not work as had the same ID as a similar button at the top of this page.
* Update: when bbPress plugin is active, "Grant Roles" does not revoke bbPress role granted to user anymore.
* Fix: The same ID "move_from_no_role" and "move_from_no_role_dialog" were included twice to the "Users" page.

= [4.35] 11.06.2017 =
* Update: Bulk capabilities selection checkbox is not shown for 'administrator' role for single site WP, and is shown if current user is superadmin for multisite WP. It was done to exclude sudden revoke of all capabilities from the 'administrator' role.
* Update: Full copy of JQuery UI 1.11.4 custom theme CSS file (jquery-ui.css) was included.
* Fix: User->User Role Editor page apparently loads own jQuery UI CSS (instead of use of WordPress default one) in order to exclude the conflicts with themes and plugins which can load own jQuery UI CSS globally not for own pages only.
* Fix: "Change Log" link was replaced with secure https://www.role-editor.com/changelog

= [4.34] 02.06.2017 =
* New: Multisite 'upgrade_network' capability support was added for compatibility with WordPress 4.8.
* New: Multisite 'delete_sites' capability support was added.
* Update: Users->Grant Roles: if a single user was selected for "Grant Roles" bulk action, dialog will show the current roles of selected user with checkboxes turned ON (pre-selected).
* Fix: Transients caching was removed from URE_Lib::_get_post_types() function. It cached post types list too early in some cases.
* Fix: jQuery UI CSS was updated to fix minor view inconsistency at the URE's Settings page.
* Fix: "Reset" presentation code remainders were removed from the main User Role Editor page.
* Fix: 'manage_links' capability was included into a wrong subgroup instead of "Core->General". It was a mistake in the capabilities group counters for that reason.

= [4.33] 19.05.2017 =
* Fix: "Users->Without Roles", "Users->Grant Roles" are shown only to the users with 'edit_users' capability.
* Fix: Roles were updated for all sites of all networks for WordPress multisite. "Apply to All Sites" option updates roles inside a current network only.
* Update: "Reset" button moved from the "Users->User Role Editor" main page to the "Settings->User Role Editor->Tools" tab.
* Update: "Users->Grant Roles" button worked only for superadmin or user with 'ure_manage_options' capability. User with 'edit_users' can use this feature now.
* New: boolean filter 'ure_bulk_grant_roles' allows to not show "Users->Grant Roles" button if you don't need it.
* New: boolean filter 'ure_users_select_primary_role' can hide 'Primary role' selection controls from the user profile edit page. 
* New: boolean filter 'ure_users_show_wp_change_role' can hide "Change Role" bulk action selection control from the Users page. So it's possible to configure permissions for user who can change just other roles of a user without changing his primary role.
* Update: Settings tabs and dialog stylesheets was updated to jQuery UI 1.11.4 default theme.  

= [4.32.3] 03.04.2017 =
* Fix: Boolean false was sent to WordPress core wp_enqueue_script() function as the 2nd parameter instead of an empty string. We should respect the type of parameter which code author supposed to use initially.
* Fix: Bulk grant to users multiple roles JavaScript code is loaded now for users.php page only, not globally.

= [4.32.2] 17.03.2017 =
* Fix: "Users->Grant Roles" button did not work with switched off option "Count Users without role" at "Settings->User Role Editor->Additional Modules" tab. "JQuery UI" library was not loaded.
* Update: minimal PHP version was raised to 5.3.

= [4.32.1] 09.03.2017 =
* Fix: URL to users.php page was built incorrectly after bulk roles assignment to the users selected at the 1st page of a users list.

= [4.32] 09.03.2017 =
* New: Button "Grant Roles" allows to "Assign multiple roles to the selected users" directly from the "Users" page.
* Update: singleton template was applied to the main User_Role_Editor class. While GLOBALS['user-role-editor'] reference to the instance of User_Role_Editor class is still available for the compatibility purpose, call to User_Role_Editor::get_instance() is the best way now to get a reference to the instance of User_Role_Editor class.
* Fix: Missed 'unfiltered_html' capability is shown now at the 'General' capabilities group too.

= [4.31.1] 06.01.2017 =
* Fix: WP transients get/set were removed from URE_Own_Capabilities class. It leaded to the MySQL deadlock in some cases.
* Update: Base_Lib::get_request_var() sanitizes user input by PHP's filter_var() in addition to WordPress core's esc_attr().

For full list of changes applied to User Role Editor plugin look changelog.txt file.


== Additional Documentation ==

You can find more information about "User Role Editor" plugin at [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/)

I am ready to answer on your questions about plugin usage. Use [plugin page comments](http://www.shinephp.com/user-role-editor-wordpress-plugin/) for that.
