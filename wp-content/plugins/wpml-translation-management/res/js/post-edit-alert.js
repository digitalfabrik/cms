/* global jQuery, window */

(function($) {

	$('document').ready(function() {

		var alert = $('.js-wpml-tm-post-edit-alert');

		if (0 === alert.length) {
			return;
		}

		alert.dialog({
			dialogClass: 'otgs-ui-dialog',
			closeOnEscape: false,
			draggable: false,
			modal: true,
			minWidth: 520,
			open: function(e) {
				$(e.target).closest('.otgs-ui-dialog').find('.ui-widget-header').remove();
			}
		});

		alert.on('click', '.js-wpml-tm-go-back', function(e) {
			e.preventDefault();
			window.history.go(-1);
		}).on('click', '.js-wpml-tm-use-standard-editor', function(e) {
			e.preventDefault();
			alert.dialog('close');
		});

	});

})(jQuery);
