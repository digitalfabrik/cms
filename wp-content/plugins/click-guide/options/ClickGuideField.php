<?php 

class ClickGuideField {

	private $name;
	private $desc;
	private $order;
	
	public function __construct( $name, $desc = null, $order = 1 ) {
		$this->setName( $name );
		$this->setDesc( $desc );
		$this->setOrder( $order );
    }

    public function setName( $name ) {
    	$this->name = $name;
    }

    public function setDesc( $desc ) {
        if ($desc !== null) {
    	   $this->desc = $desc;	
        }	    	
    }

    public function setOrder( $order ) {
        if ($order === null or $order === 1) {
           $this->order = 1;
        } else {
    	   $this->order = $order;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getDesc() {
        return $this->desc;
    }

    public function getOrder() {
        return $this->order;
    }

}

?>