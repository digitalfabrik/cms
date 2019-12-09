<?php

namespace WPML\TM\ATE\Log;

class Entry {

	/**
	 * @var int $timestamp The log's creation timestamp.
	 */
	public $timestamp = 0;

	/**
	 * @see ErrorEvents
	 *
	 * @var int $event The event code that triggered the log.
	 */
	public $event = 0;

	/**
	 * @var string $description The details of the log (e.g. exception message).
	 */
	public $description = '';

	/**
	 * @var int $wpmlJobId [Optional] The WPML Job ID (when applies).
	 */
	public $wpmlJobId = 0;

	/**
	 * @var int $ateJobId [Optional] The ATE Job ID (when applies).
	 */
	public $ateJobId = 0;

	/**
	 * @var array $extraData [Optional] Complementary serialized data (e.g. API request/response data).
	 */
	public $extraData = [];

	/**
	 * @param array $item
	 *
	 * @return Entry
	 */
	public function __construct( array $item = null ) {
		if ( $item ) {
			$this->timestamp   = (int) $item['timestamp'];
			$this->event       = (int) $item['event'];
			$this->description = $item['description'];
			$this->wpmlJobId   = (int) $item['wpmlJobId'];
			$this->ateJobId    = (int) $item['ateJobId'];
			$this->extraData   = (array) $item['extraData'];
		}
	}

	/**
	 * @return string
	 */
	public function getFormattedDate() {
		return date_i18n( 'Y/m/d g:i:s A', $this->timestamp );
	}

	/**
	 * @return string
	 */
	public function getEventLabel() {
		return wpml_collect(
			[
				ErrorEvents::SERVER_ATE   => 'ATE Server Communication',
				ErrorEvents::SERVER_AMS   => 'AMS Server Communication',
				ErrorEvents::SERVER_XLIFF => 'XLIFF Server Communication',
				ErrorEvents::JOB_DOWNLOAD => 'Job Download',
			]
		)->get( $this->event, '' );

	}

	/**
	 * @return string
	 */
	public function getExtraDataToString() {
		return json_encode( $this->extraData );
	}
}
