<?php
/**
 * @package wpml-core
 * @subpackage wpml-core
 */

require_once( 'translationproxy-api.class.php' );
require_once( 'translationproxy-service.class.php' );
require_once( 'translationproxy-batch.class.php' );

/**
 * Class TranslationProxy_Project
 */
class TranslationProxy_Project {

	public $id;
	public $access_key;
	public $ts_id;
	public $ts_access_key;

	/**
	 * @var TranslationProxy_Service
	 */
	public $service;
	public $errors;

	/**
	 * @param TranslationProxy_Service $service
	 * @param string                   $delivery
	 */
	public function __construct( $service, $delivery = 'xmlrpc' ) {
		$this->service = $service;
		$this->errors  = array();

		$icl_translation_projects = TranslationProxy::get_translation_projects();
		$project_index            = self::generate_service_index( $service );
		if ( $project_index && $icl_translation_projects && isset( $icl_translation_projects [ $project_index ] ) ) {
			$project = $icl_translation_projects[ $project_index ];

			$this->id            = $project['id'];
			$this->access_key    = $project['access_key'];
			$this->ts_id         = $project['ts_id'];
			$this->ts_access_key = $project['ts_access_key'];

			$this->service->delivery_method = $delivery;
		}
	}

	/**
	 * Returns the index by which a translation service can be found in the array returned by
	 * \TranslationProxy::get_translation_projects
	 *
	 * @param $service object
	 *
	 * @return bool|string
	 */
	public static function generate_service_index( $service ) {
		$index = false;
		if ( $service ) {
			$service->custom_fields_data = isset( $service->custom_fields_data ) ? $service->custom_fields_data : array();
			if ( isset( $service->id ) ) {
				$index = md5( $service->id . serialize( $service->custom_fields_data ) );
			}
		}

		return $index;
	}

	/**
	 * Create and configure project (Translation Service)
	 *
	 * @param string      $url
	 * @param string      $name
	 * @param string      $description
	 * @param string      $delivery
	 * @param bool|string $site_key
	 *
	 * @return bool
	 * @throws TranslationProxy_Api_Error
	 * @throws Exception
	 */
	public function create( $url, $name, $description, $delivery = 'xmlrpc', $site_key = false ) {
		$params = array(
				'service'       => array( 'id' => $this->service->id ),
				'project'       => array(
						'name'            => $name,
						'description'     => $description,
						'url'             => $url,
						'delivery_method' => $delivery,
						'sitekey'         => $site_key,
				),
				"custom_fields" => $this->service->custom_fields_data,
		);

		try {
			$response            = TranslationProxy_Api::proxy_request( '/projects.json', $params, 'POST' );
			$this->id            = $response->project->id;
			$this->access_key    = $response->project->accesskey;
			$this->ts_id         = $response->project->ts_id;
			$this->ts_access_key = $response->project->ts_accesskey;

			if ( isset( $response->project->polling_method ) && $response->project->polling_method != $delivery ) {
				$this->update_delivery_method( $response->project->polling_method );
				$this->service->delivery_method = $response->project->polling_method;
			}

		} catch ( TranslationProxy_Api_Error $e ) {
			$this->add_error( $e );

			return false;
		} catch ( Exception $e ) {
			$this->add_error( $e );

			return false;
		}

		return true;
	}

