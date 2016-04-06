<?php

    class EnhancedHelpMenuSettingsPage {

        // Holds the values to be used in the fields callbacks
        private $enhancedhelpmenu_status;
        private $enhancedhelpmenu_title;
        private $enhancedhelpmenu_content;

        public function __construct() {
            add_action('network_admin_menu', array($this, 'add_plugin_page'));
            add_action('admin_init', array($this, 'page_init'));

            add_action('network_admin_menu',  array($this, 'saveSettings'));
        }

        // Save settings
        function saveSettings() {

            // save status
            if( isset( $_POST['enhancedhelpmenu_status'] ) and !empty( $_POST['enhancedhelpmenu_status'] ) ) {
                update_site_option( 'enhancedhelpmenu_status', 1 );
            } else {
                update_site_option( 'enhancedhelpmenu_status', 0 );
            }

            // save title
            if( isset( $_POST['enhancedhelpmenu_title'] ) ) {
                update_site_option( 'enhancedhelpmenu_title', $_POST['enhancedhelpmenu_title'] );
            }

            // save content
            if( isset( $_POST['enhancedhelpmenu_content'] ) ) {
                update_site_option( 'enhancedhelpmenu_content', $_POST['enhancedhelpmenu_content'] );
            }
        }

        // add options page
        public function add_plugin_page() {
            add_submenu_page(
                'settings.php',
                'Enhanced Help Menu',
                'Enhanced Help Menu',
                'manage_options', // capability
                'enhancedhelpmenu-optionspage',
                array( $this, 'create_admin_page' )
            );
        }

        // options page callback
        public function create_admin_page() {
            $this->enhancedhelpmenu_status = get_site_option( 'enhancedhelpmenu_status' );
            $this->enhancedhelpmenu_title = get_site_option( 'enhancedhelpmenu_title' );
            $this->enhancedhelpmenu_content = get_site_option( 'enhancedhelpmenu_content' );
            ?>
            <div class="wrap">
                <h2>Enhanced Help Menu</h2>
                <form method="post" action="settings.php?page=enhancedhelpmenu-optionspage">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields( 'enhancedhelpmenu_option_group' );
                    do_settings_sections( 'enhancedhelpmenu_settings' );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        // register and add settings
        public function page_init() {
            register_setting(
                'enhancedhelpmenu_option_group', // Option group
                'enhancedhelpmenu_options' // Option name
            );

            add_settings_section(
                'enhancedhelpmenu_section_id', // ID
                'Custom tab', // Title
                array( $this, 'print_section_info' ), // Callback
                'enhancedhelpmenu_settings' // Page
            );

            add_settings_field(
                'status',
                'Enable custom tab',
                array( $this, 'status_callback' ),
                'enhancedhelpmenu_settings',
                'enhancedhelpmenu_section_id'
            );

            add_settings_field(
                'title',
                'Title',
                array( $this, 'title_callback' ),
                'enhancedhelpmenu_settings',
                'enhancedhelpmenu_section_id'
            );

            add_settings_field(
                'content', // ID
                'Content', // Title
                array( $this, 'content_callback' ), // Callback
                'enhancedhelpmenu_settings', // Page
                'enhancedhelpmenu_section_id' // Section
            );
        }

        // print section text
        public function print_section_info() {
            print 'A custom tab can be created in WordPress help menu by the following fields. Once a title is specified, the tab is displayed.';
        }

        public function status_callback() {
            if( $this->enhancedhelpmenu_status == 1 ) {
                print( '<input type="checkbox" id="enhancedhelpmenu_status" name="enhancedhelpmenu_status" checked />' );
            } else {
                print( '<input type="checkbox" id="enhancedhelpmenu_status" name="enhancedhelpmenu_status" />' );
            }
        }

        public function title_callback() {
            printf(
                '<input type="text" id="enhancedhelpmenu_title" name="enhancedhelpmenu_title" value="%s" style="width:100%% !important;" />',
                isset( $this->enhancedhelpmenu_title ) ? esc_attr( $this->enhancedhelpmenu_title) : ''
            );
        }

        public function content_callback() {
            echo wp_editor( $this->enhancedhelpmenu_content, 'enhancedhelpmenu_content', array('textarea_name' => 'enhancedhelpmenu_content','wpautop' => false)  );
            /*
            printf(
                '<input type="text" id="content" name="enhancedhelpmenu_options[content]" value="%s" />',
                isset( $this->options['content'] ) ? esc_attr( $this->options['content']) : ''
            );
            */
        }

    }

?>