<?php
define( 'WP_INSTALLER_VERSION', '1.8.10' );

include_once dirname( __FILE__ ) . '/includes/functions-core.php';
include_once dirname( __FILE__ ) . '/includes/class-wp-installer.php';

include_once WP_Installer()->plugin_path() . '/includes/class-wp-installer-api.php';
include_once WP_Installer()->plugin_path() . '/includes/class-translation-service-info.php';
include_once WP_Installer()->plugin_path() . '/includes/class-installer-dependencies.php';
include_once WP_Installer()->plugin_path() . '/includes/class-wp-installer-channels.php';

include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-components-sender.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-components-storage.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-components-hooks.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-share-local-components-setting.php';

include_once WP_Installer()->plugin_path() . '/templates/template-service/interface-iotgs-installer-template-service.php';
include_once WP_Installer()->plugin_path() . '/templates/template-service/class-otgs-installer-twig-template-service.php';
include_once WP_Installer()->plugin_path() . '/templates/template-service/class-otgs-installer-twig-template-service-loader.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-components-setting-templates.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-components-setting-resources.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-plugins-page-notice.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-wp-components-setting-ajax.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-filename-hooks.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-php-functions.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-icons.php';

include_once WP_Installer()->plugin_path() . '/includes/functions-templates.php';

// Initialization
WP_Installer();
WP_Installer_Channels();

$local_components_resources = new OTGS_Installer_WP_Components_Setting_Resources( WP_Installer() );
$local_components_resources->add_hooks();

$local_components_setting = new OTGS_Installer_WP_Share_Local_Components_Setting();
$local_components_sender  = new OTGS_Installer_WP_Components_Sender(
	WP_Installer(),
	$local_components_setting
);

$local_components_storage = new OTGS_Installer_WP_Components_Storage();
$local_components_hooks   = new OTGS_Installer_WP_Components_Hooks( $local_components_storage, $local_components_sender, $local_components_setting );
$local_components_hooks->add_hooks();

$local_components_ajax_setting = new OTGS_Installer_WP_Components_Setting_Ajax(
	$local_components_setting,
	WP_Installer()
);
$local_components_ajax_setting->add_hooks();

$filename_hooks = new OTGS_Installer_Filename_Hooks( new OTGS_Installer_PHP_Functions() );
$filename_hooks->add_hooks();

$icons = new OTGS_Installer_Icons( WP_Installer() );
$icons->add_hooks();
