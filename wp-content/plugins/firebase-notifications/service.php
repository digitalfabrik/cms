<?php
class FirebaseNotificationsService {
	
	function __construct(){
		$this->read_settings();
	}


	public function translate_send_notifications( $items ) {
		$languages = icl_get_languages();
		foreach($items as $item) {
			if( $item['title'] == '' and $item['message'] == '' ) {
				//autotranslate | at
				if( $item['translate'] == 'at' ) {
					$translation_service = new TranslationService();
					$item['title'] = $translation_service->translate_string($items[ICL_LANGUAGE_CODE]['title'], ICL_LANGUAGE_CODE, $item['lang']);
					$item['message'] = $translation_service->translate_string($items[ICL_LANGUAGE_CODE]['message'], ICL_LANGUAGE_CODE, $item['lang']);
				// no message | no
				} elseif( $item['translate'] == 'no' ) {
					continue;
				// original language | or
				} elseif( $item['translate'] == 'or' ) {
					$item['title'] = $items[ICL_LANGUAGE_CODE]['title'];
					$item['message'] = $items[ICL_LANGUAGE_CODE]['message'];
				}
			}
			$this->send_notification( $item['title'],$item['message'],$item['lang'], $item['group'] );
		}
		echo "<div class='notice notice-success'><p>".__( 'Messages sent.', 'firebase-notifications' )."</p></div>";
	}


	private function send_notification( $title, $body, $language, $group ) {
		$header = $this->build_header( $this->settings['auth_key'] );
		$fields = $this->build_json( $title, $body, $language, $this->settings['blog_id'], $group );
		return $this->execute_curl( $this->settings['api_url'], $header, $fields );
	}


	private function read_settings() {
		// are network settings enforced?
		$this->settings['blog_id'] = get_current_blog_id();
		$this->settings['force_network_settings'] = get_site_option( 'fbn_force_network_settings' );
		$this->settings['per_blog_topic'] = get_site_option( 'fbn_per_blog_topic' );
		// use network settings
		if ( $this->settings['force_network_settings'] == '2' ) {
			$this->settings['api_url'] = get_site_option('fbn_api_url');
			$this->settings['auth_key'] = get_site_option('fbn_auth_key');
		}
		// network or blog settings
		elseif ( $this->settings['force_network_settings'] == '1' ) {
			if( get_blog_option( $blog_id, 'fbn_use_network_settings' ) == '1' ) {
				$this->settings['api_url'] = get_site_option('fbn_api_url');
				$this->settings['auth_key'] = get_site_option('fbn_auth_key');
				$this->settings['fbn_title_prefix'] = get_site_option('fbn_title_prefix');
			} else {
				$this->settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
				$this->settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
			}
		}
		// blog settings
		elseif ( $this->settings['force_network_settings'] == '0' ) {
			$this->settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
			$this->settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
			$this->settings['fbn_title_prefix'] = get_site_option('fbn_title_prefix');
		}
	}


	private function execute_curl( $url, $headers, $fields ) {
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


	private function build_json( $title, $body, $language, $blog_id, $group ) {
		if( "" != $this->settings['fbn_title_prefix'] )
			$title = $this->settings['fbn_title_prefix'].' '.$title;
		$fields = array (
			'to' => '/topics/' . ($this->settings['per_blog_topic'] == '1' ? (string)$blog_id . "-" . $language . "-" : "") . $group,
			'notification' => array (
				'title' => $title,
				'body' => $body
			),
			'data' => array (
				'title' => $title,
				'body' => $body,
				'city' => (string)get_current_blog_id(),
				'lanCode' => $language
			),
			'apns' => array(
				'headers' => array(
					'apns-priority' => '5'
				),
				'payload' => array(
					'aps' => array(
						'category' => 'NEW_MESSAGE_CATEGORY'
					)
				)
			),
			'android' => array(
				'ttl' => '86400s'
			),
			'to' => '/topics/' . ($this->settings['per_blog_topic'] == '1' ? (string)$blog_id . "-" . $language . "-" : "") . $group
		 );
		return json_encode ( $fields );
	}


	private function build_header( $authKey ) {
		$headers = array (
			'Authorization: key=' . $authKey,
			'Content-Type: application/json'
		);
		return $headers;
	}

}

?>
