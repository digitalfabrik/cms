<?php

class WPML_Translation_Editor extends WPML_WPDB_And_SP_User {

	private $target_lang;
	private $lang_source;
	private $term_elements = array();
	private $terms = array();
	private $click_to_toggle_html;
	private $original_content_paragraph_html;
	private $rtlo;
	private $rtlt;

	/**
	 * @param SitePress $sitepress
	 * @param wpdb      $wpdb
	 * @param string    $target_lang the display name of the target language
	 * @param string    $source_lang the display name of the source language
	 * @param string    $rtl_original
	 * @param string    $rtl_translated
	 */
	public function __construct( &$sitepress, &$wpdb, $target_lang, $source_lang, $rtl_original, $rtl_translated ) {
		parent::__construct( $wpdb, $sitepress );
		$this->target_lang                     = $target_lang;
		$this->lang_source                     = $source_lang;
		$this->click_to_toggle_html            = '<div title="' . __( 'Click to toggle',
		                                                              'wpml-translation-management' ) . '" class="handlediv"><br /></div>';
		$this->original_content_paragraph_html = $this->render_original_content_paragraph();
		$this->rtlo                            = $rtl_original;
		$this->rtlt                            = $rtl_translated;

		add_filter( 'tiny_mce_before_init', array( $this, 'filter_original_editor_buttons' ), 10, 2 );
		add_filter( 'the_editor', array( $this, 'filter_original_editor_textarea' ), 10, 1 );
		$this->enqueue_js();
		add_action( 'after_wp_tiny_mce', array( $this, 'init_tinymce' ) );
	}

    public function filter_original_editor_buttons( $config, $editor_id ) {
        if ( strpos( $editor_id, 'original_' ) === 0 ) {
            $config[ 'toolbar1' ] = " ";
            $config[ 'toolbar2' ] = " ";
            $config[ 'readonly' ] = "1";
        }

        return $config;
    }

    public function filter_original_editor_textarea( $content ) {
        if ( strpos( $content, 'id="wp-original_' ) !== false ) {
            $content = str_replace( '<textarea ', '<textarea disabled ', $content );
        }

        return $content;
    }

	public static function get_job_id_from_request() {
		/**
		 * @var TranslationManagement $iclTranslationManagement
		 * @var WPML_Post_Translation $wpml_post_translations
		 */
		global $iclTranslationManagement, $wpml_post_translations, $wpml_translation_job_factory, $sitepress, $wpdb;

		$job_id               = filter_input( INPUT_GET, 'job_id', FILTER_SANITIZE_NUMBER_INT );
		$trid                 = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );
		$language_code        = filter_input( INPUT_GET, 'language_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$source_language_code = filter_input( INPUT_GET, 'source_language_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $job_id && $trid && $language_code ) {
			$job_id = $iclTranslationManagement->get_translation_job_id( $trid, $language_code );
			if ( ! $job_id ) {
				if ( ! $source_language_code ) {
					$post_id = SitePress::get_original_element_id_by_trid( $trid );
				} else {
					$posts_in_trid = $wpml_post_translations->get_element_translations( false, $trid );
					$post_id       = isset( $posts_in_trid[ $source_language_code ] ) ? $posts_in_trid[ $source_language_code ] : false;
				}
				$blog_translators = wpml_tm_load_blog_translators();
				$args             = array(
					'lang_from' => $source_language_code,
					'lang_to'   => $language_code,
					'job_id'    => $job_id
				);
				if ( $post_id && $blog_translators->is_translator( $sitepress->get_current_user()->ID, $args ) ) {
					$job_id = $wpml_translation_job_factory->create_local_post_job( $post_id, $language_code );
				}
			}
		}

