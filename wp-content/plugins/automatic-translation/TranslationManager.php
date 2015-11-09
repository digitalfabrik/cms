<?php

const ERR_MSGS_OPTION_KEY = 'automatic-translation-error-messages';
require_once __DIR__ . '/TranslationService.php';

add_action('admin_notices', function () {
	$messages = get_option(ERR_MSGS_OPTION_KEY);
	if (empty($messages)) return;
	foreach ($messages as $i => $message) {
		echo "<div class='update-nag'> <p>$message</p></div>";
		unset($messages[$i]);
		update_option(ERR_MSGS_OPTION_KEY, $messages);
	}
});

class TranslationManager {
	const AUTOMATIC_TRANSLATION_META_KEY = 'automatic_translation';
	const TRANSLATION_DISCLAIMER = '<p><em>This page was translated automatically, manual translation coming soon.</em></p><br>';
	private $translation_service;

	public function __construct() {
		$this->translation_service = new TranslationService();
	}

	public function on_save_post($post_id) {
		update_post_meta($post_id, self::AUTOMATIC_TRANSLATION_META_KEY, false);
		$post = get_post($post_id, OBJECT);
		if (!in_array($post->post_status, ['publish', 'revision'])
			|| ICL_LANGUAGE_CODE !== 'de'
			// Only translate from German for now.
			// This is based on the assumption that content will always be available in German at first,
			// but gets rid of the case where manual changes to previously automatically translated content
			// are propagated into other languages.
		) {
			return;
		}

		$languages = apply_filters('wpml_active_languages', null, '');
		foreach ($languages as $language) {
			$language_code = $language['code'];
			if ($language_code == ICL_LANGUAGE_CODE) { // ignore current language
				continue;
			}
			$this->create_translation($post, $language_code);
		}
	}

	private function create_translation($post, $target_language_code) {
		$current_translation_id = apply_filters('wpml_object_id', $post->ID, $post->post_type, FALSE, $target_language_code);
		if ($current_translation_id === $post->ID) {
			throw new RuntimeException("translated post id equal to source post id");
		}
		// already translated manually
		if ($current_translation_id !== null
			&& !get_post_meta($current_translation_id, self::AUTOMATIC_TRANSLATION_META_KEY, true)
		) {
			return;
		}
		try {
			$translated_post = $this->translation_service->translate_post($post, $target_language_code);
		} catch (Exception $e) {
			$messages = get_option(ERR_MSGS_OPTION_KEY);
			$messages[] = "Fehler beim automatischen Uebersetzen: " . $e->getMessage();
			update_option(ERR_MSGS_OPTION_KEY, $messages);
			return;
		}
		$translated_post['post_content'] = self::TRANSLATION_DISCLAIMER . $translated_post['post_content'];
		if ($current_translation_id !== null) {
			$translated_post['ID'] = $current_translation_id;
		}
		$this->remove_save_post_hook(); // remove and re-add hook to avoid infinite loop
		// potential issue: the last row's content is changed too with wp_insert_post
		$translated_post_id = wp_insert_post($translated_post);
		$this->add_save_post_hook();
		$this->link_wpml($post->ID, $post->post_type, $translated_post_id, $target_language_code);
		$this->mark_as_automatic_translation($translated_post_id);
	}

	private function link_wpml($source_post_id, $source_post_type, $translated_post_id, $language_code) {
		global $sitepress;
		$wpml_post_type = 'post_' . $source_post_type;
		$source_trid = $sitepress->get_element_trid($source_post_id, $wpml_post_type);
		$sitepress->set_element_language_details($translated_post_id, $wpml_post_type, $source_trid, $language_code);
	}

	private function mark_as_automatic_translation($post_id) {
		add_post_meta($post_id, self::AUTOMATIC_TRANSLATION_META_KEY, true);
	}

	public function add_save_post_hook() {
		add_action('save_post', [$this, 'on_save_post']);
	}

	private function remove_save_post_hook() {
		remove_action('save_post', [$this, 'on_save_post']);
	}
}
