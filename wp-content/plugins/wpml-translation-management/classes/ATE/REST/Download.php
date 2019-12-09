<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\TM\ATE\Download\Job;
use WPML\TM\ATE\Download\Process;
use WPML\TM\Jobs\Utils\ElementLinkFactory;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;

class Download extends Base {

	const PROCESS_QUANTITY = 5;

	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::DOWNLOAD_JOBS,
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'download' ],
				]
			]
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [
			'manage_options',
			'manage_translations',
			'translate'
		];
	}

	public function download() {
		$result = make( Process::class )->run( self::PROCESS_QUANTITY );

		return [
			'jobs'              => $this->getJobs( $result->processedJobs ),
			'downloadQueueSize' => $result->downloadQueueSize,
		];
	}

	private function getJobs( Collection $processedJobs ) {
		$jobIds    = $processedJobs->pluck( 'wpmlJobId' );
		$viewLinks = $jobIds->map( [ wpml_tm_load_job_factory(), 'get_translation_job' ] )
		                    ->map( [ ElementLinkFactory::create(), 'getTranslation' ] );

		return $jobIds->zip( $viewLinks )
		              ->map( function ( $pair ) {
			              return [
				              'jobId'    => (int) $pair[0],
				              'viewLink' => $pair[1]
			              ];
		              } )
		              ->toArray();
	}
}
