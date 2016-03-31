<?php 

class ClickGuideOptionsPageInstance {

    private $options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        // add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    // Add options page
    public function add_plugin_page() {
        add_submenu_page(
            'tools.php',
            'Klick Guide', 
            'Klick Guide', 
            'manage_clickguide', // capability
            'clickguide-optionspage-instance', 
            array( $this, 'create_admin_page' )
        );
    }

    // Options page callback
    public function create_admin_page()
    {
        $this->options = get_option( 'clickguide_tours' );
        ?>
        <div class="wrap optionsPageWrap">
            <h2>Klick Guide</h2>    
            <p>Bitte wählen Sie aus, welche der nachfolgenden Touren in dieser Instanz angeboten werden sollen. Standardmäßig sind alle Touren ausgewählt.<p>
            <?php require_once __DIR__ . '/optionsPage.php'; ?>
        </div>
        <?php
    }

}

?>