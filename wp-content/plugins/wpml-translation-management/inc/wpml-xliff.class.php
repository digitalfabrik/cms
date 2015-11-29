<?php
/**
 * @package wpml-core
 */

class WPML_TM_xliff {
	

	private $xliff_version;

	/**
	 * WPML_TM_xliff constructor.
	 *
	 * @param string $xliff_version
	 */
	public function __construct( $xliff_version = TRANSLATION_PROXY_XLIFF_VERSION ) {
		$this->xliff_version = $xliff_version;
	}

    /**
     * Retrieve the string translations from a XLIFF
     *
     * @param string $content The XLIFF representing a set of strings
     *
     * @return array The string translation representation
     */
    public function get_strings_xliff_translation( $content ) {

        $xliff = $this->load_xliff( $content );
        $data = array();
        foreach ( $xliff->file->body->children() as $node ) {
            $attr                = $node->attributes();
            $element_id          = (string) $attr[ 'id' ];
	        $target = $this->get_xliff_node_target( $node );

	        if ( ! $target ) {
		        return new WP_Error( 'xliff_invalid', __( 'The uploaded xliff file does not seem to be properly formed.', 'wpml-translation-management' ) );
	        }
            $target              = str_replace( '<br class="xliff-newline" />', "\n", $target );
            $data[ $element_id ] = $target;
        }

        return $data;
    }
    
    /**
     * Generate a XLIFF file for a given set of strings.
     *
     * @param array $strings
     * @param string $source_language
     * @param string $target_language
     *
     * @return resource XLIFF file
     */
    public function get_strings_xliff_file($strings, $source_language, $target_language) {
        $xliff_content = $this->generate_strings_xliff($strings, $source_language, $target_language);
        return $this->generate_xliff_file($xliff_content);
    }

	/**
	 * Generate a XLIFF string representation for a given set of strings.
	 *
	 * @param array  $strings
	 * @param string $source_language
	 * @param string $target_language
	 *
	 * @return string XLIFF content
	 *
	 */
	public function generate_strings_xliff( $strings, $source_language, $target_language ) {
		$translation_units = $this->generate_strings_translation_units( $strings );
		// Keep unindented to generate a pretty printed xml
		$xliff = $this->generate_xliff( uniqid(), $source_language, $target_language, $translation_units );

		return $xliff;
	}

	private function generate_xliff( $original_id, $source_language, $target_language, $translation_units ) {
		// Keep unindented to generate a pretty printed xml
		$xliff = "";

		$xliff .= '<?xml version="1.0" encoding="utf-8" standalone="no"?>';
		$xliff .= $this->get_xliff_opening( $this->xliff_version );
		// unique file id (orginal attribute) is required by Translation Proxy to distinguish files (only for Cloudwords)
		$xliff .= "\t" . '<file original="'.$original_id.'" source-language="'.$source_language.'" target-language="'.$target_language.'" datatype="plaintext">';
		$xliff .= "\t" . "\t" . '<header />' . "\n";
		$xliff .= "\t" . "\t" . '<body>' . "\n";
		$xliff .= "\t" . "\t" . "\t" . $translation_units . "\n";
		$xliff .= "\t" . "\t" . '</body>' . "\n";
		$xliff .= "\t" . '</file>' . "\n";
		$xliff .= "</xliff>" . "\n";

		return $xliff;
	}

	private function get_xliff_opening( $xliff_version ) {
		$xliff = '';
		switch( $xliff_version ) {
			case '10':
				$xliff .= '<!DOCTYPE xliff PUBLIC "-//XLIFF//DTD XLIFF//EN" "http://www.oasis-open.org/committees/xliff/documents/xliff.dtd">' . PHP_EOL;
				$xliff .= '<xliff version="1.0">' . "\n";
				break;
			case '11':
				$xliff .= '<xliff version="1.1" xmlns="urn:oasis:names:tc:xliff:document:1.1">' . "\n";
				break;
			case '12':
			default:
				$xliff .= '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' . "\n";
				break;
		}

		return $xliff;
	}
  
    /**
     * Generate translation units for a given set of strings.
     *
     * The units are the actual content to be translated
     * Represented as a source and a target
     *
     * @param array $strings
     *
     * @return string The translation units representation
     */
    private function generate_strings_translation_units($strings){
        $translation_units = '';
        foreach($strings as $string) {
	        $id = 'string-' . $string->id;
	        $translation_units .= $this->get_translation_unit( $id, "string", $string->value, $string->value );
        }
        return $translation_units;
    }

