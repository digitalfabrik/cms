<?php
class IgFirebaseService {
	
	function __construct(){
		$this->readSettings();
	}


	public function sendNotification( $title, $body, $language ) {
		$header = $this->buildHeader( $this->settings['auth_key'] );
		$fields = $this->buildJson( $title, $body, $language, $this->settings['blog_id'] );
	}


	private function readSettings() {
		$this->settings['api_url'] = 'https://fcm.googleapis.com/fcm/send';
		$this->settings['blog_id'] = get_current_blog_id();
		$this->settings['auth_key'] = "asdf";
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


	private function buildJson( $title, $body, $language, $blog_id ) {
		$fields = array (
			'to' => '/topics/' . (string)$blog_id . "-" . $language,
			'notification' => array (
				'title' => $title,
				'body' => $body
			)
		 );
		return json_encode ( $fields );
	}


	private function buildHeader( $authKey ) {
		$headers = array (
			'Authorization: key=' . $authKey,
			'Content-Type: application/json'
		);
		return $headers;
	}

}

?>
