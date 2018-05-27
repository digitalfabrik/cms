<?php

/**
 * @author OnTheGo Systems
 */
class WPMLTranslationProxyApiException extends Exception {

	public function __construct( $message, $code = 0, $previous = null ) {
		WPML_TranslationProxy_Com_Log::log_error( $message );

		parent::__construct( $message, $code, $previous );
	}
}