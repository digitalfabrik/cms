=== User Role Editor Pro ===
Contributors: Vladimir Garagulya (https://www.role-editor.com)
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.4
Tested up to: 4.9.6
Stable tag: 4.47.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With User Role Editor WordPress plugin you may change WordPress user roles and capabilities easy.

== Description ==

With User Role Editor WordPress plugin you can change user role capabilities easy.
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
= [4.47.1] 05.06.2018 =
* Core version: 4.43
* Core version was updated to 4.43:
* Update: references to non-existed roles are removed from the URE role additional options data storage after any role update.
* Fix: Additional options section view for the current role was not refreshed properly after other current role selection.


= [4.47] 22.05.2018 =
* Core version: 4.42
* New: support for new user capabilities introduced by WordPress 4.9.6 was added: manage_privacy_options (Settings->Privacy), export_others_personal_data (Tools->Export Personal Data), erase_others_personal_data (Tools->Erase Personal Data).
* Fix: Divi theme et_pb_layout (Divi Library) custom post type (CPT) was unavailable even for admin when "Force custom post types to use their own capabilities" URE option was turned ON.
et_pb_layout CPT is not available by default at User Role Editor pages (users.php), and we have to tell Divi to load et_pb_layout for URE pages via Divi's custom filter 'et_builder_should_load_framework'.


= [4.46] 16.05.2018 =
* Core version: 4.42
* Fix: Gravity Forms Access add-on:
*   - "Fatal error: Maximum function nesting level of 256 reached, aborting!" raised in some cases was fixed.
*   - Not allowed forms were excluded from top admin menu bar "Forms" recent forms list.
* Core version was updated to 4.42:
* Fix: Type checking was added (URE_Lib::restore_visual_composer_caps()) to fix "Warning: Invalid argument supplied for foreach() in .../user-role-editor-pro/includes/classes/ure-lib.php on line 315".


= [4.45] 07.05.2018 =
* Core version: 4.41
* New: WordPress Multisite: it's possbile to automatically copy selected add-ons data from the main site to the new created subsite. 
Use custom filter [ure_addons_to_copy_for_new_blog](https://www.role-editor.com/documentation/hooks/ure_addons_to_copy_for_new_blog/) to build list of add-on, which data URE should copy. 
* Update: Admin menu access add-on: 
*   - menu item title was shown empty at "Admin menu" window, if it began from '<span' HTML tag.
*   - blocked menu item was not removed automatically if its submenu had the only item with the same link. It was possible in case menu item used another user capability then related submenu item.
* Update: Content view restrictions add-on:
    - when you leave 'Redirect to:' parameter empty user is redirected to the login page and back to the initial URL after the successful login.
* Update: Gravity Forms access add-on:
*   - Function GFFormsModel::get_lead_table_name() call was replaced with GFFormsModel::get_entry_table_name() to provide compatibility with Gravity Forms v. 2.3+.
*   - Gravity forms entry_detail page was added to the list of pages for which URE should control the list of available Gravity Forms.
* Fix: Posts/Pages edit restrictions add-on: 
*   - infinite recursion was possible in some cases for 'pre_get_posts' action in posts-edit-access.php;
*   - "Fatal error: Call to undefined method URE_Content_View_Restrictions::is_page_template_resticted_for_role() in content-view-restrictions.php on line 581" was fixed.
* Core version was updated to 4.41:
* New: URE changes currently selected role via AJAX request, without full "Users->User Role Editor" page refresh.
* Update: All [WPBakery Visual Composer](http://vc.wpbakery.com) plugin custom user capabilities (started from 'vc_access_rules_') were excluded from processing by User Role Editor. Visual Composer loses settings made via its own "Role Manager" after the role update by User Role Editor in other case. The reason - Visual Composer stores not boolean values with user capabilities granted to the roles via own "Role Manager". User Role Editor converted them to related boolean values during role(s) update.


= [4.44] 06.04.2018 =
* Core version: 4.40.3
* Update: Integration with bbPress was enhanced.
* Core version was updated to 4.40.3:
* Update: bbPress detection and code for integration with it was updated to support multisite installations when URE is network activated but bbPress is activated on some sites of the network only. (Free version does not support bbPress roles. It excludes them from processing as bbPress creates them dynamically.)

= [4.43] 04.04.2018 =
* Core version: 4.40.1
* Fix: Core version was reversed back to 4.40.1 due to fatal error cause by 4.40.2 update for some installations with active bbPress.

= [4.42] 04.04.2018 =
* Core version: 4.40.2
* New: Content View Restrictions add-on: 
* - New criteria "Page Templates" were added to content view restrictions for roles.
* - It's possible to extend 'Any User Role (logged in only)' condition using custom filter 'ure_is_user_logged_in', in order to check the additional conditions, for example: date, user name, subdomain, etc.
* Update: Admin menu access add-on: full admin menu copy creation code priority at 'admin_menu' hook was increased from 1000 to 9999. It should be executed after other plugins. MetaSlider plugin adds its admin menu with 9553 priority.
* Fix: Content View Restrictions add-on: 
  - "Redirect to URL" option did not work for pages.
  - pages linked to BuddyPress components activity, groups, members ignored restrictions set by URE.
* Fix: Meta Boxes Access add-on: meta boxes blocking code hooked to the general 'add_meta_boxes' action had fired too early and could not block meta boxes added via 'add_meta_boxes_{post_type}' hook executed later.
*
* Core version was updated to 4.40.2:
* Update: Load required .php files from the active bbPress plugin directly, as in some cases URE code may be executed earlier than they are loaded by bbPress.

= [4.41] 06.02.2018 =
* Core version: 4.40.1
* New: Export-import module was rewritten:
* - Attention: New exported data format is not compatible with data exported by older versions.
* - Users->User Role Editor: Export/Import buttons applied to the single currently selected role only.
* - Settings->Tools-> Export/Import buttons will work for all roles of current site (main site from the network admin) in the next version.
* - It's possible to export/import add-ons data, like 'Admin Menu', 'Posts Edit', 'Post View', 'Meta Boxes'.
* New: Content View Restrictions add-on: redirect to URL option was added for access error action.
* Update: URE_Admin_Menu::load_menu_access_data_for_role() method was renamed to URE_Admin_Menu::load_data_for_role().
* Update: URE_Admin_Menu::load_menu_access_data_for_user() method was renamed to URE_Admin_Menu::load_data_for_user().
* Update: Page permissions viewer add-on: source code tracing was returned back (if available).
* Update: Other roles access add-on: changes were applied for compatibility with PHP versions prior 5.5
* Fix: Content View Restrictions add-on: 
* - HTML layout issue was fixed at the "Action" section of post editor meta box.
* - Restrictions were not applied properly for WooCommerce products list via [products] shortcode as WooCommerce may cache query result of unrestricted user for 30 days and show that cached data to users with restricted view access.
* Fix: Admin menu access add-on (URE_Admin_Menu_Access::update_menu()): bug in menu update may lead to the indefinite redirection loop as a blocked URL was selected as the 1st available menu item link.
*
* Core version was updated to 4.40.1:
* Update: use wp_roles() function from WordPress API instead of initializing $wp_roles global directly. wp_roles() function (introduced with WP 4.3) was included conditionally to URE code for backward compatibility with WordPress 4.0+
* Fix: Bug was introduced by version 4.37 with users recalculation for "All" tab after excluding users with "administrator" role. Code worked incorrectly for Japanese locale.
* Fix: WordPress multisite: bbPress plugin detection code was changed from checking bbPress API function existence to checking WordPress active plugins list. bbPress plugin activated for the site was not available yet for the network activated User Role Editor at the point of URE instance creation. URE did not work with bbPress roles as it should by design for that reason. URE (free version) should ignore bbPress roles and capabilities as the special efforts are required for this.


== Upgrade Notice ==
= [4.47.1] 05.06.2018 =
* Core version: 4.43
* Core version was updated to 4.43:
* Update: references to non-existed roles are removed from the URE role additional options data storage after any role update.
* Fix: Additional options section view for the current role was not refreshed properly after other current role selection.


