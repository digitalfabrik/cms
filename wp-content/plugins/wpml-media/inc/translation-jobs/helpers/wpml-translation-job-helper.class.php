<?php

class WPML_Translation_Job_Helper {

	public function encode_field_data( $data, $format ) {

		return base64_encode( $data );
	}

	function decode_field_data( $data, $format ) {
		global $iclTranslationManagement;

		return $iclTranslationManagement->decode_field_data( $data, $format );
	}

	protected function get_tm_setting( $indexes ) {
		global $iclTranslationManagement;

		if ( empty( $iclTranslationManagement->settings ) ) {
			$iclTranslationManagement->init();
		}

		$settings = $iclTranslationManagement->settings;

		foreach ( $indexes as $index ) {
			$settings = isset( $settings[ $index ] ) ? $settings[ $index ] : null;
			if ( ! isset( $settings ) ) {
				break;
			}
		}

		return $settings;
	}
}