<?php
/**
 * Load related files
 * Project: User Role Editor Pro WordPress plugin
 * 
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 *
**/

require_once(URE_PLUGIN_DIR .'pro/includes/classes/ajax-processor.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/utils.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/mutex.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/license-key.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugin-presence.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/bbpress.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/duplicate-post.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/wc-bookings.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/assign-role-pro.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/screen-help-pro.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/create-posts-cap.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/post-types-own-caps.php');

// Export/Import 
require_once(URE_PLUGIN_DIR .'pro/includes/classes/import-roles-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/import-validator.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/export-single-role.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/import-single-role.php');

// Additional modules:
require_once(URE_PLUGIN_DIR .'pro/includes/classes/addons-manager.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/network-addons-data-replicator.php');

// Admin menu access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-copy.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-hashes.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-url-allowed-args.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-access.php');

// Front end menu access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/front-end-menu-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/front-end-menu-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/front-end-menu-access.php');

// Navigation Menus access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/nav-menus-admin-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/nav-menus-admin-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/nav-menus-admin-access.php');

// Widgets Admin access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-admin-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-admin-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-admin-access.php');

// Widgets Show access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-show-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-show-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-show-access.php');

// Metaboxes access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/meta-boxes.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/meta-boxes-access.php');

// Other Roles access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/other-roles.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/other-roles-access.php');

// Posts edit access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-role-controller.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-role.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-user-meta.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-user.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-bulk-action.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access.php');

// Plugins access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-controller.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-role-controller.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-role.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-user-controller.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-user.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-role-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access-user-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-access.php');

// Admin page permissions viewer
require_once(URE_PLUGIN_DIR .'pro/includes/classes/page-permissions-view.php');

// Themes activation access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/themes-access.php');

// Gravity Forms Access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/gf-access.php');

// Content view restricitons
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/posts-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/posts-view-access.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions-editor.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions-posts-list.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions.php');

// Content view restrictions shortcode
require_once(URE_PLUGIN_DIR .'pro/includes/classes/content-view-shortcode-users.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/content-view-shortcode-roles.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/content-view-shortcode.php');

// Additional Capabilities
require_once(URE_PLUGIN_DIR .'pro/includes/classes/additional-caps.php');

// role/user capabilities editor Extended - for Pro version
require_once(URE_PLUGIN_DIR .'pro/includes/classes/editor-ext.php');


// User Role Editor Pro Settings - Tools
require_once(URE_PLUGIN_DIR .'pro/includes/classes/export-roles-csv.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/tools-ext.php');

// User Role Editor Pro Settings
require_once(URE_PLUGIN_DIR .'pro/includes/classes/settings-pro.php');

// User Role Editor Pro
require_once(URE_PLUGIN_DIR .'pro/includes/classes/user-role-editor-pro-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/user-role-editor-pro.php');