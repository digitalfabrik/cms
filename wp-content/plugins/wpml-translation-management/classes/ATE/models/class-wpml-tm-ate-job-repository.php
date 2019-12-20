<?php

class WPML_TM_ATE_Job_Repository {

	/** @var WPML_TM_Jobs_Repository */
	private $job_repository;

	public function __construct( WPML_TM_Jobs_Repository $job_repository ) {
		$this->job_repository  = $job_repository;
	}

	/**
	 * @return WPML_TM_Jobs_Collection
	 */
	public function get_jobs_to_sync() {
		$search_params = new WPML_TM_Jobs_Search_Params();
		$search_params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_LOCAL );
		$search_params->set_status( self::get_in_progress_statuses() );
		$search_params->set_job_types( array( WPML_TM_Job_Entity::POST_TYPE, WPML_TM_Job_Entity::PACKAGE_TYPE ) );

		return $this->job_repository
			->get( $search_params )
			->filter( function( WPML_TM_Post_Job_Entity $job ) {
				return $job->is_ate_job();
			} );
	}

	/** @return array */
	public static function get_in_progress_statuses() {
		return array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS );
	}
}