<?php

add_action('init', function () {
	$post_id = $_POST['automatic_translation_post'];
	if (!$post_id) {
		return;
	}

	require_once __DIR__ . '/plugin.php';
	$automaticTranslationButtonPlugin = new AutomaticTranslationButtonPlugin();
	$nonce_action = $automaticTranslationButtonPlugin->get_nonce_action($post_id);
	check_admin_referer($nonce_action);

	require_once __DIR__ . '/../automatic-translation/plugin.php';
	$translator = new TranslationManager();
	$translator->update_post_translation($post_id);
});
