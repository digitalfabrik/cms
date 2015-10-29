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

	public function get_available_languages($post_id, $post_type) {
		$language_codes = $this->get_language_codes();
		$other_pages_ids = [];
		foreach ($language_codes as $language_code) {
			if ($language_code == ICL_LANGUAGE_CODE) { // ignore current language
				continue;
			}
			$id = apply_filters('wpml_object_id', $post_id, $post_type, FALSE, $language_code);
			if ($id == null
				|| $id == $post_id // happens for events
			) {
				continue;
			}
			$other_pages_ids[$language_code] = $id;
		}
		return $other_pages_ids;
	}
}
