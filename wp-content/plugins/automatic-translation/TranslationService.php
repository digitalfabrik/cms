<?php

class TranslationService {
	private $clientId;
	private $clientSecret;

	public function __construct() {
		$this->clientId = TRANSLATION_MICROSOFT_CLIENT_ID;
		$this->clientSecret = TRANSLATION_MICROSOFT_CLIENT_SECRET;
	}

	public function translate_post($post, $source_language_code, $target_language_code) {
		$translated_title = $this->translate_string($post->post_title, $source_language_code, $target_language_code);
		$translated_content = $this->translate_string($post->post_content, $source_language_code, $target_language_code);
		$translated_post = [
			'post_title' => $translated_title,
			'post_content' => $translated_content,
			'post_type' => $post->post_type,
			'post_status' => $post->post_status
		];
		return $translated_post;
	}

	/**
	 * See http://blogs.msdn.com/b/translation/p/phptranslator.aspx
	 */
	public function translate_string($string, $source_language_code, $target_language_code) {
		//OAuth Url.
		$authUrl = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
		//Application Scope Url
		$scopeUrl = "http://api.microsofttranslator.com";
		//Application grant type
		$grantType = "client_credentials";

		//Create the AccessTokenAuthentication object.
		$authObj = new AccessTokenAuthentication();
		//Get the Access token.
		$accessToken = $authObj->getTokens($grantType, $scopeUrl, $this->clientId, $this->clientSecret, $authUrl);
		//Create the authorization Header string.
		$authHeader = "Authorization: Bearer " . $accessToken;

		//Set the params.//
		$params = "text=" . urlencode($string) . "&to=" . $target_language_code . "&from=" . $source_language_code;
		$translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";

		//Create the Translator Object.
		$translatorObj = new HTTPTranslator();

		//Get the curlResponse.
		$curlResponse = $translatorObj->curlRequest($translateUrl, $authHeader);

		//Interprets a string of XML into an object.
		$xmlObj = simplexml_load_string($curlResponse);
		if (strpos($xmlObj->body->h1, "Exception") !== false) {
			throw new AutomaticTranslationException("Fehler bei der automatischen Uebersetzung der Inhalte nach $target_language_code: "
				. implode(", ", (array)$xmlObj->body->p));
		}
		$translatedStr = "";
		foreach ((array)$xmlObj[0] as $val) {
			$translatedStr .= $val;
		}
		return $translatedStr;
	}
}

class AccessTokenAuthentication {
	/*
	 * Get the access token.
	 *
	 * @param string $grantType    Grant type.
	 * @param string $scopeUrl     Application Scope URL.
	 * @param string $clientID     Application client ID.
	 * @param string $clientSecret Application client ID.
	 * @param string $authUrl      Oauth Url.
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	function getTokens($grantType, $scopeUrl, $clientID, $clientSecret, $authUrl) {
		//Initialize the Curl Session.
		$ch = curl_init();
		//Create the request Array.
		$paramArr = array(
			'grant_type' => $grantType,
			'scope' => $scopeUrl,
			'client_id' => $clientID,
			'client_secret' => $clientSecret
		);
		//Create an Http Query.//
		$paramArr = http_build_query($paramArr);
		//Set the Curl URL.
		curl_setopt($ch, CURLOPT_URL, $authUrl);
		//Set HTTP POST Request.
		curl_setopt($ch, CURLOPT_POST, TRUE);
		//Set data to POST in HTTP "POST" Operation.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $paramArr);
		//CURLOPT_RETURNTRANSFER- TRUE to return the transfer as a string of the return value of curl_exec().
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//CURLOPT_SSL_VERIFYPEER- Set FALSE to stop cURL from verifying the peer's certificate.
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//Execute the  cURL session.
		$strResponse = curl_exec($ch);
		//Get the Error Code returned by Curl.
		$curlErrno = curl_errno($ch);
		if ($curlErrno) {
			$curlError = curl_error($ch);
			throw new Exception($curlError);
		}
		//Close the Curl Session.
		curl_close($ch);
		//Decode the returned JSON string.
		$objResponse = json_decode($strResponse);
		if ($objResponse->error) {
			throw new Exception($objResponse->error_description);
		}
		return $objResponse->access_token;
	}
}

/*
 * Processing the translator request.
 */

class HTTPTranslator {
	/*
	 * Create and execute the HTTP CURL request.
	 *
	 * @param string $url        HTTP Url.
	 * @param string $authHeader Authorization Header string.
	 * @param string $postData   Data to post.
	 *
	 * @return string.
	 *
	 */
	function curlRequest($url, $authHeader) {
		//Initialize the Curl Session.
		$ch = curl_init();
		//Set the Curl url.
		curl_setopt($ch, CURLOPT_URL, $url);
		//Set the HTTP HEADER Fields.
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader, "Content-Type: text/xml"));
		//CURLOPT_RETURNTRANSFER- TRUE to return the transfer as a string of the return value of curl_exec().
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//CURLOPT_SSL_VERIFYPEER- Set FALSE to stop cURL from verifying the peer's certificate.
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
		//Execute the  cURL session.
		$curlResponse = curl_exec($ch);
		//Get the Error Code returned by Curl.
		$curlErrno = curl_errno($ch);
		if ($curlErrno) {
			$curlError = curl_error($ch);
			throw new Exception($curlError);
		}
		//Close a cURL session.
		curl_close($ch);
		return $curlResponse;
	}
}

class AutomaticTranslationException extends Exception {
}
