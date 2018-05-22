<?php

/**
 * @link https://git.onthegosystems.com/tp/translation-proxy/wikis/add_files_batch_job
 */
class WPML_TP_Job extends WPML_TP_REST_Object {

	/** @var int */
	private $id;

	/** @param int $id */
	public function set_id( $id ) {
		$this->id = (int) $id;
	}

	/** @return int */
	public function get_id() {
		return $this->id;
	}

	/** @return array */
	protected function get_properties() {
		return array(
			'id' => 'id',
		);
	}
}
