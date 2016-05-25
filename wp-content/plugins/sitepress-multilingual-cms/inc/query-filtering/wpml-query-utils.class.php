<?php

/**
 * Class WPML_Query_Utils
 *
 * @package wpml-core
 */
class WPML_Query_Utils extends WPML_WPDB_User {

	/**
	 * Returns the number of posts for a given post_type, author and language combination that is published.
	 *
	 * @param array|string $post_type
	 * @param WP_User      $author_data
	 * @param string       $lang language code to check
	 *
	 * @return int
	 *
	 * @used-by \WPML_Languages::add_author_url_to_ls_lang to determine what languages to show in the Language Switcher
	 */
	public function author_query_has_posts( $post_type, $author_data, $lang ) {
		$post_types        = (array) $post_type;
		$post_type_snippet = (bool) $post_types ? " AND post_type IN (" . wpml_prepare_in( $post_types ) . ") " : "";

		return $this->wpdb->get_var( $this->wpdb->prepare( "
                        SELECT COUNT(p.ID) FROM {$this->wpdb->posts} p
						JOIN {$this->wpdb->prefix}icl_translations t
							ON p.ID=t.element_id AND t.element_type = CONCAT('post_', p.post_type)
						WHERE p.post_author=%d
						  " . $post_type_snippet . "
						  AND post_status='publish'
						  AND language_code=%s",
		                                                   $author_data->ID,
		                                                   $lang ) );
	}

	/**
	 * Returns the number of posts for a given post_type, date and language combination that is published.
	 *
	 * @param string   $lang language code to check
	 * @param  int     $year
	 * @param null|int $month
	 * @param null|int $day
	 * @param string   $post_type
	 *
	 * @return null|string
	 *
	 * @used-by \WPML_Languages::add_date_or_cpt_url_to_ls_lang to determine what languages to show in the Language Switcher
	 */
	public function archive_query_has_posts( $lang, $year = null, $month = null, $day = null, $post_type = 'post' ) {
		$post_types        = (array) $post_type;
		$post_type_snippet = (bool) $post_types ? " AND post_type IN (" . wpml_prepare_in( $post_types ) . ") " : "";
		$year_snippet      = (bool) $year === true ? $this->wpdb->prepare( ' AND year(p.post_date) = %d ', $year ) : '';
		$month_snippet     = (bool) $month === true ? $this->wpdb->prepare( ' AND month(p.post_date) = %d ', $month ) : '';
		$day_snippet       = (bool) $day === true ? $this->wpdb->prepare( ' AND day(p.post_date) = %d ', $day ) : '';

		return $this->wpdb->get_var( $this->wpdb->prepare( "
                        SELECT COUNT(p.ID) FROM {$this->wpdb->posts} p
						JOIN {$this->wpdb->prefix}icl_translations t
							ON p.ID = t.element_id AND t.element_type = CONCAT('post_', p.post_type)
						WHERE post_status='publish'
							" . $year_snippet . $month_snippet . $day_snippet . $post_type_snippet . "
						  AND language_code = %s", $lang ) );
	}
}