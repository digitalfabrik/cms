/*globals wpml_mail_data, jQuery*/
jQuery( document ).ready(function() {
	"use strict";

	var selector = jQuery( '#wpml_mail_language_switcher_form').find('select' );
	selector.change(function() {
		var data;
		selector.prop( 'disabled', true );
		data = {
			'action': 'wpml_mail_language_switcher_form_ajax',
			'mail': wpml_mail_data.mail,
			'language': selector.val(),
			'nonce': wpml_mail_data.nonce
		};

		/** @namespace wpml_mail_data.ajax_url */
		/**
		 * @namespace wpml_mail_data.auto_refresh_page
		 * @type int
		 * */
		jQuery.post(wpml_mail_data.ajax_url, data, function() {
			selector.prop( 'disabled', false );
			selector.css( 'color', 'green' );
			if (1 === wpml_mail_data.auto_refresh_page) {
				location.reload();
			}
		});

	});

});
