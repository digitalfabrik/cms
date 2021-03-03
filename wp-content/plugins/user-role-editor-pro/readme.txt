=== User Role Editor Pro ===
Contributors: Vladimir Garagulya (https://www.role-editor.com)
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.4
Tested up to: 5.7
Stable tag: 4.59.2
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

User Role Editor Pro WordPress plugin makes user roles and capabilities changing easy. Edit/add/delete WordPress user roles and capabilities.

== Description ==

User Role Editor Pro WordPress plugin allows you to change user roles and capabilities easy.
Just turn on check boxes of capabilities you wish to add to the selected role and click "Update" button to save your changes. That's done. 
Add new roles and customize its capabilities according to your needs, from scratch of as a copy of other existing role. 
Unnecessary self-made role can be deleted if there are no users whom such role is assigned.
Role assigned every new created user by default may be changed too.
Capabilities could be assigned on per user basis. Multiple roles could be assigned to user simultaneously.
You can add new capabilities and remove unnecessary capabilities which could be left from uninstalled plugins.
Multi-site support is provided.

== Installation ==

Installation procedure:

1. Deactivate plugin if you have the previous version installed.
2. Extract "user-role-editor-pro.zip" archive content to the "/wp-content/plugins/user-role-editor-pro" directory.
3. Activate "User Role Editor Pro" plugin via 'Plugins' menu in WordPress admin menu. 
4. Go to the "Settings"-"User Role Editor" and adjust plugin options according to your needs. For WordPress multisite URE options page is located under Network Admin Settings menu.
5. Go to the "Users"-"User Role Editor" menu item and change WordPress roles and capabilities according to your needs.

In case you have a free version of User Role Editor installed: 
Pro version includes its own copy of a free version (or the core of a User Role Editor). So you should deactivate free version and can remove it before installing of a Pro version. 
The only thing that you should remember is that both versions (free and Pro) use the same place to store their settings data. 
So if you delete free version via WordPress Plugins Delete link, plugin will delete automatically its settings data. Changes made to the roles will stay unchanged.
You will have to configure lost part of the settings at the User Role Editor Pro Settings page again after that.
Right decision in this case is to delete free version folder (user-role-editor) after deactivation via FTP, not via WordPress.

== Changelog ==

= [4.59.2] 02.03.2021 =
* Core version: 4.58.3
* Fix: "Multisite -> Update Network" did not work due to bug in version 4.59.
* Fix: Posts/pages, custom post types edit restrictions add-on: 
*    - Restricted user can some times see a full list of posts or pages due to internal caching issue.
*    - Media Library restricted items list did not take into account authors ID list restriction criteria for images loaded directly to the Media Library, which does not have parent posts.
*    - When products editing is restricted by product category/tag, product variations shown by "Admin Columns Pro - WooCommerce" plugin were not available. Now, if product is allowed, then related variations are allowed automatically too.
* Update: Option "Force custom post types to use their own capabilities" replaces default capabilities for the custom taxonomies also. 
It takes the slug of the 1st post type associated with such taxonomy (e.g. 'video') and builds own capabilities this way: manage_terms->manage_video_terms, edit_terms->edit_video_terms, delete_terms->delete_video_terms, assign_terms->assign_video_terms.
URE automatically adds such capabilities to the 'administrator' role. You have to grant these new capabilities to other roles manually.
* Update: 'edit_css' capability is mapped to 'unfiltered_html' for WordPress multisite, in case 'Enable "unfiltered_html" capability' option is turned ON at the URE's settings 'Multisite' tab. This automatically enables for a single site (blog/subsite) admin the 'Additional CSS' tab at the 'Appearance->Customize' page.
* Update: Admin menu access add-on: New custom filter 'ure_admin_menu_access_not_block_url' is available. It allows to whitelist not selected URL (without path, like admin.php?page=mlw_quiz_options), which is not presented at the admin menu and it's not possible to select them with the "Block not Selected" model.
* Core version was updated to version 4.58.3
* Update: URE automatically adds custom taxonomies user capabilities to administrator role before opening "Users->User Role Editor" page.
* Fix: Role changes were not saved with option "Confirm role update" switched off. 

= [4.59.1] 26.01.2021 =
* Core version 4.58.2
* Fix: Import single role: Uncaught TypeError: $ is not a function at HTMLDivElement.Import (import-single-role.js?ver=4.59:46) was fixed.

= [4.59] 24.01.2021 =
* Core version 4.58.2
* Update: Admin menu access add-on: Update button saves changes via AJAX without full page reload.
* Update: Nav menus admin access add-on: Update button saves changes via AJAX without full page reload.
* Update: Widgets admin access add-on: Update button saves changes via AJAX without full page reload.
* Update: All JavaScript files are loaded with URE plugin version number as a query string for cache busting purpose.
* Fix: Widgets admin access add-on: "PHP Warning:  A non-numeric value encountered in /wp-content/plugins/user-role-editor-pro/pro/includes/classes/widgets-admin-view.php on line 189" was fixed.
* Fix: "JQMIGRATE: jQuery.fn.click() event shorthand is deprecated" notice was fixed.
* Fix: "JQMIGRATE: jQuery.fn.submit() event shorthand is deprecated" notice was fixed.
* Core version was updated to 4.58.2
* Update: Users->User Role Editor: Update button saves changes via AJAX without full page reload.
* Fix: New user registered via frontend (wp-login.php?action=register) automatically receives additional (other) default role(s) according to selection made at User Role Editor settings "Other default roles" tab.
* Fix: "JQMIGRATE: jquery.fn.resize() event shorthand is deprecated" notice was fixed.
* Fix: "JQMIGRATE: Number-typed values are deprecated for jQuery.fn.css( (property name), value )" notice was fixed.

Full list of changes is available in changelog.txt file.
