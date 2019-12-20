<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\TM\ATE\Download;

use WPML\Collect\Support\Collection;

class Result {

	/** @var Collection $processedJobs */
	public $processedJobs;

	/** @var int $downloadQueueSize */
	public $downloadQueueSize = 0;

	public function __construct() {
		$this->processedJobs = wpml_collect( [] );
	}
}
