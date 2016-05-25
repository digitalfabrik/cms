<?php

/**
 * @package wpml-mail
 */
class WPML_Mail {
	private $WPML_Mail_Recipients;
	private $WPML_Mail_Wrapper;
	private $WPML_Mail_Language_Switcher;
	private $WPML_Mail_Languages_Helper;

	function __construct() {

		$this->WPML_Mail_Languages_Helper  = new WPML_Mail_Languages_Helper();
		$this->WPML_Mail_Recipients        = new WPML_Mail_Recipients( $this->WPML_Mail_Languages_Helper );
		$this->WPML_Mail_Wrapper           = new WPML_Mail_Wrapper();
		$this->WPML_Mail_Language_Switcher = new WPML_Mail_Language_Switcher( $this->WPML_Mail_Languages_Helper );
	}
}
