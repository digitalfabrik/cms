<?php

class TranslationService {
	public function translate_post($post) {
		$translated_title = $this->translate_string($post->post_title);
		$translated_content = $this->translate_string($post->post_content);
		$translated_post = [
			'post_title' => $translated_title,
			'post_content' => $translated_content,
			'post_type' => $post->post_type,
			'post_status' => $post->post_status
		];
		return $translated_post;
	}

	public function translate_string($string) {
		return "TODO: TRANSLATION for $string";
	}
}