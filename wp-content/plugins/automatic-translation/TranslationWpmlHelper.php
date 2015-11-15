<?php

class TranslationWpmlHelper {
	/**
	 * @param $post
	 * @param $target_language_code
	 * @return int|null the id of the post in the target language or null if no such post exists
	 */
	public function get_translated_post_id($post, $target_language_code) {
		return apply_filters('wpml_object_id', $post->ID, $post->post_type, FALSE, $target_language_code);
	}

	public function link_wpml($source_post_id, $source_post_type, $translated_post_id, $language_code) {
		global $sitepress;
		$wpml_post_type = 'post_' . $source_post_type;
		$source_trid = $sitepress->get_element_trid($source_post_id, $wpml_post_type);
		$sitepress->set_element_language_details($translated_post_id, $wpml_post_type, $source_trid, $language_code);
	}
}