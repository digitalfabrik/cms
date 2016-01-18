jQuery(document).ready(function () {
	// sideload button into wpml metabox
	jQuery("#icl_div").append("" +
		"<form id='automatic-translation-button-form'>" +
		"<input type='hidden' />" +
		"<button id='automatic-translation-button-button' type='submit'>Automatic translation to other languages</button>" +
		"</form>"
	);
	// prevent submit default
	jQuery('#automatic-translation-button-form').click(function () {
		jQuery.ajax({
			type: 'POST',
			url: automatic_translation_button_vars.ajaxurl,
			data: {
				action: 'automatic-translation-button-translate',
				post: automatic_translation_button_vars.post
			},
			success: function () {
				window.location.reload();
			}
		});
		return false;
	});
});
