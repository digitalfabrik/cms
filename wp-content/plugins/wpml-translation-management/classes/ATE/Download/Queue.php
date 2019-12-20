<?php

namespace WPML\TM\ATE\Download;

use WPML\Collect\Support\Collection;
use WPML\TM\Upgrade\Commands\CreateAteDownloadQueueTable;

class Queue {

	/** @var \wpdb $wpdb */
	private $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param Collection $jobs A collection of `Job`
	 */
	public function push( Collection $jobs ) {
		if ( ! $jobs->count() ) {
			return;
		}

		$prepare = function( Job $job ) {
			return $this->wpdb->prepare( "(%d,%s)", $job->ateJobId, $job->url );
		};

		$columns = '(editor_job_id, download_url)';
		$values  = $jobs->map( $prepare )->implode( ',' );


		$this->wpdb->query(
			"INSERT IGNORE INTO {$this->getTableName()} {$columns} VALUES {$values}"
		);
	}

	/**
	 * @return Collection
	 */
	public function getEditorJobIds() {
		return wpml_collect( $this->wpdb->get_col( "SELECT editor_job_id FROM {$this->getTableName()}" ) );
	}

	/**
	 * @return int
	 */
	public function count() {
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->getTableName()}" );
	}

	/** @return Job|null */
	public function getFirst() {
		$job = null;

		$this->wpdb->query( "START TRANSACTION" );

		$row = $this->getFirstUnlockedRow();

		if ( $row ) {
			$job = Job::fromDb( $row );
			$this->lockJob( $job );
		}

		$this->wpdb->query( "COMMIT" );

		return $job;
	}

	/**
	 * @return \stdClass|null
	 */
	private function getFirstUnlockedRow() {
		$oldLockTimestamp = time() - self::getLockExpiration();

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->getTableName()}
				WHERE lock_timestamp IS NULL OR lock_timestamp < %d
				LIMIT 1
				FOR UPDATE",
				$oldLockTimestamp
			)
		);
	}

	public function lockJob( Job $job ) {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->getTableName()} SET lock_timestamp=%d WHERE editor_job_id=%d",
				time(),
				$job->ateJobId
			)
		);
	}

	public function remove( Job $job ) {
		$this->wpdb->delete(
			$this->getTableName(),
			[ 'editor_job_id' => $job->ateJobId ],
			[ '%d' ]
		);
	}

	/** @return string */
	private function getTableName() {
		return $this->wpdb->prefix . CreateAteDownloadQueueTable::TABLE_NAME;
	}

	/** @return int */
	public static function getLockExpiration() {
		return 3 * MINUTE_IN_SECONDS;
	}
}
