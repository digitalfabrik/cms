<?php

class WPML_Locale extends WPML_WPDB_And_SP_User {

	/** @var  string $locale */
	private $locale;

	/** @var bool $theme_locales_loaded */
	private $theme_locales_loaded = false;

	/**
	 * WPML_Locale constructor.
	 *
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 * @param string    $locale
	 */
	public function __construct( &$wpdb, &$sitepress, &$locale ) {
		parent::__construct( $wpdb, $sitepress );
		$this->locale = &$locale;
	}

	public function init() {
		if ( $this->is_need_filter_title_sanitization() ) {
			add_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 10, 2 );
		}
	}

	/**
	 * Hooked to 'sanitize_title' in case the user is using a language that has either German or Danish locale, to
	 * ensure that WP Core sanitization functions handle special chars accordingly.
	 *
	 * @param string $title
	 * @param string $raw_title
	 *
	 * @return string
	 */
	public function filter_sanitize_title( $title, $raw_title ) {
		if ( $title !== $raw_title ) {
			remove_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 10 );
			$chars                            = array();
			$chars[ chr( 195 ) . chr( 132 ) ] = 'Ae';
			$chars[ chr( 195 ) . chr( 133 ) ] = 'Aa';
			$chars[ chr( 195 ) . chr( 134 ) ] = 'Ae';
			$chars[ chr( 195 ) . chr( 150 ) ] = 'Oe';
			$chars[ chr( 195 ) . chr( 152 ) ] = 'Oe';
			$chars[ chr( 195 ) . chr( 156 ) ] = 'Ue';
			$chars[ chr( 195 ) . chr( 159 ) ] = 'ss';
			$chars[ chr( 195 ) . chr( 164 ) ] = 'ae';
			$chars[ chr( 195 ) . chr( 165 ) ] = 'aa';
			$chars[ chr( 195 ) . chr( 166 ) ] = 'ae';
			$chars[ chr( 195 ) . chr( 182 ) ] = 'oe';
			$chars[ chr( 195 ) . chr( 184 ) ] = 'oe';
			$chars[ chr( 195 ) . chr( 188 ) ] = 'ue';
			$title                            = sanitize_title( strtr( $raw_title, $chars ) );
			add_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 10, 2 );
		}

		return $title;
	}

	/**
	 * @return bool|mixed
	 */
	public function locale() {
		global $sitepress;

		add_filter( 'language_attributes', array(
			$this,
			'_language_attributes'
		) );

		$wp_api  = $this->sitepress->get_wp_api();
		$is_ajax = $wp_api->is_ajax();
		if ( $is_ajax && isset( $_REQUEST['action'] ) && isset( $_REQUEST['lang'] ) ) {
			$locale_lang_code = $_REQUEST['lang'];
		} elseif ( $wp_api->is_admin()
		           && ( ! $is_ajax
		                || $this->sitepress->check_if_admin_action_from_referer() )
		) {
			$locale_lang_code = $this->sitepress->user_lang_by_authcookie();
		} else {
			$locale_lang_code = $this->sitepress->get_current_language();
		}
		$locale = $this->get_locale( $locale_lang_code );
		// theme localization
		remove_filter( 'locale', array(
			$sitepress,
			'locale'
		) ); //avoid infinite loop
		if ( ! $this->theme_locales_loaded
		     && (bool) $this->sitepress->get_setting( 'theme_localization_load_textdomain' ) === true
		     && (bool) $this->sitepress->get_setting( 'gettext_theme_domain_name' ) === true
		     && (bool) ( $theme_folder_settings = $this->sitepress->get_setting( 'theme_language_folders' ) ) === true
		) {
			foreach ( $theme_folder_settings as $folder ) {
				$wp_api->load_textdomain( $this->sitepress->get_setting( 'gettext_theme_domain_name' ),
					$folder . '/' . $locale . '.mo' );
			}
			$this->theme_locales_loaded = true;
		}
		add_filter( 'locale', array( $sitepress, 'locale' ) );

		return $locale;
	}

	public function get_locale( $code ) {
		if ( is_null( $code ) ) {

			return false;
		}
		$found  = false;
		$cache_key = 'get_locale' . $code;
		$cache  = new WPML_WP_Cache( '' );
		$locale = $cache->get( $cache_key, $found );
		if ( $found ) {

			return $locale;
		}
		$all_locales_data = $this->wpdb->get_results( "SELECT code, locale FROM {$this->wpdb->prefix}icl_locale_map" );
		foreach ( $all_locales_data as $locales_data ) {
			$all_locales[ $locales_data->code ] = $locales_data->locale;
		}
		$locale = isset( $all_locales[ $code ] ) ? $all_locales[ $code ] : false;
		if ( $locale === false ) {
			$this_locale_data = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT code, default_locale FROM {$this->wpdb->prefix}icl_languages WHERE code = %s",
																$code ) );
			if ( $this_locale_data ) {
				$locale = $this_locale_data->default_locale;
			}
		}
		$cache->set( $cache_key, $locale );

		return $locale;
	}

	public function switch_locale( $lang_code = false ) {
		global $l10n;
		static $original_l10n;
		if ( ! empty( $lang_code ) ) {
			$original_l10n = isset( $l10n[ 'sitepress' ] ) ? $l10n[ 'sitepress' ] : null;
			if ( $original_l10n !== null ) {
				unset( $l10n[ 'sitepress' ] );
			}
			load_textdomain( 'sitepress',
							 ICL_PLUGIN_PATH . '/locale/sitepress-' . $this->get_locale( $lang_code ) . '.mo' );
		} else { // switch back
			$l10n[ 'sitepress' ] = $original_l10n;
		}
	}

	public function get_locale_file_names() {
		$locales = array();
		$res     = $this->wpdb->get_results( "
			SELECT lm.code, locale
			FROM {$this->wpdb->prefix}icl_locale_map lm JOIN {$this->wpdb->prefix}icl_languages l ON lm.code = l.code AND l.active=1" );
		foreach ( $res as $row ) {
			$locales[ $row->code ] = $row->locale;
		}

		return $locales;
	}

	public function set_locale_file_names( $locale_file_names_pairs ) {
		$lfn = $this->get_locale_file_names();
		$new = array_diff( array_keys( $locale_file_names_pairs ), array_keys( $lfn ) );
		if ( ! empty( $new ) ) {
			foreach ( $new as $code ) {
				$this->wpdb->insert( $this->wpdb->prefix . 'icl_locale_map',
							   array( 'code' => $code, 'locale' => $locale_file_names_pairs[ $code ] ) );
			}
		}
		$remove = array_diff( array_keys( $lfn ), array_keys( $locale_file_names_pairs ) );
		if ( ! empty( $remove ) ) {
			$this->wpdb->query( "DELETE FROM {$this->wpdb->prefix}icl_locale_map
                           WHERE code IN (" . wpml_prepare_in( $remove ) . ")" );
		}

		$update = array_diff( $locale_file_names_pairs, $lfn );
		foreach ( $update as $code => $locale ) {
			$this->wpdb->update( $this->wpdb->prefix . 'icl_locale_map', array( 'locale' => $locale ), array( 'code' => $code ) );
		}

		return true;
	}

	private function is_need_filter_title_sanitization() {
		$active_languages = $this->sitepress->get_active_languages();
		$needs_filter     = false;
		foreach ( $active_languages as $lang ) {
			if ( in_array( $lang[ 'default_locale' ], array( 'de_DE', 'da_DK' ) ) ) {
				$needs_filter = true;
				break;
			}
		}

		return $needs_filter;
	}

	function _language_attributes( $latr ) {

		return preg_replace(
			'#lang="(.[a-z])"#i',
			'lang="' . str_replace( '_', '-', $this->locale ) . '"',
			$latr );
	}
}