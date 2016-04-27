<?php

abstract class WPML_TM_Xliff_Shared extends WPML_TM_Job_Factory_User {

	/**
	 * @param SimpleXMLElement $xliff
	 *
	 * @return string
	 */
	protected function identifier_from_xliff( $xliff ) {
		$file_attributes = $xliff->{'file'}->attributes();

		return (string) $file_attributes['original'];
	}

	/**
	 * @param SimpleXMLElement $xliff
	 *
	 * @return stdClass|void|WP_Error
	 */
	protected function get_job_for_xliff( $xliff ) {
		$identifier           = $this->identifier_from_xliff( $xliff );
		$job_identifier_parts = explode( '-', (string) $identifier );
		if ( sizeof( $job_identifier_parts ) == 2 && is_numeric( $job_identifier_parts[0] ) ) {
			$job_id = $job_identifier_parts[0];
			$md5    = $job_identifier_parts[1];
			/** @var stdClass $job */
			$job = $this->job_factory->get_translation_job( (int) $job_id, false, 1, false );
			if ( ! $job || $md5 != md5( $job_id . $job->original_doc_id ) ) {
				$job = $this->does_not_belong_error();
			}
		} else {
			$job = $this->invalid_xliff_error();
		}

		return $job;
	}

	/**
	 * @param $xliff_node
	 *
	 * @return string
	 */
	protected function get_xliff_node_target( $xliff_node ) {

		return (string) ( isset( $xliff_node->target->mrk )
			? $xliff_node->target->mrk : $xliff_node->target );
	}

	protected function generate_job_data( $xliff, $job ) {
		$data = array(
			'job_id'   => $job->job_id,
			'fields'   => array(),
			'complete' => 1
		);
		foreach ( $xliff->file->body->children() as $node ) {
			$attr   = $node->attributes();
			$type   = (string) $attr['id'];
			$target = $this->get_xliff_node_target( $node );

			if ( ! $target ) {
				return $this->invalid_xliff_error();
			}

			foreach ( $job->elements as $element ) {
				if ( strpos($type, $element->field_type ) === 0 || strpos($element->field_type, $type ) === 0) {
					$target              = str_replace( '<br class="xliff-newline" />', "\n", $target );
					$field               = array();
					$field['data']       = $target;
					$field['finished']   = 1;
					$field['tid']        = $element->tid;
					$field['field_type'] = $element->field_type;
					$field['format']     = $element->field_format;

					$data['fields'][] = $field;
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * @return WP_Error
	 */
	protected function invalid_xliff_error() {

		return new WP_Error(
			'xliff_invalid',
			__( 'The uploaded xliff file does not seem to be properly formed.',
				'wpml-translation-management' ) );
	}

	/**
	 * @return WP_Error
	 */
	protected function does_not_belong_error() {

		return new WP_Error( 'xliff_doesn\'t_match',
			__( 'The uploaded xliff file doesn\'t belong to this system.',
				'wpml-translation-management' ) );
	}
}