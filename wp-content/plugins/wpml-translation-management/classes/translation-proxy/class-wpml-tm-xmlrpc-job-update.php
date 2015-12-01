<?php

/**
 * Class WPML_TM_XmlRpc_Job_Update
 */
class WPML_TM_XmlRpc_Job_Update extends WPML_TP_Project_User {

	/** @var WPML_Pro_Translation $pro_translation */
	private $pro_translation;

	const CMS_FAILED = 0;
	const CMS_SUCCESS = 1;

	/**
	 * WPML_TM_XmlRpc_Job_Update constructor.
	 *
	 * @param WPML_Pro_Translation     $pro_translation
	 * @param TranslationProxy_Project $project
	 */
	public function __construct( &$project, &$pro_translation ) {
		parent::__construct( $project );
		$this->pro_translation = &$pro_translation;
	}

	/**
	 *
	 * Handle job update notifications from TP
	 *
	 * @param array $args
	 * @param bool  $bypass_auth if true forces ignoring the signature check when used together with polling
	 *
	 * @return int|string
	 */
	function update_status( $args, $bypass_auth = false ) {
		if ( ! ( isset( $args[0] ) && isset( $args[1] ) && isset( $args[2] ) && isset( $args[3] ) ) ) {
			throw new InvalidArgumentException( 'This method requires an array of 4 input parameters!' );
		}
		$translation_proxy_job_id = $args[0];
		$cms_id                   = $args[1];
		$status                   = $args[2];
		$signature                = $args[3];

		if ( ! $bypass_auth
		     && ! $this->authenticate_request( $translation_proxy_job_id, $cms_id, $status, $signature )
		) {
			return "Wrong signature";
		}

		switch ( $status ) {
			case "translation_ready" :
				$ret = $this->pro_translation->download_and_process_translation( $translation_proxy_job_id, $cms_id );
				break;
			case "cancelled" :
				$ret = $this->pro_translation->cancel_translation( $translation_proxy_job_id, $cms_id );
				break;
			default :
				return "Not supported status: {$status}";
		}

		return $this->pro_translation->errors
				? join( '', $this->pro_translation->errors )
				: ( (bool) $ret === true ? self::CMS_SUCCESS : self::CMS_FAILED );
	}

	/**
	 * @param int    $translation_proxy_job_id
	 * @param string $cms_id
	 * @param string $status
	 * @param string $signature
	 *
	 * @return bool
	 */
	private function authenticate_request( $translation_proxy_job_id, $cms_id, $status, $signature ) {

		return sha1( $this->project->id
		             . $this->project->access_key
		             . $translation_proxy_job_id
		             . $cms_id . $status ) === $signature;
	}
}