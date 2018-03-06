<?php

class WpmlHelper {

	public function get_languages() {
		return apply_filters('wpml_active_languages', null, '');
	}

	public function get_language_codes() {
		$languages = $this->get_languages();
		$language_codes = [];
		foreach ($languages as $language) {
			$language_codes[] = $language['code'];
		}
		return $language_codes;
	}

	public function get_available_languages($post, $postMapper) {
		$language_codes = $this->get_language_codes();
		$other_pages_ids = [];
		foreach ($language_codes as $language_code) {
			if ($language_code == ICL_LANGUAGE_CODE) { // ignore current language
				continue;
			}

			$value = $this->$postMapper($post, $language_code);

            if ($value != null) {
                $other_pages_ids[$language_code] = $value;
            }
		}
		return $other_pages_ids;
	}

	private function map_post_to_foreign_language_id($post, $language_code) {
		$id = apply_filters('wpml_object_id', $post->ID, $post->post_type, FALSE, $language_code);
		if ( null == $id || $id == $post->ID ) {
			return null;
		}
		return $id;
	}

	private function map_post_to_foreign_language_url($post, $language_code) {
		// CURRENTLY NOT WORKING: $wpml_permalink = apply_filters('wpml_permalink', get_page_link($post->ID), $language_code);
		global $sitepress;
		$current_language = $sitepress->get_current_language();
		$sitepress->switch_lang($language_code, true);
		$wpml_permalink = get_permalink($this->map_post_to_foreign_language_id($post, $language_code));
		$sitepress->switch_lang($current_language, true);
		return $wpml_permalink;
	}

}
