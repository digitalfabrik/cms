<?php
class FirebaseNotificationsService {
	
	function __construct(){
		$this->readSettings();
	}


	public function translateSendNotifications( $titles, $bodys, $translate ) {
		//auutotranslate
		if( $translate == 'at' ) {
			
		// no message
		} elseif( $translate == 'no' ) {
			
		// original language
		} elseif( $translate == 'or' ) {
			
		}
	}

	private function sendNotification( $title, $body, $language ) {
		$header = $this->buildHeader( $this->settings['auth_key'] );
		$fields = $this->buildJson( $title, $body, $language, $this->settings['blog_id'] );
		echo $this->executeCurl( $this->settings['api_url'], $headers, $fields );
	}


	private function readSettings() {
		// are network settings enforced?
		$this->settings['blog_id'] = get_current_blog_id();
		$this->settings['force_network_settings'] = add_site_option( 'fbn_force_network_settings' );
		// use network settings
		if ( $this->settings['force_network_settings'] == '2' ) {
			$this->settings['api_url'] = get_site_option('fbn_auth_key');
			$this->settings['auth_key'] = get_site_option('fbn_api_url');
		}
		// network or blog settings
		elseif ( $this->settings['force_network_settings'] == '1' ) {
			if( get_blog_option( $blog_id, 'fbn_use_network_settings' ) == '1' ) {
				$this->settings['api_url'] = get_site_option('fbn_auth_key');
				$this->settings['auth_key'] = get_site_option('fbn_api_url');
			} else {
				$this->settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
				$this->settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
			}
		}
		// blog settings
		elseif ( $this->settings['force_network_settings'] == '0' ) {
			$this->settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
			$this->settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
		}
	}


	private function executeCurl( $url, $headers, $fields ) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return $result;
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
