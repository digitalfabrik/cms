<?php 

require_once __DIR__ . '/ClickGuideTour.php';
require_once __DIR__ . '/ClickGuideWaypoint.php';

class ClickGuideOptionsPage {

    private $clickguideStatus;
    private $welcomePopUpMessage;
    private $nameForClickGuide;

    public function __construct() {
        add_action( 'network_admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    // Add options page
    public function add_plugin_page() {
        add_submenu_page(
            'settings.php',
            'Klick Guide', 
            'Klick Guide', 
            'manage_options', // capability
            'clickguide-optionspage', 
            array( $this, 'create_admin_page' )
        );
    }

    // Options page callback
    public function create_admin_page()
    {
        $this->clickguideStatus = get_site_option( 'clickguide_status' );
        $this->welcomePopUpMessage = get_option( 'welcome_popup_message' );
        $this->nameForClickGuide = get_option( 'clickguide_naming' );
        ?>
        <div class="wrap optionsPageWrap">
            <h2>Klick Guide</h2>    
            <?php require_once __DIR__ . '/templates/optionsPage.php'; ?>
        </div>
        <?php
    }

    // Register and add settings
    public function page_init() {        

        // Register Settings Welcome PopUp
        register_setting(
            'welcome_popup_group', // Option group
            'welcome_popup', // Option name
            array( $this, 'welcomePopUpSanitize' ) // Sanitize
        );

        // Welcome PopUp (Fields & Section)
        add_settings_section(
            'welcome_section', // ID
            'Willkommensnachricht (PopUp)', // Title
            array( $this, 'print_welcome_section_info' ), // Callback
            'clickguide-optionspage-welcomemessage' // Page
        );

        add_settings_field(
            'clickguide_status', // ID
            'Status', // Title
            array( $this, 'clickguide_status_callback' ), // Callback
            'clickguide-optionspage-welcomemessage', // Page
            'welcome_section' // Section
        );

        add_settings_field(
            'welcome_message', // ID
            'Willkommensnachricht', // Title 
            array( $this, 'welcome_message_callback' ), // Callback
            'clickguide-optionspage-welcomemessage', // Page
            'welcome_section' // Section           
        );

        add_settings_field(
            'clickguide_naming', // ID
            'Bezeichnung für den clickguide', // Title
            array( $this, 'clickguide_naming_callback' ), // Callback
            'clickguide-optionspage-welcomemessage', // Page
            'welcome_section' // Section
        );

    }
  
    // Section Description
    public function print_welcome_section_info() {
        print 'Beim Aufruf des Dashboards wird ein PopUp angezeigt, dass auf den Klick-Guide aufmerksam macht. Das PopUp wird nur angezeigt, wenn der Benutzer die Anzeige nicht durch "Diese Meldung nicht mehr anzeigen" unterbunden hat. Nachfolgend kann der einleitende Text dieses PopUps festgelegt werden. Unter diesem Text werden alle Touren in der definierten Reihenfolge aufgelistet.';
    }

    // Callback for clickguide status
    public function clickguide_status_callback() {
        if( $this->clickguideStatus == 1 ) {
            print( '<input type="checkbox" id="clickguide_status" name="clickguide_status" checked />' );
        } else {
            print( '<input type="checkbox" id="clickguide_status" name="clickguide_status" />' );
        }
    }

    // Callback for welcome message
    public function welcome_message_callback() {
    	echo wp_editor( $this->welcomePopUpMessage, 'welcome_message', array('textarea_name' => 'welcome_popup_message','wpautop' => false)  );
    }

    // Callback for clickguide Naming
    public function clickguide_naming_callback() {
        printf(
            '<input type="text" id="clickguide_naming" name="clickguide_naming" value="%s" style="width:100%%;" />',
            isset( $this->nameForClickGuide ) ? esc_attr( $this->nameForClickGuide ) : ''
        );
    }

    /** 
     * Sanitize fields of welcome popup
     *
     * @param array $input Contains welcome message
     */
    public function welcomePopUpSanitize( $input ) {
        $new_input = array();
        if( isset( $input['welcome_popup_message'] ) ) {
            $new_input['welcome_popup_message'] = wp_kses_post( $input['welcome_popup_message'] );
        }

        return $new_input;
    }

}

?>