    /**
     * Generate a XLIFF file for a given job.
     *
     * @param int $job_id
     *
     * @return resource XLIFF representation of the job
     */
    public function get_job_xliff_file( $job_id ) {
        $xliff_content = $this->generate_job_xliff( $job_id );
        return $this->generate_xliff_file($xliff_content);
    }

	/**
	 * Generate a XLIFF string for a given job.
	 *
	 * @param int       $job_id
	 *
	 * @return string XLIFF representation of the job
	 */
    public function generate_job_xliff( $job_id ) {

        global $iclTranslationManagement;

        // don't include not-translatable and don't auto-assign
        $job               = $iclTranslationManagement->get_translation_job( (int) $job_id, false, false, 1 );
        $translation_units = $this->get_job_translation_units( $job );
        $original          = $job_id . '-' . md5( $job_id . $job->original_doc_id );

        $xliff = $this->generate_xliff( $original,
                                        $job->source_language_code,
                                        $job->language_code,
                                        $translation_units );

        return $xliff;
    }
    
    /**
     * Generate translation units.
     *
     * The units are the actual content to be translated
     * Represented as a source and a target
     *
     * @param object $job
     *
     * @return string The translation units representation
     */
    private function get_job_translation_units($job) {

	    global $iclTranslationManagement;
	    $translation_units = '';

	    foreach ($job->elements as $element) {
		    if ($element->field_translate == '1') {

			    $field_data = $element->field_data;
			    $field_data_translated = $element->field_data_translated;

			    $field_data_translated = $iclTranslationManagement->decode_field_data($field_data_translated, $element->field_format);
			    $field_data = $iclTranslationManagement->decode_field_data($field_data, $element->field_format);

					if (substr($element->field_type, 0, 6) === 'field-') {
						$field_data_translated = apply_filters('wpml_tm_xliff_export_translated_cf', $field_data_translated, $element);
						$field_data = apply_filters('wpml_tm_xliff_export_original_cf', $field_data, $element);
					}
			    // check for untranslated fields and copy the original if required.

			    if (!isset($field_data_translated) || $field_data_translated == '') {
				    $field_data_translated = $field_data;
			    }
			    // check for empty array
			    if (is_array($field_data_translated)) {
				    $empty = true;
				    foreach($field_data_translated as $translated_value) {
					    if ($translated_value != '') {
						    $empty = false;
						    break;
					    }

				    }
				    if ($empty) {
					    $field_data_translated = $field_data;
				    }
			    }
			    if (is_array($field_data)) {
				    $field_data = implode(', ', $field_data);
			    }
			    if (is_array($field_data_translated)) {
				    $field_data_translated = implode(', ', $field_data_translated);
			    }

			    if ($field_data != '') {

				    $translation_units .= $this->get_translation_unit( $element->field_type, $element->field_type, $field_data, $field_data_translated );
			    }
		    }
	    }

        return $translation_units;
    }

	private function get_translation_unit($field_id, $field_name, $field_data, $field_data_translated) {
		global $sitepress;

		$translation_unit = "";
		if ($sitepress->get_setting('xliff_newlines') == WPML_XLIFF_TM_NEWLINES_REPLACE) {
			$field_data = str_replace("\n", '<br class="xliff-newline" />', $field_data);
			$field_data_translated = str_replace("\n", '<br class="xliff-newline" />', $field_data_translated);
		}
		$translation_unit .= '         <trans-unit resname="' . $field_name. '" restype="string" datatype="html" id="' . $field_id. '">' . "\n";
		$translation_unit .= '            <source><![CDATA[' . $field_data . ']]></source>' . "\n";
		$translation_unit .= '            <target><![CDATA[' . $field_data_translated . ']]></target>' . "\n";
		$translation_unit .= '         </trans-unit>' . "\n";

		return $translation_unit;
	}

