<?php

use WPML\TM\TranslationProxy\Services\AuthorizationFactory;

class WPML_TM_Translation_Service_Authentication_Ajax_Factory implements IWPML_Backend_Action_Loader {

	/**
	 * @return WPML_TM_Translation_Service_Authentication_Ajax
	 */
	public function create() {
		return new WPML_TM_Translation_Service_Authentication_Ajax( new AuthorizationFactory() );
	}
}