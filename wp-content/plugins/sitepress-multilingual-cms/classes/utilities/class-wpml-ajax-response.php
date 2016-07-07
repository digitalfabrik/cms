<?php

class WPML_Ajax_Response {

	private $success;
	private $response_data;
	
	public function __construct( $success, $response_data ) {
		$this->success       = $success;
		$this->response_data = $response_data;
	}
	
	public function send_json() {
		if ( $this->success ) {
			wp_send_json_success( $this->response_data );
		} else {
			wp_send_json_error( $this->response_data );
		}
	}
	
	public function is_success() {
		return $this->success;
	}
}
