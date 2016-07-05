<?php

class WPML_Translation_Proxy_Networking {

	const API_VERSION = 1.1;

	/**
	 * @param            $url
	 * @param array      $params
	 * @param string     $method
	 * @param            $multi_part
	 * @param bool|false $gzencoded
	 * @param bool|true  $has_return_value
	 * @param bool|true  $json_response
	 * @param bool|true  $has_api_response
	 *
	 * @return array|mixed|null|object|string
	 * @throws TranslationProxy_Api_Error
	 */
	public function send_request(
		$url,
		$params = array(),
		$method = 'GET',
		$multi_part,
		$gzencoded = false,
		$has_return_value = true,
		$json_response = true,
		$has_api_response = true
	) {
		$response = null;
		$method   = strtoupper( $method );

		if ( $params ) {
			$url = TranslationProxy_Api::add_parameters_to_url( $url, $params );
			if ( $method == 'GET' ) {
				$url .= '?' . http_build_query( $params );
			}
		}
		if ( ! isset( $params[ 'api_version' ] ) || ! $params[ 'api_version' ] ) {
			$params[ 'api_version' ] = self::API_VERSION;
		}

		TranslationProxy_Com_Log::log_call( $url, $params, $method, $multi_part );
		$api_response = $this->call_remote_api( $url, $params, $method, $multi_part, $has_return_value );

		if ( $gzencoded ) {
			try {
				$gzdecoded_response = @gzdecode( $api_response );
				if ( ! $gzdecoded_response ) {
					throw new TranslationProxy_Api_Error( 'gzdecode() returned an empty value. api_response: ' . print_r( $api_response,
							true ),
						0 );
				} else {
					$api_response = $gzdecoded_response;
				}
			} catch ( Exception $e ) {
				throw new TranslationProxy_Api_Error( 'gzdecode() failed. api_response: ' . print_r( $api_response,
						true ), 0 );
			}
		}

		TranslationProxy_Com_Log::log_response($json_response ? $api_response : 'XLIFF received');

		if ( $has_return_value ) {
			if ( $json_response ) {
				$response = json_decode( $api_response );
				if ( $has_api_response ) {
					$response = $this->get_api_response( $response );
				}
			} else {
				$response = $api_response;
			}
		}

		return $response;
	}

	public function get_extra_fields_remote( $project ) {

		$params = array(
			'accesskey'   => $project->access_key,
			'api_version' => self::API_VERSION,
			'project_id'  => $project->id
		);

		return TranslationProxy_Api::proxy_request( '/projects/{project_id}/extra_fields.json', $params );
	}

	function get_current_project() {

		return TranslationProxy::get_current_project();
	}

	/**
	 * @param string $url
	 * @param array  $params
	 * @param string $method
	 * @param bool   $multipart
	 * @param bool   $has_return_value
	 *
	 * @throws TranslationProxy_Api_Error
	 *
	 * @return null|string
	 */
	private function call_remote_api( $url, $params, $method, $multipart, $has_return_value = true ) {
		$response = null;
		$context  = self::_get_stream_context( $params, $method, $multipart );
		$response = @file_get_contents( $url, false, $context );
		if ( $has_return_value && $response === false ) {
			throw new TranslationProxy_Api_Error( "Cannot communicate with the remote service" );
		}

		return $response;
	}

	private function get_api_response( $response ) {
		if ( ! $response || ! isset( $response->status->code ) ) {
			throw new TranslationProxy_Api_Error( "Cannot communicate with the remote service" );
		}
		if ( $response->status->code != 0 ) {
			throw new TranslationProxy_Api_Error( $response->status->message, $response->status->code );
		}

		return $response->response;
	}

	private function _get_stream_context( $params, $method, $multipart ) {
		if ( $multipart ) {
			list( $header, $content ) = self::_prepare_multipart_request( $params );
		} else {
			$content = wp_json_encode( $params );
			$header  = 'Content-type: application/json';
		}
		$options = array(
			'http' => array(
				'method'  => $method,
				'content' => $content,
				'header'  => $header
			),
			'ssl' => array(
				'verify_peer' => false,
			)
		);

		return stream_context_create( $options );
	}

	private function _prepare_multipart_request( $params ) {
		$boundary = '----' . microtime( true );
		$header   = "Content-Type: multipart/form-data; boundary=$boundary";
		$content  = self::_add_multipart_contents( $boundary, $params );
		$content .= "--$boundary--\r\n";

		return array( $header, $content );
	}

	private function _add_multipart_contents( $boundary, $params, $context = array() ) {
		$initial_context = $context;
		$content         = '';

		foreach ( $params as $key => $value ) {
			$context    = $initial_context;
			$context[ ] = $key;

			if ( is_array( $value ) ) {
				$content .= self::_add_multipart_contents( $boundary, $value, $context );
			} else {
				$pieces = array_slice( $context, 1 );
				if ( $pieces ) {
					$name = "{$context[0]}[" . implode( "][", $pieces ) . "]";
				} else {
					$name = "{$context[0]}";
				}

				$content .= "--$boundary\r\n" . "Content-Disposition: form-data; name=\"$name\"";

				if ( is_resource( $value ) ) {
					$filename = self::get_file_name( $params, $key );
					$content .= "; filename=\"$filename\"\r\n" . "Content-Type: application/octet-stream\r\n\r\n" . gzencode( stream_get_contents( $value ) ) . "\r\n";
				} else {
					$content .= "\r\n\r\n$value\r\n";
				}
			}
		}

		return $content;
	}

	private function get_file_name( $params, $default = 'file' ) {

		$title = isset( $params[ 'title' ] ) ? sanitize_title_with_dashes( strtolower( filter_var( $params[ 'title' ],
			FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) ) )
			: '';
		if ( str_replace( array( '-', '_' ), '', $title ) == '' ) {
			$title = $default;
		}
		$source_language = isset( $params[ 'source_language' ] ) ? $params[ 'source_language' ] : '';
		$target_language = isset( $params[ 'target_language' ] ) ? $params[ 'target_language' ] : '';

		$filename = implode( '-', array_filter( array( $title, $source_language, $target_language ) ) );

		return $filename . ".xliff.gz";
	}
}
