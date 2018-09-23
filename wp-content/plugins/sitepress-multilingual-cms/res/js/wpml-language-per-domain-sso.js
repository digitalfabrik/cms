jQuery(document).ready(function ($) {
	'use strict';

	$('.wpml_iframe').load(function() {
		if ( wpml_sso.is_expired ) {
			return;
		}
		if ( wpml_sso.is_user_logged_in ) {
			send_message_to_domains( 'wpml_is_user_signed_in', wpml_sso.current_user_id, this );
		} else {
			send_message_to_domains( 'wpml_is_user_signed_out', true, this );
		}
	});

	function send_message_to_domains(local_storage_key, value, iframe ) {
		iframe.contentWindow.postMessage(JSON.stringify({key: local_storage_key, data: value}), "*");
	}
});
