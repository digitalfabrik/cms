<?php 

require_once __DIR__ . '/ClickGuideField.php';

class ClickGuideWaypoint extends ClickGuideField {

    private $site;
    private $position;
    private $order;

    public function __construct( $name, $desc, $order, $site, $position ) {
		$this->setPosition( $position );   
        $this->setSite( $site );   	

    	parent::__construct( $name, $desc, $order );
    }

    public function setPosition( $position ) {
    	$this->position = $position;
    }

    public function getPosition() {
        return $this->position;
    }

    public function setSite( $site ) {
        $this->site = $site;
    }

    public function getSite() {
        return $this->site;
    }

    // write object in database 
    public function writeInDB() {
        global $wpdb;

        $name = $this->getName();
        $desc = $this->getDesc();
        $order = $this->getOrder();
        $site = $this->getSite();
        $position = $this->getPosition();

        $table_name = CLICKGUIDE_TABLE;
        // $sql = "INSERT INTO {$table_name} (cg_type, cg_name, cg_desc, cg_order, cg_position) VALUES (1, '$name', '$desc', '$order', '$position');";

        // require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // dbDelta($sql);
        $wpdb->insert( $table_name, array('cg_type' => 1, 'cg_name' => $name, 'cg_desc' => $desc, 'cg_order' => $order, 'cg_site' => $site, 'cg_position' => $position) );

        return $wpdb->insert_id;
    }

}

?>