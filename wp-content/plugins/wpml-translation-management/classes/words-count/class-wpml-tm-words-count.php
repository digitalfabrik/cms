<?php

class WPML_TM_Words_Count {

	/**
	 * @var array
	 */
	private $nonPreSelectedTypes;
	/**
	 * @var array
	 */
	private $report;
	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var WPDB
	 */
	private $wpdb;

	/**
	 * @param SitePress   $sitepress
	 * @param WPML_WP_API $wpml_wp_api
	 * @param WPDB        $wpdb
	 */
	public function __construct( &$sitepress, &$wpml_wp_api, &$wpdb ) {
		$this->wpdb        = &$wpdb;
		$this->wpml_wp_api = &$wpml_wp_api;
		$this->sitepress   = &$sitepress;

		$this->nonPreSelectedTypes = array( 'attachment' );

		if ( $this->wpml_wp_api->is_back_end() ) {
			add_filter( 'wpml_words_count_url', array( $this, 'words_count_url_filter' ) );
		}
	}

	public function words_count_url_filter( $default_url ) {
		return $this->get_words_count_url();
	}

	private function get_words_count_url() {
		return $this->wpml_wp_api->get_tm_url( 'dashboard', '#words-count' );
	}

	public function get_summary( $source_language ) {
		$this->report = array();
		$this->get_posts_summary( $source_language );
		$this->get_strings_summary( $source_language );

		return array_values( $this->report );
	}

	private function get_posts_summary( $source_language ) {
		$posts_query    = "
		SELECT
		  p.ID, p.post_type, t.element_type, (SELECT count(tt.trid) from {$this->wpdb->prefix}icl_translations tt WHERE tt.trid = t.trid) as translations
		FROM {$this->wpdb->prefix}icl_translations t
		  INNER JOIN {$this->wpdb->prefix}posts p
		    ON t.element_id = p.ID AND t.element_type = CONCAT('post_', p.post_type)
		WHERE t.language_code = %s
		GROUP BY p.ID, p.post_type
		ORDER BY p.post_type;
		";
		$posts_prepared = $this->wpdb->prepare( $posts_query, $source_language );
		$posts          = $this->wpdb->get_results( $posts_prepared );

		$active_languages       = $this->sitepress->get_active_languages();
		$active_languages_count = count( $active_languages );

		foreach ( $posts as $post ) {
			$post_type = $post->post_type;
			$wpml_post = new WPML_Post( $post->ID, $this->sitepress, $this->wpdb );
			$untranslated_languages = $active_languages_count - $post->translations;
			if ( true == apply_filters( 'wpml_is_translated_post_type', false, $post_type ) ) {
				if ( ! isset( $this->report[ $post_type ] ) ) {
					$this->init_post_type_report( $post_type, $wpml_post );
				}
				$this->report[ $post_type ][ 'count' ][ 'total' ] ++;
				if ( $post->translations < $active_languages_count ) {
					$this->report[ $post_type ][ 'count' ][ 'untranslated' ] += $untranslated_languages;
				}
				$element_attributes = array(
					'element_id'   => $post->ID,
					'element_type' => $post->element_type,
					'post_type'    => $post->post_type,
				);

				$words_count = $wpml_post->get_words_count();
				$words_count = apply_filters( 'wpml_element_words_count', $words_count, $element_attributes );
				$this->report[ $post_type ][ 'words' ][ 'total' ] += $words_count;
				$this->report[ $post_type ][ 'words' ][ 'untranslated' ] += $words_count * $untranslated_languages;
			}
		}
	}

	private function get_strings_summary( $source_language ) {
		$strings_query    = "
				SELECT
				  s.id, s.context as domain, s.gettext_context as context, s.name, s.value,
				  (SELECT count(*)
				  	FROM {$this->wpdb->prefix}icl_string_translations t
				  	WHERE t.string_id = s.id AND t.language<>s.language) as translations
				FROM {$this->wpdb->prefix}icl_strings s
				WHERE s.language = %s
				ORDER BY s.context, s.domain_name_context_md5;
				";
		$strings_prepared = $this->wpdb->prepare( $strings_query, $source_language );
		$strings          = $this->wpdb->get_results( $strings_prepared );

		$active_languages       = $this->sitepress->get_active_languages();
		$active_languages_count = count( $active_languages );

		foreach ( $strings as $string ) {
			$wpml_string = new WPML_String( $string->id, $this->sitepress, $this->wpdb );
			if ( ! isset( $this->report[ 'strings' ] ) ) {
				$this->init_strings_report( $wpml_string );
			}
			$type = 'strings';
			$this->report[ $type ][ 'count' ][ 'total' ] ++;
			if ( $string->translations < $active_languages_count ) {
				$this->report[ $type ][ 'count' ][ 'untranslated' ] += ( $active_languages_count - $string->translations - 1 );
			}
			$element_attributes = array(
				'element_id'   => $string->id,
				'element_type' => 'string',
				'post_type'    => 'string',
			);

			$words_count = $wpml_string->get_words_count();
			$words_count = apply_filters( 'wpml_element_words_count', $words_count, $element_attributes );
			$this->report[ $type ][ 'words' ][ 'total' ] += $words_count;
			$this->report[ $type ][ 'words' ][ 'untranslated' ] = ( $this->report[ $type ][ 'words' ][ 'total' ] ) * $this->report[ $type ][ 'count' ][ 'untranslated' ];
		}
	}

	/**
	 * @param string    $post_type
	 * @param WPML_Post $wpml_post
	 */
	private function init_post_type_report( $post_type, $wpml_post ) {
		$this->report[ $post_type ] = array(
			'selected' => ! in_array( $post_type, $this->nonPreSelectedTypes ),
			'type'     => $wpml_post->get_type_name( 'name' ),
			'count'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
			'words'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
		);
	}

	/**
	 * @param WPML_String $wpml_string
	 */
	private function init_strings_report( $wpml_string ) {
		$this->report[ 'strings' ] = array(
			'selected' => true,
			'type'     => $wpml_string->get_type_name(),
			'count'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
			'words'    => array(
				'total'        => 0,
				'untranslated' => 0,
			),
		);
	}

	public function get_words_counts() {
		return $this->wpdb->get_results();
	}

	public function set_words_count_panel_default_status( $open = true ) {
		$box_status = $open ? 'open' : 'closed';

		return update_option( 'wpml_words_count_panel_default_status', $box_status );
	}
}