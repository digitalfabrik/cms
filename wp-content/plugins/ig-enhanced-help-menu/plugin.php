<?php
/*
Plugin Name: Enhanced help menu
Description: Shows wordpress help menu on every view and adds a custom tab that can be edited via network admin
Author:      Sascha Beele
*/

if( is_admin() ) {
    require_once __DIR__ .  '/EnhancedHelpMenu.php';
    $clickGuide = new EnhancedHelpMenu();
}

?>