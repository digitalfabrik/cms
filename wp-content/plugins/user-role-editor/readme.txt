=== User Role Editor ===
Contributors: shinephp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=vladimir%40shinephp%2ecom&lc=RU&item_name=ShinePHP%2ecom&item_number=User%20Role%20Editor%20WordPress%20plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.0
Tested up to: 4.3
Stable tag: trunk
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
Русская версия этой статьи доступна по адресу [ru.shinephp.com](http://ru.shinephp.com/user-role-editor-wordpress-plugin-rus/)

Do you need more functionality with quality support in real time? Do you wish remove advertisements from User Role Editor pages? 
Buy [Pro version](https://www.role-editor.com). 
Pro version includes extra modules:
<ul>
<li>Block selected admin menu items for role.</li>
<li>Block selected widgets under "Appearance" menu for role.</li>
<li>"Export/Import" module. You can export user roles to the local file and import them then to any WordPress site or other sites of the multi-site WordPress network.</li> 
<li>Roles and Users permissions management via Network Admin  for multisite configuration. One click Synchronization to the whole network.</li>
<li>"Other roles access" module allows to define which other roles user with current role may see at WordPress: dropdown menus, e.g assign role to user editing user profile, etc.</li>
<li>Per posts/pages users access management to post/page editing functionality.</li>
<li>Per plugin users access management for plugins activate/deactivate operations.</li>
<li>Per form users access management for Gravity Forms plugin.</li>
<li>Shortcode to show enclosed content to the users with selected roles only.</li>
<li>Posts and pages view restrictions for selected roles.</li>
</ul>
Pro version is advertisement free. Premium support is included. It is provided by User Role Editor plugin developer Vladimir Garagulya. You will get an answer on your question not once a week, but in 24 hours or quicker.

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

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](shinephp.com).

= Translations =
* Dutch: Gerhard Hoogterp;
* French: [Transifex](https://www.transifex.com);
* Hebrew: [atar4u](http://atar4u.com);
* Hungarian: Németh Balázs;
* Italian: [Giuseppe Velardo](http://www.comprensivoleopardi.gov.it/);
* Persian: [Morteza](https://wordpress.org/support/profile/mo0orteza);
* Russian: [Vladimir Garagulya](https://www.role-editor.com)
* Spanish: [Dario Ferrer](http://darioferrer.com/) - needs update;
* Turkish: [Transifex](https://www.transifex.com).


Dear plugin User!
If you wish to help me with this plugin translation I very appreciate it. Please send your language .po and .mo files to vladimir[at-sign]shinephp.com email. Do not forget include you site link in order to show it with greetings for the translation help in this readme.txt file.
Some translations may be outdated. If you have better translation for some phrases, send it to me and it will be taken into consideration. You are welcome!


== Changelog ==

= [4.19.1] 20.08.2015 =
* Default role value has not been refreshed automatically after change at the "Default Role" dialog - fixed.
* More detailed notice messages are shown after default role change - to reflect a possible error or problem.
* Other default roles (in addition to the primary role) has been assigned to a new registered user for requests from the admin back-end only. Now this feature works for the requests from the front-end user registration forms too.

= 4.19 =
* 28.07.2015
* It is possible to assign to the user multiple roles directly through a user profile edit page. 
* Custom SQL-query (checked if the role is in use and slow on the huge data) was excluded and replaced with WordPress built-in function call. [Thanks to Aaron](https://wordpress.org/support/topic/poorly-scaling-queries).
* Bulk role assignment to the users without role was rewritten for cases with a huge quant of users. It processes just 50 users without role for the one request to return the answer from the server in the short time. The related code was extracted to the separate class.
* Code to fix JavaScript and CSS compatibility issues introduced by other plugins and themes, which load its stuff globally, was extracted into the separate class.
* Custom filters were added: 'ure_full_capabilites' - takes 1 input parameter, array with a full list of user capabilities visible at URE, 'ure_built_in_wp_caps' - takes 1 input parameter, array with a list of WordPress core user capabilities. These filters may be useful if you give access to the URE for some not administrator user, and wish to change the list of capabilities which are available to him at URE.
* Dutch translation was updated. Thanks to Gerhard Hoogterp.

= 4.18.4 =
* 30.04.2015
* Calls to the function add_query_arg() is properly escaped with esc_url_raw() to exclude potential XSS vulnerabilities. Nothing critical: both calls of add_query_arg() are placed at the unused sections of the code.
* Italian translation was updated. Thanks to Leo.

= 4.18.3 =
* 24.02.2015
* Fixed PHP fatal error for roles reset operation.
* Fixed current user capability checking before URE Options page open.
* 3 missed phrases were added to the translations files. Thanks to [Morteza](https://wordpress.org/support/profile/mo0orteza)
* Hebrew translation updated. Thanks to [atar4u](http://atar4u.com)
* Persian translation updated. Thanks to [Morteza](https://wordpress.org/support/profile/mo0orteza)

= 4.18.2 =
* 06.02.2015
* New option "Edit user capabilities" was added. If it is unchecked - capabilities section of selected user will be shown in the readonly mode. Administrator (except superadmin for multisite) can not assign capabilities to the user directly. He should make it using roles only.
* More universal checking applied to the custom post type capabilities creation to exclude not existing property notices.
* Multisite: URE's options page is prohibited by 'manage_network_users' capability instead of 'ure_manage_options' in case single site administrators does not have permission to use URE.
* URE protects administrator user from editing by other users by default. If you wish to turn off such protection, you may add filter 'ure_supress_administrators_protection' and return 'true' from it.
* Plugin installation to the WordPress multisite with large (thousands) subsites had a problem with script execution time. Fixed. URE does not try to update all subsites at once now. It does it for every subsite separately, only when you visit that subsite.
* Fixed JavaScript bug with 'Reset Roles' for FireFox v.34.


= 4.18.1 =
* 14.12.2014
* As activation hook does not fire during bulk plugins update, automatic plugin version check and upgrade execution were added.

= 4.18 =
* 14.12.2014
* Own custom user capabilities, e.g. 'ure_edit_roles' are used to restrict access to User Role Editor functionality ([read more](https://www.role-editor.com/user-role-editor-4-18-new-permissions/)).
* If custom post type uses own custom user capabilities URE add them to the 'Custom Capabilities' section automatically.
* Multisite: You may allow to the users without superadmin privileges to add/create site users without sending them email confirmation request.
* Bug fix: when non-admin user updated other user profile, that user lost secondary roles.
* Italian translation was added. Thanks to [Giuseppe Velardo](http://www.comprensivoleopardi.gov.it/).

= 4.17.3 =
* 23.11.2014
* French and Turkish translation were updated. Thanks to [Transifex](https://www.transifex.com) translation team.

= 4.17.2 =
* 21.10.2014
* Notice: "Undefined property: Ure_Lib::$pro in .../class-user-role-editor.php on line 550" was fixed.
* Settings help screen text was updated.
* Russian translation was updated.
* Hungarian translation was updated. Thanks to Németh Balázs.
* French and Turkish translation were updated. Thanks to [Transifex](https://www.transifex.com) translation team.


= 4.17.1 =
* 01.10.2014
* Bug fix for the PHP Fatal error: Call to undefined function is_plugin_active_for_network(). It may take place under multisite only, 
in case no one of the other active plugins load file with this function already before User Role Editor v. 4.17 tries to call it.

= 4.17 =
* 01.10.2014
* Multisite (update for cases when URE was not network activated): It is possible to use own settings for single site activated instances of User Role Editor. 
  Earlier User Role Editor used the settings values from the main blog only located under "Network Admin - Settings".
  Some critical options were hidden from the "Multisite" tab for single site administrators and visible to the superadmin only. 
  Single site admin should not have access to the options which purpose is to restrict him.
  Important! In case you decide to allow single site administrator activate/deactivate User Role Editor himself, setup this PHP constant at the wp-config.php file:
  define('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE', 1);
  Otherwise single site admin will not see User Role Editor in the plugins list after its activation. User Role Editor hides itself under multisite from all users except superadmin by default.
* Help screen for the Settings page was updated.
* Hungarian translation was added. Thanks to Németh Balázs.
* Dutch translation was added. Thanks to Arjan Bosch.

  
= 4.16 =
* 11.09.2014
* "create_sites" user capability was added to the list of built-in WordPress user capabilities for WordPress multisite. It does not exist by default. But it is used to control "Add New" button at the "Sites" page under WordPress multisite network admin.
* bug fix: WordPress database prefix value was not used in 2 SQL queries related to the "count users without role" module - updated.

= 4.15 =
* 08.09.2014
* Rename role button was added to the URE toolbar. It allows to change user role display name (role ID is always the same). Be careful and double think before rename some built-in WordPress role.

= 4.14.4 =
* 08.08.2014
* Missed "manage_sites" user capability was added to the list of built-in WordPress capabilities managed by User Role Editor.
* Russian translation was updated.

= 4.14.3 =
* 25.07.2014
* Integer "1" as default capability value for new added empty role was excluded for the better compatibility with WordPress core. Boolean "true" is used instead as WordPress itself does.
* Integration with Gravity Forms permissions system was enhanced for WordPress multisite.

= 4.14.2 =
* 18.07.2014
* The instance of main plugin class User_Role_Editor is available for other developers now via $GLOBALS['user_role_editor']
* Compatibility issue with the theme ["WD TechGoStore"](http://wpdance.com) is resolved. This theme loads its JS and CSS stuff for admin backend uncoditionally - for all pages. While the problem is caused just by CSS URE unloads all this theme JS and CSS for optimizaiton purpose for WP admin backend pages where conflict is possible.

= 4.14.1 =
* 13.06.2014
* MySQL query optimizing to reduce memory consumption. Thanks to [SebastiaanO](http://wordpress.org/support/topic/allowed-memory-size-exhausted-fixed).
* Extra WordPress nonce field was removed from the post at main role editor page to exclude nonce duplication.
* Minor code enhancements.
* Fixes for some missed translations.

= 4.14 =
* 16.05.2014
* Persian translation was added. Thanks to Morteza.

= 4.12 =
* 22.04.2014
* Bug was fixed. It had prevented bulk move users without role (--No role for this site--) to the selected role in case such users were shown more than at one WordPress Users page.
* Korean translation was added. Thanks to [Taek Yoon](http://www.ajinsys.com).
* Pro version update notes:
* Use new "Admin Menu" button to block selected admin menu items for role. You need to activate this module at the "Additional Modules". This feature is useful when some of submenu items are restricted by the same user capability,
e.g. "Settings" submenu, but you wish allow to user work just with part of it. You may use "Admin Menu" dialog as the reference for your work with roles and capabilities as "Admin Menu" shows 
what user capability restrict access to what admin menu item.
* Posts/Pages edit restriction feature does not prohibit to add new post/page now. Now it should be managed via 'create_posts' or 'create_pages' user capabilities.
* If you use Posts/Pages edit restriction by author IDs, there is no need to add user ID to allow him edit his own posts or page. Current user is added to the allowed authors list automatically.
* New tab "Additional Modules" was added to the User Role Editor options page. As per name all options related to additional modules were moved there.

= 4.11 =
* 06.04.2014
* Single-site: It is possible to bulk move users without role (--No role for this site--) to the selected role or automatically created role "No rights" without any capabilities. Get more details at http://role-editor.com/no-role-for-this-site/
* Plugin uses for dialogs jQuery UI CSS included into WordPress package.
* Pro version: It is possible to restrict editing posts/pages by its authors user ID (targeted user should have edit_others_posts or edit_others_pages capability).
* Pro version, multi-site: Superadmin can setup individual lists of themes available for activation to selected sites administrators.
* Pro version, Gravity Forms access restriction module was tested and compatible with Gravity Forms version 1.8.5

= 4.10 =
* 15.02.2014
* Security enhancement: WordPress text translation functions were replaced with more secure esc_html__() and esc_html_e() variants.
* Pro version: It is possible to restrict access to the post or page content view for selected roles. Activate the option at plugin "Settings" page and use new "Content View Restrictions" metabox at post/page editor to setup content view access restrictions.
* Pro version: Gravity Forms access management module was updated for compatibility with Gravity Forms version 1.8.3. If you need compatibility with earlier Gravity Forms versions, e.g. 1.7.9, use User Role Editor version 4.9.


= 4.9 =
* 19.01.2014
* New tab "Default Roles" was added to the User Role Editor settings page. It is possible to select multiple default roles to assign them automatically to the new registered user.
* CSS and dialog windows layout various enhancements.
* 'members_get_capabilities' filter was applied to provide better compatibility with themes and plugins which may use it to add its own user capabilities.
* jQuery UI CSS was updated to version 1.10.4.
* Pro version: Option was added to download jQuery UI CSS from the jQuery CDN.
* Pro version: Bug was fixed: Plugins activation assess restriction section was not shown for selected user under multi-site environment.


Click [here](http://role-editor.com/changelog)</a> to look at [the full list of changes](http://role-editor.com/changelog) of User Role Editor plugin.


== Additional Documentation ==

You can find more information about "User Role Editor" plugin at [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/)

I am ready to answer on your questions about plugin usage. Use [plugin page comments](http://www.shinephp.com/user-role-editor-wordpress-plugin/) for that.
