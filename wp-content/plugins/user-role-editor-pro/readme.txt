=== User Role Editor Pro ===
Contributors: Vladimir Garagulya (https://www.role-editor.com)
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.4
Tested up to: 5.4.1
Stable tag: 4.56
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

= [4.56] 02.05.2020 =
* Core version: 4.54
* Fix: Front-end menu access add-on: 
* - Do not switch menu walker to custom one starting from WordPress version 5.4 as it natively supports 'wp_nav_menu_item_custom_fields' action.
* - Do not duplicate UI elements output in case 'wp_nav_menu_item_custom_fields' action is executed more than one time - (for example, by external code incompatible with WordPress 5.4).
* Core version was updated to 4.54:
* New: Quick filter hides capabilities, which do not contain search string
* Update: CSS enhancement: When site has many custom post types capabilities list section maximal height is limited by real height of the left side (capabilities groups) section, not by 720px as earlier.
* Fix: Empty list of capabilities (0/0) was shown for custom post types (CPT) which are defined with the same capability type as another CPT.
For example courses CPT from LearnDash plugin is defined with 'course' capability type (edit_courses, etc.) and other CPT from LearnDash were shown with 0/0 capabilities (lessons, topics, quizzes, certificates).

= [4.55.2] 30.03.2020 =
* Core version: 4.53.1
* Fix: bbPress roles UI elements, like "Forum role", "Change forum role to..." were empty due to fix made with version 44.55.1.

= [4.55.1] 28.03.2020 =
* Core version: 4.53.1
* New: Content view access add-on:
*   - 'ure_content_view_access_data_for_role' custom filter was added. It takes 2 parameters: 1st - array with content view access data defined for a role, $role_id - role ID, for which content view access data is filtered.
*   - 'ure_content_view_access_data_for_user' custom filter was added. It takes 2 parameters: 1st - array with content view access data defined for a user, $user_id - user ID, for which content view access data is filtered.
* New: Front-end menu access add-on: 'ure_show_front_end_menu_item' custom filter was added. It takes 3 parameters: 1st - logical, if TRUE - show menu item, 2nd - nav_menu_item data structure with checked menu item, 3rd - URE restriction data for menu item. Return false to hide menu item from current user.
* Fix: Changes were not saved to bbPress roles.
* Fix: Excluded using $this in a static method URE_Admin_Menu_Hashes::require_data_conversion(), line #179.
* Fix: Excluded using $this in a static method URE_Network_Addons_Data_Replicator::get_for_new_blog(), lines #172, #200.
* * Core version was updated to 4.53.1:
* Fix: Undefined variable: message at wp-content/plugins/user-role-editor/includes/classes/editor.php:898
* Update: Few English grammar enhancements.


Full list of changes is available in changelog.txt file.
