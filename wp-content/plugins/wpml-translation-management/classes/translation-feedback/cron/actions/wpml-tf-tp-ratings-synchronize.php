<?php

/**
 * Class WPML_TF_TP_Ratings_Synchronize
 *
 * @author OnTheGoSystems
 */
class WPML_TF_TP_Ratings_Synchronize {

	const MAX_RATINGS_TO_SYNCHRONIZE = 5;

	/** @var WPML_TF_Data_Object_Storage $feedback_storage */
	private $feedback_storage;

	/** @var WPML_TP_API_TF_Ratings $tp_ratings */
	private $tp_ratings;

	/**
	 * WPML_TF_TP_Ratings_Synchronize constructor.
	 *
	 * @param WPML_TF_Data_Object_Storage $feedback_storage
	 * @param WPML_TP_API_TF_Ratings      $tp_ratings
	 */
	public function __construct( WPML_TF_Data_Object_Storage $feedback_storage, WPML_TP_API_TF_Ratings $tp_ratings ) {
		$this->feedback_storage = $feedback_storage;
		$this->tp_ratings       = $tp_ratings;
	}

	/** @param bool $clear_all_pending_ratings */
	public function run( $clear_all_pending_ratings = false ) {
		$filter_args = array(
			'pending_tp_ratings' => $clear_all_pending_ratings ? -1 : self::MAX_RATINGS_TO_SYNCHRONIZE,
		);

		$feedback_filter = new WPML_TF_Feedback_Collection_Filter( $filter_args );
		/** @var WPML_TF_Feedback_Collection $feedback_collection */
		$feedback_collection = $this->feedback_storage->get_collection( $feedback_filter );
		$time_threshold      = 5 * MINUTE_IN_SECONDS;

		foreach ( $feedback_collection as $feedback ) {
			/** @var WPML_TF_Feedback $feedback */
			$time_since_creation = time() - strtotime( $feedback->get_date_created() );

			if ( ! $clear_all_pending_ratings && $time_since_creation < $time_threshold ) {
				continue;
			}

			$tp_rating_id = $this->tp_ratings->send( $feedback );

			if ( $tp_rating_id || $clear_all_pending_ratings ) {
				$feedback->get_tp_responses()->set_rating_id( (int) $tp_rating_id );
				$this->feedback_storage->persist( $feedback );
			}
		}
	}
}
