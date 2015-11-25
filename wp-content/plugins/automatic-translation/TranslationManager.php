<?php

const ERR_MSGS_OPTION_KEY = 'automatic-translation-error-messages';
require_once __DIR__ . '/TranslationService.php';
require_once __DIR__ . '/TranslationWpmlHelper.php';

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
	const TRANSLATION_DISCLAIMER = '<p><em>This page was translated automatically, manual translation coming soon.</em></p>';
	private $translation_service;

	public function __construct() {
		$this->translation_service = new TranslationService();
		$this->wpml_helper = new TranslationWpmlHelper();
	}

	public function on_save_post($post_id) {
		update_post_meta($post_id, self::AUTOMATIC_TRANSLATION_META_KEY, false);
		$post = get_post($post_id, OBJECT);
		if (!$this->accept_post($post)) {
			return;
		}

		$languages = apply_filters('wpml_active_languages', null, '');
		foreach ($languages as $language) {
			$language_code = $language['code'];
			if ($language_code == ICL_LANGUAGE_CODE) { // ignore current language
				continue;
			}
			try {
				if ($language_code == 'en') {
					// default behaviour for translating into english
					$this->create_translation($post, ICL_LANGUAGE_CODE, $language_code);
				} else {
					// special behaviour: as of issue #166, the quality of translations is better
					// when the source language is english.
					// This even holds true when the english content is an automatic translation.
					$english_post = $this->get_or_create_english_translation($post);
					$this->create_translation($english_post, 'en', $language_code);
				}
			} catch (AutomaticTranslationException $e) {
				break;
			}
		}
	}

	private function accept_post($post) {
		return
				// do not translate drafts
				in_array($post->post_status, ['publish', 'revision'])
				// Only translate selected types
				&& in_array($post->post_type, ['page', 'event'])
				// Only translate on updates to the German and English content for now.
				// This is based on the assumption that content will always be available in these languages at first,
				// but gets rid of the case where manual changes to previously automatically translated content
				// are propagated into other languages.
				&& in_array(ICL_LANGUAGE_CODE, ['de', 'en']);
	}

	private function get_or_create_english_translation($post) {
		if (ICL_LANGUAGE_CODE == 'en') {
			return $post;
		}
		$english_post_id = $this->wpml_helper->get_translated_post_id($post, 'en');
		if ($english_post_id) {
			$english_post = get_post($english_post_id, OBJECT);
		} else {
			$english_post = $this->create_translation($post, ICL_LANGUAGE_CODE, 'en');
			$english_post = get_post($english_post, OBJECT); // convert to object
		}
		return $this->remove_disclaimer($english_post);
	}

	/**
	 * @param WP_Post $post WP_Post object
	 * @param string $source_language_code
	 * @param string $target_language_code
	 * @return array|null post in ARRAY_A format or null on error or when a manual translation exists
	 * @throws Exception when the translation failed
	 */
	private function create_translation($post, $source_language_code, $target_language_code) {
		if (!is_object($post)) {
			throw new RuntimeException("Given post is not an object");
		}
		$current_translation_id = $this->wpml_helper->get_translated_post_id($post, $target_language_code);
		if ($current_translation_id === $post->ID) {
			throw new RuntimeException("translated post id equal to source post id ($current_translation_id)");
		}
		if (!$this->should_create_automatic_translation($current_translation_id)) {
			return null;
		}
		try {
			$translated_post = $this->translation_service->translate_post($post, $source_language_code, $target_language_code);
		} catch (Exception $e) {
			$messages = get_option(ERR_MSGS_OPTION_KEY);
			$messages[] = "Fehler beim automatischen Uebersetzen: " . $e->getMessage();
			update_option(ERR_MSGS_OPTION_KEY, $messages);
			throw new AutomaticTranslationException($e);
		}
		$translated_post['post_content'] = self::TRANSLATION_DISCLAIMER . $translated_post['post_content'];
		if ($current_translation_id !== null) {
			$translated_post['ID'] = $current_translation_id;
		}
		$this->remove_save_post_hook(); // remove and re-add hook to avoid infinite loop
		// potential issue: the last row's content is changed too with wp_insert_post
		$translated_post_id = wp_insert_post($translated_post);
		$translated_post['ID'] = $translated_post_id;
		$this->add_save_post_hook();
		$this->wpml_helper->link_wpml($post->ID, $post->post_type, $translated_post_id, $target_language_code);
		$this->mark_as_automatic_translation($translated_post_id);
		return $translated_post;
	}

	private function should_create_automatic_translation($current_translation_id) {
		if ($current_translation_id === null) {
			return true;
		}
		if (get_post_meta($current_translation_id, self::AUTOMATIC_TRANSLATION_META_KEY, true)) {
			return true;
		}
		if (get_post_status($current_translation_id) == 'trash') {
			return true;
		}
		return false;
	}

	private function mark_as_automatic_translation($post_id) {
		add_post_meta($post_id, self::AUTOMATIC_TRANSLATION_META_KEY, true);
	}

	/**
	 * @param WP_Post $post WP_Post object
	 * @return WP_Post
	 */
	private function remove_disclaimer($post) {
		$result_post = clone $post;
		$content = $post->post_content;
		if (strrpos($content, self::TRANSLATION_DISCLAIMER, -strlen($content)) === false) {
			// does not contain disclaimer
			return $post;
		}
		$result_post->post_content = substr($content, strlen(self::TRANSLATION_DISCLAIMER));
		return $result_post;
	}

	public function add_save_post_hook() {
		add_action('save_post', [$this, 'on_save_post']);
	}

	private function remove_save_post_hook() {
		remove_action('save_post', [$this, 'on_save_post']);
	}
}
