<?php

class WPML_Pre_Option_Page extends WPML_WPDB_And_SP_User {

	private $switched;
	private $lang;
	
	public function __construct( &$wpdb, &$sitepress, $switched, $lang ) {
		parent::__construct( $wpdb, $sitepress );
		
		$this->switched = $switched;
		$this->lang     = $lang;
	}
	
	public function get( $type ) {

		$cache_key   = $type;
		$cache_group = 'wpml_pre_option_page';
		$cache_found = false;
		
		$cache       = new WPML_WP_Cache( $cache_group );
		$results     = $cache->get( $cache_key, $cache_found );

		if ( ( ( ! $cache_found || ! isset ( $results[ $type ] ) ) && ! $this->switched )
		     || ( $this->switched && $this->sitepress->get_setting( 'setup_complete' ) )
		) {
			$results[ $type ] = array();
			// Fetch for all languages and cache them.
			$values = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"	SELECT element_id, language_code
						FROM {$this->wpdb->prefix}icl_translations
						WHERE trid =
							(SELECT trid
							 FROM {$this->wpdb->prefix}icl_translations
							 WHERE element_type = 'post_page'
							 AND element_id = (SELECT option_value
											   FROM {$this->wpdb->options}
											   WHERE option_name=%s
											   LIMIT 1))
						",
					$type
				)
			);

			foreach ( $values as $lang_result ) {
				$results [ $type ] [ $lang_result->language_code ] = $lang_result->element_id;
			}

			if ( $results ) {
				$cache->set( $cache_key, $results );
			}
		}

		return isset( $results[ $type ][ $this->lang ] ) ? $results[ $type ][ $this->lang ] : '';
	}

}