	/**
	 * Convert WPML language code to service language
	 *
	 * @param $language string
	 *
	 * @return bool|string
	 */
	private function service_language( $language ) {
		return TranslationProxy_Service::get_language( $this->service, $language );
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Get information about the project (Translation Service)
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	public function custom_text( $location, $locale = "en" ) {
		$response = '';
		if ( ! $this->ts_id || ! $this->ts_access_key ) {
			return '';
		}

		//Sending Translation Service (ts_) id and access_key, as we are talking directly to the Translation Service
		//Todo: use project->id and project->access_key once this call is moved to TP
		$params = array(
			'project_id' => $this->ts_id,
			'accesskey'  => $this->ts_access_key,
			'location'   => $location,
			'lc'         => $locale,
		);

		if ( $this->service->custom_text_url ) {
			$response = TranslationProxy_Api::service_request( $this->service->custom_text_url, $params, 'GET', false, true, true, true );
		}

		return $response;
	}

	function current_service_name() {

		return TranslationProxy::get_current_service_name();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * IFrames to display project info (Translation Service)
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function select_translator_iframe_url( $source_language, $target_language ) {
		//Sending Translation Service (ts_) id and access_key, as we are talking directly to the Translation Service
		$params['project_id']      = $this->ts_id;
		$params['accesskey']       = $this->ts_access_key;
		$params['source_language'] = $this->service_language( $source_language );
		$params['target_language'] = $this->service_language( $target_language );
		$params['compact']         = 1;

		return $this->_create_iframe_url( $this->service->select_translator_iframe_url, $params );
	}

	public function translator_contact_iframe_url( $translator_id ) {
		//Sending Translation Service (ts_) id and access_key, as we are talking directly to the Translation Service
		$params['project_id']    = $this->ts_id;
		$params['accesskey']     = $this->ts_access_key;
		$params['translator_id'] = $translator_id;
		$params['compact']       = 1;
		if ( $this->service->translator_contact_iframe_url ) {
			return $this->_create_iframe_url( $this->service->translator_contact_iframe_url, $params );
		}

		return false;
	}

	private function _create_iframe_url( $url, $params ) {
		try {
			if ( $params ) {
				$url = TranslationProxy_Api::add_parameters_to_url( $url, $params );
				$url .= '?' . http_build_query( $params );
			}

			return $url;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Jobs handling (Translation Proxy)
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * @param bool $source_language
	 * @param bool $target_languages
	 *
	 * @internal param bool $name
	 * @return bool|TranslationProxy_Batch
	 */
	function get_batch_job( $source_language = false, $target_languages = false ) {
		$cache_key   = md5( wp_json_encode( array( $source_language, $target_languages ) ) );
		$cache_group = 'get_batch_job';
		$cache_found = false;

		$batch_data = wp_cache_get( $cache_key, $cache_group, false, $cache_found );

		if ( $cache_found ) {
			return $batch_data;
		}

		try {
			$batch_data = TranslationProxy_Basket::get_batch_data();

			if ( ! $batch_data ) {
				if ( ! $source_language ) {
					$source_language = TranslationProxy_Basket::get_source_language();
				}
				if ( ! $target_languages ) {
					$target_languages = TranslationProxy_Basket::get_remote_target_languages();
				}

				if ( ! $source_language || ! $target_languages ) {
					return false;
				}

				$batch_data = $this->create_batch_job( $source_language, $target_languages );
				if ( $batch_data ) {
					TranslationProxy_Basket::set_batch_data( $batch_data );
				}
			}

			wp_cache_set( $cache_key, $batch_data, $cache_group );

			return $batch_data;

		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	/**
	 * @return bool|int
	 */
	function get_batch_job_id() {
		try {
			$batch_data = $this->get_batch_job();
			$ret        = $batch_data ? $batch_data->id : false;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );
			$ret = false;
		}

		return $ret;
	}

	/**
	 * @param bool $source_language
	 * @param      $target_languages
	 *
	 * @internal param bool $name
	 * @return bool|TranslationProxy_Batch
	 */
	public function create_batch_job( $source_language, $target_languages ) {
		$batch_name = TranslationProxy_Basket::get_basket_name();

		$extra_fields = TranslationProxy_Basket::get_basket_extra_fields();

		if ( ! $source_language ) {
			$source_language = TranslationProxy_Basket::get_source_language();
		}
		if ( ! $target_languages ) {
			$target_languages = TranslationProxy_Basket::get_remote_target_languages();
		}

		if ( ! $source_language || ! $target_languages ) {
			return false;
		}

		if ( ! $batch_name ) {
			$batch_name = sprintf( __( '%s: WPML Translation Jobs', 'wpml-translation-management' ), get_option( 'blogname' ) );
		}

		TranslationProxy_Basket::set_basket_name( $batch_name );

		$params = array(
			"api_version" => TranslationProxy_Api::API_VERSION,
			"project_id"  => $this->id,
			"accesskey"   => $this->access_key,
			'batch'       => array(
				'source_language'  => $source_language,
				'target_languages' => $target_languages,
				'name'             => $batch_name
			)
		);

		if ( $extra_fields ) {
			$params['extra_fields'] = $extra_fields;
		}

		try {
			$response = TranslationProxy_Api::proxy_request( '/projects/{project_id}/batches.json', $params, 'POST', false );

			$batch = false;
			if ( $response ) {
				$batch = $response->batch;
				TranslationProxy_Basket::set_batch_data( $batch );
			}

			return $batch;

		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	/**
	 *
	 * Add Files Batch Job
	 * @link http://git.icanlocalize.com/fotanus/translation_proxy/wikis/add_files_batch_job
	 *
	 * @param string $file
	 * @param string $title
	 * @param string $cms_id
	 * @param string $url
	 * @param string $source_language
	 * @param string $target_language
	 * @param int    $word_count
	 * @param int    $translator_id
	 * @param string $note
	 * @param int    $is_update
	 *
	 * @return bool
	 */
	public function send_to_translation_batch_mode( $file, $title, $cms_id, $url, $source_language, $target_language, $word_count, $translator_id = 0, $note = '', $is_update = 0 ) {

		$batch_id = $this->get_batch_job_id();

		if ( ! $batch_id ) {
			return false;
		}

		$params = array(
			'api_version' => TranslationProxy_Api::API_VERSION,
			'project_id'  => $this->id,
			'batch_id'    => $batch_id,
			'accesskey'   => $this->access_key,
			'job'         => array(
				'file'            => $file,
				'word_count'      => $word_count,
				'title'           => $title,
				'cms_id'          => $cms_id,
				'url'             => $url,
				'is_update'       => $is_update,
				'translator_id'   => $translator_id,
				'note'            => $note,
				'source_language' => $source_language,
				'target_language' => $target_language,
			)
		);

		try {
			$response = TranslationProxy_Api::proxy_request( '/batches/{batch_id}/jobs.json', $params, 'POST', true );

			if ( $response ) {
				return $response->job->id;
			}

			return false;

		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	/**
	 * @param bool|int $tp_batch_id
	 *
	 * @link http://git.icanlocalize.com/onthego/translation_proxy/wikis/commit_batch_job
	 *
	 * @return array|bool|mixed|null|stdClass|string
	 */
	function commit_batch_job( $tp_batch_id = false ) {
		$tp_batch_id = $tp_batch_id ? $tp_batch_id : $this->get_batch_job_id();

		if ( ! $tp_batch_id ) {
			return true;
		}

		$params = array(
			"api_version" => TranslationProxy_Api::API_VERSION,
			'project_id'  => $this->id,
			'accesskey'   => $this->access_key,
			'batch_id'    => $tp_batch_id,
		);

		try {
			$response    = TranslationProxy_Api::proxy_request( '/batches/{batch_id}/commit.json', $params, 'PUT', false );
			$basket_name = TranslationProxy_Basket::get_basket_name();
			if ( $basket_name ) {
				global $wpdb;

				$batch_id_sql      = "SELECT id FROM {$wpdb->prefix}icl_translation_batches WHERE batch_name=%s";
				$batch_id_prepared = $wpdb->prepare( $batch_id_sql, array( $basket_name ) );
				$batch_id          = $wpdb->get_var( $batch_id_prepared );

				$batch_data = array(
					'batch_name'  => $basket_name,
					'tp_id'       => $tp_batch_id,
					'last_update' => date( 'Y-m-d H:i:s' ),
				);
				if ( isset( $response ) && $response ) {
					$batch_data['ts_url'] = serialize( $response );
				}

				if ( ! $batch_id ) {
					$wpdb->insert( $wpdb->prefix . 'icl_translation_batches', $batch_data );
				} else {
					$wpdb->update( $wpdb->prefix . 'icl_translation_batches', $batch_data, array( 'id' => $batch_id ) );
				}
			}

			return isset( $response ) ? $response : false;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	/**
	 *
	 * @return object[]
	 */
	public function jobs() {

		return $this->get_jobs( 'any' );
	}

	/**
	 * @return object[]
	 */
	public function finished_jobs() {

		return $this->get_jobs( 'translation_ready' );
	}

	/**
	 * @return object[]
	 * @param null|bool $include_archived
	 */
	public function cancelled_jobs($include_archived = null) {
		$jobs           = $this->get_jobs( 'any', $include_archived );
		$jobs_cancelled = $this->get_jobs( 'cancelled', $include_archived );
		$current        = array();
		foreach ( $jobs_cancelled as $job ) {
			if ( $job->cms_id === '' ) {
				$current[ 'string_' . $job->id ] = $job;
			} elseif ( ! isset( $current[ $job->cms_id ] ) || $current[ $job->cms_id ]->id < $job->id ) {
				$current[ $job->cms_id ] = $job;
			}
		}

		foreach ( $jobs as $maybe_newer_job ) {
			if ( isset( $current[ $maybe_newer_job->cms_id ] )
			     && $maybe_newer_job->id > $current[ $maybe_newer_job->cms_id ]->id
			) {
				unset( $current[ $maybe_newer_job->cms_id ] );
			}
		}

		return array_values( $current );
	}

	public function archive_translation( $job_id ) {
		$params = array(
			'job_id'     => $job_id,
			'project_id' => $this->id,
			'accesskey'  => $this->access_key,
			'job'        => array(
				'archived' => true,
			),
		);
		try {
			TranslationProxy_Api::proxy_request( '/jobs/{job_id}.json', $params, 'PUT' );

			return true;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	public function update_delivery_method( $method ) {
		global $sitepress;
		if ( 'xmlrpc' === $method ) {
			$sitepress->set_setting( 'translation_pickup_method', ICL_PRO_TRANSLATION_PICKUP_XMLRPC, true );
		} elseif ( 'polling' === $method ) {
			$sitepress->set_setting( 'translation_pickup_method', ICL_PRO_TRANSLATION_PICKUP_POLLING, true );
		}
	}

	public function set_delivery_method( $method ) {
		$params = array(
			'project_id' => $this->id,
			'accesskey'  => $this->access_key,
			'project'    => array( 'delivery_method' => $method ),
		);
		try {
			TranslationProxy_Api::proxy_request( '/projects.json', $params, 'put' );

			return true;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	public function fetch_translation( $job_id ) {
		$params = array(
			'project_id' => $this->id,
			'accesskey'  => $this->access_key,
			'job_id'     => $job_id,
		);
		try {
			return TranslationProxy_Api::proxy_download( '/jobs/{job_id}/xliff.json', $params );
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	public function check_status( $batch_id ) {
		$tp_networking = wpml_tm_load_tp_networking();
		$params        = array(
			'batch_id'   => $batch_id,
			'project_id' => $this->id,
			'accesskey'  => $this->access_key,
		);
		try {
			$tp_networking->send_request( OTG_TRANSLATION_PROXY_URL . "/batches/{batch_id}/check.json", $params, 'GET', false, true );

			return true;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	public function update_job( $job_id, $url = null, $state = 'delivered' ) {
		$params = array(
			'job_id'     => $job_id,
			'project_id' => $this->id,
			'accesskey'  => $this->access_key,
			'job'        => array(
				'state' => $state,
			),
		);
		if ( $url ) {
			$params['job']['url'] = $url;
		}
		try {
			TranslationProxy_Api::proxy_request( '/jobs/{job_id}.json', $params, 'PUT' );

			return true;
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}

	private function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * @param string    $state
	 * @param null|bool $include_archived `null` ignores this argument, `true` or `false` filters by this argument
	 *
	 * @return mixed
	 */
	private function get_jobs( $state = 'any', $include_archived = null ) {
		$batch = TranslationProxy_Basket::get_batch_data();

		$params = array(
			'project_id' => $this->id,
			'accesskey'  => $this->access_key,
			'state'      => $state,
		);

		if ( null !== $include_archived ) {
			$params['archived'] = $include_archived;
		}

		if ( $batch ) {
			$params['batch_id'] = $batch ? $batch->id : false;

			return TranslationProxy_Api::proxy_request( '/batches/{batch_id}/jobs.json', $params );
		} else {
			//FIXME: remove this once TP will accept the TP Project ID: https://icanlocalize.basecamphq.com/projects/11113143-translation-proxy/todo_items/182251206/comments
			$params['project_id'] = $this->id;
		}
		try {
			return TranslationProxy_Api::proxy_request( '/jobs.json', $params );
		} catch ( Exception $ex ) {
			$this->add_error( $ex->getMessage() );

			return false;
		}
	}
}
