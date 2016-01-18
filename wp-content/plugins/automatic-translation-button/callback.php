<?php

add_action('wp_ajax_automatic-translation-button-translate', function () {
	$post_id = $_POST['post'];
	if(!$post_id) {
		die("No post set");
	}

	require_once __DIR__ . '/plugin.php';
	$automaticTranslationButtonPlugin = new AutomaticTranslationButtonPlugin();
	$nonce_action = $automaticTranslationButtonPlugin->get_nonce_action($post_id);
	check_admin_referer($nonce_action);

	require_once __DIR__ . '/../automatic-translation/plugin.php';
	$translator = new TranslationManager();
	$translator->update_post_translation($post_id);
});