	/**
	 * Retrieve the translation from a XLIFF
	 *
	 * @param string $content The XLIFF representing a job
	 *
	 * @return WP_Error|array(int, array) The job id and translation to be stored
	 */
	public function get_job_xliff_translation( $content ) {
		$xliff = $this->load_xliff( $content );
		if ( is_wp_error( $xliff ) ) {
			return $xliff;
		}

		$file_attributes = $xliff->file->attributes();
		$original        = (string) $file_attributes['original'];
		$original_id     = explode( '-', $original );

		if ( sizeof( $original_id ) == 2 and is_numeric( $original_id[0] ) ) {
			$job = $this->get_job_for_content( $original_id, $file_attributes );
			if ( is_wp_error( $job ) ) {
				return $job;
			}
			$job_id = $job->job_id;
		} else {
			return new WP_Error( 'xliff_invalid', __( 'The uploaded xliff file does not seem to be properly formed.', 'wpml-translation-management' ) );
		}

		$data = array( 'job_id' => $job_id, 'fields' => array(), 'complete' => 1 );
		foreach ( $xliff->file->body->children() as $node ) {
			$attr   = $node->attributes();
			$type   = (string) $attr['id'];
			$target = $this->get_xliff_node_target( $node );

			if ( ! $target ) {
				return new WP_Error( 'xliff_invalid', __( 'The uploaded xliff file does not seem to be properly formed.', 'wpml-translation-management' ) );
			}

			foreach ( $job->elements as $index => $element ) {
				if ( $element->field_type == $type ) {
					$target = str_replace( '<br class="xliff-newline" />', "\n", $target );
					if ( $element->field_format == 'csv_base64' ) {
						$target = explode( ',', $target );
					}
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

		return array( $job_id, $data );
	}

	/**
	 * @param $original_id_data
	 * @param $file_attributes
	 *
	 * @return stdClass|void|WP_Error
	 */
	private function get_job_for_content( $original_id_data, $file_attributes ) {
		global $iclTranslationManagement;

		$job_id = $original_id_data[0];
		$md5    = $original_id_data[1];
		$job    = $iclTranslationManagement->get_translation_job( (int) $job_id, false, false, 1 );
		//$job    = $job->original_doc_id == $original_id[0] ? $job : false;
		if ( $job && $md5 != md5( $job_id . $job->original_doc_id ) ) {
			$job = new WP_Error( 'xliff_doesn\'t_match',
					__( 'The uploaded xliff file doesn\'t belong to this system.', 'wpml-translation-management' ) );
		} elseif ( ! $job ) {
			$job_id = (int) $this->get_fallback_job_id( $original_id_data, $file_attributes );
			$job    = $iclTranslationManagement->get_translation_job( $job_id, false, false, 1 );
			if ( ! $job ) {
				$job = new WP_Error( 'Job doesn\'t_match',
						__( 'The uploaded xliff file doesn\'t contain a matching job.', 'wpml-translation-management' ) );
			}
		}

		return $job;
	}

	/**
	 * @param array            $original_id_data
	 * @param SimpleXMLElement $data
	 *
	 * @return null|string
	 */
	private function get_fallback_job_id( $original_id_data, $data ) {
		global $wpml_post_translations, $iclTranslationManagement;

		$target_language = isset( $data['target-language'] ) ? (string) $data['target-language'] : false;

		return $target_language ? $iclTranslationManagement->get_translation_job_id(
				$wpml_post_translations->get_element_trid( $original_id_data[0] ), $target_language ) : 0;
	}

	/**
	 * Parse a XML containing the XLIFF
	 *
	 * @param string $content
	 *
	 * @return SimpleXMLElement|WP_Error The parsed XLIFF or a WP error in case it could not be parsed
	 */
	private function load_xliff( $content ) {
		$xml = function_exists( 'simplexml_load_string' )
				? simplexml_load_string( $content )
				: new WP_Error( 'xml_missing',
						__( 'The Simple XML library is missing.', 'wpml-translation-management' ) );

		return $xml
				? $xml
				: new WP_Error( 'not_xml_file',
						sprintf( __( 'The xliff file could not be read.', 'wpml-translation-management' ) ) );
	}
    
    /**
     * Save a xliff string to a temporary file
     *
     * @param string $xliff_content
     *
     * @return resource XLIFF
     */
    private function generate_xliff_file($xliff_content) {
        $file = fopen('php://temp','r+');
        fwrite($file, $xliff_content);
        rewind($file);
        return $file;        
    }

	private function get_xliff_node_target( $xliff_node ) {
		if ( isset( $xliff_node->target->mrk ) ) {
			$target = (string) $xliff_node->target->mrk;

			return $target;
		} else {
			$target = (string) $xliff_node->target;

			return $target;
		}
	}
}
