jQuery(document).ready(function () {
	jQuery( '.js-otgs-components-report-user-choice' ).click(function () {
		var spinner = jQuery(this).parent().prev();

		spinner.addClass('is-active');

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: jQuery(this).data('nonce-action'),
				nonce: jQuery(this).data('nonce-value'),
				agree: jQuery(this).is(':checked') ? 1 : 0,
				repo: jQuery(this).data('repo')
			},
			success: function () {
				spinner.removeClass('is-active');
			}
		});
	});
});
