<?php 

require_once __DIR__ . '/ClickGuideField.php';

class ClickGuideTour extends ClickGuideField {

    private $waypoints;

    public function __construct( $name, $desc, $waypoints = null ) {
    	$this->setWaypoints( $waypoints );

    	parent::__construct( $name, $desc );
    }

    public function setWaypoints( $waypoints ) {
    	if( isset( $waypoints ) and !empty($waypoints) ) {
    		$this->waypoints = $waypoints;
    	} else {
    		$this->waypoints = null;
    	}
    }

    public function getWaypoints() {
        $this->waypoints;
    }

    // write object in database
    public function writeInDB() {
        global $wpdb;

        $name = $this->getName();
        $desc = $this->getDesc();
        $order = $this->getOrder();

        $table_name = CLICKGUIDE_TABLE;
        $sql = "INSERT INTO {$table_name} (cg_type, cg_name, cg_desc, cg_order) VALUES (0, '$name', '$desc', '$order');";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

}

?>