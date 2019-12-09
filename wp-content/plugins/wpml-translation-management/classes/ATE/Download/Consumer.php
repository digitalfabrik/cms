<?php

namespace WPML\TM\ATE\Download;

use Exception;
use WPML_TM_ATE_API;
use WPML_TM_ATE_Jobs;

class Consumer {

	/** @var WPML_TM_ATE_API $ateApi */
	private $ateApi;

	/** @var WPML_TM_ATE_Jobs $ateJobs */
	private $ateJobs;

	public function __construct( WPML_TM_ATE_API $ateApi, WPML_TM_ATE_Jobs $ateJobs ) {
		$this->ateApi  = $ateApi;
		$this->ateJobs = $ateJobs;
	}

	/**
	 * @param Job $job
	 *
	 * @return Job|false
	 * @throws Exception
	 */
	public function process( Job $job ) {
		$xliffContent = $this->ateApi->get_remote_xliff_content( $job->url );
		$wpmlJobId    = $this->ateJobs->apply( $xliffContent );

		if ( $wpmlJobId ) {
			$processedJob            = clone $job;
			$processedJob->wpmlJobId = $wpmlJobId;

			return $processedJob;
		}

		return false;
	}
}