		return $job_id;
	}

	/**
	 * Enqueues the JavaScript used by the TM editor.
     */
    public function enqueue_js() {
        wp_register_script (
            'wpml-tm-editor-scripts',
            WPML_TM_URL . '/res/js/translation-editor.js',
            array( 'jquery', 'jquery-ui-dialog' ),
            WPML_TM_VERSION,
            true
        );
        wp_enqueue_script ( 'wpml-tm-editor-scripts' );
        wp_localize_script ( 'wpml-tm-editor-scripts', 'tmEditorStrings', $this->get_translation_editor_labels () );
    }

    public function init_tinymce(){
        echo '<script type="text/javascript">
				tinyMCE.on("onKeyUp", function (tmce) {
					tmEditor.update_copy_link_visibility();
				});
            </script>';
    }

	private function get_translation_editor_labels() {

		$labels = array(
			'dontShowAgain' => __( "Don't show this again.",
			                       'wpml-translation-management' ),
			'learnMore'     => __( '<p>The administrator has disabled term translation from the translation editor. </p>
<p>If your access permissions allow you can change this under "Translation Management" - "Multilingual Content Setup" - "Block translating taxonomy terms that already got translated". </p>
<p>Please note that editing terms from the translation editor will affect all posts that have the respective terms associated.</p>',
			                       'wpml-translation-management' ),
			'warning'       => __( "Please be advised that editing this term's translation here will change the value of the term in general. The changes made here, will not only affect this post!",
			                       'wpml-translation-management' ),
			'title'					=> __("Terms translation is disabled", 'wpml-translation-management')
		);


		return $labels;
	}

	public function get_click_to_toggle_html() {
		return $this->click_to_toggle_html;
	}

	/**
	 * Generates the HTML for the header of a postbox.
	 *
	 * @param $title string
	 *
	 * @return string
	 */
	public function get_post_box_header( $title ) {
		return '<h3 class="hndle">' . $title . '</h3>';
	}

	/**
	 * Wrapper that allows the adding of one job element, that belongs to a term to this object,
	 * so that it's inputs can later be rendered.
	 *
	 * @param $term_element object
	 */
	public function add_term( $term_element ) {
		$this->term_elements [ ] = $term_element;
	}

	/**
	 * @return string HTML of all taxonomy term meta-boxes, needed for the translation job.
	 */
	public function render_term_metaboxes() {

		$this->terms = $this->format_term_elements( $this->term_elements );
		$html        = '<div class="metabox-holder" id="taxonomy-terms">';

		foreach ( $this->terms as $taxonomy => $terms_in_taxonomy ) {
			$html .= $this->render_taxonomy_metabox( $taxonomy );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the HTML for a taxonomy metabox, holding all terms of that taxonomy.
	 *
	 * Identifier of the taxonomy, like for example 'post_tag'
	 * @param $taxonomy String
	 *
	 * @return string
	 */
	private function render_taxonomy_metabox( $taxonomy ) {
		global $sitepress;

		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( $taxonomy_object && isset( $taxonomy_object->label ) ) {
			$taxonomy_label = $taxonomy_object->label;
		} else {
			$taxonomy_label = $taxonomy;
		}

		$html = '<div class="postbox-container icl-tj-postbox-container-' . $taxonomy_label . '">';
		$html .= '<div class="meta-box-sortables ui-sortable" id="icl-translation-job-sortables-' . $taxonomy_label . '">';
		$html .= '<div class="postbox" id="icl-translation-job-element-' . $taxonomy_label . '">';
		$html .= $this->get_click_to_toggle_html();
		$html .= $this->get_post_box_header( $taxonomy_label );
		$html .= '<div class="inside">';
		$html .= '<p></p>';
		$html .= $this->get_translated_content_paragraph( $taxonomy, false );
		$blocked = $sitepress->get_setting( 'tm_block_retranslating_terms' );

		$additional_styles = '';
		$learn_more        = '';
		if ( $blocked ) {
			$explanation       = __( '<p>Some terms in this taxonomy have already been translated.</p> <p>If your user permissions allow, you can edit the translation from the "Taxonomy Translation" admin screen.</p>',
			                         'wpml-translation-management' );
			$learn_more        = '&nbsp;&nbsp;<a href="#" class="tm-learn-more">' . __( "Learn more",
			                                                                            'wpml-translation-management' ) . '</a>';
			$additional_styles = 'style="color:red;"';
		} else {
			$explanation = __( "Some terms have already been translated. If you want to edit their translation, please uncheck the box below their value.",
			                   'wpml-translation-management' );
		}

		$html .= '<div class="icl-tm-explanation" ' . $additional_styles . '>' . $explanation . $learn_more . '</div>';

		$terms_in_taxonomy = $this->terms[ $taxonomy ];

		$rows = array();
		foreach ( $terms_in_taxonomy as $term ) {
			$rows[ ] = $this->render_html_single_term_row( $term[ 'original' ],
			                                               $term[ 'translation' ],
			                                               $term[ 'ttid_string' ],
			                                               $term[ 'tid' ],
			                                               $blocked );
		}

		foreach ( $rows as $row ) {
			$html .= $row[ 'translated' ];
		}

		$html .= $this->get_original_content_paragraph();

		$html .= '<div class="icl-tj-original">';
		$html .= '<div class="icl_multiple" dir="' . $this->rtlo . '">';
		foreach ( $rows as $row ) {
			$html .= $row[ 'original' ];
		}

		$html .= str_repeat( '</div>', 6 );

		return $html;
	}

	/**
	 * @param $original_content String
	 * @param $existing_content String
	 * @param $field_type String
	 * @param $tid Integer
	 * @param $blocked Boolean
	 *
	 * An associative array holding the html for the original value row as well as the html for the row
	 * containing the translated value of a field.
	 *
	 * @return array
	 */
	private function render_html_single_term_row( $original_content, $existing_content, $field_type, $tid, $blocked ) {

		$html = '<span><label>';
		$html .= '<input ';
		$html .= 'id="' . sanitize_title( $field_type ) . '"';
		$html .= 'class="icl_multiple" ';
		$html .= 'type="text" ';
		if ( ! ( $blocked && $existing_content ) ) {
			$html .= 'name="fields[' . esc_attr( $field_type ) . '][data]" ';
		}
		$html .= 'value="' . $existing_content . '" ';
		if ( $existing_content ) {
			$html .= 'disabled="disabled"';
		}
		$html .= $this->rtlt;
		$html .= ' />';
		$html .= '</label>';

		if ( ! ( $blocked && $existing_content ) ) {
			$html .= $this->get_finished_checkbox_html( $existing_content, false, $field_type, true );
			$html .= $this->hidden_field_html( $tid, $field_type, 'csv_base64' );
		}
		$html .= '</span>';

		return array(
			'translated' => $html,
			'original'   => $this->render_html_single_original_term_row( $original_content )
		);
	}

	private function render_html_single_original_term_row( $original_content ) {
		$html = '<div class="icl_multiple" ' . $this->rtlo . '>';
		$html .= '<div style="float: left;margin-right:4px;">' . $original_content . '</div>';
		$html .= '<br clear="all">';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the html of a hidden field that is used by TM to correctly associate data submitted from the TM Editor
	 * with its job and field.
	 *
	 * The tid entry for the translated field's data in the icl_translate table.
	 *
	 * @param $tid Integer
	 * @param $field_type String
	 * The type of encoding, the field uses when saved to the database.
	 * @param $field_format String
	 *
	 * @return string
	 */
	public function hidden_field_html( $tid, $field_type, $field_format ) {

		$field_start = '<input type="hidden" name="fields[' . esc_attr( $field_type ) . ']';
		$html        = $field_start . '[format]" value="' . $field_format . '" />';
		$html .= $field_start . '[tid]" value="' . $tid . '" />';

		return $html;
	}

	/**
	 * Returns the html of a 'This translation is finished.' checkbox.
	 *
	 * Sets whether or not the checkbox is checked by default.
	 *
	 * @param $finished boolean
	 * Sets whether or not the checkbox applies to a multivalued field.
	 * @param $multiple boolean
	 * @param $field_type string
	 * Sets whether or not the checkbox applies to the translated value of a taxonomy term.
	 * @param bool $term
	 *
	 * @return string
	 */
	public function get_finished_checkbox_html( $finished, $multiple, $field_type, $term = false ) {

		$class = '"icl_tm_finished';
		if ( $multiple ) {
			$class .= ' icl_tmf_multiple';
		}
		if ( $term ) {
			$class .= ' icl_tmf_term';
		}
		$class .= '"';

		$html = '<p><label><input class=' . $class;
		$html .= ' type="checkbox"';
		$html .= ' name = "fields[' . $field_type . '][finished]" value="1"';

		if ( $finished ) {
			$html .= ' checked="checked" ';
		}
		$html .= '/>  ';

		$html .= __( 'This translation is finished.', 'wpml-translation-management' );
		$html .= '</label>';
		$html .= '<span class="icl_tm_error" style="display: none;">' . __( 'This field cannot be empty',
		                                                                    'wpml-translation-management' ) . '</span>';
		$html .= '</p>';

		return $html;
	}

	/**
	 * Renders and then returns the paragraph heading translated content sections.
	 *
	 * @param $field_type string
	 *
	 * Sets whether or not a 'Copy from Original' link is to be displayed for this paragraph.
	 * @param $show_copy_link boolean
	 *
	 * @return string
	 */
	public function get_translated_content_paragraph( $field_type, $show_copy_link ) {
		$html = '<p>';
		$html .= __( 'Translated content', 'wpml-translation-management' ) . ' - ' . $this->target_lang;
        $html .= $show_copy_link ? $this->render_copy_from_original_link($field_type) : '';
		$html .= '</p>';

		return $html;
	}

    public function render_copy_from_original_link($field_type = false ){
        $caption = $field_type === false ? __( 'Copy all fields from %s', 'wpml-translation-management' )
                                         : __( 'Copy from %s', 'wpml-translation-management' );
        $caption = sprintf ( $caption, $this->lang_source );
        $sep = $field_type === false ? '' : '| &nbsp;';
        $field_type = $field_type === false ? 'icl_all_fields' : sanitize_title($field_type);
        $html = '<span>'. $sep .'<a class="icl_tm_copy_link" id="icl_tm_copy_link_' . $field_type . '"';
        $html .= 'href="#">' . $caption . '</a></span>';

        return $html;
    }

	/**
	 * Renders and then returns the html paragraph heading the original contents of a translated field.
	 *
	 * @return string
	 */
	private function render_original_content_paragraph() {
		$html = '<p>';
		$html .= __( 'Original content', 'wpml-translation-management' );
		$html .= ' - ' . $this->lang_source;
		$html .= '</p>';

		return $html;
	}

	/**
	 * Returns the html paragraph heading the original contents of a translated field.
	 *
	 * @return string
	 */
	public function get_original_content_paragraph() {
		return $this->original_content_paragraph_html;
	}

	/**
	 * Formats term data retrieved from the database.
	 *
	 * @param $data array
	 *
	 * @return array
	 */
	private function format_term_elements( $data ) {
		global $wpdb;

		$terms = array();

		foreach ( $data as $element ) {
			$term_translation                            = base64_decode( $element->field_data_translated );
			if(!$term_translation) {
				$post_fields                    = isset($_POST[ 'fields' ]) ? $_POST[ 'fields' ] : null;
				$current_element_in_post_fields = $post_fields ? $post_fields[ $element->field_type ] : null;
				if ( $current_element_in_post_fields && $current_element_in_post_fields[ 'tid' ] == $element->tid ) {
					$term_translation = $current_element_in_post_fields[ 'data' ];
				}
			}

			$terms [ substr( $element->field_type, 2 ) ] = array(
				'original'    => base64_decode( $element->field_data ),
				'translation' => $term_translation,
				'tid'         => $element->tid,
				'ttid_string' => $element->field_type
			);
		}
		$return = array();

		$ttids = array_keys( $terms );

		if ( ! empty( $ttids ) ) {
			$ttid_in_query_fragment = "term_taxonomy_id IN (" . wpml_prepare_in( $ttids, '%d' ) . ")";
			$query = "SELECT taxonomy, term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE " . $ttid_in_query_fragment;
			$res = $wpdb->get_results( $query );

			foreach ( $res as $term ) {
				if ( ! isset( $return[ $term->taxonomy ] ) ) {
					$return[ $term->taxonomy ] = array();
				}
				$return[ $term->taxonomy ][ ] = $terms[ $term->term_taxonomy_id ];
			}
		}

		return $return;
	}

	/**
	 * Applies filters to a custom field job element.
	 * Custom fields that were named with numeric suffixes are stripped of these suffixes.
	 *
	 * @param object $element
	 * @param object $job
	 *
	 * @return array
	 */
	public function custom_field_data( $element, $job ) {

		$element_field_type_parts = explode( '-', $element->field_type );
		$last_part                = array_pop( $element_field_type_parts );
		$unfiltered_type          = empty( $element_field_type_parts )
			? $last_part
			: ( implode( '-', $element_field_type_parts )
			    . ( is_numeric( $last_part ) ? '' : '-' . $last_part ) );

		$element_field_type = $unfiltered_type;
		/**
		 * @deprecated Use `wpml_editor_custom_field_name` filter instead
		 * @since 3.2
		 */
		$element_field_type       = apply_filters( 'icl_editor_cf_name', $element_field_type );
		$element_field_type       = apply_filters( 'wpml_editor_custom_field_name', $element_field_type );

		$element_field_style      = 1;
		/**
		 * @deprecated Use `wpml_editor_custom_field_style` filter instead
		 * @since 3.2
		 */
		$element_field_style      = apply_filters( 'icl_editor_cf_style',
		                                           $element_field_style,
		                                           $unfiltered_type );
		$element_field_style      = apply_filters( 'wpml_editor_custom_field_style',
		                                           $element_field_style,
		                                           $unfiltered_type );

		$element                  = apply_filters( 'wpml_editor_cf_to_display', $element, $job );

		return array( $element_field_type, $element_field_style, $element );
	}

}

