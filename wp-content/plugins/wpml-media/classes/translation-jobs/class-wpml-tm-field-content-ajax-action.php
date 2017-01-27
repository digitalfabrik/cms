<?php

class WPML_TM_Field_Content_Ajax_Action {

	/** @var  WPML_TM_Job_Action_Factory $job_action_factory */
	private $job_action_factory;

	/** @var  int $job_id */
	private $job_id;

	/**
	 * WPML_TM_Field_Content_Ajax_Action constructor.
	 *
	 * @param WPML_TM_Job_Action_Factory $job_action_factory
	 * @param int                        $job_id
	 */
	public function __construct( &$job_action_factory, $job_id ) {
		$this->job_action_factory = &$job_action_factory;
		if ( ! ( is_int( $job_id ) && $job_id > 0 ) ) {
			throw new InvalidArgumentException( 'Invalid job id provided, received: ' . serialize( $job_id ) );
		}
		$this->job_id = $job_id;
	}

	/**
	 * @return array containing the wp ajax callback function at index 0 and the
	 * arguments to be used (array of fields for the requested job) at index 1.
	 */
	public function run() {
		try {

			return array(
				'wp_send_json_success',
				$this->job_action_factory->field_contents( $this->job_id )->run()
			);
		} catch ( Exception $e ) {

			return array( 'wp_send_json_error', 0 );
		}
	}
}