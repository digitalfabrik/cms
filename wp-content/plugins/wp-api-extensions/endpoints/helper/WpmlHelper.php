<?php

class WpmlHelper {
	private $cached_language_codes = null;

	public function get_languages() {
		return apply_filters('wpml_active_languages', null, '');
	}

	public function get_language_codes() {
		if ($this->cached_language_codes != null) {
			return $this->cached_language_codes;
		}

		$languages = $this->get_languages();
		$this->cached_language_codes = [];
		foreach ($languages as $language) {
			$this->cached_language_codes[] = $language['code'];
		}
		return $this->cached_language_codes;
	}

	public function get_available_languages($post_id, $post_type) {
		$language_codes = $this->get_language_codes();
		$other_pages_ids = [];
		foreach ($language_codes as $language_code) {
			if ($language_code == ICL_LANGUAGE_CODE) {
				continue;
			}
			$id = apply_filters('wpml_object_id', $post_id, $post_type, FALSE, $language_code);
			if ($id == null) {
				continue;
			}
			$other_pages_ids[] = $id;
		}
		return $other_pages_ids;
	}
}
