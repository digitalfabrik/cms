<?php

namespace WPML\TM\ATE\Download;

use Exception;
use WPML\Collect\Support\Collection;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\ErrorEvents;
use WPML_TM_ATE_API;

class Process {

	/** @var Queue $queue */
	private $queue;

	/** @var Consumer $consumer */
	private $consumer;

	/** @var WPML_TM_ATE_API $ateApi */
	private $ateApi;

	public function __construct( Queue $queue, Consumer $consumer, WPML_TM_ATE_API $ateApi ) {
		$this->queue    = $queue;
		$this->consumer = $consumer;
		$this->ateApi   = $ateApi;
	}

	/**
	 * @param int $quantity
	 *
	 * @return Result
	 */
	public function run( $quantity = 5 ) {
		$result = new Result();
		$job    = $processedJob = null;

		do {
			try {
				$job = $this->queue->getFirst();

				if ( $job ) {
					$processedJob = $this->consumer->process( $job );
					$this->queue->remove( $job );

					if ( ! $processedJob ) {
						throw new Exception( 'The translation job could not be applied.' );
					}

					$result->processedJobs->push( $processedJob );
				}
			} catch ( Exception $e ) {
				// @todo: Check which action to take depending on the situation.
				$currentJob = $processedJob ?: $job;
				$this->logException( $e, $currentJob );
			}

			$processedJob = null;
			$quantity--;
		} while ( $quantity && $job );

		$this->acknowledgeAte( $result->processedJobs );

		$result->downloadQueueSize = $this->queue->count();

		return $result;
	}

	private function acknowledgeAte( Collection $processedJobs ) {
		if ( $processedJobs->count() ) {
			$this->ateApi->confirm_received_job( $processedJobs->pluck( 'ateJobId' )->toArray() );
		}
	}

	/**
	 * @param Exception $e
	 * @param Job|null  $job
	 */
	private function logException( Exception $e, Job $job = null ) {
		$entry              = new Entry();
		$entry->description = $e->getMessage();

		if ( $job ) {
			$entry->ateJobId    = $job->ateJobId;
			$entry->wpmlJobId   = $job->wpmlJobId;
			$entry->extraData   = [ 'downloadUrl' => $job->url ];
		}

		if ( $e instanceof \Requests_Exception ) {
			$entry->event = ErrorEvents::SERVER_XLIFF;
		} else {
			$entry->event = ErrorEvents::JOB_DOWNLOAD;
		}

		wpml_tm_ate_ams_log( $entry );
	}
}
