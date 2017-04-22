<?php
class IgFirebaseService() {
	
	function __construct(){
		$this->api_url = 'https://fcm.googleapis.com/fcm/send';
		$this->blog_id = get_current_blog_id();
	}


	public function sendNotification( $title, $body, $language, $group ) {

	}


	private function readSettings() {
		
	}


	private function executeCurl( $url, $headers, $fields ) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
		$result = curl_exec ( $ch );
		return $result;
		curl_close ( $ch );
	}


	private function buildJson( $title, $body, $language, $group ) {
		$fields = array (
			'to' => '/topics/news',
			'notification' => array (
				'title' => 'my title',
				'body' => 'hello world'
			)
		 );
		return json_encode ( $fields );
	}

	private function buildHeader( $authKey ) {
		$headers = array (
			'Authorization: key=' . $authKey,
			'Content-Type: application/json'
		);
		return json_encode($headers)
	}

}




















?>
