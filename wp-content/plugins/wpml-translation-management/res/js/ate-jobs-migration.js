jQuery(document).ready(function () {
	var buttonId = '#wpml_tm_ate_source_id_migration_btn';

	jQuery(buttonId).click(function () {
		jQuery(this).attr('disabled', 'disabled');
		jQuery(this).after('<span class="wpml-fix-tp-id-spinner">' + icl_ajxloaderimg + '</span>');

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: jQuery(this).data('action'),
				nonce: jQuery(this).data('nonce'),
			},
			success: function () {
				jQuery(buttonId).removeAttr('disabled');
				jQuery('.wpml-fix-tp-id-spinner').remove();
			}
		});
	});
});
