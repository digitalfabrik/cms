jQuery(document).ready(function () {
	// sideload button into wpml metabox
	jQuery("#icl_div").append("" +
		"<form id='automatic-translation-button-form' method='post'>" +
		automatic_translation_button_vars.nonce_field +
		"<input type='hidden' name='automatic_translation_post' value='" + automatic_translation_button_vars.post + "'/>" +
		"<button id='automatic-translation-button-button' type='submit'>Automatic translation to other languages</button>" +
		"</form>"
	);
});
