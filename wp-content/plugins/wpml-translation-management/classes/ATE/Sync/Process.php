<?php

namespace WPML\TM\ATE\Sync;

use WPML\TM\ATE\Download\Job;
use WPML\TM\ATE\Download\Queue;
use WPML\Utilities\KeyedLock;
use WPML_TM_ATE_API;
use WPML_TM_ATE_Job_Repository;

class Process {

	const LOCK_RELEASE_TIMEOUT = 1 * MINUTE_IN_SECONDS;

	/** @var WPML_TM_ATE_API $api */
	private $api;

	/** @var KeyedLock $lock */
	private $lock;

	/** @var WPML_TM_ATE_Job_Repository $ateRepository */
	private $ateRepository;

	/** @var Queue $downloadQueue */
	private $downloadQueue;

	/** @var Trigger $trigger */
	private $trigger;

	public function __construct(
		WPML_TM_ATE_API $api,
		KeyedLock $lock,
		WPML_TM_ATE_Job_Repository $ateRepository,
		Queue $downloadQueue,
		Trigger $trigger
	) {
		$this->api           = $api;
		$this->lock          = $lock;
		$this->ateRepository = $ateRepository;
		$this->downloadQueue = $downloadQueue;
		$this->trigger       = $trigger;
	}

	/**
	 * @param Arguments $args
	 *
	 * @return Result
	 */
	public function run( Arguments $args ) {
		$result          = new Result();
		$result->lockKey = $this->lock->create( $args->lockKey, self::LOCK_RELEASE_TIMEOUT );

		if ( $result->lockKey ) {

			if ( $args->page ) {
				$result = $this->runSyncOnPages( $result, $args );
			} else {
				$result = $this->runSyncInit( $result );
			}

			if ( ! $result->nextPage ) {
				$result->lockKey = false;
				$this->lock->release();
				$this->trigger->setLastSync();
			}
		}

		$result->downloadQueueSize = $this->downloadQueue->count();

		return $result;
	}

	/**
	 * This will run the sync on extra pages.
	 *
	 * @param Result    $result
	 * @param Arguments $args
	 *
	 * @return Result
	 */
	private function runSyncOnPages( Result $result, Arguments $args ) {
		$apiPage = $args->page - 1; // ATE API pagination starts at 0.
		$data    = $this->api->sync_page( $args->ateToken, $apiPage );

		if ( isset( $data->items ) ) {
			$this->pushToDownloadQueue( $data->items );
		}

		if ( $args->numberOfPages > $args->page ) {
			$result->nextPage      = $args->page + 1;
			$result->numberOfPages = $args->numberOfPages;
			$result->ateToken      = $args->ateToken;
		}

		return $result;
	}

	/**
	 * This will run the first sync iteration.
	 * We send all the job IDs we want to sync.
	 *
	 * @param Result $result
	 *
	 * @return Result
	 */
	private function runSyncInit( Result $result ) {
		$ateJobIds = $this->getAteJobIdsToSync();

		if ( $ateJobIds || $this->trigger->isSyncRequired() ) {
			$data = $this->api->sync_all( $ateJobIds );

			if ( isset( $data->items ) ) {
				$this->pushToDownloadQueue( $data->items );
			}

			if ( isset( $data->edited ) ) {
				$this->pushToDownloadQueue( $data->edited );
			}

			if ( isset( $data->next->pagination_token, $data->next->pages_number ) ) {
				$result->ateToken      = $data->next->pagination_token;
				$result->numberOfPages = $data->next->pages_number;
				$result->nextPage      = 1; // We start pagination at 1 to avoid carrying a falsy value.
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getAteJobIdsToSync() {
		return wpml_collect( $this->ateRepository->get_jobs_to_sync()->map_to_property( 'editor_job_id' ) )
			->diff( $this->downloadQueue->getEditorJobIds() )
			->toArray();
	}

	/**
	 * @param \stdClass[] $items
	 */
	private function pushToDownloadQueue( array $items ) {
		$jobs = wpml_collect( $items )->map( function( $item ) {
			return Job::fromAteResponse( $item );
		} );

		$this->downloadQueue->push( $jobs );
	}
}
