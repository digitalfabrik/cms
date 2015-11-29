<?php

class WPML_TM_Translation_Status_Display {

	private $language_pairs = array();
	private $current_lang;
	private $statuses       = array();
	private $editor_is_default;

	public function __construct( $user_id, $user_is_admin, $language_pairs, $current_lang, $active_languages ) {
		$this->language_pairs    = $language_pairs;
		$this->current_lang      = $current_lang;
		$this->active_langs      = $active_languages;
		$this->editor_is_default = icl_get_setting( 'doc_translation_method' );
		add_action( 'wpml_cache_clear', array( $this, 'init' ), 11, 0 );
	}

	public function init( $refresh = true ) {
		add_filter( 'wpml_icon_to_translation', array( $this, 'filter_status_icon' ), 10, 4 );
		add_filter( 'wpml_link_to_translation', array( $this, 'filter_status_link' ), 10, 4 );
		add_filter( 'wpml_text_to_translation', array( $this, 'filter_status_text' ), 10, 4 );
	}

	private function get_user_id() {
		return get_current_user_id();
	}

	private function is_current_user_admin() {
		return $this->user_is_admin = current_user_can( 'manage_options' );
	}

	private function maybe_load_stats( $trid ) {
		if ( !isset( $this->statuses[ $trid ] ) ) {
			global $wpdb;

			$stats                   = $wpdb->get_results (
				$wpdb->prepare (
					"SELECT st.status, l.code, st.translator_id, st.translation_service
								FROM {$wpdb->prefix}icl_languages l
								LEFT JOIN {$wpdb->prefix}icl_translations i
									ON l.code = i.language_code
								JOIN {$wpdb->prefix}icl_translation_status st
									ON i.translation_id = st.translation_id
								WHERE l.active = 1
									AND i.trid = %d
									OR i.trid IS NULL",
					$trid
				),
				ARRAY_A
			);
			$this->statuses[ $trid ] = array();
			foreach ( $stats as $element ) {
				$this->statuses[ $trid ][ $element[ 'code' ] ] = $element;
			}
		}
	}

	private function is_remote( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ][ 'translation_service' ] )
		       && (bool) $this->statuses[ $trid ][ $lang ][ 'translation_service' ] !== false
		       && $this->statuses[ $trid ][ $lang ][ 'translation_service' ] !== 'local';
	}

	private function is_in_progress( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ][ 'status' ] )
		       && ( $this->statuses[ $trid ][ $lang ][ 'status' ] == ICL_TM_IN_PROGRESS
		            || $this->statuses[ $trid ][ $lang ][ 'status' ] == ICL_TM_WAITING_FOR_TRANSLATOR );
	}

	private function is_wrong_translator( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ][ 'translator_id' ] )
		       && $this->statuses[ $trid ][ $lang ][ 'translator_id' ] != $this->get_user_id() && !$this->is_current_user_admin();
	}

	private function is_in_basket( $trid, $lang ) {
		$status_helper = wpml_get_post_status_helper ();

		return $status_helper->get_status ( false, $trid, $lang ) === ICL_TM_IN_BASKET;
	}

	private function is_lang_pair_allowed( $lang ) {
		$args = array(
			'lang_from'      => $this->current_lang,
			'lang_to'        => $lang,
			'admin_override' => $this->is_current_user_admin(),
		);

//		$result = apply_filters( 'wpml_is_translator', false, $this->user_id, $args );

		$result = apply_filters( 'wpml_is_translator', false, $this->get_user_id(), $args );

		return $result;
	}

	private function exists( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ] );
	}

	public function filter_status_icon( $icon, $post_id, $lang, $trid ) {
		$this->maybe_load_stats( $trid );

		if ( ( $this->is_remote( $trid, $lang )
					 || $this->is_wrong_translator( $trid, $lang ) )
				 && $this->is_in_progress( $trid, $lang )
		) {
			$icon = 'in-progress.png';
		} elseif ( $this->is_in_basket( $trid, $lang )
							 || ( ! $this->is_lang_pair_allowed( $lang ) && $this->exists( $trid, $lang ) )
		) {
			$icon = 'edit_translation_disabled.png';
		} elseif ( ! $this->is_lang_pair_allowed( $lang ) && ! $this->exists( $trid, $lang ) ) {
			$icon = 'add_translation_disabled.png';
		}

		return $icon;
	}

	public function filter_status_text( $text, $original_post_id, $lang, $trid ) {
		global $wpml_post_translations;

		$this->maybe_load_stats ( $trid );

		if ( $this->is_remote ( $trid, $lang ) ) {
			if ( $wpml_post_translations->get_source_lang_code( $original_post_id ) === $lang ) {
				$text = __ (
					"You can't edit this document, because translations of this document are currently in progress.",
					'sitepress'
				);
			} else {
				$text = sprintf (
					__ (
						"You can't edit this translation, because this translation to %s is already in progress.",
						'sitepress'
					),
					$this->active_langs[ $lang ][ 'display_name' ]
				);
			}
		} elseif ( $this->is_in_basket ( $trid, $lang ) ) {
			$text = __ (
				'Cannot edit this item, because it is currently in the translation basket.',
				'sitepress'
			);
		}

		return $text;
	}

	public function filter_status_link( $link, $post_id, $lang, $trid ) {
		global $wpml_post_translations;
		$this->maybe_load_stats( $trid );

		$is_remote = $this->is_remote( $trid, $lang );
		$is_in_progress = $this->is_in_progress( $trid, $lang );
		$is_in_basket = $this->is_in_basket( $trid, $lang );
		$is_lang_pair_allowed = $this->is_lang_pair_allowed( $lang );
		$trid_lang_exists = $this->exists( $trid, $lang );
		$source_language_code = $wpml_post_translations->get_source_lang_code( $post_id );

		$tm_editor_link_base_url = 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php';

		if ( ( $is_remote && $is_in_progress ) || $is_in_basket || ! $is_lang_pair_allowed ) {
			$link = '###';
		} elseif ( ( $is_in_progress && ! $is_remote || $this->editor_is_default && $trid_lang_exists ) && $source_language_code !== $lang ) {
			global $iclTranslationManagement;
			$link = $tm_editor_link_base_url . '&job_id=' . $iclTranslationManagement->get_translation_job_id( $trid, $lang );
		} elseif ( $this->editor_is_default && ! $this->exists( $trid, $lang ) ) {
			$link = $tm_editor_link_base_url . '&trid=' . $trid . '&language_code=' . $lang . '&source_language_code=' . $this->current_lang;
		}

		return $link;
	}
}