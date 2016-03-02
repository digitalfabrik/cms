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

add_filter('wp_api_extensions_output_post', function ($output_post) {
	$output_post['automatic_translation'] = get_post_meta($output_post['id'], TranslationManager::AUTOMATIC_TRANSLATION_META_KEY, true);
	return $output_post;
});

/*
 * Handling post and it's translation
 */ 	
class TranslationManager {
	const AUTOMATIC_TRANSLATION_META_KEY = 'automatic_translation';
	private $translation_service;

	public function __construct() {
		$this->translation_service = new TranslationService();
		$this->wpml_helper = new TranslationWpmlHelper();
	}

	public function update_post_translation($post_id) {
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
		return $english_post;
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
		if ($current_translation_id !== null) {
			$translated_post['ID'] = $current_translation_id;
		}
		
		// check if translation of possible parent posts exists before creating translation
		$parent_id = $this->translated_parent_exists( $post, $target_language_code );
		if( ! $parent_id ) {
			$parent_post = get_post( $this->get_post_parent($post->ID) );

			$this->translate_parent( $parent_post, $source_language_code, $target_language_code );
			$post = get_post($post->ID);
		} else {

		}
		
		$this->remove_save_post_hook(); // remove and re-add hook to avoid infinite loop
		// potential issue: the last row's content is changed too with wp_insert_post
		$translated_post_id = wp_insert_post($translated_post);
		$translated_post['ID'] = $translated_post_id;
		$this->add_save_post_hook();
		$this->wpml_helper->link_wpml($post->ID, $post->post_type, $translated_post_id, $target_language_code);
		$this->mark_as_automatic_translation($translated_post_id);
		
		//fixes wrong post_parent for autotranslation
		//after being saved for the first time, the parent of the translated page can be determined by looking at the parent in the source language
		$translated_post['post_parent'] = $this->wpml_helper->get_translation_post_parent( $translated_post['ID'], $source_language_code, $target_language_code );
		wp_update_post( $translated_post );
		// end fix wrong post_parent
		
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
	
	//check if translation of parent exists in target language code
	private function translated_parent_exists( $post, $target_language_code ) {
		//if page has post_status inherit, get post_parent
		//we dont need the current translation. the initial translation suffices
		$post = get_post($post->ID);
		$post_main_id = $post->ID;
		if( $post->post_status == 'inherit' ) {
			$post_main_id = $this->get_post_parent ( $post->ID );
		} 
		//get post_parent if post_status is not inherit
		$post_parent_id = $this->get_post_parent ( $post_main_id );
				
		if( $post_parent_id == 0 ) {
			return true; //there is no parent that needs translation
		}
		
		$post->ID = $post_parent_id;
		$post = get_post($post->ID);
		
		if( $this->wpml_helper->get_translated_post_id($post, $target_language_code) == NULL ) {
			return false; //translation of parent is missing
		} else {
			return $post->ID;
		}
	}
	
	private function get_post_parent ( $post_id ) {
		global $wpdb;
		
		$query = "SELECT post_parent FROM $wpdb->posts WHERE ID = '$post_id' AND post_status = 'inherit'";
		$parent = $wpdb->get_results($query, OBJECT);
		if( $parent[0]->post_parent )
			$post_id = $parent[0]->post_parent;
		
		$query = "SELECT post_parent FROM $wpdb->posts WHERE ID = '$post_id'";
		$parent = $wpdb->get_results($query, OBJECT);
		return $parent[0]->post_parent;
	}
	
	//parameters: post object of child, wpml source language code and target language code
	private function translate_parent( $post, $source_language_code, $target_language_code ) {
		$post_translate = get_post( $parent->post_parent );			
		$SubTranslation = new TranslationManager();

		$SubTranslation->create_translation($post, $source_language_code, $target_language_code);
	}

	private function mark_as_automatic_translation($post_id) {
		add_post_meta($post_id, self::AUTOMATIC_TRANSLATION_META_KEY, true);
	}

	public function add_save_post_hook() {
		// temporarily disabled due to issue #229. currently using the translation button instead
//		add_action('save_post', [$this, 'update_post_translation']);
	}

	private function remove_save_post_hook() {
		remove_action('save_post', [$this, 'update_post_translation']);
	}
}
