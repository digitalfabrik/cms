<?php

class WPML_Translation_Job_Helper {

	public function encode_field_data( $data, $format ) {
		if ( $format == 'base64' ) {
			$data = base64_encode( $data );
		} elseif ( $format == 'csv_base64' ) {
			$exp = (array) $data;
			foreach ( $exp as $k => $e ) {
				$exp[ $k ] = '"' . base64_encode( trim( $e ) ) . '"';
			}
			$data = join( ',', $exp );
		}

		return $data;
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