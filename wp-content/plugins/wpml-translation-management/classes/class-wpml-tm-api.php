<?php

class WPML_TM_API {

	/** @var TranslationManagement */
	private $TranslationManagement;

	/** @var WPML_TM_Blog_Translators $blog_translators */
	private $blog_translators;

	/**
	 * WPML_TM_API constructor.
	 *
	 * @param WPML_TM_Blog_Translators $blog_translators
	 * @param TranslationManagement    $TranslationManagement
	 */
	public function __construct( &$blog_translators, &$TranslationManagement ) {
		$this->blog_translators      = &$blog_translators;
		$this->TranslationManagement = &$TranslationManagement;
	}

	public function init_hooks() {
		add_filter( 'wpml_is_translator', array(
			$this,
			'is_translator_filter'
		), 10, 3 );
		add_filter( 'wpml_translator_languages_pairs', array(
			$this,
			'translator_languages_pairs_filter'
		), 10, 2 );
		add_action( 'wpml_edit_translator', array(
			$this,
			'edit_translator_action'
		), 10, 2 );
	}

	/**
	 * @param bool        $default
	 * @param int|WP_User $user
	 * @param array       $args
	 *
	 * @return bool
	 */
	public function is_translator_filter( $default, $user, $args ) {
		$result  = $default;
		$user_id = $this->get_user_id( $user );
		if ( is_numeric( $user_id ) ) {
			$result = $this->blog_translators->is_translator( $user_id, $args );
		}

		return $result;
	}

	public function edit_translator_action( $user, $language_pairs ) {
		$user_id = $this->get_user_id( $user );
		if ( is_numeric( $user_id ) ) {
			$this->TranslationManagement->edit_translator( $user_id, $language_pairs );
		}
	}

	public function translator_languages_pairs_filter( $default, $user ) {
		$result  = $default;
		$user_id = $this->get_user_id( $user );
		if ( is_numeric( $user_id ) ) {
			if ( $this->blog_translators->is_translator( $user_id ) ) {
				$result = $this->blog_translators->get_language_pairs( $user_id );
			}
		}

		return $result;
	}

	/**
	 * @param $user
	 *
	 * @return int
	 */
	private function get_user_id( $user ) {
		$user_id = $user;

		if ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;

			return $user_id;
		}

		return $user_id;
	}
}