<?php

    require_once __DIR__ .  '/EnhancedHelpMenuSettingsPage.php';

    class EnhancedHelpMenu {

        private $settingsPage;

        public function __construct() {
            if( is_network_admin() ) {
                $this->settingsPage = new EnhancedHelpMenuSettingsPage();
            } else {
                add_action( 'current_screen', array( $this, 'addCustomTab' ) );
            }
        }

        public function addCustomTab() {
            if( get_site_option('enhancedhelpmenu_status') == 1 ) {
                $title = get_site_option('enhancedhelpmenu_title');
                $content = get_site_option('enhancedhelpmenu_content');

                $screen = get_current_screen();
                $screen->add_help_tab( array(
                    'id' => 'integreat-help-tab',
                    'title' => $title,
                    'content' => $content
                ) );
            }
        }

    }

?>